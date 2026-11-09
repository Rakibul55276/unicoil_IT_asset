<?php $e = function ($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
};
$bytes = function ($v) {
    if ($v === null || !is_numeric($v)) {
        return "—";
    }
    $v = (float) $v;
    return $v >= 1073741824
        ? number_format($v / 1073741824, 2) . " GB"
        : number_format($v / 1048576, 1) . " MB";
};
$status = strtolower($data["device"]["agent_status"] ?? "OFFLINE");
Flasher::Message();
?>
<div class="agent-ui">
  <div class="agent-detail-head"><a href="<?= BASEURL ?>/Agent"><i class="fa fa-arrow-left"></i>
      Back to automatic devices</a><span class="agent-status <?= $status ?>"><i></i><?= ucfirst(
    $status,
) ?></span></div>
  <div class="agent-panel agent-section">
    <div class="agent-section-title">
      <div><span>MANUAL ASSET LINK</span>
        <h3>Connect this PC to an asset record</h3>
      </div><span class="agent-tag"><?= $e(
    $data["device"]["asset_link_source"] ?? "AUTO",
) ?></span>
    </div>
    <form method="post" action="<?= BASEURL ?>/Agent/link/<?= (int) $data["device"][
    "device_id"
] ?>" class="agent-link-form"><input type="hidden" name="csrf_token" value="<?= $e(
    $data["csrfToken"],
) ?>"><input class="form-control" name="assetNum" list="agent-assets" maxlength="30" value="<?= $e(
    $data["device"]["assetNum"] ?? "",
) ?>" placeholder="Select or enter manual asset number"><datalist id="agent-assets"><?php foreach (
    $data["assets"]
    as $a
): ?><option value="<?= $e($a["assetNum"]) ?>"><?= $e(
    $a["Description"],
) ?></option><?php endforeach; ?></datalist><button class="btn btn-success"><i
          class="fa fa-link"></i> Save link</button><?php if (
    !empty($data["device"]["assetNum"])
): ?><button class="btn btn-default" name="action" value="unlink"><i class="fa fa-unlink"></i>
        Unlink</button><?php endif; ?><a class="btn btn-primary"
        href="<?= BASEURL ?>/Asset/receive"><i class="fa fa-plus"></i> Manual Asset Entry</a></form><?php if (
    !empty($data["device"]["linked_asset"])
): ?><p class="agent-linked"><button type="button" class="btn btn-link btn-xs" data-asset-details="<?= $e($data["device"]["assetNum"]) ?>"><strong><?= $e($data["device"]["assetNum"]) ?></strong></button> · <?= $e(
    $data["device"]["linked_asset"]["Description"],
) ?> · Status <?= $e($data["device"]["linked_asset"]["assetStat"]) ?></p><?php endif; ?>
  </div>
  <div class="agent-two">
    <div class="agent-panel agent-section">
      <div class="agent-section-title">
        <div><span>HARDWARE</span>
          <h3>Computer</h3>
        </div><i class="fa fa-microchip"></i>
      </div>
      <dl class="agent-specs"><?php foreach (
    [
        "Manufacturer" => $data["device"]["manufacturer"],
        "Model" => $data["device"]["model"],
        "CPU" => $data["device"]["cpu_name"],
        "CPU cores" => $data["device"]["cpu_cores"],
        "Memory" => $bytes($data["device"]["memory_bytes"]),
        "BIOS serial" => $data["device"]["serial_number"],
    ]
    as $k => $v
): ?><div>
          <dt><?= $e($k) ?></dt>
          <dd><?= $e(
    $v ?: "—",
) ?></dd>
        </div><?php endforeach; ?></dl>
    </div>
    <div class="agent-panel agent-section">
      <div class="agent-section-title">
        <div><span>SYSTEM</span>
          <h3>Windows and network</h3>
        </div><i class="fa fa-windows"></i>
      </div>
      <dl class="agent-specs"><?php foreach (
    [
        "Operating system" => $data["device"]["os_name"],
        "Version / build" => trim(
            ($data["device"]["os_version"] ?? "") . " / " . ($data["device"]["os_build"] ?? ""),
            " /",
        ),
        "Architecture" => $data["device"]["os_architecture"],
        "IP address" => $data["device"]["primary_ip"],
        "MAC address" => $data["device"]["mac_address"],
        "Last boot" => $data["device"]["last_boot_at"],
    ]
    as $k => $v
): ?><div>
          <dt><?= $e($k) ?></dt>
          <dd><?= $e(
    $v ?: "—",
) ?></dd>
        </div><?php endforeach; ?></dl>
    </div>
  </div>
  <div class="agent-panel agent-section">
    <div class="agent-section-title">
      <div><span>STORAGE</span>
        <h3>Fixed disks</h3>
      </div><span class="agent-count"><?= count(
    $data["disks"],
) ?></span>
    </div>
    <div class="agent-disks"><?php foreach ($data["disks"] as $d):

    $size = (float) $d["size_bytes"];
    $used = max(0, $size - (float) $d["free_bytes"]);
    $pct = $size > 0 ? min(100, round(($used / $size) * 100)) : 0;
    ?><div class="agent-disk"><strong><?= $e($d["device_key"]) ?></strong><span><?= $e(
    $d["volume_label"] ?: $d["filesystem"],
) ?></span>
        <p><?= $bytes($d["free_bytes"]) ?> free of <?= $bytes(
     $d["size_bytes"],
 ) ?></p>
        <div class="agent-progress"><i style="width:<?= (int) $pct ?>%"></i></div>
      </div><?php
endforeach; ?></div>
  </div>
  <div class="agent-panel agent-section">
    <div class="agent-section-title">
      <div><span>APPLICATIONS</span>
        <h3>Installed software</h3>
      </div><span class="agent-count"><?= count(
    $data["software"],
) ?></span>
    </div>
    <form method="get" class="agent-software-search"><input class="form-control" name="software"
        value="<?= $e(
    $data["softwareSearch"],
) ?>" placeholder="Search software, publisher or version"><button class="btn btn-default"><i
          class="fa fa-search"></i> Search</button><?php if (
    $data["softwareSearch"]
): ?><a class="btn btn-link" href="<?= BASEURL ?>/Agent/device/<?= (int) $data["device"][
    "device_id"
] ?>">Clear</a><?php endif; ?></form>
    <div class="table-responsive">
      <table class="table agent-table">
        <thead>
          <tr>
            <th>Application</th>
            <th>Version</th>
            <th>Publisher</th>
            <th>Architecture</th>
            <th>Installed</th>
          </tr>
        </thead>
        <tbody><?php foreach (
    $data["software"]
    as $s
): ?><tr>
            <td><strong><?= $e($s["software_name"]) ?></strong></td>
            <td><?= $e(
    $s["software_version"] ?: "—",
) ?></td>
            <td><?= $e($s["publisher"] ?: "—") ?></td>
            <td><?= $e(
    $s["architecture"] ?: "—",
) ?></td>
            <td><?= $e(
    $s["install_date"] ?: "—",
) ?></td>
          </tr><?php endforeach; ?></tbody>
      </table>
    </div>
  </div>
  <div class="agent-panel agent-section">
    <div class="agent-section-title">
      <div><span>ACTIVITY</span>
        <h3>Recent synchronization</h3>
      </div><span class="agent-count"><?= count(
    $data["syncHistory"],
) ?></span>
    </div>
    <div class="table-responsive">
      <table class="table agent-table">
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
    $data["syncHistory"]
    as $s
): ?><tr>
            <td><?= $e($s["received_at"]) ?></td>
            <td><span class="agent-status current"><i></i><?= $e(
    $s["sync_status"],
) ?></span></td>
            <td><?= $e($s["source_ip"] ?: "—") ?></td>
            <td><?= (int) $s[
    "disk_count"
] ?></td>
            <td><?= (int) $s[
    "software_count"
] ?></td>
          </tr><?php endforeach; ?></tbody>
      </table>
    </div>
  </div>
</div>
