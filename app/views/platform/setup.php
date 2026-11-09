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
    body{margin:0;background:#eef2f6;font-family:"Segoe UI",Arial,sans-serif}.setup-shell{min-height:100vh;display:grid;place-items:center;padding:24px}.setup-card{width:min(520px,100%);padding:30px;background:#fff;border-radius:14px;box-shadow:0 20px 55px rgba(15,23,42,.14)}
  </style>
</head>
<body>
  <main class="setup-shell"><section class="setup-card">
    <h1>Create Platform Administrator</h1>
    <p class="text-muted">This one-time setup closes automatically after the first account is created.</p>
    <?php if ($message): ?>
    <div class="alert alert-<?= $esc($message["type"]) ?>"><?= $esc($message["message"]) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= BASEURL ?>/Platform/setup">
      <input type="hidden" name="csrf_token" value="<?= $esc($data["csrf"]) ?>">
      <div class="form-group"><label>Setup Key</label><input type="password"
        class="form-control" name="setup_key" required autocomplete="off"></div>
      <div class="form-group"><label>Administrator Username</label><input
        class="form-control" name="username" minlength="3" maxlength="50" required></div>
      <div class="form-group"><label>Password</label><input type="password"
        class="form-control" name="password" minlength="12" required></div>
      <div class="form-group"><label>Confirm Password</label><input type="password"
        class="form-control" name="confirm_password" minlength="12" required></div>
      <button class="btn btn-success" type="submit">Create Administrator</button>
    </form>
  </section></main>
</body>
</html>
