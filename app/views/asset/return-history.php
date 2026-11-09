<?php
$f = $data["filters"];
$p = $data["pagination"] ?? [
    "page" => 1,
    "total" => count($data["rows"] ?? []),
    "totalPages" => 1,
    "offset" => 0,
];
$e = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
};
$dateTimeBadge = static function ($value): string {
    $timestamp = strtotime((string) $value);
    if ($timestamp === false) {
        return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
    }
    return '<span class="history-datetime history-datetime-return">' .
        '<strong><i class="fa fa-calendar"></i> ' .
        date("Y-m-d", $timestamp) .
        '</strong><small><i class="fa fa-clock-o"></i> ' .
        date("h:i:s A", $timestamp) .
        '</small></span>';
};
$sortLabels = [
    "date_desc" => "Return Date - Newest",
    "date_asc" => "Return Date - Oldest",
    "category_asc" => "Category A-Z",
    "category_desc" => "Category Z-A",
    "condition_asc" => "Condition A-Z",
    "condition_desc" => "Condition Z-A",
    "type_asc" => "Return Type A-Z",
    "type_desc" => "Return Type Z-A",
    "asset_asc" => "Asset Number A-Z",
    "asset_desc" => "Asset Number Z-A",
];
$conditionButtonClass = static function ($condition) {
    return [
        "GOOD" => "btn-success",
        "DAMAGED" => "btn-warning",
    ][strtoupper((string) $condition)] ?? "btn-default";
};
$sortableHeader = static function (
    string $label,
    string $ascending,
    string $descending,
) use ($f, $e): string {
    $current = (string) ($f["sort"] ?? "date_desc");
    $next = $current === $ascending ? $descending : $ascending;
    $query = $f;
    $query["sort"] = $next;
    $query["page"] = 1;
    $icon = $current === $ascending
        ? "fa-sort-asc"
        : ($current === $descending ? "fa-sort-desc" : "fa-sort");
    return '<a href="' . BASEURL . '/Asset/returnHistory?' .
        $e(http_build_query($query)) . '" class="return-sort-link">' .
        $e($label) . ' <i class="fa ' . $icon . '"></i></a>';
};
?>
<div class="page-content">
  <div class="container-fluid">
    <style>
      .history-datetime {
        display: inline-flex;
        flex-direction: column;
        min-width: 142px;
        padding: 7px 9px;
        border: 1px solid #ead9bd;
        border-left: 4px solid #d4811f;
        border-radius: 5px;
        background: #fff8ed;
        line-height: 1.25;
        white-space: nowrap
      }

      .history-datetime strong { color: #74460d; font-size: 12px }
      .history-datetime small { margin-top: 4px; color: #78684f; font-size: 11px }
      .history-datetime i { width: 13px; text-align: center }
    </style>
    <div class="widget-box">
      <div class="widget-header">
        <h4 class="widget-title"><i class="fa fa-filter"></i> Search, Filter and Sort Returns</h4>
      </div>
      <div class="widget-body">
        <div class="widget-main">
          <form method="get" action="<?= BASEURL ?>/Asset/returnHistory">
            <div class="row">
              <div class="col-md-4 form-group"><label for="returnHistorySearch">Asset Number or
                  Description</label><input id="returnHistorySearch" class="form-control"
                  name="search" maxlength="100" value="<?= $e(
              $f["search"],
          ) ?>" placeholder="Search asset number, description, or serial"></div>
              <div class="col-md-2 form-group"><label
                  for="returnHistoryCategory">Category</label><select id="returnHistoryCategory"
                  class="form-control searchable-select" name="category"
                  data-placeholder="All categories">
                  <option value="">All Categories</option><?php foreach (
              $data["categories"]
              as $category
          ): ?><option value="<?= $e($category["assetKat"]) ?>" <?= $f["category"] ===
$category["assetKat"]
    ? "selected"
    : "" ?>><?= $e($category["Description"]) ?></option><?php endforeach; ?>
                </select></div>
              <div class="col-md-2 form-group"><label for="returnHistoryCondition">Asset
                  Condition</label><select id="returnHistoryCondition" class="form-control"
                  name="condition">
                  <option value="">All Conditions</option><?php foreach (
              $data["conditions"]
              as $condition
          ): ?><option value="<?= $e($condition) ?>" <?= $f["condition"] === $condition
    ? "selected"
    : "" ?>><?= $e(str_replace("_", " ", $condition)) ?></option><?php endforeach; ?>
                </select></div>
              <div class="col-md-2 form-group"><label for="returnHistoryType">Return
                  Type</label><select id="returnHistoryType" class="form-control" name="returnType">
                  <option value="">All Types</option><?php foreach (
              $data["returnTypes"]
              as $type
          ): ?><option value="<?= $e($type) ?>" <?= $f["returnType"] === $type
    ? "selected"
    : "" ?>><?= $e(
    $type === "REISSUE" ? "Re-issue" : "Scrap",
) ?></option><?php endforeach; ?>
                </select></div>
              <div class="col-md-2 form-group"><label for="returnHistorySort">Sort By</label><select
                  id="returnHistorySort" class="form-control" name="sort"><?php foreach (
              $sortLabels
              as $value => $label
          ): ?><option value="<?= $e($value) ?>" <?= $f["sort"] === $value
    ? "selected"
    : "" ?>><?= $e($label) ?></option><?php endforeach; ?></select></div>
              <div class="col-md-2 form-group"><label for="returnHistoryPerPage">Rows</label>
                <select id="returnHistoryPerPage" class="form-control" name="perPage">
                  <?php foreach ([25, 50, 100] as $pageSize): ?>
                  <option value="<?= $pageSize ?>" <?= (int) ($f["perPage"] ?? 50) === $pageSize
                      ? "selected"
                      : "" ?>><?= $pageSize ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i>
              Apply</button>
            <a class="btn btn-warning" href="<?= BASEURL ?>/Asset/returnHistory"><i
                class="fa fa-refresh"></i> Reset Filters</a>
            <?php $exportQuery = http_build_query($f); ?>
            <!-- <a class="btn btn-success"
              href="<?= BASEURL ?>/Asset/exportReportExcel/returns?<?= $exportQuery ?>"><i
                class="fa fa-file-excel-o"></i> Export Excel</a>
            <a class="btn btn-danger"
              href="<?= BASEURL ?>/Asset/exportReportPdf/returns?<?= $exportQuery ?>"><i
                class="fa fa-file-pdf-o"></i> Export PDF</a> -->
            <span class="label label-info" style="margin-left:8px"><?= number_format(
                (int) ($p["total"] ?? 0),
            ) ?> return record<?= (int) ($p["total"] ?? 0) === 1 ? "" : "s" ?></span>
          </form>
        </div>
      </div>
    </div>

    <div class="space-12"></div>
    <div class="widget-box">
      <div class="widget-header">
        <h4 class="widget-title"><i class="fa fa-history"></i> Complete Return History</h4>
      </div>
      <div class="widget-body">
        <?php if (
        !$data["rows"]
    ): ?><div class="alert alert-info" style="margin:12px">No return records match the selected
          filters.</div><?php endif; ?>
        <div class="table-responsive">
          <table id="dynamic-table" class="table table-striped table-bordered table-hover">
            <thead>
              <tr>
                <th>No.</th>
                <th><?= $sortableHeader("Return No.", "reference_asc", "reference_desc") ?></th>
                <th><i class="fa fa-calendar"></i> <?= $sortableHeader("Return Date & Time", "date_asc", "date_desc") ?></th>
                <th><?= $sortableHeader("Asset Number", "asset_asc", "asset_desc") ?></th>
                <th><?= $sortableHeader("Asset Name / Return Condition", "description_asc", "description_desc") ?></th>
                <th><?= $sortableHeader("Category", "category_asc", "category_desc") ?></th>
                <th><?= $sortableHeader("Custodian", "custodian_asc", "custodian_desc") ?></th>
                <th><?= $sortableHeader("Remarks", "remarks_asc", "remarks_desc") ?></th>
                <th><?= $sortableHeader("Received By", "receiver_asc", "receiver_desc") ?></th>
              </tr>
            </thead>
            <tbody><?php foreach ($data["rows"] as $index => $row):
          $custodian = trim((string) ($row["custodian"] ?? ($row["acknowledged_by"] ?? "")));
          if (($row["assignee_type"] ?? "") === "DEPARTMENT") {
              $custodian = trim((string) ($row["deptName"] ?? ""));
          }
          $assetNums = explode("|||", (string) ($row["assetNum"] ?? ""));
          $conditions = explode("|||", (string) ($row["condition_in"] ?? ""));
          $descriptions = explode("|||", (string) ($row["assetDescription"] ?? ""));
          $categories = explode("|||", (string) ($row["categoryName"] ?? ""));
          $rowspan = max(1, count($assetNums));
          foreach ($assetNums as $assetIndex => $assetNum): ?>
              <tr class="return-asset-subrow">
                <?php if (
              $assetIndex === 0
          ): ?><td rowspan="<?= $rowspan ?>" class="return-shared-cell"><?= (int) ($p["offset"] ?? 0) +
    $index +
    1 ?></td>
                <td rowspan="<?= $rowspan ?>" class="return-reference-cell"><span
                    class="return-number-box"><?= $e(
    $row["return_no"],
) ?></span><a class="btn btn-danger btn-xs return-pdf-button" href="<?= BASEURL ?>/Asset/downloadReturnPdf/<?= $e(
    $row["return_id"],
) ?>" title="Download combined return PDF"><i class="fa fa-file-pdf-o"></i> PDF</a></td>
                <td rowspan="<?= $rowspan ?>" class="return-shared-cell"><?= $dateTimeBadge(
    $row["return_date"] ?? "",
) ?></td><?php endif; ?>
                <?php
          $rawAssetCondition = $conditions[$assetIndex] ?? "-";
          $assetCondition = trim(
              strrchr($rawAssetCondition, ":") ?: $rawAssetCondition,
              ": \t\n\r\0\x0B",
          );
          $assetDescription =
              $descriptions[$assetIndex] ?? trim(strstr($rawAssetCondition, ":", true) ?: "");
          ?>
                <td><button type="button" class="btn btn-link btn-xs" data-asset-details="<?= $e($assetNum) ?>"><strong><?= $e($assetNum) ?></strong></button></td>
                <td>
                  <div><?= $e(
    $assetDescription ?: "-",
) ?></div><span class="btn btn-xs <?= $conditionButtonClass(
    $assetCondition,
) ?> condition-button"><?= $e(
     ucwords(strtolower(str_replace("_", " ", $assetCondition))),
 ) ?></span>
                </td>
                <td><?= $e($categories[$assetIndex] ?? "-") ?></td>
                <?php if (
              $assetIndex === 0
          ): ?><td rowspan="<?= $rowspan ?>" class="return-shared-cell"><?= $e(
    $custodian ?: "-",
) ?></td>
                <td rowspan="<?= $rowspan ?>" class="return-shared-cell"><?= $e(
    $row["reason"] ?? $row["remarks"] ?? "-",
) ?></td>
                <td rowspan="<?= $rowspan ?>" class="return-shared-cell"><?= $e(
    $row["received_by"],
) ?></td><?php endif; ?>
              </tr>
              <?php endforeach;
      endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="row" style="margin:12px">
          <div class="col-sm-6">
            Showing <?= (int) ($p["total"] ?? 0) === 0
                ? 0
                : (int) ($p["offset"] ?? 0) + 1 ?>
            to <?= min(
                (int) ($p["offset"] ?? 0) + count($data["rows"] ?? []),
                (int) ($p["total"] ?? 0),
            ) ?> of <?= number_format((int) ($p["total"] ?? 0)) ?> returns
          </div>
          <div class="col-sm-6 text-right">
            <?php
            $currentPage = (int) ($p["page"] ?? 1);
            $totalPages = (int) ($p["totalPages"] ?? 1);
            $returnQuery = $f;
            ?>
            <?php if ($currentPage > 1): ?>
            <a class="btn btn-default btn-sm"
              href="<?= BASEURL ?>/Asset/returnHistory?<?= $e(
    http_build_query($returnQuery + ["page" => $currentPage - 1]),
) ?>">Previous</a>
            <?php endif; ?>
            <span class="btn btn-light btn-sm disabled">
              Page <?= $currentPage ?> of <?= $totalPages ?>
            </span>
            <?php if ($currentPage < $totalPages): ?>
            <a class="btn btn-default btn-sm"
              href="<?= BASEURL ?>/Asset/returnHistory?<?= $e(
    http_build_query($returnQuery + ["page" => $currentPage + 1]),
) ?>">Next</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<style>
  .return-reference-cell,
  .return-shared-cell {
    text-align: center;
    vertical-align: middle !important
  }

  .return-reference-cell {
    min-width: 150px
  }

  .return-number-box {
    display: block;
    padding: 6px 8px;
    border: 1px solid #0b7769;
    border-radius: 5px;
    background: #eef8f6;
    color: #075b50;
    font-weight: 700;
    overflow-wrap: anywhere
  }

  .return-pdf-button {
    display: inline-block;
    margin-top: 7px
  }

  .return-asset-subrow td {
    vertical-align: middle !important
  }

  .condition-button {
    min-width: 105px;
    cursor: default !important;
    font-weight: 700;
    text-transform: capitalize
  }

  .return-sort-link {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    color: inherit;
    font-weight: 700;
    text-decoration: none
  }

  .return-sort-link:hover,
  .return-sort-link:focus {
    color: #0b7769;
    text-decoration: none
  }
</style>
<?php require __DIR__ . "/../templates/asset_details_modal.php"; ?>
