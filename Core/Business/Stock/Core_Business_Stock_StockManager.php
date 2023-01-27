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
 */
class Core_Business_Stock_StockManager
{

    /**
     * This will update a Pack quantity and will decrease the quantity of containing Products if needed.
     *
     * @param Product $product A product pack object to update its quantity
     * @param StockAvailable $stockAvailable the stock of the product to fix with correct quantity
     * @param int $deltaQuantity The movement of the stock (negative for a decrease)
     * @param int|null $idShop Opional shop ID
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function updatePackQuantity($product, $stockAvailable, $deltaQuantity, $idShop = null)
    {
        $deltaQuantity = (int)$deltaQuantity;
        if ($deltaQuantity !== 0) {

            // update pack items quantities, if necessary
            if ($product->pack_dynamic || $product->shouldAdjustPackItemsQuantities()) {
                /** @var Adapter_PackItemsManager $packItemsManager */
                $packItemsManager = Adapter_ServiceLocator::get('Adapter_PackItemsManager');
                $productsPack = $packItemsManager->getPackItems($product);
                /** @var Adapter_StockManager $stockManager */
                $stockManager = Adapter_ServiceLocator::get('Adapter_StockManager');
                foreach ($productsPack as $productPack) {
                    /** @var StockAvailable $productStockAvailable */
                    $productStockAvailable = $stockManager->getStockAvailableByProduct($productPack, $productPack->id_pack_product_attribute, $idShop);
                    $productStockAvailable->quantity = $productStockAvailable->quantity + ($deltaQuantity * $productPack->pack_quantity);
                    $productStockAvailable->update();
                }
            }

            // update pack quantity
            if ($product->pack_dynamic) {
                StockAvailable::synchronizeDynamicPack($product->id);
            } else {
                if ($product->shouldAdjustPackQuantity()) {
                    $stockAvailable->quantity = $stockAvailable->quantity + $deltaQuantity;
                    $stockAvailable->update();
                }
            }
        }
    }

    /**
     * Will update Product available stock int he given declinaison. If product is a Pack, could decrease the sub products.
     * If Product is contained in a Pack, Pack could be decreased or not (only if sub product stocks become not sufficient).
     *
     * @param Product $product The product to update its stockAvailable
     * @param integer $idProductAttribute The declinaison to update (null if not)
     * @param integer $deltaQuantity The quantity change (positive or negative)
     * @param int|null $idShop Optional
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function updateQuantity($product, $idProductAttribute, $deltaQuantity, $idShop = null)
    {
        $deltaQuantity = (int)$deltaQuantity;
        if ($deltaQuantity !== 0) {
            /** @var Adapter_StockManager $stockManager */
            $stockManager = Adapter_ServiceLocator::get('Adapter_StockManager');
            /** @var StockAvailable $stockAvailable */
            $stockAvailable = $stockManager->getStockAvailableByProduct($product, $idProductAttribute, $idShop);

            if (Validate::isLoadedObject($stockAvailable)) {
                /** @var Adapter_PackItemsManager $packItemsManager */
                $packItemsManager = Adapter_ServiceLocator::get('Adapter_PackItemsManager');


                // Update quantity of the pack products
                if ($packItemsManager->isPack($product)) {
                    // The product is a pack
                    $this->updatePackQuantity($product, $stockAvailable, $deltaQuantity, $idShop);
                } else {
                    // The product is not a pack
                    $stockAvailable->quantity = $stockAvailable->quantity + $deltaQuantity;
                    $stockAvailable->update();

                    // adjust packs this item might be in
                    $packs = $packItemsManager->getPacksContainingItem($product, $idProductAttribute);
                    $dynamicPacks = [];
                    foreach ($packs as $pack) {
                        if ($pack->pack_dynamic) {
                            // dynamic pack, synchronize
                            $dynamicPacks[] = $pack->id;
                        } else {
                            if ($pack->getPackStockType() === Pack::STOCK_TYPE_DECREMENT_PACK_AND_PRODUCTS) {
                                // pack with 'Decrement both' settings, adjust quantity only when item quantity decreased
                                if ($deltaQuantity < 0) {
                                    $quantityByPack = $pack->pack_item_quantity;
                                    $maxPackQuantity = max(0, floor($stockAvailable->quantity / $quantityByPack));

                                    $stockAvailablePack = $stockManager->getStockAvailableByProduct($pack, null, $idShop);
                                    if ($stockAvailablePack->quantity > $maxPackQuantity) {
                                        $stockAvailablePack->quantity = $maxPackQuantity;
                                        $stockAvailablePack->update();
                                    }
                                }
                            }
                        }
                    }

                    if ($dynamicPacks) {
                        StockAvailable::synchronizeDynamicPacks($dynamicPacks);
                    }
                }
            }
        }
    }
}
