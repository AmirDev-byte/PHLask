<?php
/**
 * توابع کمکی برای PHLask
 */

if (!function_exists('env')) {
    /**
     * خواندن مقدار از متغیرهای محیطی
     *
     * @param string $key کلید متغیر محیطی
     * @param mixed $default مقدار پیش‌فرض
     * @return mixed
     */
    function env(string $key, $default = null)
    {
        $value = getenv($key);

        if ($value === false) {
            return $default;
        }

        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'null':
            case '(null)':
                return null;
            case 'empty':
            case '(empty)':
                return '';
        }

        return $value;
    }
}

if (!function_exists('view')) {
    /**
     * نمایش یک قالب
     *
     * @param string $template نام قالب
     * @param array $data داده‌های قالب
     * @return string
     */
    function view(string $template, array $data = []): string
    {
        $viewsPath = __DIR__ . '/../views/';
        $templatePath = $viewsPath . $template . '.php';

        if (!file_exists($templatePath)) {
            throw new \RuntimeException("View template not found: {$template}");
        }

        extract($data);

        ob_start();
        include $templatePath;
        return ob_get_clean();
    }
}

if (!function_exists('redirect')) {
    /**
     * هدایت به مسیر دیگر
     *
     * @param string $url مسیر مقصد
     * @param int $status کد وضعیت
     * @return \PHLask\Http\Response
     */
    function redirect(string $url, int $status = 302): \PHLask\Http\Response
    {
        $response = new \PHLask\Http\Response();
        return $response->redirect($url, $status);
    }
}

if (!function_exists('app')) {
    /**
     * دریافت نمونه برنامه
     *
     * @return \PHLask\App
     */
    function app(): \PHLask\App
    {
        return \PHLask\App::getInstance();
    }
}

if (!function_exists('db')) {
    /**
     * دریافت اتصال پایگاه داده
     *
     * @return \PHLask\Database\Connection
     */
    function db(): \PHLask\Database\Connection
    {
        return app()->db();
    }
}