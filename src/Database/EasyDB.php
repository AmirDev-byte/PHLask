<?php

namespace PHLask\Database;

/**
 * EasyDB - کتابخانه بسیار ساده برای کار با پایگاه داده
 */
class EasyDB
{
    /**
     * @var \PDO اتصال پایگاه داده
     */
    private \PDO $pdo;

    /**
     * @var array<string, Table> جداول تعریف شده
     */
    private array $tables = [];

    /**
     * سازنده کلاس EasyDB
     *
     * @param string $dsn آدرس اتصال به پایگاه داده
     * @param string|null $username نام کاربری (برای SQLite نیاز نیست)
     * @param string|null $password رمز عبور (برای SQLite نیاز نیست)
     * @param array<int, mixed> $options تنظیمات اضافی
     */
    public function __construct(string $dsn, ?string $username = null, ?string $password = null, array $options = [])
    {
        $defaultOptions = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false
        ];

        $this->pdo = new \PDO($dsn, $username, $password, array_merge($defaultOptions, $options));
    }

    /**
     * ایجاد اتصال به پایگاه داده SQLite
     *
     * @param string $path مسیر فایل SQLite
     */
    public static function sqlite(string $path): self
    {
        return new self('sqlite:' . $path);
    }

    /**
     * ایجاد اتصال به پایگاه داده MySQL
     *
     * @param string $database نام پایگاه داده
     * @param string $username نام کاربری
     * @param string $password رمز عبور
     * @param string $host نام هاست
     * @param int $port پورت
     */
    public static function mysql(
        string $database,
        string $username,
        string $password,
        string $host = 'localhost',
        int    $port = 3306
    ): self
    {
        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
        return new self($dsn, $username, $password);
    }

    /**
     * دسترسی به یک جدول
     *
     * @param string $name نام جدول
     */
    public function table(string $name): Table
    {
        if (!isset($this->tables[$name])) {
            $this->tables[$name] = new Table($name, $this->pdo);
        }
        return $this->tables[$name];
    }

    /**
     * اجرای کوئری خام
     *
     * @param string $query کوئری SQL
     * @param array<string, mixed> $params پارامترها
     * @return array<int, array<string, mixed>> نتایج
     */
    public function query(string $query, array $params = []): array
    {
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * شروع تراکنش
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * تایید تراکنش
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * بازگشت تراکنش
     */
    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }
}

/**
 * کلاس Table برای مدیریت یک جدول
 */
class Table
{
    /**
     * @var string نام جدول
     */
    private string $name;

    /**
     * @var \PDO اتصال پایگاه داده
     */
    private \PDO $pdo;

    /**
     * @var array<int, array{0: string, 1: mixed}> شرط‌های where
     */
    private array $wheres = [];

    /**
     * @var array<string, mixed> پارامترهای شرط‌ها
     */
    private array $params = [];

    /**
     * @var array<int, array{column: string, direction: string}>|null دستورات order by
     */
    private ?array $orders = null;

    /**
     * @var int|null محدود کردن نتایج
     */
    private ?int $limit = null;

    /**
     * @var int|null شروع نتایج
     */
    private ?int $offset = null;

    /**
     * سازنده کلاس Table
     *
     * @param string $name نام جدول
     * @param \PDO $pdo اتصال پایگاه داده
     */
    public function __construct(string $name, \PDO $pdo)
    {
        $this->name = $name;
        $this->pdo = $pdo;
    }

    /**
     * مرتب‌سازی نتایج
     *
     * @param string $column نام ستون
     * @param string $direction جهت مرتب‌سازی
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orders ??= [];
        $this->orders[] = [
            'column' => $column,
            'direction' => strtoupper($direction)
        ];
        return $this;
    }

    /**
     * تعیین شروع نتایج
     *
     * @param int $offset مقدار جابجایی
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * دریافت همه رکوردها
     *
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->get();
    }

    /**
     * دریافت نتایج براساس شرایط
     *
     * @return array<int, array<string, mixed>>
     */
    public function get(): array
    {
        $sql = "SELECT * FROM {$this->name}";
        [$whereSql, $params] = $this->buildWhere();

        if (!empty($whereSql)) {
            $sql .= " WHERE " . $whereSql;
        }

        if (!empty($this->orders)) {
            $orderClauses = [];
            foreach ($this->orders as $order) {
                $orderClauses[] = $order['column'] . ' ' . $order['direction'];
            }
            $sql .= " ORDER BY " . implode(', ', $orderClauses);
        }

        if ($this->limit !== null) {
            $sql .= " LIMIT " . $this->limit;
        }

        if ($this->offset !== null) {
            $sql .= " OFFSET " . $this->offset;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        // بازنشانی بعد از اجرا
        $this->reset();

        return $stmt->fetchAll();
    }

    /**
     * ساخت بخش WHERE کوئری
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildWhere(): array
    {
        if (empty($this->wheres)) {
            return ['', []];
        }

        $conditions = [];
        $params = [];

        foreach ($this->wheres as $index => $where) {
            $paramName = ":{$where[0]}_{$index}";
            $conditions[] = "{$where[0]} = {$paramName}";
            $params[$paramName] = $where[1];
        }

        return [implode(' AND ', $conditions), $params];
    }

    /**
     * بازنشانی شرایط
     */
    private function reset(): void
    {
        $this->wheres = [];
        $this->params = [];
        $this->orders = null;
        $this->limit = null;
        $this->offset = null;
    }

    /**
     * به‌روزرسانی رکوردها
     *
     * @param array<string, mixed> $data داده‌ها
     * @return int تعداد رکوردهای به‌روزرسانی شده
     */
    public function update(array $data): int
    {
        [$whereSql, $whereParams] = $this->buildWhere();

        if (empty($whereSql)) {
            throw new \RuntimeException('Update requires at least one WHERE condition');
        }

        $sets = [];
        $params = [];

        foreach ($data as $column => $value) {
            $placeholder = ":{$column}_update";
            $sets[] = "{$column} = {$placeholder}";
            $params[$placeholder] = $value;
        }

        $sql = "UPDATE {$this->name} SET " . implode(', ', $sets) . " WHERE " . $whereSql;
        $params = array_merge($params, $whereParams);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        // بازنشانی بعد از اجرا
        $this->reset();

        return $stmt->rowCount();
    }

    /**
     * حذف رکوردها
     *
     * @return int تعداد رکوردهای حذف شده
     */
    public function delete(): int
    {
        [$whereSql, $whereParams] = $this->buildWhere();

        if (empty($whereSql)) {
            throw new \RuntimeException('Delete requires at least one WHERE condition');
        }

        $sql = "DELETE FROM {$this->name} WHERE " . $whereSql;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($whereParams);

        // بازنشانی بعد از اجرا
        $this->reset();

        return $stmt->rowCount();
    }

    /**
     * شمارش تعداد رکوردها
     */
    public function count(): int
    {
        $sql = "SELECT COUNT(*) AS count FROM {$this->name}";
        [$whereSql, $params] = $this->buildWhere();

        if (!empty($whereSql)) {
            $sql .= " WHERE " . $whereSql;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        // بازنشانی بعد از اجرا
        $this->reset();

        $result = $stmt->fetch();
        return (int)($result['count'] ?? 0);
    }

    /**
     * یافتن یا ایجاد یک رکورد
     *
     * @param array<string, mixed> $search شرایط جستجو
     * @param array<string, mixed> $data داده‌های اضافی برای ایجاد
     * @return array<string, mixed>|null
     */
    public function firstOrCreate(array $search, array $data = []): ?array
    {
        foreach ($search as $column => $value) {
            $this->where($column, $value);
        }

        $record = $this->first();

        if ($record) {
            return $record;
        }

        $insertData = array_merge($search, $data);
        $id = $this->insert($insertData);
        return $this->find($id);
    }

    /**
     * افزودن شرط where
     *
     * @param string $column نام ستون
     * @param mixed $value مقدار
     */
    public function where(string $column, mixed $value): self
    {
        $this->wheres[] = [$column, $value];
        return $this;
    }

    /**
     * دریافت اولین رکورد
     *
     * @return array<string, mixed>|null
     */
    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->get();
        return !empty($results) ? $results[0] : null;
    }

    /**
     * محدود کردن نتایج
     *
     * @param int $limit تعداد
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * درج رکورد جدید
     *
     * @param array<string, mixed> $data داده‌ها
     * @return int ID رکورد جدید
     */
    public function insert(array $data): int
    {
        if (empty($data)) {
            return 0;
        }

        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => ":{$col}", $columns);

        $sql = "INSERT INTO {$this->name} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";

        $params = [];
        foreach ($data as $column => $value) {
            $params[":{$column}"] = $value;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * یافتن یک رکورد با ID
     *
     * @param int|string $id شناسه
     * @return array<string, mixed>|null
     */
    public function find(int|string $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->name} WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
}