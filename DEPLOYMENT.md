# استقرار

این سند برای سرور لینوکس با PHP، MySQL یا MariaDB، Composer و Nginx یا Apache است. Docker الزامی نیست.

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

## سلامت بعد از انتشار

- `https://example.com/up` باید پاسخ موفق بدهد.
- ورود مدیر، فهرست درخواست‌ها و یک ثبت آزمایشی مشتری بررسی شود.
- `storage/logs/laravel.log` نباید رمز، OTP یا توکن داشته باشد.
- درگاه پیامک را با اقدام «پیامک آزمایشی» و مشاهده گزارش پیامک کنترل کنید.
