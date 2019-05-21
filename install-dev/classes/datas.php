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
class Datas
{
    protected static $availableArgs = [
        'step'              => [
            'name'     => 'step',
            'default'  => 'all',
            'validate' => 'isGenericName',
            'help'     => 'all / database,fixtures,theme,modules',
        ],
        'language'          => [
            'default'  => 'en',
            'validate' => 'isLanguageIsoCode',
            'alias'    => 'l',
            'help'     => 'language iso code',
        ],
        'allLanguages'     => [
            'name'     => 'all_languages',
            'default'  => '0',
            'validate' => 'isInt',
            'alias'    => 'l',
            'help'     => 'install all available languages',
        ],
        'timezone'          => [
            'default' => 'Europe/Paris',
            'alias'   => 't',
        ],
        'baseUri'          => [
            'name'     => 'base_uri',
            'validate' => 'isUrl',
            'default'  => '/',
        ],
        'httpHost'         => [
            'name'     => 'domain',
            'validate' => 'isGenericName',
            'default'  => 'localhost',
        ],
        'databaseServer'   => [
            'name'     => 'db_server',
            'default'  => 'localhost',
            'validate' => 'isGenericName',
            'alias'    => 'h',
        ],
        'databaseLogin'    => [
            'name'     => 'db_user',
            'alias'    => 'u',
            'default'  => 'root',
            'validate' => 'isGenericName',
        ],
        'databasePassword' => [
            'name'    => 'db_password',
            'alias'   => 'p',
            'default' => '',
        ],
        'databaseName'     => [
            'name'     => 'db_name',
            'alias'    => 'd',
            'default'  => 'thirtybees',
            'validate' => 'isGenericName',
        ],
        'databaseClear'    => [
            'name'     => 'db_clear',
            'default'  => '1',
            'validate' => 'isInt',
            'help'     => 'Drop existing tables',
        ],
        'databaseCreate'   => [
            'name'     => 'db_create',
            'default'  => '0',
            'validate' => 'isInt',
            'help'     => 'Create the database if not exist',
        ],
        'databasePrefix'   => [
            'name'     => 'prefix',
            'default'  => 'ps_',
            'validate' => 'isGenericName',
        ],
        'databaseEngine'   => [
            'name'     => 'engine',
            'validate' => 'isMySQLEngine',
            'default'  => 'InnoDB',
            'help'     => 'InnoDB/MyISAM',
        ],
        'shopName'         => [
            'name'     => 'name',
            'validate' => 'isGenericName',
            'default'  => 'thirty bees',
        ],
        'shopActivity'     => [
            'name'     => 'activity',
            'default'  => 0,
            'validate' => 'isInt',
        ],
        'shopCountry'      => [
            'name'     => 'country',
            'validate' => 'isLanguageIsoCode',
            'default'  => 'fr',
        ],
        'adminFirstname'   => [
            'name'     => 'firstname',
            'validate' => 'isName',
            'default'  => 'John',
        ],
        'adminLastname'    => [
            'name'     => 'lastname',
            'validate' => 'isName',
            'default'  => 'Doe',
        ],
        'adminPassword'    => [
            'name'     => 'password',
            'validate' => 'isPasswd',
            'default'  => '0123456789',
        ],
        'adminEmail'       => [
            'name'     => 'email',
            'validate' => 'isEmail',
            'default'  => 'pub@thirtybees.com',
        ],
        'showLicense'      => [
            'name'    => 'license',
            'default' => 0,
            'help'    => 'show thirty bees license',
        ],
        'sendEmail'        => [
            'name'    => 'send_email',
            'default' => 1,
            'help'    => 'send an email to the administrator after installation',
        ],
    ];
    private static $instance = null;
    protected $datas = [];

//    public $showLicense;
//    public $httpHost;
//    public $timezone;
//    public $language;
//
//    public $databaseCreate;
//    public $modelDatabase;
//    public $newsletter;
//    public $adminEmail;
//    public $lang;
//    public $sendEmail;
//    public $databaseServer;
//    public $databaseLogin;
//    public $databasePassword;
//    public $databaseName;
//    public $databasePrefix;
//    public $databaseEngine;
//    public $databaseClear;
//    public $shopName;
//    public $baseUri;
//    public $shopCountry;
//    public $allLanguages;
//
//    public $step;
//
//    public $xmlLoaderIds;
//
//    public $shopActivity;
//    public $adminFirstname;
//    public $adminLastname;
//    public $adminPassword;
//
//    public $smtpServer;
//    public $smtpLogin;
//    public $smtpPassword;
//    public $smtpPort;
//    public $smtpEncryption;


    /**
     * @return Datas
     */
    public static function getInstance()
    {
        if (Datas::$instance === null) {
            Datas::$instance = new Datas();
        }

        return Datas::$instance;
    }

    public function __get($key)
    {
        if (isset($this->datas[$key])) {
            return $this->datas[$key];
        }
        return false;
    }
    public function __set($key, $value)
    {
        $this->datas[$key] = $value;
    }

    public function getAndCheckArgs($argv)
    {
        if (!$argv) {
            return false;
        }

        $argsOk = [];
        foreach ($argv as $arg) {
            if (!preg_match('/^--([^=\'"><|`]+)(?:=([^=><|`]+)|(?!license))/i', trim($arg), $res)) {
                continue;
            }

            if ($res[1] == 'license' && !isset($res[2])) {
                $res[2] = 1;
            } elseif (!isset($res[2])) {
                continue;
            }

            $argsOk[$res[1]] = $res[2];
        }

        $errors = [];
        foreach (Datas::getArgs() as $key => $row) {
            if (isset($row['name'])) {
                $name = $row['name'];
            } else {
                $name = $key;
            }
            if (!isset($argsOk[$name])) {
                if (!isset($row['default'])) {
                    $errors[] = 'Field '.$row['name'].' is empty';
                } else {
                    $this->$key = $row['default'];
                }
            } elseif (isset($row['validate']) && !call_user_func(['Validate', $row['validate']], $argsOk[$name])) {
                $errors[] = 'Field '.$key.' is not valid';
            } else {
                $this->$key = $argsOk[$name];
            }
        }

        return count($errors) ? $errors : true;
    }

    /**
     * @return array
     */
    public static function getArgs()
    {
        return Datas::$availableArgs;
    }
}
