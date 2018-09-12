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
 * Class CMSCategoryCore
 *
 * @since 1.0.0
 */
class CMSCategoryCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'          => 'cms_category',
        'primary'        => 'id_cms_category',
        'multilang'      => true,
        'multilang_shop' => true,
        'fields'         => [
            'active'           => ['type' => self::TYPE_BOOL, 'validate' => 'isBool',        'required' => true],
            'id_parent'        => ['type' => self::TYPE_INT,  'validate' => 'isUnsignedInt', 'required' => true],
            'position'         => ['type' => self::TYPE_INT],
            'level_depth'      => ['type' => self::TYPE_INT],
            'date_add'         => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd'         => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],

            /* Lang fields */
            'name'             => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCatalogName', 'required' => true, 'size' => 64],
            'link_rewrite'     => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isLinkRewrite', 'required' => true, 'size' => 64],
            'description'      => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml'],
            'meta_title'       => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 128],
            'meta_description' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255],
            'meta_keywords'    => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255],
        ],
    ];
    protected static $_links = [];
    /** @var int CMSCategory ID */
    public $id_cms_category;
    /** @var string Name */
    public $name;
    /** @var bool Status for display */
    public $active = 1;
    /** @var string Description */
    public $description;
    /** @var int Parent CMSCategory ID */
    public $id_parent;
    /** @var  int category position */
    public $position;
    /** @var int Parents number */
    public $level_depth;
    /** @var string string used in rewrited URL */
    public $link_rewrite;
    /** @var string Meta title */
    public $meta_title;
    /** @var string Meta keywords */
    public $meta_keywords;
    /** @var string Meta description */
    public $meta_description;
    /** @var string Object creation date */
    public $date_add;

    /** @var string Object last modification date */
    public $date_upd;
    // @codingStandardsIgnoreEnd

    /**
     * @param null      $idLang
     * @param int       $current
     * @param int       $active
     * @param int       $links
     * @param Link|null $link
     *
     * @return array|bool|null|object
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getRecurseCategory($idLang = null, $current = 1, $active = 1, $links = 0, Link $link = null)
    {
        if (!$link) {
            $link = Context::getContext()->link;
        }
        if (is_null($idLang)) {
            $idLang = Context::getContext()->language->id;
        }

        $category = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('c.`id_cms_category`, c.`id_parent`, c.`level_depth`, cl.`name`, cl.`link_rewrite`')
                ->from('cms_category', 'c')
                ->innerJoin('cms_category_lang', 'cl', 'c.`id_cms_category` = cl.`id_cms_category`')
                ->where('c.`id_cms_category` = '.(int) $current)
                ->where('`id_lang` = '.(int) $idLang)
        );

        $sql = 'SELECT c.`id_cms_category`
				FROM `'._DB_PREFIX_.'cms_category` c
				WHERE c.`id_parent` = '.(int) $current.
            ($active ? ' AND c.`active` = 1' : '');
        $result = Db::getInstance()->executeS($sql);
        foreach ($result as $row) {
            $category['children'][] = CMSCategory::getRecurseCategory($idLang, $row['id_cms_category'], $active, $links);
        }

        $sql = 'SELECT c.`id_cms`, cl.`meta_title`, cl.`link_rewrite`
				FROM `'._DB_PREFIX_.'cms` c
				'.Shop::addSqlAssociation('cms', 'c').'
				JOIN `'._DB_PREFIX_.'cms_lang` cl ON c.`id_cms` = cl.`id_cms`
				WHERE `id_cms_category` = '.(int) $current.'
				AND cl.`id_lang` = '.(int) $idLang.($active ? ' AND c.`active` = 1' : '').'
				GROUP BY c.id_cms
				ORDER BY c.`position`';
        $category['cms'] = Db::getInstance()->executeS($sql);
        if ($links == 1) {
            $category['link'] = $link->getCMSCategoryLink($current, $category['link_rewrite']);
            foreach ($category['cms'] as $key => $cms) {
                $category['cms'][$key]['link'] = $link->getCMSLink($cms['id_cms'], $cms['link_rewrite']);
            }
        }

        return $category;
    }

    /**
     * @param array $categories
     * @param array $current
     * @param int   $idCmsCategory
     * @param int   $idSelected
     * @param bool  $isHtml
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function recurseCMSCategory($categories, $current, $idCmsCategory = 1, $idSelected = 1, $isHtml = false)
    {
        $html = '<option value="'.$idCmsCategory.'"'.(($idSelected == $idCmsCategory) ? ' selected="selected"' : '').'>'
            .str_repeat('&nbsp;', $current['infos']['level_depth'] * 5)
            .CMSCategory::hideCMSCategoryPosition(stripslashes($current['infos']['name'])).'</option>';
        if (!$isHtml) {
            echo $html;
        }
        if (isset($categories[$idCmsCategory])) {
            foreach (array_keys($categories[$idCmsCategory]) as $key) {
                $html .= CMSCategory::recurseCMSCategory($categories, $categories[$idCmsCategory][$key], $key, $idSelected, $isHtml);
            }
        }

        return $html;
    }

    /**
     * Return available categories
     *
     * @param int  $idLang Language ID
     * @param bool $active return only active categories
     * @param bool $order
     *
     * @return array Categories
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getCategories($idLang, $active = true, $order = true)
    {
        if (!Validate::isBool($active)) {
            die(Tools::displayError());
        }

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            '
		SELECT *
		FROM `'._DB_PREFIX_.'cms_category` c
		LEFT JOIN `'._DB_PREFIX_.'cms_category_lang` cl ON c.`id_cms_category` = cl.`id_cms_category`
		WHERE `id_lang` = '.(int) $idLang.'
		'.($active ? 'AND `active` = 1' : '').'
		ORDER BY `name` ASC'
        );

        if (!$order) {
            return $result;
        }

        $categories = [];
        foreach ($result as $row) {
            $categories[$row['id_parent']][$row['id_cms_category']]['infos'] = $row;
        }

        return $categories;
    }

    /**
     * @param int $idLang
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getSimpleCategories($idLang)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            '
		SELECT c.`id_cms_category`, cl.`name`
		FROM `'._DB_PREFIX_.'cms_category` c
		LEFT JOIN `'._DB_PREFIX_.'cms_category_lang` cl ON (c.`id_cms_category` = cl.`id_cms_category`)
		WHERE cl.`id_lang` = '.(int) $idLang.'
		ORDER BY cl.`name`'
        );
    }

    /**
     * Return main categories
     *
     * @param int  $idLang Language ID
     * @param bool $active return only active categories
     *
     * @return array categories
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getHomeCategories($idLang, $active = true)
    {
        return CMSCategory::getChildren(1, $idLang, $active);
    }

    /**
     * @param int  $idParent
     * @param int  $idLang
     * @param bool $active
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getChildren($idParent, $idLang, $active = true)
    {
        if (!Validate::isBool($active)) {
            die(Tools::displayError());
        }

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            '
		SELECT c.`id_cms_category`, cl.`name`, cl.`link_rewrite`
		FROM `'._DB_PREFIX_.'cms_category` c
		LEFT JOIN `'._DB_PREFIX_.'cms_category_lang` cl ON c.`id_cms_category` = cl.`id_cms_category`
		WHERE `id_lang` = '.(int) $idLang.'
		AND c.`id_parent` = '.(int) $idParent.'
		'.($active ? 'AND `active` = 1' : '').'
		ORDER BY `name` ASC'
        );

        // Modify SQL result
        $resultsArray = [];
        foreach ($result as $row) {
            $row['name'] = CMSCategory::hideCMSCategoryPosition($row['name']);
            $resultsArray[] = $row;
        }

        return $resultsArray;
    }

    /**
     * Check if CMSCategory can be moved in another one
     *
     * @param int $idCmsCategory
     * @param int $idParent Parent candidate
     *
     * @return bool Parent validity
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function checkBeforeMove($idCmsCategory, $idParent)
    {
        if ($idCmsCategory == $idParent) {
            return false;
        }
        if ($idParent == 1) {
            return true;
        }
        $i = (int) $idParent;

        while (42) {
            $result = Db::getInstance()->getRow('SELECT `id_parent` FROM `'._DB_PREFIX_.'cms_category` WHERE `id_cms_category` = '.(int) $i);
            if (!isset($result['id_parent'])) {
                return false;
            }
            if ($result['id_parent'] == $idCmsCategory) {
                return false;
            }
            if ($result['id_parent'] == 1) {
                return true;
            }
            $i = $result['id_parent'];
        }
    }

    /**
     * @param int $idCmsCategory
     * @param int $idLang
     *
     * @return bool|mixed
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getLinkRewrite($idCmsCategory, $idLang)
    {
        if (!Validate::isUnsignedId($idCmsCategory) || !Validate::isUnsignedId($idLang)) {
            return false;
        }

        if (isset(static::$_links[$idCmsCategory.'-'.$idLang])) {
            return static::$_links[$idCmsCategory.'-'.$idLang];
        }

        $result = Db::getInstance()->getRow(
            '
		SELECT cl.`link_rewrite`
		FROM `'._DB_PREFIX_.'cms_category` c
		LEFT JOIN `'._DB_PREFIX_.'cms_category_lang` cl ON c.`id_cms_category` = cl.`id_cms_category`
		WHERE `id_lang` = '.(int) $idLang.'
		AND c.`id_cms_category` = '.(int) $idCmsCategory
        );
        static::$_links[$idCmsCategory.'-'.$idLang] = $result['link_rewrite'];

        return $result['link_rewrite'];
    }

    /**
     * Light back office search for categories
     *
     * @param int    $idLang       Language ID
     * @param string $query        Searched string
     * @param bool   $unrestricted allows search without lang and includes first CMSCategory and exact match
     *
     * @return array Corresponding categories
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function searchByName($idLang, $query, $unrestricted = false)
    {
        if ($unrestricted === true) {
            return Db::getInstance()->getRow(
                '
			SELECT c.*, cl.*
			FROM `'._DB_PREFIX_.'cms_category` c
			LEFT JOIN `'._DB_PREFIX_.'cms_category_lang` cl ON (c.`id_cms_category` = cl.`id_cms_category`)
			WHERE `name` = \''.pSQL($query).'\''
            );
        } else {
            return Db::getInstance()->executeS(
                '
			SELECT c.*, cl.*
			FROM `'._DB_PREFIX_.'cms_category` c
			LEFT JOIN `'._DB_PREFIX_.'cms_category_lang` cl ON (c.`id_cms_category` = cl.`id_cms_category` AND `id_lang` = '.(int) $idLang.')
			WHERE `name` LIKE \'%'.pSQL($query).'%\' AND c.`id_cms_category` != 1'
            );
        }
    }

    /**
     * Retrieve CMSCategory by name and parent CMSCategory id
     *
     * @param int    $idLang              Language ID
     * @param string $cmsCategoryName     Searched CMSCategory name
     * @param int    $idParentCmsCategory parent CMSCategory ID
     *
     * @return array Corresponding CMSCategory
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @deprecated 1.0.0
     */
    public static function searchByNameAndParentCMSCategoryId($idLang, $cmsCategoryName, $idParentCmsCategory)
    {
        Tools::displayAsDeprecated();

        return Db::getInstance()->getRow(
            '
		SELECT c.*, cl.*
	    FROM `'._DB_PREFIX_.'cms_category` c
	    LEFT JOIN `'._DB_PREFIX_.'cms_category_lang` cl ON (c.`id_cms_category` = cl.`id_cms_category` AND `id_lang` = '.(int) $idLang.')
	    WHERE `name` = \''.pSQL($cmsCategoryName).'\'
		AND c.`id_cms_category` != 1
		AND c.`id_parent` = '.(int) $idParentCmsCategory
        );
    }

    /**
     * @param int $idCategory
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getUrlRewriteInformations($idCategory)
    {
        return Db::getInstance()->executeS(
            (new DbQuery())
                ->select('l.`id_lang`, c.`link_rewrite`')
                ->from('cms_category_lang', 'c')
                ->leftJoin('lang', 'l', 'c.`id_lang` = l.`id_lang`')
                ->where('c.`id_cms_category` = '.(int) $idCategory)
                ->where('l.`active` = 1')
        );
    }

    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function add($autoDate = true, $nullValues = false)
    {
        $this->position = CMSCategory::getLastPosition((int) $this->id_parent);
        $this->level_depth = $this->calcLevelDepth();
        foreach ($this->name as $k => $value) {
            if (preg_match('/^[1-9]\./', $value)) {
                $this->name[$k] = '0'.$value;
            }
        }
        $ret = parent::add($autoDate, $nullValues);
        $this->cleanPositions($this->id_parent);

        return $ret;
    }

    /**
     * @param int $idCategoryParent
     *
     * @return false|null|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getLastPosition($idCategoryParent)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('MAX(`position`)')
                ->from('cms_category')
                ->where('`id_parent` = '.(int) $idCategoryParent)
        );
    }

    /**
     * Get the number of parent categories
     *
     * @return int Level depth
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function calcLevelDepth()
    {
        $parentCMSCategory = new CMSCategory($this->id_parent);
        if (!$parentCMSCategory) {
            die('parent CMS Category does not exist');
        }

        return $parentCMSCategory->level_depth + 1;
    }

    /**
     * @param int $idCategoryParent
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function cleanPositions($idCategoryParent)
    {
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('`id_cms_category`')
                ->from('cms_category')
                ->where('`id_parent` = '.(int) $idCategoryParent)
                ->orderBy('`position`')
        );
        $sizeof = count($result);
        for ($i = 0; $i < $sizeof; ++$i) {
            $sql = '
			UPDATE `'._DB_PREFIX_.'cms_category`
			SET `position` = '.(int) $i.'
			WHERE `id_parent` = '.(int) $idCategoryParent.'
			AND `id_cms_category` = '.(int) $result[$i]['id_cms_category'];
            Db::getInstance()->execute($sql);
        }

        return true;
    }

    /**
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function update($nullValues = false)
    {
        if (PageCache::isEnabled()) {
            PageCache::invalidateEntity('cms_category', $this->id);
        }

        $this->level_depth = $this->calcLevelDepth();
        foreach ($this->name as $k => $value) {
            if (preg_match('/^[1-9]\./', $value)) {
                $this->name[$k] = '0'.$value;
            }
        }
        $return = parent::update($nullValues);

        return $return;
    }

    /**
     * Recursive scan of subcategories
     *
     * @param int       $maxDepth         Maximum depth of the tree (i.e. 2 => 3 levels depth)
     * @param int       $currentDepth     specify the current depth in the tree (don't use it, only for rucursivity!)
     * @param int       $idLang           Specify the id of the language used
     * @param array     $excludedIdsArray specify a list of ids to exclude of results
     * @param Link|null $link
     *
     * @return array Subcategories lite tree
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function recurseLiteCategTree($maxDepth = 3, $currentDepth = 0, $idLang = null, $excludedIdsArray = null, Link $link = null)
    {
        if (!$link) {
            $link = Context::getContext()->link;
        }

        if (is_null($idLang)) {
            $idLang = Context::getContext()->language->id;
        }

        // recursivity for subcategories
        $children = [];
        $subcats = $this->getSubCategories($idLang, true);
        if (($maxDepth == 0 || $currentDepth < $maxDepth) && $subcats && count($subcats)) {
            foreach ($subcats as &$subcat) {
                if (!$subcat['id_cms_category']) {
                    break;
                } elseif (!is_array($excludedIdsArray) || !in_array($subcat['id_cms_category'], $excludedIdsArray)) {
                    $categ = new CMSCategory($subcat['id_cms_category'], $idLang);
                    $categ->name = CMSCategory::hideCMSCategoryPosition($categ->name);
                    $children[] = $categ->recurseLiteCategTree($maxDepth, $currentDepth + 1, $idLang, $excludedIdsArray);
                }
            }
        }

        return [
            'id'       => $this->id_cms_category,
            'link'     => $link->getCMSCategoryLink($this->id, $this->link_rewrite),
            'name'     => $this->name,
            'desc'     => $this->description,
            'children' => $children,
        ];
    }

    /**
     * Return current CMSCategory childs
     *
     * @param int  $idLang Language ID
     * @param bool $active return only active categories
     *
     * @return array Categories
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getSubCategories($idLang, $active = true)
    {
        if (!Validate::isBool($active)) {
            die(Tools::displayError());
        }

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            '
		SELECT c.*, cl.id_lang, cl.name, cl.description, cl.link_rewrite, cl.meta_title, cl.meta_keywords, cl.meta_description
		FROM `'._DB_PREFIX_.'cms_category` c
		LEFT JOIN `'._DB_PREFIX_.'cms_category_lang` cl ON (c.`id_cms_category` = cl.`id_cms_category` AND `id_lang` = '.(int) $idLang.')
		WHERE `id_parent` = '.(int) $this->id.'
		'.($active ? 'AND `active` = 1' : '').'
		GROUP BY c.`id_cms_category`
		ORDER BY `name` ASC'
        );

        // Modify SQL result
        foreach ($result as &$row) {
            $row['name'] = CMSCategory::hideCMSCategoryPosition($row['name']);
        }

        return $result;
    }

    /**
     * Hide CMSCategory prefix used for position
     *
     * @param string $name CMSCategory name
     *
     * @return string Name without position
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function hideCMSCategoryPosition($name)
    {
        return preg_replace('/^[0-9]+\./', '', $name);
    }

    /**
     * Delete several categories from database
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     *
     * @param array $categories
     *
     * @return bool|int
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function deleteSelection($categories)
    {
        $return = true;
        foreach ($categories as $idCategoryCms) {
            $categoryCms = new CMSCategory($idCategoryCms);
            $return &= $categoryCms->delete();
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
    public function delete()
    {
        if ($this->id == 1) {
            return false;
        }

        if (PageCache::isEnabled()) {
            PageCache::invalidateEntity('cms', $this->id);
        }

        $this->clearCache();

        // Get children categories
        $toDelete = [(int) $this->id];
        $this->recursiveDelete($toDelete, (int) $this->id);
        $toDelete = array_unique($toDelete);

        // Delete CMS Category and its child from database
        $list = count($toDelete) > 1 ? implode(',', $toDelete) : (int) $this->id;
        $idShopList = Shop::getContextListShopID();
        if (count($this->id_shop_list)) {
            $idShopList = $this->id_shop_list;
        }

        Db::getInstance()->delete($this->def['table'].'_shop', '`'.$this->def['primary'].'` IN ('.$list.') AND id_shop IN ('.implode(', ', $idShopList).')');

        $hasMultishopEntries = $this->hasMultishopEntries();
        if (!$hasMultishopEntries) {
            Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'cms_category` WHERE `id_cms_category` IN ('.$list.')');
            Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'cms_category_lang` WHERE `id_cms_category` IN ('.$list.')');
        }

        CMSCategory::cleanPositions($this->id_parent);

        // Delete pages which are in categories to delete
        $result = Db::getInstance()->executeS(
            '
		SELECT `id_cms`
		FROM `'._DB_PREFIX_.'cms`
		WHERE `id_cms_category` IN ('.$list.')'
        );
        foreach ($result as $c) {
            $cms = new CMS((int) $c['id_cms']);
            if (Validate::isLoadedObject($cms)) {
                $cms->delete();
            }
        }

        return true;
    }

    /**
     * Recursively add specified CMSCategory childs to $toDelete array
     *
     * @param array     &$toDelete     Array reference where categories ID will be saved
     * @param array|int $idCmsCategory Parent CMSCategory ID
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function recursiveDelete(&$toDelete, $idCmsCategory)
    {
        if (!is_array($toDelete) || !$idCmsCategory) {
            die(Tools::displayError());
        }

        $result = Db::getInstance()->executeS(
            '
		SELECT `id_cms_category`
		FROM `'._DB_PREFIX_.'cms_category`
		WHERE `id_parent` = '.(int) $idCmsCategory
        );
        foreach ($result as $row) {
            $toDelete[] = (int) $row['id_cms_category'];
            $this->recursiveDelete($toDelete, (int) $row['id_cms_category']);
        }
    }

    /**
     * @param Link|null $link
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getLink(Link $link = null)
    {
        if (!$link) {
            $link = Context::getContext()->link;
        }

        return $link->getCMSCategoryLink($this->id, $this->link_rewrite);
    }

    /**
     * @param int|null $idLang
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getName($idLang = null)
    {
        $context = Context::getContext();
        if (!$idLang) {
            if (isset($this->name[$context->language->id])) {
                $idLang = $context->language->id;
            } else {
                $idLang = (int) Configuration::get('PS_LANG_DEFAULT');
            }
        }

        return isset($this->name[$idLang]) ? $this->name[$idLang] : '';
    }

    /**
     * Get Each parent CMSCategory of this CMSCategory until the root CMSCategory
     *
     * @param int $idLang Language ID
     *
     * @return false|array Corresponding categories
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getParentsCategories($idLang = null)
    {
        if (is_null($idLang)) {
            $idLang = Context::getContext()->language->id;
        }

        $categories = null;
        $idCurrent = $this->id;
        while (true) {
            $query = '
				SELECT c.*, cl.*
				FROM `'._DB_PREFIX_.'cms_category` c
				LEFT JOIN `'._DB_PREFIX_.'cms_category_lang` cl ON (c.`id_cms_category` = cl.`id_cms_category` AND `id_lang` = '.(int) $idLang.')
				WHERE c.`id_cms_category` = '.(int) $idCurrent.' AND c.`id_parent` != 0
			';
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);

            $categories[] = $result[0];
            if (!$result || $result[0]['id_parent'] == 1) {
                return $categories;
            }
            $idCurrent = $result[0]['id_parent'];
        }

        return false;
    }

    /**
     * @param bool $way
     * @param int  $position
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function updatePosition($way, $position)
    {
        if (!$res = Db::getInstance()->executeS(
            '
			SELECT cp.`id_cms_category`, cp.`position`, cp.`id_parent`
			FROM `'._DB_PREFIX_.'cms_category` cp
			WHERE cp.`id_parent` = '.(int) $this->id_parent.'
			ORDER BY cp.`position` ASC'
        )
        ) {
            return false;
        }
        foreach ($res as $category) {
            if ((int) $category['id_cms_category'] == (int) $this->id) {
                $movedCategory = $category;
            }
        }

        if (!isset($movedCategory) || !isset($position)) {
            return false;
        }
        // < and > statements rather than BETWEEN operator
        // since BETWEEN is treated differently according to databases
        return (Db::getInstance()->execute(
                '
			UPDATE `'._DB_PREFIX_.'cms_category`
			SET `position`= `position` '.($way ? '- 1' : '+ 1').'
			WHERE `position`
			'.($way
                    ? '> '.(int) $movedCategory['position'].' AND `position` <= '.(int) $position
                    : '< '.(int) $movedCategory['position'].' AND `position` >= '.(int) $position).'
			AND `id_parent`='.(int) $movedCategory['id_parent']
            )
            && Db::getInstance()->execute(
                '
			UPDATE `'._DB_PREFIX_.'cms_category`
			SET `position` = '.(int) $position.'
			WHERE `id_parent` = '.(int) $movedCategory['id_parent'].'
			AND `id_cms_category`='.(int) $movedCategory['id_cms_category']
            ));
    }
}
