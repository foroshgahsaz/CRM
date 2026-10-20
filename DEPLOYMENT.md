# استقرار

نسخهٔ فعلی سامانه: **1.1.3** (فایل `VERSION` و `CHANGELOG.md`).

دریافت روی سرور:

```bash
git fetch origin
git checkout v1.1.3
# یا: git checkout cursor/release-v1-1-3-6a95
```

این سند برای سرور لینوکس با PHP، MySQL یا MariaDB، Composer و Nginx یا Apache است. Docker الزامی نیست.

**نصب محلی** (SQLite و `php artisan serve`) در `README.md` است؛ روی سرور تولید همان مراحل را با MySQL و بدون `serve` اجرا نکنید — از وب‌سرور + PHP-FPM و دستورهای بخش «انتشار نسخه» استفاده کنید.

## سرور

- PHP 8.3 و افزونه‌های ذکرشده در README
- MySQL 8 یا MariaDB 10.6+
- Composer 2
- امکان نوشتن در `storage` و `bootstrap/cache`
- Document Root فقط `public`
- HTTPS
- Cron
- در صورت امکان، یک فرآیند پایدار برای `queue:work`

`upload_max_filesize` و `post_max_size` را حداقل هم‌اندازه `max_upload_kb` تنظیمات سامانه بگذارید. `max_execution_time` برای درخواست معمولی ۶۰ ثانیه کافی است.

## وب‌سرور

نمونه Nginx:

```nginx
root /var/www/crm/public;
index index.php;

location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    include snippets/fastcgi-php.conf;
    fastcgi_pass unix:/run/php/php8.3-fpm.sock;
}

location ~ /\.(?!well-known).* {
    deny all;
}
```

دسترسی فایل:

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo find storage bootstrap/cache -type d -exec chmod 775 {} \;
sudo find storage bootstrap/cache -type f -exec chmod 664 {} \;
```

کاربر صف باید به همان `storage` دسترسی داشته باشد.

## محیط تولید

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com
APP_TIMEZONE=Asia/Tehran
APP_LOCALE=fa
LOG_LEVEL=warning

DB_CONNECTION=mysql
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=database
CACHE_STORE=database

SMS_GATEWAY=sms_ir
SMSIR_API_KEY=
SMSIR_LINE_NUMBER=
```

برای **SMS.ir** کلید را از پنل «برنامه‌نویسان» بگیرید و `SMSIR_LINE_NUMBER` را همان شماره خط ارسال (فقط رقم، مثلاً `30004505000017`) بگذارید. متن OTP و سایر پیام‌ها از قالب‌های داخل سامانه ساخته می‌شود و با متد `send/bulk` ارسال می‌گردد.

درگاه عمومی `http` (Bearer و فیلدهای `to`/`message`/`sender`) همچنان برای سرویس‌های دیگر در دسترس است:

```dotenv
SMS_GATEWAY=http
SMS_HTTP_URL=https://sms.example.com/send
SMS_HTTP_TOKEN=
SMS_SENDER=
```

`APP_KEY` را یک‌بار بسازید و پایدار نگه دارید. تغییر آن نشست‌ها و کوکی رمزشده را باطل می‌کند. فایل `.env` نباید از وب قابل خواندن باشد و نباید در Git باشد.

## انتشار نسخه

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan crm:check-env
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan crm:create-admin
```

`storage:link` فقط برای لوگوی عمومی است. مدارک درخواست روی دیسک `local` می‌مانند و لینک عمومی ندارند.

## صف و Cron

اگر systemd در دسترس است:

```ini
[Service]
User=www-data
WorkingDirectory=/var/www/crm
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --timeout=30
Restart=always
```

و در crontab کاربر وب:

```cron
* * * * * cd /var/www/crm && php artisan schedule:run >> /dev/null 2>&1
```

اگر worker دائمی ممکن نیست:

```cron
* * * * * cd /var/www/crm && php artisan schedule:run >> /dev/null 2>&1
* * * * * cd /var/www/crm && php artisan queue:work --stop-when-empty --tries=3 --timeout=30 >> /dev/null 2>&1
```

در این حالت پیامک ممکن است تا یک دقیقه تأخیر داشته باشد. ارسال موفق فقط وقتی ثبت می‌شود که درگاه پاسخ موفق بدهد.

## پشتیبان

- دامپ روزانه MySQL با `mysqldump --single-transaction`
- کپی `storage/app/private`
- نگهداری `APP_KEY` جدا از دامپ عمومی
- بازیابی را روی یک پایگاه خالی تمرین کنید: بازگرداندن دامپ، برگرداندن فایل‌ها، سپس `php artisan migrate --force`

حذف فیزیکی درخواست‌ها در سامانه پیاده‌سازی نشده است.

## به‌روزرسانی و بازگشت

1. از پایگاه و `storage/app/private` نسخه بگیرید.
2. کد جدید را دریافت کنید.
3. `composer install --no-dev --optimize-autoloader` و `npm ci && npm run build`
4. `php artisan down`
5. `php artisan migrate --force`
6. کش‌ها را دوباره بسازید و `php artisan up`

اگر migration برگشت‌پذیر و داده‌ای از دست نرفته باشد:

```bash
php artisan down
php artisan migrate:rollback --step=1 --force
# بازگشت به commit قبلی و composer install
php artisan up
```

اگر migration داده را تغییر داده، به‌جای rollback از پشتیبان همان نسخه استفاده کنید.

## عیب‌یابی

### `1050 Table 'users' already exists` / `cache doesn't exist`

یعنی یک **migrate قبلی نیمه‌کاره** مانده: جدول `users` ساخته شده ولی migrationها تمام نشده‌اند (مثلاً بعد از خطای 1071).

**نصب تازه بدون داده:**

```bash
php artisan config:clear
php artisan migrate:fresh --force
php artisan crm:create-admin --name="مدیر سامانه" --email="admin@example.com"
php artisan config:cache
```

`crm:check-env` (1.1.3+) همین وضعیت را گزارش می‌کند. تا migrate کامل نشود `crm:create-admin` اجرا نکنید.

### `1071 Specified key was too long` (MySQL / MariaDB)

برخی سرورها (MariaDB قدیمی یا `utf8mb4` با سقف ایندекс ۱۰۰۰ بایت) روی `varchar(255)` unique خطا می‌دهند.

**نسخه 1.1.2+** در bootstrap طول پیش‌فرض رشته را **191** کاراکتر می‌گذارد.

اگر migrate وسط کار fail شده (مثلاً جدول `users` ناقص مانده):

```bash
git checkout v1.1.2
php artisan config:clear
php artisan migrate:fresh --force    # فقط وقتی دادهٔ production ندارید
php artisan db:seed --force          # در صورت نیاز
php artisan crm:create-admin ...
```

### `SQLSTATE[HY000] [2002] Connection refused` (MySQL)

لاراول به `DB_HOST`/`DB_PORT` وصل نمی‌شود. در پاد Kubernetes یا هاست اشتراکی، MySQL معمولاً روی **`127.0.0.1` نیست**.

1. **`php artisan crm:check-env`** — host مؤثر و علت خطا را نشان می‌دهد.
2. در `.env` **`DB_HOST`** را درست کنید (نام Service در K8s، hostname هاست، یا IP دیتابیس — نه `127.0.0.1` مگر MySQL واقعاً کنار همان پاد باشد).
3. **نسخه 1.1.1+:** اگر `DB_HOST=127.0.0.1` مانده، می‌توانید در Deployment **`MYSQL_HOST`** یا **`CRM_DB_HOST`** تزریق کنید؛ اپ host را از آن می‌گیرد. نام DB/کاربر/رمز را با **`MYSQL_DATABASE`** / **`MYSQL_USER`** / **`MYSQL_PASSWORD`** هم می‌توان هم‌تراز کرد.
4. **`DB_DATABASE`** (مثلاً `crmdbcot_db`) و کاربر MySQL باید از قبل ساخته شده باشند.
5. بعد از تغییر env: **`php artisan config:clear`** (اگر قبلاً `config:cache` زده‌اید).
6. تست دستی: `mysql -h HOST -P 3306 -u USER -p`.

تا وقتی **`crm:check-env`** موفق نشود، `migrate`، `db:seed` و `crm:create-admin` خطا می‌دهند.

### مخلوط کردن دستورهای README با MySQL

اگر `DB_CONNECTION=mysql` است، **`touch database/database.sqlite` لازم نیست** و کمکی به migrate نمی‌کند. برای تولید:

- `.env` را دستی بسازید (یا از `.env.example` کپی و مقادیر MySQL و `APP_URL` را پر کنید).
- `composer install --no-dev --optimize-autoloader` (نه نصب dev روی production).
- `php artisan migrate --force` (با `APP_ENV=production` بدون `--force` تأیید تعاملی می‌خواهد).

### `php artisan serve`

فقط برای توسعه است. در production Document Root را روی `public` بگذارید؛ `serve` را به‌عنوان سرویس دائمی اجرا نکنید.

## سلامت بعد از انتشار

- `https://example.com/up` باید پاسخ موفق بدهد.
- ورود مدیر، فهرست درخواست‌ها و یک ثبت آزمایشی مشتری بررسی شود.
- `storage/logs/laravel.log` نباید رمز، OTP یا توکن داشته باشد.
- درگاه پیامک را با اقدام «پیامک آزمایشی» و مشاهده گزارش پیامک کنترل کنید.
