<?php

namespace RMS\Payment;

use RMS\Payment\Contracts\Gateway;
use RMS\Payment\DTO\InitializationResult;
use RMS\Payment\DTO\PaymentRequest;
use RMS\Payment\DTO\VerificationResult;
use RMS\Payment\Support\GatewayManager;

class PaymentClient
{
    public function __construct(
        protected GatewayManager $manager,
        protected array $config = []
    ) {
    }

    public function start(PaymentRequest $request, ?string $driver = null): InitializationResult
    {
        $gateway = $this->resolveGateway($driver);
        $normalizedRequest = $this->normalizeRequest($request, $driver);

        return $gateway->initialize($normalizedRequest);
    }

    public function verify(array $payload, ?string $driver = null): VerificationResult
    {
        $gateway = $this->resolveGateway($driver);

        return $gateway->verify($payload);
    }

    public function gateways(): array
    {
        return array_keys($this->config['gateways'] ?? []);
    }

    protected function resolveGateway(?string $driver = null): Gateway
    {
        return $this->manager->driver($driver);
    }

    protected function normalizeRequest(PaymentRequest $request, ?string $driver = null): PaymentRequest
    {
        $callback = $request->callbackUrl ?? $this->config['callback_url'] ?? null;

        if (!$callback) {
            return $request;
        }

        $callback = $this->appendQueryParameters($callback, [
            'payment_order' => $request->orderId,
            'payment_driver' => $driver ?? $this->manager->getDefaultDriver(),
        ]);

        return new PaymentRequest(
            orderId: $request->orderId,
            amount: $request->amount,
            currency: $request->currency,
            callbackUrl: $callback,
            customerName: $request->customerName,
            customerMobile: $request->customerMobile,
            customerEmail: $request->customerEmail,
            description: $request->description,
            metadata: $request->metadata,
        );
    }

    protected function appendQueryParameters(string $url, array $parameters): string
    {
        $filtered = array_filter($parameters, fn ($value) => $value !== null && $value !== '');
        if (empty($filtered)) {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';
        $query = http_build_query($filtered);

        return $url.$separator.$query;
    }
}

