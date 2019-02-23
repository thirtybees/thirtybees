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
 * Class HTMLTemplateSupplyOrderFormCore
 *
 * @since 1.0.0
 */
class HTMLTemplateSupplyOrderFormCore extends HTMLTemplate
{
    // @codingStandardsIgnoreStart
    /** @var SupplyOrder $supply_order */
    public $supply_order;
    /** @var Warehouse $warehouse */
    public $warehouse;
    /** @var Address $address_warehouse */
    public $address_warehouse;
    /** @var Address $address_supplier */
    public $address_supplier;
    /** @var Context $context */
    public $context;
    /** @var Currency $currency */
    protected $currency;
    // @codingStandardsIgnoreEnd

    /**
     * @param SupplyOrderCore $supplyOrder
     * @param Smarty          $smarty
     *
     * @throws PrestaShopException
     */
    public function __construct(SupplyOrderCore $supplyOrder, Smarty $smarty)
    {
        $this->supply_order = $supplyOrder;
        $this->smarty = $smarty;
        $this->context = Context::getContext();
        $this->warehouse = new Warehouse((int) $supplyOrder->id_warehouse);
        $this->address_warehouse = new Address((int) $this->warehouse->id_address);
        $this->address_supplier = new Address(Address::getAddressIdBySupplierId((int) $supplyOrder->id_supplier));
        $this->currency = new Currency((int) $this->supply_order->id_currency);

        // Header informations
        $this->date = Tools::displayDate($supplyOrder->date_add);
        $this->title = static::l('Supply order form');

        $this->shop = new Shop((int) $this->supply_order->id_shop);
    }

    /**
     * @see     HTMLTemplate::getContent()
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     * @throws SmartyException
     * @throws Exception
     */
    public function getContent()
    {
        $supplyOrderDetails = $this->supply_order->getEntriesCollection();
        $this->roundSupplyOrderDetails($supplyOrderDetails);

        $this->roundSupplyOrder($this->supply_order);

        $taxOrderSummary = $this->getTaxOrderSummary();

        $this->smarty->assign(
            [
                'warehouse'            => $this->warehouse,
                'address_warehouse'    => $this->address_warehouse,
                'address_supplier'     => $this->address_supplier,
                'supply_order'         => $this->supply_order,
                'supply_order_details' => $supplyOrderDetails,
                'tax_order_summary'    => $taxOrderSummary,
                'currency'             => $this->currency,
            ]
        );

        $tpls = [
            'style_tab'     => $this->smarty->fetch($this->getTemplate('invoice.style-tab')),
            'addresses_tab' => $this->smarty->fetch($this->getTemplate('supply-order.addresses-tab')),
            'product_tab'   => $this->smarty->fetch($this->getTemplate('supply-order.product-tab')),
            'tax_tab'       => $this->smarty->fetch($this->getTemplate('supply-order.tax-tab')),
            'total_tab'     => $this->smarty->fetch($this->getTemplate('supply-order.total-tab')),
        ];
        $this->smarty->assign($tpls);

        return $this->smarty->fetch($this->getTemplate('supply-order'));
    }

    /**
     * Returns the invoice logo
     *
     * @return String Logo path
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    protected function getLogo()
    {
        $logo = '';

        if (Configuration::get('PS_LOGO_INVOICE', null, null, (int) Shop::getContextShopID()) != false && file_exists(_PS_IMG_DIR_.Configuration::get('PS_LOGO_INVOICE', null, null, (int) Shop::getContextShopID()))) {
            $logo = _PS_IMG_DIR_.Configuration::get('PS_LOGO_INVOICE', null, null, (int) Shop::getContextShopID());
        } elseif (Configuration::get('PS_LOGO', null, null, (int) Shop::getContextShopID()) != false && file_exists(_PS_IMG_DIR_.Configuration::get('PS_LOGO', null, null, (int) Shop::getContextShopID()))) {
            $logo = _PS_IMG_DIR_.Configuration::get('PS_LOGO', null, null, (int) Shop::getContextShopID());
        }

        return $logo;
    }

    /**
     * @see HTMLTemplate::getBulkFilename()
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getBulkFilename()
    {
        return 'supply_order.pdf';
    }

    /**
     * @see HTMLTemplate::getFileName()
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getFilename()
    {
        return static::l('SupplyOrderForm').sprintf('_%s', $this->supply_order->reference).'.pdf';
    }

    /**
     * Get order taxes summary
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     * @throws PrestaShopDatabaseException
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    protected function getTaxOrderSummary()
    {
        $query = new DbQuery();
        $query->select('SUM(`price_with_order_discount_te`) AS `base_te`');
        $query->select('`tax_rate`');
        $query->select('SUM(`tax_value_with_order_discount`) AS `total_tax_value`');
        $query->from('supply_order_detail');
        $query->where('`id_supply_order` = '.(int) $this->supply_order->id);
        $query->groupBy('`tax_rate`');

        $results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);

        $decimals = 0;
        if ($this->currency->decimals) {
            $decimals = Configuration::get('PS_PRICE_DISPLAY_PRECISION');
        }
        foreach ($results as &$result) {
            $result['base_te'] = Tools::ps_round(
                $result['base_te'],
                $decimals
            );
            $result['tax_rate'] = Tools::ps_round(
                $result['tax_rate'],
                $decimals
            );
            $result['total_tax_value'] = Tools::ps_round(
                $result['total_tax_value'],
                $decimals
            );
        }

        unset($result); // remove reference

        return $results;
    }

    /**
     * @see     HTMLTemplate::getHeader()
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @return string
     * @throws Exception
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function getHeader()
    {
        $shopName = Configuration::get('PS_SHOP_NAME');
        $pathLogo = $this->getLogo();
        $width = $height = 0;

        if (!empty($pathLogo)) {
            list($width, $height) = getimagesize($pathLogo);
        }

        $this->smarty->assign(
            [
                'logo_path'       => $pathLogo,
                'img_ps_dir'      => 'http://'.Tools::getMediaServer(_PS_IMG_)._PS_IMG_,
                'img_update_time' => Configuration::get('PS_IMG_UPDATE_TIME'),
                'title'           => $this->title,
                'reference'       => $this->supply_order->reference,
                'date'            => $this->date,
                'shop_name'       => $shopName,
                'width_logo'      => $width,
                'height_logo'     => $height,
            ]
        );

        return $this->smarty->fetch($this->getTemplate('supply-order-header'));
    }

    /**
     * @see     HTMLTemplate::getFooter()
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     * @throws Exception
     */
    public function getFooter()
    {
        $this->address = $this->address_warehouse;
        $freeText = [];
        $freeText[] = HTMLTemplateSupplyOrderForm::l('TE: Tax excluded');
        $freeText[] = HTMLTemplateSupplyOrderForm::l('TI: Tax included');

        $this->smarty->assign(
            [
                'shop_address' => $this->getShopAddress(),
                'shop_fax'     => Configuration::get('PS_SHOP_FAX'),
                'shop_phone'   => Configuration::get('PS_SHOP_PHONE'),
                'shop_details' => Configuration::get('PS_SHOP_DETAILS'),
                'free_text'    => $freeText,
            ]
        );

        return $this->smarty->fetch($this->getTemplate('supply-order-footer'));
    }

    /**
     * Rounds values of a SupplyOrderDetail object
     *
     * @param array|PrestaShopCollection $collection
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    protected function roundSupplyOrderDetails(&$collection)
    {
        $decimals = 0;
        if ($this->currency->decimals) {
            $decimals = Configuration::get('PS_PRICE_DISPLAY_PRECISION');
        }
        foreach ($collection as $supplyOrderDetail) {
            /** @var SupplyOrderDetail $supplyOrderDetail */
            $supplyOrderDetail->unit_price_te = Tools::ps_round(
                $supplyOrderDetail->unit_price_te,
                $decimals
            );
            $supplyOrderDetail->price_te = Tools::ps_round(
                $supplyOrderDetail->price_te,
                $decimals
            );
            $supplyOrderDetail->discount_rate = Tools::ps_round(
                $supplyOrderDetail->discount_rate,
                $decimals
            );
            $supplyOrderDetail->price_with_discount_te = Tools::ps_round(
                $supplyOrderDetail->price_with_discount_te,
                $decimals
            );
            $supplyOrderDetail->tax_rate = Tools::ps_round(
                $supplyOrderDetail->tax_rate,
                $decimals
            );
            $supplyOrderDetail->price_ti = Tools::ps_round(
                $supplyOrderDetail->price_ti,
                $decimals
            );
        }
    }

    /**
     * Rounds values of a SupplyOrder object
     *
     * @param SupplyOrder $supplyOrder
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    protected function roundSupplyOrder(SupplyOrder &$supplyOrder)
    {
        $decimals = 0;
        if ($this->currency->decimals) {
            $decimals = Configuration::get('PS_PRICE_DISPLAY_PRECISION');
        }
        $supplyOrder->total_te = Tools::ps_round(
            $supplyOrder->total_te,
            $decimals
        );
        $supplyOrder->discount_value_te = Tools::ps_round(
            $supplyOrder->discount_value_te,
            $decimals
        );
        $supplyOrder->total_with_discount_te = Tools::ps_round(
            $supplyOrder->total_with_discount_te,
            $decimals
        );
        $supplyOrder->total_tax = Tools::ps_round(
            $supplyOrder->total_tax,
            $decimals
        );
        $supplyOrder->total_ti = Tools::ps_round(
            $supplyOrder->total_ti,
            $decimals
        );
    }
}
