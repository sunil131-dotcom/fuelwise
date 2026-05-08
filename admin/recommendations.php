<?php
$adminTitle = 'Recommendations';
require_once '../includes/db.php';
requireAdmin();

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM recommendations WHERE id=$id");
    setFlash('success','Recommendation deleted.');
    redirect('admin/recommendations.php');
}

$recs = $conn->query("SELECT r.*, u.full_name, u.email FROM recommendations r JOIN users u ON r.user_id=u.id ORDER BY r.created_at DESC")->fetch_all(MYSQLI_ASSOC);
include 'header.php';
?>
<div class="admin-page-title">
  <h2>All Diet Plans</h2>
  <span class="badge badge-blue"><?php echo count($recs); ?> Total</span>
</div>
<div class="admin-table-wrap">
  <div class="admin-table-header">
    <div class="admin-search"><span>🔍</span><input type="text" id="tableSearch" placeholder="Search..."></div>
  </div>
  <table>
    <thead><tr><th>#</th><th>User</th><th>Condition</th><th>Calories</th><th>Protein</th><th>Carbs</th><th>Fat</th><th>Date</th><th>Action</th></tr></thead>
    <tbody id="tableBody">
      <?php foreach ($recs as $i => $r): ?>
      <tr>
        <td><?php echo $i+1; ?></td>
        <td><div style="font-weight:600;font-size:0.88rem;"><?php echo htmlspecialchars($r['full_name']); ?></div><div style="font-size:0.78rem;color:var(--text-light);"><?php echo htmlspecialchars($r['email']); ?></div></td>
        <td><span class="badge <?php echo $r['condition_type']==='diabetes'?'badge-blue':($r['condition_type']==='ibs'?'badge-green':'badge-orange'); ?>"><?php echo ucfirst(str_replace('_',' ',$r['condition_type'])); ?></span></td>
        <td><?php echo number_format($r['daily_calories']); ?> kcal</td>
        <td><?php echo $r['daily_protein']; ?>g</td>
        <td><?php echo $r['daily_carbs']; ?>g</td>
        <td><?php echo $r['daily_fat']; ?>g</td>
        <td style="font-size:0.82rem;"><?php echo date('d M Y',strtotime($r['created_at'])); ?></td>
        <td><a href="?delete=<?php echo $r['id']; ?>" class="action-btn delete btn-delete-confirm">Delete</a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include 'footer.php'; ?>
