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
 * Class TranslatedConfigurationCore
 *
 * @since 1.0.0
 */
class TranslatedConfigurationCore extends Configuration
{
    public $value = [];

    public static $definition = [
        'table'     => 'configuration',
        'primary'   => 'id_configuration',
        'multilang' => true,
        'fields'    => [
            'id_shop_group' => ['type' => self::TYPE_NOTHING, 'validate' => 'isUnsignedId', 'dbType' => 'int(11) unsigned'],
            'id_shop'       => ['type' => self::TYPE_NOTHING, 'validate' => 'isUnsignedId', 'dbType' => 'int(11) unsigned'],
            'name'          => ['type' => self::TYPE_STRING, 'validate' => 'isConfigName', 'required' => true, 'size' => 254],
            'value'         => ['type' => self::TYPE_STRING, 'lang' => true, 'size' => ObjectModel::SIZE_TEXT],
            'date_add'      => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false],
            'date_upd'      => ['type' => self::TYPE_DATE, 'lang' => true, 'validate' => 'isDate'],
        ],
        'keys' => [
            'configuration' => [
                'id_shop'       => ['type' => ObjectModel::KEY, 'columns' => ['id_shop']],
                'id_shop_group' => ['type' => ObjectModel::KEY, 'columns' => ['id_shop_group']],
            ],
            'configuration_kpi' => [
                'id_shop'       => ['type' => ObjectModel::KEY, 'columns' => ['id_shop']],
                'id_shop_group' => ['type' => ObjectModel::KEY, 'columns' => ['id_shop_group']],
                'name'          => ['type' => ObjectModel::KEY, 'columns' => ['name']],
            ],
            'configuration_kpi_lang' => [
                'primary' => ['type' => ObjectModel::PRIMARY_KEY, 'columns' => ['id_configuration_kpi', 'id_lang']],
            ],
        ],
    ];
    protected $webserviceParameters = [
        'objectNodeName'  => 'translated_configuration',
        'objectsNodeName' => 'translated_configurations',
        'fields'          => [
            'value'    => [],
            'date_add' => [],
            'date_upd' => [],
        ],
    ];

    /**
     * TranslatedConfigurationCore constructor.
     *
     * @param int|null $id
     * @param int|null $idLang
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function __construct($id = null, $idLang = null)
    {
        $this->def = ObjectModel::getDefinition($this);
        // Check if the id configuration is set in the configuration_lang table.
        // Otherwise configuration is not set as translated configuration.
        if ($id !== null) {
            $idTranslated = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select(bqSQL(static::$definition['primary']))
                    ->from(bqSQL(static::$definition['table']).'_lang')
                    ->where('`'.bqSQL(static::$definition['primary']).'` = '.(int) $id)
                    ->limit(1, 0)
            );

            if (empty($idTranslated)) {
                $id = null;
            }
        }
        parent::__construct($id, $idLang);
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
        return $this->update($nullValues);
    }

    /**
     * @param bool $nullValues
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function update($nullValues = false)
    {
        $ishtml = false;
        foreach ($this->value as $i18NValue) {
            if (Validate::isCleanHtml($i18NValue)) {
                $ishtml = true;
                break;
            }
        }
        Configuration::updateValue($this->name, $this->value, $ishtml);

        $lastInsert = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('`id_configuration` AS `id`')
                ->from('configuration')
                ->where('`name` = \''.pSQL($this->name).'\'')
        );
        if ($lastInsert) {
            $this->id = $lastInsert['id'];
        }

        return true;
    }

    /**
     * @param string $sqlJoin
     * @param string $sqlFilter
     * @param string $sqlSort
     * @param string $sqlLimit
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getWebserviceObjectList($sqlJoin, $sqlFilter, $sqlSort, $sqlLimit)
    {
        $query = '
		SELECT DISTINCT main.`'.$this->def['primary'].'` FROM `'._DB_PREFIX_.$this->def['table'].'` main
		'.$sqlJoin.'
		WHERE id_configuration IN
		(	SELECT id_configuration
			FROM '._DB_PREFIX_.$this->def['table'].'_lang
		) '.$sqlFilter.'
		'.($sqlSort != '' ? $sqlSort : '').'
		'.($sqlLimit != '' ? $sqlLimit : '').'
		';

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
    }
}
