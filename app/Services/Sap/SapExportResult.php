<?php

namespace App\Services\Sap;

class SapExportResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $key = null,
        public readonly ?string $errorCode = null,
        public readonly string $errorMessage = ''
    ) {
    }

    public static function success(string $key = null): self
    {
        return new self(true, $key);
    }

    public static function failure(string $message, string $code = null): self
    {
        return new self(false, null, $code, $message);
    }
}
