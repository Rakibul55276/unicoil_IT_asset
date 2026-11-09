<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Administrator password reset is CLI-only.\n");
}

$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
require_once dirname(__DIR__) . '/app/config/config.php';

$username = trim((string) ($argv[1] ?? 'admin'));
if (!preg_match('/^[A-Za-z0-9_.-]{1,50}$/', $username)) {
    fwrite(STDERR, "Invalid username.\n");
    exit(1);
}

function readSecret(string $prompt): string
{
    fwrite(STDOUT, $prompt);
    $hideInput = DIRECTORY_SEPARATOR !== '\\' && function_exists('shell_exec');
    if ($hideInput) {
        shell_exec('stty -echo');
    }

    try {
        $value = rtrim((string) fgets(STDIN), "\r\n");
    } finally {
        if ($hideInput) {
            shell_exec('stty echo');
            fwrite(STDOUT, PHP_EOL);
        }
    }

    return $value;
}

$password = readSecret("New password for {$username}: ");
$confirmation = readSecret('Confirm new password: ');

if ($password !== $confirmation) {
    fwrite(STDERR, "Passwords do not match.\n");
    exit(1);
}
if (strlen($password) < 12) {
    fwrite(STDERR, "Password must contain at least 12 characters.\n");
    exit(1);
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $statement = $pdo->prepare(
        'UPDATE users SET password = :password, active = 1 WHERE username = :username'
    );
    $statement->execute(['password' => $hash, 'username' => $username]);

    if ($statement->rowCount() === 0) {
        $check = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :username');
        $check->execute(['username' => $username]);
        if ((int) $check->fetchColumn() === 0) {
            throw new RuntimeException("User '{$username}' does not exist.");
        }
    }

    fwrite(STDOUT, "Password updated and account activated for {$username}.\n");
} catch (Throwable $error) {
    fwrite(STDERR, 'Password reset failed: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}

