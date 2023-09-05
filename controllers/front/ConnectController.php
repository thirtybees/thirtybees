<?php
/**
 * Copyright (C) 2023 thirty bees
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
 * @copyright 2023 thirty bees
 * @license   Open Software License (OSL 3.0)
 */

use Thirtybees\Core\DependencyInjection\ServiceLocator;
use Thirtybees\Core\Error\Response\JSendErrorResponse;

/**
 * Class ConnectControllerCore
 */
class ConnectControllerCore extends FrontController
{
    const CONNECT_CODE = 'code';

    /**
     * @var string
     */
    public $php_self = 'connect';

    /**
     * Initialize content
     * @throws PrestaShopException
     */
    public function initContent()
    {
        $method = Tools::getRequestMethod();
        if ($method !== 'POST') {
            Tools::redirect($this->context->link->getPageLink('index'));
        }

        header('Content-Type: application/json;charset=UTF-8');
        ServiceLocator::getInstance()->getErrorHandler()->setErrorResponseHandler(new JSendErrorResponse(_PS_MODE_DEV_));

        $parts = explode('|', (string)Configuration::get(Configuration::CONNECT_CODE));
        $expectedCode = $parts[0] ?? '';
        $ts = (int)$parts[1] ?? 0;

        if (!$expectedCode || !$ts) {
            $this->sendResponse('fail', "Connect code was not issued yet");
        }

        if ($ts < (time() - 300)) {
            $this->sendResponse('fail', "Expired connect code");
        }

        $providedCode = Tools::getValue(static::CONNECT_CODE);
        if ($providedCode !== $expectedCode) {
            $this->sendResponse('fail', "Invalid authorization code");
        }

        Configuration::updateGlobalValue(Configuration::CONNECTED, 1);
        $this->sendResponse('success', Configuration::getServerTrackingId());
    }

    /**
     * @return void
     */
    protected function displayMaintenancePage()
    {
        // no-op
    }

    /**
     * @param string $status
     * @param $data
     *
     * @return void
     * @throws PrestaShopException
     */
    private function sendResponse(string $status, $data)
    {
        $this->ajaxDie(json_encode([
            'status' => $status,
            'data' => $data
        ]));
    }


}
