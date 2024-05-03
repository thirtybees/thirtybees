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
 * Class PrestaShopDatabaseExceptionCore
 */
class PrestaShopDatabaseExceptionCore extends PrestaShopException
{
    /**
     * @var string|null contains sql statement associated with error
     */
    private $sql;

    /**
     * PrestaShopDatabaseExceptionCore constructor.
     *
     * @param string $message
     * @param string|DbQuery|null $sql
     */
    public function __construct($message = '', $sql = null)
    {
        parent::__construct($message);

        if ($sql instanceof DbQuery) {
            $this->sql = $sql->buildSql();
        } else {
            $this->sql = $sql;
        }

        if ($this->trace) {
            // we want to report on different
            foreach ($this->trace as $row) {
                if (strpos($row['file'], 'classes/db/Db.php') === false) {
                    array_unshift($this->trace, [
                        'file' => $this->file,
                        'line' => $this->line,
                    ]);
                    $this->file = $row['file'];
                    $this->line = $row['line'];
                    return;
                }
            }
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->message;
    }

    /**
     * Display additional SQL section on error message page
     *
     * @return array describing sections
     */
    public function getExtraSections()
    {
        $sections = parent::getExtraSections();
        if ($this->sql) {
          $sections[] = [
              'label' => 'SQL',
              'content' => $this->sql,
          ];
        }
        return $sections;

    }
}
