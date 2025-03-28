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

use Thirtybees\Core\InitializationCallback;

/**
 * Class FeatureCore
 */
class FeatureCore extends ObjectModel implements InitializationCallback
{
    const SORT_VALUE_ASC = 0;
    const SORT_VALUE_DESC = 1;
    const SORT_CUSTOM = 2;

    /**
     * @var string|string[] Feature name
     */
    public $name;

    /**
     * @var string|string[] Feature name
     */
    public $public_name;

    /**
     * @var int Position of the feature
     */
    public $position;

    /**
     * @var bool Flag to indicate if feature allows multiple values, or just a single one
     */
    public $allows_multiple_values = false;

    /**
     * @var int Sorting method when multiple values were selected
     */
    public $sorting;

    /**
     * @var bool Deprecated
     */
    public $allows_custom_values = true;

    /**
     * @var string|string[] FO separator, when multiple values were selected
     */
    public $multiple_separator;

    /**
     * @var string|string[] FO display schema, when multiple values were selected
     */
    public $multiple_schema;


    /**
     * @var array Object model definition
     */
    public static $definition = [
        'table'     => 'feature',
        'primary'   => 'id_feature',
        'multilang' => true,
        'fields'    => [
            'position'                  => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'dbDefault' => '0'],
            'allows_multiple_values'    => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true, 'dbDefault' => '0'],
            'allows_custom_values'      => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true, 'dbDefault' => '1'],
            'sorting'                   => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'dbType' => 'tinyint(1)', 'dbDefault' => self::SORT_VALUE_ASC],

            /* Lang fields */
            'name'                 => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 128, 'dbNullable' => true],
            'public_name'          => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 128, 'dbNullable' => true],
            'multiple_separator'   => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml', 'required' => false, 'size' => 128, 'dbNullable' => true],
            'multiple_schema'      => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString', 'required' => false, 'size' => 128, 'dbNullable' => true],
        ],
        'keys' => [
            'feature_lang' => [
                'id_lang' => ['type' => ObjectModel::KEY, 'columns' => ['id_lang', 'name']],
                'id_lang_pub' => ['type' => ObjectModel::KEY, 'columns' => ['id_lang', 'public_name']],
            ],
            'feature_shop' => [
                'id_shop' => ['type' => ObjectModel::KEY, 'columns' => ['id_shop']],
            ],
        ],
    ];

    /**
     * @var array Webservice parameters
     */
    protected $webserviceParameters = [
        'objectsNodeName' => 'product_features',
        'objectNodeName'  => 'product_feature',
        'fields'          => [],
    ];

    /**
     * Get a feature data for a given id_feature and id_lang
     *
     * @param int $idLang Language id
     * @param int $idFeature Feature id
     *
     * @return array|false Array with feature's data
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getFeature($idLang, $idFeature)
    {
        return Db::readOnly()->getRow(
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
     * @param int $idLang Language id
     * @param bool $withShop
     *
     * @return array Multiple arrays with feature's data
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getFeatures($idLang, $withShop = true)
    {
        return Db::readOnly()->getArray(
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
     * @throws PrestaShopException
     */
    public static function nbFeatures($idLang)
    {
        return Db::readOnly()->getValue(
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
     * @param int|false $position
     * @param string|null $publicName
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public static function addFeatureImport($name, $position = false, $publicName = null)
    {
        $name = (string)$name;
        $publicName = $publicName ? (string)$publicName : $name;

        $featureId = (int)Db::readOnly()->getValue(
            (new DbQuery())
                ->select('`id_feature`')
                ->from('feature_lang')
                ->where('`name` = \''.pSQL($name).'\'')
        );
        if (! $featureId) {
            // Feature doesn't exist, create it
            $feature = new Feature();
            $feature->name = array_fill_keys(Language::getIDs(), $name);
            $feature->public_name = array_fill_keys(Language::getIDs(), $publicName);
            if ($position) {
                $feature->position = (int) $position;
            } else {
                $feature->position = Feature::getHigherPosition() + 1;
            }
            $feature->add();

            return $feature->id;
        } else {
            if (is_numeric($position) && $feature = new Feature($featureId)) {
                $feature->position = (int) $position;
                if (Validate::isLoadedObject($feature)) {
                    $feature->update();
                }
            }
            return $featureId;
        }
    }

    /**
     * getHigherPosition
     *
     * Get the higher feature position
     *
     * @return int $position
     *
     * @throws PrestaShopException
     */
    public static function getHigherPosition()
    {
        $position = Db::readOnly()->getValue(
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
     * @throws PrestaShopException
     */
    public function add($autoDate = true, $nullValues = false)
    {
        if ($this->position <= 0) {
            $this->position = Feature::getHigherPosition() + 1;
        }

        if ($this->name && !$this->public_name) {
            $this->public_name = $this->name;
        }

        $return = parent::add($autoDate, true);
        Hook::triggerEvent('actionFeatureSave', ['id_feature' => $this->id]);

        return $return;
    }

    /**
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function update($nullValues = false)
    {
        $this->clearCache();

        $result = true;

        $tableName = $this->def['table'].'_lang';
        $fields = $this->getFieldsLang();
        $conn = Db::getInstance();
        $featureId = (int)$this->id;
        foreach ($fields as $field) {
            foreach (array_keys($field) as $key) {
                if (!Validate::isTableOrIdentifier($key)) {
                    throw new PrestaShopException('key '.$key.' is not a valid table or identifier');
                }
            }
            $langId = (int)$field['id_lang'];

            $exists = (bool)$conn->getValue(
                (new DbQuery())
                    ->select('1')
                    ->from($tableName)
                    ->where("id_feature = $featureId")
                    ->where("id_lang = $langId")
            );

            if (! $exists) {
                $result = $conn->insert($tableName, $field) && $result;
            } else {
                $where =  "id_feature = $featureId AND id_lang = $langId";
                $result = $conn->update($tableName, $field, $where) && $result;
            }
        }

        if ($result) {
            $result = parent::update($nullValues);
            if ($result) {
                Hook::triggerEvent('actionFeatureSave', ['id_feature' => $featureId]);
            }
        }

        return $result;
    }

    /**
     * @param array $listIdsProduct
     * @param int $idLang
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getFeaturesForComparison($listIdsProduct, $idLang)
    {
        if (!Feature::isFeatureActive()) {
            return [];
        }

        $ids = '';
        foreach ($listIdsProduct as $id) {
            $ids .= (int) $id.',';
        }

        $ids = rtrim($ids, ',');

        if (empty($ids)) {
            return [];
        }

        return Db::readOnly()->getArray(
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
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    public function delete()
    {
        /* Also delete related attributes */
        $conn = Db::getInstance();
        $conn->execute(
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
        $conn->delete('feature_value', '`id_feature` = '.(int) $this->id);
        /* Also delete related products */
        $conn->delete('feature_product', '`id_feature` = '.(int) $this->id);

        $return = parent::delete();
        if ($return) {
            Hook::triggerEvent('actionFeatureDelete', ['id_feature' => $this->id]);
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
     * @throws PrestaShopException
     */
    public static function cleanPositions()
    {
        $conn = Db::getInstance();
        $conn->execute('SET @i = -1', false);
        $sql = 'UPDATE `'._DB_PREFIX_.'feature` SET `position` = @i:=@i+1 ORDER BY `position` ASC';

        return (bool) $conn->execute($sql);
    }

    /**
     * Move a feature
     *
     * @param bool $way Up (1) or Down (0)
     * @param int $position
     * @param int|null $idFeature
     *
     * @return bool Update result
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function updatePosition($way, $position, $idFeature = null)
    {
        if (!$res = Db::readOnly()->getArray(
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
        $conn = Db::getInstance();
        return ($conn->update(
            'feature',
            [
                'position' => ['type' => 'sql', 'value' => '`position` '.($way ? '- 1' : '+ 1')],
            ],
            '`position`'.($way ? '> '.(int) $movedFeature['position'].' AND `position` <= '.(int) $position : '< '.(int) $movedFeature['position'].' AND `position` >= '.(int) $position)
        )
        && $conn->update(
            'feature',
            [
                'position' => (int) $position,
            ],
            '`id_feature`='.(int) $movedFeature['id_feature']
        ));
    }

    /**
     * @return Feature[]
     * @throws PrestaShopException
     */
    public static function getAll()
    {
        $collection = new PrestaShopCollection('Feature');
        return $collection->getResults();
    }

    /**
     * Reset feature positions
     *
     * @param Db $conn
     * @return void
     * @throws PrestaShopException
     */
    public static function initializationCallback(Db $conn)
    {
        // add missing public names
        $conn->execute('UPDATE ' . _DB_PREFIX_ . "feature_lang SET public_name = name WHERE COALESCE(public_name, '') = ''");

        // recalculate positions
        $features = static::getFeatures(Configuration::get('PS_LANG_DEFAULT'));
        foreach ($features as $feature) {
            FeatureValue::cleanPositions((int)$feature['id_feature']);
        }
    }
}
