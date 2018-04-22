<?php
/**
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2018 thirty bees
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
 * @author    thirty bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2018 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class PageNotFoundControllerCore
 *
 * @since 1.0.0
 */
class PageNotFoundControllerCore extends FrontController
{
    // @codingStandardsIgnoreSta
    /** @var string $php_self */
    public $php_self = 'pagenotfound';
    /** @var string $page_name */
    public $page_name = 'pagenotfound';
    /** @var bool $ssl */
    public $ssl = true;
    // @codingStandardsIgnoreEnd

    /**
     * Assign template vars related to page content
     *
     * @see   FrontController::initContent()
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function initContent()
    {
        header('HTTP/1.1 404 Not Found');
        header('Status: 404 Not Found');

        if (preg_match('/\.(gif|jpe?g|png|ico)$/i', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
            $this->context->cookie->disallowWriting();

            // First preg_match() matches friendly URLs, second one plain URLs.
            if (preg_match('@^'.__PS_BASE_URI__
                .'([0-9]+)\-([_a-zA-Z-]+)(/[_a-zA-Z-]+)?\.(png|jpe?g|gif)$@',
                $_SERVER['REQUEST_URI'], $matches)
                || preg_match('@^'._PS_PROD_IMG_
                   .'[0-9/]+/([0-9]+)\-([_a-zA-Z]+)(\.)(png|jpe?g|gif)$@',
                   $_SERVER['REQUEST_URI'], $matches)) {
                $imageType = ImageType::getByNameNType($matches[2], 'products');
                if ($imageType && count($imageType)) {
                    $root = _PS_PROD_IMG_DIR_;
                    $folder = Image::getImgFolderStatic($matches[1]);
                    $file = $matches[1];
                    $ext = '.'.$matches[4];

                    if (file_exists($root.$folder.$file.$ext)) {
                        if (ImageManager::resize($root.$folder.$file.$ext, $root.$folder.$file.'-'.$matches[2].$ext, (int) $imageType['width'], (int) $imageType['height'])) {
                            header('HTTP/1.1 200 Found');
                            header('Status: 200 Found');
                            header('Content-Type: image/jpg');
                            readfile($root.$folder.$file.'-'.$matches[2].$ext);
                            exit;
                        }
                    }
                }
            } elseif (preg_match('@^'.__PS_BASE_URI__
                      .'c/([0-9]+)\-([_a-zA-Z-]+)(/[_a-zA-Z0-9-]+)?\.(png|jpe?g|gif)$@',
                      $_SERVER['REQUEST_URI'], $matches)
                      || preg_match('@^'._THEME_CAT_DIR_
                         .'([0-9]+)\-([_a-zA-Z-]+)(\.)(png|jpe?g|gif)$@',
                         $_SERVER['REQUEST_URI'], $matches)) {
                $imageType = ImageType::getByNameNType($matches[2], 'categories');
                if ($imageType && count($imageType)) {
                    $root = _PS_CAT_IMG_DIR_;
                    $file = $matches[1];
                    $ext = '.'.$matches[4];

                    if (file_exists($root.$file.$ext)) {
                        if (ImageManager::resize($root.$file.$ext, $root.$file.'-'.$matches[2].$ext, (int) $imageType['width'], (int) $imageType['height'])) {
                            header('HTTP/1.1 200 Found');
                            header('Status: 200 Found');
                            header('Content-Type: image/jpg');
                            readfile($root.$file.'-'.$matches[2].$ext);
                            exit;
                        }
                    }
                }
            }

            header('Content-Type: image/gif');
            readfile(_PS_IMG_DIR_.'404.gif');
            exit;
        } elseif (in_array(mb_strtolower(substr($_SERVER['REQUEST_URI'], -3)), ['.js', 'css'])) {
            $this->context->cookie->disallowWriting();
            exit;
        }

        parent::initContent();

        $this->setTemplate(_PS_THEME_DIR_.'404.tpl');
    }

    /**
     * Canonical redirection
     *
     * @param string $canonicalUrl
     *
     * @deprecated 1.0.1
     */
    protected function canonicalRedirection($canonicalUrl = '')
    {
        // 404 - no need to redirect to the canonical url
    }

    /**
     * SSL redirection
     *
     * @deprecated 1.0.1
     */
    protected function sslRedirection()
    {
        // 404 - no need to redirect
    }
}
