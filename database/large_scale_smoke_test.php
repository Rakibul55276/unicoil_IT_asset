<?php

// Read-only CLI smoke test for the bounded large-data queries.
if (PHP_SAPI !== "cli") {
    http_response_code(404);
    exit();
}

chdir(dirname(__DIR__));
require "app/init.php";
require_once "app/models/Asset_model.php";
require_once "app/models/Employee_model.php";
require_once "app/models/Request_model.php";
require_once "app/models/Scrap_model.php";

$assets = new Asset_model();
$stock = $assets->getAssetPage("IN_STOCK", ["page" => 1, "perPage" => 25]);
$active = $assets->getActiveAssetsPage(["page" => 1, "perPage" => 25]);
$returns = $assets->getFilteredReturnHistory([
    "page" => 1,
    "perPage" => 25,
    "sort" => "date_desc",
]);
$openIssues = $assets->searchOpenIssues("DT", "", 30);
$audit = $assets->getAuditReport(
    date("Y-m-01"),
    date("Y-m-d"),
    "",
    "",
    "",
    "",
    "",
    "",
    "",
    "",
    10,
);
$employees = (new Employee_model())->getAllEmployeeWithAssets();
$requestAssets = (new Request_model())->searchAvailableAssetsForItem(1, 1, "DT", 30);
$scrapAssets = (new Scrap_model())->searchAvailableAssets("DT", 30);

if (
    count($stock["items"]) > 25 ||
    count($active["items"]) > 25 ||
    count($returns["items"]) > 25 ||
    count($openIssues) > 30 ||
    count($requestAssets) > 30 ||
    count($scrapAssets) > 30 ||
    count($audit) > 10
) {
    throw new RuntimeException("A bounded query returned more rows than allowed.");
}

echo json_encode(
    [
        "ok" => true,
        "stock" => ["total" => $stock["total"], "page_rows" => count($stock["items"])],
        "active" => ["total" => $active["total"], "page_rows" => count($active["items"])],
        "returns" => ["total" => $returns["total"], "page_rows" => count($returns["items"])],
        "open_issue_search_rows" => count($openIssues),
        "request_asset_search_rows" => count($requestAssets),
        "scrap_asset_search_rows" => count($scrapAssets),
        "employees" => count($employees),
        "audit_sample_rows" => count($audit),
    ],
    JSON_PRETTY_PRINT,
) . PHP_EOL;

