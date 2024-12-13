<?php

namespace Thirtybees\Core\Error;

/**
 * This error handler is instantiated at application bootstrap, before autoload classes are loaded
 * Its purpose is to collect all errors and warnings before full-fledged error handler can be used
 *
 * Important note: No dependency on other classes can be used here. Keep is as simple as possible
 */
class BootstrapErrorHandler
{
    /**
     * @var array
     */
    private array $errors;

    private bool $collect;

    /**
     * @return BootstrapErrorHandler|null
     */
    public static function getInstance()
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new static();
        }
        return $instance;
    }

    /**
     *  private constructor
     */
    private function __construct()
    {
        $this->collect = true;
        $this->errors = [];
    }

    /**
     * @return void
     */
    public function installErrorHandler()
    {
        @ini_set('display_errors', 'off');
        @error_reporting(E_ALL);
        set_error_handler([$this, 'errorHandler']);
    }

    /**
     * Error handler function
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
        if ($this->collect) {
            $this->errors[] = [
                'errno' => $errno,
                'errstr' => $errstr,
                'errfile' => $errfile,
                'errline' => $errline
            ];
        }
        return false;
    }

    /**
     * @return array
     */
    public function getCollectedErrors(): array
    {
        $this->collect = false;
        return $this->errors;
    }

}