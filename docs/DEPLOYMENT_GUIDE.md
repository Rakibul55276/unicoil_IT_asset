# Localhost and cPanel Deployment Guide

## Prerequisites

- PHP 8.1 or later
- MariaDB/MySQL with InnoDB
- PDO MySQL, OpenSSL, Fileinfo, GD, and mbstring extensions
- Apache `mod_rewrite` and permission to use `.htaccess`
- HTTPS for production

## Localhost installation

1. Copy the project to `C:\xampp\htdocs\IT-asset-new_`.
2. Start Apache and MySQL.
3. Create the database in phpMyAdmin.
4. Import the approved schema/install scripts and run migrations.
5. Copy `config.example.php` to `config.php` and configure:

```php
'app_timezone' => 'Asia/Riyadh',
'app_url' => 'http://localhost/IT-asset-new_',
'db_host' => 'localhost',
'db_name' => 'YOUR_DATABASE',
'db_user' => 'YOUR_USER',
'db_pass' => 'YOUR_PASSWORD',
```

6. Open `http://localhost/IT-asset-new_/health.php` if Agent is enabled, then open the main application.

## cPanel installation

1. Create a backup of the current site and database.
2. Create the MySQL database/user in cPanel and grant the user **ALL PRIVILEGES** on that database.
3. Import the approved schema or migration SQL in the selected database.
4. Upload one project archive to a temporary folder and extract once. Do not extract repeated archives over one another.
5. Move the project contents into the intended directory, for example `public_html/IT-Assets`.
6. Preserve the root `.htaccess` and upload-protection `.htaccess` files.
7. Create `config.php` from `config.example.php`; use the full cPanel database name and database username (often account-prefixed).
8. Set `app_url` exactly, with correct case and no trailing slash, for example `https://example.com/IT-Assets`.
9. Confirm PHP 8.1+ and extensions in **MultiPHP Manager / Select PHP Version**.
10. Ensure runtime upload directories are writable by the web server but are not executable.
11. Open the login page, sign in, and perform the verification checklist below.

Do not use cPanel **Error Pages** to diagnose PHP HTTP 500 errors. Read the application's `error_log` or cPanel error log.

## Upgrade without data loss

1. Back up database and uploads.
2. Put the new release in a separate temporary directory.
3. Preserve production `config.php`, `app/config/mail_secret.php`, and `public/uploads`.
4. Compare release notes and run only unapplied migrations.
5. Switch directories or copy reviewed source files.
6. Clear only safe generated caches/temp files; never delete evidence uploads.
7. Test before reopening access.

## Production verification

- Login succeeds for Administrator, Issuer, and one standard employee.
- Unauthorized roles receive a controlled denial, not an HTTP 500.
- Create/view/update one test asset without corrupting history.
- Assign and return a test batch; both PDFs contain all selected assets.
- Asset status and global condition update correctly.
- Search, sorting, filters, pagination, and exports work.
- Workflow request, approval, issue, acceptance, return request, and scrap are tested if enabled.
- QR and one physical label scan correctly.
- Mail delivery is tested if configured.
- Agent health and one token-authenticated synchronization are tested if enabled.
- Database and upload backups can be restored.

## Git deployment safety

The repository excludes private credentials, runtime uploads, logs, build output, and customer data exports. Keep production secrets outside Git. Use a release tag/ZIP created from the committed source and keep Windows installers in GitHub Releases rather than committing `dist` output.
