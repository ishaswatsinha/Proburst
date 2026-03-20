<?php
include '../config/database.php';

$slug = $_GET['slug'] ?? '';

if (!$slug) {
    die("Product not found");
}

$sql = "SELECT * FROM products WHERE slug='$slug'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    die("Product not found");
}

$product = $result->fetch_assoc();
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<div class="product-page">

    <!-- LEFT IMAGE -->
    <div class="product-image">
        <img src="../assets/images/<?php echo $product['image']; ?>">
    </div>

    <!-- RIGHT DETAILS -->
    <div class="product-details">

        <h1><?php echo $product['name']; ?></h1>

        <div class="rating">⭐⭐⭐⭐☆</div>

        <h2 class="price">₹<?php echo number_format($product['price']); ?></h2>

        <p class="desc">
            <?php echo $product['description']; ?>
        </p>

        <p class="stock">
            <?php echo $product['stock']; ?> items available
        </p>

        <button onclick="addToCart(
  <?php echo $row['id']; ?>,
  '<?php echo $row['name']; ?>',
  <?php echo $row['price']; ?>,
  '<?php echo $row['image']; ?>',
  this
)">
Add to Cart
</button>
    </div>

</div>

<?php include '../includes/footer.php'; ?>