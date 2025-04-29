/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.11-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: 127.0.0.1    Database: onlifin
-- ------------------------------------------------------
-- Server version	10.11.11-MariaDB-0ubuntu0.24.04.2

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
-- Table structure for table `accounts`
--

DROP TABLE IF EXISTS `accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'checking',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `description` text DEFAULT NULL,
  `color` varchar(7) DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `initial_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `current_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `accounts_user_id_foreign` (`user_id`),
  CONSTRAINT `accounts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounts`
--

LOCK TABLES `accounts` WRITE;
/*!40000 ALTER TABLE `accounts` DISABLE KEYS */;
INSERT INTO `accounts` VALUES
(1,'Conta Principal','checking',1,NULL,NULL,1,'2025-04-18 05:38:24','2025-04-18 05:38:24',0.00,0.00),
(2,'Conta Principal','checking',1,NULL,NULL,2,'2025-04-18 05:38:54','2025-04-18 05:38:54',0.00,0.00),
(3,'Conta Principal','checking',1,NULL,NULL,3,'2025-04-19 22:40:43','2025-04-19 22:40:43',0.00,0.00),
(4,'Conta Principal','checking',1,NULL,NULL,4,'2025-04-19 22:40:43','2025-04-19 22:40:43',0.00,0.00),
(5,'Conta Principal','checking',1,NULL,NULL,5,'2025-04-19 22:40:43','2025-04-19 22:40:43',0.00,0.00),
(6,'Conta Principal','checking',1,NULL,NULL,6,'2025-04-19 22:40:43','2025-04-19 22:40:43',0.00,0.00),
(7,'Conta Principal','checking',1,NULL,NULL,7,'2025-04-19 22:40:43','2025-04-19 22:40:43',0.00,0.00),
(8,'Conta Principal','checking',1,NULL,NULL,8,'2025-04-19 22:40:43','2025-04-19 22:40:43',0.00,0.00),
(9,'Conta Principal','checking',1,NULL,NULL,9,'2025-04-19 22:40:43','2025-04-19 22:40:43',0.00,0.00),
(10,'Conta Principal','checking',1,NULL,NULL,10,'2025-04-19 22:40:43','2025-04-19 22:40:43',0.00,0.00),
(11,'Conta Principal','checking',1,NULL,NULL,11,'2025-04-19 22:40:43','2025-04-19 22:40:43',0.00,0.00),
(12,'Conta Principal','checking',1,NULL,NULL,12,'2025-04-19 22:40:44','2025-04-19 22:40:44',0.00,0.00),
(13,'Conta Principal','checking',1,NULL,NULL,13,'2025-04-19 22:40:44','2025-04-19 22:40:44',0.00,0.00),
(14,'Conta Principal','checking',1,NULL,NULL,14,'2025-04-19 22:40:44','2025-04-19 22:40:44',0.00,0.00),
(15,'Conta Principal','checking',1,NULL,NULL,15,'2025-04-19 22:40:44','2025-04-19 22:40:44',0.00,0.00),
(16,'Conta Principal','checking',1,NULL,NULL,16,'2025-04-19 22:48:03','2025-04-19 22:48:03',0.00,0.00),
(17,'Conta Principal','checking',1,NULL,NULL,17,'2025-04-19 22:48:04','2025-04-19 22:48:04',0.00,0.00),
(18,'Conta Principal','checking',1,NULL,NULL,18,'2025-04-19 22:48:04','2025-04-19 22:48:04',0.00,0.00),
(19,'Conta Principal','checking',1,NULL,NULL,19,'2025-04-19 22:48:04','2025-04-19 22:48:04',0.00,0.00),
(20,'Conta Principal','checking',1,NULL,NULL,20,'2025-04-19 22:48:04','2025-04-19 22:48:04',0.00,0.00),
(21,'Conta Principal','checking',1,NULL,NULL,21,'2025-04-19 22:48:04','2025-04-19 22:48:04',0.00,0.00),
(22,'Conta Principal','checking',1,NULL,NULL,22,'2025-04-19 22:48:04','2025-04-19 22:48:04',0.00,0.00),
(23,'Conta Principal','checking',1,NULL,NULL,23,'2025-04-19 22:48:04','2025-04-19 22:48:04',0.00,0.00),
(24,'Conta Principal','checking',1,NULL,NULL,24,'2025-04-19 22:48:04','2025-04-19 22:48:04',0.00,0.00),
(25,'Conta Principal','checking',1,NULL,NULL,25,'2025-04-19 22:48:04','2025-04-19 22:48:04',0.00,0.00),
(26,'Conta Principal','checking',1,NULL,NULL,26,'2025-04-19 22:48:04','2025-04-19 22:48:04',0.00,0.00),
(27,'Conta Principal','checking',1,NULL,NULL,27,'2025-04-19 22:48:04','2025-04-19 22:48:04',0.00,0.00),
(28,'Conta Principal','checking',1,NULL,NULL,28,'2025-04-19 22:48:04','2025-04-19 22:48:04',0.00,0.00),
(29,'Conta Principal','checking',1,NULL,NULL,29,'2025-04-19 22:48:39','2025-04-19 22:48:39',0.00,0.00),
(30,'Conta Principal','checking',1,NULL,NULL,30,'2025-04-19 22:48:39','2025-04-19 22:48:39',0.00,0.00),
(31,'Conta Principal','checking',1,NULL,NULL,31,'2025-04-19 22:48:39','2025-04-19 22:48:39',0.00,0.00),
(32,'Conta Principal','checking',1,NULL,NULL,32,'2025-04-19 22:48:39','2025-04-19 22:48:39',0.00,0.00),
(33,'Conta Principal','checking',1,NULL,NULL,33,'2025-04-19 22:48:39','2025-04-19 22:48:39',0.00,0.00),
(34,'Conta Principal','checking',1,NULL,NULL,34,'2025-04-19 22:48:39','2025-04-19 22:48:39',0.00,0.00),
(35,'Conta Principal','checking',1,NULL,NULL,35,'2025-04-19 22:48:39','2025-04-19 22:48:39',0.00,0.00),
(36,'Conta Principal','checking',1,NULL,NULL,36,'2025-04-19 22:48:39','2025-04-19 22:48:39',0.00,0.00),
(37,'Conta Principal','checking',1,NULL,NULL,37,'2025-04-19 22:48:39','2025-04-19 22:48:39',0.00,0.00),
(38,'Conta Principal','checking',1,NULL,NULL,38,'2025-04-19 22:48:39','2025-04-19 22:48:39',0.00,0.00),
(39,'Conta Principal','checking',1,NULL,NULL,39,'2025-04-19 22:48:39','2025-04-19 22:48:39',0.00,0.00),
(40,'Conta Principal','checking',1,NULL,NULL,40,'2025-04-19 22:48:39','2025-04-19 22:48:39',0.00,0.00),
(41,'Conta Principal','checking',1,NULL,NULL,41,'2025-04-19 22:48:39','2025-04-19 22:48:39',0.00,0.00),
(42,'Conta Principal','checking',1,NULL,NULL,42,'2025-04-19 22:52:01','2025-04-19 22:52:01',0.00,0.00),
(43,'Runte Inc','checking',1,NULL,NULL,1,'2025-04-19 22:52:01','2025-04-19 22:52:01',0.00,0.00),
(44,'Conta Principal','checking',1,NULL,NULL,43,'2025-04-19 22:52:01','2025-04-19 22:52:01',0.00,0.00),
(45,'Langosh Inc','checking',1,NULL,NULL,43,'2025-04-19 22:52:01','2025-04-19 22:52:01',0.00,0.00),
(46,'Conta Principal','checking',1,NULL,NULL,44,'2025-04-19 22:52:01','2025-04-19 22:52:01',0.00,0.00),
(47,'Pfannerstill PLC','checking',1,NULL,NULL,44,'2025-04-19 22:52:01','2025-04-19 22:52:01',0.00,0.00),
(48,'Conta Principal','checking',1,NULL,NULL,45,'2025-04-19 22:52:01','2025-04-19 22:52:01',0.00,0.00),
(49,'McCullough LLC','checking',1,NULL,NULL,45,'2025-04-19 22:52:01','2025-04-19 22:52:01',0.00,0.00),
(50,'Conta Principal','checking',1,NULL,NULL,46,'2025-04-19 22:52:01','2025-04-19 22:52:01',0.00,0.00),
(51,'Conta Principal','checking',1,NULL,NULL,47,'2025-04-19 22:52:01','2025-04-19 22:52:01',0.00,0.00),
(52,'Anderson-McLaughlin','checking',1,NULL,NULL,1,'2025-04-19 22:52:01','2025-04-19 22:52:01',0.00,0.00),
(53,'Conta Principal','checking',1,NULL,NULL,48,'2025-04-19 22:52:01','2025-04-19 22:52:01',0.00,0.00),
(54,'Conta Principal','checking',1,NULL,NULL,49,'2025-04-19 22:52:01','2025-04-19 22:52:01',0.00,0.00),
(55,'Nitzsche-Kunde','checking',1,NULL,NULL,1,'2025-04-19 22:52:01','2025-04-19 22:52:01',0.00,0.00),
(56,'Conta Principal','checking',1,NULL,NULL,50,'2025-04-19 22:52:01','2025-04-19 22:52:01',0.00,0.00),
(57,'Conta Principal','checking',1,NULL,NULL,51,'2025-04-19 22:52:01','2025-04-19 22:52:01',0.00,0.00),
(58,'Wilderman, Rau and Wuckert','checking',1,NULL,NULL,51,'2025-04-19 22:52:01','2025-04-19 22:52:01',0.00,0.00),
(59,'Conta Principal','checking',1,NULL,NULL,52,'2025-04-19 22:52:01','2025-04-19 22:52:01',0.00,0.00),
(60,'Farrell Group','checking',1,NULL,NULL,52,'2025-04-19 22:52:01','2025-04-19 22:52:01',0.00,0.00),
(61,'Conta Principal','checking',1,NULL,NULL,53,'2025-04-19 22:52:01','2025-04-19 22:52:01',0.00,0.00),
(62,'Mann Inc','checking',1,NULL,NULL,53,'2025-04-19 22:52:01','2025-04-19 22:52:01',0.00,0.00),
(63,'Conta Principal','checking',1,NULL,NULL,54,'2025-04-19 22:52:01','2025-04-19 22:52:01',0.00,0.00),
(64,'Boehm-Cummerata','checking',1,NULL,NULL,54,'2025-04-19 22:52:01','2025-04-19 22:52:01',0.00,0.00);
/*!40000 ALTER TABLE `accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
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
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(255) DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `categories_user_id_foreign` (`user_id`),
  CONSTRAINT `categories_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES
(4,'DESPESAS','expense',NULL,'#3b82f6',NULL,2,'2025-04-18 23:26:53','2025-04-18 23:26:53'),
(5,'RECEITAS','income',NULL,'#3b82f6',NULL,2,'2025-04-18 23:26:58','2025-04-18 23:26:58'),
(6,'Transferências Enviadas','expense','Categoria criada automaticamente pela IA','#d5586d','fa-solid fa-tag',2,'2025-04-19 02:03:24','2025-04-19 02:03:24'),
(7,'Transferências Recebidas','income','Categoria criada automaticamente pela IA','#9b07e1','fa-solid fa-tag',2,'2025-04-19 02:03:24','2025-04-19 02:03:24'),
(8,'porro','expense','Corrupti iure illum id aut vero facilis.','#cfab3d','default',1,'2025-04-19 22:52:01','2025-04-19 22:52:01'),
(9,'Supermercado','income','Est vel nesciunt officia nemo quod voluptatem.','#ed7351','default',1,'2025-04-19 22:52:01','2025-04-19 22:52:01'),
(10,'Restaurante','income','Rem dolore voluptates eius aut aut ut magni illum.','#c5a1c6','default',1,'2025-04-19 22:52:01','2025-04-19 22:52:01'),
(11,'et','income','Qui maxime omnis ut eius tenetur velit nisi.','#d21eba','default',1,'2025-04-19 22:52:01','2025-04-19 22:52:01'),
(12,'quis','expense','Repudiandae facilis aliquid dolor id natus et totam voluptatibus.','#399a43','default',1,'2025-04-19 22:52:01','2025-04-19 22:52:01'),
(13,'enim','expense','Assumenda ipsa reiciendis consequatur eos sint consectetur impedit.','#a086d3','default',1,'2025-04-19 22:52:01','2025-04-19 22:52:01'),
(14,'necessitatibus','income','Eligendi eum sunt repellendus harum.','#07ef99','default',1,'2025-04-19 22:52:01','2025-04-19 22:52:01');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `due_date_notification_settings`
--

DROP TABLE IF EXISTS `due_date_notification_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `due_date_notification_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `notify_expenses` tinyint(1) NOT NULL DEFAULT 1,
  `notify_incomes` tinyint(1) NOT NULL DEFAULT 1,
  `notify_on_due_date` tinyint(1) NOT NULL DEFAULT 1,
  `notify_days_before` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '[1,3,7]' CHECK (json_valid(`notify_days_before`)),
  `notify_channels` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '["email","database"]' CHECK (json_valid(`notify_channels`)),
  `expense_template_id` bigint(20) unsigned DEFAULT NULL,
  `income_template_id` bigint(20) unsigned DEFAULT NULL,
  `group_notifications` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `due_date_notification_settings_user_id_foreign` (`user_id`),
  KEY `due_date_notification_settings_expense_template_id_foreign` (`expense_template_id`),
  KEY `due_date_notification_settings_income_template_id_foreign` (`income_template_id`),
  CONSTRAINT `due_date_notification_settings_expense_template_id_foreign` FOREIGN KEY (`expense_template_id`) REFERENCES `notification_templates` (`id`) ON DELETE SET NULL,
  CONSTRAINT `due_date_notification_settings_income_template_id_foreign` FOREIGN KEY (`income_template_id`) REFERENCES `notification_templates` (`id`) ON DELETE SET NULL,
  CONSTRAINT `due_date_notification_settings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `due_date_notification_settings`
--

LOCK TABLES `due_date_notification_settings` WRITE;
/*!40000 ALTER TABLE `due_date_notification_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `due_date_notification_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
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
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES
(1,'2024_02_23_000000_create_sessions_table',1),
(2,'0001_01_01_000001_create_cache_table',2),
(3,'0001_01_01_000002_create_jobs_table',2),
(4,'2014_10_12_000000_create_users_table',2),
(5,'2024_02_23_000001_create_accounts_table',2),
(6,'2024_02_23_000002_create_categories_table',2),
(7,'2024_02_23_000003_create_transactions_table',2),
(8,'2024_02_23_000004_create_roles_table',2),
(9,'2024_02_23_000005_create_permissions_table',2),
(10,'2024_02_23_000006_create_role_user_table',2),
(11,'2024_02_23_000007_create_permission_role_table',2),
(12,'2024_02_23_add_status_to_transactions',2),
(13,'2025_03_11_230234_add_description_and_color_to_accounts_table',2),
(14,'2025_03_11_233944_create_settings_table',2),
(15,'2025_03_12_163420_add_description_to_categories_table',2),
(16,'2025_03_12_164528_add_is_admin_to_users_table',2),
(17,'2025_03_12_174726_create_replicate_settings_table',2),
(18,'2025_03_30_203228_add_notification_settings_to_settings_table',2),
(19,'2025_03_30_211641_add_is_active_to_users_table',2),
(20,'2025_03_30_224441_sync_user_status_fields',2),
(21,'2025_03_31_013443_create_password_reset_tokens_table',2),
(22,'2025_03_31_015322_create_notifications_table',2),
(23,'2025_03_31_020643_create_notification_templates_table',2),
(24,'2025_03_31_092521_add_new_columns_to_notification_settings',2),
(25,'2025_03_31_095642_add_notification_settings_to_users_table',2),
(26,'[timestamp]_add_phone_to_users_table',2),
(27,'2025_03_31_162902_add_whatsapp_notifications_to_users_table',3),
(28,'2024_02_24_000001_update_accounts_table_structure',4),
(29,'2024_04_02_000000_create_system_logs_table',4),
(30,'2025_02_24_001122_add_user_id_to_categories_table',4),
(31,'2025_02_24_001336_add_user_id_to_categories_table',4),
(32,'2025_02_24_001358_add_description_to_categories_table',4),
(33,'2025_02_24_110748_add_user_id_to_accounts_table',4),
(34,'2025_02_24_114128_update_users_table_add_missing_fields',4),
(35,'2025_03_13_011459_add_recurrence_fields_to_transactions_table',4),
(36,'2025_04_01_090924_add_provider_to_replicate_settings_table',4),
(37,'2025_04_18_023349_add_amount_to_transactions_if_not_exists',4),
(38,'xxxx_xx_xx_add_description_to_categories_table',5),
(39,'xxxx_xx_xx_add_user_id_to_accounts_table',5),
(40,'xxxx_xx_xx_add_user_id_to_categories_table',5),
(41,'xxxx_xx_xx_create_role_user_table',5),
(42,'xxxx_xx_xx_update_users_table_add_missing_fields',5),
(43,'2025_04_18_185604_create_model_api_keys_table',6);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_api_keys`
--

DROP TABLE IF EXISTS `model_api_keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_api_keys` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `provider` varchar(50) NOT NULL COMMENT 'Provedor de IA (openai, anthropic, gemini, etc)',
  `model` varchar(100) NOT NULL COMMENT 'Nome do modelo específico',
  `api_token` text NOT NULL COMMENT 'Chave API específica para este modelo',
  `system_prompt` text DEFAULT NULL COMMENT 'Prompt do sistema específico para este modelo',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Se esta configuração está ativa',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `model_api_keys_provider_model_unique` (`provider`,`model`),
  KEY `model_api_keys_is_active_index` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_api_keys`
--

LOCK TABLES `model_api_keys` WRITE;
/*!40000 ALTER TABLE `model_api_keys` DISABLE KEYS */;
INSERT INTO `model_api_keys` VALUES
(5,'gemini','gemini-2.0-flash','AIzaSyBCxoPMWv4oOH84kadpv_n6SRCU2PqWPoA','faça a nalise das transações do extrato, existem os debitos pagamentos com cartão, transferencias enviadas PIX , pagamento por QR code , cadastre estas transações nas categorias correspondes ou se não existir categoria cadastrada faça o cadastro da mesma e insira estas transação nela, ja para as receitas existem os pagamentos recebidos, boletos, transferencias e recebidas (receitas) faça o mesmo que foi feito para as despesas para estas transações',1,'2025-04-19 16:23:02','2025-04-19 16:23:02');
/*!40000 ALTER TABLE `model_api_keys` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_templates`
--

DROP TABLE IF EXISTS `notification_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `type` enum('expense','income','system','custom') NOT NULL,
  `event` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `email_subject` text DEFAULT NULL,
  `email_content` text DEFAULT NULL,
  `whatsapp_content` text DEFAULT NULL,
  `push_title` text DEFAULT NULL,
  `push_content` text DEFAULT NULL,
  `push_image` varchar(255) DEFAULT NULL,
  `available_variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`available_variables`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notification_templates_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_templates`
--

LOCK TABLES `notification_templates` WRITE;
/*!40000 ALTER TABLE `notification_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `notification_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permission_role`
--

DROP TABLE IF EXISTS `permission_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permission_role` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `permission_role_permission_id_foreign` (`permission_id`),
  KEY `permission_role_role_id_foreign` (`role_id`),
  CONSTRAINT `permission_role_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `permission_role_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permission_role`
--

LOCK TABLES `permission_role` WRITE;
/*!40000 ALTER TABLE `permission_role` DISABLE KEYS */;
/*!40000 ALTER TABLE `permission_role` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `category` varchar(255) NOT NULL DEFAULT 'system',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `replicate_settings`
--

DROP TABLE IF EXISTS `replicate_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `replicate_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `provider` varchar(255) NOT NULL DEFAULT 'openai',
  `api_token` varchar(255) DEFAULT NULL,
  `model_version` varchar(255) NOT NULL DEFAULT 'claude-3-sonnet-20240229',
  `system_prompt` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `replicate_settings`
--

LOCK TABLES `replicate_settings` WRITE;
/*!40000 ALTER TABLE `replicate_settings` DISABLE KEYS */;
INSERT INTO `replicate_settings` VALUES
(1,'gemini','AIzaSyDufboevdUz00X9broVJ6ME6rQT86l1NVc','gemini-pro','analise o extrato e cadastre as transações',0,'2025-04-18 20:41:26','2025-04-18 21:47:59');
/*!40000 ALTER TABLE `replicate_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_user`
--

DROP TABLE IF EXISTS `role_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `role_user_role_id_foreign` (`role_id`),
  KEY `role_user_user_id_foreign` (`user_id`),
  CONSTRAINT `role_user_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_user`
--

LOCK TABLES `role_user` WRITE;
/*!40000 ALTER TABLE `role_user` DISABLE KEYS */;
INSERT INTO `role_user` VALUES
(1,1,1,NULL,NULL),
(2,1,2,NULL,NULL);
/*!40000 ALTER TABLE `role_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES
(1,'Administrador','Acesso completo ao sistema','2025-04-18 05:38:24','2025-04-18 05:38:24');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` text NOT NULL,
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
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `email_notifications_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `email_notify_new_transactions` tinyint(1) NOT NULL DEFAULT 1,
  `email_notify_due_dates` tinyint(1) NOT NULL DEFAULT 1,
  `email_notify_low_balance` tinyint(1) NOT NULL DEFAULT 1,
  `email_low_balance_threshold` decimal(10,2) DEFAULT NULL,
  `whatsapp_notifications_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `whatsapp_number` varchar(255) DEFAULT NULL,
  `whatsapp_notify_new_transactions` tinyint(1) NOT NULL DEFAULT 1,
  `whatsapp_notify_due_dates` tinyint(1) NOT NULL DEFAULT 1,
  `whatsapp_notify_low_balance` tinyint(1) NOT NULL DEFAULT 1,
  `whatsapp_low_balance_threshold` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`),
  KEY `settings_user_id_foreign` (`user_id`),
  CONSTRAINT `settings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES
(1,'whatsapp_enabled','1',NULL,'2025-04-19 13:24:44','2025-04-19 13:24:44',1,1,1,1,NULL,0,NULL,1,1,1,NULL),
(2,'whatsapp_provider','twilio',NULL,'2025-04-19 13:24:44','2025-04-19 13:24:44',1,1,1,1,NULL,0,NULL,1,1,1,NULL),
(3,'whatsapp_use_templates','1',NULL,'2025-04-19 13:24:44','2025-04-19 13:24:44',1,1,1,1,NULL,0,NULL,1,1,1,NULL),
(4,'whatsapp_debug_mode','1',NULL,'2025-04-19 13:24:44','2025-04-19 13:24:44',1,1,1,1,NULL,0,NULL,1,1,1,NULL),
(5,'whatsapp_retry_failed','true',NULL,'2025-04-19 13:24:44','2025-04-19 13:24:44',1,1,1,1,NULL,0,NULL,1,1,1,NULL),
(6,'whatsapp_max_retries','3',NULL,'2025-04-19 13:24:44','2025-04-19 13:24:44',1,1,1,1,NULL,0,NULL,1,1,1,NULL),
(7,'twilio_account_sid','galvatec@gmail.com',NULL,'2025-04-19 13:24:44','2025-04-19 13:24:44',1,1,1,1,NULL,0,NULL,1,1,1,NULL),
(8,'twilio_auth_token','123456',NULL,'2025-04-19 13:24:44','2025-04-19 13:24:44',1,1,1,1,NULL,0,NULL,1,1,1,NULL),
(9,'twilio_from_number','+5512981385854',NULL,'2025-04-19 13:24:44','2025-04-19 13:24:44',1,1,1,1,NULL,0,NULL,1,1,1,NULL),
(10,'twilio_sandbox_mode','1',NULL,'2025-04-19 13:24:44','2025-04-19 13:24:44',1,1,1,1,NULL,0,NULL,1,1,1,NULL),
(11,'messagebird_access_key',NULL,NULL,'2025-04-19 13:24:44','2025-04-19 13:24:44',1,1,1,1,NULL,0,NULL,1,1,1,NULL),
(12,'messagebird_channel_id',NULL,NULL,'2025-04-19 13:24:44','2025-04-19 13:24:44',1,1,1,1,NULL,0,NULL,1,1,1,NULL),
(13,'messagebird_namespace',NULL,NULL,'2025-04-19 13:24:44','2025-04-19 13:24:44',1,1,1,1,NULL,0,NULL,1,1,1,NULL);
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_logs`
--

DROP TABLE IF EXISTS `system_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `module` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `ip_address` varchar(255) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `system_logs_user_id_foreign` (`user_id`),
  KEY `system_logs_action_index` (`action`),
  KEY `system_logs_module_index` (`module`),
  KEY `system_logs_created_at_index` (`created_at`),
  CONSTRAINT `system_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=82 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_logs`
--

LOCK TABLES `system_logs` WRITE;
/*!40000 ALTER TABLE `system_logs` DISABLE KEYS */;
INSERT INTO `system_logs` VALUES
(1,2,'update','ai_settings','Configurações de IA atualizadas','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"is_active\":false,\"has_system_prompt\":false}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 20:41:26','2025-04-18 20:41:26'),
(2,2,'update','ai_settings','Configurações de IA atualizadas','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"is_active\":false,\"has_system_prompt\":false}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 20:41:51','2025-04-18 20:41:51'),
(3,2,'update','ai_settings','Configurações de IA atualizadas','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"is_active\":false,\"has_system_prompt\":false}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 20:47:51','2025-04-18 20:47:51'),
(4,2,'update','ai_settings','Configurações de IA atualizadas','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"is_active\":false,\"has_system_prompt\":false}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 20:48:18','2025-04-18 20:48:18'),
(5,2,'update','ai_settings','Configurações de IA atualizadas','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"is_active\":false,\"has_system_prompt\":false}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 20:49:14','2025-04-18 20:49:14'),
(6,2,'test','ai_settings','Teste de conexão falhou: Nenhuma configuração encontrada','[]','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 20:51:47','2025-04-18 20:51:47'),
(7,2,'test','ai_settings','Teste de conexão falhou: Nenhuma configuração encontrada','[]','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 20:51:54','2025-04-18 20:51:54'),
(8,2,'test','ai_settings','Teste de conexão falhou: Nenhuma configuração encontrada','[]','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 20:52:00','2025-04-18 20:52:00'),
(9,2,'update','ai_settings','Configurações de IA atualizadas','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"is_active\":false,\"has_system_prompt\":true}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 20:52:31','2025-04-18 20:52:31'),
(10,2,'error','ai_settings','Erro ao testar conexão com IA: Provedor de IA não suportado','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"error\":\"Provedor de IA n\\u00e3o suportado\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 20:58:16','2025-04-18 20:58:16'),
(11,2,'error','ai_settings','Erro ao testar conexão com IA: Provedor de IA não suportado','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"error\":\"Provedor de IA n\\u00e3o suportado\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 20:58:17','2025-04-18 20:58:17'),
(12,2,'error','ai_settings','Erro ao testar conexão com IA: Provedor de IA não suportado','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"error\":\"Provedor de IA n\\u00e3o suportado\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 20:58:17','2025-04-18 20:58:17'),
(13,2,'error','ai_settings','Erro ao testar conexão com IA: Provedor de IA não suportado','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"error\":\"Provedor de IA n\\u00e3o suportado\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 20:58:18','2025-04-18 20:58:18'),
(14,2,'error','ai_settings','Erro ao testar conexão com IA: Provedor de IA não suportado','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"error\":\"Provedor de IA n\\u00e3o suportado\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 20:58:18','2025-04-18 20:58:18'),
(15,2,'error','ai_settings','Erro ao testar conexão com IA: Provedor de IA não suportado','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"error\":\"Provedor de IA n\\u00e3o suportado\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 20:58:18','2025-04-18 20:58:18'),
(16,2,'error','ai_settings','Erro ao testar conexão com IA: Provedor de IA não suportado','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"error\":\"Provedor de IA n\\u00e3o suportado\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 20:58:32','2025-04-18 20:58:32'),
(17,2,'error','ai_settings','Erro ao testar conexão com IA: Provedor de IA não suportado','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"error\":\"Provedor de IA n\\u00e3o suportado\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 20:59:02','2025-04-18 20:59:02'),
(18,2,'error','ai_settings','Erro ao testar conexão com IA: Provedor de IA não suportado','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"error\":\"Provedor de IA n\\u00e3o suportado\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 20:59:03','2025-04-18 20:59:03'),
(19,2,'error','ai_settings','Erro ao testar conexão com IA: Provedor de IA não suportado','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"error\":\"Provedor de IA n\\u00e3o suportado\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 20:59:03','2025-04-18 20:59:03'),
(20,2,'error','ai_settings','Erro ao testar conexão com IA: Provedor de IA não suportado','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"error\":\"Provedor de IA n\\u00e3o suportado\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 20:59:04','2025-04-18 20:59:04'),
(21,2,'error','ai_settings','Erro ao testar conexão com IA: Provedor de IA não suportado','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"error\":\"Provedor de IA n\\u00e3o suportado\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 20:59:04','2025-04-18 20:59:04'),
(22,2,'error','ai_settings','Erro ao testar conexão com IA: Provedor de IA não suportado','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"error\":\"Provedor de IA n\\u00e3o suportado\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 20:59:04','2025-04-18 20:59:04'),
(23,2,'error','ai_settings','Erro ao testar conexão com IA: Provedor de IA não suportado','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"error\":\"Provedor de IA n\\u00e3o suportado\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 20:59:04','2025-04-18 20:59:04'),
(24,2,'error','ai_settings','Erro ao testar conexão com IA: Provedor de IA não suportado','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"error\":\"Provedor de IA n\\u00e3o suportado\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 20:59:04','2025-04-18 20:59:04'),
(25,2,'error','ai_settings','Erro ao testar conexão com IA: Provedor de IA não suportado','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"error\":\"Provedor de IA n\\u00e3o suportado\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 20:59:05','2025-04-18 20:59:05'),
(26,2,'error','ai_settings','Erro ao testar conexão com IA: Provedor de IA não suportado','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"error\":\"Provedor de IA n\\u00e3o suportado\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 20:59:08','2025-04-18 20:59:08'),
(27,2,'error','ai_settings','Erro ao testar conexão com IA: Provedor de IA não suportado','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"error\":\"Provedor de IA n\\u00e3o suportado\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 20:59:34','2025-04-18 20:59:34'),
(28,2,'error','ai_settings','Erro ao testar conexão com IA: Provedor de IA não suportado','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"error\":\"Provedor de IA n\\u00e3o suportado\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 20:59:35','2025-04-18 20:59:35'),
(29,2,'error','ai_settings','Erro ao testar conexão com IA: Provedor de IA não suportado','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"error\":\"Provedor de IA n\\u00e3o suportado\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 20:59:40','2025-04-18 20:59:40'),
(30,2,'error','ai_settings','Erro ao testar conexão com IA: Provedor de IA não suportado','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"error\":\"Provedor de IA n\\u00e3o suportado\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 20:59:49','2025-04-18 20:59:49'),
(31,2,'error','ai_settings','Erro ao testar conexão com IA: Provedor de IA não suportado','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"error\":\"Provedor de IA n\\u00e3o suportado\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 20:59:50','2025-04-18 20:59:50'),
(32,2,'error','ai_settings','Erro ao testar conexão com IA: Provedor de IA não suportado','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"error\":\"Provedor de IA n\\u00e3o suportado\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:00:33','2025-04-18 21:00:33'),
(33,2,'error','ai_settings','Erro ao testar conexão com IA: Provedor de IA não suportado','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"error\":\"Provedor de IA n\\u00e3o suportado\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:00:34','2025-04-18 21:00:34'),
(34,2,'error','ai_settings','Erro ao testar conexão com IA: Provedor de IA não suportado','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"error\":\"Provedor de IA n\\u00e3o suportado\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:00:35','2025-04-18 21:00:35'),
(35,2,'error','ai_settings','Erro ao testar conexão com IA: Provedor de IA não suportado','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"error\":\"Provedor de IA n\\u00e3o suportado\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:00:35','2025-04-18 21:00:35'),
(36,2,'error','ai_settings','Erro ao testar conexão com IA: Provedor de IA não suportado','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"error\":\"Provedor de IA n\\u00e3o suportado\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:00:36','2025-04-18 21:00:36'),
(37,2,'update','ai_settings','Configurações de IA atualizadas','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"is_active\":false,\"has_system_prompt\":true}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:00:37','2025-04-18 21:00:37'),
(38,2,'error','ai_settings','Erro ao testar conexão com IA: Provedor de IA não suportado','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"error\":\"Provedor de IA n\\u00e3o suportado\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:00:49','2025-04-18 21:00:49'),
(39,2,'update','ai_settings','Configurações de IA atualizadas','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"is_active\":false,\"has_system_prompt\":true}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:02:13','2025-04-18 21:02:13'),
(40,2,'error','ai_settings','Erro ao testar conexão com IA: Provedor de IA não suportado','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"error\":\"Provedor de IA n\\u00e3o suportado\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:03:17','2025-04-18 21:03:17'),
(41,2,'error','ai_settings','Erro ao testar conexão com IA: Provedor de IA não suportado','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"error\":\"Provedor de IA n\\u00e3o suportado\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:03:18','2025-04-18 21:03:18'),
(42,2,'error','ai_settings','Erro ao testar conexão com IA: Provedor de IA não suportado','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"error\":\"Provedor de IA n\\u00e3o suportado\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:03:19','2025-04-18 21:03:19'),
(43,2,'error','ai_settings','Erro ao testar conexão com IA: Provedor de IA não suportado','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"error\":\"Provedor de IA n\\u00e3o suportado\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:03:20','2025-04-18 21:03:20'),
(44,2,'error','ai_settings','Erro ao testar conexão com IA: Provedor de IA não suportado','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"error\":\"Provedor de IA n\\u00e3o suportado\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:03:22','2025-04-18 21:03:22'),
(45,2,'error','ai_settings','Erro ao testar conexão com IA: Provedor de IA não suportado','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"error\":\"Provedor de IA n\\u00e3o suportado\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:03:23','2025-04-18 21:03:23'),
(46,2,'error','ai_settings','Erro ao testar conexão com IA: Erro ao testar conexão com Gemini: models/gemini-pro is not found for API version v1beta, or is not supported for generateContent. Call ListModels to see the list of available models and their supported methods.','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"error\":\"Erro ao testar conex\\u00e3o com Gemini: models\\/gemini-pro is not found for API version v1beta, or is not supported for generateContent. Call ListModels to see the list of available models and their supported methods.\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:04:34','2025-04-18 21:04:34'),
(47,2,'error','ai_settings','Erro ao testar conexão com IA: Erro ao testar conexão com Gemini: models/gemini-pro is not found for API version v1beta, or is not supported for generateContent. Call ListModels to see the list of available models and their supported methods.','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"error\":\"Erro ao testar conex\\u00e3o com Gemini: models\\/gemini-pro is not found for API version v1beta, or is not supported for generateContent. Call ListModels to see the list of available models and their supported methods.\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:04:44','2025-04-18 21:04:44'),
(48,2,'error','ai_settings','Erro ao testar conexão com IA: Erro ao testar conexão com Gemini: models/gemini-pro is not found for API version v1beta, or is not supported for generateContent. Call ListModels to see the list of available models and their supported methods.','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"error\":\"Erro ao testar conex\\u00e3o com Gemini: models\\/gemini-pro is not found for API version v1beta, or is not supported for generateContent. Call ListModels to see the list of available models and their supported methods.\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:05:19','2025-04-18 21:05:19'),
(49,2,'test','ai_settings','Teste de conexão com IA realizado com sucesso','{\"provider\":\"gemini\",\"model\":\"gemini-pro\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:05:42','2025-04-18 21:05:42'),
(50,2,'update','ai_settings','Configurações de IA atualizadas','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"is_active\":false,\"has_system_prompt\":true}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:06:52','2025-04-18 21:06:52'),
(51,2,'test','ai_settings','Teste de conexão com IA realizado com sucesso','{\"provider\":\"gemini\",\"model\":\"gemini-pro\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:22:41','2025-04-18 21:22:41'),
(52,2,'update','ai_settings','Configurações de IA atualizadas','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"is_active\":false,\"has_system_prompt\":true}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:23:13','2025-04-18 21:23:13'),
(53,2,'test','ai_settings','Teste de conexão com IA realizado com sucesso','{\"provider\":\"gemini\",\"model\":\"gemini-pro\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:23:42','2025-04-18 21:23:42'),
(54,2,'update','ai_settings','Configurações de IA atualizadas','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"is_active\":false,\"has_system_prompt\":true}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:23:44','2025-04-18 21:23:44'),
(55,2,'update','ai_settings','Configurações de IA atualizadas','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"is_active\":false,\"has_system_prompt\":true}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:33:45','2025-04-18 21:33:45'),
(56,2,'error','ai_settings','Erro ao testar conexão com IA: Erro ao testar conexão com Gemini: API key not valid. Please pass a valid API key.','{\"provider\":\"gemini\",\"model\":\"gpt-3.5-turbo\",\"error\":\"Erro ao testar conex\\u00e3o com Gemini: API key not valid. Please pass a valid API key.\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:33:51','2025-04-18 21:33:51'),
(57,2,'update','ai_settings','Configurações de IA atualizadas','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"is_active\":false,\"has_system_prompt\":true}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:34:43','2025-04-18 21:34:43'),
(58,2,'test','ai_settings','Teste de conexão com IA realizado com sucesso','{\"provider\":\"gemini\",\"model\":\"gemini-pro\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:34:58','2025-04-18 21:34:58'),
(59,2,'error','ai_settings','Erro ao testar conexão com IA: Erro ao testar conexão com OpenAI: Incorrect API key provided: AIzaSyCW***************************ce-g. You can find your API key at https://platform.openai.com/account/api-keys.','{\"provider\":\"openai\",\"model\":\"gemini-pro\",\"error\":\"Erro ao testar conex\\u00e3o com OpenAI: Incorrect API key provided: AIzaSyCW***************************ce-g. You can find your API key at https:\\/\\/platform.openai.com\\/account\\/api-keys.\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:35:03','2025-04-18 21:35:03'),
(60,2,'error','ai_settings','Erro ao testar conexão com IA: Erro ao testar conexão com Gemini: API key not valid. Please pass a valid API key.','{\"provider\":\"gemini\",\"model\":\"gpt-3.5-turbo\",\"error\":\"Erro ao testar conex\\u00e3o com Gemini: API key not valid. Please pass a valid API key.\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:35:26','2025-04-18 21:35:26'),
(61,2,'update','ai_settings','Configurações de IA atualizadas','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"is_active\":false,\"has_system_prompt\":true}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:36:11','2025-04-18 21:36:11'),
(62,2,'error','ai_settings','Erro ao testar conexão com IA: Erro ao testar conexão com Gemini: API key not valid. Please pass a valid API key.','{\"provider\":\"gemini\",\"model\":\"gpt-3.5-turbo\",\"error\":\"Erro ao testar conex\\u00e3o com Gemini: API key not valid. Please pass a valid API key.\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:36:17','2025-04-18 21:36:17'),
(63,2,'update','ai_settings','Configurações de IA atualizadas','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"is_active\":false,\"has_system_prompt\":true}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:38:09','2025-04-18 21:38:09'),
(64,2,'error','ai_settings','Erro ao testar conexão com IA: Chave API do Gemini inválida: a chave parece muito curta ou vazia','{\"provider\":\"gemini\",\"model\":\"gpt-3.5-turbo\",\"error\":\"Chave API do Gemini inv\\u00e1lida: a chave parece muito curta ou vazia\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:38:11','2025-04-18 21:38:11'),
(65,2,'update','ai_settings','Configurações de IA atualizadas','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"is_active\":false,\"has_system_prompt\":true}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:38:30','2025-04-18 21:38:30'),
(66,2,'error','ai_settings','Erro ao testar conexão com IA: Chave API do Gemini inválida: a chave parece muito curta ou vazia','{\"provider\":\"gemini\",\"model\":\"gpt-3.5-turbo\",\"error\":\"Chave API do Gemini inv\\u00e1lida: a chave parece muito curta ou vazia\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:38:31','2025-04-18 21:38:31'),
(67,2,'update','ai_settings','Configurações de IA atualizadas','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"is_active\":false,\"has_system_prompt\":true}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:40:46','2025-04-18 21:40:46'),
(68,2,'error','ai_settings','Erro ao testar conexão com IA: Erro ao testar modelo Gemini: API key not valid. Please pass a valid API key.','{\"provider\":\"gemini\",\"model\":\"gpt-3.5-turbo\",\"error\":\"Erro ao testar modelo Gemini: API key not valid. Please pass a valid API key.\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:40:49','2025-04-18 21:40:49'),
(69,2,'update','ai_settings','Configurações de IA atualizadas','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"is_active\":false,\"has_system_prompt\":true}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:42:51','2025-04-18 21:42:51'),
(70,2,'update','ai_settings','Configurações de IA atualizadas','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"is_active\":false,\"has_system_prompt\":true}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:43:16','2025-04-18 21:43:16'),
(71,2,'update','ai_settings','Configurações de IA atualizadas','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"is_active\":false,\"has_system_prompt\":true}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:43:58','2025-04-18 21:43:58'),
(72,2,'update','ai_settings','Configurações de IA atualizadas','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"is_active\":false,\"has_system_prompt\":true}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:45:12','2025-04-18 21:45:12'),
(73,2,'error','ai_settings','Erro ao testar conexão com IA: Chave API do Gemini inválida. Crie uma nova em makersuite.google.com/app/apikey','{\"provider\":\"gemini\",\"model\":\"gpt-3.5-turbo\",\"error\":\"Chave API do Gemini inv\\u00e1lida. Crie uma nova em makersuite.google.com\\/app\\/apikey\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:45:18','2025-04-18 21:45:18'),
(74,2,'update','ai_settings','Configurações de IA atualizadas','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"is_active\":false,\"has_system_prompt\":true}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:47:59','2025-04-18 21:47:59'),
(75,2,'error','ai_settings','Erro ao testar conexão com IA: Chave API do Gemini inválida. Crie uma nova em makersuite.google.com/app/apikey','{\"provider\":\"gemini\",\"model\":\"gpt-3.5-turbo\",\"error\":\"Chave API do Gemini inv\\u00e1lida. Crie uma nova em makersuite.google.com\\/app\\/apikey\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:48:07','2025-04-18 21:48:07'),
(76,2,'error','ai_settings','Erro ao testar conexão com IA: Erro na API Gemini (Código 400): API key not valid. Please pass a valid API key.','{\"provider\":\"gemini\",\"model\":\"gpt-3.5-turbo\",\"error\":\"Erro na API Gemini (C\\u00f3digo 400): API key not valid. Please pass a valid API key.\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:49:26','2025-04-18 21:49:26'),
(77,2,'update','ai_settings','Configurações de IA atualizadas','{\"provider\":\"gemini\",\"model\":\"gemini-pro\",\"is_active\":false,\"has_system_prompt\":true}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:51:13','2025-04-18 21:51:13'),
(78,2,'error','ai_settings','Erro ao testar conexão com IA: Erro na API Gemini (Código 400): API key not valid. Please pass a valid API key.','{\"provider\":\"gemini\",\"model\":\"gpt-3.5-turbo\",\"error\":\"Erro na API Gemini (C\\u00f3digo 400): API key not valid. Please pass a valid API key.\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:51:16','2025-04-18 21:51:16'),
(79,2,'error','ai_settings','Erro ao testar conexão com IA: Erro na API Gemini: API key not valid. Please pass a valid API key.','{\"provider\":\"gemini\",\"model\":\"gpt-3.5-turbo\",\"error\":\"Erro na API Gemini: API key not valid. Please pass a valid API key.\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 21:52:57','2025-04-18 21:52:57'),
(80,2,'error','ai_settings','Erro ao testar conexão com IA: Erro na API Gemini: API key not valid. Please pass a valid API key.','{\"provider\":\"gemini\",\"model\":\"gpt-3.5-turbo\",\"error\":\"Erro na API Gemini: API key not valid. Please pass a valid API key.\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 22:31:17','2025-04-18 22:31:17'),
(81,2,'error','ai_settings','Erro ao testar conexão com IA: Erro na API Gemini: API key not valid. Please pass a valid API key.','{\"provider\":\"gemini\",\"model\":\"gpt-3.5-turbo\",\"error\":\"Erro na API Gemini: API key not valid. Please pass a valid API key.\"}','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','2025-04-18 22:35:42','2025-04-18 22:35:42');
/*!40000 ALTER TABLE `system_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('income','expense') NOT NULL,
  `status` enum('pending','paid') NOT NULL DEFAULT 'pending',
  `recurrence_type` enum('none','fixed','installment') NOT NULL DEFAULT 'none',
  `installment_number` int(11) DEFAULT NULL,
  `total_installments` int(11) DEFAULT NULL,
  `next_date` date DEFAULT NULL,
  `date` date NOT NULL,
  `description` varchar(255) NOT NULL,
  `amount` bigint(20) NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `account_id` bigint(20) unsigned NOT NULL,
  `notes` text DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transactions_category_id_foreign` (`category_id`),
  KEY `transactions_account_id_foreign` (`account_id`),
  KEY `transactions_user_id_foreign` (`user_id`),
  CONSTRAINT `transactions_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`),
  CONSTRAINT `transactions_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=160 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transactions`
--

LOCK TABLES `transactions` WRITE;
/*!40000 ALTER TABLE `transactions` DISABLE KEYS */;
INSERT INTO `transactions` VALUES
(31,'income','pending','none',NULL,NULL,NULL,'2025-04-16','Pagamento Salário',350,5,2,NULL,2,'2025-04-19 19:16:44','2025-04-19 19:16:44'),
(32,'expense','pending','none',NULL,NULL,NULL,'2025-04-17','Supermercado Carrefour',-25075,4,2,NULL,2,'2025-04-19 19:16:44','2025-04-19 19:16:44'),
(33,'expense','paid','none',NULL,NULL,NULL,'2025-04-18','Netflix Assinatura',-3990,4,2,NULL,2,'2025-04-19 19:16:44','2025-04-20 03:30:47'),
(34,'income','paid','none',NULL,NULL,NULL,'2025-04-19','Transferência PIX Recebida',10000,7,2,NULL,2,'2025-04-19 19:16:44','2025-04-19 19:34:59'),
(35,'income','pending','none',NULL,NULL,NULL,'2025-04-16','Pagamento Salário',350,5,2,NULL,2,'2025-04-19 19:17:27','2025-04-19 19:17:27'),
(36,'expense','pending','none',NULL,NULL,NULL,'2025-04-17','Supermercado Carrefour',-25075,4,2,NULL,2,'2025-04-19 19:17:27','2025-04-19 19:17:27'),
(37,'expense','paid','none',NULL,NULL,NULL,'2025-04-18','Netflix Assinatura',-3990,4,2,NULL,2,'2025-04-19 19:17:27','2025-04-20 03:32:09'),
(38,'income','paid','none',NULL,NULL,NULL,'2025-04-19','Transferência PIX Recebida',10000,7,2,NULL,2,'2025-04-19 19:17:27','2025-04-19 19:34:57'),
(39,'income','pending','none',NULL,NULL,NULL,'2025-04-16','Pagamento Salário',350,5,2,NULL,2,'2025-04-19 19:18:02','2025-04-19 19:18:02'),
(40,'expense','pending','none',NULL,NULL,NULL,'2025-04-17','Supermercado Carrefour',-25075,4,2,NULL,2,'2025-04-19 19:18:02','2025-04-19 19:18:02'),
(41,'expense','paid','none',NULL,NULL,NULL,'2025-04-18','Netflix Assinatura',-3990,4,2,NULL,2,'2025-04-19 19:18:02','2025-04-20 03:32:07'),
(42,'income','paid','none',NULL,NULL,NULL,'2025-04-19','Transferência PIX Recebida',10000,7,2,NULL,2,'2025-04-19 19:18:02','2025-04-19 19:35:01'),
(43,'income','pending','none',NULL,NULL,NULL,'1991-04-16','Facere velit veritatis at cum.',721,8,43,NULL,42,'2025-04-19 22:52:01','2025-04-19 22:52:01'),
(52,'expense','pending','none',NULL,NULL,NULL,'2025-04-01','Compra no débito - Auto Posto Santa Ines',5000,4,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(53,'income','pending','none',NULL,NULL,NULL,'2025-04-01','Transferência recebida pelo Pix - MICHELLE GALVAO FREIRE - •••.234.998-•• - BCO C6 S.A. (0336) Agência: 1 Conta: 27968388-0',3000,7,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(54,'expense','pending','none',NULL,NULL,NULL,'2025-04-01','Compra no débito - Restaurante e Lanchon',500,4,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(55,'expense','pending','none',NULL,NULL,NULL,'2025-04-01','Compra no débito - Montana',2000,4,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(56,'expense','pending','none',NULL,NULL,NULL,'2025-04-02','Transferência enviada pelo Pix - ONLITEC INFORMATICA - 13.666.555/0001-19 - Nubank (0260) Agência: 1 Conta: 61409826-4',1000,6,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(57,'expense','pending','none',NULL,NULL,NULL,'2025-04-02','Compra no débito - Drogasil2422',299,4,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(58,'expense','pending','none',NULL,NULL,NULL,'2025-04-02','Compra no débito - Bandeirante Auto Post',700,4,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(59,'income','pending','none',NULL,NULL,NULL,'2025-04-02','Transferência recebida pelo Pix - 57815082 BEATRIZ DOMINGOS GALVAO FREIRE - 57.815.082/0001-40 - CORA SCFI (0403) Agência: 1 Conta: 5382301-2',5000,7,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(60,'expense','pending','none',NULL,NULL,NULL,'2025-04-02','Compra no débito - Marinanicodetede',700,4,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(61,'expense','pending','none',NULL,NULL,NULL,'2025-04-02','Compra no débito - Rede Sete Estrelas P',3000,4,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(62,'expense','pending','none',NULL,NULL,NULL,'2025-04-03','Compra no débito - Marinanicodetede',950,4,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(63,'expense','pending','none',NULL,NULL,NULL,'2025-04-03','Compra no débito - Marinanicodetede',250,4,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(64,'income','pending','none',NULL,NULL,NULL,'2025-04-03','Transferência recebida pelo Pix - 57815082 BEATRIZ DOMINGOS GALVAO FREIRE - 57.815.082/0001-40 - CORA SCFI (0403) Agência: 1 Conta: 5382301-2',5000,7,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(65,'expense','pending','none',NULL,NULL,NULL,'2025-04-03','Compra no débito - Cunha',5000,4,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(66,'income','pending','none',NULL,NULL,NULL,'2025-04-03','Transferência recebida pelo Pix - 57815082 BEATRIZ DOMINGOS GALVAO FREIRE - 57.815.082/0001-40 - CORA SCFI (0403) Agência: 1 Conta: 5382301-2',50000,7,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(67,'expense','pending','none',NULL,NULL,NULL,'2025-04-03','Compra no débito - Assai Atacadista Lj121',50076,4,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(68,'income','pending','none',NULL,NULL,NULL,'2025-04-04','Transferência recebida pelo Pix - CTECH SOLUCOES - 35.614.415/0001-30 - BANCO INTER (0077) Agência: 1 Conta: 4691835-3',10000,7,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(69,'expense','pending','none',NULL,NULL,NULL,'2025-04-04','Compra no débito - Rede Sete Estrelas P',3000,4,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(70,'expense','pending','none',NULL,NULL,NULL,'2025-04-04','Transferência enviada pelo Pix - Pagarme Pagamentos SA - 18.727.053/0001-74 - STONE IP S.A. (0197) Agência: 1 Conta: 16714636-4',3637,6,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(71,'expense','pending','none',NULL,NULL,NULL,'2025-04-05','Transferência enviada pelo Pix - 57815082 BEATRIZ DOMINGOS GALVAO FREIRE - 57.815.082/0001-40 - CORA SCFI (0403) Agência: 1 Conta: 5382301-2',3000,6,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(72,'income','pending','none',NULL,NULL,NULL,'2025-04-08','Transferência recebida pelo Pix - ANA LUCIA ROCHA MAGALHAES - •••.807.638-•• - BCO SANTANDER (BRASIL) S.A. (0033) Agência: 3310 Conta: 1017696-7',10500,7,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(73,'expense','pending','none',NULL,NULL,NULL,'2025-04-08','Compra no débito - Restaurante e Lanchon',1500,4,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(74,'expense','pending','none',NULL,NULL,NULL,'2025-04-08','Compra no débito - Assai Atacadista Lj121',9000,4,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(75,'income','pending','none',NULL,NULL,NULL,'2025-04-08','Transferência recebida pelo Pix - 57815082 BEATRIZ DOMINGOS GALVAO FREIRE - 57.815.082/0001-40 - CORA SCFI (0403) Agência: 1 Conta: 5382301-2',3000,7,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(76,'expense','pending','none',NULL,NULL,NULL,'2025-04-08','Compra no débito - Autopostolc',3000,4,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(77,'income','pending','none',NULL,NULL,NULL,'2025-04-10','Transferência recebida pelo Pix - 57815082 BEATRIZ DOMINGOS GALVAO FREIRE - 57.815.082/0001-40 - CORA SCFI (0403) Agência: 1 Conta: 5382301-2',5000,7,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(78,'expense','pending','none',NULL,NULL,NULL,'2025-04-10','Compra no débito - Autopostolc',5000,4,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(79,'income','pending','none',NULL,NULL,NULL,'2025-04-10','Transferência recebida pelo Pix - 57815082 BEATRIZ DOMINGOS GALVAO FREIRE - 57.815.082/0001-40 - CORA SCFI (0403) Agência: 1 Conta: 5382301-2',3000,7,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(80,'expense','pending','none',NULL,NULL,NULL,'2025-04-10','Compra no débito - Autopostolc',3000,4,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(81,'income','pending','none',NULL,NULL,NULL,'2025-04-11','Transferência recebida pelo Pix - Alessandro Galvão Freire - •••.841.228-•• - 99PAY IP S.A. Agência: 1 Conta: 514588-0',300,7,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(82,'income','pending','none',NULL,NULL,NULL,'2025-04-11','Transferência recebida pelo Pix - 57815082 BEATRIZ DOMINGOS GALVAO FREIRE - 57.815.082/0001-40 - CORA SCFI (0403) Agência: 1 Conta: 5382301-2',700,7,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(83,'income','pending','none',NULL,NULL,NULL,'2025-04-11','Transferência Recebida - Márcia Aparecida Domingos Freire - •••.569.868-•• - NU PAGAMENTOS - IP (0260) Agência: 1 Conta: 15572782-4',50,7,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(84,'expense','pending','none',NULL,NULL,NULL,'2025-04-11','Compra no débito - Autopostolc',1500,4,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(85,'income','pending','none',NULL,NULL,NULL,'2025-04-12','Transferência Recebida - Márcia Aparecida Domingos Freire - •••.569.868-•• - NU PAGAMENTOS - IP (0260) Agência: 1 Conta: 15572782-4',5000,7,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(86,'expense','pending','none',NULL,NULL,NULL,'2025-04-12','Compra no débito - Autopostolc',5000,4,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(87,'income','pending','none',NULL,NULL,NULL,'2025-04-14','Transferência recebida pelo Pix - DOSEVITA PRODUTOS NATURAIS LTDA - 31.196.826/0001-00 - CAIXA ECONOMICA FEDERAL (0104) Agência: 2935 Conta: 578063836-1',22000,7,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(88,'expense','pending','none',NULL,NULL,NULL,'2025-04-14','Débito em conta',3332,4,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(89,'expense','pending','none',NULL,NULL,NULL,'2025-04-14','Compra no débito - Kalunga',7280,4,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(90,'expense','pending','none',NULL,NULL,NULL,'2025-04-14','Compra no débito - Acai do Genio',3750,4,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(91,'expense','pending','none',NULL,NULL,NULL,'2025-04-15','Compra no débito - Marli Cunha Mercado',2357,4,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(92,'income','pending','none',NULL,NULL,NULL,'2025-04-15','Transferência Recebida - Márcia Aparecida Domingos Freire - •••.569.868-•• - NU PAGAMENTOS - IP (0260) Agência: 1 Conta: 15572782-4',8000,7,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(93,'expense','pending','none',NULL,NULL,NULL,'2025-04-15','Compra no débito - Autopostolc',6000,4,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(94,'expense','pending','none',NULL,NULL,NULL,'2025-04-15','Compra no débito - Raia3049',599,4,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(95,'income','pending','none',NULL,NULL,NULL,'2025-04-15','Transferência Recebida - Márcia Aparecida Domingos Freire - •••.569.868-•• - NU PAGAMENTOS - IP (0260) Agência: 1 Conta: 15572782-4',5000,7,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(96,'expense','pending','none',NULL,NULL,NULL,'2025-04-15','Compra no débito - Rede Sete Estrelas',5000,4,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(97,'expense','pending','none',NULL,NULL,NULL,'2025-04-15','Compra no débito - Restaurante 4r',1600,4,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(98,'expense','pending','none',NULL,NULL,NULL,'2025-04-16','Compra no débito - Raia3049',599,4,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(99,'expense','pending','none',NULL,NULL,NULL,'2025-04-16','Compra no débito - Marinanicodetede',1700,4,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(100,'expense','pending','none',NULL,NULL,NULL,'2025-04-16','Compra no débito - Nutri Vale Produtos Na',1482,4,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(101,'income','pending','none',NULL,NULL,NULL,'2025-04-16','Transferência recebida pelo Pix - 57815082 BEATRIZ DOMINGOS GALVAO FREIRE - 57.815.082/0001-40 - CORA SCFI (0403) Agência: 1 Conta: 5382301-2',5000,7,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(102,'expense','pending','none',NULL,NULL,NULL,'2025-04-16','Compra no débito - Rede Sete Estrelas',3000,4,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(103,'expense','pending','none',NULL,NULL,NULL,'2025-04-17','Compra no débito - Marinanicodetede',1500,4,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 02:37:59'),
(104,'income','paid','none',NULL,NULL,NULL,'2025-04-18','Transferência recebida pelo Pix - DANIELLE F F ABREU - •••.391.087-•• - BCO DO BRASIL S.A. (0001) Agência: 2909 Conta: 49585-9',6000,7,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 03:32:19'),
(105,'expense','paid','none',NULL,NULL,NULL,'2025-04-18','Compra no débito - Mercado Seven Ii',1598,4,2,NULL,2,'2025-04-20 02:37:59','2025-04-20 03:32:16'),
(106,'expense','pending','none',NULL,NULL,NULL,'2025-04-01','Compra no débito - Auto Posto Santa Ines',5000,4,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(107,'income','pending','none',NULL,NULL,NULL,'2025-04-01','Transferência recebida pelo Pix - MICHELLE GALVAO FREIRE - •••.234.998-•• - BCO C6 S.A. (0336) Agência: 1 Conta: 27968388-0',3000,7,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(108,'expense','pending','none',NULL,NULL,NULL,'2025-04-01','Compra no débito - Restaurante e Lanchon',500,4,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(109,'expense','pending','none',NULL,NULL,NULL,'2025-04-01','Compra no débito - Montana',2000,4,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(110,'expense','pending','none',NULL,NULL,NULL,'2025-04-02','Transferência enviada pelo Pix - ONLITEC INFORMATICA - 13.666.555/0001-19 - Nubank (0260) Agência: 1 Conta: 61409826-4',1000,6,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(111,'expense','pending','none',NULL,NULL,NULL,'2025-04-02','Compra no débito - Drogasil2422',299,4,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(112,'expense','pending','none',NULL,NULL,NULL,'2025-04-02','Compra no débito - Bandeirante Auto Post',700,4,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(113,'income','pending','none',NULL,NULL,NULL,'2025-04-02','Transferência recebida pelo Pix - 57815082 BEATRIZ DOMINGOS GALVAO FREIRE - 57.815.082/0001-40 - CORA SCFI (0403) Agência: 1 Conta: 5382301-2',5000,7,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(114,'expense','pending','none',NULL,NULL,NULL,'2025-04-02','Compra no débito - Marinanicodetede',700,4,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(115,'expense','pending','none',NULL,NULL,NULL,'2025-04-02','Compra no débito - Rede Sete Estrelas P',3000,4,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(116,'expense','pending','none',NULL,NULL,NULL,'2025-04-03','Compra no débito - Marinanicodetede',950,4,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(117,'expense','pending','none',NULL,NULL,NULL,'2025-04-03','Compra no débito - Marinanicodetede',250,4,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(118,'income','pending','none',NULL,NULL,NULL,'2025-04-03','Transferência recebida pelo Pix - 57815082 BEATRIZ DOMINGOS GALVAO FREIRE - 57.815.082/0001-40 - CORA SCFI (0403) Agência: 1 Conta: 5382301-2',5000,7,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(119,'expense','pending','none',NULL,NULL,NULL,'2025-04-03','Compra no débito - Cunha',5000,4,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(120,'income','pending','none',NULL,NULL,NULL,'2025-04-03','Transferência recebida pelo Pix - 57815082 BEATRIZ DOMINGOS GALVAO FREIRE - 57.815.082/0001-40 - CORA SCFI (0403) Agência: 1 Conta: 5382301-2',50000,7,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(121,'expense','pending','none',NULL,NULL,NULL,'2025-04-03','Compra no débito - Assai Atacadista Lj121',50076,4,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(122,'income','pending','none',NULL,NULL,NULL,'2025-04-04','Transferência recebida pelo Pix - CTECH SOLUCOES - 35.614.415/0001-30 - BANCO INTER (0077) Agência: 1 Conta: 4691835-3',10000,7,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(123,'expense','pending','none',NULL,NULL,NULL,'2025-04-04','Compra no débito - Rede Sete Estrelas P',3000,4,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(124,'expense','pending','none',NULL,NULL,NULL,'2025-04-04','Transferência enviada pelo Pix - Pagarme Pagamentos SA - 18.727.053/0001-74 - STONE IP S.A. (0197) Agência: 1 Conta: 16714636-4',3637,6,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(125,'expense','pending','none',NULL,NULL,NULL,'2025-04-05','Transferência enviada pelo Pix - 57815082 BEATRIZ DOMINGOS GALVAO FREIRE - 57.815.082/0001-40 - CORA SCFI (0403) Agência: 1 Conta: 5382301-2',3000,6,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(126,'income','pending','none',NULL,NULL,NULL,'2025-04-08','Transferência recebida pelo Pix - ANA LUCIA ROCHA MAGALHAES - •••.807.638-•• - BCO SANTANDER (BRASIL) S.A. (0033) Agência: 3310 Conta: 1017696-7',10500,7,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(127,'expense','pending','none',NULL,NULL,NULL,'2025-04-08','Compra no débito - Restaurante e Lanchon',1500,4,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(128,'expense','pending','none',NULL,NULL,NULL,'2025-04-08','Compra no débito - Assai Atacadista Lj121',9000,4,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(129,'income','pending','none',NULL,NULL,NULL,'2025-04-08','Transferência recebida pelo Pix - 57815082 BEATRIZ DOMINGOS GALVAO FREIRE - 57.815.082/0001-40 - CORA SCFI (0403) Agência: 1 Conta: 5382301-2',3000,7,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(130,'expense','pending','none',NULL,NULL,NULL,'2025-04-08','Compra no débito - Autopostolc',3000,4,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(131,'income','pending','none',NULL,NULL,NULL,'2025-04-10','Transferência recebida pelo Pix - 57815082 BEATRIZ DOMINGOS GALVAO FREIRE - 57.815.082/0001-40 - CORA SCFI (0403) Agência: 1 Conta: 5382301-2',5000,7,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(132,'expense','pending','none',NULL,NULL,NULL,'2025-04-10','Compra no débito - Autopostolc',5000,4,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(133,'income','pending','none',NULL,NULL,NULL,'2025-04-10','Transferência recebida pelo Pix - 57815082 BEATRIZ DOMINGOS GALVAO FREIRE - 57.815.082/0001-40 - CORA SCFI (0403) Agência: 1 Conta: 5382301-2',3000,7,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(134,'expense','pending','none',NULL,NULL,NULL,'2025-04-10','Compra no débito - Autopostolc',3000,4,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(135,'income','pending','none',NULL,NULL,NULL,'2025-04-11','Transferência recebida pelo Pix - Alessandro Galvão Freire - •••.841.228-•• - 99PAY IP S.A. Agência: 1 Conta: 514588-0',300,7,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(136,'income','pending','none',NULL,NULL,NULL,'2025-04-11','Transferência recebida pelo Pix - 57815082 BEATRIZ DOMINGOS GALVAO FREIRE - 57.815.082/0001-40 - CORA SCFI (0403) Agência: 1 Conta: 5382301-2',700,7,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(137,'income','pending','none',NULL,NULL,NULL,'2025-04-11','Transferência Recebida - Márcia Aparecida Domingos Freire - •••.569.868-•• - NU PAGAMENTOS - IP (0260) Agência: 1 Conta: 15572782-4',50,7,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(138,'expense','pending','none',NULL,NULL,NULL,'2025-04-11','Compra no débito - Autopostolc',1500,4,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(139,'income','pending','none',NULL,NULL,NULL,'2025-04-12','Transferência Recebida - Márcia Aparecida Domingos Freire - •••.569.868-•• - NU PAGAMENTOS - IP (0260) Agência: 1 Conta: 15572782-4',5000,7,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(140,'expense','pending','none',NULL,NULL,NULL,'2025-04-12','Compra no débito - Autopostolc',5000,4,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(141,'income','pending','none',NULL,NULL,NULL,'2025-04-14','Transferência recebida pelo Pix - DOSEVITA PRODUTOS NATURAIS LTDA - 31.196.826/0001-00 - CAIXA ECONOMICA FEDERAL (0104) Agência: 2935 Conta: 578063836-1',22000,7,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(142,'expense','pending','none',NULL,NULL,NULL,'2025-04-14','Débito em conta',3332,4,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(143,'expense','pending','none',NULL,NULL,NULL,'2025-04-14','Compra no débito - Kalunga',7280,4,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(144,'expense','pending','none',NULL,NULL,NULL,'2025-04-14','Compra no débito - Acai do Genio',3750,4,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(145,'expense','pending','none',NULL,NULL,NULL,'2025-04-15','Compra no débito - Marli Cunha Mercado',2357,4,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(146,'income','pending','none',NULL,NULL,NULL,'2025-04-15','Transferência Recebida - Márcia Aparecida Domingos Freire - •••.569.868-•• - NU PAGAMENTOS - IP (0260) Agência: 1 Conta: 15572782-4',8000,7,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(147,'expense','pending','none',NULL,NULL,NULL,'2025-04-15','Compra no débito - Autopostolc',6000,4,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(148,'expense','pending','none',NULL,NULL,NULL,'2025-04-15','Compra no débito - Raia3049',599,4,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(149,'income','pending','none',NULL,NULL,NULL,'2025-04-15','Transferência Recebida - Márcia Aparecida Domingos Freire - •••.569.868-•• - NU PAGAMENTOS - IP (0260) Agência: 1 Conta: 15572782-4',5000,7,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(150,'expense','pending','none',NULL,NULL,NULL,'2025-04-15','Compra no débito - Rede Sete Estrelas',5000,4,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(151,'expense','pending','none',NULL,NULL,NULL,'2025-04-15','Compra no débito - Restaurante 4r',1600,4,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(152,'expense','pending','none',NULL,NULL,NULL,'2025-04-16','Compra no débito - Raia3049',599,4,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(153,'expense','pending','none',NULL,NULL,NULL,'2025-04-16','Compra no débito - Marinanicodetede',1700,4,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(154,'expense','pending','none',NULL,NULL,NULL,'2025-04-16','Compra no débito - Nutri Vale Produtos Na',1482,4,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(155,'income','pending','none',NULL,NULL,NULL,'2025-04-16','Transferência recebida pelo Pix - 57815082 BEATRIZ DOMINGOS GALVAO FREIRE - 57.815.082/0001-40 - CORA SCFI (0403) Agência: 1 Conta: 5382301-2',5000,7,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(156,'expense','pending','none',NULL,NULL,NULL,'2025-04-16','Compra no débito - Rede Sete Estrelas',3000,4,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(157,'expense','pending','none',NULL,NULL,NULL,'2025-04-17','Compra no débito - Marinanicodetede',1500,4,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 02:39:24'),
(158,'income','paid','none',NULL,NULL,NULL,'2025-04-18','Transferência recebida pelo Pix - DANIELLE F F ABREU - •••.391.087-•• - BCO DO BRASIL S.A. (0001) Agência: 2909 Conta: 49585-9',6000,7,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 03:32:13'),
(159,'expense','paid','none',NULL,NULL,NULL,'2025-04-18','Compra no débito - Mercado Seven Ii',1598,4,2,NULL,2,'2025-04-20 02:39:24','2025-04-20 03:32:05');
/*!40000 ALTER TABLE `transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `email_notifications` tinyint(1) NOT NULL DEFAULT 1,
  `push_notifications` tinyint(1) NOT NULL DEFAULT 0,
  `due_date_notifications` tinyint(1) NOT NULL DEFAULT 1,
  `whatsapp_notifications` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'Administrador','admin@example.com',NULL,'2025-04-18 05:38:24','$2y$12$s3zh73HjkN0.gqkKQPaR/e8rTQ4kTkqtl/C.S8j1A.F3moOAuaKqG',1,1,NULL,'2025-04-18 05:38:24','2025-04-18 05:38:24',1,0,1,0),
(2,'Galvatec','galvatec@gmail.com',NULL,'2025-04-18 05:38:54','$2y$12$zw.V3uKeL/rVnULg3dlEGuC7ajScXSpds2KRMvJ1z9ZaULyOG2YQi',1,1,NULL,'2025-04-18 05:38:54','2025-04-18 13:38:53',1,0,1,0),
(3,'Dr. Kade Boehm Sr.','emmerich.gaylord@example.com',NULL,'2025-04-19 22:40:43','$2y$04$wKI/DBKPqgdfRe189QbcYOZYZa2RzUWzNqtd84HZTtkyqWOI.cU46',1,0,'tXzvF0asaT','2025-04-19 22:40:43','2025-04-19 22:40:43',1,0,1,0),
(4,'Octavia Jacobi II','champlin.torrance@example.net',NULL,'2025-04-19 22:40:43','$2y$04$wKI/DBKPqgdfRe189QbcYOZYZa2RzUWzNqtd84HZTtkyqWOI.cU46',1,0,'lBrsjgWTCX','2025-04-19 22:40:43','2025-04-19 22:40:43',1,0,1,0),
(5,'Wellington Halvorson','hilpert.lesley@example.com',NULL,'2025-04-19 22:40:43','$2y$04$wKI/DBKPqgdfRe189QbcYOZYZa2RzUWzNqtd84HZTtkyqWOI.cU46',1,0,'AkqHfp81Tt','2025-04-19 22:40:43','2025-04-19 22:40:43',1,0,1,0),
(6,'Mr. Josue Tromp V','murphy.barry@example.com',NULL,'2025-04-19 22:40:43','$2y$04$wKI/DBKPqgdfRe189QbcYOZYZa2RzUWzNqtd84HZTtkyqWOI.cU46',1,0,'ITqXkcrDVZ','2025-04-19 22:40:43','2025-04-19 22:40:43',1,0,1,0),
(7,'Mr. Derrick Erdman','upollich@example.net',NULL,'2025-04-19 22:40:43','$2y$04$wKI/DBKPqgdfRe189QbcYOZYZa2RzUWzNqtd84HZTtkyqWOI.cU46',1,0,'azq3UiJC6A','2025-04-19 22:40:43','2025-04-19 22:40:43',1,0,1,0),
(8,'Norma Adams','bechtelar.maryam@example.com',NULL,'2025-04-19 22:40:43','$2y$04$wKI/DBKPqgdfRe189QbcYOZYZa2RzUWzNqtd84HZTtkyqWOI.cU46',1,0,'uBp6jBrAXK','2025-04-19 22:40:43','2025-04-19 22:40:43',1,0,1,0),
(9,'Tomas Mills MD','kelsie.jones@example.net',NULL,'2025-04-19 22:40:43','$2y$04$wKI/DBKPqgdfRe189QbcYOZYZa2RzUWzNqtd84HZTtkyqWOI.cU46',1,0,'MEWnYDsvzd','2025-04-19 22:40:43','2025-04-19 22:40:43',1,0,1,0),
(10,'Joesph Durgan I','rossie75@example.com',NULL,'2025-04-19 22:40:43','$2y$04$wKI/DBKPqgdfRe189QbcYOZYZa2RzUWzNqtd84HZTtkyqWOI.cU46',1,0,'f3NJ11JS5g','2025-04-19 22:40:43','2025-04-19 22:40:43',1,0,1,0),
(11,'Lauryn Reichel','roberts.ona@example.org',NULL,'2025-04-19 22:40:43','$2y$04$wKI/DBKPqgdfRe189QbcYOZYZa2RzUWzNqtd84HZTtkyqWOI.cU46',1,0,'z1YrQTKLgY','2025-04-19 22:40:43','2025-04-19 22:40:43',1,0,1,0),
(12,'Allen Runolfsdottir','zulauf.reyna@example.com',NULL,'2025-04-19 22:40:44','$2y$04$wKI/DBKPqgdfRe189QbcYOZYZa2RzUWzNqtd84HZTtkyqWOI.cU46',1,0,'P97tKXUOSp','2025-04-19 22:40:44','2025-04-19 22:40:44',1,0,1,0),
(13,'Prof. Anabelle Hessel','nils47@example.org',NULL,'2025-04-19 22:40:44','$2y$04$wKI/DBKPqgdfRe189QbcYOZYZa2RzUWzNqtd84HZTtkyqWOI.cU46',1,0,'xFABhVEdFX','2025-04-19 22:40:44','2025-04-19 22:40:44',1,0,1,0),
(14,'Judson Sawayn','schaefer.karelle@example.org',NULL,'2025-04-19 22:40:44','$2y$04$wKI/DBKPqgdfRe189QbcYOZYZa2RzUWzNqtd84HZTtkyqWOI.cU46',1,0,'bOWOScKOma','2025-04-19 22:40:44','2025-04-19 22:40:44',1,0,1,0),
(15,'Prof. Guadalupe Weber PhD','christiansen.retta@example.com',NULL,'2025-04-19 22:40:44','$2y$04$wKI/DBKPqgdfRe189QbcYOZYZa2RzUWzNqtd84HZTtkyqWOI.cU46',1,0,'pPBv3pIxmS','2025-04-19 22:40:44','2025-04-19 22:40:44',1,0,1,0),
(16,'Mr. Timmothy West II','rath.chadrick@example.net',NULL,'2025-04-19 22:48:03','$2y$04$OeJrtTs/speSvrAG2O2lguJ37eu.VawDduldO5aaSkS8Hr3ZL1CPa',1,0,'ztcQrb0Meg','2025-04-19 22:48:03','2025-04-19 22:48:03',1,0,1,0),
(17,'Dr. Judah Cartwright','adeline.sawayn@example.com',NULL,'2025-04-19 22:48:04','$2y$04$OeJrtTs/speSvrAG2O2lguJ37eu.VawDduldO5aaSkS8Hr3ZL1CPa',1,0,'CJtUTmhHe9','2025-04-19 22:48:04','2025-04-19 22:48:04',1,0,1,0),
(18,'Uriah Wyman','clair.ortiz@example.com',NULL,'2025-04-19 22:48:04','$2y$04$OeJrtTs/speSvrAG2O2lguJ37eu.VawDduldO5aaSkS8Hr3ZL1CPa',1,0,'EsDVoC9iS8','2025-04-19 22:48:04','2025-04-19 22:48:04',1,0,1,0),
(19,'Laury Bernhard','ddaugherty@example.com',NULL,'2025-04-19 22:48:04','$2y$04$OeJrtTs/speSvrAG2O2lguJ37eu.VawDduldO5aaSkS8Hr3ZL1CPa',1,0,'WyGZqxeN1k','2025-04-19 22:48:04','2025-04-19 22:48:04',1,0,1,0),
(20,'Matt Barton','hgerlach@example.org',NULL,'2025-04-19 22:48:04','$2y$04$OeJrtTs/speSvrAG2O2lguJ37eu.VawDduldO5aaSkS8Hr3ZL1CPa',1,0,'EZI5gGw4e2','2025-04-19 22:48:04','2025-04-19 22:48:04',1,0,1,0),
(21,'Eldora Rempel','karine83@example.org',NULL,'2025-04-19 22:48:04','$2y$04$OeJrtTs/speSvrAG2O2lguJ37eu.VawDduldO5aaSkS8Hr3ZL1CPa',1,0,'WiyaksXuBg','2025-04-19 22:48:04','2025-04-19 22:48:04',1,0,1,0),
(22,'Brandon Stiedemann','stark.adriana@example.net',NULL,'2025-04-19 22:48:04','$2y$04$OeJrtTs/speSvrAG2O2lguJ37eu.VawDduldO5aaSkS8Hr3ZL1CPa',1,0,'6gWb9Guw4o','2025-04-19 22:48:04','2025-04-19 22:48:04',1,0,1,0),
(23,'Kimberly Douglas','myrna.dicki@example.org',NULL,'2025-04-19 22:48:04','$2y$04$OeJrtTs/speSvrAG2O2lguJ37eu.VawDduldO5aaSkS8Hr3ZL1CPa',1,0,'Bf5psEjAkA','2025-04-19 22:48:04','2025-04-19 22:48:04',1,0,1,0),
(24,'Oren Macejkovic','anne52@example.net',NULL,'2025-04-19 22:48:04','$2y$04$OeJrtTs/speSvrAG2O2lguJ37eu.VawDduldO5aaSkS8Hr3ZL1CPa',1,0,'YGV5M5vyMc','2025-04-19 22:48:04','2025-04-19 22:48:04',1,0,1,0),
(25,'Juvenal Kub','gturner@example.org',NULL,'2025-04-19 22:48:04','$2y$04$OeJrtTs/speSvrAG2O2lguJ37eu.VawDduldO5aaSkS8Hr3ZL1CPa',1,0,'B9TgG8Aeuv','2025-04-19 22:48:04','2025-04-19 22:48:04',1,0,1,0),
(26,'Russel Armstrong','kunde.horacio@example.org',NULL,'2025-04-19 22:48:04','$2y$04$OeJrtTs/speSvrAG2O2lguJ37eu.VawDduldO5aaSkS8Hr3ZL1CPa',1,0,'G1T4dpBZ6M','2025-04-19 22:48:04','2025-04-19 22:48:04',1,0,1,0),
(27,'Prof. Lonie Pagac','dconnelly@example.org',NULL,'2025-04-19 22:48:04','$2y$04$OeJrtTs/speSvrAG2O2lguJ37eu.VawDduldO5aaSkS8Hr3ZL1CPa',1,0,'03cKYPhXth','2025-04-19 22:48:04','2025-04-19 22:48:04',1,0,1,0),
(28,'Jennifer Kutch','vmaggio@example.org',NULL,'2025-04-19 22:48:04','$2y$04$OeJrtTs/speSvrAG2O2lguJ37eu.VawDduldO5aaSkS8Hr3ZL1CPa',1,0,'XorRRwEJGZ','2025-04-19 22:48:04','2025-04-19 22:48:04',1,0,1,0),
(29,'Prof. Hector Kub III','jarrell.dickinson@example.com',NULL,'2025-04-19 22:48:39','$2y$04$jydBSRg0xjnkUNVLFKNPFu3tZyv2rJmgww64Jko9P6PwdYeW5sAJi',1,0,'mXYikvRA7J','2025-04-19 22:48:39','2025-04-19 22:48:39',1,0,1,0),
(30,'Deonte Robel','qbrekke@example.net',NULL,'2025-04-19 22:48:39','$2y$04$jydBSRg0xjnkUNVLFKNPFu3tZyv2rJmgww64Jko9P6PwdYeW5sAJi',1,0,'FxvtBdG8jc','2025-04-19 22:48:39','2025-04-19 22:48:39',1,0,1,0),
(31,'Erich Adams','kuhn.darius@example.org',NULL,'2025-04-19 22:48:39','$2y$04$jydBSRg0xjnkUNVLFKNPFu3tZyv2rJmgww64Jko9P6PwdYeW5sAJi',1,0,'XpKogDUdSa','2025-04-19 22:48:39','2025-04-19 22:48:39',1,0,1,0),
(32,'Luna Kautzer','tblick@example.com',NULL,'2025-04-19 22:48:39','$2y$04$jydBSRg0xjnkUNVLFKNPFu3tZyv2rJmgww64Jko9P6PwdYeW5sAJi',1,0,'oZ2wWqPk4y','2025-04-19 22:48:39','2025-04-19 22:48:39',1,0,1,0),
(33,'Dr. Isai Powlowski','jessica18@example.com',NULL,'2025-04-19 22:48:39','$2y$04$jydBSRg0xjnkUNVLFKNPFu3tZyv2rJmgww64Jko9P6PwdYeW5sAJi',1,0,'QttvvkvMvo','2025-04-19 22:48:39','2025-04-19 22:48:39',1,0,1,0),
(34,'Jennie Swift','zola15@example.net',NULL,'2025-04-19 22:48:39','$2y$04$jydBSRg0xjnkUNVLFKNPFu3tZyv2rJmgww64Jko9P6PwdYeW5sAJi',1,0,'HquqS07Nbb','2025-04-19 22:48:39','2025-04-19 22:48:39',1,0,1,0),
(35,'Lyric Altenwerth','effertz.janiya@example.org',NULL,'2025-04-19 22:48:39','$2y$04$jydBSRg0xjnkUNVLFKNPFu3tZyv2rJmgww64Jko9P6PwdYeW5sAJi',1,0,'pnvzdCfBkD','2025-04-19 22:48:39','2025-04-19 22:48:39',1,0,1,0),
(36,'Adriana Sipes','ysporer@example.com',NULL,'2025-04-19 22:48:39','$2y$04$jydBSRg0xjnkUNVLFKNPFu3tZyv2rJmgww64Jko9P6PwdYeW5sAJi',1,0,'EdIcdrEarL','2025-04-19 22:48:39','2025-04-19 22:48:39',1,0,1,0),
(37,'Dr. Ida Simonis','skye95@example.net',NULL,'2025-04-19 22:48:39','$2y$04$jydBSRg0xjnkUNVLFKNPFu3tZyv2rJmgww64Jko9P6PwdYeW5sAJi',1,0,'vRbTfKG4Zf','2025-04-19 22:48:39','2025-04-19 22:48:39',1,0,1,0),
(38,'Dorian Klein','fsporer@example.org',NULL,'2025-04-19 22:48:39','$2y$04$jydBSRg0xjnkUNVLFKNPFu3tZyv2rJmgww64Jko9P6PwdYeW5sAJi',1,0,'Nwt3VCfvVy','2025-04-19 22:48:39','2025-04-19 22:48:39',1,0,1,0),
(39,'Estell Bernhard DDS','fwilliamson@example.com',NULL,'2025-04-19 22:48:39','$2y$04$jydBSRg0xjnkUNVLFKNPFu3tZyv2rJmgww64Jko9P6PwdYeW5sAJi',1,0,'lHnezKu4Lb','2025-04-19 22:48:39','2025-04-19 22:48:39',1,0,1,0),
(40,'Miss Katlynn Kling','brandt.raynor@example.net',NULL,'2025-04-19 22:48:39','$2y$04$jydBSRg0xjnkUNVLFKNPFu3tZyv2rJmgww64Jko9P6PwdYeW5sAJi',1,0,'aKSny7vIyq','2025-04-19 22:48:39','2025-04-19 22:48:39',1,0,1,0),
(41,'Jamaal Schowalter','mitchell.jerad@example.net',NULL,'2025-04-19 22:48:39','$2y$04$jydBSRg0xjnkUNVLFKNPFu3tZyv2rJmgww64Jko9P6PwdYeW5sAJi',1,0,'nNV98mTaA7','2025-04-19 22:48:39','2025-04-19 22:48:39',1,0,1,0),
(42,'Elvis Kirlin','joanie.considine@example.com',NULL,'2025-04-19 22:52:01','$2y$04$JY7kL3.EH3SrNU4yAe4/AeZ8E7Qk2YglgGm5Jib9m7iXVqsUPjVpW',1,0,'eMta0RpXD1','2025-04-19 22:52:01','2025-04-19 22:52:01',1,0,1,0),
(43,'Liana Hill','jackie.fadel@example.net',NULL,'2025-04-19 22:52:01','$2y$04$JY7kL3.EH3SrNU4yAe4/AeZ8E7Qk2YglgGm5Jib9m7iXVqsUPjVpW',1,0,'0uYMZe4OF1','2025-04-19 22:52:01','2025-04-19 22:52:01',1,0,1,0),
(44,'Elroy Daugherty MD','camilla.rowe@example.org',NULL,'2025-04-19 22:52:01','$2y$04$JY7kL3.EH3SrNU4yAe4/AeZ8E7Qk2YglgGm5Jib9m7iXVqsUPjVpW',1,0,'yMnR6asSmF','2025-04-19 22:52:01','2025-04-19 22:52:01',1,0,1,0),
(45,'Audie Keebler Sr.','maximilian.walker@example.com',NULL,'2025-04-19 22:52:01','$2y$04$JY7kL3.EH3SrNU4yAe4/AeZ8E7Qk2YglgGm5Jib9m7iXVqsUPjVpW',1,0,'ELNfi6HV3U','2025-04-19 22:52:01','2025-04-19 22:52:01',1,0,1,0),
(46,'Matt Wilderman','abelardo.price@example.org',NULL,'2025-04-19 22:52:01','$2y$04$JY7kL3.EH3SrNU4yAe4/AeZ8E7Qk2YglgGm5Jib9m7iXVqsUPjVpW',1,0,'wQnW0AEGnr','2025-04-19 22:52:01','2025-04-19 22:52:01',1,0,1,0),
(47,'Roy Mueller Sr.','vsenger@example.com',NULL,'2025-04-19 22:52:01','$2y$04$JY7kL3.EH3SrNU4yAe4/AeZ8E7Qk2YglgGm5Jib9m7iXVqsUPjVpW',1,0,'xtRYlUvrh6','2025-04-19 22:52:01','2025-04-19 22:52:01',1,0,1,0),
(48,'Prof. Eriberto Goldner Jr.','doyle.donato@example.net',NULL,'2025-04-19 22:52:01','$2y$04$JY7kL3.EH3SrNU4yAe4/AeZ8E7Qk2YglgGm5Jib9m7iXVqsUPjVpW',1,0,'OmKoyoMkam','2025-04-19 22:52:01','2025-04-19 22:52:01',1,0,1,0),
(49,'Isabel Gerlach','gay69@example.org',NULL,'2025-04-19 22:52:01','$2y$04$JY7kL3.EH3SrNU4yAe4/AeZ8E7Qk2YglgGm5Jib9m7iXVqsUPjVpW',1,0,'ZPbd0zky5X','2025-04-19 22:52:01','2025-04-19 22:52:01',1,0,1,0),
(50,'Prof. Alessandro Hilpert','vprosacco@example.org',NULL,'2025-04-19 22:52:01','$2y$04$JY7kL3.EH3SrNU4yAe4/AeZ8E7Qk2YglgGm5Jib9m7iXVqsUPjVpW',1,0,'EBsCHVCfQa','2025-04-19 22:52:01','2025-04-19 22:52:01',1,0,1,0),
(51,'Lance Berge','lonzo.ondricka@example.org',NULL,'2025-04-19 22:52:01','$2y$04$JY7kL3.EH3SrNU4yAe4/AeZ8E7Qk2YglgGm5Jib9m7iXVqsUPjVpW',1,0,'UWRch6OB9B','2025-04-19 22:52:01','2025-04-19 22:52:01',1,0,1,0),
(52,'Janiya Shields V','yasmeen75@example.org',NULL,'2025-04-19 22:52:01','$2y$04$JY7kL3.EH3SrNU4yAe4/AeZ8E7Qk2YglgGm5Jib9m7iXVqsUPjVpW',1,0,'2jHfuRodSm','2025-04-19 22:52:01','2025-04-19 22:52:01',1,0,1,0),
(53,'Milford Mitchell','torey.mayer@example.com',NULL,'2025-04-19 22:52:01','$2y$04$JY7kL3.EH3SrNU4yAe4/AeZ8E7Qk2YglgGm5Jib9m7iXVqsUPjVpW',1,0,'IhvFTA4JqA','2025-04-19 22:52:01','2025-04-19 22:52:01',1,0,1,0),
(54,'Dr. Horace Shields V','mzieme@example.com',NULL,'2025-04-19 22:52:01','$2y$04$JY7kL3.EH3SrNU4yAe4/AeZ8E7Qk2YglgGm5Jib9m7iXVqsUPjVpW',1,0,'bk3Gw4saQ7','2025-04-19 22:52:01','2025-04-19 22:52:01',1,0,1,0);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-04-19 23:00:38
