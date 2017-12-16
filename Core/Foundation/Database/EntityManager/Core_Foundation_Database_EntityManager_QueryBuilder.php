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

/**
 * Class Core_Foundation_Database_EntityManager_QueryBuilder
 *
 * @since 1.0.0
 */
// @codingStandardsIgnoreStart
class Core_Foundation_Database_EntityManager_QueryBuilder
{
    // @codingStandardsIgnoreEnd

    protected $db;

    /**
     * Core_Foundation_Database_EntityManager_QueryBuilder constructor.
     *
     * @param Core_Foundation_Database_DatabaseInterface $db
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function __construct(Core_Foundation_Database_DatabaseInterface $db)
    {
        $this->db = $db;
    }

    /**
     * @param string $value
     *
     * @return string
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function quote($value)
    {
        $escaped = $this->db->escape($value);

        if (is_string($value)) {
            return "'".$escaped."'";
        } else {
            return $escaped;
        }
    }

    /**
     * @param string $andOrOr
     * @param array  $conditions
     *
     * @return string
     * @throws Core_Foundation_Database_Exception
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function buildWhereConditions($andOrOr, array $conditions)
    {
        $operator = strtoupper($andOrOr);

        if ($operator !== 'AND' && $operator !== 'OR') {
            throw new Core_Foundation_Database_Exception(sprintf('Invalid operator %s - must be "and" or "or".', $andOrOr));
        }

        $parts = [];

        foreach ($conditions as $key => $value) {
            if (is_scalar($value)) {
                $parts[] = $key.' = '.$this->quote($value);
            } else {
                $list = [];
                foreach ($value as $item) {
                    $list[] = $this->quote($item);
                }
                $parts[] = $key.' IN ('.implode(', ', $list).')';
            }
        }

        return implode(" $operator ", $parts);
    }
}
