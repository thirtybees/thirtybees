<?php
/**
 * Copyright (C) 2022-2022 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2022-2022 thirty bees
 * @license   Open Software License (OSL 3.0)
 */

namespace Thirtybees\Core\Import;


/**
 * Interface DataSourceInterface
 *
 * @since 1.4.0
 */
interface DataSourceInterface
{

    /**
     * Returns current row
     *
     * @return array|false
     */
    public function getRow();

    /**
     * Returns information about number of columns in the dataset. Resets pointer in the data source
     *
     * @return int
     */
    public function getNumberOfColumns();

    /**
     * Returns information about number of rows in the dataset. Resets pointer in the data source
     *
     * @return int
     */
    public function getNumberOfRows();

    /**
     * Closes data source
     *
     * @return boolean
     */
    public function close();
}
