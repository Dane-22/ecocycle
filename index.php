<?php 
include 'header.php';
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Eco Cycle: Sustainable Marketplace</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="format-detection" content="telephone=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="author" content="DMMMSU Environmental Concerns">
    <meta name="keywords" content="recycling, sustainable, eco-friendly, marketplace, DMMMSU">
    <meta name="description" content="Join Eco Cycle - DMMMSU's sustainable marketplace for recycled products. Reduce waste, support local artisans, and make eco-conscious choices.">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" href="css/vendor.css">
    <link rel="stylesheet" type="text/css" href="style.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Open+Sans:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">

  </head>
  <body>

    <main class="main-content" style="margin-left: 0; padding: 20px;">
      <!-- Enhanced Hero Section -->
      <section style="background-image: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('images/DMMMSU.jpg'); background-repeat: no-repeat; background-size: cover; background-position: center; min-height: 80vh; display: flex; align-items: center;">
        <div class="container-fluid">
          <div class="row">
            <div class="col-lg-6 pt-5 mt-5">
              <h2 class="display-1 ls-1 text-white">
                <span class="fw-bold text-success">Recycle</span> Smarter, for a <span class="fw-bold text-white">Greener Tomorrow</span>
              </h2>
              <p class="fs-4 text-white-50 mb-4">Join DMMMSU's sustainable marketplace where every purchase supports environmental conservation and local artisans. Make every item count — reduce, reuse, and recycle.</p>
              <div class="d-flex gap-3 flex-wrap">
                <a href="signup.php" class="btn btn-success text-uppercase fs-6 rounded-pill px-4 py-3 mt-3 fw-bold">Start Recycling Today</a>
                <a href="#categories" class="btn btn-outline-light text-uppercase fs-6 rounded-pill px-4 py-3 mt-3">Explore Products</a>
              </div>
              <div class="mt-4 text-white-50">
                <small>🌟 Join 500+ eco-conscious students and community members</small>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Statistics Section -->
      <div class="container-fluid mt-5 py-5 bg-success text-white">
        <div class="row text-center">
          <div class="col-md-3 mb-4">
            <div class="stat-item">
              <h3 class="display-4 fw-bold">500+</h3>
              <p class="fs-5">Active Recyclers</p>
            </div>
          </div>
          <div class="col-md-3 mb-4">
            <div class="stat-item">
              <h3 class="display-4 fw-bold">1,200+</h3>
              <p class="fs-5">Items Recycled</p>
            </div>
          </div>
          <div class="col-md-3 mb-4">
            <div class="stat-item">
              <h3 class="display-4 fw-bold">₱25K+</h3>
              <p class="fs-5">Eco Coins Earned</p>
            </div>
          </div>
          <div class="col-md-3 mb-4">
            <div class="stat-item">
              <h3 class="display-4 fw-bold">50+</h3>
              <p class="fs-5">Local Artisans</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Enhanced Eco Deals Section -->
      <div class="container-fluid mt-5 py-4 bg-light">
        <div class="row text-center">
          <div class="col-12">
            <h2 class="fw-bold mb-4 text-success">ECO CYCLE LOVES</h2>
            <div class="d-flex flex-wrap justify-content-center gap-4">
              <div class="deal-item p-3 bg-white rounded shadow-sm">
                <h5 class="fw-bold text-success">🌱 GREEN STYLES</h5>
                <p class="mb-0">Sustainable fashion choices</p>
              </div>
              <div class="deal-item p-3 bg-white rounded shadow-sm">
                <h5 class="fw-bold text-success">💰 ECO DEALS</h5>
                <p class="mb-0">Daily discounts on recycled goods</p>
              </div>
              <div class="deal-item p-3 bg-white rounded shadow-sm">
                <h5 class="fw-bold text-success">🌍 Earth-Friendly Choices</h5>
                <p class="mb-0">From ₱29 and up</p>
              </div>
              <div class="deal-item p-3 bg-white rounded shadow-sm">
                <h5 class="fw-bold text-success">🚚 Free Shipping & Vouchers</h5>
                <p class="mb-0">For select items</p>
              </div>
              <div class="deal-item p-3 bg-white rounded shadow-sm">
                <h5 class="fw-bold text-success">⭐ Eco Points Rewards</h5>
                <p class="mb-0">Redeem for discounts</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Enhanced Categories Section -->
      <div class="container-fluid mt-5" id="categories">
        <div class="row">
          <div class="col-12">
            <div class="text-center mb-5">
              <h2 class="fw-bold mb-3">🌱 EXPLORE SUSTAINABLE CATEGORIES</h2>
              <p class="text-muted fs-5 mb-4">Discover eco-friendly products made from recycled materials by local artisans</p>
            </div>
            
            <div class="position-relative">
              <!-- Removed swiper-buttons -->
              <!-- <div class="swiper-buttons d-flex align-items-center mb-4 justify-content-center"> -->
              <!--   <button class="swiper-prev category-carousel-prev btn btn-outline-success me-3" style="border-radius:50%; width: 45px; height: 45px;"> -->
              <!--     <i class="fas fa-chevron-left"></i> -->
              <!--   </button> -->
              <!--   <button class="swiper-next category-carousel-next btn btn-outline-success" style="border-radius:50%; width: 45px; height: 45px;"> -->
              <!--     <i class="fas fa-chevron-right"></i> -->
              <!--   </button> -->
              <!-- </div> -->
              
              <div class="category-carousel swiper">
                <div class="swiper-wrapper">
                  <!-- Category 1: Eco Green Products -->
                  <div class="swiper-slide d-flex flex-column align-items-center">
                    <div class="category-circle text-center">
                      <img src="images/ecobottle.jpg" class="category-img" alt="Eco Green Products">
                    </div>
                    <div class="category-label">Eco Green Products</div>
                  </div>
                  
                  <!-- Category 2: Upcycled Products -->
                  <div class="swiper-slide d-flex flex-column align-items-center">
                    <div class="category-circle text-center">
                      <img src="images/wooden-shelf.jpg" class="category-img" alt="Upcycled Products">
                    </div>
                    <div class="category-label">Upcycled Products</div>
                  </div>
                  
                  <!-- Category 3: Recycled Bags -->
                  <div class="swiper-slide d-flex flex-column align-items-center">
                    <div class="category-circle text-center">
                      <img src="images/recycled-tote-bag.jpg" class="category-img" alt="Recycled Bags">
                    </div>
                    <div class="category-label">Recycled Bags</div>
                  </div>
                  
                  <!-- Category 4: Recycled Glassware -->
                  <div class="swiper-slide d-flex flex-column align-items-center">
                    <div class="category-circle text-center">
                      <img src="images/glass-vase.jpg" class="category-img" alt="Recycled Glassware">
                    </div>
                    <div class="category-label">Recycled Glassware</div>
                  </div>
                  
                  <!-- Category 5: Recycled Papers -->
                  <div class="swiper-slide d-flex flex-column align-items-center">
                    <div class="category-circle text-center">
                      <img src="images/recycled-notebook.jpg" class="category-img" alt="Recycled Papers">
                    </div>
                    <div class="category-label">Recycled Papers</div>
                  </div>
                  
                  <!-- Category 6: Eco Beauty -->
                  <div class="swiper-slide d-flex flex-column align-items-center">
                    <div class="category-circle text-center">
                      <img src="images/bamboo-toothbrush.jpg" class="category-img" alt="Eco Beauty">
                    </div>
                    <div class="category-label">Eco Beauty</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Enhanced Product Container -->
      <div class="container-fluid mt-5">
        <h2 class="fw-bold mb-4 text-center">FEATURED RECYCLED PRODUCTS</h2>
        <p class="text-center text-muted mb-5">Handcrafted with love by local artisans using recycled materials</p>
        <div class="row">
          <!-- Product 1 -->
          <div class="col-12 col-md-6 col-lg-3 mb-4">
            <div class="card h-100 border shadow-sm product-card">
              <div class="position-relative">
                <img src="images/ecobottle.jpg" class="card-img-top" alt="Eco Bottle 500ml" style="max-height: 200px; object-fit: contain;">
                <span class="badge bg-success position-absolute top-0 end-0 m-2">Best Seller</span>
              </div>
              <div class="card-body d-flex flex-column">
                <h5 class="card-title">Eco Bottle 500ml</h5>
                <p class="card-text small">A premium reusable water bottle crafted from recycled materials. Features double-wall insulation to keep drinks cold for 24 hours. Perfect for students and eco-conscious individuals.</p>
                <div class="mt-auto">
                  <p class="fw-bold text-success fs-5">₱100.00</p>
                  <div class="d-flex justify-content-between align-items-center">
                    <span class="badge bg-light text-dark">Made from recycled plastic</span>
                    <button class="btn btn-outline-success btn-sm">Add to Cart</button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Product 2 -->
          <div class="col-12 col-md-6 col-lg-3 mb-4">
            <div class="card h-100 border shadow-sm product-card">
              <div class="position-relative">
                <img src="images/recycled-tote-bag.jpg" class="card-img-top" alt="Recycled Tote Bag" style="max-height: 200px; object-fit: contain;">
                <span class="badge bg-warning position-absolute top-0 end-0 m-2">Limited Stock</span>
              </div>
              <div class="card-body d-flex flex-column">
                <h5 class="card-title">Recycled Tote Bag</h5>
                <p class="card-text small">Handcrafted from recycled denim and canvas materials. Features reinforced handles and spacious interior. Perfect for grocery shopping, library visits, or daily use. Each bag saves approximately 2 plastic bags from landfills.</p>
                <div class="mt-auto">
                  <p class="fw-bold text-success fs-5">₱150.00</p>
                  <div class="d-flex justify-content-between align-items-center">
                    <span class="badge bg-light text-dark">Upcycled denim</span>
                    <button class="btn btn-outline-success btn-sm">Add to Cart</button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Product 3 -->
          <div class="col-12 col-md-6 col-lg-3 mb-4">
            <div class="card h-100 border shadow-sm product-card">
              <div class="position-relative">
                <img src="images/recycled-notebook.jpg" class="card-img-top" alt="Eco-Friendly Notebook" style="max-height: 200px; object-fit: contain;">
                <span class="badge bg-info position-absolute top-0 end-0 m-2">New</span>
              </div>
              <div class="card-body d-flex flex-column">
                <h5 class="card-title">Eco-Friendly Notebook</h5>
                <p class="card-text small">Premium notebook made from 100% post-consumer recycled paper. Features 80 pages of lined paper with a durable recycled cardboard cover. Perfect for students, writers, and anyone who loves to take notes sustainably.</p>
                <div class="mt-auto">
                  <p class="fw-bold text-success fs-5">₱120.00</p>
                  <div class="d-flex justify-content-between align-items-center">
                    <span class="badge bg-light text-dark">100% recycled paper</span>
                    <button class="btn btn-outline-success btn-sm">Add to Cart</button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Product 4 -->
          <div class="col-12 col-md-6 col-lg-3 mb-4">
            <div class="card h-100 border shadow-sm product-card">
              <div class="position-relative">
                <img src="images/ecophonecase.jpg" class="card-img-top" alt="Eco Phone Case" style="max-height: 200px; object-fit: contain;">
                <span class="badge bg-danger position-absolute top-0 end-0 m-2">Popular</span>
              </div>
              <div class="card-body d-flex flex-column">
                <h5 class="card-title">Eco Phone Case</h5>
                <p class="card-text small">Protect your phone with style! Made from recycled plastic and biodegradable materials. Features shock-absorbing technology and precise cutouts. Available for iPhone and Samsung models. Each case prevents 3 plastic bottles from entering landfills.</p>
                <div class="mt-auto">
                  <p class="fw-bold text-success fs-5">₱200.00</p>
                  <div class="d-flex justify-content-between align-items-center">
                    <span class="badge bg-light text-dark">Recycled plastic</span>
                    <button class="btn btn-outline-success btn-sm">Add to Cart</button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Second Row of Products -->
          <!-- Product 5 -->
          <div class="col-12 col-md-6 col-lg-3 mb-4">
            <div class="card h-100 border shadow-sm product-card">
              <div class="position-relative">
              <img src="images/glass-vase.jpg" class="card-img-top" alt="Recycled Glass Flower Vase" style="max-height: 200px; object-fit: contain;">
                <span class="badge bg-primary position-absolute top-0 end-0 m-2">Handcrafted</span>
              </div>
              <div class="card-body d-flex flex-column">
                <h5 class="card-title">Recycled Glass Vase</h5>
                <p class="card-text small">Beautifully handcrafted from recycled glass bottles by local artisans. Each piece is unique with subtle variations in color and texture. Perfect for fresh flowers or as a decorative accent. Supports local craftsmanship and reduces glass waste.</p>
                <div class="mt-auto">
                  <p class="fw-bold text-success fs-5">₱250.00</p>
                  <div class="d-flex justify-content-between align-items-center">
                    <span class="badge bg-light text-dark">Upcycled glass</span>
                    <button class="btn btn-outline-success btn-sm">Add to Cart</button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Product 6 -->
          <div class="col-12 col-md-6 col-lg-3 mb-4">
            <div class="card h-100 border shadow-sm product-card">
              <div class="position-relative">
              <img src="images/wooden-shelf.jpg" class="card-img-top" alt="Upcycled Wooden Shelf" style="max-height: 200px; object-fit: contain;">
                <span class="badge bg-secondary position-absolute top-0 end-0 m-2">Premium</span>
              </div>
              <div class="card-body d-flex flex-column">
                <h5 class="card-title">Upcycled Wooden Shelf</h5>
                <p class="card-text small">Sturdy and stylish shelf crafted from reclaimed wood. Each piece tells a story with its unique grain patterns and natural imperfections. Perfect for displaying books, plants, or decorative items. Supports sustainable forestry practices.</p>
                <div class="mt-auto">
                  <p class="fw-bold text-success fs-5">₱350.00</p>
                  <div class="d-flex justify-content-between align-items-center">
                    <span class="badge bg-light text-dark">Reclaimed wood</span>
                    <button class="btn btn-outline-success btn-sm">Add to Cart</button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Product 7 -->
          <div class="col-12 col-md-6 col-lg-3 mb-4">
            <div class="card h-100 border shadow-sm product-card">
              <div class="position-relative">
              <img src="images/bamboo-toothbrush.jpg" class="card-img-top" alt="Bamboo Toothbrush" style="max-height: 200px; object-fit: contain;">
                <span class="badge bg-success position-absolute top-0 end-0 m-2">Biodegradable</span>
              </div>
              <div class="card-body d-flex flex-column">
                <h5 class="card-title">Bamboo Toothbrush Set</h5>
                <p class="card-text small">Set of 4 biodegradable toothbrushes with bamboo handles and soft bristles. Each brush decomposes naturally, unlike plastic alternatives. Includes travel case made from recycled materials. Perfect for eco-conscious dental care.</p>
                <div class="mt-auto">
                  <p class="fw-bold text-success fs-5">₱80.00</p>
                  <div class="d-flex justify-content-between align-items-center">
                    <span class="badge bg-light text-dark">Bamboo handle</span>
                    <button class="btn btn-outline-success btn-sm">Add to Cart</button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Product 8 -->
          <div class="col-12 col-md-6 col-lg-3 mb-4">
            <div class="card h-100 border shadow-sm product-card">
              <div class="position-relative">
              <img src="images/recycled-pots.jpg" class="card-img-top" alt="Recycled Plastic Plant Pots" style="max-height: 200px; object-fit: contain;">
                <span class="badge bg-warning position-absolute top-0 end-0 m-2">Garden Essential</span>
              </div>
              <div class="card-body d-flex flex-column">
                <h5 class="card-title">Recycled Plant Pots Set</h5>
                <p class="card-text small">Set of 3 small plant pots made from recycled plastic containers. Features drainage holes and comes with saucers. Perfect for succulents, herbs, or small plants. Each set prevents 5 plastic containers from entering landfills.</p>
                <div class="mt-auto">
                  <p class="fw-bold text-success fs-5">₱120.00</p>
                  <div class="d-flex justify-content-between align-items-center">
                    <span class="badge bg-light text-dark">Recycled plastic</span>
                    <button class="btn btn-outline-success btn-sm">Add to Cart</button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Call to Action Section -->
      <div class="container-fluid mt-5 py-5 bg-light">
        <div class="row text-center">
          <div class="col-12">
            <h2 class="fw-bold mb-3">Ready to Make a Difference?</h2>
            <p class="fs-5 text-muted mb-4">Join our community of eco-conscious individuals and start your sustainable journey today.</p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
              <a href="signup.php" class="btn btn-success btn-lg px-4 py-3">Join Eco Cycle</a>
              <a href="login.php" class="btn btn-outline-success btn-lg px-4 py-3">Sign In</a>
            </div>
          </div>
        </div>
      </div>

      <!-- Enhanced Footer bottom -->
      <div id="footer-bottom" class="bg-dark text-white py-5">
        <div class="container-fluid">
          <!-- Content sections -->
          <div class="row text-center">
            <div class="col-md-6 col-lg-3 mb-4">
              <h5 class="text-success">❓ FAQ</h5>
              <p><strong>What is the Ecocycle Nluc?</strong><br>
              A comprehensive platform by DMMMSU to support recycling, environmental sustainability, and community engagement through education and innovation.</p>
            </div>

            <div class="col-md-6 col-lg-3 mb-4">
              <h5 class="text-success">🤝 Community</h5>
              <p>Join a growing network of 500+ recyclers, students, and environmental advocates from DMMMSU and nearby communities. Share ideas, post recyclable materials, and collaborate on eco-friendly projects.</p>
            </div>

            <div class="col-md-6 col-lg-3 mb-4">
              <h5 class="text-success">🌱 About Us</h5>
              <p>The Ecocycle Nluc is an initiative aimed at promoting responsible waste management and recycling through education, innovation, and community engagement. We believe in creating a sustainable future together.</p>
            </div>

            <div class="col-md-6 col-lg-3 mb-4">
              <h5 class="text-success">📞 Contact Us</h5>
              <p>Email: <a href="mailto:ecocycle@dmmmsu.edu.ph" class="text-success">ecocycle@dmmmsu.edu.ph</a><br>
              Phone: (072) 888-1234<br>
              Address: DMMMSU NLUC - Sapilang, Bacnotan, La Union<br>
              Office Hours: Mon-Fri 8:00 AM - 5:00 PM</p>
            </div>
          </div>

          <!-- Copyright -->
          <div class="row justify-content-center mt-4">
            <div class="col-md-6 text-center">
              <p class="text-muted">© 2024 DMMMSU Environmental Concerns, Sustainability and Development Unit. All rights reserved.</p>
            </div>
          </div>
        </div>
      </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
    <script src="js/jquery-1.11.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
    <script src="js/plugins.js"></script>
    <script src="js/script.js"></script>
  </body>
</html>