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
    /**
     * @var array
     */
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
            'default'  => 'tb_',
            'validate' => 'isGenericName',
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
    ];

    /**
     * @var string
     */
    public $step;

    /**
     * @var string
     */
    public $language;

    /**
     * @var bool
     */
    public $allLanguages;

    /**
     * @var string
     */
    public $timezone;

    /**
     * @var string
     */
    public $baseUri;

    /**
     * @var string
     */
    public $httpHost;

    /**
     * @var string
     */
    public $databaseServer;

    /**
     * @var string
     */
    public $databaseLogin;

    /**
     * @var string
     */
    public $databasePassword;

    /**
     * @var string
     */
    public $databaseName;

    /**
     * @var bool
     */
    public $databaseClear;

    /**
     * @var bool
     */
    public $databaseCreate;

    /**
     * @var string
     */
    public $databasePrefix;

    /**
     * @var string
     */
    public $shopName;

    /**
     * @var  int
     */
    public $shopActivity;

    /**
     * @var string
     */
    public $shopCountry;

    /**
     * @var string
     */
    public $adminFirstname;

    /**
     * @var string
     */
    public $adminLastname;

    /**
     * @var string
     */
    public $adminPassword;

    /**
     * @var string
     */
    public $adminEmail;

    /**
     * @var bool
     */
    public $showLicense;

    /**
     * @param string[] $argv
     * @return string[]|bool
     */
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
        foreach (static::$availableArgs as $key => $row) {
            $name = $row['name'] ?? $key;
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
