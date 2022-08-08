<?php
/**
 * Copyright (C) 2022 thirty bees
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
 * @copyright 2019 thirty bees
 * @license   Open Software License (OSL 3.0)
 */

namespace Thirtybees\Core\Error\Response;

use Thirtybees\Core\Error\ErrorDescription;

/**
 * Class DebugErrorPageCore
 *
 * @since 1.4.0
 */
class DebugErrorPageCore extends AbstractErrorPage
{
    /**
     * Return content type
     * @return string
     */
    protected function getContentType()
    {
        return 'text/html';
    }

    /**
     * @param ErrorDescription $errorDescription
     * @return string
     */
    protected function renderError(ErrorDescription $errorDescription)
    {
        return static::displayErrorTemplate(
            _PS_ROOT_DIR_.'/error500_debug.phtml',
            [
                'errorDescription' => $errorDescription,
                'helper' => $this,
            ]
        );
    }

    /**
     * Helper function to render file lines
     *
     * @param $lines array of file lines
     * @return string output
     */
    public function displayLines($lines)
    {
        $ret = '';
        if ($lines) {
            $ret = '<pre>';
            foreach ($lines as $currentLine) {
                if ($currentLine['highlighted']) {
                    $ret .= "<span class='selected'>";
                }
                $ret .= "<span class='line'>" . $currentLine['number'] . ":</span>" . htmlentities($currentLine['line']);
                if ($currentLine['highlighted']) {
                    $ret .= "</span>";
                }
            }
            $ret .= '</pre>';
        }
        return $ret;
    }

    /**
     * Helper function to escape input
     *
     * @param $input
     * @return string
     */
    public function displayString($input)
    {
        if (is_null($input)) {
            return 'NULL';
        }
        if (is_string($input) && $input) {
            $value = html_entity_decode($input);
            return htmlentities($value);
        }
        return (string)$input;
    }
}
