<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in as admin (adjust session check as needed)
// if (!isset($_SESSION['is_admin'])) { header("Location: login.php"); exit(); }

$database = new Database();
$db = $database->getConnection();

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'], $_POST['status'])) {
    $update_stmt = $db->prepare("UPDATE bookings SET booking_status = ? WHERE booking_id = ?");
    $update_stmt->execute([$_POST['status'], $_POST['booking_id']]);
    $msg = "Booking status updated successfully.";
}

// Fetch all bookings with customer and package details
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
    <style>
        body { font-family: 'Inter', sans-serif; background: #f7f7f8; padding: 40px; }
        .admin-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.04); }
        .admin-table th, .admin-table td { padding: 14px 16px; text-align: left; font-size: 0.88rem; border-bottom: 1px solid #eee; }
        .admin-table th { background: #fafafa; font-weight: 600; color: #444; }
        .badge { padding: 4px 10px; border-radius: 20px; font-weight: 600; font-size: 0.75rem; text-transform: uppercase; }
        .badge-Pending { background: #fef3c7; color: #d97706; }
        .badge-Confirmed { background: #dcfce7; color: #15803d; }
        .badge-Cancelled { background: #fee2e2; color: #b91c1c; }
        .proof-btn { color: #2563eb; text-decoration: underline; font-weight: 500; }
        .action-select { padding: 6px 10px; border-radius: 6px; border: 1px solid #ccc; font-size: 0.82rem; }
    </style>
</head>
<body>

    <h2>Manage Bookings</h2>
    <?php if (isset($msg)) echo "<p style='color: green;'>$msg</p>"; ?>

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
                <td style="max-width: 250px; font-size: 0.8rem; color: #555;"><?= htmlspecialchars($b['notes']) ?></td>
                <td>
                    <?php if (!empty($b['payment_proof'])): ?>
                        <a href="<?= htmlspecialchars($b['payment_proof']) ?>" target="_blank" class="proof-btn">View Receipt</a>
                    <?php else: ?>
                        <span style="color: #999;">None</span>
                    <?php endif; ?>
                </td>
                <td><span class="badge badge-<?= $b['booking_status'] ?>"><?= $b['booking_status'] ?></span></td>
                <td>
                    <form method="POST" style="display:inline;">
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