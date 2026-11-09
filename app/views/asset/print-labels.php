<?php
$labels = array_values($data["labels"] ?? []);
$requestedCount = (int) ($data["requestedCount"] ?? count($labels));
$e = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
};
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Bulk Asset Stickers - <?= count($labels) ?> labels</title>
  <?php require dirname(__DIR__) . "/templates/favicon.php"; ?>
  <style>
    * { box-sizing: border-box }
    html, body {
      margin: 0;
      background: #edf0f2;
      color: #111;
      font-family: Arial, sans-serif
    }
    .toolbar {
      position: sticky;
      top: 0;
      z-index: 2;
      padding: 12px 16px;
      background: #fff;
      border-bottom: 1px solid #ccd4d8;
      text-align: center
    }
    .toolbar strong { color: #00594d }
    .toolbar p { margin: 6px 0 0; color: #52616a; font-size: 12px }
    .toolbar button {
      margin-left: 10px;
      padding: 9px 18px;
      border: 0;
      border-radius: 4px;
      background: #00594d;
      color: #fff;
      cursor: pointer;
      font-size: 14px;
      font-weight: 700
    }
    .warning {
      margin: 8px auto 0;
      max-width: 720px;
      color: #8a4b08;
      font-weight: 700
    }
    .label-preview {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 10px;
      padding: 16px
    }
    .sticker-page {
      width: 70mm;
      height: 35mm;
      flex: 0 0 35mm;
      background: #fff
    }
    .sticker {
      --sticker-logo-width: 24mm;
      --sticker-logo-max-height: 14mm;
      width: 70mm;
      height: 35mm;
      display: grid;
      grid-template-columns: 35mm 1fr;
      align-items: center;
      overflow: hidden;
      border: 1px solid #111;
      background: #fff
    }
    .qr-side {
      height: 100%;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 2mm
    }
    .qr-side img {
      display: block;
      width: 23mm;
      height: 23mm;
      image-rendering: pixelated
    }
    .asset-number {
      width: 100%;
      margin-top: .7mm;
      overflow: hidden;
      font-size: 10.5pt;
      font-weight: 800;
      line-height: 1;
      letter-spacing: .5px;
      text-align: center;
      text-overflow: ellipsis;
      white-space: nowrap
    }
    .asset-category {
      width: 100%;
      margin-top: .5mm;
      overflow: hidden;
      color: #555;
      font-size: 5.5pt;
      font-weight: 600;
      line-height: 1;
      text-align: center;
      text-overflow: ellipsis;
      white-space: nowrap
    }
    .brand-side {
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 3mm 3mm 3mm 1mm;
      border-left: .3mm solid #d8d8d8
    }
    .brand-side img {
      display: block;
      width: var(--sticker-logo-width);
      max-width: 100%;
      max-height: var(--sticker-logo-max-height);
      object-fit: contain
    }
    @page { size: 70mm 35mm; margin: 0 }
    @media print {
      html, body { width: 70mm; margin: 0; background: #fff }
      .toolbar { display: none !important }
      .label-preview { display: block; padding: 0 }
      .sticker-page {
        width: 70mm;
        height: 35mm;
        margin: 0;
        break-after: page;
        page-break-after: always
      }
      .sticker-page:last-child {
        break-after: auto;
        page-break-after: auto
      }
      .sticker { border: 0 }
    }
  </style>
</head>

<body>
  <div class="toolbar">
    <strong><?= count($labels) ?> adhesive asset label<?= count($labels) === 1 ? "" : "s" ?></strong>
    <button type="button" onclick="window.print()">Print Selected Labels</button>
    <p>Printer media: 70 &times; 35 mm adhesive labels. Use Actual Size / 100%, zero margins, disable Fit to Page, and turn off browser Headers and footers.</p>
    <?php if (count($labels) !== $requestedCount): ?>
    <div class="warning">Some selected assets were skipped because they are no longer in stock.</div>
    <?php endif; ?>
  </div>

  <main class="label-preview">
    <?php foreach ($labels as $asset):
        $assetNum = trim((string) ($asset["assetNum"] ?? ""));
        $categoryName = trim(
            (string) ($asset["categoryName"] ?? ($asset["assetKat"] ?? "")),
        ); ?>
    <div class="sticker-page">
      <section class="sticker" aria-label="UNICOIL asset sticker <?= $e($assetNum) ?>">
        <div class="qr-side">
          <img src="<?= $e($asset["qrDataUri"] ?? "") ?>" alt="QR code for asset <?= $e($assetNum) ?>">
          <div class="asset-number"><?= $e($assetNum) ?></div>
          <?php if ($categoryName !== ""): ?>
          <div class="asset-category"><?= $e($categoryName) ?></div>
          <?php endif; ?>
        </div>
        <div class="brand-side">
          <img src="<?= BASEURL ?>/public/img/unicoil-logo.png" alt="UNICOIL logo">
        </div>
      </section>
    </div>
    <?php endforeach; ?>
  </main>
</body>

</html>
