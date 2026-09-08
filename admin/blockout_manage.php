<?php
$page_title = "Manage Blackout Dates";
require_once 'header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_blackout') {
        $title      = trim($_POST['title']);
        $start_date = $_POST['start_date'];
        $end_date   = $_POST['end_date'] ?: $start_date;
        $start_time = !empty($_POST['start_time']) ? $_POST['start_time'] : null;
        $end_time   = !empty($_POST['end_time']) ? $_POST['end_time'] : null;
        $reason     = trim($_POST['reason'] ?? '');

        $stmt = $db->prepare("INSERT INTO blackout_dates (title, start_date, end_date, start_time, end_time, reason) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $start_date, $end_date, $start_time, $end_time, $reason]);
        header("Location: blackout_manage.php");
        exit();
    }

    if ($_POST['action'] === 'delete_blackout' && isset($_POST['blackout_id'])) {
        $stmt = $db->prepare("DELETE FROM blackout_dates WHERE blackout_id = ?");
        $stmt->execute([(int)$_POST['blackout_id']]);
        header("Location: blackout_manage.php");
        exit();
    }
}

$blackouts = $db->query("SELECT * FROM blackout_dates ORDER BY start_date DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="action-bar">
    <h1 class="page-title-pink">Blackout Dates & Holidays</h1>
</div>

<div class="table-card" style="margin-bottom: 20px;">
    <h3 style="margin-top: 0; margin-bottom: 16px;">Add Blocked Date or Time Slot</h3>
    <form method="POST" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;">
        <input type="hidden" name="action" value="add_blackout">
        
        <div>
            <label style="display:block; font-size: 0.8rem; font-weight:600; margin-bottom: 4px;">Title</label>
            <input type="text" name="title" required placeholder="e.g., Studio Maintenance" class="custom-input" style="width: 200px;">
        </div>

        <div>
            <label style="display:block; font-size: 0.8rem; font-weight:600; margin-bottom: 4px;">Start Date</label>
            <input type="date" name="start_date" required class="custom-input">
        </div>

        <div>
            <label style="display:block; font-size: 0.8rem; font-weight:600; margin-bottom: 4px;">End Date</label>
            <input type="date" name="end_date" class="custom-input">
        </div>

        <div>
            <label style="display:block; font-size: 0.8rem; font-weight:600; margin-bottom: 4px;">Start Time (Optional)</label>
            <input type="time" name="start_time" class="custom-input">
        </div>

        <div>
            <label style="display:block; font-size: 0.8rem; font-weight:600; margin-bottom: 4px;">End Time (Optional)</label>
            <input type="time" name="end_time" class="custom-input">
        </div>

        <button type="submit" class="btn-act btn-approve" style="height: 42px;">Block Date</button>
    </form>
</div>

<div class="table-card">
    <table class="booking-table">
        <thead>
            <tr>
                <th>TITLE</th>
                <th>DATES</th>
                <th>TIME RANGE</th>
                <th class="text-right">ACTIONS</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($blackouts)): ?>
                <tr><td colspan="4" class="admin-empty-state">No blackout dates created yet.</td></tr>
            <?php else: ?>
                <?php foreach ($blackouts as $b): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($b['title']) ?></strong></td>
                        <td><?= date('M d, Y', strtotime($b['start_date'])) ?> to <?= date('M d, Y', strtotime($b['end_date'])) ?></td>
                        <td>
                            <?= ($b['start_time'] && $b['end_time']) 
                                ? date('g:i A', strtotime($b['start_time'])) . ' - ' . date('g:i A', strtotime($b['end_time'])) 
                                : '<span class="badge-status status-cancelled">All Day</span>' ?>
                        </td>
                        <td class="text-right">
                            <form method="POST" onsubmit="return confirm('Remove this blackout rule?');" style="display:inline;">
                                <input type="hidden" name="action" value="delete_blackout">
                                <input type="hidden" name="blackout_id" value="<?= (int)$b['blackout_id'] ?>">
                                <button type="submit" class="btn-act btn-cancel">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>