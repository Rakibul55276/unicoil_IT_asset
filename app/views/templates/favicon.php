<?php
// Central browser-tab icon. Change this path once to update every page.
$faviconUrl = rtrim((string) BASEURL, "/") . "/public/img/unicoil-logo.png";
?>
<link rel="icon" type="image/png"
  href="<?= htmlspecialchars($faviconUrl, ENT_QUOTES, "UTF-8") ?>">
<?php unset($faviconUrl); ?>
