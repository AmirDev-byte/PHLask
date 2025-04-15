<?php
/**
 * SimpleFlask.php - کتابخانه بسیار ساده برای ساخت وب اپلیکیشن و کار با دیتابیس
 */

require_once __DIR__ . '/vendor/autoload.php';

/**
 * کلاس SimpleFlask - رابط ساده برای استفاده از PHLask
 */
class SimpleFlask
{
    /**
     * @var \PDO|null اتصال پایگاه داده
     */
    private ?\PDO $db = null;

    /**
     * @var array<string, array<string, callable>> مسیرهای تعریف شده
     */
    private array $routes = [];

    /**
     * @var array<string, mixed> متغیرهای قالب
     */
    private array $vars = [];

    /**
     * سازنده کلاس
     */
    public function __construct()
    {
        // در صورت نیاز، تنظیمات اولیه انجام می‌شود
    }

    /**
     * اتصال به پایگاه داده
     *
     * @param string $type نوع پایگاه داده (sqlite یا mysql)
     * @param array<string, mixed> $config تنظیمات اتصال
     */
    public function connectDB(string $type, array $config = []): self
    {
        $this->db = match ($type) {
            'sqlite' => new \PDO('sqlite:' . ($config['path'] ?? ':memory:')),
            'mysql' => new \PDO(
                sprintf(
                    "mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4",
                    $config['host'] ?? 'localhost',
                    $config['port'] ?? 3306,
                    $config['database'] ?? '',
                ),
                $config['username'] ?? 'root',
                $config['password'] ?? '',
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            ),
            default => throw new \InvalidArgumentException("Unsupported database type: {$type}")
        };

        return $this;
    }

    /**
     * تعریف یک مسیر GET
     *
     * @param string $path مسیر
     * @param callable $callback تابع پاسخگو
     */
    public function get(string $path, callable $callback): self
    {
        $this->routes['GET'][$path] = $callback;
        return $this;
    }

    /**
     * تعریف یک مسیر POST
     *
     * @param string $path مسیر
     * @param callable $callback تابع پاسخگو
     */
    public function post(string $path, callable $callback): self
    {
        $this->routes['POST'][$path] = $callback;
        return $this;
    }

    /**
     * دریافت داده از فرم
     *
     * @param string $name نام فیلد
     * @param mixed $default مقدار پیش‌فرض
     * @return mixed
     */
    public function input(string $name, mixed $default = null): mixed
    {
        return $_POST[$name] ?? $_GET[$name] ?? $default;
    }

    /**
     * تنظیم متغیر قالب
     *
     * @param string $name نام متغیر
     * @param mixed $value مقدار متغیر
     */
    public function set(string $name, mixed $value): self
    {
        $this->vars[$name] = $value;
        return $this;
    }

    /**
     * نمایش قالب
     *
     * @param string $template آدرس فایل قالب
     * @param array<string, mixed> $data داده‌های اضافی
     */
    public function view(string $template, array $data = []): string
    {
        // ترکیب داده‌های تنظیم شده با داده‌های ارسالی
        $data = array_merge($this->vars, $data);

        // استخراج متغیرها برای استفاده در قالب
        extract($data);

        // شروع بافر خروجی
        ob_start();

        // بارگذاری قالب
        include $template;

        // دریافت و پاکسازی بافر
        return ob_get_clean() ?: '';
    }

    /**
     * کار با جدول دیتابیس
     *
     * @param string $table نام جدول
     */
    public function table(string $table): TableHelper
    {
        if ($this->db === null) {
            throw new \Exception('Database connection is not established. Call connectDB() first.');
        }

        return new TableHelper($table, $this->db);
    }

    /**
     * اجرای یک کوئری خام SQL
     *
     * @param string $query کوئری SQL
     * @param array<string, mixed> $params پارامترها
     * @return array<int, array<string, mixed>> نتیجه کوئری
     */
    public function query(string $query, array $params = []): array
    {
        if ($this->db === null) {
            throw new \Exception('Database connection is not established. Call connectDB() first.');
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * شروع یک تراکنش
     */
    public function beginTransaction(): bool
    {
        if ($this->db === null) {
            throw new \Exception('Database connection is not established.');
        }

        return $this->db->beginTransaction();
    }

    /**
     * تایید تراکنش
     */
    public function commit(): bool
    {
        if ($this->db === null) {
            throw new \Exception('Database connection is not established.');
        }

        return $this->db->commit();
    }

    /**
     * بازگشت تراکنش
     */
    public function rollBack(): bool
    {
        if ($this->db === null) {
            throw new \Exception('Database connection is not established.');
        }

        return $this->db->rollBack();
    }

    /**
     * ایجاد هدایت (redirect)
     *
     * @param string $url آدرس مقصد
     * @return never
     */
    public function redirect(string $url): never
    {
        header("Location: {$url}");
        exit;
    }

    /**
     * ارسال پاسخ JSON
     *
     * @param mixed $data داده‌های مورد نظر
     * @return never
     */
    public function json(mixed $data): never
    {
        header('Content-Type: application/json');
        echo json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * اجرای برنامه
     */
    public function run(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        // بررسی وجود مسیر
        if (isset($this->routes[$method][$path])) {
            $callback = $this->routes[$method][$path];
            echo call_user_func($callback, $this);
        } else {
            // مسیر یافت نشد
            header('HTTP/1.0 404 Not Found');
            echo '404 Not Found';
        }
    }
}

/**
 * کلاس TableHelper - کمک برای کار با جدول دیتابیس
 */
class TableHelper
{
    /**
     * @var string نام جدول
     */
    private string $table;

    /**
     * @var \PDO اتصال پایگاه داده
     */
    private \PDO $db;

    /**
     * @var array<int, array{0: string, 1: mixed}> شرط‌های where
     */
    private array $wheres = [];

    /**
     * @var array<string, mixed> پارامترهای شرط‌ها
     */
    private array $params = [];

    /**
     * @var array<int, array{0: string, 1: string}>|null دستورات order by
     */
    private ?array $orders = null;

    /**
     * @var int|null محدود کردن نتایج
     */
    private ?int $limit = null;

    /**
     * سازنده کلاس
     *
     * @param string $table نام جدول
     * @param \PDO $db اتصال پایگاه داده
     */
    public function __construct(string $table, \PDO $db)
    {
        $this->table = $table;
        $this->db = $db;
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
        $this->orders[] = [$column, strtoupper($direction)];
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
        $sql = "SELECT * FROM {$this->table}";
        [$conditions, $params] = $this->buildWhere();

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        if (!empty($this->orders)) {
            $orderClauses = [];
            foreach ($this->orders as $order) {
                $orderClauses[] = "{$order[0]} {$order[1]}";
            }
            $sql .= " ORDER BY " . implode(', ', $orderClauses);
        }

        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        // بازنشانی بعد از اجرا
        $this->reset();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * ساخت بخش WHERE کوئری
     *
     * @return array{0: array<int, string>, 1: array<string, mixed>}
     */
    private function buildWhere(): array
    {
        if (empty($this->wheres)) {
            return [[], []];
        }

        $conditions = [];
        $params = [];

        foreach ($this->wheres as $index => $where) {
            $paramName = ":{$where[0]}_{$index}";
            $conditions[] = "{$where[0]} = {$paramName}";
            $params[$paramName] = $where[1];
        }

        return [$conditions, $params];
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
    }

    /**
     * به‌روزرسانی رکوردها
     *
     * @param array<string, mixed> $data داده‌ها
     * @return int تعداد رکوردهای به‌روزرسانی شده
     */
    public function update(array $data): int
    {
        [$conditions, $whereParams] = $this->buildWhere();

        if (empty($conditions)) {
            throw new \RuntimeException('Update requires at least one WHERE condition');
        }

        $sets = [];
        $params = [];

        foreach ($data as $key => $value) {
            $paramName = ":set_{$key}";
            $sets[] = "{$key} = {$paramName}";
            $params[$paramName] = $value;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets) .
            " WHERE " . implode(' AND ', $conditions);

        $stmt = $this->db->prepare($sql);
        $stmt->execute([...$params, ...$whereParams]);

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
        [$conditions, $params] = $this->buildWhere();

        if (empty($conditions)) {
            throw new \RuntimeException('Delete requires at least one WHERE condition');
        }

        $sql = "DELETE FROM {$this->table} WHERE " . implode(' AND ', $conditions);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        // بازنشانی بعد از اجرا
        $this->reset();

        return $stmt->rowCount();
    }

    /**
     * شمارش تعداد رکوردها
     */
    public function count(): int
    {
        $sql = "SELECT COUNT(*) AS count FROM {$this->table}";
        [$conditions, $params] = $this->buildWhere();

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        // بازنشانی بعد از اجرا
        $this->reset();

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
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

        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";

        $params = [];
        foreach ($data as $column => $value) {
            $params[":{$column}"] = $value;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$this->db->lastInsertId();
    }

    /**
     * یافتن یک رکورد با ID
     *
     * @param int|string $id شناسه
     * @return array<string, mixed>|null
     */
    public function find(int|string $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }
}