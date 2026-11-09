# Database-per-company SaaS and voucher setup

For the complete localhost and cPanel deployment procedure, see:

`README_SAAS_LOCALHOST_CPANEL.md`

The existing IT Asset Management workflows are unchanged. The SaaS layer is
inactive while `saas_voucher_enabled` is `false`.

## 1. Install the platform control tables

For the expanded UNICOIL installation, select `central_asset_db` in
cPanel/phpMyAdmin, then import:

`database/saas_platform_install.sql`

The script adds only four tables prefixed with `saas_`. Existing asset and
workflow tables remain unchanged. `it_asset_management` is the preserved
legacy database and must not be selected for this import.

The platform tables may be stored in a separate control database on a larger
hosting environment. In this installation they intentionally coexist in
`central_asset_db`.

## 2. Prepare a database for each company

Create a separate database for every company and import the clean application
database structure into it. Existing asset numbers and workflow queries can
therefore remain unchanged.

## 3. Configure private values

Copy the SaaS settings from `config.example.php` into the private `config.php`.
Set:

- platform database credentials;
- a random `saas_encryption_key` of at least 32 characters;
- a different random `saas_setup_key`;
- `saas_voucher_enabled` to `true`.

Never reuse or publish either secret.

## 4. Create the first Platform Administrator

Open:

`/Platform/setup`

Enter the setup key from `config.php`, then create a password of at least
12 characters. The setup route stops accepting new accounts as soon as the
first Platform Administrator exists.

The Platform Console is available at:

`/Platform`

## 5. Register companies and issue vouchers

1. Register the already-created company database in the Platform Console.
2. Give each company a unique company code.
3. Generate a one-time voucher for the required duration.
4. Give the full voucher to the company Administrator.
5. The Administrator signs in with the company code and redeems it from the
   Company Subscription screen.

Only a voucher hash is stored. The full voucher is displayed once.

## Switching the layer off

Set:

`saas_voucher_enabled` to `false`

The original single-company database settings are used immediately. No
company asset, request, assignment, return, approval or scrap code is removed.
