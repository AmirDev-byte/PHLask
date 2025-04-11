<?php
/**
 * مثال استفاده بسیار ساده از EasyDB
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PHLask\Database\EasyDB;

// ----- مثال اتصال به SQLite -----
$db = EasyDB::sqlite(__DIR__ . '/test.db');

// جستجوی کاربران و نمایش آنها
$users = $db->table('users')
    ->where('active', 1)
    ->orderBy('name')
    ->get();

echo "کاربران فعال: " . count($users) . "\n";
foreach ($users as $user) {
    echo "- {$user['name']} ({$user['email']})\n";
}

// ایجاد کاربر جدید
$newUserId = $db->table('users')->insert([
    'name' => 'کاربر جدید',
    'email' => 'user@example.com',
    'active' => 1
]);

echo "کاربر جدید با شناسه {$newUserId} ایجاد شد\n";

// به‌روزرسانی کاربر
$db->table('users')
    ->where('id', $newUserId)
    ->update(['name' => 'نام به‌روز شده']);

// یافتن یا ایجاد کاربر
$user = $db->table('users')->firstOrCreate(
    ['email' => 'unique@example.com'],
    ['name' => 'کاربر یکتا', 'active' => 1]
);

echo "کاربر یافت یا ایجاد شد: {$user['name']}\n";

// حذف کاربر
$deleted = $db->table('users')
    ->where('id', $newUserId)
    ->delete();

echo "{$deleted} کاربر حذف شد\n";

// ----- مثال اتصال به MySQL -----
/*
$db = EasyDB::mysql(
    'database_name',
    'username',
    'password',
    'localhost'
);

// مشابه مثال SQLite با همان سینتکس
*/