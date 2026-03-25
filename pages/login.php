<?php
// =============================================
// pages/login.php
// =============================================
session_start();
require_once '../config/pdo.php';
require_once '../models/User.php';
require_once '../includes/auth.php';

if (isLoggedIn()) { header('Location: /index.php'); exit; }

$errors = [];
$old    = ['email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $old      = ['email' => $email];

    if (empty($email) || empty($password)) {
        $errors[] = 'Both email and password are required.';
    } else {
        $userModel = new User($pdo);
        $user      = $userModel->authenticate($email, $password);

        if (!$user) {
            $errors[] = 'Invalid email or password.';
        } else {
            loginUser($user);
            setFlash('success', 'Welcome back, ' . htmlspecialchars($user['name']) . '!');

            // Redirect to the page they were trying to visit, or home
            $redirect = $_SESSION['redirect_after_login'] ?? '/proburst/index.php';
            unset($_SESSION['redirect_after_login']);
            header("Location: $redirect");
            exit;
        }
    }
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Proburst</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">

        <div class="auth-logo">
            <img src="../assets/images/Light_Logo.png" alt="Proburst">
        </div>

        <h2>Welcome Back</h2>
        <p class="sub">Login to your Proburst account</p>

        <?php if ($flash && $flash['type'] === 'success'): ?>
            <div class="flash-success">✅ <?= htmlspecialchars($flash['message']) ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="auth-errors">
                <?php foreach ($errors as $e): ?>
                    <p>⚠ <?= $e ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="you@email.com"
                       value="<?= htmlspecialchars($old['email']) ?>" required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Your password" required>
            </div>

            <div class="forgot">
                <a href="forgot-password.php">Forgot password?</a>
            </div>

            <button type="submit" class="btn-auth">LOGIN →</button>
        </form>

        <p class="auth-switch">
            New to Proburst? <a href="register.php">Create account</a>
        </p>
    </div>
</div>
</body>
</html>
