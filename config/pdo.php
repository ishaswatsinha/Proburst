<?php
// =============================================
// config/pdo.php
// PDO connection (used by User model)
// Your existing config/database.php (mysqli) stays untouched
// =============================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'proburst_db');   // ← change to your DB name
define('DB_USER', 'root');       // ← change to your MySQL user
define('DB_PASS', '');           // ← change to your MySQL password

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // In production, log this — never show raw errors
    error_log($e->getMessage());
    die(json_encode(['error' => 'Database connection failed.']));
}
