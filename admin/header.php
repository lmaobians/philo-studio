<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$database = new Database();
$db = $database->getConnection();

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) : 'Admin Control Center'; ?> | PHILO Studio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
</head>
<body class="admin-page">

    <nav class="admin-nav">
        <div class="admin-brand">
            <span>ADMIN CONTROL CENTER</span>
        </div>
        
        <div class="admin-nav-links">
            <a href="dashboard.php" class="nav-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">Bookings</a>
            <a href="daily_schedule.php" class="nav-link <?= $current_page === 'daily_schedule.php' ? 'active' : '' ?>">Daily Schedule</a>
            <a href="packages_manage.php" class="nav-link <?= $current_page === 'packages_manage.php' ? 'active' : '' ?>">Packages</a>
            <a href="customers.php" class="nav-link <?= $current_page === 'customers.php' ? 'active' : '' ?>">Customers</a>
        </div>

        <div class="topbar-profile">
            <div class="profile-info">
                <span class="user-role">Administrator</span>
                <span class="user-name"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin Studio'); ?></span>
            </div>
            <a href="logout.php" class="btn-logout" title="Log Out">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                <span>Log Out</span>
            </a>
        </div>
    </nav>

    <div class="admin-container">

        <?php if (isset($_SESSION['admin_msg'])): ?>
            <div class="alert-toast">
                <?= $_SESSION['admin_msg']; unset($_SESSION['admin_msg']); ?>
            </div>
        <?php endif; ?>