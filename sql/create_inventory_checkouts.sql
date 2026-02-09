-- Tabelle für Ausleihen erstellen (fehlt und verursacht Absturz)
-- This table tracks inventory item checkouts and returns

-- NOTE: This table uses dual field names for backwards compatibility with existing code:
-- - inventory_item_id AND item_id (both should reference the same inventory item)
-- - checkout_date AND checked_out_at (both should contain the same timestamp)
-- - return_date AND returned_at (both should contain the same timestamp)
-- 
-- IMPORTANT: When inserting/updating, ensure both fields in each pair are set to the same value
-- to maintain data consistency. Future refactoring should consolidate to single field names.
-- Consider using database triggers or application-level logic to keep pairs synchronized.

CREATE TABLE IF NOT EXISTS inventory_checkouts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    
    -- Item reference (dual field names for compatibility)
    inventory_item_id INT NOT NULL,
    item_id INT NOT NULL,  -- Should always equal inventory_item_id
    
    quantity INT DEFAULT 1,
    
    -- Checkout timestamp (dual field names for compatibility)
    checkout_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    checked_out_at DATETIME DEFAULT CURRENT_TIMESTAMP, -- Should always equal checkout_date
    
    -- Return timestamp (dual field names for compatibility)
    return_date DATETIME NULL,
    returned_at DATETIME NULL, -- Should always equal return_date
    
    due_date DATETIME NULL,
    status VARCHAR(50) DEFAULT 'checked_out', -- 'checked_out', 'returned'
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_item_id (item_id),
    INDEX idx_status (status),
    INDEX idx_returned_at (returned_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
