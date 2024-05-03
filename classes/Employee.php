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

use GuzzleHttp\Client;
use Thirtybees\Core\InitializationCallback;

/**
 * Class EmployeeCore
 */
class EmployeeCore extends ObjectModel implements InitializationCallback
{
    /**
     * @var int Determine employee profile
     */
    public $id_profile;

    /**
     * @var int employee language
     */
    public $id_lang;

    /**
     * @var string Lastname
     */
    public $lastname;

    /**
     * @var string Firstname
     */
    public $firstname;

    /**
     * @var string e-mail
     */
    public $email;

    /**
     * @var string Password
     */
    public $passwd;

    /**
     * @var string Password
     */
    public $last_passwd_gen;

    /**
     * @var string $stats_date_from
     */
    public $stats_date_from;

    /**
     * @var string $stats_date_to
     */
    public $stats_date_to;

    /**
     * @var string $stats_compare_from
     */
    public $stats_compare_from;

    /**
     * @var string $stats_compare_to
     */
    public $stats_compare_to;

    /**
     * @var int $stats_compare_option
     */
    public $stats_compare_option = 1;

    /**
     * @var string $preselect_date_range
     */
    public $preselect_date_range;

    /**
     * @var string Display back office background in the specified color
     */
    public $bo_color;

    /**
     * @var int
     */
    public $default_tab;

    /**
     * @var string employee's chosen theme
     */
    public $bo_theme;

    /**
     * @var string employee's chosen css file
     */
    public $bo_css = 'admin-theme.css';

    /**
     * @var int employee desired screen width
     */
    public $bo_width;

    /**
     * @var bool, false
     */
    public $bo_menu = 1;

    /**
     * @var bool
     */
    public $bo_show_screencast = false;

    /**
     * @var bool Status
     */
    public $active = 1;

    /**
     * @var bool Optin status
     */
    public $optin = 1;

    /**
     * @var int[]
     */
    protected $associated_shops = [];

    /**
     * @var string
     */
    public $last_connection_date;

    /**
     * @var string stored HMAC-SHA256 signature of security-critical fields
     */
    public $signature;

    /**
     * @var Notification|null
     */
    protected $notification = null;

    /**
     * @var array Object model definition
     */
    public static $definition = [
        'table'   => 'employee',
        'primary' => 'id_employee',
        'fields'  => [
            'id_profile'               => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true               ],
            'id_lang'                  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true, 'dbDefault' => '0'],
            'lastname'                 => ['type' => self::TYPE_STRING, 'validate' => 'isName', 'required' => true, 'size' => 32 ],
            'firstname'                => ['type' => self::TYPE_STRING, 'validate' => 'isName', 'required' => true, 'size' => 32 ],
            'email'                    => ['type' => self::TYPE_STRING, 'validate' => 'isEmail', 'required' => true, 'size' => 128],
            'passwd'                   => ['type' => self::TYPE_STRING, 'validate' => 'isPasswdAdmin', 'required' => true, 'size' => 60 ],
            'last_passwd_gen'          => ['type' => self::TYPE_DATE, 'dbType' => 'timestamp', 'dbDefault' => ObjectModel::DEFAULT_CURRENT_TIMESTAMP],
            'stats_date_from'          => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbType' => 'date'],
            'stats_date_to'            => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbType' => 'date'],
            'stats_compare_from'       => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbType' => 'date'],
            'stats_compare_to'         => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbType' => 'date'],
            'stats_compare_option'     => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt' , 'size' => 1, 'dbType' => 'int(1) unsigned', 'dbDefault' => '1'],
            'preselect_date_range'     => ['type' => self::TYPE_STRING, 'size' => 32 ],
            'bo_color'                 => ['type' => self::TYPE_STRING, 'validate' => 'isColor', 'size' => 32 ],
            'bo_theme'                 => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 32 ],
            'bo_css'                   => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 64 ],
            'default_tab'              => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'dbDefault' => '0'],
            'bo_width'                 => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'dbDefault' => '0'],
            'bo_menu'                  => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '1'],
            'active'                   => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'],
            'optin'                    => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '1'],
            'last_connection_date'     => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => true],
            'signature'                => ['type' => self::TYPE_STRING, 'validate' => 'isSha256', 'size' => 64, 'copy_post' => false],
        ],
        'keys' => [
            'employee' => [
                'employee_login'     => ['type' => ObjectModel::KEY, 'columns' => ['email', 'passwd']],
                'id_employee_passwd' => ['type' => ObjectModel::KEY, 'columns' => ['id_employee', 'passwd']],
                'id_profile'         => ['type' => ObjectModel::KEY, 'columns' => ['id_profile']],
            ],
            'employee_shop' => [
                'id_shop' => ['type' => ObjectModel::KEY, 'columns' => ['id_shop']],
            ],
        ],
    ];

    /**
     * @var array Webservice parameters
     */
    protected $webserviceParameters = [
        'fields' => [
            'id_lang'            => ['xlink_resource' => 'languages'],
            'last_passwd_gen'    => ['setter' => null],
            'stats_date_from'    => ['setter' => null],
            'stats_date_to'      => ['setter' => null],
            'stats_compare_from' => ['setter' => null],
            'stats_compare_to'   => ['setter' => null],
            'passwd'             => ['setter' => 'setWsPasswd'],
        ],
    ];

    /**
     * EmployeeCore constructor.
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
        parent::__construct($id, null, $idShop);

        if (!is_null($idLang)) {
            $this->id_lang = (int) (Language::getLanguage($idLang) !== false) ? $idLang : Configuration::get('PS_LANG_DEFAULT');
        }

        if ($this->id) {
            $this->associated_shops = $this->getAssociatedShops();
        }

        $this->image_dir = _PS_EMPLOYEE_IMG_DIR_;
    }

    /**
     * Return list of employees
     *
     * @param bool $activeOnly Filter employee by active status
     *
     * @return array Employees
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getEmployees($activeOnly = true)
    {
        $sql = new DbQuery();
        $sql->select('`id_employee`, `firstname`, `lastname`');
        $sql->from(bqSQL(static::$definition['table']));
        if ($activeOnly) {
            $sql->where('`active` = 1');
        }
        $sql->orderBy('`lastname` ASC');

        return Db::readOnly()->getArray($sql);
    }

    /**
     * @param string $email
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function employeeExists($email)
    {
        return (bool) Db::readOnly()->getValue(
            (new DbQuery())
                ->select('`id_employee`')
                ->from('employee')
                ->where('`email` = \''.pSQL($email).'\'')
        );
    }

    /**
     * @param int $idProfile
     * @param bool $activeOnly
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getEmployeesByProfile($idProfile, $activeOnly = false)
    {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from(bqSQL(static::$definition['table']));
        $sql->where('`id_profile` = '.(int) $idProfile);
        if ($activeOnly) {
            $sql->where('`active` = 1');
        }

        return Db::readOnly()->getArray($sql);
    }

    /**
     * @param int $idEmployee
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function setLastConnectionDate($idEmployee)
    {
        $idEmployee = (int)$idEmployee;
        if ($idEmployee) {
            return Db::getInstance()->update(
                bqSQL(static::$definition['table']),
                [
                    'last_connection_date' => date('Y-m-d H:i:s')
                ],
                '`id_employee` = ' . (int)$idEmployee
            );
        }
        return false;
    }

    /**
     * @return array
     *
     * @throws PrestaShopException
     */
    public function getFields()
    {
        if (empty($this->stats_date_from) || $this->stats_date_from == '0000-00-00') {
            $this->stats_date_from = date('Y-m-d', strtotime('-1 month'));
        }

        if (empty($this->stats_compare_from) || $this->stats_compare_from == '0000-00-00') {
            $this->stats_compare_from = null;
        }

        if (empty($this->stats_date_to) || $this->stats_date_to == '0000-00-00') {
            $this->stats_date_to = date('Y-m-d');
        }

        if (empty($this->stats_compare_to) || $this->stats_compare_to == '0000-00-00') {
            $this->stats_compare_to = null;
        }

        return parent::getFields();
    }

    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function add($autoDate = true, $nullValues = true)
    {
        $this->last_passwd_gen = date('Y-m-d H:i:s', strtotime('-'.Configuration::get('PS_PASSWD_TIME_BACK').'minutes'));
        $this->saveOptin();
        $this->updateTextDirection();

        $result = parent::add($autoDate, $nullValues);
        $result = $this->updateSignature() && $result;
        return $result;
    }

    /**
     * Subscribe to the thirty bees newsletter. Also resets $this->optin on
     * failure.
     *
     * @return bool Wether un/registration was successful.
     *
     * @throws PrestaShopException
     */
    protected function saveOptin()
    {
        $success = true;

        if (!defined('TB_INSTALLATION_IN_PROGRESS')) {
            if ($this->optin && $this->email) {
                $context = Context::getContext();

                $guzzle = new Client([
                    'base_uri'    => Configuration::getApiServer(),
                    'timeout'     => 20,
                    'verify'      => Configuration::getSslTrustStore(),
                ]);

                try {
                    $body = $guzzle->post(
                        '/newsletter/', [
                            'json' => [
                                'email'    => $this->email,
                                'fname'    => $this->firstname,
                                'lname'    => $this->lastname,
                                'activity' => Configuration::get('PS_SHOP_ACTIVITY'),
                                'country'  => $context->country->iso_code,
                                'language' => $context->language->iso_code,
                                'URL'      => $context->shop->getBaseURL(),
                            ],
                            'headers' => [
                                'X-SID' => Configuration::getServerTrackingId()
                            ]
                        ]
                    )->getBody();

                    if ((string) $body) {
                        // Service itself wasn't successful.
                        $success = false;
                        $this->optin = false;
                    }

                } catch (Throwable $e) {
                    $success = false;
                    $this->optin = false;
                }
            }
        }

        return $success;
    }

    /**
     * Deletes this employee
     *
     * @return bool
     * @throws PrestaShopException
     */
    public function delete()
    {
        $id = (int)$this->id;
        if ($id) {
            Db::getInstance()->delete('employee_notification', 'id_employee = ' . $id);
        }
        return parent::delete();
    }

    /**
     * @throws PrestaShopException
     */
    protected function updateTextDirection()
    {
        if (defined('_PS_ADMIN_DIR_')) {
            $path = _PS_ADMIN_DIR_.'/themes/'.$this->bo_theme.'/css/';
        } else {
            // Probably installation in progress.
            $path = _PS_ROOT_DIR_.'/admin/themes/'.$this->bo_theme.'/css/';
            if ( ! is_dir($path)) {
                $path = _PS_ROOT_DIR_.'/admin-dev/themes/'.$this->bo_theme.'/css/';
                if ( ! is_dir($path)) {
                    // Give up.
                    return;
                }
            }
        }

        $language = new Language($this->id_lang);

        if ($language->is_rtl && !strpos($this->bo_css, '_rtl')) {
            $boCss = preg_replace('/^(.*)\.css$/', '$1_rtl.css', $this->bo_css);
            $boCss = str_replace('schemes/', 'schemes_rtl/', $boCss);

            if (file_exists($path.$boCss)) {
                $this->bo_css = $boCss;
            }
        } elseif (!$language->is_rtl && strpos($this->bo_css, '_rtl')) {
            $boCss = str_replace('_rtl', '', $this->bo_css);

            if (file_exists($path.$boCss)) {
                $this->bo_css = $boCss;
            }
        }
    }

    /**
     * Update the database record. Also used by AdminDashboardController for
     * newsletter registration.
     *
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function update($nullValues = false)
    {
        $success = true;

        if (empty($this->stats_date_from) || $this->stats_date_from == '0000-00-00') {
            $this->stats_date_from = date('Y-m-d');
        }

        if (empty($this->stats_date_to) || $this->stats_date_to == '0000-00-00') {
            $this->stats_date_to = date('Y-m-d');
        }

        $currentEmployee = new Employee((int) $this->id);
        if ($currentEmployee->optin != $this->optin
            || $currentEmployee->email != $this->email
            || !Configuration::get('TB_STORE_REGISTERED')) {
            $success = $this->saveOptin();
        }

        $this->updateTextDirection();

        $success = parent::update($nullValues) && $success;
        $success = $this->updateSignature() && $success;
        return $success;
    }

    /**
     * Return employee instance from its e-mail (optionally check password)
     *
     * @param string $email E-mail
     * @param string $plainTextPassword Password is also checked if specified
     * @param bool $activeOnly Filter employee by active status
     *
     * @return static|bool Employee instance
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getByEmail($email, $plainTextPassword = null, $activeOnly = true)
    {
        if (!Validate::isEmail($email) || ($plainTextPassword && !Validate::isPasswdAdmin($plainTextPassword))) {
            return false;
        }

        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('employee');
        $sql->where('`email` = \''.pSQL($email).'\'');
        if ($activeOnly) {
            $sql->where('`active` = 1');
        }
        $result = Db::readOnly()->getRow($sql);

        if (!$result) {
            return false;
        }

        // verify that stored password/email/profile/signature was not tampered with
        $employeeId = (int)$result['id_employee'];
        $profileId = (int)$result['id_profile'];
        $storedPassword = $result['passwd'];
        $storedEmail = $result['email'];
        $storedSignature = $result['signature'];
        $calculatedSignature = static::calculateSignature($employeeId, $profileId, $storedEmail, $storedPassword);
        if ($storedSignature !== $calculatedSignature) {
            return false;
        }

        if ($plainTextPassword && !password_verify($plainTextPassword, $storedPassword)) {
            // Check if it matches the legacy md5 hashing and, if it does, rehash it.
            if (Validate::isMd5($storedPassword) && $storedPassword === md5(_COOKIE_KEY_.$plainTextPassword)) {
                $newPassword = Tools::hash($plainTextPassword);
                $newSignature = static::calculateSignature($employeeId, $profileId, $storedEmail, $newPassword);
                Db::getInstance()->update(
                    bqSQL(static::$definition['table']),
                    [
                        'passwd' => pSQL($newPassword),
                        'signature' => pSQL($newSignature),
                    ],
                    'id_employee = '.(int) $result['id_employee']
                );
                $result['passwd'] = $newPassword;
                $result['signature'] = $newSignature;
            } else {
                return false;
            }
        }

        $this->id = $employeeId;
        $this->id_profile = $result['id_profile'];
        foreach ($result as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }

        return $this;
    }

    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function isLastAdmin()
    {
        return ($this->isSuperAdmin()
            && Employee::countProfile($this->id_profile, true) == 1
            && $this->active
        );
    }

    /**
     * Check if current employee is super administrator
     *
     * @return bool
     */
    public function isSuperAdmin()
    {
        return $this->id_profile == _PS_ADMIN_PROFILE_;
    }

    /**
     * @param int $idProfile
     * @param bool $activeOnly
     *
     * @return false|null|string
     *
     * @throws PrestaShopException
     */
    public static function countProfile($idProfile, $activeOnly = false)
    {
        $sql = new DbQuery();
        $sql->select('COUNT(*)');
        $sql->from(bqSQL(static::$definition['table']));
        $sql->where('`id_profile` = '.(int) $idProfile);
        if ($activeOnly) {
            $sql->where('`active` = 1');
        }

        return Db::readOnly()->getValue($sql);
    }

    /**
     * @param string $plainTextPassword
     *
     * @return bool
     */
    public function setWsPasswd($plainTextPassword)
    {
        if ($this->id != 0) {
            if ($this->passwd != $plainTextPassword) {
                $this->passwd = Tools::hash($plainTextPassword);
            }
        } else {
            $this->passwd = Tools::hash($plainTextPassword);
        }

        return true;
    }

    /**
     * Check employee informations saved into cookie and return employee validity
     *
     * @return bool employee validity
     *
     * @throws PrestaShopException
     */
    public function isLoggedBack()
    {
        if (!Cache::isStored('isLoggedBack'.$this->id)) {
            /* Employee is valid only if it can be load and if cookie password is the same as database one */
            $result = (
                $this->id && Validate::isUnsignedId($this->id) && Employee::checkPassword($this->id, Context::getContext()->cookie->passwd)
                && (!isset(Context::getContext()->cookie->remote_addr) || Context::getContext()->cookie->remote_addr == ip2long(Tools::getRemoteAddr()) || !Configuration::get('PS_COOKIE_CHECKIP'))
            );
            Cache::store('isLoggedBack'.$this->id, $result);

            return $result;
        }

        return Cache::retrieve('isLoggedBack'.$this->id);
    }

    /**
     * Check if employee password is the right one
     *
     * @param int $idEmployee
     * @param string $hashedPassword Password
     *
     * @return bool result
     *
     * @throws PrestaShopException
     */
    public static function checkPassword($idEmployee, $hashedPassword)
    {
        $sql = new DbQuery();
        $sql->select('`id_employee`');
        $sql->from('employee');
        $sql->where('`id_employee` = '.(int) $idEmployee);
        $sql->where('`active` = 1');
        $sql->where('`passwd` = \''.pSQL($hashedPassword).'\'');

        return (bool) Db::readOnly()->getValue($sql);
    }

    /**
     * Logout
     *
     * @throws PrestaShopException
     */
    public function logout()
    {
        if (isset(Context::getContext()->cookie)) {
            Context::getContext()->cookie->delete();
            Context::getContext()->cookie->write();
        }
        $this->id = null;
    }

    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function favoriteModulesList()
    {
        return Db::readOnly()->getArray(
            (new DbQuery())
                ->select('module')
                ->from('module_preference')
                ->where('`id_employee` = '.(int) $this->id)
                ->where('`favorite` = 1')
                ->where('`interest` = 1 OR `interest` IS NULL')
        );
    }

    /**
     * Check if the employee is associated to a specific shop
     *
     * @param int $idShop
     *
     * @return bool
     */
    public function hasAuthOnShop($idShop)
    {
        return $this->isSuperAdmin() || in_array($idShop, $this->associated_shops);
    }

    /**
     * Check if the employee is associated to a specific shop group
     *
     * @param int $idShopGroup
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hasAuthOnShopGroup($idShopGroup)
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        foreach ($this->associated_shops as $idShop) {
            if ($idShopGroup == Shop::getGroupFromShop($idShop, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get default id_shop with auth for current employee
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public function getDefaultShopID()
    {
        if ($this->isSuperAdmin() || in_array(Configuration::get('PS_SHOP_DEFAULT'), $this->associated_shops)) {
            return Configuration::get('PS_SHOP_DEFAULT');
        }

        return $this->associated_shops[0];
    }

    /**
     * @return string
     *
     * @throws PrestaShopException
     */
    public function getImage()
    {
        return Context::getContext()->link->getMediaLink(_PS_IMG_.'admin/employees_xl.png');
    }

    /**
     * @param string $type
     *
     * @return int
     *
     * @throws PrestaShopException
     * @deprecated since 1.4.0
     */
    public function getLastElementsForNotify($type)
    {
        Tools::displayAsDeprecated();
        return $this->getNotification()->getLastSeenId($type);
    }

    /**
     * Returns Notification object associated with this employee
     *
     * @return Notification
     * @throws PrestaShopException
     */
    public function getNotification()
    {
        if (is_null($this->notification)) {
            $this->notification = new Notification($this);

        }
        return $this->notification;
    }

    /**
     * Returns true, if this employee has access to $tabId with $permission level
     *
     * @param int|string $tab either tab ID or controller name
     * @param string $permission permission level
     * @return bool
     * @throws PrestaShopException
     */
    public function hasAccess($tab, $permission)
    {
        if (! Profile::isValidPermission($permission)) {
            throw new PrestaShopException("Invalid permission type");
        }

        $tabId = (int)$tab;
        if (! $tabId && is_string($tab)) {
            $tabId = (int)Tab::getIdFromClassName($tab);
        }
        $tabAccess = Profile::getProfileAccess($this->id_profile, $tabId);
        return (bool)$tabAccess[$permission];
    }

    /**
     * Calculates HMAC-SHA256 signature
     *
     * @param int $employeeId
     * @param int $profileId,
     * @param string $email
     * @param string $password
     *
     * @return string
     */
    protected static function calculateSignature(int $employeeId, int $profileId, string $email, string $password)
    {
        return Tools::signature($employeeId . $email . $profileId . $password);
    }

    /**
     * Updates HMAC-SHA256 signature stored inside database
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    protected function updateSignature()
    {
        $id = (int)$this->id;
        if ($id) {
            $signature = static::calculateSignature($id, (int)$this->id_profile, $this->email, $this->passwd);
            if ($signature !== $this->signature) {
                return Db::getInstance()->update(
                    bqSQL(static::$definition['table']),
                    [
                        'signature' => pSQL($signature)
                    ],
                    'id_employee = ' . (int)$id
                );
            }
            return true;
        }
        return false;
    }

    /**
     * @param Db $conn
     *
     * @return void
     * @throws PrestaShopException
     */
    public static function initializationCallback(Db $conn)
    {
        // if signature is missing/empty, calculate and save it
        $employees = new PrestaShopCollection('Employee');
        $employees->sqlWhere('COALESCE(`signature`, "") = ""');
        /** @var Employee $employee */
        foreach ($employees as $employee) {
            $employee->updateSignature();
        }
    }

}
