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
 * Class OrderStateCore
 */
class OrderStateCore extends ObjectModel
{
    /** @var string|string[] Name */
    public $name;
    /** @var string|string[] Template name if there is any e-mail to send */
    public $template;
    /** @var bool Send an e-mail to customer ? */
    public $send_email;
    /** @var string $module_name */
    public $module_name;
    /** @var bool Allow customer to view and download invoice when order is at this state */
    public $invoice;
    /** @var string Display state in the specified color */
    public $color;
    /** @var bool $unremovable */
    public $unremovable;
    /** @var bool Log authorization */
    public $logable;
    /** @var bool Delivery */
    public $delivery;
    /** @var bool Hidden */
    public $hidden;
    /** @var bool Shipped */
    public $shipped;
    /** @var bool Paid */
    public $paid;
    /** @var bool Attach PDF Invoice */
    public $pdf_invoice;
    /** @var bool Attach PDF Delivery Slip */
    public $pdf_delivery;
    /** @var bool True if carrier has been deleted (staying in database as deleted) */
    public $deleted = 0;
    /** @var bool Active */
    public $active = 1;

    /**
     * @var array Object model definition
     */
    public static $definition = [
        'table'     => 'order_state',
        'primary'   => 'id_order_state',
        'multilang' => true,
        'fields'    => [
            'invoice'      => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0', 'dbNullable' => true],
            'send_email'   => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'],
            'module_name'  => ['type' => self::TYPE_STRING, 'validate' => 'isModuleName', 'size' => 64],
            'color'        => ['type' => self::TYPE_STRING, 'validate' => 'isColor', 'size' => 32],
            'unremovable'  => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbNullable' => false],
            'hidden'       => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'],
            'logable'      => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '0'],
            'delivery'     => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'],
            'shipped'      => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'],
            'paid'         => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'],
            'pdf_invoice'  => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'],
            'pdf_delivery' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'],
            'deleted'      => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'],
            'active'       => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '1'],

            /* Lang fields */
            'name'         => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 64],
            'template'     => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isTplName', 'size' => 64, 'dbNullable' => false],
        ],
        'keys' => [
            'order_state' => [
                'module_name' => ['type' => ObjectModel::KEY, 'columns' => ['module_name']],
            ],
        ],
    ];

    /**
     * @var array Webservice parameters
     */
    protected $webserviceParameters = [
        'fields' => [
            'unremovable' => [],
            'delivery'    => [],
            'hidden'      => [],
        ],
    ];

    const FLAG_NO_HIDDEN    = 1;  /* 00001 */
    const FLAG_LOGABLE        = 2;  /* 00010 */
    const FLAG_DELIVERY        = 4;  /* 00100 */
    const FLAG_SHIPPED        = 8;  /* 01000 */
    const FLAG_PAID        = 16; /* 10000 */

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
    public static function getOrderStates($idLang)
    {
        $cacheId = 'OrderState::getOrderStates_'.(int) $idLang;
        if (!Cache::isStored($cacheId)) {
            $result = Db::readOnly()->getArray(
                (new DbQuery())
                    ->select('*')
                    ->from('order_state', 'os')
                    ->leftJoin('order_state_lang', 'osl', 'os.`id_order_state` = osl.`id_order_state`')
                    ->where('osl.`id_lang` = '.(int) $idLang)
                    ->where('`deleted` = 0')
                    ->where('`active` = 1')
                    ->orderBy('`name` ASC')
            );
            Cache::store($cacheId, $result);

            return $result;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * Check if we can make a invoice when order is in this state
     *
     * @param int $idOrderState State ID
     *
     * @return bool availability
     *
     * @throws PrestaShopException
     */
    public static function invoiceAvailable($idOrderState)
    {
        $result = false;
        if (Configuration::get('PS_INVOICE')) {
            $result = Db::readOnly()->getValue(
                (new DbQuery())
                    ->select('`invoice`')
                    ->from('order_state')
                    ->where('`id_order_state` = '.(int) $idOrderState)
            );
        }

        return (bool) $result;
    }

    /**
     * @return bool
     */
    public function isRemovable()
    {
        return !($this->unremovable);
    }
}
