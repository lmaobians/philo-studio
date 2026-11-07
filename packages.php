<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// 1. Strict Category Order requested
$category_order = ['The Classics', 'On Cloud', 'Full Grid+', 'Darkroom', 'Studio Access'];

$stmt = $db->prepare("SELECT * FROM packages WHERE status = 'active' ORDER BY price ASC");
$stmt->execute();
$all_packages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize arrays in exact ordered key sequence
$categorized_packages = [];
foreach ($category_order as $cat) {
    $categorized_packages[$cat] = [];
}

foreach ($all_packages as $pkg) {
    $cat = $pkg['category'];
    
    $p_name = strtolower($pkg['name']);
    $p_cat  = strtolower($pkg['category']);
    if (strpos($p_name, 'group') !== false || strpos($p_name, 'full grid') !== false || strpos($p_cat, 'studio access') !== false) {
        $pkg['slot_minutes'] = 60;
    } else {
        $pkg['slot_minutes'] = 30;
    }

    if (isset($categorized_packages[$cat])) {
        $categorized_packages[$cat][] = $pkg;
    } else {
        $categorized_packages[$cat] = [$pkg];
    }
}

// Filter out empty categories while keeping strict order
$categorized_packages = array_filter($categorized_packages, function($pkgs) {
    return !empty($pkgs);
});

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_package_id'])) {
    if (!isset($_SESSION['customer_id'])) {
        header("Location: login.php?redirect=" . urlencode("packages.php"));
        exit();
    }

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
    <title>Packages & Rates | PHILO Studio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,400..700;1,400..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Scoped overrides to enforce side-by-side grid layout & centered chip buttons */
        .page-title-section {
            text-align: center;
            padding: 3rem 1rem 1.5rem;
            max-width: 800px;
            margin: 0 auto;
        }
        .chip-nav-center {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1.25rem;
            margin-bottom: 3.5rem;
            flex-wrap: wrap;
        }
        .chip-btn {
            display: inline-block;
            padding: 8px 18px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            color: #333333;
            font-size: 0.88rem;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
            transition: all 0.2s ease;
        }
        .chip-btn:hover {
            background: #c46760;
            color: #ffffff;
            border-color: #c46760;
        }
        .category-subtitle-label {
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 0.85rem;
            font-weight: 700;
            color: #4a4a4a;
            margin-bottom: 2rem;
        }
        .packages-grid-horizontal {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.75rem;
            max-width: 1100px;
            margin: 0 auto;
        }
        .packages-grid-horizontal.single-item {
            grid-template-columns: minmax(300px, 360px);
            justify-content: center;
        }
        @media (max-width: 900px) {
            .packages-grid-horizontal {
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            }
        }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <section class="packages-page-container" id="packages-display">
        
        <div class="page-title-section">
            <p class="section-label">OUR RATES & SERVICES</p>
            <h1>Choose Your Experience</h1>
            <p class="hero-description">From quick solo portraits to full studio rentals, we have a package built for every moment.</p>
        </div>

        <!-- Centered Category Button Filter Chips -->
        <div class="chip-nav-center">
            <?php foreach (array_keys($categorized_packages) as $cat): ?>
                <a href="#cat-<?= md5($cat) ?>" class="chip-btn"><?= htmlspecialchars($cat) ?></a>
            <?php endforeach; ?>
        </div>

        <!-- Ordered Categories & Side-by-Side Package Grid -->
        <?php foreach ($categorized_packages as $category_name => $packages_list): ?>
            <div class="category-block" id="cat-<?= md5($category_name) ?>">
                <p class="category-subtitle-label"><?= htmlspecialchars($category_name) ?></p>
                
                <div class="packages-grid-horizontal <?= count($packages_list) === 1 ? 'single-item' : '' ?>">
                    
                    <?php foreach ($packages_list as $pkg): ?>
                        <div class="package-card">
                            
                            <div class="card-image-wrap">
                                <img src="<?= htmlspecialchars(!empty($pkg['image_url']) ? $pkg['image_url'] : 'images/solo-package.jpg') ?>" 
                                     alt="<?= htmlspecialchars($pkg['name']) ?>">
                                
                                <span class="img-badge">
                                    <?= htmlspecialchars($pkg['duration'] ?? '30 min') ?> · 
                                    <?= ($pkg['min_pax'] ?? 1) == ($pkg['max_pax'] ?? 2) 
                                        ? ($pkg['max_pax'] ?? 1) . ' person' 
                                        : ($pkg['min_pax'] ?? 1) . '–' . ($pkg['max_pax'] ?? 2) . ' people' ?>
                                </span>
                            </div>

                            <div class="card-body">
                                <div class="card-head">
                                    <h3><?= htmlspecialchars($pkg['name']) ?></h3>
                                    <span class="price">₱<?= number_format($pkg['price'], 0) ?></span>
                                </div>
                                
                                <p class="package-tagline"><?= htmlspecialchars($pkg['category']) ?></p>
                                
                                <p class="package-desc-text">
                                    <?= htmlspecialchars($pkg['inclusions'] ?? $pkg['description'] ?? '') ?>
                                </p>
                                
                                <form method="POST">
                                    <input type="hidden" name="select_package_id" value="<?= (int)$pkg['package_id'] ?>">
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