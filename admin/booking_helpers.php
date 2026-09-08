<?php
function getFilteredBookings(PDO $db, string $search = '', string $status = 'All'): array {
    $search = trim($search);
    $status = trim($status);

    $query = "
        SELECT 
            b.booking_id, b.booking_reference, b.schedule_date, b.schedule_time,
            b.notes, b.payment_proof, b.booking_status,
            c.full_name, c.email, c.phone,
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
        $params = array_fill(0, 4, $searchTerm);
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

function getDailySchedule(PDO $db, string $date, string $search = '', string $status = 'All'): array {
    $search = trim($search);
    $status = trim($status);

    $query = "
        SELECT b.*, c.full_name, c.email, c.phone, p.name AS package_name 
        FROM bookings b
        LEFT JOIN customers c ON b.customer_id = c.customer_id
        LEFT JOIN packages p ON b.package_id = p.package_id
        WHERE b.schedule_date = ?
    ";
    $params = [$date];

    if (!empty($search)) {
        $query .= " AND (b.booking_reference LIKE ? OR c.full_name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)";
        $searchTerm = '%' . $search . '%';
        array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    }

    if (!empty($status) && $status !== 'All') {
        $query .= " AND b.booking_status = ?";
        $params[] = $status;
    }

    $query .= " ORDER BY b.schedule_time ASC";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getFilteredCustomers(PDO $db, string $search = ''): array {
    $search = trim($search);
    $query = "SELECT * FROM customers WHERE 1=1";
    $params = [];

    if (!empty($search)) {
        $query .= " AND (full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $params = [$searchTerm, $searchTerm, $searchTerm];
    }

    $query .= " ORDER BY customer_id DESC";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getFilteredPackages(PDO $db, string $search = ''): array {
    $search = trim($search);
    $query = "SELECT * FROM packages WHERE 1=1";
    $params = [];

    if (!empty($search)) {
        $query .= " AND (name LIKE ? OR description LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $params = [$searchTerm, $searchTerm];
    }

    $query .= " ORDER BY price ASC";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function isSlotBlackedOut(PDO $db, string $date, string $time): bool {
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM blackout_dates
        WHERE blackout_date = ?
    AND (
            (start_time IS NULL AND end_time IS NULL)
            OR (? BETWEEN start_time AND end_time)
        )
    ");
    $stmt->execute([$date, $time]);
    return $stmt->fetchColumn() > 0;
}

function renderFilterForm(string $currentSearch = '', string $currentStatus = 'All', bool $showStatusFilter = true, string $currentDate = ''): void {
    ?>
    <form method="GET" class="filter-bar" style="display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; align-items: center;">
        <?php if ($currentDate !== ''): ?>
            <div style="display: flex; align-items: center; gap: 6px;">
                <label for="date-input" style="font-weight: 600; font-size: 0.9rem; color: #555;">Date:</label>
                <input type="date" id="date-input" name="date" value="<?= htmlspecialchars($currentDate) ?>" class="custom-input" style="max-width: 170px;" onchange="this.form.submit()">
            </div>
        <?php endif; ?>

        <input type="text" name="search" value="<?= htmlspecialchars($currentSearch) ?>" placeholder="Search records..." class="custom-input" style="max-width: 280px; flex: 1;">
        
        <?php if ($showStatusFilter): ?>
            <select name="status_filter" class="custom-select" style="max-width: 160px;">
                <option value="All" <?= $currentStatus === 'All' ? 'selected' : '' ?>>All Statuses</option>
                <option value="Pending" <?= $currentStatus === 'Pending' ? 'selected' : '' ?>>Pending</option>
                <option value="Confirmed" <?= $currentStatus === 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                <option value="Cancelled" <?= $currentStatus === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
        <?php endif; ?>

        <button type="submit" class="btn-act btn-approve">Search</button>

        <?php if (!empty($currentSearch) || ($showStatusFilter && $currentStatus !== 'All')): ?>
            <a href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?><?= $currentDate !== '' ? '?date=' . urlencode($currentDate) : '' ?>" class="btn-act btn-cancel" style="display: inline-flex; align-items: center; justify-content: center; text-decoration: none;">Reset Filters</a>
        <?php endif; ?>
    </form>
    <?php
}