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
 * Class TagCore
 *
 * @since 1.0.0
 */
class TagCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    /** @var int Language id */
    public $id_lang;
    /** @var string Name */
    public $name;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'tag',
        'primary' => 'id_tag',
        'fields'  => [
            'id_lang' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'name'    => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 32],
        ],
    ];

    protected $webserviceParameters = [
        'fields' => [
            'id_lang' => ['xlink_resource' => 'languages'],
        ],
    ];

    /**
     * TagCore constructor.
     *
     * @param int|null    $id
     * @param string|null $name
     * @param int|null    $idLang
     *
     * @since   1.0.0
     * @version 1.0.0
     */
    public function __construct($id = null, $name = null, $idLang = null)
    {
        $this->def = Tag::getDefinition($this);
        $this->setDefinitionRetrocompatibility();

        if ($id) {
            parent::__construct($id);
        } elseif ($name && Validate::isGenericName($name) && $idLang && Validate::isUnsignedId($idLang)) {
            $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
                '
			SELECT *
			FROM `'._DB_PREFIX_.'tag` t
			WHERE `name` = \''.pSQL($name).'\' AND `id_lang` = '.(int) $idLang
            );

            if ($row) {
                $this->id = (int) $row['id_tag'];
                $this->id_lang = (int) $row['id_lang'];
                $this->name = $row['name'];
            }
        }
    }

    /**
     * Add several tags in database and link it to a product
     *
     * @param int          $idLang    Language id
     * @param int          $idProduct Product id to link tags with
     * @param string|array $tagList   List of tags, as array or as a string with comas
     *
     * @return bool Operation success
     *
     * @since   1.0.0
     * @version 1.0.0
     */
    public static function addTags($idLang, $idProduct, $tagList, $separator = ',')
    {
        if (!Validate::isUnsignedId($idLang)) {
            return false;
        }

        if (!is_array($tagList)) {
            $tagList = array_filter(array_unique(array_map('trim', preg_split('#\\'.$separator.'#', $tagList, null, PREG_SPLIT_NO_EMPTY))));
        }

        $list = [];
        if (is_array($tagList)) {
            foreach ($tagList as $tag) {
                if (!Validate::isGenericName($tag)) {
                    return false;
                }
                $tag = trim(Tools::substr($tag, 0, static::$definition['fields']['name']['size']));
                $tagObj = new Tag(null, $tag, (int) $idLang);

                /* Tag does not exist in database */
                if (!Validate::isLoadedObject($tagObj)) {
                    $tagObj->name = $tag;
                    $tagObj->id_lang = (int) $idLang;
                    $tagObj->add();
                }
                if (!in_array($tagObj->id, $list)) {
                    $list[] = $tagObj->id;
                }
            }
        }
        $data = '';
        foreach ($list as $tag) {
            $data .= '('.(int) $tag.','.(int) $idProduct.','.(int) $idLang.'),';
        }
        $data = rtrim($data, ',');

        $result = Db::getInstance()->execute(
            '
		INSERT INTO `'._DB_PREFIX_.'product_tag` (`id_tag`, `id_product`, `id_lang`)
		VALUES '.$data
        );

        if ($list != []) {
            static::updateTagCount($list);
        }

        return $result;
    }

    /**
     * @param bool $autodate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0
     */
    public function add($autodate = true, $nullValues = false)
    {
        if (!parent::add($autodate, $nullValues)) {
            return false;
        } elseif (isset($_POST['products'])) {
            return $this->setProducts(Tools::getValue('products'));
        }

        return true;
    }

    /**
     * @param $array
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0
     */
    public function setProducts($array)
    {
        $result = Db::getInstance()->delete('product_tag', 'id_tag = '.(int) $this->id);
        if (is_array($array)) {
            $array = array_map('intval', $array);
            $result &= ObjectModel::updateMultishopTable('Product', ['indexed' => 0], 'a.id_product IN ('.implode(',', $array).')');
            $ids = [];
            foreach ($array as $idProduct) {
                $ids[] = '('.(int) $idProduct.','.(int) $this->id.','.(int) $this->id_lang.')';
            }

            if ($result) {
                $result &= Db::getInstance()->execute('INSERT INTO '._DB_PREFIX_.'product_tag (id_product, id_tag, id_lang) VALUES '.implode(',', $ids));
                if (Configuration::get('PS_SEARCH_INDEXATION')) {
                    $result &= Search::indexation(false);
                }
            }
        }
        static::updateTagCount([(int) $this->id]);

        return $result;
    }

    /**
     * @param array|null $tagList
     *
     * @since   1.0.0
     * @version 1.0.0
     */
    public static function updateTagCount($tagList = null)
    {
        if (!Module::getBatchMode()) {
            if ($tagList != null) {
                $tagListQuery = ' AND pt.id_tag IN ('.implode(',', $tagList).')';
                Db::getInstance()->execute('DELETE pt FROM `'._DB_PREFIX_.'tag_count` pt WHERE 1=1 '.$tagListQuery);
            } else {
                $tagListQuery = '';
            }

            Db::getInstance()->execute(
                'REPLACE INTO `'._DB_PREFIX_.'tag_count` (id_group, id_tag, id_lang, id_shop, counter)
			SELECT cg.id_group, pt.id_tag, pt.id_lang, id_shop, COUNT(pt.id_tag) AS times
				FROM `'._DB_PREFIX_.'product_tag` pt
				INNER JOIN `'._DB_PREFIX_.'product_shop` product_shop
					USING (id_product)
				JOIN (SELECT DISTINCT id_group FROM `'._DB_PREFIX_.'category_group`) cg
				WHERE product_shop.`active` = 1
				AND EXISTS(SELECT 1 FROM `'._DB_PREFIX_.'category_product` cp
								LEFT JOIN `'._DB_PREFIX_.'category_group` cgo ON (cp.`id_category` = cgo.`id_category`)
								WHERE cgo.`id_group` = cg.id_group AND product_shop.`id_product` = cp.`id_product`)
				'.$tagListQuery.'
				GROUP BY pt.id_tag, pt.id_lang, cg.id_group, id_shop ORDER BY NULL'
            );
            Db::getInstance()->execute(
                'REPLACE INTO `'._DB_PREFIX_.'tag_count` (id_group, id_tag, id_lang, id_shop, counter)
			SELECT 0, pt.id_tag, pt.id_lang, id_shop, COUNT(pt.id_tag) AS times
				FROM `'._DB_PREFIX_.'product_tag` pt
				INNER JOIN `'._DB_PREFIX_.'product_shop` product_shop
					USING (id_product)
				WHERE product_shop.`active` = 1
				'.$tagListQuery.'
				GROUP BY pt.id_tag, pt.id_lang, id_shop ORDER BY NULL'
            );
        }
    }

    /**
     * @param int $idLang
     * @param int $nb
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @since   1.0.0
     * @version 1.0.0
     */
    public static function getMainTags($idLang, $nb = 10)
    {
        $context = Context::getContext();
        if (Group::isFeatureActive()) {
            $groups = FrontController::getCurrentCustomerGroups();

            return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                '
			SELECT t.name, counter AS times
			FROM `'._DB_PREFIX_.'tag_count` pt
			LEFT JOIN `'._DB_PREFIX_.'tag` t ON (t.id_tag = pt.id_tag)
			WHERE pt.`id_group` '.(count($groups) ? 'IN ('.implode(',', $groups).')' : '= 1').'
			AND pt.`id_lang` = '.(int) $idLang.' AND pt.`id_shop` = '.(int) $context->shop->id.'
			ORDER BY times DESC
			LIMIT '.(int) $nb
            );
        } else {
            return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                '
			SELECT t.name, counter AS times
			FROM `'._DB_PREFIX_.'tag_count` pt
			LEFT JOIN `'._DB_PREFIX_.'tag` t ON (t.id_tag = pt.id_tag)
			WHERE pt.id_group = 0 AND pt.`id_lang` = '.(int) $idLang.' AND pt.`id_shop` = '.(int) $context->shop->id.'
			ORDER BY times DESC
			LIMIT '.(int) $nb
            );
        }
    }

    /**
     * @param int $idProduct
     *
     * @return array|bool
     *
     * @since   1.0.0
     * @version 1.0.0
     */
    public static function getProductTags($idProduct)
    {
        if (!$tmp = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            '
		SELECT t.`id_lang`, t.`name`
		FROM '._DB_PREFIX_.'tag t
		LEFT JOIN '._DB_PREFIX_.'product_tag pt ON (pt.id_tag = t.id_tag)
		WHERE pt.`id_product`='.(int) $idProduct
        )
        ) {
            return false;
        }
        $result = [];
        foreach ($tmp as $tag) {
            $result[$tag['id_lang']][] = $tag['name'];
        }

        return $result;
    }

    /**
     * @param int $idProduct
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0
     */
    public static function deleteTagsForProduct($idProduct)
    {
        $tagsRemoved = Db::getInstance()->executeS('SELECT id_tag FROM '._DB_PREFIX_.'product_tag WHERE id_product='.(int) $idProduct);
        $result = Db::getInstance()->delete('product_tag', 'id_product = '.(int) $idProduct);
        Db::getInstance()->delete(
            'tag', 'NOT EXISTS (SELECT 1 FROM '._DB_PREFIX_.'product_tag
        												WHERE '._DB_PREFIX_.'product_tag.id_tag = '._DB_PREFIX_.'tag.id_tag)'
        );
        $tagList = [];
        foreach ($tagsRemoved as $tagRemoved) {
            $tagList[] = $tagRemoved['id_tag'];
        }
        if ($tagList != []) {
            static::updateTagCount($tagList);
        }

        return $result;
    }

    /**
     * @param bool         $associated
     * @param Context|null $context
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @since   1.0.0
     * @version 1.0.0
     */
    public function getProducts($associated = true, Context $context = null)
    {
        if (!$context) {
            $context = Context::getContext();
        }
        $idLang = $this->id_lang ? $this->id_lang : $context->language->id;

        if (!$this->id && $associated) {
            return [];
        }

        $in = $associated ? 'IN' : 'NOT IN';

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            '
		SELECT pl.name, pl.id_product
		FROM `'._DB_PREFIX_.'product` p
		LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON p.id_product = pl.id_product'.Shop::addSqlRestrictionOnLang('pl').'
		'.Shop::addSqlAssociation('product', 'p').'
		WHERE pl.id_lang = '.(int) $idLang.'
		AND product_shop.active = 1
		'.($this->id ? ('AND p.id_product '.$in.' (SELECT pt.id_product FROM `'._DB_PREFIX_.'product_tag` pt WHERE pt.id_tag = '.(int) $this->id.')') : '').'
		ORDER BY pl.name'
        );
    }
}
