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
     * @var array لیست مسیرهای تعریف شده
     */
    private array $routes = [];

    /**
     * افزودن مسیر جدید به مسیریاب
     *
     * @param string $method متد HTTP
     * @param string $path مسیر
     * @param callable $handler تابع پاسخگو
     * @return void
     */
    public function addRoute(string $method, string $path, callable $handler): void
    {
        // اطمینان از وجود مسیر با / در ابتدا
        if ($path !== '/' && $path[0] !== '/') {
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
     * @return array|null اطلاعات مسیر تطبیق شده یا null در صورت عدم تطابق
     */
    public function match(string $method, string $path): ?array
    {
        // اطمینان از وجود / در ابتدای مسیر
        if ($path !== '/' && $path[0] !== '/') {
            $path = '/' . $path;
        }

        // حذف / اضافی از انتهای مسیر
        $path = rtrim($path, '/');
        if (empty($path)) {
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
                $params = array_filter($matches, function($key) {
                    return !is_numeric($key);
                }, ARRAY_FILTER_USE_KEY);

                return [$route['handler'], $params];
            }
        }

        return null;
    }

    /**
     * دریافت لیست تمام مسیرهای ثبت شده
     *
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}