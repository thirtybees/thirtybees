<?php
/**
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2018 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.thirtybees.com for more information.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2017-2018 thirty bees
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class ErrorHandlerCore
 *
 * @since 1.0.9
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
     * @var bool indicates, whether we should prevent default error handler or not
     */
    protected $preventDefaultErrorHandler = false;

    /**
     * @var object psr compliant logger
     */
    protected $logger = null;

    /**
     * Get instance of error handler
     *
     * @return ErrorHandlerCore
     *
     * @since   1.0.9
     * @version 1.0.9 Initial version
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
     * @since   1.0.9
     * @version 1.0.9 Initial version
     * @throws PrestaShopException
     */
    public function init()
    {
        if ($this->initialized) {
            throw new PrestaShopException("Error handler already initialized");
        }

        @ini_set('display_errors', 'off');
        @error_reporting(E_ALL | E_STRICT);

        // if we can't turn off display errors, we have to prevent default error handler instead
        $this->preventDefaultErrorHandler = @ini_get('display_errors') !== 'off';

        // Set uncaught exception handler
        set_exception_handler([$this, 'uncaughtExceptionHandler']);

        // Set error handler
        set_error_handler([$this, 'errorHandler']);



        $this->initialized = true;
    }

    /**
     * @return array of collected error messages
     */
    public function getErrorMessages($includeSuppressed = false)
    {
        if ($this->errorMessages) {
            return $includeSuppressed ? $this->errorMessages : array_filter($this->errorMessages, function ($error) {
                return !$error['suppressed'];
            });
        }
        return [];
    }

    /**
     * Uncaught exception handler - any uncaught exception will be processed by this method
     *
     * @since   1.0.9
     * @version 1.0.9 Initial version
     * @param Exception $e uncaught exception
     */
    public function uncaughtExceptionHandler(Throwable $e)
    {
        $exception = new PrestaShopException($e->getMessage(), $e->getCode(), null, $e->getTrace(), $e->getFile(), $e->getLine());
        $exception->displayMessage();
    }


    /**
     * Error handler. It only records any error, warning or notice to $errors array
     * and yields to default handler
     *
     * @param int $errno level of the error raised
     * @param string $errstr error message
     * @param string $errfile filename that the error was raised in
     * @param int $errline line number the error was raised at
     *
     * @return bool
     *
     * @since   1.0.9
     * @version 1.0.9 Initial version
     */
    public function errorHandler($errno, $errstr, $errfile, $errline)
    {
        // detect whether this message was
        $suppressed = error_reporting() === 0;
        $error = [
            'errno' => $errno,
            'errstr' => $errstr,
            'errfile' => $errfile,
            'errline' => $errline,
            'suppressed' => $suppressed,
            'type' => static::getErrorType($errno),
            'level' => static::getLogLevel($errno)
        ];
        $this->errorMessages[] = $error;
        if (! $suppressed) {
            $this->logMessage($error);
        }

        return $suppressed || $this->preventDefaultErrorHandler;
    }

    /**
     * Sets external logger. If $replay parameter is true, then any already collected error messages will be
     * emitted
     *
     * @param $logger
     * @param bool $replay
     */
    public function setLogger(LoggerInterface $logger, $replay=false)
    {
        $this->logger = $logger;
        if ($replay) {
            forEach($this->getErrorMessages(false) as $errorMessage) {
                $this->logMessage($errorMessage);
            }
        }
    }

    /**
     * Forward error message to psr compliant logger
     *
     * @param $msg
     */
    protected function logMessage($msg)
    {
        if (! $this->logger) {
            return;
        }

        $file = static::normalizeFileName($msg['errfile']);
        $message = $msg['type'] . ': ' . $msg['errstr'] . ' in ' . $file . ' at line ' . $msg['errline'];
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
     * Returns file name relative to thirtybees root directory
     *
     * @param $file
     * @return string file
     */
    private static function normalizeFileName($file)
    {
        return ltrim(str_replace([_PS_ROOT_DIR_, '\\'], ['', '/'], $file), '/');
    }


    /**
     * Returns error type for given error level
     *
     * @param int $errno level of the error raised
     *
     * @return string error type
     *
     * @since   1.0.9
     * @version 1.0.9 Initial version
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
     * Returns error PSR log level for given error level
     *
     * @param int $errno level of the error raised
     *
     * @return string error log level
     *
     * @since   1.0.9
     * @version 1.0.9 Initial version
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

}
