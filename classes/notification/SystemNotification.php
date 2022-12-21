<?php
/**
 * Copyright (C) 2023-2023 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2023-2023 thirty bees
 * @license   Open Software License (OSL 3.0)
 */

namespace Thirtybees\Core\Notification;

use Db;
use DbQuery;
use ObjectModel;
use PrestaShopException;

/**
 * Class SystemNotificationCore
 */
class SystemNotificationCore extends ObjectModel
{
    const IMPORTANCE_LOW = 'LOW';
    const IMPORTANCE_MEDIUM = 'MEDIUM';
    const IMPORTANCE_HIGH = 'HIGH';
    const IMPORTANCE_URGENT = 'URGENT';

    /**
     * @var string unique message identification
     */
    public $uuid;

    /**
     * @var string importance level
     */
    public $importance;

    /**
     * @var string short description of notification
     */
    public $title;

    /**
     * @var string notificatiton message, html
     */
    public $message;

    /**
     * @var string date when the notification was published
     */
    public $date_created;

    /**
     * @var string date when the notification was added to the database
     */
    public $date_add;

    /**
     * @var int importance level
     */
    public $date_upd;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'system_notification',
        'primary' => 'id_system_notification',
        'multilang' => false,
        'fields' => [
            'uuid' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 32],
            'importance' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'values' => [
                self::IMPORTANCE_LOW,
                self::IMPORTANCE_MEDIUM,
                self::IMPORTANCE_HIGH,
                self::IMPORTANCE_URGENT
            ]],
            'title' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 512],
            'message' => ['type' => self::TYPE_HTML, 'validate' => 'isString', 'size' => ObjectModel::SIZE_MEDIUM_TEXT ],
            'date_created' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
        'keys' => [
            'system_notification' => [
                'notification_uuid' => ['type' => ObjectModel::UNIQUE_KEY, 'columns' => ['uuid']],
            ]
        ]
    ];

    /**
     * Returns notification by its UUID
     *
     * @param string $uuid
     * @return static | null
     * @throws PrestaShopException
     */
    public static function getByUuid($uuid)
    {
        $id = (int)Db::getInstance()->getValue((new DbQuery())
            ->select('id_system_notification')
            ->from('system_notification')
            ->where("uuid = '" . pSQL($uuid) ."'")
        );
        if ($id) {
            return new static($id);
        }
        return null;
    }

    /**
     * @param string $importance
     *
     * @return string
     */
    public static function getBadgeClass($importance)
    {
        switch ($importance) {
            case static::IMPORTANCE_LOW:
                return 'badge-info';
            case static::IMPORTANCE_MEDIUM:
                return 'badge-success';
            case static::IMPORTANCE_HIGH:
                return '';
            case static::IMPORTANCE_URGENT:
                return 'badge-danger';
            default:
                return '';
        }
    }
}
