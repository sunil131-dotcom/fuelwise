<?php
require_once '../includes/db.php';
require_once '../includes/mailer.php';
if (!isset($_SESSION['admin_pending_id'])) redirect('admin/login.php');
if (isAdminLoggedIn()) redirect('admin/index.php');

$adminId = $_SESSION['admin_pending_id'];
$email   = $_SESSION['admin_pending_email'];
$name    = $_SESSION['admin_pending_name'];
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = sanitize($_POST['otp'] ?? '');

    $stmt = $conn->prepare("SELECT otp_code, otp_expires_at, otp_attempts FROM admins WHERE id = ?");
    $stmt->bind_param("i", $adminId);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($admin['otp_attempts'] >= 3) {
        $error = 'Too many failed attempts. Please log in again.';
        unset($_SESSION['admin_pending_id']);
    } elseif (strtotime($admin['otp_expires_at']) < time()) {
        $error = 'OTP has expired. Please log in again.';
        unset($_SESSION['admin_pending_id']);
    } elseif ($otp !== $admin['otp_code']) {
        $stmt = $conn->prepare("UPDATE admins SET otp_attempts = otp_attempts + 1 WHERE id = ?");
        $stmt->bind_param("i", $adminId);
        $stmt->execute();
        $stmt->close();
        $error = 'Incorrect OTP code. Please try again.';
    } else {
        $stmt = $conn->prepare("UPDATE admins SET otp_code=NULL, otp_expires_at=NULL, otp_attempts=0 WHERE id=?");
        $stmt->bind_param("i", $adminId);
        $stmt->execute();
        $stmt->close();
        $_SESSION['admin_id']       = $adminId;
        $_SESSION['admin_name']     = $name;
        $_SESSION['admin_email']    = $email;
        $_SESSION['admin_verified'] = true;
        unset($_SESSION['admin_pending_id']);
        redirect('admin/index.php');
    }
}

// Resend OTP
if (isset($_GET['resend'])) {
    $otp     = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    $stmt = $conn->prepare("UPDATE admins SET otp_code=?, otp_expires_at=?, otp_attempts=0 WHERE id=?");
    $stmt->bind_param("ssi", $otp, $expires, $adminId);
    $stmt->execute();
    $stmt->close();
    $sent = sendAdminOTPEmail($email, $name, $otp);
    if (DEV_MODE && !$sent) $_SESSION['dev_admin_otp'] = $otp;
    redirect('admin/verify-otp.php');
}

// Capture dev OTP (set by login.php or resend)
$devOTP = '';
if (DEV_MODE && isset($_SESSION['dev_admin_otp'])) {
    $devOTP = $_SESSION['dev_admin_otp'];
    unset($_SESSION['dev_admin_otp']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Verify OTP — FuelWise Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/css/style.css">
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/css/auth.css">
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/css/admin.css">
</head>
<body>
<div class="otp-page">
  <div class="otp-card">
    <div class="otp-icon">📲</div>
    <h2>Two-Step Verification</h2>
    <p>We sent a 6-digit code to <span class="email-highlight"><?php echo htmlspecialchars($email); ?></span>. Enter it below to access the admin panel.</p>
    <?php if ($error): ?><div class="alert alert-error" style="text-align:left;"><span>✗</span> <?php echo $error; ?></div><?php endif; ?>
    <?php if ($devOTP) echo devShowOTP($devOTP, 'Admin login OTP'); ?>
    <form method="POST">
      <div class="otp-inputs">
        <?php for ($i=0;$i<6;$i++): ?><input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric"><?php endfor; ?>
      </div>
      <input type="hidden" name="otp" id="otpCombined">
      <button type="submit" class="btn-primary btn-block btn-lg" style="margin-top:8px;">Verify & Login →</button>
    </form>
    <div class="resend-timer">
      Code expires in <span id="resendTimer">600</span>s &nbsp;|&nbsp;
      <a id="resendLink" href="?resend=1" style="display:none;">Resend Code</a>
    </div>
    <div style="margin-top:20px;font-size:0.85rem;">
      <a href="<?php echo SITE_URL; ?>/admin/login.php" style="color:var(--green);">← Back to Login</a>
    </div>
  </div>
</div>
<script src="<?php echo SITE_URL; ?>/js/main.js"></script>
</body></html>
