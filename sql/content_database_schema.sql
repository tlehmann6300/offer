-- Content Database Schema (dbs15161271)
-- Inventory, Rentals, Events, System Logs
-- MySQL 8.0+ compatible (no integer display width warnings)

-- ============================================
-- INVENTORY MANAGEMENT TABLES
-- ============================================

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    color VARCHAR(7) DEFAULT '#3B82F6',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- Locations table
CREATE TABLE IF NOT EXISTS locations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    address TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- Inventory table
CREATE TABLE IF NOT EXISTS inventory (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    easyverein_id INT UNSIGNED NULL UNIQUE COMMENT 'Unique ID from easyVerein sync',
    name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    serial_number VARCHAR(100) DEFAULT NULL,
    category_id INT UNSIGNED DEFAULT NULL,
    location_id INT UNSIGNED DEFAULT NULL COMMENT 'Reference to locations table',
    location VARCHAR(255) DEFAULT NULL COMMENT 'Text location from easyVerein or manual entry',
    status ENUM('available', 'in_use', 'maintenance', 'retired') NOT NULL DEFAULT 'available',
    current_stock INT NOT NULL DEFAULT 0,
    min_stock INT NOT NULL DEFAULT 0,
    unit VARCHAR(50) DEFAULT 'Stück',
    unit_price DECIMAL(10, 2) DEFAULT 0.00,
    purchase_date DATE DEFAULT NULL,
    acquisition_date DATE DEFAULT NULL COMMENT 'Acquisition date from easyVerein',
    value DECIMAL(10, 2) DEFAULT 0.00 COMMENT 'Item value from easyVerein',
    image_path VARCHAR(255) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_synced_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last sync with easyVerein',
    is_archived_in_easyverein BOOLEAN NOT NULL DEFAULT 0,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL,
    INDEX idx_easyverein_id (easyverein_id),
    INDEX idx_category (category_id),
    INDEX idx_location (location_id),
    INDEX idx_serial_number (serial_number)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Inventory with easyVerein integration support';

-- Inventory history (audit log)
CREATE TABLE IF NOT EXISTS inventory_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    change_type ENUM(
        'adjustment', 'create', 'update', 'delete',
        'checkout', 'checkin', 'writeoff'
    ) NOT NULL DEFAULT 'adjustment',
    old_stock INT DEFAULT NULL,
    new_stock INT DEFAULT NULL,
    change_amount INT DEFAULT NULL,
    reason VARCHAR(100) DEFAULT NULL,
    comment TEXT DEFAULT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES inventory(id) ON DELETE CASCADE,
    INDEX idx_item_id (item_id),
    INDEX idx_user_id (user_id),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- RENTALS
-- ============================================

CREATE TABLE IF NOT EXISTS rentals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    item_id INT UNSIGNED NOT NULL,
    amount INT NOT NULL,
    rented_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expected_return DATE DEFAULT NULL,
    actual_return TIMESTAMP DEFAULT NULL,
    status ENUM('active', 'returned', 'overdue', 'defective')
        NOT NULL DEFAULT 'active',
    defect_notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES inventory(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_item_id (item_id),
    INDEX idx_status (status)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- EVENT MANAGEMENT
-- ============================================

-- Events table
CREATE TABLE IF NOT EXISTS events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    location VARCHAR(255) DEFAULT NULL,
    maps_link VARCHAR(255) DEFAULT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    registration_start DATETIME DEFAULT NULL,
    registration_end DATETIME DEFAULT NULL,
    contact_person VARCHAR(100) DEFAULT NULL,
    status ENUM('planned', 'open', 'closed', 'running', 'past')
        NOT NULL DEFAULT 'planned',
    is_external BOOLEAN NOT NULL DEFAULT FALSE,
    external_link VARCHAR(255) DEFAULT NULL,
    needs_helpers BOOLEAN NOT NULL DEFAULT FALSE,
    locked_by INT UNSIGNED DEFAULT NULL,
    locked_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_start_time (start_time),
    INDEX idx_needs_helpers (needs_helpers)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- Event roles
CREATE TABLE IF NOT EXISTS event_roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    role VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    UNIQUE KEY unique_event_role (event_id, role),
    INDEX idx_event_id (event_id)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- Event helper types
CREATE TABLE IF NOT EXISTS event_helper_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    INDEX idx_event_id (event_id)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- Event slots
CREATE TABLE IF NOT EXISTS event_slots (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    helper_type_id INT UNSIGNED NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    quantity_needed INT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (helper_type_id) REFERENCES event_helper_types(id) ON DELETE CASCADE,
    INDEX idx_helper_type_id (helper_type_id),
    INDEX idx_start_time (start_time)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- Event signups
CREATE TABLE IF NOT EXISTS event_signups (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    slot_id INT UNSIGNED DEFAULT NULL,
    status ENUM('confirmed', 'waitlist', 'cancelled')
        NOT NULL DEFAULT 'confirmed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (slot_id) REFERENCES event_slots(id) ON DELETE SET NULL,
    UNIQUE KEY unique_signup (event_id, user_id, slot_id),
    INDEX idx_event_id (event_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- Event history
CREATE TABLE IF NOT EXISTS event_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED DEFAULT NULL,
    change_type VARCHAR(50) NOT NULL,
    change_details TEXT DEFAULT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    INDEX idx_event_id (event_id),
    INDEX idx_user_id (user_id),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- PROJECT MANAGEMENT
-- ============================================

-- Projects table
CREATE TABLE IF NOT EXISTS projects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    client_name VARCHAR(255) DEFAULT NULL COMMENT 'Sensibel',
    client_contact_details TEXT DEFAULT NULL COMMENT 'Sensibel',
    priority ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
    type ENUM('internal', 'external') NOT NULL DEFAULT 'internal',
    status ENUM('draft', 'open', 'applying', 'assigned', 'running', 'completed', 'archived') NOT NULL DEFAULT 'draft',
    max_consultants INT UNSIGNED NOT NULL DEFAULT 1,
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    file_path VARCHAR(255) DEFAULT NULL,
    documentation TEXT DEFAULT NULL COMMENT 'Project completion documentation/report',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_type (type),
    INDEX idx_start_date (start_date),
    INDEX idx_end_date (end_date)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Project management table with sensitive client information';

-- Project applications
CREATE TABLE IF NOT EXISTS project_applications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    motivation TEXT DEFAULT NULL,
    experience_count INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('pending', 'reviewing', 'accepted', 'rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_application (project_id, user_id),
    INDEX idx_project_id (project_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Applications from users to work on projects';

-- Project assignments
CREATE TABLE IF NOT EXISTS project_assignments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    role ENUM('lead', 'member') NOT NULL DEFAULT 'member',
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_assignment (project_id, user_id),
    INDEX idx_project_id (project_id),
    INDEX idx_user_id (user_id),
    INDEX idx_role (role)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Assignments of users to projects with specific roles';

-- ============================================
-- BLOG MODULE
-- ============================================

-- Blog posts table
CREATE TABLE IF NOT EXISTS blog_posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content LONGTEXT NOT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    external_link VARCHAR(255) DEFAULT NULL,
    category ENUM('Allgemein', 'IT', 'Marketing', 'Human Resources', 'Qualitätsmanagement', 'Akquise') NOT NULL,
    author_id INT UNSIGNED NOT NULL COMMENT 'Links to user_db.users',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_author_id (author_id)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Blog posts with categorization and author tracking';

-- Blog comments table
CREATE TABLE IF NOT EXISTS blog_comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL COMMENT 'Author of comment',
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    INDEX idx_post_id (post_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Comments on blog posts';

-- Blog likes table
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
  COMMENT='User likes on blog posts - composite primary key prevents duplicate likes';

-- ============================================
-- EVENT REGISTRATIONS
-- ============================================

-- Event registrations table
CREATE TABLE IF NOT EXISTS event_registrations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('confirmed', 'cancelled') NOT NULL DEFAULT 'confirmed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    UNIQUE KEY unique_registration (event_id, user_id),
    INDEX idx_event_id (event_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Tracks user registrations for events with confirmation status';

-- ============================================
-- SYSTEM LOGS
-- ============================================

CREATE TABLE IF NOT EXISTS system_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) DEFAULT NULL,
    entity_id INT UNSIGNED DEFAULT NULL,
    details TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DEFAULT DATA
-- ============================================

INSERT IGNORE INTO categories (name, description, color) VALUES 
('Technik', 'Computer, Bildschirme, Peripherie', '#8B5CF6'),
('Büromaterial', 'Stifte, Papier, Ordner', '#F59E0B'),
('Event-Zubehör', 'Equipment für Events', '#EF4444');

INSERT IGNORE INTO locations (name, description) VALUES 
('Furtwangen H-Bau -1.87', 'Lagerraum Furtwangen H-Bau -1.87'),
('Furtwangen H-Bau -1.88', 'Lagerraum Furtwangen H-Bau -1.88');
