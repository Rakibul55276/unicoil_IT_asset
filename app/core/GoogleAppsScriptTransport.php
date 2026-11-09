<?php

class GoogleAppsScriptTransport
{
    public function send(
        string $to,
        string $subject,
        string $html,
        string $from,
        string $fromName,
        array $cc = [],
        array $attachments = [],
    ): string {
        $url = defined("GOOGLE_MAIL_WEB_APP_URL") ? trim((string) GOOGLE_MAIL_WEB_APP_URL) : "";
        $secret = defined("GOOGLE_MAIL_WEB_APP_SECRET")
            ? trim((string) GOOGLE_MAIL_WEB_APP_SECRET)
            : "";
        if ($url === "" || $secret === "") {
            throw new RuntimeException(
                "Google mail gateway is not configured. Add its web app URL and secret to app/config/mail_secret.php.",
            );
        }
        if (
            strpos($url, "https://script.google.com/macros/s/") !== 0 ||
            substr($url, -5) !== "/exec"
        ) {
            throw new RuntimeException(
                "Google mail gateway URL is invalid. Use the deployed Apps Script URL ending in /exec.",
            );
        }
        if (!function_exists("curl_init")) {
            throw new RuntimeException(
                "The PHP cURL extension is required for Google mail delivery.",
            );
        }

        $payload = [
            "secret" => $secret,
            "to" => $to,
            "cc" => array_values($cc),
            "subject" => $subject,
            "html" => $html,
            "replyTo" => $from,
            "fromName" => $fromName,
            "attachments" => [],
        ];
        foreach ($attachments as $file) {
            $content = file_get_contents($file["path"]);
            if ($content === false) {
                throw new RuntimeException("Unable to read attachment: " . $file["name"]);
            }
            $payload["attachments"][] = [
                "name" => $file["name"],
                "mime" => $file["mime"],
                "content" => base64_encode($content),
            ];
        }

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new RuntimeException("Unable to encode the Google mail request.");
        }
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => ["content-type: application/json"],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 40,
        ]);
        $response = curl_exec($curl);
        $connectionError = curl_error($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($response === false) {
            throw new RuntimeException(
                "Google mail gateway connection failed: " . $connectionError,
            );
        }
        $decoded = json_decode((string) $response, true);
        if ($status < 200 || $status >= 300 || !is_array($decoded) || empty($decoded["ok"])) {
            $detail = is_array($decoded) ? trim((string) ($decoded["error"] ?? "")) : "";
            throw new RuntimeException(
                "Google mail gateway rejected the email (HTTP " .
                    $status .
                    "): " .
                    ($detail ?: "Invalid response"),
            );
        }
        return trim((string) ($decoded["requestId"] ?? "GOOGLE-ACCEPTED")) ?: "GOOGLE-ACCEPTED";
    }
}
