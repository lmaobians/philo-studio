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

    $stmt = $db->prepare("
        SELECT schedule_time 
        FROM bookings 
        WHERE DATE(schedule_date) = :date 
        AND UPPER(TRIM(booking_status)) IN ('CONFIRMED', 'PENDING')
    ");

    $stmt->execute(['date' => $date]);
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $booked = [];
    foreach ($rows as $timeVal) {
        $timestamp = strtotime($timeVal);
        if ($timestamp !== false) {
            $booked[] = date('H:i:00', $timestamp);
            $booked[] = date('g:i A', $timestamp);
            $booked[] = date('h:i A', $timestamp);
            $booked[] = date('H:i', $timestamp);
            $booked[] = date('g:i a', $timestamp);
            $booked[] = date('h:i a', $timestamp);
        }
    }

    echo json_encode(array_values(array_unique($booked)));
} catch (Exception $e) {
    echo json_encode([]);
}