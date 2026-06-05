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
 * Class CustomerMessageCore
 */
class CustomerMessageCore extends ObjectModel
{
    /**
     * @var int $id_customer_thread
     */
    public $id_customer_thread;

    /**
     * @var int $id_employee
     */
    public $id_employee;

    /**
     * @var string $message
     */
    public $message;

    /**
     * @var string|null $file_name
     */
    public $file_name;

    /**
     * @var string $ip_address
     */
    public $ip_address;

    /**
     * @var string $user_agent
     */
    public $user_agent;

    /**
     * @var int $private
     */
    public $private;

    /**
     * @var string $date_add
     */
    public $date_add;

    /**
     * @var string $date_upd
     */
    public $date_upd;

    /**
     * @var bool $read
     */
    public $read;

    /**
     * @var array Object model definition
     */
    public static $definition = [
        'table'   => 'customer_message',
        'primary' => 'id_customer_message',
        'fields'  => [
            'id_customer_thread' => ['type' => self::TYPE_INT, 'dbType' => 'int(11)'],
            'id_employee'        => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'message'            => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'required' => true, 'size' => ObjectModel::SIZE_MEDIUM_TEXT],
            'file_name'          => ['type' => self::TYPE_STRING, 'size' => ObjectModel::SIZE_TEXT],
            'ip_address'         => ['type' => self::TYPE_STRING, 'validate' => 'isIp2Long', 'size' => 16],
            'user_agent'         => ['type' => self::TYPE_STRING, 'size' => 250],
            'date_add'           => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false],
            'date_upd'           => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false],
            'private'            => ['type' => self::TYPE_INT, 'dbType' => 'tinyint(4)', 'dbDefault' => '0'],
            'read'               => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '0'],
        ],
        'keys' => [
            'customer_message' => [
                'id_customer_thread' => ['type' => ObjectModel::KEY, 'columns' => ['id_customer_thread']],
                'id_employee'        => ['type' => ObjectModel::KEY, 'columns' => ['id_employee']],
            ],
        ],
    ];

    /**
     * @var array Webservice parameters
     */
    protected $webserviceParameters = [
        'fields' => [
            'id_employee'        => [
                'xlink_resource' => 'employees',
            ],
            'id_customer_thread' => [
                'xlink_resource' => 'customer_threads',
            ],
        ],
    ];

    /**
     * @param int $idOrder
     * @param bool $hidePrivate
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getMessagesByOrderId($idOrder, $hidePrivate = true)
    {
        return Db::readOnly()->getArray(
            (new DbQuery())
                ->select('cm.*')
                ->select('c.`firstname` AS `cfirstname`')
                ->select('c.`lastname` AS `clastname`')
                ->select('e.`firstname` AS `efirstname`')
                ->select('e.`lastname` AS `elastname`')
                ->select('(COUNT(cm.id_customer_message) = 0 AND ct.id_customer != 0) AS is_new_for_me')
                ->from('customer_message', 'cm')
                ->leftJoin('customer_thread', 'ct', 'ct.`id_customer_thread` = cm.`id_customer_thread`')
                ->leftJoin('customer', 'c', 'ct.`id_customer` = c.`id_customer`')
                ->leftOuterJoin('employee', 'e', 'e.`id_employee` = cm.`id_employee`')
                ->where('ct.`id_order` = '.(int) $idOrder)
                ->where($hidePrivate ? 'cm.`private` = 0' : '')
                ->groupBy('cm.`id_customer_message`')
                ->orderBy('cm.`date_add` DESC')
        );
    }

    /**
     * @param string|null $where
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public static function getTotalCustomerMessages($where = null)
    {
        $conn = Db::readOnly();
        if (is_null($where)) {
            return (int) $conn->getValue(
                (new DbQuery())
                    ->select('COUNT(*)')
                    ->from('customer_message')
                    ->leftJoin('customer_thread', 'ct', 'cm.`id_customer_thread` = ct.`id_customer_thread`')
                    ->where('1 '.Shop::addSqlRestriction())
            );
        } else {
            return (int) $conn->getValue(
                (new DbQuery())
                    ->select('COUNT(*)')
                    ->from('customer_message', 'cm')
                    ->leftJoin('customer_thread', 'ct', 'cm.`id_customer_thread` = ct.`id_customer_thread`')
                    ->where($where.Shop::addSqlRestriction())
            );
        }
    }

    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function delete()
    {
        foreach ($this->getAttachments() as $attachment) {
            $filePath = $this->getFilePath($attachment['stored_name'] ?? '');
            if ($filePath && file_exists($filePath)) {
                unlink($filePath);
            }
        }

        return parent::delete();
    }

    /**
     * @param string|null $storedName
     *
     * @return string
     */
    public function getFilePath(?string $storedName = null): string
    {
        $storedName = $storedName ?: $this->getPrimaryStoredName();
        if ($storedName) {
            return _PS_UPLOAD_DIR_ . basename($storedName);
        }

        return '';
    }

    /**
     * @param string|null $storedName
     *
     * @return bool
     */
    public function fileExists(?string $storedName = null): bool
    {
        $filePath = $this->getFilePath($storedName);
        return (
            $filePath &&
            file_exists($filePath) &&
            is_file($filePath)
        );
    }

    /**
     * @return array
     */
    public function getAttachments(): array
    {
        return static::decodeAttachments($this->file_name);
    }

    /**
     * @return string|null
     */
    public function getPrimaryStoredName(): ?string
    {
        $attachments = $this->getAttachments();
        if (isset($attachments[0]['stored_name'])) {
            return $attachments[0]['stored_name'];
        }

        return ($this->file_name && !is_array($this->file_name)) ? (string)$this->file_name : null;
    }

    /**
     * @param string|null $encoded
     *
     * @return array
     */
    public static function decodeAttachments($encoded): array
    {
        if (!$encoded) {
            return [];
        }

        if (is_array($encoded)) {
            return array_values(array_filter($encoded, function ($attachment) {
                return isset($attachment['stored_name']) && $attachment['stored_name'];
            }));
        }

        if (is_string($encoded)) {
            $decoded = json_decode($encoded, true);

            if (is_array($decoded)) {
                return array_values(array_filter($decoded, function ($attachment) {
                    return isset($attachment['stored_name']) && $attachment['stored_name'];
                }));
            }

            return [
                [
                    'stored_name' => $encoded,
                    'original_name' => null,
                    'mime' => null,
                ],
            ];
        }

        return [];
    }

    /**
     * @param array $attachments
     *
     * @return string|null
     */
    public static function encodeAttachments(array $attachments): ?string
    {
        return $attachments ? json_encode(array_values($attachments)) : null;
    }

    /**
     * Normalize the result of Tools::fileAttachment so controllers can handle single or multiple uploads the same way.
     *
     * @param array|null $fileAttachment
     *
     * @return array
     */
    public static function normalizeFileAttachments($fileAttachment): array
    {
        if (!$fileAttachment) {
            return [];
        }

        if (isset($fileAttachment['rename'])) {
            return [$fileAttachment];
        }

        if (is_array($fileAttachment)) {
            return array_values(array_filter($fileAttachment, function ($attachment) {
                return is_array($attachment) && isset($attachment['rename']);
            }));
        }

        return [];
    }

    /**
     * Hydrate attachment information for a list of message rows.
     *
     * @param array $messages
     *
     * @return array
     */
    public static function appendAttachmentData(array $messages): array
    {
        foreach ($messages as &$message) {
            $message['attachments'] = static::decodeAttachments($message['file_name'] ?? null);
        }

        return $messages;
    }

}
