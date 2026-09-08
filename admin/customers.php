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

$total_customers = $db->query("SELECT COUNT(*) FROM customers")->fetchColumn();

$query = "
    SELECT 
        c.customer_id,
        c.full_name,
        c.email,
        c.phone,
        COUNT(b.booking_id) AS total_bookings,
        MAX(b.schedule_date) AS last_booking_date
    FROM customers c
    LEFT JOIN bookings b ON c.customer_id = b.customer_id
    GROUP BY c.customer_id, c.full_name, c.email, c.phone
    ORDER BY c.customer_id DESC
";
$stmt = $db->prepare($query);
$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Accounts | PHILO Studio</title>
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
            <a href="customers.php" class="btn-toggle active">Customers</a>
            <a href="logout.php" class="btn-logout">Log Out</a>
        </div>
    </nav>

    <div class="admin-container">

        <div class="action-bar">
            <h1 class="page-title-pink">Customer Accounts</h1>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="label">TOTAL REGISTERED CLIENTS</div>
                <div class="val"><?= htmlspecialchars((string)$total_customers) ?></div>
            </div>
        </div>

        <div class="table-card">
            <table class="booking-table">
                <thead>
                    <tr>
                        <th>CLIENT ID</th>
                        <th>FULL NAME</th>
                        <th>EMAIL ADDRESS</th>
                        <th>PHONE NUMBER</th>
                        <th>TOTAL BOOKINGS</th>
                        <th>LAST BOOKED DATE</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customers)): ?>
                        <tr><td colspan="6" class="admin-empty-state">No registered customers found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($customers as $c): ?>
                            <tr>
                                <td><strong>#<?= (int)$c['customer_id'] ?></strong></td>
                                <td><strong><?= htmlspecialchars($c['full_name'] ?: 'N/A') ?></strong></td>
                                <td><span class="admin-detail"><?= htmlspecialchars($c['email'] ?: 'N/A') ?></span></td>
                                <td><span class="admin-detail"><?= htmlspecialchars($c['phone'] ?: 'N/A') ?></span></td>
                                <td><strong><?= (int)$c['total_bookings'] ?> session(s)</strong></td>
                                <td>
                                    <span class="admin-detail">
                                        <?= $c['last_booking_date'] ? date('M d, Y', strtotime($c['last_booking_date'])) : 'Never' ?>
                                    </span>
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