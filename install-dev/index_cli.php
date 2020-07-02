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

// Checks
// Check compatibility
$errors = array();
if (version_compare(PHP_VERSION, '5.6.0', '<')) {
    $errors[] = 'Make sure your PHP version is at least 5.6.';
}

// Check if composer packages are available
if (!file_exists(dirname(__FILE__).'/../vendor/autoload.php')) {
    $errors[] = 'The composer packages are not available. Make sure you have copied the <code>vendor</code> folder or have run the <code>composer install --no-dev</code> command.';
}

if (!empty($errors)) {
    foreach ($errors as $error) {
        $error = strip_tags($error);
        echo "$error\n";
    }
    die();
}

/* Redefine REQUEST_URI */
$_SERVER['REQUEST_URI'] = '/install/index_cli.php';
require_once dirname(__FILE__).'/init.php';
require_once _TB_INSTALL_PATH_.'classes/datas.php';

try {
    require_once _TB_INSTALL_PATH_.'classes/controllerConsole.php';

    $tests = ConfigurationTest::check(ConfigurationTest::getDefaultTests());
    foreach ($tests as $test => $result) {
        if ($result !== 'ok') {
            die("Installation failed: test `$test` failed");
        }
    }

    InstallControllerConsole::execute($argc, $argv);
    echo '-- Installation successful! --'."\n";
} catch (PrestashopException $e) {
    die($e);
}
