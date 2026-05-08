<?php
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/db.php';
}
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?php echo isset($pageTitle) ? $pageTitle . ' — FuelWise' : 'FuelWise — AI-Powered Personalized Nutrition'; ?></title>
    <meta name="description" content="FuelWise uses artificial intelligence to deliver personalized diet and exercise recommendations for people with diabetes, pre-diabetes, hypertension, IBS, and weight management goals."/>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700;900&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/css/style.css"/>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/css/fuelwise-v2.css"/>
    <?php if (isset($extraCSS)): ?>
        <link rel="stylesheet" href="<?php echo SITE_URL; ?>/css/<?php echo $extraCSS; ?>"/>
    <?php endif; ?>
</head>
<body>

<nav class="navbar" id="navbar">
    <div class="nav-container">
        <a href="<?php echo SITE_URL; ?>/index.php" class="nav-logo">
            <span class="logo-icon">🌿</span>
            Fuel<span class="logo-accent">Wise</span>
        </a>

        <ul class="nav-menu" id="navMenu">
            <li><a href="<?php echo SITE_URL; ?>/index.php" class="nav-link <?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">Home</a></li>
            <li><a href="<?php echo SITE_URL; ?>/about.php" class="nav-link <?php echo $currentPage === 'about.php' ? 'active' : ''; ?>">About</a></li>
            <?php if (isLoggedIn()): ?>
                <li><a href="<?php echo SITE_URL; ?>/assessment.php" class="nav-link <?php echo $currentPage === 'assessment.php' ? 'active' : ''; ?>">Assessment</a></li>
                <li><a href="<?php echo SITE_URL; ?>/recommendation.php" class="nav-link <?php echo $currentPage === 'recommendation.php' ? 'active' : ''; ?>">Diet Plan</a></li>
                <li><a href="<?php echo SITE_URL; ?>/dashboard.php" class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a></li>
                <li><a href="<?php echo SITE_URL; ?>/logout.php" class="nav-link nav-logout">Logout</a></li>
            <?php else: ?>
                <li><a href="<?php echo SITE_URL; ?>/login.php" class="nav-link <?php echo $currentPage === 'login.php' ? 'active' : ''; ?>">Login</a></li>
                <li><a href="<?php echo SITE_URL; ?>/register.php" class="btn-nav <?php echo $currentPage === 'register.php' ? 'active' : ''; ?>">Get Started</a></li>
            <?php endif; ?>
        </ul>

        <button class="hamburger" id="hamburger" aria-label="Toggle menu">
            <span></span><span></span><span></span>
        </button>
    </div>
</nav>
