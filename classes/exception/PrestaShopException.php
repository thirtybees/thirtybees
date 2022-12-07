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
 *  @author    thirty bees <contact@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017-2018 thirty bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class PrestaShopExceptionCore
 */
class PrestaShopExceptionCore extends Exception
{
    /**
     * @var array
     */
    protected $trace;

    /**
     * PrestaShopExceptionCore constructor.
     *
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     * @param array|null $customTrace
     * @param string|null $file
     * @param int|null $line
     */
    public function __construct($message = '', $code = 0, Throwable $previous = null, $customTrace = null, $file = null, $line = null)
    {
        parent::__construct($message, $code, $previous);

        if (!$customTrace) {
            $this->trace = $this->getTrace();
        } else {
            $this->trace = $customTrace;
        }

        if ($file) {
            $this->file = $file;
        }
        if ($line) {
            $this->line = $line;
        }
    }

    /**
     * This method acts like an error handler.
     * Exception is displayed to user using currently selected error page, and script execution will end
     *
     * @return void
     */
    public function displayMessage()
    {
        $errorHandler = Thirtybees\Core\DependencyInjection\ServiceLocator::getInstance()->getErrorHandler();
        $errorHandler->handleFatalError(Thirtybees\Core\Error\ErrorUtils::describeException($this));
        exit;
    }

    /**
     * This method can be overridden by subclasses to include additional sections into output
     *
     * See PrestaShopDatabaseException for example how to add new section displaying SQL query
     *
     * @return array
     */
    public function getExtraSections()
    {
        return [];
    }

    /**
     * @return array
     */
    public function getCustomTrace()
    {
        return $this->trace;
    }

}
