<?php

namespace PHLask\Database;

use PDO;
use PDOException;
use PHLask\Exceptions\DatabaseException;

/**
 * Connection - کلاس اتصال به پایگاه داده
 *
 * این کلاس مسئول ایجاد و مدیریت اتصال به پایگاه داده است
 */
class Connection
{
    /**
     * @var array<string, self> اتصال‌های ایجاد شده
     */
    private static array $connections = [];

    /**
     * @var PDO نمونه PDO برای اتصال به پایگاه داده
     */
    private PDO $pdo;

    /**
     * @var array<string, mixed> تنظیمات اتصال
     */
    private array $config;

    /**
     * سازنده کلاس Connection
     *
     * @param array<string, mixed> $config تنظیمات اتصال
     * @throws DatabaseException در صورت خطا در اتصال
     */
    public function __construct(array $config)
    {
        $this->config = array_merge([
            'driver' => 'mysql',
            'host' => 'localhost',
            'port' => 3306,
            'database' => '',
            'username' => '',
            'password' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ], $config);

        $this->connect();
    }

    /**
     * ایجاد اتصال به پایگاه داده
     *
     * @throws DatabaseException در صورت خطا در اتصال
     */
    private function connect(): void
    {
        try {
            $dsn = $this->buildDsn();
            $this->pdo = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $this->config['options']
            );
        } catch (PDOException $e) {
            throw DatabaseException::connectionError(
                'Failed to connect to database: ' . $e->getMessage(),
                $e->getCode(),
                ['dsn' => $dsn ?? null],
                $e
            );
        }
    }

    /**
     * ایجاد DSN براساس پیکربندی
     *
     * @return string
     */
    private function buildDsn(): string
    {
        $driver = $this->config['driver'];

        return match ($driver) {
            'mysql' => sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $this->config['host'],
                $this->config['port'],
                $this->config['database'],
                $this->config['charset']
            ),
            'pgsql' => sprintf(
                'pgsql:host=%s;port=%d;dbname=%s',
                $this->config['host'],
                $this->config['port'],
                $this->config['database']
            ),
            'sqlite' => 'sqlite:' . $this->config['database'],
            default => throw new \InvalidArgumentException('Unsupported database driver: ' . $driver)
        };
    }

    /**
     * ایجاد یا دریافت اتصال
     *
     * @param string $name نام اتصال
     * @param array<string, mixed>|null $config پیکربندی اتصال (فقط برای ایجاد اتصال جدید)
     * @throws DatabaseException در صورت خطا در اتصال
     */
    public static function connection(string $name = 'default', ?array $config = null): self
    {
        if (!isset(self::$connections[$name])) {
            if ($config === null) {
                throw new \InvalidArgumentException("Configuration required for new connection: {$name}");
            }

            self::$connections[$name] = new self($config);
        }

        return self::$connections[$name];
    }

    /**
     * دریافت نمونه PDO
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * دریافت پیکربندی اتصال
     *
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * دریافت نتیجه تک سطری
     *
     * @param string $query کوئری SQL
     * @param array<string, mixed> $params پارامترهای کوئری
     * @return array<string, mixed>|null سطر نتیجه یا null در صورت عدم وجود
     * @throws DatabaseException در صورت خطا در اجرای کوئری
     */
    public function fetchOne(string $query, array $params = []): ?array
    {
        $statement = $this->query($query, $params);
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        return $result !== false ? $result : null;
    }

    /**
     * اجرای کوئری SQL
     *
     * @param string $query کوئری SQL
     * @param array<string, mixed> $params پارامترهای کوئری
     * @throws DatabaseException در صورت خطا در اجرای کوئری
     */
    public function query(string $query, array $params = []): \PDOStatement
    {
        try {
            $statement = $this->pdo->prepare($query);
            $statement->execute($params);
            return $statement;
        } catch (PDOException $e) {
            throw DatabaseException::queryError(
                'Query execution failed: ' . $e->getMessage(),
                $query,
                $e->getCode(),
                ['params' => $params],
                $e
            );
        }
    }

    /**
     * دریافت تمام نتایج
     *
     * @param string $query کوئری SQL
     * @param array<string, mixed> $params پارامترهای کوئری
     * @return array<int, array<string, mixed>> آرایه‌ای از سطرهای نتیجه
     * @throws DatabaseException در صورت خطا در اجرای کوئری
     */
    public function fetchAll(string $query, array $params = []): array
    {
        $statement = $this->query($query, $params);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * دریافت یک ستون از نتایج
     *
     * @param string $query کوئری SQL
     * @param array<string, mixed> $params پارامترهای کوئری
     * @param int $columnIndex شماره ستون (پیش‌فرض: 0)
     * @return array<int, mixed> آرایه‌ای از مقادیر ستون
     * @throws DatabaseException در صورت خطا در اجرای کوئری
     */
    public function fetchColumn(string $query, array $params = [], int $columnIndex = 0): array
    {
        $statement = $this->query($query, $params);
        $result = [];

        while ($row = $statement->fetch(PDO::FETCH_NUM)) {
            if (isset($row[$columnIndex])) {
                $result[] = $row[$columnIndex];
            }
        }

        return $result;
    }

    /**
     * درج داده‌ها در جدول
     *
     * @param string $table نام جدول
     * @param array<string, mixed> $data داده‌های برای درج
     * @return int آیدی آخرین رکورد درج شده
     * @throws DatabaseException در صورت خطا در اجرای کوئری
     */
    public function insert(string $table, array $data): int
    {
        if (empty($data)) {
            throw new \InvalidArgumentException('Data cannot be empty for insert operation');
        }

        $columns = array_keys($data);
        $placeholders = array_map(fn($column) => ':' . $column, $columns);

        $query = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $params = [];
        foreach ($data as $column => $value) {
            $params[':' . $column] = $value;
        }

        $this->query($query, $params);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * به‌روزرسانی داده‌ها در جدول
     *
     * @param string $table نام جدول
     * @param array<string, mixed> $data داده‌های برای به‌روزرسانی
     * @param string $where شرط به‌روزرسانی
     * @param array<string, mixed> $params پارامترهای شرط
     * @return int تعداد رکوردهای تغییر یافته
     * @throws DatabaseException در صورت خطا در اجرای کوئری
     */
    public function update(string $table, array $data, string $where, array $params = []): int
    {
        if (empty($data)) {
            throw new \InvalidArgumentException('Data cannot be empty for update operation');
        }

        $sets = [];
        $updateParams = [];

        foreach ($data as $column => $value) {
            $placeholder = ':update_' . $column;
            $sets[] = $column . ' = ' . $placeholder;
            $updateParams[$placeholder] = $value;
        }

        $query = sprintf(
            'UPDATE %s SET %s WHERE %s',
            $table,
            implode(', ', $sets),
            $where
        );

        $mergedParams = array_merge($updateParams, $params);
        $statement = $this->query($query, $mergedParams);

        return $statement->rowCount();
    }

    /**
     * حذف داده‌ها از جدول
     *
     * @param string $table نام جدول
     * @param string $where شرط حذف
     * @param array<string, mixed> $params پارامترهای شرط
     * @return int تعداد رکوردهای حذف شده
     * @throws DatabaseException در صورت خطا در اجرای کوئری
     */
    public function delete(string $table, string $where, array $params = []): int
    {
        $query = sprintf('DELETE FROM %s WHERE %s', $table, $where);
        $statement = $this->query($query, $params);

        return $statement->rowCount();
    }

    /**
     * اجرای تراکنش
     *
     * @template T
     * @param callable(self): T $callback تابع حاوی عملیات تراکنش
     * @return T نتیجه تابع callback
     * @throws \Exception در صورت خطا در اجرای تراکنش
     */
    public function transaction(callable $callback): mixed
    {
        try {
            $this->pdo->beginTransaction();
            $result = $callback($this);
            $this->pdo->commit();

            return $result;
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }
}