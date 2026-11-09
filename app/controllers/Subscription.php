<?php

class Subscription extends Controller
{
    public function index()
    {
        $this->requireSaasUser();
        $company = SaasTenantContext::platform()->getCompanyById(
            SaasTenantContext::companyId(),
        );
        if (!$company) {
            http_response_code(404);
            echo "Company subscription was not found.";
            return;
        }

        $data = [
            "title" => "Company Subscription",
            "company" => $company,
            "state" => SaasTenantContext::platform()->subscriptionState($company),
            "canRedeem" => $this->isCompanyAdministrator(),
            "csrf" => SaasTenantContext::csrfToken("subscription_redeem"),
        ];
        $this->view("subscription/index", $data);
    }

    public function redeem()
    {
        $this->requireSaasUser();
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            echo "POST is required.";
            return;
        }
        if (!$this->isCompanyAdministrator()) {
            http_response_code(403);
            echo "Only a company Administrator can redeem a voucher.";
            return;
        }
        if (
            !SaasTenantContext::verifyCsrf(
                "subscription_redeem",
                $_POST["csrf_token"] ?? "",
            )
        ) {
            Flasher::setMessage("", "The renewal form expired. Please try again.", "danger");
            $this->redirect();
        }

        try {
            $result = SaasTenantContext::platform()->redeemVoucher(
                SaasTenantContext::companyId(),
                (string) ($_POST["voucher_code"] ?? ""),
                (string) ($_SESSION["usr"]["user"] ?? ""),
            );
            Flasher::setMessage(
                "Subscription renewed.",
                "New expiry: " . $result["newExpiry"],
                "success",
            );
        } catch (Throwable $error) {
            Flasher::setMessage("", $error->getMessage(), "danger");
        }
        $this->redirect();
    }

    private function requireSaasUser(): void
    {
        if (!SaasTenantContext::enabled()) {
            http_response_code(404);
            echo "Subscription mode is not enabled.";
            exit();
        }
        if (!SaasTenantContext::resolved() || empty($_SESSION["usr"])) {
            header("Location: " . BASEURL);
            exit();
        }
    }

    private function isCompanyAdministrator(): bool
    {
        $username = strtolower(trim((string) ($_SESSION["usr"]["user"] ?? "")));
        $roles = $_SESSION["usr"]["roles"] ?? [];
        return $username === "admin" || in_array("ADMINISTRATOR", $roles, true);
    }

    private function redirect(): void
    {
        header("Location: " . BASEURL . "/Subscription");
        exit();
    }
}
