/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.14-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: crafthost
-- ------------------------------------------------------
-- Server version	10.11.14-MariaDB-0ubuntu0.24.04.1

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
-- Table structure for table `audit_log`
--

DROP TABLE IF EXISTS `audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` bigint(20) unsigned NOT NULL,
  `action` varchar(64) NOT NULL,
  `target_type` varchar(32) DEFAULT NULL,
  `target_id` bigint(20) unsigned DEFAULT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_log_target_type_target_id_index` (`target_type`,`target_id`),
  KEY `audit_log_admin_id_created_at_index` (`admin_id`,`created_at`),
  CONSTRAINT `audit_log_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_log`
--

LOCK TABLES `audit_log` WRITE;
/*!40000 ALTER TABLE `audit_log` DISABLE KEYS */;
INSERT INTO `audit_log` VALUES
(1,4,'server.suspended','server',4,NULL,'2026-05-21 09:14:05'),
(2,4,'server.unsuspended','server',4,NULL,'2026-05-21 09:14:10'),
(3,4,'server.deleted','server',6,'{\"user_id\":5,\"tariff\":\"Diamond\"}','2026-05-21 10:27:36'),
(4,4,'server.deleted','server',5,'{\"user_id\":3,\"tariff\":\"Diamond\"}','2026-05-21 10:27:39'),
(5,4,'server.deleted','server',4,'{\"user_id\":3,\"tariff\":\"Netherite\"}','2026-05-21 10:27:41'),
(6,4,'server.deleted','server',3,'{\"user_id\":2,\"tariff\":\"Diamond\"}','2026-05-21 10:27:42'),
(7,4,'server.deleted','server',2,'{\"user_id\":1,\"tariff\":\"Diamond\"}','2026-05-21 10:27:45'),
(8,4,'server.deleted','server',1,'{\"user_id\":2,\"tariff\":\"Stone\"}','2026-05-21 10:27:48'),
(9,4,'ticket.assigned','ticket',1,'{\"admin_id\":4}','2026-05-21 18:14:14'),
(10,4,'ticket.replied','ticket',1,NULL,'2026-05-21 18:14:16');
/*!40000 ALTER TABLE `audit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `backups`
--

DROP TABLE IF EXISTS `backups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `backups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `server_id` bigint(20) unsigned NOT NULL,
  `ptero_backup_id` varchar(255) DEFAULT NULL,
  `size_bytes` bigint(20) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `backups_server_id_foreign` (`server_id`),
  CONSTRAINT `backups_server_id_foreign` FOREIGN KEY (`server_id`) REFERENCES `servers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `backups`
--

LOCK TABLES `backups` WRITE;
/*!40000 ALTER TABLE `backups` DISABLE KEYS */;
INSERT INTO `backups` VALUES
(1,1,'636208bf-3efa-4e33-8c3c-85ee45e69bae',0,'2026-05-21 01:26:45');
/*!40000 ALTER TABLE `backups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_verification_tokens`
--

DROP TABLE IF EXISTS `email_verification_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_verification_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_verification_tokens`
--

LOCK TABLES `email_verification_tokens` WRITE;
/*!40000 ALTER TABLE `email_verification_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_verification_tokens` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `faqs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category` varchar(50) NOT NULL DEFAULT 'start',
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `faqs`
--

LOCK TABLES `faqs` WRITE;
/*!40000 ALTER TABLE `faqs` DISABLE KEYS */;
INSERT INTO `faqs` VALUES
(1,'start','Как создать сервер на CraftHost?','Зарегистрируйтесь, пополните баланс в личном кабинете, откройте Конфигуратор, выберите тариф, версию ядра и срок аренды — сервер поднимется автоматически за 60 секунд.',1,1,'2026-05-21 18:05:06','2026-05-21 18:05:06'),
(2,'start','Нужно ли что-то устанавливать на компьютер?','Нет. Управление сервером полностью через браузер: веб-консоль, файловый менеджер и бэкапы доступны с любого устройства.',2,1,'2026-05-21 18:05:06','2026-05-21 18:05:06'),
(3,'start','Какие версии Minecraft поддерживаются?','Мы поддерживаем Vanilla, Paper, Forge, Fabric и Spigot. Версии от 1.16 до последней актуальной. Выбрать ядро можно при создании сервера.',3,1,'2026-05-21 18:05:07','2026-05-21 18:05:07'),
(4,'start','Как подключиться к серверу?','После создания сервера в личном кабинете появится адрес (IP:порт). Скопируйте его и вставьте в Minecraft: Мультиплеер → Добавить сервер → Адрес сервера.',4,1,'2026-05-21 18:05:07','2026-05-21 18:05:07'),
(5,'billing','Как работает оплата?','Вы пополняете внутренний баланс, а аренда сервера списывается с него. Тарификация посуточная — платите только за выбранный срок, без скрытых платежей.',5,1,'2026-05-21 18:05:08','2026-05-21 18:05:08'),
(6,'billing','Какие способы оплаты доступны?','Банковские карты (Visa/MasterCard/МИР) через ЮKassa и криптовалюта USDT (TRC-20, BEP-20, ERC-20).',6,1,'2026-05-21 18:05:08','2026-05-21 18:05:08'),
(7,'billing','Можно ли продлить сервер?','Да, в личном кабинете на карточке сервера есть кнопка «Продлить». Укажите количество дней — стоимость рассчитается автоматически.',7,1,'2026-05-21 18:05:09','2026-05-21 18:05:09'),
(8,'billing','Есть ли реферальная программа?','Да! Делитесь реферальной ссылкой из кабинета и получайте 10% от пополнений приглашённых пользователей на свой баланс. Друзья получают промокод на 3% скидку.',8,1,'2026-05-21 18:05:09','2026-05-21 18:05:09'),
(9,'billing','Что произойдёт, если не продлить сервер?','После истечения срока аренды сервер будет приостановлен. Данные хранятся ещё 7 дней. Если не продлить за это время — сервер и все данные удаляются.',9,1,'2026-05-21 18:05:10','2026-05-21 18:05:10'),
(10,'server','Как управлять сервером через консоль?','На карточке сервера нажмите «Консоль» — откроется веб-консоль с выводом логов в реальном времени. Вводите команды прямо в поле ввода (op, whitelist, say и т.д.).',10,1,'2026-05-21 18:05:10','2026-05-21 18:05:10'),
(11,'server','Как установить моды и плагины?','Используйте файловый менеджер в панели Pterodactyl. Загрузите файлы плагинов (.jar) в папку plugins (Paper/Spigot) или mods (Forge/Fabric) и перезапустите сервер.',11,1,'2026-05-21 18:05:11','2026-05-21 18:05:11'),
(12,'server','Как сделать бэкап сервера?','В карточке сервера раскройте раздел «Бэкапы» и нажмите «Создать бэкап». Для восстановления нажмите «Восстановить» рядом с нужным бэкапом.',12,1,'2026-05-21 18:05:11','2026-05-21 18:05:11'),
(13,'server','Как пересоздать мир?','На карточке сервера нажмите «Новый мир». Внимание: текущий мир будет удалён! Рекомендуем сделать бэкап перед этим.',13,1,'2026-05-21 18:05:11','2026-05-21 18:05:11'),
(14,'server','Как дать друзьям доступ к серверу?','Просто отправьте друзьям адрес сервера (IP:порт) из личного кабинета. Для управления правами используйте команды whitelist и op в консоли.',14,1,'2026-05-21 18:05:12','2026-05-21 18:05:12'),
(15,'tech','Защищены ли серверы от DDoS?','Да, на всех тарифах включена сетевая DDoS-защита без дополнительной платы. Мы используем фильтрацию трафика для защиты игровых серверов.',15,1,'2026-05-21 18:05:12','2026-05-21 18:05:12'),
(16,'tech','Какое железо используется?','Высокочастотные процессоры Intel Xeon / AMD EPYC, NVMe SSD-накопители и DDR4 ECC память. Ресурсы строго выделены — никаких лагов из-за соседей.',16,1,'2026-05-21 18:05:13','2026-05-21 18:05:13'),
(17,'tech','Есть ли ограничения по трафику?','Нет, трафик не ограничен на всех тарифах. Скорость порта — 1 Гбит/с.',17,1,'2026-05-21 18:05:13','2026-05-21 18:05:13'),
(18,'tech','Какой аптайм гарантируется?','Мы гарантируем 99.9% аптайм. Серверная инфраструктура размещена в сертифицированном дата-центре с резервным питанием и каналами связи.',18,1,'2026-05-21 18:05:14','2026-05-21 18:05:14'),
(19,'support','Как связаться с поддержкой?','Нажмите кнопку чата в правом нижнем углу на любой странице. Создайте обращение, и мы ответим в среднем за 15 минут.',19,1,'2026-05-21 18:05:14','2026-05-21 18:05:14'),
(20,'support','В какое время работает поддержка?','Поддержка работает ежедневно с 10:00 до 23:00 по московскому времени. В нерабочее время обращения обрабатываются в порядке очереди.',20,1,'2026-05-21 18:05:14','2026-05-21 18:05:14'),
(21,'support','Можно ли получить возврат средств?','Да, если сервер не использовался. Для возврата обратитесь в поддержку с описанием причины. Решение принимается индивидуально.',21,1,'2026-05-21 18:05:15','2026-05-21 18:05:15');
/*!40000 ALTER TABLE `faqs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mc_versions`
--

DROP TABLE IF EXISTS `mc_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mc_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(64) NOT NULL,
  `label` varchar(128) NOT NULL,
  `type` varchar(32) NOT NULL,
  `jar_url` varchar(512) DEFAULT NULL,
  `ptero_egg_id` int(10) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mc_versions_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mc_versions`
--

LOCK TABLES `mc_versions` WRITE;
/*!40000 ALTER TABLE `mc_versions` DISABLE KEYS */;
INSERT INTO `mc_versions` VALUES
(1,'vanilla_1.21.5','Vanilla 1.21.5','vanilla','',1,1,1),
(2,'vanilla_1.21.4','Vanilla 1.21.4','vanilla','',1,1,2),
(3,'vanilla_1.20.4','Vanilla 1.20.4','vanilla','',1,1,3),
(4,'vanilla_1.19.4','Vanilla 1.19.4','vanilla','',1,1,4),
(5,'vanilla_1.18.2','Vanilla 1.18.2','vanilla','',1,1,5),
(6,'paper_1.21.5','Paper 1.21.5','paper','',2,1,6),
(7,'paper_1.21.4','Paper 1.21.4','paper','',2,1,7),
(8,'paper_1.20.4','Paper 1.20.4','paper','',2,1,8),
(9,'paper_1.19.4','Paper 1.19.4','paper','',2,1,9),
(10,'paper_1.18.2','Paper 1.18.2','paper','',2,1,10),
(11,'forge_1.20.1','Forge 1.20.1','forge','',3,1,11),
(12,'forge_1.19.4','Forge 1.19.4','forge','',3,1,12);
/*!40000 ALTER TABLE `mc_versions` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES
(1,'2014_10_12_000000_create_users_table',1),
(2,'2014_10_12_100000_create_password_reset_tokens_table',1),
(3,'2019_08_19_000000_create_failed_jobs_table',1),
(4,'2019_12_14_000001_create_personal_access_tokens_table',1),
(5,'2024_01_01_000000_create_base_tables',1),
(6,'2025_05_19_000000_add_provisioning_fields_to_servers_table',1),
(7,'2025_05_19_010000_create_mc_versions_table',1),
(8,'2025_05_19_010100_create_nodes_table',1),
(9,'2025_05_19_010200_create_promo_codes_table',1),
(10,'2025_05_19_010300_create_settings_table',1),
(11,'2025_05_19_010400_ensure_notifications_and_audit_log_tables',1),
(12,'2025_05_19_020000_add_ticket_assignment_and_order_type',1),
(13,'2025_05_21_000001_add_meta_to_payments_table',2),
(14,'2025_05_21_000002_create_faqs_table',3),
(15,'2026_05_21_000003_create_email_verification_tokens_table',4);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nodes`
--

DROP TABLE IF EXISTS `nodes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `nodes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `ptero_node_id` int(10) unsigned DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `fqdn` varchar(255) DEFAULT NULL,
  `max_servers` int(10) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nodes`
--

LOCK TABLES `nodes` WRITE;
/*!40000 ALTER TABLE `nodes` DISABLE KEYS */;
/*!40000 ALTER TABLE `nodes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `type` varchar(64) NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_user_id_read_at_index` (`user_id`,`read_at`),
  CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES
(1,2,'server_error','{\"server_id\":1,\"error\":\"\\u041d\\u0435 \\u0443\\u0434\\u0430\\u043b\\u043e\\u0441\\u044c \\u0441\\u043e\\u0437\\u0434\\u0430\\u0442\\u044c \\u0441\\u0435\\u0440\\u0432\\u0435\\u0440 \\u0432 Pterodactyl: {\\n    \\\"errors\\\": [\\n        {\\n            \\\"code\\\": \\\"AccessDeniedHttpException\\\",\\n            \\\"status\\\": \\\"403\\\",\\n            \\\"detail\\\": \\\"This action is unauthorized.\\\"\\n        }\\n    ]\\n}\"}',NULL,'2026-05-21 00:59:19'),
(2,1,'server_error','{\"server_id\":2,\"error\":\"\\u041d\\u0435 \\u0443\\u0434\\u0430\\u043b\\u043e\\u0441\\u044c \\u0441\\u043e\\u0437\\u0434\\u0430\\u0442\\u044c \\u0441\\u0435\\u0440\\u0432\\u0435\\u0440 \\u0432 Pterodactyl: {\\n    \\\"errors\\\": [\\n        {\\n            \\\"code\\\": \\\"AccessDeniedHttpException\\\",\\n            \\\"status\\\": \\\"403\\\",\\n            \\\"detail\\\": \\\"This action is unauthorized.\\\"\\n        }\\n    ]\\n}\"}',NULL,'2026-05-21 00:59:19'),
(3,2,'server_error','{\"server_id\":3,\"error\":\"\\u041d\\u0435 \\u0443\\u0434\\u0430\\u043b\\u043e\\u0441\\u044c \\u0441\\u043e\\u0437\\u0434\\u0430\\u0442\\u044c \\u0441\\u0435\\u0440\\u0432\\u0435\\u0440 \\u0432 Pterodactyl: {\\n    \\\"errors\\\": [\\n        {\\n            \\\"code\\\": \\\"AccessDeniedHttpException\\\",\\n            \\\"status\\\": \\\"403\\\",\\n            \\\"detail\\\": \\\"This action is unauthorized.\\\"\\n        }\\n    ]\\n}\"}',NULL,'2026-05-21 01:02:09'),
(4,2,'server_error','{\"server_id\":1,\"error\":\"\\u041d\\u0435 \\u0443\\u0434\\u0430\\u043b\\u043e\\u0441\\u044c \\u0441\\u043e\\u0437\\u0434\\u0430\\u0442\\u044c \\u0441\\u0435\\u0440\\u0432\\u0435\\u0440 \\u0432 Pterodactyl: {\\n    \\\"errors\\\": [\\n        {\\n            \\\"code\\\": \\\"AccessDeniedHttpException\\\",\\n            \\\"status\\\": \\\"403\\\",\\n            \\\"detail\\\": \\\"This action is unauthorized.\\\"\\n        }\\n    ]\\n}\"}',NULL,'2026-05-21 01:03:19'),
(5,1,'server_error','{\"server_id\":2,\"error\":\"\\u041d\\u0435 \\u0443\\u0434\\u0430\\u043b\\u043e\\u0441\\u044c \\u0441\\u043e\\u0437\\u0434\\u0430\\u0442\\u044c \\u0441\\u0435\\u0440\\u0432\\u0435\\u0440 \\u0432 Pterodactyl: {\\n    \\\"errors\\\": [\\n        {\\n            \\\"code\\\": \\\"AccessDeniedHttpException\\\",\\n            \\\"status\\\": \\\"403\\\",\\n            \\\"detail\\\": \\\"This action is unauthorized.\\\"\\n        }\\n    ]\\n}\"}',NULL,'2026-05-21 01:03:19'),
(6,2,'server_error','{\"server_id\":3,\"error\":\"\\u041d\\u0435 \\u0443\\u0434\\u0430\\u043b\\u043e\\u0441\\u044c \\u0441\\u043e\\u0437\\u0434\\u0430\\u0442\\u044c \\u0441\\u0435\\u0440\\u0432\\u0435\\u0440 \\u0432 Pterodactyl: {\\n    \\\"errors\\\": [\\n        {\\n            \\\"code\\\": \\\"AccessDeniedHttpException\\\",\\n            \\\"status\\\": \\\"403\\\",\\n            \\\"detail\\\": \\\"This action is unauthorized.\\\"\\n        }\\n    ]\\n}\"}',NULL,'2026-05-21 01:03:20'),
(7,2,'server_error','{\"server_id\":3,\"error\":\"\\u041d\\u0435 \\u0443\\u0434\\u0430\\u043b\\u043e\\u0441\\u044c \\u0441\\u043e\\u0437\\u0434\\u0430\\u0442\\u044c \\u0441\\u0435\\u0440\\u0432\\u0435\\u0440 \\u0432 Pterodactyl: {\\n    \\\"errors\\\": [\\n        {\\n            \\\"code\\\": \\\"InvalidFilterQuery\\\",\\n            \\\"status\\\": \\\"400\\\",\\n            \\\"detail\\\": \\\"Requested filter(s) `assigned` are not allowed. Allowed filter(s) are `ip, port, ip_alias, server_id`.\\\"\\n        }\\n    ]\\n}\"}',NULL,'2026-05-21 01:05:37'),
(8,2,'server_error','{\"server_id\":1,\"error\":\"\\u041d\\u0435 \\u0443\\u0434\\u0430\\u043b\\u043e\\u0441\\u044c \\u0441\\u043e\\u0437\\u0434\\u0430\\u0442\\u044c \\u0441\\u0435\\u0440\\u0432\\u0435\\u0440 \\u0432 Pterodactyl: {\\n    \\\"errors\\\": [\\n        {\\n            \\\"code\\\": \\\"InvalidFilterQuery\\\",\\n            \\\"status\\\": \\\"400\\\",\\n            \\\"detail\\\": \\\"Requested filter(s) `assigned` are not allowed. Allowed filter(s) are `ip, port, ip_alias, server_id`.\\\"\\n        }\\n    ]\\n}\"}',NULL,'2026-05-21 01:05:37'),
(9,1,'server_error','{\"server_id\":2,\"error\":\"\\u041d\\u0435 \\u0443\\u0434\\u0430\\u043b\\u043e\\u0441\\u044c \\u0441\\u043e\\u0437\\u0434\\u0430\\u0442\\u044c \\u0441\\u0435\\u0440\\u0432\\u0435\\u0440 \\u0432 Pterodactyl: {\\n    \\\"errors\\\": [\\n        {\\n            \\\"code\\\": \\\"InvalidFilterQuery\\\",\\n            \\\"status\\\": \\\"400\\\",\\n            \\\"detail\\\": \\\"Requested filter(s) `assigned` are not allowed. Allowed filter(s) are `ip, port, ip_alias, server_id`.\\\"\\n        }\\n    ]\\n}\"}',NULL,'2026-05-21 01:05:37'),
(10,1,'server_error','{\"server_id\":2,\"error\":\"\\u041d\\u0435 \\u0443\\u0434\\u0430\\u043b\\u043e\\u0441\\u044c \\u0441\\u043e\\u0437\\u0434\\u0430\\u0442\\u044c \\u0441\\u0435\\u0440\\u0432\\u0435\\u0440 \\u0432 Pterodactyl: {\\\"errors\\\":[{\\\"code\\\":\\\"ValidationException\\\",\\\"status\\\":\\\"422\\\",\\\"detail\\\":\\\"The Server Version variable field is required.\\\",\\\"meta\\\":{\\\"source_field\\\":\\\"environment.VANILLA_VERSION\\\",\\\"rule\\\":\\\"required\\\"}}]}\"}',NULL,'2026-05-21 01:07:34'),
(11,2,'server_error','{\"server_id\":1,\"error\":\"\\u041d\\u0435 \\u0443\\u0434\\u0430\\u043b\\u043e\\u0441\\u044c \\u0441\\u043e\\u0437\\u0434\\u0430\\u0442\\u044c \\u0441\\u0435\\u0440\\u0432\\u0435\\u0440 \\u0432 Pterodactyl: {\\\"errors\\\":[{\\\"code\\\":\\\"ValidationException\\\",\\\"status\\\":\\\"422\\\",\\\"detail\\\":\\\"The Server Version variable field is required.\\\",\\\"meta\\\":{\\\"source_field\\\":\\\"environment.VANILLA_VERSION\\\",\\\"rule\\\":\\\"required\\\"}}]}\"}',NULL,'2026-05-21 01:07:34'),
(12,2,'server_error','{\"server_id\":3,\"error\":\"\\u041d\\u0435 \\u0443\\u0434\\u0430\\u043b\\u043e\\u0441\\u044c \\u0441\\u043e\\u0437\\u0434\\u0430\\u0442\\u044c \\u0441\\u0435\\u0440\\u0432\\u0435\\u0440 \\u0432 Pterodactyl: {\\\"errors\\\":[{\\\"code\\\":\\\"ValidationException\\\",\\\"status\\\":\\\"422\\\",\\\"detail\\\":\\\"The Server Version variable field is required.\\\",\\\"meta\\\":{\\\"source_field\\\":\\\"environment.VANILLA_VERSION\\\",\\\"rule\\\":\\\"required\\\"}}]}\"}',NULL,'2026-05-21 01:07:35'),
(13,2,'server_ready','{\"server_id\":3,\"address\":\":0\",\"tariff\":\"Diamond\"}',NULL,'2026-05-21 01:08:32'),
(14,2,'server_ready','{\"server_id\":1,\"address\":\":0\",\"tariff\":\"Stone\"}',NULL,'2026-05-21 01:08:32'),
(15,1,'server_ready','{\"server_id\":2,\"address\":\":0\",\"tariff\":\"Diamond\"}',NULL,'2026-05-21 01:08:33'),
(16,3,'server_ready','{\"server_id\":4,\"address\":\":0\",\"tariff\":\"Netherite\"}',NULL,'2026-05-21 02:17:26'),
(17,3,'server_ready','{\"server_id\":5,\"address\":\":0\",\"tariff\":\"Diamond\"}',NULL,'2026-05-21 02:18:56'),
(18,5,'server_ready','{\"server_id\":6,\"address\":\":0\",\"tariff\":\"Diamond\"}',NULL,'2026-05-21 09:35:09'),
(19,4,'server_ready','{\"server_id\":7,\"address\":\":0\",\"tariff\":\"Netherite\"}',NULL,'2026-05-21 10:29:24'),
(20,6,'success',NULL,NULL,'2026-05-21 17:43:52'),
(21,4,'info',NULL,NULL,'2026-05-21 18:14:00'),
(22,4,'ticket_reply','{\"ticket_id\":1,\"subject\":\"\\u043f\\u043f\"}',NULL,'2026-05-21 18:14:16');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `server_id` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'new',
  `status` varchar(255) NOT NULL DEFAULT 'completed',
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `orders_user_id_foreign` (`user_id`),
  KEY `orders_server_id_foreign` (`server_id`),
  CONSTRAINT `orders_server_id_foreign` FOREIGN KEY (`server_id`) REFERENCES `servers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES
(1,2,1,5.00,'new','completed','2026-05-21 00:51:26'),
(2,1,2,480.00,'new','completed','2026-05-21 00:55:35'),
(3,2,3,480.00,'new','completed','2026-05-21 01:01:07'),
(4,3,4,150.00,'new','completed','2026-05-21 02:17:22'),
(5,3,5,16.00,'new','completed','2026-05-21 02:18:50'),
(6,5,6,32.00,'new','completed','2026-05-21 09:35:05'),
(7,4,7,30.00,'new','completed','2026-05-21 10:29:11');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
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
INSERT INTO `password_reset_tokens` VALUES
('admin@crafthost.ru','$2y$12$iHjUmjzIepuf4yj4jioiNeGck1AL8JitHqcYzuRLl.L6uFoE3AKua','2026-05-21 09:30:09'),
('danilkhalilov3@gmail.com','$2y$12$/tBhre50hsNP5gnTO9BwTeAljPWG7qX1.JSyDWyqYj5qxAAe/kbKi','2026-05-21 09:32:11');
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `provider` varchar(255) NOT NULL DEFAULT 'balance',
  `external_id` varchar(255) DEFAULT NULL,
  `confirmation_url` varchar(1024) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'completed',
  `meta` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payments_user_id_foreign` (`user_id`),
  CONSTRAINT `payments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES
(1,1,1005.00,'manual',NULL,NULL,'success',NULL,'2026-05-21 00:41:41'),
(2,2,555.00,'manual',NULL,NULL,'success',NULL,'2026-05-21 01:28:14'),
(3,3,150.00,'crypto_usdt','1.5804','http://144.31.48.179/payment/usdt?payment_id=3','success','{\"amount_usdt\":1.5804,\"rub_amount\":150,\"rub_to_usdt\":95,\"networks\":{\"bep20\":{\"label\":\"BEP-20\",\"fullLabel\":\"BNB Smart Chain (BEP-20)\",\"wallet\":\"0x7db6ae87b04b2b72d9fefc6f392d8d94718aec74\",\"time\":\"~15 \\u0441\\u0435\\u043a\"},\"erc20\":{\"label\":\"ERC-20\",\"fullLabel\":\"Ethereum (ERC-20)\",\"wallet\":\"0x7db6ae87b04b2b72d9fefc6f392d8d94718aec74\",\"time\":\"~3-5 \\u043c\\u0438\\u043d\\u0443\\u0442\"},\"trc20\":{\"label\":\"TRC-20\",\"fullLabel\":\"TRON (TRC-20)\",\"wallet\":\"TM7Yap9nerWHTgEPsGf9VGbriR2mv48mTu\",\"time\":\"~1 \\u043c\\u0438\\u043d\\u0443\\u0442\\u0430\"}},\"invoice_ttl\":60,\"created_ts\":1779329555,\"tx_hash\":\"0x9f3e1bc2c9bd828879ad5697c6ecfc9c4756943cede1da9fc8427c38060b5dfc\",\"tx_block\":99498887,\"tx_confirmations\":13,\"tx_network\":\"BEP-20\"}','2026-05-21 02:12:35'),
(4,3,100.00,'yookassa','31a07e37-000f-5001-9000-1f990f985729',NULL,'pending',NULL,'2026-05-21 02:14:14'),
(5,3,1000.00,'crypto_usdt','10.5306','http://144.31.48.179/payment/usdt?payment_id=5','success','{\"amount_usdt\":10.5306,\"rub_amount\":1000,\"rub_to_usdt\":95,\"networks\":{\"bep20\":{\"label\":\"BEP-20\",\"fullLabel\":\"BNB Smart Chain (BEP-20)\",\"wallet\":\"0x7db6ae87b04b2b72d9fefc6f392d8d94718aec74\",\"time\":\"~15 \\u0441\\u0435\\u043a\"},\"erc20\":{\"label\":\"ERC-20\",\"fullLabel\":\"Ethereum (ERC-20)\",\"wallet\":\"0x7db6ae87b04b2b72d9fefc6f392d8d94718aec74\",\"time\":\"~3-5 \\u043c\\u0438\\u043d\\u0443\\u0442\"},\"trc20\":{\"label\":\"TRC-20\",\"fullLabel\":\"TRON (TRC-20)\",\"wallet\":\"TM7Yap9nerWHTgEPsGf9VGbriR2mv48mTu\",\"time\":\"~1 \\u043c\\u0438\\u043d\\u0443\\u0442\\u0430\"}},\"invoice_ttl\":60,\"created_ts\":1779329781,\"tx_hash\":\"0x4a87be8bf31146201f2575eafb58a05abf05553e856e1712acfd580b10390e9b\",\"tx_block\":99499273,\"tx_confirmations\":27,\"tx_network\":\"BEP-20\"}','2026-05-21 02:16:21'),
(6,5,100.00,'crypto_usdt','1.0507','http://144.31.48.179/payment/usdt?payment_id=6','success','{\"amount_usdt\":1.0507,\"rub_amount\":100,\"rub_to_usdt\":95,\"networks\":{\"bep20\":{\"label\":\"BEP-20\",\"fullLabel\":\"BNB Smart Chain (BEP-20)\",\"wallet\":\"0x7db6ae87b04b2b72d9fefc6f392d8d94718aec74\",\"time\":\"~15 \\u0441\\u0435\\u043a\"},\"erc20\":{\"label\":\"ERC-20\",\"fullLabel\":\"Ethereum (ERC-20)\",\"wallet\":\"0x7db6ae87b04b2b72d9fefc6f392d8d94718aec74\",\"time\":\"~3-5 \\u043c\\u0438\\u043d\\u0443\\u0442\"},\"trc20\":{\"label\":\"TRC-20\",\"fullLabel\":\"TRON (TRC-20)\",\"wallet\":\"TM7Yap9nerWHTgEPsGf9VGbriR2mv48mTu\",\"time\":\"~1 \\u043c\\u0438\\u043d\\u0443\\u0442\\u0430\"}},\"invoice_ttl\":60,\"created_ts\":1779356008,\"tx_hash\":\"0xe7c80f0275c5a87d6b5a27452557b78473bca94cc2abf762d2ad6aca3857a6d7\",\"tx_block\":99557454,\"tx_confirmations\":7,\"tx_network\":\"BEP-20\"}','2026-05-21 09:33:28'),
(7,5,50.00,'yookassa','31a0ea33-000f-5001-9000-1bd9afc8bdc6',NULL,'success',NULL,'2026-05-21 09:54:58');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
INSERT INTO `personal_access_tokens` VALUES
(2,'App\\Models\\User',2,'auth_token','6137473c0f1509aa44c00837588b3c38998561a2772191a85de6dcdd0cd1dbbb','[\"*\"]','2026-05-21 00:51:26',NULL,'2026-05-21 00:46:24','2026-05-21 00:51:26'),
(13,'App\\Models\\User',4,'auth_token','cc8adb4b604607e99197c2debffc8fe974cf0b61667cd1eb5882285ea6cb3332','[\"*\"]','2026-05-21 18:14:29',NULL,'2026-05-21 17:44:41','2026-05-21 18:14:29');
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `promo_codes`
--

DROP TABLE IF EXISTS `promo_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `promo_codes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(32) NOT NULL,
  `discount_pct` tinyint(3) unsigned NOT NULL,
  `max_uses` int(10) unsigned NOT NULL DEFAULT 0,
  `used_count` int(10) unsigned NOT NULL DEFAULT 0,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `promo_codes_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `promo_codes`
--

LOCK TABLES `promo_codes` WRITE;
/*!40000 ALTER TABLE `promo_codes` DISABLE KEYS */;
INSERT INTO `promo_codes` VALUES
(1,'2OBX4GRJ',3,0,0,NULL,'2026-05-21 09:34:16',NULL);
/*!40000 ALTER TABLE `promo_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `promo_uses`
--

DROP TABLE IF EXISTS `promo_uses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `promo_uses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `promo_code_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `order_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `promo_uses_promo_code_id_user_id_unique` (`promo_code_id`,`user_id`),
  KEY `promo_uses_user_id_foreign` (`user_id`),
  KEY `promo_uses_order_id_foreign` (`order_id`),
  CONSTRAINT `promo_uses_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `promo_uses_promo_code_id_foreign` FOREIGN KEY (`promo_code_id`) REFERENCES `promo_codes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `promo_uses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `promo_uses`
--

LOCK TABLES `promo_uses` WRITE;
/*!40000 ALTER TABLE `promo_uses` DISABLE KEYS */;
/*!40000 ALTER TABLE `promo_uses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `referral_codes`
--

DROP TABLE IF EXISTS `referral_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `referral_codes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `code` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `referral_codes_code_unique` (`code`),
  KEY `referral_codes_user_id_foreign` (`user_id`),
  CONSTRAINT `referral_codes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `referral_codes`
--

LOCK TABLES `referral_codes` WRITE;
/*!40000 ALTER TABLE `referral_codes` DISABLE KEYS */;
INSERT INTO `referral_codes` VALUES
(1,1,'GBMJM0PL','2026-05-21 00:42:41'),
(2,3,'INXEYXM7','2026-05-21 02:14:38'),
(3,5,'2OBX4GRJ','2026-05-21 09:34:16');
/*!40000 ALTER TABLE `referral_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `referral_commissions`
--

DROP TABLE IF EXISTS `referral_commissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `referral_commissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `referrer_id` bigint(20) unsigned NOT NULL,
  `referred_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `referral_commissions_referrer_id_foreign` (`referrer_id`),
  KEY `referral_commissions_referred_id_foreign` (`referred_id`),
  CONSTRAINT `referral_commissions_referred_id_foreign` FOREIGN KEY (`referred_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `referral_commissions_referrer_id_foreign` FOREIGN KEY (`referrer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `referral_commissions`
--

LOCK TABLES `referral_commissions` WRITE;
/*!40000 ALTER TABLE `referral_commissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `referral_commissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `server_mods`
--

DROP TABLE IF EXISTS `server_mods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `server_mods` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `server_id` bigint(20) unsigned NOT NULL,
  `filename` varchar(255) NOT NULL,
  `size_bytes` bigint(20) unsigned NOT NULL DEFAULT 0,
  `uploaded_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `server_mods_server_id_foreign` (`server_id`),
  CONSTRAINT `server_mods_server_id_foreign` FOREIGN KEY (`server_id`) REFERENCES `servers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `server_mods`
--

LOCK TABLES `server_mods` WRITE;
/*!40000 ALTER TABLE `server_mods` DISABLE KEYS */;
/*!40000 ALTER TABLE `server_mods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `servers`
--

DROP TABLE IF EXISTS `servers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `servers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `tariff_id` bigint(20) unsigned NOT NULL,
  `node_id` bigint(20) unsigned DEFAULT NULL,
  `ptero_server_id` varchar(255) DEFAULT NULL,
  `server_ip` varchar(45) DEFAULT NULL,
  `server_port` smallint(5) unsigned DEFAULT NULL,
  `sftp_password` varchar(64) DEFAULT NULL,
  `provisioning_error` text DEFAULT NULL,
  `mc_version` varchar(32) NOT NULL DEFAULT '1.20.4',
  `status` enum('pending','provisioning','active','suspended','deleted','error') NOT NULL DEFAULT 'pending',
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `servers_user_id_foreign` (`user_id`),
  KEY `servers_tariff_id_foreign` (`tariff_id`),
  KEY `servers_node_id_foreign` (`node_id`),
  CONSTRAINT `servers_node_id_foreign` FOREIGN KEY (`node_id`) REFERENCES `nodes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `servers_tariff_id_foreign` FOREIGN KEY (`tariff_id`) REFERENCES `tariffs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `servers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `servers`
--

LOCK TABLES `servers` WRITE;
/*!40000 ALTER TABLE `servers` DISABLE KEYS */;
INSERT INTO `servers` VALUES
(1,2,1,NULL,NULL,'144.31.48.179',25566,'0hnK5IKI7AIGSOdA',NULL,'vanilla_1.20.4','deleted','2026-05-22 00:51:26','2026-05-21 00:51:26','2026-05-21 10:27:48'),
(2,1,3,NULL,NULL,'144.31.48.179',25567,'bZNTtPdwXjvA9exH',NULL,'vanilla_1.19.4','deleted','2026-06-20 00:55:35','2026-05-21 00:55:35','2026-05-21 10:27:45'),
(3,2,3,NULL,NULL,'144.31.48.179',25568,'nNZ3QFQkNiNXOJ6Y',NULL,'vanilla_1.20.4','deleted','2026-06-20 01:01:07','2026-05-21 01:01:07','2026-05-21 10:27:42'),
(4,3,4,NULL,NULL,'144.31.48.179',25569,'gMp5aLRe739gAqoJ',NULL,'vanilla_1.20.4','deleted','2026-05-26 02:17:22','2026-05-21 02:17:22','2026-05-21 10:27:41'),
(5,3,3,NULL,NULL,'144.31.48.179',25570,'XVh2MQCfffIuUQEY',NULL,'vanilla_1.20.4','deleted','2026-05-22 02:18:50','2026-05-21 02:18:50','2026-05-21 10:27:39'),
(6,5,3,NULL,'8c178378','144.31.48.179',25571,'LKM2WW82QKF2JRL2',NULL,'vanilla_1.20.4','deleted','2026-05-23 09:35:05','2026-05-21 09:35:05','2026-05-21 10:27:36'),
(7,4,4,NULL,'60aa88f4','144.31.48.179',25571,'mpFImOzLCcNfzRf7',NULL,'vanilla_1.20.4','active','2026-05-22 10:29:11','2026-05-21 10:29:11','2026-05-21 10:29:24');
/*!40000 ALTER TABLE `servers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `type` varchar(16) NOT NULL DEFAULT 'string',
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`key`)
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
-- Table structure for table `support_tickets`
--

DROP TABLE IF EXISTS `support_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `support_tickets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `subject` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'open',
  `assigned_admin_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `support_tickets_user_id_foreign` (`user_id`),
  KEY `support_tickets_assigned_admin_id_foreign` (`assigned_admin_id`),
  CONSTRAINT `support_tickets_assigned_admin_id_foreign` FOREIGN KEY (`assigned_admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `support_tickets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `support_tickets`
--

LOCK TABLES `support_tickets` WRITE;
/*!40000 ALTER TABLE `support_tickets` DISABLE KEYS */;
INSERT INTO `support_tickets` VALUES
(1,4,'пп','answered',4,'2026-05-21 18:14:00','2026-05-21 18:14:16');
/*!40000 ALTER TABLE `support_tickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tariffs`
--

DROP TABLE IF EXISTS `tariffs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tariffs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `ram_mb` int(10) unsigned NOT NULL,
  `cpu_percent` int(10) unsigned NOT NULL DEFAULT 100,
  `disk_mb` int(10) unsigned NOT NULL DEFAULT 10240,
  `slots` int(10) unsigned NOT NULL DEFAULT 20,
  `price_day` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tariffs`
--

LOCK TABLES `tariffs` WRITE;
/*!40000 ALTER TABLE `tariffs` DISABLE KEYS */;
INSERT INTO `tariffs` VALUES
(1,'Stone',1024,100,10240,20,5.00,'2026-05-20 22:08:59','2026-05-20 22:08:59'),
(2,'Iron',2048,150,20480,20,9.00,'2026-05-20 22:08:59','2026-05-20 22:08:59'),
(3,'Diamond',4096,200,40960,20,16.00,'2026-05-20 22:08:59','2026-05-20 22:08:59'),
(4,'Netherite',8192,300,81920,20,30.00,'2026-05-20 22:08:59','2026-05-20 22:08:59');
/*!40000 ALTER TABLE `tariffs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_messages`
--

DROP TABLE IF EXISTS `ticket_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `body` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ticket_messages_ticket_id_foreign` (`ticket_id`),
  KEY `ticket_messages_user_id_foreign` (`user_id`),
  CONSTRAINT `ticket_messages_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_messages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket_messages`
--

LOCK TABLES `ticket_messages` WRITE;
/*!40000 ALTER TABLE `ticket_messages` DISABLE KEYS */;
INSERT INTO `ticket_messages` VALUES
(1,1,4,'пп','2026-05-21 18:14:00'),
(2,1,4,'пп','2026-05-21 18:14:16');
/*!40000 ALTER TABLE `ticket_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `referrer_id` bigint(20) unsigned DEFAULT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'client',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'','danil4@mail.ru',NULL,'$2y$12$FBpbdTTIgxa7epkj0Xs5GOoZqXcaBo3SUf9j5APp599gy9s07xi1a',525.00,NULL,'client',NULL,'2026-05-21 00:41:32','2026-05-21 00:55:35'),
(2,'','testorder@test.com',NULL,'$2y$12$AwMpN2xkxExhgrdO4HRODuE0LSjQcZwOMCUaWzY2i0HOhSFXFruRi',1070.00,NULL,'client',NULL,'2026-05-21 00:46:24','2026-05-21 01:28:14'),
(3,'','nekr@mail.ru',NULL,'$2y$12$vddiSNcwqG6FH2dVM7h0QOCh1AEDqxmPB4DJcgsQwCu/9jAoY.1DS',984.00,NULL,'client',NULL,'2026-05-21 01:52:34','2026-05-21 02:18:50'),
(4,'','admin@crafthost.ru',NULL,'$2y$12$cupTySDcgshZNHgKCi5g3uhfs23/rBicHTA9HtFkeIGLTdMyC2qk6',970.00,NULL,'admin',NULL,'2026-05-21 09:13:18','2026-05-21 10:29:11'),
(5,'','danilkhalilov3@gmail.com',NULL,'$2y$12$L1jqvU//iquXzRpSLGik/uR3Ban/wS/yg3.86e20S7x4xOnX6N8qe',118.00,NULL,'client',NULL,'2026-05-21 09:33:20','2026-05-21 09:55:32'),
(6,'','lordovcky1@gmail.com','2026-05-21 17:43:52','$2y$12$VMtC.a4TqHNvFr8QdvVMtu/Xp4gNDnwYz7SXPTC.qitrvgVyr3nkq',0.00,NULL,'client',NULL,'2026-05-21 10:02:27','2026-05-21 17:43:52');
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

-- Dump completed on 2026-05-21 18:15:46
