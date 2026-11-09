<?php

/**
 * Resolves a company before the legacy Database class is instantiated.
 * Existing models consequently connect to that company's dedicated database.
 */
class SaasTenantContext
{
    private static bool $enabled = false;
    private static ?array $company = null;
    private static ?array $databaseConfig = null;
    private static ?SaasPlatform $platform = null;
    private static ?string $error = null;
    private static array $rootConfig = [];
    private static string $requestedCode = "";

    public static function boot(array $rootConfig): void
    {
        self::$rootConfig = $rootConfig;
        self::$enabled = filter_var(
            $rootConfig["saas_voucher_enabled"] ?? false,
            FILTER_VALIDATE_BOOLEAN,
        );
        if (!self::$enabled) {
            return;
        }

        self::$requestedCode = self::requestedCompanyCode();
        if (self::$requestedCode === "") {
            return;
        }

        try {
            self::$platform = new SaasPlatform($rootConfig);
            self::$company = self::$platform->getCompanyByCode(
                self::$requestedCode,
            );
            if (!self::$company) {
                self::$error = "Company code was not found.";
                return;
            }
            self::$databaseConfig = self::$platform->tenantDatabase(self::$company);
            if (
                trim((string) self::$databaseConfig["db_host"]) === "" ||
                trim((string) self::$databaseConfig["db_name"]) === "" ||
                trim((string) self::$databaseConfig["db_user"]) === ""
            ) {
                self::$error = "The company database is not configured.";
                self::$databaseConfig = null;
            }
        } catch (Throwable $error) {
            self::$error = "SaaS platform unavailable: " . $error->getMessage();
            error_log(self::$error);
        }
    }

    public static function enabled(): bool
    {
        return self::$enabled;
    }

    public static function company(): ?array
    {
        return self::$company;
    }

    public static function companyId(): int
    {
        return (int) (self::$company["company_id"] ?? 0);
    }

    public static function companyCode(): string
    {
        return (string) (self::$company["company_code"] ?? self::$requestedCode);
    }

    public static function storagePrefix(): string
    {
        if (!self::enabled() || !self::resolved()) {
            return "";
        }
        return strtolower(
            preg_replace('/[^A-Z0-9_-]/i', "", self::companyCode()),
        ) . "_";
    }

    public static function databaseConfig(): ?array
    {
        return self::$databaseConfig;
    }

    public static function resolved(): bool
    {
        return self::$company !== null && self::$databaseConfig !== null;
    }

    public static function error(): ?string
    {
        return self::$error;
    }

    public static function platform(): SaasPlatform
    {
        if (!self::$platform) {
            self::$platform = new SaasPlatform(self::$rootConfig);
        }
        return self::$platform;
    }

    public static function subscriptionState(): string
    {
        if (!self::$company) {
            return "NO_COMPANY";
        }
        return self::platform()->subscriptionState(self::$company);
    }

    public static function hasApplicationAccess(): bool
    {
        return in_array(
            self::subscriptionState(),
            ["ACTIVE", "TRIAL", "GRACE"],
            true,
        );
    }

    public static function commitCompanySession(): void
    {
        if (!self::resolved()) {
            return;
        }
        $_SESSION["saas_company"] = [
            "company_id" => self::companyId(),
            "company_code" => self::companyCode(),
        ];
    }

    public static function clearCompanySession(): void
    {
        unset($_SESSION["saas_company"]);
    }

    public static function csrfToken(string $scope): string
    {
        $key = "saas_csrf_" . preg_replace('/[^a-z0-9_-]/i', "", $scope);
        if (empty($_SESSION[$key])) {
            $_SESSION[$key] = bin2hex(random_bytes(24));
        }
        return (string) $_SESSION[$key];
    }

    public static function verifyCsrf(string $scope, $token): bool
    {
        return hash_equals(
            self::csrfToken($scope),
            (string) ($token ?? ""),
        );
    }

    private static function requestedCompanyCode(): string
    {
        $sessionCode =
            $_SESSION["saas_company"]["company_code"] ?? "";
        $postedCode =
            ($_SERVER["REQUEST_METHOD"] ?? "") === "POST"
                ? ($_POST["company_code"] ?? "")
                : "";
        $code = $sessionCode !== "" ? $sessionCode : $postedCode;
        $code = strtoupper(trim((string) $code));
        return preg_match('/^[A-Z0-9][A-Z0-9_-]{1,29}$/', $code) ? $code : "";
    }
}
