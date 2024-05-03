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

/**
 * Class CustomizationFieldCore
 */
class CustomizationFieldCore extends ObjectModel
{
    /** @var int */
    public $id_product;
    /** @var int Customization type (0 File, 1 Textfield) (See Product class) */
    public $type;
    /** @var bool Field is required */
    public $required;
    /** @var string|string[] Label for customized field */
    public $name;

    /**
     * @var array Object model definition
     */
    public static $definition = [
        'table'          => 'customization_field',
        'primary'        => 'id_customization_field',
        'multilang'      => true,
        'multilang_shop' => true,
        'fields'         => [
            /* Classic fields */
            'id_product' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'type'       => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true, 'dbType' => 'tinyint(1)'],
            'required'   => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true, 'dbType' => 'tinyint(1)'],

            /* Lang fields */
            'name'       => ['type' => self::TYPE_STRING, 'lang' => true, 'required' => true, 'size' => 255],
        ],
        'keys' => [
            'customization_field' => [
                'id_product' => ['type' => ObjectModel::KEY, 'columns' => ['id_product']],
            ],
        ],
    ];

    /**
     * @var array Webservice parameters
     */
    protected $webserviceParameters = [
        'fields' => [
            'id_product' => [
                'xlink_resource' => [
                    'resourceName' => 'products',
                ],
            ],
        ],
    ];
}
