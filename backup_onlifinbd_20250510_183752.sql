/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.6.21-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: onlifinbd
-- ------------------------------------------------------
-- Server version	10.6.21-MariaDB-0ubuntu0.22.04.2

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
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounts`
--

LOCK TABLES `accounts` WRITE;
/*!40000 ALTER TABLE `accounts` DISABLE KEYS */;
INSERT INTO `accounts` VALUES (11,'CORA','checking',1,NULL,'#6366f1',3,'2025-04-27 14:49:45','2025-05-09 14:29:07',0.00,-1719.34),(12,'conta principal','checking',1,NULL,'#6366f1',2,'2025-04-28 13:59:45','2025-05-06 14:21:33',0.00,0.00),(13,'Nubank alessandro','checking',1,NULL,'#6366f1',2,'2025-05-05 13:18:42','2025-05-06 14:21:33',257.22,124.22),(14,'Conta Principal','checking',1,NULL,NULL,4,'2025-05-05 13:45:03','2025-05-05 13:45:03',0.00,0.00);
/*!40000 ALTER TABLE `accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai_call_logs`
--

DROP TABLE IF EXISTS `ai_call_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_call_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `provider` varchar(255) DEFAULT NULL,
  `model` varchar(255) DEFAULT NULL,
  `duration_ms` int(11) DEFAULT NULL,
  `status_code` int(11) DEFAULT NULL,
  `prompt_preview` text DEFAULT NULL,
  `response_preview` text DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_call_logs_user_id_foreign` (`user_id`),
  KEY `ai_call_logs_provider_index` (`provider`),
  KEY `ai_call_logs_model_index` (`model`),
  KEY `ai_call_logs_status_code_index` (`status_code`),
  CONSTRAINT `ai_call_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai_call_logs`
--

LOCK TABLES `ai_call_logs` WRITE;
/*!40000 ALTER TABLE `ai_call_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai_call_logs` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Alimentação','expense',NULL,NULL,NULL,1,'2025-04-20 13:42:31','2025-04-20 13:42:31'),(2,'Transporte','expense',NULL,NULL,NULL,1,'2025-04-20 13:42:31','2025-04-20 13:42:31'),(5,'Freelance','income',NULL,NULL,NULL,1,'2025-04-20 13:42:31','2025-04-20 13:42:31'),(18,'Moradia','expense',NULL,NULL,NULL,2,'2025-04-20 13:43:34','2025-04-20 13:43:34'),(23,'Adc embraer','expense','clube de lazer','#3b82f6',NULL,2,'2025-04-28 13:21:27','2025-04-28 13:21:27'),(24,'sabesp','expense','agua','#3b82f6',NULL,2,'2025-04-29 18:24:03','2025-04-29 18:24:03'),(25,'edep','expense','Luz','#3b82f6',NULL,2,'2025-04-29 18:24:37','2025-04-29 18:24:37'),(26,'celular marcia','expense','conta','#3b82f6',NULL,2,'2025-04-29 18:25:33','2025-04-29 18:25:33'),(27,'celular Alessandro','expense','conta','#3b82f6',NULL,2,'2025-04-29 18:26:20','2025-04-29 18:26:20'),(28,'Celular Beatriz','expense','conta','#3b82f6',NULL,2,'2025-04-29 18:26:53','2025-04-29 18:26:53'),(29,'Celular Geovanna','expense','conta','#3b82f6',NULL,2,'2025-04-29 18:27:15','2025-04-29 18:27:15'),(30,'Gaz','expense','conta','#3b82f6',NULL,2,'2025-04-29 18:27:32','2025-04-29 18:27:32'),(31,'Internet casa','expense',NULL,'#3b82f6',NULL,2,'2025-05-05 13:15:21','2025-05-05 13:15:21'),(32,'Apve','expense',NULL,'#3b82f6',NULL,2,'2025-05-05 14:33:01','2025-05-05 14:33:01'),(33,'Onlitec','expense',NULL,'#3b82f6',NULL,2,'2025-05-07 13:58:42','2025-05-07 13:58:42'),(34,'combustivél','expense',NULL,'#3b82f6',NULL,2,'2025-05-07 19:20:29','2025-05-07 19:21:54'),(35,'conta avulsa','expense','Marcia','#3b82f6',NULL,2,'2025-05-07 19:22:59','2025-05-07 19:23:04'),(37,'Lazer','expense','Coronel','#3b82f6',NULL,2,'2025-05-08 13:47:46','2025-05-08 13:47:46'),(38,'Alimentação','expense',NULL,'#3b82f6',NULL,2,'2025-05-08 13:49:33','2025-05-08 13:49:33'),(39,'Quallit','income','serviço avulso','#3b82f6',NULL,2,'2025-05-08 18:38:13','2025-05-08 18:38:13'),(40,'Hi engenharia','income',NULL,'#3b82f6',NULL,2,'2025-05-08 18:42:22','2025-05-08 18:42:22'),(41,'Hi engenharia','income','Contrato mensal','#3b82f6',NULL,2,'2025-05-08 18:43:04','2025-05-08 18:43:04'),(42,'Farmacia','expense',NULL,'#3b82f6',NULL,2,'2025-05-09 14:10:04','2025-05-09 14:10:04'),(43,'Gerencial','income',NULL,'#3b82f6',NULL,2,'2025-05-09 14:11:06','2025-05-09 14:11:06'),(44,'Farmacia','expense',NULL,'#3b82f6',NULL,2,'2025-05-09 14:11:37','2025-05-09 14:11:37');
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
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2024_02_23_000000_create_sessions_table',1),(2,'0001_01_01_000001_create_cache_table',2),(3,'0001_01_01_000002_create_jobs_table',2),(4,'2014_10_12_000000_create_users_table',2),(5,'2024_02_23_000001_create_accounts_table',2),(6,'2024_02_23_000002_create_categories_table',2),(7,'2024_02_23_000003_create_transactions_table',2),(8,'2024_02_23_000004_create_roles_table',2),(9,'2024_02_23_000005_create_permissions_table',2),(10,'2024_02_23_000006_create_role_user_table',2),(11,'2024_02_23_000007_create_permission_role_table',2),(12,'2024_02_23_add_status_to_transactions',2),(13,'2025_03_11_230234_add_description_and_color_to_accounts_table',2),(14,'2025_03_11_233944_create_settings_table',2),(15,'2025_03_12_163420_add_description_to_categories_table',2),(16,'2025_03_12_164528_add_is_admin_to_users_table',2),(17,'2025_03_12_174726_create_replicate_settings_table',2),(18,'2025_03_30_203228_add_notification_settings_to_settings_table',2),(19,'2025_03_30_211641_add_is_active_to_users_table',2),(20,'2025_03_30_224441_sync_user_status_fields',2),(21,'2025_03_31_013443_create_password_reset_tokens_table',2),(22,'2025_03_31_015322_create_notifications_table',2),(23,'2025_03_31_020643_create_notification_templates_table',2),(24,'2025_03_31_092521_add_new_columns_to_notification_settings',2),(25,'2025_03_31_095642_add_notification_settings_to_users_table',2),(26,'[timestamp]_add_phone_to_users_table',2),(27,'2025_03_31_162902_add_whatsapp_notifications_to_users_table',3),(28,'2024_02_24_000001_update_accounts_table_structure',4),(29,'2024_04_02_000000_create_system_logs_table',4),(30,'2025_02_24_001122_add_user_id_to_categories_table',4),(31,'2025_02_24_001336_add_user_id_to_categories_table',4),(32,'2025_02_24_001358_add_description_to_categories_table',4),(33,'2025_02_24_110748_add_user_id_to_accounts_table',4),(34,'2025_02_24_114128_update_users_table_add_missing_fields',4),(35,'2025_03_13_011459_add_recurrence_fields_to_transactions_table',4),(36,'2025_04_01_090924_add_provider_to_replicate_settings_table',4),(37,'2025_04_18_023349_add_amount_to_transactions_if_not_exists',4),(38,'2025_04_18_185604_create_model_api_keys_table',4),(39,'xxxx_xx_xx_add_description_to_categories_table',4),(40,'xxxx_xx_xx_add_user_id_to_accounts_table',4),(41,'xxxx_xx_xx_add_user_id_to_categories_table',4),(42,'xxxx_xx_xx_create_role_user_table',4),(43,'xxxx_xx_xx_update_users_table_add_missing_fields',4),(44,'2023_06_01_000000_add_endpoint_to_replicate_settings',5),(45,'2024_03_21_000000_create_open_router_configs_table',5),(46,'2025_04_26_022256_create_ai_call_logs_table',5),(47,'2025_04_29_153132_add_client_and_supplier_to_transactions_table',5),(48,'2025_04_29_171528_add_api_key_to_users_table',5),(49,'2025_04_29_172731_alter_sessions_payload_column',5),(50,'2025_04_29_200000_add_endpoint_to_replicate_settings_table',5),(51,'2025_04_30_234225_update_openrouter_configs_make_endpoint_nullable',5),(52,'2024_03_19_000001_ensure_current_balance_in_accounts',6),(53,'2025_03_12_174727_add_endpoint_to_replicate_settings',7);
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_api_keys`
--

LOCK TABLES `model_api_keys` WRITE;
/*!40000 ALTER TABLE `model_api_keys` DISABLE KEYS */;
INSERT INTO `model_api_keys` VALUES (1,'gemini','gemini-2.0-flash','AIzaSyBCxoPMWv4oOH84kadpv_n6SRCU2PqWPoA','Prompt para Análise e Categorização de Extrato Bancário\r\n\r\nInstrua a IA a executar os seguintes procedimentos para processar um extrato bancário:\r\n\r\nAnálise do Extrato Bancário:\r\nLeia e interprete todas as transações contidas no extrato bancário fornecido, identificando data, valor, descrição/título e tipo (débito ou crédito).\r\nCategorização das Transações:\r\nClassifique as transações de acordo com as seguintes regras:\r\nDébitos:\r\nTransações identificadas como débitos (ex.: compras, saques) devem ser registradas na categoria Débitos.\r\nPagamentos via QR Code devem ser registrados na categoria Pagamentos.\r\nTransações de transferências enviadas devem ser registradas na categoria Transferências Enviadas.\r\nPagamentos (ex.: boletos, contas) devem ser registrados na categoria Pagamentos.\r\nReceitas:\r\nTransações de recebimentos (ex.: depósitos, Pix recebido) devem ser registradas na categoria Recebimentos.\r\nTransações de transferências recebidas devem ser registradas na categoria Transferências Recebidas.\r\nTransações não identificadas:\r\nCaso não seja possível determinar a categoria de uma transação de débito, registre-a na categoria Débito.\r\nCaso não seja possível determinar a categoria de uma transação de crédito, registre-a na categoria Recebimento.\r\nExtração de Informações do Título:\r\nAnalise o título ou descrição de cada transação para identificar informações sobre quem recebeu (no caso de débitos) ou quem pagou (no caso de créditos).\r\nInsira essas informações no campo Observações da transação.\r\nExemplo: Se o título for \"Pix enviado para João Silva\", registre \"João Silva\" como destinatário no campo Observações.\r\nCaso não haja informações claras sobre o pagador ou recebedor, deixe o campo Observações em branco ou registre \"Não identificado\".\r\nRegistro das Transações:\r\nCadastre cada transação em um sistema ou planilha com os seguintes campos:\r\nData\r\nValor\r\nCategoria (conforme definido acima)\r\nDescrição/Título Original\r\nObservações (com informações sobre pagador/recebedor, quando aplicável)\r\nGaranta que as transações sejam organizadas por data em ordem cronológica.\r\nValidação e Relatório:\r\nApós o processamento, valide se todas as transações foram categorizadas corretamente.\r\nGere um relatório resumido contendo:\r\nTotal de transações processadas.\r\nQuantidade de transações por categoria.\r\nLista de transações com categorização não identificada (se houver), para revisão manual.\r\nTratamento de Erros:\r\nCaso o extrato contenha dados inconsistentes (ex.: valores inválidos, descrições ilegíveis), registre essas transações em uma lista de \"erros\" com uma nota explicativa para revisão posterior.\r\nNão interrompa o processamento em caso de erros parciais; continue processando as demais transações.',1,'2025-04-20 14:09:12','2025-04-20 19:46:50');
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
-- Table structure for table `open_router_configs`
--

DROP TABLE IF EXISTS `open_router_configs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `open_router_configs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `provider` varchar(255) NOT NULL,
  `model` varchar(255) NOT NULL,
  `custom_model` varchar(255) DEFAULT NULL,
  `api_key` varchar(255) NOT NULL,
  `endpoint` varchar(255) DEFAULT NULL,
  `system_prompt` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `open_router_configs`
--

LOCK TABLES `open_router_configs` WRITE;
/*!40000 ALTER TABLE `open_router_configs` DISABLE KEYS */;
INSERT INTO `open_router_configs` VALUES (1,'openrouter','openai/gpt-4.1',NULL,'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',NULL,'INSTRUÇÕES PARA ANÁLISE DE EXTRATO BANCÁRIO:\r\n\r\nVocê é um assistente financeiro especializado em análise de transações bancárias. Examine cada linha do extrato e extraia os dados em formato JSON.\r\n\r\nCAMPOS A EXTRAIR:\r\n- name: Nome da pessoa, empresa ou serviço envolvido na transação\r\n- transaction_type: \"expense\" ou \"income\" (despesa ou receita)\r\n- date: Data da transação no formato DD/MM/AAAA\r\n- amount: Valor monetário da transação\r\n- category: Classificação da transação conforme lista abaixo\r\n- notes: Detalhes adicionais, códigos, identificadores e outras informações relevantes\r\n\r\nREGRAS DE CATEGORIZAÇÃO:\r\n\r\n1) IDENTIFICAR NOME: Extraia o nome principal da pessoa, empresa ou serviço envolvido na transação.\r\n   Exemplos: \"Supermercado Extra\", \"Salário Empresa ABC\", \"Maria Silva\"\r\n\r\n2) IDENTIFICAR TIPO DE TRANSAÇÃO:\r\n   - expense: pagamentos, compras, débitos, saídas\r\n   - income: salários, recebimentos, créditos, entradas\r\n\r\n3) CATEGORIAS PARA DESPESAS:\r\n   - Alimentação: Supermercados, restaurantes, delivery, padarias\r\n   - Transporte: Combustível, aplicativos de transporte, estacionamento, pedágios\r\n   - Moradia: Aluguel, condomínio, IPTU, manutenção, móveis\r\n   - Contas Fixas: Energia, água, gás, internet, telefonia, TV\r\n   - Saúde: Farmácias, médicos, planos de saúde, exames\r\n   - Educação: Mensalidades, cursos, livros, material escolar\r\n   - Compras: Lojas de departamento, vestuário, eletrônicos\r\n   - Lazer: Cinema, streaming, eventos, viagens\r\n   - Serviços: Assinaturas, academia, serviços domésticos\r\n   - Impostos e Taxas: Tributos, taxas bancárias, anuidades\r\n   - Saques: Retiradas em dinheiro\r\n   - Transferências Enviadas: PIX, TED, DOC enviados\r\n   - Outras Despesas: Gastos não classificados acima\r\n\r\n4) CATEGORIAS PARA RECEITAS:\r\n   - Salário: Pagamentos de salários, proventos, adiantamentos\r\n   - Recebimentos de Clientes: Vendas, pagamentos por serviços\r\n   - Transferências Recebidas: PIX, TED, DOC recebidos\r\n   - Reembolsos: Estornos, devoluções\r\n   - Rendimentos: Juros, dividendos, aluguel recebido\r\n   - Outras Receitas: Receitas não classificadas acima\r\n\r\n5) CAMPO NOTES: Inclua TODA informação adicional não utilizada nos outros campos, como:\r\n   - Códigos e identificadores (ID, número de operação)\r\n   - Detalhes da transação (itens, serviços)\r\n   - Números de documentos (NF, boletos)\r\n   - Dados de conta (agência, conta)\r\n   - Informações complementares\r\n\r\nFORMATO DE SAÍDA OBRIGATÓRIO:\r\nRetorne um array JSON com um objeto para cada transação:\r\n\r\n[\r\n  {\r\n    \"id\": 0,\r\n    \"transaction_type\": \"expense\", \r\n    \"date\": \"DD/MM/AAAA\",\r\n    \"amount\": 150.00,\r\n    \"name\": \"Nome da empresa/pessoa\",\r\n    \"category\": \"Nome da categoria\",\r\n    \"notes\": \"Informações adicionais\",\r\n    \"suggested_category\": \"Nome da categoria\" \r\n  },\r\n  ...\r\n]\r\n\r\nEXEMPLOS:\r\n\r\nInput: Pagto Eletron Boleto 123456 SUPERMERCADO ABC LTDA R$ 150,00 20/04/2024\r\nOutput:\r\n{\r\n  \"id\": 0,\r\n  \"transaction_type\": \"expense\",\r\n  \"date\": \"20/04/2024\",\r\n  \"amount\": 150.00,\r\n  \"name\": \"SUPERMERCADO ABC LTDA\",\r\n  \"category\": \"Alimentação\",\r\n  \"notes\": \"Pagto Eletron Boleto 123456\",\r\n  \"suggested_category\": \"Alimentação\"\r\n}\r\n\r\nInput: TEV PIX Recebido 30/04 Maria Silva CPF123* ID: E123456789 R$ 500,00\r\nOutput:\r\n{\r\n  \"id\": 1,\r\n  \"transaction_type\": \"income\",\r\n  \"date\": \"30/04/2024\",\r\n  \"amount\": 500.00,\r\n  \"name\": \"Maria Silva\",\r\n  \"category\": \"Transferências Recebidas\",\r\n  \"notes\": \"TEV CPF123* ID: E123456789\",\r\n  \"suggested_category\": \"Transferências Recebidas\"\r\n}\r\n\r\nIMPORTANTE: Retorne APENAS o array JSON sem textos explicativos. Não use formatação markdown.','2025-05-01 16:32:42','2025-05-01 16:32:42');
/*!40000 ALTER TABLE `open_router_configs` ENABLE KEYS */;
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
INSERT INTO `password_resets` VALUES ('galvatec@onlifin.com.br','$2y$12$SM.Dx0YiQkZHz8Sjm7vylOMNafcWnYDSCH72w9MOSAbZBrLoqmAV2','2025-05-10 14:56:40');
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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permission_role`
--

LOCK TABLES `permission_role` WRITE;
/*!40000 ALTER TABLE `permission_role` DISABLE KEYS */;
INSERT INTO `permission_role` VALUES (1,1,1,NULL,NULL),(2,2,1,NULL,NULL),(3,3,1,NULL,NULL),(4,4,1,NULL,NULL),(5,5,1,NULL,NULL),(6,6,1,NULL,NULL),(7,7,1,NULL,NULL),(8,8,1,NULL,NULL),(9,7,2,NULL,NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'view_users','Ver usuários','system','2025-04-20 13:43:34','2025-04-20 13:43:34'),(2,'create_users','Criar usuários','system','2025-04-20 13:43:34','2025-04-20 13:43:34'),(3,'edit_users','Editar usuários','system','2025-04-20 13:43:34','2025-04-20 13:43:34'),(4,'delete_users','Excluir usuários','system','2025-04-20 13:43:34','2025-04-20 13:43:34'),(5,'view_roles','Ver perfis','system','2025-04-20 13:43:34','2025-04-20 13:43:34'),(6,'manage_roles','Gerenciar perfis','system','2025-04-20 13:43:34','2025-04-20 13:43:34'),(7,'view_reports','Ver relatórios','system','2025-04-20 13:43:34','2025-04-20 13:43:34'),(8,'manage_backups','Gerenciar backups','system','2025-04-20 13:43:34','2025-04-20 13:43:34');
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
  `endpoint` varchar(255) DEFAULT NULL,
  `model_version` varchar(255) NOT NULL DEFAULT 'claude-3-sonnet-20240229',
  `system_prompt` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `replicate_settings`
--

LOCK TABLES `replicate_settings` WRITE;
/*!40000 ALTER TABLE `replicate_settings` DISABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_user`
--

LOCK TABLES `role_user` WRITE;
/*!40000 ALTER TABLE `role_user` DISABLE KEYS */;
INSERT INTO `role_user` VALUES (1,1,1,NULL,NULL),(2,1,2,NULL,NULL),(3,1,4,NULL,NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Administrador','Acesso total ao sistema','2025-04-20 13:43:34','2025-04-20 13:43:34'),(2,'Usuário','Acesso básico ao sistema','2025-04-20 13:43:34','2025-04-20 13:43:34');
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
  `payload` mediumtext NOT NULL,
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
INSERT INTO `sessions` VALUES ('BwTP8KXNuReQQG5kpZ2XnsfisSjIrV19DlfVBF7G',3,'172.20.120.166','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36','YTo1OntzOjY6Il90b2tlbiI7czo0MDoidFlZZkhFWGZCZGF2bjNLMm9lY3RweG95RzZmQ3VaU1JXdm1mWE05dyI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjE6e3M6MzoidXJsIjtzOjQ2OiJodHRwOi8vb25saWZpbi5vbmxpdGVjLmNvbS5ici8/cGVyaW9kPWFsbF90aW1lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6Mzt9',1746891170),('tsZhIyvIVG5hfywnCpMK7dvzBrLpySjXVPLyPHxI',NULL,'172.20.120.166','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiYzFRdGZ3MFRGODRMWWF1U1NsTXhvT3B0N0NMUk5PbENoY0RYVjk1ayI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czoyOToiaHR0cDovL29ubGlmaW4ub25saXRlYy5jb20uYnIiO31zOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czozNToiaHR0cDovL29ubGlmaW4ub25saXRlYy5jb20uYnIvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1746889224);
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_logs`
--

LOCK TABLES `system_logs` WRITE;
/*!40000 ALTER TABLE `system_logs` DISABLE KEYS */;
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
  `fornecedor` varchar(255) DEFAULT NULL,
  `cliente` varchar(255) DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=201 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transactions`
--

LOCK TABLES `transactions` WRITE;
/*!40000 ALTER TABLE `transactions` DISABLE KEYS */;
INSERT INTO `transactions` VALUES (186,'income','pending','none',NULL,NULL,NULL,'2025-05-05','TESTE0002',NULL,NULL,50000,5,12,NULL,3,'2025-05-05 14:15:23','2025-05-05 14:15:23'),(187,'expense','paid','fixed',NULL,NULL,'2025-06-10','2025-05-05','internet',NULL,NULL,13300,31,13,NULL,2,'2025-05-05 14:24:54','2025-05-05 14:24:54'),(190,'expense','pending','fixed',NULL,NULL,'2025-06-05','2025-05-12','celular','claro',NULL,4488,28,11,NULL,2,'2025-05-05 20:11:36','2025-05-07 19:47:34'),(191,'expense','pending','fixed',NULL,NULL,'2025-06-05','2025-05-15','apve','apve',NULL,14000,32,11,NULL,2,'2025-05-05 20:12:21','2025-05-05 20:12:21'),(192,'expense','paid','none',NULL,NULL,NULL,'2025-05-06','faxada',NULL,NULL,20000,33,11,NULL,2,'2025-05-07 13:59:18','2025-05-07 13:59:18'),(193,'expense','paid','none',NULL,NULL,NULL,'2025-05-07','combustivél',NULL,NULL,10000,34,11,NULL,2,'2025-05-07 19:22:33','2025-05-07 19:22:33'),(194,'expense','pending','none',NULL,NULL,NULL,'2025-05-09','celular','claro',NULL,4872,29,11,NULL,2,'2025-05-07 19:43:34','2025-05-07 19:43:34'),(195,'expense','paid','none',NULL,NULL,NULL,'2025-05-07','Bar do coronel',NULL,NULL,38000,37,11,NULL,2,'2025-05-08 13:48:37','2025-05-08 13:48:37'),(196,'expense','paid','none',NULL,NULL,NULL,'2025-05-07','Bolo miguel',NULL,NULL,7800,38,11,NULL,2,'2025-05-08 13:50:11','2025-05-08 13:50:11'),(197,'income','pending','none',NULL,NULL,NULL,'2025-05-20','Quallit',NULL,'qualit',250000,39,11,NULL,2,'2025-05-08 18:38:43','2025-05-08 18:39:17'),(198,'income','pending','none',NULL,NULL,NULL,'2025-05-08','Hi engenharia',NULL,'Hi engenharia',39859,41,11,NULL,2,'2025-05-08 22:35:12','2025-05-08 22:35:12'),(199,'expense','paid','none',NULL,NULL,NULL,'2025-05-09','remédio','droga raia',NULL,3575,42,11,NULL,2,'2025-05-09 14:12:37','2025-05-09 14:12:37'),(200,'expense','paid','none',NULL,NULL,NULL,'2025-05-09','agua','sabesp',NULL,8807,24,11,NULL,2,'2025-05-09 14:29:07','2025-05-09 14:29:07');
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Administrador','alfreire@onlitec.com.br',NULL,'2025-04-20 13:43:33','$2y$12$dWXmsVSNSDFtmMeIrHdD0eChR454d9i6TWnGW69b/.FTyRX2e6mJO',1,1,NULL,'2025-04-20 13:42:30','2025-04-20 13:43:33',1,0,1,0),(2,'Administrador Galvatec','galvatec@onlifin.com.br',NULL,'2025-04-20 13:43:33','$2y$12$imQVLcvWRHcT8L25YEtQOOle/NHYwJAQysABbAjKPrD2rAjJASzYS',1,1,NULL,'2025-04-20 13:42:31','2025-04-20 13:43:33',1,0,1,0),(3,'Marcia','marciafreire@onlitec.com.br',NULL,NULL,'$2y$12$zNEQwb3lTlk4.JGwh6usxOK8ndtf6A.i.JHBhKwiZmxSB0BcKRLN.',1,1,NULL,'2025-04-20 13:44:13','2025-04-20 13:45:08',1,0,1,0),(4,'Alessandro','alessandro@onlitec.com.br',NULL,'2025-05-05 13:45:03','$2y$12$x3I0ozsB6DVaDGczzlpkHOXhUOaEppkG6/uka7nMP2imUiXd.GajO',1,1,NULL,'2025-05-05 13:45:03','2025-05-05 13:45:03',1,0,1,0);
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

-- Dump completed on 2025-05-10 18:37:56
