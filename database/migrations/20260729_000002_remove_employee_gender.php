<?php

return [
    "version" => "20260729_000002_remove_employee_gender",
    "description" => "Remove the unused employee gender field",
    "up" => function (PDO $pdo): void {
        $column = $pdo->query(
            "SELECT COUNT(*)
             FROM information_schema.columns
             WHERE table_schema=DATABASE()
               AND table_name='employee'
               AND column_name='gender'",
        );
        if ((int) $column->fetchColumn() > 0) {
            $pdo->exec("ALTER TABLE employee DROP COLUMN gender");
        }
    },
];
