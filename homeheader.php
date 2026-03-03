<?php
// Start session if not started
if (!isset($_SESSION)) {
  session_start();
}

// Include database connection (if available)
require_once 'config/database.php';

// Determine login state and user type
$is_logged_in = isset($_SESSION['user_id']);
$is_buyer = $is_logged_in && (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'buyer');

// Buyer info (fallback to Guest when not logged in)
$buyer_name = $is_logged_in ? ($_SESSION['fullname'] ?? 'Buyer') : 'Guest';
$buyer_username = $is_logged_in ? ($_SESSION['username'] ?? '') : '';
$buyer_email = $is_logged_in ? ($_SESSION['email'] ?? '') : '';

// Get first letter for avatar
$first_letter = strtoupper(substr($buyer_name, 0, 1));

// Initialize counts
$cart_count = 0;
$messages_count = 0;
$has_seller_account = false;

// Only fetch cart/messages/seller info when we have a logged-in buyer
if ($is_buyer) {
  try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as cart_count FROM Cart WHERE buyer_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_count = $stmt->fetch()['cart_count'] ?? 0;
  } catch (PDOException $e) {
    $cart_count = 0;
  }

  try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as msg_count FROM messages WHERE buyer_id = ? AND sender_type = 'admin' AND status = 'unread'");
    $stmt->execute([$_SESSION['user_id']]);
    $messages_count = $stmt->fetch()['msg_count'] ?? 0;
  } catch (PDOException $e) {
    $messages_count = 0;
  }

  try {
    $stmt = $pdo->prepare("SELECT seller_id FROM Sellers WHERE email = ? OR username = ? LIMIT 1");
    $stmt->execute([$buyer_email, $buyer_username]);
    if ($stmt->fetch()) {
      $has_seller_account = true;
    }
  } catch (PDOException $e) {
    // ignore
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ecocycle NLUC</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="css/mobile.css">
  <style>
    body { 
      padding-top: 80px; 
      font-family: 'Poppins', sans-serif;
      background-color: #f8f9fa;
    }
    
    .buyer-header {
      background: linear-gradient(135deg, #1a5f7a 0%, #2c786c 50%, #28bf4b 100%);
      height: 80px;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      z-index: 1100;
      box-shadow: 0 2px 20px rgba(0,0,0,0.1);
      border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    
    .header-content {
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 30px;
    }
    
    .header-left {
      display: flex;
      align-items: center;
      gap: 20px;
    }
    
    .logo-section {
      display: flex;
      align-items: center;
      gap: 15px;
    }
    
    .logo-img {
      height: 45px;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .brand-text {
      color: white;
      font-weight: 700;
      font-size: 1.3rem;
      margin: 0;
      text-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }
    
    .brand-subtitle {
      color: rgba(255,255,255,0.8);
      font-size: 0.85rem;
      margin: 0;
      font-weight: 400;
    }
    
    .header-center {
      flex: 1;
      display: flex;
      justify-content: center;
      align-items: center;
      max-width: 500px;
    }
    
    .search-container {
      position: relative;
      width: 100%;
      max-width: 4050px;
    }
    
    .search-input {
      background: rgba(255,255,255,0.95) !important;
      border: none;
      height: 42px;
      padding: 8px 45px 8px 15px;
      font-size: 1rem;
      border-radius: 25px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      backdrop-filter: blur(10px);
      transition: all 0.3s e880ase;
    }
    
    .search-input:focus {
      background: rgba(255,255,255,1) !important;
      box-shadow: 0 4px 20px rgba(0,0,0,0.15);
      transform: translateY(-1px);
    }
    
    .search-loading {
      position: absolute;
      right: 45px;
      top: 50%;
      transform: translateY(-50%);
    }
    
    .search-suggestions {
      position: absolute;
      top: 100%;
      left: 0;
      right: 0;
      
      background: white;
      border: 1px solid #dee2e6;
      border-top: none;
      border-radius: 0 0 8px 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      z-index: 1001;
      max-height: 400px;
      overflow-y: auto;
      display: none;
      margin-top: 2px;
    }
    
    .search-suggestion-item {
      padding: 12px 15px;
      border-bottom: 1px solid #f8f9fa;
      cursor: pointer;
      transition: background-color 0.2s ease;
    }
    
    .search-suggestion-item:hover {
      background-color: #f8f9fa;
    }
    
    .search-suggestion-item:last-child {
      border-bottom: none;
    }
    
    .search-icon {
      position: absolute;
      right: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: #6c757d;
      font-size: 1.1rem;
    }
    
    .header-right {
      display: flex;
      align-items: center;
      gap: 20px;
    }
    
    .header-actions {
      display: flex;
      align-items: center;
      gap: 15px;
    }
    
    .cart-btn {
      position: relative;
      background: rgba(255,255,255,0.1);
      border: none;
      color: white;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.3s ease;
      backdrop-filter: blur(10px);
      text-decoration: none;
    }
    
    .cart-btn:hover {
      background: rgba(255,255,255,0.2);
      transform: translateY(-2px);
      color: white;
    }
    
    .cart-badge {
      position: absolute;
      top: -5px;
      right: -5px;
      background: #ff4757;
      color: white;
      border-radius: 50%;
      width: 18px;
      height: 18px;
      font-size: 0.7rem;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
    }
    
    .notification-btn {
      position: relative;
      background: rgba(255,255,255,0.1);
      border: none;
      color: white;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.3s ease;
      backdrop-filter: blur(10px);
    }
    
    .notification-btn:hover {
      background: rgba(255,255,255,0.2);
      transform: translateY(-2px);
      color: white;
    }
    
    .notification-badge {
      position: absolute;
      top: -5px;
      right: -5px;
      background: #ff4757;
      color: white;
      border-radius: 50%;
      width: 18px;
      height: 18px;
      font-size: 0.7rem;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
    }
    
    .buyer-profile {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 8px 16px;
      background: rgba(255,255,255,0.1);
      border-radius: 25px;
      backdrop-filter: blur(10px);
      transition: all 0.3s ease;
      cursor: pointer;
    }
    
    .buyer-profile:hover {
      background: rgba(255,255,255,0.2);
      transform: translateY(-2px);
    }
    
    .buyer-avatar {
      width: 35px;
      height: 35px;
      border-radius: 50%;
      border: 2px solid rgba(255,255,255,0.3);
      background: #28bf4b;
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
      font-size: 1rem;
    }
    
    .buyer-info {
      color: white;
      text-align: left;
    }
    
    .buyer-name {
      font-weight: 600;
      font-size: 0.9rem;
      margin: 0;
      line-height: 1.2;
    }
    
    .buyer-role {
      font-size: 0.75rem;
      color: rgba(255,255,255,0.8);
      margin: 0;
      line-height: 1.2;
    }
    
    .dropdown-menu {
      border: none;
      box-shadow: 0 10px 30px rgba(0,0,0,0.15);
      border-radius: 12px;
      padding: 8px 0;
      min-width: 200px;
      backdrop-filter: blur(10px);
      background: rgba(255,255,255,0.95);
    }
    
    .dropdown-item {
      padding: 10px 20px;
      color: #495057;
      font-weight: 500;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .dropdown-item:hover {
      background: #fff;
      color: #28bf4b;
      transform: translateX(5px);
    }
    
    .dropdown-item i {
      width: 16px;
      opacity: 0.7;
    }
    
    .dropdown-divider {
      margin: 8px 0;
      border-color: rgba(0,0,0,0.1);
    }
    
    .sidebar-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      background: rgba(0,0,0,0.3);
      z-index: 2000;
      display: none;
      transition: opacity 0.3s;
    }
    .sidebar-overlay.active {
      display: block;
      opacity: 1;
    }
    .sidebar-slide {
      position: fixed;
      top: 80px;
      left: 0;
      width: 250px;
      max-width: 90vw;
      height: calc(100vh - 80px);
      background: #fff;
      box-shadow: 2px 0 8px rgba(0,0,0,0.08);
      z-index: 2100;
      transform: translateX(-100%);
      transition: transform 0.3s cubic-bezier(.4,0,.2,1);
      overflow-y: auto;
      padding: 0;
      border-right: 1px solid #dee2e6;
      display: block;
    }
    .sidebar-slide.open {
      transform: translateX(0);
    }
    .sidebar-close-btn {
      background: none;
      border: none;
      font-size: 1.5rem;
      position: absolute;
      top: 10px;
      right: 10px;
      color: #2c786c;
      z-index: 2200;
      display: block;
    }
    .sidebar-title {
      font-weight: bold;
      margin-bottom: 15px;
      color: #2c786c;
    }
    .category-item {
      padding: 8px 0;
      border-bottom: 1px solid #eee;
    }
    .category-item:hover {
      background-color: #e9ecef;
      cursor: pointer;
    }
    .price-filter {
      padding: 15px 0;
    }
    .create-listing-btn {
      background-color: #2c786c;
      color: white;
      margin-bottom: 20px;
      width: 100%;
      padding: 10px;
      font-weight: bold;
    }
    .create-listing-btn:hover {
      background-color: #245c54;
      color: white;
    }
    @media (max-width: 576px) {
      .dropdown-menu {
        min-width: 140px;
        font-size: 0.95rem;
        right: 4px;
        margin-right: 4px;
      }
    }
  </style>
</head>
<body>

<header class="buyer-header">
  <div class="header-content">
    <div class="header-left">
              <div class="logo-section">
          <button id="menuToggle" class="btn btn-link p-0 me-2" style="font-size:1.5rem;color:white;background:none;border:none;">
            <i class="fas fa-bars"></i>
          </button>
          <img src="images/logo.png.png" alt="Recycling Logo" class="logo-img">
        </div>
    </div>
    
    <div class="header-center">
      <div class="search-container" style="margin-left:-60px;">
        <input type="text" class="form-control search-input" id="searchInput" placeholder="Search products..." style="width:100%; min-width:600px; max-width:1400px;">
        <i class="fas fa-search search-icon" style="right: -90px;"></i>
        <div class="search-loading" id="searchLoading" style="display: none;">
          <div class="spinner-border spinner-border-sm text-success" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>
        <!-- Search Suggestions -->
        <div class="search-suggestions" id="searchSuggestions">
          <!-- Suggestions will be populated here -->
        </div>
      </div>
    </div>
    

        

    <div class="header-right">
      <div class="header-actions">
        <div class="dropdown position-relative ms-2">
          <button class="cart-btn d-flex align-items-center" type="button" id="sellerDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false" title="Filter by Seller">
            <span><i class="fas fa-store"></i></span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="sellerDropdownBtn">
            <?php
            require_once 'config/database.php';
            try {
              $stmt = $pdo->prepare("SELECT DISTINCT s.seller_id, s.fullname as seller_name FROM Sellers s JOIN Products p ON s.seller_id = p.seller_id WHERE p.status = 'active' ORDER BY s.fullname");
              $stmt->execute();
              $sellers = $stmt->fetchAll();
            } catch (PDOException $e) {
              $sellers = [];
            }
            $current_seller = isset($_GET['seller']) ? intval($_GET['seller']) : 0;
            if (!empty($sellers)):
              foreach ($sellers as $seller):
                $is_active = ($current_seller == $seller['seller_id']);
                $filter_url = 'home.php?seller=' . $seller['seller_id'];
            ?>
                <li>
                  <a class="dropdown-item d-flex align-items-center <?php echo $is_active ? 'active' : ''; ?>" href="<?php echo $filter_url; ?>">
                    <span class="me-2"><i class="fas fa-store"></i></span>
                    <span><?php echo htmlspecialchars($seller['seller_name']); ?></span>
                  </a>
                </li>
            <?php
              endforeach;
            else:
            ?>
              <li class="dropdown-item-text text-muted">No sellers available</li>
            <?php endif; ?>
            <?php if ($current_seller > 0): ?>
              <li><hr class="dropdown-divider"></li>
              <li>
                <a class="dropdown-item d-flex align-items-center" href="home.php">
                  <span class="me-2"><i class="fas fa-times-circle"></i></span> Clear Seller Filter
                </a>
              </li>
            <?php endif; ?>
          </ul>
        </div>
        <a href="mycart.php" class="cart-btn" title="Shopping Cart">
          <i class="fas fa-shopping-cart"></i>
          <?php if ($cart_count > 0): ?>
            <span class="cart-badge"><?php echo $cart_count; ?></span>
          <?php endif; ?>
        </a>
            <button class="notification-btn" id="openNotificationSidebar" title="Notifications" type="button">
              <i class="fas fa-bell"></i>
            </button>
            <?php
            // --- Notification logic (direct DB call) ---
            $notifications = [];
            if ($is_logged_in) {
              // Redeem notifications
              try {
                $stmt = $pdo->prepare("SELECT br.order_id, bp.name, br.status, br.redeemed_at as date, 'redeem' as type FROM bardproductsredeem br JOIN bardproducts bp ON br.product_id = bp.id WHERE br.user_id = ? AND br.user_type = ? AND (br.status = 'approved' OR br.status = 'rejected') ORDER BY br.redeemed_at DESC LIMIT 10");
                $stmt->execute([$_SESSION['user_id'], $_SESSION['user_type']]);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) { $notifications[] = $r; }
              } catch (PDOException $e) {}
              // Order status notifications from notifications table
              try {
                $stmt = $pdo->prepare("SELECT order_id, product_name as name, status, product_id, created_at as date, type FROM notifications WHERE user_id = ? AND user_type = 'buyer' AND type = 'order' ORDER BY created_at DESC LIMIT 20");
                $stmt->execute([$_SESSION['user_id']]);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $o) { $notifications[] = $o; }
              } catch (PDOException $e) {}
              // Sort and limit
              usort($notifications, function($a, $b) { return strtotime($b['date']) - strtotime($a['date']); });
              $notifications = array_slice($notifications, 0, 20);
            }
            ?>
        <a href="messages.php" class="notification-btn" title="Messages">
          <i class="fas fa-envelope"></i>
          <?php if ($messages_count > 0): ?>
            <span class="cart-badge"><?php echo $messages_count; ?></span>
          <?php endif; ?>
        </a>
        <?php if ($is_buyer): ?>
        <div class="dropdown position-relative">
          <div class="buyer-profile" id="profileDropdown">
              <div class="buyer-avatar">
                <?php echo $first_letter; ?>
              </div>
              <div class="buyer-info">
                <div class="buyer-name"><?php echo htmlspecialchars($buyer_name); ?></div>
                <div class="buyer-role">Buyer</div>
              </div>
          </div>
          <ul class="dropdown-menu dropdown-menu-end" id="profileDropdownMenu" aria-labelledby="profileDropdown">
            <li><div class="dropdown-item-text"><strong><?php echo htmlspecialchars($buyer_name); ?></strong></div></li>
            <li><div class="dropdown-item-text text-muted small"><?php echo htmlspecialchars($buyer_email); ?></div></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> My Profile</a></li>
            <li><hr class="dropdown-divider"></li>
            <?php if ($has_seller_account): ?>
            <li><a class="dropdown-item" href="switch_account.php"><i class="fas fa-retweet me-2"></i> Switch to Seller</a></li>
            <li><hr class="dropdown-divider"></li>
            <?php endif; ?>
            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Log out</a></li>
          </ul>
        </div>
        <?php else: ?>
        <div class="d-flex align-items-center gap-2">
          <a href="login.php" class="btn btn-light btn-sm">Login</a>
          <a href="signup.php" class="btn btn-outline-light btn-sm">Sign up</a>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</header>


<!-- Sidebar Overlay and Slide-in Sidebar -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="sidebar-slide" id="sidebarSlide">
  <?php include 'homesidebar.php'; ?>
</div>

<!-- Notification Sidebar -->
<?php include 'notification_sidebar.html'; ?>

<style>
  .notification-sidebar {
    position: fixed;
    top: 0;
    right: -400px;
    width: 370px;
    height: 100vh;
    background: #fff;
    box-shadow: -2px 0 16px rgba(0,0,0,0.08);
    z-index: 3000;
    transition: right 0.35s cubic-bezier(.4,0,.2,1);
    display: flex;
    flex-direction: column;
    border-left: 1px solid #e0e0e0;
    font-family: 'Poppins', sans-serif;
  }
  .notification-sidebar.open {
    right: 0;
  }
  .notification-sidebar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 22px 24px 12px 24px;
    border-bottom: 1px solid #e0e0e0;
    font-size: 1.3rem;
    font-weight: 600;
    color: #1a5f7a;
    background: #f8f9fa;
  }
  .notification-sidebar-body {
    flex: 1;
    overflow-y: auto;
    padding: 18px 18px 18px 18px;
  }
  .notification-sidebar-item {
    background: #f8f9fa;
    border-radius: 12px;
    margin-bottom: 16px;
    box-shadow: 0 1px 6px rgba(40,191,75,0.04);
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px 16px;
    font-size: 1rem;
  }
    .notification-sidebar-item:hover {
      background: #e6f7ee;
      box-shadow: 0 2px 12px rgba(40,191,75,0.10);
      cursor: pointer;
      transition: background 0.2s, box-shadow 0.2s;
    }
  .notification-sidebar-item:last-child {
    margin-bottom: 0;
  }
  .notification-sidebar-icon {
    font-size: 1.5rem;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #e6f7ee;
    color: #28bf4b;
  }
  .notification-sidebar-item.rejected .notification-sidebar-icon {
    background: #ffeaea;
    color: #ff4757;
  }
  .notification-sidebar-content {
    flex: 1;
  }
  .notification-sidebar-product {
    font-weight: 600;
    color: #2c786c;
    font-size: 1.08rem;
  }
  .notification-sidebar-status {
    font-size: 0.97rem;
    color: #495057;
    margin-bottom: 2px;
  }
  .notification-sidebar-date {
    font-size: 0.85rem;
    color: #888;
  }
  .notification-sidebar-badge {
    font-size: 0.93rem;
    font-weight: 600;
    padding: 5px 13px;
    border-radius: 20px;
    margin-left: 10px;
  }
  .notification-sidebar-badge.approved {
    background: #28bf4b;
    color: #fff;
  }
  .notification-sidebar-badge.rejected {
    background: #ff4757;
    color: #fff;
  }
</style>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Notification Sidebar logic
document.addEventListener('DOMContentLoaded', function() {
  const openBtn = document.getElementById('openNotificationSidebar');
  const sidebar = document.getElementById('notificationSidebar');
  const closeBtn = document.getElementById('closeNotificationSidebar');
  const body = document.getElementById('notificationSidebarBody');
  // Open sidebar
  if (openBtn && sidebar) {
    openBtn.addEventListener('click', function() {
      sidebar.classList.add('open');
      document.body.style.overflow = 'hidden';
      // PHP-rendered notifications
      body.innerHTML = '';
      <?php if (!empty($notifications)): ?>
        // DEBUG: Output notification array to console
        console.log('Notifications:', <?php echo json_encode($notifications); ?>);
        <?php foreach ($notifications as $n): ?>
          <?php
            $icon = $n['type'] === 'redeem' ? 'fa-gift' : 'fa-box';
            if ($n['type'] === 'redeem') {
              $badge = $n['status'] === 'approved' ? '<span class=\"notification-sidebar-badge approved\">Approved</span>' : '<span class=\"notification-sidebar-badge rejected\">Rejected</span>';
              $message = $n['status'] === 'approved' ? 'Your redemption was approved.' : 'Your redemption was rejected.';
              // Link to redeem details (if available)
              $link = 'redeemed-products.php';
            } else {
              $statusMap = [
                'shipped' => ['label' => 'Shipped', 'color' => 'approved', 'msg' => 'Your order was shipped.'],
                'delivered' => ['label' => 'Delivered', 'color' => 'approved', 'msg' => 'Your order was delivered.'],
                'confirmed' => ['label' => 'Confirmed', 'color' => 'approved', 'msg' => 'Order confirmed.'],
                'cancelled' => ['label' => 'Cancelled', 'color' => 'rejected', 'msg' => 'Order was cancelled.'],
                'pending' => ['label' => 'Pending', 'color' => '', 'msg' => 'Order is pending.']
              ];
              $s = $statusMap[$n['status']] ?? ['label' => $n['status'], 'color' => '', 'msg' => $n['status']];
              $badge = '<span class=\"notification-sidebar-badge ' . $s['color'] . '\">' . $s['label'] . '</span>';
              $message = $s['msg'];
              // Link to order details
              $link = isset($n['order_id']) ? ('myorders.php?order_id=' . urlencode($n['order_id'])) : '#';
            }
          ?>
          body.innerHTML += `<a href='<?php echo $link; ?>' class='notification-sidebar-item <?php echo ($n['status'] === 'rejected' || $n['status'] === 'cancelled') ? 'rejected' : ''; ?>' style='text-decoration:none;color:inherit;'>
            <div class='notification-sidebar-icon'><i class='fas <?php echo $icon; ?>'></i></div>
            <div class='notification-sidebar-content'>
              <div class='notification-sidebar-product'><?php echo htmlspecialchars($n['name']); ?></div>
              <div class='notification-sidebar-status'><?php echo $message . ' ' . $badge; ?></div>
              <div class='notification-sidebar-date'><?php echo $n['date']; ?></div>
            </div>
          </a>`;
        <?php endforeach; ?>
      <?php else: ?>
        body.innerHTML = '<div class=\"text-center text-muted py-4\">No notifications found.</div>';
      <?php endif; ?>
    });
  }
  // Close sidebar
  if (closeBtn && sidebar) {
    closeBtn.addEventListener('click', function() {
      sidebar.classList.remove('open');
      document.body.style.overflow = '';
    });
  }
  // Close sidebar when clicking outside
  document.addEventListener('mousedown', function(e) {
    if (sidebar.classList.contains('open') && !sidebar.contains(e.target) && e.target !== openBtn) {
      sidebar.classList.remove('open');
      document.body.style.overflow = '';
    }
  });
});
// Notification dropdown logic
document.addEventListener('DOMContentLoaded', function() {
  const notifBtn = document.getElementById('notificationDropdownBtn');
  const notifMenu = document.getElementById('notificationDropdownMenu');
  const notifLoading = document.getElementById('notif-loading');
  let notifLoaded = false;
  if (notifBtn && notifMenu) {
    notifBtn.addEventListener('click', function() {
      if (!notifLoaded) {
        notifLoading.style.display = 'block';
        fetch('api/notifications-redeem.php')
          .then(res => res.json())
          .then(data => {
            notifLoading.style.display = 'none';
            notifMenu.innerHTML = '';
            if (data.success && data.notifications.length > 0) {
              data.notifications.forEach(n => {
                let badge = n.status === 'approved' ? '<span class="badge bg-success ms-2">Approved</span>' : '<span class="badge bg-danger ms-2">Rejected</span>';
                notifMenu.innerHTML += `<li class='dropdown-item'>
                  <div><strong>${n.name}</strong> ${badge}</div>
                  <div class='small text-muted'>${n.status === 'approved' ? 'Your redemption was approved.' : 'Your redemption was rejected.'}</div>
                  <div class='small text-secondary'>${n.redeemed_at}</div>
                </li>`;
              });
            } else {
              notifMenu.innerHTML = '<li class="dropdown-item-text text-muted text-center">No notifications</li>';
            }
            notifLoaded = true;
          })
          .catch(() => {
            notifLoading.style.display = 'none';
            notifMenu.innerHTML = '<li class="dropdown-item-text text-danger text-center">Failed to load notifications</li>';
          });
      }
    });
    notifBtn.addEventListener('show.bs.dropdown', function() {
      notifLoaded = false;
    });
  }
});
  // Sidebar toggle logic
  document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menuToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const sidebarSlide = document.getElementById('sidebarSlide');
    const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');

    function openSidebar() {
      sidebarOverlay.classList.add('active');
      sidebarSlide.classList.add('open');
      document.body.style.overflow = 'hidden';
    }
    function closeSidebar() {
      sidebarOverlay.classList.remove('active');
      sidebarSlide.classList.remove('open');
      document.body.style.overflow = '';
    }
    if(menuToggle) menuToggle.addEventListener('click', openSidebar);
    if(sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);
    if(sidebarCloseBtn) sidebarCloseBtn.addEventListener('click', closeSidebar);

    // Improved profile dropdown logic (only if elements exist)
    var profileImg = document.getElementById('profileDropdown');
    var dropdownMenu = document.getElementById('profileDropdownMenu');
    if (profileImg && dropdownMenu) {
      function closeDropdown() {
        dropdownMenu.classList.remove('show');
      }
      function openDropdown() {
        dropdownMenu.classList.add('show');
      }
      function toggleDropdown(e) {
        e.preventDefault();
        dropdownMenu.classList.toggle('show');
      }
      // For both click and touch
      profileImg.addEventListener('click', toggleDropdown);
      profileImg.addEventListener('touchend', function(e) {
        e.preventDefault();
        toggleDropdown(e);
      });
      // Close dropdown when clicking outside
      document.addEventListener('click', function(event) {
        if (!profileImg.contains(event.target) && !dropdownMenu.contains(event.target)) {
          closeDropdown();
        }
      });
      // Keyboard accessibility
      profileImg.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          toggleDropdown(e);
        }
      });
    }
    
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const searchSuggestions = document.getElementById('searchSuggestions');
    const searchLoading = document.getElementById('searchLoading');
    
    if (searchInput) {
      let searchTimeout;
      
      // Function to perform search
      function performSearch(query) {
        if (query.trim().length > 0) {
          // Use a reliable path calculation
          const path = window.location.pathname;
          let basePath = '';
          
          // Extract directory path (remove filename)
          if (path.lastIndexOf('/') > 0) {
            basePath = path.substring(0, path.lastIndexOf('/') + 1);
          } else if (path === '/') {
            basePath = '/';
          } else {
            basePath = './';
          }
          
          // Navigate to search results page
          const searchUrl = basePath + 'search-results.php?q=' + encodeURIComponent(query.trim());
          window.location.href = searchUrl;
        }
      }
      
      // Handle Enter key press
      searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          searchSuggestions.style.display = 'none';
          performSearch(this.value);
        }
      });
      
      searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        // Clear previous timeout
        clearTimeout(searchTimeout);
        
        if (query.length < 2) {
          searchSuggestions.style.display = 'none';
          searchLoading.style.display = 'none';
          return;
        }
        
        // Show loading
        searchLoading.style.display = 'block';
        
        // Debounce search
        searchTimeout = setTimeout(() => {
          // Get API path relative to current page location
          const path = window.location.pathname;
          let basePath = '';
          if (path.lastIndexOf('/') > 0) {
            basePath = path.substring(0, path.lastIndexOf('/') + 1);
          } else {
            basePath = './';
          }
          const apiPath = basePath + 'api/search-products.php';
          // Fetch search suggestions from API
          fetch(`${apiPath}?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
              searchLoading.style.display = 'none';
              
              if (data.success && data.products.length > 0) {
                searchSuggestions.innerHTML = data.products.map(product => 
                  `<div class="search-suggestion-item" data-product-id="${product.product_id}">
                    <div class="d-flex align-items-center">
                      <img src="${product.image_url || 'images/placeholder.png'}" 
                           alt="${product.name}" 
                           style="width: 40px; height: 40px; object-fit: cover; border-radius: 5px; margin-right: 10px;">
                      <div class="flex-grow-1">
                        <div style="font-weight: 500; color: #333;">${product.name}</div>
                        <div style="font-size: 0.85rem; color: #666;">₱${parseFloat(product.price).toFixed(2)}</div>
                      </div>
                    </div>
                  </div>`
                ).join('');
                searchSuggestions.style.display = 'block';
              } else {
                searchSuggestions.innerHTML = '<div class="search-suggestion-item text-muted">No products found</div>';
                searchSuggestions.style.display = 'block';
              }
            })
            .catch(error => {
              console.error('Search error:', error);
              searchLoading.style.display = 'none';
              searchSuggestions.style.display = 'none';
            });
        }, 300);
      });
      
      // Hide suggestions when clicking outside
      document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchSuggestions.contains(e.target)) {
          searchSuggestions.style.display = 'none';
        }
      });
      
      // Handle suggestion clicks
      searchSuggestions.addEventListener('click', function(e) {
        const suggestionItem = e.target.closest('.search-suggestion-item');
        if (suggestionItem) {
          const productId = suggestionItem.getAttribute('data-product-id');
          if (productId) {
            // Navigate to product details page
            window.location.href = `product-details.php?id=${productId}`;
          }
        }
      });
    }
  });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>