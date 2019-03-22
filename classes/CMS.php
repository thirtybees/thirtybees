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
 * Class CMSCore
 *
 * @since 1.0.0
 */
class CMSCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'          => 'cms',
        'primary'        => 'id_cms',
        'multilang'      => true,
        'multilang_shop' => true,
        'fields'         => [
            'id_cms_category'  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'position'         => ['type' => self::TYPE_INT],
            'indexation'       => ['type' => self::TYPE_BOOL],
            'active'           => ['type' => self::TYPE_BOOL],

            /* Lang fields */
            'meta_description' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName',                     'size' => 255],
            'meta_keywords'    => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName',                     'size' => 255],
            'meta_title'       => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 128],
            'link_rewrite'     => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isLinkRewrite', 'required' => true, 'size' => 128],
            'content'          => ['type' => self::TYPE_HTML,   'lang' => true, 'validate' => 'isCleanHtml',                       'size' => 3999999999999],
        ],
    ];
    /** @var string Name */
    public $meta_title;
    public $meta_description;
    public $meta_keywords;
    public $content;
    public $link_rewrite;
    public $id_cms_category;
    public $position;
    public $indexation;
    // @codingStandardsIgnoreEnd
    public $active;
    protected $webserviceParameters = [
        'objectNodeName'  => 'content',
        'objectsNodeName' => 'content_management_system',
    ];

    /**
     * @param int        $idLang
     * @param array|null $selection
     * @param bool       $active
     * @param Link|null  $link
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getLinks($idLang, $selection = null, $active = true, Link $link = null)
    {
        if (!$link) {
            $link = Context::getContext()->link;
        }
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('c.`id_cms`, cl.`link_rewrite`, cl.`meta_title`')
                ->from('cms', 'c')
                ->leftJoin('cms_lang', 'cl', 'c.`id_cms` = cl.`id_cms` AND cl.`id_lang` = '.(int) $idLang)
                ->join(Shop::addSqlAssociation('cms', 'c'))
                ->where($selection !== null ? 'c.`id_cms` IN ('.implode(',', array_map('intval', $selection)).')' : '')
                ->where($active ? 'c.`active` = 1 ' : '')
                ->groupBy('c.`id_cms`')
                ->orderBy('c.`position`')
        );

        $links = [];
        if ($result) {
            foreach ($result as $row) {
                $row['link'] = $link->getCMSLink((int) $row['id_cms'], $row['link_rewrite']);
                $links[] = $row;
            }
        }

        return $links;
    }

    /**
     * @param int|null $idLang
     * @param bool     $idBlock
     * @param bool     $active
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function listCms($idLang = null, $idBlock = false, $active = true)
    {
        if (empty($idLang)) {
            $idLang = (int) Configuration::get('PS_LANG_DEFAULT');
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('c.`id_cms`, l.`meta_title`')
                ->from('cms', 'c')
                ->innerJoin('cms_lang', 'l', 'c.`id_cms` = l.`id_cms`')
                ->join($idBlock ? 'JOIN `'._DB_PREFIX_.'block_cms` b ON (c.`id_cms` = b.`id_cms`)' : '')
                ->where('l.`id_lang` = '.(int) $idLang)
                ->where($idBlock ? 'b.`id_block` = '.(int) $idBlock : '')
                ->where($active ? 'c.`active` = 1' : '')
                ->groupBy('c.`id_cms`')
                ->orderBy('c.`position`')
        );
    }

    /**
     * @param int|null $idLang
     * @param int|null $idCmsCategory
     * @param bool     $active
     * @param int|null $idShop
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getCMSPages($idLang = null, $idCmsCategory = null, $active = true, $idShop = null)
    {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('cms', 'c');

        if ($idLang) {
            if ($idShop) {
                $sql->innerJoin('cms_lang', 'l', 'c.`id_cms` = l.`id_cms` AND l.`id_lang` = '.(int) $idLang.' AND l.`id_shop` = '.(int) $idShop);
            } else {
                $sql->innerJoin('cms_lang', 'l', 'c.`id_cms` = l.`id_cms` AND l.`id_lang` = '.(int) $idLang);
            }
        }

        if ($idShop) {
            $sql->innerJoin('cms_shop', 'cs', 'c.`id_cms` = cs.`id_cms` AND cs.`id_shop` = '.(int) $idShop);
        }

        if ($active) {
            $sql->where('c.`active` = 1');
        }

        if ($idCmsCategory) {
            $sql->where('c.`id_cms_category` = '.(int) $idCmsCategory);
        }

        $sql->orderBy('position');

        return Db::getInstance()->executeS($sql);
    }

    /**
     * @param int $idCms
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getUrlRewriteInformations($idCms)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('l.`id_lang`, c.`link_rewrite`')
                ->from('cms_lang', 'c')
                ->leftJoin('lang', 'l', 'c.`id_lang` = l.`id_lang`')
                ->where('c.`id_cms` = '.(int) $idCms)
                ->where('l.`active` = 1')
        );
    }

    /**
     * @param int      $idCms
     * @param int|null $idLang
     * @param int|null $idShop
     *
     * @return array|bool|null|object
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getCMSContent($idCms, $idLang = null, $idShop = null)
    {
        if (is_null($idLang)) {
            $idLang = (int) Configuration::get('PS_LANG_DEFAULT');
        }
        if (is_null($idShop)) {
            $idShop = (int) Configuration::get('PS_SHOP_DEFAULT');
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('`content`')
                ->from('cms_lang')
                ->where('`id_cms` = '.(int) $idCms)
                ->where('`id_lang` = '.(int) $idLang)
                ->where('`id_shop` = '.(int) $idShop)
        );
    }

    /**
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getRepositoryClassName()
    {
        return 'Core_Business_CMS_CMSRepository';
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
        $this->position = CMS::getLastPosition((int) $this->id_cms_category);

        return parent::add($autoDate, true);
    }

    /**
     * @param int $idCategory
     *
     * @return false|null|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getLastPosition($idCategory)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('MAX(`position`) + 1')
                ->from('cms')
                ->where('`id_cms_category` = '.(int) $idCategory)
        );
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
            PageCache::invalidateEntity('cms', $this->id);
        }

        if (parent::update($nullValues)) {
            return $this->cleanPositions($this->id_cms_category);
        }

        return false;
    }

    /**
     * @param int $idCategory
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function cleanPositions($idCategory)
    {
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('`id_cms`')
                ->from('cms')
                ->where('`id_cms_category` = '.(int) $idCategory)
                ->orderBy('`position`')
        );

        for ($i = 0, $total = count($result); $i < $total; ++$i) {
            Db::getInstance()->update(
                'cms',
                [
                    'position' => (int) $i,
                ],
                '`id_cms_category` = '.(int) $idCategory.' AND `id_cms` = '.(int) $result[$i]['id_cms']
            );
        }

        return true;
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
        if (PageCache::isEnabled()) {
            PageCache::invalidateEntity('cms', $this->id);
        }

        if (parent::delete()) {
            return $this->cleanPositions($this->id_cms_category);
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
            (new DbQuery())
                ->select('cp.`id_cms`, cp.`position`, cp.`id_cms_category`')
                ->from('cms', 'cp')
                ->where('cp.`id_cms_category` = '.(int) $this->id_cms_category)
                ->orderBy('cp.`position` ASC')
        )) {
            return false;
        }

        foreach ($res as $cms) {
            if ((int) $cms['id_cms'] == (int) $this->id) {
                $movedCms = $cms;
            }
        }

        if (!isset($movedCms) || !isset($position)) {
            return false;
        }

        // < and > statements rather than BETWEEN operator
        // since BETWEEN is treated differently according to databases
        return (Db::getInstance()->update(
            'cms',
            [
                'position' => ['type' => 'sql', 'value' => '`position` '.($way ? '- 1' : '+ 1')],
            ],
            '`position` '.($way ? '> '.(int) $movedCms['position'].' AND `position` <= '.(int) $position : '< '.(int) $movedCms['position'].' AND `position` >= '.(int) $position).' AND `id_cms_category`='.(int) $movedCms['id_cms_category']
        ) && Db::getInstance()->update(
            'cms',
            [
                'position' => (int) $position,
            ],
            '`id_cms` = '.(int) $movedCms['id_cms'].' AND `id_cms_category`='.(int) $movedCms['id_cms_category']
        ));
    }
}
