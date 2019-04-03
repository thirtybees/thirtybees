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
 * Class GuestCore
 *
 * @since 1.0.0
 */
class GuestCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    public $id_operating_system;
    public $id_web_browser;
    public $id_customer;
    public $javascript;
    public $screen_resolution_x;
    public $screen_resolution_y;
    public $screen_color;
    public $sun_java;
    public $adobe_flash;
    public $adobe_director;
    public $apple_quicktime;
    public $real_player;
    public $windows_media;
    public $accept_language;
    public $mobile_theme;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'guest',
        'primary' => 'id_guest',
        'fields'  => [
            'id_operating_system' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_web_browser'      => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_customer'         => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'javascript'          => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '0', 'dbNullable' => true],
            'screen_resolution_x' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'dbType' => 'smallint(5) unsigned'],
            'screen_resolution_y' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'dbType' => 'smallint(5) unsigned'],
            'screen_color'        => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'dbType' => 'tinyint(3) unsigned'],
            'sun_java'            => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)'],
            'adobe_flash'         => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)'],
            'adobe_director'      => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)'],
            'apple_quicktime'     => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)'],
            'real_player'         => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)'],
            'windows_media'       => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)'],
            'accept_language'     => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 8],
            'mobile_theme'        => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '0'],
        ],
        'keys' => [
            'guest' => [
                'id_customer'         => ['type' => ObjectModel::KEY, 'columns' => ['id_customer']],
                'id_operating_system' => ['type' => ObjectModel::KEY, 'columns' => ['id_operating_system']],
                'id_web_browser'      => ['type' => ObjectModel::KEY, 'columns' => ['id_web_browser']],
            ],
        ],
    ];

    protected $webserviceParameters = [
        'fields' => [
            'id_customer' => ['xlink_resource' => 'customers'],
        ],
    ];

    /**
     * @param Cookie $cookie
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function setNewGuest($cookie)
    {
        $guest = new Guest(isset($cookie->id_customer) ? Guest::getFromCustomer((int) ($cookie->id_customer)) : null);
        $guest->userAgent();
        $guest->save();
        $cookie->id_guest = (int) ($guest->id);
    }

    /**
     * @param int $idCustomer
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getFromCustomer($idCustomer)
    {
        if (!Validate::isUnsignedId($idCustomer)) {
            return false;
        }
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('`id_guest`')
                ->from('guest')
                ->where('`id_customer` = '.(int) $idCustomer)
        );

        return $result['id_guest'];
    }

    /**
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function userAgent()
    {
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $acceptLanguage = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';
        $this->accept_language = $this->getLanguage($acceptLanguage);
        $this->id_operating_system = $this->getOs($userAgent);
        $this->id_web_browser = $this->getBrowser($userAgent);
        $this->mobile_theme = Context::getContext()->getMobileDevice();
    }

    /**
     * @param $acceptLanguage
     *
     * @return mixed|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function getLanguage($acceptLanguage)
    {
        // $langsArray is filled with all the languages accepted, ordered by priority
        $langsArray = [];
        preg_match_all('/([a-z]{2}(-[a-z]{2})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/', $acceptLanguage, $array);
        if (count($array[1])) {
            $langsArray = array_combine($array[1], $array[4]);
            foreach ($langsArray as $lang => $val) {
                if ($val === '') {
                    $langsArray[$lang] = 1;
                }
            }
            arsort($langsArray, SORT_NUMERIC);
        }

        // Only the first language is returned
        return (count($langsArray) ? key($langsArray) : '');
    }

    /**
     * @param string $userAgent
     *
     * @return null
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function getOs($userAgent)
    {
        $osArray = [
            'Windows 8'     => 'Windows NT 6.2',
            'Windows 7'     => 'Windows NT 6.1',
            'Windows Vista' => 'Windows NT 6.0',
            'Windows XP'    => 'Windows NT 5',
            'MacOsX'        => 'Mac OS X',
            'Android'       => 'Android',
            'Linux'         => 'X11',
        ];

        foreach ($osArray as $k => $value) {
            if (strstr($userAgent, $value)) {
                $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
                    (new DbQuery())
                        ->select('`id_operating_system`')
                        ->from('operating_system', 'os')
                        ->where('os.`name` = \''.pSQL($k).'\'')
                );

                return $result['id_operating_system'];
            }
        }

        return null;
    }

    /**
     * @param string $userAgent
     *
     * @return null
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function getBrowser($userAgent)
    {
        $browserArray = [
            'Chrome'      => 'Chrome/',
            'Safari'      => 'Safari',
            'Safari iPad' => 'iPad',
            'Firefox'     => 'Firefox/',
            'Opera'       => 'Opera',
            'IE 11'       => 'Trident',
            'IE 10'       => 'MSIE 10',
            'IE 9'        => 'MSIE 9',
            'IE 8'        => 'MSIE 8',
            'IE 7'        => 'MSIE 7',
            'IE 6'        => 'MSIE 6',
        ];
        foreach ($browserArray as $k => $value) {
            if (strstr($userAgent, $value)) {
                $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
                    (new DbQuery())
                        ->select('`id_web_browser`')
                        ->from('web_browser', 'wb')
                        ->where('wb.`name` = \''.pSQL($k).'\'')
                );

                return $result['id_web_browser'];
            }
        }

        return null;
    }

    /**
     * @param int $idGuest
     * @param int $idCustomer
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function mergeWithCustomer($idGuest, $idCustomer)
    {
        // Since the guests are merged, the guest id in the connections table must be changed too
        Db::getInstance()->update(
            'connections',
            [
                'id_guest' => (int) $idGuest,
            ],
            '`id_guest` = '.(int) $this->id
        );

        // The current guest is removed from the database
        $this->delete();

        // $this is still filled with values, so it's id is changed for the old guest
        $this->id = (int) ($idGuest);
        $this->id_customer = (int) ($idCustomer);

        // $this is now the old guest but filled with the most up to date values
        $this->update();
    }
}
