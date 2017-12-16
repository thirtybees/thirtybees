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
    // @codingStandardsIgnoreStart
    public $id_tax_rules_group;
    public $id_country;
    public $id_state;
    public $zipcode_from;
    public $zipcode_to;
    public $id_tax;
    public $behavior;
    public $description;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'tax_rule',
        'primary' => 'id_tax_rule',
        'fields'  => [
            'id_tax_rules_group' => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId', 'required' => true],
            'id_country'         => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId', 'required' => true],
            'id_state'           => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId'                    ],
            'zipcode_from'       => ['type' => self::TYPE_STRING, 'validate' => 'isPostCode'                      ],
            'zipcode_to'         => ['type' => self::TYPE_STRING, 'validate' => 'isPostCode'                      ],
            'id_tax'             => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId', 'required' => true],
            'behavior'           => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedInt'                   ],
            'description'        => ['type' => self::TYPE_STRING, 'validate' => 'isString'                        ],
        ],
    ];

    protected $webserviceParameters = [
        'fields' => [
            'id_tax_rules_group' => ['xlink_resource' => 'tax_rule_groups'],
            'id_state'           => ['xlink_resource' => 'states'],
            'id_country'         => ['xlink_resource' => 'countries'],
        ],
    ];

    /**
     * @param $idGroup
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function deleteByGroupId($idGroup)
    {
        if (empty($idGroup)) {
            die(Tools::displayError());
        }

        return Db::getInstance()->execute(
            '
			DELETE FROM `'._DB_PREFIX_.'tax_rule`
			WHERE `id_tax_rules_group` = '.(int) $idGroup
        );
    }

    /**
     * @param $idTaxRule
     *
     * @return array|bool|null|object
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
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
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
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
     * @deprecated 1.0.0
     */
    public static function deleteTaxRuleByIdCounty($idCounty)
    {
        Tools::displayAsDeprecated();

        return true;
    }

    /**
     * @param int $idTax
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
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
     * @param string $zipcode a range of zipcode (eg: 75000 / 75000-75015)
     *
     * @return array an array containing two zipcode ordered by zipcode
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function breakDownZipCode($zipCodes)
    {
        $zipCodes = preg_split('/-/', $zipCodes);

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
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @return bool
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
