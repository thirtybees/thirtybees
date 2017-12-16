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
 * Class AdminModulesPositionsControllerCore
 *
 * @since 1.0.0
 */
class AdminModulesPositionsControllerCore extends AdminController
{
    // @codingStandardsIgnoreStart
    /** @var int $display_key */
    protected $display_key = 0;
    // @codingStandardsIgnoreEnd

    /**
     * AdminModulesPositionsControllerCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    /**
     * Post processing
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function postProcess()
    {
        // Getting key value for display
        if (Tools::getValue('show_modules') && strval(Tools::getValue('show_modules')) != 'all') {
            $this->display_key = (int) Tools::getValue('show_modules');
        }

        $this->addjQueryPlugin(
            [
                'select2',
            ]
        );

        $this->addJS(
            [
                _PS_JS_DIR_.'admin/modules-position.js',
                _PS_JS_DIR_.'jquery/plugins/select2/select2_locale_'.$this->context->language->iso_code.'.js',
            ]
        );

        // Change position in hook
        if (array_key_exists('changePosition', $_GET)) {
            if ($this->tabAccess['edit'] === '1') {
                $idModule = (int) Tools::getValue('id_module');
                $idHook = (int) Tools::getValue('id_hook');
                $module = Module::getInstanceById($idModule);
                if (Validate::isLoadedObject($module)) {
                    $module->updatePosition($idHook, (int) Tools::getValue('direction'));
                    Tools::redirectAdmin(static::$currentIndex.($this->display_key ? '&show_modules='.$this->display_key : '').'&token='.$this->token);
                } else {
                    $this->errors[] = Tools::displayError('This module cannot be loaded.');
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        } // Add new module in hook
        elseif (Tools::isSubmit('submitAddToHook')) {
            if ($this->tabAccess['add'] === '1') {
                // Getting vars...
                $idModule = (int) Tools::getValue('id_module');
                $module = Module::getInstanceById($idModule);
                $idHook = (int) Tools::getValue('id_hook');
                $hook = new Hook($idHook);

                if (!$idModule || !Validate::isLoadedObject($module)) {
                    $this->errors[] = Tools::displayError('This module cannot be loaded.');
                } elseif (!$idHook || !Validate::isLoadedObject($hook)) {
                    $this->errors[] = Tools::displayError('Hook cannot be loaded.');
                } elseif (Hook::getModulesFromHook($idHook, $idModule)) {
                    $this->errors[] = Tools::displayError('This module has already been transplanted to this hook.');
                } elseif (!$module->isHookableOn($hook->name)) {
                    $this->errors[] = Tools::displayError('This module cannot be transplanted to this hook.');
                } // Adding vars...
                else {
                    if (!$module->registerHook($hook->name, Shop::getContextListShopID())) {
                        $this->errors[] = Tools::displayError('An error occurred while transplanting the module to its hook.');
                    } else {
                        $exceptions = Tools::getValue('exceptions');
                        $exceptions = (isset($exceptions[0])) ? $exceptions[0] : [];
                        $exceptions = explode(',', str_replace(' ', '', $exceptions));
                        $exceptions = array_unique($exceptions);

                        foreach ($exceptions as $key => $except) {
                            if (empty($except)) {
                                unset($exceptions[$key]);
                            } elseif (!empty($except) && !Validate::isFileName($except)) {
                                $this->errors[] = Tools::displayError('No valid value for field exceptions has been defined.');
                            }
                        }
                        if (!$this->errors && !$module->registerExceptions($idHook, $exceptions, Shop::getContextListShopID())) {
                            $this->errors[] = Tools::displayError('An error occurred while transplanting the module to its hook.');
                        }
                    }
                    if (!$this->errors) {
                        Tools::redirectAdmin(static::$currentIndex.'&conf=16'.($this->display_key ? '&show_modules='.$this->display_key : '').'&token='.$this->token);
                    }
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to add this.');
            }
        } // Edit module from hook
        elseif (Tools::isSubmit('submitEditGraft')) {
            if ($this->tabAccess['add'] === '1') {
                // Getting vars...
                $idModule = (int) Tools::getValue('id_module');
                $module = Module::getInstanceById($idModule);
                $idHook = (int) Tools::getValue('id_hook');
                $hook = new Hook($idHook);

                if (!$idModule || !Validate::isLoadedObject($module)) {
                    $this->errors[] = Tools::displayError('This module cannot be loaded.');
                } elseif (!$idHook || !Validate::isLoadedObject($hook)) {
                    $this->errors[] = Tools::displayError('Hook cannot be loaded.');
                } else {
                    $exceptions = Tools::getValue('exceptions');
                    if (is_array($exceptions)) {
                        foreach ($exceptions as $id => $exception) {
                            $exception = explode(',', str_replace(' ', '', $exception));
                            $exception = array_unique($exception);
                            // Check files name
                            foreach ($exception as $except) {
                                if (!empty($except) && !Validate::isFileName($except)) {
                                    $this->errors[] = Tools::displayError('No valid value for field exceptions has been defined.');
                                }
                            }

                            $exceptions[$id] = $exception;
                        }

                        // Add files exceptions
                        if (!$module->editExceptions($idHook, $exceptions)) {
                            $this->errors[] = Tools::displayError('An error occurred while transplanting the module to its hook.');
                        }

                        if (!$this->errors) {
                            Tools::redirectAdmin(static::$currentIndex.'&conf=16'.($this->display_key ? '&show_modules='.$this->display_key : '').'&token='.$this->token);
                        }
                    } else {
                        $exceptions = explode(',', str_replace(' ', '', $exceptions));
                        $exceptions = array_unique($exceptions);

                        // Check files name
                        foreach ($exceptions as $except) {
                            if (!empty($except) && !Validate::isFileName($except)) {
                                $this->errors[] = Tools::displayError('No valid value for field exceptions has been defined.');
                            }
                        }

                        // Add files exceptions
                        if (!$module->editExceptions($idHook, $exceptions, Shop::getContextListShopID())) {
                            $this->errors[] = Tools::displayError('An error occurred while transplanting the module to its hook.');
                        } else {
                            Tools::redirectAdmin(static::$currentIndex.'&conf=16'.($this->display_key ? '&show_modules='.$this->display_key : '').'&token='.$this->token);
                        }
                    }
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to add this.');
            }
        } // Delete module from hook
        elseif (array_key_exists('deleteGraft', $_GET)) {
            if ($this->tabAccess['delete'] === '1') {
                $idModule = (int) Tools::getValue('id_module');
                $module = Module::getInstanceById($idModule);
                $idHook = (int) Tools::getValue('id_hook');
                $hook = new Hook($idHook);
                if (!Validate::isLoadedObject($module)) {
                    $this->errors[] = Tools::displayError('This module cannot be loaded.');
                } elseif (!$idHook || !Validate::isLoadedObject($hook)) {
                    $this->errors[] = Tools::displayError('Hook cannot be loaded.');
                } else {
                    if (!$module->unregisterHook($idHook, Shop::getContextListShopID())
                        || !$module->unregisterExceptions($idHook, Shop::getContextListShopID())
                    ) {
                        $this->errors[] = Tools::displayError('An error occurred while deleting the module from its hook.');
                    } else {
                        Tools::redirectAdmin(static::$currentIndex.'&conf=17'.($this->display_key ? '&show_modules='.$this->display_key : '').'&token='.$this->token);
                    }
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to delete this.');
            }
        } elseif (Tools::isSubmit('unhookform')) {
            if (!($unhooks = Tools::getValue('unhooks')) || !is_array($unhooks)) {
                $this->errors[] = Tools::displayError('Please select a module to unhook.');
            } else {
                foreach ($unhooks as $unhook) {
                    $explode = explode('_', $unhook);
                    $idHook = $explode[0];
                    $idModule = $explode[1];
                    $module = Module::getInstanceById((int) $idModule);
                    $hook = new Hook((int) $idHook);
                    if (!Validate::isLoadedObject($module)) {
                        $this->errors[] = Tools::displayError('This module cannot be loaded.');
                    } elseif (!$idHook || !Validate::isLoadedObject($hook)) {
                        $this->errors[] = Tools::displayError('Hook cannot be loaded.');
                    } else {
                        if (!$module->unregisterHook((int) $idHook) || !$module->unregisterExceptions((int) $idHook)) {
                            $this->errors[] = Tools::displayError('An error occurred while deleting the module from its hook.');
                        }
                    }
                }
                if (!count($this->errors)) {
                    Tools::redirectAdmin(static::$currentIndex.'&conf=17'.($this->display_key ? '&show_modules='.$this->display_key : '').'&token='.$this->token);
                }
            }
        } else {
            parent::postProcess();
        }
    }

    /**
     * Initialize content
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function initContent()
    {
        $this->initTabModuleList();
        $this->addjqueryPlugin('sortable');
        $this->initPageHeaderToolbar();

        if (array_key_exists('addToHook', $_GET) || array_key_exists('editGraft', $_GET) || (Tools::isSubmit('submitAddToHook') && $this->errors)) {
            $this->display = 'edit';

            $this->content .= $this->renderForm();
        } else {
            $this->content .= $this->initMain();
        }

        $this->context->smarty->assign(
            [
                'content'                   => $this->content,
                'show_page_header_toolbar'  => $this->show_page_header_toolbar,
                'page_header_toolbar_title' => $this->page_header_toolbar_title,
                'page_header_toolbar_btn'   => $this->page_header_toolbar_btn,
            ]
        );
    }

    /**
     * Initialize page header toolbar
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function initPageHeaderToolbar()
    {
        $this->page_header_toolbar_btn['save'] = [
            'href' => static::$currentIndex.'&addToHook'.($this->display_key ? '&show_modules='.$this->display_key : '').'&token='.$this->token,
            'desc' => $this->l('Transplant a module', null, null, false),
            'icon' => 'process-icon-anchor',
        ];

        parent::initPageHeaderToolbar();
    }

    /**
     * Render form
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function renderForm()
    {
        // Init toolbar
        $this->initToolbarTitle();
        // toolbar (save, cancel, new, ..)
        $this->initToolbar();
        $idModule = (int) Tools::getValue('id_module');
        $idHook = (int) Tools::getValue('id_hook');
        $showModules = (int) Tools::getValue('show_modules');

        if (Tools::isSubmit('editGraft')) {
            // Check auth for this page
            if (!$idModule || !$idHook) {
                Tools::redirectAdmin(static::$currentIndex.'&token='.$this->token);
            }

            $sql = 'SELECT id_module
					FROM '._DB_PREFIX_.'hook_module
					WHERE id_module = '.$idModule.'
						AND id_hook = '.$idHook.'
						AND id_shop IN('.implode(', ', Shop::getContextListShopID()).')';
            if (!Db::getInstance()->getValue($sql)) {
                Tools::redirectAdmin(static::$currentIndex.'&token='.$this->token);
            }

            $slModule = Module::getInstanceById($idModule);
            $exceptsList = $slModule->getExceptions($idHook, true);
            $exceptsDiff = false;
            if ($exceptsList) {
                $first = current($exceptsList);
                foreach ($exceptsList as $k => $v) {
                    if (array_diff($v, $first) || array_diff($first, $v)) {
                        $exceptsDiff = true;
                    }
                }
            }
        } else {
            $exceptsDiff = false;
            $exceptsList = Tools::getValue('exceptions', [[]]);
        }
        $modules = Module::getModulesInstalled(0);

        $instances = [];
        foreach ($modules as $module) {
            if ($tmpInstance = Module::getInstanceById($module['id_module'])) {
                $instances[$tmpInstance->displayName] = $tmpInstance;
            }
        }
        ksort($instances);
        $modules = $instances;

        $hooks = [];
        if ($showModules || (Tools::getValue('id_hook') > 0)) {
            $moduleInstance = Module::getInstanceById((int) Tools::getValue('id_module', $showModules));
            $hooks = $moduleInstance->getPossibleHooksList();
        }

        $exceptionListDiff = [];
        foreach ($exceptsList as $shop_id => $fileList) {
            $exceptionListDiff[] = $this->displayModuleExceptionList($fileList, $shop_id);
        }

        $tpl = $this->createTemplate('form.tpl');
        $tpl->assign(
            [
                'url_submit'          => static::$currentIndex.'&token='.$this->token,
                'edit_graft'          => Tools::isSubmit('editGraft'),
                'id_module'           => (int) Tools::getValue('id_module'),
                'id_hook'             => (int) Tools::getValue('id_hook'),
                'show_modules'        => $showModules,
                'hooks'               => $hooks,
                'exception_list'      => $this->displayModuleExceptionList(array_shift($exceptsList), 0),
                'exception_list_diff' => $exceptionListDiff,
                'except_diff'         => isset($exceptsDiff) ? $exceptsDiff : null,
                'display_key'         => $this->display_key,
                'modules'             => $modules,
                'show_toolbar'        => true,
                'toolbar_btn'         => $this->toolbar_btn,
                'toolbar_scroll'      => $this->toolbar_scroll,
                'title'               => $this->toolbar_title,
                'table'               => 'hook_module',
            ]
        );

        return $tpl->fetch();
    }

    /**
     * Display module exception list
     *
     * @param array $fileList
     * @param int   $idShop
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function displayModuleExceptionList($fileList, $idShop)
    {
        if (!is_array($fileList)) {
            $fileList = ($fileList) ? [$fileList] : [];
        }

        $content = '<p><input type="text" name="exceptions['.$idShop.']" value="'.implode(', ', $fileList).'" id="em_text_'.$idShop.'" placeholder="'.$this->l('E.g. address, addresses, attachment').'"/></p>';

        if ($idShop) {
            $shop = new Shop($idShop);
            $content .= ' ('.$shop->name.')';
        }

        $content .= '<p>
					<select size="25" id="em_list_'.$idShop.'" multiple="multiple">
					<option disabled="disabled">'.$this->l('___________ CUSTOM ___________').'</option>';

        // @todo do something better with controllers
        $controllers = Dispatcher::getControllers(_PS_FRONT_CONTROLLER_DIR_);
        ksort($controllers);

        foreach ($fileList as $k => $v) {
            if (!array_key_exists($v, $controllers)) {
                $content .= '<option value="'.$v.'">'.$v.'</option>';
            }
        }

        $content .= '<option disabled="disabled">'.$this->l('____________ CORE ____________').'</option>';

        foreach ($controllers as $k => $v) {
            $content .= '<option value="'.$k.'">'.$k.'</option>';
        }

        $modulesControllersType = ['admin' => $this->l('Admin modules controller'), 'front' => $this->l('Front modules controller')];
        foreach ($modulesControllersType as $type => $label) {
            $content .= '<option disabled="disabled">____________ '.$label.' ____________</option>';
            $allModulesControllers = Dispatcher::getModuleControllers($type);
            foreach ($allModulesControllers as $module => $modulesControllers) {
                foreach ($modulesControllers as $cont) {
                    $content .= '<option value="module-'.$module.'-'.$cont.'">module-'.$module.'-'.$cont.'</option>';
                }
            }
        }

        $content .= '</select>
					</p>';

        return $content;
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function initMain()
    {
        // Init toolbar
        $this->initToolbarTitle();

        $adminDir = basename(_PS_ADMIN_DIR_);
        $modules = Module::getModulesInstalled();

        $assocModulesId = [];
        foreach ($modules as $module) {
            if ($tmpInstance = Module::getInstanceById((int) $module['id_module'])) {
                // We want to be able to sort modules by display name
                $moduleInstances[$tmpInstance->displayName] = $tmpInstance;
                // But we also want to associate hooks to modules using the modules IDs
                $assocModulesId[(int) $module['id_module']] = $tmpInstance->displayName;
            }
        }
        ksort($moduleInstances);
        $hooks = Hook::getHooks();
        foreach ($hooks as $key => $hook) {
            // Get all modules for this hook or only the filtered module
            $hooks[$key]['modules'] = Hook::getModulesFromHook($hook['id_hook'], $this->display_key);
            $hooks[$key]['module_count'] = count($hooks[$key]['modules']);
            if ($hooks[$key]['module_count']) {
                // If modules were found, link to the previously created Module instances
                if (is_array($hooks[$key]['modules']) && !empty($hooks[$key]['modules'])) {
                    foreach ($hooks[$key]['modules'] as $moduleKey => $module) {
                        if (isset($assocModulesId[$module['id_module']])) {
                            $hooks[$key]['modules'][$moduleKey]['instance'] = $moduleInstances[$assocModulesId[$module['id_module']]];
                        }
                    }
                }
            } else {
                unset($hooks[$key]);
            }
        }

        $this->addJqueryPlugin('tablednd');

        $this->toolbar_btn['save'] = [
            'href' => static::$currentIndex.'&addToHook'.($this->display_key ? '&show_modules='.$this->display_key : '').'&token='.$this->token,
            'desc' => $this->l('Transplant a module'),
        ];

        $liveEditParams = [
            'live_edit'   => true,
            'ad'          => $adminDir,
            'liveToken'   => $this->token,
            'id_employee' => (int) $this->context->employee->id,
            'id_shop'     => (int) $this->context->shop->id,
        ];

        $this->context->smarty->assign(
            [
                'show_toolbar'       => true,
                'toolbar_btn'        => $this->toolbar_btn,
                'title'              => $this->toolbar_title,
                'toolbar_scroll'     => 'false',
                'token'              => $this->token,
                'url_show_modules'   => static::$currentIndex.'&token='.$this->token.'&show_modules=',
                'modules'            => $moduleInstances,
                'url_show_invisible' => static::$currentIndex.'&token='.$this->token.'&show_modules='.(int) Tools::getValue('show_modules').'&hook_position=',
                'live_edit'          => Shop::isFeatureActive() && Shop::getContext() != Shop::CONTEXT_SHOP,
                'url_live_edit'      => $this->getLiveEditUrl($liveEditParams),
                'display_key'        => $this->display_key,
                'hooks'              => $hooks,
                'url_submit'         => static::$currentIndex.'&token='.$this->token,
                'can_move'           => (Shop::isFeatureActive() && Shop::getContext() != Shop::CONTEXT_SHOP) ? false : true,
            ]
        );

        return $this->createTemplate('list_modules.tpl')->fetch();
    }

    /**
     * Get live edit params
     *
     * @param array $liveEditParams
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function getLiveEditUrl($liveEditParams)
    {
        $lang = '';

        $languageIds = Language::getIDs(true);
        if (Configuration::get('PS_REWRITING_SETTINGS') && !empty($languageIds) && count($languageIds) > 1) {
            $lang = Language::getIsoById($this->context->employee->id_lang).'/';
        }
        unset($languageIds);

        // Shop::initialize() in config.php may empty $this->context->shop->virtual_uri so using a new shop instance for getBaseUrl()
        $this->context->shop = new Shop((int) $this->context->shop->id);
        $url = $this->context->shop->getBaseURL().$lang.Dispatcher::getInstance()->createUrl('index', (int) $this->context->language->id, $liveEditParams);

        return $url;
    }

    /**
     * Ajax process update positions
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function ajaxProcessUpdatePositions()
    {
        if ($this->tabAccess['edit'] === '1') {
            $idModule = (int) (Tools::getValue('id_module'));
            $idHook = (int) (Tools::getValue('id_hook'));
            $way = (int) (Tools::getValue('way'));
            $positions = Tools::getValue(strval($idHook));
            $position = (is_array($positions)) ? array_search($idHook.'_'.$idModule, $positions) : null;
            $module = Module::getInstanceById($idModule);
            if (Validate::isLoadedObject($module)) {
                if ($module->updatePosition($idHook, $way, $position)) {
                    die(true);
                } else {
                    die('{"hasError" : true, "errors" : "Cannot update module position."}');
                }
            } else {
                die('{"hasError" : true, "errors" : "This module cannot be loaded."}');
            }
        }
    }

    /**
     * Ajax process get hookable list
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function ajaxProcessGetHookableList()
    {
        if ($this->tabAccess['view'] === '1') {
            /* PrestaShop demo mode */
            if (_PS_MODE_DEMO_) {
                die('{"hasError" : true, "errors" : ["Live Edit: This functionality has been disabled."]}');
            }

            if (!count(Tools::getValue('hooks_list'))) {
                die('{"hasError" : true, "errors" : ["Live Edit: no module on this page."]}');
            }

            $modulesList = Tools::getValue('modules_list');
            $hooksList = Tools::getValue('hooks_list');
            $hookableList = [];

            foreach ($modulesList as $module) {
                $module = trim($module);
                if (!$module) {
                    continue;
                }

                if (!Validate::isModuleName($module)) {
                    die('{"hasError" : true, "errors" : ["Live Edit: module is invalid."]}');
                }

                $moduleInstance = Module::getInstanceByName($module);
                foreach ($hooksList as $hookName) {
                    $hookName = trim($hookName);
                    if (!$hookName) {
                        continue;
                    }
                    if (!array_key_exists($hookName, $hookableList)) {
                        $hookableList[$hookName] = [];
                    }
                    if ($moduleInstance->isHookableOn($hookName)) {
                        array_push($hookableList[$hookName], str_replace('_', '-', $module));
                    }
                }
            }
            $hookableList['hasError'] = false;
            $this->ajaxDie(json_encode($hookableList));
        }
    }

    /**
     * Ajax process get hookable module list
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function ajaxProcessGetHookableModuleList()
    {
        if ($this->tabAccess['view'] === '1') {
            /* PrestaShop demo mode */
            if (_PS_MODE_DEMO_) {
                $this->ajaxDie('{"hasError" : true, "errors" : ["Live Edit: This functionality has been disabled."]}');
            }
            /* PrestaShop demo mode*/

            $hookName = Tools::getValue('hook');
            $hookableModulesList = [];
            $modules = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                ->select('`id_module`, `name`')
                ->from('module')
            );
            foreach ($modules as $module) {
                if (!Validate::isModuleName($module['name'])) {
                    continue;
                }
                if (file_exists(_PS_MODULE_DIR_.$module['name'].'/'.$module['name'].'.php')) {
                    include_once(_PS_MODULE_DIR_.$module['name'].'/'.$module['name'].'.php');

                    /** @var Module $mod */
                    $mod = new $module['name']();
                    if ($mod->isHookableOn($hookName)) {
                        $hookableModulesList[] = ['id' => (int) $mod->id, 'name' => $mod->displayName, 'display' => Hook::exec($hookName, [], (int) $mod->id)];
                    }
                }
            }
            $this->ajaxDie(json_encode($hookableModulesList));
        }
    }

    /**
     * Ajax process save hook
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function ajaxProcessSaveHook()
    {
        if ($this->tabAccess['edit'] === '1') {
            /* PrestaShop demo mode */
            if (_PS_MODE_DEMO_) {
                $this->ajaxDie('{"hasError" : true, "errors" : ["Live Edit: This functionality has been disabled."]}');
            }

            $hooksList = explode(',', Tools::getValue('hooks_list'));
            $idShop = (int) Tools::getValue('id_shop');
            if (!$idShop) {
                $idShop = $this->context->shop->id;
            }

            $res = true;
            $hooksList = Tools::getValue('hook');

            foreach ($hooksList as $idHook => $modules) {
                // 1st, drop all previous hooked modules
                $sql = 'DELETE FROM `'._DB_PREFIX_.'hook_module` WHERE `id_hook` =  '.(int) $idHook.' AND id_shop = '.(int) $idShop;
                $res &= Db::getInstance()->execute($sql);

                $i = 1;
                $value = '';
                $ids = [];
                // then prepare sql query to rehook all chosen modules(id_module, id_shop, id_hook, position)
                // position is i (autoincremented)
                if (is_array($modules) && count($modules)) {
                    foreach ($modules as $idModule) {
                        if ($idModule && !in_array($idModule, $ids)) {
                            $ids[] = (int) $idModule;
                            $value .= '('.(int) $idModule.', '.(int) $idShop.', '.(int) $idHook.', '.(int) $i.'),';
                        }
                        $i++;
                    }

                    if ($value) {
                        $value = rtrim($value, ',');
                        $res &= Db::getInstance()->execute('INSERT INTO  `'._DB_PREFIX_.'hook_module` (id_module, id_shop, id_hook, position) VALUES '.$value);
                    }
                }
            }
            if ($res) {
                $hasError = true;
            } else {
                $hasError = false;
            }

            $this->ajaxDie(json_encode([
                'hasError' => $hasError,
                'errors'   => '',
            ]));
        }
    }

    /**
     * Return a json array containing the possible hooks for a module.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function ajaxProcessGetPossibleHookingListForModule()
    {
        $moduleId = (int) Tools::getValue('module_id');
        if ($moduleId == 0) {
            $this->ajaxDie(json_encode(['hasError' => true, 'errors' => ['Wrong Module ID.']]));
        }

        $moduleInstance = Module::getInstanceById($moduleId);
        $this->ajaxDie(json_encode($moduleInstance->getPossibleHooksList()));
    }
}
