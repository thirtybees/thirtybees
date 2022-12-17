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


use PrestaShopException;
use Tools;

/**
 * class CSVDataSource
 */
class CSVDataSourceCore implements DataSourceInterface
{
    /**
     * @var string
     */
    protected $filepath;

    /**
     * @var string
     */
    protected $separator;

    /**
     * @var resource
     */
    protected $handle;

    /**
     * @var bool
     */
    private $containsBom;

    /**
     * @var int
     */
    private $numberOfColumns = 0;

    /**
     * @var bool
     */
    private $convert = false;

    /**
     * Creates new CSV data source
     *
     * @param string $filepath
     * @param string $separator
     *
     * @throws PrestaShopException
     */
    public function __construct(string $filepath, string $separator)
    {
        $this->filepath = $filepath;
        $this->separator = $separator;

        if (is_file($filepath) && is_readable($filepath)) {
            if (!mb_check_encoding(file_get_contents($filepath), 'UTF-8')) {
                $this->convert = true;
            }
            $this->handle = fopen($filepath, 'r');
        }

        if (! $this->handle) {
            throw new PrestaShopException(sprintf(Tools::displayError('Cannot read CSV file "%s"'), $this->filepath));
        }

        // detect if file contains BOM header or not
        $this->containsBom = fread($this->handle, 3) == "\xEF\xBB\xBF";
        if (! $this->containsBom) {
            $this->rewind();
        }
        $line = $this->getRow();
        if ($line) {
            $this->numberOfColumns = count($line);
        }
        $this->rewind();
    }


    /**
     * Rewinds file handle to the beginning
     *
     * @return bool
     */
    public function rewind()
    {
        $result = rewind($this->handle);
        if ($result) {
            if ($this->containsBom) {
                fread($this->handle, 3);
            }
        }
        return $result;
    }

    /**
     * Returns current row
     *
     * @return array|false
     */
    public function getRow()
    {
        $row = fgetcsv($this->handle, 0, $this->separator);
        if ($row && $this->convert) {
            $row = array_map([static::class, 'convertString'], $row);
        }
        return $row;
    }

    /**
     * Closes CSV file
     *
     * @return bool
     */
    public function close()
    {
        if ($this->handle) {
            return fclose($this->handle);
        }
        return true;
    }

    /**
     * Returns information about number of columns in the dataset
     *
     * @return int
     */
    public function getNumberOfColumns()
    {
        return $this->numberOfColumns;
    }

    /**
     * Returns information about number of rows in the dataset
     *
     * @return int
     */
    public function getNumberOfRows()
    {
        $this->rewind();
        $cnt = 0;
        while ($this->getRow()) {
            $cnt++;
        }
        $this->rewind();
        return $cnt;
    }

    /**
     * @param string|null $string
     *
     * @return string
     */
    protected static function convertString($string)
    {
        if (! is_string($string)) {
            return '';
        }
        return mb_convert_encoding((string)$string, 'UTF-8', mb_list_encodings());
    }

}
