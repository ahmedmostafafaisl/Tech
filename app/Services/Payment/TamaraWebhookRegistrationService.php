<?php

namespace App\Services\Payment;

use App\Models\TamaraWebhook;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


final class TamaraWebhookRegistrationService
{
    private const PATH = '/webhooks';

    /** Keys removed from anything Tamara returns before it is stored or shown. */
    /** Longest Tamara reply body written to the log / stored (characters). */
    private const MAX_LOGGED_BODY = 10000;

    private const SENSITIVE_KEYS = ['headers', 'authorization', 'secret', 'token', 'api_key', 'apikey'];

    // ------------------------------------------------------------------ what we are about to do

    public function environment(): string
    {
        return (string) app()->environment();
    }

    public function apiHost(): string
    {
        $url = trim((string) config('services.tamara.api_url'));
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if ($url === '' || $host === '') {
            throw TamaraWebhookException::configuration('TAMARA_API_URL is not configured (or is not a valid URL).');
        }

        return $host;
    }

    /** True unless the configured Tamara API is the sandbox. */
    public function targetsProduction(): bool
    {
        return ! str_contains($this->apiHost(), 'sandbox');
    }

    /** The URL Tamara must call, built from APP_URL and the route itself, never hard-coded. */
    public function webhookUrl(): string
    {
        $base = trim((string) config('app.url'));

        if ($base === '') {
            throw TamaraWebhookException::configuration('APP_URL is not configured.');
        }

        $parts = parse_url($base);
        $host  = strtolower((string) ($parts['host'] ?? ''));

        if ($host === '') {
            throw TamaraWebhookException::configuration('APP_URL is not a valid URL.');
        }

        if (($parts['scheme'] ?? '') !== 'https') {
            throw TamaraWebhookException::configuration('APP_URL must use https: Tamara delivers webhooks to a public https address.');
        }

        if ($this->isLocalHost($host)) {
            throw TamaraWebhookException::configuration("APP_URL ({$host}) is a local address Tamara cannot reach.");
        }

        return rtrim($base, '/') . route('tamara.webhook', [], false);
    }

    /** @return array<int, string> */
    public function desiredEvents(): array
    {
        $events = array_values(array_unique(array_filter((array) config('services.tamara.webhook.events'), 'is_string')));

        if ($events === []) {
            throw TamaraWebhookException::configuration('No Tamara webhook events are configured (TAMARA_WEBHOOK_EVENTS).');
        }

        return $events;
    }

    /** Checks everything that must be in place before any call is made. */
    public function preflight(): array
    {
        $this->apiKey();

        return [
            'environment' => $this->environment(),
            'api_host'    => $this->apiHost(),
            'production'  => $this->targetsProduction(),
            'url'         => $this->webhookUrl(),
            'events'      => $this->desiredEvents(),
        ];
    }

    public function activeRecord(): ?TamaraWebhook
    {
        return TamaraWebhook::activeFor($this->environment(), $this->apiHost())->latest('id')->first();
    }

    // ------------------------------------------------------------------ operations

    /**
     * Idempotent: never creates a second webhook for the same environment and Tamara host.
     *
     * @return array{action: string, webhook: TamaraWebhook}  action: created | updated | unchanged
     */
    public function register(): array
    {
        return $this->exclusively(fn() => $this->doRegister());
    }

    private function doRegister(): array
    {
        $target = $this->preflight();
        $type   = (string) config('services.tamara.webhook.type', 'order');
        $record = $this->activeRecord();

        if ($record !== null) {
            try {
                $remote = $this->fetchRemote($record);
            } catch (TamaraWebhookException $e) {
                if (! $e->isNotFoundAtTamara()) {
                    // We cannot tell whether it still exists, so creating another could duplicate it: refuse.
                    throw TamaraWebhookException::api(
                        "A webhook ({$record->webhook_id}) is already recorded but Tamara could not confirm it: {$e->getMessage()} Nothing was created, to avoid a duplicate.",
                        $e->httpStatus,
                        $e->detail
                    );
                }

                // Gone at Tamara: remember that, and register a fresh one below.
                $record->update(['status' => TamaraWebhook::STATUS_MISSING_REMOTE, 'active' => false, 'deactivated_at' => now()]);
                $record = null;
            }

            if ($record !== null) {
                if ($this->matches($remote, $target['url'], $target['events'], $type)) {
                    $record->update([
                        'url' => $target['url'],
                        'events' => $target['events'],
                        'type' => $type,
                        'remote_payload' => $this->sanitise($remote, $record->secretValue()),
                        'last_synced_at' => now(),
                    ]);

                    return ['action' => 'unchanged', 'webhook' => $record->refresh()];
                }

                return ['action' => 'updated', 'webhook' => $this->pushUpdate($record, $target['url'], $target['events'], $type)];
            }
        }

        return ['action' => 'created', 'webhook' => $this->create($target, $type)];
    }

    /**
     * The active webhook as Tamara currently reports it (safe to show: no headers, no secret).
     *
     * @return array{webhook: TamaraWebhook, remote: array}
     */
    public function retrieve(): array
    {
        $record = $this->activeRecord() ?? throw TamaraWebhookException::notRegistered();
        $remote = $this->fetchRemote($record);

        $record->update(['remote_payload' => $this->sanitise($remote, $record->secretValue()), 'last_synced_at' => now()]);

        return ['webhook' => $record->refresh(), 'remote' => $this->sanitise($remote, $record->secretValue())];
    }

    /**
     * Changes the URL and/or events at Tamara, then locally. The secret is never changed here (and is resent unchanged,
     * so an update cannot wipe the header Tamara authenticates with). Rotating it = delete, then register again.
     *
     * @param  array{url?: string, events?: array<int, string>}  $changes
     */
    public function update(array $changes): TamaraWebhook
    {
        return $this->exclusively(fn() => $this->doUpdate($changes));
    }

    private function doUpdate(array $changes): TamaraWebhook
    {
        $record = $this->activeRecord() ?? throw TamaraWebhookException::notRegistered();

        $url    = isset($changes['url']) ? $this->assertAllowedUrl((string) $changes['url']) : $record->url;
        $events = isset($changes['events']) ? array_values(array_unique($changes['events'])) : (array) $record->events;

        return $this->pushUpdate($record, $url, $events, (string) ($record->type ?: 'order'));
    }

    /** Deletes at Tamara first; only after that succeeds is the local record marked inactive (and kept). */
    public function delete(): TamaraWebhook
    {
        return $this->exclusively(fn() => $this->doDelete());
    }

    private function doDelete(): TamaraWebhook
    {
        $record = $this->activeRecord() ?? throw TamaraWebhookException::notRegistered();

        try {
            $this->send('DELETE', self::PATH . '/' . rawurlencode((string) $record->webhook_id), null, $record);
        } catch (TamaraWebhookException $e) {
            if (! $e->isNotFoundAtTamara()) {
                throw $e;   // not confirmed: the record stays active
            }
            // already absent at Tamara: the end state is the one we wanted
        }

        $record->update(['active' => false, 'status' => TamaraWebhook::STATUS_DELETED, 'deactivated_at' => now(), 'last_synced_at' => now()]);

        Log::info('Tamara webhook deleted', ['webhook_id' => $record->webhook_id, 'environment' => $record->environment]);

        return $record->refresh();
    }

    // ------------------------------------------------------------------ internals

    /**
     * One change at a time per environment and Tamara host. Without this two simultaneous runs could both see "no
     * webhook yet" and both create one. (Cache locks are per server with the `file` driver: use redis/database for a
     * lock that spans instances.)
     */
    private function exclusively(callable $work): mixed
    {
        $lock = Cache::lock('tamara-webhook:' . $this->environment() . ':' . $this->apiHost(), 120);

        try {
            return $lock->block(5, $work);
        } catch (LockTimeoutException) {
            throw TamaraWebhookException::api('Another Tamara webhook change is already in progress. Try again in a minute.');
        }
    }

    private function create(array $target, string $type): TamaraWebhook
    {
        $secret = bin2hex(random_bytes(32));

        // Stored (encrypted) BEFORE Tamara receives it, so a failure after the request can never lose the secret.
        $record = TamaraWebhook::create([
            'webhook_id' => null,
            'environment' => $target['environment'],
            'api_host' => $target['api_host'],
            'type' => $type,
            'url' => $target['url'],
            'events' => $target['events'],
            'secret' => $secret,
            'status' => TamaraWebhook::STATUS_PENDING,
            'active' => false,
        ]);

        try {
            $response = $this->send('POST', self::PATH, $this->body($type, $target['events'], $target['url'], $secret), $record);
        } catch (\Throwable $e) {
            $record->update([
                'status'         => TamaraWebhook::STATUS_FAILED,
                'remote_payload' => ['error' => [
                    'http_status' => $e instanceof TamaraWebhookException ? $e->httpStatus : null,
                    'detail'      => $e instanceof TamaraWebhookException ? ($e->detail ?? $e->getMessage()) : $e->getMessage(),
                ]],
            ]);

            throw $e;
        }

        $json = $response->json();
        $id   = is_array($json) && is_string($json['webhook_id'] ?? null) && $json['webhook_id'] !== '' ? $json['webhook_id'] : null;

        if ($id === null) {
            $record->update(['status' => TamaraWebhook::STATUS_FAILED]);

            throw TamaraWebhookException::api(
                'Tamara accepted the request but its reply has no webhook_id (reply fields: ' . implode(', ', is_array($json) ? array_keys($json) : ['not JSON']) . '). '
                    . 'Check the webhook list in the Tamara portal before running this again.'
            );
        }

        $record->update([
            'webhook_id' => $id,
            'status' => TamaraWebhook::STATUS_ACTIVE,
            'active' => true,
            'remote_payload' => $this->sanitise((array) $json, $secret),
            'registered_at' => now(),
            'last_synced_at' => now(),
        ]);

        Log::info('Tamara webhook registered', ['webhook_id' => $id, 'environment' => $record->environment, 'url' => $record->url]);

        return $record->refresh();
    }

    private function pushUpdate(TamaraWebhook $record, string $url, array $events, string $type): TamaraWebhook
    {
        $secret = $record->secretValue()
            ?? throw TamaraWebhookException::configuration('The stored webhook secret cannot be decrypted (was APP_KEY changed?). Delete and register the webhook again.');

        try {
            $response = $this->send('PUT', self::PATH . '/' . rawurlencode((string) $record->webhook_id), $this->body($type, $events, $url, $secret), $record);
        } catch (TamaraWebhookException $e) {
            if ($e->isNotFoundAtTamara()) {
                $record->update(['status' => TamaraWebhook::STATUS_MISSING_REMOTE, 'active' => false, 'deactivated_at' => now()]);

                throw TamaraWebhookException::api('Tamara no longer has this webhook. Run: php artisan tamara:webhook:register', 404);
            }

            throw $e;
        }

        $json = $response->json();
        $record->update([
            'url' => $url,
            'events' => $events,
            'type' => $type,
            'status' => TamaraWebhook::STATUS_ACTIVE,
            'remote_payload' => is_array($json) ? $this->sanitise($json, $secret) : $record->remote_payload,
            'last_synced_at' => now(),
        ]);

        Log::info('Tamara webhook updated', ['webhook_id' => $record->webhook_id, 'environment' => $record->environment, 'url' => $url]);

        return $record->refresh();
    }

    /** @return array<string, mixed> */
    private function fetchRemote(TamaraWebhook $record): array
    {
        $json = $this->send('GET', self::PATH . '/' . rawurlencode((string) $record->webhook_id), null, $record)->json();

        return is_array($json) ? $json : [];
    }

    private function body(string $type, array $events, string $url, string $secret): array
    {
        return ['type' => $type, 'events' => array_values($events), 'url' => $url, 'headers' => ['authorization' => $secret]];
    }

    private function matches(array $remote, string $url, array $events, string $type): bool
    {
        $remoteEvents = $remote['events'] ?? null;

        if (! is_array($remoteEvents)) {
            return false;
        }

        $a = array_map('strval', $remoteEvents);
        $b = array_map('strval', $events);
        sort($a);
        sort($b);

        return ($remote['url'] ?? null) === $url && $a === $b && (! isset($remote['type']) || $remote['type'] === $type);
    }

    private function assertAllowedUrl(string $url): string
    {
        $parts = parse_url($url);
        $host  = strtolower((string) ($parts['host'] ?? ''));

        if (($parts['scheme'] ?? '') !== 'https' || $host === '' || $this->isLocalHost($host)) {
            throw TamaraWebhookException::invalidInput('The webhook URL must be a public https URL.');
        }

        $allowed = array_map('strtolower', (array) config('services.tamara.webhook.allowed_hosts'));
        $allowed = $allowed !== [] ? $allowed : [strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST))];

        // Tamara sends the secret header to this URL: it may only ever point back at this application.
        if (! in_array($host, $allowed, true)) {
            throw TamaraWebhookException::invalidInput('The webhook URL host is not allowed: it must be this application\'s own host.');
        }

        if (rtrim((string) ($parts['path'] ?? ''), '/') !== rtrim(route('tamara.webhook', [], false), '/') || isset($parts['query'])) {
            throw TamaraWebhookException::invalidInput('The webhook URL must be this application\'s Tamara webhook endpoint (' . route('tamara.webhook', [], false) . ').');
        }

        return $url;
    }

    private function isLocalHost(string $host): bool
    {
        if (in_array($host, ['localhost', '127.0.0.1', '::1', '0.0.0.0'], true)) {
            return true;
        }

        foreach (['.test', '.local', '.localhost', '.internal'] as $suffix) {
            if (str_ends_with($host, $suffix)) {
                return true;
            }
        }

        return filter_var($host, FILTER_VALIDATE_IP) !== false
            && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }

    private function apiKey(): string
    {
        $key = trim((string) config('services.tamara.api_key'));

        if ($key === '') {
            throw TamaraWebhookException::configuration('TAMARA_API_KEY is not configured.');
        }

        return $key;
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.tamara.api_url'), '/'))
            ->withToken($this->apiKey())
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('services.tamara.timeout', 20))
            ->connectTimeout((int) config('services.tamara.connect_timeout', 5));
    }

    /** One call to Tamara. Errors are never swallowed and never leak the API key or the webhook secret. */
    private function send(string $method, string $path, ?array $body, ?TamaraWebhook $record): Response
    {
        $secret = $record?->secretValue();

        try {
            $response = $this->http()->send($method, $path, $body === null ? [] : ['json' => $body]);
        } catch (ConnectionException $e) {
            $detail = $this->redact($e->getMessage(), $secret);

            Log::error('Tamara webhook API call could not reach Tamara', [
                'request'    => $this->describeRequest($method, $path, $body),
                'exception'  => get_class($e),
                'error'      => $detail,
                'webhook_id' => $record?->webhook_id,
            ]);

            throw TamaraWebhookException::api('Could not reach Tamara: ' . $detail, null, $detail);
        }

        if ($response->failed()) {
            // The COMPLETE reply, not just its "message": Tamara explains what it rejected in the body.
            $detail = mb_substr($this->redact((string) $response->body(), $secret), 0, self::MAX_LOGGED_BODY);
            $reason = $response->json('message');
            $reason = is_string($reason) ? ': ' . $this->redact(mb_substr($reason, 0, 500), $secret) : '';

            Log::error('Tamara webhook API call failed', [
                'request'       => $this->describeRequest($method, $path, $body),
                'status'        => $response->status(),
                'reason'        => $response->reason(),
                'response_body' => $detail,
                'webhook_id'    => $record?->webhook_id,
                'record_id'     => $record?->id,
            ]);

            throw TamaraWebhookException::api("Tamara answered HTTP {$response->status()}{$reason}.", $response->status(), $detail);
        }

        return $response;
    }

    /** What was sent to Tamara, for the log: the real URL and body, with every header VALUE (the secret) masked. */
    private function describeRequest(string $method, string $path, ?array $body): array
    {
        $summary = ['method' => $method, 'url' => rtrim((string) config('services.tamara.api_url'), '/') . $path];

        if ($body !== null) {
            if (isset($body['headers']) && is_array($body['headers'])) {
                $body['headers'] = array_map(fn() => '[redacted]', $body['headers']);
            }

            $summary['body'] = $body;
        }

        return $summary;
    }

    /** Removes the API key and the secret (and any header-like field) from text or data that might be shown or stored. */
    private function redact(string $text, ?string $secret = null): string
    {
        foreach (array_filter([trim((string) config('services.tamara.api_key')), $secret, trim((string) config('services.tamara.notification_token'))]) as $value) {
            $text = str_replace($value, '[redacted]', $text);
        }

        return $text;
    }

    /** @return array<string, mixed> */
    private function sanitise(array $data, ?string $secret): array
    {
        $clean = [];

        foreach ($data as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), self::SENSITIVE_KEYS, true)) {
                continue;
            }

            $clean[$key] = is_array($value) ? $this->sanitise($value, $secret) : (is_string($value) ? $this->redact($value, $secret) : $value);
        }

        return $clean;
    }
}
