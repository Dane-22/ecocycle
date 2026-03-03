<!-- homesidebar.php -->
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
    .sidebar .seller-menu-item.active {
        background-color: #e9ecef;
        color: #28bf4b;
        font-weight: 500;
        border-left: 3px solid #28bf4b;
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
    .filter-section {
        padding: 15px 18px;
        border-top: 1px solid #e9ecef;
        margin-top: 10px;
    }
    .filter-title {
        font-size: 0.95rem;
        font-weight: 600;
        color: #2c786c;
        margin-bottom: 12px;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        cursor: pointer;
        user-select: none;
    }
    .filter-item {
        padding: 8px 12px;
        margin-bottom: 6px;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s ease;
        color: #495057;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .filter-item:hover {
        background-color: #f8f9fa;
        color: #28bf4b;
        padding-left: 16px;
    }
    .filter-item.active {
        background-color: #e9ecef;
        color: #28bf4b;
        font-weight: 500;
        border-left: 3px solid #28bf4b;
    }
    .filter-item i {
        width: 16px;
        text-align: center;
        color: #2c786c;
    }
    .clear-filter {
        margin-top: 10px;
        padding: 8px 12px;
        text-align: center;
        color: #6c757d;
        font-size: 0.85rem;
        cursor: pointer;
        border-radius: 6px;
        transition: all 0.2s ease;
    }
    .clear-filter:hover {
        background-color: #f8f9fa;
        color: #28bf4b;
    }
    .filter-toggle-btn {
        margin-left: auto;
        background: none;
        border: none;
        color: #2c786c;
        font-size: 0.85rem;
        cursor: pointer;
        padding: 4px 8px;
        border-radius: 4px;
        transition: all 0.2s ease;
    }
    .filter-toggle-btn:hover {
        background-color: #f8f9fa;
        color: #28bf4b;
    }
    .category-list {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease-out;
    }
    .category-list.expanded {
        max-height: 1000px;
        transition: max-height 0.5s ease-in;
    }
    .filter-title i.toggle-icon {
        transition: transform 0.3s ease;
    }
    .filter-title.expanded i.toggle-icon {
        transform: rotate(180deg);
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
    <div>
        <!-- No logo or coin at the top -->
        <div class="seller-menu-item" id="home-menu-item">
            <a href="home.php" id="home-link">
                <span class="menu-icon"><i class="fas fa-th-large"></i></span>
                Home
            </a>
        </div>
        <div class="seller-menu-item">
            <a href="myorders.php">
                <span class="menu-icon"><i class="fas fa-shopping-cart"></i></span>
                My Orders
            </a>
        </div>
        <div class="seller-menu-item">
            <a href="purchasehistory.php">
                <span class="menu-icon"><i class="fas fa-history"></i></span>
                My Purchases
            </a>
        </div>
        <div class="seller-menu-item">
            <a href="ecocoins.php">
                <span class="menu-icon"><i class="fas fa-leaf"></i></span>
                Ecocoins
            </a>
        </div>
        <div class="seller-menu-item">
            <a href="customer_service.php">
                <span class="menu-icon"><i class="fas fa-headset"></i></span>
                Customer Service
            </a>
        </div>
        
        <!-- Filter by Seller Section -->
        
        <!-- Filter by Category Section -->
        <div class="filter-section">
            <div class="filter-title" id="categoryFilterTitle" onclick="toggleCategoryList()">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-tags"></i>
                    <span>Filter by Category</span>
                </div>
                <button class="filter-toggle-btn" onclick="event.stopPropagation(); toggleCategoryList();">
                    <i class="fas fa-chevron-down toggle-icon" id="categoryToggleIcon"></i>
                </button>
            </div>
            <div class="category-list" id="categoryList">
                <?php
                if (!isset($categories)) {
                    require_once 'config/database.php';
                    try {
                        $stmt = $pdo->prepare("SELECT * FROM Categories ORDER BY name");
                        $stmt->execute();
                        $categories = $stmt->fetchAll();
                    } catch (PDOException $e) {
                        $categories = [];
                    }
                }
                // Ensure $current_category and $current_seller are defined
                $current_category = isset($_GET['category']) ? intval($_GET['category']) : 0;
                $current_seller = isset($_GET['seller']) ? intval($_GET['seller']) : 0;
                if (!empty($categories)):
                    foreach ($categories as $category):
                        $is_active = ($current_category == $category['category_id']);
                        $filter_url = 'home.php?';
                        if ($current_seller > 0) {
                            $filter_url .= 'seller=' . $current_seller . '&';
                        }
                        $filter_url .= 'category=' . $category['category_id'];
                ?>
                    <div class="filter-item <?php echo $is_active ? 'active' : ''; ?>" 
                         onclick="window.location.href='<?php echo $filter_url; ?>'">
                        <i class="fas fa-tag"></i>
                        <span><?php echo htmlspecialchars($category['name']); ?></span>
                    </div>
                <?php
                    endforeach;
                else:
                ?>
                    <div class="text-muted" style="padding: 8px 12px; font-size: 0.85rem;">
                        No categories available
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<!-- Make sure to include Font Awesome in your main layout for the icons to show -->

<script>
function toggleCategoryList() {
    const categoryList = document.getElementById('categoryList');
    const categoryTitle = document.getElementById('categoryFilterTitle');
    const toggleIcon = document.getElementById('categoryToggleIcon');
    
    if (categoryList && categoryTitle && toggleIcon) {
        const isExpanded = categoryList.classList.contains('expanded');
        
        if (isExpanded) {
            categoryList.classList.remove('expanded');
            categoryTitle.classList.remove('expanded');
        } else {
            categoryList.classList.add('expanded');
            categoryTitle.classList.add('expanded');
        }
    }
}

// Auto-expand category list if a category filter is active
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($current_category > 0): ?>
    const categoryList = document.getElementById('categoryList');
    const categoryTitle = document.getElementById('categoryFilterTitle');
    if (categoryList && categoryTitle) {
        categoryList.classList.add('expanded');
        categoryTitle.classList.add('expanded');
    }
    <?php endif; ?>
});
</script>

<!-- Removed Dropdown JS for Home menu -->
