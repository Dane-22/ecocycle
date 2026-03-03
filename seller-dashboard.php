<?php 
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
    $stmt = $pdo->prepare("SELECT * FROM sellers WHERE seller_id = ?");
    $stmt->execute([getCurrentUserId()]);
    $seller = $stmt->fetch();
    
    if (!$seller) {
        header("Location: login.php");
        exit();
    }
    
    // Get seller's ecocoins balance
    $seller_ecocoins = (float)($seller['ecocoins_balance'] ?? 0);
} catch (PDOException $e) {
    header("Location: login.php");
    exit();
}

// Validate and prepare date filters
$start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';

if ($start_date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) {
    $start_date = '';
}

if ($end_date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
    $end_date = '';
}

if ($start_date && $end_date && $start_date > $end_date) {
    $temp = $start_date;
    $start_date = $end_date;
    $end_date = $temp;
}

// Get seller's statistics
try {
  // Date filter logic
  $date_filter = '';
  $params = [getCurrentUserId()];
  if (!empty($start_date)) {
    $date_filter .= ' AND o.created_at >= ?';
    $params[] = $start_date;
  }
  if (!empty($end_date)) {
    $date_filter .= ' AND o.created_at <= ?';
    $params[] = $end_date . ' 23:59:59';
  }
  // Get total orders and sales
  $stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT o.order_id) as total_orders, 
         SUM(oi.quantity * oi.price) as total_sales
    FROM orders o 
    JOIN Order_Items oi ON o.order_id = oi.order_id 
    JOIN products p ON oi.product_id = p.product_id 
    WHERE p.seller_id = ? AND o.status != 'cancelled' $date_filter
  ");
  $stmt->execute($params);
  $order_stats = $stmt->fetch();
  $total_orders = $order_stats['total_orders'] ?? 0;
  // Deduct 5% bard share from total sales
  $total_sales = ($order_stats['total_sales'] ?? 0) * 0.95;
    
    // Get total unique buyers
  $stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT o.buyer_id) as total_buyers
    FROM orders o 
    JOIN Order_Items oi ON o.order_id = oi.order_id 
    JOIN products p ON oi.product_id = p.product_id 
    WHERE p.seller_id = ? AND o.status != 'cancelled' $date_filter
  ");
  $stmt->execute($params);
  $total_buyers = $stmt->fetch()['total_buyers'] ?? 0;
    
    // Get total sold products (sum of quantities sold)
  $stmt = $pdo->prepare("
    SELECT SUM(oi.quantity) as sold_products
    FROM Order_Items oi
    JOIN products p ON oi.product_id = p.product_id
    JOIN orders o ON oi.order_id = o.order_id
    WHERE p.seller_id = ? AND o.status != 'cancelled' $date_filter
  ");
  $stmt->execute($params);
  $sold_products = $stmt->fetch()['sold_products'] ?? 0;
    
    // Calculate average rating (set to 0 since there is no customer service feedback yet)
    $average_rating = 0;
    
    // Get recent orders
  $stmt = $pdo->prepare("
    SELECT o.order_id, o.created_at, o.status, o.total_amount,
         b.fullname as buyer_name,
         p.name as product_name,
         oi.quantity, oi.price
    FROM orders o 
    JOIN Order_Items oi ON o.order_id = oi.order_id 
    JOIN products p ON oi.product_id = p.product_id 
    JOIN buyers b ON o.buyer_id = b.buyer_id
    WHERE p.seller_id = ? AND o.status != 'cancelled' $date_filter
    ORDER BY o.created_at DESC
    LIMIT 5
  ");
  $stmt->execute($params);
  $recent_orders = $stmt->fetchAll();
    
    // Get top selling products
    $stmt = $pdo->prepare("
        SELECT p.name, p.price, p.image_url, COUNT(oi.order_item_id) as sales_count
        FROM products p 
        LEFT JOIN Order_Items oi ON p.product_id = oi.product_id 
        LEFT JOIN orders o ON oi.order_id = o.order_id AND o.status != 'cancelled'
        WHERE p.seller_id = ? AND p.status = 'active'
        GROUP BY p.product_id
        ORDER BY sales_count DESC
        LIMIT 5
    ");
    $stmt->execute([getCurrentUserId()]);
    $top_products = $stmt->fetchAll();
    
    // Get product verification statistics
    $stmt = $pdo->prepare("
        SELECT status, COUNT(*) as count
        FROM products 
        WHERE seller_id = ?
        GROUP BY status
    ");
    $stmt->execute([getCurrentUserId()]);
    $product_status_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $pending_products = $product_status_counts['pending'] ?? 0;
    $active_products = $product_status_counts['active'] ?? 0;
    $rejected_products = $product_status_counts['rejected'] ?? 0;
    $inactive_products = $product_status_counts['inactive'] ?? 0;
    $total_products = $pending_products + $active_products + $rejected_products + $inactive_products;
    
    // Get monthly sales for the last 12 months

  $monthly_sales = [];
  $months = [];
  $month_names = ['Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'];
  $current_year = date('Y');
  $current_month = date('n');
  // Determine the starting year for October
  $start_year = ($current_month >= 10) ? $current_year : $current_year - 1;
  for ($i = 0; $i < 12; $i++) {
    $month_num = (($i + 10 - 1) % 12) + 1; // October is 10
    $year = $start_year;
    if ($month_num < 10) {
      $year = $start_year + 1;
    }
    $months[] = $month_names[$i];
    $monthly_params = [getCurrentUserId(), $month_num, $year];
    $monthly_date_filter = '';
    if (!empty($start_date)) {
        $monthly_date_filter .= ' AND o.created_at >= ?';
        $monthly_params[] = $start_date;
    }
    if (!empty($end_date)) {
        $monthly_date_filter .= ' AND o.created_at <= ?';
        $monthly_params[] = $end_date . ' 23:59:59';
    }
    $stmt = $pdo->prepare("
      SELECT SUM(oi.quantity * oi.price) as sales
      FROM orders o
      JOIN Order_Items oi ON o.order_id = oi.order_id
      JOIN products p ON oi.product_id = p.product_id
      WHERE p.seller_id = ? AND o.status != 'cancelled' AND MONTH(o.created_at) = ? AND YEAR(o.created_at) = ? $monthly_date_filter
    ");
    $stmt->execute($monthly_params);
    $monthly_sales[] = (float)($stmt->fetch()['sales'] ?? 0);
  }
    
} catch (PDOException $e) {
    $total_orders = 0;
    $total_sales = 0;
    $total_buyers = 0;
    $average_rating = 0;
    $recent_orders = [];
    $top_products = [];
    $sold_products = 0;
    $pending_products = 0;
    $active_products = 0;
    $rejected_products = 0;
    $inactive_products = 0;
    $total_products = 0;
    $monthly_sales = [];
    $months = [];
}

include 'sellerheader.php';
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Ecocycle Nluc - Seller Dashboard</title>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
      /* Enhanced stat card styles from admin dashboard */
      .stat-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border-radius: 15px;
        overflow: hidden;
        border: none;
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        min-height: 180px;
      }
      .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 30px rgba(0,0,0,0.12) !important;
      }
      .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        transition: all 0.3s ease;
        font-size: 2.5rem;
      }
      .card-title {
        font-size: 0.9rem;
        font-weight: 600;
      }
      .card-text.h4 {
        font-size: 2rem;
        font-weight: bold;
      }
      .stat-card small {
        font-size: 0.95rem;
        font-weight: 500;
      }
      @media (max-width: 991px) {
        .stat-card { min-height: 120px; }
        .card-text.h4 { font-size: 1.2rem; }
        .card-title { font-size: 0.8rem; }
      }
      .stats-card {
        background: linear-gradient(135deg, #28bf4b 0%, #2c786c 100%);
        border-radius: 15px;
        padding: 25px;
        color: white;
        box-shadow: 0 8px 25px rgba(40, 191, 75, 0.15);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: none;
      }
      .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 35px rgba(40, 191, 75, 0.25);
      }
      .stats-icon {
        font-size: 2.5rem;
        opacity: 0.8;
        margin-bottom: 15px;
      }
      .stats-number {
        font-size: 2.5rem;
        font-weight: bold;
        margin-bottom: 5px;
      }
      .stats-label {
        font-size: 1rem;
        opacity: 0.9;
        font-weight: 500;
      }
      .stats-change {
        font-size: 0.9rem;
        opacity: 0.8;
        margin-top: 10px;
      }
      .stats-change.positive {
        color: #90EE90;
      }
      .stats-change.negative {
        color: #FFB6C1;
      }
      .chart-container {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        border: 1px solid #e9ecef;
      }
      .recent-orders {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        border: 1px solid #e9ecef;
      }
      .order-item {
        padding: 15px;
        border-bottom: 1px solid #f1f3f4;
        transition: background-color 0.2s ease;
      }
      .order-item:hover {
        background-color: #f8f9fa;
      }
      .order-item:last-child {
        border-bottom: none;
      }
      .order-status {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
      }
      .status-pending {
        background-color: #fff3cd;
        color: #856404;
      }
      .status-confirmed {
        background-color: #d1edff;
        color: #0c5460;
      }
      .status-shipped {
        background-color: #d4edda;
        color: #155724;
      }
      .status-delivered {
        background-color: #d1edff;
        color: #0c5460;
      }
      .quick-actions {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        border: 1px solid #e9ecef;
      }
      .action-btn {
        display: flex;
        align-items: center;
        padding: 15px;
        margin-bottom: 10px;
        border: 1px solid #e9ecef;
        border-radius: 10px;
        text-decoration: none;
        color: #495057;
        transition: all 0.3s ease;
        background: white;
      }
      .action-btn:hover {
        background-color: #28bf4b;
        color: white;
        border-color: #28bf4b;
        transform: translateX(5px);
      }
      .action-btn i {
        font-size: 1.5rem;
        margin-right: 15px;
        width: 30px;
        text-align: center;
      }
      .section-title {
        font-size: 1.3rem;
        font-weight: bold;
        color: #2c786c;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e9ecef;
      }
      .welcome-section {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 30px;
        border: 1px solid #dee2e6;
      }
      .welcome-title {
        font-size: 2rem;
        font-weight: bold;
        color: #2c786c;
        margin-bottom: 10px;
      }
      .welcome-subtitle {
        color: #6c757d;
        font-size: 1.1rem;
      }
      .filter-form-container {
        max-width: 100%;
        margin-bottom: 1.5rem;
      }
      .filter-form-container form {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        gap: 1rem;
      }
      .filter-form-container .form-group-inline {
        min-width: 200px;
      }
      @media (max-width: 575.98px) {
        .filter-form-container {
          max-width: 100%;
        }
      }
      @media print {
        body * { visibility: hidden; }
        #financial-report-section, #financial-report-section * { visibility: visible; }
        #financial-report-section { position: absolute; left: 0; top: 0; width: 100%; }
      }
    </style>
  </head>
  <body>
    <div class="container-fluid">
      <div class="row">
        <!-- Main Content Column -->
        <div class="col-12 main-content">
          <div class="container-lg mt-3">
            <?php
            $success_message = '';
            if (isset($_SESSION['success_message'])) {
                $success_message = $_SESSION['success_message'];
                unset($_SESSION['success_message']);
            }
            ?>
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                  <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle me-3" style="font-size: 1.5rem;"></i>
                    <div>
                      <h6 class="alert-heading mb-1">Success</h6>
                      <p class="mb-0"><?php echo htmlspecialchars($success_message); ?></p>
                    </div>
                  </div>
                  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <!-- Welcome Section -->
            <div class="welcome-section">
              <div class="welcome-title">Welcome back, <?php echo htmlspecialchars($seller['fullname']); ?>!</div>
              <div class="welcome-subtitle">Here's what's happening with your store today</div>
            </div>

            <!-- Date Filter -->
            <div class="filter-form-container">
            <form method="get">
              <div class="form-group-inline">
                <label for="start_date" class="form-label text-muted fw-semibold">Start Date</label>
                <input
                  type="date"
                  id="start_date"
                  name="start_date"
                  class="form-control"
                  value="<?php echo htmlspecialchars($start_date); ?>"
                >
              </div>
              <div class="form-group-inline">
                <label for="end_date" class="form-label text-muted fw-semibold">End Date</label>
                <input
                  type="date"
                  id="end_date"
                  name="end_date"
                  class="form-control"
                  value="<?php echo htmlspecialchars($end_date); ?>"
                >
              </div>
              <div class="d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-success">
                  <i class="fas fa-filter me-2"></i>Apply Filter
                </button>
                <a href="seller-dashboard.php" class="btn btn-outline-secondary">
                  <i class="fas fa-undo me-2"></i>Reset
                </a>
              </div>
            </form>
            </div>

            <!-- Product Verification Status Alert -->
            <?php if ($pending_products > 0): ?>
            <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
              <div class="d-flex align-items-center">
                <i class="fas fa-clock me-3" style="font-size: 1.5rem;"></i>
                <div>
                  <h6 class="alert-heading mb-1">Product Verification Required</h6>
                  <p class="mb-2">You have <strong><?php echo $pending_products; ?> product(s)</strong> pending admin verification. These products will become visible to buyers once approved.</p>
                  <a href="seller-manageproducts.php" class="btn btn-warning btn-sm">
                    <i class="fas fa-eye me-1"></i>View My products
                  </a>
                </div>
              </div>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if ($rejected_products > 0): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
              <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-triangle me-3" style="font-size: 1.5rem;"></i>
                <div>
                  <h6 class="alert-heading mb-1">products Rejected</h6>
                  <p class="mb-2">You have <strong><?php echo $rejected_products; ?> product(s)</strong> that were rejected by admin. Please review and update them for resubmission.</p>
                  <a href="seller-manageproducts.php" class="btn btn-danger btn-sm">
                    <i class="fas fa-edit me-1"></i>Review products
                  </a>
                </div>
              </div>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Statistics Cards Row (Enhanced) -->
            <div class="row g-2 mb-4">
              <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 mb-3" style="flex: 0 0 20%; max-width: 20%;">
                <div class="card shadow-sm border-0 stat-card h-100">
                  <div class="card-body text-center d-flex flex-column justify-content-center">
                    <div class="stat-icon bg-success bg-opacity-10 text-success mb-3">
                      <i class="fas fa-users fa-2x"></i>
                    </div>
                    <h6 class="card-title text-muted mb-2 fw-semibold">My Customers</h6>
                    <p class="card-text h4 fw-bold text-success mb-1"><?php echo $total_buyers; ?></p>
                    <small class="text-success fw-medium">
                      <i class="fas fa-arrow-up me-1"></i>Unique buyers
                    </small>
                  </div>
                </div>
              </div>
              <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 mb-3" style="flex: 0 0 20%; max-width: 20%;">
                <div class="card shadow-sm border-0 stat-card h-100">
                  <div class="card-body text-center d-flex flex-column justify-content-center">
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary mb-3">
                      <i class="fas fa-box fa-2x"></i>
                    </div>
                    <h6 class="card-title text-muted mb-2 fw-semibold">Sold products</h6>
                    <p class="card-text h4 fw-bold text-primary mb-1"><?php echo $sold_products; ?></p>
                    <small class="text-primary fw-medium">
                      <i class="fas fa-arrow-up me-1"></i>Total sold
                    </small>
                  </div>
                </div>
              </div>
              <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 mb-3" style="flex: 0 0 20%; max-width: 20%;">
                <div class="card shadow-sm border-0 stat-card h-100">
                  <div class="card-body text-center d-flex flex-column justify-content-center">
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning mb-3">
                      <i class="fas fa-coins fa-2x"></i>
                    </div>
                    <h6 class="card-title text-muted mb-2 fw-semibold">Total Sales</h6>
                    <p class="card-text h4 fw-bold text-warning mb-1">₱<?php echo number_format($total_sales, 2); ?></p>
                    <small class="text-warning fw-medium">
                      <i class="fas fa-arrow-up me-1"></i>Lifetime earnings
                    </small>
                  </div>
                </div>
              </div>
              <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 mb-3" style="flex: 0 0 20%; max-width: 20%;">
                <div class="card shadow-sm border-0 stat-card h-100">
                  <div class="card-body text-center d-flex flex-column justify-content-center">
                    <div class="stat-icon bg-success bg-opacity-10 text-success mb-3">
                      <i class="fas fa-leaf fa-2x"></i>
                    </div>
                    <h6 class="card-title text-muted mb-2 fw-semibold">EcoCoins Earned</h6>
                    <p class="card-text h4 fw-bold text-success mb-1"><?php echo number_format($seller_ecocoins, 2); ?></p>
                    <small class="text-success fw-medium">
                      <i class="fas fa-arrow-up me-1"></i>Rewards balance
                    </small>
                  </div>
                </div>
              </div>
              <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 mb-3" style="flex: 0 0 20%; max-width: 20%;">
                <div class="card shadow-sm border-0 stat-card h-100">
                  <div class="card-body text-center d-flex flex-column justify-content-center">
                      <div class="stat-icon bg-info bg-opacity-10 text-info mb-3">
                        <i class="fas fa-clock fa-2x"></i>
                      </div>
                      <h6 class="card-title text-muted mb-2 fw-semibold">Pending orders</h6>
                      <p class="card-text h4 fw-bold text-info mb-1">
                        <?php
                          // Ensure a lightweight table exists to track which orders the seller has viewed
                          try {
                              $pdo->exec("CREATE TABLE IF NOT EXISTS order_views (
                                  id INT AUTO_INCREMENT PRIMARY KEY,
                                  order_id INT NOT NULL,
                                  seller_id INT NOT NULL,
                                  viewed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                  UNIQUE KEY ux_order_seller (order_id, seller_id)
                              ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                          } catch (PDOException $e) {
                              // ignore - table creation failed (likely permissions); fall back to default count
                          }

                          // Count pending orders for this seller that have NOT been viewed by this seller
                          try {
                              $stmt = $pdo->prepare(
                                  "SELECT COUNT(DISTINCT o.order_id) as pending_orders
                                   FROM orders o
                                   JOIN Order_Items oi ON o.order_id = oi.order_id
                                   JOIN products p ON oi.product_id = p.product_id
                                   WHERE p.seller_id = ? AND oi.status = 'pending'
                                     AND NOT EXISTS (
                                       SELECT 1 FROM order_views ov WHERE ov.order_id = o.order_id AND ov.seller_id = ?
                                     )"
                              );
                              $stmt->execute([getCurrentUserId(), getCurrentUserId()]);
                              $pending_orders = $stmt->fetch()['pending_orders'] ?? 0;
                          } catch (PDOException $e) {
                              // fallback to previous simple count if the above query fails
                              $stmt = $pdo->prepare("SELECT COUNT(DISTINCT o.order_id) as pending_orders FROM orders o JOIN Order_Items oi ON o.order_id = oi.order_id JOIN products p ON oi.product_id = p.product_id WHERE p.seller_id = ? AND oi.status = 'pending'");
                              $stmt->execute([getCurrentUserId()]);
                              $pending_orders = $stmt->fetch()['pending_orders'] ?? 0;
                          }

                          // Make the number clickable — opens Manage orders filtered to unread/pending
                          $link = 'seller-manageorders.php?filter=unread';
                          echo '<a href="' . htmlspecialchars($link) . '" class="text-decoration-none text-info">' . intval($pending_orders) . '</a>';
                        ?>
                      </p>
                      <small class="text-info fw-medium">
                        <i class="fas fa-arrow-up me-1"></i>orders awaiting action
                      </small>
                  </div>
                </div>
              </div>
            </div>

            <!-- Charts and Recent orders Row -->
            <div class="row mb-4">
              <!-- Sales Chart -->
              <div class="col-lg-8 mb-4">
                <div class="chart-container" style="max-width: 900px; height: 400px; margin: 0 auto;">
                  <div class="section-title">Sales Overview (Bar Graph)</div>
                  <canvas id="salesBarChart" height="200" width="800"></canvas>
                </div>
              </div>

              <!-- Product Sales & Stocks Dashboard -->
              <div class="col-lg-4 mb-4">
                <div class="chart-container">
                  <div class="section-title">Product Sales & Stocks</div>
                  <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                      <thead class="table-light">
                        <tr>
                          <th>Product Name</th>
                          <th>Total Sales (₱)</th>
                          <th>Stock</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php
                        $productStmt = $pdo->prepare("
                          SELECT p.name, p.stock_quantity,
                            COALESCE(SUM(oi.quantity * oi.price), 0) AS total_sales
                          FROM products p
                          LEFT JOIN Order_Items oi ON p.product_id = oi.product_id
                          LEFT JOIN orders o ON oi.order_id = o.order_id AND o.status != 'cancelled'
                          WHERE p.seller_id = ?
                          GROUP BY p.product_id
                          ORDER BY total_sales DESC
                        ");
                        $productStmt->execute([getCurrentUserId()]);
                        foreach ($productStmt->fetchAll() as $row) {
                        ?>
                        <tr>
                          <td><?php echo htmlspecialchars($row['name']); ?></td>
                          <td>₱<?php echo number_format($row['total_sales'], 2); ?></td>
                          <td>
                            <?php
                              $stock = (int)$row['stock_quantity'];
                              if ($stock === 0) {
                                echo '<span style="color: red; font-weight: bold;">' . htmlspecialchars($stock) . '</span>';
                              } else {
                                echo htmlspecialchars($stock);
                              }
                            ?>
                          </td>
                        </tr>
                        <?php }
                        ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>

            <!-- ...existing code... -->

            <!-- Recent orders and Top products -->
            <div class="row">
              <!-- Top products -->
              <!-- Removed Top Selling products section -->
            </div>

            <!-- ...existing code... -->
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/js/all.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script>
    function printSellerReport() {
      var printContents = document.getElementById('seller-report-section').innerHTML;
      var originalContents = document.body.innerHTML;
      document.body.innerHTML = printContents;
      window.print();
      document.body.innerHTML = originalContents;
      location.reload();
    }

    function saveSellerReportPDF() {
      var report = document.getElementById('seller-report-section');
      html2canvas(report).then(function(canvas) {
        var imgData = canvas.toDataURL('image/png');
        var pdf = new window.jspdf.jsPDF('p', 'mm', 'a4');
        var pageWidth = pdf.internal.pageSize.getWidth();
        var pageHeight = pdf.internal.pageSize.getHeight();
        var imgWidth = pageWidth;
        var imgHeight = canvas.height * imgWidth / canvas.width;
        if (imgHeight > pageHeight) {
          imgHeight = pageHeight;
        }
        pdf.addImage(imgData, 'PNG', 0, 0, imgWidth, imgHeight);
        pdf.save('seller_report.pdf');
      });
    }

      // Seller Bar Chart (Admin Style)
      const ctxBar = document.getElementById('salesBarChart').getContext('2d');
      const months = <?php echo json_encode($months); ?>;
      const monthlySales = <?php echo json_encode($monthly_sales); ?>;
      // If you want to add more datasets, you can fetch and add them here
      const salesBarChart = new Chart(ctxBar, {
        type: 'bar',
        data: {
          labels: months,
          datasets: [
            {
              label: 'Total Sales',
              data: monthlySales,
              backgroundColor: 'rgba(44, 120, 108, 0.8)',
              borderColor: '#2c786c',
              borderWidth: 1
            }
            // Add more datasets here if needed
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: true,
              position: 'top',
              labels: {
                usePointStyle: true
              }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                callback: function(value) {
                  return '₱' + value.toLocaleString();
                }
              }
            }
          }
        }
      });

      // Print Financial Report
      function printFinancialReport() {
        var printContents = document.getElementById('financial-report-section').innerHTML;
        var originalContents = document.body.innerHTML;
        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
        location.reload();
      }

      function saveFinancialReportPDF() {
        const report = document.getElementById('financial-report-section');
        html2canvas(report).then(canvas => {
          const imgData = canvas.toDataURL('image/png');
          const pdf = new window.jspdf.jsPDF('p', 'mm', 'a4');
          const pageWidth = pdf.internal.pageSize.getWidth();
          const pageHeight = pdf.internal.pageSize.getHeight();
          // Calculate image dimensions to fit A4
          const imgProps = pdf.getImageProperties(imgData);
          const pdfWidth = pageWidth - 20;
          const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
          pdf.addImage(imgData, 'PNG', 10, 10, pdfWidth, pdfHeight);
          pdf.save('Financial_Statement_Report.pdf');
        });
      }

      // Add some interactivity to the stats cards
      document.querySelectorAll('.stats-card').forEach(card => {
        card.addEventListener('click', function() {
          this.style.transform = 'scale(0.95)';
          setTimeout(() => {
            this.style.transform = 'translateY(-5px)';
          }, 150);
        });
      });

      // Add hover effects to table rows
      document.querySelectorAll('.table tbody tr').forEach(row => {
        row.addEventListener('mouseenter', function() {
          this.style.backgroundColor = '#f8f9fa';
        });
        row.addEventListener('mouseleave', function() {
          this.style.backgroundColor = '';
        });
      });
    </script>
  </body>
</html>
