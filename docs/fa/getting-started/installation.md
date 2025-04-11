# نصب و راه‌اندازی

در این بخش به مراحل نصب و راه‌اندازی فریمورک فلسک‌پی‌اچ‌پی می‌پردازیم.

## نیازمندی‌ها

قبل از نصب فلسک‌پی‌اچ‌پی، اطمینان حاصل کنید که سیستم شما نیازمندی‌های زیر را داراست:

- PHP نسخه 7.4 یا بالاتر
- افزونه PDO برای PHP (برای کار با پایگاه داده)
- افزونه OpenSSL برای PHP
- افزونه JSON برای PHP
- افزونه Mbstring برای PHP
- Composer (برای مدیریت وابستگی‌ها)

### بررسی نسخه PHP

برای بررسی نسخه PHP نصب شده روی سیستم خود، دستور زیر را در ترمینال اجرا کنید:

```bash
php -v
```

### بررسی افزونه‌های نصب شده

برای مشاهده افزونه‌های PHP نصب شده:

```bash
php -m
```

یا می‌توانید یک فایل PHP با محتوای زیر ایجاد کرده و آن را اجرا کنید:

```php
<?php
phpinfo();
```

## نصب با Composer

ساده‌ترین راه برای نصب فلسک‌پی‌اچ‌پی استفاده از Composer است:

```bash
composer require amirdev-byte/flask-php
```

این دستور، فلسک‌پی‌اچ‌پی و تمام وابستگی‌های آن را نصب می‌کند.

## نصب دستی

اگر به هر دلیلی نمی‌خواهید از Composer استفاده کنید، می‌توانید به صورت دستی نصب کنید:

1. آخرین نسخه را از [صفحه Releases](https://github.com/amirdev-byte/flask-php/releases) دانلود کنید.
2. فایل دانلود شده را استخراج کنید.
3. پوشه استخراج شده را به مسیر پروژه خود منتقل کنید.
4. از autoloader خود برای بارگذاری کلاس‌ها استفاده کنید.

## ایجاد یک پروژه جدید

برای ایجاد یک پروژه جدید با فلسک‌پی‌اچ‌پی، می‌توانید مراحل زیر را دنبال کنید:

### 1. ایجاد ساختار پروژه

ابتدا یک پوشه جدید برای پروژه خود ایجاد کنید:

```bash
mkdir my-flask-app
cd my-flask-app
```

### 2. ایجاد composer.json

یک فایل `composer.json` با محتوای زیر ایجاد کنید:

```json
{
    "name": "yourname/my-flask-app",
    "description": "My first PHLask application",
    "type": "project",
    "require": {
        "php": ">=7.4",
        "amirdev-byte/flask-php": "^1.0"
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/"
        }
    }
}
```

### 3. نصب وابستگی‌ها

اجرای دستور زیر برای نصب وابستگی‌ها:

```bash
composer install
```

### 4. ایجاد فایل index.php

یک فایل `public/index.php` به عنوان نقطه ورودی برنامه ایجاد کنید:

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHLask\App;
use PHLask\Http\Request;
use PHLask\Http\Response;

// ایجاد نمونه برنامه
$app = App::getInstance();

// تعریف مسیر ساده
$app->get('/', function(Request $request, Response $response) {
    return $response->json([
        'message' => 'به برنامه فلسک‌پی‌اچ‌پی من خوش آمدید!',
        'status' => 'running'
    ]);
});

// اجرای برنامه
$app->run();
```

### 5. تنظیم وب سرور (Apache)

اگر از Apache استفاده می‌کنید، یک فایل `.htaccess` در پوشه `public` با محتوای زیر ایجاد کنید:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

### 6. تنظیم وب سرور (Nginx)

اگر از Nginx استفاده می‌کنید، پیکربندی زیر را به configuration فایل خود اضافه کنید:

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/your/project/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;  # مسیر سوکت PHP-FPM را تنظیم کنید
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

## استفاده با Swoole (اختیاری)

برای افزایش کارایی، می‌توانید فلسک‌پی‌اچ‌پی را با Swoole اجرا کنید. ابتدا افزونه Swoole را نصب کنید:

```bash
pecl install swoole
```

سپس یک فایل `server.php` با محتوای زیر ایجاد کنید:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use PHLask\App;
use Swoole\Http\Server;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;

$app = App::getInstance();

// تعریف مسیرها
// ...

$server = new Server('127.0.0.1', 8080);

$server->on('request', function (SwooleRequest $swooleRequest, SwooleResponse $swooleResponse) use ($app) {
    // تبدیل درخواست Swoole به درخواست PHLask
    $request = convertSwooleRequest($swooleRequest);
    
    // اجرای برنامه و دریافت پاسخ
    $response = $app->handleRequest($request);
    
    // ارسال پاسخ به کلاینت
    sendResponse($swooleResponse, $response);
});

$server->start();

// توابع کمکی برای تبدیل درخواست و پاسخ
// ...
```

## بررسی نصب

برای بررسی نصب صحیح، می‌توانید از PHP Built-in Server استفاده کنید:

```bash
cd public
php -S localhost:8000
```

حالا در مرورگر خود به آدرس `http://localhost:8000` بروید. باید پاسخی مشابه زیر دریافت کنید:

```json
{
    "message": "به برنامه فلسک‌پی‌اچ‌پی من خوش آمدید!",
    "status": "running"
}
```

## عیب‌یابی نصب

### مشکل: خطای Composer در نصب وابستگی‌ها

اگر هنگام نصب با Composer با خطا روبرو شدید، موارد زیر را بررسی کنید:

1. نسخه PHP را بررسی کنید (حداقل 7.4 باید باشد).
2. دسترسی‌های فایل را بررسی کنید.
3. از به‌روز بودن Composer اطمینان حاصل کنید:
   ```bash
   composer self-update
   ```

### مشکل: خطای "Class not found"

اگر با خطای "Class not found" مواجه شدید، بررسی کنید:

1. فایل autoload.php در مسیر درست include شده باشد.
2. دستور `composer dump-autoload` را اجرا کنید.
3. نام‌گذاری namespace کلاس‌ها را بررسی کنید.

### مشکل: مسیریابی کار نمی‌کند

اگر مسیریابی به درستی کار نمی‌کند، موارد زیر را بررسی کنید:

1. تنظیمات RewriteRule در Apache یا try_files در Nginx را بررسی کنید.
2. از فعال بودن mod_rewrite در Apache اطمینان حاصل کنید.
3. دسترسی‌های فایل `.htaccess` را بررسی کنید.

## گام بعدی

اکنون که فلسک‌پی‌اچ‌پی را نصب کردید، می‌توانید به راهنمای [شروع سریع](quick-start.md) مراجعه کنید تا با مفاهیم اصلی فریمورک آشنا شوید و اولین API خود را بسازید.