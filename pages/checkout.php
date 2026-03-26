<?php
// pages/checkout.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../includes/auth.php';
require_once '../config/database.php';

// Pre-fill from session if logged in
$prefill = ['name' => '', 'phone' => '', 'email' => ''];
if (isLoggedIn()) {
    $uid = (int)$_SESSION['user_id'];
    $row = $conn->query("SELECT name, phone, email FROM users WHERE id = $uid LIMIT 1")->fetch_assoc();
    if ($row) $prefill = $row;
}
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<section class="checkout">
  <h2 class="section-title" style="text-align:center;margin-bottom:30px">Checkout</h2>

  <div class="checkout-container">

    <!-- LEFT: FORM -->
    <div class="checkout-form">
      <h3>Shipping Details</h3>

      <div id="checkout-msg" style="display:none;border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:.9rem;"></div>

      <form id="checkoutForm">
        <input type="text"  name="name"    placeholder="Full Name"    required value="<?= htmlspecialchars($prefill['name']) ?>">
        <input type="text"  name="phone"   placeholder="Phone Number" required value="<?= htmlspecialchars($prefill['phone'] ?? '') ?>">
        <input type="email" name="email"   placeholder="Email Address"          value="<?= htmlspecialchars($prefill['email']) ?>">
        <textarea name="address" placeholder="Full Address (House No, Street, Area)" required></textarea>
        <div class="row">
          <input type="text" name="city"    placeholder="City"    required>
          <input type="text" name="pincode" placeholder="Pincode" required maxlength="6">
        </div>
        <button type="submit" id="placeBtn">Place Order</button>
      </form>
    </div>

    <!-- RIGHT: SUMMARY -->
    <div class="checkout-summary">
      <h3>Order Summary</h3>
      <div id="order-items"></div>
      <div style="display:flex;justify-content:space-between;margin-top:16px;padding-top:14px;border-top:1px solid #ddd">
        <span>Total</span>
        <strong id="order-total">₹0</strong>
      </div>
    </div>

  </div>
</section>

<?php include '../includes/footer.php'; ?>


