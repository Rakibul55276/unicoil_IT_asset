<?php

declare(strict_types=1);

$remoteAddress = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remoteAddress, ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('This password utility is available only through local XAMPP.');
}

$hash = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string) ($_POST['password'] ?? '');
    $confirmation = (string) ($_POST['confirmation'] ?? '');

    if (strlen($password) < 12) {
        $error = 'Use a password containing at least 12 characters.';
    } elseif ($password !== $confirmation) {
        $error = 'The passwords do not match.';
    } else {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
}

?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Local Admin Password Hash</title>
    <style>
        body{font-family:Arial,sans-serif;background:#f4f6f9;margin:0;padding:40px;color:#243444}
        main{max-width:680px;margin:auto;background:#fff;border:1px solid #d9e0e7;border-radius:6px;padding:26px;box-shadow:0 3px 14px rgba(0,0,0,.08)}
        label{display:block;font-weight:bold;margin:16px 0 6px}input{box-sizing:border-box;width:100%;padding:11px;border:1px solid #b8c3ce;border-radius:4px}
        button{margin-top:18px;padding:11px 18px;border:0;border-radius:4px;background:#337ab7;color:#fff;cursor:pointer}.error{color:#b52b27}.result{margin-top:22px;padding:15px;background:#edf8ee;border:1px solid #9ecda5;word-break:break-all}
        code{font-family:Consolas,monospace}.note{color:#667788;font-size:14px}
    </style>
</head>
<body><main>
    <h2>Generate Administrator Password Hash</h2>
    <p class="note">Runs only on localhost. The password is processed in memory and is not saved.</p>
    <?php if ($error !== ''): ?><p class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
    <form method="post" autocomplete="off">
        <label for="password">New password</label>
        <input type="password" id="password" name="password" minlength="12" required autocomplete="new-password">
        <label for="confirmation">Confirm password</label>
        <input type="password" id="confirmation" name="confirmation" minlength="12" required autocomplete="new-password">
        <button type="submit">Generate bcrypt hash</button>
    </form>
    <?php if ($hash !== ''): ?>
        <div class="result"><strong>Copy this complete hash:</strong><br><br><code><?= htmlspecialchars($hash, ENT_QUOTES, 'UTF-8'); ?></code></div>
    <?php endif; ?>
</main></body></html>
