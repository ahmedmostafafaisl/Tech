<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * A webhook registered with Tamara by this application.
 *
 * `secret` is encrypted at rest and hidden from every serialisation, so it cannot reach an API response or a log by
 * accident. The only way to read it is secretValue(), used to compare an incoming Authorization header and to resend it
 * to Tamara when the webhook is updated.
 */
class TamaraWebhook extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_FAILED = 'failed';

    public const STATUS_DELETED = 'deleted';

    public const STATUS_MISSING_REMOTE = 'missing_remote';

    protected $table = 'tamara_webhooks';

    protected $fillable = [
        'webhook_id', 'environment', 'api_host', 'type', 'url', 'events', 'secret', 'status', 'active',
        'remote_payload', 'registered_at', 'last_synced_at', 'deactivated_at',
    ];

    protected $hidden = ['secret', 'remote_payload'];

    protected $casts = [
        'events'         => 'array',
        'remote_payload' => 'array',
        'secret'         => 'encrypted',
        'active'         => 'boolean',
        'registered_at'  => 'datetime',
        'last_synced_at' => 'datetime',
        'deactivated_at' => 'datetime',
    ];

    /** The active webhook for an environment at a Tamara API host (there is at most one). */
    public function scopeActiveFor(Builder $query, string $environment, string $apiHost): Builder
    {
        return $query->where('active', true)
            ->where('environment', $environment)
            ->where('api_host', $apiHost);
    }

    /** The decrypted secret, or null when it cannot be decrypted (for example the APP_KEY changed). */
    public function secretValue(): ?string
    {
        try {
            $value = $this->secret;
        } catch (\Throwable) {
            return null;
        }

        return is_string($value) && $value !== '' ? $value : null;
    }

    /** What is safe to show an operator: never the secret, never Tamara's raw reply. */
    public function toSafeArray(): array
    {
        return [
            'webhook_id'     => $this->webhook_id,
            'environment'    => $this->environment,
            'api_host'       => $this->api_host,
            'type'           => $this->type,
            'url'            => $this->url,
            'events'         => $this->events,
            'status'         => $this->status,
            'active'         => $this->active,
            'registered_at'  => optional($this->registered_at)->toIso8601String(),
            'last_synced_at' => optional($this->last_synced_at)->toIso8601String(),
        ];
    }
}
