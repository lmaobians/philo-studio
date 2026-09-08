<?php
$page_title = "Daily Schedule List";
require_once 'header.php';

$selected_date = $_GET['date'] ?? date('Y-m-d');

$query = "
    SELECT 
        b.booking_id,
        b.booking_reference,
        b.schedule_date,
        b.schedule_time,
        b.notes,
        b.payment_proof,
        b.booking_status,
        c.full_name,
        c.email,
        c.phone,
        COALESCE(p.name, 'Studio Package') AS package_name,
        COALESCE(p.price, 0) AS package_price
    FROM bookings b
    LEFT JOIN customers c ON b.customer_id = c.customer_id
    LEFT JOIN packages p ON b.package_id = p.package_id
    WHERE b.schedule_date = ? AND b.booking_status != 'Cancelled'
    ORDER BY b.schedule_time ASC
";
$stmt = $db->prepare($query);
$stmt->execute([$selected_date]);
$daily_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_today = count($daily_bookings);
?>

        <div class="action-bar">
            <h1 class="page-title-pink">Daily Schedule List</h1>
            <form method="GET" class="admin-inline">
                <input type="date" name="date" value="<?= htmlspecialchars($selected_date) ?>" onchange="this.form.submit()" style="padding: 8px 12px; border-radius: 6px; border: 1px solid #ccc;">
            </form>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="label">SLOTS BOOKED FOR <?= date('M d, Y', strtotime($selected_date)) ?></div>
                <div class="val"><?= htmlspecialchars((string)$total_today) ?></div>
            </div>
        </div>

        <div class="table-card">
            <table class="booking-table">
                <thead>
                    <tr>
                        <th>TIME SLOT</th>
                        <th>REF</th>
                        <th>CLIENT DETAILS</th>
                        <th>PACKAGE</th>
                        <th>NOTES & ADD-ONS</th>
                        <th>STATUS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daily_bookings)): ?>
                        <tr><td colspan="6" class="admin-empty-state">No client sessions scheduled for this date.</td></tr>
                    <?php else: ?>
                        <?php foreach ($daily_bookings as $b): ?>
                            <tr>
                                <td><strong><?= date('g:i A', strtotime($b['schedule_time'])) ?></strong></td>
                                <td><strong><?= htmlspecialchars($b['booking_reference'] ?: 'BK-'.$b['booking_id']) ?></strong></td>
                                <td>
                                    <strong><?= htmlspecialchars($b['full_name'] ?: 'Client') ?></strong><br>
                                    <span class="admin-detail"><?= htmlspecialchars($b['phone'] ?: 'N/A') ?></span>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($b['package_name']) ?></strong><br>
                                    <span class="admin-detail">₱<?= number_format($b['package_price'], 0) ?></span>
                                </td>
                                <td class="admin-notes"><?= htmlspecialchars($b['notes'] ?: 'None') ?></td>
                                <td>
                                    <span class="badge-status status-<?= strtolower(htmlspecialchars($b['booking_status'])) ?>">
                                        <?= htmlspecialchars($b['booking_status']) ?>
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