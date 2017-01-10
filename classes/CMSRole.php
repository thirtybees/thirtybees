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
 *  @author    Thirty Bees <contact@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017 Thirty Bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

class CMSRoleCore extends ObjectModel
{
    /** @var string name */
    public $name;
    /** @var integer id_cms */
    public $id_cms;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'cms_role',
        'primary' => 'id_cms_role',
        'fields' => [
            'name'        =>    ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 50],
            'id_cms'    =>    ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
        ],
    ];

    public static function getRepositoryClassName()
    {
        return 'Core_Business_CMS_CMSRoleRepository';
    }
}
