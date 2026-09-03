<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';

// Fallback logic for user ID to match dashboard session handling
$customer_id = $_SESSION['customer_id'] ?? $_SESSION['user_id'] ?? null;

if (!$customer_id || !isset($_SESSION['booking_package']) || !isset($_SESSION['booking_date']) || !isset($_SESSION['booking_backdrop'])) {
    header("Location: booking.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Fetch customer details
$cust_stmt = $db->prepare("SELECT full_name, email, phone FROM customers WHERE customer_id = ? LIMIT 1");
$cust_stmt->execute([$customer_id]);
$customer = $cust_stmt->fetch();

$pkg       = $_SESSION['booking_package'];
$date      = $_SESSION['booking_date'];
$time      = $_SESSION['booking_time'];
$backdrop  = $_SESSION['booking_backdrop'];
$has_pets  = $_SESSION['booking_has_pets'];
$pet_info  = $_SESSION['booking_pet_details'];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_mode = $_POST['payment_mode'];
    $consent      = $_POST['privacy_consent'];

    // Handle Image Upload
    $payment_proof_path = '';
    if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath   = $_FILES['payment_proof']['tmp_name'];
        $fileName      = $_FILES['payment_proof']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = 'proof_' . time() . '_' . uniqid() . '.' . $fileExtension;
            $uploadFileDir = './uploads/';

            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }

            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $payment_proof_path = $dest_path;
            } else {
                $error = "There was an error moving the uploaded file. Please ensure the 'uploads/' directory exists and is writable.";
            }
        } else {
            $error = "Upload failed. Allowed file types: JPG, PNG, WEBP, PDF.";
        }
    } else {
        $error = "Please upload your proof of payment before completing the booking.";
    }

    if (empty($error)) {
        $notes = "Backdrop: {$backdrop} | Pets: {$has_pets}";
        if ($has_pets === 'Yes' && !empty($pet_info)) {
            $notes .= " ({$pet_info})";
        }
        $notes .= " | Payment: {$payment_mode} | Social Media Consent: {$consent}";

        // Final double-booking check
        $check = $db->prepare("SELECT booking_id FROM bookings WHERE schedule_date = ? AND schedule_time = ? AND booking_status IN ('Pending', 'Confirmed') LIMIT 1");
        $check->execute([$date, $time]);

        if ($check->fetch()) {
            $error = "That time slot was just taken by another client. Please select a different date or time.";
        } else {
            // Generate unique booking reference (BK-XXXXXX)
            $booking_ref = 'BK-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));

            $query = "INSERT INTO bookings (booking_reference, customer_id, package_id, schedule_date, schedule_time, participants, notes, payment_proof, booking_status) 
                      VALUES (?, ?, ?, ?, ?, 1, ?, ?, 'Pending')";
            $stmt = $db->prepare($query);

            if ($stmt->execute([$booking_ref, $customer_id, $pkg['package_id'], $date, $time, $notes, $payment_proof_path])) {
                unset($_SESSION['booking_package'], $_SESSION['booking_date'], $_SESSION['booking_time'], $_SESSION['booking_backdrop'], $_SESSION['booking_has_pets'], $_SESSION['booking_pet_details']);
                
                $_SESSION['booking_success'] = "Your slot has been reserved! We will verify your payment shortly.";
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Failed to complete booking. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review & Payment | PHILO Studio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body { background-color: #f7f7f8; }
        .booking-container { max-width: 700px; margin: 40px auto; padding: 0 20px; font-family: 'Inter', sans-serif; }
        .step-header { text-align: center; margin-bottom: 25px; }
        .step-header h1 { font-size: 1.8rem; font-weight: 700; color: #111; margin-top: 5px; }
        
        .card-panel { background: #ffffff; border: 1px solid #eaeaea; border-radius: 16px; padding: 32px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); margin-bottom: 20px; }
        
        .summary-card { background: #fafafa; border: 1px solid #f0f0f0; border-radius: 12px; padding: 20px; margin-bottom: 25px; }
        .summary-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed #e2e8f0; font-size: 0.9rem; }
        .summary-row:last-child { border-bottom: none; }
        .summary-label { color: #666; font-weight: 500; }
        .summary-val { color: #111; font-weight: 600; text-align: right; }

        .policy-box { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; font-size: 0.88rem; line-height: 1.6; color: #444; margin-bottom: 25px; }
        .bank-details { background: #f8f9fa; border-radius: 8px; padding: 12px 16px; border: 1px solid #eee; margin: 10px 0; font-size: 0.88rem; }
        
        .custom-select, .file-input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.9rem;
            outline: none;
            background: #fff;
            font-family: inherit;
            box-sizing: border-box;
        }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <div class="booking-container">
        <div class="step-header">
            <p style="text-transform: uppercase; letter-spacing: 1px; font-size: 0.75rem; font-weight: 700; color: #888;">Step 4 of 4</p>
            <h1>Review & Finalize Booking</h1>
        </div>

        <?php if (!empty($error)): ?>
            <div class="auth-error" style="margin-bottom: 20px; padding: 12px; background: #fee2e2; color: #dc2626; border-radius: 8px; font-size: 0.9rem;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card-panel">
            <h3 style="margin-top:0; font-size: 1.1rem; color: #111; margin-bottom: 15px;">Booking Summary</h3>
            
            <div class="summary-card">
                <div class="summary-row"><span class="summary-label">Name:</span><span class="summary-val"><?= htmlspecialchars($customer['full_name'] ?? $_SESSION['customer_name'] ?? $_SESSION['user_name'] ?? 'Client') ?></span></div>
                <div class="summary-row"><span class="summary-label">Email:</span><span class="summary-val"><?= htmlspecialchars($customer['email'] ?? $_SESSION['customer_email'] ?? $_SESSION['user_email'] ?? 'N/A') ?></span></div>
                <div class="summary-row"><span class="summary-label">Phone Number:</span><span class="summary-val"><?= htmlspecialchars($customer['phone'] ?? 'N/A') ?></span></div>
                <div class="summary-row"><span class="summary-label">Package:</span><span class="summary-val"><?= htmlspecialchars($pkg['name']) ?> (₱<?= number_format($pkg['price'], 0) ?>)</span></div>
                <div class="summary-row"><span class="summary-label">Backdrop Color:</span><span class="summary-val"><?= htmlspecialchars($backdrop) ?></span></div>
                <div class="summary-row"><span class="summary-label">Pets (Yes / No):</span><span class="summary-val"><?= htmlspecialchars($has_pets) ?></span></div>
                <?php if ($has_pets === 'Yes' && !empty($pet_info)): ?>
                    <div class="summary-row"><span class="summary-label">Pet Details:</span><span class="summary-val"><?= htmlspecialchars($pet_info) ?></span></div>
                <?php endif; ?>
                <div class="summary-row"><span class="summary-label">Preferred Date:</span><span class="summary-val"><?= htmlspecialchars($date) ?></span></div>
                <div class="summary-row"><span class="summary-label">Preferred Time:</span><span class="summary-val"><?= date("g:i A", strtotime($time)) ?></span></div>
            </div>

            <div class="policy-box">
                <strong style="color: #111;">Mode of Payment Accounts:</strong>
                <div class="bank-details">
                    <strong>BDO:</strong> Niña Aliza B. Ramas — 011790056258<br>
                    <strong>GCASH:</strong> Niña Aliza B. Ramas — 0956 876 6873
                </div>

                <strong style="color: #111;">Reschedule and Cancellation Policy</strong>
                <ul style="margin: 8px 0; padding-left: 20px;">
                    <li>You may reschedule your slot for free after your payment has been made.</li>
                    <li>No refunds and rebooking within the lock-in period of 2 days before your reserved time slot.</li>
                    <li>A rescheduling fee (45% of your package) will be charged should you wish to reschedule your slot after the lock-in period.</li>
                </ul>

                <strong style="color: #111;">Studio Policy Reminders:</strong>
                <ul style="margin: 8px 0; padding-left: 20px;">
                    <li><strong>Payment Policy:</strong> We allow up to one (1) hour for payment to secure your booking slot.</li>
                    <li><strong>Grace Period:</strong> We strictly observe a 10-minute grace period per client.</li>
                </ul>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="payment_mode" style="font-size: 0.9rem; font-weight: 600; display: block; margin-bottom: 8px;">Mode of Payment</label>
                    <select id="payment_mode" name="payment_mode" class="custom-select" required>
                        <option value="">-- Choose Payment Option --</option>
                        <option value="BDO">BDO</option>
                        <option value="GCash">GCash</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="font-size: 0.9rem; font-weight: 600; display: block; margin-bottom: 6px;">Upload Proof of Payment (Screenshot/Receipt)</label>
                    <input type="file" name="payment_proof" class="file-input" accept="image/*,.pdf" required>
                    <span style="font-size: 0.8rem; color: #777; margin-top: 4px; display: block;">Accepted formats: JPG, PNG, WEBP, PDF.</span>
                </div>

                <div class="form-group" style="margin-bottom: 25px;">
                    <label style="font-size: 0.9rem; font-weight: 600; display: block; margin-bottom: 6px;">Privacy and Consent (Required)</label>
                    <p style="font-size: 0.83rem; color: #666; margin-top: 0; line-height: 1.5; margin-bottom: 10px;">
                        As we would love to share your cool poses and photos, we value our client's privacy to fulfill our studio's principles which is to provide a safe place for YOU to just BE YOU. PHILO Studio would like to ask for your consent if you're comfortable with us posting your photos taken during your shoot in the studio on our social media accounts (Facebook and Instagram) for content purposes.
                    </p>
                    <select name="privacy_consent" class="custom-select" required>
                        <option value="">-- Choose Yes or No --</option>
                        <option value="Yes">Yes</option>
                        <option value="No">No</option>
                    </select>
                </div>

                <div style="display: flex; gap: 12px; margin-top: 25px;">
                    <a href="booking_step3.php" class="btn-primary" style="background: #f0f0f0; color: #333; text-decoration: none; text-align: center; flex: 1; padding: 14px; border-radius: 10px;">&larr; Back</a>
                    <button type="submit" class="btn-primary" style="flex: 2; padding: 14px; border-radius: 10px;">SUBMIT PROOF & CONFIRM</button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>