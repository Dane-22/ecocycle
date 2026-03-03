<?php
include 'adminheader.php';
include 'adminsidebar.php';
require_once '../config/database.php';

// Fetch all transaction logs
try {
    $stmt = $pdo->query("SELECT log_id, user_id, user_type, action, description, ip_address, user_agent, created_at FROM transaction_logs ORDER BY created_at DESC");
    $logs = $stmt->fetchAll();
} catch (PDOException $e) {
    $logs = [];
}
?>

<div class="container transaction-logs-fixed-panel" style="padding-top:40px;">
  <div class="card p-4">
    <h4 class="mb-4">Transaction Logs</h4>
    <table class="table table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th>Log ID</th>
          <th>User ID</th>
          <th>User Type</th>
          <th>Action</th>
          <th>Description</th>
          <th>IP Address</th>
          <th>User Agent</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($logs)): ?>
        <tr><td colspan="8" class="text-center">No transaction logs found.</td></tr>
        <?php else: foreach ($logs as $log): ?>
        <tr>
          <td><strong><?= htmlspecialchars($log['log_id']) ?></strong></td>
          <td><?= htmlspecialchars($log['user_id']) ?></td>
          <td><?= htmlspecialchars(ucfirst($log['user_type'])) ?></td>
          <td><?= htmlspecialchars($log['action']) ?></td>
          <td><?= htmlspecialchars($log['description']) ?></td>
          <td><?= htmlspecialchars($log['ip_address']) ?></td>
          <td style="max-width:200px; word-break:break-all; font-size:12px; color:#666;">
            <?= htmlspecialchars($log['user_agent']) ?>
          </td>
          <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($log['created_at']))) ?></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<style>
.transaction-logs-fixed-panel {
  position: fixed;
  left: 280px;
  top: 60px;
  width: 1200px;
  max-width: 99vw;
  z-index: 200;
  background: #f8f9fa;
  overflow-y: auto;
  height: calc(100vh - 80px);
  bottom: 0;
  border-radius: 12px;
}
@media (max-width: 992px) {
  .transaction-logs-fixed-panel {
    left: 0;
    width: 98vw;
    max-width: 98vw;
  }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 