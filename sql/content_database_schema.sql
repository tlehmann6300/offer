-- Content Database Schema (dbs15161271)
-- Inventory, Rentals, Events, System Logs
-- Optimized version with consolidated migrations (Checkout system, Rentals, Events)

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Locations table (includes H-1.87 and H-1.88)
CREATE TABLE IF NOT EXISTS locations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    address TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inventory table
CREATE TABLE IF NOT EXISTS inventory (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    category_id INT UNSIGNED DEFAULT NULL,
    location_id INT UNSIGNED DEFAULT NULL,
    current_stock INT NOT NULL DEFAULT 0,
    min_stock INT NOT NULL DEFAULT 0,
    unit VARCHAR(50) DEFAULT 'Stück',
    unit_price DECIMAL(10, 2) DEFAULT 0.00,
    image_path VARCHAR(255) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL,
    INDEX idx_category (category_id),
    INDEX idx_location (location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inventory history table (audit log) - optimized with TEXT
CREATE TABLE IF NOT EXISTS inventory_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    change_type ENUM('adjustment', 'create', 'update', 'delete', 'checkout', 'checkin', 'writeoff') NOT NULL DEFAULT 'adjustment',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- RENTALS TABLE
-- ============================================

-- Rentals table (simplified rental tracking)
CREATE TABLE IF NOT EXISTS rentals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    item_id INT UNSIGNED NOT NULL,
    amount INT NOT NULL,
    rented_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expected_return DATE DEFAULT NULL,
    actual_return TIMESTAMP DEFAULT NULL,
    status ENUM('active', 'returned', 'overdue', 'defective') NOT NULL DEFAULT 'active',
    defect_notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES inventory(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_item_id (item_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- EVENT MANAGEMENT TABLES
-- ============================================

-- Events table (main table for all events)
CREATE TABLE IF NOT EXISTS events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    location VARCHAR(100) DEFAULT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    contact_person VARCHAR(100) DEFAULT NULL,
    status ENUM('planned', 'open', 'closed', 'running', 'past') NOT NULL DEFAULT 'planned',
    is_external TINYINT(1) NOT NULL DEFAULT 0,
    external_link VARCHAR(255) DEFAULT NULL,
    needs_helpers TINYINT(1) NOT NULL DEFAULT 0,
    locked_by INT UNSIGNED DEFAULT NULL,
    locked_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_start_time (start_time),
    INDEX idx_needs_helpers (needs_helpers)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Event roles table (links events with allowed roles)
CREATE TABLE IF NOT EXISTS event_roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    role VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    UNIQUE KEY unique_event_role (event_id, role),
    INDEX idx_event_id (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Event helper types table (defines types of helpers needed)
CREATE TABLE IF NOT EXISTS event_helper_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    INDEX idx_event_id (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Event slots table (time slots for specific helper types)
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Event signups table (tracks user registrations)
CREATE TABLE IF NOT EXISTS event_signups (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    slot_id INT UNSIGNED DEFAULT NULL,
    status ENUM('confirmed', 'waitlist', 'cancelled') NOT NULL DEFAULT 'confirmed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (slot_id) REFERENCES event_slots(id) ON DELETE SET NULL,
    UNIQUE KEY unique_signup (event_id, user_id, slot_id),
    INDEX idx_event_id (event_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Event history table (audit log for events) - optimized with TEXT
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SYSTEM LOGS TABLE
-- ============================================

-- System logs table
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DEFAULT DATA
-- ============================================

-- Insert initial categories
INSERT IGNORE INTO categories (name, description, color) VALUES 
('Technik', 'Computer, Bildschirme, Peripherie', '#8B5CF6'),
('Büromaterial', 'Stifte, Papier, Ordner', '#F59E0B'),
('Event-Zubehör', 'Equipment für Events', '#EF4444');

-- Insert initial locations
INSERT IGNORE INTO locations (name, description) VALUES 
('Furtwangen H-Bau -1.87', 'Lagerraum Furtwangen H-Bau -1.87'),
('Furtwangen H-Bau -1.88', 'Lagerraum Furtwangen H-Bau -1.88');

-- ============================================
-- OPTIONAL: AUTOMATED CLEANUP PROCEDURE
-- ============================================
-- This procedure deletes old log entries from inventory_history and event_history
-- that are older than 2 years. Uncomment and adjust as needed.

/*
DELIMITER $$

CREATE PROCEDURE cleanup_old_logs()
BEGIN
    -- Delete inventory_history entries older than 2 years
    DELETE FROM inventory_history 
    WHERE timestamp < DATE_SUB(NOW(), INTERVAL 2 YEAR);
    
    -- Delete event_history entries older than 2 years
    DELETE FROM event_history 
    WHERE timestamp < DATE_SUB(NOW(), INTERVAL 2 YEAR);
    
    -- Log the cleanup action
    INSERT INTO system_logs (action, details, timestamp) 
    VALUES ('cleanup_old_logs', CONCAT('Deleted old history entries. Timestamp: ', NOW()), NOW());
END$$

DELIMITER ;

-- To schedule this procedure to run automatically, create an event:
-- Note: Make sure the event scheduler is enabled: SET GLOBAL event_scheduler = ON;

/*
CREATE EVENT IF NOT EXISTS cleanup_old_logs_event
ON SCHEDULE EVERY 1 MONTH
STARTS CURRENT_TIMESTAMP
DO
    CALL cleanup_old_logs();
*/
