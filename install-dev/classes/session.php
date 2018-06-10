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
 * Manage session for install script
 *
 * @since 1.0.0
 */
class InstallSession
{
    /** @var InstallSession $instance */
    protected static $instance;

    /** @var bool $cookieMode */
    protected static $cookieMode = false;

    /** @var bool|Cookie $cookie */
    protected static $cookie = false;

//    public $databaseServer;
//    public $databaseName;
//    public $databaseLogin;
//    public $databasePassword;
//    public $databasePrefix;
//    public $databaseClear;
//    public $rewriteEngine;
//
//    public $installType;
//
//    public $shopName;
//    public $shopActivity;
//    public $shopCountry;
//    public $shopTimezone;
//    public $adminFirstname;
//    public $adminLastname;
//    public $adminEmail;
//    public $sendInformations;
//
//    public $adminPassword;
//    public $adminPasswordConfirm;
//
//    public $useSmtp;
//    public $smtpEncryption;
//    public $smtpPort;
//
//    public $lang;
//    public $lastStep;
//
//    public $supportPhone;
//
//    public $licenseAgreement;
//    public $configurationAgreement;
//
//    public $processValidated;
//
//    public $xmlLoaderIds;
//    public $modules;

    /**
     * @return InstallSession
     *
     * @since 1.0.0
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * InstallSession constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        session_name('install_'.substr(md5($_SERVER['HTTP_HOST']), 0, 12));
        $sessionStarted = session_start();
        if (!($sessionStarted)
            || (!isset($_SESSION['session_mode']) && (isset($_GET['_']) || isset($_POST['submitNext']) || isset($_POST['submitPrevious']) || isset($_POST['language'])))
        ) {
            InstallSession::$cookieMode = true;
            InstallSession::$cookie = new Cookie('ps_install', null, time() + 7200, null, true);
        }
        if ($sessionStarted && !isset($_SESSION['session_mode'])) {
            $_SESSION['session_mode'] = 'session';
            session_write_close();
        }
    }

    /**
     * @since 1.0.0
     */
    public function clean()
    {
        if (InstallSession::$cookieMode) {
            InstallSession::$cookie->logout();
        } else {
            foreach ($_SESSION as $k => $v) {
                unset($_SESSION[$k]);
            }
        }
    }

    public function &__get($varname)
    {
        if (InstallSession::$cookieMode) {
            $ref = InstallSession::$cookie->{$varname};
            if (0 === strncmp($ref, 'json_array:', strlen('json_array:'))) {
                $ref = json_decode(substr($ref, strlen('json_array:')));
            }
        } else {
            if (isset($_SESSION[$varname])) {
                $ref = &$_SESSION[$varname];
            } else {
                $null = null;
                $ref = &$null;
            }
        }
        return $ref;
    }

    public function __set($varname, $value)
    {
        if (InstallSession::$cookieMode) {
            if ($varname == 'xml_loader_ids') {
                return;
            }
            if (is_array($value)) {
                $value = 'json_array:'.json_encode($value);
            }
            InstallSession::$cookie->{$varname} = $value;
        } else {
            $_SESSION[$varname] = $value;
        }
    }

    public function __isset($varname)
    {
        if (InstallSession::$cookieMode) {
            return isset(InstallSession::$cookie->{$varname});
        } else {
            return isset($_SESSION[$varname]);
        }
    }

    public function __unset($varname)
    {
        if (InstallSession::$cookieMode) {
            unset(InstallSession::$cookie->{$varname});
        } else {
            unset($_SESSION[$varname]);
        }
    }
}
