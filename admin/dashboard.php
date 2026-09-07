<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
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

        $payment_proof_path = null;
        if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_ext   = strtolower(pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION));
            $file_name = 'walkin_proof_' . time() . '_' . uniqid() . '.' . $file_ext;
            $target_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['payment_proof']['tmp_name'], $target_path)) {
                $payment_proof_path = 'uploads/' . $file_name;
            }
        }
        
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

        $full_notes = "Payment Method: " . strtoupper($pay_method);
        if (!empty($backdrop)) $full_notes .= " | Backdrop: " . $backdrop;
        if ($has_pets) $full_notes .= " | Pets: Yes - " . ($pet_details ?: 'No details');
        if (!empty($notes)) $full_notes .= " | Notes: " . $notes;

        $booking_ref = 'BK-' . strtoupper(substr(uniqid(), -6));
        
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

    if ($_POST['action'] === 'update_status' && isset($_POST['booking_id'], $_POST['status'])) {
        $b_id   = (int)$_POST['booking_id'];
        $status = $_POST['status'];

        $update = $db->prepare("UPDATE bookings SET booking_status = ? WHERE booking_id = ?");
        $update->execute([$status, $b_id]);

        $_SESSION['admin_msg'] = "Booking status updated to <strong>{$status}</strong>.";
        header("Location: dashboard.php");
        exit();
    }

    if ($_POST['action'] === 'delete_booking' && isset($_POST['booking_id'])) {
        $b_id = (int)$_POST['booking_id'];

        $del = $db->prepare("DELETE FROM bookings WHERE booking_id = ?");
        $del->execute([$b_id]);

        $_SESSION['admin_msg'] = "Booking #{$b_id} deleted successfully.";
        header("Location: dashboard.php");
        exit();
    }
}

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
    <title>Booking Management | PHILO Studio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body class="admin-page">

    <nav class="admin-nav">
        <div class="admin-brand">
            <img src="../header-logo.jpg" alt="PHILO Studio Logo" onerror="this.classList.add('is-hidden')">
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
            <h1 class="page-title-pink">Booking Management</h1>
            <button onclick="openModal('createModal')" class="btn-create">+ Create Walk-In Booking</button>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="label">TOTAL RESERVATIONS</div>
                <div class="val"><?= $total_bookings ?></div>
            </div>
            <div class="stat-card">
                <div class="label">PENDING REVIEW</div>
                <div class="val admin-pending-value"><?= $pending_count ?></div>
            </div>
            <div class="stat-card">
                <div class="label">CONFIRMED</div>
                <div class="val admin-confirmed-value"><?= $confirmed_count ?></div>
            </div>
        </div>

        <div class="table-card">
            <table class="booking-table">
                <thead>
                    <tr>
                        <th>REF</th>
                        <th>CLIENT DETAILS</th>
                        <th>PACKAGE & SLOT</th>
                        <th>PROOF</th>
                        <th>STATUS</th>
                        <th class="text-right">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bookings)): ?>
                        <tr><td colspan="6" class="admin-empty-state">No booking entries found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $b): 
                            $jsonBooking = htmlspecialchars(json_encode([
                                'id' => $b['booking_id'],
                                'ref' => $b['booking_reference'] ?: 'BK-'.$b['booking_id'],
                                'name' => $b['full_name'] ?: 'Client',
                                'email' => $b['email'] ?: 'N/A',
                                'phone' => $b['phone'] ?: 'N/A',
                                'package' => $b['package_name'],
                                'price' => number_format($b['package_price'], 0),
                                'date' => date('M d, Y', strtotime($b['schedule_date'])),
                                'time' => date('g:i A', strtotime($b['schedule_time'])),
                                'notes' => $b['notes'] ?: 'None',
                                'proof' => $b['payment_proof'] ? '../'.$b['payment_proof'] : null,
                                'status' => $b['booking_status']
                            ]), ENT_QUOTES, 'UTF-8');
                        ?>
                            <tr class="clickable-row" onclick="showBookingDetails(<?= $jsonBooking ?>)">
                                <td><strong><?= htmlspecialchars($b['booking_reference'] ?: 'BK-'.$b['booking_id']) ?></strong></td>
                                <td>
                                    <strong><?= htmlspecialchars($b['full_name'] ?: 'Client') ?></strong><br>
                                    <span class="admin-detail"><?= htmlspecialchars($b['email'] ?: 'N/A') ?></span><br>
                                    <span class="admin-detail"><?= htmlspecialchars($b['phone'] ?: 'N/A') ?></span>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($b['package_name']) ?></strong> (₱<?= number_format($b['package_price'], 0) ?>)<br>
                                    <span class="admin-detail">
                                        📅 <?= date('M d, Y', strtotime($b['schedule_date'])) ?> &nbsp;|&nbsp; ⏰ <?= date('g:i A', strtotime($b['schedule_time'])) ?>
                                    </span>
                                </td>
                                <td onclick="event.stopPropagation();">
                                    <?php if (!empty($b['payment_proof'])): ?>
                                        <a href="../<?= htmlspecialchars($b['payment_proof']) ?>" target="_blank" class="admin-proof-link">View Proof &rarr;</a>
                                    <?php else: ?>
                                        <span class="admin-none">None</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge-status status-<?= strtolower($b['booking_status']) ?>">
                                        <?= htmlspecialchars($b['booking_status']) ?>
                                    </span>
                                </td>
                                <td class="text-right" onclick="event.stopPropagation();">
                                    <div class="action-btn-group">
                                        <form method="POST" class="admin-inline">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="booking_id" value="<?= $b['booking_id'] ?>">
                                            <?php if ($b['booking_status'] !== 'Confirmed'): ?>
                                                <button type="submit" name="status" value="Confirmed" class="btn-act btn-approve">Approve</button>
                                            <?php endif; ?>
                                            <?php if ($b['booking_status'] !== 'Cancelled'): ?>
                                                <button type="submit" name="status" value="Cancelled" class="btn-act btn-cancel">Cancel</button>
                                            <?php endif; ?>
                                        </form>

                                        <form method="POST" class="admin-inline" onsubmit="return confirm('Are you sure you want to delete this booking?');">
                                            <input type="hidden" name="action" value="delete_booking">
                                            <input type="hidden" name="booking_id" value="<?= $b['booking_id'] ?>">
                                            <button type="submit" class="btn-act btn-delete">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

    <div class="modal-overlay" id="createModal">
        <div class="modal-card">
            <div class="modal-header">
                <h2>Create Walk-In Reservation</h2>
                <button type="button" class="btn-close" onclick="closeModal('createModal')">&times;</button>
            </div>

            <div class="step-indicators">
                <span class="step-pill active" id="ind-1">1. Customer & Pkg</span>
                <span class="step-pill" id="ind-2">2. Customization</span>
                <span class="step-pill" id="ind-3">3. Schedule</span>
                <span class="step-pill" id="ind-4">4. Payment</span>
            </div>

            <form method="POST" enctype="multipart/form-data" id="walkinForm">
                <input type="hidden" name="action" value="create_booking">

                <div class="step-content active" id="step-1">
                    <h3 class="admin-step-title">Step 1: Client & Package Selection</h3>
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
                    <div class="admin-right-action">
                        <button type="button" class="btn-create" onclick="goToStep(2)">Next: Customization &rarr;</button>
                    </div>
                </div>

                <div class="step-content" id="step-2">
                    <h3 class="admin-step-title">Step 2: Customization & Add-ons</h3>
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
                        <label class="admin-pet-label">
                            <input type="checkbox" name="has_pets" id="has_pets" onchange="togglePetDetails()">
                            Client bringing pets? <span class="admin-free-label">(Free of Charge)</span>
                        </label>
                    </div>
                    <div class="form-group admin-pet-box" id="pet_box">
                        <label>Pet Details (Breed / Count)</label>
                        <input type="text" name="pet_details" placeholder="e.g. 1 Golden Retriever, 2 Cats">
                    </div>
                    <div class="admin-form-actions">
                        <button type="button" onclick="goToStep(1)" class="admin-back-button">&larr; Back</button>
                        <button type="button" class="btn-create" onclick="goToStep(3)">Next: Schedule &rarr;</button>
                    </div>
                </div>

                <div class="step-content" id="step-3">
                    <h3 class="admin-step-title">Step 3: Schedule Slot</h3>
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
                    <div class="admin-form-actions">
                        <button type="button" onclick="goToStep(2)" class="admin-back-button">&larr; Back</button>
                        <button type="button" class="btn-create" onclick="goToStep(4)">Next: Payment &rarr;</button>
                    </div>
                </div>

                <div class="step-content" id="step-4">
                    <h3 class="admin-step-title">Step 4: Payment Method & Proof</h3>
                    
                    <label class="admin-payment-label">Select Payment Method</label>
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

                    <div class="form-group admin-proof-group" id="proof_upload_group">
                        <label>Upload Payment Receipt / Proof</label>
                        <input type="file" name="payment_proof" accept="image/*,.pdf">
                        <span class="admin-proof-help">Required for GCash, BDO, or GoTyme walk-ins.</span>
                    </div>

                    <div class="admin-form-actions payment-actions">
                        <button type="button" onclick="goToStep(3)" class="admin-back-button">&larr; Back</button>
                        <button type="submit" class="btn-create">Complete Walk-In Booking</button>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <div class="modal-overlay" id="detailModal">
        <div class="modal-card modal-card-clean">
            <div class="modal-header">
                <h2>Reservation Details</h2>
                <button type="button" class="btn-close" onclick="closeModal('detailModal')">&times;</button>
            </div>
            
            <div class="detail-grid">
                <div class="detail-item">
                    <label>Reference</label>
                    <p id="det-ref" class="highlight-pink"></p>
                </div>
                <div class="detail-item">
                    <label>Status</label>
                    <p><span id="det-status" class="badge-status"></span></p>
                </div>
                <div class="detail-item">
                    <label>Client Name</label>
                    <p id="det-name"></p>
                </div>
                <div class="detail-item">
                    <label>Contact Info</label>
                    <p><span id="det-email"></span><br><span id="det-phone"></span></p>
                </div>
                <div class="detail-item">
                    <label>Package</label>
                    <p><span id="det-package"></span> (₱<span id="det-price"></span>)</p>
                </div>
                <div class="detail-item">
                    <label>Date & Time</label>
                    <p>📅 <span id="det-date"></span> | ⏰ <span id="det-time"></span></p>
                </div>
                <div class="detail-item full-width">
                    <label>Notes & Add-ons</label>
                    <div id="det-notes-tags" class="addons-tags-container"></div>
                </div>
                <div class="detail-item full-width" id="det-proof-container">
                    <label>Payment Proof</label>
                    <div id="det-proof-preview"></div>
                </div>
            </div>

            <div class="modal-actions-footer">
                <form method="POST" id="modal-approve-form" class="admin-inline">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="booking_id" id="modal-booking-id-1">
                    <button type="submit" name="status" value="Confirmed" class="btn-act btn-approve btn-lg">Approve Reservation</button>
                </form>

                <form method="POST" id="modal-cancel-form" class="admin-inline">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="booking_id" id="modal-booking-id-2">
                    <button type="submit" name="status" value="Cancelled" class="btn-act btn-cancel btn-lg">Cancel Reservation</button>
                </form>

                <form method="POST" class="admin-inline" onsubmit="return confirm('Delete this booking permanently?');">
                    <input type="hidden" name="action" value="delete_booking">
                    <input type="hidden" name="booking_id" id="modal-booking-id-3">
                    <button type="submit" class="btn-act btn-delete btn-lg">Delete</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openModal(id) { 
            document.getElementById(id).classList.add('is-visible');
        }
        function closeModal(id) { 
            document.getElementById(id).classList.remove('is-visible');
        }

        function showBookingDetails(b) {
            document.getElementById('det-ref').innerText = b.ref;
            document.getElementById('det-name').innerText = b.name;
            document.getElementById('det-email').innerText = b.email;
            document.getElementById('det-phone').innerText = b.phone;
            document.getElementById('det-package').innerText = b.package;
            document.getElementById('det-price').innerText = b.price;
            document.getElementById('det-date').innerText = b.date;
            document.getElementById('det-time').innerText = b.time;

            const tagsContainer = document.getElementById('det-notes-tags');
            tagsContainer.innerHTML = '';

            if (b.notes && b.notes !== 'None') {
                const items = b.notes.split('|');
                items.forEach(item => {
                    const parts = item.split(':');
                    if (parts.length >= 2) {
                        const key = parts[0].trim();
                        const val = parts.slice(1).join(':').trim();
                        
                        const tag = document.createElement('div');
                        tag.className = 'addon-tag';
                        tag.innerHTML = `<span class="tag-key">${key}</span><span class="tag-val">${val}</span>`;
                        tagsContainer.appendChild(tag);
                    } else if (item.trim() !== '') {
                        const tag = document.createElement('div');
                        tag.className = 'addon-tag';
                        tag.innerHTML = `<span class="tag-val">${item.trim()}</span>`;
                        tagsContainer.appendChild(tag);
                    }
                });
            } else {
                tagsContainer.innerHTML = `<span class="admin-none">No notes or add-ons provided.</span>`;
            }

            const badge = document.getElementById('det-status');
            badge.innerText = b.status;
            badge.className = 'badge-status status-' + b.status.toLowerCase();

            const proofDiv = document.getElementById('det-proof-preview');
            if (b.proof) {
                proofDiv.innerHTML = `<a href="${b.proof}" target="_blank" class="admin-proof-link">View Uploaded Proof Image &rarr;</a>`;
            } else {
                proofDiv.innerHTML = `<span class="admin-none">No payment proof uploaded</span>`;
            }

            document.getElementById('modal-booking-id-1').value = b.id;
            document.getElementById('modal-booking-id-2').value = b.id;
            document.getElementById('modal-booking-id-3').value = b.id;

            openModal('detailModal');
        }

        function goToStep(step) {
            document.querySelectorAll('.step-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.step-pill').forEach(el => el.classList.remove('active'));
            
            document.getElementById('step-' + step).classList.add('active');
            document.getElementById('ind-' + step).classList.add('active');
        }

        function togglePetDetails() {
            const hasPets = document.getElementById('has_pets').checked;
            document.getElementById('pet_box').classList.toggle('is-visible', hasPets);
        }

        function selectPayment(method, el) {
            document.querySelectorAll('.payment-option').forEach(opt => opt.classList.remove('selected'));
            el.classList.add('selected');
            el.querySelector('input[type="radio"]').checked = true;

            const proofGroup = document.getElementById('proof_upload_group');
            if (method === 'cash') {
                proofGroup.classList.remove('is-visible');
            } else {
                proofGroup.classList.add('is-visible');
            }
        }
    </script>
</body>
</html>