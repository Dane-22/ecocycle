<?php
// Bard Redemption Verification Page
require_once '../config/database.php';
require_once '../config/phpmailer_config.php';
// Handle Approve/Reject actions
if (isset($_GET['approve'])) {
  $redeem_id = intval($_GET['approve']);
  
  // Get redemption details
  $stmt = $pdo->prepare('
    SELECT r.*, b.fullname as buyer_name, b.email as buyer_email, p.name as product_name
    FROM bardproductsredeem r
    JOIN buyers b ON r.user_id = b.buyer_id
    JOIN bardproducts p ON r.product_id = p.id
    WHERE r.redeem_id = ?
  ');
  $stmt->execute([$redeem_id]);
  $redemption = $stmt->fetch(PDO::FETCH_ASSOC);
  
  // Update status to approved
  $pdo->prepare('UPDATE bardproductsredeem SET status="approved" WHERE redeem_id=?')->execute([$redeem_id]);
  
  // Send approval email to user
  if ($redemption) {
    sendRedemptionApprovedEmail(
      $redemption['buyer_email'],
      $redemption['buyer_name'],
      $redemption['product_name'],
      $redemption['order_id'],
      $redemption['ecocoins_spent']
    );
  }
  
  header('Location: bardredemptionverification.php');
  exit();
}
if (isset($_GET['reject'])) {
  $redeem_id = intval($_GET['reject']);
  
  // Get redemption details
  $stmt = $pdo->prepare('
    SELECT r.*, b.fullname as buyer_name, b.email as buyer_email, p.name as product_name
    FROM bardproductsredeem r
    JOIN buyers b ON r.user_id = b.buyer_id
    JOIN bardproducts p ON r.product_id = p.id
    WHERE r.redeem_id = ?
  ');
  $stmt->execute([$redeem_id]);
  $redemption = $stmt->fetch(PDO::FETCH_ASSOC);
  
  // Update status to cancelled
  $pdo->prepare('UPDATE bardproductsredeem SET status="cancelled" WHERE redeem_id=?')->execute([$redeem_id]);
  
  // Refund the EcoCoins back to user
  if ($redemption) {
    $pdo->prepare('UPDATE buyers SET ecocoins_balance = ecocoins_balance + ? WHERE buyer_id = ?')
      ->execute([$redemption['ecocoins_spent'], $redemption['user_id']]);
    
    // Send rejection email to user
    sendRedemptionRejectedEmail(
      $redemption['buyer_email'],
      $redemption['buyer_name'],
      $redemption['product_name'],
      $redemption['order_id'],
      $redemption['ecocoins_spent']
    );
  }
  
  header('Location: bardredemptionverification.php');
  exit();
}
include 'bardheader.php';
include 'bardsidebar.php';
// Fetch pending bardproductsredeem
$stmt = $pdo->prepare('
  SELECT r.*, b.fullname as buyer_name, b.email as buyer_email, b.phone_number
  FROM bardproductsredeem r
  JOIN buyers b ON r.user_id = b.buyer_id
  WHERE r.status = "pending"
  ORDER BY r.created_at ASC
');
$stmt->execute();
$orders = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Redemption Verification</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<style>
  @media (min-width: 768px) {
    .main-content {
      margin-left: 280px; /* width of .admin-sidebar */
      padding: 32px 24px 24px 24px;
    }
  }
  @media (max-width: 767px) {
    .main-content {
      margin-left: 0;
      padding: 16px 8px;
    }
  }
  .users-fixed-panel {
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
    .users-fixed-panel {
      left: 0;
      width: 98vw;
      max-width: 98vw;
    }
  }
  
  /* Enhanced Product Image Styling */
  .product-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #e9ecef;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    cursor: pointer;
  }
  .product-image:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  }
  
  /* Enhanced Button Styling */
  .btn-enhanced {
    border-radius: 6px;
    font-weight: 600;
    letter-spacing: 0.2px;
    transition: all 0.3s ease;
    border: none;
    position: relative;
    overflow: hidden;
    text-transform: uppercase;
    font-size: 0.75rem;
    padding: 0.35rem 0.75rem;
    min-width: 85px;
    max-width: 85px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  
  .btn-enhanced::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
  }
  
  .btn-enhanced:hover::before {
    left: 100%;
  }
  
  .btn-view {
    background: linear-gradient(135deg, #17a2b8, #138496);
    color: white;
    box-shadow: 0 4px 15px rgba(23, 162, 184, 0.3);
  }
  
  .btn-view:hover {
    background: linear-gradient(135deg, #138496, #117a8b);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(23, 162, 184, 0.4);
  }
  
  .btn-approve {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
  }
  
  .btn-approve:hover {
    background: linear-gradient(135deg, #218838, #1e7e34);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
  }
  
  .btn-reject {
    background: linear-gradient(135deg, #dc3545, #e74c3c);
    color: white;
    box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
  }
  
  .btn-reject:hover {
    background: linear-gradient(135deg, #c82333, #bd2130);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
  }
  
  .btn-group-enhanced {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    align-items: center;
  }
  
  .btn-group-enhanced .btn {
    margin: 0;
    width: 85px;
    height: 28px;
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
  }
  
  /* Modal Button Enhancements */
  .modal-btn {
    border-radius: 8px;
    font-weight: 600;
    letter-spacing: 0.3px;
    padding: 0.5rem 1rem;
    font-size: 0.8rem;
    transition: all 0.3s ease;
    border: none;
    position: relative;
    overflow: hidden;
    min-width: 120px;
    max-width: 120px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  
  .modal-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.6s;
  }
  
  .modal-btn:hover::before {
    left: 100%;
  }
  
  .modal-btn-approve {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
  }
  
  .modal-btn-approve:hover {
    background: linear-gradient(135deg, #218838, #1e7e34);
    color: white;
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(40, 167, 69, 0.5);
  }
  
  .modal-btn-reject {
    background: linear-gradient(135deg, #dc3545, #e74c3c);
    color: white;
    box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
  }
  
  .modal-btn-reject:hover {
    background: linear-gradient(135deg, #c82333, #bd2130);
    color: white;
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(220, 53, 69, 0.5);
  }
  
  /* Responsive button adjustments */
  @media (max-width: 768px) {
    .btn-group-enhanced {
      flex-direction: column;
      gap: 0.25rem;
    }
    
    .btn-group-enhanced .btn {
      margin-bottom: 0;
      width: 75px;
      height: 26px;
      font-size: 0.65rem;
      padding: 0.2rem 0.4rem;
    }
    
    .modal-btn {
      min-width: 100px;
      max-width: 100px;
      height: 36px;
      padding: 0.4rem 0.8rem;
      font-size: 0.75rem;
    }
  }
  
  /* Order Details Modal Styling */
  .order-details-modal .modal-content {
    border-radius: 16px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.18);
    border: none;
  }
  .order-details-modal .modal-header {
    border-bottom: 1px solid #e9ecef;
    background: #f8f9fa;
    border-radius: 16px 16px 0 0;
  }
  .order-details-modal .modal-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: #198754;
  }
  .order-details-modal .modal-body {
    padding: 1.5rem;
  }
  .order-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 0.75rem;
    margin-bottom: 1.5rem;
  }
  .order-info-item {
    background: #f8f9fa;
    padding: 0.75rem;
    border-radius: 6px;
    border-left: 3px solid #198754;
  }
  .order-info-label {
    font-size: 0.75rem;
    color: #6c757d;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    margin-bottom: 0.2rem;
  }
  .order-info-value {
    font-size: 0.9rem;
    font-weight: 600;
    color: #212529;
  }
  
  @media (max-width: 768px) {
    .order-info-grid {
      grid-template-columns: 1fr;
    }
  }
  
  /* Order Card Styling */
  .order-card {
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    margin-bottom: 1.5rem;
  }
  
  .order-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
  }
  
  .order-card .card-header {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    border-radius: 12px 12px 0 0;
    border: none;
    padding: 1rem 1.5rem;
  }
  
  .order-card .card-body {
    padding: 1.5rem;
  }
  
  .product-list-item {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    border-left: 3px solid #28a745;
  }
</style>

<div class="main-content">
    <h2 style="color:#1a5f7a; font-weight:700;">Redemption Verification</h2>
    <div class="container users-fixed-panel" style="padding-top:40px;">
      <div class="card p-4">
        <?php if (empty($orders)): ?>
          <div class="text-center">
            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No pending redemption requests found.</h5>
            <p class="text-muted">All redemption requests have been processed.</p>
          </div>
        <?php else: ?>
          <?php foreach ($orders as $order): ?>
            <div class="order-card card">
              <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <!-- Remove Redemption #number, show only name -->
                    <strong><i class="fas fa-user me-2"></i><?php echo htmlspecialchars($order['buyer_name']); ?></strong>
                  </div>
                  <div class="text-end">
                    <small><?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></small>
                  </div>
                </div>
                <div class="mt-2">
                  <span class="ms-3"><i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($order['buyer_email']); ?></span>
                </div>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-md-8">
                    <div class="order-info-grid">
                      <div class="order-info-item">
                        <div class="order-info-label">Phone Number</div>
                        <div class="order-info-value"><?php echo htmlspecialchars($order['phone_number']); ?></div>
                      </div>
                      <div class="order-info-item">
                        <div class="order-info-label">Status</div>
                        <div class="order-info-value">
                          <span class="badge bg-success"><i class="fas fa-coins me-1"></i>Pending</span>
                        </div>
                      </div>
                      <div class="order-info-item">
                        <div class="order-info-label">Total EcoCoins Spent</div>
                        <div class="order-info-value">
                            <?php echo isset($order['ecocoins_spent']) ? number_format($order['ecocoins_spent'], 2) : '0.00'; ?>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="d-flex flex-column gap-2">
                      <button type="button" class="btn btn-view btn-enhanced" onclick="viewOrderDetails(<?php echo htmlspecialchars(json_encode($order)); ?>)">
                        <i class="fas fa-eye me-1"></i> View Details
                      </button>
                      <button type="button" class="btn btn-approve btn-enhanced" onclick="approveOrder(<?php echo $order['redeem_id']; ?>, '<?php echo htmlspecialchars($order['buyer_name']); ?>')">
                        <i class="fas fa-check me-1"></i> Approve
                      </button>
                      <button type="button" class="btn btn-reject btn-enhanced" onclick="rejectOrder(<?php echo $order['redeem_id']; ?>, '<?php echo htmlspecialchars($order['buyer_name']); ?>')">
                        <i class="fas fa-times me-1"></i> Reject
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade order-details-modal" id="orderDetailsModal" tabindex="-1" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="orderDetailsModalLabel">Redemption Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <!-- Remove user and product image column -->
          <!--
          <div class="col-md-4 text-center mb-3">
            <img id="modalUserImage" src="" alt="User Image" class="rounded-circle border" style="width:120px;height:120px;object-fit:cover;">
            <div class="mt-3">
              <img id="modalProductImage" src="" alt="Product Image" class="product-image" style="width:90px;height:90px;">
              <div id="modalProductName" class="mt-2 fw-bold"></div>
            </div>
          </div>
          -->
          <div class="col-md-12">
            <div class="order-info-grid">
              <!-- Remove Redemption ID from modal -->
              <!--
              <div class="order-info-item">
                <div class="order-info-label">Redemption ID</div>
                <div class="order-info-value" id="modalOrderId"></div>
              </div>
              -->
              <div class="order-info-item">
                <div class="order-info-label">Customer Name</div>
                <div class="order-info-value" id="modalCustomerName"></div>
              </div>
              <div class="order-info-item">
                <div class="order-info-label">Email</div>
                <div class="order-info-value" id="modalCustomerEmail"></div>
              </div>
              <div class="order-info-item">
                <div class="order-info-label">Phone</div>
                <div class="order-info-value" id="modalCustomerPhone"></div>
              </div>
              <div class="order-info-item">
                <div class="order-info-label">Redemption Date</div>
                <div class="order-info-value" id="modalOrderDate"></div>
              </div>
              <div class="order-info-item">
                <div class="order-info-label">EcoCoins Spent</div>
                <div class="order-info-value" id="modalEcoCoinsSpent"></div>
              </div>
            </div>
          </div>
        </div>
        <div class="d-flex justify-content-center gap-3 mt-4">
          <a id="modalApproveBtn" href="#" class="btn modal-btn modal-btn-approve">
            <i class="fas fa-check me-2"></i>Approve Redemption
          </a>
          <a id="modalRejectBtn" href="#" class="btn modal-btn modal-btn-reject">
            <i class="fas fa-times me-2"></i>Reject Redemption
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function viewOrderDetails(order) {
  // Set modal content
  document.getElementById('orderDetailsModalLabel').textContent = 'Redemption Details';
  // document.getElementById('modalOrderId').textContent = order.redeem_id; // Remove Redemption ID
  document.getElementById('modalCustomerName').textContent = order.buyer_name;
  document.getElementById('modalCustomerEmail').textContent = order.buyer_email;
  document.getElementById('modalCustomerPhone').textContent = order.phone_number;
  document.getElementById('modalOrderDate').textContent = new Date(order.created_at).toLocaleString();
  document.getElementById('modalEcoCoinsSpent').textContent = order.ecocoins_spent + ' EcoCoins';

  // Set action buttons
  document.getElementById('modalApproveBtn').href = '?approve=' + order.redeem_id;
  document.getElementById('modalRejectBtn').href = '?reject=' + order.redeem_id;

  // Show modal
  const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
  modal.show();
}

// SweetAlert2 confirmation functions
function approveOrder(redeemId, customerName) {
  Swal.fire({
    title: "Approve Redemption?",
    text: `Are you sure you want to approve the redemption for "${customerName}"?`,
    showCancelButton: true,
    confirmButtonColor: "#28a745",
    cancelButtonColor: "#6c757d",
    confirmButtonText: "Yes, approve it!",
    cancelButtonText: "Cancel"
  }).then((result) => {
    if (result.isConfirmed) {
      window.location.href = `?approve=${redeemId}`;
    }
  });
}

function rejectOrder(redeemId, customerName) {
  Swal.fire({
    title: "Reject Redemption?",
    text: `Are you sure you want to reject the redemption for "${customerName}"?`,
    showCancelButton: true,
    confirmButtonColor: "#dc3545",
    cancelButtonColor: "#6c757d",
    confirmButtonText: "Yes, reject it!",
    cancelButtonText: "Cancel"
  }).then((result) => {
    if (result.isConfirmed) {
      window.location.href = `?reject=${redeemId}`;
    }
  });
}

// Add click handlers for approve/reject buttons in modal
document.addEventListener('DOMContentLoaded', function() {
  document.getElementById('modalApproveBtn').addEventListener('click', function(e) {
    e.preventDefault();
    const customerName = document.getElementById('modalCustomerName').textContent;
    Swal.fire({
      title: "Approve Redemption?",
      text: `Are you sure you want to approve the redemption for "${customerName}"?`,
      showCancelButton: true,
      confirmButtonColor: "#28a745",
      cancelButtonColor: "#6c757d",
      confirmButtonText: "Yes, approve it!",
      cancelButtonText: "Cancel"
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = this.href;
      }
    });
  });

  document.getElementById('modalRejectBtn').addEventListener('click', function(e) {
    e.preventDefault();
    const customerName = document.getElementById('modalCustomerName').textContent;
    Swal.fire({
      title: "Reject Redemption?",
      text: `Are you sure you want to reject the redemption for "${customerName}"?`,
      showCancelButton: true,
      confirmButtonColor: "#dc3545",
      cancelButtonColor: "#6c757d",
      confirmButtonText: "Yes, reject it!",
      cancelButtonText: "Cancel"
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = this.href;
      }
    });
  });});</script></body></html>