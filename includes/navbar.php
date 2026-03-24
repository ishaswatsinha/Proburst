<?php
include __DIR__ . '/../config/database.php';
$catQuery = $conn->query("SELECT * FROM categories");
?>

<div class="top-bar">
  Flash Sale is Live. Shop Now.
</div>

<nav class="navbar">

  <!-- LOGO -->
  <div class="logo">
    <div class="hamburger" id="hamburger">☰</div>

    <a href="/proburst/index.php">
      <img src="/proburst/assets/images/Light_Logo.png" alt="logo">
    </a>
  </div>

  <!-- SEARCH -->
  <div class="search-box">
    <input type="text" placeholder="Search products...">
    <span class="search-icon">🔍</span>
  </div>

  <!-- ICONS -->
  <div class="nav-icons">

    <span class="icons">
      <i class="fa-solid fa-user"></i>
    </span>

    <span class="icons">
      <i class="fa-solid fa-heart"></i>
    </span>

    <a href="/proburst/pages/cart.php" class="icons cart-icon">
      <i class="fa-solid fa-cart-shopping"></i>
      <span id="cart-count">0</span>
    </a>

  </div>

</nav>

<!-- MENU -->
<div class="menu-bar">
  <div class="menu-close" id="menuClose">✕</div>
  <ul>

    <li><a href="/proburst/index.php">Home</a></li>
    <li><a href="/proburst/pages/about.php">About</a></li>

    <li class="dropdown">
      <a href="/proburst/pages/shop.php">Products</a>

      <div class="dropdown-menu">

        <?php while($cat = $catQuery->fetch_assoc()): ?>

          <div class="dropdown-item has-submenu">

            <span><?php echo $cat['name']; ?> ›</span>

            <div class="submenu">

              <?php
              $subQuery = $conn->query("SELECT * FROM subcategories WHERE category_id=".$cat['id']);
              while($sub = $subQuery->fetch_assoc()):
              ?>

                <a href="/proburst/pages/shop.php?subcategory=<?php echo $sub['id']; ?>">
                  <div><?php echo $sub['name']; ?></div>
                </a>

              <?php endwhile; ?>

            </div>

          </div>

        <?php endwhile; ?>

      </div>
    </li>

    <li><a href="#">Hot Offers</a></li>
    <li><a href="#">Best Sellers</a></li>
    <li><a href="#">Blog</a></li>
    <li><a href="#">Business Enquiry</a></li>

  </ul>
</div>

<div class="overlay" id="overlay"></div>