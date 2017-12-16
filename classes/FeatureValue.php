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
    /** @var bool Custom */
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
            'custom'     => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],

            /* Lang fields */
            'value'      => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 255],
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
     * @param bool $custom
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
                ->select('*')
                ->from('feature_value', 'v')
                ->leftJoin('feature_value_lang', 'vl', 'v.`id_feature_value` = vl.`id_feature_value` AND vl.`id_lang` = '.(int) $idLang)
                ->where('v.`id_feature` = '.(int) $idFeature)
                ->where(!$custom ? 'v.`custom` IS NULL OR v.`custom` = 0' : '')
                ->orderBy('vl.`value` ASC')
        );
    }

    /**
     * Get all language for a given value
     *
     * @param bool $idFeatureValue Feature value id
     *
     * @return array Array with value's languages
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getFeatureValueLang($idFeatureValue)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from('feature_value_lang')
                ->where('`id_feature_value` = '.(int) $idFeatureValue)
                ->orderBy('`id_lang`')
        );
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
     * @param int|null $idProduct
     * @param int|null $idLang
     * @param bool     $custom
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
        $idFeatureValue = false;
        if (!is_null($idProduct) && $idProduct) {
            $idFeatureValue = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('fp.`id_feature_value`')
                    ->from('feature_product', 'fp')
                    ->innerJoin('feature_value', 'fv', 'fv.`id_feature_value` = fp.`id_feature_value`')
                    ->where('fp.`id_feature` = '.(int) $idFeature)
                    ->where('fv.`custom` = '.(int) $custom)
                    ->where('fp.`id_product` = '.(int) $idProduct)
            );

            if ($custom && $idFeatureValue && !is_null($idLang) && $idLang) {
                Db::getInstance()->update(
                    'feature_value_lang',
                    [
                        'value' => pSQL($value),
                    ],
                    '`id_feature_value` = '.(int) $idFeatureValue.' AND `value` != \''.pSQL($value).'\' AND `id_lang` = '.(int) $idLang
                );
            }
        }

        if (!$custom) {
            $idFeatureValue = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('fv.`id_feature_value`')
                    ->from('feature_value', 'fv')
                    ->leftJoin('feature_value_lang', 'fvl', 'fvl.`id_feature_value` = fv.`id_feature_value` AND fvl.`id_lang` = '.(int) $idLang)
                    ->where('fvl.`value` = \''.pSQL($value).'\'')
                    ->where('fv.`id_feature` = '.(int) $idFeature)
                    ->where('fv.`custom` = 0')
                    ->groupBy('fv.`id_feature_value`')
            );
        }

        if ($idFeatureValue) {
            return (int) $idFeatureValue;
        }

        // Feature doesn't exist, create it
        $featureValue = new FeatureValue();
        $featureValue->id_feature = (int) $idFeature;
        $featureValue->custom = (bool) $custom;
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
}
