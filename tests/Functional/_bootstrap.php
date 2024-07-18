<?php
/** @noinspection PhpUnhandledExceptionInspection */
require_once __DIR__.'/../../config/config.inc.php';

// Disable mails
Configuration::updateValue(Configuration::MAIL_TRANSPORT, Mail::TRANSPORT_NONE);

// Disable Friendly URLs
Configuration::updateValue('PS_REWRITING_SETTINGS', 0);

// Enable Smarty cache
Configuration::updateValue('PS_SMARTY_CACHE', 1);
Configuration::updateValue('PS_SMARTY_FORCE_COMPILE', 1);

// SET tracking id
Configuration::updateValue(Configuration::TRACKING_ID, 'test-runner');

// Enable webservice
Configuration::updateValue('PS_WEBSERVICE', 1);
define('WEBSERVICE_TEST_KEY', 'TEST_KEY________________________');
if (!$key = WebserviceKey::getInstanceByKey(WEBSERVICE_TEST_KEY)) {
    $key = new WebserviceKey();
    $key->key = WEBSERVICE_TEST_KEY;
    $key->context_employee_id = 1;
    $key->save();
}

// set all permission
$allPersmission = [];
foreach (WebserviceRequest::getResources() as $k => $_) {
    $allPersmission[$k] = ['GET' => 1, 'PUT' => 1, 'POST' => 1, 'DELETE' => 1, 'HEAD' => 1];
}
WebserviceKey::setPermissionForAccount($key->id, $allPersmission);