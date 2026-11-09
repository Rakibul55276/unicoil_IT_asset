<?php

class Home extends Controller
{
    public function index()
    {
        if (isset($_SESSION["usr"])) {
            // Refresh role and employee linkage on every dashboard load so
            // access changes made by an Administrator take effect immediately.
            $access = $this->model("User_model")->getUserById(
                (string) ($_SESSION["usr"]["user"] ?? ""),
            );
            if ($access) {
                $_SESSION["usr"]["roles"] = $access["roles"] ?? [];
                $_SESSION["usr"]["empId"] = $access["empId"] ?? null;
                $_SESSION["usr"]["name"] = $access["nama"] ?? $_SESSION["usr"]["name"];
            }
            // $data['nama'] = $this->model('User_model')->getUser();
            $data["title"] = "IT-Asset Management Dashboard";
            $data["menu"] = "Dashboard";
            $data["menu-dsc"] = "overview";
            $requestModel = $this->model("Request_model");
            $context = $requestModel->getUserContext((string) ($_SESSION["usr"]["user"] ?? ""));
            $data["roleDashboard"] = $this->model("Dashboard_model")->getRoleDashboard($context);
            $workflowUiEnabled = defined("WORKFLOW_UI_ENABLED") ? WORKFLOW_UI_ENABLED : true;
            $issuerUiEnabled = defined("ISSUER_UI_ENABLED") ? ISSUER_UI_ENABLED : false;
            $data["workflowUiEnabled"] = $workflowUiEnabled;
            $data["issuerUiEnabled"] = $issuerUiEnabled;
            $data["isFullAnalytics"] =
                in_array("ADMINISTRATOR", $context["roles"] ?? [], true) ||
                ($issuerUiEnabled && in_array("ISSUER", $context["roles"] ?? [], true)) ||
                ($workflowUiEnabled &&
                    in_array("IT_MANAGER", $context["roles"] ?? [], true));
            if ($data["isFullAnalytics"]) {
                $assetModel = $this->model("Asset_model");
                $data["analytics"] = $assetModel->getDashboardAnalytics();

                // The selectable warranty window defaults to the current year
                // plus the next three years, which shows a typical full asset
                // lifecycle while remaining user-adjustable.
                $defaultFrom = (new DateTimeImmutable("first day of January"))->format("Y-m-d");
                $defaultTo = (new DateTimeImmutable("last day of December +3 years"))->format(
                    "Y-m-d",
                );
                $warrantyFrom = trim((string) ($_GET["warranty_from"] ?? $defaultFrom));
                $warrantyTo = trim((string) ($_GET["warranty_to"] ?? $defaultTo));
                if (
                    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $warrantyFrom) ||
                    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $warrantyTo) ||
                    $warrantyFrom > $warrantyTo
                ) {
                    $warrantyFrom = $defaultFrom;
                    $warrantyTo = $defaultTo;
                }
                $data["analytics"]["warranty"] = $assetModel->getWarrantyTimeline(
                    $warrantyFrom,
                    $warrantyTo,
                    0,
                );
                $data["agentSummary"] = ["total" => 0, "current_count" => 0, "unlinked_count" => 0];
                if (defined("AGENT_ENABLED") && AGENT_ENABLED) {
                    try {
                        $data["agentSummary"] = $this->model("Agent_model")->summary();
                    } catch (Throwable $e) {
                        // Agent inventory is optional and must never block the main dashboard.
                    }
                }
            }
            $this->view("templates/header_a", $data);
            $this->view("dashboard/index", $data);
            $this->view("templates/footer_a");
        } else {
            if (empty($_SESSION["csrf_login"])) {
                $_SESSION["csrf_login"] = bin2hex(random_bytes(32));
            }
            $data["title"] = "IT-Asset Management";
            $data["saasEnabled"] = SaasTenantContext::enabled();
            $data["companyCode"] = SaasTenantContext::companyCode();
            $data["loginCsrf"] = $_SESSION["csrf_login"];
            $this->view("templates/header", $data);
            $this->view("home/index", $data);
            $this->view("templates/footer");
        }
    }

    public function results()
    {
        if (
            empty($_SESSION["usr"]) ||
            ($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST"
        ) {
            header("location: " . BASEURL);
            exit();
        }

        $search = trim((string) ($_POST["search"] ?? ""));
        $data["title"] = "Bookmark Search Results";
        $data["src"] = $search;
        $data["results"] = $this->model("Home_model")->searchBookMark([
            "search" => $search,
        ]);
        $this->view("templates/header", $data);
        $this->view("home/results", $data);
        $this->view("templates/footer");
    }

    public function members()
    {
        if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
            header("location: " . BASEURL);
            exit();
        }

        if (
            empty($_SESSION["csrf_login"]) ||
            !hash_equals(
                (string) $_SESSION["csrf_login"],
                (string) ($_POST["csrf_token"] ?? ""),
            )
        ) {
            Flasher::setMessage("", "The login form expired. Please try again.", "danger");
            header("location: " . BASEURL);
            exit();
        }

        $now = time();
        $lockedUntil = (int) ($_SESSION["login_locked_until"] ?? 0);
        if ($lockedUntil > $now) {
            Flasher::setMessage(
                "",
                "Too many sign-in attempts. Please wait before trying again.",
                "danger",
            );
            header("location: " . BASEURL);
            exit();
        }

        if (
            SaasTenantContext::enabled() &&
            !SaasTenantContext::resolved()
        ) {
            $this->recordFailedLogin($now);
            Flasher::setMessage("", "Invalid company or sign-in details.", "danger");
            header("location: " . BASEURL);
            exit();
        }

        try {
            // The database connection is first opened during this request.
            // Catch production credential/schema problems so cPanel records a
            // useful error instead of returning an unhelpful blank HTTP 500.
            $data["user"] = $this->model("Home_model")->login($_POST);
        } catch (Throwable $error) {
            $reference = strtoupper(bin2hex(random_bytes(4)));
            $detail = $error->getMessage();
            if ($error->getPrevious()) {
                $detail .= " / " . $error->getPrevious()->getMessage();
            }
            error_log(
                "Login database failure [" .
                    $reference .
                    "]: " .
                    $detail,
            );
            unset($_SESSION["usr"]);
            Flasher::setMessage(
                "",
                "Sign-in could not connect to the application database. Reference: " .
                    $reference,
                "danger",
            );
            header("location: " . BASEURL);
            exit();
        }
        if (!is_array($data["user"])) {
            $this->recordFailedLogin($now);
            // Use one response for unknown users and incorrect passwords.
            Flasher::setMessage("", "Invalid company or sign-in details.", "danger");
            header("location: " . BASEURL);
            exit();
        } else {
            // Authentication uses only the users table. Load the employee and
            // role mapping before creating the session so an incomplete cPanel
            // migration cannot leave a partially authenticated user behind.
            try {
                $access = $this->model("User_model")->getUserById(
                    $data["user"]["username"],
                );
                if (!is_array($access)) {
                    throw new RuntimeException("The authenticated account mapping was not found.");
                }
            } catch (Throwable $error) {
                $detail = $error->getMessage();
                if ($error->getPrevious()) {
                    $detail .= " / " . $error->getPrevious()->getMessage();
                }
                error_log(
                    "Login account initialization failed for user " .
                        (string) $data["user"]["username"] .
                        ": " .
                        $detail,
                );
                unset($_SESSION["usr"]);
                Flasher::setMessage(
                    "",
                    "Sign-in could not be completed because the application database is not fully configured. Contact the system administrator.",
                    "danger",
                );
                header("location: " . BASEURL);
                exit();
            }

            unset(
                $_SESSION["login_attempts"],
                $_SESSION["login_locked_until"],
                $_SESSION["csrf_login"],
            );
            Auth::setLoginSession($data["user"]["username"]);
            $_SESSION["usr"]["name"] = $data["user"]["nama"];
            $_SESSION["usr"]["roles"] = $access["roles"] ?? [];
            $_SESSION["usr"]["empId"] = $access["empId"] ?? null;
            SaasTenantContext::commitCompanySession();
            // Flasher::setMessage('','Login Berhasil','success');
            header("location: " . BASEURL);
            exit();
        }
    }

    public function logout()
    {
        unset($_SESSION["usr"]);
        SaasTenantContext::clearCompanySession();
        Flasher::setMessage("", "System Logout", "success");
        session_regenerate_id(true);
        header("location: " . BASEURL);
        exit();
    }

    public function register()
    {
        // Public self-registration is intentionally disabled. Administrators
        // create and role-map accounts through the User module.
        http_response_code(404);
        echo "Not Found";
    }

    /** Apply a small session-scoped throttle without revealing account state. */
    private function recordFailedLogin(int $now): void
    {
        $attempts = (int) ($_SESSION["login_attempts"] ?? 0) + 1;
        if ($attempts >= 5) {
            $_SESSION["login_locked_until"] = $now + 60;
            $_SESSION["login_attempts"] = 0;
        } else {
            $_SESSION["login_attempts"] = $attempts;
        }
    }
}
