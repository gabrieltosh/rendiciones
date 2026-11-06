<?php

namespace App\Services\Sap;

class SapExportResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $key = null,
        public readonly ?string $errorCode = null,
        public readonly string $errorMessage = '',
        /** @var array<int, string> DocEntry del borrador de entrada de mercancías por id de documento */
        public readonly array $drafts = []
    ) {
    }

    public static function success(string $key = null, array $drafts = []): self
    {
        return new self(true, $key, null, '', $drafts);
    }

    public static function failure(string $message, string $code = null): self
    {
        return new self(false, null, $code, $message);
    }
}
