<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['cart'] = json_decode($_POST['cart'], true);
}
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<h2 style="padding:20px;">Checkout</h2>

<pre>
<?php print_r($_SESSION['cart']); ?>
</pre>

<?php include '../includes/footer.php'; ?>