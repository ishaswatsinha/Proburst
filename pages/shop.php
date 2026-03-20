<?php
include '../config/database.php';

/* =========================
   FILTER LOGIC
========================= */

$where = [];

/* CATEGORY FILTER */
if (!empty($_GET['category'])) {
  $categories = implode(",", $_GET['category']);
  $where[] = "category_id IN ($categories)";
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

  <!-- LEFT SIDEBAR -->
  <aside class="sidebar">

    <form method="GET">

      <h3>CATEGORIES</h3>

      <label>
        <input type="checkbox" name="category[]" value="1" <?php if (isset($_GET['category']) && in_array(1, $_GET['category']))
          echo 'checked'; ?>>
        Protein
      </label>

      <label>
        <input type="checkbox" name="category[]" value="2" <?php if (isset($_GET['category']) && in_array(2, $_GET['category']))
          echo 'checked'; ?>>
        Creatine
      </label>

      <label>
        <input type="checkbox" name="category[]" value="3" <?php if (isset($_GET['category']) && in_array(3, $_GET['category']))
          echo 'checked'; ?>>
        Mass
      </label>

      <br><br>

      <h4>PRICE</h4>

      <label>
        <input type="radio" name="price" value="low" <?php if (isset($_GET['price']) && $_GET['price'] == 'low')
          echo 'checked'; ?>>
        Below ₹1000
      </label>

      <label>
        <input type="radio" name="price" value="mid" <?php if (isset($_GET['price']) && $_GET['price'] == 'mid')
          echo 'checked'; ?>>
        ₹1000 - ₹3000
      </label>

      <label>
        <input type="radio" name="price" value="high" <?php if (isset($_GET['price']) && $_GET['price'] == 'high')
          echo 'checked'; ?>>
        Above ₹3000
      </label>

    </form>

  </aside>


  <!-- RIGHT CONTENT -->
  <div class="products-section">

    <!-- TOP BAR -->
    <div class="top-bar">
      <h2>Shop By Products (<?php echo $result->num_rows; ?>)</h2>

      <form method="GET">
        <select name="sort" onchange="this.form.submit()">

          <option value="">Sort</option>

          <option value="low" <?php if ($sort == 'low')
            echo 'selected'; ?>>
            Price Low to High
          </option>

          <option value="high" <?php if ($sort == 'high')
            echo 'selected'; ?>>
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

<!-- AUTO FILTER SUBMIT -->
<script>
  document.querySelectorAll("input").forEach(el => {
    el.addEventListener("change", () => {
      el.form.submit();
    });
  });
</script>