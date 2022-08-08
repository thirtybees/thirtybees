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
 * Class JSendErrorResponse
 *
 * @since 1.4.0
 */
class CliErrorResponseCore extends AbstractErrorPage
{
    /**
     * Return content type
     * @return string
     */
    protected function getContentType()
    {
        return 'text/plain';
    }

    /**
     * @param ErrorDescription $errorDescription
     * @return string
     */
    protected function renderError(ErrorDescription $errorDescription)
    {
        $message = $errorDescription->getExtendedMessage() . "\n";
        $message .= "Stacktrace:\n" . $errorDescription->getTraceAsString() . "\n";
        $cause = $errorDescription->getCause();
        while ($cause) {
            $message .= "Cause by " . $cause->getExtendedMessage() . "\n";
            $cause = $cause->getCause();
        }
        return $message;
    }
}
