<?php
// Set session cookie lifetime to 30 days (must be before session_start)
if (session_status() == PHP_SESSION_NONE) {
    session_set_cookie_params(60 * 60 * 24 * 30);
    session_start();
}
// Check if admin is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    // Clear any existing session data for security
    $_SESSION = array();
        // Destroy the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
        // Destroy the session
    session_destroy();
        // Redirect to admin login page
    header("Location: ../admin/adminlogin.php");
    exit();
}
// Session timeout check (8 hours = 28800 seconds)
$session_timeout = 28800; // 8 hours
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    // Session has expired
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header("Location: ../admin/adminlogin.php?error=session_expired");
    exit();
}
// Update last activity time
$_SESSION['last_activity'] = time();

// Additional security: Check if admin account still exists and is active
try {
    require_once 'database.php';
    $stmt = $pdo->prepare("SELECT admin_id, status FROM Admins WHERE admin_id = ? AND status = 'active'");
    $stmt->execute([$_SESSION['user_id']]);
    $admin = $stmt->fetch();
    if (!$admin) {
        // Admin account doesn't exist or is inactive, logout
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        header("Location: ../admin/adminlogin.php?error=account_inactive");
        exit();
    }
} catch (PDOException $e) {
    // If database error, still allow access but log the error
    error_log("Admin session check database error: " . $e->getMessage());
}?> 