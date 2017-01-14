<?php
/**
 * 2007-2016 PrestaShop
 *
 * Thirty Bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017 Thirty Bees
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
 * @author    Thirty Bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017 Thirty Bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class CustomizationCore
 *
 * @since 1.0.0
 */
class CustomizationCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    /** @var int $id_product_attribute */
    public $id_product_attribute;
    /** @var int $id_address_delivery */
    public $id_address_delivery;
    /** @var int $id_cart */
    public $id_cart;
    /** @var int $id_product */
    public $id_product;
    /** @var int $quantity */
    public $quantity;
    /** @var int $quantity_refunded */
    public $quantity_refunded;
    /** @var int $quantity_returned */
    public $quantity_returned;
    /** @var bool $in_cart */
    public $in_cart;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'customization',
        'primary' => 'id_customization',
        'fields'  => [
            /* Classic fields */
            'id_product_attribute' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_address_delivery'  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_cart'              => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_product'           => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'quantity'             => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'quantity_refunded'    => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'quantity_returned'    => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'in_cart'              => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true],
        ],
    ];
    protected $webserviceParameters = [
        'fields'       => [
            'id_address_delivery' => [
                'xlink_resource' => [
                    'resourceName' => 'addresses',
                ],
            ],
            'id_cart'             => [
                'xlink_resource' => [
                    'resourceName' => 'carts',
                ],
            ],
            'id_product'          => [
                'xlink_resource' => [
                    'resourceName' => 'products',
                ],
            ],
        ],
        'associations' => [
            'customized_data_text_fields' => [
                'resource' => 'customized_data_text_field', 'virtual_entity' => true, 'fields' => [
                    'id_customization_field' => ['required' => true, 'xlink_resource' => 'product_customization_fields'],
                    'value'                  => [],
                ],
            ],
            'customized_data_images'      => [
                'resource' => 'customized_data_image', 'virtual_entity' => true, 'setter' => false, 'fields' => [
                    'id_customization_field' => ['xlink_resource' => 'product_customization_fields'],
                    'value'                  => [],
                ],
            ],
        ],
    ];

    /**
     * @param int $idOrder
     *
     * @return array|bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getReturnedCustomizations($idOrder)
    {
        if (($result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                '
			SELECT ore.`id_order_return`, ord.`id_order_detail`, ord.`id_customization`, ord.`product_quantity`
			FROM `'._DB_PREFIX_.'order_return` ore
			INNER JOIN `'._DB_PREFIX_.'order_return_detail` ord ON (ord.`id_order_return` = ore.`id_order_return`)
			WHERE ore.`id_order` = '.(int) ($idOrder).' AND ord.`id_customization` != 0'
            )) === false
        ) {
            return false;
        }
        $customizations = [];
        foreach ($result as $row) {
            $customizations[(int) ($row['id_customization'])] = $row;
        }

        return $customizations;
    }

    /**
     * @param int $idCart
     *
     * @return array|bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getOrderedCustomizations($idCart)
    {
        if (!$result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SELECT `id_customization`, `quantity` FROM `'._DB_PREFIX_.'customization` WHERE `id_cart` = '.(int) ($idCart))) {
            return false;
        }
        $customizations = [];
        foreach ($result as $row) {
            $customizations[(int) ($row['id_customization'])] = $row;
        }

        return $customizations;
    }

    /**
     * @param $customizations
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function countCustomizationQuantityByProduct($customizations)
    {
        $total = [];
        foreach ($customizations as $customization) {
            $total[(int) $customization['id_order_detail']] = !isset($total[(int) $customization['id_order_detail']]) ? (int) $customization['quantity'] : $total[(int) $customization['id_order_detail']] + (int) $customization['quantity'];
        }

        return $total;
    }

    /**
     * @param      $id_customization
     * @param      $idLang
     * @param null $idShop
     *
     * @return bool|false|null|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getLabel($id_customization, $idLang, $idShop = null)
    {
        if (!(int) $id_customization || !(int) $idLang) {
            return false;
        }
        if (Shop::isFeatureActive() && !(int) $idShop) {
            $idShop = (int) Context::getContext()->shop->id;
        }

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            '
		SELECT `name`
		FROM `'._DB_PREFIX_.'customization_field_lang`
		WHERE `id_customization_field` = '.(int) $id_customization.((int) $idShop ? ' AND cfl.`id_shop` = '.(int) $idShop : '').'
		AND `id_lang` = '.(int) $idLang
        );

        return $result;
    }

    /**
     * @param array $idsCustomizations
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function retrieveQuantitiesFromIds($idsCustomizations)
    {
        $quantities = [];

        $inValues = '';
        foreach ($idsCustomizations as $key => $idCustomization) {
            if ($key > 0) {
                $inValues .= ',';
            }
            $inValues .= (int) ($idCustomization);
        }

        if (!empty($inValues)) {
            $results = Db::getInstance()->executeS(
                'SELECT `id_customization`, `id_product`, `quantity`, `quantity_refunded`, `quantity_returned`
							 FROM `'._DB_PREFIX_.'customization`
							 WHERE `id_customization` IN ('.$inValues.')'
            );

            foreach ($results as $row) {
                $quantities[$row['id_customization']] = $row;
            }
        }

        return $quantities;
    }

    /**
     * @param int $idCart
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function countQuantityByCart($idCart)
    {
        $quantity = [];

        $results = Db::getInstance()->executeS(
            '
			SELECT `id_product`, `id_product_attribute`, SUM(`quantity`) AS quantity
			FROM `'._DB_PREFIX_.'customization`
			WHERE `id_cart` = '.(int) $idCart.'
			GROUP BY `id_cart`, `id_product`, `id_product_attribute`
		'
        );

        foreach ($results as $row) {
            $quantity[$row['id_product']][$row['id_product_attribute']] = $row['quantity'];
        }

        return $quantity;
    }

    /**
     * This method is allow to know if a feature is used or active
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isFeatureActive()
    {
        return Configuration::get('PS_CUSTOMIZATION_FEATURE_ACTIVE');
    }

    /**
     * This method is allow to know if a Customization entity is currently used
     *
     * @param $table
     * @param $hasActiveColumn
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isCurrentlyUsed($table = null, $hasActiveColumn = false)
    {
        return (bool) Db::getInstance()->getValue(
            '
			SELECT `id_customization_field`
			FROM `'._DB_PREFIX_.'customization_field`
		'
        );
    }

    /**
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getWsCustomizedDataTextFields()
    {
        if (!$results = Db::getInstance()->executeS(
            '
			SELECT id_customization_field, value
			FROM `'._DB_PREFIX_.'customization_field` cf
			LEFT JOIN `'._DB_PREFIX_.'customized_data` cd ON (cf.id_customization_field = cd.index)
			WHERE `id_product` = '.(int) $this->id_product.'
			AND cf.type = 1'
        )
        ) {
            return [];
        }

        return $results;
    }

    /**
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getWsCustomizedDataImages()
    {
        if (!$results = Db::getInstance()->executeS(
            '
			SELECT id_customization_field, value
			FROM `'._DB_PREFIX_.'customization_field` cf
			LEFT JOIN `'._DB_PREFIX_.'customized_data` cd ON (cf.id_customization_field = cd.index)
			WHERE `id_product` = '.(int) $this->id_product.'
			AND cf.type = 0'
        )
        ) {
            return [];
        }

        return $results;
    }

    /**
     * @param $values
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setWsCustomizedDataTextFields($values)
    {
        $cart = new Cart($this->id_cart);
        if (!Validate::isLoadedObject($cart)) {
            WebserviceRequest::getInstance()->setError(500, Tools::displayError('Could not load cart id='.$this->id_cart), 137);

            return false;
        }
        Db::getInstance()->execute(
            '
		DELETE FROM `'._DB_PREFIX_.'customized_data`
		WHERE id_customization = '.(int) $this->id.'
		AND type = 1'
        );
        foreach ($values as $value) {
            $query = 'INSERT INTO `'._DB_PREFIX_.'customized_data` (`id_customization`, `type`, `index`, `value`)
				VALUES ('.(int) $this->id.', 1, '.(int) $value['id_customization_field'].', \''.pSQL($value['value']).'\')';

            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        return true;
    }
}
