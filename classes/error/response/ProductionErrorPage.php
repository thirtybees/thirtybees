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
class ProductionErrorPageCore extends AbstractErrorPage
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
     * @throws \PrestaShopException
     */
    protected function renderError(ErrorDescription $errorDescription)
    {
        return static::displayErrorTemplate(
            _PS_ROOT_DIR_.'/error500.phtml',
            [
                'shopEmail' => \Configuration::get('PS_SHOP_EMAIL'),
                'encrypted' => $errorDescription->encrypt(),
            ]
        );
    }
}
