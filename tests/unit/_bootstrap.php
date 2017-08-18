<?php
// Here you can initialize variables that will be available to your tests
require_once __DIR__.'/../../vendor/autoload.php';

$kernel = AspectMock\Kernel::getInstance();
$kernel->init([
    'appDir' => __DIR__,
    'cacheDir' => rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'mocks',
    'includePaths' => [
        __DIR__.'/../../classes',
        __DIR__.'/../../Core',
        __DIR__.'/../../Adapter',
        __DIR__.'/../_support/override',
    ],
]);

require_once __DIR__.'/../../config/defines.inc.php';
require_once __DIR__.'/../../config/settings.inc.php';
require_once __DIR__.'/../_support/unitloadclasses.php';
require_once __DIR__.'/../../config/alias.php';

if (!defined('_PS_PRICE_DISPLAY_PRECISION_')) {
	define('_PS_PRICE_DISPLAY_PRECISION_', 2);
}