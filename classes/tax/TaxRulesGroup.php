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
 * Class TaxRulesGroupCore
 *
 * @since 1.0.0
 */
class TaxRulesGroupCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    public $name;

    /** @var bool active state */
    public $active;

    public $deleted = 0;

    /** @var string Object creation date */
    public $date_add;

    /** @var string Object last modification date */
    public $date_upd;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'tax_rules_group',
        'primary' => 'id_tax_rules_group',
        'fields'  => [
            'name'     => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 64],
            'active'   => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'                                         ],
            'deleted'  => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'                                         ],
            'date_add' => ['type' => self::TYPE_DATE,   'validate' => 'isDate'                                         ],
            'date_upd' => ['type' => self::TYPE_DATE,   'validate' => 'isDate'                                         ],
        ],
    ];

    protected $webserviceParameters = [
        'objectsNodeName' => 'tax_rule_groups',
        'objectNodeName'  => 'tax_rule_group',
        'fields'          => [
        ],
    ];

    protected static $_taxes = [];

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
        if (!$this->deleted && $this->isUsed()) {
            $currentTaxRulesGroup = new TaxRulesGroup((int) $this->id);
            if ((!$newTaxRulesGroup = $currentTaxRulesGroup->duplicateObject()) || !$currentTaxRulesGroup->historize($newTaxRulesGroup)) {
                return false;
            }

            $this->id = (int) $newTaxRulesGroup->id;
        }

        return parent::update($nullValues);
    }

    /**
     * Save the object with the field deleted to true
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function historize(TaxRulesGroup $taxRulesGroup)
    {
        $this->deleted = true;

        return parent::update() &&
            Db::getInstance()->execute(
                '
		INSERT INTO '._DB_PREFIX_.'tax_rule
		(id_tax_rules_group, id_country, id_state, zipcode_from, zipcode_to, id_tax, behavior, description)
		(
			SELECT '.(int) $taxRulesGroup->id.', id_country, id_state, zipcode_from, zipcode_to, id_tax, behavior, description
			FROM '._DB_PREFIX_.'tax_rule
			WHERE id_tax_rules_group='.(int) $this->id.'
		)'
            ) &&
            Db::getInstance()->execute(
                '
		UPDATE '._DB_PREFIX_.'product
		SET id_tax_rules_group='.(int) $taxRulesGroup->id.'
		WHERE id_tax_rules_group='.(int) $this->id
            ) &&
            Db::getInstance()->execute(
                '
		UPDATE '._DB_PREFIX_.'product_shop
		SET id_tax_rules_group='.(int) $taxRulesGroup->id.'
		WHERE id_tax_rules_group='.(int) $this->id
            ) &&
            Db::getInstance()->execute(
                '
		UPDATE '._DB_PREFIX_.'carrier
		SET id_tax_rules_group='.(int) $taxRulesGroup->id.'
		WHERE id_tax_rules_group='.(int) $this->id
            ) &&
            Db::getInstance()->execute(
                '
		UPDATE '._DB_PREFIX_.'carrier_tax_rules_group_shop
		SET id_tax_rules_group='.(int) $taxRulesGroup->id.'
		WHERE id_tax_rules_group='.(int) $this->id
            );
    }

    /**
     * @param int $idTaxRule
     *
     * @return false|null|string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getIdTaxRuleGroupFromHistorizedId($idTaxRule)
    {
        $params = Db::getInstance()->getRow(
            '
		SELECT id_country, id_state, zipcode_from, zipcode_to, id_tax, behavior
		FROM '._DB_PREFIX_.'tax_rule
		WHERE id_tax_rule='.(int) $idTaxRule
        );

        return Db::getInstance()->getValue(
            '
		SELECT id_tax_rule
		FROM '._DB_PREFIX_.'tax_rule
		WHERE
			id_tax_rules_group = '.(int) $this->id.' AND
			id_country='.(int) $params['id_country'].' AND id_state='.(int) $params['id_state'].' AND id_tax='.(int) $params['id_tax'].' AND
			zipcode_from=\''.pSQL($params['zipcode_from']).'\' AND zipcode_to=\''.pSQL($params['zipcode_to']).'\' AND behavior='.(int) $params['behavior']
        );
    }

    /**
     * @param bool $onlyActive
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getTaxRulesGroups($onlyActive = true)
    {
        return Db::getInstance()->executeS(
            '
			SELECT DISTINCT g.id_tax_rules_group, g.name, g.active
			FROM `'._DB_PREFIX_.'tax_rules_group` g'
            .Shop::addSqlAssociation('tax_rules_group', 'g').' WHERE deleted = 0'
            .($onlyActive ? ' AND g.`active` = 1' : '').'
			ORDER BY name ASC'
        );
    }

    /**
     * @return array an array of tax rules group formatted as $id => $name
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getTaxRulesGroupsForOptions()
    {
        $taxRules[] = ['id_tax_rules_group' => 0, 'name' => Tools::displayError('No tax')];

        return array_merge($taxRules, TaxRulesGroup::getTaxRulesGroups());
    }

    /**
     * @return bool
     * @throws PrestaShopException
     */
    public function delete()
    {
        $res = Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'tax_rule` WHERE `id_tax_rules_group`='.(int) $this->id);

        return (parent::delete() && $res);
    }

    /**
     * @param $idCountry
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getAssociatedTaxRatesByIdCountry($idCountry)
    {
        $rows = Db::getInstance()->executeS(
            '
			SELECT rg.`id_tax_rules_group`, t.`rate`
			FROM `'._DB_PREFIX_.'tax_rules_group` rg
			LEFT JOIN `'._DB_PREFIX_.'tax_rule` tr ON (tr.`id_tax_rules_group` = rg.`id_tax_rules_group`)
			LEFT JOIN `'._DB_PREFIX_.'tax` t ON (t.`id_tax` = tr.`id_tax`)
			WHERE tr.`id_country` = '.(int) $idCountry.'
			AND tr.`id_state` = 0
			AND 0 between `zipcode_from` AND `zipcode_to`'
        );

        $res = [];
        foreach ($rows as $row) {
            $res[$row['id_tax_rules_group']] = $row['rate'];
        }

        return $res;
    }

    /**
     * Returns the tax rules group id corresponding to the name
     *
     * @param string $name
     *
     * @return int id of the tax rules
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getIdByName($name)
    {
        return Db::getInstance()->getValue(
            'SELECT `id_tax_rules_group`
			FROM `'._DB_PREFIX_.'tax_rules_group` rg
			WHERE `name` = \''.pSQL($name).'\''
        );
    }

    /**
     * @param int  $idCountry
     * @param int  $idState
     * @param bool $idTaxRule
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function hasUniqueTaxRuleForCountry($idCountry, $idState, $idTaxRule = false)
    {
        $rules = TaxRule::getTaxRulesByGroupId((int) Context::getContext()->language->id, (int) $this->id);
        foreach ($rules as $rule) {
            if ($rule['id_country'] == $idCountry && $idState == $rule['id_state'] && !$rule['behavior'] && (int) $idTaxRule != $rule['id_tax_rule']) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return false|null|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function isUsed()
    {
        return Db::getInstance()->getValue(
            '
		SELECT `id_tax_rules_group`
		FROM `'._DB_PREFIX_.'order_detail`
		WHERE `id_tax_rules_group` = '.(int) $this->id
        );
    }
}
