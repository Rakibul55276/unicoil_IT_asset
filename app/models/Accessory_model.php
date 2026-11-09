<?php

/**
 * Quantity-based accessory stock.
 *
 * This model is intentionally independent from assetdata, asset_issue and
 * asset_return. Serialized assets continue to use their existing lifecycle.
 */
class Accessory_model
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function getInventory(string $search = "", string $category = "", string $status = ""): array
    {
        $sql = "SELECT accessory_id,accessory_code,accessory_name,category,unit,
                       quantity_on_hand,reorder_level,active,created_by,created_at,updated_at
                FROM accessory_item WHERE 1=1";
        $search = trim($search);
        $category = trim($category);
        $status = strtoupper(trim($status));
        if ($search !== "") {
            $sql .= " AND (accessory_code LIKE :codeSearch OR accessory_name LIKE :nameSearch)";
        }
        if ($category !== "") {
            $sql .= " AND category=:category";
        }
        if ($status === "AVAILABLE") {
            $sql .= " AND active=1 AND quantity_on_hand>0";
        } elseif ($status === "LOW") {
            $sql .= " AND active=1 AND quantity_on_hand<=reorder_level";
        } elseif ($status === "OUT") {
            $sql .= " AND active=1 AND quantity_on_hand=0";
        } elseif ($status === "INACTIVE") {
            $sql .= " AND active=0";
        }
        $sql .= " ORDER BY active DESC,accessory_name,accessory_code";
        $this->db->query($sql);
        if ($search !== "") {
            $value = "%" . $search . "%";
            $this->db->bind("codeSearch", $value);
            $this->db->bind("nameSearch", $value);
        }
        if ($category !== "") {
            $this->db->bind("category", $category);
        }
        return $this->db->resultSet();
    }

    public function getActiveItems(): array
    {
        $this->db->query(
            "SELECT accessory_id,accessory_code,accessory_name,category,unit,quantity_on_hand
             FROM accessory_item WHERE active=1 ORDER BY accessory_name,accessory_code",
        );
        return $this->db->resultSet();
    }

    public function getItemById(int $accessoryId): ?array
    {
        $this->db->query(
            "SELECT accessory_id,accessory_code,accessory_name,category,unit,
                    quantity_on_hand,reorder_level,active
             FROM accessory_item WHERE accessory_id=:accessoryId LIMIT 1",
        );
        $this->db->bind("accessoryId", $accessoryId, PDO::PARAM_INT);
        $item = $this->db->single();
        return $item ?: null;
    }

    public function getCategories(): array
    {
        $this->db->query(
            "SELECT DISTINCT category FROM accessory_item
             WHERE category<>'' ORDER BY category",
        );
        return array_map(
            static function (array $row): string {
                return (string) $row["category"];
            },
            $this->db->resultSet(),
        );
    }

    public function getRecentTransactions(int $limit = 100): array
    {
        $limit = max(1, min($limit, 500));
        $this->db->query(
            "SELECT t.transaction_id,t.transaction_no,t.event_type,t.quantity,
                    t.balance_before,t.balance_after,t.recipient_type,t.reference_no,
                    t.remarks,t.performed_by,t.created_at,
                    i.accessory_code,i.accessory_name,i.category,i.unit,
                    e.empId,e.fullname,d.deptId,d.deptName
             FROM accessory_transaction t
             JOIN accessory_item i ON i.accessory_id=t.accessory_id
             LEFT JOIN employee e ON e.empId=t.empId
             LEFT JOIN department d ON d.deptId=t.deptId
             ORDER BY t.created_at DESC,t.transaction_id DESC
             LIMIT :transactionLimit",
        );
        $this->db->bind("transactionLimit", $limit, PDO::PARAM_INT);
        return $this->db->resultSet();
    }

    public function getActiveEmployees(): array
    {
        $this->db->query(
            "SELECT e.empId,e.fullname,e.position,d.deptName
             FROM employee e LEFT JOIN department d ON d.deptId=e.deptId
             WHERE e.aktif=1 ORDER BY e.fullname,e.empId",
        );
        return $this->db->resultSet();
    }

    public function getActiveDepartments(): array
    {
        $this->db->query(
            "SELECT deptId,deptName FROM department WHERE active=1 ORDER BY deptName",
        );
        return $this->db->resultSet();
    }

    /** Create a stock item and its optional opening receipt in one transaction. */
    public function createItem(array $data, string $username): int
    {
        $code = strtoupper(trim((string) ($data["accessory_code"] ?? "")));
        $name = trim((string) ($data["accessory_name"] ?? ""));
        $category = trim((string) ($data["category"] ?? ""));
        $unit = strtoupper(trim((string) ($data["unit"] ?? "PCS")));
        $openingQuantity = $this->quantity($data["opening_quantity"] ?? 0, true);
        $reorderLevel = $this->quantity($data["reorder_level"] ?? 0, true);
        $remarks = trim((string) ($data["remarks"] ?? ""));
        $reference = $this->optionalText($data["reference_no"] ?? "", 100);

        if (!preg_match('/^[A-Z0-9][A-Z0-9_-]{1,29}$/', $code)) {
            throw new InvalidArgumentException(
                "Accessory code must contain 2-30 letters, numbers, hyphens or underscores.",
            );
        }
        if ($name === "" || mb_strlen($name) > 150) {
            throw new InvalidArgumentException("Accessory name is required (maximum 150 characters). ");
        }
        if ($category === "" || mb_strlen($category) > 100) {
            throw new InvalidArgumentException("Accessory category is required (maximum 100 characters). ");
        }
        if ($unit === "" || mb_strlen($unit) > 20) {
            throw new InvalidArgumentException("Unit is required (maximum 20 characters). ");
        }
        if ($openingQuantity > 0 && $remarks === "") {
            throw new InvalidArgumentException("Opening-stock remarks or justification are required.");
        }
        if (mb_strlen($remarks) > 500) {
            throw new InvalidArgumentException("Remarks cannot exceed 500 characters.");
        }

        try {
            $this->db->beginTransaction();
            $this->db->query(
                "INSERT INTO accessory_item
                 (accessory_code,accessory_name,category,unit,quantity_on_hand,reorder_level,created_by)
                 VALUES(:code,:name,:category,:unit,:quantity,:reorderLevel,:createdBy)",
            );
            $this->db->bind("code", $code);
            $this->db->bind("name", $name);
            $this->db->bind("category", $category);
            $this->db->bind("unit", $unit);
            $this->db->bind("quantity", $this->decimal($openingQuantity));
            $this->db->bind("reorderLevel", $this->decimal($reorderLevel));
            $this->db->bind("createdBy", $username);
            $this->db->execute();
            $accessoryId = (int) $this->db->lastInsertId();

            if ($openingQuantity > 0) {
                $this->insertTransaction(
                    $accessoryId,
                    "RECEIPT",
                    $openingQuantity,
                    0.0,
                    $openingQuantity,
                    "NONE",
                    null,
                    null,
                    $reference,
                    $remarks,
                    $username,
                );
            }
            $this->db->commit();
            return $accessoryId;
        } catch (PDOException $exception) {
            $this->db->rollBack();
            if ((string) $exception->getCode() === "23000") {
                throw new RuntimeException("Accessory code already exists.");
            }
            throw $exception;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    /** Administrator correction; stock differences are always ledger adjustments. */
    public function updateItem(array $data, string $username): void
    {
        $accessoryId = (int) ($data["accessory_id"] ?? 0);
        $code = strtoupper(trim((string) ($data["accessory_code"] ?? "")));
        $name = trim((string) ($data["accessory_name"] ?? ""));
        $category = trim((string) ($data["category"] ?? ""));
        $unit = strtoupper(trim((string) ($data["unit"] ?? "PCS")));
        $newStock = $this->quantity($data["quantity_on_hand"] ?? 0, true);
        $lowStockLevel = $this->quantity($data["reorder_level"] ?? 0, true);
        $active = ((string) ($data["active"] ?? "0")) === "1" ? 1 : 0;
        $remarks = trim((string) ($data["remarks"] ?? ""));
        $reference = $this->optionalText($data["reference_no"] ?? "", 100);

        if ($accessoryId < 1) {
            throw new InvalidArgumentException("Select a valid accessory item.");
        }
        if (!preg_match('/^[A-Z0-9][A-Z0-9_-]{1,29}$/', $code)) {
            throw new InvalidArgumentException(
                "Accessory code must contain 2-30 letters, numbers, hyphens or underscores.",
            );
        }
        if ($name === "" || mb_strlen($name) > 150) {
            throw new InvalidArgumentException("Accessory name is required (maximum 150 characters).");
        }
        if ($category === "" || mb_strlen($category) > 100) {
            throw new InvalidArgumentException("Accessory category is required (maximum 100 characters).");
        }
        if ($unit === "" || mb_strlen($unit) > 20) {
            throw new InvalidArgumentException("Unit is required (maximum 20 characters).");
        }
        if (mb_strlen($remarks) > 500) {
            throw new InvalidArgumentException("Change remarks cannot exceed 500 characters.");
        }

        try {
            $this->db->beginTransaction();
            $this->db->query(
                "SELECT accessory_id,quantity_on_hand FROM accessory_item
                 WHERE accessory_id=:accessoryId FOR UPDATE",
            );
            $this->db->bind("accessoryId", $accessoryId, PDO::PARAM_INT);
            $current = $this->db->single();
            if (!$current) {
                throw new RuntimeException("Accessory item was not found.");
            }
            $before = round((float) $current["quantity_on_hand"], 3);
            if ($before !== $newStock && $remarks === "") {
                throw new InvalidArgumentException(
                    "Change remarks are required when changing current stock.",
                );
            }

            $this->db->query(
                "UPDATE accessory_item SET accessory_code=:code,accessory_name=:name,
                    category=:category,unit=:unit,quantity_on_hand=:quantity,
                    reorder_level=:lowStockLevel,active=:active
                 WHERE accessory_id=:accessoryId",
            );
            $this->db->bind("code", $code);
            $this->db->bind("name", $name);
            $this->db->bind("category", $category);
            $this->db->bind("unit", $unit);
            $this->db->bind("quantity", $this->decimal($newStock));
            $this->db->bind("lowStockLevel", $this->decimal($lowStockLevel));
            $this->db->bind("active", $active, PDO::PARAM_INT);
            $this->db->bind("accessoryId", $accessoryId, PDO::PARAM_INT);
            $this->db->execute();

            if ($before !== $newStock) {
                $this->insertTransaction(
                    $accessoryId,
                    $newStock > $before ? "ADJUSTMENT_IN" : "ADJUSTMENT_OUT",
                    abs($newStock - $before),
                    $before,
                    $newStock,
                    "NONE",
                    null,
                    null,
                    $reference,
                    $remarks,
                    $username,
                );
            }
            $this->db->commit();
        } catch (PDOException $exception) {
            $this->db->rollBack();
            if ((string) $exception->getCode() === "23000") {
                throw new RuntimeException("Accessory code already exists.");
            }
            throw $exception;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    /** Delete unused setup entries only; operational custody history is permanent. */
    public function deleteItem(int $accessoryId): void
    {
        if ($accessoryId < 1) {
            throw new InvalidArgumentException("Select a valid accessory item.");
        }
        try {
            $this->db->beginTransaction();
            $this->db->query(
                "SELECT accessory_id FROM accessory_item
                 WHERE accessory_id=:accessoryId FOR UPDATE",
            );
            $this->db->bind("accessoryId", $accessoryId, PDO::PARAM_INT);
            if (!$this->db->single()) {
                throw new RuntimeException("Accessory item was not found.");
            }
            $this->db->query(
                "SELECT COUNT(*) AS operational_count FROM accessory_transaction
                 WHERE accessory_id=:accessoryId AND event_type<>'RECEIPT'",
            );
            $this->db->bind("accessoryId", $accessoryId, PDO::PARAM_INT);
            $usage = $this->db->single();
            if ((int) ($usage["operational_count"] ?? 0) > 0) {
                throw new RuntimeException(
                    "This accessory has issue, return or adjustment history. Mark it Inactive instead of deleting it.",
                );
            }
            $this->db->query(
                "DELETE FROM accessory_transaction WHERE accessory_id=:accessoryId",
            );
            $this->db->bind("accessoryId", $accessoryId, PDO::PARAM_INT);
            $this->db->execute();
            $this->db->query("DELETE FROM accessory_item WHERE accessory_id=:accessoryId");
            $this->db->bind("accessoryId", $accessoryId, PDO::PARAM_INT);
            $this->db->execute();
            $this->db->commit();
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    /**
     * Apply one stock movement with a locked balance and immutable ledger row.
     */
    public function recordTransaction(array $data, string $username): string
    {
        $accessoryId = (int) ($data["accessory_id"] ?? 0);
        $eventType = strtoupper(trim((string) ($data["event_type"] ?? "")));
        $quantity = $this->quantity($data["quantity"] ?? 0, false);
        $recipientType = strtoupper(trim((string) ($data["recipient_type"] ?? "NONE")));
        $remarks = trim((string) ($data["remarks"] ?? ""));
        $reference = $this->optionalText($data["reference_no"] ?? "", 100);
        $allowedEvents = ["RECEIPT", "ISSUE", "RETURN", "ADJUSTMENT_IN", "ADJUSTMENT_OUT"];
        if ($accessoryId < 1 || !in_array($eventType, $allowedEvents, true)) {
            throw new InvalidArgumentException("Select a valid accessory and stock action.");
        }
        if ($remarks === "" || mb_strlen($remarks) > 500) {
            throw new InvalidArgumentException("Remarks/justification are required (maximum 500 characters). ");
        }

        $empId = null;
        $deptId = null;
        if (in_array($eventType, ["ISSUE", "RETURN"], true)) {
            if (!in_array($recipientType, ["EMPLOYEE", "DEPARTMENT"], true)) {
                throw new InvalidArgumentException("Select an employee or department recipient.");
            }
            if ($recipientType === "EMPLOYEE") {
                $empId = (int) ($data["empId"] ?? 0);
                if ($empId < 1 || !$this->activeEmployeeExists($empId)) {
                    throw new InvalidArgumentException("Select a valid active employee.");
                }
            } else {
                $deptId = trim((string) ($data["deptId"] ?? ""));
                if ($deptId === "" || !$this->activeDepartmentExists($deptId)) {
                    throw new InvalidArgumentException("Select a valid active department.");
                }
            }
        } else {
            $recipientType = "NONE";
        }

        try {
            $this->db->beginTransaction();
            $this->db->query(
                "SELECT accessory_id,quantity_on_hand,active
                 FROM accessory_item WHERE accessory_id=:accessoryId FOR UPDATE",
            );
            $this->db->bind("accessoryId", $accessoryId, PDO::PARAM_INT);
            $item = $this->db->single();
            if (!$item || (int) $item["active"] !== 1) {
                throw new RuntimeException("The selected accessory is unavailable or inactive.");
            }
            $before = round((float) $item["quantity_on_hand"], 3);
            if ($eventType === "RETURN") {
                $custody = $this->recipientCustody(
                    $accessoryId,
                    $recipientType,
                    $empId,
                    $deptId,
                );
                if ($quantity > $custody) {
                    throw new RuntimeException(
                        "Return quantity exceeds recipient custody. Available custody is " .
                        $this->decimal($custody) . ".",
                    );
                }
            }
            $outgoing = in_array($eventType, ["ISSUE", "ADJUSTMENT_OUT"], true);
            $after = round($outgoing ? $before - $quantity : $before + $quantity, 3);
            if ($after < 0) {
                throw new RuntimeException(
                    "Insufficient stock. Available quantity is " . $this->decimal($before) . ".",
                );
            }

            $this->db->query(
                "UPDATE accessory_item SET quantity_on_hand=:balance
                 WHERE accessory_id=:accessoryId",
            );
            $this->db->bind("balance", $this->decimal($after));
            $this->db->bind("accessoryId", $accessoryId, PDO::PARAM_INT);
            $this->db->execute();
            $transactionNo = $this->insertTransaction(
                $accessoryId,
                $eventType,
                $quantity,
                $before,
                $after,
                $recipientType,
                $empId,
                $deptId,
                $reference,
                $remarks,
                $username,
            );
            $this->db->commit();
            return $transactionNo;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    private function insertTransaction(
        int $accessoryId,
        string $eventType,
        float $quantity,
        float $before,
        float $after,
        string $recipientType,
        ?int $empId,
        ?string $deptId,
        ?string $reference,
        string $remarks,
        string $username
    ): string {
        $transactionNo = $this->newTransactionNo();
        $this->db->query(
            "INSERT INTO accessory_transaction
             (transaction_no,accessory_id,event_type,quantity,balance_before,balance_after,
              recipient_type,empId,deptId,reference_no,remarks,performed_by)
             VALUES(:transactionNo,:accessoryId,:eventType,:quantity,:beforeBalance,:afterBalance,
                    :recipientType,:empId,:deptId,:referenceNo,:remarks,:performedBy)",
        );
        $this->db->bind("transactionNo", $transactionNo);
        $this->db->bind("accessoryId", $accessoryId, PDO::PARAM_INT);
        $this->db->bind("eventType", $eventType);
        $this->db->bind("quantity", $this->decimal($quantity));
        $this->db->bind("beforeBalance", $this->decimal($before));
        $this->db->bind("afterBalance", $this->decimal($after));
        $this->db->bind("recipientType", $recipientType);
        $this->db->bind("empId", $empId, $empId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $this->db->bind("deptId", $deptId, $deptId === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $this->db->bind("referenceNo", $reference, $reference === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $this->db->bind("remarks", $remarks);
        $this->db->bind("performedBy", $username);
        $this->db->execute();
        return $transactionNo;
    }

    private function newTransactionNo(): string
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $number = "ACT-" . date("Ymd-His") . "-" . random_int(100, 999);
            $this->db->query(
                "SELECT 1 FROM accessory_transaction WHERE transaction_no=:number LIMIT 1",
            );
            $this->db->bind("number", $number);
            if (!$this->db->single()) {
                return $number;
            }
        }
        throw new RuntimeException("Unable to generate an accessory transaction number.");
    }

    private function activeEmployeeExists(int $empId): bool
    {
        $this->db->query("SELECT 1 FROM employee WHERE empId=:empId AND aktif=1 LIMIT 1");
        $this->db->bind("empId", $empId, PDO::PARAM_INT);
        return (bool) $this->db->single();
    }

    private function activeDepartmentExists(string $deptId): bool
    {
        $this->db->query("SELECT 1 FROM department WHERE deptId=:deptId AND active=1 LIMIT 1");
        $this->db->bind("deptId", $deptId);
        return (bool) $this->db->single();
    }

    /** Net quantity currently held by one employee or department. */
    private function recipientCustody(
        int $accessoryId,
        string $recipientType,
        ?int $empId,
        ?string $deptId
    ): float {
        $sql = "SELECT COALESCE(SUM(
                    CASE event_type WHEN 'ISSUE' THEN quantity WHEN 'RETURN' THEN -quantity ELSE 0 END
                ),0) AS custody
                FROM accessory_transaction
                WHERE accessory_id=:accessoryId AND recipient_type=:recipientType";
        if ($recipientType === "EMPLOYEE") {
            $sql .= " AND empId=:empId";
        } else {
            $sql .= " AND deptId=:deptId";
        }
        $this->db->query($sql);
        $this->db->bind("accessoryId", $accessoryId, PDO::PARAM_INT);
        $this->db->bind("recipientType", $recipientType);
        if ($recipientType === "EMPLOYEE") {
            $this->db->bind("empId", $empId, PDO::PARAM_INT);
        } else {
            $this->db->bind("deptId", $deptId);
        }
        $row = $this->db->single();
        return round((float) ($row["custody"] ?? 0), 3);
    }

    private function quantity($value, bool $allowZero): float
    {
        if (!is_numeric($value)) {
            throw new InvalidArgumentException("Quantity must be numeric.");
        }
        $quantity = round((float) $value, 3);
        if (($allowZero && $quantity < 0) || (!$allowZero && $quantity <= 0) || $quantity > 99999999999) {
            throw new InvalidArgumentException(
                $allowZero ? "Quantity cannot be negative." : "Quantity must be greater than zero.",
            );
        }
        return $quantity;
    }

    private function decimal(float $value): string
    {
        return number_format($value, 3, ".", "");
    }

    private function optionalText($value, int $maximum): ?string
    {
        $value = trim((string) $value);
        if (mb_strlen($value) > $maximum) {
            throw new InvalidArgumentException("Reference number is too long.");
        }
        return $value === "" ? null : $value;
    }
}
