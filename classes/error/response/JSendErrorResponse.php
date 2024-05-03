<?php
/**
 * Copyright (C) 2017-2024 thirty bees
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
 * @copyright 2017-2024 thirty bees
 * @license   Open Software License (OSL 3.0)
 */

namespace Thirtybees\Core\Error\Response;

use Thirtybees\Core\Error\ErrorDescription;

/**
 * Class JSendErrorResponse
 */
class JSendErrorResponseCore extends AbstractErrorPage
{
    /**
     * @var bool
     */
    protected $sendErrorMessage;

    /**
     * @param bool $sendErrorMessage
     */
    public function __construct(bool $sendErrorMessage)
    {
        $this->sendErrorMessage = $sendErrorMessage;
    }

    /**
     * Return content type
     * @return string
     */
    protected function getContentType()
    {
        return 'application/json';
    }

    /**
     * @param ErrorDescription $errorDescription
     * @return string
     */
    protected function renderError(ErrorDescription $errorDescription)
    {
        return json_encode([
            'status' => 'error',
            'message' => $this->getResponseMessage($errorDescription)
        ], JSON_PRETTY_PRINT);
    }

    /**
     * @param ErrorDescription $errorDescription
     * @return string
     */
    protected function getResponseMessage(ErrorDescription $errorDescription)
    {
        if ($this->sendErrorMessage) {
            return $errorDescription->getExtendedMessage();
        } else {
            return \Tools::displayError('Internal server error');
        }
    }
}
