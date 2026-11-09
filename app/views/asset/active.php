<div class="page-content">
  <div class="container-fluid">

    <?php if (!empty($data["assignmentPdfIds"])):
    $pdfUrl =
        BASEURL .
        "/Asset/downloadAssignmentPdf/" .
        htmlspecialchars($data["assignmentPdfIds"], ENT_QUOTES, "UTF-8"); ?>
    <div class="alert alert-success">
      <i class="fa fa-check-circle"></i> Asset assigned successfully.
      <a class="btn btn-success btn-sm"
        href="<?= htmlspecialchars($pdfUrl, ENT_QUOTES, "UTF-8") ?>">
        <i class="fa fa-file-pdf-o"></i> Download Assignment PDF
      </a>
    </div>
    <iframe src="<?= htmlspecialchars(
    $pdfUrl,
    ENT_QUOTES,
    "UTF-8",
) ?>" title="Assignment PDF download" style="display:none"></iframe>
    <?php
endif; ?>

    <?php
    $filters = $data["filters"] ?? [
        "search" => "",
        "category" => "ALL",
        "assignmentType" => "",
        "perPage" => 50,
    ];
    $pagination = $data["pagination"] ?? [
        "page" => 1,
        "total" => count($data["assets"] ?? []),
        "totalPages" => 1,
        "offset" => 0,
    ];
    // Keep request-workflow references preserved but hide their column while
    // the workflow interface is paused globally.
    $workflowUiEnabled = defined("WORKFLOW_UI_ENABLED") ? WORKFLOW_UI_ENABLED : true;
    $canBulkPrintLabels = (bool) ($data["canBulkPrintLabels"] ?? false);
    $dateTimeBadge = static function ($value): string {
        $timestamp = strtotime((string) $value);
        if ($timestamp === false) {
            return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
        }
        return '<span class="history-datetime history-datetime-assigned">' .
            '<strong><i class="fa fa-calendar-check-o"></i> ' .
            date("Y-m-d", $timestamp) .
            '</strong><small><i class="fa fa-clock-o"></i> ' .
            date("h:i:s A", $timestamp) .
            '</small></span>';
    };
    ?>
    <style>
      .history-datetime {
        display: inline-flex;
        flex-direction: column;
        min-width: 142px;
        padding: 7px 9px;
        border: 1px solid #cfe3dc;
        border-left: 4px solid #27845f;
        border-radius: 5px;
        background: #f1f8f5;
        line-height: 1.25;
        white-space: nowrap
      }

      .history-datetime strong { color: #174e3d; font-size: 12px }
      .history-datetime small { margin-top: 4px; color: #5d6f69; font-size: 11px }
      .history-datetime i { width: 13px; text-align: center }
    </style>
    <form method="get" action="<?= BASEURL ?>/Asset/active" class="row mb-4 align-items-end">
      <div class="col-md-4 col-12 mb-2">
        <label for="asset-search">Search</label>
        <input type="search" name="search" id="asset-search" class="form-control"
          value="<?= htmlspecialchars((string) $filters["search"], ENT_QUOTES, "UTF-8") ?>"
          placeholder="Asset, serial, employee, department, issue, or request prefix">
      </div>
      <div class="col-md-2 col-6 mb-2">
        <label for="assignment-filter">Assignment</label>
        <select name="assignmentType" id="assignment-filter" class="form-control">
          <option value="">All</option>
          <option value="DEPARTMENT" <?= $filters["assignmentType"] === "DEPARTMENT"
              ? "selected"
              : "" ?>>Departments</option>
          <option value="USER" <?= $filters["assignmentType"] === "USER"
              ? "selected"
              : "" ?>>Users</option>
        </select>
      </div>
      <div class="col-md-2 col-6 mb-2">
        <label for="category-filter">Category</label>
        <select name="category" id="category-filter" class="form-control">
          <option value="ALL">All Categories</option><?php foreach (
            $data["categories"]
            as $category
        ): ?><option value="<?= htmlspecialchars($category["assetKat"]) ?>" <?= (string) $filters[
    "category"
] === (string) $category["assetKat"]
    ? "selected"
    : "" ?>><?= htmlspecialchars(
    $category["Description"],
) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2 col-6 mb-2">
        <label for="active-per-page">Rows</label>
        <select name="perPage" id="active-per-page" class="form-control">
          <?php foreach ([25, 50, 100] as $pageSize): ?>
          <option value="<?= $pageSize ?>" <?= (int) $filters["perPage"] === $pageSize
              ? "selected"
              : "" ?>><?= $pageSize ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2 col-6 mb-2">
        <label>&nbsp;</label>
        <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-search"></i>
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
        href="<?= BASEURL ?>/Asset/exportReportExcel/assigned?<?= $exportQuery ?>">
        <i class="fa fa-file-excel-o"></i> Export Excel
      </a>
      <a class="btn btn-danger"
        href="<?= BASEURL ?>/Asset/exportReportPdf/assigned?<?= $exportQuery ?>">
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
      <input type="hidden" name="asset_scope" value="ACTIVE">
      <div class="card-body d-flex flex-wrap align-items-center justify-content-between">
        <div class="mb-2 mr-3">
          <strong><i class="fa fa-tags"></i> Bulk Device Labels</strong>
          <div class="text-muted small">Select assigned assets below. Each item prints on one 70 × 35 mm adhesive label.</div>
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

    <!-- Asset Table Card -->
    <div class="card shadow-sm">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0" id="asset-list-heading">All Assigned Assets</h5>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped table-bordered mb-0" id="asset-table">
            <thead class="thead-light">
              <tr>
                <?php if ($canBulkPrintLabels): ?>
                <th class="text-center" style="width:42px">
                  <input type="checkbox" id="select-all-labels" title="Select all assets on this page"
                    aria-label="Select all assets on this page">
                </th>
                <?php endif; ?>
                <th>No.</th>
                <?php if ($workflowUiEnabled): ?>
                <th>Request No.</th>
                <?php endif; ?>
                <th>Issue No.</th>
                <th>Asset Number(s)</th>
                <th>Serial Number</th>
                <th>Description(s)</th>
                <th>Category per Asset</th>
                <th id="holder-name-heading">Assigned To</th>
                <th>Qty</th>
                <th>Unit</th>
                <th>Created By</th>
                <th><i class="fa fa-calendar-check-o"></i> Assignment Date &amp; Time</th>
                <th>Asset Action</th>
              </tr>
            </thead>
            <tbody id="asset-table-body">
              <?php
                            $no = (int) ($pagination["offset"] ?? 0);

                            foreach ($data["assets"] ?? [] as $asset):

                                $no++;

                                $assetNums = array_values(
                                    array_filter(
                                        array_map(
                                            "trim",
                                            explode(",", (string) ($asset["assetNums"] ?? "")),
                                        ),
                                    ),
                                );
                                $assetDescriptions = explode(
                                    "|||",
                                    (string) ($asset["assetDescriptions"] ?? ""),
                                );
                                $assetSerialNumbers = explode(
                                    "|||",
                                    (string) ($asset["assetSerialNumbers"] ?? ""),
                                );
                                $assetCategories = explode(
                                    "|||",
                                    (string) ($asset["assetCategoryNames"] ?? ""),
                                );
                                $rowspan = max(1, count($assetNums));
                                $issueId = (int) ($asset["issueId"] ?? 0);
                                $requestReferences = [];
                                foreach (
                                    explode(
                                        "|||",
                                        (string) ($asset["requestReferences"] ?? ""),
                                    )
                                    as $requestReference
                                ) {
                                    [$requestId, $requestNo] = array_pad(
                                        explode("::", $requestReference, 2),
                                        2,
                                        "",
                                    );
                                    if ((int) $requestId > 0 && $requestNo !== "") {
                                        $requestReferences[] = [
                                            "id" => (int) $requestId,
                                            "number" => $requestNo,
                                        ];
                                    }
                                }

                                $assignmentType = $asset["assignmentType"] ?? "";

                                if ($assignmentType === "DEPARTMENT") {
                                    $holderId = $asset["deptId"] ?? "-";

                                    $departmentName = trim((string) ($asset["deptName"] ?? ""));
                                    $holderName = $departmentName !== "" ? $departmentName : "-";
                                } else {
                                    $holderId = $asset["empId"] ?? "-";
                                    $holderName = $asset["empName"] ?? "-";
                                }
                                ?>
              <?php foreach ($assetNums as $assetIndex => $assetNum): ?>
              <tr class="asset-row" data-type="<?= htmlspecialchars(
                                $asset["assignmentType"] ?? "",
                            ) ?>" data-categories=",<?= htmlspecialchars(
    $asset["assetCategoryCodes"] ?? "",
) ?>," data-search="<?= htmlspecialchars(
    strtolower(
        implode(" ", [
            $asset["requestNo"] ?? "Manual Assign",
            $asset["issueNo"] ?? "",
            $asset["assetNums"] ?? "",
            $asset["assetSerialNumbers"] ?? "",
            $asset["assetDescriptions"] ?? "",
            $asset["assetCategoryNames"] ?? "",
            $holderName,
            $asset["createdBy"] ?? "",
            $asset["issueDate"] ?? "",
        ]),
    ),
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
                <?php if (
                                    $assetIndex === 0
                                ): ?><td rowspan="<?= $rowspan ?>" class="group-cell"><?= $no ?>
                </td>
                <?php if ($workflowUiEnabled): ?>
                <td rowspan="<?= $rowspan ?>" class="group-cell request-number-cell">
                  <?php if ($requestReferences): ?>
                  <?php foreach ($requestReferences as $requestReference): ?>
                  <a class="label label-info"
                    href="<?= BASEURL ?>/Request/details/<?= $requestReference["id"] ?>"
                    title="View request details and full approval trail">
                    <i class="fa fa-history"></i>
                    <?= htmlspecialchars($requestReference["number"]) ?>
                  </a>
                  <?php endforeach; ?>
                  <?php else: ?>
                  <span class="label label-default">Manual Assign</span>
                  <?php endif; ?>
                </td>
                <?php endif; ?>
                <td rowspan="<?= $rowspan ?>" class="group-cell issue-number-cell"><span
                    class="issue-number-box"><?= htmlspecialchars(
    $asset["issueNo"] ?? "-",
) ?></span><?php if (
    !empty($asset["issueId"])
): ?><div class="issue-transaction-actions"><a class="btn btn-warning btn-sm" href="<?= BASEURL ?>/Asset/returns/<?= htmlspecialchars(
    $asset["openBatchIssueIds"] ?? (string) (int) $asset["issueId"],
    ENT_QUOTES,
    "UTF-8",
) ?>" title="Return Assignment"><i class="fa fa-reply"></i></a><a class="btn btn-danger btn-sm"
                      href="<?= BASEURL ?>/Asset/downloadAssignmentPdf/<?= htmlspecialchars(
    $asset["batchIssueIds"] ?? (string) (int) $asset["issueId"],
    ENT_QUOTES,
    "UTF-8",
) ?>" title="Assignment PDF"><i class="fa fa-file-pdf-o"></i></a></div><?php endif; ?></td>
                <?php endif; ?>
                <td><button type="button" class="btn btn-link btn-xs" data-asset-details="<?= htmlspecialchars(
                    $assetNum,
                    ENT_QUOTES,
                    "UTF-8",
                ) ?>"><strong><?= htmlspecialchars($assetNum) ?></strong></button></td>
                <td><?= htmlspecialchars(
                                    $assetSerialNumbers[$assetIndex] ?? "-",
                                ) ?></td>
                <td><?= htmlspecialchars(
                                    $assetDescriptions[$assetIndex] ?? "-",
                                ) ?></td>
                <td><?= htmlspecialchars(
                                     $assetCategories[$assetIndex] ?? "-",
                                 ) ?></td>
                <?php if ($assetIndex === 0): ?>
                <td rowspan="<?= $rowspan ?>" class="group-cell"><?= htmlspecialchars(
    $holderName,
) ?></td>
                <td rowspan="<?= $rowspan ?>" class="group-cell"><?= htmlspecialchars(
    $asset["quantity"],
) ?></td>
                <td rowspan="<?= $rowspan ?>" class="group-cell"><?= htmlspecialchars(
    $asset["unit"],
) ?></td>
                <td rowspan="<?= $rowspan ?>" class="group-cell"><?= htmlspecialchars(
    $asset["createdBy"],
) ?></td>
                <td rowspan="<?= $rowspan ?>" class="group-cell"><?= $dateTimeBadge(
    $asset["issueDate"] ?? "",
) ?></td>
                <?php endif; ?>
                <td>
                  <div class="btn-group btn-group-xs">
                    <?php if (false): // Redundant eye action retained for possible future use; asset number opens the modal. ?>
                    <button type="button" class="btn btn-info asset-detail-link" data-asset="<?= htmlspecialchars(
                                      $assetNum,
                                      ENT_QUOTES,
                                      "UTF-8",
                                  ) ?>" title="View Asset"><i class="fa fa-eye"></i></button>
                    <?php endif; ?>
                    <a class="btn btn-info" target="_blank" href="<?= BASEURL ?>/Asset/printLabel/<?= rawurlencode(
    $assetNum,
) ?>" title="QR Sticker"><i class="fa fa-tag"></i></a>
                    <a class="btn btn-default" target="_blank" href="<?= BASEURL ?>/Asset/printHistory/<?= rawurlencode(
    $assetNum,
) ?>" title="Asset History"><i class="fa fa-print"></i></a>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php
                            endforeach;
                            ?>
              <?php if ($no === 0): ?>
              <tr>
                  <td colspan="<?= ($workflowUiEnabled ? 13 : 12) +
                     ($canBulkPrintLabels ? 1 : 0) ?>" class="text-center">
                  No active assets found.
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
            ) ?> of <?= number_format((int) ($pagination["total"] ?? 0)) ?> assignments
          </div>
          <div class="col-sm-6 text-right">
            <?php
            $page = (int) ($pagination["page"] ?? 1);
            $totalPages = (int) ($pagination["totalPages"] ?? 1);
            $queryBase = [
                "search" => $filters["search"],
                "category" => $filters["category"],
                "assignmentType" => $filters["assignmentType"],
                "perPage" => $filters["perPage"],
            ];
            ?>
            <?php if ($page > 1): ?><a class="btn btn-default btn-sm"
              href="<?= BASEURL ?>/Asset/active?<?= htmlspecialchars(
    http_build_query($queryBase + ["page" => $page - 1]),
    ENT_QUOTES,
    "UTF-8",
) ?>">Previous</a><?php endif; ?>
            <span class="btn btn-light btn-sm disabled">Page <?= $page ?> of <?= $totalPages ?></span>
            <?php if ($page < $totalPages): ?><a class="btn btn-default btn-sm"
              href="<?= BASEURL ?>/Asset/active?<?= htmlspecialchars(
    http_build_query($queryBase + ["page" => $page + 1]),
    ENT_QUOTES,
    "UTF-8",
) ?>">Next</a><?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Asset List Modal -->
    <!-- Asset Details Modal -->
    <!-- <div class="modal fade" id="assetDetailModal" tabindex="-1" role="dialog" aria-labelledby="assetDetailModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="assetDetailModalLabel">Asset Details</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" id="asset-detail"></div>
                    <div class="modal-footer">
                        <button type="button" id="btn-dtl-clsd" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div> -->

  </div> <!-- container-fluid -->
</div>

<style>
  #asset-table .group-cell {
    vertical-align: middle;
    text-align: center
  }

  #asset-table .issue-number-cell {
    min-width: 145px
  }

  #asset-table .issue-number-box {
    display: inline-block;
    max-width: 145px;
    padding: 7px 9px;
    border: 1px solid #0b7769;
    border-radius: 5px;
    background: #eef8f6;
    color: #075b50;
    font-weight: 700;
    line-height: 1.35;
    overflow-wrap: anywhere
  }

  #asset-table .issue-transaction-actions {
    display: flex;
    justify-content: center;
    gap: 5px;
    margin-top: 8px
  }

  #asset-table th:last-child,
  #asset-table td:last-child {
    text-align: center;
    white-space: nowrap
  }
</style>
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
