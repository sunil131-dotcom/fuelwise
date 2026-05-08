<?php
require_once '../includes/db.php';
require_once '../includes/mailer.php';
if (isAdminLoggedIn()) redirect('admin/index.php');

$error = '';
$devOTP = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM admins WHERE email = ? AND is_active = 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$admin || !password_verify($password, $admin['password'])) {
        $error = 'Invalid email or password.';
    } else {
        // Generate OTP
        $otp     = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        $stmt = $conn->prepare("UPDATE admins SET otp_code=?, otp_expires_at=?, otp_attempts=0 WHERE id=?");
        $stmt->bind_param("ssi", $otp, $expires, $admin['id']);
        $stmt->execute();
        $stmt->close();

        $sent = sendAdminOTPEmail($admin['email'], $admin['full_name'], $otp);

        $_SESSION['admin_pending_id']    = $admin['id'];
        $_SESSION['admin_pending_email'] = $admin['email'];
        $_SESSION['admin_pending_name']  = $admin['full_name'];

        // In DEV_MODE, if email failed, stash OTP for the next page
        if (DEV_MODE && !$sent) {
            $_SESSION['dev_admin_otp'] = $otp;
        }

        redirect('admin/verify-otp.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Admin Login — FuelWise</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/css/style.css">
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/css/auth.css">
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/css/admin.css">
</head>
<body>
<div class="otp-page">
  <div class="otp-card" style="text-align:left;">
    <div style="text-align:center;margin-bottom:28px;">
      <div style="font-size:2.5rem;margin-bottom:8px;">🔐</div>
      <h2 style="color:var(--navy-dark);">Admin Login</h2>
      <p style="color:var(--text-light);font-size:0.9rem;">FuelWise Administration Panel</p>
    </div>
    <?php if ($error): ?><div class="alert alert-error"><span>✗</span> <?php echo $error; ?></div><?php endif; ?>
    <form method="POST">
      <div class="form-group">
        <label class="form-label">Admin Email</label>
        <input type="email" name="email" class="form-control" placeholder="admin@fuelwise.com" required autofocus value="<?php echo htmlspecialchars($_POST['email']??''); ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <div class="password-toggle">
          <input type="password" name="password" class="form-control" placeholder="Admin password" required>
          <button type="button" class="toggle-btn">👁️</button>
        </div>
      </div>
      <button type="submit" class="btn-primary btn-block btn-lg" style="margin-top:8px;">Continue →</button>
    </form>
    <div style="text-align:center;margin-top:20px;font-size:0.85rem;color:var(--text-light);">
      <a href="<?php echo SITE_URL; ?>/index.php" style="color:var(--green);">← Back to Website</a>
    </div>
  </div>
</div>
<script src="<?php echo SITE_URL; ?>/js/main.js"></script>
</body></html>
