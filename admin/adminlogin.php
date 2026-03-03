<?php
session_start();
require_once '../config/database.php';

$error_message = '';
$success_message = '';

// Check if admin is already logged in, redirect to dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
    if ($_SESSION['user_type'] === 'admin') {
        header('Location: dashboard.php');
        exit();
    } elseif ($_SESSION['user_type'] === 'bard') {
        header('Location: barddashboard.php');
        exit();
    }
}

// Check for logout success message
// Removed logout success message as requested

// Check for account inactive message
if (isset($_GET['error']) && $_GET['error'] === 'account_inactive') {
    $error_message = 'Your account is inactive or has been removed. Please contact the system administrator.';
}

// Check for session expired message
if (isset($_GET['error']) && $_GET['error'] === 'session_expired') {
    $error_message = 'Your session has expired. Please log in again.';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login_identifier = trim($_POST['login_identifier']);
    $password = str_replace(' ', '', $_POST['password']);

    if (empty($login_identifier) || empty($password)) {
        $error_message = 'Please fill in all required fields.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM Admins WHERE (username = ? OR email = ?) AND status = 'active'");
            $stmt->execute([$login_identifier, $login_identifier]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                // Login successful
                $_SESSION['user_id'] = $admin['admin_id'];
                $_SESSION['username'] = $admin['username'];
                $_SESSION['fullname'] = $admin['fullname'];
                $_SESSION['user_type'] = 'admin';
                $_SESSION['email'] = $admin['email'];
                $_SESSION['role'] = $admin['role'];
                $_SESSION['last_activity'] = time(); // Set last activity time
                
                // Log successful login
                error_log("Admin login: User ID " . $admin['admin_id'] . " (" . $admin['username'] . ") logged in at " . date('Y-m-d H:i:s'));
                
                header('Location: dashboard.php');
                exit();
            } else {
                // Try bard login
                $stmt = $pdo->prepare("SELECT * FROM Bard WHERE (username = ? OR email = ?) AND status = 'active'");
                $stmt->execute([$login_identifier, $login_identifier]);
                $bard = $stmt->fetch();

                if ($bard && password_verify($password, $bard['password'])) {
                    // Bard login successful
                    $_SESSION['user_id'] = $bard['bard_id'];
                    $_SESSION['username'] = $bard['username'];
                    $_SESSION['fullname'] = $bard['fullname'];
                    $_SESSION['user_type'] = 'bard';
                    $_SESSION['email'] = $bard['email'];
                    $_SESSION['role'] = $bard['role'];
                    $_SESSION['last_activity'] = time();

                    // Log successful bard login
                    error_log("Bard login: User ID " . $bard['bard_id'] . " (" . $bard['username'] . ") logged in at " . date('Y-m-d H:i:s'));

                    header('Location: barddashboard.php');
                    exit();
                } else {
                    $error_message = 'Invalid credentials or inactive account.';
                }
            }
        } catch (PDOException $e) {
            $error_message = 'Login failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Ecocycle</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="../css/vendor.css">
    <link rel="stylesheet" type="text/css" href="../style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&family=Open+Sans:ital,wght@0,400;0,600;0,700;1,400;1,600;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #1a5f7a 0%, #2c786c 50%, #28bf4b 100%);
            font-family: 'Poppins', sans-serif;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        main {
            display: flex;
            min-height: 100vh;
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
        .right {
            padding: 1rem 0;
        }
        .login-card {
            max-width: 400px;
            margin: 0 auto;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 6px 32px rgba(44,120,108,0.10);
            padding: 2.5rem 2rem 2rem 2rem;
            position: relative;
        }
        .login-logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .brand-text {
            color: #1a5f7a;
            font-weight: 700;
            font-size: 1.3rem;
            margin: 0;
            text-shadow: 0 1px 3px rgba(0,0,0,0.07);
        }
        .login-card h2 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #2c3e50;
            font-weight: 700;
        }
        .form-control {
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            font-size: 1rem;
            margin-bottom: 1rem;
            padding: 0.7rem 1rem;
        }
        .form-control:focus {
            border-color: #28bf4b;
            box-shadow: 0 0 0 2px rgba(40,191,75,0.10);
        }
        .btn-login {
            width: 100%;
            padding: 0.7rem;
            background: linear-gradient(135deg, #1a5f7a 0%, #28bf4b 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            box-shadow: 0 2px 10px rgba(40,191,75,0.10);
            transition: background 0.2s, box-shadow 0.2s;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #28bf4b 0%, #1a5f7a 100%);
            color: #fff;
            box-shadow: 0 4px 20px rgba(40,191,75,0.15);
        }
        .error-message {
            color: #c0392b;
            text-align: center;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        .success-message {
            color: #27ae60;
            text-align: center;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        .password-container {
            position: relative;
            margin-bottom: 1rem;
        }
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            padding: 0;
            font-size: 1rem;
            transition: color 0.2s;
        }
        .password-toggle:hover {
            color: #28bf4b;
        }
        @media (max-width: 768px) {
            .split {
                flex: 100%;
            }
            .left {
                padding: 1rem;
            }
            .login-card {
                padding: 2rem 1.5rem;
            }
        }
    </style>
</head>
<body>
    <main class="container">
        <div class="split left">
            <div class="brand">
                <div class="brand-logo">
                    <img src="../images/Ecocycle NLUC bee.png" alt="Ecocycle NLUC with bee logo" style="border-radius: 50%; object-fit: cover; width: 320px; height: 320px;">
                </div>
                <div class="brand-tagline" style="margin-top: 1rem; font-size: 2rem; font-weight: 400; color: #fff; text-align: center; line-height: 1.4; font-family: Poppins, sans-serif; font-style: italic;">
                  "NLUC: Nurturing a Lasting Unified Conservation"
                </div>
            </div>
        </div>

        <div class="split right">
            <div class="login-card">
                <div class="login-logo">
                    <div class="brand-text">Ecocycle Admin</div>
                </div>
                <?php if (!empty($success_message)): ?>
                    <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>
                <?php if (!empty($error_message)): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                <form method="POST" autocomplete="off">
                    <input type="text" name="login_identifier" class="form-control" placeholder="Username or Email" required autofocus>
                    <div class="password-container">
                        <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                    <button type="submit" class="btn btn-login">Log In</button>
                </form>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/js/all.min.js"></script>
    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
