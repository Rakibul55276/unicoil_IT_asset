<?php
require dirname(__DIR__) . "/bootstrap.php";
DashboardAuth::requireLogin();
$user = DashboardAuth::user();
$search = substr(trim((string) ($_GET["q"] ?? "")), 0, 100);
$status = strtoupper(trim((string) ($_GET["status"] ?? "")));
$link = strtoupper(trim((string) ($_GET["link"] ?? "")));
if (!in_array($status, ["", "CURRENT", "STALE", "OFFLINE"], true)) {
    $status = "";
}
if (!in_array($link, ["", "LINKED", "UNLINKED"], true)) {
    $link = "";
}
try {
    $repository = new AgentDashboardRepository();
    $summary = $repository->summary();
    $devices = $repository->devices($search, $status, $link);
    $loadError = "";
} catch (Throwable $e) {
    error_log("Agent dashboard load failed: " . $e->getMessage());
    $summary = [];
    $devices = [];
    $loadError = "Dashboard data could not be loaded. Confirm database/install.sql was imported.";
}
DashboardView::header("Devices", $user);
?>
<main class="page-shell">
  <section class="page-heading">
    <div>
      <p class="eyebrow">WINDOWS INVENTORY</p>
      <h1>Agent devices</h1>
      <p class="muted">Current means synchronized within seven hours. Offline means more than 24
        hours.</p>
    </div><a class="secondary" href="health.php">API health</a>
  </section>
  <?php if ($loadError !== ""): ?><div class="alert error"><?= DashboardView::escape(
    $loadError,
) ?></div><?php endif; ?>
  <section class="summary-grid"><?php foreach (
    [
        ["All devices", $summary["total"] ?? 0, "neutral"],
        ["Current", $summary["current_count"] ?? 0, "good"],
        ["Stale", $summary["stale_count"] ?? 0, "warn"],
        ["Offline", $summary["offline_count"] ?? 0, "bad"],
        ["Unlinked", $summary["unlinked_count"] ?? 0, "info"],
    ]
    as $card
): ?><article class="summary-card <?= $card[2] ?>"><span><?= DashboardView::escape(
    $card[0],
) ?></span><strong><?= (int) $card[1] ?></strong></article><?php endforeach; ?></section>
  <section class="panel">
    <form class="filters" method="get"><label class="search-field"><span>Search</span><input
          name="q" value="<?= DashboardView::escape(
    $search,
) ?>" placeholder="Hostname, serial, asset, model…"></label><label><span>Status</span><select
          name="status">
          <option value="">All statuses</option><?php foreach (
    ["CURRENT" => "Current", "STALE" => "Stale", "OFFLINE" => "Offline"]
    as $v => $l
): ?><option value="<?= $v ?>" <?= $status === $v
    ? "selected"
    : "" ?>><?= $l ?></option><?php endforeach; ?>
        </select></label><label><span>Asset link</span><select name="link">
          <option value="">All devices</option>
          <option value="LINKED" <?= $link ===
"LINKED"
    ? "selected"
    : "" ?>>Linked</option>
          <option value="UNLINKED" <?= $link === "UNLINKED"
    ? "selected"
    : "" ?>>Unlinked</option>
        </select></label><button class="primary" type="submit">Apply filters</button><a
        class="text-link" href="dashboard.php">Reset</a></form>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Device</th>
            <th>Asset</th>
            <th>Hardware</th>
            <th>Operating system</th>
            <th>Memory</th>
            <th>Status</th>
            <th>Last sync</th>
          </tr>
        </thead>
        <tbody><?php
foreach ($devices as $device):
    $s = DashboardView::status(
        $device,
    ); ?><tr>
            <td><a class="device-link" href="device.php?id=<?= (int) $device[
    "device_id"
] ?>"><?= DashboardView::escape($device["hostname"]) ?></a><small><?= DashboardView::escape(
    $device["serial_number"] ?: "No serial",
) ?></small></td>
            <td><?= $device["assetNum"]
    ? '<span class="asset-tag">' . DashboardView::escape($device["assetNum"]) . "</span>"
    : '<span class="muted">Unlinked</span>' ?></td>
            <td><?= DashboardView::escape(
    trim(($device["manufacturer"] ?? "") . " " . ($device["model"] ?? "")) ?: "—",
) ?><small><?= DashboardView::escape(
    $device["cpu_name"] ?? "",
) ?></small></td>
            <td><?= DashboardView::escape(
    $device["os_name"] ?: "—",
) ?><small><?= DashboardView::escape(
    $device["os_version"] ?? "",
) ?></small></td>
            <td><?= DashboardView::bytes(
    $device["memory_bytes"],
) ?></td>
            <td><span class="status <?= strtolower($s) ?>"><i></i><?= DashboardView::escape(
    DashboardView::statusLabel($s),
) ?></span></td>
            <td><?= DashboardView::escape(
    $device["last_seen_at"],
) ?><small>Agent <?= DashboardView::escape($device["agent_version"]) ?></small></td>
          </tr><?php
endforeach;
if (
    !$devices
): ?><tr>
            <td colspan="7" class="empty">No agent devices match these filters.</td>
          </tr><?php endif;
?></tbody>
      </table>
    </div>
  </section>
</main>
<?php DashboardView::footer(); ?>