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

/** @noinspection PhpUnhandledExceptionInspection */

if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', getcwd());
}
include(_PS_ADMIN_DIR_.'/../config/config.inc.php');

$employee = Context::getContext()->employee;
if (!$employee->isLoggedBack()) {
    Tools::redirectAdmin(Context::getContext()->link->getAdminLink('AdminLogin'));
}

if (!$employee->hasAccess(AdminBackupController::class, Profile::PERMISSION_VIEW)) {
    throw new PrestaShopException(Tools::displayError('You do not have permission to view this.'));
}

$backupdir = realpath(PrestaShopBackup::getBackupPath());

if ($backupdir === false) {
    throw new PrestaShopException(Tools::displayError('There is no "/backup" directory.'));
}

if (!$backupfile = Tools::getValue('filename')) {
    throw new PrestaShopException(Tools::displayError('No file has been specified.'));
}

// Check the realpath so we can validate the backup file is under the backup directory
$backupfile = realpath($backupdir.DIRECTORY_SEPARATOR.$backupfile);

if ($backupfile === false or strncmp($backupdir, $backupfile, strlen($backupdir)) != 0) {
    throw new PrestaShopException('The backup file does not exist.');
}

if (substr($backupfile, -4) == '.bz2') {
    $contentType = 'application/x-bzip2';
} elseif (substr($backupfile, -3) == '.gz') {
    $contentType = 'application/x-gzip';
} else {
    $contentType = 'text/x-sql';
}
$fp = @fopen($backupfile, 'r');

if ($fp === false) {
    throw new PrestaShopException(Tools::displayError('Unable to open backup file(s).').' "'.addslashes($backupfile).'"');
}

// Add the correct headers, this forces the file is saved
header('Content-Type: '.$contentType);
header('Content-Disposition: attachment; filename="'.Tools::getValue('filename'). '"');

if (ob_get_level() && ob_get_length() > 0) {
    ob_clean();
}
$ret = @fpassthru($fp);

fclose($fp);

if ($ret === false) {
    throw new PrestaShopException(Tools::displayError('Unable to display backup file(s).').' "'.addslashes($backupfile).'"');
}
