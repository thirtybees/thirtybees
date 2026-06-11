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

use GuzzleHttp\Client;
use Psr\Http\Message\StreamInterface;

class AdminAddonsCatalogControllerCore extends AdminController
{
    const ADDONS_URL = '/catalog/catalog.json';

    /**
     * @var string[]|null
     */
    private $modulesAvailable = null;

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
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function initContent()
    {
        $this->context->smarty->assign([
            'iso_lang' => $this->context->language->iso_code,
            'iso_currency' => $this->context->currency->iso_code,
            'iso_country' => $this->context->country->iso_code,
            'addons_content' => $this->getCatalog(),
        ]);

        parent::initContent();
    }

    /**
     * Returns catalog content
     *
     * @return array
     * @throws PrestaShopException
     */
    protected function getCatalog()
    {
        $content = $this->downloadCatalog();
        if (!$content) {
            return [];
        }

        $parsed = json_decode($content, true);
        if (!is_array($parsed) || !array_key_exists('content', $parsed)) {
            return [];
        }

        $sections = array_map([$this, 'processSection'], $parsed['content']);
        return [
            'ad_top' => $parsed['ad_top'],
            'content' => $sections
        ];
    }

    /**
     * @param array $section
     * @return array
     */
    protected function processSection(array $section): array
    {
        if (isset($section['modules']) && is_array($section['modules'])) {
            $section['modules'] = array_map([$this, 'processModule'], $section['modules']);
        }
        return $section;
    }

    /**
     * @param array $module
     * @return array
     * @throws PrestaShopException
     */
    protected function processModule(array $module): array
    {
        $button = [
            'label' => $this->l('Learn more'),
            'url' => $module['url']
        ];
        if (isset($module['module'])) {
            $moduleName = (string)$module['module'];
            $availableModules = $this->getAvailableModules();
            if (isset($availableModules[$moduleName])) {
                $installed = $availableModules[$moduleName];
                if ($installed) {
                    $button = [
                        'label' => $this->l('Configure'),
                        'url' => $this->context->link->getAdminLink('AdminModules', true, [
                            'module_name' => $moduleName,
                            'configure' => $moduleName,
                        ]),
                    ];
                } else {
                    $button = [
                        'label' => $this->l('Install'),
                        'url' => $this->context->link->getAdminLink('AdminModules', true, [
                            'module_name' => $moduleName,
                            'anchor' => ucfirst($moduleName)
                        ]),
                    ];
                }
            }
        }
        $module['button'] = $button;
        return $module;
    }

    /**
     * @return string[]
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function getAvailableModules(): array
    {
        if (is_null($this->modulesAvailable)) {
            $this->modulesAvailable = [];
            foreach (Module::getModulesOnDisk(true) as $moduleInfo) {
                if ($moduleInfo->canInstall) {
                    $this->modulesAvailable[$moduleInfo->name] = (bool)$moduleInfo->installed;
                }
            }
        }
        return $this->modulesAvailable;
    }

    /**
     * Downloads catalog json feed
     *
     * @return StreamInterface|null
     * @throws PrestaShopException
     */
    protected function downloadCatalog()
    {
        $guzzle = new Client([
            'base_uri' => Configuration::getApiServer(),
            'http_errors' => true,
            'verify' => Configuration::getSslTrustStore(),
            'timeout' => 20,
        ]);

        try {
            return $guzzle->get(static::ADDONS_URL, [
                'headers' => [
                    'X-SID' => Configuration::getServerTrackingId()
                ]
            ])->getBody();
        } catch (Throwable $e) {
            return null;
        }
    }
}
