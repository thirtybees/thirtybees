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
 * Class TaxRulesTaxManagerCore
 *
 * @since 1.0.0
 */
class TaxRulesTaxManagerCore implements TaxManagerInterface
{
    // @codingStandardsIgnoreStart
    public $address;
    public $type;
    public $tax_calculator;
    // @codingStandardsIgnoreEnd

    /**
     * @var Core_Business_ConfigurationInterface
     */
    private $configurationManager;

    /**
     *
     * @param Address $address
     * @param mixed   $type An additional parameter for the tax manager (ex: tax rules id for TaxRuleTaxManager)
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws Adapter_Exception
     */
    public function __construct(Address $address, $type, Core_Business_ConfigurationInterface $configurationManager = null)
    {
        if ($configurationManager === null) {
            $this->configurationManager = Adapter_ServiceLocator::get('Core_Business_ConfigurationInterface');
        } else {
            $this->configurationManager = $configurationManager;
        }

        $this->address = $address;
        $this->type = $type;
    }

    /**
     * Returns true if this tax manager is available for this address
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isAvailableForThisAddress(Address $address)
    {
        return true; // default manager, available for all addresses
    }

    /**
     * Return the tax calculator associated to this address
     *
     * @return TaxCalculator
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getTaxCalculator()
    {
        static $taxEnabled = null;

        if (isset($this->tax_calculator)) {
            return $this->tax_calculator;
        }

        if ($taxEnabled === null) {
            $taxEnabled = $this->configurationManager->get('PS_TAX');
        }

        if (!$taxEnabled) {
            return new TaxCalculator([]);
        }

        $taxes = [];
        $postcode = 0;

        if (!empty($this->address->postcode)) {
            $postcode = $this->address->postcode;
        }

        $cacheId = (int) $this->address->id_country.'-'.(int) $this->address->id_state.'-'.$postcode.'-'.(int) $this->type;

        if (!Cache::isStored($cacheId)) {
            $rows = Db::getInstance()->executeS(
                '
				SELECT tr.*
				FROM `'._DB_PREFIX_.'tax_rule` tr
				JOIN `'._DB_PREFIX_.'tax_rules_group` trg ON (tr.`id_tax_rules_group` = trg.`id_tax_rules_group`)
				WHERE trg.`active` = 1
				AND tr.`id_country` = '.(int) $this->address->id_country.'
				AND tr.`id_tax_rules_group` = '.(int) $this->type.'
				AND tr.`id_state` IN (0, '.(int) $this->address->id_state.')
				AND (\''.pSQL($postcode).'\' BETWEEN tr.`zipcode_from` AND tr.`zipcode_to`
					OR (tr.`zipcode_to` = 0 AND tr.`zipcode_from` IN(0, \''.pSQL($postcode).'\')))
				ORDER BY tr.`zipcode_from` DESC, tr.`zipcode_to` DESC, tr.`id_state` DESC, tr.`id_country` DESC'
            );

            $behavior = 0;
            $firstRow = true;

            foreach ($rows as $row) {
                $tax = new Tax((int) $row['id_tax']);

                $taxes[] = $tax;

                // the applied behavior correspond to the most specific rules
                if ($firstRow) {
                    $behavior = $row['behavior'];
                    $firstRow = false;
                }

                if ($row['behavior'] == 0) {
                    break;
                }
            }
            $result = new TaxCalculator($taxes, $behavior);
            Cache::store($cacheId, $result);

            return $result;
        }

        return Cache::retrieve($cacheId);
    }
}
