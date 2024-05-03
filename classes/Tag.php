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

/**
 * Class TagCore
 */
class TagCore extends ObjectModel
{
    /**
     * @var array Object model definition
     */
    public static $definition = [
        'table'   => 'tag',
        'primary' => 'id_tag',
        'fields'  => [
            'id_lang' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'name'    => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 32],
        ],
        'keys' => [
            'tag' => [
                'id_lang'  => ['type' => ObjectModel::KEY, 'columns' => ['id_lang']],
                'tag_name' => ['type' => ObjectModel::KEY, 'columns' => ['name']],
            ],
        ],
    ];
    /** @var int Language id */
    public $id_lang;
    /** @var string Name */
    public $name;

    /**
     * @var array Webservice parameters
     */
    protected $webserviceParameters = [
        'fields' => [
            'id_lang' => ['xlink_resource' => 'languages'],
        ],
    ];

    /**
     * TagCore constructor.
     *
     * @param int|null $id
     * @param string|null $name
     * @param int|null $idLang
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function __construct($id = null, $name = null, $idLang = null)
    {
        $this->def = Tag::getDefinition($this);
        $this->setDefinitionRetrocompatibility();

        if ($id) {
            parent::__construct($id);
        } elseif ($name && Validate::isGenericName($name) && $idLang && Validate::isUnsignedId($idLang)) {
            $row = Db::readOnly()->getRow(
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
     * @param int $idLang Language id
     * @param int $idProduct Product id to link tags with
     * @param string|array $tagList List of tags, as array or as a string with comas
     * @param string $separator Separator to split a given string inot an array.
     *                                Not needed if $tagList is an array already.
     *
     * @return bool Operation success
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function addTags($idLang, $idProduct, $tagList, $separator = ',')
    {
        $idProduct = (int)$idProduct;
        $idLang = (int)$idLang;

        if (!is_array($tagList)) {
            $tagList = explode($separator, $tagList);
        }

        if (is_array($tagList) && $tagList) {
            $list = [];
            $result = true;
            foreach ($tagList as $tag) {
                if (!Validate::isGenericName($tag)) {
                    $result = false;
                } else {
                    $tag = trim(mb_substr($tag, 0, static::$definition['fields']['name']['size']));
                    $tagObj = new Tag(null, $tag, $idLang);

                    /* Tag does not exist in database */
                    if (!Validate::isLoadedObject($tagObj)) {
                        $tagObj->name = $tag;
                        $tagObj->id_lang = $idLang;
                        $tagObj->add();
                    }
                    $tagId = (int)$tagObj->id;
                    if (!in_array($tagId, $list)) {
                        $list[] = $tagId;
                    }
                }
            }

            if ($list) {
                $insert = [];
                foreach ($list as $tag) {
                    $insert[] = [
                        'id_tag' => $tag,
                        'id_product' => $idProduct,
                        'id_lang' => $idLang,
                    ];
                }

                $result = Db::getInstance()->insert('product_tag', $insert, false, true, Db::INSERT_IGNORE) && $result;
                static::updateTagCount($list);
            }
            return $result;
        }

        return true;
    }

    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
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
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function setProducts($array)
    {
        $conn = Db::getInstance();
        $result = $conn->delete('product_tag', '`id_tag` = '.(int) $this->id);
        if (is_array($array) && $array) {
            $array = array_map('intval', $array);
            $result = (
                ObjectModel::updateMultishopTable('Product', ['indexed' => 0], 'a.id_product IN ('.implode(',', $array).')') &&
                $result
            );
            $ids = [];
            foreach ($array as $idProduct) {
                $ids[] = [
                    'id_product' => (int) $idProduct,
                    'id_tag'     => (int) $this->id,
                    'id_lang'    => (int) $this->id_lang,
                ];
            }

            if ($result) {
                $result = $conn->insert('product_tag', $ids);
                if (Configuration::get('PS_SEARCH_INDEXATION')) {
                    $result = Search::indexation(false) && $result;
                }
            }
        }
        static::updateTagCount([(int) $this->id]);

        return $result;
    }

    /**
     * @param array|null $tagList
     *
     * @throws PrestaShopException
     */
    public static function updateTagCount($tagList = null)
    {
        if (!Module::getBatchMode()) {
            $conn = Db::getInstance();
            if ($tagList != null) {
                $tagListQuery = ' AND pt.id_tag IN ('.implode(',', $tagList).')';
                $conn->execute('DELETE pt FROM `'._DB_PREFIX_.'tag_count` pt WHERE 1=1 '.$tagListQuery);
            } else {
                $tagListQuery = '';
            }

            $conn->execute(
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
            $conn->execute(
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
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getMainTags($idLang, $nb = 10)
    {
        $context = Context::getContext();
        if (Group::isFeatureActive()) {
            $groups = FrontController::getCurrentCustomerGroups();
            $query = (new DbQuery())
                ->select('t.`name`, pt.`counter` AS `times`')
                ->from('tag_count', 'pt')
                ->innerJoin('tag', 't', 't.`id_tag` = pt.`id_tag`')
                ->where('pt.`id_group` '.(count($groups) ? 'IN ('.implode(',', $groups).')' : '= 1'))
                ->where('pt.`id_lang` = '.(int) $idLang)
                ->where('pt.`id_shop` = '.(int) $context->shop->id)
                ->orderBy('`times` DESC')
                ->limit((int) $nb);
        } else {
            $query = (new DbQuery())
                ->select('t.`name`, pt.`counter` AS `times`')
                ->from('tag_count', 'pt')
                ->innerJoin('tag', 't', 't.`id_tag` = pt.`id_tag`')
                ->where('pt.`id_group` = 0')
                ->where('pt.`id_lang` = '.(int) $idLang)
                ->where('pt.`id_shop` = '.(int) $context->shop->id)
                ->orderBy('`times` DESC')
                ->limit((int) $nb);
        }

        return Db::readOnly()->getArray($query);
    }

    /**
     * @param int $idProduct
     *
     * @return array|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getProductTags($idProduct)
    {
        if (!$tmp = Db::readOnly()->getArray(
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
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function deleteTagsForProduct($idProduct)
    {
        $tagsRemoved = Db::readOnly()->getArray(
            (new DbQuery())
                ->select('`id_tag`')
                ->from('product_tag')
                ->where('`id_product` = '.(int) $idProduct)
        );
        $conn = Db::getInstance();
        $result = $conn->delete('product_tag', 'id_product = '.(int) $idProduct);
        $conn->delete('tag', 'NOT EXISTS (SELECT 1 FROM '._DB_PREFIX_.'product_tag WHERE '._DB_PREFIX_.'product_tag.id_tag = '._DB_PREFIX_.'tag.id_tag)');
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
     * @param bool $associated
     * @param Context|null $context
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
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

        return Db::readOnly()->getArray(
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
