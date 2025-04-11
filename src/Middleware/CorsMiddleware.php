<?php

namespace PHLask\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * CorsMiddleware - میان‌افزار برای مدیریت CORS
 *
 * این میان‌افزار برای مدیریت درخواست‌های Cross-Origin Resource Sharing استفاده می‌شود
 */
class CorsMiddleware implements MiddlewareInterface
{
    /**
     * @var array تنظیمات CORS
     */
    private array $options;

    /**
     * سازنده کلاس CorsMiddleware
     *
     * @param array $options تنظیمات CORS
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge([
            'allowedOrigins' => ['*'],
            'allowedMethods' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],
            'allowedHeaders' => ['Content-Type', 'Authorization', 'Accept', 'X-Requested-With'],
            'exposedHeaders' => [],
            'maxAge' => 86400, // یک روز
            'allowCredentials' => false,
        ], $options);
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // اگر درخواست OPTIONS (preflight) است
        if ($request->getMethod() === 'OPTIONS') {
            return $this->handlePreflight($request);
        }

        // اجرای درخواست اصلی و افزودن هدرهای CORS به پاسخ
        $response = $handler->handle($request);
        return $this->addCorsHeaders($request, $response);
    }

    /**
     * مدیریت درخواست preflight (OPTIONS)
     *
     * @param ServerRequestInterface $request درخواست
     * @return ResponseInterface
     */
    private function handlePreflight(ServerRequestInterface $request): ResponseInterface
    {
        // ایجاد پاسخ خالی با کد 204
        $response = new \PHLask\Http\Response(204);

        // افزودن هدرهای CORS
        $response = $this->addCorsHeaders($request, $response);

        // افزودن هدرهای اضافی برای preflight
        $requestMethod = $request->getHeaderLine('Access-Control-Request-Method');
        if (!empty($requestMethod) && in_array($requestMethod, $this->options['allowedMethods'])) {
            $response = $response->withHeader('Access-Control-Allow-Methods', implode(', ', $this->options['allowedMethods']));
        }

        $requestHeaders = $request->getHeaderLine('Access-Control-Request-Headers');
        if (!empty($requestHeaders)) {
            $response = $response->withHeader('Access-Control-Allow-Headers', implode(', ', $this->options['allowedHeaders']));
        }

        // تنظیم مدت اعتبار پاسخ preflight
        $response = $response->withHeader('Access-Control-Max-Age', (string)$this->options['maxAge']);

        return $response;
    }

    /**
     * افزودن هدرهای CORS به پاسخ
     *
     * @param ServerRequestInterface $request درخواست
     * @param ResponseInterface $response پاسخ
     * @return ResponseInterface
     */
    private function addCorsHeaders(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // تنظیم Access-Control-Allow-Origin
        $origin = $request->getHeaderLine('Origin');
        if (empty($origin)) {
            return $response;
        }

        if (in_array('*', $this->options['allowedOrigins'])) {
            $response = $response->withHeader('Access-Control-Allow-Origin', '*');
        } elseif (in_array($origin, $this->options['allowedOrigins'])) {
            $response = $response->withHeader('Access-Control-Allow-Origin', $origin);
            $response = $response->withHeader('Vary', 'Origin');
        }

        // تنظیم Access-Control-Allow-Credentials
        if ($this->options['allowCredentials']) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        // تنظیم Access-Control-Expose-Headers
        if (!empty($this->options['exposedHeaders'])) {
            $response = $response->withHeader('Access-Control-Expose-Headers', implode(', ', $this->options['exposedHeaders']));
        }

        return $response;
    }
}