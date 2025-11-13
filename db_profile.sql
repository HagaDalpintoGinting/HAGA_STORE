-- Additional schema for profile features
-- Database: db_produk

USE `db_produk`;

-- Extend users with optional biodata fields (MySQL 5.7-compatible)
-- Adds columns only if they don't exist using INFORMATION_SCHEMA + dynamic SQL
SET @db := DATABASE();

-- dob
SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA=@db AND TABLE_NAME='users' AND COLUMN_NAME='dob') = 0,
               'ALTER TABLE `users` ADD COLUMN `dob` DATE NULL AFTER `email`',
               'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- gender
SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA=@db AND TABLE_NAME='users' AND COLUMN_NAME='gender') = 0,
               'ALTER TABLE `users` ADD COLUMN `gender` ENUM(''male'',''female'',''other'') NULL AFTER `dob`',
               'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- phone
SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA=@db AND TABLE_NAME='users' AND COLUMN_NAME='phone') = 0,
               'ALTER TABLE `users` ADD COLUMN `phone` VARCHAR(20) NULL AFTER `gender`',
               'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- avatar
SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA=@db AND TABLE_NAME='users' AND COLUMN_NAME='avatar') = 0,
               'ALTER TABLE `users` ADD COLUMN `avatar` VARCHAR(255) NULL AFTER `phone`',
               'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Addresses
CREATE TABLE IF NOT EXISTS `user_addresses` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `label` VARCHAR(50) DEFAULT NULL,
  `recipient_name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `address_text` TEXT NOT NULL,
  `city` VARCHAR(100) NOT NULL,
  `postal_code` VARCHAR(10) NOT NULL,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_addresses_user` (`user_id`),
  CONSTRAINT `fk_user_addresses_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payments preferences
CREATE TABLE IF NOT EXISTS `user_payments` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `method` ENUM('bank','ewallet','cod') NOT NULL,
  `provider` VARCHAR(50) DEFAULT NULL,
  `account_name` VARCHAR(100) DEFAULT NULL,
  `account_number` VARCHAR(50) DEFAULT NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_payments_user` (`user_id`),
  CONSTRAINT `fk_user_payments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
