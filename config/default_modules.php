<?php
/**
 * Copyright (C) 2018 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2018 thirty bees
 * @license   Open Software License (OSL 3.0)
 */

/**
 * List of modules to install at installation time. Modules in this list, but
 * not present on disk, get ignored (see InstallModelInstall->installModules()).
 */
// For retrocompatibility with PHP 5.6 we cannot use
// define('_TB_DEFAULT_MODULES_', [ ...
$_TB_DEFAULT_MODULES_ = [
    'bankwire',
    'beesblog',
    'blockbanner',
    'blockbestsellers',
    'blockcart',
    'blocksocial',
    'blockcategories',
    'blockcurrencies',
    'blockfacebook',
    'blocklanguages',
    'blocklayered',
    'blockcms',
    'blockcmsinfo',
    'blockcontact',
    'blockcontactinfos',
    'blockmanufacturer',
    'blockmyaccount',
    'blockmyaccountfooter',
    'blocknewproducts',
    'blocknewsletter',
    'blockpaymentlogo',
    'blocksearch',
    'blockspecials',
    'blockstore',
    'blocksupplier',
    'blocktags',
    'blocktopmenu',
    'blockuserinfo',
    'blockviewed',
    'coreupdater',
    'ctconfiguration',
    'dashactivity',
    'dashtrends',
    'dashgoals',
    'dashproducts',
    'homeslider',
    'homefeatured',
    'productpaymentlogos',
    'socialsharing',
    'statsdata',
    'statsmodule',
    'tbhtmlblock',
    'tbupdater',
    'themeconfigurator',
    'thememanager',
];
