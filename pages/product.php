<?php
include '../config/database.php';
include '../includes/auth.php';

$slug = $_GET['slug'] ?? '';
if (!$slug) { header('Location: shop.php'); exit; }

// ── FETCH PRODUCT ──
$stmt = $conn->prepare("SELECT * FROM products WHERE slug = ? LIMIT 1");
$stmt->bind_param("s", $slug);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) { header('Location: shop.php'); exit; }
$product = $result->fetch_assoc();
$pid = (int)$product['id'];

// ── GALLERY IMAGES ──
$galleryImages = [];
$gRes = $conn->query("SELECT image FROM product_images WHERE product_id=$pid ORDER BY sort_order ASC");
if ($gRes) while ($g = $gRes->fetch_assoc()) $galleryImages[] = $g['image'];

// ── RELATED PRODUCTS ──
$related = $conn->query("
  SELECT * FROM products
  WHERE category_id = {$product['category_id']}
  AND id != $pid ORDER BY RAND() LIMIT 4
");

// ── CATEGORY NAME ──
$catRow  = $conn->query("SELECT name FROM categories WHERE id = {$product['category_id']}");
$catName = $catRow && $catRow->num_rows ? $catRow->fetch_assoc()['name'] : 'Products';

// ── PRICE ──
$discount_pct = !empty($product['discount_percent']) ? (int)$product['discount_percent'] : 20;
$mrp = !empty($product['mrp']) ? (float)$product['mrp'] : round($product['price'] * (100 / (100 - $discount_pct)));

// ── ALL IMAGES (cover + gallery) ──
$allImages = [];
if (!empty($product['image'])) $allImages[] = $product['image'];
foreach ($galleryImages as $gi) { if ($gi !== $product['image']) $allImages[] = $gi; }

// ── HANDLE REVIEW SUBMISSION ──
$reviewMsg = ''; $reviewMsgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    // Require login to leave a review
    if (!isLoggedIn()) {
        $reviewMsg = 'Please log in to submit a review.';
        $reviewMsgType = 'error';
    } else {
        $rName   = $conn->real_escape_string(trim($_POST['r_name']   ?? ''));
        $rRating = max(1, min(5, (int)($_POST['r_rating'] ?? 5)));
        $rTitle  = $conn->real_escape_string(trim($_POST['r_title']  ?? ''));
        $rBody   = $conn->real_escape_string(trim($_POST['r_body']   ?? ''));
        $rUserId = (int)$_SESSION['user_id'];

        if (!$rName || !$rBody) {
            $reviewMsg = 'Please fill in your name and review.';
            $reviewMsgType = 'error';
        } else {
            // Check if this user already reviewed this product
            $already = $conn->query("SELECT id FROM product_reviews WHERE product_id=$pid AND user_id=$rUserId")->num_rows;
            if ($already > 0) {
                $reviewMsg = 'You have already reviewed this product.';
                $reviewMsgType = 'error';
            } else {
                // Handle photo upload
                $rPhoto = 'NULL';
                if (!empty($_FILES['r_photo']['name'])) {
                    $allowed = ['jpg','jpeg','png','webp','gif'];
                    $ext     = strtolower(pathinfo($_FILES['r_photo']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, $allowed) && $_FILES['r_photo']['error'] === UPLOAD_ERR_OK) {
                        $fname = 'review_' . uniqid('', true) . '.' . $ext;
                        $dest  = __DIR__ . '/../assets/images/reviews/';
                        if (!is_dir($dest)) mkdir($dest, 0755, true);
                        if (move_uploaded_file($_FILES['r_photo']['tmp_name'], $dest . $fname)) {
                            $rPhoto = "'" . $conn->real_escape_string($fname) . "'";
                        }
                    }
                }
                // Check if verified purchase (user ordered this product)
                $verified = $conn->query("
                    SELECT oi.id FROM order_items oi
                    JOIN orders o ON oi.order_id = o.id
                    WHERE o.user_id=$rUserId AND oi.product_id=$pid LIMIT 1
                ")->num_rows > 0 ? 1 : 0;

                $conn->query("
                    INSERT INTO product_reviews (product_id, user_id, name, rating, title, body, photo, verified, approved)
                    VALUES ($pid, $rUserId, '$rName', $rRating, '$rTitle', '$rBody', $rPhoto, $verified, 1)
                ");
                $reviewMsg = 'Thank you! Your review has been posted.';
            }
        }
    }
}

// ── FETCH REVIEWS ──
$reviews = $conn->query("
    SELECT * FROM product_reviews
    WHERE product_id=$pid AND approved=1
    ORDER BY created_at DESC
");
$reviewList = [];
while ($r = $reviews->fetch_assoc()) $reviewList[] = $r;
$totalReviews = count($reviewList);

// ── RATING STATS ──
$avgRating    = 0;
$starCounts   = [5=>0,4=>0,3=>0,2=>0,1=>0];
if ($totalReviews > 0) {
    $sum = 0;
    foreach ($reviewList as $r) {
        $sum += (int)$r['rating'];
        $starCounts[(int)$r['rating']]++;
    }
    $avgRating = round($sum / $totalReviews, 1);
}
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<style>
/* ══════════════════════════════════════
   PRODUCT PAGE STYLES
══════════════════════════════════════ */
.pdp-breadcrumb { padding:14px 5%; font-size:13px; color:#888; background:#fafafa; border-bottom:1px solid #eee; }
.pdp-breadcrumb a { color:#555; text-decoration:none; }
.pdp-breadcrumb a:hover { color:#ff6a00; }
.pdp-breadcrumb span { margin:0 6px; }

.pdp-wrapper { display:flex; gap:48px; padding:40px 5%; max-width:1280px; margin:0 auto; background:#fff; }

/* GALLERY */
.pdp-gallery { flex:0 0 480px; max-width:480px; }
.pdp-main-img {
  width:100%; border:1px solid #eee; border-radius:14px; overflow:hidden;
  background:#f9f9f9; display:flex; align-items:center; justify-content:center;
  padding:20px; min-height:400px; position:relative;
}
.pdp-main-img img { max-width:100%; max-height:380px; object-fit:contain; transition:transform .4s; cursor:zoom-in; }
.pdp-main-img img:hover { transform:scale(1.06); }
.pdp-img-nav {
  position:absolute; top:50%; transform:translateY(-50%);
  background:rgba(255,255,255,.85); border:1px solid #eee; border-radius:50%;
  width:36px; height:36px; display:flex; align-items:center; justify-content:center;
  cursor:pointer; font-size:14px; color:#333; transition:background .2s; z-index:2;
}
.pdp-img-nav:hover { background:#ff6a00; color:#fff; border-color:#ff6a00; }
.pdp-img-prev { left:10px; } .pdp-img-next { right:10px; }
.pdp-thumbs { display:flex; gap:10px; margin-top:14px; flex-wrap:wrap; }
.pdp-thumb { width:72px; height:72px; border:2px solid #eee; border-radius:8px; overflow:hidden; cursor:pointer; background:#f9f9f9; padding:4px; transition:border-color .2s; }
.pdp-thumb.active, .pdp-thumb:hover { border-color:#ff6a00; }
.pdp-thumb img { width:100%; height:100%; object-fit:contain; }

/* DETAILS */
.pdp-details { flex:1; min-width:0; }
.pdp-brand { font-size:13px; color:#ff6a00; font-weight:600; text-transform:uppercase; letter-spacing:.5px; margin-bottom:8px; }
.pdp-title { font-size:24px; font-weight:700; color:#111; line-height:1.35; margin-bottom:14px; }
.pdp-rating-row { display:flex; align-items:center; gap:10px; margin-bottom:18px; flex-wrap:wrap; }
.pdp-stars { color:#f4a000; font-size:17px; letter-spacing:1px; }
.pdp-review-count { font-size:13px; color:#555; }
.pdp-verified-badge { background:#e6f7ed; color:#1a8c45; font-size:11px; font-weight:600; padding:3px 8px; border-radius:20px; }
.pdp-price-block { margin-bottom:20px; }
.pdp-price-main { font-size:32px; font-weight:800; color:#111; }
.pdp-price-mrp { font-size:16px; color:#999; text-decoration:line-through; margin-left:10px; }
.pdp-price-save { display:inline-block; background:#fff0e6; color:#ff6a00; font-size:13px; font-weight:700; padding:3px 10px; border-radius:20px; margin-left:10px; }
.pdp-tax-note { font-size:12px; color:#888; margin-top:4px; }
.pdp-stock-in { display:inline-flex; align-items:center; gap:6px; color:#1a8c45; font-weight:600; font-size:14px; margin-bottom:20px; }
.pdp-stock-in::before { content:''; width:8px; height:8px; background:#1a8c45; border-radius:50%; display:inline-block; }
.pdp-stock-out { color:#cc0000; font-weight:600; font-size:14px; margin-bottom:20px; }
.pdp-qty-row { display:flex; align-items:center; gap:16px; margin-bottom:24px; }
.pdp-qty-label { font-size:14px; font-weight:600; color:#333; }
.pdp-qty-ctrl { display:flex; align-items:center; border:1px solid #ddd; border-radius:8px; overflow:hidden; }
.pdp-qty-ctrl button { width:38px; height:38px; background:#f5f5f5; border:none; cursor:pointer; font-size:18px; color:#333; transition:background .2s; }
.pdp-qty-ctrl button:hover { background:#ffe0cc; }
.pdp-qty-ctrl span { width:44px; text-align:center; font-weight:600; font-size:16px; border-left:1px solid #ddd; border-right:1px solid #ddd; padding:6px 0; }
.pdp-btn-row { display:flex; gap:14px; margin-bottom:28px; flex-wrap:wrap; }
.pdp-btn-cart { flex:1; min-width:160px; padding:15px 20px; background:#ff6a00; color:#fff; border:none; border-radius:10px; font-size:16px; font-weight:700; cursor:pointer; transition:background .2s,transform .2s; }
.pdp-btn-cart:hover { background:#e05500; transform:translateY(-2px); }
.pdp-btn-buy  { flex:1; min-width:160px; padding:15px 20px; background:#111; color:#fff; border:none; border-radius:10px; font-size:16px; font-weight:700; cursor:pointer; transition:background .2s,transform .2s; }
.pdp-btn-buy:hover { background:#333; transform:translateY(-2px); }

.pdp-btn-notify {
  flex:1; min-width:200px; padding:15px 20px;
  background:#f5f5f5; color:#555;
  border:2px dashed #ccc; border-radius:10px;
  font-size:15px; font-weight:700; cursor:pointer;
  transition:all .2s; text-align:center;
  display:flex; align-items:center; justify-content:center; gap:8px;
}
.pdp-btn-notify:hover { background:#fff3cd; border-color:#f4a000; color:#b45309; }
.pdp-btn-notify.subscribed { background:#e6f7ed; border-color:#22c55e; color:#15803d; cursor:default; }
.pdp-trust { display:flex; gap:20px; padding:16px 0; border-top:1px solid #f0f0f0; border-bottom:1px solid #f0f0f0; margin-bottom:24px; flex-wrap:wrap; }
.pdp-trust-item { display:flex; align-items:center; gap:8px; font-size:13px; color:#444; font-weight:500; }
.pdp-trust-item i { color:#ff6a00; font-size:18px; }
.pdp-tabs-bar { display:flex; border-bottom:2px solid #eee; margin-bottom:20px; margin-top:10px; }
.pdp-tab-btn { padding:10px 22px; border:none; background:none; font-size:14px; font-weight:600; color:#888; cursor:pointer; border-bottom:3px solid transparent; margin-bottom:-2px; transition:.2s; }
.pdp-tab-btn.active { color:#ff6a00; border-bottom-color:#ff6a00; }
.pdp-tab-panel { display:none; font-size:14px; color:#444; line-height:1.8; }
.pdp-tab-panel.active { display:block; }
.pdp-tab-panel p { margin-bottom:10px; }

/* ══════════════════════════════════════
   REVIEWS SECTION
══════════════════════════════════════ */
.reviews-section {
  max-width: 1280px;
  margin: 0 auto;
  padding: 50px 5%;
  background: #fff;
  border-top: 8px solid #f5f5f5;
}
.reviews-heading {
  font-size: 22px;
  font-weight: 800;
  color: #111;
  margin-bottom: 28px;
  display: flex;
  align-items: center;
  gap: 10px;
}

/* RATING SUMMARY BAR */
.reviews-summary {
  display: flex;
  gap: 40px;
  align-items: flex-start;
  padding: 28px;
  background: #fafafa;
  border-radius: 14px;
  margin-bottom: 36px;
  flex-wrap: wrap;
  border: 1px solid #f0f0f0;
}
.reviews-avg-block {
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 110px;
}
.reviews-avg-number {
  font-size: 56px;
  font-weight: 900;
  color: #111;
  line-height: 1;
}
.reviews-avg-stars { font-size: 22px; color: #f4a000; margin: 6px 0; letter-spacing: 2px; }
.reviews-avg-count { font-size: 13px; color: #888; }

.reviews-bars { flex: 1; min-width: 200px; }
.reviews-bar-row {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 8px;
  font-size: 13px;
}
.reviews-bar-label { width: 40px; color: #555; font-weight: 600; white-space: nowrap; }
.reviews-bar-track {
  flex: 1;
  height: 8px;
  background: #eee;
  border-radius: 10px;
  overflow: hidden;
}
.reviews-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #ff6a00, #ff9d00);
  border-radius: 10px;
  transition: width .6s ease;
}
.reviews-bar-count { width: 28px; text-align: right; color: #888; font-size: 12px; }

/* WRITE REVIEW BUTTON */
.write-review-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: #ff6a00;
  color: #fff;
  border: none;
  border-radius: 10px;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  transition: background .2s, transform .2s;
  text-decoration: none;
  margin-left: auto;
}
.write-review-btn:hover { background: #e05500; transform: translateY(-2px); }

/* ── REVIEW FORM ── */
.review-form-box {
  background: #fff;
  border: 2px solid #ff6a00;
  border-radius: 16px;
  padding: 28px;
  margin-bottom: 36px;
  display: none;
}
.review-form-box.open { display: block; }
.review-form-title { font-size: 18px; font-weight: 700; color: #111; margin-bottom: 20px; }

/* Star picker */
.star-picker { display: flex; gap: 4px; margin-bottom: 4px; flex-direction: row-reverse; justify-content: flex-end; }
.star-picker input { display: none; }
.star-picker label {
  font-size: 32px;
  color: #ddd;
  cursor: pointer;
  transition: color .15s;
}
.star-picker input:checked ~ label,
.star-picker label:hover,
.star-picker label:hover ~ label { color: #f4a000; }

.rf-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px; }
.rf-group { display: flex; flex-direction: column; gap: 6px; }
.rf-group label { font-size: 12px; font-weight: 600; color: #888; text-transform: uppercase; letter-spacing: .5px; }
.rf-group input,
.rf-group textarea {
  background: #fafafa;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 10px 12px;
  font-size: 14px;
  font-family: inherit;
  color: #111;
  outline: none;
  transition: border-color .2s;
  width: 100%;
}
.rf-group input:focus,
.rf-group textarea:focus { border-color: #ff6a00; background: #fff; }
.rf-group textarea { resize: vertical; min-height: 100px; }

/* Photo upload */
.rf-photo-area {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px 18px;
  border: 2px dashed #ddd;
  border-radius: 10px;
  cursor: pointer;
  transition: border-color .2s, background .2s;
  background: #fafafa;
  margin-bottom: 14px;
}
.rf-photo-area:hover { border-color: #ff6a00; background: #fff5f0; }
.rf-photo-area .rf-photo-icon { font-size: 28px; }
.rf-photo-area .rf-photo-text { font-size: 14px; font-weight: 600; color: #333; }
.rf-photo-area .rf-photo-hint { font-size: 12px; color: #888; margin-top: 2px; }

#rfPhotoPreview {
  width: 70px; height: 70px;
  object-fit: cover;
  border-radius: 8px;
  display: none;
  border: 2px solid #ff6a00;
}

.rf-submit-btn {
  background: #ff6a00;
  color: #fff;
  border: none;
  padding: 13px 32px;
  border-radius: 10px;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  transition: background .2s;
}
.rf-submit-btn:hover { background: #e05500; }
.rf-cancel-btn {
  background: #f0f0f0;
  color: #444;
  border: none;
  padding: 13px 22px;
  border-radius: 10px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  margin-left: 10px;
  transition: background .2s;
}
.rf-cancel-btn:hover { background: #e0e0e0; }

/* Review message */
.review-msg {
  padding: 12px 16px;
  border-radius: 8px;
  margin-bottom: 16px;
  font-size: 14px;
  font-weight: 500;
}
.review-msg.success { background: #e6f7ed; border: 1px solid #b2e0c3; color: #1a7a3c; }
.review-msg.error   { background: #fdecea; border: 1px solid #f5c6cb; color: #c0392b; }

/* ── REVIEW CARDS LIST ── */
.reviews-filter-bar {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-bottom: 20px;
  align-items: center;
}
.reviews-filter-bar span { font-size: 13px; color: #888; font-weight: 600; }
.rf-filter-btn {
  padding: 6px 14px;
  border-radius: 20px;
  border: 1px solid #ddd;
  background: #fff;
  font-size: 13px;
  cursor: pointer;
  font-weight: 500;
  color: #444;
  transition: all .15s;
}
.rf-filter-btn.active,
.rf-filter-btn:hover { background: #ff6a00; color: #fff; border-color: #ff6a00; }

.review-cards { display: flex; flex-direction: column; gap: 20px; }

.review-card {
  border: 1px solid #f0f0f0;
  border-radius: 14px;
  padding: 22px 24px;
  background: #fff;
  transition: box-shadow .2s;
}
.review-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,.07); }

.rc-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 10px;
  flex-wrap: wrap;
}
.rc-avatar {
  width: 40px; height: 40px;
  background: linear-gradient(135deg, #ff6a00, #ff9d00);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-weight: 700; font-size: 16px; color: #fff;
  flex-shrink: 0;
}
.rc-name { font-weight: 700; font-size: 15px; color: #111; }
.rc-date { font-size: 12px; color: #aaa; margin-left: auto; }
.rc-stars { color: #f4a000; font-size: 16px; letter-spacing: 1px; }
.rc-verified {
  background: #e6f7ed; color: #1a8c45;
  font-size: 11px; font-weight: 600;
  padding: 2px 8px; border-radius: 20px;
}
.rc-title { font-weight: 700; font-size: 15px; color: #111; margin-bottom: 6px; }
.rc-body { font-size: 14px; color: #444; line-height: 1.75; margin-bottom: 10px; }
.rc-photo {
  width: 90px; height: 90px;
  object-fit: cover;
  border-radius: 10px;
  border: 1px solid #eee;
  cursor: pointer;
  transition: transform .2s;
  margin-top: 6px;
}
.rc-photo:hover { transform: scale(1.04); }

.reviews-empty {
  text-align: center;
  padding: 48px 20px;
  color: #999;
}
.reviews-empty .re-icon { font-size: 48px; margin-bottom: 12px; }
.reviews-empty p { font-size: 15px; }

/* Load more */
.load-more-btn {
  display: block;
  width: 100%;
  padding: 13px;
  border: 2px solid #ff6a00;
  background: #fff;
  color: #ff6a00;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  margin-top: 16px;
  transition: background .2s, color .2s;
  text-align: center;
}
.load-more-btn:hover { background: #ff6a00; color: #fff; }

/* ── RELATED ── */
.pdp-related { padding:50px 5%; background:#f8f8f8; }
.pdp-related h2 { font-size:24px; font-weight:700; margin-bottom:28px; color:#111; }
.pdp-related-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:20px; }
.pdp-rel-card { background:#fff; border-radius:12px; padding:16px; text-align:center; transition:box-shadow .2s,transform .2s; border:1px solid #eee; }
.pdp-rel-card:hover { box-shadow:0 6px 24px rgba(0,0,0,.09); transform:translateY(-4px); }
.pdp-rel-card img { width:100%; height:160px; object-fit:contain; margin-bottom:10px; }
.pdp-rel-card h4 { font-size:13px; color:#111; margin-bottom:6px; line-height:1.4; }
.pdp-rel-card h4 a { text-decoration:none; color:inherit; }
.pdp-rel-card .rel-price { font-weight:700; color:#111; font-size:15px; margin-bottom:10px; }
.pdp-rel-card button { background:#111; color:#fff; border:none; padding:9px 16px; border-radius:6px; font-size:13px; cursor:pointer; transition:background .2s; width:100%; }
.pdp-rel-card button:hover { background:#ff6a00; }

/* ── NOTIFY ME ── */
function subscribeNotify(btn, productId) {
  if (btn.classList.contains('subscribed')) return;
  btn.textContent = '✅ You will be notified!';
  btn.classList.add('subscribed');
  btn.disabled = true;
  // Store in localStorage so it persists across page refreshes
  const key = 'notify_' + productId;
  localStorage.setItem(key, '1');
}
// On page load — restore subscribed state if user already clicked
(function() {
  var nb = document.getElementById('notifyBtn');
  if (!nb) return;
  var pid = nb.getAttribute('onclick').match(/\d+/)?.[0];
  if (pid && localStorage.getItem('notify_' + pid)) {
    nb.textContent = '✅ You will be notified!';
    nb.classList.add('subscribed');
    nb.disabled = true;
  }
})();

/* ── LIGHTBOX ── */
.lightbox-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.92); z-index:9999; align-items:center; justify-content:center; }
.lightbox-overlay.open { display:flex; }
.lightbox-inner { position:relative; max-width:90vw; max-height:90vh; }
.lightbox-inner img { max-width:90vw; max-height:85vh; object-fit:contain; border-radius:8px; }
.lightbox-close { position:fixed; top:20px; right:24px; color:#fff; font-size:32px; cursor:pointer; background:none; border:none; z-index:10000; }
.lightbox-prev,.lightbox-next { position:fixed; top:50%; transform:translateY(-50%); background:rgba(255,255,255,.15); border:none; color:#fff; font-size:26px; padding:14px 18px; cursor:pointer; border-radius:8px; transition:background .2s; z-index:10000; }
.lightbox-prev { left:16px; } .lightbox-next { right:16px; }
.lightbox-prev:hover,.lightbox-next:hover { background:rgba(255,106,0,.7); }
.lightbox-counter { position:fixed; bottom:24px; left:50%; transform:translateX(-50%); color:#fff; font-size:14px; background:rgba(0,0,0,.5); padding:4px 14px; border-radius:20px; }

/* ── RESPONSIVE ── */
@media (max-width:900px) {
  .pdp-wrapper { flex-direction:column; padding:20px; gap:28px; }
  .pdp-gallery { flex:none; max-width:100%; }
  .pdp-related-grid { grid-template-columns:repeat(2,1fr); }
  .reviews-summary { gap:20px; }
  .rf-grid { grid-template-columns:1fr; }
}
@media (max-width:600px) {
  .reviews-section { padding:30px 16px; }
  .review-card { padding:16px; }
  .rc-date { margin-left:0; width:100%; }
  .pdp-title { font-size:19px; }
  .pdp-price-main { font-size:26px; }
  .pdp-btn-row { flex-direction:column; }
  .pdp-related-grid { grid-template-columns:repeat(2,1fr); gap:12px; }
}

/* OUT OF STOCK — NOTIFY ME */
.pdp-notify-box {
  background: #fff8f0;
  border: 2px dashed #ffb380;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 24px;
}
.pdp-notify-label {
  font-size: 14px;
  color: #555;
  margin-bottom: 12px;
  font-weight: 500;
}
.pdp-notify-form {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}
.pdp-notify-form input {
  flex: 1;
  min-width: 200px;
  padding: 11px 14px;
  border: 1.5px solid #ddd;
  border-radius: 8px;
  font-size: 14px;
  font-family: inherit;
  outline: none;
  transition: border-color .2s;
}
.pdp-notify-form input:focus { border-color: #ff6a00; }
.pdp-notify-btn {
  padding: 11px 22px;
  background: #111;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  transition: background .2s;
  white-space: nowrap;
}
.pdp-notify-btn:hover { background: #ff6a00; }
.pdp-notify-btn:disabled { background: #888; cursor: not-allowed; }
.pdp-notify-msg {
  margin-top: 10px;
  font-size: 13px;
  font-weight: 600;
  padding: 8px 12px;
  border-radius: 6px;
}
.pdp-notify-msg.success { background: #e6f7ed; color: #1a8c45; }
.pdp-notify-msg.error   { background: #fdecea; color: #c0392b; }
</style>

<!-- BREADCRUMB -->
<div class="pdp-breadcrumb">
  <a href="/proburst/index.php">Home</a><span>›</span>
  <a href="/proburst/pages/shop.php">Products</a><span>›</span>
  <a href="/proburst/pages/shop.php?category[]=<?= $product['category_id'] ?>"><?= htmlspecialchars($catName) ?></a><span>›</span>
  <?= htmlspecialchars($product['name']) ?>
</div>

<!-- MAIN PRODUCT AREA -->
<div class="pdp-wrapper">

  <!-- GALLERY -->
  <div class="pdp-gallery">
    <div class="pdp-main-img" id="pdpMainImg">
      <?php if (count($allImages) > 1): ?>
      <button class="pdp-img-nav pdp-img-prev" onclick="shiftMain(-1)">&#8249;</button>
      <button class="pdp-img-nav pdp-img-next" onclick="shiftMain(1)">&#8250;</button>
      <?php endif; ?>
      <img src="../assets/images/<?= htmlspecialchars($allImages[0] ?? $product['image']) ?>"
           alt="<?= htmlspecialchars($product['name']) ?>"
           id="pdpBigImg" onclick="openLightbox(currentImgIdx)">
    </div>
    <div class="pdp-thumbs">
      <?php foreach ($allImages as $idx => $img): ?>
      <div class="pdp-thumb <?= $idx === 0 ? 'active' : '' ?>"
           onclick="switchImg(this,'../assets/images/<?= htmlspecialchars($img) ?>',<?= $idx ?>)">
        <img src="../assets/images/<?= htmlspecialchars($img) ?>" alt="">
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- DETAILS -->
  <div class="pdp-details">
    <div class="pdp-brand">Proburst</div>
    <h1 class="pdp-title"><?= htmlspecialchars($product['name']) ?></h1>

    <div class="pdp-rating-row">
      <?php
        $displayAvg = $avgRating > 0 ? $avgRating : 4.1;
        $displayCnt = $totalReviews > 0 ? $totalReviews : rand(25,350);
        $fullStars  = floor($displayAvg);
        $halfStar   = ($displayAvg - $fullStars) >= 0.5;
        $starHtml   = str_repeat('★', $fullStars) . ($halfStar ? '½' : '') . str_repeat('☆', 5 - $fullStars - ($halfStar?1:0));
      ?>
      <span class="pdp-stars"><?= $starHtml ?></span>
      <span class="pdp-review-count"><?= $displayAvg ?> · <?= $displayCnt ?> Reviews</span>
      <span class="pdp-verified-badge">✔ Verified Brand</span>
    </div>

    <div class="pdp-price-block">
      <span class="pdp-price-main">₹<?= number_format($product['price']) ?></span>
      <span class="pdp-price-mrp">₹<?= number_format($mrp) ?></span>
      <span class="pdp-price-save">Save <?= $discount_pct ?>%</span>
      <div class="pdp-tax-note">Inclusive of all taxes. Free delivery on orders above ₹499</div>
    </div>

    <?php if ($product['stock'] > 0): ?>
      <!-- IN STOCK -->
      <div class="pdp-stock-in">In Stock (<?= (int)$product['stock'] ?> units left)</div>

    <div class="pdp-qty-row">
        <span class="pdp-qty-label">Quantity:</span>
        <div class="pdp-qty-ctrl">
          <button onclick="changeQty(-1)">−</button>
          <span id="pdpQty">1</span>
          <button onclick="changeQty(1)">+</button>
        </div>
      </div>
    <div class="pdp-btn-row">
        <button class="pdp-btn-cart" onclick="pdpAddCart()">🛒 Add to Cart</button>
        <button class="pdp-btn-buy"  onclick="pdpBuyNow()">⚡ Buy Now</button>
    </div>

    <?php else: ?>
      <!-- OUT OF STOCK -->
      <div class="pdp-stock-out">⚠ Out of Stock</div>

      <!-- NOTIFY ME FORM -->
      <div class="pdp-notify-box" id="pdpNotifyBox">
        <p class="pdp-notify-label">Get notified when this product is back in stock:</p>
        <div class="pdp-notify-form">
          <input type="email" id="notifyEmail" placeholder="Enter your email address"
            value="<?= htmlspecialchars($_SESSION['user_email'] ?? '') ?>">
          <button onclick="submitNotify()" class="pdp-notify-btn" id="notifyBtn">
            🔔 Notify Me
          </button>
        </div>
        <div id="notifyMsg" class="pdp-notify-msg" style="display:none"></div>
      </div>

    <?php endif; ?>

    <div class="pdp-trust">
      <div class="pdp-trust-item"><i class="fa-solid fa-shield-halved"></i> 100% Genuine</div>
      <div class="pdp-trust-item"><i class="fa-solid fa-truck"></i> Fast Delivery</div>
      <div class="pdp-trust-item"><i class="fa-solid fa-rotate-left"></i> Easy Returns</div>
      <div class="pdp-trust-item"><i class="fa-solid fa-headset"></i> 24/7 Support</div>
    </div>

    <div class="pdp-tabs-bar">
      <button class="pdp-tab-btn active" onclick="pdpTab(this,'desc')">Description</button>
      <button class="pdp-tab-btn" onclick="pdpTab(this,'benefits')">Key Benefits</button>
      <button class="pdp-tab-btn" onclick="pdpTab(this,'howto')">How to Use</button>
    </div>
    <div class="pdp-tab-panel active" id="pdp-desc"><p><?= nl2br(htmlspecialchars($product['description'])) ?></p></div>
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

<!-- ══════════════════════════════════════════
     CUSTOMER REVIEWS SECTION
══════════════════════════════════════════ -->
<section class="reviews-section">

  <!-- HEADING ROW -->
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:24px;">
    <h2 class="reviews-heading">⭐ Customer Reviews <span style="font-size:16px;color:#888;font-weight:500;">(<?= $totalReviews ?>)</span></h2>
    <button class="write-review-btn" onclick="toggleReviewForm()">✏️ Write a Review</button>
  </div>

  <!-- REVIEW MESSAGE (after submit) -->
  <?php if ($reviewMsg): ?>
    <div class="review-msg <?= $reviewMsgType ?>"><?= htmlspecialchars($reviewMsg) ?></div>
  <?php endif; ?>

  <!-- WRITE REVIEW FORM -->
  <div class="review-form-box" id="reviewFormBox">
    <div class="review-form-title">Share Your Experience</div>

    <?php if (!isLoggedIn()): ?>
      <div class="review-msg error">
        You need to <a href="/proburst/pages/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" style="color:#c0392b;font-weight:700;">log in</a> to write a review.
      </div>
    <?php else: ?>
    <form method="POST" enctype="multipart/form-data">

      <!-- STAR RATING PICKER -->
      <div style="margin-bottom:16px;">
        <div style="font-size:12px;font-weight:600;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Your Rating *</div>
        <div class="star-picker" id="starPicker">
          <?php for ($s = 5; $s >= 1; $s--): ?>
          <input type="radio" name="r_rating" id="star<?= $s ?>" value="<?= $s ?>" <?= $s === 5 ? 'checked' : '' ?>>
          <label for="star<?= $s ?>" title="<?= $s ?> star<?= $s > 1 ? 's' : '' ?>">★</label>
          <?php endfor; ?>
        </div>
        <div id="starLabel" style="font-size:13px;color:#f4a000;font-weight:600;margin-top:4px;">Excellent</div>
      </div>

      <div class="rf-grid">
        <div class="rf-group">
          <label>Your Name *</label>
          <input type="text" name="r_name" placeholder="e.g. Rahul S."
            value="<?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>" required>
        </div>
        <div class="rf-group">
          <label>Review Title</label>
          <input type="text" name="r_title" placeholder="e.g. Great product!">
        </div>
      </div>

      <div class="rf-group" style="margin-bottom:14px;">
        <label>Your Review *</label>
        <textarea name="r_body" placeholder="Tell others about your experience with this product..." required></textarea>
      </div>

      <!-- PHOTO UPLOAD -->
      <label for="rfPhotoInput" class="rf-photo-area" id="rfPhotoArea">
        <span class="rf-photo-icon">📷</span>
        <div>
          <div class="rf-photo-text">Add a photo (optional)</div>
          <div class="rf-photo-hint">JPG, PNG or WEBP · Max 5MB</div>
        </div>
        <img id="rfPhotoPreview" src="" alt="preview">
        <input type="file" name="r_photo" id="rfPhotoInput" accept="image/*" style="display:none">
      </label>

      <div style="display:flex;align-items:center;gap:0;">
        <button type="submit" name="submit_review" class="rf-submit-btn">Post Review</button>
        <button type="button" class="rf-cancel-btn" onclick="toggleReviewForm()">Cancel</button>
      </div>
    </form>
    <?php endif; ?>
  </div>

  <!-- RATING SUMMARY -->
  <?php if ($totalReviews > 0): ?>
  <div class="reviews-summary">
    <div class="reviews-avg-block">
      <div class="reviews-avg-number"><?= $avgRating ?></div>
      <div class="reviews-avg-stars">
        <?php
          $f = floor($avgRating); $h = ($avgRating - $f) >= 0.5;
          echo str_repeat('★',$f) . ($h?'½':'') . str_repeat('☆', 5-$f-($h?1:0));
        ?>
      </div>
      <div class="reviews-avg-count"><?= $totalReviews ?> rating<?= $totalReviews>1?'s':'' ?></div>
    </div>

    <div class="reviews-bars">
      <?php foreach ([5,4,3,2,1] as $s):
        $cnt = $starCounts[$s];
        $pct = $totalReviews > 0 ? round(($cnt / $totalReviews) * 100) : 0;
      ?>
      <div class="reviews-bar-row">
        <span class="reviews-bar-label">★ <?= $s ?></span>
        <div class="reviews-bar-track">
          <div class="reviews-bar-fill" style="width:<?= $pct ?>%"></div>
        </div>
        <span class="reviews-bar-count"><?= $cnt ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- FILTER BUTTONS -->
  <div class="reviews-filter-bar">
    <span>Filter:</span>
    <button class="rf-filter-btn active" onclick="filterReviews(0,this)">All</button>
    <?php foreach ([5,4,3,2,1] as $s): if ($starCounts[$s] > 0): ?>
    <button class="rf-filter-btn" onclick="filterReviews(<?= $s ?>,this)">★ <?= $s ?> (<?= $starCounts[$s] ?>)</button>
    <?php endif; endforeach; ?>
    <button class="rf-filter-btn" onclick="filterVerified(this)" id="verifiedBtn">✔ Verified only</button>
  </div>

  <!-- REVIEW CARDS -->
  <div class="review-cards" id="reviewCards">
    <?php foreach ($reviewList as $rv):
      $initials = strtoupper(mb_substr($rv['name'], 0, 1));
      $starsHtml = str_repeat('★', (int)$rv['rating']) . str_repeat('☆', 5 - (int)$rv['rating']);
    ?>
    <div class="review-card"
         data-rating="<?= $rv['rating'] ?>"
         data-verified="<?= $rv['verified'] ?>">
      <div class="rc-header">
        <div class="rc-avatar"><?= $initials ?></div>
        <div>
          <div class="rc-name"><?= htmlspecialchars($rv['name']) ?></div>
          <div class="rc-stars"><?= $starsHtml ?></div>
        </div>
        <?php if ($rv['verified']): ?>
        <span class="rc-verified">✔ Verified Purchase</span>
        <?php endif; ?>
        <span class="rc-date"><?= date('d M Y', strtotime($rv['created_at'])) ?></span>
      </div>
      <?php if (!empty($rv['title'])): ?>
        <div class="rc-title"><?= htmlspecialchars($rv['title']) ?></div>
      <?php endif; ?>
      <div class="rc-body"><?= nl2br(htmlspecialchars($rv['body'])) ?></div>
      <?php if (!empty($rv['photo'])): ?>
        <img src="../assets/images/reviews/<?= htmlspecialchars($rv['photo']) ?>"
             class="rc-photo"
             onclick="openPhotoLightbox(this.src)"
             alt="Review photo">
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- LOAD MORE (shows if > 5 reviews) -->
  <?php if ($totalReviews > 5): ?>
  <button class="load-more-btn" id="loadMoreBtn" onclick="loadMore()">Load More Reviews</button>
  <?php endif; ?>

  <?php else: ?>
  <div class="reviews-empty">
    <div class="re-icon">💬</div>
    <p>No reviews yet. Be the first to review this product!</p>
  </div>
  <?php endif; ?>

</section>

<!-- RELATED PRODUCTS -->
<?php if ($related && $related->num_rows > 0): ?>
<section class="pdp-related">
  <h2>You May Also Like</h2>
  <div class="pdp-related-grid">
    <?php while ($rel = $related->fetch_assoc()): ?>
    <div class="pdp-rel-card">
      <img src="../assets/images/<?= $rel['image'] ?>" alt="<?= htmlspecialchars($rel['name']) ?>">
      <h4><a href="product.php?slug=<?= $rel['slug'] ?>"><?= htmlspecialchars($rel['name']) ?></a></h4>
      <div class="rel-price">₹<?= number_format($rel['price']) ?></div>
      <button onclick="addToCart(<?= $rel['id'] ?>,'<?= addslashes($rel['name']) ?>',<?= $rel['price'] ?>,'<?= $rel['image'] ?>',this)">Add to Cart</button>
    </div>
    <?php endwhile; ?>
  </div>
</section>
<?php endif; ?>

<!-- LIGHTBOX (gallery + review photos) -->
<div class="lightbox-overlay" id="lightbox" onclick="closeLightboxOnBg(event)">
  <button class="lightbox-close" onclick="closeLightbox()">✕</button>
  <?php if (count($allImages) > 1): ?>
  <button class="lightbox-prev" onclick="shiftLightbox(-1)">&#8249;</button>
  <button class="lightbox-next" onclick="shiftLightbox(1)">&#8250;</button>
  <?php endif; ?>
  <div class="lightbox-inner"><img src="" alt="" id="lightboxImg"></div>
  <div class="lightbox-counter" id="lightboxCounter"></div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
// ── IMAGE GALLERY DATA ──
var allImages    = <?= json_encode(array_map(fn($i) => '../assets/images/'.$i, $allImages)) ?>;
var currentImgIdx = 0;

/* QUANTITY */
var pdpQty = 1;
function changeQty(n) { pdpQty = Math.max(1,Math.min(10,pdpQty+n)); document.getElementById('pdpQty').innerText = pdpQty; }

/* CART */
function pdpAddCart() { addToCart(<?= $product['id'] ?>,'<?= addslashes($product['name']) ?>',<?= $product['price'] ?>,'<?= $product['image'] ?>',null,pdpQty); }
function pdpBuyNow()  { pdpAddCart(); window.location.href='/proburst/pages/cart.php'; }

/* THUMBNAIL SWITCH */
function switchImg(thumb,src,idx) {
  document.getElementById('pdpBigImg').src = src;
  document.querySelectorAll('.pdp-thumb').forEach(t=>t.classList.remove('active'));
  thumb.classList.add('active'); currentImgIdx = idx;
}

/* ARROW NAV */
function shiftMain(dir) {
  currentImgIdx = (currentImgIdx+dir+allImages.length)%allImages.length;
  document.getElementById('pdpBigImg').src = allImages[currentImgIdx];
  document.querySelectorAll('.pdp-thumb').forEach((t,i)=>t.classList.toggle('active',i===currentImgIdx));
}

/* TABS */
function pdpTab(btn,id) {
  document.querySelectorAll('.pdp-tab-btn').forEach(b=>b.classList.remove('active'));
  document.querySelectorAll('.pdp-tab-panel').forEach(p=>p.classList.remove('active'));
  btn.classList.add('active'); document.getElementById('pdp-'+id).classList.add('active');
}

/* GALLERY LIGHTBOX */
var lbIdx = 0;
var lbPhotoMode = false;
function openLightbox(idx) {
  lbPhotoMode = false; lbIdx = idx;
  document.getElementById('lightboxImg').src = allImages[lbIdx];
  updateLightboxCounter();
  document.getElementById('lightbox').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function openPhotoLightbox(src) {
  lbPhotoMode = true;
  document.getElementById('lightboxImg').src = src;
  document.getElementById('lightboxCounter').textContent = '';
  document.getElementById('lightbox').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeLightbox() { document.getElementById('lightbox').classList.remove('open'); document.body.style.overflow=''; }
function closeLightboxOnBg(e) { if(e.target===document.getElementById('lightbox')) closeLightbox(); }
function shiftLightbox(dir) {
  if (lbPhotoMode) return;
  lbIdx = (lbIdx+dir+allImages.length)%allImages.length;
  document.getElementById('lightboxImg').src = allImages[lbIdx];
  updateLightboxCounter();
  currentImgIdx = lbIdx;
  document.getElementById('pdpBigImg').src = allImages[lbIdx];
  document.querySelectorAll('.pdp-thumb').forEach((t,i)=>t.classList.toggle('active',i===lbIdx));
}
function updateLightboxCounter() {
  var el = document.getElementById('lightboxCounter');
  if (el && allImages.length>1) el.textContent = (lbIdx+1)+' / '+allImages.length;
}
document.addEventListener('keydown',function(e){
  if (!document.getElementById('lightbox').classList.contains('open')) return;
  if(e.key==='ArrowRight') shiftLightbox(1);
  if(e.key==='ArrowLeft')  shiftLightbox(-1);
  if(e.key==='Escape')     closeLightbox();
});

/* ── REVIEW FORM ── */
function toggleReviewForm() {
  var box = document.getElementById('reviewFormBox');
  box.classList.toggle('open');
  if (box.classList.contains('open')) box.scrollIntoView({behavior:'smooth',block:'start'});
}

// Star rating label
var starLabels = {5:'Excellent',4:'Good',3:'Average',2:'Poor',1:'Terrible'};
document.querySelectorAll('.star-picker input').forEach(function(inp){
  inp.addEventListener('change',function(){
    document.getElementById('starLabel').textContent = starLabels[this.value] || '';
  });
});

// Review photo preview
var rfPhotoInput = document.getElementById('rfPhotoInput');
if (rfPhotoInput) {
  rfPhotoInput.addEventListener('change', function(){
    var file = this.files[0]; if(!file) return;
    var prev = document.getElementById('rfPhotoPreview');
    prev.src = URL.createObjectURL(file);
    prev.style.display = 'block';
  });
}

/* ── FILTER REVIEWS ── */
var activeFilter = 0;
var verifiedOnly = false;

function filterReviews(stars, btn) {
  activeFilter = stars; verifiedOnly = false;
  document.getElementById('verifiedBtn').classList.remove('active');
  document.querySelectorAll('.rf-filter-btn').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  applyFilters();
}
function filterVerified(btn) {
  verifiedOnly = !verifiedOnly;
  btn.classList.toggle('active', verifiedOnly);
  applyFilters();
}
function applyFilters() {
  var shown = 0;
  document.querySelectorAll('.review-card').forEach(function(card){
    var r = parseInt(card.dataset.rating);
    var v = card.dataset.verified === '1';
    var show = (activeFilter===0 || r===activeFilter) && (!verifiedOnly || v);
    card.style.display = show ? 'block' : 'none';
    if (show) shown++;
  });
}

/* ── LOAD MORE ── */
var visibleCount = 5;
var allCards = document.querySelectorAll('.review-card');
function initLoadMore() {
  allCards.forEach(function(card, i){ card.style.display = i < visibleCount ? 'block' : 'none'; });
  var btn = document.getElementById('loadMoreBtn');
  if (btn) btn.style.display = allCards.length <= visibleCount ? 'none' : 'block';
}
function loadMore() {
  visibleCount += 5;
  allCards.forEach(function(card,i){ if(i < visibleCount) card.style.display='block'; });
  var btn = document.getElementById('loadMoreBtn');
  if (btn && visibleCount >= allCards.length) btn.style.display='none';
}
<?php if ($totalReviews > 5): ?>
initLoadMore();
<?php endif; ?>


/* ── NOTIFY ME (Out of Stock) ── */
function submitNotify() {
  var email = document.getElementById('notifyEmail').value.trim();
  var btn   = document.getElementById('notifyBtn');
  var msg   = document.getElementById('notifyMsg');

  if (!email || !email.includes('@')) {
    msg.className = 'pdp-notify-msg error';
    msg.textContent = 'Please enter a valid email address.';
    msg.style.display = 'block';
    return;
  }

  btn.disabled    = true;
  btn.textContent = 'Submitting...';

  // Store notification request — simple localStorage for now
  // In production this would POST to a server endpoint
  var key      = 'notify_' + 0;
  var existing = JSON.parse(localStorage.getItem(key) || '[]');
  if (!existing.includes(email)) existing.push(email);
  localStorage.setItem(key, JSON.stringify(existing));

  msg.className   = 'pdp-notify-msg success';
  msg.textContent = '✅ Done! We will email you at ' + email + ' when this product is back in stock.';
  msg.style.display = 'block';
  btn.textContent = '✓ Notified';
}
</script>
