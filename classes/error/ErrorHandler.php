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

namespace Thirtybees\Core\Error;

use FileLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use SmartyCustom;
use Thirtybees\Core\Error\Response\ErrorResponseInterface;
use Throwable;

/**
 * Class ErrorHandlerCore
 */
class ErrorHandlerCore
{
    /**
     * @var ErrorResponseInterface
     */
    protected $errorResponse;

    /**
     * @var array list of errors, warnings and notices encountered during request processing
     */
    protected $errorMessages = [];

    /**
     * @var LoggerInterface[] psr compliant logger
     */
    protected $loggers = [];

    /**
     * @var callable custom handler of fatal error
     */
    protected $fatalErrorHandler;

    /**
     * Error handler constructor
     *
     * Creates and initialize error handling logic
     */
    public function __construct(ErrorResponseInterface $errorResponse)
    {
        $this->errorResponse = $errorResponse;

        @ini_set('display_errors', 'off');
        @error_reporting(E_ALL | E_STRICT);

        // Set uncaught exception handler
        set_exception_handler([$this, 'uncaughtExceptionHandler']);

        // Set error handler
        set_error_handler([$this, 'errorHandler']);

        // register shutdown handler to catch fatal errors
        register_shutdown_function([$this, 'shutdown']);
    }

    /**
     * @param ErrorResponseInterface $errorResponse
     */
    public function setErrorResponseHandler(ErrorResponseInterface $errorResponse)
    {
        $this->errorResponse = $errorResponse;
    }

    /**
     * Returns list of collected php error messags
     *
     * @param bool $includeSuppressed if true, result will include even
     *             messages that were suppressed using @ operator
     * @param int $mask message types to return, defaults to E_ALL.
     *
     * @return array of collected error messages
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
     * @param Throwable $e uncaught exception
     */
    public function uncaughtExceptionHandler(Throwable $e)
    {
        static::handleFatalError(ErrorUtils::describeException($e));
    }

    /**
     * @param ErrorDescription $errorDescription
     * @return void
     */
    public function handleFatalError(ErrorDescription $errorDescription)
    {
        $this->logFatalError($errorDescription);
        $this->errorResponse->sendResponse($errorDescription);
        exit;
    }

    /**
     * @param ErrorDescription $errorDescription
     * @return void
     */
    public function logFatalError(ErrorDescription $errorDescription)
    {
        // log all exceptions to file
        $logger = new FileLogger();
        $logger->setFilename(_PS_ROOT_DIR_.'/log/'.date('Ymd').'_exception.log');
        $logger->logError($errorDescription->getExtendedMessage());

        // log exception through custom logger, if set
        if ($this->loggers) {
            $extra = $errorDescription->getExtraSections();
            $stacktrace = $errorDescription->getTraceAsString();
            $previous = $errorDescription->getCause();
            while ($previous) {
                $stacktrace .= "\nCaused by: ";
                $stacktrace .= $previous->getErrorName() . ': ' . $previous->getMessage();
                $stacktrace .= ' at line ' . $previous->getSourceLine();
                $stacktrace .= ' in file ' . ErrorUtils::getRelativeFile($previous->getSourceFile());
                $previous = $previous->getCause();
            }
            $extra[] = [
                'label' => 'Stacktrace',
                'content' => $stacktrace
            ];

            $error = [
                'errno' => 0,
                'errstr' => $errorDescription->getErrorName() . ': ' . $errorDescription->getMessage(),
                'errfile' => ErrorUtils::getRelativeFile($errorDescription->getSourceFile()),
                'errline' => $errorDescription->getSourceLine(),
                'suppressed' => false,
                'type' => 'Exception',
                'level' => static::getLogLevel(E_ERROR),
                'extra' => $extra,
            ];

            $this->logMessage($error);
        }
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
     */
    public function errorHandler($errno, $errstr, $errfile, $errline)
    {
        $suppressed = error_reporting() === 0;
        $file = $errfile;
        $line = $errline;
        $realFile = null;
        $realLine = 0;

        if (SmartyCustom::isCompiledTemplate($file)) {
            $realFile = ErrorUtils::getRelativeFile($errfile);
            $realLine = $errline;
            $file = SmartyCustom::getCurrentTemplate();
            $line = 0;
        }

        $file = ErrorUtils::getRelativeFile($file);

        $error = [
            'errno'       => $errno,
            'errstr'      => $errstr,
            'errfile'     => $file,
            'errline'     => $line,
            'suppressed'  => $suppressed,
            'type'        => static::getErrorType($errno),
            'level'       => static::getLogLevel($errno),
        ];
        if ($realFile) {
            $error['realFile'] = $realFile;
            $error['realLine'] = $realLine;
        }

        $this->errorMessages[] = $error;
        if (! $suppressed) {
            $this->logMessage($error);
        }

        return $suppressed || static::displayErrorEnabled();
    }

    /**
     * Shutdown handler let us detect and react to fatal errors.
     *
     * @return void
     */
    public function shutdown()
    {
        $error = error_get_last();

        if (is_array($error) && static::isFatalError($error['type'])) {
            $errorDescription = ErrorUtils::describeError($error);
            if ($this->fatalErrorHandler && is_callable($this->fatalErrorHandler)) {
                $this->logFatalError($errorDescription);
                call_user_func($this->fatalErrorHandler, $error);
            } else {
                $this->handleFatalError($errorDescription);
            }
        }
    }

    /**
     * Adds external logger. If $replay parameter is true, then any already
     * collected error messages will be emitted.
     *
     * @param LoggerInterface $logger
     * @param bool $replay
     */
    public function addLogger(LoggerInterface $logger, $replay=false)
    {
        $this->loggers[] = $logger;
        if ($replay) {
            foreach($this->getErrorMessages(false) as $errorMessage) {
                $this->sendMessageToLogger($logger, $errorMessage);
            }
        }
    }

    /**
     * Allows set custom handler for fatal errors. Returns previous handler, if exists
     *
     * @param callable $callable
     * @return callable | null
     */
    public function setFatalErrorHandler($callable)
    {
        $ret = $this->fatalErrorHandler;
        $this->fatalErrorHandler = $callable;
        return $ret;
    }

    /**
     * Forward error message to psr compliant logger.
     *
     * @param array $msg
     */
    protected function logMessage($msg)
    {
        if (! $this->loggers) {
            return;
        }
        foreach ($this->loggers as $logger) {
            $this->sendMessageToLogger($logger, $msg);
        }
    }

    /**
     * Converts $msg to string representation
     *
     * @param array $msg error message
     *
     * @return string
     */
    public static function formatErrorMessage($msg)
    {
        $file = ErrorUtils::getRelativeFile($msg['errfile']);

        return $msg['type'].': '
            .$msg['errstr'].' in '.$file.' at line '.$msg['errline'];
    }

    /**
     * Returns error type for given error level.
     *
     * @param int $errno level of the error raised
     *
     * @return string error type
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
     * @param int $errno
     * @return boolean
     */
    public static function isFatalError($errno)
    {
        return (
            $errno === E_USER_ERROR ||
            $errno === E_ERROR ||
            $errno === E_CORE_ERROR ||
            $errno === E_COMPILE_ERROR ||
            $errno === E_RECOVERABLE_ERROR
        );
    }

    /**
     * Returns error PSR log level for given error level.
     *
     * @param int $errno level of the error raised
     *
     * @return string error log level
     */
    public static function getLogLevel($errno)
    {
        switch ($errno) {
            case E_PARSE:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
                return LogLevel::CRITICAL;
            case E_USER_ERROR:
            case E_RECOVERABLE_ERROR:
            case E_ERROR:
                return LogLevel::ERROR;
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
            case E_USER_WARNING:
            case E_WARNING:
            case E_USER_DEPRECATED:
            case E_DEPRECATED:
                return LogLevel::WARNING;
            case E_USER_NOTICE:
            case E_NOTICE:
                return LogLevel::NOTICE;
            default:
                return LogLevel::DEBUG;
        }
    }

    /**
     * Returns true, if display_errors settings is turned on.
     *
     * @return boolean
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

    /**
     * @param LoggerInterface $logger
     * @param array $msg
     * @return void
     */
    protected function sendMessageToLogger(LoggerInterface $logger, $msg)
    {
        $message = static::formatErrorMessage($msg);

        switch ($msg['level']) {
            case LogLevel::EMERGENCY:
                $logger->emergency($message, $msg);
                break;
            case LogLevel::ALERT:
                $logger->alert($message, $msg);
                break;
            case LogLevel::CRITICAL:
                $logger->critical($message, $msg);
                break;
            case LogLevel::ERROR:
                $logger->error($message, $msg);
                break;
            case LogLevel::WARNING:
                $logger->warning($message, $msg);
                break;
            case LogLevel::NOTICE:
                $logger->notice($message, $msg);
                break;
            case LogLevel::INFO:
                $logger->info($message, $msg);
                break;
            case LogLevel::DEBUG:
                $logger->debug($message, $msg);
                break;
        }
    }
}
