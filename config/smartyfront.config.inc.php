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

global $smarty;
$smarty->setTemplateDir(_PS_THEME_DIR_.'tpl');

if (Configuration::get('PS_JS_HTML_THEME_COMPRESSION')) {
    $smarty->registerFilter('output', 'smartyPackJSinHTML');
}

function smartyTranslate($params, $smarty)
{
    global $_LANG;

    if (!isset($params['js'])) {
        $params['js'] = false;
    }
    if (!isset($params['pdf'])) {
        $params['pdf'] = false;
    }
    if (!isset($params['mod'])) {
        $params['mod'] = false;
    }
    if (!isset($params['sprintf'])) {
        $params['sprintf'] = null;
    }

    $string = str_replace('\'', '\\\'', $params['s']);
    $filename = ((!isset($smarty->compiler_object) || !is_object($smarty->compiler_object->template)) ? $smarty->template_resource : $smarty->compiler_object->template->getTemplateFilepath());

    $basename = basename($filename, '.tpl');
    $key = $basename.'_'.md5($string);

    if (isset($smarty->source) && (strpos($smarty->source->filepath, DIRECTORY_SEPARATOR.'override'.DIRECTORY_SEPARATOR) !== false)) {
        $key = 'override_'.$key;
    }

    if ($params['mod']) {
        return Translate::postProcessTranslation(Translate::getModuleTranslation($params['mod'], $params['s'], $basename, $params['sprintf'], $params['js']), $params);
    } elseif ($params['pdf']) {
        return Translate::postProcessTranslation(Translate::getPdfTranslation($params['s'], $params['sprintf']), $params);
    }

    if ($_LANG != null && isset($_LANG[$key]) && $_LANG[$key] !== '') {
        $msg = $_LANG[$key];
    } elseif ($_LANG != null && isset($_LANG[mb_strtolower($key)]) && $_LANG[mb_strtolower($key)] !== '') {
        $msg = $_LANG[mb_strtolower($key)];
    } else {
        $msg = $params['s'];
    }

    if ($msg !== $params['s'] && !$params['js']) {
        $msg = stripslashes($msg);
    } elseif ($params['js']) {
        $msg = addslashes($msg);
    }

    if ($params['sprintf'] !== null) {
        $msg = Translate::checkAndReplaceArgs($msg, $params['sprintf']);
    }

    return Translate::smartyPostProcessTranslation($params['js'] ? $msg : Tools::safeOutput($msg), $params);
}
