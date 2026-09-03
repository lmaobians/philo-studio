<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php?redirect=" . urlencode("booking.php"));
    exit();
}

$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare("SELECT * FROM packages WHERE status = 'active' ORDER BY category ASC, price ASC");
$stmt->execute();
$all_packages = $stmt->fetchAll();

$categorized_packages = [];
foreach ($all_packages as $pkg) {
    $p_name = strtolower($pkg['name']);
    $p_cat  = strtolower($pkg['category']);
    
    if (strpos($p_name, 'group') !== false || strpos($p_name, 'full grid') !== false || strpos($p_cat, 'studio access') !== false) {
        $pkg['slot_minutes'] = 60;
    } else {
        $pkg['slot_minutes'] = 30;
    }
    
    $categorized_packages[$pkg['category']][] = $pkg;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_package_id'])) {
    $pkg_id = (int)$_POST['select_package_id'];
    
    foreach ($all_packages as $p) {
        if ($p['package_id'] == $pkg_id) {
            $_SESSION['booking_package'] = $p;
            $_SESSION['booking_package']['slot_minutes'] = $p['slot_minutes'] ?? 30;
            break;
        }
    }
    header("Location: booking_step2.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Package | PHILO Studio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <div class="booking-container">
        <div class="step-header">
            <p class="booking-step-label">Step 1 of 4</p>
            <h1>Choose Your Experience</h1>
            <p>Select a photography package to proceed to date & scheduling</p>
        </div>

        <div class="chip-nav">
            <?php foreach ($categorized_packages as $cat => $pkgs): ?>
                <a href="#cat-<?= md5($cat) ?>" class="chip"><?= htmlspecialchars($cat) ?></a>
            <?php endforeach; ?>
        </div>

        <?php foreach ($categorized_packages as $cat => $pkgs): ?>
            <div class="category-section" id="cat-<?= md5($cat) ?>">
                <div class="category-title"><?= htmlspecialchars($cat) ?></div>
                <div class="grid-2col">
                    <?php foreach ($pkgs as $pkg): ?>
                        <div class="pkg-card">
                            <img src="<?= htmlspecialchars($pkg['image_url'] ?: 'images/solo-package.jpg') ?>" alt="Package Image" class="pkg-img">
                            <div class="pkg-content">
                                <div>
                                    <div class="pkg-head">
                                        <h3><?= htmlspecialchars($pkg['name']) ?></h3>
                                        <span class="pkg-price">₱<?= number_format($pkg['price'], 0) ?></span>
                                    </div>
                                    <span class="pkg-badge">⏱ <?= htmlspecialchars($pkg['duration']) ?></span>
                                    <div class="pkg-desc"><?= htmlspecialchars($pkg['inclusions']) ?></div>
                                </div>
                                <form method="POST">
                                    <input type="hidden" name="select_package_id" value="<?= $pkg['package_id'] ?>">
                                    <button type="submit" class="btn-primary full-width package-select-button">Select Package &rarr;</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</body>
</html>