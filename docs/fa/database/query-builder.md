# کوئری بیلدر (Query Builder)

کوئری بیلدر یکی از ویژگی‌های قدرتمند فلسک‌پی‌اچ‌پی است که به شما امکان می‌دهد کوئری‌های SQL را به صورت شیء‌گرا و زنجیره‌ای بسازید. با استفاده از کوئری بیلدر، نیازی به نوشتن کوئری‌های SQL خام ندارید و می‌توانید کوئری‌های پیچیده را به راحتی ایجاد کنید.

## مزایای استفاده از کوئری بیلدر

- **امنیت**: محافظت خودکار در برابر حملات SQL Injection
- **خوانایی**: کد تمیز و خوانا با استفاده از روش‌های زنجیره‌ای
- **قابلیت استفاده مجدد**: امکان ایجاد کوئری‌های پویا و قابل استفاده مجدد
- **مستقل از دیتابیس**: کار با انواع مختلف پایگاه‌های داده بدون تغییر در کد
- **یکپارچگی با فریمورک**: همکاری نزدیک با سایر بخش‌های فریمورک

## شروع کار با کوئری بیلدر

### ایجاد یک نمونه از کوئری بیلدر

```php
use PHLask\Database\QueryBuilder;
use FlaskPHP\Database\Connection;

// روش 1: ایجاد مستقیم
$query = new QueryBuilder('users');

// روش 2: استفاده از اتصال موجود
$connection = Connection::connection('default');
$query = new QueryBuilder('users', $connection);

// روش 3: استفاده از متد table در کلاس Connection
$query = Connection::connection()->table('users');
```

## عملیات پایه

### دریافت همه رکوردها

```php
// دریافت همه کاربران
$users = $query->get();

// نتیجه: آرایه‌ای از همه رکوردها
// [
//     ['id' => 1, 'name' => 'علی', 'email' => 'ali@example.com'],
//     ['id' => 2, 'name' => 'مریم', 'email' => 'maryam@example.com'],
//     ...
// ]
```

### دریافت یک رکورد

```php
// دریافت اولین کاربر
$user = $query->first();

// نتیجه: اولین رکورد یا null اگر رکوردی یافت نشود
// ['id' => 1, 'name' => 'علی', 'email' => 'ali@example.com']
```

### انتخاب ستون‌های خاص

```php
// انتخاب ستون‌های خاص
$users = $query->select('id', 'name', 'email')->get();

// یا با استفاده از آرایه
$users = $query->select(['id', 'name', 'email'])->get();

// نتیجه: آرایه‌ای از رکوردها با ستون‌های مشخص شده
// [
//     ['id' => 1, 'name' => 'علی', 'email' => 'ali@example.com'],
//     ['id' => 2, 'name' => 'مریم', 'email' => 'maryam@example.com'],
//     ...
// ]
```

## شرط‌ها (Where Clauses)

### شرط ساده

```php
// یافتن کاربران فعال
$activeUsers = $query->where('active', 1)->get();

// یافتن کاربران با یک ایمیل خاص
$user = $query->where('email', 'ali@example.com')->first();
```

### شرط با عملگر

```php
// یافتن کاربران با سن بیشتر از 18
$adults = $query->where('age', '>', 18)->get();

// یافتن کاربران با سن بین 18 و 30
$youngAdults = $query
    ->where('age', '>=', 18)
    ->where('age', '<=', 30)
    ->get();
```

### شرط‌های OR

```php
// یافتن کاربران با نام علی یا محمد
$users = $query
    ->where('name', 'علی')
    ->orWhere('name', 'محمد')
    ->get();
```

### شرط‌های LIKE

```php
// یافتن کاربران با نام‌هایی که با "م" شروع می‌شوند
$users = $query->whereLike('name', 'م%')->get();

// یافتن کاربران با نام‌هایی که شامل "علی" هستند
$users = $query->whereLike('name', '%علی%')->get();

// استفاده از OR با LIKE
$users = $query
    ->whereLike('name', '%علی%')
    ->orWhereLike('name', '%محمد%')
    ->get();
```

### شرط‌های NULL

```php
// یافتن کاربران بدون تاریخ تولد
$users = $query->whereNull('birth_date')->get();

// یافتن کاربران با تاریخ تولد
$users = $query->whereNotNull('birth_date')->get();
```

### شرط‌های IN

```php
// یافتن کاربران با شناسه‌های خاص
$users = $query->whereIn('id', [1, 2, 3])->get();

// یافتن کاربران با نقش‌های خاص
$users = $query->whereIn('role', ['admin', 'editor'])->get();

// یافتن کاربرانی که در این نقش‌ها نیستند
$users = $query->whereNotIn('role', ['guest', 'user'])->get();
```

### شرط‌های BETWEEN

```php
// یافتن کاربران با سن بین 18 و 30
$users = $query->whereBetween('age', [18, 30])->get();

// یافتن کاربران با سن خارج از محدوده 18 تا 30
$users = $query->whereNotBetween('age', [18, 30])->get();
```

### ترکیب شرط‌ها

می‌توانید انواع مختلف شرط‌ها را با هم ترکیب کنید:

```php
// یافتن کاربران فعال با سن بیشتر از 18 که نام‌شان با "م" شروع می‌شود
$users = $query
    ->where('active', 1)
    ->where('age', '>', 18)
    ->whereLike('name', 'م%')
    ->get();
```

## مرتب‌سازی (Ordering)

```php
// مرتب‌سازی بر اساس نام (صعودی)
$users = $query->orderBy('name')->get();

// مرتب‌سازی بر اساس نام (نزولی)
$users = $query->orderBy('name', 'DESC')->get();
// یا
$users = $query->orderByDesc('name')->get();

// مرتب‌سازی چندگانه
$users = $query
    ->orderBy('role')
    ->orderByDesc('created_at')
    ->get();
```

## گروه‌بندی (Grouping)

```php
// گروه‌بندی کاربران بر اساس نقش
$roleCounts = $query
    ->select('role', 'COUNT(*) as count')
    ->groupBy('role')
    ->get();

// گروه‌بندی با شرط HAVING
$popularRoles = $query
    ->select('role', 'COUNT(*) as count')
    ->groupBy('role')
    ->having('count', '>', 5)
    ->get();
```

## محدودیت و جابجایی (Limit & Offset)

```php
// دریافت 10 کاربر اول
$users = $query->limit(10)->get();

// دریافت 10 کاربر بعدی (برای صفحه‌بندی)
$users = $query->limit(10)->offset(10)->get();

// روش ساده‌تر برای صفحه‌بندی
$page = 2; // شماره صفحه
$perPage = 10; // تعداد رکورد در هر صفحه
$users = $query->paginate($page, $perPage);
```

## پیوند‌ها (Joins)

### INNER JOIN

```php
// پیوند جدول کاربران با جدول سفارش‌ها
$usersWithOrders = $query
    ->select('users.*', 'orders.id as order_id', 'orders.total')
    ->join('orders', 'users.id', '=', 'orders.user_id')
    ->get();
```

### LEFT JOIN

```php
// پیوند جدول کاربران با جدول سفارش‌ها (حتی کاربران بدون سفارش)
$usersWithOrders = $query
    ->select('users.*', 'orders.id as order_id', 'orders.total')
    ->leftJoin('orders', 'users.id', '=', 'orders.user_id')
    ->get();
```

### RIGHT JOIN

```php
// پیوند جدول کاربران با جدول سفارش‌ها (حتی سفارش‌های بدون کاربر)
$usersWithOrders = $query
    ->select('users.*', 'orders.id as order_id', 'orders.total')
    ->rightJoin('orders', 'users.id', '=', 'orders.user_id')
    ->get();
```

## توابع تجمعی (Aggregate Functions)

### شمارش رکوردها

```php
// شمارش تعداد کل کاربران
$count = $query->count();

// شمارش کاربران فعال
$activeCount = $query->where('active', 1)->count();

// شمارش یک ستون خاص (بدون null)
$emailCount = $query->count('email');
```

### سایر توابع تجمعی

در حال حاضر، فلسک‌پی‌اچ‌پی از توابع تجمعی مانند SUM، AVG، MAX و MIN به صورت مستقیم پشتیبانی نمی‌کند، اما می‌توانید با استفاده از متد select و raw این عملیات را انجام دهید:

```php
// میانگین سن کاربران
$avgAge = $query
    ->select('AVG(age) as avg_age')
    ->first();

$avgAge = $avgAge['avg_age'] ?? 0;

// بیشترین سن
$maxAge = $query
    ->select('MAX(age) as max_age')
    ->first();

$maxAge = $maxAge['max_age'] ?? 0;
```

## عملیات درج، به‌روزرسانی و حذف

### درج رکورد

```php
// درج یک کاربر جدید
$userId = $query->insert([
    'name' => 'رضا محمدی',
    'email' => 'reza@example.com',
    'age' => 25,
    'active' => 1
]);

// نتیجه: شناسه رکورد درج شده
// 123
```

### به‌روزرسانی رکوردها

```php
// به‌روزرسانی یک کاربر خاص
$affected = $query
    ->where('id', 123)
    ->update([
        'name' => 'رضا احمدی',
        'email' => 'reza.new@example.com'
    ]);

// به‌روزرسانی همه کاربران غیرفعال
$affected = $query
    ->where('active', 0)
    ->update([
        'last_login' => null
    ]);

// نتیجه: تعداد رکوردهای به‌روزرسانی شده
// 3
```

### حذف رکوردها

```php
// حذف یک کاربر خاص
$affected = $query
    ->where('id', 123)
    ->delete();

// حذف همه کاربران غیرفعال
$affected = $query
    ->where('active', 0)
    ->delete();

// نتیجه: تعداد رکوردهای حذف شده
// 3
```

## کوئری‌های خام (Raw Queries)

اگر نیاز به اجرای کوئری‌های SQL خام دارید، می‌توانید از متد raw استفاده کنید:

```php
// اجرای یک کوئری خام
$results = $query->raw('SELECT * FROM users WHERE created_at > :date', [
    ':date' => '2023-01-01'
]);
```

## روش‌های جستجوی پیشرفته

### استفاده از exists

```php
// بررسی وجود حداقل یک کاربر فعال
$hasActiveUsers = $query
    ->where('active', 1)
    ->exists();

// نتیجه: true یا false
```

### دریافت مقدار یک ستون

```php
// دریافت ایمیل کاربر با شناسه 123
$email = $query
    ->where('id', 123)
    ->value('email');

// نتیجه: مقدار ستون یا null اگر رکوردی یافت نشود
// "reza@example.com"
```

### دریافت آرایه‌ای از مقادیر یک ستون

```php
// دریافت ایمیل همه کاربران فعال
$emails = $query
    ->where('active', 1)
    ->pluck('email');

// نتیجه: آرایه‌ای از مقادیر ستون
// ["ali@example.com", "maryam@example.com", ...]
```

## دیباگ کوئری‌ها

برای دیباگ و مشاهده کوئری SQL تولید شده می‌توانید از متد debug استفاده کنید:

```php
// مشاهده کوئری SQL
$sql = $query
    ->where('active', 1)
    ->orderBy('name')
    ->limit(10)
    ->debug();

// نتیجه: کوئری SQL با مقادیر پارامترها
// "SELECT * FROM users WHERE active = 1 ORDER BY name ASC LIMIT 10"

// توجه: این متد کوئری را اجرا نمی‌کند، فقط آن را نمایش می‌دهد
```

## کوئری‌های تو در تو (Subqueries)

فلسک‌پی‌اچ‌پی در حال حاضر از تابع مستقیم برای ساخت subquery پشتیبانی نمی‌کند، اما می‌توانید با استفاده از SQL خام، subquery‌ها را پیاده‌سازی کنید:

```php
// استفاده از raw SQL برای subquery
$users = $query->raw('
    SELECT * FROM users 
    WHERE id IN (
        SELECT user_id FROM orders WHERE total > :total
    )
', [
    ':total' => 1000
]);
```

## ترکیب با تراکنش‌ها (Transactions)

برای اطمینان از انسجام داده‌ها، می‌توانید کوئری‌ها را در یک تراکنش اجرا کنید:

```php
use FlaskPHP\Database\Connection;

$connection = Connection::connection();

try {
    $connection->beginTransaction();
    
    // درج کاربر جدید
    $userId = $connection->table('users')->insert([
        'name' => 'کاربر جدید',
        'email' => 'new@example.com'
    ]);
    
    // درج آدرس برای کاربر
    $connection->table('addresses')->insert([
        'user_id' => $userId,
        'city' => 'تهران',
        'address' => 'خیابان ولیعصر'
    ]);
    
    $connection->commit();
    
    return 'عملیات با موفقیت انجام شد';
} catch (\Exception $e) {
    $connection->rollBack();
    
    return 'خطا: ' . $e->getMessage();
}
```

## نمونه‌های کاربردی

### مثال 1: جستجوی پیشرفته

```php
public function searchUsers(string $keyword, array $filters = [], int $page = 1, int $perPage = 10)
{
    $query = new QueryBuilder('users');
    
    // جستجو براساس کلیدواژه
    if (!empty($keyword)) {
        $query->whereLike('name', '%' . $keyword . '%')
              ->orWhereLike('email', '%' . $keyword . '%');
    }
    
    // اعمال فیلترها
    if (!empty($filters)) {
        // فیلتر براساس وضعیت فعال بودن
        if (isset($filters['active'])) {
            $query->where('active', (int) $filters['active']);
        }
        
        // فیلتر براساس نقش
        if (!empty($filters['role'])) {
            $query->whereIn('role', $filters['role']);
        }
        
        // فیلتر براساس محدوده سنی
        if (!empty($filters['age_from']) && !empty($filters['age_to'])) {
            $query->whereBetween('age', [$filters['age_from'], $filters['age_to']]);
        }
        
        // فیلتر براساس تاریخ ثبت‌نام
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
    }
    
    // مرتب‌سازی
    $sortField = $filters['sort_field'] ?? 'created_at';
    $sortDirection = $filters['sort_direction'] ?? 'DESC';
    $query->orderBy($sortField, $sortDirection);
    
    // صفحه‌بندی
    $query->paginate($page, $perPage);
    
    // دریافت داده‌ها
    $users = $query->get();
    $total = $query->count();
    
    return [
        'data' => $users,
        'meta' => [
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => ceil($total / $perPage)
        ]
    ];
}
```

### مثال 2: گزارش‌گیری

```php
public function getSalesReport(string $period = 'monthly', string $startDate = null, string $endDate = null)
{
    $query = new QueryBuilder('orders');
    
    // انتخاب فیلدها براساس دوره زمانی
    if ($period === 'daily') {
        $query->select('DATE(created_at) as date', 'COUNT(*) as count', 'SUM(total) as total_sales');
        $query->groupBy('DATE(created_at)');
    } elseif ($period === 'monthly') {
        $query->select('YEAR(created_at) as year', 'MONTH(created_at) as month', 'COUNT(*) as count', 'SUM(total) as total_sales');
        $query->groupBy('YEAR(created_at)', 'MONTH(created_at)');
    } elseif ($period === 'yearly') {
        $query->select('YEAR(created_at) as year', 'COUNT(*) as count', 'SUM(total) as total_sales');
        $query->groupBy('YEAR(created_at)');
    }
    
    // فیلتر براساس محدوده تاریخ
    if ($startDate) {
        $query->where('created_at', '>=', $startDate);
    }
    
    if ($endDate) {
        $query->where('created_at', '<=', $endDate);
    }
    
    // مرتب‌سازی براساس تاریخ
    if ($period === 'daily') {
        $query->orderBy('date');
    } elseif ($period === 'monthly') {
        $query->orderBy('year');
        $query->orderBy('month');
    } elseif ($period === 'yearly') {
        $query->orderBy('year');
    }
    
    // اجرای کوئری
    return $query->get();
}
```

### مثال 3: بررسی داده‌های منحصر به فرد قبل از درج

```php
public function createUser(array $data)
{
    $query = new QueryBuilder('users');
    
    // بررسی تکراری نبودن ایمیل
    $existingUser = $query->where('email', $data['email'])->first();
    
    if ($existingUser) {
        throw new \Exception('این ایمیل قبلاً ثبت شده است');
    }
    
    // افزودن زمان ایجاد
    $data['created_at'] = date('Y-m-d H:i:s');
    $data['updated_at'] = date('Y-m-d H:i:s');
    
    // هش کردن رمز عبور
    if (isset($data['password'])) {
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
    }
    
    // درج کاربر جدید
    $userId = $query->insert($data);
    
    // بازگرداندن اطلاعات کاربر
    return $query->where('id', $userId)->first();
}
```

## محدودیت‌ها و راه‌حل‌ها

### محدودیت 1: Subqueries پیچیده

در حال حاضر، فلسک‌پی‌اچ‌پی از API شیء‌گرا برای subquery‌های پیچیده پشتیبانی نمی‌کند. برای این موارد می‌توانید از کوئری‌های خام استفاده کنید:

```php
// استفاده از کوئری خام
$results = $connection->fetchAll('
    SELECT u.* 
    FROM users u
    WHERE EXISTS (
        SELECT 1 FROM orders o 
        WHERE o.user_id = u.id AND o.total > :total
    )
', [
    ':total' => 1000
]);
```

### محدودیت 2: توابع تجمعی پیشرفته

اگرچه می‌توانید از توابع تجمعی با استفاده از select استفاده کنید، اما API مستقیمی برای آن‌ها وجود ندارد. در نسخه‌های آینده، متدهای مستقیم برای این توابع اضافه خواهند شد.

## توصیه‌ها و بهترین روش‌ها

### 1. استفاده از تراکنش‌ها برای عملیات چندگانه

همیشه برای عملیاتی که شامل چندین درج یا به‌روزرسانی هستند، از تراکنش‌ها استفاده کنید تا از انسجام داده‌ها اطمینان حاصل کنید:

```php
$connection->transaction(function($conn) {
    // درج کاربر
    $userId = $conn->table('users')->insert([
        'name' => 'کاربر جدید',
        'email' => 'new@example.com'
    ]);
    
    // درج محصولات سفارش داده شده
    foreach ($products as $product) {
        $conn->table('orders')->insert([
            'user_id' => $userId,
            'product_id' => $product['id'],
            'quantity' => $product['quantity']
        ]);
    }
    
    return $userId;
});
```

### 2. بررسی وجود رکورد قبل از به‌روزرسانی یا حذف

همیشه قبل از به‌روزرسانی یا حذف، وجود رکورد را بررسی کنید:

```php
$query = new QueryBuilder('users');

// بررسی وجود کاربر
$exists = $query->where('id', $userId)->exists();

if (!$exists) {
    throw new \Exception('کاربر یافت نشد');
}

// به‌روزرسانی کاربر
$query->where('id', $userId)->update($data);
```

### 3. محدود کردن نتایج برای کارایی بهتر

همیشه نتایج را با استفاده از limit محدود کنید، مخصوصاً برای جداول بزرگ:

```php
// به جای این
$users = $query->where('active', 1)->get();

// از این استفاده کنید
$users = $query->where('active', 1)->limit(100)->get();
```

### 4. استفاده از select برای کاهش حجم داده‌ها

فقط ستون‌هایی را انتخاب کنید که واقعاً به آن‌ها نیاز دارید:

```php
// به جای این
$users = $query->get();

// از این استفاده کنید
$users = $query->select('id', 'name', 'email')->get();
```

### 5. استفاده از پارامترهای باند شده

همیشه از متدهای کوئری بیلدر برای ایجاد کوئری‌ها استفاده کنید و از ساخت دستی کوئری‌ها خودداری کنید تا از حملات SQL Injection جلوگیری شود:

```php
// نادرست (آسیب‌پذیر به SQL Injection)
$id = $_GET['id'];
$sql = "SELECT * FROM users WHERE id = $id";
$results = $connection->fetchAll($sql);

// درست
$id = $_GET['id'];
$results = $query->where('id', $id)->get();
```

## عیب‌یابی مشکلات رایج

### مشکل 1: کوئری داده‌های اشتباه برمی‌گرداند

اگر کوئری شما نتایج اشتباهی برمی‌گرداند، موارد زیر را بررسی کنید:

1. با استفاده از متد `debug()` کوئری SQL نهایی را بررسی کنید.
2. اطمینان حاصل کنید که شرط‌های تعریف شده صحیح هستند.
3. از ترتیب صحیح `where` و `orWhere` اطمینان حاصل کنید.

```php
// مشاهده کوئری SQL
$sql = $query
    ->where('active', 1)
    ->whereLike('name', '%علی%')
    ->debug();

echo $sql; // بررسی کوئری
```

### مشکل 2: ارور SQL

اگر با خطای SQL مواجه شدید:

1. نام جدول و ستون‌ها را بررسی کنید.
2. اطمینان حاصل کنید که قالب داده‌ها با نوع ستون مطابقت دارد.
3. کوئری SQL را با استفاده از `debug()` بررسی کنید.

```php
try {
    $users = $query->where('active', 1)->get();
} catch (\Exception $e) {
    echo 'خطا: ' . $e->getMessage();
    echo 'کوئری: ' . $query->debug();
}
```

### مشکل 3: خطای Undefined column

اگر با خطای "Undefined column" مواجه شدید:

1. نام‌های ستون‌ها را با دقت بررسی کنید.
2. در صورت استفاده از join، نام جدول را به ستون‌ها اضافه کنید.

```php
// ناdrست (ممکن است باعث ابهام شود)
$query->select('id', 'name')->join('orders', 'users.id', '=', 'orders.user_id');

// درست
$query->select('users.id', 'users.name')->join('orders', 'users.id', '=', 'orders.user_id');
```

## نسخه‌های آینده

در نسخه‌های آینده فلسک‌پی‌اچ‌پی، قابلیت‌های زیر به کوئری بیلدر اضافه خواهند شد:

- متدهای مستقیم برای توابع تجمعی (`sum()`, `avg()`, `min()`, `max()`)
- پشتیبانی بهتر از subquery‌ها با API شیء‌گرا
- پشتیبانی از JOIN‌های پیچیده‌تر
- قابلیت‌های بیشتر برای گروه‌بندی و مرتب‌سازی
- بهینه‌سازی کوئری‌ها برای عملکرد بهتر

## مقایسه با سایر کوئری بیلدرها

### فلسک‌پی‌اچ‌پی vs Laravel Query Builder

```php
// Laravel
$users = DB::table('users')
    ->where('active', 1)
    ->orderBy('name')
    ->get();

// فلسک‌پی‌اچ‌پی
$users = (new QueryBuilder('users'))
    ->where('active', 1)
    ->orderBy('name')
    ->get();
```

### فلسک‌پی‌اچ‌پی vs Doctrine DBAL

```php
// Doctrine DBAL
$queryBuilder = $conn->createQueryBuilder();
$users = $queryBuilder
    ->select('*')
    ->from('users')
    ->where('active = :active')
    ->setParameter('active', 1)
    ->orderBy('name')
    ->execute()
    ->fetchAll();

// فلسک‌پی‌اچ‌پی
$users = (new QueryBuilder('users'))
    ->where('active', 1)
    ->orderBy('name')
    ->get();
```

## خلاصه

کوئری بیلدر فلسک‌پی‌اچ‌پی ابزاری قدرتمند و انعطاف‌پذیر برای کار با پایگاه داده است که به شما امکان می‌دهد کوئری‌های SQL را به صورت شیء‌گرا و امن بسازید. با استفاده از روش‌های زنجیره‌ای، می‌توانید کوئری‌های پیچیده را به راحتی ایجاد کنید و از مزایای امنیتی مانند محافظت در برابر SQL Injection بهره‌مند شوید.

با رعایت بهترین روش‌ها و توصیه‌های ارائه شده، می‌توانید کوئری‌های بهینه و کارآمد ایجاد کنید که با نیازهای برنامه شما مطابقت دارند.

## گام بعدی

پس از آشنایی با کوئری بیلدر، برای یادگیری بیشتر می‌توانید به بخش‌های زیر مراجعه کنید:

- [اتصال به پایگاه داده](connection.md) - آشنایی با کلاس Connection و روش‌های برقراری اتصال به پایگاه داده
- [مدل‌ها](models.md) - آشنایی با کلاس Model و استفاده از ORM
- [تراکنش‌ها](transactions.md) - کار با تراکنش‌ها برای اطمینان از انسجام داده‌ها