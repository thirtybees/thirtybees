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
     * Get instance of error handler
     *
     * @return ErrorHandlerCore
     *
     * @since 1.0.9
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
     * @since 1.0.9
     */
    public function init()
    {
        if ($this->initialized) {
            throw new PrestaShopException('Error handler already initialized');
        }

        @ini_set('display_errors', 'off');
        @error_reporting(E_ALL | E_STRICT);

        // If we can't turn off display errors, we have to prevent default
        // error handler instead
        $this->preventDefaultErrorHandler = @ini_get('display_errors') !== 'off';

        // Set uncaught exception handler
        set_exception_handler([$this, 'uncaughtExceptionHandler']);

        // Set error handler
        set_error_handler([$this, 'errorHandler']);

        $this->initialized = true;
    }

    /**
     * @return array of collected error messages
     *
     * @since 1.0.9
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
     * Uncaught exception handler - any uncaught exception will be processed by
     * this method
     *
     * @param Exception $e uncaught exception
     *
     * @since 1.0.9
     */
    public function uncaughtExceptionHandler(Throwable $e)
    {
        $exception = new PrestaShopException($e->getMessage(), $e->getCode(),
                                             null, $e->getTrace(),
                                             $e->getFile(), $e->getLine());
        $exception->displayMessage();
    }

    /**
     * Error handler. It only records any error, warning or notice to $errors
     * array and yields to default handler
     *
     * @param int $errno level of the error raised
     * @param string $errstr error message
     * @param string $errfile filename that the error was raised in
     * @param int $errline line number the error was raised at
     *
     * @return bool
     *
     * @since 1.0.9
     */
    public function errorHandler($errno, $errstr, $errfile, $errline)
    {
        $suppressed = error_reporting() === 0;
        $error = [
            'errno'       => $errno,
            'errstr'      => $errstr,
            'errfile'     => $errfile,
            'errline'     => $errline,
            'suppressed'  => $suppressed,
            'type'        => static::getErrorType($errno),
        ];
        $this->errorMessages[] = $error;

        return $suppressed || $this->preventDefaultErrorHandler;
    }

    /**
     * Returns error type for given error level
     *
     * @param int $errno level of the error raised
     *
     * @return string error type
     *
     * @since 1.0.9
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
}
