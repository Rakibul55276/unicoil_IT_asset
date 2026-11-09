<?php
$roles = $data["context"]["roles"] ?? [];
$isAdministrator = in_array("ADMINISTRATOR", $roles, true);
?>
<div class="row"><div class="col-xs-12">
  <?php Flasher::msgInfo(); ?>
  <?php if ($isAdministrator || in_array("ISSUER", $roles, true)): ?>
  <a href="<?= BASEURL ?>/Scrap/create" class="btn btn-danger" style="margin-bottom:15px"><i class="fa fa-plus"></i> New Scrap Request</a>
  <?php endif; ?>
  <div class="alert alert-info">Submitting a request does not scrap the asset. The asset changes to Scrapped only after IT Manager approval.</div>
  <div class="widget-box"><div class="widget-header"><h4 class="widget-title"><i class="fa fa-recycle"></i> Scrap Requests</h4></div>
    <div class="widget-body"><div class="widget-main no-padding"><div class="table-responsive">
      <table id="dynamic-table" class="table table-striped table-bordered table-hover" data-no-pdf="1">
        <thead><tr><th>No.</th><th>Request</th><th>Asset</th><th>Category</th><th>Requested By</th><th>Reason</th><th>Status</th><th>Review</th></tr></thead>
        <tbody><?php foreach ($data["requests"] as $index => $request): ?>
          <tr>
            <td><?= $index + 1 ?></td>
            <td><strong><?= htmlspecialchars($request["scrap_request_no"]) ?></strong><br><small><?= htmlspecialchars($request["created_at"]) ?></small></td>
            <td><button type="button" class="btn btn-link btn-xs" data-asset-details="<?= htmlspecialchars($request["assetNum"], ENT_QUOTES, "UTF-8") ?>"><?= htmlspecialchars($request["assetNum"]) ?></button><br><small><?= htmlspecialchars($request["Description"]) ?></small></td>
            <td><?= htmlspecialchars($request["categoryName"] ?: "-") ?></td>
            <td><?= htmlspecialchars($request["requestedByName"]) ?></td>
            <td><?= htmlspecialchars($request["reason"]) ?></td>
            <td><span class="label label-<?= $request["status"] === "APPROVED" ? "success" : ($request["status"] === "REJECTED" ? "danger" : "warning") ?>"><?= htmlspecialchars(str_replace("_", " ", $request["status"])) ?></span></td>
            <td>
              <?php if ($request["status"] === "PENDING_IT_APPROVAL" && ($isAdministrator || in_array("IT_MANAGER", $roles, true))): ?>
              <form action="<?= BASEURL ?>/Scrap/approve/<?= (int) $request["scrap_request_id"] ?>" method="post" style="margin-bottom:5px">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($data["csrf"]) ?>">
                <input type="text" name="comment" class="form-control input-sm" maxlength="500" placeholder="Optional approval comment" style="margin-bottom:4px">
                <button class="btn btn-xs btn-success" type="submit" onclick="return confirm('Approve scrapping this asset?');"><i class="fa fa-check"></i> Approve</button>
              </form>
              <form action="<?= BASEURL ?>/Scrap/reject/<?= (int) $request["scrap_request_id"] ?>" method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($data["csrf"]) ?>">
                <input type="text" name="reason" class="form-control input-sm" maxlength="500" required placeholder="Rejection reason" style="margin-bottom:4px">
                <button class="btn btn-xs btn-danger" type="submit"><i class="fa fa-times"></i> Reject</button>
              </form>
              <?php else: ?>
              <?= htmlspecialchars($request["reviewedByName"] ?: "-") ?><br><small><?= htmlspecialchars($request["review_comment"] ?: "") ?></small>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?><?php if (!$data["requests"]): ?><tr><td colspan="8" class="text-center text-muted">No scrap requests recorded.</td></tr><?php endif; ?></tbody>
      </table>
    </div></div></div>
  </div>
</div></div>
<?php require __DIR__ . "/../templates/asset_details_modal.php"; ?>
