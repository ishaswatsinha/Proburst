<?php
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/../config/database.php';

$msg = '';

// ── UPDATE STATUS ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $oid  = (int)$_POST['order_id'];
    $stat = $conn->real_escape_string($_POST['status']);
    $allowed = ['pending','shipped','delivered','cancelled'];
    if (in_array($stat, $allowed)) {
        $conn->query("UPDATE orders SET status='$stat' WHERE id=$oid");
        $msg = "Order #$oid status updated to $stat.";
    }
}

// ── VIEW SINGLE ORDER ──
$viewOrder = null;
$orderItems = [];
if (isset($_GET['view'])) {
    $vid = (int)$_GET['view'];
    $res = $conn->query("SELECT o.*, u.email AS uemail FROM orders o LEFT JOIN users u ON o.user_id=u.id WHERE o.id=$vid");
    $viewOrder = $res->fetch_assoc();
    $itemRes = $conn->query("
        SELECT oi.*, p.name AS pname, p.image
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id=p.id
        WHERE oi.order_id=$vid
    ");
    while($item = $itemRes->fetch_assoc()) $orderItems[] = $item;
}

// ── FILTER ──
$statusFilter = $_GET['status'] ?? '';
$where = $statusFilter ? "WHERE o.status='".($conn->real_escape_string($statusFilter))."'" : '';
$orders = $conn->query("SELECT o.*, u.name AS uname FROM orders o LEFT JOIN users u ON o.user_id=u.id $where ORDER BY o.created_at DESC");

adminHead('Orders');
adminSidebar($navItems, $currentPage);
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Orders <span>Management</span></h1>
    <p class="page-subtitle">View, filter, and update all customer orders.</p>
  </div>
</div>

<?php if($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<!-- SINGLE ORDER VIEW MODAL -->
<?php if ($viewOrder): ?>
<div class="form-card" style="border-left:3px solid var(--accent)">
  <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px">
    <div>
      <h2 class="table-card-title">Order #<?= $viewOrder['id'] ?> Details</h2>
      <p class="text-muted" style="margin-top:4px">Placed on <?= date('d M Y, h:i A', strtotime($viewOrder['created_at'])) ?></p>
    </div>
    <a href="orders.php" class="btn btn-ghost btn-sm">✕ Close</a>
  </div>
  <div class="form-grid">
    <div>
      <p class="text-muted" style="font-size:11px;text-transform:uppercase;margin-bottom:6px">Customer Info</p>
      <p class="fw-bold"><?= htmlspecialchars($viewOrder['name']) ?></p>
      <p><?= htmlspecialchars($viewOrder['phone']) ?></p>
      <p><?= htmlspecialchars($viewOrder['email']) ?></p>
    </div>
    <div>
      <p class="text-muted" style="font-size:11px;text-transform:uppercase;margin-bottom:6px">Shipping Address</p>
      <p><?= htmlspecialchars($viewOrder['address']) ?></p>
      <p><?= htmlspecialchars($viewOrder['city']) ?>, <?= htmlspecialchars($viewOrder['pincode']) ?></p>
    </div>
    <div>
      <p class="text-muted" style="font-size:11px;text-transform:uppercase;margin-bottom:6px">Order Summary</p>
      <p>Total: <span class="fw-bold text-accent">₹<?= number_format($viewOrder['total']) ?></span></p>
      <p>Status: <span class="badge badge-<?= $viewOrder['status'] ?>"><?= ucfirst($viewOrder['status']) ?></span></p>
    </div>
  </div>

  <!-- ITEMS -->
  <div class="divider"></div>
  <p class="text-muted" style="font-size:11px;text-transform:uppercase;margin-bottom:12px">Items Ordered</p>
  <table>
    <thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead>
    <tbody>
    <?php foreach($orderItems as $it): ?>
    <tr>
      <td style="display:flex;align-items:center;gap:10px">
        <img src="../assets/images/<?= htmlspecialchars($it['image']) ?>" class="product-thumb" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22/>'">
        <?= htmlspecialchars($it['pname']) ?>
      </td>
      <td><?= $it['qty'] ?></td>
      <td>₹<?= number_format($it['price']) ?></td>
      <td class="fw-bold">₹<?= number_format($it['qty'] * $it['price']) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <!-- UPDATE STATUS -->
  <div class="divider"></div>
  <form method="POST" style="display:flex;gap:10px;align-items:center">
    <input type="hidden" name="order_id" value="<?= $viewOrder['id'] ?>">
    <label class="text-muted" style="font-size:13px">Update Status:</label>
    <select name="status" style="background:var(--surface);border:1px solid var(--border);color:var(--text);padding:7px 12px;border-radius:7px;font-size:13px">
      <?php foreach(['pending','shipped','delivered','cancelled'] as $s): ?>
        <option value="<?= $s ?>" <?= $viewOrder['status']==$s?'selected':'' ?>><?= ucfirst($s) ?></option>
      <?php endforeach; ?>
    </select>
    <button name="update_status" class="btn btn-primary">Update</button>
  </form>
</div>
<?php endif; ?>

<!-- FILTER BAR -->
<div class="filter-bar">
  <span class="text-muted">Filter by status:</span>
  <?php foreach(['','pending','shipped','delivered','cancelled'] as $s): ?>
    <a href="orders.php<?= $s ? '?status='.$s : '' ?>"
       class="btn btn-sm <?= $statusFilter === $s ? 'btn-primary' : 'btn-ghost' ?>">
      <?= $s ? ucfirst($s) : 'All' ?>
    </a>
  <?php endforeach; ?>
</div>

<!-- ORDERS TABLE -->
<div class="table-card">
  <div class="table-card-header">
    <span class="table-card-title">All Orders (<?= $orders->num_rows ?>)</span>
  </div>
  <?php if($orders->num_rows > 0): ?>
  <table>
    <thead><tr>
      <th>#ID</th><th>Customer</th><th>Phone</th><th>City</th><th>Total</th><th>Status</th><th>Date</th><th>Actions</th>
    </tr></thead>
    <tbody>
    <?php while($o = $orders->fetch_assoc()): ?>
    <tr>
      <td class="fw-bold text-accent">#<?= $o['id'] ?></td>
      <td><?= htmlspecialchars($o['name']) ?></td>
      <td class="text-muted"><?= htmlspecialchars($o['phone']) ?></td>
      <td><?= htmlspecialchars($o['city']) ?></td>
      <td class="fw-bold">₹<?= number_format($o['total']) ?></td>
      <td>
        <form method="POST" class="inline-form">
          <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
          <select name="status" onchange="this.form.submit()"
            style="background:var(--surface);border:1px solid var(--border);color:var(--text);padding:4px 8px;border-radius:6px;font-size:12px;cursor:pointer">
            <?php foreach(['pending','shipped','delivered','cancelled'] as $s): ?>
              <option value="<?= $s ?>" <?= $o['status']==$s?'selected':'' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
          </select>
          <input type="hidden" name="update_status" value="1">
        </form>
      </td>
      <td class="text-muted"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
      <td>
        <a href="orders.php?view=<?= $o['id'] ?>" class="btn btn-info btn-sm">👁 View</a>
      </td>
    </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
  <?php else: ?>
    <div class="empty-state"><div class="empty-icon">📦</div><p>No orders found.</p></div>
  <?php endif; ?>
</div>

<?php adminClose(); ?>
