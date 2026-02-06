-- ============================================
-- FULL CONTENT DATABASE SCHEMA
-- For fresh system installation
-- ============================================
-- This file creates all tables for content management across all modules.
-- Database: content_db (dbs15161271)
--
-- Modules included:
-- - Inventory Management (categories, locations, items, checkouts)
-- - Events Management (events, registrations)
-- - Project Management (projects, applications, members, files)
-- - Alumni Management (profiles with job info)
-- - Blog (posts, comments, likes)

-- ============================================
-- INVENTORY MODULE
-- ============================================

-- Inventory Categories
CREATE TABLE IF NOT EXISTS inventory_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Inventory item categories';

-- Inventory Locations
CREATE TABLE IF NOT EXISTS inventory_locations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Storage locations for inventory items';

-- Inventory Items
CREATE TABLE IF NOT EXISTS inventory_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    category_id INT UNSIGNED DEFAULT NULL,
    location_id INT UNSIGNED DEFAULT NULL,
    current_stock INT NOT NULL DEFAULT 0,
    min_stock INT NOT NULL DEFAULT 0,
    serial_number VARCHAR(100) DEFAULT NULL,
    unit VARCHAR(50) DEFAULT 'Stück',
    unit_price DECIMAL(10, 2) DEFAULT 0.00,
    image_path VARCHAR(255) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    easyverein_id INT NULL UNIQUE,
    last_synced_at DATETIME DEFAULT NULL,
    is_archived_in_easyverein BOOLEAN NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES inventory_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (location_id) REFERENCES inventory_locations(id) ON DELETE SET NULL,
    INDEX idx_category (category_id),
    INDEX idx_location (location_id),
    INDEX idx_serial_number (serial_number),
    INDEX idx_easyverein_id (easyverein_id)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Inventory items with stock tracking and EasyVerein integration';

-- Inventory Checkouts
CREATE TABLE IF NOT EXISTS inventory_checkouts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL COMMENT 'Links to user_db.users',
    quantity INT NOT NULL DEFAULT 1,
    checkout_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expected_return_date DATETIME DEFAULT NULL,
    returned_date DATETIME DEFAULT NULL,
    status ENUM('checked_out', 'returned', 'overdue', 'lost') NOT NULL DEFAULT 'checked_out',
    notes TEXT DEFAULT NULL,
    last_reminder_sent_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE,
    INDEX idx_item_id (item_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_checkout_date (checkout_date)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Tracks item checkouts and returns';

-- ============================================
-- EVENTS MODULE
-- ============================================

-- Events
CREATE TABLE IF NOT EXISTS events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    location VARCHAR(255) DEFAULT NULL,
    maps_link VARCHAR(255) DEFAULT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    registration_start DATETIME DEFAULT NULL,
    registration_end DATETIME DEFAULT NULL,
    contact_person VARCHAR(100) DEFAULT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    type ENUM('internal', 'external', 'workshop', 'social', 'meeting') NOT NULL DEFAULT 'internal',
    status ENUM('planned', 'open', 'closed', 'running', 'past', 'cancelled') NOT NULL DEFAULT 'planned',
    max_participants INT UNSIGNED DEFAULT NULL,
    needs_helpers BOOLEAN NOT NULL DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_type (type),
    INDEX idx_start_time (start_time),
    INDEX idx_needs_helpers (needs_helpers)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Events and activities';

-- Event Registrations
CREATE TABLE IF NOT EXISTS event_registrations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL COMMENT 'Links to user_db.users',
    registered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('confirmed', 'cancelled', 'waitlist') NOT NULL DEFAULT 'confirmed',
    notes TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    UNIQUE KEY unique_registration (event_id, user_id),
    INDEX idx_event_id (event_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='User registrations for events';

-- ============================================
-- PROJECT MODULE
-- ============================================

-- Projects
CREATE TABLE IF NOT EXISTS projects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    client_name VARCHAR(255) DEFAULT NULL COMMENT 'Sensitive',
    client_contact_details TEXT DEFAULT NULL COMMENT 'Sensitive',
    priority ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
    status ENUM('draft', 'open', 'applying', 'assigned', 'running', 'completed', 'archived') NOT NULL DEFAULT 'draft',
    max_consultants INT UNSIGNED NOT NULL DEFAULT 1,
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    documentation TEXT DEFAULT NULL COMMENT 'Project completion documentation/report',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_start_date (start_date),
    INDEX idx_end_date (end_date)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Projects with client information';

-- Project Members (Assignments)
CREATE TABLE IF NOT EXISTS project_members (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL COMMENT 'Links to user_db.users',
    role ENUM('lead', 'member') NOT NULL DEFAULT 'member',
    assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_member (project_id, user_id),
    INDEX idx_project_id (project_id),
    INDEX idx_user_id (user_id),
    INDEX idx_role (role)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Project team members and their roles';

-- Project Applications
CREATE TABLE IF NOT EXISTS project_applications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL COMMENT 'Links to user_db.users',
    motivation TEXT DEFAULT NULL,
    experience_count INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('pending', 'reviewing', 'accepted', 'rejected') NOT NULL DEFAULT 'pending',
    applied_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_application (project_id, user_id),
    INDEX idx_project_id (project_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='User applications to join projects';

-- Project Files
CREATE TABLE IF NOT EXISTS project_files (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    uploaded_by INT UNSIGNED NOT NULL COMMENT 'Links to user_db.users',
    filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    file_type VARCHAR(100) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    INDEX idx_project_id (project_id),
    INDEX idx_uploaded_by (uploaded_by)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Project-related file attachments';

-- ============================================
-- ALUMNI MODULE
-- ============================================

-- Alumni Profiles
CREATE TABLE IF NOT EXISTS alumni_profiles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE COMMENT 'Links to user_db.users',
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    mobile_phone VARCHAR(50) DEFAULT NULL,
    linkedin_url VARCHAR(255) DEFAULT NULL,
    xing_url VARCHAR(255) DEFAULT NULL,
    industry VARCHAR(255) DEFAULT NULL COMMENT 'Branche - for alumni filtering',
    company VARCHAR(255) DEFAULT NULL COMMENT 'Aktueller Arbeitgeber - required for alumni, optional for candidates/members',
    position VARCHAR(255) DEFAULT NULL COMMENT 'Job position - required for alumni, optional for candidates/members',
    studiengang VARCHAR(255) DEFAULT NULL COMMENT 'Field of study for candidates and members',
    semester VARCHAR(50) DEFAULT NULL COMMENT 'Current semester for candidates and members',
    angestrebter_abschluss VARCHAR(255) DEFAULT NULL COMMENT 'Desired degree for candidates and members',
    about_me TEXT DEFAULT NULL COMMENT 'Personal description/bio for all users',
    image_path VARCHAR(255) DEFAULT NULL,
    last_verified_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_reminder_sent_at DATETIME DEFAULT NULL COMMENT 'Tracks when annual reminder email was sent',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_industry (industry),
    INDEX idx_company (company),
    INDEX idx_last_name (last_name)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Extended profile data for all users (Alumni, Board, Members, Candidates)';

-- ============================================
-- BLOG MODULE
-- ============================================

-- Blog Posts
CREATE TABLE IF NOT EXISTS blog_posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content LONGTEXT NOT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    external_link VARCHAR(255) DEFAULT NULL,
    category ENUM('Allgemein', 'IT', 'Marketing', 'Human Resources', 'Qualitätsmanagement', 'Akquise') NOT NULL,
    author_id INT UNSIGNED NOT NULL COMMENT 'Links to user_db.users',
    published_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_author_id (author_id),
    INDEX idx_published_at (published_at)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Blog posts with categorization';

-- Blog Comments
CREATE TABLE IF NOT EXISTS blog_comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL COMMENT 'Links to user_db.users',
    content TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    INDEX idx_post_id (post_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Comments on blog posts';

-- Blog Likes
CREATE TABLE IF NOT EXISTS blog_likes (
    post_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL COMMENT 'Links to user_db.users',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (post_id, user_id),
    FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='User likes on blog posts';

-- ============================================
-- DEFAULT DATA
-- ============================================

-- Insert default inventory categories
INSERT IGNORE INTO inventory_categories (name, description) VALUES 
('Technik', 'Computer, Bildschirme, Peripherie'),
('Büromaterial', 'Stifte, Papier, Ordner'),
('Event-Zubehör', 'Equipment für Events');

-- Insert default inventory locations
INSERT IGNORE INTO inventory_locations (name, description) VALUES 
('Furtwangen H-Bau -1.87', 'Lagerraum Furtwangen H-Bau -1.87'),
('Furtwangen H-Bau -1.88', 'Lagerraum Furtwangen H-Bau -1.88');
