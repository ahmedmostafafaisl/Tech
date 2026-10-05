<?php

namespace App\Services\DY365;

/**
 * A DY call that got an answer, but a failed one. Extends RuntimeException so every
 * existing catch keeps working; the difference is that the HTTP status is no longer
 * thrown away, so callers (the circuit breaker, in particular) can tell "DY said 404
 * for this record" from "DY is down".
 */
class DyRequestFailed extends \RuntimeException
{
    public function __construct(string $message, public readonly ?int $status = null)
    {
        parent::__construct($message);
    }
}
