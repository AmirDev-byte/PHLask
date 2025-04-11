# مدل‌ها (Models)

مدل‌ها در فلسک‌پی‌اچ‌پی لایه انتزاعی برای تعامل با پایگاه داده هستند که به شما امکان می‌دهند داده‌ها را به صورت شیء‌گرا مدیریت کنید. با استفاده از مدل‌ها، می‌توانید با جداول پایگاه داده خود به عنوان کلاس‌های PHP تعامل داشته باشید.

## مزایای استفاده از مدل‌ها

- **کد تمیزتر**: تعامل با داده‌ها به صورت شیء‌گرا
- **انکپسولیشن**: منطق مرتبط با داده در یک مکان جمع می‌شود
- **قابلیت استفاده مجدد**: استفاده مجدد از کد برای عملیات مشابه
- **اعتبارسنجی**: اعتبارسنجی داده‌ها قبل از ذخیره
- **خوانایی**: کد تمیزتر و خواناتر

## ایجاد یک مدل

برای ایجاد یک مدل، کلاس خود را از کلاس `FlaskPHP\Database\Model` ارث‌بری کنید:

```php
<?php

namespace App\Models;

use FlaskPHP\Database\Model;

class User extends Model
{
    // تنظیم نام جدول (اختیاری)
    protected static string $table = 'users';
    
    // تنظیم کلید اصلی (اختیاری، پیش‌فرض: id)
    protected static string $primaryKey = 'id';
    
    // فعال‌سازی تایم‌استمپ‌ها (created_at و updated_at)
    protected static bool $timestamps = true;
    
    // تنظیم نام فیلدهای تایم‌استمپ (اختیاری)
    protected static string $createdAt = 'created_at';
    protected static string $updatedAt = 'updated_at';
    
    // متدهای سفارشی برای این مدل
    public function fullName(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }
    
    public function isActive(): bool
    {
        return (bool) $this->active;
    }
    
    // متد برای هش کردن رمز عبور
    public function setPassword(string $password): self
    {
        $this->password = password_hash($password, PASSWORD_DEFAULT);
        return $this;
    }
    
    // متد برای بررسی رمز عبور
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }
}
```

## تنظیمات مدل

### نام جدول

فلسک‌پی‌اچ‌پی به صورت خودکار نام جدول را از نام کلاس مدل استنتاج می‌کند. برای مثال، کلاس `User` به جدول `users` نگاشت می‌شود. اگر می‌خواهید نام متفاوتی برای جدول تعیین کنید، می‌توانید خاصیت `$table` را تنظیم کنید:

```php
protected static string $table = 'tbl_users';
```

### کلید اصلی

پیش‌فرض کلید اصلی `id` است. اگر می‌خواهید کلید اصلی دیگری تعیین کنید، می‌توانید خاصیت `$primaryKey` را تنظیم کنید:

```php
protected static string $primaryKey = 'user_id';
```

### تایم‌استمپ‌ها

فلسک‌پی‌اچ‌پی به صورت خودکار فیلدهای `created_at` و `updated_at` را مدیریت می‌کند. اگر نمی‌خواهید از این ویژگی استفاده کنید، می‌توانید آن را غیرفعال کنید:

```php
protected static bool $timestamps = false;
```

یا اگر می‌خواهید نام‌های متفاوتی برای این فیلدها استفاده کنید:

```php
protected static string $createdAt = 'date_created';
protected static string $updatedAt = 'date_updated';
```

## کار با مدل‌ها

### ایجاد یک نمونه جدید

```php
// روش 1: ایجاد نمونه جدید و سپس ذخیره
$user = new User();
$user->name = 'علی رضایی';
$user->email = 'ali@example.com';
$user->setPassword('password123');
$user->save();

// روش 2: ایجاد با استفاده از آرایه و سپس ذخیره
$user = new User([
    'name' => 'علی رضایی',
    'email' => 'ali@example.com',
    'active' => 1
]);
$user->setPassword('password123');
$user->save();

// روش 3: ایجاد مستقیم با استفاده از متد create
$user = User::create([
    'name' => 'علی رضایی',
    'email' => 'ali@example.com',
    'password' => password_hash('password123', PASSWORD_DEFAULT),
    'active' => 1
]);
```

### یافتن یک رکورد

```php
// یافتن با کلید اصلی
$user = User::find(1);

// بررسی وجود رکورد
if ($user) {
    echo $user->name; // 'علی رضایی'
} else {
    echo 'کاربر یافت نشد';
}

// یافتن اولین رکورد با یک شرط خاص
$admin = User::findWhere('role', 'admin');

// یافتن با شرط و عملگر مقایسه
$olderUsers = User::findWhere('age', '>', 30);

// یافتن براساس ایمیل (با متد سفارشی)
public static function findByEmail(string $email): ?self
{
    return static::findWhere('email', $email);
}

$user = User::findByEmail('ali@example.com');
```

### دریافت همه رکوردها

```php
// دریافت همه کاربران
$allUsers = User::all();

// دریافت همه کاربران فعال
$activeUsers = User::findAllWhere('active', 1);

// دریافت کاربران با شرط‌های پیچیده‌تر
$admins = User::query()
    ->where('role', 'admin')
    ->where('active', 1)
    ->orderBy('created_at', 'DESC')
    ->get();
```

### به‌روزرسانی یک رکورد

```php
// روش 1: یافتن و سپس به‌روزرسانی
$user = User::find(1);
if ($user) {
    $user->name = 'علی محمدی';
    $user->email = 'ali.new@example.com';
    $user->save();
}

// روش 2: به‌روزرسانی چندین ویژگی با یک بار
$user = User::find(1);
if ($user) {
    $user->fill([
        'name' => 'علی محمدی',
        'email' => 'ali.new@example.com',
        'active' => 0
    ]);
    $user->save();
}

// بررسی تغییرات
$user = User::find(1);
if ($user->isDirty('email')) {
    echo 'ایمیل تغییر کرده است';
}

// دریافت فیلدهای تغییر یافته
$dirtyFields = $user->getDirty();
```

### حذف یک رکورد

```php
// روش 1: یافتن و سپس حذف
$user = User::find(1);
if ($user) {
    $user->delete();
}

// روش 2: حذف با استفاده از کوئری بیلدر
User::query()->where('id', 1)->delete();

// حذف چندین رکورد
User::query()->where('active', 0)->delete();
```

## روابط بین مدل‌ها

در نسخه فعلی فلسک‌پی‌اچ‌پی، روابط بین مدل‌ها به صورت مستقیم پشتیبانی نمی‌شوند، اما می‌توانید با استفاده از متدهای سفارشی، این روابط را پیاده‌سازی کنید:

### رابطه یک به چند

```php
class User extends Model
{
    // ... سایر خاصیت‌ها و متدها
    
    // دریافت سفارش‌های یک کاربر
    public function orders()
    {
        return Order::findAllWhere('user_id', $this->id);
    }
}

class Order extends Model
{
    // ... سایر خاصیت‌ها و متدها
    
    // دریافت کاربر مربوط به سفارش
    public function user(): ?User
    {
        return User::find($this->user_id);
    }
}

// استفاده
$user = User::find(1);
$orders = $user->orders();

$order = Order::find(1);
$orderOwner = $order->user();
```

### رابطه چند به چند

```php
class User extends Model
{
    // ... سایر خاصیت‌ها و متدها
    
    // دریافت نقش‌های یک کاربر
    public function roles()
    {
        // فرض می‌کنیم جدول میانی 'user_roles' است
        $roleIds = (new QueryBuilder('user_roles'))
            ->where('user_id', $this->id)
            ->select('role_id')
            ->pluck('role_id');
            
        return Role::query()->whereIn('id', $roleIds)->get();
    }
    
    // افزودن نقش به کاربر
    public function addRole(int $roleId): bool
    {
        return (new QueryBuilder('user_roles'))->insert([
            'user_id' => $this->id,
            'role_id' => $roleId
        ]) > 0;
    }
    
    // حذف نقش از کاربر
    public function removeRole(int $roleId): bool
    {
        return (new QueryBuilder('user_roles'))
            ->where('user_id', $this->id)
            ->where('role_id', $roleId)
            ->delete() > 0;
    }
}

class Role extends Model
{
    // ... سایر خاصیت‌ها و متدها
    
    // دریافت کاربران دارای این نقش
    public function users()
    {
        // فرض می‌کنیم جدول میانی 'user_roles' است
        $userIds = (new QueryBuilder('user_roles'))
            ->where('role_id', $this->id)
            ->select('user_id')
            ->pluck('user_id');
            
        return User::query()->whereIn('id', $userIds)->get();
    }
}

// استفاده
$user = User::find(1);
$roles = $user->roles();
$user->addRole(2);
$user->removeRole(3);

$role = Role::find(1);
$usersWithRole = $role->users();
```

## توسعه مدل‌ها

### افزودن متدهای سفارشی

می‌توانید متدهای سفارشی به مدل‌های خود اضافه کنید تا منطق خاص برنامه را پیاده‌سازی کنید:

```php
class User extends Model
{
    // ... سایر خاصیت‌ها و متدها
    
    // متد برای بررسی امکان دسترسی به یک ویژگی
    public function canAccess(string $feature): bool
    {
        $permissions = $this->getPermissions();
        return in_array($feature, $permissions);
    }
    
    // متد برای تغییر وضعیت فعال/غیرفعال
    public function toggleActive(): bool
    {
        $this->active = $this->active ? 0 : 1;
        return $this->save();
    }
    
    // متد برای دریافت آواتار کاربر یا آواتار پیش‌فرض
    public function getAvatar(): string
    {
        if (!empty($this->avatar)) {
            return '/uploads/avatars/' . $this->avatar;
        }
        
        return '/assets/images/default-avatar.png';
    }
    
    // متد برای بررسی اینکه آیا کاربر مدیر است
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
```

### متدهای استاتیک سفارشی

متدهای استاتیک سفارشی برای انجام عملیات روی کل مجموعه داده:

```php
class User extends Model
{
    // ... سایر خاصیت‌ها و متدها
    
    // شمارش کاربران فعال
    public static function countActive(): int
    {
        return static::query()->where('active', 1)->count();
    }
    
    // یافتن کاربر با توکن
    public static function findByToken(string $token): ?self
    {
        return static::findWhere('remember_token', $token);
    }
    
    // یافتن یا ایجاد کاربر با ایمیل
    public static function findOrCreateByEmail(string $email, array $data = []): self
    {
        $user = static::findWhere('email', $email);
        
        if (!$user) {
            $userData = array_merge(['email' => $email], $data);
            $user = static::create($userData);
        }
        
        return $user;
    }
    
    // بروزرسانی دسته‌ای کاربران
    public static function deactivateInactive(int $days): int
    {
        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        return static::query()
            ->where('last_login', '<', $date)
            ->where('active', 1)
            ->update(['active' => 0]);
    }
}
```

## اعتبارسنجی داده‌ها

در فلسک‌پی‌اچ‌پی، اعتبارسنجی داده‌ها به صورت داخلی پیاده‌سازی نشده است، اما می‌توانید متدهای اعتبارسنجی سفارشی در مدل خود ایجاد کنید:

```php
class User extends Model
{
    // ... سایر خاصیت‌ها و متدها
    
    // متد برای اعتبارسنجی داده‌ها قبل از ذخیره
    public function validate(): array
    {
        $errors = [];
        
        // بررسی نام
        if (empty($this->name)) {
            $errors['name'] = 'نام الزامی است';
        } elseif (strlen($this->name) < 3) {
            $errors['name'] = 'نام باید حداقل 3 کاراکتر باشد';
        }
        
        // بررسی ایمیل
        if (empty($this->email)) {
            $errors['email'] = 'ایمیل الزامی است';
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'ایمیل نامعتبر است';
        } else {
            // بررسی تکراری نبودن ایمیل
            $existingUser = self::findWhere('email', $this->email);
            if ($existingUser && $existingUser->id !== $this->id) {
                $errors['email'] = 'این ایمیل قبلاً ثبت شده است';
            }
        }
        
        // بررسی رمز عبور برای کاربران جدید
        if (!$this->exists && empty($this->password)) {
            $errors['password'] = 'رمز عبور الزامی است';
        } elseif (!$this->exists && strlen($this->password) < 8) {
            $errors['password'] = 'رمز عبور باید حداقل 8 کاراکتر باشد';
        }
        
        return $errors;
    }
    
    // متد برای ذخیره با اعتبارسنجی
    public function saveWithValidation(): array
    {
        $errors = $this->validate();
        
        if (empty($errors)) {
            $this->save();
        }
        
        return $errors;
    }
}

// استفاده
$user = new User([
    'name' => 'علی',
    'email' => 'invalid-email'
]);

$errors = $user->saveWithValidation();

if (!empty($errors)) {
    // نمایش خطاها به کاربر
    foreach ($errors as $field => $message) {
        echo "خطا در {$field}: {$message}\n";
    }
} else {
    echo "کاربر با موفقیت ذخیره شد";
}
```

## پیاده‌سازی Traits برای مدل‌ها

می‌توانید Trait‌هایی برای قابلیت‌های مشترک بین مدل‌ها ایجاد کنید:

```php
// SoftDeleteTrait.php
trait SoftDeleteTrait
{
    public function delete(): bool
    {
        return $this->update(['deleted_at' => date('Y-m-d H:i:s')]);
    }
    
    public function forceDelete(): bool
    {
        return parent::delete();
    }
    
    public function restore(): bool
    {
        return $this->update(['deleted_at' => null]);
    }
    
    public static function withTrashed()
    {
        return static::query();
    }
    
    public static function onlyTrashed()
    {
        return static::query()->whereNotNull('deleted_at');
    }
}

// استفاده در مدل
class User extends Model
{
    use SoftDeleteTrait;
    
    // ... سایر خاصیت‌ها و متدها
}

// استفاده
$user = User::find(1);
$user->delete(); // soft delete
$user->restore(); // بازگرداندن
$user->forceDelete(); // حذف واقعی

$trashedUsers = User::onlyTrashed()->get();
$allUsersWithTrashed = User::withTrashed()->get();
```

## نمونه کاربردی: سیستم احراز هویت

```php
class Auth
{
    public static function attempt(string $email, string $password): ?User
    {
        $user = User::findWhere('email', $email);
        
        if (!$user) {
            return null;
        }
        
        if (!$user->verifyPassword($password)) {
            return null;
        }
        
        // به‌روزرسانی زمان آخرین ورود
        $user->last_login = date('Y-m-d H:i:s');
        $user->save();
        
        return $user;
    }
    
    public static function login(User $user): string
    {
        // ایجاد توکن جدید
        $token = bin2hex(random_bytes(32));
        
        // ذخیره توکن
        $user->remember_token = $token;
        $user->save();
        
        // ذخیره توکن در سشن یا کوکی
        $_SESSION['auth_token'] = $token;
        
        return $token;
    }
    
    public static function check(): bool
    {
        return self::user() !== null;
    }
    
    public static function user(): ?User
    {
        if (empty($_SESSION['auth_token'])) {
            return null;
        }
        
        return User::findWhere('remember_token', $_SESSION['auth_token']);
    }
    
    public static function logout(): void
    {
        $user = self::user();
        
        if ($user) {
            $user->remember_token = null;
            $user->save();
        }
        
        unset($_SESSION['auth_token']);
    }
}

// استفاده
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $user = Auth::attempt($email, $password);
    
    if ($user) {
        Auth::login($user);
        header('Location: /dashboard');
        exit;
    } else {
        $error = 'ایمیل یا رمز عبور نادرست است';
    }
}

// بررسی وضعیت ورود کاربر
if (Auth::check()) {
    $currentUser = Auth::user();
    echo "خوش آمدید، {$currentUser->name}";
} else {
    echo "لطفاً وارد شوید";
}
```

## توصیه‌ها و بهترین روش‌ها

### 1. مدل‌ها را در یک پوشه جداگانه قرار دهید

```
app/
  Models/
    User.php
    Post.php
    Comment.php
    Role.php
```

### 2. از نام‌گذاری استاندارد استفاده کنید

- نام کلاس‌های مدل باید در حالت PascalCase و مفرد باشند (مثلاً `User` نه `Users`).
- نام جداول باید در حالت snake_case و جمع باشند (مثلاً `users` نه `user`).

### 3. منطق کسب‌و‌کار را در مدل‌ها قرار دهید

مدل‌ها مکان مناسبی برای منطق کسب‌و‌کار مرتبط با آن نوع داده هستند. به جای اینکه این منطق را در کنترلرها یا دیگر بخش‌های برنامه پخش کنید، آن‌ها را در مدل قرار دهید.

### 4. از اعتبارسنجی استفاده کنید

همیشه داده‌ها را قبل از ذخیره در پایگاه داده اعتبارسنجی کنید. این کار را می‌توانید با افزودن متدهای اعتبارسنجی به مدل‌های خود انجام دهید.

### 5. از روش‌های زنجیره‌ای استفاده کنید

برای خوانایی بیشتر، از متدهایی استفاده کنید که امکان زنجیره کردن را فراهم می‌کنند:

```php
$user = (new User())
    ->fill($data)
    ->setPassword($password)
    ->saveWithValidation();
```

## آینده توسعه مدل‌ها

در نسخه‌های آینده فلسک‌پی‌اچ‌پی، قابلیت‌های زیر به مدل‌ها اضافه خواهند شد:

1. **روابط خودکار**: تعریف روابط با سایر مدل‌ها به صورت اعلامی
2. **سیستم اعتبارسنجی**: اعتبارسنجی داده‌ها به صورت داخلی
3. **سیستم رویدادها**: امکان تعریف رویدادها برای عملیات مختلف مدل (مثل قبل از ذخیره، بعد از حذف و غیره)
4. **Eager Loading**: بارگذاری روابط به صورت بهینه
5. **Scopes**: تعریف قید و شرط‌های معمول به صورت scope

## مقایسه با سایر فریمورک‌ها

### فلسک‌پی‌اچ‌پی vs Laravel

```php
// Laravel
$user = User::find(1);
$user->name = 'New Name';
$user->save();

// فلسک‌پی‌اچ‌پی
$user = User::find(1);
$user->name = 'New Name';
$user->save();
```

همانطور که می‌بینید، API در هر دو فریمورک بسیار مشابه است، اما در فلسک‌پی‌اچ‌پی، سیستم ORM ساده‌تر است و قابلیت‌های پیشرفته مانند روابط اعلامی و Eager Loading هنوز پیاده‌سازی نشده‌اند.

## گام بعدی

پس از آشنایی با مدل‌ها، برای یادگیری بیشتر می‌توانید به بخش‌های زیر مراجعه کنید:

- [کوئری بیلدر](query-builder.md) - استفاده از کوئری بیلدر برای عملیات پیچیده‌تر
- [اتصال به پایگاه داده](connection.md) - آشنایی با کلاس Connection
- [تراکنش‌ها](transactions.md) - کار با تراکنش‌ها برای عملیات پیچیده