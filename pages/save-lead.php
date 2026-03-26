<?php
// pages/save-lead.php
// Fixed: prepared statements, proper validation, no debug output in production

if (session_status() === PHP_SESSION_NONE)
    session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$city = trim($_POST['city'] ?? '');

// Validation
if (!$name || !$phone || !$city) {
    echo json_encode(['success' => false, 'error' => 'All fields are required']);
    exit;
}
if (!preg_match('/^[0-9]{10}$/', $phone)) {
    echo json_encode(['success' => false, 'error' => 'Enter a valid 10-digit phone number']);
    exit;
}
if (strlen($name) > 100 || strlen($city) > 100) {
    echo json_encode(['success' => false, 'error' => 'Input too long']);
    exit;
}

// ✅ PREPARED STATEMENT — no SQL injection possible
$stmt = $conn->prepare(
    "INSERT INTO franchise_leads (name, phone, city, created_at) VALUES (?, ?, ?, NOW())"
);
$stmt->bind_param('sss', $name, $phone, $city);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Thank you! We will contact you soon.']);
} else {
    error_log('Franchise lead insert failed: ' . $stmt->error);
    echo json_encode(['success' => false, 'error' => 'Something went wrong. Please try again.']);
}

$stmt->close();
