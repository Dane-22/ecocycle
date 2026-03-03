<?php 
include 'sellerheader.php';
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

try {
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
	$stmt = $pdo->prepare("
		SELECT COUNT(DISTINCT o.order_id) as total_orders, 
				 SUM(oi.quantity * oi.price) as total_sales
		FROM Orders o 
		JOIN Order_Items oi ON o.order_id = oi.order_id 
		JOIN Products p ON oi.product_id = p.product_id 
		WHERE p.seller_id = ? AND o.status != 'cancelled' $date_filter
	");
	$stmt->execute($params);
	$order_stats = $stmt->fetch();
	$total_orders = $order_stats['total_orders'] ?? 0;
	$total_sales = ($order_stats['total_sales'] ?? 0) * 0.95;
	$stmt = $pdo->prepare("
		SELECT COUNT(DISTINCT o.buyer_id) as total_buyers
		FROM Orders o 
		JOIN Order_Items oi ON o.order_id = oi.order_id 
		JOIN Products p ON oi.product_id = p.product_id 
		WHERE p.seller_id = ? AND o.status != 'cancelled' $date_filter
	");
	$stmt->execute($params);
	$total_buyers = $stmt->fetch()['total_buyers'] ?? 0;
	$stmt = $pdo->prepare("
		SELECT SUM(oi.quantity) as sold_products
		FROM Order_Items oi
		JOIN Products p ON oi.product_id = p.product_id
		JOIN Orders o ON oi.order_id = o.order_id
		WHERE p.seller_id = ? AND o.status != 'cancelled' $date_filter
	");
	$stmt->execute($params);
	$sold_products = $stmt->fetch()['sold_products'] ?? 0;
	$average_rating = 0;
	$stmt = $pdo->prepare("
		SELECT o.order_id, o.created_at, o.status, o.total_amount,
				 b.fullname as buyer_name,
				 p.name as product_name,
				 oi.quantity, oi.price
		FROM Orders o 
		JOIN Order_Items oi ON o.order_id = oi.order_id 
		JOIN Products p ON oi.product_id = p.product_id 
		JOIN Buyers b ON o.buyer_id = b.buyer_id
		WHERE p.seller_id = ? AND o.status != 'cancelled' $date_filter
		ORDER BY o.created_at DESC
		LIMIT 5
	");
	$stmt->execute($params);
	$recent_orders = $stmt->fetchAll();
	$stmt = $pdo->prepare("
			SELECT p.name, p.price, p.image_url, COUNT(oi.order_item_id) as sales_count
			FROM Products p 
			LEFT JOIN Order_Items oi ON p.product_id = oi.product_id 
			LEFT JOIN Orders o ON oi.order_id = o.order_id AND o.status != 'cancelled'
			WHERE p.seller_id = ? AND p.status = 'active'
			GROUP BY p.product_id
			ORDER BY sales_count DESC
			LIMIT 5
	");
	$stmt->execute([getCurrentUserId()]);
	$top_products = $stmt->fetchAll();
	$stmt = $pdo->prepare("
			SELECT status, COUNT(*) as count
			FROM Products 
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
	$monthly_sales = [];
	$months = [];
	$month_names = ['Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'];
	$current_year = date('Y');
	$current_month = date('n');
	$start_year = ($current_month >= 10) ? $current_year : $current_year - 1;
	for ($i = 0; $i < 12; $i++) {
		$month_num = (($i + 10 - 1) % 12) + 1;
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
			FROM Orders o
			JOIN Order_Items oi ON o.order_id = oi.order_id
			JOIN Products p ON oi.product_id = p.product_id
			WHERE p.seller_id = ? AND o.status != 'cancelled' AND MONTH(o.created_at) = ? AND YEAR(o.created_at) = ? $monthly_date_filter
		");
		$stmt->execute($monthly_params);
		$monthly_sales[] = (float)($stmt->fetch()['sales'] ?? 0);
	}
	// Prepare daily sales report (filtered by date range if set, else last 7 days)
	$daily_sales = [];
	if (!empty($start_date) && !empty($end_date)) {
		$period_start = new DateTime($start_date);
		$period_end = new DateTime($end_date);
		$period_end->setTime(0, 0, 0); // Ensure end date is inclusive
		$interval = new DateInterval('P1D');
		$period = new DatePeriod($period_start, $interval, $period_end->modify('+1 day'));
		foreach ($period as $date) {
			$date_str = $date->format('Y-m-d');
			$stmt = $pdo->prepare("
				SELECT SUM(oi.quantity * oi.price) as sales, COUNT(DISTINCT o.order_id) as orders
				FROM Orders o
				JOIN Order_Items oi ON o.order_id = oi.order_id
				JOIN Products p ON oi.product_id = p.product_id
				WHERE p.seller_id = ? AND o.status != 'cancelled' AND DATE(o.created_at) = ?
			");
			$stmt->execute([getCurrentUserId(), $date_str]);
			$row = $stmt->fetch();
			$daily_sales[] = [
				'date' => $date_str,
				'sales' => (float)($row['sales'] ?? 0),
				'orders' => (int)($row['orders'] ?? 0)
			];
		}
	} else {
		$today = new DateTime();
		for ($i = 6; $i >= 0; $i--) {
			$date = clone $today;
			$date->modify("-$i days");
			$date_str = $date->format('Y-m-d');
			$stmt = $pdo->prepare("
				SELECT SUM(oi.quantity * oi.price) as sales, COUNT(DISTINCT o.order_id) as orders
				FROM Orders o
				JOIN Order_Items oi ON o.order_id = oi.order_id
				JOIN Products p ON oi.product_id = p.product_id
				WHERE p.seller_id = ? AND o.status != 'cancelled' AND DATE(o.created_at) = ?
			");
			$stmt->execute([getCurrentUserId(), $date_str]);
			$row = $stmt->fetch();
			$daily_sales[] = [
				'date' => $date_str,
				'sales' => (float)($row['sales'] ?? 0),
				'orders' => (int)($row['orders'] ?? 0)
			];
		}
	}

	// Prepare list of clients (buyers)
	$clients = [];
	$stmt = $pdo->prepare("
		SELECT b.buyer_id, b.fullname, b.email, COUNT(DISTINCT o.order_id) as total_orders, SUM(oi.quantity * oi.price) as total_spent
		FROM Buyers b
		JOIN Orders o ON b.buyer_id = o.buyer_id
		JOIN Order_Items oi ON o.order_id = oi.order_id
		JOIN Products p ON oi.product_id = p.product_id
		WHERE p.seller_id = ? AND o.status != 'cancelled'
		GROUP BY b.buyer_id, b.fullname, b.email
		ORDER BY total_spent DESC
	");
	$stmt->execute([getCurrentUserId()]);
	$clients = $stmt->fetchAll();

	$monthly_sales_no_zeros = array_map(function($v) {
		return $v == 0 ? null : $v;
	}, $monthly_sales);
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
		$daily_sales = [];
		$clients = [];
	}
?>

<!DOCTYPE html>
<html lang="en">
<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Seller Reports</title>
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
<div class="container-fluid">
	<div class="row">
		<!-- Main Content Column -->
		<div class="col-12 main-content">
			<div class="container-lg py-4">
				<!-- Financial Statement Report: now full width -->
				<div class="row">
					<div class="col-12">
						<div class="chart-container mt-4" id="financial-report-section">
							<div class="d-flex justify-content-between align-items-center mb-3">
								<div class="section-title mb-0" style="font-size:2rem; font-weight:700; color:#145c2c;">Financial Statement Report (Monthly)</div>
								<div>
									<button class="btn btn-light btn-sm" style="background: #f1faea; color: #222; border: none; font-weight: 400;" onclick="printFinancialReport()">
										<i class="fas fa-print me-1"></i>Print Report
									</button>
									<button class="btn btn-success btn-sm" style="background: #5cb85c; color: #fff; border: none; font-weight: 400;" onclick="saveFinancialReportPDF()">
										<i class="fas fa-file-pdf me-1"></i>Save as PDF
									</button>
								</div>
							</div>
							<!-- Date Filter Form -->
							<form method="get" class="row g-2 mb-3 align-items-end">
								<div class="col-auto">
									<label for="start_date" class="form-label mb-0">Start Date:</label>
									<input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
								</div>
								<div class="col-auto">
									<label for="end_date" class="form-label mb-0">End Date:</label>
									<input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
								</div>
								<div class="col-auto">
									<button type="submit" class="btn btn-success">Filter</button>
									<a href="seller-reports.php" class="btn btn-secondary">Reset</a>
								</div>
							</form>
							<div class="table-responsive">
								<table class="table table-bordered table-hover">
									<thead class="table-light">
										<tr>
											<th>Month</th>
											<th>Cost of Goods Sold</th>
											<th>Revenue (Sales) (₱)</th>
											<th>Gross Income (₱)</th>
											<th>Net Income (₱)</th>
										</tr>
									</thead>
									<tbody>
										<?php 
										$total_revenue = 0;
										$total_cogs = 0;
										$total_gross_profit = 0;
										// Initialize total_net_profit to avoid undefined variable warning
										$total_net_profit = 0;
										foreach ($months as $idx => $month): 
											$revenue = $monthly_sales[$idx];
											if ($revenue > 0): 
												$cogs = $revenue * 0.70;
												$gross_profit = $revenue - $cogs;
												// Deduct 30% for utilities and transportation
												$net_profit = $gross_profit * 0.70;
												$total_revenue += $revenue;
												$total_cogs += $cogs;
												$total_gross_profit += $gross_profit;
												$total_net_profit += $net_profit;
										?>
											<tr>
												<td><?php echo htmlspecialchars($month); ?></td>
												<td>₱<?php echo number_format($cogs, 2); ?></td>
												<td>₱<?php echo number_format($revenue, 2); ?></td>
												<td>₱<?php echo number_format($gross_profit, 2); ?></td>
												<td>₱<?php echo number_format($net_profit, 2); ?></td>
											</tr>
										<?php 
											endif;
										endforeach; 
										// Calculate total net profit for the footer
										if (!isset($total_net_profit)) $total_net_profit = 0;
										?>
									</tbody>
									<tfoot class="table-group-divider fw-bold">
										<tr>
											<td>Total</td>
											<td>₱<?php echo number_format($total_cogs, 2); ?></td>
											<td>₱<?php echo number_format($total_revenue, 2); ?></td>
											<td>₱<?php echo number_format($total_gross_profit, 2); ?></td>
											<td>₱<?php echo number_format($total_net_profit, 2); ?></td>
										</tr>
									</tfoot>
								</table>
							</div>
						</div>
					</div>
				</div>
				<!-- Daily Sales Report Section -->
				<div class="row">
					<div class="col-12">
						<div class="chart-container mt-4" id="daily-sales-report-section">
							<div class="d-flex justify-content-between align-items-center mb-3">
								<div class="section-title mb-0" style="font-size:2rem; font-weight:700; color:#145c2c;">Daily Sales Report (Last 7 Days)</div>
								<div>
									<button class="btn btn-light btn-sm" style="background: #f1faea; color: #222; border: none; font-weight: 400;" onclick="printDailySalesReport()">
										<i class="fas fa-print me-1"></i>Print Report
									</button>
									<button class="btn btn-success btn-sm" style="background: #5cb85c; color: #fff; border: none; font-weight: 400;" onclick="saveDailySalesReportPDF()">
										<i class="fas fa-file-pdf me-1"></i>Save as PDF
									</button>
								</div>
							</div>
							<div class="table-responsive">
								<table class="table table-bordered table-hover">
									<thead class="table-light">
										<tr>
											<th>Date</th>
											<th>Number of Orders</th>
											<th>Sales (₱)</th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($daily_sales as $row): ?>
											<tr>
												<td><?php echo htmlspecialchars($row['date']); ?></td>
												<td><?php echo $row['orders']; ?></td>
												<td>₱<?php echo number_format($row['sales'], 2); ?></td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>

				<!-- Seller Report Section -->
				<div class="row">
					<div class="col-12">
						<div class="chart-container mt-4" id="seller-report-section">
					<div class="d-flex justify-content-between align-items-center mb-3">
						<div class="section-title mb-0" style="font-size:2rem; font-weight:700; color:#145c2c;">Seller Report</div>
						<div>
							  <button class="btn btn-light btn-sm" style="background: #f1faea; color: #222; border: none; font-weight: 400;" onclick="printSellerReport()">
								<i class="fas fa-print me-1"></i>Print Report
							</button>
							  <button class="btn btn-success btn-sm" style="background: #5cb85c; color: #fff; border: none; font-weight: 400;" onclick="saveSellerReportPDF()">
								<i class="fas fa-file-pdf me-1"></i>Save as PDF
							</button>
						</div>
					</div>
					<div class="table-responsive">
						<table class="table table-bordered table-hover">
							<thead class="table-light">
								<tr>
									<th>Report Item</th>
									<th>Details</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td>List of Suppliers</td>
									<td>
										<ul>
											<li><?php echo htmlspecialchars($seller['fullname']); ?></li>
										</ul>
									</td>
								</tr>
								<tr>
									<td>List of Clients (Buyers)</td>
									<td>
										<div class="table-responsive">
											<table class="table table-sm table-bordered">
												<thead class="table-light">
													<tr>
														<th>Name</th>
														<th>Email</th>
														<th>Total Orders</th>
														<th>Total Spent (₱)</th>
													</tr>
												</thead>
												<tbody>
													<?php if (!empty($clients)): ?>
														<?php foreach ($clients as $client): ?>
															<tr>
																<td><?php echo htmlspecialchars($client['fullname']); ?></td>
																<td><?php echo htmlspecialchars($client['email']); ?></td>
																<td><?php echo $client['total_orders']; ?></td>
																<td>₱<?php echo number_format($client['total_spent'], 2); ?></td>
															</tr>
														<?php endforeach; ?>
													<?php else: ?>
														<tr><td colspan="4">No clients found.</td></tr>
													<?php endif; ?>
												</tbody>
											</table>
										</div>
									</td>
								</tr>
								<tr>
									<td>List of Producers</td>
									<td>
										<ol>
											<?php
											$producerStmt = $pdo->prepare("SELECT DISTINCT producers FROM products WHERE seller_id = ? AND producers IS NOT NULL AND producers != ''");
											$producerStmt->execute([getCurrentUserId()]);
											$producers = $producerStmt->fetchAll(PDO::FETCH_COLUMN);
											if (!empty($producers)) {
												foreach ($producers as $producer) {
													echo '<li>' . htmlspecialchars($producer) . '</li>';
												}
											} else {
												echo '<li>' . htmlspecialchars($seller['fullname']) . '</li>';
											}
											?>
										</ol>
									</td>
								</tr>
								<tr>
									<td>Costs Inventory (Sold)</td>
									<td>
										<?php
										$costStmt = $pdo->prepare("SELECT SUM(oi.quantity * oi.price) as sold_total FROM order_items oi JOIN products p ON oi.product_id = p.product_id WHERE p.seller_id = ? AND oi.status != 'cancelled'");
										$costStmt->execute([getCurrentUserId()]);
										$soldTotal = $costStmt->fetch()['sold_total'] ?? 0;
										echo '₱' . number_format($soldTotal, 2);
										?>
									</td>
								</tr>
								<tr>
									<td>Volume of Materials Recovered (by type)</td>
									<td>
										<ul>
											<?php
											$volStmt = $pdo->prepare("SELECT c.name as type, SUM(p.stock_quantity) as volume FROM products p JOIN categories c ON p.category_id = c.category_id WHERE p.seller_id = ? GROUP BY c.name");
											$volStmt->execute([getCurrentUserId()]);
											foreach ($volStmt->fetchAll() as $row) {
												echo '<li>' . htmlspecialchars($row['type']) . ': ' . htmlspecialchars($row['volume']) . ' units</li>';
											}
											?>
										</ul>
									</td>
								</tr>
								<tr>
									<td>Selling Recycled Materials</td>
									<td>
										<ul>
											<?php
											$recycledStmt = $pdo->prepare("SELECT name, price FROM products WHERE seller_id = ? AND (name LIKE '%recycled%' OR description LIKE '%recycled%')");
											$recycledStmt->execute([getCurrentUserId()]);
											foreach ($recycledStmt->fetchAll() as $prod) {
												echo '<li>' . htmlspecialchars($prod['name']) . ': ₱' . number_format($prod['price'], 2) . '</li>';
											}
											?>
										</ul>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
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
		const imgProps = pdf.getImageProperties(imgData);
		const pdfWidth = pageWidth - 20;
		const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
		pdf.addImage(imgData, 'PNG', 10, 10, pdfWidth, pdfHeight);
		pdf.save('Financial_Statement_Report.pdf');
	});
}
function printDailySalesReport() {
	var printContents = document.getElementById('daily-sales-report-section').innerHTML;
	var originalContents = document.body.innerHTML;
	document.body.innerHTML = printContents;
	window.print();
	document.body.innerHTML = originalContents;
	location.reload();
}
function saveDailySalesReportPDF() {
	var report = document.getElementById('daily-sales-report-section');
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
		pdf.save('daily_sales_report.pdf');
	});
}
</script>
</body>
</html>
