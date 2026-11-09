<?php

class SimplePdfReport
{
    /** Download a compact landscape table report for summary-style exports. */
    public static function downloadTable(
        string $title,
        string $subtitle,
        array $headers,
        array $rows,
        string $filename,
        string $logoPath,
    ): void {
        $pageWidth = 842;
        $pageHeight = 595;
        $margin = 28;
        $tableWidth = $pageWidth - ($margin * 2);
        $columnCount = max(1, count($headers));
        $columnWidth = $tableWidth / $columnCount;
        $rowsPerPage = 18;
        $pages = array_chunk($rows ?: [array_fill(0, $columnCount, "No records")], $rowsPerPage);
        $objects = [1 => "<< /Type /Catalog /Pages 2 0 R >>"];
        $pageIds = [];
        $nextObject = 3;
        $logoObject = $nextObject++;
        $fontRegular = $nextObject++;
        $fontBold = $nextObject++;
        $logo = self::jpegData($logoPath);

        foreach ($pages as $pageIndex => $pageRows) {
            $pageObject = $nextObject++;
            $contentObject = $nextObject++;
            $pageIds[] = $pageObject . " 0 R";
            $content = "0.02 0.32 0.27 rg 0 0 {$pageWidth} {$pageHeight} re f\n";
            self::text($content, "F2", 18, $margin, 552, $title, [1, 1, 1]);
            self::text($content, "F1", 9, $margin, 533, $subtitle, [0.86, 0.94, 0.92]);
            $logoWidth = 58;
            $logoHeight = $logo["height"] * ($logoWidth / $logo["width"]);
            $content .= "q " . self::num($logoWidth) . " 0 0 " . self::num($logoHeight) . " 752 526 cm /ImLogo Do Q\n";

            $headerY = 488;
            $rowHeight = 23;
            $content .= "0.10 0.42 0.36 rg {$margin} {$headerY} {$tableWidth} 28 re f\n";
            foreach ($headers as $column => $header) {
                $x = $margin + ($column * $columnWidth);
                self::cell($content, (string) $header, $x + 4, $headerY + 10, (int) $columnWidth - 8, 2, true, [1, 1, 1], 8);
            }
            $y = $headerY;
            foreach ($pageRows as $rowIndex => $row) {
                $y -= $rowHeight;
                $fill = $rowIndex % 2 === 0 ? "0.96 0.98 0.98" : "1 1 1";
                $content .= "{$fill} rg {$margin} {$y} {$tableWidth} {$rowHeight} re f\n";
                $content .= "0.82 0.87 0.87 RG .4 w {$margin} {$y} {$tableWidth} {$rowHeight} re S\n";
                foreach (array_values($row) as $column => $value) {
                    if ($column >= $columnCount) break;
                    $x = $margin + ($column * $columnWidth);
                    if ($column > 0) {
                        $content .= "0.86 0.89 0.89 RG .3 w {$x} {$y} m {$x} " . ($y + $rowHeight) . " l S\n";
                    }
                    self::cell($content, (string) $value, $x + 4, $y + 8, (int) $columnWidth - 8, 2, false, [0.12, 0.18, 0.24], 7.4);
                }
            }
            self::text($content, "F1", 8, $margin, 18, "Generated " . date("Y-m-d H:i") . " | UNICOIL IT Asset Management", [0.55, 0.65, 0.63]);
            self::text($content, "F2", 8, 770, 18, "Page " . ($pageIndex + 1) . " / " . count($pages), [0.55, 0.65, 0.63]);
            $objects[$pageObject] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$pageWidth} {$pageHeight}] /Resources << /Font << /F1 {$fontRegular} 0 R /F2 {$fontBold} 0 R >> /XObject << /ImLogo {$logoObject} 0 R >> >> /Contents {$contentObject} 0 R >>";
            $objects[$contentObject] = "<< /Length " . strlen($content) . ">>\nstream\n{$content}\nendstream";
        }
        $objects[2] = "<< /Type /Pages /Kids [" . implode(" ", $pageIds) . "] /Count " . count($pageIds) . " >>";
        $objects[$logoObject] = "<< /Type /XObject /Subtype /Image /Width {$logo["width"]} /Height {$logo["height"]} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length " . strlen($logo["bytes"]) . ">>\nstream\n{$logo["bytes"]}\nendstream";
        $objects[$fontRegular] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
        $objects[$fontBold] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>";
        ksort($objects);
        $pdf = self::assemble($objects);
        header("Content-Type: application/pdf");
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        header("Content-Length: " . strlen($pdf));
        echo $pdf;
        exit();
    }

    public static function downloadAssignment(
        array $record,
        string $filename,
        string $logoPath,
    ): void {
        $pdf = self::buildAssignment($record, $logoPath);
        header("Content-Type: application/pdf");
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        header("Content-Length: " . strlen($pdf));
        echo $pdf;
        exit();
    }

    public static function downloadReturn(array $record, string $filename, string $logoPath): void
    {
        $pdf = self::buildAssignment($record, $logoPath, "RETURN");
        header("Content-Type: application/pdf");
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        header("Content-Length: " . strlen($pdf));
        echo $pdf;
        exit();
    }

    public static function buildAssignment(
        array $r,
        string $logoPath,
        string $formType = "ASSIGNMENT",
    ): string {
        $isReturn = $formType === "RETURN";
        $regular = 5;
        $bold = 6;
        $logoObject = 7;
        $content = "";
        $pageWidth = 595;
        $value = static fn($v): string => trim((string) ($v ?? "")) !== ""
            ? trim((string) $v)
            : "-";
        $firstValue = static function (array $values): string {
            foreach ($values as $candidate) {
                $candidate = trim((string) ($candidate ?? ""));
                if ($candidate !== "") {
                    return $candidate;
                }
            }
            return "-";
        };
        $centerTextX = static function (string $text, float $size) use ($pageWidth): float {
            $charWidth = $size * 0.52;
            $textWidth = strlen($text) * $charWidth;
            return max(40, min($pageWidth - 40 - $textWidth, ($pageWidth - $textWidth) / 2));
        };
        // Material document reference follows the lifecycle of each form:
        // Assignment: REQ number until receiver acceptance completes the request,
        //             then the generated ISS number.
        // Return:     RRQ number until Issuer/Administrator receives the asset,
        //             then the generated return transaction number.
        $requestNumber = $firstValue([
            $r["request_no"] ?? null,
            $r["requestNo"] ?? null,
        ]);
        $issueNumber = $firstValue([
            $r["issue_no"] ?? null,
            $r["issueNo"] ?? null,
            $r["batch_no"] ?? null,
        ]);
        $requestStatus = strtoupper(trim((string) (
            $r["request_status"] ?? ($r["requestStatus"] ?? "")
        )));

        if ($isReturn) {
            $returnRequestNumber = $firstValue([
                $r["return_request_no"] ?? null,
                $r["returnRequestNo"] ?? null,
            ]);
            $returnNumber = $firstValue([
                $r["actual_return_no"] ?? null,
                $r["return_no"] ?? null,
                $r["returnNo"] ?? null,
            ]);
            $returnRequestStatus = strtoupper(trim((string) (
                $r["return_request_status"] ?? ($r["returnRequestStatus"] ?? "")
            )));
            $returnReceived = $returnRequestStatus === "COMPLETED" ||
                !empty($r["return_id"]);

            if ($returnReceived && $returnNumber !== "-") {
                $materialDocumentNo = $returnNumber;
            } elseif ($returnRequestNumber !== "-") {
                $materialDocumentNo = $returnRequestNumber;
            } else {
                // A completed/legacy return form may not have a linked return
                // request, so use its actual return transaction number.
                $materialDocumentNo = $returnNumber;
            }
        } elseif ($requestNumber !== "-") {
            $materialDocumentNo = $requestStatus === "COMPLETED" && $issueNumber !== "-"
                ? $issueNumber
                : $requestNumber;
        } else {
            // Standalone/manual assignments are not linked to a request.
            $materialDocumentNo = $issueNumber;
        }
        $departmentPrefix = trim((string) ($r["departmentPrefix"] ?? ""));
        if ($departmentPrefix === "") {
            $departmentWords = preg_split(
                "/\s+/",
                trim((string) ($r["deptName"] ?? "")),
                -1,
                PREG_SPLIT_NO_EMPTY,
            );
            if (count($departmentWords) > 1) {
                $departmentPrefix = implode(
                    "",
                    array_map(
                        static fn($word): string => strtoupper(substr($word, 0, 1)),
                        $departmentWords,
                    ),
                );
            } elseif ($departmentWords) {
                $departmentPrefix = strtoupper(substr($departmentWords[0], 0, 3));
            }
        }
        $departmentDocumentId = $departmentPrefix;
        $departmentRawId = trim((string) ($r["deptId"] ?? ""));
        if ($departmentRawId !== "") {
            $formattedDepartmentId = ctype_digit($departmentRawId)
                ? str_pad($departmentRawId, 4, "0", STR_PAD_LEFT)
                : $departmentRawId;
            $departmentDocumentId .=
                ($departmentDocumentId !== "" ? "-" : "") . $formattedDepartmentId;
        }
        $line = static function (
            string &$c,
            float $x1,
            float $y1,
            float $x2,
            float $y2,
            float $width = 0.6,
        ): void {
            $c .= "0 0 0 RG $width w $x1 $y1 m $x2 $y2 l S\n";
        };
        $box = static function (string &$c, float $x, float $y, float $w, float $h): void {
            $c .= "0 0 0 RG .5 w $x $y $w $h re S\n";
        };
        $logo = self::jpegData($logoPath);
        $dw = 78;
        $dh = $logo["height"] * ($dw / $logo["width"]);
        // Header layout:
        // - Company name at the top-left.
        // - UNICOIL logo at the top-right.
        // - Department name and form title remain centred below.
        $logoX = 557 - $dw;
        $logoY = 832 - $dh;
        $content .=
            "q " . self::num($dw) . " 0 0 " . self::num($dh) . " " .
            self::num($logoX) . " " . self::num($logoY) . " cm /ImLogo Do Q" . "\n";

        self::text(
            $content,
            "F2",
            11.5,
            40,
            792,
            "UNIVERSAL METAL COATING COMPANY LIMITED (UNICOIL)",
            [0, 0, 0],
        );

        self::text(
            $content,
            "F1",
            11,
            $centerTextX("Information Technology Department", 11),
            742,
            "Information Technology Department",
            [0, 0, 0],
        );
        $isDepartment =
            strtoupper((string) ($r["assignee_type"] ?? ($r["assigneeType"] ?? ""))) ===
            "DEPARTMENT";
        $formTitle = self::formTitle($isReturn, $isDepartment);
        self::text(
            $content,
            "F2",
            14.5,
            $centerTextX($formTitle, 14.5),
            716,
            $formTitle,
            [0, 0, 0],
        );
        // Section divider removed for a cleaner layout.
        $informationTitle = $isDepartment ? "Department Information" : "User Information";
        self::text($content, "F2", 11.5, 40, 665, $informationTitle, [0, 0, 0]);
        if ($isDepartment) {
            $fields = [
                [40, 630, 180, "Department ID: " . $value($departmentDocumentId)],
                [
                    220,
                    630,
                    185,
                    "Section Head: " .
                    $value($r["departmentHeadName"] ?? ($r["acknowledged_by"] ?? "")),
                ],
                [405, 630, 150, "Area: " . $value(
                    $r["departmentArea"] ?? ($r["departmentHeadArea"] ?? ""),
                )],
                [40, 600, 180, "Department Name: " . $value($r["deptName"] ?? "")],
                [220, 600, 185, "Head Email: " . $value($r["departmentHeadEmail"] ?? "")],
                [405, 600, 150, "Building: " . $value(
                    $r["departmentBuilding"] ?? ($r["departmentHeadBuilding"] ?? ""),
                )],
                [40, 570, 180, "Extension Number: " . $value($r["departmentHeadExtension"] ?? "")],
                [220, 570, 335, "Material document No: " . $materialDocumentNo],
                /* Section field temporarily hidden. [445,570,110,'Section: -'], */
            ];
        } else {
            // Assignment and return records may expose employee data under
            // different keys. Check the return-specific keys and the first
            // returned asset as fallbacks. $firstValue() skips empty strings.
            $firstAsset =
                !empty($r["assets"]) && is_array($r["assets"])
                    ? (array) ($r["assets"][0] ?? [])
                    : [];

            $employeeId = $firstValue([
                $r["empId"] ?? null,
                $r["employeeId"] ?? null,
                $r["employee_id"] ?? null,
                $r["emp_id"] ?? null,
                $r["returnedByEmpId"] ?? null,
                $r["returned_by_emp_id"] ?? null,
                $r["assigneeId"] ?? null,
                $r["assignee_id"] ?? null,
                $r["assignedEmpId"] ?? null,
                $r["assigned_emp_id"] ?? null,
                $firstAsset["empId"] ?? null,
                $firstAsset["employeeId"] ?? null,
                $firstAsset["employee_id"] ?? null,
                $firstAsset["emp_id"] ?? null,
                $firstAsset["assigneeId"] ?? null,
                $firstAsset["assignedEmpId"] ?? null,
            ]);

            $employeeEmail = $firstValue([
                $r["employeeEmail"] ?? null,
                $r["empEmail"] ?? null,
                $r["employee_email"] ?? null,
                $r["email"] ?? null,
                $r["returnedByEmail"] ?? null,
                $r["returned_by_email"] ?? null,
                $r["assigneeEmail"] ?? null,
                $r["assignee_email"] ?? null,
                $firstAsset["employeeEmail"] ?? null,
                $firstAsset["empEmail"] ?? null,
                $firstAsset["employee_email"] ?? null,
                $firstAsset["email"] ?? null,
            ]);

            $fields = [
                [
                    40,
                    630,
                    220,
                    "ID / Email: " . $employeeId . " / " . $employeeEmail,
                ],
                [
                    260,
                    630,
                    190,
                    "Position: " . $value($r["employeePosition"] ?? ""),
                ],
                [
                    450,
                    630,
                    105,
                    "Location: " . $value($r["employeeArea"] ?? ""),
                ],
                [
                    40,
                    600,
                    220,
                    "Name: " .
                    $value($r["employeeName"] ?? ($r["acknowledged_by"] ?? "")),
                ],
                [
                    260,
                    600,
                    190,
                    "Department: " . $value($r["deptName"] ?? ""),
                ],
                [
                    450,
                    600,
                    105,
                    "Building: " . $value($r["employeeBuilding"] ?? ""),
                ],
                [
                    40,
                    570,
                    220,
                    "Extension Number: " . $value($r["employeeExtension"] ?? ""),
                ],
                [
                    260,
                    570,
                    295,
                    "Material document No: " . $materialDocumentNo,
                ],
            ];
        }

        // Fields that usually contain longer identifiers use a smaller font.
        foreach ($fields as [$x, $y, $w, $text]) {
            $box($content, $x, $y, $w, 22);

            $fontSize = strpos($text, "Material document No:") === 0
                ? 7.3
                : (strpos($text, "ID / Email:") === 0
                    ? 7.0
                    : 8.0);
            self::cell(
                $content,
                $text,
                $x + 5,
                $y + 8,
                (int) $w - 10,
                1,
                false,
                [0, 0, 0],
                $fontSize,
            );
        }

        // Assignment-only issue type block. The checkboxes are intentionally left blank
        // so the printed form can be marked manually.
        if (!$isReturn) {
            self::text($content, "F2", 11.5, 42, 535, "Issue Type", [0, 0, 0]);

            $issueTypeY = 507;

            $box($content, 42, $issueTypeY - 2, 10, 10);
            self::text($content, "F1", 10, 58, $issueTypeY, "Permanent", [0, 0, 0]);

            $box($content, 145, $issueTypeY - 2, 10, 10);
            self::text($content, "F1", 10, 161, $issueTypeY, "Temporary", [0, 0, 0]);

            self::text(
                $content,
                "F1",
                10,
                255,
                $issueTypeY,
                "Expected Return Date (if Temporary): ____________",
                [0, 0, 0],
            );
        }

        $assetTitleY = $isReturn ? 480 : 475;
        self::text($content, "F2", 11.5, 42, $assetTitleY, "Asset Information", [0, 0, 0]);
        $assets = array_slice(
            !empty($r["assets"]) && is_array($r["assets"]) ? $r["assets"] : [$r],
            0,
            8,
        );
        // Asset table: all headers and values are centred with internal padding.
        // If every asset has the same remarks, the Remarks cells are merged into
        // one full-height column. Different remarks remain separate per asset row.
        // Give Remarks enough width to remain useful while preserving compact
        // columns for serial number, asset number, and condition.
        // Remarks receives extra width because it can contain both the linked
        // request number and the issuer's free-text notes.
        $x = [40, 90, 158, 253, 343, 398, 445, 555];
        $headers = ["Serial No.", "Asset Type", "Asset Name", "Serial Number", "Asset No.", "Condition", "Remarks"];
        $tableTop = $isReturn ? 462 : 457;
        $headerHeight = 24;
        $assetCount = max(1, count($assets));
        $minimumTableBottom = $isReturn ? 249 : 257;
        $availableRowsHeight = $tableTop - $headerHeight - $minimumTableBottom;

        // Short lists receive taller rows for readable wrapped remarks. The
        // height gradually returns to 22 points when all eight rows are used.
        $rowHeight = min(44, max(22, floor($availableRowsHeight / $assetCount)));
        $tableBottom = $tableTop - $headerHeight - count($assets) * $rowHeight;
        $remarkLinesPerRow = max(1, min(4, (int) floor(($rowHeight - 8) / 10.5)));

        // Resolve each asset remark once so the comparison and rendering use
        // exactly the same fallback rules.
        $resolvedRemarks = [];
        foreach ($assets as $asset) {
            $remark = trim((string) (
                $asset["remarks"] ??
                $asset["purpose"] ??
                $asset["reason"] ??
                $r["remarks"] ??
                $r["purpose"] ??
                $r["reason"] ??
                ""
            ));
            if (!$isReturn && $remark !== "") {
                // Older records may contain the request reference inside the
                // purpose field. It now belongs beside the material document
                // number, so keep only a genuine issuer remark in this column.
                $remark = preg_replace(
                    [
                        "/(?:^|\R)\s*-\s*Request:\s*[^\r\n]*/i",
                        "/^\s*Issued from equipment request\s+\S+\s*$/i",
                        "/^\s*-\s*Issuer remarks:\s*/i",
                    ],
                    ["", "", ""],
                    $remark,
                );
                $remark = trim((string) $remark);
            }
            $resolvedRemarks[] = $remark !== "" ? $remark : "-";
        }

        // Case-insensitive comparison avoids treating "Good" and "good" as
        // different remarks. Whitespace is also normalised.
        $normalisedRemarks = array_map(
            static fn(string $remark): string => strtolower(
                trim((string) preg_replace("/\s+/", " ", $remark))
            ),
            $resolvedRemarks,
        );
        $sameRemarks = count(array_unique($normalisedRemarks)) <= 1;

        $box($content, 40, $tableBottom, 515, $tableTop - $tableBottom);

        foreach (array_slice($x, 1, -1) as $vx) {
            $line($content, $vx, $tableBottom, $vx, $tableTop, 0.4);
        }

        $headerBottom = $tableTop - $headerHeight;
        $line($content, 40, $headerBottom, 555, $headerBottom, 0.4);

        foreach ($headers as $i => $header) {
            $headerFontSize = in_array($i, [0, 4, 5], true) ? 7.0 : 7.6;
            self::centeredCell(
                $content,
                $header,
                $x[$i],
                $headerBottom,
                $x[$i + 1] - $x[$i],
                $headerHeight,
                1,
                true,
                $headerFontSize,
                2,
            );
        }

        foreach ($assets as $index => $asset) {
            $rowTop = $headerBottom - $index * $rowHeight;
            $rowBottom = $rowTop - $rowHeight;

            if ($index < count($assets) - 1) {
                // Keep the Remarks column visually merged when all remarks match.
                $rowLineEnd = $sameRemarks ? $x[6] : 555;
                $line($content, 40, $rowBottom, $rowLineEnd, $rowBottom, 0.35);
            }

            $condition = $isReturn
                ? $asset["condition_in"] ??
                    ($asset["conditionIn"] ?? ($r["condition_in"] ?? ""))
                : $asset["condition_out"] ??
                    ($asset["conditionOut"] ?? ($r["condition_out"] ?? ($r["conditionOut"] ?? "")));

            $rowValues = [
                (string) ($index + 1),
                $value($asset["categoryName"] ?? ($asset["assetCategory"] ?? "")),
                $value($asset["assetDescription"] ?? ($asset["Description"] ?? "")),
                $value($asset["serial_number"] ?? ($asset["serialNumber"] ?? "")),
                $value($asset["assetNum"] ?? ""),
                $value(
                    ucwords(
                        strtolower(
                            str_replace("_", " ", (string) $condition),
                        ),
                    ),
                ),
            ];

            // Render the first six columns for every asset.
            foreach ($rowValues as $column => $cellValue) {
                $maxLines = in_array($column, [1, 2, 3, 5], true) ? 2 : 1;
                $fontSize = in_array($column, [1, 2], true)
                    ? 7.5
                    : ($column === 3
                        ? 7.0
                        : ($column === 5
                            ? 7.2
                            : 7.8));

                self::centeredCell(
                    $content,
                    $cellValue,
                    $x[$column],
                    $rowBottom,
                    $x[$column + 1] - $x[$column],
                    $rowHeight,
                    $maxLines,
                    false,
                    $fontSize,
                    4,
                );
            }

            // Different remarks are shown separately in their corresponding rows.
            if (!$sameRemarks) {
                self::centeredCell(
                    $content,
                    $resolvedRemarks[$index],
                    $x[6],
                    $rowBottom,
                    $x[7] - $x[6],
                    $rowHeight,
                    $remarkLinesPerRow,
                    false,
                    7.5,
                    4,
                );
            }
        }

        // One shared remark is centred in a full-height merged Remarks column.
        if ($sameRemarks && $resolvedRemarks) {
            self::centeredCell(
                $content,
                $resolvedRemarks[0],
                $x[6],
                $tableBottom,
                $x[7] - $x[6],
                $headerBottom - $tableBottom,
                max(
                    1,
                    min(
                        12,
                        (int) floor(
                            ($headerBottom - $tableBottom - 8) / 9.5,
                        ),
                    ),
                ),
                false,
                7.5,
                4,
            );
        }

        // Signature layout:
        // Assignment: Requester Manager, IT Manager, Issuer, Receiver.
        // Return: Returned by, Received by.
        $signatures = $isReturn
            ? ["Returned by", "Received by"]
            : ["Requester Manager", "IT Manager", "Issuer", "Receiver"];

        $signatureLeft = 42;
        $signatureRight = 553;
        $signatureGap = $isReturn ? 18 : 6;
        $signatureCount = count($signatures);
        $signatureWidth =
            ($signatureRight - $signatureLeft - ($signatureCount - 1) * $signatureGap) /
            $signatureCount;

        // Keep the signature section fixed in the footer.
        $blockBottom = 58;
        $blockTop = $isReturn ? 176 : 184;
        $headerHeight = $isReturn ? 24 : 34;

        // Place the acknowledgement immediately above the signature section.
        $agreementLine = $blockTop + 72;

        $agreementTitle = $isReturn
            ? "Asset Return Acknowledgement"
            : "User Receiving Agreement";
        $agreementSubtitle = "(Read and acknowledge by signing below)";
        $agreementText = $isReturn
            ? "The assets listed above have been returned to IT and their recorded conditions have been acknowledged."
            : "I acknowledge receipt of the assets listed above in good condition and accept responsibility for their proper use and care.";

        self::text(
            $content,
            "F2",
            11.5,
            $centerTextX($agreementTitle, 11.5),
            $agreementLine - 18,
            $agreementTitle,
            [0, 0, 0],
        );
        self::text(
            $content,
            "F1",
            10.5,
            $centerTextX($agreementSubtitle, 10.5),
            $agreementLine - 37,
            $agreementSubtitle,
            [0, 0, 0],
        );
        self::centeredCell(
            $content,
            $agreementText,
            42,
            $blockTop + 4,
            511,
            24,
            2,
            false,
            8.2,
            6,
            [0, 0, 0],
        );

        foreach ($signatures as $i => $label) {
            $sx = $signatureLeft + $i * ($signatureWidth + $signatureGap);
            $box($content, $sx, $blockBottom, $signatureWidth, $blockTop - $blockBottom);
            $line($content, $sx, $blockTop - $headerHeight, $sx + $signatureWidth, $blockTop - $headerHeight, 0.5);

            $labelSize = strlen($label) > 16 ? 8.2 : 9.0;
            $estimatedLabelWidth = strlen($label) * $labelSize * 0.48;
            $labelX = $sx + max(3, ($signatureWidth - $estimatedLabelWidth) / 2);
            self::text($content, "F2", $labelSize, $labelX, $blockTop - 14, $label, [0, 0, 0]);

            // Requester Manager requires a printed email-approval choice.
            if (!$isReturn && $i === 0) {
                self::text($content, "F1", 7.2, $sx + 4, $blockTop - 27, "Email approval?", [0, 0, 0]);
                $box($content, $sx + 61, $blockTop - 31, 7, 7);
                self::text($content, "F1", 7.0, $sx + 71, $blockTop - 28, "Yes", [0, 0, 0]);
                $box($content, $sx + 91, $blockTop - 31, 7, 7);
                self::text($content, "F1", 7.0, $sx + 101, $blockTop - 28, "No", [0, 0, 0]);
            }

            $signatureLineY = $blockTop - $headerHeight - 43;
            $line($content, $sx + 8, $signatureLineY, $sx + $signatureWidth - 8, $signatureLineY, 0.6);

            $nameText = "Name & Signature";
            $nameWidth = strlen($nameText) * 8 * 0.48;
            self::text(
                $content,
                "F1",
                8,
                $sx + max(4, ($signatureWidth - $nameWidth) / 2),
                $signatureLineY - 14,
                $nameText,
                [0, 0, 0],
            );

            $dateLineY = $blockBottom + 24;
            $dateLineWidth = min(74, $signatureWidth - 16);
            $line($content, $sx + 8, $dateLineY, $sx + 8 + $dateLineWidth, $dateLineY, 0.5);
            self::text($content, "F1", 8, $sx + 11, $dateLineY - 13, "Date", [0, 0, 0]);
        }

        self::text($content, "F1", 7.5, 40, 35, " UNICOIL IT Asset Management", [
            0.35,
            0.35,
            0.35,
        ]);
        self::text($content, "F1", 5.5, 492, 35, "Powered by IT (Rakib)", [
            0.72,
            0.72,
            0.72,
        ]);
        $objects = [
            1 => "<< /Type /Catalog /Pages 2 0 R >>",
            2 => "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
            3 => "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 5 0 R /F2 6 0 R >> /XObject << /ImLogo 7 0 R >> >> /Contents 4 0 R >>",
            4 => "<< /Length " . strlen($content) . ">>\nstream\n$content\nendstream",
            5 => "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>",
            6 => "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>",
            7 =>
                "<< /Type /XObject /Subtype /Image /Width " .
                $logo["width"] .
                " /Height " .
                $logo["height"] .
                " /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length " .
                strlen($logo["bytes"]) .
                ">>\nstream\n" .
                $logo["bytes"] .
                "\nendstream",
        ];
        return self::assemble($objects);
    }

    public static function downloadAudit(
        array $rows,
        string $from,
        string $to,
        string $filename,
        string $imageDir,
        string $defaultImage,
        string $reportTitle = "IT ASSET AUDIT REPORT",
        string $reportSubtitle = "Complete asset lifecycle activity for the selected period",
        ?array $summaryCards = null,
    ): void {
        $pdf = self::buildAudit(
            $rows,
            $from,
            $to,
            $imageDir,
            $defaultImage,
            $reportTitle,
            $reportSubtitle,
            $summaryCards,
        );
        header("Content-Type: application/pdf");
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        header("Content-Length: " . strlen($pdf));
        echo $pdf;
        exit();
    }

    public static function buildAudit(
        array $rows,
        string $from,
        string $to,
        string $imageDir,
        string $defaultImage,
        string $reportTitle = "IT ASSET AUDIT REPORT",
        string $reportSubtitle = "Complete asset lifecycle activity for the selected period",
        ?array $summaryCards = null,
    ): string {
        $bodyFontSize = 9.4;
        $purchaseFontSize = 9.4;
        $detailsFontSize = 9.4;
        $isRequestHistory = strtoupper(trim($reportTitle)) ===
            "REQUEST TRANSACTION HISTORY";

        // Size and paginate rows from their wrapped Details content. This keeps
        // every larger table font readable while supplier, PO, invoice, and
        // receipt-note text remains visible in full.
        $pages = [];
        $currentPage = [];
        $currentPageHeight = 0.0;
        $availableTableHeight = 400.0;
        foreach ($rows as $row) {
            $eventText = (string) ($row["eventType"] ?? "-");
            $eventFontSize =
                strlen($eventText) > 9 ? 8.2 : $bodyFontSize;
            $requiredHeights = [
                14.0 +
                    count(
                        self::wrapAll(
                            (string) ($row["eventDate"] ?? "-"),
                            self::charactersPerLine(60, $bodyFontSize),
                        ),
                    ) *
                        ($bodyFontSize + 3),
                14.0 +
                    count(
                        self::wrapAll(
                            $eventText,
                            self::charactersPerLine(56, $eventFontSize),
                        ),
                    ) *
                        ($eventFontSize + 3),
                14.0 +
                    count(
                        self::wrapAll(
                            (string) ($row["assetNum"] ?? "-"),
                            self::charactersPerLine(64, $bodyFontSize),
                        ),
                    ) *
                        ($bodyFontSize + 3),
                14.0 +
                    count(
                        self::wrapAll(
                            (string) ($row["assetDescription"] ?? "-"),
                            self::charactersPerLine(117, $bodyFontSize),
                        ),
                    ) *
                        ($bodyFontSize + 3),
                14.0 +
                    count(
                        self::wrapAll(
                            (string) ($row["assignedTo"] ?? "-"),
                            self::charactersPerLine(92, $bodyFontSize),
                        ),
                    ) *
                        ($bodyFontSize + 3),
                14.0 +
                    count(
                        self::wrapAll(
                            (string) ($row["department"] ?? "-"),
                            self::charactersPerLine(92, $bodyFontSize),
                        ),
                    ) *
                        ($bodyFontSize + 3),
                14.0 +
                    count(
                        self::wrapAll(
                            (string) ($row["details"] ?? "-"),
                            self::charactersPerLine(135, $detailsFontSize),
                        ),
                    ) *
                        ($detailsFontSize + 3),
            ];
            if (
                trim((string) ($row["warrantyExpiry"] ?? "")) !== "" &&
                trim((string) ($row["warrantyExpiry"] ?? "")) !== "-"
            ) {
                $requiredHeights[] = 17.0 + (2 * ($purchaseFontSize + 4));
            }
            $rowHeight = max(54.0, ...$requiredHeights);
            if (
                $currentPage &&
                $currentPageHeight + $rowHeight > $availableTableHeight
            ) {
                $pages[] = $currentPage;
                $currentPage = [];
                $currentPageHeight = 0.0;
            }
            $currentPage[] = ["row" => $row, "height" => $rowHeight];
            $currentPageHeight += $rowHeight;
        }
        if ($currentPage) {
            $pages[] = $currentPage;
        }
        if (!$pages) {
            $pages = [[]];
        }
        $pageCount = count($pages);
        /* Audit PDF images temporarily hidden.
        $imageData=[];foreach($rows as $row){$name=basename((string)($row['assetImage']??''));$path=$name!==''&&is_file($imageDir.$name)?$imageDir.$name:$defaultImage;if(!isset($imageData[$path]))$imageData[$path]=self::jpegData($path);}
        if(!$imageData)$imageData[$defaultImage]=self::jpegData($defaultImage);
        */
        $next = 3 + $pageCount * 2;
        $logo = self::jpegData(dirname(__DIR__, 2) . "/public/img/unicoil-logo.jpg");
        $logoObject = $next++;
        $fontRegular = $next++;
        $fontBold = $next++;
        $objects = [1 => "<< /Type /Catalog /Pages 2 0 R >>"];
        $kids = [];
        for ($i = 0; $i < $pageCount; $i++) {
            $kids[] = 3 + $i * 2 . " 0 R";
        }
        $objects[2] =
            "<< /Type /Pages /Kids [" . implode(" ", $kids) . "] /Count " . $pageCount . " >>";
        $counts = ["RECEIPT" => 0, "ASSIGN" => 0, "RETURN" => 0, "DELETE" => 0];
        foreach ($rows as $row) {
            if (isset($counts[$row["eventType"]])) {
                $counts[$row["eventType"]]++;
            }
        }
        foreach ($pages as $pageIndex => $pageRows) {
            $pageObject = 3 + $pageIndex * 2;
            $contentObject = $pageObject + 1;
            $content = "";
            $content .= "0.02 0.34 0.28 rg 0 520 842 75 re f\n";
            // Present the white-background logo as a compact brand badge so it
            // feels intentional against the dark header rather than pasted on.
            $badgeX = 728;
            $badgeY = 530;
            $badgeWidth = 84;
            $badgeHeight = 55;
            $content .=
                "1 1 1 rg " .
                self::num($badgeX) .
                " " .
                self::num($badgeY) .
                " " .
                self::num($badgeWidth) .
                " " .
                self::num($badgeHeight) .
                " re f\n";
            $content .=
                "0.72 0.86 0.82 RG 0.7 w " .
                self::num($badgeX) .
                " " .
                self::num($badgeY) .
                " " .
                self::num($badgeWidth) .
                " " .
                self::num($badgeHeight) .
                " re S\n";
            $logoWidth = 64;
            $logoHeight = $logo["height"] * ($logoWidth / $logo["width"]);
            $logoX = $badgeX + (($badgeWidth - $logoWidth) / 2);
            $logoY = $badgeY + (($badgeHeight - $logoHeight) / 2);
            $content .=
                "q " .
                self::num($logoWidth) .
                " 0 0 " .
                self::num($logoHeight) .
                " " .
                self::num($logoX) .
                " " .
                self::num($logoY) .
                " cm /ImLogo Do Q" .
                "\n";
            self::text($content, "F2", 20, 30, 565, $reportTitle, [1, 1, 1]);
            self::text(
                $content,
                "F1",
                10,
                30,
                543,
                $reportSubtitle,
                [0.88, 0.96, 0.94],
            );
            self::text(
                $content,
                "F2",
                9.5,
                555,
                565,
                $from === $to
                    ? "As of: " . $to
                    : "Period: " . $from . " to " . $to,
                [1, 1, 1],
            );

            // Compact summary cards make totals readable without crowding the
            // table header.
            $pageSummaryCards = $summaryCards ?? [
                ["TOTAL EVENTS", count($rows), [0.12, 0.38, 0.58]],
                ["RECEIPTS", $counts["RECEIPT"], [0.08, 0.48, 0.38]],
                ["ASSIGNMENTS", $counts["ASSIGN"], [0.23, 0.44, 0.68]],
                ["RETURNS", $counts["RETURN"], [0.88, 0.55, 0.12]],
                ["DELETIONS", $counts["DELETE"], [0.78, 0.16, 0.20]],
            ];
            foreach ($pageSummaryCards as $cardIndex => $card) {
                $cardX = 30 + ($cardIndex * 158);
                $color = $card[2];
                $content .=
                    "0.96 0.97 0.98 rg " .
                    self::num($cardX) .
                    " 480 150 27 re f\n";
                $content .=
                    self::num($color[0]) .
                    " " .
                    self::num($color[1]) .
                    " " .
                    self::num($color[2]) .
                    " rg " .
                    self::num($cardX) .
                    " 480 5 27 re f\n";
                self::text(
                    $content,
                    "F2",
                    8,
                    $cardX + 12,
                    497,
                    (string) $card[0],
                    [0.32, 0.38, 0.44],
                );
                self::text(
                    $content,
                    "F2",
                    11,
                    $cardX + 120,
                    491,
                    (string) $card[1],
                    $color,
                );
            }
            /* Photo column temporarily hidden from the audit PDF. */
            $x = [30, 98, 160, 232, 344, 469, 569, 669, 812];
            $headers = $isRequestHistory
                ? [
                    "Date",
                    "Event",
                    "Reference",
                    "Type / Status",
                    "Requested For",
                    "Requester",
                    "Department",
                    "Remarks",
                ]
                : [
                    "Date",
                    "Event",
                    "Asset No.",
                    "Warranty / Remaining",
                    "Description",
                    "Assigned To",
                    "Department",
                    "Details",
                ];
            $headerY = 438;
            $headerHeight = 30;
            $content .=
                "0.02 0.38 0.31 rg 30 $headerY 782 " .
                self::num($headerHeight) .
                " re f\n";
            for ($c = 0; $c < count($headers); $c++) {
                self::text(
                    $content,
                    "F2",
                    9.2,
                    $x[$c] + 4,
                    $headerY + 11,
                    $headers[$c],
                    [1, 1, 1],
                );
            }
            $rowY = $headerY;
            foreach ($pageRows as $rowIndex => $rowLayout) {
                $row = $rowLayout["row"];
                $rowHeight = (float) $rowLayout["height"];
                $rowY -= $rowHeight;
                $fill = $rowIndex % 2 === 0 ? "0.96 0.98 1" : "1 1 1";
                $content .=
                    "$fill rg 30 $rowY 782 " .
                    self::num($rowHeight) .
                    " re f\n0.82 0.87 0.91 RG 0.5 w 30 $rowY 782 " .
                    self::num($rowHeight) .
                    " re S\n";
                foreach (array_slice($x, 1, -1) as $vx) {
                    $content .=
                        "0.87 0.90 0.93 RG 0.4 w $vx $rowY m $vx " .
                        ($rowY + $rowHeight) .
                        " l S\n";
                }
                /* Audit PDF asset image rendering temporarily hidden. */
                $cellTop = $rowY + $rowHeight - 15;
                self::cell($content, $row["eventDate"] ?? "", 34, $cellTop, 60, 3, false, [0.12, 0.18, 0.24], $bodyFontSize);
                $eventText = (string) ($row["eventType"] ?? "");
                $eventFontSize =
                    strlen($eventText) > 9 ? 8.2 : $bodyFontSize;
                self::cell(
                    $content,
                    $eventText,
                    102,
                    $cellTop,
                    56,
                    2,
                    true,
                    self::eventColor($eventText),
                    $eventFontSize,
                );
                self::cell($content, $row["assetNum"] ?? "", 164, $cellTop, 64, 3, true, [0.08, 0.34, 0.58], $bodyFontSize);
                $warrantyExpiry = trim((string) ($row["warrantyExpiry"] ?? ""));
                if ($isRequestHistory) {
                    self::text(
                        $content,
                        "F2",
                        $purchaseFontSize,
                        236,
                        $cellTop - 3,
                        (string) ($row["requestType"] ?? "-"),
                        [0.08, 0.34, 0.58],
                    );
                    self::fullCell(
                        $content,
                        (string) ($row["statusTransition"] ?? "-"),
                        236,
                        $cellTop - ($purchaseFontSize + 8),
                        104,
                        false,
                        [0.18, 0.22, 0.26],
                        8.4,
                    );
                } elseif ($warrantyExpiry !== "" && $warrantyExpiry !== "-") {
                    $warrantyRemaining = trim(
                        (string) ($row["warrantyRemaining"] ?? ""),
                    );
                    if ($warrantyRemaining === "" || $warrantyRemaining === "-") {
                        $warrantyRemaining = self::warrantyRemainingLabel(
                            $warrantyExpiry,
                        );
                    }
                    self::text(
                        $content,
                        "F1",
                        $purchaseFontSize,
                        236,
                        $cellTop - 4,
                        "Expiry: " . $warrantyExpiry,
                        [0.18, 0.22, 0.26],
                    );
                    self::text(
                        $content,
                        "F1",
                        $purchaseFontSize,
                        236,
                        $cellTop - ($purchaseFontSize + 8),
                        "Remaining: " . $warrantyRemaining,
                        [0.18, 0.22, 0.26],
                    );
                } else {
                    self::text($content, "F1", $bodyFontSize, 236, $cellTop, "-", [0.35, 0.42, 0.48]);
                }
                self::cell($content, $row["assetDescription"] ?? "", 348, $cellTop, 117, 6, false, [0.12, 0.18, 0.24], $bodyFontSize);
                self::cell($content, $row["assignedTo"] ?? "-", 473, $cellTop, 92, 6, false, [0.12, 0.18, 0.24], $bodyFontSize);
                self::cell($content, $row["department"] ?? "-", 573, $cellTop, 92, 6, false, [0.12, 0.18, 0.24], $bodyFontSize);
                self::fullCell(
                    $content,
                    $row["details"] ?? "-",
                    673,
                    $cellTop,
                    135,
                    false,
                    [0.12, 0.18, 0.24],
                    $detailsFontSize,
                );
            }
            self::text(
                $content,
                "F1",
                8.5,
                30,
                22,
                " " . date("Y-m-d H:i") . " | UNICOIL IT Asset Management",
                [0.35, 0.42, 0.48],
            );
            self::text(
                $content,
                "F2",
                8.5,
                760,
                22,
                "Page " . ($pageIndex + 1) . " / " . $pageCount,
                [0.12, 0.25, 0.38],
            );
            self::text(
                $content,
                "F1",
                5.5,
                673,
                22,
                "Powered by IT (Rakib)",
                [0.72, 0.74, 0.76],
            );
            $objects[$pageObject] =
                "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 " .
                $fontRegular .
                " 0 R /F2 " .
                $fontBold .
                " 0 R >> /XObject << /ImLogo " .
                $logoObject .
                " 0 R >> >> /Contents " .
                $contentObject .
                " 0 R >>";
            $objects[$contentObject] =
                "<< /Length " . strlen($content) . " >>\nstream\n$content\nendstream";
        }
        /* Audit PDF asset image objects temporarily hidden. */
        $objects[$logoObject] =
            "<< /Type /XObject /Subtype /Image /Width " .
            $logo["width"] .
            " /Height " .
            $logo["height"] .
            " /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length " .
            strlen($logo["bytes"]) .
            " >>\nstream\n" .
            $logo["bytes"] .
            "\nendstream";
        $objects[$fontRegular] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
        $objects[$fontBold] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>";
        ksort($objects);
        return self::assemble($objects);
    }

    private static function jpegData(string $path): array
    {
        $info = @getimagesize($path);
        if ($info && ($info["mime"] ?? "") === "image/jpeg") {
            $bytes = (string) file_get_contents($path);
            $width = $info[0];
            $height = $info[1];
            return compact("bytes", "width", "height");
        }
        if (!function_exists("imagecreatefromstring")) {
            throw new RuntimeException("A JPEG image is required because PHP GD is unavailable.");
        }
        $source = @imagecreatefromstring((string) @file_get_contents($path));
        if (!$source) {
            $source = imagecreatetruecolor(80, 60);
            $bg = imagecolorallocate($source, 235, 239, 243);
            imagefill($source, 0, 0, $bg);
        }
        ob_start();
        imagejpeg($source, null, 78);
        $bytes = ob_get_clean();
        $width = imagesx($source);
        $height = imagesy($source);
        imagedestroy($source);
        return compact("bytes", "width", "height");
    }
    /**
     * Draw wrapped text horizontally and vertically centred inside a table cell.
     */
    private static function centeredCell(
        string &$c,
        string $text,
        float $x,
        float $y,
        float $width,
        float $height,
        int $maxLines = 1,
        bool $bold = false,
        float $fontSize = 7,
        float $padding = 4,
        array $color = [0, 0, 0],
    ): void {
        $usableWidth = max(8, $width - ($padding * 2));
        // Use a conservative width estimate so wide uppercase identifiers do
        // not cross a table border before the ellipsis is applied.
        $approximateCharacterWidth = max(3.2, $fontSize * 0.60);
        $charactersPerLine = max(3, (int) floor($usableWidth / $approximateCharacterWidth));
        $wrapped = self::wrap($text, $charactersPerLine, $maxLines);

        $lineHeight = $fontSize + 2;
        $lineCount = max(1, count($wrapped));
        $firstBaseline =
            $y +
            ($height / 2) +
            (($lineCount - 1) * $lineHeight / 2) -
            ($fontSize * 0.34);

        foreach ($wrapped as $lineIndex => $lineText) {
            $estimatedWidth = strlen($lineText) * $fontSize * 0.55;
            $textX = $x + max($padding, ($width - $estimatedWidth) / 2);
            $maximumX = $x + $width - $padding - $estimatedWidth;
            $textX = min($textX, max($x + $padding, $maximumX));

            self::text(
                $c,
                $bold ? "F2" : "F1",
                $fontSize,
                $textX,
                $firstBaseline - ($lineIndex * $lineHeight),
                $lineText,
                $color,
            );
        }
    }

    private static function cell(
        string &$c,
        string $text,
        float $x,
        float $y,
        int $width,
        int $lines,
        bool $bold = false,
        array $color = [0.18, 0.22, 0.26],
        float $fontSize = 7,
    ): void {
        $wrapped = self::wrap(
            $text,
            self::charactersPerLine($width, $fontSize),
            $lines,
        );
        foreach ($wrapped as $i => $line) {
            self::text($c, $bold ? "F2" : "F1", $fontSize, $x, $y - $i * ($fontSize + 3), $line, $color);
        }
    }

    /**
     * Draw every wrapped line without applying the normal table-cell ellipsis.
     * Audit rows are sized from this same wrapping calculation before rendering.
     */
    private static function fullCell(
        string &$c,
        string $text,
        float $x,
        float $y,
        int $width,
        bool $bold = false,
        array $color = [0.18, 0.22, 0.26],
        float $fontSize = 7,
    ): void {
        $wrapped = self::wrapAll(
            $text,
            self::charactersPerLine($width, $fontSize),
        );
        foreach ($wrapped as $lineIndex => $line) {
            self::text(
                $c,
                $bold ? "F2" : "F1",
                $fontSize,
                $x,
                $y - ($lineIndex * ($fontSize + 3)),
                $line,
                $color,
            );
        }
    }

    private static function charactersPerLine(
        float $width,
        float $fontSize,
    ): int {
        return max(
            5,
            (int) floor($width / max(4.4, $fontSize * 0.63)),
        );
    }

    private static function wrapAll(string $text, int $chars): array
    {
        return self::wrap($text, $chars, PHP_INT_MAX);
    }

    private static function wrap(string $text, int $chars, int $max): array
    {
        $text = trim($text);
        if ($text === "") {
            return ["-"];
        }

        $lines = [];
        $truncated = false;
        $paragraphs = preg_split("/\R+/", $text) ?: [$text];
        foreach ($paragraphs as $paragraphIndex => $paragraph) {
            $paragraph = trim(
                (string) preg_replace("/[ \t]+/", " ", $paragraph),
            );
            if ($paragraph === "") {
                continue;
            }
            // Cutting long tokens keeps email addresses, serial numbers and
            // document references inside their assigned PDF table cells.
            $paragraphLines = explode(
                "\n",
                wordwrap($paragraph, max(1, $chars), "\n", true),
            );
            foreach ($paragraphLines as $lineIndex => $line) {
                if (count($lines) >= $max) {
                    $truncated = true;
                    break 2;
                }
                $lines[] = $line;
                if (
                    count($lines) >= $max &&
                    ($lineIndex < count($paragraphLines) - 1 ||
                        $paragraphIndex < count($paragraphs) - 1)
                ) {
                    $truncated = true;
                    break 2;
                }
            }
        }

        if ($truncated && $lines) {
            $lastIndex = count($lines) - 1;
            $lines[$lastIndex] =
                rtrim(
                    substr(
                        $lines[$lastIndex],
                        0,
                        max(1, $chars - 3),
                    ),
                ) . "...";
        }
        if (!$lines) {
            return ["-"];
        }
        return array_slice($lines, 0, $max);
    }
    private static function eventColor(string $event): array
    {
        return [
            "RECEIPT" => [0.08, 0.45, 0.67],
            "ASSIGN" => [0.16, 0.5, 0.27],
            "RETURN" => [0.82, 0.45, 0.08],
            "DELETE" => [0.72, 0.16, 0.14],
        ][$event] ?? [0.2, 0.2, 0.2];
    }

    private static function warrantyRemainingLabel(string $expiry): string
    {
        $expiryDate = DateTimeImmutable::createFromFormat("!Y-m-d", $expiry);
        if (!$expiryDate || $expiryDate->format("Y-m-d") !== $expiry) {
            return "Unknown";
        }

        $days = (int) (new DateTimeImmutable("today"))
            ->diff($expiryDate)
            ->format("%r%a");
        if ($days < 0) {
            return abs($days) . " days overdue";
        }
        if ($days === 0) {
            return "Expires today";
        }
        return $days . " days left";
    }

    /**
     * Keep the form-title decision in one place so the PDF layout code stays
     * focused on positioning and rendering.
     */
    private static function formTitle(bool $isReturn, bool $isDepartment): string
    {
        if ($isReturn) {
            return "IT Asset Return Form";
        }

        return $isDepartment
            ? "IT Hardware / Software Issuance Form (Department)"
            : "IT Hardware / Software Issuance Form (Individual)";
    }

    private static function text(
        string &$c,
        string $font,
        float $size,
        float $x,
        float $y,
        string $text,
        array $rgb,
    ): void {
        $c .=
            self::num($rgb[0]) .
            " " .
            self::num($rgb[1]) .
            " " .
            self::num($rgb[2]) .
            " rg BT /$font " .
            self::num($size) .
            " Tf " .
            self::num($x) .
            " " .
            self::num($y) .
            " Td (" .
            self::escape($text) .
            ") Tj ET\n";
    }
    private static function escape(string $text): string
    {
        $text = iconv("UTF-8", "Windows-1252//TRANSLIT//IGNORE", $text) ?: $text;
        return str_replace(["\\", "(", ")", "\r", "\n"], ["\\\\", "\\(", "\\)", "", " "], $text);
    }
    private static function num(float $n): string
    {
        return rtrim(rtrim(number_format($n, 2, ".", ""), "0"), ".");
    }
    private static function assemble(array $objects): string
    {
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        foreach ($objects as $n => $obj) {
            $offsets[$n] = strlen($pdf);
            $pdf .= "$n 0 obj\n$obj\nendobj\n";
        }
        $xref = strlen($pdf);
        $count = max(array_keys($objects)) + 1;
        $pdf .= "xref\n0 $count\n0000000000 65535 f \n";
        for ($i = 1; $i < $count; $i++) {
            $pdf .= sprintf("%010d 00000 n ", $offsets[$i]) . "\n";
        }
        $pdf .= "trailer\n<< /Size $count /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
        return $pdf;
    }
}
