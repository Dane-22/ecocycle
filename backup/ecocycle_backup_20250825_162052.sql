-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: ecocycledb
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admin_activity_logs`
--

DROP TABLE IF EXISTS `admin_activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_activity_logs` (
  `activity_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `target_type` enum('user','product','order','category','setting','backup') NOT NULL,
  `target_id` int(11) DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`activity_id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_activity_logs`
--

LOCK TABLES `admin_activity_logs` WRITE;
/*!40000 ALTER TABLE `admin_activity_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin_activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_dashboard_stats`
--

DROP TABLE IF EXISTS `admin_dashboard_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_dashboard_stats` (
  `stat_id` int(11) NOT NULL,
  `stat_type` varchar(50) NOT NULL,
  `stat_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`stat_value`)),
  `calculated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`stat_id`),
  KEY `idx_stat_type` (`stat_type`),
  KEY `idx_calculated_at` (`calculated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_dashboard_stats`
--

LOCK TABLES `admin_dashboard_stats` WRITE;
/*!40000 ALTER TABLE `admin_dashboard_stats` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin_dashboard_stats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','admin') DEFAULT 'admin',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (1,'Super Admin','ECSD HEAD','ecsdhead@ecocycle.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','super_admin','active','2025-07-04 17:01:23','2025-07-04 17:01:23');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bard`
--

DROP TABLE IF EXISTS `bard`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bard` (
  `bard_id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bard`
--

LOCK TABLES `bard` WRITE;
/*!40000 ALTER TABLE `bard` DISABLE KEYS */;
INSERT INTO `bard` VALUES (1,'BARD HEAD','BARD HEAD','bardhead@ecocycle.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','active','2025-07-04 09:01:23','2025-07-04 09:01:23');
/*!40000 ALTER TABLE `bard` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bardproducts`
--

DROP TABLE IF EXISTS `bardproducts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bardproducts` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `ecocoins_cost` int(11) NOT NULL,
  `stocks` int(11) NOT NULL DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bardproducts`
--

LOCK TABLES `bardproducts` WRITE;
/*!40000 ALTER TABLE `bardproducts` DISABLE KEYS */;
INSERT INTO `bardproducts` VALUES (1,'vdvxc','SDVCXV',5000,45,'uploads/bard_products/bard_product_688091079690c.jpg','2025-07-23 07:36:39','2025-07-24 01:25:31');
/*!40000 ALTER TABLE `bardproducts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bardproductsredeem`
--

DROP TABLE IF EXISTS `bardproductsredeem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bardproductsredeem` (
  `redeem_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('buyer','seller') NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `ecocoins_spent` int(11) NOT NULL,
  `status` enum('pending','approved','declined','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `order_id` varchar(50) NOT NULL,
  `redeemed_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bardproductsredeem`
--

LOCK TABLES `bardproductsredeem` WRITE;
/*!40000 ALTER TABLE `bardproductsredeem` DISABLE KEYS */;
INSERT INTO `bardproductsredeem` VALUES (0,6,'buyer',1,1,5000,'pending','2025-07-24 01:25:37','2025-07-24 01:25:37',5000.00,'L280POMV','2025-07-24 09:25:37'),(0,6,'buyer',1,1,5000,'pending','2025-08-19 11:49:54','2025-08-19 11:49:54',5000.00,'VY7WAMX6','2025-08-19 19:49:54');
/*!40000 ALTER TABLE `bardproductsredeem` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `buyers`
--

DROP TABLE IF EXISTS `buyers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `buyers` (
  `buyer_id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `documents` varchar(255) DEFAULT NULL,
  `status` enum('active','blocked') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  `ecocoins_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`buyer_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `buyers`
--

LOCK TABLES `buyers` WRITE;
/*!40000 ALTER TABLE `buyers` DISABLE KEYS */;
INSERT INTO `buyers` VALUES (0,'Rey Anjhelo','Reyan','09519520010','reyan@gmail.com','$2y$10$ZOcMVmyMXPnF47Ak5S8M0uj0Kyh23tWkGV8rVnBGW1OnBfe3DAjQO','Sevilla San Fernando City, La Union','[\"reyan_1756129656_0.png\"]','active','2025-08-25 13:47:36','2025-08-25 13:47:44','2025-08-25 21:47:44',0.00),(6,'Aldwin Corial','adiee','09488668013','aldwinb.corial@gmail.com','$2y$10$O10JJ3RLtpaMrwHuhk0rwOsDyikfBwFfnD5VcescMfbs6hJRAWRoq','Bitalag Bacnotan, La Union','[\"adiee_1753099110_0.png\"]','active','2025-07-21 11:58:30','2025-08-25 14:03:22','2025-08-25 22:03:22',465000.00),(7,'Dhessie Caiole','dhes.caoile','09771279238','dhessiecaile@gmail.com','$2y$10$Nc4Ij4nI02jLoTWlV30RleovisHMXtD7FRXqvowinZ6syUX4n8z0q','Santa Rita Agoo, La Union',NULL,'active','2025-07-21 12:01:45','2025-07-21 14:06:53','2025-07-21 22:06:53',0.00),(8,'Reign Jairuh Cariaso','jai_luhh','09982072940','jairuhcariaso@gmail.com','$2y$10$08ZspmmDTe7N3EZNU5XzfuRMchcRqaE/VTfufJmFVOB18MOpkNMK6','Guinabang Bacnotan, La Union',NULL,'active','2025-07-21 12:03:15','2025-07-21 12:03:15',NULL,0.00);
/*!40000 ALTER TABLE `buyers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cart`
--

DROP TABLE IF EXISTS `cart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cart` (
  `cart_id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`cart_id`),
  KEY `buyer_id` (`buyer_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart`
--

LOCK TABLES `cart` WRITE;
/*!40000 ALTER TABLE `cart` DISABLE KEYS */;
/*!40000 ALTER TABLE `cart` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Greenchoice','Products selected for their green impact','2025-07-04 17:01:23'),(2,'Best Seller','Top selling eco products','2025-07-04 17:01:23'),(3,'No Label','Products without special labels','2025-07-04 09:01:23');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ecocoins_transactions`
--

DROP TABLE IF EXISTS `ecocoins_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ecocoins_transactions` (
  `transaction_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('buyer','seller') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transaction_type` enum('earn','spend','transfer','adjustment') NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`transaction_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_user_type` (`user_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ecocoins_transactions`
--

LOCK TABLES `ecocoins_transactions` WRITE;
/*!40000 ALTER TABLE `ecocoins_transactions` DISABLE KEYS */;
INSERT INTO `ecocoins_transactions` VALUES (0,6,'buyer',-22600.00,'spend','Payment for Order #0','2025-08-25 14:01:34'),(1,1,'buyer',-45700.00,'spend','Payment for Order #1','2025-07-20 11:23:51'),(2,6,'buyer',-41200.00,'spend','Payment for Order #1','2025-07-21 13:09:37'),(3,6,'buyer',-41200.00,'spend','Payment for Order #1','2025-07-21 13:36:05');
/*!40000 ALTER TABLE `ecocoins_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','shipped','delivered','cancelled') DEFAULT 'pending',
  `tracking_number` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`order_item_id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (0,0,4,1,120.00,'delivered','LBC233475195'),(1,1,8,1,60.00,'shipped','LBC2334O5093'),(2,1,3,1,237.00,'delivered','LBC233793065');
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','shipped','delivered','cancelled') DEFAULT 'pending',
  `shipping_address` text NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`order_id`),
  KEY `buyer_id` (`buyer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (0,6,226.00,'pending','Bitalag Bacnotan, La Union','ecocoins',NULL,'2025-08-25 14:01:34','2025-08-25 14:01:34'),(1,6,412.00,'pending','Bitalag Bacnotan, La Union','ecocoins',NULL,'2025-07-21 13:36:05','2025-07-21 13:36:05');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `organizations`
--

DROP TABLE IF EXISTS `organizations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `organizations` (
  `organization_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `documents` varchar(255) DEFAULT NULL,
  `org_type` varchar(10) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`organization_id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `organizations`
--

LOCK TABLES `organizations` WRITE;
/*!40000 ALTER TABLE `organizations` DISABLE KEYS */;
INSERT INTO `organizations` VALUES (1,'SITE',NULL,'site@gmail.com','$2y$10$cukP6al1L3wFrytktpmkx.u7w3W1/NUf8nLBntwBJftjLTUsZ1Gzi','09519520018','Sapilang Bacnotan, La Union','[\"site_1756129225_0.docx\"]','buyer','','2025-08-25 13:40:25','2025-08-25 13:40:25'),(2,'17s',NULL,'17s@gmail.com','$2y$10$cKley74GS7AjrQ2ZbtRlH.uM1z4Yt1kHb/gqqIGFPeOCwvuU.gzk.','09773279262','Bitalag Bacnotan, La Union','[\"17s_1756130359_0.docx\"]','buyer','','2025-08-25 13:59:19','2025-08-25 13:59:19'),(3,'grsd',NULL,'grsd@gmail.com','$2y$10$2zivSeP9084F5Sr1E70kse/yVJfJ1CpxMTokuefsQnTD5Xufp7W22','03771279262','Sapilang Bacnotan, La Union','[\"grsd_1756130841_0.docx\"]','seller','','2025-08-25 14:07:21','2025-08-25 14:07:21');
/*!40000 ALTER TABLE `organizations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `image_url` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'inactive',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`product_id`),
  KEY `seller_id` (`seller_id`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,6,1,'Green Choice Collagen & Vitamin C - 60 Capsules','Skin, Nails and Hair Formula -  Premium Collagen Hydrolysate By Green Choice - Anti-Aging Nutritional Supplement Rich In Vitamin C - Skin, Nails & Hair Rejuvenation - Supports Bone & Muscle Health - Made In USA - 60 Capsules',513.00,67,'uploads/product_687e2eb9858822.86441954.png','active','2025-07-21 12:12:41','2025-07-21 12:41:06'),(2,6,1,'Crown Chemical - Floor Care - Green Choice pH Neutral Multi-Surface Cleaner','A professional quality detergent for removing built-up dirt and grime from all types of floors. Neutral pH in solution makes this product ideal for use on finished floors without dulling or stripping.',513.00,89,'uploads/product_687e30e18abbd2.33177856.jpg','active','2025-07-21 12:21:53','2025-07-21 12:41:08'),(3,7,3,'Upcycled Sari Bird House','Another ingenious use of otherwise waste materials. Handmade in India using upcycled saris secured around a  frame creating a colourful and cheerful little abode. The birds will thank you.',237.00,67,'uploads/product_687e32185e14d0.58316921.jpg','active','2025-07-21 12:27:04','2025-07-21 13:36:05'),(4,7,3,'Upcycled Plastic Woven Bag','This vibrant blue handwoven bag is made from upcycled plastic materials, skillfully crafted into a durable and stylish tote. Featuring a traditional basket weave design, it is both eco-friendly and versatile — perfect for shopping, beach trips, or everyday use.',120.00,137,'uploads/product_687e32efa00616.69161349.jpg','active','2025-07-21 12:30:39','2025-08-25 14:01:34'),(6,6,2,'Upcycled Vintage Drapery Hat | Sustainable Handmade Fashion','Add a touch of charm and eco-conscious flair to your outfit with this handcrafted, vintage-style reversible sun hat. Featuring alternating panels of leafy green stripes and delicate red vine patterns on a cream background, this hat blends rustic elegance with bold texture.',115.00,0,'uploads/product_687e364b3e5404.25292379.png','active','2025-07-21 12:44:59','2025-07-21 13:02:54'),(7,8,3,'C2Gin','Tayun Shottt!',4000.00,69,'uploads/product_687e3a5a5a2be8.78240140.jpg','inactive','2025-07-21 13:02:18','2025-07-21 13:02:18'),(8,6,2,'YONG-JANG CHILI GARLIC OIL','NGHANG SARAPPP!\r\n- 100% Made with Fresh Chili & Garlic.\r\n- Punong puno, hindi ka lugi.\r\n- 120 ml\r\nORDER YOURS NOW!!!',60.00,9,'uploads/product_687e3b338130f5.35647293.jpg','active','2025-07-21 13:05:55','2025-07-21 13:36:05');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_history`
--

DROP TABLE IF EXISTS `purchase_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_history` (
  `purchase_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','shipped','delivered','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`purchase_id`),
  KEY `order_id` (`order_id`),
  KEY `buyer_id` (`buyer_id`),
  KEY `product_id` (`product_id`),
  KEY `seller_id` (`seller_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_history`
--

LOCK TABLES `purchase_history` WRITE;
/*!40000 ALTER TABLE `purchase_history` DISABLE KEYS */;
INSERT INTO `purchase_history` VALUES (0,0,6,4,7,1,120.00,'pending','2025-08-25 14:01:34');
/*!40000 ALTER TABLE `purchase_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `redemptions`
--

DROP TABLE IF EXISTS `redemptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `redemptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `cost` decimal(10,2) NOT NULL,
  `order_id` varchar(255) NOT NULL,
  `redemption_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `ecocoins_spent` int(11) NOT NULL DEFAULT 0,
  `redeemed_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `redemptions`
--

LOCK TABLES `redemptions` WRITE;
/*!40000 ALTER TABLE `redemptions` DISABLE KEYS */;
INSERT INTO `redemptions` VALUES (1,6,1,0.00,'L280POMV','2025-07-24 01:25:37',5000,'2025-07-24 09:25:37'),(2,6,1,0.00,'VY7WAMX6','2025-08-19 11:49:54',5000,'2025-08-19 19:49:54');
/*!40000 ALTER TABLE `redemptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sellers`
--

DROP TABLE IF EXISTS `sellers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sellers` (
  `seller_id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `documents` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  `ecocoins_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`seller_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sellers`
--

LOCK TABLES `sellers` WRITE;
/*!40000 ALTER TABLE `sellers` DISABLE KEYS */;
INSERT INTO `sellers` VALUES (6,'Honey Boy Corial','hbnitty','09771279262','honeyboyb.corial@gmail.com','$2y$10$JGW3FXmeQaY4bnQAqm7MLeX8tjBzWZu1aYq/2bm6r8a9a4dzkz6PO','Bitalag Bacnotan, La Union','[\"hbnitty_1753099153_0.docx\"]','','2025-07-21 11:59:13','2025-08-25 14:01:59','2025-08-25 22:01:59',0.00),(7,'Raynan Corial','nannan','09519524018','raynancorial@gmail.com','$2y$10$kbm/k4uFv9tA70f5QZKnK.YUvpAmJXxsPfhZm6PGON0XHIrw0jJmm','Bitalag Bacnotan, La Union','[\"nannan_1753100066_0.docx\"]','','2025-07-21 12:14:27','2025-08-25 14:02:15','2025-08-25 22:02:15',0.00),(8,'Andres Artek','andresartek','09972072978','andresartek@gmail.com','$2y$10$LtOLS9d5zwLF/BtkVP6VquM2ymxR7E04WvH9W8tohYHdmWZKmSHAS','Pandan Bacnotan, La Union',NULL,'','2025-07-21 12:59:23','2025-07-21 13:00:01','2025-07-21 21:00:01',0.00);
/*!40000 ALTER TABLE `sellers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','integer','boolean','json') DEFAULT 'string',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES (1,'backup_enabled','true','boolean','Enable automatic database backups','2025-07-04 17:01:23','2025-07-04 17:01:23'),(2,'backup_frequency','daily','string','Backup frequency: daily, weekly, monthly','2025-07-04 17:01:23','2025-07-04 17:01:23'),(3,'backup_retention_days','30','integer','Number of days to keep backup files','2025-07-04 17:01:23','2025-07-04 17:01:23'),(4,'backup_path','./backups/','string','Directory path for storing backup files','2025-07-04 17:01:23','2025-07-04 17:01:23'),(5,'backup_compression','true','boolean','Enable compression for backup files','2025-07-04 17:01:23','2025-07-04 17:01:23'),(6,'last_backup_date','','string','Date and time of last successful backup','2025-07-04 17:01:23','2025-07-04 17:01:23'),(7,'backup_file_size_limit','100','integer','Maximum backup file size in MB','2025-07-04 17:01:23','2025-07-04 17:01:23'),(8,'auto_restore_enabled','false','boolean','Enable automatic database restore functionality','2025-07-04 17:01:23','2025-07-04 17:01:23');
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transaction_logs`
--

DROP TABLE IF EXISTS `transaction_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transaction_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('buyer','seller','admin') NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `idx_user_type` (`user_type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transaction_logs`
--

LOCK TABLES `transaction_logs` WRITE;
/*!40000 ALTER TABLE `transaction_logs` DISABLE KEYS */;
INSERT INTO `transaction_logs` VALUES (0,6,'buyer','ecocoins_payment','Paid ₱226 using EcoCoins for Order #0','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2025-08-25 14:01:34'),(1,6,'buyer','ecocoins_payment','Paid ₱412 using EcoCoins for Order #1','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36','2025-07-21 13:36:05');
/*!40000 ALTER TABLE `transaction_logs` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-08-25 22:20:52
