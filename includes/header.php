<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<header class="navbar">
    <a href="philostudio.php" class="header-logo">
        <img src="images/header-logo.jpg" alt="Philo Studio Logo">
    </a>

    <nav class="nav-links">
        <a href="philostudio.php" class="<?= ($current_page == 'philostudio.php') ? 'active' : '' ?>">Home</a>
        <a href="packages.php" class="<?= ($current_page == 'packages.php') ? 'active' : '' ?>">Packages</a>
        <a href="gallery.php">Gallery</a>
        <a href="#about">About</a>
        <a href="#faq">FAQ</a>
        <a href="#contact">Contact</a>
        <?php if (isset($_SESSION['customer_id'])): ?>
            <a href="dashboard.php" class="<?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">My Account</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php" class="<?= ($current_page == 'login.php') ? 'active' : '' ?>">Login</a>
        <?php endif; ?>
    </nav>

    <a href="packages.php" class="btn-primary">BOOK NOW</a>
</header>