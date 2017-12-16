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
 * Class CustomerThreadCore
 *
 * @since 1.0.0
 */
class CustomerThreadCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    /** @var int $id_contact */
    public $id_contact;
    /** @var int $id_customer */
    public $id_customer;
    /** @var int $id_order */
    public $id_order;
    /** @var int $id_product */
    public $id_product;
    /** @var bool $status */
    public $status;
    /** @var string $email */
    public $email;
    /** @var string $token */
    public $token;
    /** @var string $date_add */
    public $date_add;
    /** @var string $date_upd */
    public $date_upd;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'customer_thread',
        'primary' => 'id_customer_thread',
        'fields'  => [
            'id_lang'     => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_contact'  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_shop'     => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_order'    => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_product'  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'email'       => ['type' => self::TYPE_STRING, 'validate' => 'isEmail', 'size' => 254],
            'token'       => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true],
            'status'      => ['type' => self::TYPE_STRING],
            'date_add'    => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd'    => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];
    protected $webserviceParameters = [
        'fields'       => [
            'id_lang'     => [
                'xlink_resource' => 'languages',
            ],
            'id_shop'     => [
                'xlink_resource' => 'shops',
            ],
            'id_customer' => [
                'xlink_resource' => 'customers',
            ],
            'id_order'    => [
                'xlink_resource' => 'orders',
            ],
            'id_product'  => [
                'xlink_resource' => 'products',
            ],
        ],
        'associations' => [
            'customer_messages' => [
                'resource' => 'customer_message',
                'id'       => ['required' => true],
            ],
        ],
    ];

    /**
     * @param int      $idCustomer
     * @param int|null $read
     * @param int|null $idOrder
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getCustomerMessages($idCustomer, $read = null, $idOrder = null)
    {
        $sql = (new DbQuery())
            ->select('*')
            ->from('customer_thread', 'ct')
            ->leftJoin('customer_message', 'cm', 'ct.`id_customer_thread` = cm.`id_customer_thread`')
            ->where('`id_customer` = '.(int) $idCustomer);

        if ($read !== null) {
            $sql->where('cm.`read` = '.(int) $read);
        }
        if ($idOrder !== null) {
            $sql->where('ct.`id_order` = '.(int) $idOrder);
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }

    /**
     * @param string $email
     * @param int    $idOrder
     *
     * @return false|null|string
     *
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getIdCustomerThreadByEmailAndIdOrder($email, $idOrder)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('cm.`id_customer_thread`')
                ->from('customer_thread', 'cm')
                ->where('cm.`email` = \''.pSQL($email).'\'')
                ->where('cm.`id_shop` = '.(int) Context::getContext()->shop->id)
                ->where('cm.`id_order` = '.(int) $idOrder)
        );
    }

    /**
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getContacts()
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('cl.*, COUNT(*) as `total`')
                ->select('(SELECT `id_customer_thread` FROM `'._DB_PREFIX_.'customer_thread` ct2 WHERE status = "open" AND ct.`id_contact` = ct2.`id_contact` '.Shop::addSqlRestriction().' ORDER BY `date_upd` ASC LIMIT 1) AS `id_customer_thread`')
                ->from('customer_thread', 'ct')
                ->leftJoin('contact_lang', 'cl', 'cl.`id_contact` = ct.`id_contact` AND cl.`id_lang` = '.(int) Context::getContext()->language->id)
                ->where('ct.`status` = "open"')
                ->where('ct.`id_contact` IS NOT NULL')
                ->where('cl.`id_contact` IS NOT NULL '.Shop::addSqlRestriction())
                ->groupBy('ct.`id_contact`')
                ->having('COUNT(*) > 0')
        );
    }

    /**
     * @param string|null $where
     *
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getTotalCustomerThreads($where = null)
    {
        return (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('COUNT(*)')
                ->from('customer_thread')
                ->where(($where ?: '1').' '.Shop::addSqlRestriction())
        );
    }

    /**
     * @param int $idCustomerThread
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getMessageCustomerThreads($idCustomerThread)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('ct.*, cm.*, cl.name subject, CONCAT(e.firstname, \' \', e.lastname) employee_name')
                ->select('CONCAT(c.firstname, \' \', c.lastname) customer_name, c.firstname')
                ->from('customer_thread', 'ct')
                ->leftJoin('customer_message', 'cm', 'ct.`id_customer_thread` = cm.`id_customer_thread`')
                ->leftJoin('contact_lang', 'cl', 'cl.`id_contact` = ct.`id_contact` AND cl.`id_lang` = '.(int) Context::getContext()->language->id)
                ->leftJoin('employee', 'e', 'e.`id_employee` = cm.`id_employee`')
                ->leftJoin('customer', 'c', '(IFNULL(ct.`id_customer`, ct.`email`) = IFNULL(c.`id_customer`, c.`email`))')
                ->where('ct.`id_customer_thread` = '.(int) $idCustomerThread)
                ->orderBy('cm.`date_add` ASC')
        );
    }

    /**
     * @param int $idCustomerThread
     *
     * @return false|null|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getNextThread($idCustomerThread)
    {
        $context = Context::getContext();

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`id_customer_thread`')
                ->from('customer_thread', 'ct')
                ->where('ct.status = "open"')
                ->where('ct.`date_upd` = (SELECT date_add FROM '._DB_PREFIX_.'customer_message WHERE (id_employee IS NULL OR id_employee = 0) AND id_customer_thread = '.(int) $idCustomerThread.' ORDER BY date_add DESC LIMIT 1)')
                ->where($context->cookie->{'customer_threadFilter_cl!id_contact'} ? 'ct.`id_contact` = '.(int) $context->cookie->{'customer_threadFilter_cl!id_contact'} : '')
                ->where($context->cookie->{'customer_threadFilter_l!id_lang'} ? 'ct.`id_lang` = '.(int) $context->cookie->{'customer_threadFilter_l!id_lang'} : '')
                ->orderBy('ct.`date_upd` ASC')
        );
    }

    /**
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getWsCustomerMessages()
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('`id_customer_message` AS `id`')
                ->from('customer_message')
                ->where('`id_customer_thread` = '.(int) $this->id)
        );
    }

    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function delete()
    {
        if (!Validate::isUnsignedId($this->id)) {
            return false;
        }

        $return = true;
        $result = Db::getInstance()->executeS(
            (new DbQuery())
                ->select('`id_customer_message`')
                ->from('customer_message')
                ->where('`id_customer_thread` = '.(int) $this->id)
        );

        if (count($result)) {
            foreach ($result as $res) {
                $message = new CustomerMessage((int) $res['id_customer_message']);
                if (!Validate::isLoadedObject($message)) {
                    $return = false;
                } else {
                    $return &= $message->delete();
                }
            }
        }
        $return &= parent::delete();

        return $return;
    }
}
