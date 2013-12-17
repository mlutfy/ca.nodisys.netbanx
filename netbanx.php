<?php

require_once 'netbanx.civix.php';

define('NETBANX_SETTINGS_GROUP', 'Netbanx Extension');

/**
 * Implementation of hook_civicrm_config
 */
function netbanx_civicrm_config(&$config) {
  _netbanx_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function netbanx_civicrm_xmlMenu(&$files) {
  _netbanx_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function netbanx_civicrm_install() {
  return _netbanx_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function netbanx_civicrm_uninstall() {
  return _netbanx_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function netbanx_civicrm_enable() {
  return _netbanx_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function netbanx_civicrm_disable() {
  return _netbanx_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 */
function netbanx_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _netbanx_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function netbanx_civicrm_managed(&$entities) {
  return _netbanx_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_buildForm().
 */
function netbanx_civicrm_buildForm($formName, &$form) {
  $f = 'netbanx_civicrm_buildForm_' . $formName;

  if (function_exists($f)) {
    $f($form);
  }
}

/**
 * Form: CRM_Contribute_Form_Contribution_Main
 */
function netbanx_civicrm_buildForm_CRM_Contribute_Form_Contribution_Main(&$form) {
  // Adds the credit card logo to the contribution page (billing block)
  CRM_Core_Region::instance('billing-block')->add(array(
    'template' => 'CRM/Netbanx/Form/BillingBlockLogo.tpl',
    'weight' => -10,
  ));

  $resources = CRM_Core_Resources::singleton();
  $resources->addStyleFile('ca.nodisys.netbanx', 'netbanx.css');
}

/**
 * Form: CRM_Contribute_Form_Contribution_ThankYou
 */
function netbanx_civicrm_buildForm_CRM_Contribute_Form_Contribution_ThankYou(&$form) {
  // Adds the credit card receipt from Netbanx to the contribution ThankYou page
  CRM_Core_Region::instance('contribution-thankyou-billing-block')->add(array(
    'template' => 'CRM/Netbanx/Form/ThankYouReceipt.tpl',
  ));
}

/**
 * Returns the Netbanx receipt for the CC transaction
 * Called from CRM/Netbanx/Form/ThankYouReceipt.tpl
 */
function netbanx_civicrm_receipt($trx_id) {
  return  db_query('SELECT receipt FROM {civicrmdesjardins_receipt} WHERE trx_id = :trx_id', array(':trx_id' => $trx_id))->fetchField();
}

/**
 * Implementation of hook_civicrm_navigationMenu().
 */
function netbanx_civicrm_navigationMenu(&$params) {
  // Get the ID of the 'Administer/System Settings' menu
  $adminMenuId = CRM_Core_DAO::getFieldValue('CRM_Core_BAO_Navigation', 'Administer', 'id', 'name');
  $settingsMenuId = CRM_Core_DAO::getFieldValue('CRM_Core_BAO_Navigation', 'System Settings', 'id', 'name');

  // Skip adding menu if there is no administer menu
  if (! $adminMenuId) {
    CRM_Core_Error::debug_log_message('The Netbanx Extension could not find the Administer menu item. Menu item to configure this extension will not be added.');
    return;
  }

  if (! $settingsMenuId) {
    CRM_Core_Error::debug_log_message('The Netbanx Extension could not find the System Settings menu item. Menu item to configure this extension will not be added.');
    return;
  }

  // get the maximum key under administer menu
  $maxSettingsMenuKey = max(array_keys($params[$adminMenuId]['child'][$settingsMenuId]['child']));
  $nextSettingsMenuKey = $maxSettingsMenuKey + 1;

  $params[$adminMenuId]['child'][$settingsMenuId]['child'][$nextSettingsMenuKey] =  array(
    'attributes' => array(
      'name'       => 'Netbanx Settings',
      'label'      => 'Netbanx Settings',
      'url'        => 'civicrm/admin/setting/netbanx&reset=1',
      'permission' => 'administer CiviCRM',
      'parentID'   => $settingsMenuId,
      'navID'      => $nextSettingsMenuKey,
      'active'      => 1,
    ),
  );
}

