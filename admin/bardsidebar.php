<?php
// Bard Sidebar Component
?>
<style>
  .bard-sidebar {
    background: linear-gradient(180deg, #1a5f7a 0%, #2c786c 100%);
    border-right: none;
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
  
  .bard-sidebar::-webkit-scrollbar {
    width: 6px;
  }
  
  .bard-sidebar::-webkit-scrollbar-track {
    background: rgba(255,255,255,0.1);
  }
  
  .bard-sidebar::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.3);
    border-radius: 3px;
  }
  
  .bard-menu-item {
    padding: 16px 20px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    font-weight: 500;
    display: flex;
    align-items: center;
    transition: all 0.3s ease;
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    position: relative;
    font-size: 0.95rem;
    white-space: nowrap;
  }
  
  .bard-menu-item:hover {
    background: rgba(255,255,255,0.1);
    color: #ffffff;
    transform: translateX(5px);
    border-left: 4px solid #28bf4b;
    padding-left: 24px;
  }
  
  .bard-menu-item.active {
    /* background: rgba(255,255,255,0.15); */
    /* color: #ffffff; */
    /* border-left: 4px solid #ffd700; */
    /* padding-left: 24px; */
    /* font-weight: 600; */
    /* box-shadow: inset 0 0 20px rgba(255,255,255,0.05); */
  }
  
  .bard-menu-item i {
    width: 24px;
    margin-right: 6px;
    font-size: 1.2rem;
    opacity: 0.9;
    flex-shrink: 0;
  }
  
  .bard-sidebar-title {
    font-size: 1.4rem;
    font-weight: 700;
    margin: 30px 20px 35px 20px;
    color: #ffffff;
    text-align: center;
    padding-bottom: 15px;
    border-bottom: 2px solid rgba(255,255,255,0.2);
    position: relative;
  }
  
  .bard-sidebar-title::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 50%;
    transform: translateX(-50%);
    width: 60px;
    height: 2px;
    background: #28bf4b;
  }
  
  .bard-logo-section {
    padding: 25px 20px 15px 20px;
    text-align: center;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    margin-bottom: 20px;
  }
  
  .bard-logo {
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
  
  .bard-brand {
    font-size: 1.1rem;
    font-weight: 600;
    color: #ffffff;
    margin-bottom: 5px;
  }
  
  .bard-subtitle {
    font-size: 0.8rem;
    color: rgba(255,255,255,0.7);
    font-weight: 400;
  }
  
  .bard-menu-item .badge {
    margin-left: auto;
    background: #28bf4b;
    color: white;
    font-size: 0.7rem;
    padding: 2px 8px;
    border-radius: 10px;
  }
  
  .bard-menu-text {
    margin-left: 6px;
    display: inline-block;
  }
  
  @media (max-width: 992px) {
    .bard-sidebar {
      width: 250px;
    }
  }
  
  @media (max-width: 768px) {
    .bard-sidebar {
      width: 100%;
      height: auto;
      position: relative;
      transform: translateX(-100%);
    }
    
    .bard-sidebar.show {
      transform: translateX(0);
    }
  }
</style>

<div class="bard-sidebar pt-0">
  <!-- Logo and Brand Section -->
  <div class="bard-logo-section">
    <div class="bard-logo">
      <i class="fas fa-magic"></i>
    </div>
    
  </div>
  
  <!-- Bard Navigation Menu -->
  <a href="barddashboard.php" class="bard-menu-item">
    <i class="fas fa-tachometer-alt"></i>
    <span class="bard-menu-text">Dashboard</span>
  </a>
  
  <a href="bardaddproducts.php" class="bard-menu-item">
    <i class="fas fa-gift"></i>
    <span class="bard-menu-text">Add Products</span>
  </a>
  
  <a href="bardredemptionverification.php" class="bard-menu-item">
    <i class="fas fa-check-circle"></i>
    <span class="bard-menu-text">Redemption Verification</span>
  </a>
  
  <a href="bardredemptionlogs.php" class="bard-menu-item">
    <i class="fas fa-receipt"></i>
    <span class="bard-menu-text">Redeem Transaction Logs</span>
  </a>
  
  <a href="managebardproducts.php" class="bard-menu-item">
    <i class="fas fa-boxes"></i>
    <span class="bard-menu-text">Manage Products</span>
  </a>
  
</div> 