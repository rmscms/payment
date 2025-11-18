<?php

return [
    'common' => [
        'id' => 'شناسه',
        'back' => 'بازگشت',
        'updated_at' => 'آخرین بروزرسانی',
    ],
    'drivers' => [
        'title' => 'درگاه‌های پرداخت',
        'actions' => [
            'create' => 'افزودن درگاه جدید',
        ],
        'messages' => [
            'synced' => 'لیست درگاه‌ها با تنظیمات هماهنگ شد.',
        ],
        'fields' => [
            'driver' => 'شناسه درایور',
            'title' => 'عنوان نمایشی',
            'slug' => 'اسلاگ',
            'logo' => 'آدرس لوگو',
            'documentation_url' => 'لینک مستندات',
            'description' => 'توضیحات',
            'sort_order' => 'ترتیب نمایش',
            'is_active' => 'وضعیت فعال',
        ],
        'hints' => [
            'driver' => 'برای استفاده در config و کد. بهتر است انگلیسی و یکتا باشد.',
            'slug' => 'برای نمایش در URL یا تگ‌های داده.',
            'logo' => 'آدرس تصویر (CDN یا مسیر داخلی).',
        ],
    ],
    'transactions' => [
        'title' => 'تراکنش‌های پرداخت',
        'fields' => [
            'driver' => 'درگاه',
            'order_id' => 'شماره سفارش',
            'authority' => 'Authority',
            'reference_id' => 'Reference ID',
            'transaction_id' => 'Transaction ID',
            'card_mask' => 'شماره کارت',
            'amount' => 'مبلغ',
            'currency' => 'واحد پول',
            'status' => 'وضعیت',
            'created_at' => 'تاریخ ایجاد',
            'verified_at' => 'تاریخ تایید',
        ],
        'statuses' => [
            'initialized' => 'ایجاد شده',
            'sent' => 'ارسال به درگاه',
            'returned' => 'بازگشت از درگاه',
            'success' => 'موفق',
            'failed' => 'ناموفق',
        ],
        'stats' => [
            'total' => 'همه تراکنش‌ها',
            'success' => 'موفق',
            'failed' => 'ناموفق',
            'today' => 'امروز',
        ],
    ],
];

