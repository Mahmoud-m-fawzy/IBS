-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 23, 2026 at 02:04 AM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

CREATE DATABASE IF NOT EXISTS `ibs_store`;
USE `ibs_store`;
--
-- Database: `ibs_store`
--

DELIMITER $$
--
-- Procedures
--
DROP PROCEDURE IF EXISTS `GetProductStock`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetProductStock` (IN `product_id` INT)   BEGIN
    SELECT 
        p.id,
        (SELECT item_code FROM product_items WHERE product_id = p.id LIMIT 1) as code,
        p.brand,
        p.model,
        p.quantity as stock,
        p.min_stock,
        c.name as category_name,
        b.name as branch_name,
        CASE 
            WHEN p.quantity <= p.min_stock THEN 'Low Stock'
            WHEN p.quantity = 0 THEN 'Out of Stock'
            ELSE 'In Stock'
        END as stock_status
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN branches b ON p.branch_id = b.id
    WHERE p.id = product_id;
END$$

DROP PROCEDURE IF EXISTS `GetSalesByPeriod`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetSalesByPeriod` (IN `start_date` DATE, IN `end_date` DATE)   BEGIN
    SELECT 
        s.id,
        s.receipt_number,
        c.name as customer_name,
        u.name as staff_name,
        b.name as branch_name,
        s.total_amount,
        s.payment_method,
        s.sale_date,
        COUNT(si.id) as item_count
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    LEFT JOIN users u ON s.staff_id = u.id
    LEFT JOIN branches b ON s.branch_id = b.id
    LEFT JOIN sale_items si ON s.id = si.sale_id
    WHERE DATE(s.sale_date) BETWEEN start_date AND end_date
    GROUP BY s.id
    ORDER BY s.sale_date DESC;
END$$

DROP PROCEDURE IF EXISTS `GetTopProducts`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetTopProducts` (IN `limit_count` INT)   BEGIN
    SELECT 
        p.id,
        (SELECT item_code FROM product_items WHERE product_id = p.id LIMIT 1) as code,
        p.brand,
        p.model,
        c.name as category_name,
        COALESCE(SUM(si.quantity), 0) as total_sold,
        COALESCE(SUM(si.total_price), 0) as total_revenue
    FROM products p
    LEFT JOIN sale_items si ON p.id = si.product_id
    LEFT JOIN sales s ON si.sale_id = s.id
    WHERE p.is_active = TRUE
    GROUP BY p.id
    ORDER BY total_revenue DESC
    LIMIT limit_count;
END$$

--
-- Functions
--
DROP FUNCTION IF EXISTS `CalculateStockValue`$$
CREATE DEFINER=`root`@`localhost` FUNCTION `CalculateStockValue` (`product_id` INT) RETURNS DECIMAL(10,2) DETERMINISTIC READS SQL DATA BEGIN
    DECLARE stock_value DECIMAL(10,2);
    SELECT quantity * purchase_price INTO stock_value
    FROM products
    WHERE id = product_id;
    RETURN stock_value;
END$$

DROP FUNCTION IF EXISTS `GetTotalStockValue`$$
CREATE DEFINER=`root`@`localhost` FUNCTION `GetTotalStockValue` () RETURNS DECIMAL(15,2) DETERMINISTIC READS SQL DATA BEGIN
    DECLARE total_value DECIMAL(15,2);
    SELECT SUM(quantity * purchase_price) INTO total_value
    FROM products
    WHERE is_active = TRUE;
    RETURN total_value;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
CREATE TABLE IF NOT EXISTS `branches` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `location` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `name`, `location`, `phone`, `email`, `address`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Main Branch', '123 Main Street, City, Country', '+0123456789', 'main@ibs.com', 'Primary business location', 1, '2026-02-08 13:50:00', '2026-02-13 23:06:45');

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

DROP TABLE IF EXISTS `brands`;
CREATE TABLE IF NOT EXISTS `brands` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `logo_url` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `idx_brands_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`id`, `name`, `description`, `logo_url`, `website`, `contact_email`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Apple', 'Apple Inc. - Technology company designing consumer electronics', 'https://ar.wikipedia.org/wiki/%D9%85%D9%84%D9%81:Apple_logo_black.svg', 'https://www.apple.com', 'support@apple.com', 1, '2026-01-24 00:14:15', '2026-02-20 23:17:36'),
(2, 'Samsung', 'Samsung Electronics - Global technology company', NULL, 'https://www.samsung.com', 'support@samsung.com', 1, '2026-01-24 00:14:15', '2026-02-13 23:06:45'),
(3, 'Xiaomi', 'Xiaomi Corporation - Chinese electronics company', NULL, 'https://www.mi.com', 'support@xiaomi.com', 1, '2026-01-24 00:14:15', '2026-02-13 23:06:45'),
(4, 'OnePlus', 'OnePlus Technology - Smartphone manufacturer', NULL, 'https://www.oneplus.com', 'support@oneplus.com', 1, '2026-01-24 00:14:15', '2026-02-13 23:06:45'),
(5, 'Huawei', 'Huawei Technologies - Chinese multinational technology company', NULL, 'https://www.huawei.com', 'support@huawei.com', 1, '2026-01-24 00:14:15', '2026-02-13 23:06:45');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Phones', 'Mobile phones and smartphones', 1, '2026-01-24 00:14:15', '2026-02-13 23:06:45'),
(2, 'AirPods', 'Apple wireless earbuds and headphones', 1, '2026-01-24 00:14:15', '2026-02-13 23:06:45'),
(3, 'Watch', 'Smart watches and wearable devices', 1, '2026-01-24 00:14:15', '2026-02-13 23:06:45'),
(4, 'Accessories', 'Phone accessories like cases, chargers, cables, etc.', 1, '2026-01-24 00:14:15', '2026-02-13 23:06:45'),
(5, 'Tablets', 'Tablet devices', 1, '2026-01-24 00:14:15', '2026-02-13 23:06:45');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
CREATE TABLE IF NOT EXISTS `customers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `address` text,
  `branch_id` int DEFAULT NULL,
  `is_walk_in` tinyint(1) DEFAULT '0',
  `total_purchases` decimal(10,2) DEFAULT '0.00',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_customers_branch` (`branch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `phone`, `email`, `password`, `address`, `branch_id`, `is_walk_in`, `total_purchases`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Ahmed Hassan', '+966-50-111-2222', 'ahmed@email.com', NULL, 'Riyadh, Saudi Arabia', 1, 0, 1000.00, 1, '2026-01-24 00:14:16', '2026-02-14 19:18:19'),
(2, 'Sarah Johnson', '+966-55-333-4444', 'sarah@email.com', NULL, 'Jeddah, Saudi Arabia', 1, 0, 0.00, 1, '2026-01-24 00:14:16', '2026-02-13 23:06:45'),
(3, 'Mohammed Ali', '+966-58-555-6666', 'mohammed@email.com', NULL, 'Dammam, Saudi Arabia', 1, 0, 200.00, 1, '2026-01-24 00:14:16', '2026-02-18 22:04:39'),
(4, 'mahmoud', '01207703807', NULL, 'RCP-2026-7043', NULL, NULL, 1, 0.00, 1, '2026-02-23 02:03:05', '2026-02-23 02:03:05');

-- --------------------------------------------------------

--
-- Table structure for table `financial_summary`
--

DROP TABLE IF EXISTS `financial_summary`;
CREATE TABLE IF NOT EXISTS `financial_summary` (
  `branch_name` varchar(100) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `net_profit` decimal(33,2) DEFAULT NULL,
  `total_expenses` decimal(32,2) DEFAULT NULL,
  `total_income` decimal(32,2) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `income`
--

DROP TABLE IF EXISTS `income`;
CREATE TABLE IF NOT EXISTS `income` (
  `id` int NOT NULL AUTO_INCREMENT,
  `amount` decimal(10,2) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `category_id` int DEFAULT NULL,
  `branch_id` int DEFAULT NULL,
  `date` date NOT NULL,
  `entry_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_income_date` (`entry_date`),
  KEY `idx_income_branch` (`branch_id`),
  KEY `fk_income_category` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `income`
--

INSERT INTO `income` (`id`, `amount`, `description`, `price`, `category_id`, `branch_id`, `date`, `entry_date`, `created_at`, `updated_at`) VALUES
(1, 5000.00, 'Initial investment', 5000.00, NULL, NULL, '2026-01-01', '2026-01-01', '2026-01-22 23:19:00', '2026-02-13 23:06:45'),
(2, 3000.00, 'Additional capital', 3000.00, NULL, NULL, '2026-01-15', '2026-01-15', '2026-01-22 23:19:00', '2026-02-13 23:06:45'),
(3, 2000.00, 'Loan received', 2000.00, NULL, NULL, '2026-01-20', '2026-01-20', '2026-01-22 23:19:00', '2026-02-13 23:06:45');

-- --------------------------------------------------------

--
-- Table structure for table `income_entries`
--

DROP TABLE IF EXISTS `income_entries`;
CREATE TABLE IF NOT EXISTS `income_entries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `price` decimal(10,2) NOT NULL,
  `description` text NOT NULL,
  `entry_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_income_date` (`entry_date`),
  KEY `idx_income_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `low_stock_alerts`
--

DROP TABLE IF EXISTS `low_stock_alerts`;
CREATE TABLE IF NOT EXISTS `low_stock_alerts` (
  `id` int DEFAULT NULL,
  `code` varchar(50) DEFAULT NULL,
  `brand` varchar(50) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `stock` int DEFAULT NULL,
  `min_stock` int DEFAULT NULL,
  `category_name` varchar(50) DEFAULT NULL,
  `branch_name` varchar(100) DEFAULT NULL,
  `is_low_stock` int DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `amount` decimal(10,2) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `payment_method` varchar(50) DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `category_id` int DEFAULT NULL,
  `branch_id` int DEFAULT NULL,
  `date` date NOT NULL,
  `entry_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payment_date` (`entry_date`),
  KEY `idx_payment_branch` (`branch_id`),
  KEY `fk_payment_category` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `amount`, `description`, `price`, `payment_method`, `reference_number`, `category_id`, `branch_id`, `date`, `entry_date`, `created_at`, `updated_at`) VALUES
(1, 1500.00, 'Shop rent', 1500.00, NULL, NULL, NULL, NULL, '2026-01-05', '2026-01-05', '2026-01-22 23:19:00', '2026-02-13 23:06:45'),
(2, 800.00, 'Electricity bill', 800.00, NULL, NULL, NULL, NULL, '2026-01-10', '2026-01-10', '2026-01-22 23:19:00', '2026-02-13 23:06:45'),
(3, 1200.00, 'Staff salaries', 1200.00, NULL, NULL, NULL, NULL, '2026-01-15', '2026-01-15', '2026-01-22 23:19:00', '2026-02-13 23:06:45');

-- --------------------------------------------------------

--
-- Table structure for table `payment_entries`
--

DROP TABLE IF EXISTS `payment_entries`;
CREATE TABLE IF NOT EXISTS `payment_entries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `price` decimal(10,2) NOT NULL,
  `description` text NOT NULL,
  `entry_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payment_date` (`entry_date`),
  KEY `idx_payment_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_splits`
--

DROP TABLE IF EXISTS `payment_splits`;
CREATE TABLE IF NOT EXISTS `payment_splits` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sale_id` int NOT NULL,
  `payment_method` varchar(20) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reference_number` varchar(50) DEFAULT NULL,
  `installment_details` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payment_splits_sale_id` (`sale_id`),
  KEY `idx_payment_splits_method` (`payment_method`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `payment_splits`
--

INSERT INTO `payment_splits` (`id`, `sale_id`, `payment_method`, `amount`, `reference_number`, `installment_details`, `created_at`) VALUES
(1, 4, 'Cash', 2000.00, NULL, NULL, '2026-02-20 23:32:49'),
(2, 4, 'Visa', 638.00, NULL, NULL, '2026-02-20 23:32:49'),
(3, 5, 'Cash', 3000.00, NULL, NULL, '2026-02-21 01:00:22'),
(4, 5, 'Visa', 999.00, NULL, NULL, '2026-02-21 01:00:22'),
(5, 9, 'Cash', 2000.00, NULL, NULL, '2026-02-21 21:39:45'),
(6, 9, 'Cash', 1000.00, NULL, NULL, '2026-02-21 21:39:45'),
(7, 10, 'Cash', 20000.00, NULL, NULL, '2026-02-21 21:50:05'),
(8, 10, 'Visa', 10000.00, NULL, NULL, '2026-02-21 21:50:05'),
(9, 11, 'Cash', 2000.00, NULL, NULL, '2026-02-21 21:54:19'),
(10, 11, 'Visa', 1000.00, NULL, NULL, '2026-02-21 21:54:19'),
(11, 12, 'Cash', 150000.00, NULL, NULL, '2026-02-21 22:10:59'),
(12, 14, 'Cash', 45000.00, NULL, NULL, '2026-02-22 00:05:14'),
(13, 15, 'Cash', 45000.00, NULL, NULL, '2026-02-22 00:09:12'),
(14, 16, 'Cash', 45000.00, NULL, NULL, '2026-02-22 00:12:52'),
(15, 17, 'Visa', 45000.00, NULL, NULL, '2026-02-22 00:15:40'),
(16, 18, 'Cash', 30000.00, NULL, NULL, '2026-02-22 00:22:35'),
(17, 19, 'Cash', 75000.00, NULL, NULL, '2026-02-22 00:25:52'),
(18, 20, 'Cash', 30000.00, NULL, NULL, '2026-02-22 00:39:26'),
(19, 21, 'Cash', 75000.00, NULL, NULL, '2026-02-22 00:45:46'),
(20, 22, 'Cash', 45000.00, NULL, NULL, '2026-02-22 00:48:22'),
(21, 23, 'Cash', 125000.00, NULL, NULL, '2026-02-22 02:38:13'),
(22, 24, 'Instapay', 75000.00, NULL, NULL, '2026-02-22 02:39:00'),
(23, 25, 'Cash', 50000.00, NULL, NULL, '2026-02-22 02:39:53'),
(24, 26, 'Cash', 30000.00, NULL, NULL, '2026-02-22 03:03:39'),
(25, 27, 'Cash', 15000.00, NULL, NULL, '2026-02-22 03:20:25'),
(26, 28, 'Cash', 30000.00, NULL, NULL, '2026-02-22 03:21:02'),
(27, 29, 'Cash', 9000.00, NULL, NULL, '2026-02-22 03:29:48'),
(28, 30, 'Cash', 3000.00, NULL, NULL, '2026-02-22 03:51:16'),
(29, 31, 'Cash', 10000.00, NULL, NULL, '2026-02-22 23:20:46'),
(30, 31, 'Visa', 5000.00, NULL, NULL, '2026-02-22 23:20:46'),
(31, 31, 'Instapay', 5000.00, NULL, NULL, '2026-02-22 23:20:46'),
(32, 31, 'Installment', 5000.00, NULL, NULL, '2026-02-22 23:20:46'),
(33, 32, 'Cash', 50000.00, NULL, NULL, '2026-02-23 00:01:35'),
(34, 32, 'Instapay', 25000.00, NULL, NULL, '2026-02-23 00:01:35'),
(35, 33, 'Cash', 25000.00, NULL, NULL, '2026-02-23 02:03:05');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `brand` varchar(50) NOT NULL,
  `model` varchar(100) NOT NULL,
  `quantity` int DEFAULT '0',
  `suggested_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `purchase_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `min_selling_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `min_stock` int DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `description` text,
  `image_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `category_id` int DEFAULT NULL,
  `branch_id` int DEFAULT NULL,
  `color` varchar(30) DEFAULT NULL,
  `supplier_id` int DEFAULT NULL,
  `brand_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_products_category` (`category_id`),
  KEY `idx_products_supplier` (`supplier_id`),
  KEY `idx_products_branch` (`branch_id`),
  KEY `idx_products_brand` (`brand_id`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `brand`, `model`, `quantity`, `suggested_price`, `purchase_price`, `min_selling_price`, `min_stock`, `category`, `description`, `image_url`, `is_active`, `created_at`, `updated_at`, `category_id`, `branch_id`, `color`, `supplier_id`, `brand_id`) VALUES
(35, 'Samsung', 'S24 ULTRA', -3, 15000.00, 10000.00, 12000.00, 2, 'Phones', 'Good\n', 'images/products/1771721111_699a5197387e0.jpeg', 1, '2026-02-22 00:45:11', '2026-02-22 03:21:02', 1, 1, 'BLACK', 1, 2),
(36, 'Apple', 'IPHONE 17 PRO', 0, 25000.00, 20000.00, 22000.00, 5, 'Phones', 'Good', 'images/products/1771727838_699a6bde50fc1.png', 1, '2026-02-22 02:37:18', '2026-02-22 02:39:53', 1, 1, 'Gold', 3, 1),
(37, 'Huawei', 'watch 9', 2, 1500.00, 1000.00, 1200.00, 5, 'Phones', '', 'images/products/1771730950_699a780655bc1.jpeg', 1, '2026-02-22 03:29:10', '2026-02-22 03:51:16', 1, 1, 'BLACK', 1, 5),
(38, 'Apple', 'IPHONE 16', 10, 25000.00, 20000.00, 22000.00, 5, 'Phones', 'gg', 'images/products/1771734682_699a869ad8090.png', 1, '2026-02-22 04:31:22', '2026-02-23 02:03:05', 1, 1, 'Gold', 3, 1);

-- --------------------------------------------------------

--
-- Table structure for table `product_items`
--

DROP TABLE IF EXISTS `product_items`;
CREATE TABLE IF NOT EXISTS `product_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `item_code` varchar(50) NOT NULL COMMENT 'Unique identifier for this specific unit',
  `barcode` varchar(50) DEFAULT NULL,
  `imei` varchar(20) DEFAULT NULL,
  `serial_number` varchar(50) DEFAULT NULL,
  `status` enum('available','sold','damaged','returned') DEFAULT 'available',
  `sale_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_item_code` (`item_code`),
  UNIQUE KEY `idx_imei` (`imei`),
  UNIQUE KEY `idx_serial` (`serial_number`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_status` (`status`),
  KEY `fk_product_items_sale` (`sale_id`)
) ENGINE=InnoDB AUTO_INCREMENT=192 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `product_items`
--

INSERT INTO `product_items` (`id`, `product_id`, `item_code`, `barcode`, `imei`, `serial_number`, `status`, `sale_id`, `created_at`, `updated_at`) VALUES
(147, 35, 'PHO-SAM-S24UL-0001', '0000002400013', 'P_35_1', 'PENDING_SERIAL_35_PHO-SAM-S24UL-0001', 'sold', 21, '2026-02-22 00:45:11', '2026-02-22 00:45:46'),
(148, 35, 'PHO-SAM-S24UL-0002', '0000002400020', 'P_35_2', 'PENDING_SERIAL_35_PHO-SAM-S24UL-0002', 'sold', 21, '2026-02-22 00:45:11', '2026-02-22 00:45:46'),
(149, 35, 'PHO-SAM-S24UL-0003', '0000002400037', 'P_35_3', 'PENDING_SERIAL_35_PHO-SAM-S24UL-0003', 'sold', 21, '2026-02-22 00:45:11', '2026-02-22 00:45:46'),
(150, 35, 'PHO-SAM-S24UL-0004', '0000002400044', 'P_35_4', 'PENDING_SERIAL_35_PHO-SAM-S24UL-0004', 'sold', 21, '2026-02-22 00:45:11', '2026-02-22 00:45:46'),
(151, 35, 'PHO-SAM-S24UL-0005', '0000002400051', 'P_35_5', 'PENDING_SERIAL_35_PHO-SAM-S24UL-0005', 'sold', 21, '2026-02-22 00:45:11', '2026-02-22 00:45:46'),
(152, 35, 'PHO-SAM-S24UL-0006', '0000002400068', 'P_35_6', 'PENDING_SERIAL_35_PHO-SAM-S24UL-0006', 'sold', 26, '2026-02-22 00:45:11', '2026-02-22 03:03:39'),
(153, 35, 'PHO-SAM-S24UL-0007', '0000002400075', 'P_35_7', 'PENDING_SERIAL_35_PHO-SAM-S24UL-0007', 'sold', 26, '2026-02-22 00:45:11', '2026-02-22 03:03:39'),
(154, 35, 'PHO-SAM-S24UL-0008', '0000002400082', 'P_35_8', 'PENDING_SERIAL_35_PHO-SAM-S24UL-0008', 'sold', 27, '2026-02-22 00:45:11', '2026-02-22 03:20:25'),
(155, 35, 'PHO-SAM-S24UL-0009', '0000002400099', 'P_35_9', 'PENDING_SERIAL_35_PHO-SAM-S24UL-0009', 'sold', 28, '2026-02-22 00:45:11', '2026-02-22 03:21:02'),
(156, 35, 'PHO-SAM-S24UL-0010', '0000002400105', 'P_35_10', 'PENDING_SERIAL_35_PHO-SAM-S24UL-0010', 'sold', 28, '2026-02-22 00:45:11', '2026-02-22 03:21:02'),
(157, 36, 'PHO-APP-IPHON-0001', '0000000000017', 'P_36_1', 'PENDING_SERIAL_36_PHO-APP-IPHON-0001', 'sold', 23, '2026-02-22 02:37:18', '2026-02-22 02:38:13'),
(158, 36, 'PHO-APP-IPHON-0002', '0000000000024', 'P_36_2', 'PENDING_SERIAL_36_PHO-APP-IPHON-0002', 'sold', 23, '2026-02-22 02:37:18', '2026-02-22 02:38:13'),
(159, 36, 'PHO-APP-IPHON-0003', '0000000000031', 'P_36_3', 'PENDING_SERIAL_36_PHO-APP-IPHON-0003', 'sold', 23, '2026-02-22 02:37:18', '2026-02-22 02:38:13'),
(160, 36, 'PHO-APP-IPHON-0004', '0000000000048', 'P_36_4', 'PENDING_SERIAL_36_PHO-APP-IPHON-0004', 'sold', 23, '2026-02-22 02:37:18', '2026-02-22 02:38:13'),
(161, 36, 'PHO-APP-IPHON-0005', '0000000000055', 'P_36_5', 'PENDING_SERIAL_36_PHO-APP-IPHON-0005', 'sold', 23, '2026-02-22 02:37:18', '2026-02-22 02:38:13'),
(162, 36, 'PHO-APP-IPHON-0006', '0000000000062', 'P_36_6', 'PENDING_SERIAL_36_PHO-APP-IPHON-0006', 'sold', 24, '2026-02-22 02:37:18', '2026-02-22 02:39:00'),
(163, 36, 'PHO-APP-IPHON-0007', '0000000000079', 'P_36_7', 'PENDING_SERIAL_36_PHO-APP-IPHON-0007', 'sold', 24, '2026-02-22 02:37:18', '2026-02-22 02:39:00'),
(164, 36, 'PHO-APP-IPHON-0008', '0000000000086', 'P_36_8', 'PENDING_SERIAL_36_PHO-APP-IPHON-0008', 'sold', 24, '2026-02-22 02:37:18', '2026-02-22 02:39:00'),
(165, 36, 'PHO-APP-IPHON-0009', '0000000000093', 'P_36_9', 'PENDING_SERIAL_36_PHO-APP-IPHON-0009', 'sold', 25, '2026-02-22 02:37:18', '2026-02-22 02:39:53'),
(166, 36, 'PHO-APP-IPHON-0010', '0000000000109', 'P_36_10', 'PENDING_SERIAL_36_PHO-APP-IPHON-0010', 'sold', 25, '2026-02-22 02:37:18', '2026-02-22 02:39:53'),
(167, 37, 'PHO-HUA-WATCH-0001', '0000000000017', NULL, NULL, 'sold', 29, '2026-02-22 03:29:10', '2026-02-22 03:29:48'),
(168, 37, 'PHO-HUA-WATCH-0002', '0000000000024', NULL, NULL, 'sold', 29, '2026-02-22 03:29:10', '2026-02-22 03:29:48'),
(169, 37, 'PHO-HUA-WATCH-0003', '0000000000031', NULL, NULL, 'sold', 29, '2026-02-22 03:29:10', '2026-02-22 03:29:48'),
(170, 37, 'PHO-HUA-WATCH-0004', '0000000000048', NULL, NULL, 'sold', 29, '2026-02-22 03:29:10', '2026-02-22 03:29:48'),
(171, 37, 'PHO-HUA-WATCH-0005', '0000000000055', NULL, NULL, 'sold', 29, '2026-02-22 03:29:10', '2026-02-22 03:29:48'),
(172, 37, 'PHO-HUA-WATCH-0006', '0000000000062', NULL, NULL, 'sold', 29, '2026-02-22 03:29:10', '2026-02-22 03:29:48'),
(173, 37, 'PHO-HUA-WATCH-0007', '0000000000079', NULL, NULL, 'sold', 30, '2026-02-22 03:29:10', '2026-02-22 03:51:16'),
(174, 37, 'PHO-HUA-WATCH-0008', '0000000000086', NULL, NULL, 'sold', 30, '2026-02-22 03:29:10', '2026-02-22 03:51:16'),
(175, 37, 'PHO-HUA-WATCH-0009', '0000000000093', NULL, NULL, 'available', NULL, '2026-02-22 03:29:10', '2026-02-22 03:29:10'),
(176, 37, 'PHO-HUA-WATCH-0010', '0000000000109', NULL, NULL, 'available', NULL, '2026-02-22 03:29:10', '2026-02-22 03:29:10'),
(177, 38, 'PHO-APP-IPHON-0011', '0000000000116', '23232', '43', 'sold', 33, '2026-02-22 04:31:22', '2026-02-23 02:03:05'),
(178, 38, 'PHO-APP-IPHON-0012', '0000000000123', 'P_38_12', 'PENDING_SERIAL_38_PHO-APP-IPHON-0012', 'available', NULL, '2026-02-22 04:31:22', '2026-02-23 00:24:46'),
(179, 38, 'PHO-APP-IPHON-0013', '0000000000130', 'P_38_13', 'PENDING_SERIAL_38_PHO-APP-IPHON-0013', 'sold', 32, '2026-02-22 04:31:22', '2026-02-23 00:01:35'),
(180, 38, 'PHO-APP-IPHON-0014', '0000000000147', 'P_38_14', 'PENDING_SERIAL_38_PHO-APP-IPHON-0014', 'available', NULL, '2026-02-22 04:31:22', '2026-02-22 04:31:22'),
(181, 38, 'PHO-APP-IPHON-0015', '0000000000154', 'P_38_15', 'PENDING_SERIAL_38_PHO-APP-IPHON-0015', 'available', NULL, '2026-02-22 04:31:22', '2026-02-22 04:31:22'),
(182, 38, 'PHO-APP-IPHON-0016', '0000000000161', 'P_38_16', 'PENDING_SERIAL_38_PHO-APP-IPHON-0016', 'available', NULL, '2026-02-22 04:31:22', '2026-02-22 04:31:22'),
(183, 38, 'PHO-APP-IPHON-0017', '0000000000178', 'P_38_17', 'PENDING_SERIAL_38_PHO-APP-IPHON-0017', 'available', NULL, '2026-02-22 04:31:22', '2026-02-22 04:31:22'),
(184, 38, 'PHO-APP-IPHON-0018', '0000000000185', 'P_38_18', 'PENDING_SERIAL_38_PHO-APP-IPHON-0018', 'available', NULL, '2026-02-22 04:31:22', '2026-02-22 04:31:22'),
(185, 38, 'PHO-APP-IPHON-0019', '0000000000192', 'P_38_19', 'PENDING_SERIAL_38_PHO-APP-IPHON-0019', 'available', NULL, '2026-02-22 04:31:22', '2026-02-22 04:31:22'),
(186, 38, 'PHO-APP-IPHON-0020', '0000000000208', 'P_38_20', 'PENDING_SERIAL_38_PHO-APP-IPHON-0020', 'available', NULL, '2026-02-22 04:31:22', '2026-02-22 04:31:22'),
(187, 38, 'PHO-APP-IPHON-0021', '0000000000215', 'P_38_21', 'PENDING_SERIAL_38_PHO-APP-IPHON-0021', 'available', NULL, '2026-02-22 04:31:22', '2026-02-22 04:31:22'),
(188, 38, 'PHO-APP-IPHON-0022', '0000000000222', 'P_38_22', 'PENDING_SERIAL_38_PHO-APP-IPHON-0022', 'available', NULL, '2026-02-22 04:31:22', '2026-02-22 04:31:22'),
(189, 38, 'PHO-APP-IPHON-0023', '0000000000239', 'P_38_23', 'PENDING_SERIAL_38_PHO-APP-IPHON-0023', 'available', NULL, '2026-02-22 04:31:22', '2026-02-22 04:31:22'),
(190, 38, 'PHO-APP-IPHON-0024', '0000000000246', 'P_38_24', 'PENDING_SERIAL_38_PHO-APP-IPHON-0024', 'available', NULL, '2026-02-22 04:31:22', '2026-02-22 04:31:22'),
(191, 38, 'PHO-APP-IPHON-0025', '0000000000253', 'P_38_25', 'PENDING_SERIAL_38_PHO-APP-IPHON-0025', 'available', NULL, '2026-02-22 04:31:22', '2026-02-22 04:31:22');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
CREATE TABLE IF NOT EXISTS `sales` (
  `id` int NOT NULL AUTO_INCREMENT,
  `receipt_number` varchar(20) NOT NULL,
  `sale_number` varchar(50) NOT NULL DEFAULT '',
  `customer_id` int DEFAULT NULL,
  `staff_id` int DEFAULT NULL,
  `branch_id` int DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(20) DEFAULT NULL,
  `status` enum('completed','returned','partially_returned') DEFAULT 'completed',
  `is_split_payment` tinyint(1) DEFAULT '0',
  `total_paid` decimal(10,2) GENERATED ALWAYS AS (`total_amount`) STORED,
  `sale_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `receipt_number` (`receipt_number`),
  KEY `idx_sales_customer` (`customer_id`),
  KEY `idx_sales_staff` (`staff_id`),
  KEY `idx_sales_branch` (`branch_id`),
  KEY `idx_sales_date` (`sale_date`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `receipt_number`, `sale_number`, `customer_id`, `staff_id`, `branch_id`, `total_amount`, `payment_method`, `status`, `is_split_payment`, `sale_date`, `notes`, `created_at`, `updated_at`) VALUES
(21, 'RCP-2026-2680', '', NULL, 2, NULL, 75000.00, 'Cash', 'completed', 0, '2026-02-22 00:45:46', NULL, '2026-02-22 00:45:46', '2026-02-22 00:45:46'),
(22, 'RCP-2026-7252', '', NULL, 2, NULL, 45000.00, 'Cash', 'completed', 0, '2026-02-22 00:48:22', NULL, '2026-02-22 00:48:22', '2026-02-22 00:48:22'),
(23, 'RCP-2026-0418', '', NULL, 2, NULL, 125000.00, 'Cash', 'completed', 0, '2026-02-22 02:38:13', NULL, '2026-02-22 02:38:13', '2026-02-22 02:38:13'),
(24, 'RCP-2026-1723', '', NULL, 2, NULL, 75000.00, 'Instapay', 'completed', 0, '2026-02-22 02:39:00', NULL, '2026-02-22 02:39:00', '2026-02-22 02:39:00'),
(25, 'RCP-2026-4335', '', NULL, 2, NULL, 50000.00, 'Cash', 'completed', 0, '2026-02-22 02:39:53', NULL, '2026-02-22 02:39:53', '2026-02-22 02:39:53'),
(26, 'RCP-2026-5164', '', NULL, 2, NULL, 30000.00, 'Cash', 'completed', 0, '2026-02-22 03:03:39', NULL, '2026-02-22 03:03:39', '2026-02-22 03:03:39'),
(27, 'RCP-2026-2740', '', NULL, 2, NULL, 15000.00, 'Cash', 'completed', 0, '2026-02-22 03:20:25', NULL, '2026-02-22 03:20:25', '2026-02-22 03:20:25'),
(28, 'RCP-2026-5630', '', NULL, 2, NULL, 30000.00, 'Cash', 'completed', 0, '2026-02-22 03:21:02', NULL, '2026-02-22 03:21:02', '2026-02-22 03:21:02'),
(29, 'RCP-2026-9106', '', NULL, 2, NULL, 9000.00, 'Cash', 'completed', 0, '2026-02-22 03:29:48', NULL, '2026-02-22 03:29:48', '2026-02-22 03:29:48'),
(30, 'RCP-2026-7161', '', NULL, 2, NULL, 3000.00, 'Cash', 'completed', 0, '2026-02-22 03:51:16', NULL, '2026-02-22 03:51:16', '2026-02-22 03:51:16'),
(31, 'RCP-2026-2949', '', NULL, 2, NULL, 25000.00, 'Split', 'returned', 1, '2026-02-22 23:20:46', NULL, '2026-02-22 23:20:46', '2026-02-23 00:00:31'),
(32, 'RCP-2026-0766', '', NULL, 2, NULL, 75000.00, 'Split', 'partially_returned', 1, '2026-02-23 00:01:35', NULL, '2026-02-23 00:01:35', '2026-02-23 00:24:46'),
(33, 'RCP-2026-7043', '', 4, 2, NULL, 25000.00, 'Cash', 'completed', 0, '2026-02-23 02:03:05', NULL, '2026-02-23 02:03:05', '2026-02-23 02:03:05');

-- --------------------------------------------------------

--
-- Stand-in structure for view `sales_summary`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `sales_summary`;
CREATE TABLE IF NOT EXISTS `sales_summary` (
`sale_date` date
,`total_sales` bigint
,`total_revenue` decimal(32,2)
,`avg_sale_amount` decimal(14,6)
,`branch_name` varchar(100)
);

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

DROP TABLE IF EXISTS `sale_items`;
CREATE TABLE IF NOT EXISTS `sale_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sale_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `returned_quantity` int NOT NULL DEFAULT '0',
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sale_items_sale` (`sale_id`),
  KEY `idx_sale_items_product` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `product_id`, `quantity`, `returned_quantity`, `unit_price`, `total_price`, `created_at`) VALUES
(30, 21, 35, 5, 0, 15000.00, 75000.00, '2026-02-22 00:45:46'),
(31, 22, 35, 3, 0, 15000.00, 45000.00, '2026-02-22 00:48:22'),
(32, 23, 36, 5, 0, 25000.00, 125000.00, '2026-02-22 02:38:13'),
(33, 24, 36, 3, 0, 25000.00, 75000.00, '2026-02-22 02:39:00'),
(34, 25, 36, 2, 0, 25000.00, 50000.00, '2026-02-22 02:39:53'),
(35, 26, 35, 2, 0, 15000.00, 30000.00, '2026-02-22 03:03:39'),
(36, 27, 35, 1, 0, 15000.00, 15000.00, '2026-02-22 03:20:25'),
(37, 28, 35, 2, 0, 15000.00, 30000.00, '2026-02-22 03:21:02'),
(38, 29, 37, 6, 0, 1500.00, 9000.00, '2026-02-22 03:29:48'),
(39, 30, 37, 2, 0, 1500.00, 3000.00, '2026-02-22 03:51:16'),
(40, 31, 38, 1, 0, 25000.00, 25000.00, '2026-02-22 23:20:46'),
(41, 32, 38, 3, 2, 25000.00, 75000.00, '2026-02-23 00:01:35'),
(42, 33, 38, 1, 0, 25000.00, 25000.00, '2026-02-23 02:03:05');

--
-- Triggers `sale_items`
--
DROP TRIGGER IF EXISTS `restore_stock_on_sale_delete`;
DELIMITER $$
CREATE TRIGGER `restore_stock_on_sale_delete` AFTER DELETE ON `sale_items` FOR EACH ROW BEGIN
    UPDATE products 
    SET quantity = quantity + OLD.quantity 
    WHERE id = OLD.product_id;
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `update_stock_on_sale`;
DELIMITER $$
CREATE TRIGGER `update_stock_on_sale` AFTER INSERT ON `sale_items` FOR EACH ROW BEGIN
    UPDATE products 
    SET quantity = quantity - NEW.quantity 
    WHERE id = NEW.product_id;
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `update_stock_on_sale_item_update`;
DELIMITER $$
CREATE TRIGGER `update_stock_on_sale_item_update` AFTER UPDATE ON `sale_items` FOR EACH ROW BEGIN
    IF NEW.quantity != OLD.quantity THEN
        UPDATE products 
        SET quantity = quantity + (OLD.quantity - NEW.quantity) 
        WHERE id = NEW.product_id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

DROP TABLE IF EXISTS `stock_movements`;
CREATE TABLE IF NOT EXISTS `stock_movements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `movement_type` enum('in','out') NOT NULL,
  `quantity` int NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_movement_product` (`product_id`),
  KEY `idx_movement_type` (`movement_type`),
  KEY `idx_movement_ref` (`reference_type`,`reference_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `product_id`, `movement_type`, `quantity`, `reference_type`, `reference_id`, `created_by`, `created_at`) VALUES
(1, 38, 'in', 2, 'return', 32, 1, '2026-02-23 00:24:46'),
(2, 38, 'out', 1, 'sale', 33, 2, '2026-02-23 02:03:05');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE IF NOT EXISTS `suppliers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `contact_person`, `phone`, `email`, `address`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Global Tech Supplies', 'Ahmed Mohamed', '+966-50-123-4567', 'ahmed@globaltech.sa', 'Riyadh, Saudi Arabia', 1, '2026-01-24 00:14:16', '2026-02-13 23:06:45'),
(2, 'Mobile Parts Warehouse', 'Salem Abdullah', '+966-55-987-6543', 'salem@mobileparts.sa', 'Jeddah, Saudi Arabia', 1, '2026-01-24 00:14:16', '2026-02-13 23:06:45'),
(3, 'Electronics Import Co', 'Khalid Omar', '+966-58-456-7890', 'khalid@electronics.sa', 'Dammam, Saudi Arabia', 1, '2026-01-24 00:14:16', '2026-02-13 23:06:45');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('owner','admin','staff') NOT NULL DEFAULT 'staff',
  `branch_id` int DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_branch` (`branch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `name`, `email`, `phone`, `role`, `branch_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'owner', 'owner123', 'System Owner', 'owner@ibs.com', '+1234567890', 'owner', 1, 1, '2026-01-23 22:14:16', '2026-02-13 23:06:45'),
(2, 'admin', 'admin123', 'System Administrator', 'admin@ibs.com', '+1234567891', 'admin', 1, 1, '2026-01-23 22:14:16', '2026-02-13 23:06:45'),
(3, 'staff1', 'staff123', 'Ahmed Hassan', 'ahmed@email.com', '+966-50-111-2222', 'staff', 1, 1, '2026-01-23 22:14:16', '2026-02-13 23:06:45'),
(4, 'staff2', 'staff123', 'Sarah Johnson', 'sarah@email.com', '+966-55-333-4444', 'staff', 1, 1, '2026-01-23 22:14:16', '2026-02-13 23:06:45');

-- --------------------------------------------------------

--
-- Structure for view `sales_summary`
--
DROP TABLE IF EXISTS `sales_summary`;

DROP VIEW IF EXISTS `sales_summary`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `sales_summary`  AS SELECT cast(`s`.`sale_date` as date) AS `sale_date`, count(`s`.`id`) AS `total_sales`, coalesce(sum(`s`.`total_amount`),0) AS `total_revenue`, avg(`s`.`total_amount`) AS `avg_sale_amount`, `b`.`name` AS `branch_name` FROM (`sales` `s` left join `branches` `b` on((`s`.`branch_id` = `b`.`id`))) GROUP BY cast(`s`.`sale_date` as date), `b`.`name` ORDER BY `sale_date` DESC ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `fk_customers_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_products_brand` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_products_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_items`
--
ALTER TABLE `product_items`
  ADD CONSTRAINT `fk_product_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_product_items_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `fk_sales_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_sales_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sales_staff` FOREIGN KEY (`staff_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `fk_sale_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_sale_items_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL;

DELIMITER $$
--
-- Events
--
DROP EVENT IF EXISTS `audit_log_sales`$$
CREATE DEFINER=`root`@`localhost` EVENT `audit_log_sales` ON SCHEDULE EVERY 1 HOUR STARTS '2026-02-14 00:48:37' ON COMPLETION NOT PRESERVE ENABLE DO INSERT INTO payment (description, amount, payment_method, reference_number, category_id, branch_id, entry_date, notes)
    SELECT 
        CONCAT('Sales Audit - ', COUNT(*), ' sales'),
        COALESCE(SUM(total_amount), 0),
        'Audit',
        CONCAT('AUDIT-', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s')),
        4,
        1,
        DATE(NOW()),
        'Automated sales audit log'
    FROM sales
    WHERE DATE(sale_date) = DATE(NOW())$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
