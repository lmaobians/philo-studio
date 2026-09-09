<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);

function isActive($target_pages, $current_page) {
    if (is_array($target_pages)) {
        return in_array($current_page, $target_pages) ? 'active' : '';
    }
    return ($current_page === $target_pages) ? 'active' : '';
}
?>

<link rel="stylesheet" href="style.css">
<header class="navbar">
    <a href="philostudio.php" class="header-logo">
        <img src="images/header-logo.jpg" alt="Philo Studio Logo">
    </a>

    <button class="menu-toggle" id="menuToggle" aria-label="Toggle Navigation">
        <span></span>
        <span></span>
        <span></span>
    </button>

    <div class="nav-menu" id="navMenu">
        <nav class="nav-links">
            <a href="philostudio.php" class="<?= isActive(['philostudio.php', 'index.php'], $current_page) ?>">Home</a>
            <a href="packages.php" class="<?= isActive(['packages.php', 'booking_step1.php', 'booking_step2.php', 'booking_step3.php', 'booking_step4.php'], $current_page) ?>">Packages</a>
            <a href="gallery.php" class="<?= isActive('gallery.php', $current_page) ?>">Gallery</a>
            <a href="about.php" class="<?= isActive('about.php', $current_page) ?>">About</a>
            <a href="faq.php" class="<?= isActive('faq.php', $current_page) ?>">FAQ</a>
            <a href="contact.php" class="<?= isActive('contact.php', $current_page) ?>">Contact</a>
            
            <?php if (isset($_SESSION['customer_id'])): ?>
                <a href="dashboard.php" class="<?= isActive('dashboard.php', $current_page) ?>">My Account</a>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php" class="<?= isActive('login.php', $current_page) ?>">Login</a>
            <?php endif; ?>
        </nav>

        <a href="packages.php" class="btn-primary">BOOK NOW</a>

        <script>
    document.addEventListener('DOMContentLoaded', () => {
        const menuToggle = document.getElementById('menuToggle');
        const navMenu = document.getElementById('navMenu');

        if (menuToggle && navMenu) {
        menuToggle.addEventListener('click', () => {
            menuToggle.classList.toggle('active');
            navMenu.classList.toggle('active');
        });
        }
    });
</script>
    </div>
</header>