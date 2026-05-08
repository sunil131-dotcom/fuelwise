<?php
require_once 'includes/db.php';
if (isLoggedIn()) redirect('dashboard.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter your email and password.';
    } else {
        $stmt = $conn->prepare("SELECT id, full_name, email, password, is_active FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $error = 'No account found with this email address.';
        } elseif (!$user['is_active']) {
            $error = 'Your account has been deactivated. Please contact support.';
        } elseif (!password_verify($password, $user['password'])) {
            $error = 'Incorrect password. Please try again.';
        } else {
            // -- Store session --
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_name']  = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];

            // -- Also cache gender for the recommendation engine --
            $stmt2 = $conn->prepare("SELECT gender FROM users WHERE id=?");
            $stmt2->bind_param("i", $user['id']);
            $stmt2->execute();
            $_SESSION['gender'] = ($stmt2->get_result()->fetch_assoc()['gender'] ?? 'male');
            $stmt2->close();

            // -- Update last_login timestamp --
            $now = date('Y-m-d H:i:s');
            $stmt2 = $conn->prepare("UPDATE users SET last_login=? WHERE id=?");
            $stmt2->bind_param("si", $now, $user['id']);
            $stmt2->execute();
            $stmt2->close();

            // -- Update streak + trigger engagement notifications --
            updateUserStreak($user['id']);
            triggerEngagementNotifications($user['id']);

            setFlash('success', 'Welcome back, ' . $user['full_name'] . '!');
            redirect('dashboard.php');
        }
    }
}

$pageTitle = 'Login';
$extraCSS  = 'auth.css';
include 'includes/header.php';
?>

<div class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">
            <a href="<?php echo SITE_URL; ?>/index.php">🌿 Fuel<span>Wise</span></a>
        </div>
        <div class="auth-title">
            <h2>Welcome Back</h2>
            <p>Sign in to access your personalised diet plan</p>
        </div>

        <?php showFlash(); ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><span>✗</span> <?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="john@example.com"
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="password-toggle">
                    <input type="password" name="password" class="form-control" placeholder="Your password" required>
                    <button type="button" class="toggle-btn">👁️</button>
                </div>
            </div>

            <div class="forgot-link">
                <a href="<?php echo SITE_URL; ?>/forgot-password.php">Forgot your password?</a>
            </div>

            <button type="submit" class="btn-primary btn-block btn-lg">Sign In →</button>
        </form>

        <div class="auth-footer">
            Don't have an account? <a href="<?php echo SITE_URL; ?>/register.php">Create one free</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
