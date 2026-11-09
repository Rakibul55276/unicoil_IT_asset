<?php

class Dashboard_model
{
    private Database $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function getRoleDashboard(array $context): array
    {
        $username = (string) ($context['username'] ?? '');
        $empId = (int) ($context['empId'] ?? 0);
        $deptId = (string) ($context['deptId'] ?? '');
        $roles = $context['roles'] ?? [];

        return [
            'context' => $context,
            'primaryRole' => $this->primaryRole($roles),
            'personal' => $this->personalSummary($username, $empId),
            'department' => in_array('DEPARTMENT_MANAGER', $roles, true)
                ? $this->departmentSummary($username, $deptId)
                : [],
            'hr' => in_array('HR_REQUESTER', $roles, true)
                ? $this->hrSummary($username)
                : [],
            'it' => in_array('IT_MANAGER', $roles, true)
                ? $this->itSummary($username)
                : [],
            'issuer' => in_array('ISSUER', $roles, true)
                ? $this->issuerSummary($username)
                : [],
            'administrator' => in_array('ADMINISTRATOR', $roles, true)
                ? $this->administratorSummary()
                : [],
        ];
    }

    private function primaryRole(array $roles): string
    {
        foreach (['ADMINISTRATOR', 'IT_MANAGER', 'ISSUER', 'DEPARTMENT_MANAGER', 'HR_REQUESTER'] as $role) {
            if (in_array($role, $roles, true)) {
                return $role;
            }
        }
        return 'USER';
    }

    private function personalSummary(string $username, int $empId): array
    {
        $this->db->query("SELECT
            COUNT(*) AS total,
            SUM(status IN ('PENDING_DEPARTMENT_APPROVAL','PENDING_IT_APPROVAL')) AS pending,
            SUM(status='APPROVED') AS ready,
            SUM(status='PENDING_RECEIVER_ACCEPTANCE') AS awaiting_acceptance,
            SUM(status='COMPLETED') AS completed,
            SUM(status='REJECTED') AS rejected
            FROM asset_request
            WHERE requested_by=:username OR (:empCheck > 0 AND (requested_for=:requestedFor OR receiver_emp_id=:receiverEmp))");
        $this->db->bind('username', $username);
        $this->db->bind('empCheck', $empId);
        $this->db->bind('requestedFor', $empId);
        $this->db->bind('receiverEmp', $empId);
        $summary = $this->db->single() ?: [];

        $this->db->query("SELECT request_id,request_no,status,request_target_type,required_date,created_at
            FROM asset_request
            WHERE requested_by=:username OR (:empCheck > 0 AND (requested_for=:requestedFor OR receiver_emp_id=:receiverEmp))
            ORDER BY created_at DESC LIMIT 6");
        $this->db->bind('username', $username);
        $this->db->bind('empCheck', $empId);
        $this->db->bind('requestedFor', $empId);
        $this->db->bind('receiverEmp', $empId);
        $recent = $this->db->resultSet();

        $assets = [];
        $assetCount = 0;
        if ($empId > 0) {
            $this->db->query("SELECT i.issue_no,i.issue_date,a.assetNum,a.Description,k.Description AS category
                FROM asset_issue i
                JOIN assetdata a ON a.assetNum=i.assetNum
                LEFT JOIN assetkat k ON k.assetKat=a.assetKat
                WHERE i.empId=:empId AND i.status='ISSUED'
                ORDER BY i.issue_date DESC LIMIT 6");
            $this->db->bind('empId', $empId);
            $assets = $this->db->resultSet();
            $this->db->query("SELECT COUNT(*) AS total FROM asset_issue WHERE empId=:empId AND status='ISSUED'");
            $this->db->bind('empId', $empId);
            $assetCount = (int) (($this->db->single()['total'] ?? 0));
        }

        return ['summary' => $summary, 'recent' => $recent, 'assets' => $assets, 'assetCount' => $assetCount];
    }

    private function departmentSummary(string $username, string $deptId): array
    {
        $this->db->query("SELECT COUNT(*) AS pending
            FROM asset_request_approval_step s
            JOIN asset_request r ON r.request_id=s.request_id
            WHERE s.approver_username=:username AND s.step_type='MANAGER'
              AND s.status='WAITING' AND r.status='PENDING_DEPARTMENT_APPROVAL'");
        $this->db->bind('username', $username);
        $pending = (int) (($this->db->single()['pending'] ?? 0));

        $this->db->query("SELECT COUNT(*) AS total
            FROM asset_request
            WHERE deptId=:departmentId
              AND request_target_type='DEPARTMENT'");
        $this->db->bind('departmentId', $deptId);
        $total = (int) (($this->db->single()['total'] ?? 0));

        $this->db->query("SELECT request_id,request_no,status,request_target_type,created_at
            FROM asset_request
            WHERE deptId=:deptId
              AND request_target_type='DEPARTMENT'
            ORDER BY created_at DESC LIMIT 6");
        $this->db->bind('deptId', $deptId);
        return [
            'pending' => $pending,
            'total' => $total,
            'recent' => $this->db->resultSet(),
        ];
    }

    private function hrSummary(string $username): array
    {
        $this->db->query("SELECT COUNT(*) AS created,
            SUM(status IN ('PENDING_DEPARTMENT_APPROVAL','PENDING_IT_APPROVAL')) AS pending,
            SUM(status='COMPLETED') AS completed
            FROM asset_request WHERE requested_by=:username");
        $this->db->bind('username', $username);
        return $this->db->single() ?: [];
    }

    private function itSummary(string $username): array
    {
        $this->db->query("SELECT COUNT(*) AS pending
            FROM asset_request_approval_step s
            JOIN asset_request r ON r.request_id=s.request_id
            WHERE s.approver_username=:username AND s.step_type='IT_MANAGER'
              AND s.status='WAITING' AND r.status='PENDING_IT_APPROVAL'");
        $this->db->bind('username', $username);
        $pending = (int) (($this->db->single()['pending'] ?? 0));
        $this->db->query("SELECT
            SUM(assetStat='IN_STOCK') AS in_stock,
            SUM(assetStat='ASSIGNED') AS assigned,
            COUNT(*) AS total_assets
            FROM assetdata");
        $asset = $this->db->single() ?: [];
        $this->db->query("SELECT COUNT(*) AS waiting_issue FROM asset_request WHERE status='APPROVED'");
        return ['pending' => $pending, 'waitingIssue' => (int) (($this->db->single()['waiting_issue'] ?? 0)), 'asset' => $asset];
    }

    private function issuerSummary(string $username): array
    {
        $this->db->query("SELECT COUNT(*) AS waiting FROM asset_request
            WHERE status='APPROVED' AND requested_by<>:username");
        $this->db->bind('username', $username);
        $waiting = (int) (($this->db->single()['waiting'] ?? 0));
        $this->db->query("SELECT COUNT(*) AS issued_today FROM asset_issue
            WHERE issued_by=:username AND DATE(issue_date)=CURDATE()");
        $this->db->bind('username', $username);
        $issuedToday = (int) (($this->db->single()['issued_today'] ?? 0));
        return ['waiting' => $waiting, 'issuedToday' => $issuedToday];
    }

    private function administratorSummary(): array
    {
        $this->db->query("SELECT status,COUNT(*) AS total FROM asset_request GROUP BY status");
        $statuses = [];
        foreach ($this->db->resultSet() as $row) {
            $statuses[$row['status']] = (int) $row['total'];
        }
        $this->db->query("SELECT COUNT(*) AS active_users FROM users WHERE active=1");
        return ['statuses' => $statuses, 'activeUsers' => (int) (($this->db->single()['active_users'] ?? 0))];
    }
}
