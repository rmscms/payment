# RMS Payment

پکیج `rmscms/payment` یک لایه‌ی ماژولار برای مدیریت درگاه‌های بانکی در اکوسیستم RMS است. هدف این لایه، جداسازی منطق پیچیده‌ی پرداخت از پروژه‌های مختلف (Shop، Core و …) و فراهم کردن یک API یکپارچه برای «شروع تراکنش» و «تأیید نهایی» است.

## امکانات فعلی

- ساختار روشن برای ثبت درگاه‌های متعدد (۲۰+ بانک داخلی).
- DTOهای مشخص برای درخواست و پاسخ: `PaymentRequest`, `InitializationResult`, `VerificationResult`.
- Driver Manager بر پایه‌ی `Illuminate\Support\Manager` برای resolve کردن درگاه‌ها.
- پشتیبانی از هر دو حالت «Redirect» و «Form POST» هنگام هدایت کاربر به بانک.
- Gateway پیش‌فرض Sandbox برای توسعه و تست.
- Driver آماده برای زرین‌پال (محیط واقعی و صندوق تست) همراه با لاگ‌گیری در دیتابیس و پشتیبانی از قابلیت‌هایی مثل کارت مشخص، currency و متد verify.
- Facade به نام `Payment` برای استفاده‌ی سریع (متدهای `start`, `verify`, `gateways`).
- مدل `PaymentTransaction` با وضعیت‌های مرحله‌ای (initialized, sent, returned, success, failed) برای ذخیره‌ی کامل لاگ تراکنش‌ها.

## نصب

```bash
composer require rmscms/payment
```

سپس برای راه‌اندازی کامل جداول و منوهای ادمین، دستور نصب را اجرا کنید:

```bash
php artisan payment:install
```

این دستور کانفیگ را پابلیش می‌کند، مایگریشن‌های پکیج را اجرا می‌کند، درایور پیش‌فرض را نصب/همگام‌سازی می‌کند و منوی پرداخت را به سایدبار ادمین اضافه می‌کند. در صورت نیاز می‌توانید درایور مشخصی را نصب کنید:

```bash
php artisan payment:install --driver=zarinpal
```

یا اگر در محیط Monorepo هستید:

1. فولدر را در `packages/rms/payment` قرار دهید.
2. در `composer.json` ریشه مسیر آن را به لیست repositories اضافه کنید:
   ```json
   {
     "repositories": [
       {
         "type": "path",
         "url": "packages/rms/payment",
         "options": { "symlink": true }
       }
     ]
   }
   ```
3. Autoload پروژه را به‌روزرسانی کنید:
   ```json
   "autoload": {
     "psr-4": {
       "RMS\\Payment\\": "packages/rms/payment/src/"
     }
   }
   ```
4. `composer dump-autoload`

### آماده‌سازی Playground سندباکس (پروژه‌ی `rms2-packages`)

1. پس از نصب، فایل کانفیگ را منتشر کنید:
   ```bash
   php artisan vendor:publish --provider="RMS\Payment\PaymentServiceProvider" --tag=payment-config
   ```
2. مایگریشن‌های هسته را اجرا کنید:
   ```bash
   php artisan migrate --path=packages/rms/payment/database/migrations
   ```
3. برای هر Driver که مسیر Migration دارد، دستور اختصاصی‌اش را بزنید (مثلاً زرین‌پال):
   ```bash
   php artisan payment:install-driver zarinpal
   ```
4. در `.env` مقدارهای زیر را ست کنید تا به سندباکس زرین‌پال متصل شوید:
   ```
   PAYMENT_GATEWAY=zarinpal
   PAYMENT_ZARINPAL_MERCHANT=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
   PAYMENT_ZARINPAL_SANDBOX=true
   PAYMENT_ZARINPAL_DESCRIPTION="پرداخت سندباکس فروشگاه"
   ```
5. حالا می‌توانید از مسیر `http://localhost:8000/payment/sandbox` فرم تست را باز کنید. این صفحه:
   - فرم ساخت تراکنش تستی با مبلغ دلخواه دارد.
   - امکان انتخاب Driver (بر اساس `PAYMENT_GATEWAY`) را فراهم می‌کند؛ برای تست آفلاین، مقدار `.env` را روی `sandbox` بگذارید.
   - بعد از `Start`، به درگاه تستی داخلی (`/payment/sandbox/gateway`) هدایت می‌شوید و می‌توانید سناریوی موفق/ناموفق را انتخاب کنید.
   - هر تراکنش در جدول `payment_transactions` با وضعیت و پیام دقیق (status_detail) لاگ می‌شود و در لیست پایین صفحه قابل مشاهده است.
   - Callback روی آدرس `payment/sandbox/callback` فراخوانی می‌شود و خروجی `Payment::verify()` به‌صورت کامل نمایش داده می‌شود.

## تنظیمات

فایل پیکربندی در `config/payment.php` منتشر می‌شود. مهم‌ترین کلیدها:

```php
return [
    'default' => env('PAYMENT_GATEWAY', 'sandbox'),
    'currency' => 'IRT',
    'amount_scale' => 1,
    'callback_url' => '/payment/callback',
    'certificates_path' => storage_path('payment/certs'),
    'admin' => [
        'enabled' => env('PAYMENT_ADMIN_ENABLED', true),
        'prefix' => env('PAYMENT_ADMIN_PREFIX', 'payment'),
        'route_name' => 'payment',
    ],
    'gateways' => [
        'sandbox' => [
            'driver' => RMS\Payment\Gateways\SandboxGateway::class,
            'merchant_id' => env('PAYMENT_SANDBOX_MERCHANT'),
            'secret_key' => env('PAYMENT_SANDBOX_SECRET'),
            'mode' => 'form',
            'title' => 'Sandbox Gateway',
            'migrations' => [
                // 'packages/rms/payment/database/migrations'
            ],
        ],
        'zarinpal' => [
            'driver' => RMS\Payment\Gateways\ZarinpalGateway::class,
            'merchant_id' => env('PAYMENT_ZARINPAL_MERCHANT'),
            'sandbox' => env('PAYMENT_ZARINPAL_SANDBOX', true),
            'description' => env('PAYMENT_ZARINPAL_DESCRIPTION', 'پرداخت اینترنتی'),
            'default_currency' => 'IRT',
            'title' => 'زرین‌پال',
            'migrations' => [
                'packages/rms/payment/database/migrations',
            ],
        ],
        // بانک‌های دیگر...
    ],
];
```

### پنل مدیریت درگاه‌ها و تراکنش‌ها

- با افزودن جدول `payment_drivers` می‌توانید عنوان، توضیحات، لوگو و وضعیت فعال/غیرفعال هر درگاه را نگه دارید.
- مسیرهای ادمین به صورت پیش‌فرض روی `admin/payment/*` فعال هستند و می‌توانید با مقادیر `PAYMENT_ADMIN_ENABLED` و `PAYMENT_ADMIN_PREFIX` آنها را کنترل کنید.
- دو صفحه‌ی آماده در پنل ادمین اضافه شده است:
  1. **Drivers**: مدیریت اطلاعات و وضعیت هر درگاه (CRUD کامل + همگام‌سازی با کانفیگ).
  2. **Transactions**: گزارش تراکنش‌ها با فیلتر، اکسپورت و نمایش Payload کامل هر رکورد.
- برای هماهنگی سریع درگاه‌ها با فایل کانفیگ، دکمه‌ی «Sync from config» در لیست درگاه‌ها اضافه شده است.

## نحوه استفاده

```php
use RMS\Payment\DTO\PaymentRequest;
use RMS\Payment\Facades\Payment;

$request = new PaymentRequest(
    orderId: 'ORDER-10023',
    amount: 250000,
    currency: 'IRT',
    callbackUrl: route('payments.callback'),
    customerName: 'Ali Customer',
    customerMobile: '09120000000',
    description: 'پرداخت سفارش 10023',
    metadata: ['email' => 'customer@example.com']
);

$init = Payment::start($request); // InitializationResult

if ($init->successful) {
    return redirect()->away($init->redirectUrl);
}
```

در مرحله‌ی بازگشت از بانک:

```php
$result = Payment::verify($request->all());

if ($result->successful) {
    // ثبت سفارش قطعی
} else {
    // نمایش خطا و لغو تراکنش
}
```

## توسعه‌ی درگاه جدید

1. یک کلاس جدید بسازید که `RMS\Payment\Contracts\Gateway` را پیاده‌سازی کند.
2. آن را در `config/payment.php` ثبت کنید:
   ```php
   'gateways' => [
       'zarinpal' => [
           'driver' => App\Payment\Gateways\ZarinpalGateway::class,
           'merchant_id' => env('ZARINPAL_MERCHANT'),
           'sandbox' => false,
       'mode' => 'form',
       'cert_path' => storage_path('payment/certs/zarinpal'),
           'migrations' => [
               'database/migrations/payment/zarinpal',
           ],
       ],
   ],
   ```
3. (اختیاری) برای حالت دو مرحله‌ای می‌توانید متد `verify` را به گونه‌ای بنویسید که هم توکن اولیه و هم نتیجه‌ی callback را بررسی کند.
4. اگر بانک نیاز به ذخیره‌ی کد رهگیری یا فیلدهای اختصاصی دارد، داخل Migration پروژه‌ی مصرف‌کننده جدول `payment_transactions` (یا مشابه) ایجاد کنید و `reference_id`, `transaction_id`, `card_mask` و سایر متادیتا را ذخیره نمایید.
5. برای بانک‌هایی که RSA 2048 یا گواهی اختصاصی می‌خواهند، فایل‌های `.key` و `.cer` را داخل مسیری که در `certificates_path` مشخص شده قرار دهید و داخل Driver خودتان از همان مسیر بخوانید.

## نصب Driverها

اگر در کانفیگ هر Driver مسیری برای Migration تعریف کنید، با دستور زیر می‌توانید آنها را اجرا کنید:

```bash
php artisan payment:install-driver        # استفاده از درایور پیش‌فرض
php artisan payment:install-driver zarinpal
```

دستور بالا ابتدا مایگریشن‌های پایه‌ی پکیج را اجرا می‌کند، سپس اگر درایور مسیر اختصاصی داشته باشد آن را نیز migrate می‌کند و در نهایت رکورد مربوط به درگاه را در جدول `payment_drivers` به‌روز/ایجاد می‌کند. اگر درایوری Migration نداشته باشد، فقط مرحله‌ی همگام‌سازی انجام می‌شود.

## وضعیت‌های ذخیره‌شده در جدول `payment_transactions`

- `initialized`: رکورد ایجاد شده ولی هنوز کاربر به درگاه هدایت نشده (مرحله ساخت توکن).
- `sent`: لینک یا فرم درگاه به کاربر داده شده است.
- `returned`: کاربر از درگاه برگشته و callback فراخوانی شده است.
- `success`: تراکنش با موفقیت وریفای شده و ref_id دریافت شده است.
- `failed`: به دلیل خطای بانکی یا لغو کاربر پرداخت ناموفق بوده است.

درایور زرین‌پال این وضعیت‌ها را به صورت خودکار به‌روزرسانی می‌کند و Message خطا را بر اساس کدهای رسمی زرین‌پال نگه می‌دارد. در صورت نوشتن درایور جدید کافی است از متدهای `PaymentTransaction` (`markSent`, `markReturned`, `markSuccess`, `markFailed`) استفاده کنید تا همهٔ بانک‌ها گزارش یکپارچه داشته باشند.

## نقشه‌ی راه

- افزودن Trait برای درگاه‌های RESTful (ارسال درخواست HTTP، مدیریت امضا و …).
- پشتیبانی از کیف پول و تسویه‌ی درون‌سیستمی.
- Eventهای `PaymentInitialized`, `PaymentVerified`.
- تست‌های خودکار برای هر Driver.
- ابزار Scaffold برای ایجاد Driver جدید به‌همراه Migration و تنظیمات.

با این ساختار، اتصال هر بانک جدید صرفاً پیاده‌سازی یک کلاس است و پروژه‌ی اصلی فقط با `PaymentClient` تعامل خواهد داشت.

## یکپارچه‌سازی در پنل ادمین

- پس از نصب و migrate، دو کنترلر ادمین آماده در اختیار دارید:
  - `RMS\Payment\Http\Controllers\Admin\PaymentDriversController` با روت پایه `admin/payment/drivers`
  - `RMS\Payment\Http\Controllers\Admin\PaymentTransactionsController` با روت پایه `admin/payment/transactions`
- اگر از قالب پیش‌فرض RMS استفاده می‌کنید کافی است در سایدبار پروژه (مثلاً `resources/views/vendor/cms/admin/layout/sidebar.blade.php`) لینک‌های زیر را اضافه کنید:

```blade
<x-cms::submenu-item
    title="مدیریت پرداخت"
    icon="ph-credit-card"
    :children="[
        ['title' => 'درگاه‌های پرداخت', 'url' => route('admin.payment.drivers.index')],
        ['title' => 'تراکنش‌ها', 'url' => route('admin.payment.transactions.index')],
    ]"
/>
```

- با این کار ادمین می‌تواند وضعیت درگاه‌ها را فعال/غیرفعال کند و گزارش تراکنش‌ها را ببیند؛ کارت‌های آماری نیز بر اساس همان فیلترهای جدول به‌روزرسانی می‌شوند.

### ????? ???? ???? ??? ????? (shop-test)

???? ?????? ????? ?????? ????? ???? ??? ?? ????? ???? ???????? ?? ??????? shop-test ??????? ??? ?? ???? ????:

`
# 1) ????? ???? ?????? ???? API ? ????? Blade
cd C:\laragon\www\rms2-packages\shop-test
php artisan serve --port=8000

# 2) ????? ??????? ???? ??????? public ? ????? /shop/*
php -S 127.0.0.1:8001 -t public

# 3) (???????) Vite dev server ???? ?????????? ?????
npm run dev -- --host 127.0.0.1 --port 5173
`

- ???? http://127.0.0.1:8000/admin ??? ????? ?????? ?? ????? ??????.
- ???? http://127.0.0.1:8000/shop ???? ??????? ?? ???? ?????? ??? ??????????? ??????? ?? ?? ????? ???? ???????? ??? ??????? ??? ???? ????? ????? ????? (??? /shop/products/{slug}) ????? ???????? API ?? ???? ??? ?????? ? timeout ?? ??????.
- ???? ??? ???????? ? Callback ???? ???????? ????????? ?? ?? ????? ???? ???? ??????? ???? ?????.
