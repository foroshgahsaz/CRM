# سامانه مدیریت درخواست مشتریان

سامانه ثبت، پیگیری، ارجاع، رسیدگی و تأیید نهایی درخواست‌های مشتریان است. مشتری بدون حساب سازمانی و با تأیید شماره موبایل درخواست می‌دهد. کارکنان از پنل `/admin` کار می‌کنند.

## پیش‌نیازها

- PHP 8.3 با افزونه‌های `mbstring`، `xml`، `curl`، `zip`، `bcmath`، `intl`، `gd`، `pdo_mysql` و `pdo_sqlite`
- Composer 2
- MySQL 8 یا MariaDB 10.6+ برای استقرار؛ SQLite برای توسعه و تست کافی است
- Node.js 20+ فقط برای ساخت دارایی‌های رابط عمومی
- وب‌سرور با Document Root روی `public`

نسخه‌های قفل‌شده پروژه: Laravel 13، Filament 5، Livewire 4، Spatie Permission 8، morilog/jalali 3، Tailwind CSS 4.

## نصب محلی

فقط برای توسعه روی SQLite. استقرار تولید با MySQL در `DEPLOYMENT.md` است (نسخه **1.1.1**، tag `v1.1.1`). قبل از migrate: `php artisan crm:check-env`.

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan crm:create-admin --name="مدیر سامانه" --email="admin@example.com"
npm install
npm run build
php artisan serve
```

پنل کارکنان: `http://127.0.0.1:8000/admin`  
صفحه مشتری: `http://127.0.0.1:8000`

دستور ایجاد مدیر، نقش‌ها و مجوزها را هم seed می‌کند. رمز را در مخزن یا اسکریپت عمومی نگذارید. اگر `--password` ندهید، یک رمز تصادفی فقط در خروجی ترمینال چاپ می‌شود.

## محیط

مقادیر نمونه در `.env.example` است. برای MySQL این‌ها را تنظیم کنید:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=crm
DB_USERNAME=crm
DB_PASSWORD=
```

`APP_DEBUG` در تولید باید `false` باشد. منطقه زمانی پیش‌فرض `Asia/Tehran` است. تاریخ عملیاتی در پایگاه داده میلادی ذخیره می‌شود و در رابط شمسی نمایش داده می‌شود.

## صف و زمان‌بند

اعلان داخلی پس از commit ثبت می‌شود. پیامک اطلاع‌رسانی در Job صف قرار می‌گیرد. OTP بلافاصله پس از ذخیره هش ارسال می‌شود تا خطا همان لحظه دیده شود.

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=30
php artisan schedule:work
```

زمان‌بند هر روز چالش‌های OTP منقضی را پاک می‌کند. اگر worker دائمی ندارید، در cron هر دقیقه `queue:work --stop-when-empty` را اجرا کنید. جزئیات در `DEPLOYMENT.md` است.

## تست

```bash
php artisan test --compact
```

تست‌ها از SQLite حافظه‌ای، صف sync و درگاه پیامک آزمایشی استفاده می‌کنند. نتیجه واقعی آخرین اجرا در `CHANGELOG.md` ثبت می‌شود.

## تصمیم‌های معماری

- جدول درخواست `service_requests` و مدل `ServiceRequest` است تا با Request مربوط به HTTP تداخل نداشته باشد.
- وضعیت و مسئول فقط داخل سرویس گردش کار عوض می‌شود. مدل در غیر این صورت استثنا می‌دهد.
- هر درخواست حداکثر یک مأموریت فعال دارد. ستون یکتای `active_lock` این را در پایگاه داده تضمین می‌کند. محدودیت مقصد «کاربر یا واحد» در سرویس اعمال می‌شود، چون نسخه فعلی Laravel برای این جدول `check` در Blueprint ندارد.
- `current_assignment_id` کلید خارجی حلقه‌ای به مأموریت‌ها ندارد. هماهنگی آن در همان تراکنش ارجاع حفظ می‌شود.
- تأیید و بستن دو رویداد جدا هستند. اگر هر دو مجوز باشد، می‌توانند در یک تراکنش و با دو رکورد تاریخچه اجرا شوند.
- وضعیت `awaiting_unit_claim` یعنی پرونده به واحد ارجاع شده و هنوز عضوی آن را نپذیرفته است. در این فاصله مالک کاربر ندارد و واحد مشخص است.
- پنل کارکنان یک Filament در `/admin` است و دکمه‌ها با مجوز، نه با نقش ثابت، کنترل می‌شوند.
- پیامک از اینترفیس `SmsGateway` می‌گذرد. در production از `sms_ir` (SMS.ir) یا `http` (Bearer سفارشی) استفاده کنید؛ `log` فقط برای توسعه است.
- نشست مشتری یک کوکی HttpOnly با توکن تصادفی است که فقط هش آن ذخیره می‌شود و به همان پرونده محدود است.

گردش کار، مجوزها و استقرار در `WORKFLOW.md`، `SECURITY.md` و `DEPLOYMENT.md` آمده است.
