<?php
require dirname(__DIR__) . "/bootstrap.php";
DashboardAuth::start();
if (DashboardAuth::check()) {
    header("Location: dashboard.php");
    exit();
}
$error = "";
if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") {
    try {
        DashboardAuth::validateCsrf($_POST["csrf"] ?? null);
        if (
            DashboardAuth::attempt(
                (string) ($_POST["username"] ?? ""),
                (string) ($_POST["password"] ?? ""),
            )
        ) {
            header("Location: dashboard.php");
            exit();
        }
        $error = "Username or password is incorrect.";
        usleep(350000);
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
DashboardView::header("Sign in");
?>
<main class="login-shell">
  <section class="login-card">
    <div class="login-mark">UA</div>
    <p class="eyebrow">SECURE INVENTORY</p>
    <h1>PC Agent Dashboard</h1>
    <p class="muted">Sign in to manage synchronized Windows computers.</p>
    <?php if ($error !== ""): ?><div class="alert error"><?= DashboardView::escape(
    $error,
) ?></div><?php endif; ?>
    <form method="post" class="stack-form" autocomplete="on"><input type="hidden" name="csrf" value="<?= DashboardView::escape(
    DashboardAuth::csrfToken(),
) ?>"><label>Username<input name="username" maxlength="50" required autofocus
          autocomplete="username"></label><label>Password<input type="password" name="password"
          required autocomplete="current-password"></label><button class="primary wide"
        type="submit">Sign in</button></form>
  </section>
</main>
<?php DashboardView::footer(); ?>