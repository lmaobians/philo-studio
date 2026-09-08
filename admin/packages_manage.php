<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'toggle_status' && isset($_POST['package_id'], $_POST['status'])) {
        $stmt = $db->prepare("UPDATE packages SET status = ? WHERE package_id = ?");
        $stmt->execute([$_POST['status'], (int)$_POST['package_id']]);
        $_SESSION['admin_msg'] = "Package status updated successfully.";
        header("Location: packages_manage.php");
        exit();
    }

    if ($_POST['action'] === 'update_price' && isset($_POST['package_id'], $_POST['price'])) {
        $stmt = $db->prepare("UPDATE packages SET price = ? WHERE package_id = ?");
        $stmt->execute([(float)$_POST['price'], (int)$_POST['package_id']]);
        $_SESSION['admin_msg'] = "Package price updated successfully.";
        header("Location: packages_manage.php");
        exit();
    }
}

$packages = $db->query("SELECT * FROM packages ORDER BY category ASC, price ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Package Management | PHILO Studio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body class="admin-page">

    <nav class="admin-nav">
        <div class="admin-brand">
            <img src="../header-logo.jpg" alt="PHILO Studio Logo" onerror="this.classList.add('is-hidden')">
            <span>ADMIN CONTROL CENTER</span>
        </div>
        <div class="action-btn-group">
            <a href="dashboard.php" class="btn-toggle">Bookings</a>
            <a href="daily_schedule.php" class="btn-toggle">Daily Schedule</a>
            <a href="customers.php" class="btn-toggle">Customers</a>
            <a href="packages_manage.php" class="btn-toggle active">Packages</a>
            <a href="logout.php" class="btn-logout">Log Out</a>
        </div>
    </nav>

    <div class="admin-container">

        <?php if (isset($_SESSION['admin_msg'])): ?>
            <div class="alert-toast">
                <?= $_SESSION['admin_msg']; unset($_SESSION['admin_msg']); ?>
            </div>
        <?php endif; ?>

        <div class="action-bar">
            <h1 class="page-title-pink">Package Rates & Services</h1>
        </div>

        <div class="table-card">
            <table class="booking-table">
                <thead>
                    <tr>
                        <th>PACKAGE NAME</th>
                        <th>CATEGORY</th>
                        <th>DURATION & PAX</th>
                        <th>PRICE (₱)</th>
                        <th>STATUS</th>
                        <th class="text-right">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($packages)): ?>
                        <tr><td colspan="6" class="admin-empty-state">No studio packages found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($packages as $p): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                                <td><span class="admin-detail"><?= htmlspecialchars($p['category']) ?></span></td>
                                <td>
                                    <span class="admin-detail">
                                        <?= htmlspecialchars($p['duration'] ?? 'N/A') ?> | Max <?= (int)($p['max_pax'] ?? 1) ?> pax
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" class="admin-inline">
                                        <input type="hidden" name="action" value="update_price">
                                        <input type="hidden" name="package_id" value="<?= (int)$p['package_id'] ?>">
                                        <input type="number" step="0.01" name="price" value="<?= (float)$p['price'] ?>" style="width: 90px; padding: 4px 8px; border-radius: 4px; border: 1px solid #ccc;">
                                        <button type="submit" class="btn-act btn-approve">Save</button>
                                    </form>
                                </td>
                                <td>
                                    <span class="badge-status status-<?= $p['status'] === 'active' ? 'confirmed' : 'cancelled' ?>">
                                        <?= ucfirst(htmlspecialchars($p['status'] ?? 'active')) ?>
                                    </span>
                                </td>
                                <td class="text-right">
                                    <form method="POST" class="admin-inline">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="package_id" value="<?= (int)$p['package_id'] ?>">
                                        <input type="hidden" name="status" value="<?= $p['status'] === 'active' ? 'inactive' : 'active' ?>">
                                        <button type="submit" class="btn-act <?= $p['status'] === 'active' ? 'btn-cancel' : 'btn-approve' ?>">
                                            <?= $p['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

</body>
</html>