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
    /**
     * Master-data deletes must be POST requests carrying the shared delete token
     * rendered by the authenticated layout. A plain link (GET) can no longer
     * delete records, which closes cross-site request forgery through links.
     */
    protected function requirePostDeleteToken(): void
    {
        if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
            http_response_code(405);
            exit("Method not allowed.");
        }
        if (
            empty($_SESSION["csrf_master_delete"]) ||
            !hash_equals(
                (string) $_SESSION["csrf_master_delete"],
                (string) ($_POST["csrf_token"] ?? ""),
            )
        ) {
            http_response_code(419);
            exit("The delete form expired. Refresh the page and try again.");
        }
    }

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
