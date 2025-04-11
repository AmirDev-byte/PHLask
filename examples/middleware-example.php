<?php
/**
 * مثال استفاده از مدل‌های PHLask
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PHLask\Database\Connection;
use PHLask\Database\Model;

// تنظیمات پایگاه داده
$dbConfig = [
    'driver' => 'mysql',
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'test_db',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
];

// ایجاد اتصال به پایگاه داده
try {
    $connection = Connection::connection('default', $dbConfig);
} catch (\Exception $e) {
    echo 'Database connection failed: ' . $e->getMessage() . PHP_EOL;
    exit;
}

// تعریف یک مدل برای جدول کاربران
class User extends Model
{
    // تنظیم نام جدول (اختیاری)
    protected static string $table = 'users';

    // تعریف متدهای مدل برای کارهای اختصاصی

    /**
     * یافتن کاربر با ایمیل
     *
     * @param string $email ایمیل کاربر
     * @return User|null
     */
    public static function findByEmail(string $email): ?self
    {
        return static::findWhere('email', $email);
    }

    /**
     * بررسی صحت رمز عبور
     *
     * @param string $password رمز عبور
     * @return bool
     */
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }

    /**
     * تنظیم رمز عبور (با هش کردن)
     *
     * @param string $password رمز عبور
     * @return self
     */
    public function setPassword(string $password): self
    {
        $this->password = password_hash($password, PASSWORD_DEFAULT);
        return $this;
    }

    /**
     * تبدیل مدل به آرایه با حذف رمز عبور
     *
     * @return array
     */
    public function toArray(): array
    {
        $data = parent::toArray();

        // حذف رمز عبور برای امنیت بیشتر
        unset($data['password']);

        return $data;
    }
}

// نمونه‌های استفاده از مدل

// ایجاد یک کاربر جدید
function createUser()
{
    echo "Creating a new user...\n";

    try {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => password_hash('secret123', PASSWORD_DEFAULT),
            'active' => 1
        ]);

        echo "User created with ID: " . $user->id . "\n";
        return $user;
    } catch (\Exception $e) {
        echo "Error creating user: " . $e->getMessage() . "\n";
        return null;
    }
}

// یافتن یک کاربر با آیدی
function findUser($id)
{
    echo "Finding user with ID: $id\n";

    $user = User::find($id);

    if ($user) {
        echo "User found: " . $user->name . " (" . $user->email . ")\n";
    } else {
        echo "User not found\n";
    }

    return $user;
}

// یافتن کاربر با ایمیل
function findUserByEmail($email)
{
    echo "Finding user with email: $email\n";

    $user = User::findByEmail($email);

    if ($user) {
        echo "User found: " . $user->name . " (ID: " . $user->id . ")\n";
    } else {
        echo "User not found\n";
    }

    return $user;
}

// به‌روزرسانی اطلاعات کاربر
function updateUser($user)
{
    echo "Updating user...\n";

    $user->name = 'John Updated';
    $user->setPassword('newpassword');

    if ($user->save()) {
        echo "User updated successfully\n";
    } else {
        echo "Failed to update user\n";
    }

    return $user;
}

// حذف کاربر
function deleteUser($user)
{
    echo "Deleting user...\n";

    if ($user->delete()) {
        echo "User deleted successfully\n";
    } else {
        echo "Failed to delete user\n";
    }
}

// دریافت لیست کاربران با شروط مختلف
function listUsers()
{
    echo "Listing users...\n";

    // دریافت همه کاربران
    $allUsers = User::all();
    echo "Total users: " . count($allUsers) . "\n";

    // دریافت کاربران فعال
    $activeUsers = User::findAllWhere('active', 1);
    echo "Active users: " . count($activeUsers) . "\n";

    // دریافت کاربران با شروط پیشرفته
    $filteredUsers = User::query()
        ->where('active', 1)
        ->whereLike('email', '%@example.com%')
        ->orderBy('created_at', 'DESC')
        ->limit(10)
        ->get();

    echo "Filtered users: " . count($filteredUsers) . "\n";

    // چاپ لیست کاربران
    foreach ($filteredUsers as $index => $userData) {
        $user = new User($userData, true);
        echo ($index + 1) . ". " . $user->name . " (" . $user->email . ")\n";
    }
}

// استفاده از تراکنش برای انجام چند عملیات به صورت اتمیک
function transactionExample()
{
    echo "Transaction example...\n";

    try {
        $connection = Connection::connection();

        $result = $connection->transaction(function ($conn) {
            // ایجاد کاربر اول
            $user1 = User::create([
                'name' => 'Transaction User 1',
                'email' => 'tx1@example.com',
                'password' => password_hash('tx1pass', PASSWORD_DEFAULT),
                'active' => 1
            ]);

            // ایجاد کاربر دوم
            $user2 = User::create([
                'name' => 'Transaction User 2',
                'email' => 'tx2@example.com',
                'password' => password_hash('tx2pass', PASSWORD_DEFAULT),
                'active' => 1
            ]);

            // بازگشت آرایه‌ای از نتایج
            return [
                'user1' => $user1->id,
                'user2' => $user2->id
            ];
        });

        echo "Transaction successful:\n";
        echo "- User 1 ID: " . $result['user1'] . "\n";
        echo "- User 2 ID: " . $result['user2'] . "\n";
    } catch (\Exception $e) {
        echo "Transaction failed: " . $e->getMessage() . "\n";
    }
}

// اجرای مثال‌ها
echo "===== PHLask Model Examples =====\n\n";

// اجرای مثال ایجاد کاربر
$newUser = createUser();
echo "\n";

// اجرای مثال یافتن کاربر
if ($newUser) {
    $user = findUser($newUser->id);
    echo "\n";

    // اجرای مثال به‌روزرسانی کاربر
    if ($user) {
        updateUser($user);
        echo "\n";
    }
}

// اجرای مثال یافتن کاربر با ایمیل
$emailUser = findUserByEmail('john@example.com');
echo "\n";

// اجرای مثال لیست کاربران
listUsers();
echo "\n";

// اجرای مثال تراکنش
transactionExample();
echo "\n";

// اجرای مثال حذف کاربر
if ($newUser) {
    deleteUser($newUser);
}

echo "\n===== Examples Completed =====\n";