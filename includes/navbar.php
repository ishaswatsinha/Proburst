<?php
// includes/navbar.php
if (session_status() === PHP_SESSION_NONE)
  session_start();
require_once __DIR__ . '/../includes/auth.php';
include __DIR__ . '/../config/database.php';

$catQuery = $conn->query("SELECT * FROM categories");

$navAvatarInitial = '';
$navFirstName = '';
if (isLoggedIn() && !empty($_SESSION['user_name'])) {
  $navAvatarInitial = strtoupper(mb_substr($_SESSION['user_name'], 0, 1));
  $navFirstName = htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]);
}
?>

<div class="top-bar">Flash Sale is Live. Shop Now.</div>

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
    <input type="text" id="navSearch" placeholder="Search products..." autocomplete="off">
    <span class="search-icon">🔍</span>
    <div class="search-dropdown" id="searchDropdown"></div>
  </div>

  <!-- ICONS -->
  <div class="nav-icons">
    <span class="icons"><i class="fa-solid fa-user"></i></span>
    <span class="icons"><i class="fa-solid fa-heart"></i></span>
    <a href="/proburst/pages/cart.php" class="icons cart-icon">
      <i class="fa-solid fa-cart-shopping"></i>
      <span id="cart-count">0</span>
    </a>
  </div>

  <?php if (isLoggedIn()): ?>
    <div class="nav-user-menu">
      <a href="/proburst/pages/account.php" class="nav-user-btn">

      </a>

      <?php if ($_SESSION['user_role'] === 'user'): ?>
        <a href="/proburst/pages/account.php" style="color:#ff4d00;font-weight:600;margin-right:10px;">
          <span class="nav-avatar"><?= $navAvatarInitial ?></span>
          <span><?= $navFirstName ?></span>
        </a>
      <?php endif; ?>

      <?php if ($_SESSION['user_role'] === 'admin'): ?>
        <a href="/proburst/admin/" style="color:#ff4d00;font-weight:600;margin-right:10px; ">⚡<span><?= $navFirstName ?></span>
</a>
      <?php endif; ?>

      <a href="/proburst/pages/logout.php" class="nav-logout-btn">Logout</a>
    </div>
  <?php else: ?>
    <div class="nav-auth-btns">
      <a href="/proburst/pages/login.php" class="nav-login-btn">Login</a>
      <a href="/proburst/pages/register.php" class="nav-register-btn">Sign Up</a>
    </div>
  <?php endif; ?>

</nav>

<!-- MOBILE MENU -->
<div class="menu-bar">
  <div class="menu-close" id="menuClose">✕</div>
  <ul>
    <li><a href="/proburst/index.php">Home</a></li>
    <li><a href="/proburst/pages/about.php">About</a></li>
    <li class="dropdown">
      <a href="/proburst/pages/shop.php">Products</a>
      <div class="dropdown-menu">
        <?php while ($cat = $catQuery->fetch_assoc()): ?>
          <div class="dropdown-item has-submenu">
            <span><?= $cat['name'] ?> ›</span>
            <div class="submenu">
              <?php
              $subQuery = $conn->query("SELECT * FROM subcategories WHERE category_id=" . (int) $cat['id']);
              while ($sub = $subQuery->fetch_assoc()):
                ?>
                <a href="/proburst/pages/shop.php?subcategory=<?= $sub['id'] ?>">
                  <div><?= $sub['name'] ?></div>
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