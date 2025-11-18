<?php

namespace RMS\Payment\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentDriver extends Model
{
    protected $table = 'payment_drivers';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

