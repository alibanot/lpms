ALTER TABLE events
    ADD COLUMN deposit_date DATE NULL AFTER deposit_paid,
    ADD COLUMN balance_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER deposit_date,
    ADD COLUMN balance_paid_date DATE NULL AFTER balance_paid,
    ADD INDEX idx_event_deposit_date (deposit_date),
    ADD INDEX idx_event_balance_paid_date (balance_paid_date);
