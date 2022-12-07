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
 * Class InstallLanguage
 */
class InstallLanguage
{
    /**
     * @var string Current language folder
     */
    protected $path;

    /**
     * @var string Current language iso
     */
    protected $iso;

    /**
     * @var array Cache list of installer translations for this language
     */
    protected $data;

    /**
     * @var array Cache list of informations in language.xml file
     */
    protected $meta;

    /**
     * @var array Cache list of countries for this language
     */
    protected $countries;

    /**
     * InstallLanguage constructor.
     *
     * @param string $iso
     */
    public function __construct($iso)
    {
        $this->path = _PS_INSTALL_LANGS_PATH_.$iso.'/';
        $this->iso = $iso;
    }

    /**
     * Get iso for current language
     *
     * @return string
     */
    public function getIso()
    {
        return $this->iso;
    }

    /**
     * Get an information from language.xml file (E.g. $this->getMetaInformation('name'))
     *
     * @param string $key
     *
     * @return string
     */
    public function getMetaInformation($key)
    {
        if (!is_array($this->meta)) {
            $this->meta = [];
            $xml = @simplexml_load_file($this->path.'language.xml');
            if ($xml) {
                foreach ($xml->children() as $node) {
                    $this->meta[$node->getName()] = (string) $node;
                }
            }
        }

        return isset($this->meta[$key]) ? $this->meta[$key] : null;
    }

    /**
     * @param string $key
     * @param string $type
     *
     * @return string|null
     */
    public function getTranslation($key, $type = 'translations')
    {
        if (!is_array($this->data)) {
            $this->data = file_exists($this->path.'install.php') ? include($this->path.'install.php') : [];
        }

        return isset($this->data[$type][$key]) ? $this->data[$type][$key] : null;
    }

    /**
     * @return array
     */
    public function getCountries()
    {
        if (!is_array($this->countries)) {
            $this->countries = [];
            if (file_exists($this->path.'data/country.xml')) {
                if ($xml = @simplexml_load_file($this->path.'data/country.xml')) {
                    foreach ($xml->country as $country) {
                        $this->countries[strtolower((string) $country['id'])] = (string) $country->name;
                    }
                }
            }
        }

        return $this->countries;
    }
}
