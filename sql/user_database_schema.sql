-- User Database Schema (dbs15253086)
-- Authentication, User Management, Alumni Profiles
-- Warning-free for MySQL 8.0+

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'board', 'alumni_board', 'manager', 'member', 'alumni')
        NOT NULL DEFAULT 'member',
    tfa_secret VARCHAR(32) DEFAULT NULL,
    tfa_enabled BOOLEAN NOT NULL DEFAULT FALSE,
    is_alumni_validated BOOLEAN NOT NULL DEFAULT FALSE,
    last_login DATETIME DEFAULT NULL,
    failed_login_attempts INT NOT NULL DEFAULT 0,
    locked_until DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- Alumni profiles table
CREATE TABLE IF NOT EXISTS alumni_profiles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE COMMENT 'Links to users table',
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
    last_reminder_sent_at DATETIME DEFAULT NULL COMMENT 'Tracks when the annual reminder email was sent to this alumni',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_alumni_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_industry (industry),
    INDEX idx_company (company),
    INDEX idx_last_name (last_name)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Extended profile data for Alumni, Board, and Admins';

-- Invitation tokens table
CREATE TABLE IF NOT EXISTS invitation_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(64) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL,
    role ENUM('admin', 'board', 'alumni_board', 'manager', 'member', 'alumni', 'candidate')
        NOT NULL DEFAULT 'member',
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    used_at DATETIME DEFAULT NULL,
    used_by INT UNSIGNED DEFAULT NULL,
    INDEX idx_token (token),
    INDEX idx_email (email),
    CONSTRAINT fk_invitation_creator
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- Session management table
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    session_token VARCHAR(64) NOT NULL UNIQUE,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    INDEX idx_session_token (session_token),
    INDEX idx_user_id (user_id),
    CONSTRAINT fk_session_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- IMPORTANT:
-- Create the initial admin user manually after deployment
-- Do NOT store passwords in plaintext
--
-- Example:
-- INSERT INTO users (email, password_hash, role)
-- VALUES ('admin@ibc.de', PASSWORD_HASH_HERE, 'admin');
--
-- PHP:
-- password_hash('YourSecurePassword', PASSWORD_ARGON2ID);
