<?php
// pages/forgot-password.php — Production ready

if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/database.php';
require_once '../config/mailer.php';
require_once '../models/User.php';
require_once '../includes/auth.php';

if (isLoggedIn()) { header('Location: /proburst/index.php'); exit; }

$userModel = new User($conn);
$errors    = [];
$success   = '';
$devLink   = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } else {
        $token = $userModel->createResetToken($email);
        $user  = $userModel->findByEmail($email);

        if ($token && $user) {
            $resetLink = rtrim(SITE_URL, '/') . '/pages/reset-password.php?token=' . urlencode($token);
            $toName    = (string)($user['name'] ?? 'Customer');

            $result = sendPasswordResetEmail($email, $toName, $resetLink);

            if ($result === true) {
                // Live server — email sent successfully
                $success = 'A password reset link has been sent to your email address.';
            } elseif (is_array($result) && !empty($result['dev'])) {
                // Dev/localhost mode — show link on screen
                $devLink = $result['link'];
                $success = 'Dev mode: Reset link generated (shown below).';
            } else {
                // SMTP error — log it but don't expose to user
                error_log('[Proburst Mailer Error] ' . print_r($result, true));
                // Still show success to user for security (don't expose internals)
                $success = 'If that email is registered, a reset link has been sent.';
            }
        } else {
            // Email not found — show same message (prevent email enumeration)
            $success = 'If that email is registered, a reset link has been sent.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — Proburst</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .hint {
            font-size: .82rem; color: #666;
            margin-bottom: 20px; line-height: 1.6;
        }
        .dev-box {
            background: #1a1200; border: 1px dashed #e0a000;
            border-radius: 10px; padding: 16px 18px; margin-top: 16px;
        }
        .dev-box strong {
            display: block; color: #e0a000;
            font-size: .85rem; margin-bottom: 10px;
        }
        .dev-box a {
            color: #f4c430; font-size: .82rem;
            word-break: break-all; line-height: 1.6;
        }
        .dev-note {
            margin-top: 10px; font-size: .75rem;
            color: #6b5200; line-height: 1.5;
        }
        .back-link {
            display: block; text-align: center;
            margin-top: 22px; color: #666;
            font-size: .88rem; text-decoration: none;
        }
        .back-link:hover { color: #e63946; }
    </style>
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-logo">
            <img src="../assets/images/Light_Logo.png" alt="Proburst">
        </div>

        <h2>Forgot Password?</h2>
        <p class="sub">Enter your registered email to receive a reset link.</p>

        <?php if (!empty($errors)): ?>
            <div class="auth-errors">
                <?php foreach ($errors as $e): ?>
                    <p>⚠ <?= htmlspecialchars((string)$e) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="flash-success">✅ <?= htmlspecialchars($success) ?></div>

            <?php if ($devLink): ?>
                <div class="dev-box">
                    <strong>🛠 Dev Mode — Click below to reset password:</strong>
                    <a href="<?= htmlspecialchars($devLink) ?>">
                        <?= htmlspecialchars($devLink) ?>
                    </a>
                    <div class="dev-note">
                        This box only appears on localhost.<br>
                        On live server, this link is emailed automatically.
                    </div>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <form method="POST" novalidate>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email"
                           placeholder="you@email.com"
                           value="<?= htmlspecialchars((string)($_POST['email'] ?? '')) ?>"
                           required autofocus>
                </div>
                <p class="hint">
                    We'll send a password reset link valid for
                    <strong style="color:#ccc">1 hour</strong>.
                </p>
                <button type="submit" class="btn-auth">SEND RESET LINK →</button>
            </form>
        <?php endif; ?>

        <a href="login.php" class="back-link">← Back to Login</a>
    </div>
</div>
</body>
</html>
