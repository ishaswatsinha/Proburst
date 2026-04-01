<?php
// admin/layout.php — shared header, sidebar, and footer for all admin pages
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$currentPage = basename($_SERVER['PHP_SELF'], '.php');

$navItems = [
    'index'      => ['icon' => '📊', 'label' => 'Dashboard'],
    'orders'     => ['icon' => '📦', 'label' => 'Orders'],
    'products'   => ['icon' => '🛒', 'label' => 'Products'],
    'categories' => ['icon' => '🗂️',  'label' => 'Categories'],
    'users'      => ['icon' => '👥', 'label' => 'Users'],
    'reviews'    => ['icon' => '⭐', 'label' => 'Reviews'],
    'leads'           => ['icon' => '📋', 'label' => 'Franchise Leads'],
    'product_reviews' => ['icon' => '⭐', 'label' => 'Product Reviews'],
];

function adminHead(string $title): void {
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$title} — Proburst Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@500;600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="admin.css">
</head>
<body>
HTML;
}

function adminSidebar(array $navItems, string $currentPage): void {

    // ── MOBILE TOP BAR ──
    echo '
    <!-- Mobile top bar (only visible on small screens) -->
    <div class="mobile-topbar">
      <div class="brand-area">
        <span style="font-size:20px">⚡</span>
        <span class="mob-brand-name">PROBURST</span>
        <span class="mob-brand-tag">ADMIN</span>
      </div>
      <button class="hamburger-btn" id="hamburgerBtn" aria-label="Toggle menu">
        <span></span>
        <span></span>
        <span></span>
      </button>
    </div>

    <!-- Overlay that closes sidebar when tapped -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    ';

    // ── SIDEBAR ──
    echo '<div class="admin-wrap">';
    echo '<aside class="sidebar" id="adminSidebar">';

    // Sidebar brand (desktop)
    echo '<div class="sidebar-brand">
            <span class="brand-logo">⚡</span>
            <span class="brand-name">PROBURST</span>
            <span class="brand-tag">ADMIN</span>
          </div>';

    // Nav links
    echo '<nav class="sidebar-nav">';
    foreach ($navItems as $page => $info) {
        $active = ($currentPage === $page) ? ' active' : '';
        echo "<a href=\"{$page}.php\" class=\"nav-item{$active}\">
                <span class=\"nav-icon\">{$info['icon']}</span>
                <span class=\"nav-label\">{$info['label']}</span>
              </a>";
    }
    echo '</nav>';

    echo '<div class="sidebar-footer"><a href="../pages/logout.php" class="logout-btn">⬅ Logout</a></div>';
    echo '</aside>';

    // Main content wrapper opens here
    echo '<div class="admin-main">';
}

function adminClose(): void {
    echo '</div></div>'; // close .admin-main and .admin-wrap

    // ── HAMBURGER JAVASCRIPT ──
    echo <<<JS
<script>
(function () {
  var btn      = document.getElementById('hamburgerBtn');
  var sidebar  = document.getElementById('adminSidebar');
  var overlay  = document.getElementById('sidebarOverlay');

  if (!btn || !sidebar || !overlay) return;

  function openSidebar() {
    sidebar.classList.add('sidebar-open');
    overlay.classList.add('visible');
    btn.classList.add('open');
    document.body.style.overflow = 'hidden'; // prevent background scroll
  }

  function closeSidebar() {
    sidebar.classList.remove('sidebar-open');
    overlay.classList.remove('visible');
    btn.classList.remove('open');
    document.body.style.overflow = '';
  }

  btn.addEventListener('click', function () {
    sidebar.classList.contains('sidebar-open') ? closeSidebar() : openSidebar();
  });

  // Tap overlay to close
  overlay.addEventListener('click', closeSidebar);

  // Close sidebar when a nav link is tapped on mobile
  var navLinks = sidebar.querySelectorAll('.nav-item');
  navLinks.forEach(function (link) {
    link.addEventListener('click', function () {
      if (window.innerWidth <= 768) closeSidebar();
    });
  });

  // Close sidebar if window resized back to desktop
  window.addEventListener('resize', function () {
    if (window.innerWidth > 768) closeSidebar();
  });
})();
</script>
JS;

    echo '</body></html>';
}
