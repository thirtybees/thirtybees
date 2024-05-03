<?php
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
 * @author    thirty bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2024 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class PageNotFoundControllerCore
 */
class PageNotFoundControllerCore extends FrontController
{
    /** @var string $php_self */
    public $php_self = 'pagenotfound';
    /** @var string $page_name */
    public $page_name = 'pagenotfound';
    /** @var bool $ssl */
    public $ssl = true;

    /**
     * Assign template vars related to page content
     *
     * @see FrontController::initContent()
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function initContent()
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $urlPath = parse_url($requestUri, PHP_URL_PATH);
        if ($urlPath && preg_match('/\.(webp|gif|jpe?g|png|ico)$/i', $urlPath)) {
            $requestUri = urldecode($requestUri);
            $this->context->cookie->disallowWriting();

            $imageType = null;
            $sourcePath = null;
            $sendPath = null;

            // product image without image types: /127/name.jpg
            if (preg_match('@^'.__PS_BASE_URI__.'([0-9]+)/.+\.(webp|png|jpe?g|gif)$@', $requestUri, $matches)) {
                $root = _PS_PROD_IMG_DIR_;
                $file = $matches[1];
                $folder = Image::getImgFolderStatic($file);
                $ext = '.'. $this->normalizeImageExtension($matches[2]);
                $sendPath = $this->getImageSourcePath($root.$folder.$file.$ext);
            // product image url with image type: /127-cart/name.jpg
            } elseif (
                preg_match('@^'.__PS_BASE_URI__.'([0-9]+)-([_a-zA-Z0-9\s-]+)(/.+)?\.(webp|png|jpe?g|gif)$@', $requestUri, $matches) ||
                preg_match('@^'._PS_PROD_IMG_.'[0-9/]+/([0-9]+)-([_a-zA-Z0-9\s-]+)(\.)(webp|png|jpe?g|gif)$@', $requestUri, $matches)
            ) {
                $imageType = ImageType::getByNameNType($matches[2], 'products');
                if ($imageType) {
                    $root = _PS_PROD_IMG_DIR_;
                    $folder = Image::getImgFolderStatic($matches[1]);
                    $file = $matches[1];
                    $ext = '.'. $this->normalizeImageExtension($matches[4]);

                    $sourcePath = $root.$folder.$file.$ext;
                    $sendPath = $root.$folder.$file.'-'.$imageType['name'].$ext;
                }
            } else {
                foreach ([
                    // Entries should match the list in
                    // ImageType::getByNameNType(), except for 'products'.
                    'categories'    => _THEME_CAT_DIR_,
                    'manufacturers' => _THEME_MANU_DIR_,
                    'suppliers'     => _THEME_SUP_DIR_,
                    'scenes'        => _THEME_SCENE_DIR_,
                    'stores'        => _THEME_STORE_DIR_,
                ] as $type => $path) {
                    $dir = str_replace(_PS_IMG_, '', $path);
                    if (preg_match('@^'.__PS_BASE_URI__.$dir.'([0-9]+)-([_a-zA-Z0-9\s-]+)(/.+)?\.(webp|png|jpe?g|gif)$@', $requestUri, $matches) ||
                        preg_match('@^'.$path .'([0-9]+)-([_a-zA-Z0-9\s-]+)(\.)(webp|png|jpe?g|gif)$@', $requestUri, $matches)
                    ) {
                        $imageType = ImageType::getByNameNType(
                            $matches[2],
                            $type
                        );
                        if ($imageType) {
                            $root = _PS_IMG_DIR_.$dir;
                            $file = $matches[1];
                            $ext = '.'.$this->normalizeImageExtension($matches[4]);

                            $sourcePath = $root.$file.$ext;
                            $sendPath = $root.$file.'-'.$imageType['name'].$ext;

                            break;
                        }
                    }
                }
            }

            if ($sendPath) {
                if (! file_exists($sendPath) && $sourcePath && $imageType) {
                    $sourcePath = $this->getImageSourcePath($sourcePath);
                    if ($sourcePath) {
                        ImageManager::resize(
                            $sourcePath,
                            $sendPath,
                            (int) $imageType['width'],
                            (int) $imageType['height']
                        );
                    }
                }

                if (file_exists($sendPath)) {
                    $type = pathinfo($sendPath, PATHINFO_EXTENSION);
                    $type = str_replace('jpg', 'jpeg', $type);
                    header('HTTP/1.1 200 Found');
                    header('Status: 200 Found');
                    header('Content-Type: image/'.$type);
                    readfile($sendPath);

                    exit;
                }
            }

            header('HTTP/1.1 404 Not Found');
            header('Status: 404 Not Found');
            header('Content-Type: image/gif');
            readfile(_PS_IMG_DIR_.'404.gif');
            exit;
        } elseif (in_array(mb_strtolower(substr($requestUri, -3)), ['.js', 'css'])) {
            $this->context->cookie->disallowWriting();
            exit;
        }

        header('HTTP/1.1 404 Not Found');
        header('Status: 404 Not Found');
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

    /**
     * Returns valid image source path
     *
     * @param string $sourcePath
     * @return string | null
     */
    protected function getImageSourcePath(string $sourcePath)
    {
        if (file_exists($sourcePath)) {
            return $sourcePath;
        }
        foreach (['.jpg', '.webp', '.jpeg'] as $ext) {
            $sourcePath = preg_replace( '/\.(webp|gif|jpe?g|png|ico)$/i', $ext, $sourcePath);
            if (file_exists($sourcePath)) {
                return $sourcePath;
            }
        }
        return null;
    }

    /**
     * returns image extension
     *
     * @param string $ext
     * @return string
     */
    protected function normalizeImageExtension($ext)
    {
        $ext = strtolower((string)$ext);
        if ($ext === 'jpeg') {
            return 'jpg';
        }
        return $ext;
    }
}
