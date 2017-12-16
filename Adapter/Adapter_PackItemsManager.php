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
 * Class Adapter_PackItemsManager
 *
 * @since 1.0.0
 */
// @codingStandardsIgnoreStart
class Adapter_PackItemsManager
{
    // @codingStandardsIgnoreEnd

    /**
     * Get the Products contained in the given Pack.
     *
     * @param Product  $product
     * @param bool|int $idLang
     *
     * @return array The products contained in this Pack, with special dynamic attributes [pack_quantity, id_pack_product_attribute]
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws Adapter_Exception
     */
    public function getPackItems($product, $idLang = false)
    {
        if (!static::isPack($product)) {
            return [];
        }

        if ($idLang === false) {
            $configuration = Adapter_ServiceLocator::get('Core_Business_ConfigurationInterface');
            $idLang = (int) $configuration->get('PS_LANG_DEFAULT');
        }

        return Pack::getItems($product->id, $idLang);
    }

    /**
     * Get all Packs that contains the given item in the corresponding declination.
     *
     * @param Product  $item
     * @param int      $itemAttributeId
     * @param int|bool $idLang
     *
     * @return array The packs that contains the given item, with special dynamic attribute [pack_item_quantity]
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws Adapter_Exception
     */
    public function getPacksContainingItem($item, $itemAttributeId, $idLang = false)
    {
        if ($idLang === false) {
            $configuration = Adapter_ServiceLocator::get('Core_Business_ConfigurationInterface');
            $idLang = (int) $configuration->get('PS_LANG_DEFAULT');
        }

        return Pack::getPacksContainingItem($item->id, $itemAttributeId, $idLang);
    }

    /**
     * Is this product a pack?
     *
     * @param Product $product
     *
     * @return boolean
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function isPack($product)
    {
        return Pack::isPack($product->id);
    }

    /**
     * Is this product in a pack?
     *
     * If $idProductAttribute specified, then will restrict search on the given combination,
     * else this method will match a product if at least one of all its combination is in a pack.
     *
     * @param Product  $product
     * @param int|bool $idProductAttribute Optional: combination of the product
     *
     * @return boolean
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function isPacked($product, $idProductAttribute = false)
    {
        return Pack::isPacked($product->id, $idProductAttribute);
    }
}
