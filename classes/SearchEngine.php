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
 * Class SearchEngineCore
 *
 * @since 1.0.0
 */
class SearchEngineCore extends ObjectModel
{
    public $server;
    public $getvar;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'search_engine',
        'primary' => 'id_search_engine',
        'fields'  => [
            'server' => ['type' => self::TYPE_STRING, 'validate' => 'isUrl', 'required' => true],
            'getvar' => ['type' => self::TYPE_STRING, 'validate' => 'isModuleName', 'required' => true],
        ],
    ];

    /**
     * @param string $url
     *
     * @return bool|string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getKeywords($url)
    {
        $parsedUrl = @parse_url($url);
        if (!isset($parsedUrl['host']) || !isset($parsedUrl['query'])) {
            return false;
        }
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SELECT `server`, `getvar` FROM `'._DB_PREFIX_.'search_engine`');
        foreach ($result as $row) {
            $host = &$row['server'];
            $varname = &$row['getvar'];
            if (strstr($parsedUrl['host'], $host)) {
                $array = [];
                preg_match('/[^a-z]'.$varname.'=.+\&/U', $parsedUrl['query'], $array);
                if (empty($array[0])) {
                    preg_match('/[^a-z]'.$varname.'=.+$/', $parsedUrl['query'], $array);
                }
                if (empty($array[0])) {
                    return false;
                }
                $str = urldecode(str_replace('+', ' ', ltrim(substr(rtrim($array[0], '&'), strlen($varname) + 1), '=')));
                if (!Validate::isMessage($str)) {
                    return false;
                }

                return $str;
            }
        }
    }
}
