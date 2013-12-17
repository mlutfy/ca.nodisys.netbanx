<?php

/*
 +--------------------------------------------------------------------+
 | Netbanx Payment Gateway Processor (post/autonomous)                |
 +--------------------------------------------------------------------+
 | Copyright Mathieu Lutfy 2010-2013                                  |
 | http://www.nodisys.ca/en                                           |
 +--------------------------------------------------------------------+
 | This file is part of the Payment gateway extension for CiviCRM.    |
 |                                                                    |
 | IMPORTANT:                                                         |
 | This is a community contributed extension. It is not endorsed or   |
 | supported by neither Desjardins nor CiviCRM. Use at your own risk. |
 |                                                                    |
 | LICENSE:                                                           |
 | This extension is free software; you can copy, modify, and         |
 | distribute it under the terms of the GNU Affero General Public     |
 | License Version 3, 19 November 2007.                               |
 |                                                                    |
 | This extension is distributed in the hope that it will be useful,  |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of     |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License along with this program; if not, contact CiviCRM LLC       |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 * Credit Card Payment Processor class for Netbanx (post/autonomous mode).
 */

require_once 'CRM/Core/Payment.php';

class CRM_Core_Payment_Netbanx extends CRM_Core_Payment {
  // Netbanx services paths
  const CIVICRM_NETBANX_SERVICE_CREDIT_CARD = 'creditcardWS/CreditCardService';

  // Netbanx stuff
  const CIVICRM_NETBANX_PAYMENT_ACCEPTED = 'ACCEPTED';
  const CIVICRM_NETBANX_PAYMENT_DECLINED = 'DECLINED';
  const CIVICRM_NETBANX_PAYMENT_ERROR    = 'ERROR';

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  static private $_singleton = null;

  /**
   * mode of operation: live or test
   *
   * @var object
   * @static
   */
  static protected $_mode = null;

  // IP of the visitor
  private $ip = 0;

  // CiviCRM invoice ID
  private $invoice_id = NULL;

  /**
   * Constructor
   *
   * @param string $mode the mode of operation: live or test
   *
   * @return void
   */
  function __construct($mode, &$paymentProcessor) {
    $this->_mode = $mode;
    $this->_paymentProcessor = $paymentProcessor;
    $this->_processorName = ts('Netbanx');
/*
    $this->_profile['mode'] = $mode;
    $this->_profile['user_name']  = $this->_paymentProcessor['user_name'];
    $this->_profile['password'] = $this->_paymentProcessor['password'];
    $this->_profile['subject'] = $this->_paymentProcessor['subject'];
*/
  }

  /**
   * Singleton function used to manage this object
   *
   * @param string $mode the mode of operation: live or test
   *
   * @return object
   * @static
   *
   */
  static function &singleton($mode, &$paymentProcessor) {
    $processorName = $paymentProcessor['name'];
    if (self::$_singleton[$processorName] === NULL) {
      self::$_singleton[$processorName] = new self($mode, $paymentProcessor);
    }
    return self::$_singleton[$processorName];
  }

  /**
   * Submit a payment using the Netbanx API:
   * http://support.optimalpayments.com/docapi.asp
   * http://support.optimalpayments.com/test_environment.asp
   *
   * @param array $params assoc array of input parameters for this transaction
   *
   * @return array the result in a nice formatted array (or an error object)
   * @public
   */
  function doDirectPayment(&$params) {
    if (! class_exists('SoapClient')) {
      return self::error('The Netbanx API service requires php-soap.  Please talk to your system administrator to get this configured (Debian/Ubuntu: apt-get install php-soap).');
    }

    $this->ip = $params['ip_address'];
    $this->invoice_id = $params['invoiceID'];

    if ($params['currencyID'] != 'CAD') {
       # [ML] FIXME return self::error('Invalid currency selection, must be CAD');
    }

/*
    // Fraud-protection: Validate the postal code
    if (! self::isValidPostalCode($params)) {
      watchdog('civicrmnetbanx', 'Invalid postcode for Canada: ' . print_r($params, 1));
      return self::netbanxFailMessage('NBX002', 'request invalid postcode', $params);
    }
*/

/* less necessary now that we have CVV2
    // Fraud-protection: Limit the number of transactions: 1 per hours
    if ($this->isTooManyTransactions($params)) {
      watchdog('civicrmnetbanx', 'Too many transactions from: ' . $params['ip_address']);
      return self::netbanxFailMessage('NBX003', 'request flood by ip', $params);
    }
*/

    self::log($params, 'civicrm params');

    // NETBANX START
    $data = array(
      'merchantAccount' => self::netbanxMerchantAccount(),
      'merchantRefNum' => $this->invoice_id,  // string max 255 chars
      'amount' => self::netbanxGetAmount($params),
      'card' => self::netbanxGetCard($params),
      'customerIP' => $params['ip_address'],
      'billingDetails' => self::netbanxGetBillingDetails($params),
    );

    $response = self::netbanxPurchase($data);

    if (! $response) {
      return self::netbanxFailMessage('NBX010', 'netbanx response null', $params);
    }

    if ($response->decision != self::CIVICRM_NETBANX_PAYMENT_ACCEPTED) {
      $receipt = self::generateReceipt($params, $response, FALSE);
      return self::netbanxFailMessage($receipt, 'netbanx response declined', $params, $response);
    }

    // Success
    $params['trxn_id']       = $response->confirmationNumber;
    $params['gross_amount']  = $data['amount'];

    // Assigning the receipt to the $params doesn't really do anything
    // In previous versions, we would patch the core in order to show the receipt.
    // It would be nice to have something in CiviCRM core in order to handle this.
    $params['receipt_netbanx'] = self::generateReceipt($params, $response);
    $params['trxn_result_code'] = $response->confirmationNumber . "-" . $response->authCode . "-" . $response->cvdResponse . "-" . $response->avsResponse;

    db_query("INSERT INTO {civicrmdesjardins_receipt} (trx_id, receipt, first_name, last_name, card_type, card_number, timestamp, ip)
              VALUES (:trx_id, :receipt, :first_name, :last_name, :card_type, :card_number, :timestamp, :ip)",
              array(
                ':trx_id' => $params['trxn_id'],
                ':receipt' => $params['receipt_netbanx'],
                ':first_name' => $params['first_name'],
                ':last_name' => $params['last_name'],
                ':card_type' => $params['credit_card_type'],
                ':card_number' => self::netbanxGetCardForReceipt($params['credit_card_number']),
                ':timestamp' => time(),
                ':ip' => $this->ip,
             ));

    // Invoke hook_civicrmdesjardins_success($params, $purchase).
    module_invoke_all('civicrmdesjardins_success', $params, $response);

    return $params;
  }

  /**
   * Returns a correctly formatted array with the merchant account info.
   */
  function netbanxMerchantAccount() {
    return array(
      'accountNum' => $this->_paymentProcessor['subject'],
      'storeID' => $this->_paymentProcessor['user_name'], // aka 'Merchant ID'
      'storePwd' => $this->_paymentProcessor['password'],
    );
  }

  /**
   * Extracts the transaction amount
   * Returns in the format: 20.50
   */
  function netbanxGetAmount($params) {
    $amount = 0;

    if (! empty($params['amount'])){
      $amount = $params['amount'];
    }
    else{
      $amount = $params['amount_other'];
    }

    // format: 10.00
    $amount = number_format($amount, 2, '.', '');

    return $amount;
  }

  /**
   * Extracts the credit card info.
   * Returns an array.
   */
  function netbanxGetCard($params) {
    $card = array(
      'cardNum' => $params['credit_card_number'],
      'cardExpiry' => array(
        'month' => $params['month'],
        'year' => $params['year'],
      ),
    );

    // Add security code.
    if (! empty($params['cvv2'])) {
      $card['cvdIndicator'] = 1;
      $card['cvdIndicatorSpecified'] = TRUE;
      $card['cvd'] = $params['cvv2'];
    }

    return $card;
  }

  /**
   * Extracts the billing details info.
   * Returns an array.
   */
  function netbanxGetBillingDetails($params) {
    $billing = array(
      'firstName' => $params['first_name'],
      'lastName' => $params['last_name'],
      'street' => $params['street_address'],
      'city' => $params['city'],
      'country' => $params['country'],
      'countrySpecified' => TRUE,
      'zip' => $params['postal_code'],
      'email' => $params['email-Primary'],
    );

    // Add state or region based on country
    if (in_array($params['country'], array('US', 'CA'))) {
      $billing['state'] = $params['state_province'];
    }
    else {
      $billing['region'] = $params['state_province'];
    }

    return $billing;
  }

  /**
   * Initiates the soap client
   * see @netbanxPurchase()
   */
  function netbanxGetSoapClient($service) {
    $wsdl_url = $this->netbanxGetWsdlUrl($service);
    return new SoapClient($wsdl_url);
  }

  /**
   * Returns the appropriate web service URL
   * see @netbanxPurchase()
   * FIXME: should use civicrm gateway settings, not hardcode URLs
   */
  function netbanxGetWsdlUrl($service) {
    $url = NULL;

    switch ($this->_mode) {
      case 'test':
        $url = 'https://webservices.test.optimalpayments.com/';
        break;
      case 'live':
        $url = 'https://webservices.optimalpayments.com/';
        break;
      default:
        die('netbanxGetWsdlUrl: unknown mode: ' . $this->_mode);
    }

    return $url . $service . '/v1?wsdl';
  }

  /**
   * Send the purchase request to Netbanx
   */
  function netbanxPurchase($data) {
    self::log($data, 'netbanx request');

    $netbanx = $this->netbanxGetSoapClient(self::CIVICRM_NETBANX_SERVICE_CREDIT_CARD);
    $response = $netbanx->ccPurchase(array('ccAuthRequestV1' => $data));

    $v1 = $response->ccTxnResponseV1;

    // re-order the vender-specific data (ex: Desjardins)
    // otherwise it's an array and doesn't look very reliable:
    /*
     [detail] => Array (
       [0] => stdClass Object ( [tag] => BATCH_NUMBER [value] => 019)
       [1] => stdClass Object ( [tag] => SEQ_NUMBER [value] => 036)
       [2] => stdClass Object ( [tag] => EFFECTIVE_DATE [value] => 121003)
       [3] => stdClass Object ( [tag] => TERMINAL_ID [value] => 85025505))
    */
    if (property_exists($v1, 'addendumResponse')) {
      $v1->addendum = array();

      foreach ($v1->addendumResponse->detail as $key => $val) {
        $tag = $val->tag;
        $v1->addendum[$tag] = $val->value;
      }
    }

    return $v1;
  }

  /**
   * Input: 4511111111111111
   * Returns: **** **** **** 1111 (Visa/MC/Amex requirement)
   */
  function netbanxGetCardForReceipt($card_number) {
    $a = substr($card_number, 0, 2);
    $b = substr($card_number, -4, 4);
    $str = '**** **** **** ' . $b;
    return $str;
  }

  /**
   * Make CiviCRM return a fail message and cancel the transaction.
   * FIXME: this is not very clean..
   */
  function netbanxFailMessage($code, $errtype, $request = NULL, $response = NULL) {
    self::log($response, 'netbanx response null', TRUE);

    // FIXME: format: self::error(9003, 'Message here'); ?
    if (is_numeric($code)) {
      return self::error(t("Error") . ": " . t('The transaction could not be processed, please contact us for more information.') . ' (code: ' . $code . ') '
             . '<div class="civicrm-dj-retrytx">' . t("The transaction was not approved. Please verify your credit card number and expiration date.") . '</div>');
    }

    return self::error(t('The transaction could not be processed, please contact us for more information.')
           . '<div class="civicrm-dj-retrytx">' . t("The transaction was not approved. Please verify your credit card number and expiration date.") . '</div>'
           . '<br/><pre class="civicrm-dj-receiptfail">' . $code . '</pre>');
  }

  /**
   * Validate the postal code.
   * Returns TRUE if the postal code is valid.
   */
  function isValidPostalCode($params) {
    if ($params['country'] != 'CA') {
      return TRUE;
    }

    $province     = $params['state_province'];
    $postal_code  = $params['postal_code'];
    $postal_first = strtoupper(substr($postal_code, 0, 1));

    $provinces_codes = array(
      'AB' => array('T'),
      'BC' => array('V'),
      'MB' => array('R'),
      'NB' => array('E'),
      'NL' => array('A'),
      'NT' => array('X'),
      'NS' => array('B'),
      'NU' => array('X'),
      'ON' => array('K', 'L', 'M', 'N', 'P'),
      'PE' => array('C'),
      'QC' => array('H', 'J', 'G'),
      'SK' => array('S'),
      'YT' => array('Y'),
    );

    if (in_array($postal_first, $provinces_codes[$province])) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Check whether the person (by IP address) has been doing too many transactions lately (2 tx in the past 6 hours)
   * Returns TRUE if there have been too many transactions
   */
  function isTooManyTransactions($params) {
    $ip = $params['ip_address'];

    $nb_tx_lately = db_query('SELECT count(*) from {civicrmdesjardins_receipt}
       WHERE ip = :ip and timestamp > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 HOUR))',
       array(':ip' => $ip))->fetchField();

    if ($nb_tx_lately >= 400) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * error : either an object that implements getResponseCode() and getErrorMessage, or a string.
   * errnum : if the error is a string, this should have the error number.
   */
  function &error($error = null, $errnum = 9002) {
      $e =& CRM_Core_Error::singleton();
      if (is_object($error)) {
          $e->push( $error->getResponseCode(),
                    0, null,
                    $error->getErrorMessage());
      } elseif (is_string($error)) {
          $e->push( $errnum,
                    0, null,
                    $error);
      } else {
          $e->push(9001, 0, null, "Unknown System Error.");
      }
      return $e;
  }

  /**
   * This function checks to see if we have the right config values
   *
   * @return string the error message if any
   * @public
   */
  function checkConfig() {
    $error = array();

    if (empty($this->_paymentProcessor['user_name'])) {
      $error[] = ts('Merchant ID is not set in the Administer CiviCRM &raquo; Payment Processor.');
    }

    if (empty($this->_paymentProcessor['password'])) {
      $error[] = ts('Password is not set in the Administer CiviCRM &raquo; Payment Processor.');
    }

    if (! empty($error)) {
      return implode('<p>', $error);
    } else {
      return null;
    }
  }

  /**
   * Logs exchanges with Netbanx
   */
  function log($message, $type, $fail = 0) {
    $time = time();

    // If the message is a params, data or response, cleanse it before print_r
    // credit card numbers/cvv2 must not be stored in the database
    if (is_array($message)) {
      if (isset($message['card'])) {
        $message['card']['cardNum'] = self::netbanxGetCardForReceipt($message['card']['cardNum']);
        $message['card']['cvd'] = 'XYZ';
      }

      if (isset($message['credit_card_number'])) {
        $message['credit_card_number'] = self::netbanxGetCardForReceipt($message['credit_card_number']);
        $message['cvv2'] = 'XYZ';
      }

      $message = print_r($message, 1);
    }
    elseif (is_object($message)) {
      $message = print_r($message, 1);
    }

    // sometimes the field is empty, not 0
    if (! $fail) {
      $fail = 0;
    }

    db_query("INSERT INTO {civicrmdesjardins_log} (trx_id, timestamp, type, message, fail, ip)
               VALUES (:trx_id, :timestamp, :type, :message, :fail, :ip)",
              array(':trx_id' => $this->invoice_id, ':timestamp' => $time, ':type' => $type, ':message' => $message, ':fail' => $fail, ':ip' => $this->ip));
  }

  /**
   * Generates a human-readable receipt using the purchase response from Desjardins.
   * trx_id : CiviCRM transaction ID
   * amount : numeric amount of the transcation
   * purchase : response from Netbanx (object)
   * success : whether this is a receipt for a successful or failed transaction (not really used)
   */
  function generateReceipt($params, $response, $success = TRUE) {
    $receipt = '';

    $trx_id = $this->invoice_id; // CiviCRM's ID
    $tx = $purchase->merchant->transaction;

    $receipt .= self::getNameAndAddress() . "\n\n";

    $receipt .= ts('CREDIT CARD TRANSACTION RECORD') . "\n\n";

    $receipt .= ts('Date: %1', array(1 => $response->txnTime)) . "\n";
    $receipt .= ts('Transaction: %1', array(1 => $this->invoice_id)) . "\n";
    $receipt .= ts('Type: purchase') . "\n"; // could be preauthorization, preauth completion, refund.
    $receipt .= ts('Authorization: %1', array(1 => $response->authCode)) . "\n";
    $receipt .= ts('Confirmation: %1', array(1 => $response->confirmationNumber)) . "\n";

    // Not necessary, according to Netbanx rep.
    // $receipt .= ts('Seq.: %1 Batch: %2', array(1 => $response->addendum['SEQ_NUMBER'], 2 => $response->addendum['BATCH_NUMBER'])) . "\n\n";

    $receipt .= ts('Credit card type: %1', array(1 => $params['credit_card_type'])) . "\n";
    $receipt .= ts('Credit card holder name: %1', array(1 => $params['first_name'] . ' ' . $params['last_name'])) . "\n";
    $receipt .= ts('Credit card number: %1', array(1 => self::netbanxGetCardForReceipt($params['credit_card_number']))) . "\n\n";

    $receipt .= ts('Transaction amount: %1', array(1 => CRM_Utils_Money::format($params['amount']))) . "\n\n";

    if ($response->decision == self::CIVICRM_NETBANX_PAYMENT_ACCEPTED) {
      $receipt .= ts('TRANSACTION APPROVED - THANK YOU') . "\n\n";
    }
    elseif ($response->decision == self::CIVICRM_NETBANX_PAYMENT_ERROR) {
      $receipt .= wordwrap(ts('TRANSACTION CANCELLED - %1', array(1 => $response->description))) . "\n\n";
    }
    elseif ($response->decision == self::CIVICRM_NETBANX_PAYMENT_DECLINED) {
      $description = $response->description;

      // Silly.. but we try to translate as many messages as possible.
      if ($description == 'Your request has been declined by the issuing bank.') {
        $description = ts('Your request has been declined by the issuing bank.');
      }

      $receipt .= ts('TRANSACTION DECLINED - %1', array(1 => $description)) . "\n\n";
    }
    else {
      $receipt .= $response->decision . ' - ' . $response->description . "\n\n";
    }

    if (function_exists('variable_get')) {
      $tos_url  = variable_get('civicrmdesjardins_tos_url', FALSE);
      $tos_text = variable_get('civicrmdesjardins_tos_text', FALSE);

      if ($tos_url) {
        $receipt .= ts("Terms and conditions:") . "\n";
        $receipt .= $tos_url . "\n\n";
      }

      if ($tos_text) {
        $receipt .= wordwrap($tos_text);
      }
    }

    // Add obligatory notes:
    $receipt .= "\n";
    $receipt .= ts('Prices are in canadian dollars ($ CAD).') . "\n";
    $receipt .= ts("This transaction is non-taxable.");

    return $receipt;
  }

  /**
   * Returns the org's name and address
   */
  function getNameAndAddress() {
    $receipt = '';

    // Fetch the domain name, but allow to override it (Desjardins requires that it
    // be the exact business name of the org, and sometimes we use shorter names.
    $domain = civicrm_api('Domain', 'get', array('version' => 3));

    $org_name = variable_get('civicrmdesjardins_orgname', NULL);

    if (! $org_name) {
      $org_name = $domain['values'][1]['name'];
    }

    // get province abbrev
    $province = db_query('SELECT abbreviation FROM {civicrm_state_province} WHERE id = :id', array(':id' => $domain['values'][1]['domain_address']['state_province_id']))->fetchField();
    // $country = db_query('SELECT name FROM {civicrm_country} WHERE id = :id', array(':id' => $domain['values'][1]['domain_address']['country_id']))->fetchField();

    $receipt .= $org_name . "\n";
    $receipt .= $domain['values'][1]['domain_address']['street_address'] . "\n";
    $receipt .= $domain['values'][1]['domain_address']['city'] . ', ' . $province;

    return $receipt;
  }
}


