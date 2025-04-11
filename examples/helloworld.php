<?php
/**
 * مثال ساده Hello World با PHLask
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PHLask\App;
use PHLask\Http\Request;
use PHLask\Http\Response;

// ایجاد نمونه از برنامه
$app = new App();

// مسیر ساده
$app->get('/', function (Request $request, Response $response) {
    return $response->text('Hello World!');
});

// مسیر با پارامتر
$app->get('/hello/{name}', function (Request $request, Response $response) {
    $name = $request->param('name', 'Guest');
    return $response->text("Hello, {$name}!");
});

// برگرداندن JSON
$app->get('/api/info', function (Request $request, Response $response) {
    return $response->json([
        'name' => 'PHLask',
        'version' => '1.0.0',
        'author' => 'Your Name',
        'features' => [
            'Simple routing',
            'Optional middleware',
            'Optional database',
            'PSR-7 compatible'
        ]
    ]);
});

// میان‌افزار ساده
$app->middleware(function (Request $request, callable $next) {
    // قبل از اجرای درخواست
    $startTime = microtime(true);

    // اجرای میان‌افزار بعدی یا پاسخگو
    $response = $next($request);

    // بعد از اجرای درخواست
    $endTime = microtime(true);
    $executionTime = ($endTime - $startTime) * 1000; // میلی‌ثانیه

    // افزودن هدر زمان اجرا به پاسخ
    return $response->withHeader('X-Execution-Time', round($executionTime, 2) . 'ms');
});

// اجرای برنامه
$app->run();