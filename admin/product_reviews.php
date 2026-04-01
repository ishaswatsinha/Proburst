<?php
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/../config/database.php';

$msg = '';

// ── APPROVE / HIDE TOGGLE ──
if (isset($_GET['toggle'])) {
    $rid = (int)$_GET['toggle'];
    $r   = $conn->query("SELECT approved FROM product_reviews WHERE id=$rid")->fetch_assoc();
    $new = $r['approved'] ? 0 : 1;
    $conn->query("UPDATE product_reviews SET approved=$new WHERE id=$rid");
    $msg = $new ? "Review approved and visible." : "Review hidden from product page.";
}

// ── DELETE ──
if (isset($_GET['delete'])) {
    $rid = (int)$_GET['delete'];
    $r   = $conn->query("SELECT photo FROM product_reviews WHERE id=$rid")->fetch_assoc();
    if ($r && $r['photo']) @unlink(__DIR__ . '/../assets/images/reviews/' . $r['photo']);
    $conn->query("DELETE FROM product_reviews WHERE id=$rid");
    $msg = "Review deleted.";
}

// ── FILTERS ──
$filterStatus  = $_GET['status']  ?? '';
$filterProduct = (int)($_GET['product'] ?? 0);
$search        = $conn->real_escape_string($_GET['q'] ?? '');

$where = [];
if ($filterStatus === 'approved')    $where[] = "pr.approved=1";
if ($filterStatus === 'hidden')      $where[] = "pr.approved=0";
if ($filterStatus === 'verified')    $where[] = "pr.verified=1";
if ($filterProduct > 0)              $where[] = "pr.product_id=$filterProduct";
if ($search)                         $where[] = "(pr.name LIKE '%$search%' OR pr.title LIKE '%$search%' OR pr.body LIKE '%$search%')";
$whereSQL = $where ? 'WHERE '.implode(' AND ',$where) : '';

$reviews = $conn->query("
    SELECT pr.*, p.name AS pname, p.slug AS pslug
    FROM product_reviews pr
    LEFT JOIN products p ON pr.product_id = p.id
    $whereSQL
    ORDER BY pr.created_at DESC
");

// Stats
$totalReviews    = $conn->query("SELECT COUNT(*) FROM product_reviews")->fetch_row()[0];
$approvedReviews = $conn->query("SELECT COUNT(*) FROM product_reviews WHERE approved=1")->fetch_row()[0];
$hiddenReviews   = $conn->query("SELECT COUNT(*) FROM product_reviews WHERE approved=0")->fetch_row()[0];
$avgRating       = $conn->query("SELECT ROUND(AVG(rating),1) FROM product_reviews WHERE approved=1")->fetch_row()[0];

$productsList = $conn->query("SELECT id, name FROM products ORDER BY name");

adminHead('Product Reviews');
adminSidebar($navItems, $currentPage);
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Product <span>Reviews</span></h1>
    <p class="page-subtitle">Moderate customer reviews — approve, hide, or delete.</p>
  </div>
</div>

<?php if($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<!-- STATS -->
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px;">
  <div class="stat-card" style="--accent-color:#f59e0b">
    <div class="stat-icon">⭐</div>
    <div class="stat-value"><?= $totalReviews ?></div>
    <div class="stat-label">Total Reviews</div>
  </div>
  <div class="stat-card" style="--accent-color:#22c55e">
    <div class="stat-icon">✅</div>
    <div class="stat-value"><?= $approvedReviews ?></div>
    <div class="stat-label">Approved</div>
  </div>
  <div class="stat-card" style="--accent-color:#ef4444">
    <div class="stat-icon">🚫</div>
    <div class="stat-value"><?= $hiddenReviews ?></div>
    <div class="stat-label">Hidden</div>
  </div>
  <div class="stat-card" style="--accent-color:#ff4d00">
    <div class="stat-icon">📊</div>
    <div class="stat-value"><?= $avgRating ?: '—' ?></div>
    <div class="stat-label">Avg Rating</div>
  </div>
</div>

<!-- FILTERS -->
<form method="GET" class="filter-bar">
  <input type="text" name="q" placeholder="Search name, title, body..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
  <select name="product">
    <option value="">All Products</option>
    <?php $productsList->data_seek(0); while($p=$productsList->fetch_assoc()): ?>
      <option value="<?= $p['id'] ?>" <?= $filterProduct===$p['id']?'selected':'' ?>><?= htmlspecialchars($p['name']) ?></option>
    <?php endwhile; ?>
  </select>
  <select name="status">
    <option value="">All Status</option>
    <option value="approved" <?= $filterStatus==='approved'?'selected':'' ?>>Approved</option>
    <option value="hidden"   <?= $filterStatus==='hidden'  ?'selected':'' ?>>Hidden</option>
    <option value="verified" <?= $filterStatus==='verified'?'selected':'' ?>>Verified Purchase</option>
  </select>
  <button class="btn btn-ghost">Filter</button>
  <?php if($search||$filterProduct||$filterStatus): ?>
    <a href="product_reviews.php" class="btn btn-ghost">Clear</a>
  <?php endif; ?>
</form>

<!-- REVIEWS TABLE -->
<div class="table-card">
  <div class="table-card-header">
    <span class="table-card-title">Reviews (<?= $reviews->num_rows ?>)</span>
  </div>
  <div class="table-scroll">
  <?php if($reviews->num_rows > 0): ?>
  <table>
    <thead><tr>
      <th>#</th><th>Reviewer</th><th>Product</th><th>Rating</th><th>Title & Body</th><th>Photo</th><th>Status</th><th>Date</th><th>Actions</th>
    </tr></thead>
    <tbody>
    <?php while($r=$reviews->fetch_assoc()):
      $stars = str_repeat('★',(int)$r['rating']).str_repeat('☆',5-(int)$r['rating']);
    ?>
    <tr style="<?= !$r['approved'] ? 'opacity:.55' : '' ?>">
      <td class="text-muted"><?= $r['id'] ?></td>
      <td>
        <div class="fw-bold"><?= htmlspecialchars($r['name']) ?></div>
        <?php if($r['verified']): ?>
          <span class="badge badge-instock" style="font-size:10px;">✔ Verified</span>
        <?php endif; ?>
      </td>
      <td>
        <a href="../pages/product.php?slug=<?= htmlspecialchars($r['pslug']) ?>" target="_blank"
           style="color:var(--accent);text-decoration:none;font-size:13px;">
          <?= htmlspecialchars($r['pname']) ?>
        </a>
      </td>
      <td style="color:#f59e0b;font-size:15px;letter-spacing:1px;"><?= $stars ?></td>
      <td style="max-width:260px;">
        <?php if($r['title']): ?>
          <div class="fw-bold" style="font-size:13px;margin-bottom:3px;"><?= htmlspecialchars($r['title']) ?></div>
        <?php endif; ?>
        <div class="text-muted" style="font-size:12px;line-height:1.5;">
          <?= htmlspecialchars(mb_substr($r['body'],0,120)) ?><?= mb_strlen($r['body'])>120?'…':'' ?>
        </div>
      </td>
      <td>
        <?php if($r['photo']): ?>
          <img src="../assets/images/reviews/<?= htmlspecialchars($r['photo']) ?>"
               style="width:48px;height:48px;object-fit:cover;border-radius:6px;border:1px solid var(--border);"
               onerror="this.style.display='none'">
        <?php else: ?>
          <span class="text-muted">—</span>
        <?php endif; ?>
      </td>
      <td>
        <span class="badge <?= $r['approved'] ? 'badge-instock' : 'badge-outstock' ?>">
          <?= $r['approved'] ? 'Visible' : 'Hidden' ?>
        </span>
      </td>
      <td class="text-muted" style="white-space:nowrap;"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
      <td>
        <div class="action-group">
          <a href="product_reviews.php?toggle=<?= $r['id'] ?>"
             class="btn btn-sm <?= $r['approved'] ? 'btn-warning' : 'btn-success' ?>"
             style="<?= $r['approved'] ? 'background:rgba(245,158,11,.12);color:#f59e0b;border:1px solid rgba(245,158,11,.25)' : '' ?>">
            <?= $r['approved'] ? '🚫 Hide' : '✅ Approve' ?>
          </a>
          <a href="product_reviews.php?delete=<?= $r['id'] ?>" class="btn btn-danger btn-sm"
             onclick="return confirm('Permanently delete this review?')">🗑</a>
        </div>
      </td>
    </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
  <?php else: ?>
    <div class="empty-state"><div class="empty-icon">⭐</div><p>No reviews found.</p></div>
  <?php endif; ?>
  </div>
</div>

<?php adminClose(); ?>
