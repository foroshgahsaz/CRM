# تغییرات

## 1.2.15

- **صفحهٔ اصلی:** حذف باکس‌های عمودی مرحله ۱–۳؛ رادار وسط و سه مرحلهٔ افقی؛ پس‌زمینهٔ گرادیان بدون باکس سفید `main`
- **OTP SMS.ir:** ارسال با **`send/verify`** و `SMSIR_OTP_TEMPLATE_ID` (پیش‌فرض **235057**، پارامتر **CODE**)

## 1.2.14

- راهنمای **دیسک پایدار Runflare** برای `storage/app` (مدارک + لوگو) در `DEPLOYMENT.md`

## 1.2.13

- **`crm:seed-demo`** / `DemoScenarioSeeder`: ۱۰ درخواست، ۴ واحد، ۸ کاربر staff، ارجاعات و پیام‌ها
- خروجی حساب‌ها: `storage/app/demo-credentials.txt`

## 1.2.12

- صفحات عمومی برای تأیید SMS.ir: `/about`, `/contact`, `/privacy` (OTP و حریم خصوصی)
- راهنمای «سایت فعال و دارای محتوا» در `DEPLOYMENT.md`

## 1.2.11

- برچسب انتشار برای merge و `git pull` روی سرور (محتوا همان 1.2.0؛ فقط شمارهٔ `VERSION`).

## 1.2.0

**انتشار تجمیعی برای استقرار روی سرور** (شامل 1.1.12 → 1.1.17):

- UI: گرادیان، محتوای سفید، فونت **Yekan Bakh**
- ورود admin: `admin` / `admin`، رفع **419** سشن، استایل login
- **Date picker شمسی** (vendor در `public/vendor/jalali-datepicker`)
- **AdminUserSeeder**، گزارش‌ها sort=10، مستند OTP

روی سرور: `git pull origin main` (یا این branch) + `npm ci && npm run build` + migrate/seed.

## 1.1.17

- فونت **Yekan Bakh** از mainshop (`public/fonts/yekan`)
- پس‌زمینه **سفید** برای ناحیه محتوا (`main`)
- Date picker: بارگذاری JS/CSS از `public/vendor/jalali-datepicker` + init پایدار

## 1.1.16

- منوی **گزارش‌ها** در گروه «نظارت»: `navigationSort = 10`

## 1.1.15

- **Date picker شمسی** (`@majidh1/jalalidatepicker`) در فرم ثبت درخواست، گزارش‌ها و Filament (تاریخ خرید + فیلدهای نوع `date`)
- کامپوننت `JalaliDateInput` برای پنل admin

## 1.1.14

- **`ConfigureSessionForRequest`**: رفع 419 روی HTTP حتی وقتی `SESSION_SECURE_COOKIE=true` (وب + Filament)
- UI مدرن: پس‌زمینه aurora/grid، کارت‌های glow، هدر sticky، فرم‌های عمومی و login ادمین

## 1.1.13

- **`AdminUserSeeder`**: مدیر پیش‌فرض `admin` / `admin` (ایمیل `admin@admin.com`؛ در فرم ورود می‌توانید فقط `admin` بزنید)
- ورود پنل: **پیش‌فرض تب ایمیل/رمز** (برای bypass OTP)
- رفع **419 صفحه منقضی شد**: `trustProxies` + هشدار `crm:check-env` برای `SESSION_SECURE_COOKIE` روی HTTP
- استایل `/admin/login`: کارت سفید روی گرادیان (خوانایی label و عنوان)

## 1.1.12

- هویت بصری جدید: گرادیان **آبی–بنفش**، glassmorphism، دکمه‌های pill (صفحات عمومی + Filament admin theme)
- صفحهٔ اصلی hero شبیه مرجع (مراحل شماره‌دار، CTA سفید، رادار تزئینی)

## 1.1.11

- رفع **1071** روی Spatie `permissions` / `roles` (طول `name` و `guard_name` = 125؛ `model_type` = 125)

## 1.1.10

- رفع **1071** روی ایندекс `failed_jobs` (طول `connection`/`queue` = 100)
- ورود پنل: **پیش‌فرض تب موبایل** + سوئیچ واقعی (Livewire)
- `crm:install --fresh` دوباره کامل migrate می‌کند (شامل `deleted_at` روی users)

## 1.1.9

- سیاست انتشار: **فقط branch** `cursor/release-v1-1-9-6a95` — **بدون git tag**
- محتوا همان 1.1.8 (ورود موبایل پنل، OTP debug، reset admin، crm:install)

## 1.1.8

- ورود پنل **`/admin/login`**: **ایمیل + رمز** یا **موبایل + OTP** (کاربر staff باید `mobile` و نقش داشته باشد)
- **`CRM_OTP_DEBUG=true`**: دیدن OTP در `storage/logs/laravel.log` (تست بدون الگوی SMS.ir)
- **`php artisan crm:reset-admin-password {email}`** برای بازیابی رمز admin

## 1.1.7

- **`php artisan crm:install`** — migrate (+ اختیاری `--fresh`) و **`db:seed`** کامل (نوع درخواست، قالب پیامک، …)
- **`crm:create-admin`** دیگر فقط نقش seed نمی‌کند؛ کل `DatabaseSeeder` را اجرا می‌کند
- **`crm:check-env`**: بررسی وجود `request_types` و هشدار seed خالی
- مستند: ورود admin با **ایمیل**؛ OTP موبایل فقط بخش مشتری

## 1.1.6

- راهنمای **Runflare / متغیرهای محلی** و پر کردن **`APP_KEY`** با `php artisan key:generate --show`

## 1.1.5

- دستور **`php artisan crm:bootstrap-env`** برای ساخت `.env` از `.env.example`
- **`crm:check-env`**: هشدار نبود `.env` یا خالی بودن `APP_KEY`
- مستند استقرار: `.env` در Git نیست

## 1.1.4

شمارهٔ انتشار برای **pull روی سرور** — tag **`v1.1.4`**.

شامل اصلاحات تجمیعی از 1.1.1 تا 1.1.3: env MySQL در K8s، درگاه SMS.ir، طول رشته 191، تشخیص migrate ناقص، راهنمای `migrate:fresh`.

```bash
git fetch origin && git checkout v1.1.4
```

## 1.1.3

- **`crm:check-env`**: تشخیص migrate ناقص (users بدون cache / pending تکراری)
- **`crm:create-admin`**: توقف با پیام واضح اگر `roles` یا `cache` وجود ندارد
- مستند خطای **1050 Table already exists** در `DEPLOYMENT.md`

## 1.1.2

- **`Schema::defaultStringLength(191)`** برای سازگاری migrate با MySQL/MariaDB (رفع خطای `1071 Specified key was too long` روی ایندекс `email` و مشابه)
- مستند عیب‌یابی migrate ناقص در `DEPLOYMENT.md`

## 1.1.1

- خواندن تنظیم MySQL از **`MYSQL_*`** / **`CRM_DB_*`** وقتی `DB_HOST` هنوز `127.0.0.1` یا خالی است (مناسب Kubernetes)
- دستور **`php artisan crm:check-env`** برای تشخیص Connection refused قبل از migrate
- به‌روزرسانی `.env.example` و `DEPLOYMENT.md`

## 1.1.0

- درگاه **`sms_ir`** برای [SMS.ir](https://sms.ir) (متد REST `send/bulk`، هدر `X-API-KEY`)؛ متغیرهای `SMSIR_API_KEY` و `SMSIR_LINE_NUMBER`
- به‌روزرسانی `DEPLOYMENT.md` و `.env.example` برای استقرار با SMS.ir
- بخش عیب‌یابی اتصال MySQL و تفکیک نصب محلی از تولید

آزمون: `tests/Unit/SmsIrGatewayTest.php` و مجموعه قبلی (`php artisan test --compact`).

### محدودیت‌های شناخته‌شده (1.1.0)

- در production `SMS_GATEWAY=sms_ir` (یا `http`) و مقادیر مربوطه را تنظیم کنید؛ `log` فقط برای توسعه است.
- ارسال با پترن verify در SMS.ir در این نسخه استفاده نمی‌شود؛ متن از قالب‌های CRM ساخته می‌شود.

## 1.0.0

سامانه عملیاتی ثبت و رسیدگی به درخواست مشتری:

- ثبت عمومی با OTP، کد رهگیری، فرم شمسی و پیوست خصوصی
- پیگیری محدود به همان پرونده
- گردش کار ارجاع، پذیرش واحد، مدرک تکمیلی، تکمیل کارشناسی، تأیید، رد، بستن، بایگانی و بازگشایی
- پنل Filament برای درخواست، کاربر، نقش، واحد، نوع درخواست، فیلد، SLA، قالب پیامک، گزارش، تنظیمات، پیامک و حسابرسی
- اعلان داخلی و پیامک صف‌شده با درگاه قابل تعویض
- گزارش عملکرد و خروجی CSV

آزمون خودکار با `php artisan test --compact` روی PHP 8.3، SQLite حافظه‌ای و درگاه پیامک آزمایشی اجرا شد: ۲۷ تست و ۱۶۱ assertion موفق.

### محدودیت‌های شناخته‌شده

- درگاه پیامک پیش‌فرض `log` است. در production تا وقتی `SMS_GATEWAY=http` و توکن محیط تنظیم نشود، ارسال ناموفق ثبت می‌شود و موفق فرض نمی‌گردد.
- قرارداد HTTP عمومی است و SDK فروشنده خاصی ندارد.
- تست‌ها SQLite هستند. محدودیت یک مأموریت فعال روی MySQL هم با ایندکس یکتا است، اما محاسبه میانگین گزارش برای MySQL جدا از SQLite نوشته شده و روی سرور MySQL در این محیط اجرا نشده است.
- رابط در تست HTTP و Livewire بررسی شده است. مرورگر گرافیکی جداگانه در این تحویل به‌عنوان معیار قبولی استفاده نشده است.
- اگر worker صف نباشد، پیامک اطلاع‌رسانی تا اجرای cron معطل می‌ماند. OTP منتظر صف نمی‌ماند.
