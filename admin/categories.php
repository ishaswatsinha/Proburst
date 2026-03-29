<?php
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/../config/database.php';

$msg = '';

function slugify(string $t): string {
    return trim(preg_replace('/[^a-z0-9]+/','-',strtolower(trim($t))),'-');
}

// ── DELETE CATEGORY ──
if (isset($_GET['delete_cat'])) {
    $id = (int)$_GET['delete_cat'];
    $conn->query("DELETE FROM categories WHERE id=$id");
    $msg = "Category deleted.";
}
// ── DELETE SUBCATEGORY ──
if (isset($_GET['delete_sub'])) {
    $id = (int)$_GET['delete_sub'];
    $conn->query("DELETE FROM subcategories WHERE id=$id");
    $msg = "Subcategory deleted.";
}

// ── SAVE CATEGORY ──
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_cat'])) {
    $cid  = (int)($_POST['cat_id'] ?? 0);
    $name = $conn->real_escape_string(trim($_POST['cat_name']));
    $slug = $conn->real_escape_string($_POST['cat_slug'] ?: slugify($_POST['cat_name']));
    if ($cid > 0) {
        $conn->query("UPDATE categories SET name='$name',slug='$slug' WHERE id=$cid");
        $msg = "Category updated.";
    } else {
        $conn->query("INSERT INTO categories (name,slug) VALUES ('$name','$slug')");
        $msg = "Category added.";
    }
}

// ── SAVE SUBCATEGORY ──
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_sub'])) {
    $sid   = (int)($_POST['sub_id'] ?? 0);
    $catId = (int)$_POST['sub_cat_id'];
    $name  = $conn->real_escape_string(trim($_POST['sub_name']));
    $slug  = $conn->real_escape_string($_POST['sub_slug'] ?: slugify($_POST['sub_name']));
    if ($sid > 0) {
        $conn->query("UPDATE subcategories SET category_id=$catId,name='$name',slug='$slug' WHERE id=$sid");
        $msg = "Subcategory updated.";
    } else {
        $conn->query("INSERT INTO subcategories (category_id,name,slug) VALUES ($catId,'$name','$slug')");
        $msg = "Subcategory added.";
    }
}

// ── LOAD EDIT ──
$editCat = null; $editSub = null;
if (isset($_GET['edit_cat'])) $editCat = $conn->query("SELECT * FROM categories WHERE id=".(int)$_GET['edit_cat'])->fetch_assoc();
if (isset($_GET['edit_sub'])) $editSub = $conn->query("SELECT * FROM subcategories WHERE id=".(int)$_GET['edit_sub'])->fetch_assoc();

// ── LOAD DATA ──
$cats = $conn->query("SELECT c.*, COUNT(s.id) as sub_count FROM categories c LEFT JOIN subcategories s ON s.category_id=c.id GROUP BY c.id ORDER BY c.name");
$subs = $conn->query("SELECT s.*, c.name as cname FROM subcategories s LEFT JOIN categories c ON s.category_id=c.id ORDER BY c.name, s.name");
$catsDropdown = $conn->query("SELECT * FROM categories ORDER BY name");

adminHead('Categories');
adminSidebar($navItems, $currentPage);
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Categories <span>& Subcategories</span></h1>
    <p class="page-subtitle">Manage product categorization structure.</p>
  </div>
</div>

<?php if($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">

<!-- CATEGORY FORM -->
<div class="form-card">
  <h2 class="table-card-title" style="margin-bottom:16px"><?= $editCat ? 'Edit Category' : 'Add Category' ?></h2>
  <form method="POST">
    <input type="hidden" name="cat_id" value="<?= $editCat['id'] ?? 0 ?>">
    <div class="form-group" style="margin-bottom:12px">
      <label>Category Name *</label>
      <input type="text" name="cat_name" required value="<?= htmlspecialchars($editCat['name'] ?? '') ?>">
    </div>
    <div class="form-group" style="margin-bottom:16px">
      <label>Slug</label>
      <input type="text" name="cat_slug" value="<?= htmlspecialchars($editCat['slug'] ?? '') ?>">
    </div>
    <div class="form-actions">
      <button name="save_cat" class="btn btn-primary">💾 Save</button>
      <?php if($editCat): ?><a href="categories.php" class="btn btn-ghost">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>

<!-- SUBCATEGORY FORM -->
<div class="form-card">
  <h2 class="table-card-title" style="margin-bottom:16px"><?= $editSub ? 'Edit Subcategory' : 'Add Subcategory' ?></h2>
  <form method="POST">
    <input type="hidden" name="sub_id" value="<?= $editSub['id'] ?? 0 ?>">
    <div class="form-group" style="margin-bottom:12px">
      <label>Parent Category *</label>
      <select name="sub_cat_id" required>
        <option value="">Select</option>
        <?php $catsDropdown->data_seek(0); while($c=$catsDropdown->fetch_assoc()): ?>
          <option value="<?= $c['id'] ?>" <?= ($editSub['category_id']??'') == $c['id'] ? 'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="form-group" style="margin-bottom:12px">
      <label>Subcategory Name *</label>
      <input type="text" name="sub_name" required value="<?= htmlspecialchars($editSub['name'] ?? '') ?>">
    </div>
    <div class="form-group" style="margin-bottom:16px">
      <label>Slug</label>
      <input type="text" name="sub_slug" value="<?= htmlspecialchars($editSub['slug'] ?? '') ?>">
    </div>
    <div class="form-actions">
      <button name="save_sub" class="btn btn-primary">💾 Save</button>
      <?php if($editSub): ?><a href="categories.php" class="btn btn-ghost">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>

</div>

<!-- CATEGORIES TABLE -->
<div class="table-card" style="margin-bottom:24px">
  <div class="table-card-header">
    <span class="table-card-title">All Categories (<?= $cats->num_rows ?>)</span>
  </div>
  <div class="table-scroll"><table>
    <thead><tr><th>#</th><th>Name</th><th>Slug</th><th>Subcategories</th><th>Actions</th></tr></thead>
    <tbody>
    <?php while($c=$cats->fetch_assoc()): ?>
    <tr>
      <td class="text-muted"><?= $c['id'] ?></td>
      <td class="fw-bold"><?= htmlspecialchars($c['name']) ?></td>
      <td class="text-muted"><?= htmlspecialchars($c['slug']) ?></td>
      <td><span class="badge badge-info" style="background:rgba(59,130,246,.15);color:#3b82f6"><?= $c['sub_count'] ?> subs</span></td>
      <td>
        <div class="action-group">
          <a href="categories.php?edit_cat=<?= $c['id'] ?>" class="btn btn-ghost btn-sm">✏️</a>
          <a href="categories.php?delete_cat=<?= $c['id'] ?>" class="btn btn-danger btn-sm"
             onclick="return confirm('Delete this category and all its subcategories?')">🗑</a>
        </div>
      </td>
    </tr>
    <?php endwhile; ?>
    </tbody>
  </table></div>
</div>

<!-- SUBCATEGORIES TABLE -->
<div class="table-card">
  <div class="table-card-header">
    <span class="table-card-title">All Subcategories (<?= $subs->num_rows ?>)</span>
  </div>
  <div class="table-scroll"><table>
    <thead><tr><th>#</th><th>Name</th><th>Parent Category</th><th>Slug</th><th>Actions</th></tr></thead>
    <tbody>
    <?php while($s=$subs->fetch_assoc()): ?>
    <tr>
      <td class="text-muted"><?= $s['id'] ?></td>
      <td class="fw-bold"><?= htmlspecialchars($s['name']) ?></td>
      <td class="text-muted"><?= htmlspecialchars($s['cname'] ?? '—') ?></td>
      <td class="text-muted"><?= htmlspecialchars($s['slug']) ?></td>
      <td>
        <div class="action-group">
          <a href="categories.php?edit_sub=<?= $s['id'] ?>" class="btn btn-ghost btn-sm">✏️</a>
          <a href="categories.php?delete_sub=<?= $s['id'] ?>" class="btn btn-danger btn-sm"
             onclick="return confirm('Delete this subcategory?')">🗑</a>
        </div>
      </td>
    </tr>
    <?php endwhile; ?>
    </tbody>
  </table></div>
</div>

<?php adminClose(); ?>
