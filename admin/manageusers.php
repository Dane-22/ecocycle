<style>
/* Unify admin dropdown-item style for all admin pages */
.dropdown-menu .dropdown-item {
  padding: 8px 15px !important;
  color: #495057 !important;
  font-weight: 400 !important;
  font-size: 1.025rem !important;
  display: flex !important;
  align-items: center !important;
  gap: 8px !important;
  transition: all 0.2s ease !important;
}
.dropdown-menu .dropdown-item:hover, .dropdown-menu .dropdown-item:focus {
  background: #fff !important;
  color: #28bf4b !important;
  font-weight: 400 !important;
  transform: translateX(5px) !important;
}

/* Add/adjust CSS for action buttons and dropdown menu */
.action-btn {
  min-width: 90px !important;
  max-width: 140px !important;
  width: 100% !important;
  text-align: center !important;
  white-space: nowrap !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
  margin-bottom: 2px !important;
  margin-right: 2px !important;
  display: block !important;
  font-size: 0.975rem !important;
  padding: 0.475rem 0.725rem !important;
}
.dropdown-menu {
  min-width: 150px !important;
  max-width: 200px !important;
  width: auto !important;
  box-sizing: border-box !important;
  word-break: break-word !important;
}

/* Make action cell container wider */
.d-flex.flex-wrap.gap-1.align-items-center {
  flex-wrap: wrap;
  max-width: 220px;
}

/* Make Action column wider */
th[style*="min-width:220px"] {
  min-width: 200px !important;
}

/* Minimize table fonts and padding */
.table {
  font-size: 1.025rem !important;
  margin-bottom: 0 !important;
}
.table td, .table th {
  padding: 0.85rem !important;
  vertical-align: middle !important;
}
.table th {
  font-size: 1.075rem !important;
  font-weight: 600 !important;
}
.badge {
  font-size: 0.925rem !important;
  padding: 0.525rem 0.775rem !important;
}
</style>
<?php
// Include session check first for security
include 'adminheader.php';

// Include database connection
require_once '../config/database.php';
// Include PHPMailer for email notifications
require_once '../config/phpmailer_config.php';



// Handle seller status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $action = $_POST['action'];
        
        // Debug: Log the action being processed
        error_log("Processing action: " . $action);
        
        // Handle add user action
        if ($action === 'add_user') {
            // Collect and validate fields
            $user_type = $_POST['add_user_type'] ?? '';
            $fullname = trim($_POST['add_fullname'] ?? '');
            $username = trim($_POST['add_username'] ?? '');
            $email = trim($_POST['add_email'] ?? '');
            $password = $_POST['add_password'] ?? '';
            $phone_number = trim($_POST['add_phone_number'] ?? '');
            $address = trim($_POST['add_address'] ?? '');
            
            // Basic validation
            if (!$user_type || !$fullname || !$username || !$email || !$password) {
                $error_message = "All required fields must be filled.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error_message = "Invalid email address.";
            } else {
                // Check for duplicate email or username
                $table = $user_type === 'seller' ? 'Sellers' : 'Buyers';
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE email = ? OR username = ?");
                $stmt->execute([$email, $username]);
                if ($stmt->fetchColumn() > 0) {
                    $error_message = "A user with this email or username already exists in $table.";
                } else {
                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    // Insert into table
                    if ($user_type === 'buyer') {
                        $stmt = $pdo->prepare("INSERT INTO Buyers (fullname, username, email, password, phone_number, address, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                        $stmt->execute([$fullname, $username, $email, $hashed_password, $phone_number, $address]);
                                            $success_message = "Buyer added successfully!";
                    echo "<script>
                      Swal.fire({
                        title: 'Success!',
                        text: 'Buyer added successfully!',
                        icon: 'success',
                        confirmButtonColor: '#28a745',
                        timer: 3000,
                        timerProgressBar: true
                      });
                    </script>";
                } elseif ($user_type === 'seller') {
                    $stmt = $pdo->prepare("INSERT INTO Sellers (fullname, username, email, password, phone_number, address, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())");
                    $stmt->execute([$fullname, $username, $email, $hashed_password, $phone_number, $address]);
                    $success_message = "Seller added successfully! (Status: pending)";
                    echo "<script>
                      Swal.fire({
                        title: 'Success!',
                        text: 'Seller added successfully! (Status: pending)',
                        icon: 'success',
                        confirmButtonColor: '#28a745',
                        timer: 3000,
                        timerProgressBar: true
                      });
                    </script>";
                    }
                }
            }
        }
        // Handle truncate all users action (independent of user_type)
        if ($action === 'truncate_all') {
            error_log("Starting truncate_all action");
            
            // Test database connection first
            $test_stmt = $pdo->prepare("SELECT 1");
            $test_stmt->execute();
            error_log("Database connection test successful");
            
            // Delete all buyers and sellers
            $stmt = $pdo->prepare("DELETE FROM Buyers");
            $stmt->execute();
            $buyers_deleted = $stmt->rowCount();
            error_log("Deleted " . $buyers_deleted . " buyers");
            
            $stmt = $pdo->prepare("DELETE FROM Sellers");
            $stmt->execute();
            $sellers_deleted = $stmt->rowCount();
            error_log("Deleted " . $sellers_deleted . " sellers");
            
                    $success_message = "All users deleted successfully! ($buyers_deleted buyers, $sellers_deleted sellers)";
        error_log("Truncate completed successfully: " . $success_message);
        
        // Add JavaScript to show SweetAlert2 success message
        echo "<script>
          Swal.fire({
            title: 'Success!',
            text: 'All users have been deleted successfully! ($buyers_deleted buyers, $sellers_deleted sellers)',
            icon: 'success',
            confirmButtonColor: '#28a745',
            timer: 3000,
            timerProgressBar: true
          });
        </script>";
        } else {
            // Handle individual user actions
            $user_id = $_POST['user_id'];
            $user_type = $_POST['user_type'];
            
            if ($user_type === 'seller') {
                if ($action === 'approve') {
                    $stmt = $pdo->prepare("UPDATE Sellers SET status = 'active' WHERE seller_id = ?");
                    $stmt->execute([$user_id]);
                    // Activate all pending products for this seller
                    $stmt = $pdo->prepare("UPDATE Products SET status = 'active' WHERE seller_id = ? AND status = 'pending'");
                    $stmt->execute([$user_id]);
                    $success_message = "Seller approved successfully! All pending products are now active.";
                    
                    // Add JavaScript to show SweetAlert2 success message
                    echo "<script>
                      Swal.fire({
                        title: 'Approved!',
                        text: 'Seller has been approved successfully! All pending products are now active.',
                        icon: 'success',
                        confirmButtonColor: '#28a745',
                        timer: 3000,
                        timerProgressBar: true
                      });
                    </script>";
                } elseif ($action === 'reject') {
                    $stmt = $pdo->prepare("UPDATE Sellers SET status = 'rejected' WHERE seller_id = ?");
                    $stmt->execute([$user_id]);
                    $success_message = "Seller rejected successfully!";
                    
                    // Add JavaScript to show SweetAlert2 success message
                    echo "<script>
                      Swal.fire({
                        title: 'Rejected!',
                        text: 'Seller has been rejected successfully!',
                        icon: 'error',
                        confirmButtonColor: '#dc3545',
                        timer: 3000,
                        timerProgressBar: true
                      });
                    </script>";
                } elseif ($action === 'delete') {
                    // Delete only the seller account
                    $stmt = $pdo->prepare("DELETE FROM Sellers WHERE seller_id = ?");
                    $stmt->execute([$user_id]);
                    $success_message = "Seller deleted successfully!";
                    
                    // Add JavaScript to show SweetAlert2 success message
                    echo "<script>
                      Swal.fire({
                        title: 'Deleted!',
                        text: 'Seller has been deleted successfully!',
                        icon: 'success',
                        confirmButtonColor: '#dc3545',
                        timer: 3000,
                        timerProgressBar: true
                      });
                    </script>";
                } elseif ($action === 'reset_password') {
                    // Reset password for seller
                    $new_password = $_POST['new_password'] ?? '';
                    if (empty($new_password)) {
                        $error_message = "Password cannot be empty.";
                    } else {
                        // Get seller email and name first
                        $stmt = $pdo->prepare("SELECT email, fullname FROM Sellers WHERE seller_id = ?");
                        $stmt->execute([$user_id]);
                        $seller = $stmt->fetch();
                        
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE Sellers SET password = ? WHERE seller_id = ?");
                        $stmt->execute([$hashed_password, $user_id]);
                        
                        // Send email notification with new password
                        if ($seller && !empty($seller['email'])) {
                            sendPasswordResetNotification($seller['email'], $seller['fullname'], $new_password, 'seller');
                        }
                        
                        $success_message = "Seller password reset successfully and notification email sent!";
                        echo "<script>
                          Swal.fire({
                            title: 'Success!',
                            text: 'Seller password has been reset successfully!\nNotification email has been sent to the registered email address.',
                            icon: 'success',
                            confirmButtonColor: '#28a745',
                            timer: 4000,
                            timerProgressBar: true
                          });
                        </script>";
                    }
                } elseif ($action === 'delete_both') {
                    // Delete both buyer and seller accounts
                    $stmt = $pdo->prepare("SELECT email FROM Sellers WHERE seller_id = ?");
                    $stmt->execute([$user_id]);
                    $seller = $stmt->fetch();
                    
                    if ($seller) {
                        // Check if there's a buyer account with the same email
                        $stmt = $pdo->prepare("SELECT buyer_id FROM Buyers WHERE email = ?");
                        $stmt->execute([$seller['email']]);
                        $buyer = $stmt->fetch();
                        
                        if ($buyer) {
                            // Delete both buyer and seller accounts
                            $stmt = $pdo->prepare("DELETE FROM Buyers WHERE buyer_id = ?");
                            $stmt->execute([$buyer['buyer_id']]);
                            $stmt = $pdo->prepare("DELETE FROM Sellers WHERE seller_id = ?");
                            $stmt->execute([$user_id]);
                            $success_message = "Both buyer and seller accounts deleted successfully!";
                            echo "<script>
                              Swal.fire({
                                title: 'Deleted!',
                                text: 'Both buyer and seller accounts deleted successfully!',
                                icon: 'success',
                                confirmButtonColor: '#dc3545',
                                timer: 3000,
                                timerProgressBar: true
                              });
                            </script>";
                        } else {
                            // Delete only seller account
                            $stmt = $pdo->prepare("DELETE FROM Sellers WHERE seller_id = ?");
                            $stmt->execute([$user_id]);
                            $success_message = "Seller deleted successfully!";
                            echo "<script>
                              Swal.fire({
                                title: 'Deleted!',
                                text: 'Seller deleted successfully!',
                                icon: 'success',
                                confirmButtonColor: '#dc3545',
                                timer: 3000,
                                timerProgressBar: true
                              });
                            </script>";
                        }
                                            } else {
                            $success_message = "Seller deleted successfully!";
                            echo "<script>
                              Swal.fire({
                                title: 'Deleted!',
                                text: 'Seller deleted successfully!',
                                icon: 'success',
                                confirmButtonColor: '#dc3545',
                                timer: 3000,
                                timerProgressBar: true
                              });
                            </script>";
                        }
                }
            } elseif ($user_type === 'buyer') {
                if ($action === 'reset_password') {
                    // Reset password for buyer
                    $new_password = $_POST['new_password'] ?? '';
                    if (empty($new_password)) {
                        $error_message = "Password cannot be empty.";
                    } else {
                        // Get buyer email and name first
                        $stmt = $pdo->prepare("SELECT email, fullname FROM Buyers WHERE buyer_id = ?");
                        $stmt->execute([$user_id]);
                        $buyer = $stmt->fetch();
                        
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE Buyers SET password = ? WHERE buyer_id = ?");
                        $stmt->execute([$hashed_password, $user_id]);
                        
                        // Send email notification with new password
                        if ($buyer && !empty($buyer['email'])) {
                            sendPasswordResetNotification($buyer['email'], $buyer['fullname'], $new_password, 'buyer');
                        }
                        
                        $success_message = "Buyer password reset successfully and notification email sent!";
                        echo "<script>
                          Swal.fire({
                            title: 'Success!',
                            text: 'Buyer password has been reset successfully!\nNotification email has been sent to the registered email address.',
                            icon: 'success',
                            confirmButtonColor: '#28a745',
                            timer: 4000,
                            timerProgressBar: true
                          });
                        </script>";
                    }
                } elseif ($action === 'block') {
                    // Check if status column exists, if not, add it first
                    try {
                        $stmt = $pdo->prepare("ALTER TABLE Buyers ADD COLUMN IF NOT EXISTS status ENUM('active', 'blocked') DEFAULT 'active'");
                        $stmt->execute();
                    } catch (PDOException $e) {
                        // Column might already exist, continue
                    }
                    $stmt = $pdo->prepare("UPDATE Buyers SET status = 'blocked' WHERE buyer_id = ?");
                    $stmt->execute([$user_id]);
                    $success_message = "Buyer blocked successfully!";
                    
                    // Add JavaScript to show SweetAlert2 success message
                    echo "<script>
                      Swal.fire({
                        title: 'Blocked!',
                        text: 'Buyer has been blocked successfully!',
                        icon: 'warning',
                        confirmButtonColor: '#ffc107',
                        timer: 3000,
                        timerProgressBar: true
                      });
                    </script>";
                } elseif ($action === 'delete') {
                    // Delete only the buyer account
                    $stmt = $pdo->prepare("DELETE FROM Buyers WHERE buyer_id = ?");
                    $stmt->execute([$user_id]);
                    $success_message = "Buyer deleted successfully!";
                    
                    // Add JavaScript to show SweetAlert2 success message
                    echo "<script>
                      Swal.fire({
                        title: 'Deleted!',
                        text: 'Buyer has been deleted successfully!',
                        icon: 'success',
                        confirmButtonColor: '#dc3545',
                        timer: 3000,
                        timerProgressBar: true
                      });
                    </script>";
                } elseif ($action === 'delete_both') {
                    // Delete both buyer and seller accounts
                    $stmt = $pdo->prepare("SELECT email FROM Buyers WHERE buyer_id = ?");
                    $stmt->execute([$user_id]);
                    $buyer = $stmt->fetch();
                    
                    if ($buyer) {
                        // Check if there's a seller account with the same email
                        $stmt = $pdo->prepare("SELECT seller_id FROM Sellers WHERE email = ?");
                        $stmt->execute([$buyer['email']]);
                        $seller = $stmt->fetch();
                        
                        if ($seller) {
                            // Delete both buyer and seller accounts
                            $stmt = $pdo->prepare("DELETE FROM Sellers WHERE seller_id = ?");
                            $stmt->execute([$seller['seller_id']]);
                            $stmt = $pdo->prepare("DELETE FROM Buyers WHERE buyer_id = ?");
                            $stmt->execute([$user_id]);
                            $success_message = "Both buyer and seller accounts deleted successfully!";
                            echo "<script>
                              Swal.fire({
                                title: 'Deleted!',
                                text: 'Both buyer and seller accounts deleted successfully!',
                                icon: 'success',
                                confirmButtonColor: '#dc3545',
                                timer: 3000,
                                timerProgressBar: true
                              });
                            </script>";
                        } else {
                            // Delete only buyer account
                            $stmt = $pdo->prepare("DELETE FROM Buyers WHERE buyer_id = ?");
                            $stmt->execute([$user_id]);
                            $success_message = "Buyer deleted successfully!";
                            echo "<script>
                              Swal.fire({
                                title: 'Deleted!',
                                text: 'Buyer deleted successfully!',
                                icon: 'success',
                                confirmButtonColor: '#dc3545',
                                timer: 3000,
                                timerProgressBar: true
                              });
                            </script>";
                        }
                                            } else {
                            $success_message = "Buyer deleted successfully!";
                            echo "<script>
                              Swal.fire({
                                title: 'Deleted!',
                                text: 'Buyer deleted successfully!',
                                icon: 'success',
                                confirmButtonColor: '#dc3545',
                                timer: 3000,
                                timerProgressBar: true
                              });
                            </script>";
                        }
                }
            }
        }
    } catch (PDOException $e) {
        $error_message = "An error occurred while updating user status: " . $e->getMessage();
        error_log("Database error: " . $e->getMessage());
    }
}

// Get all users (buyers and sellers)
try {
    // Check if status column exists in Buyers table
    $statusColumnExists = false;
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM Buyers LIKE 'status'");
        $stmt->execute();
        $statusColumnExists = $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        // Column doesn't exist
        $statusColumnExists = false;
    }
    
    // Get buyers - handle case where status column might not exist
    if ($statusColumnExists) {
        $stmt = $pdo->prepare("
            SELECT buyer_id as id, fullname, username, email, phone_number, address, 'buyer' as user_type, 
                   COALESCE(status, 'active') as status, created_at, NULL as business_docs
            FROM Buyers
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT buyer_id as id, fullname, username, email, phone_number, address, 'buyer' as user_type, 
                   'active' as status, created_at, NULL as business_docs
            FROM Buyers
        ");
    }
    $stmt->execute();
    $buyers = $stmt->fetchAll();
    
    // Get sellers
    $stmt = $pdo->prepare("
        SELECT seller_id as id, fullname, username, email, phone_number, address, 'seller' as user_type, 
               status, created_at, NULL as business_docs
        FROM Sellers
    ");
    $stmt->execute();
    $sellers = $stmt->fetchAll();
    
    // Create a map to track users with both buyer and seller accounts
    $userAccounts = [];
    
    // Process buyers
    foreach ($buyers as $buyer) {
        $key = strtolower($buyer['email']); // Use email as unique identifier
        if (!isset($userAccounts[$key])) {
            $userAccounts[$key] = [
                'buyer' => $buyer,
                'seller' => null
            ];
        } else {
            $userAccounts[$key]['buyer'] = $buyer;
        }
    }
    
    // Process sellers
    foreach ($sellers as $seller) {
        $key = strtolower($seller['email']); // Use email as unique identifier
        if (!isset($userAccounts[$key])) {
            $userAccounts[$key] = [
                'buyer' => null,
                'seller' => $seller
            ];
        } else {
            $userAccounts[$key]['seller'] = $seller;
        }
    }
    
    // Combine users, showing "Buyer/Seller" for users with both accounts
    $users = [];
    foreach ($userAccounts as $email => $accounts) {
        if ($accounts['buyer'] && $accounts['seller']) {
            // User has both buyer and seller accounts
            $combinedUser = $accounts['buyer']; // Use buyer as base
            $combinedUser['user_type'] = 'buyer/seller';
            $combinedUser['buyer_id'] = $accounts['buyer']['id'];
            $combinedUser['seller_id'] = $accounts['seller']['id'];
            $combinedUser['buyer_status'] = $accounts['buyer']['status'];
            $combinedUser['seller_status'] = $accounts['seller']['status'];
            // Use the earlier creation date
            if (strtotime($accounts['buyer']['created_at']) < strtotime($accounts['seller']['created_at'])) {
                $combinedUser['created_at'] = $accounts['buyer']['created_at'];
            } else {
                $combinedUser['created_at'] = $accounts['seller']['created_at'];
            }
            $users[] = $combinedUser;
        } elseif ($accounts['buyer']) {
            // Only buyer account
            $users[] = $accounts['buyer'];
        } elseif ($accounts['seller']) {
            // Only seller account
            $users[] = $accounts['seller'];
        }
    }
    
    // Sort by creation date (newest first)
    usort($users, function($a, $b) {
        return strtotime($a['created_at']) - strtotime($b['created_at']);
    });
    
} catch (PDOException $e) {
    $users = [];
    $error_message = "Failed to load users: " . $e->getMessage();
}

// Handle search and filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['filter']) ? trim($_GET['filter']) : '';

if ($search !== '' || $filter !== '') {
  $users = array_filter($users, function($user) use ($search, $filter) {
        $matchesSearch = $search === '' || 
                        stripos($user['username'], $search) !== false || 
                        stripos($user['email'], $search) !== false ||
                        stripos($user['fullname'], $search) !== false;
        
    $matchesFilter = true;
    if ($filter === 'buyer' || $filter === 'seller') {
      $matchesFilter = $user['user_type'] === $filter;
    } elseif ($filter === 'buyer/seller') {
      $matchesFilter = $user['user_type'] === 'buyer/seller';
    }
        
    return $matchesSearch && $matchesFilter;
  });
}

include 'adminsidebar.php';
?>

<div class="container users-fixed-panel" style="padding-top:40px;">
  <!-- Error Messages (keep Bootstrap alerts for errors) -->
  <?php if (!empty($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="fas fa-exclamation-circle me-2"></i>
      <?php echo htmlspecialchars($error_message); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="mb-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
    <form class="d-flex flex-wrap gap-2" style="max-width: 600px;" method="get">
      <div class="input-group flex-grow-1">
          <input class="form-control" type="search" placeholder="Search users..." aria-label="Search" name="search" value="<?php echo htmlspecialchars($search); ?>">
        <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
      </div>
      <select class="form-select" style="max-width: 180px;" aria-label="Filter by user type" name="filter" id="user-filter">
          <option value=""<?php echo $filter === '' ? ' selected' : ''; ?>>All Users</option>
          <option value="buyer"<?php echo $filter === 'buyer' ? ' selected' : ''; ?>>Buyers</option>
          <option value="seller"<?php echo $filter === 'seller' ? ' selected' : ''; ?>>Sellers</option>
          <option value="buyer/seller"<?php echo $filter === 'buyer/seller' ? ' selected' : ''; ?>>Buyer/Seller</option>
      </select>
    </form>
      
    <div class="d-flex flex-column align-items-center gap-2 ms-auto">
      <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal">
        <i class="fas fa-user-plus me-2"></i>Add User
      </button>
      <form method="POST" style="display: inline;" onsubmit="return confirmTruncate()">
        <input type="hidden" name="action" value="truncate_all">
         <button type="submit" class="btn btn-danger" title="Delete all buyers and sellers">
           <i class="fas fa-trash me-2"></i>Delete All Users
        </button>
      </form>
    </div>
  </div>

  <br>

  <div class="card p-4">
    <div style="overflow-x:auto;">
    <table class="table table-hover align-middle" style="min-width:700px;">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>Name</th>
          <th>Username</th>
          <th>Email</th>
          <th>Type</th>
          <th>Status</th>
          <th style="min-width:200px;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $i = 0;
        foreach ($users as $user):
        ?>
        <tr>
          <td><?php echo ++$i; ?></td>
          <td><?php echo htmlspecialchars($user['fullname']); ?></td>
          <td><?php echo htmlspecialchars($user['username']); ?></td>
          <td><?php echo htmlspecialchars($user['email']); ?></td>
          <td>
            <?php if ($user['user_type'] == 'buyer/seller'): ?>
              <span class="badge bg-info">Buyer/Seller</span>
            <?php else: ?>
              <span class="badge bg-<?php echo $user['user_type'] == 'buyer' ? 'primary' : 'warning'; ?>">
                <?php echo ucfirst($user['user_type']); ?>
              </span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($user['user_type'] == 'buyer/seller'): ?>
              <?php 
              // Determine overall status for buyer/seller account
              $buyerActive = ($user['buyer_status'] != 'blocked');
              $sellerActive = ($user['seller_status'] == 'approved' || $user['seller_status'] == 'active');
              
              if ($buyerActive && $sellerActive): ?>
                <span class="badge bg-success">Active</span>
              <?php elseif ($user['buyer_status'] == 'blocked'): ?>
                <span class="badge bg-danger">Blocked</span>
              <?php elseif ($user['seller_status'] == 'pending'): ?>
                <span class="badge bg-warning">Pending</span>
              <?php elseif ($user['seller_status'] == 'rejected'): ?>
                <span class="badge bg-danger">Rejected</span>
              <?php else: ?>
                <span class="badge bg-success">Active</span>
              <?php endif; ?>
            <?php elseif ($user['user_type'] == 'buyer'): ?>
              <?php if ($user['status'] == 'blocked'): ?>
                <span class="badge bg-danger">Blocked</span>
              <?php else: ?>
                <span class="badge bg-success">Active</span>
              <?php endif; ?>
            <?php else: ?>
              <?php if ($user['status'] == 'pending'): ?>
                <span class="badge bg-warning">Pending</span>
              <?php elseif ($user['status'] == 'approved'): ?>
                <span class="badge bg-success">Approved</span>
              <?php elseif ($user['status'] == 'rejected'): ?>
                <span class="badge bg-danger">Rejected</span>
              <?php elseif ($user['status'] == 'blocked'): ?>
                <span class="badge bg-danger">Blocked</span>
              <?php else: ?>
                <span class="badge bg-success">Active</span>
              <?php endif; ?>
            <?php endif; ?>
          </td>
          <td>
            <div class="d-flex flex-wrap gap-1 align-items-center" style="flex-wrap:wrap;max-width:220px;">
              <?php if ($user['user_type'] == 'buyer/seller'): ?>
                <!-- Buyer/Seller combined account actions -->
                <?php if ($user['buyer_status'] != 'blocked'): ?>
                  <form method="POST" style="display: inline;">
                    <input type="hidden" name="user_id" value="<?php echo $user['buyer_id']; ?>">
                    <input type="hidden" name="user_type" value="buyer">
                    <input type="hidden" name="action" value="block">
                    <button type="button" class="btn btn-sm btn-warning action-btn" onclick="confirmBlockBuyer(<?php echo $user['buyer_id']; ?>)">
                      <i class="fas fa-ban me-1"></i>Block Buyer
                    </button>
                  </form>
                <?php endif; ?>
                <button type="button" class="btn btn-sm btn-info action-btn" onclick="openResetPasswordModal(<?php echo $user['buyer_id']; ?>, 'buyer')">
                  <i class="fas fa-key me-1"></i>Reset
                </button>
                <?php if ($user['seller_status'] == 'pending'): ?>
                  <form method="POST" style="display: inline;">
                    <input type="hidden" name="user_id" value="<?php echo $user['seller_id']; ?>">
                    <input type="hidden" name="user_type" value="seller">
                    <input type="hidden" name="action" value="approve">
                    <button type="button" class="btn btn-sm btn-success action-btn" onclick="confirmApproveSeller(<?php echo $user['seller_id']; ?>)">
                      <i class="fas fa-check me-1"></i>Approve Seller
                    </button>
                  </form>
                  <form method="POST" style="display: inline;">
                    <input type="hidden" name="user_id" value="<?php echo $user['seller_id']; ?>">
                    <input type="hidden" name="user_type" value="seller">
                    <input type="hidden" name="action" value="reject">
                    <button type="button" class="btn btn-sm btn-danger action-btn" onclick="confirmRejectSeller(<?php echo $user['seller_id']; ?>)">
                      <i class="fas fa-times me-1"></i>Reject Seller
                    </button>
                  </form>
                <?php endif; ?>
                <!-- Delete options for buyer/seller accounts -->
                <form method="POST" style="display: inline;">
                  <input type="hidden" name="user_id" value="<?php echo $user['buyer_id']; ?>">
                  <input type="hidden" name="user_type" value="buyer">
                  <input type="hidden" name="action" value="delete_both">
                   <button type="button" class="btn btn-sm btn-danger action-btn" onclick="confirmDeleteBoth(<?php echo $user['buyer_id']; ?>, <?php echo $user['seller_id']; ?>)">
                     <i class="fas fa-trash me-1"></i>Delete
                  </button>
                </form>
              <?php elseif ($user['user_type'] == 'buyer'): ?>
                <?php if ($user['status'] != 'blocked'): ?>
                  <form method="POST" style="display: inline;">
                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                    <input type="hidden" name="user_type" value="<?php echo $user['user_type']; ?>">
                    <input type="hidden" name="action" value="block">
                    <button type="button" class="btn btn-sm btn-warning action-btn" onclick="confirmBlockUser(<?php echo $user['id']; ?>, '<?php echo $user['user_type']; ?>')">
                      <i class="fas fa-ban me-1"></i>Block
                    </button>
                  </form>
                <?php endif; ?>
                <button type="button" class="btn btn-sm btn-info action-btn" onclick="openResetPasswordModal(<?php echo $user['id']; ?>, '<?php echo $user['user_type']; ?>')">
                  <i class="fas fa-key me-1"></i>Reset
                </button>
                <form method="POST" style="display: inline;">
                  <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                  <input type="hidden" name="user_type" value="<?php echo $user['user_type']; ?>">
                  <input type="hidden" name="action" value="delete">
                   <button type="button" class="btn btn-sm btn-danger action-btn" onclick="confirmDeleteUser(<?php echo $user['id']; ?>, '<?php echo $user['user_type']; ?>')">
                     <i class="fas fa-trash me-1"></i>Delete
                  </button>
                </form>
              <?php else: ?>
                <!-- Seller only account actions -->
                <?php if ($user['status'] == 'pending'): ?>
                  <form method="POST" style="display: inline;">
                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                    <input type="hidden" name="user_type" value="<?php echo $user['user_type']; ?>">
                    <input type="hidden" name="action" value="approve">
                    <button type="button" class="btn btn-sm btn-success action-btn" onclick="confirmApproveUser(<?php echo $user['id']; ?>, '<?php echo $user['user_type']; ?>')">
                      <i class="fas fa-check me-1"></i>Approve
                    </button>
                  </form>
                  <form method="POST" style="display: inline;">
                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                    <input type="hidden" name="user_type" value="<?php echo $user['user_type']; ?>">
                    <input type="hidden" name="action" value="reject">
                    <button type="button" class="btn btn-sm btn-danger action-btn" onclick="confirmRejectUser(<?php echo $user['id']; ?>, '<?php echo $user['user_type']; ?>')">
                      <i class="fas fa-times me-1"></i>Reject
                    </button>
                  </form>
                <?php endif; ?>
                <button type="button" class="btn btn-sm btn-info action-btn" onclick="openResetPasswordModal(<?php echo $user['id']; ?>, '<?php echo $user['user_type']; ?>')">
                  <i class="fas fa-key me-1"></i>Reset
                </button>
                <form method="POST" style="display: inline;">
                  <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                  <input type="hidden" name="user_type" value="<?php echo $user['user_type']; ?>">
                  <input type="hidden" name="action" value="delete">
                  <button type="button" class="btn btn-sm btn-danger action-btn" onclick="confirmDeleteUser(<?php echo $user['id']; ?>, '<?php echo $user['user_type']; ?>')">
                    <i class="fas fa-trash me-1"></i>Delete
                  </button>
                </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Optionally add JS for confirmation dialog -->
<script>
// Auto-submit form on dropdown change
const filterSelect = document.getElementById('user-filter');
if (filterSelect) {
  filterSelect.addEventListener('change', function() {
    this.form.submit();
  });
}

// Confirmation function for truncate all users using SweetAlert2
function confirmTruncate() {
  Swal.fire({
    title: "Are you sure?",
    text: "This will permanently delete ALL buyers and sellers from the database! This action cannot be undone.",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    cancelButtonColor: "#3085d6",
    confirmButtonText: "Yes, delete all users!",
    cancelButtonText: "Cancel"
  }).then((result) => {
    if (result.isConfirmed) {
      // Submit the form
      document.querySelector('form[onsubmit="return confirmTruncate()"]').submit();
    }
  });
  
  // Return false to prevent form submission until SweetAlert confirms
  return false;
}

// SweetAlert2 functions for user actions
function confirmBlockBuyer(buyerId) {
  Swal.fire({
    title: "Are you sure?",
    text: "Are you sure you want to block the buyer account?",
    showCancelButton: true,
    confirmButtonColor: "#ffc107",
    cancelButtonColor: "#6c757d",
    confirmButtonText: "Yes, block it!"
  }).then((result) => {
    if (result.isConfirmed) {
      submitForm(buyerId, 'buyer', 'block');
    }
  });
}

function confirmApproveSeller(sellerId) {
  Swal.fire({
    title: "Are you sure?",
    text: "Are you sure you want to approve this seller?",
    showCancelButton: true,
    confirmButtonColor: "#28a745",
    cancelButtonColor: "#6c757d",
    confirmButtonText: "Yes, approve it!"
  }).then((result) => {
    if (result.isConfirmed) {
      submitForm(sellerId, 'seller', 'approve');
    }
  });
}

function confirmRejectSeller(sellerId) {
  Swal.fire({
    title: "Are you sure?",
    text: "Are you sure you want to reject this seller?",
    showCancelButton: true,
    confirmButtonColor: "#dc3545",
    cancelButtonColor: "#6c757d",
    confirmButtonText: "Yes, reject it!"
  }).then((result) => {
    if (result.isConfirmed) {
      submitForm(sellerId, 'seller', 'reject');
    }
  });
}

function confirmDeleteBuyerOnly(buyerId) {
  Swal.fire({
    title: "Are you sure?",
    text: "Are you sure you want to delete the buyer account only? This action cannot be undone.",
    showCancelButton: true,
    confirmButtonColor: "#dc3545",
    cancelButtonColor: "#6c757d",
    confirmButtonText: "Yes, delete it!"
  }).then((result) => {
    if (result.isConfirmed) {
      submitForm(buyerId, 'buyer', 'delete');
    }
  });
}

function confirmDeleteSellerOnly(sellerId) {
  Swal.fire({
    title: "Are you sure?",
    text: "Are you sure you want to delete the seller account only? This action cannot be undone.",
    showCancelButton: true,
    confirmButtonColor: "#dc3545",
    cancelButtonColor: "#6c757d",
    confirmButtonText: "Yes, delete it!"
  }).then((result) => {
    if (result.isConfirmed) {
      submitForm(sellerId, 'seller', 'delete');
    }
  });
}

function confirmDeleteBoth(buyerId, sellerId) {
  Swal.fire({
    title: "⚠️ WARNING!",
    text: "This will delete BOTH buyer and seller accounts! This action cannot be undone. Are you absolutely sure?",
    showCancelButton: true,
    confirmButtonColor: "#dc3545",
    cancelButtonColor: "#6c757d",
    confirmButtonText: "Yes, delete both!"
  }).then((result) => {
    if (result.isConfirmed) {
      submitForm(buyerId, 'buyer', 'delete_both');
    }
  });
}

function confirmBlockUser(userId, userType) {
  Swal.fire({
    title: "Are you sure?",
    text: "Are you sure you want to block this user?",
    showCancelButton: true,
    confirmButtonColor: "#ffc107",
    cancelButtonColor: "#6c757d",
    confirmButtonText: "Yes, block it!"
  }).then((result) => {
    if (result.isConfirmed) {
      submitForm(userId, userType, 'block');
    }
  });
}

function confirmDeleteUser(userId, userType) {
  Swal.fire({
    title: "Are you sure?",
    text: "Are you sure you want to delete this user? This action cannot be undone.",
    showCancelButton: true,
    confirmButtonColor: "#dc3545",
    cancelButtonColor: "#6c757d",
    confirmButtonText: "Yes, delete it!"
  }).then((result) => {
    if (result.isConfirmed) {
      submitForm(userId, userType, 'delete');
    }
  });
}

function confirmApproveUser(userId, userType) {
  Swal.fire({
    title: "Are you sure?",
    text: "Are you sure you want to approve this seller?",
    showCancelButton: true,
    confirmButtonColor: "#28a745",
    cancelButtonColor: "#6c757d",
    confirmButtonText: "Yes, approve it!"
  }).then((result) => {
    if (result.isConfirmed) {
      submitForm(userId, userType, 'approve');
    }
  });
}

function confirmRejectUser(userId, userType) {
  Swal.fire({
    title: "Are you sure?",
    text: "Are you sure you want to reject this seller?",
    showCancelButton: true,
    confirmButtonColor: "#dc3545",
    cancelButtonColor: "#6c757d",
    confirmButtonText: "Yes, reject it!"
  }).then((result) => {
    if (result.isConfirmed) {
      submitForm(userId, userType, 'reject');
    }
  });
}

function generatePassword() {
  // Generate password with format: EcocycleNluc2026 + random numbers
  const randomNum = Math.floor(Math.random() * 900) + 100; // 100-999
  const password = 'EcocycleNluc2026@' + randomNum;
  return password;
}

function openResetPasswordModal(userId, userType) {
  // Store user ID and type in data attributes
  document.getElementById('resetPasswordUserId').value = userId;
  document.getElementById('resetPasswordUserType').value = userType;
  
  // Auto-generate password
  const generatedPassword = generatePassword();
  document.getElementById('resetPasswordNewPassword').value = generatedPassword;
  document.getElementById('resetPasswordConfirmPassword').value = generatedPassword;
  
  // Update password strength indicator
  checkPasswordStrength();
  
  // Show modal
  const modal = new bootstrap.Modal(document.getElementById('resetPasswordModal'));
  modal.show();
}

function regeneratePassword() {
  const generatedPassword = generatePassword();
  document.getElementById('resetPasswordNewPassword').value = generatedPassword;
  document.getElementById('resetPasswordConfirmPassword').value = generatedPassword;
  
  // Update password strength indicator
  checkPasswordStrength();
  
  // Show notification
  Swal.fire({
    title: 'Password Generated!',
    text: 'A new password has been generated.',
    icon: 'info',
    confirmButtonColor: '#28bf4b',
    timer: 2000,
    timerProgressBar: true
  });
}

function togglePasswordVisibility(inputId) {
  const input = document.getElementById(inputId);
  const button = event.target.closest('.password-toggle-btn');
  const icon = button.querySelector('i');
  
  if (input.type === 'password') {
    input.type = 'text';
    icon.classList.remove('fa-eye');
    icon.classList.add('fa-eye-slash');
  } else {
    input.type = 'password';
    icon.classList.remove('fa-eye-slash');
    icon.classList.add('fa-eye');
  }
}

function checkPasswordStrength() {
  const password = document.getElementById('resetPasswordNewPassword').value;
  const strengthBar = document.getElementById('passwordStrengthBar');
  const strengthText = document.getElementById('passwordStrengthText');
  
  if (!password) {
    strengthBar.style.width = '0%';
    strengthText.textContent = '';
    return;
  }
  
  let strength = 0;
  let strengthLabel = '';
  let strengthClass = '';
  
  // Check length
  if (password.length >= 6) strength += 20;
  if (password.length >= 8) strength += 10;
  if (password.length >= 12) strength += 10;
  if (password.length >= 16) strength += 10;
  
  // Check for lowercase letters
  if (/[a-z]/.test(password)) strength += 15;
  
  // Check for uppercase letters
  if (/[A-Z]/.test(password)) strength += 15;
  
  // Check for numbers
  if (/[0-9]/.test(password)) strength += 15;
  
  // Check for special characters
  if (/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) strength += 15;
  
  // Determine strength level
  if (strength < 25) {
    strengthLabel = 'Weak';
    strengthClass = 'strength-weak';
  } else if (strength < 50) {
    strengthLabel = 'Fair';
    strengthClass = 'strength-fair';
  } else if (strength < 75) {
    strengthLabel = 'Good';
    strengthClass = 'strength-good';
  } else {
    strengthLabel = 'Strong';
    strengthClass = 'strength-strong';
  }
  
  // Update UI
  strengthBar.style.width = Math.min(strength, 100) + '%';
  strengthBar.className = 'password-strength-bar ' + strengthClass;
  strengthText.textContent = 'Strength: ' + strengthLabel;
  strengthText.className = 'password-strength-text ' + strengthClass;
}

function submitResetPassword() {
  const userId = document.getElementById('resetPasswordUserId').value;
  const userType = document.getElementById('resetPasswordUserType').value;
  const newPassword = document.getElementById('resetPasswordNewPassword').value;
  const confirmPassword = document.getElementById('resetPasswordConfirmPassword').value;
  
  // Validate passwords
  if (!newPassword || !confirmPassword) {
    Swal.fire({
      title: 'Error',
      text: 'Both password fields are required.',
      icon: 'error',
      confirmButtonColor: '#dc3545'
    });
    return;
  }
  
  if (newPassword !== confirmPassword) {
    Swal.fire({
      title: 'Error',
      text: 'Passwords do not match.',
      icon: 'error',
      confirmButtonColor: '#dc3545'
    });
    return;
  }
  
  if (newPassword.length < 6) {
    Swal.fire({
      title: 'Error',
      text: 'Password must be at least 6 characters long.',
      icon: 'error',
      confirmButtonColor: '#dc3545'
    });
    return;
  }
  
  // Show confirmation dialog
  Swal.fire({
    title: "Are you sure?",
    text: "Are you sure you want to reset this user's password?",
    showCancelButton: true,
    confirmButtonColor: "#0d6efd",
    cancelButtonColor: "#6c757d",
    confirmButtonText: "Yes, reset it!"
  }).then((result) => {
    if (result.isConfirmed) {
      // Close modal
      const modal = bootstrap.Modal.getInstance(document.getElementById('resetPasswordModal'));
      if (modal) modal.hide();
      
      // Submit the form
      submitForm(userId, userType, 'reset_password', newPassword);
    }
  });
}

// Helper function to submit forms
function submitForm(userId, userType, action, newPassword = null) {
  const form = document.createElement('form');
  form.method = 'POST';
  form.style.display = 'none';
  
  const userIdInput = document.createElement('input');
  userIdInput.type = 'hidden';
  userIdInput.name = 'user_id';
  userIdInput.value = userId;
  
  const userTypeInput = document.createElement('input');
  userTypeInput.type = 'hidden';
  userTypeInput.name = 'user_type';
  userTypeInput.value = userType;
  
  const actionInput = document.createElement('input');
  actionInput.type = 'hidden';
  actionInput.name = 'action';
  actionInput.value = action;
  
  form.appendChild(userIdInput);
  form.appendChild(userTypeInput);
  form.appendChild(actionInput);
  
  // Add password if resetting password
  if (newPassword !== null) {
    const passwordInput = document.createElement('input');
    passwordInput.type = 'hidden';
    passwordInput.name = 'new_password';
    passwordInput.value = newPassword;
    form.appendChild(passwordInput);
  }
  
  document.body.appendChild(form);
  form.submit();
}

document.addEventListener('DOMContentLoaded', function() {
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.forEach(function (tooltipTriggerEl) {
    new bootstrap.Tooltip(tooltipTriggerEl);
  });
  
  // Simple modal initialization
  const addUserModal = document.getElementById('addUserModal');
  if (addUserModal) {
    addUserModal.addEventListener('shown.bs.modal', function () {
      // Focus on first input field
      const firstInput = addUserModal.querySelector('input, select');
      if (firstInput) {
        firstInput.focus();
      }
      
      // Remove any backdrop
      const backdrop = document.querySelector('.modal-backdrop');
      if (backdrop) {
        backdrop.style.display = 'none';
        backdrop.style.opacity = '0';
      }
    });
  }
  
  // Override modal opening to prevent backdrop
  const modalTrigger = document.querySelector('[data-bs-target="#addUserModal"]');
  if (modalTrigger) {
    modalTrigger.addEventListener('click', function(e) {
      // Remove backdrop immediately
      setTimeout(function() {
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
          backdrop.style.display = 'none';
          backdrop.style.opacity = '0';
        }
      }, 100);
    });
  }
  
  // Handle form submission
  const addUserForm = document.getElementById('addUserForm');
  if (addUserForm) {
    addUserForm.addEventListener('submit', function(e) {
      console.log('Form submitted');
    });
  }
});
</script>

<style>
.users-fixed-panel {
  position: fixed;
  left: 280px;
  top: 60px;
  width: 1270px;
  max-width: 99vw;
  z-index: 100; /* Lowered to avoid covering modal */
  background: #f8f9fa;
  overflow-y: auto;
  overflow-x: auto;
  height: calc(100vh - 80px);
  bottom: 0;
  border-radius: 12px;
}
@media (max-width: 992px) {
  .users-fixed-panel {
    left: 0;
    width: 98vw;
    max-width: 98vw;
  }
}
/* Remove modal backdrop completely */
.modal-backdrop { 
  display: none !important;
  opacity: 0 !important;
  pointer-events: none !important;
}

.modal { 
  z-index: 2100 !important; 
  pointer-events: auto !important;
}

/* Ensure modal is fully interactive */
.modal-dialog {
  z-index: 2101 !important;
  pointer-events: auto !important;
  position: relative;
}

.modal-content {
  pointer-events: auto !important;
  position: relative;
  z-index: 2102 !important;
}

.modal input,
.modal select,
.modal textarea,
.modal button {
  pointer-events: auto !important;
  position: relative;
  z-index: 2103 !important;
}

/* Override any conflicting styles */
.users-fixed-panel {
  z-index: 100 !important;
}

/* Make all action buttons the same width */
.action-btn {
  width: 120px !important;
  height: 40px !important;
  padding: 8px 12px !important;
  text-align: center !important;
  white-space: nowrap !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
  margin-bottom: 2px !important;
  margin-right: 2px !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  line-height: 1 !important;
  font-size: 0.85rem !important;
}

/* Make action cell container wider */
.d-flex.flex-wrap.gap-1.align-items-center {
  flex-wrap: wrap;
  max-width: 260px;
}

/* Make Action column wider */
th[style*="min-width:220px"] {
  min-width: 260px !important;
}

/* Ensure modal is above everything and bright */
#addUserModal {
  z-index: 9999 !important;
  background: transparent !important;
}

#addUserModal .modal-dialog {
  z-index: 10000 !important;
  box-shadow: 0 10px 30px rgba(0,0,0,0.3) !important;
}

#addUserModal .modal-content {
  z-index: 10001 !important;
  background: white !important;
  border: 2px solid #007bff !important;
  border-radius: 10px !important;
}

/* Make modal header bright */
#addUserModal .modal-header {
  background: #f8f9fa !important;
  border-bottom: 1px solid #dee2e6 !important;
}

/* Make modal body bright */
#addUserModal .modal-body {
  background: white !important;
  padding: 20px !important;
}

/* Make modal footer bright */
#addUserModal .modal-footer {
  background: #f8f9fa !important;
  border-top: 1px solid #dee2e6 !important;
}

/* Reset Password Modal Styles */
.password-input-group {
  position: relative;
  display: flex;
  align-items: center;
}

.password-input-group input {
  flex: 1;
  padding-right: 40px;
}

.password-toggle-btn {
  position: absolute;
  right: 10px;
  background: none;
  border: none;
  cursor: pointer;
  color: #666;
  font-size: 18px;
  z-index: 10;
  transition: color 0.2s ease;
}

.password-toggle-btn:hover {
  color: #28bf4b;
}

.password-strength-meter {
  height: 5px;
  background-color: #e9ecef;
  border-radius: 3px;
  margin-top: 8px;
  overflow: hidden;
}

.password-strength-bar {
  height: 100%;
  width: 0%;
  border-radius: 3px;
  transition: width 0.3s ease, background-color 0.3s ease;
}

.password-strength-text {
  font-size: 12px;
  margin-top: 5px;
  font-weight: 500;
}

.strength-weak {
  background-color: #dc3545;
  color: #dc3545;
}

.strength-fair {
  background-color: #ffc107;
  color: #ffc107;
}

.strength-good {
  background-color: #17a2b8;
  color: #17a2b8;
}

.strength-strong {
  background-color: #28bf4b;
  color: #28bf4b;
}
</style>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="resetPasswordModalLabel"><i class="fas fa-key me-2"></i>Reset User Password</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <label for="resetPasswordNewPassword" class="form-label mb-0">New Password</label>
            <button type="button" class="btn btn-sm btn-outline-success" onclick="regeneratePassword()" title="Generate a new password">
              <i class="fas fa-sync-alt me-1"></i>Generate
            </button>
          </div>
          <div class="password-input-group">
            <input type="password" class="form-control" id="resetPasswordNewPassword" placeholder="Enter new password" required oninput="checkPasswordStrength()">
            <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('resetPasswordNewPassword')" title="Show/Hide Password">
              <i class="fas fa-eye"></i>
            </button>
          </div>
          <div class="password-strength-meter">
            <div class="password-strength-bar" id="passwordStrengthBar"></div>
          </div>
          <div class="password-strength-text" id="passwordStrengthText"></div>
          <small class="text-muted d-block mt-2">Password is auto-generated. Click Generate to create a new one.</small>
        </div>
        <div class="mb-3">
          <label for="resetPasswordConfirmPassword" class="form-label">Confirm Password</label>
          <div class="password-input-group">
            <input type="password" class="form-control" id="resetPasswordConfirmPassword" placeholder="Confirm new password" required>
            <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('resetPasswordConfirmPassword')" title="Show/Hide Password">
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>
        <input type="hidden" id="resetPasswordUserId" value="">
        <input type="hidden" id="resetPasswordUserType" value="">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="submitResetPassword()">Reset</button>
      </div>
    </div>
  </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" id="addUserForm">
        <div class="modal-header">
          <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="addUserType" class="form-label">User Type</label>
            <select class="form-select" id="addUserType" name="add_user_type" required>
              <option value="buyer">Buyer</option>
              <option value="seller">Seller</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="addFullName" class="form-label">Full Name</label>
            <input type="text" class="form-control" id="addFullName" name="add_fullname" required>
          </div>
          <div class="mb-3">
            <label for="addUsername" class="form-label">Username</label>
            <input type="text" class="form-control" id="addUsername" name="add_username" required>
          </div>
          <div class="mb-3">
            <label for="addEmail" class="form-label">Email</label>
            <input type="email" class="form-control" id="addEmail" name="add_email" required>
          </div>
          <div class="mb-3">
            <label for="addPassword" class="form-label">Password</label>
            <input type="password" class="form-control" id="addPassword" name="add_password" required>
          </div>
          <div class="mb-3">
            <label for="addPhone" class="form-label">Phone Number</label>
            <input type="text" class="form-control" id="addPhone" name="add_phone_number">
          </div>
          <div class="mb-3">
            <label for="addAddress" class="form-label">Address</label>
            <input type="text" class="form-control" id="addAddress" name="add_address">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Add User</button>
        </div>
        <input type="hidden" name="action" value="add_user">
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
