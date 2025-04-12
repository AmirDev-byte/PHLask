# برنامه (App)

کلاس `App` هسته اصلی فریمورک فلسک‌پی‌اچ‌پی است که مسئولیت مدیریت کلی برنامه، مسیریابی، اجرای میان‌افزارها و پردازش
درخواست‌ها و پاسخ‌ها را بر عهده دارد. تقریباً تمام عملیات‌ها در فلسک‌پی‌اچ‌پی از طریق این کلاس انجام می‌شود.

## ایجاد یک نمونه از برنامه

```php
use PHLask\App;

// روش 1: ایجاد نمونه جدید
$app = new App();

// روش 2: استفاده از الگوی Singleton (پیشنهادی)
$app = App::getInstance();
```

در بیشتر موارد، استفاده از روش دوم (الگوی Singleton) توصیه می‌شود، زیرا اطمینان می‌دهد که در سراسر برنامه فقط یک نمونه
از `App` وجود دارد.

## تعریف مسیرها

کلاس `App` متدهایی برای تعریف مسیرها ارائه می‌دهد که هر کدام با یک متد HTTP مرتبط هستند:

```php
$app->get('/', function(Request $request, Response $response) {
    return $response->text('سلام دنیا!');
});

$app->post('/users', function(Request $request, Response $response) {
    // ایجاد کاربر جدید
    return $response->json(['message' => 'کاربر ایجاد شد']);
});

$app->put('/users/{id}', function(Request $request, Response $response) {
    $id = $request->param('id');
    // به‌روزرسانی کاربر
    return $response->json(['message' => "کاربر {$id} به‌روزرسانی شد"]);
});

$app->delete('/users/{id}', function(Request $request, Response $response) {
    $id = $request->param('id');
    // حذف کاربر
    return $response->json(['message' => "کاربر {$id} حذف شد"]);
});

$app->patch('/users/{id}', function(Request $request, Response $response) {
    // به‌روزرسانی جزئی کاربر
});

$app->options('/users', function(Request $request, Response $response) {
    // پاسخ به درخواست OPTIONS
});
```

## استفاده از میان‌افزارها

میان‌افزارها امکان پردازش درخواست قبل و بعد از اجرای handler اصلی را فراهم می‌کنند:

```php
// افزودن یک میان‌افزار ساده
$app->middleware(function(Request $request, callable $next) {
    // قبل از اجرای handler
    $startTime = microtime(true);
    
    // اجرای زنجیره میان‌افزارها و handler
    $response = $next($request);
    
    // بعد از اجرای handler
    $endTime = microtime(true);
    $executionTime = ($endTime - $startTime) * 1000; // به میلی‌ثانیه
    
    // افزودن هدر اختصاصی به پاسخ
    return $response->withHeader('X-Execution-Time', "{$executionTime}ms");
});

// افزودن میان‌افزار پیاده‌سازی شده با کلاس PSR-15
$app->middleware(new CorsMiddleware());
```

برای اطلاعات بیشتر در مورد میان‌افزارها، به بخش [میان‌افزارها](middleware.md) مراجعه کنید.

## مدیریت خطاها

کلاس `App` امکان تعریف مدیریت‌کننده‌های خطا برای کدهای وضعیت HTTP مختلف را فراهم می‌کند:

```php
// مدیریت خطای 404 (Not Found)
$app->errorHandler(404, function($error, Request $request, Response $response) {
    return $response->status(404)->json([
        'error' => 'صفحه مورد نظر یافت نشد',
        'path' => $request->getUri()->getPath()
    ]);
});

// مدیریت خطای 500 (Internal Server Error)
$app->errorHandler(500, function($error, Request $request, Response $response) {
    // ثبت خطا در لاگ (در محیط واقعی)
    error_log($error->getMessage());
    
    return $response->status(500)->json([
        'error' => 'خطای داخلی سرور',
        'message' => 'متأسفانه مشکلی در پردازش درخواست شما به وجود آمده است'
    ]);
});
```

برای اطلاعات بیشتر در مورد مدیریت خطا، به بخش [مدیریت خطا](error-handling.md) مراجعه کنید.

## اتصال به پایگاه داده

می‌توانید اتصال به پایگاه داده را با استفاده از متد `enableDatabase` فعال کنید:

```php
// فعال‌سازی اتصال به پایگاه داده
$app->enableDatabase([
    'driver' => 'mysql',
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'my_database',
    'username' => 'root',
    'password' => 'your_password',
    'charset' => 'utf8mb4'
]);

// استفاده از پایگاه داده
$users = $app->db()->table('users')->get();
```

برای اطلاعات بیشتر در مورد کار با پایگاه داده، به بخش [اتصال به پایگاه داده](../database/connection.md) مراجعه کنید.

## اجرای برنامه

پس از تعریف مسیرها و میان‌افزارها، باید متد `run` را فراخوانی کنید تا برنامه شروع به پردازش درخواست ورودی کند:

```php
// اجرای برنامه
$app->run();
```

## کنترل دستی درخواست و پاسخ

در برخی موارد، ممکن است بخواهید درخواست را به صورت دستی پردازش کنید (مثلاً در تست‌ها):

```php
// ایجاد یک درخواست دستی
$request = new Request('GET', new Uri('/api/users'));

// پردازش درخواست و دریافت پاسخ
$response = $app->handleRequest($request);

// بررسی پاسخ
echo $response->getStatusCode(); // 200
echo $response->getBody(); // محتوای پاسخ
```

## گام بعدی

اکنون که با کلاس `App` آشنا شدید، می‌توانید به بخش‌های دیگر مستندات مراجعه کنید:

- [مسیریابی](routing.md) - آشنایی بیشتر با سیستم مسیریابی
- [میان‌افزارها](middleware.md) - کار با میان‌افزارها
- [درخواست و پاسخ](request-response.md) - کار با درخواست‌ها و پاسخ‌ها
- [مدیریت خطا](error-handling.md) - مدیریت خطاها و استثناها
- [اتصال به پایگاه داده](../database/connection.md) - کار با پایگاه داده