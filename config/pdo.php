<?php
// config/pdo.php — DEPRECATED
// This project now uses mysqli exclusively via config/database.php
// This file is kept only so old includes don't throw "file not found" errors.
// It does nothing. You can safely delete it.

// If you see this message in a PHP error log, find and update the file
// that is still requiring pdo.php and change it to require database.php instead.

if (!isset($conn)) {
    require_once __DIR__ . '/database.php';
}
