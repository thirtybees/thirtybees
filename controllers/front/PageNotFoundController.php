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

        $mainImageExtensions = implode('|', ImageManager::getAllowedImageExtensions(false, true));

        if ($urlPath && preg_match('/\.('.$mainImageExtensions.'|ico)$/i', $urlPath)) {
            $requestUri = urldecode($requestUri);
            $requestUri = preg_replace('#^'.preg_quote(Context::getContext()->shop->getBaseURI(), '#').'#i', '/', $requestUri);

            $this->context->cookie->disallowWriting();

            $imageType = null;
            $sourcePath = null;
            $sendPath = null;
            $imageTypeName = '';

            $uriParts = explode('/', ltrim($requestUri, '/'));

            if (($imageEntityName = $uriParts[0]) && ($imageEntity = ImageEntity::getImageEntities($imageEntityName, true))) {

                // Check if we have a model
                $idEntity_imageType = '';
                $linkRewrite_retina_imageExtension = '';

                if (count($uriParts)==4) {
                    $idEntity_imageType = $uriParts[2];
                    $linkRewrite_retina_imageExtension = $uriParts[3];
                } else {
                    if (count($uriParts) == 3) {
                        $idEntity_imageType = $uriParts[1];
                        $linkRewrite_retina_imageExtension = $uriParts[2];
                    }
                }

                // Separate idEntity and imageTypeName
                $parts = explode('-', $idEntity_imageType);
                $idEntity = $parts[0];
                $imageTypeName = count($parts) > 1 ? str_replace($idEntity.'-', '', $idEntity_imageType) : '';

                // Check if $imageTypeName is actually allowed for this entity
                if (!$imageTypeName || in_array($imageTypeName, array_column($imageEntity['imageTypes'], 'name'))) {

                    $imageType = ImageType::getInstanceByName($imageTypeName);

                    // Check if imageType has a parent
                    if ($imageType->id_image_type_parent) {
                        $imageType = new ImageType($imageType->id_image_type_parent);
                        $imageTypeName = $imageType->name;
                    }

                    $imageTypeNameFormatted = $imageTypeName ? '-' . $imageTypeName : '';

                    // Separate retina and imageExtension
                    $imageExtension = pathinfo($linkRewrite_retina_imageExtension, PATHINFO_EXTENSION);
                    $imageExtension = $this->normalizeImageExtension($imageExtension);
                    $retina = str_contains($linkRewrite_retina_imageExtension, '2x.' . $imageExtension) && $imageType->id ? '2x' : '';

                    // As products have a sophisticated image system with folder structure
                    $subfolder = ($imageEntity['name'] == ImageEntity::ENTITY_TYPE_PRODUCTS) ? Image::getImgFolderStatic($idEntity) : '';

                    $sendPath = $imageEntity['path'] . $subfolder . $idEntity . $imageTypeNameFormatted . $retina . '.' . $imageExtension;
                    $sourcePath = ImageManager::getSourceImage($imageEntity['path'] . $subfolder, $idEntity);
                }
            } else {
                // Check if source file is actually requested, but in a missing extension
                $sendPath = ImageManager::tryRestoreImage(_PS_ROOT_DIR_ . '/' . ltrim($requestUri, '/'));
            }

            if ($sendPath) {

                if (!file_exists($sendPath) && $sourcePath && $imageType->width && $imageType->height) {
                    // Create the image in the default imageExtension (readable by the user)
                    ImageManager::resize(
                        $sourcePath,
                        $sendPath,
                        $imageType->width,
                        $imageType->height,
                    );
                }

                if (file_exists($sendPath)) {
                    $imageExtension = pathinfo($sendPath, PATHINFO_EXTENSION);
                    $mimeType = Media::getFileInformations('images', $imageExtension)['mimeType'] ?? 'image/jpeg';
                    header('HTTP/1.1 200 Found');
                    header('Status: 200 Found');
                    header('Content-Type: '.$mimeType);
                    readfile($sendPath);
                    exit;
                }
            }

            // We haven't found any image, we try to display the default image
            if ($notFoundImage = $this->context->link->getDefaultImageUri($this->context->language->iso_code, $imageTypeName, false, '', true)) {
                $imageExtension = pathinfo($notFoundImage, PATHINFO_EXTENSION);
                $mimeType = Media::getFileInformations('images', $imageExtension)['mimeType'] ?? 'image/jpeg';
                header('HTTP/1.1 200 Found');
                header('Status: 200 Found');
                header('Content-Type: '.$mimeType);
                readfile($notFoundImage);
                exit;
            }

            // We haven't even found the default image (should never happen in theory)
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
