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
 * Class FileLoggerCore
 *
 * @since 1.0.0
 */
class FileLoggerCore extends AbstractLogger
{
    protected $filename = '';

    /**
     * Check if the specified filename is writable and set the filename
     *
     * @param string $filename
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
    */
    public function setFilename($filename)
    {
        if (is_writable(dirname($filename))) {
            $this->filename = $filename;
        } else {
            $this->filename = '';
        }
    }

    /**
     * Log the message
     *
     * @return string
     *
     * @since    1.0.0
     * @version  1.0.0 Initial version
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Write the message in the log file
     *
     * @param string $message
     * @param int    $level
     *
     * @return bool True on success, false on failure.
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function logMessage($message, $level)
    {
        if (!is_string($message)) {
            $message = print_r($message, true);
        }

        $formattedMessage = '*'.$this->level_value[$level].'* '."\t".date('Y/m/d - H:i:s').': '.$message."\r\n";

        $result = false;
        $path = $this->getFilename();
        if ($path) {
            $result = (bool) file_put_contents($path, $formattedMessage, FILE_APPEND);
        }

        return $result;
    }
}
