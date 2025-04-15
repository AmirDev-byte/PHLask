<?php
/**
 * مثال استفاده از پایگاه داده در PHLask
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PHLask\App;
use PHLask\Exceptions\HttpException;
use PHLask\Http\Request;
use PHLask\Http\Response;

// ایجاد نمونه از برنامه
$app = new App();

// فعال‌سازی اتصال به پایگاه داده
$app->enableDatabase([
    'driver' => 'mysql',
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'test_db',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
]);

// دریافت لیست کاربران
$app->get('/users', function (Request $request) use ($app): array {
    // استفاده از QueryBuilder
    $query = $app->db()->table('users');

    // اعمال فیلترها
    if ($name = $request->query('name')) {
        $query->whereLike('name', "%{$name}%");
    }

    // اعمال مرتب‌سازی
    $query->orderBy('id', 'DESC');

    // اعمال صفحه‌بندی
    $page = (int)$request->query('page', 1);
    $perPage = (int)$request->query('per_page', 10);
    $query->paginate($page, $perPage);

    // اجرای کوئری
    $users = $query->get();
    $total = $query->count();

    return [
        'data' => $users,
        'meta' => [
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ]
    ];
});

// دریافت اطلاعات یک کاربر
$app->get('/users/{id}', function (Request $request) use ($app): array {
    $id = $request->param('id');

    $user = $app->db()->table('users')
        ->where('id', $id)
        ->first();

    if (!$user) {
        throw new HttpException(404, 'کاربر یافت نشد');
    }

    return $user;
});

// ایجاد کاربر جدید
$app->post('/users', function (Request $request) use ($app): array {
    $data = $request->all();

    // اعتبارسنجی داده‌ها
    if (empty($data['name']) || empty($data['email'])) {
        throw new HttpException(400, 'نام و ایمیل الزامی هستند');
    }

    // درج کاربر جدید
    $userId = $app->db()->table('users')->insert($data);

    return [
        'id' => $userId,
        'message' => 'کاربر با موفقیت ایجاد شد'
    ];
});

// به‌روزرسانی کاربر
$app->put('/users/{id}', function (Request $request) use ($app): array {
    $id = $request->param('id');
    $data = $request->all();

    // به‌روزرسانی کاربر
    $app->db()->table('users')
        ->where('id', $id)
        ->update($data);

    return [
        'message' => 'کاربر با موفقیت به‌روزرسانی شد'
    ];
});

// حذف کاربر
$app->delete('/users/{id}', function (Request $request) use ($app): array {
    $id = $request->param('id');

    $app->db()->table('users')
        ->where('id', $id)
        ->delete();

    return [
        'message' => 'کاربر با موفقیت حذف شد'
    ];
});

// مدیریت خطاها
$app->errorHandler(404, function (\Throwable $error) {
    return [
        'error' => 'Not Found',
        'message' => $error?->getMessage() ?? 'منبع درخواستی یافت نشد',
    ];
});

$app->errorHandler(400, function (\Throwable $error) {
    return [
        'error' => 'Bad Request',
        'message' => $error?->getMessage() ?? 'پارامترهای درخواست نامعتبر است',
    ];
});

$app->errorHandler(500, function (\Throwable $error) {
    return [
        'error' => 'Internal Server Error',
        'message' => $error?->getMessage() ?? 'خطای داخلی سرور',
        'details' => $_ENV['APP_DEBUG'] ?? false ? [
            'file' => $error?->getFile(),
            'line' => $error?->getLine(),
            'trace' => $error?->getTraceAsString(),
        ] : null,
    ];
});

// اجرای برنامه
$app->run();