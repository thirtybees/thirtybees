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

use \GuzzleHttp\Exception\RequestException;

class AdminAddonsCatalogControllerCore extends AdminController
{
    const ADDONS_URL = 'https://api.thirtybees.com/catalog/catalog.json';

    public function __construct()
    {
        $this->bootstrap = true;

        parent::__construct();
    }

    public function initContent()
    {
        $parentDomain = Tools::getHttpHost(true).substr($_SERVER['REQUEST_URI'], 0, -1 * strlen(basename($_SERVER['REQUEST_URI'])));

        $addonsContent = false;
        $guzzle = new \GuzzleHttp\Client([
            'http_errors' => true,
            'verify'      => _PS_TOOL_DIR_.'cacert.pem',
            'timeout'     => 20,
        ]);
        try {
            $addonsContent = $guzzle->get(static::ADDONS_URL)->getBody();
        } catch (RequestException $e) {
        }
        if ($addonsContent) {
            $addonsContent = json_decode($addonsContent, true);
        }

        $this->context->smarty->assign([
            'iso_lang'        => $this->context->language->iso_code,
            'iso_currency'    => $this->context->currency->iso_code,
            'iso_country'     => $this->context->country->iso_code,
            'addons_content'  => $addonsContent,
            'parent_domain'   => $parentDomain,
        ]);

        parent::initContent();
    }
}
