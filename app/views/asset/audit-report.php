<div class="page-content">
  <div class="container-fluid">
    <style>
      #audit-report-table .audit-details {
        min-width: 240px;
        max-width: 360px;
        white-space: normal;
        overflow-wrap: anywhere;
        word-break: normal;
        line-height: 1.45
      }

      #audit-report-table .audit-details br {
        display: block
      }

      #audit-report-table .audit-details strong {
        display: inline;
        margin-left: 0
      }

      #audit-report-table .audit-date {
        min-width: 118px;
        white-space: nowrap
      }

      #audit-report-table .audit-date strong,
      #audit-report-table .audit-date span {
        display: block
      }

      #audit-report-table .audit-date strong {
        color: #23364d;
        font-size: 12px
      }

      #audit-report-table .audit-date span {
        margin-top: 4px;
        color: #667085;
        font-size: 11px
      }

      .audit-event-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 8px;
        border-radius: 12px;
        color: #fff;
        font-size: 10px;
        font-weight: 700;
        line-height: 1;
        white-space: nowrap;
        text-transform: uppercase
      }

      .audit-event-receipt { background: #3178a9 }
      .audit-event-in-stock { background: #2f8f83 }
      .audit-event-update { background: #6f5aa7 }
      .audit-event-assign { background: #27845f }
      .audit-event-return { background: #d4811f }
      .audit-event-approved { background: #438b45 }
      .audit-event-danger { background: #c94b50 }
      .audit-event-default { background: #667085 }
    </style>
    <div class="widget-box">
      <div class="widget-header">
        <h4 class="widget-title"><i class="fa fa-shield"></i> Asset Transaction Audit</h4>
      </div>
      <div class="widget-body">
        <div class="widget-main">
          <?php
          $formatAuditDateParts = static function ($value): array {
              $timestamp = strtotime((string) $value);
              return $timestamp === false
                  ? ["date" => (string) $value, "time" => ""]
                  : [
                      "date" => date("Y-m-d", $timestamp),
                      "time" => date("h:i:s A", $timestamp),
                  ];
          };
          $eventPresentation = [
              "RECEIPT" => ["label" => "Receipt", "class" => "audit-event-receipt", "icon" => "fa-truck"],
              "IN_STOCK" => ["label" => "In Stock", "class" => "audit-event-in-stock", "icon" => "fa-cubes"],
              "ASSET_UPDATE" => ["label" => "Asset Update", "class" => "audit-event-update", "icon" => "fa-pencil"],
              "ASSIGN" => ["label" => "Assignment", "class" => "audit-event-assign", "icon" => "fa-share"],
              "RETURN" => ["label" => "Return", "class" => "audit-event-return", "icon" => "fa-reply"],
              "ACCESSORY_RECEIPT" => ["label" => "Accessory Receipt", "class" => "audit-event-receipt", "icon" => "fa-plus-circle"],
              "ACCESSORY_ISSUE" => ["label" => "Accessory Issue", "class" => "audit-event-assign", "icon" => "fa-minus-circle"],
              "ACCESSORY_RETURN" => ["label" => "Accessory Return", "class" => "audit-event-return", "icon" => "fa-reply"],
              "ACCESSORY_ADJUSTMENT_IN" => ["label" => "Accessory Adjustment +", "class" => "audit-event-approved", "icon" => "fa-plus"],
              "ACCESSORY_ADJUSTMENT_OUT" => ["label" => "Accessory Adjustment -", "class" => "audit-event-danger", "icon" => "fa-minus"],
              "SCRAP_APPROVED" => ["label" => "Scrap Approved", "class" => "audit-event-approved", "icon" => "fa-recycle"],
              "SCRAP_REJECTED" => ["label" => "Scrap Rejected", "class" => "audit-event-danger", "icon" => "fa-ban"],
              "DELETE" => ["label" => "Deletion", "class" => "audit-event-danger", "icon" => "fa-trash"],
          ];
          $filterQuery = [
        "from" => $data["from"],
        "to" => $data["to"],
        "assetNum" => $data["assetNum"],
        "eventType" => $data["eventType"],
        "category" => $data["category"],
        "department" => $data["department"],
        "search" => $data["search"],
        "actor" => $data["actor"],
        "assignedTo" => $data["assignedTo"],
        "condition" => $data["condition"],
    ]; ?>
          <form method="get" action="<?= BASEURL ?>/Asset/auditReport">
            <div class="row">
              <div class="col-md-2 form-group"><label>From</label><input type="date" name="from"
                  class="form-control" value="<?= htmlspecialchars(
            $data["from"],
        ) ?>" required></div>
              <div class="col-md-2 form-group"><label>To</label><input type="date" name="to"
                  class="form-control" value="<?= htmlspecialchars(
            $data["to"],
        ) ?>" required></div>
              <div class="col-md-4 form-group">
                <label>Asset Number</label>
                <input type="text" name="assetNum" class="form-control" maxlength="30"
                  value="<?= htmlspecialchars(
                      (string) $data["assetNum"],
                      ENT_QUOTES,
                      "UTF-8",
                  ) ?>" placeholder="Enter exact asset number or leave blank">
              </div>
              <div class="col-md-2 form-group"><label>Event</label><select name="eventType"
                  class="form-control">
                  <option value="">All events</option><?php
        $eventOptions = [
                "RECEIPT" => "Receipt",
                "IN_STOCK" => "In Stock",
                "ASSIGN" => "Assignment",
                "RETURN" => "Return",
                "ASSET_UPDATE" => "Asset Update",
                "DELETE" => "Deletion",
            ];
        if (!empty($data["workflowUiEnabled"])) {
            $eventOptions = array_merge(
                array_slice($eventOptions, 0, 3, true),
                [
                    "SCRAP_APPROVED" => "Scrap Approved",
                    "SCRAP_REJECTED" => "Scrap Rejected",
                ],
                array_slice($eventOptions, 3, null, true),
            );
        }
        if (!empty($data["accessoryInventoryEnabled"])) {
            $eventOptions += [
                "ACCESSORY_RECEIPT" => "Accessory Receipt",
                "ACCESSORY_ISSUE" => "Accessory Issue",
                "ACCESSORY_RETURN" => "Accessory Return",
                "ACCESSORY_ADJUSTMENT_IN" => "Accessory Adjustment Increase",
                "ACCESSORY_ADJUSTMENT_OUT" => "Accessory Adjustment Decrease",
            ];
        }
        foreach ($eventOptions as $value => $label
        ): ?><option value="<?= $value ?>" <?= $data["eventType"] === $value
    ? "selected"
    : "" ?>><?= $label ?></option><?php endforeach; ?>
                </select></div>
              <div class="col-md-2 form-group"><label>Category</label><select name="category"
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
              <div class="col-md-3 form-group"><label>Department</label><select name="department"
                  class="form-control searchable-select" data-placeholder="All departments">
                  <option value="">All departments</option><?php foreach (
            $data["departments"]
            as $department
        ): ?><option value="<?= htmlspecialchars(
    $department["deptName"],
    ENT_QUOTES,
    "UTF-8",
) ?>" <?= $data["department"] === $department["deptName"] ? "selected" : "" ?>><?= htmlspecialchars(
    $department["deptName"],
) ?></option><?php endforeach; ?>
                </select></div>
              <div class="col-md-3 form-group"><label>Search</label><input type="text" name="search" class="form-control" maxlength="100" value="<?= htmlspecialchars($data["search"]) ?>" placeholder="Asset, reference, description or details"></div>
              <div class="col-md-3 form-group"><label>Actor</label><input type="text" name="actor" class="form-control" maxlength="100" value="<?= htmlspecialchars($data["actor"]) ?>" placeholder="Issued, received, accepted or approved by"></div>
              <div class="col-md-3 form-group"><label>Assigned / Accepted For</label><input type="text" name="assignedTo" class="form-control" maxlength="100" value="<?= htmlspecialchars($data["assignedTo"]) ?>" placeholder="Employee or department"></div>
              <div class="col-md-3 form-group"><label>Condition / Decision</label><input type="text" name="condition" class="form-control" maxlength="50" value="<?= htmlspecialchars($data["condition"]) ?>" placeholder="Good, damaged, accepted, approved..."></div>
              <div class="col-md-12 form-group">
                <button class="btn btn-primary" type="submit"><i class="fa fa-filter"></i> Apply
                  Filters</button>
                <a class="btn btn-warning" href="<?= BASEURL ?>/Asset/auditReport"><i
                    class="fa fa-refresh"></i> Reset Filters</a>
                <a class="btn btn-success" href="<?= BASEURL ?>/Asset/exportAuditExcel?<?= http_build_query(
    $filterQuery,
) ?>"><i class="fa fa-file-excel-o"></i> Export Excel</a>
                <a class="btn btn-danger" href="<?= BASEURL ?>/Asset/exportAuditPdf?<?= http_build_query(
    $filterQuery,
) ?>"><i class="fa fa-file-pdf-o"></i> Export PDF</a>
              </div>
            </div>
          </form>
          <p class="help-block">
            Showing up to 1,000 audit events (<?= count($data["rows"]) ?> loaded) from
            <?= htmlspecialchars($data["from"]) ?> through <?= htmlspecialchars($data["to"]) ?>
            <?= $data["assetNum"] !== "" ? " | Asset: " . htmlspecialchars($data["assetNum"]) : "" ?>
            <?= $data["eventType"] !== "" ? " | Event: " . htmlspecialchars($data["eventType"]) : "" ?>
            <?= $data["category"] !== "" ? " | Category: " . htmlspecialchars($data["category"]) : "" ?>
            <?= $data["department"] !== "" ? " | Department: " . htmlspecialchars($data["department"]) : "" ?>.
            <?= $data["search"] !== "" ? " | Search: " . htmlspecialchars($data["search"]) : "" ?>
            <?= $data["actor"] !== "" ? " | Actor: " . htmlspecialchars($data["actor"]) : "" ?>
            <?= $data["assignedTo"] !== "" ? " | Assigned/accepted for: " . htmlspecialchars($data["assignedTo"]) : "" ?>
            <?= $data["condition"] !== "" ? " | Condition: " . htmlspecialchars($data["condition"]) : "" ?>
          </p>
        </div>
      </div>
    </div>
    <div class="space-12"></div>
    <div class="table-responsive">
      <table id="audit-report-table" class="table table-bordered table-striped table-hover">
        <thead>
          <tr>
            <th>Date</th>
            <th>Event</th>
            <th>Referrence NO.</th>
            <th>Asset / Accessory</th>
            <th>Description</th>
            <th>Category</th>
            <th>Purchase Date</th>
            <th>Warranty Expiry</th>
            <th>Actor</th>
            <th>Assigned To</th>
            <th>Department</th>
            <th>Condition</th>
            <th class="audit-details">Details</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($data["rows"] as $r):
              $eventDateParts = $formatAuditDateParts($r["eventDate"] ?? "");
              $eventTypeKey = strtoupper((string) ($r["eventType"] ?? ""));
              $eventMeta = $eventPresentation[$eventTypeKey] ?? [
                  "label" => str_replace("_", " ", $eventTypeKey),
                  "class" => "audit-event-default",
                  "icon" => "fa-circle-o",
              ];
              ?><tr>
            <td class="audit-date"><strong><?= htmlspecialchars(
                $eventDateParts["date"],
            ) ?></strong><?php if ($eventDateParts["time"] !== ""): ?><span><i
                  class="fa fa-clock-o"></i> <?= htmlspecialchars(
                      $eventDateParts["time"],
                  ) ?></span><?php endif; ?></td>
            <td><span class="audit-event-badge <?= $eventMeta["class"] ?>"><i
                  class="fa <?= $eventMeta["icon"] ?>"></i><?= htmlspecialchars(
                      $eventMeta["label"],
                  ) ?></span></td>
            <td><?= htmlspecialchars($r["referenceNo"]) ?></td>
            <td><?php if (!empty($r["assetNum"])): ?><?php if (strpos($eventTypeKey, "ACCESSORY_") === 0): ?><strong><?= htmlspecialchars($r["assetNum"]) ?></strong><?php else: ?><button type="button" class="btn btn-link btn-xs" data-asset-details="<?= htmlspecialchars($r["assetNum"], ENT_QUOTES, "UTF-8") ?>"><?= htmlspecialchars($r["assetNum"]) ?></button><?php endif; ?><?php else: ?>-<?php endif; ?></td>
            <td><?= htmlspecialchars($r["assetDescription"]) ?></td>
            <td><?= htmlspecialchars(
    $r["category"] ?? "-",
) ?></td>
            <td><?= htmlspecialchars($r["purchaseDate"] ?? "-") ?></td>
            <td><?= htmlspecialchars(
    $r["warrantyExpiry"] ?? "-",
) ?></td>
            <td><?= htmlspecialchars($r["actor"] ?? "-") ?></td>
            <td><?= htmlspecialchars(
    $r["assignedTo"] ?? "-",
) ?></td>
            <td><?= htmlspecialchars($r["department"] ?? "-") ?></td>
            <td><?= htmlspecialchars(
    $r["conditionInfo"] ?? "-",
) ?></td>
            <td class="audit-details"><?php if (
    ($r["eventType"] ?? "") ===
    "RECEIPT"
): ?><strong>Supplier:</strong> <?= htmlspecialchars(
    $r["supplierName"] ?? "-",
) ?><br><strong>PO Number:</strong> <?= htmlspecialchars(
    $r["poNumber"] ?? "-",
) ?><br><strong>Receipt Note:</strong> <?= nl2br(
    htmlspecialchars($r["receiptNote"] ?? "-"),
) ?><br><strong>Invoice:</strong> <?php if (!empty($r["invoicePdf"])):

    $invoiceLabel =
        trim((string) ($r["invoiceNumber"] ?? "")) ?:
        pathinfo((string) $r["invoicePdf"], PATHINFO_FILENAME);
    $invoiceDownload = preg_replace("/[^A-Za-z0-9_-]+/", "_", $invoiceLabel) . ".pdf";
    ?><a class="btn btn-xs btn-danger" href="<?= BASEURL ?>/Asset/downloadInvoice/<?= rawurlencode(
    basename($r["invoicePdf"]),
) ?>" target="_blank" rel="noopener" download="<?= htmlspecialchars(
    $invoiceDownload,
    ENT_QUOTES,
    "UTF-8",
) ?>"><i class="fa fa-file-pdf-o"></i> <?= htmlspecialchars($invoiceLabel) ?></a>
              <?php else: ?>
              <?= htmlspecialchars($r["invoiceNumber"] ?? "No PDF") ?>
              <?php endif; ?>
              <?php else: ?>
              <?= htmlspecialchars($r["details"] ?? "-") ?>
              <?php endif; ?></td>
          </tr><?php endforeach; ?>
          <?php if (
      !$data["rows"]
  ): ?><tr>
            <td colspan="13" class="text-center">No audit events in the selected period.</td>
          </tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require __DIR__ . "/../templates/asset_details_modal.php"; ?>
