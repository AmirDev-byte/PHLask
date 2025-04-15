<?php

namespace PHLask\Database;

/**
 * Model - کلاس پایه برای مدل‌های دیتابیس
 *
 * این کلاس یک لایه انتزاعی برای کار با جداول دیتابیس ارائه می‌دهد
 */
abstract class Model
{
    /**
     * @var string نام جدول
     */
    protected static string $table;

    /**
     * @var string کلید اصلی
     */
    protected static string $primaryKey = 'id';

    /**
     * @var bool آیا اتوماتیک زمان ایجاد و به‌روزرسانی ثبت شود
     */
    protected static bool $timestamps = true;

    /**
     * @var string نام ستون زمان ایجاد
     */
    protected static string $createdAt = 'created_at';

    /**
     * @var string نام ستون زمان به‌روزرسانی
     */
    protected static string $updatedAt = 'updated_at';

    /**
     * @var Connection|null اتصال پایگاه داده
     */
    protected static ?Connection $connection = null;

    /**
     * @var array<string, mixed> مقادیر ویژگی‌های مدل
     */
    protected array $attributes = [];

    /**
     * @var array<string, mixed> مقادیر اصلی (قبل از تغییر)
     */
    protected array $original = [];

    /**
     * @var bool آیا مدل جدید است
     */
    protected bool $exists = false;

    /**
     * سازنده کلاس Model
     *
     * @param array<string, mixed> $attributes ویژگی‌های اولیه
     * @param bool $exists آیا مدل در پایگاه داده وجود دارد
     */
    public function __construct(array $attributes = [], bool $exists = false)
    {
        $this->fill($attributes);
        $this->exists = $exists;

        if ($exists) {
            $this->original = $this->attributes;
        }
    }

    /**
     * تنظیم مقادیر ویژگی‌ها
     *
     * @param array<string, mixed> $attributes مقادیر ویژگی‌ها
     */
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }

        return $this;
    }

    /**
     * تنظیم یک ویژگی
     *
     * @param string $key نام ویژگی
     * @param mixed $value مقدار ویژگی
     */
    public function setAttribute(string $key, mixed $value): self
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    /**
     * دریافت همه رکوردها
     *
     * @return array<int, static>
     */
    public static function all(): array
    {
        $records = static::query()->get();

        return static::hydrate($records);
    }

    /**
     * شروع کوئری بیلدر برای این مدل
     */
    public static function query(): QueryBuilder
    {
        return new QueryBuilder(static::getTable(), static::getConnection());
    }

    /**
     * دریافت نام جدول
     */
    public static function getTable(): string
    {
        if (isset(static::$table)) {
            return static::$table;
        }

        // استخراج نام جدول از نام کلاس
        $className = (new \ReflectionClass(static::class))->getShortName();

        // تبدیل StudlyCase به snake_case و جمع بستن
        $table = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));

        // جمع بستن ساده (افزودن 's' به انتها)
        return $table . 's';
    }

    /**
     * دریافت اتصال پایگاه داده
     */
    public static function getConnection(): Connection
    {
        if (static::$connection === null) {
            static::$connection = Connection::connection();
        }

        return static::$connection;
    }

    /**
     * تنظیم اتصال پایگاه داده
     *
     * @param Connection $connection اتصال
     */
    public static function setConnection(Connection $connection): void
    {
        static::$connection = $connection;
    }

    /**
     * تبدیل رکوردهای دیتابیس به مدل‌ها
     *
     * @param array<int, array<string, mixed>> $records رکوردها
     * @return array<int, static>
     */
    protected static function hydrate(array $records): array
    {
        $models = [];

        foreach ($records as $record) {
            $models[] = new static($record, true);
        }

        return $models;
    }

    /**
     * یافتن یک رکورد با کلید اصلی
     *
     * @param mixed $id مقدار کلید اصلی
     */
    public static function find(mixed $id): ?self
    {
        $record = static::query()->where(static::$primaryKey, $id)->first();

        if (!$record) {
            return null;
        }

        return new static($record, true);
    }

    /**
     * یافتن اولین رکورد با شرط‌های داده شده
     *
     * @param string $column نام ستون
     * @param mixed $operator عملگر یا مقدار
     * @param mixed $value مقدار (اختیاری)
     */
    public static function findWhere(string $column, mixed $operator, mixed $value = null): ?self
    {
        $query = static::query();

        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $record = $query->where($column, $operator, $value)->first();

        if (!$record) {
            return null;
        }

        return new static($record, true);
    }

    /**
     * یافتن همه رکوردها با شرط‌های داده شده
     *
     * @param string $column نام ستون
     * @param mixed $operator عملگر یا مقدار
     * @param mixed $value مقدار (اختیاری)
     * @return array<int, static>
     */
    public static function findAllWhere(string $column, mixed $operator, mixed $value = null): array
    {
        $query = static::query();

        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $records = $query->where($column, $operator, $value)->get();

        return static::hydrate($records);
    }

    /**
     * ایجاد یک مدل جدید و ذخیره آن
     *
     * @param array<string, mixed> $attributes ویژگی‌ها
     */
    public static function create(array $attributes): self
    {
        $model = new static($attributes);
        $model->save();

        return $model;
    }

    /**
     * ذخیره مدل در پایگاه داده
     */
    public function save(): bool
    {
        if ($this->exists) {
            return $this->update();
        }

        return $this->insert();
    }

    /**
     * به‌روزرسانی مدل در پایگاه داده
     */
    protected function update(): bool
    {
        $dirty = $this->getDirty();

        if (empty($dirty)) {
            return true;
        }

        // افزودن زمان به‌روزرسانی
        if (static::$timestamps) {
            $dirty[static::$updatedAt] = date('Y-m-d H:i:s');
        }

        // به‌روزرسانی در پایگاه داده
        $updated = static::getConnection()->update(
            static::getTable(),
            $dirty,
            static::$primaryKey . ' = :id',
            [':id' => $this->getAttribute(static::$primaryKey)]
        );

        if ($updated) {
            $this->fill($dirty);
            $this->original = $this->attributes;
            return true;
        }

        return false;
    }

    /**
     * دریافت مقادیر تغییر یافته
     *
     * @return array<string, mixed>
     */
    public function getDirty(): array
    {
        $dirty = [];

        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $value !== $this->original[$key]) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    /**
     * دریافت یک ویژگی
     *
     * @param string $key نام ویژگی
     * @return mixed
     */
    public function getAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * درج مدل در پایگاه داده
     */
    protected function insert(): bool
    {
        $attributes = $this->attributes;

        // افزودن زمان ایجاد و به‌روزرسانی
        if (static::$timestamps) {
            $time = date('Y-m-d H:i:s');
            $attributes[static::$createdAt] = $time;
            $attributes[static::$updatedAt] = $time;
        }

        // درج در پایگاه داده
        $id = static::getConnection()->insert(static::getTable(), $attributes);

        if ($id) {
            $this->exists = true;
            $this->setAttribute(static::$primaryKey, $id);
            $this->original = $this->attributes;
            return true;
        }

        return false;
    }

    /**
     * بررسی تغییر یک ویژگی
     *
     * @param string|null $key نام ویژگی
     */
    public function isDirty(?string $key = null): bool
    {
        if ($key === null) {
            return $this->attributes != $this->original;
        }

        if (!array_key_exists($key, $this->original)) {
            return array_key_exists($key, $this->attributes);
        }

        return $this->attributes[$key] !== $this->original[$key];
    }

    /**
     * حذف مدل از پایگاه داده
     */
    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        $deleted = static::getConnection()->delete(
            static::getTable(),
            static::$primaryKey . ' = :id',
            [':id' => $this->getAttribute(static::$primaryKey)]
        );

        if ($deleted) {
            $this->exists = false;
            return true;
        }

        return false;
    }

    /**
     * تبدیل مدل به آرایه
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * دسترسی به ویژگی‌ها به صورت خصوصیت
     *
     * @param string $key نام ویژگی
     * @return mixed
     */
    public function __get(string $key): mixed
    {
        return $this->getAttribute($key);
    }

    /**
     * تنظیم ویژگی‌ها به صورت خصوصیت
     *
     * @param string $key نام ویژگی
     * @param mixed $value مقدار ویژگی
     */
    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }

    /**
     * بررسی وجود ویژگی
     *
     * @param string $key نام ویژگی
     */
    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    /**
     * حذف ویژگی
     *
     * @param string $key نام ویژگی
     */
    public function __unset(string $key): void
    {
        unset($this->attributes[$key]);
    }
}