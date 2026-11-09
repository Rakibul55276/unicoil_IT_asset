<?php
/**
 * Lightweight database migration runner for the IT Asset application.
 *
 * Usage from the project directory:
 *   C:\xampp\php\php.exe database\migrate.php status
 *   C:\xampp\php\php.exe database\migrate.php migrate
 *
 * This script is CLI-only so migrations cannot be triggered from a browser.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Database migrations can only be run from the command line.\n");
}

$command = $argv[1] ?? 'migrate';
$targetVersion = trim((string) ($argv[2] ?? ''));
if (!in_array($command, ['migrate', 'status'], true)) {
    fwrite(STDERR, "Unknown command: {$command}\nUse: migrate or status\n");
    exit(1);
}

// config.php builds BASEURL using HTTP_HOST, which is normally absent in CLI.
if (!isset($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = 'localhost';
}
require_once dirname(__DIR__) . '/app/core/SaasCrypto.php';
require_once dirname(__DIR__) . '/app/core/SaasTenantContext.php';
require_once dirname(__DIR__) . '/app/config/config.php';

try {
    $dbHost = getenv('MIGRATION_DB_HOST') ?: DB_HOST;
    $dbName = getenv('MIGRATION_DB_NAME') ?: DB_NAME;
    $dbUser = getenv('MIGRATION_DB_USER') ?: DB_USER;
    $dbPass = getenv('MIGRATION_DB_PASS');
    $dbPass = $dbPass === false ? DB_PASS : $dbPass;

    $pdo = new PDO(
        'mysql:host=' . $dbHost . ';dbname=' . $dbName . ';charset=utf8mb4',
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS schema_migrations (
            version varchar(100) NOT NULL,
            description varchar(255) NOT NULL,
            batch int unsigned NOT NULL,
            applied_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (version),
            KEY idx_schema_migrations_batch (batch)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $migrationFiles = glob(__DIR__ . '/migrations/*.php') ?: [];
    sort($migrationFiles, SORT_STRING);

    $appliedRows = $pdo->query('SELECT version FROM schema_migrations')->fetchAll();
    $applied = array_fill_keys(array_column($appliedRows, 'version'), true);

    if ($command === 'status') {
        echo str_pad('Status', 10) . str_pad('Version', 28) . "Description\n";
        echo str_repeat('-', 80) . "\n";
        foreach ($migrationFiles as $file) {
            $migration = loadMigration($file);
            $status = isset($applied[$migration['version']]) ? 'Applied' : 'Pending';
            echo str_pad($status, 10)
                . str_pad($migration['version'], 28)
                . $migration['description'] . "\n";
        }
        exit(0);
    }

    $pending = [];
    $targetFound = $targetVersion === '';
    foreach ($migrationFiles as $file) {
        $migration = loadMigration($file);
        if ($migration['version'] === $targetVersion) {
            $targetFound = true;
        }
        if (
            !isset($applied[$migration['version']]) &&
            ($targetVersion === '' || $migration['version'] === $targetVersion)
        ) {
            $pending[] = $migration;
        }
    }

    if (!$targetFound) {
        throw new RuntimeException('Migration version not found: ' . $targetVersion);
    }

    if (!$pending) {
        echo "Database is already up to date.\n";
        exit(0);
    }

    $batch = (int) $pdo->query('SELECT COALESCE(MAX(batch), 0) + 1 FROM schema_migrations')->fetchColumn();
    foreach ($pending as $migration) {
        echo "Applying {$migration['version']}: {$migration['description']} ... ";
        $pdo->beginTransaction();
        try {
            $migration['up']($pdo);
            $stmt = $pdo->prepare(
                'INSERT INTO schema_migrations (version, description, batch)
                 VALUES (:version, :description, :batch)'
            );
            $stmt->execute([
                ':version' => $migration['version'],
                ':description' => $migration['description'],
                ':batch' => $batch,
            ]);
            // MySQL/MariaDB implicitly commit DDL such as ALTER/CREATE TABLE.
            // Commit only when the transaction is still active.
            if ($pdo->inTransaction()) {
                $pdo->commit();
            }
            echo "DONE\n";
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo "FAILED\n";
            throw $error;
        }
    }

    echo "Applied " . count($pending) . " migration(s), batch {$batch}.\n";
} catch (Throwable $error) {
    fwrite(STDERR, "Migration error: " . $error->getMessage() . "\n");
    exit(1);
}

function loadMigration(string $file): array
{
    $migration = require $file;
    foreach (['version', 'description', 'up'] as $key) {
        if (!isset($migration[$key])) {
            throw new RuntimeException(basename($file) . " is missing migration key: {$key}");
        }
    }
    if (!is_callable($migration['up'])) {
        throw new RuntimeException(basename($file) . ' has a non-callable up migration');
    }
    if (basename($file, '.php') !== $migration['version']) {
        throw new RuntimeException(basename($file) . ' version must match its filename');
    }
    return $migration;
}
