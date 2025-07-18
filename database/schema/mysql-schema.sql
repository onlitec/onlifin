/*M!999999\- enable the sandbox mode */ 
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'checking',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `user_id` bigint(20) unsigned NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(7) DEFAULT NULL,
  `group_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `initial_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `current_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `accounts_group_id_foreign` (`group_id`),
  CONSTRAINT `accounts_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `companies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `owner_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `personal_company` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `companies_owner_id_foreign` (`owner_id`),
  CONSTRAINT `companies_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `company_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `company_profiles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `phone_number` varchar(30) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `tax_id` varchar(50) DEFAULT NULL,
  `entity_type` varchar(255) DEFAULT NULL,
  `chatbot_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `company_profiles_company_id_foreign` (`company_id`),
  KEY `company_profiles_created_by_foreign` (`created_by`),
  KEY `company_profiles_updated_by_foreign` (`updated_by`),
  CONSTRAINT `company_profiles_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_profiles_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `company_profiles_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `company_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `company_user` (
  `company_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`company_id`,`user_id`),
  KEY `company_user_user_id_foreign` (`user_id`),
  CONSTRAINT `company_user_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
DROP TABLE IF EXISTS `group_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `group_role` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_role_group_id_role_id_unique` (`group_id`,`role_id`),
  KEY `group_role_role_id_foreign` (`role_id`),
  CONSTRAINT `group_role_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `group_role_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `group_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `group_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_user_group_id_user_id_unique` (`group_id`,`user_id`),
  KEY `group_user_user_id_foreign` (`user_id`),
  CONSTRAINT `group_user_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `group_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `groups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_api_keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_api_keys` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `provider` varchar(50) NOT NULL COMMENT 'Provedor de IA (openai, anthropic, gemini, etc)',
  `model` varchar(100) NOT NULL COMMENT 'Nome do modelo específico',
  `api_token` text NOT NULL COMMENT 'Chave API específica para este modelo',
  `system_prompt` text DEFAULT NULL COMMENT 'Prompt do sistema específico para este modelo',
  `chat_prompt` text DEFAULT NULL,
  `import_prompt` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Se esta configuração está ativa',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `model_api_keys_provider_model_unique` (`provider`,`model`),
  KEY `model_api_keys_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  `chat_prompt` text DEFAULT NULL,
  `import_prompt` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
DROP TABLE IF EXISTS `ssl_error_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ssl_error_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `action` varchar(255) NOT NULL,
  `domain` varchar(255) NOT NULL,
  `error_type` varchar(255) DEFAULT NULL,
  `error_message` text NOT NULL,
  `error_detail` text DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `friendly_message` text NOT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ssl_error_logs_domain_created_at_index` (`domain`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  `company_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transactions_category_id_foreign` (`category_id`),
  KEY `transactions_account_id_foreign` (`account_id`),
  KEY `transactions_user_id_foreign` (`user_id`),
  CONSTRAINT `transactions_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`),
  CONSTRAINT `transactions_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  `profile_photo` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `current_company_id` bigint(20) unsigned DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `email_notifications` tinyint(1) NOT NULL DEFAULT 1,
  `push_notifications` tinyint(1) NOT NULL DEFAULT 0,
  `due_date_notifications` tinyint(1) NOT NULL DEFAULT 1,
  `whatsapp_notifications` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_current_company_id_foreign` (`current_company_id`),
  CONSTRAINT `users_current_company_id_foreign` FOREIGN KEY (`current_company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

/*M!999999\- enable the sandbox mode */ 
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'2024_02_23_000000_create_sessions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2014_10_12_000000_create_users_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2024_02_23_000001_create_accounts_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2024_02_23_000002_create_categories_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2024_02_23_000003_create_transactions_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2024_02_23_000004_create_roles_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2024_02_23_000005_create_permissions_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2024_02_23_000006_create_role_user_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2024_02_23_000007_create_permission_role_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2024_02_23_add_status_to_transactions',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2025_03_11_230234_add_description_and_color_to_accounts_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2025_03_11_233944_create_settings_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2025_03_12_163420_add_description_to_categories_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2025_03_12_164528_add_is_admin_to_users_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2025_03_12_174726_create_replicate_settings_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2025_03_30_203228_add_notification_settings_to_settings_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2025_03_30_211641_add_is_active_to_users_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2025_03_30_224441_sync_user_status_fields',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2025_03_31_013443_create_password_reset_tokens_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2025_03_31_015322_create_notifications_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2025_03_31_020643_create_notification_templates_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2025_03_31_092521_add_new_columns_to_notification_settings',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2025_03_31_095642_add_notification_settings_to_users_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'[timestamp]_add_phone_to_users_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2025_03_31_162902_add_whatsapp_notifications_to_users_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2024_02_24_000001_update_accounts_table_structure',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2024_04_02_000000_create_system_logs_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2025_02_24_001122_add_user_id_to_categories_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2025_02_24_001336_add_user_id_to_categories_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2025_02_24_001358_add_description_to_categories_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2025_02_24_110748_add_user_id_to_accounts_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2025_02_24_114128_update_users_table_add_missing_fields',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2025_03_13_011459_add_recurrence_fields_to_transactions_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2025_04_01_090924_add_provider_to_replicate_settings_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2025_04_18_023349_add_amount_to_transactions_if_not_exists',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2025_04_18_185604_create_model_api_keys_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'xxxx_xx_xx_add_description_to_categories_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'xxxx_xx_xx_add_user_id_to_accounts_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'xxxx_xx_xx_add_user_id_to_categories_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'xxxx_xx_xx_create_role_user_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'xxxx_xx_xx_update_users_table_add_missing_fields',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2023_06_01_000000_add_endpoint_to_replicate_settings',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2024_03_21_000000_create_open_router_configs_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2025_04_26_022256_create_ai_call_logs_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2025_04_29_153132_add_client_and_supplier_to_transactions_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2025_04_29_171528_add_api_key_to_users_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2025_04_29_172731_alter_sessions_payload_column',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2025_04_29_200000_add_endpoint_to_replicate_settings_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2025_04_30_234225_update_openrouter_configs_make_endpoint_nullable',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2024_03_19_000001_ensure_current_balance_in_accounts',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2025_03_12_174727_add_endpoint_to_replicate_settings',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2025_05_13_002056_add_current_balance_to_accounts_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2024_06_07_000001_create_groups_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2025_05_14_000000_add_profile_photo_to_users_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2025_05_19_234201_create_companies_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2025_05_20_002631_create_company_profiles_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2024_01_01_000000_create_permission_tables',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2025_05_20_004351_add_company_id_to_transactions_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2025_05_20_014440_add_chatbot_enabled_to_company_profiles_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2025_05_22_080616_add_prompt_types_to_open_router_configs_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2025_05_30_000000_add_prompt_types_to_model_api_keys_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2025_06_14_000001_create_company_user_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2025_06_14_000002_add_description_category_to_permissions_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2025_06_14_000003_add_current_company_id_to_users_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2025_06_15_000000_add_description_to_roles_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2025_06_15_000001_create_role_user_and_permission_role_tables',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2025_06_24_220500_add_chat_prompt_and_import_prompt_to_open_router_configs',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2025_07_03_233719_add_group_id_to_accounts_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2025_07_05_160427_ensure_user_id_exists_in_accounts_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2025_01_05_200000_create_ssl_error_logs_table',13);
