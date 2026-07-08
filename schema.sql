-- Database schema for Quotation Management System
-- Create database and import this file in local phpMyAdmin

CREATE DATABASE IF NOT EXISTS `quotation_management`;
USE `quotation_management`;

-- 1. Table structure for table `users`
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(255) NOT NULL,
  `company_name` VARCHAR(255) NOT NULL,
  `company_address` TEXT NOT NULL,
  `gstin_no` VARCHAR(15) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `header_image` TEXT DEFAULT NULL,
  `footer_image` TEXT DEFAULT NULL,
  `sign_image` TEXT DEFAULT NULL,
  `subscription_type` ENUM('trial', 'monthly', 'yearly', '3yearly') DEFAULT 'trial',
  `subscription_start_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `subscription_end_date` DATETIME DEFAULT NULL,
  `trial_products_count` INT DEFAULT 0,
  `trial_companies_count` INT DEFAULT 0,
  `trial_quotations_count` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Table structure for table `products`
CREATE TABLE IF NOT EXISTS `products` (
  `product_id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `price` DECIMAL(10, 2) NOT NULL,
  `description` TEXT NOT NULL,
  `image` TEXT DEFAULT NULL,
  `id` INT NOT NULL, -- User ID who added this product
  `date_added` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_products_user` FOREIGN KEY (`id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Table structure for table `Companies`
CREATE TABLE IF NOT EXISTS `Companies` (
  `company_id` INT AUTO_INCREMENT PRIMARY KEY,
  `party_name` VARCHAR(255) NOT NULL,
  `party_add` TEXT NOT NULL,
  `party_contact` VARCHAR(50) NOT NULL,
  `party_email` VARCHAR(255) NOT NULL,
  `gst_no` VARCHAR(15) NOT NULL,
  `id` INT DEFAULT NULL, -- User ID who added this company
  `date_added` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_companies_user` FOREIGN KEY (`id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Table structure for table `quotations`
CREATE TABLE IF NOT EXISTS `quotations` (
  `quotation_no` VARCHAR(50) NOT NULL PRIMARY KEY,
  `user_id` INT NOT NULL,
  `company_id` INT NOT NULL,
  `company_name` VARCHAR(255) NOT NULL,
  `company_address` TEXT NOT NULL,
  `company_contact` VARCHAR(50) NOT NULL,
  `company_email` VARCHAR(255) NOT NULL,
  `gst_no` VARCHAR(15) NOT NULL,
  `order_by_person` VARCHAR(255) NOT NULL,
  `quotation_date` DATE NOT NULL,
  `items_json` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`items_json`)),
  `sub_total` DECIMAL(10, 2) NOT NULL,
  `gst_amount` DECIMAL(10, 2) NOT NULL,
  `grand_total` DECIMAL(10, 2) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_quotations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
