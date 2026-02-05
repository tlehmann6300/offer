-- Migration: Add alumni_profiles table
-- This table stores extended profile data for Alumni, Board, and Admins
-- Created: 2026-02-05

CREATE TABLE IF NOT EXISTS alumni_profiles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE COMMENT 'Links to external Auth DB',
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    mobile_phone VARCHAR(50) DEFAULT NULL,
    linkedin_url VARCHAR(255) DEFAULT NULL,
    xing_url VARCHAR(255) DEFAULT NULL,
    industry VARCHAR(255) DEFAULT NULL COMMENT 'For filtering',
    company VARCHAR(255) NOT NULL,
    position VARCHAR(255) NOT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    last_verified_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_industry (industry),
    INDEX idx_company (company),
    INDEX idx_last_name (last_name)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Extended profile data for Alumni, Board, and Admins';
