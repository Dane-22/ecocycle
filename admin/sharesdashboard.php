<?php
require_once '../config/database.php';
if (!isset($pdo)) {
    die('Database connection not established.');
}

// Date filter logic for Bard, Admin, Seller shares
$bardIncomeMonthly = [];
$adminIncomeMonthly = [];
$sellerIncomeMonthly = [];
$monthLabels = [];
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';

$sharesSql = "SELECT o.created_at, (oi.price * oi.quantity) AS amount_paid FROM orders o JOIN order_items oi ON o.order_id = oi.order_id WHERE o.status != 'cancelled'";
if ($startDate && $endDate) {
	$sharesSql .= " AND o.created_at BETWEEN :startDate AND :endDate";
}
$stmt = $pdo->prepare($sharesSql);
if ($startDate && $endDate) {
	$stmt->bindParam(':startDate', $startDate);
	$stmt->bindParam(':endDate', $endDate);
}
$stmt->execute();
$shareOrders = $stmt->fetchAll();

$totalSales = 0;
foreach ($shareOrders as $item) {
	$totalSales += $item['amount_paid'];
}
$bardShare = $totalSales * 0.05; // 5% Bard Admin
$adminShare = $totalSales * 0.10; // 10% Admin
$sellerShare = $totalSales * 0.85; // 85% Seller

// Financial report calculations
$transactionCount = count($shareOrders);
$totalRevenue = $totalSales;
$totalExpenses = 0;
$totalProfit = $totalRevenue - $totalExpenses;

// Collect months in range
if ($startDate && $endDate) {
	$period = new DatePeriod(
		new DateTime($startDate),
		new DateInterval('P1M'),
		(new DateTime($endDate))->modify('+1 month')
	);
	foreach ($period as $dt) {
		$monthName = $dt->format('F');
		$bardIncomeMonthly[$monthName] = 0;
		$adminIncomeMonthly[$monthName] = 0;
		$sellerIncomeMonthly[$monthName] = 0;
		$monthLabels[] = $monthName;
	}
} else {
	// Default: October and November of current year
	$currentYear = date('Y');
	$monthsToShow = [10, 11];
	foreach ($monthsToShow as $monthNum) {
		$monthName = date('F', mktime(0, 0, 0, $monthNum, 10));
		$bardIncomeMonthly[$monthName] = 0;
		$adminIncomeMonthly[$monthName] = 0;
		$sellerIncomeMonthly[$monthName] = 0;
		$monthLabels[] = $monthName;
	}
}

foreach ($shareOrders as $item) {
	$date = $item['created_at'];
	$monthName = date('F', strtotime($date));
	if (isset($bardIncomeMonthly[$monthName])) {
		$bardIncomeMonthly[$monthName] += $item['amount_paid'] * 0.05;
		$adminIncomeMonthly[$monthName] += $item['amount_paid'] * 0.10;
		$sellerIncomeMonthly[$monthName] += $item['amount_paid'] * 0.85;
	}
}
?>
<html>
<head>
	<title>Admin Shares Dashboard</title>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
	<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
	<style>
		body, html { background-color: #f8f9fa; font-family: 'Poppins', sans-serif; }
		.main-content { margin-left: 255px; padding: 20px; min-height: 100vh; }
		.stat-card { border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); background: #fff; }
		.stat-icon { width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto; }
		.card-title { font-size: 0.9rem; font-weight: 600; }
		.h4 { font-size: 1.5rem; font-weight: 700; }
		.text-muted { font-size: 0.85rem; color: #6c757d !important; }
	</style>
</head>
<body>
	<?php include 'adminheader.php'; ?>
	<?php include 'adminsidebar.php'; ?>
	<div class="main-content">
	<div class="container-fluid mt-3 d-flex flex-column align-items-start" style="max-width:900px; margin-left:132px;">
		<h2 class="fw-bold mb-4" style="margin-left:8px;">Shares Dashboard</h2>

		<!-- Date Filter Form -->
		<form method="get" class="mb-4 d-flex align-items-center" style="gap:12px;">
			<label for="start_date" style="font-size:0.95rem; white-space:nowrap;">Start Date:</label>
			<input type="date" id="start_date" name="start_date" class="form-control" style="max-width:160px; font-size:0.9rem;" value="<?php echo isset($_GET['start_date']) ? htmlspecialchars($_GET['start_date']) : ''; ?>">
			<label for="end_date" style="font-size:0.95rem; white-space:nowrap;">End Date:</label>
			<input type="date" id="end_date" name="end_date" class="form-control" style="max-width:160px; font-size:0.9rem;" value="<?php echo isset($_GET['end_date']) ? htmlspecialchars($_GET['end_date']) : ''; ?>">
			<button type="submit" class="btn btn-success" style="font-size:0.9rem; padding:6px 16px;">Filter</button>
		</form>
			<div class="row g-3 mb-4 justify-content-center" style="width:900px; flex-wrap:nowrap;">
				<div class="col-md-4 d-flex align-items-stretch" style="min-width: 250px; max-width: 300px; width: 300px;">
					<div class="card stat-card" style="width: 100%; min-width: 250px; max-width: 300px;">
						<div class="card-body text-center">
							<div class="stat-icon bg-info bg-opacity-10 text-info mb-3"><i class="fas fa-user-tie fa-2x"></i></div>
							<h6 class="card-title text-muted mb-2">Bard Admin Share</h6>
							<p class="h4 fw-bold text-info mb-1">₱<?php echo number_format($bardShare, 2); ?></p>
							<small class="text-info">5% of all sales</small>
						</div>
					</div>
				</div>
				<div class="col-md-4 d-flex align-items-stretch" style="max-width: 300px; width: 300px;">
					<div class="card stat-card" style="width: 100%; max-width: 300px;">
						<div class="card-body text-center">
							<div class="stat-icon bg-primary bg-opacity-10 text-primary mb-3"><i class="fas fa-user-shield fa-2x"></i></div>
							<h6 class="card-title text-muted mb-2">Admin Share</h6>
							<p class="h4 fw-bold text-primary mb-1">₱<?php echo number_format($adminShare, 2); ?></p>
							<small class="text-primary">10% of all sales</small>
						</div>
					</div>
				</div>
				<div class="col-md-4 d-flex align-items-stretch" style="max-width: 300px; width: 300px;">
					<div class="card stat-card" style="width: 100%; max-width: 300px;">
						<div class="card-body text-center">
							<div class="stat-icon bg-success bg-opacity-10 text-success mb-3"><i class="fas fa-store fa-2x"></i></div>
							<h6 class="card-title text-muted mb-2">Seller Share</h6>
							<p class="h4 fw-bold text-success mb-1">₱<?php echo number_format($sellerShare, 2); ?></p>
							<small class="text-success">85% of all sales</small>
						</div>
					</div>
				</div>
			</div>
			<div class="row mb-4">
				<div class="col-12" style="max-width:900px; margin:0; width:900px;">
					<div class="card" style="max-width:900px; margin:0 auto; width:900px;">
						<div class="card-header bg-transparent border-0 py-3">
							<h5 class="card-title mb-0 fw-semibold text-dark">
								<i class="fas fa-chart-line me-2 text-info"></i>Shares Income (Monthly)
							</h5>
						</div>
						<div class="card-body">
							<div class="chart-container" style="position: relative; height:400px; max-width:850px; width:850px; margin:0 auto;">
								<canvas id="sharesChart" style="width:100% !important; height:100% !important; max-width:850px; width:850px;"></canvas>
							</div>
						</div>
					</div>
				</div>
			</div>
			<!-- Financial Report Section (Moved to Bottom of main content) -->
			<div class="card mb-4" style="max-width:900px; margin:32px auto 0 auto; width:900px; position:relative;">
				<div class="card-header bg-transparent border-0 py-3 d-flex justify-content-between align-items-center">
					<h5 class="card-title mb-0 fw-semibold text-dark">
						<i class="fas fa-file-invoice-dollar me-2 text-success"></i>Financial Report
					</h5>
					<button class="btn btn-outline-success" style="float:right;" onclick="printFinancialReport()">
						<i class="fas fa-print"></i> Print
					</button>
				</div>
				<div class="card-body">
					<div id="financial-report-section">
						<table class="table table-bordered table-striped mb-0">
							<tbody>
								<tr>
									<th>Bard Admin Share (5%)</th>
									<td>₱<?php echo number_format($bardShare, 2); ?></td>
								</tr>
								<tr>
									<th>Admin Share (10%)</th>
									<td>₱<?php echo number_format($adminShare, 2); ?></td>
								</tr>
								<tr>
									<th>Seller Share (85%)</th>
									<td>₱<?php echo number_format($sellerShare, 2); ?></td>
								</tr>
								<tr>
									<th>Number of Transactions</th>
									<td><?php echo $transactionCount; ?></td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
	<script>
		function printFinancialReport() {
			var printContents = document.getElementById('financial-report-section').innerHTML;
			var originalContents = document.body.innerHTML;
			var printWindow = window.open('', '', 'height=600,width=900');
			printWindow.document.write('<html><head><title>Financial Report</title>');
			printWindow.document.write('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">');
			printWindow.document.write('</head><body>');
			printWindow.document.write(printContents);
			printWindow.document.write('</body></html>');
			printWindow.document.close();
			printWindow.focus();
			printWindow.print();
			printWindow.close();
		}
		document.addEventListener('DOMContentLoaded', function() {
			const ctx = document.getElementById('sharesChart').getContext('2d');
			const bardData = <?php echo json_encode(array_values($bardIncomeMonthly)); ?>;
			const adminData = <?php echo json_encode(array_values($adminIncomeMonthly)); ?>;
			const sellerData = <?php echo json_encode(array_values($sellerIncomeMonthly)); ?>;
			const data = {
				labels: <?php echo json_encode($monthLabels); ?>,
				datasets: [
					{
						label: 'Bard Share',
						data: bardData,
						backgroundColor: 'rgba(54, 162, 235, 0.7)',
						borderColor: 'rgba(54, 162, 235, 1)',
						borderWidth: 2
					},
					{
						label: 'Admin Share',
						data: adminData,
						backgroundColor: 'rgba(255, 99, 132, 0.7)',
						borderColor: 'rgba(255, 99, 132, 1)',
						borderWidth: 2
					},
					{
						label: 'Seller Share',
						data: sellerData,
						backgroundColor: 'rgba(75, 192, 192, 0.7)',
						borderColor: 'rgba(75, 192, 192, 1)',
						borderWidth: 2
					}
				]
			};
			new Chart(ctx, {
				type: 'bar',
				data: data,
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: { 
							position: 'top',
							display: true
						},
						title: { 
							display: false
						}
					},
					scales: {
						y: {
							beginAtZero: true,
							ticks: { 
								callback: function(value) { 
									return '₱' + value.toLocaleString(); 
								} 
							},
							title: { 
								display: true, 
								text: 'Income (₱)' 
							}
						},
						x: {
							title: {
								display: true,
								text: 'Month'
							}
						}
					}
				}
			});
		});
	</script>
</body>
</html>


