<?php

use Thirtybees\Core\DependencyInjection\ServiceLocator;

/**
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2024 thirty bees
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
 *  @copyright 2017-2024 thirty bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

class Tools extends ToolsCore
{
    /**
     * @param string $url Desired URL
     * @param false|string $baseUri Base URI (optional)
     * @param Link|null $link
     * @param string|string[]|null $headers A list of headers to send before redirection
     *
     *
     * @return void
     * @throws PrestaShopException
     */
    public static function redirect($url, $baseUri = __PS_BASE_URI__, Link $link = null, $headers = null)
    {
        if (!$link) {
            $link = Context::getContext()->link;
        }

        if (strpos($url, 'http://') === false && strpos($url, 'https://') === false && $link) {
            if (strpos($url, $baseUri) === 0) {
                $url = substr($url, strlen($baseUri));
            }
            if (strpos($url, 'index.php?controller=') !== false && strpos($url, 'index.php/') == 0) {
                $url = substr($url, strlen('index.php?controller='));
                if (Configuration::get('PS_REWRITING_SETTINGS')) {
                    $url = Tools::strReplaceFirst('&', '?', $url);
                }
            }

            $explode = explode('?', $url);
            // don't use ssl if url is home page
            // used when logout for example
            $use_ssl = !empty($url);
            $url = $link->getPageLink($explode[0], $use_ssl);
            if (isset($explode[1])) {
                $url .= '?'.$explode[1];
            }
        }

        // Send additional headers
        if ($headers) {
            if (!is_array($headers)) {
                $headers = [$headers];
            }

            foreach ($headers as $header) {
                header($header);
            }
        }

        Context::getContext()->controller->setRedirectAfter($url);
    }

    /**
     * @return string
     * @throws PrestaShopException
     */
    public static function getDefaultControllerClass()
    {
        if (isset(Context::getContext()->employee) && Validate::isLoadedObject(Context::getContext()->employee) && isset(Context::getContext()->employee->default_tab)) {
            $defaultController = Tab::getClassNameById((int)Context::getContext()->employee->default_tab);
        }
        if (empty($defaultController)) {
            $defaultController = 'AdminDashboard';
        }
        $controllers = Dispatcher::getControllers([
            _PS_ADMIN_DIR_.'/tabs/',
            _PS_ADMIN_CONTROLLER_DIR_,
            _PS_OVERRIDE_DIR_.'controllers/admin/'
        ]);
        if (! isset($controllers[strtolower($defaultController)])) {
            $defaultController = 'adminnotfound';
        }
        return $controllers[strtolower($defaultController)] ?? '';
    }

    /**
     * @param string $url
     *
     * @return void
     */
    public static function redirectLink($url)
    {
    }

    /**
     * @param string $url
     *
     * @return void
     * @throws SmartyException
     */
    public static function redirectAdmin($url)
    {
        if (!is_object(Context::getContext()->controller)) {
            try {
                $controller = ServiceLocator::getInstance()->getController(Tools::getDefaultControllerClass());
                $controller->setRedirectAfter($url);
                $controller->run();
                Context::getContext()->controller = $controller;
                die();
            } catch (PrestaShopException $e) {
                $e->displayMessage();
            }
        } else {
            Context::getContext()->controller->setRedirectAfter($url);
        }
    }
}
