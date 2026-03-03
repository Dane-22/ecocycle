<?php
require_once '../config/database.php';
if (!isset($pdo)) {
	die('Database connection not established.');
}

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
$bardShare = $totalSales * 0.05;
$adminShare = $totalSales * 0.10;
$sellerShare = $totalSales * 0.85;

$transactionCount = count($shareOrders);
$totalRevenue = $totalSales;
$totalExpenses = 0;
$totalProfit = $totalRevenue - $totalExpenses;

// Monthly shares report
$bardIncomeMonthly = [];
$adminIncomeMonthly = [];
$sellerIncomeMonthly = [];
$monthLabels = [];
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

// Daily shares report
$bardIncomeDaily = [];
$adminIncomeDaily = [];
$sellerIncomeDaily = [];
$dayLabels = [];
foreach ($shareOrders as $item) {
	$day = date('Y-m-d', strtotime($item['created_at']));
	if (!isset($bardIncomeDaily[$day])) {
		$bardIncomeDaily[$day] = 0;
		$adminIncomeDaily[$day] = 0;
		$sellerIncomeDaily[$day] = 0;
		$dayLabels[] = $day;
	}
	$bardIncomeDaily[$day] += $item['amount_paid'] * 0.05;
	$adminIncomeDaily[$day] += $item['amount_paid'] * 0.10;
	$sellerIncomeDaily[$day] += $item['amount_paid'] * 0.85;
}
?>
<html>
<head>
	<style>
		@media print {
			.fa,
			[class^="fa-"],
			[class*=" fa-"],
			button,
			.card-title i,
			.card-header i {
				display: none !important;
			}
		}
	</style>
	<title>Admin Reports</title>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
	<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
	<?php include 'adminheader.php'; ?>
	<?php include 'adminsidebar.php'; ?>
	<div class="container mt-4" style="margin-left:300px; max-width:1200px; width:100%;">
		<div id="full-report-content">
		<div class="d-flex justify-content-between align-items-center mb-4">
			<h2 class="fw-bold mb-0">Admin Reports</h2>
			<button class="btn btn-success" onclick="printFullReport()" title="Print Report">
				<i class="fas fa-print"></i> Print Report
			</button>
		</div>
		<form method="get" class="mb-4 d-flex align-items-center" style="gap:16px;">
			<label for="start_date" class="form-label mb-0">Start Date:</label>
			<input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo isset($_GET['start_date']) ? htmlspecialchars($_GET['start_date']) : ''; ?>">
			<label for="end_date" class="form-label mb-0">End Date:</label>
			<input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo isset($_GET['end_date']) ? htmlspecialchars($_GET['end_date']) : ''; ?>">
			<button type="submit" class="btn btn-primary">Filter</button>
		</form>
		<div class="card mb-4">
			<div class="card-header bg-transparent border-0 py-3">
				<h5 class="card-title mb-0 fw-semibold text-dark">
					Financial Report
				</h5>
			</div>
			<div class="card-body">
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
		<div class="card mb-4" style="max-width:1200px; margin:0 auto; width:100%;">
			<div class="card-header bg-transparent border-0 py-3">
				<h5 class="card-title mb-0 fw-semibold text-dark">
					Shares Income (Monthly)
				</h5>
			</div>
			<div class="card-body" id="shares-monthly-section">
				<table class="table table-bordered table-striped mb-0">
					<thead>
						<tr>
							<th>Month</th>
							<th>Bard Share</th>
							<th>Admin Share</th>
							<th>Seller Share</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($monthLabels as $month): ?>
						<tr>
							<td><?php echo htmlspecialchars($month); ?></td>
							<td>₱<?php echo number_format($bardIncomeMonthly[$month], 2); ?></td>
							<td>₱<?php echo number_format($adminIncomeMonthly[$month], 2); ?></td>
							<td>₱<?php echo number_format($sellerIncomeMonthly[$month], 2); ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
		<div class="card mb-4" style="max-width:1200px; margin:0 auto; width:100%;">
			<div class="card-header bg-transparent border-0 py-3">
				<h5 class="card-title mb-0 fw-semibold text-dark">
					Shares Income (Daily)
				</h5>
				</div> <!-- #full-report-content -->
			</div>
			<div class="card-body" id="shares-daily-section">
				<table class="table table-bordered table-striped mb-0">
					<thead>
						<tr>
							<th>Date</th>
							<th>Bard Share</th>
							<th>Admin Share</th>
							<th>Seller Share</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($dayLabels as $day): ?>
						<tr>
							<td><?php echo htmlspecialchars($day); ?></td>
							<td>₱<?php echo number_format($bardIncomeDaily[$day], 2); ?></td>
							<td>₱<?php echo number_format($adminIncomeDaily[$day], 2); ?></td>
							<td>₱<?php echo number_format($sellerIncomeDaily[$day], 2); ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/js/all.min.js"></script>
	<script>
		function printFullReport() {
			var reportContent = document.getElementById('full-report-content').innerHTML;
			var printWindow = window.open('', '', 'height=800,width=1200');
			printWindow.document.write('<html><head><title>Print Report</title>');
			printWindow.document.write('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">');
			printWindow.document.write('</head><body>');
			printWindow.document.write(reportContent);
			printWindow.document.write('</body></html>');
			printWindow.document.close();
			printWindow.focus();
			printWindow.print();
			printWindow.close();
		}
	</script>
</body>
</html>
