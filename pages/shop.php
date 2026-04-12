<?php
include '../config/database.php';

/* =========================
   FETCH DATA
========================= */

$categoriesData = $conn->query("SELECT * FROM categories");

$subcategoriesData = [];
$subQuery = $conn->query("SELECT * FROM subcategories");

while ($sub = $subQuery->fetch_assoc()) {
  $subcategoriesData[$sub['category_id']][] = $sub;
}

/* =========================
   FILTER VALUES
========================= */

$category = $_GET['category'] ?? [];
$subcategory = $_GET['subcategory'] ?? '';
$price = $_GET['price'] ?? '';
$sort = $_GET['sort'] ?? '';

$where = [];

/* CATEGORY FILTER ✅ FIXED */
if (!empty($category)) {
  $cat_ids = implode(",", array_map('intval', $category));
  $where[] = "category_id IN ($cat_ids)";
}

/* SUBCATEGORY FILTER */
if (!empty($subcategory)) {
  $where[] = "subcategory_id = " . (int)$subcategory;
}

/* PRICE */
if ($price == 'low') {
  $where[] = "price < 1000";
} elseif ($price == 'mid') {
  $where[] = "price BETWEEN 1000 AND 3000";
} elseif ($price == 'high') {
  $where[] = "price > 3000";
}

/* BASE QUERY */
$sql = "SELECT * FROM products";

if (!empty($where)) {
  $sql .= " WHERE " . implode(" AND ", $where);
}

/* SORT */
if ($sort == 'low') {
  $sql .= " ORDER BY price ASC";
} elseif ($sort == 'high') {
  $sql .= " ORDER BY price DESC";
}

$result = $conn->query($sql);
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<div class="shop-page">

<!-- =========================
   SIDEBAR
========================= -->
<aside class="sidebar">

<form method="GET" id="filterForm">

<h3 class="sidebar-title">CATEGORIES</h3>

<?php while($cat = $categoriesData->fetch_assoc()): ?>

  <div class="category-block">

    <!-- ✅ CATEGORY CHECKBOX (FIX) -->
    <label class="category-main">
      <input type="checkbox" name="category[]" value="<?php echo $cat['id']; ?>"
      <?php if (in_array($cat['id'], $category)) echo 'checked'; ?>>
      <?php echo $cat['name']; ?>
    </label>

    <!-- SUBCATEGORIES -->
    <div class="subcategory-list">

      <?php if (!empty($subcategoriesData[$cat['id']])): ?>
        <?php foreach($subcategoriesData[$cat['id']] as $sub): ?>

          <label class="subcategory-item">

            <input type="radio" name="subcategory" value="<?php echo $sub['id']; ?>"
            <?php if ($subcategory == $sub['id']) echo 'checked'; ?>>

            <?php echo $sub['name']; ?>

          </label>

        <?php endforeach; ?>
      <?php endif; ?>

    </div>

  </div>

<?php endwhile; ?>


<!-- PRICE -->
<h4 class="sidebar-subtitle">PRICE</h4>

<label class="price-item">
  <input type="radio" name="price" value="low" <?= $price=='low'?'checked':'' ?>>
  Below ₹1000
</label>

<label class="price-item">
  <input type="radio" name="price" value="mid" <?= $price=='mid'?'checked':'' ?>>
  ₹1000 - ₹3000
</label>

<label class="price-item">
  <input type="radio" name="price" value="high" <?= $price=='high'?'checked':'' ?>>
  Above ₹3000
</label>

</form>

</aside>


<!-- =========================
   PRODUCTS
========================= -->
<div class="products-section">

<div class="top-bar">
  <h2>Shop By Products (<?php echo $result->num_rows; ?>)</h2>

  <form method="GET">

    <!-- PRESERVE FILTERS -->
    <?php foreach($category as $c): ?>
      <input type="hidden" name="category[]" value="<?= $c ?>">
    <?php endforeach; ?>

    <?php if($subcategory): ?>
      <input type="hidden" name="subcategory" value="<?= $subcategory ?>">
    <?php endif; ?>

    <?php if($price): ?>
      <input type="hidden" name="price" value="<?= $price ?>">
    <?php endif; ?>

    <select name="sort" onchange="this.form.submit()">

      <option value="">Sort</option>

      <option value="low" <?= $sort=='low'?'selected':'' ?>>
        Price Low to High
      </option>

      <option value="high" <?= $sort=='high'?'selected':'' ?>>
        Price High to Low
      </option>

    </select>

  </form>
</div>


<!-- PRODUCT GRID -->
<div class="product-grid">

<?php if ($result->num_rows > 0): ?>

  <?php while ($row = $result->fetch_assoc()): ?>

    <div class="product-card">

      <img src="../assets/images/<?php echo $row['image']; ?>">

      <h3>
        <a href="product.php?slug=<?php echo $row['slug']; ?>">
          <?php echo $row['name']; ?>
        </a>
      </h3>

      <p class="price">₹<?php echo number_format($row['price']); ?></p>

      <?php if ($row['stock'] > 0): ?>
      <button onclick="addToCart(
        <?php echo $row['id']; ?>,
        '<?php echo $row['name']; ?>',
        <?php echo $row['price']; ?>,
        '<?php echo $row['image']; ?>',
        this
      )">
        Add to Cart
      </button>
      <?php else: ?>
      <button disabled style="width:100%;padding:10px;background:#f0f0f0;color:#999;border:1px dashed #ccc;border-radius:6px;font-size:13px;cursor:not-allowed;">
        Out of Stock
      </button>
      <?php endif; ?>

    </div>

  <?php endwhile; ?>

<?php else: ?>

  <p>No products found</p>

<?php endif; ?>

</div>

</div>
</div>

<?php include '../includes/footer.php'; ?>

<!-- AUTO FILTER -->
<script>
document.querySelectorAll("#filterForm input").forEach(el => {
  el.addEventListener("change", () => {
    document.getElementById("filterForm").submit();
  });
});
</script>