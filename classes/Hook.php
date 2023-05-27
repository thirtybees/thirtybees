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
 * Class HookCore
 */
class HookCore extends ObjectModel
{
    /**
     * @var array List of executed hooks on this page
     */
    public static $executed_hooks = [];

    /**
     * @var array
     */
    public static $native_module;

    /**
     * @var string Hook name identifier
     */
    public $name;

    /**
     * @var string Hook title (displayed in BO)
     */
    public $title;

    /**
     * @var string Hook description
     */
    public $description;

    /**
     * @var bool
     */
    public $position = false;

    /**
     * @var bool Is this hook usable with live edit ?
     */
    public $live_edit = false;

    /**
     * @var array Object model definition
     */
    public static $definition = [
        'table'   => 'hook',
        'primary' => 'id_hook',
        'fields'  => [
            'name'        => ['type' => self::TYPE_STRING, 'validate' => 'isHookName', 'required' => true, 'size' => 64, 'unique' => 'hook_name'],
            'title'       => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 64, 'dbNullable' => false],
            'description' => ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml', 'size' => ObjectModel::SIZE_TEXT],
            'position'    => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '1'],
            'live_edit'   => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '0'],
        ],
    ];

    /**
     * Return Hooks List
     *
     * @param bool $position
     *
     * @return array Hooks List
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getHooks($position = false)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            '
			SELECT * FROM `'._DB_PREFIX_.'hook` h
			'.($position ? 'WHERE h.`position` = 1' : '').'
			ORDER BY `name`'
        );
    }

    /**
     * Return hook ID from name
     *
     * @throws PrestaShopException
     */
    public static function getNameById($hookId)
    {
        $cacheId = 'hook_namebyid_'.$hookId;
        if (!Cache::isStored($cacheId)) {
            $result = Db::getInstance()->getValue(
                '
							SELECT `name`
							FROM `'._DB_PREFIX_.'hook`
							WHERE `id_hook` = '.(int) $hookId
            );
            Cache::store($cacheId, $result);

            return $result;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * Return hook live edit bool from ID
     *
     * @throws PrestaShopException
     */
    public static function getLiveEditById($hookId)
    {
        $cacheId = 'hook_live_editbyid_'.$hookId;
        if (!Cache::isStored($cacheId)) {
            $result = Db::getInstance()->getValue(
                '
							SELECT `live_edit`
							FROM `'._DB_PREFIX_.'hook`
							WHERE `id_hook` = '.(int) $hookId
            );
            Cache::store($cacheId, $result);

            return $result;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * Return Hooks List
     *
     * @param int $idHook
     * @param int $idModule
     *
     * @return array Modules List
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getModulesFromHook($idHook, $idModule = null)
    {
        $hmList = static::getHookModuleList();
        $moduleList = (isset($hmList[$idHook])) ? $hmList[$idHook] : [];

        if ($idModule) {
            return (isset($moduleList[$idModule])) ? [$moduleList[$idModule]] : [];
        }

        return $moduleList;
    }

    /**
     * Get list of all registered hooks with modules
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getHookModuleList()
    {
        $cacheId = 'hook_module_list';
        if (!Cache::isStored($cacheId)) {
            $results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
                SELECT h.id_hook, h.name AS h_name, h.title, h.description, h.position, h.live_edit, hm.position AS hm_position, m.id_module, m.name, m.active
                FROM `'._DB_PREFIX_.'hook_module` hm
                STRAIGHT_JOIN `'._DB_PREFIX_.'hook` h ON (h.id_hook = hm.id_hook AND hm.id_shop = '.(int) Context::getContext()->shop->id.')
                STRAIGHT_JOIN `'._DB_PREFIX_.'module` AS m ON (m.id_module = hm.id_module)
                ORDER BY hm.position'
            );
            $list = [];
            foreach ($results as $result) {
                if (!isset($list[$result['id_hook']])) {
                    $list[$result['id_hook']] = [];
                }

                $list[$result['id_hook']][$result['id_module']] = [
                    'id_hook'     => $result['id_hook'],
                    'title'       => $result['title'],
                    'description' => $result['description'],
                    'hm.position' => $result['position'],
                    'live_edit'   => $result['live_edit'],
                    'm.position'  => $result['hm_position'],
                    'id_module'   => $result['id_module'],
                    'name'        => $result['name'],
                    'active'      => $result['active'],
                ];
            }
            Cache::store($cacheId, $list);
            return $list;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * Executes displayable hook and returns HTML string
     *
     * If $moduleId parameter is provided, only hook from single module will be executed. Otherwise, all modules
     * will be invoked, and their HTML results will be concatenated.
     *
     * Module hook response is expected to be HTML code. Responses can be extended with additional HTML elements
     * to support Live Edit functionality, FPC hole punching, etc. Do not use for hooks that return different type
     * of data (for example javascript, json, or xml), as this post-processing would break it.
     *
     * @param string $hookName Hook Name
     * @param array $hookArgs Parameters for the functions
     * @param int|null $moduleId Execute hook for this module only
     *
     * @return string
     *
     * @throws PrestaShopException
     *
     * @since 1.5.0
     */
    public static function displayHook(string $hookName, array $hookArgs = [], int $moduleId = null):string
    {
        return (string)static::exec($hookName, $hookArgs, $moduleId);
    }

    /**
     * Triggers event
     *
     * Used to notify modules about some events
     *
     * @param string $hookName Hook Name
     * @param array $hookArgs Parameters for the functions
     * @param int|null $idShop If specified, hook will be executed for shop with this ID
     * @param int|null $idModule Execute hook for this module only
     *
     * @return void
     *
     * @throws PrestaShopException
     *
     * @since 1.5.0
     */
    public static function triggerEvent(string $hookName, array $hookArgs = [], int $idShop = null, int $idModule = null): void
    {
        static::execWithoutCache($hookName, $hookArgs, $idModule, true, false, false, $idShop);
    }

    /**
     * Query modules for response
     *
     * This hook is used to query modules for some kind of informations. Returns array - map from module name to result
     *
     * Modules can return arbitrary data
     *
     * @param string $hookName Hook Name
     * @param array $hookArgs Parameters for the functions
     * @param int|null $idModule Execute hook for this module only
     *
     * @return array
     *
     * @throws PrestaShopException
     *
     * @since 1.5.0
     */
    public static function getResponses(string $hookName, array $hookArgs = [], int $idModule = null): array
    {
        $ret = static::execWithoutCache($hookName, $hookArgs, $idModule, true, false);
        if (is_array($ret)) {
            return $ret;
        }
        trigger_error("Hook::execWithoutCache did not return array. Ignoring result");
        return [];
    }

    /**
     * Returns hook response from specific module
     *
     * @param string $hookName Hook Name
     * @param int $idModule Execute hook for this module only
     * @param array $hookArgs Parameters for the functions
     *
     * @return mixed|null
     *
     * @throws PrestaShopException
     *
     * @since 1.5.0
     */
    public static function getResponse(string $hookName, int $idModule, array $hookArgs = [])
    {
        $responses = static::getResponses($hookName, $hookArgs, $idModule);
        return $responses
            ? array_shift($responses)
            : null;
    }

    /**
     * Returns response from first module that implements this hook, according to hook positions
     *
     * If parameter $raiseWarning is set to true, and more than one module implements hook, warning will be raised.
     *
     * @param string $hookName Hook Name
     * @param array $hookArgs Parameters for the functions
     * @param bool $raiseWarning
     *
     * @return mixed|null
     * @throws PrestaShopException
     *
     * @since 1.5.0
     */
    public static function getFirstResponse(string $hookName, array $hookArgs = [], bool $raiseWarning = true)
    {
        $responses = static::getResponses($hookName, $hookArgs);
        $count = count($responses);
        switch ($count) {
            case 0:
                return null;
            case 1:
                return array_shift($responses);
            default:
                $modules = implode(', ', array_keys($responses));
                trigger_error(sprintf("Multiple modules [$1%s] are attached to '$2%s' hook. Only the first response will be used!", $modules, $hookName), E_USER_WARNING);
                return array_shift($responses);
        }
    }

    /**
     * Execute modules for specified hook
     *
     * @param string $hookName Hook Name
     * @param array $hookArgs Parameters for the functions
     * @param int $idModule Execute hook for this module only
     * @param bool $arrayReturn If specified, module output will be set by name in an array
     * @param bool $checkExceptions Check permission exceptions
     * @param bool $usePush @deprecated since thirty bees 1.5
     * @param int $idShop If specified, hook will be executed for shop with this ID
     *
     * @return string|array modules output
     *
     * @throws PrestaShopException
     */
    public static function exec(
        $hookName,
        $hookArgs = [],
        $idModule = null,
        $arrayReturn = false,
        $checkExceptions = true,
        $usePush = false,
        $idShop = null
    ) {
        if ($usePush !== false) {
            Tools::displayParameterAsDeprecated('usePush');
        }
        if ($arrayReturn || !PageCache::isEnabled() || PageCacheKey::get() === false) {
            return static::execWithoutCache($hookName, $hookArgs, $idModule, $arrayReturn, $checkExceptions, false, $idShop);
        }

        if (!$moduleList = static::getHookModuleExecList($hookName)) {
            return '';
        }

        $return = '';

        if (!$idModule) {
            $cacheEntry = PageCache::get();
            $cachedHooks = PageCache::getCachedHooks();
            foreach ($moduleList as $m) {
                $idModule = (int) $m['id_module'];
                $data = static::execWithoutCache($hookName, $hookArgs, $idModule, false, $checkExceptions, false, $idShop);
                $idHook = (int) static::getIdByName($hookName);
                if (isset($cachedHooks[$idModule][$idHook])) {
                    $return .= $data;
                } else {
                    // wrap dynamic hooks
                    $key = $cacheEntry->setHook($idModule, $idHook, $hookName, $hookArgs);
                    $delimiter = "<!--[$key]-->";
                    $return .= $delimiter.$data.$delimiter;
                }
            }
        } else {
            $return = static::execWithoutCache($hookName, $hookArgs, $idModule, false, $checkExceptions, false, $idShop);
        }

        return $return;
    }

    /**
     * Execute modules for specified hook
     *
     * @param string $hookName Hook Name
     * @param array $hookArgs Parameters for the functions
     * @param int $idModule Execute hook for this module only
     * @param bool $arrayReturn If specified, module output will be set by name in an array
     * @param bool $checkExceptions Check permission exceptions
     * @param bool $usePush @deprecated since thirty bees 1.5
     * @param int $idShop If specified, hook will be executed for shop with this ID
     *
     * @throws PrestaShopException
     *
     * @return string|array modules output
     */
    public static function execWithoutCache(
        $hookName,
        $hookArgs = [],
        $idModule = null,
        $arrayReturn = false,
        $checkExceptions = true,
        $usePush = false,
        $idShop = null
    ) {
        $output = $arrayReturn ? [] : '';

        if (defined('TB_INSTALLATION_IN_PROGRESS')) {
            return $output;
        }
        if ($usePush !== false) {
            Tools::displayParameterAsDeprecated('usePush');
        }

        static $disableNonNativeModules = null;
        if ($disableNonNativeModules === null) {
            $disableNonNativeModules = (bool) Configuration::get('PS_DISABLE_NON_NATIVE_MODULE');
        }

        // Check arguments validity
        if (($idModule && !is_numeric($idModule)) || !Validate::isHookName($hookName)) {
            throw new PrestaShopException('Invalid id_module or hook_name');
        }

        // If no modules associated to hook_name or recompatible hook name, we stop the function

        if (!$moduleList = Hook::getHookModuleExecList($hookName)) {
            return $output;
        }

        // Check if hook exists
        if (!$idHook = Hook::getIdByName($hookName)) {
            return $output;
        }

        // Store list of executed hooks on this page
        Hook::$executed_hooks[$idHook] = $hookName;

        $liveEdit = false;
        $context = Context::getContext();
        if (!isset($hookArgs['cookie']) || !$hookArgs['cookie']) {
            $hookArgs['cookie'] = $context->cookie;
        }
        if (!isset($hookArgs['cart']) || !$hookArgs['cart']) {
            $hookArgs['cart'] = $context->cart;
        }

        $retroHookName = Hook::getRetroHookName($hookName);

        // Look on modules list
        $altern = 0;

        if ($disableNonNativeModules && !isset(Hook::$native_module)) {
            Hook::$native_module = Module::getNativeModuleList();
        }

        if ($idShop !== null && Validate::isUnsignedId($idShop) && $idShop != $context->shop->getContextShopID()) {
            $oldContext = $context->shop->getContext();
            $oldShop = clone $context->shop;
            $shop = new Shop((int) $idShop);
            if (Validate::isLoadedObject($shop)) {
                $context->shop = $shop;
                $context->shop->setContext(Shop::CONTEXT_SHOP, $shop->id);
            }
        }

        foreach ($moduleList as $array) {
            // Check errors
            if ($idModule && $idModule != $array['id_module']) {
                continue;
            }

            if ($disableNonNativeModules && Hook::$native_module && count(Hook::$native_module) && !in_array($array['module'], Hook::$native_module)) {
                continue;
            }

            // Check permissions
            if ($checkExceptions) {
                $exceptions = Module::getExceptionsStatic($array['id_module'], $array['id_hook']);

                $controller = Dispatcher::getInstance()->getController();
                $controllerObj = Context::getContext()->controller;

                //check if current controller is a module controller
                if (isset($controllerObj->module) && Validate::isLoadedObject($controllerObj->module)) {
                    $controller = 'module-'.$controllerObj->module->name.'-'.$controller;
                }

                if (in_array($controller, $exceptions)) {
                    continue;
                }

                //Backward compatibility of controller names
                $matchingName = [
                    'authentication'     => 'auth',
                    'productscomparison' => 'compare',
                ];
                if (isset($matchingName[$controller]) && in_array($matchingName[$controller], $exceptions)) {
                    continue;
                }
                if (Validate::isLoadedObject($context->employee) && !Module::getPermissionStatic($array['id_module'], 'view', $context->employee)) {
                    continue;
                }
            }

            if (!($moduleInstance = Module::getInstanceByName($array['module']))) {
                continue;
            }

            // Check which / if method is callable
            $hookCallable = is_callable([$moduleInstance, 'hook'.$hookName]);
            $hookRetroCallable = is_callable([$moduleInstance, 'hook'.$retroHookName]);

            if ($hookCallable || $hookRetroCallable) {

                if (Module::preCall($moduleInstance->name)) {
                    $hookArgs['altern'] = ++$altern;

                    // Call hook method
                    if ($hookCallable) {
                        $display = Hook::coreCallHook($moduleInstance, 'hook' . $hookName, $hookArgs);
                    } else {
                        $display = Hook::coreCallHook($moduleInstance, 'hook' . $retroHookName, $hookArgs);
                    }

                    // Live edit
                    if (!$arrayReturn && $array['live_edit'] && Tools::isSubmit('live_edit') && Tools::getValue('ad')
                        && Tools::getValue('liveToken') == Tools::getAdminToken(
                            'AdminModulesPositions'
                            . (int)Tab::getIdFromClassName('AdminModulesPositions') . Tools::getIntValue('id_employee')
                        )
                    ) {
                        $liveEdit = true;
                        $output .= static::wrapLiveEdit($display, $moduleInstance, $array['id_hook']);
                    } elseif ($arrayReturn) {
                        $output[$moduleInstance->name] = $display;
                    } else {
                        $output .= $display;
                    }
                }
            } else {
                Logger::addLog("Module '{$array['module']}' doesn't implement handler for hook '$hookName'", 2, 0, 'Module', $moduleInstance->id);
            }
        }

        if (isset($oldShop) && isset($oldContext) && isset($shop)) {
            $context->shop = $oldShop;
            $context->shop->setContext($oldContext, $shop->id);
        }

        if ($arrayReturn) {
            return $output;
        } else {
            if ($liveEdit) {
                return (
                    '<script type="text/javascript">hooks_list.push(\'' . $hookName . '\');</script>' .
                    '<div id="' . $hookName . '" class="dndHook" style="min-height:50px">' . $output . '</div>'
                );
            } else {
                return $output;
            }
        }
    }

    /**
     * Get list of modules we can execute per hook
     *
     * @param string $hookName Get list of modules for this hook if given
     *
     * @return array|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getHookModuleExecList($hookName = null)
    {
        $context = Context::getContext();
        $cacheId = 'hook_module_exec_list_'.(isset($context->shop->id) ? '_'.$context->shop->id : '').((isset($context->customer)) ? '_'.$context->customer->id : '');
        if (!Cache::isStored($cacheId) || $hookName == 'displayPayment' || $hookName == 'displayPaymentEU') {
            $frontend = true;
            $groups = [];
            $useGroups = Group::isFeatureActive();
            if (isset($context->employee)) {
                $frontend = false;
            } else {
                // Get groups list
                if ($useGroups) {
                    if (isset($context->customer) && $context->customer->isLogged()) {
                        $groups = $context->customer->getGroups();
                    } elseif (isset($context->customer) && $context->customer->isLogged(true)) {
                        $groups = [(int) Configuration::get('PS_GUEST_GROUP')];
                    } else {
                        $groups = [(int) Configuration::get('PS_UNIDENTIFIED_GROUP')];
                    }
                }
            }

            // SQL Request
            $sql = new DbQuery();
            $sql->select('h.`name` as hook, m.`id_module`, h.`id_hook`, m.`name` as module, h.`live_edit`');
            $sql->from('module', 'm');
            $sql->join(Shop::addSqlAssociation('module', 'm', true, 'module_shop.enable_device & '.(int) Context::getContext()->getDevice()));
            $sql->innerJoin('module_shop', 'ms', 'ms.`id_module` = m.`id_module`');
            $sql->innerJoin('hook_module', 'hm', 'hm.`id_module` = m.`id_module`');
            $sql->innerJoin('hook', 'h', 'hm.`id_hook` = h.`id_hook`');
            if ($hookName != 'displayPayment' && $hookName != 'displayPaymentEU') {
                $sql->where('h.`name` != "displayPayment" AND h.`name` != "displayPaymentEU"');
            } // For payment modules, we check that they are available in the contextual country
            elseif ($frontend) {
                if (Validate::isLoadedObject($context->country)) {
                    $sql->where('((h.`name` = "displayPayment" OR h.`name` = "displayPaymentEU") AND (SELECT `id_country` FROM `'._DB_PREFIX_.'module_country` mc WHERE mc.`id_module` = m.`id_module` AND `id_country` = '.(int) $context->country->id.' AND `id_shop` = '.(int) $context->shop->id.' LIMIT 1) = '.(int) $context->country->id.')');
                }
                if (Validate::isLoadedObject($context->currency)) {
                    $sql->where('((h.`name` = "displayPayment" OR h.`name` = "displayPaymentEU") AND (SELECT `id_currency` FROM `'._DB_PREFIX_.'module_currency` mcr WHERE mcr.`id_module` = m.`id_module` AND `id_currency` IN ('.(int) $context->currency->id.', -1, -2) LIMIT 1) IN ('.(int) $context->currency->id.', -1, -2))');
                }
                if (Validate::isLoadedObject($context->cart)) {
                    $carrier = new Carrier($context->cart->id_carrier);
                    if (Validate::isLoadedObject($carrier)) {
                        $sql->where('((h.`name` = "displayPayment" OR h.`name` = "displayPaymentEU") AND (SELECT `id_reference` FROM `'._DB_PREFIX_.'module_carrier` mcar WHERE mcar.`id_module` = m.`id_module` AND `id_reference` = '.(int) $carrier->id_reference.' AND `id_shop` = '.(int) $context->shop->id.' LIMIT 1) = '.(int) $carrier->id_reference.')');
                    }
                }
            }
            if (Validate::isLoadedObject($context->shop)) {
                $sql->where('hm.`id_shop` = '.(int) $context->shop->id);
            }

            if ($frontend) {
                if ($useGroups) {
                    $sql->leftJoin('module_group', 'mg', 'mg.`id_module` = m.`id_module`');
                    if (Validate::isLoadedObject($context->shop)) {
                        $sql->where('mg.`id_shop` = '.((int) $context->shop->id).(count($groups) ? ' AND  mg.`id_group` IN ('.implode(', ', $groups).')' : ''));
                    } elseif (count($groups)) {
                        $sql->where('mg.`id_group` IN ('.implode(', ', $groups).')');
                    }
                }
            }

            $sql->groupBy('hm.id_hook, hm.id_module');
            $sql->orderBy('hm.`position`');

            $list = [];
            if ($result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql)) {
                foreach ($result as $row) {
                    $row['hook'] = strtolower($row['hook']);
                    if (!isset($list[$row['hook']])) {
                        $list[$row['hook']] = [];
                    }

                    $list[$row['hook']][] = [
                        'id_hook'   => $row['id_hook'],
                        'module'    => $row['module'],
                        'id_module' => $row['id_module'],
                        'live_edit' => $row['live_edit'],
                    ];
                }
            }
            if ($hookName != 'displayPayment' && $hookName != 'displayPaymentEU') {
                Cache::store($cacheId, $list);
            }
        } else {
            $list = Cache::retrieve($cacheId);
        }

        // If hook_name is given, just get list of modules for this hook
        if ($hookName) {
            $retroHookName = strtolower(Hook::getRetroHookName($hookName));
            $hookName = strtolower($hookName);

            $return = [];
            $insertedModules = [];
            if (isset($list[$hookName])) {
                $return = $list[$hookName];
            }
            foreach ($return as $module) {
                $insertedModules[] = $module['id_module'];
            }
            if (isset($list[$retroHookName])) {
                foreach ($list[$retroHookName] as $retroModuleCall) {
                    if (!in_array($retroModuleCall['id_module'], $insertedModules)) {
                        $return[] = $retroModuleCall;
                    }
                }
            }

            return (count($return) > 0 ? $return : false);
        } else {
            return $list;
        }
    }

    /**
     * Return backward compatibility hook name
     *
     * @param string $hookName Hook name
     *
     * @return string alternate hook name
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getRetroHookName($hookName)
    {
        $hookName = strtolower($hookName);
        $aliasList = Hook::getHookAliasList();

        if (isset($aliasList[$hookName])) {
            return strtolower($aliasList[$hookName]);
        }

        foreach ($aliasList as $alias => $original) {
            if (strtolower($original) === $hookName) {
                return strtolower($alias);
            }
        }

        return '';
    }

    /**
     * Get list of hook alias
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getHookAliasList()
    {
        $cacheId = 'hook_alias';
        if (!Cache::isStored($cacheId)) {
            $hookAliasList = Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'hook_alias`');
            $hookAlias = [];
            if ($hookAliasList) {
                foreach ($hookAliasList as $ha) {
                    $hookAlias[strtolower($ha['alias'])] = $ha['name'];
                }
            }
            Cache::store($cacheId, $hookAlias);

            return $hookAlias;
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * Return hook ID from name
     *
     * @param string $hookName Hook name
     *
     * @return int Hook ID
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getIdByName($hookName)
    {
        $hookName = strtolower($hookName);
        if (!Validate::isHookName($hookName)) {
            return false;
        }

        $cacheId = 'hook_idsbyname';
        if (!Cache::isStored($cacheId)) {
            // Get all hook ID by name and alias
            $hookIds = [];
            $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
            $result = $db->executeS(
                '
			SELECT `id_hook`, `name`
			FROM `'._DB_PREFIX_.'hook`
			UNION
			SELECT `id_hook`, ha.`alias` AS name
			FROM `'._DB_PREFIX_.'hook_alias` ha
			INNER JOIN `'._DB_PREFIX_.'hook` h ON ha.name = h.name', false
            );
            while ($row = $db->nextRow($result)) {
                $hookIds[strtolower($row['name'])] = $row['id_hook'];
            }
            Cache::store($cacheId, $hookIds);
        } else {
            $hookIds = Cache::retrieve($cacheId);
        }

        return (isset($hookIds[$hookName]) ? $hookIds[$hookName] : false);
    }

    /**
     * @param Module $module
     * @param string $method
     * @param array $params
     *
     * @return string|array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function coreCallHook($module, $method, $params)
    {
        // Define if we will log modules performances for this session
        if (Module::$_log_modules_perfs === null) {
            $modulo = _PS_DEBUG_PROFILING_ ? 1 : Configuration::get('PS_log_modules_perfs_MODULO');
            Module::$_log_modules_perfs = ($modulo && mt_rand(0, $modulo - 1) == 0);
            if (Module::$_log_modules_perfs) {
                Module::$_log_modules_perfs_session = mt_rand();
            }
        }

        // Immediately return the result if we do not log performances
        if (!Module::$_log_modules_perfs) {
            return $module->{$method}($params);
        }

        // Store time and memory before and after hook call and save the result in the database
        $timeStart = microtime(true);
        $memoryStart = memory_get_usage(true);

        // Call hook
        $r = $module->{$method}($params);

        $timeEnd = microtime(true);
        $memoryEnd = memory_get_usage(true);

        Db::getInstance()->insert(
            'modules_perfs',
            [
                'session'      => (int) Module::$_log_modules_perfs_session,
                'module'       => pSQL($module->name),
                'method'       => pSQL($method),
                'time_start'   => pSQL($timeStart),
                'time_end'     => pSQL($timeEnd),
                'memory_start' => pSQL($memoryStart),
                'memory_end'   => pSQL($memoryEnd),
            ]
        );

        return $r;
    }

    /**
     * @param string|null $display
     * @param Module $moduleInstance
     * @param int $idHook
     *
     * @return string
     */
    public static function wrapLiveEdit($display, $moduleInstance, $idHook)
    {
        $display = (string)$display;
        return '<script type="text/javascript"> modules_list.push(\''.Tools::safeOutput($moduleInstance->name).'\');</script>
				<div id="hook_'.(int) $idHook.'_module_'.(int) $moduleInstance->id.'_moduleName_'.str_replace('_', '-', Tools::safeOutput($moduleInstance->name)).'"
				class="dndModule" style="border: 1px dotted red;'.(!strlen($display) ? 'height:50px;' : '').'">
					<span style="font-family: Georgia;font-size:13px;font-style:italic;">
						<img style="padding-right:5px;" src="'._MODULE_DIR_.Tools::safeOutput($moduleInstance->name).'/logo.gif">'
            .Tools::safeOutput($moduleInstance->displayName).'<span style="float:right">
				<a href="#" id="'.(int) $idHook.'_'.(int) $moduleInstance->id.'" class="moveModule">
					<img src="'._PS_ADMIN_IMG_.'arrow_out.png"></a>
				<a href="#" id="'.(int) $idHook.'_'.(int) $moduleInstance->id.'" class="unregisterHook">
					<img src="'._PS_ADMIN_IMG_.'delete.gif"></a></span>
				</span>'.$display.'</div>';
    }

    /**
     * Return hook ID from name
     *
     * @param string $hookName Hook name
     *
     * @return int Hook ID
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     *
     * @deprecated 1.0.0 use Hook::getIdByName() instead
     */
    public static function get($hookName)
    {
        Tools::displayAsDeprecated('Use Hook::getIdByName() instead');

        if (!Validate::isHookName($hookName)) {
            throw new PrestaShopException("Invalid hook name: " . $hookName);
        }

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('`id_hook`, `name`')
                ->from('hook')
                ->where('`name` = \''.pSQL($hookName).'\'')
        );

        return ($result ? $result['id_hook'] : false);
    }

    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add($autoDate = true, $nullValues = false)
    {
        Cache::clean('hook_idsbyname');

        return parent::add($autoDate, $nullValues);
    }

    /**
     * Returns true if $hookName is a displayable hook
     *
     * At the moment, crude check for hook name is used -- every hook starting with display
     * is considered displayable hook, with exception of displayAdmin*
     *
     * @param string $hookName
     * @param bool $includeBackOfficeHooks if true, then check for back office displayable hooks as well
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function isDisplayableHook($hookName, $includeBackOfficeHooks=false)
    {
        $variants = [ $hookName, HookCore::getRetroHookName($hookName) ];
        foreach ($variants as $hook) {
            $hook = strtolower($hook);
            if ((strpos($hook, 'display') === 0)) {
                if ($includeBackOfficeHooks) {
                    return true;
                }
                return (
                    strpos($hook, 'displayadmin') !== 0 &&
                    strpos($hook, 'displaybackoffice') !== 0
                );
            }
        }
        return false;
    }

}
