<?php
// pages/login.php — mysqli only

if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/database.php'; // gives $conn
require_once '../models/User.php';
require_once '../includes/auth.php';

if (isLoggedIn()) { header('Location: /proburst/index.php'); exit; }

$errors = [];
$old    = ['email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $old      = ['email' => $email];

    if (empty($email) || empty($password)) {
        $errors[] = 'Both email and password are required.';
    } else {
        $userModel = new User($conn);
        $user      = $userModel->authenticate($email, $password);

        if (!$user) {
            $errors[] = 'Invalid email or password.';
        } else {
            loginUser($user);
            setFlash('success', 'Welcome back, ' . htmlspecialchars($user['name']) . '!');
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
    <!-- <style>
        .auth-wrapper{min-height:100vh;display:flex;align-items:center;justify-content:center;background:#0a0a0a;padding:40px 16px}
        .auth-card{background:#111;border:1px solid #222;border-radius:16px;padding:40px 36px;width:100%;max-width:420px}
        .auth-logo{text-align:center;margin-bottom:28px}
        .auth-logo img{height:48px}
        .auth-card h2{color:#fff;font-size:1.6rem;margin-bottom:6px;text-align:center}
        .auth-card p.sub{color:#888;text-align:center;margin-bottom:28px;font-size:.9rem}
        .form-group{margin-bottom:16px}
        .form-group label{display:block;color:#ccc;font-size:.85rem;margin-bottom:6px}
        .form-group input{width:100%;background:#1a1a1a;border:1px solid #333;border-radius:8px;padding:12px 14px;color:#fff;font-size:.95rem;transition:border .2s;box-sizing:border-box}
        .form-group input:focus{outline:none;border-color:#e63946}
        .forgot{text-align:right;margin-top:-8px;margin-bottom:16px}
        .forgot a{color:#888;font-size:.82rem;text-decoration:none}
        .btn-auth{width:100%;background:#e63946;color:#fff;border:none;border-radius:8px;padding:13px;font-size:1rem;font-weight:700;cursor:pointer;margin-top:8px;letter-spacing:.5px}
        .btn-auth:hover{background:#c1121f}
        .auth-errors{background:#2a0a0a;border:1px solid #e63946;border-radius:8px;padding:12px 16px;margin-bottom:20px}
        .auth-errors p{color:#ff6b6b;font-size:.85rem;margin:4px 0}
        .flash-success{background:#0a2a0a;border:1px solid #2d6a2d;border-radius:8px;padding:12px 16px;margin-bottom:20px;color:#6fcf97;font-size:.85rem}
        .auth-switch{text-align:center;margin-top:20px;color:#888;font-size:.9rem}
        .auth-switch a{color:#e63946;text-decoration:none;font-weight:600}
    </style> -->
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-logo"><img src="../assets/images/Light_Logo.png" alt="Proburst"></div>
        <h2>Welcome Back</h2>
        <p class="sub">Login to your Proburst account</p>

        <?php if ($flash && $flash['type'] === 'success'): ?>
            <div class="flash-success">✅ <?= htmlspecialchars($flash['message']) ?></div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="auth-errors">
                <?php foreach ($errors as $e): ?><p>⚠ <?= $e ?></p><?php endforeach; ?>
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
            <div class="forgot"><a href="#">Forgot password?</a></div>
            <button type="submit" class="btn-auth">LOGIN →</button>
        </form>

        <p class="auth-switch">New to Proburst? <a href="register.php">Create account</a></p>
    </div>
</div>
</body>
</html>
