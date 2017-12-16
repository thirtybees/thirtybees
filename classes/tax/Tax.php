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
 * Class TaxCore
 *
 * @since   1.0.0
 */
class TaxCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    /** @var string Name */
    public $name;

    /** @var float Rate (%) */
    public $rate;

    /** @var bool active state */
    public $active;

    /** @var bool true if the tax has been historized */
    public $deleted = 0;

    protected static $_product_country_tax = [];
    protected static $_product_tax_via_rules = [];
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'     => 'tax',
        'primary'   => 'id_tax',
        'multilang' => true,
        'fields'    => [
            'rate'    => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true],
            'active'  => ['type' => self::TYPE_BOOL],
            'deleted' => ['type' => self::TYPE_BOOL],
            /* Lang fields */
            'name'    => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 32],
        ],
    ];


    protected $webserviceParameters = [
        'objectsNodeName' => 'taxes',
    ];

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function delete()
    {
        /* Clean associations */
        TaxRule::deleteTaxRuleByIdTax((int) $this->id);

        if ($this->isUsed()) {
            return $this->historize();
        } else {
            return parent::delete();
        }
    }

    /**
     * Save the object with the field deleted to true
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function historize()
    {
        $this->deleted = true;

        return parent::update();
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function toggleStatus()
    {
        if (parent::toggleStatus()) {
            return $this->_onStatusChange();
        }

        return false;
    }

    /**
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function update($nullValues = false)
    {
        if (!$this->deleted && $this->isUsed()) {
            $historizedTax = new Tax($this->id);
            $historizedTax->historize();

            // remove the id in order to create a new object
            $this->id = 0;
            $res = $this->add();

            // change tax id in the tax rule table
            $res &= TaxRule::swapTaxId($historizedTax->id, $this->id);

            return $res;
        } elseif (parent::update($nullValues)) {
            return $this->_onStatusChange();
        }

        return false;
    }

    /**
     * @return bool
     *
     * @deprecated 2.0.0
     * @throws PrestaShopException
     */
    protected function _onStatusChange()
    {
        if (!$this->active) {
            return TaxRule::deleteTaxRuleByIdTax($this->id);
        }

        return true;
    }

    /**
     * Returns true if the tax is used in an order details
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function isUsed()
    {
        return Db::getInstance()->getValue(
            '
		SELECT `id_tax`
		FROM `'._DB_PREFIX_.'order_detail_tax`
		WHERE `id_tax` = '.(int) $this->id
        );
    }

    /**
     * Get all available taxes
     *
     * @param bool $idLang
     * @param bool $activeOnly
     *
     * @return array Taxes
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getTaxes($idLang = false, $activeOnly = true)
    {
        $sql = new DbQuery();
        $sql->select('t.id_tax, t.rate');
        $sql->from('tax', 't');
        $sql->where('t.`deleted` != 1');

        if ($idLang) {
            $sql->select('tl.name, tl.id_lang');
            $sql->leftJoin('tax_lang', 'tl', 't.`id_tax` = tl.`id_tax` AND tl.`id_lang` = '.(int) $idLang);
            $sql->orderBy('`name` ASC');
        }

        if ($activeOnly) {
            $sql->where('t.`active` = 1');
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function excludeTaxeOption()
    {
        return !Configuration::get('PS_TAX');
    }

    /**
     * Return the tax id associated to the specified name
     *
     * @param string $taxName
     * @param int    $active (true by default)
     *
     * @return bool|int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getTaxIdByName($taxName, $active = 1)
    {
        $tax = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            '
			SELECT t.`id_tax`
			FROM `'._DB_PREFIX_.'tax` t
			LEFT JOIN `'._DB_PREFIX_.'tax_lang` tl ON (tl.id_tax = t.id_tax)
			WHERE tl.`name` = \''.pSQL($taxName).'\' '.
            ($active == 1 ? ' AND t.`active` = 1' : '')
        );

        return $tax ? (int) $tax['id_tax'] : false;
    }

    /**
     * Returns the ecotax tax rate
     *
     * @param int $idAddress
     *
     * @return float $tax_rate
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getProductEcotaxRate($idAddress = null)
    {
        $address = Address::initialize($idAddress);

        $taxManager = TaxManagerFactory::getManager($address, (int) Configuration::get('PS_ECOTAX_TAX_RULES_GROUP_ID'));
        $taxCalculator = $taxManager->getTaxCalculator();

        return $taxCalculator->getTotalRate();
    }

    /**
     * Returns the carrier tax rate
     *
     * @param $idAddress
     *
     * @return float $tax_rate
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getCarrierTaxRate($idCarrier, $idAddress = null)
    {
        $address = Address::initialize($idAddress);
        $idTaxRules = (int) Carrier::getIdTaxRulesGroupByIdCarrier((int) $idCarrier);

        $taxManager = TaxManagerFactory::getManager($address, $idTaxRules);
        $taxCalculator = $taxManager->getTaxCalculator();

        return $taxCalculator->getTotalRate();
    }

    /**
     * Return the product tax rate using the tax rules system
     *
     * @param int $idProduct
     * @param int $idCountry
     *
     * @return Tax
     *
     * @deprecated 1.0.0
     * @throws PrestaShopException
     */
    public static function getProductTaxRateViaRules($idProduct, $idCountry, $idState, $zipcode)
    {
        Tools::displayAsDeprecated();

        if (!isset(static::$_product_tax_via_rules[$idProduct.'-'.$idCountry.'-'.$idState.'-'.$zipcode])) {
            $taxRate = TaxRulesGroup::getTaxesRate((int) Product::getIdTaxRulesGroupByIdProduct((int) $idProduct), (int) $idCountry, (int) $idState, $zipcode);
            static::$_product_tax_via_rules[$idProduct.'-'.$idCountry.'-'.$zipcode] = $taxRate;
        }

        return static::$_product_tax_via_rules[$idProduct.'-'.$idCountry.'-'.$zipcode];
    }

    /**
     * Returns the product tax
     *
     * @param int          $idProduct
     * @param null         $idAddress
     * @param Context|null $context
     *
     * @return float
     *
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getProductTaxRate($idProduct, $idAddress = null, Context $context = null)
    {
        if ($context == null) {
            $context = Context::getContext();
        }

        $address = Address::initialize($idAddress);
        $idTaxRules = (int) Product::getIdTaxRulesGroupByIdProduct($idProduct, $context);

        $taxManager = TaxManagerFactory::getManager($address, $idTaxRules);
        $taxCalculator = $taxManager->getTaxCalculator();

        return $taxCalculator->getTotalRate();
    }
}
