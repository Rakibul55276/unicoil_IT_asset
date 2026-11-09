<?php

return [
    'version' => '20260712_000003_add_department_head',
    'description' => 'Add a designated active employee as department head',
    'up' => function (PDO $pdo): void {
        $exists = (int)$pdo->query(
            "SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema=DATABASE() AND table_name='department' AND column_name='headEmpId'"
        )->fetchColumn();
        if (!$exists) {
            $pdo->exec('ALTER TABLE department ADD COLUMN headEmpId int NULL AFTER deptName');
            $pdo->exec('ALTER TABLE department ADD KEY idx_department_head (headEmpId)');
            $pdo->exec(
                'ALTER TABLE department ADD CONSTRAINT fk_department_head
                 FOREIGN KEY (headEmpId) REFERENCES employee(empId)
                 ON UPDATE CASCADE ON DELETE SET NULL'
            );
        }
    },
];

