-- ============================================================================
-- VISOR TV PRO - BASE DE DATOS COMPLETA PARA MYSQL WORKBENCH Y HOSTINGER
-- Generado en vivo con datos exactos del frontend (Ashly Nicoleeee, Dilan, Sede Centrooooo)
-- Fecha: 2026-09-30 22:10:36
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `visortv` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `visortv`;

SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------------
-- Estructura: `migrations`
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos: `migrations` (8 registros)
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1, '0001_01_01_000000_create_users_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2, '0001_01_01_000001_create_cache_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3, '0001_01_01_000002_create_jobs_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4, '2026_09_04_214447_create_personal_access_tokens_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5, '2026_09_04_214515_create_visor_tv_tables', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6, '2026_09_29_230000_add_theme_to_sedes_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7, '2026_09_29_233000_add_theme_to_users_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8, '2026_09_30_161837_alter_icon_column_in_sedes_table', 1);

-- -------------------------------------------------------------
-- Estructura: `users`
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admin',
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `theme` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'dark-blue',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos: `users` (4 registros)
INSERT INTO `users` (`id`, `name`, `username`, `email`, `email_verified_at`, `password`, `role`, `avatar`, `theme`, `is_active`, `last_login_at`, `last_login_ip`, `remember_token`, `created_at`, `updated_at`) VALUES (1, 'Ashly Nicoleeee', 'ashly', 'ashly@visortv.com', NULL, '$2y$12$2C3xrZXXkZH.1SqCstLTVenI2uarDmDtpU.EdJM6mWhenSFKn1K0m', 'superadmin', '/api/media/stream/avatar_1_1790807373.jpg', 'dark-blue', 1, '2026-10-01 03:33:18', '127.0.0.1', NULL, '2026-09-30 22:03:10', '2026-10-01 03:33:18');
INSERT INTO `users` (`id`, `name`, `username`, `email`, `email_verified_at`, `password`, `role`, `avatar`, `theme`, `is_active`, `last_login_at`, `last_login_ip`, `remember_token`, `created_at`, `updated_at`) VALUES (2, 'Administrador General', 'admin', 'admin@visortv.com', NULL, '$2y$12$I8SktV1os9naGK0x13QTDug8FYPGSrBIME9ImWvdlFovZQOQNSzNG', 'admin', NULL, 'dark-blue', 1, NULL, NULL, NULL, '2026-09-30 22:03:10', '2026-09-30 22:03:10');
INSERT INTO `users` (`id`, `name`, `username`, `email`, `email_verified_at`, `password`, `role`, `avatar`, `theme`, `is_active`, `last_login_at`, `last_login_ip`, `remember_token`, `created_at`, `updated_at`) VALUES (3, 'Operador de Turno', 'operador', 'operador@visortv.com', NULL, '$2y$12$TZbQn6ipNk.4Y4crYxj9tOndQJyC4/bbmouxqYKQOoW42OthqYz72', 'operator', NULL, 'dark-blue', 1, NULL, NULL, NULL, '2026-09-30 22:03:10', '2026-09-30 22:03:10');
INSERT INTO `users` (`id`, `name`, `username`, `email`, `email_verified_at`, `password`, `role`, `avatar`, `theme`, `is_active`, `last_login_at`, `last_login_ip`, `remember_token`, `created_at`, `updated_at`) VALUES (4, 'Dilan', 'dilan', 'dilan@tv', NULL, '$2y$12$KJJYc8jddXRH15vqJ7bZ.uLYY/ihqXcp4/Zs79e9JcN425KPIht8S', 'admin', 'avatar-code', 'dark-blue', 1, NULL, NULL, NULL, '2026-09-30 22:05:36', '2026-09-30 22:05:36');

-- -------------------------------------------------------------
-- Estructura: `sedes`
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `sedes`;
CREATE TABLE `sedes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#2563eb',
  `theme` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon` text COLLATE utf8mb4_unicode_ci,
  `order_num` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by_user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sedes_slug_unique` (`slug`),
  KEY `sedes_created_by_user_id_foreign` (`created_by_user_id`),
  KEY `sedes_order_num_is_active_index` (`order_num`,`is_active`),
  CONSTRAINT `sedes_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos: `sedes` (5 registros)
INSERT INTO `sedes` (`id`, `name`, `slug`, `description`, `address`, `color`, `theme`, `icon`, `order_num`, `is_active`, `created_by_user_id`, `created_at`, `updated_at`) VALUES (1, 'Sede Principal (Centrooooo)', 'sede-principal', 'Recepción y salas de espera centrales con Smart TV de alta resolución', 'Av. Principal # 100 - Torre A', '#2563eb', NULL, 'Building2', 1, 1, 1, '2026-09-30 22:03:10', '2026-09-30 22:07:13');
INSERT INTO `sedes` (`id`, `name`, `slug`, `description`, `address`, `color`, `theme`, `icon`, `order_num`, `is_active`, `created_by_user_id`, `created_at`, `updated_at`) VALUES (2, 'Sede Norte', 'sede-norte', 'Área de atención al público y pasillos de consulta', 'Calle 140 # 15 - 30', '#059669', NULL, 'Compass', 2, 1, 1, '2026-09-30 22:03:10', '2026-09-30 22:03:10');
INSERT INTO `sedes` (`id`, `name`, `slug`, `description`, `address`, `color`, `theme`, `icon`, `order_num`, `is_active`, `created_by_user_id`, `created_at`, `updated_at`) VALUES (3, 'Sede Sur', 'sede-sur', 'Pantallas de información general y cartelera médica', 'Carrera 10 # 35 Sur', '#d97706', NULL, 'Landmark', 3, 1, 1, '2026-09-30 22:03:10', '2026-09-30 22:03:10');
INSERT INTO `sedes` (`id`, `name`, `slug`, `description`, `address`, `color`, `theme`, `icon`, `order_num`, `is_active`, `created_by_user_id`, `created_at`, `updated_at`) VALUES (4, 'Sede Occidente', 'sede-occidente', 'Módulo de atención al usuario y auditorio empresarial', 'Avenida El Dorado # 68 - 90', '#7c3aed', NULL, 'Store', 4, 1, 1, '2026-09-30 22:03:10', '2026-09-30 22:03:10');
INSERT INTO `sedes` (`id`, `name`, `slug`, `description`, `address`, `color`, `theme`, `icon`, `order_num`, `is_active`, `created_by_user_id`, `created_at`, `updated_at`) VALUES (5, 'Sede VIP / Corporativa', 'sede-vip', 'Lounge ejecutivo y salas directivas en piso 12', 'Carrera 7 # 116 - 50 Piso 12', '#0891b2', NULL, 'Crown', 5, 1, 1, '2026-09-30 22:03:10', '2026-09-30 22:03:10');

-- -------------------------------------------------------------
-- Estructura: `media_items`
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `media_items`;
CREATE TABLE `media_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sede_id` bigint unsigned NOT NULL,
  `uploaded_by_user_id` bigint unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'image',
  `filename` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` bigint unsigned NOT NULL DEFAULT '0',
  `duration` int NOT NULL DEFAULT '10',
  `fit_mode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'contain',
  `resolution` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_num` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `media_items_sede_id_is_active_order_num_index` (`sede_id`,`is_active`,`order_num`),
  KEY `media_items_uploaded_by_user_id_index` (`uploaded_by_user_id`),
  CONSTRAINT `media_items_sede_id_foreign` FOREIGN KEY (`sede_id`) REFERENCES `sedes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `media_items_uploaded_by_user_id_foreign` FOREIGN KEY (`uploaded_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos: `media_items` (3 registros)
INSERT INTO `media_items` (`id`, `sede_id`, `uploaded_by_user_id`, `title`, `type`, `filename`, `original_name`, `mime_type`, `file_size`, `duration`, `fit_mode`, `resolution`, `order_num`, `is_active`, `created_at`, `updated_at`) VALUES (1, 1, 1, 'Bienvenida Corporativa — Sede Principal', 'image', 'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1920&q=80', 'bienvenida_corporativa.jpg', 'image/jpeg', 1048576, 10, 'contain', '1920x1080', 1, 1, '2026-09-30 22:03:10', '2026-09-30 22:03:10');
INSERT INTO `media_items` (`id`, `sede_id`, `uploaded_by_user_id`, `title`, `type`, `filename`, `original_name`, `mime_type`, `file_size`, `duration`, `fit_mode`, `resolution`, `order_num`, `is_active`, `created_at`, `updated_at`) VALUES (2, 1, 1, 'Información de Servicios y Especialidades', 'image', 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1920&q=80', 'catalogo_servicios.jpg', 'image/jpeg', 1048576, 8, 'contain', '1920x1080', 2, 1, '2026-09-30 22:03:10', '2026-09-30 22:03:10');
INSERT INTO `media_items` (`id`, `sede_id`, `uploaded_by_user_id`, `title`, `type`, `filename`, `original_name`, `mime_type`, `file_size`, `duration`, `fit_mode`, `resolution`, `order_num`, `is_active`, `created_at`, `updated_at`) VALUES (3, 2, 1, 'Horarios de Atención — Sede Norte', 'image', 'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?auto=format&fit=crop&w=1920&q=80', 'horarios_sede_norte.jpg', 'image/jpeg', 1048576, 12, 'contain', '1920x1080', 1, 1, '2026-09-30 22:03:10', '2026-09-30 22:03:10');

-- -------------------------------------------------------------
-- Estructura: `settings`
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'string',
  `group` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `updated_by_user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`key`),
  KEY `settings_updated_by_user_id_foreign` (`updated_by_user_id`),
  CONSTRAINT `settings_updated_by_user_id_foreign` FOREIGN KEY (`updated_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos: `settings` (11 registros)
INSERT INTO `settings` (`key`, `value`, `type`, `group`, `updated_by_user_id`, `created_at`, `updated_at`) VALUES ('app_author', 'Ashly Nicole', 'string', 'general', NULL, '2026-09-30 22:03:10', '2026-09-30 22:03:10');
INSERT INTO `settings` (`key`, `value`, `type`, `group`, `updated_by_user_id`, `created_at`, `updated_at`) VALUES ('app_name', 'Visor TV Sistemas', 'string', 'general', NULL, '2026-09-30 22:03:10', '2026-09-30 22:03:10');
INSERT INTO `settings` (`key`, `value`, `type`, `group`, `updated_by_user_id`, `created_at`, `updated_at`) VALUES ('default_image_duration', '10', 'string', 'general', NULL, '2026-09-30 22:03:10', '2026-09-30 22:03:10');
INSERT INTO `settings` (`key`, `value`, `type`, `group`, `updated_by_user_id`, `created_at`, `updated_at`) VALUES ('tv_auto_refresh_seconds', '30', 'string', 'general', NULL, '2026-09-30 22:03:10', '2026-09-30 22:03:10');
INSERT INTO `settings` (`key`, `value`, `type`, `group`, `updated_by_user_id`, `created_at`, `updated_at`) VALUES ('tv_show_clock', '1', 'string', 'general', NULL, '2026-09-30 22:03:10', '2026-09-30 22:03:10');
INSERT INTO `settings` (`key`, `value`, `type`, `group`, `updated_by_user_id`, `created_at`, `updated_at`) VALUES ('tv_show_date', '1', 'string', 'general', NULL, '2026-09-30 22:03:10', '2026-09-30 22:03:10');
INSERT INTO `settings` (`key`, `value`, `type`, `group`, `updated_by_user_id`, `created_at`, `updated_at`) VALUES ('tv_show_progress_bar', '1', 'string', 'general', NULL, '2026-09-30 22:03:10', '2026-09-30 22:03:10');
INSERT INTO `settings` (`key`, `value`, `type`, `group`, `updated_by_user_id`, `created_at`, `updated_at`) VALUES ('tv_show_sede_title', '1', 'string', 'general', NULL, '2026-09-30 22:03:10', '2026-09-30 22:03:10');
INSERT INTO `settings` (`key`, `value`, `type`, `group`, `updated_by_user_id`, `created_at`, `updated_at`) VALUES ('tv_ticker_enabled', '0', 'string', 'general', NULL, '2026-09-30 22:03:10', '2026-09-30 22:03:10');
INSERT INTO `settings` (`key`, `value`, `type`, `group`, `updated_by_user_id`, `created_at`, `updated_at`) VALUES ('tv_ticker_message', 'Bienvenidos a Visor TV • Señalización Digital Inteligente', 'string', 'general', NULL, '2026-09-30 22:03:10', '2026-09-30 22:03:10');
INSERT INTO `settings` (`key`, `value`, `type`, `group`, `updated_by_user_id`, `created_at`, `updated_at`) VALUES ('tv_transition_effect', 'fade', 'string', 'general', NULL, '2026-09-30 22:03:10', '2026-09-30 22:03:10');

-- -------------------------------------------------------------
-- Estructura: `audit_logs`
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `user_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sede_id` bigint unsigned DEFAULT NULL,
  `sede_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entity_id` bigint unsigned DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `audit_logs_action_created_at_index` (`action`,`created_at`),
  KEY `audit_logs_sede_id_created_at_index` (`sede_id`,`created_at`),
  KEY `audit_logs_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `audit_logs_created_at_index` (`created_at`),
  CONSTRAINT `audit_logs_sede_id_foreign` FOREIGN KEY (`sede_id`) REFERENCES `sedes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos: `audit_logs` (9 registros)
INSERT INTO `audit_logs` (`id`, `user_id`, `user_name`, `sede_id`, `sede_name`, `action`, `entity_type`, `entity_id`, `description`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES (1, 1, 'Ashly Nicole', 1, 'Sede Principal (Centro)', 'system.init', 'System', 1, 'Ashly Nicole inicializó la plataforma Visor TV con arquitectura sólida y auditoría activa', '{\"author\": \"Ashly Nicole\", \"version\": \"2.0.0\"}', '127.0.0.1', 'VisorTV Setup Script', '2026-09-30 21:48:10');
INSERT INTO `audit_logs` (`id`, `user_id`, `user_name`, `sede_id`, `sede_name`, `action`, `entity_type`, `entity_id`, `description`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES (2, 1, 'Ashly Nicole', 1, 'Sede Principal (Centro)', 'media.upload', 'MediaItem', 1, 'Ashly Nicole configuró el contenido \"Bienvenida Corporativa\" en Sede Principal', '{\"type\": \"image\", \"duration\": 10}', '127.0.0.1', 'VisorTV Web Client', '2026-09-30 21:53:10');
INSERT INTO `audit_logs` (`id`, `user_id`, `user_name`, `sede_id`, `sede_name`, `action`, `entity_type`, `entity_id`, `description`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES (3, 1, 'Ashly Nicole', 1, 'Sede Principal (Centro)', 'media.upload', 'MediaItem', 2, 'Ashly Nicole configuró el contenido \"Información de Servicios\" en Sede Principal', '{\"type\": \"image\", \"duration\": 8}', '127.0.0.1', 'VisorTV Web Client', '2026-09-30 21:58:10');
INSERT INTO `audit_logs` (`id`, `user_id`, `user_name`, `sede_id`, `sede_name`, `action`, `entity_type`, `entity_id`, `description`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES (4, 1, 'Ashly Nicole', NULL, NULL, 'auth.login', 'User', 1, 'Ashly Nicole (@ashly) inició sesión exitosamente', '{\"role\": \"superadmin\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26200; es-CO) PowerShell/7.6.6', '2026-09-30 22:03:45');
INSERT INTO `audit_logs` (`id`, `user_id`, `user_name`, `sede_id`, `sede_name`, `action`, `entity_type`, `entity_id`, `description`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES (5, 1, 'Ashly Nicole', NULL, NULL, 'auth.login', 'User', 1, 'Ashly Nicole (@ashly) inició sesión exitosamente', '{\"role\": \"superadmin\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 22:04:58');
INSERT INTO `audit_logs` (`id`, `user_id`, `user_name`, `sede_id`, `sede_name`, `action`, `entity_type`, `entity_id`, `description`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES (6, 1, 'Ashly Nicole', NULL, NULL, 'user.create', 'User', 4, 'Se creó el usuario \'Dilan\' (@dilan) con rol \'admin\'', '{\"role\": \"admin\", \"username\": \"dilan\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 22:05:36');
INSERT INTO `audit_logs` (`id`, `user_id`, `user_name`, `sede_id`, `sede_name`, `action`, `entity_type`, `entity_id`, `description`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES (7, 1, 'Ashly Nicole', NULL, NULL, 'user.update', 'User', 1, 'Se actualizaron los datos del usuario \'Ashly Nicoleeee\' (@ashly)', '[\"name\", \"username\", \"email\", \"role\", \"is_active\", \"avatar\"]', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 22:06:41');
INSERT INTO `audit_logs` (`id`, `user_id`, `user_name`, `sede_id`, `sede_name`, `action`, `entity_type`, `entity_id`, `description`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES (8, 1, 'Ashly Nicoleeee', 1, 'Sede Principal (Centrooooo)', 'sede.update', 'Sede', 1, 'Se actualizaron los datos de la sede \'Sede Principal (Centrooooo)\'', '[\"name\", \"description\", \"address\", \"color\", \"icon\", \"is_active\"]', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 22:07:13');
INSERT INTO `audit_logs` (`id`, `user_id`, `user_name`, `sede_id`, `sede_name`, `action`, `entity_type`, `entity_id`, `description`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES (9, 1, 'Ashly Nicoleeee', NULL, NULL, 'auth.login', 'User', 1, 'Ashly Nicoleeee (@ashly) inició sesión exitosamente', '{\"role\": \"superadmin\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26200; es-CO) PowerShell/7.6.6', '2026-09-30 22:10:29');

-- -------------------------------------------------------------
-- Estructura: `personal_access_tokens`
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos: `personal_access_tokens` (1 registros)
INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES (3, 'App\\Models\\User', 1, 'visor_tv_auth', '2e5e20062420f99ecde9e5e35e0b3749065bce7b794f4f691a75e9e82e36fbb4', '[\"*\"]', NULL, NULL, '2026-09-30 22:10:29', '2026-09-30 22:10:29');

-- -------------------------------------------------------------
-- Estructura: `sessions`
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Estructura: `cache`
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Estructura: `cache_locks`
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Estructura: `jobs`
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Estructura: `job_batches`
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Estructura: `failed_jobs`
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
-- ============================================================================
-- FIN DEL SCRIPT VISOR TV PRO
-- ============================================================================
