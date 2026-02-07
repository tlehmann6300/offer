-- ============================================
-- FULL USER DATABASE SCHEMA
-- For fresh system installation (Production Reset)
-- ============================================
-- This file creates all tables needed for user management and authentication.
-- Database: user_db (dbs15253086)
-- 
-- Tables:
-- - users: Core user accounts with authentication, security, and notification preferences
-- - user_invitations: Token-based invitation system with role assignment
-- - user_sessions: Session management with IP and user-agent tracking
-- - alumni_profiles: Extended profile information moved to content_db

-- ============================================
-- USERS TABLE
-- ============================================
-- Core authentication and user management table
-- Supports multiple roles: admin, board, head, member, alumni, candidate
-- Includes security features (TFA, login attempts, account locking)
-- Includes notification preferences for projects and events
CREATE TABLE IF NOT EXISTS users (
    -- Primary identification
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL COMMENT 'Hashed password using Argon2ID',
    
    -- Role-based access control
    -- admin: Full system access
    -- board: Board member with elevated permissions
    -- head: Department/resource head
    -- member: Active member
    -- alumni: Former member with alumni status
    -- candidate: Prospective member applying for membership
    role ENUM('admin', 'board', 'head', 'member', 'alumni', 'candidate') 
        NOT NULL DEFAULT 'member',
    
    -- Security features
    tfa_enabled TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Two-factor authentication enabled',
    failed_login_attempts INT NOT NULL DEFAULT 0 COMMENT 'Counter for failed login attempts',
    locked_until DATETIME DEFAULT NULL COMMENT 'Temporary lock expiration time',
    is_locked_permanently TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Permanent account lock',
    
    -- Notification preferences
    notify_new_projects TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Email notifications for new projects',
    notify_new_events TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Email notifications for new events',
    
    -- Alumni validation
    is_alumni_validated TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Alumni status verification flag',
    
    -- Audit timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for performance
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Core user accounts with authentication, security, and notification preferences';

-- ============================================
-- USER INVITATIONS TABLE
-- ============================================
-- Token-based invitation system for new user registration
-- Allows pre-assigning roles before account creation
CREATE TABLE IF NOT EXISTS user_invitations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL COMMENT 'Email address to invite',
    token VARCHAR(64) NOT NULL UNIQUE COMMENT 'Unique invitation token',
    role ENUM('admin', 'board', 'head', 'member', 'alumni', 'candidate') 
        NOT NULL DEFAULT 'member' COMMENT 'Pre-assigned role for invited user',
    expires_at DATETIME NOT NULL COMMENT 'Invitation expiration date and time',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Indexes for performance
    INDEX idx_email (email),
    INDEX idx_token (token),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Token-based invitation system with role pre-assignment';

-- ============================================
-- USER SESSIONS TABLE
-- ============================================
-- Manages active user sessions with security tracking
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    session_token VARCHAR(64) NOT NULL UNIQUE COMMENT 'Unique session identifier',
    ip_address VARCHAR(45) NOT NULL COMMENT 'IPv4 or IPv6 address',
    user_agent VARCHAR(255) DEFAULT NULL COMMENT 'Browser/client user agent string',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL COMMENT 'Session expiration time',
    
    -- Foreign key to users table
    CONSTRAINT fk_session_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Indexes for performance
    INDEX idx_session_token (session_token),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='User session management with IP and user-agent tracking';

-- ============================================
-- NOTES FOR DEPLOYMENT
-- ============================================
-- 1. Create initial admin user after deployment using:
--    INSERT INTO users (email, password, role)
--    VALUES ('admin@example.com', PASSWORD_HASH_HERE, 'admin');
--    
--    Generate hash in PHP: password_hash('YourPassword', PASSWORD_ARGON2ID);
--
-- 2. Extended user profiles (alumni_profiles) are stored in content_db
--    See full_content_schema.sql for the alumni_profiles table
--
-- 3. Notification defaults:
--    - notify_new_projects: ON (1) by default
--    - notify_new_events: ON (1) by default
--
-- 4. Security features require implementation in application code:
--    - TFA verification
--    - Failed login attempt tracking
--    - Account locking logic
