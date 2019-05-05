<?php
// Here you can initialize variables that will be available to your tests
require_once __DIR__.'/../../vendor/autoload.php';

/**
 * Turn any notice/warning/error into a full error, causing the Travis CI
 * build to fail. Else it won't get noticed in day to day operations.
 */
function errorHandlerThirty($errno, $errstr, $errfile, $errline)
{
  trigger_error(
      'Original error: '.$errstr.' in '.$errfile.':'.$errline,
      E_USER_ERROR
  );

  return true;
}

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

$oldErrorHandler = set_error_handler('errorHandlerThirty');
require_once __DIR__.'/../_support/unitloadclasses.php';
set_error_handler($oldErrorHandler);

require_once __DIR__.'/../../config/alias.php';
