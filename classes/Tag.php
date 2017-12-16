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
 * Class TagCore
 *
 * @since 1.0.0
 */
class TagCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
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
    /** @var int Language id */
    public $id_lang;
    /** @var string Name */
    public $name;
    protected $webserviceParameters = [
        'fields' => [
            'id_lang' => ['xlink_resource' => 'languages'],
        ],
    ];
    // @codingStandardsIgnoreEnd

    /**
     * TagCore constructor.
     *
     * @param int|null    $id
     * @param string|null $name
     * @param int|null    $idLang
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
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
                (new DbQuery())
                    ->select('*')
                    ->from('tag', 't')
                    ->where('`name` = \''.pSQL($name).'\'')
                    ->where('`id_lang` = '.(int) $idLang)
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
     * @param string       $separator Separator to split a given string inot an array.
     *                                Not needed if $tagList is an array already.
     *
     * @return bool Operation success
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0
     */
    public static function addTags($idLang, $idProduct, $tagList, $separator = ',')
    {
        if (!Validate::isUnsignedId($idLang)) {
            return false;
        }

        if (!is_array($tagList)) {
            $tagList = explode($separator, $tagList);
        }

        $list = [];
        if (is_array($tagList)) {
            foreach ($tagList as $tag) {
                if (!Validate::isGenericName($tag)) {
                    return false;
                }
                $tag = trim(mb_substr($tag, 0, static::$definition['fields']['name']['size']));
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
        $insert = [];
        foreach ($list as $tag) {
            $insert[] = [
                'id_tag'     => (int) $tag,
                'id_product' => (int) $idProduct,
                'id_lang'    => (int) $idLang,
            ];
        }

        $result = Db::getInstance()->insert('product_tag', $insert);

        if ($list != []) {
            static::updateTagCount($list);
        }

        return $result;
    }

    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add($autoDate = true, $nullValues = false)
    {
        if (!parent::add($autoDate, $nullValues)) {
            return false;
        } elseif (isset($_POST['products'])) {
            return $this->setProducts(Tools::getValue('products'));
        }

        return true;
    }

    /**
     * @param array $array
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function setProducts($array)
    {
        $result = Db::getInstance()->delete('product_tag', '`id_tag` = '.(int) $this->id);
        if (is_array($array)) {
            $array = array_map('intval', $array);
            $result &= ObjectModel::updateMultishopTable('Product', ['indexed' => 0], 'a.id_product IN ('.implode(',', $array).')');
            $ids = [];
            foreach ($array as $idProduct) {
                $ids[] = [
                    'id_product' => (int) $idProduct,
                    'id_tag'     => (int) $this->id,
                    'id_lang'    => (int) $this->id_lang,
                ];
            }

            if ($result) {
                $result &= Db::getInstance()->insert('product_tag', $ids);
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
     * @throws PrestaShopException
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
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getMainTags($idLang, $nb = 10)
    {
        $context = Context::getContext();
        if (Group::isFeatureActive()) {
            $groups = FrontController::getCurrentCustomerGroups();

            return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('t.`name`, `counter` AS `times`')
                    ->from('tag_count', 'pt')
                    ->leftJoin('tag', 't', 't.`id_tag` = pt.`id_tag`')
                    ->where('pt.`id_group` '.(count($groups) ? 'IN ('.implode(',', $groups).')' : '= 1'))
                    ->where('pt.`id_lang` = '.(int) $idLang)
                    ->where('pt.`id_shop` = '.(int) $context->shop->id)
                    ->orderBy('`times` DESC')
                    ->limit((int) $nb)
            );
        } else {
            return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('t.`name`, `counter` AS `times`')
                    ->from('tag_count', 'pt')
                    ->leftJoin('tag', 't', 't.`id_tag` = pt.`id_tag`')
                    ->where('pt.`id_group` = 0')
                    ->where('pt.`id_lang` = '.(int) $idLang)
                    ->where('pt.`id_shop` = '.(int) $context->shop->id)
                    ->orderBy('`times` DESC')
                    ->limit((int) $nb)
            );
        }
    }

    /**
     * @param int $idProduct
     *
     * @return array|bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0
     */
    public static function getProductTags($idProduct)
    {
        if (!$tmp = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('t.`id_lang`, t.`name`')
                ->from('tag', 't')
                ->leftJoin('product_tag', 'pt', 'pt.`id_tag` = t.`id_tag`')
                ->where('pt.`id_product` = '.(int) $idProduct)

        )) {
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
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function deleteTagsForProduct($idProduct)
    {
        $tagsRemoved = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('`id_tag`')
                ->from('product_tag')
                ->where('`id_product` = '.(int) $idProduct)
        );
        $result = Db::getInstance()->delete('product_tag', 'id_product = '.(int) $idProduct);
        Db::getInstance()->delete('tag', 'NOT EXISTS (SELECT 1 FROM '._DB_PREFIX_.'product_tag WHERE '._DB_PREFIX_.'product_tag.id_tag = '._DB_PREFIX_.'tag.id_tag)');
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
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
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
            (new DbQuery())
                ->select('pl.`name`, pl.`id_product`')
                ->from('product', 'p')
                ->leftJoin('product_lang', 'pl', 'p.`id_product` = pl.`id_product`'.Shop::addSqlRestrictionOnLang('pl').Shop::addSqlAssociation('product', 'p').' AND pl.`id_lang` = '.(int) $idLang)
                ->where('product_shop.`active` = 1')
                ->where($this->id ? ('p.`id_product` '.$in.' (SELECT pt.`id_product` FROM `'._DB_PREFIX_.'product_tag` pt WHERE pt.`id_tag` = '.(int) $this->id.')') : '')
                ->orderBy('pl.`name`')
        );
    }
}
