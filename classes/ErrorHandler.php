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
        $this->initialized = true;
    }

}