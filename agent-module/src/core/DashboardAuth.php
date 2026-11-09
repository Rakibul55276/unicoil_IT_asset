<?php

class DashboardCsrfException extends RuntimeException {}

class DashboardAuth
{
    private const SESSION_NAME = "unicoil_agent_dashboard";

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $secure =
            (!empty($_SERVER["HTTPS"]) &&
                strtolower((string) $_SERVER["HTTPS"]) !== "off") ||
            strtolower((string) ($_SERVER["HTTP_X_FORWARDED_PROTO"] ?? "")) ===
                "https";
        ini_set("session.use_strict_mode", "1");
        session_name(self::SESSION_NAME);
        session_set_cookie_params([
            "lifetime" => 0,
            "path" => "/",
            "secure" => $secure,
            "httponly" => true,
            "samesite" => "Strict",
        ]);
        session_start();
        if (empty($_SESSION["agent_csrf"])) {
            $_SESSION["agent_csrf"] = bin2hex(random_bytes(32));
        }
    }

    public static function attempt(string $username, string $password): bool
    {
        self::start();
        $now = time();
        $locked = (int) ($_SESSION["agent_login_locked_until"] ?? 0);
        if ($locked > $now) {
            throw new RuntimeException(
                "Too many login attempts. Try again in " . ($locked - $now) . " seconds.",
            );
        }
        $db = new Database();
        $db->query(
            "SELECT u.username,u.nama,u.password,u.active
             FROM users u
             WHERE u.username=:username
               AND u.active=1
               AND (
                   LOWER(u.username)='admin'
                   OR EXISTS (
                       SELECT 1
                       FROM user_role ur
                       WHERE ur.username=u.username
                         AND ur.role_code='ADMINISTRATOR'
                   )
               )
             LIMIT 1",
        );
        $db->bind("username", trim($username));
        $user = $db->single();
        if (!$user || !password_verify($password, $user["password"])) {
            $attempts = (int) ($_SESSION["agent_login_attempts"] ?? 0) + 1;
            $_SESSION["agent_login_attempts"] = $attempts;
            if ($attempts >= 5) {
                $_SESSION["agent_login_locked_until"] = $now + 60;
                $_SESSION["agent_login_attempts"] = 0;
            }
            return false;
        }
        session_regenerate_id(true);
        $_SESSION["agent_user"] = ["username" => $user["username"], "name" => $user["nama"]];
        $_SESSION["agent_login_attempts"] = 0;
        unset($_SESSION["agent_login_locked_until"]);
        return true;
    }

    public static function check(): bool
    {
        self::start();
        return isset($_SESSION["agent_user"]["username"]);
    }
    public static function requireLogin(): void
    {
        if (!self::check()) {
            header("Location: login.php");
            exit();
        }
    }
    public static function user(): array
    {
        self::requireLogin();
        return $_SESSION["agent_user"];
    }
    public static function csrfToken(): string
    {
        self::start();
        return $_SESSION["agent_csrf"];
    }
    public static function validateCsrf($token): void
    {
        self::start();
        if (!is_string($token) || !hash_equals($_SESSION["agent_csrf"], $token)) {
            throw new DashboardCsrfException(
                "The request expired. Refresh the page and try again.",
            );
        }
    }
    public static function logout(): void
    {
        self::start();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $p = session_get_cookie_params();
            setcookie(
                session_name(),
                "",
                time() - 42000,
                $p["path"],
                $p["domain"] ?? "",
                (bool) $p["secure"],
                (bool) $p["httponly"],
            );
        }
        session_destroy();
    }
}
