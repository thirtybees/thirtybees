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

/**
* @deprecated since 1.5
*/
class CountyCore extends ObjectModel
{
    public $id;
    public $name;
    public $id_state;
    public $active;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'county',
        'primary' => 'id_county',
        'fields' => [
            'name' =>        ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 64],
            'id_state' =>    ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'active' =>    ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
        ],
    ];

    protected static $_cache_get_counties = [];
    protected static $_cache_county_zipcode = [];

    const USE_BOTH_TAX = 0;
    const USE_COUNTY_TAX = 1;
    const USE_STATE_TAX = 2;

    protected $webserviceParameters = [
        'fields' => [
            'id_state' => ['xlink_resource'=> 'states'],
        ],
    ];

    public function delete()
    {
        return true;
    }

    /**
    * @deprecated since 1.5
    */
    public static function getCounties($id_state)
    {
        Tools::displayAsDeprecated();
        return false;
    }

    /**
    * @deprecated since 1.5
    */
    public function getZipCodes()
    {
        Tools::displayAsDeprecated();
        return false;
    }

    /**
    * @deprecated since 1.5
    */
    public function addZipCodes($zip_codes)
    {
        Tools::displayAsDeprecated();
        return true;
    }

    /**
    * @deprecated since 1.5
    */
    public function removeZipCodes($zip_codes)
    {
        Tools::displayAsDeprecated();
        return true;
    }

    /**
    * @deprecated since 1.5
    */
    public function breakDownZipCode($zip_codes)
    {
        Tools::displayAsDeprecated();
        return [0,0];
    }

    /**
    * @deprecated since 1.5
    */
    public static function getIdCountyByZipCode($id_state, $zip_code)
    {
        Tools::displayAsDeprecated();
        return false;
    }

    /**
    * @deprecated since 1.5
    */
    public function isZipCodeRangePresent($zip_codes)
    {
        Tools::displayAsDeprecated();
        return false;
    }

    /**
    * @deprecated since 1.5
    */
    public function isZipCodePresent($zip_code)
    {
        Tools::displayAsDeprecated();
        return false;
    }

    /**
    * @deprecated since 1.5
    */
    public static function deleteZipCodeByIdCounty($id_county)
    {
        Tools::displayAsDeprecated();
        return true;
    }

    /**
    * @deprecated since 1.5
    */
    public static function getIdCountyByNameAndIdState($name, $id_state)
    {
        Tools::displayAsDeprecated();
        return false;
    }
}
