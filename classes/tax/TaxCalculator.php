<?php
/**
 * 2007-2016 PrestaShop
 *
 * Thirty Bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017 Thirty Bees
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
 * @author    Thirty Bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017 Thirty Bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class TaxCalculatorCore
 *
 * @since 1.0.0
 */
class TaxCalculatorCore
{
    /**
     * COMBINE_METHOD sum taxes
     * eg: 100€ * (10% + 15%)
     */
    const COMBINE_METHOD = 1;

    /**
     * ONE_AFTER_ANOTHER_METHOD apply taxes one after another
     * eg: (100€ * 10%) * 15%
     */
    const ONE_AFTER_ANOTHER_METHOD = 2;

    // @codingStandardsIgnoreStart
    /**
     * @var array $taxes
     */
    public $taxes;

    /**
     * @var int $computation_method (COMBINE_METHOD | ONE_AFTER_ANOTHER_METHOD)
     */
    public $computation_method;
    // @codingStandardsIgnoreEnd

    /**
     * @param array $taxes
     * @param int   $computationMethod (COMBINE_METHOD | ONE_AFTER_ANOTHER_METHOD)
     *
     * @throws Exception
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function __construct(array $taxes = [], $computationMethod = self::COMBINE_METHOD)
    {
        // sanity check
        foreach ($taxes as $tax) {
            if (!($tax instanceof Tax)) {
                throw new Exception('Invalid Tax Object');
            }
        }

        $this->taxes = $taxes;
        $this->computation_method = (int) $computationMethod;
    }

    /**
     * Compute and add the taxes to the specified price
     *
     * @param float $priceTaxExcluded price tax excluded
     *
     * @return float price with taxes
     */
    public function addTaxes($priceTaxExcluded)
    {
        return $priceTaxExcluded * (1 + ($this->getTotalRate() / 100));
    }

    /**
     * Compute and remove the taxes to the specified price
     *
     * @param float $priceTaxIncluded price tax inclusive
     *
     * @return float price without taxes
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function removeTaxes($priceTaxIncluded)
    {
        return $priceTaxIncluded / (1 + $this->getTotalRate() / 100);
    }

    /**
     * @return float total taxes rate
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getTotalRate()
    {
        $taxes = 0;
        if ($this->computation_method == static::ONE_AFTER_ANOTHER_METHOD) {
            $taxes = 1;
            foreach ($this->taxes as $tax) {
                $taxes *= (1 + (abs($tax->rate) / 100));
            }

            $taxes = $taxes - 1;
            $taxes = $taxes * 100;
        } else {
            foreach ($this->taxes as $tax) {
                $taxes += abs($tax->rate);
            }
        }

        return (float) $taxes;
    }

    /**
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getTaxesName()
    {
        $name = '';
        foreach ($this->taxes as $tax) {
            $name .= $tax->name[(int) Context::getContext()->language->id].' - ';
        }

        $name = rtrim($name, ' - ');

        return $name;
    }

    /**
     * Return the tax amount associated to each taxes of the TaxCalculator
     *
     * @param float $priceTaxExcluded
     *
     * @return array $taxes_amount
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getTaxesAmount($priceTaxExcluded)
    {
        $taxesAmounts = [];

        foreach ($this->taxes as $tax) {
            if ($this->computation_method == static::ONE_AFTER_ANOTHER_METHOD) {
                $taxesAmounts[$tax->id] = $priceTaxExcluded * (abs($tax->rate) / 100);
                $priceTaxExcluded = $priceTaxExcluded + $taxesAmounts[$tax->id];
            } else {
                $taxesAmounts[$tax->id] = ($priceTaxExcluded * (abs($tax->rate) / 100));
            }
        }

        return $taxesAmounts;
    }

    /**
     * Return the total taxes amount
     *
     * @param float $priceTaxExcluded
     *
     * @return float $amount
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getTaxesTotalAmount($priceTaxExcluded)
    {
        $amount = 0;

        $taxes = $this->getTaxesAmount($priceTaxExcluded);
        foreach ($taxes as $tax) {
            $amount += $tax;
        }

        return $amount;
    }
}
