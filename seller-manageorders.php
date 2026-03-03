<?php
// Function to generate auto tracking number
function generateTrackingNumber($order_item_id) {
  $prefix = 'LBC';
  $date = date('md'); // mmdd format (e.g., 0923 for September 23)
  $sequence = str_pad($order_item_id, 6, '0', STR_PAD_LEFT); // 6-digit order item ID
  return $prefix . $date . $sequence;
}

// Handle photo proof upload for sellers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['seller_proof_photo'])) {
  require_once 'config/session_check.php';
  require_once 'config/database.php';
  
  if (!isSeller()) {
    http_response_code(403);
    exit('Not authorized');
  }
  
  $order_item_id = $_POST['order_item_id'] ?? null;
  $upload_dir = 'uploads/seller-proofs/';
  
  if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
  }
  
  // Verify that this order_item belongs to this seller
  if ($order_item_id) {
    $stmt = $pdo->prepare('SELECT oi.order_item_id FROM Order_Items oi JOIN Products p ON oi.product_id = p.product_id WHERE oi.order_item_id = ? AND p.seller_id = ?');
    $stmt->execute([$order_item_id, getCurrentUserId()]);
    
    if ($stmt->fetch()) {
      $file = $_FILES['seller_proof_photo'];
      $filename = time() . '_' . basename($file['name']);
      $target_path = $upload_dir . $filename;
      $upload_ok = false;
      $error_msg = '';
      
      if ($file['error'] === UPLOAD_ERR_OK) {
        $file_type = mime_content_type($file['tmp_name']);
        if (strpos($file_type, 'image') === 0) {
          if (move_uploaded_file($file['tmp_name'], $target_path)) {
            $upload_ok = true;
          } else {
            $error_msg = 'Failed to move uploaded file.';
          }
        } else {
          $error_msg = 'Invalid file type.';
        }
      } else {
        $error_msg = 'File upload error.';
      }
      
      if ($upload_ok) {
        try {
          $update = $pdo->prepare('UPDATE Order_Items SET proof_photo = ? WHERE order_item_id = ?');
          $update->execute([$target_path, $order_item_id]);
          http_response_code(200);
          echo json_encode(['success' => true, 'message' => 'Proof uploaded successfully', 'proof_path' => $target_path]);
          exit;
        } catch (PDOException $e) {
          http_response_code(500);
          echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
          exit;
        }
      } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Upload failed: ' . $error_msg]);
        exit;
      }
    } else {
      http_response_code(403);
      echo json_encode(['success' => false, 'message' => 'Order item not found or access denied']);
      exit;
    }
  }
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Missing order item ID']);
  exit;
}

// --- AJAX handler for order status/tracking updates (MUST BE FIRST) ---
if (
    isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' &&
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {
    // Enable debugging - uncomment to see what's being sent
    file_put_contents('debug.log', date('Y-m-d H:i:s') . ' - Request: ' . file_get_contents('php://input') . "\n", FILE_APPEND);
    
    require_once 'config/session_check.php';
    require_once 'config/database.php';
    
    // Check if user is a seller
    if (!isSeller()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Not authorized']);
        exit();
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $response = ['success' => false, 'message' => 'Invalid request'];
    
    if (isset($input['order_item_id'])) {
        $order_item_id = intval($input['order_item_id']);
        // Check if this order_item belongs to a product sold by this seller
        $stmt = $pdo->prepare('
            SELECT oi.order_item_id
            FROM Order_Items oi
            JOIN Products p ON oi.product_id = p.product_id
            WHERE oi.order_item_id = ? AND p.seller_id = ?
            LIMIT 1
        ');
        $stmt->execute([$order_item_id, getCurrentUserId()]);
        
        if ($stmt->fetch()) {
            // Update status (and optionally tracking number)
            if (isset($input['status'])) {
              $status = $input['status'];
              $valid_statuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
              if (!in_array($status, $valid_statuses)) {
                $response = ['success' => false, 'message' => 'Invalid status'];
              } else {
                $tracking_number = isset($input['tracking_number']) ? trim($input['tracking_number']) : null;
                
                // Auto-generate tracking number if status is 'shipped' and no tracking number provided
                if ($status === 'shipped' && empty($tracking_number)) {
                  $tracking_number = generateTrackingNumber($order_item_id);
                }
                
                try {
                  $stmt2 = $pdo->prepare('UPDATE Order_Items SET status = ?, tracking_number = ? WHERE order_item_id = ?');
                  $stmt2->execute([$status, $tracking_number, $order_item_id]);

                  // Award EcoCoins to seller when order is delivered
                  if ($status === 'delivered') {
                    // Get product price, quantity, and seller_id
                    $stmt_ecocoin = $pdo->prepare('
                      SELECT oi.quantity, oi.price, p.seller_id, oi.product_id, p.name as product_name, o.order_id
                      FROM Order_Items oi
                      JOIN Products p ON oi.product_id = p.product_id
                      JOIN Orders o ON oi.order_id = o.order_id
                      WHERE oi.order_item_id = ?
                      LIMIT 1
                    ');
                    $stmt_ecocoin->execute([$order_item_id]);
                    $ecocoinData = $stmt_ecocoin->fetch();
                    
                    if ($ecocoinData) {
                      $seller_id = $ecocoinData['seller_id'];
                      $quantity = $ecocoinData['quantity'];
                      $price = $ecocoinData['price'];
                      $total_amount = $price * $quantity;
                      $order_id = $ecocoinData['order_id'];
                      $product_id = $ecocoinData['product_id'];
                      $product_name = $ecocoinData['product_name'];
                      // Award EcoCoins (1 EcoCoin per 100 pesos spent)
                      $ecocoins_awarded = round($total_amount / 100, 2);
                      
                      // Update seller's ecocoins balance
                      $stmt_update = $pdo->prepare('UPDATE Sellers SET ecocoins_balance = ecocoins_balance + ? WHERE seller_id = ?');
                      $stmt_update->execute([$ecocoins_awarded, $seller_id]);
                      
                      // Try to log transaction (optional, with error handling)
                      try {
                        $stmt_log = $pdo->prepare('INSERT INTO ecocoins_transactions (user_id, user_type, amount, transaction_type, description, order_id) VALUES (?, ?, ?, ?, ?, ?)');
                        $stmt_log->execute([$seller_id, 'seller', $ecocoins_awarded, 'earn', "Sale completed for Order #{$order_id}", $order_id]);
                      } catch (Exception $e) {
                        // Fallback if order_id column doesn't exist
                        $stmt_log = $pdo->prepare('INSERT INTO ecocoins_transactions (user_id, user_type, amount, transaction_type, description) VALUES (?, ?, ?, ?, ?)');
                        $stmt_log->execute([$seller_id, 'seller', $ecocoins_awarded, 'earn', "Sale completed for Order"]);
                      }
                      
                      // Insert notification for seller about ecocoins earned
                      try {
                        $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
                          id INT AUTO_INCREMENT PRIMARY KEY,
                          user_id INT NOT NULL,
                          user_type VARCHAR(20) NOT NULL DEFAULT 'buyer',
                          order_id INT,
                          product_id INT,
                          product_name VARCHAR(255),
                          status VARCHAR(20),
                          type VARCHAR(20) DEFAULT 'order',
                          amount DECIMAL(10,2),
                          created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                        
                        $stmt_notif = $pdo->prepare('INSERT INTO notifications (user_id, user_type, order_id, product_id, product_name, status, type, amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                        $stmt_notif->execute([$seller_id, 'seller', $order_id, $product_id, $product_name, 'ecocoin', 'ecocoin', $ecocoins_awarded]);
                      } catch (Exception $e) {
                        // Notification insertion failed, but don't stop the process
                      }
                    }
                  }

                  // Insert notification for buyer if status is confirmed, shipped, delivered, or cancelled
                  if (in_array($status, ['confirmed', 'shipped', 'delivered', 'cancelled'])) {
                    // Get order and buyer info
                    $stmt3 = $pdo->prepare('SELECT o.order_id, o.buyer_id, oi.product_id, p.name as product_name FROM Order_Items oi JOIN Orders o ON oi.order_id = o.order_id JOIN Products p ON oi.product_id = p.product_id WHERE oi.order_item_id = ? LIMIT 1');
                    $stmt3->execute([$order_item_id]);
                    $orderInfo = $stmt3->fetch();
                    if ($orderInfo) {
                      $buyer_id = $orderInfo['buyer_id'];
                      $order_id = $orderInfo['order_id'];
                      $product_id = $orderInfo['product_id'];
                      $product_name = $orderInfo['product_name'];
                      // Insert notification into a notifications table (create if not exists)
                      $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        user_type VARCHAR(20) NOT NULL DEFAULT 'buyer',
                        order_id INT,
                        product_id INT,
                        product_name VARCHAR(255),
                        status VARCHAR(20),
                        type VARCHAR(20) DEFAULT 'order',
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                      $stmt4 = $pdo->prepare('INSERT INTO notifications (user_id, user_type, order_id, product_id, product_name, status, type) VALUES (?, ?, ?, ?, ?, ?, ?)');
                      $stmt4->execute([$buyer_id, 'buyer', $order_id, $product_id, $product_name, $status, 'order']);
                    }
                  }

                  $response = ['success' => true, 'message' => 'Order item status updated successfully', 'tracking_number' => $tracking_number];
                } catch (PDOException $e) {
                  $response = ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
                }
              }
            }
            // Update tracking number only
            elseif (isset($input['tracking_number'])) {
                $tracking_number = trim($input['tracking_number']);
                try {
                    $stmt2 = $pdo->prepare('UPDATE Order_Items SET tracking_number = ? WHERE order_item_id = ?');
                    $stmt2->execute([$tracking_number, $order_item_id]);
                    $response = ['success' => true, 'message' => 'Tracking number updated successfully'];
                } catch (PDOException $e) {
                    $response = ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
                }
            }
        } else {
            $response = ['success' => false, 'message' => 'Order item not found or access denied'];
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Include session check for sellers
require_once 'config/session_check.php';

// Check if user is a seller
if (!isSeller()) {
    header("Location: seller-manageorders.php");
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

// Fetch orders for the current seller
try {
  // Ensure order_views table exists (used to track which orders seller has already viewed)
  try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS order_views (
      id INT AUTO_INCREMENT PRIMARY KEY,
      order_id INT NOT NULL,
      seller_id INT NOT NULL,
      viewed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY ux_order_seller (order_id, seller_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  } catch (PDOException $e) {
    // ignore creation errors; functionality will degrade to default behaviour
  }

  // If the page is opened with ?filter=unread then show only pending orders that the seller hasn't viewed yet
  $showUnreadOnly = (isset($_GET['filter']) && $_GET['filter'] === 'unread');

  if ($showUnreadOnly) {
    // Show only orders that have at least one order item in 'pending' status and haven't been viewed by this seller
    $query = "
    SELECT 
      o.order_id,
      o.created_at,
      o.total_amount,
      o.shipping_address,
      o.payment_method,
      b.fullname as buyer_name,
      b.email as buyer_email,
      b.phone_number as buyer_phone,
      p.name as product_name,
      p.image_url,
      oi.quantity,
      oi.price as item_price,
      oi.payment_receipt,
      oi.order_item_id,
      oi.status as item_status,
      oi.tracking_number as item_tracking_number,
      oi.proof_photo
    FROM Orders o
    JOIN Order_Items oi ON o.order_id = oi.order_id
    JOIN Products p ON oi.product_id = p.product_id
    JOIN Buyers b ON o.buyer_id = b.buyer_id
    WHERE p.seller_id = ?
    AND oi.status = 'pending'
    AND NOT EXISTS (SELECT 1 FROM order_views ov WHERE ov.order_id = o.order_id AND ov.seller_id = ?)
    ORDER BY o.created_at DESC
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute([getCurrentUserId(), getCurrentUserId()]);
    $orders = $stmt->fetchAll();

    // Mark these orders as viewed by this seller so they won't show as unread next time
    if (!empty($orders)) {
      // collect distinct order ids
      $orderIds = [];
      foreach ($orders as $r) {
        $orderIds[$r['order_id']] = true;
      }
      $insertStmt = $pdo->prepare("INSERT IGNORE INTO order_views (order_id, seller_id, viewed_at) VALUES (?, ?, NOW())");
      foreach (array_keys($orderIds) as $oid) {
        try {
          $insertStmt->execute([$oid, getCurrentUserId()]);
        } catch (PDOException $e) {
          // ignore individual insert failures
        }
      }
    }
  } else {
    $query = "
    SELECT 
      o.order_id,
      o.created_at,
      o.total_amount,
      o.shipping_address,
      o.payment_method,
      b.fullname as buyer_name,
      b.email as buyer_email,
      b.phone_number as buyer_phone,
      p.name as product_name,
      p.image_url,
      oi.quantity,
      oi.price as item_price,
      oi.payment_receipt,
      oi.order_item_id,
      oi.status as item_status,
      oi.tracking_number as item_tracking_number,
      oi.proof_photo
    FROM Orders o
    JOIN Order_Items oi ON o.order_id = oi.order_id
    JOIN Products p ON oi.product_id = p.product_id
    JOIN Buyers b ON o.buyer_id = b.buyer_id
    WHERE p.seller_id = ?
    AND oi.status NOT IN ('cancelled')
    ORDER BY o.created_at DESC
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute([getCurrentUserId()]);
    $orders = $stmt->fetchAll();
  }
} catch (PDOException $e) {
    error_log("Error fetching orders: " . $e->getMessage());
    $orders = [];
}

include 'sellerheader.php';
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Manage Orders - Ecocycle</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="format-detection" content="telephone=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="author" content="">
    <meta name="keywords" content="">
    <meta name="description" content="">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="css/vendor.css">
    <link rel="stylesheet" type="text/css" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Open+Sans:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .swal2-popup,
        .swal2-title,
        .swal2-html-container,
        .swal2-confirm,
        .swal2-cancel {
            font-family: 'Poppins', sans-serif !important;
        }
    </style>
  </head>
  <body>
    <div class="container-fluid">
      <div class="row">
        <div class="col-12 main-content">
          <div class="container-lg mt-3">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <h3 class="fw-bold mb-0">Manage Orders</h3>
            </div>
            <!-- Search and Filters -->
            <div class="search-filter-row mb-3" style="display: flex; gap: 1rem; flex-wrap: wrap;">
              <div class="input-group" style="max-width: 250px;">
                <input type="text" id="searchInput" class="form-control" placeholder="Search by customer or product...">
                <button id="searchBtn" class="btn" type="button" style="background-color: #198754; border-color: #198754;">
                  <i class="fas fa-search" style="color: #fff;"></i>
                </button>
              </div>
              <select id="filterStatus" class="form-select" style="max-width: 200px;">
                <option value="">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="confirmed">Confirmed</option>
                <option value="shipped">Shipped</option>
                <option value="delivered">Delivered</option>
                <option value="cancelled">Cancelled</option>
              </select>
              <button type="reset" class="btn btn-outline-secondary" id="resetBtn">Reset</button>
            </div>
            <!-- Orders Table -->
            <div class="table-responsive">
              <table class="table table-bordered align-middle">
                <thead class="table-success">
                  <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Product</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Track Number</th>
                     <th>Payment Method</th>
                    <th>Photo Proof</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($orders)): ?>
                    <tr>
                      <td colspan="9" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-3"></i>
                        <p>No orders found</p>
                        <small>Start selling to see your orders here</small>
                      </td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                      <tr data-order-item-id="<?php echo $order['order_item_id']; ?>" data-order-id="<?php echo $order['order_id']; ?>">
                        <td><strong>#<?php echo htmlspecialchars($order['order_id']); ?></strong></td>
                        <td>
                          <div>
                            <strong><?php echo htmlspecialchars($order['buyer_name']); ?></strong><br>
                            <small class="text-muted"><?php echo htmlspecialchars($order['buyer_email']); ?></small>
                          </div>
                        </td>
                        <td>
                          <div class="d-flex align-items-center">
                            <?php if ($order['image_url']): ?>
                            <img src="<?php echo htmlspecialchars($order['image_url']); ?>" 
                                   alt="<?php echo htmlspecialchars($order['product_name']); ?>" 
                                   class="me-2" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                            <?php endif; ?>
                            <span><?php echo htmlspecialchars($order['product_name']); ?></span>
                          </div>
                        </td>
                        <td>₱<?php echo number_format($order['item_price'] * $order['quantity'], 2); ?></td>
                        <td class="status-cell">
                          <span class="badge <?php 
                            switch($order['item_status']) {
                              case 'delivered': echo 'bg-success'; break;
                              case 'shipped': echo 'bg-info text-dark'; break;
                              case 'confirmed': echo 'bg-warning text-dark'; break;
                              case 'pending': echo 'bg-secondary'; break;
                              case 'cancelled': echo 'bg-danger'; break;
                              default: echo 'bg-secondary';
                            }
                          ?>">
                            <?php echo ucfirst(htmlspecialchars($order['item_status'])); ?>
                          </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                        <td class="track-number-cell">
                          <?php if ($order['item_tracking_number']): ?>
                            <?php echo htmlspecialchars($order['item_tracking_number']); ?>
                          <?php endif; ?>
                        </td>
                         <td>
                           <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                             <div style="display:flex; align-items:center; gap:8px;">
                               <?php 
                                 $paymentMethod = strtolower($order['payment_method']);
                                 $badgeClass = '';
                                 $icon = '';
                                 $displayText = '';
                                 
                                 switch($paymentMethod) {
                                   case 'cod':
                                     $badgeClass = 'badge bg-warning text-dark';
                                     $icon = 'fas fa-money-bill-wave';
                                     $displayText = 'Cash on Delivery';
                                     break;
                                   case 'gcash':
                                     $badgeClass = 'badge bg-info text-dark';
                                     $icon = 'fas fa-mobile-alt';
                                     $displayText = 'GCash';
                                     break;
                                   case 'ecocoins':
                                     $badgeClass = 'badge bg-success';
                                     $icon = 'fas fa-leaf';
                                     $displayText = 'EcoCoins';
                                     break;
                                   default:
                                     $badgeClass = 'badge bg-secondary';
                                     $icon = 'fas fa-question-circle';
                                     $displayText = ucfirst(htmlspecialchars($order['payment_method']));
                                 }
                               ?>
                               <span class="<?php echo $badgeClass; ?>">
                                 <i class="<?php echo $icon; ?> me-1"></i><?php echo $displayText; ?>
                               </span>
                             </div>
                             <div style="display:flex; align-items:center;">
                               <?php if (strtolower($order['payment_method']) === 'gcash') : ?>
                                 <?php if (!empty($order['payment_receipt'])): ?>
                                   <?php $receiptPath = htmlspecialchars($order['payment_receipt']); ?>
                                   <a href="#" class="view-receipt-link btn btn-sm btn-outline-primary" data-receipt="<?php echo $receiptPath; ?>" title="View receipt" aria-label="View receipt">
                                     <i class="fas fa-file-image"></i>
                                   </a>
                                 <?php else: ?>
                                   <small class="text-muted">No receipt</small>
                                 <?php endif; ?>
                               <?php endif; ?>
                             </div>
                           </div>
                         </td>
                        <td>
                          <?php if (!empty($order['proof_photo'])): ?>
                            <a href="#" class="view-proof-link btn btn-sm btn-outline-primary" data-proof="<?php echo htmlspecialchars($order['proof_photo']); ?>" title="View photo proof" aria-label="View photo proof">
                              <i class="fas fa-image"></i> View
                            </a>
                          <?php else: ?>
                            <?php if ($order['item_status'] === 'delivered'): ?>
                              <button class="btn btn-sm btn-outline-success upload-proof-btn" data-order-item-id="<?php echo htmlspecialchars($order['order_item_id']); ?>" title="Upload proof of delivery">
                                <i class="fas fa-upload"></i> Upload
                              </button>
                            <?php else: ?>
                              <small class="text-muted">-</small>
                            <?php endif; ?>
                          <?php endif; ?>
                        </td>
                        <td>
                          <?php if ($order['item_status'] != 'delivered' && $order['item_status'] != 'cancelled'): ?>
                          <button class="btn btn-sm btn-outline-primary edit-status-btn" title="Edit">
                              <i class="fas fa-edit"></i>
                            </button>
                          <?php else: ?>
                            <button class="btn btn-sm btn-outline-secondary edit-status-btn" title="Edit" disabled>
                              <i class="fas fa-edit"></i>
                            </button>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- LBC Track Number Modal -->
    <div class="modal fade" id="trackNumberModal" tabindex="-1" aria-labelledby="trackNumberModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="trackNumberModalLabel">Enter LBC Track Number</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id="trackNumberForm">
              <div class="mb-3">
                <label for="trackNumberInput" class="form-label">LBC Track Number</label>
                <input type="text" class="form-control" id="trackNumberInput" required>
                <input type="hidden" id="currentOrderItemId">
              </div>
              <button type="submit" class="btn btn-success">Save</button>
            </form>
          </div>
        </div>
      </div>
    </div>
    <!-- Edit Status Modal -->
    <div class="modal fade" id="editStatusModal" tabindex="-1" aria-labelledby="editStatusModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="editStatusModalLabel">Edit Order Status</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id="editStatusForm">
              <input type="hidden" id="editStatusOrderItemId">
              <div class="mb-3">
                <label class="form-label">Select Status</label>
                <div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="orderStatus" id="statusPending" value="pending">
                    <label class="form-check-label" for="statusPending">Pending</label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="orderStatus" id="statusConfirmed" value="confirmed">
                    <label class="form-check-label" for="statusConfirmed">Confirmed</label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="orderStatus" id="statusShipped" value="shipped">
                    <label class="form-check-label" for="statusShipped">Shipped</label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="orderStatus" id="statusDelivered" value="delivered">
                    <label class="form-check-label" for="statusDelivered">Delivered</label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="orderStatus" id="statusCancelled" value="cancelled">
                    <label class="form-check-label" for="statusCancelled">Cancelled</label>
                  </div>
                </div>
              </div>
              <div class="mb-3">
                <label for="editTrackNumberInput" class="form-label">LBC Track Number <span class="text-muted">(Auto-generated when shipped)</span></label>
                <input type="text" class="form-control" id="editTrackNumberInput">
                <small class="text-muted" id="trackingNote" style="display:none;">This tracking number will be auto-generated when status is "Shipped"</small>
              </div>
              <button type="submit" class="btn btn-success">Save</button>
            </form>
          </div>
        </div>
      </div>
    </div>
      <!-- View Receipt Modal -->
      <div class="modal fade" id="viewReceiptModal" tabindex="-1" aria-labelledby="viewReceiptModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="viewReceiptModalLabel">Payment Receipt</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
              <img id="viewReceiptImg" src="" alt="Receipt" style="max-width:200px; width:100%; height:auto; object-fit:contain;" />
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
          </div>
        </div>
      </div>
      <!-- View Photo Proof Modal -->
      <div class="modal fade" id="viewProofModal" tabindex="-1" aria-labelledby="viewProofModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="viewProofModalLabel">Photo Proof</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
              <img id="viewProofImg" src="" alt="Photo Proof" style="max-width:100%; width:100%; height:auto; object-fit:contain;" />
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Upload Proof Modal -->
      <div class="modal fade" id="uploadProofModal" tabindex="-1" aria-labelledby="uploadProofLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <form id="uploadProofForm" enctype="multipart/form-data" method="post">
              <div class="modal-header">
                <h5 class="modal-title" id="uploadProofLabel">Upload Proof of Delivery</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <input type="hidden" name="order_item_id" id="proofOrderItemId">
                <div class="mb-3">
                  <label for="sellerProofPhoto" class="form-label">Proof Photo</label>
                  <input class="form-control" type="file" id="sellerProofPhoto" name="seller_proof_photo" accept="image/*" required>
                  <small class="text-muted">Upload a photo proving the order was delivered</small>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success">Upload</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/js/all.min.js"></script>
    <script>
      // Search and filter logic for table rows (robust: handles placeholder rows and case-insensitive status)
      document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const filterStatus = document.getElementById('filterStatus');
        const searchBtn = document.getElementById('searchBtn');
        const resetBtn = document.getElementById('resetBtn');
        const table = document.querySelector('table');
        const rows = table.querySelectorAll('tbody tr');

        function textFromCell(row, idx) {
          if (!row || !row.cells || !row.cells[idx]) return '';
          return row.cells[idx].innerText.toLowerCase().replace(/\s+/g, ' ').trim();
        }

        function filterTable() {
          const searchValue = (searchInput.value || '').toLowerCase().trim();
          const status = (filterStatus.value || '').toLowerCase().trim();

          rows.forEach(row => {
            // Skip rows that don't have the expected number of cells (e.g., the "No orders found" message row)
            if (!row.cells || row.cells.length < 5) {
              // If showing all, ensure placeholder stays visible; otherwise hide it
              row.style.display = (searchValue === '' && status === '') ? '' : 'none';
              return;
            }

            const customer = textFromCell(row, 1);
            const product = textFromCell(row, 2);
            const stat = textFromCell(row, 4); // already lowercased

            let show = true;
            if (searchValue) {
              if (!(customer.includes(searchValue) || product.includes(searchValue))) show = false;
            }
            if (status) {
              // stat may include extra whitespace or the word from the badge; compare lowercase
              if (stat !== status) show = false;
            }

            row.style.display = show ? '' : 'none';
          });
        }

        // Enter key support for quick searching
        searchInput.addEventListener('keydown', function(e) {
          if (e.key === 'Enter') {
            e.preventDefault();
            filterTable();
          }
        });

        searchBtn.addEventListener('click', filterTable);
        filterStatus.addEventListener('change', filterTable);
        resetBtn.addEventListener('click', function() {
          searchInput.value = '';
          filterStatus.value = '';
          filterTable();
        });

        // Initial filter pass in case server rendered values are present
        filterTable();
      });

      // LBC Track Number logic
      let currentOrderItemId = null;
      document.addEventListener('click', function(e) {
        if (e.target.classList.contains('track-btn')) {
          const row = e.target.closest('tr');
          currentOrderItemId = row.getAttribute('data-order-item-id');
          document.getElementById('currentOrderItemId').value = currentOrderItemId;
          
          // Get current track number from the cell
          const trackCell = row.querySelector('.track-number-cell');
          let currentTrackNumber = '';
          if (trackCell.textContent.trim() !== 'Add' && trackCell.textContent.trim() !== 'Edit') {
            currentTrackNumber = trackCell.textContent.replace('Edit', '').trim();
          }
          
          document.getElementById('trackNumberInput').value = currentTrackNumber;
          const modal = new bootstrap.Modal(document.getElementById('trackNumberModal'));
          modal.show();
        }
      });

      document.getElementById('trackNumberForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const orderItemId = document.getElementById('currentOrderItemId').value;
        const trackNum = document.getElementById('trackNumberInput').value.trim();
        
        if (orderItemId && trackNum) {
          // Update via AJAX
          fetch('seller-manageorders.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              order_item_id: orderItemId,
              tracking_number: trackNum
            })
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              // Update table cell
              const row = document.querySelector(`tr[data-order-item-id="${orderItemId}"]`);
              const cell = row.querySelector('.track-number-cell');
              cell.innerHTML = trackNum + ' <button class="btn btn-sm btn-outline-secondary track-btn ms-1">Edit</button>';
              // Show success message
              Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Track number updated successfully!'
              });
              
              // Hide modal
              bootstrap.Modal.getInstance(document.getElementById('trackNumberModal')).hide();
            } else {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error updating track number: ' + data.message
              });
            }
          })
          .catch(error => {
            console.error('Error updating track number. Please try again.');
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'Error updating track number. Please try again.'
            });
          });
        }
      });

      // Add event listeners to status radio buttons to enable/disable tracking input
      document.querySelectorAll('input[name="orderStatus"]').forEach(radio => {
        radio.addEventListener('change', function() {
          const trackingInput = document.getElementById('editTrackNumberInput');
          const trackingNote = document.getElementById('trackingNote');
          
          if (this.value === 'shipped') {
            // Auto-generated - make read-only
            trackingInput.disabled = true;
            trackingInput.placeholder = 'Auto-generated (LBC format)';
            trackingInput.value = '';
            trackingNote.style.display = 'block';
          } else {
            // Manual entry allowed
            trackingInput.disabled = false;
            trackingInput.placeholder = 'Enter tracking number (optional)';
            trackingNote.style.display = 'none';
          }
        });
      });
      
      // Status change logic
      const statusBadgeMap = {
        'pending': '<span class="badge bg-secondary">Pending</span>',
        'confirmed': '<span class="badge bg-warning text-dark">Confirmed</span>',
        'shipped': '<span class="badge bg-info text-dark">Shipped</span>',
        'delivered': '<span class="badge bg-success">Delivered</span>',
        'cancelled': '<span class="badge bg-danger">Cancelled</span>'
      };
      
      let currentEditOrderItemId = null;
      document.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-status-btn') || e.target.closest('.edit-status-btn')) {
          const btn = e.target.classList.contains('edit-status-btn') ? e.target : e.target.closest('.edit-status-btn');
          if (btn.disabled) return;
          
          const row = btn.closest('tr');
          currentEditOrderItemId = row.getAttribute('data-order-item-id');
          document.getElementById('editStatusOrderItemId').value = currentEditOrderItemId;
          
          // Set the current status in the modal
          const currentStatus = row.querySelector('.status-cell span').innerText.trim().toLowerCase();
          document.getElementById('statusPending').checked = currentStatus === 'pending';
          document.getElementById('statusConfirmed').checked = currentStatus === 'confirmed';
          document.getElementById('statusShipped').checked = currentStatus === 'shipped';
          document.getElementById('statusDelivered').checked = currentStatus === 'delivered';
          document.getElementById('statusCancelled').checked = currentStatus === 'cancelled';
          
          // Set the current track number in the modal
          const trackCell = row.querySelector('.track-number-cell');
          let trackValue = '';
          if (trackCell.querySelector('button')) {
            // If button exists, check if theres text before it
            const textContent = trackCell.textContent.trim();
            if (textContent !== 'Add' && textContent !== 'Edit') {             trackValue = textContent.replace('Edit', '').trim();
            }
          } else {
            trackValue = trackCell.textContent.trim();
          }
          document.getElementById('editTrackNumberInput').value = trackValue;
          
          // Hide or show tracking number field based on whether one already exists
          const trackNumberGroup = document.getElementById('editTrackNumberInput').closest('.mb-3');
          if (trackValue && trackValue !== '-') {
            // Tracking number already exists - hide the input field
            trackNumberGroup.style.display = 'none';
          } else {
            // No tracking number yet - show the input field
            trackNumberGroup.style.display = 'block';
          }
          
          const modal = new bootstrap.Modal(document.getElementById('editStatusModal'));
          modal.show();
        }
      });
      
      // Handle status changes to show/hide tracking number field
      document.querySelectorAll('input[name="orderStatus"]').forEach(radio => {
        radio.addEventListener('change', function() {
          const trackNumberGroup = document.getElementById('editTrackNumberInput').closest('.mb-3');
          const currentTrackNum = document.getElementById('editTrackNumberInput').value.trim();
          
          // If there's already a tracking number, always hide the field (it's already generated)
          if (currentTrackNum && currentTrackNum !== '-') {
            trackNumberGroup.style.display = 'none';
          } else {
            // If no tracking number yet, show the field for all statuses
            // (it will be auto-generated by backend for "shipped" status)
            trackNumberGroup.style.display = 'block';
          }
        });
      });

      document.getElementById('editStatusForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const orderItemId = document.getElementById('editStatusOrderItemId').value;
        const statusRadio = document.querySelector('input[name="orderStatus"]:checked');
        
        if (!statusRadio) {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Please select a status'
          });
          return;
        }
        
        const status = statusRadio.value;
        const trackNumInput = document.getElementById('editTrackNumberInput');
        let trackNum = trackNumInput.value.trim();
        
        // If status is shipped, disable editing - tracking is auto-generated
        if (status === 'shipped' && trackNum === '') {
          trackNum = ''; // Will be auto-generated on backend
        }
        
        console.log('Sending update:', { order_item_id: orderItemId, status: status, tracking_number: trackNum });
        
        // Update via AJAX
        fetch('seller-manageorders.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({
            order_item_id: orderItemId,
            status: status,
            tracking_number: trackNum
          })
        })
        .then(response => {
          console.log('Response status:', response.status);
          return response.json();
        })
        .then(data => {
          console.log('Response data:', data);
          if (data.success) {
            // Update badge
            const row = document.querySelector(`tr[data-order-item-id="${orderItemId}"]`);
            row.querySelector('.status-cell').innerHTML = statusBadgeMap[status];
            
            // Update track number cell - use auto-generated number if available
            const trackCell = row.querySelector('.track-number-cell');
            const displayTrackNum = data.tracking_number || trackNum;
            if (status === 'pending') {             trackCell.innerHTML = '<button class="btn btn-sm btn-outline-secondary track-btn">Add</button>';
            } else if (status === 'delivered' || status === 'cancelled') {             trackCell.innerHTML = displayTrackNum ? displayTrackNum : '-';
            } else {
              trackCell.innerHTML = (displayTrackNum ? displayTrackNum + ' ' : '') + '<button class="btn btn-sm btn-outline-secondary track-btn">' + (displayTrackNum ? 'Edit' : 'Add') + '</button>';
            }
            
            // Disable edit button if Completed or Cancelled
            const editBtn = row.querySelector('.edit-status-btn');
            if (status === 'delivered' || status === 'cancelled') {              editBtn.disabled = true;
              editBtn.classList.remove('btn-outline-primary');
              editBtn.classList.add('btn-outline-secondary');
            }
            
            // Show success message
            Swal.fire({
              icon: 'success',
              title: 'Success!',
              text: 'Order status updated successfully!'
            });
            
            // Hide modal
            bootstrap.Modal.getInstance(document.getElementById('editStatusModal')).hide();
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'Error updating order status: ' + data.message
            });
          }
        })
        .catch(error => {
          console.error('Error updating order status:', error);
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error updating order status. Please try again.'
          });
        });
      });
    </script>
    <script>
      // View receipt button handler: opens modal and sets image src
      document.addEventListener('click', function(e) {
        const link = e.target.closest('.view-receipt-link');
        if (!link) return;
        e.preventDefault();
        const receipt = link.getAttribute('data-receipt') || link.getAttribute('href');
        if (!receipt) return;
        const img = document.getElementById('viewReceiptImg');
        const downloadLink = document.getElementById('downloadReceiptLink');
        img.src = receipt;
        if (downloadLink) {
          downloadLink.href = receipt;
        }
        try {
          const modal = new bootstrap.Modal(document.getElementById('viewReceiptModal'));
          modal.show();
        } catch (err) {
          // fallback: open in new tab
          window.open(receipt, '_blank');
        }
      });
      
      // View photo proof button handler: opens modal and sets image src
      document.addEventListener('click', function(e) {
        const link = e.target.closest('.view-proof-link');
        if (!link) return;
        e.preventDefault();
        const proof = link.getAttribute('data-proof') || link.getAttribute('href');
        if (!proof) return;
        const img = document.getElementById('viewProofImg');
        img.src = proof;
          // Store the row to hide after modal closes
          window._lastProofRow = link.closest('tr');
        try {
          const modal = new bootstrap.Modal(document.getElementById('viewProofModal'));
          modal.show();
        } catch (err) {
          // fallback: open in new tab
          window.open(proof, '_blank');
        }
      });
      
      // Upload proof button handler
      document.addEventListener('click', function(e) {
        const btn = e.target.closest('.upload-proof-btn');
        if (!btn) return;
        e.preventDefault();
        const orderItemId = btn.getAttribute('data-order-item-id');
        document.getElementById('proofOrderItemId').value = orderItemId;
        document.getElementById('sellerProofPhoto').value = '';
        const modal = new bootstrap.Modal(document.getElementById('uploadProofModal'));
        modal.show();
      });
      
      // Handle proof upload form submission
      document.getElementById('uploadProofForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const orderItemId = document.getElementById('proofOrderItemId').value;
        
        Swal.fire({
          title: 'Uploading...',
          text: 'Please wait while your proof is being uploaded.',
          allowOutsideClick: false,
          allowEscapeKey: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });
        
        fetch('seller-manageorders.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            Swal.fire({
              title: 'Success!',
              text: 'Your proof has been uploaded successfully.',
              icon: 'success',
              confirmButtonColor: '#28bf4b'
            }).then(() => {
              location.reload();
            });
          } else {
            Swal.fire({
              title: 'Error',
              text: 'Upload failed: ' + (data.message || 'Unknown error'),
              icon: 'error'
            });
          }
        })
        .catch(error => {
          Swal.fire({
            title: 'Error',
            text: 'Network error. Please try again.',
            icon: 'error'
          });
        });
      });


    </script>
  </body>
</html>
