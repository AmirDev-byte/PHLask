# میان‌افزارها (Middleware)

میان‌افزارها یکی از قدرتمندترین ویژگی‌های فلسک‌پی‌اچ‌پی هستند. آن‌ها به شما امکان می‌دهند منطق پردازش درخواست را به صورت
لایه‌ای و به ترتیب خاصی اجرا کنید. میان‌افزارها قبل و بعد از پردازش اصلی درخواست اجرا می‌شوند و می‌توانند جریان درخواست
را کنترل کنند.

## مفهوم میان‌افزار

میان‌افزار یک لایه میانی بین درخواست HTTP ورودی و پاسخ نهایی است. هر میان‌افزار می‌تواند:

1. درخواست ورودی را بررسی و اصلاح کند
2. عملیات خاصی را قبل یا بعد از پردازش اصلی انجام دهد
3. تصمیم بگیرد آیا درخواست به میان‌افزار بعدی یا handler اصلی برسد یا نه
4. پاسخ را قبل از ارسال به کاربر بررسی و اصلاح کند

## چرا از میان‌افزار استفاده کنیم؟

میان‌افزارها مزایای زیادی دارند:

- **جداسازی دغدغه‌ها (Separation of Concerns)**: منطق برنامه را به بخش‌های مستقل تقسیم می‌کنند
- **کد قابل استفاده مجدد**: از تکرار کد جلوگیری می‌کنند
- **کنترل جریان**: می‌توانند تصمیم بگیرند درخواست به کجا برود
- **اجرای مرتب**: به ترتیب مشخصی اجرا می‌شوند

مثال‌های رایج استفاده از میان‌افزار:

- احراز هویت و کنترل دسترسی
- ثبت لاگ و ردیابی درخواست‌ها
- کنترل CORS
- کش کردن پاسخ‌ها
- فشرده‌سازی پاسخ‌ها
- مدیریت نشست‌ها
- اعتبارسنجی داده‌ها

## تعریف میان‌افزار در فلسک‌پی‌اچ‌پی

در فلسک‌پی‌اچ‌پی، میان‌افزارها می‌توانند به دو صورت تعریف شوند:

1. **تابع Closure**: تابعی که دو پارامتر دریافت می‌کند: درخواست و تابع بعدی
2. **کلاس PSR-15**: کلاسی که باید اینترفیس `Psr\Http\Server\MiddlewareInterface` را پیاده‌سازی کند

### میان‌افزار به صورت تابع Closure

```php
$app->middleware(function(Request $request, callable $next) {
    // کد قبل از پردازش درخواست
    
    // فراخوانی میان‌افزار بعدی یا handler اصلی
    $response = $next($request);
    
    // کد بعد از پردازش درخواست
    
    return $response;
});
```

### میان‌افزار به صورت کلاس PSR-15

```php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LogMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // کد قبل از پردازش درخواست
        $startTime = microtime(true);
        
        // فراخوانی میان‌افزار بعدی یا handler اصلی
        $response = $handler->handle($request);
        
        // کد بعد از پردازش درخواست
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // به میلی‌ثانیه
        
        // افزودن زمان اجرا به هدر پاسخ
        return $response->withHeader('X-Execution-Time', round($executionTime, 2).'ms');
    }
}

// افزودن میان‌افزار به برنامه
$app->middleware(new LogMiddleware());
```

## ترتیب اجرای میان‌افزارها

میان‌افزارها به صورت FIFO (First In, First Out) اجرا می‌شوند. یعنی میان‌افزاری که اول اضافه می‌شود، اول اجرا می‌شود.
میان‌افزارها به صورت پیاز لایه‌ای عمل می‌کنند:

```
           ┌────────────────────────┐
           │       Middleware 1     │
           │   ┌──────────────┐    │
           │   │ Middleware 2 │    │
           │   │   ┌──────┐   │    │
Request ──►│──►│──►│ App  │───┼───►│──► Response
           │   │   │      │   │    │
           │   │   └──────┘   │    │
           │   └──────────────┘    │
           └────────────────────────┘
```

هر میان‌افزار می‌تواند:

1. درخواست را به میان‌افزار بعدی یا handler اصلی بفرستد
2. درخواست را اصلاح کند و سپس به میان‌افزار بعدی بفرستد
3. اجرای زنجیره را متوقف کند و خودش پاسخ را برگرداند

## نمونه‌های کاربردی میان‌افزار

### میان‌افزار احراز هویت

این میان‌افزار بررسی می‌کند که آیا کاربر احراز هویت شده است یا خیر:

```php
$app->middleware(function(Request $request, callable $next) {
    // مسیرهایی که نیاز به احراز هویت ندارند
    $publicPaths = ['/', '/login', '/register'];
    
    $path = $request->getUri()->getPath();
    
    // اگر مسیر عمومی است، بدون بررسی ادامه می‌دهیم
    if (in_array($path, $publicPaths)) {
        return $next($request);
    }
    
    // بررسی توکن احراز هویت
    $token = $request->getHeaderLine('Authorization');
    
    if (empty($token) || !preg_match('/^Bearer\s+(.+)$/', $token, $matches)) {
        return new Response(401, [], json_encode([
            'error' => 'احراز هویت الزامی است',
            'message' => 'لطفاً توکن معتبر ارائه دهید'
        ]));
    }
    
    $tokenValue = $matches[1];
    
    // در یک برنامه واقعی، اینجا توکن را بررسی می‌کنید
    // مثلاً با JWT یا بررسی در پایگاه داده
    
    // برای مثال، فرض می‌کنیم که توکن معتبر است و اطلاعات کاربر را دریافت می‌کنیم
    $user = [
        'id' => 1,
        'name' => 'کاربر تست',
        'email' => 'test@example.com'
    ];
    
    // افزودن اطلاعات کاربر به درخواست
    $request = $request->withAttribute('user', $user);
    
    // ادامه پردازش درخواست
    return $next($request);
});
```

### میان‌افزار CORS

برای مدیریت Cross-Origin Resource Sharing (CORS) و اجازه دادن به دامنه‌های مختلف برای دسترسی به API شما:

```php
use PHLask\Middleware\CorsMiddleware;

// استفاده از کلاس آماده
$app->middleware(new CorsMiddleware([
    'allowedOrigins' => ['https://example.com', 'https://app.example.com'],
    'allowedMethods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    'allowedHeaders' => ['Content-Type', 'Authorization', 'X-Requested-With'],
    'exposedHeaders' => [],
    'maxAge' => 86400, // یک روز
    'allowCredentials' => true,
]));

// یا پیاده‌سازی دستی
$app->middleware(function(Request $request, callable $next) {
    // برای درخواست‌های preflight (OPTIONS)
    if ($request->getMethod() === 'OPTIONS') {
        $response = new Response(204);
        
        $response = $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
            ->withHeader('Access-Control-Max-Age', '86400');
            
        return $response;
    }
    
    // برای سایر درخواست‌ها
    $response = $next($request);
    
    return $response->withHeader('Access-Control-Allow-Origin', '*');
});
```

### میان‌افزار لاگینگ

برای ثبت جزئیات درخواست‌ها و زمان اجرا:

```php
$app->middleware(function(Request $request, callable $next) {
    $startTime = microtime(true);
    
    $method = $request->getMethod();
    $path = $request->getUri()->getPath();
    
    // ثبت اطلاعات درخواست ورودی
    error_log("[{$method}] {$path} - شروع پردازش");
    
    // اجرای میان‌افزار بعدی یا handler اصلی
    $response = $next($request);
    
    $endTime = microtime(true);
    $executionTime = ($endTime - $startTime) * 1000; // به میلی‌ثانیه
    
    // ثبت اطلاعات پاسخ و زمان اجرا
    $statusCode = $response->getStatusCode();
    error_log("[{$method}] {$path} - پایان پردازش - کد وضعیت: {$statusCode} - زمان اجرا: {$executionTime}ms");
    
    return $response;
});
```

### میان‌افزار اعتبارسنجی داده‌ها

برای بررسی و اعتبارسنجی داده‌های ورودی:

```php
$app->middleware(function(Request $request, callable $next) {
    // فقط برای درخواست‌های POST و PUT
    $method = $request->getMethod();
    if ($method !== 'POST' && $method !== 'PUT') {
        return $next($request);
    }
    
    // دریافت داده‌های ورودی
    $data = $request->getParsedBody();
    
    // قوانین اعتبارسنجی (مثال ساده)
    $rules = [
        'name' => ['required', 'string', 'min:3'],
        'email' => ['required', 'email'],
        'age' => ['integer', 'min:18'],
    ];
    
    // اجرای اعتبارسنجی
    $errors = $this->validate($data, $rules);
    
    if (!empty($errors)) {
        return new Response(400, [], json_encode([
            'error' => 'داده‌های نامعتبر',
            'messages' => $errors
        ]));
    }
    
    // ادامه پردازش درخواست
    return $next($request);
});

// متد کمکی برای اعتبارسنجی (پیاده‌سازی ساده)
function validate(array $data, array $rules): array
{
    $errors = [];
    
    foreach ($rules as $field => $fieldRules) {
        foreach ($fieldRules as $rule) {
            if ($rule === 'required' && (!isset($data[$field]) || $data[$field] === '')) {
                $errors[$field][] = "فیلد {$field} الزامی است";
                break; // به قوانین بعدی نمی‌رویم
            }
            
            if (!isset($data[$field]) || $data[$field] === '') {
                continue; // اگر فیلد خالی است و الزامی نیست، سایر قوانین را بررسی نمی‌کنیم
            }
            
            if ($rule === 'string' && !is_string($data[$field])) {
                $errors[$field][] = "فیلد {$field} باید رشته باشد";
            }
            
            if ($rule === 'integer' && !is_numeric($data[$field])) {
                $errors[$field][] = "فیلد {$field} باید عدد باشد";
            }
            
            if ($rule === 'email' && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                $errors[$field][] = "فیلد {$field} باید یک ایمیل معتبر باشد";
            }
            
            if (strpos($rule, 'min:') === 0) {
                $min = (int) substr($rule, 4);
                
                if (is_string($data[$field]) && mb_strlen($data[$field]) < $min) {
                    $errors[$field][] = "فیلد {$field} باید حداقل {$min} کاراکتر باشد";
                } elseif (is_numeric($data[$field]) && $data[$field] < $min) {
                    $errors[$field][] = "فیلد {$field} باید حداقل {$min} باشد";
                }
            }
        }
    }
    
    return $errors;
}
```

### میان‌افزار کش کردن پاسخ

برای افزایش کارایی با کش کردن پاسخ‌های API:

```php
$app->middleware(function(Request $request, callable $next) {
    // فقط برای درخواست‌های GET
    if ($request->getMethod() !== 'GET') {
        return $next($request);
    }
    
    $cacheKey = 'cache_' . md5($request->getUri()->getPath() . '?' . $request->getUri()->getQuery());
    $cacheTTL = 3600; // مدت زمان کش (به ثانیه)
    
    // بررسی وجود پاسخ در کش
    $cachedResponse = $this->getCachedResponse($cacheKey);
    
    if ($cachedResponse !== null) {
        // افزودن هدر برای نشان دادن اینکه پاسخ از کش آمده است
        return $cachedResponse->withHeader('X-Cache', 'HIT');
    }
    
    // اجرای درخواست
    $response = $next($request);
    
    // ذخیره پاسخ در کش (فقط برای پاسخ‌های موفق)
    if ($response->getStatusCode() === 200) {
        $this->cacheResponse($cacheKey, $response, $cacheTTL);
    }
    
    return $response->withHeader('X-Cache', 'MISS');
});

// متدهای کمکی برای کش کردن (پیاده‌سازی ساده)
function getCachedResponse(string $key)
{
    $cacheFile = sys_get_temp_dir() . '/' . $key;
    
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $this->cacheTTL) {
        $data = unserialize(file_get_contents($cacheFile));
        return $data;
    }
    
    return null;
}

function cacheResponse(string $key, ResponseInterface $response, int $ttl): void
{
    $cacheFile = sys_get_temp_dir() . '/' . $key;
    file_put_contents($cacheFile, serialize($response));
}
```

## پیاده‌سازی میان‌افزارهای استاندارد PSR-15

در فلسک‌پی‌اچ‌پی می‌توانید از میان‌افزارهای استاندارد PSR-15 استفاده کنید. برای این کار، کلاس میان‌افزار باید اینترفیس
`Psr\Http\Server\MiddlewareInterface` را پیاده‌سازی کند:

```php
namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use PHLask\Http\Response;

class AuthMiddleware implements MiddlewareInterface
{
    /**
     * @var array لیست مسیرهایی که نیاز به احراز هویت ندارند
     */
    private array $publicPaths;
    
    /**
     * سازنده کلاس AuthMiddleware
     *
     * @param array $publicPaths مسیرهای عمومی
     */
    public function __construct(array $publicPaths = [])
    {
        $this->publicPaths = $publicPaths;
    }
    
    /**
     * پردازش درخواست
     *
     * @param ServerRequestInterface $request درخواست
     * @param RequestHandlerInterface $handler پردازنده درخواست
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        
        // اگر مسیر عمومی است، بدون بررسی ادامه می‌دهیم
        if (in_array($path, $this->publicPaths)) {
            return $handler->handle($request);
        }
        
        // بررسی توکن احراز هویت
        $token = $request->getHeaderLine('Authorization');
        
        if (empty($token) || !preg_match('/^Bearer\s+(.+)$/', $token, $matches)) {
            return new Response(401, [], json_encode([
                'error' => 'احراز هویت الزامی است',
                'message' => 'لطفاً توکن معتبر ارائه دهید'
            ]));
        }
        
        $tokenValue = $matches[1];
        
        // بررسی اعتبار توکن (پیاده‌سازی واقعی باید اینجا انجام شود)
        
        // افزودن اطلاعات کاربر به درخواست
        $user = ['id' => 1, 'name' => 'کاربر تست'];
        $request = $request->withAttribute('user', $user);
        
        // ادامه پردازش درخواست
        return $handler->handle($request);
    }
}

// استفاده از میان‌افزار
$app->middleware(new \App\Middleware\AuthMiddleware([
    '/', '/login', '/register'
]));
```

## میان‌افزارهای آماده

فلسک‌پی‌اچ‌پی چندین میان‌افزار آماده ارائه می‌دهد که می‌توانید مستقیماً از آن‌ها استفاده کنید:

### CorsMiddleware

برای مدیریت CORS (Cross-Origin Resource Sharing):

```php
use PHLask\Middleware\CorsMiddleware;

$app->middleware(new CorsMiddleware([
    'allowedOrigins' => ['*'],
    'allowedMethods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    'allowedHeaders' => ['Content-Type', 'Authorization', 'X-Requested-With'],
    'exposedHeaders' => [],
    'maxAge' => 86400,
    'allowCredentials' => true,
]));
```

### JsonBodyParserMiddleware

برای پردازش خودکار بدنه درخواست‌های JSON:

```php
use PLHask\Middleware\JsonBodyParserMiddleware;

$app->middleware(new JsonBodyParserMiddleware());
```

### ContentTypeMiddleware

برای تنظیم خودکار نوع محتوا در پاسخ:

```php
use PLHask\Middleware\ContentTypeMiddleware;

$app->middleware(new ContentTypeMiddleware('application/json'));
```

### RateLimitMiddleware

برای محدود کردن تعداد درخواست‌ها:

```php
use PLHask\Middleware\RateLimitMiddleware;

$app->middleware(new RateLimitMiddleware([
    'limit' => 100, // تعداد درخواست‌های مجاز
    'period' => 3600, // دوره زمانی (به ثانیه)
    'identifier' => function (Request $request) {
        // تعیین شناسه کاربر (معمولاً IP یا شناسه کاربر)
        return $request->getServerParams()['REMOTE_ADDR'] ?? '0.0.0.0';
    }
]));
```

## توصیه‌ها و بهترین روش‌ها

### 1. ترتیب میان‌افزارها مهم است

ترتیب اضافه کردن میان‌افزارها بسیار مهم است. به عنوان مثال، میان‌افزار CORS باید قبل از میان‌افزار احراز هویت اضافه شود
تا درخواست‌های preflight (OPTIONS) بتوانند بدون احراز هویت پردازش شوند.

### 2. کار را در میان‌افزار مناسب انجام دهید

هر میان‌افزار باید یک کار مشخص را انجام دهد. از میان‌افزارهای بزرگ و پیچیده که چندین کار را انجام می‌دهند، خودداری کنید.

### 3. زنجیره میان‌افزارها را قطع نکنید (مگر عمداً)

اگر می‌خواهید که زنجیره میان‌افزارها ادامه یابد، حتماً باید تابع `$next($request)` را فراخوانی کنید. اگر این کار را
نکنید، زنجیره میان‌افزارها قطع می‌شود و handler اصلی هرگز اجرا نمی‌شود.

### 4. خطاها را در میان‌افزار مدیریت کنید

میان‌افزارها می‌توانند برای مدیریت خطاها نیز استفاده شوند. به عنوان مثال، می‌توانید یک میان‌افزار برای گرفتن استثناها
ایجاد کنید:

```php
$app->middleware(function(Request $request, callable $next) {
    try {
        return $next($request);
    } catch (\Exception $e) {
        // ثبت خطا
        error_log('خطا: ' . $e->getMessage());
        
        // ارسال پاسخ خطا
        return new Response(500, [], json_encode([
            'error' => 'خطای سرور',
            'message' => $e->getMessage()
        ]));
    }
});
```

### 5. درخواست و پاسخ را تغییر دهید، نه تنظیمات کلی را

میان‌افزارها باید فقط درخواست و پاسخ را تغییر دهند، نه تنظیمات کلی برنامه را. این به معماری تمیزتر و قابل آزمایش‌تر منجر
می‌شود.

## عیب‌یابی میان‌افزارها

### مشکل: میان‌افزار اجرا نمی‌شود

اگر میان‌افزار شما اجرا نمی‌شود، موارد زیر را بررسی کنید:

1. **ترتیب افزودن**: آیا میان‌افزار قبل از فراخوانی `$app->run()` اضافه شده است؟
2. **شرایط داخلی**: آیا شرایط داخلی میان‌افزار باعث می‌شود که کد اصلی اجرا نشود؟
3. **خطاهای قبلی**: آیا میان‌افزارهای قبلی زنجیره را قطع کرده‌اند؟

### مشکل: میان‌افزار باعث خطا می‌شود

اگر میان‌افزار شما باعث خطا می‌شود، موارد زیر را بررسی کنید:

1. **متغیرهای نامعتبر**: آیا تمام متغیرهایی که استفاده می‌کنید، معتبر هستند؟
2. **فراخوانی $next**: آیا تابع `$next($request)` را درست فراخوانی کرده‌اید؟
3. **تعریف میان‌افزار**: آیا میان‌افزار درست تعریف شده است؟

### مشکل: زنجیره میان‌افزارها قطع می‌شود

اگر زنجیره میان‌افزارها زودتر از انتظار قطع می‌شود، موارد زیر را بررسی کنید:

1. **بازگشت زودهنگام**: آیا جایی در میان‌افزار بدون فراخوانی `$next($request)` مقداری را برمی‌گردانید؟
2. **شرط‌های اشتباه**: آیا شرط‌های میان‌افزار درست تعریف شده‌اند؟

## نمونه‌های پیشرفته‌تر

### میان‌افزار برای مدیریت نشست‌ها

```php
$app->middleware(function(Request $request, callable $next) {
    // شروع نشست
    session_start();
    
    // افزودن اطلاعات نشست به درخواست
    $request = $request->withAttribute('session', $_SESSION);
    
    // ادامه زنجیره
    $response = $next($request);
    
    // ذخیره نشست
    session_write_close();
    
    return $response;
});
```

### میان‌افزار برای تغییر خودکار زبان براساس هدر Accept-Language

```php
$app->middleware(function(Request $request, callable $next) {
    $acceptLanguage = $request->getHeaderLine('Accept-Language');
    $defaultLanguage = 'en';
    
    // پردازش هدر Accept-Language
    $languages = [];
    foreach (explode(',', $acceptLanguage) as $item) {
        $parts = explode(';q=', $item);
        $lang = trim($parts[0]);
        $quality = isset($parts[1]) ? (float) $parts[1] : 1.0;
        $languages[$lang] = $quality;
    }
    
    // مرتب‌سازی براساس کیفیت
    arsort($languages);
    
    // زبان‌های پشتیبانی شده
    $supportedLanguages = ['en', 'fa', 'ar', 'fr'];
    
    // انتخاب بهترین زبان
    $selectedLanguage = $defaultLanguage;
    foreach ($languages as $lang => $quality) {
        $lang = substr($lang, 0, 2); // فقط کد اصلی زبان
        if (in_array($lang, $supportedLanguages)) {
            $selectedLanguage = $lang;
            break;
        }
    }
    
    // افزودن زبان انتخاب شده به درخواست
    $request = $request->withAttribute('language', $selectedLanguage);
    
    return $next($request);
});
```

### میان‌افزار برای اعمال فشرده‌سازی Gzip

```php
$app->middleware(function(Request $request, callable $next) {
    // ابتدا درخواست را پردازش می‌کنیم
    $response = $next($request);
    
    // بررسی پشتیبانی مرورگر از gzip
    $acceptEncoding = $request->getHeaderLine('Accept-Encoding');
    if (strpos($acceptEncoding, 'gzip') === false) {
        return $response; // مرورگر gzip را پشتیبانی نمی‌کند
    }
    
    // بررسی نوع محتوا
    $contentType = $response->getHeaderLine('Content-Type');
    $compressibleTypes = [
        'text/html',
        'text/css',
        'text/plain',
        'application/javascript',
        'application/json',
        'application/xml',
    ];
    
    $canCompress = false;
    foreach ($compressibleTypes as $type) {
        if (strpos($contentType, $type) !== false) {
            $canCompress = true;
            break;
        }
    }
    
    if (!$canCompress) {
        return $response; // نوع محتوا قابل فشرده‌سازی نیست
    }
    
    // دریافت بدنه پاسخ
    $body = (string) $response->getBody();
    
    // اگر بدنه خیلی کوچک است، فشرده‌سازی نمی‌کنیم
    if (strlen($body) < 1024) {
        return $response;
    }
    
    // فشرده‌سازی بدنه
    $compressed = gzencode($body, 9);
    
    // ایجاد بدنه جدید
    $stream = new \PLHask\Http\Stream(fopen('php://temp', 'r+'));
    $stream->write($compressed);
    
    // ایجاد پاسخ جدید با بدنه فشرده‌شده و هدرهای مناسب
    return $response
        ->withBody($stream)
        ->withHeader('Content-Encoding', 'gzip')
        ->withHeader('Content-Length', strlen($compressed));
});
```

## گام بعدی

اکنون که با میان‌افزارها در فلسک‌پی‌اچ‌پی آشنا شدید، می‌توانید:

- [درخواست و پاسخ](request-response.md) را مطالعه کنید تا با جزئیات بیشتری از کلاس‌های Request و Response آشنا شوید.
- [مدیریت خطا](error-handling.md) را بررسی کنید تا با روش‌های مدیریت خطا در برنامه خود آشنا شوید.
- [اتصال به پایگاه داده](../database/connection.md) را مطالعه کنید تا با نحوه کار با پایگاه داده آشنا شوید.