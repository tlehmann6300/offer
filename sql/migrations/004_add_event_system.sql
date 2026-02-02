-- Migration Script: Add Event & Helper System
-- Creates all tables for the Event & Helper System as per requirements
-- Run this script on the content database

-- ============================================
-- CONTENT DATABASE (dbs15161271) MIGRATION
-- ============================================

USE dbs15161271;

-- ============================================
-- Events Table
-- Main table for all events (internal and external)
-- ============================================
CREATE TABLE IF NOT EXISTS events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    location VARCHAR(255) DEFAULT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    contact_person VARCHAR(255) DEFAULT NULL,
    status ENUM('planned', 'open', 'closed', 'running', 'past') NOT NULL DEFAULT 'planned',
    is_external BOOLEAN NOT NULL DEFAULT FALSE,
    external_link VARCHAR(500) DEFAULT NULL,
    needs_helpers BOOLEAN NOT NULL DEFAULT FALSE,
    locked_by INT UNSIGNED DEFAULT NULL,
    locked_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_start_time (start_time),
    INDEX idx_end_time (end_time),
    INDEX idx_needs_helpers (needs_helpers),
    INDEX idx_locked_by (locked_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Event Roles Table
-- Links events with allowed roles for participation
-- (e.g., member, alumni, board, alumni_board, manager, admin)
-- ============================================
CREATE TABLE IF NOT EXISTS event_roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    role VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    UNIQUE KEY unique_event_role (event_id, role),
    INDEX idx_event_id (event_id),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Event Helper Types Table
-- Defines types of helpers needed for events (e.g., "Aufbau", "Abbau", "Catering")
-- ============================================
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

-- ============================================
-- Event Slots Table
-- Defines time slots for specific helper types with quantity needed
-- ============================================
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
    INDEX idx_start_time (start_time),
    INDEX idx_end_time (end_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Event Signups Table
-- Tracks user registrations for events and helper slots
-- ============================================
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
    INDEX idx_slot_id (slot_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Event History Table
-- Audit log for all changes to events
-- ============================================
CREATE TABLE IF NOT EXISTS event_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED DEFAULT NULL,
    change_type VARCHAR(50) NOT NULL,
    change_details JSON DEFAULT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    INDEX idx_event_id (event_id),
    INDEX idx_user_id (user_id),
    INDEX idx_change_type (change_type),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- VERIFICATION
-- ============================================

-- Verify all tables were created
SELECT 'Events table check:' as 'Status';
DESCRIBE events;

SELECT 'Event Roles table check:' as 'Status';
DESCRIBE event_roles;

SELECT 'Event Helper Types table check:' as 'Status';
DESCRIBE event_helper_types;

SELECT 'Event Slots table check:' as 'Status';
DESCRIBE event_slots;

SELECT 'Event Signups table check:' as 'Status';
DESCRIBE event_signups;

SELECT 'Event History table check:' as 'Status';
DESCRIBE event_history;

SELECT 'Migration 004 completed successfully!' as 'Status';
