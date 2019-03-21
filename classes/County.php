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
 * @deprecated 1.0.0
 */
class CountyCore extends ObjectModel
{
    const USE_BOTH_TAX = 0;
    const USE_COUNTY_TAX = 1;
    const USE_STATE_TAX = 2;

    // @codingStandardsIgnoreStart
    protected static $_cache_get_counties = [];
    protected static $_cache_county_zipcode = [];
    public $id;
    public $name;
    public $id_state;
    public $active;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [];

    protected $webserviceParameters = [
        'fields' => [
            'id_state' => ['xlink_resource' => 'states'],
        ],
    ];

    /**
     * @deprecated 1.0.0
     */
    public static function getCounties($id_state)
    {
        Tools::displayAsDeprecated();

        return false;
    }

    /**
     * @deprecated 1.0.0
     */
    public static function getIdCountyByZipCode($id_state, $zip_code)
    {
        Tools::displayAsDeprecated();

        return false;
    }

    /**
     * @deprecated 1.0.0
     */
    public static function deleteZipCodeByIdCounty($id_county)
    {
        Tools::displayAsDeprecated();

        return true;
    }

    /**
     * @deprecated 1.0.0
     */
    public static function getIdCountyByNameAndIdState($name, $idState)
    {
        Tools::displayAsDeprecated();

        return false;
    }

    /**
     * @deprecated 1.0.0
     */
    public function delete()
    {
        return true;
    }

    /**
     * @deprecated 1.0.0
     */
    public function getZipCodes()
    {
        Tools::displayAsDeprecated();

        return false;
    }

    /**
     * @deprecated 1.0.0
     */
    public function addZipCodes($zipCodes)
    {
        Tools::displayAsDeprecated();

        return true;
    }

    /**
     * @deprecated 1.0.0
     */
    public function removeZipCodes($zipCodes)
    {
        Tools::displayAsDeprecated();

        return true;
    }

    /**
     * @deprecated 1.0.0
     */
    public function breakDownZipCode($zipCodes)
    {
        Tools::displayAsDeprecated();

        return [0, 0];
    }

    /**
     * @deprecated 1.0.0
     */
    public function isZipCodeRangePresent($zipCodes)
    {
        Tools::displayAsDeprecated();

        return false;
    }

    /**
     * @deprecated since 1.0.0
     */
    public function isZipCodePresent($zipCode)
    {
        Tools::displayAsDeprecated();

        return false;
    }
}
