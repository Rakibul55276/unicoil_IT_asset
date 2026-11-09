# IT Asset Management SaaS Voucher Deployment

This guide covers both localhost/XAMPP and cPanel deployment of the
database-per-company subscription system.

## Production upload safety

Before creating the cPanel archive:

1. Do not include the local `config.php`, `app/config/mail_secret.php`, `.git`,
   `database`, `output`, `tmp`, or existing files under `public/uploads`.
2. Keep the root `.htaccess` and every `.htaccess` under `public/uploads`; these
   files block source, SQL, logs, direct invoices, direct images, and executable
   uploads.
3. Copy `config.example.php` to `config.php` on the server and enter the cPanel
   database credentials. Set `app_url` to the exact HTTPS application URL.
4. Use a dedicated cPanel database user, a strong unique password, and only the
   privileges required for that company's database. Never deploy a `root`
   database account or a blank password.
5. Generate new production setup/encryption keys before adding the first
   company. If migrating companies that already have encrypted tenant
   passwords, preserve the existing encryption key through a private channel.
6. Import the chosen SQL file separately through phpMyAdmin. Do not place SQL
   exports inside the public website directory.
7. Enable cPanel **Force HTTPS Redirect**, verify the certificate, rotate agent
   tokens and mail gateway secrets, and test login before giving users access.

The existing asset, employee, request, approval, assignment, return and scrap
workflows are preserved. The SaaS layer runs before those workflows and
selects the correct company database.

## Architecture

- `central_asset_db` is the expanded UNICOIL application database.
- `central_asset_db` also contains the four `saas_*` platform control tables.
- `it_asset_management` is the preserved legacy database and must not be
  changed.
- Every additional customer receives a separate asset-management database.
- The Platform Administrator creates companies and one-time vouchers.
- A Company Administrator redeems a voucher to extend the subscription.
- No online payment gateway is used.

Platform control tables:

- `saas_company`
- `saas_platform_admin`
- `saas_voucher`
- `saas_voucher_redemption`

## Important security rules

1. Use a different encryption key and setup key.
2. Each value should contain at least 64 random hexadecimal characters.
3. Never publish or commit the keys.
4. Never change `saas_encryption_key` after saving a tenant database password.
5. Clear `saas_setup_key` after creating the first Platform Administrator.
6. Use HTTPS on hosting.
7. Give each customer database user access only to that customer's database.
8. Back up the platform database and each company database separately.

Generate a random key:

```powershell
C:\xampp\php\php.exe -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Run the command twice and keep the two results separate.

---

# Part A: Localhost/XAMPP

## A1. Project and database

Local project:

```text
C:\xampp\htdocs\IT-asset-new_
```

Local URL:

```text
http://localhost/IT-asset-new_/
```

The current local expanded-system database is:

```text
central_asset_db
```

The platform SQL has already been installed locally. To install it again on a
new local database, select `central_asset_db` in phpMyAdmin and import:

```text
database/saas_platform_install.sql
```

The SQL uses `CREATE TABLE IF NOT EXISTS` and only creates tables beginning
with `saas_`.

## A2. Local configuration

Open:

```text
config.php
```

Use:

```php
return [
    'db_host' => 'localhost',
    'db_name' => 'central_asset_db',
    'db_user' => 'root',
    'db_pass' => '',

    'workflow_ui_enabled' => true,
    'saas_voucher_enabled' => true,
    'platform_db_host' => 'localhost',
    'platform_db_name' => 'central_asset_db',
    'platform_db_user' => 'root',
    'platform_db_pass' => '',

    'saas_encryption_key' => 'YOUR_PRIVATE_ENCRYPTION_KEY',
    'saas_setup_key' => 'YOUR_TEMPORARY_SETUP_KEY',
];
```

## A3. Create the first Platform Administrator

Open:

```text
http://localhost/IT-asset-new_/Platform/setup
```

Enter:

- the exact value saved as `saas_setup_key`;
- a Platform Administrator username;
- a password containing at least 12 characters.

After successful creation, open:

```text
http://localhost/IT-asset-new_/Platform/login
```

Then clear only the temporary setup key:

```php
'saas_setup_key' => '',
```

Do not clear or change `saas_encryption_key`.

## A4. Register UNICOIL

From the Platform Console, create the first company with:

```text
Company Code: UNICOIL
Company Name: UNICOIL
DB Host: leave empty
DB Name: central_asset_db
DB User: leave empty
DB Password: leave empty
Trial Days: 0
```

Empty connection fields use the default database credentials from
`config.php`.

## A5. Generate and redeem a voucher

1. Select UNICOIL in **Generate Voucher**.
2. Select 30, 90, 180, 365 or 730 subscription days.
3. Generate the voucher.
4. Copy the complete voucher immediately.
5. Open the normal login page.
6. Enter company code `UNICOIL` and the existing company Administrator login.
7. The inactive or expired company is redirected to **Company Subscription**.
8. Paste the voucher and select **Redeem Voucher**.

Only the SHA-256 voucher hash is stored. The full voucher cannot be recovered
after leaving the Platform Console.

## A6. Normal local login

When SaaS mode is enabled, login requires:

```text
Company Code
Username
Password
```

When SaaS mode is disabled, the Company Code field disappears and the
original single-company login operates normally.

---

# Part B: cPanel Hosting

## B1. Back up before deployment

Download:

- the complete hosting application folder;
- an SQL export of `central_asset_db`;
- an SQL export of `it_asset_management`.

Do not import SaaS tables into `it_asset_management`.

## B2. Upload the application

Upload the project under the required web directory. For example:

```text
public_html/asset
```

The corresponding URL is:

```text
https://your-domain.example/asset/
```

Ensure PHP has these extensions:

- PDO MySQL
- OpenSSL
- Fileinfo
- GD
- mbstring

PHP 8.1 or newer is recommended.

## B3. Install the platform tables

In cPanel:

1. Open phpMyAdmin.
2. Select the hosting `central_asset_db`.
3. Open **Import**.
4. Import `database/saas_platform_install.sql`.
5. Confirm that the four `saas_*` tables exist.

The hosting database name may contain a cPanel prefix, such as:

```text
hostinguser_central_asset_db
```

Always use the complete displayed database name.

## B4. Configure the hosting database

Edit the private root `config.php` using cPanel File Manager:

```php
return [
    'db_host' => 'localhost',
    'db_name' => 'hostinguser_central_asset_db',
    'db_user' => 'hostinguser_assetuser',
    'db_pass' => 'COMPANY_DATABASE_PASSWORD',

    'workflow_ui_enabled' => true,
    'saas_voucher_enabled' => true,
    'platform_db_host' => 'localhost',
    'platform_db_name' => 'hostinguser_central_asset_db',
    'platform_db_user' => 'hostinguser_assetuser',
    'platform_db_pass' => 'COMPANY_DATABASE_PASSWORD',

    'saas_encryption_key' => 'NEW_HOSTING_ENCRYPTION_KEY',
    'saas_setup_key' => 'NEW_TEMPORARY_HOSTING_SETUP_KEY',
];
```

Generate new hosting keys. Do not reuse the localhost keys.

In **MySQL Databases**, confirm that the database user is assigned to the
database with all required privileges.

## B5. Create the hosting Platform Administrator

If hosted under `/asset`, open:

```text
https://your-domain.example/asset/Platform/setup
```

Enter the hosting `saas_setup_key`, Platform Administrator username and strong
password.

After successful creation:

```php
'saas_setup_key' => '',
```

Platform login:

```text
https://your-domain.example/asset/Platform/login
```

## B6. Register the first hosting company

For UNICOIL:

```text
Company Code: UNICOIL
Company Name: UNICOIL
DB Host: leave empty
DB Name: hostinguser_central_asset_db
DB User: leave empty
DB Password: leave empty
Trial Days: 0
```

Generate and redeem a voucher using the same steps as localhost.

## B7. Add another customer

For every additional customer:

1. Create a new cPanel MySQL database.
2. Create a dedicated database user.
3. Assign that user only to the customer's database.
4. Import the clean IT Asset Management database structure.
5. Do not import another copy of the `saas_*` platform tables.
6. Open the Platform Console.
7. Register the customer with a unique company code.
8. Enter the customer's database host, name, user and password.
9. Generate a voucher assigned to that customer.
10. Give the company code and voucher to the customer's Administrator.

Tenant database passwords are encrypted before being stored in
`saas_company`.

---

# Subscription behavior

Company states:

- `TRIAL`: temporary trial access;
- `ACTIVE`: valid voucher subscription;
- `GRACE`: seven-day access after normal expiry;
- `EXPIRED`: only renewal and logout are available;
- `SUSPENDED`: voucher redemption is blocked until the Platform Administrator
  restores the company;
- `INACTIVE`: company has not yet been activated.

Voucher extension rule:

- if the current expiry is in the future, voucher days are added to that date;
- if already expired, voucher days are added from today;
- each voucher can be redeemed only once;
- a voucher can optionally be restricted to one company.

---

# Disable SaaS mode without removing anything

Set:

```php
'saas_voucher_enabled' => false,
```

Effects:

- original database credentials are used;
- Company Code disappears from login;
- Platform and Subscription routes are hidden;
- existing workflow data and `saas_*` records are not deleted.

`WORKFLOW_UI_ENABLED` remains a separate switch and continues to control the
request and approval interface.

Both switches are maintained together in the private root `config.php`:

```php
// Equipment requests, approvals, self-service, return requests and scrap approval.
'workflow_ui_enabled' => true,

// Company Code login, Platform Console, subscription gate and voucher renewal.
'saas_voucher_enabled' => true,
```

Change only the required value to `false`. The switches are independent and
neither option deletes data.

---

# Troubleshooting

## Platform setup says the key is invalid

Confirm:

- `saas_setup_key` is not empty;
- the form value exactly matches `config.php`;
- no quotation marks or spaces were pasted into the form;
- the correct hosting `config.php` was edited.

## Platform URL returns 404

Confirm:

```php
'saas_voucher_enabled' => true,
```

## Platform page returns a database error

Confirm:

- the complete cPanel database name and username are configured;
- the database user is assigned to the database;
- all four `saas_*` tables exist;
- PHP PDO MySQL is enabled.

## Company creation reports that the database cannot be verified

The tenant database must be reachable and contain:

- `users`
- `assetdata`
- `asset_issue`
- `asset_return`

Confirm that its database user has permission to read and write those tables.

## Company login fails

Confirm:

- the Company Code matches `saas_company.company_code`;
- the company database contains that user;
- the company status and subscription are valid;
- the correct tenant database was registered.

## Encryption error after moving hosting

The hosting installation must keep the same `saas_encryption_key` used when
its tenant database passwords were encrypted. If the key is lost, stored
passwords cannot be decrypted and must be re-entered.
