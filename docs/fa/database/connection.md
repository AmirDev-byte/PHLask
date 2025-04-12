# اتصال به پایگاه داده

کلاس `Connection` در فلسک‌پی‌اچ‌پی مسئول ایجاد و مدیریت اتصال به پایگاه داده است. این کلاس پایه‌ای برای تمام عملیات
دیتابیس مانند کوئری‌ها، تراکنش‌ها و ارتباط با مدل‌ها است.

## ایجاد اتصال به پایگاه داده

فلسک‌پی‌اچ‌پی از چندین روش برای ایجاد اتصال به پایگاه داده پشتیبانی می‌کند:

### روش 1: استفاده از متد استاتیک connection

```php
use PHLask\Database\Connection;

// ایجاد اتصال پیش‌فرض
$connection = Connection::connection('default', [
    'driver' => 'mysql',
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'my_database',
    'username' => 'root',
    'password' => 'your_password',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
]);

// استفاده از اتصال در جای دیگر برنامه
$connection = Connection::connection('default');
```

### روش 2: ایجاد مستقیم نمونه جدید

```php
use PHLask\Database\Connection;

$connection = new Connection([
    'driver' => 'mysql',
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'my_database',
    'username' => 'root',
    'password' => 'your_password',
    'charset' => 'utf8mb4',
]);
```

### روش 3: استفاده از کلاس App

```php
use PHLask\App;

$app = App::getInstance();

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

// دسترسی به اتصال پایگاه داده
$connection = $app->db();
```

## انواع پایگاه‌های داده پشتیبانی شده

فلسک‌پی‌اچ‌پی از چندین نوع پایگاه داده پشتیبانی می‌کند:

### MySQL

```php
$connection = Connection::connection('mysql', [
    'driver' => 'mysql',
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'my_database',
    'username' => 'root',
    'password' => 'your_password',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
]);
```

### PostgreSQL

```php
$connection = Connection::connection('pgsql', [
    'driver' => 'pgsql',
    'host' => 'localhost',
    'port' => 5432,
    'database' => 'my_database',
    'username' => 'postgres',
    'password' => 'your_password',
    'charset' => 'utf8',
]);
```

### SQLite

```php
$connection = Connection::connection('sqlite', [
    'driver' => 'sqlite',
    'database' => __DIR__ . '/database.sqlite',
]);
```

## اجرای کوئری‌های ساده

کلاس `Connection` متدهایی برای اجرای کوئری‌های SQL ارائه می‌دهد:

### اجرای کوئری و دریافت همه نتایج

```php
// دریافت همه کاربران
$users = $connection->fetchAll('SELECT * FROM users');

// دریافت کاربران فعال
$activeUsers = $connection->fetchAll(
    'SELECT * FROM users WHERE active = :active',
    [':active' => 1]
);

// حلقه روی نتایج
foreach ($activeUsers as $user) {
    echo $user['name'] . '<br>';
}
```

### اجرای کوئری و دریافت یک سطر

```php
// دریافت یک کاربر با شناسه خاص
$user = $connection->fetchOne(
    'SELECT * FROM users WHERE id = :id',
    [':id' => 123]
);

if ($user) {
    echo "کاربر یافت شد: " . $user['name'];
} else {
    echo "کاربر یافت نشد";
}
```

### اجرای کوئری و دریافت یک ستون

```php
// دریافت لیست ایمیل‌های کاربران
$emails = $connection->fetchColumn(
    'SELECT email FROM users WHERE active = :active',
    [':active' => 1]
);

// حلقه روی ایمیل‌ها
foreach ($emails as $email) {
    echo $email . '<br>';
}
```

## عملیات درج، به‌روزرسانی و حذف

کلاس `Connection` متدهایی برای عملیات‌های پایه دیتابیس ارائه می‌دهد:

### درج داده

```php
// درج یک کاربر جدید
$userId = $connection->insert('users', [
    'name' => 'علی رضایی',
    'email' => 'ali@example.com',
    'password' => password_hash('password123', PASSWORD_DEFAULT),
    'active' => 1,
    'created_at' => date('Y-m-d H:i:s')
]);

echo "کاربر جدید با شناسه {$userId} ایجاد شد";
```

### به‌روزرسانی داده

```php
// به‌روزرسانی یک کاربر
$affected = $connection->update(
    'users',
    [
        'name' => 'علی محمدی',
        'email' => 'ali.new@example.com',
        'updated_at' => date('Y-m-d H:i:s')
    ],
    'id = :id',
    [':id' => 123]
);

echo "{$affected} رکورد به‌روزرسانی شد";
```

### حذف داده

```php
// حذف یک کاربر
$affected = $connection->delete(
    'users',
    'id = :id',
    [':id' => 123]
);

echo "{$affected} رکورد حذف شد";
```

## استفاده از تراکنش‌ها

تراکنش‌ها به شما امکان می‌دهند مجموعه‌ای از عملیات را به صورت اتمیک اجرا کنید:

### روش 1: استفاده از متد transaction

```php
try {
    $result = $connection->transaction(function($conn) {
        // عملیات اول
        $userId = $conn->insert('users', [
            'name' => 'کاربر جدید',
            'email' => 'user@example.com'
        ]);
        
        // عملیات دوم
        $conn->insert('profiles', [
            'user_id' => $userId,
            'bio' => 'توضیحات کاربر'
        ]);
        
        // بازگرداندن نتیجه
        return $userId;
    });
    
    echo "عملیات با موفقیت انجام شد. شناسه کاربر: {$result}";
} catch (\Exception $e) {
    echo "خطا: " . $e->getMessage();
}
```

### روش 2: مدیریت دستی تراکنش

```php
try {
    // شروع تراکنش
    $connection->beginTransaction();
    
    // عملیات اول
    $userId = $connection->insert('users', [
        'name' => 'کاربر جدید',
        'email' => 'user@example.com'
    ]);
    
    // عملیات دوم
    $connection->insert('profiles', [
        'user_id' => $userId,
        'bio' => 'توضیحات کاربر'
    ]);
    
    // تأیید تراکنش
    $connection->commit();
    
    echo "عملیات با موفقیت انجام شد";
} catch (\Exception $e) {
    // برگشت تراکنش
    $connection->rollBack();
    
    echo "خطا: " . $e->getMessage();
}
```

## کوئری بیلدر

کلاس `Connection` یک رابط برای استفاده از کوئری بیلدر ارائه می‌دهد:

```php
// ایجاد نمونه کوئری بیلدر
$query = $connection->table('users');

// دریافت کاربران فعال
$activeUsers = $query->where('active', 1)->get();

// دریافت کاربر با شناسه خاص
$user = $query->where('id', 123)->first();

// درج کاربر جدید
$userId = $query->insert([
    'name' => 'کاربر جدید',
    'email' => 'user@example.com'
]);

// به‌روزرسانی کاربر
$affected = $query->where('id', 123)->update([
    'name' => 'نام جدید'
]);

// حذف کاربر
$affected = $query->where('id', 123)->delete();
```

برای اطلاعات بیشتر در مورد کوئری بیلدر، به بخش [کوئری بیلدر](query-builder.md) مراجعه کنید.

## چندین اتصال همزمان

می‌توانید چندین اتصال به پایگاه‌های داده مختلف داشته باشید:

```php
// اتصال به پایگاه داده اصلی
$mainDb = Connection::connection('main', [
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'main_db',
    'username' => 'root',
    'password' => 'password'
]);

// اتصال به پایگاه داده لاگ
$logDb = Connection::connection('log', [
    'driver' => 'mysql',
    'host' => 'log-server',
    'database' => 'logs_db',
    'username' => 'logger',
    'password' => 'log_password'
]);

// استفاده از اتصال‌ها
$users = $mainDb->table('users')->get();
$logDb->insert('access_logs', ['user_id' => 1, 'action' => 'login']);
```

## مدیریت خطاها

هنگام کار با پایگاه داده، مدیریت خطاها بسیار مهم است:

```php
use PHLask\Exceptions\DatabaseException;

try {
    $users = $connection->fetchAll('SELECT * FROM non_existent_table');
} catch (DatabaseException $e) {
    echo "خطای دیتابیس: " . $e->getMessage();
    echo "کوئری: " . $e->getQuery();
    
    // ثبت خطا در لاگ
    error_log('خطای دیتابیس: ' . $e->getMessage());
    
    // نمایش پیام مناسب به کاربر
    echo "متأسفانه مشکلی در دریافت اطلاعات به وجود آمده است. لطفاً بعداً دوباره تلاش کنید.";
}
```

## توصیه‌ها و بهترین روش‌ها

### 1. استفاده از پارامترهای باند شده

همیشه از پارامترهای باند شده برای جلوگیری از حمله‌های SQL Injection استفاده کنید:

```php
// نادرست (آسیب‌پذیر به SQL Injection)
$username = $_POST['username'];
$users = $connection->fetchAll("SELECT * FROM users WHERE username = '$username'");

// درست
$username = $_POST['username'];
$users = $connection->fetchAll(
    "SELECT * FROM users WHERE username = :username",
    [':username' => $username]
);
```

### 2. مدیریت صحیح اتصال‌ها

اتصال‌ها را به درستی مدیریت کنید و از ایجاد اتصال‌های غیرضروری جلوگیری کنید:

```php
// نادرست: ایجاد اتصال‌های متعدد
function getUserById($id) {
    $conn = new Connection([/* تنظیمات */]);
    return $conn->fetchOne('SELECT * FROM users WHERE id = :id', [':id' => $id]);
}

// درست: استفاده از یک اتصال مشترک
$conn = Connection::connection();

function getUserById($id, $conn) {
    return $conn->fetchOne('SELECT * FROM users WHERE id = :id', [':id' => $id]);
}
```

### 3. استفاده از تراکنش‌ها برای عملیات چندگانه

برای عملیات‌هایی که شامل چندین تغییر وابسته هستند، از تراکنش‌ها استفاده کنید:

```php
$connection->transaction(function($conn) {
    // تمام عملیات به صورت اتمیک انجام می‌شوند
    // اگر هر خطایی رخ دهد، تمام تغییرات برگشت داده می‌شوند
});
```

### 4. بستن اتصال پس از استفاده

در برنامه‌های بزرگ که منابع محدودی دارند، اتصال‌ها را پس از استفاده ببندید:

```php
$connection = new Connection([/* تنظیمات */]);
// استفاده از اتصال
// ...
// بستن اتصال
$connection->close();
```

توجه: در فلسک‌پی‌اچ‌پی، اتصال‌های ایجاد شده با متد `connection` به صورت خودکار مدیریت می‌شوند و نیازی به بستن دستی
ندارند.

## عیب‌یابی مشکلات رایج

### مشکل 1: خطای اتصال به پایگاه داده

اگر با خطای اتصال مواجه می‌شوید، موارد زیر را بررسی کنید:

1. اطلاعات اتصال (نام کاربری، رمز عبور، نام پایگاه داده) را بررسی کنید.
2. مطمئن شوید که پایگاه داده در حال اجرا است و قابل دسترسی است.
3. تنظیمات فایروال را بررسی کنید.

### مشکل 2: خطای کوئری

اگر کوئری شما با خطا مواجه می‌شود:

1. سینتکس کوئری را بررسی کنید.
2. وجود جدول‌ها و ستون‌های مورد استفاده را بررسی کنید.
3. از کوئری بیلدر برای ساخت کوئری‌های پیچیده استفاده کنید.

### مشکل 3: عملکرد ضعیف

اگر با مشکلات عملکردی مواجه هستید:

1. از ایندکس‌های مناسب استفاده کنید.
2. کوئری‌ها را بهینه کنید.
3. فقط داده‌های مورد نیاز را بازیابی کنید (از `SELECT *` اجتناب کنید).
4. برای داده‌های ثابت یا پرکاربرد، از سیستم کش استفاده کنید.

## گام بعدی

اکنون که با کلاس `Connection` آشنا شدید، می‌توانید به بخش‌های دیگر مستندات مراجعه کنید:

- [کوئری بیلدر](query-builder.md) - استفاده از کوئری بیلدر برای ساخت کوئری‌های پیچیده
- [مدل‌ها](models.md) - کار با مدل‌های داده‌ای
- [تراکنش‌ها](transactions.md) - استفاده پیشرفته از تراکنش‌ها
- [کتابخانه EasyDB](easy-db.md) - کتابخانه ساده‌تر برای تعامل با پایگاه داده