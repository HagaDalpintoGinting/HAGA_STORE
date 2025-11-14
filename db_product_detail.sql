-- Schema updates for product detail features
-- Database: db_produk

USE `db_produk`;

SET @db := DATABASE();

-- Add stock column if missing
SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA=@db AND TABLE_NAME='produk' AND COLUMN_NAME='stock') = 0,
               'ALTER TABLE `produk` ADD COLUMN `stock` INT NOT NULL DEFAULT 0 AFTER `harga`',
               'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add sizes column if missing (comma-separated, e.g. "M,L,XL,XXL")
SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA=@db AND TABLE_NAME='produk' AND COLUMN_NAME='sizes') = 0,
               'ALTER TABLE `produk` ADD COLUMN `sizes` VARCHAR(100) DEFAULT NULL AFTER `stock`',
               'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Gallery table for additional images
CREATE TABLE IF NOT EXISTS `produk_images` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `produk_id` INT NOT NULL,
  `filename` VARCHAR(255) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_produk_images_produk` (`produk_id`),
  CONSTRAINT `fk_produk_images_produk` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
