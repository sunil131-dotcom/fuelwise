<?php
require_once 'includes/db.php';
if (isLoggedIn()) redirect('dashboard.php');

$token    = sanitize($_GET['token'] ?? '');
$email    = sanitize($_GET['email'] ?? '');
$error    = '';
$success  = false;
$validToken = false;

if (empty($token) || empty($email)) {
    $error = 'Invalid reset link. Please request a new one.';
} else {
    $stmt = $conn->prepare("SELECT * FROM password_resets WHERE email = ? AND token = ? AND expires_at > NOW()");
    $stmt->bind_param("ss", $email, $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $validToken = $result->num_rows > 0;
    $stmt->close();
    if (!$validToken) {
        $error = 'This reset link is invalid or has expired. Please request a new one.';
    }
}

if ($validToken && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hash, $email);
        $stmt->execute();
        $stmt->close();

        // Delete used token
        $stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->close();

        $success = true;
    }
}

$pageTitle = 'Reset Password';
$extraCSS  = 'auth.css';
include 'includes/header.php';
?>

<div class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">
            <a href="<?php echo SITE_URL; ?>/index.php">🌿 Fuel<span>Wise</span></a>
        </div>

        <?php if ($success): ?>
            <div style="text-align:center; padding: 20px 0;">
                <div style="font-size:3rem; margin-bottom:16px;">✅</div>
                <h2 style="color:var(--navy-dark); margin-bottom:12px;">Password Updated!</h2>
                <p style="color:var(--text-mid); margin-bottom:24px;">Your password has been successfully reset. You can now log in with your new password.</p>
                <a href="<?php echo SITE_URL; ?>/login.php" class="btn-primary btn-block btn-lg">Go to Login →</a>
            </div>
        <?php elseif ($error && !$validToken): ?>
            <div class="auth-title">
                <h2>Invalid Link</h2>
            </div>
            <div class="alert alert-error"><span>✗</span> <?php echo $error; ?></div>
            <a href="<?php echo SITE_URL; ?>/forgot-password.php" class="btn-primary btn-block" style="display:block; text-align:center; padding:12px;">Request New Reset Link</a>
        <?php else: ?>
            <div class="auth-title">
                <h2>Set New Password</h2>
                <p>Choose a strong password for your account.</p>
            </div>
            <?php if ($error): ?>
                <div class="alert alert-error"><span>✗</span> <?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST" action="?token=<?php echo urlencode($token); ?>&email=<?php echo urlencode($email); ?>">
                <div class="form-group">
                    <label class="form-label">New Password <span>*</span></label>
                    <div class="password-toggle">
                        <input type="password" name="password" id="password" class="form-control" placeholder="Min. 8 characters" required>
                        <button type="button" class="toggle-btn">👁️</button>
                    </div>
                    <div class="password-strength"><div class="password-strength-bar" id="strengthBar"></div></div>
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm New Password <span>*</span></label>
                    <div class="password-toggle">
                        <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password" required>
                        <button type="button" class="toggle-btn">👁️</button>
                    </div>
                </div>
                <button type="submit" class="btn-primary btn-block btn-lg">Update Password →</button>
            </form>
        <?php endif; ?>

        <div class="auth-footer" style="margin-top:20px;">
            <a href="<?php echo SITE_URL; ?>/login.php">← Back to Login</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
