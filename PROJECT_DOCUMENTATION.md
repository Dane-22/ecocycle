# Ecocycle Project Documentation

## 🌱 Project Overview

**Ecocycle** is a comprehensive e-commerce platform for buying and selling eco-friendly products, built with PHP and MySQL. The platform supports both buyers and sellers, featuring a complete product catalog, shopping cart, order management, and an innovative ecocoins reward system.

---

## 🏗️ System Architecture

### **Technology Stack**
- **Backend:** PHP 8.0+
- **Database:** MySQL/MariaDB
- **Frontend:** HTML5, CSS3, JavaScript
- **Email Service:** PHPMailer with Gmail SMTP
- **Authentication:** Session-based with password hashing

### **Project Structure**
```
/ecocycle/
├── config/
│   └── database.php          # Database connection
├── PHPMailer/                  # Email sending library
├── uploads/                   # File uploads (products, documents, QR codes)
├── backup/                    # Database backups
├── *.php                     # Application pages (57+ files)
└── *.sql                     # Database schemas and migrations
```

---

## 🗄️ Database Schema

### **Core Tables**

#### **Users & Authentication**
- **`admins`** - System administrators
- **`buyers`** - Customer accounts with ecocoins balance
- **`sellers`** - Vendor accounts with product management
- **`login_attempts`** - Security tracking for failed logins

#### **Products & Catalog**
- **`products`** - Product listings with categories and pricing
- **`categories`** - Product categorization
- **`product_images`** - Product photo gallery
- **`reviews`** - Customer product reviews

#### **Orders & Transactions**
- **`orders`** - Purchase orders with status tracking
- **`order_items`** - Individual products in orders
- **`payments`** - Payment processing records
- **`gcash_payments`** - GCash payment integration

#### **Ecocoins System**
- **`ecocoins_transactions`** - Virtual currency transactions
- **`bardproductsredeem`** - Product redemption records
- **`ecocoins_payment`** - Ecocoins purchase processing

#### **Communication**
- **`messages`** - User messaging system
- **`notifications`** - System notifications

#### **System Management**
- **`system_settings`** - Configuration parameters
- **`backups`** - Database backup records

---

## 🔐 Security Features

### **Login Security System**
- **Failed Login Tracking:** Records IP, timestamp, and user agent
- **Account Lockout:** 5 failed attempts triggers lock
- **Password Reset:** Secure token-based reset with 1-hour expiry
- **Email Notifications:** Automatic security alerts via PHPMailer

### **Password Reset Flow**
1. **5 failed attempts** → Account locked
2. **Email sent** with secure reset token
3. **1-hour expiry** on reset links
4. **Token validation** using PHP time comparison
5. **Automatic cleanup** of login attempts after reset

### **Data Protection**
- **Password Hashing:** bcrypt with salt
- **Session Management:** Secure session handling
- **Input Validation:** XSS and SQL injection prevention
- **File Upload Security:** Type and size validation

---

## 💰 Ecocoins System

### **Virtual Currency**
- **Earning:** Customers earn ecocoins through purchases and activities
- **Redemption:** Exchange ecocoins for products
- **Purchase:** Buy additional ecocoins via payment gateway
- **Balance Tracking:** Real-time balance updates

### **Transaction Types**
- **Purchase Rewards:** Earn coins when buying products
- **Product Redemption:** Spend coins on exclusive items
- **Direct Purchase:** Buy coins with real money
- **Admin Adjustments:** Manual balance corrections

---

## 🛒 E-commerce Features

### **Product Management**
- **Multi-category:** Organized product catalog
- **Image Gallery:** Multiple product photos
- **Pricing System:** Flexible pricing with discounts
- **Inventory Tracking:** Stock level management
- **Seller Dashboard:** Complete product control panel

### **Shopping Cart & Checkout**
- **Cart Management:** Add/remove items with quantity control
- **Guest Checkout:** Purchase without account requirement
- **Payment Integration:** Multiple payment methods (GCash, Ecocoins)
- **Order Processing:** Complete order lifecycle management

### **Order Management**
- **Status Tracking:** Pending → Processing → Shipped → Delivered
- **Order History:** Complete purchase records
- **Seller Notifications:** Automatic order alerts
- **Receipt Generation:** Digital order receipts

---

## 👥 User Roles & Permissions

### **Buyers (Customers)**
- **Browse Products:** View catalog with search and filters
- **Shopping Cart:** Add items and checkout
- **Order Management:** Track orders and view history
- **Profile Management:** Personal information and addresses
- **Ecocoins:** View balance and transaction history
- **Reviews:** Rate and review purchased products

### **Sellers (Vendors)**
- **Product Management:** Create, edit, and delete products
- **Order Fulfillment:** Process and ship customer orders
- **Sales Analytics:** Revenue and performance reports
- **Profile Management:** Business information and settings
- **Communication:** Message with customers
- **GCash Integration:** Receive payments via QR codes

### **Administrators**
- **User Management:** Approve/reject seller accounts
- **Product Moderation:** Review and manage listings
- **Order Oversight:** Monitor all transactions
- **System Configuration:** Manage settings and features
- **Database Management:** Backup and restore operations

---

## 📧 Email & Communication

### **PHPMailer Integration**
- **Gmail SMTP:** Secure email delivery
- **Templates:** Professional HTML email designs
- **Transactional Emails:** 
  - Account verification
  - Password reset notifications
  - Order confirmations
  - Support request notifications
  - Security alerts

### **Support System**
- **Contact Form:** Categorized issue reporting
- **Urgency Levels:** High/Medium/Low priority
- **Auto-Response:** Immediate confirmation emails
- **Admin Notifications:** Detailed support tickets
- **Reference Tracking:** Unique IDs for each request

---

## 🎨 Frontend Features

### **Responsive Design**
- **Mobile-First:** Optimized for all screen sizes
- **Modern UI:** Clean, professional interface
- **Accessibility:** WCAG compliance considerations
- **Progressive Enhancement:** Works without JavaScript

### **User Experience**
- **Intuitive Navigation:** Clear menu structure
- **Search & Filter:** Advanced product discovery
- **Real-time Updates:** AJAX for cart and notifications
- **Visual Feedback:** Loading states and confirmations

---

## 🔧 Development & Maintenance

### **Database Management**
- **Automated Backups:** Scheduled database exports
- **Migration Scripts:** Schema update utilities
- **Structure Validation:** Database integrity checks
- **Performance Monitoring:** Query optimization

### **Debugging Tools**
- **Error Logging:** Comprehensive error tracking
- **Debug Scripts:** Token validation and database testing
- **Security Testing:** Login attempt simulation
- **Performance Analysis:** Load time monitoring

---

## 📁 Key Files Overview

### **Core Application Files**
- **`index.php`** - Main landing page
- **`login.php`** - User authentication with security features
- **`signup.php`** - User registration
- **`home.php`** - User dashboard hub
- **`profile.php`** - User profile management

### **E-commerce Files**
- **`product-details.php`** - Individual product pages
- **`mycart.php`** - Shopping cart management
- **`checkout.php`** - Order processing
- **`myorders.php`** - Customer order history

### **Seller Management**
- **`seller-dashboard.php`** - Vendor control panel
- **`seller-createproduct.php`** - Product creation
- **`seller-manageproducts.php`** - Product inventory
- **`seller-manageorders.php`** - Order fulfillment

### **System Administration**
- **`bardashboard.php`** - Admin control panel
- **`customer-service.php`** - User management
- **`profile.php`** - Account settings

### **Security & Support**
- **`reset-password.php`** - Secure password reset
- **`contact-us.php`** - Customer support system
- **`forgot-password.php`** - Password recovery

### **Utilities**
- **`config/database.php`** - Database configuration
- **`header.php`** - Common page header
- **`logout.php`** - Session termination

---

## 🚀 Deployment & Configuration

### **Server Requirements**
- **PHP 8.0+** with PDO and MySQL extensions
- **MySQL/MariaDB** 5.6+ or equivalent
- **Web Server:** Apache/Nginx with mod_rewrite
- **SSL Certificate:** HTTPS recommended
- **File Permissions:** Writable uploads and backup directories

### **Configuration Files**
- **Database:** `config/database.php`
- **Email:** PHPMailer settings in individual files
- **Paths:** Absolute paths for file operations
- **Security:** Session and cookie parameters

---

## 🔍 Testing & Quality Assurance

### **Security Testing**
- **SQL Injection:** Parameterized queries throughout
- **XSS Prevention:** Output encoding and CSP headers
- **CSRF Protection:** Token verification on forms
- **Session Security:** Regeneration and timeout handling

### **Performance Testing**
- **Database Optimization:** Indexed queries and caching
- **Image Optimization:** Compressed uploads and thumbnails
- **Code Efficiency:** Minimal server load design
- **Caching Strategy:** Browser and server caching

---

## 📊 Analytics & Reporting

### **Business Intelligence**
- **Sales Reports:** Revenue and product performance
- **User Analytics:** Registration and activity metrics
- **Conversion Tracking:** Purchase funnel analysis
- **Inventory Reports:** Stock and sales correlation

### **System Monitoring**
- **Error Tracking:** Comprehensive logging system
- **Performance Metrics:** Response time monitoring
- **Usage Statistics:** Feature utilization tracking
- **Security Alerts:** Failed login and suspicious activity

---

## 🔄 Future Enhancements

### **Planned Features**
- **Mobile Application:** Native iOS/Android apps
- **Advanced Analytics:** Real-time dashboard
- **API Integration:** Third-party service connections
- **Enhanced Security:** Two-factor authentication
- **Internationalization:** Multi-language support

### **Scalability Considerations**
- **Load Balancing:** Multi-server deployment
- **Database Optimization:** Query performance tuning
- **CDN Integration:** Content delivery optimization
- **Microservices:** Modular architecture planning

---

## 🛠️ Development Guidelines

### **Code Standards**
- **PSR-4 Autoloading:** Class organization
- **MVC Pattern:** Separation of concerns
- **Error Handling:** Comprehensive exception management
- **Documentation:** Inline comments and API docs

### **Security Best Practices**
- **Input Validation:** All user data sanitized
- **Output Encoding:** XSS prevention
- **Authentication:** Secure session management
- **Authorization:** Role-based access control

---

## 📞 Support & Maintenance

### **Troubleshooting**
- **Debug Scripts:** Token validation and database testing
- **Log Analysis:** Error pattern identification
- **Performance Monitoring:** Bottleneck detection
- **Backup Recovery:** Point-in-time restoration

### **Regular Maintenance**
- **Database Backups:** Automated daily exports
- **Log Rotation:** Prevent disk space issues
- **Security Updates:** Regular dependency updates
- **Performance Tuning:** Query optimization reviews

---

## 📝 Conclusion

The **Ecocycle** platform represents a comprehensive e-commerce solution with innovative features like the ecocoins reward system and robust security measures. The modular architecture allows for easy maintenance and future enhancements while maintaining high security and performance standards.

**Key Strengths:**
- ✅ Complete e-commerce functionality
- ✅ Advanced security features
- ✅ Innovative reward system
- ✅ Professional user experience
- ✅ Scalable architecture
- ✅ Comprehensive admin tools

**Ready for production deployment** with ongoing monitoring and enhancement opportunities.

---

*Documentation generated: February 6, 2026*
*Last updated: Current project state*
