<div class="row">
  <style>
    .my-asset-grouped-table {
      font-size: 14px;
    }

    .my-asset-grouped-table > thead > tr > th {
      font-size: 13px;
      line-height: 1.35;
      padding: 10px 9px;
    }

    .my-asset-grouped-table > tbody > tr > td {
      font-size: 14px;
      line-height: 1.45;
      padding: 11px 9px;
      vertical-align: middle;
    }

    .my-asset-grouped-table .issue-summary-row > td {
      background: #eef6fb !important;
      border-top: 3px solid #4a89c7;
      padding: 13px 12px;
    }

    .issue-summary {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 14px 22px;
    }

    .issue-summary > div {
      min-width: 130px;
    }

    .issue-summary small {
      display: block;
      margin-bottom: 2px;
      color: #63758a;
      font-size: 12px;
      font-weight: 600;
      line-height: 1.25;
      text-transform: uppercase;
    }

    .issue-summary strong {
      display: block;
      color: #1d3557;
      font-size: 15px;
      line-height: 1.4;
      overflow-wrap: anywhere;
    }

    .issue-summary span {
      font-size: 13px;
      line-height: 1.35;
    }

    .issue-summary-number {
      min-width: auto !important;
      color: #2f6fa7;
      font-size: 20px;
      font-weight: 700;
    }

    .issue-summary-purpose {
      flex: 1 1 180px;
    }

    .issue-summary-action {
      min-width: auto !important;
      margin-left: auto;
    }

    .issue-summary-action .btn {
      font-size: 13px;
      padding: 5px 9px;
    }

    .issue-summary-action .btn + .btn {
      margin-left: 5px;
    }

    .bulk-return-select {
      width: 64px;
      text-align: center;
    }

    .bulk-return-checkbox {
      width: 17px;
      height: 17px;
      margin: 0;
      vertical-align: middle;
    }

    @media (max-width: 767px) {
      .my-asset-grouped-table {
        font-size: 13px;
      }

      .issue-summary > div {
        min-width: 44%;
      }

      .issue-summary-action {
        width: 100%;
        margin-left: 0;
      }
    }
  </style>
  <div class="col-xs-12">
    <?php if (empty($data["employeeId"])): ?>
    <div class="alert alert-warning">
      Your user account is not linked to an employee. Ask an Administrator to link it before using this page.
    </div>
    <?php else: ?>
<?php
$returnRequestReference = static function (string $requestNumber): string {
    if (preg_match('/^(RRQ-\d{8}-\d{6}-\d{3})-\d{2}$/', $requestNumber, $match)) {
        return $match[1];
    }
    return $requestNumber;
};
    $historyOnly = !empty($data["historyOnly"]);
    $assetScope = (string) ($data["assetScope"] ?? "mine");
    $scopeRoute = $historyOnly ? "myAssetHistory" : "myAssets";
    $groupIssueRows = static function (array $rows): array {
        $groups = [];
        foreach ($rows as $row) {
            $groupKey = trim((string) ($row["batch_no"] ?? ""));
            if ($groupKey === "") {
                $groupKey = trim((string) ($row["issue_no"] ?? ""));
            }
            if ($groupKey === "") {
                $groupKey = "issue-" . (int) ($row["issue_id"] ?? 0);
            }
            $groups[$groupKey][] = $row;
        }

        $groupedRows = [];
        $groupNumber = 0;
        foreach ($groups as $groupKey => $records) {
            $groupNumber++;
            $groupSize = count($records);
            foreach ($records as $recordIndex => $record) {
                $record["_group_first"] = $recordIndex === 0;
                $record["_group_size"] = $groupSize;
                $record["_group_number"] = $groupNumber;
                $record["_group_issue_no"] = $groupKey;
                $groupedRows[] = $record;
            }
        }
        return $groupedRows;
    };
    $assignedAssetRows = $groupIssueRows($data["assets"] ?? []);
    $historyRows = $groupIssueRows($data["history"] ?? []);
    ?>
    <ul class="nav nav-tabs" style="margin-bottom:15px">
      <li class="<?= $assetScope === "mine" ? "active" : "" ?>">
        <a href="<?= BASEURL ?>/Asset/<?= $scopeRoute ?>?scope=mine">
          <i class="fa fa-user"></i>
          <?= $historyOnly ? "My Asset History" : "My Assets" ?>
        </a>
      </li>
      <?php if (!empty($data["canViewDepartmentAssets"])): ?>
      <li class="<?= $assetScope === "department" ? "active" : "" ?>">
        <a href="<?= BASEURL ?>/Asset/<?= $scopeRoute ?>?scope=department">
          <i class="fa fa-building"></i>
          <?= $historyOnly
              ? "Department Asset History"
              : "Department Assets" ?>
        </a>
      </li>
      <?php endif; ?>
    </ul>
    <?php if ($historyOnly): ?>
    <div class="alert alert-info">
      <?= $assetScope === "department"
          ? "Complete assignment and return history for assets assigned to your department."
          : "Complete personal assignment, acceptance, return-request, and completed-return history." ?>
    </div>
    <?php else: ?>
    <div class="alert alert-info">
      <?= $assetScope === "department"
          ? "Assets currently assigned to your department."
          : "Assets currently assigned directly to you." ?>
    </div>
    <?php endif; ?>
    <?php if (!$historyOnly): ?>
    <div class="widget-box">
      <div class="widget-header">
        <h4 class="widget-title"><i class="fa fa-laptop"></i>
          <?= $assetScope === "department"
              ? "Department Assigned Assets"
              : "My Assigned Assets" ?>
        </h4>
      </div>
      <div class="widget-body"><div class="widget-main no-padding">
        <div class="clearfix" style="padding:10px 12px;border-bottom:1px solid #e5eaf0">
          <button type="button" class="btn btn-sm btn-warning pull-right" id="bulk-return-request-btn" disabled>
            <i class="fa fa-reply-all"></i> Request Return (<span id="bulk-return-count">0</span>)
          </button>
          <span class="text-muted"><i class="fa fa-info-circle"></i> Select 1 to 8 assets from the same ISS batch.</span>
        </div>
        <div class="table-responsive">
        <table class="table table-striped table-bordered table-hover my-asset-grouped-table" data-no-pdf="1">
          <thead><tr>
            <th class="bulk-return-select">Select</th><th>Asset</th><th>Serial Number</th><th>Description</th><th>Category</th><th>Condition</th>
            <th>Return Process</th><th>Return Document</th>
          </tr></thead>
          <tbody>
            <?php foreach ($assignedAssetRows as $asset): ?>
              <?php if (!empty($asset["_group_first"])): ?>
              <tr class="issue-summary-row"><td colspan="8">
                <div class="issue-summary">
                  <div class="issue-summary-number">#<?= (int) $asset["_group_number"] ?></div>
                  <div><small>Issue Number</small><strong><?= htmlspecialchars((string) $asset["_group_issue_no"]) ?></strong><span><?= htmlspecialchars((string) $asset["issue_date"]) ?></span></div>
                  <div><small>Assigned To</small><strong><?= htmlspecialchars((string) ($asset["assignee_type"] === "DEPARTMENT" ? $asset["deptName"] : $asset["employeeName"])) ?></strong><span><?= (int) $asset["_group_size"] ?> asset(s)</span></div>
                  <div class="issue-summary-purpose"><small>Purpose</small><strong><?= htmlspecialchars((string) ($asset["purpose"] ?: "-")) ?></strong></div>
                  <div><small>Acceptance</small>
                    <?php if (!empty($asset["request_no"])): ?>
                    <strong><?= htmlspecialchars((string) $asset["request_no"]) ?></strong>
                    <span class="label label-<?= !empty($asset["receiver_accepted_at"]) ? "success" : "warning" ?>"><?= !empty($asset["receiver_accepted_at"]) ? "Accepted" : "Pending" ?></span>
                    <?php else: ?><strong>Manual assignment</strong><?php endif; ?>
                  </div>
                  <div class="issue-summary-action">
                    <a class="btn btn-xs btn-danger" href="<?= BASEURL ?>/Asset/downloadAssignmentPdf/<?= (int) $asset["issue_id"] ?>"><i class="fa fa-file-pdf-o"></i> Assignment PDF</a>
                  </div>
                </div>
              </td></tr>
              <?php endif; ?>
              <tr>
                <td class="bulk-return-select">
                  <?php if (($asset["returnRequestStatus"] ?? "") !== "PENDING"): ?>
                  <input type="checkbox" class="bulk-return-checkbox" data-group="<?= htmlspecialchars((string) $asset["_group_issue_no"], ENT_QUOTES, "UTF-8") ?>" data-issue="<?= (int) $asset["issue_id"] ?>" data-asset="<?= htmlspecialchars((string) $asset["assetNum"], ENT_QUOTES, "UTF-8") ?>" aria-label="Select <?= htmlspecialchars((string) $asset["assetNum"], ENT_QUOTES, "UTF-8") ?> for return">
                  <?php else: ?><span class="text-muted">-</span><?php endif; ?>
                </td>
                <td><button type="button" class="btn btn-link btn-xs" data-asset-details="<?= htmlspecialchars((string) $asset["assetNum"], ENT_QUOTES, "UTF-8") ?>"><strong><?= htmlspecialchars((string) $asset["assetNum"]) ?></strong></button></td>
                <td><?= htmlspecialchars((string) ($asset["serial_number"] ?: "-")) ?></td>
                <td><?= htmlspecialchars((string) $asset["assetDescription"]) ?><?php if ($asset["manufacturer"] || $asset["model"]): ?><br><small class="text-muted"><?= htmlspecialchars(trim((string) ($asset["manufacturer"] ?? "") . " " . (string) ($asset["model"] ?? ""))) ?></small><?php endif; ?></td>
                <td><?= htmlspecialchars((string) ($asset["categoryName"] ?: "-")) ?></td>
                <td><span class="label label-info"><?= htmlspecialchars(ucwords(strtolower(str_replace("_", " ", (string) ($asset["condition_out"] ?: "-"))))) ?></span></td>
                <td>
                  <?php if (($asset["returnRequestStatus"] ?? "") === "PENDING"): ?>
                  <span class="label label-warning">Awaiting Issuer</span><br><small><?= htmlspecialchars($returnRequestReference((string) $asset["return_request_no"])) ?></small>
                  <?php else: ?>
                  <span class="label label-info">Active Custody</span><br>
                  <button type="button" class="btn btn-xs btn-warning return-request-btn" style="margin-top:4px" data-issue="<?= (int) $asset["issue_id"] ?>" data-asset="<?= htmlspecialchars((string) $asset["assetNum"], ENT_QUOTES, "UTF-8") ?>"><i class="fa fa-reply"></i> Request Return</button>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if (($asset["returnRequestStatus"] ?? "") === "PENDING"): ?>
                  <a class="btn btn-xs btn-warning" href="<?= BASEURL ?>/Asset/downloadReturnRequestPdf/<?= (int) $asset["return_request_id"] ?>"><i class="fa fa-file-pdf-o"></i> Return Form</a>
                  <?php else: ?><span class="text-muted">Not requested</span><?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($assignedAssetRows)): ?><tr><td colspan="8" class="text-center text-muted"><?= $assetScope === "department" ? "No assets are currently assigned to your department." : "No assets are currently assigned directly to you." ?></td></tr><?php endif; ?>
          </tbody>
        </table>
      </div></div></div>
    </div>
    <?php endif; ?>

    <?php if ($historyOnly): ?>
    <div class="widget-box">
      <div class="widget-header"><h4 class="widget-title"><i class="fa fa-history"></i> <?= $assetScope === "department" ? "Department Assignment &amp; Return History" : "My Assignment &amp; Return History" ?></h4></div>
      <div class="widget-body"><div class="widget-main no-padding"><div class="table-responsive">
        <table class="table table-striped table-bordered table-hover my-asset-grouped-table" data-no-pdf="1">
          <thead><tr>
            <th>Asset</th><th>Serial Number</th><th>Description</th><th>Category</th><th>Condition</th>
            <th>Return Process</th><th>Return Document</th>
          </tr></thead>
          <tbody>
            <?php foreach ($historyRows as $record): ?>
              <?php
              $issueStatus = strtoupper((string) ($record["issueStatus"] ?? ""));
              $returnRequestStatus = strtoupper((string) ($record["returnRequestStatus"] ?? ""));
              $hasReturnRequest = !empty($record["return_request_id"]);
              $hasCompletedReturn = !empty($record["return_id"]);
              ?>
              <?php if (!empty($record["_group_first"])): ?>
              <tr class="issue-summary-row"><td colspan="7">
                <div class="issue-summary">
                  <div class="issue-summary-number">#<?= (int) $record["_group_number"] ?></div>
                  <div><small>Issue Number</small><strong><?= htmlspecialchars((string) $record["_group_issue_no"]) ?></strong><span><?= htmlspecialchars((string) $record["issue_date"]) ?></span></div>
                  <div><small>Assigned To</small><strong><?= htmlspecialchars((string) ($record["assignee_type"] === "DEPARTMENT" ? $record["deptName"] : $record["employeeName"])) ?></strong><span><?= (int) $record["_group_size"] ?> asset(s)</span></div>
                  <div class="issue-summary-purpose"><small>Purpose</small><strong><?= htmlspecialchars((string) ($record["purpose"] ?: "-")) ?></strong></div>
                  <div><small>Acceptance</small>
                    <?php if (!empty($record["request_no"])): ?>
                    <strong><?= htmlspecialchars((string) $record["request_no"]) ?></strong>
                    <span class="label label-<?= !empty($record["receiver_accepted_at"]) ? "success" : "warning" ?>"><?= !empty($record["receiver_accepted_at"]) ? "Accepted" : "Pending" ?></span>
                    <?php else: ?><strong>Manual assignment</strong><?php endif; ?>
                  </div>
                  <div class="issue-summary-action"><a class="btn btn-xs btn-danger" href="<?= BASEURL ?>/Asset/downloadAssignmentPdf/<?= (int) $record["issue_id"] ?>"><i class="fa fa-file-pdf-o"></i> Assignment PDF</a></div>
                </div>
              </td></tr>
              <?php endif; ?>
              <tr>
                <td><strong><?= htmlspecialchars((string) $record["assetNum"]) ?></strong></td>
                <td><?= htmlspecialchars((string) ($record["serial_number"] ?: "-")) ?></td>
                <td><?= htmlspecialchars((string) $record["assetDescription"]) ?><?php if ($record["manufacturer"] || $record["model"]): ?><br><small class="text-muted"><?= htmlspecialchars(trim((string) ($record["manufacturer"] ?? "") . " " . (string) ($record["model"] ?? ""))) ?></small><?php endif; ?></td>
                <td><?= htmlspecialchars((string) ($record["categoryName"] ?: "-")) ?></td>
                <td><span class="label label-info"><?= htmlspecialchars(ucwords(strtolower(str_replace("_", " ", (string) ($record["condition_out"] ?: "-"))))) ?></span></td>
                <td>
                  <?php if ($issueStatus === "RETURNED" && $hasCompletedReturn): ?>
                  <span class="label label-success">Returned</span><br><strong><?= htmlspecialchars((string) (($record["returnBatchNo"] ?? "") ?: $record["return_no"])) ?></strong><br><small><?= htmlspecialchars((string) $record["return_date"]) ?></small>
                  <?php if (!empty($record["condition_in"])): ?><br><span class="label label-info"><?= htmlspecialchars(ucwords(strtolower(str_replace("_", " ", (string) $record["condition_in"])))) ?></span><?php endif; ?>
                  <?php if (!empty($record["returnReason"])): ?><br><small><?= htmlspecialchars((string) $record["returnReason"]) ?></small><?php endif; ?>
                  <?php elseif ($returnRequestStatus === "PENDING"): ?>
                  <span class="label label-warning">Awaiting Issuer</span><br><strong><?= htmlspecialchars($returnRequestReference((string) $record["return_request_no"])) ?></strong><br><small><?= htmlspecialchars((string) $record["returnRequestedAt"]) ?></small>
                  <?php elseif ($issueStatus === "ISSUED"): ?><span class="label label-info">Active Custody</span>
                  <?php else: ?><span class="label label-default"><?= htmlspecialchars(ucwords(strtolower(str_replace("_", " ", $issueStatus ?: "Unknown")))) ?></span><?php endif; ?>
                </td>
                <td>
                  <?php if ($hasCompletedReturn): ?><a class="btn btn-xs btn-success" href="<?= BASEURL ?>/Asset/downloadReturnPdf/<?= (int) $record["return_id"] ?>"><i class="fa fa-file-pdf-o"></i> Return Form</a>
                  <?php elseif ($hasReturnRequest): ?><a class="btn btn-xs btn-warning" href="<?= BASEURL ?>/Asset/downloadReturnRequestPdf/<?= (int) $record["return_request_id"] ?>"><i class="fa fa-file-pdf-o"></i> Return Form</a>
                  <?php else: ?><span class="text-muted">No return document</span><?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($historyRows)): ?><tr><td colspan="7" class="text-center text-muted"><?= $assetScope === "department" ? "No assignment or return history is available for your department." : "No personal assignment or return history is available." ?></td></tr><?php endif; ?>
          </tbody>
        </table>
      </div></div></div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
<?php if (empty($data["historyOnly"])): ?>
<div class="modal fade" id="returnRequestModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form method="post" action="<?= BASEURL ?>/Asset/requestReturn">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">Request Asset Return</h4>
        </div>
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($data["returnRequestCsrf"], ENT_QUOTES, "UTF-8") ?>">
          <div id="returnRequestIssueInputs"></div>
          <input type="hidden" name="return_scope" value="<?= htmlspecialchars(
              (string) ($data["assetScope"] ?? "mine"),
              ENT_QUOTES,
              "UTF-8",
          ) ?>">
          <div class="form-group"><label>Selected Asset(s)</label><input id="returnRequestAsset" class="form-control" readonly></div>
          <div class="form-group"><label>Reason <span class="red">*</span></label>
            <textarea name="reason" class="form-control" rows="3" maxlength="500" required placeholder="Why are you returning the selected asset(s)?"></textarea>
          </div>
          <div class="alert alert-info">The asset remains assigned until the Issuer processes the physical return.</div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning"><i class="fa fa-paper-plane"></i> Send to Issuer</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
function openReturnRequestModal(selectedAssets) {
  if (!selectedAssets.length || selectedAssets.length > 8) {
    window.alert('Select between 1 and 8 assigned assets.');
    return;
  }
  var batch = selectedAssets[0].group;
  if (selectedAssets.some(function (asset) { return asset.group !== batch; })) {
    window.alert('Select assets from one ISS batch only.');
    return;
  }

  var inputContainer = document.getElementById('returnRequestIssueInputs');
  inputContainer.innerHTML = '';
  selectedAssets.forEach(function (asset) {
    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'issue_ids[]';
    input.value = asset.issue;
    inputContainer.appendChild(input);
  });

  document.getElementById('returnRequestAsset').value = selectedAssets
    .map(function (asset) { return asset.asset; })
    .join(', ');
  document.querySelector('#returnRequestModal .modal-title').textContent =
    selectedAssets.length > 1
      ? 'Request Return for ' + selectedAssets.length + ' Assets'
      : 'Request Asset Return';
  jQuery('#returnRequestModal').modal('show');
}

function selectedAssetsForReturn() {
  return Array.prototype.slice.call(
    document.querySelectorAll('.bulk-return-checkbox:checked')
  }).map(function (checkbox) {
    return {
      issue: checkbox.getAttribute('data-issue'),
      asset: checkbox.getAttribute('data-asset'),
      group: checkbox.getAttribute('data-group')
    };
  });
}

function updateBulkReturnButton() {
  var button = document.getElementById('bulk-return-request-btn');
  if (!button) return;

  var count = selectedAssetsForReturn().length;
  button.disabled = count === 0 || count > 8;
  document.getElementById('bulk-return-count').textContent = String(count);
}

document.addEventListener('click', function (event) {
  var button = event.target.closest('.return-request-btn');
  if (button) {
    openReturnRequestModal([{
      issue: button.getAttribute('data-issue'),
      asset: button.getAttribute('data-asset'),
      group: button.closest('tr').querySelector('.bulk-return-checkbox').getAttribute('data-group')
    }]);
    return;
  }

  var bulkButton = event.target.closest('#bulk-return-request-btn');
  if (bulkButton) {
    openReturnRequestModal(selectedAssetsForReturn());
  }
});

document.addEventListener('change', function (event) {
  if (!event.target.classList.contains('bulk-return-checkbox')) return;
  if (event.target.checked) {
    var selected = selectedAssetsForReturn();
    var firstGroup = selected.length ? selected[0].group : '';
    if (selected.some(function (asset) { return asset.group !== firstGroup; })) {
      event.target.checked = false;
      window.alert('Select assets from one ISS batch only.');
    }
  }
  updateBulkReturnButton();
});
</script>
<?php endif; ?>
<?php require __DIR__ . "/../templates/asset_details_modal.php"; ?>
