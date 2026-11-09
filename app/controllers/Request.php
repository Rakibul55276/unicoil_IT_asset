<?php

class Request extends Controller
{
    private Request_model $requests;
    private array $context;

    public function __construct()
    {
        if (empty($_SESSION["usr"])) {
            header("location:" . BASEURL);
            exit();
        }
        if (defined("WORKFLOW_UI_ENABLED") && !WORKFLOW_UI_ENABLED) {
            http_response_code(404);
            exit("Request workflow is currently disabled.");
        }
        $this->requests = $this->model("Request_model");
        $this->context = $this->requests->getUserContext((string) $_SESSION["usr"]["user"]);
        if (!$this->context) {
            http_response_code(403);
            exit("Active user account required.");
        }
        $_SESSION["usr"]["roles"] = $this->context["roles"] ?? [];
        $_SESSION["usr"]["empId"] = $this->context["empId"] ?? null;
        if (empty($_SESSION["csrf_asset_request"])) {
            $_SESSION["csrf_asset_request"] = bin2hex(random_bytes(32));
        }
    }

    public function index()
    {
        $data = $this->pageData("Equipment Requests", "Request, approve and issue equipment");
        $roles = $this->context["roles"] ?? [];
        $canViewDepartment = in_array("DEPARTMENT_MANAGER", $roles, true) &&
            trim((string) ($this->context["deptId"] ?? "")) !== "";
        $canViewAll = (bool) array_intersect(
            $roles,
            [
                "ADMINISTRATOR",
                "DEPARTMENT_MANAGER",
                "IT_MANAGER",
                "ISSUER",
            ],
        );
        $view = strtolower(trim((string) ($_GET["view"] ?? "")));
        if (!in_array($view, ["all", "mine", "department"], true)) {
            $view = $canViewAll ? "all" : "mine";
        }
        if ($view === "department" && !$canViewDepartment) {
            // A platform/system administrator may carry every role without
            // being linked to one department. In that case, the department
            // scope is meaningless; show the administrator's complete list.
            if ($this->hasRole("ADMINISTRATOR")) {
                $view = "all";
            } else {
                $this->forbidden();
            }
        }

        $requests = $view === "department"
            ? $this->requests->getRequests($this->context, true)
            : $this->requests->getRequests($this->context);
        if ($view === "mine") {
            $username = (string) ($this->context["username"] ?? "");
            $employeeId = (int) ($this->context["empId"] ?? 0);
            $requests = array_values(
                array_filter(
                    $requests,
                    static fn(array $request): bool =>
                        ($request["request_target_type"] ?? "USER") !==
                            "DEPARTMENT" &&
                        (
                            (string) $request["requested_by"] === $username ||
                            ($employeeId > 0 &&
                                (
                                    (int) ($request["requested_for"] ?? 0) ===
                                        $employeeId ||
                                    (int) ($request["receiver_emp_id"] ?? 0) ===
                                        $employeeId
                                ))
                        ),
                ),
            );
        }

        if ((string) ($_GET["pending"] ?? "") === "1") {
            $username = (string) ($this->context["username"] ?? "");
            $employeeId = (int) ($this->context["empId"] ?? 0);
            if ($view === "mine") {
                $requests = array_values(
                    array_filter(
                        $requests,
                        static fn(array $request): bool => in_array(
                            (string) $request["status"],
                            [
                                "PENDING_DEPARTMENT_APPROVAL",
                                "PENDING_IT_APPROVAL",
                            ],
                            true,
                        ),
                    ),
                );
            } else {
                $isIssuer = in_array("ISSUER", $roles, true);
                $requests = array_values(
                    array_filter(
                        $requests,
                        static fn(array $request): bool =>
                            (string) ($request["currentApprover"] ?? "") ===
                                $username ||
                            ($isIssuer &&
                                (string) $request["status"] === "APPROVED" &&
                                (string) $request["requested_by"] !==
                                    $username) ||
                            ((string) $request["status"] ===
                                "PENDING_RECEIVER_ACCEPTANCE" &&
                                $employeeId > 0 &&
                                (int) ($request["receiver_emp_id"] ??
                                    $request["requested_for"] ??
                                    0) ===
                                    $employeeId),
                    ),
                );
            }
        }

        $data["requests"] = $requests;
        $data["requestView"] = $view;
        $data["canViewDepartmentRequests"] = $canViewDepartment;
        $data["canViewAllRequests"] = $canViewAll;
        $data["pendingOnly"] = (string) ($_GET["pending"] ?? "") === "1";
        $data["requestListTitle"] = [
            "mine" => "My Requests",
            "department" => "Department Requests",
            "all" => "Available Requests",
        ][$view];
        // Every active account linked to an employee may submit a personal
        // equipment request, including users who also have the ISSUER role.
        // Operational roles do not remove normal employee access.
        $data["canCreate"] = !empty($this->context["empId"]) ||
            $this->hasAnyRole(["HR_REQUESTER", "IT_MANAGER"]);
        $this->render("request/index", $data);
    }

    /** Administrator/Issuer report for request workflow events only. */
    public function history()
    {
        $this->requireWorkflowUiEnabled();
        $this->requireAnyRole(["ADMINISTRATOR", "ISSUER"]);
        $filters = $this->requestHistoryFilters();
        $rows = $this->requests->getRequestTransactionHistory($filters, 1000);
        $data = $this->pageData(
            "Request Transaction History",
            "Equipment and return request workflow activity",
        );
        $data["requestPage"] = false;
        $data["requestHistoryPage"] = true;
        $data["reportPage"] = true;
        $data["rows"] = $rows;
        $data["filters"] = $filters;
        $data["departments"] = $this->model("Department_model")->getAllDept();
        $data["eventTypes"] = $this->requestHistoryEventTypes();
        $data["statuses"] = $this->requestHistoryStatuses();
        $this->render("request/history", $data);
    }

    public function exportHistoryExcel()
    {
        $this->requireWorkflowUiEnabled();
        $this->requireAnyRole(["ADMINISTRATOR", "ISSUER"]);
        $filters = $this->requestHistoryFilters();
        $rows = $this->requests->getRequestTransactionHistory($filters, 10000);
        header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
        header(
            'Content-Disposition: attachment; filename="request-transaction-history-' .
                $filters["from"] .
                "-to-" .
                $filters["to"] .
                '.xls"',
        );
        echo '<html><head><meta charset="UTF-8"><style>';
        echo 'table{border-collapse:collapse}th{background:#006b57;color:#fff}';
        echo 'th,td{border:1px solid #b8c9c5;padding:7px;vertical-align:top}';
        echo '</style></head><body><h2>Request Transaction History</h2>';
        echo '<p>Period: ' . htmlspecialchars($filters["from"], ENT_QUOTES, "UTF-8") .
            ' to ' . htmlspecialchars($filters["to"], ENT_QUOTES, "UTF-8") . '</p>';
        echo '<table><thead><tr><th>Date</th><th>Request Type</th><th>Reference</th>' .
            '<th>Asset</th><th>Event</th><th>From Status</th><th>To Status</th>' .
            '<th>Requested For</th><th>Requester</th><th>Department</th>' .
            '<th>Actor</th><th>Remarks</th></tr></thead><tbody>';
        foreach ($rows as $row) {
            echo '<tr>';
            foreach (
                [
                    "eventDate",
                    "requestType",
                    "referenceNo",
                    "assetNum",
                    "eventType",
                    "fromStatus",
                    "toStatus",
                    "requestedFor",
                    "requester",
                    "department",
                    "actor",
                    "details",
                ] as $key
            ) {
                $value = (string) ($row[$key] ?? "");
                if (preg_match('/^[=+\-@]/', $value)) {
                    $value = "'" . $value;
                }
                echo '<td>' . htmlspecialchars($value, ENT_QUOTES, "UTF-8") . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table></body></html>';
        exit();
    }

    public function exportHistoryPdf()
    {
        $this->requireWorkflowUiEnabled();
        $this->requireAnyRole(["ADMINISTRATOR", "ISSUER"]);
        $filters = $this->requestHistoryFilters();
        $rows = $this->requests->getRequestTransactionHistory($filters, 1000);
        $pdfRows = array_map(
            static fn(array $row): array => [
                "eventDate" => $row["eventDate"] ?? "-",
                "eventType" => str_replace("_", " ", (string) ($row["eventType"] ?? "-")),
                "assetNum" => $row["referenceNo"] ?? "-",
                "requestType" => $row["requestType"] ?? "-",
                "statusTransition" => trim(
                    (string) ($row["fromStatus"] ?? "") .
                    ((string) ($row["fromStatus"] ?? "") !== "" ? " -> " : "") .
                    (string) ($row["toStatus"] ?? ""),
                ),
                "assetDescription" => $row["requestedFor"] ?? "-",
                "assignedTo" => $row["requester"] ?? "-",
                "department" => $row["department"] ?? "-",
                "details" => $row["details"] ?? "-",
            ],
            $rows,
        );
        $equipmentCount = count(
            array_filter(
                $rows,
                static fn(array $row): bool => ($row["requestType"] ?? "") === "EQUIPMENT",
            ),
        );
        $returnCount = count($rows) - $equipmentCount;
        SimplePdfReport::downloadAudit(
            $pdfRows,
            $filters["from"],
            $filters["to"],
            "request-transaction-history-" . $filters["from"] . "-to-" . $filters["to"] . ".pdf",
            dirname(__DIR__, 2) . "/public/uploads/asset/",
            dirname(__DIR__, 2) . "/public/img/no-image.png",
            "REQUEST TRANSACTION HISTORY",
            "Equipment and return request workflow activity",
            [
                ["TOTAL EVENTS", count($rows), [0.12, 0.38, 0.58]],
                ["EQUIPMENT", $equipmentCount, [0.08, 0.48, 0.38]],
                ["RETURN REQUESTS", $returnCount, [0.88, 0.55, 0.12]],
            ],
        );
    }

    public function create()
    {
        $data = $this->pageData("New Equipment Request", "Request equipment without technical details");
        $data["categories"] = $this->requests->getCategories();
        $data["canRequestForOthers"] = $this->hasRole("HR_REQUESTER");
        $data["employees"] = $data["canRequestForOthers"]
            ? $this->requests->getActiveEmployees()
            : array_values(array_filter($this->requests->getActiveEmployees(), fn(array $employee): bool =>
                (int) $employee["empId"] === (int) ($this->context["empId"] ?? 0)));
        $departments = $this->requests->getActiveDepartments();
        $data["canRequestDepartment"] = $this->hasRole("DEPARTMENT_MANAGER");
        $isAdministrator = in_array(
            "ADMINISTRATOR",
            $this->context["roles"] ?? [],
            true,
        );
        $data["departments"] = $isAdministrator
            ? $departments
            : ($data["canRequestDepartment"]
                ? array_values(array_filter($departments, fn(array $department): bool =>
                    (string) $department["deptId"] === (string) ($this->context["deptId"] ?? "")))
                : []);
        $this->render("request/create", $data);
    }

    public function store()
    {
        $this->requirePost();
        $this->verifyCsrf();
        try {
            $requestId = $this->requests->createRequest($_POST, $this->context);
            Flasher::setMessage("Request created.", "The approval route and next approver are shown in the audit trail.", "success");
            $this->redirect("/Request/details/" . $requestId);
        } catch (Throwable $error) {
            Flasher::setMessage("Request not created.", $error->getMessage(), "danger");
            $this->redirect("/Request/create");
        }
    }

    public function details($id)
    {
        $request = $this->loadVisibleRequest((int) $id);
        $data = $this->pageData($request["request_no"], "Equipment request details and approvals");
        $data["request"] = $request;
        $data["canDepartmentApprove"] = $request["status"] === "PENDING_DEPARTMENT_APPROVAL" &&
            $this->canApproveDepartment($request);
        $data["canItApprove"] =
            $request["status"] === "PENDING_IT_APPROVAL" &&
            $this->requests->isCurrentApprover(
                (int) $request["request_id"],
                $this->context["username"],
                "IT_MANAGER",
            );
        $data["canAdminManageApproval"] = $this->hasRole("ADMINISTRATOR") &&
            empty($request["allocations"]) &&
            !in_array($request["status"], ["PENDING_RECEIVER_ACCEPTANCE", "COMPLETED", "CANCELLED"], true);
        $cancelableStatuses = [
            "PENDING_DEPARTMENT_APPROVAL",
            "PENDING_IT_APPROVAL",
            "APPROVED",
        ];
        $isAdministrator = $this->hasRole("ADMINISTRATOR");
        $allocationCount = count($request["allocations"] ?? []);

        // Keep the cancellation control visible to administrators while a
        // request is still in the approval/issuance queue. Once an asset has
        // actually been issued, cancellation remains blocked to protect the
        // physical assignment and its audit trail.
        $data["showCancelRequestControl"] = $isAdministrator && in_array(
            $request["status"],
            $cancelableStatuses,
            true,
        );
        $data["canCancelRequest"] =
            $data["showCancelRequestControl"] && $allocationCount === 0;
        $data["cancelRequestBlockedReason"] = $allocationCount > 0
            ? $allocationCount . " asset(s) have already been issued from this request. " .
                "A request with a physical issue transaction cannot be cancelled."
            : "";
        // Asset allocation is an Issuer duty, not an approval action.
        // Administrator access alone does not grant issuance permission.
        // The allocator must not be the requester or the intended receiver.
        $receiverEmpId = (int) ($request["receiver_emp_id"] ??
            ($request["request_target_type"] === "DEPARTMENT"
                ? ($request["headEmpId"] ?? 0)
                : ($request["requested_for"] ?? 0)));
        $remainingApprovedAssets = array_sum(array_map(
            static fn(array $item): int => max(
                0,
                (int) ($item["approved_quantity"] ?? 0) - (int) ($item["allocatedCount"] ?? 0),
            ),
            $request["items"] ?? [],
        ));
        $data["canIssue"] = $remainingApprovedAssets > 0 &&
            $request["status"] === "APPROVED" &&
            in_array("ISSUER", $this->context["roles"] ?? [], true) &&
            (string) $request["requested_by"] !== (string) $this->context["username"] &&
            ($receiverEmpId <= 0 || $receiverEmpId !== (int) ($this->context["empId"] ?? 0));
        $data["canReject"] = in_array(
            $request["status"],
            [
                "PENDING_DEPARTMENT_APPROVAL",
                "PENDING_IT_APPROVAL",
            ],
            true,
        ) && (
            $data["canDepartmentApprove"] ||
            $data["canItApprove"]
        );
        $isReceiver = $request["request_target_type"] === "DEPARTMENT"
            ? (int) ($this->context["empId"] ?? 0) ===
                (int) ($request["receiver_emp_id"] ?? $request["headEmpId"] ?? 0)
            : (int) ($this->context["empId"] ?? 0) === (int) $request["requested_for"];
        $data["canAccept"] = $request["status"] === "PENDING_RECEIVER_ACCEPTANCE" && $isReceiver;
        $data["canEdit"] =
            $request["status"] === "PENDING_DEPARTMENT_APPROVAL" &&
            (string) $request["requested_by"] ===
                (string) $this->context["username"];
        // Issuer asset selectors use remote search; never load all in-stock assets.
        $data["availableAssets"] = [];
        $this->render("request/view", $data);
    }

    public function searchAvailableAssets($requestId, $requestItemId)
    {
        header("Content-Type: application/json; charset=utf-8");
        $request = $this->loadVisibleRequest((int) $requestId);
        $receiverEmpId = (int) ($request["receiver_emp_id"] ??
            ($request["request_target_type"] === "DEPARTMENT"
                ? ($request["headEmpId"] ?? 0)
                : ($request["requested_for"] ?? 0)));
        $canIssue = $request["status"] === "APPROVED" &&
            $this->hasRole("ISSUER") &&
            (string) $request["requested_by"] !== (string) $this->context["username"] &&
            ($receiverEmpId <= 0 ||
                $receiverEmpId !== (int) ($this->context["empId"] ?? 0));
        if (!$canIssue) {
            http_response_code(403);
            echo json_encode(["results" => []]);
            return;
        }
        $rows = $this->requests->searchAvailableAssetsForItem(
            (int) $requestId,
            (int) $requestItemId,
            (string) ($_GET["q"] ?? ""),
            30,
        );
        $results = array_map(static function (array $asset): array {
            $serial = trim((string) ($asset["serial_number"] ?? ""));
            return [
                "id" => (string) $asset["assetNum"],
                "text" => $asset["assetNum"] .
                    " - " .
                    $asset["Description"] .
                    ($serial !== "" ? " - " . $serial : ""),
            ];
        }, $rows);
        echo json_encode(["results" => $results], JSON_UNESCAPED_UNICODE);
    }

    public function edit($id)
    {
        $request = $this->loadVisibleRequest((int) $id);
        $this->requireEditableByRequester($request);

        $data = $this->pageData(
            "Edit " . $request["request_no"],
            "Update requested equipment before approval",
        );
        $data["request"] = $request;
        $data["categories"] = $this->requests->getCategories();
        $this->render("request/edit", $data);
    }

    public function update($id)
    {
        $this->requirePost();
        $this->verifyCsrf();
        $request = $this->loadVisibleRequest((int) $id);
        $this->requireEditableByRequester($request);

        try {
            $this->requests->updateRequestedItems(
                (int) $id,
                $this->context["username"],
                $_POST,
            );
            Flasher::setMessage(
                "Request updated.",
                "The requested equipment list was saved.",
                "success",
            );
        } catch (Throwable $error) {
            Flasher::setMessage(
                "Request not updated.",
                $error->getMessage(),
                "danger",
            );
        }
        $this->redirect("/Request/details/" . (int) $id);
    }

    public function approveDepartment($id)
    {
        $this->requirePost();
        $this->verifyCsrf();
        $request = $this->loadVisibleRequest((int) $id);
        if (!$this->canApproveDepartment($request)) {
            $this->forbidden();
        }
        try {
            $this->requests->approveDepartment((int) $id, $this->context["username"],
                trim((string) ($_POST["remarks"] ?? "")) ?: null);
            Flasher::setMessage("Approval recorded.", "The request moved to its next configured step.", "success");
        } catch (Throwable $error) {
            Flasher::setMessage("Approval failed.", $error->getMessage(), "danger");
        }
        $this->redirect("/Request/details/" . (int) $id);
    }

    public function approveIt($id)
    {
        $this->requirePost();
        $this->verifyCsrf();
        $request = $this->loadVisibleRequest((int) $id);
        if (
            !$this->requests->isCurrentApprover(
                (int) $id,
                $this->context["username"],
                "IT_MANAGER",
            )
        ) {
            $this->forbidden();
        }
        try {
            $this->requests->approveIt((int) $id, $this->context["username"], $_POST);
            Flasher::setMessage("IT approved.", "The request is now waiting for the Issuer to issue assets.", "success");
        } catch (Throwable $error) {
            Flasher::setMessage("Approval failed.", $error->getMessage(), "danger");
        }
        $this->redirect("/Request/details/" . (int) $id);
    }


    public function adminChangeApprovalStatus($id)
    {
        $this->requirePost();
        $this->verifyCsrf();
        $this->requireRole("ADMINISTRATOR");
        $request = $this->loadVisibleRequest((int) $id);
        try {
            $this->requests->adminChangeApprovalStatus(
                (int) $id,
                $this->context["username"],
                (string) ($_POST["target_status"] ?? ""),
                (string) ($_POST["reason"] ?? ""),
            );
            Flasher::setMessage("Approval status updated.", "The Administrator override was recorded in the audit trail.", "success");
        } catch (Throwable $error) {
            Flasher::setMessage("Status update failed.", $error->getMessage(), "danger");
        }
        $this->redirect("/Request/details/" . (int) $id);
    }

    public function cancel($id)
    {
        $this->requirePost();
        $this->verifyCsrf();
        $this->requireRole("ADMINISTRATOR");
        $this->loadVisibleRequest((int) $id);
        try {
            $this->requests->cancelRequest(
                (int) $id,
                (string) $this->context["username"],
                (string) ($_POST["reason"] ?? ""),
            );
            Flasher::setMessage(
                "Request cancelled.",
                "The request remains available in transaction history.",
                "warning",
            );
        } catch (Throwable $error) {
            Flasher::setMessage("Cancellation failed.", $error->getMessage(), "danger");
        }
        $this->redirect("/Request/details/" . (int) $id);
    }

    public function reject($id)
    {
        $this->requirePost();
        $this->verifyCsrf();
        $request = $this->loadVisibleRequest((int) $id);
        $allowed = ($request["status"] === "PENDING_DEPARTMENT_APPROVAL" && $this->canApproveDepartment($request)) ||
            ($request["status"] === "PENDING_IT_APPROVAL" &&
                $this->requests->isCurrentApprover(
                    (int) $request["request_id"],
                    $this->context["username"],
                    "IT_MANAGER",
                ));
        if (!$allowed) {
            $this->forbidden();
        }
        try {
            $this->requests->reject((int) $id, $this->context["username"], (string) ($_POST["reason"] ?? ""));
            Flasher::setMessage("Request rejected.", "The reason is recorded in the audit trail.", "warning");
        } catch (Throwable $error) {
            Flasher::setMessage("Rejection failed.", $error->getMessage(), "danger");
        }
        $this->redirect("/Request/details/" . (int) $id);
    }

    public function issue($id)
    {
        $this->requirePost();
        $this->verifyCsrf();
        if (!in_array("ISSUER", $this->context["roles"] ?? [], true)) {
            $this->forbidden();
        }
        $request = $this->loadVisibleRequest((int) $id);
        if ($request["status"] !== "APPROVED") {
            Flasher::setMessage("Issue failed.", "This request is not ready for issuance.", "danger");
            $this->redirect("/Request/details/" . (int) $id);
        }
        $receiverEmpId = (int) ($request["receiver_emp_id"] ??
            ($request["request_target_type"] === "DEPARTMENT"
                ? ($request["headEmpId"] ?? 0)
                : ($request["requested_for"] ?? 0)));
        if (
            (string) $request["requested_by"] === (string) $this->context["username"] ||
            ($receiverEmpId > 0 && $receiverEmpId === (int) ($this->context["empId"] ?? 0))
        ) {
            Flasher::setMessage(
                "Issue failed.",
                "You cannot assign assets for a request created for yourself.",
                "danger",
            );
            $this->redirect("/Request/details/" . (int) $id);
        }
        try {
            $itemAssets = $this->validateAllocation(
                $request,
                $_POST["item_assets"] ?? [],
            );
            $assetNums = [];
            foreach ($itemAssets as $assets) {
                foreach ((array) $assets as $assetNum) {
                    $assetNums[] = trim((string) $assetNum);
                }
            }
            $conditions = [];
            foreach ($assetNums as $assetNum) {
                $conditions[$assetNum] = strtoupper(trim((string) ($_POST["condition_out"][$assetNum] ?? "GOOD")));
            }
            $issuerRemarks = trim((string) ($_POST["remarks"] ?? ""));
            $assignmentData = [
                "assigneeType" => $request["request_target_type"],
                "empId" => $request["requested_for"],
                "deptId" => $request["deptId"],
                "departmentHeadId" =>
                    $request["receiver_emp_id"] ?? $request["headEmpId"],
                "assetNums" => $assetNums,
                "issueDate" => date("Y-m-d H:i:s"),
                "expectedReturnDate" => "",
                "conditionOut" => $conditions,
                "remarks" => $issuerRemarks,
                "requestAllocation" => [
                    "requestId" => (int) $id,
                    "actor" => (string) $this->context["username"],
                    "itemAssets" => $itemAssets,
                ],
            ];
            $assignment = $this->model("Asset_model")->assignAsset($assignmentData);
            Flasher::setMessage(
                "Assets issued.",
                "All approved assets are issued; receiver acceptance is now required.",
                "success",
            );
        } catch (Throwable $error) {
            Flasher::setMessage("Issue failed.", $error->getMessage(), "danger");
        }
        $this->redirect("/Request/details/" . (int) $id);
    }

    public function accept($id)
    {
        $this->requirePost();
        $this->verifyCsrf();
        $request = $this->loadVisibleRequest((int) $id);
        $isReceiver = $request["request_target_type"] === "DEPARTMENT"
            ? (int) ($this->context["empId"] ?? 0) ===
                (int) ($request["receiver_emp_id"] ?? $request["headEmpId"] ?? 0)
            : (int) ($this->context["empId"] ?? 0) === (int) $request["requested_for"];
        if (!$isReceiver) {
            $this->forbidden();
        }
        try {
            $this->requests->accept((int) $id, $this->context["username"], (string) ($_POST["comment"] ?? ""));
            Flasher::setMessage("Receipt accepted.", "The equipment request is complete.", "success");
        } catch (Throwable $error) {
            Flasher::setMessage("Acceptance failed.", $error->getMessage(), "danger");
        }
        $this->redirect("/Request/details/" . (int) $id);
    }

    private function validateAllocation(array $request, array $itemAssets): array
    {
        $seen = [];
        $normalized = [];
        $validItemIds = [];
        foreach ($request["items"] as $item) {
            $itemId = (int) $item["request_item_id"];
            $validItemIds[$itemId] = true;
            $approved = (int) ($item["approved_quantity"] ?? 0);
            $allocated = (int) ($item["allocatedCount"] ?? 0);
            $remaining = max(0, $approved - $allocated);
            if ($remaining < 1) {
                continue;
            }
            $selected = array_values(array_unique(array_filter(array_map(
                static fn($assetNum): string => trim((string) $assetNum),
                (array) ($itemAssets[$itemId] ?? []),
            ))));
            if (count($selected) !== $remaining) {
                throw new InvalidArgumentException(
                    "Select all $remaining approved asset(s) for " .
                    $item["categoryName"] . ". Issuance must be completed in one submission.",
                );
            }
            foreach ($selected as $assetNum) {
                if (
                    isset($seen[$assetNum]) ||
                    !$this->requests->isAssetAvailableForRequestItem(
                        (int) $request["request_id"],
                        $itemId,
                        $assetNum,
                    )
                ) {
                    throw new InvalidArgumentException("One selected asset is unavailable or belongs to another category.");
                }
                $seen[$assetNum] = true;
            }
            if ($selected) {
                $normalized[$itemId] = $selected;
            }
        }
        foreach (array_keys($itemAssets) as $itemId) {
            if (!isset($validItemIds[(int) $itemId])) {
                throw new InvalidArgumentException(
                    "One selected request item does not belong to this request.",
                );
            }
        }
        if (!$seen) {
            throw new InvalidArgumentException("Select every approved asset before issuing.");
        }
        if (count($seen) > 8) {
            throw new InvalidArgumentException(
                "One request can issue a maximum of 8 approved assets.",
            );
        }
        return $normalized;
    }

    private function loadVisibleRequest(int $requestId): array
    {
        $request = $this->requests->getRequest($requestId);
        if (!$request) {
            http_response_code(404);
            exit("Request not found.");
        }

        $roles = $this->context["roles"] ?? [];

        // Administrators may inspect every request. Issuers must be able to open
        // an approved request even though the approval route has finished and
        // therefore no WAITING approver row exists anymore. The separate
        // canIssue check still blocks assigning assets to the allocator's own
        // request or to themselves as receiver.
        $isIssuer = in_array("ISSUER", $roles, true);
        $isApprovedForIssuance =
            $isIssuer &&
            (string) ($request["status"] ?? "") === "APPROVED";
        $wasIssuedByCurrentUser =
            $isIssuer &&
            (string) ($request["issued_by"] ?? "") !== "" &&
            strcasecmp(
                (string) $request["issued_by"],
                (string) ($this->context["username"] ?? ""),
            ) === 0;
        $isOwnDepartmentRequest =
            in_array("DEPARTMENT_MANAGER", $roles, true) &&
            (string) ($request["request_target_type"] ?? "") === "DEPARTMENT" &&
            (string) ($request["deptId"] ?? "") ===
                (string) ($this->context["deptId"] ?? "");
        if (
            in_array("ADMINISTRATOR", $roles, true) ||
            $isApprovedForIssuance ||
            $wasIssuedByCurrentUser ||
            $isOwnDepartmentRequest
        ) {
            return $request;
        }

        $visible = array_column($this->requests->getRequests($this->context), "request_id");
        if (!in_array($requestId, array_map("intval", $visible), true)) {
            $this->forbidden();
        }
        return $request;
    }

    private function canApproveDepartment(array $request): bool
    {
        return $this->requests->isCurrentApprover(
            (int) $request["request_id"],
            $this->context["username"],
            "MANAGER",
        );
    }

    private function requireEditableByRequester(array $request): void
    {
        if (
            $request["status"] !== "PENDING_DEPARTMENT_APPROVAL" ||
            (string) $request["requested_by"] !==
                (string) $this->context["username"]
        ) {
            http_response_code(403);
            exit(
                "Only the requester can edit this request before the first approval."
            );
        }
    }

    private function requestHistoryFilters(): array
    {
        $defaultFrom = date("Y-m-01");
        $defaultTo = date("Y-m-d");
        $from = trim((string) ($_GET["from"] ?? $defaultFrom));
        $to = trim((string) ($_GET["to"] ?? $defaultTo));
        if (
            !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) ||
            !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) ||
            $from > $to
        ) {
            $from = $defaultFrom;
            $to = $defaultTo;
        }
        $requestType = strtoupper(trim((string) ($_GET["requestType"] ?? "")));
        if (!in_array($requestType, ["", "EQUIPMENT", "RETURN"], true)) {
            $requestType = "";
        }
        $eventType = strtoupper(trim((string) ($_GET["eventType"] ?? "")));
        if ($eventType !== "" && !isset($this->requestHistoryEventTypes()[$eventType])) {
            $eventType = "";
        }
        $status = strtoupper(trim((string) ($_GET["status"] ?? "")));
        if ($status !== "" && !isset($this->requestHistoryStatuses()[$status])) {
            $status = "";
        }

        return [
            "from" => $from,
            "to" => $to,
            "requestType" => $requestType,
            "eventType" => $eventType,
            "status" => $status,
            "department" => substr(trim((string) ($_GET["department"] ?? "")), 0, 10),
            "actor" => substr(trim((string) ($_GET["actor"] ?? "")), 0, 100),
            "search" => substr(trim((string) ($_GET["search"] ?? "")), 0, 150),
        ];
    }

    private function requestHistoryEventTypes(): array
    {
        return [
            "CREATED" => "Equipment Request Created",
            "REQUEST_UPDATED" => "Equipment Request Updated",
            "MANAGER_APPROVED" => "Department Manager Approved",
            "DEPARTMENT_APPROVED" => "Department Manager Approved",
            "IT_APPROVED" => "IT Manager Approved",
            "ADMIN_STATUS_OVERRIDE" => "Administrator Status Override",
            "REJECTED" => "Equipment Request Rejected",
            "ADMIN_CANCELLED" => "Equipment Request Cancelled",
            "ISSUED" => "Equipment Request Issued",
            "RECEIVER_ACCEPTED" => "Receiver Accepted Request",
            "RETURN_REQUEST_CREATED" => "Return Request Created",
            "RETURN_REQUEST_COMPLETED" => "Return Request Completed",
            "RETURN_REQUEST_CANCELLED" => "Return Request Cancelled",
        ];
    }

    private function requestHistoryStatuses(): array
    {
        return [
            "PENDING_DEPARTMENT_APPROVAL" => "Pending Department Approval",
            "PENDING_IT_APPROVAL" => "Pending IT Approval",
            "APPROVED" => "Waiting for Issuer",
            "PENDING_RECEIVER_ACCEPTANCE" => "Pending Receiver Acceptance",
            "PENDING" => "Pending Return Request",
            "COMPLETED" => "Completed",
            "REJECTED" => "Rejected",
            "CANCELLED" => "Cancelled",
        ];
    }

    private function pageData(string $menu, string $description): array
    {
        return [
            "title" => "IT Asset - " . $menu,
            "menu" => $menu,
            "menu-dsc" => $description,
            "requestPage" => true,
            "requestContext" => $this->context,
            "csrf" => $_SESSION["csrf_asset_request"],
        ];
    }

    private function render(string $view, array $data): void
    {
        $this->view("templates/header_a", $data);
        $this->view($view, $data);
        $this->view("templates/footer_a");
    }

    private function hasRole(string $role): bool
    {
        $roles = $this->context["roles"] ?? [];
        return in_array("ADMINISTRATOR", $roles, true) || in_array($role, $roles, true);
    }

    private function hasAnyRole(array $roles): bool
    {
        $assignedRoles = $this->context["roles"] ?? [];
        return in_array("ADMINISTRATOR", $assignedRoles, true) ||
            (bool) array_intersect($roles, $assignedRoles);
    }

    private function requireRole(string $role): void
    {
        if (!$this->hasRole($role)) {
            $this->forbidden();
        }
    }

    private function requireAnyRole(array $roles): void
    {
        if (!$this->hasAnyRole($roles)) {
            $this->forbidden();
        }
    }

    private function requireWorkflowUiEnabled(): void
    {
        if (defined("WORKFLOW_UI_ENABLED") && !WORKFLOW_UI_ENABLED) {
            http_response_code(404);
            exit("Request workflow reporting is currently disabled.");
        }
    }

    private function requirePost(): void
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            exit("Method not allowed.");
        }
    }

    private function verifyCsrf(): void
    {
        $token = (string) ($_POST["csrf_token"] ?? "");
        if (!hash_equals((string) ($_SESSION["csrf_asset_request"] ?? ""), $token)) {
            http_response_code(419);
            exit("Invalid form token. Refresh and try again.");
        }
    }

    private function redirect(string $path): void
    {
        header("location: " . BASEURL . $path);
        exit();
    }

    private function forbidden(): void
    {
        http_response_code(403);
        exit("You do not have permission for this request action.");
    }
}
