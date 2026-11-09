# Troubleshooting

## Login says it cannot connect to the database

1. Confirm MySQL is running.
2. Verify `db_name`, `db_user`, and `db_pass` in private `config.php`.
3. On cPanel, use the full prefixed database/user names and grant the user to the database.
4. Verify PHP PDO MySQL is enabled.
5. Check `error_log` for the displayed reference code.

`SQLSTATE[HY000] [1045]` means invalid credentials or missing database-user grant. `Production database credentials are not configured` means placeholders/private config are still in use.

## HTTP 500

- Read the newest PHP error log entry.
- Confirm all uploaded application folders belong to the same release.
- Verify PHP version/extensions and file permissions.
- Check database migrations before assuming the page code is wrong.
- Run PHP syntax validation locally.

## MySQL stops in XAMPP

- Check whether another MySQL service owns port 3306.
- Read `xampp\mysql\data\mysql_error.log` before modifying data files.
- Start XAMPP as Administrator when Windows permissions block the service.
- Never replace the data folder without a verified backup.

## View import errors

- Error 1227/SUPER privilege: remove the `DEFINER` clause and use `SQL SECURITY INVOKER`.
- Illegal mix of collations: align joined key columns to the same collation.
- Foreign-key error 150: compare both column types, lengths, signedness, indexes, engines, and collations.
- Table already exists: determine install versus upgrade; do not paste error text back into the SQL editor.

## Assignment/return batch contains one item instead of many

- Confirm selected items belong to the same eligible batch required by the page.
- Use one submit action and do not refresh during save.
- Verify the document action receives the batch/record IDs rather than one asset number.
- Check transaction records before resubmitting to avoid duplicates.

## Upload, image, or PDF problem

- Verify `public/uploads` subdirectories are writable and protected from execution.
- Confirm Fileinfo/GD/mbstring extensions.
- Check configured upload-size limits in PHP and hosting.
- Confirm logo/image files exist with exact letter case on Linux hosting.

## Email not received

- A successful API/SMTP response is not the same as appearing in Sent Items.
- Check the configured transport secret, sender verification, provider logs, recipient spam/quarantine, SPF/DKIM/DMARC, and daily quota.
- Shared hosting may block outbound SMTP ports; the Google Apps Script HTTPS transport avoids that restriction when configured.

## Agent does not synchronize

- Confirm `agent_enabled` is true.
- Open `health.php` and check for JSON success.
- Verify the client URL, `uia_...` token, internet/TLS access, and server API token record.
- Review `agent_sync_log` and Agent client logs.

## Safe diagnostic commands

```powershell
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { C:\xampp\php\php.exe -l $_.FullName }
C:\xampp\php\php.exe database\migrate.php status
git status --short
```

Never display production passwords, mail secrets, setup keys, encryption keys, or API tokens in screenshots or support tickets.
