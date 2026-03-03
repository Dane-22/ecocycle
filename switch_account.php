<?php
session_start();
if (!isset($_SESSION['user_type'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['user_type'] === 'buyer') {
    // Switch to seller
    header("Location: login.php?as=seller");
    exit();
} else if ($_SESSION['user_type'] === 'seller') {
    // Switch to buyer
    header("Location: login.php?as=buyer");
    exit();
} else {
    // Default fallback
    header("Location: login.php");
    exit();
} 