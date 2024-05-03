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

/**
 * Class AdminConnectControllerCore
 *
 * @property Configuration|null $object
 */
class AdminConnectControllerCore extends AdminController
{
    const ACTION_CONNECT = 'connect';

    /**
     * AdminConnectControllerCore constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->className = 'Configuration';
        $this->table = 'configuration';
        $this->bootstrap = true;

        parent::__construct();

        $this->toolbar_title = $this->l('Connect thirty bees account');
    }

    /**
     * @return false|mixed|void
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function postProcess()
    {
        if (Tools::isSubmit(static::ACTION_CONNECT)) {
            $this->connect();
        }
        return parent::postProcess();
    }

    /**
     * @return void
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    private function connect()
    {
        $urls = [];
        $link = $this->context->link;
        $shopIds = Shop::getShops(false, null, true);
        sort($shopIds);
        foreach ($shopIds as $shopId) {
            $shop = new Shop($shopId);
            if ($shop->domain || $shop->domain_ssl) {
                $url = $link->getPageLink('connect', null, null, null, false, $shopId);
                $code = $shopId . '-' . Tools::passwdGen(40);
                Configuration::updateValue(Configuration::CONNECT_CODE, $code . '|' . time(), false, null, $shopId);
                $urls[] = [
                    'id' => $shopId,
                    'baseUrl' => $shop->getBaseURL(true),
                    'code' => $code,
                    'url' => $url,
                ];
            }
        }

        $this->context->smarty->assign([
            'redirectUrl' => 'https://accounts.thirtybees.com/connect/init',
            'connectUrls' => $urls
        ]);
        die($this->getSmartyOutputContent('controllers/connect/redirect.tpl'));
    }


}
