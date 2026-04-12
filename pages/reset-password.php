<?php
// pages/reset-password.php
// Compatible with PHP 7.2+ and PHP 8.x

if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../includes/auth.php';

if (isLoggedIn()) { header('Location: /proburst/index.php'); exit; }

$userModel    = new User($conn);
$token        = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
$errors       = [];
$tokenInvalid = false;

// Validate token on GET
if ($token) {
    $validUser = $userModel->findByResetToken($token);
    if (!$validUser) $tokenInvalid = true;
} else {
    $tokenInvalid = true;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$tokenInvalid) {
    $new     = (string)($_POST['new_password']     ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    // Re-validate token on POST
    $validUser = $userModel->findByResetToken($token);
    if (!$validUser) {
        $tokenInvalid = true;
    } elseif (strlen($new) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    } elseif ($new !== $confirm) {
        $errors[] = 'Passwords do not match.';
    } else {
        $ok = $userModel->resetPasswordByToken($token, $new);
        if ($ok) {
            setFlash('success', 'Password reset successfully! Please log in.');
            header('Location: /proburst/pages/login.php');
            exit;
        } else {
            $errors[] = 'Something went wrong. Please request a new reset link.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — Proburst</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .pw-wrap       { position:relative; }
        .pw-wrap input { padding-right:44px !important; }
        .pw-eye {
            position:absolute; right:12px; top:50%; transform:translateY(-50%);
            background:none; border:none; color:#666; cursor:pointer; font-size:.95rem; padding:0;
        }
        .pw-eye:hover { color:#e63946; }
        .pw-strength     { height:4px; border-radius:2px; background:#2a2a2a; margin-top:7px; overflow:hidden; }
        .pw-strength-bar { height:100%; width:0; border-radius:2px; transition:width .3s,background .3s; }
        .pw-strength-lbl { font-size:.74rem; color:#666; margin-top:4px; }
        .expired-box { text-align:center; padding:10px 0 20px; }
        .expired-box .icon { font-size:3rem; margin-bottom:12px; display:block; }
        .expired-box p { color:#888; font-size:.9rem; margin-bottom:20px; line-height:1.6; }
        .back-link { display:block; text-align:center; margin-top:20px; color:#666; font-size:.88rem; text-decoration:none; }
        .back-link:hover { color:#e63946; }
    </style>
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-logo">
            <img src="../assets/images/Light_Logo.png" alt="Proburst">
        </div>

        <?php if ($tokenInvalid): ?>
            <!-- Expired / Invalid token -->
            <div class="expired-box">
                <span class="icon">🔗</span>
                <h2>Link Expired</h2>
                <p>
                    This reset link is invalid or has already expired.<br>
                    Reset links are valid for <strong style="color:#ccc">1 hour</strong>.
                </p>
                <a href="forgot-password.php" class="btn-auth"
                   style="display:inline-block;text-decoration:none;padding:13px 24px;width:auto">
                    Request New Link →
                </a>
            </div>

        <?php else: ?>
            <h2>Set New Password</h2>
            <p class="sub">Choose a strong new password for your account.</p>

            <?php if (!empty($errors)): ?>
                <div class="auth-errors">
                    <?php foreach ($errors as $e): ?>
                        <p>⚠ <?= htmlspecialchars((string)$e) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <div class="form-group">
                    <label>New Password</label>
                    <div class="pw-wrap">
                        <input type="password" name="new_password" id="newPwd"
                               placeholder="Min. 8 characters" required autofocus
                               oninput="checkStr(this.value)">
                        <button type="button" class="pw-eye" onclick="tog('newPwd',this)">👁</button>
                    </div>
                    <div class="pw-strength"><div class="pw-strength-bar" id="sBar"></div></div>
                    <div class="pw-strength-lbl" id="sLbl"></div>
                </div>

                <div class="form-group">
                    <label>Confirm New Password</label>
                    <div class="pw-wrap">
                        <input type="password" name="confirm_password" id="conPwd"
                               placeholder="Re-enter password" required>
                        <button type="button" class="pw-eye" onclick="tog('conPwd',this)">👁</button>
                    </div>
                </div>

                <button type="submit" class="btn-auth">RESET PASSWORD →</button>
            </form>
        <?php endif; ?>

        <a href="login.php" class="back-link">← Back to Login</a>
    </div>
</div>
<script>
function tog(id, btn) {
    var inp = document.getElementById(id);
    inp.type = inp.type === 'password' ? 'text' : 'password';
    btn.textContent = inp.type === 'password' ? '👁' : '🙈';
}
function checkStr(v) {
    var b = document.getElementById('sBar');
    var l = document.getElementById('sLbl');
    if (!b) return;
    var s = 0;
    if (v.length >= 8)           s++;
    if (/[A-Z]/.test(v))         s++;
    if (/[0-9]/.test(v))         s++;
    if (/[^A-Za-z0-9]/.test(v))  s++;
    var c=['#e63946','#ff6a00','#e0a000','#4caf82'];
    var t=['Weak','Fair','Good','Strong'];
    var w=['25%','50%','75%','100%'];
    if(!v){b.style.width='0';l.textContent='';return;}
    var i=Math.max(0,Math.min(s-1,3));
    b.style.width=w[i];b.style.background=c[i];
    l.textContent=t[i];l.style.color=c[i];
}
</script>
</body>
</html>
