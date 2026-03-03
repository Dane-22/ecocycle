<?php
// Load environment variables
require_once __DIR__ . '/env.php';

// Database configuration from environment variables
$host = env('DB_HOST', 'localhost');
$dbname = env('DB_NAME', 'ecocycledb');
$username = env('DB_USER', 'root');
$password = env('DB_PASSWORD', '');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>