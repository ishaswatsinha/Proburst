<?php
// ajax/check-login.php
// Returns JSON indicating if the current user is logged in.
// Called by cart.php before redirecting to checkout.

if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../includes/auth.php';

header('Content-Type: application/json');
echo json_encode(['loggedIn' => isLoggedIn()]);
