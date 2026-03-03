<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Eco Cycle: Marketplace</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    .main-header {
      background-color: rgb(40, 191, 75);
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      z-index: 1000;
      height: 80px;
      transition: height 0.3s ease;
    }
    body {
      padding-top: 90px;
      transition: padding-top 0.3s ease;
    }
    .search-input {
      background-color: white !important;
      border: 1px solid #ced4da;
      height: 42px;
      padding: 8px 15px;
      font-size: 1rem;
      border-radius: 8px;
      transition: all 0.3s ease;
    }
    .search-input:focus {
      box-shadow: 0 0 0 0.2rem rgba(40, 191, 75, 0.25);
      border-color: #28a745;
    }
    .navbar-nav .nav-link {
      color: white !important;
      border: 1px solid white;
      padding: 6px 16px;
      border-radius: 30px;
      transition: all 0.3s ease;
      height: 42px;
      line-height: 28px;
      font-size: 1rem;
      display: flex;
      align-items: center;
      margin: 0 4px;
      white-space: nowrap;
    }
    .navbar-nav .nav-link:hover {
      background-color: rgba(255, 255, 255, 0.1);
      transform: translateY(-1px);
    }
    .main-header .row {
      height: 80px;
    }
    .logo-img {
      height: 50px;
      transition: height 0.3s ease;
    }
    .profile-img {
      width: 42px;
      height: 42px;
      border-radius: 50%;
      object-fit: cover;
    }
    .navbar-toggler {
      border: none;
      padding: 4px 8px;
      background: transparent;
      transition: all 0.3s ease;
    }
    .navbar-toggler svg {
      width: 25px;
      height: 25px;
      fill: white;
      transition: transform 0.3s ease;
    }
    .navbar-toggler:hover svg {
      transform: scale(1.1);
    }
    
    /* Mobile-specific styles */
    @media (max-width: 768px) {
      .main-header {
        height: 60px;
      }
      body {
        padding-top: 70px;
      }
      .logo-img {
        height: 35px;
      }
      .search-input {
        height: 36px;
        font-size: 14px;
        padding: 6px 12px;
      }
      .navbar-nav .nav-link {
        height: 36px;
        padding: 4px 12px;
        font-size: 12px;
        margin: 0 2px;
      }
      .navbar-toggler svg {
        width: 20px;
        height: 20px;
      }
      .profile-img {
        width: 32px;
        height: 32px;
      }
      
      /* Mobile search container */
      .search-container {
        position: relative;
        width: 100%;
      }
      
      /* Mobile navigation adjustments */
      .mobile-nav {
        display: flex;
        align-items: center;
        gap: 8px;
      }
      
      .desktop-nav {
        display: none;
      }
    }
    
    /* Tablet styles */
    @media (min-width: 769px) and (max-width: 1024px) {
      .search-input {
        height: 40px;
        font-size: 0.95rem;
      }
      .navbar-nav .nav-link {
        height: 40px;
        padding: 5px 14px;
        font-size: 0.95rem;
      }
    }
    
    /* Offcanvas mobile menu enhancements */
    .offcanvas {
      background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    }
    
    .offcanvas-header {
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      padding: 1rem 1.5rem;
    }
    
    .offcanvas-title {
      color: white;
      font-weight: 600;
    }
    
    .offcanvas-body {
      padding: 1rem 1.5rem;
    }
    
    .mobile-menu-item {
      display: flex;
      align-items: center;
      padding: 12px 16px;
      margin: 4px 0;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 8px;
      color: white;
      text-decoration: none;
      transition: all 0.3s ease;
    }
    
    .mobile-menu-item:hover {
      background: rgba(255, 255, 255, 0.2);
      color: white;
      transform: translateX(4px);
    }
    
    .mobile-menu-item i {
      margin-right: 12px;
      width: 20px;
      text-align: center;
    }
    
    /* Sidebar toggle button */
    .sidebar-toggle {
      background: transparent;
      border: 1px solid rgba(255, 255, 255, 0.3);
      color: white;
      padding: 6px 10px;
      border-radius: 6px;
      transition: all 0.3s ease;
    }
    
    .sidebar-toggle:hover {
      background: rgba(255, 255, 255, 0.1);
      border-color: rgba(255, 255, 255, 0.5);
    }
    
    /* Search suggestions */
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
      max-height: 300px;
      overflow-y: auto;
      display: none;
    }
    
    .search-suggestion-item {
      padding: 10px 15px;
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
    
    /* Loading animation */
    .search-loading {
      display: none;
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
    }
    
    .spinner {
      width: 16px;
      height: 16px;
      border: 2px solid #f3f3f3;
      border-top: 2px solid #28a745;
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
  </style>
</head>
<body>

<header class="main-header">
  <div class="container-fluid h-100">
    <div class="row h-100 border-bottom align-items-center">
      <!-- Logo and Mobile Toggle -->
      <div class="col-sm-4 col-lg-2 text-center text-sm-start d-flex gap-2 align-items-center">
        <img src="images/logo.png.png" alt="Recycling Logo" class="logo-img">
        
        <!-- Mobile Menu Toggle -->
        <button class="navbar-toggler d-md-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar"
          aria-controls="offcanvasNavbar">
          <svg width="25" height="25" viewBox="0 0 24 24">
            <path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/>
          </svg>
        </button>
        
        <!-- Sidebar Toggle for Mobile -->
        <button class="sidebar-toggle d-md-none" id="sidebarToggle" style="display: none;">
          <i class="fas fa-bars"></i>
        </button>
      </div>
      
      <!-- Search Bar -->
      <div class="col-sm-6 offset-sm-2 offset-md-0 col-lg-6">
        <div class="search-container">
          <div class="d-flex align-items-center position-relative w-100">
            <input type="text" class="form-control search-input pe-5" placeholder="Search products..." id="searchInput">
            <i class="fas fa-search position-absolute top-50 end-0 translate-middle-y pe-3" style="color: #6c757d;"></i>
            <div class="search-loading" id="searchLoading">
              <div class="spinner"></div>
            </div>
          </div>
          <!-- Search Suggestions -->
          <div class="search-suggestions" id="searchSuggestions">
            <!-- Suggestions will be populated here -->
          </div>
        </div>
      </div>

      <!-- Navigation -->
      <div class="col-lg-4 ms-lg-auto">
        <!-- Desktop Navigation -->
        <ul class="navbar-nav list-unstyled d-none d-lg-flex flex-row gap-2 justify-content-end align-items-center mb-0 fw-bold text-uppercase desktop-nav">
          <li class="nav-item">
            <a href="signup.php" class="nav-link">Sign Up</a>
          </li>
          <li class="nav-item">
            <a href="login.php" class="nav-link">Log In</a>
          </li>
        </ul>
        
        <!-- Mobile Navigation -->
        <div class="d-flex d-lg-none justify-content-end align-items-center mobile-nav">
          <a href="mycart.php" class="btn btn-outline-light btn-sm me-2">
            <i class="fas fa-shopping-cart"></i>
            <span class="badge bg-danger ms-1" id="cartBadge">0</span>
          </a>
          <a href="profile.php" class="btn btn-outline-light btn-sm">
            <i class="fas fa-user"></i>
          </a>
        </div>
      </div>
    </div>
  </div>
</header>

<!-- Mobile Offcanvas Menu -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="offcanvasNavbarLabel">
      <img src="images/logo.png.png" alt="Logo" style="height: 30px; margin-right: 10px;">
      Eco Cycle
    </h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body">
    <div class="d-flex flex-column gap-2">
      <a href="home.php" class="mobile-menu-item">
        <i class="fas fa-home"></i>
        Home
      </a>
      <a href="mycart.php" class="mobile-menu-item">
        <i class="fas fa-shopping-cart"></i>
        My Cart
        <span class="badge bg-danger ms-auto" id="mobileCartBadge">0</span>
      </a>
      <a href="myorders.php" class="mobile-menu-item">
        <i class="fas fa-box"></i>
        My Orders
      </a>
      <a href="profile.php" class="mobile-menu-item">
        <i class="fas fa-user"></i>
        Profile
      </a>
      <a href="logout.php" class="mobile-menu-item">
        <i class="fas fa-sign-out-alt"></i>
        Logout
      </a>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Mobile sidebar toggle functionality
document.addEventListener('DOMContentLoaded', function() {
  const sidebarToggle = document.getElementById('sidebarToggle');
  const sidebar = document.querySelector('.sidebar');
  
  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', function() {
      sidebar.classList.toggle('show');
    });
    
    // Close sidebar when clicking outside
    document.addEventListener('click', function(e) {
      if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
        sidebar.classList.remove('show');
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
        // Use absolute path so searches work from any page/depth
        window.location.href = '/Ecocycle/search-results.php?q=' + encodeURIComponent(query.trim());
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
        // Fetch search suggestions from API
        fetch(`api/search-products.php?q=${encodeURIComponent(query)}`)
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
  
  // Update cart badge (mock data)
  function updateCartBadge() {
    const badges = document.querySelectorAll('#cartBadge, #mobileCartBadge');
    const cartCount = Math.floor(Math.random() * 5); // Mock cart count
    badges.forEach(badge => {
      badge.textContent = cartCount;
      badge.style.display = cartCount > 0 ? 'inline' : 'none';
    });
  }
  
  updateCartBadge();
  
  // Responsive header adjustments
  function adjustHeader() {
    const header = document.querySelector('header');
    const sidebarToggle = document.getElementById('sidebarToggle');
    
    if (window.innerWidth <= 768) {
      // Show sidebar toggle on pages with sidebar
      if (document.querySelector('.sidebar')) {
        sidebarToggle.style.display = 'block';
      }
    } else {
      sidebarToggle.style.display = 'none';
      if (sidebar) {
        sidebar.classList.remove('show');
      }
    }
  }
  
  adjustHeader();
  window.addEventListener('resize', adjustHeader);
});
</script>
</body>
</html>