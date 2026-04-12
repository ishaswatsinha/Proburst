<?php
// pages/order-confirmation.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../includes/auth.php';
require_once '../config/database.php';

// Must be logged in
requireLogin('/proburst/pages/login.php');

$order_id = (int)($_GET['order_id'] ?? 0);
$uid      = (int)$_SESSION['user_id'];

// Fetch order — make sure it belongs to this user
if ($order_id <= 0) { header('Location: /proburst/index.php'); exit; }

$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->bind_param('ii', $order_id, $uid);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) { header('Location: /proburst/index.php'); exit; }

// Fetch order items with product details
$items = [];
$iStmt = $conn->prepare("
    SELECT oi.qty, oi.price, p.name, p.image, p.slug
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$iStmt->bind_param('i', $order_id);
$iStmt->execute();
$iRes = $iStmt->get_result();
while ($row = $iRes->fetch_assoc()) $items[] = $row;
$iStmt->close();

$placedDate = date('d M Y, h:i A', strtotime($order['created_at']));
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<style>
/* ════════════════════════════════════════════
   ORDER CONFIRMATION PAGE
════════════════════════════════════════════ */
.oc-page {
  background: #f5f5f5;
  min-height: 80vh;
  padding: 50px 5%;
}

/* Success banner */
.oc-banner {
  background: linear-gradient(135deg, #ff6a00, #ff9d00);
  border-radius: 18px;
  padding: 40px 30px;
  text-align: center;
  margin-bottom: 32px;
  color: #fff;
  position: relative;
  overflow: hidden;
}
.oc-banner::before {
  content: '';
  position: absolute;
  width: 220px; height: 220px;
  background: rgba(255,255,255,.08);
  border-radius: 50%;
  top: -60px; right: -60px;
}
.oc-banner::after {
  content: '';
  position: absolute;
  width: 140px; height: 140px;
  background: rgba(255,255,255,.06);
  border-radius: 50%;
  bottom: -40px; left: -30px;
}
.oc-tick {
  font-size: 64px;
  display: block;
  margin-bottom: 12px;
  animation: tickPop .5s cubic-bezier(.18,.89,.32,1.28) both;
}
@keyframes tickPop {
  from { transform: scale(0); opacity: 0; }
  to   { transform: scale(1); opacity: 1; }
}
.oc-banner h1 {
  font-size: 28px;
  font-weight: 800;
  margin: 0 0 8px;
}
.oc-banner p {
  font-size: 15px;
  opacity: .9;
  margin: 0;
}
.oc-order-num {
  display: inline-block;
  background: rgba(255,255,255,.22);
  border: 2px solid rgba(255,255,255,.4);
  border-radius: 30px;
  padding: 7px 22px;
  font-size: 16px;
  font-weight: 800;
  margin-top: 16px;
  letter-spacing: 1px;
}

/* Main grid */
.oc-grid {
  display: grid;
  grid-template-columns: 1fr 360px;
  gap: 24px;
  max-width: 1100px;
  margin: 0 auto;
}

/* Cards */
.oc-card {
  background: #fff;
  border-radius: 14px;
  padding: 26px;
  box-shadow: 0 2px 12px rgba(0,0,0,.06);
}
.oc-card-title {
  font-size: 16px;
  font-weight: 800;
  color: #111;
  margin: 0 0 18px;
  padding-bottom: 14px;
  border-bottom: 2px solid #f0f0f0;
  display: flex;
  align-items: center;
  gap: 8px;
}

/* Order items list */
.oc-item {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 12px 0;
  border-bottom: 1px solid #f5f5f5;
}
.oc-item:last-child { border-bottom: none; }
.oc-item-img {
  width: 60px; height: 60px;
  object-fit: contain;
  border-radius: 8px;
  background: #f7f7f7;
  padding: 4px;
  flex-shrink: 0;
  border: 1px solid #eee;
}
.oc-item-name {
  flex: 1;
  font-size: 14px;
  font-weight: 600;
  color: #111;
  line-height: 1.4;
}
.oc-item-qty  { font-size: 13px; color: #888; margin-top: 3px; }
.oc-item-price { font-size: 15px; font-weight: 800; color: #ff6a00; white-space: nowrap; }

/* Total row */
.oc-total-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 18px;
  padding-top: 16px;
  border-top: 2px solid #f0f0f0;
  font-size: 17px;
  font-weight: 800;
  color: #111;
}
.oc-total-row span:last-child { color: #ff6a00; }

/* Shipping + status card */
.oc-detail-row {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.oc-detail-item {
  display: flex;
  gap: 10px;
  font-size: 14px;
  color: #444;
}
.oc-detail-label {
  font-weight: 700;
  color: #111;
  min-width: 90px;
  flex-shrink: 0;
}

/* Status badge */
.oc-status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 14px;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 700;
  background: rgba(245,158,11,.12);
  color: #d97706;
  border: 1px solid rgba(245,158,11,.3);
}

/* What happens next steps */
.oc-steps {
  display: flex;
  flex-direction: column;
  gap: 14px;
}
.oc-step {
  display: flex;
  align-items: flex-start;
  gap: 14px;
}
.oc-step-icon {
  width: 38px; height: 38px;
  border-radius: 50%;
  background: #fff5ee;
  border: 2px solid #ffcca0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  flex-shrink: 0;
}
.oc-step-text strong { display: block; font-size: 14px; color: #111; margin-bottom: 2px; }
.oc-step-text span   { font-size: 12px; color: #888; }

/* Action buttons */
.oc-actions {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-top: 6px;
}
.oc-btn-primary {
  display: block;
  width: 100%;
  padding: 13px;
  background: #ff6a00;
  color: #fff;
  text-align: center;
  border-radius: 10px;
  text-decoration: none;
  font-weight: 700;
  font-size: 15px;
  transition: background .2s, transform .2s;
}
.oc-btn-primary:hover { background: #e05500; transform: translateY(-1px); }
.oc-btn-ghost {
  display: block;
  width: 100%;
  padding: 13px;
  background: #f5f5f5;
  color: #333;
  text-align: center;
  border-radius: 10px;
  text-decoration: none;
  font-weight: 700;
  font-size: 15px;
  transition: background .2s;
}
.oc-btn-ghost:hover { background: #eee; }

/* Responsive */
@media (max-width: 900px) {
  .oc-grid { grid-template-columns: 1fr; }
  .oc-banner h1 { font-size: 22px; }
}
@media (max-width: 500px) {
  .oc-page { padding: 24px 16px; }
  .oc-card { padding: 18px; }
  .oc-banner { padding: 28px 18px; }
}
</style>

<div class="oc-page">

  <!-- SUCCESS BANNER -->
  <div class="oc-banner">
    <span class="oc-tick">✅</span>
    <h1>Order Placed Successfully!</h1>
    <p>Thank you, <strong><?= htmlspecialchars($order['name']) ?></strong>! Your order has been received.</p>
    <div class="oc-order-num">Order #<?= $order_id ?></div>
  </div>

  <div class="oc-grid">

    <!-- LEFT COLUMN -->
    <div style="display:flex;flex-direction:column;gap:24px;">

      <!-- ORDER ITEMS -->
      <div class="oc-card">
        <div class="oc-card-title">🛒 Items Ordered</div>
        <?php foreach ($items as $it): ?>
        <div class="oc-item">
          <img class="oc-item-img"
               src="../assets/images/<?= htmlspecialchars($it['image'] ?? '') ?>"
               alt="<?= htmlspecialchars($it['name'] ?? '') ?>"
               onerror="this.src='../assets/images/placeholder.png'">
          <div style="flex:1;">
            <div class="oc-item-name"><?= htmlspecialchars($it['name'] ?? 'Product') ?></div>
            <div class="oc-item-qty">Qty: <?= $it['qty'] ?></div>
          </div>
          <div class="oc-item-price">₹<?= number_format($it['price'] * $it['qty']) ?></div>
        </div>
        <?php endforeach; ?>
        <div class="oc-total-row">
          <span>Total Amount</span>
          <span>₹<?= number_format($order['total']) ?></span>
        </div>
      </div>

      <!-- SHIPPING DETAILS -->
      <div class="oc-card">
        <div class="oc-card-title">📦 Shipping Details</div>
        <div class="oc-detail-row">
          <div class="oc-detail-item">
            <span class="oc-detail-label">Name</span>
            <span><?= htmlspecialchars($order['name']) ?></span>
          </div>
          <div class="oc-detail-item">
            <span class="oc-detail-label">Phone</span>
            <span><?= htmlspecialchars($order['phone']) ?></span>
          </div>
          <?php if (!empty($order['email'])): ?>
          <div class="oc-detail-item">
            <span class="oc-detail-label">Email</span>
            <span><?= htmlspecialchars($order['email']) ?></span>
          </div>
          <?php endif; ?>
          <div class="oc-detail-item">
            <span class="oc-detail-label">Address</span>
            <span><?= htmlspecialchars($order['address']) ?>, <?= htmlspecialchars($order['city']) ?> — <?= htmlspecialchars($order['pincode']) ?></span>
          </div>
          <div class="oc-detail-item">
            <span class="oc-detail-label">Placed On</span>
            <span><?= $placedDate ?></span>
          </div>
          <div class="oc-detail-item">
            <span class="oc-detail-label">Status</span>
            <span class="oc-status-badge">⏳ <?= ucfirst($order['status']) ?></span>
          </div>
        </div>
      </div>

    </div>

    <!-- RIGHT COLUMN -->
    <div style="display:flex;flex-direction:column;gap:24px;">

      <!-- WHAT HAPPENS NEXT -->
      <div class="oc-card">
        <div class="oc-card-title">📋 What Happens Next?</div>
        <div class="oc-steps">
          <div class="oc-step">
            <div class="oc-step-icon">✅</div>
            <div class="oc-step-text">
              <strong>Order Confirmed</strong>
              <span>Your order has been received and logged.</span>
            </div>
          </div>
          <div class="oc-step">
            <div class="oc-step-icon">📦</div>
            <div class="oc-step-text">
              <strong>Processing</strong>
              <span>We'll pack your items within 1–2 business days.</span>
            </div>
          </div>
          <div class="oc-step">
            <div class="oc-step-icon">🚚</div>
            <div class="oc-step-text">
              <strong>Shipped</strong>
              <span>You'll receive a tracking update once dispatched.</span>
            </div>
          </div>
          <div class="oc-step">
            <div class="oc-step-icon">🏠</div>
            <div class="oc-step-text">
              <strong>Delivered</strong>
              <span>Expected delivery in 4–7 business days.</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ACTION BUTTONS -->
      <div class="oc-card">
        <div class="oc-card-title">🔗 Quick Links</div>
        <div class="oc-actions">
          <a href="/proburst/pages/account.php" class="oc-btn-primary">📋 View My Orders</a>
          <a href="/proburst/pages/shop.php"    class="oc-btn-ghost">🛒 Continue Shopping</a>
          <a href="/proburst/index.php"          class="oc-btn-ghost">🏠 Back to Home</a>
        </div>
      </div>

    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
