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

class GenderCore extends ObjectModel
{
    /**
     * @var array Object model definition
     */
    public static $definition = [
        'table'     => 'gender',
        'primary'   => 'id_gender',
        'primaryKeyDbType' => 'int(11)',
        'multilang' => true,
        'fields'    => [
            'type' => ['type' => self::TYPE_INT, 'required' => true, 'dbType' => 'tinyint(1)'],

            /* Lang fields */
            'name' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString', 'required' => true, 'size' => 20],
        ],
        'keys' => [
            'gender_lang' => [
                'id_gender' => ['type' => ObjectModel::KEY, 'columns' => ['id_gender']],
            ],
        ],
    ];

    /**
     * @var int
     */
    public $id_gender;

    /**
     * @var string|string[]
     */
    public $name;

    /**
     * @var int
     */
    public $type;

    /**
     * GenderCore constructor.
     *
     * @param int|null $id
     * @param int|null $idLang
     * @param int|null $idShop
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function __construct($id = null, $idLang = null, $idShop = null)
    {
        parent::__construct($id, $idLang, $idShop);

        $this->image_dir = _PS_GENDERS_DIR_;
    }

    /**
     * @param int|null $idLang
     *
     * @return PrestaShopCollection
     *
     * @throws PrestaShopException
     */
    public static function getGenders($idLang = null)
    {
        if (is_null($idLang)) {
            $idLang = Context::getContext()->language->id;
        }

        $genders = new PrestaShopCollection('Gender', $idLang);

        return $genders;
    }

    /**
     * @return array[]
     * @throws PrestaShopException
     */
    public static function getIconList(): array
    {
        $gendersIcon = [
            'default' => [
                'src' => static::getGenderImage(null),
                'alt' => Translate::getAdminTranslation('Unknown gender', 'Gender')
            ]
        ];
        foreach (GenderCore::getGenders() as $gender) {
            /** @var Gender $gender */
            $gendersIcon[$gender->id] = [
                'src' => $gender->getImage(),
                'alt' => $gender->name
            ];
        }
        return $gendersIcon;
    }

    /**
     * @param int|null $id
     * @return string
     */
    protected static function getGenderImage($id)
    {
        $id = (int)$id;
        if (!$id || !file_exists(_PS_GENDERS_DIR_ . $id . '.jpg')) {
            return _THEME_GENDERS_DIR_ . 'Unknown.jpg';
        }

        return _THEME_GENDERS_DIR_ . $id . '.jpg';
    }

    /**
     * @param bool $useUnknown
     *
     * @return string
     */
    public function getImage($useUnknown = false)
    {
        return static::getGenderImage($this->id);
    }
}
