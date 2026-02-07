-- ============================================
-- FULL CONTENT DATABASE SCHEMA
-- For fresh system installation (Production Reset)
-- ============================================
-- This file creates all tables for content management across all modules.
-- Database: content_db (dbs15161271)
--
-- Modules included:
-- - Alumni Management (profiles with job and education info)
-- - Events Management (events, registrations with guest tracking)
-- - Project Management (projects with manager, applications, files)
-- - Inventory Management (items with EasyVerein sync, transactions, rentals)
-- - Blog Module (posts, comments, likes)

-- ============================================
-- ALUMNI MODULE
-- ============================================

-- Alumni Profiles
-- Extended profile information for all user types
-- Includes career information, education details, and contact info
CREATE TABLE IF NOT EXISTS alumni_profiles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE COMMENT 'Links to user_db.users table',
    
    -- Basic contact information
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    mobile_phone VARCHAR(50) DEFAULT NULL,
    
    -- Social media links
    linkedin_url VARCHAR(255) DEFAULT NULL COMMENT 'LinkedIn profile URL',
    xing_url VARCHAR(255) DEFAULT NULL COMMENT 'Xing profile URL',
    
    -- Profile image
    image_path VARCHAR(255) DEFAULT NULL,
    
    -- Career information (primarily for alumni)
    position VARCHAR(255) DEFAULT NULL COMMENT 'Current job position',
    company VARCHAR(255) DEFAULT NULL COMMENT 'Current employer',
    industry VARCHAR(255) DEFAULT NULL COMMENT 'Industry/Branche for filtering',
    
    -- Education information (for members and candidates)
    study_program VARCHAR(255) DEFAULT NULL COMMENT 'Field of study (Studiengang)',
    semester INT DEFAULT NULL COMMENT 'Current semester number',
    degree VARCHAR(255) DEFAULT NULL COMMENT 'Desired/achieved degree (Angestrebter Abschluss)',
    graduation_year INT DEFAULT NULL COMMENT 'Year of graduation',
    
    -- Status and verification
    status VARCHAR(50) DEFAULT NULL COMMENT 'Profile status',
    last_verified_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Last verification date',
    last_reminder_sent_at DATETIME DEFAULT NULL COMMENT 'Last annual reminder sent to alumni',
    
    -- Audit timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for performance
    INDEX idx_user_id (user_id),
    INDEX idx_last_name (last_name),
    INDEX idx_industry (industry),
    INDEX idx_company (company)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Extended profile data for all users (Alumni, Board, Members, Candidates)';

-- ============================================
-- EVENTS MODULE
-- ============================================

-- Events
-- Main events table supporting internal and external events
CREATE TABLE IF NOT EXISTS events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    
    -- Date and time
    start_date DATETIME NOT NULL COMMENT 'Event start date and time',
    end_date DATETIME NOT NULL COMMENT 'Event end date and time',
    
    -- Location information
    location VARCHAR(255) DEFAULT NULL,
    
    -- Image
    image_path VARCHAR(255) DEFAULT NULL,
    
    -- Participation limits
    max_participants INT UNSIGNED DEFAULT NULL COMMENT 'Maximum number of participants',
    
    -- Event status
    -- draft: Being planned
    -- published: Open for registration
    -- cancelled: Event cancelled
    -- completed: Event finished
    status ENUM('draft', 'published', 'cancelled', 'completed') 
        NOT NULL DEFAULT 'draft',
    
    -- Audit timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for performance
    INDEX idx_status (status),
    INDEX idx_start_date (start_date)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Events and activities management';

-- Event Registrations
-- Tracks user registrations for events with guest support
CREATE TABLE IF NOT EXISTS event_registrations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL COMMENT 'Links to user_db.users',
    
    -- Registration status
    -- confirmed: Registration confirmed
    -- cancelled: Registration cancelled
    -- waiting: On waitlist
    status ENUM('confirmed', 'cancelled', 'waiting') 
        NOT NULL DEFAULT 'confirmed',
    
    -- Guest support
    guests_count INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Number of additional guests',
    
    -- Audit timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign key to events table
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    
    -- Unique constraint: one registration per user per event
    UNIQUE KEY unique_registration (event_id, user_id),
    
    -- Indexes for performance
    INDEX idx_event_id (event_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='User registrations for events with guest tracking';

-- ============================================
-- PROJECT MODULE
-- ============================================

-- Projects
-- Project management with client information and type classification
CREATE TABLE IF NOT EXISTS projects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    
    -- Project manager
    manager_id INT UNSIGNED DEFAULT NULL COMMENT 'Links to user_db.users - Project manager',
    
    -- Project classification
    -- internal: Internal IBC project
    -- external: Client project
    type ENUM('internal', 'external') NOT NULL DEFAULT 'internal',
    
    -- Project status
    -- draft: Being drafted
    -- open: Open for applications
    -- assigned: Team assigned
    -- running: In progress
    -- completed: Finished
    -- archived: Archived
    status ENUM('draft', 'open', 'assigned', 'running', 'completed', 'archived') 
        NOT NULL DEFAULT 'draft',
    
    -- Date tracking
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    
    -- Audit timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for performance
    INDEX idx_manager_id (manager_id),
    INDEX idx_type (type),
    INDEX idx_status (status),
    INDEX idx_start_date (start_date)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Project management with type classification';

-- Project Applications
-- User applications to join projects
CREATE TABLE IF NOT EXISTS project_applications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL COMMENT 'Links to user_db.users',
    motivation TEXT DEFAULT NULL COMMENT 'Application motivation text',
    
    -- Application status
    -- pending: Awaiting review
    -- reviewing: Under review
    -- accepted: Application accepted
    -- rejected: Application rejected
    status ENUM('pending', 'reviewing', 'accepted', 'rejected') 
        NOT NULL DEFAULT 'pending',
    
    -- Audit timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign key to projects table
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    
    -- Unique constraint: one application per user per project
    UNIQUE KEY unique_application (project_id, user_id),
    
    -- Indexes for performance
    INDEX idx_project_id (project_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='User applications to join projects';

-- Project Files
-- File attachments for projects
CREATE TABLE IF NOT EXISTS project_files (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    uploaded_by INT UNSIGNED NOT NULL COMMENT 'Links to user_db.users',
    filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size INT UNSIGNED NOT NULL COMMENT 'File size in bytes',
    file_type VARCHAR(100) DEFAULT NULL COMMENT 'MIME type',
    description TEXT DEFAULT NULL,
    
    -- Audit timestamp
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign key to projects table
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    
    -- Indexes for performance
    INDEX idx_project_id (project_id),
    INDEX idx_uploaded_by (uploaded_by)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Project-related file attachments';

-- ============================================
-- INVENTORY MODULE
-- ============================================

-- Inventory Items
-- Equipment and materials tracking with EasyVerein integration
CREATE TABLE IF NOT EXISTS inventory_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    
    -- Stock tracking
    quantity INT NOT NULL DEFAULT 0 COMMENT 'Current available quantity',
    total_stock INT NOT NULL DEFAULT 0 COMMENT 'Total quantity owned',
    
    -- Categorization
    category VARCHAR(100) DEFAULT NULL,
    location VARCHAR(255) DEFAULT NULL,
    
    -- Image
    image_path VARCHAR(255) DEFAULT NULL,
    
    -- EasyVerein integration
    easyverein_id INT UNSIGNED NULL UNIQUE COMMENT 'ID from EasyVerein sync',
    last_synced_at DATETIME DEFAULT NULL COMMENT 'Last sync with EasyVerein',
    
    -- Audit timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for performance
    INDEX idx_category (category),
    INDEX idx_location (location),
    INDEX idx_easyverein_id (easyverein_id)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Inventory items with stock tracking and EasyVerein integration';

-- Inventory Transactions
-- Audit log for all inventory changes
CREATE TABLE IF NOT EXISTS inventory_transactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL COMMENT 'Links to user_db.users - User who made the transaction',
    
    -- Transaction details
    transaction_type ENUM('checkout', 'return', 'adjustment', 'purchase', 'disposal') 
        NOT NULL,
    quantity_change INT NOT NULL COMMENT 'Positive for increase, negative for decrease',
    notes TEXT DEFAULT NULL,
    
    -- Audit timestamp
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign key to inventory_items table
    FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE,
    
    -- Indexes for performance
    INDEX idx_item_id (item_id),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Audit log for all inventory changes';

-- Rentals
-- Active rentals of inventory items
CREATE TABLE IF NOT EXISTS rentals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL COMMENT 'Links to user_db.users - User who rented the item',
    
    -- Rental details
    quantity INT NOT NULL DEFAULT 1,
    rented_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expected_return_date DATE DEFAULT NULL,
    actual_return_date TIMESTAMP DEFAULT NULL,
    
    -- Rental status
    -- active: Currently rented
    -- returned: Returned successfully
    -- overdue: Past expected return date
    -- defective: Returned with defects
    status ENUM('active', 'returned', 'overdue', 'defective') 
        NOT NULL DEFAULT 'active',
    
    -- Notes
    notes TEXT DEFAULT NULL COMMENT 'General notes',
    defect_notes TEXT DEFAULT NULL COMMENT 'Notes about defects if status is defective',
    
    -- Audit timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign key to inventory_items table
    FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE,
    
    -- Indexes for performance
    INDEX idx_item_id (item_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Active rentals of inventory items';

-- ============================================
-- BLOG MODULE
-- ============================================

-- Blog Posts
-- Blog content with categorization and author tracking
CREATE TABLE IF NOT EXISTS blog_posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content LONGTEXT NOT NULL,
    
    -- Media
    image_path VARCHAR(255) DEFAULT NULL,
    
    -- Categorization
    category ENUM('Allgemein', 'IT', 'Marketing', 'Human Resources', 'Qualitätsmanagement', 'Akquise') 
        NOT NULL DEFAULT 'Allgemein',
    
    -- Author tracking
    author_id INT UNSIGNED NOT NULL COMMENT 'Links to user_db.users',
    
    -- Audit timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for performance
    INDEX idx_category (category),
    INDEX idx_author_id (author_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Blog posts with categorization and author tracking';

-- Blog Comments
-- User comments on blog posts
CREATE TABLE IF NOT EXISTS blog_comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL COMMENT 'Links to user_db.users - Comment author',
    content TEXT NOT NULL,
    
    -- Audit timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign key to blog_posts table
    FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    
    -- Indexes for performance
    INDEX idx_post_id (post_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='User comments on blog posts';

-- Blog Likes
-- User likes on blog posts
CREATE TABLE IF NOT EXISTS blog_likes (
    post_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL COMMENT 'Links to user_db.users',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Composite primary key prevents duplicate likes
    PRIMARY KEY (post_id, user_id),
    
    -- Foreign key to blog_posts table
    FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    
    -- Index for user's likes
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='User likes on blog posts - composite primary key prevents duplicate likes';

-- ============================================
-- NOTES FOR DEPLOYMENT
-- ============================================
-- 
-- 1. This schema assumes the user_db database exists with the users table
--    See full_user_schema.sql for the user database schema
--
-- 2. Alumni profiles are stored in the content database but reference
--    users from the user database via user_id
--
-- 3. All user_id columns that reference user_db.users should be manually
--    validated in your application code, as cross-database foreign keys
--    are not enforced by MySQL
--
-- 4. EasyVerein integration:
--    - inventory_items.easyverein_id stores the external system ID
--    - last_synced_at tracks when the item was last synchronized
--
-- 5. All tables use InnoDB engine for:
--    - Transaction support
--    - Foreign key constraints
--    - Better crash recovery
--
-- 6. All tables use utf8mb4 character set for:
--    - Full Unicode support including emoji
--    - International character support
