<?php
include '../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

$name = $data['name'];
$phone = $data['phone'];
$email = $data['email'];
$address = $data['address'];
$city = $data['city'];
$pincode = $data['pincode'];
$total = $data['total'];

/* INSERT ORDER */
$conn->query("
  INSERT INTO orders (name, phone, email, address, city, pincode, total)
  VALUES ('$name','$phone','$email','$address','$city','$pincode','$total')
");

$order_id = $conn->insert_id;

/* INSERT ITEMS */
foreach ($data['cart'] as $item) {

  $pid = $item['id'];
  $qty = $item['qty'];
  $price = $item['price'];

  $conn->query("
    INSERT INTO order_items (order_id, product_id, qty, price)
    VALUES ('$order_id','$pid','$qty','$price')
  ");
}

echo "success";