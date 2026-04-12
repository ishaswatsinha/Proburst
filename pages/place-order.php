<?php
// pages/place-order.php
// Receives JSON cart data, saves order + items to DB,
// decrements product stock, returns "success:ORDER_ID" or "error:reason"

if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../includes/auth.php';
require_once '../config/database.php';

// ── MUST BE LOGGED IN ──
if (!isLoggedIn()) {
    echo 'error:not_logged_in';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'error:method_not_allowed';
    exit;
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    echo 'error:invalid_json';
    exit;
}

$name    = trim($data['name']    ?? '');
$phone   = trim($data['phone']   ?? '');
$email   = trim($data['email']   ?? '');
$address = trim($data['address'] ?? '');
$city    = trim($data['city']    ?? '');
$pincode = trim($data['pincode'] ?? '');
$total   = (float)($data['total'] ?? 0);
$cart    = $data['cart'] ?? [];

if (!$name || !$phone || !$address || !$city || !$pincode) {
    echo 'error:missing_fields';
    exit;
}
if (empty($cart)) {
    echo 'error:empty_cart';
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// ── INSERT ORDER ──
$stmt = $conn->prepare(
    "INSERT INTO orders (user_id, name, phone, email, address, city, pincode, total, status, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())"
);
$stmt->bind_param('issssssd', $user_id, $name, $phone, $email, $address, $city, $pincode, $total);

if (!$stmt->execute()) {
    error_log('Order insert error: ' . $stmt->error);
    echo 'error:db_failed';
    exit;
}

$order_id = $conn->insert_id;
$stmt->close();

// ── INSERT ORDER ITEMS + DECREMENT STOCK ──
$itemStmt  = $conn->prepare(
    "INSERT INTO order_items (order_id, product_id, qty, price) VALUES (?, ?, ?, ?)"
);
// GREATEST(0, stock - qty) ensures stock never goes negative
$stockStmt = $conn->prepare(
    "UPDATE products SET stock = GREATEST(0, stock - ?) WHERE id = ?"
);

foreach ($cart as $item) {
    $pid   = (int)($item['id']    ?? 0);
    $qty   = (int)($item['qty']   ?? 1);
    $price = (float)($item['price'] ?? 0);

    if ($pid <= 0 || $qty <= 0) continue;

    // Save order item row
    $itemStmt->bind_param('iiid', $order_id, $pid, $qty, $price);
    $itemStmt->execute();

    // Decrement this product's stock
    $stockStmt->bind_param('ii', $qty, $pid);
    $stockStmt->execute();
}

$itemStmt->close();
$stockStmt->close();

// Return success + order ID so JS redirects to confirmation page
echo 'success:' . $order_id;
