# PHLask | فلسک پی‌اچ‌پی

<p align="center">
  <img src="/docs/img/logo.jpg" alt="PHLask Logo" width="200" height="200">
</p>

<p align="center">
  <b>فریمورک سبک و قدرتمند PHP برای ساخت API و وب اپلیکیشن‌های مدرن</b>
</p>

<p align="center">
  <a href="docs/fa/getting-started/installation.md">نصب</a> •
  <a href="docs/fa/getting-started/quick-start.md">شروع سریع</a> •
  <a href="#features">ویژگی‌ها</a> •
  <a href="docs/fa">مستندات</a> •
  <a href="examples">نمونه‌ها</a> •
  <a href="docs/fa/roadmap.md">نقشه راه</a> •
  <a href="CONTRIBUTING.md">مشارکت</a> •
  <a href="LICENSE">مجوز</a>
</p>

---

## معرفی

فلسک‌پی‌اچ‌پی یک فریمورک سبک و قدرتمند PHP است که با الهام از فریمورک Flask در پایتون طراحی شده است. این فریمورک ابزاری
عالی برای ساخت API‌های RESTful و وب اپلیکیشن‌های مدرن با معماری میکروسرویس ارائه می‌دهد.

ویژگی اصلی فلسک‌پی‌اچ‌پی، سادگی و انعطاف‌پذیری آن است. شما می‌توانید به سرعت یک API یا وب اپلیکیشن را راه‌اندازی کنید و
با استفاده از کتابخانه‌های ارائه شده، مسیریابی، اتصال به پایگاه داده، احراز هویت و مدیریت درخواست‌ها را به راحتی انجام
دهید.

## <a name="features"></a>ویژگی‌ها

### مسیریابی قدرتمند

- پشتیبانی از تمام متدهای HTTP (GET, POST, PUT, DELETE, PATCH, OPTIONS)
- پشتیبانی از پارامترهای مسیر `{param}` و پارامترهای اختیاری `{param?}`
- تطبیق الگوهای مسیر با استفاده از Regular Expressions

### میان‌افزار (Middleware)

- پشتیبانی کامل از PSR-15
- زنجیره اجرای میان‌افزارها
- میان‌افزارهای آماده مانند CORS، احراز هویت و غیره

### مدیریت درخواست و پاسخ

- پیاده‌سازی کامل PSR-7
- پشتیبانی از JSON، HTML، Text و Redirect
- دسترسی آسان به پارامترهای درخواست

### ابزارهای پایگاه داده

- کوئری بیلدر قدرتمند با سینتکس روان
- مدیریت اتصال‌های پایگاه داده
- سیستم مدل ساده (ORM)
- پشتیبانی از تراکنش‌ها

### امنیت

- مدیریت خطاهای HTTP
- فیلترینگ داده‌های ورودی
- احراز هویت و مجوزدهی

### انعطاف‌پذیری

- معماری کاملاً ماژولار
- سازگار با PSR
- قابلیت استفاده در کنار سایر کتابخانه‌ها و فریمورک‌ها

## نصب سریع

```bash
composer require amirdev-byte/phlask
```

نیازمندی‌های سیستم:

- PHP 7.4 یا بالاتر
- PDO PHP Extension
- OpenSSL PHP Extension
- JSON PHP Extension
- Mbstring PHP Extension

## مثال سریع

```php
<?php
require_once 'vendor/autoload.php';

use PHLask\App;
use PHLask\Http\Request;
use PHLask\Http\Response;

// ایجاد نمونه از برنامه
$app = App::getInstance();

// تعریف مسیر GET ساده
$app->get('/', function(Request $request, Response $response) {
    return $response->json([
        'message' => 'سلام دنیا!',
        'version' => '1.0.0'
    ]);
});

// تعریف مسیر GET با پارامتر
$app->get('/users/{id}', function(Request $request, Response $response) {
    $userId = $request->param('id');
    
    // در اینجا می‌توانید اطلاعات کاربر را از دیتابیس دریافت کنید
    
    return $response->json([
        'id' => $userId,
        'name' => 'کاربر شماره ' . $userId,
        'email' => 'user' . $userId . '@example.com'
    ]);
});

// اجرای برنامه
$app->run();
```

## مستندات بیشتر

برای اطلاعات بیشتر و راهنمای کامل، لطفاً به [مستندات کامل](docs/fa/README.md) مراجعه کنید.

## مثال‌های کاربردی

فریمورک فلسک‌پی‌اچ‌پی شامل چندین مثال کاربردی است که می‌توانید آن‌ها را در پوشه [examples](examples) پیدا کنید:

- `helloworld.php`: یک مثال ساده از API
- `simple-api.php`: یک API کامل‌تر با احراز هویت و CORS
- `database-example.php`: مثال استفاده از پایگاه داده
- `middleware-example.php`: مثال استفاده از میان‌افزارها
- `easy-db-example.php`: استفاده از کتابخانه ساده دیتابیس

## مشارکت

مشارکت شما در این پروژه بسیار ارزشمند است. برای اطلاعات بیشتر در مورد نحوه مشارکت،
لطفاً [راهنمای مشارکت](CONTRIBUTING.md) را مطالعه کنید.

## مجوز

این پروژه تحت مجوز MIT منتشر شده است. برای اطلاعات بیشتر، به فایل [LICENSE](LICENSE) مراجعه کنید.

---

<p align="center">
  ساخته شده با ❤️ توسط تیم PHLask
</p>
