<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include session check for sellers
require_once 'config/session_check.php';

// Check if user is a seller
if (!isSeller()) {
    header("Location: login.php");
    exit();
}

// Include database connection
require_once 'config/database.php';

// Get seller data from database
try {
    $stmt = $pdo->prepare("SELECT * FROM Sellers WHERE seller_id = ?");
    $stmt->execute([getCurrentUserId()]);
    $seller = $stmt->fetch();
    
    if (!$seller) {
        header("Location: login.php");
        exit();
    }
} catch (PDOException $e) {
    header("Location: login.php");
    exit();
}

// Get seller's statistics
try {
    // Get total products
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_products FROM Products WHERE seller_id = ? AND status = 'active'");
    $stmt->execute([getCurrentUserId()]);
    $total_products = $stmt->fetch()['total_products'];
    
    // Get total orders and sales
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT o.order_id) as total_orders, 
               SUM(oi.quantity * oi.price) as total_sales
        FROM Orders o 
        JOIN Order_Items oi ON o.order_id = oi.order_id 
        JOIN Products p ON oi.product_id = p.product_id 
        WHERE p.seller_id = ? AND o.status != 'cancelled'
    ");
    $stmt->execute([getCurrentUserId()]);
    $order_stats = $stmt->fetch();
    $total_orders = $order_stats['total_orders'] ?? 0;
    $total_sales = $order_stats['total_sales'] ?? 0;
    
    // Calculate average rating (mock for now since we don't have a rating system)
    $average_rating = 4.8;
    
} catch (PDOException $e) {
    $total_products = 0;
    $total_orders = 0;
    $total_sales = 0;
    $average_rating = 0;
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    try {
        if ($action === 'update_profile') {
            $fullname = trim($_POST['fullname']);
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $address = trim($_POST['address']);
            // Handle optional GCash QR upload
            $gcashQrFilename = null;
            $upload_attempted = false;
            $upload_error = null;
            if (isset($_FILES['gcash_qr'])) {
                $file = $_FILES['gcash_qr'];
                if ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                    $upload_attempted = true;
                    // map common upload errors
                    switch ($file['error']) {
                        case UPLOAD_ERR_OK:
                            break;
                        case UPLOAD_ERR_INI_SIZE:
                        case UPLOAD_ERR_FORM_SIZE:
                            $upload_error = 'The uploaded GCash QR image exceeds the server size limit. Try a smaller file (max 2MB).';
                            break;
                        case UPLOAD_ERR_PARTIAL:
                            $upload_error = 'The uploaded GCash QR image was only partially uploaded.';
                            break;
                        case UPLOAD_ERR_NO_TMP_DIR:
                            $upload_error = 'Missing a temporary folder on the server.';
                            break;
                        case UPLOAD_ERR_CANT_WRITE:
                            $upload_error = 'Failed to write uploaded GCash QR image to disk.';
                            break;
                        case UPLOAD_ERR_EXTENSION:
                        default:
                            $upload_error = 'An unexpected error occurred while uploading the GCash QR image.';
                            break;
                    }

                    if (!$upload_error) {
                        // basic validation
                        $allowedTypes = ['image/png' => 'png', 'image/jpeg' => 'jpg'];
                        if ($file['size'] > 2 * 1024 * 1024) { // 2MB limit
                            $upload_error = 'GCash QR image must be less than 2MB.';
                        } else {
                            $finfo = finfo_open(FILEINFO_MIME_TYPE);
                            $mime = finfo_file($finfo, $file['tmp_name']);
                            finfo_close($finfo);
                            if (!array_key_exists($mime, $allowedTypes)) {
                                $upload_error = 'Only PNG and JPEG images are allowed for the GCash QR.';
                            } else {
                                $ext = $allowedTypes[$mime];
                                $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'gcash_qr' . DIRECTORY_SEPARATOR;
                                if (!is_dir($uploadDir)) {
                                    if (!mkdir($uploadDir, 0755, true)) {
                                        $upload_error = 'Unable to create upload directory for GCash QR images.';
                                    }
                                }
                                if (!$upload_error && !is_writable($uploadDir)) {
                                    $upload_error = 'Upload directory is not writable by the web server.';
                                }

                                if (!$upload_error) {
                                    $newName = 'gcash_' . getCurrentUserId() . '_' . time() . '.' . $ext;
                                    $destination = $uploadDir . $newName;
                                    if (!move_uploaded_file($file['tmp_name'], $destination)) {
                                        $upload_error = 'Failed to move uploaded GCash QR image.';
                                    } else {
                                        $gcashQrFilename = 'uploads/gcash_qr/' . $newName;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            
            // Basic validation
            if (empty($fullname) || empty($username) || empty($email) || empty($phone)) {
                $error_message = "Full name, username, email, and phone number are required.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error_message = "Please enter a valid email address.";
            } else {
                // Check if username is already taken by another seller
                $stmt = $pdo->prepare("SELECT seller_id FROM Sellers WHERE username = ? AND seller_id != ?");
                $stmt->execute([$username, getCurrentUserId()]);
                if ($stmt->fetch()) {
                    $error_message = "Username is already taken by another user.";
                } else {
                    // Check if email is already taken by another seller
                    $stmt = $pdo->prepare("SELECT seller_id FROM Sellers WHERE email = ? AND seller_id != ?");
                    $stmt->execute([$email, getCurrentUserId()]);
                    if ($stmt->fetch()) {
                        $error_message = "Email address is already taken by another user.";
                    } else {
                        
                        // If upload was attempted and failed, stop and surface the message
                        if (!empty($upload_attempted) && !empty($upload_error)) {
                            $error_message = $upload_error;
                        } else {
                        // Ensure gcash_qr column exists; add if missing
                        try {
                            $colCheck = $pdo->prepare("SHOW COLUMNS FROM Sellers LIKE 'gcash_qr'");
                            $colCheck->execute();
                            if (!$colCheck->fetch()) {
                                $pdo->exec("ALTER TABLE Sellers ADD COLUMN gcash_qr VARCHAR(255) DEFAULT NULL");
                            }
                        } catch (PDOException $e) {
                            // ignore column creation errors; proceed without gcash_qr
                        }

                        // Update profile (include gcash_qr if uploaded)
                            try {
                                if ($gcashQrFilename) {
                                    $stmt = $pdo->prepare("UPDATE Sellers SET fullname = ?, username = ?, email = ?, phone_number = ?, address = ?, gcash_qr = ? WHERE seller_id = ?");
                                    $stmt->execute([$fullname, $username, $email, $phone, $address, $gcashQrFilename, getCurrentUserId()]);
                                } else {
                                    $stmt = $pdo->prepare("UPDATE Sellers SET fullname = ?, username = ?, email = ?, phone_number = ?, address = ? WHERE seller_id = ?");
                                    $stmt->execute([$fullname, $username, $email, $phone, $address, getCurrentUserId()]);
                                }
                            } catch (PDOException $e) {
                                // If upload succeeded but DB update failed, remove uploaded file to avoid orphans
                                if ($gcashQrFilename) {
                                    $maybePath = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $gcashQrFilename);
                                    if (file_exists($maybePath)) {
                                        @unlink($maybePath);
                                    }
                                }
                                throw $e; // rethrow to outer catch and show generic error
                            }
                        }
                        
                        // Log the profile update activity
                        $stmt = $pdo->prepare("INSERT INTO Transaction_Logs (user_id, user_type, action, description, ip_address) VALUES (?, 'seller', 'profile_update', ?, ?)");
                        $description = "Profile updated: " . $fullname;
                        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                        $stmt->execute([getCurrentUserId(), $description, $ip_address]);
                        
                        // Update session data to reflect changes immediately
                        $_SESSION['fullname'] = $fullname;
                        $_SESSION['username'] = $username;
                        $_SESSION['email'] = $email;
                        // Refresh seller data
                        $stmt = $pdo->prepare("SELECT * FROM Sellers WHERE seller_id = ?");
                        $stmt->execute([getCurrentUserId()]);
                        $seller = $stmt->fetch();
                        $success_message = "Profile updated successfully!";
                    }
                }
            }
        }
        // Add approve/reject logic here if needed (but this is not typical for seller-profile.php)
        if ($action === 'approve') {
            $stmt = $pdo->prepare("UPDATE Sellers SET status = 'approved' WHERE seller_id = ?");
            $stmt->execute([getCurrentUserId()]);
            $success_message = "Seller approved successfully!";
        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare("UPDATE Sellers SET status = 'rejected' WHERE seller_id = ?");
            $stmt->execute([getCurrentUserId()]);
            $success_message = "Seller rejected successfully!";
        }
    } catch (PDOException $e) {
        $error_message = "An error occurred while updating your profile.";
    }
}



// Get recent activities from database
$recent_activities = [];

try {
    // Get recent product activities
    $stmt = $pdo->prepare("
        SELECT 'product' as type, 
               CONCAT('Added product: ', name) as title,
               created_at as date,
               'fas fa-plus-circle' as icon
        FROM Products 
        WHERE seller_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([getCurrentUserId()]);
    $product_activities = $stmt->fetchAll();
    
    // Get recent order activities
    $stmt = $pdo->prepare("
        SELECT 'order' as type,
               CASE 
                   WHEN o.status = 'delivered' THEN CONCAT('Order #', o.order_id, ' completed')
                   WHEN o.status = 'shipped' THEN CONCAT('Order #', o.order_id, ' shipped')
                   WHEN o.status = 'confirmed' THEN CONCAT('Order #', o.order_id, ' confirmed')
                   ELSE CONCAT('Order #', o.order_id, ' received')
               END as title,
               o.created_at as date,
               CASE 
                   WHEN o.status = 'delivered' THEN 'fas fa-check-circle'
                   WHEN o.status = 'shipped' THEN 'fas fa-shipping-fast'
                   WHEN o.status = 'confirmed' THEN 'fas fa-thumbs-up'
                   ELSE 'fas fa-shopping-cart'
               END as icon
        FROM Orders o
        JOIN Order_Items oi ON o.order_id = oi.order_id
        JOIN Products p ON oi.product_id = p.product_id
        WHERE p.seller_id = ?
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([getCurrentUserId()]);
    $order_activities = $stmt->fetchAll();
    
    // Get recent sales activities (significant sales)
    $stmt = $pdo->prepare("
        SELECT 'sales' as type,
               CONCAT('Earned ₱', FORMAT(oi.quantity * oi.price, 2), ' from Order #', o.order_id) as title,
               o.created_at as date,
               'fas fa-coins' as icon
        FROM Orders o
        JOIN Order_Items oi ON o.order_id = oi.order_id
        JOIN Products p ON oi.product_id = p.product_id
        WHERE p.seller_id = ? AND o.status != 'cancelled'
        ORDER BY o.created_at DESC
        LIMIT 3
    ");
    $stmt->execute([getCurrentUserId()]);
    $sales_activities = $stmt->fetchAll();
    
    // Get recent profile updates from transaction logs
    $stmt = $pdo->prepare("
        SELECT 'profile' as type,
               'Profile updated' as title,
               created_at as date,
               'fas fa-user-edit' as icon
        FROM Transaction_Logs
        WHERE user_id = ? AND user_type = 'seller' AND action = 'profile_update'
        ORDER BY created_at DESC
        LIMIT 3
    ");
    $stmt->execute([getCurrentUserId()]);
    $profile_activities = $stmt->fetchAll();
    
    // Combine all activities and sort by date
    $all_activities = array_merge($product_activities, $order_activities, $sales_activities, $profile_activities);
    
    // Sort by date (newest first) and take the most recent 8 activities
    usort($all_activities, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    $recent_activities = array_slice($all_activities, 0, 8);
    
    // If no activities found, show default message
    if (empty($recent_activities)) {
        $recent_activities = [
            ['type' => 'info', 'title' => 'No activities yet', 'date' => date('Y-m-d'), 'icon' => 'fas fa-info-circle']
        ];
    }
    
} catch (PDOException $e) {
    // Fallback to mock data if there's an error
    $recent_activities = [
        ['type' => 'order', 'title' => 'Order #S12345 completed', 'date' => '2024-01-20', 'icon' => 'fas fa-shopping-bag'],
        ['type' => 'product', 'title' => 'Added new recycled notebook', 'date' => '2024-01-18', 'icon' => 'fas fa-plus-circle'],
        ['type' => 'sales', 'title' => 'Earned ₱' . number_format($total_sales), 'date' => '2024-01-17', 'icon' => 'fas fa-coins'],
        ['type' => 'order', 'title' => 'Order #S12340 received', 'date' => '2024-01-15', 'icon' => 'fas fa-shopping-cart']
    ];
}

// Get first letter of seller's name for avatar
$first_letter = strtoupper(substr($seller['fullname'], 0, 1));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Profile - Ecocycle NLUC</title>
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
        
        .profile-stats {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        
        .stat-item {
            text-align: center;
            padding: 1rem;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #28bf4b;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 500;
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
        
        .form-control-edit {
            background-color: #fff;
            border-color: #28bf4b;
            box-shadow: 0 0 0 0.2rem rgba(40, 191, 75, 0.25);
        }
        
        .form-control-edit:focus {
            border-color: #28bf4b;
            box-shadow: 0 0 0 0.2rem rgba(40, 191, 75, 0.25);
        }
        
        .rating-display {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .rating-stars {
            color: #ffc107;
        }
        

        
        @media (max-width: 768px) {
            .stat-number {
                font-size: 1.5rem;
            }
            
            .profile-section {
                padding: 1.5rem;
            }
            
            .document-actions {
                flex-direction: column;
            }
        }
        
        .btn-success {
            background-color: #28bf4b !important;
            border-color: #28bf4b !important;
        }
        .btn-success:hover, .btn-success:focus {
            background-color: #20a040 !important;
            border-color: #20a040 !important;
        }
        
        .activity-item {
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        
        .activity-item:hover {
            transform: translateX(5px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .activity-item[data-type="product"] {
            border-left-color: #28bf4b;
        }
        
        .activity-item[data-type="order"] {
            border-left-color: #007bff;
        }
        
        .activity-item[data-type="sales"] {
            border-left-color: #ffc107;
        }
        
        .activity-item[data-type="profile"] {
            border-left-color: #6f42c1;
        }
        
        .activity-item[data-type="info"] {
            border-left-color: #6c757d;
        }
        
        .activity-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .activity-icon.product {
            background-color: rgba(40, 191, 75, 0.1);
            color: #28bf4b;
        }
        
        .activity-icon.order {
            background-color: rgba(0, 123, 255, 0.1);
            color: #007bff;
        }
        
        .activity-icon.sales {
            background-color: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }
        
        .activity-icon.profile {
            background-color: rgba(111, 66, 193, 0.1);
            color: #6f42c1;
        }
        
        .activity-icon.info {
            background-color: rgba(108, 117, 125, 0.1);
            color: #6c757d;
        }
    </style>
</head>
<body>

<?php include 'sellerheader.php'; ?>

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
                <h1 class="mb-2"><?php echo htmlspecialchars($seller['fullname']); ?></h1>
                <p class="mb-2"><?php echo htmlspecialchars($seller['email']); ?></p>
                <div class="rating-display">
                    <?php if ($total_orders > 0): ?>
                        <div class="rating-stars">
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star<?php echo $i <= floor($average_rating) ? '' : ($i - $average_rating < 1 ? '-half-alt' : ''); ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <span><?php echo $average_rating; ?> (<?php echo $total_orders; ?> orders)</span>
                    <?php else: ?>
                        <span class="text-muted">No ratings yet</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-4 text-center text-md-end">
                <div class="d-flex flex-column align-items-center align-items-md-end">
                    <h3 class="mb-1">₱<?php echo number_format($total_sales, 2); ?></h3>
                    <p class="mb-0">Total Sales</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <!-- Profile Stats -->
    <div class="profile-stats">
        <div class="row">
            <div class="col-md-3 col-6">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $total_orders; ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $total_products; ?></div>
                    <div class="stat-label">Products Listed</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-item">
                    <div class="stat-number"><?php echo date('M Y', strtotime($seller['created_at'])); ?></div>
                    <div class="stat-label">Seller Since</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-item">
                    <div class="stat-number">Active</div>
                    <div class="stat-label">Account Status</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Personal Information Section -->
    <div class="profile-section">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="section-title mb-0">
                <i class="fas fa-user"></i>
                Personal Information
            </h5>
            <button type="button" class="btn btn-outline-success" id="editProfileBtn">
                <i class="fas fa-edit me-2"></i>Edit Profile
            </button>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <form id="profileForm" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update_profile">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="fullname" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="fullname" name="fullname" value="<?php echo htmlspecialchars($seller['fullname']); ?>" readonly>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($seller['username']); ?>" readonly>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($seller['email']); ?>" readonly>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="phone" class="form-label">Phone Number</label>
                    <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($seller['phone_number']); ?>" readonly>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="3" readonly><?php echo htmlspecialchars($seller['address'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="gcash_qr" class="form-label">GCash QR (for buyers)</label>
                    <input type="file" class="form-control" id="gcash_qr" name="gcash_qr" accept="image/png, image/jpeg" disabled>
                    <small class="text-muted">Optional. Upload your GCash QR so buyers can pay you directly. PNG or JPG, max 2MB.</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Current GCash QR</label>
                    <div id="gcashPreview" style="min-height:120px; display:flex; align-items:center; justify-content:center; border:1px dashed #e9ecef; border-radius:8px; padding:8px; background:#fff;">
                        <?php if (!empty($seller['gcash_qr'])): ?>
                            <img src="<?php echo htmlspecialchars($seller['gcash_qr']); ?>" alt="GCash QR" style="max-height:120px; max-width:100%; object-fit:contain;" />
                        <?php else: ?>
                            <span class="text-muted">No GCash QR uploaded</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="d-none" id="saveCancelButtons">
                <div class="alert alert-info" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Edit Mode:</strong> Make your changes and click Save Changes to update your profile.
                </div>
                <div class="d-flex justify-content-center gap-3">
                    <button type="submit" class="btn btn-success btn-lg me-2" style="min-width: 150px;">
                        <i class="fas fa-save me-2"></i>Save Changes
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-lg" id="cancelEditBtn" style="min-width: 120px;">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Become a Buyer Section -->
    <div class="profile-section">
        <div class="alert alert-info d-flex align-items-center justify-content-between" role="alert" style="border-radius: 8px; margin-bottom: 0;">
            <div>
                <i class="fas fa-shopping-cart me-2"></i>
                <strong>Want to buy products?</strong> Become a buyer and start shopping on Ecocycle!
            </div>
            <a href="become_buyer.php" class="btn btn-success ms-3">
                <i class="fas fa-arrow-right me-1"></i> Become a Buyer
            </a>
        </div>
    </div>
    
    <!-- Recent Activities Section -->
    <div class="profile-section">
        <h5 class="section-title">
            <i class="fas fa-history"></i>
            Recent Activities
        </h5>
        <div class="row">
            <?php foreach($recent_activities as $activity): ?>
            <div class="col-md-6 mb-3">
                <div class="d-flex align-items-center p-3 border rounded activity-item" data-type="<?php echo $activity['type']; ?>">
                    <div class="me-3">
                        <div class="activity-icon <?php echo $activity['type']; ?>">
                            <i class="<?php echo $activity['icon']; ?>"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-1"><?php echo htmlspecialchars($activity['title']); ?></h6>
                        <small class="text-muted">
                            <i class="fas fa-clock me-1"></i>
                            <?php echo date('M j, Y g:i A', strtotime($activity['date'])); ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Profile form handling
    const editProfileBtn = document.getElementById('editProfileBtn');
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    const saveCancelButtons = document.getElementById('saveCancelButtons');
    const profileForm = document.getElementById('profileForm');
    const profileFormInputs = profileForm.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"]');
    const addressTextarea = profileForm.querySelector('textarea[name="address"]');
    const gcashInput = document.getElementById('gcash_qr');
    const gcashPreview = document.getElementById('gcashPreview');
    
    // Store original values for cancel functionality
    const originalProfileValues = {};
    
    profileFormInputs.forEach(input => {
        originalProfileValues[input.id] = input.value;
    });
    if (addressTextarea) {
        originalProfileValues[addressTextarea.id] = addressTextarea.value;
    }
    
    // Edit Profile Button Click
    editProfileBtn.addEventListener('click', function() {
        // Enable form inputs
        profileFormInputs.forEach(input => {
            input.removeAttribute('readonly');
            input.classList.add('form-control-edit');
        });
        if (gcashInput) {
            gcashInput.removeAttribute('disabled');
        }
        if (addressTextarea) {
            addressTextarea.removeAttribute('readonly');
            addressTextarea.classList.add('form-control-edit');
        }
        
        // Show save/cancel buttons
        saveCancelButtons.classList.remove('d-none');
        
        // Hide edit button
        editProfileBtn.classList.add('d-none');
        
        // Focus on first input
        profileFormInputs[0].focus();
    });
    
    // Cancel Edit Button Click
    cancelEditBtn.addEventListener('click', function() {
        // Restore original values
        profileFormInputs.forEach(input => {
            input.value = originalProfileValues[input.id];
            input.setAttribute('readonly', true);
            input.classList.remove('form-control-edit');
        });
        if (gcashInput) {
            gcashInput.value = '';
            gcashInput.setAttribute('disabled', true);
            // restore preview to original server image
            // (server-rendered preview remains unchanged, so no action required)
        }
        if (addressTextarea) {
            addressTextarea.value = originalProfileValues[addressTextarea.id];
            addressTextarea.setAttribute('readonly', true);
            addressTextarea.classList.remove('form-control-edit');
        }
        
        // Hide save/cancel buttons
        saveCancelButtons.classList.add('d-none');
        
        // Show edit button
        editProfileBtn.classList.remove('d-none');
        

    });

    // Preview selected GCash QR image
    if (gcashInput) {
        gcashInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            const allowed = ['image/png', 'image/jpeg'];
            if (!allowed.includes(file.type)) {
                alert('Only PNG and JPG images are allowed for the GCash QR.');
                e.target.value = '';
                return;
            }
            if (file.size > 2 * 1024 * 1024) {
                alert('GCash QR image must be less than 2MB.');
                e.target.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = function(evt) {
                gcashPreview.innerHTML = '';
                const img = document.createElement('img');
                img.src = evt.target.result;
                img.style.maxHeight = '120px';
                img.style.maxWidth = '100%';
                img.style.objectFit = 'contain';
                gcashPreview.appendChild(img);
            };
            reader.readAsDataURL(file);
        });
    }
});
</script>

</body>
</html>
