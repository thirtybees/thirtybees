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

use Thirtybees\Core\View\Model\ProductViewModel;

/**
 * Class ProductControllerCore
 */
class ProductControllerCore extends FrontController
{
    /**
     * @var string
     */
    public $php_self = 'product';

    /**
     * @var ProductViewModel
     */
    protected $product;

    /**
     * @var Category
     */
    protected $category;

    /**
     * Set media
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function setMedia()
    {
        parent::setMedia();
        if (is_array($this->errors) && count($this->errors)) {
            return;
        }

        if (!$this->useMobileTheme()) {
            $this->addCSS(_THEME_CSS_DIR_.'product.css');
            $this->addCSS(_THEME_CSS_DIR_.'print.css', 'print');
            $this->addJqueryPlugin(['fancybox', 'idTabs', 'scrollTo', 'serialScroll', 'bxslider']);
            $this->addJS(
                [
                    _PS_JS_DIR_.'front/product-page.js',
                    _THEME_JS_DIR_.'tools.js',  // retro compat themes 1.5
                    _THEME_JS_DIR_.'product.js',
                ]
            );
        } else {
            $this->addJqueryPlugin(['scrollTo', 'serialScroll']);
            $this->addJS(
                [
                    _THEME_JS_DIR_.'tools.js',  // retro compat themes 1.5
                    _THEME_MOBILE_JS_DIR_.'product.js',
                    _THEME_MOBILE_JS_DIR_.'jquery.touch-gallery.js',
                ]
            );
        }

        if (Configuration::get('PS_DISPLAY_JQZOOM') == 1) {
            $this->addJqueryPlugin('jqzoom');
        }
    }

    /**
     * Initialize product controller
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     * @see FrontController::init()
     */
    public function init()
    {
        parent::init();

        $productId = Tools::getIntValue('id_product');
        $combinationId = $this->resolveCombinationId($productId);
        $this->product = new ProductViewModel(
            $productId,
            $combinationId,
            (int)$this->context->language->id,
            (int)$this->context->shop->id
        );

        if (!Validate::isLoadedObject($this->product)) {
            header('HTTP/1.1 404 Not Found');
            header('Status: 404 Not Found');
            $this->errors[] = Tools::displayError('Product not found');
        } else {
            $this->canonicalRedirection();
            /*
             * If the product is associated to the shop
             * and is active or not active but preview mode (need token + file_exists)
             * allow showing the product
             * In all the others cases => 404 "Product is no longer available"
             */
            if (!$this->product->isAssociatedToShop() || !$this->product->active) {
                if (Tools::getValue('adtoken') == Tools::getAdminToken('AdminProducts'.(int) Tab::getIdFromClassName('AdminProducts').Tools::getIntValue('id_employee')) && $this->product->isAssociatedToShop()) {
                    // If the product is not active, it's the admin preview mode
                    $this->context->smarty->assign('adminActionDisplay', true);
                } else {
                    $this->context->smarty->assign('adminActionDisplay', false);
                    if (!$this->product->id_product_redirected || $this->product->id_product_redirected == $this->product->id) {
                        $this->product->redirect_type = '404';
                    }

                    switch ($this->product->redirect_type) {
                        case '301':
                            header('HTTP/1.1 301 Moved Permanently');
                            header('Location: '.$this->context->link->getProductLink($this->product->id_product_redirected));
                            exit;
                        case '302':
                            header('HTTP/1.1 302 Moved Temporarily');
                            header('Cache-Control: no-cache');
                            header('Location: '.$this->context->link->getProductLink($this->product->id_product_redirected));
                            exit;
                        case '404':
                        default:
                            header('HTTP/1.1 404 Not Found');
                            header('Status: 404 Not Found');
                            $this->errors[] = Tools::displayError('This product is no longer available.');
                            break;
                    }
                }
            } elseif (!$this->product->checkAccess(isset($this->context->customer->id) && $this->context->customer->id ? (int) $this->context->customer->id : 0)) {
                header('HTTP/1.1 403 Forbidden');
                header('Status: 403 Forbidden');
                $this->errors[] = Tools::displayError('You do not have access to this product.');
            } else {
                // Load category
                $idCategory = false;
                $referer = Tools::getHttpReferer();
                if ($referer &&
                    $referer == Tools::secureReferrer($referer) && // Assure us the previous page was one of the shop
                    preg_match('~^.*(?<!\/content)\/([0-9]+)\-(.*[^\.])|(.*)id_(category|product)=([0-9]+)(.*)$~', $referer, $regs)
                ) {
                    // If the previous page was a category and is a parent category of the product use this category as parent category
                    $idObject = false;
                    if (isset($regs[1]) && is_numeric($regs[1])) {
                        $idObject = (int) $regs[1];
                    } elseif (isset($regs[5]) && is_numeric($regs[5])) {
                        $idObject = (int) $regs[5];
                    }
                    if ($idObject) {
                        $referers = [$referer, urldecode($referer)];
                        if (in_array($this->context->link->getCategoryLink($idObject), $referers)) {
                            $idCategory = (int) $idObject;
                        } elseif (isset($this->context->cookie->last_visited_category) && (int) $this->context->cookie->last_visited_category && in_array($this->context->link->getProductLink($idObject), $referers)) {
                            $idCategory = (int) $this->context->cookie->last_visited_category;
                        }
                    }
                }
                if (!$idCategory || !Category::inShopStatic($idCategory, $this->context->shop) || !Product::idIsOnCategoryId((int) $this->product->id, ['0' => ['id_category' => $idCategory]])) {
                    $idCategory = (int) $this->product->id_category_default;
                }
                $this->category = new Category((int) $idCategory, (int) $this->context->cookie->id_lang);
                if (isset($this->context->cookie) && isset($this->category->id_category) && !(Module::isInstalled('blockcategories') && Module::isEnabled('blockcategories'))) {
                    $this->context->cookie->last_visited_category = (int) $this->category->id_category;
                }
            }
        }
    }

    /**
     * Canonical redirection
     *
     * @param string $canonicalUrl
     *
     * @return void
     *
     * @throws PrestaShopException
     */
    public function canonicalRedirection($canonicalUrl = '')
    {
        if (Tools::getValue('live_edit')) {
            return;
        }
        if (Validate::isLoadedObject($this->product)) {
            parent::canonicalRedirection($this->context->link->getProductLink($this->product));
        }
    }

    /**
     * Assign template vars related to page content
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();

        if (!$this->errors) {
            if (Pack::isPack((int) $this->product->id) && !Pack::isInStock((int) $this->product->id)) {
                $this->product->quantity = 0;
            }

            $this->product->description = $this->transformDescriptionWithImg($this->product->description);

            // Assign to the template the id of the virtual product. "0" if the product is not downloadable.
            $this->context->smarty->assign('virtual', ProductDownload::getIdFromIdProduct((int) $this->product->id));

            $this->context->smarty->assign('customizationFormTarget', Tools::safeOutput(urldecode($_SERVER['REQUEST_URI'])));

            if (Tools::isSubmit('submitCustomizedDatas')) {
                // If cart has not been saved, we need to do it so that customization fields can have an id_cart
                // We check that the cookie exists first to avoid ghost carts
                if (!$this->context->cart->id && isset($_COOKIE[$this->context->cookie->getName()])) {
                    $this->context->cart->add();
                    $this->context->cookie->id_cart = (int) $this->context->cart->id;
                }
                $this->pictureUpload();
                $this->textRecord();
                $this->formTargetFormat();
            } elseif (Tools::getIsset('deletePicture') && !$this->context->cart->deleteCustomizationToProduct($this->product->id, Tools::getValue('deletePicture'))) {
                $this->errors[] = Tools::displayError('An error occurred while deleting the selected picture.');
            }

            $pictures = [];
            $textFields = [];
            if ($this->product->customizable) {
                $files = $this->context->cart->getProductCustomization($this->product->id, Product::CUSTOMIZE_FILE, true);
                foreach ($files as $file) {
                    $pictures['pictures_'.$this->product->id.'_'.$file['index']] = $file['value'];
                }

                $texts = $this->context->cart->getProductCustomization($this->product->id, Product::CUSTOMIZE_TEXTFIELD, true);

                foreach ($texts as $text_field) {
                    $textFields['textFields_'.$this->product->id.'_'.$text_field['index']] = str_replace('<br />', "\n", $text_field['value']);
                }
            }

            $this->context->smarty->assign(
                [
                    'pictures'   => $pictures,
                    'textFields' => $textFields,
                ]
            );

            $customizationFields = $this->product->customizable
                ? $this->product->getCustomizationFields($this->context->language->id)
                : false;

            // Assign template vars related to the category + execute hooks related to the category
            $this->assignCategory();
            // Assign template vars related to the price and tax
            $this->assignPriceAndTax();

            // Assign template vars related to the images
            $this->assignImages();
            // Assign attribute groups to the template
            $this->assignAttributesGroups();

            // Assign attributes combinations to the template
            $this->assignAttributesCombinations();

            // Pack management
            $packItems = Pack::isPack($this->product->id) ? Pack::getItemTable($this->product->id, $this->context->language->id, true) : [];
            $this->context->smarty->assign('packItems', $packItems);
            $this->context->smarty->assign('packs', Pack::getPacksTable($this->product->id, $this->context->language->id, true, 1));

            if (isset($this->category->id) && $this->category->id) {
                $returnLink = Tools::safeOutput($this->context->link->getCategoryLink($this->category));
            } else {
                $returnLink = 'javascript: history.back();';
            }

            $accessories = $this->product->getAccessories($this->context->language->id);
            if ($this->product->cache_is_pack || is_array($accessories) && ($accessories)) {
                $this->context->controller->addCSS(_THEME_CSS_DIR_.'product_list.css');
            }
            if ($this->product->customizable) {
                $customizationDatas = $this->context->cart->getProductCustomization($this->product->id, null, true);
            }

            $this->context->smarty->assign(
                [
                    'stock_management'         => Configuration::get('PS_STOCK_MANAGEMENT'),
                    'customizationRequired'    => $this->product->isCustomizationRequired(),
                    'customizationFields'      => $customizationFields,
                    'id_customization'         => empty($customizationDatas) ? null : $customizationDatas[0]['id_customization'],
                    'accessories'              => $accessories,
                    'return_link'              => $returnLink,
                    'product'                  => $this->product,
                    'product_manufacturer'     => new Manufacturer((int) $this->product->id_manufacturer, $this->context->language->id),
                    'token'                    => Tools::getToken(false),
                    'features'                 => $this->product->getFrontFeatures($this->context->language->id),
                    'attachments'              => (($this->product->cache_has_attachments) ? $this->product->getAttachments($this->context->language->id) : []),
                    'allow_oosp'               => $this->product->isAvailableWhenOutOfStock((int) $this->product->out_of_stock),
                    'last_qties'               => (int) Configuration::get('PS_LAST_QTIES'),
                    'HOOK_EXTRA_LEFT'          => Hook::displayHook('displayLeftColumnProduct'),
                    'HOOK_EXTRA_RIGHT'         => Hook::displayHook('displayRightColumnProduct'),
                    'HOOK_PRODUCT_OOS'         => Hook::displayHook('actionProductOutOfStock', ['product' => $this->product]),
                    'HOOK_PRODUCT_ACTIONS'     => Hook::displayHook('displayProductButtons', ['product' => $this->product]),
                    'HOOK_PRODUCT_TAB'         => Hook::displayHook('displayProductTab', ['product' => $this->product]),
                    'HOOK_PRODUCT_TAB_CONTENT' => Hook::displayHook('displayProductTabContent', ['product' => $this->product]),
                    'HOOK_PRODUCT_CONTENT'     => Hook::displayHook('displayProductContent', ['product' => $this->product]),
                    'display_qties'            => (int) Configuration::get('PS_DISPLAY_QTIES'),
                    'display_ht'               => !Tax::excludeTaxeOption(),
                    'jqZoomEnabled'            => Configuration::get('PS_DISPLAY_JQZOOM'),
                    'ENT_NOQUOTES'             => ENT_NOQUOTES,
                    'outOfStockAllowed'        => (int) Configuration::get('PS_ORDER_OUT_OF_STOCK'),
                    'errors'                   => $this->errors,
                    'body_classes'             => [
                        $this->php_self.'-'.$this->product->id,
                        $this->php_self.'-'.$this->product->link_rewrite,
                        'category-'.(isset($this->category) ? $this->category->id : ''),
                        'category-'.(isset($this->category) ? $this->category->getFieldByLang('link_rewrite') : ''),
                    ],
                    'display_discount_price'   => Configuration::get('PS_DISPLAY_DISCOUNT_PRICE'),
                    'show_condition'           => Configuration::get('PS_SHOW_CONDITION'),
                ]
            );
        }
        $this->setTemplate(_PS_THEME_DIR_.'product.tpl');
    }

    /**
     * Transform description w/ image
     *
     * @param string $desc
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    protected function transformDescriptionWithImg($desc)
    {
        $desc = (string)$desc;
        $reg = '/\[img\-([0-9]+)\-(left|right)\-([a-zA-Z0-9-_]+)\]/';
        while (preg_match($reg, $desc, $matches)) {
            $linkLmg = $this->context->link->getImageLink($this->product->link_rewrite, (int)$matches[1], $matches[3]);
            $class = $matches[2] == 'left' ? 'class="imageFloatLeft"' : 'class="imageFloatRight"';
            $htmlImg = '<img src="'.$linkLmg.'" alt="" '.$class.'/>';
            $desc = str_replace($matches[0], $htmlImg, $desc);
        }

        return $desc;
    }

    /**
     * Picture upload
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function pictureUpload()
    {
        if (!$fieldIds = $this->product->getCustomizationFieldIds()) {
            return false;
        }
        $authorizedFileFields = [];
        foreach ($fieldIds as $fieldId) {
            if ($fieldId['type'] == Product::CUSTOMIZE_FILE) {
                $authorizedFileFields[(int) $fieldId['id_customization_field']] = 'file'.(int) $fieldId['id_customization_field'];
            }
        }
        $indexes = array_flip($authorizedFileFields);
        foreach ($_FILES as $fieldName => $file) {
            if (in_array($fieldName, $authorizedFileFields) && isset($file['tmp_name']) && !empty($file['tmp_name'])) {
                $fileName = md5(uniqid(rand(), true));
                if ($error = ImageManager::validateUpload($file, (int) Configuration::get('PS_PRODUCT_PICTURE_MAX_SIZE'))) {
                    $this->errors[] = $error;
                }

                $productPictureWidth = (int) Configuration::get('PS_PRODUCT_PICTURE_WIDTH');
                $productPictureHeight = (int) Configuration::get('PS_PRODUCT_PICTURE_HEIGHT');
                $tmpName = tempnam(_PS_TMP_IMG_DIR_, 'PS');
                if ($error || (!$tmpName || !move_uploaded_file($file['tmp_name'], $tmpName))) {
                    return false;
                }
                /* Original file */
                if (!ImageManager::resize($tmpName, _PS_UPLOAD_DIR_.$fileName)) {
                    $this->errors[] = Tools::displayError('An error occurred during the image upload process.');
                } /* A smaller one */
                elseif (!ImageManager::resize($tmpName, _PS_UPLOAD_DIR_.$fileName.'_small', $productPictureWidth, $productPictureHeight)) {
                    $this->errors[] = Tools::displayError('An error occurred during the image upload process.');
                } elseif (!chmod(_PS_UPLOAD_DIR_.$fileName, 0777) || !chmod(_PS_UPLOAD_DIR_.$fileName.'_small', 0777)) {
                    $this->errors[] = Tools::displayError('An error occurred during the image upload process.');
                } else {
                    $this->context->cart->addPictureToProduct($this->product->id, $indexes[$fieldName], Product::CUSTOMIZE_FILE, $fileName);
                }
                unlink($tmpName);
            }
        }

        return true;
    }

    /**
     * Text record
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function textRecord()
    {
        if (!$fieldIds = $this->product->getCustomizationFieldIds()) {
            return;
        }

        $authorizedTextFields = [];
        foreach ($fieldIds as $fieldId) {
            if ($fieldId['type'] == Product::CUSTOMIZE_TEXTFIELD) {
                $authorizedTextFields[(int) $fieldId['id_customization_field']] = 'textField'.(int) $fieldId['id_customization_field'];
            }
        }

        $indexes = array_flip($authorizedTextFields);
        foreach ($_POST as $fieldName => $value) {
            if (in_array($fieldName, $authorizedTextFields) && $value != '') {
                if (!Validate::isMessage($value)) {
                    $this->errors[] = Tools::displayError('Invalid message');
                } else {
                    $this->context->cart->addTextFieldToProduct($this->product->id, $indexes[$fieldName], Product::CUSTOMIZE_TEXTFIELD, $value);
                }
            } elseif (in_array($fieldName, $authorizedTextFields) && $value == '') {
                $this->context->cart->deleteCustomizationToProduct((int) $this->product->id, $indexes[$fieldName]);
            }
        }
    }

    /**
     * From target format
     *
     * @return void
     */
    protected function formTargetFormat()
    {
        $customizationFormTarget = Tools::safeOutput(urldecode($_SERVER['REQUEST_URI']));
        foreach ($_GET as $field => $value) {
            if (strncmp($field, 'group_', 6) == 0) {
                $customizationFormTarget = preg_replace('/&group_([[:digit:]]+)=([[:digit:]]+)/', '', $customizationFormTarget);
            }
        }
        if (isset($_POST['quantityBackup'])) {
            $this->context->smarty->assign('quantityBackup', (int) $_POST['quantityBackup']);
        }
        $this->context->smarty->assign('customizationFormTarget', $customizationFormTarget);
    }

    /**
     * Assign template vars related to category
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function assignCategory()
    {
        // Assign category to the template
        if (Validate::isLoadedObject($this->category) && $this->category->inShop() && $this->category->isAssociatedToShop()) {
            $path = Tools::getPath($this->category->id, $this->product->name, true);
        } elseif (Category::inShopStatic($this->product->id_category_default, $this->context->shop)) {
            $this->category = new Category((int) $this->product->id_category_default, (int) $this->context->language->id);
            if (Validate::isLoadedObject($this->category) && $this->category->active && $this->category->isAssociatedToShop()) {
                $path = Tools::getPath((int) $this->product->id_category_default, $this->product->name);
            }
        }
        if (!isset($path) || !$path) {
            $path = Tools::getPath((int) $this->context->shop->id_category, $this->product->name);
        }

        if (Validate::isLoadedObject($this->category)) {
            $subCategories = $this->category->getSubCategories($this->context->language->id, true);

            $this->context->smarty->assign(
                [
                    'path'                 => $path,
                    'category'             => $this->category,
                    'subCategories'        => $subCategories,
                    'id_category_current'  => (int) $this->category->id,
                    'id_category_parent'   => (int) $this->category->id_parent,
                    'return_category_name' => Tools::safeOutput($this->category->getFieldByLang('name')),
                    'categories'           => Category::getHomeCategories($this->context->language->id, true, (int) $this->context->shop->id),
                ]
            );
        }
        $this->context->smarty->assign(['HOOK_PRODUCT_FOOTER' => Hook::displayHook('displayFooterProduct', ['product' => $this->product, 'category' => $this->category])]);
    }

    /**
     * Assign price and tax to the template
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function assignPriceAndTax()
    {
        $idCustomer = (isset($this->context->customer) ? (int) $this->context->customer->id : 0);
        $idGroup = (int) Group::getCurrent()->id;
        $idCountry = $idCustomer ? (int) Customer::getCurrentCountry($idCustomer) : (int) Tools::getCountry();

        $groupReduction = GroupReduction::getValueForProduct($this->product->id, $idGroup);
        if ($groupReduction === false) {
            $groupReduction = Group::getReduction((int) $this->context->cookie->id_customer) / 100;
        }

        $currency = $this->context->currency;
        $idCurrency = (int) $currency->id;
        $idProduct = (int) $this->product->id;
        $idShop = $this->context->shop->id;
        $decimals = $currency->getDisplayPrecision();

        // Tax
        $tax = (float) $this->product->getTaxesRate(new Address((int) $this->context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')}));
        $this->context->smarty->assign('tax_rate', $tax);

        $productPriceWithoutEcoTax = $this->product->getPrice() - $this->product->ecotax;

        $ecotaxRate = (float) Tax::getProductEcotaxRate($this->context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')});
        if (Product::$_taxCalculationMethod == PS_TAX_INC && (int) Configuration::get('PS_TAX')) {
            $ecotaxTaxAmount = Tools::ps_round($this->product->ecotax * (1 + $ecotaxRate / 100), $decimals);
        } else {
            $ecotaxTaxAmount = Tools::ps_round($this->product->ecotax, $decimals);
        }

        $quantityDiscounts = SpecificPrice::getQuantityDiscounts($idProduct, $idShop, $idCurrency, $idCountry, $idGroup, null, true, (int) $this->context->customer->id);
        foreach ($quantityDiscounts as &$quantityDiscount) {
            if (!isset($quantityDiscount['base_price'])) {
                $quantityDiscount['base_price'] = 0;
            }
            if ($quantityDiscount['id_product_attribute']) {
                $quantityDiscount['base_price'] = $this->product->getPrice(Product::$_taxCalculationMethod === PS_TAX_INC, $quantityDiscount['id_product_attribute']);
                $combination = new Combination((int) $quantityDiscount['id_product_attribute']);
                $attributes = $combination->getAttributesName((int) $this->context->language->id);
                $quantityDiscount['attributes'] = implode(' - ', array_column($attributes, 'name'));
            } else {
                $quantityDiscount['base_price'] = $this->product->getPrice(Product::$_taxCalculationMethod == PS_TAX_INC);
            }
            if ((int) $quantityDiscount['id_currency'] == 0 && $quantityDiscount['reduction_type'] == 'amount') {
                $quantityDiscount['reduction'] = Tools::convertPriceFull($quantityDiscount['reduction'], null, $currency);
            }
        }

        $address = new Address($this->context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')});
        $this->context->smarty->assign(
            [
                'quantity_discounts'         => $this->formatQuantityDiscounts($quantityDiscounts, null, (float) $tax, $ecotaxTaxAmount),
                'ecotax_tax_inc'             => $ecotaxTaxAmount,
                'ecotax_tax_exc'             => Tools::ps_round($this->product->ecotax, $decimals),
                'ecotaxTax_rate'             => $ecotaxRate,
                'productPriceWithoutEcoTax'  => $productPriceWithoutEcoTax,
                'group_reduction'            => $groupReduction,
                'no_tax'                     => Tax::excludeTaxeOption() || !$this->product->getTaxesRate($address),
                'ecotax'                     => (!count($this->errors) && $this->product->ecotax > 0 ? Tools::convertPrice((float) $this->product->ecotax) : 0),
                'tax_enabled'                => Configuration::get('PS_TAX') && !Configuration::get('AEUC_LABEL_TAX_INC_EXC'),
                'customer_group_without_tax' => Group::getPriceDisplayMethod($this->context->customer->id_default_group),
            ]
        );
    }

    /**
     * Format quantity discounts
     *
     * @param array $specificPrices
     * @param float|null $price unused
     * @param float $taxRate
     * @param float $ecotaxAmount
     *
     * @return array
     */
    protected function formatQuantityDiscounts($specificPrices, $price, $taxRate, $ecotaxAmount)
    {
        foreach ($specificPrices as $key => &$row) {
            $row['quantity'] = &$row['from_quantity'];
            if ($row['price'] >= 0) {
                // The price may be directly set

                $currentPrice = (!$row['reduction_tax'] ?
                    $row['price'] :
                    round(
                        $row['price'] * (1 + $taxRate / 100),
                        _TB_PRICE_DATABASE_PRECISION_
                    )
                ) + (float) $ecotaxAmount;

                if ($row['reduction_type'] == 'amount') {
                    $currentPrice -= $row['reduction_tax'] ?
                        $row['reduction'] :
                        round(
                            $row['reduction'] / (1 + $taxRate / 100),
                            _TB_PRICE_DATABASE_PRECISION_
                        );
                    $row['reduction_with_tax'] = $row['reduction_tax'] ?
                        $row['reduction'] :
                        round(
                            $row['reduction'] / (1 + $taxRate / 100),
                            _TB_PRICE_DATABASE_PRECISION_
                        );
                } else {
                    $currentPrice = round(
                        $currentPrice * (1 - $row['reduction']),
                        _TB_PRICE_DATABASE_PRECISION_
                    );
                }

                $row['real_value'] = $row['base_price'] > 0 ? $row['base_price'] - $currentPrice : $currentPrice;
            } else {
                if ($row['reduction_type'] == 'amount') {
                    if (Product::$_taxCalculationMethod == PS_TAX_INC) {
                        $row['real_value'] = $row['reduction_tax'] == 1 ?
                            $row['reduction'] :
                            round(
                                $row['reduction'] * (1 + $taxRate / 100),
                                _TB_PRICE_DATABASE_PRECISION_
                            );
                    } else {
                        $row['real_value'] = $row['reduction_tax'] == 0 ?
                            $row['reduction'] :
                            round(
                                $row['reduction'] / (1 + $taxRate / 100),
                                _TB_PRICE_DATABASE_PRECISION_
                            );
                    }
                    $row['reduction_with_tax'] = $row['reduction_tax'] ?
                        $row['reduction'] :
                        $row['reduction'] + round(
                            $row['reduction'] * $taxRate / 100,
                            _TB_PRICE_DATABASE_PRECISION_
                        );
                } else {
                    $row['real_value'] = $row['reduction'] * 100;
                }
            }
            $row['nextQuantity'] = (isset($specificPrices[$key + 1]) ? (int) $specificPrices[$key + 1]['from_quantity'] : -1);
        }

        return $specificPrices;
    }

    /**
     * Assign template vars related to images
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function assignImages()
    {
        $images = $this->product->getImages((int) $this->context->cookie->id_lang);
        $productImages = [];

        if (isset($images[0])) {
            $this->context->smarty->assign('mainImage', $images[0]);
        }
        foreach ($images as $image) {
            if ($image['cover']) {
                $this->context->smarty->assign('mainImage', $image);
                $cover = $image;
                $cover['id_image'] = $image['id_image'];
                $cover['id_image_only'] = (int) $image['id_image'];
            }
            $productImages[(int) $image['id_image']] = $image;
        }

        if (!isset($cover)) {
            if (isset($images[0])) {
                $cover = $images[0];
                $cover['id_image'] = $images[0]['id_image'];
                $cover['id_image_only'] = (int) $images[0]['id_image'];
            } else {
                $cover = [
                    'id_image' => $this->context->language->iso_code.'-default',
                    'legend'   => 'No picture',
                    'title'    => 'No picture',
                ];
            }
        }
        $size = Image::getSize(ImageType::getFormatedName('large'));
        $this->context->smarty->assign(
            [
                'have_image'  => (isset($cover['id_image']) && (int) $cover['id_image']) ? [(int) $cover['id_image']] : Product::getCover(Tools::getIntValue('id_product')),
                'cover'       => $cover,
                'imgWidth'    => (int) $size['width'],
                'mediumSize'  => Image::getSize(ImageType::getFormatedName('medium')),
                'largeSize'   => Image::getSize(ImageType::getFormatedName('large')),
                'homeSize'    => Image::getSize(ImageType::getFormatedName('home')),
                'cartSize'    => Image::getSize(ImageType::getFormatedName('cart')),
                'col_img_dir' => _PS_COL_IMG_DIR_,
            ]
        );
        if (count($productImages)) {
            $this->context->smarty->assign('images', $productImages);
        }
    }

    /**
     * Assign template vars related to attribute groups and colors
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function assignAttributesGroups()
    {
        $colors = [];
        $groups = [];

        // @todo (RM) should only get groups and not all declination ?
        $attributesGroups = $this->product->getAttributesGroups($this->context->language->id);
        if (is_array($attributesGroups) && $attributesGroups) {
            $combinationImages = $this->product->getCombinationImages($this->context->language->id);
            $combinationPricesSet = [];
            $link = Context::getContext()->link;
            $combinations = [];
            foreach ($attributesGroups as $row) {

                $combinationId = (int)$row['id_product_attribute'];
                $attributeGroupId = (int)$row['id_attribute_group'];
                $attributeId = (int)$row['id_attribute'];

                // Color management
                if (isset($row['is_color_group']) && $row['is_color_group'] && (isset($row['attribute_color']) && $row['attribute_color']) || (file_exists(_PS_COL_IMG_DIR_.$attributeId.'.jpg'))) {
                    $colors[$attributeId]['value'] = $row['attribute_color'];
                    $colors[$attributeId]['name'] = $row['attribute_name'];
                    if (!isset($colors[$attributeId]['attributes_quantity'])) {
                        $colors[$attributeId]['attributes_quantity'] = 0;
                    }
                    $colors[$attributeId]['attributes_quantity'] += (int) $row['quantity'];
                }
                if (!isset($groups[$attributeGroupId])) {
                    $groups[$attributeGroupId] = [
                        'group_name' => $row['group_name'],
                        'name'       => $row['public_group_name'],
                        'group_type' => $row['group_type'],
                        'default'    => -1,
                    ];
                }

                $groups[$attributeGroupId]['attributes'][$attributeId] = $row['attribute_name'];
                if ($row['default_on'] && $groups[$attributeGroupId]['default'] == -1) {
                    $groups[$attributeGroupId]['default'] = (int) $attributeId;
                }
                if (!isset($groups[$attributeGroupId]['attributes_quantity'][$attributeId])) {
                    $groups[$attributeGroupId]['attributes_quantity'][$attributeId] = 0;
                }
                $groups[$attributeGroupId]['attributes_quantity'][$attributeId] += (int) $row['quantity'];

                $combinations[$combinationId]['attributes_values'][$attributeGroupId] = $row['attribute_name'];
                $combinations[$combinationId]['attributes'][] = $attributeId;
                $combinations[$combinationId]['price'] = Tools::convertPriceFull($row['price'], null, $this->context->currency);
                $combinations[$combinationId]['hashUrl'] = $link->getCombinationHashUrl((int)$this->product->id, (int)$row['id_product_attribute']);

                // Call getPriceStatic in order to set $combination_specific_price
                if (!isset($combinationPricesSet[(int) $row['id_product_attribute']])) {
                    Product::getPriceStatic(
                        (int) $this->product->id,
                        false,
                        $row['id_product_attribute'],
                        _TB_PRICE_DATABASE_PRECISION_,
                        null,
                        false,
                        false,
                        1,
                        false,
                        null,
                        null,
                        null,
                        $combinationSpecificPrice
                    );
                    $combinationPricesSet[$combinationId] = true;
                    $combinations[$combinationId]['specific_price'] = $combinationSpecificPrice;
                }
                $combinations[$combinationId]['ecotax'] = (float) $row['ecotax'];
                $combinations[$combinationId]['weight'] = (float) $row['weight'];
                $combinations[$combinationId]['quantity'] = (int) $row['quantity'];
                $combinations[$combinationId]['reference'] = $row['reference'];
                $combinations[$combinationId]['unit_impact'] = Tools::convertPriceFull($row['unit_price_impact'], null, $this->context->currency);
                $combinations[$combinationId]['minimal_quantity'] = $row['minimal_quantity'];
                if ($row['available_date'] != '0000-00-00' && Validate::isDate($row['available_date'])) {
                    $combinations[$combinationId]['available_date'] = $row['available_date'];
                    $combinations[$combinationId]['date_formatted'] = Tools::displayDate($row['available_date']);
                } else {
                    $combinations[$combinationId]['available_date'] = $combinations[$combinationId]['date_formatted'] = '';
                }

                if (!isset($combinationImages[$combinationId][0]['id_image'])) {
                    $combinations[$combinationId]['id_image'] = -1;
                } else {
                    $combinations[$combinationId]['id_image'] = $idImage = (int) $combinationImages[$combinationId][0]['id_image'];
                    if ($row['default_on']) {
                        if (isset($this->context->smarty->tpl_vars['cover']->value)) {
                            $currentCover = $this->context->smarty->tpl_vars['cover']->value;
                        }

                        if (is_array($combinationImages[$combinationId])) {
                            foreach ($combinationImages[$combinationId] as $tmp) {
                                if (isset($currentCover) && $tmp['id_image'] == $currentCover['id_image']) {
                                    $combinations[$combinationId]['id_image'] = $idImage = (int) $tmp['id_image'];
                                    break;
                                }
                            }
                        }

                        if ($idImage > 0) {
                            if (isset($this->context->smarty->tpl_vars['images']->value)) {
                                $productImages = $this->context->smarty->tpl_vars['images']->value;
                            }
                            if (isset($productImages[$idImage]) && is_array($productImages)) {
                                $productImages[$idImage]['cover'] = 1;
                                $this->context->smarty->assign('mainImage', $productImages[$idImage]);
                                if (count($productImages)) {
                                    $this->context->smarty->assign('images', $productImages);
                                }
                            }
                            if (isset($this->context->smarty->tpl_vars['cover']->value)) {
                                $cover = $this->context->smarty->tpl_vars['cover']->value;
                            }
                            if (isset($cover) && is_array($cover) && isset($productImages) && is_array($productImages)) {
                                $productImages[$cover['id_image']]['cover'] = 0;
                                if (isset($productImages[$idImage])) {
                                    $cover = $productImages[$idImage];
                                }
                                $cover['id_image'] = (int) $idImage;
                                $cover['id_image_only'] = (int) $idImage;
                                $this->context->smarty->assign('cover', $cover);
                            }
                        }
                    }
                }
            }

            // wash attributes list (if some attributes are unavailables and if allowed to wash it)
            if (!Product::isAvailableWhenOutOfStock($this->product->out_of_stock) && Configuration::get('PS_DISP_UNAVAILABLE_ATTR') == 0) {
                foreach ($groups as &$group) {
                    foreach ($group['attributes_quantity'] as $key => $quantity) {
                        if ($quantity <= 0) {
                            unset($group['attributes'][$key]);
                        }
                    }
                }

                foreach ($colors as $key => $color) {
                    if ($color['attributes_quantity'] <= 0) {
                        unset($colors[$key]);
                    }
                }
            }
            if (isset($combinations)) {
                foreach ($combinations as $idProductAttribute => $comb) {
                    $attributeList = '';
                    foreach ($comb['attributes'] as $idAttribute) {
                        $attributeList .= '\''.(int) $idAttribute.'\',';
                    }
                    $attributeList = rtrim($attributeList, ',');
                    $combinations[$idProductAttribute]['list'] = $attributeList;
                }
            }

            $this->context->smarty->assign(
                [
                    'groups'            => $groups,
                    'colors'            => (count($colors)) ? $colors : false,
                    'combinations'      => isset($combinations) ? $combinations : [],
                    'combinationImages' => $combinationImages,
                ]
            );
        }
    }

    /**
     * Get and assign attributes combinations informations
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function assignAttributesCombinations()
    {
        $attributesCombinations = Product::getAttributesInformationsByProduct($this->product->id);
        if (is_array($attributesCombinations) && count($attributesCombinations)) {
            foreach ($attributesCombinations as &$ac) {
                foreach ($ac as &$val) {
                    $val = str_replace(Configuration::get('PS_ATTRIBUTE_ANCHOR_SEPARATOR'), '_', Tools::link_rewrite(str_replace([',', '.'], '-', $val)));
                }
            }
        } else {
            $attributesCombinations = [];
        }
        $this->context->smarty->assign(
            [
                'attributesCombinations'     => $attributesCombinations,
                'attribute_anchor_separator' => Configuration::get('PS_ATTRIBUTE_ANCHOR_SEPARATOR'),
            ]
        );
    }

    /**
     * Get Product
     *
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * Get Category
     *
     * @return Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param int $productId
     *
     * @return int
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function resolveCombinationId(int $productId): int
    {
        $combinationId = Tools::getIntValue('combination');
        if ($combinationId) {
            $combinationIds = array_map('intval', array_column(Product::getProductAttributesIds($productId, true), 'id_product_attribute'));
            if (! in_array($combinationId, $combinationIds, true)) {
                Tools::redirect($this->context->link->getProductLink($productId));
            }
        }
        return $combinationId;
    }

    /**
     * @param int $shopId
     * @param int $languageId
     *
     * @return string|null
     * @throws PrestaShopException
     */
    protected function getCurrentPageAlternateUrl(int $shopId, int $languageId)
    {
        if (Validate::isLoadedObject($this->product)) {
            $product = ((int)$this->product->id_shop === $shopId && (int)$this->product->id_lang === $languageId)
                ? $this->product
                : new ProductViewModel((int)$this->product->id, (int)$this->product->getSelectedCombinationId(), $languageId, $shopId);

            if ($product->active) {
                return $this->context->link->getProductLink($this->product->id, null, null, null, $languageId, $shopId, $this->product->getSelectedCombinationId());
            }
        }
        return null;
    }


}
