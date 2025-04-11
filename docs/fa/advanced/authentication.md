# احراز هویت (Authentication)

احراز هویت یکی از مهم‌ترین بخش‌های هر برنامه وب است که به شما امکان می‌دهد هویت کاربران را تأیید کنید و دسترسی‌های مناسب را به آن‌ها ارائه دهید. در این بخش با روش‌های مختلف پیاده‌سازی احراز هویت در فلسک‌پی‌اچ‌پی آشنا می‌شوید.

## مفاهیم اولیه احراز هویت

احراز هویت فرآیندی است که طی آن هویت یک کاربر تأیید می‌شود. این فرآیند معمولاً شامل چند مرحله است:

1. **ثبت‌نام (Registration)**: کاربر اطلاعات خود را ارائه می‌دهد و یک حساب کاربری ایجاد می‌کند.
2. **ورود (Login)**: کاربر با استفاده از اطلاعات ارائه شده در مرحله ثبت‌نام (معمولاً نام کاربری/ایمیل و رمز عبور) وارد سیستم می‌شود.
3. **احراز هویت مستمر (Persistent Authentication)**: سیستم باید بتواند کاربر را در جلسات بعدی بدون نیاز به ورود مجدد شناسایی کند.
4. **خروج (Logout)**: کاربر از سیستم خارج می‌شود و جلسه او پایان می‌یابد.

## پیاده‌سازی احراز هویت در فلسک‌پی‌اچ‌پی

فلسک‌پی‌اچ‌پی روش‌های مختلفی برای پیاده‌سازی احراز هویت ارائه می‌دهد:

### 1. احراز هویت مبتنی بر جلسه (Session-based Authentication)

این روش شامل ذخیره اطلاعات کاربر در جلسه (session) سمت سرور است:

```php
// کلاس AuthService برای مدیریت احراز هویت مبتنی بر جلسه
class SessionAuthService
{
    private $connection;
    
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        
        // اطمینان از شروع جلسه
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * ثبت‌نام کاربر جدید
     * 
     * @param string $name نام
     * @param string $email ایمیل
     * @param string $password رمز عبور
     * @return int شناسه کاربر جدید
     * @throws \Exception در صورت بروز خطا
     */
    public function register(string $name, string $email, string $password): int
    {
        // بررسی تکراری نبودن ایمیل
        $user = $this->connection->fetchOne(
            'SELECT id FROM users WHERE email = :email',
            [':email' => $email]
        );
        
        if ($user) {
            throw new \Exception('ایمیل قبلا ثبت شده است');
        }
        
        // هش کردن رمز عبور
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // درج کاربر جدید
        return $this->connection->insert('users', [
            'name' => $name,
            'email' => $email,
            'password' => $hashedPassword,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * ورود کاربر
     * 
     * @param string $email ایمیل
     * @param string $password رمز عبور
     * @return bool نتیجه ورود
     */
    public function login(string $email, string $password): bool
    {
        // یافتن کاربر
        $user = $this->connection->fetchOne(
            'SELECT id, name, email, password FROM users WHERE email = :email',
            [':email' => $email]
        );
        
        if (!$user) {
            return false;
        }
        
        // بررسی رمز عبور
        if (!password_verify($password, $user['password'])) {
            return false;
        }
        
        // حذف رمز عبور از اطلاعات کاربر
        unset($user['password']);
        
        // ذخیره اطلاعات کاربر در جلسه
        $_SESSION['user'] = $user;
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        
        return true;
    }
    
    /**
     * بررسی ورود کاربر
     * 
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    /**
     * دریافت اطلاعات کاربر جاری
     * 
     * @return array|null
     */
    public function getCurrentUser(): ?array
    {
        return $this->isLoggedIn() ? $_SESSION['user'] : null;
    }
    
    /**
     * خروج کاربر
     * 
     * @return void
     */
    public function logout(): void
    {
        // حذف اطلاعات کاربر از جلسه
        unset($_SESSION['user']);
        unset($_SESSION['logged_in']);
        
        // نابودی کامل جلسه (اختیاری)
        session_destroy();
    }
}
```

استفاده از سرویس احراز هویت مبتنی بر جلسه در مسیرها:

```php
$app = App::getInstance();

// ایجاد نمونه سرویس احراز هویت
$authService = new SessionAuthService(Connection::connection());

// مسیر ثبت‌نام
$app->post('/register', function(Request $request, Response $response) use ($authService) {
    $data = $request->all();
    
    // اعتبارسنجی داده‌ها
    if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
        return $response->status(400)->json([
            'error' => 'همه فیلدها الزامی هستند'
        ]);
    }
    
    try {
        $userId = $authService->register($data['name'], $data['email'], $data['password']);
        
        return $response->status(201)->json([
            'message' => 'ثبت‌نام با موفقیت انجام شد',
            'user_id' => $userId
        ]);
    } catch (\Exception $e) {
        return $response->status(400)->json([
            'error' => $e->getMessage()
        ]);
    }
});

// مسیر ورود
$app->post('/login', function(Request $request, Response $response) use ($authService) {
    $data = $request->all();
    
    // اعتبارسنجی داده‌ها
    if (empty($data['email']) || empty($data['password'])) {
        return $response->status(400)->json([
            'error' => 'ایمیل و رمز عبور الزامی هستند'
        ]);
    }
    
    if ($authService->login($data['email'], $data['password'])) {
        return $response->json([
            'message' => 'ورود موفقیت‌آمیز',
            'user' => $authService->getCurrentUser()
        ]);
    } else {
        return $response->status(401)->json([
            'error' => 'ایمیل یا رمز عبور نادرست است'
        ]);
    }
});

// مسیر پروفایل (نیازمند احراز هویت)
$app->get('/profile', function(Request $request, Response $response) use ($authService) {
    if (!$authService->isLoggedIn()) {
        return $response->status(401)->json([
            'error' => 'برای دسترسی به این بخش باید وارد شوید'
        ]);
    }
    
    $user = $authService->getCurrentUser();
    
    return $response->json($user);
});

// مسیر خروج
$app->post('/logout', function(Request $request, Response $response) use ($authService) {
    $authService->logout();
    
    return $response->json([
        'message' => 'خروج با موفقیت انجام شد'
    ]);
});
```

### 2. احراز هویت مبتنی بر توکن (Token-based Authentication)

این روش شامل تولید یک توکن منحصر به فرد برای هر کاربر پس از ورود است. کاربر این توکن را در هر درخواست ارسال می‌کند:

```php
// کلاس AuthService برای مدیریت احراز هویت مبتنی بر توکن
class TokenAuthService
{
    private $connection;
    
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }
    
    /**
     * ثبت‌نام کاربر جدید
     */
    public function register(string $name, string $email, string $password): int
    {
        // بررسی تکراری نبودن ایمیل
        $user = $this->connection->fetchOne(
            'SELECT id FROM users WHERE email = :email',
            [':email' => $email]
        );
        
        if ($user) {
            throw new \Exception('ایمیل قبلا ثبت شده است');
        }
        
        // هش کردن رمز عبور
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // درج کاربر جدید
        return $this->connection->insert('users', [
            'name' => $name,
            'email' => $email,
            'password' => $hashedPassword,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * تولید توکن برای کاربر
     */
    public function generateToken(int $userId): string
    {
        // تولید توکن منحصر به فرد
        $token = bin2hex(random_bytes(32));
        
        // ذخیره توکن در پایگاه داده
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 ساعت اعتبار
        
        $this->connection->delete(
            'user_tokens',
            'user_id = :user_id',
            [':user_id' => $userId]
        );
        
        $this->connection->insert('user_tokens', [
            'user_id' => $userId,
            'token' => $token,
            'expires_at' => $expiresAt,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        return $token;
    }
    
    /**
     * ورود کاربر و تولید توکن
     */
    public function login(string $email, string $password): ?array
    {
        // یافتن کاربر
        $user = $this->connection->fetchOne(
            'SELECT id, name, email, password FROM users WHERE email = :email',
            [':email' => $email]
        );
        
        if (!$user) {
            return null;
        }
        
        // بررسی رمز عبور
        if (!password_verify($password, $user['password'])) {
            return null;
        }
        
        // حذف رمز عبور از اطلاعات کاربر
        unset($user['password']);
        
        // تولید توکن
        $token = $this->generateToken($user['id']);
        
        return [
            'user' => $user,
            'token' => $token,
            'expires_at' => date('Y-m-d H:i:s', time() + 3600)
        ];
    }
    
    /**
     * احراز هویت با توکن
     */
    public function authenticateWithToken(string $token): ?array
    {
        // یافتن توکن معتبر
        $tokenData = $this->connection->fetchOne(
            'SELECT user_id, expires_at FROM user_tokens WHERE token = :token',
            [':token' => $token]
        );
        
        if (!$tokenData) {
            return null;
        }
        
        // بررسی اعتبار زمانی توکن
        if (strtotime($tokenData['expires_at']) < time()) {
            return null;
        }
        
        // یافتن اطلاعات کاربر
        $user = $this->connection->fetchOne(
            'SELECT id, name, email FROM users WHERE id = :id',
            [':id' => $tokenData['user_id']]
        );
        
        return $user;
    }
    
    /**
     * ابطال توکن (خروج)
     */
    public function invalidateToken(string $token): bool
    {
        return $this->connection->delete(
            'user_tokens',
            'token = :token',
            [':token' => $token]
        ) > 0;
    }
}
```

استفاده از سرویس احراز هویت مبتنی بر توکن در مسیرها:

```php
$app = App::getInstance();

// ایجاد نمونه سرویس احراز هویت
$authService = new TokenAuthService(Connection::connection());

// میان‌افزار احراز هویت برای مسیرهای محافظت شده
$authMiddleware = function(Request $request, callable $next) use ($authService) {
    // دریافت توکن از هدر Authorization
    $token = $request->getHeaderLine('Authorization');
    
    // حذف پیشوند "Bearer " از توکن
    if (strpos($token, 'Bearer ') === 0) {
        $token = substr($token, 7);
    }
    
    if (empty($token)) {
        return new Response(401, [], json_encode([
            'error' => 'توکن احراز هویت الزامی است'
        ]));
    }
    
    // احراز هویت با توکن
    $user = $authService->authenticateWithToken($token);
    
    if (!$user) {
        return new Response(401, [], json_encode([
            'error' => 'توکن نامعتبر یا منقضی شده است'
        ]));
    }
    
    // افزودن اطلاعات کاربر به درخواست
    $request = $request->withAttribute('user', $user);
    
    // ادامه پردازش درخواست
    return $next($request);
};

// مسیر ثبت‌نام
$app->post('/api/register', function(Request $request, Response $response) use ($authService) {
    $data = $request->all();
    
    // اعتبارسنجی داده‌ها
    if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
        return $response->status(400)->json([
            'error' => 'همه فیلدها الزامی هستند'
        ]);
    }
    
    try {
        $userId = $authService->register($data['name'], $data['email'], $data['password']);
        
        return $response->status(201)->json([
            'message' => 'ثبت‌نام با موفقیت انجام شد',
            'user_id' => $userId
        ]);
    } catch (\Exception $e) {
        return $response->status(400)->json([
            'error' => $e->getMessage()
        ]);
    }
});

// مسیر ورود
$app->post('/api/login', function(Request $request, Response $response) use ($authService) {
    $data = $request->all();
    
    // اعتبارسنجی داده‌ها
    if (empty($data['email']) || empty($data['password'])) {
        return $response->status(400)->json([
            'error' => 'ایمیل و رمز عبور الزامی هستند'
        ]);
    }
    
    $result = $authService->login($data['email'], $data['password']);
    
    if ($result) {
        return $response->json($result);
    } else {
        return $response->status(401)->json([
            'error' => 'ایمیل یا رمز عبور نادرست است'
        ]);
    }
});

// مسیر پروفایل (با میان‌افزار احراز هویت)
$app->get('/api/profile', function(Request $request, Response $response) {
    // اطلاعات کاربر از میان‌افزار احراز هویت دریافت می‌شود
    $user = $request->getAttribute('user');
    
    return $response->json([
        'user' => $user
    ]);
})->middleware($authMiddleware);

// مسیر خروج
$app->post('/api/logout', function(Request $request, Response $response) use ($authService) {
    // دریافت توکن از هدر Authorization
    $token = $request->getHeaderLine('Authorization');
    
    // حذف پیشوند "Bearer " از توکن
    if (strpos($token, 'Bearer ') === 0) {
        $token = substr($token, 7);
    }
    
    if (empty($token)) {
        return $response->status(400)->json([
            'error' => 'توکن الزامی است'
        ]);
    }
    
    $authService->invalidateToken($token);
    
    return $response->json([
        'message' => 'خروج با موفقیت انجام شد'
    ]);
});
```

### 3. احراز هویت با استفاده از JWT (JSON Web Tokens)

JWT یک استاندارد باز برای انتقال امن داده‌ها به صورت JSON است. این روش برای API‌ها بسیار مناسب است زیرا stateless (بدون حالت) است:

```php
// برای استفاده از JWT، ابتدا کتابخانه firebase/php-jwt را نصب کنید
// composer require firebase/php-jwt

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// کلاس AuthService برای مدیریت احراز هویت مبتنی بر JWT
class JwtAuthService
{
    private $connection;
    private $secretKey;
    
    public function __construct(Connection $connection, string $secretKey)
    {
        $this->connection = $connection;
        $this->secretKey = $secretKey;
    }
    
    /**
     * ثبت‌نام کاربر جدید
     */
    public function register(string $name, string $email, string $password): int
    {
        // بررسی تکراری نبودن ایمیل
        $user = $this->connection->fetchOne(
            'SELECT id FROM users WHERE email = :email',
            [':email' => $email]
        );
        
        if ($user) {
            throw new \Exception('ایمیل قبلا ثبت شده است');
        }
        
        // هش کردن رمز عبور
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // درج کاربر جدید
        return $this->connection->insert('users', [
            'name' => $name,
            'email' => $email,
            'password' => $hashedPassword,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * تولید توکن JWT
     */
    public function generateJwt(array $user): string
    {
        $issuedAt = time();
        $expirationTime = $issuedAt + 3600; // 1 ساعت اعتبار
        
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email']
            ]
        ];
        
        return JWT::encode($payload, $this->secretKey, 'HS256');
    }
    
    /**
     * ورود کاربر و تولید توکن JWT
     */
    public function login(string $email, string $password): ?array
    {
        // یافتن کاربر
        $user = $this->connection->fetchOne(
            'SELECT id, name, email, password FROM users WHERE email = :email',
            [':email' => $email]
        );
        
        if (!$user) {
            return null;
        }
        
        // بررسی رمز عبور
        if (!password_verify($password, $user['password'])) {
            return null;
        }
        
        // حذف رمز عبور از اطلاعات کاربر
        unset($user['password']);
        
        // تولید توکن JWT
        $token = $this->generateJwt($user);
        
        return [
            'user' => $user,
            'token' => $token,
            'expires_at' => date('Y-m-d H:i:s', time() + 3600)
        ];
    }
    
    /**
     * احراز هویت با توکن JWT
     */
    public function authenticateWithJwt(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));
            
            // بررسی زمان انقضاء توکن
            if (time() > $decoded->exp) {
                return null;
            }
            
            return (array) $decoded->user;
        } catch (\Exception $e) {
            return null;
        }
    }
}
```

استفاده از سرویس احراز هویت مبتنی بر JWT در مسیرها:

```php
$app = App::getInstance();

// کلید رمزنگاری JWT (باید در محیط واقعی، امن نگهداری شود)
$secretKey = 'your-secret-key-here';

// ایجاد نمونه سرویس احراز هویت
$authService = new JwtAuthService(Connection::connection(), $secretKey);

// میان‌افزار احراز هویت برای مسیرهای محافظت شده
$jwtAuthMiddleware = function(Request $request, callable $next) use ($authService) {
    // دریافت توکن از هدر Authorization
    $token = $request->getHeaderLine('Authorization');
    
    // حذف پیشوند "Bearer " از توکن
    if (strpos($token, 'Bearer ') === 0) {
        $token = substr($token, 7);
    }
    
    if (empty($token)) {
        return new Response(401, [], json_encode([
            'error' => 'توکن JWT الزامی است'
        ]));
    }
    
    // احراز هویت با توکن JWT
    $user = $authService->authenticateWithJwt($token);
    
    if (!$user) {
        return new Response(401, [], json_encode([
            'error' => 'توکن JWT نامعتبر یا منقضی شده است'
        ]));
    }
    
    // افزودن اطلاعات کاربر به درخواست
    $request = $request->withAttribute('user', $user);
    
    // ادامه پردازش درخواست
    return $next($request);
};

// مسیر ثبت‌نام
$app->post('/api/register', function(Request $request, Response $response) use ($authService) {
    $data = $request->all();
    
    // اعتبارسنجی داده‌ها
    if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
        return $response->status(400)->json([
            'error' => 'همه فیلدها الزامی هستند'
        ]);
    }
    
    try {
        $userId = $authService->register($data['name'], $data['email'], $data['password']);
        
        return $response->status(201)->json([
            'message' => 'ثبت‌نام با موفقیت انجام شد',
            'user_id' => $userId
        ]);
    } catch (\Exception $e) {
        return $response->status(400)->json([
            'error' => $e->getMessage()
        ]);
    }
});

// مسیر ورود
$app->post('/api/login', function(Request $request, Response $response) use ($authService) {
    $data = $request->all();
    
    // اعتبارسنجی داده‌ها
    if (empty($data['email']) || empty($data['password'])) {
        return $response->status(400)->json([
            'error' => 'ایمیل و رمز عبور الزامی هستند'
        ]);
    }
    
    $result = $authService->login($data['email'], $data['password']);
    
    if ($result) {
        return $response->json($result);
    } else {
        return $response->status(401)->json([
            'error' => 'ایمیل یا رمز عبور نادرست است'
        ]);
    }
});

// مسیر پروفایل (با میان‌افزار احراز هویت JWT)
$app->get('/api/profile', function(Request $request, Response $response) {
    // اطلاعات کاربر از میان‌افزار احراز هویت دریافت می‌شود
    $user = $request->getAttribute('user');
    
    return $response->json([
        'user' => $user
    ]);
})->middleware($jwtAuthMiddleware);
```

## امنیت احراز هویت

امنیت یکی از مهم‌ترین جنبه‌های احراز هویت است. در اینجا چند توصیه امنیتی برای پیاده‌سازی احراز هویت آورده شده است:

### 1. هش کردن رمزهای عبور

همیشه رمزهای عبور را قبل از ذخیره در پایگاه داده هش کنید:

```php
// هش کردن رمز عبور
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// بررسی رمز عبور
$isValid = password_verify($password, $hashedPassword);
```

### 2. استفاده از HTTPS

همیشه از HTTPS برای انتقال داده‌های حساس استفاده کنید. اطلاعات احراز هویت (مانند رمز عبور یا توکن) هرگز نباید از طریق اتصال رمزگذاری نشده منتقل شوند.

### 3. محافظت در برابر حملات CSRF

برای محافظت در برابر حملات Cross-Site Request Forgery (CSRF)، می‌توانید از توکن‌های CSRF استفاده کنید:

```php
// ایجاد توکن CSRF
function generateCsrfToken(): string
{
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $token;
    return $token;
}

// بررسی توکن CSRF
function verifyCsrfToken(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// میان‌افزار CSRF
$csrfMiddleware = function(Request $request, callable $next) {
    // فقط برای متدهای غیر امن (POST, PUT, DELETE, PATCH)
    if (in_array($request->getMethod(), ['POST', 'PUT', 'DELETE', 'PATCH'])) {
        // دریافت توکن CSRF از هدر یا بدنه درخواست
        $token = $request->getHeaderLine('X-CSRF-Token') ?: ($request->getParsedBody()['_csrf'] ?? null);
        
        if (!$token || !verifyCsrfToken($token)) {
            return new Response(403, [], json_encode([
                'error' => 'توکن CSRF نامعتبر است'
            ]));
        }
    }
    
    return $next($request);
};

// استفاده از میان‌افزار CSRF
$app->middleware($csrfMiddleware);
```

### 4. محدود کردن تلاش‌های ورود

برای جلوگیری از حملات Brute Force، تعداد تلاش‌های ورود را محدود کنید:

```php
/**
 * بررسی و محدود کردن تلاش‌های ورود
 * 
 * @param string $email ایمیل کاربر
 * @param bool $success آیا ورود موفقیت‌آمیز بود
 * @return bool آیا اجازه تلاش مجدد دارد
 */
function rateLimitLogin(string $email, bool $success = false): bool
{
    $ip = $_SERVER['REMOTE_ADDR'];
    $key = "login_attempts:{$ip}:{$email}";
    
    if ($success) {
        // در صورت ورود موفق، تلاش‌ها را ریست کن
        $_SESSION[$key] = 0;
        return true;
    }
    
    // افزایش تعداد تلاش‌ها
    $_SESSION[$key] = ($_SESSION[$key] ?? 0) + 1;
    
    // اگر تعداد تلاش‌ها بیش از حد مجاز است، اجازه تلاش مجدد نده
    if ($_SESSION[$key] > 5) {
        // می‌توان زمان انتظار را نیز تنظیم کرد
        $_SESSION[$key . '_locked_until'] = time() + 300; // قفل برای 5 دقیقه
        return false;
    }
    
    return true;
}
```

### 5. مدیریت نشست‌ها

برای احراز هویت مبتنی بر جلسه، مدیریت مناسب نشست‌ها بسیار مهم است:

```php
// تنظیمات امن برای جلسه
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1); // فقط برای HTTPS

// تغییر شناسه جلسه پس از ورود
session_regenerate_id(true);
```

## نمونه کامل: احراز هویت با JWT در API

در ادامه یک نمونه کامل از پیاده‌سازی احراز هویت با JWT در یک API ارائه شده است:

```php
<?php
// auth_api.php - نمونه پیاده‌سازی احراز هویت JWT

require_once 'vendor/autoload.php';

use FlaskPHP\App;
use FlaskPHP\Http\Request;
use FlaskPHP\Http\Response;
use FlaskPHP\Database\Connection;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// کلید رمزنگاری JWT (در محیط واقعی، این کلید باید از متغیرهای محیطی یا فایل پیکربندی خوانده شود)
$jwtKey = 'your-secret-key-here';

// مدت زمان اعتبار توکن (به ثانیه)
$jwtExpiry = 3600; // 1 ساعت

// ایجاد اتصال به پایگاه داده
$dbConfig = [
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'auth_api',
    'username' => 'root',
    'password' => '',
];
$db = Connection::connection('default', $dbConfig);

// ایجاد برنامه
$app = App::getInstance();

// کلاس AuthService
class AuthService
{
    private $db;
    private $jwtKey;
    private $jwtExpiry;
    
    public function __construct(Connection $db, string $jwtKey, int $jwtExpiry)
    {
        $this->db = $db;
        $this->jwtKey = $jwtKey;
        $this->jwtExpiry = $jwtExpiry;
    }
    
    // ثبت‌نام کاربر جدید
    public function register(string $name, string $email, string $password): int
    {
        // بررسی تکراری نبودن ایمیل
        $user = $this->db->fetchOne(
            'SELECT id FROM users WHERE email = :email',
            [':email' => $email]
        );
        
        if ($user) {
            throw new \Exception('ایمیل قبلا ثبت شده است');
        }
        
        // هش کردن رمز عبور
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // درج کاربر جدید
        return $this->db->insert('users', [
            'name' => $name,
            'email' => $email,
            'password' => $hashedPassword,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    // ورود کاربر و صدور توکن JWT
    public function login(string $email, string $password): ?array
    {
        // یافتن کاربر
        $user = $this->db->fetchOne(
            'SELECT id, name, email, password FROM users WHERE email = :email',
            [':email' => $email]
        );
        
        if (!$user) {
            return null;
        }
        
        // بررسی رمز عبور
        if (!password_verify($password, $user['password'])) {
            return null;
        }
        
        // حذف رمز عبور از اطلاعات کاربر
        unset($user['password']);
        
        // صدور توکن JWT
        $token = $this->issueJwt($user);
        
        return [
            'user' => $user,
            'token' => $token,
            'expires_in' => $this->jwtExpiry
        ];
    }
    
    // صدور توکن JWT
    private function issueJwt(array $user): string
    {
        $issuedAt = time();
        $expirationTime = $issuedAt + $this->jwtExpiry;
        
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email']
            ]
        ];
        
        return JWT::encode($payload, $this->jwtKey, 'HS256');
    }
    
    // احراز هویت با توکن JWT
    public function authenticate(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtKey, 'HS256'));
            
            // تبدیل اطلاعات کاربر به آرایه
            return (array) $decoded->user;
        } catch (\Exception $e) {
            return null;
        }
    }
}

// ایجاد سرویس احراز هویت
$authService = new AuthService($db, $jwtKey, $jwtExpiry);

// میان‌افزار احراز هویت
$authMiddleware = function(Request $request, callable $next) use ($authService) {
    // دریافت توکن JWT از هدر Authorization
    $token = $request->getHeaderLine('Authorization');
    
    // حذف پیشوند "Bearer " از توکن
    if (strpos($token, 'Bearer ') === 0) {
        $token = substr($token, 7);
    }
    
    if (empty($token)) {
        return new Response(401, ['Content-Type' => 'application/json'], json_encode([
            'error' => 'توکن احراز هویت الزامی است'
        ]));
    }
    
    // احراز هویت با توکن JWT
    $user = $authService->authenticate($token);
    
    if (!$user) {
        return new Response(401, ['Content-Type' => 'application/json'], json_encode([
            'error' => 'توکن نامعتبر یا منقضی شده است'
        ]));
    }
    
    // افزودن اطلاعات کاربر به درخواست
    $request = $request->withAttribute('user', $user);
    
    // ادامه پردازش درخواست
    return $next($request);
};

// مسیر ثبت‌نام
$app->post('/api/register', function(Request $request, Response $response) use ($authService) {
    $data = $request->getParsedBody();
    
    // اعتبارسنجی داده‌ها
    if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
        return $response->withStatus(400)->json([
            'error' => 'همه فیلدها الزامی هستند'
        ]);
    }
    
    try {
        $userId = $authService->register($data['name'], $data['email'], $data['password']);
        
        return $response->withStatus(201)->json([
            'message' => 'ثبت‌نام با موفقیت انجام شد',
            'user_id' => $userId
        ]);
    } catch (\Exception $e) {
        return $response->withStatus(400)->json([
            'error' => $e->getMessage()
        ]);
    }
});

// مسیر ورود
$app->post('/api/login', function(Request $request, Response $response) use ($authService) {
    $data = $request->getParsedBody();
    
    // اعتبارسنجی داده‌ها
    if (empty($data['email']) || empty($data['password'])) {
        return $response->withStatus(400)->json([
            'error' => 'ایمیل و رمز عبور الزامی هستند'
        ]);
    }
    
    $result = $authService->login($data['email'], $data['password']);
    
    if ($result) {
        return $response->json($result);
    } else {
        return $response->withStatus(401)->json([
            'error' => 'ایمیل یا رمز عبور نادرست است'
        ]);
    }
});

// مسیر پروفایل (با میان‌افزار احراز هویت)
$app->get('/api/profile', function(Request $request, Response $response) {
    // اطلاعات کاربر از میان‌افزار احراز هویت دریافت می‌شود
    $user = $request->getAttribute('user');
    
    return $response->json([
        'user' => $user
    ]);
})->middleware($authMiddleware);

// مسیر تازه‌سازی توکن
$app->post('/api/refresh-token', function(Request $request, Response $response) use ($authService) {
    // این مسیر باید با احراز هویت محافظت شود
    $user = $request->getAttribute('user');
    
    $result = $authService->login($user['email'], ''); // نیاز به پیاده‌سازی متد جداگانه برای تازه‌سازی توکن
    
    return $response->json([
        'token' => $result['token'],
        'expires_in' => $result['expires_in']
    ]);
})->middleware($authMiddleware);

// اجرای برنامه
$app->run();
```

## جمع‌بندی

در این بخش با مفاهیم و روش‌های مختلف احراز هویت در فلسک‌پی‌اچ‌پی آشنا شدید:

1. **احراز هویت مبتنی بر جلسه**: برای وب‌سایت‌های سنتی مناسب است و اطلاعات کاربر در سمت سرور ذخیره می‌شود.
2. **احراز هویت مبتنی بر توکن**: برای API‌ها مناسب است و توکن‌ها در پایگاه داده ذخیره می‌شوند.
3. **احراز هویت مبتنی بر JWT**: یک روش بدون حالت (stateless) برای API‌ها که نیاز به ذخیره‌سازی توکن در سمت سرور ندارد.

همچنین با توصیه‌های امنیتی مهم مانند هش کردن رمزهای عبور، استفاده از HTTPS، محافظت در برابر حملات CSRF و محدود کردن تلاش‌های ورود آشنا شدید.

برای پیاده‌سازی احراز هویت امن و مقیاس‌پذیر، توصیه می‌شود از روش‌های استاندارد و کتابخانه‌های معتبر استفاده کنید و همواره به‌روزرسانی‌های امنیتی را دنبال کنید.