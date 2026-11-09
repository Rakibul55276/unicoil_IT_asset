<?php

return [
    'version' => '20260713_000008_employee_email_and_assignment_notifications',
    'description' => 'Add employee email and assignment email delivery log',
    'up' => function (PDO $pdo): void {
        $emailColumn = $pdo->query(
            "SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema=DATABASE() AND table_name='employee' AND column_name='email'"
        )->fetchColumn();
        if ((int) $emailColumn === 0) {
            $pdo->exec('ALTER TABLE employee ADD COLUMN email varchar(190) NULL AFTER fullname');
            $pdo->exec('CREATE INDEX idx_employee_email ON employee (email)');
        }

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS assignment_email_log (
                email_log_id bigint unsigned NOT NULL AUTO_INCREMENT,
                issue_id bigint unsigned NOT NULL,
                recipient_email varchar(190) NULL,
                delivery_status enum('SENT','FAILED','SKIPPED') NOT NULL,
                attachment_count int unsigned NOT NULL DEFAULT 0,
                error_message varchar(500) NULL,
                attempted_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (email_log_id),
                KEY idx_assignment_email_issue (issue_id),
                KEY idx_assignment_email_status (delivery_status, attempted_at),
                CONSTRAINT fk_assignment_email_issue FOREIGN KEY (issue_id)
                    REFERENCES asset_issue(issue_id) ON UPDATE CASCADE ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    },
];
