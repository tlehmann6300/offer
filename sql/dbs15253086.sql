-- ============================================
-- USER DATABASE (dbs15253086) - DEFINITIVE SCHEMA
-- ============================================
-- This file contains the complete user database schema
-- Database: user_db (dbs15253086)
-- ============================================

-- ============================================
-- USERS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('board', 'head', 'member', 'alumni', 'candidate', 'alumni_board') 
        NOT NULL DEFAULT 'member',
    tfa_enabled TINYINT(1) NOT NULL DEFAULT 0,
    failed_login_attempts INT NOT NULL DEFAULT 0,
    locked_until DATETIME DEFAULT NULL,
    is_locked_permanently TINYINT(1) NOT NULL DEFAULT 0,
    is_alumni_validated TINYINT(1) NOT NULL DEFAULT 0,
    pending_email_update_request TINYINT(1) NOT NULL DEFAULT 0,
    prompt_profile_review TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Core user accounts with authentication and security';

-- ============================================
-- USER INVITATIONS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS user_invitations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    role ENUM('board', 'head', 'member', 'alumni', 'candidate', 'alumni_board') 
        NOT NULL DEFAULT 'member',
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_token (token),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Token-based invitation system';

-- ============================================
-- EMAIL CHANGE REQUESTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS email_change_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    new_email VARCHAR(100) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_email_change_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_user_id (user_id),
    INDEX idx_token (token),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Token-based email change verification';
