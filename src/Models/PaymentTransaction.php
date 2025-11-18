<?php

namespace RMS\Payment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class PaymentTransaction extends Model
{
    public const STATUS_INITIALIZED = 'initialized';
    public const STATUS_SENT = 'sent';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';

    protected $table = 'payment_transactions';

    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'request_payload' => 'array',
        'callback_payload' => 'array',
        'response_payload' => 'array',
        'verified_at' => 'datetime',
    ];

    public static function createRecord(array $attributes): self
    {
        return static::create($attributes);
    }

    public function isFinal(): bool
    {
        return in_array($this->status, [self::STATUS_SUCCESS, self::STATUS_FAILED], true);
    }

    public function isReturned(): bool
    {
        return $this->status === self::STATUS_RETURNED;
    }

    public function hasCallbackPayload(): bool
    {
        return !empty($this->callback_payload);
    }

    public function markSent(array $attributes = []): void
    {
        if ($this->isFinal()) {
            return;
        }

        $this->fill($attributes);
        $this->status = self::STATUS_SENT;
        $this->save();
    }

    public function markReturned(array $attributes = []): void
    {
        if ($this->isFinal()) {
            return;
        }

        $this->fill($attributes);
        $this->status = self::STATUS_RETURNED;
        $this->save();
    }

    public function markSuccess(array $attributes = []): void
    {
        if ($this->isFinal()) {
            return;
        }

        $this->fill($attributes);
        $this->status = self::STATUS_SUCCESS;
        $this->verified_at = $attributes['verified_at'] ?? Carbon::now();
        $this->save();
    }

    public function markFailed(array $attributes = []): void
    {
        if ($this->isFinal()) {
            return;
        }

        $this->fill($attributes);
        $this->status = self::STATUS_FAILED;
        $this->save();
    }
}

