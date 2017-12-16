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
 * Class SupplyOrderStateCore
 *
 * @since 1.0.0
 */
class SupplyOrderStateCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    /**
     * @var string Name of the state
     */
    public $name;

    /**
     * @var bool Tells if a delivery note can be issued (i.e. the order has been validated)
     */
    public $delivery_note;

    /**
     * @var bool Tells if the order is still editable by an employee (i.e. you can add products)
     */
    public $editable;

    /**
     * @var bool Tells if the the order has been delivered
     */
    public $receipt_state;

    /**
     * @var bool Tells if the the order is in a state corresponding to a product pending receipt
     */
    public $pending_receipt;

    /**
     * @var bool Tells if the the order is in an enclosed state (i.e. terminated, canceled)
     */
    public $enclosed;

    /**
     * @var string Color used to display the state in the specified color (Ex. #FFFF00)
     */
    public $color;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'     => 'supply_order_state',
        'primary'   => 'id_supply_order_state',
        'multilang' => true,
        'fields'    => [
            'delivery_note'   => ['type' => self::TYPE_BOOL,                   'validate' => 'isBool'                                          ],
            'editable'        => ['type' => self::TYPE_BOOL,                   'validate' => 'isBool'                                          ],
            'receipt_state'   => ['type' => self::TYPE_BOOL,                   'validate' => 'isBool'                                          ],
            'pending_receipt' => ['type' => self::TYPE_BOOL,                   'validate' => 'isBool'                                          ],
            'enclosed'        => ['type' => self::TYPE_BOOL,                   'validate' => 'isBool'                                          ],
            'color'           => ['type' => self::TYPE_STRING,                 'validate' => 'isColor'                                         ],
            'name'            => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 128],
        ],
    ];

    /**
     * @see ObjectModel::$webserviceParameters
     */
    protected $webserviceParameters = [
        'objectsNodeName' => 'supply_order_states',
        'objectNodeName' => 'supply_order_state',
        'fields' => [],
    ];

    /**
     * Gets the list of supply order statuses
     *
     * @param int $idStateReferrer Optional, used to know what state is available after this one
     * @param int $idLang          Optional Id Language
     *
     * @return array States
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getSupplyOrderStates($idStateReferrer = null, $idLang = null)
    {
        if ($idLang == null) {
            $idLang = Context::getContext()->language->id;
        }

        $query = new DbQuery();
        $query->select('sl.name, s.id_supply_order_state');
        $query->from('supply_order_state', 's');
        $query->leftjoin('supply_order_state_lang', 'sl', 's.id_supply_order_state = sl.id_supply_order_state AND sl.id_lang='.(int) $idLang);

        if (!is_null($idStateReferrer)) {
            $isReceiptState = false;
            $isEditable = false;
            $isDeliveryNote = false;
            $isPendingReceipt = false;

            //check current state to see what state is available
            $state = new SupplyOrderState((int) $idStateReferrer);
            if (Validate::isLoadedObject($state)) {
                $isReceiptState = $state->receipt_state;
                $isEditable = $state->editable;
                $isDeliveryNote = $state->delivery_note;
                $isPendingReceipt = $state->pending_receipt;
            }

            $query->where('s.id_supply_order_state <> '.(int) $idStateReferrer);

            //check first if the order is editable
            if ($isEditable) {
                $query->where('s.editable = 1 OR s.delivery_note = 1 OR s.enclosed = 1');
            }
            //check if the delivery note is available or if the state correspond to a pending receipt state
            elseif ($isDeliveryNote || $isPendingReceipt) {
                $query->where('(s.delivery_note = 0 AND s.editable = 0) OR s.enclosed = 1');
            }
            //check if the state correspond to a receipt state
            elseif ($isReceiptState) {
                $query->where('s.receipt_state = 1 AND s.id_supply_order_state > '.(int) $idStateReferrer);
            }
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
    }

    /**
     * Gets the list of supply order statuses
     *
     * @param array $ids    Optional Do not include these ids in the result
     * @param int   $idLang Optional
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getStates($ids = null, $idLang = null)
    {
        if ($idLang == null) {
            $idLang = Context::getContext()->language->id;
        }

        if ($ids && !is_array($ids)) {
            $ids = [];
        }

        $query = new DbQuery();
        $query->select('sl.name, s.id_supply_order_state');
        $query->from('supply_order_state', 's');
        $query->leftjoin('supply_order_state_lang', 'sl', 's.id_supply_order_state = sl.id_supply_order_state AND sl.id_lang='.(int) $idLang);
        if ($ids) {
            $query->where('s.id_supply_order_state NOT IN('.implode(',', array_map('intval', $ids)).')');
        }

        $query->orderBy('sl.name ASC');

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
    }
}
