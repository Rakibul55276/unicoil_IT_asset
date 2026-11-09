<?php
$returnRequestGroupKey = static function (string $requestNumber): string {
    if (preg_match('/^(RRQ-\d{8}-\d{6}-\d{3})-\d{2}$/', $requestNumber, $match)) {
        return $match[1];
    }
    return $requestNumber;
};
$returnDocumentGroupKey = static function (string $documentNumber): string {
    if (preg_match('/^(RET-\d{8}-\d{6}-\d{3})-\d{2}$/', $documentNumber, $match)) {
        return $match[1];
    }
    return $documentNumber;
};
$processReturnGroups = [];
$returnRequestGroups = [];
foreach ($data["requests"] as $returnRequest) {
    $groupKey = $returnRequestGroupKey((string) $returnRequest["return_request_no"]);
    $assetLine = [
        "issue" => (int) $returnRequest["issue_id"],
        "request" => (string) $returnRequest["return_request_no"],
        "asset" => (string) $returnRequest["assetNum"],
        "description" => (string) $returnRequest["assetDescription"],
        "serial" => (string) ($returnRequest["serial_number"] ?? ""),
        "reason" => (string) ($returnRequest["reason"] ?? ""),
    ];
    if (($returnRequest["status"] ?? "") === "PENDING") {
        $processReturnGroups[$groupKey][] = $assetLine;
    }

    if (!isset($returnRequestGroups[$groupKey])) {
        $returnRequestGroups[$groupKey] = [
            "reference" => $groupKey,
            "first" => $returnRequest,
            "assets" => [],
            "statuses" => [],
            "returnNumbers" => [],
        ];
    }
    $returnRequestGroups[$groupKey]["assets"][] = $assetLine;
    $returnRequestGroups[$groupKey]["statuses"][] = strtoupper(
        (string) ($returnRequest["status"] ?? "PENDING"),
    );
    if (!empty($returnRequest["return_no"])) {
        $returnRequestGroups[$groupKey]["returnNumbers"][] = $returnDocumentGroupKey(
            (string) $returnRequest["return_no"],
        );
    }
}
?>
<div class="row"><div class="col-xs-12">
  <?php Flasher::msgInfo(); ?>
  <div class="alert alert-info">Return requests do not change stock automatically. The Issuer must receive the asset and complete the normal return form.</div>
  <div class="widget-box">
    <div class="widget-header"><h4 class="widget-title"><i class="fa fa-inbox"></i> Employee Return Requests</h4></div>
    <div class="widget-body"><div class="widget-main no-padding"><div class="table-responsive">
      <table id="dynamic-table" class="table table-striped table-bordered table-hover" data-no-pdf="1">
        <thead><tr><th>No.</th><th>Request</th><th>Asset</th><th>Assigned To</th><th>Requested By</th><th>Reason</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
          <?php foreach (array_values($returnRequestGroups) as $index => $requestGroup): ?>
          <?php
          $request = $requestGroup["first"];
          $groupKey = (string) $requestGroup["reference"];
          $assets = $requestGroup["assets"];
          $statuses = array_values(array_unique($requestGroup["statuses"]));
          $isPending = in_array("PENDING", $statuses, true);
          $groupStatus = $isPending
              ? "PENDING"
              : (count($statuses) === 1 ? $statuses[0] : "PARTIAL");
          $returnNumbers = array_values(
              array_unique(array_filter($requestGroup["returnNumbers"])),
          );
          ?>
          <tr>
            <td><?= $index + 1 ?></td>
            <td><strong><?= htmlspecialchars($groupKey) ?></strong><br><small><?= htmlspecialchars($request["created_at"]) ?></small><br><span class="label label-info"><?= count($assets) ?> asset(s)</span></td>
            <td>
              <?php foreach ($assets as $assetIndex => $asset): ?>
              <div<?= $assetIndex > 0 ? ' style="border-top:1px solid #e5eaf0;margin-top:6px;padding-top:6px"' : "" ?>>
                <button type="button" class="btn btn-link btn-xs" data-asset-details="<?= htmlspecialchars($asset["asset"], ENT_QUOTES, "UTF-8") ?>"><strong><?= htmlspecialchars($asset["asset"]) ?></strong></button>
                <small><?= htmlspecialchars($asset["description"]) ?><?= $asset["serial"] ? " / SN: " . htmlspecialchars($asset["serial"]) : "" ?></small>
              </div>
              <?php endforeach; ?>
            </td>
            <td><?= htmlspecialchars($request["assignedTo"] ?: "-") ?></td>
            <td><?= htmlspecialchars($request["requestedByName"]) ?></td>
            <td><?= htmlspecialchars($request["reason"]) ?></td>
            <?php
            $groupStatusClass = $isPending
                ? "warning"
                : ($groupStatus === "COMPLETED"
                    ? "success"
                    : ($groupStatus === "CANCELLED" ? "default" : "info"));
            ?>
            <td><span class="label label-<?= $groupStatusClass ?>"><?= htmlspecialchars($groupStatus) ?></span><?php if ($returnNumbers): ?><br><small><?= htmlspecialchars(implode(", ", $returnNumbers)) ?></small><?php endif; ?></td>
            <td>
              <a class="btn btn-xs btn-danger" href="<?= BASEURL ?>/Asset/downloadReturnRequestPdf/<?= (int) $request["return_request_id"] ?>"><i class="fa fa-file-pdf-o"></i> Return Form PDF</a>
              <?php if ($isPending): ?>
              <?php $pendingGroupCount = count($processReturnGroups[$groupKey] ?? []); ?>
              <button type="button" class="btn btn-xs btn-warning process-return-btn"
                data-group="<?= htmlspecialchars($groupKey, ENT_QUOTES, "UTF-8") ?>"><i class="fa fa-reply-all"></i> <?= $pendingGroupCount > 1 ? "Process Batch (" . $pendingGroupCount . ")" : "Process Return" ?></button>
              <?php if (!empty($data["canCancelReturnRequests"])): ?>
              <button type="button" class="btn btn-xs btn-default cancel-return-request-btn"
                data-request-id="<?= (int) $request["return_request_id"] ?>"
                data-reference="<?= htmlspecialchars($groupKey, ENT_QUOTES, "UTF-8") ?>">
                <i class="fa fa-ban"></i> Cancel Request
              </button>
              <?php endif; ?>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($returnRequestGroups)): ?><tr><td colspan="8" class="text-center text-muted">No return requests found.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div></div></div>
  </div>
</div></div>

<?php if (!empty($data["canCancelReturnRequests"])): ?>
<div class="modal fade" id="cancelReturnRequestModal" tabindex="-1" role="dialog"
  aria-labelledby="cancelReturnRequestTitle">
  <div class="modal-dialog" role="document"><div class="modal-content">
    <form id="cancel-return-request-form" method="post">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="cancelReturnRequestTitle"><i class="fa fa-ban"></i> Cancel Return Request</h4>
      </div>
      <div class="modal-body">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string) $data["csrf"], ENT_QUOTES, "UTF-8") ?>">
        <div class="alert alert-warning">
          This cancels the complete RRQ batch without returning any asset. The
          request and reason remain visible in Request Transaction History.
        </div>
        <div class="form-group">
          <label>Request</label>
          <p class="form-control-static"><strong id="cancel-return-reference"></strong></p>
        </div>
        <div class="form-group">
          <label for="cancel-return-reason">Cancellation Reason <span class="text-danger">*</span></label>
          <textarea class="form-control" id="cancel-return-reason" name="reason"
            rows="3" maxlength="500" required></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-danger"><i class="fa fa-ban"></i> Cancel Complete Request</button>
      </div>
    </form>
  </div></div>
</div>
<?php endif; ?>

<div class="modal fade" id="processReturnModal" tabindex="-1" role="dialog" aria-labelledby="processReturnTitle">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <form action="<?= BASEURL ?>/Asset/storeReturn" method="post" enctype="multipart/form-data">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title" id="processReturnTitle"><i class="fa fa-reply"></i> Process Return</h4>
        </div>
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string) $data["csrf"], ENT_QUOTES, "UTF-8") ?>">
          <input type="hidden" name="return_source" value="requests">
          <input type="hidden" name="returnType" value="REISSUE">
          <input type="hidden" name="returnRequestBatch" id="processReturnBatch">
          <div id="processReturnIssueInputs"></div>

          <div class="form-group">
            <label>Return Request</label>
            <input type="text" id="processReturnRequestNo" class="form-control" readonly>
          </div>
          <div class="form-group">
            <label>Employee Reason</label>
            <textarea id="processReturnEmployeeReason" class="form-control" rows="2" readonly></textarea>
          </div>
          <div class="row">
            <div class="col-sm-12 form-group">
              <label>Assets Being Returned</label>
              <div class="table-responsive">
                <table class="table table-bordered table-striped" style="margin-bottom:0">
                  <thead><tr><th style="width:45px">No.</th><th>Asset</th><th>Serial Number</th><th>Description</th><th style="width:175px">Return Condition</th></tr></thead>
                  <tbody id="processReturnAssetRows"></tbody>
                </table>
              </div>
            </div>
            <div class="col-sm-6 form-group">
              <label>Return Date <span class="red">*</span></label>
              <input type="datetime-local" name="returnDate" class="form-control" value="<?= date("Y-m-d\TH:i") ?>" max="<?= date("Y-m-d\TH:i") ?>" required>
            </div>
          </div>
          <div class="form-group">
            <label>Issuer Remarks</label>
            <textarea name="remarks" class="form-control" rows="3" maxlength="500"></textarea>
          </div>
          <div class="form-group">
            <label>Return Images <small class="text-muted">(optional, maximum 5 MB each)</small></label>
            <input type="file" name="returnImages[]" class="form-control" accept="image/jpeg,image/png,image/webp" multiple>
          </div>
          <div class="alert alert-info" style="margin-bottom:0">Completing this form returns the asset to stock and closes the employee return request.</div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success"><i class="fa fa-check"></i> Complete Return</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
(function () {
var processReturnGroups = <?= json_encode(
    $processReturnGroups,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT,
) ?>;

function appendTextCell(row, text) {
  var cell = document.createElement('td');
  cell.textContent = text || '-';
  row.appendChild(cell);
}

document.addEventListener('click', function (event) {
  var cancelButton = event.target.closest('.cancel-return-request-btn');
  if (cancelButton) {
    var cancelForm = document.getElementById('cancel-return-request-form');
    cancelForm.action = <?= json_encode(BASEURL . "/Asset/cancelReturnRequest/") ?> +
      cancelButton.getAttribute('data-request-id');
    document.getElementById('cancel-return-reference').textContent =
      cancelButton.getAttribute('data-reference') || '-';
    document.getElementById('cancel-return-reason').value = '';
    jQuery('#cancelReturnRequestModal').modal('show');
    return;
  }

  var button = event.target.closest('.process-return-btn');
  if (!button) return;

  var groupKey = button.getAttribute('data-group');
  var assets = processReturnGroups[groupKey] || [];
  if (!assets.length) return;

  var form = document.querySelector('#processReturnModal form');
  form.reset();
  var issueInputs = document.getElementById('processReturnIssueInputs');
  var assetRows = document.getElementById('processReturnAssetRows');
  issueInputs.innerHTML = '';
  assetRows.innerHTML = '';
  document.getElementById('processReturnBatch').value = groupKey;

  assets.forEach(function (asset, index) {
    var issueInput = document.createElement('input');
    issueInput.type = 'hidden';
    issueInput.name = 'issueIds[]';
    issueInput.value = asset.issue;
    issueInputs.appendChild(issueInput);

    var row = document.createElement('tr');
    appendTextCell(row, String(index + 1));
    appendTextCell(row, asset.asset);
    appendTextCell(row, asset.serial);
    appendTextCell(row, asset.description);

    var conditionCell = document.createElement('td');
    var condition = document.createElement('select');
    condition.name = 'conditionIn[' + asset.issue + ']';
    condition.className = 'form-control input-sm';
    condition.required = true;
    [
      ['NEW', 'New'],
      ['GOOD', 'Good'],
      ['DAMAGED', 'Damaged']
    ].forEach(function (optionData) {
      var option = document.createElement('option');
      option.value = optionData[0];
      option.textContent = optionData[1];
      condition.appendChild(option);
    });
    conditionCell.appendChild(condition);
    row.appendChild(conditionCell);
    assetRows.appendChild(row);
  });

  document.getElementById('processReturnRequestNo').value = groupKey +
    (assets.length > 1 ? ' (' + assets.length + ' assets)' : '');
  document.getElementById('processReturnEmployeeReason').value = assets[0].reason || '';
  document.getElementById('processReturnTitle').innerHTML = '<i class="fa fa-reply"></i> Process Return' +
    (assets.length > 1 ? ' - ' + assets.length + ' Assets' : '');
  jQuery('#processReturnModal').modal('show');
});
})();
</script>
<?php require __DIR__ . "/../templates/asset_details_modal.php"; ?>
