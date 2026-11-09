<?php
$statusLabels = [
    "PENDING_DEPARTMENT_APPROVAL" => "Department Approval",
    "PENDING_IT_APPROVAL" => "IT Approval",
    "APPROVED" => "Waiting for Issuer to Issue",
    "PENDING_RECEIVER_ACCEPTANCE" => "Receiver Acceptance",
    "COMPLETED" => "Completed",
    "REJECTED" => "Rejected",
    "CANCELLED" => "Cancelled",
];
$statusClasses = [
    "PENDING_DEPARTMENT_APPROVAL" => "label-warning",
    "PENDING_IT_APPROVAL" => "label-info",
    "APPROVED" => "label-primary",
    "PENDING_RECEIVER_ACCEPTANCE" => "label-purple",
    "COMPLETED" => "label-success",
    "REJECTED" => "label-danger",
    "CANCELLED" => "label-default",
];
?>
<div class="row"><div class="col-xs-12">
  <?php Flasher::msgInfo(); ?>
  <div class="clearfix" style="margin-bottom:15px">
    <?php if (!empty($data["canCreate"])): ?>
    <a href="<?= BASEURL ?>/Request/create" class="btn btn-success"><i class="fa fa-plus"></i> New Equipment Request</a>
    <?php endif; ?>
    <?php if (array_intersect($data["requestContext"]["roles"] ?? [], ["ADMINISTRATOR", "ISSUER"])): ?>
    <a href="<?= BASEURL ?>/Asset/returnRequests" class="btn btn-warning"><i class="fa fa-inbox"></i> Return Requests</a>
    <?php endif; ?>
    <?php if (array_intersect($data["requestContext"]["roles"] ?? [], ["ADMINISTRATOR", "ISSUER", "IT_MANAGER"])): ?>
    <a href="<?= BASEURL ?>/Scrap" class="btn btn-danger"><i class="fa fa-recycle"></i> Scrap Approval</a>
    <?php endif; ?>
    <span class="text-muted" style="margin-left:10px">Request equipment types only. IT selects specifications and the issuer selects physical assets.</span>
  </div>

  <ul class="nav nav-tabs" style="margin-bottom:15px">
    <?php if (!empty($data["canViewAllRequests"])): ?>
    <li class="<?= ($data["requestView"] ?? "") === "all" ? "active" : "" ?>">
      <a href="<?= BASEURL ?>/Request?view=all">
        <i class="fa fa-tasks"></i> Available Requests
      </a>
    </li>
    <?php endif; ?>
    <li class="<?= ($data["requestView"] ?? "") === "mine" ? "active" : "" ?>">
      <a href="<?= BASEURL ?>/Request?view=mine">
        <i class="fa fa-user"></i> My Requests
      </a>
    </li>
    <?php if (!empty($data["canViewDepartmentRequests"])): ?>
    <li class="<?= ($data["requestView"] ?? "") === "department"
        ? "active"
        : "" ?>">
      <a href="<?= BASEURL ?>/Request?view=department">
        <i class="fa fa-building"></i> Department Requests
      </a>
    </li>
    <?php endif; ?>
  </ul>

  <?php if (!empty($data["pendingOnly"])): ?>
  <div class="alert alert-info">
    Showing requests currently waiting for your action.
    <a href="<?= BASEURL ?>/Request?view=<?= urlencode(
    (string) ($data["requestView"] ?? "mine"),
) ?>" class="pull-right">Show all</a>
  </div>
  <?php endif; ?>

  <div class="widget-box">
    <div class="widget-header"><h4 class="widget-title"><i class="fa fa-list-alt"></i>
      <?= htmlspecialchars(
          (string) ($data["requestListTitle"] ?? "Equipment Requests"),
      ) ?></h4></div>
    <div class="widget-body"><div class="widget-main no-padding"><div class="table-responsive">
      <table id="dynamic-table" class="table table-striped table-bordered table-hover" data-no-pdf="1">
        <thead><tr><th>No.</th><th>Request</th><th>Requested For</th><th>Department</th><th>Equipment</th><th>Required</th><th>Status</th><th>Created</th><th>Action</th></tr></thead>
        <tbody>
          <?php foreach ($data["requests"] as $index => $request): ?>
          <tr>
            <td><?= $index + 1 ?></td>
            <td><strong><?= htmlspecialchars($request["request_no"]) ?></strong><br><small class="text-muted">By <?= htmlspecialchars($request["requestedByName"]) ?></small></td>
            <td><?= htmlspecialchars($request["requestedForName"]) ?><br><small><?= (int) $request["requested_for"] ?></small></td>
            <td><?= htmlspecialchars($request["deptName"]) ?></td>
            <td><?= htmlspecialchars($request["itemSummary"]) ?></td>
            <td><?= $request["required_date"] ? htmlspecialchars($request["required_date"]) : "-" ?></td>
            <td><span class="label <?= $statusClasses[$request["status"]] ?? "label-default" ?>"><?= htmlspecialchars($statusLabels[$request["status"]] ?? $request["status"]) ?></span></td>
            <td><?= htmlspecialchars($request["created_at"]) ?></td>
            <td>
              <a class="btn btn-xs btn-primary" href="<?= BASEURL ?>/Request/details/<?= (int) $request["request_id"] ?>"><i class="fa fa-eye"></i> View</a>
              <?php if (
                  $request["status"] === "PENDING_DEPARTMENT_APPROVAL" &&
                  (string) $request["requested_by"] ===
                      (string) ($data["requestContext"]["username"] ?? "")
              ): ?>
              <a class="btn btn-xs btn-warning" href="<?= BASEURL ?>/Request/edit/<?= (int) $request["request_id"] ?>"><i class="fa fa-pencil"></i> Edit</a>
              <?php endif; ?>
              <?php if (!empty($request["issueIds"])): ?>
              <a class="btn btn-xs btn-danger" href="<?= BASEURL ?>/Asset/downloadAssignmentPdf/<?= htmlspecialchars($request["issueIds"], ENT_QUOTES, "UTF-8") ?>" title="Download combined Assignment Form PDF"><i class="fa fa-file-pdf-o"></i> Assignment PDF</a>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($data["requests"])): ?><tr><td colspan="9" class="text-center text-muted">No <?= htmlspecialchars(
              strtolower(
                  (string) ($data["requestListTitle"] ??
                      "equipment requests"),
              ),
          ) ?> are available.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div></div></div>
  </div>
</div></div>
