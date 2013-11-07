<?php
return array(
  0 => array(
    'module' => 'ca.nodisys.netbanx',
    'name' => 'Netbanx',
    'entity' => 'PaymentProcessorType',
    'params' => array(
      'version' => 3,
      'name' => 'Netbanx',
      'title' => 'Netbanx ',
      'description' => 'Netbanx Payment Processor',
      'class_name' => 'Payment_Netbanx',
      'billing_mode' => 'form',
      'subject_label' => 'Account Number',
      'user_name_label' => 'Merchant ID',
      'password_label' => 'Password',
      'url_site_default'=> 'https://webservices.optimalpayments.com/',
      'url_recur_default' => 'https://webservices.optimalpayments.com/',
      'url_site_test_default' => 'https://webservices.test.optimalpayments.com/',
      'url_recur_test_default' => 'https://webservices.test.optimalpayments.com/',
      'is_recur' => 1,
      'payment_type' => 1,
    ),
  )
);

