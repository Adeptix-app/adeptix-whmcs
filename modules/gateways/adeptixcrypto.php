<?php
/**
 * Adeptix Crypto Payments gateway module for WHMCS - direct on-chain USDT/USDC deposit.
 * Structured per WHMCS's own official sample gateway module (https://developers.whmcs.com/payment-gateways/).
 */

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

function adeptixcrypto_MetaData()
{
    return [
        'DisplayName' => 'Adeptix Crypto (USDT/USDC)',
        'APIVersion' => '1.1',
    ];
}

function adeptixcrypto_config()
{
    return [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'Adeptix Crypto (USDT/USDC)',
        ],
        'apiKey' => [
            'FriendlyName' => 'API Key',
            'Type' => 'password',
            'Size' => '48',
            'Default' => '',
            'Description' => 'From your Adeptix dashboard\'s API Keys page.',
        ],
        'webhookSecret' => [
            'FriendlyName' => 'Webhook Secret',
            'Type' => 'password',
            'Size' => '48',
            'Default' => '',
            'Description' => 'From your Adeptix dashboard\'s Settings page. Register this callback URL there: modules/gateways/callback/adeptixcrypto.php',
        ],
        'chain' => [
            'FriendlyName' => 'Chain',
            'Type' => 'dropdown',
            'Options' => ['bsc' => 'BSC', 'polygon' => 'Polygon', 'tron' => 'Tron'],
            'Description' => 'Which chain to accept deposits on.',
        ],
        'token' => [
            'FriendlyName' => 'Token',
            'Type' => 'dropdown',
            'Options' => ['USDT' => 'USDT', 'USDC' => 'USDC'],
            'Description' => 'Which stablecoin to accept.',
        ],
    ];
}

/** One row per invoice with a currently outstanding deposit request - lets pay.php reuse an
 * unexpired request instead of creating a new one on every page reload. */
function adeptixcrypto_activate()
{
    try {
        \WHMCS\Database\Capsule::statement(
            'CREATE TABLE IF NOT EXISTS `mod_adeptix_crypto_requests` (
                `invoice_id` INT UNSIGNED NOT NULL PRIMARY KEY,
                `payment_request_id` VARCHAR(64) NOT NULL,
                `pay_to_address` VARCHAR(128) NOT NULL,
                `amount` VARCHAR(32) NOT NULL,
                `chain` VARCHAR(16) NOT NULL,
                `token` VARCHAR(8) NOT NULL,
                `expires_at` VARCHAR(40) NOT NULL,
                UNIQUE KEY `payment_request_id` (`payment_request_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
        return ['status' => 'success', 'description' => 'Adeptix Crypto activated.'];
    } catch (\Exception $e) {
        return ['status' => 'error', 'description' => $e->getMessage()];
    }
}

function adeptixcrypto_deactivate()
{
    try {
        \WHMCS\Database\Capsule::statement('DROP TABLE IF EXISTS `mod_adeptix_crypto_requests`');
        return ['status' => 'success', 'description' => 'Adeptix Crypto deactivated.'];
    } catch (\Exception $e) {
        return ['status' => 'error', 'description' => $e->getMessage()];
    }
}

/**
 * @param array<string, mixed> $params
 */
function adeptixcrypto_link($params)
{
    $invoiceId = (int) $params['invoiceid'];
    $systemUrl = rtrim((string) $params['systemurl'], '/');
    $payUrl = $systemUrl . '/modules/gateways/adeptixcrypto/pay.php?invoiceid=' . $invoiceId;

    return '<a href="' . htmlspecialchars($payUrl) . '" class="btn btn-primary">' . htmlspecialchars($params['langpaynow']) . '</a>';
}
