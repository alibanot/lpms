ALTER TABLE events
    ADD COLUMN status ENUM('Pending','Complete') NOT NULL DEFAULT 'Pending' AFTER balance_paid_date,
    ADD INDEX idx_event_status (status);
