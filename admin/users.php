<?php
$adminTitle = 'Manage Users';
require_once '../includes/db.php';
requireAdmin();

// Toggle active status
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $conn->query("UPDATE users SET is_active = NOT is_active WHERE id=$id");
    setFlash('success','User status updated.');
    redirect('admin/users.php');
}
// Delete user
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM users WHERE id=$id");
    setFlash('success','User deleted.');
    redirect('admin/users.php');
}

$users = $conn->query("SELECT u.*, (SELECT COUNT(*) FROM recommendations r WHERE r.user_id=u.id) as rec_count FROM users u ORDER BY u.created_at DESC")->fetch_all(MYSQLI_ASSOC);

include 'header.php';
?>
<div class="admin-page-title">
  <h2>Manage Users</h2>
  <span class="badge badge-blue"><?php echo count($users); ?> Total</span>
</div>
<div class="admin-table-wrap">
  <div class="admin-table-header">
    <div class="admin-search"><span>🔍</span><input type="text" id="tableSearch" placeholder="Search users..."></div>
  </div>
  <table>
    <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Gender</th><th>Plans</th><th>Joined</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody id="tableBody">
      <?php foreach ($users as $i => $u): ?>
      <tr>
        <td><?php echo $i+1; ?></td>
        <td><?php echo htmlspecialchars($u['full_name']); ?></td>
        <td style="color:var(--text-light);font-size:0.85rem;"><?php echo htmlspecialchars($u['email']); ?></td>
        <td><?php echo ucfirst($u['gender']); ?></td>
        <td><span class="badge badge-blue"><?php echo $u['rec_count']; ?></span></td>
        <td style="font-size:0.83rem;"><?php echo date('d M Y',strtotime($u['created_at'])); ?></td>
        <td><span class="badge <?php echo $u['is_active']?'badge-green':'badge-red'; ?>"><?php echo $u['is_active']?'Active':'Inactive'; ?></span></td>
        <td style="white-space:nowrap;">
          <a href="?toggle=<?php echo $u['id']; ?>" class="action-btn <?php echo $u['is_active']?'toggle-on':'toggle-off'; ?>"><?php echo $u['is_active']?'Deactivate':'Activate'; ?></a>
          <a href="?delete=<?php echo $u['id']; ?>" class="action-btn delete btn-delete-confirm" style="margin-left:4px;">Delete</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include 'footer.php'; ?>
