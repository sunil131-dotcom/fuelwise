<?php
require_once 'includes/db.php';
requireLogin();

$userId = $_SESSION['user_id'];

// Handle marking notification read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark_read') {
    $notifId = (int)($_POST['notif_id'] ?? 0);
    if ($notifId === 0) {
        markAllNotificationsRead($userId);
    } else {
        markNotificationRead($userId, $notifId);
    }
    redirect('dashboard.php');
}

// Trigger engagement notifications (welcome, streaks, inactivity)
triggerEngagementNotifications($userId);

// -- Fetch user data --
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// -- Latest recommendation (for today's plan) --
$stmt = $conn->prepare("SELECT * FROM recommendations WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$rec = $stmt->get_result()->fetch_assoc();
$stmt->close();

// -- Health profile --
$stmt = $conn->prepare("SELECT * FROM health_profiles WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
$stmt->close();

// -- Get today's meal plan from weekly_meals --
$todayMeal = null;
if ($rec) {
    $todayDay = (int)date('N');
    $stmt = $conn->prepare("SELECT * FROM weekly_meals WHERE recommendation_id = ? AND day_number = ?");
    $stmt->bind_param("ii", $rec['id'], $todayDay);
    $stmt->execute();
    $todayMeal = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// -- Streak info --
$streak = getUserStreak($userId);

// -- Notifications --
$notifications = getAllNotifications($userId, 8);
$unreadCount = count(array_filter($notifications, fn($n) => !$n['is_read']));

// -- Count history --
$stmt = $conn->prepare("SELECT COUNT(*) as c FROM recommendations WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$recCount = $stmt->get_result()->fetch_assoc()['c'];
$stmt->close();

$pageTitle = 'Dashboard';
$extraCSS  = 'dashboard.css';
include 'includes/header.php';
?>

<div class="inner-page">
    <div class="page-header">
        <h1>Welcome, <?php echo htmlspecialchars(explode(' ', $user['full_name'])[0]); ?> 👋</h1>
        <p>Here's your health dashboard and today's personalised plan.</p>
        <div class="breadcrumb">
            <a href="<?php echo SITE_URL; ?>/index.php">Home</a>
            <span>›</span>
            <span class="current">Dashboard</span>
        </div>
    </div>

    <div class="inner-content">
        <?php showFlash(); ?>

        <!-- Notifications banner -->
        <?php if ($unreadCount > 0): ?>
        <div class="notifications-banner">
            <div class="notifications-header">
                <h3>🔔 You have <?php echo $unreadCount; ?> new notification<?php echo $unreadCount > 1 ? 's' : ''; ?></h3>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="mark_read">
                    <input type="hidden" name="notif_id" value="0">
                    <button type="submit" class="btn-text-sm">Mark all read</button>
                </form>
            </div>
            <div class="notifications-list">
                <?php foreach (array_slice($notifications, 0, 3) as $n):
                    if ($n['is_read']) continue; ?>
                    <div class="notification-item notification-<?php echo $n['type']; ?>">
                        <div class="notif-icon"><?php echo $n['icon']; ?></div>
                        <div class="notif-content">
                            <strong><?php echo htmlspecialchars($n['title']); ?></strong>
                            <p><?php echo htmlspecialchars($n['message']); ?></p>
                            <?php if ($n['action_url']): ?>
                            <a href="<?php echo htmlspecialchars($n['action_url']); ?>" class="notif-action"><?php echo htmlspecialchars($n['action_label'] ?: 'View'); ?> →</a>
                            <?php endif; ?>
                        </div>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="mark_read">
                            <input type="hidden" name="notif_id" value="<?php echo $n['id']; ?>">
                            <button type="submit" class="notif-dismiss">×</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Top stats -->
        <div class="stats-grid" style="margin-bottom:28px;">
            <div class="stat-card">
                <div class="stat-icon orange">🔥</div>
                <div>
                    <div class="stat-value"><?php echo $streak['current_streak'] ?? 0; ?></div>
                    <div class="stat-label">Day Streak</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">🏆</div>
                <div>
                    <div class="stat-value"><?php echo $streak['longest_streak'] ?? 0; ?></div>
                    <div class="stat-label">Longest Streak</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue">📋</div>
                <div>
                    <div class="stat-value"><?php echo $recCount; ?></div>
                    <div class="stat-label">Plans Generated</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red">👤</div>
                <div>
                    <div class="stat-value"><?php echo $profile['bmi'] ?? 'N/A'; ?></div>
                    <div class="stat-label">Your BMI</div>
                </div>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 2fr 1fr; gap:24px;">

            <!-- Main content -->
            <div>
                <!-- Today's Meal Plan -->
                <?php if ($todayMeal): ?>
                <div class="panel">
                    <div class="panel-header">
                        <h3>🍽️ Today's Meal Plan (<?php echo date('l'); ?>)</h3>
                        <a href="<?php echo SITE_URL; ?>/recommendation.php" class="btn-text-sm">View Full 7-Day Plan →</a>
                    </div>
                    <div class="panel-body">
                        <?php
                        $meals = [
                            'breakfast' => ['🌅', 'Breakfast', $todayMeal['breakfast']],
                            'lunch'     => ['☀️', 'Lunch',     $todayMeal['lunch']],
                            'dinner'    => ['🌙', 'Dinner',    $todayMeal['dinner']],
                            'snacks'    => ['🍎', 'Snacks',    $todayMeal['snacks']],
                        ];
                        foreach ($meals as $key => [$icon, $label, $items]):
                            $itemList = explode(' | ', $items);
                        ?>
                        <div class="meal-card">
                            <div class="meal-header <?php echo $key === 'snacks' ? 'snack' : $key; ?>">
                                <?php echo $icon; ?> <?php echo $label; ?>
                            </div>
                            <div class="meal-body">
                                <?php foreach ($itemList as $i => $item): ?>
                                    <div class="meal-item">
                                        <span class="meal-option-num">Option <?php echo $i + 1; ?></span>
                                        <p><?php echo htmlspecialchars(trim($item)); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <div style="margin-top:16px; padding:12px; background:var(--light-grey); border-radius:var(--radius); display:flex;gap:20px;font-size:0.88rem;color:var(--text-mid);">
                            <span>🔥 <?php echo number_format($todayMeal['day_calories']); ?> kcal</span>
                            <span>💪 <?php echo $todayMeal['day_protein']; ?>g protein</span>
                            <span>🌾 <?php echo $todayMeal['day_carbs']; ?>g carbs</span>
                            <span>🥑 <?php echo $todayMeal['day_fat']; ?>g fat</span>
                        </div>
                    </div>
                </div>
                <?php elseif (!$rec): ?>
                <!-- No plan yet -->
                <div class="panel">
                    <div class="panel-body" style="text-align:center; padding:40px 24px;">
                        <div style="font-size:3rem; margin-bottom:12px;">🥗</div>
                        <h3 style="margin-bottom:10px;">No Diet Plan Yet</h3>
                        <p style="color:var(--text-mid); margin-bottom:20px;">Complete your health assessment to receive your personalised plan.</p>
                        <a href="<?php echo SITE_URL; ?>/assessment.php" class="btn-primary btn-lg">Start Assessment →</a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Recent notifications -->
                <?php if (!empty($notifications)): ?>
                <div class="panel" style="margin-top:20px;">
                    <div class="panel-header">
                        <h3>🔔 Recent Activity</h3>
                    </div>
                    <div class="panel-body">
                        <?php foreach ($notifications as $n): ?>
                        <div class="activity-item <?php echo $n['is_read'] ? 'read' : 'unread'; ?>">
                            <div class="activity-icon"><?php echo $n['icon']; ?></div>
                            <div class="activity-content">
                                <strong><?php echo htmlspecialchars($n['title']); ?></strong>
                                <p><?php echo htmlspecialchars($n['message']); ?></p>
                                <small><?php echo date('M j, g:i A', strtotime($n['created_at'])); ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div>
                <?php if ($profile): ?>
                <div class="panel" style="margin-bottom:20px;">
                    <div class="panel-header"><h3>👤 Profile Snapshot</h3></div>
                    <div class="panel-body">
                        <div class="profile-info">
                            <div class="info-row"><span class="label">Age</span><span class="value"><?php echo $profile['age']; ?></span></div>
                            <div class="info-row"><span class="label">Weight</span><span class="value"><?php echo $profile['weight']; ?> kg</span></div>
                            <div class="info-row"><span class="label">Height</span><span class="value"><?php echo $profile['height']; ?> cm</span></div>
                            <div class="info-row"><span class="label">BMI</span><span class="value">
                                <?php
                                $b = $profile['bmi'];
                                echo $b . ' ';
                                if ($b < 18.5) echo '<span class="badge badge-blue">Underweight</span>';
                                elseif ($b < 25) echo '<span class="badge badge-green">Normal</span>';
                                elseif ($b < 30) echo '<span class="badge badge-orange">Overweight</span>';
                                else echo '<span class="badge badge-red">Obese</span>';
                                ?>
                            </span></div>
                            <div class="info-row"><span class="label">Conditions</span><span class="value"><?php echo getConditionDisplay($profile['condition_type'], $profile['conditions_list']); ?></span></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Quick actions -->
                <div class="panel">
                    <div class="panel-header"><h3>⚡ Quick Actions</h3></div>
                    <div class="panel-body" style="display:flex; flex-direction:column; gap:10px;">
                        <a href="<?php echo SITE_URL; ?>/recommendation.php" class="btn-primary" style="text-align:center;">🍽️ View My Diet Plan</a>
                        <a href="<?php echo SITE_URL; ?>/assessment.php" class="btn-outline" style="text-align:center; padding:10px; border:2px solid var(--navy); color:var(--navy); border-radius:var(--radius); text-decoration:none;">📝 Update Assessment</a>
                        <a href="<?php echo SITE_URL; ?>/history.php" class="btn-outline" style="text-align:center; padding:10px; border:2px solid var(--navy); color:var(--navy); border-radius:var(--radius); text-decoration:none;">📋 View Plan History</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
