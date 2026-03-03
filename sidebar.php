<style>
  .sidebar {
    background-color: #f8f9fa;
    padding: 20px;
    border-right: 1px solid #dee2e6;
    height: 100vh;
    position: fixed;
    overflow-y: auto;
    transition: all 0.3s ease;
    z-index: 1040;
  }
  
  .main-content {
    padding: 20px;
    margin-left: 250px;
    transition: margin-left 0.3s ease;
  }
  
  .category-item {
    padding: 12px 16px;
    border-bottom: 1px solid #eee;
    cursor: pointer;
    transition: all 0.3s ease;
    border-radius: 8px;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  
  .category-item:hover {
    background-color: #e9ecef;
    transform: translateX(4px);
  }
  
  .category-item.active {
    background-color: #28a745;
    color: white;
  }
  
  .category-item i {
    margin-right: 10px;
    width: 16px;
    text-align: center;
  }
  
  .price-filter {
    padding: 15px 0;
  }
  
  .sidebar-title {
    font-weight: bold;
    margin-bottom: 15px;
    color: #2c786c;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
  }
  
  .sidebar-title i {
    margin-right: 8px;
  }
  
  .create-listing-btn {
    background-color: #28a745;
    color: white;
    margin-bottom: 20px;
    width: 100%;
    padding: 12px;
    font-weight: bold;
    border: none;
    border-radius: 8px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
  }
  
  .create-listing-btn:hover {
    background-color: #218838;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
  }
  
  /* Filter sections */
  .filter-section {
    margin-bottom: 25px;
    padding: 15px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  }
  
  .filter-section h5 {
    margin-bottom: 15px;
    color: #2c786c;
    font-size: 1rem;
    font-weight: 600;
  }
  
  /* Form controls */
  .form-check {
    margin-bottom: 8px;
    padding: 8px 0;
  }
  
  .form-check-input {
    width: 18px;
    height: 18px;
    margin-top: 0;
    margin-right: 10px;
  }
  
  .form-check-label {
    font-size: 0.95rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    min-height: 44px;
  }
  
  /* Range slider */
  .form-range {
    width: 100%;
    height: 6px;
    border-radius: 3px;
    background: #dee2e6;
    outline: none;
    margin: 15px 0;
  }
  
  .form-range::-webkit-slider-thumb {
    appearance: none;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #28a745;
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
  }
  
  .form-range::-moz-range-thumb {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #28a745;
    cursor: pointer;
    border: none;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
  }
  
  .price-range-display {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 10px 0;
    font-size: 0.9rem;
    color: #6c757d;
  }
  
  .price-range-display span {
    background: #e9ecef;
    padding: 4px 8px;
    border-radius: 4px;
    font-weight: 500;
  }
  
  /* Reset button */
  .reset-filters-btn {
    background-color: #6c757d;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 10px 16px;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    width: 100%;
  }
  
  .reset-filters-btn:hover {
    background-color: #5a6268;
    transform: translateY(-1px);
  }
  
  /* Mobile Responsive Styles */
  @media (max-width: 768px) {
    .sidebar {
      position: fixed;
      top: 60px;
      left: -280px;
      width: 280px;
      height: calc(100vh - 60px);
      z-index: 1050;
      background: #fff;
      box-shadow: 2px 0 10px rgba(0,0,0,0.1);
      padding: 15px;
    }
    
    .sidebar.show {
      left: 0;
    }
    
    .main-content {
      margin-left: 0;
      padding: 15px;
    }
    
    .filter-section {
      margin-bottom: 20px;
      padding: 12px;
    }
    
    .category-item {
      padding: 14px 16px;
      font-size: 1rem;
    }
    
    .create-listing-btn {
      padding: 14px;
      font-size: 1rem;
      margin-bottom: 15px;
    }
    
    .sidebar-title {
      font-size: 1rem;
      margin-bottom: 12px;
    }
    
    .form-check-label {
      font-size: 1rem;
      min-height: 48px;
    }
    
    .form-check-input {
      width: 20px;
      height: 20px;
    }
  }
  
  /* Tablet styles */
  @media (min-width: 769px) and (max-width: 1024px) {
    .sidebar {
      width: 220px;
    }
    
    .main-content {
      margin-left: 220px;
    }
    
    .filter-section {
      padding: 12px;
    }
    
    .category-item {
      padding: 10px 14px;
      font-size: 0.9rem;
    }
  }
  
  /* Touch device optimizations */
  @media (hover: none) and (pointer: coarse) {
    .category-item:hover {
      transform: none;
    }
    
    .create-listing-btn:hover {
      transform: none;
    }
    
    .reset-filters-btn:hover {
      transform: none;
    }
    
    .category-item:active {
      background-color: #d1ecf1;
      transform: scale(0.98);
    }
    
    .create-listing-btn:active {
      transform: scale(0.98);
    }
    
    .reset-filters-btn:active {
      transform: scale(0.98);
    }
  }
  
  /* Dark mode support */
  @media (prefers-color-scheme: dark) {
    .sidebar {
      background-color: #2d3748;
      border-right-color: #4a5568;
    }
    
    .filter-section {
      background: #4a5568;
    }
    
    .sidebar-title {
      color: #e2e8f0;
    }
    
    .category-item {
      border-bottom-color: #4a5568;
      color: #e2e8f0;
    }
    
    .category-item:hover {
      background-color: #4a5568;
    }
    
    .category-item.active {
      background-color: #38a169;
    }
    
    .form-check-label {
      color: #e2e8f0;
    }
    
    .price-range-display {
      color: #a0aec0;
    }
    
    .price-range-display span {
      background: #4a5568;
      color: #e2e8f0;
    }
  }
  
  /* High contrast mode */
  @media (prefers-contrast: high) {
    .sidebar {
      border-right: 2px solid #000;
    }
    
    .filter-section {
      border: 2px solid #000;
    }
    
    .category-item {
      border-bottom: 2px solid #000;
    }
    
    .create-listing-btn {
      border: 2px solid currentColor;
    }
  }
  
  /* Reduced motion support */
  @media (prefers-reduced-motion: reduce) {
    .sidebar,
    .main-content,
    .category-item,
    .create-listing-btn,
    .reset-filters-btn {
      transition: none;
    }
    
    .category-item:hover,
    .create-listing-btn:hover,
    .reset-filters-btn:hover {
      transform: none;
    }
  }
</style>

<div class="container-fluid">
  <div class="row">
    <!-- Sidebar Column -->
    <div class="col-md-3 col-lg-2 d-md-block sidebar">
      <div class="position-sticky pt-3">
        <!-- Create New Listing Button -->
        <button class="btn create-listing-btn">
          <i class="fas fa-plus"></i> Create New Listing
        </button>
        
        <!-- Categories Section -->
        <div class="filter-section">
          <h5 class="sidebar-title">
            <i class="fas fa-tags"></i> Product Categories
          </h5>
          <div class="list-group list-group-flush">
            <div class="category-item active">
              <span><i class="fas fa-th-large"></i> All Products</span>
              <span class="badge bg-primary">24</span>
            </div>
            <div class="category-item">
              <span><i class="fas fa-recycle"></i> Recycled Plastic</span>
              <span class="badge bg-secondary">8</span>
            </div>
            <div class="category-item">
              <span><i class="fas fa-file-alt"></i> Recycled Paper</span>
              <span class="badge bg-secondary">6</span>
            </div>
            <div class="category-item">
              <span><i class="fas fa-wine-glass"></i> Recycled Glass</span>
              <span class="badge bg-secondary">4</span>
            </div>
            <div class="category-item">
              <span><i class="fas fa-cog"></i> Recycled Metal</span>
              <span class="badge bg-secondary">3</span>
            </div>
            <div class="category-item">
              <span><i class="fas fa-tshirt"></i> Upcycled Fashion</span>
              <span class="badge bg-secondary">2</span>
            </div>
            <div class="category-item">
              <span><i class="fas fa-home"></i> Eco-Friendly Home</span>
              <span class="badge bg-secondary">1</span>
            </div>
          </div>
        </div>
        
        <!-- Price Filter -->
        <div class="filter-section">
          <h5 class="sidebar-title">
            <i class="fas fa-dollar-sign"></i> Price Range
          </h5>
          <div class="price-filter">
            <input type="range" class="form-range" min="0" max="1000" step="50" id="priceRange" value="500">
            <div class="price-range-display">
              <span>₱0</span>
              <span id="currentPrice">₱500</span>
              <span>₱1000</span>
            </div>
            <button class="btn btn-sm btn-success mt-2 w-100">
              <i class="fas fa-filter"></i> Apply Filter
            </button>
          </div>
        </div>
        
        <!-- Eco Rating Filter -->
        <div class="filter-section">
          <h5 class="sidebar-title">
            <i class="fas fa-star"></i> Eco Rating
          </h5>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" value="" id="rating5">
            <label class="form-check-label" for="rating5">
              ★★★★★ (5 stars)
            </label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" value="" id="rating4">
            <label class="form-check-label" for="rating4">
              ★★★★ (4+ stars)
            </label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" value="" id="rating3">
            <label class="form-check-label" for="rating3">
              ★★★ (3+ stars)
            </label>
          </div>
        </div>
        
        <!-- Material Filter -->
        <div class="filter-section">
          <h5 class="sidebar-title">
            <i class="fas fa-cube"></i> Materials
          </h5>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" value="" id="materialPlastic">
            <label class="form-check-label" for="materialPlastic">
              Recycled Plastic
            </label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" value="" id="materialPaper">
            <label class="form-check-label" for="materialPaper">
              Recycled Paper
            </label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" value="" id="materialGlass">
            <label class="form-check-label" for="materialGlass">
              Recycled Glass
            </label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" value="" id="materialMetal">
            <label class="form-check-label" for="materialMetal">
              Recycled Metal
            </label>
          </div>
        </div>
        
        <button class="btn reset-filters-btn">
          <i class="fas fa-undo"></i> Reset Filters
        </button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Price range slider functionality
  const priceRange = document.getElementById('priceRange');
  const currentPrice = document.getElementById('currentPrice');
  
  if (priceRange && currentPrice) {
    priceRange.addEventListener('input', function() {
      currentPrice.textContent = '₱' + this.value;
    });
  }
  
  // Category item click functionality
  const categoryItems = document.querySelectorAll('.category-item');
  categoryItems.forEach(item => {
    item.addEventListener('click', function() {
      // Remove active class from all items
      categoryItems.forEach(cat => cat.classList.remove('active'));
      // Add active class to clicked item
      this.classList.add('active');
      
      // Here you would typically filter products based on category
      console.log('Selected category:', this.textContent.trim());
    });
  });
  
  // Filter functionality
  const filterCheckboxes = document.querySelectorAll('.form-check-input');
  const applyFilterBtn = document.querySelector('.btn-success');
  
  if (applyFilterBtn) {
    applyFilterBtn.addEventListener('click', function() {
      const selectedFilters = [];
      filterCheckboxes.forEach(checkbox => {
        if (checkbox.checked) {
          selectedFilters.push(checkbox.id);
        }
      });
      
      console.log('Applied filters:', selectedFilters);
      // Here you would typically apply the filters to the product list
    });
  }
  
  // Reset filters functionality
  const resetBtn = document.querySelector('.reset-filters-btn');
  if (resetBtn) {
    resetBtn.addEventListener('click', function() {
      // Reset all checkboxes
      filterCheckboxes.forEach(checkbox => {
        checkbox.checked = false;
      });
      
      // Reset price range
      if (priceRange) {
        priceRange.value = 500;
        currentPrice.textContent = '₱500';
      }
      
      // Reset category selection
      categoryItems.forEach(cat => cat.classList.remove('active'));
      categoryItems[0].classList.add('active'); // Select "All Products"
      
      console.log('Filters reset');
    });
  }
  
  // Mobile sidebar close on outside click
  document.addEventListener('click', function(e) {
    const sidebar = document.querySelector('.sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    
    if (window.innerWidth <= 768 && sidebar && sidebarToggle) {
      if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
        sidebar.classList.remove('show');
      }
    }
  });
});
</script>