<?php
session_start();
require_once 'config/database.php';

// PHPMailer for sending emails
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';
$error = '';
$show_form = true;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $user_type = $_POST['user_type'] ?? '';
    $issue_type = $_POST['issue_type'] ?? '';
    $description = trim($_POST['description']);
    $urgency = $_POST['urgency'] ?? 'medium';
    
    // Validation
    if (empty($name) || empty($email) || empty($user_type) || empty($issue_type) || empty($description)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!in_array($user_type, ['buyer', 'seller'])) {
        $error = 'Please select a valid user type.';
    } elseif (strlen($description) < 10) {
        $error = 'Please provide more details about your issue (at least 10 characters).';
    } else {
        // Send email to admin
        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'honeyboyb.corial@gmail.com';
            $mail->Password = 'keew djpl zgpw clpv';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            
            // Recipients
            $mail->setFrom('honeyboyb.corial@gmail.com', 'Ecocycle Support System');
            $mail->addAddress('honeyboyb.corial@gmail.com', 'Ecocycle Admin');
            $mail->addReplyTo($email, $name);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Ecocycle Support Request - ' . ucfirst($issue_type) . ' (' . ucfirst($urgency) . ')';
            
            $urgency_color = $urgency == 'high' ? '#dc3545' : ($urgency == 'medium' ? '#ffc107' : '#28a745');
            $user_type_badge = $user_type == 'buyer' ? '#007bff' : '#28bf4b';
            
            $mail->Body = '
                <html>
                <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
                    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;">
                        <div style="text-align: center; margin-bottom: 30px;">
                            <h2 style="color: #1a5f7a; margin-bottom: 10px;">Ecocycle Support Request</h2>
                            <p style="color: #666; font-size: 16px;">User Assistance Needed</p>
                        </div>
                        
                        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                            <h3 style="color: #333; margin-top: 0;">Request Details</h3>
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td style="padding: 8px; font-weight: bold; width: 140px;">Name:</td>
                                    <td style="padding: 8px;">' . htmlspecialchars($name) . '</td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px; font-weight: bold;">Email:</td>
                                    <td style="padding: 8px;">' . htmlspecialchars($email) . '</td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px; font-weight: bold;">User Type:</td>
                                    <td style="padding: 8px;">
                                        <span style="background-color: ' . $user_type_badge . '; color: white; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">
                                            ' . strtoupper($user_type) . '
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px; font-weight: bold;">Issue Type:</td>
                                    <td style="padding: 8px;">' . htmlspecialchars(ucfirst($issue_type)) . '</td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px; font-weight: bold;">Urgency:</td>
                                    <td style="padding: 8px;">
                                        <span style="color: ' . $urgency_color . '; font-weight: bold;">' . strtoupper($urgency) . '</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px; font-weight: bold;">Submitted:</td>
                                    <td style="padding: 8px;">' . date('Y-m-d H:i:s') . '</td>
                                </tr>
                            </table>
                        </div>
                        
                        <div style="margin-bottom: 20px;">
                            <h3 style="color: #333;">Issue Description</h3>
                            <div style="background-color: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 5px;">
                                <p style="margin: 0; white-space: pre-wrap;">' . htmlspecialchars($description) . '</p>
                            </div>
                        </div>
                        
                        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
                            <p style="color: #666; font-size: 14px; margin: 0;">
                                This support request was submitted through the Ecocycle contact form.<br>
                                Please respond to the user at: <a href="mailto:' . htmlspecialchars($email) . '">' . htmlspecialchars($email) . '</a>
                            </p>
                        </div>
                    </div>
                </body>
                </html>';
            
            $mail->AltBody = "Ecocycle Support Request\n\n" .
                            "Name: " . $name . "\n" .
                            "Email: " . $email . "\n" .
                            "User Type: " . $user_type . "\n" .
                            "Issue Type: " . $issue_type . "\n" .
                            "Urgency: " . $urgency . "\n" .
                            "Description: " . $description . "\n\n" .
                            "Submitted: " . date('Y-m-d H:i:s');
            
            $mail->send();
            
            // Send confirmation email to user
            $confirmation_mail = new PHPMailer(true);
            $confirmation_mail->isSMTP();
            $confirmation_mail->Host = 'smtp.gmail.com';
            $confirmation_mail->SMTPAuth = true;
            $confirmation_mail->Username = 'danielrillera2@gmail.com';
            $confirmation_mail->Password = 'jlpk jvjd hcey stjn';
            $confirmation_mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $confirmation_mail->Port = 587;
            
            $confirmation_mail->setFrom('danielrillera2@gmail.com', 'Ecocycle Support');
            $confirmation_mail->addAddress($email, $name);
            $confirmation_mail->isHTML(true);
            $confirmation_mail->Subject = 'Your Ecocycle Support Request Has Been Received';
            
            $confirmation_mail->Body = '
                <html>
                <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
                    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;">
                        <div style="text-align: center; margin-bottom: 30px;">
                            <h2 style="color: #1a5f7a; margin-bottom: 10px;">Support Request Received</h2>
                            <p style="color: #666; font-size: 16px;">Ecocycle Support Team</p>
                        </div>
                        
                        <div style="background-color: #d4edda; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #28a745;">
                            <h3 style="color: #155724; margin-top: 0;">Thank You!</h3>
                            <p style="margin: 0;">Your support request has been successfully submitted. Our team will review your issue and get back to you within 24 hours.</p>
                        </div>
                        
                        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 8px;">
                            <h4 style="color: #333; margin-top: 0;">Request Summary:</h4>
                            <ul style="margin: 10px 0; padding-left: 20px;">
                                <li><strong>Issue Type:</strong> ' . htmlspecialchars(ucfirst($issue_type)) . '</li>
                                <li><strong>Urgency:</strong> ' . htmlspecialchars(ucfirst($urgency)) . '</li>
                                <li><strong>Reference ID:</strong> #' . strtoupper(substr(md5($email . time()), 0, 8)) . '</li>
                            </ul>
                        </div>
                        
                        <div style="text-align: center; margin-top: 30px;">
                            <p style="color: #666; font-size: 14px;">
                                If you need immediate assistance, please reply to this email or contact us directly at:<br>
                                <a href="mailto:admin@ecocycle.com">admin@ecocycle.com</a>
                            </p>
                        </div>
                    </div>
                </body>
                </html>';
            
            $confirmation_mail->AltBody = "Your Ecocycle support request has been received.\n\n" .
                                        "Issue Type: " . $issue_type . "\n" .
                                        "Urgency: " . $urgency . "\n" .
                                        "Reference ID: #" . strtoupper(substr(md5($email . time()), 0, 8)) . "\n\n" .
                                        "Our team will respond within 24 hours.";
            
            $confirmation_mail->send();
            
            // Save contact submission to database
            try {
                $contact_stmt = $pdo->prepare('
                    INSERT INTO messages (buyer_id, sender_type, message_text, status, created_at)
                    VALUES (?, ?, ?, ?, NOW())
                ');
                
                // Create message text with contact details
                $message_text = "Contact Form Submission\n\n"
                    . "Name: " . $name . "\n"
                    . "Email: " . $email . "\n"
                    . "User Type: " . ucfirst($user_type) . "\n"
                    . "Issue Type: " . ucfirst($issue_type) . "\n"
                    . "Urgency: " . ucfirst($urgency) . "\n\n"
                    . "Message:\n" . $description;
                
                // Use 0 as buyer_id for contact form submissions (not logged-in users)
                $contact_stmt->execute([0, 'buyer', $message_text, 'sent']);
            } catch (Exception $db_error) {
                error_log('Failed to save contact submission to database: ' . $db_error->getMessage());
                // Don't fail the whole request if database save fails, email was sent successfully
            }
            
            $message = 'Your support request has been submitted successfully! We will get back to you within 24 hours. A confirmation email has been sent to your address.';
            $show_form = false;
            
        } catch (Exception $e) {
            $error = 'Unable to send your request. Please try again later or contact us directly at admin@ecocycle.com.';
            error_log('Contact form email failed: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Support - Ecocycle NLUC</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        
        .contact-container {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 600px;
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
        
        input, select, textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            box-sizing: border-box;
        }
        
        input:focus, select:focus, textarea:focus {
            border-color: #28bf4b;
            outline: none;
            box-shadow: 0 0 0 2px rgba(40,191,75,0.2);
        }
        
        textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .form-row {
            display: flex;
            gap: 1rem;
        }
        
        .form-row .form-group {
            flex: 1;
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
        
        .help-text {
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.3rem;
        }
        
        .urgency-high {
            color: #dc3545;
            font-weight: 600;
        }
        
        .urgency-medium {
            color: #ffc107;
            font-weight: 600;
        }
        
        .urgency-low {
            color: #28a745;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="contact-container">
        <h2>Contact Ecocycle Support</h2>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($show_form): ?>
        <div style="background-color: #f8f9fa; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
            <p style="margin: 0; color: #666; font-size: 0.9rem;">
                <i class="fas fa-info-circle"></i> Having trouble logging in? Fill out the form below and our support team will help you resolve the issue as quickly as possible.
            </p>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label for="name">Full Name *</label>
                <input type="text" id="name" name="name" required 
                       placeholder="Enter your full name"
                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" required 
                       placeholder="your.email@example.com"
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                <div class="help-text">We'll use this to contact you about your issue</div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="user_type">Account Type *</label>
                    <select id="user_type" name="user_type" required>
                        <option value="">Select...</option>
                        <option value="buyer" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'buyer') ? 'selected' : ''; ?>>Buyer</option>
                        <option value="seller" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'seller') ? 'selected' : ''; ?>>Seller</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="urgency">Urgency Level</label>
                    <select id="urgency" name="urgency">
                        <option value="low" <?php echo (isset($_POST['urgency']) && $_POST['urgency'] == 'low') ? 'selected' : ''; ?>>Low</option>
                        <option value="medium" <?php echo (isset($_POST['urgency']) && $_POST['urgency'] == 'medium') ? 'selected' : ''; ?>>Medium</option>
                        <option value="high" <?php echo (isset($_POST['urgency']) && $_POST['urgency'] == 'high') ? 'selected' : ''; ?>>High</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="issue_type">Issue Type *</label>
                <select id="issue_type" name="issue_type" required>
                    <option value="">Select an issue...</option>
                    <option value="login trouble" <?php echo (isset($_POST['issue_type']) && $_POST['issue_type'] == 'login trouble') ? 'selected' : ''; ?>>Login Trouble</option>
                    <option value="password reset" <?php echo (isset($_POST['issue_type']) && $_POST['issue_type'] == 'password reset') ? 'selected' : ''; ?>>Password Reset Issues</option>
                    <option value="account locked" <?php echo (isset($_POST['issue_type']) && $_POST['issue_type'] == 'account locked') ? 'selected' : ''; ?>>Account Locked</option>
                    <option value="forgot credentials" <?php echo (isset($_POST['issue_type']) && $_POST['issue_type'] == 'forgot credentials') ? 'selected' : ''; ?>>Forgot Email/Username</option>
                    <option value="verification" <?php echo (isset($_POST['issue_type']) && $_POST['issue_type'] == 'verification') ? 'selected' : ''; ?>>Account Verification</option>
                    <option value="other" <?php echo (isset($_POST['issue_type']) && $_POST['issue_type'] == 'other') ? 'selected' : ''; ?>>Other Issue</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="description">Describe Your Issue *</label>
                <textarea id="description" name="description" required 
                          placeholder="Please describe your issue in detail. Include any error messages you're seeing, what you were trying to do, and what happened instead..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                <div class="help-text">Provide as much detail as possible to help us resolve your issue faster</div>
            </div>
            
            <button type="submit">
                <i class="fas fa-paper-plane"></i> Send Support Request
            </button>
        </form>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
        </div>
    </div>
</body>
</html>
