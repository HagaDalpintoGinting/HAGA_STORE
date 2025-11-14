-- thread_db.sql
-- Gabungan semua file .sql

-- db_orders.sql
-- Orders and Wishlist schema (MySQL 5.7 compatible)
-- Database: db_produk

USE `db_produk`;

-- Orders
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
  KEY `idx_orders_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Wishlist
CREATE TABLE IF NOT EXISTS `user_wishlist` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `produk_id` INT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_product` (`user_id`,`produk_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- db_product_detail.sql
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

-- db_profile.sql
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
               'ALTER TABLE `users` ADD COLUMN `gender` ENUM('male','female','other') NULL AFTER `dob`',
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

-- db_users.sql
-- Users table for HAGA_STORE
-- Database: db_produk

-- Ensure correct database is selected (adjust if needed)
USE `db_produk`;

-- Create users table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('user','admin') NOT NULL DEFAULT 'user',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed: admin user (email: admin@example.com, password: password)
-- The bcrypt hash below corresponds to the plain text 'password'
INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES
('Admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin')
ON DUPLICATE KEY UPDATE email = email; -- no-op if already exists

-- db_produk (3).sql
-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 26 Okt 2025 pada 13.25
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- Database: `db_produk`
--

-- --------------------------------------------------------

-- Struktur dari tabel `admin`
--
CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `admin` (`id`, `username`, `password`) VALUES
(1, 'admin', 'admin');

-- --------------------------------------------------------

-- Struktur dari tabel `produk`
--
CREATE TABLE `produk` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `harga` int(11) NOT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `kategori` varchar(50) DEFAULT NULL,
  `diskon` int(3) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `produk` (`id`, `nama`, `harga`, `gambar`, `deskripsi`, `kategori`, `diskon`) VALUES
(2, 'The Beatles T-shirt', 499999, 'The Beatles Mens Graphic Tee Black S.jpeg', 'Kaos Band Elegan ', 'Clothes', 0),
(3, 'Air Jordan 1 OG', 2499999, 'Stadium Goods - The Latest Sneakers & Premium Apparel.jpeg', 'Sneakers Keren Dan Menawan', 'Shoes', 0),
(4, 'Levis Jeans 501', 1299999, 'levis jeans.jpeg', 'Celana Jeans Yang Cocok Untuk Tampil Casual', 'Pants', 0);

ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `produk`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `produk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
