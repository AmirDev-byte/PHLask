# شروع سریع

در این راهنما، شما یاد می‌گیرید چگونه در مدت زمان کوتاهی یک برنامه ساده با فلسک‌پی‌اچ‌پی ایجاد کنید.

## ایجاد اولین برنامه

فرض کنیم فلسک‌پی‌اچ‌پی را [طبق مستندات نصب](installation.md) روی سیستم خود نصب کرده‌اید. حالا می‌خواهیم یک برنامه ساده ایجاد کنیم.

### گام 1: ایجاد فایل ورودی

ابتدا یک فایل `index.php` در پوشه `public` پروژه خود ایجاد کنید:

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHLask\App;
use PHLask\Http\Request;
use PHLask\Http\Response;

// ایجاد نمونه برنامه
$app = App::getInstance();

// تعریف مسیرها
$app->get('/', function(Request $request, Response $response) {
    return $response->json([
        'message' => 'سلام دنیا!',
        'time' => date('Y-m-d H:i:s')
    ]);
});

// اجرای برنامه
$app->run();
```

### گام 2: اجرای برنامه

برای اجرای برنامه، می‌توانید از سرور داخلی PHP استفاده کنید:

```bash
cd public
php -S localhost:8000
```

حالا در مرورگر خود به آدرس `http://localhost:8000` بروید. باید یک پاسخ JSON مشابه زیر دریافت کنید:

```json
{
  "message": "سلام دنیا!",
  "time": "2023-05-01 12:34:56"
}
```

تبریک! شما اولین برنامه خود را با فلسک‌پی‌اچ‌پی ایجاد کردید.

## افزودن مسیرهای بیشتر

حالا بیایید چند مسیر دیگر به برنامه خود اضافه کنیم:

```php
// مسیر با پارامتر
$app->get('/hello/{name}', function(Request $request, Response $response) {
    $name = $request->param('name');
    return $response->text("سلام {$name}!");
});

// مسیر POST
$app->post('/users', function(Request $request, Response $response) {
    $data = $request->all();
    
    // اعتبارسنجی داده‌ها
    if (empty($data['name']) || empty($data['email'])) {
        return $response->status(400)->json([
            'error' => 'فیلدهای name و email الزامی هستند'
        ]);
    }
    
    // در دنیای واقعی اینجا داده‌ها را در دیتابیس ذخیره می‌کنید
    
    return $response->status(201)->json([
        'message' => 'کاربر با موفقیت ایجاد شد',
        'user' => [
            'id' => 123, // در دنیای واقعی این از دیتابیس می‌آید
            'name' => $data['name'],
            'email' => $data['email']
        ]
    ]);
});

// مسیر با متد PUT
$app->put('/users/{id}', function(Request $request, Response $response) {
    $id = $request->param('id');
    $data = $request->all();
    
    return $response->json([
        'message' => "کاربر با شناسه {$id} به‌روزرسانی شد",
        'user' => array_merge(['id' => $id], $data)
    ]);
});

// مسیر با متد DELETE
$app->delete('/users/{id}', function(Request $request, Response $response) {
    $id = $request->param('id');
    
    return $response->json([
        'message' => "کاربر با شناسه {$id} حذف شد"
    ]);
});
```

## تست مسیرها

برای تست مسیرهای ایجاد شده، می‌توانید از ابزارهایی مانند cURL، Postman یا ابزارهای مشابه استفاده کنید. در اینجا چند مثال با cURL آورده شده است:

### تست مسیر GET با پارامتر

```bash
curl http://localhost:8000/hello/امیر
```

خروجی: `سلام امیر!`

### تست مسیر POST

```bash
curl -X POST http://localhost:8000/users \
     -H "Content-Type: application/json" \
     -d '{"name":"علی رضایی","email":"ali@example.com"}'
```

### تست مسیر PUT

```bash
curl -X PUT http://localhost:8000/users/123 \
     -H "Content-Type: application/json" \
     -d '{"name":"علی رضایی (ویرایش شده)","email":"ali.updated@example.com"}'
```

### تست مسیر DELETE

```bash
curl -X DELETE http://localhost:8000/users/123
```

## اتصال به پایگاه داده

عملیات واقعی معمولاً نیازمند ارتباط با پایگاه داده هستند. فلسک‌پی‌اچ‌پی یک کوئری بیلدر و سیستم ORM ساده را ارائه می‌دهد که می‌تواند به راحتی با پایگاه‌های داده MySQL، PostgreSQL و SQLite کار کند.

### تنظیم اتصال به پایگاه داده

ابتدا باید یک اتصال به پایگاه داده ایجاد کنید:

```php
use PHLask\Database\Connection;

// تنظیمات پایگاه داده
$dbConfig = [
    'driver' => 'mysql',
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'my_database',
    'username' => 'root',
    'password' => 'your_password',
    'charset' => 'utf8mb4'
];

// ایجاد اتصال
try {
    Connection::connection('default', $dbConfig);
} catch (\Exception $e) {
    die('خطا در اتصال به پایگاه داده: ' . $e->getMessage());
}
```

### استفاده از کوئری بیلدر

حالا می‌توانید از کوئری بیلدر برای عملیات پایگاه داده استفاده کنید:

```php
use PHLask\Database\QueryBuilder;

// افزودن یک مسیر جدید برای دریافت لیست کاربران
$app->get('/users', function(Request $request, Response $response) {
    try {
        $query = new QueryBuilder('users');
        
        // افزودن محدودیت‌ها
        if ($request->query('search')) {
            $search = '%' . $request->query('search') . '%';
            $query->whereLike('name', $search)
                  ->orWhereLike('email', $search);
        }
        
        // مرتب‌سازی
        $query->orderBy('created_at', 'DESC');
        
        // صفحه‌بندی
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', 10);
        $query->paginate($page, $perPage);
        
        // اجرای کوئری
        $users = $query->get();
        $total = $query->count();
        
        return $response->json([
            'data' => $users,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'last_page' => ceil($total / $perPage)
            ]
        ]);
    } catch (\Exception $e) {
        return $response->status(500)->json([
            'error' => 'خطا در دریافت اطلاعات',
            'message' => $e->getMessage()
        ]);
    }
});
```

### استفاده از مدل‌ها

برای کار راحت‌تر با پایگاه داده، می‌توانید از مدل‌ها استفاده کنید. ابتدا یک کلاس مدل ایجاد کنید:

```php
<?php
// app/Models/User.php

namespace App\Models;

use PHLask\Database\Model;

class User extends Model
{
    // تنظیم نام جدول (اختیاری)
    protected static string $table = 'users';
    
    // تعریف متدهای اضافی
    public static function findByEmail(string $email): ?self
    {
        return static::findWhere('email', $email);
    }
    
    public function setPassword(string $password): self
    {
        $this->password = password_hash($password, PASSWORD_DEFAULT);
        return $this;
    }
    
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }
}
```

سپس استفاده از مدل در مسیرها:

```php
use App\Models\User;

// دریافت لیست کاربران با استفاده از مدل
$app->get('/api/users', function(Request $request, Response $response) {
    $users = User::all();
    return $response->json($users);
});

// یافتن یک کاربر با شناسه
$app->get('/api/users/{id}', function(Request $request, Response $response) {
    $id = $request->param('id');
    $user = User::find($id);
    
    if (!$user) {
        return $response->status(404)->json([
            'error' => 'کاربر یافت نشد'
        ]);
    }
    
    return $response->json($user->toArray());
});

// ایجاد کاربر جدید
$app->post('/api/users', function(Request $request, Response $response) {
    $data = $request->all();
    
    try {
        $user = new User($data);
        $user->setPassword($data['password']);
        $user->save();
        
        return $response->status(201)->json([
            'message' => 'کاربر با موفقیت ایجاد شد',
            'user' => $user->toArray()
        ]);
    } catch (\Exception $e) {
        return $response->status(400)->json([
            'error' => 'خطا در ایجاد کاربر',
            'message' => $e->getMessage()
        ]);
    }
});
```

## افزودن میان‌افزار (Middleware)

میان‌افزارها امکان پردازش درخواست قبل و بعد از اجرای handler اصلی را فراهم می‌کنند. به عنوان مثال، می‌توانیم یک میان‌افزار برای احراز هویت ایجاد کنیم:

```php
// افزودن میان‌افزار احراز هویت
$app->middleware(function(Request $request, callable $next) {
    // بررسی توکن احراز هویت در هدر
    $token = $request->getHeaderLine('Authorization');
    
    // مسیرهایی که نیاز به احراز هویت ندارند
    $publicPaths = ['/', '/login', '/register'];
    
    // اگر مسیر درخواستی عمومی است، بدون بررسی ادامه می‌دهیم
    $currentPath = $request->getUri()->getPath();
    if (in_array($currentPath, $publicPaths)) {
        return $next($request);
    }
    
    // بررسی وجود توکن
    if (empty($token) || !preg_match('/^Bearer\s+(.+)$/', $token, $matches)) {
        return new Response(401, [], json_encode([
            'error' => 'احراز هویت الزامی است',
            'message' => 'لطفاً توکن معتبر ارائه دهید'
        ]));
    }
    
    $tokenValue = $matches[1];
    
    // در دنیای واقعی، اینجا توکن را بررسی می‌کنید
    // مثلاً با JWT یا بررسی در پایگاه داده
    
    // فرض کنیم کاربر معتبر است
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

## مدیریت خطاها

می‌توانید مدیریت‌کننده‌های خطای سفارشی برای کدهای وضعیت HTTP مختلف تعریف کنید:

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
    // لاگ کردن خطا (در محیط واقعی)
    error_log($error->getMessage());
    
    return $response->status(500)->json([
        'error' => 'خطای داخلی سرور',
        'message' => 'متأسفانه مشکلی در پردازش درخواست شما به وجود آمده است.'
    ]);
});
```

## نمونه کامل یک برنامه ساده

با توجه به مفاهیمی که تا اینجا آموختیم، بیایید یک نمونه کامل از یک برنامه API ساده ایجاد کنیم:

```php
<?php
// public/index.php

require_once __DIR__ . '/../vendor/autoload.php';

use PHLask\App;
use PHLask\Http\Request;
use PHLask\Http\Response;
use PHLask\Database\Connection;
use PHLask\Exceptions\HttpException;

// تنظیمات پایگاه داده
$dbConfig = [
    'driver' => 'mysql',
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'my_app',
    'username' => 'root',
    'password' => 'your_password',
    'charset' => 'utf8mb4'
];

// اتصال به پایگاه داده
try {
    Connection::connection('default', $dbConfig);
} catch (\Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'خطا در اتصال به پایگاه داده',
        'message' => $e->getMessage()
    ]);
    exit;
}

// ایجاد نمونه برنامه
$app = App::getInstance();

// افزودن میان‌افزار CORS
$app->middleware(function(Request $request, callable $next) {
    $response = $next($request);
    
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
});

// میان‌افزار احراز هویت ساده
$app->middleware(function(Request $request, callable $next) {
    $publicPaths = ['/', '/login', '/register'];
    $path = $request->getUri()->getPath();
    
    if (in_array($path, $publicPaths)) {
        return $next($request);
    }
    
    $token = $request->getHeaderLine('Authorization');
    
    if (empty($token)) {
        throw new HttpException(401, 'احراز هویت الزامی است');
    }
    
    // در اینجا اعتبار توکن را بررسی کنید
    
    return $next($request);
});

// تعریف مسیرها

// صفحه اصلی
$app->get('/', function(Request $request, Response $response) {
    return $response->json([
        'message' => 'خوش آمدید به API فلسک‌پی‌اچ‌پی',
        'version' => '1.0.0'
    ]);
});

// مسیر ورود
$app->post('/login', function(Request $request, Response $response) {
    $data = $request->all();
    
    // بررسی وجود نام کاربری و رمز عبور
    if (empty($data['username']) || empty($data['password'])) {
        throw new HttpException(400, 'نام کاربری و رمز عبور الزامی هستند');
    }
    
    // در دنیای واقعی، اطلاعات کاربر را از دیتابیس دریافت و رمز عبور را بررسی می‌کنید
    if ($data['username'] === 'admin' && $data['password'] === 'password') {
        return $response->json([
            'token' => 'sample-token-123', // در واقعیت، یک توکن JWT تولید کنید
            'user' => [
                'id' => 1,
                'username' => 'admin',
                'name' => 'مدیر سیستم'
            ]
        ]);
    }
    
    throw new HttpException(401, 'نام کاربری یا رمز عبور نادرست است');
});

// دریافت لیست محصولات
$app->get('/products', function(Request $request, Response $response) {
    try {
        $query = new PHLask\Database\QueryBuilder('products');
        
        // اعمال فیلترها
        if ($request->query('category')) {
            $query->where('category_id', $request->query('category'));
        }
        
        if ($request->query('search')) {
            $query->whereLike('name', '%' . $request->query('search') . '%');
        }
        
        // اعمال مرتب‌سازی
        $query->orderBy('created_at', 'DESC');
        
        // اعمال صفحه‌بندی
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', 10);
        $query->paginate($page, $perPage);
        
        // دریافت داده‌ها
        $products = $query->get();
        $total = $query->count();
        
        return $response->json([
            'data' => $products,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'last_page' => ceil($total / $perPage)
            ]
        ]);
    } catch (\Exception $e) {
        throw new HttpException(500, 'خطا در دریافت محصولات: ' . $e->getMessage());
    }
});

// دریافت جزئیات یک محصول
$app->get('/products/{id}', function(Request $request, Response $response) {
    $id = $request->param('id');
    
    try {
        $product = (new PHLask\Database\QueryBuilder('products'))
            ->where('id', $id)
            ->first();
        
        if (!$product) {
            throw new HttpException(404, 'محصول یافت نشد');
        }
        
        return $response->json($product);
    } catch (HttpException $e) {
        throw $e;
    } catch (\Exception $e) {
        throw new HttpException(500, 'خطا در دریافت اطلاعات محصول: ' . $e->getMessage());
    }
});

// مدیریت خطاها
$app->errorHandler(400, function($error, Request $request, Response $response) {
    return $response->status(400)->json([
        'error' => 'درخواست نامعتبر',
        'message' => $error->getMessage()
    ]);
});

$app->errorHandler(401, function($error, Request $request, Response $response) {
    return $response->status(401)->json([
        'error' => 'احراز هویت ناموفق',
        'message' => $error->getMessage()
    ]);
});

$app->errorHandler(404, function($error, Request $request, Response $response) {
    return $response->status(404)->json([
        'error' => 'یافت نشد',
        'message' => $error->getMessage() ?: 'منبع درخواستی یافت نشد'
    ]);
});

$app->errorHandler(500, function($error, Request $request, Response $response) {
    // در محیط تولید، پیام خطا را برای کاربر نمایش ندهید
    $message = getenv('APP_ENV') === 'production' 
        ? 'خطای داخلی سرور'
        : $error->getMessage();
    
    return $response->status(500)->json([
        'error' => 'خطای داخلی سرور',
        'message' => $message
    ]);
});

// اجرای برنامه
$app->run();
```

## گام بعدی

در این راهنمای سریع، با مفاهیم اساسی فلسک‌پی‌اچ‌پی آشنا شدید. برای یادگیری بیشتر، به بخش‌های دیگر مستندات مراجعه کنید:

- [مسیریابی پیشرفته](../core-concepts/routing.md) - آشنایی با امکانات بیشتر مسیریابی
- [کار با میان‌افزارها](../core-concepts/middleware.md) - ایجاد و استفاده از میان‌افزارهای سفارشی
- [کوئری بیلدر](../database/query-builder.md) - آشنایی با تمام قابلیت‌های کوئری بیلدر
- [مدل‌ها](../database/models.md) - کار با مدل‌ها و ORM
- [مدیریت خطا](../core-concepts/error-handling.md) - شیوه‌های پیشرفته مدیریت خطا