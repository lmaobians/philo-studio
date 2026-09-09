<?php
session_start();
header('Content-Type: application/json');

require_once 'config/database.php';

$date = $_GET['date'] ?? null;

if (!$date) {
    echo json_encode([]);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $blocked = [];

    // 1. Fetch Confirmed/Pending Customer Bookings
    $stmt = $db->prepare("
        SELECT schedule_time 
        FROM bookings 
        WHERE DATE(schedule_date) = :date 
        AND UPPER(TRIM(booking_status)) IN ('CONFIRMED', 'PENDING')
    ");
    $stmt->execute(['date' => $date]);
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($rows as $timeVal) {
        if (!empty($timeVal)) {
            $ts = strtotime($timeVal);
            if ($ts !== false) {
                $blocked[] = date('H:i:00', $ts);
            }
        }
    }

    // 2. Fetch Admin Blockouts for the same date
    $stmtBlockouts = $db->prepare("
        SELECT start_time, end_time 
        FROM blockout_dates 
        WHERE blockout_date = :date
    ");
    $stmtBlockouts->execute(['date' => $date]);
    $blockouts = $stmtBlockouts->fetchAll(PDO::FETCH_ASSOC);

    // Operating slots array for reference
    $operatingSlots = [
        '10:00:00', '11:00:00', '12:00:00', 
        '13:00:00', '14:00:00', '15:00:00', 
        '16:00:00', '17:00:00', '18:00:00', '19:00:00'
    ];

    foreach ($blockouts as $b) {
        $startTime = $b['start_time'];
        $endTime   = $b['end_time'];

        // If start/end time is missing or set to 00:00:00, block the full day
        $isFullDay = (
            empty($startTime) || 
            empty($endTime) || 
            $startTime === '00:00:00'
        );

        if ($isFullDay) {
            $blocked = array_merge($blocked, $operatingSlots);
            break; // Whole day is blocked, no need to check further blockouts
        }

        // Handle partial range blockouts (e.g. 09:00 AM - 12:00 PM)
        $blockStart = strtotime($date . ' ' . $startTime);
        $blockEnd   = strtotime($date . ' ' . $endTime);

        foreach ($operatingSlots as $slot) {
            $slotTime = strtotime($date . ' ' . $slot);
            if ($slotTime >= $blockStart && $slotTime < $blockEnd) {
                $blocked[] = $slot;
            }
        }
    }

    // Format output variants to guarantee Javascript string matching compatibility
    $formattedSlots = [];
    foreach ($blocked as $slotStr) {
        $ts = strtotime($slotStr);
        if ($ts !== false) {
            $formattedSlots[] = date('H:i:00', $ts);
            $formattedSlots[] = date('g:i A', $ts);
            $formattedSlots[] = date('h:i A', $ts);
            $formattedSlots[] = date('H:i', $ts);
            $formattedSlots[] = date('g:i a', $ts);
            $formattedSlots[] = date('h:i a', $ts);
        }
    }

    echo json_encode(array_values(array_unique($formattedSlots)));

} catch (Exception $e) {
    echo json_encode([]);
}