<?php
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/../config/database.php';

$msg = '';

// ── TOGGLE ROLE ──
if (isset($_GET['toggle_role'])) {
    $uid = (int)$_GET['toggle_role'];
    $u = $conn->query("SELECT role FROM users WHERE id=$uid")->fetch_assoc();
    $newRole = $u['role'] === 'admin' ? 'user' : 'admin';
    $conn->query("UPDATE users SET role='$newRole' WHERE id=$uid");
    $msg = "User role updated to $newRole.";
}

// ── DELETE ──
if (isset($_GET['delete'])) {
    $uid = (int)$_GET['delete'];
    if ($uid !== (int)$_SESSION['user_id']) {
        $conn->query("DELETE FROM users WHERE id=$uid");
        $msg = "User deleted.";
    } else {
        $msg = "You cannot delete your own account.";
    }
}

// ── SEARCH ──
$search = $conn->real_escape_string($_GET['q'] ?? '');
$roleFilter = $conn->real_escape_string($_GET['role'] ?? '');
$where = [];
if ($search)     $where[] = "(name LIKE '%$search%' OR email LIKE '%$search%' OR phone LIKE '%$search%')";
if ($roleFilter) $where[] = "role='$roleFilter'";
$whereSQL = $where ? 'WHERE '.implode(' AND ', $where) : '';

$users = $conn->query("
    SELECT u.*,
        (SELECT COUNT(*) FROM orders o WHERE o.user_id=u.id) AS order_count,
        (SELECT COALESCE(SUM(total),0) FROM orders o WHERE o.user_id=u.id AND o.status!='cancelled') AS total_spent
    FROM users u $whereSQL ORDER BY u.created_at DESC
");

adminHead('Users');
adminSidebar($navItems, $currentPage);
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Users <span>Management</span></h1>
    <p class="page-subtitle">View and manage all registered customers and admins.</p>
  </div>
</div>

<?php if($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<!-- FILTER BAR -->
<form method="GET" class="filter-bar">
  <input type="text" name="q" placeholder="Search name, email, phone..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
  <select name="role">
    <option value="">All Roles</option>
    <option value="user"  <?= ($_GET['role']??'')==='user'  ?'selected':'' ?>>Customers</option>
    <option value="admin" <?= ($_GET['role']??'')==='admin' ?'selected':'' ?>>Admins</option>
  </select>
  <button class="btn btn-ghost">Search</button>
  <?php if(!empty($_GET['q']) || !empty($_GET['role'])): ?>
    <a href="users.php" class="btn btn-ghost">Clear</a>
  <?php endif; ?>
</form>

<!-- USERS TABLE -->
<div class="table-card">
  <div class="table-card-header">
    <span class="table-card-title">All Users (<?= $users->num_rows ?>)</span>
  </div>
  <?php if($users->num_rows > 0): ?>
  <div class="table-scroll"><table>
    <thead><tr>
      <th>User</th><th>Email</th><th>Phone</th><th>Role</th><th>Orders</th><th>Total Spent</th><th>Joined</th><th>Actions</th>
    </tr></thead>
    <tbody>
    <?php while($u=$users->fetch_assoc()): ?>
    <tr>
      <td>
        <div style="display:flex;align-items:center;gap:10px">
          <div class="user-avatar"><?= strtoupper(mb_substr($u['name'],0,1)) ?></div>
          <div>
            <div class="fw-bold"><?= htmlspecialchars($u['name']) ?></div>
            <small class="text-muted">#<?= $u['id'] ?></small>
          </div>
        </div>
      </td>
      <td class="text-muted"><?= htmlspecialchars($u['email']) ?></td>
      <td class="text-muted"><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
      <td><span class="badge badge-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
      <td><?= $u['order_count'] ?></td>
      <td class="fw-bold">₹<?= number_format($u['total_spent']) ?></td>
      <td class="text-muted"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
      <td>
        <div class="action-group">
          <a href="users.php?toggle_role=<?= $u['id'] ?>"
             class="btn btn-sm <?= $u['role']==='admin' ? 'btn-warning' : 'btn-info' ?>"
             style="<?= $u['role']==='admin' ? 'background:rgba(245,158,11,.12);color:#f59e0b;border:1px solid rgba(245,158,11,.25)' : '' ?>"
             onclick="return confirm('Toggle role for <?= htmlspecialchars($u['name']) ?>?')">
            <?= $u['role']==='admin' ? '👤 Make User' : '⚡ Make Admin' ?>
          </a>
          <?php if($u['id'] !== (int)$_SESSION['user_id']): ?>
          <a href="users.php?delete=<?= $u['id'] ?>" class="btn btn-danger btn-sm"
             onclick="return confirm('Delete this user and all their data?')">🗑</a>
          <?php endif; ?>
        </div>
      </td>
    </tr>
    <?php endwhile; ?>
    </tbody>
  </table></div>
  <?php else: ?>
    <div class="empty-state"><div class="empty-icon">👥</div><p>No users found.</p></div>
  <?php endif; ?>
</div>

<?php adminClose(); ?>
