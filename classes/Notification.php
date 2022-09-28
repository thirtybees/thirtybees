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
    /**
     * @var array
     */
    protected $types;

    /**
     * @var int
     */
    protected $employeeId;

    /**
     * @var array
     */
    protected $lastSeenIds = null;

    /**
     * @var array
     */
    protected $permissions;

    /**
     * NotificationCore constructor.
     *
     * @param EmployeeCore|null $employee
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function __construct($employee = null)
    {
        if (! $employee) {
            $employee = Context::getContext()->employee;
        }

        $this->employeeId = (int)$employee->id;
        $this->permissions = [];
        foreach (Profile::getProfileAccesses($employee->id_profile, 'class_name') as $tab => $access) {
            $this->permissions[$tab] = (bool)$access['view'];
        }

        $this->types = [];

        // register build in notification types
        if (Configuration::get('PS_SHOW_NEW_ORDERS')) {
            $this->registerType('order', [
                'getNotifications' => [$this, 'getNewOrders'],
                'renderer' => 'renderOrderNotification',
                'rendererData' => [
                    'orderNumber' => $this->l('Order number:'),
                    'total' => $this->l('Total:'),
                    'from' => $this->l('From:'),
                ],
                'controller' => 'AdminOrders',
                'icon' => 'icon-shopping-cart',
                'header' => $this->l('Latest Orders'),
                'emptyMessage' => $this->l('No new orders have been placed on your shop.'),
                'showAll' => $this->l('Show all orders'),
            ]);
        }

        if (Configuration::get('PS_SHOW_NEW_CUSTOMERS')) {
            $this->registerType('customer', [
                'getNotifications' => [$this, 'getNewCustomers'],
                'renderer' => 'renderCustomerNotification',
                'rendererData' => [
                    'customerName' => $this->l('Customer name:'),
                ],
                'controller' => 'AdminCustomers',
                'icon' => 'icon-user',
                'header' => $this->l('Latest Registrations'),
                'emptyMessage' => $this->l('No new customers have registered on your shop.'),
                'showAll' => $this->l('Show all customers'),
            ]);
        }

        if (Configuration::get('PS_SHOW_NEW_MESSAGES')) {
            $this->registerType('customer_message', [
                'getNotifications' => [$this, 'getNewCustomerMessages'],
                'renderer' => 'renderCustomerMessageNotification',
                'rendererData' => [
                    'from' => $this->l('From:'),
                ],
                'controller' => 'AdminCustomerThreads',
                'icon' => 'icon-envelope',
                'header' => $this->l('Latest Messages'),
                'emptyMessage' => $this->l('No new messages have been posted on your shop.'),
                'showAll' => $this->l('Show all messages'),
            ]);
        }

        // Register modules notification types
        foreach (static::getModuleNotificationTypes() as $type => $definition) {
            $this->registerType($type, $definition);
        }
    }

    /**
     * Returns notification types defined by modules
     *
     * @throws PrestaShopException
     */
    protected static function getModuleNotificationTypes()
    {
        static $moduleTypes = null;
        if (is_null($moduleTypes)) {
            $moduleTypes = static::resolveModuleNotificationTypes();
        }
        return $moduleTypes;
    }

    /**
     * Returns notification types defined by modules
     *
     * @return array
     * @throws PrestaShopException
     */
    protected static function resolveModuleNotificationTypes()
    {
        $moduleTypes = [];
        $result = Hook::exec('actionGetNotificationType', [], null, true);
        if (is_array($result)) {
            foreach ($result as $moduleName => $definitions) {
                if (is_array($definitions)) {
                    foreach ($definitions as $type => $definition) {
                        $fullType = $moduleName . '_' . $type;
                        $fullType = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $fullType));
                        $moduleTypes[$fullType] = $definition;
                    }
                }
            }
        }
        return $moduleTypes;
    }

    /**
     * Returns enabled notification types
     *
     * @return array
     * @throws PrestaShopException
     */
    public function getTypes()
    {
        $ret = [];
        $link = Context::getContext()->link;
        foreach ($this->types as $type => $description) {
            $ret[] = [
                'type' => $type,
                'icon' => $description['icon'],
                'header' => $description['header'],
                'emptyMessage' => $description['emptyMessage'],
                'showAll' => $description['showAll'],
                'showAllLink' => ($description['showAllLink'] ?? $link->getAdminLink($description['controller'])
                )
            ];
        }
        return $ret;
    }

    /**
     * Initialize notifications to match current last id.
     * Used when new employee is created, to prevent too many unread notifications
     *
     * @throws PrestaShopException
     */
    public function initialize()
    {
        foreach ($this->getNotifications() as $notification) {
            $type = $notification['type'];
            $lastId = (int)$notification['lastId'];
            $this->markAsRead($type, $lastId);
        }
    }

    /**
     * Returns true, if $type is supported
     *
     * @param $type
     * @return bool
     */
    public function hasType($type)
    {
        return isset($this->types[$type]);
    }

    /**
     * Returns last seen notification id for given type
     *
     * @param string $type
     * @return int
     * @throws PrestaShopException
     */
    public function getLastSeenId($type)
    {
        if (is_null($this->lastSeenIds)) {
            $this->lastSeenIds = [];
            $types = "'" . implode("', '", array_map('pSql', array_keys($this->types))) . "'";
            $sql = (new DbQuery())
                ->select('type, last_id')
                ->from('employee_notification')
                ->where('id_employee = ' . $this->employeeId)
                ->where('type IN (' . $types . ')');
            $employeeInfos = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
            if (is_array($employeeInfos)) {
                foreach ($employeeInfos as $row) {
                    $this->lastSeenIds[$row['type']] = (int)$row['last_id'];
                }
            }
        }
        return $this->lastSeenIds[$type] ?? 0;
    }

    /**
     * Get all unread the notifications
     *
     * @param array $typeFilter list of types to return. Pass null to return all supported types
     *
     * @return array containing the notifications
     *
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getNotifications($typeFilter = null)
    {
        $notifications = [];
        foreach ($this->types as $type => $description) {
            if (! is_null($typeFilter)) {
                if (! in_array($type, $typeFilter)) {
                    continue;
                }
            }
            $callable = $description['getNotifications'];
            if (is_callable($callable)) {
                $notifications[] = array_merge(
                    [
                        'type' => $type,
                        'renderer' => $description['renderer'],
                        'rendererData' => $description['rendererData'],
                    ],
                    $callable($this->getLastSeenId($type), 5)
                );
            }
        }
        return $notifications;
    }

    /**
     * Marks notification of given types as read
     *
     * @param string $type - notification type, must be allowed in $this->types
     * @param int $lastId - last notification id that employee seen
     * @return bool
     * @throws PrestaShopException
     */
    public function markAsRead($type, $lastId)
    {
        if (! isset($this->types[$type])) {
            return false;
        }

        $lastId  = (int)$lastId;
        if ($lastId <= 0) {
            return false;
        }

        return Db::getInstance()->insert(
            'employee_notification',
            [
                'id_employee' => $this->employeeId,
                'type' => pSQL($type),
                'last_id' => $lastId
            ],
            false,
            false,
            Db::REPLACE
        );
    }

    /**
     * Returns information about orders created after $lastId
     *
     * @param int $lastId order id
     * @param int $limit number of detail rows to return
     *
     * @return array
     * @throws PrestaShopException
     */
    protected function getNewOrders($lastId, $limit)
    {
        $link = Context::getContext()->link;

        $baseSql = (new DbQuery())
            ->from('orders', 'o')
            ->leftJoin('customer', 'c', 'c.`id_customer` = o.`id_customer`')
            ->where('`id_order` > '. $lastId.' '.Shop::addSqlRestriction(false, 'o'));

        $totalSql = clone $baseSql;
        $totalSql->select('COUNT(1)');

        $detailSql = clone $baseSql;
        $detailSql
            ->select('o.`id_order`, o.`total_paid`, o.`id_currency`, o.`date_add`, c.`id_customer`, CONCAT(c.`firstname`, " ", c.`lastname`) as name')
            ->orderBy('`id_order` DESC')
            ->limit($limit);

        $connection = Db::getInstance(_PS_USE_SQL_SLAVE_);

        $result = $connection->executeS($detailSql);
        $total = (int)$connection->getValue($totalSql);

        $results = [];
        foreach ($result as $row) {
            $id = (int) $row['id_order'];
            $lastId = max($id, $lastId);
            $results[] = [
                'link' => $link->getAdminLink('AdminOrders', true, ['vieworder' => 1, 'id_order' => $id]),
                'id' => $id,
                'total' => Tools::displayPrice((float) $row['total_paid'], (int) $row['id_currency']),
                'customerName' => $row['name'],
                'ts' => (int)strtotime($row['date_add'])
            ];
        }

        return [
            'total' => $total,
            'lastId' => $lastId,
            'results' => $results
        ];
    }

    /**
     * Returns information about new customers created after $lastId
     *
     * @param int $lastId order id
     * @param int $limit number of detail rows to return
     *
     * @return array
     * @throws PrestaShopException
     */
    protected function getNewCustomers($lastId, $limit)
    {
        $lastId = (int)$lastId;
        $link = Context::getContext()->link;

        $baseSql = (new DbQuery())
            ->from('customer', 'c')
            ->where('c.`deleted` = 0')
            ->where('c.`id_customer` > '.$lastId.' '.Shop::addSqlRestriction(false, 'c'));

        $totalSql = clone $baseSql;
        $totalSql->select('COUNT(1)');

        $detailSql = clone $baseSql;
        $detailSql
            ->select('c.`id_customer`, c.`date_add`, CONCAT(c.`firstname`, " ", c.`lastname`) as name')
            ->orderBy('`id_customer` DESC')
            ->limit($limit);

        $connection = Db::getInstance(_PS_USE_SQL_SLAVE_);

        $result = $connection->executeS($detailSql);
        $total = (int)$connection->getValue($totalSql);

        $results = [];
        foreach ($result as $row) {
            $id = (int) $row['id_customer'];
            $lastId = max($id, $lastId);
            $results[] = [
                'link' => $link->getAdminLink('AdminCustomers', true, ['viewcustomer' => 1, 'id_customer' => $id]),
                'id' => $id,
                'customerName' => $row['name'],
                'ts' => (int)strtotime($row['date_add'])
            ];
        }

        return [
            'total' => $total,
            'lastId' => $lastId,
            'results' => $results
        ];
    }

    /**
     * Returns information about customer messages created after $lastId
     *
     * @param int $lastId order id
     * @param int $limit number of detail rows to return
     *
     * @return array
     * @throws PrestaShopException
     */
    protected function getNewCustomerMessages($lastId, $limit)
    {
        $lastId = (int)$lastId;
        $link = Context::getContext()->link;

        $baseSql = (new DbQuery())
            ->from('customer_message', 'c')
            ->leftJoin('customer_thread', 'ct', 'c.`id_customer_thread` = ct.`id_customer_thread`')
            ->leftJoin('customer', 'customer', 'ct.`id_customer` = customer.`id_customer`')
            ->where('c.`id_customer_message` > ' . $lastId)
            ->where('c.`id_employee` = 0')
            ->where('ct.`id_shop` IN (' . implode(', ', Shop::getContextListShopID()) . ')');

        $totalSql = clone $baseSql;
        $totalSql->select('COUNT(1)');

        $detailSql = clone $baseSql;
        $detailSql
            ->select('c.`id_customer_message`, ct.`id_customer_thread`')
            ->select('ct.`email`, c.`date_add`, customer.id_customer, customer.firstname, customer.lastname, customer.email as customerEmail')
            ->orderBy('c.`id_customer_message` DESC')
            ->limit($limit);

        $connection = Db::getInstance(_PS_USE_SQL_SLAVE_);

        $result = $connection->executeS($detailSql);
        $total = (int)$connection->getValue($totalSql);

        $results = [];
        foreach ($result as $row) {
            $id = (int)$row['id_customer_message'];
            $threadId = (int)$row['id_customer_thread'];
            $lastId = max($id, $lastId);
            $customerId = (int)$row['id_customer'];
            if ($customerId) {
                $email = $row['customerEmail'] ? $row['customerEmail'] : $row['email'];
                $from = $row['firstname'] . ' ' . $row['lastname'] . ' - ' . $email;
            } else {
                $from = $row['email'];
            }
            $results[] = [
                'link' => $link->getAdminLink('AdminCustomerThreads', true, ['viewcustomer_thread' => 1, 'id_customer_thread' => $threadId]),
                'id' => $id,
                'from' => $from,
                'ts' => (int)strtotime($row['date_add'])
            ];
        }

        return [
            'total' => $total,
            'lastId' => $lastId,
            'results' => $results
        ];
    }


    /**
     * Registers new notification type
     *
     * @param string $type
     * @param array $definition
     * @return bool
     * @throws PrestaShopException
     */
    protected function registerType($type, $definition)
    {
        // validate $definition
        $required = ['getNotifications', 'renderer', 'icon', 'header', 'emptyMessage', 'showAll'];
        foreach ($required as $item) {
            if (! isset($definition[$item])) {
                throw new PrestaShopException('Invalid notification definition "' . $type . '": missing field "' . $item . '"');
            }
        }
        if (! is_callable($definition['getNotifications'])) {
            throw new PrestaShopException('Invalid notification definition "' . $type . '": "getNotification" is not callable');
        }
        if (!isset($definition['controller']) && !isset($definition['showAllLink'])) {
            throw new PrestaShopException('Invalid notification definition "' . $type . '": "either "showAllLink" or "controller" must be specified');
        }
        if (isset($definition['controller'])) {
            $controller = $definition['controller'];
            if (! isset($this->permissions[$controller])) {
                return false;
            }
            if (! $this->permissions[$controller]) {
                return false;
            }
        }
        if (! isset($definition['rendererData'])) {
            $definition['rendererData'] = [];
        }
        $this->types[$type] = $definition;
        return true;
    }


    /**
     * Translate method
     *
     * @param string $str
     * @return string
     */
    protected function l($str)
    {
        return Translate::getAdminTranslation($str, 'AdminController');
    }
}
