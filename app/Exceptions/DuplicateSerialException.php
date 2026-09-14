<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Thrown by AppointmentTransactionRepository when a serial write violates
 * the ats_salesline_serial_unique constraint. Keeps the controller from
 * needing to know anything about the underlying DB exception class.
 */
class DuplicateSerialException extends Exception
{
    //
}
