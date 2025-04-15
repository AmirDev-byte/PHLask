<?php

namespace PHLask;

/**
 * Router - کلاس مسیریاب برنامه
 *
 * این کلاس مسئول مدیریت مسیرها و تطبیق مسیر درخواستی با مسیرهای تعریف شده است
 */
class Router
{
    /**
     * @var array<array{method: string, path: string, pattern: string, handler: callable}> لیست مسیرهای تعریف شده
     */
    private array $routes = [];

    /**
     * افزودن مسیر جدید به مسیریاب
     *
     * @param string $method متد HTTP
     * @param string $path مسیر
     * @param callable $handler تابع پاسخگو
     */
    public function addRoute(string $method, string $path, callable $handler): void
    {
        // اطمینان از وجود مسیر با / در ابتدا
        if ($path !== '/' && !str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        // تبدیل پارامترهای مسیر به الگوی regex
        $pattern = $this->buildPatternFromPath($path);

        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'pattern' => $pattern,
            'handler' => $handler
        ];
    }

    /**
     * تبدیل مسیر به الگوی regex
     *
     * @param string $path مسیر
     * @return string الگوی regex
     */
    private function buildPatternFromPath(string $path): string
    {
        // تبدیل پارامترهای {param} به الگوی (?P<param>[^/]+)
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        // پشتیبانی از پارامترهای اختیاری {param?}
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\?\}/', '(?P<$1>[^/]*)(?:/|$)', $pattern);

        return "#^" . $pattern . "$#";
    }

    /**
     * بررسی تطابق مسیر درخواستی با مسیرهای تعریف شده
     *
     * @param string $method متد HTTP
     * @param string $path مسیر درخواستی
     * @return array{0: callable, 1: array<string, string>}|null اطلاعات مسیر تطبیق شده یا null در صورت عدم تطابق
     */
    public function match(string $method, string $path): ?array
    {
        // اطمینان از وجود / در ابتدای مسیر
        if ($path !== '/' && !str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        // حذف / اضافی از انتهای مسیر
        $path = rtrim($path, '/');
        if ($path === '') {
            $path = '/';
        }

        foreach ($this->routes as $route) {
            // بررسی تطابق متد
            if ($route['method'] !== $method) {
                continue;
            }

            // بررسی تطابق مسیر
            if (preg_match($route['pattern'], $path, $matches)) {
                // حذف کلیدهای عددی از نتیجه تطبیق
                $params = array_filter($matches, fn($key) => !is_numeric($key), ARRAY_FILTER_USE_KEY);

                return [$route['handler'], $params];
            }
        }

        return null;
    }

    /**
     * دریافت لیست تمام مسیرهای ثبت شده
     *
     * @return array<array{method: string, path: string, pattern: string, handler: callable}>
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}