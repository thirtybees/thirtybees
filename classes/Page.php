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
 * Class PageCore
 */
class PageCore extends ObjectModel
{
    /**
     * @var int
     */
    public $id_page_type;

    /**
     * @var int
     */
    public $id_object;

    /**
     * @var array Object model definition
     */
    public static $definition = [
        'table'   => 'page',
        'primary' => 'id_page',
        'fields'  => [
            'id_page_type' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_object'    => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
        ],
        'keys' => [
            'page' => [
                'id_object'    => ['type' => ObjectModel::KEY, 'columns' => ['id_object']],
                'id_page_type' => ['type' => ObjectModel::KEY, 'columns' => ['id_page_type']],
            ],
        ],
    ];

    /**
     * @return int Current page ID
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getCurrentId()
    {
        $controller = Dispatcher::getInstance()->getController();
        $pageTypeId = Page::getPageTypeByName($controller);

        // Some pages must be distinguished in order to record exactly what is being seen
        // @todo dispatcher module
        $specialArray = [
            'product'      => 'id_product',
            'category'     => 'id_category',
            'order'        => 'step',
            'manufacturer' => 'id_manufacturer',
        ];

        $where = '';
        $insertData = [
            'id_page_type' => $pageTypeId,
        ];

        if (array_key_exists($controller, $specialArray)) {
            $objectId = Tools::getValue($specialArray[$controller], null);
            $where = ' AND `id_object` = '.(int) $objectId;
            $insertData['id_object'] = (int) $objectId;
        }

        $result = Db::readOnly()->getRow(
            (new DbQuery())
                ->select('`id_page`')
                ->from('page')
                ->where('`id_page_type` = '.(int) $pageTypeId.$where)
        );
        if ($result && $result['id_page']) {
            return (int)$result['id_page'];
        }

        $conn = Db::getInstance();
        $conn->insert('page', $insertData, true);

        return $conn->Insert_ID();
    }

    /**
     * Return page type ID from page name
     *
     * @param string $name Page name (E.g. product.php)
     *
     * @return false|int|null|string
     * @throws PrestaShopException
     */
    public static function getPageTypeByName($name)
    {
        if ($value = Db::readOnly()->getValue(
            (new DbQuery())
                ->select('`id_page_type`')
                ->from('page_type')
                ->where('`name` = \''.pSQL($name).'\'')
        )) {
            return $value;
        }

        $conn = Db::getInstance();
        $conn->insert('page_type', ['name' => pSQL($name)]);

        return $conn->Insert_ID();
    }

    /**
     * @param int $idPage
     *
     * @throws PrestaShopException
     */
    public static function setPageViewed($idPage)
    {
        $idDateRange = DateRange::getCurrentRange();
        $context = Context::getContext();

        // Try to increment the visits counter
        $sql = 'UPDATE `'._DB_PREFIX_.'page_viewed`
				SET `counter` = `counter` + 1
				WHERE `id_date_range` = '.(int) $idDateRange.'
					AND `id_page` = '.(int) $idPage.'
					AND `id_shop` = '.(int) $context->shop->id;
        $conn = Db::getInstance();
        $conn->execute($sql);

        // If no one has seen the page in this date range, it is added
        if ($conn->Affected_Rows() == 0) {
            $conn->insert(
                'page_viewed',
                [
                    'id_date_range' => (int) $idDateRange,
                    'id_page'       => (int) $idPage,
                    'counter'       => 1,
                    'id_shop'       => (int) $context->shop->id,
                    'id_shop_group' => (int) $context->shop->id_shop_group,
                ], false, true, Db::INSERT_IGNORE
            );
        }
    }
}
