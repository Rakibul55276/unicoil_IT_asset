<div class="page-content">
  <div class="container-fluid">

    <?php
    $filters = $data["filters"] ?? [
        "search" => "",
        "category" => "ALL",
        "condition" => "ALL",
        "perPage" => 50,
    ];
    $pagination = $data["pagination"] ?? [
        "page" => 1,
        "perPage" => 50,
        "total" => count($data["assets"] ?? []),
        "totalPages" => 1,
        "offset" => 0,
    ];
    $inventoryRoles = $_SESSION["usr"]["roles"] ?? [];
    $inventoryUsername = strtolower(
        trim((string) ($_SESSION["usr"]["user"] ?? "")),
    );
    $canOperateInventory =
        $inventoryUsername === "admin" ||
        (bool) array_intersect(
            $inventoryRoles,
            ["ADMINISTRATOR", "ISSUER"],
        );
    $canBulkPrintLabels = (bool) ($data["canBulkPrintLabels"] ?? false);
    $conditionLabelClass = static function (string $condition): string {
        return [
            "NEW" => "label-primary",
            "GOOD" => "label-success",
            "DAMAGED" => "label-danger",
        ][strtoupper($condition)] ?? "label-default";
    };
    ?>

    <!-- Server-side filtering keeps browser memory stable for large inventories. -->
    <form method="get" action="<?= BASEURL ?>/Asset/inactive" class="row mb-4 align-items-end">
      <div class="col-md-4 col-12 mb-2">
        <label for="asset-search">Search</label>
        <input type="search" name="search" id="asset-search" class="form-control"
          value="<?= htmlspecialchars((string) $filters["search"], ENT_QUOTES, "UTF-8") ?>"
          placeholder="Asset number, serial number, or description prefix" autocomplete="off">
      </div>

      <div class="col-md-2 col-sm-6 col-12 mb-2">
        <label for="category-filter">Category</label>
        <select name="category" id="category-filter" class="form-control">
          <option value="ALL">
            All Categories
          </option>

          <?php foreach ($data["categories"] ?? [] as $category): ?>
          <option value="<?= htmlspecialchars(
                                (string) ($category["assetKat"] ?? ""),
                                ENT_QUOTES,
                                "UTF-8",
                            ) ?>" <?= (string) $filters["category"] ===
    (string) ($category["assetKat"] ?? "")
    ? "selected"
    : "" ?>>
            <?= htmlspecialchars(
                                (string) ($category["Description"] ?? "-"),
                                ENT_QUOTES,
                                "UTF-8",
                            ) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-2 col-sm-6 col-12 mb-2">
        <label for="condition-filter">Condition</label>
        <select name="condition" id="condition-filter" class="form-control">
          <option value="ALL">All Conditions</option>
          <?php foreach (["NEW" => "New", "GOOD" => "Good", "DAMAGED" => "Damaged"] as $conditionValue => $conditionLabel): ?>
          <option value="<?= $conditionValue ?>" <?= (string) ($filters["condition"] ?? "ALL") === $conditionValue
              ? "selected"
              : "" ?>><?= htmlspecialchars($conditionLabel) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-2 col-6 mb-2">
        <label for="per-page">Rows</label>
        <select name="perPage" id="per-page" class="form-control">
          <?php foreach ([25, 50, 100] as $pageSize): ?>
          <option value="<?= $pageSize ?>" <?= (int) $filters["perPage"] === $pageSize
    ? "selected"
    : "" ?>><?= $pageSize ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2 col-6 mb-2">
        <label class="hidden-sm hidden-xs" aria-hidden="true">&nbsp;</label>
        <button type="submit" class="btn btn-primary btn-block filter-submit-btn"><i class="fa fa-search"></i>
          Apply</button>
      </div>
    </form>

    <?php
    $exportFilters = $filters;
    unset($exportFilters["page"]);
    $exportQuery = http_build_query($exportFilters);
    ?>
    <!-- <div class="btn-group" style="margin-bottom:15px">
      <a class="btn btn-success"
        href="<?= BASEURL ?>/Asset/exportReportExcel/instock?<?= $exportQuery ?>">
        <i class="fa fa-file-excel-o"></i> Export Excel
      </a>
      <a class="btn btn-danger"
        href="<?= BASEURL ?>/Asset/exportReportPdf/instock?<?= $exportQuery ?>">
        <i class="fa fa-file-pdf-o"></i> Export PDF
      </a>
    </div> -->

    <!-- Flash Messages -->
    <div class="row mb-3">
      <div class="col-12" id="msg-info">
        <?php Flasher::msgInfo(); ?>
      </div>
    </div>

    <?php if ($canBulkPrintLabels): ?>
    <form id="bulk-label-form" method="post" target="_blank"
      action="<?= BASEURL ?>/Asset/printBulkLabels" class="card shadow-sm mb-3">
      <input type="hidden" name="csrf_token"
        value="<?= htmlspecialchars((string) ($data["bulkLabelCsrf"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
      <input type="hidden" name="asset_scope" value="IN_STOCK">
      <div class="card-body d-flex flex-wrap align-items-center justify-content-between">
        <div class="mb-2 mr-3">
          <strong><i class="fa fa-tags"></i> Bulk Device Labels</strong>
          <div class="text-muted small">Select in-stock assets below. Each item prints on one 70 × 35 mm adhesive label.</div>
        </div>
        <div class="mb-2">
          <button type="button" class="btn btn-default btn-sm" id="clear-label-selection" disabled>
            <i class="fa fa-times"></i> Clear
          </button>
          <button type="submit" class="btn btn-success" id="print-selected-labels" disabled>
            <i class="fa fa-print"></i> Print Selected Labels
            <span class="badge badge-light" id="selected-label-count">0</span>
          </button>
        </div>
      </div>
    </form>
    <?php endif; ?>

    <!-- Stock Asset Table -->
    <div class="card shadow-sm">

      <div class="card-header bg-primary text-white">
        <h5 class="mb-0" id="stock-asset-heading">
          Stock Assets
        </h5>
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">

          <table class="table table-striped table-bordered table-hover mb-0" id="asset-table">
            <thead class="thead-light">
              <tr>
                <?php if ($canBulkPrintLabels): ?>
                <th class="text-center" style="width:42px">
                  <input type="checkbox" id="select-all-labels" title="Select all assets on this page"
                    aria-label="Select all assets on this page">
                </th>
                <?php endif; ?>
                <th>No.</th>
                <th>Asset Number</th>
                <th>Serial Number</th>
                <th>Description</th>
                <th>Category</th>
                <th>Location</th>
                <th>Quantity</th>
                <th>Unit</th>
                <th>Condition</th>
                <th>Stock Status</th>
                <th>Created By</th>
                <th>Created Date</th>
                <th>Action</th>
              </tr>
            </thead>

            <tbody id="asset-table-body">

              <?php
                            $no = (int) ($pagination["offset"] ?? 0);

                            foreach ($data["assets"] ?? [] as $asset):

                                $no++;

                                $assetNum = trim((string) ($asset["assetNum"] ?? ""));

                                $categoryCode = (string) ($asset["assetKat"] ?? "");
                                ?>

              <tr class="asset-row" data-category="<?= htmlspecialchars(
                                        $categoryCode,
                                        ENT_QUOTES,
                                        "UTF-8",
                                    ) ?>">
                <?php if ($canBulkPrintLabels): ?>
                <td class="text-center">
                  <input type="checkbox" class="asset-label-checkbox" form="bulk-label-form"
                    name="assetNums[]" value="<?= htmlspecialchars($assetNum, ENT_QUOTES, "UTF-8") ?>"
                    aria-label="Select label for asset <?= htmlspecialchars($assetNum, ENT_QUOTES, "UTF-8") ?>">
                </td>
                <?php endif; ?>
                <td>
                  <?= $no ?>
                </td>

                <!-- Asset Number -->
                <td>
                  <?php if ($assetNum !== ""): ?>
                  <button type="button" class="btn btn-link btn-xs asset-detail-link" data-asset="<?= htmlspecialchars(
                                                    $assetNum,
                                                    ENT_QUOTES,
                                                    "UTF-8",
                                                ) ?>" title="View asset details">
                    <?= htmlspecialchars(
                                                    $assetNum,
                                                    ENT_QUOTES,
                                                    "UTF-8",
                                                ) ?>
                  </button>
                  <?php else: ?>
                  -
                  <?php endif; ?>
                </td>

                <!-- Serial Number -->
                <td>
                  <?= htmlspecialchars(
                                            (string) ($asset["serial_number"] ?? "-"),
                                            ENT_QUOTES,
                                            "UTF-8",
                                        ) ?>
                </td>

                <!-- Description -->
                <td>
                  <?= htmlspecialchars(
                                            (string) ($asset["Description"] ?? "-"),
                                            ENT_QUOTES,
                                            "UTF-8",
                                        ) ?>
                </td>

                <!-- Category -->
                <td>
                  <?= htmlspecialchars(
                                            (string) ($asset["assetKatName"] ?? "-"),
                                            ENT_QUOTES,
                                            "UTF-8",
                                        ) ?>
                </td>

                <!-- Location -->
                <td>
                  <?= htmlspecialchars(
                                            (string) ($asset["locationName"] ??
                                                ($asset["location"] ?? "-")),
                                            ENT_QUOTES,
                                            "UTF-8",
                                        ) ?>
                </td>

                <!-- Quantity -->
                <td>
                  <?= htmlspecialchars(
                                            (string) ($asset["quantity"] ?? "-"),
                                            ENT_QUOTES,
                                            "UTF-8",
                                        ) ?>
                </td>

                <!-- Unit -->
                <td>
                  <?= htmlspecialchars(
                                            (string) ($asset["unit"] ?? "-"),
                                            ENT_QUOTES,
                                            "UTF-8",
                                        ) ?>
                </td>

                <!-- Current global asset condition -->
                <?php
                $currentCondition = strtoupper(
                    trim((string) ($asset["asset_condition"] ?? "NEW")),
                );
                ?>
                <td>
                  <span class="label <?= $conditionLabelClass($currentCondition) ?>">
                    <?= htmlspecialchars(
                        ucwords(strtolower(str_replace("_", " ", $currentCondition))),
                        ENT_QUOTES,
                        "UTF-8",
                    ) ?>
                  </span>
                </td>

                <!-- Status -->
                <td>
                  <span class="badge badge-warning">
                    <?= htmlspecialchars(
                                                (string) ($asset["assetStatusName"] ?? "-"),
                                                ENT_QUOTES,
                                                "UTF-8",
                                            ) ?>
                  </span>
                </td>

                <!-- Created By -->
                <td>
                  <?= htmlspecialchars(
                                            (string) ($asset["createdBy"] ?? "-"),
                                            ENT_QUOTES,
                                            "UTF-8",
                                        ) ?>
                </td>

                <!-- Created Date -->
                <td>
                  <?= htmlspecialchars(
                                            (string) ($asset["createdDate"] ?? "-"),
                                            ENT_QUOTES,
                                            "UTF-8",
                                        ) ?>
                </td>

                <!-- Action -->
                <td class="text-nowrap">

                  <!-- Shared Asset Detail Modal -->
                  <?php if (false): // Redundant eye action retained for possible future use; asset number opens the modal. ?>
                  <button type="button" class="btn btn-info btn-sm asset-detail-link" data-asset="<?= htmlspecialchars(
                                                $assetNum,
                                                ENT_QUOTES,
                                                "UTF-8",
                                            ) ?>" title="View Asset Details"
                    <?= $assetNum === "" ? "disabled" : "" ?>>
                    <i class="fa fa-eye"></i>
                  </button>
                  <?php endif; ?>

                  <?php if ($canOperateInventory): ?>
                  <!-- Physical inventory edits are limited to Issuer/Administrator. -->
                  <a class="btn btn-success btn-sm" href="<?= BASEURL ?>/Asset/editEntry/<?= rawurlencode(
    $assetNum,
) ?>" title="Edit Asset">
                    <i class="fa fa-pencil"></i>
                  </a>
                  <?php endif; ?>
                  <a class="btn btn-info btn-sm" target="_blank" href="<?= BASEURL ?>/Asset/printLabel/<?= rawurlencode(
    $assetNum,
) ?>" title="Print Asset QR Sticker"><i class="fa fa-tag"></i></a>
                  <a class="btn btn-default btn-sm" target="_blank" href="<?= BASEURL ?>/Asset/printHistory/<?= rawurlencode(
    $assetNum,
) ?>" title="Print Full Transaction History"><i class="fa fa-print"></i></a>

                </td>
              </tr>

              <?php
                            endforeach;
                            ?>

              <?php if ($no === 0): ?>
              <tr id="initial-empty-row">
                <td colspan="<?= $canBulkPrintLabels ? 15 : 14 ?>" class="text-center">
                  No stock assets found.
                </td>
              </tr>
              <?php endif; ?>

            </tbody>
          </table>

        </div>
      </div>
      <div class="card-footer">
        <div class="row">
          <div class="col-sm-6">
            Showing <?= (int) ($pagination["total"] ?? 0) === 0
                ? 0
                : (int) ($pagination["offset"] ?? 0) + 1 ?>
            to <?= min(
                (int) ($pagination["offset"] ?? 0) + count($data["assets"] ?? []),
                (int) ($pagination["total"] ?? 0),
            ) ?>
            of <?= number_format((int) ($pagination["total"] ?? 0)) ?> assets
          </div>
          <div class="col-sm-6 text-right">
            <?php
            $page = (int) ($pagination["page"] ?? 1);
            $totalPages = (int) ($pagination["totalPages"] ?? 1);
            $queryBase = [
                "search" => $filters["search"],
                "category" => $filters["category"],
                "condition" => $filters["condition"],
                "perPage" => $filters["perPage"],
            ];
            ?>
            <?php if ($page > 1): ?>
            <a class="btn btn-default btn-sm"
              href="<?= BASEURL ?>/Asset/inactive?<?= htmlspecialchars(
    http_build_query($queryBase + ["page" => $page - 1]),
    ENT_QUOTES,
    "UTF-8",
) ?>"><i class="fa fa-chevron-left"></i> Previous</a>
            <?php endif; ?>
            <span class="btn btn-light btn-sm disabled">Page <?= $page ?> of <?= $totalPages ?></span>
            <?php if ($page < $totalPages): ?>
            <a class="btn btn-default btn-sm"
              href="<?= BASEURL ?>/Asset/inactive?<?= htmlspecialchars(
    http_build_query($queryBase + ["page" => $page + 1]),
    ENT_QUOTES,
    "UTF-8",
) ?>">Next <i class="fa fa-chevron-right"></i></a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- Existing Shared Asset Details Modal -->
<?php require __DIR__ . "/../templates/asset_details_modal.php"; ?>

<?php if ($canBulkPrintLabels): ?>
<script>
(function () {
  var boxes = Array.prototype.slice.call(document.querySelectorAll('.asset-label-checkbox'));
  var selectAll = document.getElementById('select-all-labels');
  var clearButton = document.getElementById('clear-label-selection');
  var printButton = document.getElementById('print-selected-labels');
  var countBadge = document.getElementById('selected-label-count');

  function refreshSelection() {
    var selected = boxes.filter(function (box) { return box.checked; }).length;
    countBadge.textContent = String(selected);
    printButton.disabled = selected === 0;
    clearButton.disabled = selected === 0;
    if (selectAll) {
      selectAll.checked = boxes.length > 0 && selected === boxes.length;
      selectAll.indeterminate = selected > 0 && selected < boxes.length;
    }
  }

  boxes.forEach(function (box) {
    box.addEventListener('change', refreshSelection);
  });
  if (selectAll) {
    selectAll.addEventListener('change', function () {
      boxes.forEach(function (box) { box.checked = selectAll.checked; });
      refreshSelection();
    });
  }
  clearButton.addEventListener('click', function () {
    boxes.forEach(function (box) { box.checked = false; });
    refreshSelection();
  });
  document.getElementById('bulk-label-form').addEventListener('submit', function (event) {
    if (!boxes.some(function (box) { return box.checked; })) {
      event.preventDefault();
    }
  });
  refreshSelection();
})();
</script>
<?php endif; ?>
