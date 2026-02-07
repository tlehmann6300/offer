-- ============================================
-- CONTENT DATABASE LEGACY (dbs15161271) - DEFINITIVE SCHEMA
-- ============================================
-- This file contains the complete content database schema
-- Database: content_db (dbs15161271)
-- EXCLUDES invoices table (moved to dbs15251284)
-- ============================================

-- ============================================
-- ALUMNI PROFILES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS alumni_profiles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    mobile_phone VARCHAR(50) DEFAULT NULL,
    linkedin_url VARCHAR(255) DEFAULT NULL,
    xing_url VARCHAR(255) DEFAULT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    position VARCHAR(255) DEFAULT NULL,
    company VARCHAR(255) DEFAULT NULL,
    industry VARCHAR(255) DEFAULT NULL,
    study_program VARCHAR(255) DEFAULT NULL,
    semester INT DEFAULT NULL,
    degree VARCHAR(255) DEFAULT NULL,
    graduation_year INT DEFAULT NULL,
    status VARCHAR(50) DEFAULT NULL,
    last_verified_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_reminder_sent_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_last_name (last_name),
    INDEX idx_industry (industry),
    INDEX idx_company (company),
    INDEX idx_position (position)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Extended profile data for all users';

-- ============================================
-- PROJECTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS projects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    manager_id INT UNSIGNED DEFAULT NULL,
    type ENUM('internal', 'external') NOT NULL DEFAULT 'internal',
    status ENUM('draft', 'open', 'assigned', 'running', 'completed', 'archived') 
        NOT NULL DEFAULT 'draft',
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_manager_id (manager_id),
    INDEX idx_type (type),
    INDEX idx_status (status),
    INDEX idx_start_date (start_date)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Project management with type classification';

-- ============================================
-- PROJECT APPLICATIONS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS project_applications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    motivation TEXT DEFAULT NULL,
    status ENUM('pending', 'reviewing', 'accepted', 'rejected') 
        NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_application (project_id, user_id),
    
    INDEX idx_project_id (project_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='User applications to join projects';

-- ============================================
-- PROJECT FILES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS project_files (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    uploaded_by INT UNSIGNED NOT NULL,
    filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    file_type VARCHAR(100) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    
    INDEX idx_project_id (project_id),
    INDEX idx_uploaded_by (uploaded_by)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Project file attachments';

-- ============================================
-- INVENTORY ITEMS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS inventory_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    quantity INT NOT NULL DEFAULT 0,
    total_stock INT NOT NULL DEFAULT 0,
    category VARCHAR(100) DEFAULT NULL,
    location VARCHAR(255) DEFAULT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    easyverein_id INT UNSIGNED NULL UNIQUE,
    last_synced_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_category (category),
    INDEX idx_location (location),
    INDEX idx_easyverein_id (easyverein_id)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Inventory items with EasyVerein integration';

-- ============================================
-- INVENTORY TRANSACTIONS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS inventory_transactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    transaction_type ENUM('checkout', 'return', 'adjustment', 'purchase', 'disposal') 
        NOT NULL,
    quantity_change INT NOT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE,
    
    INDEX idx_item_id (item_id),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Audit log for inventory changes';

-- ============================================
-- INVENTORY RENTALS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS inventory_rentals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    rented_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expected_return_date DATE DEFAULT NULL,
    actual_return_date TIMESTAMP DEFAULT NULL,
    status ENUM('active', 'returned', 'overdue', 'defective') 
        NOT NULL DEFAULT 'active',
    notes TEXT DEFAULT NULL,
    defect_notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE,
    
    INDEX idx_item_id (item_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Active rentals of inventory items';

-- ============================================
-- EVENTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    location VARCHAR(255) DEFAULT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    max_participants INT UNSIGNED DEFAULT NULL,
    status ENUM('draft', 'published', 'cancelled', 'completed') 
        NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_status (status),
    INDEX idx_start_date (start_date)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Events and activities management';

-- ============================================
-- EVENT REGISTRATIONS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS event_registrations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    status ENUM('confirmed', 'cancelled', 'waiting') 
        NOT NULL DEFAULT 'confirmed',
    guests_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
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
-- BLOG POSTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS blog_posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content LONGTEXT NOT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    category ENUM('Allgemein', 'IT', 'Marketing', 'Human Resources', 'Qualitätsmanagement', 'Akquise') 
        NOT NULL DEFAULT 'Allgemein',
    author_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_category (category),
    INDEX idx_author_id (author_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Blog posts with categorization';

-- ============================================
-- BLOG COMMENTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS blog_comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    
    INDEX idx_post_id (post_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='User comments on blog posts';

-- ============================================
-- BLOG LIKES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS blog_likes (
    post_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (post_id, user_id),
    FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='User likes on blog posts';
