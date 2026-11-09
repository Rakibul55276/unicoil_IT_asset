<?php
class Employee_model
{
    private $db;
    private $table = "employee";

    public function __construct()
    {
        $this->db = new Database();
    }

    // Count active employees
    public function countActiveEmployee()
    {
        // Query the correct table and column
        $this->db->query("SELECT COUNT(*) AS total FROM employee WHERE aktif = 1");
        $result = $this->db->single();

        // Return the total as an array
        return is_array($result) && isset($result["total"])
            ? ["total" => (int) $result["total"]]
            : ["total" => 0];
    }

    // Get all employees
    public function getAllEmployee()
    {
        $this->db->query("SELECT * FROM {$this->table}");
        return $this->db->resultSet();
    }

    // Get all employees with assets
    // Get all employees with assets issued directly to users only
    public function getAllEmployeeWithAssets()
    {
        $sql = "
        SELECT
            e.empId,
            e.fullname,
            e.email,
            e.position,
            e.area,
            e.building,
            e.extension_number,
            e.section,
            e.aktif,

            CASE
                WHEN e.aktif = 1 THEN 'Active'
                ELSE 'Inactive'
            END AS employeeStatus,

            COALESCE(employee_dept.deptName, head_department.deptName, e.department) AS department,
            COALESCE(assigned.assignedAssetCount, 0) AS assignedAssetCount,
            COALESCE(assigned.assignmentIssueIds, '') AS assignmentIssueIds,
            COALESCE(assigned.assetNums, '-') AS assetNums,
            COALESCE(assigned.assetDescriptions, '-') AS assetDescriptions,
            COALESCE(assigned.assetCategories, '-') AS assetCategories

        FROM employee e

        LEFT JOIN department employee_dept
            ON employee_dept.deptId = e.deptId

        LEFT JOIN (
            SELECT headEmpId, MIN(deptName) AS deptName
            FROM department
            WHERE headEmpId IS NOT NULL
            GROUP BY headEmpId
        ) head_department ON head_department.headEmpId = e.empId

        LEFT JOIN (
            SELECT
                i.empId,
                COUNT(*) AS assignedAssetCount,
                GROUP_CONCAT(i.issue_id ORDER BY i.issue_date, i.issue_id SEPARATOR ',') AS assignmentIssueIds,
                GROUP_CONCAT(i.assetNum ORDER BY i.assetNum SEPARATOR ',') AS assetNums,
                GROUP_CONCAT(a.Description ORDER BY i.assetNum SEPARATOR ', ') AS assetDescriptions,
                GROUP_CONCAT(k.Description ORDER BY i.assetNum SEPARATOR ', ') AS assetCategories
            FROM asset_issue i
            JOIN assetdata a ON a.assetNum = i.assetNum
            LEFT JOIN assetkat k ON k.assetKat = a.assetKat
            WHERE i.assignee_type = 'USER'
              AND i.status = 'ISSUED'
            GROUP BY i.empId
        ) assigned ON assigned.empId = e.empId

        WHERE e.aktif IN (0, 1)

        ORDER BY COALESCE(employee_dept.deptName, head_department.deptName, e.department) ASC,
                 e.fullname ASC
    ";

        $this->db->query($sql);

        return $this->db->resultSet();
    }

    // Get single employee by ID
    public function getEmployeeByID($id)
    {
        $this->db->query(
            "SELECT empId, fullname, email, position, area, building, extension_number, section, aktif, department, deptId, supervisorEmpId, firstname, lastname FROM {$this->table} WHERE empId = :empId",
        );
        $this->db->bind("empId", $id);
        return $this->db->single();
    }

    public function getEmployeeAssetTransactions($id)
    {
        $this
            ->db->query("SELECT i.issue_id,i.issue_no,i.batch_no,i.issue_date,i.expected_return_date,
            i.condition_out,i.purpose,i.status AS issue_status,i.assetNum,
            a.Description AS asset_description,a.serial_number,
            k.Description AS category_name,
            r.return_id,r.return_no,r.return_date,r.condition_in,r.reason
            FROM asset_issue i
            INNER JOIN assetdata a ON a.assetNum=i.assetNum
            LEFT JOIN assetkat k ON k.assetKat=a.assetKat
            LEFT JOIN asset_return r ON r.issue_id=i.issue_id
            WHERE i.empId=:empId AND i.assignee_type='USER'
            ORDER BY i.issue_date DESC,i.issue_id DESC");
        $this->db->bind("empId", (int) $id);
        return $this->db->resultSet();
    }

    public function saveData($data)
    {
        try {
            $data["empId"] = trim($data["empId"]);
            $data["fName"] = trim($data["fName"]);
            $data["lName"] = trim($data["lName"]);

            if ($data["empId"] === "" || !ctype_digit($data["empId"])) {
                return ["error" => "Employee ID must be numeric and not empty"];
            }
            if ($data["fName"] === "" || $data["lName"] === "") {
                return ["error" => "First and Last Name are required"];
            }
            $email = trim($data["email"] ?? "");
            if (
                $email === "" ||
                !filter_var($email, FILTER_VALIDATE_EMAIL) ||
                strlen($email) > 190
            ) {
                return ["error" => "Please enter a valid employee email address"];
            }

            $this->db->query(
                "SELECT deptId,deptName FROM department WHERE deptId=:deptId AND active=1",
            );
            $this->db->bind("deptId", $data["department"] ?? "");
            $dept = $this->db->single();
            if (!$dept) {
                return ["error" => "Please select a valid active department"];
            }
            $supervisorId = (int) ($data["supervisorEmpId"] ?? 0);
            if ($supervisorId === (int) $data["empId"]) {
                return ["error" => "An employee cannot be their own superior"];
            }
            if ($supervisorId > 0) {
                $this->db->query("SELECT 1 FROM employee
                    WHERE empId=:empId AND aktif=1");
                $this->db->bind("empId", $supervisorId);
                if (!$this->db->single()) {
                    return ["error" => "Select a valid active employee as the superior manager"];
                }
            }

            // Duplicate check
            $this->db->query("SELECT 1 FROM {$this->table} WHERE empId = :empId LIMIT 1");
            $this->db->bind("empId", $data["empId"]);
            if ($this->db->single()) {
                return ["error" => "Employee ID {$data["empId"]} already exists"];
            }

            $this->db->query("
            INSERT INTO {$this->table}
            (empId, firstname, lastname, fullname, email, position, area, building, extension_number, section, department, deptId, supervisorEmpId, aktif)
            VALUES (:empId, :firstname, :lastname, :fullname, :email, :position, :area, :building, :extensionNumber, :section, :department, :deptId, :supervisorEmpId, :aktif)
        ");
            $this->db->bind("empId", $data["empId"]);
            $this->db->bind("firstname", $data["fName"]);
            $this->db->bind("lastname", $data["lName"]);
            $this->db->bind("fullname", $data["fName"] . " " . $data["lName"]);
            $this->db->bind("email", $email);
            $this->db->bind("position", trim($data["position"] ?? "") ?: null);
            $this->db->bind("area", trim($data["area"] ?? "") ?: null);
            $this->db->bind("building", trim($data["building"] ?? "") ?: null);
            $this->db->bind("extensionNumber", trim($data["extensionNumber"] ?? "") ?: null);
            $this->db->bind("section", trim($data["section"] ?? "") ?: null);
            $this->db->bind("department", $dept["deptName"]);
            $this->db->bind("deptId", $dept["deptId"]);
            $this->db->bind(
                "supervisorEmpId",
                $supervisorId ?: null,
            );
            $this->db->bind("aktif", $data["status"]);

            $this->db->execute();

            if ($this->db->rowCount() === 0) {
                return ["error" => "Employee was not saved. Please try again."];
            }

            return ["success" => true];
        } catch (Exception $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    public function updateData(
        $data,
        string $actor = "admin",
        bool $canEditEmployeeId = false
    )
    {
        $transactionStarted = false;
        try {
            $employeeId = trim((string) ($data["empId"] ?? ""));
            $originalEmployeeId = trim(
                (string) ($data["originalEmpId"] ?? $employeeId),
            );
            if (
                $employeeId === "" ||
                $originalEmployeeId === "" ||
                !ctype_digit($employeeId) ||
                !ctype_digit($originalEmployeeId) ||
                (int) $employeeId < 1 ||
                (int) $employeeId > 2147483647 ||
                (int) $originalEmployeeId < 1
            ) {
                return ["error" => "Employee ID must be a valid positive number"];
            }
            $employeeId = (int) $employeeId;
            $originalEmployeeId = (int) $originalEmployeeId;
            $employeeIdChanged = $employeeId !== $originalEmployeeId;
            if ($employeeIdChanged && !$canEditEmployeeId) {
                return [
                    "error" =>
                        "Only a System Administrator can change an Employee ID.",
                ];
            }

            $email = trim($data["email"] ?? "");
            if (
                $email === "" ||
                !filter_var($email, FILTER_VALIDATE_EMAIL) ||
                strlen($email) > 190
            ) {
                return ["error" => "Please enter a valid employee email address"];
            }
            $this->db->query("SELECT deptName FROM department WHERE deptId = :deptId");
            $this->db->bind("deptId", $data["department"]);
            $dept = $this->db->single();
            if (!$dept) {
                return ["error" => "Invalid department selected"];
            }
            $supervisorId = (int) ($data["supervisorEmpId"] ?? 0);
            if ($supervisorId === $employeeId) {
                return ["error" => "An employee cannot be their own superior"];
            }
            if ($supervisorId > 0) {
                $this->db->query("SELECT 1 FROM employee
                    WHERE empId=:empId AND aktif=1");
                $this->db->bind("empId", $supervisorId);
                if (!$this->db->single()) {
                    return ["error" => "Select a valid active employee as the superior manager"];
                }
            }

            $deptName = $dept["deptName"];
            $this->db->query(
                "SELECT deptId FROM {$this->table} WHERE empId=:empId",
            );
            $this->db->bind("empId", $originalEmployeeId);
            $existingEmployee = $this->db->single();
            if (!$existingEmployee) {
                return ["error" => "Employee not found"];
            }

            if ($employeeIdChanged) {
                $this->db->query(
                    "SELECT 1 FROM {$this->table} WHERE empId=:empId LIMIT 1",
                );
                $this->db->bind("empId", $employeeId);
                if ($this->db->single()) {
                    return ["error" => "Employee ID {$employeeId} already exists"];
                }
            }

            // Employee logins normally use the Employee ID as their username.
            // Rename only that exact convention; named accounts stay unchanged.
            $linkedLoginUsername = "";
            if ($employeeIdChanged) {
                $this->db->query(
                    "SELECT username FROM users
                     WHERE empId=:empId AND username=:username LIMIT 1",
                );
                $this->db->bind("empId", $originalEmployeeId);
                $this->db->bind("username", (string) $originalEmployeeId);
                $linkedLogin = $this->db->single();
                $linkedLoginUsername = (string) ($linkedLogin["username"] ?? "");
                if ($linkedLoginUsername !== "") {
                    $this->db->query(
                        "SELECT 1 FROM users WHERE username=:username LIMIT 1",
                    );
                    $this->db->bind("username", (string) $employeeId);
                    if ($this->db->single()) {
                        return [
                            "error" =>
                                "User login {$employeeId} already exists. Choose another Employee ID.",
                        ];
                    }
                }
            }

            $oldDepartment = (string) ($existingEmployee["deptId"] ?? "");
            $newDepartment = (string) $data["department"];
            $departmentChanged = $oldDepartment !== $newDepartment;

            $this->db->beginTransaction();
            $transactionStarted = true;

            if ($employeeIdChanged) {
                // All supported schemas use ON UPDATE CASCADE for Employee ID
                // references. If a deployment does not, MySQL rejects this
                // statement and the whole operation is rolled back.
                $this->db->query(
                    "UPDATE {$this->table} SET empId=:newEmpId
                     WHERE empId=:originalEmpId",
                );
                $this->db->bind("newEmpId", $employeeId);
                $this->db->bind("originalEmpId", $originalEmployeeId);
                $this->db->executeOrFail();
                if ($this->db->rowCount() !== 1) {
                    throw new RuntimeException("Employee ID was not updated.");
                }

                if ($linkedLoginUsername !== "") {
                    $this->db->query(
                        "UPDATE users SET username=:newUsername
                         WHERE username=:oldUsername",
                    );
                    $this->db->bind("newUsername", (string) $employeeId);
                    $this->db->bind("oldUsername", $linkedLoginUsername);
                    $this->db->executeOrFail();
                    if ($actor === $linkedLoginUsername) {
                        $actor = (string) $employeeId;
                    }
                }
            }

            $this->db->query("UPDATE {$this->table} 
                          SET firstname = :firstname,
                              lastname = :lastname,
                              fullname = :fullname,
                              email = :email,
                              position = :position,
                              area = :area,
                              building = :building,
                              extension_number = :extensionNumber,
                              section = :section,
                              department = :department,
                              deptId = :deptId,
                              supervisorEmpId = :supervisorEmpId,
                              aktif = :aktif
                          WHERE empId = :empId");
            $this->db->bind("empId", $employeeId);
            $this->db->bind("firstname", $data["fName"]);
            $this->db->bind("lastname", $data["lName"]);
            $this->db->bind("fullname", $data["fName"] . " " . $data["lName"]);
            $this->db->bind("email", $email);
            $this->db->bind("position", trim($data["position"] ?? "") ?: null);
            $this->db->bind("area", trim($data["area"] ?? "") ?: null);
            $this->db->bind("building", trim($data["building"] ?? "") ?: null);
            $this->db->bind("extensionNumber", trim($data["extensionNumber"] ?? "") ?: null);
            $this->db->bind("section", trim($data["section"] ?? "") ?: null);
            $this->db->bind("department", $deptName);
            $this->db->bind("deptId", $data["department"]);
            $this->db->bind(
                "supervisorEmpId",
                $supervisorId ?: null,
            );
            $this->db->bind("aktif", $data["status"]);
            $this->db->executeOrFail();

            if ($departmentChanged) {
                // Only requests waiting for their first manager decision are
                // rerouted. Approved/completed history stays unchanged.
                $this->db->query(
                    "SELECT request_id FROM asset_request
                    WHERE requested_for=:empId
                      AND request_target_type='USER'
                      AND status='PENDING_DEPARTMENT_APPROVAL'",
                );
                $this->db->bind("empId", $employeeId);
                $pendingRequests = $this->db->resultSet();

                $this->db->query(
                    "UPDATE asset_request
                    SET deptId=:deptId,work_location=:department
                    WHERE requested_for=:empId
                      AND request_target_type='USER'
                      AND status='PENDING_DEPARTMENT_APPROVAL'",
                );
                $this->db->bind("deptId", $newDepartment);
                $this->db->bind("department", $deptName);
                $this->db->bind("empId", $employeeId);
                $this->db->executeOrFail();

                foreach ($pendingRequests as $pendingRequest) {
                    $this->db->query(
                        "INSERT INTO asset_request_event
                        (request_id,event_type,from_status,to_status,
                         actor_username,remarks)
                        VALUES (:requestId,'DEPARTMENT_REROUTED',
                         'PENDING_DEPARTMENT_APPROVAL',
                         'PENDING_DEPARTMENT_APPROVAL',:actor,:remarks)",
                    );
                    $this->db->bind(
                        "requestId",
                        (int) $pendingRequest["request_id"],
                    );
                    $this->db->bind("actor", $actor);
                    $this->db->bind(
                        "remarks",
                        "Employee department changed from " .
                            $oldDepartment .
                            " to " .
                            $newDepartment .
                            " (" .
                            $deptName .
                            ").",
                    );
                    $this->db->executeOrFail();
                }

                // Active user assignments follow the employee's current
                // department. Returned and completed records remain historical.
                $this->db->query(
                    "UPDATE asset_issue
                    SET deptId=:deptId
                    WHERE empId=:empId
                      AND assignee_type='USER'
                      AND status='ISSUED'",
                );
                $this->db->bind("deptId", $newDepartment);
                $this->db->bind("empId", $employeeId);
                $this->db->executeOrFail();

                $this->db->query(
                    "UPDATE assetdata
                    SET deptId=:deptId
                    WHERE empId=:empId",
                );
                $this->db->bind("deptId", $newDepartment);
                $this->db->bind("empId", $employeeId);
                $this->db->executeOrFail();
            }

            $this->db->commit();
            $transactionStarted = false;
            return [
                "success" => true,
                "departmentChanged" => $departmentChanged,
                "employeeIdChanged" => $employeeIdChanged,
                "previousUsername" => $linkedLoginUsername,
                "renamedUsername" =>
                    $linkedLoginUsername !== "" ? (string) $employeeId : "",
            ];
        } catch (Throwable $e) {
            if ($transactionStarted) {
                $this->db->rollBack();
            }
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    // Delete employee
    public function deleteData($id)
    {
        $this->db->query("DELETE FROM {$this->table} WHERE empId = :empId");
        $this->db->bind("empId", $id);
        $this->db->execute();
        return $this->db->rowCount();
    }
}
?>
