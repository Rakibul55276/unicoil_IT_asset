<?php
$company = $data["company"];
$state = (string) $data["state"];
$esc = static fn($value) => htmlspecialchars(
    (string) $value,
    ENT_QUOTES,
    "UTF-8",
);
$stateClass = in_array($state, ["ACTIVE", "TRIAL"], true)
    ? "success"
    : ($state === "GRACE"
        ? "warning"
        : "danger");
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
    body{margin:0;background:#f4f6f9;color:#172033;font-family:"Segoe UI",Arial,sans-serif}
    .subscription-shell{min-height:100vh;display:grid;place-items:center;padding:24px}
    .subscription-card{width:min(620px,100%);padding:30px;background:#fff;border:1px solid #e2e8f0;border-radius:16px;box-shadow:0 20px 55px rgba(15,23,42,.12)}
    .subscription-card h1{margin-top:0}.subscription-meta{padding:15px;background:#f8fafc;border-radius:10px;margin:18px 0}
    .subscription-meta p{margin:7px 0}.subscription-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px}
  </style>
</head>
<body>
  <main class="subscription-shell">
    <section class="subscription-card">
      <h1>Company Subscription</h1>
      <p class="text-muted"><?= $esc($company["company_name"]) ?>
        (<?= $esc($company["company_code"]) ?>)</p>
      <?php Flasher::Message(); ?>
      <div class="subscription-meta">
        <p><strong>Status:</strong>
          <span class="label label-<?= $stateClass ?>"><?= $esc($state) ?></span>
        </p>
        <p><strong>Subscription expiry:</strong>
          <?= $esc($company["subscription_expires_at"] ?: "Not activated") ?></p>
        <p><strong>Grace period:</strong>
          <?= $esc($company["grace_until"] ?: "-") ?></p>
      </div>

      <?php if (!empty($data["canRedeem"])): ?>
      <form method="post" action="<?= BASEURL ?>/Subscription/redeem">
        <input type="hidden" name="csrf_token" value="<?= $esc($data["csrf"]) ?>">
        <div class="form-group">
          <label for="voucher-code">Renewal Voucher</label>
          <input class="form-control" id="voucher-code" name="voucher_code"
            maxlength="40" required autocomplete="off"
            placeholder="VCH-XXXXXXXX-XXXXXXXX-XXXXXXXX">
        </div>
        <button type="submit" class="btn btn-success">Redeem Voucher</button>
      </form>
      <?php else: ?>
      <div class="alert alert-info">
        Contact your company Administrator to renew this subscription.
      </div>
      <?php endif; ?>

      <div class="subscription-actions">
        <?php if (in_array($state, ["ACTIVE", "TRIAL", "GRACE"], true)): ?>
        <a class="btn btn-primary" href="<?= BASEURL ?>">Continue to System</a>
        <?php endif; ?>
        <a class="btn btn-default" href="<?= BASEURL ?>/Home/logout">Logout</a>
      </div>
    </section>
  </main>
</body>
</html>
