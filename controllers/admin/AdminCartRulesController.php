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
 * Class AdminCartRulesControllerCore
 *
 * @since 1.0.0
 */
class AdminCartRulesControllerCore extends AdminController
{
    /**
     * AdminCartRulesControllerCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'cart_rule';
        $this->className = 'CartRule';
        $this->lang = true;
        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->_orderWay = 'DESC';

        $this->bulk_actions = ['delete' => ['text' => $this->l('Delete selected'), 'icon' => 'icon-trash', 'confirm' => $this->l('Delete selected items?')]];

        $this->fields_list = [
            'id_cart_rule' => ['title' => $this->l('ID'), 'align' => 'center', 'class' => 'fixed-width-xs'],
            'name'         => ['title' => $this->l('Name')],
            'priority'     => ['title' => $this->l('Priority'), 'align' => 'center', 'class' => 'fixed-width-xs'],
            'code'         => ['title' => $this->l('Code'), 'class' => 'fixed-width-sm'],
            'quantity'     => ['title' => $this->l('Quantity'), 'align' => 'center', 'class' => 'fixed-width-xs'],
            'date_to'      => ['title' => $this->l('Expiration date'), 'type' => 'datetime', 'class' => 'fixed-width-lg'],
            'active'       => ['title' => $this->l('Status'), 'active' => 'status', 'type' => 'bool', 'align' => 'center', 'class' => 'fixed-width-xs', 'orderby' => false],
        ];

        parent::__construct();
    }

    /**
     * @since 1.0.0
     */
    public function ajaxProcessLoadCartRules()
    {
        $type = $token = $search = '';
        $limit = $count = $idCartRule = 0;
        if (Tools::getIsset('limit')) {
            $limit = Tools::getValue('limit');
        }

        if (Tools::getIsset('type')) {
            $type = Tools::getValue('type');
        }

        if (Tools::getIsset('count')) {
            $count = Tools::getValue('count');
        }

        if (Tools::getIsset('id_cart_rule')) {
            $idCartRule = Tools::getValue('id_cart_rule');
        }

        if (Tools::getIsset('search')) {
            $search = Tools::getValue('search');
        }

        $page = floor($count / $limit);

        $html = '';
        $nextLink = '';

        if (($page * $limit) + 1 == $count || $count == 0) {
            if ($count == 0) {
                $count = 1;
            }

            /** @var CartRule $currentObject */
            $currentObject = $this->loadObject(true);
            $cartRules = $currentObject->getAssociatedRestrictions('cart_rule', false, true, ($page) * $limit, $limit, $search);

            if ($type == 'selected') {
                $i = 1;
                foreach ($cartRules['selected'] as $cartRule) {
                    $html .= '<option value="'.(int) $cartRule['id_cart_rule'].'">&nbsp;'.Tools::safeOutput($cartRule['name']).'</option>';
                    if ($i == $limit) {
                        break;
                    }
                    $i++;
                }
                if ($i == $limit) {
                    $nextLink = $this->context->link->getAdminLink('AdminCartRules').'&ajaxMode=1&ajax=1&id_cart_rule='.(int) $idCartRule.'&action=loadCartRules&limit='.(int) $limit.'&type=selected&count='.($count - 1 + count($cartRules['selected']).'&search='.urlencode($search));
                }
            } else {
                $i = 1;
                foreach ($cartRules['unselected'] as $cartRule) {
                    $html .= '<option value="'.(int) $cartRule['id_cart_rule'].'">&nbsp;'.Tools::safeOutput($cartRule['name']).'</option>';
                    if ($i == $limit) {
                        break;
                    }
                    $i++;
                }
                if ($i == $limit) {
                    $nextLink = $this->context->link->getAdminLink('AdminCartRules').'&ajaxMode=1&ajax=1&id_cart_rule='.(int) $idCartRule.'&action=loadCartRules&limit='.(int) $limit.'&type=unselected&count='.($count - 1 + count($cartRules['unselected']).'&search='.urlencode($search));
                }
            }
        }

        $this->ajaxDie(json_encode(['html' => $html, 'next_link' => $nextLink]));
    }

    /**
     * @since 1.0.0
     */
    public function setMedia()
    {
        parent::setMedia();
        $this->addJqueryPlugin(['typewatch', 'fancybox', 'autocomplete']);
    }

    /**
     * @since 1.0.0
     */
    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_cart_rule'] = [
                'href' => static::$currentIndex.'&addcart_rule&token='.$this->token,
                'desc' => $this->l('Add new cart rule', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     */
    public function postProcess()
    {
        if (Tools::isSubmit('submitAddcart_rule') || Tools::isSubmit('submitAddcart_ruleAndStay')) {
            // If the reduction is associated to a specific product, then it must be part of the product restrictions
            if ((int) Tools::getValue('reduction_product') && Tools::getValue('apply_discount_to') == 'specific' && Tools::getValue('apply_discount') != 'off') {
                $reductionProduct = (int) Tools::getValue('reduction_product');

                // First, check if it is not already part of the restrictions
                $alreadyRestricted = false;
                if (is_array($ruleGroupArray = Tools::getValue('product_rule_group')) && count($ruleGroupArray) && Tools::getValue('product_restriction')) {
                    foreach ($ruleGroupArray as $ruleGroupId) {
                        if (is_array($ruleArray = Tools::getValue('product_rule_'.$ruleGroupId)) && count($ruleArray)) {
                            foreach ($ruleArray as $ruleId) {
                                if (Tools::getValue('product_rule_'.$ruleGroupId.'_'.$ruleId.'_type') == 'products'
                                    && in_array($reductionProduct, Tools::getValue('product_rule_select_'.$ruleGroupId.'_'.$ruleId))
                                ) {
                                    $alreadyRestricted = true;
                                    break 2;
                                }
                            }
                        }
                    }
                }
                if ($alreadyRestricted == false) {
                    // Check the product restriction
                    $_POST['product_restriction'] = 1;

                    // Add a new rule group
                    $ruleGroupId = 1;
                    if (is_array($ruleGroupArray)) {
                        // Empty for (with a ; at the end), that just find the first rule_group_id available in rule_group_array
                        for ($ruleGroupId = 1; in_array($ruleGroupId, $ruleGroupArray); ++$ruleGroupId) {
                            42;
                        }
                        $_POST['product_rule_group'][] = $ruleGroupId;
                    } else {
                        $_POST['product_rule_group'] = [$ruleGroupId];
                    }

                    // Set a quantity of 1 for this new rule group
                    $_POST['product_rule_group_'.$ruleGroupId.'_quantity'] = 1;
                    // Add one rule to the new rule group
                    $_POST['product_rule_'.$ruleGroupId] = [1];
                    // Set a type 'product' for this 1 rule
                    $_POST['product_rule_'.$ruleGroupId.'_1_type'] = 'products';
                    // Add the product in the selected products
                    $_POST['product_rule_select_'.$ruleGroupId.'_1'] = [$reductionProduct];
                }
            }

            // These are checkboxes (which aren't sent through POST when they are not check), so they are forced to 0
            foreach (['country', 'carrier', 'group', 'cart_rule', 'product', 'shop'] as $type) {
                if (!Tools::getValue($type.'_restriction')) {
                    $_POST[$type.'_restriction'] = 0;
                }
            }

            // Remove the gift if the radio button is set to "no"
            if (!(int) Tools::getValue('free_gift')) {
                $_POST['gift_product'] = 0;
            }

            // Retrieve the product attribute id of the gift (if available)
            if ($idProduct = (int) Tools::getValue('gift_product')) {
                $_POST['gift_product_attribute'] = (int) Tools::getValue('ipa_'.$idProduct);
            }

            // Idiot-proof control
            if (strtotime(Tools::getValue('date_from')) > strtotime(Tools::getValue('date_to'))) {
                $this->errors[] = Tools::displayError('The voucher cannot end before it begins.');
            }
            if ((int) Tools::getValue('minimum_amount') < 0) {
                $this->errors[] = Tools::displayError('The minimum amount cannot be lower than zero.');
            }
            if ((float) Tools::getValue('reduction_percent') < 0 || (float) Tools::getValue('reduction_percent') > 100) {
                $this->errors[] = Tools::displayError('Reduction percentage must be between 0% and 100%');
            }
            if ((int) Tools::getValue('reduction_amount') < 0) {
                $this->errors[] = Tools::displayError('Reduction amount cannot be lower than zero.');
            }
            if (Tools::getValue('code') && ($sameCode = (int) CartRule::getIdByCode(Tools::getValue('code'))) && $sameCode != Tools::getValue('id_cart_rule')) {
                $this->errors[] = sprintf(Tools::displayError('This cart rule code is already used (conflict with cart rule %d)'), $sameCode);
            }
            if (Tools::getValue('apply_discount') == 'off' && !Tools::getValue('free_shipping') && !Tools::getValue('free_gift')) {
                $this->errors[] = Tools::displayError('An action is required for this cart rule.');
            }

            $_POST['minimum_amount'] = priceval(
                Tools::getValue('minimum_amount')
            );
            $_POST['reduction_amount'] = priceval(
                Tools::getValue('reduction_amount')
            );
        }

        return parent::postProcess();
    }

    /**
     * @return false|ObjectModel
     *
     * @since 1.0.0
     */
    public function processDelete()
    {
        $res = parent::processDelete();
        if (Tools::isSubmit('delete'.$this->table)) {
            $back = urldecode(Tools::getValue('back', ''));
            if (!empty($back)) {
                $this->redirect_after = $back;
            }
        }

        return $res;
    }

    /**
     * @return false|ObjectModel
     *
     * @since 1.0.0
     */
    public function processAdd()
    {
        if ($cartRule = parent::processAdd()) {
            $this->context->smarty->assign('new_cart_rule', $cartRule);
        }
        if (Tools::getValue('submitFormAjax')) {
            $this->redirect_after = false;
        }

        return $cartRule;
    }

    /**
     * @since 1.0.0
     */
    public function ajaxProcess()
    {
        if (Tools::isSubmit('newProductRule')) {
            die($this->getProductRuleDisplay(Tools::getValue('product_rule_group_id'), Tools::getValue('product_rule_id'), Tools::getValue('product_rule_type')));
        }
        if (Tools::isSubmit('newProductRuleGroup') && $productRuleGroupId = Tools::getValue('product_rule_group_id')) {
            die($this->getProductRuleGroupDisplay($productRuleGroupId, Tools::getValue('product_rule_group_'.$productRuleGroupId.'_quantity', 1)));
        }

        if (Tools::isSubmit('customerFilter')) {
            $queryMultishop = Shop::isFeatureActive() ? 's.`name` AS `from_shop_name`,' : '';
            $searchQuery = trim(Tools::getValue('q'));
            $customers = Db::getInstance()->executeS(
                'SELECT c.`id_customer`, c.`email`, '.$queryMultishop.' CONCAT(c.`firstname`, \' \', c.`lastname`) as cname
                FROM `'._DB_PREFIX_.'customer` c
                LEFT JOIN `'._DB_PREFIX_.'shop` s ON (c.`id_shop` = s.`id_shop`)
                WHERE c.`deleted` = 0 AND c.`is_guest` = 0 AND c.`active` = 1
                AND (
                    c.`id_customer` = '.(int) $searchQuery.'
                    OR c.`email` LIKE "%'.pSQL($searchQuery).'%"
                    OR c.`firstname` LIKE "%'.pSQL($searchQuery).'%"
                    OR c.`lastname` LIKE "%'.pSQL($searchQuery).'%"
                )
                ORDER BY c.`firstname`, c.`lastname` ASC
                LIMIT 50'
            );
            $this->ajaxDie(json_encode($customers));
        }
        // Both product filter (free product and product discount) search for products
        if (Tools::isSubmit('giftProductFilter') || Tools::isSubmit('reductionProductFilter')) {
            $products = Product::searchByName($this->context->language->id, trim(Tools::getValue('q')));
            $this->ajaxDie(json_encode($products));
        }
    }

    /**
     * @param       $productRuleGroupId
     * @param       $productRuleId
     * @param       $productRuleType
     * @param array $selected
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function getProductRuleDisplay($productRuleGroupId, $productRuleId, $productRuleType, $selected = [])
    {
        $this->context->smarty->assign(
            [
                'product_rule_group_id' => (int) $productRuleGroupId,
                'product_rule_id'       => (int) $productRuleId,
                'product_rule_type'     => $productRuleType,
            ]
        );

        switch ($productRuleType) {
            case 'attributes':
                $attributes = ['selected' => [], 'unselected' => []];
                $results = Db::getInstance()->executeS(
                    '
				SELECT CONCAT(agl.name, " - ", al.name) as name, a.id_attribute as id
				FROM '._DB_PREFIX_.'attribute_group_lang agl
				LEFT JOIN '._DB_PREFIX_.'attribute a ON a.id_attribute_group = agl.id_attribute_group
				LEFT JOIN '._DB_PREFIX_.'attribute_lang al ON (a.id_attribute = al.id_attribute AND al.id_lang = '.(int) $this->context->language->id.')
				WHERE agl.id_lang = '.(int) $this->context->language->id.'
				ORDER BY agl.name, al.name'
                );
                foreach ($results as $row) {
                    $attributes[in_array($row['id'], $selected) ? 'selected' : 'unselected'][] = $row;
                }
                $this->context->smarty->assign('product_rule_itemlist', $attributes);
                $chooseContent = $this->createTemplate('controllers/cart_rules/product_rule_itemlist.tpl')->fetch();
                $this->context->smarty->assign('product_rule_choose_content', $chooseContent);
                break;
            case 'products':
                $products = ['selected' => [], 'unselected' => []];
                $results = Db::getInstance()->executeS(
                    '
				SELECT DISTINCT name, p.id_product as id
				FROM '._DB_PREFIX_.'product p
				LEFT JOIN `'._DB_PREFIX_.'product_lang` pl
					ON (p.`id_product` = pl.`id_product`
					AND pl.`id_lang` = '.(int) $this->context->language->id.Shop::addSqlRestrictionOnLang('pl').')
				'.Shop::addSqlAssociation('product', 'p').'
				WHERE id_lang = '.(int) $this->context->language->id.'
				ORDER BY name'
                );
                foreach ($results as $row) {
                    $products[in_array($row['id'], $selected) ? 'selected' : 'unselected'][] = $row;
                }
                $this->context->smarty->assign('product_rule_itemlist', $products);
                $chooseContent = $this->createTemplate('product_rule_itemlist.tpl')->fetch();
                $this->context->smarty->assign('product_rule_choose_content', $chooseContent);
                break;
            case 'manufacturers':
                $products = ['selected' => [], 'unselected' => []];
                $results = Db::getInstance()->executeS(
                    '
				SELECT name, id_manufacturer as id
				FROM '._DB_PREFIX_.'manufacturer
				ORDER BY name'
                );
                foreach ($results as $row) {
                    $products[in_array($row['id'], $selected) ? 'selected' : 'unselected'][] = $row;
                }
                $this->context->smarty->assign('product_rule_itemlist', $products);
                $chooseContent = $this->createTemplate('product_rule_itemlist.tpl')->fetch();
                $this->context->smarty->assign('product_rule_choose_content', $chooseContent);
                break;
            case 'suppliers':
                $products = ['selected' => [], 'unselected' => []];
                $results = Db::getInstance()->executeS(
                    '
				SELECT name, id_supplier as id
				FROM '._DB_PREFIX_.'supplier
				ORDER BY name'
                );
                foreach ($results as $row) {
                    $products[in_array($row['id'], $selected) ? 'selected' : 'unselected'][] = $row;
                }
                $this->context->smarty->assign('product_rule_itemlist', $products);
                $chooseContent = $this->createTemplate('product_rule_itemlist.tpl')->fetch();
                $this->context->smarty->assign('product_rule_choose_content', $chooseContent);
                break;
            case 'categories':
                $categories = ['selected' => [], 'unselected' => []];
                $results = Db::getInstance()->executeS(
                    '
				SELECT DISTINCT name, c.id_category as id
				FROM '._DB_PREFIX_.'category c
				LEFT JOIN `'._DB_PREFIX_.'category_lang` cl
					ON (c.`id_category` = cl.`id_category`
					AND cl.`id_lang` = '.(int) $this->context->language->id.Shop::addSqlRestrictionOnLang('cl').')
				'.Shop::addSqlAssociation('category', 'c').'
				WHERE id_lang = '.(int) $this->context->language->id.'
				ORDER BY name'
                );
                foreach ($results as $row) {
                    $categories[in_array($row['id'], $selected) ? 'selected' : 'unselected'][] = $row;
                }
                $this->context->smarty->assign('product_rule_itemlist', $categories);
                $chooseContent = $this->createTemplate('product_rule_itemlist.tpl')->fetch();
                $this->context->smarty->assign('product_rule_choose_content', $chooseContent);
                break;
            default:
                $this->context->smarty->assign('product_rule_itemlist', ['selected' => [], 'unselected' => []]);
                $this->context->smarty->assign('product_rule_choose_content', '');
        }

        return $this->createTemplate('product_rule.tpl')->fetch();
    }

    /**
     * @param int  $productRuleGroupId
     * @param int  $productRuleGroupQuantity
     * @param null $productRules
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function getProductRuleGroupDisplay($productRuleGroupId, $productRuleGroupQuantity = 1, $productRules = null)
    {
        $this->context->smarty->assign('product_rule_group_id', $productRuleGroupId);
        $this->context->smarty->assign('product_rule_group_quantity', $productRuleGroupQuantity);
        $this->context->smarty->assign('product_rules', $productRules);

        return $this->createTemplate('product_rule_group.tpl')->fetch();
    }

    /**
     * Return the form for a single cart rule group either with or without product_rules set up
     *
     * @since 1.0.0
     */
    public function ajaxProcessSearchProducts()
    {
        $array = $this->searchProducts(Tools::getValue('product_search'));
        $this->content = trim(json_encode($array));
    }

    /**
     * @param $search
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function searchProducts($search)
    {
        if ($products = Product::searchByName((int) $this->context->language->id, $search)) {
            foreach ($products as &$product) {
                $combinations = [];
                $productObj = new Product((int) $product['id_product'], false, (int) $this->context->language->id);
                $attributes = $productObj->getAttributesGroups((int) $this->context->language->id);
                $product['formatted_price'] = Tools::displayPrice(Tools::convertPrice($product['price_tax_incl'], $this->context->currency), $this->context->currency);

                foreach ($attributes as $attribute) {
                    if (!isset($combinations[$attribute['id_product_attribute']]['attributes'])) {
                        $combinations[$attribute['id_product_attribute']]['attributes'] = '';
                    }
                    $combinations[$attribute['id_product_attribute']]['attributes'] .= $attribute['attribute_name'].' - ';
                    $combinations[$attribute['id_product_attribute']]['id_product_attribute'] = $attribute['id_product_attribute'];
                    $combinations[$attribute['id_product_attribute']]['default_on'] = $attribute['default_on'];
                    if (!isset($combinations[$attribute['id_product_attribute']]['price'])) {
                        $priceTaxIncl = Product::getPriceStatic((int) $product['id_product'], true, $attribute['id_product_attribute']);
                        $combinations[$attribute['id_product_attribute']]['formatted_price'] = Tools::displayPrice(Tools::convertPrice($priceTaxIncl, $this->context->currency), $this->context->currency);
                    }
                }

                foreach ($combinations as &$combination) {
                    $combination['attributes'] = rtrim($combination['attributes'], ' - ');
                }
                $product['combinations'] = $combinations;
            }

            return [
                'products' => $products,
                'found'    => true,
            ];
        } else {
            return ['found' => false, 'notfound' => Tools::displayError('No product has been found.')];
        }
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function renderForm()
    {
        $limit = 40;
        $this->toolbar_btn['save-and-stay'] = [
            'href' => '#',
            'desc' => $this->l('Save and Stay'),
        ];

        /** @var CartRule $currentObject */
        $currentObject = $this->loadObject(true);

        if ($description = json_decode($currentObject->description)) {
            if (isset($description->type) && $description->type === 'cheapest_product') {
                $this->errors[] = $this->l('This cart rule cannot be edited: it is managed by the system.');

                return '';
            }
        }

        // All the filter are prefilled with the correct information
        $customerFilter = '';
        if (Validate::isUnsignedId($currentObject->id_customer) &&
            ($customer = new Customer($currentObject->id_customer)) &&
            Validate::isLoadedObject($customer)
        ) {
            $customerFilter = $customer->firstname.' '.$customer->lastname.' ('.$customer->email.')';
        }

        $giftProductFilter = '';
        if (Validate::isUnsignedId($currentObject->gift_product) &&
            ($product = new Product($currentObject->gift_product, false, $this->context->language->id)) &&
            Validate::isLoadedObject($product)
        ) {
            $giftProductFilter = (!empty($product->reference) ? $product->reference : $product->name);
        }

        $reductionProductFilter = '';
        if (Validate::isUnsignedId($currentObject->reduction_product) &&
            ($product = new Product($currentObject->reduction_product, false, $this->context->language->id)) &&
            Validate::isLoadedObject($product)
        ) {
            $reductionProductFilter = (!empty($product->reference) ? $product->reference : $product->name);
        }

        $productRuleGroups = $this->getProductRuleGroupsDisplay($currentObject);

        $attributeGroups = AttributeGroup::getAttributesGroups($this->context->language->id);
        $currencies = Currency::getCurrencies(false, true, true);
        $languages = Language::getLanguages();
        $countries = $currentObject->getAssociatedRestrictions('country', true, true);
        $groups = $currentObject->getAssociatedRestrictions('group', false, true);
        $shops = $currentObject->getAssociatedRestrictions('shop', false, false);
        $cartRules = $currentObject->getAssociatedRestrictions('cart_rule', false, true, 0, $limit);
        $carriers = $currentObject->getAssociatedRestrictions('carrier', true, false);
        foreach ($carriers as &$carriers2) {
            foreach ($carriers2 as &$carrier) {
                foreach ($carrier as $field => &$value) {
                    if ($field == 'name' && $value == '0') {
                        $value = Configuration::get('PS_SHOP_NAME');
                    }
                }
            }
        }

        $giftProductSelect = '';
        $giftProductAttributeSelect = '';
        if ((int) $currentObject->gift_product) {
            $searchProducts = $this->searchProducts($giftProductFilter);
            if (isset($searchProducts['products']) && is_array($searchProducts['products'])) {
                foreach ($searchProducts['products'] as $product) {
                    $giftProductSelect .= '
					<option value="'.$product['id_product'].'" '.($product['id_product'] == $currentObject->gift_product ? 'selected="selected"' : '').'>
						'.$product['name'].(count($product['combinations']) == 0 ? ' - '.$product['formatted_price'] : '').'
					</option>';

                    if (count($product['combinations'])) {
                        $giftProductAttributeSelect .= '<select class="control-form id_product_attribute" id="ipa_'.$product['id_product'].'" name="ipa_'.$product['id_product'].'">';
                        foreach ($product['combinations'] as $combination) {
                            $giftProductAttributeSelect .= '
							<option '.($combination['id_product_attribute'] == $currentObject->gift_product_attribute ? 'selected="selected"' : '').' value="'.$combination['id_product_attribute'].'">
								'.$combination['attributes'].' - '.$combination['formatted_price'].'
							</option>';
                        }
                        $giftProductAttributeSelect .= '</select>';
                    }
                }
            }
        }

        $product = new Product($currentObject->gift_product);
        $this->context->smarty->assign(
            [
                'show_toolbar'                  => true,
                'toolbar_btn'                   => $this->toolbar_btn,
                'toolbar_scroll'                => $this->toolbar_scroll,
                'title'                         => [$this->l('Payment: '), $this->l('Cart Rules')],
                'defaultDateFrom'               => date('Y-m-d H:00:00'),
                'defaultDateTo'                 => date('Y-m-d H:00:00', strtotime('+1 month')),
                'customerFilter'                => $customerFilter,
                'giftProductFilter'             => $giftProductFilter,
                'gift_product_select'           => $giftProductSelect,
                'gift_product_attribute_select' => $giftProductAttributeSelect,
                'reductionProductFilter'        => $reductionProductFilter,
                'defaultCurrency'               => Configuration::get('PS_CURRENCY_DEFAULT'),
                'id_lang_default'               => Configuration::get('PS_LANG_DEFAULT'),
                'languages'                     => $languages,
                'currencies'                    => $currencies,
                'countries'                     => $countries,
                'carriers'                      => $carriers,
                'groups'                        => $groups,
                'shops'                         => $shops,
                'cart_rules'                    => $cartRules,
                'product_rule_groups'           => $productRuleGroups,
                'product_rule_groups_counter'   => count($productRuleGroups),
                'attribute_groups'              => $attributeGroups,
                'currentIndex'                  => static::$currentIndex,
                'currentToken'                  => $this->token,
                'currentObject'                 => $currentObject,
                'currentTab'                    => $this,
                'hasAttribute'                  => $product->hasAttributes(),
            ]
        );
        Media::addJsDef(
            [
                'baseHref' => $this->context->link->getAdminLink('AdminCartRules').'&ajaxMode=1&ajax=1&id_cart_rule='.(int) Tools::getValue('id_cart_rule').'&action=loadCartRules&limit='.(int) $limit.'&count=0',
            ]
        );
        $this->content .= $this->createTemplate('form.tpl')->fetch();

        $this->addJqueryUI('ui.datepicker');
        $this->addJqueryPlugin(['jscroll', 'typewatch']);

        return parent::renderForm();
    }

    /**
     * Retrieve the cart rule product rule groups in the POST data
     * if available, and in the database if there is none
     *
     * @param CartRule $cartRule
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function getProductRuleGroupsDisplay($cartRule)
    {
        $productRuleGroupsArray = [];
        if (Tools::getValue('product_restriction') && is_array($array = Tools::getValue('product_rule_group')) && count($array)) {
            $i = 1;
            foreach ($array as $ruleGroupId) {
                $productRulesArray = [];
                if (is_array($array = Tools::getValue('product_rule_'.$ruleGroupId)) && count($array)) {
                    foreach ($array as $ruleId) {
                        $productRulesArray[] = $this->getProductRuleDisplay(
                            $ruleGroupId,
                            $ruleId,
                            Tools::getValue('product_rule_'.$ruleGroupId.'_'.$ruleId.'_type'),
                            Tools::getValue('product_rule_select_'.$ruleGroupId.'_'.$ruleId)
                        );
                    }
                }

                $productRuleGroupsArray[] = $this->getProductRuleGroupDisplay(
                    $i++,
                    (int) Tools::getValue('product_rule_group_'.$ruleGroupId.'_quantity'),
                    $productRulesArray
                );
            }
        } else {
            $i = 1;
            foreach ($cartRule->getProductRuleGroups() as $productRuleGroup) {
                $j = 1;
                $productRulesDisplay = [];
                foreach ($productRuleGroup['product_rules'] as $idProductRule => $productRule) {
                    $productRulesDisplay[] = $this->getProductRuleDisplay($i, $j++, $productRule['type'], $productRule['values']);
                }
                $productRuleGroupsArray[] = $this->getProductRuleGroupDisplay($i++, $productRuleGroup['quantity'], $productRulesDisplay);
            }
        }

        return $productRuleGroupsArray;
    }

    /**
     * @since 1.0.0
     */
    public function displayAjaxSearchCartRuleVouchers()
    {
        $found = false;
        if ($vouchers = CartRule::getCartsRuleByCode(Tools::getValue('q'), (int) $this->context->language->id, true)) {
            $found = true;
        }

        $this->ajaxDie(json_encode(['found' => $found, 'vouchers' => $vouchers]));
    }

    /**
     * @param ObjectModel $currentObject
     *
     * @since 1.0.0
     */
    protected function afterUpdate($currentObject)
    {
        // All the associations are deleted for an update, then recreated when we call the "afterAdd" method
        $idCartRule = Tools::getValue('id_cart_rule');
        foreach (['country', 'carrier', 'group', 'product_rule_group', 'shop'] as $type) {
            Db::getInstance()->delete('cart_rule_'.$type, '`id_cart_rule` = '.(int) $idCartRule);
        }

        Db::getInstance()->delete(
            'cart_rule_product_rule', 'NOT EXISTS (SELECT 1 FROM `'._DB_PREFIX_.'cart_rule_product_rule_group`
			WHERE `'._DB_PREFIX_.'cart_rule_product_rule`.`id_product_rule_group` = `'._DB_PREFIX_.'cart_rule_product_rule_group`.`id_product_rule_group`)'
        );
        Db::getInstance()->delete(
            'cart_rule_product_rule_value', 'NOT EXISTS (SELECT 1 FROM `'._DB_PREFIX_.'cart_rule_product_rule`
			WHERE `'._DB_PREFIX_.'cart_rule_product_rule_value`.`id_product_rule` = `'._DB_PREFIX_.'cart_rule_product_rule`.`id_product_rule`)'
        );
        Db::getInstance()->delete('cart_rule_combination', '`id_cart_rule_1` = '.(int) $idCartRule.' OR `id_cart_rule_2` = '.(int) $idCartRule);

        $this->afterAdd($currentObject);
    }

    /**
     * @TODO Move this function into CartRule
     *
     * @param ObjectModel $currentObject
     *
     * @return void
     * @throws PrestaShopDatabaseException
     *
     * @since 1.0.0
     */
    protected function afterAdd($currentObject)
    {
        // Add restrictions for generic entities like country, carrier and group
        foreach (['country', 'carrier', 'group', 'shop'] as $type) {
            if (Tools::getValue($type.'_restriction') && is_array($array = Tools::getValue($type.'_select')) && count($array)) {
                $values = [];
                foreach ($array as $id) {
                    $values[] = '('.(int) $currentObject->id.','.(int) $id.')';
                }
                Db::getInstance()->execute('INSERT INTO `'._DB_PREFIX_.'cart_rule_'.$type.'` (`id_cart_rule`, `id_'.$type.'`) VALUES '.implode(',', $values));
            }
        }
        // Add cart rule restrictions
        if (Tools::getValue('cart_rule_restriction') && is_array($array = Tools::getValue('cart_rule_select')) && count($array)) {
            $values = [];
            foreach ($array as $id) {
                $values[] = '('.(int) $currentObject->id.','.(int) $id.')';
            }
            Db::getInstance()->execute('INSERT INTO `'._DB_PREFIX_.'cart_rule_combination` (`id_cart_rule_1`, `id_cart_rule_2`) VALUES '.implode(',', $values));
        }
        // Add product rule restrictions
        if (Tools::getValue('product_restriction') && is_array($ruleGroupArray = Tools::getValue('product_rule_group')) && count($ruleGroupArray)) {
            foreach ($ruleGroupArray as $ruleGroupId) {
                Db::getInstance()->execute(
                    'INSERT INTO `'._DB_PREFIX_.'cart_rule_product_rule_group` (`id_cart_rule`, `quantity`)
				VALUES ('.(int) $currentObject->id.', '.(int) Tools::getValue('product_rule_group_'.$ruleGroupId.'_quantity').')'
                );
                $idProductRuleGroup = Db::getInstance()->Insert_ID();

                if (is_array($ruleArray = Tools::getValue('product_rule_'.$ruleGroupId)) && count($ruleArray)) {
                    foreach ($ruleArray as $ruleId) {
                        Db::getInstance()->execute(
                            'INSERT INTO `'._DB_PREFIX_.'cart_rule_product_rule` (`id_product_rule_group`, `type`)
						VALUES ('.(int) $idProductRuleGroup.', "'.pSQL(Tools::getValue('product_rule_'.$ruleGroupId.'_'.$ruleId.'_type')).'")'
                        );
                        $idProductRule = Db::getInstance()->Insert_ID();

                        $values = [];
                        foreach (Tools::getValue('product_rule_select_'.$ruleGroupId.'_'.$ruleId) as $id) {
                            $values[] = '('.(int) $idProductRule.','.(int) $id.')';
                        }
                        $values = array_unique($values);
                        if (count($values)) {
                            Db::getInstance()->execute('INSERT INTO `'._DB_PREFIX_.'cart_rule_product_rule_value` (`id_product_rule`, `id_item`) VALUES '.implode(',', $values));
                        }
                    }
                }
            }
        }

        // If the new rule has no cart rule restriction, then it must be added to the white list of the other cart rules that have restrictions
        if (!Tools::getValue('cart_rule_restriction')) {
            Db::getInstance()->execute(
                '
			INSERT INTO `'._DB_PREFIX_.'cart_rule_combination` (`id_cart_rule_1`, `id_cart_rule_2`) (
				SELECT id_cart_rule, '.(int) $currentObject->id.' FROM `'._DB_PREFIX_.'cart_rule` WHERE cart_rule_restriction = 1
			)'
            );
        } // And if the new cart rule has restrictions, previously unrestricted cart rules may now be restricted (a mug of coffee is strongly advised to understand this sentence)
        else {
            $ruleCombinations = Db::getInstance()->executeS(
                '
			SELECT cr.id_cart_rule
			FROM '._DB_PREFIX_.'cart_rule cr
			WHERE cr.id_cart_rule != '.(int) $currentObject->id.'
			AND cr.cart_rule_restriction = 0
			AND NOT EXISTS (
				SELECT 1
				FROM '._DB_PREFIX_.'cart_rule_combination
				WHERE cr.id_cart_rule = '._DB_PREFIX_.'cart_rule_combination.id_cart_rule_2 AND '.(int) $currentObject->id.' = id_cart_rule_1
			)
			AND NOT EXISTS (
				SELECT 1
				FROM '._DB_PREFIX_.'cart_rule_combination
				WHERE cr.id_cart_rule = '._DB_PREFIX_.'cart_rule_combination.id_cart_rule_1 AND '.(int) $currentObject->id.' = id_cart_rule_2
			)
			'
            );
            foreach ($ruleCombinations as $incompatibleRule) {
                Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.'cart_rule` SET cart_rule_restriction = 1 WHERE id_cart_rule = '.(int) $incompatibleRule['id_cart_rule'].' LIMIT 1');
                Db::getInstance()->execute(
                    '
				INSERT IGNORE INTO `'._DB_PREFIX_.'cart_rule_combination` (`id_cart_rule_1`, `id_cart_rule_2`) (
					SELECT id_cart_rule, '.(int) $incompatibleRule['id_cart_rule'].' FROM `'._DB_PREFIX_.'cart_rule`
					WHERE active = 1
					AND id_cart_rule != '.(int) $currentObject->id.'
					AND id_cart_rule != '.(int) $incompatibleRule['id_cart_rule'].'
				)'
                );
            }
        }
    }
}
