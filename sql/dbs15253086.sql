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
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('board_finance', 'board_internal', 'board_external', 'alumni_board', 'alumni_auditor', 'alumni', 'honorary_member', 'head', 'member', 'candidate') 
        NOT NULL DEFAULT 'member',
    -- 'BOOLEAN' ersetzt 'TINYINT(1)' um Warnings zu vermeiden
    is_alumni_validated BOOLEAN NOT NULL DEFAULT 0, 
    last_login DATETIME DEFAULT NULL,
    failed_login_attempts INT NOT NULL DEFAULT 0,
    locked_until DATETIME DEFAULT NULL,
    is_locked_permanently BOOLEAN NOT NULL DEFAULT 0,
    tfa_enabled BOOLEAN NOT NULL DEFAULT 0,
    tfa_secret VARCHAR(255) DEFAULT NULL COMMENT 'Two-factor authentication secret for Google Authenticator',
    pending_email_update_request BOOLEAN NOT NULL DEFAULT 0,
    prompt_profile_review BOOLEAN NOT NULL DEFAULT 0,
    theme_preference VARCHAR(10) DEFAULT 'auto',
    birthday DATE DEFAULT NULL COMMENT 'User birthday for birthday wishes',
    gender ENUM('m', 'f', 'd') DEFAULT NULL COMMENT 'User gender: m=male, f=female, d=diverse',
    about_me VARCHAR(400) DEFAULT NULL,
    study_level ENUM('bachelor', 'master') DEFAULT NULL,
    study_program VARCHAR(255) DEFAULT NULL,
    study_status ENUM('current', 'completed') DEFAULT 'current',
    profile_complete BOOLEAN NOT NULL DEFAULT 1 COMMENT 'Flag to track if user has completed initial profile setup (first_name + last_name)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_birthday (birthday),
    INDEX idx_profile_complete (profile_complete)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Core user accounts with authentication and security';

-- ============================================
-- INVITATION TOKENS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS invitation_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(64) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL,
    role ENUM('board_finance', 'board_internal', 'board_external', 'alumni_board', 'alumni_auditor', 'alumni', 'honorary_member', 'head', 'member', 'candidate') 
        NOT NULL DEFAULT 'member',
    created_by INT UNSIGNED DEFAULT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME DEFAULT NULL,
    used_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_invitation_created_by
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_invitation_used_by
        FOREIGN KEY (used_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_email (email),
    INDEX idx_token (token),
    INDEX idx_expires_at (expires_at),
    INDEX idx_created_by (created_by),
    INDEX idx_used_at (used_at),
    INDEX idx_used_by (used_by)
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
    new_email VARCHAR(255) NOT NULL,
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

-- ============================================
-- USER SESSIONS TABLE (OPTIONAL)
-- ============================================
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    session_token VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_session_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_user_id (user_id),
    INDEX idx_session_token (session_token),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Optional session tracking for security auditing';