<?php

require_once 'mahjimportcontacts.civix.php';

use CRM_Mahjimportcontacts_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function mahjimportcontacts_civicrm_config(&$config): void {
  _mahjimportcontacts_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function mahjimportcontacts_civicrm_install(): void {
  _mahjimportcontacts_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function mahjimportcontacts_civicrm_enable(): void {
  _mahjimportcontacts_civix_civicrm_enable();
}
