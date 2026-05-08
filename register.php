<?php
require_once 'includes/db.php';
if (isLoggedIn()) redirect('dashboard.php');

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name'] ?? '');
    $email     = sanitize($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';
    $gender    = sanitize($_POST['gender'] ?? '');
    $dob       = sanitize($_POST['date_of_birth'] ?? '');
    $phone     = sanitize($_POST['phone'] ?? '');

    if (empty($full_name)) $errors[] = 'Full name is required.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';
    if (empty($gender)) $errors[] = 'Please select your gender.';
    if (empty($dob)) $errors[] = 'Date of birth is required.';

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = 'An account with this email already exists.';
        }
        $stmt->close();
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, gender, date_of_birth, phone) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $full_name, $email, $hash, $gender, $dob, $phone);
        if ($stmt->execute()) {
            $userId = $conn->insert_id;
            $_SESSION['user_id']    = $userId;
            $_SESSION['user_name']  = $full_name;
            $_SESSION['user_email'] = $email;
            $_SESSION['gender']     = $gender;

            // Initialize streak
            updateUserStreak($userId);

            // Try to send welcome email (silent on failure)
            if (function_exists('sendWelcomeEmail')) {
                @sendWelcomeEmail($email, $full_name);
            } else {
                @require_once 'includes/mailer.php';
                @sendWelcomeEmail($email, $full_name);
            }

            // In-app welcome notification
            createNotification($userId, 'info', '🌿',
                'Welcome to FuelWise!',
                "We're glad you're here, {$full_name}. Complete your health assessment to get started.",
                SITE_URL . '/assessment.php', 'Start Assessment');

            setFlash('success', 'Welcome to FuelWise! Please complete your health assessment to get started.');
            redirect('assessment.php');
        } else {
            $errors[] = 'Registration failed. Please try again.';
        }
        $stmt->close();
    }
}

$pageTitle = 'Register';
$extraCSS  = 'auth.css';
include 'includes/header.php';
?>

<div class="auth-page">
    <div class="auth-card wide">
        <div class="auth-logo">
            <a href="<?php echo SITE_URL; ?>/index.php">🌿 Fuel<span>Wise</span></a>
        </div>
        <div class="auth-title">
            <h2>Create Your Account</h2>
            <p>Join FuelWise and get your personalised nutrition plan</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <span>✗</span>
                <div><?php foreach ($errors as $e) echo "<div>$e</div>"; ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Full Name <span>*</span></label>
                    <input type="text" name="full_name" class="form-control" placeholder="John Smith"
                           value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address <span>*</span></label>
                    <input type="email" name="email" class="form-control" placeholder="john@example.com"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Password <span>*</span></label>
                    <div class="password-toggle">
                        <input type="password" name="password" id="password" class="form-control"
                               placeholder="Min. 8 characters" required>
                        <button type="button" class="toggle-btn">👁️</button>
                    </div>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="strengthBar"></div>
                    </div>
                    <div class="form-hint">Use uppercase, numbers, and symbols for a strong password.</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm Password <span>*</span></label>
                    <div class="password-toggle">
                        <input type="password" name="confirm_password" class="form-control"
                               placeholder="Repeat password" required>
                        <button type="button" class="toggle-btn">👁️</button>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Gender <span>*</span></label>
                    <select name="gender" class="form-control" required>
                        <option value="">Select gender</option>
                        <option value="male"   <?php echo (($_POST['gender'] ?? '') === 'male')   ? 'selected' : ''; ?>>Male</option>
                        <option value="female" <?php echo (($_POST['gender'] ?? '') === 'female') ? 'selected' : ''; ?>>Female</option>
                        <option value="other"  <?php echo (($_POST['gender'] ?? '') === 'other')  ? 'selected' : ''; ?>>Other / Prefer not to say</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Date of Birth <span>*</span></label>
                    <input type="date" name="date_of_birth" class="form-control"
                           value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>"
                           max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Phone Number <span style="color:var(--text-light); font-weight:400;">(optional)</span></label>
                <input type="tel" name="phone" class="form-control" placeholder="+45 12 34 56 78"
                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
            </div>

            <div class="form-group" style="margin-top:8px;">
                <label style="display:flex; gap:10px; align-items:flex-start; cursor:pointer;">
                    <input type="checkbox" required style="margin-top:3px; accent-color:var(--green);">
                    <span style="font-size:0.88rem; color:var(--text-mid);">
                        I agree to FuelWise's terms of use and acknowledge that this tool provides dietary guidance only — not medical advice.
                    </span>
                </label>
            </div>

            <button type="submit" class="btn-primary btn-block btn-lg" style="margin-top:8px;">
                Create My Account →
            </button>
        </form>

        <div class="auth-footer">
            Already have an account? <a href="<?php echo SITE_URL; ?>/login.php">Sign in here</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
