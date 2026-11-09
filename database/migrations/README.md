# Database migrations

The full SQL dump is needed only once to establish the baseline. After that,
put each database change in a new PHP file inside `database/migrations` and run:

```powershell
C:\xampp\php\php.exe database\migrate.php status
C:\xampp\php\php.exe database\migrate.php migrate
```

To apply one specific migration without running older pending migrations:

```powershell
C:\xampp\php\php.exe database\migrate.php migrate 20260804_000001_return_request_cancellation
```

The runner creates `schema_migrations` and records every successfully applied
version. Running `migrate` again skips completed versions and applies only new
files.

For safe testing against another database, set `MIGRATION_DB_HOST`,
`MIGRATION_DB_NAME`, `MIGRATION_DB_USER`, and `MIGRATION_DB_PASS` environment
variables before running the command. Normal runs use `app/config/config.php`.

## Creating a migration

Copy this structure into a uniquely named file. Use a sortable timestamp and
keep the filename identical to `version`:

```php
<?php

return [
    'version' => '20260712_120000_add_asset_vendor',
    'description' => 'Add vendor reference to assets',
    'up' => function (PDO $pdo): void {
        $pdo->exec(
            'ALTER TABLE assetdata ADD COLUMN vendor_id bigint unsigned NULL'
        );
        $pdo->exec(
            'CREATE INDEX idx_asset_vendor ON assetdata (vendor_id)'
        );
    },
];
```

Rules:

- Never edit an already-applied migration.
- Add a new migration for every later change.
- Back up production data before structural changes.
- Keep each migration focused on one related change.
- MySQL/MariaDB can auto-commit DDL statements, so test migrations on a copy of
  the production database before deployment.
