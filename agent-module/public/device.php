<?php
require dirname(__DIR__) . "/bootstrap.php";
DashboardAuth::requireLogin();
$user = DashboardAuth::user();
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
if (!$id) {
    http_response_code(400);
    exit("Invalid device ID.");
}
$search = substr(trim((string) ($_GET["software"] ?? "")), 0, 100);
$message = "";
$error = "";
try {
    $repository = new AgentDashboardRepository();
    if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") {
        DashboardAuth::validateCsrf($_POST["csrf"] ?? null);
        $asset =
            ($_POST["action"] ?? "") === "unlink"
                ? ""
                : strtoupper(trim((string) ($_POST["assetNum"] ?? "")));
        if ($asset !== "" && !preg_match('/^[A-Z0-9_-]{1,30}$/', $asset)) {
            throw new InvalidArgumentException("Asset number format is invalid.");
        }
        $repository->linkAsset((int) $id, $asset === "" ? null : $asset);
        $message =
            $asset === "" ? "Manual asset link removed." : "Device linked to asset " . $asset . ".";
    }
    $device = $repository->device((int) $id);
    if (!$device) {
        http_response_code(404);
        exit("Agent device was not found.");
    }
    $disks = $repository->disks((int) $id);
    $software = $repository->software((int) $id, $search);
    $history = $repository->syncHistory((int) $id);
    $assets = $repository->assets();
} catch (Throwable $e) {
    error_log("Agent device failed: " . $e->getMessage());
    if ($e instanceof DashboardCsrfException) {
        http_response_code(400);
    }
    $error =
        $e instanceof InvalidArgumentException || $e instanceof RuntimeException
            ? $e->getMessage()
            : "Device information could not be loaded.";
    try {
        $device = isset($repository) ? $repository->device((int) $id) : null;
    } catch (Throwable $ignored) {
        $device = null;
    }
    $disks = $software = $history = $assets = [];
}
$status = $device ? DashboardView::status($device) : "OFFLINE";
DashboardView::header($device ? $device["hostname"] : "Device", $user);
?>
<main class="page-shell"><a class="back-link" href="dashboard.php">← Back to devices</a><?php if (
    !$device
): ?><div class="alert error"><?= DashboardView::escape(
    $error ?: "Device not found.",
) ?></div>
</main><?php
DashboardView::footer();
exit();
endif; ?>
<section class="device-hero">
  <div>
    <p class="eyebrow">DEVICE <?= (int) $device[
    "device_id"
] ?></p>
    <h1><?= DashboardView::escape(
    $device["hostname"],
) ?></h1>
    <p class="muted"><?= DashboardView::escape(
    trim(($device["manufacturer"] ?? "") . " " . ($device["model"] ?? "")),
) ?> · <?= DashboardView::escape(
     $device["serial_number"] ?: "No serial number",
 ) ?></p>
  </div><span class="status large <?= strtolower(
    $status,
) ?>"><i></i><?= DashboardView::escape(DashboardView::statusLabel($status)) ?></span>
</section>
<?php
if ($message): ?><div class="alert success"><?= DashboardView::escape($message) ?></div><?php endif;
if ($error): ?><div class="alert error"><?= DashboardView::escape($error) ?></div><?php endif;
?>
<section class="panel section-card">
  <div class="section-title">
    <div>
      <p class="eyebrow">IDENTITY</p>
      <h2>Asset link</h2>
    </div><span class="source-tag"><?= DashboardView::escape(
    $device["asset_link_source"] ?? "AUTO",
) ?></span>
  </div>
  <form method="post" class="link-form"><input type="hidden" name="csrf" value="<?= DashboardView::escape(
    DashboardAuth::csrfToken(),
) ?>"><input name="assetNum" list="asset-options" maxlength="30" value="<?= DashboardView::escape(
    $device["assetNum"] ?? "",
) ?>" placeholder="Enter existing asset number"><datalist id="asset-options"><?php foreach (
    $assets
    as $asset
): ?><option value="<?= DashboardView::escape($asset["assetNum"]) ?>"><?= DashboardView::escape(
    $asset["Description"],
) ?></option><?php endforeach; ?></datalist><button class="primary" type="submit">Save
      link</button><?php if (
    !empty($device["assetNum"])
): ?><button class="secondary" type="submit" name="action"
      value="unlink">Unlink</button><?php endif; ?></form><?php if (
    !empty($device["linked_asset"])
): ?><p class="linked-description"><strong><?= DashboardView::escape(
    $device["assetNum"],
) ?></strong> · <?= DashboardView::escape(
    $device["linked_asset"]["Description"],
) ?> · Status <?= DashboardView::escape(
     $device["linked_asset"]["assetStat"],
 ) ?></p><?php endif; ?>
</section>
<section class="detail-grid">
  <article class="panel section-card">
    <p class="eyebrow">HARDWARE</p>
    <h2>Computer</h2>
    <dl class="spec-list"><?php foreach (
    [
        "Manufacturer" => $device["manufacturer"],
        "Model" => $device["model"],
        "CPU" => $device["cpu_name"],
        "CPU cores" => $device["cpu_cores"],
        "Memory" => DashboardView::bytes($device["memory_bytes"]),
        "BIOS serial" => $device["serial_number"],
    ]
    as $k => $v
): ?><div>
        <dt><?= DashboardView::escape($k) ?></dt>
        <dd><?= DashboardView::escape(
    $v ?: "—",
) ?></dd>
      </div><?php endforeach; ?></dl>
  </article>
  <article class="panel section-card">
    <p class="eyebrow">SYSTEM</p>
    <h2>Windows and network</h2>
    <dl class="spec-list"><?php foreach (
    [
        "Operating system" => $device["os_name"],
        "Version / build" => trim(
            ($device["os_version"] ?? "") . " / " . ($device["os_build"] ?? ""),
            " /",
        ),
        "Architecture" => $device["os_architecture"],
        "IP address" => $device["primary_ip"],
        "MAC address" => $device["mac_address"],
        "Last boot" => $device["last_boot_at"],
    ]
    as $k => $v
): ?><div>
        <dt><?= DashboardView::escape($k) ?></dt>
        <dd><?= DashboardView::escape(
    $v ?: "—",
) ?></dd>
      </div><?php endforeach; ?></dl>
  </article>
</section>
<section class="panel section-card">
  <div class="section-title">
    <div>
      <p class="eyebrow">STORAGE</p>
      <h2>Fixed disks</h2>
    </div><span class="count-tag"><?= count(
    $disks,
) ?></span>
  </div>
  <div class="disk-grid"><?php
foreach ($disks as $disk):

    $size = (float) $disk["size_bytes"];
    $used = max(0, $size - (float) $disk["free_bytes"]);
    $pct = $size > 0 ? min(100, round(($used / $size) * 100)) : 0;
    ?><article class="disk-card">
      <div><strong><?= DashboardView::escape(
    $disk["device_key"],
) ?></strong><span><?= DashboardView::escape(
    $disk["volume_label"] ?: $disk["filesystem"],
) ?></span></div>
      <p><?= DashboardView::bytes(
    $disk["free_bytes"],
) ?> free of <?= DashboardView::bytes(
     $disk["size_bytes"],
 ) ?></p>
      <div class="progress"><span style="width:<?= (int) $pct ?>%"></span></div>
    </article><?php
endforeach;
if (!$disks): ?><p class="empty">No fixed disks reported.</p><?php endif;
?></div>
</section>
<section class="panel section-card">
  <div class="section-title">
    <div>
      <p class="eyebrow">APPLICATIONS</p>
      <h2>Installed software</h2>
    </div><span class="count-tag"><?= count(
    $software,
) ?></span>
  </div>
  <form class="software-search" method="get"><input type="hidden" name="id"
      value="<?= (int) $id ?>"><input name="software" value="<?= DashboardView::escape(
    $search,
) ?>" placeholder="Search software, publisher or version"><button class="secondary"
      type="submit">Search</button><?php if (
    $search
): ?><a class="text-link" href="device.php?id=<?= (int) $id ?>">Clear</a><?php endif; ?></form>
  <div class="table-wrap compact">
    <table>
      <thead>
        <tr>
          <th>Application</th>
          <th>Version</th>
          <th>Publisher</th>
          <th>Architecture</th>
          <th>Installed</th>
        </tr>
      </thead>
      <tbody><?php
foreach ($software as $item): ?><tr>
          <td><strong><?= DashboardView::escape(
    $item["software_name"],
) ?></strong></td>
          <td><?= DashboardView::escape(
    $item["software_version"] ?: "—",
) ?></td>
          <td><?= DashboardView::escape(
    $item["publisher"] ?: "—",
) ?></td>
          <td><?= DashboardView::escape(
    $item["architecture"] ?: "—",
) ?></td>
          <td><?= DashboardView::escape($item["install_date"] ?: "—") ?></td>
        </tr><?php endforeach;
if (
    !$software
): ?><tr>
          <td colspan="5" class="empty">No software matches this search.</td>
        </tr><?php endif;
?></tbody>
    </table>
  </div>
</section>
<section class="panel section-card">
  <div class="section-title">
    <div>
      <p class="eyebrow">ACTIVITY</p>
      <h2>Recent synchronization</h2>
    </div><span class="count-tag"><?= count(
    $history,
) ?></span>
  </div>
  <div class="table-wrap compact">
    <table>
      <thead>
        <tr>
          <th>Received</th>
          <th>Status</th>
          <th>Source IP</th>
          <th>Disks</th>
          <th>Software</th>
        </tr>
      </thead>
      <tbody><?php foreach (
    $history
    as $sync
): ?><tr>
          <td><?= DashboardView::escape(
    $sync["received_at"],
) ?></td>
          <td><span class="status current"><i></i><?= DashboardView::escape(
    $sync["sync_status"],
) ?></span></td>
          <td><?= DashboardView::escape($sync["source_ip"] ?: "—") ?></td>
          <td><?= (int) $sync[
    "disk_count"
] ?></td>
          <td><?= (int) $sync[
    "software_count"
] ?></td>
        </tr><?php endforeach; ?></tbody>
    </table>
  </div>
</section>
</main>
<?php DashboardView::footer(); ?>