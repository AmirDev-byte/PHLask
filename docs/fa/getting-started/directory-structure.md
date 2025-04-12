# ساختار پروژه

در این بخش با ساختار پیشنهادی پروژه‌های فلسک‌پی‌اچ‌پی آشنا می‌شوید. پیروی از این ساختار به شما کمک می‌کند تا پروژه‌های
خود را به روشی منظم و استاندارد سازماندهی کنید، که باعث افزایش قابلیت خوانایی، نگهداری و توسعه پروژه می‌شود.

## ساختار پایه

یک پروژه فلسک‌پی‌اچ‌پی معمولاً دارای ساختار زیر است:

```
project-root/
├── app/
│   ├── Controllers/
│   ├── Models/
│   ├── Middleware/
│   ├── Services/
│   ├── Repositories/
│   └── Helpers/
├── config/
├── public/
│   ├── index.php
│   ├── assets/
│   └── .htaccess
├── resources/
│   ├── views/
│   ├── lang/
│   └── css/
├── routes/
├── storage/
│   ├── logs/
│   ├── cache/
│   └── uploads/
├── tests/
├── vendor/
├── .env
├── .env.example
├── .gitignore
├── composer.json
└── README.md
```

## توضیح پوشه‌ها و فایل‌ها

### 1. پوشه `app/`

این پوشه حاوی کد اصلی برنامه شماست:

- **Controllers/**: کنترلرها مسئول پردازش درخواست‌ها و برگرداندن پاسخ‌ها هستند.
  ```php
  // app/Controllers/UserController.php
  namespace App\Controllers;
  
  use PHLask\Http\Request;
  use PHLask\Http\Response;
  
  class UserController
  {
      public function index(Request $request, Response $response)
      {
          $users = $this->userService->getAllUsers();
          return $response->json($users);
      }
  }
  ```

- **Models/**: مدل‌ها نمایانگر جداول پایگاه داده و منطق مربوط به داده‌ها هستند.
  ```php
  // app/Models/User.php
  namespace App\Models;
  
  use PHLask\Database\Model;
  
  class User extends Model
  {
      protected static string $table = 'users';
  }
  ```

- **Middleware/**: میان‌افزارها درخواست‌ها را قبل و بعد از پردازش اصلی تغییر می‌دهند.
  ```php
  // app/Middleware/AuthMiddleware.php
  namespace App\Middleware;
  
  use PHLask\Http\Request;
  
  class AuthMiddleware
  {
      public function __invoke(Request $request, callable $next)
      {
          // بررسی احراز هویت
          if (!$this->isAuthenticated($request)) {
              return new Response(401, [], json_encode(['error' => 'Unauthorized']));
          }
          
          return $next($request);
      }
  }
  ```

- **Services/**: سرویس‌ها منطق کسب‌وکار برنامه را پیاده‌سازی می‌کنند.
  ```php
  // app/Services/UserService.php
  namespace App\Services;
  
  use App\Repositories\UserRepository;
  
  class UserService
  {
      private $userRepository;
      
      public function __construct(UserRepository $userRepository)
      {
          $this->userRepository = $userRepository;
      }
      
      public function getAllUsers()
      {
          return $this->userRepository->getAll();
      }
  }
  ```

- **Repositories/**: ریپوزیتوری‌ها رابط بین منطق کسب‌وکار و لایه دسترسی به داده‌ها هستند.
  ```php
  // app/Repositories/UserRepository.php
  namespace App\Repositories;
  
  use App\Models\User;
  
  class UserRepository
  {
      public function getAll()
      {
          return User::all();
      }
  }
  ```

- **Helpers/**: توابع کمکی که در سراسر برنامه استفاده می‌شوند.
  ```php
  // app/Helpers/StringHelper.php
  namespace App\Helpers;
  
  class StringHelper
  {
      public static function slugify(string $text): string
      {
          // تبدیل متن به slug
      }
  }
  ```

### 2. پوشه `config/`

این پوشه حاوی فایل‌های پیکربندی برنامه است:

```php
// config/app.php
return [
    'name' => 'My Flask PHP App',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => $_ENV['APP_DEBUG'] ?? false,
    'url' => $_ENV['APP_URL'] ?? 'http://localhost',
    'timezone' => 'Asia/Tehran',
];

// config/database.php
return [
    'default' => $_ENV['DB_CONNECTION'] ?? 'mysql',
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['DB_PORT'] ?? 3306,
            'database' => $_ENV['DB_DATABASE'] ?? 'forge',
            'username' => $_ENV['DB_USERNAME'] ?? 'forge',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ],
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => $_ENV['DB_DATABASE'] ?? database_path('database.sqlite'),
        ],
    ],
];
```

### 3. پوشه `public/`

این پوشه تنها پوشه‌ای است که باید برای وب سرور قابل دسترسی باشد:

- **index.php**: نقطه ورود اصلی برنامه
  ```php
  // public/index.php
  <?php
  
  require_once __DIR__ . '/../vendor/autoload.php';
  
  // بارگذاری متغیرهای محیطی
  $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
  $dotenv->load();
  
  // ایجاد نمونه برنامه
  $app = PHLask\App::getInstance();
  
  // بارگذاری مسیرها
  require_once __DIR__ . '/../routes/web.php';
  require_once __DIR__ . '/../routes/api.php';
  
  // اجرای برنامه
  $app->run();
  ```

- **.htaccess**: تنظیمات Apache برای مسیریابی
  ```apache
  # public/.htaccess
  <IfModule mod_rewrite.c>
      <IfModule mod_negotiation.c>
          Options -MultiViews -Indexes
      </IfModule>
  
      RewriteEngine On
  
      # Handle Authorization Header
      RewriteCond %{HTTP:Authorization} .
      RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
  
      # Redirect Trailing Slashes If Not A Folder...
      RewriteCond %{REQUEST_FILENAME} !-d
      RewriteCond %{REQUEST_URI} (.+)/$
      RewriteRule ^ %1 [L,R=301]
  
      # Send Requests To Front Controller...
      RewriteCond %{REQUEST_FILENAME} !-d
      RewriteCond %{REQUEST_FILENAME} !-f
      RewriteRule ^ index.php [L]
  </IfModule>
  ```

- **assets/**: فایل‌های استاتیک مانند CSS، JavaScript و تصاویر

### 4. پوشه `resources/`

این پوشه حاوی منابع غیر PHP برنامه است:

- **views/**: قالب‌های HTML
  ```php
  <!-- resources/views/users/index.php -->
  <!DOCTYPE html>
  <html>
      <head>
          <title>لیست کاربران</title>
      </head>
      <body>
          <h1>لیست کاربران</h1>
          <ul>
              <?php foreach ($users as $user): ?>
                  <li><?= $user['name'] ?></li>
              <?php endforeach; ?>
          </ul>
      </body>
  </html>
  ```

- **lang/**: فایل‌های ترجمه
  ```php
  // resources/lang/fa/messages.php
  return [
      'welcome' => 'خوش آمدید',
      'login' => 'ورود',
      'register' => 'ثبت‌نام',
  ];
  ```

- **css/**: فایل‌های CSS اصلی (قبل از کامپایل)

### 5. پوشه `routes/`

این پوشه حاوی فایل‌های تعریف مسیر است:

```php
// routes/web.php
<?php

use App\Controllers\HomeController;
use App\Controllers\UserController;

$app->get('/', [HomeController::class, 'index']);
$app->get('/about', [HomeController::class, 'about']);
$app->get('/contact', [HomeController::class, 'contact']);

$app->get('/users', [UserController::class, 'index']);
$app->get('/users/{id}', [UserController::class, 'show']);
$app->post('/users', [UserController::class, 'store']);
$app->put('/users/{id}', [UserController::class, 'update']);
$app->delete('/users/{id}', [UserController::class, 'destroy']);

// routes/api.php
<?php

use App\Controllers\Api\UserController;
use App\Controllers\Api\AuthController;
use App\Middleware\AuthMiddleware;

$app->post('/api/login', [AuthController::class, 'login']);
$app->post('/api/register', [AuthController::class, 'register']);

// مسیرهای نیازمند احراز هویت
$app->middleware(new AuthMiddleware())->group(function($app) {
    $app->get('/api/users', [UserController::class, 'index']);
    $app->get('/api/users/{id}', [UserController::class, 'show']);
    $app->post('/api/users', [UserController::class, 'store']);
    $app->put('/api/users/{id}', [UserController::class, 'update']);
    $app->delete('/api/users/{id}', [UserController::class, 'destroy']);
});
```

### 6. پوشه `storage/`

این پوشه حاوی فایل‌هایی است که در زمان اجرای برنامه ایجاد می‌شوند:

- **logs/**: فایل‌های لاگ
- **cache/**: فایل‌های کش
- **uploads/**: فایل‌های آپلود شده توسط کاربران

### 7. پوشه `tests/`

این پوشه حاوی فایل‌های تست است:

```php
// tests/UserTest.php
namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Models\User;

class UserTest extends TestCase
{
    public function testUserCreation()
    {
        $user = new User([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        
        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
    }
}
```

### 8. پوشه `vendor/`

این پوشه توسط Composer ایجاد می‌شود و حاوی کتابخانه‌های خارجی است. این پوشه نباید در کنترل نسخه گنجانده شود.

### 9. فایل `.env`

این فایل حاوی متغیرهای محیطی برنامه است:

```env
APP_NAME="My Flask PHP App"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=my_database
DB_USERNAME=root
DB_PASSWORD=
```

### 10. فایل `.env.example`

نمونه‌ای از فایل `.env` که می‌تواند در کنترل نسخه گنجانده شود.

### 11. فایل `.gitignore`

فایل‌ها و پوشه‌هایی که نباید در کنترل نسخه گنجانده شوند:

```
/vendor/
/node_modules/
/storage/logs/*
/storage/cache/*
/storage/uploads/*
.env
.phpunit.result.cache
composer.lock
```

### 12. فایل `composer.json`

پیکربندی Composer برای مدیریت وابستگی‌ها:

```json
{
  "name": "your-name/your-project",
  "description": "Your project description",
  "type": "project",
  "require": {
    "php": ">=7.4",
    "amirdev-byte/phlask": "^1.0",
    "vlucas/phpdotenv": "^5.3"
  },
  "require-dev": {
    "phpunit/phpunit": "^9.5"
  },
  "autoload": {
    "psr-4": {
      "App\\": "app/"
    },
    "files": [
      "app/Helpers/functions.php"
    ]
  },
  "autoload-dev": {
    "psr-4": {
      "Tests\\": "tests/"
    }
  },
  "scripts": {
    "test": "phpunit"
  }
}
```

## ساختار یک پروژه کوچک

برای پروژه‌های کوچک‌تر، می‌توانید از ساختار ساده‌تری استفاده کنید:

```
project-root/
├── app/
│   ├── Controllers/
│   ├── Models/
│   └── Middleware/
├── public/
│   ├── index.php
│   ├── assets/
│   └── .htaccess
├── views/
├── storage/
├── vendor/
├── .env
├── composer.json
└── README.md
```

## ساختار یک API

اگر فقط یک API ایجاد می‌کنید، می‌توانید از ساختار زیر استفاده کنید:

```
api-root/
├── app/
│   ├── Controllers/
│   ├── Models/
│   ├── Middleware/
│   └── Services/
├── config/
├── public/
│   ├── index.php
│   └── .htaccess
├── routes/
├── storage/
│   └── logs/
├── tests/
├── vendor/
├── .env
├── composer.json
└── README.md
```

## نکات مهم

1. **امنیت**: فقط پوشه `public/` باید از طریق وب سرور قابل دسترسی باشد.
2. **مجوزها**: اطمینان حاصل کنید که پوشه `storage/` و زیرپوشه‌های آن توسط وب سرور قابل نوشتن هستند.
3. **اتولودینگ**: از PSR-4 برای اتولودینگ کلاس‌ها استفاده کنید.
4. **متغیرهای محیطی**: اطلاعات حساس مانند رمزهای عبور پایگاه داده را در فایل `.env` قرار دهید و آن را در کنترل نسخه
   نگنجانید.
5. **کنترل نسخه**: از Git برای کنترل نسخه استفاده کنید و فایل `.gitignore` مناسب ایجاد کنید.

## نمونه کامل یک پروژه

یک نمونه کامل از ساختار پروژه با چند فایل نمونه:

### پوشه‌ها و فایل‌ها

```
my-flask-app/
├── app/
│   ├── Controllers/
│   │   ├── HomeController.php
│   │   └── UserController.php
│   ├── Models/
│   │   └── User.php
│   ├── Middleware/
│   │   └── AuthMiddleware.php
│   ├── Services/
│   │   └── UserService.php
│   └── Repositories/
│       └── UserRepository.php
├── config/
│   ├── app.php
│   └── database.php
├── public/
│   ├── index.php
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   └── images/
│   └── .htaccess
├── resources/
│   └── views/
│       ├── home.php
│       └── users/
│           ├── index.php
│           └── show.php
├── routes/
│   ├── web.php
│   └── api.php
├── storage/
│   ├── logs/
│   ├── cache/
│   └── uploads/
├── .env
├── .env.example
├── .gitignore
├── composer.json
└── README.md
```

### نمونه فایل‌ها

```php
// app/Controllers/UserController.php
namespace App\Controllers;

use PHLask\Http\Request;
use PHLask\Http\Response;
use App\Services\UserService;

class UserController
{
    private $userService;
    
    public function __construct()
    {
        $this->userService = new UserService();
    }
    
    public function index(Request $request, Response $response)
    {
        $users = $this->userService->getAllUsers();
        
        // برای API
        if ($request->wantsJson()) {
            return $response->json($users);
        }
        
        // برای وب
        $html = view('users/index', ['users' => $users]);
        return $response->html($html);
    }
    
    public function show(Request $request, Response $response)
    {
        $id = $request->param('id');
        $user = $this->userService->getUserById($id);
        
        if (!$user) {
            return $response->status(404)->json(['error' => 'User not found']);
        }
        
        if ($request->wantsJson()) {
            return $response->json($user);
        }
        
        $html = view('users/show', ['user' => $user]);
        return $response->html($html);
    }
}
```

```php
// public/index.php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

// بارگذاری متغیرهای محیطی
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// ایجاد نمونه برنامه
$app = PHLask\App::getInstance();

// بارگذاری پیکربندی
$app->loadConfig(require_once __DIR__ . '/../config/app.php');

// فعال‌سازی اتصال به پایگاه داده
$dbConfig = require_once __DIR__ . '/../config/database.php';
$app->enableDatabase($dbConfig['connections'][$dbConfig['default']]);

// بارگذاری مسیرها
require_once __DIR__ . '/../routes/web.php';
require_once __DIR__ . '/../routes/api.php';

// اجرای برنامه
$app->run();
```

## گام بعدی

اکنون که با ساختار پیشنهادی پروژه آشنا شدید، می‌توانید به بخش‌های دیگر مستندات مراجعه کنید:

- [پیکربندی](configuration.md) - آشنایی با پیکربندی برنامه
- [مسیریابی](../core-concepts/routing.md) - آشنایی با سیستم مسیریابی
- [مدل‌ها](../database/models.md) - کار با مدل‌های داده‌ای
- [میان‌افزارها](../core-concepts/middleware.md) - استفاده از میان‌افزارها