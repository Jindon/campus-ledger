CREATE TABLE IF NOT EXISTS rejected_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    import_batch_id BIGINT UNSIGNED NOT NULL,
    row_no INT UNSIGNED NOT NULL,
    transaction_id VARCHAR(100) NULL,
    errors JSON NOT NULL,
    raw_data JSON NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_rejected_transactions_import_batch (import_batch_id),
    CONSTRAINT fk_rejected_transactions_import_batch FOREIGN KEY (import_batch_id)
    REFERENCES import_batches (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
