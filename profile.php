<?php
// Include session check for buyers
require_once 'config/session_check.php';

// Check if user is a buyer
if (!isBuyer()) {
    header("Location: login.php");
    exit();
}

// Include database connection
require_once 'config/database.php';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    try {
        $fullname = trim($_POST['fullname']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        
        // Basic validation
        if (empty($fullname) || empty($username) || empty($email) || empty($phone)) {
            $error_message = "Full name, username, email, and phone number are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Please enter a valid email address.";
        } else {
            // Check if username is already taken by another user
            $stmt = $pdo->prepare("SELECT buyer_id FROM Buyers WHERE username = ? AND buyer_id != ?");
            $stmt->execute([$username, getCurrentUserId()]);
            if ($stmt->fetch()) {
                $error_message = "Username is already taken by another user.";
            } else {
                // Check if email is already taken by another user
                $stmt = $pdo->prepare("SELECT buyer_id FROM Buyers WHERE email = ? AND buyer_id != ?");
                $stmt->execute([$email, getCurrentUserId()]);
                if ($stmt->fetch()) {
                    $error_message = "Email address is already taken by another user.";
                } else {
                    // Update profile
                    $stmt = $pdo->prepare("UPDATE Buyers SET fullname = ?, username = ?, email = ?, phone_number = ?, address = ? WHERE buyer_id = ?");
                    $stmt->execute([$fullname, $username, $email, $phone, $address, getCurrentUserId()]);
                    
                    // Also update seller account if exists with same email or username
                    $stmt = $pdo->prepare("UPDATE Sellers SET fullname = ?, username = ?, email = ?, phone_number = ?, address = ? WHERE email = ? OR username = ?");
                    $stmt->execute([$fullname, $username, $email, $phone, $address, $email, $username]);
                    
                    // Update session data to reflect changes immediately
                    $_SESSION['fullname'] = $fullname;
                    $_SESSION['username'] = $username;
                    $_SESSION['email'] = $email;
                    
                    $success_message = "Profile updated successfully!";
                }
            }
        }
    } catch (PDOException $e) {
        $error_message = "An error occurred while updating your profile.";
    }
}

// Get buyer data from database
try {
    $stmt = $pdo->prepare("SELECT * FROM Buyers WHERE buyer_id = ?");
    $stmt->execute([getCurrentUserId()]);
    $buyer = $stmt->fetch();
    
    if (!$buyer) {
        header("Location: login.php");
        exit();
    }
} catch (PDOException $e) {
    header("Location: login.php");
    exit();
}

// Get buyer's order statistics
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_orders FROM Orders WHERE buyer_id = ?");
    $stmt->execute([getCurrentUserId()]);
    $total_orders = $stmt->fetch()['total_orders'];
} catch (PDOException $e) {
    $total_orders = 0;
}

// Get buyer's cart count
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as cart_count FROM Cart WHERE buyer_id = ?");
    $stmt->execute([getCurrentUserId()]);
    $cart_count = $stmt->fetch()['cart_count'];
} catch (PDOException $e) {
    $cart_count = 0;
}

// Check if the user has a seller account
$has_seller_account = false;
try {
    $stmt = $pdo->prepare("SELECT seller_id FROM Sellers WHERE buyer_id = ?");
    $stmt->execute([getCurrentUserId()]);
    if ($stmt->fetch()) {
        $has_seller_account = true;
    }
} catch (PDOException $e) {
    // Log the error for debugging
    error_log("Error checking seller account: " . $e->getMessage());
}

// Get first letter of name for acronym
$first_letter = strtoupper(substr($buyer['fullname'], 0, 1));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Eco Cycle</title>
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
        
        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }
        
        .alert {
            border-radius: 8px;
            border: none;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        @media (max-width: 768px) {
            .stat-number {
                font-size: 1.5rem;
            }
            
            .profile-section {
                padding: 1.5rem;
            }
        }

        .btn-success {
            background-color: #28bf4b !important;
            border-color: #28bf4b !important;
            color: #111 !important; /* Black font */
        }
        .btn-success:hover, .btn-success:focus {
            background-color: #20a040 !important;
            border-color: #20a040 !important;
            color: #111 !important; /* Black font on hover */
        }
    </style>
</head>
<body>

<?php include 'homeheader.php'; ?>

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
                <h1 class="mb-2"><?php echo htmlspecialchars($buyer['fullname']); ?></h1>
                <p class="mb-2"><?php echo htmlspecialchars($buyer['email']); ?></p>
                <p class="mb-0">Username: <?php echo htmlspecialchars($buyer['username']); ?></p>
            </div>
            <div class="col-md-4 text-center text-md-end">
                <div class="d-flex flex-column align-items-center align-items-md-end">
                    <h3 class="mb-1"><?php echo $cart_count; ?></h3>
                    <p class="mb-0">Cart Items</p>
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
                    <div class="stat-number"><?php echo $cart_count; ?></div>
                    <div class="stat-label">Cart Items</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-item">
                    <div class="stat-number"><?php echo date('M Y', strtotime($buyer['created_at'])); ?></div>
                    <div class="stat-label">Member Since</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-item">
                    <div class="stat-number">Buyer</div>
                    <div class="stat-label">Account Type</div>
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
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <form id="profileForm" method="POST">
            <input type="hidden" name="action" value="update_profile">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="fullname" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="fullname" name="fullname" value="<?php echo htmlspecialchars($buyer['fullname']); ?>" readonly>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($buyer['username']); ?>" readonly>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($buyer['email']); ?>" readonly>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="phone" class="form-label">Phone Number</label>
                    <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($buyer['phone_number']); ?>" readonly>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="3" readonly><?php echo htmlspecialchars($buyer['address'] ?? ''); ?></textarea>
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
</div>

<?php if (!$has_seller_account): ?>
<!-- Become a Seller Section -->
<div class="container">
    <div class="profile-section">
        <div class="alert alert-info d-flex align-items-center justify-content-between" role="alert" style="border-radius: 8px; margin-bottom: 0;">
            <div>
                <i class="fas fa-store me-2"></i>
                <strong>Want to sell products?</strong> Become a seller and start listing your products on Ecocycle!
            </div>
            <a href="become_seller.php" class="btn btn-success ms-3">
                <i class="fas fa-arrow-right me-1"></i> Become a Seller
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const editProfileBtn = document.getElementById('editProfileBtn');
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    const saveCancelButtons = document.getElementById('saveCancelButtons');
    const profileForm = document.getElementById('profileForm');
    const formInputs = profileForm.querySelectorAll('input, textarea');
    
    // Store original values for cancel functionality
    const originalValues = {};
    formInputs.forEach(input => {
        originalValues[input.id] = input.value;
    });
    
    // Edit Profile Button Click
    editProfileBtn.addEventListener('click', function() {
        // Enable form inputs
        formInputs.forEach(input => {
            input.removeAttribute('readonly');
            input.classList.add('form-control-edit');
        });
        
        // Show save/cancel buttons
        saveCancelButtons.classList.remove('d-none');
        
        // Hide edit button
        editProfileBtn.classList.add('d-none');
        
        // Focus on first input
        formInputs[0].focus();
    });
    
    // Cancel Edit Button Click
    cancelEditBtn.addEventListener('click', function() {
        // Restore original values
        formInputs.forEach(input => {
            input.value = originalValues[input.id];
            input.setAttribute('readonly', true);
            input.classList.remove('form-control-edit');
        });
        
        // Hide save/cancel buttons
        saveCancelButtons.classList.add('d-none');
        
        // Show edit button
        editProfileBtn.classList.remove('d-none');
    });
    
    // Form submission handling
    profileForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Show loading state
        const saveButton = profileForm.querySelector('button[type="submit"]');
        const originalText = saveButton.innerHTML;
        saveButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
        saveButton.disabled = true;
        
        // Submit form data
        const formData = new FormData(profileForm);
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(html => {
            // Reload the page to show updated data
            window.location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
            // Show error message
            saveButton.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Error!';
            setTimeout(() => {
                saveButton.innerHTML = originalText;
                saveButton.disabled = false;
            }, 2000);
        });
    });
});
</script>

</body>
</html> 