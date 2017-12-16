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
 * Class HelperCore
 *
 * @since 1.0.0
 */
class HelperCore
{
    // @codingStandardsIgnoreStart
    /** @var string */
    public $currentIndex;
    /** @var string $table */
    public $table = 'configuration';
    /** @var string $identifier */
    public $identifier;
    /** @var string $token */
    public $token;
    /** @var array $toolbar_btn */
    public $toolbar_btn;
    /** @var mixed $ps_help_context */
    public $ps_help_context;
    /** @var string $title */
    public $title;
    /** @var bool $show_toolbar */
    public $show_toolbar = true;
    /** @var Context $context */
    public $context;
    /** @var bool $toolbar_scroll */
    public $toolbar_scroll = false;
    /** @var bool $bootstrap */
    public $bootstrap = false;
    /** @var Module $module */
    public $module;
    /** @var string Helper tpl folder */
    public $base_folder;
    /** @var string Controller tpl folder */
    public $override_folder;
    /** @var string base template name */
    public $base_tpl = 'content.tpl';
    /** @var array $tpl_vars */
    public $tpl_vars = [];
    /** @var Smarty_Internal_Template base template object */
    protected $tpl;
    // @codingStandardsIgnoreEnd

    /**
     * HelperCore constructor.
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function __construct()
    {
        $this->context = Context::getContext();
    }

    /**
     * @deprecated 2.0.0
     *
     * @param array  $translations
     * @param array  $selectedCat
     * @param string $inputName
     * @param bool   $useRadio
     * @param bool   $useSearch
     * @param array  $disabledCategories
     * @param bool   $useInPopup
     *
     * @return string
     * @throws PrestaShopException
     */
    public static function renderAdminCategorieTree(
        $translations,
        $selectedCat = [],
        $inputName = 'categoryBox',
        $useRadio = false,
        $useSearch = false,
        $disabledCategories = [],
        $useInPopup = false
    ) {
        Tools::displayAsDeprecated();

        $helper = new Helper();
        if (isset($translations['Root'])) {
            $root = $translations['Root'];
        } elseif (isset($translations['Home'])) {
            $root = ['name' => $translations['Home'], 'id_category' => 1];
        } else {
            throw new PrestaShopException('Missing root category parameter.');
        }

        return $helper->renderCategoryTree($root, $selectedCat, $inputName, $useRadio, $useSearch, $disabledCategories);
    }

    /**
     *
     * @param array  $root        array with the name and ID of the tree root category, if null the Shop's root category will be used
     * @param array  $selectedCat array of selected categories
     * @param string $inputName   name of input
     * @param bool   $useRadio    use radio tree or checkbox tree
     * @param bool   $useSearch   display a find category search box
     * @param array  $disabledCategories
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function renderCategoryTree(
        $root = null,
        $selectedCat = [],
        $inputName = 'categoryBox',
        $useRadio = false,
        $useSearch = false,
        $disabledCategories = []
    ) {
        $translations = [
            'selected'     => $this->l('Selected'),
            'Collapse All' => $this->l('Collapse All'),
            'Expand All'   => $this->l('Expand All'),
            'Check All'    => $this->l('Check All'),
            'Uncheck All'  => $this->l('Uncheck All'),
            'search'       => $this->l('Find a category'),
        ];

        if (Tools::isSubmit('id_shop')) {
            $idShop = Tools::getValue('id_shop');
        } elseif (Context::getContext()->shop->id) {
            $idShop = Context::getContext()->shop->id;
        } elseif (!Shop::isFeatureActive()) {
            $idShop = Configuration::get('PS_SHOP_DEFAULT');
        } else {
            $idShop = 0;
        }
        $shop = new Shop($idShop);
        $rootCategory = Category::getRootCategory(null, $shop);
        $disabledCategories[] = (int) Configuration::get('PS_ROOT_CATEGORY');
        if (!$root) {
            $root = ['name' => $rootCategory->name, 'id_category' => $rootCategory->id];
        }

        if (!$useRadio) {
            $inputName = $inputName.'[]';
        }

        if ($useSearch) {
            $this->context->controller->addJs(_PS_JS_DIR_.'jquery/plugins/autocomplete/jquery.autocomplete.js');
        }

        $html = '
		<script type="text/javascript">
			var inputName = \''.addcslashes($inputName, '\'').'\';'."\n";
        if (count($selectedCat) > 0) {
            if (isset($selectedCat[0])) {
                $html .= '			var selectedCat = "'.implode(',', array_map('intval', $selectedCat)).'";'."\n";
            } else {
                $html .= '			var selectedCat = "'.implode(',', array_map('intval', array_keys($selectedCat))).'";'."\n";
            }
        } else {
            $html .= '			var selectedCat = \'\';'."\n";
        }
        $html .= '			var selectedLabel = \''.$translations['selected'].'\';
			var home = \''.addcslashes($root['name'], '\'').'\';
			var use_radio = '.(int) $useRadio.';';
        $html .= '</script>';

        $html .= '
		<div class="category-filter">
			<a class="btn btn-link" href="#" id="collapse_all"><i class="icon-collapse"></i> '.$translations['Collapse All'].'</a>
			<a class="btn btn-link" href="#" id="expand_all"><i class="icon-expand"></i> '.$translations['Expand All'].'</a>
			'.(!$useRadio ? '
				<a class="btn btn-link" href="#" id="check_all"><i class="icon-check"></i> '.$translations['Check All'].'</a>
				<a class="btn btn-link" href="#" id="uncheck_all"><i class="icon-check-empty"></i> '.$translations['Uncheck All'].'</a>' : '')
            .($useSearch ? '
				<div class="row">
					<label class="control-label col-lg-6" for="search_cat">'.$translations['search'].' :</label>
					<div class="col-lg-6">
						<input type="text" name="search_cat" id="search_cat"/>
					</div>
				</div>' : '')
            .'</div>';

        $homeIsSelected = false;
        if (is_array($selectedCat)) {
            foreach ($selectedCat as $cat) {
                if (is_array($cat)) {
                    $disabled = in_array($cat['id_category'], $disabledCategories);
                    if ($cat['id_category'] != $root['id_category']) {
                        $html .= '<input '.($disabled ? 'disabled="disabled"' : '').' type="hidden" name="'.$inputName.'" value="'.$cat['id_category'].'" >';
                    } else {
                        $homeIsSelected = true;
                    }
                } else {
                    $disabled = in_array($cat, $disabledCategories);
                    if ($cat != $root['id_category']) {
                        $html .= '<input '.($disabled ? 'disabled="disabled"' : '').' type="hidden" name="'.$inputName.'" value="'.$cat.'" >';
                    } else {
                        $homeIsSelected = true;
                    }
                }
            }
        }

        $rootInput = '';
        if ($root['id_category'] != (int) Configuration::get('PS_ROOT_CATEGORY') || (Tools::isSubmit('ajax') && Tools::getValue('action') == 'getCategoriesFromRootCategory')) {
            $rootInput = '
				<p class="checkbox"><i class="icon-folder-open"></i><label>
					<input type="'.(!$useRadio ? 'checkbox' : 'radio').'" name="'
                .$inputName.'" value="'.$root['id_category'].'" '
                .($homeIsSelected ? 'checked' : '').' onclick="clickOnCategoryBox($(this));" />'
                .$root['name'].
                '</label></p>';
        }
        $html .= '
			<div class="container">
				<div class="well">
					<ul id="categories-treeview">
						<li id="'.$root['id_category'].'" class="hasChildren">
							<span class="folder">'.$rootInput.' </span>
							<ul>
								<li><span class="placeholder">&nbsp;</span></li>
						  	</ul>
						</li>
					</ul>
				</div>
			</div>';

        if ($useSearch) {
            $html .= '<script type="text/javascript">searchCategory();</script>';
        }

        return $html;
    }

    /**
     * Render shop list
     *
     * @return string
     *
     * @deprecated deprecated since 1.0.0 use HelperShop->getRenderedShopList
     * @throws PrestaShopException
     */
    public static function renderShopList()
    {
        Tools::displayAsDeprecated();

        if (!Shop::isFeatureActive() || Shop::getTotalShops(false, null) < 2) {
            return null;
        }

        $tree = Shop::getTree();
        $context = Context::getContext();

        // Get default value
        $shopContext = Shop::getContext();
        if ($shopContext == Shop::CONTEXT_ALL || ($context->controller->multishop_context_group == false && $shopContext == Shop::CONTEXT_GROUP)) {
            $value = '';
        } elseif ($shopContext == Shop::CONTEXT_GROUP) {
            $value = 'g-'.Shop::getContextShopGroupID();
        } else {
            $value = 's-'.Shop::getContextShopID();
        }

        // Generate HTML
        $url = $_SERVER['REQUEST_URI'].(($_SERVER['QUERY_STRING']) ? '&' : '?').'setShopContext=';
        // $html = '<a href="#"><i class="icon-home"></i> '.$shop->name.'</a>';
        $html = '<select class="shopList" onchange="location.href = \''.htmlspecialchars($url).'\'+$(this).val();">';
        $html .= '<option value="" class="first">'.Translate::getAdminTranslation('All shops').'</option>';

        foreach ($tree as $groupId => $groupData) {
            if ((!isset($context->controller->multishop_context) || $context->controller->multishop_context & Shop::CONTEXT_GROUP)) {
                $html .= '<option class="group" value="g-'.$groupId.'"'.(((empty($value) && $shopContext == Shop::CONTEXT_GROUP) || $value == 'g-'.$groupId) ? ' selected="selected"' : '').($context->controller->multishop_context_group == false ? ' disabled="disabled"' : '').'>'.Translate::getAdminTranslation('Group:').' '.htmlspecialchars($groupData['name']).'</option>';
            } else {
                $html .= '<optgroup class="group" label="'.Translate::getAdminTranslation('Group:').' '.htmlspecialchars($groupData['name']).'"'.($context->controller->multishop_context_group == false ? ' disabled="disabled"' : '').'>';
            }
            if (!isset($context->controller->multishop_context) || $context->controller->multishop_context & Shop::CONTEXT_SHOP) {
                foreach ($groupData['shops'] as $shopId => $shopData) {
                    if ($shopData['active']) {
                        $html .= '<option value="s-'.$shopId.'" class="shop"'.(($value == 's-'.$shopId) ? ' selected="selected"' : '').'>'.($context->controller->multishop_context_group == false ? htmlspecialchars($groupData['name']).' - ' : '').$shopData['name'].'</option>';
                    }
                }
            }
            if (!(!isset($context->controller->multishop_context) || $context->controller->multishop_context & Shop::CONTEXT_GROUP)) {
                $html .= '</optgroup>';
            }
        }
        $html .= '</select>';

        return $html;
    }

    /**
     * @param string $tpl
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setTpl($tpl)
    {
        $this->tpl = $this->createTemplate($tpl);
    }

    /**
     * Create a template from the override file, else from the base file.
     *
     * @param string $tplName filename
     *
     * @return Smarty_Internal_Template|object
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function createTemplate($tplName)
    {
        if ($this->override_folder) {
            if ($this->context->controller instanceof ModuleAdminController) {
                $overrideTplPath = $this->context->controller->getTemplatePath($tplName).$this->override_folder.$this->base_folder.$tplName;
            } elseif ($this->module) {
                $overrideTplPath = _PS_MODULE_DIR_.$this->module->name.'/views/templates/admin/_configure/'.$this->override_folder.$this->base_folder.$tplName;
            } else {
                if (file_exists($this->context->smarty->getTemplateDir(1).$this->override_folder.$this->base_folder.$tplName)) {
                    $overrideTplPath = $this->context->smarty->getTemplateDir(1).$this->override_folder.$this->base_folder.$tplName;
                } elseif (file_exists($this->context->smarty->getTemplateDir(0).'controllers'.DIRECTORY_SEPARATOR.$this->override_folder.$this->base_folder.$tplName)) {
                    $overrideTplPath = $this->context->smarty->getTemplateDir(0).'controllers'.DIRECTORY_SEPARATOR.$this->override_folder.$this->base_folder.$tplName;
                }
            }
        } elseif ($this->module) {
            $overrideTplPath = _PS_MODULE_DIR_.$this->module->name.'/views/templates/admin/_configure/'.$this->base_folder.$tplName;
        }

        if (isset($overrideTplPath) && file_exists($overrideTplPath)) {
            return $this->context->smarty->createTemplate($overrideTplPath, $this->context->smarty);
        } else {
            return $this->context->smarty->createTemplate($this->base_folder.$tplName, $this->context->smarty);
        }
    }

    /**
     * default behaviour for helper is to return a tpl fetched
     *
     * @return string
     *
     * @throws Exception
     * @throws SmartyException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function generate()
    {
        $this->tpl->assign($this->tpl_vars);

        return $this->tpl->fetch();
    }

    /**
     * Render a form with potentials required fields
     *
     * @param string $className
     * @param string $identifier
     * @param array  $tableFields
     *
     * @return string
     *
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function renderRequiredFields($className, $identifier, $tableFields)
    {
        $rules = call_user_func_array([$className, 'getValidationRules'], [$className]);
        $requiredClassFields = [$identifier];
        foreach ($rules['required'] as $required) {
            $requiredClassFields[] = $required;
        }

        /** @var ObjectModel $object */
        $object = new $className();
        $res = $object->getFieldsRequiredDatabase();

        $requiredFields = [];
        foreach ($res as $row) {
            $requiredFields[(int) $row['id_required_field']] = $row['field_name'];
        }

        $this->tpl_vars = [
            'table_fields'          => $tableFields,
            'irow'                  => 0,
            'required_class_fields' => $requiredClassFields,
            'required_fields'       => $requiredFields,
            'current'               => $this->currentIndex,
            'token'                 => $this->token,
        ];

        $tpl = $this->createTemplate('helpers/required_fields.tpl');
        $tpl->assign($this->tpl_vars);

        return $tpl->fetch();
    }

    /**
     * @param array $modulesList
     *
     * @return mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function renderModulesList($modulesList)
    {
        $this->tpl_vars = [
            'modules_list' => $modulesList,
            'modules_uri'  => __PS_BASE_URI__.basename(_PS_MODULE_DIR_),
        ];
        // The translations for this are defined by AdminModules, so override the context for the translations
        $overrideControllerNameForTranslations = Context::getContext()->override_controller_name_for_translations;
        Context::getContext()->override_controller_name_for_translations = 'AdminModules';
        $tpl = $this->createTemplate('helpers/modules_list/list.tpl');
        $tpl->assign($this->tpl_vars);
        $html = $tpl->fetch();
        // Restore the previous context
        Context::getContext()->override_controller_name_for_translations = $overrideControllerNameForTranslations;

        return $html;
    }

    /**
     * use translations files to replace english expression.
     *
     * @param mixed  $string       term or expression in english
     * @param string $class
     * @param bool   $addslashes   if set to true, the return value will pass through addslashes(). Otherwise, stripslashes().
     * @param bool   $htmlentities if set to true(default), the return value will pass through htmlentities($string, ENT_QUOTES, 'utf-8')
     *
     * @return string the translation if available, or the english default text.
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    protected function l($string, $class = 'AdminTab', $addslashes = false, $htmlentities = true)
    {
        // if the class is extended by a module, use modules/[module_name]/xx.php lang file
        $currentClass = get_class($this);
        if (Module::getModuleNameFromClass($currentClass)) {
            return Translate::getModuleTranslation(Module::$classInModule[$currentClass], $string, $currentClass);
        }

        return Translate::getAdminTranslation($string, get_class($this), $addslashes, $htmlentities);
    }
}
