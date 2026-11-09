<?php

class Asset extends Controller
{
    public function __construct()
    {
        if (!isset($_SESSION["usr"])) {
            header("location:" . BASEURL);
            exit();
        }
    }

    /** Only Issuer and Administrator may receive/process physical returns. */
    private function canProcessReturns(): bool
    {
        $username = strtolower(trim((string) ($_SESSION["usr"]["user"] ?? "")));
        if ($username === "admin") {
            return true;
        }
        $account = $this->model("User_model")->getUserById(
            (string) ($_SESSION["usr"]["user"] ?? ""),
        );
        $roles = array_values(array_filter($account["roles"] ?? []));
        return (bool) array_intersect($roles, ["ADMINISTRATOR", "ISSUER"]);
    }

    private function requireReturnProcessor(): void
    {
        if (!$this->canProcessReturns()) {
            http_response_code(403);
            exit("Only an Issuer or Administrator can process asset return requests.");
        }
    }

    /** Request cancellation is intentionally narrower than return processing. */
    private function canAdministerRequests(): bool
    {
        $username = strtolower(trim((string) ($_SESSION["usr"]["user"] ?? "")));
        if ($username === "admin") {
            return true;
        }
        $account = $this->model("User_model")->getUserById(
            (string) ($_SESSION["usr"]["user"] ?? ""),
        );
        return in_array("ADMINISTRATOR", $account["roles"] ?? [], true);
    }

    public function getAllDepartments()
    {
        $stmt = $this->db->prepare("SELECT deptId, deptName FROM department ORDER BY deptName ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function myAssets()
    {
        if (empty($_SESSION["csrf_my_return_request"])) {
            $_SESSION["csrf_my_return_request"] = bin2hex(random_bytes(32));
        }
        $account = $this->model("User_model")->getUserById(
            (string) ($_SESSION["usr"]["user"] ?? ""),
        );
        $employeeId = (int) ($account["empId"] ?? 0);
        $canViewDepartmentAssets = in_array(
            "DEPARTMENT_MANAGER",
            $account["roles"] ?? [],
            true,
        );
        $assetScope = strtolower(trim((string) ($_GET["scope"] ?? "mine")));
        if (!in_array($assetScope, ["mine", "department"], true)) {
            $assetScope = "mine";
        }
        if ($assetScope === "department" && !$canViewDepartmentAssets) {
            http_response_code(403);
            exit("Only a Department Manager can view department assets.");
        }
        $data = [
            "title" => "IT Asset - My Assigned Assets",
            "menu" => "My Assigned Assets",
            "menu-dsc" => "Current personal and department asset custody",
            "reportPage" => true,
            "myAssetsPage" => true,
            "employeeId" => $employeeId,
            "assetScope" => $assetScope,
            "canViewDepartmentAssets" => $canViewDepartmentAssets,
            "assets" => $this->model("Asset_model")->getMyAssignedAssets(
                $employeeId,
                $assetScope === "department",
            ),
            "returnRequestCsrf" => $_SESSION["csrf_my_return_request"],
        ];
        $this->view("templates/header_a", $data);
        $this->view("asset/my-assets", $data);
        $this->view("templates/footer_a");
    }

    /** Separate self-service page for the user's full custody history. */
    public function myAssetHistory()
    {
        $account = $this->model("User_model")->getUserById(
            (string) ($_SESSION["usr"]["user"] ?? ""),
        );
        $employeeId = (int) ($account["empId"] ?? 0);
        $canViewDepartmentAssets = in_array(
            "DEPARTMENT_MANAGER",
            $account["roles"] ?? [],
            true,
        );
        $assetScope = strtolower(trim((string) ($_GET["scope"] ?? "mine")));
        if (!in_array($assetScope, ["mine", "department"], true)) {
            $assetScope = "mine";
        }
        if ($assetScope === "department" && !$canViewDepartmentAssets) {
            http_response_code(403);
            exit("Only a Department Manager can view department asset history.");
        }
        $data = [
            "title" => "IT Asset - My Asset History",
            "menu" => "My Asset History",
            "menu-dsc" => "Personal and department assignment and return history",
            "reportPage" => true,
            "myAssetHistoryPage" => true,
            "historyOnly" => true,
            "employeeId" => $employeeId,
            "assetScope" => $assetScope,
            "canViewDepartmentAssets" => $canViewDepartmentAssets,
            "history" => $this->model("Asset_model")->getMyAssetHistory(
                $employeeId,
                $assetScope === "department",
            ),
        ];
        $this->view("templates/header_a", $data);
        $this->view("asset/my-assets", $data);
        $this->view("templates/footer_a");
    }

    public function requestReturn()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            exit("Method not allowed");
        }
        if (
            empty($_SESSION["csrf_my_return_request"]) ||
            !hash_equals(
                $_SESSION["csrf_my_return_request"],
                (string) ($_POST["csrf_token"] ?? ""),
            )
        ) {
            Flasher::setMessage("Return request failed.", "Invalid form token.", "danger");
            header("location: " . BASEURL . "/Asset/myAssets");
            exit();
        }
        $account = $this->model("User_model")->getUserById(
            (string) ($_SESSION["usr"]["user"] ?? ""),
        );
        $returnScope =
            (string) ($_POST["return_scope"] ?? "") === "department" &&
            in_array(
                "DEPARTMENT_MANAGER",
                $account["roles"] ?? [],
                true,
            )
                ? "department"
                : "mine";
        try {
            $issueIds = $_POST["issue_ids"] ?? [];
            if (!is_array($issueIds) || !$issueIds) {
                $issueIds = [(int) ($_POST["issue_id"] ?? 0)];
            }
            $result = $this->model("Asset_model")->createReturnRequests(
                $issueIds,
                (int) ($account["empId"] ?? 0),
                (string) $_SESSION["usr"]["user"],
                (string) ($_POST["reason"] ?? ""),
            );
            Flasher::setMessage(
                "Return request " . $result["batchReference"],
                (int) $result["count"] . " asset(s) sent to the Issuer for processing.",
                "success",
            );
        } catch (Throwable $error) {
            Flasher::setMessage("Return request failed.", $error->getMessage(), "danger");
        }
        header(
            "location: " .
                BASEURL .
                "/Asset/myAssets?scope=" .
                rawurlencode($returnScope),
        );
        exit();
    }

    public function returnRequests()
    {
        $this->requireReturnProcessor();
        if (empty($_SESSION["csrf_asset_return"])) {
            $_SESSION["csrf_asset_return"] = bin2hex(random_bytes(32));
        }
        $data = [
            "title" => "IT Asset - Return Requests",
            "menu" => "Return Requests",
            "menu-dsc" => "Employee requests waiting for Issuer processing",
            "returnRequestsPage" => true,
            "requests" => $this->model("Asset_model")->getReturnRequests(),
            "csrf" => $_SESSION["csrf_asset_return"],
            "canCancelReturnRequests" => $this->canAdministerRequests(),
        ];
        $this->view("templates/header_a", $data);
        $this->view("asset/return-requests", $data);
        $this->view("templates/footer_a");
    }

    public function cancelReturnRequest($returnRequestId)
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            exit("Method not allowed.");
        }
        if (!$this->canAdministerRequests()) {
            http_response_code(403);
            exit("Only an Administrator can cancel a return request.");
        }
        if (
            empty($_SESSION["csrf_asset_return"]) ||
            !hash_equals(
                (string) $_SESSION["csrf_asset_return"],
                (string) ($_POST["csrf_token"] ?? ""),
            )
        ) {
            http_response_code(419);
            exit("Invalid form token. Refresh and try again.");
        }
        try {
            $result = $this->model("Asset_model")->cancelReturnRequestBatch(
                (int) $returnRequestId,
                (string) ($_SESSION["usr"]["user"] ?? ""),
                (string) ($_POST["reason"] ?? ""),
            );
            Flasher::setMessage(
                "Return request cancelled.",
                $result["reference"] . " remains available in request history.",
                "warning",
            );
        } catch (Throwable $error) {
            Flasher::setMessage("Cancellation failed.", $error->getMessage(), "danger");
        }
        header("location: " . BASEURL . "/Asset/returnRequests");
        exit();
    }

    public function downloadReturnRequestPdf($returnRequestId)
    {
        $model = $this->model("Asset_model");
        $requestIds = $model->getReturnRequestDocumentIds((int) $returnRequestId);
        if (!$requestIds) {
            http_response_code(404);
            echo "Return request not found";
            return;
        }
        if (count($requestIds) > 8) {
            http_response_code(422);
            echo "A maximum of 8 assets can be included in one return-request PDF.";
            return;
        }

        $records = [];
        foreach ($requestIds as $requestId) {
            $record = $model->getReturnRequestForm($requestId);
            if ($record) {
                $records[] = $record;
            }
        }
        if (!$records) {
            http_response_code(404);
            echo "Return request not found";
            return;
        }
        $account = $this->model("User_model")->getUserById(
            (string) ($_SESSION["usr"]["user"] ?? ""),
        );
        $roles = $account["roles"] ?? [];
        $username = (string) ($_SESSION["usr"]["user"] ?? "");
        $privileged = strtolower($username) === "admin" ||
            (bool) array_intersect($roles, ["ADMINISTRATOR", "ISSUER"]);
        foreach ($records as $record) {
            if (!$privileged && $record["requested_by"] !== $username) {
                http_response_code(403);
                echo "You do not have permission to download this return form.";
                return;
            }
        }

        $record = $records[0];
        $record["assets"] = $records;
        $batchReference = preg_replace(
            '/-\d{2}$/',
            '',
            (string) ($record["return_request_no"] ?? $record["return_no"]),
        );
        if (count($records) > 1) {
            $record["return_request_no"] = $batchReference;
            $completedBatchNumbers = array_values(
                array_unique(
                    array_filter(
                        array_map(
                            static fn(array $item): string => trim(
                                (string) ($item["batch_no"] ?? ""),
                            ),
                            $records,
                        ),
                    ),
                ),
            );
            $combinedDocumentNumber =
                count($completedBatchNumbers) === 1
                    ? $completedBatchNumbers[0]
                    : $batchReference;
            $record["actual_return_no"] = $combinedDocumentNumber;
            $record["return_no"] = $combinedDocumentNumber;
        }
        SimplePdfReport::downloadReturn(
            $record,
            "asset-return-request-" .
                preg_replace("/[^A-Za-z0-9_-]/", "_", $batchReference) .
                ".pdf",
            dirname(__DIR__, 2) . "/public/img/unicoil-logo.jpg",
        );
    }

    /**
     * Serve asset images only to authenticated users after confirming that the
     * filename is referenced by the active tenant database.
     */
    public function media($fileName)
    {
        $this->requireAssetRole(
            ["ISSUER", "IT_MANAGER"],
            "You do not have permission to view asset media.",
        );
        $fileName = $this->validatedStoredFileName((string) $fileName, [
            "jpg",
            "jpeg",
            "png",
            "webp",
        ]);
        if (
            $fileName === null ||
            !$this->model("Asset_model")->getAssetMediaReference($fileName)
        ) {
            $this->fileNotFound();
        }

        $this->streamPrivateFile(
            dirname(__DIR__, 2) . "/public/uploads/asset/" . $fileName,
            $fileName,
            false,
        );
    }

    /** Archived asset images use a separate protected storage directory. */
    public function archivedMedia($fileName)
    {
        $this->requireAssetRole(
            ["ISSUER", "IT_MANAGER"],
            "You do not have permission to view archived asset media.",
        );
        $fileName = $this->validatedStoredFileName((string) $fileName, [
            "jpg",
            "jpeg",
            "png",
            "webp",
        ]);
        if (
            $fileName === null ||
            !$this->model("Asset_model")->getArchivedMediaReference($fileName)
        ) {
            $this->fileNotFound();
        }

        $this->streamPrivateFile(
            dirname(__DIR__, 2) . "/public/uploads/archive/" . $fileName,
            $fileName,
            false,
        );
    }

    /** Invoice PDFs are confidential receipt evidence, never public files. */
    public function downloadInvoice($fileName)
    {
        $this->requireAssetRole(
            ["ISSUER", "IT_MANAGER"],
            "You do not have permission to download invoice documents.",
        );
        $fileName = $this->validatedStoredFileName((string) $fileName, ["pdf"]);
        $invoice =
            $fileName === null
                ? null
                : $this->model("Asset_model")->getInvoiceReference($fileName);
        if (!$invoice) {
            $this->fileNotFound();
        }

        $downloadName =
            trim((string) ($invoice["invoice_number"] ?? "")) !== ""
                ? (string) $invoice["invoice_number"]
                : pathinfo($fileName, PATHINFO_FILENAME);
        $downloadName =
            preg_replace("/[^A-Za-z0-9_-]+/", "_", $downloadName) . ".pdf";

        $this->streamPrivateFile(
            dirname(__DIR__, 2) . "/public/uploads/invoices/" . $fileName,
            $downloadName,
            true,
        );
    }

    public function deleteMedia()
    {
        $this->requireAssetRole(
            ["ADMINISTRATOR"],
            "Only an Administrator can delete asset media.",
            true,
        );
        header("Content-Type: application/json");

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);

            echo json_encode([
                "success" => false,
                "message" => "Invalid request method.",
            ]);

            return;
        }

        $token = $_POST["csrf_token"] ?? "";
        if (
            empty($_SESSION["csrf_media_delete"]) ||
            !hash_equals($_SESSION["csrf_media_delete"], $token)
        ) {
            http_response_code(403);
            echo json_encode(["success" => false, "message" => "Invalid image deletion token."]);
            return;
        }

        $mediaId = isset($_POST["media_id"]) ? (int) $_POST["media_id"] : 0;

        if ($mediaId <= 0) {
            http_response_code(422);

            echo json_encode([
                "success" => false,
                "message" => "Invalid image ID.",
            ]);

            return;
        }

        try {
            $model = $this->model("Asset_model");

            $media = $model->getMediaById($mediaId);

            if (!$media) {
                http_response_code(404);

                echo json_encode([
                    "success" => false,
                    "message" => "Image was not found.",
                ]);

                return;
            }

            /*
             * Delete database record first.
             */
            if (!$model->deleteMediaById($mediaId)) {
                throw new Exception("The image database record could not be deleted.");
            }

            /*
             * Delete physical image file.
             */
            $fileName = basename($media["file_name"]);

            $filePath = dirname(__DIR__, 2) . "/public/uploads/asset/" . $fileName;

            if ($fileName !== "" && is_file($filePath)) {
                unlink($filePath);
            }

            echo json_encode([
                "success" => true,
                "message" => "Image deleted successfully.",
            ]);
        } catch (Throwable $exception) {
            http_response_code(500);

            echo json_encode([
                "success" => false,
                "message" => $exception->getMessage(),
            ]);
        }
    }

    public function printHistory($assetNum)
    {
        $history = $this->model("Asset_model")->getAssetTransactionHistory($assetNum);
        if (!$history) {
            http_response_code(404);
            echo "Asset not found";
            return;
        }
        $this->view("asset/print-history", $history);
    }

    public function printLabel($assetNum)
    {
        $assetNum = trim(rawurldecode((string) $assetNum));
        if (!preg_match('/^[A-Za-z0-9_-]{1,30}$/', $assetNum)) {
            http_response_code(400);
            echo "Invalid asset number";
            return;
        }
        $asset = $this->model("Asset_model")->getAssetEntryById($assetNum);
        if (!$asset) {
            http_response_code(404);
            echo "Asset not found";
            return;
        }
        $this->view("asset/print-label", [
            "asset" => $asset,
            "qrDataUri" => $this->qrDataUri($this->assetQrPayload($asset)),
        ]);
    }

    /** Print selected 70 x 35 mm adhesive labels for Administrator/Issuer. */
    public function printBulkLabels()
    {
        if (
            !(defined("BULK_LABEL_PRINT_ENABLED")
                ? BULK_LABEL_PRINT_ENABLED
                : false)
        ) {
            http_response_code(404);
            exit("Bulk label printing is disabled.");
        }
        $this->requireAssetRole(
            ["ISSUER"],
            "Only an Administrator or Issuer can print bulk asset labels.",
        );
        if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
            http_response_code(405);
            exit("Method not allowed.");
        }
        if (
            empty($_SESSION["csrf_bulk_labels"]) ||
            !hash_equals(
                (string) $_SESSION["csrf_bulk_labels"],
                (string) ($_POST["csrf_token"] ?? ""),
            )
        ) {
            http_response_code(403);
            exit("The label selection expired. Return to InStock Assets and try again.");
        }

        $assetScope = strtoupper(trim((string) ($_POST["asset_scope"] ?? "IN_STOCK")));
        if (!in_array($assetScope, ["IN_STOCK", "ACTIVE"], true)) {
            http_response_code(422);
            exit("Invalid asset label source.");
        }

        $assetNumbers = $_POST["assetNums"] ?? [];
        if (!is_array($assetNumbers)) {
            $assetNumbers = [$assetNumbers];
        }
        $assetNumbers = array_values(
            array_unique(
                array_filter(
                    array_map(static fn($value) => trim((string) $value), $assetNumbers),
                    static fn($value) =>
                        preg_match('/^[A-Za-z0-9_-]{1,30}$/', $value) === 1,
                ),
            ),
        );
        if (!$assetNumbers) {
            http_response_code(422);
            exit("Select at least one in-stock asset label.");
        }
        if (count($assetNumbers) > 200) {
            http_response_code(422);
            exit("A maximum of 200 asset labels can be printed in one batch.");
        }

        $assetModel = $this->model("Asset_model");
        $loaded =
            $assetScope === "ACTIVE"
                ? $assetModel->getActiveAssetsByNumbers($assetNumbers)
                : $assetModel->getInStockAssetsByNumbers($assetNumbers);
        $assetMap = [];
        foreach ($loaded as $asset) {
            $assetMap[(string) $asset["assetNum"]] = $asset;
        }
        $labels = [];
        foreach ($assetNumbers as $assetNumber) {
            if (!isset($assetMap[$assetNumber])) {
                continue;
            }
            $asset = $assetMap[$assetNumber];
            $asset["qrDataUri"] = $this->qrDataUri($this->assetQrPayload($asset));
            $labels[] = $asset;
        }
        if (!$labels) {
            http_response_code(409);
            exit("The selected assets are no longer available in this asset list.");
        }

        $this->view("asset/print-labels", [
            "labels" => $labels,
            "requestedCount" => count($assetNumbers),
        ]);
    }

    public function printAssignment($issueId)
    {
        $record = $this->model("Asset_model")->getAssignmentForm((int) $issueId);
        if (!$record) {
            http_response_code(404);
            echo "Assignment not found";
            return;
        }
        $this->view("asset/transaction-form", [
            "formType" => "ASSIGNMENT",
            "record" => $record,
            "qrDataUri" => $this->qrDataUri($this->transactionQrPayload("ASSIGNMENT", $record)),
        ]);
    }

    public function downloadAssignmentPdf($issueId)
    {
        $ids = array_values(
            array_unique(array_filter(array_map("intval", explode(",", (string) $issueId)))),
        );
        $model = $this->model("Asset_model");
        if (count($ids) === 1) {
            $documentIds = $model->getAssignmentDocumentIssueIds($ids[0]);
            if ($documentIds) {
                $ids = $documentIds;
            }
        }
        if (count($ids) > 8) {
            http_response_code(422);
            exit("A maximum of 8 assignment records can be included in one PDF.");
        }
        $records = [];
        foreach ($ids as $id) {
            $record = $model->getAssignmentForm($id);
            if ($record) {
                $records[] = $record;
            }
        }
        if (!$records) {
            http_response_code(404);
            echo "Assignment not found";
            return;
        }
        $record = $records[0];
        $record["assets"] = $records;
        $suffix = count($records) > 1 ? "-and-" . (count($records) - 1) . "-more" : "";
        SimplePdfReport::downloadAssignment(
            $record,
            "asset-assignment-" .
                preg_replace("/[^A-Za-z0-9_-]/", "_", $record["assetNum"]) .
                $suffix .
                ".pdf",
            dirname(__DIR__, 2) . "/public/img/unicoil-logo.jpg",
        );
    }

    public function printReturn($returnId)
    {
        $record = $this->model("Asset_model")->getReturnForm((int) $returnId);
        if (!$record) {
            http_response_code(404);
            echo "Return not found";
            return;
        }
        $this->view("asset/transaction-form", [
            "formType" => "RETURN",
            "record" => $record,
            "qrDataUri" => $this->qrDataUri($this->transactionQrPayload("RETURN", $record)),
        ]);
    }

    public function downloadReturnPdf($returnId)
    {
        $ids = array_values(
            array_unique(array_filter(array_map("intval", explode(",", (string) $returnId)))),
        );
        if (count($ids) > 8) {
            http_response_code(422);
            exit("A maximum of 8 return records can be included in one PDF.");
        }
        $model = $this->model("Asset_model");
        $records = [];
        foreach ($ids as $id) {
            $record = $model->getReturnForm($id);
            if ($record) {
                $records[] = $record;
            }
        }
        if (!$records) {
            http_response_code(404);
            echo "Return not found";
            return;
        }
        $record = $records[0];
        $record["assets"] = $records;
        $suffix = count($records) > 1 ? "-and-" . (count($records) - 1) . "-more" : "";
        SimplePdfReport::downloadReturn(
            $record,
            "asset-return-" .
                preg_replace("/[^A-Za-z0-9_-]/", "_", $record["assetNum"]) .
                $suffix .
                ".pdf",
            dirname(__DIR__, 2) . "/public/img/unicoil-logo.jpg",
        );
    }

    private function transactionQrPayload(string $type, array $record): string
    {
        $text = static function ($value, int $limit = 80): string {
            $value = preg_replace("/\s+/u", " ", trim((string) ($value ?? "")));
            if ($value === "") {
                return "-";
            }
            return function_exists("mb_substr")
                ? mb_substr($value, 0, $limit, "UTF-8")
                : substr($value, 0, $limit);
        };
        $holder = $text($record["employeeName"] ?? ($record["acknowledged_by"] ?? ""));
        $lines = [
            "UNICOIL IT ASSET",
            $type,
            $type === "RETURN"
                ? "Return: " . $text($record["return_no"])
                : "Issue: " . $text($record["issue_no"]),
            "Asset: " . $text($record["assetNum"]),
            "Description: " . $text($record["assetDescription"]),
            "Category: " . $text($record["categoryName"]),
            "Serial: " . $text($record["serial_number"]),
            "Employee/Custodian: " . $holder,
            "Department: " . $text($record["deptName"]),
            "Assigned: " . $text($record["issue_date"]),
        ];
        if ($type === "RETURN") {
            $lines[] = "Returned: " . $text($record["return_date"]);
            $lines[] = "Return Type: " . $text($record["return_type"]);
            $lines[] = "Condition: " . $text($record["condition_in"]);
        } else {
            $lines[] = "Condition: " . $text($record["condition_out"]);
            $lines[] = "Expected Return: " . $text($record["expected_return_date"]);
        }
        return implode("\n", $lines);
    }

    private function qrDataUri(string $value): string
    {
        $matrix = QRcode::raw($value, false, QR_ECLEVEL_M, 1, 0);
        if (!is_array($matrix) || !$matrix) {
            throw new RuntimeException("Unable to generate QR code.");
        }
        $margin = 4;
        $modules = count($matrix);
        $size = $modules + $margin * 2;
        $path = "";
        foreach ($matrix as $y => $row) {
            for ($x = 0; $x < $modules; $x++) {
                if ((ord($row[$x]) & 1) === 1) {
                    $path .= "M" . ($x + $margin) . " " . ($y + $margin) . "h1v1h-1z";
                }
            }
        }
        $svg =
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' .
            $size .
            " " .
            $size .
            '" shape-rendering="crispEdges"><rect width="100%" height="100%" fill="#fff"/><path d="' .
            $path .
            '" fill="#000"/></svg>';
        return "data:image/svg+xml;base64," . base64_encode($svg);
    }

    /** Keep single and bulk stickers encoded with identical asset information. */
    private function assetQrPayload(array $asset): string
    {
        $clean = static function ($value, int $limit = 45): string {
            $value = preg_replace("/\s+/u", " ", trim((string) ($value ?? "")));
            if ($value === "") {
                return "-";
            }
            return function_exists("mb_substr")
                ? mb_substr($value, 0, $limit, "UTF-8")
                : substr($value, 0, $limit);
        };

        $lines = [
            "UNICOIL IT ASSET",
            "Asset: " . $clean($asset["assetNum"] ?? "", 30),
            "Description: " . $clean($asset["Description"] ?? ""),
            "Category: " . $clean($asset["categoryName"] ?? ($asset["assetKat"] ?? "")),
            "Serial: " . $clean($asset["serial_number"] ?? ""),
            "Make/Model: " .
                $clean(
                    trim(
                        (string) ($asset["manufacturer"] ?? "") .
                            " " .
                            (string) ($asset["model"] ?? ""),
                    ),
                ),
            "Current Condition: " .
                $clean($asset["asset_condition"] ?? "NEW", 25),
            // "Location: " .
            //     $clean($asset["locationName"] ?? ($asset["location"] ?? "")),
        ];

        // Employee custody is included only while the asset is actively
        // assigned to a user. In-stock and department-assigned labels do not
        // display employee fields.
        $isUserAssignment =
            strtoupper(trim((string) ($asset["assetStat"] ?? ""))) === "ASSIGNED" &&
            trim((string) ($asset["empId"] ?? "")) !== "";
        if ($isUserAssignment) {
            $lines[] = "Employee ID: " . $clean($asset["empId"], 20);
            $lines[] = "Employee: " . $clean($asset["empName"] ?? "", 45);
        }

        return implode("\n", $lines);
    }

    public function warrantyTimeline()
    {
        $this->requireAssetRole(
            ["ISSUER", "IT_MANAGER"],
            "Only an Administrator, Issuer or IT Manager can view asset duration details.",
        );

        $defaultFrom = (new DateTimeImmutable("first day of January"))->format("Y-m-d");
        $defaultTo = (new DateTimeImmutable("last day of December +3 years"))->format(
            "Y-m-d",
        );
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

        $filters = [
            "from" => $from,
            "to" => $to,
            "search" => trim((string) ($_GET["search"] ?? "")),
            "category" => trim((string) ($_GET["category"] ?? "ALL")),
            "status" => trim((string) ($_GET["status"] ?? "ALL")),
            "sort" => trim((string) ($_GET["sort"] ?? "DURATION_ASC")),
            "perPage" => (int) ($_GET["perPage"] ?? 25),
            "page" => (int) ($_GET["page"] ?? 1),
        ];
        $model = $this->model("Asset_model");
        $report = $model->getWarrantyTimelinePage($filters);
        $data = [
            "title" => "IT Asset - Asset Duration Details",
            "menu" => "Asset Duration Details",
            "menu-dsc" => "Search and sort individual purchase-to-warranty timelines",
            "reportPage" => true,
            "warrantyTimelinePage" => true,
            "report" => $report,
            "filters" => $report["filters"],
            "categories" => $model->getAllAssetCategories(),
        ];

        $this->view("templates/header_a", $data);
        $this->view("asset/warranty-timeline", $data);
        $this->view("templates/footer_a");
    }

    public function active()
    {
        $data["title"] = "IT Asset - Active Assets";
        $data["menu"] = "Active Assets";
        $data["menu-dsc"] = "List of Active Assets";
        $data["reportPage"] = true;
        $filters = $this->activeAssetFilters();
        $assetPage = $this->model("Asset_model")->getActiveAssetsPage($filters);
        $data["assets"] = $assetPage["items"];
        $data["pagination"] = $assetPage;
        $data["filters"] = $filters;
        $data["categories"] = $this->model("Assetkat_model")->getAllAssetCat();
        $pdfIds = array_values(
            array_unique(
                array_filter(
                    array_map("intval", explode(",", (string) ($_GET["assignmentPdf"] ?? ""))),
                ),
            ),
        );
        if (count($pdfIds) > 8) {
            Flasher::setMessage(
                "Assignment PDF not opened.",
                "A maximum of 8 assignment records can be included in one PDF.",
                "danger",
            );
            $pdfIds = [];
        }
        $data["assignmentPdfIds"] = implode(",", $pdfIds);

        $this->addBulkLabelViewData($data);

        $this->view("templates/header_a", $data);
        $this->view("asset/active", $data);
        $this->view("templates/footer_a");
    }

    public function inactive()
    {
        $data["title"] = "IT Asset - InStock Assets";
        $data["menu"] = "InStock Assets";
        $data["menu-dsc"] = "List of InStock Assets";
        $data["reportPage"] = true;

        $filters = $this->inventoryFilters();
        $assetPage = $this->model("Asset_model")->getAssetPage("IN_STOCK", $filters);
        $data["assets"] = $assetPage["items"];
        $data["pagination"] = $assetPage;
        $data["filters"] = $filters;

        // Category dropdown
        $data["categories"] = $this->model("Asset_model")->getAllAssetCategories();

        $this->addBulkLabelViewData($data);

        $this->view("templates/header_a", $data);
        $this->view("asset/inactive", $data);
        $this->view("templates/footer_a");
    }

    /** Add the shared bulk-label permission and CSRF state to inventory pages. */
    private function addBulkLabelViewData(array &$data): void
    {
        $roles = array_values(array_filter($_SESSION["usr"]["roles"] ?? []));
        $isBulkLabelOperator =
            strtolower(trim((string) ($_SESSION["usr"]["user"] ?? ""))) === "admin" ||
            (bool) array_intersect($roles, ["ADMINISTRATOR", "ISSUER"]);
        $data["canBulkPrintLabels"] =
            (defined("BULK_LABEL_PRINT_ENABLED")
                ? BULK_LABEL_PRINT_ENABLED
                : false) && $isBulkLabelOperator;
        if ($data["canBulkPrintLabels"] && empty($_SESSION["csrf_bulk_labels"])) {
            $_SESSION["csrf_bulk_labels"] = bin2hex(random_bytes(32));
        }
        $data["bulkLabelCsrf"] = $_SESSION["csrf_bulk_labels"] ?? "";
    }

    public function disposed()
    {
        $this->requireAssetRole(
            ["ISSUER"],
            "Only an Administrator or Issuer can view the scrap list.",
        );
        $data["title"] = "IT Asset - Scrapped Assets";
        $data["menu"] = "Scrapped Assets";
        $data["menu-dsc"] = "Assets returned and marked for scrap";
        $data["scrapListPage"] = true;
        $filters = $this->inventoryFilters();
        $assetPage = $this->model("Asset_model")->getAssetPage("SCRAPPED", $filters);
        $data["assets"] = $assetPage["items"];
        $data["pagination"] = $assetPage;
        $data["filters"] = $filters;
        $data["categories"] = $this->model("Asset_model")->getAllAssetCategories();
        $account = $this->model("User_model")->getUserById(
            (string) ($_SESSION["usr"]["user"] ?? ""),
        );
        $data["canDeleteScrapped"] =
            strtolower((string) ($_SESSION["usr"]["user"] ?? "")) === "admin" ||
            in_array("ADMINISTRATOR", $account["roles"] ?? [], true);
        if (empty($_SESSION["csrf_scrap_delete"])) {
            $_SESSION["csrf_scrap_delete"] = bin2hex(random_bytes(32));
        }
        $data["deleteCsrf"] = $_SESSION["csrf_scrap_delete"];

        $this->view("templates/header_a", $data);
        $this->view("asset/disposed", $data);
        $this->view("templates/footer_a");
    }

    public function deleteScrapped()
    {
        $this->requireAssetRole(
            ["ADMINISTRATOR"],
            "Only an Administrator can permanently delete a scrapped asset.",
        );
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            exit("Method not allowed");
        }
        $token = $_POST["csrf_token"] ?? "";
        if (
            empty($_SESSION["csrf_scrap_delete"]) ||
            !hash_equals($_SESSION["csrf_scrap_delete"], $token)
        ) {
            Flasher::setMessage("Delete failed", "Invalid form token.", "danger");
            header("location: " . BASEURL . "/Asset/disposed");
            exit();
        }
        try {
            $this->model("Asset_model")->deleteScrappedAsset(
                $_POST["assetNum"] ?? "",
                $_POST["deletionReason"] ?? "",
                $_SESSION["usr"]["user"],
            );
            unset($_SESSION["csrf_scrap_delete"]);
            Flasher::setMessage(
                "Scrapped asset",
                "Deleted permanently; audit snapshot retained.",
                "success",
            );
        } catch (Throwable $e) {
            Flasher::setMessage("Delete failed", $e->getMessage(), "danger");
        }
        header("location: " . BASEURL . "/Asset/disposed");
        exit();
    }

    public function auditReport()
    {
        [$from, $to] = $this->auditDates();
        [$assetNum, $eventType, $category, $department, $search, $actor, $assignedTo, $condition] = $this->auditFilters();
        $workflowUiEnabled = defined("WORKFLOW_UI_ENABLED")
            ? WORKFLOW_UI_ENABLED
            : true;
        $accessoryInventoryEnabled = defined("ACCESSORY_INVENTORY_ENABLED")
            ? ACCESSORY_INVENTORY_ENABLED
            : false;
        $model = $this->model("Asset_model");
        $rows = $model->getAuditReport($from, $to, $assetNum, $eventType, $category, $department,
            $search, $actor, $assignedTo, $condition, 1000, $workflowUiEnabled, $accessoryInventoryEnabled);
        $categories = $model->getAllAssetCategories();
        if ($accessoryInventoryEnabled) {
            foreach ($this->model("Accessory_model")->getCategories() as $accessoryCategory) {
                $categories[] = ["Description" => $accessoryCategory];
            }
            $uniqueCategories = [];
            foreach ($categories as $categoryRow) {
                $description = trim((string) ($categoryRow["Description"] ?? ""));
                if ($description !== "") {
                    $uniqueCategories[$description] = ["Description" => $description];
                }
            }
            ksort($uniqueCategories, SORT_NATURAL | SORT_FLAG_CASE);
            $categories = array_values($uniqueCategories);
        }
        $data = [
            "title" => "IT Asset - Transaction Audit",
            "menu" => "Asset Transaction Audit",
            "menu-dsc" => implode(", ", array_filter([
                "Receipt",
                "current in-stock state",
                "update",
                "assignment",
                "return",
                $accessoryInventoryEnabled ? "accessory stock" : null,
                $workflowUiEnabled ? "scrap decision" : null,
                "deletion history",
            ])),
            "reportPage" => true,
            "rows" => $rows,
            "categories" => $categories,
            "departments" => $this->model("Department_model")->getAllDept(),
            "from" => $from,
            "to" => $to,
            "assetNum" => $assetNum,
            "eventType" => $eventType,
            "category" => $category,
            "department" => $department,
            "search" => $search,
            "actor" => $actor,
            "assignedTo" => $assignedTo,
            "condition" => $condition,
            "workflowUiEnabled" => $workflowUiEnabled,
            "accessoryInventoryEnabled" => $accessoryInventoryEnabled,
        ];
        $this->view("templates/header_a", $data);
        $this->view("asset/audit-report", $data);
        $this->view("templates/footer_a");
    }

    public function archive()
    {
        [$from, $to] = $this->auditDates();
        $category = trim($_GET["category"] ?? "");
        $model = $this->model("Asset_model");
        $data = [
            "title" => "IT Asset - Scrapped Archive",
            "menu" => "Scrapped Asset Archive",
            "menu-dsc" => "Permanent records of deleted scrapped assets",
            "rows" => $model->getScrappedArchive($from, $to, $category),
            "categories" => $model->getAllAssetCategories(),
            "from" => $from,
            "to" => $to,
            "category" => $category,
        ];
        $this->view("templates/header_a", $data);
        $this->view("asset/archive", $data);
        $this->view("templates/footer_a");
    }

    public function exportArchiveExcel()
    {
        [$from, $to] = $this->auditDates();
        $category = trim($_GET["category"] ?? "");
        $rows = $this->model("Asset_model")->getScrappedArchive($from, $to, $category);
        header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
        header(
            'Content-Disposition: attachment; filename="scrapped-archive-' .
                $from .
                "-to-" .
                $to .
                '.xls"',
        );
        echo '<html><head><meta charset="UTF-8"></head><body><h2>Scrapped Asset Archive</h2><p>Period: ' .
            htmlspecialchars($from) .
            " to " .
            htmlspecialchars($to) .
            '</p><table border="1"><tr><th>Archive ID</th><th>Archived Date</th><th>Asset Number</th><th>Description</th><th>Category</th><th>Serial</th><th>Deleted By</th><th>Reason</th><th>Transactions Retained</th><th>Images Retained</th></tr>';
        foreach ($rows as $r) {
            echo "<tr>";
            foreach (
                [
                    "deletion_id",
                    "archivedDate",
                    "assetNum",
                    "assetDescription",
                    "category",
                    "serial_number",
                    "deletedBy",
                    "deletionReason",
                    "transactionCount",
                    "mediaCount",
                ]
                as $key
            ) {
                echo "<td>" . htmlspecialchars($r[$key] ?? "", ENT_QUOTES, "UTF-8") . "</td>";
            }
            echo "</tr>";
        }
        echo "</table></body></html>";
        exit();
    }

    public function exportArchivePdf()
    {
        [$from, $to] = $this->auditDates();
        $category = trim($_GET["category"] ?? "");
        $archive = $this->model("Asset_model")->getScrappedArchive($from, $to, $category);
        $rows = [];
        foreach ($archive as $r) {
            $rows[] = [
                "eventDate" => $r["archivedDate"],
                "eventType" => "DELETE",
                "assetNum" => $r["assetNum"],
                "assetDescription" => $r["assetDescription"],
                "assetImage" => $r["assetImage"],
                "assignedTo" => "Archived by " . $r["deletedBy"],
                "department" => $r["category"],
                "details" =>
                    $r["deletionReason"] .
                    " / " .
                    $r["transactionCount"] .
                    " transactions retained",
            ];
        }
        SimplePdfReport::downloadAudit(
            $rows,
            $from,
            $to,
            "scrapped-archive-" . $from . "-to-" . $to . ".pdf",
            dirname(__DIR__, 2) . "/public/uploads/archive/",
            dirname(__DIR__, 2) . "/public/img/no-image.png",
        );
    }

    public function exportAuditExcel()
    {
        [$from, $to] = $this->auditDates();
        [$assetNum, $eventType, $category, $department, $search, $actor, $assignedTo, $condition] = $this->auditFilters();
        $workflowUiEnabled = defined("WORKFLOW_UI_ENABLED")
            ? WORKFLOW_UI_ENABLED
            : true;
        $accessoryInventoryEnabled = defined("ACCESSORY_INVENTORY_ENABLED")
            ? ACCESSORY_INVENTORY_ENABLED
            : false;
        $rows = $this->model("Asset_model")->getAuditReport(
            $from,
            $to,
            $assetNum,
            $eventType,
            $category,
            $department,
            $search,
            $actor,
            $assignedTo,
            $condition,
            0,
            $workflowUiEnabled,
            $accessoryInventoryEnabled,
        );
        header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
        header(
            'Content-Disposition: attachment; filename="asset-audit-' .
                $from .
                "-to-" .
                $to .
                '.xls"',
        );
        $summary =
            "Period: " .
            $from .
            " to " .
            $to .
            ($assetNum !== "" ? " | Asset: " . $assetNum : "") .
            ($eventType !== "" ? " | Event: " . $eventType : "") .
            ($category !== "" ? " | Category: " . $category : "") .
            ($department !== "" ? " | Department: " . $department : "");
        echo "<html><head><meta charset=\"UTF-8\"></head><body><h2>IT Asset Audit Report</h2><p>" .
            htmlspecialchars($summary, ENT_QUOTES, "UTF-8") .
            "</p><table border=\"1\"><tr><th>Date</th><th>Event</th><th>Reference</th><th>Asset</th><th>Description</th><th>Category</th><th>Purchase Date</th><th>Warranty Expiry</th><th>Actor</th><th>Assigned To</th><th>Department</th><th>Condition</th><th>Details</th></tr>";
        foreach ($rows as $r) {
            echo "<tr>";
            foreach (
                [
                    "eventDate",
                    "eventType",
                    "referenceNo",
                    "assetNum",
                    "assetDescription",
                    "category",
                    "purchaseDate",
                    "warrantyExpiry",
                    "actor",
                    "assignedTo",
                    "department",
                    "conditionInfo",
                    "details",
                ]
                as $key
            ) {
                $value = $r[$key] ?? "";
                if ($key === "eventDate") {
                    $value = $this->formatAuditDateTime($value);
                }
                echo "<td>" . htmlspecialchars($value, ENT_QUOTES, "UTF-8") . "</td>";
            }
            echo "</tr>";
        }
        echo "</table></body></html>";
        exit();
    }

    public function exportAuditPdf()
    {
        [$from, $to] = $this->auditDates();
        [$assetNum, $eventType, $category, $department, $search, $actor, $assignedTo, $condition] = $this->auditFilters();
        $workflowUiEnabled = defined("WORKFLOW_UI_ENABLED")
            ? WORKFLOW_UI_ENABLED
            : true;
        $accessoryInventoryEnabled = defined("ACCESSORY_INVENTORY_ENABLED")
            ? ACCESSORY_INVENTORY_ENABLED
            : false;
        $rows = $this->model("Asset_model")->getAuditReport(
            $from,
            $to,
            $assetNum,
            $eventType,
            $category,
            $department,
            $search,
            $actor,
            $assignedTo,
            $condition,
            0,
            $workflowUiEnabled,
            $accessoryInventoryEnabled,
        );
        foreach ($rows as &$row) {
            $row["eventDate"] = $this->formatAuditDateTime(
                $row["eventDate"] ?? "",
            );
        }
        unset($row);
        SimplePdfReport::downloadAudit(
            $rows,
            $from,
            $to,
            "asset-audit-" . $from . "-to-" . $to . ".pdf",
            dirname(__DIR__, 2) . "/public/uploads/asset/",
            dirname(__DIR__, 2) . "/public/img/no-image.png",
        );
    }

    /**
     * Download any navigation report as an Excel-compatible workbook.
     *
     * Large exports are deliberately bounded because this application may
     * contain millions of assets and runs on memory-limited shared hosting.
     */
    public function exportReportExcel($reportKey)
    {
        $report = $this->buildNavigationReport((string) $reportKey, 10000);
        $filename = $report["slug"] . "-" . date("Y-m-d") . ".xls";

        header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo '<html><head><meta charset="UTF-8"><style>';
        echo "table{border-collapse:collapse}th{background:#006b57;color:#fff}";
        echo "th,td{border:1px solid #b8c9c5;padding:6px;vertical-align:top}";
        echo "</style></head><body>";
        echo "<h2>" . $this->escapeExportValue($report["title"]) . "</h2>";
        echo "<p>" . $this->escapeExportValue($report["summary"]) . "</p>";
        if ($report["truncated"]) {
            echo '<p style="color:#b45309"><strong>Notice:</strong> Export limited to the first ' .
                number_format(count($report["rows"])) .
                " matching records for hosting safety.</p>";
        }
        echo "<table><thead><tr>";
        foreach ($report["columns"] as $column) {
            echo "<th>" . $this->escapeExportValue($column) . "</th>";
        }
        echo "</tr></thead><tbody>";
        foreach ($report["rows"] as $row) {
            echo "<tr>";
            foreach ($report["columns"] as $column) {
                echo "<td>" .
                    $this->escapeExportValue(
                        $this->spreadsheetSafeValue($row[$column] ?? ""),
                    ) .
                    "</td>";
            }
            echo "</tr>";
        }
        echo "</tbody></table></body></html>";
        exit();
    }

    /** Download a readable, paginated PDF version of a navigation report. */
    public function exportReportPdf($reportKey)
    {
        $report = $this->buildNavigationReport((string) $reportKey, 1000);
        $pdfRows = [];
        foreach ($report["rows"] as $row) {
            $pdfRows[] = $this->navigationReportPdfRow($row, $report["event"]);
        }
        $subtitle = $report["summary"];
        if ($report["truncated"]) {
            $subtitle .= " (first " . number_format(count($report["rows"])) . " records)";
        }
        SimplePdfReport::downloadAudit(
            $pdfRows,
            $report["from"],
            $report["to"],
            $report["slug"] . "-" . date("Y-m-d") . ".pdf",
            dirname(__DIR__, 2) . "/public/uploads/asset/",
            dirname(__DIR__, 2) . "/public/img/no-image.png",
            strtoupper($report["title"]),
            $subtitle,
            [["TOTAL RECORDS", count($report["rows"]), [0.08, 0.48, 0.38]]],
        );
    }

    /** Build one normalized export dataset while preserving page filters. */
    private function buildNavigationReport(string $reportKey, int $limit): array
    {
        $reportKey = strtolower(trim($reportKey));
        $model = $this->model("Asset_model");
        $today = date("Y-m-d");
        $definition = [
            "title" => "",
            "slug" => preg_replace('/[^a-z0-9-]+/', "-", $reportKey),
            "summary" => "",
            "event" => "REPORT",
            "from" => $today,
            "to" => $today,
            "rows" => [],
            "total" => 0,
        ];

        if (in_array($reportKey, ["my-assets", "my-history"], true)) {
            $account = $this->model("User_model")->getUserById(
                (string) ($_SESSION["usr"]["user"] ?? ""),
            );
            $employeeId = (int) ($account["empId"] ?? 0);
            $departmentScope = strtolower(trim((string) ($_GET["scope"] ?? "mine"))) ===
                "department";
            if (
                $departmentScope &&
                !in_array("DEPARTMENT_MANAGER", $account["roles"] ?? [], true)
            ) {
                http_response_code(403);
                exit("Only a Department Manager can export department reports.");
            }
            $scopeLabel = $departmentScope ? "Department" : "Personal";
            if ($reportKey === "my-assets") {
                $sourceRows = $model->getMyAssignedAssets($employeeId, $departmentScope);
                foreach (array_slice($sourceRows, 0, $limit) as $row) {
                    $assignedTo =
                        ($row["assignee_type"] ?? "") === "DEPARTMENT"
                            ? $row["deptName"] ?? ""
                            : $row["employeeName"] ?? "";
                    $definition["rows"][] = [
                        "Issue Date" => $row["issue_date"] ?? "",
                        "Asset Number" => $row["assetNum"] ?? "",
                        "Serial Number" => $row["serial_number"] ?? "",
                        "Description" => $row["assetDescription"] ?? "",
                        "Category" => $row["categoryName"] ?? "",
                        "Assigned To" => $assignedTo,
                        "Issue Number" => ($row["batch_no"] ?? "") ?:
                            ($row["issue_no"] ?? ""),
                        "Condition" => $row["condition_out"] ?? "",
                        "Purpose" => $row["purpose"] ?? "",
                        "Acceptance" => !empty($row["receiver_accepted_at"])
                            ? "Accepted " . $row["receiver_accepted_at"]
                            : (!empty($row["request_no"]) ? "Pending" : "Manual assignment"),
                    ];
                }
                $definition["title"] = $scopeLabel . " Assigned Assets Report";
                $definition["slug"] = "my-assigned-assets";
                $definition["summary"] = $scopeLabel . " assets currently in custody";
                $definition["event"] = "ASSIGN";
            } else {
                $sourceRows = $model->getMyAssetHistory($employeeId, $departmentScope);
                foreach (array_slice($sourceRows, 0, $limit) as $row) {
                    $assignedTo =
                        ($row["assignee_type"] ?? "") === "DEPARTMENT"
                            ? $row["deptName"] ?? ""
                            : $row["employeeName"] ?? "";
                    $definition["rows"][] = [
                        "Issue Date" => $row["issue_date"] ?? "",
                        "Return Date" => $row["return_date"] ?? "",
                        "Asset Number" => $row["assetNum"] ?? "",
                        "Serial Number" => $row["serial_number"] ?? "",
                        "Description" => $row["assetDescription"] ?? "",
                        "Category" => $row["categoryName"] ?? "",
                        "Assigned To" => $assignedTo,
                        "Issue Number" => ($row["batch_no"] ?? "") ?:
                            ($row["issue_no"] ?? ""),
                        "Issue Status" => $row["issueStatus"] ?? "",
                        "Return Number" => ($row["returnBatchNo"] ?? "") ?:
                            ($row["return_no"] ?? ""),
                        "Return Condition" => $row["condition_in"] ?? "",
                        "Return Reason" => $row["returnReason"] ?? "",
                    ];
                }
                $definition["title"] = $scopeLabel . " Asset History Report";
                $definition["slug"] = "my-asset-history";
                $definition["summary"] = $scopeLabel . " assignment and return history";
                $definition["event"] = "HISTORY";
            }
            $definition["total"] = count($sourceRows);
        } elseif ($reportKey === "assigned") {
            $this->requireAssetRole(
                ["ISSUER", "IT_MANAGER"],
                "Only an Administrator, Issuer or IT Manager can export assigned assets.",
            );
            $filters = $this->activeAssetFilters();
            $pageData = $this->collectReportPages(
                static function (int $page) use ($model, $filters): array {
                    return $model->getActiveAssetsPage(
                        array_merge($filters, ["page" => $page, "perPage" => 100]),
                    );
                },
                $limit,
            );
            foreach ($pageData["items"] as $batch) {
                $assetNumbers = array_map("trim", explode(",", (string) ($batch["assetNums"] ?? "")));
                $descriptions = explode("|||", (string) ($batch["assetDescriptions"] ?? ""));
                $serials = explode("|||", (string) ($batch["assetSerialNumbers"] ?? ""));
                $categories = explode("|||", (string) ($batch["assetCategoryNames"] ?? ""));
                foreach ($assetNumbers as $index => $assetNumber) {
                    $definition["rows"][] = [
                        "Assignment Date & Time" => $this->formatAuditDateTime(
                            $batch["issueDate"] ?? "",
                        ),
                        "Request Number" => $batch["requestNo"] ?: "Manual Assign",
                        "Issue Number" => $batch["issueNo"] ?? "",
                        "Asset Number" => $assetNumber,
                        "Serial Number" => $serials[$index] ?? "",
                        "Description" => $descriptions[$index] ?? "",
                        "Category" => $categories[$index] ?? "",
                        "Assigned To" =>
                            ($batch["assignmentType"] ?? "") === "DEPARTMENT"
                                ? $batch["deptName"] ?? ""
                                : $batch["empName"] ?? "",
                        "Department" => $batch["deptName"] ?? "",
                        "Assignment Type" => $batch["assignmentType"] ?? "",
                        "Status" => $batch["assetStatusName"] ?? "",
                    ];
                }
            }
            $definition["title"] = "Asset Assigned History Report";
            $definition["slug"] = "asset-assigned-history";
            $definition["summary"] = "Filtered active asset assignments";
            $definition["event"] = "ASSIGN";
            $definition["total"] = $pageData["total"];
        } elseif ($reportKey === "returns") {
            $this->requireAssetRole(
                ["ISSUER", "IT_MANAGER"],
                "Only an Administrator, Issuer or IT Manager can export return history.",
            );
            $filters = $this->returnHistoryExportFilters();
            $pageData = $this->collectReportPages(
                static function (int $page) use ($model, $filters): array {
                    return $model->getFilteredReturnHistory(
                        array_merge($filters, ["page" => $page, "perPage" => 100]),
                    );
                },
                $limit,
            );
            foreach ($pageData["items"] as $batch) {
                $assetNumbers = explode("|||", (string) ($batch["assetNum"] ?? ""));
                $descriptions = explode("|||", (string) ($batch["assetDescription"] ?? ""));
                $serials = explode("|||", (string) ($batch["serial_number"] ?? ""));
                $categories = explode("|||", (string) ($batch["categoryName"] ?? ""));
                $conditions = explode("|||", (string) ($batch["condition_in"] ?? ""));
                foreach ($assetNumbers as $index => $assetNumber) {
                    $definition["rows"][] = [
                        "Return Date & Time" => $this->formatAuditDateTime(
                            $batch["return_date"] ?? "",
                        ),
                        "Return Number" => $batch["return_no"] ?? "",
                        "Asset Number" => $assetNumber,
                        "Serial Number" => $serials[$index] ?? "",
                        "Description" => $descriptions[$index] ?? "",
                        "Category" => $categories[$index] ?? "",
                        "Assigned To" =>
                            ($batch["assignee_type"] ?? "") === "DEPARTMENT"
                                ? $batch["deptName"] ?? ""
                                : $batch["custodian"] ?? "",
                        "Department" => $batch["deptName"] ?? "",
                        "Condition" => $conditions[$index] ?? "",
                        "Return Type" => $batch["return_type"] ?? "",
                        "Remarks" => $batch["reason"] ?? "",
                        "Received By" => $batch["received_by"] ?? "",
                    ];
                }
            }
            $definition["title"] = "Asset Return History Report";
            $definition["slug"] = "asset-return-history";
            $definition["summary"] = "Filtered asset return transactions";
            $definition["event"] = "RETURN";
            $definition["total"] = $pageData["total"];
        } elseif ($reportKey === "instock") {
            $this->requireAssetRole(
                ["ISSUER", "IT_MANAGER"],
                "Only an Administrator, Issuer or IT Manager can export in-stock assets.",
            );
            $filters = $this->inventoryFilters();
            $pageData = $this->collectReportPages(
                static function (int $page) use ($model, $filters): array {
                    return $model->getAssetPage(
                        "IN_STOCK",
                        array_merge($filters, ["page" => $page, "perPage" => 100]),
                    );
                },
                $limit,
            );
            foreach ($pageData["items"] as $row) {
                $definition["rows"][] = [
                    "Created Date" => $row["createdDate"] ?? "",
                    "Warranty Expiry" => $row["warranty_expiry"] ?? "",
                    "Asset Number" => $row["assetNum"] ?? "",
                    "Serial Number" => $row["serial_number"] ?? "",
                    "Description" => $row["Description"] ?? "",
                    "Category" => $row["assetKatName"] ?? "",
                    "Location" => $row["locationName"] ?? ($row["location"] ?? ""),
                    "Quantity" => $row["quantity"] ?? "",
                    "Unit" => $row["unit"] ?? "",
                    "Status" => $row["assetStatusName"] ?? "",
                    "Created By" => $row["createdBy"] ?? "",
                ];
            }
            $definition["title"] = "Asset InStock History Report";
            $definition["slug"] = "asset-instock-history";
            $definition["summary"] = "Filtered assets currently available in stock";
            $definition["event"] = "IN STOCK";
            $definition["total"] = $pageData["total"];
        } elseif ($reportKey === "expiry") {
            $this->requireAssetRole(
                ["ISSUER", "IT_MANAGER"],
                "Only an Administrator, Issuer or IT Manager can export asset expiry history.",
            );
            $filters = $this->warrantyExportFilters();
            $pageData = $this->collectReportPages(
                static function (int $page) use ($model, $filters): array {
                    return $model->getWarrantyTimelinePage(
                        array_merge($filters, ["page" => $page, "perPage" => 100]),
                    );
                },
                $limit,
            );
            foreach ($pageData["items"] as $row) {
                $remaining = (int) ($row["daysRemaining"] ?? 0);
                $status =
                    $remaining < 0
                        ? abs($remaining) . " days overdue"
                        : ($remaining <= 30
                            ? $remaining . " days remaining"
                            : "Active");
                $definition["rows"][] = [
                    "Purchase Date" => $row["purchase_date"] ?? "",
                    "Warranty Expiry" => $row["warranty_expiry"] ?? "",
                    "Asset Number" => $row["assetNum"] ?? "",
                    "Serial Number" => $row["serial_number"] ?? "",
                    "Description" => $row["Description"] ?? "",
                    "Category" => $row["category"] ?? "",
                    "Duration Days" => $row["durationDays"] ?? "",
                    "Warranty Status" => $status,
                    "Asset Status" => $row["assetStat"] ?? "",
                ];
            }
            $definition["title"] = "Asset Expiry History Report";
            $definition["slug"] = "asset-expiry-history";
            $definition["summary"] = "Filtered purchase-to-warranty duration records";
            $definition["event"] = "WARRANTY";
            $definition["from"] = $filters["from"];
            $definition["to"] = $filters["to"];
            $definition["total"] = $pageData["total"];
        } else {
            http_response_code(404);
            exit("Unknown report export.");
        }

        $columnSchemas = [
            "my-assets" => [
                "Issue Date",
                "Asset Number",
                "Serial Number",
                "Description",
                "Category",
                "Assigned To",
                "Issue Number",
                "Condition",
                "Purpose",
                "Acceptance",
            ],
            "my-history" => [
                "Issue Date",
                "Return Date",
                "Asset Number",
                "Serial Number",
                "Description",
                "Category",
                "Assigned To",
                "Issue Number",
                "Issue Status",
                "Return Number",
                "Return Condition",
                "Return Reason",
            ],
            "assigned" => [
                "Assignment Date & Time",
                "Request Number",
                "Issue Number",
                "Asset Number",
                "Serial Number",
                "Description",
                "Category",
                "Assigned To",
                "Department",
                "Assignment Type",
                "Status",
            ],
            "returns" => [
                "Return Date & Time",
                "Return Number",
                "Asset Number",
                "Serial Number",
                "Description",
                "Category",
                "Assigned To",
                "Department",
                "Condition",
                "Return Type",
                "Remarks",
                "Received By",
            ],
            "instock" => [
                "Created Date",
                "Warranty Expiry",
                "Asset Number",
                "Serial Number",
                "Description",
                "Category",
                "Location",
                "Quantity",
                "Unit",
                "Status",
                "Created By",
            ],
            "expiry" => [
                "Purchase Date",
                "Warranty Expiry",
                "Asset Number",
                "Serial Number",
                "Description",
                "Category",
                "Duration Days",
                "Warranty Status",
                "Asset Status",
            ],
        ];
        $normalizedRowsExceededLimit = count($definition["rows"]) > $limit;
        if ($normalizedRowsExceededLimit) {
            $definition["rows"] = array_slice($definition["rows"], 0, $limit);
        }
        $definition["columns"] =
            $columnSchemas[$reportKey] ??
            ($definition["rows"] ? array_keys($definition["rows"][0]) : []);
        $definition["truncated"] =
            $normalizedRowsExceededLimit ||
            $definition["total"] > count($definition["rows"]);
        return $definition;
    }

    /** Collect bounded server-side pages without loading a million rows. */
    private function collectReportPages(callable $loader, int $limit): array
    {
        $items = [];
        $page = 1;
        $total = 0;
        $totalPages = 1;
        do {
            $result = $loader($page);
            $total = (int) ($result["total"] ?? 0);
            $totalPages = (int) ($result["totalPages"] ?? 1);
            $remaining = $limit - count($items);
            if ($remaining <= 0) {
                break;
            }
            $items = array_merge(
                $items,
                array_slice($result["items"] ?? [], 0, $remaining),
            );
            $page++;
        } while ($page <= $totalPages && count($items) < $limit);

        return ["items" => $items, "total" => $total];
    }

    private function returnHistoryExportFilters(): array
    {
        $conditions = ["NEW", "GOOD", "DAMAGED"];
        $types = ["REISSUE", "SCRAP"];
        $sorts = [
            "date_desc",
            "date_asc",
            "category_asc",
            "category_desc",
            "condition_asc",
            "condition_desc",
            "type_asc",
            "type_desc",
            "asset_asc",
            "asset_desc",
            "reference_asc",
            "reference_desc",
            "description_asc",
            "description_desc",
            "custodian_asc",
            "custodian_desc",
            "remarks_asc",
            "remarks_desc",
            "receiver_asc",
            "receiver_desc",
        ];
        return [
            "search" => substr(trim((string) ($_GET["search"] ?? "")), 0, 100),
            "category" => substr(trim((string) ($_GET["category"] ?? "")), 0, 10),
            "condition" => in_array($_GET["condition"] ?? "", $conditions, true)
                ? $_GET["condition"]
                : "",
            "returnType" => in_array($_GET["returnType"] ?? "", $types, true)
                ? $_GET["returnType"]
                : "",
            "sort" => in_array($_GET["sort"] ?? "", $sorts, true)
                ? $_GET["sort"]
                : "date_desc",
        ];
    }

    private function warrantyExportFilters(): array
    {
        $defaultFrom = (new DateTimeImmutable("first day of January"))->format("Y-m-d");
        $defaultTo = (new DateTimeImmutable("last day of December +3 years"))->format(
            "Y-m-d",
        );
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
        return [
            "from" => $from,
            "to" => $to,
            "search" => trim((string) ($_GET["search"] ?? "")),
            "category" => trim((string) ($_GET["category"] ?? "ALL")),
            "status" => trim((string) ($_GET["status"] ?? "ALL")),
            "sort" => trim((string) ($_GET["sort"] ?? "DURATION_ASC")),
        ];
    }

    /** Map flexible report columns into the established branded PDF layout. */
    private function navigationReportPdfRow(array $row, string $event): array
    {
        $pick = static function (array $keys) use ($row): string {
            foreach ($keys as $key) {
                if (isset($row[$key]) && trim((string) $row[$key]) !== "") {
                    return (string) $row[$key];
                }
            }
            return "-";
        };
        $coreColumns = [
            "Issue Date",
            "Return Date",
            "Created Date",
            "Purchase Date",
            "Warranty Expiry",
            "Asset Number",
            "Description",
            "Assigned To",
            "Department",
        ];
        $details = [];
        foreach ($row as $label => $value) {
            if (!in_array($label, $coreColumns, true) && trim((string) $value) !== "") {
                $details[] = $label . ": " . $value;
            }
        }
        return [
            "eventDate" => $pick([
                "Return Date",
                "Issue Date",
                "Created Date",
                "Purchase Date",
            ]),
            "eventType" => $event,
            "assetNum" => $pick(["Asset Number"]),
            "purchaseDate" => $pick(["Purchase Date"]),
            "warrantyExpiry" => $pick(["Warranty Expiry"]),
            "assetDescription" => $pick(["Description"]),
            "assignedTo" => $pick(["Assigned To"]),
            "department" => $pick(["Department"]),
            "details" => $details ? implode(" / ", $details) : "-",
        ];
    }

    private function spreadsheetSafeValue($value): string
    {
        $value = (string) $value;
        return preg_match('/^[=+\-@]/', ltrim($value)) ? "'" . $value : $value;
    }

    private function escapeExportValue($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
    }

    private function formatAuditDateTime($value): string
    {
        $timestamp = strtotime((string) $value);
        return $timestamp === false
            ? (string) $value
            : date("Y-m-d h:i:s A", $timestamp);
    }

    private function auditDates()
    {
        $from = $_GET["from"] ?? date("Y-m-01");
        $to = $_GET["to"] ?? date("Y-m-d");
        if (
            !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) ||
            !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) ||
            $from > $to
        ) {
            $from = date("Y-m-01");
            $to = date("Y-m-d");
        }
        return [$from, $to];
    }

    private function auditFilters(): array
    {
        $assetNum = substr(trim((string) ($_GET["assetNum"] ?? "")), 0, 30);
        $eventType = strtoupper(trim((string) ($_GET["eventType"] ?? "")));
        $eventTypes = ["RECEIPT", "IN_STOCK", "ASSIGN", "RETURN", "ASSET_UPDATE", "DELETE"];
        if (defined("WORKFLOW_UI_ENABLED") ? WORKFLOW_UI_ENABLED : true) {
            $eventTypes[] = "SCRAP_APPROVED";
            $eventTypes[] = "SCRAP_REJECTED";
        }
        if (defined("ACCESSORY_INVENTORY_ENABLED") && ACCESSORY_INVENTORY_ENABLED) {
            $eventTypes[] = "ACCESSORY_RECEIPT";
            $eventTypes[] = "ACCESSORY_ISSUE";
            $eventTypes[] = "ACCESSORY_RETURN";
            $eventTypes[] = "ACCESSORY_ADJUSTMENT_IN";
            $eventTypes[] = "ACCESSORY_ADJUSTMENT_OUT";
        }
        if (!in_array($eventType, $eventTypes, true)) {
            $eventType = "";
        }
        $category = substr(trim((string) ($_GET["category"] ?? "")), 0, 100);
        $department = substr(trim((string) ($_GET["department"] ?? "")), 0, 50);
        $search = substr(trim((string) ($_GET["search"] ?? "")), 0, 100);
        $actor = substr(trim((string) ($_GET["actor"] ?? "")), 0, 100);
        $assignedTo = substr(trim((string) ($_GET["assignedTo"] ?? "")), 0, 100);
        $condition = substr(trim((string) ($_GET["condition"] ?? "")), 0, 50);
        return [$assetNum, $eventType, $category, $department, $search, $actor, $assignedTo, $condition];
    }

    // Keep the module root useful; the maintained entry form lives at /receive.
    public function index()
    {
        header("location: " . BASEURL . "/Asset/receive");
        exit();
    }

    public function receive()
    {
        $this->requireAssetRole(
            ["ISSUER"],
            "Only an Issuer or Administrator can enter assets.",
        );
        if (empty($_SESSION["csrf_asset_receive"])) {
            $_SESSION["csrf_asset_receive"] = bin2hex(random_bytes(32));
        }
        $data["title"] = "IT Asset - Receive Asset";
        $data["menu"] = "Asset Entry";
        $data["menu-dsc"] = "Receive and register a new asset";
        $data["runit"] = $this->model("Asset_model")->getUnit();
        $data["asetkat"] = $this->model("Assetkat_model")->getAllAssetCat();
        $data["location"] = $this->model("Location_model")->getAllData();
        $data["vendors"] = $this->model("Vendor_model")->getAll(true);
        $data["csrf"] = $_SESSION["csrf_asset_receive"];
        $data["asset"] = ["received_date" => date("Y-m-d")];
        $data["post_url"] = BASEURL . "/Asset/storeReceived";

        $this->view("templates/header_a", $data);
        $this->view("asset/receive", $data);
        $this->view("templates/footer_a");
    }

    public function storeReceived()
    {
        $this->requireAssetRole(
            ["ISSUER"],
            "Only an Issuer or Administrator can enter assets.",
        );
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header("location: " . BASEURL . "/Asset/receive");
            exit();
        }
        $token = $_POST["csrf_token"] ?? "";
        if (
            empty($_SESSION["csrf_asset_receive"]) ||
            !hash_equals($_SESSION["csrf_asset_receive"], $token)
        ) {
            Flasher::setMessage(
                "Asset entry failed.",
                "Invalid form token. Please try again.",
                "danger",
            );
            header("location: " . BASEURL . "/Asset/receive");
            exit();
        }

        try {
            $result = $this->model("Asset_model")->receiveAsset(
                $_POST,
                $_FILES["assetImages"] ?? null,
                $_FILES["invoicePdf"] ?? null,
            );
            unset($_SESSION["csrf_asset_receive"]);
            if (($result["assetCount"] ?? 1) > 1) {
                $first = $result["assetNums"][0];
                $last = $result["assetNums"][count($result["assetNums"]) - 1];
                Flasher::setMessage(
                    $result["assetCount"] . " assets " . $first . " to " . $last,
                    "received under " . $result["receiptNo"],
                    "success",
                );
            } else {
                Flasher::setMessage(
                    "Asset " . $result["assetNum"],
                    "received under " . $result["receiptNo"],
                    "success",
                );
            }
            header("location: " . BASEURL . "/Asset/inactive");
        } catch (Throwable $error) {
            Flasher::setMessage("Asset entry failed.", $error->getMessage(), "danger");
            header("location: " . BASEURL . "/Asset/receive");
        }
        exit();
    }

    public function assign()
    {
        $this->requireAssetRole(
            ["ISSUER"],
            "Only an Issuer or Administrator can assign assets.",
        );
        if (empty($_SESSION["csrf_asset_assign"])) {
            $_SESSION["csrf_asset_assign"] = bin2hex(random_bytes(32));
        }
        $data = [
            "title" => "IT Asset - Assign Asset",
            "menu" => "Assign Asset",
            "menu-dsc" => "Issue an available asset to a department or user",
            // Assets are searched remotely; never render the full inventory.
            "assets" => [],
            "departments" => $this->model("Department_model")->getAllDept(),
            "csrf" => $_SESSION["csrf_asset_assign"],
        ];
        $this->view("templates/header_a", $data);
        $this->view("asset/assign", $data);
        $this->view("templates/footer_a");
    }

    /** Select2 endpoint for the assignment asset picker. */
    public function searchAvailableAssets()
    {
        $this->requireAssetRole(
            ["ISSUER"],
            "Only an Issuer or Administrator can search assets for assignment.",
            true,
        );
        header("Content-Type: application/json; charset=utf-8");
        $term = trim((string) ($_GET["q"] ?? ""));
        $rows = $this->model("Asset_model")->searchAvailableAssets($term, 30);
        $results = array_map(static function (array $asset): array {
            $assetNum = (string) $asset["assetNum"];
            $description = (string) ($asset["Description"] ?? "");
            $category = (string) ($asset["category"] ?? "");
            $serial = trim((string) ($asset["serial_number"] ?? ""));
            return [
                "id" => $assetNum,
                "text" => $assetNum .
                    " - " .
                    $description .
                    ($serial !== "" ? " - S/N " . $serial : "") .
                    " - " .
                    $category,
                "description" => $description,
                "category" => $category,
                "status" => (string) ($asset["status_name"] ?? ""),
                "condition" => (string) ($asset["asset_condition"] ?? "NEW"),
                "image" => (string) ($asset["assetImage"] ?? ""),
            ];
        }, $rows);
        echo json_encode(["results" => $results], JSON_UNESCAPED_UNICODE);
    }

    private function inventoryFilters(): array
    {
        $perPage = (int) ($_GET["perPage"] ?? 50);
        if (!in_array($perPage, [25, 50, 100], true)) {
            $perPage = 50;
        }
        $condition = strtoupper(trim((string) ($_GET["condition"] ?? "ALL")));
        if (!in_array($condition, ["ALL", "NEW", "GOOD", "DAMAGED"], true)) {
            $condition = "ALL";
        }
        return [
            "search" => trim((string) ($_GET["search"] ?? "")),
            "category" => trim((string) ($_GET["category"] ?? "ALL")),
            "condition" => $condition,
            "page" => max(1, (int) ($_GET["page"] ?? 1)),
            "perPage" => $perPage,
        ];
    }

    private function activeAssetFilters(): array
    {
        $filters = $this->inventoryFilters();
        $assignmentType = strtoupper(trim((string) ($_GET["assignmentType"] ?? "")));
        $filters["assignmentType"] = in_array(
            $assignmentType,
            ["USER", "DEPARTMENT"],
            true,
        )
            ? $assignmentType
            : "";
        return $filters;
    }

    public function departmentHeads($deptId)
    {
        $this->requireAssetRole(
            ["ISSUER"],
            "Only an Issuer or Administrator can select a department receiver.",
            true,
        );
        header("Content-Type: application/json");
        echo json_encode($this->model("Asset_model")->getEmployeesByDepartment($deptId));
        exit();
    }

    public function assignmentEmployee($empId)
    {
        $this->requireAssetRole(
            ["ISSUER"],
            "Only an Issuer or Administrator can select an assignment receiver.",
            true,
        );
        header("Content-Type: application/json");
        $employee = $this->model("Asset_model")->getActiveEmployeeAssignmentData($empId);
        if (!$employee) {
            http_response_code(404);
        }
        echo json_encode($employee ?: ["error" => "Active employee not found"]);
        exit();
    }

    public function storeAssignment()
    {
        $this->requireAssetRole(
            ["ISSUER"],
            "Only an Issuer or Administrator can assign assets.",
        );
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header("location: " . BASEURL . "/Asset/assign");
            exit();
        }
        if (
            empty($_SESSION["csrf_asset_assign"]) ||
            !hash_equals($_SESSION["csrf_asset_assign"], $_POST["csrf_token"] ?? "")
        ) {
            Flasher::setMessage("Assignment failed.", "Invalid form token.", "danger");
            header("location: " . BASEURL . "/Asset/assign");
            exit();
        }
        try {
            $model = $this->model("Asset_model");
            $_POST["remarks"] = trim((string) ($_POST["remarks"] ?? ""));
            $result = $model->assignAsset($_POST, $_FILES["assignmentImages"] ?? null);
            if (!empty($result["employeeName"])) {
                try {
                    $delivery = (new AssignmentMailer())->send($result);
                    foreach ($result["issueIds"] ?? [$result["issueId"]] as $issueId) {
                        $model->logAssignmentEmail(
                            $issueId,
                            $delivery["recipients"] ?? ($result["employeeEmail"] ?? null),
                            $delivery,
                        );
                    }
                } catch (Throwable $mailError) {
                    error_log("Assignment email error: " . $mailError->getMessage());
                }
            }
            unset($_SESSION["csrf_asset_assign"]);
            $count = count($result["issueIds"] ?? [$result["issueId"]]);
            Flasher::setMessage(
                $count . " asset" . ($count === 1 ? "" : "s"),
                "stored successfully",
                "success",
            );
            header(
                "location: " .
                    BASEURL .
                    "/Asset/active?assignmentPdf=" .
                    implode(",", $result["issueIds"] ?? [$result["issueId"]]),
            );
        } catch (Throwable $error) {
            Flasher::setMessage("Assignment failed.", $error->getMessage(), "danger");
            header("location: " . BASEURL . "/Asset/assign");
        }
        exit();
    }

    public function returns($issueId = null)
    {
        $this->requireReturnProcessor();
        $assetModel = $this->model("Asset_model");
        if (empty($_SESSION["csrf_asset_return"])) {
            $_SESSION["csrf_asset_return"] = bin2hex(random_bytes(32));
        }
        $selectedIssueIds = array_values(
            array_unique(array_filter(array_map("intval", explode(",", (string) $issueId)))),
        );

        // One return transaction is limited to one ISS batch. A link containing
        // one issue expands to the remaining open assets in that batch; a link
        // containing several IDs is accepted only when every ID is in the same
        // batch as the first one.
        if ($selectedIssueIds) {
            $batchIssueIds = $assetModel->getOpenIssueBatchIds($selectedIssueIds[0]);
            if (count($selectedIssueIds) === 1) {
                if ($batchIssueIds) {
                    $selectedIssueIds = $batchIssueIds;
                }
            } elseif (array_diff($selectedIssueIds, $batchIssueIds)) {
                Flasher::setMessage(
                    "Return selection not loaded.",
                    "Select assets from one ISS batch only.",
                    "danger",
                );
                header("location: " . BASEURL . "/Asset/returns");
                exit();
            }
        }
        if (count($selectedIssueIds) > 8) {
            Flasher::setMessage(
                "Return selection not loaded.",
                "A maximum of 8 assets can be returned in one batch.",
                "danger",
            );
            header("location: " . BASEURL . "/Asset/returns");
            exit();
        }
        $returnPdfIds = array_values(
            array_unique(
                array_filter(
                    array_map(
                        "intval",
                        explode(",", (string) ($_GET["returnPdf"] ?? "")),
                    ),
                ),
            ),
        );
        if (count($returnPdfIds) > 8) {
            Flasher::setMessage(
                "Return PDF not opened.",
                "A maximum of 8 return records can be included in one PDF.",
                "danger",
            );
            $returnPdfIds = [];
        }
        $data = [
            "title" => "IT Asset - Return Asset",
            "menu" => "Return Asset",
            "menu-dsc" => "Return assigned assets to stock",
            "issues" => $assetModel->getOpenIssues($selectedIssueIds),
            "history" => $assetModel->getReturnHistory(),
            "selectedIssueId" => $selectedIssueIds[0] ?? null,
            "selectedIssueIds" => $selectedIssueIds,
            "returnPdfIds" => implode(",", $returnPdfIds),
            "returnSource" =>
                (string) ($_GET["source"] ?? "") === "requests"
                    ? "requests"
                    : "",
            "csrf" => $_SESSION["csrf_asset_return"],
        ];
        $this->view("templates/header_a", $data);
        $this->view("asset/return", $data);
        $this->view("templates/footer_a");
    }

    /** Remote Select2 endpoint for scalable return-assignment searches. */
    public function searchOpenIssues()
    {
        $this->requireReturnProcessor();
        header("Content-Type: application/json; charset=utf-8");
        $term = trim((string) ($_GET["q"] ?? ""));
        $custodian = trim((string) ($_GET["custodian"] ?? ""));
        $batchIssueId = max(0, (int) ($_GET["batchIssue"] ?? 0));
        $model = $this->model("Asset_model");
        if ($batchIssueId > 0) {
            $rows = $model->getOpenIssues($model->getOpenIssueBatchIds($batchIssueId));
        } else {
            $limit = $custodian !== "" && $term === "" ? 50 : 30;
            $rows = $model->searchOpenIssues($term, $custodian, $limit);
        }
        $results = array_map(static function (array $issue): array {
            $isDepartment = $issue["assignee_type"] === "DEPARTMENT";
            $holder = $isDepartment
                ? (string) ($issue["deptName"] ?? "")
                : (string) ($issue["employee_name"] ?? "");
            $custodianKey = $isDepartment
                ? "DEPARTMENT|" . (string) ($issue["deptId"] ?? "")
                : "USER|" . (string) ($issue["empId"] ?? "");
            $parts = array_filter([
                $issue["assetNum"],
                $issue["Description"],
                $issue["category"],
                trim((string) ($issue["batch_no"] ?? "")) ?:
                    (string) ($issue["issue_no"] ?? ""),
                $isDepartment ? $issue["deptName"] ?? null : $issue["empId"] ?? null,
                $isDepartment ? null : $issue["employee_name"] ?? null,
            ]);
            return [
                "id" => (string) $issue["issue_id"],
                "text" => implode(" - ", $parts),
                "asset" => (string) $issue["assetNum"],
                "description" => (string) ($issue["Description"] ?? ""),
                "category" => (string) ($issue["category"] ?? ""),
                "type" => (string) $issue["assignee_type"],
                "holder" => $holder,
                "custodian" => $custodianKey,
                "status" => (string) ($issue["asset_status"] ?? ""),
                "image" => (string) ($issue["assetImage"] ?? ""),
            ];
        }, $rows);
        echo json_encode(["results" => $results], JSON_UNESCAPED_UNICODE);
    }

    public function returnHistory()
    {
        $conditions = ["NEW", "GOOD", "DAMAGED"];
        $types = ["REISSUE", "SCRAP"];
        $sorts = [
            "date_desc",
            "date_asc",
            "category_asc",
            "category_desc",
            "condition_asc",
            "condition_desc",
            "type_asc",
            "type_desc",
            "asset_asc",
            "asset_desc",
            "reference_asc",
            "reference_desc",
            "description_asc",
            "description_desc",
            "custodian_asc",
            "custodian_desc",
            "remarks_asc",
            "remarks_desc",
            "receiver_asc",
            "receiver_desc",
        ];
        $filters = [
            "search" => substr(trim((string) ($_GET["search"] ?? "")), 0, 100),
            "category" => substr(trim((string) ($_GET["category"] ?? "")), 0, 10),
            "condition" => in_array($_GET["condition"] ?? "", $conditions, true)
                ? $_GET["condition"]
                : "",
            "returnType" => in_array($_GET["returnType"] ?? "", $types, true)
                ? $_GET["returnType"]
                : "",
            "sort" => in_array($_GET["sort"] ?? "", $sorts, true) ? $_GET["sort"] : "date_desc",
            "page" => max(1, (int) ($_GET["page"] ?? 1)),
            "perPage" => in_array((int) ($_GET["perPage"] ?? 50), [25, 50, 100], true)
                ? (int) ($_GET["perPage"] ?? 50)
                : 50,
        ];
        $model = $this->model("Asset_model");
        $returnPage = $model->getFilteredReturnHistory($filters);
        $data = [
            "title" => "IT Asset - Return History",
            "menu" => "Return History",
            "menu-dsc" => "Complete searchable asset return records",
            "reportPage" => true,
            "rows" => $returnPage["items"],
            "pagination" => $returnPage,
            "categories" => $model->getReturnHistoryCategories(),
            "conditions" => $conditions,
            "returnTypes" => $types,
            "filters" => $filters,
        ];
        $this->view("templates/header_a", $data);
        $this->view("asset/return-history", $data);
        $this->view("templates/footer_a");
    }

    public function storeReturn()
    {
        $this->requireReturnProcessor();
        $returnSource =
            (string) ($_POST["return_source"] ?? "") === "requests"
                ? "requests"
                : "";
        $returnRedirect =
            $returnSource === "requests"
                ? BASEURL . "/Asset/returnRequests"
                : BASEURL . "/Asset/returns";
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header("location: " . BASEURL . "/Asset/returns");
            exit();
        }
        if (
            empty($_SESSION["csrf_asset_return"]) ||
            !hash_equals($_SESSION["csrf_asset_return"], $_POST["csrf_token"] ?? "")
        ) {
            Flasher::setMessage("Return failed.", "Invalid form token.", "danger");
            header("location: " . $returnRedirect);
            exit();
        }
        try {
            $model = $this->model("Asset_model");
            $_POST["remarks"] = trim((string) ($_POST["remarks"] ?? ""));
            $result = $model->returnAssignedAsset($_POST, $_FILES["returnImages"] ?? null);
            if (!empty($result["employeeName"])) {
                try {
                    $delivery = (new AssignmentMailer())->sendReturn($result);
                    foreach ($result["issueIds"] ?? [$result["issueId"]] as $issueId) {
                        $model->logAssignmentEmail(
                            $issueId,
                            $delivery["recipients"] ?? ($result["employeeEmail"] ?? null),
                            $delivery,
                            "RETURN",
                        );
                    }
                } catch (Throwable $mailError) {
                    error_log("Return email error: " . $mailError->getMessage());
                }
            }
            unset($_SESSION["csrf_asset_return"]);
            $count = count($result["issueIds"] ?? [$result["issueId"]]);
            Flasher::setMessage(
                $count . " asset" . ($count === 1 ? "" : "s"),
                "stored successfully",
                "success",
            );
            if ($returnSource === "requests") {
                header("location: " . $returnRedirect);
            } else {
                header(
                    "location: " .
                        $returnRedirect .
                        "?returnPdf=" .
                        implode(",", $result["returnIds"] ?? []),
                );
            }
        } catch (Throwable $e) {
            Flasher::setMessage("Return failed.", $e->getMessage(), "danger");
            header("location: " . $returnRedirect);
        }
        exit();
    }

    public function edit($assetNum)
    {
        $this->requireAssetRole(
            ["ISSUER"],
            "Only an Issuer or Administrator can update assets.",
        );
        $model = $this->model("Asset_model");

        $data["title"] = "IT Asset - Update Asset";
        $data["menu"] = "Update Asset";
        $data["menu-dsc"] = "Search & Update Asset";

        $data["assetNum"] = $assetNum; // asset number
        $data["asset"] = $model->getAssetById($assetNum); // ✅ fetch full asset data
        $data["runit"] = $model->getUnit();
        $data["stts"] = $model->getAssetStatus();
        $data["asetkat"] = $this->model("Assetkat_model")->getAllAssetCat();
        $data["location"] = $this->model("Location_model")->getAllData();

        $this->view("templates/header_a", $data);
        $this->view("asset/update", $data);
        $this->view("templates/footer_a");
    }

    public function editEntry($assetNum)
    {
        $this->requireAssetRole(
            ["ISSUER"],
            "Only an Issuer or Administrator can edit asset entries.",
        );
        $model = $this->model("Asset_model");
        $asset = $model->getAssetEntryById($assetNum);
        if (!$asset) {
            Flasher::setMessage("Asset", "not found", "danger");
            header("location: " . BASEURL . "/Asset/inactive");
            exit();
        }
        if (($asset["assetStat"] ?? "") !== "IN_STOCK") {
            Flasher::setMessage(
                "Edit blocked",
                "Only in-stock assets can be edited. Assigned asset details are locked to preserve the handover record.",
                "danger",
            );
            header(
                "location: " .
                    BASEURL .
                    (($asset["assetStat"] ?? "") === "ASSIGNED"
                        ? "/Asset/active"
                        : "/Asset/inactive"),
            );
            exit();
        }
        if (empty($_SESSION["csrf_asset_receive"])) {
            $_SESSION["csrf_asset_receive"] = bin2hex(random_bytes(32));
        }
        $data = [
            "title" => "IT Asset - Edit Asset",
            "menu" => "Asset Entry",
            "menu-dsc" => "Edit received asset",
            "asset" => $asset,
            "runit" => $model->getUnit(),
            "asetkat" => $this->model("Assetkat_model")->getAllAssetCat(),
            "location" => $this->model("Location_model")->getAllData(),
            "vendors" => $this->model("Vendor_model")->getAll(),
            "csrf" => $_SESSION["csrf_asset_receive"],
            "post_url" => BASEURL . "/Asset/updateReceived",
        ];
        $this->view("templates/header_a", $data);
        $this->view("asset/receive", $data);
        $this->view("templates/footer_a");
    }

    public function updateReceived()
    {
        $this->requireAssetRole(
            ["ISSUER"],
            "Only an Issuer or Administrator can edit asset entries.",
        );
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header("location: " . BASEURL . "/Asset/inactive");
            exit();
        }
        if (
            empty($_SESSION["csrf_asset_receive"]) ||
            !hash_equals($_SESSION["csrf_asset_receive"], $_POST["csrf_token"] ?? "")
        ) {
            Flasher::setMessage("Asset update failed.", "Invalid form token.", "danger");
            header("location: " . BASEURL . "/Asset/inactive");
            exit();
        }
        try {
            $result = $this->model("Asset_model")->updateReceivedAsset(
                $_POST,
                $_FILES["assetImages"] ?? null,
                $_FILES["invoicePdf"] ?? null,
            );
            unset($_SESSION["csrf_asset_receive"]);
            Flasher::setMessage("Asset " . $result["assetNum"], "Updated successfully", "success");
            header("location: " . BASEURL . "/Asset/inactive");
        } catch (Throwable $e) {
            Flasher::setMessage("Asset update failed.", $e->getMessage(), "danger");
            header("location: " . BASEURL . "/Asset/inactive");
        }
        exit();
    }

    // Update asset form + handle update
    public function update()
    {
        $this->requireAssetRole(
            ["ISSUER"],
            "Only an Issuer or Administrator can update assets.",
        );
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $file = $_FILES["assetImage"] ?? null;

            $result = $this->model("Asset_model")->updateAsset($_POST, $file);

            if ($result > 0) {
                Flasher::setMessage(
                    "Asset " . $_POST["assetId"],
                    "Updated successfully",
                    "success",
                );
            } else {
                // Changed message for zero affected rows
                Flasher::setMessage("Asset " . $_POST["assetId"], "No changes were made", "info");
            }

            header("location: " . BASEURL . "/Asset/active");
            exit();
        }
    }

    public function delete($assetNum)
    {
        Flasher::setMessage(
            "Delete blocked",
            "Use the audited Delete button on the Disposed Asset page.",
            "danger",
        );
        header("location: " . BASEURL . "/Asset/disposed");
        exit();
    }

    //     // Load dropdown data
    //     $data['title'] = 'IT Asset - Update Asset';
    //     $data['menu'] = 'Update Asset';
    //     $data['menu-dsc'] = 'Search & Update Asset';
    //     $data['runit'] = $model->getUnit();
    //     $data['stts'] = $model->getAssetStatus();
    //     $data['asetkat'] = $this->model('Assetkat_model')->getAllAssetCat();
    //     $data['location'] = $this->model('Location_model')->getAllData();
    //     $data['asset'] = null;

    //     $this->view('templates/header_a', $data);
    //     $this->view('asset/update', $data);
    //     $this->view('templates/footer_a');
    // }

    // Save new asset
    // AJAX: Get asset by ID
    public function getAssetById($assetNum)
    {
        $this->requireAssetRole(
            ["ISSUER"],
            "Only an Issuer or Administrator can load assets for editing.",
            true,
        );
        $asset = $this->model("Asset_model")->getAssetById($assetNum);
        header("Content-Type: application/json");
        echo json_encode($asset);
    }

    // Save new asset
    public function create()
    {
        $this->requireAssetRole(
            ["ISSUER"],
            "Only an Issuer or Administrator can create assets.",
        );
        $last = $this->model("Asset_model")->getLastAssetNum();
        $assetNum = $last["assetNum"] ? $last["assetNum"] + 1 : 1000000001;

        $file = $_FILES["assetImage"] ?? null;
        if ($this->model("Asset_model")->saveAsset($assetNum, $_POST, $file) > 0) {
            Flasher::setMessage("Asset " . $assetNum, "Created", "success");
        } else {
            Flasher::setMessage("Failed,", "Check your input", "danger");
        }
        header("location: " . BASEURL . "/Asset/list");
        exit();
    }

    // public function update() {
    //     $file = $_FILES['assetImage'] ?? null;
    //     if($this->model('Asset_model')->updateAsset($_POST, $file) > 0) {
    //         Flasher::setMessage('Asset ' . $_POST['assetId'], 'Updated', 'success');
    //     } else {
    //         Flasher::setMessage('Failed,', 'Check your input', 'danger');
    //     }
    //     header('location: ' . BASEURL . '/Asset/update');
    //     exit;
    // }

    // Display all assets
    public function display()
    {
        header("location: " . BASEURL . "/Asset/active");
        exit();
    }

    // Get details of a specific asset for modal
    public function details($assetNum)
    {
        $this->requireAssetRole(
            ["ISSUER", "IT_MANAGER"],
            "You do not have permission to view detailed asset transactions.",
            true,
        );
        $model = $this->model("Asset_model");
        $asset = $model->getAssetById($assetNum);

        if (!$asset) {
            $asset = $model->getArchivedAssetDetails($assetNum);
        }

        if (!$asset) {
            header("Content-Type: application/json");
            http_response_code(404);
            echo json_encode(["error" => "Asset not found"]);
            return;
        }

        // Archived payloads already contain their retained media and timeline.
        if (($asset["assetStat"] ?? "") === "ARCHIVED") {
            header("Content-Type: application/json");
            echo json_encode($asset);
            return;
        }

        if (!array_key_exists("assetImage", $asset)) {
            error_log("Asset {$assetNum} returned but assetImage field missing!");
        }

        $history = $model->getAssetTransactionHistory($assetNum);
        $asset["media"] = $model->getAssetMedia($assetNum);
        $asset["transactions"] = $history["transactions"] ?? [];
        $asset["timeline"] = $model->getAuditReport(
            "2000-01-01",
            date("Y-m-d", strtotime("+1 day")),
            $assetNum,
            "",
            "",
            "",
            "",
            "",
            "",
            "",
            0,
            defined("WORKFLOW_UI_ENABLED") ? WORKFLOW_UI_ENABLED : true,
        );
        header("Content-Type: application/json");
        echo json_encode($asset);
    }

    /**
     * Protect sensitive asset routes independently from navigation visibility.
     * The built-in admin username and ADMINISTRATOR role always have full access.
     */
    private function requireAssetRole(
        array $allowedRoles,
        string $message,
        bool $jsonResponse = false
    ): void {
        $username = (string) ($_SESSION["usr"]["user"] ?? "");
        $account = $this->model("User_model")->getUserById($username);
        $isBuiltInAdmin = strtolower($username) === "admin";
        $isActiveAccount =
            is_array($account) && (int) ($account["active"] ?? 0) === 1;
        $roles = $isActiveAccount ? ($account["roles"] ?? []) : [];
        $allowed =
            $isBuiltInAdmin ||
            ($isActiveAccount &&
                (
                    in_array("ADMINISTRATOR", $roles, true) ||
                    (bool) array_intersect($allowedRoles, $roles)
                ));

        if ($allowed) {
            return;
        }

        http_response_code(403);
        if ($jsonResponse) {
            header("Content-Type: application/json");
            echo json_encode(["error" => $message]);
            exit();
        }

        exit($message);
    }

    /** Accept only a basename with one of the explicitly allowed extensions. */
    private function validatedStoredFileName(
        string $fileName,
        array $allowedExtensions
    ): ?string {
        if (
            $fileName === "" ||
            $fileName !== basename($fileName) ||
            !preg_match("/^[A-Za-z0-9][A-Za-z0-9._-]{0,254}$/", $fileName)
        ) {
            return null;
        }

        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        return in_array($extension, $allowedExtensions, true) ? $fileName : null;
    }

    /** Stream a validated private file without exposing its physical path. */
    private function streamPrivateFile(
        string $path,
        string $downloadName,
        bool $attachment
    ): void {
        if (!is_file($path) || !is_readable($path)) {
            $this->fileNotFound();
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
        $allowedMimes = [
            "application/pdf",
            "image/jpeg",
            "image/png",
            "image/webp",
        ];
        if (!in_array($mime, $allowedMimes, true)) {
            $this->fileNotFound();
        }

        header("Content-Type: " . $mime);
        header("X-Content-Type-Options: nosniff");
        header("Cache-Control: private, no-store, max-age=0");
        header("Content-Length: " . filesize($path));
        header(
            "Content-Disposition: " .
                ($attachment ? "attachment" : "inline") .
                '; filename="' .
                addcslashes(basename($downloadName), "\\\"") .
                '"',
        );
        readfile($path);
        exit();
    }

    private function fileNotFound(): void
    {
        http_response_code(404);
        exit("File not found.");
    }
}
