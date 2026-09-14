<?php

namespace App\Exceptions\Transfer;

use Exception;

class TransferValidationException extends Exception
{
    protected array $errors;

    public function __construct(array $errors)
    {
        $this->errors = $errors;
        parent::__construct('Transfer validation failed.');
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function render($request)
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'errors'  => $this->errors,
        ], 422);
    }
}
