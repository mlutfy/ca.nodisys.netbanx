Netbanx (Optimal Payments) payment processor for CiviCRM
========================================================

This extension makes it possible to use Netbanx as a payment gateway in CiviCRM.
It supports the payment method "without redirection" (process, not notify).
In Netbanx terms, this is a "hosted payment page - autonomous".

The latest version of this extension can be found at:
https://github.com/mlutfy/ca.nodisys.netbanx

FEATURES
--------

* Credit card payments using public or backend CiviCRM forms
* CVV2
* Supports Desjardins (logo)

Not supported at the moment:

* Recurrent payments
* 3D-secure
* AVS (address verification service)
* Interac online

REQUIREMENTS
------------

* CiviCRM >= 4.2
* Tested with CiviCRM 4.2 to 4.4
* Your site must be conform to PCI-DSS requirements.
* The "curl" PHP library. Under Debian, apt-get install php5-curl.

INSTALLATION
------------

See the INSTALL.txt file.

TODO
----

* Remove mentions to Desjardins, replace by Netbanx.
* Make logo customizable (currently defaults to Desjardins).
* Support recurrent billing (ex: monthly donations).
* UI to configure the civicrmdesjardins_tos_text and civicrmdesjardins_tos_url variables.
  (you can use the 'variable' module to configure them)
* More hook_requirements to have a clearer checklist of what needs to be done before having
  a site validated by Desjardins (based on the auto-evaluation).
* Respect the CiviCRM settings for accepted cards (amex, mastercard) - this is already managed
  via civicrm/admin/options/accept_creditcard?group=accept_creditcard&reset=1
  i.e. do not show the Amex/MC logo if the card is not accepted.
* Propose a patch to CiviCRM so that we have a standard way of displaying the receipt in the
  ThankYou.tpl, so that we do not need to systematically override the template.

MORE INFORMATION ABOUT NETBANX
------------------------------

Technical information about the payment gateway:
http://support.optimalpayments.com/docapi.asp
http://support.optimalpayments.com/test_environment.asp

To open an account, contact Optimal Payments sales via their website.
If you are with Desjardins, contact Desjardins merchant services.

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

If you appreciate this module, please consider donating 10$ to the CiviCRM project:
http://civicrm.org/participate/support-civicrm

While I do my best to provide volunteer support for this extension, please
consider financially contributing to support or development of this extension
if you can.
http://www.nodisys.ca/en

CREDITS
-------

Copyright (C) 2011-2013 Mathieu Lutfy <mathieu@nodisys.ca>
http://www.nodisys.ca

Thanks to Henrique Recidive for his commerce_netbanx module, which helped
to understand the Netbanx spec:
http://drupal.org/project/commerce_netbanx

LICENSE
-------

License: AGPL 3

Copyright (C) 2011-2013 Mathieu Lutfy (mathieu@bidon.ca)
http://www.nodisys.ca/en

For more information: https://civicrm.org/what/licensing

