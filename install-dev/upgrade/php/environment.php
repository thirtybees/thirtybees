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
 * Set up a minimalistic environment to allow running core classes. For
 * inclusion by PHP upgrade scripts.
 *
 * @since 1.0.8
 */
require_once(_PS_CLASS_DIR_.'PrestaShopAutoload.php');
spl_autoload_register([PrestaShopAutoload::getInstance(), 'load']);

require_once(_PS_ROOT_DIR_.'/vendor/autoload.php');

require_once(_PS_CORE_DIR_.'/config/bootstrap.php');
