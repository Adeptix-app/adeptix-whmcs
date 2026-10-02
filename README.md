# Adeptix Payment Gateway Modules for WHMCS

Accept direct on-chain crypto payments (USDT/USDC) and hosted card/bank/wallet checkouts through
[Adeptix](https://adeptix.app), from two WHMCS gateway modules.

Full API reference: **https://docs.adeptix.app**

## What this adds

WHMCS gateway modules are one-file-per-payment-method by convention, so this ships as two
independent modules (both under **Setup → Payments → Payment Gateways**):

- **Adeptix (Card / Bank / Wallet)** (`modules/gateways/adeptix.php`) — hosted checkout via
  whichever provider you configure (e.g. `stripe`). "Pay Now" leads to
  `modules/gateways/adeptix/pay.php`, which creates the checkout and redirects — this only runs
  when a client actually clicks Pay Now, not on every invoice page load.
- **Adeptix Crypto (USDT/USDC)** (`modules/gateways/adeptixcrypto.php`) — direct on-chain payment
  on BSC, Polygon, or Tron. "Pay Now" leads to `modules/gateways/adeptixcrypto/pay.php`, which
  shows the deposit address and exact amount (reusing an unexpired request on repeat visits rather
  than creating a new one every time).

Both invoices are marked paid automatically via their own webhook callback
(`modules/gateways/callback/adeptix.php` and `.../adeptixcrypto.php`), built on WHMCS's own
documented callback helpers (`checkCbInvoiceID`, `checkCbTransID`, `logTransaction`,
`addInvoicePayment` — see https://developers.whmcs.com/payment-gateways/callbacks/).

## Install

1. Copy this module's `modules/gateways/` contents into your WHMCS installation's own
   `modules/gateways/` directory (merge, don't replace).
2. Go to **Setup → Payments → Payment Gateways**, activate **Adeptix (Card / Bank / Wallet)**
   and/or **Adeptix Crypto (USDT/USDC)**, and configure each one's API key, webhook secret, and
   provider/chain/token.
3. Register the exact callback URL shown in each module's own configuration description in your
   Adeptix dashboard's Settings page.

## How it works

Both modules build on the [official Adeptix PHP SDK](https://github.com/Adeptix-app/adeptix-php)
(bundled once at `modules/gateways/vendor/`, shared by both modules' `pay.php` and callback files —
not a separate install step). Order matching uses the WHMCS invoice id as the `order_ref` sent to
Adeptix, resolved back via `checkCbInvoiceID()` when a webhook arrives.

## Requirements

- PHP 8.0+
- A current WHMCS install (built against WHMCS's Gateway Module API v1.1 and its documented
  `WHMCS\Database\Capsule` query builder)

## Verification note

Built directly against WHMCS's own official sample gateway module
(`WHMCS/sample-gateway-module` on GitHub) and developer documentation
(developers.whmcs.com/payment-gateways/) rather than guessed at, and every PHP file passes `php -l`.
This was **not** exercised against a real running WHMCS install — WHMCS is commercially licensed
software with no free/trial edition available to test against in this environment, unlike the
other three Adeptix CMS plugins (WooCommerce, PrestaShop, OpenCart). Review and test a real invoice/payment/webhook flow in a
staging WHMCS install before using this in production.

## License

MIT
