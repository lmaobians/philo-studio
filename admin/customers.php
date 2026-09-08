<?php
$page_title = "Customer Accounts";
require_once 'header.php';

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