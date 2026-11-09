<!DOCTYPE html>
<html lang="en">

<head>
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
  <meta charset="utf-8" />
  <title><?= htmlspecialchars(
      (string) ($data["title"] ?? "IT Asset Management"),
      ENT_QUOTES,
      "UTF-8",
  ) ?></title>
  <?php require __DIR__ . "/favicon.php"; ?>

  <meta name="description" content="overview &amp; stats" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

  <!-- bootstrap & fontawesome -->
  <link rel="stylesheet" href="<?= BASEURL ?>/assets/css/bootstrap.min.css" />
  <link rel="stylesheet" href="<?= BASEURL ?>/assets/font-awesome/4.5.0/css/font-awesome.min.css" />
  <link rel="stylesheet" href="<?= BASEURL ?>/assets/css/ui.jqgrid.min.css" />
  <link rel="stylesheet" href="<?= BASEURL ?>/assets/css/jquery-ui.min.css" />
  <link rel="stylesheet" href="<?= BASEURL ?>/assets/css/select2.min.css" />
  <link rel="stylesheet" href="<?= BASEURL ?>/assets/css/ace.min.css" class="ace-main-stylesheet"
    id="main-ace-style" />
  <link rel="stylesheet" href="<?= BASEURL ?>/assets/css/ace-skins.min.css" />
  <link rel="stylesheet" href="<?= BASEURL ?>/assets/css/ace-rtl.min.css" />
  <?php
  // File versioning prevents browsers from keeping an outdated layout after
  // shared shell CSS is updated.
  $modernShellVersion = (string) @filemtime(
      dirname(__DIR__, 3) . "/assets/css/modern-shell.css",
  );
  ?>
  <link rel="stylesheet"
    href="<?= BASEURL ?>/assets/css/modern-shell.css?v=<?= rawurlencode($modernShellVersion) ?>" />
  <?php if (
      defined("AGENT_ENABLED") && AGENT_ENABLED && !empty($data["agentPage"])
  ): ?>
  <link rel="stylesheet" href="<?= BASEURL ?>/assets/css/agent-module.css" />
  <link rel="stylesheet" href="<?= BASEURL ?>/assets/css/agent-detail.css" />
  <link rel="stylesheet" href="<?= BASEURL ?>/assets/css/agent-bulk.css" /><?php endif; ?>
  <script src="<?= BASEURL ?>/assets/js/ace-extra.min.js"></script>

</head>

<?php
// Navigation is intentionally role-based so each user sees only the work that
// belongs to them. The admin account retains the complete system navigation.
$navigationRoles = array_values(array_filter($_SESSION["usr"]["roles"] ?? []));
$navigationUsername = strtolower(trim((string) ($_SESSION["usr"]["user"] ?? "")));
$isSystemAdmin =
    $navigationUsername === "admin" || in_array("ADMINISTRATOR", $navigationRoles, true);
$workflowUiEnabled = defined("WORKFLOW_UI_ENABLED") ? WORKFLOW_UI_ENABLED : true;
$issuerUiEnabled = defined("ISSUER_UI_ENABLED") ? ISSUER_UI_ENABLED : false;
$scrapApprovalEnabled = defined("SCRAP_APPROVAL_ENABLED")
    ? SCRAP_APPROVAL_ENABLED
    : true;
$accessoryInventoryEnabled = defined("ACCESSORY_INVENTORY_ENABLED")
    ? ACCESSORY_INVENTORY_ENABLED
    : false;

// General workflow and Issuer operational navigation are independently switchable.
// Existing role assignments are intentionally left unchanged.
$hasNavigationRole = static function (array $requiredRoles) use (
    $navigationRoles,
    $isSystemAdmin,
    $workflowUiEnabled,
    $issuerUiEnabled,
): bool {
    return $isSystemAdmin ||
        ($workflowUiEnabled && (bool) array_intersect($navigationRoles, $requiredRoles)) ||
        ($issuerUiEnabled &&
            in_array("ISSUER", $navigationRoles, true) &&
            in_array("ISSUER", $requiredRoles, true));
};
$canManageAssets = $hasNavigationRole(["ISSUER", "IT_MANAGER"]);
$canPerformAssetOperations = $hasNavigationRole(["ISSUER"]);
$canManageAccessories =
    $accessoryInventoryEnabled &&
    ($isSystemAdmin || in_array("ISSUER", $navigationRoles, true));
$canProcessReturns = $hasNavigationRole(["ISSUER"]);
$canManageEmployees = $hasNavigationRole(["HR_REQUESTER", "ISSUER"]);
$canManageMasterData = $hasNavigationRole(["IT_MANAGER", "ISSUER"]);
$canReviewScrap =
    $isSystemAdmin || (bool) array_intersect($navigationRoles, ["ISSUER", "IT_MANAGER"]);
$canViewScrapList = $scrapApprovalEnabled && $hasNavigationRole(["ISSUER"]);
$canViewAudit = $hasNavigationRole(["ISSUER", "IT_MANAGER"]);
$canViewAssetDetails = $hasNavigationRole(["ISSUER", "IT_MANAGER"]);
$canViewWarrantyAlerts = $hasNavigationRole(["ISSUER", "IT_MANAGER"]);
$canViewAssetReports = $canManageAssets;
$canViewAssetDirectory =
    $isSystemAdmin || (bool) array_intersect($navigationRoles, ["ISSUER", "IT_MANAGER"]);
$canViewRequestHistory =
    $workflowUiEnabled && $hasNavigationRole(["ISSUER"]);
$warrantyNotification = [
    "count" => 0,
    "expired" => 0,
    "dueSoon" => 0,
];
if ($canViewWarrantyAlerts) {
    try {
        require_once dirname(__DIR__, 2) . "/models/Asset_model.php";
        $warrantyNotification = (new Asset_model())->getWarrantyAlertSummary();
    } catch (Throwable $warrantyNotificationError) {
        // Warranty alerts must never prevent the application shell from loading.
        error_log(
            "Warranty notification unavailable: " .
                $warrantyNotificationError->getMessage(),
        );
    }
}
$requestNotification = ["count" => 0, "url" => BASEURL . "/Request"];
if ($workflowUiEnabled) {
    try {
        require_once dirname(__DIR__, 2) . "/models/Request_model.php";
        $requestNotificationModel = new Request_model();
        $requestNotificationContext = $requestNotificationModel->getUserContext(
            (string) ($_SESSION["usr"]["user"] ?? ""),
        );
        if ($requestNotificationContext) {
            $requestNotification = $requestNotificationModel->getNotificationSummary(
                $requestNotificationContext,
            );
        }
    } catch (Throwable $notificationError) {
        // The shell must remain available while a new database migration is being applied.
        error_log("Request notification unavailable: " . $notificationError->getMessage());
    }
}
?>
<body class="skin-1<?= !empty($data["reportPage"]) ? " report-page" : "" ?><?= !$isSystemAdmin
    ? " master-data-read-add-only"
    : "" ?><?= !$isSystemAdmin && in_array("ISSUER", $navigationRoles, true)
    ? " issuer-restricted-ui"
    : "" ?>">
  <style>
    /* Presentation-only restriction: destructive controls stay hidden for Issuer accounts. */
    .issuer-restricted-ui a[href*="/delete/"],
    .issuer-restricted-ui a[href*="/hapusData/"],
    .issuer-restricted-ui form[action*="/delete"],
    .issuer-restricted-ui form[action*="/hapusData"],
    .issuer-restricted-ui .scrap-delete-btn,
    .issuer-restricted-ui .asset-image-close,
    .master-data-read-add-only a[href*="/Employee/edit/"],
    .master-data-read-add-only a[href*="/Department/edit/"],
    .master-data-read-add-only a[href*="/Assetkat/edit/"],
    .master-data-read-add-only a[href*="/Location/edit/"],
    .master-data-read-add-only a[href*="/Unit/edit/"],
    .master-data-read-add-only a[href*="/Vendor/edit/"],
    .master-data-read-add-only a[href*="/Employee/hapusData/"],
    .master-data-read-add-only a[href*="/Department/hapusData/"],
    .master-data-read-add-only a[href*="/Assetkat/hapusData/"],
    .master-data-read-add-only a[href*="/Location/delete/"],
    .master-data-read-add-only a[href*="/Unit/hapusData/"],
    .master-data-read-add-only form[action*="/Assetkat/hapusData/"],
    .master-data-read-add-only form[action*="/Vendor/delete"] { display: none !important; }
  </style>
  <!-- NAVBAR -->
  <div id="navbar" class="navbar navbar-default ace-save-state">
    <div class="navbar-container ace-save-state" id="navbar-container">
      <button type="button" class="navbar-toggle menu-toggler pull-left" id="menu-toggler"
        data-target="#sidebar">
        <span class="sr-only">Toggle sidebar</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>

      <div class="navbar-header pull-left">
        <a href="<?= BASEURL ?>" class="navbar-brand">
          <small>IT Asset Management</small>
        </a>
      </div>

      <div class="navbar-buttons navbar-header pull-right" role="navigation">
        <ul class="nav ace-nav">
          <?php if ($canViewWarrantyAlerts): ?>
          <li class="<?= (int) $warrantyNotification["count"] > 0 ? "orange" : "grey" ?>">
            <a href="<?= BASEURL ?>/Asset/warrantyTimeline"
              title="<?= (int) $warrantyNotification["expired"] ?> expired and <?= (int) $warrantyNotification["dueSoon"] ?> expiring within 30 days">
              <i class="ace-icon fa fa-clock-o"></i>
              <?php if ((int) $warrantyNotification["count"] > 0): ?>
              <span class="badge badge-important"><?= (int) $warrantyNotification[
                  "count"
              ] ?></span>
              <?php endif; ?>
            </a>
          </li>
          <?php endif; ?>
          <?php if ($workflowUiEnabled): ?>
          <li class="purple">
            <a href="<?= htmlspecialchars(
                $requestNotification["url"],
                ENT_QUOTES,
                "UTF-8",
            ) ?>" title="Pending request actions">
              <i class="ace-icon fa fa-bell icon-animated-bell"></i>
              <?php if ((int) $requestNotification["count"] > 0): ?>
              <span class="badge badge-important"><?= (int) $requestNotification[
                  "count"
              ] ?></span>
              <?php endif; ?>
            </a>
          </li>
          <?php endif; ?>
          <li class="light-blue dropdown-modal">
            <a data-toggle="dropdown" href="#" class="dropdown-toggle">
              <span class="user-info">
                <small>Welcome,</small>
                <?= htmlspecialchars($_SESSION["usr"]["name"] ?? $_SESSION["usr"]["user"], ENT_QUOTES, "UTF-8") ?>
              </span>
              <i class="ace-icon fa fa-caret-down"></i>
            </a>

            <ul
              class="user-menu dropdown-menu-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">
              <li>
                <a href="<?= BASEURL ?>/User/profile">
                  <i class="ace-icon fa fa-user"></i>
                  My Profile
                </a>
              </li>
              <?php if (
                  SaasTenantContext::enabled() &&
                  SaasTenantContext::resolved() &&
                  $isSystemAdmin
              ): ?>
              <li>
                <a href="<?= BASEURL ?>/Subscription">
                  <i class="ace-icon fa fa-ticket"></i>
                  Company Subscription
                </a>
              </li>
              <?php endif; ?>
              <li class="divider"></li>
              <li>
                <a href="<?= BASEURL ?>/Home/logout">
                  <i class="ace-icon fa fa-power-off"></i>
                  Logout
                </a>
              </li>
            </ul>
          </li>
        </ul>
      </div>
    </div>
  </div>

  <!-- MAIN CONTAINER -->
  <div class="main-container ace-save-state" id="main-container">
    <script type="text/javascript">
      try {
        ace.settings.loadState('main-container')
      } catch (e) {
        // Local storage can be unavailable in private browsing; defaults are safe.
      }
    </script>

    <!-- SIDEBAR -->
    <div id="sidebar" class="sidebar responsive ace-save-state">
      <script type="text/javascript">
        try {
          ace.settings.loadState('sidebar')
        } catch (e) {
          // Keep the default sidebar state when saved preferences cannot be read.
        }
      </script>

      <ul class="nav nav-list">
        <li class="<?= empty($data["warrantyTimelinePage"]) && empty($data["assetDirectoryPage"])
            ? "active"
            : "" ?>">
          <a href="<?= BASEURL ?>">
            <i class="menu-icon fa fa-tachometer"></i>
            <span class="menu-text">Dashboard</span>
          </a>
          <b class="arrow"></b>
        </li>

        <?php if ($canViewAssetDirectory): ?>
        <li class="<?= !empty($data["assetDirectoryPage"]) ? "active" : "" ?>">
          <a href="<?= BASEURL ?>/AssetDirectory">
            <i class="menu-icon fa fa-sitemap"></i>
            <span class="menu-text">Asset Summary</span>
          </a>
          <b class="arrow"></b>
        </li>
        <?php endif; ?>

        <!-- Temporarily hidden: Automatic Agent navigation. Restore when work resumes.
        <li class="<?= !empty($data["agentPage"]) ? "open active" : "" ?>">
          <a href="#" class="dropdown-toggle"><i class="menu-icon fa fa-desktop"></i><span class="menu-text">Automatic Agent</span><b class="arrow fa fa-angle-down"></b></a>
          <ul class="submenu"><li class="<?= !empty($data["agentPage"])
              ? "active"
              : "" ?>"><a href="<?= BASEURL ?>/Agent"><i class="menu-icon fa fa-caret-right"></i> Agent Devices</a></li></ul>
        </li>
        -->

        <?php if ($canPerformAssetOperations || $canManageAccessories || $workflowUiEnabled): ?>
        <!--
          Asset Operations contains both physical asset work and the preserved
          request/approval links. Each child retains its own permission check.
        -->
        <li>
          <a href="#" class="dropdown-toggle">
            <i class="menu-icon fa fa-exchange"></i>
            <span class="menu-text">Asset Operations</span>
            <b class="arrow fa fa-angle-down"></b>
          </a>

          <ul class="submenu">
            <?php if ($canPerformAssetOperations): ?>
            <li><a href="<?= BASEURL ?>/Asset/receive"><i class="menu-icon fa fa-caret-right"></i>
                Asset Entry</a></li>
            <li><a href="<?= BASEURL ?>/Asset/assign"><i class="menu-icon fa fa-caret-right"></i>
                Asset Assign</a></li>
            <li><a href="<?= BASEURL ?>/Asset/returns"><i class="menu-icon fa fa-caret-right"></i>
                Asset Return</a></li>
            <?php endif; ?>

            <?php if ($canManageAccessories): ?>
            <li class="<?= !empty($data["accessoryPage"]) ? "active" : "" ?>">
              <a href="<?= BASEURL ?>/Accessory"><i class="menu-icon fa fa-caret-right"></i>
                Accessory Inventory</a>
            </li>
            <?php endif; ?>

            <?php if ($workflowUiEnabled): ?>
            <li class="<?= !empty($data["requestPage"]) ? "active" : "" ?>">
              <a href="<?= BASEURL ?>/Request"><i class="menu-icon fa fa-caret-right"></i>
                Equipment Requests</a>
            </li>
            <?php endif; ?>

            <?php if ($workflowUiEnabled && $canProcessReturns): ?>
            <li class="<?= !empty($data["returnRequestsPage"]) ? "active" : "" ?>">
              <a href="<?= BASEURL ?>/Asset/returnRequests"><i class="menu-icon fa fa-caret-right"></i>
                Return Requests</a>
            </li>
            <?php endif; ?>

            <?php if ($scrapApprovalEnabled && $canReviewScrap): ?>
            <li class="<?= !empty($data["scrapPage"]) ? "active" : "" ?>">
              <a href="<?= BASEURL ?>/Scrap"><i class="menu-icon fa fa-caret-right"></i>
                Scrap Approval</a>
            </li>
            <?php endif; ?>

            <?php if ($canViewScrapList): ?>
            <li class="<?= !empty($data["scrapListPage"]) ? "active" : "" ?>">
              <a href="<?= BASEURL ?>/Asset/disposed"><i class="menu-icon fa fa-caret-right"></i>
                Scrap List</a>
            </li>
            <?php endif; ?>
          </ul>
        </li>
        <?php endif; ?>

        <?php if ($canManageEmployees || $canManageMasterData): ?>
        <!-- Master records are grouped by business subject while retaining existing role checks. -->
        <li>
          <a href="#" class="dropdown-toggle">
            <i class="menu-icon fa fa-database"></i>
            <span class="menu-text">Master Data</span>
            <b class="arrow fa fa-angle-down"></b>
          </a>
          <ul class="submenu">
            <?php if ($canManageEmployees): ?>
            <li>
              <a href="#" class="dropdown-toggle">
                <i class="menu-icon fa fa-users"></i>
                Employee
                <b class="arrow fa fa-angle-down"></b>
              </a>
              <ul class="submenu">
                <li><a href="<?= BASEURL ?>/Employee"><i class="menu-icon fa fa-caret-right"></i>
                    Add Employee</a></li>
                <li><a href="<?= BASEURL ?>/Employee/list"><i
                      class="menu-icon fa fa-caret-right"></i> List Employee</a></li>
              </ul>
            </li>
            <?php endif; ?>

            <?php if ($canManageMasterData): ?>
            <li>
              <a href="#" class="dropdown-toggle">
                <i class="menu-icon fa fa-sitemap"></i>
                Department
                <b class="arrow fa fa-angle-down"></b>
              </a>
              <ul class="submenu">
                <li><a href="<?= BASEURL ?>/Department/new/department"><i
                      class="menu-icon fa fa-caret-right"></i> Add Department</a></li>
                <li><a href="<?= BASEURL ?>/Department"><i
                      class="menu-icon fa fa-caret-right"></i> List Department</a></li>
              </ul>
            </li>

            <li>
              <a href="#" class="dropdown-toggle">
                <i class="menu-icon fa fa-cubes"></i>
                Asset
                <b class="arrow fa fa-angle-down"></b>
              </a>
              <ul class="submenu">
                <li><a href="<?= BASEURL ?>/Assetkat"><i
                      class="menu-icon fa fa-caret-right"></i> Asset Categories</a></li>
                <li><a href="<?= BASEURL ?>/Location"><i
                      class="menu-icon fa fa-caret-right"></i> Asset Location</a></li>
                <li><a href="<?= BASEURL ?>/Unit"><i class="menu-icon fa fa-caret-right"></i>
                    Asset Unit</a></li>
              </ul>
            </li>

            <li>
              <a href="#" class="dropdown-toggle">
                <i class="menu-icon fa fa-truck"></i>
                Vendor
                <b class="arrow fa fa-angle-down"></b>
              </a>
              <ul class="submenu">
                <li><a href="<?= BASEURL ?>/Vendor/add"><i
                      class="menu-icon fa fa-caret-right"></i> Add Vendor</a></li>
                <li><a href="<?= BASEURL ?>/Vendor"><i
                      class="menu-icon fa fa-caret-right"></i> List Vendor</a></li>
              </ul>
            </li>
            <?php endif; ?>
          </ul>
        </li>
        <?php endif; ?>

        <?php if (
            $workflowUiEnabled ||
            $canViewAssetReports ||
            $canViewWarrantyAlerts ||
            $canViewAudit ||
            $canViewRequestHistory
        ): ?>
        <!-- Reporting links are separated from operational actions and master maintenance. -->
        <li>
          <a href="#" class="dropdown-toggle">
            <i class="menu-icon fa fa-bar-chart"></i>
            <span class="menu-text">Reports</span>
            <b class="arrow fa fa-angle-down"></b>
          </a>
          <ul class="submenu">
            <?php if ($workflowUiEnabled): ?>
            <li class="<?= !empty($data["myAssetsPage"]) ? "active" : "" ?>">
              <a href="<?= BASEURL ?>/Asset/myAssets"><i class="menu-icon fa fa-caret-right"></i>
                My Assigned Assets</a>
            </li>
            <li class="<?= !empty($data["myAssetHistoryPage"]) ? "active" : "" ?>">
              <a href="<?= BASEURL ?>/Asset/myAssetHistory"><i class="menu-icon fa fa-caret-right"></i>
                My Asset History</a>
            </li>
            <?php endif; ?>

            <?php if ($canViewAssetReports): ?>
            <li><a href="<?= BASEURL ?>/Asset/active"><i class="menu-icon fa fa-caret-right"></i>
                Asset Assigned History</a></li>
            <li><a href="<?= BASEURL ?>/Asset/returnHistory"><i
                  class="menu-icon fa fa-caret-right"></i> Asset Return History</a></li>
            <li><a href="<?= BASEURL ?>/Asset/inactive"><i
                  class="menu-icon fa fa-caret-right"></i> Asset InStock History</a></li>
            <?php endif; ?>
            <?php if ($canViewWarrantyAlerts): ?>
            <li><a href="<?= BASEURL ?>/Asset/warrantyTimeline"><i
                  class="menu-icon fa fa-caret-right"></i> Asset Expiry History</a></li>
            <?php endif; ?>
            <?php if ($canViewAudit): ?>
            <li><a href="<?= BASEURL ?>/Asset/auditReport"><i
                  class="menu-icon fa fa-caret-right"></i> Asset Audit Transaction Report</a></li>
            <?php endif; ?>
            <?php if ($canViewRequestHistory): ?>
            <li class="<?= !empty($data["requestHistoryPage"]) ? "active" : "" ?>">
              <a href="<?= BASEURL ?>/Request/history"><i
                  class="menu-icon fa fa-caret-right"></i> Request Transaction History</a>
            </li>
            <?php endif; ?>
          </ul>
        </li>
        <?php endif; ?>

        <?php if ($isSystemAdmin): ?>
        <!-- User Management -->
        <li>
          <a href="#" class="dropdown-toggle">
            <i class="menu-icon fa fa-users"></i>
            <span class="menu-text">User Management</span>
            <b class="arrow fa fa-angle-down"></b>
          </a>
          <ul class="submenu">
            <li><a href="<?= BASEURL ?>/User"><i class="menu-icon fa fa-caret-right"></i> User
                Access</a></li>
          </ul>
        </li>
        <?php endif; ?>
      </ul>

      <div class="sidebar-toggle sidebar-collapse" id="sidebar-collapse">
        <i id="sidebar-toggle-icon" class="ace-icon fa fa-angle-double-left ace-save-state"
          data-icon1="ace-icon fa fa-angle-double-left"
          data-icon2="ace-icon fa fa-angle-double-right"></i>
      </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
      <div class="main-content-inner">
        <div class="breadcrumbs ace-save-state" id="breadcrumbs">
          <ul class="breadcrumb">
            <li>
              <i class="ace-icon fa fa-home home-icon"></i>
              <a href="<?= BASEURL ?>">Dashboard</a>
            </li>
            <?php
            $breadcrumbLabel = trim((string) ($data["menu"] ?? ""));
            if (
                $breadcrumbLabel !== "" &&
                strcasecmp($breadcrumbLabel, "Dashboard") !== 0
            ): ?>
            <li class="active"><?= htmlspecialchars(
                $breadcrumbLabel,
                ENT_QUOTES,
                "UTF-8",
            ) ?></li>
            <?php endif;
            ?>
          </ul>
        </div>

        <div class="page-content">
          <div class="ace-settings-container" id="ace-settings-container">
            <!-- Settings Box -->
          </div>

          <div class="page-header">
            <h1>
              <?= $data["menu"] ?>
              <small>
                <i class="ace-icon fa fa-angle-double-right"></i>
                <?= $data["menu-dsc"] ?>
              </small>
            </h1>
          </div>

          <div class="row">
            <div class="col-xs-12">
              <!-- PAGE CONTENT START -->
