<?php
$page_title = "Package Management";
require_once 'header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'toggle_status' && isset($_POST['package_id'], $_POST['status'])) {
        $stmt = $db->prepare("UPDATE packages SET status = ? WHERE package_id = ?");
        $stmt->execute([$_POST['status'], (int)$_POST['package_id']]);
        $_SESSION['admin_msg'] = "Package status updated successfully.";
        header("Location: packages_manage.php");
        exit();
    }

    if ($_POST['action'] === 'update_price' && isset($_POST['package_id'], $_POST['price'])) {
        $stmt = $db->prepare("UPDATE packages SET price = ? WHERE package_id = ?");
        $stmt->execute([(float)$_POST['price'], (int)$_POST['package_id']]);
        $_SESSION['admin_msg'] = "Package price updated successfully.";
        header("Location: packages_manage.php");
        exit();
    }
}

$search_query = $_GET['search'] ?? '';
$search_by    = $_GET['search_by'] ?? 'all';

$query = "SELECT * FROM packages WHERE 1=1";
$params = [];

if (!empty($search_query)) {
    $term = '%' . trim($search_query) . '%';

    switch ($search_by) {
        case 'name':
            $query .= " AND name LIKE ?";
            $params[] = $term;
            break;
        case 'category':
            $query .= " AND category LIKE ?";
            $params[] = $term;
            break;
        case 'duration':
            $query .= " AND duration LIKE ?";
            $params[] = $term;
            break;
        case 'pax':
            $query .= " AND CAST(max_pax AS CHAR) LIKE ?";
            $params[] = $term;
            break;
        case 'price':
            $query .= " AND CAST(price AS CHAR) LIKE ?";
            $params[] = $term;
            break;
        case 'status':
            $status_term = strtolower(trim($search_query));
            if ($status_term === 'activated' || $status_term === 'active') {
                $query .= " AND status = 'active'";
            } elseif ($status_term === 'deactivated' || $status_term === 'inactive') {
                $query .= " AND status = 'inactive'";
            } else {
                $query .= " AND status LIKE ?";
                $params[] = $term;
            }
            break;
        default:
            $query .= " AND (name LIKE ? OR category LIKE ? OR duration LIKE ? OR CAST(max_pax AS CHAR) LIKE ? OR CAST(price AS CHAR) LIKE ? OR status LIKE ?)";
            $params = [$term, $term, $term, $term, $term, $term];
            break;
    }
}

$query .= " ORDER BY category ASC, price ASC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

        <div class="action-bar">
            <h1 class="page-title-pink">Package Rates & Services</h1>
        </div>

        <div class="table-card">
            <form method="GET" class="filter-bar" style="display: flex; gap: 12px; margin-bottom: 20px; align-items: center; flex-wrap: wrap;">
                <select name="search_by" class="custom-select" style="max-width: 170px;">
                    <option value="all" <?= $search_by === 'all' ? 'selected' : '' ?>>Search All Fields</option>
                    <option value="name" <?= $search_by === 'name' ? 'selected' : '' ?>>Package Name</option>
                    <option value="category" <?= $search_by === 'category' ? 'selected' : '' ?>>Category</option>
                    <option value="duration" <?= $search_by === 'duration' ? 'selected' : '' ?>>Duration</option>
                    <option value="pax" <?= $search_by === 'pax' ? 'selected' : '' ?>>Max Pax</option>
                    <option value="price" <?= $search_by === 'price' ? 'selected' : '' ?>>Price</option>
                    <option value="status" <?= $search_by === 'status' ? 'selected' : '' ?>>Status</option>
                </select>

                <input type="text" name="search" value="<?= htmlspecialchars($search_query) ?>" placeholder="Search records..." class="custom-input" style="max-width: 280px; flex: 1;">

                <button type="submit" class="btn-act btn-approve">Search</button>

                <?php if (!empty($search_query)): ?>
                    <a href="packages_manage.php" class="btn-act btn-cancel" style="display: inline-flex; align-items: center; justify-content: center; text-decoration: none;">Reset</a>
                <?php endif; ?>
            </form>

            <table class="booking-table">
                <thead>
                    <tr>
                        <th>PACKAGE NAME</th>
                        <th>CATEGORY</th>
                        <th>DURATION & PAX</th>
                        <th>PRICE (₱)</th>
                        <th>STATUS</th>
                        <th class="text-right">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($packages)): ?>
                        <tr><td colspan="6" class="admin-empty-state">No studio packages found matching criteria.</td></tr>
                    <?php else: ?>
                        <?php foreach ($packages as $p): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                                <td><span class="admin-detail"><?= htmlspecialchars($p['category']) ?></span></td>
                                <td>
                                    <span class="admin-detail">
                                        <?= htmlspecialchars($p['duration'] ?? 'N/A') ?> | Max <?= (int)($p['max_pax'] ?? 1) ?> pax
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" class="admin-inline">
                                        <input type="hidden" name="action" value="update_price">
                                        <input type="hidden" name="package_id" value="<?= (int)$p['package_id'] ?>">
                                        <input type="number" step="0.01" name="price" value="<?= (float)$p['price'] ?>" style="width: 90px; padding: 4px 8px; border-radius: 4px; border: 1px solid #ccc;">
                                        <button type="submit" class="btn-act btn-approve">Save</button>
                                    </form>
                                </td>
                                <td>
                                    <span class="badge-status status-<?= $p['status'] === 'active' ? 'confirmed' : 'cancelled' ?>">
                                        <?= ucfirst(htmlspecialchars($p['status'] ?? 'active')) ?>
                                    </span>
                                </td>
                                <td class="text-right">
                                    <form method="POST" class="admin-inline">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="package_id" value="<?= (int)$p['package_id'] ?>">
                                        <input type="hidden" name="status" value="<?= $p['status'] === 'active' ? 'inactive' : 'active' ?>">
                                        <button type="submit" class="btn-act <?= $p['status'] === 'active' ? 'btn-cancel' : 'btn-approve' ?>">
                                            <?= $p['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                        </button>
                                    </form>
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