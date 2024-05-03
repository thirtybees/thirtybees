<?php
/**
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2024 thirty bees
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
 *  @copyright 2017-2024 thirty bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * @param mixed $var
 * @return void
 */
function fd($var)
{
    Tools::fd($var);
}

/**
 * @param mixed $var
 * @return mixed|void
 */
function p($var)
{
    return (Tools::p($var));
}

/**
 * @param mixed $var
 * @return void
 */
function d($var)
{
    Tools::d($var);
}

/**
 * @param mixed $var
 * @return mixed|void
 */
function ppp($var)
{
    return (Tools::p($var));
}

/**
 * @param mixed $var
 * @return void
 */
function ddd($var)
{
    Tools::d($var);
}

/**
 * @param mixed $var
 * @param string|null $message_type
 * @param string|null $destination
 * @param string|null $extra_headers
 * @return bool
 */
function epr($var, $message_type = null, $destination = null, $extra_headers = null)
{
    return Tools::error_log($var, $message_type, $destination, $extra_headers);
}

/**
 * Sanitize data which will be injected into SQL query
 *
 * @param string $string SQL data which will be injected into SQL query
 * @param bool $htmlOK Does data contain HTML code ? (optional)
 *
 * @return string Sanitized data
 *
 * @throws PrestaShopDatabaseException
 * @throws PrestaShopException
 */
function pSQL($string, $htmlOK = false)
{
    return Db::getInstance()->escape($string, $htmlOK);
}

/**
 * @param string $string
 * @return string
 * @throws PrestaShopDatabaseException
 * @throws PrestaShopException
 */
function bqSQL($string)
{
    return str_replace('`', '\`', pSQL($string));
}

/**
 * @return void
 */
function displayFatalError()
{
    $error = null;
    if (function_exists('error_get_last')) {
        $error = error_get_last();
    }
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_COMPILE_ERROR])) {
        echo '[PrestaShop] Fatal error in module file :'.$error['file'].':<br />'.$error['message'];
    }
}

/**
 * @deprecated
 */
function nl2br2($string)
{
    Tools::displayAsDeprecated();
    return Tools::nl2br($string);
}
