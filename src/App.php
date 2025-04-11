<?php

namespace PHLask;

use PHLask\Http\Request;
use PHLask\Http\Response;
use PHLask\Middleware\MiddlewareHandler;
use PHLask\Exceptions\HttpException;
use Psr\Http\Message\ResponseInterface;
use Psr\Container\ContainerInterface;

/**
 * App - کلاس اصلی کتابخانه PHLask
 *
 * این کلاس مدیریت کلی برنامه، مسیریابی و اجرای درخواست‌ها را بر عهده دارد
 */
class App
{
    /**
     * @var Router مسیریاب برنامه
     */
    private Router $router;

    /**
     * @var MiddlewareHandler اجراکننده میان‌افزارها
     */
    private MiddlewareHandler $middlewareHandler;

    /**
     * @var ContainerInterface|null کانتینر وابستگی‌ها (اختیاری)
     */
    private ?ContainerInterface $container = null;

    /**
     * @var array مدیریت‌کننده‌های خطا
     */
    private array $errorHandlers = [];

    /**
     * @var App نمونه واحد (Singleton)
     */
    private static ?App $instance = null;

    /**
     * سازنده کلاس App
     */
    public function __construct()
    {
        $this->router = new Router();
        $this->middlewareHandler = new MiddlewareHandler();
    }

    /**
     * دریافت نمونه واحد از کلاس App (الگوی Singleton)
     *
     * @return App
     */
    public static function getInstance(): App
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * تنظیم کانتینر وابستگی‌ها (PSR-11)
     *
     * @param ContainerInterface $container
     * @return App
     */
    public function setContainer(ContainerInterface $container): App
    {
        $this->container = $container;
        return $this;
    }

    /**
     * دریافت کانتینر وابستگی‌ها
     *
     * @return ContainerInterface|null
     */
    public function getContainer(): ?ContainerInterface
    {
        return $this->container;
    }

    /**
     * دریافت مسیریاب برنامه
     *
     * @return Router
     */
    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * افزودن مسیر با متد GET
     *
     * @param string $path مسیر
     * @param callable $handler تابع پاسخگو
     * @return App
     */
    public function get(string $path, callable $handler): App
    {
        $this->router->addRoute('GET', $path, $handler);
        return $this;
    }

    /**
     * افزودن مسیر با متد POST
     *
     * @param string $path مسیر
     * @param callable $handler تابع پاسخگو
     * @return App
     */
    public function post(string $path, callable $handler): App
    {
        $this->router->addRoute('POST', $path, $handler);
        return $this;
    }

    /**
     * افزودن مسیر با متد PUT
     *
     * @param string $path مسیر
     * @param callable $handler تابع پاسخگو
     * @return App
     */
    public function put(string $path, callable $handler): App
    {
        $this->router->addRoute('PUT', $path, $handler);
        return $this;
    }

    /**
     * افزودن مسیر با متد DELETE
     *
     * @param string $path مسیر
     * @param callable $handler تابع پاسخگو
     * @return App
     */
    public function delete(string $path, callable $handler): App
    {
        $this->router->addRoute('DELETE', $path, $handler);
        return $this;
    }

    /**
     * افزودن مسیر با متد PATCH
     *
     * @param string $path مسیر
     * @param callable $handler تابع پاسخگو
     * @return App
     */
    public function patch(string $path, callable $handler): App
    {
        $this->router->addRoute('PATCH', $path, $handler);
        return $this;
    }

    /**
     * افزودن مسیر با متد OPTIONS
     *
     * @param string $path مسیر
     * @param callable $handler تابع پاسخگو
     * @return App
     */
    public function options(string $path, callable $handler): App
    {
        $this->router->addRoute('OPTIONS', $path, $handler);
        return $this;
    }

    /**
     * افزودن میان‌افزار به برنامه
     *
     * @param mixed $middleware میان‌افزار (پیاده‌سازی PSR-15 یا تابع)
     * @return App
     */
    public function middleware($middleware): App
    {
        $this->middlewareHandler->add($middleware);
        return $this;
    }

    /**
     * تنظیم مدیریت‌کننده خطا
     *
     * @param int $code کد خطا
     * @param callable $handler تابع مدیریت‌کننده خطا
     * @return App
     */
    public function errorHandler(int $code, callable $handler): App
    {
        $this->errorHandlers[$code] = $handler;
        return $this;
    }

    /**
     * مدیریت خطاهای HTTP
     *
     * @param int $code کد خطا
     * @param Request $request درخواست
     * @param \Throwable|null $exception استثنا (اختیاری)
     * @return ResponseInterface
     */
    private function handleError(int $code, Request $request, ?\Throwable $exception = null): ResponseInterface
    {
        $response = new Response();

        if (isset($this->errorHandlers[$code])) {
            $result = call_user_func($this->errorHandlers[$code], $exception, $request, $response);
            if ($result instanceof ResponseInterface) {
                return $result;
            }
        }

        // مدیریت پیش‌فرض خطا
        $message = $exception ? $exception->getMessage() : 'An error occurred';
        return $response->withStatus($code)->json([
            'error' => [
                'code' => $code,
                'message' => $message
            ]
        ]);
    }

    /**
     * پردازش پاسخ مسیر
     *
     * @param mixed $result نتیجه اجرای مسیر
     * @param Response $response شیء پاسخ
     * @return ResponseInterface
     */
    private function processRouteResult($result, Response $response): ResponseInterface
    {
        if ($result instanceof ResponseInterface) {
            return $result;
        }

        if (is_array($result) || is_object($result)) {
            return $response->json($result);
        }

        if (is_string($result)) {
            return $response->text($result);
        }

        return $response;
    }

    /**
     * اجرای برنامه و پاسخگویی به درخواست
     *
     * @return void
     */
    public function run(): void
    {
        $request = Request::fromGlobals();
        $response = new Response();

        try {
            // یافتن مسیر مناسب و اجرای آن
            $routeInfo = $this->router->match($request->getMethod(), $request->getUri()->getPath());

            if ($routeInfo === null) {
                // مسیر یافت نشد
                $this->handleError(404, $request, null)->send();
                return;
            }

            // استخراج پارامترهای مسیر
            [$handler, $params] = $routeInfo;
            $request = $request->withParams($params);

            // اجرای میان‌افزارها (اگر وجود داشته باشند)
            if ($this->middlewareHandler->hasMiddlewares()) {
                // تعریف handler نهایی که route handler را اجرا می‌کند
                $finalHandler = new class($handler, $response) implements \Psr\Http\Server\RequestHandlerInterface {
                    private $routeHandler;
                    private $response;

                    public function __construct(callable $routeHandler, $response) {
                        $this->routeHandler = $routeHandler;
                        $this->response = $response;
                    }

                    public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface {
                        $result = call_user_func($this->routeHandler, $request, $this->response);

                        if ($result instanceof \Psr\Http\Message\ResponseInterface) {
                            return $result;
                        }

                        if (is_array($result) || is_object($result)) {
                            return $this->response->json($result);
                        }

                        if (is_string($result)) {
                            return $this->response->text($result);
                        }

                        return $this->response;
                    }
                };

                // تنظیم handler نهایی و اجرای میان‌افزارها
                $this->middlewareHandler->setFallbackHandler($finalHandler);
                $response = $this->middlewareHandler->handle($request);
                $response->send();
            } else {
                // اجرای پاسخگو (handler) مسیر بدون میان‌افزار
                $result = call_user_func($handler, $request, $response);

                // بررسی نتیجه و ارسال پاسخ مناسب
                $this->processRouteResult($result, $response)->send();
            }

        } catch (HttpException $e) {
            // خطاهای HTTP شناخته شده
            $this->handleError($e->getStatusCode(), $request, $e)->send();
        } catch (\Throwable $e) {
            // سایر خطاها
            $this->handleError(500, $request, $e)->send();
        }
    }
}