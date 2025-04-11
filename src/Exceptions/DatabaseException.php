<?php

namespace PHLask\Exceptions;

/**
 * DatabaseException - استثنای پایگاه داده
 *
 * این کلاس برای مدیریت خطاهای مرتبط با پایگاه داده استفاده می‌شود
 */
class DatabaseException extends \Exception
{
    /**
     * @var array|null اطلاعات اضافی خطا
     */
    private ?array $details;

    /**
     * @var string|null کوئری که باعث ایجاد خطا شده است
     */
    private ?string $query;

    /**
     * سازنده کلاس DatabaseException
     *
     * @param string $message پیام خطا
     * @param int $code کد خطا
     * @param string|null $query کوئری SQL
     * @param array|null $details اطلاعات اضافی
     * @param \Throwable|null $previous خطای قبلی
     */
    public function __construct(
        string      $message = '',
        int         $code = 0,
        ?string     $query = null,
        ?array      $details = null,
        ?\Throwable $previous = null
    )
    {
        $this->query = $query;
        $this->details = $details;

        parent::__construct($message, $code, $previous);
    }

    /**
     * ایجاد استثنا برای خطای اتصال به پایگاه داده
     *
     * @param string $message پیام خطا
     * @param int $code کد خطا
     * @param array|null $details اطلاعات اضافی
     * @param \Throwable|null $previous خطای قبلی
     * @return self
     */
    public static function connectionError(
        string      $message = 'Database connection error',
        int         $code = 0,
        ?array      $details = null,
        ?\Throwable $previous = null
    ): self
    {
        return new self($message, $code, null, $details, $previous);
    }

    /**
     * ایجاد استثنا برای خطای کوئری SQL
     *
     * @param string $message پیام خطا
     * @param string $query کوئری SQL
     * @param int $code کد خطا
     * @param array|null $details اطلاعات اضافی
     * @param \Throwable|null $previous خطای قبلی
     * @return self
     */
    public static function queryError(
        string      $message = 'Database query error',
        string      $query = '',
        int         $code = 0,
        ?array      $details = null,
        ?\Throwable $previous = null
    ): self
    {
        return new self($message, $code, $query, $details, $previous);
    }

    /**
     * دریافت کوئری SQL که باعث ایجاد خطا شده است
     *
     * @return string|null
     */
    public function getQuery(): ?string
    {
        return $this->query;
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
}