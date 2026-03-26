<?php
// pages/account.php — FINAL VERSION
// Uses $conn (mysqli) directly — avoids all PDO/scope issues completely

if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../includes/auth.php';
require_once '../config/database.php'; // gives $conn (mysqli)

requireLogin();

$uid    = (int)$_SESSION['user_id'];
$errors = [];
$success = '';

// Fetch user using mysqli — no PDO needed
$user = $conn->query("SELECT * FROM users WHERE id = $uid LIMIT 1")->fetch_assoc();

if (!$user) {
    logoutUser();
    exit;
}

// ---- Handle POST ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'update_profile') {
        $name  = trim($_POST['name']  ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (empty($name))                         $errors[] = 'Name cannot be empty.';
        if (!preg_match('/^[0-9]{10}$/', $phone)) $errors[] = 'Enter a valid 10-digit phone.';

        if (empty($errors)) {
            $stmt = $conn->prepare("UPDATE users SET name=?, phone=? WHERE id=?");
            $stmt->bind_param('ssi', $name, $phone, $uid);
            $stmt->execute();
            $stmt->close();

            $_SESSION['user_name'] = $name;
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
            $hashed = password_hash($new, PASSWORD_BCRYPT);
            $stmt   = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->bind_param('si', $hashed, $uid);
            $stmt->execute();
            $stmt->close();
            $user['password'] = $hashed;
            $success = 'Password changed successfully!';
        }
    }
}

// Fetch orders
$orders = [];
$oRes = $conn->query("SELECT * FROM orders WHERE user_id = $uid ORDER BY created_at DESC");
if ($oRes) while ($row = $oRes->fetch_assoc()) $orders[] = $row;

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .account-wrapper{max-width:820px;margin:40px auto;padding:0 20px 80px}
        .account-header{display:flex;align-items:center;gap:20px;margin-bottom:36px}
        .avatar{width:72px;height:72px;border-radius:50%;background:#e63946;display:flex;align-items:center;justify-content:center;font-size:2rem;color:#fff;font-weight:700;flex-shrink:0}
        .account-header h2{color:#fff;margin:0;font-size:1.5rem}
        .account-header p{color:#888;margin:4px 0 0;font-size:.9rem}
        .tabs{display:flex;gap:4px;margin-bottom:28px;border-bottom:1px solid #222}
        .tab-btn{background:none;border:none;color:#888;font-size:.9rem;padding:10px 18px;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-1px;font-family:inherit}
        .tab-btn.active{color:#e63946;border-bottom-color:#e63946;font-weight:600}
        .tab-panel{display:none}
        .tab-panel.active{display:block}
        .card{background:#111;border:1px solid #222;border-radius:16px;padding:28px 32px;margin-bottom:24px}
        .card h3{color:#fff;font-size:1.05rem;margin-top:0;margin-bottom:20px;border-bottom:1px solid #222;padding-bottom:12px}
        .form-row{display:flex;gap:16px}
        .form-row .form-group{flex:1}
        .form-group{margin-bottom:16px}
        .form-group label{display:block;color:#ccc;font-size:.83rem;margin-bottom:6px}
        .form-group input{width:100%;background:#1a1a1a;border:1px solid #333;border-radius:8px;padding:11px 14px;color:#fff;font-size:.95rem;box-sizing:border-box;transition:border .2s}
        .form-group input:focus{outline:none;border-color:#e63946}
        .form-group input[readonly]{opacity:.45;cursor:not-allowed}
        .btn-save{background:#e63946;color:#fff;border:none;border-radius:8px;padding:11px 24px;font-size:.95rem;font-weight:700;cursor:pointer}
        .btn-save:hover{background:#c1121f}
        .alert{border-radius:8px;padding:12px 16px;margin-bottom:20px;font-size:.88rem}
        .alert-error{background:#2a0a0a;border:1px solid #e63946;color:#ff6b6b}
        .alert-success{background:#0a2a0a;border:1px solid #2d6a2d;color:#6fcf97}
        .logout-link{color:#888;font-size:.9rem;text-decoration:none}
        .logout-link:hover{color:#e63946}
        .orders-table{width:100%;border-collapse:collapse;font-size:.88rem}
        .orders-table th{color:#888;text-align:left;padding:10px 12px;border-bottom:1px solid #222;font-weight:500}
        .orders-table td{color:#ddd;padding:12px;border-bottom:1px solid #1a1a1a}
        .orders-table tr:hover td{background:#161616}
        .badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:.78rem;font-weight:600}
        .badge-pending{background:#2a2000;color:#f0a500}
        .badge-shipped{background:#002a20;color:#00c896}
        .badge-delivered{background:#0a2a0a;color:#6fcf97}
        .no-orders{color:#555;text-align:center;padding:40px 0;font-size:.95rem}
        @media(max-width:600px){.form-row{flex-direction:column;gap:0}.card{padding:20px 16px}}
    </style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="account-wrapper">

    <div class="account-header">
        <div class="avatar"><?= $avatarInitial ?></div>
        <div>
            <h2><?= htmlspecialchars($user['name']) ?></h2>
            <p><?= htmlspecialchars($user['email']) ?><?= $memberSince ? ' · Member since '.$memberSince : '' ?></p>
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
            <?php foreach ($errors as $e): ?><p style="margin:3px 0">⚠ <?= htmlspecialchars($e) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="tabs">
        <button class="tab-btn active" onclick="switchTab('profile',this)">👤 Profile</button>
        <button class="tab-btn" onclick="switchTab('orders',this)">📦 My Orders</button>
        <button class="tab-btn" onclick="switchTab('password',this)">🔑 Password</button>
    </div>

    <!-- PROFILE -->
    <div class="tab-panel active" id="tab-profile">
        <div class="card">
            <h3>Personal Information</h3>
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
                    <label>Email (cannot be changed)</label>
                    <input type="email" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                </div>
                <button type="submit" class="btn-save">Save Changes</button>
            </form>
        </div>
        <div class="card" style="text-align:center">
            <a href="logout.php" class="logout-link">🚪 Logout from this account</a>
        </div>
    </div>

    <!-- ORDERS -->
    <div class="tab-panel" id="tab-orders">
        <div class="card">
            <h3>My Orders</h3>
            <?php if (empty($orders)): ?>
                <p class="no-orders">No orders yet. <a href="/proburst/pages/shop.php" style="color:#e63946">Start Shopping →</a></p>
            <?php else: ?>
                <table class="orders-table">
                    <thead><tr><th>Order #</th><th>Date</th><th>Total</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td>#<?= $o['id'] ?></td>
                            <td><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                            <td>₹<?= number_format($o['total']) ?></td>
                            <td>
                                <?php $s = $o['status'] ?? 'pending';
                                $cls = $s === 'shipped' ? 'badge-shipped' : ($s === 'delivered' ? 'badge-delivered' : 'badge-pending'); ?>
                                <span class="badge <?= $cls ?>"><?= ucfirst($s) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- PASSWORD -->
    <div class="tab-panel" id="tab-password">
        <div class="card">
            <h3>Change Password</h3>
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
                        <input type="password" name="confirm_password" placeholder="Re-enter" required>
                    </div>
                </div>
                <button type="submit" class="btn-save">Update Password</button>
            </form>
        </div>
    </div>

</div>

<script>
function switchTab(name, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    btn.classList.add('active');
}
<?php if (!empty($errors) && ($_POST['action'] ?? '') === 'change_password'): ?>
switchTab('password', document.querySelectorAll('.tab-btn')[2]);
<?php endif; ?>
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
