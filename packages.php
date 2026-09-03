<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM packages WHERE status = 'active' ORDER BY category ASC, price ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$all_packages = $stmt->fetchAll();

$categorized_packages = [];
foreach ($all_packages as $pkg) {
    $categorized_packages[$pkg['category']][] = $pkg;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Packages | Philo Studio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,400..700;1,400..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <section class="page-title-section">
        <p class="section-label">OUR RATES & SERVICES</p>
        <h1>Choose Your Experience</h1>
        <p class="page-subtitle">From quick solo portraits to full studio rentals, we have a package built for every moment.</p>
    </section>

    <section class="packages-page-container" id="packages-display">

        <?php foreach ($categorized_packages as $category_name => $packages_list): ?>
            <div class="category-block">
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
                                    <h3><?= htmlspecialchars($pkg['name']) ?></h3>
                                    <span class="price">₱<?= number_format($pkg['price'], 0) ?></span>
                                </div>
                                <p class="package-tagline"><?= htmlspecialchars($pkg['category']) ?></p>
                                <p class="package-desc-text"><?= htmlspecialchars($pkg['inclusions']) ?></p>
                                
                                <?php if (isset($_SESSION['customer_id'])): ?>
                                    <a href="booking.php?package_id=<?= $pkg['package_id'] ?>" class="btn-primary full-width">Book this package &rarr;</a>
                                <?php else: ?>
                                    <a href="login.php?redirect=<?= urlencode('booking.php?package_id=' . $pkg['package_id']) ?>" class="btn-primary full-width">Book this package &rarr;</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                </div>
            </div>
        <?php endforeach; ?>

    </section>

</body>
</html>