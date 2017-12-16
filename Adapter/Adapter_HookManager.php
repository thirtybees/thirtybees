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
 * Class Adapter_HookManager
 *
 * @since 1.0.0
 */
// @codingStandardsIgnoreStart
class Adapter_HookManager
{
    // @codingStandardsIgnoreEnd

    /**
     * Execute modules for specified hook
     *
     * @param string $hookName        Hook Name
     * @param array  $hookArgs        Parameters for the functions
     * @param int    $idModule        Execute hook for this module only
     * @param bool   $arrayReturn     If specified, module output will be set by name in an array
     * @param bool   $checkExceptions Check permission exceptions
     * @param bool   $usePush         Force change to be refreshed on Dashboard widgets
     * @param int    $idShop          If specified, hook will be execute the shop with this ID
     *
     * @throws PrestaShopException
     *
     * @return string/array modules output
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function exec($hookName, $hookArgs = [], $idModule = null, $arrayReturn = false, $checkExceptions = true, $usePush = false, $idShop = null)
    {
        return Hook::exec($hookName, $hookArgs, $idModule, $arrayReturn, $checkExceptions, $usePush, $idShop);
    }
}
