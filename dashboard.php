<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

$customer_id = $_SESSION['customer_id'] ?? $_SESSION['user_id'] ?? null;

if (!$customer_id) {
    header("Location: login.php");
    exit();
}

$user_name  = $_SESSION['customer_name'] ?? $_SESSION['user_name'] ?? 'Client';
$user_email = $_SESSION['customer_email'] ?? $_SESSION['user_email'] ?? '';

$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare("
    SELECT 
        b.booking_id AS id,
        b.booking_reference,
        b.schedule_date AS booking_date,
        b.schedule_time AS booking_time,
        b.booking_status AS status,
        b.payment_proof,
        COALESCE(p.name, 'Studio Package') AS package, 
        COALESCE(p.price, 0) AS price 
    FROM bookings b 
    LEFT JOIN packages p ON b.package_id = p.package_id 
    WHERE b.customer_id = ? 
    ORDER BY b.booking_id DESC
");

$stmt->execute([(int)$customer_id]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - Philo Studio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <section class="dashboard-container customer-dashboard-container">
        
        <?php if (isset($_SESSION['booking_success'])): ?>
            <div class="booking-success-message">
                <?= $_SESSION['booking_success']; ?>
                <?php unset($_SESSION['booking_success']); ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-header customer-dashboard-header">
            <div>
                <p class="dashboard-welcome-label">WELCOME BACK</p>
                <h1 class="dashboard-user-name"><?= htmlspecialchars($user_name) ?></h1>
                <p class="dashboard-user-email"><?= htmlspecialchars($user_email) ?></p>
            </div>
            <a href="booking.php" class="btn-primary dashboard-book-button">BOOK NEW SESSION</a>
        </div>

        <div class="dashboard-card customer-bookings-card">
            <h2 class="bookings-card-title">My Bookings (Total: <?= count($bookings) ?>)</h2>

            <?php if (empty($bookings)): ?>
                <div class="empty-state customer-empty-state">
                    <p>You have no bookings yet.</p>
                    <a href="booking.php" class="explore-packages-link">Explore packages &rarr;</a>
                </div>
            <?php else: ?>
                <div class="booking-list customer-booking-list">
                    <?php foreach ($bookings as $b): ?>
                        <div class="customer-booking-item">
                            <div>
                                <h3 class="customer-package-name"><?= htmlspecialchars($b['package']) ?></h3>
                                <p class="customer-booking-date">
                                    📅 <?= date('M d, Y', strtotime($b['booking_date'])) ?> &nbsp;|&nbsp; 
                                    ⏰ <?= !empty($b['booking_time']) ? date('g:i A', strtotime($b['booking_time'])) : 'Scheduled' ?>
                                </p>
                                <span class="customer-booking-reference">Ref: <strong><?= htmlspecialchars($b['booking_reference']) ?></strong></span>
                            </div>
                            <div class="customer-booking-summary">
                                <span class="customer-status-badge">
                                    <?= htmlspecialchars($b['status']) ?>
                                </span>
                                <div class="customer-booking-price">₱<?= number_format($b['price'], 0) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

</body>
</html>