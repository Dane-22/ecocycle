t<style>
  .admin-sidebar {
    background: linear-gradient(180deg, #1a5f7a 0%, #2c786c 100%);
    border-right: 2px solid #28bf4b;
    height: 100vh;
    position: fixed;
    overflow-y: auto;
    width: 280px;
    left: 0;
    top: 0;
    z-index: 1050;
    box-shadow: 4px 0 20px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    font-family: 'Poppins', sans-serif;
  }
  
  .admin-sidebar::-webkit-scrollbar {
    width: 6px;
  }
  
  .admin-sidebar::-webkit-scrollbar-track {
    background: rgba(255,255,255,0.1);
  }
  
  .admin-sidebar::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.3);
    border-radius: 3px;
  }
  
  .admin-menu-item {
    padding: 16px 20px;
    border-bottom: 1px solid #28bf4b;
    border-left: 2px solid transparent;
    font-weight: 500;
    display: flex;
    align-items: center;
    transition: all 0.3s ease;
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    position: relative;
    font-size: 0.95rem;
  }
  
  .admin-menu-item:hover {
    background: rgba(255,255,255,0.1);
    color: #ffffff;
    transform: translateX(5px);
    border-left: 4px solid #28bf4b;
    padding-left: 24px;
  }
  
  .admin-menu-item.active {
    background: rgba(255,255,255,0.15);
    color: #ffffff;
    border-left: 4px solid #28bf4b;
    box-shadow: 0 0 0 2px #28bf4b inset;
    padding-left: 24px;
    font-weight: 600;
    box-shadow: inset 0 0 20px rgba(255,255,255,0.05);
  }
  
  .admin-menu-item i {
    width: 20px;
    margin-right: 20px;
    font-size: 1.1rem;
    opacity: 0.9;
  }
  
  .admin-menu-item svg {
    width: 20px;
    margin-right: 20px;
    font-size: 1.1rem;
    opacity: 0.9;
    flex-shrink: 0;
  }
  
  .admin-sidebar-title {
    font-size: 1.4rem;
    font-weight: 700;
    margin: 30px 20px 35px 20px;
    color: #ffffff;
    text-align: center;
    padding-bottom: 15px;
    border-bottom: 2px solid rgba(255,255,255,0.2);
    position: relative;
  }
  
  .admin-sidebar-title::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 50%;
    transform: translateX(-50%);
    width: 60px;
    height: 2px;
    background: #28bf4b;
  }
  
  .admin-logo-section {
    padding: 25px 20px 15px 20px;
    text-align: center;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    margin-bottom: 20px;
  }
  
  .admin-logo {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    background: rgba(255,255,255,0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 10px;
    font-size: 1.5rem;
    color: #28bf4b;
  }
  
  .admin-brand {
    font-size: 1.1rem;
    font-weight: 600;
    color: #ffffff;
    margin-bottom: 5px;
  }
  
  .admin-subtitle {
    font-size: 0.8rem;
    color: rgba(255,255,255,0.7);
    font-weight: 400;
  }
  
  .admin-menu-item .badge {
    margin-left: auto;
    background: #28bf4b;
    color: white;
    font-size: 0.7rem;
    padding: 2px 8px;
    border-radius: 10px;
  }
  
  @media (max-width: 992px) {
    .admin-sidebar {
      width: 250px;
    }
  }
  
  @media (max-width: 768px) {
    .admin-sidebar {
      width: 100%;
      height: auto;
      position: relative;
      transform: translateX(-100%);
    }
    
    .admin-sidebar.show {
      transform: translateX(0);
    }
  }
</style>

<div class="admin-sidebar pt-0">
  <!-- Logo and Brand Section -->
  <div class="admin-logo-section">
    <div class="admin-logo">
      <i class="fas fa-leaf"></i>
    </div>
  </div>
  
  <!-- Combined Navigation Menu -->
  <a href="dashboard.php" class="admin-menu-item">
    <i class="fas fa-tachometer-alt"></i>
    Dashboard
  </a>
  
  <a href="sharesdashboard.php" class="admin-menu-item">
    <i class="fas fa-chart-pie"></i>
    Shares Dashboard
  </a>

  <a href="manageusers.php" class="admin-menu-item">
    <i class="fas fa-users"></i>
    Manage Users
  </a>
  
  <a href="admin-verifysellerproducts.php" class="admin-menu-item">
    <i class="fas fa-check-circle"></i>
    Verify Seller Products
  </a>

  <a href="admintransactionlogs.php" class="admin-menu-item">
    <i class="fas fa-history"></i>
    Transaction Logs
  </a>

    <a href="adminreports.php" class="admin-menu-item">
      <i class="fas fa-file-alt"></i>
      Reports
    </a>

  <a href="adminsettings.php" class="admin-menu-item">
    <i class="fas fa-cogs"></i>
    Settings
  </a>
</div> 