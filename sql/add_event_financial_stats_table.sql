-- Create event_financial_stats table for tracking sales and calculations
-- This table enables historical comparison (e.g., 2025 vs 2026)

CREATE TABLE IF NOT EXISTS event_financial_stats (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    category ENUM('Verkauf', 'Kalkulation') NOT NULL COMMENT 'Category: Sales or Calculation',
    item_name VARCHAR(255) NOT NULL COMMENT 'Item name, e.g., Brezeln, Äpfel, Grillstand',
    quantity INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Quantity sold or calculated',
    revenue DECIMAL(10, 2) DEFAULT NULL COMMENT 'Revenue in EUR (optional for calculations)',
    record_year YEAR NOT NULL COMMENT 'Year of record for historical comparison',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED NOT NULL COMMENT 'User who created the record',
    
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_event_id (event_id),
    INDEX idx_category (category),
    INDEX idx_record_year (record_year),
    INDEX idx_event_year (event_id, record_year),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Financial statistics for events - tracks sales and calculations with yearly comparison';
