<!-- sellersidebar.php -->
<style>
    .sidebar {
        background-color: #ffffff !important;
        height: 100vh;
        width: 100%;
        position: relative;
        overflow-y: auto;
        left: 0;
        top: 0;
        z-index: 1000;
        border: none;
        color: #000000;
        font-size: 1rem;
    }
    .sidebar * {
        background-color: #ffffff !important;
    }
    .sidebar .seller-menu-item {
        background-color: #ffffff !important;
        font-size: 1rem;
        font-weight: 400;
        padding: 10px 18px;
    }
    .sidebar .seller-menu-item:hover {
        background-color: #f8f9fa !important;
        color: #28bf4b;
        padding-left: 22px;
    }
    .sidebar-title {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font-size: 1.1rem;
        font-weight: 500;
        margin: 0 0 10px 0;
        color: #2c786c;
        text-align: center;
        padding: 0 0 6px 0;
        border-bottom: 1px solid #e9ecef;
        letter-spacing: 0.5px;
    }
    .sidebar-title img {
        height: 32px;
        width: auto;
        display: inline-block;
        vertical-align: middle;
    }
    .seller-menu-item {
        padding: 10px 18px;
        border-bottom: 1px solid #f0f0f0;
        font-weight: 400;
        display: flex;
        align-items: center;
        transition: all 0.3s ease;
        color: #2c786c;
        background: transparent;
        font-size: 1rem;
    }
    .seller-menu-item:hover {
        background-color: #f8f9fa;
        cursor: pointer;
        color: #28bf4b;
        padding-left: 22px;
    }
    .seller-menu-item.active {
        background-color: #e9ecef;
        color: #28bf4b;
        font-weight: 500;
        border-left: 3px solid #28bf4b;
    }
    .menu-icon {
        margin-right: 14px;
        width: 22px;
        text-align: center;
        color: #2c786c;
    }
    .seller-menu-item a {
        text-decoration: none;
        color: inherit;
        display: flex;
        align-items: center;
        width: 100%;
    }
    .sidebar-section-title {
        font-size: 1rem;
        font-weight: 600;
        color: #2c786c;
        margin: 20px 0 10px 10px;
        letter-spacing: 0.5px;
    }
    .sidebar-divider {
        border-top: 1px solid #e9ecef;
        margin: 15px 0;
    }
    .sidebar-profile {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
        padding: 10px 15px;
        background: #f8f9fa;
        border-radius: 8px;
    }
    .sidebar-profile-img {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        border: 2px solid #28bf4b;
        margin-right: 12px;
    }
    .sidebar-profile-info {
        display: flex;
        flex-direction: column;
    }
    .sidebar-profile-name {
        font-weight: bold;
        color: #2c786c;
        font-size: 1.05rem;
    }
    .sidebar-profile-role {
        font-size: 0.9rem;
        color: #495057;
    }
    @media (max-width: 992px) {
        .sidebar {
            width: 100vw;
        }
        .sidebar-title img {
            height: 24px;
        }
    }
    @media (max-width: 768px) {
        .sidebar {
            width: 100vw;
            height: auto;
            position: relative;
        }
        .sidebar-title img {
            height: 20px;
        }
    }
</style>

<div class="sidebar">
   
        
        <div class="mb-2">
            <div class="seller-menu-item">
                <a href="seller-dashboard.php">
                    <span class="menu-icon"><i class="fas fa-tachometer-alt"></i></span>
                    Dashboard
                </a>
            </div>
            <div class="seller-menu-item">
                <a href="seller-manageproducts.php">
                    <span class="menu-icon"><i class="fas fa-th-list"></i></span>
                    Manage Products
                </a>
            </div>
            <div class="seller-menu-item">
                <a href="seller-manageorders.php">
                    <span class="menu-icon"><i class="fas fa-box"></i></span>
                    Manage Orders
                </a>
            </div>
            <div class="seller-menu-item">
                <a href="sales-history.php">
                    <span class="menu-icon"><i class="fas fa-receipt"></i></span>
                    Sales History
                </a>
            </div>
            <div class="seller-menu-item">
                <a href="ecocoins.php">
                    <span class="menu-icon"><i class="fas fa-coins"></i></span>
                    Ecocoins
                </a>
            </div>
            <div class="seller-menu-item">
                <a href="seller-createproduct.php">
                    <span class="menu-icon"><i class="fas fa-plus"></i></span>
                    Create Product
                </a>
            </div>
                <div class="seller-menu-item">
                    <a href="seller-reports.php">
                        <span class="menu-icon"><i class="fas fa-chart-bar"></i></span>
                        Reports
                    </a>
                </div>
        </div>
</div>
