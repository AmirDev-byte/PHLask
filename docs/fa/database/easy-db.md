# کتابخانه EasyDB

کتابخانه `EasyDB` یک لایه انتزاعی ساده و سبک برای تعامل با پایگاه داده در فلسک‌پی‌اچ‌پی است. این کتابخانه برای کاربرانی
طراحی شده که به دنبال یک روش ساده و مستقیم برای کار با پایگاه داده هستند، بدون نیاز به یادگیری ویژگی‌های پیچیده‌تر
کلاس‌های `Connection` و `QueryBuilder`.

## مزایای استفاده از EasyDB

- **سادگی**: API بسیار ساده و روان
- **کد کمتر**: کمترین میزان کد برای انجام عملیات رایج
- **یادگیری آسان**: مناسب برای مبتدیان و پروژه‌های کوچک
- **عدم نیاز به ORM**: مستقیماً با داده‌ها کار می‌کنید
- **انعطاف‌پذیری**: می‌توانید در کنار سایر بخش‌های فلسک‌پی‌اچ‌پی از آن استفاده کنید

## ایجاد اتصال به پایگاه داده

کتابخانه `EasyDB` روش‌های ساده‌ای برای اتصال به انواع مختلف پایگاه‌های داده ارائه می‌دهد:

### اتصال به SQLite

```php
use PLHask\Database\EasyDB;

// اتصال به فایل SQLite
$db = EasyDB::sqlite(__DIR__ . '/database.sqlite');

// استفاده از دیتابیس موقت در حافظه
$db = EasyDB::sqlite(':memory:');
```

### اتصال به MySQL

```php
use PLHask\Database\EasyDB;

// اتصال به MySQL
$db = EasyDB::mysql(
    'my_database',   // نام پایگاه داده
    'root',          // نام کاربری
    'password',      // رمز عبور
    'localhost',     // هاست (اختیاری، پیش‌فرض: localhost)
    3306             // پورت (اختیاری، پیش‌فرض: 3306)
);
```

## کار با جداول (Tables)

### دسترسی به جدول

```php
// دسترسی به جدول users
$usersTable = $db->table('users');

// می‌توانید مستقیماً عملیات را روی جدول انجام دهید
$users = $db->table('users')->all();
```

### دریافت همه رکوردها

```php
// دریافت همه کاربران
$users = $db->table('users')->all();

// چاپ نام همه کاربران
foreach ($users as $user) {
    echo $user['name'] . '<br>';
}
```

### فیلتر کردن با where

```php
// یافتن کاربران فعال
$activeUsers = $db->table('users')->where('active', 1)->get();

// یافتن کاربران با نقش خاص
$admins = $db->table('users')->where('role', 'admin')->get();
```

### مرتب‌سازی نتایج

```php
// مرتب‌سازی بر اساس نام (صعودی)
$users = $db->table('users')->orderBy('name')->get();

// مرتب‌سازی بر اساس تاریخ عضویت (نزولی)
$users = $db->table('users')->orderBy('created_at', 'DESC')->get();
```

### محدود کردن نتایج

```php
// دریافت 10 کاربر اول
$users = $db->table('users')->limit(10)->get();

// صفحه‌بندی ساده
$page = 2;
$perPage = 10;
$users = $db->table('users')
    ->limit($perPage)
    ->offset(($page - 1) * $perPage)
    ->get();
```

## عملیات CRUD

### دریافت یک رکورد (Read)

```php
// یافتن کاربر با شناسه خاص
$user = $db->table('users')->find(123);

// یافتن اولین کاربر با شرایط خاص
$user = $db->table('users')->where('email', 'ali@example.com')->first();
```

### درج رکورد جدید (Create)

```php
// درج یک کاربر جدید
$userId = $db->table('users')->insert([
    'name' => 'علی رضایی',
    'email' => 'ali@example.com',
    'password' => password_hash('secret123', PASSWORD_DEFAULT),
    'active' => 1,
    'created_at' => date('Y-m-d H:i:s')
]);

echo "کاربر جدید با شناسه {$userId} ایجاد شد";
```

### به‌روزرسانی رکورد (Update)

```php
// به‌روزرسانی یک کاربر
$affected = $db->table('users')
    ->where('id', 123)
    ->update([
        'name' => 'علی محمدی',
        'email' => 'ali.new@example.com',
        'updated_at' => date('Y-m-d H:i:s')
    ]);

echo "{$affected} رکورد به‌روزرسانی شد";
```

### حذف رکورد (Delete)

```php
// حذف یک کاربر
$affected = $db->table('users')
    ->where('id', 123)
    ->delete();

echo "{$affected} رکورد حذف شد";
```

## توابع مفید

### شمارش رکوردها

```php
// شمارش کل کاربران
$count = $db->table('users')->count();

// شمارش کاربران فعال
$activeCount = $db->table('users')->where('active', 1)->count();
```

### بررسی وجود رکورد

```php
// بررسی وجود کاربر با ایمیل خاص
$exists = $db->table('users')->where('email', 'ali@example.com')->exists();

if ($exists) {
    echo "این ایمیل قبلاً ثبت شده است";
} else {
    echo "ایمیل قابل استفاده است";
}
```

### یافتن یا ایجاد رکورد

```php
// یافتن کاربر با ایمیل خاص یا ایجاد آن
$user = $db->table('users')->firstOrCreate(
    // شرایط جستجو
    ['email' => 'new@example.com'],
    
    // داده‌های اضافی برای ایجاد (اگر رکورد یافت نشد)
    [
        'name' => 'کاربر جدید',
        'password' => password_hash('default123', PASSWORD_DEFAULT),
        'active' => 1,
        'created_at' => date('Y-m-d H:i:s')
    ]
);
```

### دریافت مقدار یک ستون

```php
// دریافت آرایه‌ای از ایمیل‌های کاربران
$emails = $db->table('users')->where('active', 1)->pluck('email');

foreach ($emails as $email) {
    echo $email . '<br>';
}
```

## استفاده از تراکنش‌ها

کتابخانه `EasyDB` از تراکنش‌های پایگاه داده پشتیبانی می‌کند:

```php
try {
    // شروع تراکنش
    $db->beginTransaction();
    
    // عملیات اول: درج کاربر
    $userId = $db->table('users')->insert([
        'name' => 'کاربر جدید',
        'email' => 'user@example.com'
    ]);
    
    // عملیات دوم: درج پروفایل
    $db->table('profiles')->insert([
        'user_id' => $userId,
        'bio' => 'بیوگرافی کاربر'
    ]);
    
    // تأیید تراکنش
    $db->commit();
    
    echo "عملیات با موفقیت انجام شد";
} catch (\Exception $e) {
    // برگشت تراکنش
    $db->rollBack();
    
    echo "خطا: " . $e->getMessage();
}
```

## اجرای کوئری‌های خام

اگر نیاز به کوئری‌های SQL پیچیده‌تر دارید، می‌توانید از متد `query` استفاده کنید:

```php
// اجرای یک کوئری SQL خام
$results = $db->query(
    'SELECT u.*, COUNT(o.id) as orders_count
     FROM users u
     LEFT JOIN orders o ON u.id = o.user_id
     WHERE u.active = :active
     GROUP BY u.id
     HAVING orders_count > :min_orders',
    [
        ':active' => 1,
        ':min_orders' => 5
    ]
);
```

## مثال‌های کاربردی

### مثال 1: سیستم احراز هویت ساده

```php
class AuthService
{
    private $db;
    
    public function __construct(EasyDB $db)
    {
        $this->db = $db;
    }
    
    public function register(string $name, string $email, string $password): int
    {
        // بررسی تکراری نبودن ایمیل
        $exists = $this->db->table('users')->where('email', $email)->exists();
        
        if ($exists) {
            throw new \Exception('این ایمیل قبلاً ثبت شده است');
        }
        
        // ایجاد کاربر جدید
        return $this->db->table('users')->insert([
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'active' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function login(string $email, string $password): ?array
    {
        // یافتن کاربر با ایمیل
        $user = $this->db->table('users')->where('email', $email)->first();
        
        if (!$user) {
            return null; // کاربر یافت نشد
        }
        
        // بررسی رمز عبور
        if (!password_verify($password, $user['password'])) {
            return null; // رمز عبور نادرست
        }
        
        // به‌روزرسانی زمان آخرین ورود
        $this->db->table('users')
            ->where('id', $user['id'])
            ->update(['last_login' => date('Y-m-d H:i:s')]);
        
        // حذف رمز عبور از اطلاعات کاربر
        unset($user['password']);
        
        return $user;
    }
}

// استفاده
$db = EasyDB::sqlite('database.sqlite');
$auth = new AuthService($db);

// ثبت‌نام
try {
    $userId = $auth->register('علی رضایی', 'ali@example.com', 'secure123');
    echo "ثبت‌نام موفقیت‌آمیز بود. شناسه کاربر: {$userId}";
} catch (\Exception $e) {
    echo "خطا در ثبت‌نام: " . $e->getMessage();
}

// ورود
$user = $auth->login('ali@example.com', 'secure123');
if ($user) {
    echo "خوش آمدید، {$user['name']}!";
} else {
    echo "ایمیل یا رمز عبور نادرست است";
}
```

### مثال 2: سیستم بلاگ ساده

```php
class BlogService
{
    private $db;
    
    public function __construct(EasyDB $db)
    {
        $this->db = $db;
    }
    
    public function getPosts(int $page = 1, int $perPage = 10): array
    {
        // دریافت پست‌ها با صفحه‌بندی
        $posts = $this->db->table('posts')
            ->where('published', 1)
            ->orderBy('created_at', 'DESC')
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->get();
        
        // دریافت تعداد کل پست‌ها
        $total = $this->db->table('posts')->where('published', 1)->count();
        
        return [
            'data' => $posts,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage)
            ]
        ];
    }
    
    public function getPost(int $id): ?array
    {
        // یافتن پست با شناسه
        $post = $this->db->table('posts')
            ->where('id', $id)
            ->where('published', 1)
            ->first();
        
        if (!$post) {
            return null;
        }
        
        // دریافت نویسنده پست
        $author = $this->db->table('users')->find($post['user_id']);
        
        // دریافت نظرات پست
        $comments = $this->db->table('comments')
            ->where('post_id', $id)
            ->where('approved', 1)
            ->orderBy('created_at', 'DESC')
            ->get();
        
        return [
            'post' => $post,
            'author' => $author,
            'comments' => $comments
        ];
    }
    
    public function createPost(int $userId, string $title, string $content, bool $published = true): int
    {
        // ایجاد پست جدید
        return $this->db->table('posts')->insert([
            'user_id' => $userId,
            'title' => $title,
            'content' => $content,
            'published' => $published ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function addComment(int $postId, int $userId, string $content): int
    {
        // افزودن نظر
        return $this->db->table('comments')->insert([
            'post_id' => $postId,
            'user_id' => $userId,
            'content' => $content,
            'approved' => 1, // تأیید خودکار
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}

// استفاده
$db = EasyDB::sqlite('blog.sqlite');
$blog = new BlogService($db);

// دریافت پست‌ها
$posts = $blog->getPosts(1, 5);
echo "نمایش {$posts['meta']['current_page']} از {$posts['meta']['total_pages']} صفحه<br>";

foreach ($posts['data'] as $post) {
    echo "<h2>{$post['title']}</h2>";
    echo "<p>" . substr($post['content'], 0, 100) . "...</p>";
    echo "<a href='/post/{$post['id']}'>ادامه مطلب</a><br><br>";
}

// ایجاد پست جدید
$postId = $blog->createPost(1, 'مطلب جدید', 'محتوای مطلب جدید');
echo "مطلب جدید با شناسه {$postId} ایجاد شد";

// نمایش یک پست
$post = $blog->getPost($postId);
if ($post) {
    echo "<h1>{$post['post']['title']}</h1>";
    echo "<p>نویسنده: {$post['author']['name']}</p>";
    echo "<div>{$post['post']['content']}</div>";
    
    echo "<h3>نظرات ({count($post['comments'])})</h3>";
    foreach ($post['comments'] as $comment) {
        echo "<div>{$comment['content']}</div>";
    }
}
```

## توصیه‌ها و بهترین روش‌ها

### 1. ایجاد یک نمونه از EasyDB

برای استفاده بهینه، فقط یک نمونه از `EasyDB` ایجاد کنید و آن را در سراسر برنامه استفاده کنید:

```php
// ایجاد یک نمونه در ابتدای برنامه
$db = EasyDB::sqlite('database.sqlite');

// انتقال آن به کلاس‌ها و توابع
function getUserById($id, $db) {
    return $db->table('users')->find($id);
}

class UserService {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    // متدهای کلاس
}
```

### 2. استفاده از تراکنش‌ها برای عملیات چندگانه

همیشه برای عملیات‌هایی که شامل چندین تغییر وابسته هستند، از تراکنش‌ها استفاده کنید:

```php
$db->beginTransaction();
try {
    // چندین عملیات
    $db->commit();
} catch (\Exception $e) {
    $db->rollBack();
    throw $e;
}
```

### 3. اعتبارسنجی داده‌ها قبل از درج یا به‌روزرسانی

قبل از ذخیره داده‌ها در پایگاه داده، آن‌ها را اعتبارسنجی کنید:

```php
function validateUser($data) {
    $errors = [];
    
    if (empty($data['name'])) {
        $errors['name'] = 'نام الزامی است';
    }
    
    if (empty($data['email'])) {
        $errors['email'] = 'ایمیل الزامی است';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'ایمیل نامعتبر است';
    }
    
    return $errors;
}

// استفاده
$data = [
    'name' => $_POST['name'] ?? '',
    'email' => $_POST['email'] ?? ''
];

$errors = validateUser($data);

if (empty($errors)) {
    $db->table('users')->insert($data);
} else {
    // نمایش خطاها
}
```

## مقایسه با QueryBuilder

کتابخانه `EasyDB` ساده‌تر و با API محدودتر نسبت به `QueryBuilder` است. انتخاب بین این دو به نیازهای پروژه شما بستگی
دارد:

- استفاده از **EasyDB** برای:
    - پروژه‌های کوچک و ساده
    - زمانی که API ساده‌تر می‌خواهید
    - کاربرانی که تازه با ORM آشنا می‌شوند

- استفاده از **QueryBuilder** برای:
    - پروژه‌های بزرگ‌تر و پیچیده‌تر
    - نیاز به قابلیت‌های پیشرفته‌تر کوئری
    - زمانی که به انعطاف‌پذیری بیشتری نیاز دارید

## گام بعدی

اکنون که با کتابخانه `EasyDB` آشنا شدید، می‌توانید به بخش‌های دیگر مستندات مراجعه کنید:

- [اتصال به پایگاه داده](connection.md) - آشنایی بیشتر با کلاس Connection
- [کوئری بیلدر](query-builder.md) - استفاده از کوئری بیلدر برای کوئری‌های پیچیده‌تر
- [مدل‌ها](models.md) - استفاده از مدل‌ها برای رویکرد شیء‌گرا