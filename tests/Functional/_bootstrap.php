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