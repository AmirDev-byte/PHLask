# کنترل دسترسی (Authorization)

کنترل دسترسی (Authorization) فرآیند تعیین این است که یک کاربر احراز هویت شده چه کارهایی را می‌تواند انجام دهد. پس از
اینکه کاربر احراز هویت شد (Authentication)، باید مشخص شود که آیا او مجاز به دسترسی به منابع مورد نظر است یا خیر.

## تفاوت احراز هویت و کنترل دسترسی

- **احراز هویت (Authentication)**: تعیین هویت کاربر (چه کسی هستید؟)
- **کنترل دسترسی (Authorization)**: تعیین مجوزهای کاربر (چه کاری می‌توانید انجام دهید؟)

## انواع کنترل دسترسی

در فلسک‌پی‌اچ‌پی می‌توانید از روش‌های مختلفی برای کنترل دسترسی استفاده کنید:

### 1. کنترل دسترسی مبتنی بر نقش (Role-Based Access Control)

در این روش، مجوزها بر اساس نقش‌های کاربران تعیین می‌شوند:

```php
/**
 * کلاس مدیریت نقش‌ها و مجوزها
 */
class RoleManager
{
    private $connection;
    
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

استفاده از مدیریت سیاست‌های دسترسی:

```php
// مسیر ویرایش پست
$app->put('/posts/{id}', function(Request $request, Response $response) {
    $user = $request->getAttribute('user');
    $postId = $request->param('id');
    
    // دریافت اطلاعات پست
    $post = Post::find($postId);
    
    if (!$post) {
        return $response->withStatus(404)->json([
            'error' => 'پست مورد نظر یافت نشد'
        ]);
    }
    
    // بررسی مجاز بودن دسترسی
    $policyManager = new PolicyManager();
    
    if (!$policyManager->authorizePost($user, $post->toArray(), 'edit')) {
        return $response->withStatus(403)->json([
            'error' => 'شما مجاز به ویرایش این پست نیستید'
        ]);
    }
    
    // ویرایش پست
    $data = $request->getParsedBody();
    $post->title = $data['title'];
    $post->content = $data['content'];
    $post->save();
    
    return $response->json([
        'message' => 'پست با موفقیت ویرایش شد',
        'post' => $post->toArray()
    ]);
})->middleware($authMiddleware);
```

## پیاده‌سازی یک سیستم کنترل دسترسی جامع

برای پیاده‌سازی یک سیستم کنترل دسترسی جامع، می‌توانید از ترکیب روش‌های فوق استفاده کنید. در ادامه یک مثال کامل ارائه شده
است:

```php
<?php
// authorization_system.php

require_once 'vendor/autoload.php';

use PHLask\App;
use PHLask\Http\Request;
use PHLask\Http\Response;
use PHLask\Database\Connection;

// ساختار پایگاه داده
/*
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL
);

CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    description VARCHAR(255),
    created_at DATETIME NOT NULL
);

CREATE TABLE user_roles (
    user_id INT NOT NULL,
    role_name VARCHAR(50) NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (user_id, role_name),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_name) REFERENCES roles(name) ON DELETE CASCADE
);

CREATE TABLE permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    description VARCHAR(255),
    created_at DATETIME NOT NULL
);

CREATE TABLE role_permissions (
    role_name VARCHAR(50) NOT NULL,
    permission_name VARCHAR(100) NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (role_name, permission_name),
    FOREIGN KEY (role_name) REFERENCES roles(name) ON DELETE CASCADE,
    FOREIGN KEY (permission_name) REFERENCES permissions(name) ON DELETE CASCADE
);

CREATE TABLE user_permissions (
    user_id INT NOT NULL,
    permission_name VARCHAR(100) NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (user_id, permission_name),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_name) REFERENCES permissions(name) ON DELETE CASCADE
);
*/

// کلاس مدیریت دسترسی
class AuthorizationManager
{
    private $connection;
    
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }
    
    /**
     * دریافت همه نقش‌های کاربر
     * 
     * @param int $userId شناسه کاربر
     * @return array
     */
    public function getUserRoles(int $userId): array
    {
        $sql = "SELECT role_name FROM user_roles WHERE user_id = :user_id";
        
        $roles = $this->connection->fetchAll($sql, [
            ':user_id' => $userId
        ]);
        
        return array_column($roles, 'role_name');
    }
    
    /**
     * دریافت همه مجوزهای کاربر
     * 
     * @param int $userId شناسه کاربر
     * @return array
     */
    public function getUserPermissions(int $userId): array
    {
        // مجوزهای مستقیم کاربر
        $sql1 = "SELECT permission_name FROM user_permissions WHERE user_id = :user_id";
        $directPermissions = $this->connection->fetchAll($sql1, [':user_id' => $userId]);
        
        // مجوزهای نقش‌های کاربر
        $sql2 = "SELECT rp.permission_name FROM user_roles ur
                JOIN role_permissions rp ON ur.role_name = rp.role_name
                WHERE ur.user_id = :user_id";
        $rolePermissions = $this->connection->fetchAll($sql2, [':user_id' => $userId]);
        
        // ترکیب و حذف تکراری‌ها
        $allPermissions = array_merge(
            array_column($directPermissions, 'permission_name'),
            array_column($rolePermissions, 'permission_name')
        );
        
        return array_unique($allPermissions);
    }
    
    /**
     * بررسی داشتن نقش
     * 
     * @param int $userId شناسه کاربر
     * @param string $role نقش مورد نظر
     * @return bool
     */
    public function hasRole(int $userId, string $role): bool
    {
        $roles = $this->getUserRoles($userId);
        return in_array($role, $roles);
    }
    
    /**
     * بررسی داشتن مجوز
     * 
     * @param int $userId شناسه کاربر
     * @param string $permission مجوز مورد نظر
     * @return bool
     */
    public function hasPermission(int $userId, string $permission): bool
    {
        $permissions = $this->getUserPermissions($userId);
        return in_array($permission, $permissions);
    }
    
    /**
     * افزودن نقش به کاربر
     * 
     * @param int $userId شناسه کاربر
     * @param string $role نقش
     * @return bool
     */
    public function assignRole(int $userId, string $role): bool
    {
        if ($this->hasRole($userId, $role)) {
            return true;
        }
        
        return $this->connection->insert('user_roles', [
            'user_id' => $userId,
            'role_name' => $role,
            'created_at' => date('Y-m-d H:i:s')
        ]) > 0;
    }
    
    /**
     * افزودن مجوز به کاربر
     * 
     * @param int $userId شناسه کاربر
     * @param string $permission مجوز
     * @return bool
     */
    public function grantPermission(int $userId, string $permission): bool
    {
        if ($this->hasPermission($userId, $permission)) {
            return true;
        }
        
        return $this->connection->insert('user_permissions', [
            'user_id' => $userId,
            'permission_name' => $permission,
            'created_at' => date('Y-m-d H:i:s')
        ]) > 0;
    }
    
    /**
     * افزودن مجوز به نقش
     * 
     * @param string $role نقش
     * @param string $permission مجوز
     * @return bool
     */
    public function grantPermissionToRole(string $role, string $permission): bool
    {
        $sql = "SELECT COUNT(*) AS count FROM role_permissions 
                WHERE role_name = :role AND permission_name = :permission";
        
        $result = $this->connection->fetchOne($sql, [
            ':role' => $role,
            ':permission' => $permission
        ]);
        
        if (($result['count'] ?? 0) > 0) {
            return true;
        }
        
        return $this->connection->insert('role_permissions', [
            'role_name' => $role,
            'permission_name' => $permission,
            'created_at' => date('Y-m-d H:i:s')
        ]) > 0;
    }
    
    /**
     * حذف نقش از کاربر
     * 
     * @param int $userId شناسه کاربر
     * @param string $role نقش
     * @return bool
     */
    public function removeRole(int $userId, string $role): bool
    {
        return $this->connection->delete(
            'user_roles',
            'user_id = :user_id AND role_name = :role',
            [
                ':user_id' => $userId,
                ':role' => $role
            ]
        ) > 0;
    }
    
    /**
     * حذف مجوز از کاربر
     * 
     * @param int $userId شناسه کاربر
     * @param string $permission مجوز
     * @return bool
     */
    public function revokePermission(int $userId, string $permission): bool
    {
        return $this->connection->delete(
            'user_permissions',
            'user_id = :user_id AND permission_name = :permission',
            [
                ':user_id' => $userId,
                ':permission' => $permission
            ]
        ) > 0;
    }
    
    /**
     * حذف مجوز از نقش
     * 
     * @param string $role نقش
     * @param string $permission مجوز
     * @return bool
     */
    public function revokePermissionFromRole(string $role, string $permission): bool
    {
        return $this->connection->delete(
            'role_permissions',
            'role_name = :role AND permission_name = :permission',
            [
                ':role' => $role,
                ':permission' => $permission
            ]
        ) > 0;
    }
    
    /**
     * ایجاد نقش جدید
     * 
     * @param string $name نام نقش
     * @param string $description توضیحات
     * @return bool
     */
    public function createRole(string $name, string $description = ''): bool
    {
        try {
            return $this->connection->insert('roles', [
                'name' => $name,
                'description' => $description,
                'created_at' => date('Y-m-d H:i:s')
            ]) > 0;
        } catch (\Exception $e) {
            // احتمالاً نقش قبلاً وجود دارد
            return false;
        }
    }
    
    /**
     * ایجاد مجوز جدید
     * 
     * @param string $name نام مجوز
     * @param string $description توضیحات
     * @return bool
     */
    public function createPermission(string $name, string $description = ''): bool
    {
        try {
            return $this->connection->insert('permissions', [
                'name' => $name,
                'description' => $description,
                'created_at' => date('Y-m-d H:i:s')
            ]) > 0;
        } catch (\Exception $e) {
            // احتمالاً مجوز قبلاً وجود دارد
            return false;
        }
    }
}

// میان‌افزارهای کنترل دسترسی
class AuthorizationMiddleware
{
    /**
     * میان‌افزار بررسی نقش
     * 
     * @param string|array $roles نقش یا نقش‌های مورد نیاز
     * @return callable
     */
    public static function requireRole($roles): callable
    {
        return function(Request $request, callable $next) use ($roles) {
            $user = $request->getAttribute('user');
            
            if (!$user) {
                return new Response(401, [], json_encode([
                    'error' => 'احراز هویت الزامی است'
                ]));
            }
            
            $authManager = new AuthorizationManager(Connection::connection());
            
            if (is_array($roles)) {
                $hasRole = false;
                foreach ($roles as $role) {
                    if ($authManager->hasRole($user['id'], $role)) {
                        $hasRole = true;
                        break;
                    }
                }
                
                if (!$hasRole) {
                    return new Response(403, [], json_encode([
                        'error' => 'شما دسترسی لازم برای این عملیات را ندارید'
                    ]));
                }
            } else {
                if (!$authManager->hasRole($user['id'], $roles)) {
                    return new Response(403, [], json_encode([
                        'error' => 'شما دسترسی لازم برای این عملیات را ندارید'
                    ]));
                }
            }
            
            return $next($request);
        };
    }
    
    /**
     * میان‌افزار بررسی مجوز
     * 
     * @param string|array $permissions مجوز یا مجوزهای مورد نیاز
     * @return callable
     */
    public static function requirePermission($permissions): callable
    {
        return function(Request $request, callable $next) use ($permissions) {
            $user = $request->getAttribute('user');
            
            if (!$user) {
                return new Response(401, [], json_encode([
                    'error' => 'احراز هویت الزامی است'
                ]));
            }
            
            $authManager = new AuthorizationManager(Connection::connection());
            
            if (is_array($permissions)) {
                $hasPermission = false;
                foreach ($permissions as $permission) {
                    if ($authManager->hasPermission($user['id'], $permission)) {
                        $hasPermission = true;
                        break;
                    }
                }
                
                if (!$hasPermission) {
                    return new Response(403, [], json_encode([
                        'error' => 'شما دسترسی لازم برای این عملیات را ندارید'
                    ]));
                }
            } else {
                if (!$authManager->hasPermission($user['id'], $permissions)) {
                    return new Response(403, [], json_encode([
                        'error' => 'شما دسترسی لازم برای این عملیات را ندارید'
                    ]));
                }
            }
            
            return $next($request);
        };
    }
}

// استفاده از سیستم کنترل دسترسی در برنامه
$app = App::getInstance();

// میان‌افزار احراز هویت (قبلاً تعریف شده)
$authMiddleware = function(Request $request, callable $next) {
    // دریافت توکن از هدر Authorization
    $token = $request->getHeaderLine('Authorization');
    
    // پیاده‌سازی احراز هویت
    // ...
    
    // افزودن اطلاعات کاربر به درخواست
    $request = $request->withAttribute('user', $user);
    
    return $next($request);
};

// مسیر پنل مدیریت (نیاز به نقش admin دارد)
$app->get('/admin/dashboard', function(Request $request, Response $response) {
    return $response->json([
        'message' => 'به پنل مدیریت خوش آمدید'
    ]);
})->middleware($authMiddleware)
  ->middleware(AuthorizationMiddleware::requireRole('admin'));

// مسیر ایجاد مقاله (نیاز به مجوز create_article دارد)
$app->post('/articles', function(Request $request, Response $response) {
    // ایجاد مقاله جدید
    return $response->json([
        'message' => 'مقاله با موفقیت ایجاد شد'
    ]);
})->middleware($authMiddleware)
  ->middleware(AuthorizationMiddleware::requirePermission('create_article'));

// مسیر مدیریت کاربران (نیاز به یکی از نقش‌های admin یا user_manager دارد)
$app->get('/users', function(Request $request, Response $response) {
    // دریافت لیست کاربران
    return $response->json([
        'message' => 'لیست کاربران'
    ]);
})->middleware($authMiddleware)
  ->middleware(AuthorizationMiddleware::requireRole(['admin', 'user_manager']));

// مسیر مدیریت نقش‌ها (نیاز به مجوز manage_roles دارد)
$app->post('/roles', function(Request $request, Response $response) {
    $data = $request->getParsedBody();
    
    // ایجاد نقش جدید
    $authManager = new AuthorizationManager(Connection::connection());
    $authManager->createRole($data['name'], $data['description'] ?? '');
    
    return $response->json([
        'message' => 'نقش با موفقیت ایجاد شد'
    ]);
})->middleware($authMiddleware)
  ->middleware(AuthorizationMiddleware::requirePermission('manage_roles'));

// مسیر افزودن نقش به کاربر (نیاز به مجوز assign_roles دارد)
$app->post('/users/{id}/roles', function(Request $request, Response $response) {
    $userId = $request->param('id');
    $data = $request->getParsedBody();
    
    // افزودن نقش به کاربر
    $authManager = new AuthorizationManager(Connection::connection());
    $authManager->assignRole($userId, $data['role']);
    
    return $response->json([
        'message' => 'نقش با موفقیت به کاربر اضافه شد'
    ]);
})->middleware($authMiddleware)
  ->middleware(AuthorizationMiddleware::requirePermission('assign_roles'));

// اجرای برنامه
$app->run();
```

## بهترین شیوه‌های کنترل دسترسی

برای پیاده‌سازی یک سیستم کنترل دسترسی موثر و امن، توصیه‌های زیر را در نظر بگیرید:

1. **اصل حداقل دسترسی**: به کاربران فقط دسترسی‌های مورد نیاز را بدهید.

2. **استفاده از نقش‌ها و مجوزها**: به جای اعطای مستقیم مجوزها به کاربران، از نقش‌ها استفاده کنید.

3. **جداسازی وظایف**: برای عملیات‌های حساس، سیستمی طراحی کنید که چند نفر باید آن را تأیید کنند.

4. **لاگ کردن فعالیت‌ها**: تمام تغییرات در دسترسی‌ها و استفاده از دسترسی‌های حساس را ثبت کنید.

5. **بازبینی منظم دسترسی‌ها**: دسترسی‌های کاربران را به صورت منظم بررسی و به‌روزرسانی کنید.

6. **کنترل دسترسی در لایه‌های مختلف**: کنترل دسترسی را هم در سطح API و هم در سطح پایگاه داده پیاده‌سازی کنید.

7. **تست امنیتی**: سیستم کنترل دسترسی را به طور مرتب تست کنید تا از عملکرد صحیح آن اطمینان حاصل کنید.

## جمع‌بندی

کنترل دسترسی یک بخش مهم در امنیت برنامه‌های وب است. در این بخش، با روش‌های مختلف پیاده‌سازی کنترل دسترسی در
فلسک‌پی‌اچ‌پی آشنا شدید:

1. **کنترل دسترسی مبتنی بر نقش (RBAC)**: دسترسی بر اساس نقش‌های کاربران تعیین می‌شود.
2. **کنترل دسترسی مبتنی بر مجوز (PBAC)**: دسترسی بر اساس مجوزهای مشخص تعیین می‌شود.
3. **کنترل دسترسی مبتنی بر منابع**: دسترسی به منابع بر اساس مالکیت یا قوانین خاص کنترل می‌شود.

شما می‌توانید از ترکیب این روش‌ها برای ایجاد یک سیستم کنترل دسترسی جامع و منعطف استفاده کنید.

مهم است که کنترل دسترسی را از همان ابتدای توسعه برنامه در نظر بگیرید و آن را به عنوان یک جنبه مداوم از مدیریت برنامه خود
در نظر بگیرید.

    /**
     * بررسی داشتن نقش
     * 
     * @param int $userId شناسه کاربر
     * @param string $role نقش مورد نظر
     * @return bool
     */
    public function hasRole(int $userId, string $role): bool
    {
        $sql = "SELECT COUNT(*) AS count FROM user_roles 
                WHERE user_id = :user_id AND role = :role";
        
        $result = $this->connection->fetchOne($sql, [
            ':user_id' => $userId,
            ':role' => $role
        ]);
        
        return ($result['count'] ?? 0) > 0;
    }
    
    /**
     * دریافت همه نقش‌های کاربر
     * 
     * @param int $userId شناسه کاربر
     * @return array
     */
    public function getUserRoles(int $userId): array
    {
        $sql = "SELECT role FROM user_roles WHERE user_id = :user_id";
        
        $roles = $this->connection->fetchAll($sql, [
            ':user_id' => $userId
        ]);
        
        return array_column($roles, 'role');
    }
    
    /**
     * افزودن نقش به کاربر
     * 
     * @param int $userId شناسه کاربر
     * @param string $role نقش
     * @return bool
     */
    public function assignRole(int $userId, string $role): bool
    {
        if ($this->hasRole($userId, $role)) {
            return true;
        }
        
        return $this->connection->insert('user_roles', [
            'user_id' => $userId,
            'role' => $role,
            'created_at' => date('Y-m-d H:i:s')
        ]) > 0;
    }
    
    /**
     * حذف نقش از کاربر
     * 
     * @param int $userId شناسه کاربر
     * @param string $role نقش
     * @return bool
     */
    public function removeRole(int $userId, string $role): bool
    {
        return $this->connection->delete(
            'user_roles',
            'user_id = :user_id AND role = :role',
            [
                ':user_id' => $userId,
                ':role' => $role
            ]
        ) > 0;
    }
    
    /**
     * بررسی داشتن حداقل یکی از نقش‌های مورد نظر
     * 
     * @param int $userId شناسه کاربر
     * @param array $roles نقش‌های مورد نظر
     * @return bool
     */
    public function hasAnyRole(int $userId, array $roles): bool
    {
        if (empty($roles)) {
            return false;
        }
        
        $placeholders = implode(',', array_fill(0, count($roles), '?'));
        
        $sql = "SELECT COUNT(*) AS count FROM user_roles 
                WHERE user_id = ? AND role IN ($placeholders)";
        
        $params = array_merge([$userId], $roles);
        
        $result = $this->connection->fetchOne($sql, $params);
        
        return ($result['count'] ?? 0) > 0;
    }
    
    /**
     * بررسی داشتن همه نقش‌های مورد نظر
     * 
     * @param int $userId شناسه کاربر
     * @param array $roles نقش‌های مورد نظر
     * @return bool
     */
    public function hasAllRoles(int $userId, array $roles): bool
    {
        if (empty($roles)) {
            return true;
        }
        
        $userRoles = $this->getUserRoles($userId);
        
        foreach ($roles as $role) {
            if (!in_array($role, $userRoles)) {
                return false;
            }
        }
        
        return true;
    }

}

```

استفاده از مدیریت نقش‌ها با میان‌افزار:

```php
// میان‌افزار بررسی نقش
$roleMiddleware = function(string $role) {
    return function(Request $request, callable $next) use ($role) {
        $user = $request->getAttribute('user');
        
        if (!$user) {
            return new Response(401, [], json_encode([
                'error' => 'احراز هویت الزامی است'
            ]));
        }
        
        $roleManager = new RoleManager(Connection::connection());
        
        if (!$roleManager->hasRole($user['id'], $role)) {
            return new Response(403, [], json_encode([
                'error' => 'شما دسترسی لازم برای این عملیات را ندارید'
            ]));
        }
        
        return $next($request);
    };
};

// نمونه استفاده از میان‌افزار نقش در مسیر
$app->get('/admin/dashboard', function(Request $request, Response $response) {
    return $response->json([
        'message' => 'به پنل مدیریت خوش آمدید'
    ]);
})->middleware($authMiddleware)
  ->middleware($roleMiddleware('admin'));
```

### 2. کنترل دسترسی مبتنی بر مجوز (Permission-Based Access Control)

در این روش، به جای نقش‌ها، مجوزهای مشخصی به کاربران داده می‌شود:

```php
/**
 * کلاس مدیریت مجوزها
 */
class PermissionManager
{
    private $connection;
    
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }
    
    /**
     * بررسی داشتن مجوز
     * 
     * @param int $userId شناسه کاربر
     * @param string $permission مجوز مورد نظر
     * @return bool
     */
    public function hasPermission(int $userId, string $permission): bool
    {
        // روش 1: بررسی مستقیم مجوزها
        $sql = "SELECT COUNT(*) AS count FROM user_permissions 
                WHERE user_id = :user_id AND permission = :permission";
        
        $result = $this->connection->fetchOne($sql, [
            ':user_id' => $userId,
            ':permission' => $permission
        ]);
        
        if (($result['count'] ?? 0) > 0) {
            return true;
        }
        
        // روش 2: بررسی مجوزهای نقش‌های کاربر
        $sql = "SELECT COUNT(*) AS count FROM user_roles ur
                JOIN role_permissions rp ON ur.role = rp.role
                WHERE ur.user_id = :user_id AND rp.permission = :permission";
        
        $result = $this->connection->fetchOne($sql, [
            ':user_id' => $userId,
            ':permission' => $permission
        ]);
        
        return ($result['count'] ?? 0) > 0;
    }
    
    /**
     * دریافت همه مجوزهای کاربر
     * 
     * @param int $userId شناسه کاربر
     * @return array
     */
    public function getUserPermissions(int $userId): array
    {
        // مجوزهای مستقیم کاربر
        $sql1 = "SELECT permission FROM user_permissions WHERE user_id = :user_id";
        $directPermissions = $this->connection->fetchAll($sql1, [':user_id' => $userId]);
        
        // مجوزهای نقش‌های کاربر
        $sql2 = "SELECT rp.permission FROM user_roles ur
                JOIN role_permissions rp ON ur.role = rp.role
                WHERE ur.user_id = :user_id";
        $rolePermissions = $this->connection->fetchAll($sql2, [':user_id' => $userId]);
        
        // ترکیب و حذف تکراری‌ها
        $allPermissions = array_merge(
            array_column($directPermissions, 'permission'),
            array_column($rolePermissions, 'permission')
        );
        
        return array_unique($allPermissions);
    }
    
    /**
     * افزودن مجوز به کاربر
     * 
     * @param int $userId شناسه کاربر
     * @param string $permission مجوز
     * @return bool
     */
    public function grantPermission(int $userId, string $permission): bool
    {
        if ($this->hasPermission($userId, $permission)) {
            return true;
        }
        
        return $this->connection->insert('user_permissions', [
            'user_id' => $userId,
            'permission' => $permission,
            'created_at' => date('Y-m-d H:i:s')
        ]) > 0;
    }
    
    /**
     * حذف مجوز از کاربر
     * 
     * @param int $userId شناسه کاربر
     * @param string $permission مجوز
     * @return bool
     */
    public function revokePermission(int $userId, string $permission): bool
    {
        return $this->connection->delete(
            'user_permissions',
            'user_id = :user_id AND permission = :permission',
            [
                ':user_id' => $userId,
                ':permission' => $permission
            ]
        ) > 0;
    }
    
    /**
     * بررسی داشتن حداقل یکی از مجوزهای مورد نظر
     * 
     * @param int $userId شناسه کاربر
     * @param array $permissions مجوزهای مورد نظر
     * @return bool
     */
    public function hasAnyPermission(int $userId, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($userId, $permission)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * بررسی داشتن همه مجوزهای مورد نظر
     * 
     * @param int $userId شناسه کاربر
     * @param array $permissions مجوزهای مورد نظر
     * @return bool
     */
    public function hasAllPermissions(int $userId, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($userId, $permission)) {
                return false;
            }
        }
        
        return true;
    }
}
```

استفاده از مدیریت مجوزها با میان‌افزار:

```php
// میان‌افزار بررسی مجوز
$permissionMiddleware = function(string $permission) {
    return function(Request $request, callable $next) use ($permission) {
        $user = $request->getAttribute('user');
        
        if (!$user) {
            return new Response(401, [], json_encode([
                'error' => 'احراز هویت الزامی است'
            ]));
        }
        
        $permissionManager = new PermissionManager(Connection::connection());
        
        if (!$permissionManager->hasPermission($user['id'], $permission)) {
            return new Response(403, [], json_encode([
                'error' => 'شما دسترسی لازم برای این عملیات را ندارید'
            ]));
        }
        
        return $next($request);
    };
};

// نمونه استفاده از میان‌افزار مجوز در مسیر
$app->post('/articles', function(Request $request, Response $response) {
    // ایجاد مقاله جدید
    return $response->json([
        'message' => 'مقاله با موفقیت ایجاد شد'
    ]);
})->middleware($authMiddleware)
  ->middleware($permissionMiddleware('create_article'));
```

### 3. کنترل دسترسی مبتنی بر منابع (Resource-Based Access Control)

در این روش، دسترسی به منابع (مثلاً پست‌ها، فایل‌ها و غیره) بر اساس مالکیت یا قوانین خاص کنترل می‌شود:

```php
/**
 * کلاس مدیریت سیاست‌های دسترسی
 */
class PolicyManager
{
    /**
     * بررسی مجاز بودن دسترسی به پست
     * 
     * @param array $user کاربر
     * @param array $post پست
     * @param string $action عملیات (view, edit, delete)
     * @return bool
     */
    public function authorizePost(array $user, array $post, string $action): bool
    {
        // مدیران به همه پست‌ها دسترسی دارند
        $roleManager = new RoleManager(Connection::connection());
        if ($roleManager->hasRole($user['id'], 'admin')) {
            return true;
        }
        
        // نویسنده‌ی پست می‌تواند آن را مشاهده، ویرایش و حذف کند
        if ($post['user_id'] == $user['id']) {
            return true;
        }
        
        // پست‌های عمومی قابل مشاهده توسط همه هستند
        if ($action === 'view' && $post['status'] === 'published') {
            return true;
        }
        
        // در غیر این صورت، دسترسی مجاز نیست
        return false;
    }
    
    /**
     * بررسی مجاز بودن دسترسی به نظر
     * 
     * @param array $user کاربر
     * @param array $comment نظر
     * @param string $action عملیات (view, edit, delete)
     * @return bool
     */
    public function authorizeComment(array $user, array $comment, string $action): bool
    {
        // مدیران به همه نظرات دسترسی دارند
        $roleManager = new RoleManager(Connection::connection());
        if ($roleManager->hasRole($user['id'], 'admin') || $roleManager->hasRole($user['id'], 'moderator')) {
            return true;
        }
        
        // نویسنده‌ی نظر می‌تواند آن را مشاهده، ویرایش و حذف کند
        if ($comment['user_id'] == $user['id']) {
            return true;
        }
        
        // مشاهده نظرات تایید شده برای همه مجاز است
        if ($action === 'view' && $comment['status'] === 'approved') {
            return true;
        }
        
        // در غیر این صورت، دسترسی مجاز نیست
        return false;
    }
    
    /**
     * بررسی مجاز بودن دسترسی به فایل
     * 
     * @param array $user کاربر
     * @param array $file فایل
     * @param string $action عملیات (view, download, edit, delete)
     * @return bool
     */
    public function authorizeFile(array $user, array $file, string $action): bool
    {
        // مدیران به همه فایل‌ها دسترسی دارند
        $roleManager = new RoleManager(Connection::connection());
        if ($roleManager->hasRole($user['id'], 'admin')) {
            return true;
        }
        
        // مالک فایل می‌تواند آن را مشاهده، دانلود، ویرایش و حذف کند
        if ($file['user_id'] == $user['id']) {
            return true;
        }
        
        // فایل‌های عمومی قابل مشاهده و دانلود توسط همه هستند
        if (($action === 'view' || $action === 'download') && $file['visibility'] === 'public') {
            return true;
        }
        
        // در غیر این صورت، دسترسی مجاز نیست
        return false;
    }
}