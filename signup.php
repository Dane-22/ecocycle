<?php
session_start();

// Include database connection
require_once 'config/database.php';

// PHPMailer for email notifications
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error_message = '';
$success_message = '';

// Send welcome / signup notification email
function sendSignupEmail($toEmail, $toName, $userType)
{
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'honeyboyb.corial@gmail.com';
        $mail->Password   = 'keew djpl zgpw clpv';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('honeyboyb.corial@gmail.com', 'Ecocycle NLUC');
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);

        $roleLabel = $userType === 'seller' ? 'Seller' : 'Buyer';

        $subjectPrefix = $userType === 'seller'
            ? 'Welcome! Your Ecocycle seller account is pending approval'
            : 'Welcome to Ecocycle! Your buyer account is ready';

        $mail->Subject = $subjectPrefix;

        $statusNote = $userType === 'seller'
            ? 'Our team will review your documents and notify you once your seller account has been approved.'
            : 'You can now log in to Ecocycle and start exploring eco-friendly products and earning Ecocoins.';

        $loginUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/ecocycle/login.php';

        $mail->Body = '
            <html>
            <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
                <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;">
                    <div style="text-align: center; margin-bottom: 30px;">
                        <h2 style="color: #1a5f7a; margin-bottom: 10px;">Welcome to Ecocycle NLUC</h2>
                        <p style="color: #666; font-size: 16px;">'.htmlspecialchars($roleLabel).' Account Created</p>
                    </div>
                    <p>Hi '.htmlspecialchars($toName).',</p>
                    <p>Thank you for signing up as a '.strtolower($roleLabel).' at <strong>Ecocycle NLUC</strong>.</p>
                    <p>'.$statusNote.'</p>
                    <p style="margin-top: 16px;">You can access your account using the button below:</p>
                    <p style="text-align: center; margin: 24px 0;">
                        <a href="'.$loginUrl.'" style="background:#28bf4b;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;font-weight:600;">Go to Login</a>
                    </p>
                    <p style="font-size: 13px; color: #777;">If you did not create this account, please contact our support team immediately.</p>
                    <p style="margin-top: 24px;">— Ecocycle NLUC Team</p>
                </div>
            </body>
            </html>';

        $mail->AltBody = 'Hi '.$toName.",\n\n".
            'Thank you for signing up as a '.$roleLabel.' at Ecocycle NLUC.'."\n".
            strip_tags($statusNote)."\n\n".
            'You can log in here: '.$loginUrl."\n\n".
            'If you did not create this account, please contact our support team.';

        $mail->send();
    } catch (Exception $e) {
        // Do not block signup if email fails, just log it
        error_log('Signup email failed: '.$e->getMessage());
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['agreedToTerms']) || $_POST['agreedToTerms'] !== '1') {
        $error_message = 'You must agree to the Terms and Conditions to register.';
    } else {
        $full_name = trim($_POST['full_name']);
        $username = trim($_POST['username']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        $password = str_replace(' ', '', $_POST['password']);
        $confirm_password = str_replace(' ', '', $_POST['confirm_password']);
        $is_buyer = isset($_POST['user_type_buyer']) ? true : false;
        $is_seller = isset($_POST['user_type_seller']) ? true : false;
        $address = trim($_POST['address']);
        $documents = NULL;

        // Basic validation
        if (empty($full_name) || empty($username) || empty($phone) || empty($email) || empty($password) || empty($confirm_password) || empty($address)) {
            $error_message = 'Please fill in all required fields.';
        } elseif (!$is_buyer && !$is_seller) {
            $error_message = 'Please select at least one user type (Buyer or Seller).';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Please enter a valid email address.';
        } elseif (strlen($password) < 6) {
            $error_message = 'Password must be at least 6 characters long.';
        } elseif ($password !== $confirm_password) {
            $error_message = 'Passwords do not match. Please try again.';
        }
        if (empty($error_message)) {
            try {
                // Check if username already exists (case sensitive)
                $stmt = $pdo->prepare("SELECT username FROM Buyers WHERE BINARY username = ? UNION SELECT username FROM Sellers WHERE BINARY username = ?");
                $stmt->execute([$username, $username]);
                
                if ($stmt->rowCount() > 0) {
                  $error_message = 'Username already exists. Please choose a different username.';
                } else {
                  // Check if email already exists (case sensitive)
                  $stmt = $pdo->prepare("SELECT email FROM Buyers WHERE BINARY email = ? UNION SELECT email FROM Sellers WHERE BINARY email = ?");
                  $stmt->execute([$email, $email]);
                    
                  if ($stmt->rowCount() > 0) {
                    $error_message = 'Email already exists. Please use a different email address.';
                  } else {
                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $buyer_registered = false;
                    $seller_registered = false;
                    
                    // Register as Buyer if checkbox is checked
                    if ($is_buyer) {
                      $stmt = $pdo->prepare("INSERT INTO Buyers (fullname, username, phone_number, email, password, address, documents) VALUES (?, ?, ?, ?, ?, ?, ?)");
                      $stmt->execute([$full_name, $username, $phone, $email, $hashed_password, $address, $documents]);
                      $buyer_id = $pdo->lastInsertId();
                      $buyer_registered = true;
                      sendSignupEmail($email, $full_name, 'buyer');
                    }
                    
                    // Register as Seller if checkbox is checked
                    if ($is_seller) {
                      $stmt = $pdo->prepare("INSERT INTO Sellers (fullname, username, phone_number, email, password, address, documents, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
                      $stmt->execute([$full_name, $username, $phone, $email, $hashed_password, $address, $documents]);
                      $seller_id = $pdo->lastInsertId();
                      $seller_registered = true;
                      sendSignupEmail($email, $full_name, 'seller');
                    }
                    
                    // Set success message based on what was registered
                    if ($buyer_registered && $seller_registered) {
                      $success_message = 'Registration Successful! Your Seller ID is ' . $seller_id . '. Check your Email for confirmation. Your seller account is pending approval.';
                    } elseif ($seller_registered) {
                      $success_message = 'Registration successful! Your Seller ID is ' . $seller_id . '. Your seller account is pending approval. You will be notified once approved.';
                    } else {
                      $success_message = 'Registration Successful! Check your Email for confirmation.';
                    }
                    
                    // Redirect to login page after 2 seconds
                    header("refresh:2;url=login.php");
                  }
                }
            } catch (PDOException $e) {
                $error_message = 'Registration failed. Please try again.';
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
  <!-- SweetAlert2 CDN -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<style>
    /* SweetAlert2 Poppins Font */
    .swal2-popup,
    .swal2-title,
    .swal2-html-container,
    .swal2-confirm,
    .swal2-cancel {
        font-family: 'Poppins', sans-serif !important;
    }
    
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
      padding: 2rem 0;
    }
    .card {
      background: #fff;
      padding: 1rem 1.2rem 1rem 1.2rem;
      width: 100%;
      max-width: 350px;
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

    /* Add more space below confirm password and select user type */
    .card form input[name="confirm_password"] {
      margin-bottom: 0.8rem !important;
    }
    /* Checkbox container styling */
    .checkbox-group {
      display: flex;
      gap: 2rem;
      margin-bottom: 1.2rem;
      align-items: center;
      justify-content: center;
      flex-wrap: wrap;
    }
    .checkbox-group label {
      display: flex;
      align-items: center;
      gap: 0.8rem;
      cursor: pointer;
      margin: 0;
      font-size: 0.95rem;
      color: #000;
      user-select: none;
      font-weight: 500;
    }
    .checkbox-group input[type="checkbox"] {
      appearance: none;
      -webkit-appearance: none;
      -moz-appearance: none;
      width: 12px;
      height: 12px;
      min-width: 12px;
      min-height: 12px;
      border: 1px solid #28bf4b;
      border-radius: 50%;
      cursor: pointer;
      background-color: #fff;
      transition: background-color 0.2s, box-shadow 0.2s;
      flex-shrink: 0;
      display: inline-block;
      padding: 0;
      margin: 0;
      line-height: 12px;
    }
    .checkbox-group input[type="checkbox"]:hover {
      box-shadow: 0 0 0 4px rgba(40,191,75,0.2);
    }
    .checkbox-group input[type="checkbox"]:checked {
      background-color: #28bf4b;
    }
    .checkbox-group input[type="checkbox"]:focus {
      outline: none;
      box-shadow: 0 0 0 4px rgba(40,191,75,0.2);
    }
    /* Remove margin from confirm password wrapper */
    #confirmPassword {
      margin-bottom: 0 !important;
    }
    /* Add space between confirm password and checkbox group */
    #signupForm > div[style*="position: relative;"]:nth-of-type(2) {
      margin-bottom: 0.8rem !important;
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

    /* Footer text */
    .footer-text {
      font-size: 0.65rem;
      color: #000;
      margin-top: 0;
      line-height: 1;
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

    /* Reduce space between password and confirm password fields */
    #signupForm > div[style*="position: relative;"]:first-of-type {
      margin-bottom: 0.1rem !important;
    }
  </style>
<body>
  <header>
    <div class="container header-content">
      <div class="logo">
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
          " NLUC: Nurturing a Lasting Unified Conservation"
        </div>
      </div>
    </div>

    <div class="split right">
      <div class="card">
        <h2>Sign Up</h2>
        
        <?php if (!empty($error_message)): ?>
          <div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px; border: 1px solid #f5c6cb;">
            <?php echo htmlspecialchars($error_message); ?>
          </div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
          <script>
            Swal.fire({
              icon: 'success',
              title: 'Registration Successful!',
              text: '<?php echo htmlspecialchars($success_message); ?>',
              confirmButtonText: 'OK',
              allowOutsideClick: false
            });
          </script>
        <?php endif; ?>
        
        <!-- Add a hidden input to the form to track agreement -->
        <form id="signupForm" method="POST">
          <input type="hidden" id="agreedToTerms" name="agreedToTerms" value="0">
          <input type="text" placeholder="Full Name" name="full_name" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" required style="margin-bottom:0.8rem;width:100%;">
          <input type="text" placeholder="Username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required style="margin-bottom:0.8rem;width:100%;">
          <input type="tel" placeholder="Phone Number" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" required style="margin-bottom:0.8rem;width:100%;">
          <input type="text" placeholder="Address" name="address" value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>" required style="margin-bottom:0.8rem;width:100%;">
          <input type="email" placeholder="Email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required style="margin-bottom:0.8rem;width:100%;">
          <div style="position:relative;margin-bottom:0.8rem;">
          <div style="position:relative;margin-bottom:0.2rem;">
            <input type="password" placeholder="Password" name="password" id="password" required style="padding-right:35px;width:100%;">
            <i class="fas fa-eye" id="togglePassword" style="position:absolute;right:18px;top:42%;transform:translateY(-50%);cursor:pointer;color:#999;font-size:12px;z-index:10;transition:color 0.2s;height:16px;width:16px;"></i>
              <span id="passwordStrength" style="position:absolute;right:60px;top:42%;transform:translateY(-50%);font-size:12px;color:#888;font-weight:500;"></span>
          </div>
          <div style="position:relative;margin-bottom:0.2rem;">
            <input type="password" placeholder="Confirm Password" name="confirm_password" id="confirmPassword" required style="padding-right:35px;width:100%;">
            <i class="fas fa-eye" id="toggleConfirmPassword" style="position:absolute;right:18px;top:50%;transform:translateY(-50%);cursor:pointer;color:#999;font-size:12px;z-index:10;transition:color 0.2s;"></i>
              <span id="confirmPasswordStrength" style="position:absolute;right:60px;top:50%;transform:translateY(-50%);font-size:12px;color:#888;font-weight:500;"></span>
          </div>
          <div style="margin-bottom:0.5rem;font-size:0.95rem;font-weight:500;color:#000;">Select Your Role:</div>
          <div class="checkbox-group" style="margin-bottom:1rem;">
            <label>
              <input type="checkbox" name="user_type_buyer" value="buyer" <?php echo (isset($_POST['user_type_buyer'])) ? 'checked' : ''; ?>>
              <span>Buyer</span>
            </label>
            <label>
              <input type="checkbox" name="user_type_seller" value="seller" <?php echo (isset($_POST['user_type_seller'])) ? 'checked' : ''; ?>>
              <span>Seller</span>
            </label>
          </div>
          <div style="display:flex;align-items:center;margin-bottom:1rem;font-size:0.95rem;color:#000;">
            <label style="margin:0;color:#000;">I have read and agree to the</label>
            <a href="#" id="showTerms" style="color:#000;text-decoration:underline;margin-left:6px;">Terms and Conditions</a>
          </div>
          <button type="submit" class="btn-next">SIGN UP</button>
        </form>

        <p class="footer-text" style="font-size:0.85rem; color:#000; text-align:center;">
          Have an account? <a href="login.php" style="color:#000; font-weight:500; text-decoration:underline;">Log In</a>
        </p>
      </div>
    </div>
  </main>

  <script>
    // JavaScript to toggle password visibility
    document.getElementById('togglePassword').addEventListener('click', function(e) {
      e.preventDefault();
      const password = document.getElementById('password');
      const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
      password.setAttribute('type', type);
      this.classList.toggle('fa-eye-slash');
    });

    // JavaScript to toggle confirm password visibility
    document.getElementById('toggleConfirmPassword').addEventListener('click', function(e) {
      e.preventDefault();
      const confirmPassword = document.getElementById('confirmPassword');
      const type = confirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
      confirmPassword.setAttribute('type', type);
      this.classList.toggle('fa-eye-slash');
    });

    // JavaScript to validate password match in real-time
    document.getElementById('confirmPassword').addEventListener('input', function() {
      const password = document.getElementById('password').value;
      const confirmPassword = this.value;
      if (confirmPassword.length > 0) {
        if (password === confirmPassword) {
          this.style.borderColor = '#28a745';
          this.style.boxShadow = '0 0 0 0.2rem rgba(40, 167, 69, 0.25)';
        } else {
          this.style.borderColor = '#dc3545';
          this.style.boxShadow = '0 0 0 0.2rem rgba(220, 53, 69, 0.25)';
        }
      } else {
        this.style.borderColor = '#ddd';
        this.style.boxShadow = 'none';
      }
        // Password strength for confirm password
        const confirmStrengthSpan = document.getElementById('confirmPasswordStrength');
        if (confirmPassword.length === 0) {
          confirmStrengthSpan.textContent = '';
        } else {
          confirmStrengthSpan.textContent = getPasswordStrength(confirmPassword);
          confirmStrengthSpan.style.color = getStrengthColor(getPasswordStrength(confirmPassword));
        }
    });

    document.getElementById('password').addEventListener('input', function() {
      const confirmPassword = document.getElementById('confirmPassword');
      if (confirmPassword.value.length > 0) {
        if (this.value === confirmPassword.value) {
          confirmPassword.style.borderColor = '#28a745';
          confirmPassword.style.boxShadow = '0 0 0 0.2rem rgba(40, 167, 69, 0.25)';
        } else {
          confirmPassword.style.borderColor = '#dc3545';
          confirmPassword.style.boxShadow = '0 0 0 0.2rem rgba(220, 53, 69, 0.25)';
        }
      }
        // Password strength for password field
        const strengthSpan = document.getElementById('passwordStrength');
        if (this.value.length === 0) {
          strengthSpan.textContent = '';
        } else {
          strengthSpan.textContent = getPasswordStrength(this.value);
          strengthSpan.style.color = getStrengthColor(getPasswordStrength(this.value));
        }
    });

      // Password strength logic
      function getPasswordStrength(password) {
        let strength = 0;
        if (password.length >= 6) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;
        if (password.length >= 10) strength++;
        if (strength <= 1) return 'Weak';
        if (strength <= 3) return 'Medium';
        return 'Strong';
      }
      function getStrengthColor(strength) {
        if (strength === 'Weak') return '#dc3545';
        if (strength === 'Medium') return '#ffc107';
        return '#28a745';
      }

    // Terms and Conditions Modal
    function showTermsModal() {
      const modal = document.createElement('div');
      modal.id = 'termsModal';
      modal.style.position = 'fixed';
      modal.style.top = '0';
      modal.style.left = '0';
      modal.style.width = '100vw';
      modal.style.height = '100vh';
      modal.style.background = 'rgba(0,0,0,0.5)';
      modal.style.display = 'flex';
      modal.style.alignItems = 'center';
      modal.style.justifyContent = 'center';
      modal.style.zIndex = '9999';
      modal.innerHTML = `
        <div style="background:#fff;max-width:500px;width:90vw;padding:2rem;border-radius:12px;box-shadow:0 6px 32px rgba(44,120,108,0.15);position:relative;display:flex;flex-direction:column;height:80vh;">
          <h3 style='margin-bottom:1rem;'>Ecocycle Terms and Conditions</h3>
          <div id='termsContent' style='font-size:0.95rem;text-align:left;overflow-y:auto;flex:1 1 auto;padding-right:8px;position:relative;'>
            <ol style='padding-left:1.2rem;'>
              <li><strong>Account Registration:</strong> You agree to provide accurate and complete information during registration. False or misleading information may result in account suspension or termination.</li>
              <li><strong>Document Submission:</strong> All required documents must be submitted and are subject to verification. Falsified documents will result in rejection of registration and possible legal action.</li>
              <li><strong>Privacy:</strong> Your personal information will be handled in accordance with our Privacy Policy. We do not share your data with third parties except as required by law.</li>
              <li><strong>Account Approval:</strong> Seller accounts are subject to approval. You will be notified once your account is approved and activated.</li>
              <li><strong>Security:</strong> You are responsible for maintaining the confidentiality of your account credentials. Notify us immediately of any unauthorized use.</li>
              <li><strong>Prohibited Activities:</strong> You agree not to use Ecocycle for illegal activities, fraud, or to violate any applicable laws.</li>
              <li><strong>Termination:</strong> Ecocycle reserves the right to suspend or terminate accounts for violations of these terms or for suspicious activity.</li>
              <li><strong>Changes to Terms:</strong> Ecocycle may update these terms at any time. Continued use of the platform constitutes acceptance of the revised terms.</li>
              <li><strong>Contact:</strong> For questions, contact our support team via the Help link above.</li>
            </ol>
            <div id="checkboxContainer" style="margin-top:2rem;display:flex;align-items:center;font-size:0.95rem;color:#000;">
              <input type="checkbox" id="termsCheckboxModal" style="margin-right:8px;">
              <label for="termsCheckboxModal" style="margin:0;color:#000;">I have read and agree to these Terms and Conditions</label>
            </div>
          </div>
          <div style="position:sticky;bottom:0;background:#fff;padding-top:1rem;z-index:2;display:flex;flex-direction:column;align-items:flex-start;">
            <button id='closeTermsModal' style='margin-top:1rem;padding:0.5rem 1.2rem;background:#28bf4b;color:#fff;border:none;border-radius:6px;font-weight:600;cursor:pointer;width:100%;'>Agree and Close</button>
          </div>
        </div>
      `;
      document.body.appendChild(modal);

      // Disable close button until scrolled to bottom and checkbox checked
      const closeBtn = document.getElementById('closeTermsModal');
      const checkbox = document.getElementById('termsCheckboxModal');
      closeBtn.disabled = true;
      checkbox.disabled = true;

      const termsContent = document.getElementById('termsContent');
      // Show checkbox only after scrolling to bottom
      const checkboxContainer = document.getElementById('checkboxContainer');
      checkboxContainer.style.display = 'none';
      termsContent.addEventListener('scroll', function() {
        if (termsContent.scrollTop + termsContent.clientHeight >= termsContent.scrollHeight - 5) {
          checkboxContainer.style.display = 'flex';
          checkbox.disabled = false;
        }
      });
      checkbox.addEventListener('change', function() {
        closeBtn.disabled = !checkbox.checked;
      });

      closeBtn.onclick = function() {
        if (!checkbox.checked) {
          Swal.fire({
            icon: 'warning',
            title: 'Agreement Required',
            text: 'Please check the box to agree to the Terms and Conditions before closing.',
            confirmButtonText: 'OK',
            allowOutsideClick: false
          });
          return;
        }
        document.body.removeChild(modal);
        // Enable the submit button
        var submitBtn = document.querySelector('#signupForm button[type="submit"]');
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.classList.remove('disabled');
        }
        // Set hidden input value
        var agreedInput = document.getElementById('agreedToTerms');
        if (agreedInput) {
          agreedInput.value = '1';
        }
      };
    }
    document.getElementById('showTerms').onclick = function(e) {
      e.preventDefault();
      showTermsModal();
    };
    document.getElementById('showTermsFooter').onclick = function(e) {
      e.preventDefault();
      showTermsModal();
    };

    // On page load, disable the submit button until terms are agreed
    window.addEventListener('DOMContentLoaded', function() {
      var submitBtn = document.querySelector('#signupForm button[type="submit"]');
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.classList.add('disabled');
      }

      document.getElementById('signupForm').addEventListener('submit', function(e) {
        var agreedInput = document.getElementById('agreedToTerms');
        if (!agreedInput || agreedInput.value !== '1') {
          Swal.fire({
            icon: 'warning',
            title: 'Please Agree to Terms',
            text: 'You must read and agree to the Terms and Conditions before signing up.',
            confirmButtonText: 'OK',
            allowOutsideClick: false
          });
          e.preventDefault();
          return false;
        }
      });
    });
  </script>

</body>
</html>