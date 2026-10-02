<?php
/**
 * Adeptix Merchant Payments - "Pay Now" landing page.
 *
 * Only reached when a client actually clicks "Pay Now" (see ../adeptix.php's _link()) - this is
 * deliberately NOT called on every invoice page render, since it makes a real, costed API call
 * that creates a new hosted checkout session each time it runs.
 */

require_once __DIR__ . '/../../../init.php'; // WHMCS's own bootstrap - also registers its own autoloader (WHMCS\Database\Capsule, etc.)
require_once __DIR__ . '/../vendor/autoload.php'; // this module's bundled Adeptix SDK

use WHMCS\Database\Capsule;
use Adeptix\AdeptixClient;
use Adeptix\Exceptions\AdeptixApiException;

$invoiceId = isset($_GET['invoiceid']) ? (int) $_GET['invoiceid'] : 0;

// Only the invoice's own owner may pay it - never trust the invoice id alone.
if (!$invoiceId || empty($_SESSION['uid'])) {
    header('Location: ' . App::getSystemURL() . '/clientarea.php?action=invoices');
    exit;
}

$invoice = Capsule::table('tblinvoices')->where('id', $invoiceId)->first();
if (!$invoice || (int) $invoice->userid !== (int) $_SESSION['uid']) {
    header('Location: ' . App::getSystemURL() . '/clientarea.php?action=invoices');
    exit;
}

$gatewayParams = getGatewayVariables('adeptix');
if (!$gatewayParams['type']) {
    die('Adeptix gateway module is not active.');
}

$client = new AdeptixClient($gatewayParams['apiKey']);

try {
    $payment = $client->payments()->create([
        'amount' => number_format((float) $invoice->total, 2, '.', ''),
        'currency' => $invoice->currency ?: 'USD',
        'email' => Capsule::table('tblclients')->where('id', $invoice->userid)->value('email'),
        'provider' => $gatewayParams['provider'],
        'order_ref' => (string) $invoiceId,
    ]);
} catch (AdeptixApiException $e) {
    logTransaction('adeptix', ['error' => $e->getMessage(), 'invoiceid' => $invoiceId], 'Checkout creation failed');
    die('Could not start checkout: ' . htmlspecialchars($e->getMessage()));
}

header('Location: ' . $payment['payment_url']);
exit;
