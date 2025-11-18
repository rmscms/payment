<?php

namespace RMS\Payment\Gateways;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RMS\Payment\Contracts\Gateway;
use RMS\Payment\DTO\InitializationResult;
use RMS\Payment\DTO\PaymentRequest;
use RMS\Payment\DTO\VerificationResult;
use RMS\Payment\Models\PaymentTransaction;

class ZarinpalGateway implements Gateway
{
    protected string $driverName = 'zarinpal';

    public function __construct(protected array $config = [])
    {
    }

    public function initialize(PaymentRequest $request): InitializationResult
    {
        $payload = [
            'merchant_id' => $this->merchantId(),
            'amount' => (int) round($request->amount),
            'callback_url' => $request->callbackUrl ?? $this->config['callback_url'] ?? null,
            'description' => $request->description ?? $this->config['description'] ?? ('پرداخت سفارش '.$request->orderId),
        ];

        if (!$payload['callback_url']) {
            return InitializationResult::failure('آدرس بازگشت (callback_url) تعریف نشده است.');
        }

        $metadata = $request->metadata ?? [];
        if ($request->customerMobile) {
            $metadata['mobile'] = $request->customerMobile;
        }
        $metadata['order_id'] = (string) ($metadata['order_id'] ?? $request->orderId);
        $metadata['user_id'] = (string) ($metadata['user_id'] ?? $request->metadata['user_id'] ?? '');
        array_walk($metadata, function (&$value) {
            if (is_int($value) || is_float($value)) {
                $value = (string) $value;
            } elseif (is_bool($value)) {
                $value = $value ? '1' : '0';
            }
        });
        $payload['metadata'] = array_filter($metadata, fn ($value) => $value !== null && $value !== '');
        if ($currency = $this->resolveCurrency($request->currency)) {
            $payload['currency'] = $currency;
        }

        // Log::info('payment.zarinpal.init_request', [
        //     'order_id' => $request->orderId,
        //     'payload' => $payload,
        // ]);

        $response = Http::withHeaders([
            'accept' => 'application/json',
            'content-type' => 'application/json',
        ])->post($this->endpoint('request'), $payload);

        $body = $response->json();
        $code = Arr::get($body, 'data.code');

        // Log::info('payment.zarinpal.init_response', [
        //     'order_id' => $request->orderId,
        //     'status_code' => $response->status(),
        //     'body' => $body,
        // ]);

        if ($response->failed() || $code !== 100) {
            $message = $this->messageForCode($code, 'در برقراری ارتباط با درگاه زرین‌پال خطا رخ داد.');

            PaymentTransaction::createRecord([
                'driver' => $this->driverName,
                'order_id' => $request->orderId,
                'amount' => (int) round($request->amount),
                'currency' => $this->resolveCurrency($request->currency),
                'metadata' => $request->metadata,
                'status' => PaymentTransaction::STATUS_FAILED,
                'status' => 'failed',
                'response_payload' => $body,
                'status_detail' => $message,
            ]);

            return InitializationResult::failure($message, $body);
        }

        $authority = Arr::get($body, 'data.authority');

        $transaction = PaymentTransaction::createRecord([
            'driver' => $this->driverName,
            'order_id' => $request->orderId,
            'authority' => $authority,
            'amount' => (int) round($request->amount),
            'currency' => $this->resolveCurrency($request->currency),
            'metadata' => $request->metadata,
            'request_payload' => $payload,
            'response_payload' => $body,
            'status' => PaymentTransaction::STATUS_INITIALIZED,
        ]);
        $transaction->markSent();

        $redirect = rtrim($this->config['start_pay_url'] ?? $this->defaultStartPay(), '/').'/'.$authority;

        return InitializationResult::redirect($redirect, $authority, $body);
    }

    public function verify(array $payload): VerificationResult
    {
        $authority = $payload['authority'] ?? $payload['Authority'] ?? null;
        if (!$authority) {
            return VerificationResult::failure('کد authority ارسال نشده است.');
        }

        $transaction = PaymentTransaction::where('authority', $authority)->first();
        if (!$transaction) {
            return VerificationResult::failure('تراکنشی با این شناسه یافت نشد.');
        }

        if ($transaction->isFinal()) {
            return VerificationResult::failure('این تراکنش قبلاً پردازش شده است.');
        }

        if ($transaction->isReturned() && $transaction->hasCallbackPayload()) {
            return VerificationResult::failure('این تراکنش در حال پردازش است، لطفاً منتظر بمانید.');
        }

        $transaction->markReturned([
            'callback_payload' => $payload,
        ]);

        $amount = (int) ($payload['amount'] ?? $transaction->amount);
        if ($amount <= 0) {
            return VerificationResult::failure('مبلغ تراکنش نامعتبر است.');
        }

        $verifyPayload = [
            'merchant_id' => $this->merchantId(),
            'amount' => $amount,
            'authority' => $authority,
        ];

        $response = Http::withHeaders([
            'accept' => 'application/json',
            'content-type' => 'application/json',
        ])->post($this->endpoint('verify'), $verifyPayload);

        $body = $response->json();
        $code = Arr::get($body, 'data.code');

        // Log::info('payment.zarinpal.verify_request', [
        //     'order_id' => $transaction->order_id,
        //     'payload' => $verifyPayload,
        // ]);

        $response = Http::withHeaders([
            'accept' => 'application/json',
            'content-type' => 'application/json',
        ])->post($this->endpoint('verify'), $verifyPayload);

        $body = $response->json();
        $code = Arr::get($body, 'data.code');

        // Log::info('payment.zarinpal.verify_response', [
        //     'order_id' => $transaction->order_id,
        //     'status_code' => $response->status(),
        //     'body' => $body,
        // ]);

        if ($response->failed() || !in_array($code, [100, 101], true)) {
            $message = $this->messageForCode($code, 'تأیید تراکنش با خطا مواجه شد.');

            $transaction->markFailed([
                'response_payload' => $body,
                'status_detail' => $message,
            ]);

            return VerificationResult::failure($message, $body);
        }

        $refId = Arr::get($body, 'data.ref_id');
        $cardPan = Arr::get($body, 'data.card_pan');
        $cardHash = Arr::get($body, 'data.card_hash');

        $transaction->markSuccess([
            'reference_id' => $refId,
            'transaction_id' => $refId,
            'card_pan' => $cardPan,
            'card_hash' => $cardHash,
            'response_payload' => $body,
            'status_detail' => Arr::get($body, 'data.message'),
        ]);

        return new VerificationResult(
            true,
            $authority,
            (string) $refId,
            $cardPan,
            $body,
            $this->messageForCode($code)
        );
    }

    protected function merchantId(): string
    {
        return $this->config['merchant_id'] ?? '';
    }

    protected function endpoint(string $type): string
    {
        $sandbox = (bool) ($this->config['sandbox'] ?? false);

        $base = $sandbox
            ? 'https://sandbox.zarinpal.com/pg/v4/payment'
            : 'https://api.zarinpal.com/pg/v4/payment';

        return match ($type) {
            'request' => $base.'/request.json',
            'verify' => $base.'/verify.json',
            'unverified' => $base.'/unVerified.json',
            'refund' => $base.'/refund.json',
            default => $base.'/request.json',
        };
    }

    protected function defaultStartPay(): string
    {
        $sandbox = (bool) ($this->config['sandbox'] ?? false);

        return $sandbox
            ? 'https://sandbox.zarinpal.com/pg/StartPay'
            : 'https://www.zarinpal.com/pg/StartPay';
    }

    protected function resolveCurrency(?string $currency): ?string
    {
        $curr = strtoupper($currency ?: ($this->config['default_currency'] ?? 'IRT'));

        return in_array($curr, ['IRR', 'IRT'], true) ? $curr : null;
    }

    protected function messageForCode(?int $code, ?string $fallback = null): string
    {
        if ($code === null) {
            return $fallback ?? 'خطای ناشناخته';
        }

        return $this->errorMessages[$code] ?? $fallback ?? 'خطای ناشناخته';
    }

    protected array $errorMessages = [
        -9 => 'خطای اعتبارسنجی',
        -10 => 'مرچنت یا آی‌پی معتبر نیست.',
        -11 => 'مرچنت فعال نشده است.',
        -12 => 'تلاش بیش از حد. لطفاً بعداً امتحان کنید.',
        -15 => 'ترمینال در حالت تعلیق است.',
        -16 => 'سطح پذیرنده کافی نیست.',
        -30 => 'اجازه‌ی تسویه اشتراکی شناور ندارید.',
        -31 => 'اطلاعات حساب تسویه نامعتبر است.',
        -32 => 'مقادیر تسهیم شناور معتبر نیست.',
        -33 => 'درصدهای تسهیم صحیح نیست.',
        -34 => 'جمع مبالغ تسهیم بیش از مبلغ تراکنش است.',
        -35 => 'تعداد افراد تسهیم بیش از حد مجاز است.',
        -40 => 'پارامتر اضافی نامعتبر است.',
        -50 => 'مبلغ پرداخت‌شده با مبلغ وریفای متفاوت است.',
        -51 => 'پرداخت ناموفق یا لغو شده است.',
        -52 => 'خطای غیرمنتظره رخ داد.',
        -53 => 'اتوریتی متعلق به این مرچنت نیست.',
        -54 => 'اتوریتی نامعتبر است.',
        100 => 'پرداخت با موفقیت انجام شد.',
        101 => 'تراکنش قبلاً وریفای شده است.',
    ];
}

