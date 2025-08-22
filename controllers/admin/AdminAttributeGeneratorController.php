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

@ini_set('max_execution_time', 3600);

/**
 * Class AdminAttributeGeneratorControllerCore
 *
 * @property Product|null $object
 */
class AdminAttributeGeneratorControllerCore extends AdminController
{
    /**
     * @var array
     */
    protected $combinations = [];

    /**
     * @var Product
     */
    protected $product;

    /**
     * AdminAttributeGeneratorControllerCore constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'product_attribute';
        $this->className = 'Product';
        $this->multishop_context_group = false;

        parent::__construct();
    }

    /**
     * @param array $list
     *
     * @return array
     */
    protected static function createCombinations($list)
    {
        if (count($list) <= 1) {
            return count($list) ? array_map(function ($v) {
                return ([$v]);
            }, $list[0]) : $list;
        }
        $res = [];
        $first = array_pop($list);
        foreach ($first as $attribute) {
            $tab = static::createCombinations($list);
            foreach ($tab as $toAdd) {
                $res[] = is_array($toAdd) ? array_merge($toAdd, [$attribute]) : [$toAdd, $attribute];
            }
        }

        return $res;
    }

    /**
     * @param int $idProduct
     * @param array $tab
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected static function setAttributesImpacts($idProduct, array $tab)
    {
        $res = true;
        $conn = Db::getInstance();
        foreach ($tab as $group) {
            foreach ($group as $attributeId) {
                $attributeId = (int)$attributeId;
                $res = $conn->insert('attribute_impact', [
                    'id_product' => (int)$idProduct,
                    'id_attribute' => (int)$attributeId,
                    'weight' => Tools::getNumberValue('weight_impact_'.$attributeId),
                    'price' => Tools::getNumberValue('price_impact_'.$attributeId),
                    'width' => Tools::getNumberValue('width_impact_'.$attributeId),
                    'height' => Tools::getNumberValue('height_impact_'.$attributeId),
                    'depth' => Tools::getNumberValue('depth_impact_'.$attributeId),
                ], false, true, Db::ON_DUPLICATE_KEY) && $res;
            }
        }
        return $res;
    }

    /**
     * @throws PrestaShopException
     */
    public function setMedia()
    {
        parent::setMedia();
        $this->addJS(_PS_JS_DIR_.'admin/attributes.js');
    }

    /**
     * @return void
     */
    public function initProcess()
    {
        if (!defined('PS_MASS_PRODUCT_CREATION')) {
            define('PS_MASS_PRODUCT_CREATION', true);
        }

        if (Tools::isSubmit('generate')) {
            if ($this->hasEditPermission()) {
                $this->action = 'generate';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to add this.');
            }
        }
        parent::initProcess();
    }

    /**
     * @throws PrestaShopException
     */
    public function postProcess()
    {
        $this->product = new Product(Tools::getIntValue('id_product'));
        $this->product->loadStockData();
        parent::postProcess();
    }

    /**
     * @throws PrestaShopException
     */
    public function processGenerate()
    {
        if (!is_array(Tools::getValue('options'))) {
            $this->errors[] = Tools::displayError('Please select at least one attribute.');
        } else {
            $tab = array_values(Tools::getValue('options'));
            if (count($tab) && Validate::isLoadedObject($this->product)) {
                static::setAttributesImpacts($this->product->id, $tab);
                $this->combinations = array_values(static::createCombinations($tab));


                // @since 1.5.0
                if ($this->product->depends_on_stock == 0) {
                    $attributes = Product::getProductAttributesIds($this->product->id, true);
                    foreach ($attributes as $attribute) {
                        StockAvailable::removeProductFromStockAvailable($this->product->id, $attribute['id_product_attribute'], $this->context->shop);
                    }
                }

                SpecificPriceRule::disableAnyApplication();

                $this->product->deleteProductAttributes();
                $values = [];
                $baseReference = Tools::getValueRaw('reference');
                $counter = Tools::nextAvailableReferenceCounter($baseReference);
                foreach ($this->combinations as $combination) {
                    $reference = $baseReference ? ($baseReference . '_' . $counter) : '';
                    $values[] = $this->addAttribute($combination, $reference);
                    $counter++;
                }
                $this->product->generateMultipleCombinations($values, $this->combinations);

                // Reset cached default attribute for the product and get a new one
                Product::getDefaultAttribute($this->product->id, 0, true);
                Product::updateDefaultAttribute($this->product->id);

                // @since 1.5.0
                if ($this->product->depends_on_stock == 0) {
                    $attributes = Product::getProductAttributesIds($this->product->id, true);
                    $quantity = Tools::getIntValue('quantity');
                    foreach ($attributes as $attribute) {
                        if (Shop::getContext() == Shop::CONTEXT_ALL) {
                            $shopsList = Shop::getShops();
                            if (is_array($shopsList)) {
                                foreach ($shopsList as $currentShop) {
                                    if (isset($currentShop['id_shop']) && (int) $currentShop['id_shop'] > 0) {
                                        StockAvailable::setQuantity($this->product->id, (int) $attribute['id_product_attribute'], $quantity, (int) $currentShop['id_shop']);
                                    }
                                }
                            }
                        } else {
                            StockAvailable::setQuantity($this->product->id, (int) $attribute['id_product_attribute'], $quantity);
                        }
                    }
                } else {
                    StockAvailable::synchronize($this->product->id);
                }

                SpecificPriceRule::enableAnyApplication();
                SpecificPriceRule::applyAllRules([(int) $this->product->id]);

                Tools::redirectAdmin($this->context->link->getAdminLink('AdminProducts').'&id_product='.Tools::getIntValue('id_product').'&updateproduct&key_tab=Combinations&conf=4');
            } else {
                $this->errors[] = Tools::displayError('Unable to initialize these parameters. A combination is missing or an object cannot be loaded.');
            }
        }
    }

    /**
     * @param int|null $tabId
     * @param array|null $tabs
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function initBreadcrumbs($tabId = null, $tabs = null)
    {
        $this->display = 'generator';

        return parent::initBreadcrumbs();
    }

    /**
     * @throws PrestaShopException
     */
    public function initContent()
    {
        if (!Combination::isFeatureActive()) {
            $url = '<a href="index.php?tab=AdminPerformance&token='.Tools::getAdminTokenLite('AdminPerformance').'#featuresDetachables">'.$this->l('Performance').'</a>';
            $this->displayWarning(sprintf($this->l('This feature has been disabled. You can activate it here: %s.'), $url));

            return;
        }

        // Init toolbar
        $this->initPageHeaderToolbar();
        $this->initGroupTable();

        $attributes = ProductAttribute::getAttributes($this->context->language->id, true);
        $attributeJs = [];

        foreach ($attributes as $attribute) {
            $attributeJs[$attribute['id_attribute_group']][$attribute['id_attribute']] = $attribute['name'];
        }

        $attributeGroups = AttributeGroup::getAttributesGroups($this->context->language->id);
        $this->product = new Product(Tools::getIntValue('id_product'));

        $images = Image::getImages($this->context->language->id, $this->product->id);
        $formattedImages = [];
        foreach ($images as $img) {
            $formattedImages[] = [
                'id'  => $img['id_image'],
                'url' => $this->context->link->getImageLink($this->product->link_rewrite[$this->context->language->id] ?? '', $img['id_image'], ImageType::getFormattedName('small')),
            ];
        }
        $groupsAffectingView = [];
        foreach ($attributeGroups as $group) {
            $groupsAffectingView[$group['id_attribute_group']] = (bool)($group['affects_product_view'] ?? false);
        }

        $this->context->smarty->assign(
            [
                'tax_rates'                 => $this->product->getTaxesRate(),
                'generate'                  => isset($_POST['generate']) && !count($this->errors),
                'combinations_size'         => count($this->combinations),
                'product_name'              => $this->product->name[$this->context->language->id] ?? '',
                'product_reference'         => $this->product->reference,
                'url_generator'             => static::$currentIndex.'&id_product='.Tools::getIntValue('id_product').'&attributegenerator&token='.Tools::getValue('token'),
                'attribute_groups'          => $attributeGroups,
                'attribute_js'              => $attributeJs,
                'images'                    => $formattedImages,
                'groups_affecting_view'     => $groupsAffectingView,
                'toolbar_btn'               => $this->toolbar_btn,
                'toolbar_scroll'            => true,
                'show_page_header_toolbar'  => $this->show_page_header_toolbar,
                'page_header_toolbar_title' => $this->page_header_toolbar_title,
                'page_header_toolbar_btn'   => $this->page_header_toolbar_btn,
            ]
        );
    }

    /**
     * @throws PrestaShopException
     */
    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();

        $this->page_header_toolbar_title = $this->l('Attributes generator', null, null, false);
        $this->page_header_toolbar_btn['back'] = [
            'href' => $this->context->link->getAdminLink('AdminProducts').'&id_product='.Tools::getIntValue('id_product').'&updateproduct&key_tab=Combinations',
            'desc' => $this->l('Back to the product', null, null, false),
        ];
    }

    /**
     * @throws PrestaShopException
     */
    public function initGroupTable()
    {
        $combinationsGroups = $this->product->getAttributesGroups($this->context->language->id);
        $attributes = [];
        $impacts = Product::getAttributesImpacts($this->product->id);
        foreach ($combinationsGroups as $combination) {
            $target = &$attributes[$combination['id_attribute_group']][$combination['id_attribute']];
            $target = $combination;
            $target['price'] = $impacts[$combination['id_attribute']]['price'] ?? 0;
            $target['weight'] = $impacts[$combination['id_attribute']]['weight'] ?? 0;
            $target['width'] = $impacts[$combination['id_attribute']]['width'] ?? 0;
            $target['height'] = $impacts[$combination['id_attribute']]['height'] ?? 0;
            $target['depth'] = $impacts[$combination['id_attribute']]['depth'] ?? 0;
        }
        $this->context->smarty->assign([
            'currency_sign'     => $this->context->currency->sign,
            'currency_decimals' => $this->context->currency->decimals,
            'weight_unit'       => Configuration::get('PS_WEIGHT_UNIT'),
            'dimension_unit'     => Configuration::get('PS_DIMENSION_UNIT'),
            'attributes'        => $attributes,
        ]);
    }

    /**
     * @param array $attributes
     * @param string $reference
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function addAttribute($attributes, $reference)
    {
        $productId = (int)$this->product->id;
        if ($productId) {
            $price = 0.0;
            $weight = 0.0;
            $width = 0.0;
            $height = 0.0;
            $depth = 0.0;
            foreach ($attributes as $attributeId) {
                $attributeId = (int)$attributeId;
                $price += Tools::getNumberValue('price_impact_'.$attributeId);
                $weight += Tools::getNumberValue('weight_impact_'.$attributeId);
                $width += Tools::getNumberValue('width_impact_'.$attributeId);
                $height += Tools::getNumberValue('height_impact_'.$attributeId);
                $depth += Tools::getNumberValue('depth_impact_'.$attributeId);
            }
            return [
                'id_product'     => $productId,
                'price'          => $price,
                'weight'         => $weight,
                'width'          => $width,
                'height'         => $height,
                'depth'          => $depth,
                'ecotax'         => 0,
                'quantity'       => Tools::getIntValue('quantity'),
                'reference'      => pSQL($reference),
                'default_on'     => 0,
                'available_date' => '0000-00-00',
            ];
        }

        return [];
    }
}
