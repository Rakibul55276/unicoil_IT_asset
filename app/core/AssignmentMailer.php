<?php

class AssignmentMailer
{
    public function send(array $assignment): array
    {
        $email = trim((string) ($assignment["employeeEmail"] ?? ""));
        if ($email === "") {
            return [
                "status" => "SKIPPED",
                "attachments" => 0,
                "message" => "Data has been saved. Please update the employee email address.",
            ];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                "status" => "FAILED",
                "attachments" => 0,
                "message" => "Data has been saved. Please update the employee email address.",
            ];
        }

        $attachments = $this->validAttachments($assignment["photoFiles"] ?? []);
        $boundary = "asset_" . bin2hex(random_bytes(16));
        $assetCount = count($assignment["assets"] ?? []);
        $subject =
            $assetCount > 1
                ? "Asset assignment: " . $assetCount . " assets"
                : "Asset assignment: " . ($assignment["assetNum"] ?? "");
        $html = $this->htmlBody($assignment);
        $from =
            defined("MAIL_FROM") && filter_var(MAIL_FROM, FILTER_VALIDATE_EMAIL)
                ? MAIL_FROM
                : "no-reply@" . ($_SERVER["SERVER_NAME"] ?? "localhost");
        $fromName = defined("MAIL_FROM_NAME") ? MAIL_FROM_NAME : "IT Asset Management";

        $headers = [
            "MIME-Version: 1.0",
            "From: " . $this->encodeHeader($fromName) . " <" . $from . ">",
            'Content-Type: multipart/mixed; boundary="' . $boundary . '"',
        ];

        $body = "--" . $boundary . "\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($html)) . "\r\n";

        foreach ($attachments as $file) {
            $content = chunk_split(base64_encode((string) file_get_contents($file["path"])));
            $body .= "--" . $boundary . "\r\n";
            $body .= "Content-Type: " . $file["mime"] . '; name="' . $file["name"] . "\"\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n";
            $body .= 'Content-Disposition: attachment; filename="' . $file["name"] . "\"\r\n\r\n";
            $body .= $content . "\r\n";
        }
        $body .= "--" . $boundary . "--\r\n";

        $cc = [];
        $headEmail = trim((string) ($assignment["departmentHeadEmail"] ?? ""));
        if (
            ($assignment["assigneeType"] ?? "") === "USER" &&
            filter_var($headEmail, FILTER_VALIDATE_EMAIL) &&
            strcasecmp($headEmail, $email) !== 0
        ) {
            $cc[] = $headEmail;
        }
        try {
            $this->deliver(
                $email,
                $subject,
                $html,
                $body,
                $headers,
                $from,
                $fromName,
                $cc,
                $attachments,
            );
            $sent = true;
            $deliveryMessage = "Email has been sent.";
        } catch (Throwable $error) {
            $sent = false;
            $deliveryMessage = $error->getMessage();
        }
        return [
            "status" => $sent ? "SENT" : "FAILED",
            "attachments" => count($attachments),
            "message" => $deliveryMessage,
            "recipients" => implode(", ", array_merge([$email], $cc)),
        ];
    }

    public function sendReturn(array $return): array
    {
        $email = trim((string) ($return["employeeEmail"] ?? ""));
        if ($email === "") {
            return [
                "status" => "SKIPPED",
                "attachments" => 0,
                "message" => "Data has been saved. Please update the employee email address.",
            ];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                "status" => "FAILED",
                "attachments" => 0,
                "message" => "Data has been saved. Please update the employee email address.",
            ];
        }

        $cc = [];
        $headEmail = trim((string) ($return["departmentHeadEmail"] ?? ""));
        if (
            ($return["assigneeType"] ?? "") === "USER" &&
            filter_var($headEmail, FILTER_VALIDATE_EMAIL) &&
            strcasecmp($headEmail, $email) !== 0
        ) {
            $cc[] = $headEmail;
        }

        return $this->sendMessage(
            $email,
            count($return["assets"] ?? []) > 1
                ? "Asset return: " . count($return["assets"]) . " assets"
                : "Asset return: " . ($return["assetNum"] ?? ""),
            $this->returnHtmlBody($return),
            $return["photoFiles"] ?? [],
            $cc,
        );
    }

    private function htmlBody(array $data): string
    {
        $e = static fn($value): string => htmlspecialchars(
            (string) ($value ?? "-"),
            ENT_QUOTES,
            "UTF-8",
        );
        return '<!doctype html><html><body style="font-family:Arial,sans-serif;color:#222">' .
            "<h2>IT Asset Assigned to You</h2>" .
            "<p>Hello " .
            $e($data["employeeName"] ?? "") .
            ",</p>" .
            "<p>The following asset" .
            (count($data["assets"] ?? []) > 1 ? "s have" : " has") .
            " been assigned to you.</p>" .
            '<table cellpadding="8" cellspacing="0" style="border-collapse:collapse;border:1px solid #bbb;width:100%;max-width:900px">' .
            '<tr><th align="left">Issue Number</th><td>' .
            $e($data["issueNo"] ?? "") .
            "</td></tr>" .
            '<tr><th align="left">Issue Date</th><td>' .
            $e($data["issueDate"] ?? "") .
            "</td></tr>" .
            $this->assignmentAssetRows($data, $e) .
            '<tr><th align="left">Department</th><td>' .
            $e($data["departmentName"] ?? "") .
            "</td></tr>" .
            '<tr><th align="left">Condition</th><td>' .
            $e($data["conditionOut"] ?? "") .
            "</td></tr>" .
            '<tr><th align="left">Expected Return</th><td>' .
            $e($data["expectedReturnDate"] ?? "") .
            "</td></tr>" .
            '<tr><th align="left">Remarks</th><td>' .
            $e($data["remarks"] ?? $data["purpose"] ?? "") .
            "</td></tr>" .
            "</table><p>Asset and assignment photos are attached when available.</p>" .
            "<p>This email contains only the current assignment; no transaction history is included.</p>" .
            "</body></html>";
    }

    private function returnHtmlBody(array $data): string
    {
        $e = static fn($value): string => htmlspecialchars(
            (string) ($value ?? "-"),
            ENT_QUOTES,
            "UTF-8",
        );
        return '<!doctype html><html><body style="font-family:Arial,sans-serif;color:#222">' .
            "<h2>IT Asset Return Confirmation</h2>" .
            "<p>Hello " .
            $e($data["employeeName"] ?? "") .
            ",</p>" .
            "<p>Your asset return has been recorded with the following details.</p>" .
            '<table cellpadding="8" cellspacing="0" style="border-collapse:collapse;border:1px solid #bbb;width:100%;max-width:900px">' .
            '<tr><th align="left">Return Number</th><td>' .
            $e($data["returnNo"] ?? "") .
            "</td></tr>" .
            '<tr><th align="left">Return Date</th><td>' .
            $e($data["returnDate"] ?? "") .
            "</td></tr>" .
            $this->returnAssetRows($data, $e) .
            '<tr><th align="left">Return Type</th><td>' .
            $e($data["type"] ?? "") .
            "</td></tr>" .
            '<tr><th align="left">Condition In</th><td>' .
            $e($data["conditionIn"] ?? "") .
            "</td></tr>" .
            '<tr><th align="left">Remarks</th><td>' .
            $e($data["remarks"] ?? $data["reason"] ?? "") .
            "</td></tr>" .
            "</table><p>Asset and return photos are attached when available.</p>" .
            "<p>This email contains only the current return; no transaction history is included.</p>" .
            "</body></html>";
    }

    private function assignmentAssetRows(array $data, callable $e): string
    {
        $assets = $data["assets"] ?? [];

        if (!$assets) {
            $assets = [
                [
                    "assetNum" => $data["assetNum"] ?? "",
                    "Description" => $data["assetDescription"] ?? "",
                    "categoryName" => $data["assetCategory"] ?? "",
                    "serial_number" => $data["serialNumber"] ?? "",
                    "manufacturer" => $data["manufacturer"] ?? "",
                    "model" => $data["model"] ?? "",
                    "conditionOut" => $data["conditionOut"] ?? "",
                    "remarks" => $data["remarks"] ?? ($data["purpose"] ?? ""),
                ],
            ];
        }

        $rows =
            '<tr>' .
            '<th align="left" valign="top" style="padding:8px;border:1px solid #bbb;background:#f3f4f6;width:150px">Assets</th>' .
            '<td style="padding:8px;border:1px solid #bbb">' .
            '<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;border:1px solid #ccc;font-size:13px">' .
            '<thead>' .
            '<tr style="background:#e9ecef">' .
            '<th align="left" style="border:1px solid #ccc;padding:6px">Asset No.</th>' .
            '<th align="left" style="border:1px solid #ccc;padding:6px">Description</th>' .
            '<th align="left" style="border:1px solid #ccc;padding:6px">Category</th>' .
            '<th align="left" style="border:1px solid #ccc;padding:6px">Serial No.</th>' .
            '<th align="left" style="border:1px solid #ccc;padding:6px">Condition</th>' .
            '<th align="left" style="border:1px solid #ccc;padding:6px">Remarks</th>' .
            '</tr>' .
            '</thead><tbody>';

        foreach ($assets as $asset) {
            $rows .=
                '<tr>' .
                '<td style="border:1px solid #ccc;padding:6px;vertical-align:top">' .
                $e($asset["assetNum"] ?? "") .
                '</td>' .
                '<td style="border:1px solid #ccc;padding:6px;vertical-align:top">' .
                $e($asset["Description"] ?? ($asset["assetDescription"] ?? "")) .
                '</td>' .
                '<td style="border:1px solid #ccc;padding:6px;vertical-align:top">' .
                $e($asset["categoryName"] ?? ($asset["assetCategory"] ?? "")) .
                '</td>' .
                '<td style="border:1px solid #ccc;padding:6px;vertical-align:top">' .
                $e($asset["serial_number"] ?? ($asset["serialNumber"] ?? "")) .
                '</td>' .
                '<td style="border:1px solid #ccc;padding:6px;vertical-align:top">' .
                $e($asset["conditionOut"] ?? ($asset["condition_out"] ?? "")) .
                '</td>' .
                '<td style="border:1px solid #ccc;padding:6px;vertical-align:top">' .
                $e($asset["remarks"] ?? ($asset["purpose"] ?? "")) .
                '</td>' .
                '</tr>';
        }

        return $rows . '</tbody></table></td></tr>';
    }

    private function returnAssetRows(array $data, callable $e): string
    {
        $assets = $data["assets"] ?? [];

        if (!$assets) {
            $assets = [
                [
                    "assetNum" => $data["assetNum"] ?? "",
                    "assetDescription" => $data["assetDescription"] ?? "",
                    "assetCategory" => $data["assetCategory"] ?? "",
                    "serial_number" => $data["serialNumber"] ?? "",
                    "conditionIn" => $data["conditionIn"] ?? "",
                    "remarks" => $data["remarks"] ?? ($data["reason"] ?? ""),
                ],
            ];
        }

        $rows =
            '<tr>' .
            '<th align="left" valign="top" style="padding:8px;border:1px solid #bbb;background:#f3f4f6;width:150px">Assets</th>' .
            '<td style="padding:8px;border:1px solid #bbb">' .
            '<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;border:1px solid #ccc;font-size:13px">' .
            '<thead>' .
            '<tr style="background:#e9ecef">' .
            '<th align="left" style="border:1px solid #ccc;padding:6px">Asset No.</th>' .
            '<th align="left" style="border:1px solid #ccc;padding:6px">Description</th>' .
            '<th align="left" style="border:1px solid #ccc;padding:6px">Category</th>' .
            '<th align="left" style="border:1px solid #ccc;padding:6px">Serial No.</th>' .
            '<th align="left" style="border:1px solid #ccc;padding:6px">Condition</th>' .
            '<th align="left" style="border:1px solid #ccc;padding:6px">Remarks</th>' .
            '</tr>' .
            '</thead><tbody>';

        foreach ($assets as $asset) {
            $rows .=
                '<tr>' .
                '<td style="border:1px solid #ccc;padding:6px;vertical-align:top">' .
                $e($asset["assetNum"] ?? "") .
                '</td>' .
                '<td style="border:1px solid #ccc;padding:6px;vertical-align:top">' .
                $e($asset["assetDescription"] ?? ($asset["Description"] ?? "")) .
                '</td>' .
                '<td style="border:1px solid #ccc;padding:6px;vertical-align:top">' .
                $e($asset["assetCategory"] ?? ($asset["categoryName"] ?? "")) .
                '</td>' .
                '<td style="border:1px solid #ccc;padding:6px;vertical-align:top">' .
                $e($asset["serial_number"] ?? ($asset["serialNumber"] ?? "")) .
                '</td>' .
                '<td style="border:1px solid #ccc;padding:6px;vertical-align:top">' .
                $e($asset["conditionIn"] ?? ($asset["condition_in"] ?? "")) .
                '</td>' .
                '<td style="border:1px solid #ccc;padding:6px;vertical-align:top">' .
                $e($asset["remarks"] ?? ($asset["reason"] ?? "")) .
                '</td>' .
                '</tr>';
        }

        return $rows . '</tbody></table></td></tr>';
    }

    private function sendMessage(
        string $email,
        string $subject,
        string $html,
        array $photoFiles,
        array $cc = [],
    ): array {
        $attachments = $this->validAttachments($photoFiles);
        $boundary = "asset_" . bin2hex(random_bytes(16));
        $from =
            defined("MAIL_FROM") && filter_var(MAIL_FROM, FILTER_VALIDATE_EMAIL)
                ? MAIL_FROM
                : "no-reply@" . ($_SERVER["SERVER_NAME"] ?? "localhost");
        $fromName = defined("MAIL_FROM_NAME") ? MAIL_FROM_NAME : "IT Asset Management";
        $headers = [
            "MIME-Version: 1.0",
            "From: " . $this->encodeHeader($fromName) . " <" . $from . ">",
            'Content-Type: multipart/mixed; boundary="' . $boundary . '"',
        ];
        $body =
            "--" .
            $boundary .
            "\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($html)) . "\r\n";
        foreach ($attachments as $file) {
            $body .=
                "--" .
                $boundary .
                "\r\nContent-Type: " .
                $file["mime"] .
                '; name="' .
                $file["name"] .
                "\"\r\n";
            $body .=
                "Content-Transfer-Encoding: base64\r\nContent-Disposition: attachment; filename=\"" .
                $file["name"] .
                "\"\r\n\r\n";
            $body .= chunk_split(base64_encode((string) file_get_contents($file["path"]))) . "\r\n";
        }
        $body .= "--" . $boundary . "--\r\n";
        try {
            $this->deliver(
                $email,
                $subject,
                $html,
                $body,
                $headers,
                $from,
                $fromName,
                $cc,
                $attachments,
            );
            $sent = true;
            $deliveryMessage = "Email has been sent.";
        } catch (Throwable $error) {
            $sent = false;
            $deliveryMessage = $error->getMessage();
        }
        return [
            "status" => $sent ? "SENT" : "FAILED",
            "attachments" => count($attachments),
            "message" => $deliveryMessage,
            "recipients" => implode(", ", array_merge([$email], $cc)),
        ];
    }

    private function deliver(
        string $email,
        string $subject,
        string $html,
        string $body,
        array $headers,
        string $from,
        string $fromName,
        array $cc = [],
        array $attachments = [],
    ): string {
        if (defined("MAIL_TRANSPORT") && MAIL_TRANSPORT === "google_apps_script") {
            return (new GoogleAppsScriptTransport())->send(
                $email,
                $subject,
                $html,
                $from,
                $fromName,
                $cc,
                $attachments,
            );
        }
        (new SmtpTransport())->send(
            $email,
            $this->encodeHeader($subject),
            $body,
            $headers,
            $from,
            $cc,
        );
        return "SMTP-ACCEPTED";
    }

    private function validAttachments(array $paths): array
    {
        $result = [];
        $seen = [];
        $allowed = ["image/jpeg", "image/png", "image/webp"];
        foreach ($paths as $path) {
            $real = realpath((string) $path);
            if (
                !$real ||
                isset($seen[$real]) ||
                !is_file($real) ||
                filesize($real) > 5 * 1024 * 1024
            ) {
                continue;
            }
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($real);
            if (!in_array($mime, $allowed, true)) {
                continue;
            }
            $seen[$real] = true;
            $result[] = ["path" => $real, "mime" => $mime, "name" => basename($real)];
        }
        return $result;
    }

    private function encodeHeader(string $value): string
    {
        return "=?UTF-8?B?" . base64_encode($value) . "?=";
    }
}