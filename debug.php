<?php
session_start();
require_once 'config/database.php';

echo "<h3>1. Active Session Data</h3><pre>";
print_r($_SESSION);
echo "</pre>";

$database = new Database();
$db = $database->getConnection();

echo "<h3>2. Last 5 Rows in Bookings Table</h3><pre>";
$stmt = $db->query("SELECT booking_id, booking_reference, customer_id, package_id, schedule_date, booking_status FROM bookings ORDER BY booking_id DESC LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";
?>