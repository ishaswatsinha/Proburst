<?php
session_start();
include '../config/database.php';

$id = $_GET['id'] ?? '';

if (!$id) {
    die("Invalid product");
}

/* GET PRODUCT */
$sql = "SELECT * FROM products WHERE id='$id'";
$result = $conn->query($sql);
$product = $result->fetch_assoc();

/* CREATE CART IF NOT EXISTS */
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

/* IF PRODUCT ALREADY IN CART */
if (isset($_SESSION['cart'][$id])) {
    $_SESSION['cart'][$id]['qty'] += 1;
} else {
    $_SESSION['cart'][$id] = [
        "id" => $product['id'],
        "name" => $product['name'],
        "price" => $product['price'],
        "image" => $product['image'],
        "qty" => 1
    ];
}

/* REDIRECT BACK */
header("Location: " . $_SERVER['HTTP_REFERER']);
exit;