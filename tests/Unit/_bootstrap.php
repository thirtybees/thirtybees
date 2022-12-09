<?php
// Here you can initialize variables that will be available to your tests
use Tests\Support\TestClassIndex;

require_once __DIR__.'/../../vendor/autoload.php';

/**
 * Turn any notice/warning/error into a full error, causing the Travis CI
 * build to fail. Else it won't get noticed in day to day operations.
 *
 * @return true
 */
function errorHandlerThirty($errno, $errstr, $errfile, $errline)
{
  trigger_error(
      'Original error: '.$errstr.' in '.$errfile.':'.$errline,
      E_USER_ERROR
  );

  return true;
}

require_once __DIR__.'/../../config/defines.inc.php';
require_once __DIR__.'/../../config/settings.inc.php';
require_once __DIR__.'/../Support/TestClassIndex.php';

$oldErrorHandler = set_error_handler('errorHandlerThirty');
set_error_handler($oldErrorHandler);
spl_autoload_register([new TestClassIndex, 'autoload']);

require_once __DIR__.'/../../config/alias.php';
