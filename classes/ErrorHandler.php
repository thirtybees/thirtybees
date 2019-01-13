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

        set_exception_handler([$this, 'uncaughtExceptionHandler']);

        $this->initialized = true;
    }

    /**
     * Uncaught exception handler - any uncaught exception will be processed by
     * this method
     *
     * @param Exception $e uncaught exception
     *
     * @since 1.0.9
     */
    public function uncaughtExceptionHandler(Exception $e)
    {
        $exception = new PrestaShopException($e->getMessage(), $e->getCode(),
                                             null, $e->getTrace(),
                                             $e->getFile(), $e->getLine());
        $exception->displayMessage();
    }
}
