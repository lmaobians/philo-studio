<?php
// MUST BE THE VERY FIRST LINE
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

// Get customer ID from session
$customer_id = $_SESSION['customer_id'] ?? $_SESSION['user_id'] ?? null;

// Redirect to login if not logged in
if (!$customer_id) {
    header("Location: login.php");
    exit();
}

$user_name  = $_SESSION['customer_name'] ?? $_SESSION['user_name'] ?? 'Client';
$user_email = $_SESSION['customer_email'] ?? $_SESSION['user_email'] ?? '';

$database = new Database();
$db = $database->getConnection();

// Simplified Query: Directly fetch bookings matching the logged-in customer_id
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

    <section class="dashboard-container" style="max-width: 1000px; margin: 40px auto; padding: 0 20px; font-family: 'Inter', sans-serif;">
        
        <?php if (isset($_SESSION['booking_success'])): ?>
            <div style="background: #d1fae5; color: #065f46; padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 500;">
                <?= $_SESSION['booking_success']; ?>
                <?php unset($_SESSION['booking_success']); ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <div>
                <p style="text-transform: uppercase; letter-spacing: 1px; font-size: 0.75rem; color: #888; font-weight: 700; margin-bottom: 4px;">WELCOME BACK</p>
                <h1 style="margin: 0; font-size: 1.8rem;"><?= htmlspecialchars($user_name) ?></h1>
                <p style="margin-top: 4px; color: #666; font-size: 0.9rem;"><?= htmlspecialchars($user_email) ?></p>
            </div>
            <a href="booking.php" class="btn-primary" style="background: #111; color: #fff; padding: 12px 20px; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 0.85rem;">BOOK NEW SESSION</a>
        </div>

        <div class="dashboard-card" style="background: #fff; border: 1px solid #eaeaea; border-radius: 12px; padding: 24px;">
            <h2 style="margin-top: 0; font-size: 1.2rem; margin-bottom: 20px;">My Bookings (Total: <?= count($bookings) ?>)</h2>

            <?php if (empty($bookings)): ?>
                <div class="empty-state" style="text-align: center; padding: 40px 0; color: #777;">
                    <p>You have no bookings yet.</p>
                    <a href="booking.php" style="color: #111; font-weight: 600;">Explore packages &rarr;</a>
                </div>
            <?php else: ?>
                <div class="booking-list" style="display: flex; flex-direction: column; gap: 16px;">
                    <?php foreach ($bookings as $b): ?>
                        <div style="border: 1px solid #eee; border-radius: 8px; padding: 16px; display: flex; justify-content: space-between; align-items: center; background: #fafafa;">
                            <div>
                                <h3 style="margin: 0 0 6px 0; font-size: 1rem;"><?= htmlspecialchars($b['package']) ?></h3>
                                <p style="margin: 0; font-size: 0.85rem; color: #555;">
                                    📅 <?= date('M d, Y', strtotime($b['booking_date'])) ?> &nbsp;|&nbsp; 
                                    ⏰ <?= !empty($b['booking_time']) ? date('g:i A', strtotime($b['booking_time'])) : 'Scheduled' ?>
                                </p>
                                <span style="font-size: 0.78rem; color: #888;">Ref: <strong><?= htmlspecialchars($b['booking_reference']) ?></strong></span>
                            </div>
                            <div style="text-align: right;">
                                <span style="display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 700; background: #fef3c7; color: #92400e; margin-bottom: 8px;">
                                    <?= htmlspecialchars($b['status']) ?>
                                </span>
                                <div style="font-weight: 700; font-size: 1rem;">₱<?= number_format($b['price'], 0) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

</body>
</html>