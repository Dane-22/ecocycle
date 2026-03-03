<?php
session_start();

// Include database connection
require_once 'config/database.php';

// Include PHPMailer
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';
require_once 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$error_message = '';
$last_user_type = isset($_COOKIE['last_user_type']) ? $_COOKIE['last_user_type'] : null;
$account_locked = false;
$reset_required = false;

// Function to record failed login attempt
function recordFailedAttempt($login_identifier) {
    global $pdo;
    try {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $stmt = $pdo->prepare("INSERT INTO login_attempts (login_identifier, ip_address, user_agent) VALUES (?, ?, ?)");
        $stmt->execute([$login_identifier, $ip_address, $user_agent]);
    } catch (PDOException $e) {
        error_log("Failed to record login attempt: " . $e->getMessage());
    }
}

// Function to check if account is locked
function isAccountLocked($login_identifier) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as attempt_count FROM login_attempts WHERE login_identifier = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $stmt->execute([$login_identifier]);
        $result = $stmt->fetch();
        return $result['attempt_count'] >= 5;
    } catch (PDOException $e) {
        error_log("Failed to check account lock: " . $e->getMessage());
        return false;
    }
}

// Function to send password reset email
function sendPasswordResetEmail($user_email, $user_type) {
    global $pdo;
    
    // Find user and create/reset token
    if ($user_type == 'buyer') {
        $stmt = $pdo->prepare("SELECT * FROM Buyers WHERE email = ? LIMIT 1");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM Sellers WHERE email = ? LIMIT 1");
    }
    
    $stmt->execute([$user_email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return false;
    }
    
    // Create reset token
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Update user with reset token
    if ($user_type == 'buyer') {
        $update = $pdo->prepare("UPDATE Buyers SET reset_token = ?, reset_token_expires = ?, reset_required = 1 WHERE buyer_id = ?");
        $update->execute([$token, $expires, $user['buyer_id']]);
    } else {
        $update = $pdo->prepare("UPDATE Sellers SET reset_token = ?, reset_token_expires = ?, reset_required = 1 WHERE seller_id = ?");
        $update->execute([$token, $expires, $user['seller_id']]);
    }
    
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'honeyboyb.corial@gmail.com';
        $mail->Password   = 'keew djpl zgpw clpv';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Recipients
        $mail->setFrom('honeyboyb.corial@gmail.com', 'Ecocycle Support');
        $mail->addAddress($user_email);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Account Security Alert - Password Reset Required';
        
        $reset_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/Ecocycle/reset-password.php?token=' . urlencode($token) . '&type=' . $user_type;
        
        $mail->Body = '
            <html>
            <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
                <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;">
                    <div style="text-align: center; margin-bottom: 30px;">
                        <h2 style="color: #1a5f7a; margin-bottom: 10px;">Ecocycle Security Alert</h2>
                        <p style="color: #666; font-size: 16px;">Account Security Notification</p>
                    </div>
                    
                    <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin: 20px 0;">
                        <h3 style="color: #856404; margin-top: 0;">⚠️ Security Alert</h3>
                        <p style="color: #856404; margin-bottom: 0;">We detected multiple failed login attempts on your account. For your security, we require you to reset your password.</p>
                    </div>
                    
                    <div style="margin: 30px 0;">
                        <h3 style="color: #2c3e50; margin-bottom: 15px;">What happened?</h3>
                        <p style="color: #555; margin-bottom: 15px;">Our system detected 5 or more unsuccessful login attempts on your account within the past hour. This could indicate:</p>
                        <ul style="color: #555; padding-left: 20px;">
                            <li>Someone trying to access your account without permission</li>
                            <li>You may have forgotten your password</li>
                            <li>A typo in your login credentials</li>
                        </ul>
                    </div>
                    
                    <div style="margin: 30px 0;">
                        <h3 style="color: #2c3e50; margin-bottom: 15px;">What should you do?</h3>
                        <p style="color: #555; margin-bottom: 20px;">Please reset your password immediately to secure your account:</p>
                        <div style="text-align: center; margin: 30px 0;">
                            <a href="' . $reset_link . '" style="background: linear-gradient(135deg, #1a5f7a 0%, #28bf4b 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px; display: inline-block;">Reset Password Now</a>
                        </div>
                        <p style="color: #666; font-size: 14px; text-align: center; margin-top: 20px;">Or copy and paste this link in your browser:<br>
                        <span style="word-break: break-all; background-color: #f8f9fa; padding: 10px; border-radius: 5px; display: inline-block; margin-top: 10px;">' . $reset_link . '</span></p>
                    </div>
                    
                    <div style="margin: 30px 0; padding: 20px; background-color: #f8f9fa; border-radius: 5px;">
                        <h3 style="color: #2c3e50; margin-bottom: 15px;">🔒 Security Tips:</h3>
                        <ul style="color: #555; padding-left: 20px; margin-bottom: 0;">
                            <li>Choose a strong, unique password</li>
                            <li>Don\'t share your password with anyone</li>
                            <li>Enable two-factor authentication if available</li>
                            <li>Regularly update your password</li>
                        </ul>
                    </div>
                    
                    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; text-align: center; color: #666; font-size: 14px;">
                        <p style="margin-bottom: 10px;">If you didn\'t attempt to log in, please contact us immediately:</p>
                        <p style="margin-bottom: 0;"><strong>Email:</strong> support@ecocycle.com | <strong>Phone:</strong> +1-234-567-8900</p>
                    </div>
                    
                    <div style="text-align: center; margin-top: 30px; color: #999; font-size: 12px;">
                        <p>This is an automated message. Please do not reply to this email.</p>
                        <p>© 2026 Ecocycle. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>'
        ;
        
        $mail->AltBody = 'Security Alert: Multiple failed login attempts detected on your Ecocycle account. Please reset your password immediately by visiting: ' . $reset_link;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Password reset email failed: " . $mail->ErrorInfo);
        return false;
    }
}

// Function to clear old login attempts
function clearOldAttempts($login_identifier) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE login_identifier = ?");
        $stmt->execute([$login_identifier]);
    } catch (PDOException $e) {
        error_log("Failed to clear login attempts: " . $e->getMessage());
    }
}

// Check for blocked account error from session check
if (isset($_GET['error']) && $_GET['error'] == 'account_blocked') {
  // Gentler, disciplinary-style message
  $error_message = 'Your account has been suspended due to policy or conduct concerns. Please contact the administrator to resolve this matter.';
}

// Handle buyer-to-seller conversion if logged in as buyer and ?as=seller is set
if (isset(
    $_GET['as']) && $_GET['as'] === 'seller' && isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'buyer') {
    require_once 'config/database.php';
    $buyer_id = $_SESSION['user_id'];
    // Fetch buyer info
    $stmt = $pdo->prepare("SELECT * FROM Buyers WHERE buyer_id = ?");
    $stmt->execute([$buyer_id]);
    $buyer = $stmt->fetch();
    if ($buyer) {
        // Check if already a seller (by email or username)
        $stmt = $pdo->prepare("SELECT * FROM Sellers WHERE email = ? OR username = ?");
        $stmt->execute([$buyer['email'], $buyer['username']]);
        $existing_seller = $stmt->fetch();
        if ($existing_seller) {
            // Log in as seller
            $_SESSION['user_id'] = $existing_seller['seller_id'];
            $_SESSION['username'] = $existing_seller['username'];
            $_SESSION['fullname'] = $existing_seller['fullname'];
            $_SESSION['user_type'] = 'seller';
            $_SESSION['email'] = $existing_seller['email'];
            // Set last_user_type cookie for role switch
            setcookie('last_user_type', 'seller', time() + (86400 * 30), "/");
            header("Location: seller-dashboard.php?msg=already_seller");
            exit();
        } else {
            // Create seller account with same credentials
            $stmt = $pdo->prepare("INSERT INTO Sellers (fullname, username, phone_number, email, password, address, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([
                $buyer['fullname'],
                $buyer['username'],
                $buyer['phone_number'],
                $buyer['email'],
                $buyer['password'], // already hashed
                $buyer['address']
            ]);
            $seller_id = $pdo->lastInsertId();
            // Log in as seller
            $_SESSION['user_id'] = $seller_id;
            $_SESSION['username'] = $buyer['username'];
            $_SESSION['fullname'] = $buyer['fullname'];
            $_SESSION['user_type'] = 'seller';
            $_SESSION['email'] = $buyer['email'];
            // Set last_user_type cookie for role switch
            setcookie('last_user_type', 'seller', time() + (86400 * 30), "/");
            header("Location: seller-dashboard.php?msg=became_seller");
            exit();
        }
    }
}

// Handle seller-to-buyer conversion if logged in as seller and ?as=buyer is set
if (isset($_GET['as']) && $_GET['as'] === 'buyer' && isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'seller') {
    require_once 'config/database.php';
    $seller_id = $_SESSION['user_id'];
    // Fetch seller info
    $stmt = $pdo->prepare("SELECT * FROM Sellers WHERE seller_id = ?");
    $stmt->execute([$seller_id]);
    $seller = $stmt->fetch();
    if ($seller) {
        // Check if already a buyer (by email or username)
        $stmt = $pdo->prepare("SELECT * FROM Buyers WHERE email = ? OR username = ?");
        $stmt->execute([$seller['email'], $seller['username']]);
        $existing_buyer = $stmt->fetch();
        if ($existing_buyer) {
            // Log in as buyer
            $_SESSION['user_id'] = $existing_buyer['buyer_id'];
            $_SESSION['username'] = $existing_buyer['username'];
            $_SESSION['fullname'] = $existing_buyer['fullname'];
            $_SESSION['user_type'] = 'buyer';
            $_SESSION['email'] = $existing_buyer['email'];
            // Set last_user_type cookie for role switch
            setcookie('last_user_type', 'buyer', time() + (86400 * 30), "/");
            header("Location: home.php?msg=already_buyer");
            exit();
        } else {
            // Create buyer account with same credentials
            $stmt = $pdo->prepare("INSERT INTO Buyers (fullname, username, phone_number, email, password, address, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
            $stmt->execute([
                $seller['fullname'],
                $seller['username'],
                $seller['phone_number'],
                $seller['email'],
                $seller['password'], // already hashed
                $seller['address']
            ]);
            $buyer_id = $pdo->lastInsertId();
            // Log in as buyer
            $_SESSION['user_id'] = $buyer_id;
            $_SESSION['username'] = $seller['username'];
            $_SESSION['fullname'] = $seller['fullname'];
            $_SESSION['user_type'] = 'buyer';
            $_SESSION['email'] = $seller['email'];
            // Set last_user_type cookie for role switch
            setcookie('last_user_type', 'buyer', time() + (86400 * 30), "/");
            header("Location: home.php?msg=became_buyer");
            exit();
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login_identifier = trim($_POST['login_identifier']);
    $password = str_replace(' ', '', $_POST['password']);
    
    // Basic validation
    if (empty($login_identifier) || empty($password)) {
        $error_message = 'Please fill in all required fields.';
    } else {
        // Check if account is locked before proceeding
        if (isAccountLocked($login_identifier)) {
            $account_locked = true;
            $reset_required = true;
            
            // Try to find user to send reset email
            $stmt = $pdo->prepare("SELECT email FROM Buyers WHERE username = ? OR email = ? OR phone_number = ? UNION SELECT email FROM Sellers WHERE username = ? OR email = ? OR phone_number = ?");
            $stmt->execute([$login_identifier, $login_identifier, $login_identifier, $login_identifier, $login_identifier, $login_identifier]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Determine user type for reset link
                $stmt = $pdo->prepare("SELECT 'buyer' as type FROM Buyers WHERE email = ? UNION SELECT 'seller' as type FROM Sellers WHERE email = ?");
                $stmt->execute([$user['email'], $user['email']]);
                $type_result = $stmt->fetch();
                $user_type = $type_result ? $type_result['type'] : 'buyer';
                
                // Send password reset email
                sendPasswordResetEmail($user['email'], $user_type);
            }
            
            $error_message = 'Too many failed login attempts. A password reset email has been sent to your email address. Please check your inbox and follow the instructions to reset your password.';
        } else {
            try {
            // Check if user has a last_user_type preference
            $preferred_user_type = isset($_COOKIE['last_user_type']) ? $_COOKIE['last_user_type'] : null;
            
            // If user has a preferred type, try that first
            if ($preferred_user_type === 'seller') {
              // Try seller first (case sensitive)
              $stmt = $pdo->prepare("SELECT * FROM Sellers WHERE BINARY username = ? OR BINARY email = ? OR phone_number = ?");
              $stmt->execute([$login_identifier, $login_identifier, $login_identifier]);
              $user = $stmt->fetch();
              if ($user && password_verify($password, $user['password'])) {
                $user_type = 'seller';
              } else {
                // Try buyer as fallback (case sensitive)
                $stmt = $pdo->prepare("SELECT * FROM Buyers WHERE BINARY username = ? OR BINARY email = ? OR phone_number = ?");
                $stmt->execute([$login_identifier, $login_identifier, $login_identifier]);
                $user = $stmt->fetch();
                if ($user && password_verify($password, $user['password'])) {
                  $user_type = 'buyer';
                } else {
                  $user_type = '';
                }
              }
            } elseif ($preferred_user_type === 'buyer') {
              // Try buyer first (case sensitive)
              $stmt = $pdo->prepare("SELECT * FROM Buyers WHERE BINARY username = ? OR BINARY email = ? OR phone_number = ?");
              $stmt->execute([$login_identifier, $login_identifier, $login_identifier]);
              $user = $stmt->fetch();
              if ($user && password_verify($password, $user['password'])) {
                $user_type = 'buyer';
              } else {
                // Try seller as fallback (case sensitive)
                $stmt = $pdo->prepare("SELECT * FROM Sellers WHERE BINARY username = ? OR BINARY email = ? OR phone_number = ?");
                $stmt->execute([$login_identifier, $login_identifier, $login_identifier]);
                $user = $stmt->fetch();
                if ($user && password_verify($password, $user['password'])) {
                  $user_type = 'seller';
                } else {
                  $user_type = '';
                }
              }
            } else {
              // No preference, try buyer first (original logic, case sensitive)
              $stmt = $pdo->prepare("SELECT * FROM Buyers WHERE BINARY username = ? OR BINARY email = ? OR phone_number = ?");
              $stmt->execute([$login_identifier, $login_identifier, $login_identifier]);
              $user = $stmt->fetch();
              $user_type = '';
              if ($user && password_verify($password, $user['password'])) {
                $user_type = 'buyer';
              } else {
                // Try to find user in Sellers table (case sensitive)
                $stmt = $pdo->prepare("SELECT * FROM Sellers WHERE BINARY username = ? OR BINARY email = ? OR phone_number = ?");
                $stmt->execute([$login_identifier, $login_identifier, $login_identifier]);
                $user = $stmt->fetch();
                if ($user && password_verify($password, $user['password'])) {
                  $user_type = 'seller';
                }
              }
            }
            if ($user_type === 'buyer') {
                // Check if buyer account is blocked
                // Handle case where status column might not exist (default to active)
                $buyer_status = isset($user['status']) ? $user['status'] : 'active';
                
        if ($buyer_status == 'blocked') {
          // Gentler, disciplinary-style message for buyers
          $error_message = 'Your account has been suspended due to policy or conduct concerns. Please contact the administrator to resolve this matter.';
        } else {
                    // Login successful for active buyers
                    // Clear login attempts on successful login
                    clearOldAttempts($login_identifier);
                    
                    $_SESSION['user_id'] = $user['buyer_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['fullname'] = $user['fullname'];
                    $_SESSION['user_type'] = $user_type;
                    $_SESSION['email'] = $user['email'];
                    // Update last_login for buyer
                    $stmt = $pdo->prepare("UPDATE Buyers SET last_login = NOW() WHERE buyer_id = ?");
                    $stmt->execute([$user['buyer_id']]);
                    // Set last_user_type cookie
                    setcookie('last_user_type', $user_type, time() + (86400 * 30), "/"); // 30 days
                    header("Location: home.php");
                    exit();
                }
            } elseif ($user_type === 'seller') {
                // For sellers, check status and allow login for any status except 'blocked'
        if ($user['status'] == 'blocked') {
          // Gentler, disciplinary-style message for sellers
          $error_message = 'Your account has been suspended due to policy or conduct concerns. Please contact the administrator to resolve this matter.';
                } else {
                    // Login successful for any non-blocked seller (pending, approved, active, etc.)
                    // Clear login attempts on successful login
                    clearOldAttempts($login_identifier);
                    
                    $_SESSION['user_id'] = $user['seller_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['fullname'] = $user['fullname'];
                    $_SESSION['user_type'] = $user_type;
                    $_SESSION['email'] = $user['email'];
                    // Update last_login for seller
                    $stmt = $pdo->prepare("UPDATE Sellers SET last_login = NOW() WHERE seller_id = ?");
                    $stmt->execute([$user['seller_id']]);
                    // Set last_user_type cookie
                    setcookie('last_user_type', $user_type, time() + (86400 * 30), "/"); // 30 days
                    header("Location: seller-dashboard.php");
                    exit();
                }
            } else {
                // Record failed login attempt
                recordFailedAttempt($login_identifier);
                
                // Check if this attempt reached the threshold
                if (isAccountLocked($login_identifier)) {
                    $account_locked = true;
                    $reset_required = true;
                    
                    // Try to find user to send reset email
                    $stmt = $pdo->prepare("SELECT email FROM Buyers WHERE username = ? OR email = ? OR phone_number = ? UNION SELECT email FROM Sellers WHERE username = ? OR email = ? OR phone_number = ?");
                    $stmt->execute([$login_identifier, $login_identifier, $login_identifier, $login_identifier, $login_identifier, $login_identifier]);
                    $user = $stmt->fetch();
                    
                    if ($user) {
                        // Determine user type for reset link
                        $stmt = $pdo->prepare("SELECT 'buyer' as type FROM Buyers WHERE email = ? UNION SELECT 'seller' as type FROM Sellers WHERE email = ?");
                        $stmt->execute([$user['email'], $user['email']]);
                        $type_result = $stmt->fetch();
                        $user_type = $type_result ? $type_result['type'] : 'buyer';
                        
                        // Send password reset email
                        sendPasswordResetEmail($user['email'], $user_type);
                    }
                    
                    $error_message = 'Too many failed login attempts. A password reset email has been sent to your email address. Please check your inbox and follow the instructions to reset your password.';
                } else {
                    // Check remaining attempts
                    $stmt = $pdo->prepare("SELECT COUNT(*) as attempt_count FROM login_attempts WHERE login_identifier = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
                    $stmt->execute([$login_identifier]);
                    $result = $stmt->fetch();
                    $remaining_attempts = 5 - $result['attempt_count'];
                    
                    $error_message = 'Invalid credentials. Please check your login details.';
                    if ($remaining_attempts <= 2) {
                        $error_message .= " You have {$remaining_attempts} attempt(s) remaining before account lockout.";
                    }
                }
            }
        } catch (PDOException $e) {
            $error_message = 'Login failed. Please try again.';
        }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ecocycle Nluc</title>
  <link rel="stylesheet" href="signlogstyle.css">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
  <!-- Add Font Awesome CDN for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<style>
    /* Reset & basics */
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    body, input, button, select {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #1a5f7a 0%, #2c786c 50%, #28bf4b 100%);
    }
    a {
      text-decoration: none;
      color: #1a5f7a;
    }

    /* Layout */
    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 15px;
    }
    header {
      background: #fff;
      padding: 1rem 0;
      border-bottom: 1px solid #eee;
    }
    .header-content {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .logo {
      display: flex;
      align-items: center;
      font-size: 1.25rem;
      font-weight: 500;
    }
    .logo img {
      margin-right: 0.5rem;
    }
    .help {
      font-size: 0.9rem;
      color: #999;
    }

    /* Main split view */
    main {
      display: flex;
      min-height: calc(100vh - 70px);
      flex-wrap: wrap;
    }
    .split {
      flex: 1;
      display: flex;
      justify-content: center;
      align-items: center;
      min-width: 300px;
    }
    .left {
      color: #fff;
      text-align: center;
      padding: 2rem;
    }
    .brand-logo img {
      width: 256px;
      height: 128px;
      margin-bottom: 1rem;
    }

    /* Right card */
    .right {
      padding: 1rem 0;
    }
    .card {
      background: #fff;
      padding: 2rem 1.5rem 1.5rem 1.5rem;
      width: 100%;
      max-width: 400px;
      min-width: 320px;
      border-radius: 18px;
      box-shadow: 0 6px 32px rgba(44,120,108,0.10);
      text-align: center;
      margin: 0 auto;
      position: relative;
      transition: box-shadow 0.2s cubic-bezier(0.4,0,0.2,1), transform 0.2s cubic-bezier(0.4,0,0.2,1);
    }
    .card:hover {
      box-shadow: 0 12px 32px rgba(44,120,108,0.15), 0 2px 8px rgba(0,0,0,0.10);
      transform: translateY(-6px) scale(1.03);
    }
    .card h2 {
      margin-bottom: 1rem;
      font-size: 1.1rem;
      font-weight: 700;
      color: #2c3e50;
      text-align: center;
    }

    /* Form elements */
    .card form input, 
    .card form select {
      width: 100%;
      padding: 0.6rem 0.8rem;
      margin-bottom: 0.8rem;
      border: 1px solid #e0e0e0;
      border-radius: 8px;
      font-size: 0.95rem;
      background-color: #fff;
      color: #000;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    .card form input:focus,
    .card form select:focus {
      border-color: #28bf4b;
      box-shadow: 0 0 0 2px rgba(40,191,75,0.10);
      outline: none;
    }

    /* Placeholder styling */
    .card form input::placeholder,
    .card form select::placeholder {
      color: #6c757d;
      opacity: 0.8;
      font-weight: 400;
    }

    /* Enhanced Select Dropdown Styling */
    .card form select.form-select {
      appearance: none;
      -webkit-appearance: none;
      -moz-appearance: none;
      background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
      background-repeat: no-repeat;
      background-position: right 0.75rem center;
      background-size: 16px 12px;
      padding-right: 2.25rem;
      cursor: pointer;
      margin-bottom: 0.8rem;
    }

    /* Button styling */
    .btn-next {
      width: 100%;
      padding: 0.6rem;
      background: linear-gradient(135deg, #1a5f7a 0%, #28bf4b 100%);
      border: none;
      border-radius: 8px;
      color: #fff;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.2s, box-shadow 0.2s;
      margin-top: 0.3rem;
      box-shadow: 0 2px 10px rgba(40,191,75,0.10);
    }
    .btn-next:hover {
      background: linear-gradient(135deg, #28bf4b 0%, #1a5f7a 100%);
      color: #fff;
      box-shadow: 0 4px 20px rgba(40,191,75,0.15);
    }

    /* Error and success messages */
    .error-message {
      color: #c0392b;
      text-align: center;
      margin-bottom: 0.8rem;
      font-weight: 500;
      font-size: 0.9rem;
    }
    .success-message {
      color: #27ae60;
      text-align: center;
      margin-bottom: 0.8rem;
      font-weight: 500;
      font-size: 0.9rem;
    }

    /* OR divider */
    .divider {
      display: flex;
      align-items: center;
      margin: 0.8rem 0;
      color: #aaa;
      font-size: 0.85rem;
    }
    .divider::before,
    .divider::after {
      content: '';
      flex: 1;
      height: 1px;
      background: #ddd;
    }
    .divider::before {
      margin-right: 0.5em;
    }
    .divider::after {
      margin-left: 0.5em;
    }

    /* Social buttons */
    .social-login {
      display: flex;
      gap: 0.25rem;
      justify-content: center;
      margin-bottom: 0.3rem;
    }
    .btn-social {
      flex: 1;
      padding: 0.3rem;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 0.8rem;
      cursor: pointer;
      background: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 5px;
    }
    .btn-social.fb {
      color:rgb(0, 0, 0);
    }
    .btn-social.google {
      color:rgb(0, 0, 0);
    }

    /* Footer text */
    .footer-text {
      font-size: 0.65rem;
      color: #000;
      margin-top: 0.2rem;
      line-height: 1.1;
    }
    .footer-text a {
      color: #000;
      font-weight: 500;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
      .split {
        flex: 100%;
      }
      .left {
        padding: 1rem;
          display: none !important;
      }
      .card {
        padding: 1.5rem 1.2rem;
      }
        .brand-logo img { 
          display: none !important;
        }
    }

    /* Reduce space between password and select user type fields in login form */
    form > div[style*="position: relative;"] {
      margin-bottom: 0.2rem !important;
    }
  </style>
<body>
  <header>
      <div class="container header-content">
        <img src="images/logo.png.png" alt="Ecocycle logo" width="100" height="40">
      </div>
      
    </div>
  </header>

  <main class="container">
    <div class="split left">
      <div class="brand">
        <div class="brand-logo">
          <img src="images/Ecocycle NLUC bee.png" alt="Ecocycle logo" style="border-radius: 50%; object-fit: cover; width: 320px; height: 320px;">
        </div>
        <div class="brand-tagline" style="margin-top: 1rem; font-size: 2rem; font-weight: 400; color: #fff; text-align: center; line-height: 1.4; font-family: Poppins, sans-serif; font-style: italic;">
          "NLUC: Nurturing a Lasting Unified Conservation"
        </div>
      </div>
    </div>


    <div class="split right">
      <div class="card">
        <h2>Hello, welcome back!</h2>
        
        <?php if (!empty($error_message)): ?>
          <div style="background-color: <?php echo ($account_locked || strpos($error_message, 'blocked') !== false) ? '#fff3cd' : '#f8d7da'; ?>; color: <?php echo ($account_locked || strpos($error_message, 'blocked') !== false) ? '#856404' : '#721c24'; ?>; padding: 12px; border-radius: 4px; margin-bottom: 15px; border: 1px solid <?php echo ($account_locked || strpos($error_message, 'blocked') !== false) ? '#ffeaa7' : '#f5c6cb'; ?>; font-weight: 500;">
            <i class="fas fa-<?php echo ($account_locked || strpos($error_message, 'blocked') !== false) ? 'exclamation-triangle' : 'exclamation-circle'; ?>" style="margin-right: 8px;"></i>
            <?php echo htmlspecialchars($error_message); ?>
            <?php if ($account_locked): ?>
              <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #ffeaa7; font-size: 0.9rem;">
                <strong>Next Steps:</strong><br>
                1. Check your email inbox (including spam folder)<br>
                2. Click the password reset link in the email<br>
                3. Create a new secure password<br>
                4. Try logging in again with your new password
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>
        
        <form method="POST">
          <div style="margin-bottom: 0.5rem; font-size: 0.9rem; color: rgba(51, 51, 51, 0.7); font-weight: 300; text-align: left;">Log in to access your account</div>
          <input type="text" placeholder="Email or Username" name="login_identifier" value="<?php echo isset($_POST['login_identifier']) ? htmlspecialchars($_POST['login_identifier']) : ''; ?>" required>
          
          <!-- Password field with visibility toggle -->
          <div style="position: relative; margin-bottom: 1rem;">
            <input type="password" placeholder="Password" name="password" id="password" required style="padding-right: 35px;">
            <i class="fas fa-eye" id="togglePassword" style="position: absolute; right: 18px; top: 35%; transform: translateY(-50%); cursor: pointer; color: #999; font-size: 12px; z-index: 10; transition: color 0.2s ease;"></i>
          </div>
          
          <button type="submit" class="btn-next">LOG IN</button>
        </form>

        <!-- Only show Sign Up link -->
        <p class="footer-text">
          <span style="font-size:0.95rem; font-weight:500;">New to ecocyle? <a href="signup.php" style="font-size:0.95rem; font-weight:600;">Sign Up</a></span>
          <div class="footer-text" style="margin-top:0.5rem; font-size:0.95rem;">
            <span>Having trouble logging in? <a href="contact-us.php" style="color:#1a5f7a; font-weight:600;">Contact Us</a></span>
          </div>
          <div class="footer-text" style="margin-top:0.2rem; font-size:0.95rem;">
            <a href="forgot-password.php" style="color:#28bf4b; font-weight:600;">Forgot Password?</a>
          </div>
        </p>
      </div>
    </div>
  </main>

  <script>
    document.addEventListener("DOMContentLoaded", function() {
      const passwordInput = document.getElementById("password");
      const togglePassword = document.getElementById("togglePassword");

      togglePassword.addEventListener("click", function(e) {
        e.preventDefault();
        const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
        passwordInput.setAttribute("type", type);
        this.classList.toggle("fa-eye-slash");
      });
    });
  </script>
</body>
</html>
