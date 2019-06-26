<?php
// This is global bootstrap for autoloading

if (!defined('TESTS_RUNNING')) {
    define('TESTS_RUNNING', true);
}

// Enable debug mode to detect all errors and warnings during test run
if (!defined('_PS_MODE_DEV_')) {
    define('_PS_MODE_DEV_', true);
}
