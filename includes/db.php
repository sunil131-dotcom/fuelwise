<?php
// ============================================================
// FuelWise Configuration
// ============================================================

// -- Database Configuration --
define('DB_HOST', 'localhost:3307');   // Change to 'localhost' if using default MySQL port 3306
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'fuelwise');

// -- Site Configuration --
define('SITE_URL',  'http://localhost/fuelwise');
define('SITE_NAME', 'FuelWise');

// -- Email Configuration (PHPMailer / Gmail SMTP) --
// IMPORTANT: You must use a Gmail App Password (not your regular password)
//   1. Enable 2-Step Verification: https://myaccount.google.com/security
//   2. Generate App Password at:   https://myaccount.google.com/apppasswords
//   3. Paste the 16-character password below (no spaces)
define('MAIL_HOST',      'smtp.gmail.com');
define('MAIL_PORT',      587);
define('MAIL_USERNAME',  'sunil.ntp1@gmail.com');     // Your Gmail address
define('MAIL_PASSWORD',  'your_app_password');        // 16-char App Password
define('MAIL_FROM',      'sunil.ntp1@gmail.com');     // Must match MAIL_USERNAME
define('MAIL_FROM_NAME', 'FuelWise');

// -- Dev Mode --
// When DEV_MODE is true, OTPs and reset tokens are also displayed on-screen
// so you can test authentication flows without a working email setup.
// SET TO FALSE IN PRODUCTION!
define('DEV_MODE', true);

// -- Clinical Thresholds (for safety warnings) --
// These are informational thresholds — not diagnostic criteria.
define('GLUCOSE_HIGH_THRESHOLD',          126);   // mg/dL — diabetes criterion
define('GLUCOSE_PREDIABETES_THRESHOLD',   100);   // mg/dL — prediabetes criterion
define('BP_SYSTOLIC_HIGH_THRESHOLD',      140);   // mmHg — stage 2 hypertension
define('BP_DIASTOLIC_HIGH_THRESHOLD',     90);    // mmHg
define('BP_SYSTOLIC_ELEVATED_THRESHOLD',  130);   // mmHg — elevated
define('BP_DIASTOLIC_ELEVATED_THRESHOLD', 85);    // mmHg
define('INSULIN_HIGH_THRESHOLD',          25);    // μU/mL — elevated fasting insulin

// -- Create Database Connection --
function getDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

$conn = getDB();

// -- Start session --
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// Helper Functions
// ============================================================

// Sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Redirect
function redirect($url) {
    header("Location: " . SITE_URL . "/" . $url);
    exit();
}

// Check login state
function isLoggedIn()      { return isset($_SESSION['user_id']); }
function isAdminLoggedIn() { return isset($_SESSION['admin_id']) && isset($_SESSION['admin_verified']) && $_SESSION['admin_verified'] === true; }

// Require login
function requireLogin() { if (!isLoggedIn())      redirect('login.php'); }
function requireAdmin() { if (!isAdminLoggedIn()) redirect('admin/login.php'); }

// ============================================================
// Flash Messages
// ============================================================
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function showFlash() {
    $flash = getFlash();
    if ($flash) {
        $icon = $flash['type'] === 'success' ? '✓' : ($flash['type'] === 'error' ? '✗' : 'ℹ');
        echo "<div class='alert alert-{$flash['type']}'><span>{$icon}</span> {$flash['message']}</div>";
    }
}

// ============================================================
// Condition Labels & Lists
// ============================================================
function getConditionLabels() {
    return [
        'diabetes'          => ['🩸', 'Type 2 Diabetes',               'Type 2 Diabetes'],
        'prediabetes'       => ['🔵', 'Pre-Diabetes',                  'Pre-Diabetes'],
        'ibs'               => ['🌿', 'Irritable Bowel Syndrome (IBS)', 'IBS'],
        'hypertension'      => ['🫀', 'High Blood Pressure (Hypertension)', 'Hypertension'],
        'weight_management' => ['⚖️', 'Weight Management',             'Weight Management'],
    ];
}

function getConditionDisplay($condition_type, $conditions_list = null) {
    $labels = getConditionLabels();
    if ($condition_type === 'multiple' && $conditions_list) {
        $codes = array_filter(array_map('trim', explode(',', $conditions_list)));
        $pretty = array_map(fn($c) => $labels[$c][2] ?? ucfirst($c), $codes);
        return implode(' + ', $pretty);
    }
    if ($condition_type === 'both') return 'Diabetes + IBS';
    return $labels[$condition_type][2] ?? ucfirst($condition_type);
}

// Parse conditions_list into an array of condition codes
function parseConditions($condition_type, $conditions_list) {
    if (!empty($conditions_list)) {
        return array_filter(array_map('trim', explode(',', $conditions_list)));
    }
    if ($condition_type === 'both') return ['diabetes', 'ibs'];
    if ($condition_type === 'multiple') return [];
    return [$condition_type];
}

// ============================================================
// User Streaks & Activity Tracking
// ============================================================
function updateUserStreak($userId) {
    $conn  = getDB();
    $today = date('Y-m-d');

    $stmt = $conn->prepare("SELECT * FROM user_streaks WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $streak = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$streak) {
        $stmt = $conn->prepare("INSERT INTO user_streaks (user_id, current_streak, longest_streak, last_login_date, total_logins) VALUES (?, 1, 1, ?, 1)");
        $stmt->bind_param("is", $userId, $today);
        $stmt->execute();
        $stmt->close();
        $conn->close();
        return 1;
    }

    $lastDate = $streak['last_login_date'];
    if ($lastDate === $today) { $conn->close(); return $streak['current_streak']; }

    $yesterday = date('Y-m-d', strtotime('-1 day'));
    if ($lastDate === $yesterday) {
        $newStreak = $streak['current_streak'] + 1;
    } else {
        $newStreak = 1; // streak broken
    }
    $longest = max($streak['longest_streak'], $newStreak);
    $totalLogins = $streak['total_logins'] + 1;

    $stmt = $conn->prepare("UPDATE user_streaks SET current_streak=?, longest_streak=?, last_login_date=?, total_logins=? WHERE user_id=?");
    $stmt->bind_param("iisii", $newStreak, $longest, $today, $totalLogins, $userId);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    return $newStreak;
}

function getUserStreak($userId) {
    $conn = getDB();
    $stmt = $conn->prepare("SELECT * FROM user_streaks WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $streak = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $streak ?: ['current_streak' => 0, 'longest_streak' => 0, 'total_logins' => 0];
}

// ============================================================
// Notifications
// ============================================================
function createNotification($userId, $type, $icon, $title, $message, $actionUrl = null, $actionLabel = null) {
    $conn = getDB();
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, icon, title, message, action_url, action_label) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssss", $userId, $type, $icon, $title, $message, $actionUrl, $actionLabel);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}

function getUnreadNotifications($userId, $limit = 5) {
    $conn = getDB();
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT ?");
    $stmt->bind_param("ii", $userId, $limit);
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
    return $results;
}

function getAllNotifications($userId, $limit = 20) {
    $conn = getDB();
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->bind_param("ii", $userId, $limit);
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
    return $results;
}

function markNotificationRead($userId, $notifId) {
    $conn = getDB();
    $now  = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1, read_at = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sii", $now, $notifId, $userId);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}

function markAllNotificationsRead($userId) {
    $conn = getDB();
    $now  = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1, read_at = ? WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("si", $now, $userId);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}

// ============================================================
// Engagement Triggers
// Called on login/dashboard to create contextual, supportive notifications.
// ============================================================
function triggerEngagementNotifications($userId) {
    $conn = getDB();

    // Has the user ever logged in before?
    $stmt = $conn->prepare("SELECT last_login, created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $streak = getUserStreak($userId);

    // Welcome to new users (account < 1 day old)
    if (strtotime($user['created_at']) > strtotime('-1 day')) {
        $exists = $conn->query("SELECT COUNT(*) as c FROM notifications WHERE user_id = $userId AND type = 'info' AND title LIKE 'Welcome%'")->fetch_assoc()['c'];
        if ($exists == 0) {
            createNotification($userId, 'info', '🌿',
                'Welcome to FuelWise!',
                "We're glad you're here. Complete your health assessment to get your personalised diet plan.",
                SITE_URL . '/assessment.php', 'Start Assessment');
        }
    }

    // Streak milestones (supportive, never shaming)
    if (in_array($streak['current_streak'], [3, 7, 14, 30, 60, 100])) {
        $msg = match($streak['current_streak']) {
            3  => "You're on a 3-day streak! Small steps lead to big changes. 🌱",
            7  => "A full week! You're building a healthy habit. 💪",
            14 => "Two weeks strong! Your dedication is inspiring. 🌟",
            30 => "A full month! You're truly committed to your health. 🏆",
            60 => "Two months of consistency! Amazing work. ✨",
            100 => "100 days! You're a FuelWise superstar! 🎉"
        };
        createNotification($userId, 'streak', '🔥',
            "{$streak['current_streak']}-day streak!", $msg);
    }

    // Has it been a while since last plan? (gentle reminder, no shaming)
    if ($streak['last_plan_date']) {
        $daysSince = (int)((time() - strtotime($streak['last_plan_date'])) / 86400);
        if ($daysSince === 7 || $daysSince === 14 || $daysSince === 30) {
            $messages = [
                7  => "It's been a week — ready for a fresh meal plan? Your dashboard is waiting.",
                14 => "We miss you! Your plan is waiting whenever you're ready.",
                30 => "A month out? No stress — come back when you're ready, we're here."
            ];
            $exists = $conn->query("SELECT COUNT(*) as c FROM notifications WHERE user_id = $userId AND type = 'reminder' AND DATE(created_at) = CURDATE()")->fetch_assoc()['c'];
            if ($exists == 0) {
                createNotification($userId, 'reminder', '🌿',
                    "Haven't seen you in a while",
                    $messages[$daysSince],
                    SITE_URL . '/recommendation.php', 'View My Plan');
            }
        }
    }

    $conn->close();
}

// ============================================================
// Log profile updates (for thesis analytics)
// ============================================================
function logProfileUpdate($userId, $updateType, $oldValues, $newValues) {
    $changed = [];
    foreach ($newValues as $k => $v) {
        if (!isset($oldValues[$k]) || $oldValues[$k] != $v) {
            $changed[] = $k;
        }
    }
    if (empty($changed)) return;

    $conn = getDB();
    $fieldsStr = implode(',', $changed);
    $oldJson = json_encode(array_intersect_key($oldValues, array_flip($changed)));
    $newJson = json_encode(array_intersect_key($newValues, array_flip($changed)));
    $stmt = $conn->prepare("INSERT INTO profile_updates (user_id, update_type, fields_changed, old_values, new_values) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $userId, $updateType, $fieldsStr, $oldJson, $newJson);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}

// ============================================================
// Allergy matcher — ingredient-level filtering
// Example: "salmon" blocks all foods containing "salmon"
// ============================================================
function foodMatchesAllergies($foodNameOrIngredients, $allergiesText) {
    if (empty($allergiesText)) return false;
    $haystack = strtolower($foodNameOrIngredients);
    $allergies = array_filter(array_map('trim', preg_split('/[,;\n]+/', strtolower($allergiesText))));
    foreach ($allergies as $allergen) {
        if (strlen($allergen) < 2) continue; // ignore tiny strings
        if (strpos($haystack, $allergen) !== false) return true;
    }
    return false;
}
?>
