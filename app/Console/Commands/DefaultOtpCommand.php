<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\Auth\DefaultOtp;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Server-side control of the optional default OTP (see App\Services\Auth\DefaultOtp).
 *
 *   php artisan otp:default status
 *   php artisan otp:default set-code 1234
 *   php artisan otp:default set-phones "0501234567, +966509876543"     (separate numbers with commas; "*" is ignored in production)
 *   php artisan otp:default enable
 *   php artisan otp:default disable
 *   php artisan otp:default clear                                      (off, and forget the code and the allowlist)
 *
 * It is a command, not an API route, on purpose: the settings API is readable without a login and writable by
 * any logged-in user, and a switch that opens the OTP step must not be reachable from there.
 */
class DefaultOtpCommand extends Command
{
    protected $signature = 'otp:default
        {action=status : status | enable | disable | set-code | set-phones | clear}
        {value? : the 4-digit code (set-code) or the phone list (set-phones)}
        {--yes : do not ask for confirmation when enabling in production}';

    protected $description = 'Manage the optional default OTP for test / app-review accounts';

    public function handle(): int
    {
        return match ($this->argument('action')) {
            'status'     => $this->doStatus(),
            'enable'     => $this->doEnable(),
            'disable'    => $this->doDisable(),
            'set-code'   => $this->doSetCode(),
            'set-phones' => $this->doSetPhones(),
            'clear'      => $this->doClear(),
            default      => $this->refuse('Unknown action. Use: status | enable | disable | set-code | set-phones | clear'),
        };
    }

    private function doStatus(): int
    {
        $enabled    = Setting::isActive(DefaultOtp::KEY_ENABLED);
        $code       = trim((string) Setting::get(DefaultOtp::KEY_CODE));
        $phones     = DefaultOtp::parsePhones((string) Setting::get(DefaultOtp::KEY_PHONES));
        $production = app()->environment('production');

        $this->table(['Setting', 'Value'], [
            ['Environment', app()->environment()],
            ['Switch (' . DefaultOtp::KEY_ENABLED . ')', $enabled ? 'ON' : 'off'],
            ['Code (' . DefaultOtp::KEY_CODE . ')', $code === '' ? 'not set' : (DefaultOtp::isValidCode($code) ? 'set (4 digits, hidden)' : 'INVALID (must be exactly 4 digits)')],
            ['Allowlist (' . DefaultOtp::KEY_PHONES . ')', $phones === [] ? 'empty (applies to nobody)' : implode(', ', $phones)],
        ]);

        $effective = array_diff($phones, ['*']);

        if (! $enabled) {
            $this->info('Default OTP is OFF: every account gets a random OTP by SMS.');
        } elseif (! DefaultOtp::isValidCode($code) || $phones === []) {
            $this->warn('Switch is ON but the code or allowlist is missing/invalid, so it is NOT active for anyone.');
        } elseif (in_array('*', $phones, true) && ! $production) {
            $this->warn('ACTIVE for EVERY account (allowlist contains "*"; allowed outside production only).');
        } else {
            $this->warn('ACTIVE for ' . count($effective) . ' account(s): ' . implode(', ', $effective));
        }

        if ($production && in_array('*', $phones, true)) {
            $this->line('Note: "*" is ignored in production; only the explicit numbers above count.');
        }

        return self::SUCCESS;
    }

    private function doEnable(): int
    {
        $code   = trim((string) Setting::get(DefaultOtp::KEY_CODE));
        $phones = DefaultOtp::parsePhones((string) Setting::get(DefaultOtp::KEY_PHONES));

        if (! DefaultOtp::isValidCode($code)) {
            return $this->refuse('Set a 4-digit code first:  php artisan otp:default set-code 1234');
        }

        if ($phones === []) {
            return $this->refuse('Set the allowlist first:  php artisan otp:default set-phones "0501234567"');
        }

        if (
            app()->environment('production') && ! $this->option('yes')
            && ! $this->confirm('This is PRODUCTION. Accounts on the allowlist will accept a fixed OTP. Enable it?')
        ) {
            return $this->refuse('Not enabled.');
        }

        $this->write(DefaultOtp::KEY_ENABLED, 'true');
        $this->audit('enable');
        $this->warn('Default OTP is now ON.');

        return $this->doStatus();
    }

    private function doDisable(): int
    {
        $this->write(DefaultOtp::KEY_ENABLED, 'false');
        $this->audit('disable');
        $this->info('Default OTP is now OFF.');

        return self::SUCCESS;
    }

    private function doSetCode(): int
    {
        $code = trim((string) $this->argument('value'));

        if (! DefaultOtp::isValidCode($code)) {
            return $this->refuse('The code must be exactly 4 digits, e.g.  php artisan otp:default set-code 1234');
        }

        $this->write(DefaultOtp::KEY_CODE, $code);
        $this->audit('set-code');
        $this->info('Code saved (it is not shown again).');

        return self::SUCCESS;
    }

    private function doSetPhones(): int
    {
        $raw = (string) $this->argument('value');

        foreach (DefaultOtp::tokens($raw) as $token) {
            if (! DefaultOtp::isValidPhoneToken($token)) {
                return $this->refuse("\"{$token}\" is not a valid phone number (9-15 digits, optional +) and not \"*\". Separate numbers with commas.");
            }
        }

        $phones = DefaultOtp::parsePhones($raw);

        if ($phones === []) {
            return $this->refuse('Give at least one phone number, e.g.  php artisan otp:default set-phones "0501234567, +966509876543"');
        }

        $this->write(DefaultOtp::KEY_PHONES, implode(',', $phones));
        $this->audit('set-phones');
        $this->info('Allowlist saved: ' . implode(', ', $phones));

        if (in_array('*', $phones, true) && app()->environment('production')) {
            $this->warn('"*" is ignored in production; list the exact numbers instead.');
        }

        return self::SUCCESS;
    }

    private function doClear(): int
    {
        $this->write(DefaultOtp::KEY_ENABLED, 'false');
        $this->write(DefaultOtp::KEY_CODE, '');
        $this->write(DefaultOtp::KEY_PHONES, '');
        $this->audit('clear');
        $this->info('Default OTP is OFF and its code and allowlist were cleared.');

        return self::SUCCESS;
    }

    private function write(string $key, string $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /** Who changed it is logged; the code itself never is. */
    private function audit(string $action): void
    {
        Log::warning('Default OTP setting changed', [
            'action'  => $action,
            'os_user' => get_current_user(),
            'env'     => app()->environment(),
        ]);
    }

    private function refuse(string $message): int
    {
        $this->error($message);

        return self::FAILURE;
    }
}
