# پیکربندی

پیکربندی مناسب یک جنبه مهم از توسعه برنامه‌های وب است. در این بخش، نحوه پیکربندی برنامه‌های فلسک‌پی‌اچ‌پی را یاد می‌گیرید.

## مقدمه

فلسک‌پی‌اچ‌پی از یک سیستم پیکربندی ساده و انعطاف‌پذیر استفاده می‌کند که به شما امکان می‌دهد تنظیمات مختلف برنامه را در فایل‌های جداگانه سازماندهی کنید. این رویکرد چندین مزیت دارد:

1. **سازماندهی بهتر**: تنظیمات مرتبط با هم در یک فایل قرار می‌گیرند.
2. **امنیت بیشتر**: تنظیمات حساس می‌توانند در فایل‌های پیکربندی خارج از دسترس عمومی قرار گیرند.
3. **انعطاف‌پذیری**: تنظیمات می‌توانند بر اساس محیط (توسعه، آزمایش، تولید) متفاوت باشند.
4. **سادگی**: دسترسی به تنظیمات در همه جای برنامه آسان است.

## ساختار فایل‌های پیکربندی

در یک پروژه فلسک‌پی‌اچ‌پی، پیشنهاد می‌شود فایل‌های پیکربندی را در یک پوشه جداگانه به نام `config` قرار دهید:

```
project-root/
├── config/
│   ├── app.php
│   ├── database.php
│   ├── mail.php
│   ├── logging.php
│   └── services.php
```

هر فایل پیکربندی یک آرایه PHP برمی‌گرداند که حاوی تنظیمات مرتبط است:

```php
// config/app.php
return [
    'name' => 'My Flask PHP App',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => $_ENV['APP_DEBUG'] ?? false,
    'url' => $_ENV['APP_URL'] ?? 'http://localhost',
    'timezone' => 'Asia/Tehran',
    'locale' => 'fa',
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
            'database' => $_ENV['DB_DATABASE'] ?? 'database.sqlite',
        ],
    ],
];

// config/mail.php
return [
    'default' => $_ENV['MAIL_MAILER'] ?? 'smtp',
    'mailers' => [
        'smtp' => [
            'host' => $_ENV['MAIL_HOST'] ?? 'smtp.mailgun.org',
            'port' => $_ENV['MAIL_PORT'] ?? 587,
            'username' => $_ENV['MAIL_USERNAME'] ?? '',
            'password' => $_ENV['MAIL_PASSWORD'] ?? '',
            'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
        ],
    ],
    'from' => [
        'address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'hello@example.com',
        'name' => $_ENV['MAIL_FROM_NAME'] ?? 'Example',
    ],
];
```

## متغیرهای محیطی

برای پیکربندی مبتنی بر محیط، استفاده از متغیرهای محیطی و فایل `.env` توصیه می‌شود. فلسک‌پی‌اچ‌پی از کتابخانه `vlucas/phpdotenv` برای بارگذاری متغیرهای محیطی از فایل `.env` پشتیبانی می‌کند.

### نصب phpdotenv

ابتدا کتابخانه را نصب کنید:

```bash
composer require vlucas/phpdotenv
```

### ایجاد فایل .env

یک فایل `.env` در پوشه اصلی پروژه ایجاد کنید:

```env
# محیط برنامه
APP_NAME="My Flask PHP App"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# تنظیمات پایگاه داده
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=my_database
DB_USERNAME=root
DB_PASSWORD=secret

# تنظیمات ایمیل
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=null
MAIL_FROM_NAME="${APP_NAME}"
```

همچنین یک فایل `.env.example` ایجاد کنید که می‌تواند در کنترل نسخه قرار گیرد و به عنوان الگویی برای تنظیمات استفاده شود:

```env
APP_NAME="My Flask PHP App"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=my_database
DB_USERNAME=root
DB_PASSWORD=

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=hello@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

### بارگذاری متغیرهای محیطی

در فایل `public/index.php` یا هر نقطه ورودی دیگر برنامه، متغیرهای محیطی را بارگذاری کنید:

```php
require_once __DIR__ . '/../vendor/autoload.php';

// بارگذاری متغیرهای محیطی
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// ادامه برنامه
```

## بارگذاری و دسترسی به تنظیمات

### بارگذاری فایل‌های پیکربندی

فایل‌های پیکربندی را بارگذاری کنید و به نمونه برنامه `App` ارسال کنید:

```php
// public/index.php

// بارگذاری فایل‌های پیکربندی
$appConfig = require_once __DIR__ . '/../config/app.php';
$dbConfig = require_once __DIR__ . '/../config/database.php';
$mailConfig = require_once __DIR__ . '/../config/mail.php';

// ایجاد نمونه برنامه
$app = FlaskPHP\App::getInstance();

// تنظیم پیکربندی‌ها
$app->loadConfig($appConfig);
$app->enableDatabase($dbConfig['connections'][$dbConfig['default']]);
```

### ایجاد یک کلاس Config

برای مدیریت بهتر تنظیمات، می‌توانید یک کلاس `Config` ایجاد کنید:

```php
// app/Config/Config.php
namespace App\Config;

class Config
{
    private static array $config = [];
    
    public static function load(string $file): void
    {
        $path = __DIR__ . '/../../config/' . $file . '.php';
        if (file_exists($path)) {
            $values = require $path;
            if (is_array($values)) {
                self::$config[$file] = $values;
            }
        }
    }
    
    public static function get(string $key, $default = null)
    {
        $parts = explode('.', $key);
        $file = $parts[0];
        
        if (!isset(self::$config[$file])) {
            self::load($file);
        }
        
        $config = self::$config[$file] ?? [];
        
        // برای دسترسی به مقادیر تو در تو مانند 'database.connections.mysql.host'
        for ($i = 1; $i < count($parts); $i++) {
            $part = $parts[$i];
            if (isset($config[$part])) {
                $config = $config[$part];
            } else {
                return $default;
            }
        }
        
        return $config;
    }
    
    public static function set(string $key, $value): void
    {
        $parts = explode('.', $key);
        $file = $parts[0];
        
        if (!isset(self::$config[$file])) {
            self::load($file);
        }
        
        $config = &self::$config;
        
        for ($i = 0; $i < count($parts) - 1; $i++) {
            $part = $parts[$i];
            if (!isset($config[$part])) {
                $config[$part] = [];
            }
            $config = &$config[$part];
        }
        
        $config[$parts[count($parts) - 1]] = $value;
    }
}
```

استفاده از کلاس Config:

```php
use App\Config\Config;

// بارگذاری همه فایل‌های پیکربندی در ابتدای برنامه
Config::load('app');
Config::load('database');
Config::load('mail');

// دسترسی به تنظیمات
$appName = Config::get('app.name', 'Default App Name');
$dbHost = Config::get('database.connections.mysql.host', 'localhost');
$mailEncryption = Config::get('mail.mailers.smtp.encryption', 'tls');

// تنظیم یک مقدار جدید
Config::set('app.debug', true);
```

## محیط‌های مختلف

یکی از بهترین روش‌ها، داشتن پیکربندی‌های متفاوت برای محیط‌های مختلف (توسعه، آزمایش، تولید) است. می‌توانید از متغیر محیطی `APP_ENV` برای تعیین محیط فعلی استفاده کنید:

```php
// config/app.php
$environment = $_ENV['APP_ENV'] ?? 'production';

$config = [
    'name' => 'My Flask PHP App',
    'env' => $environment,
    'debug' => false,
    'url' => 'http://localhost',
    'timezone' => 'Asia/Tehran',
];

// تنظیمات خاص هر محیط
if ($environment === 'development' || $environment === 'local') {
    $config['debug'] = true;
} elseif ($environment === 'testing') {
    $config['debug'] = true;
    // تنظیمات خاص تست
}

return $config;
```

## بهترین روش‌ها

### 1. تنظیمات حساس را در متغیرهای محیطی قرار دهید

تمام اطلاعات حساس مانند رمزهای عبور، کلیدهای API و غیره را در فایل `.env` قرار دهید و هرگز این فایل را در مخزن کد خود کامیت نکنید:

```php
// درست
$apiKey = $_ENV['API_KEY'] ?? '';

// نادرست
$apiKey = 'your-secret-api-key-here';
```

### 2. مقادیر پیش‌فرض معنادار تعریف کنید

همیشه برای متغیرهای محیطی، مقادیر پیش‌فرض معنادار تعریف کنید:

```php
// تعریف مقدار پیش‌فرض
$debug = $_ENV['APP_DEBUG'] ?? false;

// از عملگر نال کوئلسینگ استفاده کنید
$timezone = $_ENV['APP_TIMEZONE'] ?: 'UTC';
```

### 3. تنظیمات را در فایل‌های منطقی گروه‌بندی کنید

تنظیمات مرتبط را در فایل‌های مجزا گروه‌بندی کنید:

```
config/
├── app.php         # تنظیمات کلی برنامه
├── database.php    # تنظیمات پایگاه داده
├── mail.php        # تنظیمات ایمیل
├── logging.php     # تنظیمات لاگ
├── services.php    # تنظیمات سرویس‌های خارجی
└── cache.php       # تنظیمات کش
```

### 4. از نام‌های توصیفی استفاده کنید

برای کلیدهای پیکربندی، از نام‌های توصیفی و معنادار استفاده کنید:

```php
// درست
'login_attempts_max' => 5,
'login_attempts_timeout' => 60, // seconds

// نادرست
'max' => 5,
'timeout' => 60,
```

### 5. کامنت‌گذاری مناسب انجام دهید

تنظیمات را با کامنت‌های توضیحی مستند کنید:

```php
return [
    // حداکثر تعداد تلاش‌های ناموفق ورود به سیستم
    // پس از رسیدن به این تعداد، حساب کاربر موقتاً قفل می‌شود
    'login_attempts_max' => 5,
    
    // مدت زمان قفل حساب کاربر پس از تلاش‌های ناموفق (به ثانیه)
    'login_attempts_timeout' => 300, // 5 minutes
];
```

## نمونه‌های کاربردی

### مثال 1: پیکربندی پایگاه داده

```php
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
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ],
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => $_ENV['DB_DATABASE'] ?? 'database.sqlite',
        ],
    ],
    // تنظیمات مهاجرت (migration)
    'migrations' => [
        'table' => 'migrations',
        'path' => __DIR__ . '/../database/migrations',
    ],
];

// استفاده
$dbConfig = require __DIR__ . '/config/database.php';
$connection = $dbConfig['connections'][$dbConfig['default']];

$db = new FlaskPHP\Database\Connection($connection);
```

### مثال 2: پیکربندی احراز هویت

```php
// config/auth.php
return [
    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],
    
    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        'api' => [
            'driver' => 'token',
            'provider' => 'users',
            'hash' => false,
        ],
    ],
    
    'providers' => [
        'users' => [
            'driver' => 'database',
            'table' => 'users',
        ],
    ],
    
    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_resets',
            'expire' => 60, // minutes
            'throttle' => 60, // seconds
        ],
    ],
    
    'token' => [
        'lifetime' => 3600, // seconds
        'refresh_lifetime' => 604800, // 7 days
    ],
];

// استفاده
$authConfig = require __DIR__ . '/config/auth.php';
$tokenLifetime = $authConfig['token']['lifetime'];
```

### مثال 3: پیکربندی کش

```php
// config/cache.php
return [
    'default' => $_ENV['CACHE_DRIVER'] ?? 'file',
    
    'stores' => [
        'file' => [
            'driver' => 'file',
            'path' => __DIR__ . '/../storage/cache',
        ],
        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
        ],
        'memcached' => [
            'driver' => 'memcached',
            'servers' => [
                [
                    'host' => $_ENV['MEMCACHED_HOST'] ?? '127.0.0.1',
                    'port' => $_ENV['MEMCACHED_PORT'] ?? 11211,
                    'weight' => 100,
                ],
            ],
        ],
    ],
    
    'prefix' => $_ENV['CACHE_PREFIX'] ?? 'flask_cache',
    'ttl' => 3600, // seconds
];
```

### مثال 4: پیکربندی ثبت لاگ

```php
// config/logging.php
return [
    'default' => $_ENV['LOG_CHANNEL'] ?? 'single',
    
    'channels' => [
        'single' => [
            'driver' => 'single',
            'path' => __DIR__ . '/../storage/logs/app.log',
            'level' => $_ENV['LOG_LEVEL'] ?? 'debug',
        ],
        'daily' => [
            'driver' => 'daily',
            'path' => __DIR__ . '/../storage/logs/app.log',
            'level' => $_ENV['LOG_LEVEL'] ?? 'debug',
            'days' => 14,
        ],
        'syslog' => [
            'driver' => 'syslog',
            'level' => $_ENV['LOG_LEVEL'] ?? 'debug',
        ],
        'errorlog' => [
            'driver' => 'errorlog',
            'level' => $_ENV['LOG_LEVEL'] ?? 'debug',
        ],
    ],
];
```

### مثال 5: پیکربندی برنامه در محیط‌های مختلف

```php
// public/index.php
require_once __DIR__ . '/../vendor/autoload.php';

// بارگذاری متغیرهای محیطی
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// تعیین محیط برنامه
$environment = $_ENV['APP_ENV'] ?? 'production';

// بارگذاری پیکربندی مناسب محیط
$configPath = __DIR__ . '/../config/';
$appConfig = require $configPath . 'app.php';

// پیکربندی اضافی برای محیط خاص
if (file_exists($configPath . $environment . '/app.php')) {
    $envConfig = require $configPath . $environment . '/app.php';
    $appConfig = array_merge($appConfig, $envConfig);
}

// ایجاد نمونه برنامه
$app = FlaskPHP\App::getInstance();
$app->loadConfig($appConfig);

// فعال‌سازی حالت دیباگ در محیط‌های غیر تولیدی
if ($environment !== 'production') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// ادامه برنامه
// ...
```

## توابع کمکی

می‌توانید توابع کمکی برای دسترسی آسان‌تر به تنظیمات ایجاد کنید:

```php
// app/Helpers/functions.php

if (!function_exists('config')) {
    /**
     * دریافت مقدار پیکربندی
     *
     * @param string $key کلید پیکربندی به فرمت 'file.key'
     * @param mixed $default مقدار پیش‌فرض در صورت عدم وجود کلید
     * @return mixed
     */
    function config(string $key, $default = null)
    {
        return \App\Config\Config::get($key, $default);
    }
}

if (!function_exists('env')) {
    /**
     * دریافت مقدار متغیر محیطی
     *
     * @param string $key نام متغیر محیطی
     * @param mixed $default مقدار پیش‌فرض
     * @return mixed
     */
    function env(string $key, $default = null)
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? $default;
        
        // تبدیل مقادیر خاص
        if ($value === 'true') return true;
        if ($value === 'false') return false;
        if ($value === 'null') return null;
        
        return $value;
    }
}
```

استفاده از توابع کمکی:

```php
// نام برنامه
$appName = config('app.name', 'My App');

// اتصال به پایگاه داده
$dbConnection = config('database.connections.' . config('database.default'));

// متغیر محیطی
$debug = env('APP_DEBUG', false);
```

## امنیت در پیکربندی

هنگام کار با فایل‌های پیکربندی، رعایت نکات امنیتی بسیار مهم است:

### 1. پیکربندی‌های حساس

هرگز اطلاعات حساس مانند رمزهای عبور، کلیدهای API و غیره را مستقیماً در فایل‌های پیکربندی قرار ندهید. به جای آن، از متغیرهای محیطی استفاده کنید:

```php
// نادرست
'api_key' => 'your-secret-key-123',

// درست
'api_key' => env('API_KEY'),
```

### 2. محدودیت دسترسی

اطمینان حاصل کنید که فایل‌های پیکربندی فقط توسط سرور وب قابل خواندن هستند و از دسترسی مستقیم به آنها از طریق وب جلوگیری شود:

```apache
# .htaccess در پوشه config
<IfModule mod_authz_core.c>
    Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
    Deny from all
</IfModule>
```

### 3. فایل `.env`

فایل `.env` باید همیشه در `.gitignore` قرار بگیرد تا در مخزن کد قرار نگیرد:

```
.env
.env.*
!.env.example
```

### 4. بررسی‌های اعتبارسنجی

همیشه ورودی‌های پیکربندی را اعتبارسنجی کنید، به خصوص اگر از منابع خارجی دریافت می‌شوند:

```php
// بررسی اعتبار محیط
$environment = $_ENV['APP_ENV'] ?? 'production';
$validEnvironments = ['local', 'development', 'testing', 'staging', 'production'];

if (!in_array($environment, $validEnvironments)) {
    die('Invalid environment: ' . $environment);
}
```

## عیب‌یابی مشکلات پیکربندی

### مشکل 1: متغیرهای محیطی بارگذاری نمی‌شوند

اگر متغیرهای محیطی بارگذاری نمی‌شوند، موارد زیر را بررسی کنید:

1. آیا فایل `.env` در مسیر درست قرار دارد؟
2. آیا دسترسی‌های فایل `.env` درست است؟
3. آیا کتابخانه `vlucas/phpdotenv` نصب شده است؟
4. آیا کد بارگذاری `Dotenv` به درستی فراخوانی شده است؟

```php
// بررسی وجود فایل .env
$envPath = __DIR__ . '/../.env';
if (!file_exists($envPath)) {
    die('.env file not found. Please create one based on .env.example');
}

try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
} catch (\Exception $e) {
    die('Error loading .env file: ' . $e->getMessage());
}
```

### مشکل 2: تنظیمات اشتباه

اگر تنظیمات اشتباه بارگذاری می‌شوند، موارد زیر را بررسی کنید:

1. آیا فایل‌های پیکربندی در مسیر درست قرار دارند؟
2. آیا مقادیر پیش‌فرض مناسب تعریف شده‌اند؟
3. آیا اولویت بارگذاری تنظیمات (محیطی، پایه) درست است؟

```php
// بررسی وجود فایل پیکربندی
$configFile = __DIR__ . '/../config/app.php';
if (!file_exists($configFile)) {
    die('Configuration file not found: ' . $configFile);
}

// چاپ تنظیمات برای دیباگ
echo '<pre>';
print_r(require $configFile);
echo '</pre>';
```

## گام بعدی

اکنون که با سیستم پیکربندی فلسک‌پی‌اچ‌پی آشنا شدید، می‌توانید به بخش‌های دیگر مستندات مراجعه کنید:

- [برنامه (App)](../core-concepts/app.md) - آشنایی با کلاس اصلی برنامه
- [مسیریابی (Routing)](../core-concepts/routing.md) - آشنایی با سیستم مسیریابی
- [اتصال به پایگاه داده](../database/connection.md) - آشنایی با اتصال به پایگاه داده