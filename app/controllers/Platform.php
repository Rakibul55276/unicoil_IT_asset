<?php

class Platform extends Controller
{
    public function index()
    {
        $this->requirePlatformAdmin();
        $platform = SaasTenantContext::platform();
        $data = [
            "title" => "SaaS Platform Console",
            "companies" => $platform->listCompanies(),
            "vouchers" => $platform->listVouchers(),
            "redemptions" => $platform->listRedemptions(),
            "csrf" => SaasTenantContext::csrfToken("platform_actions"),
            "newVoucher" => $_SESSION["saas_new_voucher"] ?? null,
        ];
        unset($_SESSION["saas_new_voucher"]);
        $this->view("platform/index", $data);
    }

    public function setup()
    {
        $this->requireSaasMode();
        $platform = SaasTenantContext::platform();
        if ($platform->platformAdminCount() > 0) {
            header("Location: " . BASEURL . "/Platform/login");
            exit();
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            if ($this->platformLoginLocked()) {
                $this->platformMessage(
                    "Too many attempts. Please wait before trying again.",
                    "danger",
                );
            } elseif (
                !SaasTenantContext::verifyCsrf(
                    "platform_setup",
                    $_POST["csrf_token"] ?? "",
                )
            ) {
                $this->recordPlatformLoginFailure();
                $this->platformMessage("The setup form expired.", "danger");
            } elseif (
                SAAS_SETUP_KEY === "" ||
                !hash_equals(SAAS_SETUP_KEY, (string) ($_POST["setup_key"] ?? ""))
            ) {
                $this->recordPlatformLoginFailure();
                $this->platformMessage("The platform setup key is invalid.", "danger");
            } elseif (
                (string) ($_POST["password"] ?? "") !==
                (string) ($_POST["confirm_password"] ?? "")
            ) {
                $this->platformMessage("The passwords do not match.", "danger");
            } else {
                try {
                    $platform->createPlatformAdmin(
                        (string) ($_POST["username"] ?? ""),
                        (string) ($_POST["password"] ?? ""),
                    );
                    $this->clearPlatformLoginFailures();
                    $this->platformMessage(
                        "Platform Administrator created. Sign in now.",
                        "success",
                    );
                    header("Location: " . BASEURL . "/Platform/login");
                    exit();
                } catch (Throwable $error) {
                    $this->platformMessage($error->getMessage(), "danger");
                }
            }
        }

        $this->view("platform/setup", [
            "title" => "Create Platform Administrator",
            "csrf" => SaasTenantContext::csrfToken("platform_setup"),
        ]);
    }

    public function login()
    {
        $this->requireSaasMode();
        $platform = SaasTenantContext::platform();
        if ($platform->platformAdminCount() === 0) {
            header("Location: " . BASEURL . "/Platform/setup");
            exit();
        }
        if (!empty($_SESSION["saas_platform_admin"])) {
            header("Location: " . BASEURL . "/Platform");
            exit();
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            if ($this->platformLoginLocked()) {
                $this->platformMessage(
                    "Too many attempts. Please wait before trying again.",
                    "danger",
                );
            } elseif (
                !SaasTenantContext::verifyCsrf(
                    "platform_login",
                    $_POST["csrf_token"] ?? "",
                )
            ) {
                $this->recordPlatformLoginFailure();
                $this->platformMessage("The login form expired.", "danger");
            } else {
                $admin = $platform->authenticatePlatformAdmin(
                    (string) ($_POST["username"] ?? ""),
                    (string) ($_POST["password"] ?? ""),
                );
                if ($admin) {
                    $this->clearPlatformLoginFailures();
                    session_regenerate_id(true);
                    $_SESSION["saas_platform_admin"] = $admin;
                    header("Location: " . BASEURL . "/Platform");
                    exit();
                }
                $this->recordPlatformLoginFailure();
                $this->platformMessage("Invalid platform login.", "danger");
            }
        }

        $this->view("platform/login", [
            "title" => "Platform Administrator Login",
            "csrf" => SaasTenantContext::csrfToken("platform_login"),
        ]);
    }

    public function logout()
    {
        unset($_SESSION["saas_platform_admin"]);
        header("Location: " . BASEURL . "/Platform/login");
        exit();
    }

    public function createCompany()
    {
        $this->requirePlatformAdmin();
        $this->requirePostAndCsrf();
        try {
            SaasTenantContext::platform()->createCompany($_POST);
            $this->platformMessage("Company created.", "success");
        } catch (Throwable $error) {
            $this->platformMessage($error->getMessage(), "danger");
        }
        $this->redirectConsole();
    }

    public function createVoucher()
    {
        $this->requirePlatformAdmin();
        $this->requirePostAndCsrf();
        try {
            $_SESSION["saas_new_voucher"] = SaasTenantContext::platform()->createVoucher(
                $_POST,
                (string) ($_SESSION["saas_platform_admin"]["username"] ?? ""),
            );
            $this->platformMessage(
                "Voucher generated. Copy it now; it will not be shown again.",
                "success",
            );
        } catch (Throwable $error) {
            $this->platformMessage($error->getMessage(), "danger");
        }
        $this->redirectConsole();
    }

    public function companyStatus()
    {
        $this->requirePlatformAdmin();
        $this->requirePostAndCsrf();
        try {
            SaasTenantContext::platform()->updateCompanyStatus(
                (int) ($_POST["company_id"] ?? 0),
                (string) ($_POST["status"] ?? ""),
            );
            $this->platformMessage("Company status updated.", "success");
        } catch (Throwable $error) {
            $this->platformMessage($error->getMessage(), "danger");
        }
        $this->redirectConsole();
    }

    private function requireSaasMode(): void
    {
        if (!SaasTenantContext::enabled()) {
            http_response_code(404);
            echo "SaaS voucher mode is disabled.";
            exit();
        }
    }

    private function requirePlatformAdmin(): void
    {
        $this->requireSaasMode();
        if (empty($_SESSION["saas_platform_admin"])) {
            header("Location: " . BASEURL . "/Platform/login");
            exit();
        }
    }

    private function requirePostAndCsrf(): void
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            exit("POST is required.");
        }
        if (
            !SaasTenantContext::verifyCsrf(
                "platform_actions",
                $_POST["csrf_token"] ?? "",
            )
        ) {
            http_response_code(403);
            exit("The platform form expired.");
        }
    }

    private function redirectConsole(): void
    {
        header("Location: " . BASEURL . "/Platform");
        exit();
    }

    private function platformMessage(string $message, string $type): void
    {
        $_SESSION["saas_platform_message"] = [
            "message" => $message,
            "type" => $type,
        ];
    }

    private function platformLoginLocked(): bool
    {
        return (int) ($_SESSION["saas_platform_locked_until"] ?? 0) > time();
    }

    private function recordPlatformLoginFailure(): void
    {
        $attempts = (int) ($_SESSION["saas_platform_attempts"] ?? 0) + 1;
        if ($attempts >= 5) {
            $_SESSION["saas_platform_locked_until"] = time() + 60;
            $_SESSION["saas_platform_attempts"] = 0;
            return;
        }
        $_SESSION["saas_platform_attempts"] = $attempts;
    }

    private function clearPlatformLoginFailures(): void
    {
        unset(
            $_SESSION["saas_platform_attempts"],
            $_SESSION["saas_platform_locked_until"],
        );
    }
}
