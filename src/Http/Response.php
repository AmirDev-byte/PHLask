<?php

namespace PHLask\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Response - کلاس پاسخ HTTP
 *
 * پیاده‌سازی PSR-7 ResponseInterface
 */
class Response implements ResponseInterface
{
    /**
     * @var array لیست توضیحات وضعیت HTTP استاندارد
     */
    private const PHRASES = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        422 => 'Unprocessable Entity',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        511 => 'Network Authentication Required',
    ];
    /**
     * @var string نسخه پروتکل HTTP
     */
    private string $protocolVersion = '1.1';
    /**
     * @var array هدرهای پاسخ
     */
    private array $headers = [];
    /**
     * @var StreamInterface بدنه پاسخ
     */
    private StreamInterface $body;
    /**
     * @var int کد وضعیت HTTP
     */
    private int $statusCode;
    /**
     * @var string توضیح وضعیت HTTP
     */
    private string $reasonPhrase = '';

    /**
     * سازنده کلاس Response
     *
     * @param int $statusCode کد وضعیت
     * @param array $headers هدرها
     * @param StreamInterface|null $body بدنه
     * @param string $version نسخه پروتکل
     * @param string $reasonPhrase توضیح وضعیت
     */
    public function __construct(
        int              $statusCode = 200,
        array            $headers = [],
        ?StreamInterface $body = null,
        string           $version = '1.1',
        string           $reasonPhrase = ''
    )
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->body = $body ?? new Stream(fopen('php://temp', 'r+'));
        $this->protocolVersion = $version;
        $this->reasonPhrase = $reasonPhrase;

        if (empty($this->reasonPhrase) && isset(self::PHRASES[$statusCode])) {
            $this->reasonPhrase = self::PHRASES[$statusCode];
        }
    }

    /**
     * تنظیم پاسخ به صورت JSON
     *
     * @param mixed $data داده
     * @return Response
     */
    public function json($data): Response
    {
        $body = new Stream(fopen('php://temp', 'r+'));
        $body->write(json_encode($data, JSON_UNESCAPED_UNICODE));

        return $this
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withBody($body);
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
    public function withHeader($name, $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = is_array($value) ? $value : [$value];
        return $clone;
    }

    /**
     * تنظیم پاسخ به صورت متنی
     *
     * @param string $text متن
     * @return Response
     */
    public function text(string $text): Response
    {
        $body = new Stream(fopen('php://temp', 'r+'));
        $body->write($text);

        return $this
            ->withHeader('Content-Type', 'text/plain; charset=utf-8')
            ->withBody($body);
    }

    /**
     * تنظیم پاسخ به صورت HTML
     *
     * @param string $html کد HTML
     * @return Response
     */
    public function html(string $html): Response
    {
        $body = new Stream(fopen('php://temp', 'r+'));
        $body->write($html);

        return $this
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withBody($body);
    }

    // --------------------- پیاده‌سازی متدهای PSR-7 ---------------------

    /**
     * هدایت به مسیر دیگر
     *
     * @param string $url آدرس مقصد
     * @param int $statusCode کد وضعیت (پیش‌فرض 302)
     * @return Response
     */
    public function redirect(string $url, int $statusCode = 302): Response
    {
        return $this
            ->withStatus($statusCode)
            ->withHeader('Location', $url);
    }

    /**
     * @inheritDoc
     */
    public function withStatus($code, $reasonPhrase = ''): self
    {
        $clone = clone $this;
        $clone->statusCode = (int)$code;

        if (!empty($reasonPhrase)) {
            $clone->reasonPhrase = $reasonPhrase;
        } elseif (isset(self::PHRASES[$code])) {
            $clone->reasonPhrase = self::PHRASES[$code];
        } else {
            $clone->reasonPhrase = '';
        }

        return $clone;
    }

    /**
     * ارسال پاسخ به کاربر
     *
     * @return void
     */
    public function send(): void
    {
        // تنظیم هدرهای HTTP
        header(sprintf(
            'HTTP/%s %s %s',
            $this->protocolVersion,
            $this->statusCode,
            $this->reasonPhrase
        ));

        // تنظیم هدرهای اضافی
        foreach ($this->headers as $name => $values) {
            $values = is_array($values) ? $values : [$values];
            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), false);
            }
        }

        // چاپ بدنه پاسخ
        $body = $this->getBody();
        $body->rewind();
        echo $body->getContents();
        exit;
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
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @inheritDoc
     */
    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }
}