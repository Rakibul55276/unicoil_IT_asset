<?php
$esc = static fn($value) => htmlspecialchars(
    (string) $value,
    ENT_QUOTES,
    "UTF-8",
);
$message = $_SESSION["saas_platform_message"] ?? null;
unset($_SESSION["saas_platform_message"]);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= $esc($data["title"]) ?></title>
  <?php require dirname(__DIR__) . "/templates/favicon.php"; ?>
  <link rel="stylesheet" href="<?= BASEURL ?>/css/bootstrap.css">
  <style>
    body{margin:0;background:#eef2f6;font-family:"Segoe UI",Arial,sans-serif}.platform-auth{min-height:100vh;display:grid;place-items:center;padding:24px}.platform-card{width:min(430px,100%);padding:30px;background:#fff;border-radius:14px;box-shadow:0 20px 55px rgba(15,23,42,.14)}.platform-card h1{margin:0 0 24px;font-size:25px}.btn-block{width:100%}
  </style>
</head>
<body>
  <main class="platform-auth"><section class="platform-card">
    <h1>Platform Administrator</h1>
    <?php if ($message): ?>
    <div class="alert alert-<?= $esc($message["type"]) ?>"><?= $esc($message["message"]) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= BASEURL ?>/Platform/login">
      <input type="hidden" name="csrf_token" value="<?= $esc($data["csrf"]) ?>">
      <div class="form-group"><label>Username</label><input class="form-control"
        name="username" required autocomplete="username"></div>
      <div class="form-group"><label>Password</label><input type="password"
        class="form-control" name="password" required autocomplete="current-password"></div>
      <button class="btn btn-success btn-block" type="submit">Sign in</button>
    </form>
  </section></main>
</body>
</html>
