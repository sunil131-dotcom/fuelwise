<?php
require_once 'includes/db.php';
requireLogin();
$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM recommendations WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$pageTitle = 'My History';
$extraCSS  = 'dashboard.css';
include 'includes/header.php';
?>
<div class="inner-page">
  <div class="page-header">
    <h1>My Diet Plan History</h1>
    <p>All your previously generated personalised diet plans</p>
    <div class="breadcrumb">
      <a href="<?php echo SITE_URL; ?>/index.php">Home</a><span>›</span>
      <a href="<?php echo SITE_URL; ?>/dashboard.php">Dashboard</a><span>›</span>
      <span class="current">History</span>
    </div>
  </div>
  <div class="inner-content">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
      <h3 style="color:var(--navy-dark);"><?php echo count($history); ?> Plan(s) Generated</h3>
      <a href="<?php echo SITE_URL; ?>/assessment.php" class="btn-primary">Generate New Plan →</a>
    </div>
    <?php if (empty($history)): ?>
      <div style="text-align:center;padding:60px 24px;">
        <div style="font-size:4rem;margin-bottom:20px;">📋</div>
        <h2 style="color:var(--navy-dark);margin-bottom:12px;">No History Yet</h2>
        <p style="color:var(--text-mid);margin-bottom:28px;">Complete your health assessment to generate your first personalised diet plan.</p>
        <a href="<?php echo SITE_URL; ?>/assessment.php" class="btn-primary btn-lg">Start Assessment →</a>
      </div>
    <?php else: ?>
      <?php foreach ($history as $item): ?>
      <div class="history-card">
        <div class="history-card-header">
          <div>
            <h4>Diet Plan — <?php echo getConditionDisplay($item['condition_type'], $item['conditions_list'] ?? null); ?></h4>
            <div style="display:flex;gap:8px;margin-top:6px;flex-wrap:wrap;">
              <span class="badge badge-blue"><?php echo number_format($item['daily_calories']); ?> kcal</span>
              <span class="badge badge-green"><?php echo $item['daily_protein']; ?>g Protein</span>
              <span class="badge badge-purple"><?php echo $item['daily_carbs']; ?>g Carbs</span>
              <span class="badge badge-orange"><?php echo $item['daily_fat']; ?>g Fat</span>
            </div>
          </div>
          <div class="history-date"><?php echo date('d M Y, H:i', strtotime($item['created_at'])); ?></div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-top:4px;">
          <?php foreach (['breakfast'=>'🌅','lunch'=>'☀️','dinner'=>'🌙','snacks'=>'🍎'] as $key=>$icon):
            $first = explode(' | ',$item[$key])[0]; ?>
          <div style="background:var(--off-white);border-radius:var(--radius);padding:12px;">
            <div style="font-size:0.75rem;font-weight:700;color:var(--text-light);text-transform:uppercase;margin-bottom:4px;"><?php echo $icon.' '.ucfirst($key); ?></div>
            <div style="font-size:0.85rem;color:var(--text-dark);"><?php echo htmlspecialchars($first); ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
