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
 * Class CurrencyModuleCore
 *
 * @since 1.0.0
 */
abstract class CurrencyRateModuleCore extends Module
{
    /**
     * @param string $baseCurrency Uppercase base currency code
     *                             Only codes that have been added to the
     *                             `supportedCurrencies` array will be called.
     *                             The module will have to accept all currencies
     *                             from that array as a base.
     *
     * @return false|array Associate array with all supported currency codes as key (uppercase) and the actual
     *                     amounts as values (floats - be as accurate as you like), e.g.:
     *                     ```php
     *                     [
     *                         'EUR' => 1.233434,
     *                         'USD' => 1.343,
     *                     ]
     *                     ```
     *                     Returns `false`  if there were problems with retrieving the exchange rates
     *
     * @since 1.0.0
     * @deprecated 1.0.1 Sorry, it doesn't work as it should :(
     *             Please avoid!
     */
    public function hookCurrencyRates($baseCurrency)
    {
        return false;
    }

    /**
     * @param array $params It contains the following values:
     *                      - `currencies`: `array` of `string`s
     *                        Uppercase currency codes
     *                        Only codes that have been added to the
     *                        `currencies` array should be filled.
     *                        The module will have to accept all the currencies it provides
     *                        as a base currency, too. So if it provides `EUR` and `USD`, it should be able to calculate
     *                        with both `EUR` or `USD` as a base currency and find the exchange rate for the other.
     *                      - `baseCurrency`: `string`
     *                        Uppercase base currency code
     *
     * @return false|array Associate array with all supported and requested currency codes as key (uppercase) and the actual
     *                     amounts as values (floats - be as accurate as you like), e.g.:
     *                     ```php
     *                     [
     *                         'EUR' => 1.233434,
     *                         'USD' => 1.343,
     *                     ]
     *                     ```
     *                     Sets a currency as `false` if there were problems with retrieving the exchange rates.
     *                     This will cause thirty bees to not further process the currency. As of 1.0.x thirty bees will not request
     *                     other modules to provide the missing rates. This might change in the future.
     *
     * @since 1.0.1 Introduced as a replacement for `hookCurrencyRates`. All action modules should be prefixed with `action`
     */
    abstract public function hookActionRetrieveCurrencyRates($params);

    /**
     * @param string $fromCurrency From currency code
     * @param string $toCurrency   To currency code
     *
     * @return false|float
     *
     * @since 1.0.0
     * @deprecated 1.0.1 Sorry, it doesn't work as it should :(
     *             Please avoid!
     */
    public function hookRate($fromCurrency, $toCurrency)
    {
        return false;
    }

    /**
     * @return array Supported currencies
     *               An array with uppercase currency codes (ISO 4217)
     *
     * @since 1.0.0
     */
    abstract public function getSupportedCurrencies();

    /**
     * Install this module and scan currencies
     *
     * @return bool Indicates whether the module was successfully installed
     *
     * @since 1.0.0
     */
    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        static::scanMissingCurrencyRateModules(false, $this->name);

        return true;
    }

    /**
     * Retrieve all currencies that have exchange rate modules available
     *
     * @param bool $registeredOnly Show currencies with registered services only
     * @param bool $codesOnly      Return codes only
     *
     * @return array|false Array with currency iso code as key and module instance as value
     *
     * @since 1.0.0
     */
    public static function getCurrencyRateInfo($registeredOnly = false, $codesOnly = false)
    {
        if ($registeredOnly) {
            $sql = new DbQuery();
            $sql->select('`id_currency`, `id_module`');
            $sql->from('currency_module');
        } else {
            $sql = new DbQuery();
            $sql->select('c.`id_currency`, cm.`id_module`');
            $sql->from('currency', 'c');
            $sql->leftJoin('currency_module', 'cm', 'cm.`id_currency` = c.`id_currency`');
            $sql->where('c.`deleted` = 0');
        }

        $results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        if (!$results) {
            return false;
        }

        $defaultCurrency = Currency::getDefaultCurrency();
        if (!$defaultCurrency) {
            return false;
        }

        $return = [];
        foreach ($results as $result) {
            /** @var Currency $currency */
            $currency = Currency::getCurrencyInstance($result['id_currency']);
            /** @var CurrencyRateModule $module */
            $module = Module::getInstanceById($result['id_module']);
            if (Validate::isLoadedObject($currency) && Validate::isLoadedObject($module)) {
                if ($codesOnly) {
                    $return[mb_strtoupper($currency->iso_code)] = null;

                } else {
                    $return[mb_strtoupper($currency->iso_code)] = $module;
                }
            } elseif (!$registeredOnly && Validate::isLoadedObject($currency)) {
                $return[mb_strtoupper($currency->iso_code)] = null;
            }
        }

        return $return;
    }

    /**
     * @param bool|string $baseCurrency
     *
     * @return false|array Result
     *
     * @since 1.0.0
     * @since 1.0.1 Extra module name
     */
    public static function scanMissingCurrencyRateModules($baseCurrency = false, $extraModule = null)
    {
        if (!$baseCurrency) {
            $defaultCurrency = Currency::getDefaultCurrency();
            if (!Validate::isLoadedObject($defaultCurrency)) {
                return false;
            }
            $baseCurrency = $defaultCurrency->iso_code;
        }

        if ($extraModule) {
            $extraModule = Module::getInstanceByName($extraModule);
        }

        $registeredModules = static::getCurrencyRateInfo();
        foreach ($registeredModules as $currencyCode => &$module) {
            if (!Validate::isLoadedObject($module)) {
                $idCurrency = Currency::getIdByIsoCode($currencyCode);
                $currency = Currency::getCurrencyInstance($idCurrency);
                if (!Validate::isLoadedObject($currency)) {
                    continue;
                }

                $availableModuleName = static::providesExchangeRate($currency->iso_code, $baseCurrency, true);
                if (!$availableModuleName && Validate::isLoadedObject($extraModule)) {
                    /** @var CurrencyRateModule $extraModule */
                    $providedCurrencies = $extraModule->getSupportedCurrencies();
                    if (in_array($baseCurrency, $providedCurrencies) && in_array($currencyCode, $providedCurrencies)) {
                        $availableModuleName = $extraModule->name;
                    }
                }

                if ($availableModuleName) {
                    $availableModule = Module::getInstanceByName($availableModuleName);
                    if (Validate::isLoadedObject($availableModule)) {
                        $module['id_module'] = $availableModule->id;
                        static::setModule($currency->id, $availableModule->id);
                    }
                }
            }
        }

        return $registeredModules;
    }

    /**
     * List all installed and active currency rate modules
     *
     * @return array Available modules
     *
     * @since 1.0.0
     */
    public static function getInstalledCurrencyRateModules()
    {
        $sql = new DbQuery();
        $sql->select('m.`id_module`, m.`name`');
        $sql->from('module', 'm');
        $sql->leftJoin('hook_module', 'hm', 'hm.`id_module` = m.`id_module` '.Shop::addSqlRestriction(false, 'hm'));
        $sql->leftJoin('hook', 'h', 'hm.`id_hook` = h.`id_hook`');
        $sql->innerJoin('module_shop', 'ms', 'm.`id_module` = ms.`id_module`');
        $sql->where('ms.`id_shop` = '.(int) Context::getContext()->shop->id);
        $sql->where('h.`name` = \'actionRetrieveCurrencyRates\'');

        return Db::getInstance()->executeS($sql);
    }

    /**
     * Same as `CurrencyRateModule::getInstalledCurrencyRateModules`
     * but also returns the list of supported currencies by every module
     *
     * @return array Available modules
     *
     * @since 1.0.0
     */
    public static function getCurrencyRateModules()
    {
        $modules = [];
        $installedModules = static::getInstalledCurrencyRateModules();
        foreach ($installedModules as $moduleInfo) {
            /** @var CurrencyRateModule $module */
            $module = Module::getInstanceById($moduleInfo['id_module']);
            if (Validate::isLoadedObject($module)) {
                $modules[$module->name] = $module->getSupportedCurrencies();
            }
        }

        return $modules;
    }

    /**
     * Get providing modules
     *
     * @param string      $to      To currency code
     * @param null|string $from    From given base currency code
     * @param bool        $justOne Search for just one module
     *
     * @return array|string
     *
     * @since 1.0.0
     */
    public static function providesExchangeRate($to, $from = null, $justOne = false)
    {
        if (!$from) {
            $fromCurrency = Currency::getDefaultCurrency();
            $from = mb_strtoupper($fromCurrency->iso_code);
        }

        $modules = static::getCurrencyRateModules();
        if ($justOne) {
            $providingModules = '';
        } else {
            $providingModules = [];
        }
        foreach ($modules as $moduleName => $supportedCurrencies) {
            if (in_array(mb_strtoupper($to), $supportedCurrencies) && in_array($from, $supportedCurrencies)) {
                if ($justOne) {
                    return $moduleName;
                }
                $providingModules[] = $moduleName;
            }
        }

        return $providingModules;
    }

    /**
     * Get providing modules
     *
     * @param int    $idCurrency To currency code
     * @param string $selected   Selected module
     *
     * @return array|false
     *
     * @since 1.0.0
     */
    public static function getServices($idCurrency, $selected)
    {
        $currency = new Currency($idCurrency);
        $defaultCurrency = Currency::getDefaultCurrency();
        if (!Validate::isLoadedObject($defaultCurrency)) {
            return false;
        }

        if ($currency->iso_code == $defaultCurrency->iso_code) {
            return false;
        }

        $availableServices = static::providesExchangeRate($currency->iso_code, $defaultCurrency->iso_code, false);

        $serviceModules = [];
        foreach ($availableServices as $service) {
            $module = Module::getInstanceByName($service);
            if (!Validate::isLoadedObject($module)) {
                continue;
            }

            $serviceModules[] = [
                'id_module' => $module->id,
                'name' => $module->name,
                'display_name' => $module->displayName,
                'selected' => $module->name === $selected,
            ];
        }

        return $serviceModules;
    }

    /**
     * @param $idCurrency
     *
     * @return false|null|string
     *
     * @since 1.0.0
     */
    protected static function getModuleForCurrency($idCurrency)
    {
        $sql = new DbQuery();
        $sql->select('`id_module`');
        $sql->from('currency_module');
        $sql->where('`id_currency` = '.(int) $idCurrency);

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }

    /**
     * Set module
     *
     * @param int $idCurrency
     * @param int $idModule
     *
     * @since 1.0.0
     */
    public static function setModule($idCurrency, $idModule)
    {
        Db::getInstance()->delete(
            'currency_module',
            '`id_currency` = '.(int) $idCurrency,
            1,
            false
        );
        Db::getInstance()->insert(
            'currency_module',
            [
                'id_currency' => (int) $idCurrency,
                'id_module'   => (int) $idModule,
            ]
        );
    }
}
