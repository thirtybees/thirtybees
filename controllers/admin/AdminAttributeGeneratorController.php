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

@ini_set('max_execution_time', 3600);

/**
 * Class AdminAttributeGeneratorControllerCore
 *
 * @since 1.0.0
 */
class AdminAttributeGeneratorControllerCore extends AdminController
{
    protected $combinations = [];

    /** @var Product */
    protected $product;

    /**
     * AdminAttributeGeneratorControllerCore constructor.
     *
     * @since 1.0.0
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
     * @param $list
     *
     * @return array
     *
     * @since 1.0.0
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
     * @param int   $idProduct
     * @param array $tab
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected static function setAttributesImpacts($idProduct, array $tab)
    {
        $attributes = [];
        foreach ($tab as $group) {
            foreach ($group as $attribute) {
                $price = priceval(
                    Tools::getValue('price_impact_'.(int) $attribute)
                );
                $weight = Tools::getValue('weight_impact_'.(int) $attribute);
                $attributes[] = '('.(int) $idProduct.', '.(int) $attribute.', '.(float) $price.', '.(float) $weight.')';
            }
        }

        return Db::getInstance()->execute(
            '
		INSERT INTO `'._DB_PREFIX_.'attribute_impact` (`id_product`, `id_attribute`, `price`, `weight`)
		VALUES '.implode(',', $attributes).'
		ON DUPLICATE KEY UPDATE `price` = VALUES(price), `weight` = VALUES(weight)'
        );
    }

    /**
     * @since 1.0.0
     */
    public function setMedia()
    {
        parent::setMedia();
        $this->addJS(_PS_JS_DIR_.'admin/attributes.js');
    }

    /**
     * @since 1.0.0
     */
    public function initProcess()
    {
        if (!defined('PS_MASS_PRODUCT_CREATION')) {
            define('PS_MASS_PRODUCT_CREATION', true);
        }

        if (Tools::isSubmit('generate')) {
            if ($this->tabAccess['edit'] === '1') {
                $this->action = 'generate';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to add this.');
            }
        }
        parent::initProcess();
    }

    /**
     *
     *
     * @since 1.0.0
     */
    public function postProcess()
    {
        $this->product = new Product((int) Tools::getValue('id_product'));
        $this->product->loadStockData();
        parent::postProcess();
    }

    /**
     * @since 1.0.0
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
                $values = array_values(array_map([$this, 'addAttribute'], $this->combinations));

                // @since 1.5.0
                if ($this->product->depends_on_stock == 0) {
                    $attributes = Product::getProductAttributesIds($this->product->id, true);
                    foreach ($attributes as $attribute) {
                        StockAvailable::removeProductFromStockAvailable($this->product->id, $attribute['id_product_attribute'], $this->context->shop);
                    }
                }

                SpecificPriceRule::disableAnyApplication();

                $this->product->deleteProductAttributes();
                $this->product->generateMultipleCombinations($values, $this->combinations);

                // Reset cached default attribute for the product and get a new one
                Product::getDefaultAttribute($this->product->id, 0, true);
                Product::updateDefaultAttribute($this->product->id);

                // @since 1.5.0
                if ($this->product->depends_on_stock == 0) {
                    $attributes = Product::getProductAttributesIds($this->product->id, true);
                    $quantity = (int) Tools::getValue('quantity');
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

                Tools::redirectAdmin($this->context->link->getAdminLink('AdminProducts').'&id_product='.(int) Tools::getValue('id_product').'&updateproduct&key_tab=Combinations&conf=4');
            } else {
                $this->errors[] = Tools::displayError('Unable to initialize these parameters. A combination is missing or an object cannot be loaded.');
            }
        }
    }

    /**
     * @param int|null   $tabId
     * @param array|null $tabs
     *
     * @since 1.0.0
     */
    public function initBreadcrumbs($tabId = null, $tabs = null)
    {
        $this->display = 'generator';

        return parent::initBreadcrumbs();
    }

    /**
     * @since 1.0.0
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

        $attributes = Attribute::getAttributes($this->context->language->id, true);
        $attributeJs = [];

        foreach ($attributes as $k => $attribute) {
            $attributeJs[$attribute['id_attribute_group']][$attribute['id_attribute']] = $attribute['name'];
        }

        $attributeGroups = AttributeGroup::getAttributesGroups($this->context->language->id);
        $this->product = new Product((int) Tools::getValue('id_product'));

        $this->context->smarty->assign(
            [
                'tax_rates'                 => $this->product->getTaxesRate(),
                'generate'                  => isset($_POST['generate']) && !count($this->errors),
                'combinations_size'         => count($this->combinations),
                'product_name'              => $this->product->name[$this->context->language->id],
                'product_reference'         => $this->product->reference,
                'url_generator'             => static::$currentIndex.'&id_product='.(int) Tools::getValue('id_product').'&attributegenerator&token='.Tools::getValue('token'),
                'attribute_groups'          => $attributeGroups,
                'attribute_js'              => $attributeJs,
                'toolbar_btn'               => $this->toolbar_btn,
                'toolbar_scroll'            => true,
                'show_page_header_toolbar'  => $this->show_page_header_toolbar,
                'page_header_toolbar_title' => $this->page_header_toolbar_title,
                'page_header_toolbar_btn'   => $this->page_header_toolbar_btn,
            ]
        );
    }

    /**
     * @since 1.0.0
     */
    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();

        $this->page_header_toolbar_title = $this->l('Attributes generator', null, null, false);
        $this->page_header_toolbar_btn['back'] = [
            'href' => $this->context->link->getAdminLink('AdminProducts').'&id_product='.(int) Tools::getValue('id_product').'&updateproduct&key_tab=Combinations',
            'desc' => $this->l('Back to the product', null, null, false),
        ];
    }

    /**
     * @since 1.0.0
     */
    public function initGroupTable()
    {
        $combinationsGroups = $this->product->getAttributesGroups($this->context->language->id);
        $attributes = [];
        $impacts = Product::getAttributesImpacts($this->product->id);
        foreach ($combinationsGroups as &$combination) {
            $target = &$attributes[$combination['id_attribute_group']][$combination['id_attribute']];
            $target = $combination;
            if (isset($impacts[$combination['id_attribute']])) {
                $target['price'] = $impacts[$combination['id_attribute']]['price'];
                $target['weight'] = $impacts[$combination['id_attribute']]['weight'];
            }
        }
        $this->context->smarty->assign([
            'currency_sign'     => $this->context->currency->sign,
            'currency_decimals' => $this->context->currency->decimals,
            'weight_unit'       => Configuration::get('PS_WEIGHT_UNIT'),
            'attributes'        => $attributes,
        ]);
    }

    /**
     * @param array $attributes
     * @param float $price
     * @param int   $weight
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function addAttribute($attributes, $price = 0.0000, $weight = 0)
    {
        foreach ($attributes as $attribute) {
            $price += priceval(
                Tools::getValue('price_impact_'.(int) $attribute)
            );
            $weight += (float) Tools::getValue('weight_impact_'.(int) $attribute);
        }
        if ($this->product->id) {
            return [
                'id_product'     => (int) $this->product->id,
                'price'          => priceval($price),
                'weight'         => (float) $weight,
                'ecotax'         => 0,
                'quantity'       => (int) Tools::getValue('quantity'),
                'reference'      => pSQL($_POST['reference']),
                'default_on'     => 0,
                'available_date' => '0000-00-00',
            ];
        }

        return [];
    }
}
