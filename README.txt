

=== online billing service-woocommerce ===
Contributors: online-billing-service.com
Tags: shipping, woocommerce
Requires at least: 6.7
Requires PHP: 7.2
Tested up to: 6.7
Stable tag: 1.4.9
Author: Online Billing Service
Author URI: www.online-billing-service.com
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Invoicing woocommerce plugin for [online-billing-service.com](https://online-billing-service.com).

== Description ==
Using our WooCommerce plugin you can issue invoice and send them to your clients from your online shop dashboard.
Invoices will be generated on [online-billing-service.com](https://online-billing-service.com) website in your account.

Our plugin is relying on a 3rd party as a service, we are collecting some data from your website through our API in order to generate invoices.
Website link: [online-billing-service.com](https://online-billing-service.com)
[Terms and conditions](https://online-billing-service.com/terms-and-conditions)
[Privacy policy and GDPR](https://online-billing-service.com/privacy_policy)

[online-billing-service.com](https://online-billing-service.com)

A few notes about the sections above:

*   "Contributors" onlinebilling
*   "Tags" v1.4.9
*   "Requires at least" WordPress version 4.7.0 and WooCommerce 3.0.0.
*   "Tested up to" WordPress version 6.7 and WooCommerce 7.2.

== Installing ==

1. Upload `obs` in `/wp-content/plugins/` directory.
2. Activate plugin from 'Plugins' tab from your WordPress dashboard.
3. Login with api_key generated on [online-billing-service.com](https://online-billing-service.com).
4. You can configure plugin from Settings tab.

Or you can install our plugin directly from WordPress dashboard from Plugins section.

== Frequently Asked Questions ==

= How can I change the invoice state? =
The default state of an invoice is issued but from settings you can check the document state box and next generated invoices will have
the draft state.

== Screenshots ==

1. Homepage of plugin.
`/online billing service-homepage`

== Changelog ==
Ready to deploy!

= 1.4.9 =
* update company buyer name settings
= 0.9.8 =
* fix vat bug, added company name label
= 0.9.7 =
* generic fallback client exists function
= 0.9.6 =
* fix bugs with client identification in client_exists
= 0.9.5 =
* fix bug price divided by zero
= 0.9.4 =
* fix bug with float string conversion for product lines
= 0.9.1 =
* tested up to WordPress version 6.7
= 0.9.0 =
* fix all proforma_invoices generation known bugs

== Upgrade Notice ==

= 1.4.9 =
Update company buyer name settings and fix some bugs.

== Arbitrary section ==

We are using GraphQL API provided by [online-billing-service.com](https://online-billing-service.com).
