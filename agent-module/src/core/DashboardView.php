<?php

class DashboardView
{
    public static function securityHeaders(): void
    {
        header(
            "Content-Security-Policy: default-src 'self'; style-src 'self'; img-src 'self' data:; form-action 'self'; frame-ancestors 'none'; base-uri 'none'",
        );
        header("X-Content-Type-Options: nosniff");
        header("X-Frame-Options: DENY");
        header("Referrer-Policy: no-referrer");
        header("Cache-Control: no-store");
    }
    public static function header(string $title, ?array $user = null): void
    {
        self::securityHeaders();
        $title = self::escape($title);
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' .
            $title .
            ' · UNICOIL Agent</title><link rel="stylesheet" href="assets/dashboard.css"></head><body>';
        if ($user !== null) {
            echo '<header class="topbar"><a class="brand" href="dashboard.php">UNICOIL <span>PC Agent</span></a><nav><a href="dashboard.php">Devices</a><span class="user">' .
                self::escape($user["name"]) .
                '</span><form method="post" action="logout.php"><input type="hidden" name="csrf" value="' .
                self::escape(self::csrf()) .
                '"><button class="link-button" type="submit">Sign out</button></form></nav></header>';
        }
    }
    private static function csrf(): string
    {
        return DashboardAuth::csrfToken();
    }
    public static function footer(): void
    {
        echo "<footer>UNICOIL IT · Windows inventory service</footer></body></html>";
    }
    public static function escape($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
    }
    public static function bytes($value): string
    {
        if ($value === null || !is_numeric($value)) {
            return "—";
        }
        $b = (float) $value;
        if ($b >= 1099511627776) {
            return number_format($b / 1099511627776, 2) . " TB";
        }
        if ($b >= 1073741824) {
            return number_format($b / 1073741824, 2) . " GB";
        }
        if ($b >= 1048576) {
            return number_format($b / 1048576, 1) . " MB";
        }
        return number_format($b) . " B";
    }
    public static function status(array $device): string
    {
        if (!empty($device["agent_status"])) {
            return $device["agent_status"];
        }
        $seen = strtotime((string) ($device["last_seen_at"] ?? ""));
        if (!$seen) {
            return "OFFLINE";
        }
        $age = time() - $seen;
        return $age <= 25200 ? "CURRENT" : ($age <= 86400 ? "STALE" : "OFFLINE");
    }
    public static function statusLabel(string $status): string
    {
        return ["CURRENT" => "Current", "STALE" => "Stale", "OFFLINE" => "Offline"][$status] ??
            $status;
    }
}
