<?php
/**
 * مثال استفاده از پایگاه داده در PHLask
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PHLask\App;
use PHLask\Http\Request;

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
$app->get('/users', function (Request $request) use ($app) {
    // استفاده از QueryBuilder
    $query = $app->db()->table('users');

    // اعمال فیلترها
    if ($request->query('name')) {
        $query->whereLike('name', '%' . $request->query('name') . '%');
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
$app->get('/users/{id}', function (Request $request) use ($app) {
    $id = $request->param('id');

    $user = $app->db()->table('users')
        ->where('id', $id)
        ->first();

    if (!$user) {
        throw new \PHLask\Exceptions\HttpException(404, 'کاربر یافت نشد');
    }

    return $user;
});

// ایجاد کاربر جدید
$app->post('/users', function (Request $request) use ($app) {
    $data = $request->all();

    // اعتبارسنجی داده‌ها
    if (empty($data['name']) || empty($data['email'])) {
        throw new \PHLask\Exceptions\HttpException(400, 'نام و ایمیل الزامی هستند');
    }

    // درج کاربر جدید
    $userId = $app->db()->table('users')->insert($data);

    return [
        'id' => $userId,
        'message' => 'کاربر با موفقیت ایجاد شد'
    ];
});

// به‌روزرسانی کاربر
$app->put('/users/{id}', function (Request $request) use ($app) {
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
$app->delete('/users/{id}', function (Request $request) use ($app) {
    $id = $request->param('id');

    $app->db()->table('users')
        ->where('id', $id)
        ->delete();

    return [
        'message' => 'کاربر با موفقیت حذف شد'
    ];
});

// اجرای برنامه
$app->run();