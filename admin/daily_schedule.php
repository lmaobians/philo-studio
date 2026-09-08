<?php
$page_title = "Daily Schedule";
require_once 'header.php';
require_once 'booking_helpers.php';

$filter_date   = $_GET['date'] ?? date('Y-m-d');
$search_query  = $_GET['search'] ?? '';
$status_filter = $_GET['status_filter'] ?? 'All';

$schedule = getDailySchedule($db, $filter_date, $search_query, $status_filter);
?>

<div class="action-bar">
    <h1 class="page-title-pink">Daily Schedule</h1>
</div>

<div class="table-card">
    <?php renderFilterForm($search_query, $status_filter, true, $filter_date); ?>

    <table class="booking-table">
        <thead>
            <tr>
                <th>TIME</th>
                <th>REF</th>
                <th>CLIENT</th>
                <th>PACKAGE</th>
                <th>STATUS</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($schedule)): ?>
                <tr><td colspan="5" class="admin-empty-state">No scheduled bookings found for this date.</td></tr>
            <?php else: ?>
                <?php foreach ($schedule as $slot): ?>
                    <tr>
                        <td><strong><?= date('g:i A', strtotime($slot['schedule_time'])) ?></strong></td>
                        <td><?= htmlspecialchars($slot['booking_reference'] ?: 'BK-'.$slot['booking_id']) ?></td>
                        <td><?= htmlspecialchars($slot['full_name'] ?: 'Client') ?></td>
                        <td><?= htmlspecialchars($slot['package_name']) ?></td>
                        <td>
                            <span class="badge-status status-<?= strtolower(htmlspecialchars($slot['booking_status'])) ?>">
                                <?= htmlspecialchars($slot['booking_status']) ?>
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