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
 * Class TaxManagerFactoryCore
 */
class TaxManagerFactoryCore
{
    /**
     * @var TaxManagerInterface[]
     */
    protected static $cache_tax_manager;

    /**
     * Returns a tax manager able to handle this address
     *
     * @param Address $address
     * @param int $taxRuleGroupId TaxRulesGroup id
     *
     * @return TaxManagerInterface
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getManager(Address $address, $taxRuleGroupId)
    {
        $cacheId = static::getCacheKey($address).'-'.$taxRuleGroupId;
        if (!isset(static::$cache_tax_manager[$cacheId])) {
            $taxManager = static::execHookTaxManagerFactory($address, $taxRuleGroupId);
            if ($taxManager) {
                static::$cache_tax_manager[$cacheId] = $taxManager;
            } else {
                static::$cache_tax_manager[$cacheId] = new TaxRulesTaxManager($address, $taxRuleGroupId);
            }
        }

        return static::$cache_tax_manager[$cacheId];
    }

    /**
     * Check for a tax manager able to handle this type of address in the module list
     *
     * @param Address $address
     * @param int $type TaxRulesGroup id
     *
     * @return TaxManagerInterface|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function execHookTaxManagerFactory(Address $address, $type)
    {
        $hookName = 'taxManager';
        $modules = Hook::getModulesFromHook($hookName);

        foreach ($modules as $module) {
            $moduleId = (int)$module['id_module'];
            /** @var TaxManagerInterface|false|null $taxManager */
            $taxManager = Hook::getResponse($hookName, $moduleId, [
                'address' => $address,
                'params' => $type,
            ]);
            if ($taxManager instanceof TaxManagerInterface) {
                return $taxManager;
            }
        }

        return false;
    }

    /**
     * Create a unique identifier for the address
     *
     * @param Address $address
     * @return string
     */
    protected static function getCacheKey(Address $address)
    {
        return $address->id_country.'-'
            .(int) $address->id_state.'-'
            .$address->postcode.'-'
            .$address->vat_number.'-'
            .$address->dni;
    }
}
