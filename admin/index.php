<?php
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/../config/database.php';

// ── STATS ──
$totalOrders   = $conn->query("SELECT COUNT(*) FROM orders")->fetch_row()[0];
$totalRevenue  = $conn->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status != 'cancelled'")->fetch_row()[0];
$totalProducts = $conn->query("SELECT COUNT(*) FROM products")->fetch_row()[0];
$totalUsers    = $conn->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetch_row()[0];
$pendingOrders = $conn->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetch_row()[0];
$totalLeads    = $conn->query("SELECT COUNT(*) FROM franchise_leads")->fetch_row()[0];

// ── RECENT ORDERS ──
$recentOrders = $conn->query("SELECT o.*, u.name AS uname FROM orders o LEFT JOIN users u ON o.user_id=u.id ORDER BY o.created_at DESC LIMIT 8");

// ── LOW STOCK ──
$lowStock = $conn->query("SELECT * FROM products WHERE stock <= 5 ORDER BY stock ASC LIMIT 6");

adminHead('Dashboard');
adminSidebar($navItems, $currentPage);
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Dashboard <span>Overview</span></h1>
    <p class="page-subtitle">Welcome back, Admin — here's what's happening today.</p>
  </div>
</div>

<!-- STAT CARDS -->
<div class="stats-grid">
  <div class="stat-card" style="--accent-color:#ff4d00">
    <div class="stat-icon">📦</div>
    <div class="stat-value"><?= $totalOrders ?></div>
    <div class="stat-label">Total Orders</div>
  </div>
  <div class="stat-card" style="--accent-color:#22c55e">
    <div class="stat-icon">💰</div>
    <div class="stat-value">₹<?= number_format($totalRevenue) ?></div>
    <div class="stat-label">Revenue</div>
  </div>
  <div class="stat-card" style="--accent-color:#3b82f6">
    <div class="stat-icon">🛒</div>
    <div class="stat-value"><?= $totalProducts ?></div>
    <div class="stat-label">Products</div>
  </div>
  <div class="stat-card" style="--accent-color:#f59e0b">
    <div class="stat-icon">👥</div>
    <div class="stat-value"><?= $totalUsers ?></div>
    <div class="stat-label">Customers</div>
  </div>
  <div class="stat-card" style="--accent-color:#f59e0b">
    <div class="stat-icon">⏳</div>
    <div class="stat-value"><?= $pendingOrders ?></div>
    <div class="stat-label">Pending Orders</div>
  </div>
  <div class="stat-card" style="--accent-color:#a855f7">
    <div class="stat-icon">📋</div>
    <div class="stat-value"><?= $totalLeads ?></div>
    <div class="stat-label">Franchise Leads</div>
  </div>
</div>

<!-- RECENT ORDERS -->
<div class="table-card">
  <div class="table-card-header">
    <span class="table-card-title">Recent Orders</span>
    <a href="orders.php" class="btn btn-ghost btn-sm">View All →</a>
  </div>
  <div class="table-scroll"><table>
    <thead><tr>
      <th>#ID</th><th>Customer</th><th>City</th><th>Total</th><th>Status</th><th>Date</th><th>Action</th>
    </tr></thead>
    <tbody>
    <?php while($o = $recentOrders->fetch_assoc()): ?>
    <tr>
      <td class="fw-bold text-accent">#<?= $o['id'] ?></td>
      <td><?= htmlspecialchars($o['name']) ?><br><small class="text-muted"><?= htmlspecialchars($o['phone']) ?></small></td>
      <td><?= htmlspecialchars($o['city']) ?></td>
      <td class="fw-bold">₹<?= number_format($o['total']) ?></td>
      <td><span class="badge badge-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
      <td class="text-muted"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
      <td><a href="orders.php?view=<?= $o['id'] ?>" class="btn btn-info btn-sm">View</a></td>
    </tr>
    <?php endwhile; ?>
    </tbody>
  </table></div>
</div>

<!-- LOW STOCK -->
<?php if($lowStock->num_rows > 0): ?>
<div class="table-card">
  <div class="table-card-header">
    <span class="table-card-title">⚠️ Low Stock Alert</span>
    <a href="products.php" class="btn btn-ghost btn-sm">Manage Products</a>
  </div>
  <div class="table-scroll"><table>
    <thead><tr><th>Product</th><th>Stock Left</th><th>Price</th><th>Action</th></tr></thead>
    <tbody>
    <?php while($p = $lowStock->fetch_assoc()): ?>
    <tr>
      <td><?= htmlspecialchars($p['name']) ?></td>
      <td>
        <span class="badge <?= $p['stock'] == 0 ? 'badge-cancelled' : 'badge-pending' ?>">
          <?= $p['stock'] ?> units
        </span>
      </td>
      <td>₹<?= number_format($p['price']) ?></td>
      <td><a href="products.php?edit=<?= $p['id'] ?>" class="btn btn-ghost btn-sm">Edit</a></td>
    </tr>
    <?php endwhile; ?>
    </tbody>
  </table></div>
</div>
<?php endif; ?>

<?php adminClose(); ?>
