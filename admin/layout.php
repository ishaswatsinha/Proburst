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
    'leads'      => ['icon' => '📋', 'label' => 'Franchise Leads'],
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
    echo '<div class="admin-wrap">';
    echo '<aside class="sidebar">';
    echo '<div class="sidebar-brand"><span class="brand-logo">⚡</span><span class="brand-name">PROBURST</span><span class="brand-tag">ADMIN</span></div>';
    echo '<nav class="sidebar-nav">';
    foreach ($navItems as $page => $info) {
        $active = ($currentPage === $page) ? ' active' : '';
        echo "<a href=\"{$page}.php\" class=\"nav-item{$active}\"><span class=\"nav-icon\">{$info['icon']}</span><span class=\"nav-label\">{$info['label']}</span></a>";
    }
    echo '</nav>';
    echo '<div class="sidebar-footer"><a href="../pages/logout.php" class="logout-btn">⬅ Logout</a></div>';
    echo '</aside>';
    echo '<div class="admin-main">';
}

function adminClose(): void {
    echo '</div></div></body></html>';
}
