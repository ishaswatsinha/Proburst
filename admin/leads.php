<?php
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/../config/database.php';

$msg = '';

// ── DELETE ──
if (isset($_GET['delete'])) {
    $conn->query("DELETE FROM franchise_leads WHERE id=".(int)$_GET['delete']);
    $msg = "Lead deleted.";
}

// ── SEARCH ──
$search = $conn->real_escape_string($_GET['q'] ?? '');
$where  = $search ? "WHERE name LIKE '%$search%' OR phone LIKE '%$search%' OR city LIKE '%$search%'" : '';
$leads  = $conn->query("SELECT * FROM franchise_leads $where ORDER BY created_at DESC");

$totalLeads = $conn->query("SELECT COUNT(*) FROM franchise_leads")->fetch_row()[0];
$cities     = $conn->query("SELECT COUNT(DISTINCT city) FROM franchise_leads")->fetch_row()[0];
$thisMonth  = $conn->query("SELECT COUNT(*) FROM franchise_leads WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())")->fetch_row()[0];

adminHead('Franchise Leads');
adminSidebar($navItems, $currentPage);
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Franchise <span>Leads</span></h1>
    <p class="page-subtitle">Enquiries from people interested in Proburst franchises.</p>
  </div>
</div>

<?php if($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:24px">
  <div class="stat-card" style="--accent-color:#a855f7">
    <div class="stat-icon">📋</div>
    <div class="stat-value"><?= $totalLeads ?></div>
    <div class="stat-label">Total Leads</div>
  </div>
  <div class="stat-card" style="--accent-color:#3b82f6">
    <div class="stat-icon">📍</div>
    <div class="stat-value"><?= $cities ?></div>
    <div class="stat-label">Cities</div>
  </div>
  <div class="stat-card" style="--accent-color:#22c55e">
    <div class="stat-icon">🗓️</div>
    <div class="stat-value"><?= $thisMonth ?></div>
    <div class="stat-label">This Month</div>
  </div>
</div>

<!-- SEARCH -->
<form method="GET" class="filter-bar">
  <input type="text" name="q" placeholder="Search name, phone, city..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
  <button class="btn btn-ghost">Search</button>
  <?php if(!empty($_GET['q'])): ?><a href="leads.php" class="btn btn-ghost">Clear</a><?php endif; ?>
</form>

<div class="table-card">
  <div class="table-card-header">
    <span class="table-card-title">All Leads (<?= $leads->num_rows ?>)</span>
    <?php if($leads->num_rows > 0): ?>
    <a href="leads.php?export=csv" class="btn btn-ghost btn-sm">⬇ Export CSV</a>
    <?php endif; ?>
  </div>
  <?php if($leads->num_rows > 0): ?>
  <table>
    <thead><tr><th>#</th><th>Name</th><th>Phone</th><th>City</th><th>Date</th><th>Actions</th></tr></thead>
    <tbody>
    <?php while($l=$leads->fetch_assoc()): ?>
    <tr>
      <td class="text-muted"><?= $l['id'] ?></td>
      <td class="fw-bold"><?= htmlspecialchars($l['name']) ?></td>
      <td>
        <a href="tel:<?= htmlspecialchars($l['phone']) ?>" style="color:var(--accent);text-decoration:none">
          <?= htmlspecialchars($l['phone']) ?>
        </a>
      </td>
      <td><?= htmlspecialchars($l['city']) ?></td>
      <td class="text-muted"><?= date('d M Y, h:i A', strtotime($l['created_at'])) ?></td>
      <td>
        <a href="leads.php?delete=<?= $l['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this lead?')">🗑 Delete</a>
      </td>
    </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
  <?php else: ?>
    <div class="empty-state"><div class="empty-icon">📋</div><p>No franchise leads yet.</p></div>
  <?php endif; ?>
</div>

<?php adminClose(); ?>
