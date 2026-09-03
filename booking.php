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

// Fetch packages
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

// Handle Package Selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_package_id'])) {
    $pkg_id = (int)$_POST['select_package_id'];
    
    // Find package details
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
    <style>
        .booking-container { max-width: 1100px; margin: 40px auto; padding: 0 20px; font-family: 'Inter', sans-serif; }
        .step-header { text-align: center; margin-bottom: 30px; }
        .step-header h1 { font-size: 2rem; margin-bottom: 5px; }
        .step-header p { color: #666; font-size: 0.95rem; }
        
        .chip-nav { display: flex; justify-content: center; gap: 10px; flex-wrap: wrap; margin-bottom: 35px; }
        .chip { background: #f0f0f0; border-radius: 20px; padding: 8px 18px; text-decoration: none; color: #333; font-size: 0.85rem; font-weight: 600; }
        .chip:hover { background: #111; color: #fff; }

        .category-section { margin-bottom: 40px; }
        .category-title { font-size: 1.1rem; text-transform: uppercase; letter-spacing: 1.5px; color: #888; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 8px; }
        
        /* Side-by-Side Grid Layout */
        .grid-2col { display: grid; grid-template-columns: repeat(auto-fill, minmax(480px, 1fr)); gap: 20px; }
        @media (max-width: 600px) { .grid-2col { grid-template-columns: 1fr; } }

        .pkg-card { display: flex; flex-direction: column; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; transition: transform 0.2s, box-shadow 0.2s; }
        .pkg-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.08); border-color: #111; }
        .pkg-img { width: 100%; height: 180px; object-fit: cover; }
        .pkg-content { padding: 20px; display: flex; flex-direction: column; flex-grow: 1; justify-content: space-between; }
        .pkg-head { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 10px; }
        .pkg-head h3 { margin: 0; font-size: 1.2rem; }
        .pkg-price { font-weight: 700; font-size: 1.15rem; color: #111; }
        .pkg-badge { display: inline-block; background: #f4f4f5; padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; color: #555; margin-bottom: 12px; font-weight: 500; }
        .pkg-desc { font-size: 0.88rem; color: #555; line-height: 1.5; white-space: pre-line; margin-bottom: 20px; }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <div class="booking-container">
        <div class="step-header">
            <p style="text-transform: uppercase; letter-spacing: 1px; font-size: 0.8rem; font-weight: 700; color: #888;">Step 1 of 4</p>
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
                                    <button type="submit" class="btn-primary full-width" style="padding: 12px; border-radius: 8px;">Select Package &rarr;</button>
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