<?php
include '../config/database.php';

$slug = $_GET['slug'] ?? '';
if (!$slug) { header('Location: shop.php'); exit; }

// Fetch product — prepared statement (secure)
$stmt = $conn->prepare("SELECT * FROM products WHERE slug = ? LIMIT 1");
$stmt->bind_param("s", $slug);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) { header('Location: shop.php'); exit; }
$product = $result->fetch_assoc();

// Fetch related products (same category, exclude current)
$related = $conn->query("
  SELECT * FROM products
  WHERE category_id = " . (int)$product['category_id'] . "
  AND id != " . (int)$product['id'] . "
  ORDER BY RAND()
  LIMIT 4
");

// Category name
$catRow = $conn->query("SELECT name FROM categories WHERE id = " . (int)$product['category_id']);
$catName = $catRow && $catRow->num_rows ? $catRow->fetch_assoc()['name'] : 'Products';

// Fake MRP for display (15-35% discount)
$discount_pct = 20;
$mrp = round($product['price'] * (100 / (100 - $discount_pct)));
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<style>
/* ==============================
   PRODUCT PAGE — REFERENCE STYLE
============================== */
.pdp-breadcrumb {
  padding: 14px 5%;
  font-size: 13px;
  color: #888;
  background: #fafafa;
  border-bottom: 1px solid #eee;
}
.pdp-breadcrumb a { color: #555; text-decoration: none; }
.pdp-breadcrumb a:hover { color: #ff6a00; }
.pdp-breadcrumb span { margin: 0 6px; }

.pdp-wrapper {
  display: flex;
  gap: 48px;
  padding: 40px 5%;
  max-width: 1280px;
  margin: 0 auto;
  background: #fff;
}

/* ---- LEFT: IMAGES ---- */
.pdp-gallery {
  flex: 0 0 480px;
  max-width: 480px;
}
.pdp-main-img {
  width: 100%;
  border: 1px solid #eee;
  border-radius: 14px;
  overflow: hidden;
  background: #f9f9f9;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  min-height: 400px;
}
.pdp-main-img img {
  max-width: 100%;
  max-height: 380px;
  object-fit: contain;
  transition: transform 0.4s ease;
}
.pdp-main-img img:hover { transform: scale(1.06); }
.pdp-thumbs {
  display: flex;
  gap: 10px;
  margin-top: 14px;
  flex-wrap: wrap;
}
.pdp-thumb {
  width: 72px;
  height: 72px;
  border: 2px solid #eee;
  border-radius: 8px;
  overflow: hidden;
  cursor: pointer;
  background: #f9f9f9;
  padding: 4px;
  transition: border-color 0.2s;
}
.pdp-thumb.active, .pdp-thumb:hover { border-color: #ff6a00; }
.pdp-thumb img { width: 100%; height: 100%; object-fit: contain; }

/* ---- RIGHT: DETAILS ---- */
.pdp-details { flex: 1; min-width: 0; }

.pdp-brand {
  font-size: 13px;
  color: #ff6a00;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
}
.pdp-title {
  font-size: 24px;
  font-weight: 700;
  color: #111;
  line-height: 1.35;
  margin-bottom: 14px;
}
.pdp-rating-row {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 18px;
  flex-wrap: wrap;
}
.pdp-stars { color: #f4a000; font-size: 17px; letter-spacing: 1px; }
.pdp-review-count { font-size: 13px; color: #555; }
.pdp-verified-badge {
  background: #e6f7ed;
  color: #1a8c45;
  font-size: 11px;
  font-weight: 600;
  padding: 3px 8px;
  border-radius: 20px;
}

/* PRICE */
.pdp-price-block { margin-bottom: 20px; }
.pdp-price-main {
  font-size: 32px;
  font-weight: 800;
  color: #111;
}
.pdp-price-mrp {
  font-size: 16px;
  color: #999;
  text-decoration: line-through;
  margin-left: 10px;
}
.pdp-price-save {
  display: inline-block;
  background: #fff0e6;
  color: #ff6a00;
  font-size: 13px;
  font-weight: 700;
  padding: 3px 10px;
  border-radius: 20px;
  margin-left: 10px;
}
.pdp-tax-note { font-size: 12px; color: #888; margin-top: 4px; }

/* STOCK */
.pdp-stock-in {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: #1a8c45;
  font-weight: 600;
  font-size: 14px;
  margin-bottom: 20px;
}
.pdp-stock-in::before {
  content: '';
  width: 8px; height: 8px;
  background: #1a8c45;
  border-radius: 50%;
  display: inline-block;
}
.pdp-stock-out { color: #cc0000; font-weight: 600; font-size: 14px; margin-bottom: 20px; }

/* QUANTITY */
.pdp-qty-row {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 24px;
}
.pdp-qty-label { font-size: 14px; font-weight: 600; color: #333; }
.pdp-qty-ctrl {
  display: flex;
  align-items: center;
  border: 1px solid #ddd;
  border-radius: 8px;
  overflow: hidden;
}
.pdp-qty-ctrl button {
  width: 38px; height: 38px;
  background: #f5f5f5;
  border: none;
  cursor: pointer;
  font-size: 18px;
  color: #333;
  transition: background 0.2s;
}
.pdp-qty-ctrl button:hover { background: #ffe0cc; }
.pdp-qty-ctrl span {
  width: 44px;
  text-align: center;
  font-weight: 600;
  font-size: 16px;
  border-left: 1px solid #ddd;
  border-right: 1px solid #ddd;
  padding: 6px 0;
}

/* BUTTONS */
.pdp-btn-row {
  display: flex;
  gap: 14px;
  margin-bottom: 28px;
  flex-wrap: wrap;
}
.pdp-btn-cart {
  flex: 1;
  min-width: 160px;
  padding: 15px 20px;
  background: #ff6a00;
  color: #fff;
  border: none;
  border-radius: 10px;
  font-size: 16px;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.2s, transform 0.2s;
  letter-spacing: 0.3px;
}
.pdp-btn-cart:hover { background: #e05500; transform: translateY(-2px); }
.pdp-btn-buy {
  flex: 1;
  min-width: 160px;
  padding: 15px 20px;
  background: #111;
  color: #fff;
  border: none;
  border-radius: 10px;
  font-size: 16px;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.2s, transform 0.2s;
}
.pdp-btn-buy:hover { background: #333; transform: translateY(-2px); }

/* TRUST BADGES */
.pdp-trust {
  display: flex;
  gap: 20px;
  padding: 16px 0;
  border-top: 1px solid #f0f0f0;
  border-bottom: 1px solid #f0f0f0;
  margin-bottom: 24px;
  flex-wrap: wrap;
}
.pdp-trust-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: #444;
  font-weight: 500;
}
.pdp-trust-item i { color: #ff6a00; font-size: 18px; }

/* DESCRIPTION TABS */
.pdp-tabs-bar {
  display: flex;
  gap: 0;
  border-bottom: 2px solid #eee;
  margin-bottom: 20px;
  margin-top: 10px;
}
.pdp-tab-btn {
  padding: 10px 22px;
  border: none;
  background: none;
  font-size: 14px;
  font-weight: 600;
  color: #888;
  cursor: pointer;
  border-bottom: 3px solid transparent;
  margin-bottom: -2px;
  transition: 0.2s;
}
.pdp-tab-btn.active { color: #ff6a00; border-bottom-color: #ff6a00; }
.pdp-tab-panel { display: none; font-size: 14px; color: #444; line-height: 1.8; }
.pdp-tab-panel.active { display: block; }
.pdp-tab-panel p { margin-bottom: 10px; }

/* ---- RELATED PRODUCTS ---- */
.pdp-related {
  padding: 50px 5%;
  background: #f8f8f8;
}
.pdp-related h2 {
  font-size: 24px;
  font-weight: 700;
  margin-bottom: 28px;
  color: #111;
}
.pdp-related-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
}
.pdp-rel-card {
  background: #fff;
  border-radius: 12px;
  padding: 16px;
  text-align: center;
  transition: box-shadow 0.2s, transform 0.2s;
  border: 1px solid #eee;
}
.pdp-rel-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,0.09); transform: translateY(-4px); }
.pdp-rel-card img { width: 100%; height: 160px; object-fit: contain; margin-bottom: 10px; }
.pdp-rel-card h4 { font-size: 13px; color: #111; margin-bottom: 6px; line-height: 1.4; }
.pdp-rel-card h4 a { text-decoration: none; color: inherit; }
.pdp-rel-card .rel-price { font-weight: 700; color: #111; font-size: 15px; margin-bottom: 10px; }
.pdp-rel-card button {
  background: #111; color: #fff; border: none;
  padding: 9px 16px; border-radius: 6px;
  font-size: 13px; cursor: pointer; transition: background 0.2s; width: 100%;
}
.pdp-rel-card button:hover { background: #ff6a00; }

/* MOBILE */
@media (max-width: 900px) {
  .pdp-wrapper { flex-direction: column; padding: 20px; gap: 28px; }
  .pdp-gallery { flex: none; max-width: 100%; }
  .pdp-related-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 500px) {
  .pdp-title { font-size: 19px; }
  .pdp-price-main { font-size: 26px; }
  .pdp-btn-row { flex-direction: column; }
  .pdp-related-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
}
</style>

<!-- BREADCRUMB -->
<div class="pdp-breadcrumb">
  <a href="/proburst/index.php">Home</a>
  <span>›</span>
  <a href="/proburst/pages/shop.php">Products</a>
  <span>›</span>
  <a href="/proburst/pages/shop.php?category[]=<?php echo $product['category_id']; ?>"><?php echo htmlspecialchars($catName); ?></a>
  <span>›</span>
  <?php echo htmlspecialchars($product['name']); ?>
</div>

<!-- MAIN PRODUCT AREA -->
<div class="pdp-wrapper">

  <!-- LEFT: GALLERY -->
  <div class="pdp-gallery">
    <div class="pdp-main-img" id="pdpMainImg">
      <img src="../assets/images/<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" id="pdpBigImg">
    </div>
    <!-- Thumbnails — all same image since DB has one; easy to extend -->
    <div class="pdp-thumbs">
      <div class="pdp-thumb active" onclick="switchImg(this, '../assets/images/<?php echo $product['image']; ?>')">
        <img src="../assets/images/<?php echo $product['image']; ?>" alt="">
      </div>
    </div>
  </div>

  <!-- RIGHT: DETAILS -->
  <div class="pdp-details">

    <div class="pdp-brand">Proburst</div>

    <h1 class="pdp-title"><?php echo htmlspecialchars($product['name']); ?></h1>

    <div class="pdp-rating-row">
      <span class="pdp-stars">★★★★☆</span>
      <span class="pdp-review-count">4.1 · <?php echo rand(25,350); ?> Reviews</span>
      <span class="pdp-verified-badge">✔ Verified Brand</span>
    </div>

    <div class="pdp-price-block">
      <span class="pdp-price-main">₹<?php echo number_format($product['price']); ?></span>
      <span class="pdp-price-mrp">₹<?php echo number_format($mrp); ?></span>
      <span class="pdp-price-save">Save <?php echo $discount_pct; ?>%</span>
      <div class="pdp-tax-note">Inclusive of all taxes. Free delivery on orders above ₹499</div>
    </div>

    <?php if($product['stock'] > 0): ?>
      <div class="pdp-stock-in">In Stock (<?php echo (int)$product['stock']; ?> units left)</div>
    <?php else: ?>
      <div class="pdp-stock-out">Out of Stock</div>
    <?php endif; ?>

    <!-- QUANTITY -->
    <div class="pdp-qty-row">
      <span class="pdp-qty-label">Quantity:</span>
      <div class="pdp-qty-ctrl">
        <button onclick="changeQty(-1)">−</button>
        <span id="pdpQty">1</span>
        <button onclick="changeQty(1)">+</button>
      </div>
    </div>

    <!-- ADD TO CART + BUY NOW -->
    <div class="pdp-btn-row">
      <button class="pdp-btn-cart" onclick="pdpAddCart()">🛒 Add to Cart</button>
      <button class="pdp-btn-buy" onclick="pdpBuyNow()">⚡ Buy Now</button>
    </div>

    <!-- TRUST BADGES -->
    <div class="pdp-trust">
      <div class="pdp-trust-item"><i class="fa-solid fa-shield-halved"></i> 100% Genuine</div>
      <div class="pdp-trust-item"><i class="fa-solid fa-truck"></i> Fast Delivery</div>
      <div class="pdp-trust-item"><i class="fa-solid fa-rotate-left"></i> Easy Returns</div>
      <div class="pdp-trust-item"><i class="fa-solid fa-headset"></i> 24/7 Support</div>
    </div>

    <!-- DESCRIPTION TABS -->
    <div class="pdp-tabs-bar">
      <button class="pdp-tab-btn active" onclick="pdpTab(this,'desc')">Description</button>
      <button class="pdp-tab-btn" onclick="pdpTab(this,'benefits')">Key Benefits</button>
      <button class="pdp-tab-btn" onclick="pdpTab(this,'howto')">How to Use</button>
    </div>

    <div class="pdp-tab-panel active" id="pdp-desc">
      <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
    </div>

    <div class="pdp-tab-panel" id="pdp-benefits">
      <p>• High quality protein to support muscle recovery and growth</p>
      <p>• Advanced formula with essential amino acids</p>
      <p>• Supports lean muscle building and strength gains</p>
      <p>• Easy to digest — no bloating or discomfort</p>
      <p>• Lab tested for purity and quality assurance</p>
    </div>

    <div class="pdp-tab-panel" id="pdp-howto">
      <p>• Mix 1 scoop (30g) with 200-250ml cold water or milk</p>
      <p>• Shake well for 20-30 seconds until fully dissolved</p>
      <p>• Consume within 30 minutes after workout for best results</p>
      <p>• Can also be taken as a meal supplement between meals</p>
      <p>• Do not exceed recommended daily serving</p>
    </div>

  </div>
</div>

<!-- RELATED PRODUCTS -->
<?php if($related && $related->num_rows > 0): ?>
<section class="pdp-related">
  <h2>You May Also Like</h2>
  <div class="pdp-related-grid">
    <?php while($rel = $related->fetch_assoc()): ?>
    <div class="pdp-rel-card">
      <img src="../assets/images/<?php echo $rel['image']; ?>" alt="<?php echo htmlspecialchars($rel['name']); ?>">
      <h4><a href="product.php?slug=<?php echo $rel['slug']; ?>"><?php echo htmlspecialchars($rel['name']); ?></a></h4>
      <div class="rel-price">₹<?php echo number_format($rel['price']); ?></div>
      <button onclick="addToCart(<?php echo $rel['id']; ?>,'<?php echo addslashes($rel['name']); ?>',<?php echo $rel['price']; ?>,'<?php echo $rel['image']; ?>',this)">Add to Cart</button>
    </div>
    <?php endwhile; ?>
  </div>
</section>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>

<script>
/* ---- QUANTITY ---- */
var pdpQty = 1;
function changeQty(n) {
  pdpQty = Math.max(1, Math.min(10, pdpQty + n));
  document.getElementById('pdpQty').innerText = pdpQty;
}

/* ---- ADD TO CART ---- */
function pdpAddCart() {
  addToCart(
    <?php echo $product['id']; ?>,
    '<?php echo addslashes($product['name']); ?>',
    <?php echo $product['price']; ?>,
    '<?php echo $product['image']; ?>',
    null,
    pdpQty
  );
}

/* ---- BUY NOW ---- */
function pdpBuyNow() {
  pdpAddCart();
  window.location.href = '/proburst/pages/cart.php';
}

/* ---- IMAGE THUMBNAILS ---- */
function switchImg(thumb, src) {
  document.getElementById('pdpBigImg').src = src;
  document.querySelectorAll('.pdp-thumb').forEach(t => t.classList.remove('active'));
  thumb.classList.add('active');
}

/* ---- TABS ---- */
function pdpTab(btn, id) {
  document.querySelectorAll('.pdp-tab-btn').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.pdp-tab-panel').forEach(p => p.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('pdp-' + id).classList.add('active');
}
</script>
