<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load environment variables
require_once __DIR__ . '/env.php';

// Load Composer's autoloader if available, otherwise include PHPMailer source files directly
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    // Fall back to bundled PHPMailer in project
    require_once __DIR__ . '/../PHPMailer/src/Exception.php';
    require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
    error_log('[PHPMailer] Composer autoload not found; using bundled PHPMailer sources.');
}

// Get SMTP configuration from environment variables
$smtpHost = env('SMTP_HOST', 'smtp.gmail.com');
$smtpPort = env('SMTP_PORT', 587);
$smtpEmail = env('SMTP_EMAIL', '');
$smtpPassword = env('SMTP_PASSWORD', '');
$fromEmail = env('SMTP_FROM_EMAIL', 'no-reply@ecocycle-nluc.com');
$fromName = env('SMTP_FROM_NAME', 'EcoCycle NLUC');

if (empty($smtpEmail) || empty($smtpPassword)) {
    error_log('[PHPMailer] ERROR: SMTP_EMAIL and SMTP_PASSWORD must be set in .env file');
}

function sendPasswordResetEmail($toEmail, $toName, $resetToken, $userType) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        global $smtpHost, $smtpPort, $smtpEmail, $smtpPassword, $fromEmail, $fromName;
        
        $mail->isSMTP();
        $mail->Host       = $smtpHost;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpEmail;
        $mail->Password   = $smtpPassword;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $smtpPort;
        
        // Recipients
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($toEmail, $toName);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Required - Ecocycle NLUC';
        
        $resetLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
                    "://$_SERVER[HTTP_HOST]/Ecocycle/reset-password.php?token=$resetToken&type=$userType";
        
        $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { text-align: center; margin-bottom: 30px; }
                    .alert { background-color: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0; }
                    .btn { display: inline-block; background-color: #28bf4b; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
                    .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #666; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2 style='color: #1a5f7a;'>Ecocycle NLUC</h2>
                        <p style='color: #666; font-style: italic;'>Nurturing a lasting unified conservation</p>
                    </div>
                    
                    <div class='alert'>
                        <h3 style='margin-top: 0; color: #856404;'>🔒 Security Alert</h3>
                        <p>Multiple failed login attempts detected on your account. For security reasons, your account has been temporarily locked and requires a password reset.</p>
                    </div>
                    
                    <p>Hello <strong>$toName</strong>,</p>
                    
                    <p>We detected 5 failed login attempts on your Ecocycle NLUC account. To protect your account from unauthorized access, we have temporarily locked it.</p>
                    
                    <p><strong>Please click the button below to reset your password:</strong></p>
                    
                    <div style='text-align: center;'>
                        <a href='$resetLink' class='btn'>Reset Your Password</a>
                    </div>
                    
                    <p>Or copy and paste this link into your browser:<br>
                    <a href='$resetLink' style='color: #1a5f7a; word-break: break-all;'>$resetLink</a></p>
                    
                    <p><strong>⚠️ This link will expire in 1 hour.</strong></p>
                    
                    <p>If you didn't attempt to log in, please contact our support team immediately at <a href='mailto:support@ecocycle-nluc.com'>support@ecocycle-nluc.com</a>.</p>
                    
                    <div class='footer'>
                        <p>This is an automated message from Ecocycle NLUC Security System.</p>
                        <p>Please do not reply to this email.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        $mail->AltBody = "Password Reset Required\n\nHello $toName,\n\nWe detected 5 failed login attempts on your Ecocycle NLUC account. To protect your account from unauthorized access, we have temporarily locked it.\n\nPlease use this link to reset your password: $resetLink\n\nThis link will expire in 1 hour.\n\nIf you didn't attempt to log in, please contact our support team immediately.\n\nThis is an automated message from Ecocycle NLUC Security System.";
        
        return $mail->send();
    } catch (Exception $e) {
        error_log("PHPMailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

function sendLoginAlertEmail($toEmail, $toName, $failedAttempts, $ipAddress, $userType) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        global $smtpHost, $smtpPort, $smtpEmail, $smtpPassword;
        
        $mail->isSMTP();
        $mail->Host       = $smtpHost;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpEmail;
        $mail->Password   = $smtpPassword;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $smtpPort;
        
        // Recipients
        $mail->setFrom('security@ecocycle-nluc.com', 'Ecocycle NLUC Security');
        $mail->addAddress($toEmail, $toName);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Suspicious Login Activity - Ecocycle NLUC';
        
        $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .warning { background-color: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0; }
                    .info-box { background-color: #f8f9fa; border: 1px solid #e9ecef; padding: 15px; border-radius: 5px; margin: 20px 0; }
                    .btn { display: inline-block; background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <h3 style='color: #dc3545;'>⚠️ Suspicious Login Activity Detected</h3>
                    
                    <p>Hello <strong>$toName</strong>,</p>
                    
                    <div class='warning'>
                        <p>We detected failed login attempts on your Ecocycle NLUC account.</p>
                    </div>
                    
                    <div class='info-box'>
                        <p><strong>Login Attempt Details:</strong></p>
                        <ul style='margin: 10px 0; padding-left: 20px;'>
                            <li><strong>Failed Attempts:</strong> $failedAttempts/5</li>
                            <li><strong>IP Address:</strong> $ipAddress</li>
                            <li><strong>Account Type:</strong> " . ucfirst($userType) . "</li>
                            <li><strong>Time:</strong> " . date('F j, Y, g:i a') . "</li>
                        </ul>
                    </div>
                    
                    <p><strong>If this was you:</strong></p>
                    <ul>
                        <li>Make sure you're entering the correct credentials</li>
                        <li>Check if CAPS LOCK is turned on</li>
                        <li>Try using the 'Forgot Password' feature</li>
                    </ul>
                    
                    <p><strong>If this wasn't you:</strong></p>
                    <ul>
                        <li>Your account might be compromised</li>
                        <li>Consider resetting your password immediately</li>
                        <li>Contact our support team if you notice any suspicious activity</li>
                    </ul>
                    
                    <p style='color: #dc3545; font-weight: bold;'>⚠️ Your account will be locked after 5 failed attempts.</p>
                    
                    <div style='text-align: center; margin: 25px 0;'>
                        <a href='https://ecocycle-nluc.com/forgot-password.php' class='btn'>Reset Password Now</a>
                    </div>
                    
                    <div style='font-size: 12px; color: #666; border-top: 1px solid #eee; padding-top: 15px; margin-top: 20px;'>
                        <p><strong>Security Tips:</strong></p>
                        <ul>
                            <li>Use a strong, unique password</li>
                            <li>Never share your credentials with anyone</li>
                            <li>Regularly update your password</li>
                            <li>Enable two-factor authentication if available</li>
                        </ul>
                        <p>This is an automated security alert from Ecocycle NLUC.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        return $mail->send();
    } catch (Exception $e) {
        error_log("PHPMailer Error for login alert: {$mail->ErrorInfo}");
        return false;
    }
}

function sendPasswordResetNotification($toEmail, $toName, $newPassword, $userType) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        global $smtpHost, $smtpPort, $smtpEmail, $smtpPassword, $fromEmail, $fromName;
        
        $mail->isSMTP();
        $mail->Host       = $smtpHost;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpEmail;
        $mail->Password   = $smtpPassword;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $smtpPort;
        
        // Recipients
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($toEmail, $toName);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Password Has Been Reset - Ecocycle NLUC';
        
        $userTypeLabel = ucfirst($userType);
        
        $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9; }
                    .header { text-align: center; margin-bottom: 30px; }
                    .alert { background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0; }
                    .info-box { background-color: #e7f3ff; border-left: 4px solid #2196F3; padding: 15px; margin: 20px 0; border-radius: 4px; }
                    .password-box { background-color: #f5f5f5; border: 1px solid #ddd; padding: 15px; margin: 20px 0; border-radius: 5px; font-family: monospace; }
                    .password-value { font-size: 18px; font-weight: bold; color: #d32f2f; letter-spacing: 1px; }
                    .btn { display: inline-block; background-color: #28bf4b; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
                    .footer { font-size: 12px; color: #666; border-top: 1px solid #eee; padding-top: 15px; margin-top: 20px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2 style='color: #28bf4b;'>🔐 Password Reset Notification</h2>
                    </div>
                    
                    <p>Hello <strong>$toName</strong>,</p>
                    
                    <p>Your $userTypeLabel account password has been successfully reset by an administrator.</p>
                    
                    <div class='alert'>
                        <strong>✓ Your password has been reset</strong>
                    </div>
                    
                    <div class='info-box'>
                        <strong>Your New Password:</strong>
                        <div class='password-box'>
                            <div class='password-value'>$newPassword</div>
                        </div>
                        <p style='margin: 10px 0; color: #f57c00;'><strong>⚠️ Important:</strong> Keep this password safe and secure. Do not share it with anyone.</p>
                    </div>
                    
                    <p><strong>Next Steps:</strong></p>
                    <ul>
                        <li>Log in to your account using the password above</li>
                        <li>We recommend changing your password to something you can remember</li>
                        <li>Make sure your password is strong and unique</li>
                    </ul>
                    
                    <div style='text-align: center; margin: 25px 0;'>
                        <a href='https://localhost/Ecocycle/login.php' class='btn'>Login to Your Account</a>
                    </div>
                    
                    <div class='info-box'>
                        <strong>Security reminder:</strong>
                        <ul>
                            <li>Never share your password via email</li>
                            <li>Use a strong password with mix of letters, numbers, and symbols</li>
                            <li>Change your password regularly for security</li>
                            <li>Be cautious of phishing attempts</li>
                        </ul>
                    </div>
                    
                    <div class='footer'>
                        <p><strong>If you did not request this password reset:</strong></p>
                        <p>If you didn't authorize this password reset, please contact our support team immediately at support@ecocycle-nluc.com</p>
                        <p style='margin-top: 20px; color: #999;'>This is an automated message from Ecocycle NLUC. Please do not reply to this email.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        $mail->AltBody = "Your $userTypeLabel account password has been reset. New password: $newPassword";
        
        return $mail->send();
    } catch (Exception $e) {
        error_log("PHPMailer Error for password reset notification: {$mail->ErrorInfo}");
        return false;
    }
}

function sendRedemptionConfirmationEmail($toEmail, $toName, $productName, $orderId, $ecocoinsSpent) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        global $smtpHost, $smtpPort, $smtpEmail, $smtpPassword, $fromEmail, $fromName;
        
        $mail->isSMTP();
        $mail->Host       = $smtpHost;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpEmail;
        $mail->Password   = $smtpPassword;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $smtpPort;
        
        // Recipients
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($toEmail, $toName);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Product Redemption Confirmation - Ecocycle NLUC';
        
        $currentDate = date('F j, Y, g:i a');
        $pickupTime = date('F j, Y', strtotime('+1 day'));
        
        $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9; }
                    .header { text-align: center; margin-bottom: 30px; background: linear-gradient(135deg, #28bf4b 0%, #20a038 100%); color: white; padding: 20px; border-radius: 8px; }
                    .header h2 { margin: 0; font-size: 24px; }
                    .success-box { background-color: #d4edda; border: 2px solid #28a745; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0; }
                    .info-box { background-color: #e7f3ff; border-left: 4px solid #2196F3; padding: 15px; margin: 20px 0; border-radius: 4px; }
                    .details-table { width: 100%; border-collapse: collapse; margin: 20px 0; background-color: white; }
                    .details-table th { background-color: #f5f5f5; text-align: left; padding: 12px; border: 1px solid #ddd; font-weight: bold; }
                    .details-table td { padding: 12px; border: 1px solid #ddd; }
                    .details-table tr:nth-child(even) { background-color: #fafafa; }
                    .order-id { font-size: 18px; font-weight: bold; color: #28bf4b; word-break: break-all; }
                    .btn { display: inline-block; background-color: #28bf4b; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
                    .footer { font-size: 12px; color: #666; border-top: 1px solid #eee; padding-top: 15px; margin-top: 20px; }
                    .pickup-location { background-color: #fff9e6; border: 1px solid #ffe0b2; padding: 15px; border-radius: 5px; margin: 20px 0; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>✓ Redemption Successful!</h2>
                        <p style='margin: 10px 0; font-size: 14px;'>Thank you for redeeming with Ecocycle</p>
                    </div>
                    
                    <p>Hello <strong>$toName</strong>,</p>
                    
                    <div class='success-box'>
                        <strong>✓ Your product has been successfully redeemed!</strong>
                        <p style='margin: 10px 0;'>Please wait for Bard admin approval. You will receive another confirmation email once your redemption is approved.</p>
                    </div>
                    
                    <h3 style='color: #28bf4b; margin-top: 25px;'>📦 Redemption Details</h3>
                    <table class='details-table'>
                        <tr>
                            <th>Order ID</th>
                            <td><span class='order-id'>ECO-REDEEM-$orderId</span></td>
                        </tr>
                        <tr>
                            <th>Product Name</th>
                            <td><strong>$productName</strong></td>
                        </tr>
                        <tr>
                            <th>EcoCoins Spent</th>
                            <td><strong>$ecocoinsSpent</strong> EcoCoins</td>
                        </tr>
                        <tr>
                            <th>Redemption Date</th>
                            <td>$currentDate</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td><span style='background-color: #fff3cd; color: #856404; padding: 5px 10px; border-radius: 3px; font-weight: bold;'>Pending Approval</span></td>
                        </tr>
                    </table>
                    
                    <div class='pickup-location'>
                        <h4 style='margin-top: 0; color: #f57c00;'>📍 Pickup Location & Time</h4>
                        <p><strong>Location:</strong> Ecocycle Collection Center</p>
                        <p><strong>Expected Pickup:</strong> Within 24 hours (by $pickupTime)</p>
                        <p style='margin-bottom: 0; color: #666; font-size: 12px;'><em>You will receive a notification once your item is ready for pickup.</em></p>
                    </div>
                    
                    <div class='info-box'>
                        <h4 style='margin-top: 0;'>📝 What's Next?</h4>
                        <ol>
                            <li>Wait for Bard admin to verify your redemption</li>
                            <li>You will receive a notification email when approved</li>
                            <li>Visit the collection center to pick up your item</li>
                            <li>Please bring a valid ID for verification</li>
                        </ol>
                    </div>
                    
                    <div class='info-box'>
                        <h4 style='margin-top: 0;'>❓ Need Help?</h4>
                        <p>If you have any questions about your redemption or need to track your order, please contact our support team at <a href='mailto:support@ecocycle-nluc.com'>support@ecocycle-nluc.com</a></p>
                    </div>
                    
                    <div style='text-align: center; margin: 25px 0;'>
                        <a href='https://localhost/Ecocycle/redeemed-products.php' class='btn'>View My Redemptions</a>
                    </div>
                    
                    <div class='footer'>
                        <p><strong>Important Reminders:</strong></p>
                        <ul style='margin: 10px 0; padding-left: 20px;'>
                            <li>This order ID is your reference number for pickup</li>
                            <li>Keep this email for your records</li>
                            <li>Items must be picked up within 7 days of approval</li>
                            <li>Contact us if you need to reschedule your pickup</li>
                        </ul>
                        <p style='margin-top: 20px; color: #999;'>This is an automated message from Ecocycle NLUC. Please do not reply to this email.</p>
                        <p style='color: #999;'>© " . date('Y') . " Ecocycle NLUC. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        $mail->AltBody = "Redemption Confirmation\n\nHello $toName,\n\nYour product has been successfully redeemed!\n\nOrder ID: ECO-REDEEM-$orderId\nProduct: $productName\nEcoCoins Spent: $ecocoinsSpent\nRedemption Date: $currentDate\n\nYour item will be ready for pickup at the Ecocycle Collection Center within 24 hours.\n\nThank you for using Ecocycle!";
        
        return $mail->send();
    } catch (Exception $e) {
        error_log("PHPMailer Error for redemption confirmation: {$mail->ErrorInfo}");
        return false;
    }
}

function sendRedemptionApprovedEmail($toEmail, $toName, $productName, $orderId, $ecocoinsSpent) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        global $smtpHost, $smtpPort, $smtpEmail, $smtpPassword, $fromEmail, $fromName;
        
        $mail->isSMTP();
        $mail->Host       = $smtpHost;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpEmail;
        $mail->Password   = $smtpPassword;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $smtpPort;
        
        // Recipients
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($toEmail, $toName);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Redemption Has Been Approved! - Ecocycle NLUC';
        
        $currentDate = date('F j, Y, g:i a');
        
        $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9; }
                    .header { text-align: center; margin-bottom: 30px; background: linear-gradient(135deg, #28bf4b 0%, #20a038 100%); color: white; padding: 20px; border-radius: 8px; }
                    .header h2 { margin: 0; font-size: 24px; }
                    .success-box { background-color: #d4edda; border: 2px solid #28a745; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0; }
                    .info-box { background-color: #e7f3ff; border-left: 4px solid #2196F3; padding: 15px; margin: 20px 0; border-radius: 4px; }
                    .details-table { width: 100%; border-collapse: collapse; margin: 20px 0; background-color: white; }
                    .details-table th { background-color: #f5f5f5; text-align: left; padding: 12px; border: 1px solid #ddd; font-weight: bold; }
                    .details-table td { padding: 12px; border: 1px solid #ddd; }
                    .details-table tr:nth-child(even) { background-color: #fafafa; }
                    .order-id { font-size: 18px; font-weight: bold; color: #28bf4b; word-break: break-all; }
                    .btn { display: inline-block; background-color: #28bf4b; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
                    .footer { font-size: 12px; color: #666; border-top: 1px solid #eee; padding-top: 15px; margin-top: 20px; }
                    .pickup-location { background-color: #fff9e6; border: 1px solid #ffe0b2; padding: 15px; border-radius: 5px; margin: 20px 0; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>✓ Redemption Approved!</h2>
                        <p style='margin: 10px 0; font-size: 14px;'>Your product is ready for pickup!</p>
                    </div>
                    
                    <p>Hello <strong>$toName</strong>,</p>
                    
                    <div class='success-box'>
                        <strong>✓ Great News! Your redemption has been approved by our Bard Administrator.</strong>
                        <p style='margin: 10px 0;'>Your product is now ready for pickup. Please visit the collection center at your earliest convenience.</p>
                    </div>
                    
                    <h3 style='color: #28bf4b; margin-top: 25px;'>📦 Redemption Details</h3>
                    <table class='details-table'>
                        <tr>
                            <th>Order ID</th>
                            <td><span class='order-id'>ECO-REDEEM-$orderId</span></td>
                        </tr>
                        <tr>
                            <th>Product Name</th>
                            <td><strong>$productName</strong></td>
                        </tr>
                        <tr>
                            <th>EcoCoins Spent</th>
                            <td><strong>$ecocoinsSpent</strong> EcoCoins</td>
                        </tr>
                        <tr>
                            <th>Approval Date</th>
                            <td>$currentDate</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td><span style='background-color: #d4edda; color: #155724; padding: 5px 10px; border-radius: 3px; font-weight: bold;'>✓ Approved</span></td>
                        </tr>
                    </table>
                    
                    <div class='pickup-location'>
                        <h4 style='margin-top: 0; color: #f57c00;'>📍 Pickup Instructions</h4>
                        <p><strong>Location:</strong> Ecocycle Collection Center</p>
                        <p><strong>Hours:</strong> Monday - Friday, 9:00 AM - 5:00 PM</p>
                        <p><strong>Important:</strong> Please bring a valid ID for verification</p>
                        <p style='margin-bottom: 0; color: #666; font-size: 12px;'><em>Items must be picked up within 7 days of this approval.</em></p>
                    </div>
                    
                    <div class='info-box'>
                        <h4 style='margin-top: 0;'>✅ Next Steps</h4>
                        <ol>
                            <li>Visit the Ecocycle Collection Center during operating hours</li>
                            <li>Bring your valid ID and this email for verification</li>
                            <li>Present your Order ID: ECO-REDEEM-$orderId</li>
                            <li>Collect your product and enjoy!</li>
                        </ol>
                    </div>
                    
                    <div class='info-box'>
                        <h4 style='margin-top: 0;'>❓ Need Help?</h4>
                        <p>If you have any questions about your pickup or need to reschedule, please contact our support team at <a href='mailto:support@ecocycle-nluc.com'>support@ecocycle-nluc.com</a></p>
                    </div>
                    
                    <div style='text-align: center; margin: 25px 0;'>
                        <a href='https://localhost/Ecocycle/redeemed-products.php' class='btn'>View My Redemptions</a>
                    </div>
                    
                    <div class='footer'>
                        <p><strong>Important Reminders:</strong></p>
                        <ul style='margin: 10px 0; padding-left: 20px;'>
                            <li>Keep this email for your records</li>
                            <li>Show this email or your Order ID at pickup</li>
                            <li>Items are reserved for 7 days from approval date</li>
                            <li>Contact us to reschedule if needed</li>
                        </ul>
                        <p style='margin-top: 20px; color: #999;'>This is an automated message from Ecocycle NLUC. Please do not reply to this email.</p>
                        <p style='color: #999;'>© " . date('Y') . " Ecocycle NLUC. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        $mail->AltBody = "Redemption Approved\n\nHello $toName,\n\nGreat news! Your redemption has been approved by our Bard Administrator.\n\nOrder ID: ECO-REDEEM-$orderId\nProduct: $productName\nEcoCoins Spent: $ecocoinsSpent\nApproval Date: $currentDate\n\nYour item is now ready for pickup at the Ecocycle Collection Center.\n\nPlease visit during business hours (Monday - Friday, 9:00 AM - 5:00 PM) with a valid ID.\n\nThank you for using Ecocycle!";
        
        return $mail->send();
    } catch (Exception $e) {
        error_log("PHPMailer Error for redemption approval: {$mail->ErrorInfo}");
        return false;
    }
}

function sendRedemptionRejectedEmail($toEmail, $toName, $productName, $orderId, $ecocoinsSpent) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        global $smtpHost, $smtpPort, $smtpEmail, $smtpPassword, $fromEmail, $fromName;
        
        $mail->isSMTP();
        $mail->Host       = $smtpHost;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpEmail;
        $mail->Password   = $smtpPassword;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $smtpPort;
        
        // Recipients
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($toEmail, $toName);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Redemption Status Update - Ecocycle NLUC';
        
        $currentDate = date('F j, Y, g:i a');
        
        $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9; }
                    .header { text-align: center; margin-bottom: 30px; background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 20px; border-radius: 8px; }
                    .header h2 { margin: 0; font-size: 24px; }
                    .alert-box { background-color: #f8d7da; border: 2px solid #dc3545; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0; }
                    .info-box { background-color: #e7f3ff; border-left: 4px solid #2196F3; padding: 15px; margin: 20px 0; border-radius: 4px; }
                    .details-table { width: 100%; border-collapse: collapse; margin: 20px 0; background-color: white; }
                    .details-table th { background-color: #f5f5f5; text-align: left; padding: 12px; border: 1px solid #ddd; font-weight: bold; }
                    .details-table td { padding: 12px; border: 1px solid #ddd; }
                    .details-table tr:nth-child(even) { background-color: #fafafa; }
                    .order-id { font-size: 18px; font-weight: bold; color: #dc3545; word-break: break-all; }
                    .btn { display: inline-block; background-color: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
                    .footer { font-size: 12px; color: #666; border-top: 1px solid #eee; padding-top: 15px; margin-top: 20px; }
                    .refund-info { background-color: #e8f5e9; border: 1px solid #81c784; padding: 15px; border-radius: 5px; margin: 20px 0; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Redemption Update</h2>
                        <p style='margin: 10px 0; font-size: 14px;'>Your redemption request has been reviewed</p>
                    </div>
                    
                    <p>Hello <strong>$toName</strong>,</p>
                    
                    <div class='alert-box'>
                        <strong>⚠ Your redemption has been rejected by our Bard Administrator.</strong>
                        <p style='margin: 10px 0;'>The EcoCoins you spent for this redemption have been refunded to your account.</p>
                    </div>
                    
                    <h3 style='color: #dc3545; margin-top: 25px;'>📦 Redemption Details</h3>
                    <table class='details-table'>
                        <tr>
                            <th>Order ID</th>
                            <td><span class='order-id'>ECO-REDEEM-$orderId</span></td>
                        </tr>
                        <tr>
                            <th>Product Name</th>
                            <td><strong>$productName</strong></td>
                        </tr>
                        <tr>
                            <th>EcoCoins (Refunded)</th>
                            <td><strong>$ecocoinsSpent</strong> EcoCoins</td>
                        </tr>
                        <tr>
                            <th>Rejection Date</th>
                            <td>$currentDate</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td><span style='background-color: #f8d7da; color: #721c24; padding: 5px 10px; border-radius: 3px; font-weight: bold;'>✕ Rejected</span></td>
                        </tr>
                    </table>
                    
                    <div class='refund-info'>
                        <h4 style='margin-top: 0; color: #2e7d32;'>💰 Refund Information</h4>
                        <p><strong>Refunded Amount:</strong> $ecocoinsSpent EcoCoins</p>
                        <p><strong>Status:</strong> The EcoCoins have been credited back to your account</p>
                        <p style='margin-bottom: 0; color: #666; font-size: 12px;'><em>Please allow up to 2 hours for the refund to appear in your account balance.</em></p>
                    </div>
                    
                    <div class='info-box'>
                        <h4 style='margin-top: 0;'>❓ What Now?</h4>
                        <ul>
                            <li>The EcoCoins spent on this redemption have been refunded</li>
                            <li>You can view your updated balance in your account dashboard</li>
                            <li>Feel free to browse other products and redeem them</li>
                            <li>If you believe this is an error, please contact our support team</li>
                        </ul>
                    </div>
                    
                    <div class='info-box'>
                        <h4 style='margin-top: 0;'>📞 Questions?</h4>
                        <p>If you have any questions or concerns about the rejection, please contact our support team at <a href='mailto:support@ecocycle-nluc.com'>support@ecocycle-nluc.com</a></p>
                        <p>We're here to help and would like to understand any issues you may have.</p>
                    </div>
                    
                    <div style='text-align: center; margin: 25px 0;'>
                        <a href='https://localhost/Ecocycle/redeemed-products.php' class='btn'>View My Redemptions</a>
                    </div>
                    
                    <div class='footer'>
                        <p><strong>Helpful Tips:</strong></p>
                        <ul style='margin: 10px 0; padding-left: 20px;'>
                            <li>Check your account balance to see the refunded EcoCoins</li>
                            <li>Browse our available products for your next redemption</li>
                            <li>Contact support if you need assistance with future redemptions</li>
                        </ul>
                        <p style='margin-top: 20px; color: #999;'>This is an automated message from Ecocycle NLUC. Please do not reply to this email.</p>
                        <p style='color: #999;'>© " . date('Y') . " Ecocycle NLUC. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        $mail->AltBody = "Redemption Rejected\n\nHello $toName,\n\nWe regret to inform you that your redemption request has been rejected by our Bard Administrator.\n\nOrder ID: ECO-REDEEM-$orderId\nProduct: $productName\nEcoCoins (Refunded): $ecocoinsSpent\nRejection Date: $currentDate\n\nThe $ecocoinsSpent EcoCoins have been refunded to your account and should appear within 2 hours.\n\nIf you have any questions, please contact our support team at support@ecocycle-nluc.com\n\nThank you for your understanding!";
        
        return $mail->send();
    } catch (Exception $e) {
        error_log("PHPMailer Error for redemption rejection: {$mail->ErrorInfo}");
        return false;
    }
}
?>