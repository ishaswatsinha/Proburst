<?php

if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/database.php'; // $conn (mysqli)
require_once '../models/User.php';
require_once '../includes/auth.php';

requireLogin();

$uid     = (int)$_SESSION['user_id'];
$userModel = new User($conn);
$user    = $userModel->findById($uid);
$errors  = [];
$success = '';

if (!$user) { logoutUser(); exit; }

// ---- POST handlers ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'update_profile') {
        $name  = trim($_POST['name']  ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (empty($name))                         $errors[] = 'Name cannot be empty.';
        if (!preg_match('/^[0-9]{10}$/', $phone)) $errors[] = 'Enter a valid 10-digit phone number.';

        if (empty($errors)) {
            $userModel->updateProfile($uid, $name, $phone);
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
            $userModel->updatePassword($uid, $new);
            $user['password'] = password_hash($new, PASSWORD_BCRYPT);
            $success = 'Password changed successfully!';
        }
    }
}

// Orders for this user
$orders = [];
$oRes = $conn->query("SELECT * FROM orders WHERE user_id = $uid ORDER BY created_at DESC");
if ($oRes) while ($r = $oRes->fetch_assoc()) $orders[] = $r;

$initial     = strtoupper(mb_substr($user['name'], 0, 1));
$memberSince = !empty($user['created_at']) ? date('M Y', strtotime($user['created_at'])) : '';
$flash       = getFlash();

// Which tab to show on load
$activeTab = 'profile';
if (!empty($errors) && ($_POST['action'] ?? '') === 'change_password') $activeTab = 'password';
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
        body { background: #0d0d0d; }
        .acc-wrap  { max-width:820px; margin:40px auto; padding:0 20px 80px; }
        .acc-head  { display:flex; align-items:center; gap:20px; margin-bottom:36px; }
        .acc-av    { width:72px; height:72px; border-radius:50%; background:#e63946;
                     display:flex; align-items:center; justify-content:center;
                     font-size:2rem; color:#fff; font-weight:700; flex-shrink:0; }
        .acc-head h2 { color:#fff; margin:0; font-size:1.5rem; }
        .acc-head p  { color:#888; margin:4px 0 0; font-size:.88rem; }

        .tabs       { display:flex; border-bottom:1px solid #222; margin-bottom:28px; }
        .tab-btn    { background:none; border:none; color:#777; font-size:.9rem;
                      padding:11px 20px; cursor:pointer; border-bottom:2px solid transparent;
                      margin-bottom:-1px; font-family:inherit; transition:color .2s; }
        .tab-btn.active { color:#e63946; border-bottom-color:#e63946; font-weight:600; }

        .tab-pane   { display:none; }
        .tab-pane.active { display:block; }

        .card       { background:#111; border:1px solid #1e1e1e; border-radius:14px;
                      padding:28px 30px; margin-bottom:22px; }
        .card h3    { color:#fff; font-size:1rem; margin:0 0 20px;
                      padding-bottom:12px; border-bottom:1px solid #1e1e1e; }

        .frow       { display:flex; gap:16px; }
        .frow .fg   { flex:1; }
        .fg         { margin-bottom:16px; }
        .fg label   { display:block; color:#aaa; font-size:.8rem; margin-bottom:6px; }
        .fg input   { width:100%; background:#181818; border:1px solid #2a2a2a;
                      border-radius:8px; padding:11px 14px; color:#fff; font-size:.93rem;
                      box-sizing:border-box; transition:border .2s; }
        .fg input:focus   { outline:none; border-color:#e63946; }
        .fg input[readonly]{ opacity:.4; cursor:not-allowed; }

        .btn-save   { background:#e63946; color:#fff; border:none; border-radius:8px;
                      padding:11px 26px; font-size:.93rem; font-weight:700; cursor:pointer; }
        .btn-save:hover { background:#c1121f; }

        .alert      { border-radius:8px; padding:12px 16px; margin-bottom:20px; font-size:.88rem; }
        .alert-err  { background:#200808; border:1px solid #e63946; color:#ff8080; }
        .alert-ok   { background:#082008; border:1px solid #2d6a2d; color:#7ecf7e; }

        /* orders */
        .otable     { width:100%; border-collapse:collapse; font-size:.87rem; }
        .otable th  { color:#666; text-align:left; padding:9px 12px;
                      border-bottom:1px solid #1e1e1e; font-weight:500; }
        .otable td  { color:#ccc; padding:12px; border-bottom:1px solid #161616; }
        .otable tr:hover td { background:#141414; }
        .badge      { display:inline-block; padding:3px 11px; border-radius:20px;
                      font-size:.75rem; font-weight:600; }
        .b-pending  { background:#1e1500; color:#e0a000; }
        .b-shipped  { background:#001a14; color:#00c896; }
        .b-delivered{ background:#071407; color:#6fcf97; }
        .no-orders  { color:#444; text-align:center; padding:44px 0; font-size:.93rem; }

        .logout-lnk { color:#555; font-size:.88rem; text-decoration:none; }
        .logout-lnk:hover { color:#e63946; }

        @media(max-width:580px){
            .frow { flex-direction:column; gap:0; }
            .card { padding:20px 16px; }
        }
    </style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="acc-wrap">

    <!-- Header -->
    <div class="acc-head">
        <div class="acc-av"><?= $initial ?></div>
        <div>
            <h2><?= htmlspecialchars($user['name']) ?></h2>
            <p><?= htmlspecialchars($user['email']) ?>
               <?= $memberSince ? ' · Member since ' . $memberSince : '' ?></p>
        </div>
    </div>

    <!-- Alerts -->
    <?php if ($flash && $flash['type'] === 'success'): ?>
        <div class="alert alert-ok">✅ <?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-ok">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-err">
            <?php foreach ($errors as $e): ?>
                <div>⚠ <?= htmlspecialchars($e) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="tabs">
        <button class="tab-btn <?= $activeTab==='profile'  ? 'active':'' ?>" onclick="tab('profile',this)">👤 Profile</button>
        <button class="tab-btn <?= $activeTab==='orders'   ? 'active':'' ?>" onclick="tab('orders',this)">📦 My Orders</button>
        <button class="tab-btn <?= $activeTab==='password' ? 'active':'' ?>" onclick="tab('password',this)">🔑 Password</button>
    </div>

    <!-- PROFILE -->
    <div class="tab-pane <?= $activeTab==='profile' ? 'active':'' ?>" id="pane-profile">
        <div class="card">
            <h3>Personal Information</h3>
            <form method="POST">
                <input type="hidden" name="action" value="update_profile">
                <div class="frow">
                    <div class="fg">
                        <label>Full Name</label>
                        <input type="text" name="name"
                               value="<?= htmlspecialchars($user['name']) ?>" required>
                    </div>
                    <div class="fg">
                        <label>Phone Number</label>
                        <input type="tel" name="phone"
                               value="<?= htmlspecialchars($user['phone'] ?? '') ?>" maxlength="10">
                    </div>
                </div>
                <div class="fg">
                    <label>Email (cannot be changed)</label>
                    <input type="email" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                </div>
                <button type="submit" class="btn-save">Save Changes</button>
            </form>
        </div>
        <div class="card" style="text-align:center;padding:18px">
            <a href="logout.php" class="logout-lnk">🚪 Logout from this account</a>
        </div>
    </div>

    <!-- ORDERS -->
    <div class="tab-pane <?= $activeTab==='orders' ? 'active':'' ?>" id="pane-orders">
        <div class="card">
            <h3>My Orders</h3>
            <?php if (empty($orders)): ?>
                <div class="no-orders">
                    No orders placed yet.<br>
                    <a href="/proburst/pages/shop.php" style="color:#e63946;margin-top:8px;display:inline-block">Start Shopping →</a>
                </div>
            <?php else: ?>
                <table class="otable">
                    <thead>
                        <tr><th>Order #</th><th>Date</th><th>Total</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($orders as $o):
                        $s   = $o['status'] ?? 'pending';
                        $cls = match($s) { 'shipped'=>'b-shipped','delivered'=>'b-delivered', default=>'b-pending' };
                    ?>
                        <tr>
                            <td>#<?= $o['id'] ?></td>
                            <td><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                            <td>₹<?= number_format($o['total']) ?></td>
                            <td><span class="badge <?= $cls ?>"><?= ucfirst($s) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- PASSWORD -->
    <div class="tab-pane <?= $activeTab==='password' ? 'active':'' ?>" id="pane-password">
        <div class="card">
            <h3>Change Password</h3>
            <form method="POST">
                <input type="hidden" name="action" value="change_password">
                <div class="fg">
                    <label>Current Password</label>
                    <input type="password" name="current_password"
                           placeholder="Enter current password" required>
                </div>
                <div class="frow">
                    <div class="fg">
                        <label>New Password</label>
                        <input type="password" name="new_password"
                               placeholder="Min. 8 characters" required>
                    </div>
                    <div class="fg">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password"
                               placeholder="Re-enter" required>
                    </div>
                </div>
                <button type="submit" class="btn-save">Update Password</button>
            </form>
        </div>
    </div>

</div><!-- /acc-wrap -->

<script>
function tab(name, btn) {
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('pane-' + name).classList.add('active');
    btn.classList.add('active');
}
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
