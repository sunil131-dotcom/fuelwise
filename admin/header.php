<?php
require_once '../includes/db.php';
requireAdmin();
$currentAdminPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?php echo isset($adminTitle) ? $adminTitle.' — FuelWise Admin' : 'FuelWise Admin'; ?></title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/css/style.css">
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/css/admin.css">
</head>
<body>
<div class="admin-layout">
<aside class="admin-sidebar" id="adminSidebar">
  <div class="sidebar-brand">
    <a href="<?php echo SITE_URL; ?>/admin/index.php">🌿 Fuel<span>Wise</span></a>
    <div class="sidebar-admin-badge">Admin Panel</div>
  </div>
  <nav class="sidebar-nav">
    <div class="sidebar-section">Main</div>
    <a href="<?php echo SITE_URL; ?>/admin/index.php" class="sidebar-link <?php echo $currentAdminPage==='index.php'?'active':''; ?>"><span class="sidebar-icon">📊</span> Dashboard</a>
    <div class="sidebar-section">Management</div>
    <a href="<?php echo SITE_URL; ?>/admin/users.php" class="sidebar-link <?php echo $currentAdminPage==='users.php'?'active':''; ?>"><span class="sidebar-icon">👥</span> Users</a>
    <a href="<?php echo SITE_URL; ?>/admin/recommendations.php" class="sidebar-link <?php echo $currentAdminPage==='recommendations.php'?'active':''; ?>"><span class="sidebar-icon">🥗</span> Recommendations</a>
    <a href="<?php echo SITE_URL; ?>/admin/foods.php" class="sidebar-link <?php echo $currentAdminPage==='foods.php'?'active':''; ?>"><span class="sidebar-icon">🍽️</span> Food Database</a>
    <a href="<?php echo SITE_URL; ?>/admin/exercises.php" class="sidebar-link <?php echo $currentAdminPage==='exercises.php'?'active':''; ?>"><span class="sidebar-icon">💪</span> Exercises</a>
    <div class="sidebar-section">System</div>
    <a href="<?php echo SITE_URL; ?>/index.php" class="sidebar-link" target="_blank"><span class="sidebar-icon">🌐</span> View Website</a>
  </nav>
  <div class="sidebar-footer">
    <a href="<?php echo SITE_URL; ?>/admin/logout.php"><span>🚪</span> Logout (<?php echo htmlspecialchars($_SESSION['admin_name']); ?>)</a>
  </div>
</aside>
<div class="admin-main">
<div class="admin-topbar">
  <div style="display:flex;align-items:center;gap:12px;">
    <button id="sidebarToggle" style="background:none;border:none;cursor:pointer;font-size:1.2rem;display:none;">☰</button>
    <h1><?php echo isset($adminTitle) ? $adminTitle : 'Dashboard'; ?></h1>
  </div>
  <div class="topbar-right">
    <?php showFlash(); ?>
    <div class="admin-avatar"><?php echo strtoupper(substr($_SESSION['admin_name'],0,1)); ?></div>
    <span style="font-size:0.88rem;color:var(--text-mid);"><?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
  </div>
</div>
<div class="admin-content">
