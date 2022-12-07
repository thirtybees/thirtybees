<?php
/** @noinspection PhpUnhandledExceptionInspection */
require_once __DIR__.'/../../config/config.inc.php';

// Disable mails
Configuration::updateValue('PS_MAIL_METHOD', Mail::MAIL_METHOD_NONE);

// Disable Friendly URLs
Configuration::updateValue('PS_REWRITING_SETTINGS', 0);

// Enable Smarty cache
Configuration::updateValue('PS_SMARTY_CACHE', 1);
Configuration::updateValue('PS_SMARTY_FORCE_COMPILE', 1);
