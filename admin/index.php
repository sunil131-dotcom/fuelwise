<?php
$adminTitle = 'Dashboard';
require_once '../includes/db.php';
requireAdmin();

$totalUsers   = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$activeUsers  = $conn->query("SELECT COUNT(*) as c FROM users WHERE is_active=1")->fetch_assoc()['c'];
$totalRecs    = $conn->query("SELECT COUNT(*) as c FROM recommendations")->fetch_assoc()['c'];
$totalFoods   = $conn->query("SELECT COUNT(*) as c FROM foods")->fetch_assoc()['c'];
$totalFoodDb  = $conn->query("SELECT COUNT(*) as c FROM food_nutrition")->fetch_assoc()['c'];
$totalExercises = $conn->query("SELECT COUNT(*) as c FROM exercises")->fetch_assoc()['c'];
$diabetesRecs  = $conn->query("SELECT COUNT(*) as c FROM recommendations WHERE condition_type='diabetes'")->fetch_assoc()['c'];
$ibsRecs       = $conn->query("SELECT COUNT(*) as c FROM recommendations WHERE condition_type='ibs'")->fetch_assoc()['c'];
$hypRecs       = $conn->query("SELECT COUNT(*) as c FROM recommendations WHERE condition_type='hypertension'")->fetch_assoc()['c'];
$preRecs       = $conn->query("SELECT COUNT(*) as c FROM recommendations WHERE condition_type='prediabetes'")->fetch_assoc()['c'];
$wmRecs        = $conn->query("SELECT COUNT(*) as c FROM recommendations WHERE condition_type='weight_management'")->fetch_assoc()['c'];
$multiRecs     = $conn->query("SELECT COUNT(*) as c FROM recommendations WHERE condition_type IN ('both','multiple')")->fetch_assoc()['c'];
$recentUsers  = $conn->query("SELECT full_name, email, created_at, is_active FROM users ORDER BY created_at DESC LIMIT 8")->fetch_all(MYSQLI_ASSOC);
$recentRecs   = $conn->query("SELECT r.*, u.full_name FROM recommendations r JOIN users u ON r.user_id=u.id ORDER BY r.created_at DESC LIMIT 6")->fetch_all(MYSQLI_ASSOC);

include 'header.php';
?>
<div class="admin-page-title">
  <h2>Overview</h2>
  <span style="font-size:0.85rem;color:var(--text-light);">Last updated: <?php echo date('d M Y, H:i'); ?></span>
</div>

<div class="admin-stats">
  <div class="admin-stat" data-icon="👥"><h3><?php echo $totalUsers; ?></h3><p>Total Users</p><div class="change up">↑ <?php echo $activeUsers; ?> active</div></div>
  <div class="admin-stat" data-icon="🥗"><h3><?php echo $totalRecs; ?></h3><p>Diet Plans Generated</p><div class="change up">↑ All time</div></div>
  <div class="admin-stat" data-icon="🍽️"><h3><?php echo $totalFoods; ?></h3><p>Curated Meals</p><div class="change up">↑ + <?php echo number_format($totalFoodDb); ?> in food DB</div></div>
  <div class="admin-stat" data-icon="💪"><h3><?php echo $totalExercises; ?></h3><p>Exercises</p><div class="change up">↑ Cardio + strength</div></div>
</div>

<!-- Condition breakdown -->
<div class="admin-two-col" style="margin-bottom:24px;">
  <div class="admin-form-card">
    <h3>Condition Breakdown</h3>
    <?php foreach ([
        ['🩸 Type 2 Diabetes',  $diabetesRecs, 'badge-blue'],
        ['🔵 Pre-Diabetes',     $preRecs,      'badge-blue'],
        ['🌿 IBS',              $ibsRecs,      'badge-green'],
        ['🫀 Hypertension',     $hypRecs,      'badge-red'],
        ['⚖️ Weight Management', $wmRecs,      'badge-orange'],
        ['🔗 Multiple/Both',    $multiRecs,    'badge-blue']
    ] as [$label, $count, $badge]): $pct = $totalRecs > 0 ? round($count/$totalRecs*100) : 0; ?>
    <div style="margin-bottom:16px;">
      <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
        <span style="font-size:0.88rem;font-weight:600;"><?php echo $label; ?></span>
        <span class="badge <?php echo $badge; ?>"><?php echo $count; ?> (<?php echo $pct; ?>%)</span>
      </div>
      <div style="height:8px;background:var(--border);border-radius:4px;overflow:hidden;">
        <div style="height:100%;width:<?php echo $pct; ?>%;background:var(--green);border-radius:4px;transition:width 0.6s ease;"></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="admin-form-card">
    <h3>Recent Recommendations</h3>
    <?php foreach ($recentRecs as $r): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--border);">
      <div><div style="font-size:0.88rem;font-weight:600;color:var(--text-dark);"><?php echo htmlspecialchars($r['full_name']); ?></div>
      <div style="font-size:0.78rem;color:var(--text-light);"><?php echo date('d M, H:i',strtotime($r['created_at'])); ?></div></div>
      <span class="badge badge-green"><?php echo ucfirst(str_replace('_',' ',$r['condition_type'])); ?></span>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Recent Users -->
<div class="admin-table-wrap">
  <div class="admin-table-header">
    <h3 style="font-size:1rem;font-weight:700;color:var(--navy-dark);">Recent Users</h3>
    <a href="<?php echo SITE_URL; ?>/admin/users.php" style="font-size:0.85rem;color:var(--green);font-weight:600;">View All →</a>
  </div>
  <table>
    <thead><tr><th>Name</th><th>Email</th><th>Joined</th><th>Status</th></tr></thead>
    <tbody>
      <?php foreach ($recentUsers as $u): ?>
      <tr>
        <td><?php echo htmlspecialchars($u['full_name']); ?></td>
        <td style="color:var(--text-light);"><?php echo htmlspecialchars($u['email']); ?></td>
        <td><?php echo date('d M Y',strtotime($u['created_at'])); ?></td>
        <td><span class="badge <?php echo $u['is_active']?'badge-green':'badge-red'; ?>"><?php echo $u['is_active']?'Active':'Inactive'; ?></span></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include 'footer.php'; ?>
