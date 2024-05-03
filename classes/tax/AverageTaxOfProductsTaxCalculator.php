<?php
/**
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2024 thirty bees
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
 * @copyright 2017-2024 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class AverageTaxOfProductsTaxCalculator
 */
class AverageTaxOfProductsTaxCalculatorCore
{
    /** @var int $id_order */
    protected $id_order;
    /** @var Core_Business_ConfigurationInterface $configuration */
    protected $configuration;
    /** @var Core_Foundation_Database_DatabaseInterface $db */
    protected $db;
    /** @var string $computation_method */
    public $computation_method = 'average_tax_of_products';

    /**
     * AverageTaxOfProductsTaxCalculator constructor.
     *
     * @param Core_Foundation_Database_DatabaseInterface $db Making sure we stay connected to the same db instance
     * @param Core_Business_ConfigurationInterface $configuration
     */
    public function __construct(Core_Foundation_Database_DatabaseInterface $db, Core_Business_ConfigurationInterface $configuration)
    {
        $this->db = $db;
        $this->configuration = $configuration;
    }

    /**
     * @param int $idOrder
     *
     * @return static
     */
    public function setIdOrder($idOrder)
    {
        $this->id_order = $idOrder;

        return $this;
    }

    /**
     * @param float $priceBeforeTax
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getTaxesAmount($priceBeforeTax)
    {
        $taxBreakdown = [];
        $totalBase = 0;
        foreach ($this->getProductTaxes() as $row) {
            $taxId = (int)$row['id_tax'];
            $rate = (float)$row['rate'];
            $base = (float)$row['total_price_tax_excl'];
            $taxBreakdown[$taxId] = [
                'rate' => $rate,
                'base' => $base
            ];
            $totalBase += $base;
        }

        $amounts = [];
        foreach ($taxBreakdown as $taxId => $taxInfo) {
            $value = $priceBeforeTax * ($taxInfo['base'] / $totalBase) * $taxInfo['rate'] / 100.0;
            $amounts[$taxId] = Tools::ps_round($value, _TB_PRICE_DATABASE_PRECISION_);
        }
        return $amounts;
    }

    /**
     * @return array|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function getProductTaxes()
    {
        return $this->db->select(
            (new DbQuery())
                ->select('t.`id_tax`, t.rate, SUM(od.total_price_tax_excl) as total_price_tax_excl')
                ->from('orders', 'o')
                ->innerJoin('order_detail', 'od', 'od.`id_order` = o.`id_order`')
                ->innerJoin('order_detail_tax', 'odt', 'odt.`id_order_detail` = od.`id_order_detail`')
                ->innerJoin('tax', 't', 't.`id_tax` = odt.`id_tax`')
                ->where('o.`id_order` = '.(int) $this->id_order)
                ->groupBy('t.id_tax, t.rate')
        );
    }
}
