<?php
$asset = $data["asset"] ?? [];
$transactions = $data["transactions"] ?? [];
$assetNumber = trim((string) ($asset["assetNum"] ?? "")) ?: "-";
$generatedAt = date("Y-m-d H:i");
$receivedDate = trim((string) ($asset["received_date"] ?? ""));
if ($receivedDate === "") {
    $receivedDate = trim((string) ($asset["createdDate"] ?? "")) ?: "-";
}

$escape = static fn($value): string => htmlspecialchars(
    trim((string) ($value ?? "")) !== "" ? (string) $value : "-",
    ENT_QUOTES,
    "UTF-8",
);
$assignmentCount = count($transactions);
$returnCount = count(
    array_filter(
        $transactions,
        static fn(array $transaction): bool =>
            trim((string) ($transaction["return_no"] ?? "")) !== "",
    ),
);
$eventCount = 1 + $assignmentCount + $returnCount;
$currentCondition = trim((string) ($asset["asset_condition"] ?? "")) ?: "NEW";
$currentStatus = trim((string) ($asset["assetStat"] ?? "")) ?: "-";
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Asset <?= $escape($assetNumber) ?> Transaction History</title>
  <?php require dirname(__DIR__) . "/templates/favicon.php"; ?>
  <style>
    :root {
      --brand-green: #075b50;
      --brand-green-light: #0f7b69;
      --brand-red: #cf2036;
      --ink: #142b35;
      --muted: #60727a;
      --line: #c8d8d5;
      --surface: #f2f7f6;
      --row-alt: #f5f9fb;
    }

    * {
      box-sizing: border-box;
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
    }

    @page {
      size: A4 landscape;
      margin: 10mm;
    }

    html,
    body {
      min-height: 100%;
      margin: 0;
      background: #edf2f5;
      color: var(--ink);
      font-family: Arial, Helvetica, sans-serif;
      font-size: 13px;
      line-height: 1.4;
    }

    .toolbar {
      width: min(1120px, calc(100% - 32px));
      margin: 18px auto 8px;
      text-align: right;
    }

    .print-button {
      padding: 9px 18px;
      border: 0;
      border-radius: 4px;
      background: var(--brand-red);
      color: #fff;
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
    }

    .report {
      width: min(1120px, calc(100% - 32px));
      min-height: 730px;
      margin: 0 auto 24px;
      background: #fff;
      box-shadow: 0 10px 32px rgba(17, 48, 57, .13);
    }

    .report-header {
      display: flex;
      min-height: 112px;
      align-items: center;
      justify-content: space-between;
      gap: 24px;
      padding: 22px 34px;
      background: var(--brand-green);
      color: #fff;
    }

    .report-heading {
      min-width: 0;
    }

    .report-heading h1 {
      margin: 0 0 7px;
      font-size: 28px;
      line-height: 1.1;
      letter-spacing: .3px;
      text-transform: uppercase;
    }

    .report-heading p {
      margin: 0;
      color: #e3f0ed;
      font-size: 13px;
    }

    .header-side {
      display: flex;
      flex: 0 0 auto;
      align-items: center;
      gap: 22px;
    }

    .header-meta {
      text-align: right;
      font-size: 12px;
      line-height: 1.65;
    }

    .header-meta strong {
      display: block;
      color: #fff;
      font-size: 13px;
    }

    .logo-badge {
      display: flex;
      width: 112px;
      height: 72px;
      align-items: center;
      justify-content: center;
      border: 1px solid #b9d6d0;
      background: #fff;
    }

    .logo-badge img {
      display: block;
      width: 88px;
      max-height: 58px;
      object-fit: contain;
    }

    .report-body {
      padding: 20px 34px 30px;
    }

    .summary-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 10px;
      margin-bottom: 18px;
    }

    .summary-card {
      min-height: 52px;
      padding: 8px 11px;
      border-left: 6px solid #2f6f9f;
      background: #f1f5f8;
    }

    .summary-card:nth-child(2) {
      border-left-color: #198060;
    }

    .summary-card:nth-child(3) {
      border-left-color: #e59a23;
    }

    .summary-card:nth-child(4) {
      border-left-color: var(--brand-red);
    }

    .summary-label {
      display: block;
      margin-bottom: 2px;
      color: #536974;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
    }

    .summary-value {
      color: var(--brand-green);
      font-size: 18px;
      font-weight: 700;
      overflow-wrap: anywhere;
    }

    .section-title {
      margin: 18px 0 8px;
      padding: 8px 11px;
      border-left: 5px solid var(--brand-red);
      background: #eaf3f1;
      color: var(--brand-green);
      font-size: 14px;
      font-weight: 700;
    }

    table {
      width: 100%;
      margin: 0;
      border-collapse: collapse;
      table-layout: fixed;
    }

    th,
    td {
      padding: 8px 9px;
      border: 1px solid var(--line);
      text-align: left;
      vertical-align: top;
      overflow-wrap: anywhere;
      word-break: normal;
    }

    th {
      background: var(--brand-green);
      color: #fff;
      font-size: 12px;
      font-weight: 700;
    }

    tbody tr:nth-child(odd) td {
      background: var(--row-alt);
    }

    .asset-summary th {
      width: 17%;
    }

    .asset-summary td {
      width: 33%;
    }

    .transactions th:nth-child(1) {
      width: 19%;
    }

    .transactions th:nth-child(2) {
      width: 18%;
    }

    .transactions th:nth-child(3) {
      width: 24%;
    }

    .transactions th:nth-child(4) {
      width: 17%;
    }

    .transactions th:nth-child(5) {
      width: 22%;
    }

    .detail-line {
      display: block;
      margin-bottom: 2px;
    }

    .detail-line:last-child {
      margin-bottom: 0;
    }

    .detail-label {
      font-weight: 700;
    }

    .status-badge {
      display: inline-block;
      padding: 3px 7px;
      border-radius: 3px;
      background: #dceee9;
      color: var(--brand-green);
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
    }

    .empty-row {
      padding: 22px;
      color: var(--muted);
      text-align: center;
    }

    .report-footer {
      display: flex;
      justify-content: space-between;
      margin-top: 24px;
      padding-top: 9px;
      border-top: 1px solid var(--line);
      color: var(--muted);
      font-size: 10px;
    }

    @media screen and (max-width: 760px) {
      .report-header,
      .header-side {
        align-items: flex-start;
        flex-direction: column;
      }

      .header-meta {
        text-align: left;
      }

      .summary-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }
    }

    @media print {
      html,
      body {
        background: #fff;
        font-size: 10px;
      }

      .toolbar {
        display: none !important;
      }

      .report {
        width: 100%;
        min-height: 0;
        margin: 0;
        box-shadow: none;
      }

      .report-header {
        min-height: 88px;
        padding: 14px 20px;
        align-items: center;
        flex-direction: row;
      }

      .header-side {
        align-items: center;
        flex-direction: row;
      }

      .header-meta {
        text-align: right;
      }

      .report-heading h1 {
        font-size: 22px;
      }

      .report-body {
        padding: 14px 20px 8px;
      }

      .logo-badge {
        width: 96px;
        height: 60px;
      }

      .logo-badge img {
        width: 74px;
        max-height: 48px;
      }

      .summary-card {
        min-height: 44px;
      }

      .summary-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
      }

      .summary-value {
        font-size: 15px;
      }

      th,
      td {
        padding: 6px 7px;
      }

      thead {
        display: table-header-group;
      }

      tr,
      .summary-grid,
      .asset-summary {
        break-inside: avoid;
        page-break-inside: avoid;
      }
    }
  </style>
</head>

<body>
  <div class="toolbar">
    <button type="button" class="print-button" onclick="window.print()">Print Report</button>
  </div>

  <main class="report">
    <header class="report-header">
      <div class="report-heading">
        <h1>IT Asset Transaction History</h1>
        <p>Complete lifecycle activity for asset <?= $escape($assetNumber) ?></p>
      </div>
      <div class="header-side">
        <div class="header-meta">
          <strong>Asset: <?= $escape($assetNumber) ?></strong>
          Generated: <?= $escape($generatedAt) ?>
        </div>
        <div class="logo-badge">
          <img src="<?= BASEURL ?>/public/img/unicoil-logo.jpg" alt="UNICOIL">
        </div>
      </div>
    </header>

    <div class="report-body">
      <section class="summary-grid" aria-label="Transaction summary">
        <div class="summary-card">
          <span class="summary-label">Total Events</span>
          <span class="summary-value"><?= $eventCount ?></span>
        </div>
        <div class="summary-card">
          <span class="summary-label">Assignments</span>
          <span class="summary-value"><?= $assignmentCount ?></span>
        </div>
        <div class="summary-card">
          <span class="summary-label">Returns</span>
          <span class="summary-value"><?= $returnCount ?></span>
        </div>
        <div class="summary-card">
          <span class="summary-label">Current Condition</span>
          <span class="summary-value"><?= $escape($currentCondition) ?></span>
        </div>
      </section>

      <div class="section-title">Asset Summary</div>
      <table class="asset-summary">
        <tbody>
          <tr>
            <th>Asset Number</th>
            <td><?= $escape($assetNumber) ?></td>
            <th>Description</th>
            <td><?= $escape($asset["Description"] ?? "-") ?></td>
          </tr>
          <tr>
            <th>Category</th>
            <td><?= $escape($asset["categoryName"] ?? ($asset["assetKat"] ?? "-")) ?></td>
            <th>Serial Number</th>
            <td><?= $escape($asset["serial_number"] ?? "-") ?></td>
          </tr>
          <tr>
            <th>Received Date</th>
            <td><?= $escape($receivedDate) ?></td>
            <th>Receipt Number</th>
            <td><?= $escape($asset["receipt_no"] ?? "-") ?></td>
          </tr>
          <tr>
            <th>Location</th>
            <td><?= $escape($asset["locationName"] ?? ($asset["location"] ?? "-")) ?></td>
            <th>Current Status</th>
            <td><span class="status-badge"><?= $escape($currentStatus) ?></span></td>
          </tr>
        </tbody>
      </table>

      <div class="section-title">Issue and Return Transactions</div>
      <table class="transactions">
        <thead>
          <tr>
            <th>Issue</th>
            <th>Assigned To</th>
            <th>Issue Details</th>
            <th>Return</th>
            <th>Return Details</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($transactions as $transaction):
              $isDepartment =
                  strtoupper((string) ($transaction["assignee_type"] ?? "")) ===
                  "DEPARTMENT";
              $assignedTo = $isDepartment
                  ? $transaction["deptName"] ?? "-"
                  : $transaction["employee_name"] ??
                      ($transaction["acknowledged_by"] ?? "-");
              ?>
          <tr>
            <td>
              <span class="detail-line"><strong><?= $escape($transaction["issue_no"] ?? "-") ?></strong></span>
              <span class="detail-line"><?= $escape($transaction["issue_date"] ?? "-") ?></span>
            </td>
            <td>
              <span class="detail-line"><span class="detail-label">Type:</span> <?= $escape($transaction["assignee_type"] ?? "-") ?></span>
              <span class="detail-line"><?= $escape($assignedTo) ?></span>
            </td>
            <td>
              <span class="detail-line"><span class="detail-label">Condition:</span> <?= $escape($transaction["condition_out"] ?? "-") ?></span>
              <span class="detail-line"><span class="detail-label">Status:</span> <?= $escape($transaction["issue_status"] ?? "-") ?></span>
              <span class="detail-line"><span class="detail-label">Remarks:</span> <?= $escape($transaction["purpose"] ?? ($transaction["remarks"] ?? "-")) ?></span>
            </td>
            <td>
              <span class="detail-line"><strong><?= $escape($transaction["return_no"] ?? "-") ?></strong></span>
              <span class="detail-line"><?= $escape($transaction["return_date"] ?? "-") ?></span>
            </td>
            <td>
              <span class="detail-line"><span class="detail-label">Type:</span> <?= $escape($transaction["return_type"] ?? "-") ?></span>
              <span class="detail-line"><span class="detail-label">Condition:</span> <?= $escape($transaction["condition_in"] ?? "-") ?></span>
              <span class="detail-line"><span class="detail-label">Remarks:</span> <?= $escape($transaction["reason"] ?? ($transaction["remarks"] ?? "-")) ?></span>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$transactions): ?>
          <tr>
            <td colspan="5" class="empty-row">No issue or return transactions.</td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>

      <footer class="report-footer">
        <span>UNICOIL IT Asset Management</span>
        <span>Asset <?= $escape($assetNumber) ?> · Generated <?= $escape($generatedAt) ?></span>
        <span style="font-size:6px;font-weight:400;opacity:.38;">Powered by IT (Rakib)</span>
      </footer>
    </div>
  </main>
</body>

</html>
