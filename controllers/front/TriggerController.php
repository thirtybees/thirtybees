<?php
/**
 * Copyright (C) 2021-2021 thirty bees
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
 * @copyright 2021-2021 thirty bees
 * @license   Open Software License (OSL 3.0)
 */

use Thirtybees\Core\DependencyInjection\ServiceLocator;

/**
 * Class TriggerControllerCore
 */
class TriggerControllerCore extends FrontController
{
    /**
     * @var string
     */
    public $php_self = 'trigger';

    /**
     * Initialize content
     * @throws PrestaShopException
     */
    public function initContent()
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        // Allow CORS requests
        header("Access-Control-Allow-Origin: *");
        if ($method === 'OPTIONS') {
            exit;
        }

        // Do heavy work on POST requests only
        header('Content-Type: application/json;charset=UTF-8');
        if ($method === 'POST') {
            if (Tools::getValue('secret')) {
                $secret = Tools::getValue("secret");
                $scheduler = ServiceLocator::getInstance()->getScheduler();

                if ($secret == $scheduler->getSyntheticEventSecret()) {
                    try {
                        $scheduler->deleteSyntheticEventSecret();
                        $scheduler->run();
                        $this->ajaxDie(json_encode([
                            'status' => 'success'
                        ]));
                    } catch (Exception $e) {
                        PrestaShopLogger::addLog("Scheduler failed: " . $e);
                        $this->ajaxDie(json_encode([
                            'status' => 'failed',
                            'error' => 'Internal server error'
                        ]));
                    }
                }
            }
        }

        $this->ajaxDie(json_encode([
            'status' => 'failed',
            'error' => 'Forbidden'
        ]));
    }
}
