<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<?php
include 'config/database.php';

// Newly launched — latest 10 products
$newly = $conn->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 10");

// What People Love — categories
$cats = $conn->query("SELECT * FROM categories ORDER BY id ASC");

// Check if mrp column exists — graceful fallback
$hasMrp = false;
$colCheck = $conn->query("SHOW COLUMNS FROM products LIKE 'mrp'");
if ($colCheck && $colCheck->num_rows > 0) $hasMrp = true;

// Check discount_percent column
$hasDiscount = false;
$discCheck = $conn->query("SHOW COLUMNS FROM products LIKE 'discount_percent'");
if ($discCheck && $discCheck->num_rows > 0) $hasDiscount = true;
?>

<!-- ═══════════════════════════════════════════
     HERO SLIDER
═══════════════════════════════════════════ -->
<section class="hero-slider">
  <div class="slider">
    <div class="slide"><img src="assets/images/banners/banner1.jpeg" alt="Proburst"></div>
    <div class="slide"><img src="assets/images/banners/banner2.jpeg" alt="Proburst"></div>
    <div class="slide"><img src="assets/images/banners/banner3.jpeg" alt="Proburst"></div>
    <div class="slide"><img src="assets/images/banners/banner4.jpeg" alt="Proburst"></div>
    <div class="slide"><img src="assets/images/banners/banner5.jpeg" alt="Proburst"></div>
    <div class="slide"><img src="assets/images/banners/banner6.jpeg" alt="Proburst"></div>
  </div>
  <button class="arrow prev">&#10094;</button>
  <button class="arrow next">&#10095;</button>
  <div class="dots"></div>
</section>


<!-- ═══════════════════════════════════════════
     WHAT PEOPLE LOVE
═══════════════════════════════════════════ -->
<section class="what-people-love">
  <h2 class="wpl-title">What People <span>Love</span></h2>
  <div class="wpl-grid">
    <?php while($cat = $cats->fetch_assoc()): ?>
    <a href="pages/shop.php?category[]=<?php echo $cat['id']; ?>" class="wpl-item">
      <div class="wpl-circle">
        <img src="assets/images/category<?php echo $cat['id']; ?>.webp" alt="<?php echo htmlspecialchars($cat['name']); ?>">
      </div>
      <p><?php echo htmlspecialchars($cat['name']); ?></p>
    </a>
    <?php endwhile; ?>
  </div>
</section>


<!-- ═══════════════════════════════════════════
     NEWLY LAUNCHED
═══════════════════════════════════════════ -->
<section class="newly-launched">
  <div class="nl-header">
    <h2 class="nl-title">Newly <span>Launched</span></h2>
    <a href="pages/shop.php" class="nl-view-all">View All &#8594;</a>
  </div>

  <div class="nl-wrapper">
    <button class="nl-nav" id="nlPrev" aria-label="Previous">&#10094;</button>

    <div class="nl-slider" id="nlSlider">
      <?php while($row = $newly->fetch_assoc()):
        // Use real columns if they exist, else calculate
        if ($hasDiscount && !empty($row['discount_percent'])) {
          $disc = (int)$row['discount_percent'];
          $mrp  = $hasMrp && !empty($row['mrp']) ? (float)$row['mrp'] : round($row['price'] * 100 / (100 - $disc));
        } elseif ($hasMrp && !empty($row['mrp'])) {
          $mrp  = (float)$row['mrp'];
          $disc = $mrp > $row['price'] ? round(($mrp - $row['price']) / $mrp * 100) : 0;
        } else {
          $disc = rand(15, 40); // placeholder until real data added
          $mrp  = round($row['price'] * 100 / (100 - $disc));
        }
        $reviewCount = rand(10, 350); // placeholder
      ?>
      <div class="nl-card"
           data-id="<?php echo $row['id']; ?>"
           data-name="<?php echo htmlspecialchars($row['name'], ENT_QUOTES); ?>"
           data-price="<?php echo $row['price']; ?>"
           data-mrp="<?php echo $mrp; ?>"
           data-disc="<?php echo $disc; ?>"
           data-image="<?php echo $row['image']; ?>"
           data-slug="<?php echo $row['slug']; ?>">

        <!-- DISCOUNT BADGE -->
        <div class="nl-badge">Save up to <?php echo $disc; ?>%</div>

        <!-- IMAGE -->
        <a href="pages/product.php?slug=<?php echo $row['slug']; ?>" class="nl-img-link">
          <div class="nl-img-box">
            <img src="assets/images/<?php echo $row['image']; ?>"
                 alt="<?php echo htmlspecialchars($row['name']); ?>"
                 loading="lazy">
          </div>
        </a>

        <!-- INFO -->
        <div class="nl-info">
          <h3>
            <a href="pages/product.php?slug=<?php echo $row['slug']; ?>">
              <?php echo htmlspecialchars($row['name']); ?>
            </a>
          </h3>
          <div class="nl-price-row">
            <span class="nl-mrp">&#8377;<?php echo number_format($mrp); ?></span>
            <span class="nl-price">&#8377;<?php echo number_format($row['price']); ?></span>
          </div>
          <div class="nl-rating">
            <span class="nl-stars">&#9733;&#9733;&#9733;&#9733;&#9734;</span>
            <span class="nl-rcount"><?php echo $reviewCount; ?> Reviews</span>
          </div>
          <div class="nl-stock-tag">&#10004; In stock</div>
        </div>

        <!-- HOVER ACTIONS -->
        <div class="nl-hover-actions">
          <!-- Quick Add (no variants) -->
          <button class="nl-btn-cart"
                  onclick="nlQuickAdd(this, event)"
                  data-id="<?php echo $row['id']; ?>"
                  data-name="<?php echo htmlspecialchars($row['name'], ENT_QUOTES); ?>"
                  data-price="<?php echo $row['price']; ?>"
                  data-image="<?php echo $row['image']; ?>">
            Add to Cart
          </button>
          <!-- Choose Options (opens modal with variants) -->
          <button class="nl-btn-options"
                  onclick="openChooseOptions(<?php echo $row['id']; ?>, event)">
            Choose Options
          </button>
        </div>

      </div>
      <?php endwhile; ?>
    </div>

    <button class="nl-nav" id="nlNext" aria-label="Next">&#10095;</button>
  </div>
</section>


<!-- ═══════════════════════════════════════════
     CHOOSE OPTIONS MODAL
═══════════════════════════════════════════ -->
<div class="co-overlay" id="coOverlay" onclick="closeChooseOptions()"></div>

<div class="co-modal" id="coModal" role="dialog" aria-modal="true" aria-labelledby="coModalTitle">

  <button class="co-close" onclick="closeChooseOptions()" aria-label="Close">&times;</button>

  <!-- LOADER -->
  <div class="co-loader" id="coLoader">
    <div class="co-spinner"></div>
  </div>

  <!-- CONTENT (filled by JS) -->
  <div class="co-body" id="coBody" style="display:none">

    <div class="co-left">
      <div class="co-save-badge" id="coSaveBadge"></div>
      <img id="coImage" src="" alt="">
    </div>

    <div class="co-right">
      <h2 class="co-title" id="coModalTitle"></h2>

      <div class="co-price-row">
        <span class="co-save-tag" id="coSaveTag"></span>
        <div>
          <span class="co-mrp-label">MRP: </span>
          <span class="co-mrp" id="coMrp"></span>
        </div>
        <div>
          <span class="co-price-label">Price: </span>
          <span class="co-price" id="coPrice"></span>
        </div>
        <p class="co-tax-note">Inclusive of all Taxes</p>
      </div>

      <!-- FLAVOUR OPTIONS -->
      <div class="co-section" id="coFlavourSection" style="display:none">
        <p class="co-section-label">Flavor: <strong id="coFlavourSelected"></strong></p>
        <div class="co-options-grid" id="coFlavourGrid"></div>
      </div>

      <!-- WEIGHT OPTIONS -->
      <div class="co-section" id="coWeightSection" style="display:none">
        <p class="co-section-label">Weight: <strong id="coWeightSelected"></strong></p>
        <div class="co-options-grid" id="coWeightGrid"></div>
      </div>

      <!-- QUANTITY -->
      <div class="co-qty-row">
        <span class="co-qty-label">Quantity</span>
        <div class="co-qty-ctrl">
          <button onclick="coChangeQty(-1)">&#8722;</button>
          <span id="coQty">1</span>
          <button onclick="coChangeQty(1)">&#43;</button>
        </div>
      </div>

      <!-- ACTIONS -->
      <button class="co-btn-cart" id="coAddCartBtn" onclick="coAddToCart()">
        Add to cart
      </button>
      <a class="co-btn-view" id="coViewProduct" href="#">
        View full details &#8594;
      </a>

    </div>
  </div>

</div>


<!-- ═══════════════════════════════════════════
     HOT DEALS
═══════════════════════════════════════════ -->
<section class="hot-offers">
  <h2 class="section-title">&#128293; Hot Offers</h2>
  <div class="offers-wrapper">
    <button class="offer-nav prev">&#10094;</button>
    <div class="offers-container" id="offersSlider">
      <?php
      $offers = $conn->query("SELECT * FROM products ORDER BY RAND() LIMIT 8");
      while($row = $offers->fetch_assoc()):
      ?>
      <div class="offer-card">
        <div class="offer-badge">SALE</div>
        <img src="assets/images/<?php echo $row['image']; ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
        <h3><?php echo htmlspecialchars($row['name']); ?></h3>
        <p class="offer-price">
          &#8377;<?php echo number_format($row['price']); ?>
          <span>&#8377;<?php echo number_format($row['price'] + 500); ?></span>
        </p>
        <button onclick="addToCart(<?php echo $row['id']; ?>,<?php echo json_encode($row['name']); ?>,<?php echo (float)$row['price']; ?>,<?php echo json_encode($row['image']); ?>,this)">
          Grab Deal &#8594;
        </button>
      </div>
      <?php endwhile; ?>
    </div>
    <button class="offer-nav next">&#10095;</button>
  </div>
</section>


<!-- ═══════════════════════════════════════════
     CATEGORIES SECTION (keeping original)
═══════════════════════════════════════════ -->
<?php $cats2 = $conn->query("SELECT * FROM categories"); ?>
<section class="categories-section">
  <h2 class="section-title">Shop by Category</h2>
  <div class="category-wrapper">
    <button class="scroll-btn left" onclick="scrollCategory(-300)">&#8249;</button>
    <div class="categories-slider" id="catSlider">
      <?php while($cat = $cats2->fetch_assoc()): ?>
      <a href="pages/shop.php?category[]=<?php echo $cat['id']; ?>" class="category-card">
        <div class="category-image">
          <img src="assets/images/category<?php echo $cat['id']; ?>.webp" alt="<?php echo htmlspecialchars($cat['name']); ?>">
        </div>
        <div class="category-overlay">
          <h3><?php echo strtoupper($cat['name']); ?></h3>
          <span>Shop Now &#8594;</span>
        </div>
      </a>
      <?php endwhile; ?>
    </div>
    <button class="scroll-btn right" onclick="scrollCategory(300)">&#8250;</button>
  </div>
</section>


<!-- ═══════════════════════════════════════════
     VIDEO SECTION
═══════════════════════════════════════════ -->
<?php
$videos = $conn->query("SELECT videos.*, products.id as pid, products.name, products.price, products.image FROM videos JOIN products ON videos.product_id = products.id");
?>
<section class="video-section">
  <h2 class="section-title">Shop Our Most Loved Products</h2>
  <div class="video-slider">
    <?php while($v = $videos->fetch_assoc()): ?>
    <div class="video-card"
      data-id="<?php echo $v['pid']; ?>"
      data-video="<?php echo $v['video']; ?>"
      data-name="<?php echo htmlspecialchars($v['name']); ?>"
      data-price="<?php echo $v['price']; ?>"
      data-image="<?php echo $v['image']; ?>"
      onclick="openReel(this)">
      <video muted loop autoplay><source src="assets/videos/<?php echo $v['video']; ?>" type="video/mp4"></video>
      <div class="video-product">
        <img src="assets/images/<?php echo $v['image']; ?>">
        <div><p><?php echo $v['name']; ?></p><span>&#8377;<?php echo $v['price']; ?></span></div>
      </div>
    </div>
    <?php endwhile; ?>
  </div>
</section>


<!-- ═══════════════════════════════════════════
     INFLUENCER SECTION
═══════════════════════════════════════════ -->
<?php $inf = $conn->query("SELECT * FROM influencers"); ?>
<section class="influencer-section">
  <h2 class="section-title">Let Influencers Talk</h2>
  <div class="influencer-slider">
    <?php while($i = $inf->fetch_assoc()): ?>
    <div class="influencer-card" onclick="openInfluencer('assets/videos/<?php echo $i['video']; ?>')">
      <img src="assets/images/<?php echo $i['thumbnail']; ?>" alt="<?php echo htmlspecialchars($i['name']); ?>">
      <div class="play-btn">&#9654;</div>
      <div class="inf-info"><h4><?php echo htmlspecialchars($i['name']); ?></h4></div>
    </div>
    <?php endwhile; ?>
  </div>
</section>


<!-- ═══════════════════════════════════════════
     REVIEW SECTION
═══════════════════════════════════════════ -->
<?php $reviews = $conn->query("SELECT * FROM reviews"); ?>
<section class="reviews-section">
  <h2 class="section-title">Real People. Real Reviews &#10084;&#65039;</h2>
  <div class="reviews-wrapper">
    <button class="review-btn left" onclick="scrollReviews(-300)">&#8249;</button>
    <div class="reviews-slider" id="reviewSlider">
      <?php while($r = $reviews->fetch_assoc()): ?>
      <div class="review-card"
        data-video="<?php echo $r['video']; ?>"
        data-name="<?php echo $r['name']; ?>"
        data-role="<?php echo $r['role']; ?>"
        data-title="<?php echo $r['title']; ?>"
        onclick="openReviewReel(this)">
        <video muted autoplay loop><source src="assets/videos/<?php echo $r['video']; ?>"></video>
        <div class="review-overlay top"><?php echo $r['title']; ?></div>
        <div class="review-overlay bottom">
          <h4><?php echo $r['name']; ?><?php if($r['verified']): ?><span class="verified">&#10004;</span><?php endif; ?></h4>
          <p><?php echo $r['role']; ?></p>
        </div>
      </div>
      <?php endwhile; ?>
    </div>
    <button class="review-btn right" onclick="scrollReviews(300)">&#8250;</button>
  </div>
</section>
<div class="review-modal" id="reviewModal">
  <div class="review-box">
    <span class="close" onclick="closeReview()">&#215;</span>
    <video id="reviewVideo" controls autoplay></video>
    <div class="review-info">
      <h3 id="reviewName"></h3><p id="reviewRole"></p>
      <div class="like-btn" onclick="likeVideo(this)">&#10084;&#65039; <span>0</span></div>
    </div>
  </div>
</div>


<!-- ═══════════════════════════════════════════
     WHY CHOOSE US
═══════════════════════════════════════════ -->
<section class="why-pro-section">
  <div class="why-container">
    <div class="why-left">
      <h1>WHY <br>CHOOSE <br><span>US</span></h1>
      <div class="why-arrow">&#8594;</div>
    </div>
    <div class="why-right">
      <div class="why-box light big">
        <h2><span class="counter" data-target="25">0</span> YEARS</h2>
        <p>LEADING SUPPLEMENT BRAND<br>IN THE WORLD</p>
      </div>
      <div class="why-box red">
        <h2><span class="counter" data-target="20">0</span>MILLION+</h2>
        <p>HAPPY CUSTOMERS</p>
      </div>
      <div class="why-box light">
        <h2><span class="counter" data-target="200">0</span>+</h2>
        <p>PRODUCTS</p>
      </div>
      <div class="why-box light">
        <h2>100%</h2>
        <p>GENUINE PRODUCTS</p>
      </div>
      <div class="why-box intralife">
        <div class="intralife-badge">INTRA<span>LIFE</span></div>
        <p>POWERED BY<br>INTRALIFE PVT. LTD.</p>
      </div>
    </div>
  </div>
</section>


<!-- ═══════════════════════════════════════════
     FRANCHISE SECTION
═══════════════════════════════════════════ -->
<section class="franchise-section">
  <div class="franchise-container">
    <div class="franchise-content">
      <h2>Start Your Own <span>Proburst Franchise</span></h2>
      <p class="franchise-sub">Build a profitable fitness business with India's growing sports nutrition brand.</p>
      <p>Partner with us and launch your own premium Proburst sports nutrition franchise store. We build complete, fully-stocked Proburst outlets with high-quality supplements including whey protein, mass gainers, amino acids, and advanced performance nutrition products.</p>
      <p>The fitness and sports nutrition market in India is growing rapidly. Proburst by Intra Life Private Limited brings you a unique opportunity to become part of this booming industry by launching your own Proburst Franchise Store.</p>
      <p>With complete franchise support, premium product range, and strong brand backing, Proburst provides the perfect platform to build a profitable and scalable fitness business.</p>
      <button class="franchise-btn" onclick="openFranchiseForm()">Apply for Franchise &#8594;</button>
    </div>
    <div class="franchise-image">
      <img src="assets/images/franchiese.webp" alt="franchise">
      <div class="franchise-glow"></div>
    </div>
  </div>
</section>

<div class="franchise-modal" id="franchiseModal">
  <div class="franchise-form-box">
    <span class="franchise-close" onclick="closeFranchiseForm()">&#215;</span>
    <h3>Start Your Franchise</h3>
    <form id="franchiseForm">
      <input type="text" name="name" placeholder="Full Name" required>
      <input type="text" name="phone" placeholder="Phone Number" required>
      <input type="text" name="city" placeholder="City" required>
      <button type="submit">Submit &amp; Continue</button>
    </form>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

<!-- ═══════════════════════════════════════════
     NEWLY LAUNCHED — JS
═══════════════════════════════════════════ -->
<script>
/* ── Slider arrows ─────────────────────────────────── */
(function () {
  var slider = document.getElementById('nlSlider');
  var prev   = document.getElementById('nlPrev');
  var next   = document.getElementById('nlNext');
  if (!slider) return;
  next.addEventListener('click', function () { slider.scrollBy({ left: 300, behavior: 'smooth' }); });
  prev.addEventListener('click', function () { slider.scrollBy({ left: -300, behavior: 'smooth' }); });
})();

/* ── Quick Add (direct, no modal) ─────────────────── */
function nlQuickAdd(btn, e) {
  e.preventDefault();
  e.stopPropagation();
  var id    = btn.dataset.id;
  var name  = btn.dataset.name;
  var price = parseFloat(btn.dataset.price);
  var image = btn.dataset.image;
  addToCart(id, name, price, image, btn);
}

/* ══════════════════════════════════════════════════
   CHOOSE OPTIONS MODAL
══════════════════════════════════════════════════ */
var coState = {
  productId    : null,
  productData  : null,
  selectedFlavour : null,
  selectedWeight  : null,
  qty          : 1
};

/* Open modal */
function openChooseOptions(productId, e) {
  if (e) { e.preventDefault(); e.stopPropagation(); }

  coState.productId    = productId;
  coState.qty          = 1;
  coState.selectedFlavour = null;
  coState.selectedWeight  = null;

  /* Show overlay + modal */
  document.getElementById('coOverlay').classList.add('active');
  document.getElementById('coModal').classList.add('active');
  document.body.style.overflow = 'hidden';

  /* Show loader, hide body */
  document.getElementById('coLoader').style.display = 'flex';
  document.getElementById('coBody').style.display   = 'none';

  /* Fetch data */
  fetch('/proburst/ajax/get-variants.php?id=' + productId)
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (!data.success) { closeChooseOptions(); return; }
      coState.productData = data;
      coRenderModal(data);
    })
    .catch(function () { closeChooseOptions(); });
}

/* Render modal content */
function coRenderModal(data) {
  var p = data.product;

  /* Image + badge */
  document.getElementById('coImage').src     = '/proburst/assets/images/' + p.image;
  document.getElementById('coImage').alt     = p.name;
  document.getElementById('coSaveBadge').textContent = p.discount > 0 ? 'Save up to ' + p.discount + '%' : '';
  document.getElementById('coSaveBadge').style.display = p.discount > 0 ? 'block' : 'none';

  /* Title */
  document.getElementById('coModalTitle').textContent = p.name;

  /* Price */
  document.getElementById('coSaveTag').textContent = p.discount > 0 ? 'Save ' + p.discount + '%' : '';
  document.getElementById('coSaveTag').style.display = p.discount > 0 ? 'inline-block' : 'none';
  document.getElementById('coMrp').innerHTML   = '<s>&#8377;' + numberFmt(p.mrp) + '</s>';
  document.getElementById('coPrice').textContent = '&#8377;' + numberFmt(p.price);
  document.getElementById('coPrice').innerHTML   = '&#8377;' + numberFmt(p.price);

  /* View product link */
  document.getElementById('coViewProduct').href = '/proburst/pages/product.php?slug=' + p.slug;

  /* Reset qty */
  coState.qty = 1;
  document.getElementById('coQty').textContent = '1';

  /* Flavours */
  var fSection = document.getElementById('coFlavourSection');
  var fGrid    = document.getElementById('coFlavourGrid');
  fGrid.innerHTML = '';
  if (data.flavours && data.flavours.length > 0) {
    fSection.style.display = 'block';
    data.flavours.forEach(function (f, i) {
      var btn = document.createElement('button');
      btn.className = 'co-opt-btn' + (f.in_stock == 0 ? ' co-opt-oos' : '');
      btn.textContent = f.name;
      btn.dataset.value = f.name;
      if (f.in_stock == 0) {
        btn.disabled = true;
        btn.title = 'Out of stock';
      }
      btn.addEventListener('click', function () {
        document.querySelectorAll('#coFlavourGrid .co-opt-btn').forEach(function (b) { b.classList.remove('active'); });
        this.classList.add('active');
        coState.selectedFlavour = this.dataset.value;
        document.getElementById('coFlavourSelected').textContent = this.dataset.value;
      });
      if (i === 0 && f.in_stock != 0) {
        btn.classList.add('active');
        coState.selectedFlavour = f.name;
        document.getElementById('coFlavourSelected').textContent = f.name;
      }
      fGrid.appendChild(btn);
    });
  } else {
    fSection.style.display = 'none';
  }

  /* Weights */
  var wSection = document.getElementById('coWeightSection');
  var wGrid    = document.getElementById('coWeightGrid');
  wGrid.innerHTML = '';
  if (data.weights && data.weights.length > 0) {
    wSection.style.display = 'block';
    data.weights.forEach(function (w, i) {
      var btn = document.createElement('button');
      btn.className = 'co-opt-btn' + (w.in_stock == 0 ? ' co-opt-oos' : '');
      btn.textContent = w.label;
      btn.dataset.value  = w.label;
      btn.dataset.price  = w.price;
      if (w.in_stock == 0) {
        btn.disabled = true;
        btn.title = 'Out of stock';
      }
      btn.addEventListener('click', function () {
        document.querySelectorAll('#coWeightGrid .co-opt-btn').forEach(function (b) { b.classList.remove('active'); });
        this.classList.add('active');
        coState.selectedWeight = this.dataset.value;
        document.getElementById('coWeightSelected').textContent = this.dataset.value;
        /* Update price if weight has its own price */
        if (this.dataset.price && parseFloat(this.dataset.price) > 0) {
          document.getElementById('coPrice').innerHTML = '&#8377;' + numberFmt(this.dataset.price);
          coState.productData.product.price = parseFloat(this.dataset.price);
        }
      });
      if (i === 0 && w.in_stock != 0) {
        btn.classList.add('active');
        coState.selectedWeight = w.label;
        document.getElementById('coWeightSelected').textContent = w.label;
        if (w.price && parseFloat(w.price) > 0) {
          document.getElementById('coPrice').innerHTML = '&#8377;' + numberFmt(w.price);
          coState.productData.product.price = parseFloat(w.price);
        }
      }
      wGrid.appendChild(btn);
    });
  } else {
    wSection.style.display = 'none';
  }

  /* Show body, hide loader */
  document.getElementById('coLoader').style.display = 'none';
  document.getElementById('coBody').style.display   = 'flex';
}

/* Qty controls */
function coChangeQty(n) {
  coState.qty = Math.max(1, Math.min(10, coState.qty + n));
  document.getElementById('coQty').textContent = coState.qty;
}

/* Add to cart from modal */
function coAddToCart() {
  var p    = coState.productData.product;
  var name = p.name;

  /* Append selected options to cart name for clarity */
  var suffix = [];
  if (coState.selectedFlavour) suffix.push(coState.selectedFlavour);
  if (coState.selectedWeight)  suffix.push(coState.selectedWeight);
  if (suffix.length) name += ' (' + suffix.join(', ') + ')';

  addToCart(p.id, name, p.price, p.image, null, coState.qty);
  closeChooseOptions();
}

/* Close modal */
function closeChooseOptions() {
  document.getElementById('coOverlay').classList.remove('active');
  document.getElementById('coModal').classList.remove('active');
  document.body.style.overflow = '';
}

/* ESC key */
document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape') closeChooseOptions();
});

/* Helper */
function numberFmt(n) {
  return parseInt(n).toLocaleString('en-IN');
}
</script>
