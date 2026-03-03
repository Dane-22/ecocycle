<?php
require_once '../config/database.php';

// Get date filter from request (no defaults - user must select)
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Only filter if both dates are provided
$applyFilter = !empty($start_date) && !empty($end_date);

// Fetch redeemed BARD products from the database with optional date filter

// Query for BARD products redeemed only
$redeemSql = "
  SELECT
    CONCAT('bard-', bpr.redeem_id) AS id,
    COALESCE(b.fullname, 'Unknown User') AS user_name,
    bp.name AS product_name,
    bpr.ecocoins_spent AS ecocoins_used,
    bpr.created_at AS redeemed_date,
    bpr.status AS status,
    bp.image AS image,
    'bard' AS product_type
  FROM bardproductsredeem bpr
  LEFT JOIN buyers b ON bpr.user_id = b.buyer_id
  LEFT JOIN bardproducts bp ON bpr.product_id = bp.id
  WHERE bpr.user_type = 'buyer'
";

if ($applyFilter) {
  $redeemSql .= " AND DATE(bpr.created_at) >= ? AND DATE(bpr.created_at) <= ?";
}

$redeemSql .= " ORDER BY bpr.created_at DESC LIMIT 100";

try {
  $stmt = $pdo->prepare($redeemSql);
  if ($applyFilter) {
    $stmt->execute([$start_date, $end_date]);
  } else {
    $stmt->execute();
  }
  $redeemedProducts = $stmt->fetchAll();
} catch (PDOException $e) {
  error_log("BARD Dashboard Query Error: " . $e->getMessage());
  $redeemedProducts = [];
}

// Fetch all paid orders for shares income only with optional date filter
$sharesSql = "
  SELECT (oi.price * oi.quantity) AS amount_paid, DATE(o.created_at) AS order_date
  FROM orders o
  JOIN order_items oi ON o.order_id = oi.order_id
  WHERE o.status != 'cancelled'
";

if ($applyFilter) {
  $sharesSql .= " AND DATE(o.created_at) >= ? AND DATE(o.created_at) <= ?";
}

$stmt = $pdo->prepare($sharesSql);
if ($applyFilter) {
  $stmt->execute([$start_date, $end_date]);
} else {
  $stmt->execute();
}
$shareOrders = $stmt->fetchAll();

// Calculate statistics
// Calculate shares income (5% per transaction)
// Calculate shares income (5% per transaction)

// Shares income: 5% from all paid orders
$sharesIncome = 0;
foreach ($shareOrders as $item) {
  $sharesIncome += $item['amount_paid'] * 0.05;
}

// Other cards: stats from redeem transactions only
$totalRedeemed = count($redeemedProducts);
$totalEcocoinsUsed = 0;
$completedRedeemed = 0;
$pendingRedeemed = 0;
$processingRedeemed = 0;
$userNames = [];
foreach ($redeemedProducts as $item) {
  $totalEcocoinsUsed += $item['ecocoins_used'];
  $userNames[] = $item['user_name'];
  if (strtolower($item['status']) == 'confirmed' || strtolower($item['status']) == 'delivered' || strtolower($item['status']) == 'redeemed') {
    $completedRedeemed++;
  } elseif (strtolower($item['status']) == 'pending') {
    $pendingRedeemed++;
  } elseif (strtolower($item['status']) == 'processing' || strtolower($item['status']) == 'shipped') {
    $processingRedeemed++;
  }
}
$uniqueUsers = count(array_unique($userNames));
?>
    <style>
      body, html {
        overflow-x: hidden;
        max-width: 100%;
        background-color: #f8f9fa;
        font-family: 'Poppins', sans-serif;
      }

      .main-content {
        margin-left: 255px;
        padding: 20px;
        max-width: 100%;
        overflow-x: hidden;
        min-height: 100vh;
        background-color: #f8f9fa;
        /* Remove flex centering */
      }

      .container-fluid {
        max-width: 1400px;
        margin-left: 20px;
        margin-right: auto;
        padding: 0 20px;
        margin-top: 1rem;
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

      .status-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
      }

      .status-completed { background-color: #d4edda; color: #155724; }
      .status-pending { background-color: #fff3cd; color: #856404; }
      .status-processing { background-color: #cce5ff; color: #004085; }



      .product-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: none;
        overflow: hidden;
      }

      .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
      }

      .product-card .card-img-top {
        transition: transform 0.3s ease;
      }

      .product-card:hover .card-img-top {
        transform: scale(1.05);
      }

      @media (max-width: 768px) {
        .main-content {
          margin-left: 0;
          padding: 15px;
        }

        .container-fluid {
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

        .chart-container {
          height: 250px;
        }
      }

      .custom-card-width {
        max-width: 250px;
        margin: 0 auto;
      }
    </style>
  </head>
  <body>
    <!-- Include Bard Header -->
    <?php include 'bardheader.php'; ?>
    
    <!-- Include Bard Sidebar -->
    <?php include 'bardsidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
      <div class="container-fluid mt-3">

        <!-- Statistics Cards -->
        <div class="row mb-4">
          <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h2 class="fw-bold mb-1 text-dark">Welcome back, Bard Admin!</h2>
                <p class="text-muted mb-0 fs-6">Here's what's happening with your store today.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Date Filter Section -->
        <form method="GET" class="d-flex align-items-end gap-2 mb-3" style="flex-wrap: wrap;">
          <div>
            <label for="start_date" class="form-label fw-semibold mb-1">Start Date</label>
            <input type="date" class="form-control form-control-sm" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
          </div>
          <div>
            <label for="end_date" class="form-label fw-semibold mb-1">End Date</label>
            <input type="date" class="form-control form-control-sm" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
          </div>
          <button type="submit" class="btn btn-success btn-sm">
            <i class="fas fa-filter me-1"></i>Apply
          </button>
          <a href="barddashboard.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-redo me-1"></i>Reset
          </a>
        </form>
        <div class="row g-3 mb-4">

          <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
            <div class="card shadow-sm border-0 stat-card h-100">
              <div class="card-body text-center d-flex flex-column justify-content-center">
                <div class="stat-icon bg-info bg-opacity-10 text-info mb-3">
                  <i class="fas fa-hand-holding-usd fa-2x"></i>
                </div>
                <h6 class="card-title text-muted mb-2 fw-semibold">Shares Income</h6>
                <p class="card-text h4 fw-bold text-info mb-1">₱<?php echo number_format($sharesIncome, 2); ?></p>
                <small class="text-info fw-medium">
                  <i class="fas fa-chart-line me-1"></i>5% from seller sales
                </small>
              </div>
            </div>
          </div>

          <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
            <div class="card shadow-sm border-0 stat-card h-100">
              <div class="card-body text-center d-flex flex-column justify-content-center">
                <div class="stat-icon bg-success bg-opacity-10 text-success mb-3">
                  <i class="fas fa-gift fa-2x"></i>
                </div>
                <h6 class="card-title text-muted mb-2 fw-semibold">Total Redeemed</h6>
                <p class="card-text h4 fw-bold text-success mb-1"><?php echo number_format($totalRedeemed); ?></p>
                <small class="text-success fw-medium">
                  <i class="fas fa-check-circle me-1"></i>Products redeemed
                </small>
              </div>
            </div>
          </div>

          <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
            <div class="card shadow-sm border-0 stat-card h-100">
              <div class="card-body text-center d-flex flex-column justify-content-center">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary mb-3">
                  <i class="fas fa-coins fa-2x"></i>
                </div>
                <h6 class="card-title text-muted mb-2 fw-semibold">EcoCoins Used</h6>
                <p class="card-text h4 fw-bold text-primary mb-1"><?php echo number_format($totalEcocoinsUsed); ?></p>
                <small class="text-primary fw-medium">
                  <i class="fas fa-coins me-1"></i>Total coins spent
                </small>
              </div>
            </div>
          </div>

          <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
            <div class="card shadow-sm border-0 stat-card h-100">
              <div class="card-body text-center d-flex flex-column justify-content-center">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning mb-3">
                  <i class="fas fa-users fa-2x"></i>
                </div>
                <h6 class="card-title text-muted mb-2 fw-semibold">Active Users</h6>
                <p class="card-text h4 fw-bold text-warning mb-1"><?php echo number_format($uniqueUsers); ?></p>
                <small class="text-warning fw-medium">
                  <i class="fas fa-user-check me-1"></i>Unique users
                </small>
              </div>
            </div>
          </div>

          <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
            <div class="card shadow-sm border-0 stat-card h-100">
              <div class="card-body text-center d-flex flex-column justify-content-center">
                <div class="stat-icon bg-info bg-opacity-10 text-info mb-3">
                  <i class="fas fa-clock fa-2x"></i>
                </div>
                <h6 class="card-title text-muted mb-2 fw-semibold">Pending</h6>
                <p class="card-text h4 fw-bold text-info mb-1"><?php echo number_format($pendingRedeemed); ?></p>
                <small class="text-info fw-medium">
                  <i class="fas fa-hourglass-half me-1"></i>Awaiting processing
                </small>
              </div>
            </div>
          </div>

        </div>

        <!-- Charts Section -->
        <div class="row g-4 mb-4">
        <!-- Shares Income Graph Section -->
        <div class="row g-4 mb-4">
          <div class="col-xl-10 col-lg-11 col-md-12">
            <div class="card shadow-sm border-0 chart-card">
              <div class="card-header bg-transparent border-0 py-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-semibold text-dark">
                  <i class="fas fa-chart-bar me-2 text-info"></i>
                  Shares Income Analytics
                </h5>
                <div>
                  <!-- Shares Income for Current Month Only -->
                </div>
              </div>
              <div class="card-body py-3">
                <div class="chart-container">
                  <canvas id="sharesIncomeChart"></canvas>
                </div>
              </div>
            </div>
          </div>
        </div>
          <div class="col-xl-10 col-lg-11 col-md-12">
            <div class="card shadow-sm border-0 chart-card">
              <div class="card-header bg-transparent border-0 py-3">
                <h5 class="card-title mb-0 fw-semibold text-dark">
                  <i class="fas fa-chart-area me-2 text-primary"></i>
                  Monthly Redemption Analytics
                </h5>
              </div>
              <div class="card-body py-3">
                  <div class="chart-container">
                    <canvas id="redemptionChart"></canvas>
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
      // Redemption Analytics Chart (using PHP data)
      document.addEventListener('DOMContentLoaded', function() {
        const redemptionCtx = document.getElementById('redemptionChart').getContext('2d');
        // PHP: Prepare redemption and ecocoins data based on filter
        const redemptionLabels = <?php
          $redemptionLabels = [];
          $redemptionData = [];
          $ecocoinsData = [];
          
          if ($applyFilter) {
            // Date filter is applied - show daily data
            $start_ts = strtotime($start_date);
            $end_ts = strtotime($end_date);
            
            $current_ts = $start_ts;
            while ($current_ts <= $end_ts) {
              $redemptionLabels[] = date('M d', $current_ts);
              $redemptionData[] = 0;
              $ecocoinsData[] = 0;
              $current_ts += 86400;
            }
            
            // Query BARD products with date filter
            $redemptionSql = "
              SELECT bpr.created_at, bpr.ecocoins_spent 
              FROM bardproductsredeem bpr
              WHERE bpr.user_type = 'buyer'
              AND DATE(bpr.created_at) >= ?
              AND DATE(bpr.created_at) <= ?
              ORDER BY bpr.created_at
            ";
            $stmt = $pdo->prepare($redemptionSql);
            $stmt->execute([$start_date, $end_date]);
            $bard_orders = $stmt->fetchAll();
            
            foreach ($bard_orders as $item) {
              $item_date = date('Y-m-d', strtotime($item['created_at']));
              $day_diff = floor(($start_ts - strtotime($item_date)) / 86400);
              if ($day_diff <= 0 && $day_diff > -(count($redemptionLabels))) {
                $idx = -$day_diff;
                if (isset($redemptionData[$idx])) {
                  $redemptionData[$idx]++;
                  $ecocoinsData[$idx] += $item['ecocoins_spent'];
                }
              }
            }
          } else {
            // No filter - show monthly data for all records
            $currentYear = date('Y');
            for ($m = 1; $m <= 12; $m++) {
              $redemptionLabels[] = date('M', mktime(0, 0, 0, $m, 10));
              $redemptionData[] = 0;
              $ecocoinsData[] = 0;
            }
            
            $redemptionSql = "
              SELECT bpr.created_at, bpr.ecocoins_spent 
              FROM bardproductsredeem bpr
              WHERE bpr.user_type = 'buyer'
              ORDER BY bpr.created_at
            ";
            $stmt = $pdo->prepare($redemptionSql);
            $stmt->execute();
            $bard_orders = $stmt->fetchAll();
            
            foreach ($bard_orders as $item) {
              $month = date('n', strtotime($item['created_at'])) - 1;
              $redemptionData[$month]++;
              $ecocoinsData[$month] += $item['ecocoins_spent'];
            }
          }
          
          echo json_encode($redemptionLabels);
        ?>;
        let redemptionData = <?php echo json_encode($redemptionData); ?>;
        let ecocoinsData = <?php echo json_encode($ecocoinsData); ?>;
        <?php if ($totalRedeemed == 0): ?>
          redemptionData = redemptionData.map(() => null);
          ecocoinsData = ecocoinsData.map(() => null);
        <?php endif; ?>
        // Gradient for Redemptions
        const gradientRedemptions = redemptionCtx.createLinearGradient(0, 0, 0, 400);
        gradientRedemptions.addColorStop(0, 'rgba(102,126,234,0.25)');
        gradientRedemptions.addColorStop(1, 'rgba(102,126,234,0.01)');
        // Gradient for EcoCoins Used
        const gradientEcoCoins = redemptionCtx.createLinearGradient(0, 0, 0, 400);
        gradientEcoCoins.addColorStop(0, 'rgba(118,75,162,0.25)');
        gradientEcoCoins.addColorStop(1, 'rgba(118,75,162,0.01)');
        const redemptionChart = new Chart(redemptionCtx, {
          type: 'bar',
          data: {
            labels: redemptionLabels,
            datasets: [
              {
                label: 'Redemptions',
                data: redemptionData,
                borderColor: '#667eea',
                backgroundColor: gradientRedemptions,
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#667eea',
                pointBorderWidth: 3,
                pointRadius: 7,
                pointHoverRadius: 10,
                pointStyle: 'circle',
                hoverBackgroundColor: '#667eea',
                hoverBorderColor: '#fff',
                hoverBorderWidth: 4,
                yAxisID: 'y'
              },
              {
                label: 'EcoCoins Used',
                data: ecocoinsData,
                borderColor: '#764ba2',
                backgroundColor: gradientEcoCoins,
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#764ba2',
                pointBorderWidth: 3,
                pointRadius: 7,
                pointHoverRadius: 10,
                pointStyle: 'circle',
                hoverBackgroundColor: '#764ba2',
                hoverBorderColor: '#fff',
                hoverBorderWidth: 4,
                yAxisID: 'y1'
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
              mode: 'index',
              intersect: false,
            },
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
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                  display: true,
                  text: 'Number of Redemptions'
                }
              },
              y1: {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                  display: true,
                  text: 'EcoCoins Used'
                },
                grid: {
                  drawOnChartArea: false,
                },
              }
            }
          }
        });
      });



      // Auto-refresh dashboard data every 30 seconds
      setInterval(function() {
        console.log('Refreshing bard dashboard data...');
      }, 30000);

      // Shares Income Chart Data (PHP to JS) - with optional date filter
      const sharesIncomeData = <?php
        $sharesIncomeArray = [];
        $dayLabels = [];
        
        if ($applyFilter) {
          // Date filter applied - show daily data
          $start_ts = strtotime($start_date);
          $end_ts = strtotime($end_date);
          
          $current_ts = $start_ts;
          while ($current_ts <= $end_ts) {
            $dayLabels[] = date('M d', $current_ts);
            $sharesIncomeArray[date('Y-m-d', $current_ts)] = 0;
            $current_ts += 86400;
          }
          
          // Query shares income with date filter
          foreach ($shareOrders as $item) {
            $date = $item['order_date'];
            if (isset($sharesIncomeArray[$date])) {
              $sharesIncomeArray[$date] += $item['amount_paid'] * 0.05;
            }
          }
        } else {
          // No filter - show monthly data
          $currentYear = date('Y');
          for ($i = 0; $i < 12; $i++) {
            $m = (($i + 10 - 1) % 12) + 1;
            $monthStr = sprintf('%04d-%02d', $currentYear, $m);
            $dayLabels[] = date('F', mktime(0, 0, 0, $m, 10));
            $sharesIncomeArray[$monthStr] = 0;
          }
          
          // Aggregate shares income by month
          foreach ($shareOrders as $item) {
            $month = date('Y-m', strtotime($item['order_date']));
            if (isset($sharesIncomeArray[$month])) {
              $sharesIncomeArray[$month] += $item['amount_paid'] * 0.05;
            }
          }
        }
        
        echo json_encode(array_values($sharesIncomeArray));
      ?>;
      const sharesIncomeLabels = <?php echo json_encode($dayLabels); ?>;

      // Populate month filter dropdown

      // Draw Shares Income Chart for date range
      let sharesIncomeChart;
      function renderSharesIncomeChartAllMonths() {
        const ctx = document.getElementById('sharesIncomeChart').getContext('2d');
        const data = {
          labels: sharesIncomeLabels,
          datasets: [{
            label: 'Shares Income',
            data: sharesIncomeData,
            backgroundColor: 'rgba(54, 162, 235, 0.8)',
            borderColor: '#667eea',
            borderWidth: 1
          }]
        };
        if (sharesIncomeChart) sharesIncomeChart.destroy();
        sharesIncomeChart = new Chart(ctx, {
          type: 'bar',
          data: data,
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { display: true, position: 'top', labels: { usePointStyle: true } },
              title: {
                display: true,
                text: 'Shares Income Analytics (Monthly)'
              }
            },
            scales: {
              y: {
                beginAtZero: true,
                ticks: {
                  callback: function(value) { return '₱' + value.toLocaleString(); }
                },
                title: {
                  display: true,
                  text: 'Shares Income'
                }
              }
            }
          }
        });
      }
      // Initial render after DOM is ready
      document.addEventListener('DOMContentLoaded', function() {
        renderSharesIncomeChartAllMonths();
      });
    </script> 