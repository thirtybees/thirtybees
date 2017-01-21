<?php
// This is global bootstrap for autoloading
require_once __DIR__.'/../config/config.inc.php';

// Disable mails
Configuration::updateValue('PS_MAIL_METHOD', 3);
