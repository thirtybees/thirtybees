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
 * Class OrderReturnStateCore
 */
class OrderReturnStateCore extends ObjectModel
{
    /** @var string Name */
    public $name;

    /** @var string Display state in the specified color */
    public $color;

    /** @var bool Active */
    public $active = 1;

    /**
     * @var array Object model definition
     */
    public static $definition = [
        'table'     => 'order_return_state',
        'primary'   => 'id_order_return_state',
        'multilang' => true,
        'fields'    => [
            'color'   => ['type' => self::TYPE_STRING, 'validate' => 'isColor', 'size' => 32],
            'name'    => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 64],
            'active'  => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '1'],
        ],
    ];

    /**
     * @param int $id
     * @param int $idLang
     * @param int $idShop
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function __construct($id = null, $idLang = null, $idShop = null)
    {
        parent::__construct($id, $idLang, $idShop);
    }

    /**
     * Get all available order statuses
     *
     * @param int $idLang Language id for status name
     *
     * @return array Order statuses
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getOrderReturnStates($idLang)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from('order_return_state', 'ors')
                ->where('ors.`active` = 1')
                ->leftJoin('order_return_state_lang', 'orsl', 'ors.`id_order_return_state` = orsl.`id_order_return_state` AND orsl.`id_lang` = '.(int) $idLang)
                ->orderBy('ors.`id_order_return_state` ASC')
        );
    }

}
