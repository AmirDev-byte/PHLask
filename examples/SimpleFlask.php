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
     * @var \PDO اتصال پایگاه داده
     */
    private $db = null;

    /**
     * @var array مسیرهای تعریف شده
     */
    private $routes = [];

    /**
     * @var array متغیرهای قالب
     */
    private $vars = [];

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
     * @param array $config تنظیمات اتصال
     * @return $this
     */
    public function connectDB($type, $config = [])
    {
        if ($type === 'sqlite') {
            $path = $config['path'] ?? ':memory:';
            $this->db = new \PDO('sqlite:' . $path);
        } else if ($type === 'mysql') {
            $host = $config['host'] ?? 'localhost';
            $dbname = $config['database'] ?? '';
            $username = $config['username'] ?? 'root';
            $password = $config['password'] ?? '';
            $port = $config['port'] ?? 3306;

            $this->db = new \PDO(
                "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
                $username,
                $password,
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
        }

        return $this;
    }

    /**
     * تعریف یک مسیر GET
     *
     * @param string $path مسیر
     * @param callable $callback تابع پاسخگو
     * @return $this
     */
    public function get($path, $callback)
    {
        $this->routes['GET'][$path] = $callback;
        return $this;
    }

    /**
     * تعریف یک مسیر POST
     *
     * @param string $path مسیر
     * @param callable $callback تابع پاسخگو
     * @return $this
     */
    public function post($path, $callback)
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
    public function input($name, $default = null)
    {
        return $_POST[$name] ?? $_GET[$name] ?? $default;
    }

    /**
     * تنظیم متغیر قالب
     *
     * @param string $name نام متغیر
     * @param mixed $value مقدار متغیر
     * @return $this
     */
    public function set($name, $value)
    {
        $this->vars[$name] = $value;
        return $this;
    }

    /**
     * نمایش قالب
     *
     * @param string $template آدرس فایل قالب
     * @param array $data داده‌های اضافی
     * @return string
     */
    public function view($template, $data = [])
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
        return ob_get_clean();
    }

    /**
     * کار با جدول دیتابیس
     *
     * @param string $table نام جدول
     * @return TableHelper
     */
    public function table($table)
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
     * @param array $params پارامترها
     * @return array نتیجه کوئری
     */
    public function query($query, $params = [])
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
     *
     * @return bool
     */
    public function beginTransaction()
    {
        return $this->db->beginTransaction();
    }

    /**
     * تایید تراکنش
     *
     * @return bool
     */
    public function commit()
    {
        return $this->db->commit();
    }

    /**
     * بازگشت تراکنش
     *
     * @return bool
     */
    public function rollBack()
    {
        return $this->db->rollBack();
    }

    /**
     * ایجاد هدایت (redirect)
     *
     * @param string $url آدرس مقصد
     */
    public function redirect($url)
    {
        header("Location: {$url}");
        exit;
    }

    /**
     * ارسال پاسخ JSON
     *
     * @param mixed $data داده‌های مورد نظر
     */
    public function json($data)
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * اجرای برنامه
     */
    public function run()
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

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
    private $table;

    /**
     * @var \PDO اتصال پایگاه داده
     */
    private $db;

    /**
     * @var array شرط‌های where
     */
    private $wheres = [];

    /**
     * @var array مرتب‌سازی
     */
    private $orders = [];

    /**
     * @var int|null محدود کردن نتایج
     */
    private $limit = null;

    /**
     * سازنده کلاس
     *
     * @param string $table نام جدول
     * @param \PDO $db اتصال پایگاه داده
     */
    public function __construct($table, $db)
    {
        $this->table = $table;
        $this->db = $db;
    }

    /**
     * مرتب‌سازی نتایج
     *
     * @param string $column نام ستون
     * @param string $direction جهت مرتب‌سازی
     * @return $this
     */
    public function orderBy($column, $direction = 'ASC')
    {
        $this->orders[] = [$column, strtoupper($direction)];
        return $this;
    }

    /**
     * دریافت همه رکوردها
     *
     * @return array
     */
    public function all()
    {
        return $this->get();
    }

    /**
     * دریافت نتایج براساس شرایط
     *
     * @return array
     */
    public function get()
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        // اضافه کردن شرط‌ها
        if (!empty($this->wheres)) {
            $conditions = [];
            foreach ($this->wheres as $index => $where) {
                $paramName = ":where_{$index}";
                $conditions[] = "{$where[0]} = {$paramName}";
                $params[$paramName] = $where[1];
            }
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        // اضافه کردن مرتب‌سازی
        if (!empty($this->orders)) {
            $orderClauses = [];
            foreach ($this->orders as $order) {
                $orderClauses[] = "{$order[0]} {$order[1]}";
            }
            $sql .= " ORDER BY " . implode(', ', $orderClauses);
        }

        // اضافه کردن محدودیت
        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * به‌روزرسانی رکوردها
     *
     * @param array $data داده‌ها
     * @return int
     */
    public function update($data)
    {
        if (empty($this->wheres)) {
            throw new \Exception('Update requires at least one WHERE condition');
        }

        $sets = [];
        $params = [];

        foreach ($data as $key => $value) {
            $paramName = ":set_{$key}";
            $sets[] = "{$key} = {$paramName}";
            $params[$paramName] = $value;
        }

        $conditions = [];
        foreach ($this->wheres as $index => $where) {
            $paramName = ":where_{$index}";
            $conditions[] = "{$where[0]} = {$paramName}";
            $params[$paramName] = $where[1];
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets) .
            " WHERE " . implode(' AND ', $conditions);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /**
     * حذف رکوردها
     *
     * @return int
     */
    public function delete()
    {
        if (empty($this->wheres)) {
            throw new \Exception('Delete requires at least one WHERE condition');
        }

        $conditions = [];
        $params = [];

        foreach ($this->wheres as $index => $where) {
            $paramName = ":where_{$index}";
            $conditions[] = "{$where[0]} = {$paramName}";
            $params[$paramName] = $where[1];
        }

        $sql = "DELETE FROM {$this->table} WHERE " . implode(' AND ', $conditions);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /**
     * شمارش تعداد رکوردها
     *
     * @return int
     */
    public function count()
    {
        $sql = "SELECT COUNT(*) AS count FROM {$this->table}";
        $params = [];

        if (!empty($this->wheres)) {
            $conditions = [];
            foreach ($this->wheres as $index => $where) {
                $paramName = ":where_{$index}";
                $conditions[] = "{$where[0]} = {$paramName}";
                $params[$paramName] = $where[1];
            }
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetch(\PDO::FETCH_ASSOC)['count'];
    }

    /**
     * یافتن یا ایجاد یک رکورد
     *
     * @param array $search شرایط جستجو
     * @param array $data داده‌های اضافی برای ایجاد
     * @return array
     */
    public function firstOrCreate($search, $data = [])
    {
        // بازنشانی شرط‌های قبلی
        $this->wheres = [];

        // اضافه کردن شرط‌های جستجو
        foreach ($search as $column => $value) {
            $this->where($column, $value);
        }

        // تلاش برای یافتن رکورد
        $record = $this->first();

        if ($record) {
            return $record;
        }

        // ایجاد رکورد جدید اگر یافت نشد
        $insertData = array_merge($search, $data);
        $id = $this->insert($insertData);

        return $this->find($id);
    }

    /**
     * افزودن شرط where
     *
     * @param string $column نام ستون
     * @param mixed $value مقدار
     * @return $this
     */
    public function where($column, $value)
    {
        $this->wheres[] = [$column, $value];
        return $this;
    }

    /**
     * دریافت اولین رکورد
     *
     * @return array|null
     */
    public function first()
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    /**
     * محدود کردن نتایج
     *
     * @param int $limit تعداد
     * @return $this
     */
    public function limit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * درج رکورد جدید
     *
     * @param array $data داده‌ها
     * @return int
     */
    public function insert($data)
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";

        $params = [];
        foreach ($data as $key => $value) {
            $params[":{$key}"] = $value;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$this->db->lastInsertId();
    }

    /**
     * یافتن رکورد با شناسه
     *
     * @param int|string $id شناسه
     * @return array|null
     */
    public function find($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
}