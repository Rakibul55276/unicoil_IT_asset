<?php
$e = function ($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
};
$bytes = function ($v) {
    if ($v === null || !is_numeric($v)) {
        return "&mdash;";
    }
    $v = (float) $v;
    return $v >= 1073741824
        ? number_format($v / 1073741824, 2) . " GB"
        : number_format($v / 1048576, 1) . " MB";
};
Flasher::Message();
?>
<div class="agent-ui">
  <?php if ($data["loadError"]): ?><div class="alert alert-danger"><?= $e(
    $data["loadError"],
) ?></div><?php endif; ?>
  <div class="agent-summary"><?php foreach (
    [
        ["All devices", $data["summary"]["total"] ?? 0, "neutral", "fa-desktop"],
        ["Current", $data["summary"]["current_count"] ?? 0, "good", "fa-check-circle"],
        ["Stale", $data["summary"]["stale_count"] ?? 0, "warn", "fa-clock-o"],
        ["Offline", $data["summary"]["offline_count"] ?? 0, "bad", "fa-times-circle"],
        ["Unlinked", $data["summary"]["unlinked_count"] ?? 0, "info", "fa-chain-broken"],
    ]
    as $c
): ?><div class="agent-summary-card <?= $c[2] ?>"><i class="fa <?= $c[3] ?>"></i>
      <div><span><?= $e(
    $c[0],
) ?></span><strong><?= (int) $c[1] ?></strong></div>
    </div><?php endforeach; ?></div>
  <div class="agent-panel">
    <form class="agent-filters" method="get"><label class="agent-search"><span>Search</span><input
          class="form-control" name="q" value="<?= $e(
    $data["search"],
) ?>" placeholder="Hostname, serial, asset, model..."></label><label><span>Status</span><select
          class="form-control" name="status">
          <option value="">All statuses</option><?php foreach (
    ["CURRENT" => "Current", "STALE" => "Stale", "OFFLINE" => "Offline"]
    as $v => $l
): ?><option value="<?= $v ?>" <?= $data["status"] === $v
    ? "selected"
    : "" ?>><?= $l ?></option><?php endforeach; ?>
        </select></label><label><span>Asset link</span><select class="form-control" name="link">
          <option value="">All devices</option>
          <option value="LINKED" <?= $data[
    "link"
] === "LINKED"
    ? "selected"
    : "" ?>>Linked</option>
          <option value="UNLINKED" <?= $data["link"] === "UNLINKED"
    ? "selected"
    : "" ?>>Unlinked</option>
        </select></label><button class="btn btn-success"><i class="fa fa-filter"></i>
        Apply</button><a class="btn btn-default" href="<?= BASEURL ?>/Agent"><i
          class="fa fa-undo"></i> Reset</a></form>
    <form id="agent-bulk-form" method="post" action="<?= BASEURL ?>/Agent/deleteSelected">
      <input type="hidden" name="csrf_token" value="<?= $e($data["csrfToken"]) ?>">
      <div class="agent-bulk-toolbar">
        <label class="agent-select-all"><input type="checkbox" id="agent-select-all"> <span>Select
            all</span></label>
        <span class="agent-selected-count" id="agent-selected-count">0 selected</span>
        <button type="submit" class="btn btn-danger" id="agent-delete-selected" disabled><i
            class="fa fa-trash"></i> Delete selected</button>
      </div>
      <div class="table-responsive">
        <table class="table agent-table">
          <thead>
            <tr>
              <th class="agent-select-column"><span class="sr-only">Select</span></th>
              <th>Device</th>
              <th>Manual asset</th>
              <th>Hardware</th>
              <th>Operating system</th>
              <th>Memory</th>
              <th>Status</th>
              <th>Last sync</th>
            </tr>
          </thead>
          <tbody><?php
foreach (
    $data["devices"]
    as $d
): ?><tr>
              <td class="agent-select-column"><input class="agent-row-select" type="checkbox"
                  name="device_ids[]" value="<?= (int) $d[
    "device_id"
] ?>" aria-label="Select <?= $e(
    $d["hostname"],
) ?>"></td>
              <td><a class="agent-device-link" href="<?= BASEURL ?>/Agent/device/<?= (int) $d[
    "device_id"
] ?>"><?= $e($d["hostname"]) ?></a><small><?= $e(
    $d["serial_number"] ?: "No serial",
) ?></small></td>
              <td><?= $d["assetNum"]
    ? '<button type="button" class="btn btn-link btn-xs" data-asset-details="' . $e($d["assetNum"]) . '"><span class="agent-tag">' . $e($d["assetNum"]) . "</span></button>"
    : '<span class="text-muted">Unlinked</span>' ?></td>
              <td><?= $e(
    trim(($d["manufacturer"] ?? "") . " " . ($d["model"] ?? "")) ?: "—",
) ?><small><?= $e($d["cpu_name"] ?? "") ?></small></td>
              <td><?= $e(
    $d["os_name"] ?: "—",
) ?><small><?= $e($d["os_version"] ?? "") ?></small></td>
              <td><?= $bytes(
    $d["memory_bytes"],
) ?></td>
              <td><span class="agent-status <?= strtolower($d["agent_status"]) ?>"><i></i><?= $e(
    ucfirst(strtolower($d["agent_status"])),
) ?></span></td>
              <td><?= $e($d["last_seen_at"]) ?><small>Agent <?= $e(
    $d["agent_version"],
) ?></small></td>
            </tr><?php endforeach;
if (
    !$data["devices"]
): ?><tr>
              <td class="agent-empty" colspan="8">No synchronized devices match these filters.</td>
            </tr><?php endif;
?></tbody>
        </table>
      </div>
    </form>
  </div>
</div>
<script>
  (function() {
    var all = document.getElementById('agent-select-all'),
      rows = Array.prototype.slice.call(document.querySelectorAll('.agent-row-select')),
      button = document.getElementById('agent-delete-selected'),
      count = document.getElementById('agent-selected-count'),
      form = document.getElementById('agent-bulk-form');

    function update() {
      var selected = rows.filter(function(row) {
        return row.checked;
      }).length;
      count.textContent = selected + ' selected';
      button.disabled = selected === 0;
      all.checked = rows.length > 0 && selected === rows.length;
      all.indeterminate = selected > 0 && selected < rows.length;
    }
    all.addEventListener('change', function() {
      rows.forEach(function(row) {
        row.checked = all.checked;
      });
      update();
    });
    rows.forEach(function(row) {
      row.addEventListener('change', update);
    });
    form.addEventListener('submit', function(event) {
      var selected = rows.filter(function(row) {
        return row.checked;
      }).length;
      if (!selected || !window.confirm('Delete ' + selected + ' selected Agent device' + (
            selected === 1 ? '' : 's') +
          '? This also removes its hardware, software, and sync history.')) event
        .preventDefault();
    });
    update();
  })();
</script>
