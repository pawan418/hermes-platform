<?php
// pms_v2/index.php - Front-controller for the Project Management System
require_once __DIR__ . '/db.php';

// Fetch all settings
$settings_query = $db->query("SELECT key, value FROM settings");
$site = [];
while ($row = $settings_query->fetch()) {
    $site[$row['key']] = $row['value'];
}

$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('LSPL_SECURE_ROUTE', true);

// Auth check
if (isset($_SESSION['pms_logged_in']) && $_SESSION['pms_logged_in'] === true) {
    include __DIR__ . '/dashboard.php';
} else {
    include __DIR__ . '/login.php';
}
exit;
?>
