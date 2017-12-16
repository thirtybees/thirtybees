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
 * Class SceneCore
 *
 * @since 1.0.0
 */
class SceneCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    protected static $feature_active = null;
    /** @var string Name */
    public $name;
    /** @var bool Active Scene */
    public $active = true;
    /** @var array Zone for image map */
    public $zones = [];
    /** @var array list of category where this scene is available */
    public $categories = [];
    /** @var array Products */
    public $products;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'     => 'scene',
        'primary'   => 'id_scene',
        'multilang' => true,
        'fields'    => [
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true],

            /* Lang fields */
            'name'   => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 100],
        ],
    ];

    /**
     * SceneCore constructor.
     *
     * @param null $id
     * @param null $idLang
     * @param bool $liteResult
     * @param bool $hideScenePosition
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function __construct($id = null, $idLang = null, $liteResult = true, $hideScenePosition = false)
    {
        parent::__construct($id, $idLang);

        if (!$liteResult) {
            $this->products = $this->getProducts(true, (int) $idLang, false);
        }
        if ($hideScenePosition) {
            $this->name = Scene::hideScenePosition($this->name);
        }
        $this->image_dir = _PS_SCENE_IMG_DIR_;
    }

    /**
     * Get all products of this scene
     *
     * @return array Products
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getProducts($onlyActive = true, $idLang = null, $liteResult = true, Context $context = null)
    {
        if (!Scene::isFeatureActive()) {
            return [];
        }

        if (!$context) {
            $context = Context::getContext();
        }
        $idLang = is_null($idLang) ? $context->language->id : $idLang;

        $products = Db::getInstance()->executeS(
            '
		SELECT s.*
		FROM `'._DB_PREFIX_.'scene_products` s
		LEFT JOIN `'._DB_PREFIX_.'product` p ON (p.id_product = s.id_product)
		'.Shop::addSqlAssociation('product', 'p').'
		WHERE s.id_scene = '.(int) $this->id.($onlyActive ? ' AND product_shop.active = 1' : '')
        );

        if (!$liteResult && $products) {
            foreach ($products as &$product) {
                $product['details'] = new Product($product['id_product'], !$liteResult, $idLang);
                if (Validate::isLoadedObject($product['details'])) {
                    $product['link'] = $context->link->getProductLink(
                        $product['details']->id,
                        $product['details']->link_rewrite,
                        $product['details']->category,
                        $product['details']->ean13
                    );
                    $cover = Product::getCover($product['details']->id);
                    if (is_array($cover)) {
                        $product = array_merge($cover, $product);
                    }
                }
            }
        }

        return $products;
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
        return Configuration::get('PS_SCENE_FEATURE_ACTIVE');
    }

    /**
     * Hide scene prefix used for position
     *
     * @param string $name Scene name
     *
     * @return string Name without position
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function hideScenePosition($name)
    {
        return preg_replace('/^[0-9]+\./', '', $name);
    }

    /**
     * Get all scenes of a category
     *
     * @return array Products
     */
    public static function getScenes(
        $idCategory,
        $idLang = null,
        $onlyActive = true,
        $liteResult = true,
        $hideScenePosition = true,
        Context $context = null
    ) {
        if (!Scene::isFeatureActive()) {
            return [];
        }

        $cacheKey = 'Scene::getScenes'.$idCategory.(int) $liteResult;
        if (!Cache::isStored($cacheKey)) {
            if (!$context) {
                $context = Context::getContext();
            }
            $idLang = is_null($idLang) ? $context->language->id : $idLang;

            $sql = 'SELECT s.*
					FROM `'._DB_PREFIX_.'scene_category` sc
					LEFT JOIN `'._DB_PREFIX_.'scene` s ON (sc.id_scene = s.id_scene)
					'.Shop::addSqlAssociation('scene', 's').'
					LEFT JOIN `'._DB_PREFIX_.'scene_lang` sl ON (sl.id_scene = s.id_scene)
					WHERE sc.id_category = '.(int) $idCategory.'
						AND sl.id_lang = '.(int) $idLang
                .($onlyActive ? ' AND s.active = 1' : '').'
					ORDER BY sl.name ASC';
            $scenes = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

            if (!$liteResult && $scenes) {
                foreach ($scenes as &$scene) {
                    $scene = new Scene($scene['id_scene'], $idLang, false, $hideScenePosition);
                }
            }
            Cache::store($cacheKey, $scenes);
        } else {
            $scenes = Cache::retrieve($cacheKey);
        }

        return $scenes;
    }

    /**
     * Get categories where scene is indexed
     *
     * @param int $idScene Scene id
     *
     * @return array Categories where scene is indexed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getIndexedCategories($idScene)
    {
        return Db::getInstance()->executeS(
            '
		SELECT `id_category`
		FROM `'._DB_PREFIX_.'scene_category`
		WHERE `id_scene` = '.(int) $idScene
        );
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
        if (!$this->updateZoneProducts()) {
            return false;
        }
        if (!$this->updateCategories()) {
            return false;
        }

        if (parent::update($nullValues)) {
            // Refresh cache of feature detachable
            Configuration::updateGlobalValue('PS_SCENE_FEATURE_ACTIVE', Scene::isCurrentlyUsed($this->def['table'], true));

            return true;
        }

        return false;
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function updateZoneProducts()
    {
        if (!$this->deleteZoneProducts()) {
            return false;
        }
        if ($this->zones && !$this->addZoneProducts($this->zones)) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function deleteZoneProducts()
    {
        return Db::getInstance()->execute(
            '
		DELETE FROM `'._DB_PREFIX_.'scene_products`
		WHERE `id_scene` = '.(int) $this->id
        );
    }

    /**
     * @param $zones
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function addZoneProducts($zones)
    {
        $data = [];
        foreach ($zones as $zone) {
            $data[] = [
                'id_scene'    => (int) $this->id,
                'id_product'  => (int) $zone['id_product'],
                'x_axis'      => (int) $zone['x1'],
                'y_axis'      => (int) $zone['y1'],
                'zone_width'  => (int) $zone['width'],
                'zone_height' => (int) $zone['height'],
            ];
        }

        return Db::getInstance()->insert('scene_products', $data);
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function updateCategories()
    {
        if (!$this->deleteCategories()) {
            return false;
        }
        if (!empty($this->categories) && !$this->addCategories($this->categories)) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function deleteCategories()
    {
        return Db::getInstance()->execute(
            '
		DELETE FROM `'._DB_PREFIX_.'scene_category`
		WHERE `id_scene` = '.(int) $this->id
        );
    }

    /**
     * @param $categories
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function addCategories($categories)
    {
        $data = [];
        foreach ($categories as $category) {
            $data[] = [
                'id_scene'    => (int) $this->id,
                'id_category' => (int) $category,
            ];
        }

        return Db::getInstance()->insert('scene_category', $data);
    }

    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function add($autoDate = true, $nullValues = false)
    {
        if (!empty($this->zones)) {
            $this->addZoneProducts($this->zones);
        }
        if (!empty($this->categories)) {
            $this->addCategories($this->categories);
        }

        if (parent::add($autoDate, $nullValues)) {
            // Put cache of feature detachable only if this new scene is active else we keep the old value
            if ($this->active) {
                Configuration::updateGlobalValue('PS_SCENE_FEATURE_ACTIVE', '1');
            }

            return true;
        }

        return false;
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function delete()
    {
        $this->deleteZoneProducts();
        $this->deleteCategories();
        if (parent::delete()) {
            return $this->deleteImage() &&
                Configuration::updateGlobalValue('PS_SCENE_FEATURE_ACTIVE', Scene::isCurrentlyUsed($this->def['table'], true));
        }

        return false;
    }

    /**
     * @param bool $forceDelete
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function deleteImage($forceDelete = false)
    {
        if (file_exists($this->image_dir.'thumbs/'.$this->id.'-m_scene_default.'.$this->image_format)
            && !unlink($this->image_dir.'thumbs/'.$this->id.'-m_scene_default.'.$this->image_format)
        ) {
            return false;
        }
        if (!(isset($_FILES) && count($_FILES))) {
            return parent::deleteImage();
        }

        return true;
    }
}
