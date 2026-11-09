<?php

/**
 * Provides access only to the SaaS control database. Existing asset models
 * continue to use Database and therefore remain isolated from this layer.
 */
class SaasPlatform
{
    private PDO $pdo;
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $host = trim((string) ($config["platform_db_host"] ?? ""));
        $name = trim((string) ($config["platform_db_name"] ?? ""));
        $user = (string) ($config["platform_db_user"] ?? "");
        $pass = (string) ($config["platform_db_pass"] ?? "");
        if ($host === "" || $name === "" || $user === "") {
            throw new RuntimeException("The SaaS platform database is not configured.");
        }

        $this->pdo = new PDO(
            "mysql:host={$host};dbname={$name};charset=utf8mb4",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false,
            ],
        );
    }

    public function getCompanyByCode(string $code): ?array
    {
        $statement = $this->pdo->prepare(
            "SELECT * FROM saas_company WHERE company_code=:code LIMIT 1",
        );
        $statement->execute(["code" => self::companyCode($code)]);
        $company = $statement->fetch();
        return $company ?: null;
    }

    public function getCompanyById(int $companyId): ?array
    {
        $statement = $this->pdo->prepare(
            "SELECT * FROM saas_company WHERE company_id=:id LIMIT 1",
        );
        $statement->execute(["id" => $companyId]);
        $company = $statement->fetch();
        return $company ?: null;
    }

    public function tenantDatabase(array $company): array
    {
        $password = trim((string) ($company["db_pass_encrypted"] ?? ""));
        if ($password !== "") {
            $password = SaasCrypto::decrypt(
                $password,
                (string) ($this->config["saas_encryption_key"] ?? ""),
            );
        } else {
            $password = (string) ($this->config["db_pass"] ?? "");
        }

        return [
            "db_host" =>
                trim((string) ($company["db_host"] ?? "")) ?:
                (string) ($this->config["db_host"] ?? ""),
            "db_name" => trim((string) ($company["db_name"] ?? "")),
            "db_user" =>
                trim((string) ($company["db_user"] ?? "")) ?:
                (string) ($this->config["db_user"] ?? ""),
            "db_pass" => $password,
        ];
    }

    public function subscriptionState(array $company): string
    {
        $status = strtoupper(trim((string) ($company["status"] ?? "")));
        if ($status === "SUSPENDED") {
            return "SUSPENDED";
        }
        if ($status === "INACTIVE") {
            return "INACTIVE";
        }

        $today = new DateTimeImmutable("today");
        $expiry = self::dateOrNull($company["subscription_expires_at"] ?? null);
        if ($expiry && $expiry >= $today) {
            return $status === "TRIAL" ? "TRIAL" : "ACTIVE";
        }

        $grace = self::dateOrNull($company["grace_until"] ?? null);
        return $grace && $grace >= $today ? "GRACE" : "EXPIRED";
    }

    public function listCompanies(): array
    {
        return $this->pdo
            ->query(
                "SELECT company_id,company_code,company_name,status,
                        subscription_started_at,subscription_expires_at,grace_until,
                        db_host,db_name,db_user,created_at
                 FROM saas_company
                 ORDER BY company_name",
            )
            ->fetchAll();
    }

    public function createCompany(array $data): int
    {
        $code = self::companyCode((string) ($data["company_code"] ?? ""));
        $name = trim((string) ($data["company_name"] ?? ""));
        $dbName = trim((string) ($data["db_name"] ?? ""));
        if ($code === "" || $name === "" || $dbName === "") {
            throw new InvalidArgumentException(
                "Company code, company name and tenant database name are required.",
            );
        }

        $dbPassword = (string) ($data["db_pass"] ?? "");
        $tenantHost =
            trim((string) ($data["db_host"] ?? "")) ?:
            (string) ($this->config["db_host"] ?? "");
        $tenantUser =
            trim((string) ($data["db_user"] ?? "")) ?:
            (string) ($this->config["db_user"] ?? "");
        $tenantPassword =
            $dbPassword !== ""
                ? $dbPassword
                : (string) ($this->config["db_pass"] ?? "");
        $this->assertTenantDatabase(
            $tenantHost,
            $dbName,
            $tenantUser,
            $tenantPassword,
        );

        $encryptedPassword =
            $dbPassword === ""
                ? ""
                : SaasCrypto::encrypt(
                    $dbPassword,
                    (string) ($this->config["saas_encryption_key"] ?? ""),
                );
        $trialDays = max(0, min(365, (int) ($data["trial_days"] ?? 0)));
        $today = new DateTimeImmutable("today");
        $expiry = $trialDays > 0 ? $today->modify("+{$trialDays} days") : null;

        $statement = $this->pdo->prepare(
            "INSERT INTO saas_company
             (company_code,company_name,status,subscription_started_at,
              subscription_expires_at,grace_until,db_host,db_name,db_user,
              db_pass_encrypted)
             VALUES
             (:code,:name,:status,:started,:expires,:grace,:host,:dbName,:dbUser,:dbPass)",
        );
        $statement->execute([
            "code" => $code,
            "name" => mb_substr($name, 0, 150),
            "status" => $trialDays > 0 ? "TRIAL" : "INACTIVE",
            "started" => $trialDays > 0 ? $today->format("Y-m-d") : null,
            "expires" => $expiry ? $expiry->format("Y-m-d") : null,
            "grace" => $expiry ? $expiry->modify("+7 days")->format("Y-m-d") : null,
            "host" => mb_substr(trim((string) ($data["db_host"] ?? "")), 0, 190),
            "dbName" => mb_substr($dbName, 0, 190),
            "dbUser" => mb_substr(trim((string) ($data["db_user"] ?? "")), 0, 190),
            "dbPass" => $encryptedPassword,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateCompanyStatus(int $companyId, string $status): void
    {
        $status = strtoupper(trim($status));
        if (!in_array($status, ["ACTIVE", "INACTIVE", "SUSPENDED"], true)) {
            throw new InvalidArgumentException("Invalid company status.");
        }
        $statement = $this->pdo->prepare(
            "UPDATE saas_company SET status=:status WHERE company_id=:id",
        );
        $statement->execute(["status" => $status, "id" => $companyId]);
    }

    /**
     * Returns the one-time plaintext voucher. Only its SHA-256 hash is stored.
     */
    public function createVoucher(array $data, string $createdBy): string
    {
        $durationDays = max(1, min(3650, (int) ($data["duration_days"] ?? 0)));
        $assignedCompanyId = (int) ($data["assigned_company_id"] ?? 0);
        $validDays = max(1, min(365, (int) ($data["valid_days"] ?? 90)));

        $plainCode =
            "VCH-" .
            strtoupper(substr(bin2hex(random_bytes(12)), 0, 8)) .
            "-" .
            strtoupper(substr(bin2hex(random_bytes(12)), 0, 8)) .
            "-" .
            strtoupper(substr(bin2hex(random_bytes(12)), 0, 8));
        $statement = $this->pdo->prepare(
            "INSERT INTO saas_voucher
             (code_hash,code_prefix,duration_days,status,assigned_company_id,
              valid_until,created_by)
             VALUES
             (:hash,:prefix,:duration,'UNUSED',:company,:validUntil,:createdBy)",
        );
        $statement->execute([
            "hash" => hash("sha256", self::voucherCode($plainCode)),
            "prefix" => substr($plainCode, 0, 12),
            "duration" => $durationDays,
            "company" => $assignedCompanyId > 0 ? $assignedCompanyId : null,
            "validUntil" => (new DateTimeImmutable("today"))
                ->modify("+{$validDays} days")
                ->format("Y-m-d"),
            "createdBy" => mb_substr($createdBy, 0, 100),
        ]);

        return $plainCode;
    }

    public function listVouchers(int $limit = 100): array
    {
        $limit = max(1, min(500, $limit));
        return $this->pdo
            ->query(
                "SELECT v.voucher_id,v.code_prefix,v.duration_days,v.status,
                        v.valid_until,v.created_by,v.created_at,v.redeemed_at,
                        assigned.company_name AS assigned_company,
                        redeemed.company_name AS redeemed_company
                 FROM saas_voucher v
                 LEFT JOIN saas_company assigned
                   ON assigned.company_id=v.assigned_company_id
                 LEFT JOIN saas_company redeemed
                   ON redeemed.company_id=v.redeemed_company_id
                 ORDER BY v.voucher_id DESC
                 LIMIT {$limit}",
            )
            ->fetchAll();
    }

    public function listRedemptions(int $limit = 100): array
    {
        $limit = max(1, min(500, $limit));
        return $this->pdo
            ->query(
                "SELECT r.redemption_id,r.redeemed_by,r.previous_expiry,
                        r.new_expiry,r.redeemed_at,c.company_code,c.company_name,
                        v.code_prefix,v.duration_days
                 FROM saas_voucher_redemption r
                 JOIN saas_company c ON c.company_id=r.company_id
                 JOIN saas_voucher v ON v.voucher_id=r.voucher_id
                 ORDER BY r.redemption_id DESC
                 LIMIT {$limit}",
            )
            ->fetchAll();
    }

    public function redeemVoucher(
        int $companyId,
        string $plainCode,
        string $redeemedBy,
    ): array {
        $codeHash = hash("sha256", self::voucherCode($plainCode));
        $today = new DateTimeImmutable("today");

        $this->pdo->beginTransaction();
        try {
            $voucherStatement = $this->pdo->prepare(
                "SELECT * FROM saas_voucher WHERE code_hash=:hash FOR UPDATE",
            );
            $voucherStatement->execute(["hash" => $codeHash]);
            $voucher = $voucherStatement->fetch();
            if (!$voucher || $voucher["status"] !== "UNUSED") {
                throw new InvalidArgumentException("Voucher is invalid or already used.");
            }
            if (
                !empty($voucher["valid_until"]) &&
                new DateTimeImmutable($voucher["valid_until"]) < $today
            ) {
                throw new InvalidArgumentException("Voucher has expired.");
            }
            if (
                !empty($voucher["assigned_company_id"]) &&
                (int) $voucher["assigned_company_id"] !== $companyId
            ) {
                throw new InvalidArgumentException(
                    "Voucher is assigned to another company.",
                );
            }

            $companyStatement = $this->pdo->prepare(
                "SELECT * FROM saas_company WHERE company_id=:id FOR UPDATE",
            );
            $companyStatement->execute(["id" => $companyId]);
            $company = $companyStatement->fetch();
            if (!$company) {
                throw new InvalidArgumentException("Company was not found.");
            }
            if (strtoupper((string) $company["status"]) === "SUSPENDED") {
                throw new InvalidArgumentException(
                    "Suspended companies cannot redeem vouchers.",
                );
            }

            $previousExpiry = self::dateOrNull(
                $company["subscription_expires_at"] ?? null,
            );
            $baseDate =
                $previousExpiry && $previousExpiry >= $today ? $previousExpiry : $today;
            $newExpiry = $baseDate->modify(
                "+" . (int) $voucher["duration_days"] . " days",
            );
            $newGrace = $newExpiry->modify("+7 days");

            $companyUpdate = $this->pdo->prepare(
                "UPDATE saas_company
                 SET status='ACTIVE',
                     subscription_started_at=COALESCE(subscription_started_at,:started),
                     subscription_expires_at=:expires,
                     grace_until=:grace
                 WHERE company_id=:company",
            );
            $companyUpdate->execute([
                "started" => $today->format("Y-m-d"),
                "expires" => $newExpiry->format("Y-m-d"),
                "grace" => $newGrace->format("Y-m-d"),
                "company" => $companyId,
            ]);

            $voucherUpdate = $this->pdo->prepare(
                "UPDATE saas_voucher
                 SET status='REDEEMED',redeemed_company_id=:company,
                     redeemed_at=NOW()
                 WHERE voucher_id=:voucher",
            );
            $voucherUpdate->execute([
                "company" => $companyId,
                "voucher" => $voucher["voucher_id"],
            ]);

            $redemption = $this->pdo->prepare(
                "INSERT INTO saas_voucher_redemption
                 (voucher_id,company_id,redeemed_by,previous_expiry,new_expiry)
                 VALUES (:voucher,:company,:redeemedBy,:previousExpiry,:newExpiry)",
            );
            $redemption->execute([
                "voucher" => $voucher["voucher_id"],
                "company" => $companyId,
                "redeemedBy" => mb_substr($redeemedBy, 0, 100),
                "previousExpiry" => $previousExpiry
                    ? $previousExpiry->format("Y-m-d")
                    : null,
                "newExpiry" => $newExpiry->format("Y-m-d"),
            ]);

            $this->pdo->commit();
            return [
                "previousExpiry" => $previousExpiry
                    ? $previousExpiry->format("Y-m-d")
                    : null,
                "newExpiry" => $newExpiry->format("Y-m-d"),
                "durationDays" => (int) $voucher["duration_days"],
            ];
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    public function platformAdminCount(): int
    {
        return (int) $this->pdo
            ->query("SELECT COUNT(*) FROM saas_platform_admin")
            ->fetchColumn();
    }

    public function createPlatformAdmin(
        string $username,
        string $password,
    ): void {
        $username = trim($username);
        if (
            !preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username) ||
            strlen($password) < 12
        ) {
            throw new InvalidArgumentException(
                "Use a valid username and a password of at least 12 characters.",
            );
        }
        if ($this->platformAdminCount() > 0) {
            throw new RuntimeException("The first platform administrator already exists.");
        }

        $statement = $this->pdo->prepare(
            "INSERT INTO saas_platform_admin
             (username,password_hash,active)
             VALUES (:username,:password,1)",
        );
        $statement->execute([
            "username" => $username,
            "password" => password_hash($password, PASSWORD_DEFAULT),
        ]);
    }

    public function authenticatePlatformAdmin(
        string $username,
        string $password,
    ): ?array {
        $statement = $this->pdo->prepare(
            "SELECT admin_id,username,password_hash
             FROM saas_platform_admin
             WHERE username=:username AND active=1
             LIMIT 1",
        );
        $statement->execute(["username" => trim($username)]);
        $admin = $statement->fetch();
        if (!$admin || !password_verify($password, $admin["password_hash"])) {
            return null;
        }
        unset($admin["password_hash"]);
        return $admin;
    }

    private static function companyCode(string $code): string
    {
        $code = strtoupper(trim($code));
        return preg_match('/^[A-Z0-9][A-Z0-9_-]{1,29}$/', $code) ? $code : "";
    }

    private static function voucherCode(string $code): string
    {
        return strtoupper(preg_replace('/\s+/', "", trim($code)));
    }

    private static function dateOrNull($value): ?DateTimeImmutable
    {
        $value = trim((string) ($value ?? ""));
        if ($value === "") {
            return null;
        }
        try {
            return new DateTimeImmutable($value);
        } catch (Throwable $error) {
            return null;
        }
    }

    private function assertTenantDatabase(
        string $host,
        string $database,
        string $user,
        string $password,
    ): void {
        try {
            $tenant = new PDO(
                "mysql:host={$host};dbname={$database};charset=utf8mb4",
                $user,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ],
            );
            foreach (["users", "assetdata", "asset_issue", "asset_return"] as $table) {
                $tenant->query("SELECT 1 FROM `{$table}` LIMIT 1");
            }
        } catch (Throwable $error) {
            throw new RuntimeException(
                "Tenant database connection or required tables could not be verified.",
                0,
                $error,
            );
        }
    }
}
