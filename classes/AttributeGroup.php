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
 * Class AttributeGroupCore
 *
 * @since 1.0.0
 */
class AttributeGroupCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'     => 'attribute_group',
        'primary'   => 'id_attribute_group',
        'multilang' => true,
        'fields'    => [
            'is_color_group' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'group_type'     => ['type' => self::TYPE_STRING, 'required' => true],
            'position'       => ['type' => self::TYPE_INT, 'validate' => 'isInt'],

            /* Lang fields */
            'name'           => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 128],
            'public_name'    => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 64],
        ],
    ];
    /** @var string Name */
    public $name;
    /** @var bool $is_color_group */
    public $is_color_group;
    /** @var int $position */
    public $position;
    /** @var string $group_type */
    public $group_type;
    /** @var string Public Name */
    public $public_name;
    protected $webserviceParameters = [
        'objectsNodeName' => 'product_options',
        'objectNodeName'  => 'product_option',
        'fields'          => [],
        'associations'    => [
            'product_option_values' => [
                'resource' => 'product_option_value',
                'fields'   => [
                    'id' => [],
                ],
            ],
        ],
    ];
    // @codingStandardsIgnoreEnd

    /**
     * Get all attributes for a given language / group
     *
     * @param int  $idLang           Language id
     * @param bool $idAttributeGroup Attribute group id
     *
     * @return array Attributes
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getAttributes($idLang, $idAttributeGroup)
    {
        if (!Combination::isFeatureActive()) {
            return [];
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from('attribute', 'a')
                ->join(Shop::addSqlAssociation('attribute', 'a'))
                ->leftJoin('attribute_lang', 'al', 'a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = '.(int) $idLang)
                ->where('a.`id_attribute_group` = '.(int) $idAttributeGroup)
                ->orderBy('`position` ASC')
        );
    }

    /**
     * Get all attributes groups for a given language
     *
     * @param int $idLang Language id
     *
     * @return array Attributes groups
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getAttributesGroups($idLang)
    {
        if (!Combination::isFeatureActive()) {
            return [];
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('DISTINCT agl.`name`, ag.*, agl.*')
                ->from('attribute_group', 'ag')
                ->join(Shop::addSqlAssociation('attribute_group', 'ag'))
                ->leftJoin('attribute_group_lang', 'agl', 'ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = '.(int) $idLang)
                ->orderBy('agl.`name` ASC')
        );
    }

    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function add($autoDate = true, $nullValues = false)
    {
        if ($this->group_type == 'color') {
            $this->is_color_group = 1;
        } else {
            $this->is_color_group = 0;
        }

        if ($this->position <= 0) {
            $this->position = AttributeGroup::getHigherPosition() + 1;
        }

        $return = parent::add($autoDate, true);
        Hook::exec('actionAttributeGroupSave', ['id_attribute_group' => $this->id]);

        return $return;
    }

    /**
     * getHigherPosition
     *
     * Get the higher group attribute position
     *
     * @return int $position
     * @throws PrestaShopException
     */
    public static function getHigherPosition()
    {
        $position = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('MAX(`position`)')
                ->from('attribute_group')
        );

        if (!$position) {
            return -1;
        }

        return $position;
    }

    /**
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function update($nullValues = false)
    {
        if ($this->group_type == 'color') {
            $this->is_color_group = 1;
        } else {
            $this->is_color_group = 0;
        }

        $return = parent::update($nullValues);
        Hook::exec('actionAttributeGroupSave', ['id_attribute_group' => $this->id]);

        return $return;
    }

    /**
     * Delete several objects from database
     *
     * return boolean Deletion result
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     *
     * @param array $selection
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     */
    public function deleteSelection($selection)
    {
        /* Also delete Attributes */
        foreach ($selection as $value) {
            $obj = new AttributeGroup($value);
            if (!$obj->delete()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete()
    {
        if (!$this->hasMultishopEntries() || Shop::getContext() == Shop::CONTEXT_ALL) {
            /* Select children in order to find linked combinations */
            $attributeIds = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('`id_attribute`')
                    ->from('attribute')
                    ->where('`id_attribute_group` = '.(int) $this->id)
            );
            if ($attributeIds === false) {
                return false;
            }
            /* Removing attributes to the found combinations */
            $toRemove = [];
            foreach ($attributeIds as $attribute) {
                $toRemove[] = (int) $attribute['id_attribute'];
            }
            if (!empty($toRemove)
                && Db::getInstance()->delete('product_attribute_combination', '`id_attribute` IN ('.implode(',', $toRemove).')') === false
            ) {
                return false;
            }
            /* Remove combinations if they do not possess attributes anymore */
            if (!AttributeGroup::cleanDeadCombinations()) {
                return false;
            }
            /* Also delete related attributes */
            if (count($toRemove)) {
                if (!Db::getInstance()->delete('attribute_lang', '`id_attribute` IN ('.implode(',', $toRemove).')')
                    || !Db::getInstance()->delete('attribute_shop', '`id_attribute` IN ('.implode(',', $toRemove).')')
                    || !Db::getInstance()->delete('attribute', '`id_attribute_group` = '.(int) $this->id)
                ) {
                    return false;
                }
            }
            $this->cleanPositions();
        }
        $return = parent::delete();
        if ($return) {
            Hook::exec('actionAttributeGroupDelete', ['id_attribute_group' => $this->id]);
        }

        return $return;
    }

    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function cleanDeadCombinations()
    {
        $attributeCombinations = Db::getInstance()->executeS(
            (new DbQuery())
                ->select('pac.`id_attribute`, pa.`id_product_attribute`')
                ->from('product_attribute', 'pa')
                ->leftJoin('product_attribute_combination', 'pac', 'pa.`id_product_attribute` = pac.`id_product_attribute`')
        );
        $toRemove = [];
        foreach ($attributeCombinations as $attributeCombination) {
            if ((int) $attributeCombination['id_attribute'] == 0) {
                $toRemove[] = (int) $attributeCombination['id_product_attribute'];
            }
        }
        $return = true;
        if (!empty($toRemove)) {
            foreach ($toRemove as $remove) {
                $combination = new Combination($remove);
                $return &= $combination->delete();
            }
        }

        return $return;
    }

    /**
     * Reorder group attribute position
     * Call it after deleting a group attribute.
     *
     * @return bool $return
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function cleanPositions()
    {
        $return = true;
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('`id_attribute_group`')
                ->from('attribute_group')
                ->orderBy('`position`')
        );

        $i = 0;
        foreach ($result as $value) {
            $return = Db::getInstance()->update(
                'attribute_group',
                [
                    'position' => (int) $i++,
                ],
                '`id_attribute_group` = '.(int) $value['id_attribute_group']
            );
        }

        return $return;
    }

    /**
     * @param array $values
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function setWsProductOptionValues($values)
    {
        $ids = [];
        foreach ($values as $value) {
            $ids[] = intval($value['id']);
        }
        Db::getInstance()->delete(
            'attribute',
            '`id_attribute_group` = '.(int) $this->id.' AND `id_attribute` NOT IN ('.implode(',', $ids).')'
        );
        $ok = true;
        foreach ($values as $value) {
            $result = Db::getInstance()->update(
                'attribute',
                [
                    'id_attribute_group' => (int) $this->id,
                ],
                '`id_attribute` = '.(int) $value['id']
            );
            if ($result === false) {
                $ok = false;
            }
        }

        return $ok;
    }

    /**
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getWsProductOptionValues()
    {
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('a.`id_attribute` AS `id`')
                ->from('attribute', 'a')
                ->join(Shop::addSqlAssociation('attribute', 'a'))
                ->where('a.`id_attribute_group` = '.(int) $this->id)
        );

        return $result;
    }

    /**
     * Move a group attribute
     *
     * @param bool $way      Up (1) or Down (0)
     * @param int  $position
     *
     * @return bool Update result
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function updatePosition($way, $position)
    {
        if (!$res = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('ag.`position`, ag.`id_attribute_group`')
                ->from('attribute_group', 'ag')
                ->where('ag.`id_attribute_group` = '.(int) Tools::getValue('id_attribute_group', 1))
                ->orderBy('ag.`position` ASC')
        )
        ) {
            return false;
        }

        foreach ($res as $groupAttribute) {
            if ((int) $groupAttribute['id_attribute_group'] == (int) $this->id) {
                $movedGroupAttribute = $groupAttribute;
            }
        }

        if (!isset($movedGroupAttribute) || !isset($position)) {
            return false;
        }

        // < and > statements rather than BETWEEN operator
        // since BETWEEN is treated differently according to databases
        return Db::getInstance()->update(
            'attribute_group',
            [
                'position' => ['type' => 'sql', 'value' => '`position` '.($way ? '- 1' : '+ 1')],
            ],
            '`position` '.($way ? '> '.(int) $movedGroupAttribute['position'].' AND `position` <= '.(int) $position : '< '.(int) $movedGroupAttribute['position'].' AND `position` >= '.(int) $position)
        ) && Db::getInstance()->update(
            'attribute_group',
            [
                'position' => (int) $position,
            ],
            '`id_attribute_group` = '.(int) $movedGroupAttribute['id_attribute_group']
        );
    }
}
