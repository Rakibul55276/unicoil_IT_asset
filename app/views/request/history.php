<?php
$filters = $data["filters"] ?? [];
$eventTypes = $data["eventTypes"] ?? [];
$statuses = $data["statuses"] ?? [];
$exportQuery = http_build_query($filters);
$requestTypeLabels = [
    "EQUIPMENT" => "Equipment Request",
    "RETURN" => "Return Request",
];
$statusLabel = static fn($status): string => $status === null || $status === ""
    ? "-"
    : ($statuses[$status] ?? ucwords(strtolower(str_replace("_", " ", $status))));
?>

<div class="row"><div class="col-xs-12">
  <?php Flasher::msgInfo(); ?>

  <div class="widget-box">
    <div class="widget-header">
      <h4 class="widget-title"><i class="fa fa-filter"></i> Request History Filters</h4>
    </div>
    <div class="widget-body"><div class="widget-main">
      <form method="get" action="<?= BASEURL ?>/Request/history">
        <div class="row">
          <div class="col-md-2 form-group">
            <label for="history-from">From</label>
            <input type="date" class="form-control" id="history-from" name="from"
              value="<?= htmlspecialchars((string) ($filters["from"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
          </div>
          <div class="col-md-2 form-group">
            <label for="history-to">To</label>
            <input type="date" class="form-control" id="history-to" name="to"
              value="<?= htmlspecialchars((string) ($filters["to"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
          </div>
          <div class="col-md-2 form-group">
            <label for="history-request-type">Request Type</label>
            <select class="form-control" id="history-request-type" name="requestType">
              <option value="">All Request Types</option>
              <?php foreach ($requestTypeLabels as $value => $label): ?>
              <option value="<?= $value ?>" <?= ($filters["requestType"] ?? "") === $value ? "selected" : "" ?>><?= htmlspecialchars($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3 form-group">
            <label for="history-event">Event</label>
            <select class="form-control" id="history-event" name="eventType">
              <option value="">All Events</option>
              <?php foreach ($eventTypes as $value => $label): ?>
              <option value="<?= htmlspecialchars($value) ?>" <?= ($filters["eventType"] ?? "") === $value ? "selected" : "" ?>><?= htmlspecialchars($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3 form-group">
            <label for="history-status">Resulting Status</label>
            <select class="form-control" id="history-status" name="status">
              <option value="">All Statuses</option>
              <?php foreach ($statuses as $value => $label): ?>
              <option value="<?= htmlspecialchars($value) ?>" <?= ($filters["status"] ?? "") === $value ? "selected" : "" ?>><?= htmlspecialchars($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="row">
          <div class="col-md-3 form-group">
            <label for="history-department">Department</label>
            <select class="form-control" id="history-department" name="department">
              <option value="">All Departments</option>
              <?php foreach ($data["departments"] ?? [] as $department): ?>
              <option value="<?= htmlspecialchars((string) $department["deptId"], ENT_QUOTES, "UTF-8") ?>"
                <?= ($filters["department"] ?? "") === (string) $department["deptId"] ? "selected" : "" ?>>
                <?= htmlspecialchars((string) $department["deptName"]) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3 form-group">
            <label for="history-actor">Actor</label>
            <input type="text" class="form-control" id="history-actor" name="actor"
              maxlength="100" placeholder="Username or name"
              value="<?= htmlspecialchars((string) ($filters["actor"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
          </div>
          <div class="col-md-6 form-group">
            <label for="history-search">Search</label>
            <input type="text" class="form-control" id="history-search" name="search"
              maxlength="150" placeholder="Request number, asset, requester, employee or remarks"
              value="<?= htmlspecialchars((string) ($filters["search"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
          </div>
        </div>
        <div class="clearfix">
          <button type="submit" class="btn btn-success"><i class="fa fa-filter"></i> Apply Filters</button>
          <a href="<?= BASEURL ?>/Request/history" class="btn btn-warning"><i class="fa fa-undo"></i> Reset Filters</a>
          <div class="pull-right">
            <a href="<?= BASEURL ?>/Request/exportHistoryExcel?<?= htmlspecialchars($exportQuery, ENT_QUOTES, "UTF-8") ?>"
              class="btn btn-success"><i class="fa fa-file-excel-o"></i> Export Excel</a>
            <a href="<?= BASEURL ?>/Request/exportHistoryPdf?<?= htmlspecialchars($exportQuery, ENT_QUOTES, "UTF-8") ?>"
              class="btn btn-danger"><i class="fa fa-file-pdf-o"></i> Export PDF</a>
          </div>
        </div>
      </form>
    </div></div>
  </div>

  <div class="alert alert-info" style="margin-top:15px">
    This report contains the complete workflow trail of finalized requests only.
    Pending requests remain on their operational pages. Physical asset receipt,
    assignment and return transactions remain in Asset Audit Transaction Report.
  </div>

  <div class="widget-box">
    <div class="widget-header">
      <h4 class="widget-title"><i class="fa fa-history"></i> Request Transactions</h4>
      <span class="widget-toolbar text-muted"><?= number_format(count($data["rows"] ?? [])) ?> event(s)</span>
    </div>
    <div class="widget-body"><div class="widget-main no-padding"><div class="table-responsive">
      <table class="table table-striped table-bordered table-hover application-sortable-table">
        <thead><tr>
          <th>Date</th><th>Type</th><th>Reference</th><th>Asset</th><th>Event</th>
          <th>From</th><th>To</th><th>Requested For</th><th>Requester</th>
          <th>Department</th><th>Actor</th><th>Remarks</th>
        </tr></thead>
        <tbody>
          <?php foreach ($data["rows"] ?? [] as $row): ?>
          <tr>
            <td><?= htmlspecialchars((string) ($row["eventDate"] ?? "-")) ?></td>
            <td><span class="label <?= ($row["requestType"] ?? "") === "RETURN" ? "label-warning" : "label-info" ?>"><?= htmlspecialchars($requestTypeLabels[$row["requestType"]] ?? (string) $row["requestType"]) ?></span></td>
            <td><strong><?= htmlspecialchars((string) ($row["referenceNo"] ?? "-")) ?></strong></td>
            <td><?= htmlspecialchars((string) ($row["assetNum"] ?? "-")) ?></td>
            <td><?= htmlspecialchars($eventTypes[$row["eventType"]] ?? ucwords(strtolower(str_replace("_", " ", (string) $row["eventType"])))) ?></td>
            <td><?= htmlspecialchars($statusLabel($row["fromStatus"] ?? "")) ?></td>
            <td><?= htmlspecialchars($statusLabel($row["toStatus"] ?? "")) ?></td>
            <td><?= htmlspecialchars((string) ($row["requestedFor"] ?? "-")) ?></td>
            <td><?= htmlspecialchars((string) ($row["requester"] ?? "-")) ?></td>
            <td><?= htmlspecialchars((string) ($row["department"] ?? "-")) ?></td>
            <td><?= htmlspecialchars((string) ($row["actor"] ?? "-")) ?><br><small class="text-muted"><?= htmlspecialchars((string) ($row["actorUsername"] ?? "")) ?></small></td>
            <td><?= nl2br(htmlspecialchars((string) ($row["details"] ?? "-"))) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($data["rows"])): ?>
          <tr><td colspan="12" class="text-center text-muted">No request transactions match the selected filters.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div></div></div>
  </div>
  <p class="help-block">Display is limited to 1,000 matching events for hosting safety. Narrow the filters for a focused review.</p>
</div></div>
