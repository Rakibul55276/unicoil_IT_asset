<?php

class Asset_model
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function deleteAsset($assetNum)
    {
        $this->db->query("DELETE FROM assetdata WHERE assetNum = :assetNum");
        $this->db->bind("assetNum", $assetNum);
        $this->db->execute();
        return $this->db->rowCount();
    }

    /** Update an asset and replace its primary image when a new file is supplied. */
    public function updateAsset($data, $file = null)
    {
        $asset = $this->getAssetById($data["assetId"]);
        if (!$asset) {
            return 0;
        }

        $imageName = $asset["assetImage"] ?? null;
        $imageChanged = false;

        // Updating text fields does not require a replacement image.
        if ($file && $file["error"] === 0) {
            $uploaded = $this->storeSingleAssetImage($file);
            $targetDir = __DIR__ . "/../../public/uploads/asset/";
            // Remove the old file only after the replacement upload succeeds.
            if ($imageName && file_exists($targetDir . $imageName)) {
                unlink($targetDir . $imageName);
            }
            $imageName = $uploaded["file_name"];
            $imageChanged = true;
        }

        // Descriptive edits must not bypass the assignment/return lifecycle.
        // Custodian and live status are changed only by assignAsset() and
        // returnAssignedAsset().
        $query = "UPDATE assetdata SET
                assetKat = :assetKat,
                Description = :Description,
                quantity = :quantity,
                unit = :unit,
                location = :location,
                assetImage = :assetImage
              WHERE assetNum = :assetNum";

        $this->db->query($query);
        $this->db->bind("assetNum", $data["assetId"]);
        $this->db->bind("assetKat", $data["assetKat"]);
        $this->db->bind("Description", $data["desc"]);
        $this->db->bind("quantity", $data["qty"]);
        $this->db->bind("unit", $data["unit"]);
        $this->db->bind("location", $data["location"]);
        $this->db->bind("assetImage", $imageName);

        $this->db->execute();

        $affected = $this->db->rowCount();
        if ($affected > 0) {
            return $affected;
        }
        if ($imageChanged) {
            return 1;
        }

        return 0;
    }

    // Fetch all assets with readable names
    public function getAsset()
    {
        $this->db->query("
        SELECT 
            a.assetNum,
            a.Description,
            a.assetKat,
            ac.Description AS assetKatName,
            a.empId,
            e.fullname AS empName,
            a.createdBy,
            a.createdDate,
            a.quantity,
            a.unit,
            a.location,
            l.description AS locationName,
            a.assetStat,
            a.asset_condition,
            s.Description AS assetStatusName,
            a.assetImage
        FROM assetdata a
        LEFT JOIN employee e ON a.empId = e.empId
        LEFT JOIN assetkat ac ON a.assetKat = ac.assetKat
        LEFT JOIN assetstat s ON a.assetStat = s.assetStatus
        LEFT JOIN location l ON a.location = l.location
        ORDER BY a.assetNum ASC
    ");
        return $this->db->resultSet();
    }

    // Fetch a single asset by its number
    public function getAssetById($assetNum)
    {
        try {
            $this->db->query("
            SELECT 
                a.assetNum,
                a.Description,
                a.assetKat,
                ac.Description AS assetKatName,
                a.serial_number,
                a.manufacturer,
                a.model,
                a.purchase_date,
                a.warranty_expiry,
                a.receipt_id,
                a.empId,
                e.fullname AS empName,
                i.assignee_type AS assignmentType,
                i.deptId,
                d.deptName,
                i.issue_no,
                i.created_at,
                i.expected_return_date,
                i.condition_out,
                i.purpose,
                i.issued_by,
                i.acknowledged_by,
                a.createdBy,
                a.createdDate,
                a.quantity,
                a.unit,
                a.location,
                l.description AS locationName,
                a.assetStat,
                a.asset_condition,
                s.Description AS assetStatusName,
                a.assetImage,
                a.notes AS assetNotes,
                a.updated_at,
                r.receipt_no,
                r.received_date,
                r.supplier_name,
                r.vendor_id,
                r.po_number,
                r.invoice_number,
                r.invoice_pdf,
                r.received_by,
                r.notes AS receiptNotes,
                ri.condition_on_receipt,
                ri.remarks AS receiptItemRemarks,
                v.contact_person AS vendorContact,
                v.email AS vendorEmail,
                v.phone AS vendorPhone,
                v.address AS vendorAddress,
                v.tax_number AS vendorTaxNumber
            FROM assetdata a
            LEFT JOIN employee e ON a.empId = e.empId
            LEFT JOIN asset_issue i ON i.assetNum=a.assetNum AND i.status='ISSUED'
            LEFT JOIN department d ON d.deptId=i.deptId
            LEFT JOIN assetkat ac ON a.assetKat = ac.assetKat
            LEFT JOIN assetstat s ON a.assetStat = s.assetStatus
            LEFT JOIN location l ON a.location = l.location
            LEFT JOIN asset_receipt r ON r.receipt_id = a.receipt_id
            LEFT JOIN asset_receipt_item ri ON ri.receipt_id = a.receipt_id AND ri.assetNum = a.assetNum
            LEFT JOIN vendor v ON v.vendor_id = r.vendor_id
            WHERE a.assetNum = :assetNum
        ");
            $this->db->bind("assetNum", $assetNum);
            $asset = $this->db->single();

            // Missing assets are noteworthy; successful reads should not fill the log.
            if (!$asset) {
                error_log("Asset not found: " . $assetNum);
            } elseif (!array_key_exists("assetImage", $asset)) {
                error_log("Asset image column missing in query result for asset: " . $assetNum);
            }

            return $asset;
        } catch (Exception $e) {
            error_log("Error in getAssetById: " . $e->getMessage());
            return false;
        }
    }

    // Fetch units
    public function getUnit()
    {
        $this->db->query("SELECT * FROM unit");
        return $this->db->resultSet();
    }

    // Fetch asset status
    public function getAssetStatus()
    {
        $this->db->query("SELECT * FROM assetstat
            WHERE assetStatus IN ('IN_STOCK','ASSIGNED')
            ORDER BY FIELD(assetStatus,'IN_STOCK','ASSIGNED')");
        return $this->db->resultSet();
    }

    // Save new asset
    public function saveAsset($assetNum, $data, $file = null)
    {
        $imageName = null;

        // Handle file upload
        if ($file && $file["error"] == 0) {
            $imageName = $this->storeSingleAssetImage($file)["file_name"];
        }

        $query = "INSERT INTO assetdata 
            (assetNum, assetKat, Description, empId, createdBy, createdDate,
             quantity, unit, assetStat, asset_condition, location, assetImage)
            VALUES
            (:assetNum, :assetKat, :Description, NULL, :createdBy, :createdDate,
             :quantity, :unit, 'IN_STOCK', 'NEW', :location, :assetImage)";

        $this->db->query($query);
        $this->db->bind("assetNum", $assetNum);
        $this->db->bind("assetKat", $data["assetKat"]);
        $this->db->bind("Description", $data["desc"]);
        $this->db->bind("createdBy", $_SESSION["usr"]["user"]);
        $this->db->bind("createdDate", date("Y-m-d"));
        $this->db->bind("quantity", $data["qty"]);
        $this->db->bind("unit", $data["unit"]);
        $this->db->bind("location", $data["location"]);
        $this->db->bind("assetImage", $imageName);
        $this->db->execute();

        return $this->db->rowCount();
    }
    public function getInactiveAssets()
    {
        try {
            // Prepare query to get assets where status is Inactive
            $this->db->query("
            SELECT 
                a.assetNum,
                a.Description,
                a.assetKat,
                ac.Description AS assetKatName,
                a.serial_number,
                a.empId,
                e.fullname AS empName,
                a.createdBy,
                a.createdDate,
                a.warranty_expiry,
                a.quantity,
                a.unit,
                a.location,
                l.description AS locationName,
                a.assetStat,
                s.Description AS assetStatusName
            FROM assetdata a
            LEFT JOIN employee e ON a.empId = e.empId
            LEFT JOIN assetkat ac ON a.assetKat = ac.assetKat
            LEFT JOIN assetstat s ON a.assetStat = s.assetStatus
            LEFT JOIN location l ON a.location = l.location
            WHERE a.assetStat = 'IN_STOCK'
            ORDER BY a.assetNum ASC
        ");

            $assets = $this->db->resultSet();

            if (!$assets) {
                // No inactive assets found
                error_log("getInactiveAssets(): No  Stock found.");
                return [];
            }

            return $assets;
        } catch (Exception $e) {
            // Log the error
            error_log("getInactiveAssets() failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Return one bounded inventory page.
     *
     * Loading a complete stock table is unsafe when the database contains
     * hundreds of thousands of assets. All inventory screens should use this
     * method and render only the requested page.
     */
    public function getAssetPage(string $status, array $filters = []): array
    {
        $allowedStatuses = ["IN_STOCK", "SCRAPPED"];
        $status = strtoupper(trim($status));
        if (!in_array($status, $allowedStatuses, true)) {
            throw new InvalidArgumentException("Unsupported inventory status.");
        }

        $page = max(1, (int) ($filters["page"] ?? 1));
        $allowedPageSizes = [25, 50, 100];
        $perPage = (int) ($filters["perPage"] ?? 50);
        if (!in_array($perPage, $allowedPageSizes, true)) {
            $perPage = 50;
        }
        $search = mb_substr(trim((string) ($filters["search"] ?? "")), 0, 100);
        $category = mb_substr(trim((string) ($filters["category"] ?? "")), 0, 20);
        $condition = strtoupper(
            mb_substr(trim((string) ($filters["condition"] ?? "ALL")), 0, 20),
        );
        $allowedConditions = ["NEW", "GOOD", "DAMAGED"];

        $where = ["a.assetStat = :asset_status"];
        $bindings = ["asset_status" => $status];
        if ($category !== "" && $category !== "ALL") {
            $where[] = "a.assetKat = :asset_category";
            $bindings["asset_category"] = $category;
        }
        if (in_array($condition, $allowedConditions, true)) {
            // Exact matching uses the composite inventory index
            // (assetStat, asset_condition, assetNum) for large datasets.
            $where[] = "a.asset_condition = :asset_condition";
            $bindings["asset_condition"] = $condition;
        }
        if ($search !== "") {
            // Prefix searches can use indexes; unrestricted contains searches
            // would scan the full million-row inventory.
            $where[] = "(a.assetNum LIKE :asset_number_search
                OR a.serial_number LIKE :asset_serial_search
                OR a.Description LIKE :asset_description_search)";
            $bindings["asset_number_search"] = $search . "%";
            $bindings["asset_serial_search"] = $search . "%";
            $bindings["asset_description_search"] = $search . "%";
        }
        $whereSql = implode(" AND ", $where);

        $this->db->query("SELECT COUNT(*) AS total FROM assetdata a WHERE {$whereSql}");
        foreach ($bindings as $key => $value) {
            $this->db->bind($key, $value);
        }
        $count = $this->db->single();
        $total = (int) ($count["total"] ?? 0);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $this->db->query("
            SELECT
                a.assetNum,
                a.Description,
                a.assetKat,
                ac.Description AS assetKatName,
                a.serial_number,
                a.empId,
                e.fullname AS empName,
                a.createdBy,
                a.createdDate,
                a.warranty_expiry,
                a.quantity,
                a.unit,
                a.location,
                l.description AS locationName,
                a.assetStat,
                a.asset_condition,
                a.updated_at,
                s.Description AS assetStatusName,
                ri.condition_on_receipt AS receiptCondition
            FROM assetdata a
            LEFT JOIN employee e ON e.empId = a.empId
            LEFT JOIN assetkat ac ON ac.assetKat = a.assetKat
            LEFT JOIN assetstat s ON s.assetStatus = a.assetStat
            LEFT JOIN location l ON l.location = a.location
            LEFT JOIN asset_receipt_item ri ON ri.assetNum = a.assetNum
            WHERE {$whereSql}
            ORDER BY a.assetNum ASC
            LIMIT :page_limit OFFSET :page_offset
        ");
        foreach ($bindings as $key => $value) {
            $this->db->bind($key, $value);
        }
        $this->db->bind("page_limit", $perPage);
        $this->db->bind("page_offset", $offset);

        $items = $this->db->resultSet();
        foreach ($items as &$item) {
            $item["currentCondition"] = strtoupper(
                trim((string) ($item["asset_condition"] ?? "NEW")),
            );
            $item["conditionSource"] = "ASSET";
            $item["conditionReference"] = "";
            $item["conditionRecordedAt"] = (string) ($item["updated_at"] ?? "");
        }
        unset($item);

        return [
            "items" => $items,
            "total" => $total,
            "page" => $page,
            "perPage" => $perPage,
            "totalPages" => $totalPages,
            "offset" => $offset,
        ];
    }
    public function getMediaById($mediaId)
    {
        $this->db->query("
        SELECT
            media_id,
            assetNum,
            file_name,
            original_name,
            media_stage
        FROM asset_media
        WHERE media_id = :media_id
        LIMIT 1
    ");

        $this->db->bind(":media_id", $mediaId);

        return $this->db->single();
    }

    /**
     * Confirm that an uploaded image belongs to an asset in the active tenant.
     * This prevents the authenticated media endpoint from becoming an arbitrary
     * file reader.
     */
    public function getAssetMediaReference(string $fileName)
    {
        $this->db->query("
            SELECT assetNum, assetImage AS file_name
            FROM assetdata
            WHERE assetImage = :primary_file
            UNION ALL
            SELECT assetNum, file_name
            FROM asset_media
            WHERE file_name = :media_file
            LIMIT 1
        ");
        $this->db->bind("primary_file", $fileName);
        $this->db->bind("media_file", $fileName);
        return $this->db->single();
    }

    /** Confirm that an archived image is referenced by the active tenant. */
    public function getArchivedMediaReference(string $fileName)
    {
        $this->db->query("
            SELECT asset_num AS assetNum, archive_image AS file_name
            FROM asset_deletion_audit
            WHERE archive_image = :file_name
            LIMIT 1
        ");
        $this->db->bind("file_name", $fileName);
        return $this->db->single();
    }

    /** Return invoice metadata only when the stored file belongs to a receipt. */
    public function getInvoiceReference(string $fileName)
    {
        $this->db->query("
            SELECT receipt_id, invoice_number, invoice_pdf
            FROM asset_receipt
            WHERE invoice_pdf = :file_name
            LIMIT 1
        ");
        $this->db->bind("file_name", $fileName);
        return $this->db->single();
    }

    public function deleteMediaById($mediaId)
    {
        $media = $this->getMediaById($mediaId);
        if (!$media) {
            return false;
        }
        $this->db->beginTransaction();
        try {
            $this->db->query("DELETE FROM asset_media WHERE media_id=:media_id");
            $this->db->bind("media_id", $mediaId);
            $this->db->executeOrFail();
            $this->db->query(
                "UPDATE assetdata SET assetImage=NULL WHERE assetNum=:assetNum AND assetImage=:fileName",
            );
            $this->db->bind("assetNum", $media["assetNum"]);
            $this->db->bind("fileName", $media["file_name"]);
            $this->db->executeOrFail();
            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    public function getAllAssetCategories()
    {
        try {
            $this->db->query("
            SELECT
                assetKat,
                Description
            FROM assetkat
            ORDER BY Description ASC
        ");

            return $this->db->resultSet();
        } catch (Exception $e) {
            error_log("getAllAssetCategories() failed: " . $e->getMessage());

            return [];
        }
    }

    /** Return currently issued assets, grouped by assignment batch. */
    public function getActiveAssets()
    {
        try {
            $this->db->query("
         SELECT
    COALESCE(i.batch_no,i.issue_no) AS issueNo,
    GROUP_CONCAT(DISTINCT req.request_no ORDER BY req.request_no SEPARATOR ', ') AS requestNo,
    GROUP_CONCAT(
        DISTINCT CONCAT(req.request_id, '::', req.request_no)
        ORDER BY req.request_no
        SEPARATOR '|||'
    ) AS requestReferences,
    GROUP_CONCAT(a.assetNum ORDER BY a.assetNum SEPARATOR ', ') AS assetNums,
    GROUP_CONCAT(a.Description ORDER BY a.assetNum SEPARATOR '|||') AS assetDescriptions,
    GROUP_CONCAT(COALESCE(NULLIF(a.serial_number,''),'-') ORDER BY a.assetNum SEPARATOR '|||') AS assetSerialNumbers,
    GROUP_CONCAT(a.assetKat ORDER BY a.assetNum SEPARATOR ',') AS assetCategoryCodes,
    GROUP_CONCAT(ac.Description ORDER BY a.assetNum SEPARATOR '|||') AS assetCategoryNames,
    GROUP_CONCAT(s.Description ORDER BY a.assetNum SEPARATOR '|||') AS perAssetStatusNames,
    MAX(e.empId) AS empId,
    MAX(e.fullname) AS empName,
    i.assignee_type AS assignmentType,
    MIN(i.issue_id) AS issueId,
    GROUP_CONCAT(i.issue_id ORDER BY i.issue_id) AS openBatchIssueIds,
    (SELECT GROUP_CONCAT(ii.issue_id ORDER BY ii.issue_id) FROM asset_issue ii WHERE COALESCE(ii.batch_no,ii.issue_no)=COALESCE(i.batch_no,i.issue_no)) AS batchIssueIds,
    i.deptId AS deptId,
    MAX(d.deptName) AS deptName,
    MAX(i.acknowledged_by) AS acknowledgedBy,
    SUM(COALESCE(a.quantity,1)) AS quantity,
    GROUP_CONCAT(DISTINCT a.unit ORDER BY a.unit SEPARATOR ', ') AS unit,
    GROUP_CONCAT(DISTINCT l.description ORDER BY l.description SEPARATOR ', ') AS locationName,
    GROUP_CONCAT(DISTINCT s.Description ORDER BY s.Description SEPARATOR ', ') AS assetStatusName,
    GROUP_CONCAT(DISTINCT a.createdBy ORDER BY a.createdBy SEPARATOR ', ') AS createdBy,
    MIN(a.createdDate) AS createdDate,
    MIN(i.issue_date) AS issueDate
FROM assetdata a
JOIN asset_issue i ON i.assetNum=a.assetNum AND i.status='ISSUED'
LEFT JOIN employee e ON i.empId = e.empId
LEFT JOIN department d ON i.deptId=d.deptId
LEFT JOIN assetkat ac ON a.assetKat = ac.assetKat
LEFT JOIN assetstat s ON a.assetStat = s.assetStatus
LEFT JOIN location l ON a.location = l.location
LEFT JOIN asset_request_allocation req_alloc ON req_alloc.issue_id = i.issue_id
LEFT JOIN asset_request_item req_item ON req_item.request_item_id = req_alloc.request_item_id
LEFT JOIN asset_request req ON req.request_id = req_item.request_id
WHERE a.assetStat = 'ASSIGNED'
GROUP BY COALESCE(i.batch_no,i.issue_no),i.assignee_type,i.empId,i.deptId
ORDER BY MIN(i.issue_date) DESC,MIN(i.issue_id) DESC
        ");

            $assets = $this->db->resultSet();

            if (!$assets) {
                error_log("getActiveAssets(): No issued assets found.");
                return [];
            }

            return $assets;
        } catch (Exception $e) {
            error_log("getActiveAssets() failed: " . $e->getMessage());
            return [];
        }
    }

    /** Return one server-side page of currently assigned asset batches. */
    public function getActiveAssetsPage(array $filters = []): array
    {
        $page = max(1, (int) ($filters["page"] ?? 1));
        $perPage = (int) ($filters["perPage"] ?? 50);
        if (!in_array($perPage, [25, 50, 100], true)) {
            $perPage = 50;
        }
        $search = mb_substr(trim((string) ($filters["search"] ?? "")), 0, 100);
        $category = mb_substr(trim((string) ($filters["category"] ?? "")), 0, 20);
        $assignmentType = strtoupper(trim((string) ($filters["assignmentType"] ?? "")));

        $where = ["a.assetStat = 'ASSIGNED'"];
        $bindings = [];
        if (in_array($assignmentType, ["USER", "DEPARTMENT"], true)) {
            $where[] = "i.assignee_type = :assignment_type";
            $bindings["assignment_type"] = $assignmentType;
        }
        if ($category !== "" && $category !== "ALL") {
            $where[] = "a.assetKat = :active_category";
            $bindings["active_category"] = $category;
        }
        if ($search !== "") {
            $prefix = $search . "%";
            $where[] = "(a.assetNum LIKE :active_asset_search
                OR a.serial_number LIKE :active_serial_search
                OR a.Description LIKE :active_description_search
                OR e.empId LIKE :active_employee_id_search
                OR e.fullname LIKE :active_employee_search
                OR d.deptName LIKE :active_department_search
                OR i.issue_no LIKE :active_issue_search
                OR req.request_no LIKE :active_request_search)";
            foreach (
                [
                    "active_asset_search",
                    "active_serial_search",
                    "active_description_search",
                    "active_employee_id_search",
                    "active_employee_search",
                    "active_department_search",
                    "active_issue_search",
                    "active_request_search",
                ]
                as $binding
            ) {
                $bindings[$binding] = $prefix;
            }
        }
        $whereSql = implode(" AND ", $where);
        $fromSql = "
            FROM assetdata a
            JOIN asset_issue i ON i.assetNum = a.assetNum AND i.status = 'ISSUED'
            LEFT JOIN employee e ON i.empId = e.empId
            LEFT JOIN department d ON i.deptId = d.deptId
            LEFT JOIN asset_request_allocation req_alloc ON req_alloc.issue_id = i.issue_id
            LEFT JOIN asset_request_item req_item
                ON req_item.request_item_id = req_alloc.request_item_id
            LEFT JOIN asset_request req ON req.request_id = req_item.request_id
            WHERE {$whereSql}";

        $this->db->query("
            SELECT COUNT(*) AS total
            FROM (
                SELECT 1
                {$fromSql}
                GROUP BY COALESCE(i.batch_no, i.issue_no),
                         i.assignee_type,
                         i.empId,
                         i.deptId
            ) grouped_assignments
        ");
        foreach ($bindings as $key => $value) {
            $this->db->bind($key, $value);
        }
        $count = $this->db->single();
        $total = (int) ($count["total"] ?? 0);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $this->db->query("
            SELECT
                COALESCE(i.batch_no,i.issue_no) AS issueNo,
                GROUP_CONCAT(DISTINCT req.request_no ORDER BY req.request_no SEPARATOR ', ')
                    AS requestNo,
                GROUP_CONCAT(
                    DISTINCT CONCAT(req.request_id, '::', req.request_no)
                    ORDER BY req.request_no
                    SEPARATOR '|||'
                ) AS requestReferences,
                GROUP_CONCAT(a.assetNum ORDER BY a.assetNum SEPARATOR ', ') AS assetNums,
                GROUP_CONCAT(a.Description ORDER BY a.assetNum SEPARATOR '|||')
                    AS assetDescriptions,
                GROUP_CONCAT(COALESCE(NULLIF(a.serial_number,''),'-')
                    ORDER BY a.assetNum SEPARATOR '|||') AS assetSerialNumbers,
                GROUP_CONCAT(a.assetKat ORDER BY a.assetNum SEPARATOR ',')
                    AS assetCategoryCodes,
                GROUP_CONCAT(ac.Description ORDER BY a.assetNum SEPARATOR '|||')
                    AS assetCategoryNames,
                GROUP_CONCAT(s.Description ORDER BY a.assetNum SEPARATOR '|||')
                    AS perAssetStatusNames,
                MAX(e.empId) AS empId,
                MAX(e.fullname) AS empName,
                i.assignee_type AS assignmentType,
                MIN(i.issue_id) AS issueId,
                GROUP_CONCAT(i.issue_id ORDER BY i.issue_id) AS openBatchIssueIds,
                (
                    SELECT GROUP_CONCAT(ii.issue_id ORDER BY ii.issue_id)
                    FROM asset_issue ii
                    WHERE COALESCE(ii.batch_no,ii.issue_no) =
                          COALESCE(i.batch_no,i.issue_no)
                ) AS batchIssueIds,
                i.deptId AS deptId,
                MAX(d.deptName) AS deptName,
                MAX(i.acknowledged_by) AS acknowledgedBy,
                SUM(COALESCE(a.quantity,1)) AS quantity,
                GROUP_CONCAT(DISTINCT a.unit ORDER BY a.unit SEPARATOR ', ') AS unit,
                GROUP_CONCAT(DISTINCT l.description ORDER BY l.description SEPARATOR ', ')
                    AS locationName,
                GROUP_CONCAT(DISTINCT s.Description ORDER BY s.Description SEPARATOR ', ')
                    AS assetStatusName,
                GROUP_CONCAT(DISTINCT a.createdBy ORDER BY a.createdBy SEPARATOR ', ')
                    AS createdBy,
                MIN(a.createdDate) AS createdDate,
                MIN(i.issue_date) AS issueDate
            FROM assetdata a
            JOIN asset_issue i ON i.assetNum = a.assetNum AND i.status = 'ISSUED'
            LEFT JOIN employee e ON i.empId = e.empId
            LEFT JOIN department d ON i.deptId = d.deptId
            LEFT JOIN assetkat ac ON a.assetKat = ac.assetKat
            LEFT JOIN assetstat s ON a.assetStat = s.assetStatus
            LEFT JOIN location l ON a.location = l.location
            LEFT JOIN asset_request_allocation req_alloc ON req_alloc.issue_id = i.issue_id
            LEFT JOIN asset_request_item req_item
                ON req_item.request_item_id = req_alloc.request_item_id
            LEFT JOIN asset_request req ON req.request_id = req_item.request_id
            WHERE {$whereSql}
            GROUP BY COALESCE(i.batch_no,i.issue_no),
                     i.assignee_type,
                     i.empId,
                     i.deptId
            ORDER BY MIN(i.issue_date) DESC, MIN(i.issue_id) DESC
            LIMIT :active_page_limit OFFSET :active_page_offset
        ");
        foreach ($bindings as $key => $value) {
            $this->db->bind($key, $value);
        }
        $this->db->bind("active_page_limit", $perPage);
        $this->db->bind("active_page_offset", $offset);

        return [
            "items" => $this->db->resultSet(),
            "total" => $total,
            "page" => $page,
            "perPage" => $perPage,
            "totalPages" => $totalPages,
            "offset" => $offset,
        ];
    }

    /** Return assets that have been marked as scrapped. */
    public function getDisposedAssets()
    {
        try {
            $this->db->query("
          SELECT a.assetNum, a.Description, ac.Description AS assetKatName,
               e.fullname AS empName, a.quantity, a.unit,
               l.description AS locationName,
               s.Description AS assetStatusName,
               a.createdBy, a.createdDate
        FROM assetdata a
        LEFT JOIN employee e ON a.empId = e.empId
        LEFT JOIN assetkat ac ON a.assetKat = ac.assetKat
        LEFT JOIN assetstat s ON a.assetStat = s.assetStatus
        LEFT JOIN location l ON a.location = l.location
        WHERE a.assetStat = 'SCRAPPED'
        ORDER BY a.assetNum DESC
        ");

            $assets = $this->db->resultSet();

            if (!$assets) {
                error_log("getDisposedAssets(): No scrapped assets found.");
                return [];
            }

            return $assets;
        } catch (Exception $e) {
            error_log("getDisposedAssets() failed: " . $e->getMessage());
            return [];
        }
    }

    public function countTotalAssets()
    {
        $this->db->query("SELECT COUNT(*) AS total FROM assets");
        $result = $this->db->single();
        return ["total" => (int) $result["total"]];
    }

    public function deleteScrappedAsset($assetNum, $reason, $deletedBy)
    {
        $assetNum = trim($assetNum);
        $reason = trim($reason);
        if ($assetNum === "" || $reason === "") {
            throw new InvalidArgumentException("A deletion reason is required.");
        }
        $files = [];
        $archivePath = null;
        $this->db->beginTransaction();
        try {
            $this->db->query(
                "SELECT a.*,k.Description categoryName FROM assetdata a LEFT JOIN assetkat k ON k.assetKat=a.assetKat WHERE a.assetNum=:assetNum FOR UPDATE",
            );
            $this->db->bind("assetNum", $assetNum);
            $asset = $this->db->single();
            if (!$asset || $asset["assetStat"] !== "SCRAPPED") {
                throw new RuntimeException("Only scrapped assets can be permanently deleted.");
            }
            $this->db->query(
                "SELECT i.*,r.return_no,r.return_date,r.return_type,r.condition_in,r.reason return_reason,r.scrap_reference,e.fullname employeeName,d.deptName FROM asset_issue i LEFT JOIN asset_return r ON r.issue_id=i.issue_id LEFT JOIN employee e ON e.empId=i.empId LEFT JOIN department d ON d.deptId=i.deptId WHERE i.assetNum=:assetNum ORDER BY i.issue_date",
            );
            $this->db->bind("assetNum", $assetNum);
            $transactions = $this->db->resultSet();

            // Preserve workflow links before deleting the live foreign-key rows.
            // These records remain available inside the archived asset snapshot.
            $this->db->query(
                "SELECT rr.* FROM asset_return_request rr
                 JOIN asset_issue i ON i.issue_id=rr.issue_id
                 WHERE i.assetNum=:assetNum
                 ORDER BY rr.created_at,rr.return_request_id",
            );
            $this->db->bind("assetNum", $assetNum);
            $returnRequests = $this->db->resultSet();
            $this->db->query(
                "SELECT allocation.*,request_item.request_id,request_record.request_no
                 FROM asset_request_allocation allocation
                 LEFT JOIN asset_request_item request_item
                   ON request_item.request_item_id=allocation.request_item_id
                 LEFT JOIN asset_request request_record
                   ON request_record.request_id=request_item.request_id
                 WHERE allocation.assetNum=:assetNum
                 ORDER BY allocation.allocated_at,allocation.allocation_id",
            );
            $this->db->bind("assetNum", $assetNum);
            $requestAllocations = $this->db->resultSet();
            $this->db->query(
                "SELECT * FROM asset_scrap_request
                 WHERE assetNum=:assetNum
                 ORDER BY created_at,scrap_request_id",
            );
            $this->db->bind("assetNum", $assetNum);
            $scrapRequests = $this->db->resultSet();
            $this->db->query(
                "SELECT event_record.*
                 FROM asset_scrap_event event_record
                 JOIN asset_scrap_request request_record
                   ON request_record.scrap_request_id=event_record.scrap_request_id
                 WHERE request_record.assetNum=:assetNum
                 ORDER BY event_record.created_at,event_record.scrap_event_id",
            );
            $this->db->bind("assetNum", $assetNum);
            $scrapEvents = $this->db->resultSet();
            $asset["_archived_workflow"] = [
                "returnRequests" => $returnRequests,
                "requestAllocations" => $requestAllocations,
                "scrapRequests" => $scrapRequests,
                "scrapEvents" => $scrapEvents,
            ];

            $this->db->query(
                "SELECT media_id,media_stage,file_name,original_name,mime_type,file_size,uploaded_at FROM asset_media WHERE assetNum=:assetNum",
            );
            $this->db->bind("assetNum", $assetNum);
            $media = $this->db->resultSet();
            foreach ($media as $item) {
                $files[] = $item["file_name"];
            }
            $sourceName = basename((string) ($asset["assetImage"] ?? ""));
            if ($sourceName === " " || $sourceName === "") {
                $sourceName = basename((string) ($media[0]["file_name"] ?? ""));
            }
            $source = __DIR__ . "/../../public/uploads/asset/" . $sourceName;
            $archiveImage = null;
            if ($sourceName !== "" && is_file($source)) {
                $archiveDir = __DIR__ . "/../../public/uploads/archive/";
                if (!is_dir($archiveDir) && !mkdir($archiveDir, 0775, true)) {
                    throw new RuntimeException("Unable to create archive image directory.");
                }
                $extension = strtolower(pathinfo($sourceName, PATHINFO_EXTENSION)) ?: "jpg";
                $archiveImage =
                    SaasTenantContext::storagePrefix() .
                    "archive_" .
                    bin2hex(random_bytes(16)) .
                    "." .
                    $extension;
                $archivePath = $archiveDir . $archiveImage;
                if (!copy($source, $archivePath)) {
                    throw new RuntimeException("Unable to preserve the archive image.");
                }
            }
            $this->db->query(
                "INSERT INTO asset_deletion_audit(asset_num,asset_description,category_name,serial_number,archive_image,deleted_by,deletion_reason,asset_snapshot,transaction_snapshot,media_snapshot) VALUES(:assetNum,:description,:category,:serial,:archiveImage,:deletedBy,:reason,:assetSnapshot,:transactionSnapshot,:mediaSnapshot)",
            );
            $params = [
                "assetNum" => $assetNum,
                "description" => $asset["Description"],
                "category" => $asset["categoryName"],
                "serial" => $asset["serial_number"],
                "archiveImage" => $archiveImage,
                "deletedBy" => $deletedBy,
                "reason" => $reason,
                "assetSnapshot" => json_encode(
                    $asset,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                ),
                "transactionSnapshot" => json_encode(
                    $transactions,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                ),
                "mediaSnapshot" => json_encode(
                    $media,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                ),
            ];
            foreach ($params as $key => $value) {
                $this->db->bind($key, $value);
            }
            $this->db->executeOrFail();
            $this->db->query("DELETE FROM asset_media WHERE assetNum=:assetNum");
            $this->db->bind("assetNum", $assetNum);
            $this->db->executeOrFail();
            $this->db->query(
                "DELETE rr FROM asset_return_request rr
                 JOIN asset_issue i ON i.issue_id=rr.issue_id
                 WHERE i.assetNum=:assetNum",
            );
            $this->db->bind("assetNum", $assetNum);
            $this->db->executeOrFail();
            $this->db->query(
                "DELETE FROM asset_return WHERE issue_id IN (SELECT issue_id FROM asset_issue WHERE assetNum=:assetNum)",
            );
            $this->db->bind("assetNum", $assetNum);
            $this->db->executeOrFail();
            $this->db->query(
                "DELETE FROM assignment_email_log WHERE issue_id IN (SELECT issue_id FROM asset_issue WHERE assetNum=:assetNum)",
            );
            $this->db->bind("assetNum", $assetNum);
            $this->db->executeOrFail();
            $this->db->query(
                "DELETE FROM asset_request_allocation WHERE assetNum=:assetNum",
            );
            $this->db->bind("assetNum", $assetNum);
            $this->db->executeOrFail();
            $this->db->query("DELETE FROM asset_issue WHERE assetNum=:assetNum");
            $this->db->bind("assetNum", $assetNum);
            $this->db->executeOrFail();
            $this->db->query("DELETE FROM asset_receipt_item WHERE assetNum=:assetNum");
            $this->db->bind("assetNum", $assetNum);
            $this->db->executeOrFail();
            // Scrap events are removed automatically by the request's CASCADE
            // constraint after both have been preserved in the archive snapshot.
            $this->db->query(
                "DELETE FROM asset_scrap_request WHERE assetNum=:assetNum",
            );
            $this->db->bind("assetNum", $assetNum);
            $this->db->executeOrFail();
            $this->db->query(
                'DELETE FROM assetdata WHERE assetNum=:assetNum AND assetStat=\'SCRAPPED\'',
            );
            $this->db->bind("assetNum", $assetNum);
            $this->db->executeOrFail();
            if ($this->db->rowCount() !== 1) {
                throw new RuntimeException("Scrapped asset deletion did not complete.");
            }
            $this->db->commit();
            $dir = __DIR__ . "/../../public/uploads/asset/";
            foreach (array_unique($files) as $file) {
                $path = $dir . basename($file);
                if (is_file($path)) {
                    @unlink($path);
                }
            }
            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            if ($archivePath && is_file($archivePath)) {
                @unlink($archivePath);
            }
            throw $e;
        }
    }

    public function getScrappedArchive($from, $to, $category = "")
    {
        $sql =
            "SELECT deletion_id,DATE(deleted_at) archivedDate,deleted_at,asset_num assetNum,asset_description assetDescription,category_name category,serial_number,archive_image assetImage,deleted_by deletedBy,deletion_reason deletionReason,transaction_snapshot,media_snapshot FROM asset_deletion_audit WHERE DATE(deleted_at) BETWEEN :fromDate AND :toDate";
        if (trim($category) !== "") {
            $sql .= " AND category_name=:category";
        }
        $sql .= " ORDER BY deleted_at DESC,deletion_id DESC";
        $this->db->query($sql);
        $this->db->bind("fromDate", $from);
        $this->db->bind("toDate", $to);
        if (trim($category) !== "") {
            $this->db->bind("category", $category);
        }
        $rows = $this->db->resultSet();
        foreach ($rows as &$row) {
            $transactions = json_decode($row["transaction_snapshot"], true);
            $media = json_decode($row["media_snapshot"], true);
            $row["transactionCount"] = is_array($transactions) ? count($transactions) : 0;
            $row["mediaCount"] = is_array($media) ? count($media) : 0;
        }
        unset($row);
        return $rows;
    }

    /**
     * Rebuild the modal payload for an asset that has been permanently archived.
     * The live asset and transaction rows are removed during archival, so the
     * retained JSON snapshots are the authoritative source for this view.
     */
    public function getArchivedAssetDetails($assetNum)
    {
        $this->db->query(
            "SELECT deletion_id, deleted_at, asset_num, asset_description,
                    category_name, serial_number, archive_image, deleted_by,
                    deletion_reason, asset_snapshot, transaction_snapshot
             FROM asset_deletion_audit
             WHERE asset_num = :assetNum
             ORDER BY deletion_id DESC
             LIMIT 1",
        );
        $this->db->bind("assetNum", trim((string) $assetNum));
        $archive = $this->db->single();

        if (!$archive) {
            return null;
        }

        $snapshot = json_decode((string) $archive["asset_snapshot"], true);
        $asset = is_array($snapshot) ? $snapshot : [];
        $transactions = json_decode((string) $archive["transaction_snapshot"], true);
        $transactions = is_array($transactions) ? $transactions : [];

        // Ensure the modal still has its standard fields if an older snapshot
        // contains fewer columns than the current asset schema.
        $asset["assetNum"] = $archive["asset_num"];
        $asset["Description"] = $archive["asset_description"];
        $asset["assetKatName"] = $archive["category_name"];
        $asset["serial_number"] = $archive["serial_number"];
        $asset["assetStat"] = "ARCHIVED";
        $asset["assetStatusName"] = "Archived";
        $asset["assetImage"] = null;
        $asset["archiveImageUrl"] = !empty($archive["archive_image"])
            ? BASEURL . "/Asset/archivedMedia/" . rawurlencode($archive["archive_image"])
            : null;
        $asset["media"] = [];
        $asset["transactions"] = $transactions;

        $timeline = [];
        foreach ($transactions as $transaction) {
            $holder = $transaction["employeeName"] ??
                $transaction["deptName"] ??
                $transaction["acknowledged_by"] ??
                null;

            if (!empty($transaction["issue_date"]) || !empty($transaction["issue_no"])) {
                $timeline[] = [
                    "eventDate" => $transaction["issue_date"] ?? null,
                    "eventType" => "ASSIGN",
                    "referenceNo" => $transaction["issue_no"] ?? null,
                    "actor" => $transaction["issued_by"] ?? null,
                    "assignedTo" => $holder,
                    "department" => $transaction["deptName"] ?? null,
                    "conditionInfo" => $transaction["condition_out"] ?? null,
                    "details" => $transaction["purpose"] ?? null,
                ];
            }

            if (!empty($transaction["return_date"]) || !empty($transaction["return_no"])) {
                $timeline[] = [
                    "eventDate" => $transaction["return_date"] ?? null,
                    "eventType" => "RETURN",
                    "referenceNo" => $transaction["return_no"] ?? null,
                    "actor" => $transaction["received_by"] ?? null,
                    "assignedTo" => $holder,
                    "department" => $transaction["deptName"] ?? null,
                    "conditionInfo" => $transaction["condition_in"] ?? null,
                    "details" => $transaction["return_reason"] ?? null,
                ];
            }
        }

        $timeline[] = [
            "eventDate" => $archive["deleted_at"],
            "eventType" => "DELETE",
            "referenceNo" => "ARC-" . $archive["deletion_id"],
            "actor" => $archive["deleted_by"],
            "assignedTo" => null,
            "department" => null,
            "conditionInfo" => "Archived",
            "details" => $archive["deletion_reason"],
        ];
        $asset["timeline"] = $timeline;

        return $asset;
    }

    public function getAuditAssetOptions()
    {
        $this->db->query("SELECT assetNum,assetDescription FROM (
        SELECT assetNum,Description assetDescription FROM assetdata
        UNION
        SELECT asset_num assetNum,asset_description assetDescription FROM asset_deletion_audit
      ) assets ORDER BY assetNum");
        return $this->db->resultSet();
    }

    public function getAuditReport(
        $from,
        $to,
        $assetNum = "",
        $eventType = "",
        $category = "",
        $department = "",
        $search = "",
        $actor = "",
        $assignedTo = "",
        $condition = "",
        $limit = 0,
        bool $includeWorkflowEvents = true,
        bool $includeAccessoryEvents = false,
    ) {
        $fromDate = date("Y-m-d", strtotime($from));
        $toDate = date("Y-m-d", strtotime($to));
        $accessoryUnion = "";
        if ($includeAccessoryEvents) {
            // Accessory inventory remains physically separate; only its
            // immutable ledger is normalised into this read-only audit result.
            $accessoryUnion = "
            UNION ALL

            SELECT
                t.created_at,
                CONCAT('ACCESSORY_', t.event_type) COLLATE utf8mb4_unicode_ci,
                t.transaction_no COLLATE utf8mb4_unicode_ci,
                i.accessory_code COLLATE utf8mb4_unicode_ci,
                i.accessory_name COLLATE utf8mb4_unicode_ci,
                NULL,
                i.category COLLATE utf8mb4_unicode_ci,
                NULL,
                NULL,
                t.performed_by COLLATE utf8mb4_unicode_ci,
                CASE
                    WHEN t.recipient_type='EMPLOYEE' THEN e.fullname
                    WHEN t.recipient_type='DEPARTMENT' THEN d.deptName
                    ELSE NULL
                END COLLATE utf8mb4_unicode_ci,
                d.deptName COLLATE utf8mb4_unicode_ci,
                CONCAT(
                    'Qty ', TRIM(TRAILING '.000' FROM CAST(t.quantity AS CHAR)),
                    ' ', i.unit,
                    ' | Balance ', TRIM(TRAILING '.000' FROM CAST(t.balance_before AS CHAR)),
                    ' -> ', TRIM(TRAILING '.000' FROM CAST(t.balance_after AS CHAR))
                ) COLLATE utf8mb4_unicode_ci,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                CONCAT(
                    'Accessory ', REPLACE(t.event_type, '_', ' '),
                    ' / Quantity: ', TRIM(TRAILING '.000' FROM CAST(t.quantity AS CHAR)),
                    ' ', i.unit,
                    ' / Reference: ', COALESCE(t.reference_no, '-'),
                    ' / Remarks: ', t.remarks
                ) COLLATE utf8mb4_unicode_ci
            FROM accessory_transaction t
            JOIN accessory_item i ON i.accessory_id=t.accessory_id
            LEFT JOIN employee e ON e.empId=t.empId
            LEFT JOIN department d ON d.deptId=t.deptId";
        }
        // Normalise receipt, current in-stock state, controlled asset edits,
        // assignment, return, and deletion events so report filters operate
        // on every lifecycle event. IN_STOCK is a current-state snapshot dated
        // at the asset's most recent entry into stock (return or receipt).
        $sql = "SELECT * FROM (
            SELECT
                ar.created_at AS eventDate,
                'RECEIPT' AS eventType,
                ar.receipt_no AS referenceNo,
                a.assetNum,
                a.Description AS assetDescription,
                a.assetImage,
                k.Description AS category,
                a.purchase_date AS purchaseDate,
                a.warranty_expiry AS warrantyExpiry,
                ar.received_by AS actor,
                NULL AS assignedTo,
                NULL AS department,
                ri.condition_on_receipt AS conditionInfo,
                ar.supplier_name AS supplierName,
                ar.po_number AS poNumber,
                ar.invoice_number AS invoiceNumber,
                ar.invoice_pdf AS invoicePdf,
                ar.notes AS receiptNote,
                CONCAT(
                    'Supplier: ', COALESCE(ar.supplier_name, '-'),
                    ' / PO: ', COALESCE(ar.po_number, '-'),
                    ' / Invoice: ', COALESCE(ar.invoice_number, '-'),
                    ' / Receipt Note: ', COALESCE(ar.notes, '-')
                ) AS details
            FROM asset_receipt_item ri
            JOIN asset_receipt ar ON ar.receipt_id = ri.receipt_id
            JOIN assetdata a ON a.assetNum = ri.assetNum
            LEFT JOIN assetkat k ON k.assetKat = a.assetKat

            UNION ALL

            SELECT
                COALESCE(last_return.created_at, ar.created_at, a.createdDate),
                'IN_STOCK',
                CONCAT('STOCK-', a.assetNum),
                a.assetNum,
                a.Description,
                a.assetImage,
                k.Description,
                a.purchase_date,
                a.warranty_expiry,
                COALESCE(last_return.received_by, ar.received_by, a.createdBy),
                NULL,
                NULL,
                a.asset_condition,
                ar.supplier_name,
                ar.po_number,
                ar.invoice_number,
                ar.invoice_pdf,
                ar.notes,
                CONCAT(
                    'Currently in stock / Location: ', COALESCE(l.Description, a.location, '-'),
                    ' / Condition: ', COALESCE(a.asset_condition, '-')
                )
            FROM assetdata a
            LEFT JOIN assetkat k ON k.assetKat = a.assetKat
            LEFT JOIN asset_receipt ar ON ar.receipt_id = a.receipt_id
            LEFT JOIN location l ON l.location = a.location
            LEFT JOIN (
                SELECT i.assetNum, r.created_at, r.received_by
                FROM asset_return r
                JOIN asset_issue i ON i.issue_id = r.issue_id
                JOIN (
                    SELECT i2.assetNum, MAX(r2.return_id) AS return_id
                    FROM asset_return r2
                    JOIN asset_issue i2 ON i2.issue_id = r2.issue_id
                    GROUP BY i2.assetNum
                ) latest_return ON latest_return.assetNum = i.assetNum
                                  AND latest_return.return_id = r.return_id
            ) last_return ON last_return.assetNum = a.assetNum
            WHERE a.assetStat = 'IN_STOCK'

            UNION ALL

            SELECT
                i.issue_date,
                'ASSIGN',
                i.issue_no,
                i.assetNum,
                a.Description,
                a.assetImage,
                k.Description,
                NULL,
                NULL,
                i.issued_by,
                COALESCE(e.fullname, i.acknowledged_by),
                d.deptName,
                i.condition_out,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                CONCAT(i.assignee_type, ' / ', COALESCE(i.purpose, 'No purpose'))
            FROM asset_issue i
            JOIN assetdata a ON a.assetNum = i.assetNum
            LEFT JOIN assetkat k ON k.assetKat = a.assetKat
            LEFT JOIN employee e ON e.empId = i.empId
            LEFT JOIN department d ON d.deptId = i.deptId
            LEFT JOIN asset_request_allocation request_allocation
              ON request_allocation.issue_id=i.issue_id
            LEFT JOIN asset_request_item request_line
              ON request_line.request_item_id=request_allocation.request_item_id
            LEFT JOIN asset_request equipment_request
              ON equipment_request.request_id=request_line.request_id
            WHERE equipment_request.request_id IS NULL
               OR equipment_request.status='COMPLETED'

            UNION ALL

            SELECT
                r.created_at,
                'RETURN',
                r.return_no,
                i.assetNum,
                a.Description,
                a.assetImage,
                k.Description,
                NULL,
                NULL,
                r.received_by,
                COALESCE(e.fullname, i.acknowledged_by),
                d.deptName,
                r.condition_in,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                CONCAT(r.return_type, ' / ', COALESCE(r.reason, 'No reason'))
            FROM asset_return r
            JOIN asset_issue i ON i.issue_id = r.issue_id
            JOIN assetdata a ON a.assetNum = i.assetNum
            LEFT JOIN assetkat k ON k.assetKat = a.assetKat
            LEFT JOIN employee e ON e.empId = i.empId
            LEFT JOIN department d ON d.deptId = i.deptId

            UNION ALL

            SELECT
                ua.changed_at,
                'ASSET_UPDATE',
                CONCAT('UPD-', ua.update_audit_id),
                ua.assetNum,
                a.Description,
                a.assetImage,
                k.Description,
                NULL,
                NULL,
                ua.changed_by,
                NULL,
                NULL,
                COALESCE(
                    NULLIF(
                        JSON_UNQUOTE(
                            JSON_EXTRACT(ua.new_snapshot, '$.asset_condition')
                        ),
                        ''
                    ),
                    '-'
                ),
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                CONCAT(
                    'Reason: ', ua.change_reason,
                    ' / Changes: ', ua.change_summary
                )
            FROM asset_update_audit ua
            JOIN assetdata a ON a.assetNum=ua.assetNum
            LEFT JOIN assetkat k ON k.assetKat=a.assetKat

            UNION ALL

            SELECT
                x.deleted_at,
                'DELETE',
                CONCAT('DEL-', x.deletion_id),
                x.asset_num,
                x.asset_description,
                NULL,
                x.category_name,
                NULL,
                NULL,
                x.deleted_by,
                NULL,
                NULL,
                'SCRAPPED',
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                CONCAT(x.deletion_reason, ' / Full snapshot retained')
            FROM asset_deletion_audit x

            UNION ALL

            SELECT
                sr.reviewed_at,
                CASE WHEN sr.status='APPROVED' THEN 'SCRAP_APPROVED' ELSE 'SCRAP_REJECTED' END,
                sr.scrap_request_no,
                sr.assetNum,
                a.Description,
                a.assetImage,
                k.Description,
                NULL,
                NULL,
                sr.reviewed_by,
                NULL,
                NULL,
                sr.status,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                CONCAT(sr.reason, ' / Review: ', COALESCE(sr.review_comment, 'No comment'))
            FROM asset_scrap_request sr
            JOIN assetdata a ON a.assetNum=sr.assetNum
            LEFT JOIN assetkat k ON k.assetKat=a.assetKat
            WHERE sr.reviewed_at IS NOT NULL
            " . $accessoryUnion . "
        ) audit
        WHERE eventDate >= :fromDate
          AND eventDate < :toExclusive";
        if (!$includeWorkflowEvents) {
            $sql .= " AND eventType NOT IN ('SCRAP_APPROVED','SCRAP_REJECTED')";
        }
        $assetNum = trim($assetNum);
        if ($assetNum !== "") {
            $sql .= " AND assetNum=:assetNum";
        }
        $eventType = strtoupper(trim((string) $eventType));
        if ($eventType !== "") {
            $sql .= " AND eventType=:eventType";
        }
        $category = trim((string) $category);
        if ($category !== "") {
            $sql .= " AND category=:category";
        }
        $department = trim((string) $department);
        if ($department !== "") {
            $sql .= " AND department=:department";
        }
        $search = trim((string) $search);
        if ($search !== "") {
            $sql .= " AND (assetNum LIKE :searchAsset OR assetDescription LIKE :searchDescription OR referenceNo LIKE :searchReference OR details LIKE :searchDetails)";
        }
        $actor = trim((string) $actor);
        if ($actor !== "") {
            $sql .= " AND actor LIKE :actor";
        }
        $assignedTo = trim((string) $assignedTo);
        if ($assignedTo !== "") {
            $sql .= " AND assignedTo LIKE :assignedTo";
        }
        $condition = trim((string) $condition);
        if ($condition !== "") {
            $sql .= " AND conditionInfo LIKE :condition";
        }
        $sql .= " ORDER BY eventDate DESC,eventType,assetNum";
        $limit = max(0, min((int) $limit, 5000));
        if ($limit > 0) {
            $sql .= " LIMIT :auditLimit";
        }
        $this->db->query($sql);
        $this->db->bind("fromDate", $fromDate);
        $this->db->bind(
            "toExclusive",
            date("Y-m-d", strtotime($toDate . " +1 day")),
        );
        if ($assetNum !== "") {
            $this->db->bind("assetNum", $assetNum);
        }
        if ($eventType !== "") {
            $this->db->bind("eventType", $eventType);
        }
        if ($category !== "") {
            $this->db->bind("category", $category);
        }
        if ($department !== "") {
            $this->db->bind("department", $department);
        }
        if ($search !== "") {
            $searchValue = "%" . $search . "%";
            $this->db->bind("searchAsset", $searchValue);
            $this->db->bind("searchDescription", $searchValue);
            $this->db->bind("searchReference", $searchValue);
            $this->db->bind("searchDetails", $searchValue);
        }
        if ($actor !== "") {
            $this->db->bind("actor", "%" . $actor . "%");
        }
        if ($assignedTo !== "") {
            $this->db->bind("assignedTo", "%" . $assignedTo . "%");
        }
        if ($condition !== "") {
            $this->db->bind("condition", "%" . $condition . "%");
        }
        if ($limit > 0) {
            $this->db->bind("auditLimit", $limit);
        }
        return $this->db->resultSet();
    }

    public function getDashboardAnalytics()
    {
        $this->db->query("SELECT
      (SELECT COUNT(*) FROM assetdata) totalAssets,
      (SELECT COUNT(*) FROM assetdata WHERE assetStat='IN_STOCK') inStock,
      (SELECT COUNT(*) FROM asset_issue WHERE status='ISSUED') assigned,
      (SELECT COUNT(*) FROM assetdata WHERE assetStat='SCRAPPED') scrapped,
      (SELECT COUNT(*) FROM asset_receipt) receipts,
      (SELECT COUNT(*) FROM asset_issue) totalIssues,
      (SELECT COUNT(*) FROM asset_return) totalReturns,
      (SELECT COUNT(*) FROM asset_return WHERE return_type='REISSUE') reissued,
      (SELECT COUNT(*) FROM asset_return WHERE return_type='SCRAP') scrapReturns,
      (SELECT COUNT(*) FROM employee WHERE aktif=1) activeEmployees,
      (SELECT COUNT(*) FROM department WHERE active=1) departments,
      (SELECT COUNT(*) FROM asset_media) mediaFiles");
        $summary = $this->db->single();

        $this->db->query("SELECT k.assetKat,k.Description category,COUNT(a.assetNum) total,
      SUM(CASE WHEN a.assetStat='ASSIGNED' THEN 1 ELSE 0 END) assigned,
      SUM(CASE WHEN a.assetStat='IN_STOCK' THEN 1 ELSE 0 END) inStock,
      SUM(CASE WHEN a.assetStat='SCRAPPED' THEN 1 ELSE 0 END) scrapped
      FROM assetkat k LEFT JOIN assetdata a ON a.assetKat=k.assetKat
      GROUP BY k.assetKat,k.Description ORDER BY total DESC,k.Description");
        $categories = $this->db->resultSet();

        $this->db->query("SELECT d.deptId,d.deptName,e.fullname headName,
      (SELECT COUNT(*) FROM employee x WHERE x.deptId=d.deptId AND x.aktif=1) employeeCount,
      (SELECT COUNT(*) FROM asset_issue i WHERE i.deptId=d.deptId AND i.status='ISSUED') assignedAssets
      FROM department d LEFT JOIN employee e ON e.empId=COALESCE(
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
      WHERE d.active=1 ORDER BY assignedAssets DESC,d.deptName");
        $departments = $this->db->resultSet();

        $this->db
            ->query("SELECT e.empId,e.fullname,COALESCE(ed.deptName,hd.deptName,e.department) department,
      (SELECT COUNT(*) FROM asset_issue i WHERE i.empId=e.empId AND i.status='ISSUED') assignedAssets
      FROM employee e LEFT JOIN department ed ON ed.deptId=e.deptId
      LEFT JOIN department hd ON hd.headEmpId=e.empId WHERE e.aktif=1
      ORDER BY assignedAssets DESC,e.fullname LIMIT 10");
        $employees = $this->db->resultSet();

        $this->db
            ->query("SELECT month,SUM(received) received,SUM(issued) issued,SUM(returned) returned
      FROM (
        SELECT DATE_FORMAT(received_date,'%Y-%m') month,COUNT(*) received,0 issued,0 returned FROM asset_receipt GROUP BY month
        UNION ALL SELECT DATE_FORMAT(issue_date,'%Y-%m'),0,COUNT(*),0 FROM asset_issue GROUP BY DATE_FORMAT(issue_date,'%Y-%m')
        UNION ALL SELECT DATE_FORMAT(return_date,'%Y-%m'),0,0,COUNT(*) FROM asset_return GROUP BY DATE_FORMAT(return_date,'%Y-%m')
      ) flow GROUP BY month ORDER BY month DESC LIMIT 12");
        $flow = array_reverse($this->db->resultSet());

        $this->db->query("SELECT * FROM (
      SELECT ar.created_at eventDate,'RECEIPT' eventType,ar.receipt_no referenceNo,NULL assetNum,COALESCE(ar.supplier_name,'Asset receipt') details FROM asset_receipt ar
      UNION ALL SELECT ai.created_at,'ISSUE',ai.issue_no,ai.assetNum,CONCAT(ai.assignee_type,' assignment') FROM asset_issue ai
      UNION ALL SELECT r.created_at,'RETURN',r.return_no,i.assetNum,CONCAT(r.return_type,' / ',r.condition_in) FROM asset_return r JOIN asset_issue i ON i.issue_id=r.issue_id
      ) events ORDER BY eventDate DESC LIMIT 15");
        $recent = $this->db->resultSet();
        return compact("summary", "categories", "departments", "employees", "flow", "recent");
    }

    /**
     * Persistent warranty reminder totals.
     *
     * Expired assets remain in the reminder count until their warranty date is
     * renewed/updated. Scrapped assets are excluded because they are no longer
     * candidates for warranty renewal.
     */
    public function getWarrantyAlertSummary(): array
    {
        $this->db->query("SELECT
            SUM(CASE WHEN warranty_expiry<CURDATE() THEN 1 ELSE 0 END) AS expired,
            SUM(CASE WHEN warranty_expiry>=CURDATE()
                      AND warranty_expiry<=DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                     THEN 1 ELSE 0 END) AS dueSoon,
            SUM(CASE WHEN warranty_expiry>=CURDATE() THEN 1 ELSE 0 END) AS active,
            SUM(CASE WHEN warranty_expiry IS NULL THEN 1 ELSE 0 END) AS nonWarranty
            FROM assetdata
            WHERE assetStat<>'SCRAPPED'");
        $summary = $this->db->single() ?: [];
        $expired = (int) ($summary["expired"] ?? 0);
        $dueSoon = (int) ($summary["dueSoon"] ?? 0);
        $active = (int) ($summary["active"] ?? 0);
        $nonWarranty = (int) ($summary["nonWarranty"] ?? 0);

        return [
            "expired" => $expired,
            "dueSoon" => $dueSoon,
            "active" => $active,
            "nonWarranty" => $nonWarranty,
            "count" => $expired + $dueSoon,
        ];
    }

    /**
     * Return a bounded set of individual warranty lifecycles intersecting the
     * selected period. The count remains accurate even when the visual list is
     * capped, keeping the dashboard usable with very large asset inventories.
     */
    public function getWarrantyTimeline(
        string $fromDate,
        string $toDate,
        int $limit = 100,
    ): array {
        $from = DateTimeImmutable::createFromFormat("!Y-m-d", $fromDate);
        $to = DateTimeImmutable::createFromFormat("!Y-m-d", $toDate);
        if (
            !$from ||
            !$to ||
            $from->format("Y-m-d") !== $fromDate ||
            $to->format("Y-m-d") !== $toDate ||
            $from > $to
        ) {
            throw new InvalidArgumentException("Select a valid warranty timeline period.");
        }

        // A zero limit is used by the summary-only dashboard. Individual
        // lifecycle rows remain available for a future detailed report.
        $limit = max(0, min($limit, 250));
        $where = "a.purchase_date IS NOT NULL
            AND a.warranty_expiry IS NOT NULL
            AND a.purchase_date<=:timelineTo
            AND a.warranty_expiry>=:timelineFrom
            AND a.assetStat<>'SCRAPPED'";

        $this->db->query("SELECT COUNT(*) AS total
            FROM assetdata a
            WHERE " . $where);
        $this->db->bind("timelineTo", $toDate);
        $this->db->bind("timelineFrom", $fromDate);
        $totalRow = $this->db->single() ?: [];

        $items = [];
        if ($limit > 0) {
            $this->db->query("SELECT
                    a.assetNum,
                    a.Description,
                    a.serial_number,
                    a.purchase_date,
                    a.warranty_expiry,
                    a.assetStat,
                    k.Description AS category,
                    DATEDIFF(a.warranty_expiry, CURDATE()) AS daysRemaining
                FROM assetdata a
                LEFT JOIN assetkat k ON k.assetKat=a.assetKat
                WHERE " . $where . "
                ORDER BY a.warranty_expiry ASC, a.purchase_date ASC, a.assetNum ASC
                LIMIT " . $limit);
            $this->db->bind("timelineTo", $toDate);
            $this->db->bind("timelineFrom", $fromDate);
            $items = $this->db->resultSet();
        }

        // Short ranges are summarized by month; longer ranges by year so the
        // graph remains readable while preserving the selected date window.
        $rangeDays = (int) $from->diff($to)->format("%a");
        $groupByYear = $rangeDays > 730;
        $purchasePeriod = $groupByYear
            ? "DATE_FORMAT(purchase_date,'%Y')"
            : "DATE_FORMAT(purchase_date,'%Y-%m')";
        $expiryPeriod = $groupByYear
            ? "DATE_FORMAT(warranty_expiry,'%Y')"
            : "DATE_FORMAT(warranty_expiry,'%Y-%m')";
        $this->db->query("SELECT period,
                SUM(purchased) AS purchased,
                SUM(expiring) AS expiring,
                SUM(nonWarranty) AS nonWarranty
            FROM (
                SELECT " . $purchasePeriod . " AS period,
                    COUNT(*) AS purchased,
                    0 AS expiring,
                    0 AS nonWarranty
                FROM assetdata
                WHERE purchase_date BETWEEN :summaryFromPurchase AND :summaryToPurchase
                  AND assetStat<>'SCRAPPED'
                GROUP BY " . $purchasePeriod . "
                UNION ALL
                SELECT " . $expiryPeriod . " AS period,
                    0 AS purchased,
                    COUNT(*) AS expiring,
                    0 AS nonWarranty
                FROM assetdata
                WHERE warranty_expiry BETWEEN :summaryFromExpiry AND :summaryToExpiry
                  AND assetStat<>'SCRAPPED'
                GROUP BY " . $expiryPeriod . "
                UNION ALL
                SELECT " . $purchasePeriod . " AS period,
                    0 AS purchased,
                    0 AS expiring,
                    COUNT(*) AS nonWarranty
                FROM assetdata
                WHERE purchase_date BETWEEN :summaryFromNoWarranty AND :summaryToNoWarranty
                  AND warranty_expiry IS NULL
                  AND assetStat<>'SCRAPPED'
                GROUP BY " . $purchasePeriod . "
            ) warranty_summary
            GROUP BY period
            ORDER BY period");
        $this->db->bind("summaryFromPurchase", $fromDate);
        $this->db->bind("summaryToPurchase", $toDate);
        $this->db->bind("summaryFromExpiry", $fromDate);
        $this->db->bind("summaryToExpiry", $toDate);
        $this->db->bind("summaryFromNoWarranty", $fromDate);
        $this->db->bind("summaryToNoWarranty", $toDate);
        $summaryRows = $this->db->resultSet();

        return [
            "from" => $fromDate,
            "to" => $toDate,
            "total" => (int) ($totalRow["total"] ?? 0),
            "limit" => $limit,
            "items" => $items,
            "alerts" => $this->getWarrantyAlertSummary(),
            "summaryGraph" => [
                "granularity" => $groupByYear ? "year" : "month",
                "rows" => $summaryRows,
            ],
        ];
    }

    /**
     * Paginated individual warranty-duration report with indexed, prefix-based
     * searching. This page is separate from the dashboard summary so large
     * inventories never render thousands of lifecycle rows at once.
     */
    public function getWarrantyTimelinePage(array $filters = []): array
    {
        $page = max(1, (int) ($filters["page"] ?? 1));
        $allowedPageSizes = [25, 50, 100];
        $perPage = (int) ($filters["perPage"] ?? 25);
        if (!in_array($perPage, $allowedPageSizes, true)) {
            $perPage = 25;
        }

        $fromDate = trim((string) ($filters["from"] ?? ""));
        $toDate = trim((string) ($filters["to"] ?? ""));
        $from = DateTimeImmutable::createFromFormat("!Y-m-d", $fromDate);
        $to = DateTimeImmutable::createFromFormat("!Y-m-d", $toDate);
        if (
            !$from ||
            !$to ||
            $from->format("Y-m-d") !== $fromDate ||
            $to->format("Y-m-d") !== $toDate ||
            $from > $to
        ) {
            throw new InvalidArgumentException("Select a valid duration report period.");
        }

        $cut = static function ($value, int $length): string {
            $value = trim((string) $value);
            return function_exists("mb_substr")
                ? mb_substr($value, 0, $length, "UTF-8")
                : substr($value, 0, $length);
        };
        $search = $cut($filters["search"] ?? "", 100);
        $category = $cut($filters["category"] ?? "", 20);
        $status = strtoupper($cut($filters["status"] ?? "ALL", 20));
        if (!in_array($status, ["ALL", "ACTIVE", "DUE_SOON", "EXPIRED"], true)) {
            $status = "ALL";
        }
        $sort = strtoupper($cut($filters["sort"] ?? "DURATION_ASC", 30));
        $sortOptions = [
            "DURATION_ASC" =>
                "DATEDIFF(a.warranty_expiry,a.purchase_date) ASC,a.warranty_expiry ASC,a.assetNum ASC",
            "DURATION_DESC" =>
                "DATEDIFF(a.warranty_expiry,a.purchase_date) DESC,a.warranty_expiry DESC,a.assetNum ASC",
            "EXPIRY_ASC" => "a.warranty_expiry ASC,a.assetNum ASC",
            "EXPIRY_DESC" => "a.warranty_expiry DESC,a.assetNum ASC",
            "PURCHASE_ASC" => "a.purchase_date ASC,a.assetNum ASC",
            "PURCHASE_DESC" => "a.purchase_date DESC,a.assetNum ASC",
            "ASSET_ASC" => "a.assetNum ASC",
        ];
        if (!isset($sortOptions[$sort])) {
            $sort = "DURATION_ASC";
        }

        $where = [
            "a.purchase_date IS NOT NULL",
            "a.warranty_expiry IS NOT NULL",
            "a.purchase_date<=:durationTo",
            "a.warranty_expiry>=:durationFrom",
            "a.assetStat<>'SCRAPPED'",
        ];
        $bindings = [
            "durationTo" => $toDate,
            "durationFrom" => $fromDate,
        ];
        if ($category !== "" && $category !== "ALL") {
            $where[] = "a.assetKat=:durationCategory";
            $bindings["durationCategory"] = $category;
        }
        if ($search !== "") {
            // Prefix matching protects performance on million-row inventories.
            $where[] = "(a.assetNum LIKE :durationAssetSearch
                OR a.serial_number LIKE :durationSerialSearch
                OR a.Description LIKE :durationDescriptionSearch)";
            $bindings["durationAssetSearch"] = $search . "%";
            $bindings["durationSerialSearch"] = $search . "%";
            $bindings["durationDescriptionSearch"] = $search . "%";
        }
        if ($status === "EXPIRED") {
            $where[] = "a.warranty_expiry<CURDATE()";
        } elseif ($status === "DUE_SOON") {
            $where[] =
                "a.warranty_expiry>=CURDATE()
                 AND a.warranty_expiry<=DATE_ADD(CURDATE(),INTERVAL 30 DAY)";
        } elseif ($status === "ACTIVE") {
            $where[] = "a.warranty_expiry>DATE_ADD(CURDATE(),INTERVAL 30 DAY)";
        }
        $whereSql = implode(" AND ", $where);

        $this->db->query("SELECT COUNT(*) AS total
            FROM assetdata a
            WHERE " . $whereSql);
        foreach ($bindings as $key => $value) {
            $this->db->bind($key, $value);
        }
        $countRow = $this->db->single() ?: [];
        $total = (int) ($countRow["total"] ?? 0);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $this->db->query("SELECT
                a.assetNum,
                a.Description,
                a.serial_number,
                a.purchase_date,
                a.warranty_expiry,
                a.assetStat,
                k.Description AS category,
                DATEDIFF(a.warranty_expiry,a.purchase_date) AS durationDays,
                DATEDIFF(a.warranty_expiry,CURDATE()) AS daysRemaining
            FROM assetdata a
            LEFT JOIN assetkat k ON k.assetKat=a.assetKat
            WHERE " . $whereSql . "
            ORDER BY " . $sortOptions[$sort] . "
            LIMIT :durationLimit OFFSET :durationOffset");
        foreach ($bindings as $key => $value) {
            $this->db->bind($key, $value);
        }
        $this->db->bind("durationLimit", $perPage);
        $this->db->bind("durationOffset", $offset);

        return [
            "items" => $this->db->resultSet(),
            "total" => $total,
            "page" => $page,
            "perPage" => $perPage,
            "totalPages" => $totalPages,
            "offset" => $offset,
            "filters" => [
                "from" => $fromDate,
                "to" => $toDate,
                "search" => $search,
                "category" => $category === "" ? "ALL" : $category,
                "status" => $status,
                "sort" => $sort,
                "perPage" => $perPage,
            ],
        ];
    }

    public function countAssetsInUse()
    {
        $this->db->query("SELECT COUNT(*) AS total FROM assets WHERE status = 'in_use'");
        $result = $this->db->single();
        return ["total" => (int) $result["total"]];
    }

    public function countAssetsNotInUse()
    {
        $this->db->query("SELECT COUNT(*) AS total FROM assets WHERE status != 'in_use'");
        $result = $this->db->single();
        return ["total" => (int) $result["total"]];
    }

    public function getLastAssetNum()
    {
        $this->db->query("
        SELECT assetNum 
        FROM assetdata 
        ORDER BY assetNum DESC 
        LIMIT 1
    ");

        return $this->db->single();
    }

    public function getAssetMedia($assetNum)
    {
        $this
            ->db->query("SELECT media_id,media_stage,file_name,original_name,mime_type,file_size,uploaded_at
        FROM asset_media WHERE assetNum=:assetNum ORDER BY uploaded_at,media_id");
        $this->db->bind("assetNum", $assetNum);
        return $this->db->resultSet();
    }

    public function getAssetTransactionHistory($assetNum)
    {
        $asset = $this->getAssetEntryById($assetNum);
        if (!$asset) {
            return null;
        }
        $this
            ->db->query("SELECT i.issue_no,i.issue_date,i.assignee_type,i.condition_out,i.purpose,i.status issue_status,
      i.expected_return_date,i.returned_at,e.fullname employee_name,d.deptName,i.acknowledged_by,
      r.return_no,r.return_date,r.return_type,r.condition_in,r.reason,r.scrap_reference,r.received_by
      FROM asset_issue i LEFT JOIN employee e ON e.empId=i.empId LEFT JOIN department d ON d.deptId=i.deptId
      LEFT JOIN asset_return r ON r.issue_id=i.issue_id WHERE i.assetNum=:assetNum ORDER BY i.issue_date");
        $this->db->bind("assetNum", $assetNum);
        $transactions = $this->db->resultSet();
        return [
            "asset" => $asset,
            "transactions" => $transactions,
            "media" => $this->getAssetMedia($assetNum),
        ];
    }

    /** Current assets assigned personally or to a department the employee heads. */
    public function getMyAssignedAssets(
        int $employeeId,
        bool $departmentOnly = false,
    ): array
    {
        if ($employeeId <= 0) {
            return [];
        }
        $scopeFilter = $departmentOnly
            ? "i.assignee_type='DEPARTMENT' AND COALESCE(
                d.headEmpId,
                (
                    SELECT MIN(legacy_head.empId)
                    FROM department_head legacy_head
                    JOIN employee legacy_employee
                      ON legacy_employee.empId=legacy_head.empId
                     AND legacy_employee.aktif=1
                    WHERE legacy_head.deptId=d.deptId
                )
              )=:scopeEmployee"
            : "i.assignee_type='USER' AND i.empId=:scopeEmployee";

        $this->db->query("SELECT
            i.issue_id,i.issue_no,i.batch_no,i.issue_date,i.expected_return_date,i.assignee_type,
            i.condition_out,i.purpose,i.issued_by,i.status,i.assetNum,
            a.Description AS assetDescription,a.serial_number,a.manufacturer,a.model,a.assetImage,
            k.Description AS categoryName,l.description AS locationName,
            e.fullname AS employeeName,d.deptName,
            req.request_no,req.status AS requestStatus,req.receiver_accepted_at,
            req.receiver_accepted_by,
            return_request.return_request_id,return_request.return_request_no,return_request.status AS returnRequestStatus,
            return_request.reason AS returnRequestReason,return_request.created_at AS returnRequestedAt
            FROM asset_issue i
            JOIN assetdata a ON a.assetNum=i.assetNum
            LEFT JOIN assetkat k ON k.assetKat=a.assetKat
            LEFT JOIN location l ON l.location=a.location
            LEFT JOIN employee e ON e.empId=i.empId
            LEFT JOIN department d ON d.deptId=i.deptId
            LEFT JOIN asset_request_allocation allocation ON allocation.issue_id=i.issue_id
            LEFT JOIN asset_request_item request_item ON request_item.request_item_id=allocation.request_item_id
            LEFT JOIN asset_request req ON req.request_id=request_item.request_id
            LEFT JOIN asset_return_request return_request
              ON return_request.return_request_id=(
                SELECT MAX(latest_request.return_request_id)
                FROM asset_return_request latest_request
                WHERE latest_request.issue_id=i.issue_id
              )
            WHERE i.status='ISSUED'
              AND {$scopeFilter}
            ORDER BY i.issue_date DESC,i.issue_id DESC");
        $this->db->bind("scopeEmployee", $employeeId);
        return $this->db->resultSet();
    }

    /**
     * Complete custody history for an employee and any department for which
     * they are the current Section Head.
     */
    public function getMyAssetHistory(
        int $employeeId,
        bool $departmentOnly = false,
    ): array
    {
        if ($employeeId <= 0) {
            return [];
        }

        $scopeFilter = $departmentOnly
            ? "i.assignee_type='DEPARTMENT' AND COALESCE(
                d.headEmpId,
                (
                    SELECT MIN(legacy_head.empId)
                    FROM department_head legacy_head
                    JOIN employee legacy_employee
                      ON legacy_employee.empId=legacy_head.empId
                     AND legacy_employee.aktif=1
                    WHERE legacy_head.deptId=d.deptId
                )
              )=:historyEmployee"
            : "i.assignee_type='USER' AND i.empId=:historyEmployee";

        $this->db->query("SELECT
            i.issue_id,i.issue_no,i.batch_no,i.issue_date,i.expected_return_date,
            i.assignee_type,i.condition_out,i.purpose,i.issued_by,
            i.status AS issueStatus,i.returned_at,i.assetNum,
            a.Description AS assetDescription,a.serial_number,
            a.manufacturer,a.model,k.Description AS categoryName,
            e.fullname AS employeeName,d.deptName,
            req.request_no,req.receiver_accepted_at,
            req.receiver_accepted_by,
            return_request.return_request_id,
            return_request.return_request_no,
            return_request.status AS returnRequestStatus,
            return_request.reason AS returnRequestReason,
            return_request.created_at AS returnRequestedAt,
            r.return_id,r.return_no,r.batch_no AS returnBatchNo,r.return_date,r.condition_in,
            r.reason AS returnReason,r.received_by
            FROM asset_issue i
            JOIN assetdata a ON a.assetNum=i.assetNum
            LEFT JOIN assetkat k ON k.assetKat=a.assetKat
            LEFT JOIN employee e ON e.empId=i.empId
            LEFT JOIN department d ON d.deptId=i.deptId
            LEFT JOIN asset_request_allocation allocation
              ON allocation.issue_id=i.issue_id
            LEFT JOIN asset_request_item request_item
              ON request_item.request_item_id=allocation.request_item_id
            LEFT JOIN asset_request req
              ON req.request_id=request_item.request_id
            LEFT JOIN asset_return r ON r.issue_id=i.issue_id
            LEFT JOIN asset_return_request return_request
              ON return_request.return_request_id=(
                SELECT MAX(latest_request.return_request_id)
                FROM asset_return_request latest_request
                WHERE latest_request.issue_id=i.issue_id
              )
            WHERE {$scopeFilter}
            ORDER BY i.issue_date DESC,i.issue_id DESC");
        $this->db->bind("historyEmployee", $employeeId);
        return $this->db->resultSet();
    }

    /** Submit a return request without changing assignment or stock status. */
    public function createReturnRequest(
        int $issueId,
        int $employeeId,
        string $username,
        string $reason,
    ): string {
        $result = $this->createReturnRequests(
            [$issueId],
            $employeeId,
            $username,
            $reason,
        );
        return $result["requestNumbers"][0];
    }

    /**
     * Create one business return request for one or more assigned assets.
     * Each asset retains an internal line record so its condition and completion
     * can be tracked independently; the shared base number is the visible RRQ.
     */
    public function createReturnRequests(
        array $issueIds,
        int $employeeId,
        string $username,
        string $reason,
    ): array {
        $reason = trim($reason);
        $issueIds = array_values(
            array_unique(
                array_filter(
                    array_map("intval", $issueIds),
                    static fn(int $issueId): bool => $issueId > 0,
                ),
            ),
        );
        if (!$issueIds || $employeeId <= 0) {
            throw new InvalidArgumentException("Select at least one assigned asset.");
        }
        if (count($issueIds) > 8) {
            throw new InvalidArgumentException(
                "A return request can include a maximum of 8 assets.",
            );
        }
        if ($reason === "") {
            throw new InvalidArgumentException("Enter the reason for returning the selected assets.");
        }

        $this->db->beginTransaction();
        try {
            $custodyKey = null;
            $issueBatchKey = null;
            $baseRequestNo =
                "RRQ-" . date("Ymd-His") . "-" . random_int(100, 999);
            $requestNumbers = [];

            foreach ($issueIds as $index => $issueId) {
                $this->db->query("SELECT i.issue_id,i.issue_no,i.batch_no,i.status,
                    i.assignee_type,i.empId,i.deptId,
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
                    ) AS headEmpId
                    FROM asset_issue i
                    LEFT JOIN department d ON d.deptId=i.deptId
                    WHERE i.issue_id=:issueId FOR UPDATE");
                $this->db->bind("issueId", $issueId);
                $issue = $this->db->single();
                $allowed =
                    $issue &&
                    $issue["status"] === "ISSUED" &&
                    (($issue["assignee_type"] === "USER" &&
                        (int) $issue["empId"] === $employeeId) ||
                        ($issue["assignee_type"] === "DEPARTMENT" &&
                            (int) $issue["headEmpId"] === $employeeId));
                if (!$allowed) {
                    throw new RuntimeException(
                        "One selected asset is not currently assigned to you or your department.",
                    );
                }

                $currentCustodyKey = $issue["assignee_type"] === "DEPARTMENT"
                    ? "DEPARTMENT|" . (string) $issue["deptId"]
                    : "USER|" . (string) $issue["empId"];
                if ($custodyKey === null) {
                    $custodyKey = $currentCustodyKey;
                } elseif ($currentCustodyKey !== $custodyKey) {
                    throw new RuntimeException(
                        "Select assets assigned to one employee or department per return request.",
                    );
                }

                $currentBatchKey = trim((string) ($issue["batch_no"] ?? "")) ?:
                    trim((string) ($issue["issue_no"] ?? ""));
                if ($issueBatchKey === null) {
                    $issueBatchKey = $currentBatchKey;
                } elseif ($currentBatchKey !== $issueBatchKey) {
                    throw new RuntimeException(
                        "Select assets from one ISS batch per return request.",
                    );
                }

                $this->db->query("SELECT return_request_id
                    FROM asset_return_request
                    WHERE issue_id=:issueId AND status='PENDING' LIMIT 1");
                $this->db->bind("issueId", $issueId);
                if ($this->db->single()) {
                    throw new RuntimeException(
                        "A return request is already pending for one selected asset.",
                    );
                }

                $requestNo = count($issueIds) === 1
                    ? $baseRequestNo
                    : $baseRequestNo . "-" . str_pad((string) ($index + 1), 2, "0", STR_PAD_LEFT);
                $this->db->query("INSERT INTO asset_return_request
                    (return_request_no,issue_id,requested_by,reason)
                    VALUES (:requestNo,:issueId,:requestedBy,:reason)");
                $this->db->bind("requestNo", $requestNo);
                $this->db->bind("issueId", $issueId);
                $this->db->bind("requestedBy", $username);
                $this->db->bind("reason", substr($reason, 0, 500));
                $this->db->executeOrFail();
                $requestNumbers[] = $requestNo;
            }

            $this->db->commit();
            return [
                "batchReference" => $baseRequestNo,
                "requestNumbers" => $requestNumbers,
                "count" => count($requestNumbers),
            ];
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    /** Return requests waiting for the Issuer to process the physical return. */
    public function getReturnRequests(): array
    {
        $this->db->query("SELECT rr.*,i.issue_no,i.issue_date,i.assetNum,i.assignee_type,
            a.Description AS assetDescription,a.serial_number,k.Description AS categoryName,
            COALESCE(e.fullname,d.deptName) AS assignedTo,u.nama AS requestedByName,
            ar.return_no
            FROM asset_return_request rr
            LEFT JOIN asset_return ar ON ar.return_id=rr.return_id
            JOIN asset_issue i ON i.issue_id=rr.issue_id
            JOIN assetdata a ON a.assetNum=i.assetNum
            LEFT JOIN assetkat k ON k.assetKat=a.assetKat
            LEFT JOIN employee e ON e.empId=i.empId
            LEFT JOIN department d ON d.deptId=i.deptId
            JOIN users u ON u.username=rr.requested_by
            ORDER BY CASE WHEN rr.status='PENDING' THEN 0 ELSE 1 END,rr.created_at DESC");
        return $this->db->resultSet();
    }

    /** Cancel one complete pending RRQ batch without deleting its history. */
    public function cancelReturnRequestBatch(
        int $returnRequestId,
        string $actor,
        string $reason,
    ): array {
        $reason = trim($reason);
        if ($reason === "") {
            throw new InvalidArgumentException("Enter a cancellation reason.");
        }

        $this->db->beginTransaction();
        try {
            $this->db->query(
                "SELECT return_request_no
                 FROM asset_return_request
                 WHERE return_request_id=:returnRequestId
                 FOR UPDATE",
            );
            $this->db->bind("returnRequestId", $returnRequestId);
            $selected = $this->db->single();
            if (!$selected) {
                throw new RuntimeException("Return request not found.");
            }

            $selectedNumber = (string) $selected["return_request_no"];
            $batchReference = preg_match(
                '/^(RRQ-\d{8}-\d{6}-\d{3})-\d{2}$/',
                $selectedNumber,
                $match,
            )
                ? $match[1]
                : $selectedNumber;
            $this->db->query(
                "SELECT return_request_id,return_request_no,status,return_id
                 FROM asset_return_request
                 WHERE return_request_no=:exactReference
                    OR return_request_no LIKE :batchPattern
                 ORDER BY return_request_id
                 FOR UPDATE",
            );
            $this->db->bind("exactReference", $batchReference);
            $this->db->bind("batchPattern", $batchReference . "-%");
            $rows = array_values(
                array_filter(
                    $this->db->resultSet(),
                    static fn(array $row): bool =>
                        (string) $row["return_request_no"] === $batchReference ||
                        preg_match(
                            '/^' . preg_quote($batchReference, '/') . '-\d{2}$/',
                            (string) $row["return_request_no"],
                        ) === 1,
                ),
            );
            if (!$rows) {
                throw new RuntimeException("Return request batch not found.");
            }
            foreach ($rows as $row) {
                if (
                    (string) $row["status"] !== "PENDING" ||
                    !empty($row["return_id"])
                ) {
                    throw new RuntimeException(
                        "Only a fully pending return-request batch can be cancelled.",
                    );
                }
            }

            foreach ($rows as $row) {
                $this->db->query(
                    "UPDATE asset_return_request
                     SET status='CANCELLED',cancelled_by=:actor,
                         cancelled_at=NOW(),cancellation_reason=:reason
                     WHERE return_request_id=:returnRequestId AND status='PENDING'",
                );
                $this->db->bind("actor", $actor);
                $this->db->bind("reason", substr($reason, 0, 500));
                $this->db->bind("returnRequestId", (int) $row["return_request_id"]);
                $this->db->executeOrFail();
            }
            $this->db->commit();
            return [
                "reference" => $batchReference,
                "count" => count($rows),
            ];
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    /** Resolve all return-request rows represented by one batch PDF. */
    public function getReturnRequestDocumentIds(int $returnRequestId): array
    {
        $this->db->query("SELECT return_request_no
            FROM asset_return_request
            WHERE return_request_id=:returnRequestId");
        $this->db->bind("returnRequestId", $returnRequestId);
        $selected = $this->db->single();
        if (!$selected) {
            return [];
        }

        $requestNumber = (string) $selected["return_request_no"];
        if (!preg_match('/^(RRQ-\d{8}-\d{6}-\d{3})-\d{2}$/', $requestNumber, $match)) {
            return [$returnRequestId];
        }

        $batchReference = $match[1];
        $this->db->query("SELECT return_request_id,return_request_no
            FROM asset_return_request
            WHERE return_request_no LIKE :batchPattern
            ORDER BY return_request_id");
        $this->db->bind("batchPattern", $batchReference . "-%");
        $rows = array_filter(
            $this->db->resultSet(),
            static fn(array $row): bool =>
                preg_match(
                    '/^' . preg_quote($batchReference, '/') . '-\d{2}$/',
                    (string) $row["return_request_no"],
                ) === 1,
        );

        return array_map(
            "intval",
            array_column(array_values($rows), "return_request_id"),
        );
    }

    /** Assignment data shaped as an unprocessed IT Asset Return Form. */
    public function getReturnRequestForm(int $returnRequestId): ?array
    {
        $this->db->query("SELECT
            rr.return_request_id,rr.return_request_no,rr.status AS return_request_status,
            ar.return_id,ar.return_no AS actual_return_no,
            CASE WHEN rr.status='COMPLETED' AND ar.return_no IS NOT NULL
                 THEN ar.return_no ELSE rr.return_request_no END AS return_no,
            CASE WHEN rr.status='COMPLETED' AND ar.batch_no IS NOT NULL
                 THEN ar.batch_no ELSE rr.return_request_no END AS batch_no,
            CASE WHEN rr.status='COMPLETED' AND ar.return_date IS NOT NULL
                 THEN ar.return_date ELSE rr.created_at END AS return_date,
            CASE WHEN rr.status='COMPLETED' AND ar.return_type IS NOT NULL
                 THEN ar.return_type ELSE 'RETURN REQUEST' END AS return_type,
            CASE WHEN rr.status='COMPLETED' AND ar.condition_in IS NOT NULL
                 THEN ar.condition_in ELSE 'PENDING INSPECTION' END AS condition_in,
            COALESCE(ar.reason,rr.reason) AS reason,rr.requested_by,
            ar.scrap_reference,ar.received_by,
            i.issue_id,i.issue_no,i.issue_date,i.assignee_type,i.empId,i.deptId,
            i.acknowledged_by,i.assetNum,req.request_no,req.status AS request_status,
            a.Description AS assetDescription,
            a.serial_number,a.manufacturer,a.model,a.assetImage,
            k.Description AS categoryName,e.fullname AS employeeName,e.email AS employeeEmail,
            e.position AS employeePosition,e.area AS employeeArea,e.building AS employeeBuilding,
            e.extension_number AS employeeExtension,e.section AS employeeSection,
            d.deptName,d.deptPrefix AS departmentPrefix,
            dh.fullname AS departmentHeadName,dh.email AS departmentHeadEmail,
            dh.area AS departmentHeadArea,dh.building AS departmentHeadBuilding,
            dh.extension_number AS departmentHeadExtension
            FROM asset_return_request rr
            LEFT JOIN asset_return ar ON ar.return_id=rr.return_id
            JOIN asset_issue i ON i.issue_id=rr.issue_id
            JOIN assetdata a ON a.assetNum=i.assetNum
            LEFT JOIN assetkat k ON k.assetKat=a.assetKat
            LEFT JOIN employee e ON e.empId=i.empId
            LEFT JOIN department d ON d.deptId=i.deptId
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
            LEFT JOIN asset_request_allocation req_alloc ON req_alloc.issue_id=i.issue_id
            LEFT JOIN asset_request_item req_item ON req_item.request_item_id=req_alloc.request_item_id
            LEFT JOIN asset_request req ON req.request_id=req_item.request_id
            WHERE rr.return_request_id=:returnRequestId");
        $this->db->bind("returnRequestId", $returnRequestId);
        $record = $this->db->single();
        return $record ?: null;
    }

    public function getAssignmentForm($issueId)
    {
        $this
            ->db->query("SELECT i.issue_id,i.issue_no,i.batch_no,i.issue_date,i.expected_return_date,i.assignee_type,i.empId,i.deptId,
        i.condition_out,i.purpose,i.issued_by,i.acknowledged_by,i.status,i.assetNum,
        req.request_no,req.status request_status,
        a.Description assetDescription,a.serial_number,a.manufacturer,a.model,a.assetImage,
        k.Description categoryName,e.fullname employeeName,e.email employeeEmail,e.position employeePosition,
        e.area employeeArea,e.building employeeBuilding,e.extension_number employeeExtension,e.section employeeSection,d.deptName,d.deptPrefix departmentPrefix,
        d.area departmentArea,d.building departmentBuilding,
        immediate_head.fullname departmentHeadName,
        immediate_head.email departmentHeadEmail,
        immediate_head.area departmentHeadArea,
        immediate_head.building departmentHeadBuilding,
        immediate_head.extension_number departmentHeadExtension
        FROM asset_issue i JOIN assetdata a ON a.assetNum=i.assetNum
        LEFT JOIN assetkat k ON k.assetKat=a.assetKat LEFT JOIN employee e ON e.empId=i.empId
        LEFT JOIN department d ON d.deptId=i.deptId
        LEFT JOIN asset_request_allocation req_alloc ON req_alloc.issue_id=i.issue_id
        LEFT JOIN asset_request_item req_item ON req_item.request_item_id=req_alloc.request_item_id
        LEFT JOIN asset_request req ON req.request_id=req_item.request_id
        LEFT JOIN employee immediate_head ON immediate_head.empId=COALESCE(
            req.receiver_emp_id,
            d.headEmpId,
            (
                SELECT MIN(legacy_head.empId)
                FROM department_head legacy_head
                JOIN employee legacy_employee
                  ON legacy_employee.empId=legacy_head.empId
                 AND legacy_employee.aktif=1
                WHERE legacy_head.deptId=i.deptId
            )
        )
        WHERE i.issue_id=:issueId");
        $this->db->bind("issueId", (int) $issueId);
        return $this->db->single();
    }

    /**
     * Resolve every issue row represented by one assignment PDF. Requested
     * equipment uses the request as the document boundary, even when the
     * Issuer allocated its assets in more than one save operation. Manual
     * assignments fall back to their common batch/issue number.
     */
    public function getAssignmentDocumentIssueIds(int $issueId): array
    {
        $this->db->query("SELECT item.request_id
            FROM asset_request_allocation allocation
            JOIN asset_request_item item
              ON item.request_item_id=allocation.request_item_id
            WHERE allocation.issue_id=:issueId
            LIMIT 1");
        $this->db->bind("issueId", $issueId);
        $request = $this->db->single();

        if (!empty($request["request_id"])) {
            $this->db->query("SELECT DISTINCT allocation.issue_id
                FROM asset_request_allocation allocation
                JOIN asset_request_item item
                  ON item.request_item_id=allocation.request_item_id
                WHERE item.request_id=:requestId
                ORDER BY allocation.issue_id");
            $this->db->bind("requestId", (int) $request["request_id"]);

            return array_map(
                "intval",
                array_column($this->db->resultSet(), "issue_id"),
            );
        }

        $this->db->query("SELECT member.issue_id
            FROM asset_issue selected
            JOIN asset_issue member
              ON COALESCE(NULLIF(member.batch_no,''),member.issue_no) =
                 COALESCE(NULLIF(selected.batch_no,''),selected.issue_no)
            WHERE selected.issue_id=:issueId
            ORDER BY member.issue_id");
        $this->db->bind("issueId", $issueId);

        return array_map(
            "intval",
            array_column($this->db->resultSet(), "issue_id"),
        );
    }

    public function getReturnForm($returnId)
    {
        $this
            ->db->query("SELECT r.return_id,r.return_no,r.batch_no,r.return_date,r.return_type,r.condition_in,r.reason,
        r.scrap_reference,r.received_by,i.issue_id,i.issue_no,i.issue_date,i.assignee_type,
        i.acknowledged_by,i.assetNum,req.request_no,req.status request_status,
        a.Description assetDescription,a.serial_number,a.manufacturer,a.model,a.assetImage,
        k.Description categoryName,e.fullname employeeName,e.email employeeEmail,e.position employeePosition,
        e.area employeeArea,e.building employeeBuilding,e.extension_number employeeExtension,e.section employeeSection,
        d.deptName,d.deptPrefix departmentPrefix,d.area departmentArea,d.building departmentBuilding,
        immediate_head.fullname departmentHeadName,
        immediate_head.email departmentHeadEmail,
        immediate_head.area departmentHeadArea,
        immediate_head.building departmentHeadBuilding,
        immediate_head.extension_number departmentHeadExtension
        FROM asset_return r JOIN asset_issue i ON i.issue_id=r.issue_id
        JOIN assetdata a ON a.assetNum=i.assetNum LEFT JOIN assetkat k ON k.assetKat=a.assetKat
        LEFT JOIN employee e ON e.empId=i.empId
        LEFT JOIN department d ON d.deptId=i.deptId
        LEFT JOIN asset_request_allocation req_alloc ON req_alloc.issue_id=i.issue_id
        LEFT JOIN asset_request_item req_item ON req_item.request_item_id=req_alloc.request_item_id
        LEFT JOIN asset_request req ON req.request_id=req_item.request_id
        LEFT JOIN employee immediate_head ON immediate_head.empId=COALESCE(
            req.receiver_emp_id,
            d.headEmpId,
            (
                SELECT MIN(legacy_head.empId)
                FROM department_head legacy_head
                JOIN employee legacy_employee
                  ON legacy_employee.empId=legacy_head.empId
                 AND legacy_employee.aktif=1
                WHERE legacy_head.deptId=i.deptId
            )
        )
        WHERE r.return_id=:returnId");
        $this->db->bind("returnId", (int) $returnId);
        return $this->db->single();
    }

    private function nextAssetNumberForCategory($categoryId): string
    {
        $this->db->query(
            "SELECT asset_prefix, next_asset_sequence
             FROM assetkat
             WHERE assetKat=:category AND active=1
             FOR UPDATE",
        );
        $this->db->bind("category", trim((string) $categoryId));
        $category = $this->db->single();
        if (!$category) {
            throw new InvalidArgumentException("Select a valid active asset category.");
        }

        $prefix = strtoupper(trim((string) $category["asset_prefix"]));
        if (!preg_match('/^[A-Z][A-Z0-9]{1,5}$/', $prefix)) {
            throw new RuntimeException("The selected category has no valid asset prefix.");
        }
        $sequence = max(1, (int) ($category["next_asset_sequence"] ?? 1));
        $this->db->query(
            "UPDATE assetkat
             SET next_asset_sequence = :next_sequence
             WHERE assetKat = :category",
        );
        $this->db->bind("next_sequence", $sequence + 1);
        $this->db->bind("category", trim((string) $categoryId));
        $this->db->executeOrFail();

        return $prefix . str_pad((string) $sequence, 4, "0", STR_PAD_LEFT);
    }

    /**
     * Enforce the Asset Entry business fields on both create and edit. Warranty
     * expiry, image uploads and PDF uploads intentionally remain optional.
     */
    private function validateMandatoryAssetEntryData(array $data): array
    {
        $required = [
            "receiptNo",
            "receivedDate",
            "vendorId",
            "poNumber",
            "invoiceNumber",
            "notes",
            "assetKat",
            "desc",
            "manufacturer",
            "model",
            "location",
            "unit",
            "qty",
            "purchaseDate",
        ];
        foreach ($required as $field) {
            if (!isset($data[$field]) || trim((string) $data[$field]) === "") {
                throw new InvalidArgumentException(
                    "Please complete all required Asset Entry fields.",
                );
            }
        }

        $receivedDate = DateTimeImmutable::createFromFormat(
            "!Y-m-d",
            trim((string) $data["receivedDate"]),
        );
        $purchaseDate = DateTimeImmutable::createFromFormat(
            "!Y-m-d",
            trim((string) $data["purchaseDate"]),
        );
        $warrantyExpiryValue = trim((string) ($data["warrantyExpiry"] ?? ""));
        $warrantyExpiry =
            $warrantyExpiryValue === ""
                ? null
                : DateTimeImmutable::createFromFormat("!Y-m-d", $warrantyExpiryValue);
        if (
            !$receivedDate ||
            $receivedDate->format("Y-m-d") !== trim((string) $data["receivedDate"])
        ) {
            throw new InvalidArgumentException("Enter a valid received date.");
        }
        if (
            !$purchaseDate ||
            $purchaseDate->format("Y-m-d") !== trim((string) $data["purchaseDate"])
        ) {
            throw new InvalidArgumentException("Enter a valid purchase date.");
        }
        if (
            $warrantyExpiryValue !== "" &&
            (!$warrantyExpiry || $warrantyExpiry->format("Y-m-d") !== $warrantyExpiryValue)
        ) {
            throw new InvalidArgumentException("Enter a valid warranty expiry date.");
        }
        if ($warrantyExpiry && $warrantyExpiry < $purchaseDate) {
            throw new InvalidArgumentException(
                "Warranty expiry cannot be earlier than the purchase date.",
            );
        }

        return [
            "receivedDate" => $receivedDate,
            "purchaseDate" => $purchaseDate,
            "warrantyExpiry" => $warrantyExpiry,
        ];
    }

    public function receiveAsset($data, $file = null, $invoiceFile = null)
    {
        $data["receivedDate"] = trim((string) ($data["receivedDate"] ?? ""));
        $this->validateMandatoryAssetEntryData($data);

        if (!is_numeric($data["qty"]) || (float) $data["qty"] <= 0) {
            throw new InvalidArgumentException("Quantity must be greater than zero.");
        }
        $assetCount = filter_var($data["assetCount"] ?? 1, FILTER_VALIDATE_INT);
        if ($assetCount === false || $assetCount < 1 || $assetCount > 100) {
            throw new InvalidArgumentException(
                "Number of individual assets must be between 1 and 100.",
            );
        }
        $serialNumbers = $data["serialNumbers"] ?? [];
        if (!is_array($serialNumbers)) {
            $serialNumbers = [$serialNumbers];
        }
        if (!$serialNumbers && isset($data["serialNumber"])) {
            $serialNumbers = [(string) $data["serialNumber"]];
        }
        $serialNumbers = array_slice(array_pad($serialNumbers, $assetCount, ""), 0, $assetCount);
        $usedSerials = [];
        foreach ($serialNumbers as &$serialNumber) {
            $serialNumber = trim((string) $serialNumber);
            if ($serialNumber === "") {
                continue;
            }
            $serialKey = strtolower($serialNumber);
            if (isset($usedSerials[$serialKey])) {
                throw new InvalidArgumentException(
                    "Each asset must have a different serial number.",
                );
            }
            $usedSerials[$serialKey] = true;
        }
        unset($serialNumber);

        $uploadedImages = [];
        $allUploadedImages = [];
        $uploadedInvoice = null;
        $this->db->beginTransaction();

        try {
            $this->db->query(
                "SELECT vendor_id,vendor_name FROM vendor WHERE vendor_id=:vendorId AND active=1",
            );
            $this->db->bind("vendorId", (int) $data["vendorId"]);
            $vendor = $this->db->single();
            if (!$vendor) {
                throw new InvalidArgumentException("Select a valid active supplier.");
            }
            $receiptNo = trim($data["receiptNo"] ?? "");

            $uploadedImages = $this->storeUploadedImages($file);
            $allUploadedImages = $uploadedImages;
            $uploadedInvoice = $this->storeInvoicePdf($invoiceFile);

            $this->db->query("INSERT INTO asset_receipt
            (receipt_no, received_date, supplier_name, vendor_id, po_number, invoice_number, invoice_pdf, received_by, notes)
            VALUES (:receipt_no, :received_date, :supplier_name, :vendor_id, :po_number, :invoice_number, :invoice_pdf, :received_by, :notes)");
            $this->db->bind("receipt_no", $receiptNo);
            $this->db->bind("received_date", $data["receivedDate"]);
            $this->db->bind("supplier_name", $vendor["vendor_name"]);
            $this->db->bind("vendor_id", $vendor["vendor_id"]);
            $this->db->bind("po_number", trim($data["poNumber"] ?? "") ?: null);
            $this->db->bind("invoice_number", trim($data["invoiceNumber"] ?? "") ?: null);
            $this->db->bind("invoice_pdf", $uploadedInvoice["file_name"] ?? null);
            $this->db->bind("received_by", $_SESSION["usr"]["user"]);
            $this->db->bind("notes", trim($data["notes"] ?? "") ?: null);
            $this->db->executeOrFail();
            $receiptId = (int) $this->db->lastInsertId();

            $assetNumbers = [];
            for ($index = 0; $index < $assetCount; $index++) {
                $assetNum = $this->nextAssetNumberForCategory($data["assetKat"]);
                if (!preg_match('/^[A-Za-z0-9_-]{1,30}$/', $assetNum)) {
                    throw new InvalidArgumentException(
                        "Asset number may contain only letters, numbers, hyphen, and underscore.",
                    );
                }
                $assetImages =
                    $index === 0 ? $uploadedImages : $this->copyUploadedImages($uploadedImages);
                if ($index > 0) {
                    $allUploadedImages = array_merge($allUploadedImages, $assetImages);
                }
                $imageName = $assetImages ? $assetImages[0]["file_name"] : null;

                $this->db->query("INSERT INTO assetdata
                (assetNum, assetKat, Description, serial_number, manufacturer, model,
                 purchase_date, warranty_expiry, receipt_id, createdBy,
                 createdDate, quantity, unit, assetStat, asset_condition, location, assetImage, notes)
                VALUES
                (:assetNum, :assetKat, :description, :serial_number, :manufacturer, :model,
                 :purchase_date, :warranty_expiry, :receipt_id, :createdBy,
                 :createdDate, :quantity, :unit, 'IN_STOCK', 'NEW', :location, :assetImage, :notes)");
                $values = [
                    "assetNum" => $assetNum,
                    "assetKat" => $data["assetKat"],
                    "description" => trim($data["desc"]),
                    "serial_number" => $serialNumbers[$index] ?: null,
                    "manufacturer" => trim($data["manufacturer"] ?? "") ?: null,
                    "model" => trim($data["model"] ?? "") ?: null,
                    "purchase_date" => trim($data["purchaseDate"] ?? "") ?: null,
                    "warranty_expiry" => trim($data["warrantyExpiry"] ?? "") ?: null,
                    "receipt_id" => $receiptId,
                    "createdBy" => $_SESSION["usr"]["user"],
                    "createdDate" => $data["receivedDate"],
                    "quantity" => $data["qty"],
                    "unit" => $data["unit"],
                    "location" => $data["location"],
                    "assetImage" => $imageName,
                    "notes" => trim($data["assetNotes"] ?? "") ?: null,
                ];
                foreach ($values as $key => $value) {
                    $this->db->bind($key, $value);
                }
                $this->db->executeOrFail();

                foreach ($assetImages as $image) {
                    $this->insertAssetMedia($assetNum, "ENTRY", $image, null, null);
                }

                $this->db->query("INSERT INTO asset_receipt_item
                (receipt_id, assetNum, line_no, condition_on_receipt, remarks)
                VALUES (:receipt_id, :assetNum, :line_no, :condition_received, :remarks)");
                $this->db->bind("receipt_id", $receiptId);
                $this->db->bind("assetNum", $assetNum);
                $this->db->bind("line_no", $index + 1);
                $this->db->bind("condition_received", $data["conditionReceived"] ?? "NEW");
                $this->db->bind("remarks", trim($data["assetNotes"] ?? "") ?: null);
                $this->db->executeOrFail();
                $assetNumbers[] = $assetNum;
            }

            $this->db->commit();
            return [
                "assetNum" => $assetNumbers[0],
                "assetNums" => $assetNumbers,
                "assetCount" => count($assetNumbers),
                "receiptNo" => $receiptNo,
            ];
        } catch (Throwable $error) {
            $this->db->rollBack();
            $this->removeUploadedImages($allUploadedImages);
            if ($uploadedInvoice && is_file($uploadedInvoice["path"])) {
                unlink($uploadedInvoice["path"]);
            }
            throw $error;
        }
    }

    public function getAvailableAssets()
    {
        $this->db->query("SELECT a.assetNum, a.Description, a.assetKat, k.Description AS category,
        a.serial_number, a.manufacturer, a.model, a.assetImage, a.assetStat,
        a.asset_condition, s.Description AS status_name
        FROM assetdata a
        JOIN assetkat k ON k.assetKat=a.assetKat
        JOIN assetstat s ON s.assetStatus=a.assetStat
        WHERE a.assetStat='IN_STOCK'
          AND NOT EXISTS (SELECT 1 FROM asset_issue i WHERE i.assetNum=a.assetNum AND i.status='ISSUED')
        ORDER BY a.assetNum");
        return $this->db->resultSet();
    }

    /** Search a small number of in-stock assets for the remote assignment picker. */
    public function searchAvailableAssets(string $term, int $limit = 30): array
    {
        $term = mb_substr(trim($term), 0, 100);
        if (mb_strlen($term) < 2) {
            return [];
        }
        $limit = max(1, min($limit, 50));

        $this->db->query("
            SELECT
                a.assetNum,
                a.Description,
                a.assetKat,
                k.Description AS category,
                a.serial_number,
                a.manufacturer,
                a.model,
                a.assetImage,
                a.assetStat,
                a.asset_condition,
                s.Description AS status_name
            FROM assetdata a
            JOIN assetkat k ON k.assetKat = a.assetKat
            JOIN assetstat s ON s.assetStatus = a.assetStat
            WHERE a.assetStat = 'IN_STOCK'
              AND NOT EXISTS (
                  SELECT 1
                  FROM asset_issue i
                  WHERE i.assetNum = a.assetNum
                    AND i.status = 'ISSUED'
              )
              AND (
                  a.assetNum LIKE :asset_number_term
                  OR a.serial_number LIKE :asset_serial_term
                  OR a.Description LIKE :asset_description_term
              )
            ORDER BY
                CASE WHEN a.assetNum = :exact_term THEN 0 ELSE 1 END,
                a.assetNum
            LIMIT :result_limit
        ");
        $this->db->bind("asset_number_term", $term . "%");
        $this->db->bind("asset_serial_term", $term . "%");
        $this->db->bind("asset_description_term", $term . "%");
        $this->db->bind("exact_term", $term);
        $this->db->bind("result_limit", $limit);

        return $this->db->resultSet();
    }

    public function getAssetEntryById($assetNum)
    {
        $this
            ->db->query("SELECT a.*,r.receipt_no,r.received_date,r.supplier_name,r.vendor_id,r.po_number,r.invoice_number,r.invoice_pdf,
        r.notes AS receipt_notes,ri.condition_on_receipt,l.description AS locationName,k.Description AS categoryName,
        e.fullname AS empName
        FROM assetdata a LEFT JOIN asset_receipt r ON r.receipt_id=a.receipt_id
        LEFT JOIN asset_receipt_item ri ON ri.assetNum=a.assetNum
        LEFT JOIN location l ON l.location=a.location LEFT JOIN assetkat k ON k.assetKat=a.assetKat
        LEFT JOIN employee e ON e.empId=a.empId WHERE a.assetNum=:assetNum");
        $this->db->bind("assetNum", $assetNum);
        return $this->db->single();
    }

    /** Load a bounded label batch in one query and reject non-stock assets. */
    public function getInStockAssetsByNumbers(array $assetNumbers): array
    {
        $assetNumbers = array_slice(
            array_values(array_unique(array_filter(array_map("trim", $assetNumbers)))),
            0,
            200,
        );
        if (!$assetNumbers) {
            return [];
        }

        $placeholders = [];
        foreach ($assetNumbers as $index => $assetNumber) {
            $placeholders[] = ":bulk_label_asset_{$index}";
        }
        $this->db->query(
            "SELECT a.assetNum,a.Description,a.assetKat,a.serial_number,
                    a.manufacturer,a.model,a.assetStat,a.asset_condition,a.location,
                    k.Description AS categoryName,
                    l.description AS locationName
             FROM assetdata a
             LEFT JOIN assetkat k ON k.assetKat=a.assetKat
             LEFT JOIN location l ON l.location=a.location
             WHERE a.assetStat='IN_STOCK'
               AND a.assetNum IN (" . implode(",", $placeholders) . ")
               AND NOT EXISTS (
                   SELECT 1 FROM asset_issue i
                   WHERE i.assetNum=a.assetNum AND i.status='ISSUED'
               )",
        );
        foreach ($assetNumbers as $index => $assetNumber) {
            $this->db->bind("bulk_label_asset_{$index}", $assetNumber);
        }
        return $this->db->resultSet();
    }

    /** Load a bounded label batch and confirm every asset is currently assigned. */
    public function getActiveAssetsByNumbers(array $assetNumbers): array
    {
        $assetNumbers = array_slice(
            array_values(array_unique(array_filter(array_map("trim", $assetNumbers)))),
            0,
            200,
        );
        if (!$assetNumbers) {
            return [];
        }

        $placeholders = [];
        foreach ($assetNumbers as $index => $assetNumber) {
            $placeholders[] = ":bulk_active_label_asset_{$index}";
        }
        $this->db->query(
            "SELECT a.assetNum,a.Description,a.assetKat,a.serial_number,
                    a.manufacturer,a.model,a.assetStat,a.asset_condition,a.location,
                    a.empId,e.fullname AS empName,
                    k.Description AS categoryName,
                    l.description AS locationName
             FROM assetdata a
             LEFT JOIN assetkat k ON k.assetKat=a.assetKat
             LEFT JOIN location l ON l.location=a.location
             LEFT JOIN employee e ON e.empId=a.empId
             WHERE a.assetNum IN (" . implode(",", $placeholders) . ")
               AND EXISTS (
                   SELECT 1 FROM asset_issue i
                   WHERE i.assetNum=a.assetNum AND i.status='ISSUED'
               )",
        );
        foreach ($assetNumbers as $index => $assetNumber) {
            $this->db->bind("bulk_active_label_asset_{$index}", $assetNumber);
        }
        return $this->db->resultSet();
    }

    public function updateReceivedAsset($data, $files = null, $invoiceFile = null)
    {
        $data["receivedDate"] = trim((string) ($data["receivedDate"] ?? ""));
        $this->validateMandatoryAssetEntryData($data);
        if (empty($data["assetNum"])) {
            throw new InvalidArgumentException("Asset number is required.");
        }
        if (trim((string) ($data["serialNumber"] ?? "")) === "") {
            throw new InvalidArgumentException("Serial number is required.");
        }
        if (!is_numeric($data["qty"]) || (float) $data["qty"] <= 0) {
            throw new InvalidArgumentException("Quantity must be greater than zero.");
        }
        $images = [];
        $invoice = null;
        $this->db->beginTransaction();
        try {
            $this->db->query(
                "SELECT receipt_id,assetImage,assetStat,asset_condition
                 FROM assetdata WHERE assetNum=:assetNum FOR UPDATE",
            );
            $this->db->bind("assetNum", $data["assetNum"]);
            $current = $this->db->single();
            if (!$current) {
                throw new RuntimeException("Asset not found.");
            }
            if (($current["assetStat"] ?? "") !== "IN_STOCK") {
                throw new RuntimeException(
                    "Only in-stock assets can be edited. Assigned asset details are locked to preserve the handover record.",
                );
            }

            $updateReason = trim((string) ($data["updateReason"] ?? ""));
            if ($updateReason === "") {
                throw new InvalidArgumentException("Enter a reason for updating this asset.");
            }
            if (mb_strlen($updateReason) > 500) {
                throw new InvalidArgumentException(
                    "Asset update reason cannot exceed 500 characters.",
                );
            }
            $beforeSnapshot = $this->getAssetEntryById($data["assetNum"]);
            if (!$beforeSnapshot) {
                throw new RuntimeException("Unable to load the asset audit snapshot.");
            }

            $allowedConditions = ["NEW", "GOOD", "DAMAGED"];
            $previousCondition = strtoupper(
                trim((string) ($current["asset_condition"] ?? "NEW")),
            );
            $newCondition = strtoupper(
                trim((string) ($data["currentCondition"] ?? $previousCondition)),
            );
            if (!in_array($newCondition, $allowedConditions, true)) {
                throw new InvalidArgumentException("Select a valid current asset condition.");
            }
            $this->db->query("SELECT vendor_id,vendor_name FROM vendor WHERE vendor_id=:vendorId");
            $this->db->bind("vendorId", (int) ($data["vendorId"] ?? 0));
            $vendor = $this->db->single();
            if (!$vendor) {
                throw new InvalidArgumentException("Select a valid supplier.");
            }
            $images = $this->storeUploadedImages($files);
            $primary = $images ? $images[0]["file_name"] : $current["assetImage"];
            $this->db->query(
                "UPDATE assetdata SET assetKat=:assetKat,Description=:description,
                    serial_number=:serial,manufacturer=:manufacturer,model=:model,
                    purchase_date=:purchaseDate,warranty_expiry=:warranty,
                    quantity=:qty,unit=:unit,location=:location,assetImage=:image,
                    notes=:notes,asset_condition=:assetCondition
                 WHERE assetNum=:assetNum",
            );
            $params = [
                "assetKat" => $data["assetKat"],
                "description" => trim($data["desc"]),
                "serial" => trim($data["serialNumber"] ?? "") ?: null,
                "manufacturer" => trim($data["manufacturer"] ?? "") ?: null,
                "model" => trim($data["model"] ?? "") ?: null,
                "purchaseDate" => trim($data["purchaseDate"] ?? "") ?: null,
                "warranty" => trim($data["warrantyExpiry"] ?? "") ?: null,
                "qty" => $data["qty"],
                "unit" => $data["unit"],
                "location" => $data["location"],
                "image" => $primary,
                "notes" => trim($data["assetNotes"] ?? "") ?: null,
                "assetCondition" => $newCondition,
                "assetNum" => $data["assetNum"],
            ];
            foreach ($params as $key => $value) {
                $this->db->bind($key, $value);
            }
            $this->db->executeOrFail();
            if ($current["receipt_id"]) {
                $invoice = $this->storeInvoicePdf($invoiceFile);
                $this->db->query(
                    "UPDATE asset_receipt SET receipt_no=:receiptNo,received_date=:receivedDate,supplier_name=:supplier,vendor_id=:vendorId,po_number=:po,invoice_number=:invoice,invoice_pdf=COALESCE(:invoicePdf,invoice_pdf),notes=:notes WHERE receipt_id=:receiptId",
                );
                $receipt = [
                    "receiptNo" => trim($data["receiptNo"]),
                    "receivedDate" => $data["receivedDate"],
                    "supplier" => $vendor["vendor_name"],
                    "vendorId" => $vendor["vendor_id"],
                    "po" => trim($data["poNumber"] ?? "") ?: null,
                    "invoice" => trim($data["invoiceNumber"] ?? "") ?: null,
                    "invoicePdf" => $invoice["file_name"] ?? null,
                    "notes" => trim($data["notes"] ?? "") ?: null,
                    "receiptId" => $current["receipt_id"],
                ];
                foreach ($receipt as $key => $value) {
                    $this->db->bind($key, $value);
                }
                $this->db->executeOrFail();
            }
            // Receipt condition is an immutable historical snapshot. Editing
            // an in-stock asset may update only its current global condition.
            $this->db->query(
                "UPDATE asset_receipt_item SET remarks=:remarks WHERE assetNum=:assetNum",
            );
            $this->db->bind("remarks", trim($data["assetNotes"] ?? "") ?: null);
            $this->db->bind("assetNum", $data["assetNum"]);
            $this->db->executeOrFail();

            $afterSnapshot = $this->getAssetEntryById($data["assetNum"]);
            if (!$afterSnapshot) {
                throw new RuntimeException("Unable to verify the updated asset record.");
            }

            $auditFieldLabels = [
                "assetKat" => "Category",
                "Description" => "Description",
                "serial_number" => "Serial Number",
                "manufacturer" => "Manufacturer",
                "model" => "Model",
                "purchase_date" => "Purchase Date",
                "warranty_expiry" => "Warranty Expiry",
                "quantity" => "Quantity",
                "unit" => "Unit",
                "location" => "Location",
                "assetImage" => "Primary Image",
                "notes" => "Asset Notes",
                "asset_condition" => "Current Condition",
                "receipt_no" => "Receipt Number",
                "received_date" => "Received Date",
                "vendor_id" => "Vendor",
                "supplier_name" => "Supplier",
                "po_number" => "PO Number",
                "invoice_number" => "Invoice Number",
                "invoice_pdf" => "Invoice PDF",
                "receipt_notes" => "Receipt Notes",
            ];
            $previousValues = [];
            $newValues = [];
            $changedFields = [];
            $changeSummary = [];
            foreach ($auditFieldLabels as $field => $label) {
                $previousValue = trim((string) ($beforeSnapshot[$field] ?? ""));
                $newValue = trim((string) ($afterSnapshot[$field] ?? ""));
                if ($field === "quantity") {
                    $isChanged = (float) $previousValue !== (float) $newValue;
                } else {
                    $isChanged = $previousValue !== $newValue;
                }
                $previousValues[$field] = $previousValue;
                $newValues[$field] = $newValue;
                if (!$isChanged) {
                    continue;
                }
                $changedFields[$field] = [
                    "label" => $label,
                    "previous" => $previousValue,
                    "new" => $newValue,
                ];
                $changeSummary[] = sprintf(
                    '%s: "%s" to "%s"',
                    $label,
                    mb_substr($previousValue !== "" ? $previousValue : "-", 0, 120),
                    mb_substr($newValue !== "" ? $newValue : "-", 0, 120),
                );
            }

            if ($changedFields) {
                $changedBy = mb_substr(
                    trim((string) ($_SESSION["usr"]["user"] ?? "SYSTEM")),
                    0,
                    50,
                );
                $this->db->query(
                    "INSERT INTO asset_update_audit
                        (assetNum,changed_by,change_reason,changed_fields,
                         previous_snapshot,new_snapshot,change_summary)
                     VALUES
                        (:assetNum,:changedBy,:changeReason,:changedFields,
                         :previousSnapshot,:newSnapshot,:changeSummary)",
                );
                $this->db->bind("assetNum", $data["assetNum"]);
                $this->db->bind("changedBy", $changedBy ?: "SYSTEM");
                $this->db->bind("changeReason", $updateReason);
                $this->db->bind(
                    "changedFields",
                    json_encode(
                        $changedFields,
                        JSON_UNESCAPED_UNICODE |
                            JSON_UNESCAPED_SLASHES |
                            JSON_INVALID_UTF8_SUBSTITUTE,
                    ),
                );
                $this->db->bind(
                    "previousSnapshot",
                    json_encode(
                        $previousValues,
                        JSON_UNESCAPED_UNICODE |
                            JSON_UNESCAPED_SLASHES |
                            JSON_INVALID_UTF8_SUBSTITUTE,
                    ),
                );
                $this->db->bind(
                    "newSnapshot",
                    json_encode(
                        $newValues,
                        JSON_UNESCAPED_UNICODE |
                            JSON_UNESCAPED_SLASHES |
                            JSON_INVALID_UTF8_SUBSTITUTE,
                    ),
                );
                $this->db->bind("changeSummary", implode("; ", $changeSummary));
                $this->db->executeOrFail();
            }
            foreach ($images as $image) {
                $this->insertAssetMedia($data["assetNum"], "ENTRY", $image, null, null);
            }
            $this->db->commit();
            return ["assetNum" => $data["assetNum"]];
        } catch (Throwable $e) {
            $this->db->rollBack();
            $this->removeUploadedImages($images);
            if ($invoice && is_file($invoice["path"])) {
                unlink($invoice["path"]);
            }
            throw $e;
        }
    }

    public function getEmployeesByDepartment($deptId)
    {
        $this->db->query("SELECT e.empId,e.fullname,e.email
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
        return $this->db->resultSet();
    }

    public function getActiveEmployeeAssignmentData($empId)
    {
        $this->db->query("SELECT e.empId,e.fullname,e.email,
        e.deptId,employee_dept.deptName,
        dept_head.empId AS departmentHeadId,dept_head.fullname AS departmentHeadName,
        dept_head.email AS departmentHeadEmail
        FROM employee e
        JOIN department employee_dept
          ON employee_dept.deptId=e.deptId AND employee_dept.active=1
        LEFT JOIN employee dept_head
          ON dept_head.empId=COALESCE(
              employee_dept.headEmpId,
              (
                  SELECT MIN(legacy_head.empId)
                  FROM department_head legacy_head
                  JOIN employee legacy_employee
                    ON legacy_employee.empId=legacy_head.empId
                   AND legacy_employee.aktif=1
                  WHERE legacy_head.deptId=employee_dept.deptId
              )
          ) AND dept_head.aktif=1
        WHERE e.empId=:empId AND e.aktif=1
        LIMIT 1");
        $this->db->bind("empId", (int) $empId);
        return $this->db->single();
    }

    public function assignAsset($data, $files = null)
    {
        $assetNums = $data["assetNums"] ?? ($data["assetNum"] ?? []);
        if (!is_array($assetNums)) {
            $assetNums = [$assetNums];
        }
        $assetNums = array_values(
            array_unique(
                array_filter(array_map(static fn($number) => trim((string) $number), $assetNums)),
            ),
        );
        if (!$assetNums) {
            throw new InvalidArgumentException("Select at least one asset.");
        }
        if (count($assetNums) > 8) {
            throw new InvalidArgumentException("You can assign a maximum of 8 assets at one time.");
        }
        foreach (["assigneeType", "issueDate"] as $field) {
            if (empty($data[$field])) {
                throw new InvalidArgumentException(
                    "Please complete all required assignment fields.",
                );
            }
        }
        $issueDate = null;
        foreach (["!Y-m-d\TH:i", "!Y-m-d H:i", "!Y-m-d H:i:s"] as $format) {
            $candidate = DateTimeImmutable::createFromFormat(
                $format,
                trim((string) $data["issueDate"]),
            );
            $dateErrors = DateTimeImmutable::getLastErrors();
            if (
                $candidate instanceof DateTimeImmutable &&
                ($dateErrors === false ||
                    ((int) ($dateErrors["warning_count"] ?? 0) === 0 &&
                        (int) ($dateErrors["error_count"] ?? 0) === 0))
            ) {
                $issueDate = $candidate;
                break;
            }
        }
        if (!$issueDate) {
            throw new InvalidArgumentException("Enter a valid assignment date and time.");
        }
        // Assignment date range validation is intentionally disabled.
        $data["issueDate"] = $issueDate->format("Y-m-d H:i:s");

        $expectedReturnValue = trim((string) ($data["expectedReturnDate"] ?? ""));
        if ($expectedReturnValue !== "") {
            $expectedReturnDate = DateTimeImmutable::createFromFormat(
                "!Y-m-d",
                $expectedReturnValue,
            );
            $expectedErrors = DateTimeImmutable::getLastErrors();
            if (
                !$expectedReturnDate ||
                ($expectedErrors !== false &&
                    ((int) ($expectedErrors["warning_count"] ?? 0) > 0 ||
                        (int) ($expectedErrors["error_count"] ?? 0) > 0))
            ) {
                throw new InvalidArgumentException("Enter a valid expected return date.");
            }
            // Expected-return ordering validation is intentionally disabled.
            $data["expectedReturnDate"] = $expectedReturnDate->format("Y-m-d");
        }
        if (!in_array($data["assigneeType"], ["DEPARTMENT", "USER"], true)) {
            throw new InvalidArgumentException("Invalid assignment type.");
        }
        $conditionValues = ["NEW", "GOOD", "DAMAGED"];

        $uploadedImages = [];
        $allUploadedImages = [];
        $this->db->beginTransaction();
        try {
            // Request-based issuance must share this transaction with the
            // physical issue records. Manual assignments do not provide this
            // context and continue through the same assignment logic below.
            $requestAllocation = $this->lockRequestAllocation(
                $data["requestAllocation"] ?? null,
                $assetNums,
            );
            $deptId = null;
            $empId = null;
            $acknowledgedBy = null;
            $employeeEmail = null;
            $departmentName = null;
            $departmentHeadEmail = null;
            $departmentHeadName = null;
            if ($data["assigneeType"] === "USER") {
                $employee = $this->getActiveEmployeeAssignmentData($data["empId"] ?? "");
                if (!$employee || !$employee["deptId"]) {
                    throw new InvalidArgumentException(
                        "Enter a valid active employee ID with a department.",
                    );
                }
                $empId = (int) $employee["empId"];
                $deptId = $employee["deptId"];
                $acknowledgedBy = $employee["fullname"];
                $employeeEmail = $employee["email"] ?? null;
                $departmentName = $employee["deptName"] ?? null;
                $departmentHeadEmail = $employee["departmentHeadEmail"] ?? null;
                $departmentHeadName = $employee["departmentHeadName"] ?? null;
            } else {
                $deptId = trim($data["deptId"] ?? "");
                $headId = (int) ($data["departmentHeadId"] ?? 0);
                $heads = $this->getEmployeesByDepartment($deptId);
                foreach ($heads as $head) {
                    if ((int) $head["empId"] === $headId) {
                        $acknowledgedBy = $head["fullname"];
                        $employeeEmail = $head["email"] ?? null;
                    }
                }
                if ($deptId === "" || !$acknowledgedBy) {
                    throw new InvalidArgumentException(
                        "Select a valid department and section head.",
                    );
                }
                $this->db->query("SELECT deptName FROM department WHERE deptId=:deptId LIMIT 1");
                $this->db->bind("deptId", $deptId);
                $department = $this->db->single();
                $departmentName = $department["deptName"] ?? null;
                $departmentHeadEmail = $employeeEmail;
                $departmentHeadName = $acknowledgedBy;
            }

            $assets = [];
            foreach ($assetNums as $assetNum) {
                $this->db
                    ->query("SELECT a.assetStat,a.asset_condition,a.assetKat,a.Description,a.serial_number,a.manufacturer,a.model,a.assetImage,
                k.Description AS categoryName FROM assetdata a LEFT JOIN assetkat k ON k.assetKat=a.assetKat
                WHERE a.assetNum=:assetNum FOR UPDATE");
                $this->db->bind("assetNum", $assetNum);
                $asset = $this->db->single();
                if (!$asset || $asset["assetStat"] !== "IN_STOCK") {
                    throw new RuntimeException(
                        "Asset " . $assetNum . " is not available for assignment.",
                    );
                }
                // Receipt-date comparison is intentionally disabled. Some legacy
                // asset records do not expose a receipt date through assetdata.
                $assetCondition = strtoupper(
                    trim((string) ($asset["asset_condition"] ?? "")),
                );
                if (!in_array($assetCondition, $conditionValues, true)) {
                    throw new RuntimeException(
                        "Asset " .
                            $assetNum .
                            " cannot be assigned with its current condition.",
                    );
                }
                if (
                    $requestAllocation &&
                    ($requestAllocation["assetCategories"][$assetNum] ?? null) !==
                        (string) $asset["assetKat"]
                ) {
                    throw new RuntimeException(
                        "Asset " . $assetNum . " does not match its approved request category.",
                    );
                }
                $asset["assetNum"] = $assetNum;
                $asset["assignmentCondition"] = $assetCondition;
                $assets[] = $asset;
            }
            $baseIssueNo = substr(
                trim($data["issueNo"] ?? "") ?:
                "ISS-" . date("Ymd-His") . "-" . random_int(100, 999),
                0,
                count($assets) > 1 ? 27 : 30,
            );
            $uploadedImages = $this->storeUploadedImages($files);
            $allUploadedImages = $uploadedImages;
            $issueIds = [];
            $issueNos = [];
            foreach ($assets as $index => &$asset) {
                // Condition Out is a snapshot of the asset's stored current
                // condition. The assignment form cannot override it.
                $assetCondition = $asset["assignmentCondition"];
                $issueNo =
                    count($assets) > 1
                        ? $baseIssueNo . "-" . str_pad((string) ($index + 1), 2, "0", STR_PAD_LEFT)
                        : $baseIssueNo;
                $this->db->query("INSERT INTO asset_issue
                (issue_no,batch_no,assetNum,assignee_type,deptId,empId,issue_date,expected_return_date,condition_out,purpose,issued_by,acknowledged_by)
                VALUES (:issueNo,:batchNo,:assetNum,:type,:deptId,:empId,:issueDate,:expectedReturn,:conditionOut,:purpose,:issuedBy,:acknowledgedBy)");
                $params = [
                    "issueNo" => $issueNo,
                    "batchNo" => $baseIssueNo,
                    "assetNum" => $asset["assetNum"],
                    "type" => $data["assigneeType"],
                    "deptId" => $deptId,
                    "empId" => $empId,
                    "issueDate" => $data["issueDate"],
                    "expectedReturn" => trim($data["expectedReturnDate"] ?? "") ?: null,
                    "conditionOut" => $assetCondition,
                    // The assignment form names this field "remarks" while the
                    // database keeps it in the historical "purpose" column.
                    "purpose" => trim($data["remarks"] ?? ($data["purpose"] ?? "")) ?: null,
                    "issuedBy" => $_SESSION["usr"]["user"],
                    "acknowledgedBy" => $acknowledgedBy,
                ];
                foreach ($params as $key => $value) {
                    $this->db->bind($key, $value);
                }
                $this->db->executeOrFail();
                $issueId = (int) $this->db->lastInsertId();
                $issueIds[] = $issueId;
                $issueNos[] = $issueNo;
                $this->db->query(
                    "UPDATE assetdata
                     SET empId=:empId,deptId=:deptId,assetStat='ASSIGNED'
                     WHERE assetNum=:assetNum",
                );
                $this->db->bind("empId", $empId);
                $this->db->bind("deptId", $deptId);
                $this->db->bind("assetNum", $asset["assetNum"]);
                $this->db->executeOrFail();
                $media =
                    $index === 0 ? $uploadedImages : $this->copyUploadedImages($uploadedImages);
                if ($index > 0) {
                    $allUploadedImages = array_merge($allUploadedImages, $media);
                }
                foreach ($media as $image) {
                    $this->insertAssetMedia($asset["assetNum"], "ASSIGN", $image, $issueId, null);
                }
                $asset["issueId"] = $issueId;
                $asset["issueNo"] = $issueNo;
                $asset["conditionOut"] = $assetCondition;
                $asset["condition_out"] = $assetCondition;
            }
            unset($asset);
            $allocationProgress = null;
            if ($requestAllocation) {
                $allocationProgress = $this->completeRequestAllocation(
                    $requestAllocation,
                    $assets,
                    $issueNos[0] ?? null,
                );
            }
            $this->db->commit();
            $photoFiles = [];
            foreach ($assets as $asset) {
                if (!empty($asset["assetImage"])) {
                    $photoFiles[] =
                        __DIR__ . "/../../public/uploads/asset/" . basename($asset["assetImage"]);
                }
            }
            foreach ($allUploadedImages as $image) {
                $photoFiles[] = $image["path"];
            }
            $first = $assets[0];
            return [
                "issueId" => $issueIds[0],
                "issueIds" => $issueIds,
                "issueNo" => $issueNos[0],
                "issueNos" => $issueNos,
                "assetNum" => $first["assetNum"],
                "assets" => $assets,
                "issueDate" => $data["issueDate"],
                "expectedReturnDate" => trim($data["expectedReturnDate"] ?? "") ?: null,
                "conditionOut" => $first["conditionOut"],
                "purpose" => trim($data["remarks"] ?? "") ?: null,
                "employeeName" => $acknowledgedBy,
                "employeeEmail" => $employeeEmail,
                "departmentName" => $departmentName,
                "departmentHeadName" => $departmentHeadName,
                "departmentHeadEmail" => $departmentHeadEmail,
                "requestRemaining" => (int) ($allocationProgress["remaining"] ?? 0),
                "requestComplete" => (bool) ($allocationProgress["complete"] ?? false),
                "assetDescription" => $first["Description"],
                "assetCategory" => $first["categoryName"],
                "serialNumber" => $first["serial_number"],
                "manufacturer" => $first["manufacturer"],
                "model" => $first["model"],
                "photoFiles" => $photoFiles,
                "assigneeType" => $data["assigneeType"],
            ];
        } catch (Throwable $error) {
            $this->db->rollBack();
            $this->removeUploadedImages($allUploadedImages ?: $uploadedImages);
            throw $error;
        }
    }

    /**
     * Lock and validate an approved equipment request before issuing assets.
     * The returned plan is consumed before the surrounding transaction commits.
     */
    private function lockRequestAllocation($allocation, array $assetNums): ?array
    {
        if ($allocation === null) {
            return null;
        }
        if (!is_array($allocation)) {
            throw new InvalidArgumentException("Invalid request allocation data.");
        }

        $requestId = (int) ($allocation["requestId"] ?? 0);
        $actor = trim((string) ($allocation["actor"] ?? ""));
        $rawItemAssets = $allocation["itemAssets"] ?? [];
        if ($requestId < 1 || $actor === "" || !is_array($rawItemAssets)) {
            throw new InvalidArgumentException("Invalid request allocation data.");
        }
        if ($actor !== (string) ($_SESSION["usr"]["user"] ?? "")) {
            throw new RuntimeException("The issuing user does not match the active session.");
        }

        $this->db->query("SELECT r.request_id,r.status,r.requested_by,
            r.requested_for,r.receiver_emp_id,r.request_target_type,d.headEmpId
            FROM asset_request r
            LEFT JOIN department d ON d.deptId=r.deptId
            WHERE r.request_id=:requestId FOR UPDATE");
        $this->db->bind("requestId", $requestId);
        $request = $this->db->single();
        if (!$request || (string) $request["status"] !== "APPROVED") {
            throw new RuntimeException(
                "This request is no longer ready for issuance. Refresh and try again.",
            );
        }

        $receiverEmpId = (int) (
            $request["receiver_emp_id"] ??
            ($request["request_target_type"] === "DEPARTMENT"
                ? ($request["headEmpId"] ?? 0)
                : ($request["requested_for"] ?? 0))
        );
        $sessionEmpId = (int) ($_SESSION["usr"]["empId"] ?? 0);
        if (
            (string) $request["requested_by"] === $actor ||
            ($receiverEmpId > 0 && $receiverEmpId === $sessionEmpId)
        ) {
            throw new RuntimeException(
                "You cannot assign assets for a request created for yourself.",
            );
        }

        $this->db->query("SELECT item.request_item_id,item.assetKat,item.approved_quantity,
            (SELECT COUNT(*) FROM asset_request_allocation existing
             WHERE existing.request_item_id=item.request_item_id) AS allocated_count
            FROM asset_request_item item
            WHERE item.request_id=:requestId
            ORDER BY request_item_id
            FOR UPDATE");
        $this->db->bind("requestId", $requestId);
        $requestItems = $this->db->resultSet();

        $normalized = [];
        foreach ($rawItemAssets as $itemId => $selectedAssets) {
            $itemId = (int) $itemId;
            if ($itemId < 1 || !is_array($selectedAssets)) {
                throw new InvalidArgumentException("Invalid request item allocation.");
            }
            $normalized[$itemId] = array_values(
                array_unique(array_filter(
                    array_map(
                        static fn($assetNum) => trim((string) $assetNum),
                        $selectedAssets,
                    ),
                    static fn(string $assetNum): bool => $assetNum !== "",
                )),
            );
        }

        $selectedAssetSet = [];
        $assetCategories = [];
        $validItemIds = [];
        foreach ($requestItems as $requestItem) {
            $itemId = (int) $requestItem["request_item_id"];
            $validItemIds[$itemId] = true;
            $approvedQuantity = (int) ($requestItem["approved_quantity"] ?? 0);
            $allocatedQuantity = (int) ($requestItem["allocated_count"] ?? 0);
            $remainingQuantity = max(0, $approvedQuantity - $allocatedQuantity);
            $selectedAssets = $normalized[$itemId] ?? [];
            if (count($selectedAssets) !== $remainingQuantity) {
                throw new RuntimeException(
                    "Every remaining approved asset must be issued in one submission.",
                );
            }
            foreach ($selectedAssets as $assetNum) {
                if (isset($selectedAssetSet[$assetNum])) {
                    throw new RuntimeException(
                        "The same asset cannot be allocated more than once.",
                    );
                }
                $selectedAssetSet[$assetNum] = $itemId;
                $assetCategories[$assetNum] = (string) $requestItem["assetKat"];
            }
        }
        foreach (array_keys($normalized) as $itemId) {
            if (!isset($validItemIds[$itemId])) {
                throw new RuntimeException(
                    "One selected request item does not belong to this request.",
                );
            }
        }

        if (!$selectedAssetSet) {
            throw new RuntimeException("Select at least one asset to issue.");
        }
        if (count($selectedAssetSet) > 8) {
            throw new RuntimeException("You can issue a maximum of 8 assets in one batch.");
        }

        $expectedAssetSet = array_fill_keys($assetNums, true);
        if (
            count($selectedAssetSet) !== count($expectedAssetSet) ||
            array_diff_key($selectedAssetSet, $expectedAssetSet) ||
            array_diff_key($expectedAssetSet, $selectedAssetSet)
        ) {
            throw new RuntimeException(
                "The selected assets do not match the approved request allocation.",
            );
        }

        return [
            "requestId" => $requestId,
            "actor" => $actor,
            "itemAssets" => $normalized,
            "assetCategories" => $assetCategories,
        ];
    }

    /**
     * Record request allocations on the same connection and transaction used
     * for asset_issue and assetdata updates.
     */
    private function completeRequestAllocation(
        array $allocation,
        array $assets,
        ?string $issueNo,
    ): array {
        $assetToIssue = [];
        foreach ($assets as $asset) {
            $assetToIssue[(string) $asset["assetNum"]] = (int) $asset["issueId"];
        }

        foreach ($allocation["itemAssets"] as $itemId => $selectedAssets) {
            foreach ($selectedAssets as $assetNum) {
                if (!isset($assetToIssue[$assetNum])) {
                    throw new RuntimeException(
                        "An issued asset could not be linked to the request.",
                    );
                }
                $this->db->query("INSERT INTO asset_request_allocation
                    (request_item_id,assetNum,issue_id,allocated_by)
                    VALUES (:itemId,:assetNum,:issueId,:actor)");
                $this->db->bind("itemId", (int) $itemId);
                $this->db->bind("assetNum", $assetNum);
                $this->db->bind("issueId", $assetToIssue[$assetNum]);
                $this->db->bind("actor", $allocation["actor"]);
                $this->db->executeOrFail();
            }

            $this->db->query("UPDATE asset_request_item
                SET item_status='ALLOCATED'
                WHERE request_item_id=:itemId AND approved_quantity>0");
            $this->db->bind("itemId", (int) $itemId);
            $this->db->executeOrFail();
        }

        // lockRequestAllocation already required the complete remaining set,
        // and any failed insert rolls back this transaction.
        $remaining = 0;
        $nextStatus = "PENDING_RECEIVER_ACCEPTANCE";

        $this->db->query("UPDATE asset_request
            SET status=:nextStatus,
                issued_by=:actor,issued_at=NOW()
            WHERE request_id=:requestId AND status='APPROVED'");
        $this->db->bind("nextStatus", $nextStatus);
        $this->db->bind("actor", $allocation["actor"]);
        $this->db->bind("requestId", (int) $allocation["requestId"]);
        $this->db->executeOrFail();
        if ($this->db->rowCount() !== 1) {
            throw new RuntimeException(
                "The request status changed while assets were being issued.",
            );
        }

        $this->db->query("INSERT INTO asset_request_event
            (request_id,event_type,from_status,to_status,actor_username,remarks)
            VALUES (:requestId,:eventType,'APPROVED',
                :nextStatus,:actor,:remarks)");
        $this->db->bind("requestId", (int) $allocation["requestId"]);
        $this->db->bind("eventType", "ISSUED");
        $this->db->bind("nextStatus", $nextStatus);
        $this->db->bind("actor", $allocation["actor"]);
        $this->db->bind(
            "remarks",
            $issueNo,
        );
        $this->db->executeOrFail();
        return [
            "remaining" => $remaining,
            "complete" => true,
        ];
    }

    public function logAssignmentEmail($issueId, $email, array $delivery, $type = "ASSIGN")
    {
        $this->db->query("INSERT INTO assignment_email_log
        (issue_id,notification_type,recipient_email,delivery_status,attachment_count,error_message)
        VALUES (:issueId,:type,:email,:status,:attachments,:message)");
        $this->db->bind("issueId", (int) $issueId);
        $this->db->bind("type", $type === "RETURN" ? "RETURN" : "ASSIGN");
        $this->db->bind("email", trim((string) $email) ?: null);
        $this->db->bind("status", $delivery["status"]);
        $this->db->bind("attachments", (int) ($delivery["attachments"] ?? 0));
        $this->db->bind(
            "message",
            $delivery["status"] === "SENT"
                ? null
                : substr((string) ($delivery["message"] ?? ""), 0, 500),
        );
        $this->db->executeOrFail();
    }

    /**
     * Resolve one open issue to every open asset in the same assignment batch.
     *
     * Active-assignment links may identify only the clicked row. Expanding the
     * selection on the server keeps the return form batch-safe even if a stale
     * bookmark or older view supplies a single issue ID.
     */
    public function getOpenIssueBatchIds(int $issueId): array
    {
        if ($issueId <= 0) {
            return [];
        }

        $this->db->query("SELECT member.issue_id
            FROM asset_issue selected_issue
            JOIN asset_issue member
              ON COALESCE(NULLIF(member.batch_no,''),member.issue_no) =
                 COALESCE(NULLIF(selected_issue.batch_no,''),selected_issue.issue_no)
             AND member.assignee_type = selected_issue.assignee_type
             AND member.empId <=> selected_issue.empId
             AND member.deptId <=> selected_issue.deptId
            WHERE selected_issue.issue_id = :selected_batch_issue_id
              AND selected_issue.status = 'ISSUED'
              AND member.status = 'ISSUED'
            ORDER BY member.issue_id ASC
            LIMIT 8");
        $this->db->bind("selected_batch_issue_id", $issueId);

        return array_values(
            array_filter(
                array_map(
                    static fn(array $row): int => (int) ($row["issue_id"] ?? 0),
                    $this->db->resultSet(),
                ),
            ),
        );
    }

    /** Load only explicitly selected open issues (for edit/return deep links). */
    public function getOpenIssues(array $issueIds = []): array
    {
        $issueIds = array_values(
            array_unique(array_filter(array_map("intval", $issueIds))),
        );
        if (count($issueIds) > 8) {
            throw new InvalidArgumentException(
                "A maximum of 8 assets can be returned in one batch.",
            );
        }
        if (!$issueIds) {
            return [];
        }
        $placeholders = [];
        foreach ($issueIds as $index => $issueId) {
            $placeholders[] = ":selected_issue_{$index}";
        }
        $this->db->query("SELECT i.issue_id,i.issue_no,i.batch_no,i.assetNum,i.assignee_type,i.empId,i.deptId,i.issue_date,
        i.acknowledged_by,a.Description,a.assetKat,a.assetImage,k.Description AS category,s.Description AS asset_status,
        e.fullname AS employee_name,d.deptName
        FROM asset_issue i JOIN assetdata a ON a.assetNum=i.assetNum
        JOIN assetkat k ON k.assetKat=a.assetKat JOIN assetstat s ON s.assetStatus=a.assetStat
        LEFT JOIN employee e ON e.empId=i.empId LEFT JOIN department d ON d.deptId=i.deptId
        WHERE i.status='ISSUED'
          AND i.issue_id IN (" .
            implode(",", $placeholders) .
            ")
        ORDER BY i.issue_date DESC");
        foreach ($issueIds as $index => $issueId) {
            $this->db->bind("selected_issue_{$index}", $issueId);
        }
        return $this->db->resultSet();
    }

    /** Search open assignments without loading the entire issue table. */
    public function searchOpenIssues(
        string $term,
        string $custodian = "",
        int $limit = 30
    ): array {
        $term = mb_substr(trim($term), 0, 100);
        $custodian = trim($custodian);
        $custodianMatch = [];
        $hasCustodian = preg_match('/^(USER|DEPARTMENT)\|(.+)$/', $custodian, $custodianMatch) === 1;
        if (mb_strlen($term) < 2 && !$hasCustodian) {
            return [];
        }
        $limit = max(1, min($limit, $hasCustodian ? 500 : 50));
        $where = [
            "i.status='ISSUED'",
            "NOT EXISTS (
                SELECT 1 FROM asset_return_request pending_return
                WHERE pending_return.issue_id=i.issue_id
                  AND pending_return.status='PENDING'
            )",
        ];
        $bindings = [];

        if ($hasCustodian) {
            $match = $custodianMatch;
            $where[] = "i.assignee_type = :return_assignee_type";
            $bindings["return_assignee_type"] = $match[1];
            if ($match[1] === "USER") {
                if ((int) $match[2] <= 0) {
                    return [];
                }
                $where[] = "i.empId = :return_custodian_id";
                $bindings["return_custodian_id"] = (int) $match[2];
            } else {
                if (trim((string) $match[2]) === "") {
                    return [];
                }
                $where[] = "i.deptId = :return_custodian_id";
                $bindings["return_custodian_id"] = $match[2];
            }
        }

        if ($term !== "") {
            $where[] = "(i.assetNum LIKE :return_asset_search
                OR a.serial_number LIKE :return_serial_search
                OR a.Description LIKE :return_description_search
                OR e.empId LIKE :return_employee_id_search
                OR e.fullname LIKE :return_employee_search
                OR d.deptName LIKE :return_department_search
                OR k.Description LIKE :return_category_search)";
            $prefix = $term . "%";
            foreach (
                [
                    "return_asset_search",
                    "return_serial_search",
                    "return_description_search",
                    "return_employee_id_search",
                    "return_employee_search",
                    "return_department_search",
                    "return_category_search",
                ]
                as $name
            ) {
                $bindings[$name] = $prefix;
            }
        }

        $exactAssetOrder = $term !== ""
            ? "CASE WHEN i.assetNum = :return_exact_asset THEN 0 ELSE 1 END,"
            : "";

        $this->db->query("SELECT
            i.issue_id,
            i.issue_no,
            i.batch_no,
            i.assetNum,
            i.assignee_type,
            i.empId,
            i.deptId,
            i.issue_date,
            i.acknowledged_by,
            a.Description,
            a.assetKat,
            a.assetImage,
            k.Description AS category,
            s.Description AS asset_status,
            e.fullname AS employee_name,
            d.deptName
        FROM asset_issue i
        JOIN assetdata a ON a.assetNum=i.assetNum
        JOIN assetkat k ON k.assetKat=a.assetKat
        JOIN assetstat s ON s.assetStatus=a.assetStat
        LEFT JOIN employee e ON e.empId=i.empId
        LEFT JOIN department d ON d.deptId=i.deptId
        WHERE " .
            implode(" AND ", $where) .
            "
        ORDER BY
            {$exactAssetOrder}
            i.issue_date DESC,
            i.issue_id DESC
        LIMIT :return_issue_limit");
        foreach ($bindings as $key => $value) {
            $this->db->bind($key, $value);
        }
        if ($term !== "") {
            $this->db->bind("return_exact_asset", $term);
        }
        $this->db->bind("return_issue_limit", $limit);
        return $this->db->resultSet();
    }

    public function getReturnHistory()
    {
        $this->db->query("SELECT GROUP_CONCAT(r.return_id ORDER BY r.return_id) return_id,
        COALESCE(r.batch_no,r.return_no) return_no,MIN(r.return_date) return_date,r.return_type,
        GROUP_CONCAT(CONCAT(a.Description,': ',r.condition_in) ORDER BY i.assetNum SEPARATOR '|||') condition_in,
        MAX(r.reason) reason,GROUP_CONCAT(i.issue_no ORDER BY i.issue_id) issue_no,
        GROUP_CONCAT(i.assetNum ORDER BY i.assetNum SEPARATOR '|||') assetNum,
        GROUP_CONCAT(a.Description ORDER BY i.assetNum SEPARATOR '|||') assetDescriptions,
        GROUP_CONCAT(k.Description ORDER BY i.assetNum SEPARATOR '|||') categoryNames,
        i.assignee_type,e.fullname AS custodian,d.deptName
        FROM asset_return r JOIN asset_issue i ON i.issue_id=r.issue_id
        JOIN assetdata a ON a.assetNum=i.assetNum
        LEFT JOIN assetkat k ON k.assetKat=a.assetKat
        LEFT JOIN employee e ON e.empId=i.empId LEFT JOIN department d ON d.deptId=i.deptId
        GROUP BY COALESCE(r.batch_no,r.return_no),r.return_type,i.assignee_type,e.fullname,d.deptName
        ORDER BY MIN(r.return_date) DESC LIMIT 50");
        return $this->db->resultSet();
    }

    public function getFilteredReturnHistory(array $filters = []): array
    {
        $search = mb_substr(trim((string) ($filters["search"] ?? "")), 0, 100);
        $category = trim((string) ($filters["category"] ?? ""));
        $condition = trim((string) ($filters["condition"] ?? ""));
        $returnType = trim((string) ($filters["returnType"] ?? ""));
        $sort = (string) ($filters["sort"] ?? "date_desc");
        $page = max(1, (int) ($filters["page"] ?? 1));
        $perPage = (int) ($filters["perPage"] ?? 50);
        if (!in_array($perPage, [25, 50, 100], true)) {
            $perPage = 50;
        }

        $sortMap = [
            "date_desc" => "MIN(r.return_date) DESC,MIN(r.return_id) DESC",
            "date_asc" => "MIN(r.return_date) ASC,MIN(r.return_id) ASC",
            "category_asc" => "MIN(k.Description) ASC,MIN(r.return_date) DESC",
            "category_desc" => "MIN(k.Description) DESC,MIN(r.return_date) DESC",
            "condition_asc" => "MIN(r.condition_in) ASC,MIN(r.return_date) DESC",
            "condition_desc" => "MIN(r.condition_in) DESC,MIN(r.return_date) DESC",
            "type_asc" => "MIN(r.return_type) ASC,MIN(r.return_date) DESC",
            "type_desc" => "MIN(r.return_type) DESC,MIN(r.return_date) DESC",
            "asset_asc" => "MIN(i.assetNum) ASC,MIN(r.return_date) DESC",
            "asset_desc" => "MIN(i.assetNum) DESC,MIN(r.return_date) DESC",
            "reference_asc" => "MIN(COALESCE(r.batch_no,r.return_no)) ASC,MIN(r.return_date) DESC",
            "reference_desc" => "MIN(COALESCE(r.batch_no,r.return_no)) DESC,MIN(r.return_date) DESC",
            "description_asc" => "MIN(a.Description) ASC,MIN(r.return_date) DESC",
            "description_desc" => "MIN(a.Description) DESC,MIN(r.return_date) DESC",
            "custodian_asc" => "COALESCE(MAX(e.fullname),MAX(d.deptName),MAX(i.acknowledged_by)) ASC,MIN(r.return_date) DESC",
            "custodian_desc" => "COALESCE(MAX(e.fullname),MAX(d.deptName),MAX(i.acknowledged_by)) DESC,MIN(r.return_date) DESC",
            "remarks_asc" => "MAX(r.reason) ASC,MIN(r.return_date) DESC",
            "remarks_desc" => "MAX(r.reason) DESC,MIN(r.return_date) DESC",
            "receiver_asc" => "MAX(r.received_by) ASC,MIN(r.return_date) DESC",
            "receiver_desc" => "MAX(r.received_by) DESC,MIN(r.return_date) DESC",
        ];
        $orderBy = $sortMap[$sort] ?? $sortMap["date_desc"];
        $where = [];
        $params = [];
        if ($search !== "") {
            $where[] =
                "(i.assetNum LIKE :searchAsset
                  OR a.Description LIKE :searchDescription
                  OR a.serial_number LIKE :searchSerial)";
            // Prefix searching can use indexes even when history reaches millions.
            $term = $search . "%";
            $params["searchAsset"] = $term;
            $params["searchDescription"] = $term;
            $params["searchSerial"] = $term;
        }
        if ($category !== "") {
            $where[] = "a.assetKat=:category";
            $params["category"] = $category;
        }
        if ($condition !== "") {
            $where[] = "r.condition_in=:condition";
            $params["condition"] = $condition;
        }
        if ($returnType !== "") {
            $where[] = "r.return_type=:returnType";
            $params["returnType"] = $returnType;
        }

        $fromSql =
            " FROM asset_return r
              JOIN asset_issue i ON i.issue_id=r.issue_id
              JOIN assetdata a ON a.assetNum=i.assetNum
              LEFT JOIN assetkat k ON k.assetKat=a.assetKat
              LEFT JOIN employee e ON e.empId=i.empId
              LEFT JOIN department d ON d.deptId=i.deptId" .
            ($where ? " WHERE " . implode(" AND ", $where) : "");
        $groupBy =
            " GROUP BY COALESCE(r.batch_no,r.return_no),
                       i.assignee_type,
                       i.empId,
                       i.deptId";

        $this->db->query(
            "SELECT COUNT(*) AS total
             FROM (SELECT 1 {$fromSql} {$groupBy}) grouped_returns",
        );
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        $count = $this->db->single();
        $total = (int) ($count["total"] ?? 0);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $sql =
            "SELECT
                GROUP_CONCAT(r.return_id ORDER BY r.return_id) AS return_id,
                COALESCE(r.batch_no,r.return_no) AS return_no,
                MIN(r.return_date) AS return_date,
                MAX(r.return_type) AS return_type,
                GROUP_CONCAT(CONCAT(a.Description, ': ', r.condition_in)
                    ORDER BY i.assetNum SEPARATOR '|||') AS condition_in,
                MAX(r.reason) AS reason,
                MAX(r.scrap_reference) AS scrap_reference,
                MAX(r.received_by) AS received_by,
                GROUP_CONCAT(i.issue_no ORDER BY i.issue_id SEPARATOR ',') AS issue_no,
                GROUP_CONCAT(i.assetNum ORDER BY i.assetNum SEPARATOR '|||') AS assetNum,
                i.assignee_type,
                MAX(i.acknowledged_by) AS acknowledged_by,
                GROUP_CONCAT(a.Description ORDER BY i.assetNum SEPARATOR '|||')
                    AS assetDescription,
                GROUP_CONCAT(COALESCE(a.serial_number,'')
                    ORDER BY i.assetNum SEPARATOR '|||') AS serial_number,
                GROUP_CONCAT(a.assetKat ORDER BY i.assetNum SEPARATOR '|||') AS categoryCode,
                GROUP_CONCAT(k.Description ORDER BY i.assetNum SEPARATOR '|||') AS categoryName,
                MAX(e.fullname) AS custodian,
                MAX(d.deptName) AS deptName" .
            $fromSql .
            $groupBy .
            " ORDER BY {$orderBy}
              LIMIT :return_page_limit OFFSET :return_page_offset";
        $this->db->query($sql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        $this->db->bind("return_page_limit", $perPage);
        $this->db->bind("return_page_offset", $offset);

        return [
            "items" => $this->db->resultSet(),
            "total" => $total,
            "page" => $page,
            "perPage" => $perPage,
            "totalPages" => $totalPages,
            "offset" => $offset,
        ];
    }

    public function getReturnHistoryCategories(): array
    {
        $this->db->query("SELECT DISTINCT k.assetKat,k.Description FROM asset_return r
        JOIN asset_issue i ON i.issue_id=r.issue_id JOIN assetdata a ON a.assetNum=i.assetNum
        JOIN assetkat k ON k.assetKat=a.assetKat ORDER BY k.Description");
        return $this->db->resultSet();
    }

    public function returnAssignedAsset($data, $files = null)
    {
        $issueIds = $data["issueIds"] ?? ($data["issueId"] ?? []);
        if (!is_array($issueIds)) {
            $issueIds = [$issueIds];
        }
        $issueIds = array_values(array_unique(array_filter(array_map("intval", $issueIds))));
        if (!$issueIds) {
            throw new InvalidArgumentException("Select at least one assigned asset.");
        }
        if (count($issueIds) > 8) {
            throw new InvalidArgumentException("You can return a maximum of 8 assets at one time.");
        }
        foreach (["returnDate", "returnType", "conditionIn"] as $field) {
            if (empty($data[$field])) {
                throw new InvalidArgumentException("Please complete all required return fields.");
            }
        }
        $returnDate = null;
        foreach (["!Y-m-d\TH:i", "!Y-m-d H:i", "!Y-m-d H:i:s"] as $format) {
            $candidate = DateTimeImmutable::createFromFormat(
                $format,
                trim((string) $data["returnDate"]),
            );
            $dateErrors = DateTimeImmutable::getLastErrors();
            if (
                $candidate instanceof DateTimeImmutable &&
                ($dateErrors === false ||
                    ((int) ($dateErrors["warning_count"] ?? 0) === 0 &&
                        (int) ($dateErrors["error_count"] ?? 0) === 0))
            ) {
                $returnDate = $candidate;
                break;
            }
        }
        if (!$returnDate) {
            throw new InvalidArgumentException("Enter a valid return date and time.");
        }
        // Return date range validation is intentionally disabled.
        $data["returnDate"] = $returnDate->format("Y-m-d H:i:s");
        // Normal returns always restore the asset to stock. Scrapping is handled
        // separately through the Issuer -> IT Manager approval workflow.
        if ($data["returnType"] !== "REISSUE") {
            throw new InvalidArgumentException(
                "Assets must be returned to stock before a scrap request is submitted.",
            );
        }
        $conditionValues = ["NEW", "GOOD", "DAMAGED"];
        $conditions = $data["conditionIn"] ?? [];
        if (!is_array($conditions)) {
            $conditions = array_fill_keys($issueIds, (string) $conditions);
        }
        foreach ($issueIds as $issueId) {
            $condition = strtoupper(trim((string) ($conditions[$issueId] ?? "")));
            if (!in_array($condition, $conditionValues, true)) {
                throw new InvalidArgumentException(
                    "Select a valid return condition for every asset.",
                );
            }
            $conditions[$issueId] = $condition;
        }
        $images = [];
        $allImages = [];
        $this->db->beginTransaction();
        try {
            $returnRequestBatch = trim((string) ($data["returnRequestBatch"] ?? ""));
            if ($returnRequestBatch !== "") {
                if (!preg_match('/^RRQ-\d{8}-\d{6}-\d{3}$/', $returnRequestBatch)) {
                    throw new InvalidArgumentException("The return-request batch is invalid.");
                }

                // A business RRQ is processed once. Internal child rows remain
                // per asset, but a caller cannot complete only part of the batch.
                $this->db->query("SELECT issue_id,return_request_no
                    FROM asset_return_request
                    WHERE status='PENDING'
                      AND (return_request_no=:batchReference
                           OR return_request_no LIKE :batchPattern)
                    FOR UPDATE");
                $this->db->bind("batchReference", $returnRequestBatch);
                $this->db->bind("batchPattern", $returnRequestBatch . "-%");
                $pendingRequestRows = array_values(array_filter(
                    $this->db->resultSet(),
                    static function (array $row) use ($returnRequestBatch): bool {
                        $number = (string) ($row["return_request_no"] ?? "");
                        return $number === $returnRequestBatch ||
                            preg_match(
                                '/^' . preg_quote($returnRequestBatch, '/') . '-\d{2}$/',
                                $number,
                            ) === 1;
                    },
                ));
                $pendingRequestIssueIds = array_map(
                    "intval",
                    array_column($pendingRequestRows, "issue_id"),
                );
                sort($pendingRequestIssueIds);
                $submittedIssueIds = $issueIds;
                sort($submittedIssueIds);
                if (!$pendingRequestIssueIds || $submittedIssueIds !== $pendingRequestIssueIds) {
                    throw new RuntimeException(
                        "Process every asset in the return request together.",
                    );
                }
            } else {
                foreach ($issueIds as $issueId) {
                    $this->db->query("SELECT return_request_id
                        FROM asset_return_request
                        WHERE issue_id=:pendingIssueId AND status='PENDING'
                        LIMIT 1 FOR UPDATE");
                    $this->db->bind("pendingIssueId", $issueId);
                    if ($this->db->single()) {
                        throw new RuntimeException(
                            "This asset belongs to a pending return request. Process the complete RRQ batch from Return Requests.",
                        );
                    }
                }
            }

            $issues = [];
            foreach ($issueIds as $issueId) {
                $this->db
                    ->query("SELECT i.issue_id,i.issue_no,i.batch_no,i.issue_date,i.assetNum,i.assignee_type,i.empId,i.deptId,e.fullname employeeName,e.email employeeEmail,
                dept_head.fullname departmentHeadName,dept_head.email departmentHeadEmail,a.Description assetDescription,
                a.serial_number,a.assetImage,k.Description assetCategory FROM asset_issue i JOIN assetdata a ON a.assetNum=i.assetNum
                LEFT JOIN employee e ON e.empId=i.empId LEFT JOIN department d ON d.deptId=i.deptId
                LEFT JOIN employee dept_head ON dept_head.empId=COALESCE(
                    d.headEmpId,
                    (
                        SELECT MIN(legacy_head.empId)
                        FROM department_head legacy_head
                        JOIN employee legacy_employee
                          ON legacy_employee.empId=legacy_head.empId
                         AND legacy_employee.aktif=1
                        WHERE legacy_head.deptId=d.deptId
                    )
                ) AND dept_head.aktif=1 LEFT JOIN assetkat k ON k.assetKat=a.assetKat
                WHERE i.issue_id=:issueId AND i.status='ISSUED' FOR UPDATE");
                $this->db->bind("issueId", $issueId);
                $issue = $this->db->single();
                if (!$issue) {
                    throw new RuntimeException("An assignment is unavailable or already returned.");
                }
                $issues[] = $issue;
            }
            $first = $issues[0];
            $firstBatchKey = trim((string) ($first["batch_no"] ?? "")) ?:
                trim((string) ($first["issue_no"] ?? ""));
            foreach ($issues as $issue) {
                $sameType = $issue["assignee_type"] === $first["assignee_type"];
                $sameCustodian =
                    $first["assignee_type"] === "DEPARTMENT"
                        ? (string) $issue["deptId"] === (string) $first["deptId"]
                        : (string) $issue["empId"] === (string) $first["empId"];
                if (!$sameType || !$sameCustodian) {
                    throw new InvalidArgumentException(
                        "All selected assets must belong to the same employee or department.",
                    );
                }
                $issueBatchKey = trim((string) ($issue["batch_no"] ?? "")) ?:
                    trim((string) ($issue["issue_no"] ?? ""));
                if ($issueBatchKey !== $firstBatchKey) {
                    throw new InvalidArgumentException(
                        "All selected assets must belong to the same ISS batch.",
                    );
                }
                // Return-versus-assignment date ordering is intentionally disabled.
            }
            $baseReturnNo = substr(
                trim($data["returnNo"] ?? "") ?:
                "RET-" . date("Ymd-His") . "-" . random_int(100, 999),
                0,
                count($issues) > 1 ? 27 : 30,
            );
            $images = $this->storeUploadedImages($files);
            $allImages = $images;
            $returnIds = [];
            $returnNos = [];
            foreach ($issues as $index => &$issue) {
                $itemCondition = $conditions[(int) $issue["issue_id"]];
                $returnNo =
                    count($issues) > 1
                        ? $baseReturnNo . "-" . str_pad((string) ($index + 1), 2, "0", STR_PAD_LEFT)
                        : $baseReturnNo;
                $this->db
                    ->query("INSERT INTO asset_return(return_no,batch_no,issue_id,return_date,return_type,condition_in,return_location,reason,scrap_reference,received_by)
              VALUES(:returnNo,:batchNo,:issueId,:returnDate,:returnType,:conditionIn,NULL,:reason,:scrapReference,:receivedBy)");
                $params = [
                    "returnNo" => $returnNo,
                    "batchNo" => $baseReturnNo,
                    "issueId" => $issue["issue_id"],
                    "returnDate" => $data["returnDate"],
                    "returnType" => $data["returnType"],
                    "conditionIn" => $itemCondition,
                    "reason" => trim($data["remarks"] ?? "") ?: null,
                    "scrapReference" => trim($data["scrapReference"] ?? "") ?: null,
                    "receivedBy" => $_SESSION["usr"]["user"],
                ];
                foreach ($params as $k => $v) {
                    $this->db->bind($k, $v);
                }
                $this->db->executeOrFail();
                $returnId = (int) $this->db->lastInsertId();
                $returnIds[] = $returnId;
                $returnNos[] = $returnNo;
                // Completing the physical return also closes any request that
                // the employee or department head sent to the Issuer.
                $this->db->query("UPDATE asset_return_request
                    SET status='COMPLETED',return_id=:returnId,processed_by=:processedBy,processed_at=NOW()
                    WHERE issue_id=:returnRequestIssue AND status='PENDING'");
                $this->db->bind("returnId", $returnId);
                $this->db->bind("processedBy", $_SESSION["usr"]["user"]);
                $this->db->bind("returnRequestIssue", $issue["issue_id"]);
                $this->db->executeOrFail();
                $this->db->query(
                    "UPDATE asset_issue SET status=:status,returned_at=:returnedAt WHERE issue_id=:issueId",
                );
                $this->db->bind(
                    "status",
                    $data["returnType"] === "SCRAP" ? "SCRAPPED" : "RETURNED",
                );
                $this->db->bind("returnedAt", $data["returnDate"]);
                $this->db->bind("issueId", $issue["issue_id"]);
                $this->db->executeOrFail();
                $this->db->query(
                    "UPDATE assetdata
                     SET empId=NULL,deptId=NULL,assetStat=:status,asset_condition=:condition
                     WHERE assetNum=:assetNum",
                );
                $this->db->bind(
                    "status",
                    $data["returnType"] === "SCRAP" ? "SCRAPPED" : "IN_STOCK",
                );
                $this->db->bind("condition", $itemCondition);
                $this->db->bind("assetNum", $issue["assetNum"]);
                $this->db->executeOrFail();
                $media = $index === 0 ? $images : $this->copyUploadedImages($images);
                if ($index > 0) {
                    $allImages = array_merge($allImages, $media);
                }
                foreach ($media as $image) {
                    $this->insertAssetMedia($issue["assetNum"], "RETURN", $image, null, $returnId);
                }
                $issue["returnId"] = $returnId;
                $issue["returnNo"] = $returnNo;
                $issue["conditionIn"] = $itemCondition;
            }
            unset($issue);
            $this->db->commit();
            $photoFiles = [];
            foreach ($issues as $issue) {
                if (!empty($issue["assetImage"])) {
                    $photoFiles[] =
                        __DIR__ . "/../../public/uploads/asset/" . basename($issue["assetImage"]);
                }
            }
            foreach ($allImages as $image) {
                $photoFiles[] = $image["path"];
            }
            return [
                "issueId" => $first["issue_id"],
                "issueIds" => $issueIds,
                "returnIds" => $returnIds,
                "assets" => $issues,
                "assetNum" => $first["assetNum"],
                "returnNo" => $returnNos[0],
                "returnNos" => $returnNos,
                "type" => $data["returnType"],
                "returnDate" => $data["returnDate"],
                "conditionIn" => $conditions[(int) $first["issue_id"]],
                "reason" => trim($data["remarks"] ?? "") ?: null,
                "employeeName" => $first["employeeName"],
                "employeeEmail" => $first["employeeEmail"],
                "assigneeType" => $first["assignee_type"],
                "departmentHeadName" => $first["departmentHeadName"],
                "departmentHeadEmail" => $first["departmentHeadEmail"],
                "assetDescription" => $first["assetDescription"],
                "assetCategory" => $first["assetCategory"],
                "serialNumber" => $first["serial_number"],
                "photoFiles" => $photoFiles,
            ];
        } catch (Throwable $e) {
            $this->db->rollBack();
            $this->removeUploadedImages($allImages ?: $images);
            throw $e;
        }
    }

    private function storeUploadedImages($files)
    {
        if (!$files || !isset($files["name"])) {
            return [];
        }
        $names = is_array($files["name"]) ? $files["name"] : [$files["name"]];
        $tmpNames = is_array($files["tmp_name"]) ? $files["tmp_name"] : [$files["tmp_name"]];
        $errors = is_array($files["error"]) ? $files["error"] : [$files["error"]];
        $sizes = is_array($files["size"]) ? $files["size"] : [$files["size"]];
        $result = [];
        $dir = __DIR__ . "/../../public/uploads/asset/";
        if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
            throw new RuntimeException("Unable to create image directory.");
        }
        $allowed = ["image/jpeg" => "jpg", "image/png" => "png", "image/webp" => "webp"];
        foreach ($names as $i => $original) {
            if ($errors[$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($errors[$i] !== UPLOAD_ERR_OK || $sizes[$i] > 5 * 1024 * 1024) {
                throw new InvalidArgumentException("Each image must be no larger than 5 MB.");
            }
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmpNames[$i]);
            if (!isset($allowed[$mime])) {
                throw new InvalidArgumentException("Images must be JPG, PNG, or WEBP.");
            }
            $name =
                SaasTenantContext::storagePrefix() .
                bin2hex(random_bytes(16)) .
                "." .
                $allowed[$mime];
            if (!move_uploaded_file($tmpNames[$i], $dir . $name)) {
                throw new RuntimeException("Unable to save an uploaded image.");
            }
            $result[] = [
                "file_name" => $name,
                "original_name" => basename($original),
                "mime_type" => $mime,
                "file_size" => (int) $sizes[$i],
                "path" => $dir . $name,
            ];
        }
        return $result;
    }

    private function copyUploadedImages($images)
    {
        if (!$images) {
            return [];
        }
        $copies = [];
        $dir = __DIR__ . "/../../public/uploads/asset/";
        try {
            foreach ($images as $image) {
                $extension = strtolower(pathinfo($image["file_name"], PATHINFO_EXTENSION));
                $name =
                    SaasTenantContext::storagePrefix() .
                    bin2hex(random_bytes(16)) .
                    "." .
                    $extension;
                $path = $dir . $name;
                if (!copy($image["path"], $path)) {
                    throw new RuntimeException("Unable to copy the shared entry image.");
                }
                $copy = $image;
                $copy["file_name"] = $name;
                $copy["path"] = $path;
                $copies[] = $copy;
            }
            return $copies;
        } catch (Throwable $error) {
            $this->removeUploadedImages($copies);
            throw $error;
        }
    }

    private function insertAssetMedia($assetNum, $stage, $image, $issueId, $returnId)
    {
        $this->db->query("INSERT INTO asset_media
        (assetNum,issue_id,return_id,media_stage,file_name,original_name,mime_type,file_size,uploaded_by)
        VALUES (:assetNum,:issueId,:returnId,:stage,:fileName,:originalName,:mimeType,:fileSize,:uploadedBy)");
        foreach (
            [
                "assetNum" => $assetNum,
                "issueId" => $issueId,
                "returnId" => $returnId,
                "stage" => $stage,
                "fileName" => $image["file_name"],
                "originalName" => $image["original_name"],
                "mimeType" => $image["mime_type"],
                "fileSize" => $image["file_size"],
                "uploadedBy" => $_SESSION["usr"]["user"],
            ]
            as $key => $value
        ) {
            $this->db->bind($key, $value);
        }
        $this->db->executeOrFail();
    }

    private function removeUploadedImages($images)
    {
        foreach ($images as $image) {
            if (is_file($image["path"])) {
                unlink($image["path"]);
            }
        }
    }

    private function storeInvoicePdf($file)
    {
        if (!$file || !isset($file["error"]) || (int) $file["error"] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ((int) $file["error"] !== UPLOAD_ERR_OK) {
            throw new RuntimeException("The invoice PDF upload failed.");
        }
        if ((int) ($file["size"] ?? 0) <= 0 || (int) $file["size"] > 10 * 1024 * 1024) {
            throw new InvalidArgumentException("Invoice PDF must not exceed 10 MB.");
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file["tmp_name"]);
        if ($mime !== "application/pdf") {
            throw new InvalidArgumentException("Invoice must be a valid PDF file.");
        }
        $dir = __DIR__ . "/../../public/uploads/invoices/";
        if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
            throw new RuntimeException("Unable to create the invoice upload directory.");
        }
        $name =
            SaasTenantContext::storagePrefix() .
            "invoice_" .
            bin2hex(random_bytes(16)) .
            ".pdf";
        $path = $dir . $name;
        if (!move_uploaded_file($file["tmp_name"], $path)) {
            throw new RuntimeException("Unable to save the invoice PDF.");
        }
        return ["file_name" => $name, "path" => $path];
    }

    /**
     * Secure the two retained legacy single-image entry points using the same
     * MIME, size, and random-name policy as the current multi-image workflow.
     */
    private function storeSingleAssetImage(array $file): array
    {
        if ((int) ($file["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException("The asset image upload failed.");
        }
        $size = (int) ($file["size"] ?? 0);
        if ($size <= 0 || $size > 5 * 1024 * 1024) {
            throw new InvalidArgumentException("Asset images must not exceed 5 MB.");
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file["tmp_name"]);
        $extensions = [
            "image/jpeg" => "jpg",
            "image/png" => "png",
            "image/webp" => "webp",
        ];
        if (!isset($extensions[$mime])) {
            throw new InvalidArgumentException("Asset images must be JPG, PNG, or WebP.");
        }

        $dir = __DIR__ . "/../../public/uploads/asset/";
        if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
            throw new RuntimeException("Unable to create the asset image directory.");
        }
        $fileName =
            SaasTenantContext::storagePrefix() .
            "asset_" .
            bin2hex(random_bytes(16)) .
            "." .
            $extensions[$mime];
        $path = $dir . $fileName;
        if (!move_uploaded_file($file["tmp_name"], $path)) {
            throw new RuntimeException("Unable to save the asset image.");
        }

        return ["file_name" => $fileName, "path" => $path];
    }
}
