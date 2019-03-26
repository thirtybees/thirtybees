<?php
/**
 * Copyright (C) 2019 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2019 thirty bees
 * @license   Open Software License (OSL 3.0)
 */

/**
 * Class ErrorHandlerCore
 *
 * @since 1.1.0
 */
class ErrorHandlerCore
{
    /**
     * @var ErrorHandlerCore singleton instance;
     */
    protected static $instance;

    /**
     * @var bool true if error handling has been set up
     */
    protected $initialized = false;

    /**
     * @var array list of errors, warnings and notices encountered during request processing
     */
    protected $errorMessages = [];

    /**
     * @var object psr compliant logger
     */
    protected $logger = null;

    /**
     * Get instance of error handler
     *
     * @return ErrorHandlerCore
     *
     * @since 1.1.0
     */
    public static function getInstance()
    {
        if (! static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Initialize error handling logic
     *
     * @throws PrestaShopException
     *
     * @since 1.1.0
     */
    public function init()
    {
        if ($this->initialized) {
            throw new PrestaShopException('Error handler already initialized');
        }

        @ini_set('display_errors', 'off');
        @error_reporting(E_ALL | E_STRICT);

        // Set uncaught exception handler
        set_exception_handler([$this, 'uncaughtExceptionHandler']);

        // Set error handler
        set_error_handler([$this, 'errorHandler']);

        // register shutdown handler to catch fatal errors
        register_shutdown_function([$this, 'shutdown']);

        $this->initialized = true;
    }

    /**
     * Returns list of collected php error messags
     *
     * @param bool $includeSuppressed if true, result will include even
     *             messages that were suppressed using @ operator
     * @param int  $mask message types to return, defaults to E_ALL.
     *
     * @return array of collected error messages
     *
     * @since 1.1.0
     */
    public function getErrorMessages($includeSuppressed = false, $mask = E_ALL)
    {
        if ($this->errorMessages) {
            return array_filter($this->errorMessages, function ($error) use ($includeSuppressed, $mask) {
                if (!$includeSuppressed && $error['suppressed']) {
                    return false;
                }
                return (bool)($error['errno'] & $mask);
            });
        }

        return [];
    }

    /**
     * Uncaught exception handler - any uncaught exception will be processed by
     * this method.
     *
     * @param Exception $e uncaught exception
     *
     * @since 1.1.0
     */
    public function uncaughtExceptionHandler($e)
    {
        $exception = new PrestaShopException($e->getMessage(), $e->getCode(),
                                             null, $e->getTrace(),
                                             $e->getFile(), $e->getLine());
        $exception->displayMessage();
    }

    /**
     * Error handler. It only records any error, warning or notice to $errors
     * array and yields to default handler.
     *
     * @param int $errno level of the error raised
     * @param string $errstr error message
     * @param string $errfile filename that the error was raised in
     * @param int $errline line number the error was raised at
     *
     * @return bool
     *
     * @since 1.1.0
     */
    public function errorHandler($errno, $errstr, $errfile, $errline)
    {
        $suppressed = error_reporting() === 0;
        $file = $errfile;
        $line = $errline;

        if (SmartyCustom::isCompiledTemplate($file)) {
            $file = SmartyCustom::getCurrentTemplate();
            $line = 0;
        }

        $error = [
            'errno'       => $errno,
            'errstr'      => $errstr,
            'errfile'     => $file,
            'errline'     => $line,
            'suppressed'  => $suppressed,
            'type'        => static::getErrorType($errno),
            'level'       => static::getLogLevel($errno),
        ];
        $this->errorMessages[] = $error;
        if (! $suppressed) {
            $this->logMessage($error);
        }

        return $suppressed || static::displayErrorEnabled();
    }

    /**
     * Shutdown handler let us detect and react to fatal errors.
     *
     * @since 1.1.0
     */
    public function shutdown()
    {
        $error = error_get_last();
        if (static::isFatalError($error['type'])) {
            $stack = [
                1 => [
                    'file' => $error['file'],
                    'line' => $error['line'],
                    'type' => 'Fatal error',
                ]
            ];
            $exception = new PrestaShopException($error['message'], 0, null,
                                                 $stack, $error['file'],
                                                 $error['line']);
            $exception->displayMessage();
        }
    }

    /**
     * Sets external logger. If $replay parameter is true, then any already
     * collected error messages will be emitted.
     *
     * @param $logger
     * @param bool $replay
     *
     * @since 1.1.0
     */
    public function setLogger(LoggerInterface $logger, $replay=false)
    {
        $this->logger = $logger;
        if ($replay) {
            foreach($this->getErrorMessages(false) as $errorMessage) {
                $this->logMessage($errorMessage);
            }
        }
    }

    /**
     * Forward error message to psr compliant logger.
     *
     * @param $msg
     *
     * @since 1.1.0
     */
    protected function logMessage($msg)
    {
        if (! $this->logger) {
            return;
        }

        $message = static::formatErrorMessage($msg);

        switch ($msg['level']) {
            case LogLevel::EMERGENCY:
                $this->logger->emergency($message);
                break;
            case LogLevel::ALERT:
                $this->logger->alert($message);
                break;
            case LogLevel::CRITICAL:
                $this->logger->critical($message);
                break;
            case LogLevel::ERROR:
                $this->logger->error($message);
                break;
            case LogLevel::WARNING:
                $this->logger->warning($message);
                break;
            case LogLevel::NOTICE:
                $this->logger->notice($message);
                break;
            case LogLevel::INFO:
                $this->logger->info($message);
                break;
            case LogLevel::DEBUG:
                $this->logger->debug($message);
                break;
        }
    }

    /**
     * Converts $msg to string representation
     *
     * @param $msg array error message
     *
     * @return string
     */
    public static function formatErrorMessage($msg)
    {
        $file = static::normalizeFileName($msg['errfile']);

        return $msg['type'].': '
               .$msg['errstr'].' in '.$file.' at line '.$msg['errline'];
    }

    /**
     * Returns file name relative to thirtybees root directory.
     *
     * @param $file
     *
     * @return string file
     *
     * @since 1.1.0
     */
    public static function normalizeFileName($file)
    {
        return ltrim(str_replace([_PS_ROOT_DIR_, '\\'], ['', '/'], $file), '/');
    }


    /**
     * Returns error type for given error level.
     *
     * @param int $errno level of the error raised
     *
     * @return string error type
     *
     * @since 1.1.0
     */
    public static function getErrorType($errno)
    {
        switch ($errno) {
            case E_USER_ERROR:
            case E_ERROR:
                return 'Fatal error';
            case E_USER_WARNING:
            case E_WARNING:
                return 'Warning';
            case E_USER_NOTICE:
            case E_NOTICE:
                return 'Notice';
            case E_USER_DEPRECATED:
            case E_DEPRECATED:
                return 'Deprecation';
            default:
                return 'Unknown error';
        }
    }

    /**
     * Returns true if errno is a fatal error.
     *
     * @return boolean
     *
     * @since 1.1.0
     */
    public static function isFatalError($errno)
    {
       return ($errno === E_USER_ERROR || $errno === E_ERROR);
    }

    /**
     * Returns error PSR log level for given error level.
     *
     * @param int $errno level of the error raised
     *
     * @return string error log level
     *
     * @since 1.1.0
     */
    public static function getLogLevel($errno)
    {
        switch ($errno) {
            case E_USER_ERROR:
            case E_ERROR:
                return 'error';
            case E_USER_WARNING:
            case E_WARNING:
            case E_USER_DEPRECATED:
            case E_DEPRECATED:
                return 'warning';
            case E_USER_NOTICE:
            case E_NOTICE:
                return 'notice';
            default:
                return 'warning';
        }
    }

    /**
     * Returns true, if display_errors settings is turned on.
     *
     * @return boolean
     *
     * @since 1.1.0
     */
    public static function displayErrorEnabled() {
        $value = @ini_get('display_errors');
        switch (strtolower($value)) {
            case 'on':
            case 'yes':
            case 'true':
            case 'stdout':
            case 'stderr':
            case '1':
                return true;
            case 'off':
            case 'no':
            case '0':
                return false;
            default:
                return (bool) (int) $value;
        }
    }
}
