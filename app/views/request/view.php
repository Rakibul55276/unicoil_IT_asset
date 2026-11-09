<?php
$request = $data["request"];
$statusLabels = [
    "PENDING_DEPARTMENT_APPROVAL" => "Waiting for Department Manager",
    "PENDING_IT_APPROVAL" => "Waiting for IT Manager",
    "APPROVED" => "Waiting for Issuer to Issue",
    "PENDING_RECEIVER_ACCEPTANCE" => "Waiting for Receiver Acceptance",
    "COMPLETED" => "Completed",
    "REJECTED" => "Rejected",
    "CANCELLED" => "Cancelled by Administrator",
];
$statusClasses = [
    "PENDING_DEPARTMENT_APPROVAL" => "request-status-warning",
    "PENDING_IT_APPROVAL" => "request-status-info",
    "APPROVED" => "request-status-primary",
    "PENDING_RECEIVER_ACCEPTANCE" => "request-status-purple",
    "COMPLETED" => "request-status-success",
    "REJECTED" => "request-status-danger",
    "CANCELLED" => "request-status-muted",
];
$requestRoles = array_values(
    array_filter($data["requestContext"]["roles"] ?? []),
);
$canViewOrganizationAssignments = (bool) array_intersect(
    $requestRoles,
    ["ADMINISTRATOR", "ISSUER", "IT_MANAGER"],
);
$assignmentHistoryUrl = $canViewOrganizationAssignments
    ? BASEURL . "/Asset/active"
    : BASEURL . "/Asset/myAssetHistory";
$approvalStatusLabels = [
    "APPROVED" => "Completed",
    "WAITING" => "Waiting for Action",
    "QUEUED" => "Queued - Waiting for Previous Approval",
    "REJECTED" => "Rejected",
    "SKIPPED" => "Skipped",
    "CANCELLED" => "Cancelled",
];
$availableByItem = [];
foreach ($data["availableAssets"] ?? [] as $asset) {
    $availableByItem[(int) $asset["request_item_id"]][] = $asset;
}
$allocationsByItem = [];
foreach ($request["allocations"] as $allocation) {
    $allocationsByItem[(int) $allocation["request_item_id"]][] = $allocation;
}
?>
<style>
  .request-summary dt { color:#667085; margin-top:8px }
  .timeline-row { border-left:3px solid #438eb9; padding:0 0 15px 15px; margin-left:8px }
  .timeline-row:last-child { padding-bottom:0 }
  .request-detail-toolbar {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:15px;
    margin-bottom:14px;
    padding:12px 14px;
    background:#fff;
    border:1px solid #dfe6ee;
    border-radius:8px;
    box-shadow:0 3px 10px rgba(33, 55, 78, .05)
  }
  .request-detail-actions {
    display:flex;
    align-items:center;
    flex-wrap:wrap;
    gap:8px
  }
  .request-detail-actions .btn { margin:0 }
  .request-status-panel {
    min-width:205px;
    text-align:right
  }
  .request-status-caption {
    display:block;
    margin-bottom:4px;
    color:#6b778c;
    font-size:11px;
    font-weight:700;
    letter-spacing:.5px;
    text-transform:uppercase
  }
  .request-status {
    display:inline-flex;
    align-items:center;
    gap:7px;
    max-width:330px;
    padding:8px 13px;
    border:1px solid transparent;
    border-radius:18px;
    font-size:13px;
    font-weight:700;
    line-height:1.2
  }
  .request-status:before {
    width:8px;
    height:8px;
    border-radius:50%;
    background:currentColor;
    content:"";
    flex:0 0 auto
  }
  .request-status-warning { color:#9a5b00; background:#fff4df; border-color:#f4c37d }
  .request-status-info { color:#176b87; background:#e8f6fb; border-color:#9ed5e7 }
  .request-status-primary { color:#255fa0; background:#eaf2fb; border-color:#a9c7e8 }
  .request-status-purple { color:#70429a; background:#f3ebfa; border-color:#ceb3e5 }
  .request-status-success { color:#177245; background:#e9f7ef; border-color:#9bd0b3 }
  .request-status-danger { color:#b23b2a; background:#fdeceb; border-color:#e4a69b }
  .request-status-muted { color:#657180; background:#f1f3f5; border-color:#cdd3da }
  @media (max-width:767px) {
    .request-detail-toolbar {
      align-items:stretch;
      flex-direction:column
    }
    .request-status-panel {
      min-width:0;
      padding-top:10px;
      border-top:1px solid #edf1f5;
      text-align:left
    }
  }
</style>
<div class="row"><div class="col-xs-12">
  <?php Flasher::msgInfo(); ?>
  <div class="request-detail-toolbar">
    <div class="request-detail-actions">
      <a href="<?= BASEURL ?>/Request" class="btn btn-default">
        <i class="fa fa-arrow-left"></i> All Requests
      </a>
      <a href="<?= htmlspecialchars(
          $assignmentHistoryUrl,
          ENT_QUOTES,
          "UTF-8",
      ) ?>" class="btn btn-primary">
        <i class="fa fa-history"></i> Assignment History
      </a>
      <?php if (!empty($data["canEdit"])): ?>
      <a href="<?= BASEURL ?>/Request/edit/<?= (int) $request[
    "request_id"
] ?>" class="btn btn-warning">
        <i class="fa fa-pencil"></i> Edit Requested Items
      </a>
      <?php endif; ?>
      <?php if (!empty($data["canCancelRequest"])): ?>
      <a href="#cancel-equipment-request" class="btn btn-danger">
        <i class="fa fa-ban"></i> Cancel Request
      </a>
      <?php elseif (!empty($data["showCancelRequestControl"])): ?>
      <button type="button" class="btn btn-danger" disabled
        title="<?= htmlspecialchars(
            (string) ($data["cancelRequestBlockedReason"] ?? "Cancellation is unavailable."),
            ENT_QUOTES,
            "UTF-8",
        ) ?>">
        <i class="fa fa-lock"></i> Cancellation Locked
      </button>
      <?php endif; ?>
    </div>
    <div class="request-status-panel">
      <span class="request-status-caption">Request Status</span>
      <span class="request-status <?= $statusClasses[$request["status"]] ??
          "request-status-muted" ?>">
        <?= htmlspecialchars(
            $statusLabels[$request["status"]] ?? $request["status"],
        ) ?>
      </span>
    </div>
  </div>

  <div class="row">
    <div class="col-md-5">
      <div class="widget-box">
        <div class="widget-header"><h4 class="widget-title"><i class="fa fa-file-text-o"></i> Request Summary</h4></div>
        <div class="widget-body"><div class="widget-main request-summary">
          <dl>
            <dt>Requested For</dt><dd><strong><?= htmlspecialchars($request["request_target_type"] === "DEPARTMENT" ? $request["deptName"] : $request["requestedForName"]) ?></strong><?= $request["request_target_type"] === "USER" ? " (" . (int) $request["requested_for"] . ")" : " (Department)" ?></dd>
            <dt>Department</dt><dd><?= htmlspecialchars($request["deptName"]) ?></dd>
            <?php if ($request["request_target_type"] === "DEPARTMENT"): ?>
            <dt>Department Receiver</dt>
            <dd>
              <?= htmlspecialchars($request["receiverName"] ?: $request["departmentHeadName"] ?: "-") ?>
              (ID: <?= (int) ($request["receiver_emp_id"] ?? $request["headEmpId"] ?? 0) ?>)
              <?php if (!empty($request["receiverPosition"])): ?>
              &mdash; <?= htmlspecialchars($request["receiverPosition"]) ?>
              <?php endif; ?>
            </dd>
            <?php endif; ?>
            <dt>Submitted By</dt><dd><?= htmlspecialchars($request["requestedByName"]) ?> &mdash; <?= $request["request_source"] === "SELF" ? "Self request" : "On behalf" ?></dd>
            <dt>Required Date</dt><dd><?= $request["required_date"] ? htmlspecialchars($request["required_date"]) : "Not specified" ?></dd>
            <dt>Business Reason</dt><dd><?= nl2br(htmlspecialchars($request["business_reason"])) ?></dd>
            <?php if (!empty($request["rejection_reason"])): ?><dt>Rejection Reason</dt><dd class="text-danger"><?= htmlspecialchars($request["rejection_reason"]) ?></dd><?php endif; ?>
            <?php if (!empty($request["receiver_accepted_at"])): ?><dt>Receiver Accepted</dt><dd><?= htmlspecialchars($request["receiver_accepted_at"]) ?> by <?= htmlspecialchars($request["receiver_accepted_by"] ?: "-") ?></dd><?php endif; ?>
          </dl>
        </div></div>
      </div>
    </div>
    <div class="col-md-7">
      <div class="widget-box">
        <div class="widget-header"><h4 class="widget-title"><i class="fa fa-list"></i> Requested Equipment</h4></div>
        <div class="widget-body"><div class="widget-main no-padding"><div class="table-responsive">
          <table class="table table-bordered table-striped" style="margin-bottom:0">
            <thead><tr><th>Equipment Type</th><th>Requested</th><th>Approved</th><th>IT Specification</th><th>Issued Assets</th></tr></thead>
            <tbody><?php foreach ($request["items"] as $item): ?>
              <tr>
                <td><strong><?= htmlspecialchars($item["categoryName"]) ?></strong></td>
                <td><?= (int) $item["quantity"] ?></td>
                <td><?= $item["approved_quantity"] === null ? "Pending" : (int) $item["approved_quantity"] ?></td>
                <td><?= htmlspecialchars($item["it_specification"] ?: "-") ?></td>
                <td>
                  <?php foreach ($allocationsByItem[(int) $item["request_item_id"]] ?? [] as $allocation): ?>
                  <button type="button" class="btn btn-link btn-xs" style="margin:2px" data-asset-details="<?= htmlspecialchars(
                      $allocation["assetNum"],
                      ENT_QUOTES,
                      "UTF-8",
                  ) ?>"><span class="label label-success"><?= htmlspecialchars($allocation["assetNum"]) ?><?= $allocation["serial_number"] ? " / " . htmlspecialchars($allocation["serial_number"]) : "" ?></span></button>
                  <?php endforeach; ?>
                  <?php if (empty($allocationsByItem[(int) $item["request_item_id"]])): ?>-<?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?></tbody>
          </table>
        </div></div></div>
      </div>
    </div>
  </div>

  <?php if (!empty($request["nextApprover"])): ?>
  <?php $nextApprover = $request["nextApprover"]; ?>
  <div class="alert alert-warning" style="margin-top:15px">
    <i class="fa fa-user-circle"></i>
    <strong>Waiting for next approval:</strong>
    <?= htmlspecialchars($nextApprover["fullname"] ?: $nextApprover["approver_username"]) ?>
    <?php if (!empty($nextApprover["empId"])): ?>
    (ID: <?= (int) $nextApprover["empId"] ?>)
    <?php endif; ?>
    <?php if (!empty($nextApprover["position"])): ?>
    &mdash; <?= htmlspecialchars($nextApprover["position"]) ?>
    <?php endif; ?>
    <?php if (!empty($nextApprover["deptName"])): ?>
    &mdash; <?= htmlspecialchars($nextApprover["deptName"]) ?>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <?php if (!empty($data["canDepartmentApprove"])): ?>
  <div class="widget-box" style="margin-top:15px"><div class="widget-header"><h4 class="widget-title">Department Manager Decision</h4></div>
    <div class="widget-body"><div class="widget-main"><form action="<?= BASEURL ?>/Request/approveDepartment/<?= (int) $request["request_id"] ?>" method="post">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($data["csrf"]) ?>">
      <div class="row"><div class="col-md-9"><input type="text" name="remarks" class="form-control" maxlength="500" placeholder="Optional approval remarks"></div>
      <div class="col-md-3 text-right"><button class="btn btn-success" type="submit"><i class="fa fa-check"></i> Department Approve</button></div></div>
    </form></div></div>
  </div>
  <?php endif; ?>

  <?php if (!empty($data["canItApprove"])): ?>
  <div class="widget-box" style="margin-top:15px"><div class="widget-header"><h4 class="widget-title">IT Review and Specification</h4></div>
    <div class="widget-body"><div class="widget-main"><form action="<?= BASEURL ?>/Request/approveIt/<?= (int) $request["request_id"] ?>" method="post">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($data["csrf"]) ?>">
      <div class="alert alert-info">Confirm quantity and add a standard specification only if needed. The requester was not required to provide technical details.</div>
      <?php foreach ($request["items"] as $item): ?><div class="row" style="margin-bottom:10px">
        <div class="col-md-3"><strong><?= htmlspecialchars($item["categoryName"]) ?></strong><br><small>Requested: <?= (int) $item["quantity"] ?></small></div>
        <div class="col-md-2"><input type="number" class="form-control" name="approved_quantity[<?= (int) $item["request_item_id"] ?>]" min="0" max="<?= (int) $item["quantity"] ?>" value="<?= (int) $item["quantity"] ?>" required></div>
        <div class="col-md-7"><input type="text" class="form-control" name="it_specification[<?= (int) $item["request_item_id"] ?>]" maxlength="500" placeholder="Optional: standard, performance level or compatibility"></div>
      </div><?php endforeach; ?>
      <div class="row"><div class="col-md-9"><input type="text" name="remarks" class="form-control" maxlength="500" placeholder="Optional IT approval remarks"></div>
      <div class="col-md-3 text-right"><button class="btn btn-primary" type="submit"><i class="fa fa-check-circle"></i> IT Approve</button></div></div>
    </form></div></div>
  </div>
  <?php endif; ?>

  <?php if (!empty($data["canAdminManageApproval"])): ?>
  <div class="widget-box" style="margin-top:15px"><div class="widget-header"><h4 class="widget-title">Administrator Approval Status Control</h4></div>
    <div class="widget-body"><div class="widget-main">
      <div class="alert alert-warning"><strong>Controlled override:</strong> Return the request to Department or IT approval, or move it forward to the Issuer. Every change requires a reason and is recorded in the audit trail. Issued or completed requests cannot be changed here.</div>
      <form action="<?= BASEURL ?>/Request/adminChangeApprovalStatus/<?= (int) $request["request_id"] ?>" method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($data["csrf"]) ?>">
        <div class="row">
          <div class="col-md-4">
            <label for="admin-target-status">Move request to</label>
            <select class="form-control" id="admin-target-status" name="target_status" required>
              <option value="">-- Select status --</option>
              <option value="PENDING_DEPARTMENT_APPROVAL">Return to Department Manager</option>
              <option value="PENDING_IT_APPROVAL">Return to IT Manager</option>
              <option value="APPROVED">Forward to Issuer Queue</option>
            </select>
          </div>
          <div class="col-md-6">
            <label for="admin-status-reason">Reason <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="admin-status-reason" name="reason" maxlength="500" required placeholder="Explain why the request is being moved">
          </div>
          <div class="col-md-2 text-right" style="padding-top:25px">
            <button class="btn btn-warning" type="submit"><i class="fa fa-random"></i> Update Status</button>
          </div>
        </div>
      </form>
    </div></div>
  </div>
  <?php endif; ?>

  <?php if (!empty($data["showCancelRequestControl"])): ?>
  <div class="widget-box" id="cancel-equipment-request" style="margin-top:15px">
    <div class="widget-header">
      <h4 class="widget-title text-danger"><i class="fa fa-ban"></i> Cancel Equipment Request</h4>
    </div>
    <div class="widget-body"><div class="widget-main">
      <?php if (!empty($data["canCancelRequest"])): ?>
      <div class="alert alert-danger">
        Cancellation stops the approval/issuance queue but keeps the request and
        its audit trail. Issued or completed requests cannot be cancelled.
      </div>
      <form action="<?= BASEURL ?>/Request/cancel/<?= (int) $request["request_id"] ?>"
        method="post" onsubmit="return confirm('Cancel this equipment request? This action is recorded in request history.');">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($data["csrf"]) ?>">
        <div class="row">
          <div class="col-md-10">
            <label for="request-cancellation-reason">Cancellation Reason <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="request-cancellation-reason"
              name="reason" maxlength="500" required
              placeholder="Explain why this request is being cancelled">
          </div>
          <div class="col-md-2 text-right" style="padding-top:25px">
            <button class="btn btn-danger" type="submit"><i class="fa fa-ban"></i> Cancel Request</button>
          </div>
        </div>
      </form>
      <?php else: ?>
      <div class="alert alert-warning" style="margin-bottom:0">
        <i class="fa fa-lock"></i>
        <strong>Cancellation is locked.</strong>
        <?= htmlspecialchars(
            (string) ($data["cancelRequestBlockedReason"] ?? "This request can no longer be cancelled."),
        ) ?>
      </div>
      <?php endif; ?>
    </div></div>
  </div>
  <?php endif; ?>

  <?php if (!empty($data["canIssue"])): ?>
  <div class="widget-box" style="margin-top:15px"><div class="widget-header"><h4 class="widget-title">Issuer - Select Physical Assets</h4></div>
    <div class="widget-body"><div class="widget-main"><form action="<?= BASEURL ?>/Request/issue/<?= (int) $request["request_id"] ?>" method="post" id="request-issue-form">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($data["csrf"]) ?>">
      <div class="alert alert-warning"><strong>One-time issuance:</strong> select every approved asset below and submit once. The request moves directly to Receiver Acceptance after the issue succeeds.</div>
      <?php foreach ($request["items"] as $item): ?><?php
      $approvedQuantity = (int) ($item["approved_quantity"] ?? 0);
      $allocatedQuantity = (int) ($item["allocatedCount"] ?? 0);
      $remainingQuantity = max(0, $approvedQuantity - $allocatedQuantity);
      $batchMaximum = min(8, $remainingQuantity);
      if ($remainingQuantity > 0): ?>
      <div class="form-group"><label><?= htmlspecialchars($item["categoryName"]) ?> &mdash; <?= $remainingQuantity ?> remaining of <?= $approvedQuantity ?> approved</label>
        <select class="form-control searchable-select allocation-select" multiple data-max-count="<?= $batchMaximum ?>"
          name="item_assets[<?= (int) $item["request_item_id"] ?>][]"
          data-placeholder="Type at least 2 characters"
          data-remote-url="<?= BASEURL ?>/Request/searchAvailableAssets/<?= (int) $request[
    "request_id"
] ?>/<?= (int) $item["request_item_id"] ?>">
          <?php foreach ($availableByItem[(int) $item["request_item_id"]] ?? [] as $asset): ?>
          <option value="<?= htmlspecialchars($asset["assetNum"]) ?>"><?= htmlspecialchars($asset["assetNum"] . " - " . $asset["Description"] . ($asset["serial_number"] ? " - " . $asset["serial_number"] : "")) ?></option>
          <?php endforeach; ?>
        </select>
        <p class="help-block">Select exactly <?= $remainingQuantity ?> asset(s) for this equipment type.</p>
      </div>
      <?php endif; ?><?php endforeach; ?>
      <div class="form-group">
        <label for="issuer_remarks">Issuer Remarks</label>
        <textarea name="remarks" id="issuer_remarks" class="form-control" rows="3"
          maxlength="500" placeholder="Optional remarks to include in the assignment PDF"></textarea>
      </div>
      <div id="allocation-error" class="alert alert-danger hide"></div>
      <div class="text-right"><button class="btn btn-success" type="submit"><i class="fa fa-exchange"></i> Issue All Approved Assets</button></div>
    </form></div></div>
  </div>
  <?php endif; ?>

  <?php if (!empty($data["canAccept"])): ?>
  <div class="widget-box" style="margin-top:15px"><div class="widget-header"><h4 class="widget-title">Receiver Acceptance</h4></div>
    <div class="widget-body"><div class="widget-main"><form action="<?= BASEURL ?>/Request/accept/<?= (int) $request["request_id"] ?>" method="post">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($data["csrf"]) ?>">
      <div class="alert alert-info">By accepting, you confirm that the listed assets were received<?= $request["request_target_type"] === "DEPARTMENT" ? " for the department" : " by you" ?>. The date, time and your user account will be recorded.</div>
      <div class="row"><div class="col-md-9"><input type="text" name="comment" class="form-control" maxlength="500" placeholder="Optional receiver comment"></div>
      <div class="col-md-3 text-right"><button class="btn btn-success" type="submit"><i class="fa fa-check"></i> Accept Receipt</button></div></div>
    </form></div></div>
  </div>
  <?php endif; ?>

  <?php if (!empty($data["canReject"])): ?>
  <div class="widget-box" style="margin-top:15px"><div class="widget-header"><h4 class="widget-title text-danger">Reject Request</h4></div>
    <div class="widget-body"><div class="widget-main"><form action="<?= BASEURL ?>/Request/reject/<?= (int) $request["request_id"] ?>" method="post" onsubmit="return confirm('Reject this equipment request?');">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($data["csrf"]) ?>">
      <div class="row"><div class="col-md-9"><input type="text" name="reason" class="form-control" maxlength="500" required placeholder="Rejection reason"></div>
      <div class="col-md-3 text-right"><button class="btn btn-danger" type="submit"><i class="fa fa-times"></i> Reject</button></div></div>
    </form></div></div>
  </div>
  <?php endif; ?>

  <div class="widget-box" style="margin-top:15px"><div class="widget-header"><h4 class="widget-title"><i class="fa fa-history"></i> Approval Queue and Audit Trail</h4></div>
    <div class="widget-body"><div class="widget-main">
      <?php if (!empty($request["approvalSteps"])): ?>
      <div class="alert alert-info">
        This route shows completed approvals, the current approver, and future
        queued approvers. A queued approver receives access only after the
        previous step is completed.
      </div>
      <div class="table-responsive" style="margin-bottom:15px">
        <table class="table table-bordered table-condensed">
          <thead><tr><th>Step</th><th>Approver</th><th>ID</th><th>Position</th><th>Status</th><th>Action Date</th><th>Remarks</th></tr></thead>
          <tbody>
            <?php foreach ($request["approvalSteps"] as $approvalStep): ?>
            <tr>
              <td><?= htmlspecialchars(
                  $approvalStep["step_type"] === "IT_MANAGER"
                      ? "IT Manager Approval"
                      : ($approvalStep["step_type"] === "ADMINISTRATOR"
                          ? "Administrator Approval"
                          : "Department Manager Approval"),
              ) ?></td>
              <td><?= htmlspecialchars(
                  $approvalStep["fullname"] ?: $approvalStep["approver_username"],
              ) ?><br><small><?= htmlspecialchars($approvalStep["approver_username"]) ?></small></td>
              <td><?= $approvalStep["empId"] ? (int) $approvalStep["empId"] : "-" ?></td>
              <td><?= htmlspecialchars($approvalStep["position"] ?: "-") ?></td>
              <td><span class="label <?= $approvalStep["status"] === "APPROVED"
                  ? "label-success"
                  : ($approvalStep["status"] === "WAITING"
                      ? "label-warning"
                      : ($approvalStep["status"] === "REJECTED"
                          ? "label-danger"
                          : "label-default")) ?>"><?= htmlspecialchars(
    $approvalStatusLabels[$approvalStep["status"]] ??
        $approvalStep["status"],
) ?></span></td>
              <td><?= htmlspecialchars($approvalStep["acted_at"] ?: "-") ?></td>
              <td><?= htmlspecialchars($approvalStep["remarks"] ?: "-") ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
      <?php foreach ($request["events"] as $event): ?><div class="timeline-row">
        <strong><?= htmlspecialchars(str_replace("_", " ", $event["event_type"])) ?></strong>
        <span class="text-muted">by <?= htmlspecialchars($event["actorName"]) ?> on <?= htmlspecialchars($event["created_at"]) ?></span>
        <?php if (!empty($event["remarks"])): ?><div><?= htmlspecialchars($event["remarks"]) ?></div><?php endif; ?>
      </div><?php endforeach; ?>
    </div></div>
  </div>
</div></div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var form = document.getElementById('request-issue-form');
  if (!form) return;
  form.addEventListener('submit', function (event) {
    var message = '', total = 0;
    Array.prototype.forEach.call(form.querySelectorAll('.allocation-select'), function (select) {
      var maximum = parseInt(select.getAttribute('data-max-count'), 10);
      var selected = Array.prototype.filter.call(select.options, function (option) { return option.selected; }).length;
      total += selected;
      if (selected !== maximum && !message) message = 'Select all ' + maximum + ' approved asset(s) for every equipment type.';
    });
    if (!message && total < 1) message = 'Select every approved asset before issuing.';
    if (!message && total > 8) message = 'One request can issue a maximum of 8 approved assets.';
    var box = document.getElementById('allocation-error');
    box.textContent = message;
    box.classList.toggle('hide', !message);
    if (message) event.preventDefault();
  });
});
</script>
<?php require __DIR__ . "/../templates/asset_details_modal.php"; ?>
