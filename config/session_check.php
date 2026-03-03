<?php
// Set session cookie lifetime to 30 days (must be before session_start)
if (session_status() == PHP_SESSION_NONE) {
    session_set_cookie_params(60 * 60 * 24 * 30);
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header("Location: login.php");
    exit();
}

// Check if user account is blocked
try {
    require_once 'database.php';
    
    if ($_SESSION['user_type'] == 'buyer') {
        // Check if status column exists first
        try {
            $stmt = $pdo->prepare("SHOW COLUMNS FROM Buyers LIKE 'status'");
            $stmt->execute();
            $statusColumnExists = $stmt->rowCount() > 0;
            
            if ($statusColumnExists) {
                $stmt = $pdo->prepare("SELECT status FROM Buyers WHERE buyer_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
                
                if ($user && $user['status'] == 'blocked') {
                    // Clear session and redirect to login with blocked message
                    session_destroy();
                    header("Location: login.php?error=account_blocked");
                    exit();
                }
            }
            // If status column doesn't exist, assume user is active
        } catch (PDOException $e) {
            // If there's an error checking columns, assume user is active
            error_log("Error checking buyer status column: " . $e->getMessage());
        }
    } elseif ($_SESSION['user_type'] == 'seller') {
        $stmt = $pdo->prepare("SELECT status FROM Sellers WHERE seller_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user && $user['status'] == 'blocked') {
            // Clear session and redirect to login with blocked message
            session_destroy();
            header("Location: login.php?error=account_blocked");
            exit();
        }
    }
} catch (PDOException $e) {
    // If database error, log it but don't block access
    error_log("Session check database error: " . $e->getMessage());
}

// Function to check if user is a buyer
function isBuyer() {
    return $_SESSION['user_type'] == 'buyer';
}

// Function to check if user is a seller
function isSeller() {
    return $_SESSION['user_type'] == 'seller';
}

// Function to get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'];
}

// Function to get current username
function getCurrentUsername() {
    return $_SESSION['username'];
}

// Function to get current fullname
function getCurrentFullname() {
    return $_SESSION['fullname'];
}

// Function to get current email
function getCurrentEmail() {
    return $_SESSION['email'];
}
?> 