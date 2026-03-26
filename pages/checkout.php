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

<script>
let cart  = JSON.parse(localStorage.getItem('cart')) || [];
let total = 0;

// Render summary
(function() {
  const box = document.getElementById('order-items');
  if (!cart.length) { box.innerHTML = "<p style='color:#888'>Your cart is empty</p>"; return; }
  let html = '';
  cart.forEach(item => {
    const sub = item.price * item.qty;
    total += sub;
    html += `<div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:.9rem">
      <span>${item.name} × ${item.qty}</span>
      <span>₹${sub.toLocaleString('en-IN')}</span>
    </div>`;
  });
  box.innerHTML = html;
  document.getElementById('order-total').textContent = '₹' + total.toLocaleString('en-IN');
})();

document.getElementById('checkoutForm').addEventListener('submit', function(e) {
  e.preventDefault();

  if (!cart.length) { showMsg('Your cart is empty!', 'error'); return; }

  const btn = document.getElementById('placeBtn');
  btn.disabled    = true;
  btn.textContent = 'Placing Order...';

  const fd = new FormData(this);
  const data = {
    name:    fd.get('name').trim(),
    phone:   fd.get('phone').trim(),
    email:   fd.get('email').trim(),
    address: fd.get('address').trim(),
    city:    fd.get('city').trim(),
    pincode: fd.get('pincode').trim(),
    cart:    cart,
    total:   total
  };

  fetch('place-order.php', {
    method:  'POST',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify(data)
  })
  .then(r => r.text())                     // ✅ text() not json()
  .then(res => {
    if (res.includes('success')) {
      // Extract order id from "success:123"
      const parts   = res.split(':');
      const orderId = parts[1] || '';
      localStorage.removeItem('cart');
      if (typeof updateCartCount === 'function') updateCartCount();
      showMsg('✅ Order placed successfully!' + (orderId ? ' Order #' + orderId : ''), 'success');
      setTimeout(() => window.location.href = '/proburst/index.php', 2500);
    } else {
      const errMap = {
        'error:missing_fields': 'Please fill in all required fields.',
        'error:empty_cart':     'Your cart is empty.',
        'error:db_failed':      'Database error. Please try again.',
        'error:invalid_json':   'Request error. Please refresh and try again.'
      };
      showMsg('❌ ' + (errMap[res.trim()] || 'Something went wrong. Please try again.'), 'error');
      btn.disabled    = false;
      btn.textContent = 'Place Order';
    }
  })
  .catch(err => {
    console.error('Checkout fetch error:', err);
    showMsg('❌ Could not reach server. Check your connection.', 'error');
    btn.disabled    = false;
    btn.textContent = 'Place Order';
  });
});

function showMsg(msg, type) {
  const el = document.getElementById('checkout-msg');
  el.style.display    = 'block';
  el.style.background = type === 'success' ? '#0a2a0a' : '#2a0a0a';
  el.style.border     = '1px solid ' + (type === 'success' ? '#2d6a2d' : '#e63946');
  el.style.color      = type === 'success' ? '#6fcf97' : '#ff6b6b';
  el.textContent      = msg;
  el.scrollIntoView({ behavior: 'smooth' });
}
</script>
