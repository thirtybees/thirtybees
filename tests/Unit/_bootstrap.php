<?php
// Here you can initialize variables that will be available to your tests
use Tests\Support\TestClassIndex;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/defines.inc.php';
require_once __DIR__ . '/../../config/settings.inc.php';

spl_autoload_register([new TestClassIndex(), 'autoload']);

require_once __DIR__ . '/../../config/alias.php';
