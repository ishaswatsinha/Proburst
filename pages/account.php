<?php
require_once '../config/pdo.php';
require_once '../models/User.php';
require_once '../includes/auth.php';

requireLogin();

$userModel = new User($pdo);
$user      = $userModel->findById((int)$_SESSION['user_id']);
$errors    = [];
$success   = '';

if (!$user || !is_array($user)) {
    logoutUser();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'update_profile') {
        $name  = trim($_POST['name']  ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (empty($name))                         $errors[] = 'Name cannot be empty.';
        if (!preg_match('/^[0-9]{10}$/', $phone)) $errors[] = 'Enter a valid 10-digit phone.';

        if (empty($errors)) {
            $userModel->updateProfile($user['id'], $name, $phone);
            $_SESSION['user_name'] = $name;
            // ✅ KEY FIX: update $user in place — do NOT re-fetch (second findById returns false)
            $user['name']  = $name;
            $user['phone'] = $phone;
            $success = 'Profile updated successfully!';
        }
    }

    if ($_POST['action'] === 'change_password') {
        $current = trim($_POST['current_password'] ?? '');
        $new     = trim($_POST['new_password']     ?? '');
        $confirm = trim($_POST['confirm_password'] ?? '');

        if (!password_verify($current, $user['password'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($new) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }

        if (empty($errors)) {
            $userModel->updatePassword((int)$user['id'], $new);
            // ✅ KEY FIX: update in place — do NOT re-fetch
            $user['password'] = password_hash($new, PASSWORD_BCRYPT);
            $success = 'Password changed successfully!';
        }
    }
}

$avatarInitial = strtoupper(mb_substr($user['name'] ?? 'U', 0, 1));
$memberSince   = !empty($user['created_at']) ? date('M Y', strtotime($user['created_at'])) : '';
$flash         = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account — Proburst</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .account-wrapper { max-width: 760px; margin: 60px auto; padding: 0 20px 60px; }
        .account-header { display: flex; align-items: center; gap: 20px; margin-bottom: 40px; }
        .avatar {
            width: 72px; height: 72px; border-radius: 50%; background: #e63946;
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem; color: #fff; font-weight: 700; flex-shrink: 0;
        }
        .account-header h2 { color: #fff; margin: 0; font-size: 1.5rem; }
        .account-header p  { color: #888; margin: 4px 0 0; font-size: 0.9rem; }
        .card { background: #111; border: 1px solid #222; border-radius: 16px; padding: 28px 32px; margin-bottom: 24px; }
        .card h3 { color: #fff; font-size: 1.1rem; margin-top: 0; margin-bottom: 20px; border-bottom: 1px solid #222; padding-bottom: 12px; }
        .form-row { display: flex; gap: 16px; }
        .form-row .form-group { flex: 1; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; color: #ccc; font-size: 0.85rem; margin-bottom: 6px; }
        .form-group input {
            width: 100%; background: #1a1a1a; border: 1px solid #333; border-radius: 8px;
            padding: 11px 14px; color: #fff; font-size: 0.95rem; box-sizing: border-box; transition: border 0.2s;
        }
        .form-group input:focus { outline: none; border-color: #e63946; }
        .form-group input[readonly] { opacity: 0.5; cursor: not-allowed; }
        .btn-save { background: #e63946; color: #fff; border: none; border-radius: 8px; padding: 11px 24px; font-size: 0.95rem; font-weight: 700; cursor: pointer; transition: background 0.2s; }
        .btn-save:hover { background: #c1121f; }
        .alert { border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; font-size: 0.9rem; }
        .alert-error   { background: #2a0a0a; border: 1px solid #e63946; color: #ff6b6b; }
        .alert-success { background: #0a2a0a; border: 1px solid #2d6a2d; color: #6fcf97; }
        .logout-link { display: inline-block; color: #888; font-size: 0.9rem; text-decoration: none; padding: 8px 0; }
        .logout-link:hover { color: #e63946; }
        @media (max-width: 600px) { .form-row { flex-direction: column; gap: 0; } .card { padding: 20px 18px; } }
    </style>
</head>
<body>

<?php include '../includes/navbar.php'; ?>

<div class="account-wrapper">

    <div class="account-header">
        <div class="avatar"><?= $avatarInitial ?></div>
        <div>
            <h2><?= htmlspecialchars($user['name']) ?></h2>
            <p>
                <?= htmlspecialchars($user['email']) ?>
                <?= $memberSince ? ' · Member since ' . $memberSince : '' ?>
            </p>
        </div>
    </div>

    <?php if ($flash && $flash['type'] === 'success'): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $e): ?>
                <p style="margin:3px 0">⚠ <?= htmlspecialchars($e) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h3>👤 Personal Information</h3>
        <form method="POST">
            <input type="hidden" name="action" value="update_profile">
            <div class="form-row">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" maxlength="10">
                </div>
            </div>
            <div class="form-group">
                <label>Email Address (cannot be changed)</label>
                <input type="email" value="<?= htmlspecialchars($user['email']) ?>" readonly>
            </div>
            <button type="submit" class="btn-save">Save Changes</button>
        </form>
    </div>

    <div class="card">
        <h3>🔑 Change Password</h3>
        <form method="POST">
            <input type="hidden" name="action" value="change_password">
            <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="current_password" placeholder="Enter current password" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" placeholder="Min. 8 characters" required>
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" placeholder="Re-enter new password" required>
                </div>
            </div>
            <button type="submit" class="btn-save">Update Password</button>
        </form>
    </div>

    <div class="card" style="text-align:center">
        <a href="logout.php" class="logout-link">🚪 Logout from this account</a>
    </div>

</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>