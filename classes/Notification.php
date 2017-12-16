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
 * Class NotificationCore
 *
 * @since 1.0.0
 */
class NotificationCore
{
    public $types;

    /**
     * NotificationCore constructor.
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function __construct()
    {
        $this->types = ['order', 'customer_message', 'customer'];
    }

    /**
     * getLastElements return all the notifications (new order, new customer registration, and new customer message)
     * Get all the notifications
     *
     * @return array containing the notifications
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getLastElements()
    {
        $notifications = [];
        $employeeInfos = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('`id_last_order`, `id_last_customer_message`, `id_last_customer`')
                ->from('employee')
                ->where('`id_employee` = '.(int) Context::getContext()->cookie->id_employee)
        );

        foreach ($this->types as $type) {
            $notifications[$type] = Notification::getLastElementsIdsByType($type, $employeeInfos['id_last_'.$type]);
        }

        return $notifications;
    }

    /**
     * getLastElementsIdsByType return all the element ids to show (order, customer registration, and customer message)
     * Get all the element ids
     *
     * @param string $type          contains the field name of the Employee table
     * @param int    $idLastElement contains the id of the last seen element
     *
     * @return array containing the notifications
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getLastElementsIdsByType($type, $idLastElement)
    {
        switch ($type) {
            case 'order':
                $sql = (new DbQuery())
                    ->select('SQL_CALC_FOUND_ROWS o.`id_order`, o.`id_customer`, o.`total_paid`')
                    ->select('o.`id_currency`, o.`date_upd`, c.`firstname`, c.`lastname`')
                    ->from('orders', 'o')
                    ->leftJoin('customer', 'c', 'c.`id_customer` = o.`id_customer`')
                    ->where('`id_order` > '.(int) $idLastElement.' '.Shop::addSqlRestriction(false, 'o'))
                    ->orderBy('`id_order` DESC')
                    ->limit(5);
                break;

            case 'customer_message':
                $sql = (new DbQuery())
                    ->select('SQL_CALC_FOUND_ROWS c.`id_customer_message`, ct.`id_customer`, ct.`id_customer_thread`')
                    ->select('ct.`email`, c.`date_add` AS `date_upd`')
                    ->from('customer_message', 'c')
                    ->leftJoin('customer_thread', 'ct', 'c.`id_customer_thread` = ct.`id_customer_thread`')
                    ->where('c.`id_customer_message` > '.(int) $idLastElement)
                    ->where('c.`id_employee` = 0')
                    ->where('ct.`id_shop` IN ('.implode(', ', Shop::getContextListShopID()).')')
                    ->orderBy('c.`id_customer_message` DESC')
                    ->limit(5);
                break;
            default:
                $sql = (new DbQuery())
                    ->select('SQL_CALC_FOUND_ROWS t.`id_'.bqSQL($type).'`, t.*')
                    ->from(bqSQL($type), 't')
                    ->where('t.`deleted` = 0')
                    ->where('t.`id_'.bqSQL($type).'` > '.(int) $idLastElement.' '.Shop::addSqlRestriction(false, 't'))
                    ->orderBy('t.`id_'.bqSQL($type).'` DESC')
                    ->limit(5);
                break;
        }

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql, true, false);
        $total = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('SELECT FOUND_ROWS()', false);
        $json = ['total' => $total, 'results' => []];
        foreach ($result as $value) {
            $customerName = '';
            if (isset($value['firstname']) && isset($value['lastname'])) {
                $customerName = Tools::safeOutput($value['firstname'].' '.$value['lastname']);
            } elseif (isset($value['email'])) {
                $customerName = Tools::safeOutput($value['email']);
            }

            $json['results'][] = [
                'id_order'            => ((!empty($value['id_order'])) ? (int) $value['id_order'] : 0),
                'id_customer'         => ((!empty($value['id_customer'])) ? (int) $value['id_customer'] : 0),
                'id_customer_message' => ((!empty($value['id_customer_message'])) ? (int) $value['id_customer_message'] : 0),
                'id_customer_thread'  => ((!empty($value['id_customer_thread'])) ? (int) $value['id_customer_thread'] : 0),
                'total_paid'          => ((!empty($value['total_paid'])) ? Tools::displayPrice((float) $value['total_paid'], (int) $value['id_currency'], false) : 0),
                'customer_name'       => $customerName,
                // x1000 because of moment.js (see: http://momentjs.com/docs/#/parsing/unix-timestamp/)
                'update_date'         => isset($value['date_upd']) ? (int) strtotime($value['date_upd']) * 1000 : 0,
            ];
        }

        return $json;
    }

    /**
     * updateEmployeeLastElement return 0 if the field doesn't exists in Employee table.
     * Updates the last seen element by the employee
     *
     * @param string $type contains the field name of the Employee table
     *
     * @return bool if type exists or not
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function updateEmployeeLastElement($type)
    {
        global $cookie;

        if (in_array($type, $this->types)) {
            // We update the last item viewed
            return Db::getInstance()->update(
                'employee',
                [
                    'id_last_'.bqSQL($type) => ['type' => 'sql', 'value' => '(SELECT IFNULL(MAX(`id_'.$type.'`), 0) FROM `'._DB_PREFIX_.(($type == 'order') ? bqSQL($type).'s' : bqSQL($type)).'`)'],
                ],
                '`id_employee` = '.(int) $cookie->id_employee
            );
        }

        return false;
    }
}
