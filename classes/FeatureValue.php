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
 * Class FeatureValueCore
 *
 * @since 1.0.0
 */
class FeatureValueCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    /** @var int Group id which attribute belongs */
    public $id_feature;
    /** @var string Name */
    public $value;
    /** @var string Value that will be displayed */
    public $displayable;
    /** @var int Position if multiple values are selected */
    public $position;
    /** @var bool Custom Deprecated (custom functionality was dropped in 1.x.0) */
    public $custom = 0;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'     => 'feature_value',
        'primary'   => 'id_feature_value',
        'multilang' => true,
        'fields'    => [
            'id_feature' => ['type' => self::TYPE_INT,  'validate' => 'isUnsignedId', 'required' => true],
            'position'   => ['type' => self::TYPE_INT, 'dbDefault' => '0'],

            /* Lang fields */
            'value'         => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 255, 'dbNullable' => true],
            'displayable'   => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255, 'dbNullable' => true],
        ],
        'keys' => [
            'feature_value' => [
                'feature' => ['type' => ObjectModel::KEY, 'columns' => ['id_feature']],
            ],
        ],
    ];

    protected $webserviceParameters = [
        'objectsNodeName' => 'product_feature_values',
        'objectNodeName'  => 'product_feature_value',
        'fields'          => [
            'id_feature' => ['xlink_resource' => 'product_features'],
        ],
    ];

    /**
     * Get all values for a given feature
     *
     * @param bool $idFeature Feature id
     *
     * @return array Array with feature's values
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getFeatureValues($idFeature)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from('feature_value')
                ->where('`id_feature` = '.(int) $idFeature)
        );
    }

    /**
     * Get all values for a given feature and language
     *
     * @param int  $idLang    Language id
     * @param bool $idFeature Feature id
     * @param bool $custom Deprecated (custom functionality was dropped in 1.x.0)
     *
     * @return array Array with feature's values
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getFeatureValuesWithLang($idLang, $idFeature, $custom = false)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('v.*, vl.*, IF(vl.displayable IS NOT NULL AND vl.displayable!=\'\',vl.displayable,vl.value) AS value')
                ->from('feature_value', 'v')
                ->leftJoin('feature_value_lang', 'vl', 'v.`id_feature_value` = vl.`id_feature_value` AND vl.`id_lang` = '.(int) $idLang)
                ->leftJoin('feature_lang', 'fl', 'v.`id_feature` = fl.`id_feature` AND fl.`id_lang` = '.(int) $idLang)
                ->where('v.`id_feature` = '.(int) $idFeature)
                ->orderBy('v.`position` ASC')
        );
    }

    /**
     * Get all language for a given value
     *
     * @param bool $id_feature_value Feature value id
     * @param int $id_product Product id
     *
     * @return array Array with value's languages
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getFeatureValueLang($id_feature_value, $id_product = 0)
    {

        $query = new DbQuery();
        $query->select('vl.*');
        $query->from('feature_value_lang', 'vl');

        if ($id_product > 0) {
            $query->select('pl.displayable');
            $query->leftJoin('feature_product_lang', 'pl', 'vl.`id_feature_value` = pl.`id_feature_value` AND vl.`id_lang`=pl.`id_lang` AND pl.`id_product`='.$id_product);
        }
        $query->where('vl.`id_feature_value` = '.(int) $id_feature_value);
        $query->orderBy('vl.`id_lang`, vl.`id_feature_value`');

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
    }

    /**
     * Select the good lang in tab
     *
     * @param array $lang   Array with all language
     * @param int   $idLang Language id
     *
     * @return string String value name selected
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function selectLang($lang, $idLang)
    {
        foreach ($lang as $tab) {
            if ($tab['id_lang'] == $idLang) {
                return $tab['value'];
            }
        }
    }

    /**
     * @param int      $idFeature
     * @param string   $value
     * @param int|null $idProduct Deprecated (custom functionality was dropped in 1.x.0)
     * @param int|null $idLang
     * @param bool     $custom Deprecated (custom functionality was dropped in 1.x.0)
     *
     * @return int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function addFeatureValueImport($idFeature, $value, $idProduct = null, $idLang = null, $custom = false)
    {

        $idFeatureValue = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('fv.`id_feature_value`')
                ->from('feature_value', 'fv')
                ->leftJoin('feature_value_lang', 'fvl', 'fvl.`id_feature_value` = fv.`id_feature_value` AND fvl.`id_lang` = '.(int) $idLang)
                ->where('fvl.`value` = \''.pSQL($value).'\'')
                ->where('fv.`id_feature` = '.(int) $idFeature)
                ->groupBy('fv.`id_feature_value`')
        );

        if ($idFeatureValue) {
            return (int) $idFeatureValue;
        }

        // Feature doesn't exist, create it
        $featureValue = new FeatureValue();
        $featureValue->id_feature = (int) $idFeature;
        $featureValue->custom = false;
        $featureValue->value = array_fill_keys(Language::getIDs(false), $value);
        $featureValue->add();

        return (int) $featureValue->id;
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
        if (!$this->position) {
            $this->position = self::getHighestPosition($this->id_feature)+1;
        }

        $return = parent::add($autoDate, $nullValues);
        if ($return) {
            Hook::exec('actionFeatureValueSave', ['id_feature_value' => $this->id]);
        }

        return $return;
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopDatabaseException
     */
    public function delete()
    {
        /* Also delete related products */
        Db::getInstance()->delete('feature_product', '`id_feature_value` = '.(int) $this->id);
        $return = parent::delete();

        if ($return) {
            Hook::exec('actionFeatureValueDelete', ['id_feature_value' => $this->id]);
        }

        self::cleanPositions($this->id_feature);

        return $return;
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
        $return = parent::update($nullValues);
        if ($return) {
            Hook::exec('actionFeatureValueSave', ['id_feature_value' => $this->id]);
        }

        return $return;
    }

    /**
     * Validates that $value is valid feature value
     *
     * @param string $value
     * @return string | null
     */
    public static function validateFeatureValue($value)
    {
        if (! is_string($value)) {
            return Tools::displayError('Invalid type');
        }

        $field = ObjectModel::getDefinition(FeatureValue::class, 'value');

        // validate size
        if (isset($field['size']) && mb_strlen($value) > $field['size']) {
            return sprintf(Tools::displayError('Feature value \'%s\' is too long'), $value);
        }

        // validate content
        if (isset($field['validate']) && !call_user_func(['Validate', $field['validate']], $value)) {
            return sprintf(Tools::displayError('Feature value \'%s\' is not valid'), $value);
        }

        // this is valid feature value
        return null;
    }

    /**
     * Move a featureValue
     *
     * @param bool     $way       Up (1)  or Down (0)
     * @param int      $position
     * @param int|null $id_feature_value
     *
     * @return bool Update result
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.x.0
     * @version 1.0.0 Initial version
     */
    public function updatePosition($way, $position, $id_feature_value = null)
    {
        if (!$res = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('`position`, `id_feature_value`')
                ->from('feature_value')
                ->where('`id_feature_value` = '.(int) ($id_feature_value ?: $this->id))
                ->orderBy('`position` ASC')
        )) {
            return false;
        }

        foreach ($res as $feature_value) {
            if ((int) $feature_value['id_feature_value'] == (int) $this->id) {
                $movedFeatureValue = $feature_value;
            }
        }

        if (!isset($movedFeatureValue) || !isset($position)) {
            return false;
        }

        // < and > statements rather than BETWEEN operator
        // since BETWEEN is treated differently according to databases
        return (Db::getInstance()->update(
                'feature_value',
                [
                    'position' => ['type' => 'sql', 'value' => '`position` '.($way ? '- 1' : '+ 1')],
                ],
                '`position`'.($way ? '> '.(int) $movedFeatureValue['position'].' AND `position` <= '.(int) $position : '< '.(int) $movedFeatureValue['position'].' AND `position` >= '.(int) $position)
            )
            && Db::getInstance()->update(
                'feature_value',
                [
                    'position' => (int) $position,
                ],
                '`id_feature_value`='.(int) $movedFeatureValue['id_feature_value']
            ));
    }

    /**
     * Reorder featureValue position
     * Call it after deleting a featureValue.
     *
     * @return bool $return
     *
     * @since   1.0.x
     * @version 1.0.x Initial version
     * @throws PrestaShopException
     * @throws PrestaShopException
     */
    public static function cleanPositions($id_feature)
    {
        // reset positions of all featureValues within feature
        return Db::getInstance()->execute('
            SET @rank:=-1;
            UPDATE `'._DB_PREFIX_.'feature_value`
            SET position = @rank:=@rank+1
            WHERE `id_feature` = '.(int)$id_feature.'
            ORDER BY `position`, `id_feature_value`
        ');
    }

    /**
     * getHigherPosition
     *
     * Get the highest featureValue position
     *
     * @return int $position
     *
     * @since   1.0.x
     * @version 1.0.x Initial version
     * @throws PrestaShopException
     */
    public static function getHighestPosition($id_feature)
    {
        $position = DB::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('MAX(`position`)')
                ->from('feature_value')
                ->where('id_feature='.(int)$id_feature)
        );

        return (is_numeric($position)) ? $position : -1;
    }
}
