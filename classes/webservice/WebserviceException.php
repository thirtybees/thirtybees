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
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2018 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class WebserviceExceptionCore
 */
class WebserviceExceptionCore extends Exception
{

    /**
     * @var int
     */
    protected $status;

    /**
     * @var string
     */
    protected $wrong_value;

    /**
     * @var array
     */
    protected $available_values;

    /**
     * @var int
     */
    protected $type;

    const SIMPLE = 0;
    const DID_YOU_MEAN = 1;

    /**
     * WebserviceExceptionCore constructor.
     *
     * @param string $message
     * @param int|int[] $code
     */
    public function __construct($message, $code)
    {
        $exceptionCode = $code;
        if (is_array($code)) {
            $exceptionCode = $code[0];
            $this->setStatus($code[1]);
        }
        parent::__construct($message, $exceptionCode);
        $this->type = static::SIMPLE;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     *
     * @return static
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @param int $status
     *
     * @return static
     */
    public function setStatus($status)
    {
        if (Validate::isInt($status)) {
            $this->status = $status;
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getWrongValue()
    {
        return $this->wrong_value;
    }

    /**
     * @param string $wrongValue
     * @param array $availableValues
     *
     * @return static
     */
    public function setDidYouMean($wrongValue, $availableValues)
    {
        $this->type = static::DID_YOU_MEAN;
        $this->wrong_value = $wrongValue;
        $this->available_values = $availableValues;

        return $this;
    }

    /**
     * @return array
     */
    public function getAvailableValues()
    {
        return $this->available_values;
    }
}
