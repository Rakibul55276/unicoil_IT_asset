<?php

class SmtpTransport
{
    public function send(
        string $to,
        string $subject,
        string $body,
        array $headers,
        string $from,
        array $cc = [],
    ): void {
        $authenticationEnabled = defined("SMTP_AUTH") ? (bool) SMTP_AUTH : true;
        if (
            $authenticationEnabled &&
            (!defined("SMTP_USERNAME") ||
                SMTP_USERNAME === "" ||
                !defined("SMTP_PASSWORD") ||
                SMTP_PASSWORD === "")
        ) {
            throw new RuntimeException(
                "Company Gmail credentials are not configured. Create app/config/mail_secret.php with the Gmail address and Google App Password.",
            );
        }
        $encryption = strtolower((string) SMTP_ENCRYPTION);
        $socketTarget = ($encryption === "ssl" ? "ssl://" : "tcp://") . SMTP_HOST . ":" . SMTP_PORT;
        $socket = @stream_socket_client($socketTarget, $errno, $error, 20, STREAM_CLIENT_CONNECT);
        if (!$socket) {
            throw new RuntimeException("SMTP connection failed: " . $error . " (" . $errno . ")");
        }
        stream_set_timeout($socket, 20);
        try {
            $this->expect($socket, [220]);
            $host = $_SERVER["SERVER_NAME"] ?? "localhost";
            $this->command($socket, "EHLO " . $host, [250]);
            if ($encryption === "tls") {
                $this->command($socket, "STARTTLS", [220]);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException("Unable to establish SMTP TLS encryption.");
                }
                $this->command($socket, "EHLO " . $host, [250]);
            }
            if ($authenticationEnabled) {
                $this->command($socket, "AUTH LOGIN", [334]);
                $this->command($socket, base64_encode(SMTP_USERNAME), [334]);
                $this->command($socket, base64_encode(SMTP_PASSWORD), [235]);
            }
            $recipients = array_values(
                array_unique(
                    array_filter(
                        array_merge([$to], $cc),
                        static fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL),
                    ),
                ),
            );
            if (!$recipients) {
                throw new RuntimeException("No valid SMTP recipient was provided.");
            }
            $this->command($socket, "MAIL FROM:<" . $from . ">", [250]);
            foreach ($recipients as $recipient) {
                $this->command($socket, "RCPT TO:<" . $recipient . ">", [250, 251]);
            }
            $this->command($socket, "DATA", [354]);
            $ccHeader = $cc ? "Cc: " . implode(", ", $cc) . "\r\n" : "";
            $message =
                "To: " .
                $to .
                "\r\n" .
                $ccHeader .
                "Subject: " .
                $subject .
                "\r\n" .
                implode("\r\n", $headers) .
                "\r\n\r\n" .
                $body;
            $message = preg_replace("/(?m)^\./", "..", $message);
            fwrite($socket, $message . "\r\n.\r\n");
            $this->expect($socket, [250]);
            $this->command($socket, "QUIT", [221]);
        } finally {
            fclose($socket);
        }
    }

    private function command($socket, string $command, array $codes): string
    {
        fwrite($socket, $command . "\r\n");
        return $this->expect($socket, $codes);
    }
    private function expect($socket, array $codes): string
    {
        $response = "";
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (strlen($line) < 4 || $line[3] !== "-") {
                break;
            }
        }
        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $codes, true)) {
            throw new RuntimeException(
                "SMTP server rejected the request: " . trim(preg_replace("/\s+/", " ", $response)),
            );
        }
        return $response;
    }
}
