<?php
require_once 'config/session_check.php';
require_once 'config/database.php';

if (!isBuyer()) {
    header('Location: login.php');
    exit();
}

$buyer_id = getCurrentUserId();
$fullname = getCurrentFullname();
$username = $_SESSION['username'];
$email = getCurrentEmail();

// Get buyer info (including phone, address, password)
$stmt = $pdo->prepare('SELECT * FROM Buyers WHERE buyer_id = ?');
$stmt->execute([$buyer_id]);
$buyer = $stmt->fetch();
if (!$buyer) {
    header('Location: login.php');
    exit();
}
$phone = $buyer['phone_number'];
$address = $buyer['address'];
$password = $buyer['password']; // Already hashed

// Check if already a seller (by email or username)
$stmt = $pdo->prepare('SELECT * FROM Sellers WHERE email = ? OR username = ?');
$stmt->execute([$email, $username]);
$seller = $stmt->fetch();

if ($seller) {
    // Already a seller, log in as seller
    $_SESSION['user_type'] = 'seller';
    $_SESSION['user_id'] = $seller['seller_id'];
    $_SESSION['fullname'] = $seller['fullname'];
    $_SESSION['username'] = $seller['username'];
    $_SESSION['email'] = $seller['email'];
    header('Location: seller-dashboard.php');
    exit();
}

$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_seller') {
    try {
        $stmt = $pdo->prepare('INSERT INTO Sellers (fullname, username, phone_number, email, password, address, status) VALUES (?, ?, ?, ?, ?, ?, "pending")');
        $stmt->execute([
            $fullname,
            $username,
            $phone,
            $email,
            $password,
            $address
        ]);
        $seller_id = $pdo->lastInsertId();

        // Set session as seller
        $_SESSION['user_type'] = 'seller';
        $_SESSION['user_id'] = $seller_id;
        $_SESSION['fullname'] = $fullname;
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;

        $_SESSION['success_message'] = 'Seller account created successfully!';
        header('Location: seller-dashboard.php');
        exit();
    } catch (PDOException $e) {
        $error_message = 'Could not create seller account.';
    }
}

// Get first letter of name for avatar
$first_letter = strtoupper(substr($fullname, 0, 1));

include 'homeheader.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Become a Seller - Eco Cycle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #28bf4b 0%, #20a040 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .profile-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        .section-title {
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #28bf4b;
            box-shadow: 0 0 0 0.2rem rgba(40, 191, 75, 0.25);
        }
        .btn-save {
            background: #28bf4b;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-save:hover {
            background: #20a040;
            transform: translateY(-1px);
        }
        .alert {
            border-radius: 8px;
            border: none;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        @media (max-width: 768px) {
            .profile-section {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
<!-- Sidebar Overlay and Slide-in Sidebar for Create Seller Account Page -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="sidebar-slide" id="sidebarSlide">
  <button class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Close sidebar">&times;</button>
  <?php include 'homesidebar.php'; ?>
</div>

<!-- Profile Header -->
<div class="profile-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-2 text-center">
                <div class="profile-avatar" style="width: 100px; height: 100px; background: #28bf4b; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 3rem; font-weight: bold; margin: 0 auto; border: 4px solid white;">
                    <?php echo $first_letter; ?>
                </div>
            </div>
            <div class="col-md-6 text-center text-md-start">
                <h1 class="mb-2"><?php echo htmlspecialchars($fullname); ?></h1>
                <p class="mb-2"><?php echo htmlspecialchars($email); ?></p>
                <p class="mb-0">Username: <?php echo htmlspecialchars($username); ?></p>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="profile-section">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="section-title mb-0">
                <i class="fas fa-store"></i>
                Become a Seller
            </h5>
        </div>
        <?php
        // $success_message = '';
        // if (isset($_SESSION['success_message'])) {
        //     $success_message = $_SESSION['success_message'];
        //     unset($_SESSION['success_message']);
        // }
        ?>
        <?php /* if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; */ ?>
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="create_seller">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" class="form-control" name="fullname" value="<?php echo htmlspecialchars($fullname); ?>" readonly>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($username); ?>" readonly>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($email); ?>" readonly>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone Number</label>
                    <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($phone); ?>" readonly>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label">Address</label>
                    <textarea class="form-control" name="address" rows="2" readonly><?php echo htmlspecialchars($address); ?></textarea>
                </div>
            </div>

            <button type="submit" class="btn btn-success w-100 btn-save">Create Seller Account</button>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 