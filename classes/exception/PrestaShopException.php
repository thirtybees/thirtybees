<?php
/**
 * 2007-2016 PrestaShop
 *
 * Thirty Bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017 Thirty Bees
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
 *  @author    Thirty Bees <contact@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017 Thirty Bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class PrestaShopExceptionCore
 *
 * @since 1.0.0
 */
class PrestaShopExceptionCore extends Exception
{
    /**
     * This method acts like an error handler, if dev mode is on, display the error else use a better silent way
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function displayMessage()
    {
        header('HTTP/1.1 500 Internal Server Error');
        if (_PS_MODE_DEV_ || defined('_PS_ADMIN_DIR_') || getenv('CI')) {
            // Display error message
            echo '<style>
				#psException{font-family: Verdana; font-size: 14px}
				#psException h2{color: #F20000}
				#psException p{padding-left: 20px}
				#psException ul li{margin-bottom: 10px}
				#psException a{font-size: 12px; color: #000000}
				#psException .psTrace, #psException .psArgs{display: none}
				#psException pre{border: 1px solid #236B04; background-color: #EAFEE1; padding: 5px; font-family: Courier; width: 99%; overflow-x: auto; margin-bottom: 30px;}
				#psException .psArgs pre{background-color: #F1FDFE;}
				#psException pre .selected{color: #F20000; font-weight: bold;}
			</style>';
            echo '<div id="psException">';
            echo '<h2>['.get_class($this).']</h2>';
            echo $this->getExtendedMessage();

            $this->displayFileDebug($this->getFile(), $this->getLine());

            // Display debug backtrace
            echo '<ul>';
            foreach ($this->getTrace() as $id => $trace) {
                $relativeFile = (isset($trace['file'])) ? ltrim(str_replace([_PS_ROOT_DIR_, '\\'], ['', '/'], $trace['file']), '/') : '';
                $currentLine = (isset($trace['line'])) ? $trace['line'] : '';
                if (defined('_PS_ADMIN_DIR_')) {
                    $relativeFile = str_replace(basename(_PS_ADMIN_DIR_).DIRECTORY_SEPARATOR, 'admin'.DIRECTORY_SEPARATOR, $relativeFile);
                }
                echo '<li>';
                echo '<b>'.((isset($trace['class'])) ? $trace['class'] : '').((isset($trace['type'])) ? $trace['type'] : '').$trace['function'].'</b>';
                echo ' - <a style="font-size: 12px; color: #000000; cursor:pointer; color: blue;" onclick="document.getElementById(\'psTrace_'.$id.'\').style.display = (document.getElementById(\'psTrace_'.$id.'\').style.display != \'block\') ? \'block\' : \'none\'; return false">[line '.$currentLine.' - '.$relativeFile.']</a>';

                if (isset($trace['args']) && count($trace['args'])) {
                    echo ' - <a style="font-size: 12px; color: #000000; cursor:pointer; color: blue;" onclick="document.getElementById(\'psArgs_'.$id.'\').style.display = (document.getElementById(\'psArgs_'.$id.'\').style.display != \'block\') ? \'block\' : \'none\'; return false">['.count($trace['args']).' Arguments]</a>';
                }

                if ($relativeFile) {
                    $this->displayFileDebug($trace['file'], $trace['line'], $id);
                }
                if (isset($trace['args']) && count($trace['args'])) {
                    $this->displayArgsDebug($trace['args'], $id);
                }
                echo '</li>';
            }
            echo '</ul>';
            echo '</div>';
        } else {
            // If not in mode dev, display an error page
            if (file_exists(_PS_ROOT_DIR_.'/error500.html')) {
                echo file_get_contents(_PS_ROOT_DIR_.'/error500.html');
            }
        }
        // Log the error in the disk
        $this->logError();
        exit;
    }

    /**
     * Display lines around current line
     *
     * @param string $file
     * @param int $line
     * @param string $id
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    protected function displayFileDebug($file, $line, $id = null)
    {
        $lines = file($file);
        $offset = $line - 6;
        $total = 11;
        if ($offset < 0) {
            $total += $offset;
            $offset = 0;
        }
        $lines = array_slice($lines, $offset, $total);
        ++$offset;

        echo '<div class="psTrace" id="psTrace_'.$id.'" '.((is_null($id) ? 'style="display: block"' : '')).'><pre>';
        foreach ($lines as $k => $l) {
            $string = ($offset + $k).'. '.htmlspecialchars($l);
            if ($offset + $k == $line) {
                echo '<span class="selected">'.$string.'</span>';
            } else {
                echo $string;
            }
        }
        echo '</pre></div>';
    }

    /**
     * Display arguments list of traced function
     *
     * @param array $args List of arguments
     * @param string $id ID of argument
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    protected function displayArgsDebug($args, $id)
    {
        echo '<div class="psArgs" id="psArgs_'.$id.'"><pre>';
        foreach ($args as $arg => $value) {
            echo '<b>Argument ['.Tools::safeOutput($arg)."]</b>\n";
            echo Tools::safeOutput(print_r($value, true));
            echo "\n";
        }
        echo '</pre>';
    }

    /**
     * Log the error on the disk
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    protected function logError()
    {
        $logger = new FileLogger();
        $logger->setFilename(_PS_ROOT_DIR_.'/log/'.date('Ymd').'_exception.log');
        $logger->logError($this->getExtendedMessage(false));
    }

    /**
     * @deprecated 2.0.0
     */
    protected function getExentedMessage($html = true)
    {
        Tools::displayAsDeprecated();

        return $this->getExtendedMessage($html);
    }

    /**
     * Return the content of the Exception
     * @return string content of the exception.
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    protected function getExtendedMessage($html = true)
    {
        $format = '<p><b>%s</b><br /><i>at line </i><b>%d</b><i> in file </i><b>%s</b></p>';
        if (!$html) {
            $format = strip_tags(str_replace('<br />', ' ', $format));
        }

        return sprintf(
            $format,
            $this->getMessage(),
            $this->getLine(),
            ltrim(str_replace([_PS_ROOT_DIR_, '\\'], ['', '/'], $this->getFile()), '/')
        );
    }
}
