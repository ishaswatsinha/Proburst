<?php
include '../config/database.php';

/* =========================
   FETCH CATEGORIES + SUBCATEGORIES
========================= */

/* GET CATEGORIES */
$categoriesData = $conn->query("SELECT * FROM categories");

/* GET SUBCATEGORIES GROUPED */
$subcategoriesData = [];

$subQuery = $conn->query("SELECT * FROM subcategories");

while ($sub = $subQuery->fetch_assoc()) {
  $subcategoriesData[$sub['category_id']][] = $sub;
}

/* =========================
   FILTER LOGIC
========================= */

$where = [];

/* CATEGORY FILTER */
if (!empty($_GET['category'])) {
  $categories = implode(",", $_GET['category']);
  $where[] = "category_id IN ($categories)";
}

/* SUBCATEGORY FILTER */
if (!empty($_GET['subcategory'])) {
  $subcategory = (int)$_GET['subcategory'];
  $where[] = "subcategory_id = $subcategory";
}

/* PRICE FILTER */
if (!empty($_GET['price'])) {

  if ($_GET['price'] == 'low') {
    $where[] = "price < 1000";
  } elseif ($_GET['price'] == 'mid') {
    $where[] = "price BETWEEN 1000 AND 3000";
  } elseif ($_GET['price'] == 'high') {
    $where[] = "price > 3000";
  }
}

/* SORTING */
$sort = $_GET['sort'] ?? '';

/* BASE QUERY */
$sql = "SELECT * FROM products";

/* APPLY FILTER */
if (!empty($where)) {
  $sql .= " WHERE " . implode(" AND ", $where);
}

/* APPLY SORT */
if ($sort == 'low') {
  $sql .= " ORDER BY price ASC";
} elseif ($sort == 'high') {
  $sql .= " ORDER BY price DESC";
}

/* EXECUTE */
$result = $conn->query($sql);
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<div class="shop-page">

  <!-- SIDEBAR -->
  <aside class="sidebar">

  <form method="GET">

    <h3 class="sidebar-title">CATEGORIES</h3>

    <?php while($cat = $categoriesData->fetch_assoc()): ?>

      <div class="category-block">

        <!-- CATEGORY HEADER -->
        <div class="category-header" onclick="toggleCategory(this)">
          <span><?php echo $cat['name']; ?></span>
          <span class="sidearrow">›</span>
        </div>

        <!-- SUBCATEGORIES -->
        <div class="subcategory-list">

          <?php if (!empty($subcategoriesData[$cat['id']])): ?>
            <?php foreach($subcategoriesData[$cat['id']] as $sub): ?>

              <label class="subcategory-item">

                <input type="radio" name="subcategory" value="<?php echo $sub['id']; ?>"
                <?php if (isset($_GET['subcategory']) && $_GET['subcategory'] == $sub['id']) echo 'checked'; ?>>

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
      <input type="radio" name="price" value="low"
      <?php if (isset($_GET['price']) && $_GET['price']=='low') echo 'checked'; ?>>
      Below ₹1000
    </label>

    <label class="price-item">
      <input type="radio" name="price" value="mid"
      <?php if (isset($_GET['price']) && $_GET['price']=='mid') echo 'checked'; ?>>
      ₹1000 - ₹3000
    </label>

    <label class="price-item">
      <input type="radio" name="price" value="high"
      <?php if (isset($_GET['price']) && $_GET['price']=='high') echo 'checked'; ?>>
      Above ₹3000
    </label>

  </form>

</aside>


  <!-- PRODUCTS -->
  <div class="products-section">

    <!-- TOP BAR -->
    <div class="top-bar">
      <h2>Shop By Products (<?php echo $result->num_rows; ?>)</h2>

      <form method="GET">

        <!-- PRESERVE FILTERS -->
        <?php
        if (!empty($_GET['category'])) {
          foreach ($_GET['category'] as $cat) {
            echo '<input type="hidden" name="category[]" value="'.$cat.'">';
          }
        }

        if (!empty($_GET['subcategory'])) {
          echo '<input type="hidden" name="subcategory" value="'.$_GET['subcategory'].'">';
        }

        if (!empty($_GET['price'])) {
          echo '<input type="hidden" name="price" value="'.$_GET['price'].'">';
        }
        ?>

        <select name="sort" onchange="this.form.submit()">

          <option value="">Sort</option>

          <option value="low" <?php if ($sort == 'low') echo 'selected'; ?>>
            Price Low to High
          </option>

          <option value="high" <?php if ($sort == 'high') echo 'selected'; ?>>
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

            <img src="../assets/images/<?php echo $row['image']; ?>" alt="">

            <h3>
              <a href="product.php?slug=<?php echo $row['slug']; ?>">
                <?php echo $row['name']; ?>
              </a>
            </h3>

            <div class="rating">⭐⭐⭐⭐☆</div>

            <p class="price">
              ₹<?php echo number_format($row['price']); ?>
            </p>

            <small><?php echo $row['stock']; ?> in stock</small>

            <button onclick="addToCart(
              <?php echo $row['id']; ?>,
              '<?php echo $row['name']; ?>',
              <?php echo $row['price']; ?>,
              '<?php echo $row['image']; ?>',
              this
            )">
              Add to Cart
            </button>

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
document.querySelectorAll("input").forEach(el => {
  el.addEventListener("change", () => {
    el.form.submit();
  });
});
</script>