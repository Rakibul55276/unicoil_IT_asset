<?php
class Department_model
{
    private $db;
    private $table = "department";

    public function __construct()
    {
        $this->db = new Database();
    }

    // Fetch all departments
    public function getAllDept()
    {
        $this->db->query("
        SELECT
            d.*,
            e.fullname AS headName,

            (
                SELECT COUNT(*)
                FROM asset_issue i
                WHERE i.deptId = d.deptId
                  AND i.assignee_type = 'DEPARTMENT'
                  AND i.status = 'ISSUED'
            ) AS assignedAssetCount,

            COALESCE(
                (
                    SELECT GROUP_CONCAT(
                        i.assetNum
                        ORDER BY i.assetNum
                        SEPARATOR ','
                    )
                    FROM asset_issue i
                    WHERE i.deptId = d.deptId
                      AND i.assignee_type = 'DEPARTMENT'
                      AND i.status = 'ISSUED'
                ),
                '-'
            ) AS assignedAssetNums

        FROM department d

        LEFT JOIN employee e ON e.empId=COALESCE(
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

        ORDER BY d.deptName ASC
    ");

        return $this->db->resultSet();
    }

    // Fetch department by ID
    public function getDeptByID($id)
    {
        $this->db->query('SELECT d.*,
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
            ) AS selectedHeadEmpId,
            e.fullname AS headName
            FROM department d
            LEFT JOIN employee e ON e.empId=COALESCE(
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
            WHERE d.deptId = :deptId');
        $this->db->bind("deptId", $id);
        $department = $this->db->single();
        if (!$department) {
            return null;
        }
        return $department;
    }

    public function saveData($maxID, $data)
    {
        $data["deptName"] = trim($data["deptName"] ?? "");
        $data["deptPrefix"] = strtoupper(trim($data["deptPrefix"] ?? ""));
        $data["area"] = trim($data["area"] ?? "");
        $data["building"] = trim($data["building"] ?? "");
        if (
            $data["deptName"] === "" ||
            mb_strlen($data["deptName"]) > 50 ||
            !preg_match('/^[A-Z][A-Z0-9-]{1,9}$/', $data["deptPrefix"])
        ) {
            return 0;
        }
        $this->db->query("SELECT 1 FROM department WHERE deptName=:deptName LIMIT 1");
        $this->db->bind("deptName", $data["deptName"]);
        if ($this->db->single()) {
            return -1;
        }
        $this->db->query("SELECT 1 FROM department WHERE deptPrefix=:deptPrefix LIMIT 1");
        $this->db->bind("deptPrefix", $data["deptPrefix"]);
        if ($this->db->single()) {
            return -2;
        }
        $query =
            "INSERT INTO department (deptId, deptPrefix, deptName, area, building, headEmpId)
             VALUES(:deptId, :deptPrefix, :deptName, :area, :building, :headEmpId)";
        $this->db->query($query);
        $this->db->bind("deptId", $maxID);
        $this->db->bind("deptPrefix", $data["deptPrefix"]);
        $this->db->bind("deptName", $data["deptName"]);
        $this->db->bind("area", $data["area"] ?: null);
        $this->db->bind("building", $data["building"] ?: null);
        $this->db->bind("headEmpId", !empty($data["headEmpId"]) ? (int) $data["headEmpId"] : null);
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function updateData($data)
    {
        $data["deptName"] = trim($data["deptName"] ?? "");
        $data["deptPrefix"] = strtoupper(trim($data["deptPrefix"] ?? ""));
        $data["area"] = trim($data["area"] ?? "");
        $data["building"] = trim($data["building"] ?? "");
        if (
            $data["deptName"] === "" ||
            mb_strlen($data["deptName"]) > 50 ||
            !preg_match('/^[A-Z][A-Z0-9-]{1,9}$/', $data["deptPrefix"])
        ) {
            return -1;
        }
        $this->db->query(
            "SELECT 1 FROM department WHERE (deptName=:deptName OR deptPrefix=:deptPrefix) AND deptId<>:deptId LIMIT 1",
        );
        $this->db->bind("deptName", $data["deptName"]);
        $this->db->bind("deptPrefix", $data["deptPrefix"]);
        $this->db->bind("deptId", $data["deptId"]);
        if ($this->db->single()) {
            return -2;
        }
        $headId = (int) ($data["headEmpId"] ?? 0);
        if ($headId > 0) {
            $this->db->query("SELECT 1 FROM employee
                WHERE empId=:empId AND deptId=:deptId AND aktif=1");
            $this->db->bind("empId", $headId);
            $this->db->bind("deptId", $data["deptId"]);
            if (!$this->db->single()) {
                return -4;
            }
        }
        $this->db->beginTransaction();
        // Multiple section-head routing is disabled. Keep legacy
        // department_head rows untouched so they can be restored later.
        $query =
            "UPDATE department SET deptPrefix=:deptPrefix,deptName=:deptName,
                area=:area,building=:building,headEmpId=:headEmpId
                WHERE deptId=:deptId";
        $this->db->query($query);
        $this->db->bind("deptId", $data["deptId"]);
        $this->db->bind("deptPrefix", $data["deptPrefix"]);
        $this->db->bind("deptName", $data["deptName"]);
        $this->db->bind("area", $data["area"] ?: null);
        $this->db->bind("building", $data["building"] ?: null);
        $this->db->bind("headEmpId", $headId ?: null);
        $this->db->executeOrFail();
        $this->db->commit();
        return 1;
    }

    /**
     * Delete a department only when it is not used by organizational or
     * transaction records. Returning a structured result lets the controller
     * show a useful validation message instead of exposing a database error.
     */
    public function deleteData($id): array
    {
        $id = trim((string) $id);
        if ($id === "") {
            return ["deleted" => false, "message" => "Invalid department ID."];
        }

        $department = $this->getDeptByID($id);
        if (!$department) {
            return ["deleted" => false, "message" => "Department not found."];
        }

        $dependencies = $this->getDeleteDependencies($id);
        if (!empty($dependencies)) {
            return [
                "deleted" => false,
                "message" =>
                    "Department " .
                    $id .
                    " cannot be deleted because it is in use: " .
                    implode("; ", $dependencies) .
                    ". Reassign or remove these references first.",
            ];
        }

        try {
            $this->db->beginTransaction();

            // empdept is a current compatibility mapping, not transaction
            // history. Once validation confirms that the department has no
            // current employees, remove obsolete mappings that would otherwise
            // make the foreign key reject an otherwise valid deletion.
            $this->db->query("DELETE FROM empdept WHERE deptId = :deptId");
            $this->db->bind("deptId", $id);
            $this->db->execute();

            $this->db->query("DELETE FROM " . $this->table . " WHERE deptId = :deptId");
            $this->db->bind("deptId", $id);
            $this->db->execute();
            $deleted = $this->db->rowCount() > 0;
            $this->db->commit();

            return [
                "deleted" => $deleted,
                "message" => "Department deleted successfully.",
            ];
        } catch (PDOException $exception) {
            $this->db->rollBack();
            // A concurrent update may add a reference after validation. Keep
            // the database constraint as the final guard and show a safe error.
            if ((string) $exception->getCode() === "23000") {
                return [
                    "deleted" => false,
                    "message" =>
                        "Department " .
                        $id .
                        " is still referenced by another record and cannot be deleted.",
                ];
            }

            error_log(
                "Department deletion failed for " .
                    $id .
                    ": " .
                    $exception->getMessage(),
            );
            return [
                "deleted" => false,
                "message" => "Department could not be deleted. Please try again or contact the administrator.",
            ];
        }
    }

    /**
     * Return human-readable records that make deleting a department unsafe.
     * Employees are deliberately checked even though one database constraint
     * can null their department: silently unassigning people is not desirable.
     */
    private function getDeleteDependencies(string $id): array
    {
        $dependencies = [];

        $this->db->query(
            "SELECT DISTINCT e.empId, e.fullname
             FROM employee e
             LEFT JOIN empdept ed
               ON ed.empId = e.empId
              AND ed.deptId = :mappedDeptId
              AND ed.active = 1
             WHERE e.deptId = :directDeptId
                OR (e.deptId IS NULL AND ed.empId IS NOT NULL)
             ORDER BY e.empId
             LIMIT 9",
        );
        $this->db->bind("mappedDeptId", $id);
        $this->db->bind("directDeptId", $id);
        $employees = $this->db->resultSet();
        if (!empty($employees)) {
            $labels = [];
            foreach (array_slice($employees, 0, 8) as $employee) {
                $labels[] =
                    (string) $employee["empId"] .
                    " - " .
                    (string) $employee["fullname"];
            }
            if (count($employees) > 8) {
                $labels[] = "and more";
            }
            $dependencies[] = "employee(s) " . implode(", ", $labels);
        }

        $checks = [
            "asset assignment transaction(s)" =>
                "SELECT COUNT(*) AS total FROM asset_issue WHERE deptId = :deptId",
            "equipment request transaction(s)" =>
                "SELECT COUNT(*) AS total FROM asset_request WHERE deptId = :deptId",
        ];

        foreach ($checks as $label => $query) {
            $this->db->query($query);
            $this->db->bind("deptId", $id);
            $row = $this->db->single();
            $total = (int) ($row["total"] ?? 0);
            if ($total > 0) {
                $dependencies[] = $total . " " . $label;
            }
        }

        return $dependencies;
    }

    public function getMaxID()
    {
        $this->db->query(
            "SELECT COALESCE(MAX(CAST(deptId AS UNSIGNED)),0) AS deptId FROM " . $this->table,
        );
        return $this->db->single();
    }

    public function getActiveEmployees()
    {
        $this->db->query(
            "SELECT empId,fullname,department FROM employee WHERE aktif=1 ORDER BY fullname",
        );
        return $this->db->resultSet();
    }

    public function getHeadsByDepartment(string $deptId): array
    {
        $this->db->query("SELECT e.empId,0 AS is_superior,e.fullname,e.position
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
            WHERE d.deptId=:deptId");
        $this->db->bind("deptId", $deptId);
        return $this->db->resultSet();
    }

    public function getAllDepartmentHeads(): array
    {
        $this->db->query("SELECT d.deptId,e.empId,0 AS is_superior,
            e.fullname,e.position,d.deptName
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
            )
            WHERE e.aktif=1 AND d.active=1
            ORDER BY d.deptName,e.fullname");
        return $this->db->resultSet();
    }
}
