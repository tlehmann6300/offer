-- ============================================
-- PRODUCTION CONTENT DATABASE SCHEMA
-- ============================================
-- SQL Schema for Production Content Database
-- This file defines the complete schema for the Content Database
-- with all required tables and proper indexing
--
-- Database: Content DB (Production)
-- MySQL 8.0+ compatible
-- ============================================

-- ============================================
-- INVOICE MANAGEMENT SYSTEM (PRIORITY)
-- ============================================

-- Main invoices table for receipt tracking and approval workflow
CREATE TABLE IF NOT EXISTS invoices (
    -- Primary identifier
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- User reference (links to User DB)
    user_id INT UNSIGNED NOT NULL COMMENT 'Foreign key to users table in User DB',
    
    -- Invoice details
    description VARCHAR(255) NOT NULL COMMENT 'Short description of the expense purpose',
    amount DECIMAL(10,2) NOT NULL COMMENT 'Invoice amount in EUR',
    date_of_receipt DATE NOT NULL COMMENT 'Date when the receipt was received',
    
    -- File attachment
    file_path VARCHAR(255) DEFAULT NULL COMMENT 'Path to uploaded receipt image/PDF file',
    
    -- Status workflow: pending -> approved/rejected -> paid
    status ENUM('pending', 'approved', 'rejected', 'paid') 
        NOT NULL DEFAULT 'pending' 
        COMMENT 'Invoice processing status in workflow',
    
    -- Rejection handling
    rejection_reason TEXT DEFAULT NULL COMMENT 'Detailed reason if status is rejected',
    
    -- Audit timestamps
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Invoice creation timestamp',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
    
    -- Indexes for performance optimization
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_date_of_receipt (date_of_receipt),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Invoice management system for receipt tracking and approval workflow';

-- ============================================
-- ALUMNI PROFILES (WITH STUDENT FIELDS)
-- ============================================

-- Extended profile information for all user types
-- Includes career information, education details, and contact info
CREATE TABLE IF NOT EXISTS alumni_profiles (
    -- Primary identifier
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- User reference (links to User DB)
    user_id INT UNSIGNED NOT NULL UNIQUE COMMENT 'Links to users table in User DB',
    
    -- Basic contact information
    first_name VARCHAR(255) NOT NULL COMMENT 'User first name',
    last_name VARCHAR(255) NOT NULL COMMENT 'User last name',
    email VARCHAR(255) NOT NULL COMMENT 'User email address',
    mobile_phone VARCHAR(50) DEFAULT NULL COMMENT 'Mobile phone number',
    
    -- Social media profiles
    linkedin_url VARCHAR(255) DEFAULT NULL COMMENT 'LinkedIn profile URL',
    xing_url VARCHAR(255) DEFAULT NULL COMMENT 'Xing profile URL',
    
    -- Profile image
    image_path VARCHAR(255) DEFAULT NULL COMMENT 'Path to profile image',
    
    -- Career information (primarily for alumni)
    position VARCHAR(255) DEFAULT NULL COMMENT 'Current job position',
    company VARCHAR(255) DEFAULT NULL COMMENT 'Current employer company name',
    industry VARCHAR(255) DEFAULT NULL COMMENT 'Industry/Branche for filtering',
    
    -- Student/Education information (for members and candidates)
    study_program VARCHAR(255) DEFAULT NULL COMMENT 'Field of study (Studiengang)',
    semester INT DEFAULT NULL COMMENT 'Current semester number',
    degree VARCHAR(255) DEFAULT NULL COMMENT 'Desired or achieved degree (Angestrebter Abschluss)',
    graduation_year INT DEFAULT NULL COMMENT 'Year of graduation',
    
    -- Personal description
    about_me TEXT DEFAULT NULL COMMENT 'Personal bio/description for all users',
    
    -- Status tracking
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
    INDEX idx_company (company),
    INDEX idx_position (position)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Extended profile data for all users (Alumni, Board, Members, Candidates) with student fields';

-- ============================================
-- PROJECT MANAGEMENT (WITH TYPE CLASSIFICATION)
-- ============================================

-- Projects table with internal/external type classification
CREATE TABLE IF NOT EXISTS projects (
    -- Primary identifier
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- Project information
    title VARCHAR(255) NOT NULL COMMENT 'Project title',
    description TEXT DEFAULT NULL COMMENT 'Detailed project description',
    
    -- Project manager
    manager_id INT UNSIGNED DEFAULT NULL COMMENT 'Links to User DB users - Project manager',
    
    -- Project classification
    -- internal: Internal IBC project
    -- external: External client project
    type ENUM('internal', 'external') 
        NOT NULL DEFAULT 'internal'
        COMMENT 'Project type classification',
    
    -- Project lifecycle status
    -- draft: Being drafted
    -- open: Open for applications
    -- assigned: Team assigned
    -- running: In progress
    -- completed: Finished
    -- archived: Archived
    status ENUM('draft', 'open', 'assigned', 'running', 'completed', 'archived') 
        NOT NULL DEFAULT 'draft'
        COMMENT 'Current project status',
    
    -- Date tracking
    start_date DATE DEFAULT NULL COMMENT 'Project start date',
    end_date DATE DEFAULT NULL COMMENT 'Project end date',
    
    -- Additional media/files
    image_path VARCHAR(255) DEFAULT NULL COMMENT 'Path to project image',
    file_path VARCHAR(255) DEFAULT NULL COMMENT 'Path to project files',
    
    -- Documentation
    documentation TEXT DEFAULT NULL COMMENT 'Project completion documentation/report',
    
    -- Client information (sensitive, primarily for external projects)
    client_name VARCHAR(255) DEFAULT NULL COMMENT 'Client name (sensitive)',
    client_contact_details TEXT DEFAULT NULL COMMENT 'Client contact details (sensitive)',
    
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
  COMMENT='Project management with internal/external type classification';

-- Project Applications
-- User applications to join projects
CREATE TABLE IF NOT EXISTS project_applications (
    -- Primary identifier
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- References
    project_id INT UNSIGNED NOT NULL COMMENT 'Links to projects table',
    user_id INT UNSIGNED NOT NULL COMMENT 'Links to User DB users',
    
    -- Application details
    motivation TEXT DEFAULT NULL COMMENT 'Application motivation text',
    
    -- Application status
    -- pending: Awaiting review
    -- reviewing: Under review
    -- accepted: Application accepted
    -- rejected: Application rejected
    status ENUM('pending', 'reviewing', 'accepted', 'rejected') 
        NOT NULL DEFAULT 'pending'
        COMMENT 'Application review status',
    
    -- Audit timestamp
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

-- ============================================
-- INVENTORY MANAGEMENT (WITH IMAGE SUPPORT)
-- ============================================

-- Inventory Items table with image support and stock tracking
CREATE TABLE IF NOT EXISTS inventory_items (
    -- Primary identifier
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- Item information
    name VARCHAR(255) NOT NULL COMMENT 'Item name',
    description TEXT DEFAULT NULL COMMENT 'Detailed item description',
    
    -- Stock tracking
    quantity INT NOT NULL DEFAULT 0 COMMENT 'Current available quantity',
    total_stock INT NOT NULL DEFAULT 0 COMMENT 'Total quantity owned',
    
    -- Categorization
    category VARCHAR(100) DEFAULT NULL COMMENT 'Item category',
    location VARCHAR(255) DEFAULT NULL COMMENT 'Physical location of item',
    
    -- Image support
    image_path VARCHAR(255) DEFAULT NULL COMMENT 'Path to item image',
    
    -- EasyVerein integration support
    easyverein_id INT UNSIGNED NULL UNIQUE COMMENT 'ID from EasyVerein sync',
    last_synced_at DATETIME DEFAULT NULL COMMENT 'Last sync timestamp with EasyVerein',
    
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
  COMMENT='Inventory items with image support, stock tracking and EasyVerein integration';

-- Inventory Transactions
-- Audit log for all inventory changes
CREATE TABLE IF NOT EXISTS inventory_transactions (
    -- Primary identifier
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- References
    item_id INT UNSIGNED NOT NULL COMMENT 'Links to inventory_items table',
    user_id INT UNSIGNED NOT NULL COMMENT 'Links to User DB users - User who made the transaction',
    
    -- Transaction details
    transaction_type ENUM('checkout', 'return', 'adjustment', 'purchase', 'disposal') 
        NOT NULL
        COMMENT 'Type of inventory transaction',
    quantity_change INT NOT NULL COMMENT 'Quantity change (positive for increase, negative for decrease)',
    notes TEXT DEFAULT NULL COMMENT 'Transaction notes',
    
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

-- ============================================
-- EVENT MANAGEMENT
-- ============================================

-- Events table for managing events and activities
CREATE TABLE IF NOT EXISTS events (
    -- Primary identifier
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- Event information
    title VARCHAR(255) NOT NULL COMMENT 'Event title',
    description TEXT DEFAULT NULL COMMENT 'Detailed event description',
    
    -- Date and time
    start_date DATETIME NOT NULL COMMENT 'Event start date and time',
    end_date DATETIME NOT NULL COMMENT 'Event end date and time',
    
    -- Location information
    location VARCHAR(255) DEFAULT NULL COMMENT 'Event location address',
    maps_link VARCHAR(255) DEFAULT NULL COMMENT 'Google Maps or location link',
    
    -- Event media
    image_path VARCHAR(255) DEFAULT NULL COMMENT 'Path to event image',
    
    -- Participation management
    max_participants INT UNSIGNED DEFAULT NULL COMMENT 'Maximum number of participants allowed',
    contact_person VARCHAR(100) DEFAULT NULL COMMENT 'Event contact person name',
    
    -- Event status
    -- draft: Being planned
    -- published: Open for registration
    -- cancelled: Event cancelled
    -- completed: Event finished
    status ENUM('draft', 'published', 'cancelled', 'completed') 
        NOT NULL DEFAULT 'draft'
        COMMENT 'Current event status',
    
    -- External event support
    is_external BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'True if external event',
    external_link VARCHAR(255) DEFAULT NULL COMMENT 'Link to external event details',
    
    -- Audit timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for performance
    INDEX idx_status (status),
    INDEX idx_start_date (start_date)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Events and activities management with registration support';

-- ============================================
-- EVENT REGISTRATIONS
-- ============================================

-- Event Registrations table for tracking user registrations
CREATE TABLE IF NOT EXISTS event_registrations (
    -- Primary identifier
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- References
    event_id INT UNSIGNED NOT NULL COMMENT 'Links to events table',
    user_id INT UNSIGNED NOT NULL COMMENT 'Links to User DB users',
    
    -- Registration status
    -- confirmed: Registration confirmed
    -- cancelled: Registration cancelled
    -- waiting: On waitlist
    status ENUM('confirmed', 'cancelled', 'waiting') 
        NOT NULL DEFAULT 'confirmed'
        COMMENT 'Registration status',
    
    -- Guest support
    guests_count INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Number of additional guests',
    
    -- Registration timestamp
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Registration timestamp',
    
    -- Audit timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
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
  COMMENT='User registrations for events with guest tracking and status management';

-- ============================================
-- NOTES
-- ============================================
-- 
-- Schema Features:
-- 1. All tables use CREATE TABLE IF NOT EXISTS for idempotent execution
-- 2. Proper indexing for performance optimization
-- 3. Comprehensive comments for documentation
-- 4. Foreign key constraints where appropriate
-- 5. UTF8MB4 charset for full Unicode support (including emojis)
-- 6. InnoDB engine for ACID compliance and foreign key support
--
-- Priority Implementation:
-- - Invoices table is fully defined with all required fields
-- - Support for invoice workflow: pending -> approved/rejected -> paid
-- - rejection_reason field for detailed rejection feedback
--
-- Content Tables:
-- - alumni_profiles: Extended profiles with student/career fields
-- - projects: Project management with internal/external type classification
-- - inventory_items: Inventory with image_path and EasyVerein integration
-- - events: Event management with external event support
-- - event_registrations: User registrations with guest support
--
-- Database Configuration:
-- - Ensure proper User DB connection for user_id foreign key references
-- - Create uploads/invoices/ directory with proper permissions
-- - Configure file upload limits in php.ini as needed
-- 
-- ============================================
