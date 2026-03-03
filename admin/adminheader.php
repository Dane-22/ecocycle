<?php include '../config/admin_session_check.php'; ?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Admin Dashboard - Ecocycle Nluc</title>
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
    <link rel="stylesheet" type="text/css" href="../css/vendor.css">
    <link rel="stylesheet" type="text/css" href="../style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&family=Open+Sans:ital,wght@0,400;0,600;0,700;1,400;1,600;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
      body { 
        padding-top: 80px; 
        font-family: 'Poppins', sans-serif;
        background-color: #f8f9fa;
      }
      
      .admin-header {
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
      }
      
      .page-title {
        color: white;
        font-weight: 600;
        font-size: 1.2rem;
        margin: 0;
        text-shadow: 0 1px 3px rgba(0,0,0,0.2);
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
      
      .admin-profile {
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
      
      .admin-profile:hover {
        background: rgba(255,255,255,0.2);
        transform: translateY(-2px);
      }
      
      .admin-avatar {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        border: 2px solid rgba(255,255,255,0.3);
        object-fit: cover;
      }
      
      .admin-info {
        color: white;
        text-align: left;
      }
      
      .admin-name {
        font-weight: 600;
        font-size: 0.9rem;
        margin: 0;
        line-height: 1.2;
      }
      
      .admin-role {
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
        font-weight: 400;
        font-size: 0.95rem;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 10px;
      }
      .dropdown-item:hover, .dropdown-item:focus {
        background: #fff;
        color: #28bf4b;
        font-weight: 400;
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
      
      .breadcrumb-nav {
        background: transparent;
        padding: 0;
        margin: 0;
      }
      
      .breadcrumb-item {
        color: rgba(255,255,255,0.8);
        font-size: 0.85rem;
      }
      
      .breadcrumb-item.active {
        color: white;
        font-weight: 600;
      }
      
      .breadcrumb-item + .breadcrumb-item::before {
        color: rgba(255,255,255,0.6);
      }
      
      .dropdown-menu .dropdown-item:hover {
        background: #fff !important;
        color: #000 !important;
        font-weight: 400 !important;
        transform: translateX(5px);
      }
      
      @media (max-width: 768px) {
        .header-content {
          padding: 0 15px;
        }
        
        .brand-text {
          font-size: 1.1rem;
        }
        
        .page-title {
          font-size: 1rem;
        }
        
        .admin-info {
          display: none;
        }
        
        .header-actions {
          gap: 10px;
        }
      }
    </style>
  </head>
  <body>
    <header class="admin-header">
      <div class="header-content">
        <div class="header-left">
          <div class="logo-section">
              <img src="../images/logo.png.png" alt="Ecocycle Logo" class="logo-img">
          </div>
        </div>
        
        <div class="header-center">
          <h4 class="page-title">Admin Dashboard</h4>
        </div>
        
        <div class="header-right">
          <div class="header-actions">
            <!-- Admin Profile Dropdown - FIXED -->
            <div class="dropdown position-relative">
              <div class="admin-profile" id="profileDropdown" tabindex="0" aria-haspopup="true" aria-expanded="false" style="cursor:pointer;">
                <i class="fas fa-user-shield admin-avatar" style="font-size: 35px; color: white;"></i>
                <div class="admin-info">
                  <p class="admin-name"><?php echo htmlspecialchars($_SESSION['fullname'] ?? 'Admin User'); ?></p>
                  <p class="admin-role"><?php echo htmlspecialchars($_SESSION['role'] ?? 'Super Admin'); ?></p>
                </div>
              </div>
              <ul class="dropdown-menu dropdown-menu-end" id="profileDropdownMenu" aria-labelledby="profileDropdown">
                <li><a class="dropdown-item" href="adminreceivemessages.php"><i class="fas fa-envelope"></i> Received Messages</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="adminlogout.php"><i class="fas fa-sign-out-alt"></i> Log Out</a></li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </header>
    
    <!-- Bootstrap JS Bundle (CRITICAL - was missing!) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Improved Profile Dropdown Logic -->
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        // Add logout confirmation
        const logoutLink = document.querySelector('a[href="adminlogout.php"]');
        if (logoutLink) {
          logoutLink.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to logout?')) {
              e.preventDefault();
            }
          });
        }

        // Improved profile dropdown logic
        var profileImg = document.getElementById('profileDropdown');
        var dropdownMenu = document.getElementById('profileDropdownMenu');
        if (profileImg && dropdownMenu) {
          function closeDropdown() {
            dropdownMenu.classList.remove('show');
            profileImg.setAttribute('aria-expanded', 'false');
          }
          function openDropdown() {
            dropdownMenu.classList.add('show');
            profileImg.setAttribute('aria-expanded', 'true');
          }
          function toggleDropdown(e) {
            e.preventDefault();
            dropdownMenu.classList.toggle('show');
            var expanded = dropdownMenu.classList.contains('show');
            profileImg.setAttribute('aria-expanded', expanded ? 'true' : 'false');
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
            if (e.key === 'Escape') {
              closeDropdown();
              profileImg.blur();
            }
          });
        }
      });
    </script>
  </body>
</html>