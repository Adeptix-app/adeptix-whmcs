<?php
/**
 * Adeptix Crypto Payments webhook callback - register this exact URL
 * (modules/gateways/callback/adeptixcrypto.php) in the Adeptix dashboard's Settings page. See
 * https://docs.adeptix.app/webhooks
 */

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Adeptix\Webhooks;

$gatewayParams = getGatewayVariables('adeptixcrypto');
if (!$gatewayParams['type']) {
    http_response_code(503);
    die('Module Not Activated');
}

$rawBody = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_ADEPTIX_SIGNATURE'] ?? null;

if (!Webhooks::verifySignature($rawBody, $signature, $gatewayParams['webhookSecret'])) {
    http_response_code(401);
    die('Invalid signature');
}

$event = json_decode($rawBody, true);
if (!is_array($event) || ($event['event'] ?? null) !== 'crypto_payment.matched' || !isset($event['order_ref'])) {
    http_response_code(200);
    die('Ignored');
}

$invoiceId = checkCbInvoiceID($event['order_ref'], $gatewayParams['name']);
checkCbTransID($event['tx_hash']);

logTransaction($gatewayParams['name'], $event, 'Payment Received');

addInvoicePayment(
    $invoiceId,
    $event['tx_hash'],
    $event['amount'] ?? '',
    0,
    $gatewayParams['paymentmethod']
);

http_response_code(200);
echo 'OK';
