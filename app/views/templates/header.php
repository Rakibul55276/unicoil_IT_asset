<!DOCTYPE html>
<html>

<head>
  <title><?= htmlspecialchars(
      (string) ($data["title"] ?? "IT Asset Management"),
      ENT_QUOTES,
      "UTF-8",
  ) ?></title>
  <?php require __DIR__ . "/favicon.php"; ?>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="<?= BASEURL ?>/css/bootstrap.css">
  <link rel="stylesheet" href="<?= BASEURL ?>/css/side-bar.css">
  <link rel="stylesheet" href="<?= BASEURL ?>/assets/font-awesome/4.5.0/css/font-awesome.min.css">
  <link rel="stylesheet"
    href="<?= BASEURL ?>/assets/css/modern-login.css?v=<?= (int) @filemtime(
        dirname(__DIR__, 3) . "/assets/css/modern-login.css",
    ) ?>">
</head>

<body>

  <nav class="agent-login-nav"><a href="<?= BASEURL ?>"><strong>UNICOIL</strong> <span>IT Asset
        Management System</span></a></nav>
