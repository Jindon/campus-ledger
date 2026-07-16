CREATE TABLE IF NOT EXISTS transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_id VARCHAR(255) NOT NULL,
    occurred_at DATETIME NOT NULL,
    amount DECIMAL(14,2) NOT NULL,
    currency CHAR(3) NOT NULL,
    transaction_type VARCHAR(255) NOT NULL,
    status VARCHAR(255) NOT NULL,
    merchant VARCHAR(255) NULL,
    account VARCHAR(255) NULL,
    card_number VARCHAR(255) NULL,
    terminal_id VARCHAR(255) NULL,
    merchant_id VARCHAR(255) NULL,
    external_reference VARCHAR(255) NULL,
    import_batch_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_transactions_transaction_id (transaction_id),
    INDEX idx_transactions_occurred_at (occurred_at),
    INDEX idx_transactions_merchant (merchant),
    INDEX idx_transactions_merchant_id (merchant_id),
    INDEX idx_transactions_status (status),
    CONSTRAINT fk_transactions_import_batch FOREIGN KEY (import_batch_id)
    REFERENCES import_batches (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
