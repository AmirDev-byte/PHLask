<?php

namespace PHLask\Database;

/**
 * QueryBuilder - کلاس سازنده کوئری
 *
 * این کلاس برای ساخت پویای کوئری‌های SQL استفاده می‌شود
 */
class QueryBuilder
{
    /**
     * @var Connection اتصال پایگاه داده
     */
    private Connection $connection;

    /**
     * @var string نام جدول
     */
    private string $table;

    /**
     * @var array ستون‌های انتخابی
     */
    private array $selects = [];

    /**
     * @var array شرط‌های where
     */
    private array $wheres = [];

    /**
     * @var array پارامترهای پرس‌وجو
     */
    private array $params = [];

    /**
     * @var array|null دستورات join
     */
    private ?array $joins = null;

    /**
     * @var array|null دستورات order by
     */
    private ?array $orders = null;

    /**
     * @var array|null دستورات group by
     */
    private ?array $groups = null;

    /**
     * @var string|null شرط having
     */
    private ?string $having = null;

    /**
     * @var int|null تعداد رکوردها برای limit
     */
    private ?int $limit = null;

    /**
     * @var int|null جابجایی رکوردها برای offset
     */
    private ?int $offset = null;

    /**
     * سازنده کلاس QueryBuilder
     *
     * @param string $table نام جدول
     * @param Connection|null $connection اتصال پایگاه داده
     */
    public function __construct(string $table, ?Connection $connection = null)
    {
        $this->table = $table;
        $this->connection = $connection ?? Connection::connection();
    }

    /**
     * افزودن شرط where با رابطه منطقی OR
     *
     * @param string $column نام ستون
     * @param string|null $operator عملگر
     * @param mixed $value مقدار
     * @return self
     */
    public function orWhere(string $column, $operator = null, $value = null): self
    {
        return $this->where($column, $operator, $value, 'OR');
    }

    /**
     * افزودن شرط where
     *
     * @param string $column نام ستون
     * @param string|null $operator عملگر
     * @param mixed $value مقدار
     * @param string $boolean رابطه منطقی (AND/OR)
     * @return self
     */
    public function where(string $column, $operator = null, $value = null, string $boolean = 'AND'): self
    {
        // اگر فقط دو پارامتر ارسال شده باشد، عملگر را به '=' تنظیم می‌کنیم
        if ($value === null && $operator !== null) {
            $value = $operator;
            $operator = '=';
        }

        // ایجاد نام پارامتر یکتا
        $param = ':' . $column . '_' . count($this->params);

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => $boolean,
            'param' => $param
        ];

        $this->params[$param] = $value;

        return $this;
    }

    /**
     * افزودن شرط where برای جستجوی شبیه (LIKE) با رابطه منطقی OR
     *
     * @param string $column نام ستون
     * @param string $value مقدار
     * @return self
     */
    public function orWhereLike(string $column, string $value): self
    {
        return $this->whereLike($column, $value, 'OR');
    }

    /**
     * افزودن شرط where برای جستجوی شبیه (LIKE)
     *
     * @param string $column نام ستون
     * @param string $value مقدار
     * @param string $boolean رابطه منطقی (AND/OR)
     * @return self
     */
    public function whereLike(string $column, string $value, string $boolean = 'AND'): self
    {
        return $this->where($column, 'LIKE', $value, $boolean);
    }

    /**
     * افزودن شرط where برای مقادیر غیر null
     *
     * @param string $column نام ستون
     * @param string $boolean رابطه منطقی (AND/OR)
     * @return self
     */
    public function whereNotNull(string $column, string $boolean = 'AND'): self
    {
        return $this->whereNull($column, $boolean, true);
    }

    /**
     * افزودن شرط where برای مقادیر null
     *
     * @param string $column نام ستون
     * @param string $boolean رابطه منطقی (AND/OR)
     * @param bool $not آیا شرط معکوس شود (IS NOT NULL)
     * @return self
     */
    public function whereNull(string $column, string $boolean = 'AND', bool $not = false): self
    {
        $this->wheres[] = [
            'type' => 'null',
            'column' => $column,
            'boolean' => $boolean,
            'not' => $not
        ];

        return $this;
    }

    /**
     * افزودن شرط where برای مقادیر خارج از یک مجموعه (NOT IN)
     *
     * @param string $column نام ستون
     * @param array $values مقادیر
     * @param string $boolean رابطه منطقی (AND/OR)
     * @return self
     */
    public function whereNotIn(string $column, array $values, string $boolean = 'AND'): self
    {
        return $this->whereIn($column, $values, $boolean, true);
    }

    /**
     * افزودن شرط where برای مقادیر درون یک مجموعه (IN)
     *
     * @param string $column نام ستون
     * @param array $values مقادیر
     * @param string $boolean رابطه منطقی (AND/OR)
     * @param bool $not آیا شرط معکوس شود (NOT IN)
     * @return self
     */
    public function whereIn(string $column, array $values, string $boolean = 'AND', bool $not = false): self
    {
        $params = [];

        foreach ($values as $i => $value) {
            $param = ':' . $column . '_in_' . $i;
            $params[] = $param;
            $this->params[$param] = $value;
        }

        $this->wheres[] = [
            'type' => 'in',
            'column' => $column,
            'values' => $values,
            'boolean' => $boolean,
            'not' => $not,
            'params' => $params
        ];

        return $this;
    }

    /**
     * افزودن شرط where برای مقادیر خارج از یک محدوده (NOT BETWEEN)
     *
     * @param string $column نام ستون
     * @param array $values مقادیر [min, max]
     * @param string $boolean رابطه منطقی (AND/OR)
     * @return self
     */
    public function whereNotBetween(string $column, array $values, string $boolean = 'AND'): self
    {
        return $this->whereBetween($column, $values, $boolean, true);
    }

    /**
     * افزودن شرط where برای مقادیر بین یک محدوده (BETWEEN)
     *
     * @param string $column نام ستون
     * @param array $values مقادیر [min, max]
     * @param string $boolean رابطه منطقی (AND/OR)
     * @param bool $not آیا شرط معکوس شود (NOT BETWEEN)
     * @return self
     */
    public function whereBetween(string $column, array $values, string $boolean = 'AND', bool $not = false): self
    {
        if (count($values) !== 2) {
            throw new \InvalidArgumentException('The values for BETWEEN operator must contain exactly 2 elements');
        }

        $minParam = ':' . $column . '_min';
        $maxParam = ':' . $column . '_max';

        $this->wheres[] = [
            'type' => 'between',
            'column' => $column,
            'values' => $values,
            'boolean' => $boolean,
            'not' => $not,
            'min_param' => $minParam,
            'max_param' => $maxParam
        ];

        $this->params[$minParam] = $values[0];
        $this->params[$maxParam] = $values[1];

        return $this;
    }

    /**
     * افزودن دستور LEFT JOIN
     *
     * @param string $table نام جدول
     * @param string $first ستون اول
     * @param string $operator عملگر
     * @param string $second ستون دوم
     * @return self
     */
    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    /**
     * افزودن دستور JOIN
     *
     * @param string $table نام جدول
     * @param string $first ستون اول
     * @param string $operator عملگر
     * @param string $second ستون دوم
     * @param string $type نوع JOIN
     * @return self
     */
    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): self
    {
        $this->joins[] = [
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
            'type' => $type
        ];

        return $this;
    }

    /**
     * افزودن دستور RIGHT JOIN
     *
     * @param string $table نام جدول
     * @param string $first ستون اول
     * @param string $operator عملگر
     * @param string $second ستون دوم
     * @return self
     */
    public function rightJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    /**
     * افزودن دستور ORDER BY با جهت نزولی
     *
     * @param string $column نام ستون
     * @return self
     */
    public function orderByDesc(string $column): self
    {
        return $this->orderBy($column, 'DESC');
    }

    /**
     * افزودن دستور ORDER BY
     *
     * @param string $column نام ستون
     * @param string $direction جهت مرتب‌سازی
     * @return self
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction);

        if (!in_array($direction, ['ASC', 'DESC'])) {
            throw new \InvalidArgumentException('Order direction must be ASC or DESC');
        }

        $this->orders[] = [
            'column' => $column,
            'direction' => $direction
        ];

        return $this;
    }

    /**
     * افزودن دستور GROUP BY
     *
     * @param string|array $columns ستون‌ها
     * @return self
     */
    public function groupBy($columns): self
    {
        $this->groups = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    /**
     * افزودن شرط HAVING
     *
     * @param string $column نام ستون
     * @param string $operator عملگر
     * @param mixed $value مقدار
     * @return self
     */
    public function having(string $column, string $operator, $value): self
    {
        $param = ':having_' . $column;
        $this->having = $column . ' ' . $operator . ' ' . $param;
        $this->params[$param] = $value;

        return $this;
    }

    /**
     * صفحه‌بندی نتایج
     *
     * @param int $page شماره صفحه
     * @param int $perPage تعداد رکورد در هر صفحه
     * @return self
     */
    public function paginate(int $page, int $perPage = 15): self
    {
        $page = max(1, $page);
        $this->limit($perPage);
        $this->offset(($page - 1) * $perPage);

        return $this;
    }

    /**
     * افزودن دستور LIMIT
     *
     * @param int $limit تعداد رکوردها
     * @return self
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * افزودن دستور OFFSET
     *
     * @param int $offset جابجایی رکوردها
     * @return self
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * درج داده در جدول
     *
     * @param array $data داده‌های برای درج
     * @return int آیدی آخرین رکورد درج شده
     */
    public function insert(array $data): int
    {
        return $this->connection->insert($this->table, $data);
    }

    /**
     * به‌روزرسانی داده‌ها
     *
     * @param array $data داده‌های برای به‌روزرسانی
     * @return int تعداد رکوردهای تغییر یافته
     */
    public function update(array $data): int
    {
        $where = $this->buildWheres();
        $where = str_replace('WHERE ', '', $where);

        if (empty($where)) {
            throw new \InvalidArgumentException('Update requires a WHERE clause. Call where() method first.');
        }

        return $this->connection->update($this->table, $data, $where, $this->params);
    }

    /**
     * ساخت بخش WHERE کوئری
     *
     * @return string
     */
    protected function buildWheres(): string
    {
        if (empty($this->wheres)) {
            return '';
        }

        $sql = [];

        foreach ($this->wheres as $i => $where) {
            $boolean = $i === 0 ? '' : $where['boolean'] . ' ';

            switch ($where['type']) {
                case 'basic':
                    $sql[] = $boolean . $where['column'] . ' ' . $where['operator'] . ' ' . $where['param'];
                    break;

                case 'null':
                    $sql[] = $boolean . $where['column'] . ($where['not'] ? ' IS NOT NULL' : ' IS NULL');
                    break;

                case 'in':
                    $placeholders = implode(', ', $where['params']);
                    $sql[] = $boolean . $where['column'] . ($where['not'] ? ' NOT IN ' : ' IN ') . '(' . $placeholders . ')';
                    break;

                case 'between':
                    $sql[] = $boolean . $where['column'] . ($where['not'] ? ' NOT BETWEEN ' : ' BETWEEN ') . $where['min_param'] . ' AND ' . $where['max_param'];
                    break;
            }
        }

        return 'WHERE ' . implode(' ', $sql);
    }

    /**
     * حذف داده‌ها
     *
     * @return int تعداد رکوردهای حذف شده
     */
    public function delete(): int
    {
        $where = $this->buildWheres();
        $where = str_replace('WHERE ', '', $where);

        if (empty($where)) {
            throw new \InvalidArgumentException('Delete requires a WHERE clause. Call where() method first.');
        }

        return $this->connection->delete($this->table, $where, $this->params);
    }

    /**
     * بررسی وجود حداقل یک رکورد
     *
     * @return bool
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
     * اجرای کوئری و دریافت تعداد نتایج
     *
     * @param string $column نام ستون (پیش‌فرض: *)
     * @return int
     */
    public function count(string $column = '*'): int
    {
        $original = $this->selects;
        $this->selects = ["COUNT({$column}) as count"];

        $result = $this->first();
        $this->selects = $original;

        return isset($result['count']) ? (int)$result['count'] : 0;
    }

    /**
     * اجرای کوئری و دریافت اولین نتیجه
     *
     * @return array|null
     */
    public function first(): ?array
    {
        $sql = $this->limit(1)->toSql();
        return $this->connection->fetchOne($sql, $this->params);
    }

    /**
     * ساخت کوئری SQL کامل
     *
     * @return string
     */
    public function toSql(): string
    {
        $parts = [
            $this->buildSelect(),
            $this->buildFrom(),
            $this->buildJoins(),
            $this->buildWheres(),
            $this->buildGroups(),
            $this->buildHaving(),
            $this->buildOrders(),
            $this->buildLimitOffset()
        ];

        return implode(' ', array_filter($parts));
    }

    /**
     * ساخت بخش SELECT کوئری
     *
     * @return string
     */
    protected function buildSelect(): string
    {
        if (empty($this->selects)) {
            $this->selects = ['*'];
        }

        return 'SELECT ' . implode(', ', $this->selects);
    }

    /**
     * ساخت بخش FROM کوئری
     *
     * @return string
     */
    protected function buildFrom(): string
    {
        return 'FROM ' . $this->table;
    }

    /**
     * ساخت بخش JOIN کوئری
     *
     * @return string
     */
    protected function buildJoins(): string
    {
        if (empty($this->joins)) {
            return '';
        }

        $sql = [];

        foreach ($this->joins as $join) {
            $sql[] = $join['type'] . ' JOIN ' . $join['table'] . ' ON ' . $join['first'] . ' ' . $join['operator'] . ' ' . $join['second'];
        }

        return implode(' ', $sql);
    }

    /**
     * ساخت بخش GROUP BY کوئری
     *
     * @return string
     */
    protected function buildGroups(): string
    {
        if (empty($this->groups)) {
            return '';
        }

        return 'GROUP BY ' . implode(', ', $this->groups);
    }

    /**
     * ساخت بخش HAVING کوئری
     *
     * @return string
     */
    protected function buildHaving(): string
    {
        if (empty($this->having)) {
            return '';
        }

        return 'HAVING ' . $this->having;
    }

    /**
     * ساخت بخش ORDER BY کوئری
     *
     * @return string
     */
    protected function buildOrders(): string
    {
        if (empty($this->orders)) {
            return '';
        }

        $sql = [];

        foreach ($this->orders as $order) {
            $sql[] = $order['column'] . ' ' . $order['direction'];
        }

        return 'ORDER BY ' . implode(', ', $sql);
    }

    /**
     * ساخت بخش LIMIT و OFFSET کوئری
     *
     * @return string
     */
    protected function buildLimitOffset(): string
    {
        $sql = '';

        if ($this->limit !== null) {
            $sql .= 'LIMIT ' . $this->limit;
        }

        if ($this->offset !== null) {
            $sql .= ' OFFSET ' . $this->offset;
        }

        return $sql;
    }

    /**
     * دریافت مقدار یک ستون خاص
     *
     * @param string $column نام ستون
     * @return mixed|null
     */
    public function value(string $column)
    {
        $result = $this->select($column)->first();

        if (is_null($result)) {
            return null;
        }

        return $result[$column] ?? null;
    }

    /**
     * انتخاب ستون‌ها
     *
     * @param string|array $columns ستون‌های مورد نظر
     * @return self
     */
    public function select($columns = ['*']): self
    {
        $this->selects = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    /**
     * دریافت یک ستون به صورت آرایه
     *
     * @param string $column نام ستون
     * @return array
     */
    public function pluck(string $column): array
    {
        $results = $this->select($column)->get();

        return array_map(function ($row) use ($column) {
            return $row[$column] ?? null;
        }, $results);
    }

    /**
     * اجرای کوئری و دریافت همه نتایج
     *
     * @return array
     */
    public function get(): array
    {
        $sql = $this->toSql();
        return $this->connection->fetchAll($sql, $this->params);
    }

    /**
     * فعال‌سازی دیباگ (چاپ کوئری)
     *
     * @return string
     */
    public function debug(): string
    {
        $sql = $this->toSql();
        $params = $this->getParams();

        // جایگزین کردن پارامترها با مقادیر واقعی برای دیباگ
        foreach ($params as $key => $value) {
            $value = is_string($value) ? "'{$value}'" : $value;
            $value = is_null($value) ? 'NULL' : $value;
            $sql = str_replace($key, $value, $sql);
        }

        return $sql;
    }

    /**
     * دریافت پارامترهای کوئری
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * اجرای کوئری خام SQL
     *
     * @param string $query کوئری SQL
     * @param array $params پارامترها
     * @return array
     */
    public function raw(string $query, array $params = []): array
    {
        return $this->connection->fetchAll($query, $params);
    }
}