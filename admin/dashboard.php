<?php 
include 'adminheader.php';
include 'adminsidebar.php';

// Include database connection
require_once '../config/database.php';

// Get date filter
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Get real data from database
try {
    // Get users with both buyer and seller accounts
    if ($startDate && $endDate) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as dual_accounts FROM Buyers b INNER JOIN Sellers s ON b.email = s.email WHERE DATE(b.created_at) >= ? AND DATE(b.created_at) <= ?");
        $stmt->execute([$startDate, $endDate]);
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) as dual_accounts FROM Buyers b INNER JOIN Sellers s ON b.email = s.email");
    }
    $dualAccounts = intval($stmt->fetch()['dual_accounts'] ?? 0);
    
    // Get total buyers
    if ($startDate && $endDate) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_buyers FROM Buyers WHERE DATE(created_at) >= ? AND DATE(created_at) <= ?");
        $stmt->execute([$startDate, $endDate]);
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) as total_buyers FROM Buyers");
    }
    $totalBuyers = intval($stmt->fetch()['total_buyers'] ?? 0);
    
    // Get total sellers
    if ($startDate && $endDate) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_sellers FROM Sellers WHERE DATE(created_at) >= ? AND DATE(created_at) <= ?");
        $stmt->execute([$startDate, $endDate]);
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) as total_sellers FROM Sellers");
    }
    $totalSellers = intval($stmt->fetch()['total_sellers'] ?? 0);
    
    // Get total admins
    $stmt = $pdo->query("SELECT COUNT(*) as total_admins FROM Admins");
    $totalAdmins = intval($stmt->fetch()['total_admins'] ?? 0);
    
    // Calculate unique users (buyers + sellers - dual accounts to avoid double counting)
    $totalUsers = $totalBuyers + $totalSellers - $dualAccounts;
    
    // Get pending products for verification
    $stmt = $pdo->query("SELECT COUNT(*) as pending_products FROM Products WHERE status = 'pending'");
    $pendingProducts = intval($stmt->fetch()['pending_products'] ?? 0);
    
    // Get seller products pending admin verification (status = 'inactive')
    $stmt = $pdo->query("SELECT COUNT(*) as pending_seller_products FROM Products WHERE status = 'inactive'");
    $pendingSellerProducts = intval($stmt->fetch()['pending_seller_products'] ?? 0);
    
    // Get total sales
    if ($startDate && $endDate) {
        $stmt = $pdo->prepare("SELECT SUM(total_amount) as total_sales FROM Orders WHERE status != 'cancelled' AND DATE(created_at) >= ? AND DATE(created_at) <= ?");
        $stmt->execute([$startDate, $endDate]);
    } else {
        $stmt = $pdo->query("SELECT SUM(total_amount) as total_sales FROM Orders WHERE status != 'cancelled'");
    }
    $result = $stmt->fetch();
    $totalSales = floatval($result['total_sales'] ?? 0);
    
    // Get recent orders
    if ($startDate && $endDate) {
        $stmt = $pdo->prepare("
            SELECT o.order_id, o.created_at, o.status, o.total_amount,
                   b.fullname as customer_name
            FROM Orders o 
            JOIN Buyers b ON o.buyer_id = b.buyer_id
            WHERE o.status != 'cancelled' AND DATE(o.created_at) >= ? AND DATE(o.created_at) <= ?
            ORDER BY o.created_at DESC
            LIMIT 5
        ");
        $stmt->execute([$startDate, $endDate]);
    } else {
        $stmt = $pdo->query("
            SELECT o.order_id, o.created_at, o.status, o.total_amount,
                   b.fullname as customer_name
            FROM Orders o 
            JOIN Buyers b ON o.buyer_id = b.buyer_id
            WHERE o.status != 'cancelled'
            ORDER BY o.created_at DESC
            LIMIT 5
        ");
    }
    $recentOrders = $stmt->fetchAll();
    
    // Get recent activities (simplified for now)
    $recentActivities = [
        ['type' => 'user', 'message' => 'Total unique users: ' . $totalUsers, 'time' => 'Current'],
        ['type' => 'buyer', 'message' => 'Buyers only: ' . ($totalBuyers - $dualAccounts), 'time' => 'Current'],
        ['type' => 'seller', 'message' => 'Sellers only: ' . ($totalSellers - $dualAccounts), 'time' => 'Current'],
        ['type' => 'dual', 'message' => 'Dual accounts (Buyer/Seller): ' . $dualAccounts, 'time' => 'Current']
    ];
    
    // Build months array from July to June
    $months = [];
    $currentMonth = (int)date('n'); // 1-12
    $currentYear = (int)date('Y');

    // If filtering by date range, show only that date range
    if ($startDate && $endDate) {
        $startDateTime = new DateTime($startDate);
        $endDateTime = new DateTime($endDate);
        $period = new DatePeriod($startDateTime, new DateInterval('P1M'), $endDateTime->modify('+1 month'));
        
        foreach ($period as $dt) {
            $monthNum = (int)$dt->format('n');
            $year = (int)$dt->format('Y');
            $months[] = ['month' => $monthNum, 'year' => $year];
        }
    } else {
        // Default: show full 12-month fiscal year (July to June)
        // If current month >= July, fiscal year starts this July and ends next June
        if ($currentMonth >= 7) {
            $startYear = $currentYear;
            $startMonth = 7;
        } else {
            // If before July, fiscal year started last July
            $startYear = $currentYear - 1;
            $startMonth = 7;
        }

        for ($i = 0; $i < 12; $i++) {
            $monthNum = ($startMonth + $i - 1) % 12 + 1;
            $year = $startYear + intval(($startMonth + $i - 1) / 12);
            $months[] = ['month' => $monthNum, 'year' => $year];
        }
    }

    // Now fetch data for each month in this order
    $monthlyData = [];
    foreach ($months as $m) {
        $monthNum = $m['month'];
        $year = $m['year'];
        $monthLabel = date('M', mktime(0, 0, 0, $monthNum, 1));
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM (
                SELECT created_at FROM Buyers WHERE MONTH(created_at) = :month AND YEAR(created_at) = :year
                UNION ALL
                SELECT created_at FROM Sellers WHERE MONTH(created_at) = :month AND YEAR(created_at) = :year
            ) as combined
        ");
        $stmt->execute(['month' => $monthNum, 'year' => $year]);
        $monthlyData[$monthLabel] = intval($stmt->fetch()['count'] ?? 0);
    }
    
} catch (PDOException $e) {
    // Fallback to default values if database error
    $dualAccounts = 0;
    $totalUsers = 0;
    $totalBuyers = 0;
    $totalSellers = 0;
    $totalAdmins = 0;
    $totalSales = 0;
    $pendingProducts = 0;
    $pendingSellerProducts = 0;
    $recentOrders = [];
    $recentActivities = [];
    $monthlyData = [
        'Jan' => 0, 'Feb' => 0, 'Mar' => 0, 'Apr' => 0,
        'May' => 0, 'Jun' => 0, 'Jul' => 0, 'Aug' => 0,
        'Sep' => 0, 'Oct' => 0, 'Nov' => 0, 'Dec' => 0
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Admin Dashboard - Ecocycle Nluc</title>
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
    <link rel="stylesheet" type="text/css" href="../css/vendor.css">
    <link rel="stylesheet" type="text/css" href="../style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Open+Sans:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
      body, html {
        overflow-x: hidden;
        max-width: 100%;
        background-color: #f8f9fa;
      }
      .main-content {
        margin-left: 280px;
        padding: 20px;
        max-width: 100%;
        overflow-x: hidden;
        height: 100vh;
        overflow-y: auto;
        background-color: #f8f9fa;
      }
      .container-fluid {
        max-width: 1400px;
        margin-left: 20px;
        margin-right: auto;
        padding: 0 20px;
      }
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
      }
      .stat-icon i {
        font-size: 1.5rem;
      }
      .activity-item {
        padding: 5px 0;
        border-bottom: 1px solid #f0f0f0;
        transition: background-color 0.3s ease;
      }
      .activity-item:hover {
        background-color: #f8f9fa;
        border-radius: 4px;
        padding-left: 5px;
        padding-right: 5px;
      }
      .activity-item:last-child {
        border-bottom: none;
      }
      .status-badge {
        padding: 2px 6px;
        border-radius: 8px;
        font-size: 0.65rem;
        font-weight: 600;
      }
      .status-completed { background-color: #d4edda; color: #155724; }
      .status-pending { background-color: #fff3cd; color: #856404; }
      .status-processing { background-color: #cce5ff; color: #004085; }
      .chart-card {
        border-radius: 15px;
        border: none;
        background: #ffffff;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
      }
      .chart-card:hover {
        box-shadow: 0 8px 30px rgba(0,0,0,0.12);
      }
      .chart-container {
        position: relative;
        height: 350px;
        margin: 10px auto;
        padding: 10px;
        width: 100%;
        max-width: 100%;
      }
      .card {
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        border: none;
        background: #ffffff;
      }
      .card-header {
        border-radius: 15px 15px 0 0 !important;
        padding: 20px 25px !important;
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        border-bottom: 1px solid #e9ecef;
      }
      .card-body {
        padding: 25px !important;
      }
      .card-title {
        font-size: 0.9rem;
        font-weight: 600;
      }
      .btn {
        border-radius: 8px;
        padding: 10px 20px;
        font-weight: 500;
        transition: all 0.3s ease;
        border: none;
      }
      .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
      }
      .btn-primary {
        background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
      }
      .btn-outline-primary {
        border: 2px solid #1976d2;
        color: #1976d2;
        background: transparent;
      }
      .btn-outline-primary:hover {
        background: #1976d2;
        color: #ffffff;
      }
      h2 {
        font-size: 2rem;
        font-weight: 700;
        color: #2c3e50;
      }
      h5 {
        font-size: 1.1rem;
        font-weight: 600;
        color: #2c3e50;
      }
      h6 {
        font-size: 0.9rem;
        font-weight: 600;
        color: #6c757d;
      }
      .h4 {
        font-size: 1.5rem;
        font-weight: 700;
      }
      .text-muted {
        font-size: 0.85rem;
        color: #6c757d !important;
      }
      small {
        font-size: 0.8rem;
        font-weight: 500;
      }
      @media (max-width: 1400px) {
        .container-fluid {
          max-width: 1200px;
        }
      }
      @media (max-width: 1200px) {
        .main-content {
          margin-left: 250px;
        }
        .container-fluid {
          max-width: 1000px;
        }
      }
      @media (max-width: 992px) {
        .main-content {
          margin-left: 250px;
        }
        .container-fluid {
          max-width: 900px;
        }
      }
      @media (max-width: 768px) {
        .main-content {
          margin-left: 0;
          padding: 15px;
        }
        .container-fluid {
          max-width: 100%;
          padding: 0 15px;
        }
        .stat-card {
          min-height: 150px;
        }
        .chart-container {
          height: 300px;
        }

        h2 {
          font-size: 1.5rem;
        }
      }
      @media (max-width: 576px) {
        .stat-card {
          min-height: 120px;
        }
        .card-body {
          padding: 15px !important;
        }
        .h4 {
          font-size: 1.2rem;
        }
        .card-title {
          font-size: 0.8rem;
        }
        .chart-container {
          height: 250px;
        }
      }
    </style>
  </head>
  <body>
    <!-- Main Content -->
    <div class="main-content">
      <div class="container-fluid mt-3">
        <!-- Welcome Section -->
        <div class="row mb-4">
          <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h2 class="fw-bold mb-1 text-dark">Welcome back, Admin!</h2>
                <p class="text-muted mb-0 fs-6">Here's what's happening with your store today.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Date Filter Form -->
        <form method="get" class="mb-4 d-flex align-items-center" style="gap:12px;">
          <label for="start_date" style="font-size:0.95rem; white-space:nowrap;">Start Date:</label>
          <input type="date" id="start_date" name="start_date" class="form-control" style="max-width:160px; font-size:0.9rem;" value="<?php echo isset($_GET['start_date']) ? htmlspecialchars($_GET['start_date']) : ''; ?>">
          <label for="end_date" style="font-size:0.95rem; white-space:nowrap;">End Date:</label>
          <input type="date" id="end_date" name="end_date" class="form-control" style="max-width:160px; font-size:0.9rem;" value="<?php echo isset($_GET['end_date']) ? htmlspecialchars($_GET['end_date']) : ''; ?>">
          <button type="submit" class="btn btn-success" style="font-size:0.9rem; padding:6px 16px;">Filter</button>
        </form>

        <!-- Statistics Cards - Top Section -->
        <div class="row g-3 mb-4">
          <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
            <div class="card shadow-sm border-0 stat-card h-100">
              <div class="card-body text-center d-flex flex-column justify-content-center">
                <div class="stat-icon bg-success bg-opacity-10 text-success mb-3">
                  <i class="fas fa-users fa-2x"></i>
                </div>
                <h6 class="card-title text-muted mb-2 fw-semibold">Total Users</h6>
                <p class="card-text h4 fw-bold text-success mb-1"><?php echo number_format($totalUsers); ?></p>
                <small class="text-success fw-medium">
                  <i class="fas fa-arrow-up me-1"></i>+12% this month
                </small>
              </div>
            </div>
          </div>
          <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
            <div class="card shadow-sm border-0 stat-card h-100">
              <div class="card-body text-center d-flex flex-column justify-content-center">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary mb-3">
                  <i class="fas fa-shopping-bag fa-2x"></i>
                </div>
                <h6 class="card-title text-muted mb-2 fw-semibold">Buyers Only</h6>
                <p class="card-text h4 fw-bold text-primary mb-1"><?php echo number_format($totalBuyers - $dualAccounts); ?></p>
                <small class="text-primary fw-medium">
                  <i class="fas fa-arrow-up me-1"></i>+15% this month
                </small>
              </div>
            </div>
          </div>
          <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
            <div class="card shadow-sm border-0 stat-card h-100">
              <div class="card-body text-center d-flex flex-column justify-content-center">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning mb-3">
                  <i class="fas fa-store fa-2x"></i>
                </div>
                <h6 class="card-title text-muted mb-2 fw-semibold">Sellers Only</h6>
                <p class="card-text h4 fw-bold text-warning mb-1"><?php echo number_format($totalSellers - $dualAccounts); ?></p>
                <small class="text-warning fw-medium">
                  <i class="fas fa-arrow-up me-1"></i>+8% this month
                </small>
              </div>
            </div>
          </div>
          <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
            <div class="card shadow-sm border-0 stat-card h-100">
              <div class="card-body text-center d-flex flex-column justify-content-center">
                <div class="stat-icon bg-info bg-opacity-10 text-info mb-3">
                  <i class="fas fa-user-tie fa-2x"></i>
                </div>
                <h6 class="card-title text-muted mb-2 fw-semibold">Buyer/Seller</h6>
                <p class="card-text h4 fw-bold text-info mb-1"><?php echo number_format($dualAccounts); ?></p>
                <small class="text-info fw-medium">
                  <i class="fas fa-users me-1"></i>Dual accounts
                </small>
              </div>
            </div>
          </div>
          <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
            <div class="card shadow-sm border-0 stat-card h-100">
              <div class="card-body text-center d-flex flex-column justify-content-center">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning mb-3">
                  <i class="fas fa-clock fa-2x"></i>
                </div>
                <h6 class="card-title text-muted mb-2 fw-semibold">Pending Verification</h6>
                <p class="card-text h4 fw-bold text-warning mb-1">
                  <?php echo number_format($pendingProducts + $pendingSellerProducts); ?>
                </p>
                <small class="text-warning fw-medium">
                  <i class="fas fa-exclamation-triangle me-1"></i>Products awaiting approval
                </small>
                <?php if ($pendingProducts > 0): ?>
                  <div class="mt-3">
                    <a href="manageproducts.php" class="btn btn-warning btn-sm">
                      <i class="fas fa-eye me-1"></i>Review Products
                    </a>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Charts Section -->
        <div class="row g-4 mb-4">
          <div class="col-xl-10 col-lg-11 col-md-12">
            <div class="card shadow-sm border-0 chart-card">
              <div class="card-header bg-transparent border-0 py-3">
                <h5 class="card-title mb-0 fw-semibold text-dark">
                  <i class="fas fa-chart-area me-2 text-primary"></i>
                  Monthly User Analytics
                </h5>
              </div>
              <div class="card-body py-3">
                <div class="chart-container">
                  <canvas id="userChart"></canvas>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="row g-4 mb-4">
          <div class="col-xl-10 col-lg-11 col-md-12">
            <div class="card shadow-sm border-0 chart-card">
              <div class="card-header bg-transparent border-0 py-3">
                <h5 class="card-title mb-0 fw-semibold text-dark">
                  <i class="fas fa-chart-pie me-2 text-success"></i>
                  User Distribution (Including Dual Accounts)
                </h5>
              </div>
              <div class="card-body py-3">
                <div class="chart-container">
                  <canvas id="pieChart"></canvas>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/js/all.min.js"></script>
    
    <script>
      // User Analytics Chart
      const userCtx = document.getElementById('userChart').getContext('2d');
      const months = <?php echo json_encode(array_keys($monthlyData)); ?>;
      const monthlyData = <?php echo json_encode(array_values($monthlyData)); ?>;
      
      // Calculate buyers and sellers data based on real totals
      const totalBuyers = <?php echo $totalBuyers; ?>;
      const totalSellers = <?php echo $totalSellers; ?>;
      const dualAccounts = <?php echo $dualAccounts; ?>;
      const totalUsers = <?php echo $totalUsers; ?>;
      
      // Distribute the monthly data proportionally
      const buyersOnlyData = monthlyData.map(count => Math.round(count * ((totalBuyers - dualAccounts) / totalUsers)));
      const sellersOnlyData = monthlyData.map(count => Math.round(count * ((totalSellers - dualAccounts) / totalUsers)));
      const dualAccountsData = monthlyData.map(count => Math.round(count * (dualAccounts / totalUsers)));
      
      const userChart = new Chart(userCtx, {
        type: 'bar',
        data: {
          labels: months,
          datasets: [
            {
              label: 'Total Users',
              data: monthlyData,
              backgroundColor: 'rgba(44, 120, 108, 0.8)',
              borderColor: '#2c786c',
              borderWidth: 1
            },
            {
              label: 'Buyers Only',
              data: buyersOnlyData,
              backgroundColor: 'rgba(250, 166, 26, 0.8)',
              borderColor: '#faa61a',
              borderWidth: 1
            },
            {
              label: 'Sellers Only',
              data: sellersOnlyData,
              backgroundColor: 'rgba(231, 76, 60, 0.8)',
              borderColor: '#e74c3c',
              borderWidth: 1
            },
            {
              label: 'Buyer/Seller',
              data: dualAccountsData,
              backgroundColor: 'rgba(23, 162, 184, 0.8)',
              borderColor: '#17a2b8',
              borderWidth: 1
            }
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
                  return value.toLocaleString();
                }
              }
            }
          }
        }
      });

      // Pie Chart
      const pieCtx = document.getElementById('pieChart').getContext('2d');
      const pieChart = new Chart(pieCtx, {
        type: 'doughnut',
        data: {
          labels: ['Buyers Only', 'Sellers Only', 'Buyer/Seller'],
          datasets: [{
            data: [
              <?php echo $totalBuyers - $dualAccounts; ?>, 
              <?php echo $totalSellers - $dualAccounts; ?>, 
              <?php echo $dualAccounts; ?>
            ],
            backgroundColor: [
              '#faa61a',
              '#e74c3c',
              '#2c786c'
            ],
            borderWidth: 0
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom',
              labels: {
                padding: 20,
                usePointStyle: true
              }
            }
          }
        }
      });

      // Auto-refresh dashboard data every 30 seconds
      setInterval(function() {
        // In a real application, this would make an AJAX call to refresh data
        console.log('Refreshing dashboard data...');
      }, 30000);
    </script>
  </body>
</html>
