<?php
/**
 * Adeptix Merchant Payments gateway module for WHMCS - hosted card/bank/wallet checkout.
 *
 * Structured per WHMCS's own official sample gateway module and developer docs
 * (https://developers.whmcs.com/payment-gateways/). Third-party gateway type: _link() renders a
 * "Pay Now" button that leads to modules/gateways/adeptix/pay.php (this module's own small
 * front-end script, not called on every invoice page load) which makes the real API call and
 * redirects - see that file for why _link() itself never calls the API directly.
 */

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

function adeptix_MetaData()
{
    return [
        'DisplayName' => 'Adeptix (Card / Bank / Wallet)',
        'APIVersion' => '1.1',
    ];
}

function adeptix_config()
{
    return [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'Adeptix (Card / Bank / Wallet)',
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
            'Description' => 'From your Adeptix dashboard\'s Settings page. Register this callback URL there: modules/gateways/callback/adeptix.php',
        ],
        'provider' => [
            'FriendlyName' => 'Provider ID',
            'Type' => 'text',
            'Size' => '25',
            'Default' => 'stripe',
            'Description' => 'An enabled provider id from your Adeptix dashboard\'s Providers page, e.g. "stripe".',
        ],
    ];
}

/**
 * @param array<string, mixed> $params
 */
function adeptix_link($params)
{
    $invoiceId = (int) $params['invoiceid'];
    $systemUrl = rtrim((string) $params['systemurl'], '/');
    $payUrl = $systemUrl . '/modules/gateways/adeptix/pay.php?invoiceid=' . $invoiceId;

    return '<a href="' . htmlspecialchars($payUrl) . '" class="btn btn-primary">' . htmlspecialchars($params['langpaynow']) . '</a>';
}
