<?php
$filters = $data["filters"] ?? [
    "search" => "",
    "category" => "ALL",
    "perPage" => 50,
];
$pagination = $data["pagination"] ?? [
    "page" => 1,
    "total" => count($data["assets"] ?? []),
    "totalPages" => 1,
    "offset" => 0,
];
$page = (int) ($pagination["page"] ?? 1);
$totalPages = (int) ($pagination["totalPages"] ?? 1);
$queryBase = [
    "search" => $filters["search"],
    "category" => $filters["category"],
    "perPage" => $filters["perPage"],
];
?>
<div class="page-content">
  <div class="container-fluid">
    <div class="col-xs-12">
      <div class="widget-box" style="margin-bottom:14px">
        <div class="widget-header">
          <h4 class="widget-title"><i class="fa fa-filter"></i> Filter Scrapped Assets</h4>
        </div>
        <div class="widget-body">
          <div class="widget-main">
            <form method="get" action="<?= BASEURL ?>/Asset/disposed" class="row">
              <div class="col-sm-4">
                <label for="scrapSearch">Search</label>
                <input type="search" id="scrapSearch" name="search" class="form-control"
                  value="<?= htmlspecialchars((string) $filters["search"], ENT_QUOTES, "UTF-8") ?>"
                  placeholder="Asset, serial, or description prefix">
              </div>
              <div class="col-sm-3">
                <label for="scrapCategoryFilter">Category</label>
                <select id="scrapCategoryFilter" name="category"
                  class="form-control searchable-select" data-placeholder="Filter by category">
                  <option value="ALL">All categories</option>
                  <?php foreach ($data["categories"] ?? [] as $category): ?>
                  <option value="<?= htmlspecialchars(
                      (string) $category["assetKat"],
                      ENT_QUOTES,
                      "UTF-8",
                  ) ?>" <?= (string) $filters["category"] ===
    (string) $category["assetKat"]
    ? "selected"
    : "" ?>>
                    <?= htmlspecialchars((string) $category["Description"]) ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-sm-2">
                <label for="scrapPerPage">Rows</label>
                <select id="scrapPerPage" name="perPage" class="form-control">
                  <?php foreach ([25, 50, 100] as $pageSize): ?>
                  <option value="<?= $pageSize ?>" <?= (int) $filters["perPage"] === $pageSize
    ? "selected"
    : "" ?>><?= $pageSize ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-sm-3">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-info btn-block">
                  <i class="fa fa-search"></i> Apply
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="table-responsive">
        <table id="scrapped-assets-table" class="table table-striped table-bordered table-hover">
          <thead>
            <tr>
              <th>No.</th>
              <th>Asset Number</th>
              <th>Description</th>
              <th>Employee</th>
              <th>Category</th>
              <th>Qty</th>
              <th>Unit</th>
              <th>Status</th>
              <th>Created By</th>
              <th>Created Date</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $number = (int) ($pagination["offset"] ?? 0);
            foreach ($data["assets"] ?? [] as $asset):
                $number++; ?>
            <tr>
              <td><?= $number ?></td>
              <td>
                <button type="button" class="btn btn-link btn-xs"
                  data-asset-details="<?= htmlspecialchars(
                      (string) $asset["assetNum"],
                      ENT_QUOTES,
                      "UTF-8",
                  ) ?>"><?= htmlspecialchars((string) $asset["assetNum"]) ?></button>
              </td>
              <td><?= htmlspecialchars((string) ($asset["Description"] ?? "-")) ?></td>
              <td><?= htmlspecialchars((string) ($asset["empName"] ?? "-")) ?></td>
              <td><?= htmlspecialchars((string) ($asset["assetKatName"] ?? "-")) ?></td>
              <td><?= htmlspecialchars((string) ($asset["quantity"] ?? "-")) ?></td>
              <td><?= htmlspecialchars((string) ($asset["unit"] ?? "-")) ?></td>
              <td><span class="badge badge-danger"><?= htmlspecialchars(
                  (string) ($asset["assetStatusName"] ?? "Scrapped"),
              ) ?></span></td>
              <td><?= htmlspecialchars((string) ($asset["createdBy"] ?? "-")) ?></td>
              <td><?= htmlspecialchars((string) ($asset["createdDate"] ?? "-")) ?></td>
              <td>
                <?php if (!empty($data["canDeleteScrapped"])): ?>
                <button type="button" class="btn btn-xs btn-danger scrap-delete-btn"
                  data-asset="<?= htmlspecialchars(
                      (string) $asset["assetNum"],
                      ENT_QUOTES,
                      "UTF-8",
                  ) ?>"><i class="ace-icon fa fa-trash-o"></i> Delete</button>
                <?php else: ?>
                <span class="text-muted">View only</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php
            endforeach;
            ?>
            <?php if ($number === (int) ($pagination["offset"] ?? 0)): ?>
            <tr>
              <td colspan="11" class="text-center">No scrapped assets found.</td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="row" style="margin:12px 0">
        <div class="col-sm-6">
          Showing <?= (int) ($pagination["total"] ?? 0) === 0
              ? 0
              : (int) ($pagination["offset"] ?? 0) + 1 ?>
          to <?= min(
              (int) ($pagination["offset"] ?? 0) + count($data["assets"] ?? []),
              (int) ($pagination["total"] ?? 0),
          ) ?> of <?= number_format((int) ($pagination["total"] ?? 0)) ?> assets
        </div>
        <div class="col-sm-6 text-right">
          <?php if ($page > 1): ?>
          <a class="btn btn-default btn-sm"
            href="<?= BASEURL ?>/Asset/disposed?<?= htmlspecialchars(
    http_build_query($queryBase + ["page" => $page - 1]),
    ENT_QUOTES,
    "UTF-8",
) ?>">Previous</a>
          <?php endif; ?>
          <span class="btn btn-light btn-sm disabled">Page <?= $page ?> of <?= $totalPages ?></span>
          <?php if ($page < $totalPages): ?>
          <a class="btn btn-default btn-sm"
            href="<?= BASEURL ?>/Asset/disposed?<?= htmlspecialchars(
    http_build_query($queryBase + ["page" => $page + 1]),
    ENT_QUOTES,
    "UTF-8",
) ?>">Next</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php if (!empty($data["canDeleteScrapped"])): ?>
<div class="modal fade" id="scrapDeleteModal" tabindex="-1" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="<?= BASEURL ?>/Asset/deleteScrapped">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">Permanently Delete Scrapped Asset</h4>
        </div>
        <div class="modal-body">
          <div class="alert alert-danger">
            This removes the asset and its live transactions. A complete audit snapshot is retained.
          </div>
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(
              (string) $data["deleteCsrf"],
              ENT_QUOTES,
              "UTF-8",
          ) ?>">
          <input type="hidden" name="assetNum" id="deleteAssetNum">
          <div class="form-group">
            <label>Asset</label>
            <input id="deleteAssetDisplay" class="form-control" readonly>
          </div>
          <div class="form-group">
            <label>Deletion Reason <span class="red">*</span></label>
            <textarea name="deletionReason" class="form-control" maxlength="500" required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Delete Permanently</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php require __DIR__ . "/../templates/asset_details_modal.php"; ?>
<script>
  (function() {
    <?php if (!empty($data["canDeleteScrapped"])): ?>
    document.addEventListener('click', function(event) {
      var button = event.target.closest('.scrap-delete-btn');
      if (!button) return;
      document.getElementById('deleteAssetNum').value = button.dataset.asset;
      document.getElementById('deleteAssetDisplay').value = button.dataset.asset;
      jQuery('#scrapDeleteModal').modal('show');
    });
    <?php endif; ?>
  })();
</script>
