<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';

// Auth Guard
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// --- HANDLE POST ACTIONS (CRUD) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // 1. CREATE: Add New Booking with Full 4-Step Walk-in Workflow
    if ($_POST['action'] === 'create_booking') {
        $cust_name  = trim($_POST['full_name']);
        $cust_email = trim($_POST['email']);
        $cust_phone = trim($_POST['phone']);
        
        $package_id = (int)$_POST['package_id'];
        $backdrop   = trim($_POST['backdrop']);
        $has_pets   = isset($_POST['has_pets']) ? 1 : 0;
        $pet_details= trim($_POST['pet_details']);
        
        $sched_date = $_POST['schedule_date'];
        $sched_time = $_POST['schedule_time'];
        
        $pay_method = $_POST['payment_method'];
        $notes      = trim($_POST['notes']);

        // Handle Payment Proof File Upload
        $payment_proof_path = null;
        if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_ext  = strtolower(pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION));
            $file_name = 'walkin_proof_' . time() . '_' . uniqid() . '.' . $file_ext;
            $target_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['payment_proof']['tmp_name'], $target_path)) {
                $payment_proof_path = 'uploads/' . $file_name;
            }
        }
        
        // Find or Create Customer
        $c_stmt = $db->prepare("SELECT customer_id FROM customers WHERE email = ? LIMIT 1");
        $c_stmt->execute([$cust_email]);
        $customer = $c_stmt->fetch(PDO::FETCH_ASSOC);

        if ($customer) {
            $customer_id = $customer['customer_id'];
        } else {
            $ins_cust = $db->prepare("INSERT INTO customers (full_name, email, phone) VALUES (?, ?, ?)");
            $ins_cust->execute([$cust_name, $cust_email, $cust_phone]);
            $customer_id = $db->lastInsertId();
        }

        // Build Booking Notes string containing customization info
        $full_notes = "Payment Method: " . strtoupper($pay_method);
        if (!empty($backdrop)) $full_notes .= " | Backdrop: " . $backdrop;
        if ($has_pets) $full_notes .= " | Pets: Yes (Free) - " . ($pet_details ?: 'No details');
        if (!empty($notes)) $full_notes .= " | Notes: " . $notes;

        $booking_ref = 'BK-' . strtoupper(substr(uniqid(), -6));
        
        // Walk-in bookings with Cash or uploaded proof default to Confirmed status
        $initial_status = ($pay_method === 'cash' || !empty($payment_proof_path)) ? 'Confirmed' : 'Pending';

        $ins_book = $db->prepare("
            INSERT INTO bookings (booking_reference, customer_id, package_id, schedule_date, schedule_time, notes, payment_proof, booking_status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $ins_book->execute([$booking_ref, $customer_id, $package_id, $sched_date, $sched_time, $full_notes, $payment_proof_path, $initial_status]);

        $_SESSION['admin_msg'] = "Walk-in booking <strong>{$booking_ref}</strong> created successfully as <strong>{$initial_status}</strong>.";
        header("Location: dashboard.php");
        exit();
    }

    // 2. UPDATE: Change Booking Status
    if ($_POST['action'] === 'update_status' && isset($_POST['booking_id'], $_POST['status'])) {
        $b_id   = (int)$_POST['booking_id'];
        $status = $_POST['status'];

        $update = $db->prepare("UPDATE bookings SET booking_status = ? WHERE booking_id = ?");
        $update->execute([$status, $b_id]);

        $_SESSION['admin_msg'] = "Booking status updated to <strong>{$status}</strong>.";
        header("Location: dashboard.php");
        exit();
    }

    // 3. DELETE: Remove Booking Record
    if ($_POST['action'] === 'delete_booking' && isset($_POST['booking_id'])) {
        $b_id = (int)$_POST['booking_id'];

        $del = $db->prepare("DELETE FROM bookings WHERE booking_id = ?");
        $del->execute([$b_id]);

        $_SESSION['admin_msg'] = "Booking #{$b_id} deleted successfully.";
        header("Location: dashboard.php");
        exit();
    }
}

// --- READ: Fetch Data ---
$packages = $db->query("SELECT package_id, name, price FROM packages ORDER BY price ASC")->fetchAll(PDO::FETCH_ASSOC);

$total_bookings  = $db->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$pending_count   = $db->query("SELECT COUNT(*) FROM bookings WHERE booking_status = 'Pending'")->fetchColumn();
$confirmed_count = $db->query("SELECT COUNT(*) FROM bookings WHERE booking_status = 'Confirmed'")->fetchColumn();

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
    ORDER BY b.booking_id DESC
";
$stmt = $db->prepare($query);
$stmt->execute();
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | PHILO Studio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,600;0,700;1,400&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-light: #f3eee8;
            --card-bg: #ffffff;
            --primary-color: #cb6b5c;
            --primary-hover: #b05749;
            --text-dark: #332b28;
            --text-muted: #5e5652;
            --border-color: #e5ded7;
            --font-heading: 'Playfair Display', serif;
            --font-body: 'Inter', sans-serif;
        }

        body {
            background-color: var(--bg-light);
            font-family: var(--font-body);
            color: var(--text-dark);
            margin: 0;
            padding: 0;
        }

        h1, h2, h3, h4 {
            font-family: var(--font-heading);
            color: var(--text-dark);
        }

        .admin-nav {
            background: var(--card-bg);
            border-bottom: 1px solid var(--border-color);
            padding: 14px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .admin-brand img {
            height: 38px;
            width: auto;
            object-fit: contain;
        }

        .admin-brand span {
            font-weight: 700;
            letter-spacing: 1.5px;
            font-size: 0.85rem;
            text-transform: uppercase;
            border-left: 1px solid var(--border-color);
            padding-left: 12px;
            color: var(--text-muted);
        }

        .btn-logout {
            color: var(--text-dark);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 8px 16px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            background: var(--card-bg);
            transition: all 0.2s ease;
        }

        .btn-logout:hover {
            background: var(--primary-color);
            color: #fff;
            border-color: var(--primary-color);
        }

        .admin-container {
            max-width: 1200px;
            margin: 36px auto;
            padding: 0 24px;
        }

        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .action-bar h1 {
            margin: 0;
            font-size: 1.8rem;
        }

        .btn-create {
            background: var(--primary-color);
            color: #ffffff;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .btn-create:hover { 
            background: var(--primary-hover); 
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
        }

        .stat-card .label {
            text-transform: uppercase;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 1px;
            color: var(--text-muted);
            margin-bottom: 8px;
        }

        .stat-card .val {
            font-size: 2.2rem;
            font-family: var(--font-heading);
            font-weight: 700;
            color: var(--text-dark);
        }

        .table-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 28px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
        }

        th {
            text-align: left;
            padding: 12px 16px;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border-color);
        }

        td {
            padding: 16px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: top;
        }

        .badge-status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-pending { background: #fef3c7; color: #92400e; }
        .status-confirmed { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }

        .btn-act {
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.78rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-approve { background: var(--primary-color); color: #fff; }
        .btn-approve:hover { background: var(--primary-hover); }

        .btn-cancel { background: transparent; color: #dc2626; border: 1px solid #fee2e2; }
        .btn-cancel:hover { background: #fee2e2; }

        .btn-delete { background: #fee2e2; color: #dc2626; margin-left: 4px; }

        .alert-toast {
            background: var(--text-dark);
            color: #ffffff;
            padding: 14px 20px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 0.88rem;
        }

        /* Multi-step Modal Styling */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(51, 43, 40, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            width: 100%;
            max-width: 580px;
            padding: 32px;
            border-radius: 12px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .step-indicators {
            display: flex;
            justify-content: space-between;
            margin-bottom: 24px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 12px;
        }

        .step-pill {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        .step-pill.active {
            color: var(--primary-color);
        }

        .step-content { display: none; }
        .step-content.active { display: block; }

        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-dark); margin-bottom: 6px; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; box-sizing: border-box; font-family: var(--font-body);
        }

        .payment-methods-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }

        .payment-option {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 12px;
            cursor: pointer;
            text-align: center;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .payment-option input { display: none; }
        .payment-option.selected {
            border-color: var(--primary-color);
            background: #faf2f0;
            color: var(--primary-color);
        }
    </style>
</head>
<body>

    <nav class="admin-nav">
        <div class="admin-brand">
            <img src="../header-logo.jpg" alt="PHILO Studio Logo" onerror="this.style.display='none'">
            <span>ADMIN CONTROL CENTER</span>
        </div>
        <a href="logout.php" class="btn-logout">Log Out</a>
    </nav>

    <div class="admin-container">

        <?php if (isset($_SESSION['admin_msg'])): ?>
            <div class="alert-toast">
                <?= $_SESSION['admin_msg']; unset($_SESSION['admin_msg']); ?>
            </div>
        <?php endif; ?>

        <div class="action-bar">
            <h1>Booking Management</h1>
            <button onclick="openModal()" class="btn-create">+ Create Walk-In Booking</button>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="label">Total Reservations</div>
                <div class="val"><?= $total_bookings ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Pending Review</div>
                <div class="val" style="color: #cb6b5c;"><?= $pending_count ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Confirmed</div>
                <div class="val" style="color: #059669;"><?= $confirmed_count ?></div>
            </div>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Ref</th>
                        <th>Client Details</th>
                        <th>Package & Slot</th>
                        <th>Proof</th>
                        <th>Status</th>
                        <th>Actions (CRUD)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bookings)): ?>
                        <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 40px 0;">No booking entries found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $b): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($b['booking_reference'] ?: 'BK-'.$b['booking_id']) ?></strong></td>
                                <td>
                                    <strong><?= htmlspecialchars($b['full_name'] ?: 'Client') ?></strong><br>
                                    <span style="color: var(--text-muted); font-size: 0.8rem;"><?= htmlspecialchars($b['email'] ?: 'N/A') ?></span><br>
                                    <span style="color: var(--text-muted); font-size: 0.8rem;"><?= htmlspecialchars($b['phone'] ?: 'N/A') ?></span>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($b['package_name']) ?></strong> (₱<?= number_format($b['package_price'], 0) ?>)<br>
                                    <span style="color: var(--text-muted); font-size: 0.8rem;">
                                        📅 <?= date('M d, Y', strtotime($b['schedule_date'])) ?> &nbsp;|&nbsp; ⏰ <?= date('g:i A', strtotime($b['schedule_time'])) ?>
                                    </span>
                                    <?php if (!empty($b['notes'])): ?>
                                        <br><span style="font-size:0.75rem; color:#888;"><?= htmlspecialchars($b['notes']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($b['payment_proof'])): ?>
                                        <a href="../<?= htmlspecialchars($b['payment_proof']) ?>" target="_blank" style="color: var(--primary-color); font-weight:600; font-size:0.8rem;">View Proof &rarr;</a>
                                    <?php else: ?>
                                        <span style="color: #aaa; font-size: 0.8rem;">None</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge-status status-<?= strtolower($b['booking_status']) ?>">
                                        <?= htmlspecialchars($b['booking_status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <!-- UPDATE STATUS FORM -->
                                    <form method="POST" style="display:inline-block;">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="booking_id" value="<?= $b['booking_id'] ?>">
                                        <?php if ($b['booking_status'] !== 'Confirmed'): ?>
                                            <button type="submit" name="status" value="Confirmed" class="btn-act btn-approve">Approve</button>
                                        <?php endif; ?>
                                        <?php if ($b['booking_status'] !== 'Cancelled'): ?>
                                            <button type="submit" name="status" value="Cancelled" class="btn-act btn-cancel">Cancel</button>
                                        <?php endif; ?>
                                    </form>

                                    <!-- DELETE FORM -->
                                    <form method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this booking?');">
                                        <input type="hidden" name="action" value="delete_booking">
                                        <input type="hidden" name="booking_id" value="<?= $b['booking_id'] ?>">
                                        <button type="submit" class="btn-act btn-delete">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

    <!-- MULTI-STEP WALK-IN BOOKING MODAL -->
    <div class="modal-overlay" id="createModal">
        <div class="modal-card">
            
            <div class="step-indicators">
                <span class="step-pill active" id="ind-1">1. Customer & Pkg</span>
                <span class="step-pill" id="ind-2">2. Customization</span>
                <span class="step-pill" id="ind-3">3. Schedule</span>
                <span class="step-pill" id="ind-4">4. Payment</span>
            </div>

            <form method="POST" enctype="multipart/form-data" id="walkinForm">
                <input type="hidden" name="action" value="create_booking">

                <!-- STEP 1: Client Info & Package -->
                <div class="step-content active" id="step-1">
                    <h3 style="margin-top:0;">Step 1: Client & Package Selection</h3>
                    <div class="form-group">
                        <label>Client Full Name</label>
                        <input type="text" name="full_name" required placeholder="e.g. Maria Clara">
                    </div>
                    <div class="form-group">
                        <label>Client Email</label>
                        <input type="email" name="email" required placeholder="maria@example.com">
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone" required placeholder="09123456789">
                    </div>
                    <div class="form-group">
                        <label>Select Studio Package</label>
                        <select name="package_id" required>
                            <?php foreach ($packages as $pkg): ?>
                                <option value="<?= $pkg['package_id'] ?>"><?= htmlspecialchars($pkg['name']) ?> (₱<?= number_format($pkg['price'], 0) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="text-align:right; margin-top:20px;">
                        <button type="button" class="btn-create" onclick="goToStep(2)">Next: Customization &rarr;</button>
                    </div>
                </div>

                <!-- STEP 2: Backdrop & Customization -->
                <div class="step-content" id="step-2">
                    <h3 style="margin-top:0;">Step 2: Customization & Add-ons</h3>
                    <div class="form-group">
                        <label>Backdrop Color</label>
                        <select name="backdrop">
                            <option value="Warm White">Warm White</option>
                            <option value="Neutral Grey">Neutral Grey</option>
                            <option value="Beige / Sand">Beige / Sand</option>
                            <option value="Olive Green">Olive Green</option>
                            <option value="Terracotta">Terracotta</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                            <input type="checkbox" name="has_pets" id="has_pets" onchange="togglePetDetails()">
                            Client bringing pets? <span style="color:#059669; font-weight:700;">(Free of Charge)</span>
                        </label>
                    </div>
                    <div class="form-group" id="pet_box" style="display:none;">
                        <label>Pet Details (Breed / Count)</label>
                        <input type="text" name="pet_details" placeholder="e.g. 1 Golden Retriever, 2 Cats">
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-top:20px;">
                        <button type="button" onclick="goToStep(1)" style="padding:10px 16px; border:1px solid var(--border-color); background:#fff; border-radius:6px;">&larr; Back</button>
                        <button type="button" class="btn-create" onclick="goToStep(3)">Next: Schedule &rarr;</button>
                    </div>
                </div>

                <!-- STEP 3: Date & Time Slot -->
                <div class="step-content" id="step-3">
                    <h3 style="margin-top:0;">Step 3: Schedule Slot</h3>
                    <div class="form-group">
                        <label>Schedule Date</label>
                        <input type="date" name="schedule_date" required value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label>Schedule Time</label>
                        <input type="time" name="schedule_time" required value="10:00">
                    </div>
                    <div class="form-group">
                        <label>Additional Session Notes</label>
                        <textarea name="notes" rows="2" placeholder="Special requests..."></textarea>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-top:20px;">
                        <button type="button" onclick="goToStep(2)" style="padding:10px 16px; border:1px solid var(--border-color); background:#fff; border-radius:6px;">&larr; Back</button>
                        <button type="button" class="btn-create" onclick="goToStep(4)">Next: Payment &rarr;</button>
                    </div>
                </div>

                <!-- STEP 4: Payment Method & Proof Upload -->
                <div class="step-content" id="step-4">
                    <h3 style="margin-top:0;">Step 4: Payment Method & Proof</h3>
                    
                    <label style="font-weight:600; font-size:0.82rem; display:block; margin-bottom:8px;">Select Payment Method</label>
                    <div class="payment-methods-grid">
                        <div class="payment-option selected" onclick="selectPayment('cash', this)">
                            <input type="radio" name="payment_method" value="cash" checked> 💵 Cash (On-Site)
                        </div>
                        <div class="payment-option" onclick="selectPayment('gcash', this)">
                            <input type="radio" name="payment_method" value="gcash"> 📱 GCash
                        </div>
                        <div class="payment-option" onclick="selectPayment('bdo', this)">
                            <input type="radio" name="payment_method" value="bdo"> 🏦 BDO Transfer
                        </div>
                        <div class="payment-option" onclick="selectPayment('gotyme', this)">
                            <input type="radio" name="payment_method" value="gotyme"> 💳 GoTyme
                        </div>
                    </div>

                    <div class="form-group" id="proof_upload_group" style="display:none; background:#faf2f0; padding:12px; border-radius:8px; border:1px solid var(--border-color);">
                        <label style="color:var(--primary-color);">Upload Payment Receipt / Proof</label>
                        <input type="file" name="payment_proof" accept="image/*,.pdf">
                        <span style="font-size:0.75rem; color:var(--text-muted);">Required for GCash, BDO, or GoTyme walk-ins.</span>
                    </div>

                    <div style="display:flex; justify-content:space-between; margin-top:24px;">
                        <button type="button" onclick="goToStep(3)" style="padding:10px 16px; border:1px solid var(--border-color); background:#fff; border-radius:6px;">&larr; Back</button>
                        <button type="submit" class="btn-create">Complete Walk-In Booking</button>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <script>
        function openModal() { 
            document.getElementById('createModal').style.display = 'flex'; 
            goToStep(1);
        }
        function closeModal() { 
            document.getElementById('createModal').style.display = 'none'; 
        }

        function goToStep(step) {
            document.querySelectorAll('.step-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.step-pill').forEach(el => el.classList.remove('active'));
            
            document.getElementById('step-' + step).classList.add('active');
            document.getElementById('ind-' + step).classList.add('active');
        }

        function togglePetDetails() {
            const hasPets = document.getElementById('has_pets').checked;
            document.getElementById('pet_box').style.display = hasPets ? 'block' : 'none';
        }

        function selectPayment(method, el) {
            document.querySelectorAll('.payment-option').forEach(opt => opt.classList.remove('selected'));
            el.classList.add('selected');
            el.querySelector('input[type="radio"]').checked = true;

            const proofGroup = document.getElementById('proof_upload_group');
            if (method === 'cash') {
                proofGroup.style.display = 'none';
            } else {
                proofGroup.style.display = 'block';
            }
        }
    </script>
</body>
</html>