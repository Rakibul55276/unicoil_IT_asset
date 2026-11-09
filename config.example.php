<?php

/**
 * Database configuration template.
 *
 * Copy this file to config.php and replace each placeholder with the
 * credentials for the database used by this installation. config.php is
 * ignored by Git so local and hosting credentials are not committed.
 */
return [
    // PHP timestamps used by forms, reports, PDFs, and emails.
    'app_timezone' => 'Asia/Riyadh',
    // Set the exact public application URL on hosting, without a trailing slash.
    "app_url" => "https://example.com/asset",
    "db_host" => "localhost",
    "db_name" => "YOUR_DATABASE_NAME",
    "db_user" => "YOUR_DATABASE_USER",
    "db_pass" => "YOUR_DATABASE_PASSWORD",

    // Independent feature switches managed from this one private file.
    "workflow_ui_enabled" => true,
    // Allows Issuer asset-operation screens without enabling the full workflow UI.
    "issuer_ui_enabled" => true,
    "scrap_approval_enabled" => true,
    "bulk_label_print_enabled" => true,
    // Separate quantity-based stock for cables, connectors and other accessories.
    "accessory_inventory_enabled" => true,
    // Disable to stop Agent API synchronization and hide Agent runtime integration.
    "agent_enabled" => true,

    /*
     * Optional database-per-company SaaS mode.
     *
     * Keep this false to run the original single-company system unchanged.
     * Import database/saas_platform_install.sql before enabling it.
     */
    "saas_voucher_enabled" => false,
    "platform_db_host" => "localhost",
    "platform_db_name" => "YOUR_PLATFORM_DATABASE_NAME",
    "platform_db_user" => "YOUR_PLATFORM_DATABASE_USER",
    "platform_db_pass" => "YOUR_PLATFORM_DATABASE_PASSWORD",

    // Use two different long random values and never commit the real values.
    "saas_encryption_key" => "replace-with-at-least-32-random-characters",
    "saas_setup_key" => "replace-with-a-different-long-random-value",
];
