<?php
// pages/account.php
// Compatible with PHP 7.2+ and PHP 8.x

if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../includes/auth.php';

requireLogin();

$uid       = (int)$_SESSION['user_id'];
$userModel = new User($conn);
$user      = $userModel->findById($uid);
$errors    = [];
$success   = '';
$activeTab = 'profile';

// Defensive guard — if $user is not a valid array, log out
if (!is_array($user) || empty($user['email'])) {
    logoutUser();
    exit;
}

// ── POST handlers ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'update_profile') {
        $name  = trim((string)($_POST['name']  ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $activeTab = 'profile';
        if ($name === '')                             $errors[] = 'Name cannot be empty.';
        if (!preg_match('/^[0-9]{10}$/', $phone))    $errors[] = 'Enter a valid 10-digit phone number.';
        if (empty($errors)) {
            $userModel->updateProfile($uid, $name, $phone);
            $_SESSION['user_name'] = $name;
            $user['name']  = $name;
            $user['phone'] = $phone;
            $success = 'Profile updated successfully!';
        }
    }

    if ($_POST['action'] === 'change_password') {
        $current = (string)($_POST['current_password'] ?? '');
        $new     = (string)($_POST['new_password']     ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');
        $activeTab = 'password';
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

// ── Orders — 2 queries total, no N+1 ─────────────────────────────────
$orders = [];
$oRes = $conn->query("SELECT * FROM orders WHERE user_id = " . $uid . " ORDER BY created_at DESC");
if ($oRes) {
    while ($r = $oRes->fetch_assoc()) {
        $orders[] = $r;
    }
}

$orderItems = [];
if (!empty($orders)) {
    $ids  = array_map('intval', array_column($orders, 'id'));
    $inSql = implode(',', $ids);
    $iRes = $conn->query("
        SELECT oi.order_id, oi.qty, oi.price,
               p.name AS product_name, p.image AS product_image, p.slug AS product_slug
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id IN ($inSql)
    ");
    if ($iRes) {
        while ($row = $iRes->fetch_assoc()) {
            $orderItems[(int)$row['order_id']][] = $row;
        }
    }
}

// ── View helpers ──────────────────────────────────────────────────────
$initial     = strtoupper(mb_substr((string)($user['name'] ?? '?'), 0, 1));
$memberSince = !empty($user['created_at'])
    ? date('M Y', strtotime($user['created_at'])) : '';
$flash = getFlash();

function statusBadge($s) {
    $map = [
        'shipped'    => 'b-shipped',
        'delivered'  => 'b-delivered',
        'cancelled'  => 'b-cancelled',
        'processing' => 'b-processing',
    ];
    return isset($map[$s]) ? $map[$s] : 'b-pending';
}
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
        .acc-wrap { max-width: 840px; margin: 40px auto; padding: 0 20px 80px; }

        /* ── Header ── */
        .acc-head { display: flex; align-items: center; gap: 20px; margin-bottom: 36px; }
        .acc-av {
            width: 72px; height: 72px; border-radius: 50%;
            background: linear-gradient(135deg, #e63946, #c1121f);
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem; color: #fff; font-weight: 700; flex-shrink: 0;
            box-shadow: 0 4px 16px rgba(230,57,70,.4);
        }
        .acc-head h2 { color: #fff; margin: 0; font-size: 1.5rem; }
        .acc-head p  { color: #666; margin: 4px 0 0; font-size: .88rem; }

        /* ── Tabs ── */
        .tabs     { display: flex; border-bottom: 1px solid #222; margin-bottom: 28px; }
        .tab-btn  {
            background: none; border: none; color: #666; font-size: .88rem;
            padding: 11px 20px; cursor: pointer; border-bottom: 2px solid transparent;
            margin-bottom: -1px; font-family: inherit; transition: color .2s;
        }
        .tab-btn:hover  { color: #ccc; }
        .tab-btn.active { color: #e63946; border-bottom-color: #e63946; font-weight: 700; }
        .tab-pane         { display: none; }
        .tab-pane.active  { display: block; }

        /* ── Cards ── */
        .card    { background: #111; border: 1px solid #1e1e1e; border-radius: 14px; padding: 28px 30px; margin-bottom: 22px; }
        .card h3 { color: #fff; font-size: 1rem; margin: 0 0 22px; padding-bottom: 14px; border-bottom: 1px solid #1e1e1e; }

        /* ── Form ── */
        .frow     { display: flex; gap: 16px; }
        .frow .fg { flex: 1; }
        .fg       { margin-bottom: 16px; }
        .fg label { display: block; color: #999; font-size: .8rem; margin-bottom: 6px; font-weight: 500; }
        .fg input {
            width: 100%; background: #181818; border: 1px solid #2a2a2a;
            border-radius: 8px; padding: 11px 14px; color: #fff; font-size: .93rem;
            box-sizing: border-box; transition: border .2s;
        }
        .fg input:focus     { outline: none; border-color: #e63946; }
        .fg input[readonly] { opacity: .4; cursor: not-allowed; }

        /* Password wrapper */
        .pw-wrap        { position: relative; }
        .pw-wrap input  { padding-right: 44px !important; }
        .pw-eye {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: #555; cursor: pointer;
            font-size: .95rem; padding: 0; line-height: 1;
        }
        .pw-eye:hover { color: #e63946; }

        /* Strength bar */
        .pw-bar-wrap { height: 3px; background: #222; border-radius: 2px; margin-top: 6px; overflow: hidden; }
        .pw-bar      { height: 100%; width: 0; border-radius: 2px; transition: width .3s, background .3s; }
        .pw-bar-lbl  { font-size: .74rem; color: #555; margin-top: 4px; min-height: 14px; }

        /* ── Alerts ── */
        .alert     { border-radius: 8px; padding: 13px 16px; margin-bottom: 20px; font-size: .88rem; }
        .alert-err { background: #200808; border: 1px solid #e63946; color: #ff8080; }
        .alert-ok  { background: #082008; border: 1px solid #2d6a2d; color: #7ecf7e; }

        /* ── Buttons ── */
        .btn-save {
            background: #e63946; color: #fff; border: none; border-radius: 8px;
            padding: 11px 28px; font-size: .93rem; font-weight: 700;
            cursor: pointer; transition: background .2s; margin-top: 4px;
        }
        .btn-save:hover { background: #c1121f; }

        /* ── Orders ── */
        .no-orders { color: #444; text-align: center; padding: 44px 0; font-size: .93rem; }
        .no-orders a { color: #e63946; display: inline-block; margin-top: 8px; }

        .order-block {
            background: #161616; border: 1px solid #222; border-radius: 12px;
            margin-bottom: 16px; overflow: hidden; transition: border-color .2s;
        }
        .order-block:hover { border-color: #333; }

        .order-block-head {
            display: flex; justify-content: space-between; align-items: center;
            padding: 14px 18px; border-bottom: 1px solid #1e1e1e;
            flex-wrap: wrap; gap: 8px; background: #111;
        }
        .ob-num   { color: #fff; font-weight: 700; font-size: .93rem; margin-right: 10px; }
        .ob-date  { color: #555; font-size: .80rem; }
        .ob-total { color: #e63946; font-weight: 800; font-size: .97rem; }

        .badge        { display: inline-block; padding: 3px 11px; border-radius: 20px; font-size: .75rem; font-weight: 600; }
        .b-pending    { background: #1e1500; color: #e0a000; }
        .b-processing { background: #0d1a2e; color: #5ba4f5; }
        .b-shipped    { background: #001a14; color: #00c896; }
        .b-delivered  { background: #071407; color: #6fcf97; }
        .b-cancelled  { background: #200808; color: #e63946; }

        .order-items-list { padding: 12px 18px; display: flex; flex-direction: column; gap: 10px; }
        .oi-row { display: flex; align-items: center; gap: 12px; }
        .oi-img { width: 50px; height: 50px; object-fit: contain; border-radius: 8px; background: #1e1e1e; padding: 4px; flex-shrink: 0; border: 1px solid #2a2a2a; }
        .oi-name { color: #ccc; font-size: .86rem; font-weight: 600; line-height: 1.35; }
        .oi-meta { color: #555; font-size: .78rem; margin-top: 3px; }

        .ob-footer {
            padding: 12px 18px; border-top: 1px solid #1e1e1e;
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 10px; background: #0d0d0d;
        }
        .ob-view-btn { color: #e63946; font-size: .85rem; font-weight: 700; text-decoration: none; }
        .ob-view-btn:hover { text-decoration: underline; }

        /* Tracker */
        .ob-tracker { display: flex; align-items: center; font-size: .72rem; }
        .trk-step   { padding: 0 6px; white-space: nowrap; }
        .trk-done   { color: #2d6a2d; font-weight: 600; }
        .trk-act    { color: #e0a000; font-weight: 700; }
        .trk-green  { color: #6fcf97 !important; }
        .trk-dim    { color: #2a2a2a; }
        .trk-line   { width: 16px; height: 1px; background: #2a2a2a; flex-shrink: 0; }

        .logout-lnk { color: #555; font-size: .88rem; text-decoration: none; }
        .logout-lnk:hover { color: #e63946; }

        @media (max-width: 600px) {
            .frow { flex-direction: column; gap: 0; }
            .card { padding: 20px 16px; }
            .ob-tracker { display: none; }
            .order-block-head { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="acc-wrap">

    <!-- Header -->
    <div class="acc-head">
        <div class="acc-av"><?= htmlspecialchars($initial) ?></div>
        <div>
            <h2><?= htmlspecialchars((string)($user['name'] ?? '')) ?></h2>
            <p><?= htmlspecialchars((string)($user['email'] ?? '')) ?>
               <?= $memberSince ? ' &nbsp;·&nbsp; Member since ' . $memberSince : '' ?>
            </p>
        </div>
    </div>

    <!-- Alerts -->
    <?php if (!empty($flash) && is_array($flash) && ($flash['type'] ?? '') === 'success'): ?>
        <div class="alert alert-ok">✅ <?= htmlspecialchars((string)$flash['message']) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-ok">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-err">
            <?php foreach ($errors as $e): ?>
                <div>⚠ <?= htmlspecialchars((string)$e) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="tabs">
        <button class="tab-btn <?= $activeTab==='profile'  ? 'active':'' ?>" onclick="switchTab('profile',this)">👤 Profile</button>
        <button class="tab-btn <?= $activeTab==='orders'   ? 'active':'' ?>" onclick="switchTab('orders',this)">📦 My Orders</button>
        <button class="tab-btn <?= $activeTab==='password' ? 'active':'' ?>" onclick="switchTab('password',this)">🔑 Password</button>
    </div>

    <!-- ══ PROFILE TAB ══ -->
    <div class="tab-pane <?= $activeTab==='profile' ? 'active':'' ?>" id="pane-profile">
        <div class="card">
            <h3>Personal Information</h3>
            <form method="POST">
                <input type="hidden" name="action" value="update_profile">
                <div class="frow">
                    <div class="fg">
                        <label>Full Name</label>
                        <input type="text" name="name"
                               value="<?= htmlspecialchars((string)($user['name'] ?? '')) ?>" required>
                    </div>
                    <div class="fg">
                        <label>Phone Number</label>
                        <input type="tel" name="phone"
                               value="<?= htmlspecialchars((string)($user['phone'] ?? '')) ?>"
                               maxlength="10" placeholder="10-digit number">
                    </div>
                </div>
                <div class="fg">
                    <label>Email Address <span style="color:#444;font-size:.75rem">(cannot be changed)</span></label>
                    <input type="email" value="<?= htmlspecialchars((string)($user['email'] ?? '')) ?>" readonly>
                </div>
                <button type="submit" class="btn-save">Save Changes</button>
            </form>
        </div>
        <div class="card" style="text-align:center;padding:18px 30px">
            <a href="logout.php" class="logout-lnk">🚪 Sign out of this account</a>
        </div>
    </div>

    <!-- ══ ORDERS TAB ══ -->
    <div class="tab-pane <?= $activeTab==='orders' ? 'active':'' ?>" id="pane-orders">
        <div class="card">
            <h3>My Orders
                <span style="color:#444;font-size:.85rem;font-weight:400">
                    (<?= count($orders) ?>)
                </span>
            </h3>

            <?php if (empty($orders)): ?>
                <div class="no-orders">
                    📦 No orders placed yet.<br>
                    <a href="/proburst/pages/shop.php">Start Shopping →</a>
                </div>

            <?php else:
                foreach ($orders as $o):
                    $s        = strtolower((string)($o['status'] ?? 'pending'));
                    $badgeCls = statusBadge($s);
                    $oid      = (int)$o['id'];
                    // Use pre-fetched items — no DB query inside loop
                    $oItems   = isset($orderItems[$oid]) && is_array($orderItems[$oid])
                                ? $orderItems[$oid] : [];
            ?>
                <div class="order-block">

                    <!-- Head -->
                    <div class="order-block-head">
                        <div>
                            <span class="ob-num">Order #<?= $oid ?></span>
                            <span class="ob-date">
                                <?= !empty($o['created_at'])
                                    ? date('d M Y', strtotime($o['created_at']))
                                    : '—' ?>
                            </span>
                        </div>
                        <div style="display:flex;align-items:center;gap:12px">
                            <span class="badge <?= $badgeCls ?>"><?= ucfirst($s) ?></span>
                            <span class="ob-total">₹<?= number_format((float)($o['total'] ?? 0)) ?></span>
                        </div>
                    </div>

                    <!-- Items -->
                    <div class="order-items-list">
                        <?php if (empty($oItems)): ?>
                            <div style="color:#444;font-size:.83rem;padding:6px 0">No items found.</div>
                        <?php else:
                            foreach ($oItems as $oi):
                                $pname  = (string)($oi['product_name']  ?? 'Product');
                                $pimage = (string)($oi['product_image'] ?? '');
                                $qty    = (int)($oi['qty']   ?? 1);
                                $price  = (float)($oi['price'] ?? 0);
                        ?>
                            <div class="oi-row">
                                <img src="../assets/images/<?= htmlspecialchars($pimage) ?>"
                                     class="oi-img"
                                     onerror="this.style.opacity='.15'"
                                     alt="<?= htmlspecialchars($pname) ?>">
                                <div>
                                    <div class="oi-name"><?= htmlspecialchars($pname) ?></div>
                                    <div class="oi-meta">
                                        Qty: <?= $qty ?>
                                        &nbsp;·&nbsp;
                                        ₹<?= number_format($price * $qty) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach;
                        endif; ?>
                    </div>

                    <!-- Footer -->
                    <div class="ob-footer">
                        <a href="/proburst/pages/order-confirmation.php?order_id=<?= $oid ?>"
                           class="ob-view-btn">📋 View Details →</a>

                        <?php if ($s !== 'cancelled'): ?>
                        <div class="ob-tracker">
                            <?php
                            $steps  = ['Confirmed', 'Shipped', 'Delivered'];
                            $active = ($s === 'shipped') ? 1 : (($s === 'delivered') ? 2 : 0);
                            foreach ($steps as $idx => $stepLabel):
                                if ($idx === 2 && $s === 'delivered')
                                    $cls = 'trk-step trk-done trk-green';
                                elseif ($idx < $active)
                                    $cls = 'trk-step trk-done';
                                elseif ($idx === $active)
                                    $cls = 'trk-step trk-act';
                                else
                                    $cls = 'trk-step trk-dim';
                                $icon = ($idx <= $active) ? '✔' : '·';
                            ?>
                                <?php if ($idx > 0): ?><span class="trk-line"></span><?php endif; ?>
                                <span class="<?= $cls ?>"><?= $icon ?> <?= $stepLabel ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- ══ PASSWORD TAB ══ -->
    <div class="tab-pane <?= $activeTab==='password' ? 'active':'' ?>" id="pane-password">
        <div class="card">
            <h3>Change Password</h3>
            <form method="POST">
                <input type="hidden" name="action" value="change_password">
                <div class="fg">
                    <label>Current Password</label>
                    <div class="pw-wrap">
                        <input type="password" name="current_password" id="curPwd"
                               placeholder="Enter your current password" required>
                        <button type="button" class="pw-eye" onclick="togglePw('curPwd',this)">👁</button>
                    </div>
                </div>
                <div class="frow">
                    <div class="fg">
                        <label>New Password</label>
                        <div class="pw-wrap">
                            <input type="password" name="new_password" id="newPwd"
                                   placeholder="Min. 8 characters" required
                                   oninput="checkStrength(this.value)">
                            <button type="button" class="pw-eye" onclick="togglePw('newPwd',this)">👁</button>
                        </div>
                        <div class="pw-bar-wrap"><div class="pw-bar" id="strengthBar"></div></div>
                        <div class="pw-bar-lbl"  id="strengthLbl"></div>
                    </div>
                    <div class="fg">
                        <label>Confirm New Password</label>
                        <div class="pw-wrap">
                            <input type="password" name="confirm_password" id="conPwd"
                                   placeholder="Re-enter new password" required>
                            <button type="button" class="pw-eye" onclick="togglePw('conPwd',this)">👁</button>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn-save">Update Password</button>
            </form>
        </div>
        <div class="card" style="padding:20px 30px">
            <p style="color:#555;font-size:.85rem;margin:0">
                Forgot your current password?
                <a href="forgot-password.php"
                   style="color:#e63946;text-decoration:none;font-weight:600">
                   Reset via email →
                </a>
            </p>
        </div>
    </div>

</div><!-- /acc-wrap -->

<script>
function switchTab(name, btn) {
    document.querySelectorAll('.tab-pane').forEach(function(p){ p.classList.remove('active'); });
    document.querySelectorAll('.tab-btn').forEach(function(b){ b.classList.remove('active'); });
    document.getElementById('pane-' + name).classList.add('active');
    btn.classList.add('active');
}

function togglePw(id, btn) {
    var inp = document.getElementById(id);
    if (!inp) return;
    inp.type = inp.type === 'password' ? 'text' : 'password';
    btn.textContent = inp.type === 'password' ? '👁' : '🙈';
}

function checkStrength(val) {
    var bar = document.getElementById('strengthBar');
    var lbl = document.getElementById('strengthLbl');
    if (!bar) return;
    var score = 0;
    if (val.length >= 8)           score++;
    if (/[A-Z]/.test(val))         score++;
    if (/[0-9]/.test(val))         score++;
    if (/[^A-Za-z0-9]/.test(val))  score++;
    var c = ['#e63946','#ff6a00','#e0a000','#4caf82'];
    var l = ['Weak','Fair','Good','Strong'];
    var w = ['25%','50%','75%','100%'];
    if (!val) { bar.style.width = '0'; lbl.textContent = ''; return; }
    var i = Math.max(0, Math.min(score - 1, 3));
    bar.style.width = w[i]; bar.style.background = c[i];
    lbl.textContent = l[i]; lbl.style.color = c[i];
}
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
