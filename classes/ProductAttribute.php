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
 * Class ProductAttributeCore
 */
class ProductAttributeCore extends ObjectModel
{
    /** @var int Group id which attribute belongs */
    public $id_attribute_group;

    /** @var string|string[] Name */
    public $name;

    /** @var string $color */
    public $color;

    /** @var int $position */
    public $position;

    /**
     * @var array Object model definition
     */
    public static $definition = [
        'table'     => 'attribute',
        'primary'   => 'id_attribute',
        'multilang' => true,
        'fields'    => [
            'id_attribute_group' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'color'              => ['type' => self::TYPE_STRING, 'validate' => 'isColor', 'size' => 32],
            'position'           => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'dbDefault' => '0'],

            /* Lang fields */
            'name'               => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 128],
        ],
        'keys' => [
            'attribute' => [
                'attribute_group' => ['type' => ObjectModel::KEY, 'columns' => ['id_attribute_group']],
            ],
            'attribute_lang' => [
                'id_lang' => ['type' => ObjectModel::KEY, 'columns' => ['id_lang', 'name']],
            ],
            'attribute_shop' => [
                'id_shop' => ['type' => ObjectModel::KEY, 'columns' => ['id_shop']],
            ],
        ],
    ];

    /** @var string Path to image directory. Used for image deletion. */
    protected $image_dir = _PS_COL_IMG_DIR_;

    /** @var array WebService parameters */
    protected $webserviceParameters = [
        'objectsNodeName' => 'product_option_values',
        'objectNodeName'  => 'product_option_value',
        'fields'          => [
            'id_attribute_group' => ['xlink_resource' => 'product_options'],
        ],
    ];

    /**
     * ProductAttributeCore constructor.
     *
     * @param int|null $id
     * @param int|null $idLang
     * @param int|null $idShop
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function __construct($id = null, $idLang = null, $idShop = null)
    {
        $this->image_dir = _PS_COL_IMG_DIR_;

        parent::__construct($id, $idLang, $idShop);
    }

    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete()
    {
        if (!$this->hasMultishopEntries() || Shop::getContext() == Shop::CONTEXT_ALL) {
            $conn = Db::readOnly();
            $result = $conn->getArray(
                (new DbQuery())
                    ->select('`id_product_attribute`')
                    ->from('product_attribute_combination')
                    ->where('`id_attribute` = '.(int) $this->id)
            );
            $products = [];

            foreach ($result as $row) {
                $combination = new Combination($row['id_product_attribute']);
                $newRequest = $conn->getArray(
                    (new DbQuery())
                        ->select('`id_product`, `default_on`')
                        ->from('product_attribute')
                        ->where('`id_product_attribute` = '.(int) $row['id_product_attribute'])
                );
                foreach ($newRequest as $value) {
                    if ($value['default_on'] == 1) {
                        $products[] = $value['id_product'];
                    }
                }
                $combination->delete();
            }

            foreach ($products as $product) {
                $idProductAttribute = (int) $conn->getValue(
                    (new DbQuery())
                        ->select('`id_product_attribute`')
                        ->from('product_attribute')
                        ->where('`id_product` = '.(int) $product)
                );

                if (Validate::isLoadedObject($product = new Product((int) $product))) {
                    $product->deleteDefaultAttributes();
                    $product->setDefaultAttribute($idProductAttribute);
                }

            }

            // Delete associated restrictions on cart rules
            CartRule::cleanProductRuleIntegrity('attributes', $this->id);

            /* Reinitializing position */
            $this->cleanPositions((int) $this->id_attribute_group);
        }
        $return = parent::delete();
        if ($return) {
            Hook::triggerEvent('actionAttributeDelete', ['id_attribute' => $this->id]);
        }

        return $return;
    }

    /**
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function update($nullValues = false)
    {
        $return = parent::update($nullValues);

        if ($return) {
            Hook::triggerEvent('actionAttributeSave', ['id_attribute' => $this->id]);
        }

        return $return;
    }

    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function add($autoDate = true, $nullValues = false)
    {
        if ($this->position <= 0) {
            $this->position = static::getHigherPosition($this->id_attribute_group) + 1;
        }

        $return = parent::add($autoDate, $nullValues);

        if ($return) {
            Hook::triggerEvent('actionAttributeSave', ['id_attribute' => $this->id]);
        }

        return $return;
    }

    /**
     * Get all attributes for a given language
     *
     * @param int $idLang Language id
     * @param bool $notNull Get only not null fields if true
     *
     * @return array Attributes
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getAttributes($idLang, $notNull = false)
    {
        if (!Combination::isFeatureActive()) {
            return [];
        }

        return Db::readOnly()->getArray(
            (new DbQuery())
            ->select('DISTINCT ag.*, agl.*, a.`id_attribute`, al.`name`, agl.`name` AS `attribute_group`')
            ->from('attribute_group', 'ag')
            ->leftJoin('attribute_group_lang', 'agl', 'ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = '.(int) $idLang)
            ->leftJoin('attribute', 'a', 'a.`id_attribute_group` = ag.`id_attribute_group`')
            ->leftJoin('attribute_lang', 'al', 'al.`id_attribute` = a.`id_attribute` AND al.`id_lang` = '.(int) $idLang)
            ->join(Shop::addSqlAssociation('attribute_group', 'ag'))
            ->join(Shop::addSqlAssociation('attribute', 'a'))
            ->where($notNull ? 'a.`id_attribute` IS NOT NULL AND al.`name` IS NOT NULL AND agl.`id_attribute_group` IS NOT NULL' : '')
            ->orderBy('agl.`name` ASC, a.`position` ASC')
        );
    }

    /**
     * @param int $idAttributeGroup
     * @param string $name
     * @param int $idLang
     *
     * @return array|bool
     *
     * @throws PrestaShopException
     */
    public static function isAttribute($idAttributeGroup, $name, $idLang)
    {
        if (!Combination::isFeatureActive()) {
            return [];
        }

        $result = Db::readOnly()->getValue(
            (new DbQuery())
            ->select('COUNT(*)')
            ->from('attribute_group', 'ag')
            ->leftJoin('attribute_group_lang', 'agl', 'ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = '.(int) $idLang)
            ->leftJoin('attribute', 'a', 'a.`id_attribute_group` = ag.`id_attribute_group`')
            ->leftJoin('attribute_lang', 'al', 'a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = '.(int) $idLang)
            ->join(Shop::addSqlAssociation('attribute_group', 'ag'))
            ->join(Shop::addSqlAssociation('attribute', 'a'))
            ->where('al.`name` = \''.pSQL($name).'\'')
            ->where('ag.`id_attribute_group` = '.(int) $idAttributeGroup)
            ->orderBy('agl.`name` ASC, a.`position` ASC')
        );

        return ((int) $result > 0);
    }

    /**
     * Get quantity for a given attribute combination
     * Check if quantity is enough to deserve customer
     *
     * @param int $idProductAttribute Product attribute combination id
     * @param int $qty Quantity needed
     *
     * @param Shop|null $shop
     *
     * @return bool Quantity is available or not
     *
     * @throws PrestaShopException
     */
    public static function checkAttributeQty($idProductAttribute, $qty, Shop $shop = null)
    {
        if (!$shop) {
            $shop = Context::getContext()->shop;
        }

        $result = StockAvailable::getQuantityAvailableByProduct(null, (int) $idProductAttribute, $shop->id);

        return ($result && $qty <= $result);
    }

    /**
     * @param int $idProduct Product ID
     * @return int Quantity
     * @throws PrestaShopException
     *
     * @deprecated 1.0.0, use StockAvailable::getQuantityAvailableByProduct()
     */
    public static function getAttributeQty($idProduct)
    {
        Tools::displayAsDeprecated();

        return StockAvailable::getQuantityAvailableByProduct($idProduct);
    }

    /**
     * Update array with veritable quantity
     *
     * @deprecated since 1.0.0
     *
     * @param array $arr
     *
     * @return bool
     * @throws PrestaShopException
     */
    public static function updateQtyProduct(&$arr)
    {
        Tools::displayAsDeprecated();

        $idProduct = (int) $arr['id_product'];
        $qty = StockAvailable::getQuantityAvailableByProduct($idProduct);
        $arr['quantity'] = (int) $qty;
        return true;
    }

    /**
     * Return true if attribute is color type
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function isColorAttribute()
    {
        return (bool) Db::readOnly()->getValue(
            (new DbQuery())
                ->select('ag.`group_type`')
                ->from('attribute_group', 'ag')
                ->innerJoin('attribute', 'a', 'a.`id_attribute_group` = ag.`id_attribute_group`')
                ->where('`group_type` = \'color\'')
        );
    }

    /**
     * Get minimal quantity for product with attributes quantity
     *
     * @param int $idProductAttribute
     *
     * @return false|int Minimal Quantity or false
     *
     * @throws PrestaShopException
     */
    public static function getAttributeMinimalQty($idProductAttribute)
    {
        $minimalQuantity = Db::readOnly()->getValue(
            (new DbQuery())
                ->select('`minimal_quantity`')
                ->from('product_attribute_shop', 'pas')
                ->where('`id_shop` = ' . (int)Context::getContext()->shop->id)
                ->where('`id_product_attribute` = ' . (int)$idProductAttribute)
        );

        if ($minimalQuantity > 1) {
            return (int) $minimalQuantity;
        }

        return false;
    }

    /**
     * Move an attribute inside its group
     *
     * @param bool $way Up (1) or Down (0)
     * @param int $position
     *
     * @return bool Update result
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function updatePosition($way, $position)
    {
        if (!$idAttributeGroup = Tools::getIntValue('id_attribute_group')) {
            $idAttributeGroup = (int) $this->id_attribute_group;
        }

        if (!$res = Db::readOnly()->getArray(
            (new DbQuery())
                ->select('a.`id_attribute`, a.`position`, a.`id_attribute_group`')
                ->from('attribute', 'a')
                ->where('a.`id_attribute_group` = ' . (int)$idAttributeGroup)
                ->orderBy('a.`position` ASC')
        )) {
            return false;
        }

        foreach ($res as $attribute) {
            if ((int) $attribute['id_attribute'] == (int) $this->id) {
                $movedAttribute = $attribute;
            }
        }

        if (!isset($movedAttribute) || !isset($position)) {
            return false;
        }

        // < and > statements rather than BETWEEN operator
        // since BETWEEN is treated differently according to databases

        $conn = Db::getInstance();
        $res1 = $conn->update(
            'attribute',
            [
                'position' => ['type' => 'sql', 'value' => '`position` '.($way ? '- 1' : '+ 1')],
            ],
            '`position`'.($way ? '> '.(int) $movedAttribute['position'].' AND `position` <= '.(int) $position : '< '.(int) $movedAttribute['position'].' AND `position` >= '.(int) $position).' AND `id_attribute_group`='.(int) $movedAttribute['id_attribute_group']
        );

        $res2 = $conn->update(
            'attribute',
            [
                'position' => (int) $position,
            ],
            '`id_attribute` = '.(int) $movedAttribute['id_attribute'].' AND `id_attribute_group`='.(int) $movedAttribute['id_attribute_group']
        );

        return ($res1 && $res2);
    }

    /**
     * Reorder attribute position in group $id_attribute_group.
     * Call it after deleting an attribute from a group.
     *
     * @param int $idAttributeGroup
     * @param bool $useLastAttribute
     *
     * @return bool $return
     *
     * @throws PrestaShopException
     */
    public function cleanPositions($idAttributeGroup, $useLastAttribute = true)
    {
        $conn = Db::getInstance();
        $conn->execute('SET @i = -1', false);
        $sql = 'UPDATE `'._DB_PREFIX_.'attribute` SET `position` = @i:=@i+1 WHERE';

        if ($useLastAttribute) {
            $sql .= ' `id_attribute` != '.(int) $this->id.' AND';
        }

        $sql .= ' `id_attribute_group` = '.(int) $idAttributeGroup.' ORDER BY `position` ASC';

        return $conn->execute($sql);
    }

    /**
     * getHigherPosition
     *
     * Get the higher attribute position from a group attribute
     *
     * @param int $idAttributeGroup
     *
     * @return int $position
     *
     * @throws PrestaShopException
     */
    public static function getHigherPosition($idAttributeGroup)
    {
        $position = Db::readOnly()->getValue(
            (new DbQuery())
                ->select('MAX(`position`)')
                ->from('attribute')
                ->where('`id_attribute_group` = ' . (int)$idAttributeGroup)
        );

        return (is_numeric($position)) ? $position : -1;
    }
}
