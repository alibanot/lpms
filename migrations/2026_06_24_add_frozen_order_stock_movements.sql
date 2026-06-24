ALTER TABLE orders
    ADD COLUMN frozen_stock_id INT UNSIGNED NULL AFTER order_type,
    ADD INDEX idx_order_frozen_stock (frozen_stock_id);

CREATE TABLE IF NOT EXISTS frozen_stock_movements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    frozen_stock_id INT UNSIGNED NOT NULL,
    order_id INT UNSIGNED NULL,
    movement_date DATE NOT NULL,
    units INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_frozen_order_movement (order_id),
    INDEX idx_movement_stock (frozen_stock_id),
    INDEX idx_movement_date (movement_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
