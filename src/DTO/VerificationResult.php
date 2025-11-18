<?php

namespace RMS\Payment\DTO;

class VerificationResult
{
    public function __construct(
        public readonly bool $successful,
        public readonly ?string $referenceId = null,
        public readonly ?string $transactionId = null,
        public readonly ?string $cardMask = null,
        public readonly ?array $raw = null,
        public readonly ?string $message = null,
    ) {
    }

    public static function success(string $referenceId, string $transactionId, array $raw = []): self
    {
        return new self(true, $referenceId, $transactionId, $raw['card'] ?? null, $raw);
    }

    public static function failure(string $message, array $raw = []): self
    {
        return new self(false, null, null, null, $raw, $message);
    }
}

