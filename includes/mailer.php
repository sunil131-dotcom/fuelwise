<?php
// =====================================================================
// FuelWise - Email Sending (PHPMailer + Gmail SMTP)
// =====================================================================
// This version reports honest success/failure.
// A log is written to /logs/mail.log so you can diagnose delivery problems.
// In DEV_MODE, OTPs/reset links are returned so they can be shown on screen.
// =====================================================================

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Ensure logs directory exists
if (!is_dir(__DIR__ . '/../logs')) {
    @mkdir(__DIR__ . '/../logs', 0755, true);
}

function logMail($message) {
    $logFile = __DIR__ . '/../logs/mail.log';
    $ts = date('Y-m-d H:i:s');
    @file_put_contents($logFile, "[$ts] $message\n", FILE_APPEND);
}

/**
 * Send an email.
 * @return bool True on success, false on failure. Check logs/mail.log for details.
 */
function sendEmail($to, $toName, $subject, $body) {
    $composerAutoload = __DIR__ . '/../vendor/autoload.php';
    $manualSrc        = __DIR__ . '/../phpmailer/src/';

    if (file_exists($composerAutoload)) {
        require_once $composerAutoload;
    } elseif (file_exists($manualSrc . 'PHPMailer.php')) {
        require_once $manualSrc . 'PHPMailer.php';
        require_once $manualSrc . 'SMTP.php';
        require_once $manualSrc . 'Exception.php';
    } else {
        logMail("PHPMailer not found — falling back to PHP mail()");
        $headers  = "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $ok = @mail($to, $subject, $body, $headers);
        logMail("PHP mail() to $to → " . ($ok ? 'SENT' : 'FAILED'));
        return $ok;
    }

    // Guard against placeholder password (common setup mistake)
    if (MAIL_PASSWORD === 'your_app_password' || MAIL_PASSWORD === '') {
        logMail("⚠ MAIL_PASSWORD is placeholder ('your_app_password') — email cannot be sent. Set your Gmail App Password in includes/db.php.");
        return false;
    }

    $mail = new PHPMailer(true);
    try {
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // uncomment to see raw SMTP
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($to, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        // Plain-text alternative for spam scoring
        $mail->AltBody = strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $body));

        $mail->send();
        logMail("SMTP to $to → SENT ($subject)");
        return true;
    } catch (Exception $e) {
        logMail("SMTP to $to → FAILED: " . $mail->ErrorInfo);
        return false;
    }
}

// =====================================================================
// Templated emails
// =====================================================================

function emailWrapper($title, $innerHtml) {
    $year = date('Y');
    return "
    <div style='font-family: DM Sans, Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f8f9fa;'>
        <div style='background: linear-gradient(135deg, #1a5276, #27ae60); padding: 40px; text-align: center;'>
            <h1 style='color: white; margin: 0; font-size: 28px;'>🌿 FuelWise</h1>
            <p style='color: rgba(255,255,255,0.85); margin: 8px 0 0; font-size: 14px;'>AI-Powered Personalized Nutrition</p>
        </div>
        <div style='background: white; padding: 40px;'>
            $innerHtml
        </div>
        <div style='background: #f0f4f8; padding: 20px; text-align: center;'>
            <p style='color: #999; font-size: 12px; margin: 0;'>© $year FuelWise. Decision-support tool only — always consult your healthcare provider.</p>
        </div>
    </div>";
}

function sendPasswordResetEmail($to, $toName, $resetLink) {
    $inner = "
        <h2 style='color: #1a5276; margin-top: 0;'>Password Reset Request</h2>
        <p style='color: #555; line-height: 1.6;'>Hi <strong>{$toName}</strong>,</p>
        <p style='color: #555; line-height: 1.6;'>We received a request to reset your FuelWise account password. Click the button below to create a new password. This link is valid for <strong>15 minutes only</strong>.</p>
        <div style='text-align: center; margin: 32px 0;'>
            <a href='{$resetLink}' style='background: linear-gradient(135deg, #1a5276, #27ae60); color: white; padding: 14px 32px; text-decoration: none; border-radius: 8px; font-size: 16px; font-weight: 600; display: inline-block;'>Reset My Password</a>
        </div>
        <p style='color: #888; font-size: 13px;'>If you didn't request this, you can safely ignore this email. Your password will not change.</p>
        <p style='color: #888; font-size: 13px;'>Or copy this link: <a href='{$resetLink}' style='color: #27ae60; word-break: break-all;'>{$resetLink}</a></p>";
    return sendEmail($to, $toName, 'Reset Your FuelWise Password', emailWrapper('Password Reset', $inner));
}

function sendAdminOTPEmail($to, $toName, $otp) {
    $inner = "
        <h2 style='color: #1a5276; margin-top: 0;'>Your One-Time Password</h2>
        <p style='color: #555; line-height: 1.6;'>Hi <strong>{$toName}</strong>,</p>
        <p style='color: #555; line-height: 1.6;'>Your FuelWise Admin login OTP code is:</p>
        <div style='text-align: center; margin: 32px 0;'>
            <div style='background: #f0f7ff; border: 2px dashed #1a5276; border-radius: 12px; display: inline-block; padding: 20px 48px;'>
                <span style='font-size: 42px; font-weight: 900; color: #1a5276; letter-spacing: 10px;'>{$otp}</span>
            </div>
        </div>
        <p style='color: #888; font-size: 13px; text-align: center;'>⏱ This code expires in <strong>10 minutes</strong>. Do not share it with anyone.</p>";
    return sendEmail($to, $toName, 'FuelWise Admin - Your OTP Code', emailWrapper('OTP', $inner));
}

function sendWelcomeEmail($to, $toName) {
    $assessmentLink = SITE_URL . '/assessment.php';
    $inner = "
        <h2 style='color: #1a5276; margin-top: 0;'>Welcome to FuelWise, {$toName}! 🌱</h2>
        <p style='color: #555; line-height: 1.6;'>Thank you for joining FuelWise — your AI-powered personalized nutrition companion.</p>
        <p style='color: #555; line-height: 1.6;'>To receive your tailored diet plan, please complete your health assessment:</p>
        <div style='text-align: center; margin: 32px 0;'>
            <a href='{$assessmentLink}' style='background: linear-gradient(135deg, #1a5276, #27ae60); color: white; padding: 14px 32px; text-decoration: none; border-radius: 8px; font-size: 16px; font-weight: 600; display: inline-block;'>Start Your Assessment →</a>
        </div>
        <p style='color: #888; font-size: 13px;'>⚕️ FuelWise is a decision-support tool and not a substitute for medical advice.</p>";
    return sendEmail($to, $toName, 'Welcome to FuelWise 🌿', emailWrapper('Welcome', $inner));
}

function sendStreakEmail($to, $toName, $streak) {
    $dashboardLink = SITE_URL . '/dashboard.php';
    $inner = "
        <h2 style='color: #27ae60; margin-top: 0;'>🔥 {$streak}-Day Streak, {$toName}!</h2>
        <p style='color: #555; line-height: 1.6;'>Amazing consistency! You've checked in with FuelWise <strong>{$streak} days in a row</strong>. Small daily habits lead to big health wins.</p>
        <div style='text-align: center; margin: 32px 0;'>
            <a href='{$dashboardLink}' style='background: linear-gradient(135deg, #1a5276, #27ae60); color: white; padding: 14px 32px; text-decoration: none; border-radius: 8px; font-size: 16px; font-weight: 600; display: inline-block;'>View Today's Plan</a>
        </div>";
    return sendEmail($to, $toName, "🔥 You're on a {$streak}-day streak!", emailWrapper('Streak', $inner));
}

function sendInactivityEmail($to, $toName, $daysAway) {
    $dashboardLink = SITE_URL . '/dashboard.php';
    $inner = "
        <h2 style='color: #1a5276; margin-top: 0;'>We miss you, {$toName} 🌿</h2>
        <p style='color: #555; line-height: 1.6;'>It's been <strong>{$daysAway} days</strong> since your last visit. Your personalized plan is waiting whenever you're ready — no pressure, no judgment.</p>
        <p style='color: #555; line-height: 1.6;'>Small steps matter, and you can pick up right where you left off.</p>
        <div style='text-align: center; margin: 32px 0;'>
            <a href='{$dashboardLink}' style='background: linear-gradient(135deg, #1a5276, #27ae60); color: white; padding: 14px 32px; text-decoration: none; border-radius: 8px; font-size: 16px; font-weight: 600; display: inline-block;'>See My Plan</a>
        </div>";
    return sendEmail($to, $toName, 'Your FuelWise plan is waiting 🌿', emailWrapper('Come Back', $inner));
}

// =====================================================================
// DEV MODE — on-screen OTP/token display helpers
// When DEV_MODE is true and email sending fails (or hasn't been configured),
// these helpers render a visible box on the page with the credentials
// so you can test the authentication flows without a working SMTP.
// DISABLE BY SETTING DEV_MODE=false IN PRODUCTION.
// =====================================================================
function devShowOTP($otp, $context = 'OTP') {
    if (!DEV_MODE) return '';
    return "
    <div style='background:#fff3cd;border:2px dashed #e67e22;border-radius:8px;padding:16px;margin:20px 0;text-align:center;'>
        <p style='margin:0 0 8px;font-size:0.85rem;color:#8b5d00;'>⚙ <strong>DEV MODE</strong> — email not sent or unavailable. {$context}:</p>
        <p style='margin:0;font-size:1.8rem;font-weight:900;color:#1a5276;letter-spacing:8px;'>{$otp}</p>
        <p style='margin:8px 0 0;font-size:0.75rem;color:#8b5d00;'>Disable by setting <code>DEV_MODE=false</code> in <code>includes/db.php</code>.</p>
    </div>";
}

function devShowLink($link, $context = 'Reset link') {
    if (!DEV_MODE) return '';
    return "
    <div style='background:#fff3cd;border:2px dashed #e67e22;border-radius:8px;padding:16px;margin:20px 0;'>
        <p style='margin:0 0 8px;font-size:0.85rem;color:#8b5d00;'>⚙ <strong>DEV MODE</strong> — email not sent. {$context}:</p>
        <p style='margin:0;font-size:0.9rem;word-break:break-all;'><a href='{$link}' style='color:#1a5276;'>{$link}</a></p>
        <p style='margin:8px 0 0;font-size:0.75rem;color:#8b5d00;'>Disable by setting <code>DEV_MODE=false</code> in <code>includes/db.php</code>.</p>
    </div>";
}
?>
