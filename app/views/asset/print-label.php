<?php
$assetNum = trim((string) ($data["asset"]["assetNum"] ?? ""));
$categoryName = trim(
    (string) (
        $data["asset"]["categoryName"] ??
        $data["asset"]["assetKatName"] ??
        $data["asset"]["assetKat"] ??
        ""
    ),
);
$e = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
};
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Asset Sticker - <?= $e($assetNum) ?></title>
  <?php require dirname(__DIR__) . "/templates/favicon.php"; ?>
  <style>
    * {
      box-sizing: border-box
    }

    html,
    body {
      margin: 0;
      background: #edf0f2;
      font-family: Arial, sans-serif;
      color: #111
    }

    .toolbar {
      padding: 14px;
      text-align: center
    }

    .toolbar button {
      padding: 9px 18px;
      border: 0;
      border-radius: 4px;
      background: #00594d;
      color: #fff;
      font-size: 15px;
      font-weight: 700;
      cursor: pointer
    }

    .toolbar p {
      margin: 7px 0 0;
      color: #52616a;
      font-size: 12px
    }

    .sheet {
      display: flex;
      justify-content: center;
      padding: 8px
    }

    .sticker {
      /* Adjust these values when a different physical logo size is required. */
      --sticker-logo-width: 24mm;
      --sticker-logo-max-height: 14mm;
      width: 70mm;
      height: 35mm;
      display: grid;
      grid-template-columns: 35mm 1fr;
      align-items: center;
      background: #fff;
      border: 1px solid #111;
      overflow: hidden
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
      text-align: center;
      font-size: 10.5pt;
      font-weight: 800;
      line-height: 1;
      letter-spacing: .5px;
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

    @page {
      size: 70mm 35mm;
      margin: 0
    }

    @media print {

      html,
      body {
        width: 70mm;
        height: 35mm;
        background: #fff
      }

      .toolbar {
        display: none !important
      }

      .sheet {
        display: block;
        padding: 0
      }

      .sticker {
        border: 0
      }
    }
  </style>
</head>

<body>
  <div class="toolbar">
    <button type="button" onclick="window.print()">Print Asset Sticker</button>
    <p>Printer media: 70 &times; 35 mm adhesive label. Use Actual Size / 100%, zero margins, disable Fit to Page, and turn off browser Headers and footers.</p>
  </div>
  <div class="sheet">
    <section class="sticker" aria-label="UNICOIL asset sticker">
      <div class="qr-side"><img src="<?= $e($data["qrDataUri"]) ?>" alt="QR code for asset <?= $e(
    $assetNum,
) ?>">
        <div class="asset-number"><?= $e($assetNum) ?></div>
        <?php if ($categoryName !== ""): ?>
        <div class="asset-category"><?= $e($categoryName) ?></div>
        <?php endif; ?>
      </div>
      <div class="brand-side"><img src="<?= BASEURL ?>/public/img/unicoil-logo.png"
          alt="UNICOIL logo"></div>
    </section>
  </div>
</body>

</html>
