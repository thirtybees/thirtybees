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
 * Class AdminAccessControllerCore
 *
 * @property Profile|null $object
 */
class AdminAccessControllerCore extends AdminController
{
    const ADMIN_CONTROLLER_PERM_TYPE = 'admin_controller';

    /* @var array : Black list of id_tab that do not have access */
    public $accesses_black_list = [];

    /**
     * AdminAccessControllerCore constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->show_toolbar = false;
        $this->table = 'access';
        $this->className = 'Profile';
        $this->multishop_context = Shop::CONTEXT_ALL;
        $this->lang = false;

        // Blacklist AdminLogin
        $this->accesses_black_list[] = Tab::getIdFromClassName('AdminLogin');

        parent::__construct();
    }

    /**
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function initContent()
    {
        $this->display = 'edit';
        if (!$this->loadObject(true)) {
            return;
        }

        $this->initPageHeaderToolbar();

        $this->content .= $this->renderForm();
        $this->context->smarty->assign(
            [
                'content'                   => $this->content,
                'url_post'                  => static::$currentIndex.'&token='.$this->token,
                'show_page_header_toolbar'  => $this->show_page_header_toolbar,
                'page_header_toolbar_title' => $this->page_header_toolbar_title,
                'page_header_toolbar_btn'   => $this->page_header_toolbar_btn,
            ]
        );
    }

    /**
     * Post process callback
     *
     * @return bool
     * @throws PrestaShopException
     */
    public function postProcess()
    {
        if (Tools::isSubmit("submitSaveController")) {
            $this->saveAdminControllerPermissions();
            return true;
        } else {
            return parent::postProcess();
        }
    }

    /**
     * @throws PrestaShopException
     */
    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();
        unset($this->page_header_toolbar_btn['cancel']);
    }

    /**
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderForm()
    {
        $currentProfile = (int) $this->getCurrentProfileId();
        $profiles = Profile::getProfiles($this->context->language->id);
        $tabs = Tab::getTabs($this->context->language->id);
        $accesses = [];
        foreach ($profiles as $profile) {
            $accesses[$profile['id_profile']] = Profile::getProfileAccesses($profile['id_profile']);
        }

        // Deleted id_tab that do not have access
        foreach ($tabs as $key => $tab) {
            // Don't allow permissions for unnamed tabs (ie. AdminLogin)
            if (empty($tab['name'])) {
                unset($tabs[$key]);
            }

            foreach ($this->accesses_black_list as $idTab) {
                if ($tab['id_tab'] == (int) $idTab) {
                    unset($tabs[$key]);
                }
            }
        }

        $modules = [];
        foreach ($profiles as $profile) {
            $modules[$profile['id_profile']] = Db::readOnly()->getArray(
                '
				SELECT ma.`id_module`, m.`name`, ma.`view`, ma.`configure`, ma.`uninstall`
				FROM '._DB_PREFIX_.'module_access ma
				LEFT JOIN '._DB_PREFIX_.'module m
					ON ma.id_module = m.id_module
				WHERE id_profile = '.(int) $profile['id_profile'].'
				ORDER BY m.name
			'
            );
            foreach ($modules[$profile['id_profile']] as $k => &$module) {
                $m = Module::getInstanceById($module['id_module']);
                // the following condition handles invalid modules
                if ($m) {
                    $module['name'] = $m->displayName;
                } else {
                    unset($modules[$profile['id_profile']][$k]);
                }
            }

            uasort($modules[$profile['id_profile']], [$this, 'sortModuleByName']);
        }

        $this->fields_form = [''];
        $this->tpl_form_vars = [
            'profiles'            => $profiles,
            'accesses'            => $accesses,
            'id_tab_parentmodule' => (int) Tab::getIdFromClassName('AdminParentModules'),
            'id_tab_module'       => (int) Tab::getIdFromClassName('AdminModules'),
            'tabs'                => $tabs,
            'current_profile'     => (int) $currentProfile,
            'admin_profile'       => (int) _PS_ADMIN_PROFILE_,
            'access_edit'         => $this->hasEditPermission(),
            'perms'               => ['view', 'add', 'edit', 'delete'],
            'modules'             => $modules,
            'admin_controllers'   => $this->getAdminControllerAccess($profiles),
            'link'                => $this->context->link,
        ];

        return parent::renderForm();
    }

    /**
     * Get the current profile id
     *
     * @return int the $_GET['profile'] if valid, else 1 (the first profile id)
     */
    public function getCurrentProfileId()
    {
        return (isset($_GET['id_profile']) && !empty($_GET['id_profile']) && is_numeric($_GET['id_profile'])) ? (int) $_GET['id_profile'] : 1;
    }

    /**
     * @return void
     */
    public function initToolbarTitle()
    {
        $this->toolbar_title = array_unique($this->breadcrumbs);
    }

    /**
     * @throws PrestaShopException
     */
    public function ajaxProcessUpdateAccess()
    {
        if (_PS_MODE_DEMO_) {
            throw new PrestaShopException(Tools::displayError('This functionality has been disabled.'));
        }
        if (! $this->hasEditPermission()) {
            throw new PrestaShopException(Tools::displayError('You do not have permission to edit this.'));
        }

        if (Tools::isSubmit('submitAddAccess')) {
            $perm = Tools::getValue('perm');
            if (!in_array($perm, ['view', 'add', 'edit', 'delete', 'all'])) {
                throw new PrestaShopException('permission does not exist');
            }

            $enabled = Tools::getIntValue('enabled');
            $idTab = Tools::getIntValue('id_tab');
            $idProfile = Tools::getIntValue('id_profile');
            $where = '`id_tab`';
            $join = '';
            if (Tools::isSubmit('addFromParent')) {
                $where = 't.`id_parent`';
                $join = 'LEFT JOIN `'._DB_PREFIX_.'tab` t ON (t.`id_tab` = a.`id_tab`)';
            }

            if ($idTab == -1) {
                if ($perm == 'all') {
                    $sql = '
					UPDATE `'._DB_PREFIX_.'access` a
					SET `view` = '.(int) $enabled.', `add` = '.(int) $enabled.', `edit` = '.(int) $enabled.', `delete` = '.(int) $enabled.'
					WHERE `id_profile` = '.(int) $idProfile;
                } else {
                    $sql = '
					UPDATE `'._DB_PREFIX_.'access` a
					SET `'.bqSQL($perm).'` = '.(int) $enabled.'
					WHERE `id_profile` = '.(int) $idProfile;
                }
            } else {
                if ($perm == 'all') {
                    $sql = '
					UPDATE `'._DB_PREFIX_.'access` a '.$join.'
					SET `view` = '.(int) $enabled.', `add` = '.(int) $enabled.', `edit` = '.(int) $enabled.', `delete` = '.(int) $enabled.'
					WHERE '.$where.' = '.(int) $idTab.' AND `id_profile` = '.(int) $idProfile;
                } else {
                    $sql = '
					UPDATE `'._DB_PREFIX_.'access` a '.$join.'
					SET `'.bqSQL($perm).'` = '.(int) $enabled.'
					WHERE '.$where.' = '.(int) $idTab.' AND `id_profile` = '.(int) $idProfile;
                }
            }

            $res = Db::getInstance()->execute($sql) ? 'ok' : 'error';

            $this->ajaxDie($res);
        }
    }

    /**
     * @throws PrestaShopException
     */
    public function ajaxProcessUpdateModuleAccess()
    {
        if (_PS_MODE_DEMO_) {
            throw new PrestaShopException(Tools::displayError('This functionality has been disabled.'));
        }
        if (! $this->hasEditPermission()) {
            throw new PrestaShopException(Tools::displayError('You do not have permission to edit this.'));
        }

        if (Tools::isSubmit('changeModuleAccess')) {
            $perm = Tools::getValue('perm');
            $enabled = Tools::getIntValue('enabled');
            $idModule = Tools::getIntValue('id_module');
            $idProfile = Tools::getIntValue('id_profile');

            if (!in_array($perm, ['view', 'configure', 'uninstall'])) {
                throw new PrestaShopException('permission does not exist');
            }

            if ($idModule == -1) {
                $sql = '
					UPDATE `'._DB_PREFIX_.'module_access`
					SET `'.bqSQL($perm).'` = '.(int) $enabled.'
					WHERE `id_profile` = '.(int) $idProfile;
            } else {
                $sql = '
					UPDATE `'._DB_PREFIX_.'module_access`
					SET `'.bqSQL($perm).'` = '.(int) $enabled.'
					WHERE `id_module` = '.(int) $idModule.'
						AND `id_profile` = '.(int) $idProfile;
            }

            $res = Db::getInstance()->execute($sql) ? 'ok' : 'error';

            $this->ajaxDie($res);
        }
    }

    /**
     * @param array $a
     * @param array $b
     *
     * @return int
     */
    protected function sortModuleByName($a, $b)
    {
        return strnatcmp($a['name'], $b['name']);
    }

    /**
     * Return information about admin controllers custom permissions
     *
     * @param array $profiles
     * @return array
     * @throws PrestaShopException
     */
    protected function getAdminControllerAccess($profiles)
    {
        $controllersPermissions = AdminController::getControllersPermissions();
        foreach ($profiles as $profile) {
            $profileId = (int)$profile['id_profile'];
            $profileAccess = [];
            foreach ($controllersPermissions as $controller => $permission) {
                $permissions = [];
                foreach ($permission as $desc) {
                    $permission = $desc['permission'];
                    $level = Profile::getProfilePermission($profileId, $controller, $permission);
                    if ($level === false) {
                        $level = $desc['defaultLevel'];
                    }
                    $permissions[] = [
                        'key' => $permission,
                        'name' => $desc['name'],
                        'description' => $desc['description'],
                        'levels' => $desc['levels'],
                        'level' => $level
                    ];
                }

                $tab = Tab::getInstanceFromClassName($controller, $this->context->language->id);
                if (ValidateCore::isLoadedObject($tab)) {
                    $name = $tab->name;
                } else {
                    $name = $controller;
                }
                $profileAccess[] = [
                    'name' => $name,
                    'controller' => $controller,
                    'permissions' => $permissions,
                ];

            }
            $access[$profileId] = $profileAccess;
        }
        return $access;
    }

    /**
     * Saves controller permissions to the database
     *
     * @throws PrestaShopException
     */
    protected function saveAdminControllerPermissions()
    {
        $profileId = Tools::getIntValue('profileId');
        $controllerId = Tools::getValue('controllerId');
        $permissions = Tools::getValue('permissions');
        if ($profileId && $controllerId) {
            $connection = Db::getInstance();
            $controller = pSQL($controllerId);
            $connection->delete('profile_permission', "`id_profile` = $profileId AND `perm_group` = '$controller'");
            if ($permissions) {
                $data = [];
                foreach ($permissions as $permission => $level) {
                    $data[] = [
                        'id_profile' => $profileId,
                        'perm_type' => pSQL(static::ADMIN_CONTROLLER_PERM_TYPE),
                        'perm_group' => $controller,
                        'permission' => pSQL($permission),
                        'level' => pSQL($level)
                    ];
                }
                $connection->insert('profile_permission', $data);
            }
            Profile::invalidateCache($profileId);
        }
    }
}
