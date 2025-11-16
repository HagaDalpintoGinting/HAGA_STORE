-- ============================================================
-- HAGA_STORE – DATABASE STRUCTURE (FINAL STABLE VERSION)
-- Compatible: MySQL 5.7 / MariaDB 10.x
-- Author: Rakha Adi Saputro
-- ============================================================

-- ============================================================
-- 0. DATABASE
-- ============================================================
CREATE DATABASE IF NOT EXISTS `db_produk`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `db_produk`;

-- ============================================================
-- 1. USERS (Versi Lengkap)
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('user','admin') NOT NULL DEFAULT 'user',

  -- Biodata Lengkap
  `dob` DATE NULL,
  `gender` ENUM('male','female','other') NULL,
  `phone` VARCHAR(20) NULL,
  `avatar` VARCHAR(255) NULL,

  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed admin user
INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES
('Admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin')
ON DUPLICATE KEY UPDATE email=email;

-- ============================================================
-- 2. ADMIN (Legacy Table)
-- ============================================================
CREATE TABLE IF NOT EXISTS `admin` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `password` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `admin` (`id`, `username`, `password`) VALUES
(1, 'admin', 'admin')
ON DUPLICATE KEY UPDATE username=username;

-- ============================================================
-- 3. PRODUK (Main Product Table)
-- ============================================================
CREATE TABLE IF NOT EXISTS `produk` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nama` VARCHAR(100) NOT NULL,
  `harga` INT(11) NOT NULL,
  `gambar` VARCHAR(255) DEFAULT NULL,
  `deskripsi` TEXT DEFAULT NULL,
  `kategori` VARCHAR(50) DEFAULT NULL,
  `diskon` INT(3) NOT NULL DEFAULT 0,

  -- Additional product detail
  `stock` INT NOT NULL DEFAULT 0,
  `sizes` VARCHAR(100) DEFAULT NULL,

  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sample seed data
INSERT INTO `produk` (`id`, `nama`, `harga`, `gambar`, `deskripsi`, `kategori`, `diskon`)
VALUES
(2, 'The Beatles T-shirt', 499999, 'The Beatles Mens Graphic Tee Black S.jpeg', 'Kaos Band Elegan ', 'Clothes', 0),
(3, 'Air Jordan 1 OG', 2499999, 'Stadium Goods - The Latest Sneakers & Premium Apparel.jpeg', 'Sneakers Keren Dan Menawan', 'Shoes', 0),
(4, 'Levis Jeans 501', 1299999, 'levis jeans.jpeg', 'Celana Jeans Yang Cocok Untuk Tampil Casual', 'Pants', 0)
ON DUPLICATE KEY UPDATE nama=nama;

-- ============================================================
-- 4. PRODUK IMAGES (Gallery)
-- ============================================================
CREATE TABLE IF NOT EXISTS `produk_images` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `produk_id` INT NOT NULL,
  `filename` VARCHAR(255) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_produk_images_produk` (`produk_id`),
  CONSTRAINT `fk_produk_images_produk`
    FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. ORDERS
-- ============================================================
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NULL,
  `total` INT NOT NULL DEFAULT 0,
  `address_snapshot` TEXT,
  `payment_snapshot` TEXT,
  `tracking_number` VARCHAR(100) NULL,
  `status` ENUM('pending','paid','shipped','completed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_orders_user` (`user_id`),

  CONSTRAINT `fk_orders_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. ORDER ITEMS
-- ============================================================
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `order_id` INT NOT NULL,
  `produk_id` INT NOT NULL,
  `nama` VARCHAR(255) NOT NULL,
  `qty` INT NOT NULL,
  `harga` INT NOT NULL,
  `subtotal` INT NOT NULL,
  `size` VARCHAR(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order_items_order` (`order_id`),
  CONSTRAINT `fk_order_items_order`
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. USER WISHLIST
-- ============================================================
CREATE TABLE IF NOT EXISTS `user_wishlist` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `produk_id` INT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_product` (`user_id`,`produk_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8. USER ADDRESSES
-- ============================================================
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

  CONSTRAINT `fk_user_addresses_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9. USER PAYMENTS
-- ============================================================
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
  CONSTRAINT `fk_user_payments_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
