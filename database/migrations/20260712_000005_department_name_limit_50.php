<?php
return [
    'version' => '20260712_000005_department_name_limit_50',
    'description' => 'Limit department names to 50 characters',
    'up' => function (PDO $pdo): void {
        if ((int)$pdo->query('SELECT COUNT(*) FROM department WHERE CHAR_LENGTH(deptName)>50')->fetchColumn() > 0) {
            throw new RuntimeException('Shorten existing department names longer than 50 characters before migrating.');
        }
        $pdo->exec('ALTER TABLE department MODIFY deptName varchar(50) NOT NULL');
    },
];
