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
 * Class WebserviceSpecificManagementImagesCore
 *
 * @since 1.0.0
 */
class WebserviceSpecificManagementImagesCore implements WebserviceSpecificManagementInterface
{
    /** @var WebserviceOutputBuilder */
    protected $objOutput;
    protected $output;

    /** @var WebserviceRequest */
    protected $wsObject;

    /**
     * @var string The extension of the image to display
     */
    protected $imgExtension;

    /**
     * @var array The type of images (general, categories, manufacturers, suppliers, stores...)
     */
    protected $imageTypes = [
        'general'        => [
            'header'     => [],
            'mail'       => [],
            'invoice'    => [],
            'store_icon' => [],
        ],
        'products'       => [],
        'categories'     => [],
        'manufacturers'  => [],
        'suppliers'      => [],
        'stores'         => [],
        'customizations' => [],
    ];

    /**
     * @var string The image type (product, category, general,...)
     */
    protected $imageType = null;

    /**
     * @var array The list of supported mime types
     */
    protected $acceptedImgMimeTypes = ['image/gif', 'image/jpg', 'image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png'];

    /**
     * @var string The product image declination id
     */
    protected $productImageDeclinationId = null;

    /**
     * @var bool If the current image management has to manage a "default" image (i.e. "No product available")
     */
    protected $defaultImage = false;

    /**
     * @var string The file path of the image to display. If not null, the image will be displayed, even if the XML output was not empty
     */
    public $imgToDisplay = null;
    public $imageResource = null;

    /* ------------------------------------------------
     * GETTERS & SETTERS
     * ------------------------------------------------ */

    /**
     * @param WebserviceOutputBuilderCore $obj
     *
     * @return WebserviceSpecificManagementInterface
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setObjectOutput(WebserviceOutputBuilderCore $obj)
    {
        $this->objOutput = $obj;

        return $this;
    }

    /**
     * @return WebserviceOutputBuilder
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getObjectOutput()
    {
        return $this->objOutput;
    }

    /**
     * @param WebserviceRequestCore $obj
     *
     * @return $this
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setWsObject(WebserviceRequestCore $obj)
    {
        $this->wsObject = $obj;

        return $this;
    }

    /**
     * @return WebserviceRequest
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getWsObject()
    {
        return $this->wsObject;
    }

    /**
     * @return string
     * @throws WebserviceException
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getContent()
    {
        if ($this->output != '') {
            return $this->objOutput->getObjectRender()->overrideContent($this->output);
        } // display image content if needed
        elseif ($this->imgToDisplay) {
            if (empty($this->imgExtension)) {
                $imginfo = getimagesize($this->imgToDisplay);
                $this->imgExtension = image_type_to_extension($imginfo[2], false);
            }
            $imageResource = false;
            $types = [
                'jpg'  => [
                    'function'     => 'imagecreatefromjpeg',
                    'Content-Type' => 'image/jpeg',
                ],
                'jpeg' => [
                    'function'     => 'imagecreatefromjpeg',
                    'Content-Type' => 'image/jpeg',
                ],
                'png'  => [
                    'function'     =>
                        'imagecreatefrompng',
                    'Content-Type' => 'image/png',
                ],
                'gif'  => [
                    'function'     => 'imagecreatefromgif',
                    'Content-Type' => 'image/gif',
                ],
            ];
            if (array_key_exists($this->imgExtension, $types)) {
                $imageResource = @$types[$this->imgExtension]['function']($this->imgToDisplay);
            }

            if (!$imageResource) {
                throw new WebserviceException(sprintf('Unable to load the image "%s"', str_replace(_PS_ROOT_DIR_, '[SHOP_ROOT_DIR]', $this->imgToDisplay)), [47, 500]);
            } else {
                if (array_key_exists($this->imgExtension, $types)) {
                    $this->objOutput->setHeaderParams('Content-Type', $types[$this->imgExtension]['Content-Type']);
                }

                return file_get_contents($this->imgToDisplay);
            }
        }
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function manage()
    {
        $this->manageImages();

        return $this->wsObject->getOutputEnabled();
    }

    /**
     * Management of images URL segment
     *
     * @return bool
     *
     * @throws WebserviceException
     */
    protected function manageImages()
    {
        /*
         * available cases api/... :
         *
         *   images ("types_list") (N-1)
         *   	GET    (xml)
         *   images/general ("general_list") (N-2)
         *   	GET    (xml)
         *   images/general/[header,+] ("general") (N-3)
         *   	GET    (bin)
         *   	PUT    (bin)
         *
         *
         *   images/[categories,+] ("normal_list") (N-2) ([categories,+] = categories, manufacturers, ...)
         *   	GET    (xml)
         *   images/[categories,+]/[1,+] ("normal") (N-3)
         *   	GET    (bin)
         *   	PUT    (bin)
         *   	DELETE
         *   	POST   (bin) (if image does not exists)
         *   images/[categories,+]/[1,+]/[small,+] ("normal_resized") (N-4)
         *   	GET    (bin)
         *   images/[categories,+]/default ("display_list_of_langs") (N-3)
         *   	GET    (xml)
         *   images/[categories,+]/default/[en,+] ("normal_default_i18n")  (N-4)
         *   	GET    (bin)
         *   	POST   (bin) (if image does not exists)
         *      PUT    (bin)
         *      DELETE
         *   images/[categories,+]/default/[en,+]/[small,+] ("normal_default_i18n_resized")  (N-5)
         *   	GET    (bin)
         *
         *   images/product ("product_list")  (N-2)
         *   	GET    (xml) (list of image)
         *   images/product/[1,+] ("product_description")  (N-3)
         *   	GET    (xml) (legend, declinations, xlink to images/product/[1,+]/bin)
         *   images/product/[1,+]/bin ("product_bin")  (N-4)
         *   	GET    (bin)
         *      POST   (bin) (if image does not exists)
         *   images/product/[1,+]/[1,+] ("product_declination")  (N-4)
         *   	GET    (bin)
         *   	POST   (xml) (legend)
         *   	PUT    (xml) (legend)
         *      DELETE
         *   images/product/[1,+]/[1,+]/bin ("product_declination_bin") (N-5)
         *   	POST   (bin) (if image does not exists)
         *   	GET    (bin)
         *   	PUT    (bin)
         *   images/product/[1,+]/[1,+]/[small,+] ("product_declination_resized") (N-5)
         *   	GET    (bin)
         *   images/product/default ("product_default") (N-3)
         *   	GET    (bin)
         *   images/product/default/[en,+] ("product_default_i18n") (N-4)
         *   	GET    (bin)
         *      POST   (bin)
         *      PUT   (bin)
         *      DELETE
         *   images/product/default/[en,+]/[small,+] ("product_default_i18n_resized") (N-5)
         * 		GET    (bin)
         *
         */

        /* Declinated
         *ok    GET    (bin)
         *ok images/product ("product_list")  (N-2)
         *ok	GET    (xml) (list of image)
         *ok images/product/[1,+] ("product_description")  (N-3)
         *   	GET    (xml) (legend, declinations, xlink to images/product/[1,+]/bin)
         *ok images/product/[1,+]/bin ("product_bin")  (N-4)
         *ok 	GET    (bin)
         *      POST   (bin) (if image does not exists)
         *ok images/product/[1,+]/[1,+] ("product_declination")  (N-4)
         *ok 	GET    (bin)
         *   	POST   (xml) (legend)
         *   	PUT    (xml) (legend)
         *      DELETE
         *ok images/product/[1,+]/[1,+]/bin ("product_declination_bin") (N-5)
         *   	POST   (bin) (if image does not exists)
         *ok 	GET    (bin)
         *   	PUT    (bin)
         *   images/product/[1,+]/[1,+]/[small,+] ("product_declination_resized") (N-5)
         *ok 	GET    (bin)
         *ok images/product/default ("product_default") (N-3)
         *ok 	GET    (bin)
         *ok images/product/default/[en,+] ("product_default_i18n") (N-4)
         *ok 	GET    (bin)
         *      POST   (bin)
         *      PUT   (bin)
         *      DELETE
         *ok images/product/default/[en,+]/[small,+] ("product_default_i18n_resized") (N-5)
         *ok	GET    (bin)
         *
         * */

        // Pre configuration...
        if (isset($this->wsObject->urlSegment)) {
            for ($i = 1; $i < 6; $i++) {
                if (count($this->wsObject->urlSegment) == $i) {
                    $this->wsObject->urlSegment[$i] = '';
                }
            }
        }

        $this->imageType = $this->wsObject->urlSegment[1];

        switch ($this->wsObject->urlSegment[1]) {
            // general images management : like header's logo, invoice logo, etc...
            case 'general':
                return $this->manageGeneralImages();
                break;
            // normal images management : like the most entity images (categories, manufacturers..)...
            case 'categories':
            case 'manufacturers':
            case 'suppliers':
            case 'stores':
                switch ($this->wsObject->urlSegment[1]) {
                    case 'categories':
                        $directory = _PS_CAT_IMG_DIR_;
                        break;
                    case 'manufacturers':
                        $directory = _PS_MANU_IMG_DIR_;
                        break;
                    case 'suppliers':
                        $directory = _PS_SUPP_IMG_DIR_;
                        break;
                    case 'stores':
                        $directory = _PS_STORE_IMG_DIR_;
                        break;
                }

                return $this->manageDeclinatedImages($directory);
                break;

            // product image management : many image for one entity (product)
            case 'products':
                return $this->manageProductImages();
                break;
            case 'customizations':
                return $this->manageCustomizationImages();
                break;
            // images root node management : many image for one entity (product)
            case '':
                $this->output .= $this->objOutput->getObjectRender()->renderNodeHeader('image_types', []);
                foreach (array_keys($this->imageTypes) as $imageTypeName) {
                    $moreAttr = [
                        'xlink_resource'           => $this->wsObject->wsUrl.$this->wsObject->urlSegment[0].'/'.$imageTypeName,
                        'get'                      => 'true', 'put' => 'false', 'post' => 'false', 'delete' => 'false', 'head' => 'true',
                        'upload_allowed_mimetypes' => implode(', ', $this->acceptedImgMimeTypes),
                    ];
                    $this->output .= $this->objOutput->getObjectRender()->renderNodeHeader($imageTypeName, [], $moreAttr, false);
                }
                $this->output .= $this->objOutput->getObjectRender()->renderNodeFooter('image_types', []);

                return true;
                break;
            default:
                $exception = new WebserviceException(sprintf('Image of type "%s" does not exist', $this->wsObject->urlSegment[1]), [48, 400]);
                throw $exception->setDidYouMean($this->wsObject->urlSegment[1], array_keys($this->imageTypes));
        }
    }

    /**
     * Management of general images
     *
     * @return bool
     *
     * @throws WebserviceException
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function manageGeneralImages()
    {
        $path = '';
        $alternativePath = '';
        switch ($this->wsObject->urlSegment[2]) {
            // Set the image path on display in relation to the header image
            case 'header':
                if (in_array($this->wsObject->method, ['GET', 'HEAD', 'PUT'])) {
                    $path = _PS_IMG_DIR_.Configuration::get('PS_LOGO');
                } else {
                    throw new WebserviceException('This method is not allowed with general image resources.', [49, 405]);
                }
                break;

            // Set the image path on display in relation to the mail image
            case 'mail':
                if (in_array($this->wsObject->method, ['GET', 'HEAD', 'PUT'])) {
                    $path = _PS_IMG_DIR_.Configuration::get('PS_LOGO_MAIL');
                    $alternativePath = _PS_IMG_DIR_.Configuration::get('PS_LOGO');
                } else {
                    throw new WebserviceException('This method is not allowed with general image resources.', [50, 405]);
                }
                break;

            // Set the image path on display in relation to the invoice image
            case 'invoice':
                if (in_array($this->wsObject->method, ['GET', 'HEAD', 'PUT'])) {
                    $path = _PS_IMG_DIR_.Configuration::get('PS_LOGO_INVOICE');
                    $alternativePath = _PS_IMG_DIR_.Configuration::get('PS_LOGO');
                } else {
                    throw new WebserviceException('This method is not allowed with general image resources.', [51, 405]);
                }
                break;

            // Set the image path on display in relation to the icon store image
            case 'store_icon':
                if (in_array($this->wsObject->method, ['GET', 'HEAD', 'PUT'])) {
                    $path = _PS_IMG_DIR_.Configuration::get('PS_STORES_ICON');
                    $this->imgExtension = 'gif';
                } else {
                    throw new WebserviceException('This method is not allowed with general image resources.', [52, 405]);
                }
                break;

            // List the general image types
            case '':
                $this->output .= $this->objOutput->getObjectRender()->renderNodeHeader('general_image_types', []);
                foreach (array_keys($this->imageTypes['general']) as $generalImageTypeName) {
                    $moreAttr = [
                        'xlink_resource'           => $this->wsObject->wsUrl.$this->wsObject->urlSegment[0].'/'.$this->wsObject->urlSegment[1].'/'.$generalImageTypeName,
                        'get'                      => 'true', 'put' => 'true', 'post' => 'false', 'delete' => 'false', 'head' => 'true',
                        'upload_allowed_mimetypes' => implode(', ', $this->acceptedImgMimeTypes),
                    ];
                    $this->output .= $this->objOutput->getObjectRender()->renderNodeHeader($generalImageTypeName, [], $moreAttr, false);
                }
                $this->output .= $this->objOutput->getObjectRender()->renderNodeFooter('general_image_types', []);

                return true;
                break;

            // If the image type does not exist...
            default:
                $exception = new WebserviceException(sprintf('General image of type "%s" does not exist', $this->wsObject->urlSegment[2]), [53, 400]);
                throw $exception->setDidYouMean($this->wsObject->urlSegment[2], array_keys($this->imageTypes['general']));
        }
        // The general image type is valid, now we try to do action in relation to the method
        switch ($this->wsObject->method) {
            case 'GET':
            case 'HEAD':
                $this->imgToDisplay = ($path != '' && file_exists($path) && is_file($path)) ? $path : $alternativePath;

                return true;
                break;
            case 'PUT':

                if ($this->writePostedImageOnDisk($path, null, null)) {
                    if ($this->wsObject->urlSegment[2] == 'header') {
                        $logoName = Configuration::get('PS_LOGO') ? Configuration::get('PS_LOGO') : 'logo.jpg';
                        list($width, $height, $type, $attr) = getimagesize(_PS_IMG_DIR_.$logoName);
                        Configuration::updateValue('SHOP_LOGO_WIDTH', (int) round($width));
                        Configuration::updateValue('SHOP_LOGO_HEIGHT', (int) round($height));
                    }
                    $this->imgToDisplay = $path;

                    return true;
                } else {
                    throw new WebserviceException('Error while copying image to the directory', [54, 400]);
                }
                break;
        }
    }

    /**
     * @param $directory
     * @param $normalImageSizes
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function manageDefaultDeclinatedImages($directory, $normalImageSizes)
    {
        $this->defaultImage = true;
        // Get the language iso code list
        $langList = Language::getIsoIds(true);
        $langs = [];
        $defaultLang = Configuration::get('PS_LANG_DEFAULT');
        foreach ($langList as $lang) {
            if ($lang['id_lang'] == $defaultLang) {
                $defaultLang = $lang['iso_code'];
            }
            $langs[] = $lang['iso_code'];
        }

        // Display list of languages
        if ($this->wsObject->urlSegment[3] == '' && $this->wsObject->method == 'GET') {
            $this->output .= $this->objOutput->getObjectRender()->renderNodeHeader('languages', []);
            foreach ($langList as $lang) {
                $moreAttr = [
                    'xlink_resource'           => $this->wsObject->wsUrl.'images/'.$this->imageType.'/default/'.$lang['iso_code'],
                    'get'                      => 'true', 'put' => 'true', 'post' => 'true', 'delete' => 'true', 'head' => 'true',
                    'upload_allowed_mimetypes' => implode(', ', $this->acceptedImgMimeTypes),
                    'iso'                      => $lang['iso_code'],
                ];
                $this->output .= $this->objOutput->getObjectRender()->renderNodeHeader('language', [], $moreAttr, false);
            }

            $this->output .= $this->objOutput->getObjectRender()->renderNodeFooter('languages', []);

            return true;
        } else {
            $langIso = $this->wsObject->urlSegment[3];
            $imageSize = $this->wsObject->urlSegment[4];
            if ($imageSize != '') {
                $filename = $directory.$langIso.'-default-'.$imageSize.'.jpg';
            } else {
                $filename = $directory.$langIso.'.jpg';
            }
            $filenameExists = file_exists($filename);

            return $this->manageDeclinatedImagesCRUD($filenameExists, $filename, $normalImageSizes, $directory);// @todo : [feature] @see todo#1
        }
    }

    /**
     * @param $directory
     * @param $normalImageSizes
     *
     * @return bool
     * @throws WebserviceException
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function manageListDeclinatedImages($directory, $normalImageSizes)
    {
        // Check if method is allowed
        if ($this->wsObject->method != 'GET') {
            throw new WebserviceException('This method is not allowed for listing category images.', [55, 405]);
        }

        $this->output .= $this->objOutput->getObjectRender()->renderNodeHeader('image_types', []);
        foreach ($normalImageSizes as $imageSize) {
            $this->output .= $this->objOutput->getObjectRender()->renderNodeHeader('image_type', [], ['id' => $imageSize['id_image_type'], 'name' => $imageSize['name'], 'xlink_resource' => $this->wsObject->wsUrl.'image_types/'.$imageSize['id_image_type']], false);
        }
        $this->output .= $this->objOutput->getObjectRender()->renderNodeFooter('image_types', []);
        $this->output .= $this->objOutput->getObjectRender()->renderNodeHeader('images', []);

        if ($this->imageType == 'products') {
            $ids = [];
            $images = Image::getAllImages();
            foreach ($images as $image) {
                $ids[] = $image['id_product'];
            }
            $ids = array_unique($ids, SORT_NUMERIC);
            asort($ids);
            foreach ($ids as $id) {
                $this->output .= $this->objOutput->getObjectRender()->renderNodeHeader('image', [], ['id' => $id, 'xlink_resource' => $this->wsObject->wsUrl.'images/'.$this->imageType.'/'.$id], false);
            }
        } else {
            $nodes = scandir($directory);
            foreach ($nodes as $node) {
                // avoid too much preg_match...
                if ($node != '.' && $node != '..' && $node != '.svn') {
                    if ($this->imageType != 'products') {
                        preg_match('/^(\d+)\.jpg*$/Ui', $node, $matches);
                        if (isset($matches[1])) {
                            $id = $matches[1];
                            $this->output .= $this->objOutput->getObjectRender()->renderNodeHeader('image', [], ['id' => $id, 'xlink_resource' => $this->wsObject->wsUrl.'images/'.$this->imageType.'/'.$id], false);
                        }
                    }
                }
            }
        }
        $this->output .= $this->objOutput->getObjectRender()->renderNodeFooter('images', []);

        return true;
    }

    /**
     * @param $directory
     * @param $normalImageSizes
     *
     * @return bool
     * @throws WebserviceException
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function manageEntityDeclinatedImages($directory, $normalImageSizes)
    {
        $normalImageSizeNames = [];
        foreach ($normalImageSizes as $normalImageSize) {
            $normalImageSizeNames[] = $normalImageSize['name'];
        }
        // If id is detected
        $objectId = $this->wsObject->urlSegment[2];
        if (!Validate::isUnsignedId($objectId)) {
            throw new WebserviceException('The image id is invalid. Please set a valid id or the "default" value', [60, 400]);
        }

        // For the product case
        if ($this->imageType == 'products') {
            // Get available image ids
            $availableImageIds = [];

            // New Behavior
            foreach (Language::getIDs() as $idLang) {
                foreach (Image::getImages($idLang, $objectId) as $image) {
                    $availableImageIds[] = $image['id_image'];
                }
            }
            $availableImageIds = array_unique($availableImageIds, SORT_NUMERIC);

            // If an image id is specified
            if ($this->wsObject->urlSegment[3] != '') {
                if ($this->wsObject->urlSegment[3] == 'bin') {
                    $currentProduct = new Product($objectId);
                    $this->wsObject->urlSegment[3] = $currentProduct->getCoverWs();
                }
                if (!Validate::isUnsignedId($objectId) || !in_array($this->wsObject->urlSegment[3], $availableImageIds)) {
                    throw new WebserviceException('This image id does not exist', [57, 400]);
                } else {
                    // Check for new image system
                    $imageId = $this->wsObject->urlSegment[3];
                    $path = implode('/', str_split((string) $imageId));
                    $imageSize = $this->wsObject->urlSegment[4];

                    if (file_exists($directory.$path.'/'.$imageId.(strlen($this->wsObject->urlSegment[4]) > 0 ? '-'.$this->wsObject->urlSegment[4] : '').'.jpg')) {
                        $filename = $directory.$path.'/'.$imageId.(strlen($this->wsObject->urlSegment[4]) > 0 ? '-'.$this->wsObject->urlSegment[4] : '').'.jpg';
                        $origFilename = $directory.$path.'/'.$imageId.'.jpg';
                    } else {
                        // else old system or not exists

                        $origFilename = $directory.$objectId.'-'.$imageId.'.jpg';
                        $filename = $directory.$objectId.'-'.$imageId.'-'.$imageSize.'.jpg';
                    }
                }
            } // display the list of declinated images
            elseif ($this->wsObject->method == 'GET' || $this->wsObject->method == 'HEAD') {
                if ($availableImageIds) {
                    $this->output .= $this->objOutput->getObjectRender()->renderNodeHeader('image', [], ['id' => $objectId]);
                    foreach ($availableImageIds as $availableImageId) {
                        $this->output .= $this->objOutput->getObjectRender()->renderNodeHeader('declination', [], ['id' => $availableImageId, 'xlink_resource' => $this->wsObject->wsUrl.'images/'.$this->imageType.'/'.$objectId.'/'.$availableImageId], false);
                    }
                    $this->output .= $this->objOutput->getObjectRender()->renderNodeFooter('image', []);
                } else {
                    $this->objOutput->setStatus(404);
                    $this->wsObject->setOutputEnabled(false);
                }
            }
        } // for all other cases
        else {
            $origFilename = $directory.$objectId.'.jpg';
            $imageSize = $this->wsObject->urlSegment[3];
            $filename = $directory.$objectId.'-'.$imageSize.'.jpg';
        }

        // in case of declinated images list of a product is get
        if ($this->output != '') {
            return true;
        } // If a size was given try to display it
        elseif (isset($imageSize) && $imageSize != '') {
            // Check the given size
            if ($this->imageType == 'products' && $imageSize == 'bin') {
                $filename = $directory.$objectId.'-'.$imageId.'.jpg';
            } elseif (!in_array($imageSize, $normalImageSizeNames)) {
                $exception = new WebserviceException('This image size does not exist', [58, 400]);
                throw $exception->setDidYouMean($imageSize, $normalImageSizeNames);
            }
            if (!file_exists($filename)) {
                throw new WebserviceException('This image does not exist on disk', [59, 500]);
            }

            // Display the resized specific image
            $this->imgToDisplay = $filename;

            return true;
        } // Management of the original image (GET, PUT, POST, DELETE)
        elseif (isset($origFilename)) {
            $origFilenameExists = file_exists($origFilename);

            return $this->manageDeclinatedImagesCRUD($origFilenameExists, $origFilename, $normalImageSizes, $directory);
        } else {
            return $this->manageDeclinatedImagesCRUD(false, '', $normalImageSizes, $directory);
        }
    }

    /**
     * Management of normal images (as categories, suppliers, manufacturers and stores)
     *
     * @param string $directory the file path of the root of the images folder type
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function manageDeclinatedImages($directory)
    {
        // Get available image sizes for the current image type
        $normalImageSizes = ImageType::getImagesTypes($this->imageType);
        switch ($this->wsObject->urlSegment[2]) {
            // Match the default images
            case 'default':
                return $this->manageDefaultDeclinatedImages($directory, $normalImageSizes);
                break;
            // Display the list of images
            case '':
                return $this->manageListDeclinatedImages($directory, $normalImageSizes);
                break;
            default:
                return $this->manageEntityDeclinatedImages($directory, $normalImageSizes);
                break;
        }
    }

    /**
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function manageProductImages()
    {
        $this->manageDeclinatedImages(_PS_PROD_IMG_DIR_);
    }

    /**
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function getCustomizations()
    {
        $customizations = [];
        if (!$results = Db::getInstance()->executeS(
            '
			SELECT DISTINCT c.`id_customization`
			FROM `'._DB_PREFIX_.'customization` c
			NATURAL JOIN `'._DB_PREFIX_.'customization_field` cf
			WHERE c.`id_cart` = '.(int) $this->wsObject->urlSegment[2].'
			AND type = 0'
        )
        ) {
            return [];
        }
        foreach ($results as $result) {
            $customizations[] = $result['id_customization'];
        }

        return $customizations;
    }

    /**
     * @return bool
     * @throws WebserviceException
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function manageCustomizationImages()
    {
        $normalImageSizes = ImageType::getImagesTypes($this->imageType);
        if (empty($this->wsObject->urlSegment[2])) {
            $results = Db::getInstance()->executeS('SELECT DISTINCT `id_cart` FROM `'._DB_PREFIX_.'customization`');
            $ids = [];
            foreach ($results as $result) {
                $ids[] = $result['id_cart'];
            }
            asort($ids);
            $this->output .= $this->objOutput->getObjectRender()->renderNodeHeader('carts', []);
            foreach ($ids as $id) {
                $this->output .= $this->objOutput->getObjectRender()->renderNodeHeader('cart', [], ['id' => $id, 'xlink_resource' => $this->wsObject->wsUrl.'images/'.$this->imageType.'/'.$id], false);
            }
            $this->output .= $this->objOutput->getObjectRender()->renderNodeFooter('carts', []);

            return true;
        } elseif (empty($this->wsObject->urlSegment[3])) {
            $this->output .= $this->objOutput->getObjectRender()->renderNodeHeader('customizations', []);
            $customizations = $this->getCustomizations();
            foreach ($customizations as $id) {
                $this->output .= $this->objOutput->getObjectRender()->renderNodeHeader('customization', [], ['id' => $id, 'xlink_resource' => $this->wsObject->wsUrl.'images/'.$this->imageType.'/'.$id], false);
            }
            $this->output .= $this->objOutput->getObjectRender()->renderNodeFooter('customizations', []);

            return true;
        } elseif (empty($this->wsObject->urlSegment[4])) {
            if ($this->wsObject->method == 'GET') {
                $results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                    (new DbQuery())
                        ->select('*')
                        ->from('customized_data')
                        ->where('`id_customization` = '.(int) $this->wsObject->urlSegment[3])
                        ->where('`type` = 0')
                );

                $this->output .= $this->objOutput->getObjectRender()->renderNodeHeader('images', []);
                foreach ($results as $result) {
                    $this->output .= $this->objOutput->getObjectRender()->renderNodeHeader('image', [], ['id' => $result['index'], 'xlink_resource' => $this->wsObject->wsUrl.'images/'.$this->imageType.'/'.$result['index']], false);
                }
                $this->output .= $this->objOutput->getObjectRender()->renderNodeFooter('images', []);

                return true;
            }
        } else {
            if ($this->wsObject->method == 'GET') {
                $results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                    (new DbQuery())
                        ->select('*')
                        ->from('customized_data')
                        ->where('`id_customization` = '.(int) $this->wsObject->urlSegment[3])
                        ->where('`index` = '.(int) $this->wsObject->urlSegment[4])
                );
                if (empty($results[0]) || empty($results[0]['value'])) {
                    throw new WebserviceException('This image does not exist on disk', [61, 500]);
                }
                $this->imgToDisplay = _PS_UPLOAD_DIR_.$results[0]['value'];

                return true;
            }
            if ($this->wsObject->method == 'POST') {
                $customizations = $this->getCustomizations();
                if (!in_array((int) $this->wsObject->urlSegment[3], $customizations)) {
                    throw new WebserviceException('Customization does not exist', [61, 500]);
                }
                $results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                    (new DbQuery())
                        ->select('`id_customization_field`')
                        ->from('customization_field')
                        ->where('`id_customization_field` = '.(int) $this->wsObject->urlSegment[4])
                        ->where('`type` = 0')
                );
                if (empty($results)) {
                    throw new WebserviceException('Customization field does not exist.', [61, 500]);
                }
                $results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                    (new DbQuery())
                        ->select('*')
                        ->from('customized_data')
                        ->where('`id_customization` = '.(int) $this->wsObject->urlSegment[3])
                        ->where('`index` = '.(int) $this->wsObject->urlSegment[4])
                        ->where('`type` = 0')
                );
                if (!empty($results)) { // customization field exists and has no value
                    throw new WebserviceException('Customization field already have a value, please use PUT method.', [61, 500]);
                }

                return $this->manageDeclinatedImagesCRUD(false, '', $normalImageSizes, _PS_UPLOAD_DIR_);
            }
            $results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('*')
                    ->from('customized_data')
                    ->where('`id_customization` = '.(int) $this->wsObject->urlSegment[3])
                    ->where('`index` = '.(int) $this->wsObject->urlSegment[4])
            );
            if (empty($results[0]) || empty($results[0]['value'])) {
                throw new WebserviceException('This image does not exist on disk', [61, 500]);
            }
            $this->imgToDisplay = _PS_UPLOAD_DIR_.$results[0]['value'];
            $filenameExists = file_exists($this->imgToDisplay);

            return $this->manageDeclinatedImagesCRUD($filenameExists, $this->imgToDisplay, $normalImageSizes, _PS_UPLOAD_DIR_);
        }
    }

    /**
     * Management of normal images CRUD
     *
     * @param bool   $filenameExists if the filename exists
     * @param string $filename       the image path
     * @param array  $imageSizes     The
     * @param string $directory
     *
     * @return bool
     *
     * @throws WebserviceException
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function manageDeclinatedImagesCRUD($filenameExists, $filename, $imageSizes, $directory)
    {
        switch ($this->wsObject->method) {
            // Display the image
            case 'GET':
            case 'HEAD':
                if ($filenameExists) {
                    $this->imgToDisplay = $filename;
                } else {
                    throw new WebserviceException('This image does not exist on disk', [61, 500]);
                }
                break;
            // Modify the image
            case 'PUT':
                if ($filenameExists) {
                    if ($this->writePostedImageOnDisk($filename, null, null, $imageSizes, $directory)) {
                        $this->imgToDisplay = $filename;

                        return true;
                    } else {
                        throw new WebserviceException('Unable to save this image.', [62, 500]);
                    }
                } else {
                    throw new WebserviceException('This image does not exist on disk', [63, 500]);
                }
                break;
            // Delete the image
            case 'DELETE':
                // Delete products image in DB
                if ($this->imageType == 'products') {
                    $image = new Image((int) $this->wsObject->urlSegment[3]);

                    return $image->delete();
                } elseif ($filenameExists) {
                    if (in_array($this->imageType, ['categories', 'manufacturers', 'suppliers', 'stores'])) {
                        /** @var ObjectModel $object */
                        $imageClass = $this->wsObject->resourceList[$this->imageType]['class'];
                        $object = new $imageClass((int) $this->wsObject->urlSegment[2]);

                        return $object->deleteImage(true);
                    } else {
                        return $this->deleteImageOnDisk($filename, $imageSizes, $directory);
                    }
                } else {
                    throw new WebserviceException('This image does not exist on disk', [64, 500]);
                }
                break;
            // Add the image
            case 'POST':

                if ($filenameExists) {
                    throw new WebserviceException('This image already exists. To modify it, please use the PUT method', [65, 400]);
                } else {
                    if ($this->writePostedImageOnDisk($filename, null, null, $imageSizes, $directory)) {
                        return true;
                    } else {
                        throw new WebserviceException('Unable to save this image', [66, 500]);
                    }
                }
                break;
            default:
                throw new WebserviceException('This method is not allowed', [67, 405]);
        }
    }

    /**
     *    Delete the image on disk
     *
     * @param string $filePath   the image file path
     * @param array  $imageTypes The different sizes
     * @param string $parentPath The parent path
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function deleteImageOnDisk($filePath, $imageTypes = null, $parentPath = null)
    {
        $this->wsObject->setOutputEnabled(false);
        if (file_exists($filePath)) {
            // delete image on disk
            @unlink($filePath);
            // Delete declinated image if needed
            if ($imageTypes) {
                foreach ($imageTypes as $imageType) {
                    if ($this->defaultImage) { // @todo products images too !!
                        $declinationPath = $parentPath.$this->wsObject->urlSegment[3].'-default-'.$imageType['name'].'.jpg';
                    } else {
                        $declinationPath = $parentPath.$this->wsObject->urlSegment[2].'-'.$imageType['name'].'.jpg';
                    }
                    if (!@unlink($declinationPath)) {
                        $this->objOutput->setStatus(204);

                        return false;
                    }
                }
            }

            return true;
        } else {
            $this->objOutput->setStatus(204);

            return false;
        }
    }

    /**
     * Write the image on disk
     *
     * @param string $basePath
     * @param string $newPath
     * @param int    $destWidth
     * @param int    $destHeight
     * @param array  $imageTypes
     * @param string $parentPath
     *
     * @return string
     *
     * @throws WebserviceException
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function writeImageOnDisk($basePath, $newPath, $destWidth = null, $destHeight = null, $imageTypes = null, $parentPath = null)
    {
        list($sourceWidth, $sourceHeight, $type, $attr) = getimagesize($basePath);
        if (!$sourceWidth) {
            throw new WebserviceException('Image width was null', [68, 400]);
        }
        if ($destWidth == null) {
            $destWidth = $sourceWidth;
        }
        if ($destHeight == null) {
            $destHeight = $sourceHeight;
        }
        switch ($type) {
            case 1:
                $sourceImage = imagecreatefromgif($basePath);
                break;
            case 3:
                $sourceImage = imagecreatefrompng($basePath);
                break;
            case 2:
            default:
                $sourceImage = imagecreatefromjpeg($basePath);
                break;
        }

        $widthDiff = $destWidth / $sourceWidth;
        $heightDiff = $destHeight / $sourceHeight;

        if ($widthDiff > 1 && $heightDiff > 1) {
            $nextWidth = $sourceWidth;
            $nextHeight = $sourceHeight;
        } else {
            if ((int) (Configuration::get('PS_IMAGE_GENERATION_METHOD')) == 2 || ((int) (Configuration::get('PS_IMAGE_GENERATION_METHOD')) == 0 && $widthDiff > $heightDiff)) {
                $nextHeight = $destHeight;
                $nextWidth = (int) (($sourceWidth * $nextHeight) / $sourceHeight);
                $destWidth = ((int) (Configuration::get('PS_IMAGE_GENERATION_METHOD')) == 0 ? $destWidth : $nextWidth);
            } else {
                $nextWidth = $destWidth;
                $nextHeight = (int) ($sourceHeight * $destWidth / $sourceWidth);
                $destHeight = ((int) (Configuration::get('PS_IMAGE_GENERATION_METHOD')) == 0 ? $destHeight : $nextHeight);
            }
        }

        $borderWidth = (int) (($destWidth - $nextWidth) / 2);
        $borderHeight = (int) (($destHeight - $nextHeight) / 2);

        // Build the image
        if (!($destImage = imagecreatetruecolor($destWidth, $destHeight)) ||
            !($white = imagecolorallocate($destImage, 255, 255, 255)) ||
            !imagefill($destImage, 0, 0, $white) ||
            !imagecopyresampled($destImage, $sourceImage, $borderWidth, $borderHeight, 0, 0, $nextWidth, $nextHeight, $sourceWidth, $sourceHeight) ||
            !imagecolortransparent($destImage, $white)
        ) {
            throw new WebserviceException(sprintf('Unable to build the image "%s".', str_replace(_PS_ROOT_DIR_, '[SHOP_ROOT_DIR]', $newPath)), [69, 500]);
        }

        // Write it on disk

        switch ($this->imgExtension) {
            case 'gif':
                $imaged = imagegif($destImage, $newPath);
                break;
            case 'png':
                $quality = (Configuration::get('PS_PNG_QUALITY') === false ? 7 : Configuration::get('PS_PNG_QUALITY'));
                $imaged = imagepng($destImage, $newPath, (int) $quality);
                break;
            case 'jpeg':
            default:
                $quality = (Configuration::get('PS_JPEG_QUALITY') === false ? 90 : Configuration::get('PS_JPEG_QUALITY'));
                $imaged = imagejpeg($destImage, $newPath, (int) $quality);
                if ($this->wsObject->urlSegment[1] == 'customizations') {
                    // write smaller image in case of customization image
                    $productPictureWidth = (int) Configuration::get('PS_PRODUCT_PICTURE_WIDTH');
                    $productPictureHeight = (int) Configuration::get('PS_PRODUCT_PICTURE_HEIGHT');
                    if (!ImageManager::resize($newPath, $newPath.'_small', $productPictureWidth, $productPictureHeight)) {
                        $this->errors[] = Tools::displayError('An error occurred during the image upload process.');
                    }
                }
                break;
        }
        imagedestroy($destImage);
        if (!$imaged) {
            throw new WebserviceException(sprintf('Unable to write the image "%s".', str_replace(_PS_ROOT_DIR_, '[SHOP_ROOT_DIR]', $newPath)), [70, 500]);
        }

        // Write image declinations if present
        if ($imageTypes) {
            foreach ($imageTypes as $imageType) {
                if ($this->defaultImage) {
                    $declinationPath = $parentPath.$this->wsObject->urlSegment[3].'-default-'.$imageType['name'].'.jpg';
                } else {
                    if ($this->imageType == 'products') {
                        $declinationPath = $parentPath.chunk_split($this->wsObject->urlSegment[3], 1, '/').$this->wsObject->urlSegment[3].'-'.$imageType['name'].'.jpg';
                    } else {
                        $declinationPath = $parentPath.$this->wsObject->urlSegment[2].'-'.$imageType['name'].'.jpg';
                    }
                }
                if (!$this->writeImageOnDisk($basePath, $declinationPath, $imageType['width'], $imageType['height'])) {
                    throw new WebserviceException(sprintf('Unable to save the declination "%s" of this image.', $imageType['name']), [71, 500]);
                }
            }
        }

        Hook::exec('actionWatermark', ['id_image' => $this->wsObject->urlSegment[3], 'id_product' => $this->wsObject->urlSegment[2]]);

        return $newPath;
    }

    /**
     * Write the posted image on disk
     *
     * @param string $receptionPath
     * @param int    $destWidth
     * @param int    $destHeight
     * @param array  $imageTypes
     * @param string $parentPath
     *
     * @return bool
     *
     * @throws WebserviceException
     */
    protected function writePostedImageOnDisk($receptionPath, $destWidth = null, $destHeight = null, $imageTypes = null, $parentPath = null)
    {
        $imgMaxUploadSize = Tools::getMaxUploadSize();
        if ($this->wsObject->method == 'PUT') {
            if (isset($_FILES['image']['tmp_name']) && $_FILES['image']['tmp_name']) {
                $file = $_FILES['image'];
                if ($file['size'] > $imgMaxUploadSize) {
                    throw new WebserviceException(sprintf('The image size is too large (maximum allowed is %d KB)', ($imgMaxUploadSize / 1000)), [72, 400]);
                }
                // Get mime content type
                $mimeType = false;
                if (Tools::isCallable('finfo_open')) {
                    $const = defined('FILEINFO_MIME_TYPE') ? FILEINFO_MIME_TYPE : FILEINFO_MIME;
                    $finfo = finfo_open($const);
                    $mimeType = finfo_file($finfo, $file['tmp_name']);
                    finfo_close($finfo);
                } elseif (Tools::isCallable('mime_content_type')) {
                    $mimeType = mime_content_type($file['tmp_name']);
                } elseif (Tools::isCallable('exec')) {
                    $mimeType = trim(exec('file -b --mime-type '.escapeshellarg($file['tmp_name'])));
                }
                if (empty($mimeType) || $mimeType == 'regular file') {
                    $mimeType = $file['type'];
                }
                if (($pos = strpos($mimeType, ';')) !== false) {
                    $mimeType = substr($mimeType, 0, $pos);
                }

                // Check mime content type
                if (!$mimeType || !in_array($mimeType, $this->acceptedImgMimeTypes)) {
                    throw new WebserviceException('This type of image format is not recognized, allowed formats are: '.implode('", "', $this->acceptedImgMimeTypes), [73, 400]);
                } // Check error while uploading
                elseif ($file['error']) {
                    throw new WebserviceException('Error while uploading image. Please change your server\'s settings', [74, 400]);
                }

                // Try to copy image file to a temporary file
                if (!($tmpName = tempnam(_PS_TMP_IMG_DIR_, 'PS')) || !move_uploaded_file($_FILES['image']['tmp_name'], $tmpName)) {
                    throw new WebserviceException('Error while copying image to the temporary directory', [75, 400]);
                } // Try to copy image file to the image directory
                else {
                    $result = $this->writeImageOnDisk($tmpName, $receptionPath, $destWidth, $destHeight, $imageTypes, $parentPath);
                }

                @unlink($tmpName);

                return $result;
            } else {
                throw new WebserviceException('Please set an "image" parameter with image data for value', [76, 400]);
            }
        } elseif ($this->wsObject->method == 'POST') {
            if (isset($_FILES['image']['tmp_name']) && $_FILES['image']['tmp_name']) {
                $file = $_FILES['image'];
                if ($file['size'] > $imgMaxUploadSize) {
                    throw new WebserviceException(sprintf('The image size is too large (maximum allowed is %d KB)', ($imgMaxUploadSize / 1000)), [72, 400]);
                }
                require_once(_PS_CORE_DIR_.'/images.inc.php');
                if ($error = ImageManager::validateUpload($file)) {
                    throw new WebserviceException('Image upload error : '.$error, [76, 400]);
                }

                if (isset($file['tmp_name']) && $file['tmp_name'] != null) {
                    if ($this->imageType == 'products') {
                        $product = new Product((int) $this->wsObject->urlSegment[2]);
                        if (!Validate::isLoadedObject($product)) {
                            throw new WebserviceException('Product '.(int) $this->wsObject->urlSegment[2].' does not exist', [76, 400]);
                        }
                        $image = new Image();
                        $image->id_product = (int) ($product->id);
                        $image->position = Image::getHighestPosition($product->id) + 1;

                        if (!Image::getCover((int) $product->id)) {
                            $image->cover = 1;
                        } else {
                            $image->cover = 0;
                        }

                        if (!$image->add()) {
                            throw new WebserviceException('Error while creating image', [76, 400]);
                        }
                        if (!Validate::isLoadedObject($product)) {
                            throw new WebserviceException('Product '.(int) $this->wsObject->urlSegment[2].' does not exist', [76, 400]);
                        }
                        Hook::exec('updateProduct', ['id_product' => (int) $this->wsObject->urlSegment[2]]);
                    }

                    // copy image
                    if (!isset($file['tmp_name'])) {
                        return false;
                    }
                    if ($error = ImageManager::validateUpload($file, $imgMaxUploadSize)) {
                        throw new WebserviceException('Bad image : '.$error, [76, 400]);
                    }

                    if ($this->imageType == 'products') {
                        $image = new Image($image->id);
                        if (!(Configuration::get('PS_OLD_FILESYSTEM') && file_exists(_PS_PROD_IMG_DIR_.$product->id.'-'.$image->id.'.jpg'))) {
                            $image->createImgFolder();
                        }

                        if (!($tmpName = tempnam(_PS_TMP_IMG_DIR_, 'PS')) || !move_uploaded_file($file['tmp_name'], $tmpName)) {
                            throw new WebserviceException('An error occurred during the image upload', [76, 400]);
                        } elseif (!ImageManager::resize($tmpName, _PS_PROD_IMG_DIR_.$image->getExistingImgPath().'.'.$image->image_format)) {
                            throw new WebserviceException('An error occurred while copying image', [76, 400]);
                        } else {
                            $imagesTypes = ImageType::getImagesTypes('products');
                            foreach ($imagesTypes as $imageType) {
                                if (!ImageManager::resize($tmpName, _PS_PROD_IMG_DIR_.$image->getExistingImgPath().'-'.stripslashes($imageType['name']).'.'.$image->image_format, $imageType['width'], $imageType['height'], $image->image_format)) {
                                    $this->_errors[] = Tools::displayError('An error occurred while copying image:').' '.stripslashes($imageType['name']);
                                }
                            }
                        }
                        @unlink($tmpName);
                        $this->imgToDisplay = _PS_PROD_IMG_DIR_.$image->getExistingImgPath().'.'.$image->image_format;
                        $this->objOutput->setFieldsToDisplay('full');
                        $this->output = $this->objOutput->renderEntity($image, 1);
                        $imageContent = ['sqlId' => 'content', 'value' => base64_encode(file_get_contents($this->imgToDisplay)), 'encode' => 'base64'];
                        $this->output .= $this->objOutput->objectRender->renderField($imageContent);
                    } elseif (in_array($this->imageType, ['categories', 'manufacturers', 'suppliers', 'stores'])) {
                        if (!($tmpName = tempnam(_PS_TMP_IMG_DIR_, 'PS')) || !move_uploaded_file($file['tmp_name'], $tmpName)) {
                            throw new WebserviceException('An error occurred during the image upload', [76, 400]);
                        } elseif (!ImageManager::resize($tmpName, $receptionPath)) {
                            throw new WebserviceException('An error occurred while copying image', [76, 400]);
                        }
                        $imagesTypes = ImageType::getImagesTypes($this->imageType);
                        foreach ($imagesTypes as $imageType) {
                            if (!ImageManager::resize($tmpName, $parentPath.$this->wsObject->urlSegment[2].'-'.stripslashes($imageType['name']).'.jpg', $imageType['width'], $imageType['height'])) {
                                $this->_errors[] = Tools::displayError('An error occurred while copying image:').' '.stripslashes($imageType['name']);
                            }
                        }
                        @unlink(_PS_TMP_IMG_DIR_.$tmpName);
                        $this->imgToDisplay = $receptionPath;
                    } elseif ($this->imageType == 'customizations') {
                        $filename = md5(uniqid(rand(), true));
                        $this->imgToDisplay = _PS_UPLOAD_DIR_.$filename;
                        if (!($tmpName = tempnam(_PS_TMP_IMG_DIR_, 'PS')) || !move_uploaded_file($file['tmp_name'], $tmpName)) {
                            throw new WebserviceException('An error occurred during the image upload', [76, 400]);
                        } elseif (!ImageManager::resize($tmpName, $this->imgToDisplay)) {
                            throw new WebserviceException('An error occurred while copying image', [76, 400]);
                        }
                        $productPictureWidth = (int) Configuration::get('PS_PRODUCT_PICTURE_WIDTH');
                        $productPictureHeight = (int) Configuration::get('PS_PRODUCT_PICTURE_HEIGHT');
                        if (!ImageManager::resize($this->imgToDisplay, $this->imgToDisplay.'_small', $productPictureWidth, $productPictureHeight)) {
                            throw new WebserviceException('An error occurred while resizing image', [76, 400]);
                        }
                        @unlink(_PS_TMP_IMG_DIR_.$tmpName);

                        if (!Db::getInstance()->insert(
                            'customized_data',
                            [
                                'id_customization' => (int) $this->wsObject->urlSegment[3],
                                'type'             => 0,
                                'index'            => (int) $this->wsObject->urlSegment[4],
                                'value'            => pSQL($filename),
                            ]
                        )) {
                            return false;
                        }
                    }

                    return true;
                }
            }
        } else {
            throw new WebserviceException('Method '.$this->wsObject->method.' is not allowed for an image resource', [77, 405]);
        }
    }
}
