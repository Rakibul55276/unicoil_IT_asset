<?php

class AssetDirectory_model
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    /** Active inventory by category. Categories with no current assets are omitted. */
    public function getCategorySummary(): array
    {
        $this->db->query("SELECT
                k.assetKat AS categoryCode,
                k.Description AS categoryName,
                COUNT(DISTINCT a.assetNum) AS totalAssets,
                COUNT(DISTINCT CASE WHEN a.assetStat='ASSIGNED' THEN a.assetNum END) AS assignedAssets,
                COUNT(DISTINCT CASE WHEN a.assetStat='ASSIGNED' AND i.assignee_type='USER' THEN a.assetNum END) AS assignedUserAssets,
                COUNT(DISTINCT CASE WHEN a.assetStat='ASSIGNED' AND i.assignee_type='DEPARTMENT' THEN a.assetNum END) AS assignedDepartmentAssets,
                COUNT(DISTINCT CASE WHEN a.assetStat='IN_STOCK' THEN a.assetNum END) AS inStockAssets,
                GROUP_CONCAT(DISTINCT CASE
                    WHEN a.assetStat='ASSIGNED' AND i.assignee_type='USER'
                    THEN CONCAT(i.empId,'|',COALESCE(e.fullname,'Unknown user'))
                    END ORDER BY e.fullname SEPARATOR '|||') AS assignedUsers,
                GROUP_CONCAT(DISTINCT CASE
                    WHEN a.assetStat='ASSIGNED' AND i.assignee_type='DEPARTMENT'
                    THEN CONCAT(COALESCE(i.deptId,'-'),'|',COALESCE(d.deptName,'Unknown department'))
                    END ORDER BY d.deptName SEPARATOR '|||') AS assignedDepartments,
                GROUP_CONCAT(DISTINCT CASE
                    WHEN a.assetStat='IN_STOCK'
                    THEN CONCAT(COALESCE(a.location,'-'),'|',COALESCE(l.description,'Location not recorded'))
                    END ORDER BY l.description SEPARATOR '|||') AS stockLocations
            FROM assetkat k
            JOIN assetdata a ON a.assetKat=k.assetKat
              AND a.assetStat IN ('ASSIGNED','IN_STOCK')
            LEFT JOIN asset_issue i ON i.assetNum=a.assetNum AND i.status='ISSUED'
            LEFT JOIN employee e ON e.empId=i.empId
            LEFT JOIN department d ON d.deptId=i.deptId
            LEFT JOIN location l ON l.location=a.location
            GROUP BY k.assetKat,k.Description
            HAVING COUNT(DISTINCT a.assetNum) > 0
            ORDER BY k.Description,k.assetKat");
        return $this->db->resultSet();
    }

    /** Distribution for the selected category; zero-count groups never reach the UI. */
    public function getCategoryBreakdown(string $category): array
    {
        $this->db->query("SELECT
                COALESCE(NULLIF(d.deptId,''),'UNSPECIFIED') AS groupId,
                COALESCE(NULLIF(d.deptName,''),'Department not recorded') AS groupName,
                COUNT(*) AS assetCount,
                COUNT(DISTINCT CASE WHEN i.assignee_type='USER' THEN i.empId END) AS userCount,
                SUM(CASE WHEN i.assignee_type='DEPARTMENT' THEN 1 ELSE 0 END) AS departmentAssets
            FROM assetdata a
            JOIN asset_issue i ON i.assetNum=a.assetNum AND i.status='ISSUED'
            LEFT JOIN employee e ON e.empId=i.empId
            LEFT JOIN department d ON d.deptId=COALESCE(
                i.deptId,
                e.deptId,
                (SELECT MIN(ed.deptId) FROM empdept ed WHERE ed.empId=e.empId AND ed.active=1)
            )
            WHERE a.assetKat=:assignedCategory AND a.assetStat='ASSIGNED'
            GROUP BY COALESCE(NULLIF(d.deptId,''),'UNSPECIFIED'),
                     COALESCE(NULLIF(d.deptName,''),'Department not recorded')
            ORDER BY assetCount DESC,groupName");
        $this->db->bind("assignedCategory", $category);
        $assigned = $this->db->resultSet();

        $this->db->query("SELECT
                COALESCE(NULLIF(a.location,''),'UNSPECIFIED') AS groupId,
                COALESCE(NULLIF(l.description,''),'Location not recorded') AS groupName,
                COUNT(*) AS assetCount
            FROM assetdata a
            LEFT JOIN location l ON l.location=a.location
            WHERE a.assetKat=:stockCategory AND a.assetStat='IN_STOCK'
            GROUP BY COALESCE(NULLIF(a.location,''),'UNSPECIFIED'),
                     COALESCE(NULLIF(l.description,''),'Location not recorded')
            ORDER BY assetCount DESC,groupName");
        $this->db->bind("stockCategory", $category);
        $stock = $this->db->resultSet();

        return ["assigned" => $assigned, "stock" => $stock];
    }

    /** Bounded asset drill-down for a category, state and optional distribution group. */
    public function getAssetPage(string $category, array $filters): array
    {
        $status = strtoupper((string) ($filters["status"] ?? "ALL"));
        if (!in_array($status, ["ALL", "ASSIGNED", "IN_STOCK"], true)) {
            $status = "ALL";
        }
        $page = max(1, (int) ($filters["page"] ?? 1));
        $perPage = (int) ($filters["perPage"] ?? 25);
        if (!in_array($perPage, [25, 50, 100], true)) {
            $perPage = 25;
        }
        $search = mb_substr(trim((string) ($filters["search"] ?? "")), 0, 100);
        $groupType = (string) ($filters["groupType"] ?? "");
        $groupId = mb_substr(trim((string) ($filters["groupId"] ?? "")), 0, 30);
        $assigneeType = strtoupper(trim((string) ($filters["assigneeType"] ?? "")));

        $where = ["a.assetKat=:category", "a.assetStat IN ('ASSIGNED','IN_STOCK')"];
        $bindings = ["category" => $category];
        if ($status !== "ALL") {
            $where[] = "a.assetStat=:assetStatus";
            $bindings["assetStatus"] = $status;
        }
        if ($status === "ASSIGNED" && in_array($assigneeType, ["USER", "DEPARTMENT"], true)) {
            $where[] = "i.assignee_type=:assigneeType";
            $bindings["assigneeType"] = $assigneeType;
        }
        if ($search !== "") {
            $where[] = "(a.assetNum LIKE :assetSearch OR a.serial_number LIKE :serialSearch OR a.Description LIKE :descriptionSearch OR e.fullname LIKE :employeeSearch)";
            foreach (["assetSearch", "serialSearch", "descriptionSearch", "employeeSearch"] as $key) {
                $bindings[$key] = $search . "%";
            }
        }
        if ($groupType === "department" && $groupId !== "") {
            $departmentExpression = "COALESCE(i.deptId,e.deptId,(SELECT MIN(ed_filter.deptId) FROM empdept ed_filter WHERE ed_filter.empId=e.empId AND ed_filter.active=1))";
            if ($groupId === "UNSPECIFIED") {
                $where[] = "{$departmentExpression} IS NULL";
            } else {
                $where[] = "{$departmentExpression}=:groupDepartment";
                $bindings["groupDepartment"] = $groupId;
            }
        } elseif ($groupType === "location" && $groupId !== "") {
            if ($groupId === "UNSPECIFIED") {
                $where[] = "(a.location IS NULL OR a.location='')";
            } else {
                $where[] = "a.location=:groupLocation";
                $bindings["groupLocation"] = $groupId;
            }
        }
        $whereSql = implode(" AND ", $where);
        $joins = "LEFT JOIN asset_issue i ON i.assetNum=a.assetNum AND i.status='ISSUED'
            LEFT JOIN employee e ON e.empId=i.empId
            LEFT JOIN department d ON d.deptId=COALESCE(
                i.deptId,
                e.deptId,
                (SELECT MIN(ed_join.deptId) FROM empdept ed_join WHERE ed_join.empId=e.empId AND ed_join.active=1)
            )
            LEFT JOIN location l ON l.location=a.location";

        $this->db->query("SELECT COUNT(DISTINCT a.assetNum) AS total FROM assetdata a {$joins} WHERE {$whereSql}");
        foreach ($bindings as $key => $value) {
            $this->db->bind($key, $value);
        }
        $count = $this->db->single();
        $total = (int) ($count["total"] ?? 0);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $this->db->query("SELECT DISTINCT
                a.assetNum,a.Description,a.serial_number,a.manufacturer,a.model,
                a.assetStat,a.asset_condition,a.location,l.description AS locationName,
                i.issue_no,i.issue_date,i.assignee_type,e.empId,e.fullname AS employeeName,
                e.area AS employeeArea,e.building AS employeeBuilding,
                d.deptId,d.deptName
            FROM assetdata a {$joins}
            WHERE {$whereSql}
            ORDER BY a.assetStat,a.assetNum
            LIMIT :pageLimit OFFSET :pageOffset");
        foreach ($bindings as $key => $value) {
            $this->db->bind($key, $value);
        }
        $this->db->bind("pageLimit", $perPage);
        $this->db->bind("pageOffset", $offset);

        return [
            "items" => $this->db->resultSet(),
            "total" => $total,
            "page" => $page,
            "perPage" => $perPage,
            "totalPages" => $totalPages,
            "offset" => $offset,
        ];
    }
}
