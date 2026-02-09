-- Tabelle für Ausleihen erstellen (fehlt und verursacht Absturz)
-- This table tracks inventory item checkouts and returns

CREATE TABLE IF NOT EXISTS inventory_checkouts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    inventory_item_id INT NOT NULL,
    item_id INT NOT NULL, -- For compatibility with existing queries
    quantity INT DEFAULT 1,
    checkout_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    checked_out_at DATETIME DEFAULT CURRENT_TIMESTAMP, -- For compatibility with existing queries
    return_date DATETIME NULL,
    returned_at DATETIME NULL, -- For compatibility with existing queries
    due_date DATETIME NULL,
    status VARCHAR(50) DEFAULT 'checked_out', -- 'checked_out', 'returned'
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_item_id (item_id),
    INDEX idx_status (status),
    INDEX idx_returned_at (returned_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Note: This table supports both field names (inventory_item_id and item_id, return_date and returned_at, checkout_date and checked_out_at)
-- for maximum compatibility with existing code
