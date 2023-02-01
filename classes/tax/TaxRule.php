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
class TaxRuleCore extends ObjectModel
{
    /**
     * @var int
     */
    public $id_tax_rules_group;

    /**
     * @var int
     */
    public $id_country;

    /**
     * @var int
     */
    public $id_state;

    /**
     * @var string
     */
    public $zipcode_from;

    /**
     * @var string
     */
    public $zipcode_to;

    /**
     * @var int
     */
    public $id_tax;

    /**
     * @var int
     */
    public $behavior;

    /**
     * @var string
     */
    public $description;

    /**
     * @var array Object model definition
     */
    public static $definition = [
        'table'   => 'tax_rule',
        'primary' => 'id_tax_rule',
        'primaryKeyDbType' => 'int(11)',
        'fields'  => [
            'id_tax_rules_group' => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId', 'required' => true, 'dbType' => 'int(11)'],
            'id_country'         => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId', 'required' => true, 'dbType' => 'int(11)'],
            'id_state'           => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId', 'dbType' => 'int(11)', 'dbNullable' => false],
            'zipcode_from'       => ['type' => self::TYPE_STRING, 'validate' => 'isPostCode', 'size' => 12, 'dbNullable' => false],
            'zipcode_to'         => ['type' => self::TYPE_STRING, 'validate' => 'isPostCode', 'size' => 12, 'dbNullable' => false],
            'id_tax'             => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId', 'required' => true, 'dbType' => 'int(11)'],
            'behavior'           => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedInt', 'dbType' => 'int(11)', 'dbNullable' => false],
            'description'        => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 100, 'dbNullable' => false],
        ],
        'keys' => [
            'tax_rule' => [
                'category_getproducts' => ['type' => ObjectModel::KEY, 'columns' => ['id_tax_rules_group', 'id_country', 'id_state', 'zipcode_from']],
                'id_tax'               => ['type' => ObjectModel::KEY, 'columns' => ['id_tax']],
                'id_tax_rules_group'   => ['type' => ObjectModel::KEY, 'columns' => ['id_tax_rules_group']],
            ],
        ],
    ];

    /**
     * @var array Webservice parameters
     */
    protected $webserviceParameters = [
        'fields' => [
            'id_tax_rules_group' => ['xlink_resource' => 'tax_rule_groups'],
            'id_state'           => ['xlink_resource' => 'states'],
            'id_country'         => ['xlink_resource' => 'countries'],
        ],
    ];

    /**
     * @param int $idGroup
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function deleteByGroupId($idGroup)
    {
        return Db::getInstance()->execute(
            '
			DELETE FROM `'._DB_PREFIX_.'tax_rule`
			WHERE `id_tax_rules_group` = '.(int) $idGroup
        );
    }

    /**
     * @param int $idTaxRule
     *
     * @return array|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function retrieveById($idTaxRule)
    {
        return Db::getInstance()->getRow(
            '
			SELECT * FROM `'._DB_PREFIX_.'tax_rule`
			WHERE `id_tax_rule` = '.(int) $idTaxRule
        );
    }

    /**
     * @param int $idLang
     * @param int $idGroup
     *
     * @return array|bool|PDOStatement
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getTaxRulesByGroupId($idLang, $idGroup)
    {
        return Db::getInstance()->executeS(
            '
		SELECT g.`id_tax_rule`,
				 c.`name` AS country_name,
				 s.`name` AS state_name,
				 t.`rate`,
				 g.`zipcode_from`, g.`zipcode_to`,
				 g.`description`,
				 g.`behavior`,
				 g.`id_country`,
				 g.`id_state`
		FROM `'._DB_PREFIX_.'tax_rule` g
		LEFT JOIN `'._DB_PREFIX_.'country_lang` c ON (g.`id_country` = c.`id_country` AND `id_lang` = '.(int) $idLang.')
		LEFT JOIN `'._DB_PREFIX_.'state` s ON (g.`id_state` = s.`id_state`)
		LEFT JOIN `'._DB_PREFIX_.'tax` t ON (g.`id_tax` = t.`id_tax`)
		WHERE `id_tax_rules_group` = '.(int) $idGroup.'
		ORDER BY `country_name` ASC, `state_name` ASC, `zipcode_from` ASC, `zipcode_to` ASC'
        );
    }

    /**
     * @param int $idTax
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function deleteTaxRuleByIdTax($idTax)
    {
        return Db::getInstance()->execute(
            '
			DELETE FROM `'._DB_PREFIX_.'tax_rule`
			WHERE `id_tax` = '.(int) $idTax
        );
    }

    /**
     * @param int $idTax
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function isTaxInUse($idTax)
    {
        $cacheId = 'TaxRule::isTaxInUse_'.(int) $idTax;
        if (!Cache::isStored($cacheId)) {
            $result = (int) Db::getInstance()->getValue('SELECT COUNT(*) FROM `'._DB_PREFIX_.'tax_rule` WHERE `id_tax` = '.(int) $idTax);
            Cache::store($cacheId, $result);

            return $result;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * @param string $zipCodes a range of zipcode (eg: 75000 / 75000-75015)
     *
     * @return array an array containing two zipcode ordered by zipcode
     */
    public function breakDownZipCode($zipCodes)
    {
        $zipCodes = explode('-', $zipCodes);

        $from = $zipCodes[0];
        $to = isset($zipCodes[1]) ? $zipCodes[1] : 0;
        if (count($zipCodes) == 2) {
            $from = $zipCodes[0];
            $to = $zipCodes[1];
            if ($zipCodes[0] > $zipCodes[1]) {
                $from = $zipCodes[1];
                $to = $zipCodes[0];
            } elseif ($zipCodes[0] == $zipCodes[1]) {
                $from = $zipCodes[0];
                $to = 0;
            }
        } elseif (count($zipCodes) == 1) {
            $from = $zipCodes[0];
            $to = 0;
        }

        return [$from, $to];
    }

    /**
     * Replace a tax_rule id by an other one in the tax_rule table
     *
     * @param int $oldId
     * @param int $newId
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function swapTaxId($oldId, $newId)
    {
        return Db::getInstance()->execute(
            '
		UPDATE `'._DB_PREFIX_.'tax_rule`
		SET `id_tax` = '.(int) $newId.'
		WHERE `id_tax` = '.(int) $oldId
        );
    }
}
