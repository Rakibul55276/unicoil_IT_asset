<div class="page-content">
  <div class="container-fluid">
    <div class="widget-box">
      <div class="widget-header">
        <h4 class="widget-title"><i class="fa fa-archive"></i> Scrapped Asset Archive</h4>
      </div>
      <div class="widget-body">
        <div class="widget-main">
          <div class="alert alert-info">Archive records remain available after a scrapped asset is
            permanently deleted. Asset, transaction, and image metadata snapshots are retained for
            audit purposes.</div>
          <form method="get" action="<?= BASEURL ?>/Asset/archive" class="form-inline">
            <div class="form-group"><label>From&nbsp;</label><input type="date" name="from"
                class="form-control" value="<?= htmlspecialchars(
          $data["from"],
      ) ?>" required></div>
            <div class="form-group"><label>&nbsp;To&nbsp;</label><input type="date" name="to"
                class="form-control" value="<?= htmlspecialchars(
          $data["to"],
      ) ?>" required></div>
            <div class="form-group"><label>&nbsp;Category&nbsp;</label><select name="category"
                class="form-control searchable-select" data-placeholder="All categories">
                <option value="">All categories</option><?php foreach (
          $data["categories"]
          as $category
      ): ?><option value="<?= htmlspecialchars(
    $category["Description"],
    ENT_QUOTES,
    "UTF-8",
) ?>" <?= $data["category"] === $category["Description"] ? "selected" : "" ?>><?= htmlspecialchars(
    $category["Description"],
) ?></option><?php endforeach; ?>
              </select></div>
            <button class="btn btn-primary" type="submit"><i class="fa fa-filter"></i> Apply
              Filter</button>
            <?php $query =
          "from=" .
          urlencode($data["from"]) .
          "&to=" .
          urlencode($data["to"]) .
          "&category=" .
          urlencode($data["category"]); ?>
            <a class="btn btn-success"
              href="<?= BASEURL ?>/Asset/exportArchiveExcel?<?= $query ?>"><i
                class="fa fa-file-excel-o"></i> Export Excel</a>
            <a class="btn btn-danger" href="<?= BASEURL ?>/Asset/exportArchivePdf?<?= $query ?>"><i
                class="fa fa-file-pdf-o"></i> Export PDF</a>
          </form>
          <p class="help-block"><?= count($data["rows"]) ?> archived scrapped asset(s) found.</p>
        </div>
      </div>
    </div>
    <div class="space-12"></div>
    <div class="table-responsive">
      <table class="table table-bordered table-striped table-hover">
        <thead>
          <tr>
            <th>Photo</th>
            <th>Archive ID</th>
            <th>Archived Date</th>
            <th>Asset Number</th>
            <th>Description</th>
            <th>Category</th>
            <th>Serial</th>
            <th>Deleted By</th>
            <th>Reason</th>
            <th>Transactions</th>
            <th>Images</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($data["rows"] as $r):
      $image = !empty($r["assetImage"])
          ? BASEURL . "/Asset/archivedMedia/" . rawurlencode($r["assetImage"])
          : BASEURL . "/public/img/no-image.png"; ?><tr>
            <td><img src="<?= htmlspecialchars(
    $image,
    ENT_QUOTES,
    "UTF-8",
) ?>" onerror="this.onerror=null;this.src='<?= BASEURL ?>/public/img/no-image.png';" alt=""
                style="width:70px;height:52px;object-fit:cover;border:1px solid #ddd"></td>
            <td>ARC-<?= (int) $r[
    "deletion_id"
] ?></td>
            <td><?= htmlspecialchars($r["deleted_at"]) ?></td>
            <td><button type="button" class="btn btn-link btn-xs asset-detail-link"
                data-asset="<?= htmlspecialchars(
                    $r["assetNum"],
                    ENT_QUOTES,
                    "UTF-8",
                ) ?>"><strong><?= htmlspecialchars($r["assetNum"]) ?></strong></button></td>
            <td><?= htmlspecialchars($r["assetDescription"]) ?></td>
            <td><?= htmlspecialchars(
    $r["category"] ?? "-",
) ?></td>
            <td><?= htmlspecialchars($r["serial_number"] ?? "-") ?></td>
            <td><?= htmlspecialchars(
    $r["deletedBy"],
) ?></td>
            <td><?= htmlspecialchars($r["deletionReason"]) ?></td>
            <td><span class="badge"><?= (int) $r[
    "transactionCount"
] ?></span></td>
            <td><span class="badge"><?= (int) $r["mediaCount"] ?></span></td>
          </tr><?php
  endforeach; ?>
          <?php if (
      !$data["rows"]
  ): ?><tr>
            <td colspan="11" class="text-center">No archived scrapped assets in the selected period.
            </td>
          </tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
