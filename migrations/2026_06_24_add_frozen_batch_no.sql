ALTER TABLE frozen_stock
    ADD COLUMN batch_no VARCHAR(40) NULL AFTER id;

UPDATE frozen_stock
SET batch_no = CONCAT('FZ-', DATE_FORMAT(date_made, '%Y%m%d'), '-', LPAD(id, 3, '0'))
WHERE batch_no IS NULL OR batch_no = '';

ALTER TABLE frozen_stock
    MODIFY batch_no VARCHAR(40) NOT NULL,
    ADD UNIQUE KEY idx_frozen_batch_no (batch_no);
