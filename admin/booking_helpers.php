<?php
function getFilteredBookings(PDO $db, string $search = '', string $status = 'All'): array {
    $search = trim($search);
    $status = trim($status);

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
        WHERE 1=1
    ";

    $params = [];

    if (!empty($search)) {
        $query .= " AND (b.booking_reference LIKE ? OR c.full_name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    if (!empty($status) && $status !== 'All') {
        $query .= " AND b.booking_status = ?";
        $params[] = $status;
    }

    $query .= " ORDER BY b.booking_id DESC";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


function renderFilterForm(string $currentSearch = '', string $currentStatus = 'All', string $targetPage = ''): void {
    $action = !empty($targetPage) ? htmlspecialchars($targetPage) : '';
    ?>
    <form method="GET" action="<?= $action ?>" class="admin-inline" style="display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; align-items: center;">
        <input type="text" name="search" value="<?= htmlspecialchars($currentSearch) ?>" placeholder="Search Ref, Name, Email, or Phone..." class="custom-input" style="max-width: 320px;">
        <select name="status_filter" class="custom-select" style="max-width: 160px;">
            <option value="All" <?= $currentStatus === 'All' ? 'selected' : '' ?>>All Statuses</option>
            <option value="Pending" <?= $currentStatus === 'Pending' ? 'selected' : '' ?>>Pending</option>
            <option value="Confirmed" <?= $currentStatus === 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
            <option value="Cancelled" <?= $currentStatus === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
        </select>
        <button type="submit" class="btn-act btn-approve">Filter</button>
        <?php if (!empty($currentSearch) || ($currentStatus !== 'All')): ?>
            <a href="<?= !empty($targetPage) ? htmlspecialchars($targetPage) : $_SERVER['PHP_SELF'] ?>" class="btn-act btn-cancel" style="display: inline-flex; align-items: center; justify-content: center;">Reset</a>
        <?php endif; ?>
    </form>
    <?php
}