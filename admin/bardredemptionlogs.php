<?php
require_once '../config/database.php';
include 'bardheader.php';
include 'bardsidebar.php';

$stmt = $pdo->prepare('SELECT r.*, b.fullname, b.email, bp.name as product_name FROM bardproductsredeem r JOIN buyers b ON r.user_id = b.buyer_id JOIN bardproducts bp ON r.product_id = bp.id ORDER BY r.created_at DESC');
$stmt->execute();
$transactions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Redeem Transaction Logs</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .redeem-logs-panel { position: fixed; left: 280px; top: 60px; width: calc(100% - 280px); z-index: 200; background: #f8f9fa; overflow-y: auto; height: calc(100vh - 80px); bottom: 0; }
    @media (max-width: 992px) { .redeem-logs-panel { left: 0; width: 100%; } }
    .badge-pending { background: #ffc107; } .badge-approved { background: #28a745; } .badge-declined { background: #dc3545; }
    .redeem-header { display: flex; align-items: center; padding: 12px 0; border-bottom: 3px solid linear-gradient(135deg, #28a745, #20c997); }
    .redeem-header i { font-size: 1.5rem; margin-right: 12px; color: #28a745; }
    .redeem-header h4 { margin: 0; color: #1a5f7a; font-weight: 700; }
  </style>
</head>
<body>
<div class="container redeem-logs-panel" style="padding-top:40px;">
  <div class="card p-4">
    <div class="redeem-header">
      <i class="fas fa-receipt"></i>
      <h4>Redeem Transaction Logs</h4>
    </div>
    <table class="table table-hover align-middle" style="margin-top: 16px;">
      <thead class="table-light">
        <tr>
          <th>User</th>
          <th>Product</th>
          <th>EcoCoins</th>
          <th>Status</th>
          <th>Date</th>
          <th>Order ID</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($transactions)): ?>
        <tr><td colspan="6" class="text-center">No transactions found.</td></tr>
        <?php else: foreach ($transactions as $t): 
          $status_class = match($t['status']) {
            'approved' => 'badge-approved',
            'declined' => 'badge-declined',
            default => 'badge-pending'
          };
        ?>
        <tr>
          <td><strong><?= htmlspecialchars($t['fullname']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($t['email']) ?></small></td>
          <td><?= htmlspecialchars($t['product_name']) ?> ×<?= $t['quantity'] ?></td>
          <td><i class="fas fa-coins"></i> <?= number_format($t['ecocoins_spent']) ?></td>
          <td><span class="badge text-white <?= $status_class ?>"><?= ucfirst($t['status'] ?: 'pending') ?></span></td>
          <td><?= date('M j g:i A', strtotime($t['created_at'])) ?></td>
          <td><code><?= htmlspecialchars($t['order_id']) ?></code></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
