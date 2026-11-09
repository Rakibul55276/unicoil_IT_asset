<?php
$report = $data["report"] ?? [];
$filters = $data["filters"] ?? [];
$items = $report["items"] ?? [];
$page = (int) ($report["page"] ?? 1);
$totalPages = (int) ($report["totalPages"] ?? 1);
$offset = (int) ($report["offset"] ?? 0);
$total = (int) ($report["total"] ?? 0);
$esc = static fn($value) => htmlspecialchars(
    (string) $value,
    ENT_QUOTES,
    "UTF-8",
);

$timelineStart = new DateTimeImmutable($filters["from"]);
$timelineEnd = new DateTimeImmutable($filters["to"]);
$today = new DateTimeImmutable("today");
$warningDate = $today->modify("+30 days");
$rangeSeconds = max(
    86400,
    $timelineEnd->getTimestamp() - $timelineStart->getTimestamp(),
);
$rangeMonths = max(
    1,
    ((int) $timelineEnd->format("Y") - (int) $timelineStart->format("Y")) * 12 +
        ((int) $timelineEnd->format("n") - (int) $timelineStart->format("n")),
);
$markerStep = $rangeMonths <= 18 ? 1 : ($rangeMonths <= 48 ? 6 : 12);
$markers = [["date" => $timelineStart, "left" => 0]];
$marker = $timelineStart
    ->modify("first day of next month")
    ->modify("+" . ($markerStep - 1) . " months");
while ($marker < $timelineEnd) {
    $markers[] = [
        "date" => $marker,
        "left" =>
            (($marker->getTimestamp() - $timelineStart->getTimestamp()) /
                $rangeSeconds) *
            100,
    ];
    $marker = $marker->modify("+" . $markerStep . " months");
}
$markers[] = ["date" => $timelineEnd, "left" => 100];

$pageUrl = static function (int $targetPage) use ($filters): string {
    return BASEURL .
        "/Asset/warrantyTimeline?" .
        http_build_query(array_merge($filters, ["page" => $targetPage]));
};
$durationLabel = static function (int $days): string {
    $days = max(0, $days);
    $years = intdiv($days, 365);
    $months = intdiv($days % 365, 30);
    $remainingDays = ($days % 365) % 30;
    $parts = [];
    if ($years > 0) {
        $parts[] = $years . " " . ($years === 1 ? "year" : "years");
    }
    if ($months > 0) {
        $parts[] = $months . " " . ($months === 1 ? "month" : "months");
    }
    if (!$parts || $remainingDays > 0) {
        $parts[] =
            $remainingDays . " " . ($remainingDays === 1 ? "day" : "days");
    }
    return implode(" ", $parts);
};
?>

<style>
  .duration-report-panel {
    background: #fff;
    border: 1px solid #e4e9f0;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 8px 22px rgba(23, 32, 51, .05)
  }

  .duration-report-header {
    padding: 14px 16px;
    border-bottom: 1px solid #e4e9f0;
    background: #f8fafc
  }

  .duration-report-body {
    padding: 16px
  }

  .duration-report-chart {
    min-width: 920px
  }

  .duration-detail-axis,
  .duration-detail-track {
    position: relative;
    margin-left: 285px
  }

  .duration-detail-axis {
    height: 36px;
    border-bottom: 1px solid #cfd8e3
  }

  .duration-detail-marker {
    position: absolute;
    bottom: 5px;
    transform: translateX(-50%);
    color: #667085;
    font-size: 11px;
    white-space: nowrap
  }

  .duration-detail-marker:first-child {
    transform: none
  }

  .duration-detail-marker:last-child {
    transform: translateX(-100%)
  }

  .duration-detail-row {
    position: relative;
    min-height: 72px;
    padding: 10px 0;
    border-bottom: 1px solid #edf1f5
  }

  .duration-detail-asset {
    position: absolute;
    left: 0;
    top: 9px;
    width: 270px;
    line-height: 18px
  }

  .duration-detail-track {
    height: 20px;
    top: 5px;
    border-radius: 10px;
    background: #edf1f5
  }

  .duration-detail-bar {
    position: absolute;
    top: 0;
    height: 20px;
    min-width: 4px;
    border-radius: 10px;
    background: #3a87ad
  }

  .duration-detail-bar.due-soon {
    background: #f89406
  }

  .duration-detail-bar.expired {
    background: #d15b47
  }

  .duration-detail-meta {
    position: absolute;
    left: 285px;
    top: 40px;
    color: #667085;
    font-size: 11px;
    white-space: nowrap
  }

  .duration-filter-row .form-group {
    margin-bottom: 10px
  }

  .duration-result-note {
    margin: 12px 0 0;
    color: #667085
  }

  .duration-warranty-badge {
    display: inline-block;
    margin-left: 3px;
    padding: 1px 5px;
    border: 1px solid #b8d7ea;
    border-radius: 8px;
    background: #eaf5fb;
    color: #286987;
    font-size: 9px;
    font-weight: 700;
    line-height: 14px
  }

  .duration-warranty-badge.short-duration {
    border-color: #f4c37d;
    background: #fff3df;
    color: #9a5b00
  }

  .duration-expiry-badge,
  .duration-status-badge {
    display: inline-block;
    padding: 2px 7px;
    border-radius: 9px;
    font-size: 10px;
    font-weight: 700;
    line-height: 15px
  }

  .duration-expiry-badge {
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    color: #475569
  }

  .duration-status-badge.active {
    border: 1px solid #9ed6bb;
    background: #e8f7ef;
    color: #177245
  }

  .duration-status-badge.due-soon {
    border: 1px solid #f4c37d;
    background: #fff3df;
    color: #9a5b00
  }

  .duration-status-badge.expired {
    border: 1px solid #e4a69b;
    background: #fdeceb;
    color: #b23b2a
  }
</style>

<div class="page-content">
  <div class="container-fluid">
    <form method="get" action="<?= BASEURL ?>/Asset/warrantyTimeline"
      class="row duration-filter-row">
      <div class="col-lg-4 col-md-6 form-group">
        <label for="duration-search">Search</label>
        <input type="search" class="form-control" id="duration-search" name="search"
          value="<?= $esc($filters["search"] ?? "") ?>"
          placeholder="Asset number, serial, or description prefix" autocomplete="off">
      </div>
      <div class="col-lg-2 col-md-3 form-group">
        <label for="duration-category">Category</label>
        <select class="form-control" id="duration-category" name="category">
          <option value="ALL">All Categories</option>
          <?php foreach ($data["categories"] ?? [] as $category): ?>
          <option value="<?= $esc($category["assetKat"]) ?>" <?= (string) ($filters[
    "category"
] ?? "ALL") ===
(string) $category["assetKat"]
    ? "selected"
    : "" ?>><?= $esc($category["Description"]) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-lg-2 col-md-3 form-group">
        <label for="duration-status">Warranty Status</label>
        <select class="form-control" id="duration-status" name="status">
          <?php
          $statuses = [
              "ALL" => "All Statuses",
              "ACTIVE" => "Active (over 30 days)",
              "DUE_SOON" => "Expires within 30 days",
              "EXPIRED" => "Expired",
          ];
          foreach ($statuses as $value => $label): ?>
          <option value="<?= $value ?>" <?= ($filters["status"] ?? "ALL") === $value
    ? "selected"
    : "" ?>><?= $esc($label) ?></option>
          <?php endforeach;
          ?>
        </select>
      </div>
      <div class="col-lg-3 col-md-6 form-group">
        <label for="duration-sort">Sort Type</label>
        <select class="form-control" id="duration-sort" name="sort">
          <?php
          $sortOptions = [
              "DURATION_ASC" => "Duration: shortest first",
              "DURATION_DESC" => "Duration: longest first",
              "EXPIRY_ASC" => "Expiry: soonest first",
              "EXPIRY_DESC" => "Expiry: latest first",
              "PURCHASE_DESC" => "Purchase: newest first",
              "PURCHASE_ASC" => "Purchase: oldest first",
              "ASSET_ASC" => "Asset number",
          ];
          foreach ($sortOptions as $value => $label): ?>
          <option value="<?= $value ?>" <?= ($filters["sort"] ?? "DURATION_ASC") ===
    $value
    ? "selected"
    : "" ?>><?= $esc($label) ?></option>
          <?php endforeach;
          ?>
        </select>
      </div>
      <div class="col-lg-1 col-md-2 form-group">
        <label for="duration-per-page">Rows</label>
        <select class="form-control" id="duration-per-page" name="perPage">
          <?php foreach ([25, 50, 100] as $pageSize): ?>
          <option value="<?= $pageSize ?>" <?= (int) ($filters["perPage"] ?? 25) ===
    $pageSize
    ? "selected"
    : "" ?>><?= $pageSize ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-lg-2 col-md-3 form-group">
        <label for="duration-from">Period From</label>
        <input type="date" class="form-control" id="duration-from" name="from"
          value="<?= $esc($filters["from"]) ?>" required>
      </div>
      <div class="col-lg-2 col-md-3 form-group">
        <label for="duration-to">Period To</label>
        <input type="date" class="form-control" id="duration-to" name="to"
          value="<?= $esc($filters["to"]) ?>" required>
      </div>
      <div class="col-lg-4 col-md-6 form-group" style="padding-top:25px">
        <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i>
          Apply Filters</button>
        <a href="<?= BASEURL ?>/Asset/warrantyTimeline" class="btn btn-default">
          <i class="fa fa-undo"></i> Reset</a>
      </div>
    </form>

    <?php
    $exportFilters = $filters;
    unset($exportFilters["page"], $exportFilters["perPage"]);
    $exportQuery = http_build_query($exportFilters);
    ?>
    <!-- <div class="btn-group" style="margin-bottom:15px">
      <a class="btn btn-success"
        href="<?= BASEURL ?>/Asset/exportReportExcel/expiry?<?= $exportQuery ?>">
        <i class="fa fa-file-excel-o"></i> Export Excel
      </a>
      <a class="btn btn-danger"
        href="<?= BASEURL ?>/Asset/exportReportPdf/expiry?<?= $exportQuery ?>">
        <i class="fa fa-file-pdf-o"></i> Export PDF
      </a>
    </div> -->

    <div class="duration-report-panel">
      <div class="duration-report-header">
        <strong><i class="fa fa-calendar"></i> Individual Asset Duration Timeline</strong>
        <span class="pull-right"><?= number_format($total) ?> matching assets</span>
      </div>
      <div class="duration-report-body">
        <div class="table-responsive" style="overflow-x:auto">
          <div class="duration-report-chart">
            <div class="duration-detail-axis">
              <?php foreach ($markers as $timelineMarker): ?>
              <span class="duration-detail-marker" style="left:<?= round(
                  $timelineMarker["left"],
                  3,
              ) ?>%"><?= $esc(
    $timelineMarker["date"]->format(
        $markerStep >= 12 ? "Y" : "M Y",
    ),
) ?></span>
              <?php endforeach; ?>
            </div>

            <?php foreach ($items as $item):
                $purchaseDate = new DateTimeImmutable($item["purchase_date"]);
                $expiryDate = new DateTimeImmutable($item["warranty_expiry"]);
                $visibleStart =
                    $purchaseDate < $timelineStart ? $timelineStart : $purchaseDate;
                $visibleEnd = $expiryDate > $timelineEnd ? $timelineEnd : $expiryDate;
                $barLeft =
                    (($visibleStart->getTimestamp() - $timelineStart->getTimestamp()) /
                        $rangeSeconds) *
                    100;
                $barWidth =
                    (($visibleEnd->getTimestamp() - $visibleStart->getTimestamp()) /
                        $rangeSeconds) *
                    100;
                $statusClass = "";
                $statusText = (int) $item["daysRemaining"] . " days remaining";
                if ($expiryDate < $today) {
                    $statusClass = "expired";
                    $statusText =
                        abs((int) $item["daysRemaining"]) . " days overdue";
                } elseif ($expiryDate <= $warningDate) {
                    $statusClass = "due-soon";
                } else {
                    $statusClass = "active";
                }
                ?>
            <div class="duration-detail-row">
              <div class="duration-detail-asset">
                <button type="button" class="btn btn-link btn-xs" style="padding:0"
                  data-asset-details="<?= $esc($item["assetNum"]) ?>"><?= $esc(
    $item["assetNum"],
) ?></button>
                <strong><?= $esc($item["category"] ?? "-") ?></strong><br>
                <small><?= $esc($item["serial_number"] ?: "No serial") ?></small>
                <span class="duration-warranty-badge <?= (int) $item[
                    "durationDays"
                ] <= 30
                    ? "short-duration"
                    : "" ?>">
                  Warranty: <?= $esc(
                      $durationLabel((int) $item["durationDays"]),
                  ) ?>
                </span>
              </div>
              <div class="duration-detail-track">
                <div class="duration-detail-bar <?= $statusClass ?>"
                  style="left:<?= round(max(0, $barLeft), 3) ?>%;width:<?= round(
    max(0.6, min(100 - max(0, $barLeft), $barWidth)),
    3,
) ?>%"
                  title="<?= $esc(
                      $item["assetNum"] .
                          ": " .
                          $item["purchase_date"] .
                          " to " .
                          $item["warranty_expiry"],
                  ) ?>"></div>
              </div>
              <div class="duration-detail-meta">
                Purchased: <?= $esc($item["purchase_date"]) ?> &middot;
                <span class="duration-expiry-badge">
                  Expiry: <?= $esc($item["warranty_expiry"]) ?>
                </span>
                <span class="duration-status-badge <?= $statusClass ?>">
                  <?= $esc($statusText) ?>
                </span>
                &middot; <?= $esc($item["Description"]) ?>
              </div>
            </div>
            <?php endforeach; ?>

            <?php if (!$items): ?>
            <p class="text-center text-muted" style="padding:28px">
              No asset durations match the selected filters.
            </p>
            <?php endif; ?>
          </div>
        </div>

        <p class="duration-result-note">
          Showing <?= $total === 0 ? 0 : $offset + 1 ?> to
          <?= min($offset + count($items), $total) ?> of <?= number_format($total) ?>
          assets. Search uses prefixes to remain fast with large inventories.
        </p>

        <?php if ($totalPages > 1): ?>
        <ul class="pagination">
          <li class="<?= $page <= 1 ? "disabled" : "" ?>">
            <a href="<?= $page <= 1 ? "#" : $esc($pageUrl($page - 1)) ?>">&laquo; Previous</a>
          </li>
          <?php
          $startPage = max(1, $page - 2);
          $endPage = min($totalPages, $page + 2);
          for ($pageNumber = $startPage; $pageNumber <= $endPage; $pageNumber++): ?>
          <li class="<?= $pageNumber === $page ? "active" : "" ?>">
            <a href="<?= $esc($pageUrl($pageNumber)) ?>"><?= $pageNumber ?></a>
          </li>
          <?php endfor;
          ?>
          <li class="<?= $page >= $totalPages ? "disabled" : "" ?>">
            <a href="<?= $page >= $totalPages ? "#" : $esc($pageUrl($page + 1)) ?>">Next
              &raquo;</a>
          </li>
        </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
