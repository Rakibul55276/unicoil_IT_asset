<?php

class Scrap extends Controller
{
    private Scrap_model $scrap;
    private array $context;

    public function __construct()
    {
        if (empty($_SESSION["usr"])) {
            header("location:" . BASEURL);
            exit();
        }
        if (defined("SCRAP_APPROVAL_ENABLED") && !SCRAP_APPROVAL_ENABLED) {
            http_response_code(404);
            exit("Scrap approval is disabled.");
        }
        $this->scrap = $this->model("Scrap_model");
        $this->context = $this->model("Request_model")->getUserContext((string) $_SESSION["usr"]["user"]);
        $_SESSION["usr"]["roles"] = $this->context["roles"] ?? [];
        $_SESSION["usr"]["empId"] = $this->context["empId"] ?? null;
        if (!$this->hasAnyRole(["ISSUER", "IT_MANAGER"])) {
            http_response_code(403);
            exit("Only Issuers and IT Managers can access scrap approvals.");
        }
        if (empty($_SESSION["csrf_scrap_request"])) {
            $_SESSION["csrf_scrap_request"] = bin2hex(random_bytes(32));
        }
    }

    public function index()
    {
        $data = $this->pageData("Scrap Approval", "Issuer requests and IT Manager decisions");
        $data["requests"] = $this->scrap->getAll();
        $this->render("scrap/index", $data);
    }

    public function create()
    {
        $this->requireRole("ISSUER");
        $data = $this->pageData("Request Asset Scrap", "Submit an in-stock asset for IT approval");
        $data["assets"] = [];
        $this->render("scrap/create", $data);
    }

    public function searchAvailableAssets()
    {
        $this->requireRole("ISSUER");
        header("Content-Type: application/json; charset=utf-8");
        $rows = $this->scrap->searchAvailableAssets((string) ($_GET["q"] ?? ""), 30);
        $results = array_map(static function (array $asset): array {
            $serial = trim((string) ($asset["serial_number"] ?? ""));
            return [
                "id" => (string) $asset["assetNum"],
                "text" => $asset["assetNum"] .
                    " - " .
                    $asset["Description"] .
                    ($serial !== "" ? " - " . $serial : "") .
                    " - " .
                    ($asset["categoryName"] ?? ""),
            ];
        }, $rows);
        echo json_encode(["results" => $results], JSON_UNESCAPED_UNICODE);
    }

    public function store()
    {
        $this->requirePost();
        $this->verifyCsrf();
        $this->requireRole("ISSUER");
        try {
            $this->scrap->create((string) ($_POST["assetNum"] ?? ""),
                (string) ($_POST["reason"] ?? ""), $this->context["username"]);
            Flasher::setMessage("Scrap request submitted.", "Waiting for IT Manager approval.", "success");
        } catch (Throwable $error) {
            Flasher::setMessage("Scrap request failed.", $error->getMessage(), "danger");
        }
        $this->redirect("/Scrap");
    }

    public function approve($id)
    {
        $this->requirePost();
        $this->verifyCsrf();
        $this->requireRole("IT_MANAGER");
        try {
            $this->scrap->approve((int) $id, (string) ($_POST["comment"] ?? ""), $this->context["username"]);
            Flasher::setMessage("Scrap approved.", "The asset is now marked Scrapped.", "success");
        } catch (Throwable $error) {
            Flasher::setMessage("Approval failed.", $error->getMessage(), "danger");
        }
        $this->redirect("/Scrap");
    }

    public function reject($id)
    {
        $this->requirePost();
        $this->verifyCsrf();
        $this->requireRole("IT_MANAGER");
        try {
            $this->scrap->reject((int) $id, (string) ($_POST["reason"] ?? ""), $this->context["username"]);
            Flasher::setMessage("Scrap request rejected.", "The asset remains in stock.", "warning");
        } catch (Throwable $error) {
            Flasher::setMessage("Rejection failed.", $error->getMessage(), "danger");
        }
        $this->redirect("/Scrap");
    }

    private function pageData(string $menu, string $description): array
    {
        return [
            "title" => "IT Asset - " . $menu,
            "menu" => $menu,
            "menu-dsc" => $description,
            "scrapPage" => true,
            "context" => $this->context,
            "csrf" => $_SESSION["csrf_scrap_request"],
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
            http_response_code(403);
            exit("You do not have permission for this scrap action.");
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
        if (!hash_equals((string) ($_SESSION["csrf_scrap_request"] ?? ""), (string) ($_POST["csrf_token"] ?? ""))) {
            http_response_code(419);
            exit("Invalid form token. Refresh and try again.");
        }
    }

    private function redirect(string $path): void
    {
        header("location: " . BASEURL . $path);
        exit();
    }
}
