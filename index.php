<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<?php
include 'config/database.php';

$best = $conn->query("SELECT * FROM products ORDER BY id DESC LIMIT 6");
$new = $conn->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 6");
?>

<section class="hero-slider">

  <div class="slider">

    <!-- SLIDE 1 -->
    <div class="slide">
      <img src="assets/images/banners/banner1.jpeg" alt="">
    </div>

    <!-- SLIDE 2 -->
    <div class="slide">
      <img src="assets/images/banners/banner2.jpeg" alt="">
    </div>

    <!-- SLIDE 3 -->
    <div class="slide">
      <img src="assets/images/banners/banner3.jpeg" alt="">
    </div>

    <!-- SLIDE 4 -->
    <div class="slide">
      <img src="assets/images/banners/banner4.jpeg" alt="">
    </div>

    <!-- SLIDE 5 -->
    <div class="slide">
      <img src="assets/images/banners/banner5.jpeg" alt="">
    </div>

    <!-- SLIDE 6 -->
    <div class="slide">
      <img src="assets/images/banners/banner6.jpeg" alt="">
    </div>

    <!-- ADD MORE -->

  </div>

  <!-- ARROWS -->
  <button class="arrow prev">❮</button>
  <button class="arrow next">❯</button>

  <!-- DOTS -->
  <div class="dots"></div>

</section>

<!-- PRODUCT SELLER SECTION -->

<section class="products-section">

  <!-- TABS -->
  <div class="tabs">
    <span class="tab active" data-tab="best">BEST SELLERS</span>
    <span class="tab" data-tab="new">NEW ARRIVALS</span>
  </div>

  <!-- PRODUCTS -->
  <div class="products">

    <!-- BEST SELLERS -->
    <div class="product-list active" id="best">

      <?php while($row = $best->fetch_assoc()): ?>

        <div class="product-card">

          <!-- <div class="badge">FREE GIFT</div> -->

          <div class="img-box">
            <img src="assets/images/<?php echo $row['image']; ?>">
          </div>

          <h3>
            <a href="pages/product.php?slug=<?php echo $row['slug']; ?>">
              <?php echo $row['name']; ?>
            </a>
          </h3>

          <div class="rating">⭐⭐⭐⭐☆</div>

          <div class="price">
            ₹<?php echo number_format($row['price']); ?>
          </div>

    <!-- <button 
  class="add-cart-btn"
  data-id="<?php echo $row['id']; ?>"
  data-name="<?php echo htmlspecialchars($row['name']); ?>"
  data-price="<?php echo $row['price']; ?>"
  data-image="<?php echo $row['image']; ?>"
>
  Add to Cart
</button> -->

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

    </div>


    <!-- NEW ARRIVALS -->
    <div class="product-list" id="new">

      <?php while($row = $new->fetch_assoc()): ?>

        <div class="product-card">

          <div class="img-box">
            <img src="assets/images/<?php echo $row['image']; ?>">
          </div>

          <h3>
            <a href="pages/product.php?slug=<?php echo $row['slug']; ?>">
              <?php echo $row['name']; ?>
            </a>
          </h3>

          <div class="rating">⭐⭐⭐⭐☆</div>

          <div class="price">
            ₹<?php echo number_format($row['price']); ?>
          </div>

          <!-- <button 
  class="add-cart-btn"
  data-id="<?php echo $row['id']; ?>"
  data-name="<?php echo htmlspecialchars($row['name']); ?>"
  data-price="<?php echo $row['price']; ?>"
  data-image="<?php echo $row['image']; ?>"
>
  Add to Cart
</button> -->
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

    </div>

  </div>

</section>
<!-- ======================
HOT DEALS SECTION
======================= -->
<section class="hot-offers">

  <h2 class="section-title">🔥 Hot Offers</h2>

  <div class="offers-wrapper">

    <!-- LEFT BUTTON -->
    <button class="offer-nav prev">❮</button>

    <!-- SLIDER -->
    <div class="offers-container" id="offersSlider">

      <?php
      $offers = $conn->query("SELECT * FROM products ORDER BY RAND() LIMIT 8");
      while($row = $offers->fetch_assoc()):
      ?>

      <div class="offer-card">

        <div class="offer-badge">SALE</div>

        <img src="assets/images/<?php echo $row['image']; ?>">

        <h3><?php echo $row['name']; ?></h3>

        <p class="offer-price">
          ₹<?php echo number_format($row['price']); ?>
          <span>₹<?php echo number_format($row['price'] + 500); ?></span>
        </p>

        <button onclick="addToCart(
          <?php echo $row['id']; ?>,
          <?php echo json_encode($row['name']); ?>,
          <?php echo (float)$row['price']; ?>,
          <?php echo json_encode($row['image']); ?>,
          this
        )">
          Grab Deal →
        </button>

      </div>

      <?php endwhile; ?>

    </div>

    <!-- RIGHT BUTTON -->
    <button class="offer-nav next">❯</button>

  </div>

</section>

<!-- ============= CATEGORIES SECTION ============= -->
<?php
$cats = $conn->query("SELECT * FROM categories");
?>

<section class="categories-section">

  <h2 class="section-title">Shop by Category</h2>

  <div class="category-wrapper">

    <button class="scroll-btn left" onclick="scrollCategory(-300)">‹</button>

    <div class="categories-slider" id="catSlider">

      <?php while($cat = $cats->fetch_assoc()): ?>

        <a href="pages/shop.php?category[]=<?php echo $cat['id']; ?>" class="category-card">

          <div class="category-image">
            <img src="assets/images/category<?php echo $cat['id']; ?>.webp" alt="">
          </div>

          <div class="category-overlay">
            <h3><?php echo strtoupper($cat['name']); ?></h3>
            <span>Shop Now →</span>
          </div>

        </a>

      <?php endwhile; ?>

    </div>

    <button class="scroll-btn right" onclick="scrollCategory(300)">›</button>

  </div>

</section>


<!-- ================
VIDEO SECTION 
================== -->
<?php
$videos = $conn->query("
  SELECT videos.*, products.id as pid, products.name, products.price, products.image
  FROM videos
  JOIN products ON videos.product_id = products.id
");
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

        <video muted loop autoplay>
          <source src="assets/videos/<?php echo $v['video']; ?>" type="video/mp4">
        </video>

        <div class="video-product">
          <img src="assets/images/<?php echo $v['image']; ?>">
          <div>
            <p><?php echo $v['name']; ?></p>
            <span>₹<?php echo $v['price']; ?></span>
          </div>
        </div>

      </div>

    <?php endwhile; ?>

  </div>

</section>



<!-- ============================
Let Influencers Talk (Video Section) 
============================== -->

<?php
$inf = $conn->query("SELECT * FROM influencers");
?>

<section class="influencer-section">

  <h2 class="section-title">Let Influencers Talk</h2>

  <div class="influencer-slider">

    <?php while($i = $inf->fetch_assoc()): ?>

      <div class="influencer-card"
        onclick="openInfluencer('assets/videos/<?php echo $i['video']; ?>')"> 

        <img src="assets/images/<?php echo $i['thumbnail']; ?>" alt="">
        
        <div class="play-btn">▶</div>

        <div class="inf-info">
          <h4><?php echo $i['name']; ?></h4>
        </div>
        
      </div>

    <?php endwhile; ?>

  </div>

</section>

<!-- ========================================
REVIEW SECTION
========================================  -->

<?php
$reviews = $conn->query("SELECT * FROM reviews");
?>

<section class="reviews-section">

  <h2 class="section-title">Real People. Real Reviews ❤️</h2>

  <div class="reviews-wrapper">

    <button class="review-btn left" onclick="scrollReviews(-300)">‹</button>

    <div class="reviews-slider" id="reviewSlider">

      <?php while($r = $reviews->fetch_assoc()): ?>

      <div class="review-card"
        data-video="<?php echo $r['video']; ?>"
        data-name="<?php echo $r['name']; ?>"
        data-role="<?php echo $r['role']; ?>"
        data-title="<?php echo $r['title']; ?>"
        onclick="openReviewReel(this)">

        <video muted autoplay loop>
          <source src="assets/videos/<?php echo $r['video']; ?>">
        </video>

        <!-- TOP TEXT -->
        <div class="review-overlay top">
          <?php echo $r['title']; ?>
        </div>

        <!-- BOTTOM -->
        <div class="review-overlay bottom">
          <h4>
            <?php echo $r['name']; ?>
            <?php if($r['verified']): ?>
              <span class="verified">✔</span>
            <?php endif; ?>
          </h4>
          <p><?php echo $r['role']; ?></p>
        </div>

      </div>

      <?php endwhile; ?>

    </div>

    <button class="review-btn right" onclick="scrollReviews(300)">›</button>

  </div>

</section>

<!-- MODAL -->
<div class="review-modal" id="reviewModal">

  <div class="review-box">

    <span class="close" onclick="closeReview()">×</span>

    <video id="reviewVideo" controls autoplay></video>

    <div class="review-info">
      <h3 id="reviewName"></h3>
      <p id="reviewRole"></p>

      <div class="like-btn" onclick="likeVideo(this)">
        ❤️ <span>0</span>
      </div>
    </div>

  </div>

</div>

<!-- ==================================
WHY CHOOSE US? SECTION 
================================== -->

<section class="why-pro-section">

  <div class="why-container">

    <!-- LEFT -->
    <div class="why-left">
      <h1>
        WHY <br>
        CHOOSE <br>
        <span>US</span>
      </h1>

      <div class="why-arrow">→</div>
    </div>

    <!-- RIGHT -->
    <div class="why-right">

      <div class="why-box light big">
        <h2><span class="counter" data-target="25">0</span> YEARS</h2>
        <p>LEADING SUPPLEMENT BRAND<br>IN THE WORLD</p>
      </div>

      <div class="why-box red ">
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

      <div class="why-box light">
        <h2>FREE</h2>
        <p>SHIPPING</p>
      </div>

    </div>

  </div>

</section>

<!-- ====================================
FRANCHISE SECTION
==================================== -->

<section class="franchise-section">

  <div class="franchise-container">

    <!-- LEFT CONTENT -->
    <div class="franchise-content">

      <h2>
        Start Your Own <span>Proburst Franchise</span>
      </h2>

      <p class="franchise-sub">
        Build a profitable fitness business with India’s growing sports nutrition brand.
      </p>

      <p>
       Partner with us and launch your own premium Proburst sports nutrition franchise store. We build complete, fully-stocked Proburst outlets with high-quality supplements including whey protein, mass gainers, amino acids, and advanced performance nutrition products. Join the growing fitness industry and build a profitable business with a trusted brand.
      </p>

      <p>
        The fitness and sports nutrition market in India is growing rapidly, with millions of people focusing on health, strength, and active lifestyles. Proburst by Intra Life Private Limited brings you a unique opportunity to become part of this booming industry by launching your own Proburst Franchise Store.
      </p>

      <p>
        We help entrepreneurs, fitness enthusiasts, gym owners, and business investors establish premium sports nutrition retail outlets that offer high-quality supplements trusted by athletes and fitness lovers across the country.
      </p>
      <p>
        With complete franchise support, premium product range, and strong brand backing, Proburst provides the perfect platform to build a profitable and scalable fitness business.
      </p>

     <button class="franchise-btn" onclick="openFranchiseForm()">
  Apply for Franchise →
</button>

<!-- BADGE -->
<!-- <div class="investment-badge">
  Investment starts from ₹2 Lakhs*
</div> -->

    </div>

    <!-- RIGHT VISUAL -->
    <div class="franchise-image">

      <img src="assets/images/franchiese.webp" alt="franchise">

      <!-- GLOW EFFECT -->
      <div class="franchise-glow"></div>

    </div>

  </div>

</section>

<!-- =========================
   FRANCHISE POPUP
========================= -->
<div class="franchise-modal" id="franchiseModal">

  <div class="franchise-form-box">

    <span class="franchise-close" onclick="closeFranchiseForm()">×</span>

    <h3>Start Your Franchise</h3>

    <form id="franchiseForm">

      <input type="text" name="name" placeholder="Full Name" required>
      <input type="text" name="phone" placeholder="Phone Number" required>
      <input type="text" name="city" placeholder="City" required>

      <button type="submit">Submit & Continue</button>

    </form>

  </div>

</div>


<?php include 'includes/footer.php'; ?>