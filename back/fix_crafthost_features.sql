-- =============================================================================
--  CraftHost — патч схемы под новые фичи (продление / консоль / чат поддержки)
-- =============================================================================
--  Запускать ОДИН раз после обновления кода. Идемпотентно — повторный запуск
--  не вызовет ошибок "duplicate column".
--
--  Применение:
--   - phpMyAdmin: вкладка SQL → вставить файл → Выполнить
--   - CLI:  mysql -u USER -p crafthost < fix_crafthost_features.sql
-- =============================================================================

SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS _add_col2;
DELIMITER //
CREATE PROCEDURE _add_col2(IN p_table VARCHAR(64), IN p_col VARCHAR(64), IN p_ddl TEXT)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND COLUMN_NAME = p_col
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN ', p_ddl);
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    END IF;
END //
DELIMITER ;

-- 1. support_tickets — кто из админов взял тикет в работу (раздел «Чат»).
CALL _add_col2('support_tickets', 'assigned_admin_id',
    '`assigned_admin_id` bigint UNSIGNED DEFAULT NULL AFTER `user_id`');

-- 2. orders.type — расширяем до varchar, чтобы хранить тип 'renew' (продление).
--    Если колонка уже varchar — повторный запуск безвреден.
ALTER TABLE `orders`
  MODIFY COLUMN `type` varchar(16) NOT NULL DEFAULT 'new';

DROP PROCEDURE IF EXISTS _add_col2;

-- Готово.
