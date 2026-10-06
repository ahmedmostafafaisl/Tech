<?php

namespace App\Services\Payment;

/** A problem registering / reading / updating / deleting the Tamara webhook. The message is always safe to show an operator. */
final class TamaraWebhookException extends \RuntimeException
{
    public const CONFIGURATION = 'configuration';   // APP_URL / API key / API url unusable

    public const NOT_REGISTERED = 'not_registered'; // no active webhook recorded locally

    public const INVALID_INPUT = 'invalid_input';   // a requested change is not allowed

    public const API = 'api';                       // Tamara answered with an error, or could not be reached

    private function __construct(
        string $message,
        public readonly string $kind,
        public readonly ?int $httpStatus = null,
        /** Tamara's complete reply (or the connection error), with credentials removed. Safe to log and print. */
        public readonly ?string $detail = null,
    ) {
        parent::__construct($message);
    }

    public static function configuration(string $message): self
    {
        return new self($message, self::CONFIGURATION);
    }

    public static function notRegistered(): self
    {
        return new self('No active Tamara webhook is recorded for this environment. Run: php artisan tamara:webhook:register', self::NOT_REGISTERED);
    }

    public static function invalidInput(string $message): self
    {
        return new self($message, self::INVALID_INPUT);
    }

    public static function api(string $message, ?int $httpStatus = null, ?string $detail = null): self
    {
        return new self($message, self::API, $httpStatus, $detail);
    }

    public function isNotFoundAtTamara(): bool
    {
        return $this->kind === self::API && $this->httpStatus === 404;
    }
}
