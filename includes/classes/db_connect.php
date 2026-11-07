<?php
// Docker container service name
$host = 'database'; 
// Matches MYSQL_DATABASE in docker-compose.yml
$db   = 'philostudio'; 
// Matches MYSQL_USER and MYSQL_PASSWORD in docker-compose.yml
$user = 'philo_user'; 
$pass = 'philo_password'; 

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>