<?php 
// Temporary data for bard redeemed products (UI only)
$redeemedProducts = [
    [
        'id' => 1,
        'user_name' => 'John Doe',
        'product_name' => 'Eco-Friendly Water Bottle',
        'ecocoins_used' => 150,
        'redeemed_date' => '2024-01-15',
        'status' => 'Redeemed',
        'image' => 'images/ecobottle.jpg'
    ],
    [
        'id' => 2,
        'user_name' => 'Jane Smith',
        'product_name' => 'Recycled Notebook',
        'ecocoins_used' => 80,
        'redeemed_date' => '2024-01-14',
        'status' => 'Redeemed',
        'image' => 'images/recycled-notebook.jpg'
    ],
    [
        'id' => 3,
        'user_name' => 'Mike Johnson',
        'product_name' => 'Bamboo Toothbrush',
        'ecocoins_used' => 60,
        'redeemed_date' => '2024-01-13',
        'status' => 'Processing',
        'image' => 'images/bamboo-toothbrush.jpg'
    ],
    [
        'id' => 4,
        'user_name' => 'Sarah Wilson',
        'product_name' => 'Recycled Tote Bag',
        'ecocoins_used' => 120,
        'redeemed_date' => '2024-01-12',
        'status' => 'Redeemed',
        'image' => 'images/recycled-tote-bag.jpg'
    ],
    [
        'id' => 5,
        'user_name' => 'David Brown',
        'product_name' => 'Glass Vase',
        'ecocoins_used' => 200,
        'redeemed_date' => '2024-01-11',
        'status' => 'Redeemed',
        'image' => 'images/glass-vase.jpg'
    ],
    [
        'id' => 6,
        'user_name' => 'Lisa Garcia',
        'product_name' => 'Denim Notebook',
        'ecocoins_used' => 90,
        'redeemed_date' => '2024-01-10',
        'status' => 'Pending',
        'image' => 'images/denim-notebook.jpg'
    ]
];

// Calculate statistics
$totalRedeemed = count($redeemedProducts);
$totalEcocoinsUsed = array_sum(array_column($redeemedProducts, 'ecocoins_used'));
$completedRedeemed = count(array_filter($redeemedProducts, function($item) { return $item['status'] == 'Redeemed'; }));
$pendingRedeemed = count(array_filter($redeemedProducts, function($item) { return $item['status'] == 'Pending'; }));
$processingRedeemed = count(array_filter($redeemedProducts, function($item) { return $item['status'] == 'Processing'; }));

// Get unique users
$uniqueUsers = count(array_unique(array_column($redeemedProducts, 'user_name')));

// Monthly data for charts
$monthlyData = [
    'Jan' => 12, 'Feb' => 19, 'Mar' => 15, 'Apr' => 25,
    'May' => 22, 'Jun' => 30, 'Jul' => 28, 'Aug' => 35,
    'Sep' => 32, 'Oct' => 40, 'Nov' => 38, 'Dec' => 45
];
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Bard Dashboard - Ecocycle Nluc</title>
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
                <h2 class="fw-bold mb-1 text-dark">Welcome to Bard Dashboard!</h2>
                <p class="text-muted mb-0 fs-6">Here's what's happening with redeemed products today.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Statistics Cards - Top Section -->
        <div class="row g-3 mb-4">
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
          <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
            <div class="card shadow-sm border-0 stat-card h-100">
              <div class="card-body text-center d-flex flex-column justify-content-center">
                <div class="stat-icon bg-secondary bg-opacity-10 text-secondary mb-3">
                  <i class="fas fa-cogs fa-2x"></i>
                </div>
                <h6 class="card-title text-muted mb-2 fw-semibold">Processing</h6>
                <p class="card-text h4 fw-bold text-secondary mb-1"><?php echo number_format($processingRedeemed); ?></p>
                <small class="text-secondary fw-medium">
                  <i class="fas fa-spinner me-1"></i>In progress
                </small>
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
                  Monthly Redemption Analytics
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
                  Redemption Status Distribution
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

        <!-- Redeemed Products Section -->
        <div class="row g-4 mb-4">
          <div class="col-12">
            <div class="card shadow-sm border-0">
              <div class="card-header bg-transparent border-0 py-3">
                <h5 class="card-title mb-0 fw-semibold text-dark">
                  <i class="fas fa-gift me-2 text-success"></i>
                  Bard Redeemed Products
                </h5>
              </div>
              <div class="card-body py-3">
                <div class="row">
                  <?php foreach ($redeemedProducts as $product): ?>
                  <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                    <div class="card h-100 product-card" style="border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                      <div class="position-relative">
                        <img src="<?php echo $product['image']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['product_name']); ?>" style="height: 200px; object-fit: cover; border-radius: 15px 15px 0 0;">
                        <div class="position-absolute top-0 end-0 m-2">
                          <span class="status-badge status-<?php echo strtolower($product['status']); ?>">
                            <?php echo $product['status']; ?>
                          </span>
                        </div>
                      </div>
                      <div class="card-body">
                        <h6 class="card-title fw-bold text-dark mb-2"><?php echo htmlspecialchars($product['product_name']); ?></h6>
                        <div class="row text-muted small mb-2">
                          <div class="col-6">
                            <i class="fas fa-user me-1"></i>
                            <?php echo htmlspecialchars($product['user_name']); ?>
                          </div>
                          <div class="col-6">
                            <i class="fas fa-coins me-1"></i>
                            <?php echo number_format($product['ecocoins_used']); ?> coins
                          </div>
                        </div>
                        <div class="text-muted small">
                          <i class="fas fa-calendar me-1"></i>
                          Redeemed: <?php echo date('M d, Y', strtotime($product['redeemed_date'])); ?>
                        </div>
                      </div>
                      <div class="card-footer bg-transparent border-0 pt-0">
                        <div class="d-flex justify-content-between align-items-center">
                          <span class="badge bg-primary">ID: #<?php echo $product['id']; ?></span>
                          <button class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-eye me-1"></i>View Details
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                  <?php endforeach; ?>
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
      
      const userChart = new Chart(userCtx, {
        type: 'bar',
        data: {
          labels: months,
          datasets: [
            {
              label: 'Redemptions',
              data: monthlyData,
              backgroundColor: 'rgba(44, 120, 108, 0.8)',
              borderColor: '#2c786c',
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
          labels: ['Redeemed', 'Processing', 'Pending'],
          datasets: [{
            data: [
              <?php echo $completedRedeemed; ?>, 
              <?php echo $processingRedeemed; ?>, 
              <?php echo $pendingRedeemed; ?>
            ],
            backgroundColor: [
              '#28a745',
              '#17a2b8',
              '#ffc107'
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
        console.log('Refreshing bard dashboard data...');
      }, 30000);
    </script>
  </body>
</html> 