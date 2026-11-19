<?php
// Dependency-free unit tests for core classes. Run: php unit_tests.php <app root>
$root = $argv[1] ?? "/home/claude/app";
chdir($root);
$pass = 0; $fail = 0;
function t(string $name, bool $ok, string $detail = ""): void {
    global $pass, $fail;
    if ($ok) { $pass++; echo "PASS $name\n"; } else { $fail++; echo "FAIL $name $detail\n"; }
}
function throws(callable $fn): bool { try { $fn(); return false; } catch (Throwable $e) { return true; } }

require "app/config/FeatureSwitches.php";
require "app/core/SaasCrypto.php";

// FeatureSwitches
t("FeatureSwitches: workflow-ui enabled per .dat", FeatureSwitches::enabled("workflow-ui") === true);
t("FeatureSwitches: saas-voucher disabled per .dat", FeatureSwitches::enabled("saas-voucher") === false);
t("FeatureSwitches: unknown switch defaults to off", FeatureSwitches::enabled("does-not-exist") === false);
t("FeatureSwitches: rejects invalid names", throws(fn() => FeatureSwitches::enabled("../etc/passwd")));
t("FeatureSwitches: rejects uppercase names", throws(fn() => FeatureSwitches::enabled("Workflow")));

// SaasCrypto
$key = str_repeat("k", 40);
$enc = SaasCrypto::encrypt("db-secret-123", $key);
t("SaasCrypto: ciphertext differs from plaintext", strpos($enc, "db-secret-123") === false);
t("SaasCrypto: round trip", SaasCrypto::decrypt($enc, $key) === "db-secret-123");
t("SaasCrypto: random IV (two encryptions differ)", $enc !== SaasCrypto::encrypt("db-secret-123", $key));
t("SaasCrypto: wrong key fails", throws(fn() => SaasCrypto::decrypt($enc, str_repeat("x", 40))));
$tampered = substr($enc, 0, -4) . (substr($enc, -4) === "AAAA" ? "BBBB" : "AAAA");
t("SaasCrypto: tampered ciphertext rejected", throws(fn() => SaasCrypto::decrypt($tampered, $key)));
t("SaasCrypto: short key rejected", throws(fn() => SaasCrypto::encrypt("x", "short")));

// Router rules (static: mirrors App::__construct validation)
$src = file_get_contents("app/core/App.php");
t("App: legacy demo controllers disabled", strpos($src, '["about", "mahasiswa", "member"]') !== false);
t("App: controller name whitelist regex", strpos($src, "preg_match('/^[A-Za-z][A-Za-z0-9_]*\$/', \$requestedController)") !== false);
t("App: only public, non-inherited methods dispatchable", strpos($src, "getDeclaringClass()->getName() === \$controllerName") !== false);

// Every controller's state-changing public method has a server-side guard
$guards = ["requireRoles", "requireAssetRole", "requireReturnProcessor", "requireRole", "requireAnyRole",
    "requirePost", "requirePostAndToken", "requirePlatformAdmin", "requireSaasUser", "requireAccessAdministrator",
    "requireWorkflowUiEnabled", "requireSaasMode", "requireTransactionDocumentAccess", "loadVisibleRequest", "requirePostDeleteToken"];
$unguarded = [];
foreach (glob("app/controllers/*.php") as $file) {
    $class = basename($file, ".php");
    if (in_array(strtolower($class), ["about", "mahasiswa", "member", "home", "agent", "agentapi"], true)) continue;
    $code = file_get_contents($file);
    $ctorGuard = preg_match('/function __construct\(\)\s*\{(.*?)\n    \}/s', $code, $m) && preg_match('/requireRoles|SESSION\["usr"\]/', $m[1]);
    preg_match_all('/public function (\w+)\([^)]*\)\s*\{(.*?)\n    \}/s', $code, $all, PREG_SET_ORDER);
    foreach ($all as [$_, $name, $body]) {
        if ($name === "__construct") continue;
        if ("$class::$name" === "Platform::logout") continue; // intentionally public: only clears the platform session
        $hasGuard = false;
        foreach ($guards as $g) { if (strpos($body, $g) !== false) { $hasGuard = true; break; } }
        if (!$hasGuard && !$ctorGuard) $unguarded[] = "$class::$name";
    }
}
t("Controllers: every public action is behind a session or role guard", !$unguarded, implode(", ", $unguarded));

// Asset report/document routes now carry role or ownership guards
$asset = file_get_contents("app/controllers/Asset.php");
foreach (["active", "inactive", "returnHistory", "auditReport", "exportAuditExcel", "exportAuditPdf", "archive",
          "exportArchiveExcel", "exportArchivePdf", "printHistory", "printLabel"] as $m) {
    preg_match('/public function ' . $m . '\([^)]*\)\s*\{(.*?)\n    \}/s', $asset, $mm);
    t("Asset::$m requires ISSUER/IT_MANAGER/ADMIN", isset($mm[1]) && strpos($mm[1], 'requireAssetRole') !== false);
}
foreach (["printAssignment", "downloadAssignmentPdf", "printReturn", "downloadReturnPdf"] as $m) {
    preg_match('/public function ' . $m . '\([^)]*\)\s*\{(.*?)\n    \}/s', $asset, $mm);
    t("Asset::$m checks document ownership", isset($mm[1]) && strpos($mm[1], 'requireTransactionDocumentAccess') !== false);
}
t("Asset::getAllDepartments (broken, HTTP 500) removed", strpos($asset, "function getAllDepartments") === false);

// Request workflow invariants in the model
$req = file_get_contents("app/models/Request_model.php");
t("Request_model: MAX 8 assets constant", preg_match('/MAX_REQUEST_ASSETS\s*=\s*8/', $req) === 1);
t("Request_model: route excludes requester as dept approver", strpos($req, "u.username<>:requester") !== false);
t("Request_model: IT approver excludes requester", strpos($req, 'firstRoleUser("IT_MANAGER", $requester)') !== false);
t("Request_model: status transitions guarded by WHERE status", substr_count($req, "AND status=") >= 4);
$am = file_get_contents("app/models/Asset_model.php");
t("Asset_model: issuance locks request row FOR UPDATE", strpos($am, "WHERE r.request_id=:requestId FOR UPDATE") !== false);
t("Asset_model: return form cannot scrap (REISSUE only)", strpos($am, '$data["returnType"] !== "REISSUE"') !== false);

echo "\n$pass passed, $fail failed\n";
exit($fail ? 1 : 0);
