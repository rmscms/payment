<?php

namespace RMS\Payment\DTO;

class InitializationResult
{
    public function __construct(
        public readonly bool $successful,
        public readonly ?string $referenceId = null,
        public readonly ?string $redirectUrl = null,
        public readonly ?array $gatewayPayload = null,
        public readonly ?string $message = null,
        public readonly ?string $formAction = null,
        public readonly ?array $formFields = null,
        public readonly string $formMethod = 'POST',
    ) {
    }

    public static function redirect(string $url, string $referenceId, array $payload = []): self
    {
        return new self(true, $referenceId, $url, $payload);
    }

    public static function form(string $action, string $referenceId, array $fields, string $method = 'POST', array $payload = []): self
    {
        return new self(true, $referenceId, null, $payload, null, $action, $fields, strtoupper($method));
    }

    public static function failure(string $message, array $payload = []): self
    {
        return new self(false, null, null, $payload, $message);
    }
}

