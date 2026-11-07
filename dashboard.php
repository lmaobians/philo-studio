<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

$customer_id = $_SESSION['customer_id'] ?? $_SESSION['user_id'] ?? null;

if (!$customer_id) {
    header("Location: login.php");
    exit();
}

$user_name  = $_SESSION['customer_name'] ?? $_SESSION['user_name'] ?? 'Client';
$user_email = $_SESSION['customer_email'] ?? $_SESSION['user_email'] ?? '';

$database = new Database();
$db = $database->getConnection();

// Fetch customer contact phone number
$cust_stmt = $db->prepare("SELECT phone FROM customers WHERE customer_id = ? LIMIT 1");
$cust_stmt->execute([(int)$customer_id]);
$cust_info = $cust_stmt->fetch(PDO::FETCH_ASSOC);
$user_phone = $cust_info['phone'] ?? 'N/A';

$stmt = $db->prepare("
    SELECT 
        b.booking_id AS id,
        b.booking_reference,
        b.schedule_date AS booking_date,
        b.schedule_time AS booking_time,
        b.booking_status AS status,
        b.notes,
        b.payment_proof,
        COALESCE(p.name, 'Studio Package') AS package, 
        COALESCE(p.price, 0) AS price 
    FROM bookings b 
    LEFT JOIN packages p ON b.package_id = p.package_id 
    WHERE b.customer_id = ? 
    ORDER BY b.booking_id DESC
");

$stmt->execute([(int)$customer_id]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper function to parse notes string into tags array
function parseNotesToTags($notes_string) {
    $tags = [];
    if (empty($notes_string)) return $tags;
    
    $parts = explode('|', $notes_string);
    foreach ($parts as $part) {
        $pair = explode(':', trim($part), 2);
        if (count($pair) === 2) {
            $key = trim($pair[0]);
            $val = trim($pair[1]);
            $tags[$key] = $val;
        }
    }
    return $tags;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - Philo Studio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,400..700;1,400..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Extra spacing before the footer */
        .customer-dashboard-container {
            margin-bottom: 6rem !important;
            padding-bottom: 2rem;
        }
        .customer-booking-item {
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .customer-booking-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body class="customer-dashboard-page">

    <?php include 'includes/header.php'; ?>

    <section class="dashboard-container customer-dashboard-container">
        
        <?php if (isset($_SESSION['booking_success'])): ?>
            <div class="booking-success-message">
                <?= $_SESSION['booking_success']; ?>
                <?php unset($_SESSION['booking_success']); ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-header customer-dashboard-header">
            <div>
                <p class="dashboard-welcome-label">WELCOME BACK</p>
                <h1 class="dashboard-user-name"><?= htmlspecialchars($user_name) ?></h1>
                <p class="dashboard-user-email"><?= htmlspecialchars($user_email) ?></p>
            </div>
            <a href="packages.php" class="btn-primary dashboard-book-button">BOOK NEW SESSION</a>
        </div>

        <div class="dashboard-card customer-bookings-card">
            <h2 class="bookings-card-title">My Bookings (Total: <?= count($bookings) ?>)</h2>

            <?php if (empty($bookings)): ?>
                <div class="empty-state customer-empty-state">
                    <p>You have no bookings yet.</p>
                    <a href="packages.php" class="explore-packages-link">Explore packages &rarr;</a>
                </div>
            <?php else: ?>
                <div class="booking-list customer-booking-list">
                    <?php foreach ($bookings as $b): 
                        $notes_tags = parseNotesToTags($b['notes'] ?? '');
                        $status_str = $b['status'] ?? 'Pending';
                    ?>
                        <div class="customer-booking-item"
                             onclick='openReservationModal(<?= json_encode([
                                 "ref"          => $b["booking_reference"] ?? ("BK-" . $b["id"]),
                                 "status"       => $status_str,
                                 "client_name"  => $user_name,
                                 "email"        => $user_email,
                                 "phone"        => $user_phone,
                                 "package"      => $b["package"] . " (₱" . number_format($b["price"], 0) . ")",
                                 "datetime"     => date("M d, Y", strtotime($b["booking_date"])) . " | " . (!empty($b["booking_time"]) ? date("g:i A", strtotime($b["booking_time"])) : "Scheduled"),
                                 "proof"        => $b["payment_proof"] ?? "",
                                 "tags"         => $notes_tags
                             ]) ?>)'>
                            <div>
                                <h3 class="customer-package-name"><?= htmlspecialchars($b['package']) ?></h3>
                                <p class="customer-booking-date">
                                    📅 <?= date('M d, Y', strtotime($b['booking_date'])) ?> &nbsp;|&nbsp; 
                                    ⏰ <?= !empty($b['booking_time']) ? date('g:i A', strtotime($b['booking_time'])) : 'Scheduled' ?>
                                </p>
                                <span class="customer-booking-reference">Ref: <strong><?= htmlspecialchars($b['booking_reference']) ?></strong></span>
                            </div>
                            <div class="customer-booking-summary">
                                <span class="customer-status-badge">
                                    <?= htmlspecialchars($status_str) ?>
                                </span>
                                <div class="customer-booking-price">₱<?= number_format($b['price'], 0) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Reservation Details Modal -->
    <div id="reservationModal" class="modal-overlay" onclick="closeModalOnOverlay(event)">
        <div class="modal-card modal-card-clean">
            <div class="modal-header">
                <h2>Reservation Details</h2>
                <button type="button" class="btn-close" onclick="closeReservationModal()">&times;</button>
            </div>
            
            <div class="detail-grid">
                <div class="detail-item">
                    <label>Reference</label>
                    <p id="m-ref" style="font-weight: 700;"></p>
                </div>
                <div class="detail-item">
                    <label>Status</label>
                    <p><span id="m-status" class="badge-status"></span></p>
                </div>
                
                <div class="detail-item">
                    <label>Client Name</label>
                    <p id="m-name"></p>
                </div>
                <div class="detail-item">
                    <label>Contact Info</label>
                    <p id="m-email" class="admin-detail"></p>
                    <p id="m-phone" class="admin-detail"></p>
                </div>

                <div class="detail-item">
                    <label>Package</label>
                    <p id="m-package"></p>
                </div>
                <div class="detail-item">
                    <label>Date & Time</label>
                    <p id="m-datetime"></p>
                </div>

                <div class="detail-item full-width">
                    <label>Notes & Add-ons</label>
                    <div id="m-tags" class="addons-tags-container"></div>
                </div>

                <div class="detail-item full-width text-center" style="margin-top: 0.5rem;">
                    <label>Payment Proof</label>
                    <div id="m-proof-container"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openReservationModal(data) {
            document.getElementById('m-ref').innerText = data.ref;
            
            const statusEl = document.getElementById('m-status');
            statusEl.innerText = data.status.toUpperCase();
            statusEl.className = 'badge-status status-' + data.status.toLowerCase();

            document.getElementById('m-name').innerText = data.client_name;
            document.getElementById('m-email').innerText = data.email;
            document.getElementById('m-phone').innerText = data.phone;
            document.getElementById('m-package').innerText = data.package;
            document.getElementById('m-datetime').innerText = data.datetime;

            // Render Notes & Add-ons as tags
            const tagsContainer = document.getElementById('m-tags');
            tagsContainer.innerHTML = '';
            
            if (data.tags && Object.keys(data.tags).length > 0) {
                for (const [key, val] of Object.entries(data.tags)) {
                    const tagDiv = document.createElement('div');
                    tagDiv.className = 'addon-tag';
                    tagDiv.innerHTML = `<span class="tag-key">${key}</span><span class="tag-val">${val}</span>`;
                    tagsContainer.appendChild(tagDiv);
                }
            } else {
                tagsContainer.innerHTML = '<span class="admin-none">No add-ons or notes attached.</span>';
            }

            // Render Proof Image link
            const proofContainer = document.getElementById('m-proof-container');
            if (data.proof && data.proof.trim() !== '') {
                proofContainer.innerHTML = `<a href="${data.proof}" target="_blank" class="admin-proof-link">View Uploaded Proof Image &rarr;</a>`;
            } else {
                proofContainer.innerHTML = '<span class="admin-none">No payment proof uploaded.</span>';
            }

            document.getElementById('reservationModal').classList.add('is-visible');
        }

        function closeReservationModal() {
            document.getElementById('reservationModal').classList.remove('is-visible');
        }

        function closeModalOnOverlay(e) {
            if (e.target.id === 'reservationModal') {
                closeReservationModal();
            }
        }
    </script>

    <?php include 'includes/footer.php'; ?>

</body>
</html>