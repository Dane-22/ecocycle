<?php
session_start();
require_once 'config/database.php';

// Check if seller is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'seller') {
    header('Location: login.php');
    exit();
}

$seller_id = $_SESSION['user_id'];

// Fetch seller info
$stmt = $pdo->prepare('SELECT * FROM Sellers WHERE seller_id = ?');
$stmt->execute([$seller_id]);
$seller = $stmt->fetch();

if (!$seller) {
    header('Location: login.php');
    exit();
}

// Check if buyer account already exists with same email
$stmt = $pdo->prepare('SELECT * FROM Buyers WHERE email = ?');
$stmt->execute([$seller['email']]);
$buyer = $stmt->fetch();

if (!$buyer) {
    // Create buyer account with seller's credentials
    $stmt = $pdo->prepare('INSERT INTO Buyers (fullname, username, phone_number, email, password, address, status) VALUES (?, ?, ?, ?, ?, ?, "active")');
    $stmt->execute([
        $seller['fullname'],
        $seller['username'],
        $seller['phone_number'],
        $seller['email'],
        $seller['password'], // already hashed
        $seller['address']
    ]);
    $buyer_id = $pdo->lastInsertId();
} else {
    $buyer_id = $buyer['buyer_id'];
}

// Log out seller session
session_unset();
session_destroy();
session_start();

// Log in as buyer
$stmt = $pdo->prepare('SELECT * FROM Buyers WHERE buyer_id = ?');
$stmt->execute([$buyer_id]);
$buyer = $stmt->fetch();

$_SESSION['user_id'] = $buyer['buyer_id'];
$_SESSION['username'] = $buyer['username'];
$_SESSION['fullname'] = $buyer['fullname'];
$_SESSION['user_type'] = 'buyer';
$_SESSION['email'] = $buyer['email'];

header('Location: home.php?msg=now_buyer');
exit(); 