<?php

namespace PHLask\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Request - کلاس درخواست HTTP
 *
 * پیاده‌سازی PSR-7 ServerRequestInterface
 */
class Request implements ServerRequestInterface
{
    /**
     * @var string نسخه پروتکل HTTP
     */
    private string $protocolVersion = '1.1';

    /**
     * @var array هدرهای درخواست
     */
    private array $headers = [];

    /**
     * @var StreamInterface بدنه درخواست
     */
    private StreamInterface $body;

    /**
     * @var string متد درخواست
     */
    private string $method;

    /**
     * @var UriInterface آدرس درخواست
     */
    private UriInterface $uri;

    /**
     * @var array پارامترهای مسیر
     */
    private array $params = [];

    /**
     * @var array پارامترهای کوئری
     */
    private array $queryParams = [];

    /**
     * @var array|object|null بدنه پردازش شده
     */
    private $parsedBody = null;

    /**
     * @var array پارامترهای کوکی
     */
    private array $cookieParams = [];

    /**
     * @var array فایل‌های آپلود شده
     */
    private array $uploadedFiles = [];

    /**
     * @var array ویژگی‌های سرور
     */
    private array $serverParams = [];

    /**
     * @var array ویژگی‌های درخواست
     */
    private array $attributes = [];

    /**
     * سازنده کلاس Request
     *
     * @param string $method متد HTTP
     * @param UriInterface $uri آدرس درخواست
     * @param array $headers هدرهای درخواست
     * @param StreamInterface $body بدنه درخواست
     * @param string $version نسخه پروتکل
     */
    public function __construct(
        string           $method,
        UriInterface     $uri,
        array            $headers = [],
        ?StreamInterface $body = null,
        string           $version = '1.1'
    )
    {
        $this->method = $method;
        $this->uri = $uri;
        $this->headers = $headers;
        $this->body = $body ?? new Stream(fopen('php://temp', 'r+'));
        $this->protocolVersion = $version;
    }

    /**
     * ایجاد درخواست از متغیرهای سراسری PHP
     *
     * @return Request
     */
    public static function fromGlobals(): Request
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = Uri::fromGlobals();

        // استخراج هدرها
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $name = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                $headers[$name] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $name = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower($key))));
                $headers[$name] = $value;
            }
        }

        // ایجاد نمونه درخواست
        $request = new self($method, $uri, $headers, new Stream(fopen('php://input', 'r')));

        // تنظیم پارامترهای دیگر
        return $request
            ->withQueryParams($_GET)
            ->withCookieParams($_COOKIE)
            ->withServerParams($_SERVER)
            ->withParsedBody($_POST);
    }

    /**
     * @inheritDoc
     */
    public function withParsedBody($data): self
    {
        $clone = clone $this;
        $clone->parsedBody = $data;
        return $clone;
    }

    /**
     * تنظیم پارامترهای سرور
     *
     * @param array $serverParams
     * @return self
     */
    public function withServerParams(array $serverParams): self
    {
        $clone = clone $this;
        $clone->serverParams = $serverParams;
        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function withCookieParams(array $cookies): self
    {
        $clone = clone $this;
        $clone->cookieParams = $cookies;
        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function withQueryParams(array $query): self
    {
        $clone = clone $this;
        $clone->queryParams = $query;
        return $clone;
    }

    /**
     * تنظیم پارامترهای مسیر
     *
     * @param array $params پارامترهای مسیر
     * @return Request
     */
    public function withParams(array $params): Request
    {
        $clone = clone $this;
        $clone->params = $params;
        return $clone;
    }

    /**
     * دریافت پارامترهای مسیر
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * دریافت یک پارامتر مسیر
     *
     * @param string $name نام پارامتر
     * @param mixed $default مقدار پیش‌فرض
     * @return mixed
     */
    public function param(string $name, $default = null)
    {
        return $this->params[$name] ?? $default;
    }

    /**
     * دریافت یک پارامتر از بدنه درخواست
     *
     * @param string $name نام پارامتر
     * @param mixed $default مقدار پیش‌فرض
     * @return mixed
     */
    public function input(string $name, $default = null)
    {
        if (is_array($this->parsedBody)) {
            return $this->parsedBody[$name] ?? $default;
        } elseif (is_object($this->parsedBody)) {
            return property_exists($this->parsedBody, $name) ? $this->parsedBody->$name : $default;
        }

        return $default;
    }

    // --------------------- پیاده‌سازی متدهای PSR-7 ---------------------

    /**
     * دریافت تمام داده‌های بدنه درخواست
     *
     * @return array|object|null
     */
    public function all()
    {
        return $this->parsedBody;
    }

    /**
     * دریافت یک پارامتر از کوئری استرینگ
     *
     * @param string $name نام پارامتر
     * @param mixed $default مقدار پیش‌فرض
     * @return mixed
     */
    public function query(string $name, $default = null)
    {
        return $this->queryParams[$name] ?? $default;
    }

    /**
     * بررسی اینکه درخواست از نوع AJAX است
     *
     * @return bool
     */
    public function isAjax(): bool
    {
        return $this->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * @inheritDoc
     */
    public function getHeaderLine($name): string
    {
        $header = $this->getHeader($name);
        if (empty($header)) {
            return '';
        }
        return implode(', ', $header);
    }

    /**
     * @inheritDoc
     */
    public function getHeader($name): array
    {
        $name = strtolower($name);
        foreach ($this->headers as $key => $value) {
            if (strtolower($key) === $name) {
                return is_array($value) ? $value : [$value];
            }
        }
        return [];
    }

    /**
     * بررسی اینکه درخواست از نوع JSON است
     *
     * @return bool
     */
    public function isJson(): bool
    {
        $contentType = $this->getHeaderLine('Content-Type');
        return strpos($contentType, 'application/json') !== false;
    }

    /**
     * @inheritDoc
     */
    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    /**
     * @inheritDoc
     */
    public function withProtocolVersion($version): self
    {
        $clone = clone $this;
        $clone->protocolVersion = $version;
        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @inheritDoc
     */
    public function withAddedHeader($name, $value): self
    {
        if (!$this->hasHeader($name)) {
            return $this->withHeader($name, $value);
        }

        $clone = clone $this;
        $values = $this->getHeader($name);
        $values = array_merge($values, is_array($value) ? $value : [$value]);

        foreach ($clone->headers as $key => $existingValue) {
            if (strtolower($key) === strtolower($name)) {
                $clone->headers[$key] = $values;
                return $clone;
            }
        }

        $clone->headers[$name] = $values;
        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function hasHeader($name): bool
    {
        $name = strtolower($name);
        foreach ($this->headers as $key => $value) {
            if (strtolower($key) === $name) {
                return true;
            }
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function withHeader($name, $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = is_array($value) ? $value : [$value];
        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function withoutHeader($name): self
    {
        $clone = clone $this;
        $lowerName = strtolower($name);

        foreach ($clone->headers as $key => $value) {
            if (strtolower($key) === $lowerName) {
                unset($clone->headers[$key]);
                return $clone;
            }
        }

        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    /**
     * @inheritDoc
     */
    public function withBody(StreamInterface $body): self
    {
        $clone = clone $this;
        $clone->body = $body;
        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function getRequestTarget(): string
    {
        $target = $this->uri->getPath();
        if (empty($target)) {
            $target = '/';
        }

        $query = $this->uri->getQuery();
        if (!empty($query)) {
            $target .= '?' . $query;
        }

        return $target;
    }

    /**
     * @inheritDoc
     */
    public function withRequestTarget($requestTarget): self
    {
        $clone = clone $this;
        // در این پیاده‌سازی ساده، این متد پشتیبانی نمی‌شود
        // زیرا URI خود را از requestTarget استخراج نمی‌کنیم
        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @inheritDoc
     */
    public function withMethod($method): self
    {
        $clone = clone $this;
        $clone->method = $method;
        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    /**
     * @inheritDoc
     */
    public function withUri(UriInterface $uri, $preserveHost = false): self
    {
        $clone = clone $this;
        $clone->uri = $uri;

        if ($preserveHost && $this->hasHeader('Host')) {
            return $clone;
        }

        $host = $uri->getHost();
        if (empty($host)) {
            return $clone;
        }

        $port = $uri->getPort();
        if ($port !== null) {
            $host .= ':' . $port;
        }

        return $clone->withHeader('Host', $host);
    }

    /**
     * @inheritDoc
     */
    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    /**
     * @inheritDoc
     */
    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    /**
     * @inheritDoc
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    /**
     * @inheritDoc
     */
    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    /**
     * @inheritDoc
     */
    public function withUploadedFiles(array $uploadedFiles): self
    {
        $clone = clone $this;
        $clone->uploadedFiles = $uploadedFiles;
        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    /**
     * @inheritDoc
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @inheritDoc
     */
    public function getAttribute($name, $default = null)
    {
        if (!array_key_exists($name, $this->attributes)) {
            return $default;
        }

        return $this->attributes[$name];
    }

    /**
     * @inheritDoc
     */
    public function withAttribute($name, $value): self
    {
        $clone = clone $this;
        $clone->attributes[$name] = $value;
        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function withoutAttribute($name): self
    {
        if (!array_key_exists($name, $this->attributes)) {
            return $this;
        }

        $clone = clone $this;
        unset($clone->attributes[$name]);
        return $clone;
    }
}