<?php
session_start();
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'], $_POST['status'])) {
    $update_stmt = $db->prepare("UPDATE bookings SET booking_status = ? WHERE booking_id = ?");
    $update_stmt->execute([$_POST['status'], $_POST['booking_id']]);
    $msg = "Booking status updated successfully.";
}

$query = "SELECT b.*, c.full_name, c.email, c.phone, p.name as package_name, p.price
            FROM bookings b
            JOIN customers c ON b.customer_id = c.customer_id
            JOIN packages p ON b.package_id = p.package_id
            ORDER BY b.booking_id DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | PHILO Studio</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="legacy-admin-page">

    <h2>Manage Bookings</h2>
    <?php if (isset($msg)) echo "<p class='admin-message'>$msg</p>"; ?>

    <table class="admin-table">
        <thead>
            <tr>
                <th>Ref ID</th>
                <th>Customer</th>
                <th>Package & Date</th>
                <th>Details & Notes</th>
                <th>Proof</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bookings as $b): ?>
            <tr>
                <td><strong><?= htmlspecialchars($b['booking_reference'] ?? 'BK-' . $b['booking_id']) ?></strong></td>
                <td>
                    <strong><?= htmlspecialchars($b['full_name']) ?></strong><br>
                    <small><?= htmlspecialchars($b['email']) ?></small>
                </td>
                <td>
                    <?= htmlspecialchars($b['package_name']) ?> (₱<?= number_format($b['price'], 0) ?>)<br>
                    <small><?= htmlspecialchars($b['schedule_date']) ?> @ <?= date("g:i A", strtotime($b['schedule_time'])) ?></small>
                </td>
                <td class="admin-notes"><?= htmlspecialchars($b['notes']) ?></td>
                <td>
                    <?php if (!empty($b['payment_proof'])): ?>
                        <a href="<?= htmlspecialchars($b['payment_proof']) ?>" target="_blank" class="proof-btn">View Receipt</a>
                    <?php else: ?>
                        <span class="admin-muted">None</span>
                    <?php endif; ?>
                </td>
                <td><span class="badge badge-<?= $b['booking_status'] ?>"><?= $b['booking_status'] ?></span></td>
                <td>
                    <form method="POST" class="admin-inline-form">
                        <input type="hidden" name="booking_id" value="<?= $b['booking_id'] ?>">
                        <select name="status" class="action-select" onchange="this.form.submit()">
                            <option value="Pending" <?= $b['booking_status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="Confirmed" <?= $b['booking_status'] === 'Confirmed' ? 'selected' : '' ?>>Confirm</option>
                            <option value="Cancelled" <?= $b['booking_status'] === 'Cancelled' ? 'selected' : '' ?>>Cancel</option>
                        </select>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>
</html>