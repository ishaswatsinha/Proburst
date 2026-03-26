<?php
// ajax/search.php
// Live product search — returns JSON array of matching products

if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');

if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

// ✅ Prepared statement — safe search
$search = '%' . $conn->real_escape_string($q) . '%';
$stmt   = $conn->prepare(
    "SELECT id, name, price, image, slug
     FROM products
     WHERE name LIKE ? OR description LIKE ?
     LIMIT 8"
);
$stmt->bind_param('ss', $search, $search);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = [
        'id'    => $row['id'],
        'name'  => $row['name'],
        'price' => (int)$row['price'],
        'image' => $row['image'],
        'slug'  => $row['slug'],
        'url'   => '/proburst/pages/product.php?slug=' . urlencode($row['slug'])
    ];
}

$stmt->close();
echo json_encode($products);
