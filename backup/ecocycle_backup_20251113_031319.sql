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
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `ecocoins_cost` int(11) NOT NULL,
  `stocks` int(11) NOT NULL DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bardproducts`
--

LOCK TABLES `bardproducts` WRITE;
/*!40000 ALTER TABLE `bardproducts` DISABLE KEYS */;
INSERT INTO `bardproducts` VALUES (1,'1 Yellow Pad (80 leaves)','Made from recyclable paper, the yellow pad is perfect for notes, assignments, and everyday writing tasks. Durable and eco-friendly, it supports your studies while promoting sustainable choices.\r\nsize (8.5” x 11”)\r\n80 sheets of quality writing paper\r\nEco-friendly and recyclable\r\nIdeal for students and professionals',10000,56,'uploads/bard_products/bard_product_68c6e3a94d1d8.png','2025-09-14 15:47:53','2025-09-14 15:47:53'),(2,'Flexstick Ballpen','Write with ease and confidence using the FlexStick Smooth Ink Pen! Designed for students and professionals, it delivers clean, consistent, and super smooth writing. Perfect for notes, assignments, and everyday use.',7000,87,'uploads/bard_products/bard_product_68c6e5f726af1.jpg','2025-09-14 15:57:43','2025-09-14 15:57:43'),(3,'Journal Notebook','Stay organized and inspired with our Journal Notebook — perfect for students, professionals, and creatives! Whether you’re jotting down daily thoughts, class notes, or big ideas, this notebook is designed to keep your writing neat and stylish.\r\nPerfect Gift Idea: Ideal for students, writers, or anyone who loves stationery!',80000,29,'uploads/bard_products/bard_product_690a8146ec264.jpg','2025-11-04 22:42:14','2025-11-04 22:42:14'),(4,'DMMMSU ID LACE','The New Designed ID Lace of Dmmmsu',8500,60,'uploads/bard_products/bard_product_6914ef286b6ee.jpg','2025-11-12 20:33:44','2025-11-12 20:33:44');
/*!40000 ALTER TABLE `bardproducts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bardproductsredeem`
--

DROP TABLE IF EXISTS `bardproductsredeem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bardproductsredeem` (
  `redeem_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `redeemed_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`redeem_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bardproductsredeem`
--

LOCK TABLES `bardproductsredeem` WRITE;
/*!40000 ALTER TABLE `bardproductsredeem` DISABLE KEYS */;
INSERT INTO `bardproductsredeem` VALUES (1,1,'buyer',1,1,10000,'','2025-09-25 03:08:19','2025-09-25 03:42:01',10000.00,'6PRPCQ6R','2025-09-25 11:08:19'),(2,1,'buyer',2,1,7000,'approved','2025-09-25 03:46:10','2025-09-25 03:46:36',7000.00,'0IYK3VCQ','2025-09-25 11:46:10');
/*!40000 ALTER TABLE `bardproductsredeem` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `buyers`
--

DROP TABLE IF EXISTS `buyers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `buyers` (
  `buyer_id` int(11) NOT NULL AUTO_INCREMENT,
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
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `buyers`
--

LOCK TABLES `buyers` WRITE;
/*!40000 ALTER TABLE `buyers` DISABLE KEYS */;
INSERT INTO `buyers` VALUES (1,'Honey Boy Corial','hbcorial','09771279262','honeyboyb.corial@gmail.com','$2y$10$VySWkXz8FZWbs3YvA2ly6ezYerUskWL73VRVMUQnEnOgmqPgGoPaO','Bitalag, Bacnotan, La Union','[\"hb_1757858646_0.docx\"]','blocked','2025-09-14 14:04:06','2025-10-24 02:04:23','2025-10-07 08:35:36',0.00),(2,'Raynan B. Corial','rinanzyy','09708152859','raynanbucsitcorial@gmail.com','$2y$10$xnYw75w4mhaIzoSVCVdi8.uFA8/Y2Op26NVfzBb9B6d6kFvjpHgeS','Bitalag Bacnotan, La Union','[\"rinanzyy_1757858934_0.jpg\"]','active','2025-09-14 14:08:54','2025-11-13 02:10:35','2025-11-13 10:10:35',11.20),(3,'Racy Corial','racyb','09632679342','racycorial@gmail.com','$2y$10$5Bibejk0LLDHFYUnyoXLt.aV5F1jCCmlAx5mH.q37d0BAHwQ0cWu6','Bitalag, Bacnotan, La Union','[\"racyb_1757859542_0.jpg\"]','active','2025-09-14 14:19:02','2025-11-06 12:17:05','2025-11-06 20:16:50',2.45),(4,'Julliana Aiah Palle','aiaah','09632679349','juliana@gmail.com','$2y$10$3GHYxktVs.Idw3lzwMicjev/.f7CfKQDdDICmbdKLUTrBoWQSGcZK','Sta Lucia, Ilocos Sur','[\"aiaah_1757859817_0.jpg\"]','active','2025-09-14 14:23:37','2025-09-14 14:23:37',NULL,0.00),(5,'Joehna Faye Ortega','fayee','09489368017','joehnafaye11@gmail.com','$2y$10$MqM2i913wHy4f.nov28S8OzM7GPy8vlhmVSbMI0/smdGIfublMOyu','Paringao, Bauang La Union','[\"fayee_1757860021_0.jpg\"]','active','2025-09-14 14:27:01','2025-09-14 14:27:01',NULL,0.00),(6,'Joehlia Faith Ortega','jhl.fth','09635679094','joehliafaithortega@gmail.com','$2y$10$Af17GpDraapuus8vIQCk9OfJ6KJHf6yMytXfGOrctNf/8StScsgCS','Paringao, Bauang La Union','[\"jhlfth_1757860190_0.jpg\"]','active','2025-09-14 14:29:50','2025-11-05 02:37:34','2025-11-05 10:37:34',0.00),(7,'Jaypee','Japong','09457826481','jaypeenarnola@icloud.com','$2y$10$GIkOH0gQAsypBPQE8TTKluok97vBJZ/pP4bYRq2PkM3D6DrjnwXsK','Pandan, Bacnotan, La Union','[\"japong_1757895124_0.jpg\"]','active','2025-09-15 00:12:04','2025-09-15 00:16:36','2025-09-15 08:16:36',0.00),(8,'Marc Sonwright D. Cachero','Macky','09390828608','cmarcsonwright@gmail.com','$2y$10$RZJ2cK7igg83RsYQewIc.elFYS4JQES6/635slZPbzcZAjgk5d11q','Upper Tumapoc Burgos, La Uinon','[\"macky_1759195164_0.pdf\"]','active','2025-09-30 01:19:24','2025-09-30 01:19:52','2025-09-30 09:19:52',0.00),(9,'Abner','Abnerviernes','09457826000','abner@gmail.com','$2y$10$RtEsdvVnDrE9r2jH31/woeWuIsA0xCbUVGeknb7K9T4LRFVAUlR/S','Cabaroan, Bacnotan, La Union','[\"abnerviernes_1760420121_0.png\"]','active','2025-10-14 05:35:22','2025-10-14 05:36:40','2025-10-14 13:36:40',0.00),(10,'SITE','site','09635679094','site@dmmmsu.edu.ph','$2y$10$6iXhR9Q.lRqhCjXkW0UAS.qDGwjJNHA9DxVYm4LFVDjnG4UKOphxa','Cabaroan, Bacnotan, La Union','[\"site_1760444624_0.docx\"]','','2025-10-14 12:23:45','2025-10-14 12:23:45',NULL,0.00),(11,'17s','17sculture','09390828123','17sculture@ph.org','$2y$10$nk/tDlhJLenYQQarOgMaQeYouQNu3xVKDOjL4c.07TZGqrLkG8gH6','Sta Lucia, Ilocos Sur','[\"17s_1760445122_0.png\"]','','2025-10-14 12:32:02','2025-10-14 12:32:02',NULL,0.00),(13,'THDM ELYU','thdmelyu','09630679342','thdmelyu@motor.org','$2y$10$7MhJILUvpEJB6x7un1t3tuWsl3AIJJAXLXqJZH67vA3EGThd6E5Gq','Agoo, La Union','[\"thdmelyu_1762171566_0.png\"]','','2025-11-03 12:06:07','2025-11-03 12:06:07',NULL,0.00),(14,'Vench Axel Ross Gliam','Vench01','09686041649','axellexa122@gmail.com','$2y$10$31kL2ZrK0ygzLVUEc8VrBuxewSQ7nQnSgMCh7xjIxl5aDkYvL1P1C','Baroro, Bacnotan, La Union','[\"vench01_1762310657_0.jpg\"]','active','2025-11-05 02:44:17','2025-11-05 02:53:09','2025-11-05 10:53:09',1.55),(15,'YES','yes','09708152859','yes@gmail.org','$2y$10$YIwjX/sH/JX6O8e3ZK2AAeKdNQGMuxpbfHdwGvcesP0koOIsGvyHW','Sapilang, Bacnotan, La Union','[\"yes_1762408716_0.pdf\"]','','2025-11-06 05:58:37','2025-11-06 05:58:37',NULL,0.00);
/*!40000 ALTER TABLE `buyers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cart`
--

DROP TABLE IF EXISTS `cart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cart` (
  `cart_id` int(11) NOT NULL AUTO_INCREMENT,
  `buyer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`cart_id`),
  UNIQUE KEY `unique_buyer_product` (`buyer_id`,`product_id`),
  KEY `buyer_id` (`buyer_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart`
--

LOCK TABLES `cart` WRITE;
/*!40000 ALTER TABLE `cart` DISABLE KEYS */;
INSERT INTO `cart` VALUES (18,6,7,1,'2025-11-05 02:38:19');
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
  `transaction_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `user_type` enum('buyer','seller') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transaction_type` enum('earn','spend','transfer','adjustment') NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`transaction_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_user_type` (`user_type`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ecocoins_transactions`
--

LOCK TABLES `ecocoins_transactions` WRITE;
/*!40000 ALTER TABLE `ecocoins_transactions` DISABLE KEYS */;
INSERT INTO `ecocoins_transactions` VALUES (1,3,'buyer',1.20,'','EcoCoins awarded for Order #2','2025-10-24 03:02:43'),(2,2,'buyer',1.25,'','EcoCoins awarded for Order #4','2025-10-29 00:55:06'),(3,14,'buyer',0.30,'','EcoCoins awarded for Order #6','2025-11-05 02:51:02');
/*!40000 ALTER TABLE `ecocoins_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT,
  `buyer_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `sender_type` enum('buyer','admin') NOT NULL,
  `message_text` text NOT NULL,
  `feedback_text` text DEFAULT NULL,
  `status` enum('sent','received','replied','archived') DEFAULT 'sent',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`message_id`),
  KEY `buyer_id` (`buyer_id`),
  KEY `admin_id` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','shipped','delivered','cancelled') DEFAULT 'pending',
  `tracking_number` varchar(100) DEFAULT NULL,
  `payment_receipt` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`order_item_id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (1,1,12,1,150.00,'delivered','LBC233475093',NULL),(2,1,7,1,120.00,'delivered','LBC233793065',NULL),(3,2,7,1,120.00,'delivered','LBC233793065','uploads/receipts/1761274963_26047f456b71.png'),(4,3,7,1,120.00,'delivered','LBC233475077',NULL),(5,4,2,1,125.00,'shipped','LBC233400893','uploads/receipts/1761699306_ca9d5c8ae33c.png'),(6,5,2,1,125.00,'cancelled','',NULL),(7,6,17,1,30.00,'pending',NULL,'uploads/receipts/1762311062_7a94f447de4c.jpg'),(8,7,12,1,150.00,'pending',NULL,NULL),(9,8,2,1,125.00,'pending',NULL,NULL),(10,9,12,1,150.00,'pending',NULL,NULL),(11,10,2,1,125.00,'shipped','LBC233475093',NULL),(12,10,17,1,30.00,'shipped','LBC233475093',NULL),(13,11,12,1,150.00,'pending',NULL,NULL);
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_views`
--

DROP TABLE IF EXISTS `order_views`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_views` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `viewed_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_order_seller` (`order_id`,`seller_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_views`
--

LOCK TABLES `order_views` WRITE;
/*!40000 ALTER TABLE `order_views` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_views` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,2,334.00,'pending','Bitalag Bacnotan, La Union','cod',NULL,'2025-10-24 02:10:23','2025-10-24 02:10:23'),(2,3,176.00,'pending','Bitalag, Bacnotan, La Union','gcash',NULL,'2025-10-24 03:02:43','2025-10-24 03:02:43'),(3,2,176.00,'pending','Bitalag Bacnotan, La Union','cod',NULL,'2025-10-24 03:18:10','2025-10-24 03:18:10'),(4,2,181.00,'pending','Bitalag Bacnotan, La Union','gcash',NULL,'2025-10-29 00:55:06','2025-10-29 00:55:06'),(5,14,181.00,'pending','Baroro, Bacnotan, La Union','cod',NULL,'2025-11-05 02:45:19','2025-11-05 02:45:19'),(6,14,82.00,'pending','Baroro, Bacnotan, La Union','gcash',NULL,'2025-11-05 02:51:02','2025-11-05 02:51:02'),(7,2,208.00,'pending','Bitalag Bacnotan, La Union','cod',NULL,'2025-11-06 06:28:11','2025-11-06 06:28:11'),(8,3,181.00,'pending','Bitalag, Bacnotan, La Union','cod',NULL,'2025-11-06 12:17:05','2025-11-06 12:17:05'),(9,2,208.00,'pending','Bitalag Bacnotan, La Union','cod',NULL,'2025-11-07 13:35:10','2025-11-07 13:35:10'),(10,2,213.00,'pending','Bitalag Bacnotan, La Union','cod',NULL,'2025-11-13 01:20:52','2025-11-13 01:20:52'),(11,2,208.00,'pending','Bitalag Bacnotan, La Union','cod',NULL,'2025-11-13 01:58:25','2025-11-13 01:58:25');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `seller_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `producers` varchar(255) DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,1,1,NULL,'Lucas Papaw Ointment','A soothing herbal ointment made from fermented papaya with antiseptic and healing properties. Used commonly for dry, chapped, cracked, or mildly irritated skin.',499.00,31,'uploads/product_68c6d1ef403b43.67469243.png','active','2025-09-14 14:32:15','2025-09-25 02:59:00'),(2,1,1,NULL,'Moreishi Herbal Cream 10 g','A small herbal cream, often used for light skin irritations or moisturizing; ingredients likely include herbal extracts. Price: ~ ₱125 via Shopee.',125.00,182,'uploads/product_68c6d2e5f275d2.97390346.png','active','2025-09-14 14:36:21','2025-11-13 01:20:52'),(4,1,1,NULL,'Comfort Balm 10 g','A balm by Human Nature (a brand known for more natural ingredients), used for soothing purposes (e.g. dry skin, irritated areas).',113.76,55,'uploads/product_68c6d3458de263.15516171.png','active','2025-09-14 14:37:57','2025-10-05 10:50:16'),(5,2,3,NULL,'Recycled Sari Bag','A bag made from recycled sari (i.e. repurposed fabric/material). Smaller, simple style.',100.00,392,'uploads/product_68c6d4a7b637f8.41568770.png','active','2025-09-14 14:43:51','2025-09-14 14:46:27'),(6,2,2,NULL,'Recycled Nylon Crossbody Bag','Medium sized crossbody “dumpling”-bag, using recycled nylon. Stylish & casual.',1715.52,43,'uploads/product_68c6d52ed74755.68850609.png','active','2025-09-14 14:46:06','2025-09-14 14:46:29'),(7,1,3,NULL,'Recycled Water Bottled','Water bottle made from recycled PET plastic. Good for on-the-go hydration.',120.00,64,'uploads/product_68c6d5dc4853e2.00453896.png','active','2025-09-14 14:49:00','2025-10-24 03:18:10'),(8,2,2,NULL,'Ruffle Edge Recycled Glass Clear Vase','Large clear vase, ruffled edge, made from recycled glass.',682.50,31,'uploads/product_68c6d73d3267c1.62043025.png','active','2025-09-14 14:54:53','2025-10-02 07:03:52'),(9,2,2,NULL,'Padma Vase Recycled Glass Jade:','Padma Jade-green recycled glass vase.',580.00,135,'uploads/product_68c6d79b9ca057.82011817.png','active','2025-09-14 14:56:27','2025-10-21 23:26:42'),(10,2,2,NULL,'Steel Straw Starter Set','What’s included: Stainless steel straight straw, bent straw, straw brush / cleaner.\r\nMaterial: Stainless steel.',99.00,20,'uploads/product_68c6d891ecc169.13941913.png','active','2025-09-14 15:00:33','2025-10-24 00:06:17'),(11,1,2,NULL,'Wheat Straw Utensils Travel Set','What’s included: Fork, spoon, chopsticks, in a compact box.\r\nMaterial: Wheat straw / fiber.',110.00,0,'uploads/product_68c6d92a7c15c0.09874493.png','active','2025-09-14 15:03:06','2025-09-30 01:10:55'),(12,2,3,NULL,'Eco-Style Succulent Plants','Add a pop of life and color to your home or office with these eco-friendly succulent plants!\r\nEach plant comes in a uniquely designed recycled pot—perfect for plant lovers, gift ideas, or room décor.',150.00,19,'uploads/product_68c6db0fa3ed68.84620184.png','active','2025-09-14 15:11:11','2025-11-13 01:58:25'),(14,1,3,'Rand Jewel Cariaso\r\nAlyssa Melecio','yongs Chili Garlic','Chili garlic 120ml',150.00,34,'uploads/product_68e45b81264c57.62633074.jpg','active','2025-10-07 00:14:57','2025-11-06 11:03:43'),(15,1,2,'DMMMMSU NLUC ATBI','Dmmmsu Atbi Honey Jam','A premium honey-based spread made by DMMMSU–NARTDI using pure, locally harvested honey.',400.00,79,'uploads/product_68eb6e3e8b6ba3.48290968.jpg','active','2025-10-12 09:00:46','2025-11-06 05:54:47'),(16,2,1,'Green Choice Philippines\r\n','Traditional Medicinals Organic Green Tea with Lemongrass (16 Tea Bags)','This premium herbal tea blend combines the refreshing taste of organic green tea with the calming aroma of lemongrass. Made with 100% certified organic ingredients, it provides a smooth, mild flavor perfect for daily wellness. Known for its antioxidants, this tea supports metabolism, boosts energy naturally, and promotes overall health. Each tea bag is individually wrapped for freshness.',250.00,160,'uploads/product_68eb701e464cc3.44205819.png','active','2025-10-12 09:08:46','2025-11-04 22:04:59'),(17,1,3,'Aurora Bucsit Angelito','MDew Parol','MDew Crafted Parol made with recycled mountain dew bottles',30.00,40,'uploads/product_690a78074789e4.44604976.jpg','active','2025-11-04 22:02:47','2025-11-13 01:20:52'),(18,1,3,'Edelita C. Ebuenga','keychain','chuchu',50.00,899,'uploads/product_691534c90a8b40.03344088.jpeg','inactive','2025-11-13 01:30:49','2025-11-13 01:30:49');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_history`
--

DROP TABLE IF EXISTS `purchase_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_history` (
  `purchase_id` int(11) NOT NULL AUTO_INCREMENT,
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
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_history`
--

LOCK TABLES `purchase_history` WRITE;
/*!40000 ALTER TABLE `purchase_history` DISABLE KEYS */;
INSERT INTO `purchase_history` VALUES (1,1,2,12,2,1,150.00,'pending','2025-10-24 02:10:23'),(2,1,2,7,1,1,120.00,'pending','2025-10-24 02:10:23'),(3,2,3,7,1,1,120.00,'pending','2025-10-24 03:02:43'),(4,3,2,7,1,1,120.00,'pending','2025-10-24 03:18:10'),(5,4,2,2,1,1,125.00,'pending','2025-10-29 00:55:06'),(6,5,14,2,1,1,125.00,'pending','2025-11-05 02:45:19'),(7,6,14,17,1,1,30.00,'pending','2025-11-05 02:51:02'),(8,7,2,12,2,1,150.00,'pending','2025-11-06 06:28:11'),(9,8,3,2,1,1,125.00,'pending','2025-11-06 12:17:05'),(10,9,2,12,2,1,150.00,'pending','2025-11-07 13:35:10'),(11,10,2,2,1,1,125.00,'pending','2025-11-13 01:20:52'),(12,10,2,17,1,1,30.00,'pending','2025-11-13 01:20:52'),(13,11,2,12,2,1,150.00,'pending','2025-11-13 01:58:25');
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `redemptions`
--

LOCK TABLES `redemptions` WRITE;
/*!40000 ALTER TABLE `redemptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `redemptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sellers`
--

DROP TABLE IF EXISTS `sellers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sellers` (
  `seller_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `gcash_qr` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`seller_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sellers`
--

LOCK TABLES `sellers` WRITE;
/*!40000 ALTER TABLE `sellers` DISABLE KEYS */;
INSERT INTO `sellers` VALUES (1,'Honey Boy Bucsit Corial','adiee','09771279262','honeyboycorial64@gmail.com','$2y$10$MUaHEMzApG0WjGcVjojQMOxtOBC39A4TbCmMeKjO6MKQCYsuQCl12','Bitalag Bacnotan, La Union','[\"adiee_1757858842_0.jpg\"]','','2025-09-14 14:07:22','2025-11-13 01:28:52','2025-11-13 09:28:52',0.00,'uploads/gcash_qr/gcash_1_1761135310.jpg'),(2,'Fernando Corial','nandzz','09673165419','fernandocorial@gmail.com','$2y$10$do.60csb/J8OstXCLxoywuqZeiSdMED0Z8cazjg4geDek4Cjcq8BS','Bitalag Bacnotan, La Union','[\"nandzz_1757860110_0.jpg\"]','','2025-09-14 14:28:30','2025-11-13 01:56:09','2025-11-13 09:56:09',0.00,'uploads/gcash_qr/gcash_2_1762295411.jpg'),(6,'THDM ELYU','thdmelyu','09630679342','thdmelyu@motor.org','$2y$10$7MhJILUvpEJB6x7un1t3tuWsl3AIJJAXLXqJZH67vA3EGThd6E5Gq','Agoo, La Union','[\"thdmelyu_1762171566_0.png\"]','','2025-11-03 12:06:07','2025-11-03 12:06:07',NULL,0.00,NULL),(7,'YES','yes','09708152859','yes@gmail.org','$2y$10$YIwjX/sH/JX6O8e3ZK2AAeKdNQGMuxpbfHdwGvcesP0koOIsGvyHW','Sapilang, Bacnotan, La Union','[\"yes_1762408716_0.pdf\"]','','2025-11-06 05:58:37','2025-11-06 05:59:14','2025-11-06 13:59:14',0.00,NULL),(8,'Baniely Munoz Pajarit','banilayy','09950090082','banielympajarit@gmail.com','$2y$10$ZJI4Yxw1gZG9nO3Fk/8hwO/5bTnPig1zLdpnSBgBrmfZfjNcvi0ey','Sta Rita Bacnotan, La Union','[\"banilayy_1762955140_0.docx\"]','','2025-11-12 13:45:40','2025-11-12 13:46:57','2025-11-12 21:45:53',0.00,'uploads/gcash_qr/gcash_8_1762955217.png');
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
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
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
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transaction_logs`
--

LOCK TABLES `transaction_logs` WRITE;
/*!40000 ALTER TABLE `transaction_logs` DISABLE KEYS */;
INSERT INTO `transaction_logs` VALUES (1,2,'buyer','order_placed','COD order placed, Order ID: 1, Amount: 334','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-10-24 02:10:23'),(2,3,'buyer','order_placed','GCash order placed, Order ID: 2, Amount: 176','192.168.8.39','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36','2025-10-24 03:02:43'),(3,2,'buyer','order_placed','COD order placed, Order ID: 3, Amount: 176','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-10-24 03:18:10'),(4,2,'buyer','order_placed','GCash order placed, Order ID: 4, Amount: 181','192.168.8.39','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36','2025-10-29 00:55:06'),(5,2,'seller','profile_update','Profile updated: Fernando Corial','192.168.8.38',NULL,'2025-11-04 22:20:11'),(6,2,'seller','profile_update','Profile updated: Fernando Corial','192.168.8.38',NULL,'2025-11-04 22:29:07'),(7,2,'seller','profile_update','Profile updated: Fernando Corial','192.168.8.38',NULL,'2025-11-04 22:30:11'),(8,14,'buyer','order_placed','COD order placed, Order ID: 5, Amount: 181','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-11-05 02:45:19'),(9,14,'buyer','order_placed','GCash order placed, Order ID: 6, Amount: 82','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-11-05 02:51:02'),(10,2,'buyer','order_placed','COD order placed, Order ID: 7, Amount: 208','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-06 06:28:11'),(11,3,'buyer','order_placed','COD order placed, Order ID: 8, Amount: 181','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-06 12:17:05'),(12,2,'buyer','order_placed','COD order placed, Order ID: 9, Amount: 208','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-07 13:35:10'),(13,8,'seller','profile_update','Profile updated: Baniely Munoz Pajarit','::1',NULL,'2025-11-12 13:46:57'),(14,2,'buyer','order_placed','COD order placed, Order ID: 10, Amount: 213','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-13 01:20:52'),(15,2,'buyer','order_placed','COD order placed, Order ID: 11, Amount: 208','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-13 01:58:25');
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

-- Dump completed on 2025-11-13 10:13:21
