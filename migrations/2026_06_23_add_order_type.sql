ALTER TABLE orders
    ADD COLUMN order_type ENUM('Tempahan','Frozen') NOT NULL DEFAULT 'Tempahan' AFTER phone,
    ADD INDEX idx_order_type (order_type);
