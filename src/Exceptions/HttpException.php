<?php

namespace PHLask\Exceptions;

/**
 * HttpException - استثنای HTTP
 *
 * این کلاس برای مدیریت خطاهای HTTP استفاده می‌شود
 */
class HttpException extends \Exception
{
    /**
     * @var int کد وضعیت HTTP
     */
    private int $statusCode;

    /**
     * @var array|null اطلاعات اضافی خطا
     */
    private ?array $details;

    /**
     * سازنده کلاس HttpException
     *
     * @param int $statusCode کد وضعیت HTTP
     * @param string $message پیام خطا
     * @param array|null $details اطلاعات اضافی
     * @param \Throwable|null $previous خطای قبلی
     */
    public function __construct(
        int $statusCode = 500,
        string $message = '',
        ?array $details = null,
        ?\Throwable $previous = null
    ) {
        $this->statusCode = $statusCode;
        $this->details = $details;

        parent::__construct($message, $statusCode, $previous);
    }

    /**
     * دریافت کد وضعیت HTTP
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * دریافت اطلاعات اضافی خطا
     *
     * @return array|null
     */
    public function getDetails(): ?array
    {
        return $this->details;
    }

    /**
     * ایجاد استثنا برای خطای 400 Bad Request
     *
     * @param string $message پیام خطا
     * @param array|null $details اطلاعات اضافی
     * @return self
     */
    public static function badRequest(string $message = 'Bad Request', ?array $details = null): self
    {
        return new self(400, $message, $details);
    }

    /**
     * ایجاد استثنا برای خطای 401 Unauthorized
     *
     * @param string $message پیام خطا
     * @param array|null $details اطلاعات اضافی
     * @return self
     */
    public static function unauthorized(string $message = 'Unauthorized', ?array $details = null): self
    {
        return new self(401, $message, $details);
    }

    /**
     * ایجاد استثنا برای خطای 403 Forbidden
     *
     * @param string $message پیام خطا
     * @param array|null $details اطلاعات اضافی
     * @return self
     */
    public static function forbidden(string $message = 'Forbidden', ?array $details = null): self
    {
        return new self(403, $message, $details);
    }

    /**
     * ایجاد استثنا برای خطای 404 Not Found
     *
     * @param string $message پیام خطا
     * @param array|null $details اطلاعات اضافی
     * @return self
     */
    public static function notFound(string $message = 'Not Found', ?array $details = null): self
    {
        return new self(404, $message, $details);
    }

    /**
     * ایجاد استثنا برای خطای 405 Method Not Allowed
     *
     * @param string $message پیام خطا
     * @param array|null $details اطلاعات اضافی
     * @return self
     */
    public static function methodNotAllowed(string $message = 'Method Not Allowed', ?array $details = null): self
    {
        return new self(405, $message, $details);
    }

    /**
     * ایجاد استثنا برای خطای 422 Unprocessable Entity
     *
     * @param string $message پیام خطا
     * @param array|null $details اطلاعات اضافی
     * @return self
     */
    public static function unprocessableEntity(string $message = 'Unprocessable Entity', ?array $details = null): self
    {
        return new self(422, $message, $details);
    }

    /**
     * ایجاد استثنا برای خطای 429 Too Many Requests
     *
     * @param string $message پیام خطا
     * @param array|null $details اطلاعات اضافی
     * @return self
     */
    public static function tooManyRequests(string $message = 'Too Many Requests', ?array $details = null): self
    {
        return new self(429, $message, $details);
    }

    /**
     * ایجاد استثنا برای خطای 500 Internal Server Error
     *
     * @param string $message پیام خطا
     * @param array|null $details اطلاعات اضافی
     * @return self
     */
    public static function internalServerError(string $message = 'Internal Server Error', ?array $details = null): self
    {
        return new self(500, $message, $details);
    }
}