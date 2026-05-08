<?php
require_once 'includes/db.php';
require_once 'includes/mailer.php';
if (isLoggedIn()) redirect('dashboard.php');

$message = '';
$msgType = '';
$devLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $msgType = 'error';
    } else {
        $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE email = ? AND is_active = 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $message = 'If that email is registered, a password reset link has been sent. Please check your inbox.';
        $msgType = 'success';

        if ($user) {
            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

            $stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt->bind_param("s", $email); $stmt->execute(); $stmt->close();

            $stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $email, $token, $expires);
            $stmt->execute(); $stmt->close();

            $resetLink = SITE_URL . '/reset-password.php?token=' . $token . '&email=' . urlencode($email);
            $sent = sendPasswordResetEmail($email, $user['full_name'], $resetLink);

            // In DEV_MODE, if email failed (or always for visibility in testing), show the link on-screen
            if (DEV_MODE && !$sent) {
                $devLink = $resetLink;
            }
        }
    }
}

$pageTitle = 'Forgot Password';
$extraCSS  = 'auth.css';
include 'includes/header.php';
?>

<div class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">
            <a href="<?php echo SITE_URL; ?>/index.php">🌿 Fuel<span>Wise</span></a>
        </div>
        <div class="auth-title">
            <h2>Reset Your Password</h2>
            <p>Enter your email and we'll send you a reset link valid for 15 minutes.</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $msgType; ?>">
                <span><?php echo $msgType === 'success' ? '✓' : '✗'; ?></span> <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($devLink)) echo devShowLink($devLink, 'Password reset link'); ?>

        <?php if ($msgType !== 'success'): ?>
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control"
                       placeholder="Enter your registered email"
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                       required autofocus>
            </div>
            <button type="submit" class="btn-primary btn-block btn-lg">Send Reset Link →</button>
        </form>
        <?php endif; ?>

        <div class="auth-footer" style="margin-top:20px;">
            <a href="<?php echo SITE_URL; ?>/login.php">← Back to Login</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
