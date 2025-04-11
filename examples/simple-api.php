<?php
/**
 * مثال ساده استفاده از کتابخانه PHLask برای ساخت API
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PHLask\App;
use PHLask\Http\Request;
use PHLask\Http\Response;
use PHLask\Database\Connection;
use PHLask\Middleware\CorsMiddleware;
use PHLask\Exceptions\HttpException;

// تنظیمات پایگاه داده
$dbConfig = [
    'driver' => 'mysql',
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'test_db',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
];

// ایجاد اتصال به پایگاه داده
try {
    Connection::connection('default', $dbConfig);
} catch (\Exception $e) {
    // در صورت خطا در اتصال، پیام خطا را نمایش می‌دهیم
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// ایجاد نمونه اپلیکیشن
$app = App::getInstance();

// افزودن میان‌افزار CORS
$app->middleware(new CorsMiddleware([
    'allowedOrigins' => ['*'],
    'allowedMethods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    'allowedHeaders' => ['Content-Type', 'Authorization', 'Accept', 'X-Requested-With'],
    'allowCredentials' => true,
    'exposedHeaders' => [],
    'maxAge' => 86400, // یک روز
]));

// افزودن میان‌افزار احراز هویت
$app->middleware(function(Request $request, Response $response) {
    // مسیرهایی که نیاز به احراز هویت ندارند
    $publicRoutes = [
        '/',
        '/login',
        '/register',
    ];

    $path = $request->getUri()->getPath();

    // اگر مسیر عمومی است، اجازه دسترسی می‌دهیم
    if (in_array($path, $publicRoutes)) {
        return null;
    }

    // بررسی توکن احراز هویت
    $token = $request->getHeaderLine('Authorization');

    if (empty($token) || !preg_match('/^Bearer\s+(.+)$/', $token, $matches)) {
        throw HttpException::unauthorized('Authorization token is required');
    }

    $token = $matches[1];

    // در اینجا می‌توانید توکن را اعتبارسنجی کنید
    // مثال: بررسی توکن در دیتابیس یا JWT

    // برای مثال، فقط یک توکن ثابت را بررسی می‌کنیم
    if ($token !== 'secret-token-123') {
        throw HttpException::unauthorized('Invalid authorization token');
    }

    // می‌توانید اطلاعات کاربر را به درخواست اضافه کنید
    $user = [
        'id' => 1,
        'username' => 'test_user',
        'email' => 'user@example.com',
    ];

    return $request->withAttribute('user', $user);
});

// تعریف مسیرها

// صفحه اصلی
$app->get('/', function(Request $request, Response $response) {
    return $response->json([
        'message' => 'Welcome to PHLask API',
        'version' => '1.0.0',
    ]);
});

// مسیر ورود کاربر
$app->post('/login', function(Request $request, Response $response) {
    $username = $request->input('username');
    $password = $request->input('password');

    if (empty($username) || empty($password)) {
        throw HttpException::badRequest('Username and password are required');
    }

    // در اینجا می‌توانید اطلاعات کاربر را از دیتابیس بررسی کنید

    // برای مثال، فقط یک کاربر ثابت را بررسی می‌کنیم
    if ($username === 'admin' && $password === 'password') {
        return $response->json([
            'token' => 'secret-token-123',
            'user' => [
                'id' => 1,
                'username' => 'admin',
                'email' => 'admin@example.com',
            ],
        ]);
    }

    throw HttpException::unauthorized('Invalid username or password');
});

// دریافت اطلاعات کاربر جاری
$app->get('/user', function(Request $request, Response $response) {
    $user = $request->getAttribute('user');

    return $response->json($user);
});

// دریافت لیست کاربران
$app->get('/users', function(Request $request, Response $response) {
    // در اینجا می‌توانید لیست کاربران را از دیتابیس دریافت کنید

    // مثال استفاده از QueryBuilder
    $query = new \PHLask\Database\QueryBuilder('users');

    // اعمال فیلترها
    if ($request->query('name')) {
        $query->whereLike('name', '%' . $request->query('name') . '%');
    }

    if ($request->query('email')) {
        $query->whereLike('email', '%' . $request->query('email') . '%');
    }

    // اعمال مرتب‌سازی
    $sort = $request->query('sort', 'id');
    $order = $request->query('order', 'ASC');
    $query->orderBy($sort, $order);

    // اعمال صفحه‌بندی
    $page = (int) $request->query('page', 1);
    $perPage = (int) $request->query('per_page', 10);
    $query->paginate($page, $perPage);

    try {
        // دریافت تعداد کل کاربران
        $total = $query->count();

        // دریافت لیست کاربران
        $users = $query->get();

        return $response->json([
            'data' => $users,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($total / $perPage),
            ],
        ]);
    } catch (\Exception $e) {
        throw HttpException::internalServerError('Failed to fetch users: ' . $e->getMessage());
    }
});

// دریافت اطلاعات یک کاربر
$app->get('/users/{id}', function(Request $request, Response $response) {
    $id = $request->param('id');

    try {
        // مثال استفاده از QueryBuilder
        $user = (new \PHLask\Database\QueryBuilder('users'))
            ->where('id', $id)
            ->first();

        if (!$user) {
            throw HttpException::notFound('User not found');
        }

        return $response->json($user);
    } catch (\PHLask\Exceptions\HttpException $e) {
        throw $e;
    } catch (\Exception $e) {
        throw HttpException::internalServerError('Failed to fetch user: ' . $e->getMessage());
    }
});

// ایجاد کاربر جدید
$app->post('/users', function(Request $request, Response $response) {
    $data = $request->all();

    // اعتبارسنجی داده‌ها
    $required = ['name', 'email', 'password'];

    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw HttpException::badRequest("Field '{$field}' is required");
        }
    }

    try {
        // هش کردن رمز عبور
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

        // درج در دیتابیس
        $id = (new \PHLask\Database\QueryBuilder('users'))->insert($data);

        return $response->status(201)->json([
            'id' => $id,
            'message' => 'User created successfully',
        ]);
    } catch (\Exception $e) {
        throw HttpException::internalServerError('Failed to create user: ' . $e->getMessage());
    }
});

// به‌روزرسانی کاربر
$app->put('/users/{id}', function(Request $request, Response $response) {
    $id = $request->param('id');
    $data = $request->all();

    // حذف فیلدهای خالی
    $data = array_filter($data, function($value) {
        return $value !== null && $value !== '';
    });

    // اگر رمز عبور وجود دارد، آن را هش می‌کنیم
    if (isset($data['password'])) {
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
    }

    try {
        // بررسی وجود کاربر
        $exists = (new \PHLask\Database\QueryBuilder('users'))
            ->where('id', $id)
            ->exists();

        if (!$exists) {
            throw HttpException::notFound('User not found');
        }

        // به‌روزرسانی در دیتابیس
        $affected = (new \PHLask\Database\QueryBuilder('users'))
            ->where('id', $id)
            ->update($data);

        return $response->json([
            'id' => $id,
            'affected' => $affected,
            'message' => 'User updated successfully',
        ]);
    } catch (\PHLask\Exceptions\HttpException $e) {
        throw $e;
    } catch (\Exception $e) {
        throw HttpException::internalServerError('Failed to update user: ' . $e->getMessage());
    }
});

// حذف کاربر
$app->delete('/users/{id}', function(Request $request, Response $response) {
    $id = $request->param('id');

    try {
        // بررسی وجود کاربر
        $exists = (new \PHLask\Database\QueryBuilder('users'))
            ->where('id', $id)
            ->exists();

        if (!$exists) {
            throw HttpException::notFound('User not found');
        }

        // حذف از دیتابیس
        $affected = (new \PHLask\Database\QueryBuilder('users'))
            ->where('id', $id)
            ->delete();

        return $response->json([
            'id' => $id,
            'affected' => $affected,
            'message' => 'User deleted successfully',
        ]);
    } catch (\PHLask\Exceptions\HttpException $e) {
        throw $e;
    } catch (\Exception $e) {
        throw HttpException::internalServerError('Failed to delete user: ' . $e->getMessage());
    }
});

// مدیریت خطاهای HTTP
$app->errorHandler(404, function($error, Request $request, Response $response) {
    return $response->status(404)->json([
        'error' => 'Not Found',
        'message' => $error ? $error->getMessage() : 'The requested resource was not found',
    ]);
});

$app->errorHandler(401, function($error, Request $request, Response $response) {
    return $response->status(401)->json([
        'error' => 'Unauthorized',
        'message' => $error ? $error->getMessage() : 'Authentication is required',
    ]);
});

$app->errorHandler(400, function($error, Request $request, Response $response) {
    return $response->status(400)->json([
        'error' => 'Bad Request',
        'message' => $error ? $error->getMessage() : 'Invalid request data',
    ]);
});

$app->errorHandler(403, function($error, Request $request, Response $response) {
    return $response->status(403)->json([
        'error' => 'Forbidden',
        'message' => $error ? $error->getMessage() : 'You do not have permission to access this resource',
    ]);
});

$app->errorHandler(500, function($error, Request $request, Response $response) {
    return $response->status(500)->json([
        'error' => 'Internal Server Error',
        'message' => $error ? $error->getMessage() : 'An unexpected error occurred',
    ]);
});

// اجرای برنامه
$app->run();