<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Bard Dashboard - Ecocycle Nluc</title>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
      body { 
        padding-top: 80px; 
        font-family: 'Poppins', sans-serif;
        background-color: #f8f9fa;
      }
      
      .bard-header {
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
      
      .bard-profile {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 12px;
        background: rgba(255,255,255,0.1);
        border-radius: 20px;
        backdrop-filter: blur(10px);
        transition: all 0.3s ease;
        cursor: pointer;
      }
      
      .bard-profile:hover {
        background: rgba(255,255,255,0.2);
        transform: translateY(-2px);
      }
      
      .bard-avatar {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        border: 2px solid rgba(255,255,255,0.3);
        object-fit: cover;
      }
      
      .bard-info {
        color: white;
        text-align: left;
      }
      
      .bard-name {
        font-weight: 600;
        font-size: 0.9rem;
        margin: 0;
        line-height: 1.2;
      }
      
      .bard-role {
        font-size: 0.75rem;
        color: rgba(255,255,255,0.8);
        margin: 0;
        line-height: 1.2;
      }
      
      .dropdown-menu {
        border: none;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        border-radius: 12px;
        padding: 6px 0;
        min-width: 160px;
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
        width: 14px;
        opacity: 0.7;
      }
      
      .dropdown-item svg {
        width: 14px;
        height: 14px;
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
        
        .bard-info {
          display: none;
        }
        
        .header-actions {
          gap: 10px;
        }
      }
    </style>
  </head>
  <body>
    <header class="bard-header">
      <div class="header-content">
        <div class="header-left">
          <div class="logo-section">
              <img src="../images/logo.png.png" alt="Ecocycle Logo" class="logo-img">
          </div>
        </div>
        
        <div class="header-center">
          <h4 class="page-title"><i class="fas fa-magic me-2"></i>Bard Dashboard</h4>
        </div>
        <div class="header-right">
          <div class="header-actions">
            <!-- Bard Profile -->
            <div class="bard-profile dropdown" data-bs-toggle="dropdown" aria-expanded="false" style="cursor:pointer; padding: 6px 12px; gap: 8px;">
              <i class="fas fa-magic bard-avatar" style="font-size: 28px; color: white; width: 28px; height: 28px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.3); object-fit: cover;"></i>
              <div class="bard-info">
                <p class="bard-name" style="font-size: 0.9rem; font-weight: 600; margin: 0; line-height: 1.2;">Bard User</p>
                <p class="bard-role" style="font-size: 0.75rem; color: rgba(255,255,255,0.8); margin: 0; line-height: 1.2;">Bard Admin</p>
              </div>
            </div>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="adminlogout.php"><svg class="svg-inline--fa fa-right-from-bracket" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="right-from-bracket" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" data-fa-i2svg=""><path fill="currentColor" d="M96 480h64C177.7 480 192 465.7 192 448S177.7 416 160 416H96c-17.67 0-32-14.33-32-32V128c0-17.67 14.33-32 32-32h64C177.7 96 192 81.67 192 64S177.7 32 160 32H96C42.98 32 0 74.98 0 128v256C0 437 42.98 480 96 480zM504.8 238.5l-144.1-136c-6.975-6.578-17.2-8.375-26-4.594c-8.803 3.797-14.51 12.47-14.51 22.05l-.0918 72l-128-.001c-17.69 0-32.02 14.33-32.02 32v64c0 17.67 14.34 32 32.02 32l128 .001l.0918 71.1c0 9.578 5.707 18.25 14.51 22.05c8.803 3.781 19.03 1.984 26-4.594l144.1-136C514.4 264.4 514.4 247.6 504.8 238.5z"></path></svg> Log Out</a></li>
            </ul>
          </div>
        </div>
      </div>
    </header>
  </body>
</html> 