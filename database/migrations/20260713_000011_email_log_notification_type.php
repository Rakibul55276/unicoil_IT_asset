<?php

return [
    'version' => '20260713_000011_email_log_notification_type',
    'description' => 'Track assignment and return email notification types',
    'up' => function (PDO $pdo): void {
        $exists = (int) $pdo->query(
            "SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema=DATABASE() AND table_name='assignment_email_log'
               AND column_name='notification_type'"
        )->fetchColumn();
        if ($exists === 0) {
            $pdo->exec(
                "ALTER TABLE assignment_email_log
                 ADD COLUMN notification_type enum('ASSIGN','RETURN') NOT NULL DEFAULT 'ASSIGN'
                 AFTER issue_id"
            );
        }
    },
];
