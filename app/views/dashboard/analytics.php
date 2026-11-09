<?php $a = $data["analytics"];
$s = $a["summary"];
$agent = $data["agentSummary"] ?? ["total" => 0, "current_count" => 0, "unlinked_count" => 0];
$maxCategory = max(
    array_merge(
        [1],
        array_map(function ($r) {
            return (int) $r["total"];
        }, $a["categories"]),
    ),
);
$maxDept = max(
    array_merge(
        [1],
        array_map(function ($r) {
            return (int) $r["assignedAssets"];
        }, $a["departments"]),
    ),
);
$warranty = $a["warranty"] ?? [
    "from" => date("Y-01-01"),
    "to" => date("Y-12-31", strtotime("+3 years")),
    "total" => 0,
    "limit" => 100,
    "items" => [],
    "alerts" => [
        "expired" => 0,
        "dueSoon" => 0,
        "active" => 0,
        "nonWarranty" => 0,
        "count" => 0,
    ],
    "summaryGraph" => ["granularity" => "month", "rows" => []],
];
$durationSummaryRows = $warranty["summaryGraph"]["rows"] ?? [];
$durationPurchased = array_sum(
    array_map(
        static fn($row) => (int) ($row["purchased"] ?? 0),
        $durationSummaryRows,
    ),
);
$durationExpiring = array_sum(
    array_map(
        static fn($row) => (int) ($row["expiring"] ?? 0),
        $durationSummaryRows,
    ),
);
$durationNonWarranty = array_sum(
    array_map(
        static fn($row) => (int) ($row["nonWarranty"] ?? 0),
        $durationSummaryRows,
    ),
);
$durationGraphMax = max(
    array_merge(
        [1],
        array_map(
            static fn($row) => max(
                (int) ($row["purchased"] ?? 0),
                (int) ($row["expiring"] ?? 0),
                (int) ($row["nonWarranty"] ?? 0),
            ),
            $durationSummaryRows,
        ),
    ),
);
$timelineStart = new DateTimeImmutable($warranty["from"]);
$timelineEnd = new DateTimeImmutable($warranty["to"]);
$timelineToday = new DateTimeImmutable("today");
$timelineWarningDate = $timelineToday->modify("+30 days");
$timelineRangeSeconds = max(
    86400,
    $timelineEnd->getTimestamp() - $timelineStart->getTimestamp(),
);
$timelineMonths = max(
    1,
    ((int) $timelineEnd->format("Y") - (int) $timelineStart->format("Y")) * 12 +
        ((int) $timelineEnd->format("n") - (int) $timelineStart->format("n")),
);
$timelineMarkerStep = $timelineMonths <= 18 ? 1 : ($timelineMonths <= 48 ? 6 : 12);
$timelineMarkers = [
    [
        "date" => $timelineStart,
        "left" => 0,
    ],
];
$timelineMarker = $timelineStart
    ->modify("first day of next month")
    ->modify("+" . ($timelineMarkerStep - 1) . " months");
while ($timelineMarker < $timelineEnd) {
    $timelineMarkers[] = [
        "date" => $timelineMarker,
        "left" =>
            (($timelineMarker->getTimestamp() - $timelineStart->getTimestamp()) /
                $timelineRangeSeconds) *
            100,
    ];
    $timelineMarker = $timelineMarker->modify(
        "+" . $timelineMarkerStep . " months",
    );
}
$timelineMarkers[] = [
    "date" => $timelineEnd,
    "left" => 100,
];
?>
<style>
  .analytics-card {
    border: 1px solid #e4e9f0 !important;
    border-left: 4px solid #087f5b !important;
    border-radius: 12px;
    color: #172033 !important;
    padding: 18px;
    min-height: 116px;
    box-shadow: 0 10px 25px rgba(23, 32, 51, .06);
    margin-bottom: 16px;
    background: #fff !important
  }

  .analytics-card h2 {
    margin: 0;
    font-size: 30px
  }

  .analytics-card p {
    margin: 7px 0 0;
    color: #687386
  }

  .analytics-card i {
    float: right;
    font-size: 36px;
    color: #087f5b;
    opacity: .28
  }

  .analytics-panel {
    border: 1px solid #e4e9f0;
    border-radius: 12px;
    background: #fff;
    margin-bottom: 20px;
    box-shadow: 0 10px 25px rgba(23, 32, 51, .05);
    overflow: hidden
  }

  .analytics-panel h4 {
    margin: 0;
    padding: 14px 16px;
    border-bottom: 1px solid #e4e9f0;
    background: #f8fafc
  }

  .analytics-body {
    padding: 16px
  }

  .bar-row {
    margin-bottom: 13px
  }

  .bar-track {
    height: 10px;
    background: #edf1f5;
    border-radius: 5px;
    overflow: hidden
  }

  .bar-fill {
    height: 100%;
    background: #087f5b
  }

  .flow-bars {
    display: flex;
    align-items: flex-end;
    height: 125px;
    gap: 4px
  }

  .flow-bar {
    width: 10px;
    min-height: 2px
  }

  .flow-month {
    display: inline-block;
    min-width: 90px
  }

  .warranty-summary {
    margin-bottom: 14px
  }

  .warranty-summary .label {
    display: inline-block;
    margin: 0 7px 5px 0;
    padding: 6px 9px;
    font-size: 12px
  }

  .duration-metric {
    border: 1px solid #e4e9f0;
    border-radius: 8px;
    padding: 10px 6px;
    margin-bottom: 10px;
    background: #fafcfe
  }

  .duration-metric strong {
    display: block;
    font-size: 22px;
    color: #172033
  }

  .duration-graph {
    margin: 12px 0 20px
  }

  .duration-vertical-chart {
    display: flex;
    min-width: 620px;
    padding-top: 22px;
    padding-bottom: 35px
  }

  .duration-y-scale {
    flex: 0 0 78px;
    height: 220px;
    display: flex;
    align-items: stretch
  }

  .duration-y-axis {
    flex: 1 1 auto;
    height: 220px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding-right: 10px;
    padding-bottom: 1px;
    text-align: right;
    color: #667085;
    font-size: 11px
  }

  .duration-plot {
    position: relative;
    flex: 1 1 auto;
    min-width: 560px
  }

  .duration-columns {
    height: 220px;
    display: flex;
    align-items: stretch;
    border-left: 1px solid #bfc9d5;
    border-bottom: 1px solid #bfc9d5;
    background: repeating-linear-gradient(to top,
        transparent 0,
        transparent 54px,
        #e7ecf2 54px,
        #e7ecf2 55px)
  }

  .duration-column {
    flex: 1 1 80px;
    min-width: 72px;
    height: 255px;
    display: flex;
    flex-direction: column
  }

  .duration-column-bars {
    height: 220px;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    gap: 8px;
    padding: 0 8px
  }

  .duration-vbar {
    position: relative;
    width: 24px;
    min-height: 2px;
    border-radius: 5px 5px 0 0
  }

  .duration-vbar.purchased {
    background: #3a87ad
  }

  .duration-vbar.expiring {
    background: #f89406
  }

  .duration-vbar.non-warranty {
    background: #7f8c8d
  }

  .duration-vbar span {
    position: absolute;
    top: -18px;
    left: 50%;
    transform: translateX(-50%);
    color: #344054;
    font-size: 10px;
    font-weight: 600
  }

  .duration-x-label {
    height: 35px;
    padding: 7px 3px 0;
    text-align: center;
    color: #344054;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap
  }

  .duration-y-title {
    flex: 0 0 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    writing-mode: vertical-rl;
    transform: rotate(180deg);
    color: #667085;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap
  }

  .duration-legend {
    font-size: 12px;
    color: #667085
  }

  .duration-legend span {
    margin-right: 14px
  }

  .duration-dot {
    display: inline-block;
    width: 9px;
    height: 9px;
    margin-right: 4px;
    border-radius: 50%
  }

  .warranty-chart {
    min-width: 760px
  }

  .warranty-axis,
  .warranty-track {
    position: relative;
    margin-left: 230px
  }

  .warranty-axis {
    height: 34px;
    border-bottom: 1px solid #cfd8e3
  }

  .warranty-marker {
    position: absolute;
    bottom: 4px;
    color: #667085;
    font-size: 11px;
    transform: translateX(-50%);
    white-space: nowrap
  }

  .warranty-marker:first-child {
    transform: none
  }

  .warranty-marker:last-child {
    transform: translateX(-100%)
  }

  .warranty-row {
    position: relative;
    min-height: 54px;
    padding: 9px 0;
    border-bottom: 1px solid #edf1f5
  }

  .warranty-asset {
    position: absolute;
    left: 0;
    width: 215px;
    line-height: 17px
  }

  .warranty-track {
    height: 22px;
    top: 7px;
    border-radius: 11px;
    background: #edf1f5
  }

  .warranty-bar {
    position: absolute;
    top: 0;
    height: 22px;
    min-width: 4px;
    border-radius: 11px;
    background: #3a87ad
  }

  .warranty-bar.due-soon {
    background: #f89406
  }

  .warranty-bar.expired {
    background: #d15b47
  }

  .warranty-status {
    position: absolute;
    top: 30px;
    left: 230px;
    font-size: 11px
  }

  .metric-note {
    font-size: 12px;
    color: #778
  }

  .transaction-badge {
    display: inline-block;
    min-width: 72px;
    text-align: center;
    padding: 3px 6px;
    border-radius: 4px;
    color: #fff
  }

  .RECEIPT {
    background: #6fb3e0
  }

  .ISSUE {
    background: #87b87f
  }

  .RETURN {
    background: #ffb752
  }
</style>
<div class="page-content">
  <div class="container-fluid">
    <div class="row">
      <?php
$cards = [
    ["Manual Assets", $s["totalAssets"], "fa-laptop", "#438eb9", BASEURL . "/Asset/active"],
    // ["Agent PCs", $agent["total"], "fa-desktop", "#087f5b", BASEURL . "/Agent"],
    // [
    //     "Current Agent PCs",
    //     $agent["current_count"],
    //     "fa-check-circle",
    //     "#15966f",
    //     BASEURL . "/Agent?status=CURRENT",
    // ],
    // [
    //     "Unlinked Agent PCs",
    //     $agent["unlinked_count"],
    //     "fa-chain-broken",
    //     "#2563eb",
    //     BASEURL . "/Agent?link=UNLINKED",
    // ],
    ["In Stock", $s["inStock"], "fa-cubes", "#82af6f", BASEURL . "/Asset/inactive"],
    ["Currently Assigned", $s["assigned"], "fa-exchange", "#f89406", BASEURL . "/Asset/active"],
    ["Active Employees", $s["activeEmployees"], "fa-users", "#9585bf", BASEURL . "/Employee/list"],
    ["Departments", $s["departments"], "fa-building", "#5bc0de", BASEURL . "/Department"],
    // Temporarily hidden dashboard cards. Restore these entries when the
    // corresponding summaries are needed on the dashboard again.
    // ["Scrapped", $s["scrapped"], "fa-trash", "#d15b47", BASEURL . "/Asset/disposed"],
    // ["Total Returns", $s["totalReturns"], "fa-reply", "#a069c3", BASEURL . "/Asset/returns"],
    // ["Evidence Files", $s["mediaFiles"], "fa-picture-o", "#3a87ad", "#"],
];
foreach ($cards as $c): ?>
      <div class="col-lg-4 col-md-4 col-sm-6"><a href="<?= $c[4] ?>" style="text-decoration:none">
          <div class="analytics-card" style="background:<?= $c[3] ?>"><i
              class="fa <?= $c[2] ?>"></i>
            <h2><?= (int) $c[1] ?></h2>
            <p><?= $c[0] ?></p>
          </div>
        </a></div><?php endforeach;
?>
    </div>
    <div class="row">
      <div class="col-lg-8">
        <div class="analytics-panel" id="warranty-timeline">
          <h4><i class="fa fa-calendar-check-o"></i> Asset Duration Analytics</h4>
          <div class="analytics-body">
            <form method="get" action="<?= BASEURL ?>/" class="form-inline"
              style="margin-bottom:14px">
              <div class="form-group">
                <label for="warranty-from">From</label>
                <input type="date" class="form-control input-sm" id="warranty-from"
                  name="warranty_from" value="<?= htmlspecialchars(
                      $warranty["from"],
                      ENT_QUOTES,
                      "UTF-8",
                  ) ?>" required>
              </div>
              <div class="form-group" style="margin-left:8px">
                <label for="warranty-to">To</label>
                <input type="date" class="form-control input-sm" id="warranty-to"
                  name="warranty_to" value="<?= htmlspecialchars(
                      $warranty["to"],
                      ENT_QUOTES,
                      "UTF-8",
                  ) ?>" required>
              </div>
              <button type="submit" class="btn btn-sm btn-primary" style="margin-left:8px">
                <i class="fa fa-filter"></i> View Period
              </button>
              <a href="<?= BASEURL ?>/#warranty-timeline"
                class="btn btn-sm btn-default" style="margin-left:6px">
                <i class="fa fa-undo"></i> Reset
              </a>
            </form>

            <h5><strong>Graph Summary</strong>
              <small class="text-muted">Grouped by <?= htmlspecialchars(
                  $warranty["summaryGraph"]["granularity"] ?? "month",
              ) ?></small>
            </h5>
            <div class="row text-center">
              <div class="col-sm-2 col-xs-6">
                <div class="duration-metric"><strong><?= (int) $durationPurchased ?></strong>
                  Purchased in period</div>
              </div>
              <div class="col-sm-2 col-xs-6">
                <div class="duration-metric"><strong><?= (int) $durationExpiring ?></strong>
                  Expiring in period</div>
              </div>
              <div class="col-sm-2 col-xs-6">
                <div class="duration-metric"><strong><?= (int) ($warranty["alerts"][
                    "active"
                ] ?? 0) ?></strong> Active warranties</div>
              </div>
              <div class="col-sm-2 col-xs-6">
                <div class="duration-metric"><strong class="text-warning"><?= (int) $warranty[
                    "alerts"
                ]["dueSoon"] ?></strong> Within 30 days</div>
              </div>
              <div class="col-sm-2 col-xs-6">
                <div class="duration-metric"><strong class="text-danger"><?= (int) $warranty[
                    "alerts"
                ]["expired"] ?></strong> Expired</div>
              </div>
              <div class="col-sm-2 col-xs-6">
                <div class="duration-metric"><strong><?= (int) $durationNonWarranty ?></strong>
                  Non-warranty in period</div>
              </div>
            </div>

            <div class="duration-legend">
              <span><i class="duration-dot" style="background:#3a87ad"></i>Purchased</span>
              <span><i class="duration-dot" style="background:#f89406"></i>Warranty expiry</span>
              <span><i class="duration-dot" style="background:#7f8c8d"></i>Non-warranty</span>
            </div>
            <div class="duration-graph">
              <?php if ($durationSummaryRows): ?>
              <div class="table-responsive" style="overflow-x:auto;padding-top:4px">
                <div class="duration-vertical-chart" style="min-width:<?= max(
                    620,
                    count($durationSummaryRows) * 82,
                ) ?>px">
                  <div class="duration-y-scale">
                    <span class="duration-y-title">Asset count</span>
                    <div class="duration-y-axis">
                      <?php for ($tick = 4; $tick >= 0; $tick--): ?>
                      <span><?= (int) ceil(($durationGraphMax * $tick) / 4) ?></span>
                      <?php endfor; ?>
                    </div>
                  </div>
                  <div class="duration-plot">
                    <div class="duration-columns">
                      <?php foreach ($durationSummaryRows as $summaryRow):
                          $periodLabel = (string) $summaryRow["period"];
                          if (
                              ($warranty["summaryGraph"]["granularity"] ?? "month") ===
                              "month"
                          ) {
                              $periodDate = DateTimeImmutable::createFromFormat(
                                  "!Y-m-d",
                                  $periodLabel . "-01",
                              );
                              if ($periodDate) {
                                  $periodLabel = $periodDate->format("M Y");
                              }
                          }
                          $purchasedCount = (int) $summaryRow["purchased"];
                          $expiringCount = (int) $summaryRow["expiring"];
                          $nonWarrantyCount = (int) ($summaryRow["nonWarranty"] ?? 0);
                          ?>
                      <div class="duration-column">
                        <div class="duration-column-bars">
                          <div class="duration-vbar purchased"
                            style="height:<?= round(
                                ($purchasedCount / $durationGraphMax) * 100,
                                2,
                            ) ?>%"
                            title="<?= $purchasedCount ?> assets purchased">
                            <span><?= $purchasedCount ?></span>
                          </div>
                          <div class="duration-vbar expiring"
                            style="height:<?= round(
                                ($expiringCount / $durationGraphMax) * 100,
                                2,
                            ) ?>%"
                            title="<?= $expiringCount ?> warranties expiring">
                            <span><?= $expiringCount ?></span>
                          </div>
                          <div class="duration-vbar non-warranty"
                            style="height:<?= round(
                                ($nonWarrantyCount / $durationGraphMax) * 100,
                                2,
                            ) ?>%"
                            title="<?= $nonWarrantyCount ?> non-warranty assets purchased">
                            <span><?= $nonWarrantyCount ?></span>
                          </div>
                        </div>
                        <div class="duration-x-label"><?= htmlspecialchars(
                            $periodLabel,
                        ) ?></div>
                      </div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                </div>
              </div>
              <?php else: ?>
              <p class="text-muted text-center">No purchase or warranty-expiry activity in the
                selected period.</p>
              <?php endif; ?>
            </div>

            <?php if (false): ?>
            <!-- Preserved individual asset-duration timeline. The dashboard
                 currently shows the compact graph summary only. -->
            <div class="warranty-summary">
              <span class="label label-info"><?= (int) $warranty[
                  "total"
              ] ?> assets in selected period</span>
              <span class="label label-warning"><?= (int) $warranty["alerts"][
                  "dueSoon"
              ] ?> expire within 30 days</span>
              <span class="label label-danger"><?= (int) $warranty["alerts"][
                  "expired"
              ] ?> expired</span>
              <span class="metric-note">The warning remains until the warranty expiry is
                renewed or updated.</span>
            </div>

            <div class="table-responsive" style="max-height:480px;overflow:auto">
              <div class="warranty-chart">
                <div class="warranty-axis">
                  <?php foreach ($timelineMarkers as $marker): ?>
                  <span class="warranty-marker" style="left:<?= round(
                      $marker["left"],
                      3,
                  ) ?>%"><?= htmlspecialchars(
    $marker["date"]->format(
        $timelineMarkerStep >= 12 ? "Y" : "M Y",
    ),
) ?></span>
                  <?php endforeach; ?>
                </div>

                <?php foreach ($warranty["items"] as $item):
                    $purchaseDate = new DateTimeImmutable($item["purchase_date"]);
                    $expiryDate = new DateTimeImmutable($item["warranty_expiry"]);
                    $visibleStart =
                        $purchaseDate < $timelineStart ? $timelineStart : $purchaseDate;
                    $visibleEnd = $expiryDate > $timelineEnd ? $timelineEnd : $expiryDate;
                    $barLeft =
                        (($visibleStart->getTimestamp() - $timelineStart->getTimestamp()) /
                            $timelineRangeSeconds) *
                        100;
                    $barWidth =
                        (($visibleEnd->getTimestamp() - $visibleStart->getTimestamp()) /
                            $timelineRangeSeconds) *
                        100;
                    $barClass = "";
                    $statusText = (int) $item["daysRemaining"] . " days remaining";
                    if ($expiryDate < $timelineToday) {
                        $barClass = "expired";
                        $statusText =
                            abs((int) $item["daysRemaining"]) . " days overdue";
                    } elseif ($expiryDate <= $timelineWarningDate) {
                        $barClass = "due-soon";
                    }
                    ?>
                <div class="warranty-row">
                  <div class="warranty-asset">
                    <button type="button" class="btn btn-link btn-xs" style="padding:0"
                      data-asset-details="<?= htmlspecialchars(
                          $item["assetNum"],
                          ENT_QUOTES,
                          "UTF-8",
                      ) ?>"><?= htmlspecialchars($item["assetNum"]) ?></button>
                    <br><small title="<?= htmlspecialchars(
                        $item["Description"],
                        ENT_QUOTES,
                        "UTF-8",
                    ) ?>"><?= htmlspecialchars(
    strlen((string) $item["Description"]) > 34
        ? substr((string) $item["Description"], 0, 31) . "..."
        : (string) $item["Description"],
) ?></small>
                  </div>
                  <div class="warranty-track">
                    <div class="warranty-bar <?= $barClass ?>"
                      style="left:<?= round(max(0, $barLeft), 3) ?>%;width:<?= round(
    max(0.6, min(100 - max(0, $barLeft), $barWidth)),
    3,
) ?>%"
                      title="<?= htmlspecialchars(
                          $item["assetNum"] .
                              ": " .
                              $item["purchase_date"] .
                              " to " .
                              $item["warranty_expiry"],
                          ENT_QUOTES,
                          "UTF-8",
                      ) ?>"></div>
                  </div>
                  <span class="warranty-status <?= $barClass === "expired"
                      ? "text-danger"
                      : ($barClass === "due-soon"
                          ? "text-warning"
                          : "text-muted") ?>">
                    <?= htmlspecialchars($item["purchase_date"]) ?> &rarr;
                    <?= htmlspecialchars($item["warranty_expiry"]) ?> &middot;
                    <?= htmlspecialchars($statusText) ?>
                  </span>
                </div>
                <?php endforeach; ?>

                <?php if (empty($warranty["items"])): ?>
                <p class="text-muted text-center" style="padding:22px">
                  No asset warranties intersect the selected period.
                </p>
                <?php endif; ?>
              </div>
            </div>
            <?php if ((int) $warranty["total"] > count($warranty["items"])): ?>
            <p class="help-block" style="margin-top:10px">
              Showing the first <?= count($warranty["items"]) ?> assets by expiry date from
              <?= (int) $warranty["total"] ?> matching assets. Narrow the period to focus the
              timeline.
            </p>
            <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php if (false): ?>
      <div class="col-lg-8">
        <div class="analytics-panel">
          <h4><i class="fa fa-random"></i> Full Transaction Flow</h4>
          <div class="analytics-body">
            <div class="row text-center">
              <div class="col-xs-3">
                <h3><?= (int) $s[
    "receipts"
] ?></h3>
                <div>Receipts</div>
              </div>
              <div class="col-xs-3">
                <h3><?= (int) $s[
    "totalIssues"
] ?></h3>
                <div>Issues</div>
              </div>
              <div class="col-xs-3">
                <h3><?= (int) $s[
    "reissued"
] ?></h3>
                <div>Re-issued</div>
              </div>
              <div class="col-xs-3">
                <h3><?= (int) $s[
    "scrapReturns"
] ?></h3>
                <div>Scrap Returns</div>
              </div>
            </div>
            <hr>
            <?php if ($a["flow"]):
    foreach (
        $a["flow"]
        as $r
    ): ?><div class="row" style="margin-bottom:10px">
              <div class="col-sm-2 flow-month"><strong><?= htmlspecialchars(
    $r["month"],
) ?></strong></div>
              <div class="col-sm-10">
                <div class="flow-bars">
                  <div title="Received <?= (int) $r[
    "received"
] ?>" class="flow-bar" style="height:<?= max(
    2,
    round(($r["received"] / $maxFlow) * 100),
) ?>%;background:#6fb3e0"></div>
                  <div title="Issued <?= (int) $r[
    "issued"
] ?>" class="flow-bar" style="height:<?= max(
    2,
    round(($r["issued"] / $maxFlow) * 100),
) ?>%;background:#87b87f"></div>
                  <div title="Returned <?= (int) $r[
    "returned"
] ?>" class="flow-bar" style="height:<?= max(
    2,
    round(($r["returned"] / $maxFlow) * 100),
) ?>%;background:#ffb752"></div><span class="metric-note">Received <?= (int) $r[
    "received"
] ?> · Issued <?= (int) $r["issued"] ?> · Returned <?= (int) $r[
     "returned"
 ] ?></span>
                </div>
              </div>
            </div><?php endforeach;
else:
     ?><p class="text-muted text-center">No transaction history yet.</p><?php
endif; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>
      <div class="col-lg-4">
        <div class="analytics-panel">
          <h4><i class="fa fa-pie-chart"></i> Asset Status Summary</h4>
          <div class="analytics-body">
            <div class="bar-row"><span>In Stock <b class="pull-right"><?= (int) $s[
    "inStock"
] ?></b></span>
              <div class="bar-track">
                <div class="bar-fill" style="width:<?= $s["totalAssets"]
    ? round(($s["inStock"] / $s["totalAssets"]) * 100)
    : 0 ?>%;background:#82af6f"></div>
              </div>
            </div>
            <div class="bar-row"><span>Assigned <b class="pull-right"><?= (int) $s[
    "assigned"
] ?></b></span>
              <div class="bar-track">
                <div class="bar-fill" style="width:<?= $s["totalAssets"]
    ? round(($s["assigned"] / $s["totalAssets"]) * 100)
    : 0 ?>%;background:#f89406"></div>
              </div>
            </div>
            <div class="bar-row"><span>Scrapped <b class="pull-right"><?= (int) $s[
    "scrapped"
] ?></b></span>
              <div class="bar-track">
                <div class="bar-fill" style="width:<?= $s["totalAssets"]
    ? round(($s["scrapped"] / $s["totalAssets"]) * 100)
    : 0 ?>%;background:#d15b47"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-lg-6">
        <div class="analytics-panel">
          <h4>Assets by Category</h4>
          <div class="analytics-body"><?php foreach (
    $a["categories"]
    as $r
): ?><div class="bar-row"><span><?= htmlspecialchars(
    $r["category"],
) ?><b class="pull-right"><?= (int) $r[
    "total"
] ?></b></span>
              <div class="bar-track">
                <div class="bar-fill" style="width:<?= round(
    ($r["total"] / $maxCategory) * 100,
) ?>%"></div>
              </div><small class="metric-note">Assigned <?= (int) $r[
    "assigned"
] ?> · Stock <?= (int) $r["inStock"] ?> · Scrapped <?= (int) $r[
     "scrapped"
 ] ?></small>
            </div><?php endforeach; ?></div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="analytics-panel">
          <h4>Department & Employee Allocation</h4>
          <div class="analytics-body"><?php foreach (
    $a["departments"]
    as $r
): ?><div class="bar-row"><span><?= htmlspecialchars(
    $r["deptName"],
) ?><b class="pull-right"><?= (int) $r[
    "assignedAssets"
] ?> assets</b></span>
              <div class="bar-track">
                <div class="bar-fill" style="width:<?= round(
     ($r["assignedAssets"] / $maxDept) * 100,
 ) ?>%;background:#9585bf"></div>
              </div><small class="metric-note">Head: <?= htmlspecialchars(
    $r["headName"] ?? "Not assigned",
) ?> · <?= (int) $r[
     "employeeCount"
 ] ?> active employees</small>
            </div><?php endforeach; ?></div>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-lg-5">
        <div class="analytics-panel">
          <h4>Employee Asset Summary</h4>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Employee</th>
                  <th>Department</th>
                  <th class="text-right">Assets</th>
                </tr>
              </thead>
              <tbody><?php foreach (
    $a["employees"]
    as $r
): ?><tr>
                  <td><a href="<?= BASEURL ?>/Employee/edit/<?= (int) $r["empId"] ?>"><?= htmlspecialchars(
    $r["fullname"],
) ?></a></td>
                  <td><?= htmlspecialchars(
    $r["department"] ?? "-",
) ?></td>
                  <td class="text-right"><span class="badge"><?= (int) $r[
    "assignedAssets"
] ?></span></td>
                </tr><?php endforeach; ?></tbody>
            </table>
          </div>
        </div>
      </div>
      <div class="col-lg-7">
        <div class="analytics-panel">
          <h4>Recent System Transactions</h4>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Flow</th>
                  <th>Reference</th>
                  <th>Asset</th>
                  <th>Details</th>
                </tr>
              </thead>
              <tbody><?php
foreach ($a["recent"] as $r): ?><tr>
                  <td><?= htmlspecialchars(
    $r["eventDate"],
) ?></td>
                  <td><span class="transaction-badge <?= $r["eventType"] ?>"><?= htmlspecialchars(
    $r["eventType"],
) ?></span></td>
                  <td><?= htmlspecialchars($r["referenceNo"]) ?></td>
                  <td><?php if (!empty($r["assetNum"])): ?><button type="button" class="btn btn-link btn-xs" data-asset-details="<?= htmlspecialchars(
    $r["assetNum"],
    ENT_QUOTES,
    "UTF-8",
) ?>"><?= htmlspecialchars($r["assetNum"]) ?></button><?php else: ?>-<?php endif; ?></td>
                  <td><?= htmlspecialchars($r["details"]) ?></td>
                </tr><?php endforeach;
if (
    !$a["recent"]
): ?><tr>
                  <td colspan="5" class="text-center">No transactions recorded.</td>
                </tr><?php endif;
?></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
