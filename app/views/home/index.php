<main class="modern-login-shell">
  <section class="modern-login-card">
    <div class="login-branding">
      <img class="login-logo" src="<?= BASEURL ?>/public/img/unicoil-logo.png"
        alt="UNICOIL logo">
      <h1>IT Asset Management System</h1>
    </div>
    <!-- <p class="login-muted">Manage manual assets and automatically synchronized Windows computers in one system.</p> -->
    <?php Flasher::Message(); ?>
    <form action="<?= BASEURL ?>/Home/members" method="post" class="modern-login-form">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(
          (string) ($data["loginCsrf"] ?? ""),
          ENT_QUOTES,
          "UTF-8",
      ) ?>">
      <?php if (!empty($data["saasEnabled"])): ?>
      <label for="company-code">Company Code</label>
      <input class="form-control" name="company_code" id="company-code" maxlength="30"
        pattern="[A-Za-z0-9][A-Za-z0-9_-]{1,29}" required autocomplete="organization"
        value="<?= htmlspecialchars(
            (string) ($data["companyCode"] ?? ""),
            ENT_QUOTES,
            "UTF-8",
        ) ?>" placeholder="Example: UNICOIL">
      <?php endif; ?>
      <label for="username">Username</label>
      <input class="form-control" name="username" id="username" required autocomplete="username">
      <label for="password">Password</label>
      <input type="password" class="form-control" name="password" id="password" required
        autocomplete="current-password">
      <button type="submit" class="modern-login-button">Sign in</button>
    </form>
    <p class="login-powered-by" style="margin:24px 0 -18px; color:#9aa4b2; font-size:9px; font-weight:400; letter-spacing:.15px;">
        Powered by IT (Rakib)
    </p>
  </section>
</main>

<script>
  var firstLoginField = document.getElementById("company-code") ||
    document.getElementById("username");
  if (firstLoginField) {
    firstLoginField.focus();
  }
</script>
