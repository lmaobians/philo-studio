<?php
$page_title = "Customer Accounts";
require_once 'header.php';

$search_query = $_GET['search'] ?? '';
$search_by    = $_GET['search_by'] ?? 'all';

$query = "
    SELECT 
        c.customer_id,
        c.full_name,
        c.email,
        c.phone,
        COUNT(b.booking_id) AS total_bookings,
        MAX(b.schedule_date) AS last_booked_date
    FROM customers c
    LEFT JOIN bookings b ON c.customer_id = b.customer_id
    WHERE 1=1
";

$params = [];

if (!empty($search_query)) {
    $term = '%' . trim($search_query) . '%';
    
    switch ($search_by) {
        case 'id':
            $query .= " AND CAST(c.customer_id AS CHAR) LIKE ?";
            $params[] = $term;
            break;
        case 'name':
            $query .= " AND c.full_name LIKE ?";
            $params[] = $term;
            break;
        case 'email':
            $query .= " AND c.email LIKE ?";
            $params[] = $term;
            break;
        case 'phone':
            $query .= " AND c.phone LIKE ?";
            $params[] = $term;
            break;
        case 'date':
            $query .= " AND (b.schedule_date LIKE ? OR DATE_FORMAT(b.schedule_date, '%b %d, %Y') LIKE ?)";
            $params[] = $term;
            $params[] = $term;
            break;
        default:
            $query .= " AND (c.full_name LIKE ? OR c.email LIKE ? OR c.phone LIKE ? OR CAST(c.customer_id AS CHAR) LIKE ?)";
            $params = [$term, $term, $term, $term];
            break;
    }
}

$query .= " GROUP BY c.customer_id";

if (!empty($search_query) && $search_by === 'bookings') {
    $query .= " HAVING COUNT(b.booking_id) = ?";
    $params = [(int)$search_query];
}

$query .= " ORDER BY c.customer_id DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_clients = count($customers);
?>

<div class="action-bar">
    <h1 class="page-title-pink">Customer Accounts</h1>
</div>

<div style="margin-bottom: 20px;">
    <div class="table-card" style="display: inline-block; padding: 16px 24px !important; min-width: 220px;">
        <span style="font-size: 0.75rem; font-weight: 700; color: #71717a; text-transform: uppercase; letter-spacing: 0.05em;">Total Registered Clients</span>
        <div style="font-size: 2rem; font-weight: 700; color: #18181b; margin-top: 4px;"><?= $total_clients ?></div>
    </div>
</div>

<div class="table-card">
    <form method="GET" class="filter-bar" style="display: flex; gap: 12px; margin-bottom: 20px; align-items: center; flex-wrap: wrap;">
        <select name="search_by" class="custom-select" style="max-width: 170px;">
            <option value="all" <?= $search_by === 'all' ? 'selected' : '' ?>>Search All Fields</option>
            <option value="id" <?= $search_by === 'id' ? 'selected' : '' ?>>Client ID</option>
            <option value="name" <?= $search_by === 'name' ? 'selected' : '' ?>>Full Name</option>
            <option value="email" <?= $search_by === 'email' ? 'selected' : '' ?>>Email Address</option>
            <option value="phone" <?= $search_by === 'phone' ? 'selected' : '' ?>>Phone Number</option>
            <option value="bookings" <?= $search_by === 'bookings' ? 'selected' : '' ?>>Total Bookings</option>
            <option value="date" <?= $search_by === 'date' ? 'selected' : '' ?>>Last Booked Date</option>
        </select>

        <input type="text" name="search" value="<?= htmlspecialchars($search_query) ?>" placeholder="Search records..." class="custom-input" style="max-width: 280px; flex: 1;">

        <button type="submit" class="btn-act btn-approve">Search</button>

        <?php if (!empty($search_query)): ?>
            <a href="customers.php" class="btn-act btn-cancel" style="display: inline-flex; align-items: center; justify-content: center; text-decoration: none;">Reset</a>
        <?php endif; ?>
    </form>

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
                <tr><td colspan="6" class="admin-empty-state">No customers found matching criteria.</td></tr>
            <?php else: ?>
                <?php foreach ($customers as $c): ?>
                    <tr>
                        <td>#<?= (int)$c['customer_id'] ?></td>
                        <td><strong><?= htmlspecialchars($c['full_name']) ?></strong></td>
                        <td><?= htmlspecialchars($c['email']) ?></td>
                        <td><?= htmlspecialchars($c['phone']) ?></td>
                        <td><strong><?= (int)$c['total_bookings'] ?> session(s)</strong></td>
                        <td><?= $c['last_booked_date'] ? date('M d, Y', strtotime($c['last_booked_date'])) : 'Never' ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</div>
</body>
</html>