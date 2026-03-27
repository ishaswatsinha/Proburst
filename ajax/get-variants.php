<?php
// ajax/get-variants.php
// Returns product details + all variants (flavours + weights) as JSON
// Called by the "Choose Options" modal on the homepage

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

include '../config/database.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

// ── Product base info ────────────────────────────────────────────────
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

// ── Flavour variants ─────────────────────────────────────────────────
// Table: product_flavours (id, product_id, name, in_stock)
$flavours = [];
$fCheck = $conn->query("SHOW TABLES LIKE 'product_flavours'");
if ($fCheck && $fCheck->num_rows > 0) {
    $fStmt = $conn->prepare("SELECT * FROM product_flavours WHERE product_id = ? ORDER BY sort_order ASC, id ASC");
    $fStmt->bind_param("i", $product_id);
    $fStmt->execute();
    $fResult = $fStmt->get_result();
    while ($f = $fResult->fetch_assoc()) {
        $flavours[] = $f;
    }
}

// ── Weight/Size variants ─────────────────────────────────────────────
// Table: product_weights (id, product_id, label, price, mrp, in_stock)
$weights = [];
$wCheck = $conn->query("SHOW TABLES LIKE 'product_weights'");
if ($wCheck && $wCheck->num_rows > 0) {
    $wStmt = $conn->prepare("SELECT * FROM product_weights WHERE product_id = ? ORDER BY sort_order ASC, id ASC");
    $wStmt->bind_param("i", $product_id);
    $wStmt->execute();
    $wResult = $wStmt->get_result();
    while ($w = $wResult->fetch_assoc()) {
        $weights[] = $w;
    }
}

// ── Calculate discount ────────────────────────────────────────────────
$price    = (float)$product['price'];
$mrp      = (float)($product['mrp'] ?? $price * 1.25); // fallback if no mrp column
$discount = $mrp > $price ? round((($mrp - $price) / $mrp) * 100) : 0;

echo json_encode([
    'success'   => true,
    'product'   => [
        'id'          => (int)$product['id'],
        'name'        => $product['name'],
        'price'       => $price,
        'mrp'         => round($mrp),
        'discount'    => $discount,
        'image'       => $product['image'],
        'slug'        => $product['slug'],
        'stock'       => (int)($product['stock'] ?? 10),
        'description' => $product['description'] ?? '',
    ],
    'flavours'  => $flavours,
    'weights'   => $weights,
]);
