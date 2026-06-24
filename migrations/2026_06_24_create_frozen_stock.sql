CREATE TABLE IF NOT EXISTS frozen_stock (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    date_made DATE NOT NULL,
    expiry_date DATE NOT NULL,
    units INT UNSIGNED NOT NULL,
    units_remaining INT UNSIGNED NOT NULL,
    pieces_per_unit INT UNSIGNED NOT NULL,
    remarks TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_frozen_date_made (date_made),
    INDEX idx_frozen_expiry_date (expiry_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
