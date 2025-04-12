# درخواست و پاسخ (Request & Response)

مدیریت درخواست‌ها و پاسخ‌ها یکی از مهم‌ترین بخش‌های هر فریمورک وب است. فلسک‌پی‌اچ‌پی با پیاده‌سازی استاندارد PSR-7، یک
رابط ساده و قدرتمند برای کار با درخواست‌ها و پاسخ‌ها ارائه می‌دهد.

## مفاهیم اصلی

### درخواست (Request)

کلاس `Request` در فلسک‌پی‌اچ‌پی نمایانگر یک درخواست HTTP است. این کلاس شامل تمام اطلاعات مربوط به یک درخواست ورودی مانند
متد HTTP، مسیر، هدرها، پارامترهای کوئری، داده‌های فرم و غیره است.

### پاسخ (Response)

کلاس `Response` نمایانگر یک پاسخ HTTP است. این کلاس شامل اطلاعاتی مانند کد وضعیت، هدرها و بدنه پاسخ است که به مرورگر یا
کلاینت ارسال می‌شود.

## کار با درخواست (Request)

### دریافت نمونه درخواست

در فلسک‌پی‌اچ‌پی، نمونه کلاس `Request` به صورت خودکار ایجاد می‌شود و به تابع handler مسیر ارسال می‌شود:

```php
$app->get('/hello', function(Request $request, Response $response) {
    // استفاده از شیء $request
    // ...
    
    return $response->text('سلام دنیا!');
});
```

برای ایجاد نمونه درخواست از متغیرهای سراسری، می‌توانید از متد `fromGlobals` استفاده کنید:

```php
use PHLask\Http\Request;

$request = Request::fromGlobals();
```

### دسترسی به اطلاعات درخواست

#### دریافت متد HTTP

```php
$method = $request->getMethod(); // GET, POST, PUT, DELETE, ...
```

#### دریافت URI

```php
$uri = $request->getUri(); // شیء UriInterface

// دسترسی به بخش‌های مختلف URI
$path = $uri->getPath(); // مثلاً "/users/123"
$query = $uri->getQuery(); // مثلاً "page=1&sort=name"
$host = $uri->getHost(); // مثلاً "example.com"
$scheme = $uri->getScheme(); // "http" یا "https"
```

#### دریافت هدرها

```php
// دریافت تمام هدرها
$headers = $request->getHeaders();

// بررسی وجود یک هدر خاص
if ($request->hasHeader('Content-Type')) {
    // ...
}

// دریافت یک هدر به صورت آرایه
$contentType = $request->getHeader('Content-Type'); // ["application/json"]

// دریافت یک هدر به صورت رشته
$contentType = $request->getHeaderLine('Content-Type'); // "application/json"
```

#### دریافت پارامترهای مسیر

پارامترهای مسیر از الگوی URI استخراج می‌شوند (مثلاً `/users/{id}`):

```php
$app->get('/users/{id}', function(Request $request, Response $response) {
    $userId = $request->param('id');
    
    // می‌توانید یک مقدار پیش‌فرض هم تعیین کنید
    $page = $request->param('page', 1);
    
    // دریافت تمام پارامترهای مسیر
    $params = $request->getParams();
});
```

#### دریافت پارامترهای کوئری (Query Parameters)

پارامترهای کوئری از بخش `?key1=value1&key2=value2` در URL استخراج می‌شوند:

```php
// دریافت یک پارامتر کوئری
$page = $request->query('page', 1); // مقدار پیش‌فرض: 1
$sort = $request->query('sort', 'id');

// دریافت تمام پارامترهای کوئری
$queryParams = $request->getQueryParams();
```

#### دریافت داده‌های ارسالی (Form Data, Request Body)

برای دریافت داده‌های ارسالی در بدنه درخواست (مثلاً فرم‌ها یا JSON):

```php
// دریافت تمام داده‌های ارسالی
$data = $request->all();

// دریافت یک فیلد خاص
$name = $request->input('name');
$email = $request->input('email', 'default@example.com'); // با مقدار پیش‌فرض

// دریافت داده‌های خام
$rawBody = (string) $request->getBody();

// اگر بدنه درخواست JSON است
if ($request->isJson()) {
    $jsonData = $request->getParsedBody();
}
```

#### دریافت فایل‌های آپلود شده

```php
// دریافت تمام فایل‌های آپلود شده
$files = $request->getUploadedFiles();

// دریافت یک فایل خاص
$file = $request->getUploadedFiles()['profile_image'] ?? null;

if ($file) {
    // دریافت اطلاعات فایل
    $name = $file->getClientFilename();
    $size = $file->getSize();
    $type = $file->getClientMediaType();
    $error = $file->getError();
    
    // ذخیره فایل
    $file->moveTo('/path/to/save/' . $name);
}
```

#### دریافت کوکی‌ها

```php
// دریافت تمام کوکی‌ها
$cookies = $request->getCookieParams();

// دریافت یک کوکی خاص
$sessionId = $cookies['session_id'] ?? null;
```

#### دریافت IP کاربر

```php
$ip = $request->getServerParams()['REMOTE_ADDR'] ?? '0.0.0.0';

// یا اگر از proxy استفاده می‌کنید
$ip = $request->getHeaderLine('X-Forwarded-For') ?: ($request->getServerParams()['REMOTE_ADDR'] ?? '0.0.0.0');
```

#### بررسی نوع درخواست

```php
// بررسی درخواست Ajax
if ($request->isAjax()) {
    // ...
}

// بررسی درخواست JSON
if ($request->isJson()) {
    // ...
}

// بررسی متد درخواست
if ($request->getMethod() === 'POST') {
    // ...
}
```

#### ویژگی‌های اضافی (Attributes)

می‌توانید داده‌های دلخواه را به درخواست اضافه کنید (معمولاً در میان‌افزارها استفاده می‌شود):

```php
// تنظیم یک ویژگی
$request = $request->withAttribute('user', [
    'id' => 123,
    'name' => 'John Doe'
]);

// دریافت یک ویژگی
$user = $request->getAttribute('user');
$userId = $user['id'] ?? null;

// دریافت یک ویژگی با مقدار پیش‌فرض
$theme = $request->getAttribute('theme', 'light');

// دریافت تمام ویژگی‌ها
$attributes = $request->getAttributes();
```

## کار با پاسخ (Response)

### ایجاد پاسخ

در فلسک‌پی‌اچ‌پی، نمونه کلاس `Response` به صورت خودکار ایجاد می‌شود و به تابع handler مسیر ارسال می‌شود:

```php
$app->get('/hello', function(Request $request, Response $response) {
    // استفاده از شیء $response
    return $response->text('سلام دنیا!');
});
```

می‌توانید نمونه پاسخ را به صورت دستی نیز ایجاد کنید:

```php
use PHLask\Http\Response;

$response = new Response(200, [
    'Content-Type' => 'text/html; charset=utf-8'
], '<h1>سلام دنیا!</h1>');
```

### انواع پاسخ

#### پاسخ JSON

برای ارسال پاسخ‌های JSON:

```php
return $response->json([
    'status' => 'success',
    'data' => [
        'id' => 123,
        'name' => 'محصول نمونه'
    ]
]);
```

این معادل کد زیر است:

```php
$body = json_encode([
    'status' => 'success',
    'data' => [
        'id' => 123,
        'name' => 'محصول نمونه'
    ]
]);

return $response
    ->withHeader('Content-Type', 'application/json; charset=utf-8')
    ->withBody(new Stream(fopen('php://temp', 'r+')))
    ->getBody()->write($body);
```

#### پاسخ متنی

برای ارسال پاسخ‌های متنی ساده:

```php
return $response->text('سلام دنیا!');
```

#### پاسخ HTML

برای ارسال پاسخ‌های HTML:

```php
return $response->html('<h1>سلام دنیا!</h1>');
```

#### هدایت (Redirect)

برای هدایت کاربر به آدرس دیگر:

```php
// هدایت با کد وضعیت 302 (پیش‌فرض)
return $response->redirect('/dashboard');

// هدایت با کد وضعیت 301 (دائمی)
return $response->redirect('/new-page', 301);
```

### تنظیم کد وضعیت

```php
// تنظیم کد وضعیت
return $response->status(201)->json([
    'message' => 'منبع با موفقیت ایجاد شد'
]);

// یا به صورت زنجیره‌ای با withStatus
return $response->withStatus(201)
    ->withHeader('Content-Type', 'application/json')
    ->withBody(...);
```

### تنظیم هدرها

```php
// تنظیم یک هدر
return $response->withHeader('X-Custom-Header', 'مقدار دلخواه')
    ->json($data);

// تنظیم چندین هدر
return $response
    ->withHeader('X-Custom-Header', 'مقدار دلخواه')
    ->withHeader('Cache-Control', 'no-cache')
    ->json($data);

// افزودن مقدار به یک هدر موجود
return $response->withAddedHeader('Set-Cookie', 'name=value; Path=/');
```

### تنظیم بدنه پاسخ

```php
// تنظیم بدنه پاسخ با متد withBody
$stream = new Stream(fopen('php://temp', 'r+'));
$stream->write('سلام دنیا!');

return $response
    ->withHeader('Content-Type', 'text/plain; charset=utf-8')
    ->withBody($stream);

// یا به صورت ساده‌تر با روش‌های کمکی
return $response->text('سلام دنیا!');
```

### ارسال فایل

برای ارسال فایل به کاربر:

```php
$filePath = '/path/to/file.pdf';
$fileName = 'document.pdf';

// ایجاد استریم از فایل
$stream = new Stream(fopen($filePath, 'r'));

// تنظیم هدرهای مناسب
return $response
    ->withHeader('Content-Type', 'application/pdf')
    ->withHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"')
    ->withHeader('Content-Length', filesize($filePath))
    ->withBody($stream);

// در نسخه‌های آینده، یک متد کمکی نیز اضافه خواهد شد
// return $response->download($filePath, $fileName);
```

### تنظیم کوکی

برای تنظیم کوکی‌ها:

```php
// تنظیم یک کوکی ساده
return $response
    ->withHeader('Set-Cookie', 'name=value; Path=/; HttpOnly')
    ->text('کوکی تنظیم شد');

// تنظیم چندین کوکی
return $response
    ->withAddedHeader('Set-Cookie', 'name=value; Path=/; HttpOnly')
    ->withAddedHeader('Set-Cookie', 'theme=dark; Path=/; Max-Age=86400')
    ->text('کوکی‌ها تنظیم شدند');

// در نسخه‌های آینده، یک متد کمکی نیز اضافه خواهد شد
// return $response->withCookie('name', 'value', [
//     'path' => '/',
//     'httpOnly' => true,
//     'maxAge' => 86400
// ]);
```

### زنجیره‌کردن متدها

یکی از ویژگی‌های مهم کلاس‌های Request و Response در فلسک‌پی‌اچ‌پی، پشتیبانی از زنجیره‌کردن متدها است. این به شما امکان
می‌دهد چندین عملیات را در یک خط انجام دهید:

```php
return $response
    ->status(200)
    ->withHeader('X-Custom-Header', 'مقدار دلخواه')
    ->withHeader('Cache-Control', 'no-cache')
    ->json([
        'status' => 'success',
        'data' => $result
    ]);
```

## PSR-7 و Immutability

کلاس‌های Request و Response در فلسک‌پی‌اچ‌پی از استاندارد PSR-7 پیروی می‌کنند. یکی از ویژگی‌های مهم PSR-7،
immutability (تغییرناپذیری) است. این یعنی متدهای `with*` نمونه اصلی را تغییر نمی‌دهند، بلکه یک نمونه جدید با تغییرات
درخواستی برمی‌گردانند:

```php
// این کد کار نمی‌کند
$response->withHeader('Content-Type', 'application/json');
return $response->withBody($body);

// این کد درست است
$response = $response->withHeader('Content-Type', 'application/json');
return $response->withBody($body);

// یا به صورت زنجیره‌ای
return $response
    ->withHeader('Content-Type', 'application/json')
    ->withBody($body);
```

## استفاده از UriInterface

کلاس `Uri` پیاده‌سازی اینترفیس `UriInterface` در PSR-7 است و برای کار با URI استفاده می‌شود:

```php
use PHLask\Http\Uri;

// ایجاد یک URI از رشته
$uri = new Uri('https://example.com/users?page=1');

// دسترسی به بخش‌های مختلف URI
$scheme = $uri->getScheme(); // "https"
$host = $uri->getHost(); // "example.com"
$path = $uri->getPath(); // "/users"
$query = $uri->getQuery(); // "page=1"

// تغییر بخش‌های URI
$newUri = $uri
    ->withScheme('http')
    ->withPath('/products')
    ->withQuery('category=electronics');

// تبدیل به رشته
$uriString = (string) $newUri; // "http://example.com/products?category=electronics"
```

## استفاده از StreamInterface

کلاس `Stream` پیاده‌سازی اینترفیس `StreamInterface` در PSR-7 است و برای کار با داده‌های استریمی استفاده می‌شود:

```php
use PHLask\Http\Stream;

// ایجاد یک استریم خالی
$stream = new Stream(fopen('php://temp', 'r+'));

// نوشتن در استریم
$stream->write('سلام دنیا!');

// رفتن به ابتدای استریم
$stream->rewind();

// خواندن از استریم
$content = $stream->getContents(); // "سلام دنیا!"

// بررسی وضعیت استریم
$size = $stream->getSize(); // 13 (تعداد بایت‌ها)
$isReadable = $stream->isReadable(); // true
$isWritable = $stream->isWritable(); // true
$isSeekable = $stream->isSeekable(); // true

// بستن استریم
$stream->close();
```

## نمونه‌های کاربردی

### پردازش فرم ارسالی

```php
$app->post('/register', function(Request $request, Response $response) {
    // دریافت داده‌های فرم
    $name = $request->input('name');
    $email = $request->input('email');
    $password = $request->input('password');
    
    // اعتبارسنجی داده‌ها
    $errors = [];
    
    if (empty($name)) {
        $errors['name'] = 'نام الزامی است';
    }
    
    if (empty($email)) {
        $errors['email'] = 'ایمیل الزامی است';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'ایمیل نامعتبر است';
    }
    
    if (empty($password)) {
        $errors['password'] = 'رمز عبور الزامی است';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'رمز عبور باید حداقل 8 کاراکتر باشد';
    }
    
    // اگر خطایی وجود دارد، پاسخ خطا برگردان
    if (!empty($errors)) {
        return $response->status(400)->json([
            'status' => 'error',
            'errors' => $errors
        ]);
    }
    
    // پردازش ثبت‌نام (در دنیای واقعی، اینجا کاربر را در دیتابیس ذخیره می‌کنید)
    $userId = 123; // فرضی
    
    return $response->status(201)->json([
        'status' => 'success',
        'message' => 'ثبت‌نام با موفقیت انجام شد',
        'user' => [
            'id' => $userId,
            'name' => $name,
            'email' => $email
        ]
    ]);
});
```

### پردازش درخواست JSON

```php
$app->post('/api/products', function(Request $request, Response $response) {
    // بررسی نوع محتوای درخواست
    if (!$request->isJson()) {
        return $response->status(415)->json([
            'error' => 'نوع محتوای نامعتبر',
            'message' => 'درخواست باید با Content-Type: application/json ارسال شود'
        ]);
    }
    
    // دریافت داده‌های JSON
    $data = $request->all();
    
    // اعتبارسنجی داده‌ها
    if (empty($data['name']) || empty($data['price'])) {
        return $response->status(400)->json([
            'error' => 'داده‌های نامعتبر',
            'message' => 'فیلدهای name و price الزامی هستند'
        ]);
    }
    
    // پردازش داده‌ها (در دنیای واقعی، اینجا محصول را در دیتابیس ذخیره می‌کنید)
    $productId = 456; // فرضی
    
    return $response->status(201)->json([
        'status' => 'success',
        'message' => 'محصول با موفقیت ایجاد شد',
        'product' => [
            'id' => $productId,
            'name' => $data['name'],
            'price' => $data['price'],
            'created_at' => date('Y-m-d H:i:s')
        ]
    ]);
});
```

### آپلود فایل

```php
$app->post('/upload', function(Request $request, Response $response) {
    // دریافت فایل آپلود شده
    $files = $request->getUploadedFiles();
    
    if (empty($files['file'])) {
        return $response->status(400)->json([
            'error' => 'فایلی آپلود نشده است'
        ]);
    }
    
    $file = $files['file'];
    
    // بررسی خطای آپلود
    if ($file->getError() !== UPLOAD_ERR_OK) {
        return $response->status(400)->json([
            'error' => 'خطا در آپلود فایل',
            'code' => $file->getError()
        ]);
    }
    
    // دریافت اطلاعات فایل
    $fileName = $file->getClientFilename();
    $fileSize = $file->getSize();
    $fileType = $file->getClientMediaType();
    
    // بررسی نوع فایل (مثلاً فقط تصاویر)
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    
    if (!in_array($fileType, $allowedTypes)) {
        return $response->status(400)->json([
            'error' => 'نوع فایل نامعتبر',
            'message' => 'فقط فایل‌های JPEG، PNG و GIF مجاز هستند'
        ]);
    }
    
    // بررسی اندازه فایل (مثلاً حداکثر 2MB)
    $maxSize = 2 * 1024 * 1024; // 2MB
    
    if ($fileSize > $maxSize) {
        return $response->status(400)->json([
            'error' => 'فایل خیلی بزرگ است',
            'message' => 'حداکثر اندازه مجاز 2MB است'
        ]);
    }
    
    // ایجاد نام فایل یکتا
    $extension = pathinfo($fileName, PATHINFO_EXTENSION);
    $newFileName = uniqid() . '.' . $extension;
    $uploadDir = '/path/to/uploads/';
    $uploadPath = $uploadDir . $newFileName;
    
    // ذخیره فایل
    try {
        $file->moveTo($uploadPath);
    } catch (\Exception $e) {
        return $response->status(500)->json([
            'error' => 'خطا در ذخیره فایل',
            'message' => $e->getMessage()
        ]);
    }
    
    // بازگرداندن پاسخ موفقیت
    return $response->status(201)->json([
        'status' => 'success',
        'message' => 'فایل با موفقیت آپلود شد',
        'file' => [
            'original_name' => $fileName,
            'new_name' => $newFileName,
            'size' => $fileSize,
            'type' => $fileType,
            'url' => '/uploads/' . $newFileName
        ]
    ]);
});
```

### ایجاد صفحه‌بندی (Pagination)

```php
$app->get('/api/products', function(Request $request, Response $response) {
    // پارامترهای صفحه‌بندی
    $page = (int) $request->query('page', 1);
    $perPage = (int) $request->query('per_page', 10);
    
    // محدود کردن اندازه صفحه
    $perPage = min(50, max(1, $perPage));
    $page = max(1, $page);
    
    // در دنیای واقعی، اینجا داده‌ها را از دیتابیس می‌خوانید
    // $products = ... (دریافت از دیتابیس)
    // $total = ... (تعداد کل رکوردها)
    
    // برای مثال
    $total = 100;
    $products = [];
    
    for ($i = 1; $i <= $perPage; $i++) {
        $index = ($page - 1) * $perPage + $i;
        
        if ($index <= $total) {
            $products[] = [
                'id' => $index,
                'name' => 'محصول ' . $index,
                'price' => rand(10000, 1000000),
                'stock' => rand(0, 100)
            ];
        }
    }
    
    // محاسبه اطلاعات صفحه‌بندی
    $totalPages = ceil($total / $perPage);
    $hasNextPage = $page < $totalPages;
    $hasPrevPage = $page > 1;
    
    return $response->json([
        'data' => $products,
        'meta' => [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'has_next_page' => $hasNextPage,
            'has_prev_page' => $hasPrevPage,
            'next_page' => $hasNextPage ? $page + 1 : null,
            'prev_page' => $hasPrevPage ? $page - 1 : null,
        ],
        'links' => [
            'first' => '/api/products?page=1&per_page=' . $perPage,
            'last' => '/api/products?page=' . $totalPages . '&per_page=' . $perPage,
            'next' => $hasNextPage ? '/api/products?page=' . ($page + 1) . '&per_page=' . $perPage : null,
            'prev' => $hasPrevPage ? '/api/products?page=' . ($page - 1) . '&per_page=' . $perPage : null,
        ]
    ]);
});
```

## توصیه‌ها و بهترین روش‌ها

### 1. همیشه نمونه‌های جدید را ذخیره کنید

به دلیل immutability، حتماً نمونه جدید برگشتی از متدهای `with*` را ذخیره کنید:

```php
// درست
$response = $response->withHeader('X-Custom-Header', 'value');

// نادرست
$response->withHeader('X-Custom-Header', 'value'); // این تغییری در $response ایجاد نمی‌کند
```

### 2. از متدهای کمکی استفاده کنید

به جای استفاده مستقیم از متدهای PSR-7، از متدهای کمکی استفاده کنید:

```php
// به جای این
$response = $response
    ->withStatus(200)
    ->withHeader('Content-Type', 'application/json')
    ->withBody(/* ... */);

// از این استفاده کنید
$response = $response->json($data);
```

### 3. درخواست را اصلاح نکنید، بلکه پاسخ مناسب برگردانید

به جای تلاش برای تغییر درخواست، پاسخ مناسب برگردانید:

```php
// نادرست
$request->someMethod(); // تلاش برای تغییر درخواست

// درست
if ($someCondition) {
    return $response->status(400)->json(['error' => 'خطای اعتبارسنجی']);
}
```

### 4. داده‌های ورودی را همیشه اعتبارسنجی کنید

همیشه داده‌های ورودی کاربر را اعتبارسنجی کنید:

```php
$email = $request->input('email');

if (empty($email)) {
    return $response->status(400)->json(['error' => 'ایمیل الزامی است']);
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    return $response->status(400)->json(['error' => 'ایمیل نامعتبر است']);
}

// اکنون می‌توانید از $email استفاده کنید
```

### 5. از میان‌افزارها برای پردازش مشترک استفاده کنید

اگر نیاز به پردازش مشترک در چندین مسیر دارید، از میان‌افزارها استفاده کنید:

```php
$app->middleware(function(Request $request, callable $next) {
    // پردازش مشترک برای همه درخواست‌ها
    
    $response = $next($request);
    
    // پردازش مشترک برای همه پاسخ‌ها
    
    return $response;
});
```

## عیب‌یابی

### مشکل: داده‌های ارسالی دریافت نمی‌شوند

اگر داده‌های ارسالی (مثلاً JSON) را نمی‌توانید دریافت کنید:

1. **بررسی هدر Content-Type**: اطمینان حاصل کنید که هدر `Content-Type` درست تنظیم شده است (مثلاً `application/json` برای
   داده‌های JSON).
2. **بررسی بدنه درخواست**: با استفاده از `(string) $request->getBody()` بدنه خام درخواست را بررسی کنید.
3. **افزودن میان‌افزار پردازش بدنه**: میان‌افزاری برای پردازش خودکار بدنه درخواست اضافه کنید.

```php
$app->middleware(function(Request $request, callable $next) {
    $contentType = $request->getHeaderLine('Content-Type');
    
    if (strpos($contentType, 'application/json') !== false) {
        $body = (string) $request->getBody();
        $data = json_decode($body, true) ?: [];
        $request = $request->withParsedBody($data);
    }
    
    return $next($request);
});
```

### مشکل: هدرها در پاسخ اعمال نمی‌شوند

اگر هدرهای تنظیم شده در پاسخ اعمال نمی‌شوند:

1. **ذخیره نمونه جدید**: اطمینان حاصل کنید که نمونه برگشتی از متدهای `withHeader` را ذخیره می‌کنید.
2. **بازگرداندن پاسخ**: اطمینان حاصل کنید که پاسخ تغییر یافته را از تابع handler برمی‌گردانید.
3. **خروجی قبلی**: بررسی کنید که قبل از ارسال پاسخ، خروجی دیگری ارسال نشده باشد.

### مشکل: فایل‌های آپلودی دریافت نمی‌شوند

اگر نمی‌توانید فایل‌های آپلودی را دریافت کنید:

1. **تنظیم فرم**: بررسی کنید که فرم HTML با `enctype="multipart/form-data"` تنظیم شده باشد.
2. **بررسی تنظیمات PHP**: بررسی کنید که تنظیمات `upload_max_filesize` و `post_max_size` در `php.ini` به اندازه کافی بزرگ
   باشند.
3. **بررسی خطاهای آپلود**: کد خطای فایل را با `$file->getError()` بررسی کنید.

## توابع پرکاربرد Request

| متد                                           | توضیح                                               |
|-----------------------------------------------|-----------------------------------------------------|
| `getMethod()`                                 | متد HTTP درخواست را برمی‌گرداند (GET, POST, ...)    |
| `getUri()`                                    | شیء URI درخواست را برمی‌گرداند                      |
| `getHeaders()`                                | تمام هدرهای درخواست را برمی‌گرداند                  |
| `getHeader(string $name)`                     | یک هدر خاص را به صورت آرایه برمی‌گرداند             |
| `getHeaderLine(string $name)`                 | یک هدر خاص را به صورت رشته برمی‌گرداند              |
| `getBody()`                                   | بدنه درخواست را به صورت StreamInterface برمی‌گرداند |
| `getParsedBody()`                             | بدنه پردازش شده درخواست را برمی‌گرداند              |
| `getQueryParams()`                            | پارامترهای کوئری را برمی‌گرداند                     |
| `query(string $name, $default = null)`        | یک پارامتر کوئری خاص را برمی‌گرداند                 |
| `getCookieParams()`                           | پارامترهای کوکی را برمی‌گرداند                      |
| `getServerParams()`                           | پارامترهای سرور را برمی‌گرداند                      |
| `getUploadedFiles()`                          | فایل‌های آپلود شده را برمی‌گرداند                   |
| `getAttributes()`                             | تمام ویژگی‌های درخواست را برمی‌گرداند               |
| `getAttribute(string $name, $default = null)` | یک ویژگی خاص را برمی‌گرداند                         |
| `withAttribute(string $name, $value)`         | یک ویژگی را تنظیم می‌کند و نمونه جدید برمی‌گرداند   |
| `param(string $name, $default = null)`        | یک پارامتر مسیر را برمی‌گرداند                      |
| `getParams()`                                 | تمام پارامترهای مسیر را برمی‌گرداند                 |
| `input(string $name, $default = null)`        | یک فیلد از بدنه درخواست را برمی‌گرداند              |
| `all()`                                       | تمام داده‌های بدنه درخواست را برمی‌گرداند           |
| `isAjax()`                                    | بررسی می‌کند که آیا درخواست از نوع AJAX است         |
| `isJson()`                                    | بررسی می‌کند که آیا درخواست از نوع JSON است         |

## توابع پرکاربرد Response

| متد                                                | توضیح                                                       |
|----------------------------------------------------|-------------------------------------------------------------|
| `getStatusCode()`                                  | کد وضعیت HTTP را برمی‌گرداند                                |
| `getReasonPhrase()`                                | توضیح وضعیت HTTP را برمی‌گرداند                             |
| `getHeaders()`                                     | تمام هدرهای پاسخ را برمی‌گرداند                             |
| `getHeader(string $name)`                          | یک هدر خاص را به صورت آرایه برمی‌گرداند                     |
| `getHeaderLine(string $name)`                      | یک هدر خاص را به صورت رشته برمی‌گرداند                      |
| `getBody()`                                        | بدنه پاسخ را به صورت StreamInterface برمی‌گرداند            |
| `withStatus(int $code, string $reasonPhrase = '')` | کد وضعیت را تنظیم می‌کند و نمونه جدید برمی‌گرداند           |
| `withHeader(string $name, $value)`                 | یک هدر را تنظیم می‌کند و نمونه جدید برمی‌گرداند             |
| `withAddedHeader(string $name, $value)`            | یک مقدار به هدر موجود اضافه می‌کند و نمونه جدید برمی‌گرداند |
| `withoutHeader(string $name)`                      | یک هدر را حذف می‌کند و نمونه جدید برمی‌گرداند               |
| `withBody(StreamInterface $body)`                  | بدنه پاسخ را تنظیم می‌کند و نمونه جدید برمی‌گرداند          |
| `status(int $code)`                                | کد وضعیت را تنظیم می‌کند و خود شیء را برمی‌گرداند           |
| `json($data)`                                      | داده‌ها را به JSON تبدیل کرده و پاسخ JSON برمی‌گرداند       |
| `text(string $text)`                               | پاسخ متنی برمی‌گرداند                                       |
| `html(string $html)`                               | پاسخ HTML برمی‌گرداند                                       |
| `redirect(string $url, int $status = 302)`         | پاسخ هدایت (redirect) برمی‌گرداند                           |
| `send()`                                           | پاسخ را به کلاینت ارسال می‌کند                              |

## گام بعدی

اکنون که با کلاس‌های Request و Response در فلسک‌پی‌اچ‌پی آشنا شدید، می‌توانید:

- [میان‌افزارها](middleware.md) را مطالعه کنید تا با نحوه پردازش درخواست‌ها قبل و بعد از اجرای handler آشنا شوید.
- [مدیریت خطا](error-handling.md) را بررسی کنید تا با روش‌های مدیریت خطا در برنامه خود آشنا شوید.
- [مسیریابی](routing.md) را دوباره مرور کنید تا با روش‌های پیشرفته‌تر مسیریابی آشنا شوید.
- [اتصال به پایگاه داده](../database/connection.md) را مطالعه کنید تا با نحوه کار با پایگاه داده آشنا شوید.# درخواست و
  پاسخ (Request & Response)

مدیریت درخواست‌ها و پاسخ‌ها یکی از مهم‌ترین بخش‌های هر فریمورک وب است. فلسک‌پی‌اچ‌پی با پیاده‌سازی استاندارد PSR-7، یک
رابط ساده و قدرتمند برای کار با درخواست‌ها و پاسخ‌ها ارائه می‌دهد.

## مفاهیم اصلی

### درخواست (Request)

کلاس `Request` در فلسک‌پی‌اچ‌پی نمایانگر یک درخواست HTTP است. این کلاس شامل تمام اطلاعات مربوط به یک درخواست ورودی مانند
متد HTTP، مسیر، هدرها، پارامترهای کوئری، داده‌های فرم و غیره است.

### پاسخ (Response)

کلاس `Response` نمایانگر یک پاسخ HTTP است. این کلاس شامل اطلاعاتی مانند کد وضعیت، هدرها و بدنه پاسخ است که به مرورگر یا
کلاینت ارسال می‌شود.

## کار با درخواست (Request)

### دریافت نمونه درخواست

در فلسک‌پی‌اچ‌پی، نمونه کلاس `Request` به صورت خودکار ایجاد می‌شود و به تابع handler مسیر ارسال می‌شود:

```php