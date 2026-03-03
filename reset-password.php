<?php
session_start();
require_once 'config/database.php';

// PHPMailer (Gmail SMTP will be used for sending reset links)
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';
$error = '';
$show_form = true;

// Function to clear login attempts for a user
function clearLoginAttempts($login_identifier) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE login_identifier = ?");
        $stmt->execute([$login_identifier]);
    } catch (PDOException $e) {
        error_log("Failed to clear login attempts: " . $e->getMessage());
    }
}

// Helper: send reset link using Gmail SMTP (requires app password and Gmail account)
function send_reset_via_gmail($to_email, $fullname, $token, $user_type) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'honeyboyb.corial@gmail.com'; // <-- REPLACE with your Gmail
        $mail->Password = 'keew djpl zgpw clpv';   // <-- REPLACE with your Google App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('no-reply@ecocycle.com', 'Ecocycle');
        $mail->addAddress($to_email, $fullname);
        $mail->isHTML(true);

        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
        $resetLink = $baseUrl . '/reset-password.php?token=' . urlencode($token) . '&type=' . urlencode($user_type);

        $mail->Subject = 'Ecocycle password reset request';
        $mail->Body = "<p>Hi " . htmlspecialchars($fullname) . ",</p>\n" .
                      "<p>We detected multiple failed login attempts for your account. Click the link below to reset your password. This link will expire in 1 hour:</p>\n" .
                      "<p><a href=\"" . $resetLink . "\">Reset your password</a></p>\n" .
                      "<p>If you did not request this, you can ignore this message.</p>\n" .
                      "<p>— Ecocycle Team</p>";
        $mail->AltBody = "Reset your Ecocycle password: " . $resetLink;

        $mail->send();
        error_log("[Mail] Gmail reset sent to {$to_email}");
        return true;
    } catch (Exception $e) {
        error_log('[Mail] Gmail send failed: ' . $e->getMessage());
        return false;
    }
}

// If triggered from login (after brute-force) — send reset link via Gmail SMTP
if (isset($_GET['trigger']) && $_GET['trigger'] == '1' && !empty($_GET['email'])) {
    $emailTo = trim($_GET['email']);
    // Try buyers first
    $stmt = $pdo->prepare("SELECT buyer_id AS id, fullname FROM Buyers WHERE email = ? LIMIT 1");
    $stmt->execute([$emailTo]);
    $u = $stmt->fetch();
    $type = '';
    $idCol = '';
    if ($u) {
        $type = 'buyer';
        $id = $u['id'];
        $fullname = $u['fullname'];
        $idCol = 'buyer_id';
    } else {
        $stmt = $pdo->prepare("SELECT seller_id AS id, fullname FROM Sellers WHERE email = ? LIMIT 1");
        $stmt->execute([$emailTo]);
        $u = $stmt->fetch();
        if ($u) {
            $type = 'seller';
            $id = $u['id'];
            $fullname = $u['fullname'];
            $idCol = 'seller_id';
        }
    }

    if (!$type) {
        $error = 'No account found for that email.';
        $show_form = false;
    } else {
        // Ensure token exists
        $stmt = $pdo->prepare("SELECT reset_token, reset_token_expires FROM " . ($type === 'buyer' ? 'Buyers' : 'Sellers') . " WHERE {$idCol} = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        $token = $row && !empty($row['reset_token']) && strtotime($row['reset_token_expires']) > time() ? $row['reset_token'] : null;
        if (!$token) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $update = $pdo->prepare("UPDATE " . ($type === 'buyer' ? 'Buyers' : 'Sellers') . " SET reset_token = ?, reset_token_expires = ?, reset_required = 1 WHERE {$idCol} = ?");
            $update->execute([$token, $expires, $id]);
        }

        $sent = send_reset_via_gmail($emailTo, $fullname, $token, $type);
        if ($sent) {
            $message = 'A password reset link has been sent to your email address.';
        } else {
            $error = 'Unable to send reset email. Please contact the administrator or try again later.';
        }
        $show_form = false;
    }
} elseif (isset($_GET['email']) && isset($_GET['type']) && !isset($_GET['token'])) {
    $email = trim($_GET['email']);
    $user_type = $_GET['type'];
    
    if (!in_array($user_type, ['buyer', 'seller'])) {
        $error = 'Invalid user type.';
        $show_form = false;
    } else {
        // Find user by email
        if ($user_type == 'buyer') {
            $stmt = $pdo->prepare("SELECT * FROM Buyers WHERE email = ? LIMIT 1");
        } else {
            $stmt = $pdo->prepare("SELECT * FROM Sellers WHERE email = ? LIMIT 1");
        }
        
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $error = 'No account found for that email address.';
            $show_form = false;
        } else {
            // Create or update reset token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            if ($user_type == 'buyer') {
                $update = $pdo->prepare("UPDATE Buyers SET reset_token = ?, reset_token_expires = ?, reset_required = 1 WHERE buyer_id = ?");
                $update->execute([$token, $expires, $user['buyer_id']]);
            } else {
                $update = $pdo->prepare("UPDATE Sellers SET reset_token = ?, reset_token_expires = ?, reset_required = 1 WHERE seller_id = ?");
                $update->execute([$token, $expires, $user['seller_id']]);
            }
            
            // Send reset email
            $sent = send_reset_via_gmail($email, $user['fullname'], $token, $user_type);
            if ($sent) {
                $message = 'A password reset link has been sent to your email address. Please check your inbox (including spam folder).';
            } else {
                $error = 'Unable to send reset email. Please contact the administrator or try again later.';
            }
            $show_form = false;
        }
    }
} else {
    // Check if token is valid
    $token = $_GET['token'] ?? '';
    $user_type = $_GET['type'] ?? '';

    // DEBUG: Log what we received
    error_log("RESET-PHP DEBUG: Received token: " . $token);
    error_log("RESET-PHP DEBUG: Received type: " . $user_type);
    error_log("RESET-PHP DEBUG: Token length: " . strlen($token));

    if (!$token || !in_array($user_type, ['buyer', 'seller'])) {
        $error = 'Invalid reset link.';
        $show_form = false;
        error_log("RESET-PHP DEBUG: Invalid token or type");
    } else {
    // Check token validity - FIXED: Use PHP time instead of MySQL NOW()
    if ($user_type == 'buyer') {
        $stmt = $pdo->prepare("SELECT * FROM Buyers WHERE reset_token = ?");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM Sellers WHERE reset_token = ?");
    }

    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    // Check expiration using PHP time instead of MySQL NOW()
    if (!$user) {
        $error = 'Invalid reset link. Please request a new reset link.';
        $show_form = false;
    } else {
        $expires_time = strtotime($user['reset_token_expires']);
        $current_time = time();
        
        if ($expires_time <= $current_time) {
            $error = 'Reset link has expired. Please request a new reset link.';
            $show_form = false;
        }
    }

    // Handle password reset
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && $show_form) {
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($new_password) || empty($confirm_password)) {
            $error = 'Please fill in all fields.';
        } elseif ($new_password != $confirm_password) {
            $error = 'Passwords do not match.';
        } elseif (strlen($new_password) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } else {
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password and clear reset flags
            if ($user_type == 'buyer') {
                $update_stmt = $pdo->prepare("UPDATE Buyers SET password = ?, reset_token = NULL, 
                                             reset_token_expires = NULL, reset_required = 0 
                                             WHERE buyer_id = ?");
            } else {
                $update_stmt = $pdo->prepare("UPDATE Sellers SET password = ?, reset_token = NULL, 
                                             reset_token_expires = NULL, reset_required = 0 
                                             WHERE seller_id = ?");
            }
            
            $update_stmt->execute([$hashed_password, $user[$user_type . '_id']]);
            
            // Clear login attempts for this user (by email and username)
            clearLoginAttempts($user['email']);
            clearLoginAttempts($user['username']);
            
            $message = 'Password has been reset successfully! You can now <a href="login.php?reset=success">login</a> with your new password.';
            $show_form = false;
        }
    }
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Ecocycle NLUC</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <!-- Font Awesome CDN for eye icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1a5f7a 0%, #2c786c 50%, #28bf4b 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        
        .reset-container {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        
        h2 {
            color: #1a5f7a;
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.2rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }
        
        input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
        }
        
        input[type="password"] {
            padding-right: 35px !important;
            padding-top: 0.6rem;
            padding-bottom: 0.6rem;
            font-size: 0.97rem;
            width: calc(100% - 50px);
            display: inline-block;
        }
        
        input:focus {
            border-color: #28bf4b;
            outline: none;
            box-shadow: 0 0 0 2px rgba(40,191,75,0.2);
        }
        
        button {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, #1a5f7a 0%, #28bf4b 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 1rem;
            font-family: 'Poppins', sans-serif;
        }
        
        button:hover {
            background: linear-gradient(135deg, #28bf4b 0%, #1a5f7a 100%);
        }
        
        .message {
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .back-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }
        
        .back-link a {
            color: #28bf4b;
            text-decoration: none;
            font-weight: 600;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        .password-requirements {
            background-color: #f8f9fa;
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 0.85rem;
            color: #666;
        }
        
        .password-requirements ul {
            margin: 0;
            padding-left: 1.2rem;
        }
        
        .password-requirements li {
            margin-bottom: 0.3rem;
        }

        .password-wrapper {
            position: relative;
            width: 100%;
            max-width: 100%;
            display: flex;
            align-items: center;
        }

        .password-wrapper input[type="password"],
        .password-wrapper input[type="text"] {
            width: calc(100% - 50px);
            min-width: 0;
            flex: none;
            display: inline-block;
            transition: none;
        }

        .toggle-password {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
            font-size: 14px;
            z-index: 10;
            transition: color 0.2s ease;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <h2>Create New Password</h2>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($show_form): ?>
        <div class="password-requirements">
            <p><strong>Password Requirements:</strong></p>
            <ul>
                <li>At least 8 characters long</li>
                <li>Use a combination of letters, numbers, and symbols</li>
                <li>Avoid using personal information</li>
                <li>Don't reuse old passwords</li>
            </ul>
        </div>
        
        <form method="POST">
            <div class="form-group password-toggle-group">
                <label for="new_password">New Password</label>
                <div class="password-wrapper">
                    <input type="password" id="new_password" name="new_password" 
                        placeholder="Enter new password" required minlength="8" style="padding-right: 35px;">
                    <i class="fas fa-eye toggle-password" id="toggleNewPassword" style="position: absolute; right: 18px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #999; font-size: 14px; z-index: 10; transition: color 0.2s ease;"></i>
                </div>
            </div>
            <div class="form-group password-toggle-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="password-wrapper">
                    <input type="password" id="confirm_password" name="confirm_password" 
                        placeholder="Confirm new password" required minlength="8" style="padding-right: 35px;">
                    <i class="fas fa-eye toggle-password" id="toggleConfirmPassword" style="position: absolute; right: 18px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #999; font-size: 14px; z-index: 10; transition: color 0.2s ease;"></i>
                </div>
            </div>
            <button type="submit">Reset Password</button>
        </form>
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            const newPasswordInput = document.getElementById("new_password");
            const toggleNewPassword = document.getElementById("toggleNewPassword");
            const confirmPasswordInput = document.getElementById("confirm_password");
            const toggleConfirmPassword = document.getElementById("toggleConfirmPassword");

            toggleNewPassword.addEventListener("click", function(e) {
                e.preventDefault();
                const type = newPasswordInput.getAttribute("type") === "password" ? "text" : "password";
                newPasswordInput.setAttribute("type", type);
                this.classList.toggle("fa-eye-slash");
            });
            toggleConfirmPassword.addEventListener("click", function(e) {
                e.preventDefault();
                const type = confirmPasswordInput.getAttribute("type") === "password" ? "text" : "password";
                confirmPasswordInput.setAttribute("type", type);
                this.classList.toggle("fa-eye-slash");
            });
        });
        </script>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="login.php">← Back to Login</a>
        </div>
    </div>
</body>
</html>