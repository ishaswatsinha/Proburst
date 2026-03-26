<?php
// pages/register.php — mysqli only

if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/database.php'; // gives $conn
require_once '../models/User.php';
require_once '../includes/auth.php';

if (isLoggedIn()) { header('Location: /proburst/index.php'); exit; }

$errors = [];
$old    = ['name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $phone    = trim($_POST['phone']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm']  ?? '');
    $old = compact('name', 'email', 'phone');

    if (empty($name))                              $errors[] = 'Full name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (!preg_match('/^[0-9]{10}$/', $phone))      $errors[] = 'Enter a valid 10-digit phone number.';
    if (strlen($password) < 8)                     $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm)                    $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $userModel = new User($conn);
        $userId    = $userModel->create($name, $email, $phone, $password);

        if ($userId === false) {
            $errors[] = 'This email is already registered. <a href="login.php">Login instead?</a>';
        } else {
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
    <!-- <style>
        .auth-wrapper{min-height:100vh;display:flex;align-items:center;justify-content:center;background:#0a0a0a;padding:40px 16px}
        .auth-card{background:#111;border:1px solid #222;border-radius:16px;padding:40px 36px;width:100%;max-width:460px}
        .auth-logo{text-align:center;margin-bottom:28px}
        .auth-logo img{height:48px}
        .auth-card h2{color:#fff;font-size:1.6rem;margin-bottom:6px;text-align:center}
        .auth-card p.sub{color:#888;text-align:center;margin-bottom:28px;font-size:.9rem}
        .form-group{margin-bottom:16px}
        .form-group label{display:block;color:#ccc;font-size:.85rem;margin-bottom:6px}
        .form-group input{width:100%;background:#1a1a1a;border:1px solid #333;border-radius:8px;padding:12px 14px;color:#fff;font-size:.95rem;transition:border .2s;box-sizing:border-box}
        .form-group input:focus{outline:none;border-color:#e63946}
        .btn-auth{width:100%;background:#e63946;color:#fff;border:none;border-radius:8px;padding:13px;font-size:1rem;font-weight:700;cursor:pointer;margin-top:8px;letter-spacing:.5px}
        .btn-auth:hover{background:#c1121f}
        .auth-errors{background:#2a0a0a;border:1px solid #e63946;border-radius:8px;padding:12px 16px;margin-bottom:20px}
        .auth-errors p{color:#ff6b6b;font-size:.85rem;margin:4px 0}
        .auth-errors a{color:#e63946}
        .auth-switch{text-align:center;margin-top:20px;color:#888;font-size:.9rem}
        .auth-switch a{color:#e63946;text-decoration:none;font-weight:600}
    </style> -->
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-logo"><img src="../assets/images/Light_Logo.png" alt="Proburst"></div>
        <h2>Create Account</h2>
        <p class="sub">Join Proburst and fuel your journey 💪</p>

        <?php if (!empty($errors)): ?>
            <div class="auth-errors">
                <?php foreach ($errors as $e): ?><p>⚠ <?= $e ?></p><?php endforeach; ?>
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

        <p class="auth-switch">Already have an account? <a href="login.php">Login here</a></p>
    </div>
</div>
</body>
</html>
