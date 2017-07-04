<?php
/**
 * 2007-2016 PrestaShop
 *
 * Thirty Bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017 Thirty Bees
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
 * @author    Thirty Bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017 Thirty Bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class EmployeeCore
 *
 * @since 1.0.0
 */
class EmployeeCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    /** @var string Determine employee profile */
    public $id_profile;
    /** @var string employee language */
    public $id_lang;
    /** @var string Lastname */
    public $lastname;
    /** @var string Firstname */
    public $firstname;
    /** @var string e-mail */
    public $email;
    /** @var string Password */
    public $passwd;
    /** @var datetime Password */
    public $last_passwd_gen;
    public $stats_date_from;
    public $stats_date_to;
    public $stats_compare_from;
    public $stats_compare_to;
    public $stats_compare_option = 1;
    public $preselect_date_range;
    /** @var string Display back office background in the specified color */
    public $bo_color;
    public $default_tab;
    /** @var string employee's chosen theme */
    public $bo_theme;
    /** @var string employee's chosen css file */
    public $bo_css = 'admin-theme.css';
    /** @var int employee desired screen width */
    public $bo_width;

    /* Deprecated */
    /** @var bool, false */
    public $bo_menu = 1;
    public $bo_show_screencast = false;
    /** @var bool Status */
    public $active = 1;
    /** @var bool Optin status */
    public $optin = 1;

    /* employee notifications */
    public $remote_addr;
    public $id_last_order;
    public $id_last_customer_message;
    public $id_last_customer;
    protected $associated_shops = [];
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'employee',
        'primary' => 'id_employee',
        'fields'  => [
            'lastname'                 => ['type' => self::TYPE_STRING, 'validate' => 'isName',        'required' => true, 'size' => 32 ],
            'firstname'                => ['type' => self::TYPE_STRING, 'validate' => 'isName',        'required' => true, 'size' => 32 ],
            'email'                    => ['type' => self::TYPE_STRING, 'validate' => 'isEmail',       'required' => true, 'size' => 128],
            'id_lang'                  => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedInt', 'required' => true               ],
            'passwd'                   => ['type' => self::TYPE_STRING, 'validate' => 'isPasswdAdmin', 'required' => true, 'size' => 60 ],
            'last_passwd_gen'          => ['type' => self::TYPE_STRING                                                                  ],
            'active'                   => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'                                          ],
            'optin'                    => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'                                          ],
            'id_profile'               => ['type' => self::TYPE_INT,    'validate' => 'isInt',         'required' => true               ],
            'bo_color'                 => ['type' => self::TYPE_STRING, 'validate' => 'isColor',                           'size' => 32 ],
            'default_tab'              => ['type' => self::TYPE_INT,    'validate' => 'isInt'                                           ],
            'bo_theme'                 => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName',                     'size' => 32 ],
            'bo_css'                   => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName',                     'size' => 64 ],
            'bo_width'                 => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedInt'                                   ],
            'bo_menu'                  => ['type' => self::TYPE_BOOL,   'validate' => 'isBool'                                          ],
            'stats_date_from'          => ['type' => self::TYPE_DATE,   'validate' => 'isDate'                                          ],
            'stats_date_to'            => ['type' => self::TYPE_DATE,   'validate' => 'isDate'                                          ],
            'stats_compare_from'       => ['type' => self::TYPE_DATE,   'validate' => 'isDate'                                          ],
            'stats_compare_to'         => ['type' => self::TYPE_DATE,   'validate' => 'isDate'                                          ],
            'stats_compare_option'     => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedInt'                                   ],
            'preselect_date_range'     => ['type' => self::TYPE_STRING,                                                    'size' => 32 ],
            'id_last_order'            => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedInt'                                   ],
            'id_last_customer_message' => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedInt'                                   ],
            'id_last_customer'         => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedInt'                                   ],
        ],
    ];

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
     * @since   1.0.0
     * @version 1.0.0 Initial version
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
     * @return array|false Employees or false
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
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

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }

    /**
     * @param $email
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function employeeExists($email)
    {
        if (!Validate::isEmail($email)) {
            die(Tools::displayError());
        }

        return (bool) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`id_employee`')
                ->from('employee')
                ->where('`email` = \''.pSQL($email).'\'')
        );
    }

    /**
     * @param      $idProfile
     * @param bool $activeOnly
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
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

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }

    /**
     * @param int $idEmployee
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function setLastConnectionDate($idEmployee)
    {
        return Db::getInstance()->update(
            bqSQL(static::$definition['table']),
            [
                'last_connection_date' => ['type' => 'sql', 'value' => 'CURRENT_DATE()'],
            ],
            '`id_employee` = '.(int) $idEmployee.' AND `last_connection_date` < CURRENT_DATE()'
        );
    }

    /**
     * @see ObjectModel::getFields()
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function add($autoDate = true, $nullValues = true)
    {
        $this->last_passwd_gen = date('Y-m-d H:i:s', strtotime('-'.Configuration::get('PS_PASSWD_TIME_BACK').'minutes'));
        $this->saveOptin();
        $this->updateTextDirection();

        return parent::add($autoDate, $nullValues);
    }

    /**
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function saveOptin()
    {
        if ($this->optin && !defined('TB_INSTALLATION_IN_PROGRESS')) {
            $language = new Language($this->id_lang);
            $guzzle = new \GuzzleHttp\Client([
                'http_errors' => false,
                'verify' => _PS_TOOL_DIR_.'cacert.pem',
                'timeout' => 5,
            ]);
            try {
                //FIXME: insert mailchimp hook
            } catch (Exception $e) {
                // Don't care
            }
        }
    }

    /**
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function updateTextDirection()
    {
        if (!defined('_PS_ADMIN_DIR_')) {
            return;
        }

        $path = _PS_ADMIN_DIR_.DIRECTORY_SEPARATOR.'themes'.DIRECTORY_SEPARATOR.$this->bo_theme.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR;
        $language = new Language($this->id_lang);

        if ($language->is_rtl && !strpos($this->bo_css, '_rtl')) {
            $boCss = preg_replace('/^(.*)\.css$/', '$1_rtl.css', $this->bo_css);

            if (file_exists($path.$boCss)) {
                $this->bo_css = $boCss;
            }
        } elseif (!$language->is_rtl && strpos($this->bo_css, '_rtl')) {
            $boCss = preg_replace('/^(.*)_rtl\.css$/', '$1.css', $this->bo_css);

            if (file_exists($path.$boCss)) {
                $this->bo_css = $boCss;
            }
        }
    }

    /**
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function update($nullValues = false)
    {
        if (empty($this->stats_date_from) || $this->stats_date_from == '0000-00-00') {
            $this->stats_date_from = date('Y-m-d');
        }

        if (empty($this->stats_date_to) || $this->stats_date_to == '0000-00-00') {
            $this->stats_date_to = date('Y-m-d');
        }

        $currentEmployee = new Employee((int) $this->id);

        if ($currentEmployee->optin != $this->optin) {
            $this->saveOptin();
        }

        $this->updateTextDirection();

        return parent::update($nullValues);
    }

    /**
     * Return employee instance from its e-mail (optionnaly check password)
     *
     * @param string $email             e-mail
     * @param string $plainTextPassword Password is also checked if specified
     * @param bool   $activeOnly        Filter employee by active status
     *
     * @return Employee|bool Employee instance
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getByEmail($email, $plainTextPassword = null, $activeOnly = true)
    {
        if (!Validate::isEmail($email) || ($plainTextPassword != null && !Validate::isPasswd($plainTextPassword))) {
            die(Tools::displayError());
        }

        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('employee');
        $sql->where('`email` = \''.pSQL($email).'\'');
        if ($activeOnly) {
            $sql->where('`active` = 1');
        }

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);
        if ($plainTextPassword && !password_verify($plainTextPassword, $result['passwd'])) {
            $sql = new DbQuery();
            $sql->select('*');
            $sql->from('employee');
            $sql->where('`email` = \''.pSQL($email).'\'');
            $sql->where('`passwd` = \''.md5(_COOKIE_KEY_.$plainTextPassword).'\'');
            if ($activeOnly) {
                $sql->where('`active` = 1');
            }

            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);
            if ($result) {
                $newHash = Tools::hash($plainTextPassword);
                Db::getInstance()->update(
                    bqSQL(static::$definition['table']),
                    [
                        'passwd' => pSQL($newHash),
                    ],
                    'id_employee = '.(int) $result['id_employee']
                );
                $result['passwd'] = $newHash;
            } else {
                return false;
            }
        }

        if (!$result) {
            return false;
        }

        $this->id = $result['id_employee'];
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
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
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function isSuperAdmin()
    {
        return $this->id_profile == _PS_ADMIN_PROFILE_;
    }

    /**
     * @param      $idProfile
     * @param bool $activeOnly
     *
     * @return false|null|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
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

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }

    /**
     * @param string $plainTextPassword
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
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
     * @param int    $idEmployee
     * @param string $hashedPassword Password
     *
     * @return bool result
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function checkPassword($idEmployee, $hashedPassword)
    {
        if (!Validate::isUnsignedId($idEmployee) || !Validate::isPasswd($hashedPassword, 8)) {
            die(Tools::displayError());
        }

        $sql = new DbQuery();
        $sql->select('`id_employee`');
        $sql->from('employee');
        $sql->where('`id_employee` = '.(int) $idEmployee);
        $sql->where('`active` = 1');
        $sql->where('`passwd` = \''.pSQL($hashedPassword).'\'');

        return (bool) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }

    /**
     * Logout
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function logout()
    {
        if (isset(Context::getContext()->cookie)) {
            Context::getContext()->cookie->logout();
            Context::getContext()->cookie->write();
        }
        $this->id = null;
    }

    /**
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function favoriteModulesList()
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
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
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getImage()
    {
        return 'https://www.gravatar.com/avatar/'.md5(Tools::strtolower(trim($this->email))).'?s=200&d=mm';
    }

    /**
     * @param string $element
     *
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getLastElementsForNotify($element)
    {
        $element = bqSQL($element);
        $max = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('MAX(`id_'.bqSQL($element).'`) as `id_'.bqSQL($element).'`')
                ->from(bqSQL($element).($element == 'order' ? 's' : ''))
        );

        // if no rows in table, set max to 0
        if ((int) $max < 1) {
            $max = 0;
        }

        return (int) $max;
    }
}
