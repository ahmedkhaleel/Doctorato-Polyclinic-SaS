-- MariaDB dump 10.19  Distrib 10.4.28-MariaDB, for osx10.10 (x86_64)
--
-- Host: localhost    Database: aura
-- ------------------------------------------------------
-- Server version	10.4.28-MariaDB
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `model_type` varchar(255) DEFAULT NULL,
  `model_id` bigint(20) unsigned DEFAULT NULL,
  `changes` longtext,
  `ip_address` varchar(45) DEFAULT NULL,
  `panel` varchar(20) DEFAULT NULL,
  `user_agent` text,
  `description` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_logs_model_type_model_id_index` (`model_type`,`model_id`),
  KEY `activity_logs_user_id_index` (`user_id`),
  KEY `activity_logs_panel_index` (`panel`),
  KEY `activity_logs_action_index` (`action`),
  KEY `activity_logs_created_at_index` (`created_at`),
  CONSTRAINT `activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=125 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (1,1,'deleted','App\\Models\\ContactMessage',1,'{\"old\":{\"id\":1,\"name\":\"\\u0645\\u062d\\u0645\\u062f \\u0639\\u0628\\u062f \\u0627\\u0644\\u0644\\u0647\",\"email\":\"mohamed.abdullah@gmail.com\",\"phone\":\"01012345678\",\"subject\":\"\\u0627\\u0633\\u062a\\u0641\\u0633\\u0627\\u0631 \\u0639\\u0646 \\u062e\\u062f\\u0645\\u0627\\u062a \\u0627\\u0644\\u0639\\u064a\\u0627\\u062f\\u0629\",\"message\":\"\\u0627\\u0644\\u0633\\u0644\\u0627\\u0645 \\u0639\\u0644\\u064a\\u0643\\u0645\\u060c \\u0623\\u0648\\u062f \\u0627\\u0644\\u0627\\u0633\\u062a\\u0641\\u0633\\u0627\\u0631 \\u0639\\u0646 \\u0627\\u0644\\u062e\\u062f\\u0645\\u0627\\u062a \\u0627\\u0644\\u0645\\u062a\\u0627\\u062d\\u0629 \\u0641\\u064a \\u0627\\u0644\\u0639\\u064a\\u0627\\u062f\\u0629 \\u0648\\u062e\\u0627\\u0635\\u0629 \\u062e\\u062f\\u0645\\u0627\\u062a \\u0639\\u0644\\u0627\\u062c \\u062d\\u0628 \\u0627\\u0644\\u0634\\u0628\\u0627\\u0628 \\u0648\\u0625\\u0632\\u0627\\u0644\\u0629 \\u0622\\u062b\\u0627\\u0631\\u0647. \\u0647\\u0644 \\u064a\\u0645\\u0643\\u0646\\u0643\\u0645 \\u0625\\u0631\\u0633\\u0627\\u0644 \\u0642\\u0627\\u0626\\u0645\\u0629 \\u0628\\u0627\\u0644\\u062e\\u062f\\u0645\\u0627\\u062a \\u0648\\u0627\\u0644\\u0623\\u0633\\u0639\\u0627\\u0631\\u061f \\u0634\\u0643\\u0631\\u0627\\u064b \\u0644\\u0643\\u0645.\",\"is_read\":true}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Deleted ContactMessage \"محمد عبد الله\"','2026-03-08 13:42:18','2026-03-08 13:42:18'),(2,1,'deleted','App\\Models\\ContactMessage',1,'{\"old\":{\"id\":1,\"name\":\"\\u0645\\u062d\\u0645\\u062f \\u0639\\u0628\\u062f \\u0627\\u0644\\u0644\\u0647\",\"email\":\"mohamed.abdullah@gmail.com\",\"phone\":\"01012345678\",\"subject\":\"\\u0627\\u0633\\u062a\\u0641\\u0633\\u0627\\u0631 \\u0639\\u0646 \\u062e\\u062f\\u0645\\u0627\\u062a \\u0627\\u0644\\u0639\\u064a\\u0627\\u062f\\u0629\",\"message\":\"\\u0627\\u0644\\u0633\\u0644\\u0627\\u0645 \\u0639\\u0644\\u064a\\u0643\\u0645\\u060c \\u0623\\u0648\\u062f \\u0627\\u0644\\u0627\\u0633\\u062a\\u0641\\u0633\\u0627\\u0631 \\u0639\\u0646 \\u0627\\u0644\\u062e\\u062f\\u0645\\u0627\\u062a \\u0627\\u0644\\u0645\\u062a\\u0627\\u062d\\u0629 \\u0641\\u064a \\u0627\\u0644\\u0639\\u064a\\u0627\\u062f\\u0629 \\u0648\\u062e\\u0627\\u0635\\u0629 \\u062e\\u062f\\u0645\\u0627\\u062a \\u0639\\u0644\\u0627\\u062c \\u062d\\u0628 \\u0627\\u0644\\u0634\\u0628\\u0627\\u0628 \\u0648\\u0625\\u0632\\u0627\\u0644\\u0629 \\u0622\\u062b\\u0627\\u0631\\u0647. \\u0647\\u0644 \\u064a\\u0645\\u0643\\u0646\\u0643\\u0645 \\u0625\\u0631\\u0633\\u0627\\u0644 \\u0642\\u0627\\u0626\\u0645\\u0629 \\u0628\\u0627\\u0644\\u062e\\u062f\\u0645\\u0627\\u062a \\u0648\\u0627\\u0644\\u0623\\u0633\\u0639\\u0627\\u0631\\u061f \\u0634\\u0643\\u0631\\u0627\\u064b \\u0644\\u0643\\u0645.\",\"is_read\":true}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Deleted ContactMessage \"محمد عبد الله\"','2026-03-08 13:42:18','2026-03-08 13:42:18'),(3,1,'deleted','App\\Models\\ContactMessage',2,'{\"old\":{\"id\":2,\"name\":\"\\u0641\\u0627\\u0637\\u0645\\u0629 \\u0627\\u0644\\u0632\\u0647\\u0631\\u0627\\u0621\",\"email\":\"fatma.zahraa@yahoo.com\",\"phone\":\"01198765432\",\"subject\":\"\\u0633\\u0624\\u0627\\u0644 \\u0639\\u0646 \\u062d\\u062c\\u0632 \\u0645\\u0648\\u0639\\u062f\",\"message\":\"\\u0645\\u0631\\u062d\\u0628\\u0627\\u064b\\u060c \\u0623\\u0631\\u064a\\u062f \\u062d\\u062c\\u0632 \\u0645\\u0648\\u0639\\u062f \\u0645\\u0639 \\u0627\\u0644\\u062f\\u0643\\u062a\\u0648\\u0631\\u0629 \\u0623\\u0633\\u0645\\u0627\\u0621 \\u062d\\u0645\\u062f\\u064a \\u0644\\u0627\\u0633\\u062a\\u0634\\u0627\\u0631\\u0629 \\u0628\\u062e\\u0635\\u0648\\u0635 \\u0645\\u0634\\u0643\\u0644\\u0629 \\u0641\\u064a \\u0627\\u0644\\u0628\\u0634\\u0631\\u0629. \\u0645\\u0627 \\u0647\\u064a \\u0623\\u0642\\u0631\\u0628 \\u0627\\u0644\\u0645\\u0648\\u0627\\u0639\\u064a\\u062f \\u0627\\u0644\\u0645\\u062a\\u0627\\u062d\\u0629\\u061f \\u0648\\u0647\\u0644 \\u0627\\u0644\\u0643\\u0634\\u0641 \\u064a\\u062d\\u062a\\u0627\\u062c \\u062a\\u062d\\u0636\\u064a\\u0631 \\u0645\\u0633\\u0628\\u0642\\u061f\",\"is_read\":true}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Deleted ContactMessage \"فاطمة الزهراء\"','2026-03-08 13:42:20','2026-03-08 13:42:20'),(4,1,'deleted','App\\Models\\ContactMessage',2,'{\"old\":{\"id\":2,\"name\":\"\\u0641\\u0627\\u0637\\u0645\\u0629 \\u0627\\u0644\\u0632\\u0647\\u0631\\u0627\\u0621\",\"email\":\"fatma.zahraa@yahoo.com\",\"phone\":\"01198765432\",\"subject\":\"\\u0633\\u0624\\u0627\\u0644 \\u0639\\u0646 \\u062d\\u062c\\u0632 \\u0645\\u0648\\u0639\\u062f\",\"message\":\"\\u0645\\u0631\\u062d\\u0628\\u0627\\u064b\\u060c \\u0623\\u0631\\u064a\\u062f \\u062d\\u062c\\u0632 \\u0645\\u0648\\u0639\\u062f \\u0645\\u0639 \\u0627\\u0644\\u062f\\u0643\\u062a\\u0648\\u0631\\u0629 \\u0623\\u0633\\u0645\\u0627\\u0621 \\u062d\\u0645\\u062f\\u064a \\u0644\\u0627\\u0633\\u062a\\u0634\\u0627\\u0631\\u0629 \\u0628\\u062e\\u0635\\u0648\\u0635 \\u0645\\u0634\\u0643\\u0644\\u0629 \\u0641\\u064a \\u0627\\u0644\\u0628\\u0634\\u0631\\u0629. \\u0645\\u0627 \\u0647\\u064a \\u0623\\u0642\\u0631\\u0628 \\u0627\\u0644\\u0645\\u0648\\u0627\\u0639\\u064a\\u062f \\u0627\\u0644\\u0645\\u062a\\u0627\\u062d\\u0629\\u061f \\u0648\\u0647\\u0644 \\u0627\\u0644\\u0643\\u0634\\u0641 \\u064a\\u062d\\u062a\\u0627\\u062c \\u062a\\u062d\\u0636\\u064a\\u0631 \\u0645\\u0633\\u0628\\u0642\\u061f\",\"is_read\":true}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Deleted ContactMessage \"فاطمة الزهراء\"','2026-03-08 13:42:20','2026-03-08 13:42:20'),(5,1,'deleted','App\\Models\\ContactMessage',4,'{\"old\":{\"id\":4,\"name\":\"\\u0623\\u062d\\u0645\\u062f \\u0633\\u0645\\u064a\\u0631\",\"email\":\"ahmed.samir@gmail.com\",\"phone\":\"01234567890\",\"subject\":\"\\u0627\\u0633\\u062a\\u0641\\u0633\\u0627\\u0631 \\u0639\\u0627\\u0645\",\"message\":\"\\u0645\\u0631\\u062d\\u0628\\u0627\\u064b\\u060c \\u0623\\u0646\\u0627 \\u0645\\u0647\\u062a\\u0645 \\u0628\\u0645\\u0639\\u0631\\u0641\\u0629 \\u0627\\u0644\\u0645\\u0632\\u064a\\u062f \\u0639\\u0646 \\u062e\\u062f\\u0645\\u0627\\u062a \\u0627\\u0644\\u0639\\u0646\\u0627\\u064a\\u0629 \\u0628\\u0627\\u0644\\u0628\\u0634\\u0631\\u0629 \\u0627\\u0644\\u0645\\u062a\\u0627\\u062d\\u0629 \\u0641\\u064a \\u0627\\u0644\\u0639\\u064a\\u0627\\u062f\\u0629. \\u0647\\u0644 \\u062a\\u0642\\u062f\\u0645\\u0648\\u0646 \\u062e\\u062f\\u0645\\u0627\\u062a \\u0644\\u0644\\u0631\\u062c\\u0627\\u0644 \\u0623\\u064a\\u0636\\u0627\\u064b\\u061f \\u0648\\u0645\\u0627 \\u0647\\u064a \\u0645\\u0648\\u0627\\u0639\\u064a\\u062f \\u0627\\u0644\\u0639\\u0645\\u0644 \\u0641\\u064a \\u0627\\u0644\\u0639\\u064a\\u0627\\u062f\\u0629\\u061f \\u0623\\u0631\\u062c\\u0648 \\u0627\\u0644\\u0631\\u062f. \\u0634\\u0643\\u0631\\u0627\\u064b.\",\"is_read\":true}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Deleted ContactMessage \"أحمد سمير\"','2026-03-08 13:42:22','2026-03-08 13:42:22'),(6,1,'deleted','App\\Models\\ContactMessage',4,'{\"old\":{\"id\":4,\"name\":\"\\u0623\\u062d\\u0645\\u062f \\u0633\\u0645\\u064a\\u0631\",\"email\":\"ahmed.samir@gmail.com\",\"phone\":\"01234567890\",\"subject\":\"\\u0627\\u0633\\u062a\\u0641\\u0633\\u0627\\u0631 \\u0639\\u0627\\u0645\",\"message\":\"\\u0645\\u0631\\u062d\\u0628\\u0627\\u064b\\u060c \\u0623\\u0646\\u0627 \\u0645\\u0647\\u062a\\u0645 \\u0628\\u0645\\u0639\\u0631\\u0641\\u0629 \\u0627\\u0644\\u0645\\u0632\\u064a\\u062f \\u0639\\u0646 \\u062e\\u062f\\u0645\\u0627\\u062a \\u0627\\u0644\\u0639\\u0646\\u0627\\u064a\\u0629 \\u0628\\u0627\\u0644\\u0628\\u0634\\u0631\\u0629 \\u0627\\u0644\\u0645\\u062a\\u0627\\u062d\\u0629 \\u0641\\u064a \\u0627\\u0644\\u0639\\u064a\\u0627\\u062f\\u0629. \\u0647\\u0644 \\u062a\\u0642\\u062f\\u0645\\u0648\\u0646 \\u062e\\u062f\\u0645\\u0627\\u062a \\u0644\\u0644\\u0631\\u062c\\u0627\\u0644 \\u0623\\u064a\\u0636\\u0627\\u064b\\u061f \\u0648\\u0645\\u0627 \\u0647\\u064a \\u0645\\u0648\\u0627\\u0639\\u064a\\u062f \\u0627\\u0644\\u0639\\u0645\\u0644 \\u0641\\u064a \\u0627\\u0644\\u0639\\u064a\\u0627\\u062f\\u0629\\u061f \\u0623\\u0631\\u062c\\u0648 \\u0627\\u0644\\u0631\\u062f. \\u0634\\u0643\\u0631\\u0627\\u064b.\",\"is_read\":true}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Deleted ContactMessage \"أحمد سمير\"','2026-03-08 13:42:22','2026-03-08 13:42:22'),(7,1,'deleted','App\\Models\\ContactMessage',3,'{\"old\":{\"id\":3,\"name\":\"\\u0646\\u0627\\u062f\\u064a\\u0629 \\u062d\\u0633\\u064a\\u0646\",\"email\":\"nadia.hussein@hotmail.com\",\"phone\":\"01056789012\",\"subject\":\"\\u0627\\u0644\\u0627\\u0633\\u062a\\u0641\\u0633\\u0627\\u0631 \\u0639\\u0646 \\u0627\\u0644\\u0623\\u0633\\u0639\\u0627\\u0631 \\u0648\\u0627\\u0644\\u0639\\u0631\\u0648\\u0636\",\"message\":\"\\u0627\\u0644\\u0633\\u0644\\u0627\\u0645 \\u0639\\u0644\\u064a\\u0643\\u0645\\u060c \\u0647\\u0644 \\u064a\\u0648\\u062c\\u062f \\u0639\\u0631\\u0648\\u0636 \\u062d\\u0627\\u0644\\u064a\\u0629 \\u0639\\u0644\\u0649 \\u062c\\u0644\\u0633\\u0627\\u062a \\u0627\\u0644\\u0644\\u064a\\u0632\\u0631 \\u0644\\u0625\\u0632\\u0627\\u0644\\u0629 \\u0627\\u0644\\u0634\\u0639\\u0631\\u061f \\u0648\\u0645\\u0627 \\u0647\\u064a \\u0623\\u0633\\u0639\\u0627\\u0631 \\u0627\\u0644\\u0628\\u0627\\u0642\\u0627\\u062a \\u0627\\u0644\\u0645\\u062a\\u0627\\u062d\\u0629\\u061f \\u0623\\u0631\\u062c\\u0648 \\u0627\\u0644\\u062a\\u0648\\u0627\\u0635\\u0644 \\u0645\\u0639\\u064a \\u0641\\u064a \\u0623\\u0642\\u0631\\u0628 \\u0648\\u0642\\u062a. \\u0634\\u0643\\u0631\\u0627\\u064b.\",\"is_read\":true}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Deleted ContactMessage \"نادية حسين\"','2026-03-08 13:42:26','2026-03-08 13:42:26'),(8,1,'deleted','App\\Models\\ContactMessage',3,'{\"old\":{\"id\":3,\"name\":\"\\u0646\\u0627\\u062f\\u064a\\u0629 \\u062d\\u0633\\u064a\\u0646\",\"email\":\"nadia.hussein@hotmail.com\",\"phone\":\"01056789012\",\"subject\":\"\\u0627\\u0644\\u0627\\u0633\\u062a\\u0641\\u0633\\u0627\\u0631 \\u0639\\u0646 \\u0627\\u0644\\u0623\\u0633\\u0639\\u0627\\u0631 \\u0648\\u0627\\u0644\\u0639\\u0631\\u0648\\u0636\",\"message\":\"\\u0627\\u0644\\u0633\\u0644\\u0627\\u0645 \\u0639\\u0644\\u064a\\u0643\\u0645\\u060c \\u0647\\u0644 \\u064a\\u0648\\u062c\\u062f \\u0639\\u0631\\u0648\\u0636 \\u062d\\u0627\\u0644\\u064a\\u0629 \\u0639\\u0644\\u0649 \\u062c\\u0644\\u0633\\u0627\\u062a \\u0627\\u0644\\u0644\\u064a\\u0632\\u0631 \\u0644\\u0625\\u0632\\u0627\\u0644\\u0629 \\u0627\\u0644\\u0634\\u0639\\u0631\\u061f \\u0648\\u0645\\u0627 \\u0647\\u064a \\u0623\\u0633\\u0639\\u0627\\u0631 \\u0627\\u0644\\u0628\\u0627\\u0642\\u0627\\u062a \\u0627\\u0644\\u0645\\u062a\\u0627\\u062d\\u0629\\u061f \\u0623\\u0631\\u062c\\u0648 \\u0627\\u0644\\u062a\\u0648\\u0627\\u0635\\u0644 \\u0645\\u0639\\u064a \\u0641\\u064a \\u0623\\u0642\\u0631\\u0628 \\u0648\\u0642\\u062a. \\u0634\\u0643\\u0631\\u0627\\u064b.\",\"is_read\":true}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Deleted ContactMessage \"نادية حسين\"','2026-03-08 13:42:26','2026-03-08 13:42:26'),(9,1,'deleted','App\\Models\\DiscountCode',1,'{\"old\":{\"id\":1,\"code\":\"20\",\"discount_type\":\"percentage\",\"discount_value\":\"20.00\",\"max_uses\":100,\"used_count\":0,\"start_date\":null,\"end_date\":\"2026-03-27T22:00:00.000000Z\",\"is_active\":true,\"applicable_services\":null,\"notes\":null,\"created_by\":1}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Deleted DiscountCode #1','2026-03-08 13:42:42','2026-03-08 13:42:42'),(10,1,'deleted','App\\Models\\DiscountCode',1,'{\"old\":{\"id\":1,\"code\":\"20\",\"discount_type\":\"percentage\",\"discount_value\":\"20.00\",\"max_uses\":100,\"used_count\":0,\"start_date\":null,\"end_date\":\"2026-03-27T22:00:00.000000Z\",\"is_active\":true,\"applicable_services\":null,\"notes\":null,\"created_by\":1}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Deleted DiscountCode #1','2026-03-08 13:42:42','2026-03-08 13:42:42'),(11,1,'deleted','App\\Models\\Shift',1,'{\"old\":{\"id\":1,\"name_ar\":\"\\u0627\\u0644\\u0648\\u0631\\u062f\\u064a\\u0629 \\u0627\\u0644\\u0635\\u0628\\u0627\\u062d\\u064a\\u0629\",\"name_en\":\"Morning Shift\",\"start_time\":\"09:00:00\",\"end_time\":\"15:00:00\",\"is_active\":true}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Deleted Shift \"Morning Shift\"','2026-03-08 13:42:50','2026-03-08 13:42:50'),(12,1,'deleted','App\\Models\\Shift',1,'{\"old\":{\"id\":1,\"name_ar\":\"\\u0627\\u0644\\u0648\\u0631\\u062f\\u064a\\u0629 \\u0627\\u0644\\u0635\\u0628\\u0627\\u062d\\u064a\\u0629\",\"name_en\":\"Morning Shift\",\"start_time\":\"09:00:00\",\"end_time\":\"15:00:00\",\"is_active\":true}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Deleted Shift \"Morning Shift\"','2026-03-08 13:42:50','2026-03-08 13:42:50'),(13,1,'deleted','App\\Models\\Shift',3,'{\"old\":{\"id\":3,\"name_ar\":\"\\u0648\\u0631\\u062f\\u064a\\u0629 \\u0643\\u0627\\u0645\\u0644\\u0629\",\"name_en\":\"Full Day Shift\",\"start_time\":\"09:00:00\",\"end_time\":\"21:00:00\",\"is_active\":true}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Deleted Shift \"Full Day Shift\"','2026-03-08 13:42:52','2026-03-08 13:42:52'),(14,1,'deleted','App\\Models\\Shift',3,'{\"old\":{\"id\":3,\"name_ar\":\"\\u0648\\u0631\\u062f\\u064a\\u0629 \\u0643\\u0627\\u0645\\u0644\\u0629\",\"name_en\":\"Full Day Shift\",\"start_time\":\"09:00:00\",\"end_time\":\"21:00:00\",\"is_active\":true}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Deleted Shift \"Full Day Shift\"','2026-03-08 13:42:52','2026-03-08 13:42:52'),(15,1,'deleted','App\\Models\\Shift',2,'{\"old\":{\"id\":2,\"name_ar\":\"\\u0627\\u0644\\u0648\\u0631\\u062f\\u064a\\u0629 \\u0627\\u0644\\u0645\\u0633\\u0627\\u0626\\u064a\\u0629\",\"name_en\":\"Evening Shift\",\"start_time\":\"15:00:00\",\"end_time\":\"21:00:00\",\"is_active\":true}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Deleted Shift \"Evening Shift\"','2026-03-08 13:42:54','2026-03-08 13:42:54'),(16,1,'deleted','App\\Models\\Shift',2,'{\"old\":{\"id\":2,\"name_ar\":\"\\u0627\\u0644\\u0648\\u0631\\u062f\\u064a\\u0629 \\u0627\\u0644\\u0645\\u0633\\u0627\\u0626\\u064a\\u0629\",\"name_en\":\"Evening Shift\",\"start_time\":\"15:00:00\",\"end_time\":\"21:00:00\",\"is_active\":true}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Deleted Shift \"Evening Shift\"','2026-03-08 13:42:54','2026-03-08 13:42:54'),(17,1,'deleted','App\\Models\\User',6,'{\"old\":{\"id\":6,\"name\":\"Dr. Samar Shousha\",\"email\":\"dr.samar@aura.com\",\"role_id\":5,\"is_active\":true,\"last_seen_at\":null,\"email_verified_at\":null}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Deleted User \"Dr. Samar Shousha\"','2026-03-08 13:55:29','2026-03-08 13:55:29'),(18,1,'deleted','App\\Models\\User',6,'{\"old\":{\"id\":6,\"name\":\"Dr. Samar Shousha\",\"email\":\"dr.samar@aura.com\",\"role_id\":5,\"is_active\":true,\"last_seen_at\":null,\"email_verified_at\":null}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Deleted User \"Dr. Samar Shousha\"','2026-03-08 13:55:29','2026-03-08 13:55:29'),(19,NULL,'created','App\\Models\\User',19,'{\"new\":{\"name\":\"Dr. Alaa Elawady\",\"email\":\"dr.alaa@auraderma.com\",\"role_id\":5,\"is_active\":true,\"id\":19}}','127.0.0.1',NULL,'Symfony','System Created User \"Dr. Alaa Elawady\"','2026-03-08 15:02:41','2026-03-08 15:02:41'),(20,NULL,'created','App\\Models\\Doctor',1,'{\"new\":{\"name_en\":\"Dr. Alaa Elawady\",\"name_ar\":\"\\u062f. \\u0622\\u0644\\u0627\\u0621 \\u0627\\u0644\\u0639\\u0648\\u0636\\u064a\",\"specialization_en\":\"Dermatology & Venereology Specialist\",\"specialization_ar\":\"\\u0623\\u062e\\u0635\\u0627\\u0626\\u064a\\u0629 \\u062c\\u0644\\u062f\\u064a\\u0629 \\u0648\\u062a\\u0646\\u0627\\u0633\\u0644\\u064a\\u0629\",\"doctor_type\":\"specialist\",\"status\":\"active\",\"user_id\":19,\"email\":\"dr.alaa@auraderma.com\",\"display_order\":1,\"dermatology_fee\":400,\"cosmetic_fee\":200,\"consultation_fee\":400,\"default_commission_percentage\":0,\"dermatology_commission\":0,\"cosmetic_commission\":0,\"followup_commission\":0,\"id\":1}}','127.0.0.1',NULL,'Symfony','System Created Doctor \"Dr. Alaa Elawady\"','2026-03-08 15:02:41','2026-03-08 15:02:41'),(21,1,'updated','App\\Models\\Doctor',1,'{\"old\":{\"dermatology_commission\":\"0.00\",\"cosmetic_commission\":\"0.00\",\"followup_commission\":\"0.00\"},\"new\":{\"dermatology_commission\":\"40\",\"cosmetic_commission\":\"40\",\"followup_commission\":\"40\"}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Updated Doctor \"Dr. Alaa Elawady\"','2026-03-08 15:04:08','2026-03-08 15:04:08'),(22,1,'created','App\\Models\\DoctorSchedule',1,'{\"new\":{\"day_of_week\":\"0\",\"start_time\":\"09:00\",\"end_time\":\"17:00\",\"is_active\":\"0\",\"doctor_id\":1,\"id\":1}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #1','2026-03-08 15:04:08','2026-03-08 15:04:08'),(23,1,'created','App\\Models\\DoctorSchedule',2,'{\"new\":{\"day_of_week\":\"1\",\"start_time\":\"09:00\",\"end_time\":\"17:00\",\"is_active\":\"0\",\"doctor_id\":1,\"id\":2}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #2','2026-03-08 15:04:08','2026-03-08 15:04:08'),(24,1,'created','App\\Models\\DoctorSchedule',3,'{\"new\":{\"day_of_week\":\"2\",\"start_time\":\"09:00\",\"end_time\":\"17:00\",\"is_active\":\"0\",\"doctor_id\":1,\"id\":3}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #3','2026-03-08 15:04:08','2026-03-08 15:04:08'),(25,1,'created','App\\Models\\DoctorSchedule',4,'{\"new\":{\"day_of_week\":\"3\",\"start_time\":\"09:00\",\"end_time\":\"17:00\",\"is_active\":\"0\",\"doctor_id\":1,\"id\":4}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #4','2026-03-08 15:04:08','2026-03-08 15:04:08'),(26,1,'created','App\\Models\\DoctorSchedule',5,'{\"new\":{\"day_of_week\":\"4\",\"start_time\":\"09:00\",\"end_time\":\"17:00\",\"is_active\":\"0\",\"doctor_id\":1,\"id\":5}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #5','2026-03-08 15:04:08','2026-03-08 15:04:08'),(27,1,'created','App\\Models\\DoctorSchedule',6,'{\"new\":{\"day_of_week\":\"5\",\"start_time\":\"09:00\",\"end_time\":\"17:00\",\"is_active\":\"0\",\"doctor_id\":1,\"id\":6}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #6','2026-03-08 15:04:08','2026-03-08 15:04:08'),(28,1,'created','App\\Models\\DoctorSchedule',7,'{\"new\":{\"day_of_week\":\"6\",\"start_time\":\"09:00\",\"end_time\":\"17:00\",\"is_active\":\"0\",\"doctor_id\":1,\"id\":7}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #7','2026-03-08 15:04:08','2026-03-08 15:04:08'),(29,1,'updated','App\\Models\\Doctor',1,NULL,'127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Updated Doctor \"Dr. Alaa Elawady\"','2026-03-08 15:04:08','2026-03-08 15:04:08'),(30,1,'updated','App\\Models\\Doctor',1,'{\"old\":{\"photo\":null},\"new\":{\"photo\":\"uploads\\/doctors\\/5a8Bnbnk6QUnPQ0tuDtaatQWYRaxDVXQ8Q3sKLqo.png\"}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Updated Doctor \"Dr. Alaa Elawady\"','2026-03-08 15:06:38','2026-03-08 15:06:38'),(31,1,'created','App\\Models\\DoctorSchedule',8,'{\"new\":{\"day_of_week\":\"0\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"0\",\"doctor_id\":1,\"id\":8}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #8','2026-03-08 15:06:38','2026-03-08 15:06:38'),(32,1,'created','App\\Models\\DoctorSchedule',9,'{\"new\":{\"day_of_week\":\"1\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"0\",\"doctor_id\":1,\"id\":9}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #9','2026-03-08 15:06:38','2026-03-08 15:06:38'),(33,1,'created','App\\Models\\DoctorSchedule',10,'{\"new\":{\"day_of_week\":\"2\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"0\",\"doctor_id\":1,\"id\":10}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #10','2026-03-08 15:06:38','2026-03-08 15:06:38'),(34,1,'created','App\\Models\\DoctorSchedule',11,'{\"new\":{\"day_of_week\":\"3\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"0\",\"doctor_id\":1,\"id\":11}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #11','2026-03-08 15:06:38','2026-03-08 15:06:38'),(35,1,'created','App\\Models\\DoctorSchedule',12,'{\"new\":{\"day_of_week\":\"4\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"0\",\"doctor_id\":1,\"id\":12}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #12','2026-03-08 15:06:38','2026-03-08 15:06:38'),(36,1,'created','App\\Models\\DoctorSchedule',13,'{\"new\":{\"day_of_week\":\"5\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"0\",\"doctor_id\":1,\"id\":13}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #13','2026-03-08 15:06:38','2026-03-08 15:06:38'),(37,1,'created','App\\Models\\DoctorSchedule',14,'{\"new\":{\"day_of_week\":\"6\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"0\",\"doctor_id\":1,\"id\":14}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #14','2026-03-08 15:06:38','2026-03-08 15:06:38'),(38,1,'updated','App\\Models\\Doctor',1,NULL,'127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Updated Doctor \"Dr. Alaa Elawady\"','2026-03-08 15:06:38','2026-03-08 15:06:38'),(39,1,'updated','App\\Models\\Doctor',1,'{\"old\":{\"photo\":\"uploads\\/doctors\\/5a8Bnbnk6QUnPQ0tuDtaatQWYRaxDVXQ8Q3sKLqo.png\"},\"new\":{\"photo\":null}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Updated Doctor \"Dr. Alaa Elawady\"','2026-03-08 15:06:55','2026-03-08 15:06:55'),(40,1,'created','App\\Models\\DoctorSchedule',15,'{\"new\":{\"day_of_week\":\"0\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"1\",\"doctor_id\":1,\"id\":15}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #15','2026-03-08 15:06:55','2026-03-08 15:06:55'),(41,1,'created','App\\Models\\DoctorSchedule',16,'{\"new\":{\"day_of_week\":\"1\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"1\",\"doctor_id\":1,\"id\":16}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #16','2026-03-08 15:06:55','2026-03-08 15:06:55'),(42,1,'created','App\\Models\\DoctorSchedule',17,'{\"new\":{\"day_of_week\":\"2\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"1\",\"doctor_id\":1,\"id\":17}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #17','2026-03-08 15:06:55','2026-03-08 15:06:55'),(43,1,'created','App\\Models\\DoctorSchedule',18,'{\"new\":{\"day_of_week\":\"3\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"0\",\"doctor_id\":1,\"id\":18}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #18','2026-03-08 15:06:55','2026-03-08 15:06:55'),(44,1,'created','App\\Models\\DoctorSchedule',19,'{\"new\":{\"day_of_week\":\"4\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"0\",\"doctor_id\":1,\"id\":19}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #19','2026-03-08 15:06:55','2026-03-08 15:06:55'),(45,1,'created','App\\Models\\DoctorSchedule',20,'{\"new\":{\"day_of_week\":\"5\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"0\",\"doctor_id\":1,\"id\":20}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #20','2026-03-08 15:06:55','2026-03-08 15:06:55'),(46,1,'created','App\\Models\\DoctorSchedule',21,'{\"new\":{\"day_of_week\":\"6\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"0\",\"doctor_id\":1,\"id\":21}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #21','2026-03-08 15:06:55','2026-03-08 15:06:55'),(47,1,'updated','App\\Models\\Doctor',1,NULL,'127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Updated Doctor \"Dr. Alaa Elawady\"','2026-03-08 15:06:55','2026-03-08 15:06:55'),(48,1,'created','App\\Models\\DoctorSchedule',22,'{\"new\":{\"day_of_week\":\"0\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"1\",\"doctor_id\":1,\"id\":22}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #22','2026-03-08 15:07:12','2026-03-08 15:07:12'),(49,1,'created','App\\Models\\DoctorSchedule',23,'{\"new\":{\"day_of_week\":\"1\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"1\",\"doctor_id\":1,\"id\":23}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #23','2026-03-08 15:07:12','2026-03-08 15:07:12'),(50,1,'created','App\\Models\\DoctorSchedule',24,'{\"new\":{\"day_of_week\":\"2\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"1\",\"doctor_id\":1,\"id\":24}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #24','2026-03-08 15:07:12','2026-03-08 15:07:12'),(51,1,'created','App\\Models\\DoctorSchedule',25,'{\"new\":{\"day_of_week\":\"3\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"1\",\"doctor_id\":1,\"id\":25}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #25','2026-03-08 15:07:12','2026-03-08 15:07:12'),(52,1,'created','App\\Models\\DoctorSchedule',26,'{\"new\":{\"day_of_week\":\"4\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"1\",\"doctor_id\":1,\"id\":26}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #26','2026-03-08 15:07:12','2026-03-08 15:07:12'),(53,1,'created','App\\Models\\DoctorSchedule',27,'{\"new\":{\"day_of_week\":\"5\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"1\",\"doctor_id\":1,\"id\":27}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #27','2026-03-08 15:07:12','2026-03-08 15:07:12'),(54,1,'created','App\\Models\\DoctorSchedule',28,'{\"new\":{\"day_of_week\":\"6\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"0\",\"doctor_id\":1,\"id\":28}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #28','2026-03-08 15:07:12','2026-03-08 15:07:12'),(55,1,'updated','App\\Models\\Doctor',1,NULL,'127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Updated Doctor \"Dr. Alaa Elawady\"','2026-03-08 15:07:12','2026-03-08 15:07:12'),(56,NULL,'created','App\\Models\\User',20,'{\"new\":{\"name\":\"Dr. Eman Magdy\",\"email\":\"dr.eman@auraderma.com\",\"role_id\":5,\"is_active\":true,\"id\":20}}','127.0.0.1',NULL,'Symfony','System Created User \"Dr. Eman Magdy\"','2026-03-08 15:09:40','2026-03-08 15:09:40'),(57,NULL,'created','App\\Models\\Doctor',2,'{\"new\":{\"name_en\":\"Dr. Eman Magdy\",\"name_ar\":\"\\u062f. \\u0625\\u064a\\u0645\\u0627\\u0646 \\u0645\\u062c\\u062f\\u064a\",\"specialization_en\":\"Dermatology & Venereology Specialist\",\"specialization_ar\":\"\\u0623\\u062e\\u0635\\u0627\\u0626\\u064a\\u0629 \\u062c\\u0644\\u062f\\u064a\\u0629 \\u0648\\u062a\\u0646\\u0627\\u0633\\u0644\\u064a\\u0629\",\"doctor_type\":\"specialist\",\"status\":\"active\",\"user_id\":20,\"email\":\"dr.eman@auraderma.com\",\"display_order\":2,\"dermatology_fee\":400,\"cosmetic_fee\":200,\"consultation_fee\":400,\"default_commission_percentage\":0,\"dermatology_commission\":0,\"cosmetic_commission\":0,\"followup_commission\":0,\"id\":2}}','127.0.0.1',NULL,'Symfony','System Created Doctor \"Dr. Eman Magdy\"','2026-03-08 15:09:40','2026-03-08 15:09:40'),(58,1,'updated','App\\Models\\Doctor',2,'{\"old\":{\"dermatology_commission\":\"0.00\",\"cosmetic_commission\":\"0.00\",\"followup_commission\":\"0.00\"},\"new\":{\"dermatology_commission\":\"40\",\"cosmetic_commission\":\"40\",\"followup_commission\":\"40\"}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Updated Doctor \"Dr. Eman Magdy\"','2026-03-08 15:11:23','2026-03-08 15:11:23'),(59,1,'created','App\\Models\\DoctorSchedule',29,'{\"new\":{\"day_of_week\":\"0\",\"start_time\":\"09:00\",\"end_time\":\"17:00\",\"is_active\":\"0\",\"doctor_id\":2,\"id\":29}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #29','2026-03-08 15:11:23','2026-03-08 15:11:23'),(60,1,'created','App\\Models\\DoctorSchedule',30,'{\"new\":{\"day_of_week\":\"1\",\"start_time\":\"09:00\",\"end_time\":\"17:00\",\"is_active\":\"0\",\"doctor_id\":2,\"id\":30}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #30','2026-03-08 15:11:23','2026-03-08 15:11:23'),(61,1,'created','App\\Models\\DoctorSchedule',31,'{\"new\":{\"day_of_week\":\"2\",\"start_time\":\"09:00\",\"end_time\":\"17:00\",\"is_active\":\"0\",\"doctor_id\":2,\"id\":31}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #31','2026-03-08 15:11:23','2026-03-08 15:11:23'),(62,1,'created','App\\Models\\DoctorSchedule',32,'{\"new\":{\"day_of_week\":\"3\",\"start_time\":\"09:00\",\"end_time\":\"17:00\",\"is_active\":\"0\",\"doctor_id\":2,\"id\":32}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #32','2026-03-08 15:11:23','2026-03-08 15:11:23'),(63,1,'created','App\\Models\\DoctorSchedule',33,'{\"new\":{\"day_of_week\":\"4\",\"start_time\":\"09:00\",\"end_time\":\"17:00\",\"is_active\":\"0\",\"doctor_id\":2,\"id\":33}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #33','2026-03-08 15:11:23','2026-03-08 15:11:23'),(64,1,'created','App\\Models\\DoctorSchedule',34,'{\"new\":{\"day_of_week\":\"5\",\"start_time\":\"09:00\",\"end_time\":\"17:00\",\"is_active\":\"0\",\"doctor_id\":2,\"id\":34}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #34','2026-03-08 15:11:23','2026-03-08 15:11:23'),(65,1,'created','App\\Models\\DoctorSchedule',35,'{\"new\":{\"day_of_week\":\"6\",\"start_time\":\"09:00\",\"end_time\":\"17:00\",\"is_active\":\"0\",\"doctor_id\":2,\"id\":35}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #35','2026-03-08 15:11:23','2026-03-08 15:11:23'),(66,1,'updated','App\\Models\\Doctor',2,NULL,'127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Updated Doctor \"Dr. Eman Magdy\"','2026-03-08 15:11:23','2026-03-08 15:11:23'),(67,1,'updated','App\\Models\\Doctor',1,'{\"old\":{\"photo\":null},\"new\":{\"photo\":\"uploads\\/doctors\\/UyDBde5V50kgBH0q5VzpebQx8Hxkq9E5GNpeMxte.png\"}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Updated Doctor \"Dr. Alaa Elawady\"','2026-03-08 15:14:27','2026-03-08 15:14:27'),(68,1,'created','App\\Models\\DoctorSchedule',36,'{\"new\":{\"day_of_week\":\"0\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"1\",\"doctor_id\":1,\"id\":36}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #36','2026-03-08 15:14:27','2026-03-08 15:14:27'),(69,1,'created','App\\Models\\DoctorSchedule',37,'{\"new\":{\"day_of_week\":\"1\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"1\",\"doctor_id\":1,\"id\":37}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #37','2026-03-08 15:14:27','2026-03-08 15:14:27'),(70,1,'created','App\\Models\\DoctorSchedule',38,'{\"new\":{\"day_of_week\":\"2\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"1\",\"doctor_id\":1,\"id\":38}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #38','2026-03-08 15:14:27','2026-03-08 15:14:27'),(71,1,'created','App\\Models\\DoctorSchedule',39,'{\"new\":{\"day_of_week\":\"3\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"1\",\"doctor_id\":1,\"id\":39}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #39','2026-03-08 15:14:27','2026-03-08 15:14:27'),(72,1,'created','App\\Models\\DoctorSchedule',40,'{\"new\":{\"day_of_week\":\"4\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"1\",\"doctor_id\":1,\"id\":40}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #40','2026-03-08 15:14:27','2026-03-08 15:14:27'),(73,1,'created','App\\Models\\DoctorSchedule',41,'{\"new\":{\"day_of_week\":\"5\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"1\",\"doctor_id\":1,\"id\":41}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #41','2026-03-08 15:14:27','2026-03-08 15:14:27'),(74,1,'created','App\\Models\\DoctorSchedule',42,'{\"new\":{\"day_of_week\":\"6\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"0\",\"doctor_id\":1,\"id\":42}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #42','2026-03-08 15:14:27','2026-03-08 15:14:27'),(75,1,'updated','App\\Models\\Doctor',1,NULL,'127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Updated Doctor \"Dr. Alaa Elawady\"','2026-03-08 15:14:27','2026-03-08 15:14:27'),(76,1,'updated','App\\Models\\Doctor',2,'{\"old\":{\"photo\":null},\"new\":{\"photo\":\"uploads\\/doctors\\/gMxyk02SCjId7Q1tZrNiUxsLCyjOsf32KGYCsiuQ.png\"}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Updated Doctor \"Dr. Eman Magdy\"','2026-03-08 15:14:57','2026-03-08 15:14:57'),(77,1,'created','App\\Models\\DoctorSchedule',43,'{\"new\":{\"day_of_week\":\"0\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"0\",\"doctor_id\":2,\"id\":43}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #43','2026-03-08 15:14:57','2026-03-08 15:14:57'),(78,1,'created','App\\Models\\DoctorSchedule',44,'{\"new\":{\"day_of_week\":\"1\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"0\",\"doctor_id\":2,\"id\":44}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #44','2026-03-08 15:14:57','2026-03-08 15:14:57'),(79,1,'created','App\\Models\\DoctorSchedule',45,'{\"new\":{\"day_of_week\":\"2\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"0\",\"doctor_id\":2,\"id\":45}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #45','2026-03-08 15:14:57','2026-03-08 15:14:57'),(80,1,'created','App\\Models\\DoctorSchedule',46,'{\"new\":{\"day_of_week\":\"3\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"0\",\"doctor_id\":2,\"id\":46}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #46','2026-03-08 15:14:57','2026-03-08 15:14:57'),(81,1,'created','App\\Models\\DoctorSchedule',47,'{\"new\":{\"day_of_week\":\"4\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"0\",\"doctor_id\":2,\"id\":47}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #47','2026-03-08 15:14:57','2026-03-08 15:14:57'),(82,1,'created','App\\Models\\DoctorSchedule',48,'{\"new\":{\"day_of_week\":\"5\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"0\",\"doctor_id\":2,\"id\":48}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #48','2026-03-08 15:14:57','2026-03-08 15:14:57'),(83,1,'created','App\\Models\\DoctorSchedule',49,'{\"new\":{\"day_of_week\":\"6\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"0\",\"doctor_id\":2,\"id\":49}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #49','2026-03-08 15:14:57','2026-03-08 15:14:57'),(84,1,'updated','App\\Models\\Doctor',2,NULL,'127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Updated Doctor \"Dr. Eman Magdy\"','2026-03-08 15:14:57','2026-03-08 15:14:57'),(85,NULL,'created','App\\Models\\User',21,'{\"new\":{\"name\":\"Dr. Amira Ahmed\",\"email\":\"dr.amira@auraderma.com\",\"role_id\":5,\"is_active\":true,\"id\":21}}','127.0.0.1',NULL,'Symfony','System Created User \"Dr. Amira Ahmed\"','2026-03-08 15:21:07','2026-03-08 15:21:07'),(86,NULL,'created','App\\Models\\Doctor',3,'{\"new\":{\"name_en\":\"Dr. Amira Ahmed\",\"name_ar\":\"\\u062f. \\u0623\\u0645\\u064a\\u0631\\u0629 \\u0623\\u062d\\u0645\\u062f\",\"specialization_en\":\"Dermatology & Venereology Specialist\",\"specialization_ar\":\"\\u0623\\u062e\\u0635\\u0627\\u0626\\u064a\\u0629 \\u062c\\u0644\\u062f\\u064a\\u0629 \\u0648\\u062a\\u0646\\u0627\\u0633\\u0644\\u064a\\u0629\",\"doctor_type\":\"specialist\",\"status\":\"active\",\"user_id\":21,\"email\":\"dr.amira@auraderma.com\",\"display_order\":3,\"dermatology_fee\":400,\"cosmetic_fee\":200,\"consultation_fee\":400,\"default_commission_percentage\":0,\"dermatology_commission\":0,\"cosmetic_commission\":0,\"followup_commission\":0,\"id\":3}}','127.0.0.1',NULL,'Symfony','System Created Doctor \"Dr. Amira Ahmed\"','2026-03-08 15:21:07','2026-03-08 15:21:07'),(87,1,'updated','App\\Models\\Doctor',3,'{\"old\":{\"photo\":null},\"new\":{\"photo\":\"uploads\\/doctors\\/yWgpM8wTOYM0Ik5r8I9H7iM2tyOTFhBewhLwSQCK.png\"}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Updated Doctor \"Dr. Amira Ahmed\"','2026-03-08 15:22:35','2026-03-08 15:22:35'),(88,1,'created','App\\Models\\DoctorSchedule',50,'{\"new\":{\"day_of_week\":\"0\",\"start_time\":\"09:00\",\"end_time\":\"17:00\",\"is_active\":\"0\",\"doctor_id\":3,\"id\":50}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #50','2026-03-08 15:22:35','2026-03-08 15:22:35'),(89,1,'created','App\\Models\\DoctorSchedule',51,'{\"new\":{\"day_of_week\":\"1\",\"start_time\":\"09:00\",\"end_time\":\"17:00\",\"is_active\":\"0\",\"doctor_id\":3,\"id\":51}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #51','2026-03-08 15:22:35','2026-03-08 15:22:35'),(90,1,'created','App\\Models\\DoctorSchedule',52,'{\"new\":{\"day_of_week\":\"2\",\"start_time\":\"09:00\",\"end_time\":\"17:00\",\"is_active\":\"0\",\"doctor_id\":3,\"id\":52}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #52','2026-03-08 15:22:35','2026-03-08 15:22:35'),(91,1,'created','App\\Models\\DoctorSchedule',53,'{\"new\":{\"day_of_week\":\"3\",\"start_time\":\"09:00\",\"end_time\":\"17:00\",\"is_active\":\"0\",\"doctor_id\":3,\"id\":53}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #53','2026-03-08 15:22:35','2026-03-08 15:22:35'),(92,1,'created','App\\Models\\DoctorSchedule',54,'{\"new\":{\"day_of_week\":\"4\",\"start_time\":\"09:00\",\"end_time\":\"17:00\",\"is_active\":\"0\",\"doctor_id\":3,\"id\":54}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #54','2026-03-08 15:22:35','2026-03-08 15:22:35'),(93,1,'created','App\\Models\\DoctorSchedule',55,'{\"new\":{\"day_of_week\":\"5\",\"start_time\":\"09:00\",\"end_time\":\"17:00\",\"is_active\":\"0\",\"doctor_id\":3,\"id\":55}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #55','2026-03-08 15:22:35','2026-03-08 15:22:35'),(94,1,'created','App\\Models\\DoctorSchedule',56,'{\"new\":{\"day_of_week\":\"6\",\"start_time\":\"09:00\",\"end_time\":\"17:00\",\"is_active\":\"0\",\"doctor_id\":3,\"id\":56}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #56','2026-03-08 15:22:35','2026-03-08 15:22:35'),(95,1,'updated','App\\Models\\Doctor',3,NULL,'127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Updated Doctor \"Dr. Amira Ahmed\"','2026-03-08 15:22:35','2026-03-08 15:22:35'),(96,NULL,'created','App\\Models\\User',22,'{\"new\":{\"name\":\"Dr. Asmaa Hamdy\",\"email\":\"dr.asmaa@auraderma.com\",\"role_id\":5,\"is_active\":true,\"id\":22}}','127.0.0.1',NULL,'Symfony','System Created User \"Dr. Asmaa Hamdy\"','2026-03-08 15:25:10','2026-03-08 15:25:10'),(97,NULL,'created','App\\Models\\Doctor',4,'{\"new\":{\"name_en\":\"Dr. Asmaa Hamdy\",\"name_ar\":\"\\u062f. \\u0623\\u0633\\u0645\\u0627\\u0621 \\u062d\\u0645\\u062f\\u064a\",\"specialization_en\":\"Dermatology & Venereology Consultant\",\"specialization_ar\":\"\\u0627\\u0633\\u062a\\u0634\\u0627\\u0631\\u064a\\u0629 \\u062c\\u0644\\u062f\\u064a\\u0629 \\u0648\\u062a\\u0646\\u0627\\u0633\\u0644\\u064a\\u0629\",\"doctor_type\":\"consultant\",\"status\":\"active\",\"user_id\":22,\"email\":\"dr.asmaa@auraderma.com\",\"display_order\":4,\"dermatology_fee\":400,\"cosmetic_fee\":200,\"consultation_fee\":400,\"default_commission_percentage\":0,\"dermatology_commission\":0,\"cosmetic_commission\":0,\"followup_commission\":0,\"id\":4}}','127.0.0.1',NULL,'Symfony','System Created Doctor \"Dr. Asmaa Hamdy\"','2026-03-08 15:25:10','2026-03-08 15:25:10'),(98,1,'updated','App\\Models\\Doctor',3,'{\"old\":{\"photo\":\"uploads\\/doctors\\/yWgpM8wTOYM0Ik5r8I9H7iM2tyOTFhBewhLwSQCK.png\",\"dermatology_commission\":\"0.00\",\"cosmetic_commission\":\"0.00\",\"followup_commission\":\"0.00\"},\"new\":{\"photo\":null,\"dermatology_commission\":\"40\",\"cosmetic_commission\":\"40\",\"followup_commission\":\"40\"}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Updated Doctor \"Dr. Amira Ahmed\"','2026-03-08 15:25:23','2026-03-08 15:25:23'),(99,1,'created','App\\Models\\DoctorSchedule',57,'{\"new\":{\"day_of_week\":\"0\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"0\",\"doctor_id\":3,\"id\":57}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #57','2026-03-08 15:25:23','2026-03-08 15:25:23'),(100,1,'created','App\\Models\\DoctorSchedule',58,'{\"new\":{\"day_of_week\":\"1\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"0\",\"doctor_id\":3,\"id\":58}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #58','2026-03-08 15:25:23','2026-03-08 15:25:23'),(101,1,'created','App\\Models\\DoctorSchedule',59,'{\"new\":{\"day_of_week\":\"2\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"0\",\"doctor_id\":3,\"id\":59}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #59','2026-03-08 15:25:23','2026-03-08 15:25:23'),(102,1,'created','App\\Models\\DoctorSchedule',60,'{\"new\":{\"day_of_week\":\"3\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"0\",\"doctor_id\":3,\"id\":60}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #60','2026-03-08 15:25:23','2026-03-08 15:25:23'),(103,1,'created','App\\Models\\DoctorSchedule',61,'{\"new\":{\"day_of_week\":\"4\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"0\",\"doctor_id\":3,\"id\":61}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #61','2026-03-08 15:25:23','2026-03-08 15:25:23'),(104,1,'created','App\\Models\\DoctorSchedule',62,'{\"new\":{\"day_of_week\":\"5\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"0\",\"doctor_id\":3,\"id\":62}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #62','2026-03-08 15:25:23','2026-03-08 15:25:23'),(105,1,'created','App\\Models\\DoctorSchedule',63,'{\"new\":{\"day_of_week\":\"6\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"0\",\"doctor_id\":3,\"id\":63}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #63','2026-03-08 15:25:23','2026-03-08 15:25:23'),(106,1,'updated','App\\Models\\Doctor',3,NULL,'127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Updated Doctor \"Dr. Amira Ahmed\"','2026-03-08 15:25:23','2026-03-08 15:25:23'),(107,1,'updated','App\\Models\\Doctor',3,'{\"old\":{\"photo\":null},\"new\":{\"photo\":\"uploads\\/doctors\\/HL9NngDCXCpTOLwq2eiLCvJngJ5bhTUBGVWobTtQ.png\"}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Updated Doctor \"Dr. Amira Ahmed\"','2026-03-08 15:25:56','2026-03-08 15:25:56'),(108,1,'created','App\\Models\\DoctorSchedule',64,'{\"new\":{\"day_of_week\":\"0\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"0\",\"doctor_id\":3,\"id\":64}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #64','2026-03-08 15:25:56','2026-03-08 15:25:56'),(109,1,'created','App\\Models\\DoctorSchedule',65,'{\"new\":{\"day_of_week\":\"1\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"0\",\"doctor_id\":3,\"id\":65}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #65','2026-03-08 15:25:56','2026-03-08 15:25:56'),(110,1,'created','App\\Models\\DoctorSchedule',66,'{\"new\":{\"day_of_week\":\"2\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"0\",\"doctor_id\":3,\"id\":66}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #66','2026-03-08 15:25:56','2026-03-08 15:25:56'),(111,1,'created','App\\Models\\DoctorSchedule',67,'{\"new\":{\"day_of_week\":\"3\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"0\",\"doctor_id\":3,\"id\":67}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #67','2026-03-08 15:25:56','2026-03-08 15:25:56'),(112,1,'created','App\\Models\\DoctorSchedule',68,'{\"new\":{\"day_of_week\":\"4\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"0\",\"doctor_id\":3,\"id\":68}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #68','2026-03-08 15:25:56','2026-03-08 15:25:56'),(113,1,'created','App\\Models\\DoctorSchedule',69,'{\"new\":{\"day_of_week\":\"5\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"0\",\"doctor_id\":3,\"id\":69}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #69','2026-03-08 15:25:56','2026-03-08 15:25:56'),(114,1,'created','App\\Models\\DoctorSchedule',70,'{\"new\":{\"day_of_week\":\"6\",\"start_time\":\"09:00:00\",\"end_time\":\"17:00:00\",\"is_active\":\"0\",\"doctor_id\":3,\"id\":70}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #70','2026-03-08 15:25:56','2026-03-08 15:25:56'),(115,1,'updated','App\\Models\\Doctor',3,NULL,'127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Updated Doctor \"Dr. Amira Ahmed\"','2026-03-08 15:25:56','2026-03-08 15:25:56'),(116,1,'updated','App\\Models\\Doctor',4,'{\"old\":{\"dermatology_commission\":\"0.00\",\"cosmetic_commission\":\"0.00\",\"followup_commission\":\"0.00\"},\"new\":{\"dermatology_commission\":\"50\",\"cosmetic_commission\":\"50\",\"followup_commission\":\"50\"}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Updated Doctor \"Dr. Asmaa Hamdy\"','2026-03-08 15:26:50','2026-03-08 15:26:50'),(117,1,'created','App\\Models\\DoctorSchedule',71,'{\"new\":{\"day_of_week\":\"0\",\"start_time\":\"09:00\",\"end_time\":\"17:00\",\"is_active\":\"0\",\"doctor_id\":4,\"id\":71}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #71','2026-03-08 15:26:50','2026-03-08 15:26:50'),(118,1,'created','App\\Models\\DoctorSchedule',72,'{\"new\":{\"day_of_week\":\"1\",\"start_time\":\"09:00\",\"end_time\":\"17:00\",\"is_active\":\"0\",\"doctor_id\":4,\"id\":72}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #72','2026-03-08 15:26:50','2026-03-08 15:26:50'),(119,1,'created','App\\Models\\DoctorSchedule',73,'{\"new\":{\"day_of_week\":\"2\",\"start_time\":\"09:00\",\"end_time\":\"17:00\",\"is_active\":\"0\",\"doctor_id\":4,\"id\":73}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #73','2026-03-08 15:26:50','2026-03-08 15:26:50'),(120,1,'created','App\\Models\\DoctorSchedule',74,'{\"new\":{\"day_of_week\":\"3\",\"start_time\":\"09:00\",\"end_time\":\"17:00\",\"is_active\":\"0\",\"doctor_id\":4,\"id\":74}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #74','2026-03-08 15:26:50','2026-03-08 15:26:50'),(121,1,'created','App\\Models\\DoctorSchedule',75,'{\"new\":{\"day_of_week\":\"4\",\"start_time\":\"09:00\",\"end_time\":\"17:00\",\"is_active\":\"0\",\"doctor_id\":4,\"id\":75}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #75','2026-03-08 15:26:50','2026-03-08 15:26:50'),(122,1,'created','App\\Models\\DoctorSchedule',76,'{\"new\":{\"day_of_week\":\"5\",\"start_time\":\"09:00\",\"end_time\":\"17:00\",\"is_active\":\"0\",\"doctor_id\":4,\"id\":76}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #76','2026-03-08 15:26:50','2026-03-08 15:26:50'),(123,1,'created','App\\Models\\DoctorSchedule',77,'{\"new\":{\"day_of_week\":\"6\",\"start_time\":\"09:00\",\"end_time\":\"17:00\",\"is_active\":\"0\",\"doctor_id\":4,\"id\":77}}','127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Created DoctorSchedule #77','2026-03-08 15:26:50','2026-03-08 15:26:50'),(124,1,'updated','App\\Models\\Doctor',4,NULL,'127.0.0.1','admin','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','Admin Updated Doctor \"Dr. Asmaa Hamdy\"','2026-03-08 15:26:50','2026-03-08 15:26:50');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendances`
--

DROP TABLE IF EXISTS `attendances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `date` date NOT NULL,
  `check_in` time DEFAULT NULL,
  `check_out` time DEFAULT NULL,
  `status` enum('present','absent','late','leave') NOT NULL DEFAULT 'present',
  `overtime_hours` decimal(4,2) NOT NULL DEFAULT 0.00,
  `notes` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attendances_user_id_date_unique` (`user_id`,`date`),
  CONSTRAINT `attendances_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=67 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendances`
--

LOCK TABLES `attendances` WRITE;
/*!40000 ALTER TABLE `attendances` DISABLE KEYS */;
INSERT INTO `attendances` VALUES (1,1,'2026-02-16','08:54:00','18:00:00','present',1.70,NULL,'2026-02-17 01:45:21','2026-02-17 01:45:21'),(4,1,'2026-02-15','08:53:00','17:00:00','present',0.00,NULL,'2026-02-17 01:45:21','2026-02-17 01:45:21'),(7,1,'2026-02-14','09:21:00','17:00:00','late',0.00,NULL,'2026-02-17 01:45:21','2026-02-17 01:45:21'),(10,1,'2026-02-12','08:58:00','17:00:00','present',0.00,NULL,'2026-02-17 01:45:21','2026-02-17 01:45:21'),(13,1,'2026-02-11','09:14:00','17:00:00','late',0.00,NULL,'2026-02-17 01:45:21','2026-02-17 01:45:21'),(16,1,'2026-02-10','08:55:00','17:00:00','present',0.00,NULL,'2026-02-17 01:45:21','2026-02-17 01:45:21'),(19,1,'2026-02-09','08:54:00','17:00:00','present',0.00,NULL,'2026-02-17 01:45:21','2026-02-17 01:45:21'),(22,1,'2026-02-08','08:58:00','17:00:00','present',0.00,NULL,'2026-02-17 01:45:21','2026-02-17 01:45:21'),(25,1,'2026-02-07','08:51:00','17:00:00','present',0.00,NULL,'2026-02-17 01:45:21','2026-02-17 01:45:21'),(28,1,'2026-02-05','08:53:00','17:00:00','present',0.00,NULL,'2026-02-17 01:45:21','2026-02-17 01:45:21'),(31,1,'2026-02-04','08:59:00','17:00:00','present',0.00,NULL,'2026-02-17 01:45:21','2026-02-17 01:45:21'),(34,1,'2026-02-03',NULL,NULL,'absent',0.00,NULL,'2026-02-17 01:45:21','2026-02-17 01:45:21'),(62,1,'2026-02-17','08:58:00','18:00:00','present',1.90,NULL,'2026-02-18 19:13:33','2026-02-18 19:13:33');
/*!40000 ALTER TABLE `attendances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `booking_appointments`
--

DROP TABLE IF EXISTS `booking_appointments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `booking_appointments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint(20) unsigned NOT NULL,
  `booking_service_id` bigint(20) unsigned NOT NULL,
  `doctor_id` bigint(20) unsigned NOT NULL,
  `appointment_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `session_number` int(11) NOT NULL DEFAULT 1,
  `status` enum('scheduled','confirmed','checked_in','in_progress','completed','cancelled','no_show') NOT NULL DEFAULT 'scheduled',
  `is_retouch` tinyint(1) NOT NULL DEFAULT 0,
  `visit_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_doctor_timeslot` (`doctor_id`,`appointment_date`,`start_time`),
  KEY `booking_appointments_booking_service_id_foreign` (`booking_service_id`),
  KEY `booking_appointments_visit_id_foreign` (`visit_id`),
  KEY `booking_appointments_doctor_id_appointment_date_start_time_index` (`doctor_id`,`appointment_date`,`start_time`),
  KEY `booking_appointments_booking_id_status_index` (`booking_id`,`status`),
  KEY `booking_appointments_appointment_date_status_index` (`appointment_date`,`status`),
  CONSTRAINT `booking_appointments_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `booking_appointments_booking_service_id_foreign` FOREIGN KEY (`booking_service_id`) REFERENCES `booking_services` (`id`) ON DELETE CASCADE,
  CONSTRAINT `booking_appointments_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `booking_appointments_visit_id_foreign` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `booking_appointments`
--

LOCK TABLES `booking_appointments` WRITE;
/*!40000 ALTER TABLE `booking_appointments` DISABLE KEYS */;
/*!40000 ALTER TABLE `booking_appointments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `booking_consents`
--

DROP TABLE IF EXISTS `booking_consents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `booking_consents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint(20) unsigned NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `uploaded_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `booking_consents_booking_id_foreign` (`booking_id`),
  KEY `booking_consents_uploaded_by_foreign` (`uploaded_by`),
  CONSTRAINT `booking_consents_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `booking_consents_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `booking_consents`
--

LOCK TABLES `booking_consents` WRITE;
/*!40000 ALTER TABLE `booking_consents` DISABLE KEYS */;
/*!40000 ALTER TABLE `booking_consents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `booking_services`
--

DROP TABLE IF EXISTS `booking_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `booking_services` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint(20) unsigned NOT NULL,
  `service_id` bigint(20) unsigned DEFAULT NULL,
  `doctor_id` bigint(20) unsigned DEFAULT NULL,
  `sessions_count` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `discount_per_session` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_price` decimal(10,2) NOT NULL,
  `completed_sessions` int(11) NOT NULL DEFAULT 0,
  `status` enum('pending','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
  `notes` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `booking_services_booking_id_status_index` (`booking_id`,`status`),
  KEY `booking_services_service_id_index` (`service_id`),
  KEY `booking_services_doctor_id_index` (`doctor_id`),
  CONSTRAINT `booking_services_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `booking_services_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE SET NULL,
  CONSTRAINT `booking_services_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `booking_services`
--

LOCK TABLES `booking_services` WRITE;
/*!40000 ALTER TABLE `booking_services` DISABLE KEYS */;
/*!40000 ALTER TABLE `booking_services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bookings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` bigint(20) unsigned DEFAULT NULL,
  `booking_number` varchar(255) DEFAULT NULL,
  `source` varchar(20) NOT NULL DEFAULT 'website',
  `booking_type` enum('dermatology_consultation','cosmetic_consultation','service') DEFAULT NULL,
  `full_name` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `service_id` bigint(20) unsigned DEFAULT NULL,
  `doctor_id` bigint(20) unsigned DEFAULT NULL,
  `preferred_date` date DEFAULT NULL,
  `preferred_time` varchar(255) DEFAULT NULL,
  `notes` text,
  `status` varchar(30) DEFAULT 'unconfirmed',
  `admin_notes` text,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bookings_booking_number_unique` (`booking_number`),
  KEY `bookings_service_id_foreign` (`service_id`),
  KEY `bookings_doctor_id_foreign` (`doctor_id`),
  KEY `bookings_invoice_id_foreign` (`invoice_id`),
  KEY `bookings_created_by_foreign` (`created_by`),
  KEY `bookings_patient_id_index` (`patient_id`),
  KEY `bookings_source_index` (`source`),
  CONSTRAINT `bookings_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bookings_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bookings_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bookings_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bookings_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bookings`
--

LOCK TABLES `bookings` WRITE;
/*!40000 ALTER TABLE `bookings` DISABLE KEYS */;
/*!40000 ALTER TABLE `bookings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('laravel-cache-0716d9708d321ffb6a00818614779e779925365c','i:1;',1771442284),('laravel-cache-0716d9708d321ffb6a00818614779e779925365c:timer','i:1771442284;',1771442284),('laravel-cache-356a192b7913b04c54574d18c28d46e6395428ab','i:2;',1772995103),('laravel-cache-356a192b7913b04c54574d18c28d46e6395428ab:timer','i:1772995103;',1772995103),('laravel-cache-5c785c036466adea360111aa28563bfd556b5fba','i:2;',1772972445),('laravel-cache-5c785c036466adea360111aa28563bfd556b5fba:timer','i:1772972445;',1772972445),('laravel-cache-9e6a55b6b4563e652a23be9d623ca5055c356940','i:4;',1771716539),('laravel-cache-9e6a55b6b4563e652a23be9d623ca5055c356940:timer','i:1771716539;',1771716539),('laravel-cache-ac3478d69a3c81fa62e60f5c3696165a4e5e6ac4','i:2;',1772962820),('laravel-cache-ac3478d69a3c81fa62e60f5c3696165a4e5e6ac4:timer','i:1772962820;',1772962820),('laravel-cache-super_admin@app.com|127.0.0.1','i:1;',1772972445),('laravel-cache-super_admin@app.com|127.0.0.1:timer','i:1772972445;',1772972445),('laravel-cache-superadmin@markeza.com|127.0.0.1','i:1;',1772491594),('laravel-cache-superadmin@markeza.com|127.0.0.1:timer','i:1772491594;',1772491594),('laravel-cache-webmaster-login:webmaster@aura-derma.com|127.0.0.1','i:1;',1771441983),('laravel-cache-webmaster-login:webmaster@aura-derma.com|127.0.0.1:timer','i:1771441983;',1771441983);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_messages`
--

DROP TABLE IF EXISTS `contact_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contact_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_messages`
--

LOCK TABLES `contact_messages` WRITE;
/*!40000 ALTER TABLE `contact_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `contact_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `discount_codes`
--

DROP TABLE IF EXISTS `discount_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `discount_codes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `max_uses` int(11) DEFAULT NULL,
  `used_count` int(11) NOT NULL DEFAULT 0,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `applicable_services` longtext,
  `notes` text,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `discount_codes_code_unique` (`code`),
  KEY `discount_codes_created_by_foreign` (`created_by`),
  CONSTRAINT `discount_codes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `discount_codes`
--

LOCK TABLES `discount_codes` WRITE;
/*!40000 ALTER TABLE `discount_codes` DISABLE KEYS */;
/*!40000 ALTER TABLE `discount_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `discount_usage`
--

DROP TABLE IF EXISTS `discount_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `discount_usage` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `discount_code_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `discount_usage_discount_code_id_foreign` (`discount_code_id`),
  KEY `discount_usage_patient_id_foreign` (`patient_id`),
  KEY `discount_usage_invoice_id_foreign` (`invoice_id`),
  CONSTRAINT `discount_usage_discount_code_id_foreign` FOREIGN KEY (`discount_code_id`) REFERENCES `discount_codes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `discount_usage_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `discount_usage_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `discount_usage`
--

LOCK TABLES `discount_usage` WRITE;
/*!40000 ALTER TABLE `discount_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `discount_usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doctor_payout_visits`
--

DROP TABLE IF EXISTS `doctor_payout_visits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doctor_payout_visits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `doctor_payout_id` bigint(20) unsigned NOT NULL,
  `visit_id` bigint(20) unsigned NOT NULL,
  `visit_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `commission_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `commission_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `doctor_payout_visits_visit_id_unique` (`visit_id`),
  KEY `doctor_payout_visits_doctor_payout_id_foreign` (`doctor_payout_id`),
  CONSTRAINT `doctor_payout_visits_doctor_payout_id_foreign` FOREIGN KEY (`doctor_payout_id`) REFERENCES `doctor_payouts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `doctor_payout_visits_visit_id_foreign` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doctor_payout_visits`
--

LOCK TABLES `doctor_payout_visits` WRITE;
/*!40000 ALTER TABLE `doctor_payout_visits` DISABLE KEYS */;
/*!40000 ALTER TABLE `doctor_payout_visits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doctor_payouts`
--

DROP TABLE IF EXISTS `doctor_payouts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doctor_payouts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payout_number` varchar(255) NOT NULL,
  `doctor_id` bigint(20) unsigned NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `total_visits` int(10) unsigned NOT NULL DEFAULT 0,
  `total_revenue` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_commission` decimal(10,2) NOT NULL DEFAULT 0.00,
  `deductions` decimal(10,2) NOT NULL DEFAULT 0.00,
  `deduction_notes` text,
  `net_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','confirmed','paid','cancelled') NOT NULL DEFAULT 'draft',
  `notes` text,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `confirmed_by` bigint(20) unsigned DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `paid_by` bigint(20) unsigned DEFAULT NULL,
  `payment_method` varchar(255) DEFAULT NULL,
  `payment_reference` varchar(255) DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancellation_reason` text,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `doctor_payouts_payout_number_unique` (`payout_number`),
  KEY `doctor_payouts_confirmed_by_foreign` (`confirmed_by`),
  KEY `doctor_payouts_paid_by_foreign` (`paid_by`),
  KEY `doctor_payouts_cancelled_by_foreign` (`cancelled_by`),
  KEY `doctor_payouts_created_by_foreign` (`created_by`),
  KEY `doctor_payouts_doctor_id_status_index` (`doctor_id`,`status`),
  KEY `doctor_payouts_status_index` (`status`),
  CONSTRAINT `doctor_payouts_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `doctor_payouts_confirmed_by_foreign` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `doctor_payouts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `doctor_payouts_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `doctor_payouts_paid_by_foreign` FOREIGN KEY (`paid_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doctor_payouts`
--

LOCK TABLES `doctor_payouts` WRITE;
/*!40000 ALTER TABLE `doctor_payouts` DISABLE KEYS */;
/*!40000 ALTER TABLE `doctor_payouts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doctor_schedules`
--

DROP TABLE IF EXISTS `doctor_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doctor_schedules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` bigint(20) unsigned NOT NULL,
  `day_of_week` tinyint(4) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `doctor_schedules_doctor_id_day_of_week_unique` (`doctor_id`,`day_of_week`),
  CONSTRAINT `doctor_schedules_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=78 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doctor_schedules`
--

LOCK TABLES `doctor_schedules` WRITE;
/*!40000 ALTER TABLE `doctor_schedules` DISABLE KEYS */;
INSERT INTO `doctor_schedules` VALUES (36,1,0,'09:00:00','17:00:00',1,'2026-03-08 15:14:27','2026-03-08 15:14:27'),(37,1,1,'09:00:00','17:00:00',1,'2026-03-08 15:14:27','2026-03-08 15:14:27'),(38,1,2,'09:00:00','17:00:00',1,'2026-03-08 15:14:27','2026-03-08 15:14:27'),(39,1,3,'09:00:00','17:00:00',1,'2026-03-08 15:14:27','2026-03-08 15:14:27'),(40,1,4,'09:00:00','17:00:00',1,'2026-03-08 15:14:27','2026-03-08 15:14:27'),(41,1,5,'09:00:00','17:00:00',1,'2026-03-08 15:14:27','2026-03-08 15:14:27'),(42,1,6,'09:00:00','17:00:00',0,'2026-03-08 15:14:27','2026-03-08 15:14:27'),(43,2,0,'09:00:00','17:00:00',0,'2026-03-08 15:14:57','2026-03-08 15:14:57'),(44,2,1,'09:00:00','17:00:00',0,'2026-03-08 15:14:57','2026-03-08 15:14:57'),(45,2,2,'09:00:00','17:00:00',0,'2026-03-08 15:14:57','2026-03-08 15:14:57'),(46,2,3,'09:00:00','17:00:00',0,'2026-03-08 15:14:57','2026-03-08 15:14:57'),(47,2,4,'09:00:00','17:00:00',0,'2026-03-08 15:14:57','2026-03-08 15:14:57'),(48,2,5,'09:00:00','17:00:00',0,'2026-03-08 15:14:57','2026-03-08 15:14:57'),(49,2,6,'09:00:00','17:00:00',0,'2026-03-08 15:14:57','2026-03-08 15:14:57'),(64,3,0,'09:00:00','17:00:00',0,'2026-03-08 15:25:56','2026-03-08 15:25:56'),(65,3,1,'09:00:00','17:00:00',0,'2026-03-08 15:25:56','2026-03-08 15:25:56'),(66,3,2,'09:00:00','17:00:00',0,'2026-03-08 15:25:56','2026-03-08 15:25:56'),(67,3,3,'09:00:00','17:00:00',0,'2026-03-08 15:25:56','2026-03-08 15:25:56'),(68,3,4,'09:00:00','17:00:00',0,'2026-03-08 15:25:56','2026-03-08 15:25:56'),(69,3,5,'09:00:00','17:00:00',0,'2026-03-08 15:25:56','2026-03-08 15:25:56'),(70,3,6,'09:00:00','17:00:00',0,'2026-03-08 15:25:56','2026-03-08 15:25:56'),(71,4,0,'09:00:00','17:00:00',0,'2026-03-08 15:26:50','2026-03-08 15:26:50'),(72,4,1,'09:00:00','17:00:00',0,'2026-03-08 15:26:50','2026-03-08 15:26:50'),(73,4,2,'09:00:00','17:00:00',0,'2026-03-08 15:26:50','2026-03-08 15:26:50'),(74,4,3,'09:00:00','17:00:00',0,'2026-03-08 15:26:50','2026-03-08 15:26:50'),(75,4,4,'09:00:00','17:00:00',0,'2026-03-08 15:26:50','2026-03-08 15:26:50'),(76,4,5,'09:00:00','17:00:00',0,'2026-03-08 15:26:50','2026-03-08 15:26:50'),(77,4,6,'09:00:00','17:00:00',0,'2026-03-08 15:26:50','2026-03-08 15:26:50');
/*!40000 ALTER TABLE `doctor_schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doctor_service_rates`
--

DROP TABLE IF EXISTS `doctor_service_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doctor_service_rates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` bigint(20) unsigned NOT NULL,
  `service_id` bigint(20) unsigned NOT NULL,
  `commission_percentage` decimal(5,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `doctor_service_rates_doctor_id_service_id_unique` (`doctor_id`,`service_id`),
  KEY `doctor_service_rates_service_id_foreign` (`service_id`),
  CONSTRAINT `doctor_service_rates_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `doctor_service_rates_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doctor_service_rates`
--

LOCK TABLES `doctor_service_rates` WRITE;
/*!40000 ALTER TABLE `doctor_service_rates` DISABLE KEYS */;
/*!40000 ALTER TABLE `doctor_service_rates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doctor_vacations`
--

DROP TABLE IF EXISTS `doctor_vacations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doctor_vacations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` bigint(20) unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `doctor_vacations_doctor_id_foreign` (`doctor_id`),
  CONSTRAINT `doctor_vacations_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doctor_vacations`
--

LOCK TABLES `doctor_vacations` WRITE;
/*!40000 ALTER TABLE `doctor_vacations` DISABLE KEYS */;
/*!40000 ALTER TABLE `doctor_vacations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doctors`
--

DROP TABLE IF EXISTS `doctors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doctors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `name_ar` varchar(255) NOT NULL,
  `name_en` varchar(255) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `specialization_ar` varchar(255) DEFAULT NULL,
  `specialization_en` varchar(255) DEFAULT NULL,
  `bio_ar` text,
  `bio_en` text,
  `qualifications_ar` text,
  `qualifications_en` text,
  `phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `default_commission_percentage` decimal(5,2) DEFAULT 0.00,
  `consultation_fee` decimal(10,2) DEFAULT 0.00,
  `dermatology_fee` decimal(10,2) DEFAULT NULL,
  `cosmetic_fee` decimal(10,2) DEFAULT NULL,
  `dermatology_commission` decimal(5,2) DEFAULT NULL,
  `cosmetic_commission` decimal(5,2) DEFAULT NULL,
  `followup_commission` decimal(5,2) DEFAULT NULL,
  `clinic_notes` text,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `doctor_type` enum('consultant','specialist') DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `doctors_user_id_foreign` (`user_id`),
  CONSTRAINT `doctors_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doctors`
--

LOCK TABLES `doctors` WRITE;
/*!40000 ALTER TABLE `doctors` DISABLE KEYS */;
INSERT INTO `doctors` VALUES (1,19,'د. آلاء العوضي','Dr. Alaa Elawady','uploads/doctors/UyDBde5V50kgBH0q5VzpebQx8Hxkq9E5GNpeMxte.png','أخصائية جلدية وتناسلية','Dermatology & Venereology Specialist',NULL,NULL,NULL,NULL,NULL,'dr.alaa@auraderma.com',0.00,400.00,400.00,200.00,40.00,40.00,40.00,NULL,1,'active','specialist','2026-03-08 15:02:41','2026-03-08 15:14:27'),(2,20,'د. إيمان مجدي','Dr. Eman Magdy','uploads/doctors/gMxyk02SCjId7Q1tZrNiUxsLCyjOsf32KGYCsiuQ.png','أخصائية جلدية وتناسلية','Dermatology & Venereology Specialist',NULL,NULL,NULL,NULL,NULL,'dr.eman@auraderma.com',0.00,400.00,400.00,200.00,40.00,40.00,40.00,NULL,2,'active','specialist','2026-03-08 15:09:40','2026-03-08 15:14:57'),(3,21,'د. أميرة أحمد','Dr. Amira Ahmed','uploads/doctors/HL9NngDCXCpTOLwq2eiLCvJngJ5bhTUBGVWobTtQ.png','أخصائية جلدية وتناسلية','Dermatology & Venereology Specialist',NULL,NULL,NULL,NULL,NULL,'dr.amira@auraderma.com',0.00,400.00,400.00,200.00,40.00,40.00,40.00,NULL,3,'active','specialist','2026-03-08 15:21:07','2026-03-08 15:25:56'),(4,22,'د. أسماء حمدي','Dr. Asmaa Hamdy',NULL,'استشارية جلدية وتناسلية','Dermatology & Venereology Consultant',NULL,NULL,NULL,NULL,NULL,'dr.asmaa@auraderma.com',0.00,400.00,400.00,200.00,50.00,50.00,50.00,NULL,4,'active','consultant','2026-03-08 15:25:10','2026-03-08 15:26:50');
/*!40000 ALTER TABLE `doctors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_shifts`
--

DROP TABLE IF EXISTS `employee_shifts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_shifts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `shift_id` bigint(20) unsigned NOT NULL,
  `day_of_week` tinyint(4) NOT NULL,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_shifts_user_id_foreign` (`user_id`),
  KEY `employee_shifts_shift_id_foreign` (`shift_id`),
  CONSTRAINT `employee_shifts_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_shifts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_shifts`
--

LOCK TABLES `employee_shifts` WRITE;
/*!40000 ALTER TABLE `employee_shifts` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_shifts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expense_categories`
--

DROP TABLE IF EXISTS `expense_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expense_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name_ar` varchar(255) NOT NULL,
  `name_en` varchar(255) NOT NULL,
  `description` text,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expense_categories`
--

LOCK TABLES `expense_categories` WRITE;
/*!40000 ALTER TABLE `expense_categories` DISABLE KEYS */;
INSERT INTO `expense_categories` VALUES (1,'الإيجار والمرافق','Rent & Utilities',NULL,1,1,'2026-02-17 01:45:37','2026-02-17 01:45:37'),(2,'الرواتب','Salaries',NULL,2,1,'2026-02-17 01:45:37','2026-02-17 01:45:37'),(3,'المستلزمات الطبية','Medical Supplies',NULL,3,1,'2026-02-17 01:45:37','2026-02-17 01:45:37'),(4,'صيانة المعدات','Equipment Maintenance',NULL,4,1,'2026-02-17 01:45:37','2026-02-17 01:45:37'),(5,'التسويق والإعلان','Marketing & Advertising',NULL,5,1,'2026-02-17 01:45:37','2026-02-17 01:45:37'),(6,'مصاريف إدارية','Administrative',NULL,6,1,'2026-02-17 01:45:37','2026-02-17 01:45:37'),(7,'مصاريف أخرى','Other Expenses',NULL,7,1,'2026-02-17 01:45:37','2026-02-17 01:45:37');
/*!40000 ALTER TABLE `expense_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expense_items`
--

DROP TABLE IF EXISTS `expense_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expense_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `expense_category_id` bigint(20) unsigned NOT NULL,
  `name_ar` varchar(255) NOT NULL,
  `name_en` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expense_items_expense_category_id_foreign` (`expense_category_id`),
  CONSTRAINT `expense_items_expense_category_id_foreign` FOREIGN KEY (`expense_category_id`) REFERENCES `expense_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expense_items`
--

LOCK TABLES `expense_items` WRITE;
/*!40000 ALTER TABLE `expense_items` DISABLE KEYS */;
INSERT INTO `expense_items` VALUES (1,1,'إيجار العيادة','Clinic Rent',1,'2026-02-17 01:45:37','2026-02-17 01:45:37'),(2,1,'كهرباء','Electricity',1,'2026-02-17 01:45:37','2026-02-17 01:45:37'),(3,1,'مياه','Water',1,'2026-02-17 01:45:37','2026-02-17 01:45:37'),(4,1,'إنترنت','Internet',1,'2026-02-17 01:45:37','2026-02-17 01:45:37'),(5,1,'هاتف','Phone',1,'2026-02-17 01:45:37','2026-02-17 01:45:37'),(6,2,'رواتب الأطباء','Doctor Salaries',1,'2026-02-17 01:45:37','2026-02-17 01:45:37'),(7,2,'رواتب الممرضات','Nurse Salaries',1,'2026-02-17 01:45:37','2026-02-17 01:45:37'),(8,2,'رواتب الاستقبال','Receptionist Salaries',1,'2026-02-17 01:45:37','2026-02-17 01:45:37'),(9,2,'رواتب أخرى','Other Salaries',1,'2026-02-17 01:45:37','2026-02-17 01:45:37'),(10,3,'مستلزمات ليزر','Laser Supplies',1,'2026-02-17 01:45:37','2026-02-17 01:45:37'),(11,3,'مستلزمات تجميل','Cosmetic Supplies',1,'2026-02-17 01:45:37','2026-02-17 01:45:37'),(12,3,'قفازات ومعقمات','Gloves & Sanitizers',1,'2026-02-17 01:45:37','2026-02-17 01:45:37'),(13,3,'أدوية ومراهم','Medications & Ointments',1,'2026-02-17 01:45:37','2026-02-17 01:45:37'),(14,4,'صيانة أجهزة الليزر','Laser Device Maintenance',1,'2026-02-17 01:45:37','2026-02-17 01:45:37'),(15,4,'صيانة عامة','General Maintenance',1,'2026-02-17 01:45:37','2026-02-17 01:45:37'),(16,4,'قطع غيار','Spare Parts',1,'2026-02-17 01:45:37','2026-02-17 01:45:37'),(17,5,'إعلانات سوشيال ميديا','Social Media Ads',1,'2026-02-17 01:45:37','2026-02-17 01:45:37'),(18,5,'طباعة ومواد دعائية','Print Materials',1,'2026-02-17 01:45:37','2026-02-17 01:45:37'),(19,5,'تصوير وفيديو','Photography & Video',1,'2026-02-17 01:45:37','2026-02-17 01:45:37'),(20,6,'قرطاسية','Stationery',1,'2026-02-17 01:45:37','2026-02-17 01:45:37'),(21,6,'برمجيات واشتراكات','Software & Subscriptions',1,'2026-02-17 01:45:37','2026-02-17 01:45:37'),(22,6,'نقل ومواصلات','Transportation',1,'2026-02-17 01:45:37','2026-02-17 01:45:37'),(23,7,'ضيافة','Hospitality',1,'2026-02-17 01:45:37','2026-02-17 01:45:37'),(24,7,'تأمين','Insurance',1,'2026-02-17 01:45:37','2026-02-17 01:45:37'),(25,7,'مصاريف متنوعة','Miscellaneous',1,'2026-02-17 01:45:37','2026-02-17 01:45:37');
/*!40000 ALTER TABLE `expense_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expenses`
--

DROP TABLE IF EXISTS `expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expenses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `expense_category_id` bigint(20) unsigned DEFAULT NULL,
  `expense_item_id` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `expense_date` date NOT NULL,
  `description` text,
  `receipt_photo` varchar(255) DEFAULT NULL,
  `is_recurring` tinyint(1) NOT NULL DEFAULT 0,
  `recurring_period` enum('daily','weekly','monthly','yearly') DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expenses_expense_category_id_foreign` (`expense_category_id`),
  KEY `expenses_expense_item_id_foreign` (`expense_item_id`),
  KEY `expenses_created_by_foreign` (`created_by`),
  KEY `expenses_expense_date_index` (`expense_date`),
  CONSTRAINT `expenses_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expenses_expense_category_id_foreign` FOREIGN KEY (`expense_category_id`) REFERENCES `expense_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expenses_expense_item_id_foreign` FOREIGN KEY (`expense_item_id`) REFERENCES `expense_items` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=121 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expenses`
--

LOCK TABLES `expenses` WRITE;
/*!40000 ALTER TABLE `expenses` DISABLE KEYS */;
INSERT INTO `expenses` VALUES (1,5,18,1085.00,'2026-01-18','Expense: Marketing & Advertising - Print Materials',NULL,0,NULL,1,'2026-02-17 01:45:56','2026-02-17 01:45:56'),(2,2,8,15215.00,'2025-12-19','Expense: Salaries - Receptionist Salaries',NULL,1,'monthly',1,'2026-02-17 01:45:56','2026-02-17 01:45:56'),(3,6,20,155.00,'2026-02-12','Expense: Administrative - Stationery',NULL,0,NULL,1,'2026-02-17 01:45:56','2026-02-17 01:45:56'),(4,5,17,2738.00,'2026-01-05','Expense: Marketing & Advertising - Social Media Ads',NULL,0,NULL,1,'2026-02-17 01:45:56','2026-02-17 01:45:56'),(5,5,17,872.00,'2025-12-27','Expense: Marketing & Advertising - Social Media Ads',NULL,0,NULL,1,'2026-02-17 01:45:56','2026-02-17 01:45:56'),(6,5,17,4891.00,'2025-12-24','Expense: Marketing & Advertising - Social Media Ads',NULL,0,NULL,1,'2026-02-17 01:45:56','2026-02-17 01:45:56'),(7,3,12,3164.00,'2026-02-05','Expense: Medical Supplies - Gloves & Sanitizers',NULL,0,NULL,1,'2026-02-17 01:45:56','2026-02-17 01:45:56'),(8,1,3,8457.00,'2026-01-01','Expense: Rent & Utilities - Water',NULL,1,'monthly',1,'2026-02-17 01:45:56','2026-02-17 01:45:56'),(9,5,19,4595.00,'2026-01-15','Expense: Marketing & Advertising - Photography & Video',NULL,0,NULL,1,'2026-02-17 01:45:56','2026-02-17 01:45:56'),(10,1,5,8931.00,'2026-02-03','Expense: Rent & Utilities - Phone',NULL,1,'monthly',1,'2026-02-17 01:45:56','2026-02-17 01:45:56'),(11,6,21,1701.00,'2026-02-16','Expense: Administrative - Software & Subscriptions',NULL,0,NULL,1,'2026-02-17 01:45:56','2026-02-17 01:45:56'),(12,6,22,1252.00,'2025-12-20','Expense: Administrative - Transportation',NULL,0,NULL,1,'2026-02-17 01:45:56','2026-02-17 01:45:56'),(13,7,24,1590.00,'2026-02-16','Expense: Other Expenses - Insurance',NULL,1,'daily',1,'2026-02-17 01:45:56','2026-02-17 02:45:55'),(14,2,9,18730.00,'2025-12-26','Expense: Salaries - Other Salaries',NULL,1,'monthly',1,'2026-02-17 01:45:56','2026-02-17 01:45:56'),(15,1,3,2947.00,'2026-01-06','Expense: Rent & Utilities - Water',NULL,1,'monthly',1,'2026-02-17 01:45:56','2026-02-17 01:45:56'),(16,7,24,2821.00,'2025-12-29','Expense: Other Expenses - Insurance',NULL,0,NULL,1,'2026-02-17 01:45:56','2026-02-17 01:45:56'),(17,2,6,18599.00,'2026-01-24','Expense: Salaries - Doctor Salaries',NULL,1,'monthly',1,'2026-02-17 01:45:56','2026-02-17 01:45:56'),(18,1,4,7554.00,'2026-01-02','Expense: Rent & Utilities - Internet',NULL,1,'monthly',1,'2026-02-17 01:45:56','2026-02-17 01:45:56'),(19,4,14,3908.00,'2026-01-12','Expense: Equipment Maintenance - Laser Device Maintenance',NULL,0,NULL,1,'2026-02-17 01:45:56','2026-02-17 01:45:56'),(20,2,8,20062.00,'2026-01-02','Expense: Salaries - Receptionist Salaries',NULL,1,'monthly',1,'2026-02-17 01:45:56','2026-02-17 01:45:56'),(21,5,18,3839.00,'2026-01-22','Expense: Marketing & Advertising - Print Materials',NULL,0,NULL,1,'2026-02-17 01:45:56','2026-02-17 01:45:56'),(22,7,24,2909.00,'2026-01-03','Expense: Other Expenses - Insurance',NULL,0,NULL,1,'2026-02-17 01:45:56','2026-02-17 01:45:56'),(23,5,17,601.00,'2025-12-19','Expense: Marketing & Advertising - Social Media Ads',NULL,0,NULL,1,'2026-02-17 01:45:56','2026-02-17 01:45:56'),(24,7,23,154.00,'2026-02-11','Expense: Other Expenses - Hospitality',NULL,0,NULL,1,'2026-02-17 01:45:56','2026-02-17 01:45:56'),(25,3,11,3373.00,'2026-01-15','Expense: Medical Supplies - Cosmetic Supplies',NULL,0,NULL,1,'2026-02-17 01:45:56','2026-02-17 01:45:56'),(26,2,7,7848.00,'2026-01-27','Expense: Salaries - Nurse Salaries',NULL,1,'monthly',1,'2026-02-17 01:45:56','2026-02-17 01:45:56'),(27,3,12,1192.00,'2026-02-08','Expense: Medical Supplies - Gloves & Sanitizers',NULL,0,NULL,1,'2026-02-17 01:45:56','2026-02-17 01:45:56'),(28,4,15,8976.00,'2026-02-15','Expense: Equipment Maintenance - General Maintenance',NULL,0,NULL,1,'2026-02-17 01:45:56','2026-02-17 01:45:56'),(29,5,18,2818.00,'2026-02-13','Expense: Marketing & Advertising - Print Materials',NULL,0,NULL,1,'2026-02-17 01:45:56','2026-02-17 01:45:56'),(30,3,12,4579.00,'2026-01-15','Expense: Medical Supplies - Gloves & Sanitizers',NULL,0,NULL,1,'2026-02-17 01:45:56','2026-02-17 01:45:56'),(31,4,14,9322.00,'2026-02-05','Expense: Equipment Maintenance - Laser Device Maintenance',NULL,0,NULL,1,'2026-02-17 15:09:14','2026-02-17 15:09:14'),(32,3,13,4419.00,'2025-12-28','Expense: Medical Supplies - Medications & Ointments',NULL,0,NULL,1,'2026-02-17 15:09:14','2026-02-17 15:09:14'),(33,3,13,3679.00,'2025-12-19','Expense: Medical Supplies - Medications & Ointments',NULL,0,NULL,1,'2026-02-17 15:09:14','2026-02-17 15:09:14'),(34,6,21,94.00,'2026-01-24','Expense: Administrative - Software & Subscriptions',NULL,0,NULL,1,'2026-02-17 15:09:14','2026-02-17 15:09:14'),(35,3,11,2313.00,'2025-12-24','Expense: Medical Supplies - Cosmetic Supplies',NULL,0,NULL,1,'2026-02-17 15:09:14','2026-02-17 15:09:14'),(36,5,19,3951.00,'2025-12-29','Expense: Marketing & Advertising - Photography & Video',NULL,0,NULL,1,'2026-02-17 15:09:14','2026-02-17 15:09:14'),(37,3,10,1956.00,'2026-02-06','Expense: Medical Supplies - Laser Supplies',NULL,0,NULL,1,'2026-02-17 15:09:14','2026-02-17 15:09:14'),(38,5,18,1071.00,'2026-01-08','Expense: Marketing & Advertising - Print Materials',NULL,0,NULL,1,'2026-02-17 15:09:14','2026-02-17 15:09:14'),(39,4,15,9064.00,'2026-01-23','Expense: Equipment Maintenance - General Maintenance',NULL,0,NULL,1,'2026-02-17 15:09:14','2026-02-17 15:09:14'),(40,6,22,963.00,'2025-12-21','Expense: Administrative - Transportation',NULL,0,NULL,1,'2026-02-17 15:09:14','2026-02-17 15:09:14'),(41,1,3,1953.00,'2026-01-28','Expense: Rent & Utilities - Water',NULL,1,'monthly',1,'2026-02-17 15:09:14','2026-02-17 15:09:14'),(42,2,9,8559.00,'2026-02-05','Expense: Salaries - Other Salaries',NULL,1,'monthly',1,'2026-02-17 15:09:14','2026-02-17 15:09:14'),(43,6,22,1982.00,'2026-01-02','Expense: Administrative - Transportation',NULL,0,NULL,1,'2026-02-17 15:09:14','2026-02-17 15:09:14'),(44,3,10,3693.00,'2026-01-28','Expense: Medical Supplies - Laser Supplies',NULL,0,NULL,1,'2026-02-17 15:09:14','2026-02-17 15:09:14'),(45,6,21,1293.00,'2026-01-16','Expense: Administrative - Software & Subscriptions',NULL,0,NULL,1,'2026-02-17 15:09:14','2026-02-17 15:09:14'),(46,6,22,342.00,'2026-02-08','Expense: Administrative - Transportation',NULL,0,NULL,1,'2026-02-17 15:09:14','2026-02-17 15:09:14'),(47,4,16,7582.00,'2026-01-06','Expense: Equipment Maintenance - Spare Parts',NULL,0,NULL,1,'2026-02-17 15:09:14','2026-02-17 15:09:14'),(48,2,6,13914.00,'2026-01-10','Expense: Salaries - Doctor Salaries',NULL,1,'monthly',1,'2026-02-17 15:09:14','2026-02-17 15:09:14'),(49,3,13,2333.00,'2026-01-27','Expense: Medical Supplies - Medications & Ointments',NULL,0,NULL,1,'2026-02-17 15:09:14','2026-02-17 15:09:14'),(50,2,9,10052.00,'2026-01-02','Expense: Salaries - Other Salaries',NULL,1,'monthly',1,'2026-02-17 15:09:14','2026-02-17 15:09:14'),(51,4,16,3222.00,'2025-12-27','Expense: Equipment Maintenance - Spare Parts',NULL,0,NULL,1,'2026-02-17 15:09:14','2026-02-17 15:09:14'),(52,6,21,1035.00,'2026-01-23','Expense: Administrative - Software & Subscriptions',NULL,0,NULL,1,'2026-02-17 15:09:14','2026-02-17 15:09:14'),(53,2,7,11591.00,'2026-01-14','Expense: Salaries - Nurse Salaries',NULL,1,'monthly',1,'2026-02-17 15:09:14','2026-02-17 15:09:14'),(54,7,25,2102.00,'2026-01-13','Expense: Other Expenses - Miscellaneous',NULL,0,NULL,1,'2026-02-17 15:09:14','2026-02-17 15:09:14'),(55,1,1,10796.00,'2026-02-13','Expense: Rent & Utilities - Clinic Rent',NULL,1,'monthly',1,'2026-02-17 15:09:14','2026-02-17 15:09:14'),(56,6,21,1008.00,'2026-01-08','Expense: Administrative - Software & Subscriptions',NULL,0,NULL,1,'2026-02-17 15:09:14','2026-02-17 15:09:14'),(57,6,21,1631.00,'2026-01-15','Expense: Administrative - Software & Subscriptions',NULL,0,NULL,1,'2026-02-17 15:09:14','2026-02-17 15:09:14'),(58,1,2,550.00,'2026-02-07','Expense: Rent & Utilities - Electricity',NULL,1,'monthly',1,'2026-02-17 15:09:14','2026-02-17 15:09:14'),(59,3,13,3207.00,'2026-02-01','Expense: Medical Supplies - Medications & Ointments',NULL,0,NULL,1,'2026-02-17 15:09:14','2026-02-17 15:09:14'),(60,6,20,1103.00,'2025-12-30','Expense: Administrative - Stationery',NULL,0,NULL,1,'2026-02-17 15:09:14','2026-02-17 15:09:14'),(61,4,16,5635.00,'2026-01-17','Expense: Equipment Maintenance - Spare Parts',NULL,0,NULL,1,'2026-02-17 15:09:40','2026-02-17 15:09:40'),(62,1,4,4543.00,'2026-01-10','Expense: Rent & Utilities - Internet',NULL,1,'monthly',1,'2026-02-17 15:09:40','2026-02-17 15:09:40'),(63,1,5,13663.00,'2026-02-09','Expense: Rent & Utilities - Phone',NULL,1,'monthly',1,'2026-02-17 15:09:40','2026-02-17 15:09:40'),(64,3,12,2694.00,'2026-02-16','Expense: Medical Supplies - Gloves & Sanitizers',NULL,0,NULL,1,'2026-02-17 15:09:40','2026-02-17 15:09:40'),(65,4,15,3274.00,'2026-02-05','Expense: Equipment Maintenance - General Maintenance',NULL,0,NULL,1,'2026-02-17 15:09:40','2026-02-17 15:09:40'),(66,2,9,21254.00,'2025-12-22','Expense: Salaries - Other Salaries',NULL,1,'monthly',1,'2026-02-17 15:09:40','2026-02-17 15:09:40'),(67,3,12,3253.00,'2026-02-03','Expense: Medical Supplies - Gloves & Sanitizers',NULL,0,NULL,1,'2026-02-17 15:09:40','2026-02-17 15:09:40'),(68,1,3,1245.00,'2026-01-08','Expense: Rent & Utilities - Water',NULL,1,'monthly',1,'2026-02-17 15:09:40','2026-02-17 15:09:40'),(69,1,5,11172.00,'2025-12-23','Expense: Rent & Utilities - Phone',NULL,1,'monthly',1,'2026-02-17 15:09:40','2026-02-17 15:09:40'),(70,4,14,5231.00,'2026-02-01','Expense: Equipment Maintenance - Laser Device Maintenance',NULL,0,NULL,1,'2026-02-17 15:09:40','2026-02-17 15:09:40'),(71,3,12,2613.00,'2026-01-29','Expense: Medical Supplies - Gloves & Sanitizers',NULL,0,NULL,1,'2026-02-17 15:09:40','2026-02-17 15:09:40'),(72,6,21,1411.00,'2026-01-16','Expense: Administrative - Software & Subscriptions',NULL,0,NULL,1,'2026-02-17 15:09:40','2026-02-17 15:09:40'),(73,4,16,5737.00,'2026-02-11','Expense: Equipment Maintenance - Spare Parts',NULL,0,NULL,1,'2026-02-17 15:09:40','2026-02-17 15:09:40'),(74,4,15,8366.00,'2025-12-31','Expense: Equipment Maintenance - General Maintenance',NULL,0,NULL,1,'2026-02-17 15:09:40','2026-02-17 15:09:40'),(75,6,22,496.00,'2025-12-22','Expense: Administrative - Transportation',NULL,0,NULL,1,'2026-02-17 15:09:40','2026-02-17 15:09:40'),(76,7,23,2961.00,'2026-01-13','Expense: Other Expenses - Hospitality',NULL,0,NULL,1,'2026-02-17 15:09:40','2026-02-17 15:09:40'),(77,1,1,1051.00,'2026-01-12','Expense: Rent & Utilities - Clinic Rent',NULL,1,'monthly',1,'2026-02-17 15:09:40','2026-02-17 15:09:40'),(78,3,10,4333.00,'2025-12-25','Expense: Medical Supplies - Laser Supplies',NULL,0,NULL,1,'2026-02-17 15:09:40','2026-02-17 15:09:40'),(79,6,20,1904.00,'2025-12-25','Expense: Administrative - Stationery',NULL,0,NULL,1,'2026-02-17 15:09:40','2026-02-17 15:09:40'),(80,2,9,22714.00,'2025-12-19','Expense: Salaries - Other Salaries',NULL,1,'monthly',1,'2026-02-17 15:09:40','2026-02-17 15:09:40'),(81,7,23,1244.00,'2025-12-19','Expense: Other Expenses - Hospitality',NULL,0,NULL,1,'2026-02-17 15:09:40','2026-02-17 15:09:40'),(82,4,16,9763.00,'2026-01-14','Expense: Equipment Maintenance - Spare Parts',NULL,0,NULL,1,'2026-02-17 15:09:40','2026-02-17 15:09:40'),(83,7,24,1153.00,'2026-01-17','Expense: Other Expenses - Insurance',NULL,0,NULL,1,'2026-02-17 15:09:40','2026-02-17 15:09:40'),(84,7,23,2147.00,'2026-02-14','Expense: Other Expenses - Hospitality',NULL,0,NULL,1,'2026-02-17 15:09:40','2026-02-17 15:09:40'),(85,6,22,1618.00,'2026-01-13','Expense: Administrative - Transportation',NULL,0,NULL,1,'2026-02-17 15:09:40','2026-02-17 15:09:40'),(86,4,16,1970.00,'2026-01-18','Expense: Equipment Maintenance - Spare Parts',NULL,0,NULL,1,'2026-02-17 15:09:40','2026-02-17 15:09:40'),(87,3,11,2084.00,'2025-12-22','Expense: Medical Supplies - Cosmetic Supplies',NULL,0,NULL,1,'2026-02-17 15:09:40','2026-02-17 15:09:40'),(88,7,25,155.00,'2026-01-14','Expense: Other Expenses - Miscellaneous',NULL,0,NULL,1,'2026-02-17 15:09:40','2026-02-17 15:09:40'),(89,7,23,1027.00,'2026-02-09','Expense: Other Expenses - Hospitality',NULL,0,NULL,1,'2026-02-17 15:09:40','2026-02-17 15:09:40'),(90,3,10,2820.00,'2026-01-10','Expense: Medical Supplies - Laser Supplies',NULL,0,NULL,1,'2026-02-17 15:09:40','2026-02-17 15:09:40'),(91,4,16,5852.00,'2026-01-17','Expense: Equipment Maintenance - Spare Parts',NULL,0,NULL,1,'2026-02-18 19:13:33','2026-02-18 19:13:33'),(92,7,24,1148.00,'2026-01-29','Expense: Other Expenses - Insurance',NULL,0,NULL,1,'2026-02-18 19:13:33','2026-02-18 19:13:33'),(93,3,10,2086.00,'2025-12-24','Expense: Medical Supplies - Laser Supplies',NULL,0,NULL,1,'2026-02-18 19:13:33','2026-02-18 19:13:33'),(94,1,2,7701.00,'2026-02-04','Expense: Rent & Utilities - Electricity',NULL,1,'monthly',1,'2026-02-18 19:13:33','2026-02-18 19:13:33'),(95,3,13,4374.00,'2026-01-13','Expense: Medical Supplies - Medications & Ointments',NULL,0,NULL,1,'2026-02-18 19:13:33','2026-02-18 19:13:33'),(96,6,21,52.00,'2026-01-12','Expense: Administrative - Software & Subscriptions',NULL,0,NULL,1,'2026-02-18 19:13:33','2026-02-18 19:13:33'),(97,2,9,4270.00,'2025-12-31','Expense: Salaries - Other Salaries',NULL,1,'monthly',1,'2026-02-18 19:13:33','2026-02-18 19:13:33'),(98,3,10,4220.00,'2025-12-31','Expense: Medical Supplies - Laser Supplies',NULL,0,NULL,1,'2026-02-18 19:13:33','2026-02-18 19:13:33'),(99,3,11,4747.00,'2026-01-18','Expense: Medical Supplies - Cosmetic Supplies',NULL,0,NULL,1,'2026-02-18 19:13:33','2026-02-18 19:13:33'),(100,2,6,12703.00,'2026-02-16','Expense: Salaries - Doctor Salaries',NULL,1,'monthly',1,'2026-02-18 19:13:33','2026-02-18 19:13:33'),(101,6,22,577.00,'2026-01-30','Expense: Administrative - Transportation',NULL,0,NULL,1,'2026-02-18 19:13:33','2026-02-18 19:13:33'),(102,5,18,864.00,'2026-01-24','Expense: Marketing & Advertising - Print Materials',NULL,0,NULL,1,'2026-02-18 19:13:33','2026-02-18 19:13:33'),(103,3,12,3561.00,'2026-01-05','Expense: Medical Supplies - Gloves & Sanitizers',NULL,0,NULL,1,'2026-02-18 19:13:33','2026-02-18 19:13:33'),(104,3,12,1419.00,'2026-02-12','Expense: Medical Supplies - Gloves & Sanitizers',NULL,0,NULL,1,'2026-02-18 19:13:33','2026-02-18 19:13:33'),(105,1,1,4501.00,'2026-01-17','Expense: Rent & Utilities - Clinic Rent',NULL,1,'monthly',1,'2026-02-18 19:13:33','2026-02-18 19:13:33'),(106,1,3,2573.00,'2025-12-20','Expense: Rent & Utilities - Water',NULL,1,'monthly',1,'2026-02-18 19:13:33','2026-02-18 19:13:33'),(107,2,7,7413.00,'2026-01-06','Expense: Salaries - Nurse Salaries',NULL,1,'monthly',1,'2026-02-18 19:13:33','2026-02-18 19:13:33'),(108,6,21,1233.00,'2026-02-17','Expense: Administrative - Software & Subscriptions',NULL,0,NULL,1,'2026-02-18 19:13:33','2026-02-18 19:13:33'),(109,4,15,1276.00,'2026-01-07','Expense: Equipment Maintenance - General Maintenance',NULL,0,NULL,1,'2026-02-18 19:13:33','2026-02-18 19:13:33'),(110,2,8,14652.00,'2026-02-12','Expense: Salaries - Receptionist Salaries',NULL,1,'monthly',1,'2026-02-18 19:13:33','2026-02-18 19:13:33'),(111,1,2,14840.00,'2026-01-05','Expense: Rent & Utilities - Electricity',NULL,1,'monthly',1,'2026-02-18 19:13:33','2026-02-18 19:13:33'),(112,3,10,2793.00,'2025-12-28','Expense: Medical Supplies - Laser Supplies',NULL,0,NULL,1,'2026-02-18 19:13:33','2026-02-18 19:13:33'),(113,5,17,2095.00,'2026-01-18','Expense: Marketing & Advertising - Social Media Ads',NULL,0,NULL,1,'2026-02-18 19:13:33','2026-02-18 19:13:33'),(114,3,10,4765.00,'2026-02-17','Expense: Medical Supplies - Laser Supplies',NULL,0,NULL,1,'2026-02-18 19:13:33','2026-02-18 19:13:33'),(115,6,21,1958.00,'2025-12-31','Expense: Administrative - Software & Subscriptions',NULL,0,NULL,1,'2026-02-18 19:13:33','2026-02-18 19:13:33'),(116,6,22,1159.00,'2026-01-25','Expense: Administrative - Transportation',NULL,0,NULL,1,'2026-02-18 19:13:33','2026-02-18 19:13:33'),(117,4,16,1016.00,'2026-01-17','Expense: Equipment Maintenance - Spare Parts',NULL,0,NULL,1,'2026-02-18 19:13:33','2026-02-18 19:13:33'),(118,7,25,1236.00,'2026-02-04','Expense: Other Expenses - Miscellaneous',NULL,0,NULL,1,'2026-02-18 19:13:33','2026-02-18 19:13:33'),(119,1,3,6369.00,'2025-12-24','Expense: Rent & Utilities - Water',NULL,1,'monthly',1,'2026-02-18 19:13:33','2026-02-18 19:13:33'),(120,3,12,2574.00,'2026-01-14','Expense: Medical Supplies - Gloves & Sanitizers',NULL,0,NULL,1,'2026-02-18 19:13:33','2026-02-18 19:13:33');
/*!40000 ALTER TABLE `expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `faqs`
--

DROP TABLE IF EXISTS `faqs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `faqs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category` varchar(255) NOT NULL DEFAULT 'general',
  `question_ar` text NOT NULL,
  `question_en` text NOT NULL,
  `answer_ar` text NOT NULL,
  `answer_en` text NOT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `faqs`
--

LOCK TABLES `faqs` WRITE;
/*!40000 ALTER TABLE `faqs` DISABLE KEYS */;
INSERT INTO `faqs` VALUES (1,'general','أين يقع عيادة أورا ديرما كلينك؟','Where is Aura Derma Clinic located?','تقع عيادة أورا ديرما كلينك في ٦ أكتوبر - كايرو ميديكال سنتر (CMC) - المحور المركزي - الدور الثاني - عيادة رقم 71.','Aura Derma Clinic is located at CMC (Cairo Medical Center), Central Axis, 6th of October City, 2nd Floor, Clinic No. 71.',1,'2026-02-16 04:27:58','2026-02-16 04:27:58'),(2,'general','ما هي مواعيد العمل في العيادة؟','What are the clinic working hours?','العيادة تعمل يومياً من الساعة 10:00 صباحاً حتى 10:00 مساءً.','The clinic operates daily from 10:00 AM to 10:00 PM.',2,'2026-02-16 04:27:58','2026-02-16 04:27:58'),(3,'general','من هم الأطباء في العيادة؟','Who are the doctors at the clinic?','يضم فريق أورا ديرما كلينك نخبة من أطباء الجلدية والتجميل بقيادة د. أسماء حمدي الحاصلة على دكتوراه وزمالة في الأمراض الجلدية والتجميل والليزر، بالإضافة إلى فريق متميز من الأخصائيات.','Aura Derma Clinic\'s team includes elite dermatology and aesthetics doctors led by Dr. Asmaa Hamdy, who holds a Doctorate and Fellowship in Dermatology, Aesthetics & Laser, along with a distinguished team of specialists.',3,'2026-02-16 04:27:58','2026-02-16 04:27:58'),(4,'general','هل العيادة مجهزة بأحدث الأجهزة؟','Is the clinic equipped with the latest devices?','نعم، عيادة أورا ديرما كلينك مجهزة بأحدث الأجهزة العالمية المعتمدة في مجال الجلدية والتجميل والليزر، ونحرص على تحديث أجهزتنا باستمرار لتقديم أفضل خدمة.','Yes, Aura Derma Clinic is equipped with the latest internationally certified devices in dermatology, aesthetics, and laser. We continuously update our equipment to provide the best service.',4,'2026-02-16 04:27:58','2026-02-16 04:27:58'),(5,'laser','كم عدد جلسات الليزر المطلوبة لإزالة الشعر؟','How many laser sessions are needed for hair removal?','يختلف عدد الجلسات حسب المنطقة ونوع البشرة ولون الشعر، لكن عادةً ما يتطلب الأمر من 6 إلى 10 جلسات للحصول على أفضل النتائج مع فترة فاصلة من 4 إلى 6 أسابيع بين كل جلسة.','The number of sessions varies depending on the area, skin type, and hair color. Typically, 6 to 10 sessions are needed for optimal results, with 4 to 6 weeks between each session.',1,'2026-02-16 04:27:58','2026-02-16 04:27:58'),(6,'laser','هل إزالة الشعر بالليزر مؤلمة؟','Is laser hair removal painful?','أجهزة الليزر الحديثة المستخدمة في العيادة مزودة بتقنية تبريد متطورة تقلل من الألم بشكل كبير. قد يشعر المريض بوخز خفيف يشبه لسعة المطاط ولكنه محتمل تماماً.','The modern laser devices used at our clinic are equipped with advanced cooling technology that significantly reduces pain. Patients may feel a slight tingling similar to a rubber band snap, which is completely tolerable.',2,'2026-02-16 04:27:58','2026-02-16 04:27:58'),(7,'laser','هل الليزر آمن لجميع أنواع البشرة؟','Is laser safe for all skin types?','نعم، نستخدم أجهزة ليزر متطورة مناسبة لجميع أنواع البشرة بما في ذلك البشرة الداكنة. يقوم الطبيب بضبط إعدادات الجهاز وفقاً لنوع بشرتك لضمان الأمان والفعالية.','Yes, we use advanced laser devices suitable for all skin types, including dark skin. The doctor adjusts the device settings according to your skin type to ensure safety and effectiveness.',3,'2026-02-16 04:27:58','2026-02-16 04:27:58'),(8,'laser','ما هي التعليمات قبل وبعد جلسة الليزر؟','What are the instructions before and after a laser session?','قبل الجلسة: تجنب التعرض للشمس وعدم إزالة الشعر بالشمع أو النتف لمدة أسبوعين. بعد الجلسة: استخدام واقي الشمس وكريم مرطب وتجنب الحرارة العالية والساونا لمدة 48 ساعة.','Before the session: Avoid sun exposure and do not wax or pluck hair for two weeks. After the session: Use sunscreen and moisturizer, and avoid high heat and saunas for 48 hours.',4,'2026-02-16 04:27:58','2026-02-16 04:27:58'),(9,'skin','ما الفرق بين البوتوكس والفيلر؟','What is the difference between Botox and Filler?','البوتوكس يعمل على إرخاء العضلات لتقليل التجاعيد الناتجة عن الحركة (مثل تجاعيد الجبهة وحول العينين)، بينما الفيلر يعمل على ملء الفراغات وإضافة حجم (مثل نفخ الشفاه وتحديد الفك).','Botox works by relaxing muscles to reduce wrinkles caused by movement (such as forehead and around-eye wrinkles), while Filler works by filling spaces and adding volume (such as lip augmentation and jawline definition).',1,'2026-02-16 04:27:58','2026-02-16 04:27:58'),(10,'skin','كم تدوم نتائج الهيدرافيشل؟','How long do HydraFacial results last?','تظهر نتائج الهيدرافيشل فوراً بعد الجلسة وتستمر من أسبوع إلى أسبوعين. ينصح بعمل جلسة شهرية للحفاظ على نضارة وصحة البشرة بشكل مستمر.','HydraFacial results are visible immediately after the session and last 1 to 2 weeks. Monthly sessions are recommended to maintain ongoing skin health and radiance.',2,'2026-02-16 04:27:58','2026-02-16 04:27:58'),(11,'skin','هل التقشير الكيميائي مناسب لجميع أنواع البشرة؟','Is chemical peeling suitable for all skin types?','يتوفر لدينا أنواع مختلفة من التقشير الكيميائي تناسب جميع أنواع البشرة. يقوم الطبيب بتقييم بشرتك واختيار النوع والتركيز المناسب لحالتك للحصول على أفضل النتائج.','We offer different types of chemical peels suitable for all skin types. The doctor evaluates your skin and selects the appropriate type and concentration for your condition to achieve the best results.',3,'2026-02-16 04:27:58','2026-02-16 04:27:58'),(12,'skin','ما هي جلسات البلازما PRP وما فوائدها؟','What is PRP therapy and what are its benefits?','البلازما الغنية بالصفائح الدموية (PRP) هي علاج يتم فيه سحب عينة دم من المريض وفصل البلازما ثم حقنها في البشرة أو فروة الرأس. تساعد في تجديد البشرة وعلاج تساقط الشعر وتحفيز إنتاج الكولاجين.','Platelet-Rich Plasma (PRP) is a treatment where a blood sample is drawn from the patient, the plasma is separated, then injected into the skin or scalp. It helps rejuvenate skin, treat hair loss, and stimulate collagen production.',4,'2026-02-16 04:27:58','2026-02-16 04:27:58'),(13,'booking','كيف يمكنني حجز موعد في العيادة؟','How can I book an appointment at the clinic?','يمكنك حجز موعد من خلال الاتصال على أرقام العيادة (01007729159 أو 0238244047)، أو عبر الواتساب، أو من خلال نموذج الحجز على موقعنا الإلكتروني.','You can book an appointment by calling the clinic numbers (01007729159 or 0238244047), via WhatsApp, or through the booking form on our website.',1,'2026-02-16 04:27:58','2026-02-16 04:27:58'),(14,'booking','هل يجب حجز موعد مسبق أم يمكن الحضور مباشرة؟','Do I need to book in advance or can I walk in?','ننصح بحجز موعد مسبق لضمان الحصول على الخدمة في الوقت المناسب وتجنب الانتظار. ومع ذلك، نرحب بالحالات الطارئة والزيارات المباشرة حسب توفر المواعيد.','We recommend booking in advance to ensure timely service and avoid waiting. However, we welcome emergency cases and walk-in visits based on appointment availability.',2,'2026-02-16 04:27:58','2026-02-16 04:27:58'),(15,'booking','هل يمكنني إلغاء أو تغيير موعدي؟','Can I cancel or reschedule my appointment?','نعم، يمكنك إلغاء أو تغيير موعدك بالتواصل معنا قبل الموعد بـ 24 ساعة على الأقل عبر الهاتف أو الواتساب.','Yes, you can cancel or reschedule your appointment by contacting us at least 24 hours before your appointment via phone or WhatsApp.',3,'2026-02-16 04:27:58','2026-02-16 04:27:58'),(16,'pricing','ما هي أسعار الخدمات في العيادة؟','What are the service prices at the clinic?','تختلف أسعار الخدمات حسب نوع العلاج والحالة الفردية لكل مريض. يتم تحديد التكلفة الدقيقة بعد الاستشارة والفحص الطبي. يسعدنا تقديم استشارة مبدئية لتحديد العلاج المناسب والتكلفة.','Service prices vary depending on the type of treatment and each patient\'s individual condition. The exact cost is determined after consultation and medical examination. We are happy to provide an initial consultation to determine the appropriate treatment and cost.',1,'2026-02-16 04:27:58','2026-02-16 04:27:58'),(17,'pricing','هل تتوفر عروض أو باقات مخفضة؟','Are there any offers or discounted packages?','نعم، نقدم عروضاً وباقات مخفضة بشكل دوري على مختلف الخدمات. تابعونا على وسائل التواصل الاجتماعي أو تواصلوا معنا للاطلاع على أحدث العروض المتاحة.','Yes, we regularly offer discounts and packages on various services. Follow us on social media or contact us to learn about the latest available offers.',2,'2026-02-16 04:27:58','2026-02-16 04:27:58'),(18,'pricing','هل يمكن الدفع بالتقسيط؟','Is installment payment available?','نعم، نوفر خيارات دفع مرنة تشمل التقسيط على بعض الخدمات والباقات العلاجية. يرجى الاستفسار عند الحجز عن خيارات الدفع المتاحة.','Yes, we offer flexible payment options including installments for certain services and treatment packages. Please inquire about available payment options when booking.',3,'2026-02-16 04:27:58','2026-02-16 04:27:58');
/*!40000 ALTER TABLE `faqs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gallery`
--

DROP TABLE IF EXISTS `gallery`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gallery` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category` varchar(255) NOT NULL DEFAULT 'clinic',
  `image_path` varchar(255) DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `caption_ar` varchar(255) DEFAULT NULL,
  `caption_en` varchar(255) DEFAULT NULL,
  `is_before_after` tinyint(1) NOT NULL DEFAULT 0,
  `before_image` varchar(255) DEFAULT NULL,
  `after_image` varchar(255) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_visible` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gallery`
--

LOCK TABLES `gallery` WRITE;
/*!40000 ALTER TABLE `gallery` DISABLE KEYS */;
INSERT INTO `gallery` VALUES (1,'clinic','https://images.unsplash.com/photo-1629909613654-28e377c37b09?w=800&q=80',NULL,'استقبال عيادة أورا ديرما كلينك','Aura Derma Clinic Reception',0,NULL,NULL,1,1,'2026-02-16 04:27:58','2026-02-16 17:13:15'),(2,'clinic','https://images.unsplash.com/photo-1631217868264-e5b90bb7e133?w=800&q=80',NULL,'غرفة الاستشارات والفحص الطبي','Consultation & Examination Room',0,NULL,NULL,2,1,'2026-02-16 04:27:58','2026-02-16 17:13:15'),(3,'clinic','https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=800&q=80',NULL,'غرفة العلاج والإجراءات التجميلية','Treatment & Cosmetic Procedures Room',0,NULL,NULL,3,1,'2026-02-16 04:27:58','2026-02-16 17:13:15'),(4,'equipment','https://images.unsplash.com/photo-1609840114035-3c981b782dfe?w=800&q=80',NULL,'جهاز الليزر المتطور لإزالة الشعر','Advanced Laser Hair Removal Device',0,NULL,NULL,6,1,'2026-02-16 04:27:58','2026-02-16 17:13:15'),(5,'equipment','https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=800&q=80',NULL,'جهاز الهيدرافيشل للعناية بالبشرة','HydraFacial Skincare Device',0,NULL,NULL,7,1,'2026-02-16 04:27:58','2026-02-16 17:13:15'),(6,'before-after','https://images.unsplash.com/photo-1616394584738-fc6e612e71b9?w=800&q=80',NULL,'نتائج علاج حب الشباب - قبل وبعد','Acne Treatment Results - Before & After',1,NULL,NULL,10,1,'2026-02-16 04:27:58','2026-02-16 17:13:15'),(7,'before-after','https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?w=800&q=80',NULL,'نتائج جلسات تفتيح البشرة - قبل وبعد','Skin Whitening Results - Before & After',1,NULL,NULL,11,1,'2026-02-16 04:27:58','2026-02-16 17:13:15'),(8,'team','https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?w=800&q=80',NULL,'فريق عيادة أورا ديرما كلينك الطبي','Aura Derma Clinic Medical Team',0,NULL,NULL,13,1,'2026-02-16 04:27:58','2026-02-16 17:13:15'),(9,'clinic','https://images.unsplash.com/photo-1666214280557-091e129d66e4?w=800&q=80',NULL,'ممر العيادة الأنيق','Elegant Clinic Hallway',0,NULL,NULL,4,1,'2026-02-16 17:13:15','2026-02-16 17:13:15'),(10,'clinic','https://images.unsplash.com/photo-1638202993928-7267aad84c31?w=800&q=80',NULL,'منطقة الانتظار المريحة','Comfortable Waiting Area',0,NULL,NULL,5,1,'2026-02-16 17:13:15','2026-02-16 17:13:15'),(11,'equipment','https://images.unsplash.com/photo-1551190822-a9ce113ac100?w=800&q=80',NULL,'أجهزة التشخيص المتقدمة','Advanced Diagnostic Equipment',0,NULL,NULL,8,1,'2026-02-16 17:13:15','2026-02-16 17:13:15'),(12,'equipment','https://images.unsplash.com/photo-1579684385127-1ef15d508118?w=800&q=80',NULL,'جهاز الفراكشنال ليزر','Fractional Laser Device',0,NULL,NULL,9,1,'2026-02-16 17:13:15','2026-02-16 17:13:15'),(13,'before-after','https://images.unsplash.com/photo-1512290923902-8a9f81dc236c?w=800&q=80',NULL,'نتائج علاج التصبغات - قبل وبعد','Pigmentation Treatment Results - Before & After',1,NULL,NULL,12,1,'2026-02-16 17:13:15','2026-02-16 17:13:15'),(14,'team','https://images.unsplash.com/photo-1559839734-2b71ea197ec2?w=800&q=80',NULL,'طبيبة الجلدية المتخصصة','Specialized Dermatologist',0,NULL,NULL,14,1,'2026-02-16 17:13:15','2026-02-16 17:13:15'),(15,'team','https://images.unsplash.com/photo-1594824476967-48c8b964ac31?w=800&q=80',NULL,'فريق التجميل والعناية بالبشرة','Aesthetics & Skincare Team',0,NULL,NULL,15,1,'2026-02-16 17:13:15','2026-02-16 17:13:15');
/*!40000 ALTER TABLE `gallery` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hero_slides`
--

DROP TABLE IF EXISTS `hero_slides`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hero_slides` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title_ar` varchar(255) DEFAULT NULL,
  `title_en` varchar(255) DEFAULT NULL,
  `subtitle_ar` varchar(255) DEFAULT NULL,
  `subtitle_en` varchar(255) DEFAULT NULL,
  `description_ar` text,
  `description_en` text,
  `button_text_ar` varchar(255) DEFAULT NULL,
  `button_text_en` varchar(255) DEFAULT NULL,
  `button_link` varchar(255) DEFAULT NULL,
  `image` varchar(255) NOT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hero_slides`
--

LOCK TABLES `hero_slides` WRITE;
/*!40000 ALTER TABLE `hero_slides` DISABLE KEYS */;
/*!40000 ALTER TABLE `hero_slides` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoice_items`
--

DROP TABLE IF EXISTS `invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoice_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `description_ar` varchar(255) DEFAULT NULL,
  `description_en` varchar(255) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_items_invoice_id_foreign` (`invoice_id`),
  CONSTRAINT `invoice_items_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoice_items`
--

LOCK TABLES `invoice_items` WRITE;
/*!40000 ALTER TABLE `invoice_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `invoice_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(255) NOT NULL,
  `invoice_date` date DEFAULT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `visit_id` bigint(20) unsigned DEFAULT NULL,
  `booking_id` bigint(20) unsigned DEFAULT NULL,
  `service_package_id` bigint(20) unsigned DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_code_id` bigint(20) unsigned DEFAULT NULL,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('paid','partial','unpaid','cancelled') NOT NULL DEFAULT 'unpaid',
  `notes` text,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoices_invoice_number_unique` (`invoice_number`),
  KEY `invoices_visit_id_foreign` (`visit_id`),
  KEY `invoices_service_package_id_foreign` (`service_package_id`),
  KEY `invoices_discount_code_id_foreign` (`discount_code_id`),
  KEY `invoices_created_by_foreign` (`created_by`),
  KEY `invoices_status_created_at_index` (`status`,`created_at`),
  KEY `invoices_patient_id_index` (`patient_id`),
  KEY `invoices_booking_id_index` (`booking_id`),
  CONSTRAINT `invoices_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoices_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoices_discount_code_id_foreign` FOREIGN KEY (`discount_code_id`) REFERENCES `discount_codes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoices_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoices_service_package_id_foreign` FOREIGN KEY (`service_package_id`) REFERENCES `service_packages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoices_visit_id_foreign` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leaves`
--

DROP TABLE IF EXISTS `leaves`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leaves` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `leave_type` enum('annual','sick','personal','unpaid') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` text,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leaves_user_id_foreign` (`user_id`),
  KEY `leaves_approved_by_foreign` (`approved_by`),
  CONSTRAINT `leaves_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leaves_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leaves`
--

LOCK TABLES `leaves` WRITE;
/*!40000 ALTER TABLE `leaves` DISABLE KEYS */;
INSERT INTO `leaves` VALUES (1,1,'sick','2026-01-01','2026-02-17',NULL,'pending',NULL,'2026-02-17 02:04:45','2026-02-17 02:04:45');
/*!40000 ALTER TABLE `leaves` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `medications`
--

DROP TABLE IF EXISTS `medications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `medications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `default_dosage` varchar(255) DEFAULT NULL,
  `default_frequency` varchar(255) DEFAULT NULL,
  `default_duration` varchar(255) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `medications`
--

LOCK TABLES `medications` WRITE;
/*!40000 ALTER TABLE `medications` DISABLE KEYS */;
/*!40000 ALTER TABLE `medications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sender_id` bigint(20) unsigned NOT NULL,
  `receiver_id` bigint(20) unsigned NOT NULL,
  `body` text,
  `attachment_path` varchar(255) DEFAULT NULL,
  `attachment_name` varchar(255) DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `messages_sender_id_receiver_id_created_at_index` (`sender_id`,`receiver_id`,`created_at`),
  KEY `messages_receiver_id_read_at_index` (`receiver_id`,`read_at`),
  CONSTRAINT `messages_receiver_id_foreign` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=79 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2024_01_01_000001_create_service_categories_table',1),(5,'2024_01_01_000002_create_services_table',1),(6,'2024_01_01_000003_create_service_gallery_table',1),(7,'2024_01_01_000004_create_service_faqs_table',1),(8,'2024_01_01_000005_create_doctors_table',1),(9,'2024_01_01_000006_create_post_categories_table',1),(10,'2024_01_01_000007_create_posts_table',1),(11,'2024_01_01_000008_create_tags_table',1),(12,'2024_01_01_000009_create_offers_table',1),(13,'2024_01_01_000010_create_bookings_table',1),(14,'2024_01_01_000011_create_gallery_table',1),(15,'2024_01_01_000012_create_testimonials_table',1),(16,'2024_01_01_000013_create_settings_table',1),(17,'2024_01_01_000014_create_faqs_table',1),(18,'2024_01_01_000015_create_pages_table',1),(19,'2024_01_01_000016_create_contact_messages_table',1),(20,'2026_02_16_050253_add_role_and_active_to_users_table',1),(21,'2026_02_16_050258_create_activity_logs_table',1),(22,'2026_02_16_060547_create_roles_table',1),(23,'2026_02_16_060548_change_users_role_to_role_id',1),(24,'2026_02_16_211536_add_is_read_to_bookings_table',2),(25,'2026_02_17_000001_add_clinic_fields_to_services_table',3),(26,'2026_02_17_000002_add_clinic_fields_to_doctors_table',3),(27,'2026_02_17_000003_create_patients_table',3),(28,'2026_02_17_000004_create_patient_photos_table',3),(29,'2026_02_17_000005_create_payment_methods_table',3),(30,'2026_02_17_000006_create_medications_table',3),(31,'2026_02_17_000007_create_shifts_table',3),(32,'2026_02_17_000008_create_expense_categories_table',3),(33,'2026_02_17_000009_create_expense_items_table',3),(34,'2026_02_17_000010_create_supplies_table',3),(35,'2026_02_17_000011_create_discount_codes_table',3),(36,'2026_02_17_000012_create_doctor_schedules_table',3),(37,'2026_02_17_000013_create_doctor_vacations_table',3),(38,'2026_02_17_000014_create_doctor_service_rates_table',3),(39,'2026_02_17_000015_create_service_packages_table',3),(40,'2026_02_17_000016_create_visits_table',3),(41,'2026_02_17_000017_create_visit_photos_table',3),(42,'2026_02_17_000018_create_prescriptions_table',3),(43,'2026_02_17_000019_create_prescription_items_table',3),(44,'2026_02_17_000020_create_invoices_table',3),(45,'2026_02_17_000021_create_invoice_items_table',3),(46,'2026_02_17_000022_create_payments_table',3),(47,'2026_02_17_000023_create_discount_usage_table',3),(48,'2026_02_17_000024_create_expenses_table',3),(49,'2026_02_17_000025_create_employee_shifts_table',3),(50,'2026_02_17_000026_create_attendances_table',3),(51,'2026_02_17_000027_create_leaves_table',3),(52,'2026_02_17_000028_create_supply_transactions_table',3),(53,'2026_02_17_000029_create_service_supplies_table',3),(54,'2026_02_17_000030_add_commission_fields_to_visits_table',4),(55,'2026_02_17_000031_add_invoice_date_to_invoices_table',4),(56,'2026_02_17_120643_make_visit_id_nullable_in_prescriptions_table',5),(57,'2026_02_17_161924_create_notifications_table',6),(58,'2026_02_17_200000_create_treatment_plans_table',7),(59,'2026_02_18_000001_redesign_bookings_table',7),(60,'2026_02_18_000002_create_booking_services_table',7),(61,'2026_02_18_000003_create_booking_appointments_table',7),(62,'2026_02_18_000004_add_booking_fields_to_visits_table',7),(63,'2026_02_18_000005_add_booking_id_to_invoices_table',7),(64,'2026_02_18_100001_create_hero_slides_table',8),(65,'2026_02_18_200001_create_seo_pages_table',9),(66,'2026_02_19_000001_create_messages_table',10),(67,'2026_02_19_000002_add_last_seen_at_to_users_table',10),(68,'2026_02_22_000001_add_comprehensive_fields_to_activity_logs',11),(69,'2026_02_23_000001_add_booking_type_and_doctor_consultation_fields',12),(70,'2026_02_23_100001_add_visibility_fields_to_services_table',13),(71,'2026_02_23_100000_make_booking_services_service_id_nullable',14),(72,'2026_02_23_184216_add_retouch_support',15),(73,'2026_03_01_000001_add_doctor_type_to_doctors_table',16),(74,'2026_03_01_000002_add_medical_fee_to_services_table',16),(75,'2026_03_01_000003_create_booking_consents_table',16),(76,'2026_03_01_000004_add_pricing_settings',16),(77,'2026_03_01_000005_add_followup_commission_to_doctors_table',17),(78,'2026_03_03_000001_create_doctor_payouts_tables',18);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint(20) unsigned NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES ('0482ed86-dc3e-46f1-b86a-8439efdf7810','App\\Notifications\\NewVisitNotification','App\\Models\\User',18,'{\"type\":\"new_visit\",\"visit_id\":211,\"patient_name\":\"\\u0646\\u0648\\u0631\\u0627 \\u0623\\u062d\\u0645\\u062f \\u0645\\u062d\\u0645\\u062f\",\"service_name\":\"Acne Treatment\",\"visit_type\":\"session\",\"visit_date\":\"2026-02-21\",\"message\":\"New session visit for \\u0646\\u0648\\u0631\\u0627 \\u0623\\u062d\\u0645\\u062f \\u0645\\u062d\\u0645\\u062f\"}','2026-02-21 21:51:01','2026-02-21 21:50:30','2026-02-21 21:51:01'),('35a5a445-aa4a-4417-9abe-4e8ab44962b9','App\\Notifications\\NewBookingNotification','App\\Models\\User',5,'{\"type\":\"new_booking\",\"booking_id\":13,\"patient_name\":\"\\u0623\\u062d\\u0645\\u062f \\u062e\\u0644\\u064a\\u0644\",\"service_name\":\"Acne Treatment\",\"preferred_date\":\"2026-02-18\",\"preferred_time\":\"11:30\",\"message\":\"New booking from \\u0623\\u062d\\u0645\\u062f \\u062e\\u0644\\u064a\\u0644\"}','2026-03-01 09:48:17','2026-02-18 12:33:33','2026-03-01 09:48:17'),('3e7ae1e8-0f18-4ee8-902e-e56c1b02639e','App\\Notifications\\NewBookingNotification','App\\Models\\User',18,'{\"type\":\"new_booking\",\"booking_id\":16,\"patient_name\":\"\\u0627\\u062d\\u0645\\u062f \\u062e\\u0644\\u064a\\u0644\",\"service_name\":\"Deep Skin Cleansing\",\"preferred_date\":\"2026-02-22\",\"preferred_time\":\"12:00\",\"message\":\"New booking from \\u0627\\u062d\\u0645\\u062f \\u062e\\u0644\\u064a\\u0644\"}',NULL,'2026-02-22 21:46:08','2026-02-22 21:46:08'),('45f07746-f377-468b-bb6b-a03fb5992a72','App\\Notifications\\NewVisitNotification','App\\Models\\User',5,'{\"type\":\"new_visit\",\"visit_id\":155,\"patient_name\":\"\\u0647\\u0646\\u062f \\u0645\\u062d\\u0645\\u062f \\u0623\\u0646\\u0648\\u0631\",\"service_name\":\"Consultation\",\"visit_type\":\"consultation\",\"visit_date\":\"2026-02-17\",\"message\":\"New consultation visit for \\u0647\\u0646\\u062f \\u0645\\u062d\\u0645\\u062f \\u0623\\u0646\\u0648\\u0631\"}','2026-02-17 16:49:56','2026-02-17 16:42:43','2026-02-17 16:49:56'),('46703b52-1828-43c7-a46f-5ccd69d67500','App\\Notifications\\NewVisitNotification','App\\Models\\User',5,'{\"type\":\"new_visit\",\"visit_id\":156,\"patient_name\":\"\\u0623\\u062d\\u0645\\u062f \\u0645\\u062d\\u0645\\u062f \\u0639\\u0628\\u062f\\u0627\\u0644\\u0639\\u0632\\u064a\\u0632\",\"service_name\":\"Pigmentation Treatment\",\"visit_type\":\"session\",\"visit_date\":\"2026-02-17\",\"message\":\"New session visit for \\u0623\\u062d\\u0645\\u062f \\u0645\\u062d\\u0645\\u062f \\u0639\\u0628\\u062f\\u0627\\u0644\\u0639\\u0632\\u064a\\u0632\"}','2026-02-17 16:48:32','2026-02-17 16:48:14','2026-02-17 16:48:32'),('47ce4469-a4e7-4cb3-a033-c2f0e8ce7b14','App\\Notifications\\NewVisitNotification','App\\Models\\User',18,'{\"type\":\"new_visit\",\"visit_id\":216,\"patient_name\":\"ahmed\",\"service_name\":\"Mifill Filler\",\"visit_type\":\"session\",\"visit_date\":\"2026-03-08\",\"message\":\"New session visit for ahmed\"}',NULL,'2026-03-08 12:37:53','2026-03-08 12:37:53'),('68fcea50-c402-4c68-ae53-27929ed5c04d','App\\Notifications\\NewBookingNotification','App\\Models\\User',5,'{\"type\":\"new_booking\",\"booking_id\":10,\"patient_name\":\"fdsf\",\"service_name\":\"Excimer Laser\",\"preferred_date\":\"2026-02-20\",\"preferred_time\":\"10:30\",\"message\":\"New booking from fdsf\"}','2026-02-19 21:47:09','2026-02-18 06:48:14','2026-02-19 21:47:09'),('81089842-6859-45be-bd7a-8136996bfb5d','App\\Notifications\\NewVisitNotification','App\\Models\\User',18,'{\"type\":\"new_visit\",\"visit_id\":217,\"patient_name\":\"ahmed\",\"service_name\":\"Mifill Filler\",\"visit_type\":\"follow_up\",\"visit_date\":\"2026-03-08\",\"message\":\"New follow_up visit for ahmed\"}',NULL,'2026-03-08 12:39:31','2026-03-08 12:39:31'),('88d405dc-2bd7-4128-8742-9714a9c90073','App\\Notifications\\NewVisitNotification','App\\Models\\User',5,'{\"type\":\"new_visit\",\"visit_id\":153,\"patient_name\":\"\\u062f\\u064a\\u0646\\u0627 \\u0639\\u0627\\u062f\\u0644 \\u062d\\u0633\\u064a\\u0646\",\"service_name\":\"Acne Treatment\",\"visit_type\":\"session\",\"visit_date\":\"2026-02-17\",\"message\":\"New session visit for \\u062f\\u064a\\u0646\\u0627 \\u0639\\u0627\\u062f\\u0644 \\u062d\\u0633\\u064a\\u0646\"}','2026-02-17 15:42:19','2026-02-17 15:42:03','2026-02-17 15:42:19'),('ab05a2ba-1f2a-4cd5-af5d-2908eec37f3a','App\\Notifications\\NewVisitNotification','App\\Models\\User',18,'{\"type\":\"new_visit\",\"visit_id\":209,\"patient_name\":\"jour ahmed khaleel\",\"service_name\":\"Consultation\",\"visit_type\":\"consultation\",\"visit_date\":\"2026-02-21\",\"message\":\"New consultation visit for jour ahmed khaleel\"}','2026-02-21 19:53:39','2026-02-21 19:43:00','2026-02-21 19:53:39'),('b5b9a4c1-d716-4b12-8e97-82dda41d4ac3','App\\Notifications\\NewBookingNotification','App\\Models\\User',5,'{\"type\":\"new_booking\",\"booking_id\":17,\"patient_name\":\"\\u0645\\u062d\\u0645\\u062f \\u062c\\u0645\\u0627\\u0644\",\"service_name\":\"Pigmentation Treatment\",\"preferred_date\":\"2026-03-07\",\"preferred_time\":\"15:30\",\"message\":\"New booking from \\u0645\\u062d\\u0645\\u062f \\u062c\\u0645\\u0627\\u0644\"}','2026-02-28 21:04:38','2026-02-28 20:23:32','2026-02-28 21:04:38'),('b62ba470-f5c2-4d77-963f-dad07fdbc9f6','App\\Notifications\\NewBookingNotification','App\\Models\\User',5,'{\"type\":\"new_booking\",\"booking_id\":19,\"patient_name\":\"\\u0627\\u062d\\u0645\\u062f \\u062e\\u0644\\u064a\\u0644\",\"service_name\":\"General\",\"preferred_date\":\"2026-03-07\",\"preferred_time\":\"11:30\",\"message\":\"New booking from \\u0627\\u062d\\u0645\\u062f \\u062e\\u0644\\u064a\\u0644\"}','2026-03-01 09:08:14','2026-03-01 09:03:30','2026-03-01 09:08:14'),('ba3adf1f-bdb7-4287-8edc-6ff4763c942a','App\\Notifications\\NewVisitNotification','App\\Models\\User',5,'{\"type\":\"new_visit\",\"visit_id\":152,\"patient_name\":\"\\u0641\\u0627\\u0637\\u0645\\u0629 \\u0645\\u062d\\u0645\\u0648\\u062f \\u0625\\u0628\\u0631\\u0627\\u0647\\u064a\\u0645\",\"service_name\":\"Consultation\",\"visit_type\":\"consultation\",\"visit_date\":\"2026-02-17\",\"message\":\"New consultation visit for \\u0641\\u0627\\u0637\\u0645\\u0629 \\u0645\\u062d\\u0645\\u0648\\u062f \\u0625\\u0628\\u0631\\u0627\\u0647\\u064a\\u0645\"}','2026-02-17 15:32:33','2026-02-17 15:32:23','2026-02-17 15:32:33'),('c15c9f20-7c46-489a-81b8-0d487388e969','App\\Notifications\\NewBookingNotification','App\\Models\\User',5,'{\"type\":\"new_booking\",\"booking_id\":12,\"patient_name\":\"ahmed\",\"service_name\":\"Acne Treatment\",\"preferred_date\":\"2026-02-18\",\"preferred_time\":\"13:30\",\"message\":\"New booking from ahmed\"}','2026-03-01 10:02:45','2026-02-18 07:39:56','2026-03-01 10:02:45'),('c6da94c3-ad40-446e-9005-132ed9207fb0','App\\Notifications\\NewBookingNotification','App\\Models\\User',18,'{\"type\":\"new_booking\",\"booking_id\":14,\"patient_name\":\"jour ahmed khaleel\",\"service_name\":\"General\",\"preferred_date\":\"2026-02-21\",\"preferred_time\":\"09:00\",\"message\":\"New booking from jour ahmed khaleel\"}',NULL,'2026-02-21 19:36:29','2026-02-21 19:36:29'),('d81588bd-0262-4d63-b3b0-e81ae4643842','App\\Notifications\\NewVisitNotification','App\\Models\\User',5,'{\"type\":\"new_visit\",\"visit_id\":154,\"patient_name\":\"\\u0623\\u062d\\u0645\\u062f \\u0645\\u062d\\u0645\\u062f \\u0639\\u0628\\u062f\\u0627\\u0644\\u0639\\u0632\\u064a\\u0632\",\"service_name\":\"Consultation\",\"visit_type\":\"consultation\",\"visit_date\":\"2026-02-17\",\"message\":\"New consultation visit for \\u0623\\u062d\\u0645\\u062f \\u0645\\u062d\\u0645\\u062f \\u0639\\u0628\\u062f\\u0627\\u0644\\u0639\\u0632\\u064a\\u0632\"}','2026-02-17 16:36:17','2026-02-17 16:34:35','2026-02-17 16:36:17'),('ec49904d-714b-4535-8014-1ce61af4d008','App\\Notifications\\NewBookingNotification','App\\Models\\User',18,'{\"type\":\"new_booking\",\"booking_id\":15,\"patient_name\":\"\\u0634\\u0627\\u0648\\u0631\\u064a\",\"service_name\":\"Acne Treatment\",\"preferred_date\":\"2026-02-21\",\"preferred_time\":\"12:00\",\"message\":\"New booking from \\u0634\\u0627\\u0648\\u0631\\u064a\"}',NULL,'2026-02-21 21:31:50','2026-02-21 21:31:50');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `offers`
--

DROP TABLE IF EXISTS `offers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `offers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `service_id` bigint(20) unsigned DEFAULT NULL,
  `title_ar` varchar(255) NOT NULL,
  `title_en` varchar(255) NOT NULL,
  `description_ar` text,
  `description_en` text,
  `image` varchar(255) DEFAULT NULL,
  `original_price` decimal(10,2) DEFAULT NULL,
  `offer_price` decimal(10,2) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('active','expired','draft') NOT NULL DEFAULT 'draft',
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `offers_service_id_foreign` (`service_id`),
  CONSTRAINT `offers_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `offers`
--

LOCK TABLES `offers` WRITE;
/*!40000 ALTER TABLE `offers` DISABLE KEYS */;
INSERT INTO `offers` VALUES (1,1,'عرض علاج حب الشباب الشامل','Comprehensive Acne Treatment Offer','احصلي على بشرة صافية ونقية مع باقة علاج حب الشباب الشاملة التي تتضمن ٣ جلسات تنظيف عميق وتقشير كيميائي مع متابعة طبية كاملة.','Get clear and radiant skin with our comprehensive acne treatment package that includes 3 deep cleansing and chemical peeling sessions with full medical follow-up.','https://images.unsplash.com/photo-1616394584738-fc6e612e71b9?w=800&q=80',1500.00,999.00,'2026-02-18','2026-05-18','active',1,'2026-02-16 04:27:58','2026-02-18 19:13:33'),(2,2,'باقة الليزر المتكاملة','Complete Laser Package','باقة إزالة الشعر بالليزر لكامل الجسم بأحدث أجهزة الليزر المعتمدة عالمياً. تشمل الباقة ٦ جلسات مع استشارة مجانية قبل البدء.','Full body laser hair removal package using the latest internationally certified laser devices. The package includes 6 sessions with a free consultation before starting.','https://images.unsplash.com/photo-1598524374912-6b0b0bab3da4?w=800&q=80',3000.00,2499.00,'2026-02-18','2026-05-18','active',2,'2026-02-16 04:27:58','2026-02-18 19:13:33'),(3,3,'عرض نضارة البشرة','Skin Radiance Offer','استعيدي نضارة بشرتك مع باقة الهيدرافيشل والبلازما الغنية بالصفائح الدموية. تتضمن الباقة ٣ جلسات هيدرافيشل وجلسة بلازما PRP مع كريم عناية مجاناً.','Restore your skin radiance with our HydraFacial and PRP package. The package includes 3 HydraFacial sessions and 1 PRP session with a free skincare cream.','https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?w=800&q=80',2000.00,1499.00,'2026-02-18','2026-05-18','active',3,'2026-02-16 04:27:58','2026-02-18 19:13:33');
/*!40000 ALTER TABLE `offers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pages`
--

DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) NOT NULL,
  `title_ar` varchar(255) NOT NULL,
  `title_en` varchar(255) NOT NULL,
  `content_ar` longtext,
  `content_en` longtext,
  `seo_title_ar` varchar(255) DEFAULT NULL,
  `seo_title_en` varchar(255) DEFAULT NULL,
  `seo_desc_ar` text,
  `seo_desc_en` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pages_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pages`
--

LOCK TABLES `pages` WRITE;
/*!40000 ALTER TABLE `pages` DISABLE KEYS */;
INSERT INTO `pages` VALUES (1,'about','من نحن','About Us','<p>أورا ديرما كلينك هي عيادة متخصصة في الأمراض الجلدية والتجميل والليزر، تأسست على يد د. أسماء حمدي الحاصلة على دكتوراه وزمالة الأمراض الجلدية والتجميل والليزر. نقدم في عيادتنا خدمات طبية وتجميلية متكاملة باستخدام أحدث الأجهزة والتقنيات العالمية المعتمدة.</p>\n<p>يضم فريقنا الطبي نخبة من أطباء الجلدية والتجميل ذوي الخبرة والكفاءة العالية، ونسعى دائماً لتقديم أفضل رعاية صحية لمرضانا في بيئة مريحة وآمنة. نؤمن بأن كل مريض يستحق عناية خاصة، لذلك نقدم بروتوكولات علاجية مخصصة تناسب كل حالة على حدة.</p>\n<p>تقع عيادتنا في موقع متميز في كايرو ميديكال سنتر بمدينة ٦ أكتوبر، ونعمل يومياً من الساعة 10 صباحاً حتى 10 مساءً لخدمة مرضانا.</p>','<p>Aura Derma Clinic is a specialized dermatology, aesthetics, and laser clinic founded by Dr. Asmaa Hamdy, who holds a Doctorate and Fellowship in Dermatology, Aesthetics & Laser. We provide comprehensive medical and cosmetic services using the latest internationally certified devices and technologies.</p>\n<p>Our medical team includes elite dermatology and aesthetics doctors with extensive experience and high competence. We always strive to provide the best healthcare for our patients in a comfortable and safe environment. We believe every patient deserves special care, so we provide customized treatment protocols tailored to each individual case.</p>\n<p>Our clinic is conveniently located at Cairo Medical Center in 6th of October City, and we operate daily from 10:00 AM to 10:00 PM to serve our patients.</p>','من نحن - أورا ديرما كلينك','About Us - Aura Derma Clinic','تعرف على عيادة أورا ديرما كلينك المتخصصة في الأمراض الجلدية والتجميل والليزر في ٦ أكتوبر.','Learn about Aura Derma Clinic, specialized in dermatology, aesthetics, and laser in 6th of October City.','2026-02-16 04:27:58','2026-02-16 04:27:58'),(2,'privacy-policy','سياسة الخصوصية','Privacy Policy','<h3>مقدمة</h3>\n<p>تلتزم عيادة أورا ديرما كلينك بحماية خصوصية بيانات مرضاها وزوار موقعها الإلكتروني. توضح هذه السياسة كيفية جمع واستخدام وحماية بياناتكم الشخصية.</p>\n<h3>البيانات التي نجمعها</h3>\n<p>نقوم بجمع البيانات التالية: الاسم، رقم الهاتف، البريد الإلكتروني، والمعلومات الطبية اللازمة لتقديم الخدمة العلاجية. يتم جمع هذه البيانات عند حجز موعد أو ملء نموذج التواصل على الموقع.</p>\n<h3>استخدام البيانات</h3>\n<p>نستخدم بياناتكم لأغراض التواصل معكم بشأن المواعيد والخدمات، وتحسين جودة خدماتنا، ولن يتم مشاركة بياناتكم مع أي طرف ثالث إلا بموافقتكم أو حسب ما يقتضيه القانون.</p>\n<h3>حماية البيانات</h3>\n<p>نتخذ جميع الإجراءات التقنية والتنظيمية اللازمة لحماية بياناتكم الشخصية من الوصول غير المصرح به أو الاستخدام غير المشروع.</p>','<h3>Introduction</h3>\n<p>Aura Derma Clinic is committed to protecting the privacy of its patients\' and website visitors\' data. This policy explains how we collect, use, and protect your personal data.</p>\n<h3>Data We Collect</h3>\n<p>We collect the following data: name, phone number, email address, and medical information necessary for providing treatment services. This data is collected when booking an appointment or filling out the contact form on our website.</p>\n<h3>Data Usage</h3>\n<p>We use your data for contacting you regarding appointments and services, and improving the quality of our services. Your data will not be shared with any third party without your consent or as required by law.</p>\n<h3>Data Protection</h3>\n<p>We take all necessary technical and organizational measures to protect your personal data from unauthorized access or misuse.</p>','سياسة الخصوصية - أورا ديرما كلينك','Privacy Policy - Aura Derma Clinic','سياسة الخصوصية وحماية البيانات في عيادة أورا ديرما كلينك.','Privacy policy and data protection at Aura Derma Clinic.','2026-02-16 04:27:58','2026-02-16 04:27:58'),(3,'terms-of-use','شروط الاستخدام','Terms of Use','<h3>مقدمة</h3>\n<p>باستخدامك لموقع أورا ديرما كلينك الإلكتروني، فإنك توافق على الالتزام بهذه الشروط والأحكام. يرجى قراءتها بعناية قبل استخدام الموقع.</p>\n<h3>المحتوى الطبي</h3>\n<p>المعلومات المقدمة على هذا الموقع هي لأغراض تثقيفية وتعريفية فقط ولا تغني عن الاستشارة الطبية المتخصصة. يجب مراجعة الطبيب المختص للحصول على تشخيص وعلاج مناسب لحالتك.</p>\n<h3>حقوق الملكية الفكرية</h3>\n<p>جميع المحتويات المنشورة على هذا الموقع بما في ذلك النصوص والصور والشعارات هي ملك لعيادة أورا ديرما كلينك ومحمية بموجب قوانين حقوق الملكية الفكرية.</p>\n<h3>تعديل الشروط</h3>\n<p>تحتفظ عيادة أورا ديرما كلينك بحق تعديل هذه الشروط في أي وقت. ننصح بمراجعة هذه الصفحة بشكل دوري للاطلاع على أي تحديثات.</p>','<h3>Introduction</h3>\n<p>By using the Aura Derma Clinic website, you agree to comply with these terms and conditions. Please read them carefully before using the website.</p>\n<h3>Medical Content</h3>\n<p>Information provided on this website is for educational and informational purposes only and does not replace specialized medical consultation. You should consult a specialist doctor for proper diagnosis and treatment of your condition.</p>\n<h3>Intellectual Property Rights</h3>\n<p>All content published on this website, including text, images, and logos, is the property of Aura Derma Clinic and is protected under intellectual property laws.</p>\n<h3>Terms Modification</h3>\n<p>Aura Derma Clinic reserves the right to modify these terms at any time. We recommend reviewing this page periodically for any updates.</p>','شروط الاستخدام - أورا ديرما كلينك','Terms of Use - Aura Derma Clinic','شروط وأحكام استخدام موقع عيادة أورا ديرما كلينك.','Terms and conditions of use for Aura Derma Clinic website.','2026-02-16 04:27:58','2026-02-16 04:27:58'),(4,'booking-policy','سياسة الحجز','Booking Policy','<h3>حجز المواعيد</h3>\n<p>يمكنكم حجز المواعيد من خلال الاتصال الهاتفي أو الواتساب أو عبر نموذج الحجز على الموقع الإلكتروني. سيتم التأكيد على الموعد خلال ساعات العمل.</p>\n<h3>الإلغاء وإعادة الجدولة</h3>\n<p>في حالة الرغبة في إلغاء أو تغيير الموعد، يرجى إبلاغنا قبل 24 ساعة على الأقل من الموعد المحدد. الإلغاء المتكرر دون إخطار مسبق قد يؤثر على أولوية الحجز مستقبلاً.</p>\n<h3>التأخير عن الموعد</h3>\n<p>نرجو الحضور قبل الموعد بـ 10 دقائق على الأقل. في حالة التأخير لأكثر من 15 دقيقة، قد يتم تأجيل الموعد حسب توفر المواعيد.</p>\n<h3>الاستشارة الأولى</h3>\n<p>في الزيارة الأولى، يقوم الطبيب بتقييم حالتك بشكل شامل ووضع خطة علاجية مخصصة. يرجى إحضار أي تقارير طبية سابقة ذات صلة.</p>','<h3>Appointment Booking</h3>\n<p>You can book appointments by phone, WhatsApp, or through the booking form on our website. Appointments will be confirmed during working hours.</p>\n<h3>Cancellation and Rescheduling</h3>\n<p>If you wish to cancel or reschedule, please notify us at least 24 hours before the scheduled appointment. Frequent cancellations without prior notice may affect future booking priority.</p>\n<h3>Late Arrival</h3>\n<p>Please arrive at least 10 minutes before your appointment. Delays of more than 15 minutes may result in rescheduling based on availability.</p>\n<h3>Initial Consultation</h3>\n<p>During the first visit, the doctor will comprehensively evaluate your condition and develop a customized treatment plan. Please bring any relevant previous medical reports.</p>','سياسة الحجز - أورا ديرما كلينك','Booking Policy - Aura Derma Clinic','تعرف على سياسة حجز المواعيد في عيادة أورا ديرما كلينك.','Learn about the appointment booking policy at Aura Derma Clinic.','2026-02-16 04:27:58','2026-02-16 04:27:58');
/*!40000 ALTER TABLE `pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patient_photos`
--

DROP TABLE IF EXISTS `patient_photos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patient_photos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` bigint(20) unsigned NOT NULL,
  `photo_path` varchar(255) NOT NULL,
  `caption` varchar(255) DEFAULT NULL,
  `taken_at` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_photos_patient_id_foreign` (`patient_id`),
  CONSTRAINT `patient_photos_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_photos`
--

LOCK TABLES `patient_photos` WRITE;
/*!40000 ALTER TABLE `patient_photos` DISABLE KEYS */;
/*!40000 ALTER TABLE `patient_photos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patients`
--

DROP TABLE IF EXISTS `patients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `file_number` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `phone2` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female') DEFAULT NULL,
  `nationality` varchar(255) DEFAULT NULL,
  `address` text,
  `occupation` varchar(255) DEFAULT NULL,
  `referral_source` enum('walk_in','social_media','google','friend','doctor','advertisement','other') DEFAULT NULL,
  `referred_by` varchar(255) DEFAULT NULL,
  `medical_notes` text,
  `photo` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `patients_file_number_unique` (`file_number`),
  KEY `patients_phone_index` (`phone`),
  KEY `patients_full_name_index` (`full_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patients`
--

LOCK TABLES `patients` WRITE;
/*!40000 ALTER TABLE `patients` DISABLE KEYS */;
/*!40000 ALTER TABLE `patients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_methods`
--

DROP TABLE IF EXISTS `payment_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_methods` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name_ar` varchar(255) NOT NULL,
  `name_en` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_methods`
--

LOCK TABLES `payment_methods` WRITE;
/*!40000 ALTER TABLE `payment_methods` DISABLE KEYS */;
INSERT INTO `payment_methods` VALUES (1,'نقدي','Cash',1,1,'2026-02-17 01:45:37','2026-02-17 01:45:37'),(2,'فيزا','Visa',1,2,'2026-02-17 01:45:37','2026-02-17 01:45:37'),(3,'إنستاباي','Instapay',1,3,'2026-02-17 01:45:37','2026-02-17 01:45:37'),(4,'تحويل بنكي','Bank Transfer',1,4,'2026-02-17 01:45:37','2026-02-17 01:45:37'),(5,'محفظة إلكترونية','E-Wallet',1,5,'2026-02-17 01:45:37','2026-02-17 01:45:37');
/*!40000 ALTER TABLE `payment_methods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `payment_method_id` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `reference_number` varchar(255) DEFAULT NULL,
  `notes` text,
  `received_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payments_invoice_id_foreign` (`invoice_id`),
  KEY `payments_patient_id_foreign` (`patient_id`),
  KEY `payments_payment_method_id_foreign` (`payment_method_id`),
  KEY `payments_received_by_foreign` (`received_by`),
  KEY `payments_payment_date_index` (`payment_date`),
  CONSTRAINT `payments_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_payment_method_id_foreign` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_received_by_foreign` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `post_categories`
--

DROP TABLE IF EXISTS `post_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `post_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name_ar` varchar(255) NOT NULL,
  `name_en` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description_ar` text,
  `description_en` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `post_categories_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `post_categories`
--

LOCK TABLES `post_categories` WRITE;
/*!40000 ALTER TABLE `post_categories` DISABLE KEYS */;
INSERT INTO `post_categories` VALUES (1,'نصائح العناية بالبشرة','Skincare Tips','skincare-tips','مقالات ونصائح متخصصة للعناية بالبشرة والحفاظ على صحتها ونضارتها.','Specialized articles and tips for skincare, maintaining skin health and radiance.','2026-02-16 04:27:58','2026-02-16 04:27:58'),(2,'أحدث العلاجات والتقنيات','Latest Treatments','latest-treatments','تعرف على أحدث العلاجات والتقنيات في عالم الجلدية والتجميل.','Discover the latest treatments and technologies in the world of dermatology and aesthetics.','2026-02-16 04:27:58','2026-02-16 04:27:58'),(3,'توعية صحية','Health Awareness','health-awareness','مقالات توعوية عن الأمراض الجلدية وطرق الوقاية والعلاج.','Awareness articles about skin diseases, prevention methods, and treatments.','2026-02-16 04:27:58','2026-02-16 04:27:58'),(4,'أسئلة وأجوبة','Q&A','qa','إجابات على الأسئلة الأكثر شيوعاً حول خدماتنا وعلاجات البشرة.','Answers to the most common questions about our services and skin treatments.','2026-02-16 04:27:58','2026-02-16 04:27:58');
/*!40000 ALTER TABLE `post_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `post_tag`
--

DROP TABLE IF EXISTS `post_tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `post_tag` (
  `post_id` bigint(20) unsigned NOT NULL,
  `tag_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`post_id`,`tag_id`),
  KEY `post_tag_tag_id_foreign` (`tag_id`),
  CONSTRAINT `post_tag_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `post_tag_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `post_tag`
--

LOCK TABLES `post_tag` WRITE;
/*!40000 ALTER TABLE `post_tag` DISABLE KEYS */;
/*!40000 ALTER TABLE `post_tag` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `posts`
--

DROP TABLE IF EXISTS `posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `posts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `author_id` bigint(20) unsigned DEFAULT NULL,
  `title_ar` varchar(255) NOT NULL,
  `title_en` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `excerpt_ar` text,
  `excerpt_en` text,
  `content_ar` longtext NOT NULL,
  `content_en` longtext NOT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `seo_title_ar` varchar(255) DEFAULT NULL,
  `seo_title_en` varchar(255) DEFAULT NULL,
  `seo_desc_ar` text,
  `seo_desc_en` text,
  `status` enum('draft','published','scheduled') NOT NULL DEFAULT 'draft',
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `posts_slug_unique` (`slug`),
  KEY `posts_category_id_foreign` (`category_id`),
  KEY `posts_author_id_foreign` (`author_id`),
  CONSTRAINT `posts_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `doctors` (`id`) ON DELETE SET NULL,
  CONSTRAINT `posts_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `post_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `posts`
--

LOCK TABLES `posts` WRITE;
/*!40000 ALTER TABLE `posts` DISABLE KEYS */;
INSERT INTO `posts` VALUES (1,1,1,'الروتين اليومي المثالي للعناية بالبشرة: دليلك الشامل','The Perfect Daily Skincare Routine: Your Complete Guide','perfect-daily-skincare-routine','تعرفي على الخطوات الأساسية لروتين العناية بالبشرة اليومي الذي يناسب جميع أنواع البشرة ويمنحك نتائج مذهلة.','Learn the essential steps of a daily skincare routine that suits all skin types and gives you amazing results.','<h2>أهمية الروتين اليومي للبشرة</h2>\n<p>العناية بالبشرة ليست رفاهية بل ضرورة للحفاظ على صحة ونضارة بشرتك. الروتين اليومي المنتظم هو المفتاح للحصول على بشرة صحية ومشرقة. في عيادة أورا ديرما، نؤمن بأن البشرة الجميلة تبدأ من العناية اليومية الصحيحة.</p>\n\n<h2>الخطوة الأولى: التنظيف</h2>\n<p>ابدئي يومك بتنظيف بشرتك بغسول مناسب لنوع بشرتك. استخدمي غسولاً لطيفاً خالياً من الكبريتات إذا كانت بشرتك حساسة، أو غسولاً رغوياً إذا كانت بشرتك دهنية. التنظيف يزيل الأوساخ والزيوت المتراكمة ويهيئ بشرتك لامتصاص المنتجات التالية.</p>\n\n<h2>الخطوة الثانية: التونر</h2>\n<p>التونر يساعد على موازنة درجة حموضة البشرة وتضييق المسام. اختاري تونراً يحتوي على حمض الهيالورونيك للترطيب العميق، أو حمض الساليسيليك إذا كنتِ تعانين من حب الشباب.</p>\n\n<h2>الخطوة الثالثة: السيروم</h2>\n<p>السيروم هو المنتج الأكثر تركيزاً في روتينك. سيروم فيتامين سي صباحاً يحمي من أضرار أشعة الشمس ويوحد لون البشرة. سيروم الريتينول مساءً يحفز تجدد الخلايا ويقلل التجاعيد.</p>\n\n<h2>الخطوة الرابعة: المرطب</h2>\n<p>الترطيب ضروري لجميع أنواع البشرة، حتى الدهنية. اختاري مرطباً خفيفاً على شكل جل للبشرة الدهنية، أو كريماً غنياً للبشرة الجافة. المرطب يحبس الرطوبة ويحمي حاجز البشرة الطبيعي.</p>\n\n<h2>الخطوة الخامسة: واقي الشمس</h2>\n<p>واقي الشمس هو أهم خطوة في روتينك الصباحي. استخدمي واقياً بعامل حماية SPF 30 على الأقل يومياً، حتى في الأيام الغائمة. الأشعة فوق البنفسجية هي السبب الرئيسي لشيخوخة البشرة المبكرة والتصبغات.</p>\n\n<h2>نصائح إضافية</h2>\n<ul>\n<li>اشربي ما لا يقل عن 8 أكواب من الماء يومياً</li>\n<li>احصلي على نوم كافٍ من 7 إلى 8 ساعات</li>\n<li>تجنبي لمس وجهك بأيدٍ غير نظيفة</li>\n<li>قومي بتقشير بشرتك مرة أو مرتين أسبوعياً</li>\n<li>استشيري طبيب الجلدية لتحديد المنتجات المناسبة لبشرتك</li>\n</ul>\n\n<p>في عيادة أورا ديرما، نقدم استشارات متخصصة لتحديد نوع بشرتك وتصميم روتين عناية مخصص لك. احجزي موعدك الآن واحصلي على بشرة أحلامك.</p>','<h2>The Importance of a Daily Skincare Routine</h2>\n<p>Skincare is not a luxury but a necessity to maintain healthy, radiant skin. A consistent daily routine is the key to achieving beautiful, glowing skin. At AURA Derma Clinic, we believe that beautiful skin starts with the right daily care.</p>\n\n<h2>Step 1: Cleansing</h2>\n<p>Start your day by cleansing your skin with a cleanser suited to your skin type. Use a gentle, sulfate-free cleanser for sensitive skin, or a foaming cleanser for oily skin. Cleansing removes accumulated dirt and oils and prepares your skin to absorb subsequent products.</p>\n\n<h2>Step 2: Toner</h2>\n<p>Toner helps balance your skin\'s pH and tighten pores. Choose a toner containing hyaluronic acid for deep hydration, or salicylic acid if you struggle with acne.</p>\n\n<h2>Step 3: Serum</h2>\n<p>Serum is the most concentrated product in your routine. Vitamin C serum in the morning protects against sun damage and evens skin tone. Retinol serum at night stimulates cell renewal and reduces wrinkles.</p>\n\n<h2>Step 4: Moisturizer</h2>\n<p>Moisturizing is essential for all skin types, even oily skin. Choose a lightweight gel moisturizer for oily skin, or a rich cream for dry skin. Moisturizer locks in hydration and protects the skin\'s natural barrier.</p>\n\n<h2>Step 5: Sunscreen</h2>\n<p>Sunscreen is the most important step in your morning routine. Use a sunscreen with at least SPF 30 daily, even on cloudy days. UV rays are the primary cause of premature skin aging and hyperpigmentation.</p>\n\n<h2>Additional Tips</h2>\n<ul>\n<li>Drink at least 8 glasses of water daily</li>\n<li>Get adequate sleep of 7 to 8 hours</li>\n<li>Avoid touching your face with unclean hands</li>\n<li>Exfoliate your skin once or twice a week</li>\n<li>Consult a dermatologist to determine the right products for your skin</li>\n</ul>\n\n<p>At AURA Derma Clinic, we offer specialized consultations to determine your skin type and design a customized skincare routine for you. Book your appointment now and get the skin of your dreams.</p>','https://images.unsplash.com/photo-1556228578-0d85b1a4d571?w=800&q=80',NULL,NULL,NULL,NULL,'published',1,'2026-02-16 19:13:33','2026-02-16 04:27:58','2026-02-18 19:13:33'),(2,2,1,'تقنية الهيدرافيشيال: ثورة في تنظيف وتجديد البشرة','HydraFacial Technology: A Revolution in Skin Cleansing and Renewal','hydrafacial-technology-revolution','اكتشفي تقنية الهيدرافيشيال الحديثة التي تجمع بين التنظيف العميق والترطيب والتغذية في جلسة واحدة مميزة.','Discover the modern HydraFacial technology that combines deep cleansing, hydration, and nourishment in one remarkable session.','<h2>ما هي تقنية الهيدرافيشيال؟</h2>\n<p>الهيدرافيشيال هي تقنية متقدمة للعناية بالبشرة تجمع بين التنظيف العميق، والتقشير اللطيف، والاستخلاص، والترطيب المكثف في جلسة واحدة. تعتمد على جهاز متطور يستخدم تقنية الدوامة المائية (Vortex Technology) لتنظيف وتغذية البشرة بشكل فعال دون أي ألم أو وقت تعافي.</p>\n\n<h2>كيف تعمل تقنية الهيدرافيشيال؟</h2>\n<p>تتم الجلسة على عدة خطوات متتالية:</p>\n<ul>\n<li><strong>التنظيف والتقشير:</strong> إزالة الخلايا الميتة والأوساخ من سطح البشرة باستخدام محلول خاص</li>\n<li><strong>استخلاص الشوائب:</strong> سحب الرؤوس السوداء والأوساخ من المسام باستخدام تقنية الشفط اللطيف</li>\n<li><strong>الترطيب والتغذية:</strong> ضخ سيرومات مركزة تحتوي على حمض الهيالورونيك ومضادات الأكسدة والببتيدات</li>\n<li><strong>الحماية:</strong> تطبيق طبقة واقية لحبس المكونات النشطة في البشرة</li>\n</ul>\n\n<h2>فوائد الهيدرافيشيال</h2>\n<p>تتميز هذه التقنية بفوائد عديدة تشمل:</p>\n<ul>\n<li>تنظيف عميق للمسام وإزالة الرؤوس السوداء</li>\n<li>ترطيب مكثف يدوم لأيام</li>\n<li>تحسين ملمس البشرة ونعومتها</li>\n<li>تقليل الخطوط الدقيقة والتجاعيد</li>\n<li>توحيد لون البشرة وتفتيح التصبغات</li>\n<li>مناسبة لجميع أنواع البشرة بما فيها الحساسة</li>\n<li>نتائج فورية من أول جلسة بدون فترة تعافي</li>\n</ul>\n\n<h2>لمن تناسب هذه التقنية؟</h2>\n<p>الهيدرافيشيال مناسبة لجميع أنواع البشرة وجميع الأعمار. تُعد خياراً مثالياً لمن يعانون من المسام الواسعة، البشرة الباهتة، حب الشباب الخفيف، أو الجفاف. كما أنها ممتازة كجلسة تحضيرية قبل المناسبات الخاصة.</p>\n\n<h2>عدد الجلسات الموصى بها</h2>\n<p>للحصول على أفضل النتائج، ننصح بإجراء جلسة كل 4 إلى 6 أسابيع. يمكنك رؤية نتائج واضحة من الجلسة الأولى، ولكن الجلسات المنتظمة تعزز النتائج وتحافظ على صحة بشرتك على المدى الطويل.</p>\n\n<p>في عيادة أورا ديرما، نستخدم أحدث أجهزة الهيدرافيشيال مع سيرومات طبية عالية الجودة لضمان أفضل النتائج. احجزي جلستك الآن واستمتعي ببشرة نقية ومشرقة.</p>','<h2>What is HydraFacial Technology?</h2>\n<p>HydraFacial is an advanced skincare technology that combines deep cleansing, gentle exfoliation, extraction, and intensive hydration in a single session. It relies on a sophisticated device using Vortex Technology to effectively cleanse and nourish the skin without any pain or downtime.</p>\n\n<h2>How Does HydraFacial Work?</h2>\n<p>The session consists of several consecutive steps:</p>\n<ul>\n<li><strong>Cleansing and Exfoliation:</strong> Removing dead cells and impurities from the skin surface using a special solution</li>\n<li><strong>Extraction:</strong> Removing blackheads and debris from pores using gentle suction technology</li>\n<li><strong>Hydration and Nourishment:</strong> Infusing concentrated serums containing hyaluronic acid, antioxidants, and peptides</li>\n<li><strong>Protection:</strong> Applying a protective layer to lock active ingredients into the skin</li>\n</ul>\n\n<h2>Benefits of HydraFacial</h2>\n<p>This technology offers numerous benefits including:</p>\n<ul>\n<li>Deep pore cleansing and blackhead removal</li>\n<li>Intensive hydration lasting for days</li>\n<li>Improved skin texture and smoothness</li>\n<li>Reduction of fine lines and wrinkles</li>\n<li>Even skin tone and lightening of pigmentation</li>\n<li>Suitable for all skin types including sensitive skin</li>\n<li>Immediate results from the first session with no downtime</li>\n</ul>\n\n<h2>Who Is This Technology For?</h2>\n<p>HydraFacial is suitable for all skin types and ages. It is an ideal option for those with enlarged pores, dull skin, mild acne, or dryness. It is also excellent as a preparatory session before special occasions.</p>\n\n<h2>Recommended Number of Sessions</h2>\n<p>For best results, we recommend a session every 4 to 6 weeks. You can see visible results from the first session, but regular sessions enhance results and maintain your skin health long-term.</p>\n\n<p>At AURA Derma Clinic, we use the latest HydraFacial devices with high-quality medical serums to ensure the best results. Book your session now and enjoy clear, radiant skin.</p>','https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?w=800&q=80',NULL,NULL,NULL,NULL,'published',0,'2026-02-13 19:13:33','2026-02-16 04:27:58','2026-02-18 19:13:33'),(3,1,1,'حماية بشرتك من الشمس: كل ما تحتاجين معرفته عن واقي الشمس','Protecting Your Skin from the Sun: Everything You Need to Know About Sunscreen','sun-protection-sunscreen-guide','واقي الشمس هو خط الدفاع الأول لبشرتك. تعرفي على كيفية اختيار واستخدام واقي الشمس المناسب لحماية بشرتك طوال العام.','Sunscreen is your skin\'s first line of defense. Learn how to choose and use the right sunscreen to protect your skin all year round.','<h2>لماذا واقي الشمس ضروري؟</h2>\n<p>أشعة الشمس فوق البنفسجية هي العامل الأول المسبب لشيخوخة البشرة المبكرة، والتصبغات، وحتى سرطان الجلد. واقي الشمس ليس فقط للصيف أو الشاطئ، بل يجب استخدامه يومياً طوال العام، حتى في الأيام الغائمة.</p>\n\n<h2>أنواع الأشعة فوق البنفسجية</h2>\n<p>هناك نوعان رئيسيان من الأشعة فوق البنفسجية التي تصل لبشرتنا:</p>\n<ul>\n<li><strong>UVA (الأشعة فوق البنفسجية أ):</strong> تخترق الطبقات العميقة من الجلد وتسبب التجاعيد والشيخوخة المبكرة. تمر عبر الزجاج والغيوم</li>\n<li><strong>UVB (الأشعة فوق البنفسجية ب):</strong> تؤثر على الطبقة السطحية وتسبب الحروق الشمسية والتصبغات</li>\n</ul>\n\n<h2>كيف تختارين واقي الشمس المناسب؟</h2>\n<p>عند اختيار واقي الشمس، انتبهي لهذه المعايير:</p>\n<ul>\n<li>عامل حماية SPF 30 على الأقل للاستخدام اليومي</li>\n<li>حماية واسعة الطيف (Broad Spectrum) ضد UVA وUVB</li>\n<li>مقاوم للماء إذا كنتِ ستتعرقين أو تسبحين</li>\n<li>خالي من العطور للبشرة الحساسة</li>\n<li>قوام مناسب لنوع بشرتك: خفيف للدهنية، مرطب للجافة</li>\n</ul>\n\n<h2>طريقة الاستخدام الصحيحة</h2>\n<p>ضعي واقي الشمس قبل الخروج من المنزل بـ 15-20 دقيقة. استخدمي كمية بحجم ملعقتين صغيرتين للوجه والرقبة. أعيدي التطبيق كل ساعتين، وبشكل أكثر تكراراً عند التعرق أو السباحة.</p>\n\n<h2>أخطاء شائعة في استخدام واقي الشمس</h2>\n<ul>\n<li>عدم استخدام كمية كافية</li>\n<li>نسيان مناطق مثل الأذنين والرقبة واليدين</li>\n<li>الاعتقاد بأن المكياج يحتوي على حماية كافية</li>\n<li>عدم إعادة التطبيق خلال اليوم</li>\n<li>استخدام واقي شمس منتهي الصلاحية</li>\n</ul>\n\n<p>في عيادة أورا ديرما، نقدم استشارات متخصصة لاختيار واقي الشمس الأمثل لنوع بشرتك واحتياجاتك. كما نوفر مجموعة مختارة من أفضل واقيات الشمس الطبية المتوفرة عالمياً.</p>','<h2>Why Is Sunscreen Essential?</h2>\n<p>Ultraviolet (UV) rays from the sun are the number one cause of premature skin aging, hyperpigmentation, and even skin cancer. Sunscreen is not just for summer or the beach; it should be used daily throughout the year, even on cloudy days.</p>\n\n<h2>Types of UV Rays</h2>\n<p>There are two main types of UV rays that reach our skin:</p>\n<ul>\n<li><strong>UVA (Ultraviolet A):</strong> Penetrates deep skin layers causing wrinkles and premature aging. Passes through glass and clouds</li>\n<li><strong>UVB (Ultraviolet B):</strong> Affects the surface layer causing sunburn and pigmentation</li>\n</ul>\n\n<h2>How to Choose the Right Sunscreen?</h2>\n<p>When choosing sunscreen, pay attention to these criteria:</p>\n<ul>\n<li>SPF 30 or higher for daily use</li>\n<li>Broad Spectrum protection against both UVA and UVB</li>\n<li>Water-resistant if you will be sweating or swimming</li>\n<li>Fragrance-free for sensitive skin</li>\n<li>Suitable texture for your skin type: lightweight for oily, moisturizing for dry</li>\n</ul>\n\n<h2>Proper Application Method</h2>\n<p>Apply sunscreen 15-20 minutes before going outside. Use approximately two teaspoons for the face and neck. Reapply every two hours, and more frequently when sweating or swimming.</p>\n\n<h2>Common Sunscreen Mistakes</h2>\n<ul>\n<li>Not using enough product</li>\n<li>Forgetting areas like ears, neck, and hands</li>\n<li>Believing makeup provides sufficient protection</li>\n<li>Not reapplying throughout the day</li>\n<li>Using expired sunscreen</li>\n</ul>\n\n<p>At AURA Derma Clinic, we offer specialized consultations to choose the optimal sunscreen for your skin type and needs. We also provide a curated selection of the best medical-grade sunscreens available globally.</p>','https://images.unsplash.com/photo-1532413992378-f169ac26fff0?w=800&q=80',NULL,NULL,NULL,NULL,'published',0,'2026-02-10 19:13:33','2026-02-16 04:27:58','2026-02-18 19:13:33'),(4,2,1,'حقن البوتكس والفيلر: الفرق بينهما واستخداماتهما التجميلية','Botox and Filler Injections: Differences and Cosmetic Uses','botox-filler-differences-uses','تعرفي على الفرق بين البوتكس والفيلر وأيهما يناسب احتياجاتك التجميلية للحصول على مظهر شبابي طبيعي.','Learn about the differences between Botox and fillers and which one suits your cosmetic needs for a natural, youthful appearance.','<h2>مقدمة عن الحقن التجميلية</h2>\n<p>الحقن التجميلية أصبحت من أكثر الإجراءات التجميلية شيوعاً في العالم لقدرتها على تجديد مظهر الوجه بدون جراحة. البوتكس والفيلر هما الأكثر استخداماً، لكن الكثير من الناس يخلطون بينهما. في هذا المقال، نوضح الفرق بينهما واستخدامات كل منهما.</p>\n\n<h2>ما هو البوتكس؟</h2>\n<p>البوتكس هو مادة بروتينية مُنقّاة تعمل على إرخاء العضلات المسؤولة عن التجاعيد التعبيرية. يُستخدم بشكل أساسي لعلاج:</p>\n<ul>\n<li>خطوط الجبهة الأفقية</li>\n<li>التجاعيد بين الحاجبين (خطوط العبوس)</li>\n<li>خطوط حول العينين (أقدام الغراب)</li>\n<li>رفع الحواجب بشكل طبيعي</li>\n<li>تقليل التعرق المفرط</li>\n</ul>\n\n<h2>ما هو الفيلر؟</h2>\n<p>الفيلر هو مادة هلامية (عادةً حمض الهيالورونيك) تُحقن تحت الجلد لملء الفراغات وإعادة الحجم المفقود. يُستخدم لـ:</p>\n<ul>\n<li>نفخ الشفاه وتحديدها</li>\n<li>ملء خطوط الابتسامة (الطيات الأنفية الشفوية)</li>\n<li>نحت الفك والذقن</li>\n<li>إعادة الحجم للخدود</li>\n<li>تحسين مظهر الهالات السوداء</li>\n</ul>\n\n<h2>الفرق الرئيسي</h2>\n<p><strong>البوتكس</strong> يعمل على العضلات لمنع التقلصات التي تسبب التجاعيد. <strong>الفيلر</strong> يعمل على ملء الفراغات وإضافة حجم. يمكن استخدامهما معاً للحصول على نتائج شاملة.</p>\n\n<h2>مدة النتائج</h2>\n<p>نتائج البوتكس تدوم من 4 إلى 6 أشهر، بينما تدوم نتائج الفيلر من 6 أشهر إلى سنتين حسب نوع الفيلر ومنطقة الحقن.</p>\n\n<h2>هل الحقن آمنة؟</h2>\n<p>نعم، الحقن التجميلية آمنة عندما تُجرى بواسطة طبيب متخصص ذو خبرة. في عيادة أورا ديرما، نستخدم فقط المنتجات المعتمدة عالمياً ونتبع أعلى معايير السلامة لضمان نتائج طبيعية وآمنة.</p>','<h2>Introduction to Cosmetic Injections</h2>\n<p>Cosmetic injections have become one of the most common cosmetic procedures worldwide for their ability to rejuvenate facial appearance without surgery. Botox and fillers are the most widely used, but many people confuse them. In this article, we clarify the differences and uses of each.</p>\n\n<h2>What Is Botox?</h2>\n<p>Botox is a purified protein that works by relaxing the muscles responsible for expression wrinkles. It is primarily used to treat:</p>\n<ul>\n<li>Horizontal forehead lines</li>\n<li>Frown lines between the eyebrows</li>\n<li>Lines around the eyes (crow\'s feet)</li>\n<li>Natural brow lifting</li>\n<li>Reducing excessive sweating</li>\n</ul>\n\n<h2>What Are Fillers?</h2>\n<p>Fillers are gel-like substances (usually hyaluronic acid) injected under the skin to fill gaps and restore lost volume. They are used for:</p>\n<ul>\n<li>Lip augmentation and definition</li>\n<li>Filling smile lines (nasolabial folds)</li>\n<li>Jawline and chin contouring</li>\n<li>Restoring cheek volume</li>\n<li>Improving the appearance of dark circles</li>\n</ul>\n\n<h2>The Key Difference</h2>\n<p><strong>Botox</strong> works on muscles to prevent contractions that cause wrinkles. <strong>Fillers</strong> work by filling gaps and adding volume. They can be used together for comprehensive results.</p>\n\n<h2>Duration of Results</h2>\n<p>Botox results last 4 to 6 months, while filler results last from 6 months to 2 years depending on the type of filler and injection area.</p>\n\n<h2>Are Injections Safe?</h2>\n<p>Yes, cosmetic injections are safe when performed by an experienced, specialized physician. At AURA Derma Clinic, we use only globally approved products and follow the highest safety standards to ensure natural, safe results.</p>','https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?w=800&q=80',NULL,NULL,NULL,NULL,'published',0,'2026-02-06 19:13:33','2026-02-16 04:27:58','2026-02-18 19:13:33'),(5,3,1,'حب الشباب: أسبابه وأحدث طرق العلاج الفعّالة','Acne: Causes and Latest Effective Treatment Methods','acne-causes-latest-treatments','حب الشباب مشكلة شائعة تؤثر على الثقة بالنفس. تعرفي على أسبابه وأحدث الطرق العلاجية المتاحة للتخلص منه نهائياً.','Acne is a common problem that affects self-confidence. Learn about its causes and the latest treatment methods available to eliminate it for good.','<h2>ما هو حب الشباب؟</h2>\n<p>حب الشباب هو حالة جلدية تحدث عندما تنسد بصيلات الشعر بالزيوت والخلايا الميتة، مما يؤدي إلى ظهور البثور والرؤوس السوداء والبيضاء. وعلى الرغم من أنه أكثر شيوعاً في مرحلة المراهقة، إلا أنه يمكن أن يصيب الأشخاص في أي عمر.</p>\n\n<h2>الأسباب الرئيسية</h2>\n<ul>\n<li><strong>الهرمونات:</strong> التغيرات الهرمونية، خاصة خلال المراهقة والدورة الشهرية والحمل</li>\n<li><strong>الوراثة:</strong> إذا كان أحد الوالدين يعاني من حب الشباب، فاحتمالية إصابتك أعلى</li>\n<li><strong>الإفراط في إنتاج الزهم:</strong> الغدد الدهنية النشطة تنتج زيوتاً زائدة</li>\n<li><strong>البكتيريا:</strong> بكتيريا P. acnes تتكاثر في المسام المسدودة وتسبب الالتهاب</li>\n<li><strong>النظام الغذائي:</strong> بعض الأطعمة مثل منتجات الألبان والسكريات قد تزيد من حب الشباب</li>\n<li><strong>التوتر:</strong> يزيد من إنتاج هرمون الكورتيزول الذي يحفز الغدد الدهنية</li>\n</ul>\n\n<h2>أنواع حب الشباب</h2>\n<p>يتراوح حب الشباب من الخفيف إلى الشديد:</p>\n<ul>\n<li>الرؤوس السوداء والبيضاء (غير الملتهبة)</li>\n<li>البثور الحمراء (الحطاطات)</li>\n<li>البثور المليئة بالقيح (البثرات)</li>\n<li>العقيدات والأكياس (الحالات الشديدة)</li>\n</ul>\n\n<h2>أحدث طرق العلاج في عيادة أورا ديرما</h2>\n<p>نقدم في عيادتنا مجموعة شاملة من العلاجات المتقدمة:</p>\n<ul>\n<li><strong>التقشير الكيميائي:</strong> يزيل الطبقة السطحية ويفتح المسام المسدودة</li>\n<li><strong>العلاج بالليزر:</strong> يقتل البكتيريا ويقلل الالتهاب ويحفز تجدد الجلد</li>\n<li><strong>الميزوثيرابي:</strong> حقن فيتامينات ومضادات أكسدة مباشرة في البشرة</li>\n<li><strong>العلاج الضوئي LED:</strong> أشعة زرقاء لقتل البكتيريا وحمراء لتقليل الالتهاب</li>\n<li><strong>العلاجات الموضعية والفموية:</strong> بروتوكولات علاجية مخصصة لكل حالة</li>\n</ul>\n\n<h2>نصائح للوقاية</h2>\n<ul>\n<li>نظفي بشرتك مرتين يومياً بغسول مناسب</li>\n<li>لا تعصري أو تضغطي على البثور</li>\n<li>استخدمي مستحضرات تجميل خالية من الزيوت</li>\n<li>غيري وسادتك بانتظام</li>\n<li>قللي من التوتر واحصلي على نوم كافٍ</li>\n</ul>\n\n<p>لا تدعي حب الشباب يؤثر على ثقتك بنفسك. في عيادة أورا ديرما، نقدم خطط علاجية مخصصة تناسب حالتك. احجزي استشارتك اليوم.</p>','<h2>What Is Acne?</h2>\n<p>Acne is a skin condition that occurs when hair follicles become clogged with oil and dead skin cells, leading to pimples, blackheads, and whiteheads. Although it is most common during adolescence, it can affect people at any age.</p>\n\n<h2>Main Causes</h2>\n<ul>\n<li><strong>Hormones:</strong> Hormonal changes, especially during puberty, menstrual cycles, and pregnancy</li>\n<li><strong>Genetics:</strong> If a parent had acne, your likelihood of developing it is higher</li>\n<li><strong>Excess Sebum Production:</strong> Overactive sebaceous glands produce excess oil</li>\n<li><strong>Bacteria:</strong> P. acnes bacteria multiply in clogged pores causing inflammation</li>\n<li><strong>Diet:</strong> Some foods like dairy products and sugars may worsen acne</li>\n<li><strong>Stress:</strong> Increases cortisol production which stimulates oil glands</li>\n</ul>\n\n<h2>Types of Acne</h2>\n<p>Acne ranges from mild to severe:</p>\n<ul>\n<li>Blackheads and whiteheads (non-inflammatory)</li>\n<li>Red bumps (papules)</li>\n<li>Pus-filled bumps (pustules)</li>\n<li>Nodules and cysts (severe cases)</li>\n</ul>\n\n<h2>Latest Treatment Methods at AURA Derma Clinic</h2>\n<p>We offer a comprehensive range of advanced treatments at our clinic:</p>\n<ul>\n<li><strong>Chemical Peeling:</strong> Removes the surface layer and unclogs pores</li>\n<li><strong>Laser Therapy:</strong> Kills bacteria, reduces inflammation, and stimulates skin renewal</li>\n<li><strong>Mesotherapy:</strong> Direct injection of vitamins and antioxidants into the skin</li>\n<li><strong>LED Light Therapy:</strong> Blue light to kill bacteria and red light to reduce inflammation</li>\n<li><strong>Topical and Oral Treatments:</strong> Customized treatment protocols for each case</li>\n</ul>\n\n<h2>Prevention Tips</h2>\n<ul>\n<li>Cleanse your skin twice daily with an appropriate cleanser</li>\n<li>Do not squeeze or pick at pimples</li>\n<li>Use oil-free cosmetics</li>\n<li>Change your pillowcase regularly</li>\n<li>Reduce stress and get adequate sleep</li>\n</ul>\n\n<p>Don\'t let acne affect your confidence. At AURA Derma Clinic, we provide customized treatment plans that suit your condition. Book your consultation today.</p>','https://images.unsplash.com/photo-1616394584738-fc6e612e71b9?w=800&q=80',NULL,NULL,NULL,NULL,'published',0,'2026-02-03 19:13:33','2026-02-16 04:27:58','2026-02-18 19:13:33'),(6,2,1,'إزالة الشعر بالليزر: الحل الدائم للشعر غير المرغوب فيه','Laser Hair Removal: The Permanent Solution for Unwanted Hair','laser-hair-removal-permanent-solution','تخلصي من عناء إزالة الشعر التقليدية مع تقنية الليزر المتقدمة التي توفر نتائج دائمة وبشرة ناعمة كالحرير.','Say goodbye to traditional hair removal with advanced laser technology that provides permanent results and silky-smooth skin.','<h2>لماذا إزالة الشعر بالليزر؟</h2>\n<p>إذا كنتِ تعانين من عناء الحلاقة المتكررة، الشمع المؤلم، أو الكريمات المزيلة للشعر، فإن إزالة الشعر بالليزر هي الحل المثالي والدائم. تقنية الليزر الحديثة توفر تقليلاً دائماً لنمو الشعر بنسبة تصل إلى 90% بعد إتمام الجلسات الموصى بها.</p>\n\n<h2>كيف يعمل الليزر؟</h2>\n<p>يعمل الليزر عن طريق إرسال نبضات ضوئية مركزة تستهدف صبغة الميلانين في بصيلة الشعر. الحرارة الناتجة تدمر البصيلة وتمنعها من إنتاج شعر جديد، بينما تبقى البشرة المحيطة سليمة وآمنة.</p>\n\n<h2>المناطق التي يمكن علاجها</h2>\n<ul>\n<li>الوجه (الشفة العلوية، الذقن، السوالف)</li>\n<li>تحت الإبط</li>\n<li>الذراعين والساقين</li>\n<li>منطقة البكيني</li>\n<li>الظهر والصدر</li>\n<li>أي منطقة أخرى في الجسم</li>\n</ul>\n\n<h2>عدد الجلسات المطلوبة</h2>\n<p>يحتاج معظم الأشخاص من 6 إلى 8 جلسات بفاصل 4 إلى 6 أسابيع بين كل جلسة. يعود ذلك لأن الشعر ينمو في دورات مختلفة، والليزر يكون فعّالاً فقط على الشعر في مرحلة النمو النشط.</p>\n\n<h2>التحضير للجلسة</h2>\n<ul>\n<li>تجنبي التعرض للشمس قبل الجلسة بأسبوعين</li>\n<li>احلقي المنطقة المراد علاجها قبل الجلسة بيوم</li>\n<li>تجنبي نتف الشعر أو استخدام الشمع قبل الجلسة بـ 4 أسابيع</li>\n<li>أخبري الطبيب عن أي أدوية تتناولينها</li>\n</ul>\n\n<h2>أحدث أجهزة الليزر في عيادة أورا ديرما</h2>\n<p>نستخدم في عيادتنا أحدث أجهزة الليزر المعتمدة عالمياً والمناسبة لجميع أنواع البشرة، بما فيها البشرة الداكنة. أجهزتنا مزودة بأنظمة تبريد متقدمة لضمان راحتك أثناء الجلسة.</p>\n\n<p>احجزي جلستك الأولى في عيادة أورا ديرما واستمتعي ببشرة ناعمة خالية من الشعر.</p>','<h2>Why Laser Hair Removal?</h2>\n<p>If you\'re tired of frequent shaving, painful waxing, or depilatory creams, laser hair removal is the ideal, permanent solution. Modern laser technology provides a permanent reduction in hair growth of up to 90% after completing the recommended sessions.</p>\n\n<h2>How Does Laser Work?</h2>\n<p>The laser works by sending concentrated light pulses that target the melanin pigment in the hair follicle. The resulting heat destroys the follicle and prevents it from producing new hair, while the surrounding skin remains intact and safe.</p>\n\n<h2>Areas That Can Be Treated</h2>\n<ul>\n<li>Face (upper lip, chin, sideburns)</li>\n<li>Underarms</li>\n<li>Arms and legs</li>\n<li>Bikini area</li>\n<li>Back and chest</li>\n<li>Any other body area</li>\n</ul>\n\n<h2>Number of Sessions Required</h2>\n<p>Most people need 6 to 8 sessions with 4 to 6 weeks between each session. This is because hair grows in different cycles, and the laser is only effective on hair in the active growth phase.</p>\n\n<h2>Pre-Session Preparation</h2>\n<ul>\n<li>Avoid sun exposure two weeks before the session</li>\n<li>Shave the treatment area one day before the session</li>\n<li>Avoid plucking or waxing 4 weeks before the session</li>\n<li>Inform your doctor about any medications you are taking</li>\n</ul>\n\n<h2>Latest Laser Devices at AURA Derma Clinic</h2>\n<p>At our clinic, we use the latest globally approved laser devices suitable for all skin types, including darker skin tones. Our devices are equipped with advanced cooling systems to ensure your comfort during the session.</p>\n\n<p>Book your first session at AURA Derma Clinic and enjoy smooth, hair-free skin.</p>','https://images.unsplash.com/photo-1598524374912-6b0b0bab3da4?w=800&q=80',NULL,NULL,NULL,NULL,'published',0,'2026-01-31 19:13:33','2026-02-16 04:27:58','2026-02-18 19:13:33'),(7,3,1,'التصبغات الجلدية: أنواعها وعلاجها للحصول على بشرة موحدة اللون','Skin Pigmentation: Types and Treatments for Even-Toned Skin','skin-pigmentation-types-treatments','التصبغات الجلدية من أكثر المشاكل شيوعاً. تعرفي على أنواعها وأسبابها وأحدث العلاجات للحصول على بشرة صافية وموحدة.','Skin pigmentation is one of the most common skin concerns. Learn about its types, causes, and latest treatments for clear, even-toned skin.','<h2>ما هي التصبغات الجلدية؟</h2>\n<p>التصبغات الجلدية هي مناطق داكنة أو فاتحة تظهر على البشرة نتيجة زيادة أو نقصان في إنتاج صبغة الميلانين. تُعد من أكثر المشاكل الجلدية شيوعاً وتؤثر على مظهر البشرة بشكل ملحوظ.</p>\n\n<h2>أنواع التصبغات الشائعة</h2>\n<ul>\n<li><strong>الكلف:</strong> بقع بنية تظهر عادةً على الوجه، شائعة عند النساء خاصةً أثناء الحمل</li>\n<li><strong>النمش:</strong> بقع صغيرة بنية فاتحة تظهر في المناطق المعرضة للشمس</li>\n<li><strong>فرط التصبغ التالي للالتهاب:</strong> بقع داكنة تظهر بعد التهاب الجلد أو حب الشباب</li>\n<li><strong>البقع الشمسية:</strong> بقع داكنة تظهر مع التقدم في العمر في المناطق المعرضة للشمس</li>\n</ul>\n\n<h2>الأسباب</h2>\n<p>تتعدد أسباب التصبغات وتشمل:</p>\n<ul>\n<li>التعرض المفرط لأشعة الشمس دون حماية</li>\n<li>التغيرات الهرمونية (الحمل، موانع الحمل)</li>\n<li>الالتهابات والإصابات الجلدية</li>\n<li>بعض الأدوية التي تزيد حساسية الجلد للشمس</li>\n<li>العوامل الوراثية</li>\n</ul>\n\n<h2>العلاجات المتقدمة في أورا ديرما</h2>\n<p>نقدم مجموعة متكاملة من العلاجات المتخصصة:</p>\n<ul>\n<li><strong>التقشير الكيميائي:</strong> يزيل الطبقات السطحية المتصبغة ويكشف عن بشرة جديدة</li>\n<li><strong>ليزر تفتيح البشرة:</strong> يستهدف خلايا الميلانين بدقة عالية</li>\n<li><strong>الميزوثيرابي بالفيتامينات:</strong> حقن مواد تفتيح مثل فيتامين C والجلوتاثيون</li>\n<li><strong>كريمات التفتيح الطبية:</strong> بروتوكولات علاجية منزلية مخصصة</li>\n<li><strong>تقنية الميكرونيدلينج:</strong> تحفز إنتاج الكولاجين وتساعد على تجدد البشرة</li>\n</ul>\n\n<h2>الوقاية من التصبغات</h2>\n<ul>\n<li>استخدام واقي شمس يومياً بعامل حماية 50 أو أعلى</li>\n<li>ارتداء قبعة ونظارات شمسية عند الخروج</li>\n<li>تجنب التعرض المباشر للشمس وقت الذروة</li>\n<li>استخدام منتجات تحتوي على فيتامين C ومضادات الأكسدة</li>\n</ul>\n\n<p>في عيادة أورا ديرما، نصمم خطة علاج مخصصة لكل حالة للحصول على أفضل النتائج. احجزي موعدك لاستشارة مجانية.</p>','<h2>What Is Skin Pigmentation?</h2>\n<p>Skin pigmentation refers to dark or light areas that appear on the skin due to increased or decreased melanin production. It is one of the most common skin concerns and significantly affects skin appearance.</p>\n\n<h2>Common Types of Pigmentation</h2>\n<ul>\n<li><strong>Melasma:</strong> Brown patches usually on the face, common in women especially during pregnancy</li>\n<li><strong>Freckles:</strong> Small light-brown spots in sun-exposed areas</li>\n<li><strong>Post-inflammatory Hyperpigmentation:</strong> Dark spots after skin inflammation or acne</li>\n<li><strong>Sun Spots:</strong> Dark patches that develop with age in sun-exposed areas</li>\n</ul>\n\n<h2>Causes</h2>\n<p>Pigmentation has multiple causes including:</p>\n<ul>\n<li>Excessive sun exposure without protection</li>\n<li>Hormonal changes (pregnancy, birth control)</li>\n<li>Inflammation and skin injuries</li>\n<li>Certain medications that increase sun sensitivity</li>\n<li>Genetic factors</li>\n</ul>\n\n<h2>Advanced Treatments at AURA Derma</h2>\n<p>We offer a comprehensive range of specialized treatments:</p>\n<ul>\n<li><strong>Chemical Peeling:</strong> Removes pigmented surface layers to reveal new skin</li>\n<li><strong>Skin Lightening Laser:</strong> Precisely targets melanin cells</li>\n<li><strong>Vitamin Mesotherapy:</strong> Injections of lightening agents like Vitamin C and Glutathione</li>\n<li><strong>Medical Lightening Creams:</strong> Customized at-home treatment protocols</li>\n<li><strong>Microneedling:</strong> Stimulates collagen production and helps skin renewal</li>\n</ul>\n\n<h2>Preventing Pigmentation</h2>\n<ul>\n<li>Use daily sunscreen with SPF 50 or higher</li>\n<li>Wear a hat and sunglasses when going outdoors</li>\n<li>Avoid direct sun exposure during peak hours</li>\n<li>Use products containing Vitamin C and antioxidants</li>\n</ul>\n\n<p>At AURA Derma Clinic, we design a customized treatment plan for each case to achieve the best results. Book your appointment for a free consultation.</p>','https://images.unsplash.com/photo-1505944270255-72b8c68c6a70?w=800&q=80',NULL,NULL,NULL,NULL,'published',0,'2026-01-27 19:13:33','2026-02-16 04:27:58','2026-02-18 19:13:33'),(8,4,1,'أكثر 10 أسئلة شيوعاً عن العناية بالبشرة والتجميل','Top 10 Most Common Questions About Skincare and Aesthetics','top-10-skincare-questions-answers','إجابات شاملة على أكثر الأسئلة التي يطرحها مرضانا حول العناية بالبشرة وإجراءات التجميل والعلاجات المتقدمة.','Comprehensive answers to the most frequently asked questions by our patients about skincare, cosmetic procedures, and advanced treatments.','<h2>1. ما هو أفضل عمر لبدء العناية بالبشرة؟</h2>\n<p>لا يوجد عمر محدد، لكن ننصح ببدء روتين أساسي (تنظيف + ترطيب + واقي شمس) من عمر المراهقة. العلاجات المتقدمة مثل البوتكس والفيلر يُفضل البدء بها من منتصف العشرينيات كإجراء وقائي.</p>\n\n<h2>2. هل المنتجات الطبيعية أفضل للبشرة؟</h2>\n<p>ليس بالضرورة. العديد من المنتجات الطبيعية قد تسبب حساسية أو تهيجاً. المنتجات الطبية المُختبرة سريرياً تكون أكثر فعالية وأماناً. الأهم هو اختيار منتجات تناسب نوع بشرتك بإشراف طبيب متخصص.</p>\n\n<h2>3. كم مرة يجب زيارة طبيب الجلدية؟</h2>\n<p>ننصح بزيارة طبيب الجلدية مرة على الأقل سنوياً لفحص البشرة العام. إذا كانت لديك مشاكل جلدية محددة، قد تحتاجين لزيارات أكثر تكراراً حسب خطة العلاج.</p>\n\n<h2>4. هل البوتكس يجمد الوجه؟</h2>\n<p>لا، إذا تم حقنه بالجرعة الصحيحة وبواسطة طبيب ماهر. في عيادة أورا ديرما، نستخدم تقنيات حقن دقيقة تحافظ على التعبيرات الطبيعية للوجه مع تقليل التجاعيد.</p>\n\n<h2>5. هل الليزر مؤلم؟</h2>\n<p>معظم أجهزة الليزر الحديثة مزودة بأنظمة تبريد تجعل الجلسة مريحة. قد تشعرين ببعض الوخز الخفيف، لكنه محتمل ولا يحتاج لتخدير في معظم الحالات.</p>\n\n<h2>6. ما الفرق بين التقشير الكيميائي والتقشير بالليزر؟</h2>\n<p>التقشير الكيميائي يستخدم أحماض لإزالة الطبقات السطحية، وهو مناسب للتصبغات الخفيفة وتحسين الملمس. التقشير بالليزر أكثر دقة ويمكنه التعامل مع مشاكل أعمق مثل الندبات والتجاعيد العميقة.</p>\n\n<h2>7. هل يمكنني وضع المكياج بعد الجلسات؟</h2>\n<p>يعتمد على نوع الجلسة. بعد الهيدرافيشيال يمكنك وضع المكياج فوراً. بعد التقشير أو الليزر، ننصح بالانتظار 24-48 ساعة واستخدام مستحضرات لطيفة على البشرة.</p>\n\n<h2>8. ما هي أفضل الفيتامينات للبشرة؟</h2>\n<p>فيتامين C لتفتيح البشرة ومقاومة الأكسدة، فيتامين E للترطيب والحماية، فيتامين A (الريتينول) لمكافحة الشيخوخة، وفيتامين B3 (النياسيناميد) لتنظيم إفراز الدهون وتضييق المسام.</p>\n\n<h2>9. هل العلاجات التجميلية آمنة أثناء الحمل؟</h2>\n<p>ننصح بتجنب معظم العلاجات التجميلية أثناء الحمل والرضاعة كإجراء احترازي. يمكنك الاستمرار في الروتين الأساسي (تنظيف، ترطيب، واقي شمس) مع تجنب الريتينول وبعض الأحماض.</p>\n\n<h2>10. كيف أختار العيادة المناسبة؟</h2>\n<p>ابحثي عن عيادة مرخصة بأطباء متخصصين ذوي خبرة، تستخدم أجهزة ومنتجات معتمدة عالمياً، وتقدم استشارة شاملة قبل أي إجراء. في عيادة أورا ديرما، نفخر بتوفير كل هذه المعايير مع اهتمام شخصي بكل مريضة.</p>','<h2>1. What Is the Best Age to Start Skincare?</h2>\n<p>There is no specific age, but we recommend starting a basic routine (cleanse + moisturize + sunscreen) from teenage years. Advanced treatments like Botox and fillers are best started in your mid-twenties as a preventive measure.</p>\n\n<h2>2. Are Natural Products Better for Skin?</h2>\n<p>Not necessarily. Many natural products can cause allergies or irritation. Clinically tested medical products are more effective and safer. What matters most is choosing products that suit your skin type under the guidance of a specialist.</p>\n\n<h2>3. How Often Should I Visit a Dermatologist?</h2>\n<p>We recommend visiting a dermatologist at least once a year for a general skin examination. If you have specific skin concerns, you may need more frequent visits depending on your treatment plan.</p>\n\n<h2>4. Does Botox Freeze Your Face?</h2>\n<p>No, not when injected in the right dosage by a skilled physician. At AURA Derma Clinic, we use precise injection techniques that preserve natural facial expressions while reducing wrinkles.</p>\n\n<h2>5. Is Laser Treatment Painful?</h2>\n<p>Most modern laser devices are equipped with cooling systems that make the session comfortable. You may feel slight tingling, but it is tolerable and does not require anesthesia in most cases.</p>\n\n<h2>6. What Is the Difference Between Chemical and Laser Peeling?</h2>\n<p>Chemical peeling uses acids to remove surface layers and is suitable for mild pigmentation and texture improvement. Laser peeling is more precise and can address deeper issues like scars and deep wrinkles.</p>\n\n<h2>7. Can I Wear Makeup After Treatment Sessions?</h2>\n<p>It depends on the type of session. After HydraFacial, you can apply makeup immediately. After peeling or laser, we recommend waiting 24-48 hours and using gentle products on the skin.</p>\n\n<h2>8. What Are the Best Vitamins for Skin?</h2>\n<p>Vitamin C for brightening and antioxidant protection, Vitamin E for hydration and protection, Vitamin A (Retinol) for anti-aging, and Vitamin B3 (Niacinamide) for oil regulation and pore tightening.</p>\n\n<h2>9. Are Cosmetic Treatments Safe During Pregnancy?</h2>\n<p>We recommend avoiding most cosmetic treatments during pregnancy and breastfeeding as a precautionary measure. You can continue your basic routine (cleanse, moisturize, sunscreen) while avoiding retinol and certain acids.</p>\n\n<h2>10. How Do I Choose the Right Clinic?</h2>\n<p>Look for a licensed clinic with experienced specialist doctors, using globally approved devices and products, offering a comprehensive consultation before any procedure. At AURA Derma Clinic, we are proud to provide all these standards with personal attention to every patient.</p>','https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=800&q=80',NULL,NULL,NULL,NULL,'published',0,'2026-01-24 19:13:33','2026-02-16 04:27:58','2026-02-18 19:13:33');
/*!40000 ALTER TABLE `posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prescription_items`
--

DROP TABLE IF EXISTS `prescription_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `prescription_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `prescription_id` bigint(20) unsigned NOT NULL,
  `medication_name` varchar(255) NOT NULL,
  `dosage` varchar(255) DEFAULT NULL,
  `frequency` varchar(255) DEFAULT NULL,
  `duration` varchar(255) DEFAULT NULL,
  `instructions` text,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `prescription_items_prescription_id_foreign` (`prescription_id`),
  CONSTRAINT `prescription_items_prescription_id_foreign` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prescription_items`
--

LOCK TABLES `prescription_items` WRITE;
/*!40000 ALTER TABLE `prescription_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `prescription_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prescriptions`
--

DROP TABLE IF EXISTS `prescriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `prescriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `visit_id` bigint(20) unsigned DEFAULT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `doctor_id` bigint(20) unsigned DEFAULT NULL,
  `diagnosis` text,
  `notes` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `prescriptions_visit_id_foreign` (`visit_id`),
  KEY `prescriptions_patient_id_foreign` (`patient_id`),
  KEY `prescriptions_doctor_id_foreign` (`doctor_id`),
  CONSTRAINT `prescriptions_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE SET NULL,
  CONSTRAINT `prescriptions_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `prescriptions_visit_id_foreign` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prescriptions`
--

LOCK TABLES `prescriptions` WRITE;
/*!40000 ALTER TABLE `prescriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `prescriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `display_name_en` varchar(255) NOT NULL,
  `display_name_ar` varchar(255) NOT NULL,
  `permissions` longtext NOT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'super_admin','Super Admin','المدير العام','[\"*\"]',1,'2026-02-16 04:27:58','2026-02-16 04:27:58'),(2,'editor','Editor','محرر','[\"posts.view\",\"posts.create\",\"posts.update\",\"posts.delete\",\"post_categories.view\",\"post_categories.create\",\"post_categories.update\",\"post_categories.delete\",\"tags.view\",\"tags.create\",\"tags.update\",\"tags.delete\",\"services.view\",\"services.create\",\"services.update\",\"services.delete\",\"service_categories.view\",\"service_categories.create\",\"service_categories.update\",\"service_categories.delete\",\"offers.view\",\"offers.create\",\"offers.update\",\"offers.delete\",\"doctors.view\",\"doctors.create\",\"doctors.update\",\"doctors.delete\",\"gallery.view\",\"gallery.create\",\"gallery.update\",\"gallery.delete\",\"testimonials.view\",\"testimonials.create\",\"testimonials.update\",\"testimonials.delete\",\"faqs.view\",\"faqs.create\",\"faqs.update\",\"faqs.delete\",\"pages.view\",\"pages.update\"]',0,'2026-02-16 04:27:58','2026-02-16 04:27:58'),(3,'moderator','Moderator','مشرف','[\"bookings.view\",\"bookings.update\",\"bookings.export\",\"contact_messages.view\",\"contact_messages.delete\"]',0,'2026-02-16 04:27:58','2026-02-16 04:27:58'),(4,'receptionist','Receptionist','موظفة الاستقبال','[\"patients.view\",\"patients.create\",\"patients.update\",\"visits.view\",\"visits.create\",\"visits.update\",\"service_packages.view\",\"service_packages.create\",\"invoices.view\",\"invoices.create\",\"payments.view\",\"payments.create\",\"discount_codes.view\",\"bookings.view\",\"bookings.update\"]',0,'2026-02-17 01:45:25','2026-02-17 01:45:25'),(5,'doctor','Doctor','طبيب','[\"patients.view\",\"visits.view\",\"visits.update\",\"prescriptions.view\",\"prescriptions.create\",\"prescriptions.update\",\"service_packages.view\",\"invoices.view\",\"reports.view\"]',0,'2026-02-17 01:45:25','2026-02-17 01:45:25'),(6,'accountant','Accountant','محاسب','[\"invoices.view\",\"invoices.create\",\"invoices.update\",\"payments.view\",\"payments.create\",\"payments.delete\",\"expenses.view\",\"expenses.create\",\"expenses.update\",\"expenses.delete\",\"discount_codes.view\",\"discount_codes.create\",\"discount_codes.update\",\"reports.view\",\"patients.view\"]',0,'2026-02-17 01:45:25','2026-02-17 01:45:25'),(7,'secretary','Secretary','سكرتارية','[\"patients.view\",\"patients.create\",\"patients.update\",\"visits.view\",\"visits.create\",\"visits.update\",\"bookings.view\",\"bookings.update\",\"service_packages.view\",\"service_packages.create\",\"invoices.view\",\"invoices.create\",\"payments.view\",\"payments.create\",\"discount_codes.view\",\"prescriptions.view\",\"contact_messages.view\"]',0,'2026-02-17 17:38:52','2026-02-17 17:38:52'),(8,'webmaster','Webmaster','مدير الموقع','[\"services.view\",\"services.create\",\"services.update\",\"services.delete\",\"service_categories.view\",\"service_categories.create\",\"service_categories.update\",\"service_categories.delete\",\"offers.view\",\"offers.create\",\"offers.update\",\"offers.delete\",\"doctors.view\",\"doctors.create\",\"doctors.update\",\"doctors.delete\",\"gallery.view\",\"gallery.create\",\"gallery.update\",\"gallery.delete\",\"testimonials.view\",\"testimonials.create\",\"testimonials.update\",\"testimonials.delete\",\"faqs.view\",\"faqs.create\",\"faqs.update\",\"faqs.delete\",\"pages.view\",\"pages.update\",\"posts.view\",\"posts.create\",\"posts.update\",\"posts.delete\",\"post_categories.view\",\"post_categories.create\",\"post_categories.update\",\"post_categories.delete\",\"tags.view\",\"tags.create\",\"tags.update\",\"tags.delete\",\"settings.view\",\"settings.update\"]',1,'2026-02-18 17:12:38','2026-02-18 17:12:38');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seo_pages`
--

DROP TABLE IF EXISTS `seo_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `seo_pages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `page_identifier` varchar(255) NOT NULL,
  `page_name_en` varchar(255) NOT NULL,
  `page_name_ar` varchar(255) NOT NULL,
  `title_ar` text,
  `title_en` text,
  `description_ar` text,
  `description_en` text,
  `keywords` text,
  `og_image` varchar(255) DEFAULT NULL,
  `canonical_url` varchar(255) DEFAULT NULL,
  `is_indexable` tinyint(1) NOT NULL DEFAULT 1,
  `structured_data` longtext,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `seo_pages_page_identifier_unique` (`page_identifier`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seo_pages`
--

LOCK TABLES `seo_pages` WRITE;
/*!40000 ALTER TABLE `seo_pages` DISABLE KEYS */;
INSERT INTO `seo_pages` VALUES (1,'home','Home Page','الصفحة الرئيسية','عيادة أورا ديرما للجلدية والتجميل','AURA Derma Aesthetic Clinic','عيادة أورا ديرما للجلدية والتجميل - أحدث تقنيات العناية بالبشرة والتجميل في مصر مع نخبة من أمهر الأطباء المتخصصين.','AURA Derma Aesthetic Clinic - The latest skincare and cosmetic technologies in Egypt with an elite team of specialist doctors.','dermatology, skincare, cosmetic clinic, Egypt, عيادة جلدية, تجميل, عناية بالبشرة, مصر, أورا ديرما',NULL,NULL,1,NULL,'2026-02-18 15:47:46','2026-02-18 15:47:46'),(2,'about','About Page','صفحة من نحن','عن العيادة - عيادة أورا ديرما','About Us - AURA Derma Clinic','تعرف على عيادة أورا ديرما للجلدية والتجميل، رؤيتنا ورسالتنا وفريقنا الطبي المتميز.','Learn about AURA Derma Aesthetic Clinic, our vision, mission, and distinguished medical team.','about AURA Derma, dermatology clinic, cosmetic clinic Egypt, عن أورا ديرما, عيادة تجميل',NULL,NULL,1,NULL,'2026-02-18 15:47:46','2026-02-18 15:47:46'),(3,'services','Services Page','صفحة الخدمات','خدماتنا - عيادة أورا ديرما','Our Services - AURA Derma Clinic','استكشف خدمات عيادة أورا ديرما للجلدية والتجميل - علاجات البشرة والليزر والتجميل بأحدث التقنيات.','Explore AURA Derma Clinic services - skin treatments, laser, and cosmetic procedures with the latest technologies.','dermatology services, laser treatment, cosmetic procedures, skin care, خدمات جلدية, ليزر, تجميل',NULL,NULL,1,NULL,'2026-02-18 15:47:46','2026-02-18 15:47:46'),(4,'gallery','Gallery Page','صفحة المعرض','معرض الصور - عيادة أورا ديرما','Gallery - AURA Derma Clinic','شاهد معرض صور عيادة أورا ديرما - نتائج العلاجات والعيادة وفريق العمل.','View AURA Derma Clinic gallery - treatment results, clinic facilities, and our team.','clinic gallery, before after, treatment results, معرض العيادة, نتائج العلاج',NULL,NULL,1,NULL,'2026-02-18 15:47:46','2026-02-18 15:47:46'),(5,'offers','Offers Page','صفحة العروض','العروض - عيادة أورا ديرما','Offers - AURA Derma Clinic','اكتشف أحدث عروض وخصومات عيادة أورا ديرما للجلدية والتجميل.','Discover the latest offers and discounts at AURA Derma Aesthetic Clinic.','clinic offers, discounts, dermatology deals, عروض العيادة, خصومات, تجميل',NULL,NULL,1,NULL,'2026-02-18 15:47:46','2026-02-18 15:47:46'),(6,'faq','FAQ Page','صفحة الأسئلة الشائعة','الأسئلة الشائعة - عيادة أورا ديرما','FAQ - AURA Derma Clinic','إجابات على الأسئلة الشائعة حول خدمات وعلاجات عيادة أورا ديرما.','Answers to frequently asked questions about AURA Derma Clinic services and treatments.','FAQ, questions, dermatology FAQ, أسئلة شائعة, استفسارات',NULL,NULL,1,NULL,'2026-02-18 15:47:46','2026-02-18 15:47:46'),(7,'booking','Booking Page','صفحة الحجز','حجز موعد - عيادة أورا ديرما','Book Appointment - AURA Derma Clinic','احجز موعدك الآن في عيادة أورا ديرما للجلدية والتجميل - حجز سريع وسهل.','Book your appointment now at AURA Derma Aesthetic Clinic - quick and easy booking.','book appointment, clinic booking, dermatology appointment, حجز موعد, حجز عيادة',NULL,NULL,1,NULL,'2026-02-18 15:47:46','2026-02-18 15:47:46'),(8,'contact','Contact Page','صفحة تواصل معنا','تواصل معنا - عيادة أورا ديرما','Contact Us - AURA Derma Clinic','تواصل مع عيادة أورا ديرما - عنوان العيادة وأرقام الهاتف ونموذج التواصل.','Contact AURA Derma Clinic - clinic address, phone numbers, and contact form.','contact clinic, phone, address, location, تواصل, عنوان العيادة, هاتف',NULL,NULL,1,NULL,'2026-02-18 15:47:46','2026-02-18 15:47:46'),(9,'blog','Blog Page','صفحة المدونة','المدونة - عيادة أورا ديرما','Blog - AURA Derma Clinic','اقرأ أحدث المقالات والنصائح الطبية من خبراء عيادة أورا ديرما للجلدية والتجميل.','Read the latest articles and medical tips from AURA Derma Clinic dermatology and aesthetics experts.','dermatology blog, skin care tips, medical articles, مدونة طبية, نصائح للبشرة',NULL,NULL,1,NULL,'2026-02-18 15:47:46','2026-02-18 15:47:46');
/*!40000 ALTER TABLE `seo_pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_categories`
--

DROP TABLE IF EXISTS `service_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name_ar` varchar(255) NOT NULL,
  `name_en` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `service_categories_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_categories`
--

LOCK TABLES `service_categories` WRITE;
/*!40000 ALTER TABLE `service_categories` DISABLE KEYS */;
INSERT INTO `service_categories` VALUES (1,'خدمات الجلدية','Dermatology','dermatology',1,'2026-02-16 04:27:58','2026-02-16 04:27:58'),(2,'إزالة الشعر بالليزر','Laser Hair Removal','laser',2,'2026-02-16 04:27:58','2026-02-16 04:27:58'),(3,'العناية بالبشرة والتجميل','Skincare & Aesthetics','skincare',3,'2026-02-16 04:27:58','2026-02-16 04:27:58'),(4,'العلاجات التجديدية','Regenerative Treatments','regenerative',4,'2026-02-16 04:27:58','2026-02-16 04:27:58'),(5,'فيلر','Filler','filler',5,'2026-03-08 12:05:25','2026-03-08 12:05:25'),(6,'بوتوكس','Botox','botox',6,'2026-03-08 12:05:25','2026-03-08 12:05:25'),(7,'سكن بوسترز','Skin Boosters','skin-boosters',7,'2026-03-08 12:15:40','2026-03-08 12:15:40'),(8,'ديرمابن','Dermapen','dermapen',8,'2026-03-08 12:34:45','2026-03-08 12:34:45'),(9,'تقشير','Peeling','peeling',9,'2026-03-08 12:44:17','2026-03-08 12:44:17'),(10,'هيدرافيشل','Hydrafacial','hydrafacial',10,'2026-03-08 12:45:42','2026-03-08 12:45:42');
/*!40000 ALTER TABLE `service_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_faqs`
--

DROP TABLE IF EXISTS `service_faqs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service_faqs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `service_id` bigint(20) unsigned NOT NULL,
  `question_ar` text NOT NULL,
  `question_en` text NOT NULL,
  `answer_ar` text NOT NULL,
  `answer_en` text NOT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `service_faqs_service_id_foreign` (`service_id`),
  CONSTRAINT `service_faqs_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_faqs`
--

LOCK TABLES `service_faqs` WRITE;
/*!40000 ALTER TABLE `service_faqs` DISABLE KEYS */;
/*!40000 ALTER TABLE `service_faqs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_gallery`
--

DROP TABLE IF EXISTS `service_gallery`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service_gallery` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `service_id` bigint(20) unsigned NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `caption_ar` varchar(255) DEFAULT NULL,
  `caption_en` varchar(255) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `service_gallery_service_id_foreign` (`service_id`),
  CONSTRAINT `service_gallery_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_gallery`
--

LOCK TABLES `service_gallery` WRITE;
/*!40000 ALTER TABLE `service_gallery` DISABLE KEYS */;
/*!40000 ALTER TABLE `service_gallery` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_packages`
--

DROP TABLE IF EXISTS `service_packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service_packages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` bigint(20) unsigned NOT NULL,
  `service_id` bigint(20) unsigned DEFAULT NULL,
  `total_sessions` int(11) NOT NULL,
  `completed_sessions` int(11) NOT NULL DEFAULT 0,
  `total_price` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `final_price` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('active','completed','cancelled') NOT NULL DEFAULT 'active',
  `notes` text,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `service_packages_patient_id_foreign` (`patient_id`),
  KEY `service_packages_service_id_foreign` (`service_id`),
  KEY `service_packages_created_by_foreign` (`created_by`),
  CONSTRAINT `service_packages_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `service_packages_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `service_packages_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_packages`
--

LOCK TABLES `service_packages` WRITE;
/*!40000 ALTER TABLE `service_packages` DISABLE KEYS */;
/*!40000 ALTER TABLE `service_packages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_supplies`
--

DROP TABLE IF EXISTS `service_supplies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service_supplies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `service_id` bigint(20) unsigned NOT NULL,
  `supply_id` bigint(20) unsigned NOT NULL,
  `quantity_per_session` decimal(10,2) NOT NULL DEFAULT 1.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `service_supplies_service_id_supply_id_unique` (`service_id`,`supply_id`),
  KEY `service_supplies_supply_id_foreign` (`supply_id`),
  CONSTRAINT `service_supplies_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE,
  CONSTRAINT `service_supplies_supply_id_foreign` FOREIGN KEY (`supply_id`) REFERENCES `supplies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_supplies`
--

LOCK TABLES `service_supplies` WRITE;
/*!40000 ALTER TABLE `service_supplies` DISABLE KEYS */;
INSERT INTO `service_supplies` VALUES (1,21,21,1.00,'2026-03-08 12:06:35','2026-03-08 12:06:35'),(2,21,29,2.00,'2026-03-08 12:06:35','2026-03-08 12:06:35'),(3,22,22,1.00,'2026-03-08 12:06:35','2026-03-08 12:06:35'),(4,22,29,2.00,'2026-03-08 12:06:35','2026-03-08 12:06:35'),(5,23,23,1.00,'2026-03-08 12:06:35','2026-03-08 12:06:35'),(6,23,29,2.00,'2026-03-08 12:06:35','2026-03-08 12:06:35'),(7,24,24,1.00,'2026-03-08 12:06:35','2026-03-08 12:06:35'),(8,24,29,2.00,'2026-03-08 12:06:35','2026-03-08 12:06:35'),(9,25,25,1.00,'2026-03-08 12:06:35','2026-03-08 12:06:35'),(10,25,28,2.00,'2026-03-08 12:06:35','2026-03-08 12:06:35'),(11,25,29,2.00,'2026-03-08 12:06:35','2026-03-08 12:06:35'),(12,26,26,1.00,'2026-03-08 12:06:35','2026-03-08 12:06:35'),(13,26,28,2.00,'2026-03-08 12:06:35','2026-03-08 12:06:35'),(14,26,29,2.00,'2026-03-08 12:06:35','2026-03-08 12:06:35'),(15,27,27,1.00,'2026-03-08 12:06:35','2026-03-08 12:06:35'),(16,27,28,2.00,'2026-03-08 12:06:35','2026-03-08 12:06:35'),(17,27,29,2.00,'2026-03-08 12:06:35','2026-03-08 12:06:35'),(18,28,30,1.00,'2026-03-08 12:15:40','2026-03-08 12:15:40'),(19,28,29,2.00,'2026-03-08 12:15:40','2026-03-08 12:15:40'),(20,29,31,1.00,'2026-03-08 12:15:40','2026-03-08 12:15:40'),(21,29,29,2.00,'2026-03-08 12:15:40','2026-03-08 12:15:40'),(22,30,32,1.00,'2026-03-08 12:15:40','2026-03-08 12:15:40'),(23,30,29,2.00,'2026-03-08 12:15:40','2026-03-08 12:15:40'),(24,31,33,1.00,'2026-03-08 12:15:40','2026-03-08 12:15:40'),(25,31,29,2.00,'2026-03-08 12:15:40','2026-03-08 12:15:40'),(26,32,34,1.00,'2026-03-08 12:15:40','2026-03-08 12:15:40'),(27,32,29,2.00,'2026-03-08 12:15:40','2026-03-08 12:15:40'),(28,33,35,1.00,'2026-03-08 12:15:40','2026-03-08 12:15:40'),(29,33,29,2.00,'2026-03-08 12:15:40','2026-03-08 12:15:40'),(30,34,36,1.00,'2026-03-08 12:15:40','2026-03-08 12:15:40'),(31,34,29,2.00,'2026-03-08 12:15:40','2026-03-08 12:15:40'),(32,35,37,1.00,'2026-03-08 12:15:40','2026-03-08 12:15:40'),(33,35,29,2.00,'2026-03-08 12:15:40','2026-03-08 12:15:40'),(34,36,38,1.00,'2026-03-08 12:34:45','2026-03-08 12:34:45'),(35,36,39,1.00,'2026-03-08 12:34:45','2026-03-08 12:34:45'),(36,37,38,1.00,'2026-03-08 12:34:45','2026-03-08 12:34:45'),(37,37,39,1.00,'2026-03-08 12:34:45','2026-03-08 12:34:45'),(38,37,40,1.00,'2026-03-08 12:34:45','2026-03-08 12:34:45'),(39,37,28,3.00,'2026-03-08 12:34:45','2026-03-08 12:34:45'),(40,37,41,1.00,'2026-03-08 12:34:45','2026-03-08 12:34:45'),(41,38,38,1.00,'2026-03-08 12:34:45','2026-03-08 12:34:45'),(42,38,39,1.00,'2026-03-08 12:34:45','2026-03-08 12:34:45'),(43,38,40,1.00,'2026-03-08 12:34:45','2026-03-08 12:34:45'),(44,38,28,3.00,'2026-03-08 12:34:45','2026-03-08 12:34:45'),(45,38,41,1.00,'2026-03-08 12:34:45','2026-03-08 12:34:45'),(46,39,38,1.00,'2026-03-08 12:34:45','2026-03-08 12:34:45'),(47,39,39,1.00,'2026-03-08 12:34:45','2026-03-08 12:34:45'),(48,39,40,1.00,'2026-03-08 12:34:45','2026-03-08 12:34:45'),(49,39,28,3.00,'2026-03-08 12:34:45','2026-03-08 12:34:45'),(50,39,41,1.00,'2026-03-08 12:34:45','2026-03-08 12:34:45'),(51,40,38,1.00,'2026-03-08 12:34:45','2026-03-08 12:34:45'),(52,40,39,1.00,'2026-03-08 12:34:45','2026-03-08 12:34:45'),(53,40,42,1.00,'2026-03-08 12:34:45','2026-03-08 12:34:45'),(54,41,43,0.10,'2026-03-08 12:41:58','2026-03-08 12:41:58'),(55,42,44,1.00,'2026-03-08 12:45:42','2026-03-08 12:45:42'),(56,43,45,1.00,'2026-03-08 12:45:42','2026-03-08 12:45:42'),(57,44,46,1.00,'2026-03-08 12:45:42','2026-03-08 12:45:42');
/*!40000 ALTER TABLE `service_supplies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `services` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint(20) unsigned NOT NULL,
  `name_ar` varchar(255) NOT NULL,
  `name_en` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `short_desc_ar` text,
  `short_desc_en` text,
  `full_desc_ar` longtext,
  `full_desc_en` longtext,
  `icon` varchar(255) DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `benefits_ar` text,
  `benefits_en` text,
  `sessions_count` varchar(255) DEFAULT NULL,
  `results_ar` text,
  `results_en` text,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `price` decimal(10,2) DEFAULT NULL,
  `price_after_discount` decimal(10,2) DEFAULT NULL,
  `default_sessions` int(11) DEFAULT NULL,
  `session_duration_minutes` int(11) DEFAULT NULL,
  `supply_cost` decimal(10,2) DEFAULT 0.00,
  `medical_fee` decimal(10,2) DEFAULT NULL,
  `doctor_commission_percentage` decimal(5,2) DEFAULT NULL,
  `clinic_notes` text,
  `show_on_home` tinyint(1) NOT NULL DEFAULT 0,
  `show_on_website` tinyint(1) NOT NULL DEFAULT 1,
  `bookable` tinyint(1) NOT NULL DEFAULT 1,
  `seo_title_ar` varchar(255) DEFAULT NULL,
  `seo_title_en` varchar(255) DEFAULT NULL,
  `seo_desc_ar` text,
  `seo_desc_en` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `services_slug_unique` (`slug`),
  KEY `services_category_id_foreign` (`category_id`),
  CONSTRAINT `services_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `service_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services`
--

LOCK TABLES `services` WRITE;
/*!40000 ALTER TABLE `services` DISABLE KEYS */;
INSERT INTO `services` VALUES (1,1,'علاج حب الشباب وآثاره','Acne Treatment','acne-treatment','علاج شامل لحب الشباب بمختلف درجاته وإزالة آثاره باستخدام أحدث التقنيات الطبية. نقدم بروتوكولات علاجية مخصصة تشمل التقشير والليزر والأدوية الموضعية للحصول على بشرة صافية ونقية.','Comprehensive acne treatment for all severity levels using the latest medical technologies. We offer customized treatment protocols including peeling, laser, and topical medications for clear, radiant skin.','<h2>ما هو علاج حب الشباب؟</h2>\n<p>حب الشباب هو من أكثر المشاكل الجلدية شيوعاً ويصيب مختلف الفئات العمرية. في عيادة أورا ديرما، نقدم بروتوكولات علاجية متكاملة تستهدف أسباب حب الشباب من جذوره باستخدام أحدث التقنيات والأجهزة الطبية المعتمدة عالمياً.</p>\n<h2>كيف يعمل العلاج؟</h2>\n<p>يبدأ العلاج بتشخيص دقيق لنوع حب الشباب ودرجته، ثم يتم وضع خطة علاجية مخصصة قد تشمل التقشير الكيميائي، العلاج بالليزر، الأدوية الموضعية والفموية، وجلسات التنظيف العميق. نعمل على علاج الحبوب النشطة ومنع ظهورها مجدداً وإزالة آثارها.</p>\n<h2>لمن يناسب هذا العلاج؟</h2>\n<ul>\n<li>المراهقون والشباب الذين يعانون من حب الشباب</li>\n<li>البالغون الذين يعانون من حب الشباب الهرموني</li>\n<li>من يعانون من آثار وندبات حب الشباب القديمة</li>\n<li>أصحاب البشرة الدهنية المعرضة لظهور الحبوب</li>\n</ul>','<h2>What is Acne Treatment?</h2>\n<p>Acne is one of the most common skin conditions affecting people of all ages. At Aura Derma Clinic, we offer comprehensive treatment protocols that target the root causes of acne using the latest internationally approved medical technologies and devices.</p>\n<h2>How Does the Treatment Work?</h2>\n<p>Treatment begins with an accurate diagnosis of the acne type and severity, followed by a customized treatment plan that may include chemical peeling, laser therapy, topical and oral medications, and deep cleansing sessions. We work to treat active breakouts, prevent recurrence, and remove existing scars.</p>\n<h2>Who Is This Treatment For?</h2>\n<ul>\n<li>Teenagers and young adults suffering from acne</li>\n<li>Adults with hormonal acne</li>\n<li>Those with old acne scars and marks</li>\n<li>People with oily, acne-prone skin</li>\n</ul>',NULL,NULL,'تقليل ظهور حب الشباب بشكل ملحوظ\r\nإزالة آثار وندبات حب الشباب القديمة\r\nتنظيم إفراز الدهون في البشرة\r\nتحسين ملمس البشرة ونعومتها\r\nاستعادة الثقة بالنفس والمظهر','Significant reduction in acne breakouts\r\nRemoval of old acne scars and marks\r\nRegulation of skin oil production\r\nImproved skin texture and smoothness\r\nRestored self-confidence and appearance','6','انخفاض ملحوظ في الحبوب خلال أول أسبوعين\r\nتحسن واضح في ملمس البشرة بعد 4-6 جلسات\r\nتلاشي الندبات والآثار بعد إكمال البرنامج العلاجي\r\nبشرة أكثر صفاءً وإشراقاً','Noticeable reduction in breakouts within the first two weeks\r\nClear improvement in skin texture after 4-6 sessions\r\nFading of scars and marks after completing the treatment program\r\nClearer, more radiant skin',1,'active',500.00,NULL,6,30,80.00,420.00,35.00,NULL,1,1,0,NULL,NULL,NULL,NULL,'2026-02-16 04:27:58','2026-03-08 12:00:28'),(2,1,'علاج التصبغات والبقع الداكنة','Pigmentation Treatment','pigmentation-treatment','علاج فعّال للتصبغات الجلدية والبقع الداكنة والكلف باستخدام تقنيات متقدمة. نعمل على توحيد لون البشرة واستعادة إشراقتها الطبيعية من خلال جلسات متخصصة.','Effective treatment for skin pigmentation, dark spots, and melasma using advanced techniques. We work to even out skin tone and restore its natural radiance through specialized sessions.','<h2>ما هو علاج التصبغات؟</h2>\n<p>التصبغات الجلدية والبقع الداكنة تنتج عن زيادة إنتاج الميلانين في مناطق معينة من الجلد بسبب التعرض لأشعة الشمس أو التغيرات الهرمونية أو الالتهابات. في عيادة أورا ديرما، نستخدم أحدث التقنيات لعلاج جميع أنواع التصبغات بما فيها الكلف والنمش وبقع الشمس.</p>\n<h2>كيف يعمل العلاج؟</h2>\n<p>نعتمد على مزيج من العلاجات المتقدمة تشمل التقشير الكيميائي المتخصص، وأجهزة الليزر الحديثة، والكريمات الطبية الموضعية. يتم تصميم خطة العلاج بناءً على نوع التصبغ وعمقه ونوع البشرة لضمان أفضل النتائج مع الحفاظ على سلامة الجلد.</p>\n<h2>لمن يناسب هذا العلاج؟</h2>\n<ul>\n<li>من يعانون من الكلف الناتج عن التغيرات الهرمونية</li>\n<li>أصحاب البقع الداكنة الناتجة عن أشعة الشمس</li>\n<li>من يعانون من تصبغات ما بعد الالتهابات</li>\n<li>الراغبون في توحيد لون البشرة</li>\n</ul>','<h2>What is Pigmentation Treatment?</h2>\n<p>Skin pigmentation and dark spots result from excess melanin production in certain areas of the skin due to sun exposure, hormonal changes, or inflammation. At Aura Derma Clinic, we use the latest technologies to treat all types of pigmentation including melasma, freckles, and sunspots.</p>\n<h2>How Does the Treatment Work?</h2>\n<p>We rely on a combination of advanced treatments including specialized chemical peels, modern laser devices, and topical medical creams. The treatment plan is designed based on the type and depth of pigmentation and skin type to ensure the best results while maintaining skin safety.</p>\n<h2>Who Is This Treatment For?</h2>\n<ul>\n<li>Those suffering from melasma caused by hormonal changes</li>\n<li>People with sun-induced dark spots</li>\n<li>Those with post-inflammatory hyperpigmentation</li>\n<li>Anyone seeking to even out their skin tone</li>\n</ul>',NULL,'https://images.unsplash.com/photo-1505944270255-72b8c68c6a70?w=800&q=80','توحيد لون البشرة وتفتيح البقع الداكنة\nعلاج الكلف العميق والسطحي\nتحسين إشراقة البشرة ونضارتها\nتقليل ظهور النمش وبقع الشمس\nحماية البشرة من التصبغات المستقبلية','Even skin tone and lightening of dark spots\nTreatment of deep and superficial melasma\nImproved skin radiance and glow\nReduction of freckles and sunspots\nProtection against future pigmentation','5','تفتيح ملحوظ للبقع الداكنة بعد 3-4 جلسات\nتوحيد لون البشرة بشكل تدريجي\nبشرة أكثر إشراقاً ونضارة\nنتائج مستدامة مع العناية المنزلية المناسبة','Noticeable lightening of dark spots after 3-4 sessions\nGradual evening of skin tone\nBrighter, more radiant skin\nLong-lasting results with proper home care',2,'active',700.00,550.00,5,30,120.00,580.00,35.00,NULL,0,1,0,NULL,NULL,NULL,NULL,'2026-02-16 04:27:58','2026-03-08 12:00:28'),(3,1,'فحص الجلد بمنظار الجلد','Dermoscopy Examination','dermoscopy-examination','فحص دقيق للآفات الجلدية والشامات باستخدام منظار الجلد الرقمي عالي الدقة. يساعد الفحص في التشخيص المبكر للأمراض الجلدية ومتابعة التغيرات الجلدية بشكل دوري.','Precise examination of skin lesions and moles using high-resolution digital dermoscopy. This examination aids in early diagnosis of skin conditions and periodic monitoring of skin changes.','<h2>ما هو فحص منظار الجلد؟</h2>\n<p>فحص الديرموسكوبي هو تقنية تشخيصية غير جراحية تستخدم جهاز منظار الجلد الرقمي عالي الدقة لفحص الآفات الجلدية والشامات بتكبير يصل إلى 200 ضعف. يتيح هذا الفحص رؤية تفاصيل دقيقة غير مرئية بالعين المجردة مما يساعد في التشخيص الدقيق والمبكر.</p>\n<h2>كيف يتم الفحص؟</h2>\n<p>يقوم الطبيب المختص بتوجيه جهاز الديرموسكوب على المنطقة المراد فحصها لالتقاط صور مكبرة عالية الدقة. يتم تحليل الصور لتقييم بنية الآفة الجلدية ولونها ونمطها. الفحص غير مؤلم ولا يستغرق وقتاً طويلاً ويعتبر أداة أساسية في الكشف المبكر عن سرطان الجلد.</p>\n<h2>لمن يناسب هذا الفحص؟</h2>\n<ul>\n<li>من لديهم شامات متعددة أو متغيرة الشكل</li>\n<li>الأشخاص المعرضون لخطر سرطان الجلد</li>\n<li>من يلاحظون تغيرات في لون أو شكل الآفات الجلدية</li>\n<li>المتابعة الدورية لصحة الجلد</li>\n</ul>','<h2>What is Dermoscopy Examination?</h2>\n<p>Dermoscopy is a non-invasive diagnostic technique that uses a high-resolution digital dermatoscope to examine skin lesions and moles at up to 200x magnification. This examination reveals fine details invisible to the naked eye, enabling accurate and early diagnosis.</p>\n<h2>How Is the Examination Done?</h2>\n<p>The specialist directs the dermatoscope over the area to be examined, capturing high-resolution magnified images. The images are analyzed to assess the structure, color, and pattern of the skin lesion. The examination is painless, quick, and is considered an essential tool in early skin cancer detection.</p>\n<h2>Who Is This Examination For?</h2>\n<ul>\n<li>Those with multiple or changing moles</li>\n<li>People at higher risk of skin cancer</li>\n<li>Those noticing changes in color or shape of skin lesions</li>\n<li>Periodic skin health monitoring</li>\n</ul>',NULL,'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=800&q=80','تشخيص مبكر ودقيق للأمراض الجلدية\nفحص غير جراحي وغير مؤلم\nمتابعة دورية لتغيرات الشامات\nالكشف المبكر عن سرطان الجلد\nتوثيق رقمي للحالة الجلدية','Early and accurate diagnosis of skin conditions\nNon-invasive and painless examination\nPeriodic monitoring of mole changes\nEarly detection of skin cancer\nDigital documentation of skin condition','1','تقرير تشخيصي فوري ودقيق\nخطة متابعة مخصصة للحالة\nراحة البال من خلال الكشف المبكر\nتوثيق رقمي للمقارنة في الزيارات المستقبلية','Immediate and accurate diagnostic report\nCustomized follow-up plan for the condition\nPeace of mind through early detection\nDigital documentation for comparison in future visits',3,'active',300.00,NULL,1,20,10.00,290.00,30.00,NULL,0,1,0,NULL,NULL,NULL,NULL,'2026-02-16 04:27:58','2026-03-08 12:00:28'),(4,1,'جهاز اكزيمر لعلاج الصدفية والبهاق والثعلبة','Excimer Laser','excimer-laser','علاج متقدم بجهاز الإكزيمر ليزر لحالات الصدفية والبهاق والثعلبة. يعمل الجهاز على تحفيز الخلايا الصبغية وتقليل الالتهابات بدقة عالية دون التأثير على الأنسجة المحيطة.','Advanced treatment with Excimer laser for psoriasis, vitiligo, and alopecia areata. The device stimulates pigment cells and reduces inflammation with high precision without affecting surrounding tissues.','<h2>ما هو جهاز الإكزيمر ليزر؟</h2>\n<p>جهاز الإكزيمر ليزر هو تقنية علاجية متطورة تستخدم أشعة فوق بنفسجية مركزة بطول موجي 308 نانومتر لعلاج الأمراض الجلدية المزمنة مثل الصدفية والبهاق والثعلبة. يتميز الجهاز بدقته العالية في استهداف المناطق المصابة فقط دون التأثير على الجلد السليم المحيط.</p>\n<h2>كيف يعمل العلاج؟</h2>\n<p>يقوم الجهاز بتوجيه شعاع مركز من الأشعة فوق البنفسجية إلى المناطق المصابة، مما يحفز الخلايا الصبغية (الميلانوسايت) في حالات البهاق، ويثبط الجهاز المناعي الموضعي في حالات الصدفية والثعلبة. يتم تحديد عدد الجلسات وشدة الأشعة بناءً على نوع المرض ومساحة المنطقة المصابة.</p>\n<h2>لمن يناسب هذا العلاج؟</h2>\n<ul>\n<li>مرضى البهاق بمختلف درجاته</li>\n<li>مرضى الصدفية المحدودة المساحة</li>\n<li>من يعانون من الثعلبة</li>\n<li>حالات الأكزيما المزمنة المقاومة للعلاج التقليدي</li>\n</ul>','<h2>What is Excimer Laser?</h2>\n<p>The Excimer laser is an advanced therapeutic technology that uses focused ultraviolet light at a wavelength of 308 nanometers to treat chronic skin conditions such as psoriasis, vitiligo, and alopecia areata. The device is distinguished by its high precision in targeting only affected areas without impacting the surrounding healthy skin.</p>\n<h2>How Does the Treatment Work?</h2>\n<p>The device directs a focused beam of ultraviolet light to the affected areas, stimulating pigment cells (melanocytes) in vitiligo cases and suppressing the local immune response in psoriasis and alopecia areata. The number of sessions and light intensity are determined based on the condition type and affected area size.</p>\n<h2>Who Is This Treatment For?</h2>\n<ul>\n<li>Vitiligo patients of all severity levels</li>\n<li>Patients with limited-area psoriasis</li>\n<li>Those suffering from alopecia areata</li>\n<li>Chronic eczema cases resistant to conventional treatment</li>\n</ul>',NULL,'https://images.unsplash.com/photo-1609840114035-3c981b782dfe?w=800&q=80','علاج دقيق يستهدف المناطق المصابة فقط\nلا يؤثر على الأنسجة السليمة المحيطة\nنتائج فعالة في إعادة التصبغ للبهاق\nتقليل الالتهابات والحكة في الصدفية\nعلاج آمن ومعتمد عالمياً','Precise treatment targeting only affected areas\nNo impact on surrounding healthy tissue\nEffective results in re-pigmentation for vitiligo\nReduction of inflammation and itching in psoriasis\nSafe and internationally approved treatment','8','بداية ظهور التصبغ في مناطق البهاق بعد 6-8 جلسات\nتحسن ملحوظ في حالات الصدفية بعد 4-6 جلسات\nبدء نمو الشعر في مناطق الثعلبة\nنتائج تراكمية تتحسن مع استمرار الجلسات','Pigmentation begins appearing in vitiligo areas after 6-8 sessions\nNoticeable improvement in psoriasis cases after 4-6 sessions\nHair regrowth begins in alopecia areata areas\nCumulative results that improve with continued sessions',4,'active',600.00,500.00,8,20,50.00,550.00,30.00,NULL,0,1,0,NULL,NULL,NULL,NULL,'2026-02-16 04:27:58','2026-03-08 12:00:28'),(5,1,'الفحص بالأشعة فوق البنفسجية','Wood\'s Light Examination','woods-light-examination','فحص تشخيصي متخصص باستخدام ضوء وود للكشف عن الأمراض الجلدية الفطرية والبكتيرية والتصبغات غير المرئية. أداة تشخيصية أساسية لتحديد نوع المرض الجلدي بدقة.','Specialized diagnostic examination using Wood\'s light to detect fungal, bacterial skin diseases, and invisible pigmentation. An essential diagnostic tool for accurately identifying skin conditions.','<h2>ما هو فحص ضوء وود؟</h2>\n<p>فحص ضوء وود هو أداة تشخيصية تستخدم الأشعة فوق البنفسجية ذات الطول الموجي الطويل لفحص الجلد في غرفة مظلمة. يساعد هذا الفحص في الكشف عن العديد من الأمراض الجلدية التي قد لا تكون مرئية تحت الإضاءة العادية، حيث تظهر الآفات الجلدية المختلفة بألوان مميزة تحت ضوء وود.</p>\n<h2>كيف يتم الفحص؟</h2>\n<p>يتم إجراء الفحص في غرفة مظلمة حيث يوجه الطبيب ضوء وود على المنطقة المراد فحصها. تظهر الآفات الفطرية بلون فلوري مميز، بينما تظهر التصبغات بدرجات مختلفة حسب عمقها. يساعد الفحص في التمييز بين أنواع التصبغات والأمراض الفطرية والبكتيرية بدقة عالية.</p>\n<h2>لمن يناسب هذا الفحص؟</h2>\n<ul>\n<li>من يشتبه في إصابتهم بأمراض فطرية جلدية</li>\n<li>تشخيص أنواع التصبغات وتحديد عمقها</li>\n<li>الكشف عن الإصابات البكتيرية الجلدية</li>\n<li>تقييم حالات البهاق وتحديد مداها</li>\n</ul>','<h2>What is Wood\'s Light Examination?</h2>\n<p>Wood\'s light examination is a diagnostic tool that uses long-wave ultraviolet light to examine the skin in a dark room. This examination helps detect numerous skin conditions that may not be visible under normal lighting, as different skin lesions appear in distinctive colors under Wood\'s light.</p>\n<h2>How Is the Examination Done?</h2>\n<p>The examination is performed in a dark room where the doctor directs the Wood\'s light onto the area to be examined. Fungal lesions appear in a distinctive fluorescent color, while pigmentation shows in varying degrees depending on its depth. The examination helps accurately distinguish between types of pigmentation and fungal and bacterial diseases.</p>\n<h2>Who Is This Examination For?</h2>\n<ul>\n<li>Those suspected of having fungal skin infections</li>\n<li>Diagnosing types of pigmentation and determining their depth</li>\n<li>Detecting bacterial skin infections</li>\n<li>Evaluating vitiligo cases and determining their extent</li>\n</ul>',NULL,'https://images.unsplash.com/photo-1551190822-a9ce113ac100?w=800&q=80','تشخيص سريع ودقيق للأمراض الجلدية\nفحص غير مؤلم وآمن تماماً\nتحديد نوع التصبغ وعمقه بدقة\nالكشف عن الإصابات الفطرية غير المرئية\nمساعدة في وضع خطة العلاج المناسبة','Quick and accurate diagnosis of skin conditions\nCompletely painless and safe examination\nPrecise determination of pigmentation type and depth\nDetection of invisible fungal infections\nAssistance in developing the appropriate treatment plan','1','تقرير تشخيصي فوري وشامل\nتحديد نوع المرض الجلدي بدقة\nوضع خطة علاجية مبنية على تشخيص دقيق\nمتابعة تطور الحالة المرضية','Immediate and comprehensive diagnostic report\nAccurate identification of the skin condition type\nDevelopment of a treatment plan based on accurate diagnosis\nMonitoring of condition progression',5,'active',200.00,NULL,1,15,5.00,195.00,30.00,NULL,0,1,0,NULL,NULL,NULL,NULL,'2026-02-16 04:27:58','2026-03-08 12:00:28'),(6,1,'علاج السنط بالأمصال والكي','Wart Treatment','wart-treatment','علاج فعّال للسنط (الثآليل) باستخدام تقنيات متعددة تشمل الأمصال المناعية والكي الكهربائي والتبريد. نختار العلاج الأنسب حسب نوع وحجم وموقع السنط.','Effective wart treatment using multiple techniques including immunotherapy serums, electrocautery, and cryotherapy. We select the most suitable treatment based on the type, size, and location of the wart.','<h2>ما هو علاج السنط؟</h2>\n<p>السنط أو الثآليل هي نتوءات جلدية تسببها فيروسات الورم الحليمي البشري (HPV). تظهر في أماكن مختلفة من الجسم وقد تكون مؤلمة أو مزعجة من الناحية الجمالية. في عيادة أورا ديرما نقدم حلولاً متعددة وفعّالة للتخلص من السنط نهائياً.</p>\n<h2>كيف يعمل العلاج؟</h2>\n<p>نوفر عدة طرق علاجية تشمل العلاج بالأمصال المناعية التي تحفز الجهاز المناعي لمحاربة الفيروس، والكي الكهربائي لإزالة السنط بدقة، والعلاج بالتبريد باستخدام النيتروجين السائل. يختار الطبيب الأسلوب الأنسب بناءً على نوع السنط وحجمه وموقعه وعمر المريض.</p>\n<h2>لمن يناسب هذا العلاج؟</h2>\n<ul>\n<li>من يعانون من الثآليل في اليدين أو القدمين</li>\n<li>حالات السنط المسطح في الوجه</li>\n<li>السنط التناسلي</li>\n<li>الثآليل المتكررة التي لم تستجب للعلاجات المنزلية</li>\n</ul>','<h2>What is Wart Treatment?</h2>\n<p>Warts are skin growths caused by human papillomavirus (HPV). They appear in various body locations and can be painful or cosmetically bothersome. At Aura Derma Clinic, we offer multiple effective solutions for permanent wart removal.</p>\n<h2>How Does the Treatment Work?</h2>\n<p>We provide several treatment methods including immunotherapy serums that stimulate the immune system to fight the virus, electrocautery for precise wart removal, and cryotherapy using liquid nitrogen. The doctor selects the most suitable approach based on the wart type, size, location, and patient age.</p>\n<h2>Who Is This Treatment For?</h2>\n<ul>\n<li>Those with warts on hands or feet</li>\n<li>Flat wart cases on the face</li>\n<li>Genital warts</li>\n<li>Recurring warts that haven\'t responded to home treatments</li>\n</ul>',NULL,'https://images.unsplash.com/photo-1579684385127-1ef15d508118?w=800&q=80','إزالة فعّالة ونهائية للسنط\nتعدد الخيارات العلاجية المتاحة\nتحفيز المناعة الذاتية ضد الفيروس\nعلاج آمن مع حد أدنى من الندبات\nمنع انتشار السنط لمناطق أخرى','Effective and permanent wart removal\nMultiple treatment options available\nStimulation of natural immunity against the virus\nSafe treatment with minimal scarring\nPrevention of wart spread to other areas','3','زوال السنط بعد 1-3 جلسات حسب الحجم\nشفاء المنطقة المعالجة خلال أسبوعين\nتقليل احتمالية عودة السنط\nمظهر طبيعي للجلد بعد العلاج','Wart disappearance after 1-3 sessions depending on size\nHealing of the treated area within two weeks\nReduced likelihood of wart recurrence\nNatural skin appearance after treatment',6,'active',400.00,350.00,3,20,60.00,340.00,35.00,NULL,0,1,0,NULL,NULL,NULL,NULL,'2026-02-16 04:27:58','2026-03-08 12:00:28'),(7,2,'ليزر الجسم الكامل','Full Body Laser','full-body-laser','إزالة الشعر بالليزر لكامل الجسم باستخدام أحدث أجهزة الليزر المعتمدة عالمياً. نتائج فعّالة وآمنة لجميع أنواع البشرة مع برنامج جلسات مخصص لتحقيق أفضل النتائج.','Full body laser hair removal using the latest internationally certified laser devices. Effective and safe results for all skin types with a customized session program for optimal outcomes.','<h2>ما هو ليزر الجسم الكامل؟</h2>\n<p>إزالة الشعر بالليزر للجسم الكامل هي إجراء تجميلي متقدم يستهدف بصيلات الشعر في جميع مناطق الجسم باستخدام أجهزة ليزر عالية التقنية. في عيادة أورا ديرما نستخدم أحدث الأجهزة المعتمدة عالمياً والتي تناسب جميع ألوان البشرة وأنواع الشعر.</p>\n<h2>كيف يعمل العلاج؟</h2>\n<p>يعمل الليزر عن طريق إرسال نبضات ضوئية مركزة تستهدف صبغة الميلانين في بصيلات الشعر، مما يؤدي إلى تدمير البصيلة ومنع نمو الشعر مجدداً. يتم ضبط إعدادات الجهاز حسب لون البشرة وسماكة الشعر لضمان أقصى فعالية مع أعلى درجات الأمان.</p>\n<h2>لمن يناسب هذا العلاج؟</h2>\n<ul>\n<li>الراغبون في التخلص الدائم من شعر الجسم</li>\n<li>من يعانون من نمو الشعر الكثيف أو المزعج</li>\n<li>أصحاب البشرة الحساسة الذين يعانون من تهيج الحلاقة</li>\n<li>جميع أنواع وألوان البشرة</li>\n</ul>','<h2>What is Full Body Laser?</h2>\n<p>Full body laser hair removal is an advanced cosmetic procedure that targets hair follicles across all body areas using high-tech laser devices. At Aura Derma Clinic, we use the latest internationally certified devices suitable for all skin tones and hair types.</p>\n<h2>How Does the Treatment Work?</h2>\n<p>The laser works by sending focused light pulses that target melanin pigment in hair follicles, destroying the follicle and preventing hair regrowth. Device settings are adjusted according to skin color and hair thickness to ensure maximum effectiveness with the highest safety standards.</p>\n<h2>Who Is This Treatment For?</h2>\n<ul>\n<li>Those seeking permanent body hair removal</li>\n<li>People with thick or bothersome hair growth</li>\n<li>Those with sensitive skin who experience shaving irritation</li>\n<li>All skin types and tones</li>\n</ul>',NULL,'https://images.unsplash.com/photo-1598524374912-6b0b0bab3da4?w=800&q=80','إزالة دائمة للشعر غير المرغوب فيه\nمناسب لجميع أنواع وألوان البشرة\nجلسات سريعة ومريحة\nتوفير الوقت والجهد على المدى الطويل\nبشرة ناعمة وخالية من الشعر','Permanent removal of unwanted hair\nSuitable for all skin types and tones\nQuick and comfortable sessions\nLong-term time and effort savings\nSmooth, hair-free skin','8','انخفاض ملحوظ في نمو الشعر بعد 3 جلسات\nنتائج مثالية بعد 6-8 جلسات\nبشرة ناعمة وخالية من الشعر\nنتائج دائمة مع جلسات صيانة سنوية','Noticeable reduction in hair growth after 3 sessions\nOptimal results after 6-8 sessions\nSmooth, hair-free skin\nPermanent results with annual maintenance sessions',1,'active',3500.00,2800.00,8,90,250.00,3250.00,25.00,NULL,1,1,0,NULL,NULL,NULL,NULL,'2026-02-16 04:27:58','2026-03-08 12:00:28'),(8,2,'ليزر المناطق الحساسة','Sensitive Areas Laser','sensitive-areas-laser','إزالة الشعر بالليزر للمناطق الحساسة بتقنية آمنة ومريحة. نستخدم أجهزة مخصصة ذات إعدادات دقيقة تناسب البشرة الحساسة مع ضمان الخصوصية والراحة التامة.','Laser hair removal for sensitive areas with safe and comfortable technology. We use specialized devices with precise settings suitable for sensitive skin while ensuring complete privacy and comfort.','<h2>ما هو ليزر المناطق الحساسة؟</h2>\n<p>إزالة الشعر بالليزر في المناطق الحساسة يتطلب خبرة عالية وأجهزة متخصصة توفر أقصى فعالية مع الحفاظ على سلامة وراحة البشرة الحساسة. في عيادة أورا ديرما نوفر بيئة مريحة وخاصة مع فريق طبي متمرس يضمن أفضل تجربة علاجية.</p>\n<h2>كيف يعمل العلاج؟</h2>\n<p>نستخدم أجهزة ليزر متطورة بإعدادات مخصصة للمناطق الحساسة، حيث يتم ضبط الطاقة والنبضات بدقة لتناسب رقة الجلد في هذه المناطق. يتضمن العلاج نظام تبريد متقدم يقلل من الإحساس بالحرارة ويضمن راحة المريض خلال الجلسة.</p>\n<h2>لمن يناسب هذا العلاج؟</h2>\n<ul>\n<li>الراغبون في إزالة شعر المناطق الحساسة بشكل دائم</li>\n<li>من يعانون من تهيج وحساسية من طرق الإزالة التقليدية</li>\n<li>من يبحثون عن حل مريح وطويل الأمد</li>\n<li>جميع أنواع البشرة</li>\n</ul>','<h2>What is Sensitive Areas Laser?</h2>\n<p>Laser hair removal in sensitive areas requires high expertise and specialized devices that provide maximum effectiveness while maintaining the safety and comfort of sensitive skin. At Aura Derma Clinic, we provide a comfortable and private environment with an experienced medical team ensuring the best treatment experience.</p>\n<h2>How Does the Treatment Work?</h2>\n<p>We use advanced laser devices with settings customized for sensitive areas, where energy and pulses are precisely adjusted to suit the delicate skin in these areas. The treatment includes an advanced cooling system that reduces heat sensation and ensures patient comfort during the session.</p>\n<h2>Who Is This Treatment For?</h2>\n<ul>\n<li>Those seeking permanent hair removal in sensitive areas</li>\n<li>People experiencing irritation from traditional removal methods</li>\n<li>Those looking for a comfortable, long-lasting solution</li>\n<li>All skin types</li>\n</ul>',NULL,'https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?w=800&q=80','إزالة فعّالة وآمنة للشعر في المناطق الحساسة\nأجهزة مخصصة ذات نظام تبريد متقدم\nخصوصية تامة وراحة أثناء الجلسة\nتقليل التهيج والحساسية\nنتائج طويلة الأمد','Effective and safe hair removal in sensitive areas\nSpecialized devices with advanced cooling system\nComplete privacy and comfort during sessions\nReduced irritation and sensitivity\nLong-lasting results','7','تقليل كبير في نمو الشعر بعد 3 جلسات\nنتائج مثالية بعد 6-8 جلسات\nراحة من التهيج الناتج عن الحلاقة\nبشرة ناعمة ونظيفة','Significant reduction in hair growth after 3 sessions\nOptimal results after 6-8 sessions\nRelief from shaving-related irritation\nSmooth, clean skin',2,'active',1000.00,800.00,7,25,60.00,940.00,25.00,NULL,0,1,0,NULL,NULL,NULL,NULL,'2026-02-16 04:27:58','2026-03-08 12:00:28'),(9,2,'ليزر الوجه','Facial Laser','facial-laser','إزالة شعر الوجه بالليزر بدقة عالية وأمان تام للبشرة. تقنية متطورة تستهدف بصيلات الشعر دون التأثير على البشرة المحيطة للحصول على وجه ناعم وخالٍ من الشعر.','Precise and safe facial laser hair removal. Advanced technology targets hair follicles without affecting surrounding skin for a smooth, hair-free face.','<h2>ما هو ليزر الوجه؟</h2>\n<p>إزالة شعر الوجه بالليزر هو إجراء تجميلي دقيق يستهدف الشعر غير المرغوب فيه في منطقة الوجه بما يشمل الذقن والشفة العليا والسوالف والجبهة. نستخدم أجهزة ليزر متخصصة بدقة عالية تضمن نتائج ممتازة مع الحفاظ على نعومة وسلامة بشرة الوجه.</p>\n<h2>كيف يعمل العلاج؟</h2>\n<p>يتم استخدام جهاز ليزر بإعدادات مخصصة لبشرة الوجه الرقيقة، حيث يستهدف بصيلات الشعر بنبضات ضوئية دقيقة. يشمل العلاج نظام تبريد يحمي البشرة من أي تأثير حراري. تختلف عدد الجلسات المطلوبة حسب كثافة الشعر ولون البشرة.</p>\n<h2>لمن يناسب هذا العلاج؟</h2>\n<ul>\n<li>النساء اللواتي يعانين من شعر الوجه الزائد</li>\n<li>من يعانون من نمو الشعر الناتج عن التغيرات الهرمونية</li>\n<li>الراغبون في التخلص من شعر الذقن أو الشفة العليا</li>\n<li>جميع ألوان البشرة</li>\n</ul>','<h2>What is Facial Laser?</h2>\n<p>Facial laser hair removal is a precise cosmetic procedure targeting unwanted hair in the facial area including the chin, upper lip, sideburns, and forehead. We use specialized high-precision laser devices ensuring excellent results while maintaining facial skin smoothness and safety.</p>\n<h2>How Does the Treatment Work?</h2>\n<p>A laser device with settings customized for delicate facial skin is used, targeting hair follicles with precise light pulses. The treatment includes a cooling system that protects the skin from any thermal effects. The number of sessions required varies based on hair density and skin color.</p>\n<h2>Who Is This Treatment For?</h2>\n<ul>\n<li>Women with excess facial hair</li>\n<li>Those with hair growth caused by hormonal changes</li>\n<li>People wanting to remove chin or upper lip hair</li>\n<li>All skin tones</li>\n</ul>',NULL,'https://images.unsplash.com/photo-1512290923902-8a9f81dc236c?w=800&q=80','إزالة دقيقة لشعر الوجه غير المرغوب فيه\nحماية بشرة الوجه الرقيقة أثناء العلاج\nنتائج سريعة وملحوظة\nتوديع الحلاقة والشمع نهائياً\nبشرة وجه ناعمة ومشرقة','Precise removal of unwanted facial hair\nProtection of delicate facial skin during treatment\nQuick and noticeable results\nSay goodbye to shaving and waxing permanently\nSmooth, radiant facial skin','6','تقليل واضح في شعر الوجه بعد الجلسة الثانية\nنتائج مثالية بعد 5-7 جلسات\nوجه ناعم وخالٍ من الشعر\nتحسن في ملمس ومظهر بشرة الوجه','Clear reduction in facial hair after the second session\nOptimal results after 5-7 sessions\nSmooth, hair-free face\nImproved facial skin texture and appearance',3,'active',600.00,500.00,6,20,40.00,560.00,25.00,NULL,0,1,0,NULL,NULL,NULL,NULL,'2026-02-16 04:27:58','2026-03-08 12:00:28'),(10,3,'جلسات تنظيف البشرة العميق','Deep Skin Cleansing','deep-skin-cleansing','جلسات تنظيف عميق للبشرة لإزالة الشوائب والرؤوس السوداء وتجديد خلايا البشرة. تتضمن الجلسة تقشيراً وتنظيفاً بالبخار وقناعاً مغذياً لبشرة نقية ومشرقة.','Deep skin cleansing sessions to remove impurities, blackheads, and renew skin cells. The session includes exfoliation, steam cleansing, and a nourishing mask for pure, radiant skin.','<h2>ما هو التنظيف العميق للبشرة؟</h2>\n<p>تنظيف البشرة العميق هو إجراء تجميلي شامل يهدف إلى تنقية البشرة من الشوائب والأوساخ المتراكمة في المسام وإزالة الرؤوس السوداء والبيضاء. في عيادة أورا ديرما نقدم جلسات تنظيف متكاملة تجمع بين التقنيات الحديثة والمنتجات الطبية عالية الجودة.</p>\n<h2>كيف تتم الجلسة؟</h2>\n<p>تبدأ الجلسة بتنظيف البشرة بمنظف طبي مناسب لنوع البشرة، ثم يتم استخدام البخار لفتح المسام وتسهيل عملية التنظيف العميق. يلي ذلك استخراج الرؤوس السوداء والشوائب بأدوات معقمة ومتخصصة، ثم تطبيق تقشير لطيف وسيروم مغذي وقناع علاجي يناسب احتياجات البشرة.</p>\n<h2>لمن يناسب هذا العلاج؟</h2>\n<ul>\n<li>أصحاب البشرة الدهنية والمختلطة</li>\n<li>من يعانون من الرؤوس السوداء والمسام الواسعة</li>\n<li>الراغبون في تجديد وتنقية البشرة</li>\n<li>كإجراء دوري للحفاظ على صحة البشرة</li>\n</ul>','<h2>What is Deep Skin Cleansing?</h2>\n<p>Deep skin cleansing is a comprehensive cosmetic procedure aimed at purifying the skin from impurities and accumulated dirt in pores and removing blackheads and whiteheads. At Aura Derma Clinic, we offer complete cleansing sessions combining modern techniques with high-quality medical products.</p>\n<h2>How Is the Session Done?</h2>\n<p>The session begins with cleansing the skin using a medical cleanser suited to the skin type, then steam is used to open pores and facilitate deep cleansing. This is followed by extraction of blackheads and impurities with sterilized specialized tools, then application of gentle exfoliation, nourishing serum, and a therapeutic mask tailored to the skin\'s needs.</p>\n<h2>Who Is This Treatment For?</h2>\n<ul>\n<li>Those with oily and combination skin</li>\n<li>People with blackheads and enlarged pores</li>\n<li>Anyone seeking skin renewal and purification</li>\n<li>As a periodic routine to maintain skin health</li>\n</ul>',NULL,'https://images.unsplash.com/photo-1596755389378-c31d21fd1273?w=800&q=80','تنقية البشرة من الشوائب والأوساخ العميقة\nإزالة الرؤوس السوداء وتضييق المسام\nتجديد خلايا البشرة ونضارتها\nتحسين ملمس ومظهر البشرة\nتهيئة البشرة لامتصاص المنتجات العلاجية','Purification of skin from deep impurities and dirt\nRemoval of blackheads and pore minimization\nSkin cell renewal and radiance\nImproved skin texture and appearance\nPreparing skin for better absorption of treatment products','4','بشرة نقية ومشرقة فوراً بعد الجلسة\nمسام أصغر وأنظف\nملمس بشرة أنعم وأكثر نضارة\nتحسن مستمر مع الجلسات الدورية','Pure, radiant skin immediately after the session\nSmaller, cleaner pores\nSmoother, more radiant skin texture\nContinuous improvement with periodic sessions',1,'active',500.00,400.00,4,45,80.00,420.00,30.00,NULL,0,1,0,NULL,NULL,NULL,NULL,'2026-02-16 04:27:58','2026-03-08 12:00:28'),(11,3,'الهيدرافيشل','HydraFacial','hydrafacial','تقنية الهيدرافيشل المتطورة لتنظيف وترطيب وتجديد البشرة في جلسة واحدة. تجمع بين التنظيف العميق والتقشير اللطيف وضخ السيروم المغذي لبشرة مشرقة ونضرة فوراً.','Advanced HydraFacial technology for cleansing, hydrating, and rejuvenating skin in a single session. Combines deep cleansing, gentle exfoliation, and nourishing serum infusion for instantly radiant, glowing skin.','<h2>ما هو الهيدرافيشل؟</h2>\n<p>الهيدرافيشل هو علاج متطور للبشرة يجمع بين التنظيف العميق والتقشير اللطيف والترطيب المكثف في جلسة واحدة. تعتبر هذه التقنية من أكثر علاجات البشرة شعبية عالمياً لقدرتها على تحسين مظهر البشرة فوراً دون أي فترة نقاهة.</p>\n<h2>كيف يعمل العلاج؟</h2>\n<p>يعمل جهاز الهيدرافيشل عبر ثلاث خطوات رئيسية: أولاً التنظيف والتقشير اللطيف لإزالة الخلايا الميتة، ثم الشفط اللطيف لتنظيف المسام واستخراج الشوائب، وأخيراً ضخ سيروم مغذي غني بمضادات الأكسدة وحمض الهيالورونيك والببتيدات لتغذية وترطيب البشرة بعمق.</p>\n<h2>لمن يناسب هذا العلاج؟</h2>\n<ul>\n<li>جميع أنواع البشرة بما فيها الحساسة</li>\n<li>من يرغبون في إشراقة فورية قبل المناسبات</li>\n<li>أصحاب البشرة الجافة والباهتة</li>\n<li>من يعانون من بداية ظهور التجاعيد الدقيقة</li>\n</ul>','<h2>What is HydraFacial?</h2>\n<p>HydraFacial is an advanced skin treatment that combines deep cleansing, gentle exfoliation, and intensive hydration in a single session. This technique is one of the most popular skin treatments worldwide for its ability to instantly improve skin appearance without any downtime.</p>\n<h2>How Does the Treatment Work?</h2>\n<p>The HydraFacial device works through three main steps: first, gentle cleansing and exfoliation to remove dead cells, then gentle suction to clean pores and extract impurities, and finally infusion of nourishing serum rich in antioxidants, hyaluronic acid, and peptides to deeply nourish and hydrate the skin.</p>\n<h2>Who Is This Treatment For?</h2>\n<ul>\n<li>All skin types including sensitive skin</li>\n<li>Those wanting instant glow before events</li>\n<li>People with dry, dull skin</li>\n<li>Those with early fine lines and wrinkles</li>\n</ul>',NULL,'https://images.unsplash.com/photo-1616394584738-fc6e612e71b9?w=800&q=80','تنظيف عميق وترطيب مكثف في جلسة واحدة\nنتائج فورية دون فترة نقاهة\nمناسب لجميع أنواع البشرة\nتقليل المسام وتحسين ملمس البشرة\nتغذية البشرة بمضادات الأكسدة والفيتامينات','Deep cleansing and intensive hydration in one session\nInstant results with no downtime\nSuitable for all skin types\nPore reduction and improved skin texture\nNourishing skin with antioxidants and vitamins','4','بشرة مشرقة ونضرة فوراً بعد الجلسة\nترطيب عميق يدوم لأيام\nمسام أصغر وبشرة أنعم\nتحسن ملحوظ في لون البشرة وتوحيده','Instantly radiant, glowing skin after the session\nDeep hydration lasting for days\nSmaller pores and smoother skin\nNoticeable improvement in skin tone evenness',2,'active',1500.00,1200.00,4,45,200.00,1300.00,30.00,NULL,1,1,0,NULL,NULL,NULL,NULL,'2026-02-16 04:27:58','2026-03-08 12:00:28'),(12,3,'حقن البوتوكس والفيلر','Botox & Filler','botox-filler','حقن البوتوكس لعلاج التجاعيد وخطوط التعبير والفيلر لنفخ الشفاه وتحديد ملامح الوجه. نستخدم أجود المنتجات العالمية المعتمدة لنتائج طبيعية وآمنة تدوم طويلاً.','Botox injections for wrinkles and expression lines, and fillers for lip augmentation and facial contouring. We use the finest internationally certified products for natural, safe, and long-lasting results.','<h2>ما هي حقن البوتوكس والفيلر؟</h2>\n<p>البوتوكس هو بروتين نقي يعمل على إرخاء العضلات المسؤولة عن التجاعيد وخطوط التعبير، بينما الفيلر هو مادة حشو تعتمد غالباً على حمض الهيالورونيك لملء الخطوط وتحديد ملامح الوجه. في عيادة أورا ديرما نستخدم أفضل المنتجات العالمية المعتمدة لتحقيق نتائج طبيعية متناسقة.</p>\n<h2>كيف يعمل العلاج؟</h2>\n<p>يتم حقن البوتوكس في مناطق محددة لإرخاء العضلات المسببة للتجاعيد مثل خطوط الجبهة وحول العينين وبين الحاجبين. أما الفيلر فيتم حقنه لتعبئة الخطوط العميقة ونفخ الشفاه وتحديد الفك والذقن وملء الهالات السوداء. يقوم الطبيب بتصميم خطة الحقن بناءً على ملامح الوجه والنتيجة المرغوبة.</p>\n<h2>لمن يناسب هذا العلاج؟</h2>\n<ul>\n<li>من يعانون من تجاعيد الجبهة وخطوط التعبير</li>\n<li>الراغبون في نفخ الشفاه بشكل طبيعي</li>\n<li>من يريدون تحديد ملامح الوجه والذقن</li>\n<li>أصحاب الهالات السوداء والخطوط العميقة</li>\n</ul>','<h2>What are Botox and Filler Injections?</h2>\n<p>Botox is a purified protein that relaxes the muscles responsible for wrinkles and expression lines, while filler is a dermal filling substance typically based on hyaluronic acid to fill lines and define facial features. At Aura Derma Clinic, we use the best internationally certified products to achieve natural, harmonious results.</p>\n<h2>How Does the Treatment Work?</h2>\n<p>Botox is injected in specific areas to relax wrinkle-causing muscles such as forehead lines, crow\'s feet, and frown lines. Filler is injected to fill deep lines, augment lips, define the jawline and chin, and fill under-eye hollows. The doctor designs the injection plan based on facial features and desired results.</p>\n<h2>Who Is This Treatment For?</h2>\n<ul>\n<li>Those with forehead wrinkles and expression lines</li>\n<li>People seeking natural lip augmentation</li>\n<li>Those wanting facial and chin contouring</li>\n<li>People with dark circles and deep lines</li>\n</ul>',NULL,'https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?w=800&q=80','تقليل التجاعيد وخطوط التعبير بشكل فوري\nنفخ الشفاه وتحديد ملامح الوجه بشكل طبيعي\nنتائج سريعة وملحوظة\nمنتجات عالمية معتمدة وآمنة\nإجراء سريع بدون فترة نقاهة طويلة','Immediate reduction of wrinkles and expression lines\nNatural lip augmentation and facial contouring\nQuick and noticeable results\nInternationally certified and safe products\nQuick procedure with minimal downtime','1','نتائج فورية تتحسن خلال أسبوعين\nتأثير البوتوكس يدوم 4-6 أشهر\nتأثير الفيلر يدوم 8-18 شهراً\nمظهر أكثر شباباً ونضارة','Immediate results that improve within two weeks\nBotox effects last 4-6 months\nFiller effects last 8-18 months\nMore youthful and refreshed appearance',3,'active',3000.00,NULL,1,30,1000.00,2000.00,35.00,NULL,1,1,0,NULL,NULL,NULL,NULL,'2026-02-16 04:27:58','2026-03-08 12:00:28'),(13,3,'حقن النضارة','Glow Injections','glow-injections','حقن النضارة لتغذية البشرة من الداخل واستعادة حيويتها وإشراقها. تحتوي على مزيج من الفيتامينات وحمض الهيالورونيك ومضادات الأكسدة لبشرة صحية ومتوهجة.','Glow injections to nourish skin from within and restore its vitality and radiance. Contains a blend of vitamins, hyaluronic acid, and antioxidants for healthy, glowing skin.','<h2>ما هي حقن النضارة؟</h2>\n<p>حقن النضارة هي علاج تجميلي يهدف إلى تغذية البشرة من الداخل وإعادة الحيوية والإشراق لها. تحتوي الحقن على مزيج متوازن من حمض الهيالورونيك والفيتامينات ومضادات الأكسدة والأحماض الأمينية التي تعمل على ترطيب البشرة بعمق وتحفيز إنتاج الكولاجين.</p>\n<h2>كيف يعمل العلاج؟</h2>\n<p>يتم حقن مزيج المواد المغذية في طبقات الجلد الوسطى باستخدام إبر دقيقة جداً. تعمل هذه المواد على ترطيب البشرة من الداخل وتحفيز عمليات التجدد الخلوي وإنتاج الكولاجين. يمكن تطبيق الحقن على الوجه والرقبة واليدين ومنطقة أعلى الصدر.</p>\n<h2>لمن يناسب هذا العلاج؟</h2>\n<ul>\n<li>من يعانون من بشرة جافة وباهتة</li>\n<li>الراغبون في استعادة نضارة البشرة وتوهجها</li>\n<li>من يريدون تحسين مرونة وملمس البشرة</li>\n<li>كعلاج وقائي ضد علامات الشيخوخة المبكرة</li>\n</ul>','<h2>What are Glow Injections?</h2>\n<p>Glow injections are a cosmetic treatment aimed at nourishing the skin from within and restoring its vitality and radiance. The injections contain a balanced blend of hyaluronic acid, vitamins, antioxidants, and amino acids that deeply hydrate the skin and stimulate collagen production.</p>\n<h2>How Does the Treatment Work?</h2>\n<p>The nutrient blend is injected into the middle layers of the skin using very fine needles. These substances work to hydrate the skin from within and stimulate cellular renewal and collagen production. The injections can be applied to the face, neck, hands, and decollete area.</p>\n<h2>Who Is This Treatment For?</h2>\n<ul>\n<li>Those with dry, dull skin</li>\n<li>People seeking to restore skin radiance and glow</li>\n<li>Those wanting to improve skin elasticity and texture</li>\n<li>As a preventive treatment against early aging signs</li>\n</ul>',NULL,'https://images.unsplash.com/photo-1556228578-0d85b1a4d571?w=800&q=80','ترطيب عميق ومكثف للبشرة\nاستعادة النضارة والإشراق الطبيعي\nتحفيز إنتاج الكولاجين\nتحسين مرونة البشرة وملمسها\nتأخير ظهور علامات الشيخوخة','Deep and intensive skin hydration\nRestoration of natural radiance and glow\nStimulation of collagen production\nImproved skin elasticity and texture\nDelaying the appearance of aging signs','4','بشرة أكثر نضارة وترطيباً بعد الجلسة الأولى\nتحسن ملحوظ في إشراقة البشرة بعد 3 جلسات\nمرونة أفضل وملمس أنعم\nنتائج تراكمية تدوم عدة أشهر','More radiant and hydrated skin after the first session\nNoticeable improvement in skin glow after 3 sessions\nBetter elasticity and smoother texture\nCumulative results lasting several months',4,'active',2000.00,1700.00,3,30,500.00,1500.00,30.00,NULL,0,1,0,NULL,NULL,NULL,NULL,'2026-02-16 04:27:58','2026-03-08 12:00:28'),(14,3,'جلسات الخيوط لشد الجلد','Thread Lift','thread-lift','تقنية الخيوط التجميلية لشد الوجه والرقبة بدون جراحة. خيوط طبية قابلة للذوبان تحفز إنتاج الكولاجين وتعيد تحديد ملامح الوجه بشكل طبيعي.','Thread lift technique for non-surgical face and neck lifting. Dissolvable medical threads stimulate collagen production and naturally redefine facial contours.','<h2>ما هي جلسات الخيوط؟</h2>\n<p>جلسات الخيوط التجميلية هي إجراء غير جراحي لشد الوجه والرقبة باستخدام خيوط طبية خاصة قابلة للذوبان. تعمل هذه الخيوط على رفع الأنسجة المترهلة وتحفيز إنتاج الكولاجين الطبيعي مما يمنح الوجه مظهراً أكثر شباباً وحيوية دون الحاجة للجراحة.</p>\n<h2>كيف يعمل العلاج؟</h2>\n<p>يتم إدخال خيوط طبية دقيقة تحت الجلد باستخدام إبر خاصة. تعمل هذه الخيوط على رفع وشد الأنسجة فوراً، وعلى المدى الطويل تحفز إنتاج الكولاجين حول الخيوط مما يعزز تأثير الشد. تذوب الخيوط تدريجياً خلال أشهر لكن تأثير الكولاجين المتكون يستمر لفترة أطول.</p>\n<h2>لمن يناسب هذا العلاج؟</h2>\n<ul>\n<li>من يعانون من ترهل خفيف إلى متوسط في الوجه</li>\n<li>الراغبون في شد الوجه بدون جراحة</li>\n<li>من يريدون إعادة تحديد خط الفك والذقن</li>\n<li>الباحثون عن بديل غير جراحي لعملية شد الوجه</li>\n</ul>','<h2>What is Thread Lift?</h2>\n<p>Thread lift is a non-surgical procedure for face and neck lifting using special dissolvable medical threads. These threads lift sagging tissues and stimulate natural collagen production, giving the face a more youthful and vibrant appearance without the need for surgery.</p>\n<h2>How Does the Treatment Work?</h2>\n<p>Fine medical threads are inserted under the skin using special needles. These threads immediately lift and tighten tissues, and over time stimulate collagen production around the threads, enhancing the lifting effect. The threads gradually dissolve over months, but the effect of the formed collagen lasts longer.</p>\n<h2>Who Is This Treatment For?</h2>\n<ul>\n<li>Those with mild to moderate facial sagging</li>\n<li>People seeking non-surgical face lifting</li>\n<li>Those wanting to redefine the jawline and chin</li>\n<li>Anyone looking for a non-surgical alternative to facelift surgery</li>\n</ul>',NULL,'https://images.unsplash.com/photo-1629909613654-28e377c37b09?w=800&q=80','شد فوري للوجه والرقبة بدون جراحة\nتحفيز إنتاج الكولاجين الطبيعي\nفترة نقاهة قصيرة جداً\nنتائج طبيعية ومتناسقة\nإعادة تحديد ملامح الوجه','Immediate face and neck lifting without surgery\nStimulation of natural collagen production\nVery short recovery period\nNatural and harmonious results\nRedefinition of facial contours','1','شد فوري ملحوظ بعد الجلسة\nتحسن تدريجي خلال 2-3 أشهر\nنتائج تدوم 12-18 شهراً\nمظهر أكثر شباباً وحيوية','Noticeable immediate lifting after the session\nGradual improvement over 2-3 months\nResults lasting 12-18 months\nMore youthful and vibrant appearance',5,'active',5000.00,NULL,1,60,1500.00,3500.00,35.00,NULL,0,1,0,NULL,NULL,NULL,NULL,'2026-02-16 04:27:58','2026-03-08 12:00:28'),(15,3,'التقشير الكيميائي والبارد','Chemical & Cold Peeling','chemical-cold-peeling','جلسات تقشير كيميائي وبارد لتجديد البشرة وعلاج التصبغات والبقع الداكنة. نستخدم تراكيز مدروسة تناسب نوع بشرتك لنتائج آمنة وفعّالة.','Chemical and cold peeling sessions for skin renewal and treatment of pigmentation and dark spots. We use carefully formulated concentrations suited to your skin type for safe, effective results.','<h2>ما هو التقشير الكيميائي والبارد؟</h2>\n<p>التقشير الكيميائي والبارد هو إجراء تجميلي يستخدم محاليل كيميائية مدروسة أو تقنية التقشير البارد لإزالة الطبقات التالفة من الجلد وتحفيز نمو خلايا جديدة صحية. في عيادة أورا ديرما نقدم مجموعة متنوعة من التقشيرات بتراكيز وعمق مختلف يناسب كل حالة.</p>\n<h2>كيف يعمل العلاج؟</h2>\n<p>في التقشير الكيميائي يتم تطبيق محلول حمضي على البشرة يعمل على إزالة الطبقات السطحية التالفة. أما التقشير البارد فيستخدم تركيبة خاصة تعمل على تقشير البشرة بلطف دون تقشر ظاهري، وهو مناسب أكثر للبشرة الحساسة والداكنة. يتم اختيار النوع والتركيز بناءً على مشكلة البشرة ونوعها.</p>\n<h2>لمن يناسب هذا العلاج؟</h2>\n<ul>\n<li>من يعانون من تصبغات وبقع داكنة</li>\n<li>أصحاب البشرة الباهتة وغير المتوحدة اللون</li>\n<li>من يريدون علاج آثار حب الشباب</li>\n<li>الراغبون في تجديد وتفتيح البشرة</li>\n</ul>','<h2>What is Chemical and Cold Peeling?</h2>\n<p>Chemical and cold peeling is a cosmetic procedure that uses carefully formulated chemical solutions or cold peeling technology to remove damaged skin layers and stimulate the growth of new healthy cells. At Aura Derma Clinic, we offer a variety of peels at different concentrations and depths suited to each condition.</p>\n<h2>How Does the Treatment Work?</h2>\n<p>In chemical peeling, an acid solution is applied to the skin to remove damaged surface layers. Cold peeling uses a special formula that gently exfoliates the skin without visible peeling, making it more suitable for sensitive and darker skin. The type and concentration are chosen based on the skin concern and type.</p>\n<h2>Who Is This Treatment For?</h2>\n<ul>\n<li>Those with pigmentation and dark spots</li>\n<li>People with dull, uneven skin tone</li>\n<li>Those wanting to treat acne scars</li>\n<li>Anyone seeking skin renewal and brightening</li>\n</ul>',NULL,'https://images.unsplash.com/photo-1487412947147-5cebf100ffc2?w=800&q=80','تجديد شامل لخلايا البشرة\nعلاج فعّال للتصبغات والبقع الداكنة\nتفتيح وتوحيد لون البشرة\nتحسين ملمس البشرة ونعومتها\nتحفيز إنتاج الكولاجين','Comprehensive skin cell renewal\nEffective treatment for pigmentation and dark spots\nSkin brightening and tone evening\nImproved skin texture and smoothness\nStimulation of collagen production','5','تحسن ملحوظ في لون البشرة بعد الجلسة الأولى\nتفتيح البقع الداكنة بعد 3-4 جلسات\nبشرة أكثر نعومة وإشراقاً\nنتائج مستمرة مع برنامج العناية المنزلي','Noticeable improvement in skin tone after the first session\nLightening of dark spots after 3-4 sessions\nSmoother, more radiant skin\nSustained results with home care program',6,'active',800.00,650.00,4,30,120.00,680.00,30.00,NULL,1,1,0,NULL,NULL,NULL,NULL,'2026-02-16 04:27:58','2026-03-08 12:00:28'),(16,3,'الديرما بن والميزوثيرابي','Dermapen & Mesotherapy','dermapen-mesotherapy','تقنية الديرما بن والميزوثيرابي لتحفيز الكولاجين وعلاج ندبات حب الشباب والمسام الواسعة. حقن دقيقة تجدد البشرة من الداخل وتحسّن ملمسها ومظهرها.','Dermapen and mesotherapy techniques for collagen stimulation and treatment of acne scars and enlarged pores. Micro-injections renew skin from within, improving its texture and appearance.','<h2>ما هو الديرما بن والميزوثيرابي؟</h2>\n<p>الديرما بن هو جهاز يستخدم إبراً دقيقة جداً لعمل ثقوب مجهرية في الجلد، مما يحفز عملية الشفاء الطبيعية وإنتاج الكولاجين. الميزوثيرابي هو تقنية حقن مواد علاجية في طبقات الجلد الوسطى. الجمع بين التقنيتين يحقق نتائج استثنائية في تجديد البشرة.</p>\n<h2>كيف يعمل العلاج؟</h2>\n<p>يتم استخدام جهاز الديرما بن لعمل قنوات دقيقة في الجلد، ثم يتم تطبيق سيروم علاجي مخصص يتم امتصاصه بعمق عبر هذه القنوات. تحفز العملية إنتاج الكولاجين والإيلاستين بشكل طبيعي مما يعيد بناء الجلد من الداخل. في جلسات الميزوثيرابي يتم حقن مزيج من الفيتامينات والمعادن ومضادات الأكسدة مباشرة في الجلد.</p>\n<h2>لمن يناسب هذا العلاج؟</h2>\n<ul>\n<li>من يعانون من ندبات حب الشباب</li>\n<li>أصحاب المسام الواسعة</li>\n<li>من يريدون تحسين ملمس ومرونة البشرة</li>\n<li>الراغبون في تجديد البشرة وتحفيز الكولاجين</li>\n</ul>','<h2>What is Dermapen and Mesotherapy?</h2>\n<p>Dermapen is a device that uses very fine needles to create microscopic punctures in the skin, stimulating the natural healing process and collagen production. Mesotherapy is a technique of injecting therapeutic substances into the middle layers of the skin. Combining both techniques achieves exceptional results in skin rejuvenation.</p>\n<h2>How Does the Treatment Work?</h2>\n<p>The Dermapen device creates fine channels in the skin, then a customized therapeutic serum is applied and deeply absorbed through these channels. The process naturally stimulates collagen and elastin production, rebuilding the skin from within. In mesotherapy sessions, a blend of vitamins, minerals, and antioxidants is injected directly into the skin.</p>\n<h2>Who Is This Treatment For?</h2>\n<ul>\n<li>Those with acne scars</li>\n<li>People with enlarged pores</li>\n<li>Those wanting to improve skin texture and elasticity</li>\n<li>Anyone seeking skin renewal and collagen stimulation</li>\n</ul>',NULL,'https://images.unsplash.com/photo-1573461160327-b450ce3d8e7f?w=800&q=80','تحفيز طبيعي لإنتاج الكولاجين والإيلاستين\nعلاج فعّال لندبات حب الشباب\nتقليل حجم المسام الواسعة\nتوصيل المواد العلاجية بعمق في الجلد\nتحسين شامل لملمس ومظهر البشرة','Natural stimulation of collagen and elastin production\nEffective treatment for acne scars\nReduction of enlarged pore size\nDeep delivery of therapeutic substances into the skin\nOverall improvement in skin texture and appearance','5','تحسن في ملمس البشرة بعد الجلسة الأولى\nتقليل ملحوظ للندبات بعد 3-4 جلسات\nمسام أصغر وبشرة أكثر نعومة\nنتائج تتحسن باستمرار على مدى الأشهر التالية','Improved skin texture after the first session\nNoticeable scar reduction after 3-4 sessions\nSmaller pores and smoother skin\nResults that continue to improve over the following months',7,'active',1200.00,1000.00,6,35,250.00,950.00,30.00,NULL,0,1,0,NULL,NULL,NULL,NULL,'2026-02-16 04:27:58','2026-03-08 12:00:28'),(17,3,'الفراكشنال ليزر','Fractional Laser','fractional-laser','تقنية الفراكشنال ليزر لعلاج ندبات حب الشباب وعلامات تمدد الجلد وتجديد البشرة. يعمل على تحفيز إنتاج الكولاجين وتحسين ملمس البشرة بشكل ملحوظ.','Fractional laser technology for treating acne scars, stretch marks, and skin rejuvenation. Stimulates collagen production and noticeably improves skin texture.','<h2>ما هو الفراكشنال ليزر؟</h2>\n<p>الفراكشنال ليزر هو تقنية متقدمة تستخدم أشعة الليزر المجزأة لعلاج مناطق صغيرة من الجلد في كل نبضة، مما يترك مناطق سليمة بينها تساعد في سرعة الشفاء. تعتبر هذه التقنية من أكثر العلاجات فعالية لندبات حب الشباب وعلامات التمدد وتجديد البشرة.</p>\n<h2>كيف يعمل العلاج؟</h2>\n<p>يعمل جهاز الفراكشنال ليزر عن طريق إرسال أعمدة دقيقة من الليزر تخترق طبقات محددة من الجلد، مما يحفز الاستجابة الشفائية الطبيعية وإنتاج الكولاجين الجديد. يتم التحكم في عمق وكثافة الليزر حسب المشكلة المراد علاجها ونوع البشرة لتحقيق أفضل النتائج بأقل فترة تعافي.</p>\n<h2>لمن يناسب هذا العلاج؟</h2>\n<ul>\n<li>من يعانون من ندبات حب الشباب العميقة</li>\n<li>أصحاب علامات تمدد الجلد (السترتش ماركس)</li>\n<li>من يريدون تجديد البشرة وتحسين ملمسها</li>\n<li>الراغبون في علاج التجاعيد الدقيقة</li>\n</ul>','<h2>What is Fractional Laser?</h2>\n<p>Fractional laser is an advanced technology that uses fractionated laser beams to treat small areas of skin with each pulse, leaving intact areas in between that aid in faster healing. This technique is considered one of the most effective treatments for acne scars, stretch marks, and skin rejuvenation.</p>\n<h2>How Does the Treatment Work?</h2>\n<p>The fractional laser device sends tiny columns of laser that penetrate specific skin layers, stimulating the natural healing response and new collagen production. The depth and intensity of the laser are controlled based on the condition being treated and skin type to achieve optimal results with minimal recovery time.</p>\n<h2>Who Is This Treatment For?</h2>\n<ul>\n<li>Those with deep acne scars</li>\n<li>People with stretch marks</li>\n<li>Those wanting skin rejuvenation and texture improvement</li>\n<li>Anyone seeking to treat fine wrinkles</li>\n</ul>',NULL,'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=800&q=80','علاج فعّال لندبات حب الشباب العميقة\nتقليل علامات تمدد الجلد\nتحفيز قوي لإنتاج الكولاجين\nتحسين ملحوظ في ملمس البشرة\nتجديد شامل للبشرة','Effective treatment for deep acne scars\nReduction of stretch marks\nPowerful stimulation of collagen production\nNoticeable improvement in skin texture\nComprehensive skin rejuvenation','4','تحسن ملحوظ في ملمس البشرة بعد 2-3 جلسات\nتقليل واضح في عمق الندبات\nبشرة أكثر نعومة وتوحداً\nنتائج تستمر في التحسن لمدة 6 أشهر بعد العلاج','Noticeable improvement in skin texture after 2-3 sessions\nClear reduction in scar depth\nSmoother, more even skin\nResults continue to improve for 6 months after treatment',8,'active',1500.00,1200.00,4,40,150.00,1350.00,30.00,NULL,0,1,0,NULL,NULL,NULL,NULL,'2026-02-16 04:27:58','2026-03-08 12:00:28'),(18,4,'جلسات البلازما PRP','PRP Therapy','prp-therapy','علاج بالبلازما الغنية بالصفائح الدموية لتجديد البشرة وعلاج تساقط الشعر. نستخلص البلازما من دم المريض ونحقنها لتحفيز النمو والتجدد الطبيعي للخلايا.','Platelet-rich plasma therapy for skin rejuvenation and hair loss treatment. We extract plasma from the patient\'s blood and inject it to stimulate natural cell growth and regeneration.','<h2>ما هي جلسات البلازما PRP؟</h2>\n<p>البلازما الغنية بالصفائح الدموية (PRP) هي علاج تجديدي يستخدم مكونات من دم المريض نفسه. يتم سحب كمية صغيرة من الدم ومعالجتها للحصول على بلازما مركزة غنية بعوامل النمو والصفائح الدموية التي تحفز الشفاء وتجديد الأنسجة.</p>\n<h2>كيف يعمل العلاج؟</h2>\n<p>يتم سحب عينة صغيرة من دم المريض ووضعها في جهاز الطرد المركزي لفصل مكونات الدم واستخلاص البلازما الغنية بالصفائح الدموية. ثم يتم حقن هذه البلازما في المناطق المستهدفة سواء في فروة الرأس لعلاج تساقط الشعر أو في الوجه لتجديد البشرة. تعمل عوامل النمو على تحفيز الخلايا الجذعية وتجديد الأنسجة بشكل طبيعي.</p>\n<h2>لمن يناسب هذا العلاج؟</h2>\n<ul>\n<li>من يعانون من تساقط الشعر بدرجاته المختلفة</li>\n<li>الراغبون في تجديد البشرة بشكل طبيعي</li>\n<li>من يبحثون عن علاج آمن من مكونات الجسم الطبيعية</li>\n<li>لتسريع شفاء الأنسجة بعد الإجراءات التجميلية</li>\n</ul>','<h2>What is PRP Therapy?</h2>\n<p>Platelet-Rich Plasma (PRP) is a regenerative treatment that uses components from the patient\'s own blood. A small amount of blood is drawn and processed to obtain concentrated plasma rich in growth factors and platelets that stimulate healing and tissue regeneration.</p>\n<h2>How Does the Treatment Work?</h2>\n<p>A small blood sample is drawn from the patient and placed in a centrifuge to separate blood components and extract the platelet-rich plasma. This plasma is then injected into targeted areas, whether the scalp for hair loss treatment or the face for skin rejuvenation. Growth factors stimulate stem cells and naturally regenerate tissues.</p>\n<h2>Who Is This Treatment For?</h2>\n<ul>\n<li>Those suffering from various degrees of hair loss</li>\n<li>People seeking natural skin rejuvenation</li>\n<li>Those looking for a safe treatment using the body\'s natural components</li>\n<li>For accelerating tissue healing after cosmetic procedures</li>\n</ul>',NULL,'https://images.unsplash.com/photo-1584362917165-526a968579e8?w=800&q=80','علاج طبيعي وآمن من مكونات الجسم\nتحفيز نمو الشعر ووقف التساقط\nتجديد البشرة وتحسين ملمسها\nتحفيز إنتاج الكولاجين بشكل طبيعي\nلا توجد مخاطر حساسية أو رفض','Natural and safe treatment from the body\'s own components\nStimulation of hair growth and stopping hair loss\nSkin rejuvenation and texture improvement\nNatural stimulation of collagen production\nNo risk of allergic reactions or rejection','6','تقليل ملحوظ في تساقط الشعر بعد 3 جلسات\nبدء نمو شعر جديد بعد 4-6 جلسات\nبشرة أكثر نضارة وحيوية\nنتائج تتحسن تدريجياً مع الجلسات المتتالية','Noticeable reduction in hair loss after 3 sessions\nNew hair growth begins after 4-6 sessions\nMore radiant and vibrant skin\nResults gradually improve with consecutive sessions',1,'active',1500.00,1200.00,4,45,350.00,1150.00,30.00,NULL,1,1,0,NULL,NULL,NULL,NULL,'2026-02-16 04:27:58','2026-03-08 12:00:28'),(19,4,'الخلايا الجذعية','Stem Cell Therapy','stem-cell-therapy','علاج متقدم بالخلايا الجذعية لتجديد البشرة ومكافحة علامات الشيخوخة. تقنية حديثة تعمل على إصلاح الأنسجة التالفة وتحفيز إنتاج الكولاجين لبشرة أكثر شباباً.','Advanced stem cell therapy for skin rejuvenation and anti-aging. Modern technology that repairs damaged tissues and stimulates collagen production for younger-looking skin.','<h2>ما هو علاج الخلايا الجذعية؟</h2>\n<p>علاج الخلايا الجذعية هو من أحدث التقنيات في مجال الطب التجديدي والتجميلي. يعتمد على استخدام الخلايا الجذعية وعوامل النمو المشتقة منها لتحفيز تجديد الأنسجة وإصلاح الخلايا التالفة. يعتبر هذا العلاج ثورة في مجال مكافحة الشيخوخة وتجديد البشرة والشعر.</p>\n<h2>كيف يعمل العلاج؟</h2>\n<p>يتم استخلاص عوامل النمو والبروتينات من الخلايا الجذعية وحقنها في المناطق المستهدفة. تعمل هذه العوامل على تحفيز الخلايا المحيطة للتجدد والإصلاح، وتعزيز إنتاج الكولاجين والإيلاستين، وتحسين الدورة الدموية الموضعية. يمكن استخدام العلاج للوجه والشعر وأي منطقة تحتاج للتجديد.</p>\n<h2>لمن يناسب هذا العلاج؟</h2>\n<ul>\n<li>من يعانون من علامات الشيخوخة المبكرة</li>\n<li>الراغبون في تجديد شامل للبشرة</li>\n<li>من يعانون من تساقط الشعر الشديد</li>\n<li>الباحثون عن أحدث تقنيات الطب التجديدي</li>\n</ul>','<h2>What is Stem Cell Therapy?</h2>\n<p>Stem cell therapy is one of the latest technologies in regenerative and aesthetic medicine. It relies on using stem cells and their derived growth factors to stimulate tissue regeneration and repair damaged cells. This treatment is considered a revolution in anti-aging, skin, and hair rejuvenation.</p>\n<h2>How Does the Treatment Work?</h2>\n<p>Growth factors and proteins are extracted from stem cells and injected into targeted areas. These factors stimulate surrounding cells to regenerate and repair, enhance collagen and elastin production, and improve local blood circulation. The treatment can be used for the face, hair, and any area requiring rejuvenation.</p>\n<h2>Who Is This Treatment For?</h2>\n<ul>\n<li>Those with early signs of aging</li>\n<li>People seeking comprehensive skin rejuvenation</li>\n<li>Those with severe hair loss</li>\n<li>Anyone looking for the latest regenerative medicine technologies</li>\n</ul>',NULL,'https://images.unsplash.com/photo-1532938911079-1b06ac7ceec7?w=800&q=80','تجديد شامل للخلايا والأنسجة\nمكافحة فعالة لعلامات الشيخوخة\nتحفيز إنتاج الكولاجين والإيلاستين\nعلاج متقدم لتساقط الشعر\nتحسين الدورة الدموية الموضعية','Comprehensive cell and tissue renewal\nEffective anti-aging treatment\nStimulation of collagen and elastin production\nAdvanced treatment for hair loss\nImproved local blood circulation','4','تحسن في نضارة البشرة بعد الجلسة الأولى\nتجديد واضح للبشرة بعد 3-4 جلسات\nتقوية بصيلات الشعر وتقليل التساقط\nبشرة أكثر شباباً ومرونة','Improved skin radiance after the first session\nClear skin renewal after 3-4 sessions\nStrengthened hair follicles and reduced hair loss\nMore youthful and elastic skin',2,'active',2500.00,2000.00,4,45,600.00,1900.00,30.00,NULL,0,1,0,NULL,NULL,NULL,NULL,'2026-02-16 04:27:58','2026-03-08 12:00:28'),(20,4,'كورس علاجي للشعر','Hair Treatment Course','hair-treatment-course','برنامج علاجي متكامل لمشاكل الشعر يشمل البلازما والميزوثيرابي والخلايا الجذعية. كورس مخصص حسب حالة الشعر لوقف التساقط وتحفيز نمو شعر صحي وقوي.','Comprehensive hair treatment program including PRP, mesotherapy, and stem cells. A customized course based on hair condition to stop hair loss and stimulate healthy, strong hair growth.','<h2>ما هو الكورس العلاجي للشعر؟</h2>\n<p>الكورس العلاجي للشعر هو برنامج متكامل ومخصص لعلاج مشاكل الشعر بشكل شامل. يجمع البرنامج بين عدة تقنيات علاجية متقدمة تشمل البلازما الغنية بالصفائح الدموية والميزوثيرابي والخلايا الجذعية لتحقيق أفضل النتائج في وقف تساقط الشعر وتحفيز نموه.</p>\n<h2>كيف يعمل العلاج؟</h2>\n<p>يبدأ البرنامج بتقييم شامل لحالة الشعر وفروة الرأس لتحديد أسباب التساقط ودرجته. بناءً على التقييم يتم وضع خطة علاجية مخصصة تتضمن جلسات البلازما لتحفيز البصيلات، وجلسات الميزوثيرابي لتغذية فروة الرأس بالفيتامينات والمعادن، وعلاج الخلايا الجذعية لتجديد البصيلات الضعيفة. يتم تنظيم الجلسات على مدى عدة أسابيع لتحقيق أقصى استفادة.</p>\n<h2>لمن يناسب هذا العلاج؟</h2>\n<ul>\n<li>من يعانون من تساقط الشعر الشديد أو المزمن</li>\n<li>حالات الصلع الوراثي في مراحله المبكرة</li>\n<li>من يعانون من ضعف وترقق الشعر</li>\n<li>النساء اللواتي يعانين من تساقط الشعر بعد الولادة أو بسبب الضغوط</li>\n</ul>','<h2>What is Hair Treatment Course?</h2>\n<p>The hair treatment course is a comprehensive, customized program for treating hair problems holistically. The program combines several advanced therapeutic techniques including platelet-rich plasma, mesotherapy, and stem cells to achieve the best results in stopping hair loss and stimulating growth.</p>\n<h2>How Does the Treatment Work?</h2>\n<p>The program begins with a comprehensive assessment of hair and scalp condition to determine the causes and degree of hair loss. Based on the assessment, a customized treatment plan is developed that includes PRP sessions to stimulate follicles, mesotherapy sessions to nourish the scalp with vitamins and minerals, and stem cell therapy to rejuvenate weakened follicles. Sessions are organized over several weeks for maximum benefit.</p>\n<h2>Who Is This Treatment For?</h2>\n<ul>\n<li>Those with severe or chronic hair loss</li>\n<li>Early-stage hereditary baldness cases</li>\n<li>People with weak and thinning hair</li>\n<li>Women experiencing postpartum or stress-related hair loss</li>\n</ul>',NULL,'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?w=800&q=80','برنامج علاجي شامل ومتكامل\nالجمع بين عدة تقنيات لنتائج أفضل\nخطة مخصصة حسب حالة كل مريض\nوقف تساقط الشعر وتحفيز النمو\nتقوية بصيلات الشعر وتغذيتها','Comprehensive and integrated treatment program\nCombination of multiple techniques for better results\nCustomized plan based on each patient\'s condition\nStopping hair loss and stimulating growth\nStrengthening and nourishing hair follicles','8','تقليل ملحوظ في تساقط الشعر خلال الأسابيع الأولى\nبدء نمو شعر جديد بعد إكمال نصف البرنامج\nشعر أكثر كثافة وقوة\nنتائج مستدامة مع جلسات المتابعة الدورية','Noticeable reduction in hair loss during the first weeks\nNew hair growth begins after completing half the program\nThicker, stronger hair\nSustainable results with periodic follow-up sessions',3,'active',1000.00,800.00,8,30,200.00,800.00,30.00,NULL,0,1,0,NULL,NULL,NULL,NULL,'2026-02-16 04:27:58','2026-03-08 12:00:28'),(21,5,'فيلر نيوفيا','Neauvia Filler','neauvia-filler',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'active',7000.00,NULL,NULL,NULL,3650.00,3350.00,NULL,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-03-08 12:06:14','2026-03-08 12:13:38'),(22,5,'فيلر سيلوسوم','Celesome Filler','celesome-filler',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,'active',5000.00,NULL,NULL,NULL,1750.00,3250.00,NULL,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-03-08 12:06:14','2026-03-08 12:13:38'),(23,5,'فيلر ايفانثيا','Evanthia Filler','evanthia-filler',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,3,'active',7000.00,NULL,NULL,NULL,3550.00,3450.00,NULL,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-03-08 12:06:14','2026-03-08 12:13:38'),(24,5,'فيلر ميفل','Mifill Filler','mifill-filler',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,4,'active',4000.00,NULL,NULL,NULL,1550.00,2450.00,NULL,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-03-08 12:06:14','2026-03-08 12:13:38'),(25,6,'بوتوكس زيومين','Xeomin Botox','xeomin-botox',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'active',5000.00,NULL,NULL,NULL,2700.00,2300.00,NULL,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-03-08 12:06:14','2026-03-08 12:13:38'),(26,6,'بوتوكس اليرجان','Allergan Botox (LE)','allergan-botox',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,'active',7000.00,NULL,NULL,NULL,5050.00,1950.00,NULL,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-03-08 12:06:14','2026-03-08 12:13:38'),(27,6,'بوتوكس جنتوكس','Gentox Botox','gentox-botox',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,3,'active',4000.00,NULL,NULL,NULL,2100.00,1900.00,NULL,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-03-08 12:06:14','2026-03-08 12:13:38'),(28,7,'بروفايلو','Profhilo','profhilo',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'active',6500.00,NULL,NULL,NULL,2750.00,3750.00,NULL,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-03-08 12:15:40','2026-03-08 12:15:40'),(29,7,'RRS لونج لاستنج','RRS Long Lasting','rrs-long-lasting',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,'active',6500.00,NULL,NULL,NULL,3850.00,2650.00,NULL,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-03-08 12:15:40','2026-03-08 12:15:40'),(30,7,'RRS HA Eyes','RRS HA Eyes','rrs-ha-eyes',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,3,'active',5500.00,NULL,NULL,NULL,2800.00,2700.00,NULL,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-03-08 12:15:40','2026-03-08 12:15:40'),(31,7,'ريتش','Rich','rich-skin-booster',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,4,'active',18000.00,NULL,NULL,NULL,14550.00,3450.00,NULL,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-03-08 12:15:40','2026-03-08 12:15:40'),(32,7,'راديس','Radiesse','radiesse',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,5,'active',15000.00,NULL,NULL,NULL,9550.00,5450.00,NULL,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-03-08 12:15:40','2026-03-08 12:15:40'),(33,7,'اوليديا','Olidia','olidia',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,6,'active',12000.00,NULL,NULL,NULL,6550.00,5450.00,NULL,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-03-08 12:15:40','2026-03-08 12:15:40'),(34,7,'سكلبترا','Sculptra','sculptra',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,7,'active',18000.00,NULL,NULL,NULL,11050.00,6950.00,NULL,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-03-08 12:15:40','2026-03-08 12:15:40'),(35,7,'ياقوت','Yakoot','yakoot',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,8,'active',7000.00,NULL,NULL,NULL,3550.00,3450.00,NULL,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-03-08 12:15:40','2026-03-08 12:15:40'),(36,8,'ديرمابن بدون بلازما','Dermapen without Plasma','dermapen-without-plasma',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'active',850.00,NULL,NULL,NULL,50.00,800.00,NULL,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-03-08 12:34:45','2026-03-08 12:34:45'),(37,8,'ديرمابن مع بلازما','Dermapen with Plasma','dermapen-with-plasma',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,'active',950.00,NULL,NULL,NULL,200.00,750.00,NULL,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-03-08 12:34:45','2026-03-08 12:34:45'),(38,8,'ديرمابن مع بلازما وميزوثيرابي','Dermapen with Plasma & Mesotherapy','dermapen-plasma-mesotherapy',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,3,'active',1100.00,NULL,NULL,NULL,200.00,900.00,NULL,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-03-08 12:34:45','2026-03-08 12:34:45'),(39,8,'ديرمابن مع بلازما وسبسجن','Dermapen with Plasma & Subcision','dermapen-plasma-subcision',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,4,'active',1200.00,NULL,NULL,NULL,200.00,1000.00,NULL,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-03-08 12:34:45','2026-03-08 12:34:45'),(40,8,'ديرمابن مع LC بدون بلازما','Dermapen with LC without Plasma','dermapen-lc-without-plasma',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,5,'active',1300.00,NULL,NULL,NULL,650.00,650.00,NULL,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-03-08 12:34:45','2026-03-08 12:34:45'),(41,9,'جرين بيل','Green Peel','green-peel',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'active',1200.00,NULL,NULL,NULL,550.00,650.00,NULL,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-03-08 12:41:58','2026-03-08 12:44:17'),(42,10,'هيدرافيشل مستوى 1','Hydrafacial Level 1','hydrafacial-level-1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'active',500.00,NULL,NULL,NULL,100.00,400.00,NULL,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-03-08 12:45:42','2026-03-08 12:45:42'),(43,10,'هيدرافيشل مستوى 2','Hydrafacial Level 2','hydrafacial-level-2',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,'active',750.00,NULL,NULL,NULL,150.00,600.00,NULL,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-03-08 12:45:42','2026-03-08 12:45:42'),(44,10,'هيدرافيشل مستوى 3','Hydrafacial Level 3','hydrafacial-level-3',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,3,'active',1000.00,NULL,NULL,NULL,250.00,750.00,NULL,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-03-08 12:45:42','2026-03-08 12:45:42'),(45,2,'ليزر - أندر أرم','Laser - Underarm','laser-underarm',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'active',150.00,NULL,NULL,NULL,0.00,150.00,NULL,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-03-08 12:49:04','2026-03-08 12:49:04'),(46,2,'ليزر - شنب','Laser - Mustache','laser-mustache',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'active',100.00,NULL,NULL,NULL,0.00,100.00,NULL,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-03-08 12:49:04','2026-03-08 12:49:04'),(47,2,'ليزر - شنب + ذقن','Laser - Mustache + Shin','laser-mustache-shin',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'active',150.00,NULL,NULL,NULL,0.00,150.00,NULL,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-03-08 12:49:04','2026-03-08 12:49:04'),(48,2,'ليزر - بيكيني','Laser - Bikini','laser-bikini',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'active',350.00,NULL,NULL,NULL,0.00,350.00,NULL,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-03-08 12:49:04','2026-03-08 12:49:04'),(49,2,'ليزر - وجه','Laser - Face','laser-face',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'active',250.00,NULL,NULL,NULL,0.00,250.00,NULL,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-03-08 12:49:04','2026-03-08 12:49:04'),(50,2,'ليزر - بيكيني + أندر أرم','Laser - Bikini + Underarms','laser-bikini-underarms',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'active',450.00,NULL,NULL,NULL,0.00,450.00,NULL,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-03-08 12:49:04','2026-03-08 12:49:04'),(51,2,'ليزر - نصف ذراع علوي','Laser - Upper Half Arm','laser-upper-half-arm',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'active',400.00,NULL,NULL,NULL,0.00,400.00,NULL,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-03-08 12:49:04','2026-03-08 12:49:04'),(52,2,'ليزر - نصف ذراع سفلي','Laser - Lower Half Arm','laser-lower-half-arm',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'active',350.00,NULL,NULL,NULL,0.00,350.00,NULL,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-03-08 12:49:04','2026-03-08 12:49:04'),(53,2,'ليزر - نصف ساق علوي','Laser - Upper Half Leg','laser-upper-half-leg',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'active',700.00,NULL,NULL,NULL,0.00,700.00,NULL,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-03-08 12:49:04','2026-03-08 12:49:04'),(54,2,'ليزر - نصف ساق سفلي','Laser - Lower Half Leg','laser-lower-half-leg',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'active',650.00,NULL,NULL,NULL,0.00,650.00,NULL,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-03-08 12:49:04','2026-03-08 12:49:04'),(55,2,'ليزر - ذراع كامل','Laser - Full Arm','laser-full-arm',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'active',700.00,NULL,NULL,NULL,0.00,700.00,NULL,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-03-08 12:49:04','2026-03-08 12:49:04'),(56,2,'ليزر - ساق كاملة','Laser - Full Leg','laser-full-leg',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'active',1200.00,NULL,NULL,NULL,0.00,1200.00,NULL,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-03-08 12:49:04','2026-03-08 12:49:04'),(57,2,'ليزر - بطن','Laser - Belly','laser-belly',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'active',500.00,NULL,NULL,NULL,0.00,500.00,NULL,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-03-08 12:49:04','2026-03-08 12:49:04'),(58,2,'ليزر - ظهر','Laser - Back','laser-back',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'active',600.00,NULL,NULL,NULL,0.00,600.00,NULL,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-03-08 12:49:04','2026-03-08 12:49:04'),(59,2,'ليزر - جسم كامل (بدون ظهر وبطن)','Laser - Full Body (w/o Back & Belly)','laser-full-body-wo-back-belly',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'active',2250.00,NULL,NULL,NULL,0.00,2250.00,NULL,NULL,0,0,1,NULL,NULL,NULL,NULL,'2026-03-08 12:49:04','2026-03-08 12:49:04');
/*!40000 ALTER TABLE `services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('JZ5Vm6VRgdbpr2CZEwX87oFJqH8e3SJMJ6ZxCILt',1,'127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15','YTo0OntzOjY6Il90b2tlbiI7czo0MDoieW9hbU9LRWEya3dCN2o3alppeUFuVVg4RlRUbmpiV3hUZE81a0ZxVCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJuZXciO2E6MDp7fXM6Mzoib2xkIjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzU6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hZG1pbi9kb2N0b3JzIjtzOjU6InJvdXRlIjtzOjE5OiJhZG1pbi5kb2N0b3JzLmluZGV4Ijt9czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTt9',1772995045);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` text,
  `group` varchar(255) NOT NULL DEFAULT 'general',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'phone_1','01007729159','contact','2026-02-16 04:27:58','2026-02-16 04:27:58'),(2,'phone_2','0238244047','contact','2026-02-16 04:27:58','2026-02-16 04:27:58'),(3,'whatsapp','01007729159','contact','2026-02-16 04:27:58','2026-02-16 04:27:58'),(4,'email','info@aura-clinic.net','contact','2026-02-16 04:27:58','2026-02-16 04:27:58'),(5,'address_ar','٦ أكتوبر - كايرو ميديكال سنتر - المحور المركزي - الدور الثاني - عيادة 71','contact','2026-02-16 04:27:58','2026-02-16 04:27:58'),(6,'address_en','CMC (Cairo Medical Center), Central Axis, 6th of October City, 2nd Floor, Clinic No. 71','contact','2026-02-16 04:27:58','2026-02-16 04:27:58'),(7,'google_maps','https://maps.app.goo.gl/AGMjNFK4ketaUnGH8','contact','2026-02-16 04:27:58','2026-02-16 04:27:58'),(8,'facebook','https://www.facebook.com/auradermaclinic','social','2026-02-16 04:27:58','2026-02-16 04:27:58'),(9,'instagram','https://www.instagram.com/auradermaclinic','social','2026-02-16 04:27:58','2026-02-16 04:27:58'),(10,'tiktok','https://www.tiktok.com/@auradermaclinic','social','2026-02-16 04:27:58','2026-02-16 04:27:58'),(11,'working_hours_ar','يومياً من 10:00 صباحاً حتى 10:00 مساءً','contact','2026-02-16 04:27:58','2026-02-23 01:57:06'),(12,'working_hours_en','Daily from 10:00 AM to 10:00 PM','contact','2026-02-16 04:27:58','2026-02-23 01:57:06'),(13,'stats_clients','1000','stats','2026-02-16 04:27:58','2026-02-16 04:27:58'),(14,'stats_doctors','10','stats','2026-02-16 04:27:58','2026-02-16 04:27:58'),(15,'stats_services','20','stats','2026-02-16 04:27:58','2026-02-16 04:27:58'),(16,'stats_devices','8','stats','2026-02-16 04:27:58','2026-02-16 04:27:58'),(17,'site_name_ar','','general','2026-02-23 01:57:06','2026-02-23 01:57:06'),(18,'site_name_en','','general','2026-02-23 01:57:06','2026-02-23 01:57:06'),(19,'site_description_ar','','general','2026-02-23 01:57:06','2026-02-23 01:57:06'),(20,'site_description_en','','general','2026-02-23 01:57:06','2026-02-23 01:57:06'),(21,'logo','','general','2026-02-23 01:57:06','2026-02-23 01:57:06'),(22,'favicon','','general','2026-02-23 01:57:06','2026-02-23 01:57:06'),(23,'phone','','contact','2026-02-23 01:57:06','2026-02-23 01:57:06'),(24,'phone_secondary','','contact','2026-02-23 01:57:06','2026-02-23 01:57:06'),(25,'google_maps_url','','contact','2026-02-23 01:57:06','2026-02-23 01:57:06'),(26,'twitter','','social','2026-02-23 01:57:06','2026-02-23 01:57:06'),(27,'youtube','','social','2026-02-23 01:57:06','2026-02-23 01:57:06'),(28,'snapchat','','social','2026-02-23 01:57:06','2026-02-23 01:57:06'),(29,'stat_patients','','statistics','2026-02-23 01:57:06','2026-02-23 01:57:06'),(30,'stat_years','','statistics','2026-02-23 01:57:06','2026-02-23 01:57:06'),(31,'stat_doctors','','statistics','2026-02-23 01:57:06','2026-02-23 01:57:06'),(32,'stat_services','','statistics','2026-02-23 01:57:06','2026-02-23 01:57:06'),(33,'default_dermatology_fee','400','consultation','2026-02-23 01:57:06','2026-02-23 01:57:06'),(34,'default_cosmetic_fee','200','consultation','2026-02-23 01:57:06','2026-03-01 08:50:39'),(35,'dermatology_consultant_fee','500','consultation','2026-03-01 01:12:52','2026-03-01 08:50:39'),(36,'dermatology_specialist_fee','400','consultation','2026-03-01 01:12:52','2026-03-01 08:50:39'),(37,'cosmetic_consultation_fee','200','consultation','2026-03-01 01:12:52','2026-03-01 08:50:39'),(38,'followup_fee','100','consultation','2026-03-01 01:12:52','2026-03-01 01:12:52'),(39,'followup_window_days','15','consultation','2026-03-01 01:12:52','2026-03-01 01:12:52');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shifts`
--

DROP TABLE IF EXISTS `shifts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shifts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name_ar` varchar(255) NOT NULL,
  `name_en` varchar(255) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shifts`
--

LOCK TABLES `shifts` WRITE;
/*!40000 ALTER TABLE `shifts` DISABLE KEYS */;
/*!40000 ALTER TABLE `shifts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supplies`
--

DROP TABLE IF EXISTS `supplies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name_ar` varchar(255) NOT NULL,
  `name_en` varchar(255) NOT NULL,
  `sku` varchar(255) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `unit` varchar(255) DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `min_quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `purchase_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `supplier` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `supplies_sku_unique` (`sku`)
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplies`
--

LOCK TABLES `supplies` WRITE;
/*!40000 ALTER TABLE `supplies` DISABLE KEYS */;
INSERT INTO `supplies` VALUES (1,'جل ليزر','Laser Gel','SUP-001','Laser','bottle',50.00,10.00,150.00,'MedSupply Egypt',1,'2026-02-17 01:44:33','2026-02-17 01:44:33'),(2,'عدسة ليزر الكسندريت','Alexandrite Laser Lens','SUP-002','Laser','piece',5.00,2.00,5000.00,'Candela Egypt',1,'2026-02-17 01:44:33','2026-02-17 01:44:33'),(3,'رأس كرايو','Cryo Tip','SUP-003','Laser','piece',10.00,3.00,2500.00,'Candela Egypt',1,'2026-02-17 01:44:33','2026-02-17 01:44:33'),(4,'بوتوكس 100 وحدة','Botox 100 Units','SUP-004','Injectables','vial',20.00,5.00,3500.00,'Allergan Egypt',1,'2026-02-17 01:44:33','2026-02-17 01:44:33'),(5,'فيلر هيالورونيك 1مل','Hyaluronic Filler 1ml','SUP-005','Injectables','syringe',30.00,10.00,2000.00,'Allergan Egypt',1,'2026-02-17 01:44:33','2026-02-17 01:44:33'),(6,'ميزوثيرابي شعر','Hair Mesotherapy Cocktail','SUP-006','Injectables','vial',25.00,5.00,800.00,'Dermaheal',1,'2026-02-17 01:44:33','2026-02-17 01:44:33'),(7,'بلازما PRP كيت','PRP Kit','SUP-007','Injectables','kit',40.00,10.00,350.00,'MedSupply Egypt',1,'2026-02-17 01:44:33','2026-02-17 01:44:33'),(8,'تقشير جلايكوليك','Glycolic Peel Solution','SUP-008','Peels','bottle',15.00,5.00,600.00,'DermaPharm',1,'2026-02-17 01:44:33','2026-02-17 01:44:33'),(9,'تقشير TCA','TCA Peel Solution','SUP-009','Peels','bottle',10.00,3.00,900.00,'DermaPharm',1,'2026-02-17 01:44:33','2026-02-17 01:44:33'),(10,'ماسك هيدرافيشل','HydraFacial Mask','SUP-010','Facials','piece',100.00,20.00,50.00,'SkinCeuticals',1,'2026-02-17 01:44:33','2026-02-17 01:44:33'),(11,'قفازات نيتريل M','Nitrile Gloves (M)','SUP-011','General','box',30.00,10.00,120.00,'MedSupply Egypt',1,'2026-02-17 01:44:33','2026-02-17 01:44:33'),(12,'قفازات نيتريل L','Nitrile Gloves (L)','SUP-012','General','box',20.00,10.00,120.00,'MedSupply Egypt',1,'2026-02-17 01:44:33','2026-02-17 01:44:33'),(13,'كحول طبي 70%','Medical Alcohol 70%','SUP-013','General','liter',25.00,5.00,35.00,'MedSupply Egypt',1,'2026-02-17 01:44:33','2026-02-17 01:44:33'),(14,'شاش معقم','Sterile Gauze','SUP-014','General','pack',50.00,15.00,25.00,'MedSupply Egypt',1,'2026-02-17 01:44:33','2026-02-17 01:44:33'),(15,'قطن طبي','Medical Cotton','SUP-015','General','pack',40.00,10.00,30.00,'MedSupply Egypt',1,'2026-02-17 01:44:33','2026-02-17 01:44:33'),(16,'إبر 30G','Needles 30G','SUP-016','General','box',20.00,5.00,200.00,'MedSupply Egypt',1,'2026-02-17 01:44:33','2026-02-17 01:44:33'),(17,'سرنجات 1مل','Syringes 1ml','SUP-017','General','box',15.00,5.00,150.00,'MedSupply Egypt',1,'2026-02-17 01:44:33','2026-02-17 01:44:33'),(18,'كريم مخدر EMLA','EMLA Numbing Cream','SUP-018','Topicals','tube',30.00,10.00,180.00,'AstraZeneca',1,'2026-02-17 01:44:33','2026-02-17 01:44:33'),(19,'واقي شمس طبي','Medical Sunscreen SPF50+','SUP-019','Topicals','tube',60.00,15.00,250.00,'La Roche-Posay',1,'2026-02-17 01:44:33','2026-02-17 01:44:33'),(20,'مرطب طبي','Medical Moisturizer','SUP-020','Topicals','tube',45.00,10.00,200.00,'CeraVe',1,'2026-02-17 01:44:33','2026-02-17 01:44:33'),(21,'فيلر نيوفيا','Neauvia Filler','SUP-021','Injectables','syringe',10.00,2.00,3650.00,'',1,'2026-03-08 12:05:46','2026-03-08 12:05:46'),(22,'فيلر سيلوسوم','Celesome Filler','SUP-022','Injectables','syringe',10.00,2.00,1750.00,'',1,'2026-03-08 12:05:46','2026-03-08 12:05:46'),(23,'فيلر ايفانثيا','Evanthia Filler','SUP-023','Injectables','syringe',10.00,2.00,3550.00,'',1,'2026-03-08 12:05:46','2026-03-08 12:05:46'),(24,'فيلر ميفل','Mifill Filler','SUP-024','Injectables','syringe',9.00,2.00,1550.00,'',1,'2026-03-08 12:05:46','2026-03-08 12:38:32'),(25,'بوتوكس زيومين','Xeomin Botox','SUP-025','Injectables','vial',10.00,2.00,2650.00,'',1,'2026-03-08 12:05:46','2026-03-08 12:05:46'),(26,'بوتوكس اليرجان','Allergan Botox (LE)','SUP-026','Injectables','vial',10.00,2.00,5000.00,'',1,'2026-03-08 12:05:46','2026-03-08 12:05:46'),(27,'بوتوكس جنتوكس','Gentox Botox','SUP-027','Injectables','vial',10.00,2.00,2050.00,'',1,'2026-03-08 12:05:46','2026-03-08 12:05:46'),(28,'سرنجة إنسولين','Insulin Syringe','SUP-028','General','piece',100.00,20.00,15.00,'',1,'2026-03-08 12:05:46','2026-03-08 12:05:46'),(29,'مسحات كحولية','Alcohol Swabs','SUP-029','General','piece',198.00,50.00,5.00,'',1,'2026-03-08 12:05:46','2026-03-08 12:38:32'),(30,'بروفايلو','Profhilo','SUP-030','Injectables','syringe',10.00,2.00,2750.00,'',1,'2026-03-08 12:15:40','2026-03-08 12:15:40'),(31,'RRS لونج لاستنج','RRS Long Lasting','SUP-031','Injectables','syringe',10.00,2.00,3850.00,'',1,'2026-03-08 12:15:40','2026-03-08 12:15:40'),(32,'RRS HA Eyes','RRS HA Eyes','SUP-032','Injectables','syringe',10.00,2.00,2800.00,'',1,'2026-03-08 12:15:40','2026-03-08 12:15:40'),(33,'ريتش','Rich','SUP-033','Injectables','syringe',10.00,2.00,14550.00,'',1,'2026-03-08 12:15:40','2026-03-08 12:15:40'),(34,'راديس','Radiesse','SUP-034','Injectables','syringe',10.00,2.00,9550.00,'',1,'2026-03-08 12:15:40','2026-03-08 12:15:40'),(35,'اوليديا','Olidia','SUP-035','Injectables','syringe',10.00,2.00,6550.00,'',1,'2026-03-08 12:15:40','2026-03-08 12:15:40'),(36,'سكلبترا','Sculptra','SUP-036','Injectables','syringe',10.00,2.00,11050.00,'',1,'2026-03-08 12:15:40','2026-03-08 12:15:40'),(37,'ياقوت','Yakoot','SUP-037','Injectables','syringe',10.00,2.00,3550.00,'',1,'2026-03-08 12:15:40','2026-03-08 12:15:40'),(38,'سرنجة 3 مل','Syringe 3ml','SUP-038','General','piece',100.00,20.00,10.00,'',1,'2026-03-08 12:34:45','2026-03-08 12:34:45'),(39,'سن ديرمابن','Dermapen Needle Tip','SUP-039','Dermapen','piece',50.00,10.00,40.00,'',1,'2026-03-08 12:34:45','2026-03-08 12:34:45'),(40,'أنبوب فصل بلازما','PRP Separation Tube','SUP-040','General','piece',50.00,10.00,60.00,'',1,'2026-03-08 12:34:45','2026-03-08 12:34:45'),(41,'رسوم معمل PRP','PRP Lab Processing Fee','SUP-041','General','session',999.00,1.00,45.00,'',1,'2026-03-08 12:34:45','2026-03-08 12:34:45'),(42,'منتج LC','LC Product','SUP-042','Dermapen','piece',10.00,2.00,600.00,'',1,'2026-03-08 12:34:45','2026-03-08 12:34:45'),(43,'جرين بيل','Green Peel Kit','SUP-043','Peels','kit',5.00,1.00,5500.00,'',1,'2026-03-08 12:41:58','2026-03-08 12:41:58'),(44,'مستهلكات هيدرافيشل Level 1','Hydrafacial Level 1 Kit','SUP-044','Facials','session',50.00,10.00,100.00,'',1,'2026-03-08 12:45:42','2026-03-08 12:45:42'),(45,'مستهلكات هيدرافيشل Level 2','Hydrafacial Level 2 Kit','SUP-045','Facials','session',50.00,10.00,150.00,'',1,'2026-03-08 12:45:42','2026-03-08 12:45:42'),(46,'مستهلكات هيدرافيشل Level 3','Hydrafacial Level 3 Kit','SUP-046','Facials','session',50.00,10.00,250.00,'',1,'2026-03-08 12:45:42','2026-03-08 12:45:42');
/*!40000 ALTER TABLE `supplies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supply_transactions`
--

DROP TABLE IF EXISTS `supply_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supply_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `supply_id` bigint(20) unsigned NOT NULL,
  `transaction_type` enum('purchase','usage','adjustment','return') NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit_cost` decimal(10,2) DEFAULT NULL,
  `visit_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `supply_transactions_supply_id_foreign` (`supply_id`),
  KEY `supply_transactions_visit_id_foreign` (`visit_id`),
  KEY `supply_transactions_created_by_foreign` (`created_by`),
  CONSTRAINT `supply_transactions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `supply_transactions_supply_id_foreign` FOREIGN KEY (`supply_id`) REFERENCES `supplies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `supply_transactions_visit_id_foreign` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supply_transactions`
--

LOCK TABLES `supply_transactions` WRITE;
/*!40000 ALTER TABLE `supply_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `supply_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tags`
--

DROP TABLE IF EXISTS `tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name_ar` varchar(255) NOT NULL,
  `name_en` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tags_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tags`
--

LOCK TABLES `tags` WRITE;
/*!40000 ALTER TABLE `tags` DISABLE KEYS */;
INSERT INTO `tags` VALUES (1,'العناية بالبشرة','Skincare','skincare','2026-02-16 04:27:58','2026-02-16 04:27:58'),(2,'الليزر','Laser','laser','2026-02-16 04:27:58','2026-02-16 04:27:58'),(3,'حب الشباب','Acne','acne','2026-02-16 04:27:58','2026-02-16 04:27:58'),(4,'مكافحة الشيخوخة','Anti-Aging','anti-aging','2026-02-16 04:27:58','2026-02-16 04:27:58'),(5,'إزالة الشعر','Hair Removal','hair-removal','2026-02-16 04:27:58','2026-02-16 04:27:58'),(6,'البوتكس','Botox','botox','2026-02-16 04:27:58','2026-02-16 04:27:58'),(7,'الفيلر','Fillers','fillers','2026-02-16 04:27:58','2026-02-16 04:27:58'),(8,'تفتيح البشرة','Skin Whitening','skin-whitening','2026-02-16 04:27:58','2026-02-16 04:27:58');
/*!40000 ALTER TABLE `tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `testimonials`
--

DROP TABLE IF EXISTS `testimonials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `testimonials` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_name_ar` varchar(255) NOT NULL,
  `client_name_en` varchar(255) NOT NULL,
  `service_id` bigint(20) unsigned DEFAULT NULL,
  `rating` tinyint(4) NOT NULL DEFAULT 5,
  `review_ar` text NOT NULL,
  `review_en` text NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `status` enum('published','hidden') NOT NULL DEFAULT 'published',
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `testimonials_service_id_foreign` (`service_id`),
  CONSTRAINT `testimonials_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `testimonials`
--

LOCK TABLES `testimonials` WRITE;
/*!40000 ALTER TABLE `testimonials` DISABLE KEYS */;
INSERT INTO `testimonials` VALUES (1,'نورا أحمد','Noura Ahmed',1,5,'تجربة رائعة في عيادة أورا ديرما كلينك. الدكتورة أسماء حمدي متميزة جداً وشرحت لي كل خطوات العلاج بالتفصيل. النتائج كانت مذهلة وبشرتي أصبحت أفضل بكثير بعد جلسات علاج حب الشباب.','An amazing experience at Aura Derma Clinic. Dr. Asmaa Hamdy is exceptional and explained every step of the treatment in detail. The results were stunning and my skin improved dramatically after the acne treatment sessions.','uploads/testimonials/AGiGpjFuuhtN3jrNLyhdZ2yRrpnuyuY4gD8wuNsU.png',NULL,'published',1,'2026-02-16 04:27:58','2026-02-20 02:51:40'),(2,'سارة محمد','Sara Mohamed',2,5,'أنصح الجميع بزيارة عيادة أورا. الأجهزة حديثة جداً والفريق الطبي محترف ولطيف. أجريت جلسات ليزر لإزالة الشعر والنتائج فاقت توقعاتي. شكراً لكم.','I recommend everyone to visit Aura Clinic. The equipment is very modern and the medical team is professional and friendly. I had laser hair removal sessions and the results exceeded my expectations. Thank you.',NULL,NULL,'published',2,'2026-02-16 04:27:58','2026-02-16 04:27:58'),(3,'منى عبد الله','Mona Abdullah',3,4,'العيادة نظيفة ومرتبة والاستقبال ممتاز. عملت جلسة هيدرافيشل وكانت النتيجة واضحة من أول جلسة. بشرتي أصبحت مشرقة وناعمة. سأستمر بالمتابعة معهم.','The clinic is clean and organized with excellent reception. I had a HydraFacial session and the result was noticeable from the first session. My skin became radiant and smooth. I will continue my follow-ups with them.',NULL,NULL,'published',3,'2026-02-16 04:27:58','2026-02-16 04:27:58'),(4,'فاطمة حسن','Fatma Hassan',4,5,'من أفضل العيادات التي زرتها. الدكتورة متابعة لحالتي بشكل مستمر والنتائج ممتازة. عملت حقن بوتوكس وكانت النتيجة طبيعية جداً ومرضية. أشكر كل الفريق الطبي.','One of the best clinics I have visited. The doctor follows up on my case continuously and the results are excellent. I had Botox injections and the result was very natural and satisfying. I thank the entire medical team.',NULL,NULL,'published',4,'2026-02-16 04:27:58','2026-02-16 04:27:58'),(5,'هدى إبراهيم','Hoda Ibrahim',5,4,'تجربتي مع عيادة أورا كانت ممتازة. الموظفون ودودون والأطباء ذوو خبرة عالية. عملت جلسات تقشير كيميائي وبشرتي تحسنت بشكل ملحوظ. المكان مريح ونظيف.','My experience with Aura Clinic was excellent. The staff is friendly and the doctors are highly experienced. I had chemical peeling sessions and my skin improved noticeably. The place is comfortable and clean.',NULL,NULL,'published',5,'2026-02-16 04:27:58','2026-02-16 04:27:58'),(6,'ريم خالد','Reem Khaled',6,5,'عيادة متميزة بكل المقاييس. الدكتورة أسماء حمدي من أفضل أطباء الجلدية. عملت جلسات فيلر للشفايف والنتيجة طبيعية وجميلة جداً. أنصح بها بشدة لكل من تبحث عن نتائج حقيقية.','An outstanding clinic by all standards. Dr. Asmaa Hamdy is one of the best dermatologists. I had lip filler sessions and the result was very natural and beautiful. I highly recommend it to anyone looking for real results.',NULL,NULL,'published',6,'2026-02-16 04:27:58','2026-02-16 04:27:58');
/*!40000 ALTER TABLE `testimonials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `treatment_plan_steps`
--

DROP TABLE IF EXISTS `treatment_plan_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `treatment_plan_steps` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `treatment_plan_id` bigint(20) unsigned NOT NULL,
  `service_id` bigint(20) unsigned DEFAULT NULL,
  `step_order` int(11) NOT NULL DEFAULT 1,
  `title` varchar(255) NOT NULL,
  `description` text,
  `sessions_required` int(11) NOT NULL DEFAULT 1,
  `sessions_completed` int(11) NOT NULL DEFAULT 0,
  `estimated_cost` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','in_progress','completed','skipped') NOT NULL DEFAULT 'pending',
  `scheduled_date` date DEFAULT NULL,
  `completed_date` date DEFAULT NULL,
  `notes` text,
  `visit_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `treatment_plan_steps_service_id_foreign` (`service_id`),
  KEY `treatment_plan_steps_visit_id_foreign` (`visit_id`),
  KEY `treatment_plan_steps_treatment_plan_id_step_order_index` (`treatment_plan_id`,`step_order`),
  CONSTRAINT `treatment_plan_steps_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`),
  CONSTRAINT `treatment_plan_steps_treatment_plan_id_foreign` FOREIGN KEY (`treatment_plan_id`) REFERENCES `treatment_plans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `treatment_plan_steps_visit_id_foreign` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `treatment_plan_steps`
--

LOCK TABLES `treatment_plan_steps` WRITE;
/*!40000 ALTER TABLE `treatment_plan_steps` DISABLE KEYS */;
/*!40000 ALTER TABLE `treatment_plan_steps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `treatment_plans`
--

DROP TABLE IF EXISTS `treatment_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `treatment_plans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` bigint(20) unsigned NOT NULL,
  `doctor_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `goals` text,
  `status` enum('draft','active','completed','cancelled') NOT NULL DEFAULT 'draft',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `notes` text,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `treatment_plans_doctor_id_foreign` (`doctor_id`),
  KEY `treatment_plans_created_by_foreign` (`created_by`),
  KEY `treatment_plans_patient_id_status_index` (`patient_id`,`status`),
  CONSTRAINT `treatment_plans_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `treatment_plans_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`),
  CONSTRAINT `treatment_plans_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `treatment_plans`
--

LOCK TABLES `treatment_plans` WRITE;
/*!40000 ALTER TABLE `treatment_plans` DISABLE KEYS */;
/*!40000 ALTER TABLE `treatment_plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role_id` bigint(20) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_seen_at` timestamp NULL DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_role_id_foreign` (`role_id`),
  CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin','admin@aura.com',1,1,'2026-03-08 18:37:25','2026-02-16 04:27:58','$2y$12$3v7YJUsXW1GLyGCRWM6j9uvaGLf1EAtOCtx//D/bQ7X1XO3q1BH7q','GSPocJf0bjSzrxCTFiehgnUjlSaOh2FFwc1QfnfS2Mq0ctcfhWexoHQM852F','2026-02-16 04:27:58','2026-03-08 18:37:25'),(13,'Secretary','secretary@auraderma.com',7,1,NULL,NULL,'$2y$12$nW6/Hiti0t3l1YJctwc68OP2qemy1P8vELkPvfq/8JiywHFCZ5sTW',NULL,'2026-02-17 17:46:00','2026-02-17 17:46:00'),(15,'Secretary','secretary@aura.com',7,1,NULL,NULL,'$2y$12$ntr2Yyin/pgRHKOLkIROJedVcS.N469S.CzO9ELfMxL.2U0BuOJyK',NULL,'2026-02-18 19:13:33','2026-02-18 19:13:33'),(19,'Dr. Alaa Elawady','dr.alaa@auraderma.com',5,1,NULL,NULL,'$2y$12$92bYtcuXcw4D0Bw.32q6SeN8NghpFQ56Vk1aIglHMYYplQb2Lh5Va',NULL,'2026-03-08 15:02:41','2026-03-08 15:35:25'),(20,'Dr. Eman Magdy','dr.eman@auraderma.com',5,1,NULL,NULL,'$2y$12$Q2XaGw.Y88JqP2Sc/LkSbug2jWRlaieJQSGEcAGCMiB3fycih10cu',NULL,'2026-03-08 15:09:40','2026-03-08 15:35:25'),(21,'Dr. Amira Ahmed','dr.amira@auraderma.com',5,1,NULL,NULL,'$2y$12$Oy44RTMf.Hz3A.5AbKtUHe5sRD90LBNbmvpe.uOHNMgj9Ns70hsXW',NULL,'2026-03-08 15:21:07','2026-03-08 15:35:25'),(22,'Dr. Asmaa Hamdy','dr.asmaa@auraderma.com',5,1,NULL,NULL,'$2y$12$L7tQpV2yjM.Yy/wHGJ9XQeNZB4R0k4o2ImXeTuzCJi4gk6vbAjene',NULL,'2026-03-08 15:25:10','2026-03-08 15:35:25');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `visit_photos`
--

DROP TABLE IF EXISTS `visit_photos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `visit_photos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `visit_id` bigint(20) unsigned NOT NULL,
  `photo_path` varchar(255) NOT NULL,
  `photo_type` enum('before','after','during') NOT NULL,
  `caption` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `visit_photos_visit_id_foreign` (`visit_id`),
  CONSTRAINT `visit_photos_visit_id_foreign` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `visit_photos`
--

LOCK TABLES `visit_photos` WRITE;
/*!40000 ALTER TABLE `visit_photos` DISABLE KEYS */;
/*!40000 ALTER TABLE `visit_photos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `visits`
--

DROP TABLE IF EXISTS `visits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `visits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` bigint(20) unsigned NOT NULL,
  `doctor_id` bigint(20) unsigned DEFAULT NULL,
  `receptionist_id` bigint(20) unsigned DEFAULT NULL,
  `booking_id` bigint(20) unsigned DEFAULT NULL,
  `booking_appointment_id` bigint(20) unsigned DEFAULT NULL,
  `visit_type` enum('consultation','session','follow_up') NOT NULL DEFAULT 'session',
  `consultation_type` enum('dermatology','cosmetic') DEFAULT NULL,
  `service_id` bigint(20) unsigned DEFAULT NULL,
  `service_package_id` bigint(20) unsigned DEFAULT NULL,
  `session_number` int(11) DEFAULT NULL,
  `status` enum('waiting','in_progress','completed','cancelled') NOT NULL DEFAULT 'waiting',
  `diagnosis` text,
  `doctor_notes` text,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `commission_amount` decimal(10,2) DEFAULT NULL,
  `commission_rate` decimal(5,2) DEFAULT NULL,
  `visit_date` date NOT NULL,
  `scheduled_time` time DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `visits_patient_id_foreign` (`patient_id`),
  KEY `visits_receptionist_id_foreign` (`receptionist_id`),
  KEY `visits_service_id_foreign` (`service_id`),
  KEY `visits_service_package_id_foreign` (`service_package_id`),
  KEY `visits_visit_date_status_index` (`visit_date`,`status`),
  KEY `visits_doctor_id_visit_date_index` (`doctor_id`,`visit_date`),
  KEY `visits_booking_id_index` (`booking_id`),
  KEY `visits_booking_appointment_id_index` (`booking_appointment_id`),
  CONSTRAINT `visits_booking_appointment_id_foreign` FOREIGN KEY (`booking_appointment_id`) REFERENCES `booking_appointments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `visits_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `visits_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE SET NULL,
  CONSTRAINT `visits_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `visits_receptionist_id_foreign` FOREIGN KEY (`receptionist_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `visits_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL,
  CONSTRAINT `visits_service_package_id_foreign` FOREIGN KEY (`service_package_id`) REFERENCES `service_packages` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `visits`
--

LOCK TABLES `visits` WRITE;
/*!40000 ALTER TABLE `visits` DISABLE KEYS */;
/*!40000 ALTER TABLE `visits` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-08 20:42:34
