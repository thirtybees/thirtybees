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
 * Step 4 : configure the shop and admin access
 */
class InstallControllerHttpConfigure extends InstallControllerHttp
{
    public $listCountries = [];

    /** @var InstallSession $session */
    public $session;

    public $cacheTimezones;

    /** @var array $listActivities */
    public $listActivities;

    public $installType;

    /**
     * @see InstallAbstractModel::processNextStep()
     */
    public function processNextStep()
    {
        if (Tools::isSubmit('shopName')) {
            // Save shop configuration
            $this->session->shopName = trim(Tools::getValue('shopName'));
            $this->session->shopActivity = Tools::getValue('shopActivity');
            $this->session->installType = Tools::getValue('dbMode');
            $this->session->shopCountry = Tools::getValue('shopCountry');
            $this->session->shopTimezone = Tools::getValue('shopTimezone');

            // Save admin configuration
            $this->session->adminFirstname = trim(Tools::getValue('adminFirstname'));
            $this->session->adminLastname = trim(Tools::getValue('adminLastname'));
            $this->session->adminEmail = trim(Tools::getValue('adminEmail'));
            $this->session->sendInformations = Tools::getValue('sendInformations');
            $guzzle = new GuzzleHttp\Client([
                'base_uri'    => 'https://api.thirtybees.com',
                'timeout'     => 5,
                'http_errors' => false,
                'verify'      => __DIR__.'/../../../tools/cacert.pem',
            ]);

            try {
                $guzzle->post(
                    '/newsletter/',
                    [
                        'json' =>
                            [
                                'email'    => $this->session->adminEmail,
                                'fname'    => $this->session->adminFirstname,
                                'lname'    => $this->session->adminLastname,
                                'activity' => (string) $this->session->shopActivity,
                                'country'  => $this->session->shopCountry,
                                'language' => $this->language->getLanguageIso(),
                            ],
                    ]
                );
            } catch (Exception $e) {
                // Don't care
            }

            // If password fields are empty, but are already stored in session, do not fill them again
            if (!$this->session->adminPassword || trim(Tools::getValue('adminPassword'))) {
                $this->session->adminPassword = trim(Tools::getValue('adminPassword'));
            }

            if (!$this->session->adminPasswordConfirm || trim(Tools::getValue('adminPasswordConfirm'))) {
                $this->session->adminPasswordConfirm = trim(Tools::getValue('adminPasswordConfirm'));
            }
        }
    }

    /**
     * @see InstallAbstractModel::validate()
     */
    public function validate()
    {
        // List of required fields
        $requiredFields = ['shopName', 'shopCountry', 'shopTimezone', 'adminFirstname', 'adminLastname', 'adminEmail', 'adminPassword'];
        foreach ($requiredFields as $field) {
            if (!$this->session->$field) {
                $this->errors[$field] = $this->l('Field required');
            }
        }

        // Check shop name
        if ($this->session->shopName && !Validate::isGenericName($this->session->shopName)) {
            $this->errors['shopName'] = $this->l('Invalid shop name');
        } elseif (strlen($this->session->shopName) > 64) {
            $this->errors['shopName'] = $this->l('The field %s is limited to %d characters', $this->l('shop name'), 64);
        }

        // Check admin name
        if ($this->session->adminFirstname && !Validate::isName($this->session->adminFirstname)) {
            $this->errors['adminFirstname'] = $this->l('Your firstname contains some invalid characters');
        } elseif (strlen($this->session->adminFirstname) > 32) {
            $this->errors['adminFirstname'] = $this->l('The field %s is limited to %d characters', $this->l('firstname'), 32);
        }

        if ($this->session->adminLastname && !Validate::isName($this->session->adminLastname)) {
            $this->errors['adminLastname'] = $this->l('Your lastname contains some invalid characters');
        } elseif (strlen($this->session->adminLastname) > 32) {
            $this->errors['adminLastname'] = $this->l('The field %s is limited to %d characters', $this->l('lastname'), 32);
        }

        // Check passwords
        if ($this->session->adminPassword) {
            if (!Validate::isPasswdAdmin($this->session->adminPassword)) {
                $this->errors['adminPassword'] = $this->l('The password is incorrect (alphanumeric string with at least 8 characters)');
            } elseif ($this->session->adminPassword != $this->session->adminPasswordConfirm) {
                $this->errors['adminPassword'] = $this->l('Password and its confirmation are different');
            }
        }

        // Check email
        if ($this->session->adminEmail && !Validate::isEmail($this->session->adminEmail)) {
            $this->errors['adminEmail'] = $this->l('This e-mail address is invalid');
        }

        return count($this->errors) ? false : true;
    }

    public function process()
    {
        if (Tools::getValue('timezoneByIso')) {
            $this->processTimezoneByIso();
        }
    }

    /**
     * Obtain the timezone associated to an iso
     */
    public function processTimezoneByIso()
    {
        $timezone = $this->getTimezoneByIso(Tools::getValue('iso'));
        $this->ajaxJsonAnswer(($timezone) ? true : false, $timezone);
    }

    /**
     * Get a timezone associated to an iso
     *
     * @param string $iso
     *
     * @return string
     */
    public function getTimezoneByIso($iso)
    {
        if (!file_exists(_PS_INSTALL_DATA_PATH_.'iso_to_timezone.xml')) {
            return '';
        }

        $xml = @simplexml_load_file(_PS_INSTALL_DATA_PATH_.'iso_to_timezone.xml');
        $timezones = [];
        if ($xml) {
            foreach ($xml->relation as $relation) {
                $timezones[(string) $relation['iso']] = (string) $relation['zone'];
            }
        }

        return isset($timezones[$iso]) ? $timezones[$iso] : '';
    }

    /**
     * Get list of timezones
     *
     * @return array
     */
    public function getTimezones()
    {
        if (!is_null($this->cacheTimezones)) {
            return [];
        }

        if (!file_exists(_PS_INSTALL_DATA_PATH_.'xml/timezone.xml')) {
            return [];
        }

        $xml = @simplexml_load_file(_PS_INSTALL_DATA_PATH_.'xml/timezone.xml');
        $timezones = [];
        if ($xml) {
            foreach ($xml->entities->timezone as $timezone) {
                $timezones[] = (string) $timezone['name'];
            }
        }

        return $timezones;
    }

    /**
     * @see InstallAbstractModel::display()
     */
    public function display()
    {
        // List of activities
        $listActivities = [
            1  => $this->l('Lingerie and Adult'),
            2  => $this->l('Animals and Pets'),
            3  => $this->l('Art and Culture'),
            4  => $this->l('Babies'),
            5  => $this->l('Beauty and Personal Care'),
            6  => $this->l('Cars'),
            7  => $this->l('Computer Hardware and Software'),
            8  => $this->l('Download'),
            9  => $this->l('Fashion and accessories'),
            10 => $this->l('Flowers, Gifts and Crafts'),
            11 => $this->l('Food and beverage'),
            12 => $this->l('HiFi, Photo and Video'),
            13 => $this->l('Home and Garden'),
            14 => $this->l('Home Appliances'),
            15 => $this->l('Jewelry'),
            16 => $this->l('Mobile and Telecom'),
            17 => $this->l('Services'),
            18 => $this->l('Shoes and accessories'),
            19 => $this->l('Sports and Entertainment'),
            20 => $this->l('Travel'),
        ];

        asort($listActivities);
        $this->listActivities = $listActivities;

        // Countries list
        $this->listCountries = [];
        $countries = $this->language->getCountries();
        $topCountries = [
            'fr', 'es', 'us',
            'gb', 'it', 'de',
            'nl', 'pl', 'id',
            'be', 'br', 'se',
            'ca', 'ru', 'cn',
        ];

        foreach ($topCountries as $iso) {
            $this->listCountries[] = ['iso' => $iso, 'name' => $countries[$iso]];
        }
        $this->listCountries[] = ['iso' => 0, 'name' => '-----------------'];

        foreach ($countries as $iso => $lang) {
            if (!in_array($iso, $topCountries)) {
                $this->listCountries[] = ['iso' => $iso, 'name' => $lang];
            }
        }

        // Try to detect default country
        if (!$this->session->shopCountry) {
            $detectLanguage = $this->language->detectLanguage();
            if (isset($detectLanguage['primarytag'])) {
                $this->session->shopCountry = strtolower(isset($detectLanguage['subtag']) ? $detectLanguage['subtag'] : $detectLanguage['primarytag']);
                $this->session->shopTimezone = $this->getTimezoneByIso($this->session->shopCountry);
            }
        }

        // Install type
        $this->installType = ($this->session->installType) ? $this->session->installType : 'full';

        $this->displayTemplate('configure');
    }

    /**
     * Helper to display error for a field
     *
     * @param string $field
     *
     * @return string|null
     */
    public function displayError($field)
    {
        if (!isset($this->errors[$field])) {
            return null;
        }

        return '<span class="result aligned errorTxt">'.$this->errors[$field].'</span>';
    }
}
