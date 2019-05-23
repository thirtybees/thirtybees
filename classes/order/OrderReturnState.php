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
 *
 * @since 1.0.0
 */
class OrderReturnStateCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    /** @var string Name */
    public $name;

    /** @var string Display state in the specified color */
    public $color;

    /** @var bool Active */
    public $active;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'     => 'order_return_state',
        'primary'   => 'id_order_return_state',
        'multilang' => true,
        'fields'    => [
            'color'   => ['type' => self::TYPE_STRING,                 'validate' => 'isColor'                                        ],
            'name'    => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 64],
            'active'  => ['type' => self::TYPE_BOOL,                   'validate' => 'isBool'],
        ],
    ];

    /**
     * @since 1.1.0
     */
    public function __construct($id = null, $idLang = null, $idShop = null)
    {
        static::installationCheck();

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
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getOrderReturnStates($idLang)
    {
        static::installationCheck();

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from('order_return_state', 'ors')
                ->where('ors.`active` = 1')
                ->leftJoin('order_return_state_lang', 'orsl', 'ors.`id_order_return_state` = orsl.`id_order_return_state` AND orsl.`id_lang` = '.(int) $idLang)
                ->orderBy('ors.`id_order_return_state` ASC')
        );
    }

    /**
     * Test whether the database is up to date and fix it if not.
     *
     * Starting with v1.1.0, thirty bees no longer equips the updater module
     * with database upgrade scripts, but equipped Core Updater with the
     * capability to read each class' table description and to update the
     * database accordingly.
     *
     * Retrocompatibility: as the above is just a plan and not yet true for
     * the time being, this was added as a kludge to bridge the time until it
     * actually gets true.
     *
     * @since 1.1.0
     */
    public static function installationCheck()
    {
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $result = $db->executeS(
            (new DbQuery())
                ->select('`active`')
                ->from(static::$definition['table'])
                ->limit(1)
        );

        if ( ! $result) {
            $db->execute('ALTER TABLE '
                ._DB_PREFIX_.static::$definition['table']
                .' ADD COLUMN `active` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1;'
            );
        }
    }
}
