<?php
// Here you can initialize variables that will be available to your tests
require_once __DIR__.'/../../vendor/autoload.php';

$kernel = AspectMock\Kernel::getInstance();
$kernel->init([
    'debug' => true,
    'appDir' => __DIR__,
    'includePaths' => [
        __DIR__.'/../../controllers',
        __DIR__.'/../../Core',
        __DIR__.'/../../Adapter',
        __DIR__.'/../../override',
    ],
]);

require_once __DIR__.'/../../config/defines.inc.php';
require_once __DIR__.'/../../config/settings.inc.php';
require_once __DIR__.'/../_support/unitloadclasses.php';
require_once __DIR__.'/../../config/alias.php';
