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
    body{margin:0;background:#f4f6f9;color:#172033;font-family:"Segoe UI",Arial,sans-serif}
    .platform-nav{display:flex;justify-content:space-between;align-items:center;padding:14px 24px;background:#102a25;color:#fff}.platform-nav a{color:#fff}
    .platform-wrap{max-width:1500px;margin:0 auto;padding:22px}.platform-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}
    .platform-panel{margin-bottom:18px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;box-shadow:0 8px 24px rgba(15,23,42,.05)}
    .platform-panel h2{margin:0;padding:14px 16px;background:#f8fafc;border-bottom:1px solid #e2e8f0;font-size:19px}.platform-panel-body{padding:16px}
    .voucher-code{padding:14px;background:#fff7d6;border:1px dashed #c99700;border-radius:8px;font-family:Consolas,monospace;font-size:18px;font-weight:700;word-break:break-all}
    .table-responsive{max-height:480px}.compact-form{display:flex;gap:5px}.compact-form select{min-width:105px}
    @media(max-width:900px){.platform-grid{grid-template-columns:1fr}}
  </style>
</head>
<body>
  <header class="platform-nav">
    <strong>SaaS Platform Console</strong>
    <span><?= $esc($_SESSION["saas_platform_admin"]["username"] ?? "") ?> ·
      <a href="<?= BASEURL ?>/Platform/logout">Logout</a></span>
  </header>
  <main class="platform-wrap">
    <?php if ($message): ?>
    <div class="alert alert-<?= $esc($message["type"]) ?>"><?= $esc($message["message"]) ?></div>
    <?php endif; ?>
    <?php if (!empty($data["newVoucher"])): ?>
    <div class="alert alert-warning">
      <strong>Copy this voucher now. Its full value is not stored:</strong>
      <div class="voucher-code"><?= $esc($data["newVoucher"]) ?></div>
    </div>
    <?php endif; ?>

    <div class="platform-grid">
      <section class="platform-panel">
        <h2>Create Company</h2>
        <div class="platform-panel-body">
          <p class="text-muted">Create the tenant database from the existing clean database before registering it here.</p>
          <form method="post" action="<?= BASEURL ?>/Platform/createCompany">
            <input type="hidden" name="csrf_token" value="<?= $esc($data["csrf"]) ?>">
            <div class="row">
              <div class="col-sm-4 form-group"><label>Company Code</label><input
                class="form-control" name="company_code" maxlength="30" required
                placeholder="ACME"></div>
              <div class="col-sm-8 form-group"><label>Company Name</label><input
                class="form-control" name="company_name" maxlength="150" required></div>
            </div>
            <div class="row">
              <div class="col-sm-4 form-group"><label>DB Host</label><input
                class="form-control" name="db_host" placeholder="Use default if blank"></div>
              <div class="col-sm-4 form-group"><label>DB Name</label><input
                class="form-control" name="db_name" required></div>
              <div class="col-sm-4 form-group"><label>DB User</label><input
                class="form-control" name="db_user" placeholder="Use default if blank"></div>
            </div>
            <div class="row">
              <div class="col-sm-8 form-group"><label>DB Password</label><input
                type="password" class="form-control" name="db_pass"
                placeholder="Encrypted; use default if blank" autocomplete="new-password"></div>
              <div class="col-sm-4 form-group"><label>Trial Days</label><input
                type="number" class="form-control" name="trial_days" value="30"
                min="0" max="365"></div>
            </div>
            <button class="btn btn-success" type="submit">Create Company</button>
          </form>
        </div>
      </section>

      <section class="platform-panel">
        <h2>Generate Voucher</h2>
        <div class="platform-panel-body">
          <form method="post" action="<?= BASEURL ?>/Platform/createVoucher">
            <input type="hidden" name="csrf_token" value="<?= $esc($data["csrf"]) ?>">
            <div class="form-group"><label>Assign to Company (optional)</label>
              <select class="form-control" name="assigned_company_id">
                <option value="0">Any company</option>
                <?php foreach ($data["companies"] as $company): ?>
                <option value="<?= (int) $company["company_id"] ?>">
                  <?= $esc($company["company_code"] . " - " . $company["company_name"]) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="row">
              <div class="col-sm-6 form-group"><label>Subscription Days</label>
                <select class="form-control" name="duration_days">
                  <option value="30">30 days</option>
                  <option value="90">90 days</option>
                  <option value="180">180 days</option>
                  <option value="365">365 days</option>
                  <option value="730">730 days</option>
                </select>
              </div>
              <div class="col-sm-6 form-group"><label>Voucher Valid For</label>
                <select class="form-control" name="valid_days">
                  <option value="30">30 days</option>
                  <option value="90" selected>90 days</option>
                  <option value="180">180 days</option>
                  <option value="365">365 days</option>
                </select>
              </div>
            </div>
            <button class="btn btn-primary" type="submit">Generate One-Time Voucher</button>
          </form>
        </div>
      </section>
    </div>

    <section class="platform-panel">
      <h2>Companies</h2>
      <div class="table-responsive">
        <table class="table table-striped table-bordered">
          <thead><tr><th>Code</th><th>Company</th><th>Status</th><th>Expiry</th>
            <th>Grace</th><th>Database</th><th>Action</th></tr></thead>
          <tbody>
            <?php foreach ($data["companies"] as $company): ?>
            <tr>
              <td><?= $esc($company["company_code"]) ?></td>
              <td><?= $esc($company["company_name"]) ?></td>
              <td><?= $esc($company["status"]) ?></td>
              <td><?= $esc($company["subscription_expires_at"] ?: "-") ?></td>
              <td><?= $esc($company["grace_until"] ?: "-") ?></td>
              <td><?= $esc($company["db_name"]) ?></td>
              <td>
                <form class="compact-form" method="post"
                  action="<?= BASEURL ?>/Platform/companyStatus">
                  <input type="hidden" name="csrf_token" value="<?= $esc($data["csrf"]) ?>">
                  <input type="hidden" name="company_id"
                    value="<?= (int) $company["company_id"] ?>">
                  <select class="form-control input-sm" name="status">
                    <?php foreach (["ACTIVE", "INACTIVE", "SUSPENDED"] as $status): ?>
                    <option value="<?= $status ?>" <?= $company["status"] === $status
    ? "selected"
    : "" ?>><?= $status ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button class="btn btn-default btn-sm" type="submit">Update</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$data["companies"]): ?>
            <tr><td colspan="7" class="text-center text-muted">No companies configured.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <div class="platform-grid">
      <section class="platform-panel">
        <h2>Voucher Register</h2>
        <div class="table-responsive">
          <table class="table table-striped table-condensed">
            <thead><tr><th>Prefix</th><th>Days</th><th>Status</th><th>Company</th><th>Valid Until</th></tr></thead>
            <tbody>
              <?php foreach ($data["vouchers"] as $voucher): ?>
              <tr><td><?= $esc($voucher["code_prefix"]) ?>…</td>
                <td><?= (int) $voucher["duration_days"] ?></td>
                <td><?= $esc($voucher["status"]) ?></td>
                <td><?= $esc($voucher["redeemed_company"] ?: ($voucher["assigned_company"] ?: "Any")) ?></td>
                <td><?= $esc($voucher["valid_until"]) ?></td></tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>

      <section class="platform-panel">
        <h2>Redemption History</h2>
        <div class="table-responsive">
          <table class="table table-striped table-condensed">
            <thead><tr><th>Company</th><th>Voucher</th><th>Previous</th><th>New Expiry</th><th>By</th></tr></thead>
            <tbody>
              <?php foreach ($data["redemptions"] as $redemption): ?>
              <tr><td><?= $esc($redemption["company_code"]) ?></td>
                <td><?= $esc($redemption["code_prefix"]) ?>…</td>
                <td><?= $esc($redemption["previous_expiry"] ?: "-") ?></td>
                <td><?= $esc($redemption["new_expiry"]) ?></td>
                <td><?= $esc($redemption["redeemed_by"]) ?></td></tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </main>
</body>
</html>
