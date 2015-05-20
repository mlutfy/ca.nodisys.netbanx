Netbanx (Optimal Payments) payment processor for CiviCRM
========================================================

This extension makes it possible to use Netbanx as a payment gateway in CiviCRM.
It supports the payment method "without redirection" (post, not notify).
In Netbanx terms, this is the "Card Payments API".

The latest version of this extension can be found at:  
https://github.com/mlutfy/ca.nodisys.netbanx

FEATURES
--------

* Credit card payments using public or backend CiviCRM forms
* CVV2
* Supports Desjardins (logo)
* Partial recurring support.

Not supported at the moment:

* 3D-secure
* AVS (address verification service)
* Interac online

REQUIREMENTS
------------

* CiviCRM >= 4.4
* PHP >= 5.4
* Your site must be conform to PCI-DSS requirements.
* The "curl" PHP library. Under Debian, apt-get install php5-curl.

INSTALLATION
------------

See the INSTALL.txt file.

TODO
----

* 4.6 support: https://issues.civicrm.org/jira/browse/CRM-15555
* Remove mentions to Desjardins, replace by Netbanx.
* Make logo customizable (currently defaults to Desjardins).
* Fully support recurrent billing (ex: monthly donations, see below).
* UI to configure the civicrmdesjardins_tos_text and civicrmdesjardins_tos_url variables.
  (you can use the 'variable' module to configure them)
* More hook_requirements to have a clearer checklist of what needs to be done before having
  a site validated by Desjardins (based on the auto-evaluation).
* Respect the CiviCRM settings for accepted cards (amex, mastercard) - this is already managed
  via civicrm/admin/options/accept_creditcard?group=accept_creditcard&reset=1
  i.e. do not show the Amex/MC logo if the card is not accepted.
* Propose a patch to CiviCRM so that we have a standard way of displaying the receipt in the
  ThankYou.tpl, so that we do not need to systematically override the template.

RECURRENT BILLING
-----------------

Currently the recurrent billing (ex: monthly donations) will create a customer
profile, address and card in the customer vault, and will process the payment
using the token that was generated.

The extension does not currently save the token for regular processing (spare time only goes
so far). Please send patches or sponsor development if you need this.

MORE INFORMATION ABOUT NETBANX
------------------------------

Technical information about the payment gateway:

REST Card Payments (POST) API:  
https://developer.optimalpayments.com/en/documentation/card-payments-api/

Old SOAP-WS API:  
http://support.optimalpayments.com/docapi.asp  
http://support.optimalpayments.com/test_environment.asp

To open an account, contact Optimal Payments sales via their website.
If you are a Desjardins custom, contact Desjardins merchant services.

This module is in no way affiliated, endorsed or supported by Desjardins,
Netbanx/Optimal Solutions or Visa/Mastercard.

SUPPORT
-------

Please post bug reports in the issue tracker of this project on github:  
https://github.com/mlutfy/ca.nodisys.netbanx/issues

For general support questions, please use the CiviCRM Extensions forum:  
http://forum.civicrm.org/index.php/board,57.0.html

This is a community contributed extension written thanks to the financial
support of organisations using it, as well as the very helpful and collaborative
CiviCRM community.

While I do my best to provide volunteer support for this extension, please
consider financially contributing to support or development of this extension
if you can.

Commercial support is available through Coop SymbioTIC:  
https://www.symbiotic.coop

LICENSE
-------

Copyright (C) 2011-2015 Mathieu Lutfy <mathieu@symbiotic.coop>  
https://www.symbiotic.coop

License: AGPL v3: https://www.gnu.org/licenses/agpl.html

Thanks to Henrique Recidive for his commerce_netbanx module, which helped
to understand the Netbanx spec:  
http://drupal.org/project/commerce_netbanx
