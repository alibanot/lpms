CREATE TABLE IF NOT EXISTS events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_date DATE NOT NULL,
    event_place VARCHAR(180) NOT NULL,
    package_name VARCHAR(160) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    deposit_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    deposit_date DATE NULL,
    balance_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    balance_paid_date DATE NULL,
    status ENUM('Pending','Complete') NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_date (event_date),
    INDEX idx_event_status (status),
    INDEX idx_event_deposit_date (deposit_date),
    INDEX idx_event_balance_paid_date (balance_paid_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
