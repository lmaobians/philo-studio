<?php
$page_title = "Manage Blockout Dates";
require_once 'header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid CSRF token.");
    }

    if ($_POST['action'] === 'add_blockout') {
        $date  = $_POST['blockout_date'];
        $start = !empty($_POST['start_time']) ? $_POST['start_time'] : null;
        $end   = !empty($_POST['end_time']) ? $_POST['end_time'] : null;
        $reason = trim($_POST['reason']) ?: 'Unavailable / Holiday';

        $ins = $db->prepare("INSERT INTO blockout_dates (blockout_date, start_time, end_time, reason) VALUES (?, ?, ?, ?)");
        $ins->execute([$date, $start, $end, $reason]);
        $_SESSION['admin_msg'] = "Blockout entry added successfully.";
    }

    if ($_POST['action'] === 'delete_blockout') {
        $id = (int)$_POST['blockout_id'];
        $del = $db->prepare("DELETE FROM blockout_dates WHERE blockout_id = ?");
        $del->execute([$id]);
        $_SESSION['admin_msg'] = "Blockout entry deleted.";
    }

    header("Location: blockout_manage.php");
    exit();
}

$blockouts = $db->query("SELECT * FROM blockout_dates ORDER BY blockout_date DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="action-bar">
    <h1 class="page-title-pink">Blockout Dates & Holidays</h1>
</div>

<div class="table-card blockout-card">
    <h3 class="card-subtitle">Add Blocked Date / Range</h3>
    <form method="POST" class="blockout-form">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="action" value="add_blockout">

        <div class="form-grid">
            <div class="form-group">
                <label>Date</label>
                <input type="date" name="blockout_date" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label>Start Time <span class="optional-text">(Optional)</span></label>
                <input type="time" name="start_time" class="form-input">
            </div>
            
            <div class="form-group">
                <label>End Time <span class="optional-text">(Optional)</span></label>
                <input type="time" name="end_time" class="form-input">
            </div>
            
            <div class="form-group span-reason">
                <label>Reason</label>
                <input type="text" name="reason" class="form-input" placeholder="e.g., Studio Maintenance or Holiday">
            </div>
            
            <div class="form-group btn-container">
                <button type="submit" class="btn-create btn-block-slot">Block Slot</button>
            </div>
        </div>
    </form>
</div>

<div class="table-card">
    <table class="booking-table">
        <thead>
            <tr>
                <th>DATE</th>
                <th>TIME RANGE</th>
                <th>REASON</th>
                <th class="text-right">ACTION</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($blockouts)): ?>
                <tr>
                    <td colspan="4" class="text-center empty-msg">No blockout dates or holidays configured yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($blockouts as $b): ?>
                    <tr>
                        <td><strong><?= date('M d, Y', strtotime($b['blockout_date'])) ?></strong></td>
                        <td>
                            <?= ($b['start_time'] && $b['end_time']) 
                                ? date('g:i A', strtotime($b['start_time'])) . ' - ' . date('g:i A', strtotime($b['end_time']))
                                : '<span class="badge-full-day">Full Day</span>' ?>
                        </td>
                        <td><?= htmlspecialchars($b['reason']) ?></td>
                        <td class="text-right">
                            <form method="POST" class="admin-inline" onsubmit="return confirm('Delete this block?');">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="action" value="delete_blockout">
                                <input type="hidden" name="blockout_id" value="<?= $b['blockout_id'] ?>">
                                <button type="submit" class="btn-act btn-delete">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>