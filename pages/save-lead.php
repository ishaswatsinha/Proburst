<?php
include '../config/database.php';

/* DEBUG MODE */
error_reporting(E_ALL);
ini_set('display_errors', 1);

/* GET DATA */
$name = $_POST['name'] ?? '';
$phone = $_POST['phone'] ?? '';
$city = $_POST['city'] ?? '';

/* VALIDATION */
if (!$name || !$phone || !$city) {
    echo "Missing fields";
    exit;
}

/* INSERT */
$sql = "INSERT INTO franchise_leads (name, phone, city)
        VALUES ('$name', '$phone', '$city')";

if ($conn->query($sql)) {
    echo "success";
} else {
    echo "DB ERROR: " . $conn->error;
}