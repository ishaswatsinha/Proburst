<?php
// =============================================
// pages/register.php
// =============================================
session_start();
require_once '../config/pdo.php';
require_once '../models/User.php';
require_once '../includes/auth.php';

// Already logged in? Go home
if (isLoggedIn()) { header('Location: /index.php'); exit; }

$errors = [];
$old    = ['name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $phone    = trim($_POST['phone']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm']  ?? '');

    $old = compact('name', 'email', 'phone');

    // Validation
    if (empty($name))                          $errors[] = 'Full name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (!preg_match('/^[0-9]{10}$/', $phone))  $errors[] = 'Enter a valid 10-digit phone number.';
    if (strlen($password) < 8)                 $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm)                $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $userModel = new User($pdo);
        $userId    = $userModel->create($name, $email, $phone, $password);

        if ($userId === false) {
            $errors[] = 'This email is already registered. <a href="login.php">Login instead?</a>';
        } else {
            // Auto-login after registration
            $user = $userModel->findById($userId);
            loginUser($user);
            setFlash('success', 'Welcome to Proburst, ' . htmlspecialchars($name) . '! 🎉');
            header('Location: /proburst/index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — Proburst</title>
    <link rel="stylesheet" href="../assets/css/style.css">
   
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">

        <div class="auth-logo">
            <img src="../assets/images/Light_Logo.png" alt="Proburst">
        </div>

        <h2>Create Account</h2>
        <p class="sub">Join Proburst and fuel your journey 💪</p>

        <?php if (!empty($errors)): ?>
            <div class="auth-errors">
                <?php foreach ($errors as $e): ?>
                    <p>⚠ <?= $e ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" placeholder="John Doe"
                       value="<?= htmlspecialchars($old['name']) ?>" required>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="you@email.com"
                       value="<?= htmlspecialchars($old['email']) ?>" required>
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" name="phone" placeholder="10-digit mobile number"
                       value="<?= htmlspecialchars($old['phone']) ?>" maxlength="10" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Min. 8 characters" required>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm" placeholder="Re-enter password" required>
            </div>

            <button type="submit" class="btn-auth">CREATE ACCOUNT →</button>
        </form>

        <p class="auth-switch">
            Already have an account? <a href="login.php">Login here</a>
        </p>
    </div>
</div>
</body>
</html>
