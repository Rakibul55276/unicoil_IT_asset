# Optional SaaS Platform Module

## Purpose

When `saas_voucher_enabled` is true, a platform administrator can create companies, associate isolated tenant databases, issue time-based vouchers, and control subscription access without online payment integration.

## Isolation model

- Platform database: companies, platform administrators, vouchers, redemptions.
- Tenant database: one company's asset, employee, user, and transaction data.
- Encrypted database credentials are resolved by `SaasTenantContext`.

The original single-company application remains the tenant application; the gate wraps access without replacing its workflows.

## Files

- `app/controllers/Platform.php`
- `app/controllers/Subscription.php`
- `app/core/SaasPlatform.php`
- `app/core/SaasGate.php`
- `app/core/SaasTenantContext.php`
- `app/core/SaasCrypto.php`
- `app/views/platform/`
- `app/views/subscription/`
- `database/saas_platform_install.sql`

## Configuration

Private root `config.php` contains `saas_voucher_enabled`, platform database credentials, `saas_encryption_key`, and one-time `saas_setup_key`. Generate different long random values and never publish them.

## Adding a company

1. Create a clean tenant database and restricted database user.
2. Import the approved tenant schema/migrations.
3. Sign in to the Platform Administrator page.
4. Create the company with its public identifier and encrypted tenant database connection.
5. Generate and issue a voucher.
6. Redeem/test the company subscription and verify its users cannot access another tenant.

Platform setup closes after the first platform administrator is created. Disable/remove access to the setup route after production initialization and rotate the setup key.
