<?php
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/../config/database.php';

$msg = '';

// ── DELETE ──
if (isset($_GET['delete'])) {
    $conn->query("DELETE FROM reviews WHERE id=".(int)$_GET['delete']);
    $msg = "Review deleted.";
}

// ── TOGGLE VERIFIED ──
if (isset($_GET['toggle'])) {
    $rid = (int)$_GET['toggle'];
    $r = $conn->query("SELECT verified FROM reviews WHERE id=$rid")->fetch_assoc();
    $nv = $r['verified'] ? 0 : 1;
    $conn->query("UPDATE reviews SET verified=$nv WHERE id=$rid");
    $msg = "Review verification toggled.";
}

// ── SAVE ──
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_review'])) {
    $rid   = (int)($_POST['review_id'] ?? 0);
    $name  = $conn->real_escape_string(trim($_POST['name']));
    $role  = $conn->real_escape_string(trim($_POST['role']));
    $title = $conn->real_escape_string(trim($_POST['title']));
    $video = $conn->real_escape_string(trim($_POST['video']));
    $likes = (int)$_POST['likes'];
    $ver   = isset($_POST['verified']) ? 1 : 0;
    if ($rid > 0) {
        $conn->query("UPDATE reviews SET name='$name',role='$role',title='$title',video='$video',likes=$likes,verified=$ver WHERE id=$rid");
        $msg = "Review updated.";
    } else {
        $conn->query("INSERT INTO reviews (name,role,title,video,likes,verified) VALUES ('$name','$role','$title','$video',$likes,$ver)");
        $msg = "Review added.";
    }
}

$editReview = null;
if (isset($_GET['edit'])) $editReview = $conn->query("SELECT * FROM reviews WHERE id=".(int)$_GET['edit'])->fetch_assoc();

$reviews = $conn->query("SELECT * FROM reviews ORDER BY created_at DESC");

adminHead('Reviews');
adminSidebar($navItems, $currentPage);
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Reviews <span>& Testimonials</span></h1>
    <p class="page-subtitle">Manage video testimonials shown on the homepage.</p>
  </div>
  <a href="reviews.php?add=1" class="btn btn-primary">+ Add Review</a>
</div>

<?php if($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<?php if (isset($_GET['add']) || $editReview): ?>
<div class="form-card">
  <h2 class="table-card-title" style="margin-bottom:16px"><?= $editReview ? 'Edit Review' : 'Add Review' ?></h2>
  <form method="POST">
    <input type="hidden" name="review_id" value="<?= $editReview['id'] ?? 0 ?>">
    <div class="form-grid">
      <div class="form-group">
        <label>Reviewer Name *</label>
        <input type="text" name="name" required value="<?= htmlspecialchars($editReview['name'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Role / Title</label>
        <input type="text" name="role" placeholder="e.g. IFBB PRO" value="<?= htmlspecialchars($editReview['role'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Review Title</label>
        <input type="text" name="title" value="<?= htmlspecialchars($editReview['title'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Video Filename (in assets/videos/)</label>
        <input type="text" name="video" placeholder="review1.mp4" value="<?= htmlspecialchars($editReview['video'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Likes Count</label>
        <input type="number" name="likes" min="0" value="<?= $editReview['likes'] ?? 0 ?>">
      </div>
      <div class="form-group" style="justify-content:flex-end">
        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;margin-top:20px">
          <input type="checkbox" name="verified" <?= ($editReview['verified'] ?? 1) ? 'checked' : '' ?>> Verified Badge
        </label>
      </div>
    </div>
    <div class="form-actions">
      <button name="save_review" class="btn btn-primary">💾 Save</button>
      <a href="reviews.php" class="btn btn-ghost">Cancel</a>
    </div>
  </form>
</div>
<?php endif; ?>

<div class="table-card">
  <div class="table-card-header">
    <span class="table-card-title">All Reviews (<?= $reviews->num_rows ?>)</span>
  </div>
  <?php if($reviews->num_rows > 0): ?>
  <div class="table-scroll"><table>
    <thead><tr><th>Name</th><th>Role</th><th>Title</th><th>Video</th><th>Likes</th><th>Verified</th><th>Actions</th></tr></thead>
    <tbody>
    <?php while($r=$reviews->fetch_assoc()): ?>
    <tr>
      <td class="fw-bold"><?= htmlspecialchars($r['name']) ?></td>
      <td class="text-muted"><?= htmlspecialchars($r['role']) ?></td>
      <td><?= htmlspecialchars($r['title']) ?></td>
      <td class="text-muted"><?= htmlspecialchars($r['video']) ?></td>
      <td>❤️ <?= $r['likes'] ?></td>
      <td>
        <a href="reviews.php?toggle=<?= $r['id'] ?>" class="badge <?= $r['verified'] ? 'badge-instock' : 'badge-outstock' ?>" style="cursor:pointer;text-decoration:none">
          <?= $r['verified'] ? '✓ Verified' : '✗ Unverified' ?>
        </a>
      </td>
      <td>
        <div class="action-group">
          <a href="reviews.php?edit=<?= $r['id'] ?>" class="btn btn-ghost btn-sm">✏️</a>
          <a href="reviews.php?delete=<?= $r['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete?')">🗑</a>
        </div>
      </td>
    </tr>
    <?php endwhile; ?>
    </tbody>
  </table></div>
  <?php else: ?>
    <div class="empty-state"><div class="empty-icon">⭐</div><p>No reviews yet.</p></div>
  <?php endif; ?>
</div>

<?php adminClose(); ?>
