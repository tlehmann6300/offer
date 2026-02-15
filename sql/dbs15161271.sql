-- ================================================
-- Content Database Setup Script (dbs15161271)
-- ================================================
-- This database handles: Events, Projects, Blog Posts, 
-- Inventory, Polls, Alumni Profiles, Event Documentation
-- ================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- ================================================
-- TABLE: events
-- ================================================
CREATE TABLE IF NOT EXISTS `events` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `event_date` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` INT UNSIGNED NOT NULL,
  INDEX `idx_event_date` (`event_date`),
  INDEX `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- TABLE: event_documentation
-- ================================================
CREATE TABLE IF NOT EXISTS `event_documentation` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `event_id` INT UNSIGNED NOT NULL,
  `documentation` TEXT,
  `sellers_data` JSON DEFAULT NULL COMMENT 'JSON array of seller entries with name, items, quantity, and revenue',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
  INDEX `idx_event_id` (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- TABLE: event_financial_stats
-- ================================================
CREATE TABLE IF NOT EXISTS `event_financial_stats` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `event_id` INT UNSIGNED NOT NULL,
  `category` ENUM('Verkauf', 'Kalkulation') NOT NULL COMMENT 'Category: Sales or Calculation',
  `item_name` VARCHAR(255) NOT NULL COMMENT 'Item name, e.g., Brezeln, Äpfel, Grillstand',
  `quantity` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Quantity sold or calculated',
  `revenue` DECIMAL(10, 2) DEFAULT NULL COMMENT 'Revenue in EUR (optional for calculations)',
  `record_year` YEAR NOT NULL COMMENT 'Year of record for historical comparison',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` INT UNSIGNED NOT NULL COMMENT 'User who created the record',
  FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
  INDEX `idx_event_id` (`event_id`),
  INDEX `idx_category` (`category`),
  INDEX `idx_record_year` (`record_year`),
  INDEX `idx_event_year` (`event_id`, `record_year`),
  INDEX `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Financial statistics for events - tracks sales and calculations with yearly comparison';

-- ================================================
-- TABLE: alumni_profiles
-- ================================================
CREATE TABLE IF NOT EXISTS `alumni_profiles` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `secondary_email` VARCHAR(255) DEFAULT NULL COMMENT 'Optional secondary email address for profile display only',
  `bio` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- TABLE: polls
-- ================================================
CREATE TABLE IF NOT EXISTS `polls` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `microsoft_forms_url` TEXT DEFAULT NULL COMMENT 'Microsoft Forms embed URL or direct link for external survey integration',
  `visible_to_all` BOOLEAN NOT NULL DEFAULT 0 COMMENT 'If true, show poll to all users regardless of roles',
  `is_internal` BOOLEAN NOT NULL DEFAULT 1 COMMENT 'If true, hide poll after user votes. If false (external Forms), show hide button',
  `allowed_roles` JSON DEFAULT NULL COMMENT 'JSON array of Entra roles that can see this poll (filters against user azure_roles)',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` INT UNSIGNED NOT NULL,
  INDEX `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- TABLE: poll_hidden_by_user
-- ================================================
CREATE TABLE IF NOT EXISTS `poll_hidden_by_user` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `poll_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `hidden_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_poll_user` (`poll_id`, `user_id`),
  INDEX `idx_poll_id` (`poll_id`),
  INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tracks which users have manually hidden which polls';

-- ================================================
-- TABLE: system_settings
-- ================================================
CREATE TABLE IF NOT EXISTS `system_settings` (
  `setting_key` VARCHAR(100) PRIMARY KEY,
  `setting_value` TEXT,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` INT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
