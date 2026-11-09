<?php

class Request_model
{
    private const MAX_REQUEST_ASSETS = 8;

    private Database $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function getUserContext(string $username): array
    {
        $this->db->query("SELECT u.username,u.nama,u.email,u.empId,e.fullname,e.deptId,
            d.deptName,GROUP_CONCAT(ur.role_code ORDER BY ur.role_code) AS roles
            FROM users u
            LEFT JOIN employee e ON e.empId=u.empId
            LEFT JOIN department d ON d.deptId=e.deptId
            LEFT JOIN user_role ur ON ur.username=u.username
            WHERE u.username=:username AND u.active=1
            GROUP BY u.username,u.nama,u.email,u.empId,e.fullname,e.deptId,d.deptName");
        $this->db->bind("username", $username);
        $row = $this->db->single() ?: [];
        $row["roles"] = array_values(array_filter(explode(",", (string) ($row["roles"] ?? ""))));
        return $row;
    }

    public function getActiveEmployees(): array
    {
        $this->db->query("SELECT e.empId,e.fullname,e.deptId,d.deptName
            FROM employee e JOIN department d ON d.deptId=e.deptId
            WHERE e.aktif=1 AND d.active=1 ORDER BY e.fullname");
        return $this->db->resultSet();
    }

    public function getActiveAdministrators(array $excludeUsernames = []): array
    {
        $this->db->query("SELECT DISTINCT u.username,u.nama,u.email,u.empId,e.fullname
            FROM users u
            JOIN user_role ur ON ur.username=u.username AND ur.role_code='ADMINISTRATOR'
            LEFT JOIN employee e ON e.empId=u.empId
            WHERE u.active=1
            ORDER BY COALESCE(e.fullname,u.nama),u.username");
        $administrators = $this->db->resultSet();
        $excluded = array_fill_keys(array_map('strval', $excludeUsernames), true);
        return array_values(array_filter($administrators, static function (array $administrator) use ($excluded): bool {
            return !isset($excluded[(string) $administrator["username"]]);
        }));
    }

    public function getCategories(): array
    {
        $this->db->query("SELECT k.assetKat,k.Description,
            SUM(CASE WHEN a.assetStat='IN_STOCK' THEN 1 ELSE 0 END) AS available_count
            FROM assetkat k LEFT JOIN assetdata a ON a.assetKat=k.assetKat
            WHERE k.active=1 GROUP BY k.assetKat,k.Description ORDER BY k.Description");
        return $this->db->resultSet();
    }

    public function getActiveDepartments(): array
    {
        $this->db->query("SELECT d.deptId,d.deptName,
            COALESCE(
                d.headEmpId,
                (
                    SELECT MIN(legacy_head.empId)
                    FROM department_head legacy_head
                    JOIN employee legacy_employee
                      ON legacy_employee.empId=legacy_head.empId
                     AND legacy_employee.aktif=1
                    WHERE legacy_head.deptId=d.deptId
                )
            ) AS headEmpId,
            h.fullname AS headName
            FROM department d
            LEFT JOIN employee h ON h.empId=COALESCE(
                d.headEmpId,
                (
                    SELECT MIN(legacy_head.empId)
                    FROM department_head legacy_head
                    JOIN employee legacy_employee
                      ON legacy_employee.empId=legacy_head.empId
                     AND legacy_employee.aktif=1
                    WHERE legacy_head.deptId=d.deptId
                )
            )
            WHERE d.active=1 ORDER BY d.deptName");
        return $this->db->resultSet();
    }

    public function getRequests(
        array $context,
        bool $departmentTargetOnly = false,
    ): array
    {
        $roles = $context["roles"] ?? [];
        $departmentId = (string) ($context["deptId"] ?? "");
        if (
            $departmentTargetOnly &&
            (!in_array("DEPARTMENT_MANAGER", $roles, true) ||
                $departmentId === "")
        ) {
            throw new RuntimeException(
                "Only a Department Manager can view department requests.",
            );
        }

        $this->db->query("SELECT r.request_id,r.request_no,r.requested_by,r.requested_for,r.receiver_emp_id,r.deptId,r.issued_by,
            r.request_source,r.request_target_type,r.business_reason,r.required_date,r.work_location,r.status,r.created_at,
            CASE WHEN r.request_target_type='DEPARTMENT' THEN d.deptName ELSE e.fullname END AS requestedForName,
            d.deptName,d.headEmpId,u.nama AS requestedByName,
            (SELECT s.approver_username FROM asset_request_approval_step s
             WHERE s.request_id=r.request_id AND s.status='WAITING'
             ORDER BY s.step_order LIMIT 1) AS currentApprover,
            (SELECT COUNT(*) FROM asset_request_approval_step visible_step
             WHERE visible_step.request_id=r.request_id
               AND visible_step.approver_username=:viewerUsername
               AND visible_step.status IN ('WAITING','APPROVED','REJECTED')) AS approvalVisible,
            GROUP_CONCAT(DISTINCT CONCAT(k.Description,' x',ri.quantity) ORDER BY k.Description SEPARATOR ', ') AS itemSummary,
            (SELECT COALESCE(SUM(COALESCE(total_item.approved_quantity,0)),0)
             FROM asset_request_item total_item
             WHERE total_item.request_id=r.request_id) AS approvedTotal,
            (SELECT COUNT(*)
             FROM asset_request_allocation total_allocation
             JOIN asset_request_item allocated_item
               ON allocated_item.request_item_id=total_allocation.request_item_id
             WHERE allocated_item.request_id=r.request_id) AS allocatedTotal,
            (SELECT GROUP_CONCAT(total_allocation.issue_id ORDER BY total_allocation.issue_id SEPARATOR ',')
             FROM asset_request_allocation total_allocation
             JOIN asset_request_item allocated_item
               ON allocated_item.request_item_id=total_allocation.request_item_id
             WHERE allocated_item.request_id=r.request_id) AS issueIds
            FROM asset_request r
            LEFT JOIN employee e ON e.empId=r.requested_for
            JOIN department d ON d.deptId=r.deptId
            JOIN users u ON u.username=r.requested_by
            JOIN asset_request_item ri ON ri.request_id=r.request_id
            JOIN assetkat k ON k.assetKat=ri.assetKat
            WHERE (:departmentTargetOnly=0 OR
                (r.request_target_type='DEPARTMENT' AND r.deptId=:departmentScope))
            GROUP BY r.request_id,r.request_no,r.requested_by,r.requested_for,r.receiver_emp_id,r.deptId,r.issued_by,
                r.request_source,r.request_target_type,r.business_reason,r.required_date,r.work_location,r.status,r.created_at,
                e.fullname,d.deptName,d.headEmpId,u.nama
            ORDER BY r.created_at DESC,r.request_id DESC");
        $this->db->bind(
            "viewerUsername",
            (string) ($context["username"] ?? ""),
        );
        $this->db->bind("departmentTargetOnly", $departmentTargetOnly ? 1 : 0);
        $this->db->bind("departmentScope", $departmentId);
        $rows = $this->db->resultSet();
        if ($departmentTargetOnly) {
            return $rows;
        }
        $canSeeAll = in_array("ADMINISTRATOR", $roles, true);
        return array_values(array_filter($rows, static function (array $row) use ($context, $roles, $canSeeAll): bool {
            if ($canSeeAll) {
                return true;
            }
            // A queued future approver is intentionally excluded. A step only
            // becomes visible when it is WAITING; completed approvers retain
            // access to the request for audit/history purposes.
            if ((int) ($row["approvalVisible"] ?? 0) > 0) {
                return true;
            }
            // Once all approvals are complete there is no WAITING approver row.
            // Issuers must still be able to see approved requests in their issue
            // queue. Administrators already see all requests through canSeeAll.
            // Self-assignment is blocked again in the request controller.
            if (
                in_array("ISSUER", $roles, true) &&
                (string) ($row["status"] ?? "") === "APPROVED" &&
                (string) ($row["requested_by"] ?? "") !==
                    (string) ($context["username"] ?? "")
            ) {
                return true;
            }
            // Keep the request visible to the Issuer who completed allocation.
            // Without this rule, the successful issue redirect becomes a 403
            // as soon as the status changes to receiver acceptance.
            if (
                in_array("ISSUER", $roles, true) &&
                (string) ($row["issued_by"] ?? "") !== "" &&
                strcasecmp(
                    (string) $row["issued_by"],
                    (string) ($context["username"] ?? ""),
                ) === 0
            ) {
                return true;
            }
            if (
                ($row["request_target_type"] ?? "USER") === "DEPARTMENT" &&
                (int) ($row["receiver_emp_id"] ?? $row["headEmpId"] ?? 0) ===
                    (int) ($context["empId"] ?? 0)
            ) {
                return true;
            }
            return (string) $row["requested_by"] === (string) ($context["username"] ?? "") ||
                ($row["requested_for"] !== null && (int) $row["requested_for"] === (int) ($context["empId"] ?? 0));
        }));
    }

    public function getRequest(int $requestId): ?array
    {
        $this->db->query("SELECT r.*,e.fullname AS requestedForName,e.email AS requestedForEmail,
            d.deptName,d.headEmpId,dh.fullname AS departmentHeadName,u.nama AS requestedByName,
            receiver.fullname AS receiverName,receiver.position AS receiverPosition
            FROM asset_request r
            LEFT JOIN employee e ON e.empId=r.requested_for
            LEFT JOIN employee receiver ON receiver.empId=r.receiver_emp_id
            JOIN department d ON d.deptId=r.deptId
            LEFT JOIN employee dh ON dh.empId=COALESCE(
                d.headEmpId,
                (
                    SELECT MIN(legacy_head.empId)
                    FROM department_head legacy_head
                    JOIN employee legacy_employee
                      ON legacy_employee.empId=legacy_head.empId
                     AND legacy_employee.aktif=1
                    WHERE legacy_head.deptId=d.deptId
                )
            )
            JOIN users u ON u.username=r.requested_by
            WHERE r.request_id=:requestId");
        $this->db->bind("requestId", $requestId);
        $request = $this->db->single();
        if (!$request) {
            return null;
        }

        $this->db->query("SELECT ri.*,k.Description AS categoryName,
            COUNT(ra.allocation_id) AS allocatedCount
            FROM asset_request_item ri JOIN assetkat k ON k.assetKat=ri.assetKat
            LEFT JOIN asset_request_allocation ra ON ra.request_item_id=ri.request_item_id
            WHERE ri.request_id=:requestId
            GROUP BY ri.request_item_id,ri.request_id,ri.assetKat,ri.quantity,
                ri.approved_quantity,ri.it_specification,ri.item_status,k.Description
            ORDER BY k.Description");
        $this->db->bind("requestId", $requestId);
        $request["items"] = $this->db->resultSet();

        $this->db->query("SELECT ev.*,u.nama AS actorName FROM asset_request_event ev
            JOIN users u ON u.username=ev.actor_username
            WHERE ev.request_id=:requestId ORDER BY ev.created_at,ev.event_id");
        $this->db->bind("requestId", $requestId);
        $request["events"] = $this->db->resultSet();

        $this->db->query("SELECT s.*,u.empId,e.fullname,e.position,d.deptName,
            actor.nama AS actedByName
            FROM asset_request_approval_step s
            JOIN users u ON u.username=s.approver_username
            LEFT JOIN employee e ON e.empId=u.empId
            LEFT JOIN department d ON d.deptId=e.deptId
            LEFT JOIN users actor ON actor.username=s.acted_by
            WHERE s.request_id=:requestId ORDER BY s.step_order");
        $this->db->bind("requestId", $requestId);
        $request["approvalSteps"] = $this->db->resultSet();
        $request["nextApprover"] = null;
        foreach ($request["approvalSteps"] as $approvalStep) {
            if ($approvalStep["status"] === "WAITING") {
                $request["nextApprover"] = $approvalStep;
                break;
            }
        }

        $this->db->query("SELECT ra.request_item_id,ra.assetNum,ra.issue_id,ra.allocated_at,
            a.Description,a.serial_number,i.issue_no
            FROM asset_request_allocation ra JOIN assetdata a ON a.assetNum=ra.assetNum
            JOIN asset_issue i ON i.issue_id=ra.issue_id
            JOIN asset_request_item ri ON ri.request_item_id=ra.request_item_id
            WHERE ri.request_id=:requestId ORDER BY ra.allocation_id");
        $this->db->bind("requestId", $requestId);
        $request["allocations"] = $this->db->resultSet();
        return $request;
    }

    public function createRequest(array $data, array $context): int
    {
        $requestScope = strtoupper(
            trim((string) ($data["request_target_type"] ?? "SELF")),
        );
        if (!in_array($requestScope, ["SELF", "USER", "DEPARTMENT"], true)) {
            throw new InvalidArgumentException("Select an employee or department request type.");
        }
        $roles = $context["roles"] ?? [];
        $ownEmployeeId = (int) ($context["empId"] ?? 0);
        $targetType = $requestScope === "DEPARTMENT" ? "DEPARTMENT" : "USER";
        $requestedFor = $targetType === "USER"
            ? ($requestScope === "SELF"
                ? $ownEmployeeId
                : (int) ($data["requested_for"] ?? 0))
            : null;
        $requestedDepartment = trim((string) ($data["requested_department"] ?? ""));
        $reason = trim((string) ($data["business_reason"] ?? ""));
        if ($reason === "") {
            throw new InvalidArgumentException("Enter the business reason.");
        }

        if ($targetType === "USER") {
            if (!$requestedFor) {
                throw new InvalidArgumentException("Select the employee who needs the equipment.");
            }
            if (
                $requestedFor !== $ownEmployeeId &&
                !array_intersect($roles, ["ADMINISTRATOR", "HR_REQUESTER"])
            ) {
                throw new RuntimeException("Only HR Requesters can submit for another employee.");
            }
            $this->db->query("SELECT e.empId,e.deptId,d.deptName FROM employee e JOIN department d ON d.deptId=e.deptId
                WHERE e.empId=:empId AND e.aktif=1 AND d.active=1");
            $this->db->bind("empId", $requestedFor);
            $employee = $this->db->single();
            if (!$employee) {
                throw new InvalidArgumentException("Select an active employee with a department.");
            }
            $deptId = $employee["deptId"];
            $workLocation = $employee["deptName"];
            $receiverEmployeeId = $requestedFor;
        } else {
            $isAdministrator = in_array("ADMINISTRATOR", $roles, true);
            if (
                !$isAdministrator &&
                !in_array("DEPARTMENT_MANAGER", $roles, true)
            ) {
                throw new RuntimeException(
                    "Only the Department Manager can request assets for a department.",
                );
            }
            $ownDepartment = (string) ($context["deptId"] ?? "");
            if ($requestedDepartment === "") {
                $requestedDepartment = $ownDepartment;
            }
            if (!$isAdministrator && $requestedDepartment !== $ownDepartment) {
                throw new RuntimeException(
                    "A Department Manager can request only for their own department.",
                );
            }
            $this->db->query("SELECT deptId,deptName FROM department WHERE deptId=:deptId AND active=1");
            $this->db->bind("deptId", $requestedDepartment);
            $department = $this->db->single();
            if (!$department) {
                throw new InvalidArgumentException("Select an active department.");
            }
            $deptId = $department["deptId"];
            $workLocation = $department["deptName"];
            $this->db->query("SELECT e.empId
                FROM department d
                JOIN employee e ON e.empId=COALESCE(
                    d.headEmpId,
                    (
                        SELECT MIN(legacy_head.empId)
                        FROM department_head legacy_head
                        JOIN employee legacy_employee
                          ON legacy_employee.empId=legacy_head.empId
                         AND legacy_employee.aktif=1
                        WHERE legacy_head.deptId=d.deptId
                    )
                ) AND e.aktif=1
                WHERE d.deptId=:deptId
                LIMIT 1");
            $this->db->bind("deptId", $deptId);
            $departmentReceiver = $this->db->single();
            if (!$departmentReceiver) {
                throw new RuntimeException(
                    "Assign one active Section Head before requesting for this department.",
                );
            }
            $receiverEmployeeId = (int) $departmentReceiver["empId"];
        }

        $categories = $data["category"] ?? [];
        $quantities = $data["quantity"] ?? [];
        if (!is_array($categories) || !is_array($quantities)) {
            throw new InvalidArgumentException("Select at least one equipment type.");
        }
        $items = [];
        foreach ($categories as $index => $category) {
            $category = trim((string) $category);
            $quantity = (int) ($quantities[$index] ?? 0);
            if ($category === "" || $quantity <= 0) {
                continue;
            }
            if ($quantity > self::MAX_REQUEST_ASSETS) {
                throw new InvalidArgumentException(
                    "A request can include a maximum of 8 assets.",
                );
            }
            $items[$category] = ($items[$category] ?? 0) + $quantity;
        }
        if (!$items) {
            throw new InvalidArgumentException("Select at least one equipment type and quantity.");
        }
        if (array_sum($items) > self::MAX_REQUEST_ASSETS) {
            throw new InvalidArgumentException(
                "A request can include a maximum of 8 assets.",
            );
        }

        $this->db->beginTransaction();
        try {
            foreach ($items as $category => $quantity) {
                $this->db->query("SELECT 1 FROM assetkat WHERE assetKat=:category AND active=1");
                $this->db->bind("category", $category);
                if (!$this->db->single()) {
                    throw new InvalidArgumentException("One selected equipment category is inactive.");
                }
            }
            $requestNo = "REQ-" . date("Ymd-His") . "-" . random_int(100, 999);
            $this->db->query("INSERT INTO asset_request
                (request_no,requested_by,requested_for,receiver_emp_id,deptId,request_target_type,request_source,business_reason,required_date,work_location)
                VALUES (:requestNo,:requestedBy,:requestedFor,:receiverEmpId,:deptId,:targetType,:source,:reason,:requiredDate,:location)");
            $this->db->bind("requestNo", $requestNo);
            $this->db->bind("requestedBy", $context["username"]);
            $this->db->bind("requestedFor", $requestedFor);
            $this->db->bind("receiverEmpId", $receiverEmployeeId);
            $this->db->bind("deptId", $deptId);
            $this->db->bind("targetType", $targetType);
            $this->db->bind("source", $targetType === "DEPARTMENT" ? "DEPARTMENT" : ($requestedFor === $ownEmployeeId ? "SELF" : "ON_BEHALF"));
            $this->db->bind("reason", substr($reason, 0, 500));
            $this->db->bind("requiredDate", trim((string) ($data["required_date"] ?? "")) ?: null);
            // Department is the request work location; do not ask users to type
            // a second, potentially inconsistent location value.
            $this->db->bind("location", $workLocation);
            $this->db->executeOrFail();
            $requestId = (int) $this->db->lastInsertId();

            foreach ($items as $category => $quantity) {
                $this->db->query("INSERT INTO asset_request_item (request_id,assetKat,quantity)
                    VALUES (:requestId,:category,:quantity)");
                $this->db->bind("requestId", $requestId);
                $this->db->bind("category", $category);
                $this->db->bind("quantity", $quantity);
                $this->db->executeOrFail();
            }
            $route = $this->createApprovalRoute(
                $requestId,
                (string) $context["username"],
                $targetType === "USER" ? $requestedFor : $ownEmployeeId,
                (string) $deptId,
            );
            $initialStatus = $route["status"];
            $this->db->query("UPDATE asset_request SET status=:status WHERE request_id=:requestId");
            $this->db->bind("status", $initialStatus);
            $this->db->bind("requestId", $requestId);
            $this->db->executeOrFail();
            if ($initialStatus === "APPROVED") {
                $this->db->query("UPDATE asset_request_item
                    SET approved_quantity=quantity,item_status='APPROVED'
                    WHERE request_id=:requestId");
                $this->db->bind("requestId", $requestId);
                $this->db->executeOrFail();
            }
            $this->addEvent(
                $requestId,
                "CREATED",
                null,
                $initialStatus,
                $context["username"],
                $route["message"],
            );
            $this->db->commit();
            return $requestId;
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    public function approveDepartment(int $requestId, string $actor, ?string $remarks): void
    {
        $this->approveCurrentManagerStep($requestId, $actor, $remarks);
    }

    public function isCurrentApprover(
        int $requestId,
        string $username,
        ?string $stepType = null,
    ): bool {
        $sql = "SELECT 1 FROM asset_request_approval_step
            WHERE request_id=:requestId AND approver_username=:username
              AND status='WAITING'";
        if ($stepType !== null) {
            $sql .= " AND step_type=:stepType";
        }
        $sql .= " LIMIT 1";
        $this->db->query($sql);
        $this->db->bind("requestId", $requestId);
        $this->db->bind("username", $username);
        if ($stepType !== null) {
            $this->db->bind("stepType", $stepType);
        }
        if ($this->db->single()) {
            return true;
        }
        $this->db->query("SELECT COUNT(*) AS total FROM asset_request_approval_step
            WHERE request_id=:requestId");
        $this->db->bind("requestId", $requestId);
        if ((int) ($this->db->single()["total"] ?? 0) > 0) {
            return false;
        }
        if ($stepType === "MANAGER") {
            $this->db->query("SELECT 1 FROM asset_request r
                JOIN department d ON d.deptId=r.deptId
                JOIN users u ON u.empId=COALESCE(
                    d.headEmpId,
                    (
                        SELECT MIN(legacy_head.empId)
                        FROM department_head legacy_head
                        JOIN employee legacy_employee
                          ON legacy_employee.empId=legacy_head.empId
                         AND legacy_employee.aktif=1
                        WHERE legacy_head.deptId=d.deptId
                    )
                ) AND u.active=1
                WHERE r.request_id=:requestId
                  AND r.status='PENDING_DEPARTMENT_APPROVAL'
                  AND u.username=:username LIMIT 1");
        } elseif ($stepType === "IT_MANAGER") {
            $this->db->query("SELECT 1 FROM asset_request r
                JOIN user_role ur ON ur.role_code='IT_MANAGER'
                JOIN users u ON u.username=ur.username AND u.active=1
                WHERE r.request_id=:requestId
                  AND r.status='PENDING_IT_APPROVAL'
                  AND u.username=:username LIMIT 1");
        } else {
            return false;
        }
        $this->db->bind("requestId", $requestId);
        $this->db->bind("username", $username);
        return (bool) $this->db->single();
    }

    public function updateRequestedItems(
        int $requestId,
        string $actor,
        array $data,
    ): void {
        $request = $this->getRequest($requestId);
        if (
            !$request ||
            $request["status"] !== "PENDING_DEPARTMENT_APPROVAL" ||
            (string) $request["requested_by"] !== $actor
        ) {
            throw new RuntimeException(
                "This request can no longer be edited.",
            );
        }

        $categories = $data["category"] ?? [];
        $quantities = $data["quantity"] ?? [];
        if (!is_array($categories) || !is_array($quantities)) {
            throw new InvalidArgumentException(
                "Select at least one equipment type.",
            );
        }

        $items = [];
        foreach ($categories as $index => $category) {
            $category = trim((string) $category);
            $quantity = (int) ($quantities[$index] ?? 0);
            if ($category === "" || $quantity <= 0) {
                continue;
            }
            if ($quantity > self::MAX_REQUEST_ASSETS) {
                throw new InvalidArgumentException(
                    "A request can include a maximum of 8 assets.",
                );
            }
            $items[$category] = ($items[$category] ?? 0) + $quantity;
        }
        if (!$items) {
            throw new InvalidArgumentException(
                "Select at least one equipment type and quantity.",
            );
        }
        if (array_sum($items) > self::MAX_REQUEST_ASSETS) {
            throw new InvalidArgumentException(
                "A request can include a maximum of 8 assets.",
            );
        }

        $this->db->beginTransaction();
        try {
            foreach ($items as $category => $quantity) {
                $this->db->query(
                    "SELECT 1 FROM assetkat
                    WHERE assetKat=:category AND active=1",
                );
                $this->db->bind("category", $category);
                if (!$this->db->single()) {
                    throw new InvalidArgumentException(
                        "One selected equipment category is inactive.",
                    );
                }
            }

            // Lock the request before replacing its items. An approval that
            // starts concurrently must wait and will then see the saved items;
            // an approval that already completed is detected here.
            $this->db->query(
                "SELECT status FROM asset_request
                WHERE request_id=:requestId FOR UPDATE",
            );
            $this->db->bind("requestId", $requestId);
            $lockedRequest = $this->db->single();
            if (
                !$lockedRequest ||
                (string) $lockedRequest["status"] !==
                    "PENDING_DEPARTMENT_APPROVAL"
            ) {
                throw new RuntimeException(
                    "This request was approved while it was being edited. Refresh to view its current status.",
                );
            }

            $this->db->query(
                "UPDATE asset_request SET required_date=:requiredDate
                WHERE request_id=:requestId",
            );
            $this->db->bind(
                "requiredDate",
                trim((string) ($data["required_date"] ?? "")) ?: null,
            );
            $this->db->bind("requestId", $requestId);
            $this->db->executeOrFail();

            $this->db->query(
                "DELETE FROM asset_request_item WHERE request_id=:requestId",
            );
            $this->db->bind("requestId", $requestId);
            $this->db->executeOrFail();

            foreach ($items as $category => $quantity) {
                $this->db->query(
                    "INSERT INTO asset_request_item
                    (request_id,assetKat,quantity)
                    VALUES (:requestId,:category,:quantity)",
                );
                $this->db->bind("requestId", $requestId);
                $this->db->bind("category", $category);
                $this->db->bind("quantity", $quantity);
                $this->db->executeOrFail();
            }

            $this->addEvent(
                $requestId,
                "REQUEST_UPDATED",
                "PENDING_DEPARTMENT_APPROVAL",
                "PENDING_DEPARTMENT_APPROVAL",
                $actor,
                "Requested equipment list updated before approval.",
            );
            $this->db->commit();
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    public function approveIt(int $requestId, string $actor, array $data): void
    {
        $request = $this->getRequest($requestId);
        if (!$request || $request["status"] !== "PENDING_IT_APPROVAL" ||
            !$this->isCurrentApprover($requestId, $actor, "IT_MANAGER")) {
            throw new RuntimeException("This request is not awaiting IT approval.");
        }
        if ((string) $request["requested_by"] === $actor) {
            throw new RuntimeException("IT Managers cannot approve their own requests.");
        }
        $quantities = $data["approved_quantity"] ?? [];
        $specifications = $data["it_specification"] ?? [];
        $this->db->beginTransaction();
        try {
            foreach ($request["items"] as $item) {
                $itemId = (int) $item["request_item_id"];
                $approved = (int) ($quantities[$itemId] ?? 0);
                if ($approved < 0 || $approved > (int) $item["quantity"]) {
                    throw new InvalidArgumentException("Approved quantities cannot exceed requested quantities.");
                }
                $this->db->query("UPDATE asset_request_item SET approved_quantity=:quantity,
                    it_specification=:specification,item_status=:status WHERE request_item_id=:itemId");
                $this->db->bind("quantity", $approved);
                $this->db->bind("specification", trim((string) ($specifications[$itemId] ?? "")) ?: null);
                $this->db->bind("status", $approved > 0 ? "APPROVED" : "DECLINED");
                $this->db->bind("itemId", $itemId);
                $this->db->executeOrFail();
            }
            $this->db->query("SELECT COALESCE(SUM(approved_quantity),0) total FROM asset_request_item WHERE request_id=:requestId");
            $this->db->bind("requestId", $requestId);
            if ((int) ($this->db->single()["total"] ?? 0) < 1) {
                throw new InvalidArgumentException("Approve at least one requested item.");
            }
            $this->db->query("UPDATE asset_request SET status='APPROVED',it_approved_by=:actor,it_approved_at=NOW()
                WHERE request_id=:requestId AND status='PENDING_IT_APPROVAL'");
            $this->db->bind("actor", $actor);
            $this->db->bind("requestId", $requestId);
            $this->db->executeOrFail();
            $this->completeWaitingStep($requestId, $actor, $data["remarks"] ?? null);
            $this->addEvent(
                $requestId,
                "IT_APPROVED",
                "PENDING_IT_APPROVAL",
                "APPROVED",
                $actor,
                trim((string) ($data["remarks"] ?? "")) ?: null,
            );
            $this->db->commit();
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    public function adminChangeApprovalStatus(
        int $requestId,
        string $actor,
        string $targetStatus,
        string $reason,
    ): void {
        $allowedTargets = [
            "PENDING_DEPARTMENT_APPROVAL",
            "PENDING_IT_APPROVAL",
            "APPROVED",
        ];
        $targetStatus = strtoupper(trim($targetStatus));
        $reason = trim($reason);
        if (!in_array($targetStatus, $allowedTargets, true)) {
            throw new InvalidArgumentException("Select a valid approval status.");
        }
        if ($reason === "") {
            throw new InvalidArgumentException("Enter a reason for the Administrator status change.");
        }
        $request = $this->getRequest($requestId);
        if (!$request) {
            throw new RuntimeException("Request not found.");
        }
        $fromStatus = (string) $request["status"];
        if (!empty($request["allocations"])) {
            throw new RuntimeException(
                "Approval status cannot be changed after asset issuance has started.",
            );
        }
        if (in_array($fromStatus, ["PENDING_RECEIVER_ACCEPTANCE", "COMPLETED", "CANCELLED"], true)) {
            throw new RuntimeException("Approval status cannot be changed after asset issuance or completion.");
        }
        if ($fromStatus === $targetStatus) {
            throw new InvalidArgumentException("Select a different status.");
        }

        $nextSteps = [];
        if ($targetStatus === "PENDING_DEPARTMENT_APPROVAL") {
            $this->db->query("SELECT u.username
                FROM department d
                JOIN employee e ON e.empId=COALESCE(
                    d.headEmpId,
                    (
                        SELECT MIN(legacy_head.empId)
                        FROM department_head legacy_head
                        JOIN employee legacy_employee
                          ON legacy_employee.empId=legacy_head.empId
                         AND legacy_employee.aktif=1
                        WHERE legacy_head.deptId=d.deptId
                    )
                ) AND e.aktif=1
                JOIN users u ON u.empId=e.empId AND u.active=1
                WHERE d.deptId=:deptId AND u.username<>:requester
                ORDER BY e.fullname,u.username
                LIMIT 1");
            $this->db->bind("deptId", (string) $request["deptId"]);
            $this->db->bind("requester", (string) $request["requested_by"]);
            $departmentManager = $this->db->single();
            $approver = $departmentManager["username"] ?? null;
            if (!$approver) {
                throw new RuntimeException("No independent Department Manager is available for this request.");
            }
            $nextSteps[] = ["MANAGER", $approver];

            // Returning a request to the Section Head must recreate the
            // remaining IT approval. Otherwise Section Head approval would
            // incorrectly send the request directly to the Issuer.
            $itApprover = $this->firstRoleUser(
                "IT_MANAGER",
                (string) $request["requested_by"],
            );
            if (!$itApprover) {
                throw new RuntimeException(
                    "No independent IT Manager is available for this request.",
                );
            }
            $nextSteps[] = ["IT_MANAGER", $itApprover];
        } elseif ($targetStatus === "PENDING_IT_APPROVAL") {
            $approver = $this->firstRoleUser("IT_MANAGER", (string) $request["requested_by"]);
            if (!$approver) {
                throw new RuntimeException("No independent IT Manager is available for this request.");
            }
            $nextSteps[] = ["IT_MANAGER", $approver];
        }

        $this->db->beginTransaction();
        try {
            // Recheck under a row lock so an Administrator cannot move approval
            // backward while an Issuer is committing a partial batch.
            $this->db->query("SELECT r.status,
                (SELECT COUNT(*)
                 FROM asset_request_allocation allocation
                 JOIN asset_request_item item
                   ON item.request_item_id=allocation.request_item_id
                 WHERE item.request_id=r.request_id) AS allocated_count
                FROM asset_request r
                WHERE r.request_id=:lockedRequestId
                FOR UPDATE");
            $this->db->bind("lockedRequestId", $requestId);
            $lockedRequest = $this->db->single();
            if (
                !$lockedRequest ||
                (string) $lockedRequest["status"] !== $fromStatus ||
                (int) ($lockedRequest["allocated_count"] ?? 0) > 0
            ) {
                throw new RuntimeException(
                    "Approval status cannot be changed after asset issuance has started.",
                );
            }
            $this->db->query("UPDATE asset_request_approval_step
                SET status='SKIPPED',acted_by=:actor,acted_at=NOW(),remarks=:remarks
                WHERE request_id=:requestId AND status IN ('WAITING','QUEUED')");
            $this->db->bind("actor", $actor);
            $this->db->bind("remarks", substr($reason, 0, 500));
            $this->db->bind("requestId", $requestId);
            $this->db->executeOrFail();
            foreach ($nextSteps as $index => $nextStep) {
                $this->insertApprovalStep(
                    $requestId,
                    $nextStep[0],
                    $nextStep[1],
                    $index === 0 ? "WAITING" : "QUEUED",
                );
            }
            if (
                in_array(
                    $targetStatus,
                    [
                        "PENDING_DEPARTMENT_APPROVAL",
                        "PENDING_IT_APPROVAL",
                    ],
                    true,
                )
            ) {
                // A backward move requires a fresh IT review of quantities and
                // specifications before the Issuer receives the request.
                $this->db->query("UPDATE asset_request_item
                    SET approved_quantity=NULL,it_specification=NULL,
                        item_status='REQUESTED'
                    WHERE request_id=:requestId");
                $this->db->bind("requestId", $requestId);
                $this->db->executeOrFail();
            }
            if ($targetStatus === "APPROVED") {
                $this->db->query("UPDATE asset_request_item
                    SET approved_quantity=quantity,item_status='APPROVED'
                    WHERE request_id=:requestId AND (approved_quantity IS NULL OR approved_quantity=0)");
                $this->db->bind("requestId", $requestId);
                $this->db->executeOrFail();
            }
            $this->db->query("UPDATE asset_request
                SET status=:status,rejection_reason=NULL,
                    department_approved_by=CASE
                        WHEN :clearDepartmentApproval=1 THEN NULL
                        ELSE department_approved_by
                    END,
                    department_approved_at=CASE
                        WHEN :clearDepartmentApprovalDate=1 THEN NULL
                        ELSE department_approved_at
                    END,
                    it_approved_by=CASE
                        WHEN :clearItApproval=1 THEN NULL
                        ELSE it_approved_by
                    END,
                    it_approved_at=CASE
                        WHEN :clearItApprovalDate=1 THEN NULL
                        ELSE it_approved_at
                    END
                WHERE request_id=:requestId");
            $this->db->bind("status", $targetStatus);
            $clearDepartmentApproval =
                $targetStatus === "PENDING_DEPARTMENT_APPROVAL" ? 1 : 0;
            $clearItApproval = in_array(
                $targetStatus,
                [
                    "PENDING_DEPARTMENT_APPROVAL",
                    "PENDING_IT_APPROVAL",
                ],
                true,
            )
                ? 1
                : 0;
            $this->db->bind(
                "clearDepartmentApproval",
                $clearDepartmentApproval,
            );
            $this->db->bind(
                "clearDepartmentApprovalDate",
                $clearDepartmentApproval,
            );
            $this->db->bind("clearItApproval", $clearItApproval);
            $this->db->bind(
                "clearItApprovalDate",
                $clearItApproval,
            );
            $this->db->bind("requestId", $requestId);
            $this->db->executeOrFail();
            $message = $reason;
            if ($nextSteps) {
                $message .= " Next approver: " . $nextSteps[0][1] . ".";
                if (isset($nextSteps[1])) {
                    $message .=
                        " Then IT approval: " . $nextSteps[1][1] . ".";
                }
            }
            $this->addEvent($requestId, "ADMIN_STATUS_OVERRIDE", $fromStatus, $targetStatus, $actor, $message);
            $this->db->commit();
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    public function reject(int $requestId, string $actor, string $reason): void
    {
        $reason = trim($reason);
        if ($reason === "") {
            throw new InvalidArgumentException("Enter a rejection reason.");
        }
        $request = $this->getRequest($requestId);
        if (!$request || !in_array($request["status"], ["PENDING_DEPARTMENT_APPROVAL", "PENDING_IT_APPROVAL"], true)) {
            throw new RuntimeException("This request can no longer be rejected.");
        }
        $from = $request["status"];
        $this->db->beginTransaction();
        try {
            $this->db->query("UPDATE asset_request SET status='REJECTED',rejection_reason=:reason
                WHERE request_id=:requestId AND status=:status");
            $this->db->bind("reason", substr($reason, 0, 500));
            $this->db->bind("requestId", $requestId);
            $this->db->bind("status", $from);
            $this->db->executeOrFail();
            if ($this->db->rowCount() !== 1) {
                throw new RuntimeException(
                    "The request status changed. Refresh and try again.",
                );
            }

            $this->db->query("UPDATE asset_request_approval_step
                SET status='REJECTED',acted_by=:actor,acted_at=NOW(),remarks=:reason
                WHERE request_id=:requestId AND status='WAITING'
                  AND approver_username=:approver");
            $this->db->bind("actor", $actor);
            $this->db->bind("approver", $actor);
            $this->db->bind("reason", substr($reason, 0, 500));
            $this->db->bind("requestId", $requestId);
            $this->db->executeOrFail();
            if ($this->db->rowCount() !== 1) {
                throw new RuntimeException(
                    "This rejection is no longer assigned to you.",
                );
            }

            $this->addEvent(
                $requestId,
                "REJECTED",
                $from,
                "REJECTED",
                $actor,
                $reason,
            );
            $this->db->commit();
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    /** Cancel an unissued equipment request while retaining its full audit trail. */
    public function cancelRequest(int $requestId, string $actor, string $reason): void
    {
        $reason = trim($reason);
        if ($reason === "") {
            throw new InvalidArgumentException("Enter a cancellation reason.");
        }

        $this->db->beginTransaction();
        try {
            $this->db->query(
                "SELECT r.status,
                    (SELECT COUNT(*) FROM asset_request_allocation allocation
                     JOIN asset_request_item item
                       ON item.request_item_id=allocation.request_item_id
                     WHERE item.request_id=r.request_id) AS allocation_count
                 FROM asset_request r
                 WHERE r.request_id=:requestId
                 FOR UPDATE",
            );
            $this->db->bind("requestId", $requestId);
            $request = $this->db->single();
            if (!$request) {
                throw new RuntimeException("Request not found.");
            }

            $fromStatus = (string) $request["status"];
            if (
                !in_array(
                    $fromStatus,
                    [
                        "PENDING_DEPARTMENT_APPROVAL",
                        "PENDING_IT_APPROVAL",
                        "APPROVED",
                    ],
                    true,
                ) ||
                (int) ($request["allocation_count"] ?? 0) > 0
            ) {
                throw new RuntimeException(
                    "Only an unissued request waiting for approval or issuance can be cancelled.",
                );
            }

            $this->db->query(
                "UPDATE asset_request
                 SET status='CANCELLED'
                 WHERE request_id=:requestId",
            );
            $this->db->bind("requestId", $requestId);
            $this->db->executeOrFail();

            $this->db->query(
                "UPDATE asset_request_approval_step
                 SET status='CANCELLED',acted_by=:actor,acted_at=NOW(),remarks=:reason
                 WHERE request_id=:requestId AND status IN ('WAITING','QUEUED')",
            );
            $this->db->bind("actor", $actor);
            $this->db->bind("reason", substr($reason, 0, 500));
            $this->db->bind("requestId", $requestId);
            $this->db->executeOrFail();

            $this->addEvent(
                $requestId,
                "ADMIN_CANCELLED",
                $fromStatus,
                "CANCELLED",
                $actor,
                $reason,
            );
            $this->db->commit();
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    /**
     * Unified equipment- and return-request transaction history.
     *
     * The query is bounded for shared-hosting safety and deliberately remains
     * separate from the physical asset lifecycle audit.
     */
    public function getRequestTransactionHistory(array $filters, int $limit = 1000): array
    {
        $limit = max(1, min($limit, 10000));
        $equipmentEvents = "SELECT
                'EQUIPMENT' AS requestType,
                r.request_no AS referenceNo,
                COALESCE(allocation.assets,'') AS assetNum,
                COALESCE(allocation.descriptions,'') AS assetDescription,
                ev.event_type AS eventType,
                ev.from_status AS fromStatus,
                ev.to_status AS toStatus,
                ev.actor_username AS actorUsername,
                COALESCE(actor.nama,ev.actor_username) AS actor,
                r.requested_by AS requestedBy,
                COALESCE(requester.nama,r.requested_by) AS requester,
                CASE WHEN r.request_target_type='DEPARTMENT'
                     THEN d.deptName ELSE COALESCE(employee.fullname,'-') END AS requestedFor,
                d.deptId AS departmentCode,
                d.deptName AS department,
                ev.remarks AS details,
                ev.created_at AS eventDate
             FROM asset_request_event ev
             JOIN asset_request r ON r.request_id=ev.request_id
             LEFT JOIN users actor ON actor.username=ev.actor_username
             LEFT JOIN users requester ON requester.username=r.requested_by
             LEFT JOIN employee employee ON employee.empId=r.requested_for
             LEFT JOIN department d ON d.deptId=r.deptId
             LEFT JOIN (
                SELECT item.request_id,
                    GROUP_CONCAT(DISTINCT allocation.assetNum
                        ORDER BY allocation.assetNum SEPARATOR ', ') AS assets,
                    GROUP_CONCAT(DISTINCT asset.Description
                        ORDER BY asset.Description SEPARATOR '; ') AS descriptions
                FROM asset_request_item item
                JOIN asset_request_allocation allocation
                  ON allocation.request_item_id=item.request_item_id
                JOIN assetdata asset ON asset.assetNum=allocation.assetNum
                GROUP BY item.request_id
             ) allocation ON allocation.request_id=r.request_id
             WHERE r.status IN ('COMPLETED','CANCELLED','REJECTED')";

        $returnBase = " FROM asset_return_request rr
             JOIN asset_issue issue_record ON issue_record.issue_id=rr.issue_id
             JOIN assetdata asset ON asset.assetNum=issue_record.assetNum
             LEFT JOIN employee employee ON employee.empId=issue_record.empId
             LEFT JOIN department issue_department
               ON issue_department.deptId=issue_record.deptId
             LEFT JOIN department employee_department
               ON employee_department.deptId=employee.deptId
             LEFT JOIN users requester ON requester.username=rr.requested_by ";

        $returnCreated = "SELECT
                'RETURN' AS requestType,
                rr.return_request_no AS referenceNo,
                issue_record.assetNum AS assetNum,
                asset.Description AS assetDescription,
                'RETURN_REQUEST_CREATED' AS eventType,
                NULL AS fromStatus,
                'PENDING' AS toStatus,
                rr.requested_by AS actorUsername,
                COALESCE(requester.nama,rr.requested_by) AS actor,
                rr.requested_by AS requestedBy,
                COALESCE(requester.nama,rr.requested_by) AS requester,
                COALESCE(employee.fullname,issue_department.deptName,'-') AS requestedFor,
                COALESCE(issue_department.deptId,employee_department.deptId) AS departmentCode,
                COALESCE(issue_department.deptName,employee_department.deptName,'-') AS department,
                rr.reason AS details,
                rr.created_at AS eventDate" . $returnBase .
                " WHERE rr.status IN ('COMPLETED','CANCELLED')";

        $returnCompleted = "SELECT
                'RETURN' AS requestType,
                rr.return_request_no AS referenceNo,
                issue_record.assetNum AS assetNum,
                asset.Description AS assetDescription,
                'RETURN_REQUEST_COMPLETED' AS eventType,
                'PENDING' AS fromStatus,
                'COMPLETED' AS toStatus,
                rr.processed_by AS actorUsername,
                COALESCE(processor.nama,rr.processed_by) AS actor,
                rr.requested_by AS requestedBy,
                COALESCE(requester.nama,rr.requested_by) AS requester,
                COALESCE(employee.fullname,issue_department.deptName,'-') AS requestedFor,
                COALESCE(issue_department.deptId,employee_department.deptId) AS departmentCode,
                COALESCE(issue_department.deptName,employee_department.deptName,'-') AS department,
                CONCAT('Return completed',
                    CASE WHEN actual_return.return_no IS NOT NULL
                         THEN CONCAT(': ',actual_return.return_no) ELSE '' END) AS details,
                rr.processed_at AS eventDate" . $returnBase .
                " LEFT JOIN users processor ON processor.username=rr.processed_by
                  LEFT JOIN asset_return actual_return ON actual_return.return_id=rr.return_id
                  WHERE rr.status='COMPLETED' AND rr.processed_at IS NOT NULL";

        $returnCancelled = "SELECT
                'RETURN' AS requestType,
                rr.return_request_no AS referenceNo,
                issue_record.assetNum AS assetNum,
                asset.Description AS assetDescription,
                'RETURN_REQUEST_CANCELLED' AS eventType,
                'PENDING' AS fromStatus,
                'CANCELLED' AS toStatus,
                rr.cancelled_by AS actorUsername,
                COALESCE(canceller.nama,rr.cancelled_by) AS actor,
                rr.requested_by AS requestedBy,
                COALESCE(requester.nama,rr.requested_by) AS requester,
                COALESCE(employee.fullname,issue_department.deptName,'-') AS requestedFor,
                COALESCE(issue_department.deptId,employee_department.deptId) AS departmentCode,
                COALESCE(issue_department.deptName,employee_department.deptName,'-') AS department,
                rr.cancellation_reason AS details,
                rr.cancelled_at AS eventDate" . $returnBase .
                " LEFT JOIN users canceller ON canceller.username=rr.cancelled_by
                  WHERE rr.status='CANCELLED' AND rr.cancelled_at IS NOT NULL";

        $where = ["history.eventDate BETWEEN :fromDate AND :toDate"];
        $bindings = [
            "fromDate" => (string) $filters["from"] . " 00:00:00",
            "toDate" => (string) $filters["to"] . " 23:59:59",
        ];
        foreach (
            [
                "requestType" => "history.requestType",
                "eventType" => "history.eventType",
                "status" => "history.toStatus",
                "department" => "history.departmentCode",
            ] as $filter => $column
        ) {
            $value = trim((string) ($filters[$filter] ?? ""));
            if ($value !== "") {
                $where[] = $column . "=:" . $filter;
                $bindings[$filter] = $value;
            }
        }
        $actor = trim((string) ($filters["actor"] ?? ""));
        if ($actor !== "") {
            $where[] = "(history.actorUsername LIKE :actor OR history.actor LIKE :actorName)";
            $bindings["actor"] = "%" . $actor . "%";
            $bindings["actorName"] = "%" . $actor . "%";
        }
        $search = trim((string) ($filters["search"] ?? ""));
        if ($search !== "") {
            $where[] = "(history.referenceNo LIKE :searchReference
                OR history.assetNum LIKE :searchAsset
                OR history.assetDescription LIKE :searchDescription
                OR history.requestedFor LIKE :searchRequestedFor
                OR history.requester LIKE :searchRequester
                OR history.details LIKE :searchDetails)";
            foreach (
                [
                    "searchReference",
                    "searchAsset",
                    "searchDescription",
                    "searchRequestedFor",
                    "searchRequester",
                    "searchDetails",
                ] as $binding
            ) {
                $bindings[$binding] = "%" . $search . "%";
            }
        }

        $sql = "SELECT history.* FROM (" .
            $equipmentEvents . " UNION ALL " .
            $returnCreated . " UNION ALL " .
            $returnCompleted . " UNION ALL " .
            $returnCancelled .
            ") history WHERE " . implode(" AND ", $where) .
            " ORDER BY history.eventDate DESC,history.referenceNo DESC LIMIT :historyLimit";
        $this->db->query($sql);
        foreach ($bindings as $name => $value) {
            $this->db->bind($name, $value);
        }
        $this->db->bind("historyLimit", $limit, PDO::PARAM_INT);
        return $this->db->resultSet();
    }

    public function getNotificationSummary(array $context): array
    {
        $username = (string) ($context["username"] ?? "");
        $roles = $context["roles"] ?? [];
        $employeeId = (int) ($context["empId"] ?? 0);
        $this->db->query("SELECT COUNT(*) AS total FROM asset_request_approval_step
            WHERE approver_username=:username AND status='WAITING'");
        $this->db->bind("username", $username);
        $total = (int) ($this->db->single()["total"] ?? 0);
        $this->db->query("SELECT COUNT(*) AS total
            FROM asset_request r
            LEFT JOIN department d ON d.deptId=r.deptId
            LEFT JOIN users department_user
              ON department_user.empId=COALESCE(
                  d.headEmpId,
                  (
                      SELECT MIN(legacy_head.empId)
                      FROM department_head legacy_head
                      JOIN employee legacy_employee
                        ON legacy_employee.empId=legacy_head.empId
                       AND legacy_employee.aktif=1
                      WHERE legacy_head.deptId=d.deptId
                  )
              ) AND department_user.active=1
            WHERE NOT EXISTS (
                SELECT 1 FROM asset_request_approval_step s
                WHERE s.request_id=r.request_id
            ) AND (
                (r.status='PENDING_DEPARTMENT_APPROVAL'
                  AND department_user.username=:managerUsername)
                OR
                (r.status='PENDING_IT_APPROVAL' AND EXISTS (
                    SELECT 1 FROM user_role ur
                    WHERE ur.username=:itUsername
                      AND ur.role_code='IT_MANAGER'
                ))
            )");
        $this->db->bind("managerUsername", $username);
        $this->db->bind("itUsername", $username);
        $total += (int) ($this->db->single()["total"] ?? 0);
        if (in_array("ISSUER", $roles, true)) {
            // Count only requests this issuer is actually allowed to issue.
            // Own requests and requests where the issuer is the receiver are
            // deliberately excluded to match Request::canIssue.
            $this->db->query("SELECT COUNT(*) AS total
                FROM asset_request r
                LEFT JOIN department d ON d.deptId=r.deptId
                WHERE r.status='APPROVED'
                  AND r.requested_by<>:issuerUsername
                  AND (
                    COALESCE(r.receiver_emp_id,r.requested_for,d.headEmpId) IS NULL
                    OR COALESCE(r.receiver_emp_id,r.requested_for,d.headEmpId)<>:issuerEmpId
                  )");
            $this->db->bind("issuerUsername", $username);
            $this->db->bind("issuerEmpId", $employeeId);
            $total += (int) ($this->db->single()["total"] ?? 0);
        }
        if ($employeeId > 0) {
            $this->db->query("SELECT COUNT(*) AS total FROM asset_request r
                LEFT JOIN department d ON d.deptId=r.deptId
                WHERE r.status='PENDING_RECEIVER_ACCEPTANCE'
                  AND COALESCE(r.receiver_emp_id,r.requested_for,d.headEmpId)=:receiverEmpId");
            $this->db->bind("receiverEmpId", $employeeId);
            $total += (int) ($this->db->single()["total"] ?? 0);
        }
        return ["count" => $total, "url" => BASEURL . "/Request?pending=1"];
    }

    public function getAvailableAssets(int $requestId): array
    {
        $this->db->query("SELECT ri.request_item_id,ri.assetKat,k.Description AS categoryName,
            ri.approved_quantity,a.assetNum,a.Description,a.serial_number,a.manufacturer,a.model
            FROM asset_request_item ri JOIN assetkat k ON k.assetKat=ri.assetKat
            LEFT JOIN assetdata a ON a.assetKat=ri.assetKat AND a.assetStat='IN_STOCK'
            WHERE ri.request_id=:requestId AND ri.approved_quantity>0
            ORDER BY k.Description,a.assetNum");
        $this->db->bind("requestId", $requestId);
        return $this->db->resultSet();
    }

    /** Remote search for one approved request line. */
    public function searchAvailableAssetsForItem(
        int $requestId,
        int $requestItemId,
        string $term,
        int $limit = 30
    ): array {
        $term = mb_substr(trim($term), 0, 100);
        if (mb_strlen($term) < 2) {
            return [];
        }
        $limit = max(1, min($limit, 50));
        $this->db->query("SELECT
            a.assetNum,
            a.Description,
            a.serial_number,
            a.manufacturer,
            a.model
        FROM asset_request_item ri
        JOIN assetdata a
          ON a.assetKat=ri.assetKat
         AND a.assetStat='IN_STOCK'
        WHERE ri.request_id=:requestId
          AND ri.request_item_id=:requestItemId
          AND ri.approved_quantity>0
          AND (
              a.assetNum LIKE :requestAssetSearch
              OR a.serial_number LIKE :requestSerialSearch
              OR a.Description LIKE :requestDescriptionSearch
          )
          AND NOT EXISTS (
              SELECT 1
              FROM asset_issue i
              WHERE i.assetNum=a.assetNum
                AND i.status='ISSUED'
          )
        ORDER BY
          CASE WHEN a.assetNum=:requestExactAsset THEN 0 ELSE 1 END,
          a.assetNum
        LIMIT :requestAssetLimit");
        $this->db->bind("requestId", $requestId);
        $this->db->bind("requestItemId", $requestItemId);
        $this->db->bind("requestAssetSearch", $term . "%");
        $this->db->bind("requestSerialSearch", $term . "%");
        $this->db->bind("requestDescriptionSearch", $term . "%");
        $this->db->bind("requestExactAsset", $term);
        $this->db->bind("requestAssetLimit", $limit);
        return $this->db->resultSet();
    }

    /** Validate one selected asset at submission time to prevent stale allocation. */
    public function isAssetAvailableForRequestItem(
        int $requestId,
        int $requestItemId,
        string $assetNum
    ): bool {
        $this->db->query("SELECT 1
        FROM asset_request_item ri
        JOIN assetdata a
          ON a.assetKat=ri.assetKat
         AND a.assetStat='IN_STOCK'
        WHERE ri.request_id=:requestId
          AND ri.request_item_id=:requestItemId
          AND ri.approved_quantity>0
          AND a.assetNum=:assetNum
          AND NOT EXISTS (
              SELECT 1
              FROM asset_issue i
              WHERE i.assetNum=a.assetNum
                AND i.status='ISSUED'
          )
        LIMIT 1");
        $this->db->bind("requestId", $requestId);
        $this->db->bind("requestItemId", $requestItemId);
        $this->db->bind("assetNum", $assetNum);
        return (bool) $this->db->single();
    }

    public function accept(int $requestId, string $actor, string $comment): void
    {
        $this->db->beginTransaction();
        try {
            $this->db->query("SELECT r.status,COALESCE(SUM(GREATEST(
                    COALESCE(item.approved_quantity,0) -
                    (SELECT COUNT(*) FROM asset_request_allocation allocation
                     WHERE allocation.request_item_id=item.request_item_id),
                    0
                )),0) AS remaining
                FROM asset_request r
                JOIN asset_request_item item ON item.request_id=r.request_id
                WHERE r.request_id=:lockedRequestId
                GROUP BY r.request_id,r.status
                FOR UPDATE");
            $this->db->bind("lockedRequestId", $requestId);
            $request = $this->db->single();
            if (!$request || (string) $request["status"] !== "PENDING_RECEIVER_ACCEPTANCE") {
                throw new RuntimeException("This request is not awaiting receiver acceptance.");
            }
            if ((int) ($request["remaining"] ?? 0) > 0) {
                throw new RuntimeException(
                    "Receiver acceptance is unavailable until every approved asset is issued.",
                );
            }

            $this->db->query("UPDATE asset_request SET status='COMPLETED',receiver_accepted_at=NOW(),
                receiver_accepted_by=:actor,receiver_comment=:comment
                WHERE request_id=:requestId AND status='PENDING_RECEIVER_ACCEPTANCE'");
            $this->db->bind("actor", $actor);
            $this->db->bind("comment", trim($comment) ?: null);
            $this->db->bind("requestId", $requestId);
            $this->db->executeOrFail();
            if ($this->db->rowCount() !== 1) {
                throw new RuntimeException("This request is not awaiting receiver acceptance.");
            }
            $this->addEvent(
                $requestId,
                "RECEIVER_ACCEPTED",
                "PENDING_RECEIVER_ACCEPTANCE",
                "COMPLETED",
                $actor,
                trim($comment) ?: null,
            );
            $this->db->commit();
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    private function transition(int $requestId, string $from, string $to, string $actor,
        string $event, ?string $remarks, string $extraSet): void
    {
        $this->db->query("UPDATE asset_request SET status=:toStatus,$extraSet
            WHERE request_id=:requestId AND status=:fromStatus");
        $this->db->bind("toStatus", $to);
        $this->db->bind("actor", $actor);
        $this->db->bind("requestId", $requestId);
        $this->db->bind("fromStatus", $from);
        $this->db->executeOrFail();
        if ($this->db->rowCount() !== 1) {
            throw new RuntimeException("The request status changed. Refresh and try again.");
        }
        $this->addEvent($requestId, $event, $from, $to, $actor, $remarks);
    }

    private function approveCurrentManagerStep(
        int $requestId,
        string $actor,
        ?string $remarks,
    ): void {
        if (!$this->isCurrentApprover($requestId, $actor, "MANAGER")) {
            throw new RuntimeException("This request is assigned to another approver.");
        }
        if (!$this->hasApprovalRoute($requestId)) {
            $this->transition(
                $requestId,
                "PENDING_DEPARTMENT_APPROVAL",
                "PENDING_IT_APPROVAL",
                $actor,
                "DEPARTMENT_APPROVED",
                $remarks,
                "department_approved_by=:actor,department_approved_at=NOW()",
            );
            return;
        }
        $this->db->beginTransaction();
        try {
            $this->completeWaitingStep($requestId, $actor, $remarks);
            $next = $this->activateNextStep($requestId);
            $toStatus = $next
                ? ($next["step_type"] === "IT_MANAGER"
                    ? "PENDING_IT_APPROVAL"
                    : "PENDING_DEPARTMENT_APPROVAL")
                : "APPROVED";
            $this->db->query("UPDATE asset_request SET status=:status,
                department_approved_by=:actor,department_approved_at=NOW()
                WHERE request_id=:requestId AND status='PENDING_DEPARTMENT_APPROVAL'");
            $this->db->bind("status", $toStatus);
            $this->db->bind("actor", $actor);
            $this->db->bind("requestId", $requestId);
            $this->db->executeOrFail();
            $message = $remarks;
            if ($next) {
                $message = trim((string) $remarks);
                $message .= ($message !== "" ? "\n" : "") .
                    "Next: " . $next["approver_username"];
            }
            $this->addEvent(
                $requestId,
                "MANAGER_APPROVED",
                "PENDING_DEPARTMENT_APPROVAL",
                $toStatus,
                $actor,
                $message ?: null,
            );
            $this->db->commit();
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    private function completeWaitingStep(
        int $requestId,
        string $actor,
        ?string $remarks,
    ): void {
        $this->db->query("UPDATE asset_request_approval_step
            SET status='APPROVED',acted_by=:actor,acted_at=NOW(),remarks=:remarks
            WHERE request_id=:requestId AND approver_username=:approver
              AND status='WAITING'");
        $this->db->bind("actor", $actor);
        $this->db->bind("approver", $actor);
        $this->db->bind("remarks", trim((string) $remarks) ?: null);
        $this->db->bind("requestId", $requestId);
        $this->db->executeOrFail();
        if ($this->db->rowCount() !== 1) {
            throw new RuntimeException("This approval is no longer assigned to you.");
        }
    }

    private function hasApprovalRoute(int $requestId): bool
    {
        $this->db->query("SELECT 1 FROM asset_request_approval_step
            WHERE request_id=:requestId LIMIT 1");
        $this->db->bind("requestId", $requestId);
        return (bool) $this->db->single();
    }

    private function activateNextStep(int $requestId): ?array
    {
        $this->db->query("SELECT approval_step_id,step_type,approver_username
            FROM asset_request_approval_step
            WHERE request_id=:requestId AND status='QUEUED'
            ORDER BY step_order LIMIT 1");
        $this->db->bind("requestId", $requestId);
        $next = $this->db->single();
        if (!$next) {
            return null;
        }
        $this->db->query("UPDATE asset_request_approval_step SET status='WAITING'
            WHERE approval_step_id=:stepId AND status='QUEUED'");
        $this->db->bind("stepId", (int) $next["approval_step_id"]);
        $this->db->executeOrFail();
        return $next;
    }

    private function createApprovalRoute(
        int $requestId,
        string $requester,
        ?int $requestedFor,
        string $deptId,
    ): array {
        $requesterRoles = $this->rolesForUser($requester);

        // An IT Manager's own submitted request goes directly to the issuer.
        // No approval step is assigned to the requester.
        if (in_array("IT_MANAGER", $requesterRoles, true)) {
            return [
                "status" => "APPROVED",
                "message" => "IT Manager request: approval steps skipped; waiting for Issuer to issue.",
            ];
        }

        $route = [];
        // A Department Manager's request skips the department step and goes
        // to IT. Other users are routed to the single Section Head account.
        if (!in_array("DEPARTMENT_MANAGER", $requesterRoles, true)) {
            $this->db->query("SELECT u.username
                FROM department d
                JOIN employee e ON e.empId=COALESCE(
                    d.headEmpId,
                    (
                        SELECT MIN(legacy_head.empId)
                        FROM department_head legacy_head
                        JOIN employee legacy_employee
                          ON legacy_employee.empId=legacy_head.empId
                         AND legacy_employee.aktif=1
                        WHERE legacy_head.deptId=d.deptId
                    )
                ) AND e.aktif=1
                JOIN users u ON u.empId=e.empId AND u.active=1
                WHERE d.deptId=:deptId AND u.username<>:requester
                ORDER BY e.fullname,u.username LIMIT 1");
            $this->db->bind("deptId", $deptId);
            $this->db->bind("requester", $requester);
            $departmentManager = $this->db->single();
            if ($departmentManager) {
                $route[] = [
                    "MANAGER",
                    (string) $departmentManager["username"],
                ];
            }
        }

        $itApprover = $this->firstRoleUser("IT_MANAGER", $requester);
        if (!$itApprover) {
            throw new RuntimeException("No independent IT Manager is configured.");
        }
        // When the Department Manager is also the IT Manager, one IT approval
        // is sufficient; do not assign two consecutive steps to the same user.
        if (
            $route &&
            (string) $route[count($route) - 1][1] === $itApprover
        ) {
            array_pop($route);
        }
        $route[] = ["IT_MANAGER", $itApprover];

        foreach ($route as $index => [$stepType, $username]) {
            $this->insertApprovalStep($requestId, $stepType, $username, $index === 0 ? "WAITING" : "QUEUED");
        }
        $first = $route[0];
        return [
            "status" => $first[0] === "MANAGER" ? "PENDING_DEPARTMENT_APPROVAL" : "PENDING_IT_APPROVAL",
            "message" => "Next approver: " . $first[1],
        ];
    }

    private function insertApprovalStep(int $requestId, string $stepType, string $username, string $status): void
    {
        $this->db->query("SELECT COALESCE(MAX(step_order),0)+1 AS next_order FROM asset_request_approval_step WHERE request_id=:requestId");
        $this->db->bind("requestId", $requestId);
        $order = (int) ($this->db->single()["next_order"] ?? 1);
        $this->db->query("INSERT INTO asset_request_approval_step
            (request_id,step_order,step_type,approver_username,status)
            VALUES (:requestId,:stepOrder,:stepType,:username,:status)");
        $this->db->bind("requestId", $requestId);
        $this->db->bind("stepOrder", $order);
        $this->db->bind("stepType", $stepType);
        $this->db->bind("username", $username);
        $this->db->bind("status", $status);
        $this->db->executeOrFail();
    }

    private function rolesForUser(string $username): array
    {
        $this->db->query("SELECT role_code FROM user_role WHERE username=:username ORDER BY role_code");
        $this->db->bind("username", $username);
        return array_column($this->db->resultSet(), "role_code");
    }

    private function usernameForEmployee(int $employeeId): ?string
    {
        $this->db->query("SELECT username FROM users
            WHERE empId=:empId AND active=1 ORDER BY username LIMIT 1");
        $this->db->bind("empId", $employeeId);
        $row = $this->db->single();
        return $row ? (string) $row["username"] : null;
    }

    private function firstRoleUser(string $role, string $exclude): ?string
    {
        $this->db->query("SELECT u.username FROM users u
            JOIN user_role ur ON ur.username=u.username
            WHERE ur.role_code=:role AND u.active=1 AND u.username<>:exclude
            ORDER BY (LOWER(u.username)='admin') ASC,u.username LIMIT 1");
        $this->db->bind("role", $role);
        $this->db->bind("exclude", $exclude);
        $row = $this->db->single();
        return $row ? (string) $row["username"] : null;
    }

    private function addEvent(int $requestId, string $event, ?string $from, ?string $to,
        string $actor, ?string $remarks): void
    {
        $this->db->query("INSERT INTO asset_request_event
            (request_id,event_type,from_status,to_status,actor_username,remarks)
            VALUES (:requestId,:event,:fromStatus,:toStatus,:actor,:remarks)");
        $this->db->bind("requestId", $requestId);
        $this->db->bind("event", $event);
        $this->db->bind("fromStatus", $from);
        $this->db->bind("toStatus", $to);
        $this->db->bind("actor", $actor);
        $this->db->bind("remarks", $remarks ? substr($remarks, 0, 500) : null);
        $this->db->executeOrFail();
    }
}
