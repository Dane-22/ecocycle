// Mobile-Specific JavaScript Enhancements

class MobileUI {
  constructor() {
    this.init();
  }

  init() {
    this.setupMobileNavigation();
    this.setupTouchInteractions();
    this.setupMobileModals();
    this.setupSwipeGestures();
    this.setupPullToRefresh();
    this.setupMobileSearch();
    this.setupMobileToast();
    this.setupMobileGallery();
    this.setupMobileForms();
    this.setupMobileSidebar();
  }

  // Mobile Navigation Setup
  setupMobileNavigation() {
    const mobileNavItems = document.querySelectorAll('.mobile-nav-item');
    
    mobileNavItems.forEach(item => {
      item.addEventListener('click', (e) => {
        // Remove active class from all items
        mobileNavItems.forEach(nav => nav.classList.remove('active'));
        // Add active class to clicked item
        item.classList.add('active');
      });
    });

    // Handle mobile menu toggle
    const mobileMenuToggle = document.querySelector('.navbar-toggler');
    const offcanvas = document.querySelector('.offcanvas');
    
    if (mobileMenuToggle && offcanvas) {
      mobileMenuToggle.addEventListener('click', () => {
        // Add any additional mobile menu logic here
      });
    }
  }

  // Touch Interactions
  setupTouchInteractions() {
    // Add touch feedback to buttons
    const touchButtons = document.querySelectorAll('.btn, .mobile-btn, .category-item');
    
    touchButtons.forEach(button => {
      button.addEventListener('touchstart', () => {
        button.style.transform = 'scale(0.98)';
      });
      
      button.addEventListener('touchend', () => {
        button.style.transform = '';
      });
    });

    // Prevent zoom on double tap
    let lastTouchEnd = 0;
    document.addEventListener('touchend', (event) => {
      const now = (new Date()).getTime();
      if (now - lastTouchEnd <= 300) {
        event.preventDefault();
      }
      lastTouchEnd = now;
    }, false);
  }

  // Mobile Modals
  setupMobileModals() {
    // Create mobile modal function
    window.showMobileModal = (content, title = '') => {
      const modal = document.createElement('div');
      modal.className = 'mobile-modal';
      modal.innerHTML = `
        <div class="mobile-modal-content">
          <div class="mobile-modal-header">
            <h3 class="mobile-modal-title">${title}</h3>
            <button class="mobile-modal-close" onclick="this.closest('.mobile-modal').remove()">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="mobile-modal-body">
            ${content}
          </div>
        </div>
      `;
      
      document.body.appendChild(modal);
      
      // Close on backdrop click
      modal.addEventListener('click', (e) => {
        if (e.target === modal) {
          modal.remove();
        }
      });
    };

    // Enhanced product modal for mobile
    const productCards = document.querySelectorAll('.product-card');
    productCards.forEach(card => {
      card.addEventListener('click', (e) => {
        if (e.target.classList.contains('btn')) return; // Don't trigger on button clicks
        
        const productId = card.dataset.productId;
        const productName = card.querySelector('.card-title')?.textContent || 'Product';
        const productPrice = card.querySelector('.product-price')?.textContent || '';
        const productImage = card.querySelector('img')?.src || '';
        const productDescription = card.querySelector('.card-text')?.textContent || '';
        
        const modalContent = `
          <div class="text-center mb-3">
            <img src="${productImage}" alt="${productName}" class="img-fluid rounded" style="max-height: 200px;">
          </div>
          <h4 class="mb-2">${productName}</h4>
          <p class="text-muted mb-3">${productDescription}</p>
          <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="h5 text-success mb-0">${productPrice}</span>
            <span class="badge bg-primary">In Stock</span>
          </div>
          <div class="d-grid gap-2">
            <button class="mobile-btn mobile-btn-primary" onclick="addToCart(${productId})">
              <i class="fas fa-shopping-cart me-2"></i>Add to Cart
            </button>
            <button class="mobile-btn mobile-btn-outline" onclick="viewProductDetails(${productId})">
              <i class="fas fa-eye me-2"></i>View Details
            </button>
          </div>
        `;
        
        showMobileModal(modalContent, productName);
      });
    });
  }

  // Swipe Gestures
  setupSwipeGestures() {
    let startX = 0;
    let startY = 0;
    let endX = 0;
    let endY = 0;

    document.addEventListener('touchstart', (e) => {
      startX = e.touches[0].clientX;
      startY = e.touches[0].clientY;
    });

    document.addEventListener('touchend', (e) => {
      endX = e.changedTouches[0].clientX;
      endY = e.changedTouches[0].clientY;
      
      const diffX = startX - endX;
      const diffY = startY - endY;
      
      // Swipe left/right detection
      if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 50) {
        if (diffX > 0) {
          // Swipe left
          this.handleSwipeLeft(e.target);
        } else {
          // Swipe right
          this.handleSwipeRight(e.target);
        }
      }
      
      // Swipe up/down detection
      if (Math.abs(diffY) > Math.abs(diffX) && Math.abs(diffY) > 50) {
        if (diffY > 0) {
          // Swipe up
          this.handleSwipeUp(e.target);
        } else {
          // Swipe down
          this.handleSwipeDown(e.target);
        }
      }
    });
  }

  handleSwipeLeft(element) {
    // Handle swipe left - could be used for navigation or actions
    const swipeContainer = element.closest('.mobile-swipe-container');
    if (swipeContainer) {
      swipeContainer.classList.add('swiped');
    }
  }

  handleSwipeRight(element) {
    // Handle swipe right - could be used for navigation or actions
    const swipeContainer = element.closest('.mobile-swipe-container');
    if (swipeContainer) {
      swipeContainer.classList.remove('swiped');
    }
  }

  handleSwipeUp(element) {
    // Handle swipe up - could be used for showing more content
  }

  handleSwipeDown(element) {
    // Handle swipe down - could be used for refresh or closing modals
    const modal = element.closest('.mobile-modal');
    if (modal) {
      modal.remove();
    }
  }

  // Pull to Refresh
  setupPullToRefresh() {
    let startY = 0;
    let pullDistance = 0;
    const pullThreshold = 80;
    
    document.addEventListener('touchstart', (e) => {
      if (window.scrollY === 0) {
        startY = e.touches[0].clientY;
      }
    });

    document.addEventListener('touchmove', (e) => {
      if (window.scrollY === 0 && startY > 0) {
        pullDistance = e.touches[0].clientY - startY;
        
        if (pullDistance > 0) {
          e.preventDefault();
          
          // Show pull indicator
          let indicator = document.querySelector('.mobile-pull-indicator');
          if (!indicator) {
            indicator = document.createElement('div');
            indicator.className = 'mobile-pull-indicator';
            indicator.innerHTML = `
              <i class="fas fa-arrow-down mobile-pull-icon"></i>
              <span>Pull to refresh</span>
            `;
            document.body.insertBefore(indicator, document.body.firstChild);
          }
          
          if (pullDistance > pullThreshold) {
            indicator.innerHTML = `
              <i class="fas fa-sync-alt mobile-pull-icon"></i>
              <span>Release to refresh</span>
            `;
            indicator.classList.add('show');
          } else {
            indicator.innerHTML = `
              <i class="fas fa-arrow-down mobile-pull-icon"></i>
              <span>Pull to refresh</span>
            `;
            indicator.classList.add('show');
          }
        }
      }
    });

    document.addEventListener('touchend', () => {
      if (pullDistance > pullThreshold) {
        // Trigger refresh
        this.refreshContent();
      }
      
      // Hide pull indicator
      const indicator = document.querySelector('.mobile-pull-indicator');
      if (indicator) {
        indicator.classList.remove('show');
        setTimeout(() => indicator.remove(), 300);
      }
      
      startY = 0;
      pullDistance = 0;
    });
  }

  refreshContent() {
    // Show loading state
    this.showMobileToast('Refreshing...', 'info');
    
    // Simulate refresh
    setTimeout(() => {
      location.reload();
    }, 1000);
  }

  // Mobile Search
  setupMobileSearch() {
    const searchInput = document.querySelector('.search-input, .mobile-search-input');
    
    if (searchInput) {
      let searchTimeout;
      
      searchInput.addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        
        searchTimeout = setTimeout(() => {
          this.performSearch(e.target.value);
        }, 300);
      });
      
      // Add search button functionality
      const searchBtn = document.querySelector('.mobile-search-btn');
      if (searchBtn) {
        searchBtn.addEventListener('click', () => {
          this.performSearch(searchInput.value);
        });
      }
    }
  }

  performSearch(query) {
    if (query.trim().length < 2) return;
    
    // Show loading
    this.showMobileToast('Searching...', 'info');
    
    // Simulate search
    setTimeout(() => {
      this.showMobileToast(`Found results for "${query}"`, 'success');
    }, 1000);
  }

  // Mobile Toast Notifications
  setupMobileToast() {
    window.showMobileToast = (message, type = 'info', duration = 3000) => {
      const toast = document.createElement('div');
      toast.className = `mobile-toast ${type}`;
      toast.textContent = message;
      
      document.body.appendChild(toast);
      
      // Show toast
      setTimeout(() => {
        toast.classList.add('show');
      }, 100);
      
      // Hide toast
      setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
          toast.remove();
        }, 300);
      }, duration);
    };
  }

  // Mobile Gallery
  setupMobileGallery() {
    const galleries = document.querySelectorAll('.mobile-gallery');
    
    galleries.forEach(gallery => {
      const items = gallery.querySelectorAll('.mobile-gallery-item');
      
      items.forEach(item => {
        item.addEventListener('click', () => {
          const image = item.querySelector('.mobile-gallery-image');
          if (image) {
            this.showImageModal(image.src, image.alt);
          }
        });
      });
    });
  }

  showImageModal(src, alt) {
    const modalContent = `
      <div class="text-center">
        <img src="${src}" alt="${alt}" class="img-fluid rounded" style="max-height: 70vh;">
      </div>
    `;
    
    showMobileModal(modalContent, alt);
  }

  // Mobile Forms
  setupMobileForms() {
    const formInputs = document.querySelectorAll('.mobile-form-input, .mobile-form-select');
    
    formInputs.forEach(input => {
      // Add focus styles
      input.addEventListener('focus', () => {
        input.parentElement.classList.add('focused');
      });
      
      input.addEventListener('blur', () => {
        input.parentElement.classList.remove('focused');
      });
      
      // Auto-resize textareas
      if (input.tagName === 'TEXTAREA') {
        input.addEventListener('input', () => {
          input.style.height = 'auto';
          input.style.height = input.scrollHeight + 'px';
        });
      }
    });
  }

  // Mobile Sidebar
  setupMobileSidebar() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (sidebarToggle && sidebar) {
      sidebarToggle.addEventListener('click', () => {
        sidebar.classList.toggle('show');
      });
      
      // Close sidebar when clicking outside
      document.addEventListener('click', (e) => {
        if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
          sidebar.classList.remove('show');
        }
      });
      
      // Close sidebar on escape key
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
          sidebar.classList.remove('show');
        }
      });
    }
  }
}

// Utility Functions
window.addToCart = (productId) => {
  // Add to cart functionality
  showMobileToast('Product added to cart!', 'success');
  
  // Close modal
  const modal = document.querySelector('.mobile-modal');
  if (modal) {
    modal.remove();
  }
};

window.viewProductDetails = (productId) => {
  // View product details functionality
  showMobileToast('Opening product details...', 'info');
  
  // Close modal
  const modal = document.querySelector('.mobile-modal');
  if (modal) {
    modal.remove();
  }
};

// Initialize Mobile UI when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
  new MobileUI();
});

// Handle orientation change
window.addEventListener('orientationchange', () => {
  setTimeout(() => {
    // Recalculate any layout-dependent elements
    const modals = document.querySelectorAll('.mobile-modal');
    modals.forEach(modal => {
      const content = modal.querySelector('.mobile-modal-content');
      if (content) {
        content.style.maxHeight = window.innerHeight * 0.9 + 'px';
      }
    });
  }, 100);
});

// Handle viewport resize
window.addEventListener('resize', () => {
  // Handle any resize-dependent logic
});

// Export for use in other scripts
window.MobileUI = MobileUI; 