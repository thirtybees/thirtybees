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
 * Class HelperShopCore
 *
 * @since 1.0.0
 */
class HelperShopCore extends Helper
{
    /**
     * Render shop list
     *
     * @return string
     *
     * @throws Exception
     * @throws PrestaShopException
     * @throws SmartyException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getRenderedShopList()
    {
        if (!Shop::isFeatureActive() || Shop::getTotalShops(false, null) < 2) {
            return '';
        }

        $shopContext = Shop::getContext();
        $context = Context::getContext();
        $tree = Shop::getTree();

        if ($shopContext == Shop::CONTEXT_ALL || ($context->controller->multishop_context_group == false && $shopContext == Shop::CONTEXT_GROUP)) {
            $currentShopValue = '';
            $currentShopName = Translate::getAdminTranslation('All shops');
        } elseif ($shopContext == Shop::CONTEXT_GROUP) {
            $currentShopValue = 'g-'.Shop::getContextShopGroupID();
            $currentShopName = sprintf(Translate::getAdminTranslation('%s group'), $tree[Shop::getContextShopGroupID()]['name']);
        } else {
            $currentShopValue = 's-'.Shop::getContextShopID();

            foreach ($tree as $groupId => $groupData) {
                foreach ($groupData['shops'] as $shopId => $shopData) {
                    if ($shopId == Shop::getContextShopID()) {
                        $currentShopName = $shopData['name'];
                        break;
                    }
                }
            }
        }

        $tpl = $this->createTemplate('helpers/shops_list/list.tpl');
        $tpl->assign(
            [
                'tree'                    => $tree,
                'current_shop_name'       => $currentShopName,
                'current_shop_value'      => $currentShopValue,
                'multishop_context'       => $context->controller->multishop_context,
                'multishop_context_group' => $context->controller->multishop_context_group,
                'is_shop_context'         => ($context->controller->multishop_context & Shop::CONTEXT_SHOP),
                'is_group_context'        => ($context->controller->multishop_context & Shop::CONTEXT_GROUP),
                'shop_context'            => $shopContext,
                'url'                     => $_SERVER['REQUEST_URI'].(($_SERVER['QUERY_STRING']) ? '&' : '?').'setShopContext=',
            ]
        );

        return $tpl->fetch();
    }
}
