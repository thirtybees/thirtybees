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

namespace PrestaShop\PrestaShop\Tests\Helper;

define('_RESSOURCE_MODULE_DIR_', realpath(dirname(__FILE__).'/../resources/module/'));

/**
 * Class Module
 *
 * @package PrestaShop\PrestaShop\Tests\Helper
 */
class Module
{
    /**
     * Copy the directory in resources which get the name $module_dir_name in the module directory
     *
     * @var string module_dir_name take the directory name of a module contain in /home/prestashop/tests/resources/module
     */
    public static function addModule($moduleDirName)
    {
        if (is_dir(_RESSOURCE_MODULE_DIR_.'/'.$moduleDirName)) {
            File::recurseCopy(_RESSOURCE_MODULE_DIR_.'/'.$moduleDirName, _PS_MODULE_DIR_.'/'.$moduleDirName);

            return true;
        }

        return false;
    }

    /**
     * Delete the directory in /home/prestashop/module which get the name $module_dir_name
     *
     * @var string module_dir_name take the directory name of a module contain in /home/prestashop/module
     */
    public static function removeModule($moduleDirName)
    {
        if (is_dir(_PS_MODULE_DIR_.$moduleDirName)) {
            File::recurseDelete(_PS_MODULE_DIR_.$moduleDirName);

            return true;
        }

        return false;
    }
}
