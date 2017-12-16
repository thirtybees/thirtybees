<?php
/**
 * Copyright (C) 2017-2018 thirty bees
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
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
 * Do not edit or add to this file if you wish to upgrade thirty bees to newer
 * versions in the future. If you want to customize thirty bees for your
 * needs please refer to https://www.thirtybees.com for more information.
 *
 *  @author    thirty bees <contact@thirtybees.com>
 *  @copyright 2017-2018 thirty bees
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

if (!defined('_PS_ADMIN_DIR_')) {
  // Find admin dir even on non-developer installations.
  $adminDir = null;
  $rootDir = dir(dirname(__FILE__).'/..');
  while (($entry = $rootDir->read())) {
    $found = strpos($entry, 'admin');
    if ($found !== false && $found === 0) {
      $adminDir = $rootDir->path.'/'.$entry;
      break;
    }
  }
  $rootDir->close();
  define('_PS_ADMIN_DIR_', $adminDir);
}

// These should work with or without installation.
require_once(dirname(__FILE__).'/../config/defines.inc.php');
require_once(_PS_ROOT_DIR_.'/config/autoload.php');

AdminInformationControllerCore::generateMd5List();
