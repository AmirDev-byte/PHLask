# تراکنش‌ها (Transactions)

تراکنش‌ها یکی از مفاهیم مهم در کار با پایگاه‌های داده هستند که به شما امکان می‌دهند مجموعه‌ای از عملیات را به صورت یک واحد اتمیک اجرا کنید. در این بخش با نحوه استفاده از تراکنش‌ها در فلسک‌پی‌اچ‌پی آشنا می‌شوید.

## مفهوم تراکنش

تراکنش یک واحد منطقی از کار است که شامل یک یا چند عملیات پایگاه داده (INSERT، UPDATE، DELETE) می‌شود. تراکنش‌ها چهار ویژگی اصلی دارند که به اختصار ACID نامیده می‌شوند:

1. **اتمیک بودن (Atomicity)**: تراکنش یا کاملاً انجام می‌شود یا اصلاً انجام نمی‌شود.
2. **سازگاری (Consistency)**: تراکنش پایگاه داده را از یک حالت معتبر به حالت معتبر دیگری تغییر می‌دهد.
3. **ایزوله بودن (Isolation)**: عملیات انجام شده در یک تراکنش از دیگر تراکنش‌ها مخفی می‌ماند.
4. **ماندگاری (Durability)**: پس از اتمام موفقیت‌آمیز تراکنش، تغییرات به صورت دائمی ذخیره می‌شوند.

## چرا از تراکنش‌ها استفاده کنیم؟

تراکنش‌ها در موارد زیر ضروری هستند:

- **عملیات وابسته**: وقتی چندین عملیات پایگاه داده به هم وابسته هستند و همه آنها باید موفقیت‌آمیز باشند یا هیچ کدام نباید انجام شوند.
- **حفظ انسجام داده‌ها**: وقتی می‌خواهید از سازگاری داده‌ها در تمام مراحل عملیات اطمینان حاصل کنید.
- **مدیریت همروندی**: وقتی چندین کاربر به طور همزمان با داده‌های مشابه کار می‌کنند.

## استفاده از تراکنش‌ها در فلسک‌پی‌اچ‌پی

فلسک‌پی‌اچ‌پی دو روش اصلی برای کار با تراکنش‌ها ارائه می‌دهد:

1. **روش دستی**: کنترل مستقیم تراکنش با استفاده از متدهای `beginTransaction`، `commit` و `rollBack`.
2. **روش خودکار**: استفاده از متد `transaction` که تراکنش را به صورت خودکار مدیریت می‌کند.

### روش دستی

در روش دستی، شما به صورت صریح تراکنش را شروع، تأیید یا برگشت می‌دهید:

```php
use FlaskPHP\Database\Connection;

$connection = Connection::connection();

try {
// شروع تراکنش
$connection->beginTransaction();

// عملیات اول: ثبت سفارش
$orderId = $connection->insert('orders', [
'user_id' => 123,
'total' => 1500000,
'status' => 'pending',
'created_at' => date('Y-m-d H:i:s')
]);

// عملیات دوم: ثبت آیتم‌های سفارش
foreach ($items as $item) {
$connection->insert('order_items', [
'order_id' => $orderId,
'product_id' => $item['product_id'],
'quantity' => $item['quantity'],
'price' => $item['price']
]);

// بروزرسانی موجودی محصول
$connection->update(
'products',
['stock' => new \FlaskPHP\Database\Raw('stock - ' . $item['quantity'])],
'id = :id',
[':id' => $item['product_id']]
);
}

// عملیات سوم: ثبت تراکنش مالی
$connection->insert('transactions', [
'order_id' => $orderId,
'amount' => 1500000,
'status' => 'pending',
'created_at' => date('Y-m-d H:i:s')
]);

// تأیید تراکنش - اعمال همه تغییرات
$connection->commit();

return $orderId;
} catch (\Exception $e) {
// برگشت تراکنش - لغو همه تغییرات
$connection->rollBack();

// پرتاب مجدد استثنا یا مدیریت خطا
throw $e;
}
```

### روش خودکار

در روش خودکار، از متد `transaction` استفاده می‌کنید که تراکنش را مدیریت می‌کند و در صورت بروز خطا به صورت خودکار آن را برگشت می‌دهد:

```php
use FlaskPHP\Database\Connection;

$connection = Connection::connection();

try {
$orderId = $connection->transaction(function($conn) use ($items) {
// عملیات اول: ثبت سفارش
$orderId = $conn->insert('orders', [
'user_id' => 123,
'total' => 1500000,
'status' => 'pending',
'created_at' => date('Y-m-d H:i:s')
]);

// عملیات دوم: ثبت آیتم‌های سفارش
foreach ($items as $item) {
$conn->insert('order_items', [
'order_id' => $orderId,
'product_id' => $item['product_id'],
'quantity' => $item['quantity'],
'price' => $item['price']
]);

// بروزرسانی موجودی محصول
$conn->update(
'products',
['stock' => new \FlaskPHP\Database\Raw('stock - ' . $item['quantity'])],
'id = :id',
[':id' => $item['product_id']]
);
}

// عملیات سوم: ثبت تراکنش مالی
$conn->insert('transactions', [
'order_id' => $orderId,
'amount' => 1500000,
'status' => 'pending',
'created_at' => date('Y-m-d H:i:s')
]);

return $orderId;
});

return $orderId;
} catch (\Exception $e) {
// مدیریت خطا
throw $e;
}
```

در این روش، اگر هر خطایی در بلوک تابع رخ دهد، تراکنش به صورت خودکار برگشت داده می‌شود.

## استفاده از تراکنش‌ها با مدل‌ها

هنگام کار با مدل‌ها، می‌توانید از تراکنش‌ها برای اطمینان از انسجام داده‌ها استفاده کنید:

```php
use FlaskPHP\Database\Connection;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Transaction;

$connection = Connection::connection();

try {
$orderId = $connection->transaction(function($conn) use ($userData, $items) {
// استفاده از مدل‌ها درون تراکنش
$order = new Order([
'user_id' => $userData['user_id'],
'total' => $userData['total'],
'status' => 'pending'
]);
$order->save();

foreach ($items as $item) {
// ثبت آیتم‌های سفارش
$orderItem = new OrderItem([
'order_id' => $order->id,
'product_id' => $item['product_id'],
'quantity' => $item['quantity'],
'price' => $item['price']
]);
$orderItem->save();

// بروزرسانی موجودی محصول
$product = Product::find($item['product_id']);
$product->stock -= $item['quantity'];
$product->save();
}

// ثبت تراکنش مالی
$transaction = new Transaction([
'order_id' => $order->id,
'amount' => $userData['total'],
'status' => 'pending'
]);
$transaction->save();

return $order->id;
});

return $orderId;
} catch (\Exception $e) {
// مدیریت خطا
throw $e;
}
```

## تراکنش‌های تو در تو (Nested Transactions)

برخی پایگاه‌های داده از تراکنش‌های تو در تو پشتیبانی می‌کنند. در فلسک‌پی‌اچ‌پی، این ویژگی به صورت شبیه‌سازی شده پیاده‌سازی شده است:

```php
$connection->beginTransaction(); // سطح 1

try {
// عملیات سطح 1

$connection->beginTransaction(); // سطح 2

try {
// عملیات سطح 2

$connection->commit(); // تأیید سطح 2
} catch (\Exception $e) {
$connection->rollBack(); // برگشت سطح 2
throw $e;
}

$connection->commit(); // تأیید سطح 1
} catch (\Exception $e) {
$connection->rollBack(); // برگشت سطح 1
throw $e;
}
```

در این مثال، اگر خطایی در سطح 2 رخ دهد، فقط عملیات‌های سطح 2 برگشت داده می‌شوند و عملیات‌های سطح 1 همچنان می‌توانند ادامه یابند.

> **نکته**: پشتیبانی از تراکنش‌های تو در تو به نوع پایگاه داده بستگی دارد. MySQL (InnoDB) از طریق savepoints از آن پشتیبانی می‌کند، اما بعضی پایگاه‌های داده ممکن است پشتیبانی نکنند یا متفاوت رفتار کنند.

## نقاط ذخیره (Savepoints)

در برخی موارد، ممکن است بخواهید بخشی از تراکنش را برگشت دهید، اما کل تراکنش را لغو نکنید. برای این منظور، می‌توانید از نقاط ذخیره استفاده کنید:

```php
$connection->beginTransaction();

try {
// عملیات اول
$userId = $connection->insert('users', [
'name' => 'علی رضایی',
'email' => 'ali@example.com'
]);

// ایجاد یک نقطه ذخیره
$connection->createSavepoint('after_user_creation');

try {
// عملیات دوم
$profileId = $connection->insert('profiles', [
'user_id' => $userId,
'bio' => 'توضیحات کاربر'
]);
} catch (\Exception $e) {
// برگشت به نقطه ذخیره (فقط عملیات دوم برگشت داده می‌شود)
$connection->rollBackToSavepoint('after_user_creation');

// ادامه تراکنش
// ...
}

// تأیید تراکنش
$connection->commit();
} catch (\Exception $e) {
// برگشت کل تراکنش
$connection->rollBack();
throw $e;
}
```

> **نکته**: در نسخه فعلی فلسک‌پی‌اچ‌پی، متدهای `createSavepoint` و `rollBackToSavepoint` مستقیماً در کلاس `Connection` پیاده‌سازی نشده‌اند. برای استفاده از آنها، باید از شیء PDO استفاده کنید یا این متدها را به کلاس `Connection` اضافه کنید.

## بهترین روش‌ها برای استفاده از تراکنش‌ها

### 1. کوتاه و متمرکز نگه دارید

تراکنش‌ها را تا حد امکان کوتاه و متمرکز نگه دارید. هر چه تراکنش طولانی‌تر باشد، احتمال تداخل با سایر تراکنش‌ها بیشتر می‌شود:

```php
// نادرست: تراکنش طولانی با عملیات غیر ضروری
$connection->beginTransaction();

// دریافت داده‌ها از سرویس خارجی (عملیات زمان‌بر و غیر ضروری در تراکنش)
$externalData = $apiService->fetchData();

// پردازش داده‌ها (عملیات پردازش سنگین)
$processedData = $dataProcessor->process($externalData);

// درج داده‌ها
$connection->insert('table', $processedData);

$connection->commit();

// درست: فقط عملیات پایگاه داده در تراکنش
// دریافت و پردازش داده‌ها خارج از تراکنش
$externalData = $apiService->fetchData();
$processedData = $dataProcessor->process($externalData);

// شروع تراکنش فقط برای عملیات پایگاه داده
$connection->beginTransaction();
$connection->insert('table', $processedData);
$connection->commit();
```

### 2. مدیریت صحیح خطاها

همیشه تراکنش‌ها را در بلوک try-catch قرار دهید و در صورت بروز خطا، تراکنش را برگشت دهید:

```php
try {
$connection->beginTransaction();

// عملیات پایگاه داده

$connection->commit();
} catch (\Exception $e) {
$connection->rollBack();

// ثبت خطا
error_log('خطا در تراکنش: ' . $e->getMessage());

// پرتاب مجدد استثنا یا بازگرداندن پاسخ خطا
throw $e;
}
```

### 3. از روش خودکار استفاده کنید

در اکثر موارد، استفاده از روش خودکار (متد `transaction`) ساده‌تر و کم خطاتر است:

```php
try {
$result = $connection->transaction(function($conn) {
// عملیات پایگاه داده

return $someResult;
});
} catch (\Exception $e) {
// خطا در تراکنش
// تراکنش به صورت خودکار برگشت داده شده است
}
```

### 4. از قفل‌های صریح اجتناب کنید

تا حد امکان از قفل‌های صریح (explicit locks) اجتناب کنید. سیستم مدیریت تراکنش پایگاه داده معمولاً می‌تواند به صورت خودکار قفل‌های مناسب را ایجاد کند:

```php
// نادرست: استفاده از قفل صریح
$connection->query('LOCK TABLES users WRITE');
// عملیات
$connection->query('UNLOCK TABLES');

// درست: استفاده از تراکنش‌ها
$connection->beginTransaction();
// عملیات
$connection->commit();
```

### 5. عملیات‌های خواندن زیاد را در تراکنش قرار ندهید

عملیات‌های خواندن (SELECT) را تا حد امکان خارج از تراکنش انجام دهید، مگر اینکه به طور خاص نیاز به خواندن سازگار (consistent read) داشته باشید:

```php
// نادرست: عملیات‌های خواندن زیاد در تراکنش
$connection->beginTransaction();

$users = $connection->fetchAll('SELECT * FROM users');
$products = $connection->fetchAll('SELECT * FROM products');
$categories = $connection->fetchAll('SELECT * FROM categories');

// عملیات نوشتن
$connection->insert('orders', $orderData);

$connection->commit();

// درست: فقط عملیات‌های نوشتن در تراکنش
$users = $connection->fetchAll('SELECT * FROM users');
$products = $connection->fetchAll('SELECT * FROM products');
$categories = $connection->fetchAll('SELECT * FROM categories');

$connection->beginTransaction();
$connection->insert('orders', $orderData);
$connection->commit();
```

## نمونه‌های کاربردی

### مثال 1: سیستم سفارش گیری

```php
class OrderService
{
private $connection;

public function __construct(Connection $connection)
{
$this->connection = $connection;
}

public function createOrder(array $orderData, array $items): int
{
return $this->connection->transaction(function($conn) use ($orderData, $items) {
// بررسی موجودی محصولات
foreach ($items as $item) {
$product = $conn->fetchOne(
'SELECT id, stock, price FROM products WHERE id = :id',
[':id' => $item['product_id']]
);

if (!$product) {
throw new \Exception("محصول با شناسه {$item['product_id']} یافت نشد");
}

if ($product['stock'] < $item['quantity']) {
throw new \Exception("موجودی محصول {$product['id']} کافی نیست");
}
}

// محاسبه مجموع سفارش
$total = 0;
foreach ($items as $item) {
$product = $conn->fetchOne(
'SELECT price FROM products WHERE id = :id',
[':id' => $item['product_id']]
);
$total += $product['price'] * $item['quantity'];
}

// ثبت سفارش
$orderId = $conn->insert('orders', [
'user_id' => $orderData['user_id'],
'total' => $total,
'status' => 'pending',
'shipping_address' => $orderData['shipping_address'],
'created_at' => date('Y-m-d H:i:s')
]);

// ثبت آیتم‌های سفارش و کاهش موجودی
foreach ($items as $item) {
$conn->insert('order_items', [
'order_id' => $orderId,
'product_id' => $item['product_id'],
'quantity' => $item['quantity'],
'price' => $conn->fetchOne(
'SELECT price FROM products WHERE id = :id',
[':id' => $item['product_id']]
)['price']
]);

// کاهش موجودی محصول
$conn->update(
'products',
['stock' => new \FlaskPHP\Database\Raw('stock - :quantity')],
'id = :id',
[
':id' => $item['product_id'],
':quantity' => $item['quantity']
]
);
}

// ثبت تراکنش مالی
$conn->insert('financial_transactions', [
'order_id' => $orderId,
'amount' => $total,
'type' => 'order_payment',
'status' => 'pending',
'created_at' => date('Y-m-d H:i:s')
]);

return $orderId;
});
}

public function cancelOrder(int $orderId): bool
{
return $this->connection->transaction(function($conn) use ($orderId) {
// بررسی وجود سفارش
$order = $conn->fetchOne(
'SELECT * FROM orders WHERE id = :id AND status = :status',
[':id' => $orderId, ':status' => 'pending']
);

if (!$order) {
throw new \Exception("سفارش با شناسه {$orderId} یافت نشد یا قابل لغو نیست");
}

// دریافت آیتم‌های سفارش
$items = $conn->fetchAll(
'SELECT * FROM order_items WHERE order_id = :order_id',
[':order_id' => $orderId]
);

// بازگرداندن موجودی محصولات
foreach ($items as $item) {
$conn->update(
'products',
['stock' => new \FlaskPHP\Database\Raw('stock + :quantity')],
'id = :id',
[
':id' => $item['product_id'],
':quantity' => $item['quantity']
]
);
}

// به‌روزرسانی وضعیت سفارش
$conn->update(
'orders',
['status' => 'cancelled', 'updated_at' => date('Y-m-d H:i:s')],
'id = :id',
[':id' => $orderId]
);

// به‌روزرسانی وضعیت تراکنش مالی
$conn->update(
'financial_transactions',
['status' => 'cancelled', 'updated_at' => date('Y-m-d H:i:s')],
'order_id = :order_id AND type = :type',
[':order_id' => $orderId, ':type' => 'order_payment']
);

return true;
});
}
}

// استفاده از سرویس
$orderService = new OrderService(Connection::connection());

try {
$orderId = $orderService->createOrder(
[
'user_id' => 123,
'shipping_address' => 'تهران، خیابان ولیعصر، پلاک 123'
],
[
['product_id' => 1, 'quantity' => 2],
['product_id' => 3, 'quantity' => 1],
['product_id' => 5, 'quantity' => 3]
]
);

echo "سفارش با شناسه {$orderId} با موفقیت ثبت شد.";
} catch (\Exception $e) {
echo "خطا در ثبت سفارش: " . $e->getMessage();
}
```

### مثال 2: سیستم انتقال وجه بانکی

```php
class BankingService
{
private $connection;

public function __construct(Connection $connection)
{
$this->connection = $connection;
}

public function transferMoney(int $fromAccountId, int $toAccountId, float $amount): int
{
if ($amount <= 0) {
throw new \InvalidArgumentException("مبلغ انتقال باید بیشتر از صفر باشد");
}

return $this->connection->transaction(function($conn) use ($fromAccountId, $toAccountId, $amount) {
// قفل حساب‌ها برای جلوگیری از تغییرات همزمان
$fromAccount = $conn->fetchOne(
'SELECT * FROM accounts WHERE id = :id FOR UPDATE',
[':id' => $fromAccountId]
);

if (!$fromAccount) {
throw new \Exception("حساب مبدأ یافت نشد");
}

$toAccount = $conn->fetchOne(
'SELECT * FROM accounts WHERE id = :id FOR UPDATE',
[':id' => $toAccountId]
);

if (!$toAccount) {
throw new \Exception("حساب مقصد یافت نشد");
}

// بررسی موجودی حساب مبدأ
if ($fromAccount['balance'] < $amount) {
throw new \Exception("موجودی حساب مبدأ کافی نیست");
}

// کاهش موجودی حساب مبدأ
$conn->update(
'accounts',
[
'balance' => $fromAccount['balance'] - $amount,
'updated_at' => date('Y-m-d H:i:s')
],
'id = :id',
[':id' => $fromAccountId]
);

// افزایش موجودی حساب مقصد
$conn->update(
'accounts',
[
'balance' => $toAccount['balance'] + $amount,
'updated_at' => date('Y-m-d H:i:s')
],
'id = :id',
[':id' => $toAccountId]
);

// ثبت تراکنش
$transactionId = $conn->insert('bank_transactions', [
'from_account_id' => $fromAccountId,
'to_account_id' => $toAccountId,
'amount' => $amount,
'type' => 'transfer',
'status' => 'completed',
'reference_code' => uniqid('TRN'),
'description' => 'انتقال وجه',
'created_at' => date('Y-m-d H:i:s')
]);

return $transactionId;
});
}
}

// استفاده از سرویس
$bankingService = new BankingService(Connection::connection());

try {
$transactionId = $bankingService->transferMoney(101, 202, 1000000);
echo "انتقال وجه با موفقیت انجام شد. شناسه تراکنش: {$transactionId}";
} catch (\Exception $e) {
echo "خطا در انتقال وجه: " . $e->getMessage();
}
```

### مثال 3: سیستم مدیریت موجودی انبار

```php
class InventoryService
{
private $connection;

public function __construct(Connection $connection)
{
$this->connection = $connection;
}

public function adjustInventory(array $adjustments, string $reason): array
{
return $this->connection->transaction(function($conn) use ($adjustments, $reason) {
$results = [];

foreach ($adjustments as $adjustment) {
$productId = $adjustment['product_id'];
$quantity = $adjustment['quantity']; // می‌تواند مثبت یا منفی باشد

// دریافت اطلاعات محصول
$product = $conn->fetchOne(
'SELECT * FROM products WHERE id = :id',
[':id' => $productId]
);

if (!$product) {
throw new \Exception("محصول با شناسه {$productId} یافت نشد");
}

// بررسی کافی بودن موجودی برای کاهش
$newStock = $product['stock'] + $quantity;
if ($newStock < 0) {
throw new \Exception("موجودی محصول {$product['name']} کافی نیست");
}

// به‌روزرسانی موجودی
$conn->update(
'products',
[
'stock' => $newStock,
'updated_at' => date('Y-m-d H:i:s')
],
'id = :id',
[':id' => $productId]
);

// ثبت در تاریخچه تغییرات موجودی
$inventoryLogId = $conn->insert('inventory_logs', [
'product_id' => $productId,
'quantity' => $quantity,
'previous_stock' => $product['stock'],
'new_stock' => $newStock,
'reason' => $reason,
'created_by' => $_SESSION['user_id'] ?? null,
'created_at' => date('Y-m-d H:i:s')
]);

$results[] = [
'product_id' => $productId,
'name' => $product['name'],
'previous_stock' => $product['stock'],
'new_stock' => $newStock,
'adjustment' => $quantity,
'log_id' => $inventoryLogId
];
}

return $results;
});
}
}

// استفاده از سرویس
$inventoryService = new InventoryService(Connection::connection());

try {
$results = $inventoryService->adjustInventory(
[
['product_id' => 101, 'quantity' => 50], // افزایش 50 عدد
['product_id' => 102, 'quantity' => -10], // کاهش 10 عدد
['product_id' => 103, 'quantity' => 25] // افزایش 25 عدد
],
'تنظیم موجودی پس از انبارگردانی'
);

echo "موجودی محصولات با موفقیت به‌روزرسانی شد:\n";
foreach ($results as $result) {
echo "- {$result['name']}: از {$result['previous_stock']} به {$result['new_stock']} تغییر کرد\n";
}
} catch (\Exception $e) {
echo "خطا در تنظیم موجودی: " . $e->getMessage();
}
```

## تراکنش‌ها در محیط‌های چند کاربره

در محیط‌های چند کاربره، ممکن است چندین کاربر همزمان به داده‌های مشابه دسترسی داشته باشند. در این شرایط، استفاده صحیح از تراکنش‌ها از بسیاری از مشکلات جلوگیری می‌کند:

### سطوح انزوای تراکنش‌ها (Transaction Isolation Levels)

بیشتر پایگاه‌های داده چندین سطح انزوا برای تراکنش‌ها ارائه می‌کنند:

1. **خواندن تأیید نشده (READ UNCOMMITTED)**: یک تراکنش می‌تواند تغییرات تأیید نشده سایر تراکنش‌ها را ببیند.
2. **خواندن تأیید شده (READ COMMITTED)**: یک تراکنش فقط تغییرات تأیید شده را می‌بیند.
3. **خواندن تکراری (REPEATABLE READ)**: اگر یک داده چندبار خوانده شود، همیشه نتیجه یکسانی برمی‌گرداند.
4. **قابل سریالی (SERIALIZABLE)**: بالاترین سطح انزوا که تضمین می‌کند تراکنش‌ها به صورت کاملاً مجزا اجرا می‌شوند.

برای تنظیم سطح انزوا، می‌توانید از یک دستور SQL استفاده کنید:

```php
$connection->query('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
$connection->beginTransaction();
// عملیات
$connection->commit();
```

### استفاده از قفل‌ها (Locks)

در بعضی موارد، ممکن است نیاز به قفل‌گذاری صریح (explicit locking) روی رکوردها داشته باشید:

```php
// قفل انحصاری (Exclusive Lock)
$product = $connection->fetchOne('SELECT * FROM products WHERE id = :id FOR UPDATE', [':id' => $productId]);

// قفل اشتراکی (Shared Lock)
$product = $connection->fetchOne('SELECT * FROM products WHERE id = :id LOCK IN SHARE MODE', [':id' => $productId]);
```

قفل انحصاری مانع از خواندن و نوشتن همزمان سایر تراکنش‌ها می‌شود، در حالی که قفل اشتراکی فقط مانع از نوشتن می‌شود، اما خواندن را مجاز می‌کند.

## مشکلات رایج در استفاده از تراکنش‌ها

### 1. بن‌بست (Deadlock)

بن‌بست زمانی رخ می‌دهد که دو یا چند تراکنش منتظر آزاد شدن منابعی باشند که توسط یکدیگر قفل شده‌اند:

```
تراکنش A: قفل رکورد 1 -> منتظر قفل رکورد 2
تراکنش B: قفل رکورد 2 -> منتظر قفل رکورد 1
```

برای جلوگیری از بن‌بست:
- همیشه رکوردها را به ترتیب مشخص و یکسان قفل کنید
- زمان قفل را کوتاه نگه دارید
- از تراکنش‌های کوچک استفاده کنید
- از سطح انزوای پایین‌تر استفاده کنید (اگر امکان‌پذیر است)

### 2. تراکنش‌های ناتمام (Abandoned Transactions)

اگر تراکنشی شروع شود اما هرگز تأیید یا برگشت داده نشود، منابع پایگاه داده را مصرف می‌کند:

```php
$connection->beginTransaction();
// عملیات
// فراموش کردن commit یا rollBack
```

برای جلوگیری از این مشکل:
- همیشه از بلوک try-catch-finally استفاده کنید
- از متد `transaction` استفاده کنید که به صورت خودکار تراکنش را مدیریت می‌کند

### 3. تراکنش‌های طولانی (Long-running Transactions)

تراکنش‌های طولانی می‌توانند باعث مشکلات کارایی و مقیاس‌پذیری شوند:

```php
$connection->beginTransaction();
// عملیات زمان‌بر مانند درخواست به API خارجی
$apiResult = $apiClient->fetchData(); // ممکن است چندین ثانیه طول بکشد
// عملیات پایگاه داده
$connection->commit();
```

برای جلوگیری از این مشکل:
- تراکنش‌ها را کوتاه نگه دارید
- عملیات زمان‌بر را خارج از تراکنش انجام دهید
- از تراکنش‌های کوچک‌تر و متعدد استفاده کنید

## سایر موارد مرتبط با تراکنش‌ها

### تراکنش‌ها با چندین پایگاه داده

فلسک‌پی‌اچ‌پی از تراکنش‌های توزیع شده (distributed transactions) که شامل چندین پایگاه داده است، به صورت خودکار پشتیبانی نمی‌کند. اگر نیاز به تراکنش‌های توزیع شده دارید، باید از الگوهای طراحی مانند Saga یا Two-Phase Commit استفاده کنید یا از یک سرویس مدیریت تراکنش خارجی کمک بگیرید.

### تراکنش‌ها در محیط‌های آسنکرون

در محیط‌های آسنکرون (مانند استفاده از Swoole یا ReactPHP)، باید مراقب باشید که تراکنش‌ها در مسیر اجرایی مناسب شروع و پایان یابند:

```php
$pool->submit(function() use ($connection, $data) {
return $connection->transaction(function($conn) use ($data) {
// عملیات پایگاه داده

return $result;
});
});
```

### استفاده از تراکنش‌ها در تست‌ها

هنگام نوشتن تست‌های واحد یا یکپارچگی، می‌توانید از تراکنش‌ها برای برگشت تغییرات پس از هر تست استفاده کنید:

```php
public function setUp(): void
{
parent::setUp();

$this->connection = Connection::connection();
$this->connection->beginTransaction();
}

public function tearDown(): void
{
$this->connection->rollBack();

parent::tearDown();
}

public function testSomeFeature(): void
{
// انجام تست که شامل تغییرات پایگاه داده است
// پس از اتمام تست، همه تغییرات با rollBack برگشت داده می‌شوند
}
```

## خلاصه

تراکنش‌ها یکی از ویژگی‌های مهم پایگاه‌های داده هستند که به شما امکان می‌دهند مجموعه‌ای از عملیات را به صورت اتمیک اجرا کنید. فلسک‌پی‌اچ‌پی دو روش اصلی برای کار با تراکنش‌ها ارائه می‌دهد: روش دستی با متدهای `beginTransaction`، `commit` و `rollBack`، و روش خودکار با متد `transaction`.

با رعایت بهترین روش‌ها مانند کوتاه نگه داشتن تراکنش‌ها، مدیریت صحیح خطاها و استفاده از قفل‌ها به صورت مناسب، می‌توانید از تراکنش‌ها برای اطمینان از انسجام داده‌ها و بهبود قابلیت اطمینان برنامه خود استفاده کنید.

## گام بعدی

پس از آشنایی با تراکنش‌ها، برای یادگیری بیشتر می‌توانید به بخش‌های زیر مراجعه کنید:

- [اتصال به پایگاه داده](connection.md) - آشنایی بیشتر با کلاس Connection
- [کوئری بیلدر](query-builder.md) - استفاده از کوئری بیلدر در تراکنش‌ها
- [مدل‌ها](models.md) - استفاده از مدل‌ها در تراکنش‌ها