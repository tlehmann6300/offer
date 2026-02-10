-- Migration: Add event_documentation table for board/alumni_board to track calculations and sales
-- Date: 2026-02-10

USE dbs15161271;

-- Create event_documentation table
CREATE TABLE IF NOT EXISTS event_documentation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    calculations TEXT,
    sales_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    UNIQUE KEY unique_event_doc (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Note: sales_data will store JSON array of sales entries with fields:
-- [{"label": "Verkauf 1", "amount": 150.50, "date": "2024-01-15"}, ...]
