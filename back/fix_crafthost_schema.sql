-- =============================================================================
--  CraftHost — патч схемы БД под текущий код
-- =============================================================================
--  Назначение: твоя БД была залита вручную (нет таблицы `migrations`,
--  Laravel-миграции не запускались), поэтому в ней не хватает таблиц и колонок,
--  которые ожидает код. Этот скрипт добавляет недостающее, НЕ удаляя данные.
--
--  Безопасность:
--   - Все CREATE — через IF NOT EXISTS.
--   - Колонки добавляются через процедуру, которая проверяет их наличие,
--     поэтому скрипт можно запускать повторно без ошибок "duplicate column".
--   - Существующие строки не трогаются (новые колонки nullable / с DEFAULT).
--
--  Как применить (любой способ):
--   - phpMyAdmin: вкладка SQL → вставить весь файл → Выполнить
--   - CLI:  mysql -u USER -p crafthost < fix_crafthost_schema.sql
--
--  Заточено под MySQL 8 (твой дамп: utf8mb4_0900_ai_ci).
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- Вспомогательная процедура: добавить колонку, только если её ещё нет.
-- -----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS _add_col;
DELIMITER //
CREATE PROCEDURE _add_col(
    IN p_table VARCHAR(64),
    IN p_col   VARCHAR(64),
    IN p_ddl   TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table
          AND COLUMN_NAME = p_col
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN ', p_ddl);
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    END IF;
END //
DELIMITER ;

-- =============================================================================
-- 1. audit_log  (причина текущей ошибки 1146)
-- =============================================================================
CREATE TABLE IF NOT EXISTS `audit_log` (
  `id`          bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id`    bigint UNSIGNED NOT NULL,
  `action`      varchar(64) NOT NULL,
  `target_type` varchar(32) DEFAULT NULL,
  `target_id`   bigint UNSIGNED DEFAULT NULL,
  `meta`        json DEFAULT NULL,
  `created_at`  timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_log_target_idx` (`target_type`, `target_id`),
  KEY `audit_log_admin_idx`  (`admin_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- =============================================================================
-- 2. notifications  (создаётся при провизии сервера в ProvisionServer)
-- =============================================================================
CREATE TABLE IF NOT EXISTS `notifications` (
  `id`         bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    bigint UNSIGNED NOT NULL,
  `type`       varchar(64) NOT NULL,
  `data`       json DEFAULT NULL,
  `read_at`    timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_user_read_idx` (`user_id`, `read_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- =============================================================================
-- 3. mc_versions  (валидация заказа exists:mc_versions,slug + /api/mc-versions)
-- =============================================================================
CREATE TABLE IF NOT EXISTS `mc_versions` (
  `id`           bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug`         varchar(64) NOT NULL,
  `label`        varchar(128) NOT NULL,
  `type`         varchar(32) NOT NULL,
  `jar_url`      varchar(512) DEFAULT NULL,
  `ptero_egg_id` int UNSIGNED DEFAULT NULL,
  `is_active`    tinyint(1) NOT NULL DEFAULT 1,
  `sort_order`   int UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mc_versions_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Сид-данные (совпадают с McVersionSeeder). Используем slug, что уже в БД серверов.
INSERT INTO `mc_versions` (`slug`, `label`, `type`, `ptero_egg_id`, `is_active`, `sort_order`)
VALUES
  ('1.20.4',        'Vanilla 1.20.4', 'vanilla', 1, 1, 10),
  ('paper_1.20.4',  'Paper 1.20.4',   'paper',   3, 1, 20),
  ('forge_1.20.1',  'Forge 1.20.1',   'forge',   5, 1, 30),
  ('fabric_1.20.4', 'Fabric 1.20.4',  'fabric',  7, 1, 40)
ON DUPLICATE KEY UPDATE
  `label` = VALUES(`label`), `type` = VALUES(`type`),
  `ptero_egg_id` = VALUES(`ptero_egg_id`), `is_active` = VALUES(`is_active`),
  `sort_order` = VALUES(`sort_order`);

-- =============================================================================
-- 4. servers — расширяем enum статуса и добавляем поля провизии
-- =============================================================================
-- 4a. enum: добавляем pending/provisioning/error (старые значения сохраняются).
ALTER TABLE `servers`
  MODIFY COLUMN `status`
  enum('pending','provisioning','active','suspended','deleted','error')
  NOT NULL DEFAULT 'pending';

-- 4b. новые колонки (идемпотентно).
CALL _add_col('servers', 'node_id',            '`node_id` bigint UNSIGNED DEFAULT NULL AFTER `tariff_id`');
CALL _add_col('servers', 'server_ip',          '`server_ip` varchar(45) DEFAULT NULL AFTER `ptero_server_id`');
CALL _add_col('servers', 'server_port',        '`server_port` smallint UNSIGNED DEFAULT NULL AFTER `server_ip`');
CALL _add_col('servers', 'sftp_password',      '`sftp_password` varchar(64) DEFAULT NULL AFTER `server_port`');
CALL _add_col('servers', 'provisioning_error', '`provisioning_error` text DEFAULT NULL AFTER `sftp_password`');

-- =============================================================================
-- 5. settings — добавляем type/description и засеваем значения
-- =============================================================================
CALL _add_col('settings', 'type',        "`type` varchar(16) NOT NULL DEFAULT 'string'");
CALL _add_col('settings', 'description', '`description` varchar(255) DEFAULT NULL');

-- PRIMARY KEY по `key` (в дампе он мог не задаться как PK).
-- Добавляем только если PK ещё нет.
SET @has_pk := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'settings' AND INDEX_NAME = 'PRIMARY'
);
SET @sql := IF(@has_pk = 0, 'ALTER TABLE `settings` ADD PRIMARY KEY (`key`)', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

INSERT INTO `settings` (`key`, `value`, `type`, `description`) VALUES
  ('maintenance_mode',     '0',                                                  'bool',   'Отключить приём новых заказов'),
  ('maintenance_message',  'Технические работы. Заказы временно недоступны.',     'string', 'Сообщение при включённом maintenance_mode'),
  ('max_servers_per_user', '5',                                                  'int',    'Максимум серверов на пользователя (0 = безлимит)'),
  ('min_topup_amount',     '50',                                                 'int',    'Минимальная сумма пополнения, ₽'),
  ('support_email',        'support@crafthost.ru',                               'string', 'Контактный email поддержки')
ON DUPLICATE KEY UPDATE
  `type` = VALUES(`type`), `description` = VALUES(`description`);

-- =============================================================================
-- 6. nodes — добавляем is_active/fqdn и засеваем одну ноду
-- =============================================================================
CALL _add_col('nodes', 'is_active', '`is_active` tinyint(1) NOT NULL DEFAULT 1 AFTER `max_servers`');
CALL _add_col('nodes', 'fqdn',      '`fqdn` varchar(255) DEFAULT NULL AFTER `location`');

INSERT INTO `nodes` (`name`, `ptero_node_id`, `location`, `fqdn`, `max_servers`, `is_active`, `created_at`)
SELECT 'Default Node', 1, 'ru-mow1', 'node1.crafthost.local', 0, 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM `nodes`);

-- =============================================================================
-- 7. promo_uses — order_id делаем nullable + уникальный (promo_code_id,user_id)
-- =============================================================================
ALTER TABLE `promo_uses`
  MODIFY COLUMN `order_id` bigint UNSIGNED DEFAULT NULL;

SET @has_uniq := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'promo_uses'
    AND INDEX_NAME = 'promo_uses_code_user_unique'
);
SET @sql := IF(@has_uniq = 0,
  'ALTER TABLE `promo_uses` ADD UNIQUE KEY `promo_uses_code_user_unique` (`promo_code_id`, `user_id`)',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- =============================================================================
-- Чистка
-- =============================================================================
DROP PROCEDURE IF EXISTS _add_col;
SET FOREIGN_KEY_CHECKS = 1;

-- Готово. Проверь: SHOW TABLES; и DESCRIBE servers;
