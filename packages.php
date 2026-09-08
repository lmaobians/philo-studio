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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,400..700;1,400..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <section class="packages-page-container booking-container" id="packages-display">
        <div class="step-header" style="text-align: center; margin-bottom: 2rem;">
            <p class="booking-step-label">OUR RATES & SERVICES</p>
            <h1 class="page-title">Choose Your Experience</h1>
            <p class="hero-description">From quick solo portraits to full studio rentals, we have a package built for every moment.</p>
        </div>

        <div class="chip-nav" style="margin-bottom: 2.5rem; text-align: center;">
            <?php foreach ($categorized_packages as $cat => $pkgs): ?>
                <a href="#cat-<?= md5($cat) ?>" class="chip"><?= htmlspecialchars($cat) ?></a>
            <?php endforeach; ?>
        </div>

        <?php foreach ($categorized_packages as $category_name => $packages_list): ?>
            <div class="category-block" id="cat-<?= md5($category_name) ?>">
                <p class="category-subtitle-label"><?= strtoupper(htmlspecialchars($category_name)) ?></p>
                <div class="packages-page-grid">
                    
                    <?php foreach ($packages_list as $pkg): ?>
                        <div class="package-card">
                            <div class="card-image-wrap">
                                <img src="<?= htmlspecialchars($pkg['image_url'] ?: 'images/solo-package.jpg') ?>" alt="<?= htmlspecialchars($pkg['name']) ?>">
                                <span class="img-badge">
                                    <?= htmlspecialchars($pkg['duration']) ?> ·
                                    <?= $pkg['min_pax'] == $pkg['max_pax'] ? $pkg['max_pax'] . ' person' : $pkg['min_pax'] . '–' . $pkg['max_pax'] . ' people' ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="card-head">
                                    <h3 class="pkg-title"><?= htmlspecialchars($pkg['name']) ?></h3>
                                    <span class="price">₱<?= number_format($pkg['price'], 0) ?></span>
                                </div>
                                <p class="package-tagline"><?= htmlspecialchars($pkg['category']) ?></p>
                                <p class="package-desc-text"><?= htmlspecialchars($pkg['inclusions']) ?></p>
                                
                                <form method="POST">
                                    <input type="hidden" name="select_package_id" value="<?= $pkg['package_id'] ?>">
                                    <button type="submit" class="btn-primary full-width">Book This Package &rarr;</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>

                </div>
            </div>
        <?php endforeach; ?>

    </section>
<?php include 'includes/footer.php'; ?>
</body>
</html>