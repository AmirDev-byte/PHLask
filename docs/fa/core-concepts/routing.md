# مسیریابی (Routing)

مسیریابی یکی از مهم‌ترین بخش‌های هر فریمورک وب است. در فلسک‌پی‌اچ‌پی، سیستم مسیریابی سبک و انعطاف‌پذیری ارائه شده که به
شما امکان می‌دهد مسیرهای برنامه خود را به راحتی تعریف و مدیریت کنید.

## مفاهیم اولیه

مسیریابی در فلسک‌پی‌اچ‌پی، فرآیند تطبیق درخواست HTTP ورودی با یک تابع پاسخگو (handler) است. هر مسیر از دو بخش اصلی تشکیل
می‌شود:

1. **الگوی مسیر (Path Pattern)**: یک الگوی URI که با درخواست ورودی مطابقت داده می‌شود.
2. **تابع پاسخگو (Handler)**: تابعی که در صورت تطبیق الگو با درخواست، اجرا می‌شود.

## تعریف مسیرهای ساده

ساده‌ترین نوع مسیر، مسیری است که با یک URI ثابت تعریف می‌شود:

```php
$app->get('/about', function(Request $request, Response $response) {
    return $response->text('درباره ما');
});
```

این کد یک مسیر برای متد HTTP GET و مسیر `/about` تعریف می‌کند. زمانی که کاربر به این مسیر درخواست می‌دهد، تابع تعریف شده
اجرا می‌شود و متن "درباره ما" به عنوان پاسخ ارسال می‌شود.

## متدهای HTTP

فلسک‌پی‌اچ‌پی از همه متدهای HTTP استاندارد پشتیبانی می‌کند:

```php
// GET request
$app->get('/users', function(Request $request, Response $response) {
    // دریافت لیست کاربران
});

// POST request
$app->post('/users', function(Request $request, Response $response) {
    // ایجاد کاربر جدید
});

// PUT request
$app->put('/users/{id}', function(Request $request, Response $response) {
    // به‌روزرسانی کامل کاربر
});

// PATCH request
$app->patch('/users/{id}', function(Request $request, Response $response) {
    // به‌روزرسانی جزئی کاربر
});

// DELETE request
$app->delete('/users/{id}', function(Request $request, Response $response) {
    // حذف کاربر
});

// OPTIONS request
$app->options('/users', function(Request $request, Response $response) {
    // پاسخ به درخواست OPTIONS
});
```

## مسیرهای پارامتری

اکثر برنامه‌ها نیاز دارند که بخشی از مسیر به عنوان پارامتر باشد. مثلاً برای نمایش اطلاعات یک کاربر خاص، نیاز به شناسه آن
کاربر داریم. در فلسک‌پی‌اچ‌پی می‌توانید با قرار دادن نام پارامتر داخل آکولاد `{}` در مسیر، پارامترهای مسیر را تعریف
کنید:

```php
$app->get('/users/{id}', function(Request $request, Response $response) {
    $userId = $request->param('id');
    return $response->text("نمایش کاربر با شناسه: " . $userId);
});
```

### پارامترهای اختیاری

می‌توانید پارامترهای اختیاری نیز تعریف کنید. برای این کار، یک علامت سوال `?` بعد از نام پارامتر اضافه کنید:

```php
$app->get('/users/{id?}', function(Request $request, Response $response) {
    $userId = $request->param('id', 'all');
    
    if ($userId === 'all') {
        return $response->text("نمایش همه کاربران");
    }
    
    return $response->text("نمایش کاربر با شناسه: " . $userId);
});
```

در این مثال، اگر مسیر `/users` درخواست شود، مقدار پیش‌فرض `'all'` برای پارامتر `id` در نظر گرفته می‌شود.

### پارامترهای چندگانه

می‌توانید چندین پارامتر در یک مسیر داشته باشید:

```php
$app->get('/categories/{categoryId}/products/{productId}', function(Request $request, Response $response) {
    $categoryId = $request->param('categoryId');
    $productId = $request->param('productId');
    
    return $response->text("نمایش محصول {$productId} از دسته {$categoryId}");
});
```

## الگوهای پیچیده‌تر

### تطبیق براساس Regular Expression

در نسخه‌های جدید فلسک‌پی‌اچ‌پی، می‌توانید محدودیت‌های regular expression را برای پارامترها تعریف کنید:

```php
// فقط اعداد برای شناسه کاربر مجاز است
$router = $app->getRouter();
$router->addRoute('GET', '/users/{id:[0-9]+}', function(Request $request, Response $response) {
    $userId = $request->param('id');
    return $response->text("نمایش کاربر با شناسه عددی: " . $userId);
});

// فقط حروف برای نام کاربری مجاز است
$router->addRoute('GET', '/users/by-username/{username:[a-zA-Z]+}', function(Request $request, Response $response) {
    $username = $request->param('username');
    return $response->text("نمایش کاربر با نام کاربری: " . $username);
});
```

### تطبیق همه مسیرها

گاهی اوقات می‌خواهید یک مسیر را تعریف کنید که همه مسیرهای زیرمجموعه یک مسیر خاص را پوشش دهد. می‌توانید از یک پارامتر خاص
استفاده کنید:

```php
$app->get('/files/{path:.*}', function(Request $request, Response $response) {
    $path = $request->param('path');
    return $response->text("درخواست فایل: " . $path);
});
```

این مسیر با هر مسیری که با `/files/` شروع شود تطبیق داده می‌شود، مثلاً `/files/images/logo.png` یا
`/files/documents/report.pdf`.

## گروه‌بندی مسیرها

در برنامه‌های بزرگ‌تر، تعداد مسیرها می‌تواند زیاد شود. برای مدیریت بهتر، می‌توانید مسیرها را گروه‌بندی کنید.

> نکته: این ویژگی در نسخه‌های آینده فلسک‌پی‌اچ‌پی اضافه خواهد شد.

```php
$app->group('/api', function($group) {
    // مسیرهای API
    $group->get('/users', function(Request $request, Response $response) {
        // دریافت لیست کاربران
    });
    
    $group->get('/users/{id}', function(Request $request, Response $response) {
        // دریافت اطلاعات یک کاربر
    });
    
    // زیرگروه
    $group->group('/admin', function($adminGroup) {
        $adminGroup->get('/dashboard', function(Request $request, Response $response) {
            // داشبورد مدیریت
        });
    });
});
```

## نام‌گذاری مسیرها

نام‌گذاری مسیرها به شما کمک می‌کند در جاهای مختلف برنامه، به مسیرها ارجاع دهید بدون اینکه نیاز باشد مسیر دقیق را به خاطر
داشته باشید.

> نکته: این ویژگی در نسخه‌های آینده فلسک‌پی‌اچ‌پی اضافه خواهد شد.

```php
$app->get('/users/{id}', function(Request $request, Response $response) {
    $userId = $request->param('id');
    // ...
})->name('user.show');

// ایجاد URL برای مسیر نام‌گذاری شده
$url = $app->generateUrl('user.show', ['id' => 123]);
// نتیجه: /users/123
```

## مسیرهای محدود

گاهی نیاز دارید مسیرهایی را فقط در شرایط خاصی فعال کنید. مثلاً مسیرهایی که فقط در محیط توسعه قابل دسترسی هستند:

> نکته: این ویژگی در نسخه‌های آینده فلسک‌پی‌اچ‌پی اضافه خواهد شد.

```php
$app->get('/debug', function(Request $request, Response $response) {
    // نمایش اطلاعات دیباگ
})->condition(function() {
    return getenv('APP_ENV') === 'development';
});
```

## مسیرهای با Middleware

می‌توانید میان‌افزارهای خاص را فقط برای مسیرهای مشخص اعمال کنید. این به شما امکان می‌دهد عملیات پیش‌پردازش و پس‌پردازش
را فقط برای برخی مسیرها انجام دهید:

```php
// تعریف یک میان‌افزار احراز هویت
$authMiddleware = function(Request $request, callable $next) {
    $token = $request->getHeaderLine('Authorization');
    
    if (empty($token)) {
        return new Response(401, [], json_encode([
            'error' => 'احراز هویت الزامی است'
        ]));
    }
    
    // بررسی توکن و افزودن اطلاعات کاربر به درخواست
    $request = $request->withAttribute('user', ['id' => 1, 'name' => 'کاربر']);
    
    // ادامه زنجیره درخواست
    return $next($request);
};

// اعمال میان‌افزار برای یک مسیر خاص
$app->get('/profile', function(Request $request, Response $response) {
    $user = $request->getAttribute('user');
    return $response->json($user);
})->middleware($authMiddleware);

// یا در نسخه‌های فعلی به این صورت
$app->get('/profile', function(Request $request, Response $response) {
    $user = $request->getAttribute('user');
    return $response->json($user);
}, [$authMiddleware]);
```

## کلاس Router

در پشت صحنه، مدیریت مسیرها توسط کلاس `Router` انجام می‌شود. می‌توانید مستقیماً به این کلاس دسترسی داشته باشید:

```php
$router = $app->getRouter();
```

استفاده مستقیم از کلاس Router به شما امکانات بیشتری می‌دهد:

```php
// تعریف مسیر با کلاس Router
$router->addRoute('GET', '/custom-route', function(Request $request, Response $response) {
    return $response->text('مسیر سفارشی');
});

// دریافت لیست مسیرهای تعریف شده
$routes = $router->getRoutes();
```

## مسیریابی با کلاس‌های کنترلر

در پروژه‌های بزرگ‌تر، بهتر است از کلاس‌های کنترلر برای مدیریت منطق برنامه استفاده کنید. فلسک‌پی‌اچ‌پی به شما امکان
می‌دهد تا به جای توابع، از کلاس‌ها و متدهای آن‌ها به عنوان handler مسیر استفاده کنید:

```php
// تعریف یک کلاس کنترلر
class UserController
{
    public function index(Request $request, Response $response)
    {
        // دریافت لیست کاربران
        $users = (new \PHLask\Database\QueryBuilder('users'))->get();
        return $response->json($users);
    }
    
    public function show(Request $request, Response $response)
    {
        $id = $request->param('id');
        
        // دریافت اطلاعات کاربر
        $user = (new \PHLask\Database\QueryBuilder('users'))
            ->where('id', $id)
            ->first();
            
        if (!$user) {
            return $response->status(404)->json([
                'error' => 'کاربر یافت نشد'
            ]);
        }
        
        return $response->json($user);
    }
    
    public function store(Request $request, Response $response)
    {
        $data = $request->all();
        
        // اعتبارسنجی داده‌ها
        if (empty($data['name']) || empty($data['email'])) {
            return $response->status(400)->json([
                'error' => 'فیلدهای name و email الزامی هستند'
            ]);
        }
        
        // ایجاد کاربر جدید
        $id = (new \PLHask\Database\QueryBuilder('users'))->insert($data);
        
        return $response->status(201)->json([
            'message' => 'کاربر با موفقیت ایجاد شد',
            'id' => $id
        ]);
    }
}

// استفاده از کلاس کنترلر در مسیریابی
$app->get('/users', [UserController::class, 'index']);
$app->get('/users/{id}', [UserController::class, 'show']);
$app->post('/users', [UserController::class, 'store']);
```

## تعریف مسیرهای REST کامل

برای API‌های RESTful، معمولاً نیاز دارید مجموعه‌ای از مسیرها را برای یک منبع تعریف کنید. فلسک‌پی‌اچ‌پی قصد دارد در
نسخه‌های آینده این قابلیت را ارائه دهد:

```php
// تعریف مسیرهای REST برای منبع "users"
$app->resource('users', UserController::class);

// این دستور معادل تعریف مسیرهای زیر است:
// GET /users - UserController::index()
// GET /users/{id} - UserController::show()
// POST /users - UserController::store()
// PUT /users/{id} - UserController::update()
// DELETE /users/{id} - UserController::destroy()
```

## نکات و ترفندها

### استفاده از Regex در مسیرها

می‌توانید الگوهای regex پیچیده‌تری برای تطبیق مسیرها تعریف کنید:

```php
// یافتن محصول با کد محصول (فقط حروف و اعداد)
$app->get('/products/{code:[a-zA-Z0-9]+}', function(Request $request, Response $response) {
    $code = $request->param('code');
    return $response->text("محصول با کد: {$code}");
});

// مسیرهای تاریخی با فرمت yyyy/mm/dd
$app->get('/archive/{year:[0-9]{4}}/{month:[0-9]{2}}/{day:[0-9]{2}}', function(Request $request, Response $response) {
    $year = $request->param('year');
    $month = $request->param('month');
    $day = $request->param('day');
    
    return $response->text("آرشیو برای تاریخ: {$year}/{$month}/{$day}");
});
```

### مقادیر پیش‌فرض برای پارامترها

می‌توانید مقادیر پیش‌فرض برای پارامترها تعریف کنید:

```php
$app->get('/users/{page?}', function(Request $request, Response $response) {
    $page = $request->param('page', 1);
    return $response->text("لیست کاربران - صفحه " . $page);
});
```

### دریافت پارامترهای کوئری (Query Parameters)

علاوه بر پارامترهای مسیر، می‌توانید پارامترهای کوئری را نیز دریافت کنید:

```php
$app->get('/search', function(Request $request, Response $response) {
    $query = $request->query('q');
    $category = $request->query('category', 'all');
    
    return $response->text("جستجو برای: {$query} در دسته: {$category}");
});
```

با این مسیر، درخواست `/search?q=موبایل&category=electronics` منجر به نمایش "جستجو برای: موبایل در دسته: electronics"
می‌شود.

### انتقال پارامترها به اعضای تیم

می‌توانید پارامترهای مسیر، کوئری یا بدنه درخواست را به عنوان یک مجموعه دریافت کنید:

```php
$app->post('/orders', function(Request $request, Response $response) {
    // دریافت همه پارامترهای بدنه درخواست
    $data = $request->all();
    
    // دریافت همه پارامترهای مسیر
    $pathParams = $request->getParams();
    
    // دریافت همه پارامترهای کوئری
    $queryParams = $request->getQueryParams();
    
    // ...
});
```

### انتقال (Redirect)

گاهی اوقات نیاز دارید کاربر را به مسیر دیگری هدایت کنید:

```php
$app->get('/old-page', function(Request $request, Response $response) {
    return $response->redirect('/new-page', 301); // 301 Permanent Redirect
});

$app->post('/login', function(Request $request, Response $response) {
    // احراز هویت کاربر...
    
    if ($success) {
        return $response->redirect('/dashboard');
    } else {
        return $response->redirect('/login?error=1');
    }
});
```

## مقایسه با دیگر فریمورک‌ها

اگر با فریمورک‌های دیگر آشنا هستید، مقایسه زیر به شما کمک می‌کند تا با سیستم مسیریابی فلسک‌پی‌اچ‌پی بهتر آشنا شوید:

### فلسک پایتون

```python
# Flask (Python)
@app.route('/users/<user_id>')
def show_user(user_id):
    return f"User: {user_id}"

# PLHask
$app->get('/users/{userId}', function(Request $request, Response $response) {
    $userId = $request->param('userId');
    return $response->text("User: {$userId}");
});
```

### لاراول

```php
// Laravel
Route::get('/users/{id}', [UserController::class, 'show']);

// PLHask
$app->get('/users/{id}', [UserController::class, 'show']);
```

### اکسپرس (Node.js)

```javascript
// Express.js
app.get('/users/:id', (req, res) => {
    const userId = req.params.id;
    res.send(`User: ${userId}`);
});

// PLHask
$app->get('/users/{id}', function (Request
$request, Response
$response
)
{
    $userId = $request->param('id');
    return $response->text("User: {$userId}");
}
)
;
```

## عیب‌یابی مسیریابی

گاهی اوقات ممکن است با مشکلاتی در مسیریابی مواجه شوید. در اینجا چند مشکل رایج و راه‌حل آن‌ها آمده است:

### مسیر تطبیق داده نمی‌شود

اگر مسیر شما تطبیق داده نمی‌شود، موارد زیر را بررسی کنید:

1. **اسلش در ابتدا و انتها**: اطمینان حاصل کنید که مسیرها با اسلش `/` شروع می‌شوند اما با اسلش تمام نمی‌شوند (مگر اینکه
   مسیر اصلی `/` باشد).
2. **حساسیت به بزرگی و کوچکی حروف**: مسیرها به بزرگی و کوچکی حروف حساس هستند، بنابراین `/users` و `/Users` متفاوت هستند.
3. **الگوی regex**: اگر از الگوهای regex استفاده می‌کنید، اطمینان حاصل کنید که صحیح هستند.

### خطای 404 برای همه مسیرها

اگر همه درخواست‌ها با خطای 404 مواجه می‌شوند، موارد زیر را بررسی کنید:

1. **تنظیمات RewriteRule**: اگر از Apache استفاده می‌کنید، اطمینان حاصل کنید که فایل `.htaccess` درست تنظیم شده و
   `mod_rewrite` فعال است.
2. **تنظیمات Nginx**: اگر از Nginx استفاده می‌کنید، تنظیمات `try_files` را بررسی کنید.
3. **فراخوانی run()**: اطمینان حاصل کنید که متد `run()` در انتهای فایل `index.php` فراخوانی شده است.

### دسترسی به پارامترها

اگر نمی‌توانید به پارامترهای مسیر دسترسی پیدا کنید، موارد زیر را بررسی کنید:

1. **نام پارامتر**: اطمینان حاصل کنید که نام پارامتر در مسیر و هنگام استفاده از `param()` یکسان است.
2. **آکولاد**: اطمینان حاصل کنید که پارامترها درست با آکولاد مشخص شده‌اند: `{paramName}`.

## نمونه‌های کاربردی

### سیستم وبلاگ ساده

```php
// صفحه اصلی
$app->get('/', function(Request $request, Response $response) {
    // دریافت آخرین مقالات
    $posts = (new \PLHask\Database\QueryBuilder('posts'))
        ->orderBy('created_at', 'DESC')
        ->limit(5)
        ->get();
        
    // در واقعیت باید از یک سیستم تمپلیت استفاده کنید
    $html = '<h1>آخرین مقالات</h1><ul>';
    foreach ($posts as $post) {
        $html .= "<li><a href='/posts/{$post['id']}'>{$post['title']}</a></li>";
    }
    $html .= '</ul>';
    
    return $response->html($html);
});

// نمایش یک مقاله
$app->get('/posts/{id}', function(Request $request, Response $response) {
    $id = $request->param('id');
    
    // دریافت مقاله
    $post = (new \PLHask\Database\QueryBuilder('posts'))
        ->where('id', $id)
        ->first();
        
    if (!$post) {
        return $response->status(404)->html('<h1>مقاله یافت نشد</h1>');
    }
    
    $html = "<h1>{$post['title']}</h1>";
    $html .= "<div>{$post['content']}</div>";
    $html .= "<p>تاریخ انتشار: {$post['created_at']}</p>";
    $html .= "<a href='/'>بازگشت به صفحه اصلی</a>";
    
    return $response->html($html);
});

// دسته‌بندی‌ها
$app->get('/categories/{categorySlug}', function(Request $request, Response $response) {
    $categorySlug = $request->param('categorySlug');
    
    // دریافت دسته‌بندی
    $category = (new \PLHask\Database\QueryBuilder('categories'))
        ->where('slug', $categorySlug)
        ->first();
        
    if (!$category) {
        return $response->status(404)->html('<h1>دسته‌بندی یافت نشد</h1>');
    }
    
    // دریافت مقالات این دسته‌بندی
    $posts = (new \PLHask\Database\QueryBuilder('posts'))
        ->where('category_id', $category['id'])
        ->orderBy('created_at', 'DESC')
        ->get();
    
    $html = "<h1>مقالات دسته {$category['name']}</h1><ul>";
    foreach ($posts as $post) {
        $html .= "<li><a href='/posts/{$post['id']}'>{$post['title']}</a></li>";
    }
    $html .= '</ul>';
    $html .= "<a href='/'>بازگشت به صفحه اصلی</a>";
    
    return $response->html($html);
});
```

### API احراز هویت

```php
// ثبت‌نام کاربر جدید
$app->post('/api/register', function(Request $request, Response $response) {
    $data = $request->all();
    
    // اعتبارسنجی داده‌ها
    if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
        return $response->status(400)->json([
            'error' => 'همه فیلدها الزامی هستند'
        ]);
    }
    
    // بررسی تکراری بودن ایمیل
    $existingUser = (new \PLHask\Database\QueryBuilder('users'))
        ->where('email', $data['email'])
        ->first();
        
    if ($existingUser) {
        return $response->status(400)->json([
            'error' => 'ایمیل قبلاً ثبت شده است'
        ]);
    }
    
    // هش کردن رمز عبور
    $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
    
    // ایجاد کاربر جدید
    $userId = (new \PLHask\Database\QueryBuilder('users'))->insert($data);
    
    // ایجاد توکن (در واقعیت باید از JWT استفاده کنید)
    $token = bin2hex(random_bytes(32));
    
    return $response->status(201)->json([
        'message' => 'ثبت‌نام با موفقیت انجام شد',
        'token' => $token,
        'user' => [
            'id' => $userId,
            'name' => $data['name'],
            'email' => $data['email']
        ]
    ]);
});

// ورود کاربر
$app->post('/api/login', function(Request $request, Response $response) {
    $data = $request->all();
    
    // اعتبارسنجی داده‌ها
    if (empty($data['email']) || empty($data['password'])) {
        return $response->status(400)->json([
            'error' => 'ایمیل و رمز عبور الزامی هستند'
        ]);
    }
    
    // یافتن کاربر
    $user = (new \PLHask\Database\QueryBuilder('users'))
        ->where('email', $data['email'])
        ->first();
        
    if (!$user || !password_verify($data['password'], $user['password'])) {
        return $response->status(401)->json([
            'error' => 'ایمیل یا رمز عبور نادرست است'
        ]);
    }
    
    // ایجاد توکن (در واقعیت باید از JWT استفاده کنید)
    $token = bin2hex(random_bytes(32));
    
    return $response->json([
        'message' => 'ورود موفقیت‌آمیز',
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email']
        ]
    ]);
});
```

## گام بعدی

حالا که با مسیریابی در فلسک‌پی‌اچ‌پی آشنا شدید، می‌توانید:

- [میان‌افزارها](middleware.md) را مطالعه کنید تا با نحوه پردازش درخواست‌ها قبل و بعد از اجرای handler آشنا شوید.
- با [درخواست و پاسخ](request-response.md) بیشتر آشنا شوید تا بتوانید داده‌ها را بهتر مدیریت کنید.
- به مستندات [مدیریت خطا](error-handling.md) مراجعه کنید تا با نحوه مدیریت خطاها در برنامه خود آشنا شوید.