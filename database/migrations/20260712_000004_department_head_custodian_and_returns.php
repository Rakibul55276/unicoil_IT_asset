<?php

return [
    'version' => '20260712_000004_department_head_custodian_and_returns',
    'description' => 'Store department heads as custodians and remove return location requirement',
    'up' => function (PDO $pdo): void {
        $pdo->exec('ALTER TABLE asset_issue DROP CONSTRAINT chk_issue_target');
        $pdo->exec(
            "UPDATE asset_issue i
             JOIN department d ON d.deptId=i.deptId
             SET i.empId=d.headEmpId
             WHERE i.assignee_type='DEPARTMENT' AND i.empId IS NULL AND d.headEmpId IS NOT NULL"
        );
        $pdo->exec(
            "UPDATE assetdata a JOIN asset_issue i ON i.assetNum=a.assetNum AND i.status='ISSUED'
             SET a.empId=i.empId WHERE i.assignee_type='DEPARTMENT' AND i.empId IS NOT NULL"
        );
        $pdo->exec(
            "ALTER TABLE asset_issue ADD CONSTRAINT chk_issue_target CHECK (
                assignee_type IN ('DEPARTMENT','USER') AND deptId IS NOT NULL AND empId IS NOT NULL
            )"
        );
        $nullable = $pdo->query(
            "SELECT IS_NULLABLE FROM information_schema.columns
             WHERE table_schema=DATABASE() AND table_name='asset_return' AND column_name='return_location'"
        )->fetchColumn();
        if ($nullable === 'NO') {
            $pdo->exec('ALTER TABLE asset_return MODIFY return_location varchar(10) NULL');
        }
    },
];
