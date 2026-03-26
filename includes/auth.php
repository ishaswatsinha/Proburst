<?php
// =============================================
// includes/auth.php
// =============================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ----------------------------
// Check if user is logged in
// ----------------------------
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// ----------------------------
// Get current logged-in user
// ----------------------------
function currentUser(): array|null {
    if (!isLoggedIn()) return null;
    return [
        'id'    => $_SESSION['user_id'],
        'name'  => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'],
        'role'  => $_SESSION['user_role'],
    ];
}

// ----------------------------
// Protect a page (redirect if not logged in)
// Usage: requireLogin();  at top of any page
// ----------------------------
function requireLogin(string $redirect = '/proburst/pages/login.php'): void {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header("Location: $redirect");
        exit;
    }
}

// ----------------------------
// Protect admin-only pages
// ----------------------------
function requireAdmin(): void {
    requireLogin();
    if ($_SESSION['user_role'] !== 'admin') {
        http_response_code(403);
        die('<h2 style="text-align:center;margin-top:100px">403 — Access Denied</h2>');
    }
}

// ----------------------------
// Log the user in (set session)
// ----------------------------
function loginUser(array $user): void {
    session_regenerate_id(true); // prevent session fixation
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_name']  = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role']  = $user['role'];
}

// ----------------------------
// Log the user out
// ----------------------------
function logoutUser(): void {
    session_unset();
    session_destroy();
    header('Location: /proburst/pages/login.php');
    exit;
}

// ----------------------------
// Flash message helpers
// ----------------------------
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): array|null {
    if (!isset($_SESSION['flash'])) return null;
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}
