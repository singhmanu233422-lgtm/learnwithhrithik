<?php
// Learn With Hrithik database configuration.
// Default XAMPP MySQL settings: user=root, no password.
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'learn_with_hrithik');
define('DB_USER', 'root');
define('DB_PASS', '');

define('MAX_UPLOAD_BYTES', 100 * 1024 * 1024); // 100 MB
define('ADMIN_UPLOAD_DIR', __DIR__ . '/uploads');

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]
    );
    return $pdo;
}
function is_installed(): bool {
    try { db()->query("SELECT 1 FROM settings LIMIT 1"); return true; }
    catch (Throwable $e) { return false; }
}
