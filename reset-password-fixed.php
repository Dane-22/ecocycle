<?php
session_start();
require_once 'config/database.php';

$message = '';
$error = '';
$show_form = true;

// Check if token is valid - FIXED VERSION
$token = $_GET['token'] ?? '';
$user_type = $_GET['type'] ?? '';

if (!$token || !in_array($user_type, ['buyer', 'seller'])) {
    $error = 'Invalid reset link.';
    $show_form = false;
} else {
    // FIXED: Use PHP time comparison instead of MySQL NOW()
    if ($user_type == 'buyer') {
        $stmt = $pdo->prepare("SELECT * FROM Buyers WHERE reset_token = ?");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM Sellers WHERE reset_token = ?");
    }
    
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $error = 'Invalid reset link. Please request a new reset link.';
        $show_form = false;
    } else {
        // Check expiration using PHP time instead of MySQL NOW()
        $expires_time = strtotime($user['reset_token_expires']);
        $current_time = time();
        
        if ($expires_time <= $current_time) {
            $error = 'Reset link has expired. Please request a new reset link.';
            $error .= '<br><small>Link expired at: ' . date('Y-m-d H:i:s', $expires_time) . '</small>';
            $error .= '<br><small>Current time: ' . date('Y-m-d H:i:s', $current_time) . '</small>';
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
            
            $message = 'Password has been reset successfully! You can now <a href="login.php">login</a> with your new password.';
            $show_form = false;
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
        <form method="POST">
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" 
                       placeholder="Enter new password" required minlength="8">
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" 
                       placeholder="Confirm new password" required minlength="8">
            </div>
            
            <button type="submit">Reset Password</button>
        </form>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="login.php">← Back to Login</a>
        </div>
    </div>
</body>
</html>
