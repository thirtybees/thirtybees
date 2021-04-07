<?php
/**
 * Copyright (C) 2018 thirty bees
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
 * @copyright 2018 thirty bees
 * @license   Open Software License (OSL 3.0)
 */

class AdminAddonsCatalogControllerCore extends AdminController
{
    const ADDONS_URL = '/catalog/catalog.json';

    /**
     * AdminAddonsCatalogControllerCore constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    /**
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function initContent()
    {
        $this->context->smarty->assign([
            'iso_lang'        => $this->context->language->iso_code,
            'iso_currency'    => $this->context->currency->iso_code,
            'iso_country'     => $this->context->country->iso_code,
            'addons_content'  => $this->getCatalog(),
        ]);

        parent::initContent();
    }

    /**
     * Returns catalog content
     *
     * @return array
     */
    protected function getCatalog()
    {
        $content = $this->downloadCatalog();
        if ($content) {
            $parsed = json_decode($content, true);
            if (is_array($parsed)) {
                return $parsed;
            }
        }
        return [];
    }

    /**
     * Downloads catalog json feed
     *
     * @return string | null
     */
    protected function downloadCatalog()
    {
        $guzzle = new \GuzzleHttp\Client([
            'base_uri'    => Configuration::getApiServer(),
            'http_errors' => true,
            'verify'      => _PS_TOOL_DIR_.'cacert.pem',
            'timeout'     => 20,
        ]);

        try {
            return $guzzle->get(static::ADDONS_URL)->getBody();
        } catch (Exception $e) {
            return null;
        }
    }
}
