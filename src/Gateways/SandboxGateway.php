<?php

namespace RMS\Payment\Gateways;

use Illuminate\Support\Str;
use RMS\Payment\Contracts\Gateway;
use RMS\Payment\DTO\InitializationResult;
use RMS\Payment\DTO\PaymentRequest;
use RMS\Payment\DTO\VerificationResult;
use RMS\Payment\Models\PaymentTransaction;

class SandboxGateway implements Gateway
{
    protected string $driverName = 'sandbox';

    public function __construct(protected array $config = [])
    {
    }

    public function initialize(PaymentRequest $request): InitializationResult
    {
        $authority = $this->generateAuthority();
        $amount = (int) round($request->amount);
        $callbackUrl = $request->callbackUrl ?? $this->config['callback_url'] ?? null;

        if (!$callbackUrl) {
            return InitializationResult::failure('callback_url برای درگاه تستی مشخص نشده است.');
        }

        $payload = [
            'merchant_id' => $this->config['merchant_id'] ?? 'sandbox-merchant',
            'order_id' => $request->orderId,
            'amount' => $amount,
            'callback_url' => $callbackUrl,
        ];

        $transaction = PaymentTransaction::createRecord([
            'driver' => $this->driverName,
            'order_id' => $request->orderId,
            'authority' => $authority,
            'amount' => $amount,
            'currency' => $this->resolveCurrency($request->currency),
            'metadata' => $request->metadata,
            'request_payload' => $payload,
            'status' => PaymentTransaction::STATUS_INITIALIZED,
            'status_detail' => 'در انتظار تأیید کاربر در سندباکس',
        ]);
        $transaction->markSent();

        $fields = [
            'authority' => $authority,
            'order_id' => $request->orderId,
            'amount' => $amount,
            'callback_url' => $callbackUrl,
            'driver' => $this->driverName,
        ];

        $mode = $this->config['mode'] ?? 'form';
        if ($mode === 'redirect') {
            $redirectUrl = $this->buildGatewayUrl($fields);

            return InitializationResult::redirect($redirectUrl, $authority, $payload);
        }

        $action = $this->config['gateway_url'] ?? url('/payment/sandbox/gateway');

        return InitializationResult::form($action, $authority, $fields, 'POST', $payload);
    }

    public function verify(array $payload): VerificationResult
    {
        $authority = $payload['authority'] ?? $payload['Authority'] ?? null;

        if (!$authority) {
            return VerificationResult::failure('authority برای سندباکس ارسال نشده است.', $payload);
        }

        $transaction = PaymentTransaction::where('authority', $authority)
            ->where('driver', $this->driverName)
            ->first();

        if (!$transaction) {
            return VerificationResult::failure('تراکنش سندباکس یافت نشد.', $payload);
        }

        if ($transaction->isFinal()) {
            return new VerificationResult(
                $transaction->status === PaymentTransaction::STATUS_SUCCESS,
                $transaction->authority,
                $transaction->transaction_id,
                $transaction->card_mask,
                $transaction->response_payload,
                $transaction->status_detail
            );
        }

        $transaction->markReturned([
            'callback_payload' => $payload,
        ]);

        $status = strtolower($payload['status'] ?? 'ok');
        $message = $payload['message'] ?? ($status === 'ok' ? 'پرداخت تستی با موفقیت انجام شد.' : 'پرداخت تستی لغو شد.');

        if (!in_array($status, ['ok', 'success'], true)) {
            $transaction->markFailed([
                'status_detail' => $message,
                'response_payload' => $payload,
            ]);

            return VerificationResult::failure($message, $payload);
        }

        $refId = $payload['transaction_id'] ?? Str::upper(Str::random(10));
        $cardMask = $payload['card_mask'] ?? $this->fakeCardMask();

        $transaction->markSuccess([
            'transaction_id' => $refId,
            'card_mask' => $cardMask,
            'response_payload' => $payload,
            'status_detail' => $message,
        ]);

        return new VerificationResult(
            true,
            $authority,
            $refId,
            $cardMask,
            $payload,
            $message
        );
    }

    protected function buildGatewayUrl(array $fields): string
    {
        $base = $this->config['gateway_url'] ?? url('/payment/sandbox/gateway');
        $separator = str_contains($base, '?') ? '&' : '?';

        return $base.$separator.http_build_query($fields);
    }

    protected function generateAuthority(): string
    {
        return Str::uuid()->toString();
    }

    protected function fakeCardMask(): string
    {
        return '6037-****-****-'.str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
    }

    protected function resolveCurrency(?string $currency): string
    {
        return strtoupper($currency ?: 'IRT');
    }
}

