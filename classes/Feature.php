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
 * Class FeatureCore
 *
 * @since 1.0.0
 */
class FeatureCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    /** @var string Name */
    public $name;
    /** @var int $position */
    public $position;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'     => 'feature',
        'primary'   => 'id_feature',
        'multilang' => true,
        'fields'    => [
            'position' => ['type' => self::TYPE_INT, 'validate' => 'isInt'],

            /* Lang fields */
            'name'     => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 128],
        ],
    ];

    protected $webserviceParameters = [
        'objectsNodeName' => 'product_features',
        'objectNodeName'  => 'product_feature',
        'fields'          => [],
    ];

    /**
     * Get a feature data for a given id_feature and id_lang
     *
     * @param int $idLang    Language id
     * @param int $idFeature Feature id
     *
     * @return array Array with feature's data
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getFeature($idLang, $idFeature)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('*')
                ->from('feature', 'f')
                ->leftJoin('feature_lang', 'fl', 'f.`id_feature` = fl.`id_feature` AND fl.`id_lang` = '.(int) $idLang)
                ->where('f.`id_feature` = '.(int) $idFeature)
        );
    }

    /**
     * Get all features for a given language
     *
     * @param int  $idLang Language id
     * @param bool $withShop
     *
     * @return array Multiple arrays with feature's data
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getFeatures($idLang, $withShop = true)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('DISTINCT f.`id_feature`, f.*, fl.*')
                ->from('feature', 'f')
                ->join($withShop ? Shop::addSqlAssociation('feature', 'f') : '')
                ->leftJoin('feature_lang', 'fl', 'f.`id_feature` = fl.`id_feature` And fl.`id_lang` = '.(int) $idLang)
                ->orderBy('f.`position` ASC')
        );
    }

    /**
     * Count number of features for a given language
     *
     * @param int $idLang Language id
     *
     * @return int Number of feature
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function nbFeatures($idLang)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('COUNT(*) as `nb`')
                ->from('feature', 'ag')
                ->leftJoin('feature_lang', 'agl', 'ag.`id_feature` = agl.`id_feature` AND `id_lang` = '.(int) $idLang)
        );
    }

    /**
     * Create a feature from import
     *
     * @param string $name
     * @param bool   $position
     *
     * @return int
     *
     * @since    1.0.0
     * @version  1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function addFeatureImport($name, $position = false)
    {
        $rq = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('`id_feature`')
                ->from('feature_lang')
                ->where('`name` = \''.pSQL($name).'\'')
                ->groupBy('`id_feature`')
        );
        if (empty($rq)) {
            // Feature doesn't exist, create it
            $feature = new Feature();
            $feature->name = array_fill_keys(Language::getIDs(), (string) $name);
            if ($position) {
                $feature->position = (int) $position;
            } else {
                $feature->position = Feature::getHigherPosition() + 1;
            }
            $feature->add();

            return $feature->id;
        } elseif (isset($rq['id_feature']) && $rq['id_feature']) {
            if (is_numeric($position) && $feature = new Feature((int) $rq['id_feature'])) {
                $feature->position = (int) $position;
                if (Validate::isLoadedObject($feature)) {
                    $feature->update();
                }
            }

            return (int) $rq['id_feature'];
        }
    }

    /**
     * getHigherPosition
     *
     * Get the higher feature position
     *
     * @return int $position
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getHigherPosition()
    {
        $position = DB::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('MAX(`position`)')
                ->from('feature')
        );

        return (is_numeric($position)) ? $position : -1;
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
        if ($this->position <= 0) {
            $this->position = Feature::getHigherPosition() + 1;
        }

        $return = parent::add($autoDate, true);
        Hook::exec('actionFeatureSave', ['id_feature' => $this->id]);

        return $return;
    }

    /**
     * @param bool $nullValues
     *
     * @return bool|int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function update($nullValues = false)
    {
        $this->clearCache();

        $result = 1;
        $fields = $this->getFieldsLang();
        foreach ($fields as $field) {
            foreach (array_keys($field) as $key) {
                if (!Validate::isTableOrIdentifier($key)) {
                    die(Tools::displayError());
                }
            }

            $mode = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
                (new DbQuery())
                ->select('`id_lang`')
                ->from(bqSQL(static::$definition['table']).'_lang')
                ->where('`id_feature` = '.(int) $this->id)
                ->where('`id_lang` = '.(int) $field['id_lang'])
            );
            $result &= (!$mode) ? Db::getInstance()->insert($this->def['table'].'_lang', $field) :
                Db::getInstance()->update(
                    $this->def['table'].'_lang',
                    $field,
                    '`'.$this->def['primary'].'` = '.(int) $this->id.' AND `id_lang` = '.(int) $field['id_lang']
                );
        }

        if ($result) {
            $result &= parent::update($nullValues);
            if ($result) {
                Hook::exec('actionFeatureSave', ['id_feature' => $this->id]);
            }
        }

        return $result;
    }

    /**
     * @param array $listIdsProduct
     * @param int   $idLang
     *
     * @return array|bool|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getFeaturesForComparison($listIdsProduct, $idLang)
    {
        if (!Feature::isFeatureActive()) {
            return false;
        }

        $ids = '';
        foreach ($listIdsProduct as $id) {
            $ids .= (int) $id.',';
        }

        $ids = rtrim($ids, ',');

        if (empty($ids)) {
            return false;
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('f.*, fl.*')
                ->from('feature', 'f')
                ->leftJoin('feature_product', 'fp', 'f.`id_feature` = fp.`id_feature`')
                ->leftJoin('feature_lang', 'fl', 'f.`id_feature` = fl.`id_feature` AND fl.`id_lang` = '.(int) $idLang)
                ->where('fp.`id_product` IN ('.$ids.')')
                ->groupBy('f.`id_feature`')
                ->orderBy('f.`position` ASC')
        );
    }

    /**
     * This metohd is allow to know if a feature is used or active=
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function isFeatureActive()
    {
        return Configuration::get('PS_FEATURE_FEATURE_ACTIVE');
    }

    /**
     * Delete several objects from database
     *
     * @param array $selection Array with items to delete
     *
     * @return bool Deletion result
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function deleteSelection($selection)
    {
        /* Also delete Attributes */
        foreach ($selection as $value) {
            $obj = new Feature($value);
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
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    public function delete()
    {
        /* Also delete related attributes */
        Db::getInstance()->execute(
            '
			DELETE
				`'._DB_PREFIX_.'feature_value_lang`
			FROM
				`'._DB_PREFIX_.'feature_value_lang`
				JOIN `'._DB_PREFIX_.'feature_value`
					ON (`'._DB_PREFIX_.'feature_value_lang`.id_feature_value = `'._DB_PREFIX_.'feature_value`.id_feature_value)
			WHERE
				`'._DB_PREFIX_.'feature_value`.`id_feature` = '.(int) $this->id.'
		'
        );
        Db::getInstance()->delete('feature_value', '`id_feature` = '.(int) $this->id);
        /* Also delete related products */
        Db::getInstance()->delete('feature_product', '`id_feature` = '.(int) $this->id);

        $return = parent::delete();
        if ($return) {
            Hook::exec('actionFeatureDelete', ['id_feature' => $this->id]);
        }

        /* Reinitializing position */
        $this->cleanPositions();

        return $return;
    }

    /**
     * Reorder feature position
     * Call it after deleting a feature.
     *
     * @return bool $return
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     * @throws PrestaShopException
     */
    public static function cleanPositions()
    {
        Db::getInstance()->execute('SET @i = -1', false);
        $sql = 'UPDATE `'._DB_PREFIX_.'feature` SET `position` = @i:=@i+1 ORDER BY `position` ASC';

        return (bool) Db::getInstance()->execute($sql);
    }

    /**
     * Move a feature
     *
     * @param bool     $way       Up (1)  or Down (0)
     * @param int      $position
     * @param int|null $idFeature
     *
     * @return bool Update result
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function updatePosition($way, $position, $idFeature = null)
    {
        if (!$res = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('`position`, `id_feature`')
                ->from('feature')
                ->where('`id_feature` = '.(int) ($idFeature ? $idFeature : $this->id))
                ->orderBy('`position` ASC')
        )) {
            return false;
        }

        foreach ($res as $feature) {
            if ((int) $feature['id_feature'] == (int) $this->id) {
                $movedFeature = $feature;
            }
        }

        if (!isset($movedFeature) || !isset($position)) {
            return false;
        }

        // < and > statements rather than BETWEEN operator
        // since BETWEEN is treated differently according to databases
        return (Db::getInstance()->update(
            'feature',
            [
                'position' => ['type' => 'sql', 'value' => '`position` '.($way ? '- 1' : '+ 1')],
            ],
            '`position`'.($way ? '> '.(int) $movedFeature['position'].' AND `position` <= '.(int) $position : '< '.(int) $movedFeature['position'].' AND `position` >= '.(int) $position)
        )
        && Db::getInstance()->update(
            'feature',
            [
                'position' => (int) $position,
            ],
            '`id_feature`='.(int) $movedFeature['id_feature']
        ));
    }
}
