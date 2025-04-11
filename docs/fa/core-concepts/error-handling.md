### خطاهای پایگاه داده

این خطاها هنگام تعامل با پایگاه داده رخ می‌دهند:

- خطاهای اتصال به پایگاه داده
- خطاهای کوئری
- خطاهای تراکنش
- خطاهای محدودیت (Constraint Violations)

### خطاهای اپلیکیشن

این خطاها مرتبط با منطق داخلی اپلیکیشن شما هستند:

- خطاهای اعتبارسنجی
- خطاهای منطق کسب‌وکار
- خطاهای دسترسی به منابع

### خطاهای سیستمی

این خطاها در سطح سیستم رخ می‌دهند:

- خطاهای فایل سیستم
- خطاهای حافظه
- خطاهای سرویس‌های خارجی

## کلاس HttpException

فلسک‌پی‌اچ‌پی کلاس `HttpException` را برای مدیریت خطاهای HTTP ارائه می‌دهد. این کلاس یک نمونه از `Exception` است که
اطلاعات اضافی مانند کد وضعیت HTTP و جزئیات خطا را به همراه دارد.

### ایجاد یک استثنای HTTP

```php
use PLHask\Exceptions\HttpException;

// ایجاد استثنا به صورت مستقیم
throw new HttpException(404, 'صفحه مورد نظر یافت نشد');

// ایجاد استثنا با جزئیات اضافی
throw new HttpException(
    400, 
    'پارامترهای نامعتبر', 
    ['field' => 'email', 'error' => 'فرمت ایمیل نامعتبر است']
);
```

### متدهای کمکی

کلاس `HttpException` متدهای کمکی برای ایجاد انواع رایج خطاهای HTTP دارد:

```php
// خطای 400 Bad Request
throw HttpException::badRequest('درخواست نامعتبر است');

// خطای 401 Unauthorized
throw HttpException::unauthorized('احراز هویت مورد نیاز است');

// خطای 403 Forbidden
throw HttpException::forbidden('شما دسترسی لازم را ندارید');

// خطای 404 Not Found
throw HttpException::notFound('صفحه مورد نظر یافت نشد');

// خطای 405 Method Not Allowed
throw HttpException::methodNotAllowed('این متد HTTP پشتیبانی نمی‌شود');

// خطای 422 Unprocessable Entity
throw HttpException::unprocessableEntity('داده‌های ارسالی نامعتبر هستند');

// خطای 429 Too Many Requests
throw HttpException::tooManyRequests('تعداد درخواست‌ها بیش از حد مجاز است');

// خطای 500 Internal Server Error
throw HttpException::internalServerError('خطای داخلی سرور');
```

## کلاس DatabaseException

برای خطاهای مربوط به پایگاه داده، فلسک‌پی‌اچ‌پی کلاس `DatabaseException` را ارائه می‌دهد:

```php
use PLHask\Exceptions\DatabaseException;

// ایجاد استثنای خطای اتصال
throw DatabaseException::connectionError(
    'خطا در اتصال به پایگاه داده', 
    1234, 
    ['host' => 'localhost', 'database' => 'mydb']
);

// ایجاد استثنای خطای کوئری
throw DatabaseException::queryError(
    'خطا در اجرای کوئری', 
    'SELECT * FROM non_existent_table', 
    1234
);
```

## مدیریت خطاها در مسیریاب (Router)

### تعریف مدیریت‌کننده‌های خطا

در فلسک‌پی‌اچ‌پی می‌توانید برای هر کد وضعیت HTTP، یک مدیریت‌کننده خطا تعریف کنید:

```php
use PLHask\App;
use PLHask\Http\Request;
use PLHask\Http\Response;

$app = App::getInstance();

// مدیریت خطای 404 (Not Found)
$app->errorHandler(404, function($error, Request $request, Response $response) {
    return $response->status(404)->json([
        'error' => 'Not Found',
        'message' => $error ? $error->getMessage() : 'صفحه مورد نظر یافت نشد',
        'path' => $request->getUri()->getPath()
    ]);
});

// مدیریت خطای 500 (Internal Server Error)
$app->errorHandler(500, function($error, Request $request, Response $response) {
    // ثبت خطا در لاگ
    error_log($error->getMessage() . "\n" . $error->getTraceAsString());
    
    // در محیط تولید، جزئیات خطا را به کاربر نشان ندهید
    $message = getenv('APP_ENV') === 'production' 
        ? 'خطای داخلی سرور رخ داده است. لطفا بعدا دوباره تلاش کنید.' 
        : $error->getMessage();
    
    return $response->status(500)->json([
        'error' => 'Internal Server Error',
        'message' => $message
    ]);
});

// مدیریت خطای 401 (Unauthorized)
$app->errorHandler(401, function($error, Request $request, Response $response) {
    return $response->status(401)->json([
        'error' => 'Unauthorized',
        'message' => 'لطفا وارد حساب کاربری خود شوید'
    ]);
});

// مدیریت خطای 403 (Forbidden)
$app->errorHandler(403, function($error, Request $request, Response $response) {
    return $response->status(403)->json([
        'error' => 'Forbidden',
        'message' => 'شما دسترسی لازم برای انجام این عملیات را ندارید'
    ]);
});
```

### مدیریت خطاهای اعتبارسنجی (422 Unprocessable Entity)

```php
$app->errorHandler(422, function($error, Request $request, Response $response) {
    $details = [];
    
    if ($error instanceof HttpException) {
        $details = $error->getDetails() ?? [];
    }
    
    return $response->status(422)->json([
        'error' => 'Validation Error',
        'message' => $error->getMessage(),
        'errors' => $details
    ]);
});
```

## پرتاب و مدیریت استثناها در مسیرها

حال می‌توانید در مسیرهای خود، استثناها را پرتاب کنید و فلسک‌پی‌اچ‌پی آنها را به مدیریت‌کننده‌های خطای مناسب هدایت
می‌کند:

```php
$app->get('/users/{id}', function(Request $request, Response $response) {
    $id = $request->param('id');
    
    // بررسی معتبر بودن شناسه
    if (!is_numeric($id) || $id <= 0) {
        throw HttpException::badRequest('شناسه کاربر باید یک عدد مثبت باشد');
    }
    
    // جستجوی کاربر در پایگاه داده
    $user = UserRepository::find($id);
    
    // اگر کاربر یافت نشد
    if (!$user) {
        throw HttpException::notFound('کاربر مورد نظر یافت نشد');
    }
    
    // بررسی دسترسی کاربر جاری
    $currentUser = $request->getAttribute('user');
    if ($user['id'] !== $currentUser['id'] && $currentUser['role'] !== 'admin') {
        throw HttpException::forbidden('شما دسترسی به اطلاعات این کاربر را ندارید');
    }
    
    // بازگرداندن اطلاعات کاربر
    return $response->json($user);
});
```

## میان‌افزار Try-Catch

برای مدیریت جامع استثناها، می‌توانید یک میان‌افزار Try-Catch ایجاد کنید:

```php
$app->middleware(function(Request $request, callable $next) {
    try {
        // اجرای زنجیره میان‌افزارها و هندلر
        return $next($request);
    } catch (HttpException $e) {
        // مدیریت خطاهای HTTP
        $response = new Response($e->getStatusCode());
        return $response->json([
            'error' => $response->getReasonPhrase(),
            'message' => $e->getMessage(),
            'details' => $e->getDetails()
        ]);
    } catch (DatabaseException $e) {
        // مدیریت خطاهای پایگاه داده
        error_log('Database Error: ' . $e->getMessage() . ' - Query: ' . $e->getQuery());
        
        $response = new Response(500);
        return $response->json([
            'error' => 'Database Error',
            'message' => 'خطا در ارتباط با پایگاه داده'
        ]);
    } catch (\Exception $e) {
        // مدیریت سایر استثناها
        error_log('Uncaught Exception: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
        
        $response = new Response(500);
        return $response->json([
            'error' => 'Internal Server Error',
            'message' => 'خطای داخلی سرور'
        ]);
    }
});
```

## اعتبارسنجی داده‌ها و گزارش خطا

یک کاربرد مهم مدیریت خطا، اعتبارسنجی داده‌های ورودی است:

```php
$app->post('/users', function(Request $request, Response $response) {
    $data = $request->all();
    
    // اعتبارسنجی داده‌ها
    $validator = new Validator($data);
    $validator->required(['name', 'email', 'password'])
              ->email('email')
              ->minLength('password', 8)
              ->maxLength('name', 100);
    
    // اگر اعتبارسنجی شکست خورد
    if ($validator->fails()) {
        throw new HttpException(
            422, 
            'داده‌های ورودی نامعتبر هستند', 
            $validator->errors()
        );
    }
    
    // ذخیره کاربر جدید
    try {
        $userId = UserRepository::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT)
        ]);
        
        return $response->status(201)->json([
            'message' => 'کاربر با موفقیت ایجاد شد',
            'id' => $userId
        ]);
    } catch (DatabaseException $e) {
        // بررسی خطای یکتا بودن ایمیل
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            throw new HttpException(
                422, 
                'ایمیل وارد شده قبلا ثبت شده است', 
                ['email' => ['این ایمیل قبلا در سیستم ثبت شده است']]
            );
        }
        
        // سایر خطاهای پایگاه داده
        throw $e;
    }
});
```

## لاگ کردن خطاها

ثبت خطاها (logging) یک بخش مهم از مدیریت خطا است. فلسک‌پی‌اچ‌پی با PSR-3 سازگار است و می‌توانید از هر پیاده‌سازی از
`Psr\Log\LoggerInterface` استفاده کنید:

```php
use Psr\Log\LoggerInterface;

class ErrorHandler
{
    private LoggerInterface $logger;
    
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    
    public function handleException(\Throwable $e): void
    {
        // تعیین سطح خطا براساس نوع استثنا
        $level = 'error';
        
        if ($e instanceof HttpException) {
            $statusCode = $e->getStatusCode();
            
            // خطاهای 4xx معمولا هشدار هستند
            if ($statusCode >= 400 && $statusCode < 500) {
                $level = 'warning';
            } 
            // خطاهای 5xx بحرانی هستند
            else if ($statusCode >= 500) {
                $level = 'critical';
            }
        } else if ($e instanceof DatabaseException) {
            $level = 'critical';
        }
        
        // ساخت پیام خطا
        $message = sprintf(
            "%s: %s in %s:%d\nStack trace: %s",
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
        
        // ثبت خطا با سطح مناسب
        $this->logger->log($level, $message, [
            'exception' => $e,
            'request_id' => uniqid(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    }
}

// استفاده در میان‌افزار مدیریت خطا
$app->middleware(function(Request $request, callable $next) use ($errorHandler) {
    try {
        return $next($request);
    } catch (\Throwable $e) {
        // ثبت خطا
        $errorHandler->handleException($e);
        
        // مدیریت پاسخ به کاربر
        // ...
    }
});
```

## نمایش خطاها در محیط‌های مختلف

یک اصل مهم در مدیریت خطا، تفاوت بین محیط‌های توسعه و تولید است:

```php
$app->errorHandler(500, function($error, Request $request, Response $response) {
    // تشخیص محیط
    $isProduction = getenv('APP_ENV') === 'production';
    
    // در محیط توسعه، جزئیات کامل خطا را نمایش دهید
    if (!$isProduction) {
        return $response->status(500)->json([
            'error' => 'Internal Server Error',
            'message' => $error->getMessage(),
            'file' => $error->getFile(),
            'line' => $error->getLine(),
            'trace' => explode("\n", $error->getTraceAsString())
        ]);
    }
    
    // در محیط تولید، پیام خطای عمومی نمایش دهید
    return $response->status(500)->json([
        'error' => 'Internal Server Error',
        'message' => 'متأسفانه خطایی در سیستم رخ داده است. لطفا بعدا دوباره تلاش کنید.'
    ]);
});
```

## خطاهای 404 و صفحات سفارشی

برای مسیرهایی که یافت نمی‌شوند، می‌توانید صفحات سفارشی 404 نمایش دهید:

```php
$app->errorHandler(404, function($error, Request $request, Response $response) {
    // بررسی نوع درخواست (API یا وب)
    $wantsJson = $request->getHeaderLine('Accept') === 'application/json' || 
                 $request->isAjax();
    
    if ($wantsJson) {
        // برای درخواست‌های API، پاسخ JSON ارسال کنید
        return $response->status(404)->json([
            'error' => 'Not Found',
            'message' => 'منبع درخواستی یافت نشد',
            'path' => $request->getUri()->getPath()
        ]);
    } else {
        // برای درخواست‌های وب، صفحه HTML نمایش دهید
        $html = <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <title>صفحه یافت نشد</title>
            <style>
                body { font-family: sans-serif; text-align: center; margin-top: 50px; }
                h1 { font-size: 36px; }
                p { font-size: 18px; }
                .back { margin-top: 20px; }
            </style>
        </head>
        <body>
            <h1>404 - صفحه یافت نشد</h1>
            <p>متأسفانه صفحه مورد نظر شما یافت نشد.</p>
            <div class="back">
                <a href="/">بازگشت به صفحه اصلی</a>
            </div>
        </body>
        </html>
        HTML;
        
        return $response->status(404)->html($html);
    }
});
```

## نمونه‌های کاربردی

### مثال 1: مدیریت خطاهای احراز هویت

```php
class AuthMiddleware
{
    public function __invoke(Request $request, callable $next)
    {
        // دریافت توکن از هدر Authorization
        $token = $request->getHeaderLine('Authorization');
        
        if (empty($token)) {
            throw HttpException::unauthorized('توکن احراز هویت الزامی است');
        }
        
        // حذف پیشوند "Bearer "
        if (strpos($token, 'Bearer ') === 0) {
            $token = substr($token, 7);
        }
        
        try {
            // بررسی اعتبار توکن (مثلا با JWT)
            $userData = $this->validateToken($token);
            
            // افزودن اطلاعات کاربر به درخواست
            $request = $request->withAttribute('user', $userData);
            
            return $next($request);
        } catch (TokenExpiredException $e) {
            throw HttpException::unauthorized('توکن منقضی شده است. لطفا دوباره وارد شوید.');
        } catch (InvalidTokenException $e) {
            throw HttpException::unauthorized('توکن نامعتبر است');
        }
    }
    
    private function validateToken(string $token)
    {
        // پیاده‌سازی بررسی اعتبار توکن
        // ...
    }
}

// استفاده
$app->middleware(new AuthMiddleware());
```

### مثال 2: مدیریت خطاهای اعتبارسنجی فرم

```php
class ContactFormController
{
    public function submitForm(Request $request, Response $response)
    {
        $data = $request->all();
        $errors = [];
        
        // اعتبارسنجی نام
        if (empty($data['name'])) {
            $errors['name'] = 'نام الزامی است';
        } elseif (strlen($data['name']) < 3) {
            $errors['name'] = 'نام باید حداقل 3 کاراکتر باشد';
        }
        
        // اعتبارسنجی ایمیل
        if (empty($data['email'])) {
            $errors['email'] = 'ایمیل الزامی است';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'ایمیل نامعتبر است';
        }
        
        // اعتبارسنجی پیام
        if (empty($data['message'])) {
            $errors['message'] = 'پیام الزامی است';
        } elseif (strlen($data['message']) < 10) {
            $errors['message'] = 'پیام باید حداقل 10 کاراکتر باشد';
        }
        
        // اگر خطایی وجود دارد
        if (!empty($errors)) {
            throw new HttpException(
                422,
                'لطفا خطاهای فرم را برطرف کنید',
                $errors
            );
        }
        
        // ارسال ایمیل یا ذخیره پیام
        try {
            // ...
            
            return $response->json([
                'success' => true,
                'message' => 'پیام شما با موفقیت ارسال شد'
            ]);
        } catch (\Exception $e) {
            throw HttpException::internalServerError('خطا در ارسال پیام: ' . $e->getMessage());
        }
    }
}
```

### مثال 3: مدیریت خطاهای API خارجی

```php
class PaymentService
{
    public function processPayment(array $paymentData)
    {
        try {
            // ارسال درخواست به درگاه پرداخت
            $response = $this->sendApiRequest('https://payment-gateway.com/api/v1/payments', $paymentData);
            
            // بررسی پاسخ
            if ($response['status'] === 'success') {
                return [
                    'transaction_id' => $response['transaction_id'],
                    'amount' => $paymentData['amount'],
                    'status' => 'success'
                ];
            } else {
                throw new PaymentException(
                    'پرداخت ناموفق بود: ' . ($response['message'] ?? 'خطای نامشخص'),
                    $response['error_code'] ?? 0
                );
            }
        } catch (ConnectException $e) {
            // خطای اتصال به API
            throw new PaymentException('خطا در اتصال به درگاه پرداخت. لطفا بعدا دوباره تلاش کنید.', 0, $e);
        } catch (TimeoutException $e) {
            // خطای زمان انتظار
            throw new PaymentException('زمان پاسخگویی درگاه پرداخت به پایان رسید. لطفا بعدا دوباره تلاش کنید.', 0, $e);
        } catch (\Exception $e) {
            // سایر خطاها
            throw new PaymentException('خطا در پردازش پرداخت: ' . $e->getMessage(), 0, $e);
        }
    }
}

// استفاده در مسیر
$app->post('/checkout', function(Request $request, Response $response) {
    // ...
    
    try {
        $result = $paymentService->processPayment($paymentData);
        return $response->json($result);
    } catch (PaymentException $e) {
        // خطای پرداخت
        throw new HttpException(400, $e->getMessage());
    }
});
```

## توصیه‌ها و بهترین روش‌ها

### 1. خطاها را در سطح مناسب مدیریت کنید

خطاها را تا حد امکان در پایین‌ترین سطح ممکن که توانایی پاسخگویی مناسب را دارد، مدیریت کنید.

```php
// نادرست: پنهان کردن همه خطاها
try {
    // کد پیچیده با چندین عملیات مختلف
} catch (\Exception $e) {
    // مدیریت عمومی همه خطاها
}

// درست: مدیریت خطاهای خاص در مکان‌های مناسب
try {
    // عملیات اول
} catch (SpecificException $e) {
    // مدیریت خطای خاص
}

try {
    // عملیات دوم
} catch (AnotherException $e) {
    // مدیریت خطای دیگر
}
```

### 2. پیام‌های خطای مناسب ارائه دهید

برای کاربران نهایی، پیام‌های خطای مفید و قابل فهم ارائه دهید، اما اطلاعات حساس را فاش نکنید:

```php
// نادرست: افشای اطلاعات حساس
if ($e instanceof PDOException) {
    return $response->status(500)->json([
        'error' => $e->getMessage(), // ممکن است اطلاعات حساس دیتابیس را فاش کند
        'sql' => $e->getQuery() // افشای ساختار دیتابیس
    ]);
}

// درست: پیام مناسب بدون افشای اطلاعات حساس
if ($e instanceof PDOException) {
    // ثبت خطا برای توسعه‌دهندگان
    error_log($e->getMessage() . ': ' . $e->getQuery());
    
    // پیام عمومی برای کاربر
    return $response->status(500)->json([
        'error' => 'خطا در ارتباط با پایگاه داده',
        'message' => 'متأسفانه در حال حاضر قادر به پردازش درخواست شما نیستیم. لطفا بعدا دوباره تلاش کنید.'
    ]);
}
```

### 3. کدهای وضعیت HTTP مناسب استفاده کنید

از کدهای وضعیت HTTP مناسب برای نوع خطا استفاده کنید:

- 4xx برای خطاهای کاربر
- 5xx برای خطاهای سرور

```php
// خطای کاربر: داده‌های نامعتبر
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    throw new HttpException(400, 'ایمیل نامعتبر است');
}

// خطای کاربر: منبع یافت نشد
if (!$user) {
    throw new HttpException(404, 'کاربر یافت نشد');
}

// خطای سرور: خطای پایگاه داده
try {
    $result = $db->query($sql);
} catch (PDOException $e) {
    throw new HttpException(500, 'خطا در دیتابیس');
}
```

### 4. از تکنیک Try-Catch-Finally استفاده کنید

از بلوک finally برای انجام عملیات پاکسازی بدون توجه به بروز خطا استفاده کنید:

```php
$fileHandle = null;

try {
    $fileHandle = fopen('data.txt', 'r');
    $content = fread($fileHandle, filesize('data.txt'));
    // پردازش محتوا
} catch (Exception $e) {
    // مدیریت خطا
    error_log('خطا در خواندن فایل: ' . $e->getMessage());
    throw new HttpException(500, 'خطا در پردازش فایل');
} finally {
    // همیشه فایل را ببندید، حتی اگر خطا رخ دهد
    if ($fileHandle) {
        fclose($fileHandle);
    }
}
```

### 5. خطاها را به درستی ثبت (لاگ) کنید

جزئیات خطاها را برای اهداف عیب‌یابی ثبت کنید:

```php
try {
    // کد مشکوک
} catch (Exception $e) {
    // ثبت خطا با اطلاعات کامل
    error_log(sprintf(
        "Exception: %s in %s:%d\nStack trace: %s",
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    ));
    
    // پرتاب مجدد یا برگرداندن پاسخ خطا
    throw new HttpException(500, 'خطای داخلی سرور');
}
```

## عیب‌یابی مشکلات رایج

### مشکل 1: خطاها مدیریت نمی‌شوند

اگر خطاهای شما به درستی مدیریت نمی‌شوند، موارد زیر را بررسی کنید:

1. **میان‌افزار Try-Catch**: اطمینان حاصل کنید که میان‌افزار Try-Catch در ابتدای زنجیره میان‌افزارها قرار دارد.
2. **عدم تعریف مدیریت‌کننده خطا**: بررسی کنید که برای کد وضعیت مورد نظر، یک مدیریت‌کننده خطا تعریف کرده‌اید.
3. **ترتیب میان‌افزارها**: ترتیب اضافه‌کردن میان‌افزارها را بررسی کنید.

### مشکل 2: اطلاعات حساس در خطاها افشا می‌شود

اگر اطلاعات حساس در پیام‌های خطا نمایش داده می‌شود:

1. **بررسی محیط**: مطمئن شوید که در محیط تولید، پیام‌های خطای عمومی نمایش داده می‌شوند.
2. **پیام‌های سفارشی**: از پیام‌های خطای سفارشی به جای پیام‌های پیش‌فرض استفاده کنید.
3. **فیلتر کردن خطاها**: قبل از ارسال خطا به کاربر، اطلاعات حساس را فیلتر کنید.

### مشکل 3: خطاهای ثبت نشده

اگر خطاها به درستی ثبت (لاگ) نمی‌شوند:

1. **دسترسی فایل لاگ**: بررسی کنید که برنامه دسترسی نوشتن در فایل لاگ را داشته باشد.
2. **مسیر فایل لاگ**: مسیر فایل لاگ را بررسی کنید.
3. **تنظیمات لاگ PHP**: تنظیمات `error_log` در فایل `php.ini` را بررسی کنید.

## یکپارچه‌سازی با سیستم‌های مدیریت خطای خارجی

می‌توانید فلسک‌پی‌اچ‌پی را با سیستم‌های مدیریت و نظارت خطای خارجی مانند Sentry، Bugsnag یا New Relic یکپارچه کنید:

```php
// Sentry
\Sentry\init(['dsn' => 'your_sentry_dsn']);

// میان‌افزار مدیریت خطا با Sentry
$app->middleware(function(Request $request, callable $next) {
    try {
        return $next($request);
    } catch (\Throwable $e) {
        // ارسال خطا به Sentry
        \Sentry\captureException($e);
        
        // مدیریت پاسخ به کاربر
        if ($e instanceof HttpException) {
            $response = new Response($e->getStatusCode());
            return $response->json([
                'error' => $response->getReasonPhrase(),
                'message' => $e->getMessage()
            ]);
        }
        
        // خطاهای سیستمی
        $response = new Response(500);
        return $response->json([
            'error' => 'Internal Server Error',
            'message' => 'خطای داخلی سرور'
        ]);
    }
});
```

## مدیریت خطا در تست‌ها

هنگام نوشتن تست‌ها، نیاز دارید رفتار مدیریت خطاهای برنامه خود را بررسی کنید:

```php
public function testNotFoundError()
{
    // ایجاد یک درخواست به مسیر نامعتبر
    $request = new Request('GET', new Uri('/non-existent-page'));
    
    // اجرای برنامه و دریافت پاسخ
    $response = $this->app->handleRequest($request);
    
    // بررسی کد وضعیت
    $this->assertEquals(404, $response->getStatusCode());
    
    // بررسی محتوای پاسخ
    $data = json_decode((string) $response->getBody(), true);
    $this->assertEquals('Not Found', $data['error']);
}

public function testValidationError()
{
    // ایجاد یک درخواست با داده‌های نامعتبر
    $request = new Request('POST', new Uri('/users'));
    $request = $request->withParsedBody(['name' => '']); // بدون ایمیل
    
    // اجرای برنامه و دریافت پاسخ
    $response = $this->app->handleRequest($request);
    
    // بررسی کد وضعیت
    $this->assertEquals(422, $response->getStatusCode());
    
    // بررسی پیام‌های خطا
    $data = json_decode((string) $response->getBody(), true);
    $this->assertArrayHasKey('errors', $data);
    $this->assertArrayHasKey('email', $data['errors']);
}
```

## خلاصه

مدیریت خطا یک بخش مهم از هر برنامه وب است که به شما کمک می‌کند:

1. **تشخیص مشکلات**: خطاها و استثناها را به موقع تشخیص دهید
2. **پاسخ مناسب**: به کاربران پاسخ‌های مناسب و قابل فهم ارائه دهید
3. **عیب‌یابی**: اطلاعات لازم برای عیب‌یابی و رفع مشکلات را ثبت کنید
4. **امنیت**: از افشای اطلاعات حساس جلوگیری کنید
5. **تجربه کاربری**: تجربه کاربری بهتری با مدیریت صحیح خطاها ارائه دهید

با استفاده از ابزارهای ارائه شده توسط فلسک‌پی‌اچ‌پی مانند `HttpException`، `DatabaseException` و مدیریت‌کننده‌های خطا،
می‌توانید سیستم مدیریت خطای قوی و انعطاف‌پذیری برای برنامه خود ایجاد کنید.

## گام بعدی

پس از آشنایی با مدیریت خطا، برای یادگیری بیشتر می‌توانید به بخش‌های زیر مراجعه کنید:

- [میان‌افزارها](middleware.md) - استفاده از میان‌افزارها برای پردازش درخواست‌ها
- [اعتبارسنجی داده‌ها](../guides/validation.md) - نحوه اعتبارسنجی داده‌های ورودی
- [امنیت](../advanced/security.md) - بهترین روش‌های امنیتی در فلسک‌پی‌اچ‌پی# مدیریت خطا (Error Handling)

مدیریت صحیح خطاها یک بخش مهم از هر اپلیکیشن وب است. فلسک‌پی‌اچ‌پی ابزارهای مختلفی را برای مدیریت و گزارش خطاها ارائه
می‌دهد تا شما بتوانید برنامه‌ای ایمن و با قابلیت اطمینان بالا ایجاد کنید.

## مقدمه

مدیریت خطا در فلسک‌پی‌اچ‌پی چندین هدف مهم را دنبال می‌کند:

1. **تشخیص خطاها**: تشخیص وضعیت‌های غیرعادی و غیرمنتظره
2. **گزارش خطاها**: ثبت اطلاعات خطا برای توسعه‌دهندگان (log)
3. **پاسخ به خطاها**: نمایش پیام‌های مناسب به کاربران
4. **بازیابی از خطاها**: ادامه عملکرد برنامه در صورت امکان

## انواع خطا

در فلسک‌پی‌اچ‌پی، خطاها به چند دسته اصلی تقسیم می‌شوند:

### خطاهای HTTP

این خطاها مربوط به درخواست‌های HTTP هستند و با کدهای وضعیت HTTP مرتبط هستند:

- `400 Bad Request`: درخواست نامعتبر
- `401 Unauthorized`: احراز هویت مورد نیاز است
- `403 Forbidden`: دسترسی ممنوع است
- `404 Not Found`: منبع درخواستی یافت نشد
- `405 Method Not Allowed`: متد HTTP مجاز نیست
- `422 Unprocessable Entity`: داده‌های نامعتبر
- `429 Too Many Requests`: تعداد درخواست‌ها بیش از حد مجاز است
- `500 Internal Server Error`: خطای داخلی سرور
- و غیره

### خطاهای پایگاه داده

این خطاها هنگام تعامل با پایگاه داده رخ می‌دهند