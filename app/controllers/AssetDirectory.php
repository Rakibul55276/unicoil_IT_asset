<?php

class AssetDirectory extends Controller
{
    public function __construct()
    {
        if (empty($_SESSION["usr"])) {
            header("location:" . BASEURL);
            exit();
        }

        $username = strtolower(trim((string) ($_SESSION["usr"]["user"] ?? "")));
        $roles = array_values(array_filter($_SESSION["usr"]["roles"] ?? []));
        if (
            $username !== "admin" &&
            !(bool) array_intersect($roles, ["ADMINISTRATOR", "ISSUER", "IT_MANAGER"])
        ) {
            http_response_code(403);
            exit("Only an Administrator, Issuer, or IT Manager can view the asset summary.");
        }
    }

    public function index()
    {
        $model = $this->model("AssetDirectory_model");
        $categories = $model->getCategorySummary();
        $requestedCategory = substr(trim((string) ($_GET["category"] ?? "")), 0, 20);
        $selected = null;
        foreach ($categories as $category) {
            if ($requestedCategory !== "" && $category["categoryCode"] === $requestedCategory) {
                $selected = $category;
                break;
            }
        }
        $filters = [
            "status" => strtoupper(trim((string) ($_GET["status"] ?? "ALL"))),
            "groupType" => trim((string) ($_GET["groupType"] ?? "")),
            "groupId" => trim((string) ($_GET["groupId"] ?? "")),
            "assigneeType" => strtoupper(trim((string) ($_GET["assigneeType"] ?? ""))),
            "search" => trim((string) ($_GET["search"] ?? "")),
            "page" => max(1, (int) ($_GET["page"] ?? 1)),
            "perPage" => (int) ($_GET["perPage"] ?? 25),
        ];
        $data = [
            "title" => "IT Asset Summary",
            "menu" => "Asset Summary",
            "menu-dsc" => "Company-wide inventory by category, custodian and stock location",
            "assetDirectoryPage" => true,
            "categories" => $categories,
            "selected" => $selected,
            "breakdown" => $selected
                ? $model->getCategoryBreakdown($selected["categoryCode"])
                : ["assigned" => [], "stock" => []],
            "assets" => $selected
                ? $model->getAssetPage($selected["categoryCode"], $filters)
                : ["items" => [], "total" => 0, "page" => 1, "perPage" => 25, "totalPages" => 1, "offset" => 0],
            "filters" => $filters,
        ];

        $this->view("templates/header_a", $data);
        $this->view("asset-directory/index", $data);
        $this->view("templates/footer_a");
    }

    public function exportExcel()
    {
        [$title, $subtitle, $headers, $rows, $slug] = $this->exportDataset();
        header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
        header('Content-Disposition: attachment; filename="' . $slug . '-' . date("Y-m-d") . '.xls"');
        echo '<html><head><meta charset="UTF-8"><style>table{border-collapse:collapse}th{background:#145c4d;color:#fff}th,td{border:1px solid #b7c7c2;padding:7px;vertical-align:top}h2{color:#17365d}</style></head><body>';
        echo '<h2>' . $this->exportEscape($title) . '</h2><p>' . $this->exportEscape($subtitle) . '</p><table><thead><tr>';
        foreach ($headers as $header) {
            echo '<th>' . $this->exportEscape($header) . '</th>';
        }
        echo '</tr></thead><tbody>';
        foreach ($rows as $row) {
            echo '<tr>';
            foreach ($row as $value) {
                $safe = (string) $value;
                if (preg_match('/^[=+\-@]/', $safe)) {
                    $safe = "'" . $safe;
                }
                echo '<td>' . $this->exportEscape($safe) . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table></body></html>';
        exit();
    }

    public function exportPdf()
    {
        [$title, $subtitle, $headers, $rows, $slug] = $this->exportDataset();
        SimplePdfReport::downloadTable(
            strtoupper($title),
            $subtitle,
            $headers,
            $rows,
            $slug . '-' . date("Y-m-d") . '.pdf',
            dirname(__DIR__, 2) . "/public/img/unicoil-logo.jpg",
        );
    }

    public function employee($id)
    {
        header("Content-Type: application/json; charset=UTF-8");
        if (!ctype_digit((string) $id)) {
            http_response_code(422);
            echo json_encode(["error" => "Invalid employee ID"]);
            return;
        }
        $model = $this->model("Employee_model");
        $employee = $model->getEmployeeByID((int) $id);
        if (!$employee) {
            http_response_code(404);
            echo json_encode(["error" => "Employee not found"]);
            return;
        }
        echo json_encode([
            "employee" => $employee,
            "transactions" => $model->getEmployeeAssetTransactions((int) $id),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function assignments($categoryCode)
    {
        header("Content-Type: application/json; charset=UTF-8");
        $categoryCode = substr(trim((string) $categoryCode), 0, 20);
        $assigneeType = strtoupper(trim((string) ($_GET["type"] ?? "USER")));
        if (!in_array($assigneeType, ["USER", "DEPARTMENT"], true)) {
            http_response_code(422);
            echo json_encode(["error" => "Invalid assignment type"]);
            return;
        }
        $model = $this->model("AssetDirectory_model");
        $categoryName = "";
        foreach ($model->getCategorySummary() as $category) {
            if ($category["categoryCode"] === $categoryCode) {
                $categoryName = $category["categoryName"];
                break;
            }
        }
        if ($categoryName === "") {
            http_response_code(404);
            echo json_encode(["error" => "Asset category not found"]);
            return;
        }
        $filters = ["status" => "ASSIGNED", "assigneeType" => $assigneeType, "page" => 1, "perPage" => 100];
        $items = [];
        do {
            $page = $model->getAssetPage($categoryCode, $filters);
            $items = array_merge($items, $page["items"]);
            $filters["page"]++;
        } while ($filters["page"] <= $page["totalPages"] && count($items) < 5000);
        $rows = [];
        foreach (array_slice($items, 0, 5000) as $index => $asset) {
            $rows[] = [
                "sl" => $index + 1,
                "assetNum" => $asset["assetNum"],
                "empId" => $assigneeType === "USER" ? ($asset["empId"] ?? "") : "",
                "assignedTo" => $assigneeType === "USER"
                    ? ($asset["employeeName"] ?: "User not recorded")
                    : ($asset["deptName"] ?: "Department not recorded"),
                "department" => $asset["deptName"] ?: "-",
                "issueDate" => substr((string) ($asset["issue_date"] ?? ""), 0, 10),
            ];
        }
        echo json_encode([
            "category" => $categoryName,
            "type" => $assigneeType,
            "total" => (int) ($page["total"] ?? count($rows)),
            "rows" => $rows,
            "truncated" => count($items) >= 5000,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function exportDataset(): array
    {
        $model = $this->model("AssetDirectory_model");
        $categoryCode = substr(trim((string) ($_GET["category"] ?? "")), 0, 20);
        if ($categoryCode === "") {
            $rows = [];
            foreach ($model->getCategorySummary() as $category) {
                $rows[] = [
                    $category["categoryCode"],
                    $category["categoryName"],
                    (int) $category["totalAssets"],
                    (int) $category["assignedUserAssets"],
                    (int) $category["assignedDepartmentAssets"],
                    (int) $category["inStockAssets"],
                ];
            }
            return [
                "Asset Summary",
                "Company inventory by category, assignment type, and stock location",
                ["Code", "Category", "Total", "Assigned to users", "Assigned to departments", "In stock"],
                $rows,
                "asset-summary",
            ];
        }

        $selected = null;
        foreach ($model->getCategorySummary() as $category) {
            if ($category["categoryCode"] === $categoryCode) {
                $selected = $category;
                break;
            }
        }
        if (!$selected) {
            http_response_code(404);
            exit("Asset category not found.");
        }
        $filters = $this->directoryFilters();
        $filters["page"] = 1;
        $filters["perPage"] = 100;
        $items = [];
        do {
            $page = $model->getAssetPage($categoryCode, $filters);
            $items = array_merge($items, $page["items"]);
            $filters["page"]++;
        } while ($filters["page"] <= $page["totalPages"] && count($items) < 5000);
        $items = array_slice($items, 0, 5000);
        $rows = [];
        foreach ($items as $index => $asset) {
            $isAssigned = $asset["assetStat"] === "ASSIGNED";
            $isUser = $isAssigned && $asset["assignee_type"] === "USER";
            $isDepartment = $isAssigned && $asset["assignee_type"] === "DEPARTMENT";
            $rows[] = [
                $index + 1,
                $asset["assetNum"],
                $asset["Description"],
                $asset["serial_number"] ?: "-",
                $isAssigned ? "Assigned" : "In stock",
                $isUser ? ($asset["empId"] ?: "-") : "-",
                $isUser ? ($asset["employeeName"] ?: "User not recorded") : "-",
                $isDepartment ? ($asset["deptName"] ?: "Department not recorded") : "-",
                $isUser ? ($asset["deptName"] ?: "-") : "-",
                !$isAssigned ? ($asset["locationName"] ?: "Location not recorded") : "-",
                $isAssigned ? (substr((string) ($asset["issue_date"] ?? ""), 0, 10) ?: "-") : "-",
                ucfirst(strtolower((string) $asset["asset_condition"])),
            ];
        }
        $statusLabel = ["ASSIGNED" => "Assigned", "IN_STOCK" => "In stock"][$filters["status"]] ?? "All assets";
        return [
            $selected["categoryName"] . " - Asset Details",
            $statusLabel . " | " . number_format(count($rows)) . " exported records" . (count($items) >= 5000 ? " (limited to 5,000)" : ""),
            ["SL No.", "Asset", "Description", "Serial", "Status", "Employee ID", "Assigned user", "Assigned to department", "User department", "Stock location", "Issue date", "Condition"],
            $rows,
            "asset-details-" . preg_replace('/[^A-Za-z0-9_-]/', '-', strtolower($categoryCode)),
        ];
    }

    private function directoryFilters(): array
    {
        return [
            "status" => strtoupper(trim((string) ($_GET["status"] ?? "ALL"))),
            "groupType" => trim((string) ($_GET["groupType"] ?? "")),
            "groupId" => trim((string) ($_GET["groupId"] ?? "")),
            "assigneeType" => strtoupper(trim((string) ($_GET["assigneeType"] ?? ""))),
            "search" => trim((string) ($_GET["search"] ?? "")),
            "page" => 1,
            "perPage" => 100,
        ];
    }

    private function aggregateNames(string $packed): string
    {
        if ($packed === "") return "-";
        $names = [];
        foreach (explode("|||", $packed) as $item) {
            $parts = explode("|", $item, 2);
            $names[] = $parts[1] ?? $parts[0];
        }
        return implode(", ", $names);
    }

    private function exportEscape($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
    }
}
