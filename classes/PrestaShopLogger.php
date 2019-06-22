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
 * Class PrestaShopLoggerCore
 *
 * @since 1.0.0
 */
class PrestaShopLoggerCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    protected static $is_present = [];
    /** @var int Log id */
    public $id_log;
    /** @var int Log severity */
    public $severity;
    /** @var int Error code */
    public $error_code;
    /** @var string Message */
    public $message;
    /** @var string Object type (eg. Order, Customer...) */
    public $object_type;
    /** @var int Object ID */
    public $object_id;
    /** @var int Object ID */
    public $id_employee;
    /** @var string Object creation date */
    public $date_add;
    /** @var string Object last modification date */
    public $date_upd;
    /** @var string hash code for this log object */
    protected $hash;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'log',
        'primary' => 'id_log',
        'fields'  => [
            'severity'    => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true],
            'error_code'  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'message'     => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'object_id'   => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'id_employee' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'object_type' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'date_add'    => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd'    => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];

    /**
     * add a log item to the database and send a mail if configured for this $severity
     *
     * @param string $message        the log message
     * @param int    $severity
     * @param int    $errorCode
     * @param string $objectType
     * @param int    $objectId
     * @param bool   $allowDuplicate if set to true, can log several time the same information (not recommended)
     *
     * @param null   $idEmployee
     *
     * @return bool true if succeed
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function addLog($message, $severity = 1, $errorCode = null, $objectType = null, $objectId = null, $allowDuplicate = false, $idEmployee = null)
    {
        $log = new Logger();
        $log->severity = (int) $severity;
        $log->error_code = (int) $errorCode;
        $log->message = pSQL($message ? $message : static::getEmptyMessageText());
        $log->date_add = date('Y-m-d H:i:s');
        $log->date_upd = date('Y-m-d H:i:s');

        if ($idEmployee === null && isset(Context::getContext()->employee) && Validate::isLoadedObject(Context::getContext()->employee)) {
            $idEmployee = Context::getContext()->employee->id;
        }

        if ($idEmployee !== null) {
            $log->id_employee = (int) $idEmployee;
        }

        if (!empty($objectType) && !empty($objectId)) {
            $log->object_type = pSQL($objectType);
            $log->object_id = (int) $objectId;
        }

        if ($objectType != 'Swift_Message') {
            Logger::sendByMail($log);
        }

        if ($allowDuplicate || !$log->_isPresent()) {
            $res = $log->add();
            if ($res) {
                static::$is_present[$log->getHash()] = isset(static::$is_present[$log->getHash()]) ? static::$is_present[$log->getHash()] + 1 : 1;

                return true;
            }
        }

        return false;
    }

    /**
     * Send e-mail to the shop owner only if the minimal severity level has been reached
     *
     * @param        Logger
     * @param Logger $log
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function sendByMail($log)
    {
        if ((int) Configuration::get('PS_LOGS_BY_EMAIL') <= (int) $log->severity) {
            Mail::Send(
                (int) Configuration::get('PS_LANG_DEFAULT'),
                'log_alert',
                Mail::l('Log: You have a new alert from your shop', (int) Configuration::get('PS_LANG_DEFAULT')),
                [],
                Configuration::get('PS_SHOP_EMAIL')
            );
        }
    }

    /**
     * check if this log message already exists in database.
     *
     * @return true if exists
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    protected function _isPresent()
    {
        $key = $this->getHash();
        if (! isset(static::$is_present[$key])) {
            static::$is_present[$key] = Db::getInstance()->getValue(
                'SELECT COUNT(*)
				FROM `'._DB_PREFIX_.'log`
				WHERE
					`message` = \''.$this->message.'\'
					AND `severity` = \''.$this->severity.'\'
					AND `error_code` = \''.$this->error_code.'\'
					AND `object_type` = \''.$this->object_type.'\'
					AND `object_id` = \''.$this->object_id.'\'
				'
            );
        }

        return static::$is_present[$key];
    }

    /**
     * Calculates hash key for current log entry
     *
     * @return string hash
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getHash()
    {
        if (empty($this->hash)) {
            $this->hash = md5(
                $this->message .
                $this->severity .
                $this->error_code .
                $this->object_type .
                $this->object_id
            );
        }

        return $this->hash;
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function eraseAllLogs()
    {
        return Db::getInstance()->execute('TRUNCATE TABLE '._DB_PREFIX_.'log');
    }

    /**
     * This function is called when empty message is passed to Logger::addLog(). In that case thirtybees will log
     * information about the caller
     *
     * @since 1.1.0
     * @return string
     */
    protected static function getEmptyMessageText()
    {
        foreach (debug_backtrace() as $trace) {
            if (strpos($trace['file'], __FILE__) === false) {
                $file = str_replace(_PS_ROOT_DIR_, '', $trace['file']);
                $line = $trace['line'];
                return sprintf(Tools::displayError('Logger::addLog called with empty message at %s on line %s', false), $file, $line);
            }
        }
    }
}
