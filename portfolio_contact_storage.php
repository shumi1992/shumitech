<?php
declare(strict_types=1);

function portfolioEnsureContactInboxTable(mysqli $connection): void
{
    $createTable = "CREATE TABLE IF NOT EXISTS portfolio_contact_messages (
        message_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        sender_name VARCHAR(120) NOT NULL,
        sender_email VARCHAR(254) NOT NULL,
        subject VARCHAR(160) NOT NULL,
        message TEXT NOT NULL,
        folder VARCHAR(20) NOT NULL DEFAULT 'inbox',
        recipient_email VARCHAR(254) NULL,
        cc_emails TEXT NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_portfolio_contact_read_date (is_read, submitted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if (!$connection->query($createTable)) {
        throw new RuntimeException('Could not initialize portfolio contact inbox: ' . $connection->error);
    }

    $columns = [
        'folder' => "VARCHAR(20) NOT NULL DEFAULT 'inbox'",
        'recipient_email' => 'VARCHAR(254) NULL',
        'cc_emails' => 'TEXT NULL',
    ];
    foreach ($columns as $column => $definition) {
        $columnResult = $connection->query(
            "SHOW COLUMNS FROM portfolio_contact_messages LIKE '" . $connection->real_escape_string($column) . "'"
        );
        if (!$columnResult) {
            throw new RuntimeException('Could not inspect contact inbox schema: ' . $connection->error);
        }
        $columnExists = $columnResult->num_rows > 0;
        $columnResult->free();
        if (!$columnExists && !$connection->query("ALTER TABLE portfolio_contact_messages ADD COLUMN {$column} {$definition}")) {
            throw new RuntimeException('Could not add contact inbox column ' . $column . ': ' . $connection->error);
        }
    }
}
