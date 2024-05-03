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

abstract class ObjectModel extends ObjectModelCore
{
    /**
     * @var array[]
     */
    public static $debug_list = [];

    /**
     * Builds the object
     *
     * @param int|null $id If specified, loads and existing object from DB (optional).
     * @param int|null $idLang Required if object is multilingual (optional).
     * @param int|null $idShop ID shop for objects with multishop tables.
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function __construct($id = null, $idLang = null, $idShop = null)
    {
        parent::__construct($id, $idLang, $idShop);

        $classname = get_class($this);
        if (!isset(static::$debug_list[$classname])) {
            static::$debug_list[$classname] = [];
        }

        $classList = [
            'ObjectModel',
            'ObjectModelCore',
            $classname,
            $classname.'Core'
        ];
        $backtrace = debug_backtrace();
        if ($backtrace) {
            foreach ($backtrace as $trace_id => $row) {
                if (!isset($row['class']) || !in_array($row['class'], $classList)) {
                    break;
                }
            }
            if (isset($trace_id)) {
                $trace_id--;

                static::$debug_list[$classname][] = [
                    'file' => @$backtrace[$trace_id]['file'],
                    'line' => @$backtrace[$trace_id]['line'],
                ];
            }
        }
    }
}
