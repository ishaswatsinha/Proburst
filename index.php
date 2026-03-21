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

<!-- products-section -->



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

          <div class="badge">FREE GIFT</div>

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

    <button 
  class="add-cart-btn"
  data-id="<?php echo $row['id']; ?>"
  data-name="<?php echo htmlspecialchars($row['name']); ?>"
  data-price="<?php echo $row['price']; ?>"
  data-image="<?php echo $row['image']; ?>"
>
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

          <button 
  class="add-cart-btn"
  data-id="<?php echo $row['id']; ?>"
  data-name="<?php echo htmlspecialchars($row['name']); ?>"
  data-price="<?php echo $row['price']; ?>"
  data-image="<?php echo $row['image']; ?>"
>
  Add to Cart
</button>

        </div>

      <?php endwhile; ?>

    </div>

  </div>

</section>

<!-- ============= CATEGORIES SECTION ============= -->
<?php
$cats = $conn->query("SELECT * FROM categories");
?>

<section class="categories-section">

  <h2 class="section-title">Shop by Category</h2>

  <div class="categories-grid">

    <?php while($cat = $cats->fetch_assoc()): ?>

      <a href="pages/shop.php?category[]=<?php echo $cat['id']; ?>" class="category-card">

        <div class="category-image">

          <!-- TEMP IMAGE (you can map later from DB) -->
          <img src="assets/images/category<?php echo $cat['id']; ?>.webp" alt="">

        </div>

        <div class="category-overlay">
          <h3><?php echo strtoupper($cat['name']); ?></h3>
          <span>Shop Now →</span>
        </div>

      </a>

    <?php endwhile; ?>

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

  <h2 class="section-title">WATCH & SHOP</h2>

  <div class="video-slider">

    <?php while($v = $videos->fetch_assoc()): ?>

      <div class="video-card"
        data-id="<?php echo $v['pid']; ?>"
        data-video="<?php echo $v['video']; ?>"
        data-name="<?php echo htmlspecialchars($v['name']); ?>"
        data-price="<?php echo $v['price']; ?>"
        data-image="<?php echo $v['image']; ?>"
        onclick="openReel(this)">

        <video muted>
          <source src="assets/videos/<?php echo $v['video']; ?>" type="video/mp4">
        </video>

        <!-- PRODUCT INFO -->
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
        onclick="openInfluencer('<?php echo $i['video']; ?>')">

        <img src="assets/images/<?php echo $i['thumbnail']; ?>" alt="">

        <div class="play-btn">▶</div>

        <div class="inf-info">
          <h4><?php echo $i['name']; ?></h4>
        </div>

      </div>

    <?php endwhile; ?>

  </div>

</section>

<!-- ==========================
GOALS SECTION
============================ -->


<section class="goals">

  <h2>REACH YOUR POTENTIAL</h2>
  <p>Everyone has goals, let us help you with yours</p>

  <div class="goal-row">

    <!-- LEFT TEXT BLOCK -->
    <div class="goal-box dark">
      <h3>WHAT’S<br>YOUR<br>GOAL?</h3>
    </div>

    <!-- ITEM 1 -->
    <div class="goal-box orange">
      <img src="assets/images/products/product1.webp">
      <p>PREPARE BEFORE TRAINING</p>
    </div>

    <!-- ITEM 2 -->
    <div class="goal-box green">
      <img src="assets/images/products/product1.webp">
      <p>STRENGTH & ENDURANCE</p>
    </div>

    <!-- ITEM 3 -->
    <div class="goal-box orange">
      <img src="assets/images/products/product1.webp">
      <p>RECOVER AFTER TRAINING</p>
    </div>

    <!-- ITEM 4 -->
    <div class="goal-box green">
      <img src="assets/images/products/product1.webp">
      <p>WEIGHT GAIN</p>
    </div>

    <!-- ITEM 5 -->
    <div class="goal-box orange">
      <img src="assets/images/products/product1.webp">
      <p>ANYTIME ENDURANCE</p>
    </div>

  </div>

</section>
<!-- 
=========================================
EDUCATION AND RESOURCES
========================================= -->

<section class="education">

  <h2>EDUCATION AND RESOURCES</h2>

  <div class="edu-grid">

    <!-- CARD 1 -->
    <div class="edu-card">
      <img src="assets/images/e1.jpg">
      <div class="overlay">
        <h3>ARTICLES AND ADVICE</h3>
        <button>EXPLORE AND LEARN</button>
      </div>
    </div>

    <!-- CARD 2 -->
    <div class="edu-card">
      <img src="assets/images/e2.jpg">
      <div class="overlay">
        <h3>PROTIEN AND PACKED RECIPES</h3>
        <button>EXPLORE AND LEARN</button>
      </div>
    </div>

    <!-- CARD 3 -->
    <div class="edu-card">
      <img src="assets/images/e3.jpg">
      <div class="overlay">
        <h3>ATHLETES</h3>
        <button>EXPLORE AND LEARN</button>
      </div>
    </div>

  </div>

</section>

<!-- ===============================
 Our Range of Products 
 ===================================-->

 <section class="range">

  <h2>OUR RANGE OF PRODUCTS</h2>

  <div class="range-slider">

    <div class="range-track">

      <!-- SLIDE 1 -->
      <div class="range-item">
        <div class="range-img">
          <img src="assets/images/our-range-mass.webp">
        </div>
        <div class="range-content">
          <h3>ENERGY</h3>
          <p>
            Refuel and refocus with products that support energy and endurance.
            Boost your endurance with anytime energy from Optimum Nutrition.
          </p>
          <button>LEARN MORE</button>
        </div>
      </div>

      <!-- SLIDE 2 -->
      <div class="range-item">
        <div class="range-img">
          <img src="assets/images/our-range-mass.webp">
        </div>
        <div class="range-content">
          <h3>STRENGTH</h3>
          <p>
            Build strength and performance with high-quality supplements.
          </p>
          <button>LEARN MORE</button>
        </div>
      </div>

    </div>

    <!-- ARROWS -->
    <button class="range-arrow prev">❮</button>
    <button class="range-arrow next">❯</button>

    <!-- DOTS -->
    <div class="range-dots"></div>

  </div>

</section>

<!-- ======================
POWERING MORE THAN
=========================== -->

<section class="on-final">


  <!-- IMAGE NUMBER -->
  <div class="number-img">
    <img src="assets/images/billion-recoveries.webp" alt="2 Billion">
  </div>


  <!-- DESCRIPTION -->
  <p class="desc">
    Our appetite for success continues, fuelled by strong growth, strategic investment and exciting new acquisitions that complement our existing portfolio. We pride ourselves on developing the innovative, science-led products and cutting-edge ingredients that consumers want.
  </p>

</section>

<section class="quality">

  <h2>OPTIMUM QUALITY</h2>

  <p class="quality-desc">
    We are a global nutrition leader, a team of scientists, tastemakers, and relationship-builders with sights set on better. As curious as we are committed, we make it a point to listen to our partners and consumers, so that we can deliver the products people want and need. Through insight-and science-led innovation, we create healthier snack options, products that boost immunity and sports performance, the secret ingredients that make foods and drinks taste great, and customised nutrition solutions for a future of food that's personal and consumers that are ever-evolving.
  </p>

  <div class="quality-grid">

    <!-- ITEM 1 -->
    <div class="quality-item">
      <div class="icon">
        <i class="fas fa-hexagon"></i>
      </div>
      <h3>HIGH QUALITY RAW<br> MATERIALS</h3>
    </div>

    <!-- ITEM 2 -->
    <div class="quality-item">
      <div class="icon">
        <i class="fas fa-trophy"></i>
      </div>
      <h3>TOP RATED AND<br> REVIEWED</h3>
    </div>

    <!-- ITEM 3 -->
    <div class="quality-item">
      <div class="icon">
        <i class="fas fa-check"></i>
      </div>
      <h3>WE TEST & RE-TEST FOR<br> QUALITY</h3>
    </div>

  </div>

  <button class="quality-btn">LEARN ABOUT OUR QUALITY</button>

</section>


<?php include 'includes/footer.php'; ?>