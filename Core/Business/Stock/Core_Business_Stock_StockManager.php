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
 * Class Core_Business_Stock_StockManager
 *
 * @since 1.0.0ce 1.0.0
 */
// @codingStandardsIgnoreStart
class Core_Business_Stock_StockManager
{
    // @codingStandardsIgnoreEnd

    /**
     * This will update a Pack quantity and will decrease the quantity of containing Products if needed.
     *
     * @param Product        $product        A product pack object to update its quantity
     * @param StockAvailable $stockAvailable the stock of the product to fix with correct quantity
     * @param int            $deltaQuantity  The movement of the stock (negative for a decrease)
     * @param int|null       $idShop         Opional shop ID
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function updatePackQuantity($product, $stockAvailable, $deltaQuantity, $idShop = null)
    {
        $configuration = Adapter_ServiceLocator::get('Core_Business_ConfigurationInterface');
        if ($product->pack_stock_type == 1 || $product->pack_stock_type == 2 || ($product->pack_stock_type == 3 && $configuration->get('PS_PACK_STOCK_TYPE') > 0)) {
            $packItemsManager = Adapter_ServiceLocator::get('Adapter_PackItemsManager');
            $productsPack = $packItemsManager->getPackItems($product);
            /** @var Adapter_StockManager $stockManager */
            $stockManager = Adapter_ServiceLocator::get('Adapter_StockManager');
            /** @var Adapter_CacheManager $cacheManager */
            $cacheManager = Adapter_ServiceLocator::get('Adapter_CacheManager');
            foreach ($productsPack as $productPack) {
                /** @var StockAvailable $productStockAvailable */
                $productStockAvailable = $stockManager->getStockAvailableByProduct($productPack, $productPack->id_pack_product_attribute, $idShop);
                $productStockAvailable->quantity = $productStockAvailable->quantity + ($deltaQuantity * $productPack->pack_quantity);
                $productStockAvailable->update();

                $cacheManager->clean('StockAvailable::getQuantityAvailableByProduct_'.(int) $productPack->id.'*');
            }
        }

        $stockAvailable->quantity = $stockAvailable->quantity + $deltaQuantity;

        if ($product->pack_stock_type == 0 || $product->pack_stock_type == 2 ||
            ($product->pack_stock_type == 3 && ($configuration->get('PS_PACK_STOCK_TYPE') == 0 || $configuration->get('PS_PACK_STOCK_TYPE') == 2))) {
            $stockAvailable->update();
        }
    }

    /**
     * This will decrease (if needed) Packs containing this product
     * (with the right declinaison) if there is not enough product in stocks.
     *
     * @param Product        $product            A product object to update its quantity
     * @param integer        $idProductAttribute The product attribute to update
     * @param StockAvailable $stockAvailable     the stock of the product to fix with correct quantity
     * @param int|null       $idShop             Opional shop ID
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function updatePacksQuantityContainingProduct($product, $idProductAttribute, $stockAvailable, $idShop = null)
    {
        /** @var Core_Business_ConfigurationInterface $configuration */
        $configuration = Adapter_ServiceLocator::get('Core_Business_ConfigurationInterface');
        /** @var Adapter_PackItemsManager $packItemsManager */
        $packItemsManager = Adapter_ServiceLocator::get('Adapter_PackItemsManager');
        /** @var Adapter_StockManager $stockManager */
        $stockManager = Adapter_ServiceLocator::get('Adapter_StockManager');
        /** @var Adapter_CacheManager $cacheManager */
        $cacheManager = Adapter_ServiceLocator::get('Adapter_CacheManager');
        $packs = $packItemsManager->getPacksContainingItem($product, $idProductAttribute);
        foreach ($packs as $pack) {
            // Decrease stocks of the pack only if pack is in linked stock mode (option called 'Decrement both')
            if (!((int) $pack->pack_stock_type == 2) &&
                !((int) $pack->pack_stock_type == 3 && $configuration->get('PS_PACK_STOCK_TYPE') == 2)
                ) {
                continue;
            }

            // Decrease stocks of the pack only if there is not enough items to constituate the actual pack stocks.

            // How many packs can be constituated with the remaining product stocks
            $quantityByPack = $pack->pack_item_quantity;
            $maxPackQuantity = max([0, floor($stockAvailable->quantity / $quantityByPack)]);

            $stockAvailablePack = $stockManager->getStockAvailableByProduct($pack, null, $idShop);
            if ($stockAvailablePack->quantity > $maxPackQuantity) {
                $stockAvailablePack->quantity = $maxPackQuantity;
                $stockAvailablePack->update();

                $cacheManager->clean('StockAvailable::getQuantityAvailableByProduct_'.(int) $pack->id.'*');
            }
        }
    }

    /**
     * Will update Product available stock int he given declinaison. If product is a Pack, could decrease the sub products.
     * If Product is contained in a Pack, Pack could be decreased or not (only if sub product stocks become not sufficient).
     *
     * @param Product  $product            The product to update its stockAvailable
     * @param integer  $idProductAttribute The declinaison to update (null if not)
     * @param integer  $deltaQuantity      The quantity change (positive or negative)
     * @param int|null $idShop             Optional
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function updateQuantity($product, $idProductAttribute, $deltaQuantity, $idShop = null)
    {
        /** @var Adapter_StockManager $stockManager */
        $stockManager = Adapter_ServiceLocator::get('Adapter_StockManager');
        /** @var StockAvailable $stockAvailable */
        $stockAvailable = $stockManager->getStockAvailableByProduct($product, $idProductAttribute, $idShop);
        /** @var Adapter_PackItemsManager $packItemsManager */
        $packItemsManager = Adapter_ServiceLocator::get('Adapter_PackItemsManager');
        /** @var Adapter_CacheManager $cacheManager */
        $cacheManager = Adapter_ServiceLocator::get('Adapter_CacheManager');
        /** @var Adapter_HookManager $hookManager */
        $hookManager = Adapter_ServiceLocator::get('Adapter_HookManager');

        // Update quantity of the pack products
        if ($packItemsManager->isPack($product)) {
            // The product is a pack
            $this->updatePackQuantity($product, $stockAvailable, $deltaQuantity, $idShop);
        } else {
            // The product is not a pack
            $stockAvailable->quantity = $stockAvailable->quantity + $deltaQuantity;
            $stockAvailable->id_product = (int) $product->id;
            $stockAvailable->id_product_attribute = (int) $idProductAttribute;
            $stockAvailable->update();

            // Decrease case only: the stock of linked packs should be decreased too.
            if ($deltaQuantity < 0) {
                // The product is not a pack, but the product combination is part of a pack (use of isPacked, not isPack)
                if ($packItemsManager->isPacked($product, $idProductAttribute)) {
                    $this->updatePacksQuantityContainingProduct($product, $idProductAttribute, $stockAvailable, $idShop);
                }
            }
        }

        $cacheManager->clean('StockAvailable::getQuantityAvailableByProduct_'.(int) $product->id.'*');

        $hookManager->exec(
            'actionUpdateQuantity',
            [
                'id_product' => $product->id,
                'id_product_attribute' => $idProductAttribute,
                'quantity' => $stockAvailable->quantity,
            ]
        );
    }
}
