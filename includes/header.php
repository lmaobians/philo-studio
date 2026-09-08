<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
?>

<link rel="stylesheet" href="style.css">
<header class="navbar">
    <a href="philostudio.php" class="header-logo">
        <img src="images/header-logo.jpg" alt="Philo Studio Logo">
    </a>

    <!-- Hamburger Icon for Mobile -->
    <button class="menu-toggle" id="menuToggle" aria-label="Toggle Navigation">
        <span></span>
        <span></span>
        <span></span>
    </button>

    <!-- Collapsible Menu Container -->
    <div class="nav-menu" id="navMenu">
        <nav class="nav-links">
            <a href="philostudio.php" class="<?= ($current_page == 'philostudio.php') ? 'active' : '' ?>">Home</a>
            <a href="packages.php" class="<?= ($current_page == 'booking_step1.php') ? 'active' : '' ?>">Packages</a>
            <a href="gallery.php">Gallery</a>
            <a href="about.php">About</a>
            <a href="faq.php">FAQ</a>
            <a href="contact.php">Contact</a>
            <?php if (isset($_SESSION['customer_id'])): ?>
                <a href="dashboard.php" class="<?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">My Account</a>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php" class="<?= ($current_page == 'login.php') ? 'active' : '' ?>">Login</a>
            <?php endif; ?>
        </nav>

        <a href="packages.php" class="btn-primary">BOOK NOW</a>
    </div>
</header>

<script>
    document.getElementById('menuToggle').addEventListener('click', function() {
        this.classList.toggle('active');
        document.getElementById('navMenu').classList.toggle('active');
    });
</script>