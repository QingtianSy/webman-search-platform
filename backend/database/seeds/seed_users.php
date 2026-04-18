<?php
/**
 * Seed runner - generates bcrypt password hashes and inserts seed users.
 *
 * Usage: php database/seeds/seed_users.php
 *
 * Run this AFTER executing 0001_auth_rbac_seed.sql (which does NOT insert users).
 * This script inserts users with proper bcrypt hashes.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

$configPath = __DIR__ . '/../../config/database.php';
$config = require $configPath;
$c = $config['connections']['mysql'] ?? [];

if (empty($c['host']) || empty($c['database'])) {
    echo "ERROR: MySQL config not found. Check config/database.php and .env\n";
    exit(1);
}

try {
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $c['host'], $c['port'] ?? 3306, $c['database'], $c['charset'] ?? 'utf8mb4');
    $pdo = new PDO($dsn, $c['username'] ?? '', $c['password'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    echo "ERROR: Cannot connect to MySQL: " . $e->getMessage() . "\n";
    exit(1);
}

$users = [
    [
        'id' => 1,
        'username' => 'demo_user',
        'password' => '123456',
        'nickname' => '测试用户',
    ],
    [
        'id' => 2,
        'username' => 'admin',
        'password' => 'admin123',
        'nickname' => '超级管理员',
    ],
];

$sql = 'INSERT INTO users (id, username, password_hash, nickname, avatar, status, created_at, updated_at) VALUES (:id, :username, :password_hash, :nickname, :avatar, :status, NOW(), NOW()) ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), nickname = VALUES(nickname)';
$stmt = $pdo->prepare($sql);

foreach ($users as $user) {
    $hash = password_hash($user['password'], PASSWORD_BCRYPT);
    $stmt->execute([
        'id' => $user['id'],
        'username' => $user['username'],
        'password_hash' => $hash,
        'nickname' => $user['nickname'],
        'avatar' => '',
        'status' => 1,
    ]);
    echo "OK: {$user['username']} (hash: {$hash})\n";
}

echo "\nDone. " . count($users) . " users seeded.\n";
