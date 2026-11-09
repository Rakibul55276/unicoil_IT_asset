<?php

class Controller
{
    public function view($view, $data = [])
    {
        require_once "app/views/" . $view . ".php";
    }

    public function model($model)
    {
        require_once "app/models/" . $model . ".php";
        return new $model();
    }

    /**
     * Enforce backend authorization independently from navigation visibility.
     * Hiding a menu item is never treated as an access-control decision.
     */
    protected function requireRoles(array $allowedRoles, string $message): void
    {
        $username = trim((string) ($_SESSION["usr"]["user"] ?? ""));
        if ($username === "") {
            header("Location: " . BASEURL);
            exit();
        }

        $account = $this->model("User_model")->getUserById($username);
        $roles =
            is_array($account) && (int) ($account["active"] ?? 0) === 1
                ? array_values(array_filter($account["roles"] ?? []))
                : [];
        if (
            strtolower($username) === "admin" ||
            in_array("ADMINISTRATOR", $roles, true) ||
            (bool) array_intersect($allowedRoles, $roles)
        ) {
            return;
        }

        http_response_code(403);
        exit($message);
    }
}
