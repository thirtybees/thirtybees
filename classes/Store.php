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
 * @author    thirty bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2018 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class StoreCore
 *
 * @since 1.0.0
 */
class StoreCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    /** @var int Country id */
    public $id_country;
    /** @var int State id */
    public $id_state;
    /** @var string Store name */
    public $name;
    /** @var string Address first line */
    public $address1;
    /** @var string Address second line (optional) */
    public $address2;
    /** @var string Postal code */
    public $postcode;
    /** @var string City */
    public $city;
    /** @var float Latitude */
    public $latitude;
    /** @var float Longitude */
    public $longitude;
    /** @var string Store hours (JSON encoded array) */
    public $hours;
    /** @var string Phone number */
    public $phone;
    /** @var string Fax number */
    public $fax;
    /** @var string Note */
    public $note;
    /** @var string e-mail */
    public $email;
    /** @var string Object creation date */
    public $date_add;
    /** @var string Object last modification date */
    public $date_upd;
    /** @var bool Store status */
    public $active = true;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'store',
        'primary' => 'id_store',
        'fields'  => [
            'id_country' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_state'   => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId'],
            'name'       => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 128],
            'address1'   => ['type' => self::TYPE_STRING, 'validate' => 'isAddress', 'required' => true, 'size' => 128],
            'address2'   => ['type' => self::TYPE_STRING, 'validate' => 'isAddress', 'size' => 128],
            'postcode'   => ['type' => self::TYPE_STRING, 'size' => 12],
            'city'       => ['type' => self::TYPE_STRING, 'validate' => 'isCityName', 'required' => true, 'size' => 64],
            'latitude'   => ['type' => self::TYPE_FLOAT, 'validate' => 'isCoordinate', 'size' => 13],
            'longitude'  => ['type' => self::TYPE_FLOAT, 'validate' => 'isCoordinate', 'size' => 13],
            'hours'      => ['type' => self::TYPE_STRING, 'validate' => 'isJSON', 'size' => 65000],
            'phone'      => ['type' => self::TYPE_STRING, 'validate' => 'isPhoneNumber', 'size' => 16],
            'fax'        => ['type' => self::TYPE_STRING, 'validate' => 'isPhoneNumber', 'size' => 16],
            'note'       => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 65000],
            'email'      => ['type' => self::TYPE_STRING, 'validate' => 'isEmail', 'size' => 128],
            'active'     => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true],
            'date_add'   => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd'   => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];

    protected $webserviceParameters = [
        'fields' => [
            'id_country' => ['xlink_resource' => 'countries'],
            'id_state'   => ['xlink_resource' => 'states'],
            'hours'      => ['getter' => 'getWsHours', 'setter' => 'setWsHours'],
        ],
    ];

    /**
     * StoreCore constructor.
     *
     * @param null $idStore
     * @param null $idLang
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function __construct($idStore = null, $idLang = null)
    {
        parent::__construct($idStore);
        $this->id_image = ($this->id && file_exists(_PS_STORE_IMG_DIR_.(int) $this->id.'.jpg')) ? (int) $this->id : false;
        $this->image_dir = _PS_STORE_IMG_DIR_;
    }

    /**
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getWsHours()
    {
        $hours = json_decode($this->hours, true);

        // Retrocompatibility for thirty bees <= 1.0.4.
        //
        // To get rid of this, introduce a data converter executed by the
        // upgrader over a couple of releases, making this obsolete.
        if (!$hours) {
            $hours = Tools::unSerialize($this->hours);
        }

        return implode(';', $hours);
    }

    /**
     * @param string $hours
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setWsHours($hours)
    {
        $this->hours = json_encode(explode(';', $hours));

        return true;
    }
}
