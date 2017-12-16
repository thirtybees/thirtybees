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
 *  @author    thirty bees <contact@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017-2018 thirty bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class ConfigurationKPICore
 *
 * @since 1.0.0
 */
class ConfigurationKPICore extends Configuration
{
    // @codingStandardsIgnoreStart
    public static $definition_backup;
    // @codingStandardsIgnoreEnd

    /**
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function setKpiDefinition()
    {
        // @codingStandardsIgnoreStart
        ConfigurationKPI::$definition_backup = Configuration::$definition;
        // @codingStandardsIgnoreEnd
        Configuration::$definition['table'] = 'configuration_kpi';
        Configuration::$definition['primary'] = 'id_configuration_kpi';
    }

    /**
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function unsetKpiDefinition()
    {
        // @codingStandardsIgnoreStart
        Configuration::$definition = ConfigurationKPI::$definition_backup;
        // @codingStandardsIgnoreEnd
    }

    /**
     * @param string   $key
     * @param int|null $idShopGroup
     * @param int|null $idShop
     *
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getIdByName($key, $idShopGroup = null, $idShop = null)
    {
        ConfigurationKPI::setKpiDefinition();
        $r = parent::getIdByName($key, $idShopGroup, $idShop);
        ConfigurationKPI::unsetKpiDefinition();

        return $r;
    }

    /**
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function loadConfiguration()
    {
        ConfigurationKPI::setKpiDefinition();
        parent::loadConfiguration();
        ConfigurationKPI::unsetKpiDefinition();
    }

    /**
     * @param string   $key
     * @param int|null $idLang
     * @param int|null $idShopGroup
     * @param int|null $idShop
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function get($key, $idLang = null, $idShopGroup = null, $idShop = null)
    {
        ConfigurationKPI::setKpiDefinition();
        $r = parent::get($key, $idLang, $idShopGroup, $idShop);
        ConfigurationKPI::unsetKpiDefinition();

        return $r;
    }

    /**
     * @param string   $key
     * @param int|null $idLang
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getGlobalValue($key, $idLang = null)
    {
        ConfigurationKPI::setKpiDefinition();
        $r = parent::getGlobalValue($key, $idLang);
        ConfigurationKPI::unsetKpiDefinition();

        return $r;
    }

    /**
     * @param string   $key
     * @param int|null $idShopGroup
     * @param int|null $idShop
     *
     * @return array
     * @since   1.0.0
     *
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getInt($key, $idShopGroup = null, $idShop = null)
    {
        ConfigurationKPI::setKpiDefinition();
        $r = parent::getInt($key, $idShopGroup, $idShop);
        ConfigurationKPI::unsetKpiDefinition();

        return $r;
    }

    /**
     * @param array $keys
     * @param null  $idLang
     * @param null  $idShopGroup
     * @param null  $idShop
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getMultiple($keys, $idLang = null, $idShopGroup = null, $idShop = null)
    {
        ConfigurationKPI::setKpiDefinition();
        $r = parent::getMultiple($keys, $idLang, $idShopGroup, $idShop);
        ConfigurationKPI::unsetKpiDefinition();

        return $r;
    }

    /**
     * @param string $key
     * @param null   $idLang
     * @param null   $idShopGroup
     * @param null   $idShop
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function hasKey($key, $idLang = null, $idShopGroup = null, $idShop = null)
    {
        ConfigurationKPI::setKpiDefinition();
        $r = parent::hasKey($key, $idLang, $idShopGroup, $idShop);
        ConfigurationKPI::unsetKpiDefinition();

        return $r;
    }

    /**
     * @param string $key
     * @param mixed  $values
     * @param null   $idShopGroup
     * @param null   $idShop
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function set($key, $values, $idShopGroup = null, $idShop = null)
    {
        ConfigurationKPI::setKpiDefinition();
        parent::set($key, $values, $idShopGroup, $idShop);
        ConfigurationKPI::unsetKpiDefinition();
    }

    /**
     * @param string $key
     * @param mixed  $values
     * @param bool   $html
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function updateGlobalValue($key, $values, $html = false)
    {
        ConfigurationKPI::setKpiDefinition();
        $r = parent::updateGlobalValue($key, $values, $html);
        ConfigurationKPI::unsetKpiDefinition();

        return $r;
    }

    /**
     * @param string   $key
     * @param mixed    $values
     * @param bool     $html
     * @param int|null $idShopGroup
     * @param int|null $idShop
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function updateValue($key, $values, $html = false, $idShopGroup = null, $idShop = null)
    {
        ConfigurationKPI::setKpiDefinition();
        $r = parent::updateValue($key, $values, $html, $idShopGroup, $idShop);
        ConfigurationKPI::unsetKpiDefinition();

        return $r;
    }

    /**
     * @param string $key
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function deleteByName($key)
    {
        ConfigurationKPI::setKpiDefinition();
        $r = parent::deleteByName($key);
        ConfigurationKPI::unsetKpiDefinition();

        return $r;
    }

    /**
     * @param string $key
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function deleteFromContext($key)
    {
        ConfigurationKPI::setKpiDefinition();
        parent::deleteFromContext($key);
        ConfigurationKPI::unsetKpiDefinition();
    }

    /**
     * @param string $key
     * @param int    $idLang
     * @param int    $context
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function hasContext($key, $idLang, $context)
    {
        ConfigurationKPI::setKpiDefinition();
        $r = parent::hasContext($key, $idLang, $context);
        ConfigurationKPI::unsetKpiDefinition();

        return $r;
    }

    /**
     * @param string $key
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function isOverridenByCurrentContext($key)
    {
        ConfigurationKPI::setKpiDefinition();
        $r = parent::isOverridenByCurrentContext($key);
        ConfigurationKPI::unsetKpiDefinition();

        return $r;
    }

    /**
     * @param string $key
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isLangKey($key)
    {
        ConfigurationKPI::setKpiDefinition();
        $r = parent::isLangKey($key);
        ConfigurationKPI::unsetKpiDefinition();

        return $r;
    }

    /**
     * @param int $idShopGroup
     * @param int $idShop
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected static function sqlRestriction($idShopGroup, $idShop)
    {
        ConfigurationKPI::setKpiDefinition();
        $r = parent::sqlRestriction($idShopGroup, $idShop);
        ConfigurationKPI::unsetKpiDefinition();

        return $r;
    }
}
