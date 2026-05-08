<?php
require_once 'includes/db.php';
session_destroy();
header('Location: ' . SITE_URL . '/index.php');
exit();
?>
