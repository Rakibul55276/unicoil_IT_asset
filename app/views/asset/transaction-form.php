<?php
$record = $data["record"];
$isReturn = $data["formType"] === "RETURN";
$e = static function ($value) {
    return htmlspecialchars((string) ($value ?? "-"), ENT_QUOTES, "UTF-8");
};
$holder = trim((string) ($record["employeeName"] ?? ($record["acknowledged_by"] ?? "")));
if (($record["assignee_type"] ?? "") === "DEPARTMENT" && !empty($record["deptName"])) {
    $holder = $record["deptName"] . ($holder !== "" ? " / " . $holder : "");
}
$reference = $isReturn ? $record["return_no"] : $record["issue_no"];
$defaultImageUrl = BASEURL . "/public/img/no-image.png";
$imageName = basename(trim((string) ($record["assetImage"] ?? "")));
$imageUrl =
    $imageName !== ""
        ? BASEURL . "/Asset/media/" . rawurlencode($imageName)
        : $defaultImageUrl;
?>
<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <title><?= $isReturn
    ? "Asset Return"
    : "Asset Assignment" ?> Form - <?= $e($reference) ?></title>
  <?php require dirname(__DIR__) . "/templates/favicon.php"; ?>
  <style>
    body {
      font-family: Arial, sans-serif;
      color: #222;
      margin: 24px
    }

    .toolbar {
      text-align: right
    }

    .header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      border-bottom: 2px solid #222;
      padding-bottom: 12px
    }

    .header h1 {
      margin: 0 0 5px
    }

    .header-media {
      display: flex;
      align-items: flex-start;
      gap: 18px
    }

    .asset-photo {
      text-align: center
    }

    .asset-photo img {
      width: 145px;
      height: 145px;
      object-fit: contain;
      border: 1px solid #aaa;
      background: #f5f5f5
    }

    .asset-photo small,
    .qr small {
      display: block;
      max-width: 170px
    }

    .qr {
      text-align: center
    }

    .qr img {
      width: 145px;
      height: 145px
    }

    .meta {
      color: #666
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin: 18px 0
    }

    th,
    td {
      border: 1px solid #999;
      padding: 9px;
      text-align: left;
      vertical-align: top
    }

    th {
      width: 20%;
      background: #f0f0f0
    }

    .signatures {
      display: flex;
      gap: 40px;
      margin-top: 55px
    }

    .signature {
      flex: 1;
      border-top: 1px solid #222;
      padding-top: 8px;
      text-align: center
    }

    .document-credit {
      position: fixed;
      right: 4mm;
      bottom: 2mm;
      color: #9aa0a6;
      font-size: 6px;
      font-weight: 400;
      letter-spacing: .02em;
    }

    @media print {
      .toolbar {
        display: none
      }

      body {
        margin: 8mm
      }

      .qr img,
      .asset-photo img {
        width: 32mm;
        height: 32mm
      }
    }
  </style>
</head>

<body>
  <div class="toolbar"><button onclick="window.print()">Print Form</button></div>
  <div class="header">
    <div>
      <h1>IT Asset <?= $isReturn
    ? "Return"
    : "Assignment" ?> Form</h1>
      <div class="meta">Reference: <?= $e(
     $reference,
 ) ?><br>Generated: <?= date("Y-m-d H:i") ?></div>
    </div>
    <?php if (false): ?>
    <!-- Temporarily hidden: asset picture and transaction QR. -->
    <div class="header-media">
      <div class="asset-photo"><img src="<?= $e(
    $imageUrl,
) ?>" alt="Asset image" onerror="this.onerror=null;this.src='<?= $e(
    $defaultImageUrl,
) ?>';"><small>Asset image</small></div>
      <div class="qr"><img src="<?= $e(
    $data["qrDataUri"],
) ?>" alt="Transaction QR code" onerror="this.onerror=null;this.src='<?= $e(
    $defaultImageUrl,
) ?>';"><small>Scan to view the transaction summary</small></div>
    </div>
    <?php endif; ?>
  </div>

  <h2>Asset Details</h2>
  <table>
    <tr>
      <th>Asset Number</th>
      <td><?= $e($record["assetNum"]) ?></td>
      <th>Category</th>
      <td><?= $e(
    $record["categoryName"],
) ?></td>
    </tr>
    <tr>
      <th>Description</th>
      <td><?= $e(
    $record["assetDescription"],
) ?></td>
      <th>Serial Number</th>
      <td><?= $e($record["serial_number"]) ?></td>
    </tr>
    <tr>
      <th>Manufacturer</th>
      <td><?= $e($record["manufacturer"]) ?></td>
      <th>Model</th>
      <td><?= $e(
    $record["model"],
) ?></td>
    </tr>
  </table>

  <h2><?= $isReturn ? "Return" : "Assignment" ?> Details</h2>
  <?php if ($isReturn): ?>
  <table>
    <tr>
      <th>Return Number</th>
      <td><?= $e($record["return_no"]) ?></td>
      <th>Return Date</th>
      <td><?= $e(
    $record["return_date"],
) ?></td>
    </tr>
    <tr>
      <th>Original Issue</th>
      <td><?= $e($record["issue_no"]) ?></td>
      <th>Issue Date</th>
      <td><?= $e(
    $record["issue_date"],
) ?></td>
    </tr>
    <tr>
      <th>Returned By</th>
      <td><?= $e($holder) ?></td>
      <th>Assignment Type</th>
      <td><?= $e(
    $record["assignee_type"],
) ?></td>
    </tr>
    <tr>
      <th>Return Type</th>
      <td><?= $e($record["return_type"]) ?></td>
      <th>Condition In</th>
      <td><?= $e(
    $record["condition_in"],
) ?></td>
    </tr>
    <tr>
      <th>Remarks</th>
      <td><?= $e($record["reason"] ?? $record["remarks"] ?? "") ?></td>
      <th>Scrap Reference</th>
      <td><?= $e(
    $record["scrap_reference"],
) ?></td>
    </tr>
    <tr>
      <th>Received By</th>
      <td colspan="3"><?= $e($record["received_by"]) ?></td>
    </tr>
  </table>
  <?php else: ?>
  <table>
    <tr>
      <th>Issue Number</th>
      <td><?= $e($record["issue_no"]) ?></td>
      <th>Issue Date</th>
      <td><?= $e(
    $record["issue_date"],
) ?></td>
    </tr>
    <tr>
      <th>Assigned To</th>
      <td><?= $e($holder) ?></td>
      <th>Assignment Type</th>
      <td><?= $e(
    $record["assignee_type"],
) ?></td>
    </tr>
    <tr>
      <th>Condition Out</th>
      <td><?= $e(
    $record["condition_out"],
) ?></td>
      <th>Expected Return</th>
      <td><?= $e($record["expected_return_date"]) ?></td>
    </tr>
    <tr>
      <th>Remarks</th>
      <td><?= $e($record["purpose"] ?? $record["remarks"] ?? "") ?></td>
      <th>Issued By</th>
      <td><?= $e(
    $record["issued_by"],
) ?></td>
    </tr>
  </table>
  <?php endif; ?>

  <div class="signatures">
    <div class="signature"><?= $isReturn
    ? "Returned By"
    : "Asset Custodian" ?> Signature</div>
    <div class="signature"><?= $isReturn
     ? "Received By"
     : "Issued By" ?> Signature</div>
    <div class="signature">Date</div>
  </div>
  <div class="document-credit">Powered by IT (Rakib)</div>
</body>

</html>
