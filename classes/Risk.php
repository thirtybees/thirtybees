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
 * Class RiskCore
 *
 * @since 1.0.0
 */
class RiskCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    public $id_risk;
    public $name;
    public $color;
    public $percent;
    // @codingStandardsIgnoreEnd

    public static $definition = [
        'table'     => 'risk',
        'primary'   => 'id_risk',
        'multilang' => true,
        'fields'    => [
            'name'    => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString', 'required' => true, 'size' => 20],
            'percent' => ['type' => self::TYPE_INT, 'validate' => 'isPercentage', 'dbType' => 'tinyint(3)', 'dbNullable' => false],
            'color'   => ['type' => self::TYPE_STRING, 'validate' => 'isColor', 'size' => 32],
        ],
        'keys' => [
            'risk_lang' => [
                'id_risk' => ['type' => ObjectModel::KEY, 'columns' => ['id_risk']],
            ],
        ],
    ];

    /**
     * @param int|null $idLang
     *
     * @return PrestaShopCollection
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getRisks($idLang = null)
    {
        if (is_null($idLang)) {
            $idLang = Context::getContext()->language->id;
        }

        $risks = new PrestaShopCollection('Risk', $idLang);

        return $risks;
    }

    /**
     * @return mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getFields()
    {
        $this->validateFields();
        $fields['id_risk'] = (int) $this->id_risk;
        $fields['color'] = pSQL($this->color);
        $fields['percent'] = (int) $this->percent;

        return $fields;
    }

    /**
     * Check then return multilingual fields for database interaction
     *
     * @return array Multilingual fields
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     * @throws PrestaShopException
     */
    public function getTranslationsFieldsChild()
    {
        $this->validateFieldsLang();

        return $this->getTranslationsFields(['name']);
    }
}
