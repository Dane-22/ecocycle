<?php
session_start();
require_once 'config/database.php';
require_once 'config/phpmailer_config.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $user_type = $_POST['user_type'];
    
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } else {
        // Check if email exists in the selected user type table
        if ($user_type == 'buyer') {
            $stmt = $pdo->prepare("SELECT * FROM Buyers WHERE email = ?");
        } else {
            $stmt = $pdo->prepare("SELECT * FROM Sellers WHERE email = ?");
        }
        
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store token in database
            if ($user_type == 'buyer') {
                $update_stmt = $pdo->prepare("UPDATE Buyers SET reset_token = ?, reset_token_expires = ?, reset_required = 1 WHERE buyer_id = ?");
            } else {
                $update_stmt = $pdo->prepare("UPDATE Sellers SET reset_token = ?, reset_token_expires = ?, reset_required = 1 WHERE seller_id = ?");
            }
            
            $update_stmt->execute([$token, $expires, $user[$user_type . '_id']]);
            
            // Send reset email using PHPMailer
            $email_sent = sendPasswordResetEmail($user['email'], $user['fullname'], $token, $user_type);
            
            if ($email_sent) {
                $message = 'Password reset link has been sent to your email. Please check your inbox (and spam folder).';
            } else {
                $error = 'Failed to send email. Please try again or contact support.';
            }
        } else {
            $error = 'Email not found. Please check the email address or account type.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Ecocycle NLUC</title>
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
        
        input, select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
        }
        
        input:focus, select:focus {
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
        
        .instructions {
            background-color: #f8f9fa;
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <h2>Reset Your Password</h2>
        
        <div class="instructions">
            <p><strong>Note:</strong> Enter the email address associated with your account and select your account type.</p>
        </div>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="user_type">Account Type</label>
                <select id="user_type" name="user_type" required>
                    <option value="">Select Account Type</option>
                    <option value="buyer">Buyer Account</option>
                    <option value="seller">Seller Account</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="Enter your registered email" required>
            </div>
            
            <button type="submit">Send Reset Link</button>
        </form>
        
        <div class="back-link">
            <a href="login.php">← Back to Login</a>
        </div>
    </div>
</body>
</html>