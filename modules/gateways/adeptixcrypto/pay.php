<?php
/**
 * Adeptix Crypto Payments - deposit instructions page.
 *
 * Reuses an existing unexpired deposit request for this invoice if one exists (so refreshing the
 * page doesn't create a new on-chain address/amount each time), otherwise creates one.
 */

require_once __DIR__ . '/../../../init.php'; // WHMCS's own bootstrap - also registers its own autoloader (WHMCS\Database\Capsule, etc.)
require_once __DIR__ . '/../vendor/autoload.php'; // this module's bundled Adeptix SDK

use WHMCS\Database\Capsule;
use Adeptix\AdeptixClient;
use Adeptix\Exceptions\AdeptixApiException;

$invoiceId = isset($_GET['invoiceid']) ? (int) $_GET['invoiceid'] : 0;

if (!$invoiceId || empty($_SESSION['uid'])) {
    header('Location: ' . App::getSystemURL() . '/clientarea.php?action=invoices');
    exit;
}

$invoice = Capsule::table('tblinvoices')->where('id', $invoiceId)->first();
if (!$invoice || (int) $invoice->userid !== (int) $_SESSION['uid']) {
    header('Location: ' . App::getSystemURL() . '/clientarea.php?action=invoices');
    exit;
}

$gatewayParams = getGatewayVariables('adeptixcrypto');
if (!$gatewayParams['type']) {
    die('Adeptix Crypto gateway module is not active.');
}

$existing = Capsule::table('mod_adeptix_crypto_requests')->where('invoice_id', $invoiceId)->first();

if ($existing && strtotime($existing->expires_at) > time()) {
    $deposit = $existing;
} else {
    $client = new AdeptixClient($gatewayParams['apiKey']);

    try {
        $request = $client->crypto()->createPaymentRequest([
            'chain' => $gatewayParams['chain'],
            'token' => $gatewayParams['token'],
            'amount' => number_format((float) $invoice->total, 2, '.', ''),
            'order_ref' => (string) $invoiceId,
            'customer_email' => Capsule::table('tblclients')->where('id', $invoice->userid)->value('email'),
        ]);
    } catch (AdeptixApiException $e) {
        logTransaction('adeptixcrypto', ['error' => $e->getMessage(), 'invoiceid' => $invoiceId], 'Deposit request failed');
        die('Could not create a crypto payment request: ' . htmlspecialchars($e->getMessage()));
    }

    Capsule::table('mod_adeptix_crypto_requests')->updateOrInsert(
        ['invoice_id' => $invoiceId],
        [
            'payment_request_id' => $request['payment_request_id'],
            'pay_to_address' => $request['pay_to_address'],
            'amount' => $request['amount'],
            'chain' => $request['chain'],
            'token' => $request['token'],
            'expires_at' => $request['expires_at'],
        ]
    );

    $deposit = (object) $request;
}

?><!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Pay with crypto</title></head>
<body>
  <h1>Send exactly <?php echo htmlspecialchars($deposit->amount); ?> <?php echo htmlspecialchars($deposit->token); ?></h1>
  <p>On <?php echo htmlspecialchars($deposit->chain); ?> to:</p>
  <p><code><?php echo htmlspecialchars($deposit->pay_to_address); ?></code></p>
  <p>This address expires at <?php echo htmlspecialchars($deposit->expires_at); ?>. Your invoice will be marked paid automatically once the deposit is confirmed.</p>
  <p><a href="<?php echo htmlspecialchars(App::getSystemURL()); ?>/viewinvoice.php?id=<?php echo $invoiceId; ?>">Back to invoice</a></p>
</body>
</html>
