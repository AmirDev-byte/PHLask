# کش کردن (Caching)

کش کردن یک تکنیک بهینه‌سازی است که با ذخیره موقت داده‌ها، محاسبات گران‌قیمت یا نتایج درخواست‌های مکرر، میزان بار روی
سرور را کاهش داده و سرعت پاسخگویی را افزایش می‌دهد. در این بخش، با روش‌های مختلف کش کردن در فلسک‌پی‌اچ‌پی آشنا می‌شوید.

## اهمیت کش کردن

کش کردن می‌تواند مزایای زیادی داشته باشد:

1. **افزایش سرعت**: کاهش زمان پاسخگویی به درخواست‌ها
2. **کاهش بار سرور**: کاهش تعداد عملیات پردازشی و درخواست‌های پایگاه داده
3. **افزایش مقیاس‌پذیری**: امکان پاسخگویی به تعداد بیشتری از کاربران با منابع یکسان
4. **کاهش هزینه‌ها**: استفاده بهینه از منابع سرور

## انواع کش کردن

فلسک‌پی‌اچ‌پی از چندین روش کش کردن پشتیبانی می‌کند:

### 1. کش در حافظه

برای داده‌هایی که مکرراً استفاده می‌شوند اما حجم زیادی ندارند، می‌توان از کش در حافظه استفاده کرد. این روش ساده‌ترین و
سریع‌ترین روش کش کردن است:

```php
/**
 * کلاس کش در حافظه
 */
class MemoryCache
{
    /**
     * @var array داده‌های کش شده
     */
    private static array $cache = [];
    
    /**
     * @var array زمان منقضی شدن آیتم‌های کش
     */
    private static array $expiration = [];
    
    /**
     * ذخیره داده در کش
     * 
     * @param string $key کلید
     * @param mixed $value مقدار
     * @param int $ttl زمان زنده ماندن (به ثانیه)
     * @return bool
     */
    public static function set(string $key, $value, int $ttl = 60): bool
    {
        self::$cache[$key] = $value;
        self::$expiration[$key] = time() + $ttl;
        
        return true;
    }
    
    /**
     * دریافت داده از کش
     * 
     * @param string $key کلید
     * @param mixed $default مقدار پیش‌فرض در صورت عدم وجود یا منقضی شدن
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        if (!self::has($key)) {
            return $default;
        }
        
        return self::$cache[$key];
    }
    
    /**
     * بررسی وجود داده در کش
     * 
     * @param string $key کلید
     * @return bool
     */
    public static function has(string $key): bool
    {
        if (!isset(self::$cache[$key]) || !isset(self::$expiration[$key])) {
            return false;
        }
        
        // بررسی منقضی نشدن
        if (self::$expiration[$key] < time()) {
            self::delete($key);
            return false;
        }
        
        return true;
    }
    
    /**
     * حذف داده از کش
     * 
     * @param string $key کلید
     * @return bool
     */
    public static function delete(string $key): bool
    {
        unset(self::$cache[$key]);
        unset(self::$expiration[$key]);
        
        return true;
    }
    
    /**
     * پاک کردن کل کش
     * 
     * @return bool
     */
    public static function clear(): bool
    {
        self::$cache = [];
        self::$expiration = [];
        
        return true;
    }
    
    /**
     * دریافت یا ذخیره داده در کش
     * 
     * @param string $key کلید
     * @param callable $callback تابع تولید مقدار
     * @param int $ttl زمان زنده ماندن (به ثانیه)
     * @return mixed
     */
    public static function remember(string $key, callable $callback, int $ttl = 60)
    {
        if (self::has($key)) {
            return self::get($key);
        }
        
        $value = $callback();
        self::set($key, $value, $ttl);
        
        return $value;
    }
}
```

استفاده از کش در حافظه:

```php
// نمونه استفاده از کش برای نتایج پایگاه داده
$users = MemoryCache::remember('users_list', function() use ($db) {
    return $
```

نکته مهم: کش در حافظه فقط در طول عمر یک درخواست پایدار است و با پایان درخواست، داده‌های کش شده از بین می‌روند. این روش
برای کش کردن داده‌هایی که در یک درخواست چندین بار استفاده می‌شوند، مناسب است اما برای کش کردن بین درخواست‌ها مناسب نیست.

### 2. کش فایلی

کش فایلی داده‌ها را در فایل‌های مجزا ذخیره می‌کند و برای کش کردن بین درخواست‌ها مناسب است:

```php
/**
 * کلاس کش فایلی
 */
class FileCache
{
    /**
     * @var string مسیر دایرکتوری کش
     */
    private static string $cachePath;
    
    /**
     * تنظیم مسیر کش
     * 
     * @param string $path مسیر دایرکتوری کش
     * @return void
     */
    public static function setCachePath(string $path): void
    {
        self::$cachePath = rtrim($path, '/') . '/';
        
        // اطمینان از وجود دایرکتوری کش و قابل نوشتن بودن آن
        if (!is_dir(self::$cachePath)) {
            mkdir(self::$cachePath, 0755, true);
        }
        
        if (!is_writable(self::$cachePath)) {
            throw new \RuntimeException('Cache directory is not writable: ' . self::$cachePath);
        }
    }
    
    /**
     * دریافت مسیر فایل کش برای کلید
     * 
     * @param string $key کلید
     * @return string
     */
    private static function getCacheFile(string $key): string
    {
        if (empty(self::$cachePath)) {
            self::setCachePath(sys_get_temp_dir() . '/PHLask_cache');
        }
        
        // تبدیل کلید به نام فایل امن
        $filename = md5($key) . '.cache';
        
        return self::$cachePath . $filename;
    }
    
    /**
     * ذخیره داده در کش
     * 
     * @param string $key کلید
     * @param mixed $value مقدار
     * @param int $ttl زمان زنده ماندن (به ثانیه)
     * @return bool
     */
    public static function set(string $key, $value, int $ttl = 60): bool
    {
        $cacheFile = self::getCacheFile($key);
        
        $data = [
            'expires_at' => time() + $ttl,
            'value' => $value
        ];
        
        return file_put_contents($cacheFile, serialize($data)) !== false;
    }
    
    /**
     * دریافت داده از کش
     * 
     * @param string $key کلید
     * @param mixed $default مقدار پیش‌فرض در صورت عدم وجود یا منقضی شدن
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        if (!self::has($key)) {
            return $default;
        }
        
        $cacheFile = self::getCacheFile($key);
        $data = unserialize(file_get_contents($cacheFile));
        
        return $data['value'];
    }
    
    /**
     * بررسی وجود داده در کش
     * 
     * @param string $key کلید
     * @return bool
     */
    public static function has(string $key): bool
    {
        $cacheFile = self::getCacheFile($key);
        
        if (!file_exists($cacheFile)) {
            return false;
        }
        
        $data = unserialize(file_get_contents($cacheFile));
        
        // بررسی منقضی نشدن
        if ($data['expires_at'] < time()) {
            self::delete($key);
            return false;
        }
        
        return true;
    }
    
    /**
     * حذف داده از کش
     * 
     * @param string $key کلید
     * @return bool
     */
    public static function delete(string $key): bool
    {
        $cacheFile = self::getCacheFile($key);
        
        if (file_exists($cacheFile)) {
            return unlink($cacheFile);
        }
        
        return true;
    }
    
    /**
     * پاک کردن کل کش
     * 
     * @return bool
     */
    public static function clear(): bool
    {
        if (empty(self::$cachePath) || !is_dir(self::$cachePath)) {
            return false;
        }
        
        $files = glob(self::$cachePath . '*.cache');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        return true;
    }
    
    /**
     * دریافت یا ذخیره داده در کش
     * 
     * @param string $key کلید
     * @param callable $callback تابع تولید مقدار
     * @param int $ttl زمان زنده ماندن (به ثانیه)
     * @return mixed
     */
    public static function remember(string $key, callable $callback, int $ttl = 60)
    {
        if (self::has($key)) {
            return self::get($key);
        }
        
        $value = $callback();
        self::set($key, $value, $ttl);
        
        return $value;
    }
}
```

استفاده از کش فایلی:

```php
// تنظیم مسیر کش
FileCache::setCachePath(__DIR__ . '/cache');

// نمونه استفاده از کش فایلی
$articles = FileCache::remember('recent_articles', function() use ($db) {
    return $db->query('SELECT * FROM articles ORDER BY created_at DESC LIMIT 10')->fetchAll();
}, 3600); // کش برای 1 ساعت

// استفاده در مسیرهای API
$app->get('/api/articles', function(Request $request, Response $response) use ($db) {
    $articles = FileCache::remember('all_articles', function() use ($db) {
        return $db->query('SELECT * FROM articles')->fetchAll();
    }, 1800); // کش برای 30 دقیقه
    
    return $response->json($articles);
});
```

### 3. کش با Redis

Redis یک پایگاه داده در حافظه است که برای کش کردن بسیار مناسب است. سرعت بالا، امکان تنظیم زمان انقضا و پشتیبانی از انواع
مختلف داده از مزایای Redis است:

```php
/**
 * کلاس کش Redis
 */
class RedisCache
{
    /**
     * @var \Redis نمونه Redis
     */
    private static ?\Redis $redis = null;
    
    /**
     * @var string پیشوند کلیدهای کش
     */
    private static string $prefix = 'PHLask:';
    
    /**
     * اتصال به Redis
     * 
     * @param string $host میزبان
     * @param int $port پورت
     * @param string $password رمز عبور (اختیاری)
     * @param int $database شماره پایگاه داده
     * @return bool
     */
    public static function connect(string $host = '127.0.0.1', int $port = 6379, string $password = null, int $database = 0): bool
    {
        if (!extension_loaded('redis')) {
            throw new \RuntimeException('Redis extension is not installed');
        }
        
        self::$redis = new \Redis();
        
        if (!self::$redis->connect($host, $port)) {
            throw new \RuntimeException('Failed to connect to Redis server');
        }
        
        if ($password) {
            self::$redis->auth($password);
        }
        
        if ($database) {
            self::$redis->select($database);
        }
        
        return true;
    }
    
    /**
     * تنظیم پیشوند کلیدها
     * 
     * @param string $prefix پیشوند
     * @return void
     */
    public static function setPrefix(string $prefix): void
    {
        self::$prefix = $prefix;
    }
    
    /**
     * اطمینان از اتصال به Redis
     * 
     * @return void
     */
    private static function ensureConnection(): void
    {
        if (self::$redis === null) {
            self::connect();
        }
    }
    
    /**
     * ذخیره داده در کش
     * 
     * @param string $key کلید
     * @param mixed $value مقدار
     * @param int $ttl زمان زنده ماندن (به ثانیه)
     * @return bool
     */
    public static function set(string $key, $value, int $ttl = 60): bool
    {
        self::ensureConnection();
        
        $key = self::$prefix . $key;
        
        // تبدیل مقادیر غیر از رشته به JSON
        if (!is_string($value)) {
            $value = json_encode($value);
        }
        
        if ($ttl > 0) {
            return self::$redis->setex($key, $ttl, $value);
        } else {
            return self::$redis->set($key, $value);
        }
    }
    
    /**
     * دریافت داده از کش
     * 
     * @param string $key کلید
     * @param mixed $default مقدار پیش‌فرض در صورت عدم وجود
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        self::ensureConnection();
        
        $key = self::$prefix . $key;
        $value = self::$redis->get($key);
        
        if ($value === false) {
            return $default;
        }
        
        // تبدیل JSON به آرایه یا آبجکت
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }
        
        return $value;
    }
    
    /**
     * بررسی وجود داده در کش
     * 
     * @param string $key کلید
     * @return bool
     */
    public static function has(string $key): bool
    {
        self::ensureConnection();
        
        $key = self::$prefix . $key;
        return self::$redis->exists($key);
    }
    
    /**
     * حذف داده از کش
     * 
     * @param string $key کلید
     * @return bool
     */
    public static function delete(string $key): bool
    {
        self::ensureConnection();
        
        $key = self::$prefix . $key;
        return self::$redis->del($key) > 0;
    }
    
    /**
     * افزایش مقدار عددی
     * 
     * @param string $key کلید
     * @param int $amount مقدار افزایش
     * @return int
     */
    public static function increment(string $key, int $amount = 1): int
    {
        self::ensureConnection();
        
        $key = self::$prefix . $key;
        
        if ($amount === 1) {
            return self::$redis->incr($key);
        } else {
            return self::$redis->incrBy($key, $amount);
        }
    }
    
    /**
     * کاهش مقدار عددی
     * 
     * @param string $key کلید
     * @param int $amount مقدار کاهش
     * @return int
     */
    public static function decrement(string $key, int $amount = 1): int
    {
        self::ensureConnection();
        
        $key = self::$prefix . $key;
        
        if ($amount === 1) {
            return self::$redis->decr($key);
        } else {
            return self::$redis->decrBy($key, $amount);
        }
    }
    
    /**
     * پاک کردن همه داده‌های کش مربوط به این برنامه
     * 
     * @return bool
     */
    public static function clear(): bool
    {
        self::ensureConnection();
        
        $keys = self::$redis->keys(self::$prefix . '*');
        
        if (empty($keys)) {
            return true;
        }
        
        return self::$redis->del($keys) > 0;
    }
    
    /**
     * دریافت یا ذخیره داده در کش
     * 
     * @param string $key کلید
     * @param callable $callback تابع تولید مقدار
     * @param int $ttl زمان زنده ماندن (به ثانیه)
     * @return mixed
     */
    public static function remember(string $key, callable $callback, int $ttl = 60)
    {
        if (self::has($key)) {
            return self::get($key);
        }
        
        $value = $callback();
        self::set($key, $value, $ttl);
        
        return $value;
    }
}
```

استفاده از کش Redis:

```php
// اتصال به Redis
RedisCache::connect('127.0.0.1', 6379, null, 0);
RedisCache::setPrefix('myapp:');

// نمونه استفاده از کش Redis
$dashboard = RedisCache::remember('admin_dashboard_stats', function() use ($db) {
    // محاسبات پیچیده...
    return [
        'visits' => 15000,
        'users' => 5200,
        'orders' => 1250,
        'revenue' => 75000.50
    ];
}, 300); // کش برای 5 دقیقه

// کش کردن داده‌های کاربر
$userId = 123;
$userData = RedisCache::remember("user:{$userId}", function() use ($db, $userId) {
    return $db->query('SELECT * FROM users WHERE id = ?', [$userId])->fetch();
}, 600); // کش برای 10 دقیقه

// شمارش بازدیدها
RedisCache::increment('page_views');
$views = RedisCache::get('page_views', 0);
```

## کش کردن نتایج مسیرها (Route Caching)

کش کردن پاسخ کامل یک مسیر می‌تواند به افزایش قابل توجه کارایی برنامه منجر شود، به ویژه برای مسیرهایی که محاسبات سنگینی
دارند:

```php
/**
 * میان‌افزار کش مسیرها
 */
class RouteCacheMiddleware
{
    /**
     * @var string کلاس کش مورد استفاده
     */
    private string $cacheClass;
    
    /**
     * @var int زمان زنده ماندن پیش‌فرض
     */
    private int $defaultTtl;
    
    /**
     * @var array مسیرهایی که نباید کش شوند
     */
    private array $excludePaths;
    
    /**
     * سازنده کلاس
     * 
     * @param string $cacheClass کلاس کش مورد استفاده
     * @param int $defaultTtl زمان زنده ماندن پیش‌فرض
     * @param array $excludePaths مسیرهایی که نباید کش شوند
     */
    public function __construct(string $cacheClass = 'RedisCache', int $defaultTtl = 300, array $excludePaths = [])
    {
        $this->cacheClass = $cacheClass;
        $this->defaultTtl = $defaultTtl;
        $this->excludePaths = $excludePaths;
    }
    
    /**
     * اجرای میان‌افزار
     * 
     * @param Request $request درخواست
     * @param callable $next تابع بعدی
     * @return Response
     */
    public function __invoke(Request $request, callable $next): Response
    {
        // فقط درخواست‌های GET کش می‌شوند
        if ($request->getMethod() !== 'GET') {
            return $next($request);
        }
        
        // بررسی استثناها
        $path = $request->getUri()->getPath();
        foreach ($this->excludePaths as $excludePath) {
            if (str_starts_with($path, $excludePath) || $path === $excludePath) {
                return $next($request);
            }
        }
        
        // ساخت کلید کش براساس مسیر و پارامترهای کوئری
        $cacheKey = 'route:' . md5($path . json_encode($request->getQueryParams()));
        
        // بررسی وجود پاسخ در کش
        $cacheClass = $this->cacheClass;
        if ($cacheClass::has($cacheKey)) {
            $cachedData = $cacheClass::get($cacheKey);
            
            $response = new Response();
            $response->withStatus($cachedData['status']);
            
            foreach ($cachedData['headers'] as $name => $value) {
                $response = $response->withHeader($name, $value);
            }
            
            // افزودن هدر برای نشان دادن اینکه پاسخ از کش آمده است
            $response = $response->withHeader('X-Cache', 'HIT');
            
            // تنظیم بدنه پاسخ
            $body = new Stream(fopen('php://temp', 'r+'));
            $body->write($cachedData['body']);
            $body->rewind();
            
            return $response->withBody($body);
        }
        
        // اجرای درخواست اصلی
        $response = $next($request);
        
        // ذخیره پاسخ در کش
        $body = (string) $response->getBody();
        
        $cachedData = [
            'status' => $response->getStatusCode(),
            'headers' => $response->getHeaders(),
            'body' => $body
        ];
        
        $cacheClass::set($cacheKey, $cachedData, $this->defaultTtl);
        
        // افزودن هدر برای نشان دادن اینکه پاسخ از کش نیامده است
        return $response->withHeader('X-Cache', 'MISS');
    }
}
```

استفاده از میان‌افزار کش مسیرها:

```php
// تنظیم اتصال به Redis
RedisCache::connect('127.0.0.1', 6379);

// ایجاد نمونه میان‌افزار کش مسیرها
$routeCacheMiddleware = new RouteCacheMiddleware(
    'RedisCache', // کلاس کش مورد استفاده
    300, // زمان زنده ماندن پیش‌فرض (5 دقیقه)
    ['/admin', '/api/private', '/user/profile'] // مسیرهایی که نباید کش شوند
);

// افزودن میان‌افزار به برنامه
$app->middleware($routeCacheMiddleware);

// یا افزودن به مسیرهای خاص
$app->get('/api/products', function(Request $request, Response $response) use ($db) {
    $products = $db->query('SELECT * FROM products WHERE is_active = 1')->fetchAll();
    return $response->json($products);
})->middleware($routeCacheMiddleware);
```

## کش کردن کوئری‌های پایگاه داده

کش کردن نتایج کوئری‌های تکراری پایگاه داده می‌تواند بار قابل توجهی را از روی پایگاه داده بردارد:

```php
/**
 * کلاس اتصال پایگاه داده با قابلیت کش
 */
class CacheableConnection extends Connection
{
    /**
     * @var string کلاس کش مورد استفاده
     */
    private string $cacheClass;
    
    /**
     * @var bool فعال بودن کش
     */
    private bool $cacheEnabled = true;
    
    /**
     * @var int زمان زنده ماندن پیش‌فرض
     */
    private int $defaultTtl = 300; // 5 دقیقه
    
    /**
     * تنظیم کلاس کش
     * 
     * @param string $cacheClass نام کلاس کش
     * @return self
     */
    public function setCacheClass(string $cacheClass): self
    {
        $this->cacheClass = $cacheClass;
        return $this;
    }
    
    /**
     * فعال یا غیرفعال کردن کش
     * 
     * @param bool $enabled فعال بودن کش
     * @return self
     */
    public function setCacheEnabled(bool $enabled): self
    {
        $this->cacheEnabled = $enabled;
        return $this;
    }
    
    /**
     * تنظیم زمان زنده ماندن پیش‌فرض
     * 
     * @param int $ttl زمان زنده ماندن (به ثانیه)
     * @return self
     */
    public function setDefaultTtl(int $ttl): self
    {
        $this->defaultTtl = $ttl;
        return $this;
    }
    
    /**
     * دریافت همه نتایج
     * 
     * @param string $query کوئری SQL
     * @param array $params پارامترها
     * @param int|null $ttl زمان زنده ماندن (null برای استفاده از مقدار پیش‌فرض)
     * @return array
     * @throws DatabaseException
     */
    public function fetchAllCached(string $query, array $params = [], ?int $ttl = null): array
    {
        if (!$this->cacheEnabled || !isset($this->cacheClass)) {
            // اگر کش غیرفعال است یا کلاس کش تنظیم نشده، از متد اصلی استفاده می‌کنیم
            return parent::fetchAll($query, $params);
        }
        
        $ttl = $ttl ?? $this->defaultTtl;
        
        // ساخت کلید کش
        $cacheKey = 'db:' . md5($query . json_encode($params));
        
        // استفاده از کلاس کش تنظیم شده
        $cacheClass = $this->cacheClass;
        
        return $cacheClass::remember($cacheKey, function() use ($query, $params) {
            return parent::fetchAll($query, $params);
        }, $ttl);
    }
    
    /**
     * دریافت یک نتیجه
     * 
     * @param string $query کوئری SQL
     * @param array $params پارامترها
     * @param int|null $ttl زمان زنده ماندن (null برای استفاده از مقدار پیش‌فرض)
     * @return array|null
     * @throws DatabaseException
     */
    public function fetchOneCached(string $query, array $params = [], ?int $ttl = null): ?array
    {
        if (!$this->cacheEnabled || !isset($this->cacheClass)) {
            // اگر کش غیرفعال است یا کلاس کش تنظیم نشده، از متد اصلی استفاده می‌کنیم
            return parent::fetchOne($query, $params);
        }
        
        $ttl = $ttl ?? $this->defaultTtl;
        
        // ساخت کلید کش
        $cacheKey = 'db:' . md5($query . json_encode($params));
        
        // استفاده از کلاس کش تنظیم شده
        $cacheClass = $this->cacheClass;
        
        return $cacheClass::remember($cacheKey, function() use ($query, $params) {
            return parent::fetchOne($query, $params);
        }, $ttl);
    }
    
    /**
     * پاک کردن کش پایگاه داده
     * 
     * @param string|null $pattern الگوی کلیدهای کش برای پاک کردن
     * @return bool
     */
    public function clearCache(?string $pattern = null): bool
    {
        if (!isset($this->cacheClass)) {
            return false;
        }
        
        $cacheClass = $this->cacheClass;
        
        if ($pattern === null) {
            // پاک کردن همه کش‌های پایگاه داده
            if (method_exists($cacheClass, 'clear')) {
                return $cacheClass::clear();
            }
            return false;
        }
        
        // پاک کردن کش‌های مطابق با الگو
        // این قسمت بستگی به پیاده‌سازی کلاس کش دارد
        // برای Redis می‌توان از KEYS و DEL استفاده کرد
        
        return true;
    }
}
```

استفاده از اتصال پایگاه داده با قابلیت کش:

```php
// اتصال به Redis
RedisCache::connect('127.0.0.1', 6379);

// ایجاد اتصال پایگاه داده با قابلیت کش
$db = new CacheableConnection($dbConfig);
$db->setCacheClass('RedisCache')
   ->setCacheEnabled(true)
   ->setDefaultTtl(600); // 10 دقیقه

// استفاده از کش برای کوئری‌ها
$users = $db->fetchAllCached('SELECT * FROM users WHERE status = ?', ['active'], 300);

$product = $db->fetchOneCached('SELECT * FROM products WHERE id = ?', [123], 3600);

// پاک کردن کش پایگاه داده بعد از به‌روزرسانی یا حذف
$db->update('products', ['name' => 'New Name'], 'id = :id', [':id' => 123]);
$db->clearCache(); // پاک کردن همه کش‌های پایگاه داده
```

## نکات و توصیه‌های کش کردن

### 1. انتخاب استراتژی مناسب

- **کش در حافظه**: برای داده‌هایی که در یک درخواست چندین بار استفاده می‌شوند.
- **کش فایلی**: برای برنامه‌های کوچک که نیاز به نصب Redis یا Memcached ندارند.
- **Redis/Memcached**: برای برنامه‌های بزرگ با نیاز به عملکرد بالا و مقیاس‌پذیری.

### 2. مدیریت کلیدهای کش

- از پیشوندها برای گروه‌بندی کلیدها استفاده کنید (مثلا `user:123`, `product:456`).
- کلیدهای کش را بر اساس داده‌های ورودی به درستی بسازید.
- کلیدهای پیچیده را با md5 یا sha1 هش کنید.

### 3. زمان زنده ماندن (TTL)

- TTL را براساس نوع داده و تناوب تغییر آن تنظیم کنید.
- داده‌های کمتر تغییریابنده TTL طولانی‌تری داشته باشند.
- برای محتوای پویا از TTL کوتاه‌تر استفاده کنید.

### 4. پاک کردن هوشمند کش

پس از تغییر داده‌ها، کش مربوط به آن‌ها را به صورت هدفمند پاک کنید:

```php
// کلاس مدیریت کش
class CacheManager
{
    // ... سایر متدها

    /**
     * پاک کردن کش‌های مرتبط با کاربر
     * 
     * @param int $userId شناسه کاربر
     * @return void
     */
    public static function invalidateUserCache(int $userId): void
    {
        $cacheKeys = [
            "user:{$userId}",
            "user:{$userId}:profile",
            "user:{$userId}:permissions"
        ];
        
        foreach ($cacheKeys as $key) {
            RedisCache::delete($key);
        }
        
        // پاک کردن کش‌های لیست کاربران
        RedisCache::delete('users_list');
        RedisCache::delete('active_users');
    }
    
    /**
     * پاک کردن کش‌های مرتبط با محصول
     * 
     * @param int $productId شناسه محصول
     * @return void
     */
    public static function invalidateProductCache(int $productId): void
    {
        $cacheKeys = [
            "product:{$productId}",
            "product:{$productId}:details",
            "product:{$productId}:images"
        ];
        
        foreach ($cacheKeys as $key) {
            RedisCache::delete($key);
        }
        
        // پاک کردن کش‌های لیست محصولات
        RedisCache::delete('products_list');
        RedisCache::delete('featured_products');
    }
}

// نمونه استفاده
$app->put('/api/users/{id}', function(Request $request, Response $response) use ($db) {
    $userId = $request->param('id');
    $data = $request->getParsedBody();
    
    // به‌روزرسانی اطلاعات کاربر
    $db->update('users', $data, 'id = :id', [':id' => $userId]);
    
    // پاک کردن کش‌های مرتبط
    CacheManager::invalidateUserCache($userId);
    
    return $response->json(['message' => 'اطلاعات کاربر با موفقیت به‌روزرسانی شد']);
});
```

### 5. کش کردن محتوای فراگیر (Cache Stampede)

در سیستم‌های با بار بالا، ممکن است وقتی یک کش منقضی می‌شود، چندین درخواست همزمان برای بازسازی آن ارسال شود (Cache
Stampede). برای جلوگیری از این مشکل:

```php
/**
 * کش کردن با زمان تمدید اتوماتیک
 * 
 * @param string $key کلید
 * @param callable $callback تابع تولید مقدار
 * @param int $ttl زمان زنده ماندن اصلی
 * @param int $beta ضریب تصادفی
 * @return mixed
 */
public static function rememberWithJitter(string $key, callable $callback, int $ttl = 60, int $beta = 10): mixed
{
    // بررسی وجود مقدار در کش
    if (self::has($key)) {
        $remainingTtl = self::getRemainingTtl($key);
        
        // اگر زمان باقی‌مانده کمتر از درصدی از TTL اصلی باشد، با احتمال مشخصی کش را بازسازی می‌کنیم
        if ($remainingTtl < $ttl * 0.2) {
            // احتمال بازسازی زودهنگام
            $probability = 1 - $remainingTtl / ($ttl * 0.2);
            
            if (mt_rand(1, 100) <= $probability * 100) {
                // بازسازی زودهنگام با زمان تصادفی
                $jitter = mt_rand(-$beta, $beta);
                $newTtl = $ttl + $jitter;
                
                $value = $callback();
                self::set($key, $value, $newTtl);
                
                return $value;
            }
        }
        
        // استفاده از مقدار موجود
        return self::get($key);
    }
    
    // ایجاد مقدار جدید
    $value = $callback();
    
    // افزودن jitter به TTL
    $jitter = mt_rand(-$beta, $beta);
    $newTtl = max(1, $ttl + $jitter);
    
    self::set($key, $value, $newTtl);
    
    return $value;
}
```

### 6. کش کردن چند سطحی (Multi-Level Caching)

برای بهینه‌سازی بیشتر، می‌توانید از کش چند سطحی استفاده کنید:

```php
/**
 * کلاس کش چند سطحی
 */
class MultiLevelCache
{
    /**
     * دریافت داده از کش چند سطحی
     * 
     * @param string $key کلید
     * @param callable $callback تابع تولید مقدار
     * @param int $ttl زمان زنده ماندن
     * @return mixed
     */
    public static function remember(string $key, callable $callback, int $ttl = 60)
    {
        // سطح 1: کش در حافظه (سریع‌ترین)
        if (MemoryCache::has($key)) {
            return MemoryCache::get($key);
        }
        
        // سطح 2: کش Redis (سریع)
        if (RedisCache::has($key)) {
            $value = RedisCache::get($key);
            // ذخیره در کش حافظه برای دسترسی‌های بعدی
            MemoryCache::set($key, $value, 60); // کش در حافظه برای 1 دقیقه
            return $value;
        }
        
        // سطح 3: کش فایلی (کندتر، اما پایدارتر)
        if (FileCache::has($key)) {
            $value = FileCache::get($key);
            // ذخیره در کش‌های سریع‌تر برای دسترسی‌های بعدی
            RedisCache::set($key, $value, $ttl);
            MemoryCache::set($key, $value, 60);
            return $value;
        }
        
        // محاسبه مقدار جدید
        $value = $callback();
        
        // ذخیره در تمام سطوح کش
        FileCache::set($key, $value, $ttl * 2); // زمان طولانی‌تر برای کش فایلی
        RedisCache::set($key, $value, $ttl);
        MemoryCache::set($key, $value, 60);
        
        return $value;
    }
    
    /**
     * پاک کردن کش در تمام سطوح
     * 
     * @param string $key کلید
     * @return bool
     */
    public static function delete(string $key): bool
    {
        $result = true;
        
        $result = $result && MemoryCache::delete($key);
        $result = $result && RedisCache::delete($key);
        $result = $result && FileCache::delete($key);
        
        return $result;
    }
}
```

## کش کردن HTML جزئی (Fragment Caching)

گاهی اوقات کش کردن کل صفحه مناسب نیست، زیرا برخی بخش‌های صفحه شخصی‌سازی شده هستند. در این موارد، کش کردن بخش‌های قابل کش
صفحه (Fragment Caching) می‌تواند مفید باشد:

```php
/**
 * کش کردن بخشی از خروجی
 * 
 * @param string $key کلید کش
 * @param callable $callback تابع تولید محتوا
 * @param int $ttl زمان زنده ماندن
 * @return string
 */
function cacheFragment(string $key, callable $callback, int $ttl = 300): string
{
    return RedisCache::remember($key, $callback, $ttl);
}
```

نمونه استفاده در قالب:

```php
<!-- سربرگ صفحه -->
<header>
    <h1>عنوان سایت</h1>
    <nav>
        <?php echo cacheFragment('main_menu', function() {
            // تولید منوی اصلی که برای همه کاربران یکسان است
            return generateMainMenu();
        }, 3600); ?>
    </nav>
</header>

<!-- محتوای اصلی -->
<main>
    <!-- بخش سفارشی برای هر کاربر - کش نمی‌شود -->
    <div class="user-info">
        <?php echo getUserInfo($currentUser); ?>
    </div>
    
    <!-- بخش محصولات پربازدید - برای همه یکسان است و کش می‌شود -->
    <div class="popular-products">
        <h2>محصولات پربازدید</h2>
        <?php echo cacheFragment('popular_products', function() {
            return getPopularProducts();
        }, 1800); ?>
    </div>
    
    <!-- بخش مقالات اخیر - برای همه یکسان است و کش می‌شود -->
    <div class="recent-articles">
        <h2>مقالات اخیر</h2>
        <?php echo cacheFragment('recent_articles', function() {
            return getRecentArticles();
        }, 900); ?>
    </div>
</main>

<!-- پاورقی صفحه -->
<footer>
    <?php echo cacheFragment('footer_content', function() {
        // محتوای پاورقی که برای همه کاربران یکسان است
        return generateFooter();
    }, 86400); // کش برای 1 روز ?>
</footer>
```

## کش کردن کوئری‌های API خارجی

برای API‌های خارجی که فراخوانی آن‌ها زمان‌بر است:

```php
/**
 * کلاس مشتری API با قابلیت کش
 */
class CacheableApiClient
{
    /**
     * @var string آدرس پایه API
     */
    private string $baseUrl;
    
    /**
     * @var string کلید API
     */
    private string $apiKey;
    
    /**
     * @var string کلاس کش مورد استفاده
     */
    private string $cacheClass;
    
    /**
     * @var int زمان زنده ماندن پیش‌فرض
     */
    private int $defaultTtl = 300;
    
    /**
     * سازنده کلاس
     * 
     * @param string $baseUrl آدرس پایه API
     * @param string $apiKey کلید API
     * @param string $cacheClass کلاس کش
     */
    public function __construct(string $baseUrl, string $apiKey, string $cacheClass = 'RedisCache')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
        $this->cacheClass = $cacheClass;
    }
    
    /**
     * ارسال درخواست GET به API با قابلیت کش
     * 
     * @param string $endpoint نقطه پایانی
     * @param array $params پارامترهای درخواست
     * @param int|null $ttl زمان زنده ماندن (null برای استفاده از مقدار پیش‌فرض)
     * @return array
     */
    public function get(string $endpoint, array $params = [], ?int $ttl = null): array
    {
        $ttl = $ttl ?? $this->defaultTtl;
        
        // ساخت URL با پارامترها
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        // ساخت کلید کش
        $cacheKey = 'api:' . md5($url);
        
        // استفاده از کلاس کش
        $cacheClass = $this->cacheClass;
        
        return $cacheClass::remember($cacheKey, function() use ($url) {
            // انجام درخواست HTTP
            $curl = curl_init();
            
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $this->apiKey,
                    'Accept: application/json'
                ]
            ]);
            
            $response = curl_exec($curl);
            $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            
            curl_close($curl);
            
            if ($statusCode >= 400) {
                throw new \RuntimeException('خطا در درخواست API: ' . $statusCode);
            }
            
            return json_decode($response, true);
        }, $ttl);
    }
    
    /**
     * ارسال درخواست POST به API (بدون کش)
     * 
     * @param string $endpoint نقطه پایانی
     * @param array $data داده‌های درخواست
     * @return array
     */
    public function post(string $endpoint, array $data = []): array
    {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
                'Accept: application/json'
            ]
        ]);
        
        $response = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        curl_close($curl);
        
        if ($statusCode >= 400) {
            throw new \RuntimeException('خطا در درخواست API: ' . $statusCode);
        }
        
        // پاک کردن کش‌های مرتبط پس از عملیات POST
        $cacheClass = $this->cacheClass;
        
        // می‌توان کلیدهای کش مرتبط را پاک کرد
        // $cacheClass::delete('api:' . md5($this->baseUrl . '/' . $relatedEndpoint));
        
        return json_decode($response, true);
    }
}
```

نمونه استفاده:

```php
// ایجاد مشتری API با قابلیت کش
$weatherApi = new CacheableApiClient(
    'https://api.example.com/weather',
    'your-api-key',
    'RedisCache'
);

// درخواست با کش
$forecast = $weatherApi->get('/forecast', [
    'city' => 'Tehran',
    'days' => 5
], 1800); // کش برای 30 دقیقه

// درخواست بدون کش (برای داده‌های حساس به زمان)
$currentWeather = $weatherApi->get('/current', [
    'city' => 'Tehran'
], 60); // کش فقط برای 1 دقیقه
```

## افزودن یک سیستم کش جامع به برنامه

برای پیاده‌سازی یک سیستم کش جامع در برنامه، می‌توان از یک سرویس کش استفاده کرد:

```php
/**
 * کلاس سرویس کش
 */
class CacheService
{
    /**
     * @var string نوع درایور کش
     */
    private string $driver;
    
    /**
     * @var array تنظیمات کش
     */
    private array $config;
    
    /**
     * @var array نمونه‌های درایورهای کش
     */
    private static array $instances = [];
    
    /**
     * سازنده کلاس
     * 
     * @param string $driver نوع درایور (redis, file, memory)
     * @param array $config تنظیمات
     */
    public function __construct(string $driver = 'memory', array $config = [])
    {
        $this->driver = $driver;
        $this->config = $config;
        
        $this->initialize();
    }
    
    /**
     * مقداردهی اولیه درایور کش
     * 
     * @return void
     */
    private function initialize(): void
    {
        switch ($this->driver) {
            case 'redis':
                RedisCache::connect(
                    $this->config['host'] ?? '127.0.0.1',
                    $this->config['port'] ?? 6379,
                    $this->config['password'] ?? null,
                    $this->config['database'] ?? 0
                );
                
                if (isset($this->config['prefix'])) {
                    RedisCache::setPrefix($this->config['prefix']);
                }
                break;
                
            case 'file':
                if (isset($this->config['path'])) {
                    FileCache::setCachePath($this->config['path']);
                }
                break;
                
            case 'memory':
                // نیازی به مقداردهی اولیه نیست
                break;
                
            default:
                throw new \InvalidArgumentException("درایور کش نامعتبر: {$this->driver}");
        }
    }
    
    /**
     * دریافت نمونه سرویس
     * 
     * @param string $name نام سرویس
     * @return CacheService
     */
    public static function instance(string $name = 'default'): CacheService
    {
        if (!isset(self::$instances[$name])) {
            // تنظیمات پیش‌فرض
            self::$instances[$name] = new self();
        }
        
        return self::$instances[$name];
    }
    
    /**
     * تنظیم نمونه سرویس
     * 
     * @param string $name نام سرویس
     * @param CacheService $service نمونه سرویس
     * @return void
     */
    public static function setInstance(string $name, CacheService $service): void
    {
        self::$instances[$name] = $service;
    }
    
    /**
     * ذخیره داده در کش
     * 
     * @param string $key کلید
     * @param mixed $value مقدار
     * @param int $ttl زمان زنده ماندن
     * @return bool
     */
    public function set(string $key, $value, int $ttl = 60): bool
    {
        switch ($this->driver) {
            case 'redis':
                return RedisCache::set($key, $value, $ttl);
            
            case 'file':
                return FileCache::set($key, $value, $ttl);
            
            case 'memory':
                return MemoryCache::set($key, $value, $ttl);
            
            default:
                return false;
        }
    }
    
    /**
     * دریافت داده از کش
     * 
     * @param string $key کلید
     * @param mixed $default مقدار پیش‌فرض
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        switch ($this->driver) {
            case 'redis':
                return RedisCache::get($key, $default);
            
            case 'file':
                return FileCache::get($key, $default);
            
            case 'memory':
                return MemoryCache::get($key, $default);
            
            default:
                return $default;
        }
    }
    
    /**
     * بررسی وجود داده در کش
     * 
     * @param string $key کلید
     * @return bool
     */
    public function has(string $key): bool
    {
        switch ($this->driver) {
            case 'redis':
                return RedisCache::has($key);
            
            case 'file':
                return FileCache::has($key);
            
            case 'memory':
                return MemoryCache::has($key);
            
            default:
                return false;
        }
    }
    
    /**
     * حذف داده از کش
     * 
     * @param string $key کلید
     * @return bool
     */
    public function delete(string $key): bool
    {
        switch ($this->driver) {
            case 'redis':
                return RedisCache::delete($key);
            
            case 'file':
                return FileCache::delete($key);
            
            case 'memory':
                return MemoryCache::delete($key);
            
            default:
                return false;
        }
    }
    
    /**
     * پاک کردن کل کش
     * 
     * @return bool
     */
    public function clear(): bool
    {
        switch ($this->driver) {
            case 'redis':
                return RedisCache::clear();
            
            case 'file':
                return FileCache::clear();
            
            case 'memory':
                return MemoryCache::clear();
            
            default:
                return false;
        }
    }
    
    /**
     * دریافت یا ذخیره داده در کش
     * 
     * @param string $key کلید
     * @param callable $callback تابع تولید مقدار
     * @param int $ttl زمان زنده ماندن
     * @return mixed
     */
    public function remember(string $key, callable $callback, int $ttl = 60)
    {
        if ($this->has($key)) {
            return $this->get($key);
        }
        
        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }
}
```

نمونه استفاده از سرویس کش در برنامه:

```php
// تنظیم سرویس‌های کش
CacheService::setInstance('default', new CacheService('redis', [
    'host' => '127.0.0.1',
    'port' => 6379,
    'prefix' => 'app:'
]));

CacheService::setInstance('file', new CacheService('file', [
    'path' => __DIR__ . '/cache'
]));

CacheService::setInstance('memory', new CacheService('memory'));

// دریافت سرویس کش پیش‌فرض
$cache = CacheService::instance();

// ذخیره داده در کش
$cache->set('settings', $appSettings, 3600);

// دریافت داده از کش
$settings = $cache->get('settings');

// استفاده از remember
$products = $cache->remember('latest_products', function() use ($db) {
    return $db->query('SELECT * FROM products ORDER BY created_at DESC LIMIT 10')->fetchAll();
}, 300);

// استفاده از سرویس کش فایلی
$fileCache = CacheService::instance('file');
$fileCache->set('large_data', $largeData, 86400);

// استفاده از سرویس کش در حافظه
$memoryCache = CacheService::instance('memory');
$memoryCache->set('temp_data', $tempData, 60);
```

## جمع‌بندی

کش کردن یکی از مهم‌ترین تکنیک‌های بهینه‌سازی برای افزایش کارایی برنامه‌های وب است. در فلسک‌پی‌اچ‌پی، روش‌های مختلفی برای
کش کردن وجود دارد:

1. **کش در حافظه**: برای داده‌هایی که در یک درخواست چندین بار استفاده می‌شوند.
2. **کش فایلی**: برای ذخیره داده‌ها بین درخواست‌ها در فایل‌های مجزا.
3. **کش با Redis**: برای کش کردن مقیاس‌پذیر و با کارایی بالا.
4. **کش کردن مسیرها**: برای ذخیره پاسخ کامل یک مسیر.
5. **کش کردن کوئری‌های پایگاه داده**: برای کاهش بار روی پایگاه داده.
6. **کش کردن جزئی**: برای کش کردن بخش‌های خاصی از خروجی.
7. **کش کردن API‌های خارجی**: برای کاهش درخواست‌های API.

با استفاده از تکنیک‌های کش کردن مناسب، می‌توانید سرعت و مقیاس‌پذیری برنامه خود را به میزان قابل توجهی افزایش دهید.# کش
کردن (Caching)

کش کردن یک تکنیک بهینه‌سازی است که با ذخیره موقت داده‌ها، محاسبات گران‌قیمت یا نتایج درخواست‌های مکرر، میزان بار روی
سرور را کاهش داده و سرعت پاسخگویی را افزایش می‌دهد. در این بخش، با روش‌های مختلف کش کردن در فلسک‌پی‌اچ‌پی آشنا می‌شوید.

## اهمیت کش کردن

کش کردن می‌تواند مزایای زیادی داشته باشد:

1. **افزایش سرعت**: کاهش زمان پاسخگویی به درخواست‌ها
2. **کاهش بار سرور**: کاهش تعداد عملیات پردازشی و درخواست‌های پایگاه داده
3. **افزایش مقیاس‌پذیری**: امکان پاسخگویی به تعداد بیشتری از کاربران با منابع یکسان
4. **کاهش هزینه‌ها**: استفاده بهینه از منابع سرور

## انواع کش کردن

فلسک‌پی‌اچ‌پی از چندین روش کش کردن پشتیبانی می‌کند:

### 1. کش در حافظه

برای داده‌هایی که مکرراً استفاده می‌شوند اما حجم زیادی ندارند، می‌توان از کش در حافظه استفاده کرد. این روش ساده‌ترین و
سریع‌ترین روش کش کردن است:

```php
/**
 * کلاس کش در حافظه
 */
class MemoryCache
{
    /**
     * @var array داده‌های کش شده
     */
    private static array $cache = [];
    
    /**
     * @var array زمان منقضی شدن آیتم‌های کش
     */
    private static array $expiration = [];
    
    /**
     * ذخیره داده در کش
     * 
     * @param string $key کلید
     * @param mixed $value مقدار
     * @param int $ttl زمان زنده ماندن (به ثانیه)
     * @return bool
     */
    public static function set(string $key, $value, int $ttl = 60): bool
    {
        self::$cache[$key] = $value;
        self::$expiration[$key] = time() + $ttl;
        
        return true;
    }
    
    /**
     * دریافت داده از کش
     * 
     * @param string $key کلید
     * @param mixed $default مقدار پیش‌فرض در صورت عدم وجود یا منقضی شدن
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        if (!self::has($key)) {
            return $db->query('SELECT id, name, email FROM users')->fetchAll();
}, 300); // کش برای 5 دقیقه

// کش کردن یک محاسبه سنگین
$stats = MemoryCache::remember('user_stats', function() use ($db) {
    // انجام محاسبات پیچیده...
    sleep(2); // شبیه‌سازی یک عملیات زمان‌بر
    return [
        'total_users' => 1250,
        'active_users' => 875,
        'new_today' => 25
    ];
}, 600); // کش برای 10 دقیقهdefault;
        }
        
        return self::$cache[$key];
    }
    
    /**
     * بررسی وجود داده در کش
     * 
     * @param string $key کلید
     * @return bool
     */
    public static function has(string $key): bool
    {
        if (!isset(self::$cache[$key]) || !isset(self::$expiration[$key])) {
            return false;
        }
        
        // بررسی منقضی نشدن
        if (self::$expiration[$key] < time()) {
            self::delete($key);
            return false;
        }
        
        return true;
    }
    
    /**
     * حذف داده از کش
     * 
     * @param string $key کلید
     * @return bool
     */
    public static function delete(string $key): bool
    {
        unset(self::$cache[$key]);
        unset(self::$expiration[$key]);
        
        return true;
    }
    
    /**
     * پاک کردن کل کش
     * 
     * @return bool
     */
    public static function clear(): bool
    {
        self::$cache = [];
        self::$expiration = [];
        
        return true;
    }
    
    /**
     * دریافت یا ذخیره داده در کش
     * 
     * @param string $key کلید
     * @param callable $callback تابع تولید مقدار
     * @param int $ttl زمان زنده ماندن (به ثانیه)
     * @return mixed
     */
    public static function remember(string $key, callable $callback, int $ttl = 60)
    {
        if (self::has($key)) {
            return self::get($key);
        }
        
        $value = $callback();
        self::set($key, $value, $ttl);
        
        return $value;
    }
}
```

استفاده از کش در حافظه:

```php
// نمونه استفاده از کش برای نتایج پایگاه داده
$users = MemoryCache::remember('users_list', function() use ($db) {
    return $