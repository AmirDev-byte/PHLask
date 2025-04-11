<?php

namespace PHLask\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * MiddlewareHandler - کلاس اجرا‌کننده میان‌افزارها
 *
 * این کلاس مسئول مدیریت و اجرای میان‌افزارها است
 */
class MiddlewareHandler implements RequestHandlerInterface
{
    /**
     * @var array میان‌افزارها
     */
    private array $middlewares = [];

    /**
     * @var RequestHandlerInterface|null پاسخگوی نهایی
     */
    private ?RequestHandlerInterface $fallbackHandler = null;

    /**
     * افزودن میان‌افزار
     *
     * @param mixed $middleware میان‌افزار (کلاس PSR-15 یا تابع)
     * @return self
     */
    public function add($middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * بررسی وجود میان‌افزار
     *
     * @return bool
     */
    public function hasMiddlewares(): bool
    {
        return !empty($this->middlewares);
    }

    /**
     * دریافت لیست میان‌افزارها
     *
     * @return array
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * تنظیم پاسخگوی نهایی
     *
     * @param RequestHandlerInterface $handler پاسخگوی نهایی
     * @return self
     */
    public function setFallbackHandler(RequestHandlerInterface $handler): self
    {
        $this->fallbackHandler = $handler;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // اجرای اولین میان‌افزار
        return $this->processMiddleware(0, $request);
    }

    /**
     * اجرای میان‌افزار در موقعیت مشخص شده
     *
     * @param int $index موقعیت میان‌افزار
     * @param ServerRequestInterface $request درخواست
     * @return ResponseInterface
     */
    public function processMiddleware(int $index, ServerRequestInterface $request): ResponseInterface
    {
        // اگر میان‌افزار بیشتری موجود نیست، از پاسخگوی نهایی استفاده کنید
        if ($index >= count($this->middlewares)) {
            if ($this->fallbackHandler !== null) {
                return $this->fallbackHandler->handle($request);
            }

            throw new \RuntimeException('No middleware able to handle the request and no fallback handler provided');
        }

        $middleware = $this->middlewares[$index];

        // پاسخگوی درخواست بعدی
        $handler = new class($this, $index + 1) implements RequestHandlerInterface {
            private MiddlewareHandler $middlewareHandler;
            private int $nextIndex;

            public function __construct(MiddlewareHandler $middlewareHandler, int $nextIndex)
            {
                $this->middlewareHandler = $middlewareHandler;
                $this->nextIndex = $nextIndex;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->middlewareHandler->processMiddleware($this->nextIndex, $request);
            }
        };

        // اجرای میان‌افزار فعلی
        if ($middleware instanceof MiddlewareInterface) {
            // میان‌افزار PSR-15
            return $middleware->process($request, $handler);
        } elseif (is_callable($middleware)) {
            // تبدیل $handler به یک تابع قابل فراخوانی برای میان‌افزارهای ساده
            $next = function ($req) use ($handler) {
                return $handler->handle($req);
            };

            // تلاش برای اجرای میان‌افزار با فرمت ساده
            $result = call_user_func($middleware, $request, $next);

            // بررسی نتیجه میان‌افزار
            if (!$result instanceof ResponseInterface) {
                throw new \RuntimeException('Middleware must return a ResponseInterface instance');
            }

            return $result;
        }

        throw new \InvalidArgumentException('Middleware must be an instance of MiddlewareInterface or a callable');
    }
}