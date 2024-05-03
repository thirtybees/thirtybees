<?php
/**
 * 2007-2016 PrestaShop.
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2024 thirty bees
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
 * @copyright 2017-2024 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class FrontControllerCore.
 */
class FrontControllerCore extends Controller
{
    const JS_DEF_PLACEHOLDER = 'js_def';
    /**
     * True if controller has already been initialized.
     * Prevents initializing controller more than once.
     *
     * @var bool
     */
    public static $initialized = false;
    /**
     * @deprecated Deprecated shortcuts as of 1.1.0 - Use $context->smarty instead
     *
     * @var Smarty $smarty
     */
    protected static $smarty;
    /**
     * @deprecated Deprecated shortcuts as of 1.1.0 - Use $context->cookie instead
     *
     * @var Cookie $cookie
     */
    protected static $cookie;
    /**
     * @deprecated Deprecated shortcuts as of 1.1.0 - Use $context->link instead
     *
     * @var Link $link
     */
    protected static $link;
    /**
     * @deprecated Deprecated shortcuts as of 1.1.0 - Use $context->cart instead
     *
     * @var Cart $cart
     */
    protected static $cart;
    /**
     * @var int[] Holds current customer's groups.
     */
    protected static $currentCustomerGroups;

    /** @var string Language ISO code */
    public $iso;
    /** @var string ORDER BY field */
    public $orderBy;
    /** @var string Order way string ('ASC', 'DESC') */
    public $orderWay;
    /** @var int Current page number */
    public $p;
    /** @var int Items (products) per page */
    public $n;
    /** @var bool If set to true, will redirected user to login page during init function. */
    public $auth = false;
    /**
     * If set to true, user can be logged in as guest when checking if logged in.
     *
     * @see $auth
     *
     * @var bool
     */
    public $guestAllowed = false;
    /**
     * Route of PrestaShop page to redirect to after forced login.
     *
     * @see $auth
     *
     * @var bool
     */
    public $authRedirection = false;
    /** @var bool SSL connection flag */
    public $ssl = false;

    /** @var bool If false, does not build left page column content and hides it. */
    public $display_column_left = true;

    /** @var bool If false, does not build right page column content and hides it. */
    public $display_column_right = true;
    /** @var int */
    public $nb_items_per_page;

    /**
     * @var string|null Controller rewrite name
     */
    public $page_name = null;

    /** @var bool If true, switches display to restricted country page during init. */
    protected $restrictedCountry = false;
    /** @var bool If true, forces display to maintenance page. */
    protected $maintenance = false;

    /**
     * Controller constructor.
     *
     * @throws PrestaShopException
     *
     * @global bool $useSSL SSL connection flag
     */
    public function __construct()
    {
        $this->controller_type = 'front';

        global $useSSL;

        parent::__construct();

        if (Configuration::get('PS_SSL_ENABLED')) {
            $this->ssl = true;
        }

        if (isset($useSSL)) {
            $this->ssl = $useSSL;
        } else {
            $useSSL = $this->ssl;
        }

        if (isset($this->php_self) && is_object($this->context->theme)) {
            $columns = $this->context->theme->hasColumns($this->php_self);

            // Don't use theme tables if not configured in DB
            if ($columns) {
                $this->display_column_left = $columns['left_column'];
                $this->display_column_right = $columns['right_column'];
            }
        }
    }

    /**
     * Sets and returns customer groups that the current customer(visitor) belongs to.
     *
     * @return int[]
     *
     * @throws PrestaShopDatabaseException
     *
     * @throws PrestaShopException
     */
    public static function getCurrentCustomerGroups()
    {
        if (!Group::isFeatureActive()) {
            return [];
        }

        $context = Context::getContext();
        if (!isset($context->customer) || !$context->customer->id) {
            return [];
        }

        if (!is_array(static::$currentCustomerGroups)) {
            static::$currentCustomerGroups = [];
            $result = Db::readOnly()->getArray(
                (new DbQuery())
                    ->select('`id_group`')
                    ->from('customer_group')
                    ->where('`id_customer` = '.(int) $context->customer->id)
            );
            if ($result) {
                foreach ($result as $row) {
                    static::$currentCustomerGroups[] = (int)$row['id_group'];
                }
            }
        }

        return static::$currentCustomerGroups;
    }

    /**
     * Check if the controller is available for the current user/visitor.
     *
     * @see Controller::checkAccess()
     *
     * @return bool
     */
    public function checkAccess()
    {
        return true;
    }

    /**
     * Check if the current user/visitor has valid view permissions.
     *
     * @see Controller::viewAccess
     *
     * @return bool
     */
    public function viewAccess()
    {
        return true;
    }

    /**
     * Method that is executed after init() and checkAccess().
     * Used to process user input.
     *
     * @see Controller::run()
     */
    public function postProcess()
    {
    }

    /**
     * Starts the controller process
     *
     * Overrides Controller::run() to allow full page cache
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function run()
    {
        if (PageCache::isEnabled()) {
            $debug = Configuration::get('TB_PAGE_CACHE_DEBUG');
            $cacheEntry = PageCache::get();
            if ($cacheEntry->exists()) {
                if ($debug) {
                    header('X-thirtybees-PageCache: HIT');
                }

                $this->init();
                $this->context->cookie->write();
                $content = $cacheEntry->getFreshContent();

                echo $content;

                return;
            } else {
                if ($debug) {
                    header('X-thirtybees-PageCache: MISS');
                }
            }
        }

        parent::run();
    }

    /**
     * Initializes common front page content: header, footer and side columns.
     *
     * @throws PrestaShopException
     */
    public function initContent()
    {
        $this->process();

        if (!isset($this->context->cart)) {
            $this->context->cart = new Cart();
        }

        if (!$this->useMobileTheme()) {
            // These hooks aren't used for the mobile theme.
            // Needed hooks are called in the tpl files.

            $hookHeader = Hook::displayHook('displayHeader');

            $faviconTemplate = Configuration::get('TB_SOURCE_FAVICON_CODE') ?? '';
            if (!empty(trim($faviconTemplate))) {
                $faviconTemplate = preg_replace('/\<br(\s*)?\/?\>/i', "\n", $faviconTemplate);
                $dom = new DOMDocument();
                $dom->loadHTML($faviconTemplate);
                $links = [];
                foreach ($dom->getElementsByTagName('link') as $elem) {
                    $links[] = $elem;
                }
                foreach ($dom->getElementsByTagName('meta') as $elem) {
                    $links[] = $elem;
                }
                $faviconHtml = '';
                foreach ($links as $link) {
                    foreach ($link->attributes as $attribute) {
                        /** @var DOMElement $link */
                        if ($favicon = Tools::parseFaviconSizeTag(urldecode($attribute->value))) {
                            $attribute->value = Media::getMediaPath(_PS_IMG_DIR_."favicon/favicon_{$this->context->shop->id}_{$favicon['width']}_{$favicon['height']}.{$favicon['type']}");
                        }
                    }
                    $faviconHtml .= $dom->saveHTML($link);
                }
                if ($faviconHtml) {
                    $hookHeader .= $faviconHtml;
                }
                $hookHeader .= '<meta name="msapplication-config" content="'.Media::getMediaPath(_PS_IMG_DIR_."favicon/browserconfig_{$this->context->shop->id}.xml").'">';
                $hookHeader .= '<link rel="manifest" href="'.Media::getMediaPath(_PS_IMG_DIR_."favicon/manifest_{$this->context->shop->id}.json").'">';
            }

            if (Configuration::get('TB_EMIT_SEO_FIELDS')) {
                // append some seo fields, canonical, hrefLang, rel prev/next
                $hookHeader .= $this->getSeoFields();
            }

            // To be removed: append extra css and metas to the header hook
            $extraCode = Configuration::getMultiple([Configuration::CUSTOMCODE_METAS, Configuration::CUSTOMCODE_CSS]);
            $extraCss = $extraCode[Configuration::CUSTOMCODE_CSS] ? '<style>'.$extraCode[Configuration::CUSTOMCODE_CSS].'</style>' : '';
            $hookHeader .= $extraCode[Configuration::CUSTOMCODE_METAS].$extraCss;

            $this->context->smarty->assign(
                [
                    'HOOK_HEADER'       => $hookHeader,
                    'HOOK_TOP'          => Hook::displayHook('displayTop'),
                    'HOOK_LEFT_COLUMN'  => ($this->display_column_left ? Hook::displayHook('displayLeftColumn') : ''),
                    'HOOK_RIGHT_COLUMN' => ($this->display_column_right ? Hook::displayHook('displayRightColumn', ['cart' => $this->context->cart]) : ''),
                ]
            );
        } else {
            $this->context->smarty->assign('HOOK_MOBILE_HEADER', Hook::displayHook('displayMobileHeader'));
        }
    }

    /**
     * Called before compiling common page sections (header, footer, columns).
     * Good place to modify smarty variables.
     *
     * @see FrontController::initContent()
     */
    public function process()
    {
    }

    /**
     * Checks if mobile theme is active and in use.
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    protected function useMobileTheme()
    {
        static $useMobileTemplate = null;

        // The mobile theme must have a layout to be used
        if ($useMobileTemplate === null) {
            $useMobileTemplate = ($this->context->getMobileDevice() && file_exists(_PS_THEME_MOBILE_DIR_.'layout.tpl'));
        }

        return $useMobileTemplate;
    }

    /**
     * Generates html for additional seo tags.
     *
     * @return string html code for the new tags
     *
     * @throws PrestaShopException
     */
    public function getSeoFields()
    {
        $content = "\n";
        $canonicalUrl = $this->getCurrentPageCanonicalUrl();
        if ($canonicalUrl) {
            $content .= '<link rel="canonical" href="'.$canonicalUrl.'">'."\n";
        }
        foreach ($this->getCurrentPageHrefLangTags() as $lang) {
            $content .= $lang . "\n";
        }
        $relprevNext = $this->getCurrentPagePrevNextRelTags();
        if ($relprevNext) {
            $content .= $relprevNext . "\n";
        }
        return rtrim($content);
    }

    /**
     * creates hrefLang links for various entities.
     *
     * @param string $entity name of the object/page to get the link for
     * @param int $idItem eventual id of the object (if any)
     * @param array $languages list of languages
     * @param int $idLangDefault id of the default language
     *
     * @return string[] HTML of the hreflang tags
     *
     * @throws PrestaShopException
     */
    public function getHrefLang($entity, $idItem, $languages, $idLangDefault)
    {
        Tools::displayAsDeprecated();
        return $this->getCurrentPageHrefLangTags();
    }

    /**
     * Get rel prev/next tags for paginated pages.
     *
     * @param string $entity type of object
     * @param int $idItem id of he object
     *
     * @return string string containing the new tags
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getRelPrevNext($entity, $idItem)
    {
        switch ($entity) {
            case 'category':
                $category = new Category((int) $idItem);
                $nbProducts = $category->getProducts(null, null, null, null, null, true);
                break;
            case 'manufacturer':
                $manufacturer = new Manufacturer($idItem);
                $nbProducts = $manufacturer->getProducts($manufacturer->id, null, null, null, null, null, true);
                break;
            case 'supplier':
                $supplier = new Supplier($idItem);
                $nbProducts = $supplier->getProducts($supplier->id, null, null, null, null, null, true);
                break;
            default:
                return '';
        }

        $p = Tools::getIntValue('p');
        $n = (int) Configuration::get('PS_PRODUCTS_PER_PAGE');

        $totalPages = ceil($nbProducts / $n);

        $linkprev = '';
        $linknext = '';
        $requestPage = $this->context->link->getPaginationLink($entity, $idItem, $n, false, 1, false);
        if (!$p) {
            $p = 1;
        }

        if ($p > 1) { // we need prev
            $linkprev = $this->context->link->goPage($requestPage, $p - 1);
        }

        if ($totalPages > 1 && $p + 1 <= $totalPages) {
            $linknext = $this->context->link->goPage($requestPage, $p + 1);
        }

        $return = '';

        if ($linkprev) {
            $return .= '<link rel="prev" href="'.$linkprev.'">';
        }
        if ($linknext) {
            $return .= '<link rel="next" href="'.$linknext.'">';
        }

        return $return;
    }

    /**
     * Compiles and outputs page header section (including HTML <head>).
     *
     * @param bool $display If true, renders visual page header section
     *
     * @throws PrestaShopException
     * @throws SmartyException
     * @deprecated 2.0.0
     */
    public function displayHeader($display = true)
    {
        Tools::displayAsDeprecated();

        $this->initHeader();
        $hookHeader = Hook::displayHook('displayHeader');
        if ((Configuration::get('PS_CSS_THEME_CACHE') || Configuration::get('PS_JS_THEME_CACHE')) && is_writable(_PS_THEME_DIR_.'cache')) {
            // CSS compressor management
            if (Configuration::get('PS_CSS_THEME_CACHE')) {
                $this->css_files = Media::cccCss($this->css_files);
            }

            //JS compressor management
            if (Configuration::get('PS_JS_THEME_CACHE')) {
                $this->js_files = Media::cccJs($this->js_files);
            }
        }

        // Call hook before assign of css_files and js_files in order to include correctly all css and javascript files
        $this->context->smarty->assign(
            [
                'HOOK_HEADER'       => $hookHeader,
                'HOOK_TOP'          => Hook::displayHook('displayTop'),
                'HOOK_LEFT_COLUMN'  => ($this->display_column_left ? Hook::displayHook('displayLeftColumn') : ''),
                'HOOK_RIGHT_COLUMN' => ($this->display_column_right ? Hook::displayHook('displayRightColumn', ['cart' => $this->context->cart]) : ''),
                'HOOK_FOOTER'       => Hook::displayHook('displayFooter'),
            ]
        );

        $this->context->smarty->assign(
            [
                'css_files' => $this->css_files,
                'js_files'  => ($this->getLayout() && Configuration::get('PS_JS_DEFER')) ? [] : $this->js_files,
            ]
        );

        $this->display_header = $display;
        $this->smartyOutputContent(_PS_THEME_DIR_.'header.tpl');
    }

    /**
     * Initializes page header variables.
     *
     * @throws PrestaShopException
     */
    public function initHeader()
    {
        // Added powered by for builtwith.com
        header('Powered-By: thirty bees');
        // Hooks are voluntary out the initialize array (need those variables already assigned)
        $this->context->smarty->assign(
            [
                'time'                  => time(),
                'img_update_time'       => Configuration::get('PS_IMG_UPDATE_TIME'),
                'static_token'          => Tools::getToken(false),
                'token'                 => Tools::getToken(),
                'priceDisplayPrecision' => Context::getContext()->currency->getDisplayPrecision(),
                'content_only'          => Tools::getIntValue('content_only'),
            ]
        );

        $this->context->smarty->assign($this->initLogoAndFavicon());
    }

    /**
     * Returns logo and favicon variables, depending
     * on active theme type (regular or mobile).
     *
     * @return array
     *
     * @throws PrestaShopException
     */
    public function initLogoAndFavicon()
    {
        $mobileDevice = $this->context->getMobileDevice();

        if ($mobileDevice && Configuration::get('PS_LOGO_MOBILE')) {
            $logo = $this->context->link->getMediaLink(_PS_IMG_.Configuration::get('PS_LOGO_MOBILE').'?'.Configuration::get('PS_IMG_UPDATE_TIME'));
        } else {
            $logo = $this->context->link->getMediaLink(_PS_IMG_.Configuration::get('PS_LOGO'));
        }

        return [
            'favicon_url'       => _PS_IMG_.Configuration::get('PS_FAVICON'),
            'logo_image_width'  => ($mobileDevice == false ? Configuration::get('SHOP_LOGO_WIDTH') : Configuration::get('SHOP_LOGO_MOBILE_WIDTH')),
            'logo_image_height' => ($mobileDevice == false ? Configuration::get('SHOP_LOGO_HEIGHT') : Configuration::get('SHOP_LOGO_MOBILE_HEIGHT')),
            'logo_url'          => $logo,
        ];
    }

    /**
     * Returns the layout corresponding to the current page by using the override system
     * Ex:
     * On the url: http://localhost/index.php?id_product=1&controller=product, this method will
     * check if the layout exists in the following files (in that order), and return the first found:
     * - /themes/default/override/layout-product-1.tpl
     * - /themes/default/override/layout-product.tpl
     * - /themes/default/layout.tpl.
     *
     * @return bool|string
     *
     * @throws PrestaShopException
     */
    public function getLayout()
    {
        $entity = $this->php_self;
        $idItem = Tools::getIntValue('id_'.$entity);

        $layoutDir = $this->getThemeDir();
        $layoutOverrideDir = $this->getOverrideThemeDir();

        $layout = false;
        if ($entity) {
            if ($idItem > 0 && file_exists($layoutOverrideDir.'layout-'.$entity.'-'.$idItem.'.tpl')) {
                $layout = $layoutOverrideDir.'layout-'.$entity.'-'.$idItem.'.tpl';
            } elseif (file_exists($layoutOverrideDir.'layout-'.$entity.'.tpl')) {
                $layout = $layoutOverrideDir.'layout-'.$entity.'.tpl';
            }
        }

        if (!$layout && file_exists($layoutDir.'layout.tpl')) {
            $layout = $layoutDir.'layout.tpl';
        }

        return $layout;
    }

    /**
     * Returns theme directory (regular or mobile).
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    protected function getThemeDir()
    {
        return $this->useMobileTheme() ? _PS_THEME_MOBILE_DIR_ : _PS_THEME_DIR_;
    }

    /**
     * Returns theme override directory (regular or mobile).
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    protected function getOverrideThemeDir()
    {
        return $this->useMobileTheme() ? _PS_THEME_MOBILE_OVERRIDE_DIR_ : _PS_THEME_OVERRIDE_DIR_;
    }

    /**
     * Renders controller templates and generates page content.
     *
     * @param array|string $content Template file(s) to be rendered
     *
     * @throws SmartyException
     * @throws PrestaShopException
     */
    protected function smartyOutputContent($content)
    {
        if (PageCache::isEnabled()) {
            $html = $this->getSmartyOutputContent($content);
            PageCache::set($html);
            echo $html;
        } else {
            parent::smartyOutputContent($content);
        }
    }

    /**
     * Generates page content for controller templates
     *
     * @param array|string $content
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function getSmartyOutputContent($content): string
    {
        $this->context->smarty->assign('js_def', static::JS_DEF_PLACEHOLDER);
        $html = parent::getSmartyOutputContent($content);
        if ($html && $this->getLayout()) {
            $liveEditContent = '';
            if (!$this->useMobileTheme() && $this->checkLiveEditAccess()) {
                $liveEditContent = $this->getLiveEditFooter();
            }
            $domAvailable = extension_loaded('dom') ? true : false;
            $defer = (bool)Configuration::get('PS_JS_DEFER');
            if ($defer && $domAvailable) {
                $html = Media::deferInlineScripts($html);
            }
            $html = trim(str_replace(['</body>', '</html>'], '', $html)) . "\n";
            $this->context->smarty->assign(
                [
                    'js_def' => Media::getJsDef(),
                    'js_files' => $defer ? array_unique($this->js_files) : [],
                    'js_inline' => ($defer && $domAvailable) ? Media::getInlineScript() : [],
                ]
            );
            $javascript = $this->context->smarty->fetch(_PS_ALL_THEMES_DIR_ . 'javascript.tpl');
            if ($defer && (!isset($this->ajax) || !$this->ajax)) {
                $html .= $javascript;
            } else {
                $html = preg_replace('/(?<!\$)'.static::JS_DEF_PLACEHOLDER.'/', $javascript, $html);
            }

            $html .= $liveEditContent . ((!isset($this->ajax) || !$this->ajax) ? '</body></html>' : '');
        }
        return $html;
    }


    /**
     * Compiles and outputs page footer section.
     *
     * @param bool $display
     *
     * @throws SmartyException
     * @throws PrestaShopException
     * @deprecated 2.0.0
     */
    public function displayFooter($display = true)
    {
        Tools::displayAsDeprecated();
        $this->smartyOutputContent(_PS_THEME_DIR_.'footer.tpl');
    }

    /**
     * Renders and outputs maintenance page and ends controller process.
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function initCursedPage()
    {
        $this->displayMaintenancePage();
    }

    /**
     * Displays maintenance page if shop is closed.
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function displayMaintenancePage()
    {
        if ($this->maintenance == true || !(int) Configuration::get('PS_SHOP_ENABLE')) {
            $this->maintenance = true;
            $isCLI = Tools::isPHPCLI();
            $excludedIP = in_array(Tools::getRemoteAddr(), explode(',', (string)Configuration::get('PS_MAINTENANCE_IP')));
            // don't show maintenance page to excluded IP addresses, or to CLI scripts
            if (!$isCLI && !$excludedIP) {
                header('HTTP/1.1 503 temporarily overloaded');

                $this->context->smarty->assign($this->initLogoAndFavicon());
                $this->context->smarty->assign(
                    [
                        'HOOK_MAINTENANCE' => Hook::displayHook('displayMaintenance'),
                    ]
                );

                // If the controller is a module, then getTemplatePath will try to find the template in the modules, so we need to instanciate a real frontcontroller
                $frontController = preg_match('/ModuleFrontController$/', get_class($this)) ? new FrontController() : $this;
                $this->smartyOutputContent($frontController->getTemplatePath($this->getThemeDir().'maintenance.tpl'));
                exit;
            }
        }
    }

    /**
     * Returns template path.
     *
     * @param string $template
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public function getTemplatePath($template)
    {
        if (!$this->useMobileTheme()) {
            return $template;
        }

        $tplFile = basename($template);
        $dirname = dirname($template).(substr(dirname($template), -1, 1) == '/' ? '' : '/');

        if ($dirname == _PS_THEME_DIR_) {
            if (file_exists(_PS_THEME_MOBILE_DIR_.$tplFile)) {
                $template = _PS_THEME_MOBILE_DIR_.$tplFile;
            }
        } elseif ($dirname == _PS_THEME_MOBILE_DIR_) {
            if (!file_exists(_PS_THEME_MOBILE_DIR_.$tplFile) && file_exists(_PS_THEME_DIR_.$tplFile)) {
                $template = _PS_THEME_DIR_.$tplFile;
            }
        }

        return $template;
    }

    /**
     * Compiles and outputs full page content.
     *
     * @return bool
     *
     * @throws SmartyException
     * @throws PrestaShopException
     */
    public function display()
    {
        Tools::safePostVars();

        // assign css_files and js_files at the very last time
        if ((Configuration::get('PS_CSS_THEME_CACHE') || Configuration::get('PS_JS_THEME_CACHE')) && is_writable(_PS_THEME_DIR_.'cache')) {
            // CSS compressor management
            if (Configuration::get('PS_CSS_THEME_CACHE')) {
                $this->css_files = Media::cccCss($this->css_files);
            }
            //JS compressor management
            if (Configuration::get('PS_JS_THEME_CACHE') && !$this->useMobileTheme()) {
                $this->js_files = Media::cccJs($this->js_files);
            }
        }

        $this->context->smarty->assign(
            [
                'css_files'      => $this->css_files,
                'js_files'       => ($this->getLayout() && Configuration::get('PS_JS_DEFER')) ? [] : $this->js_files,
                'js_defer'       => (bool) Configuration::get('PS_JS_DEFER'),
                'errors'         => $this->errors,
                'display_header' => $this->display_header,
                'display_footer' => $this->display_footer,
                'img_formats'    => ['webp' => 'image/webp', 'jpg' => 'image/jpeg']
            ]
        );

        $layout = $this->getLayout();
        if ($layout) {
            if ($this->template) {
                $template = $this->context->smarty->fetch($this->template);
            } else {
                // For retrocompatibility with 1.4 controller

                ob_start();
                $this->displayContent();
                $template = ob_get_contents();
                ob_clean();
            }
            $this->context->smarty->assign('template', $template);
            $this->smartyOutputContent($layout);
        } else {
            Tools::displayAsDeprecated('layout.tpl is missing in your theme directory');
            if ($this->display_header) {
                $this->smartyOutputContent(_PS_THEME_DIR_.'header.tpl');
            }

            if ($this->template) {
                $this->smartyOutputContent($this->template);
            } else { // For retrocompatibility with 1.4 controller
                $this->displayContent();
            }

            if ($this->display_footer) {
                $this->smartyOutputContent(_PS_THEME_DIR_.'footer.tpl');
            }
        }

        return true;
    }

    /**
     * Renders page content.
     * Used for retrocompatibility with PS 1.4.
     *
     * @return void
     */
    public function displayContent()
    {
    }

    /**
     * Sets controller CSS and JS files.
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function setMedia()
    {
        /*
         * If website is accessed by mobile device
         * @see FrontControllerCore::setMobileMedia()
         */
        if ($this->useMobileTheme()) {
            $this->setMobileMedia();
            return;
        }

        $this->addCSS(_THEME_CSS_DIR_.'grid_prestashop.css', 'all');  // retro compat themes 1.5.0.1
        $this->addCSS(_THEME_CSS_DIR_.'global.css', 'all');
        $this->addJquery();
        $this->addJqueryPlugin('easing');
        $this->addJS(_PS_JS_DIR_.'tools.js');
        $this->addJS(_THEME_JS_DIR_.'global.js');
        Media::addJsDef(['currencyFormatters' => Currency::getJavascriptFormatters()]);
        // @since 1.0.4
        Media::addJsDef([
            'useLazyLoad' => (bool) Configuration::get('TB_LAZY_LOAD'),
            'useWebp'     => ImageManager::webpSupport(),
        ]);

        // Automatically add js files from js/autoload directory in the template
        $autoloadDirJs = $this->getThemeDir() . 'js/autoload/';
        if (file_exists($autoloadDirJs) && is_dir($autoloadDirJs)) {
            foreach (scandir($autoloadDirJs) as $file) {
                if (preg_match('/^[^.].*\.js$/', $file)) {
                    $this->addJS($autoloadDirJs.$file);
                }
            }
        }

        // Automatically add css files from css/autoload directory in the template
        $autoloadDirCss = $this->getThemeDir() . 'css/autoload/';
        if (file_exists($autoloadDirCss) && is_dir($autoloadDirCss)) {
            foreach (scandir($autoloadDirCss) as $file) {
                if (preg_match('/^[^.].*\.css$/', $file)) {
                    $this->addCSS($autoloadDirCss.$file);
                }
            }
        }

        if (Tools::isSubmit('live_edit') && Tools::getValue('ad') && Tools::getAdminToken('AdminModulesPositions'.(int) Tab::getIdFromClassName('AdminModulesPositions').Tools::getIntValue('id_employee'))) {
            $this->addJqueryUI('ui.sortable');
            $this->addjqueryPlugin('fancybox');
            $this->addJS(_PS_JS_DIR_.'hookLiveEdit.js');
        }

        if (Configuration::get('PS_QUICK_VIEW')) {
            $this->addjqueryPlugin('fancybox');
        }

        if (Configuration::get('PS_COMPARATOR_MAX_ITEM') > 0) {
            $this->addJS(_THEME_JS_DIR_.'products-comparison.js');
        }

        // Execute Hook FrontController SetMedia
        Hook::triggerEvent('actionFrontControllerSetMedia', []);

        $this->addSyntheticSchedulerJs();
    }

    /**
     * Specific medias for mobile device.
     * If autoload directory is present in the mobile theme, these files will not be loaded.
     *
     * @throws PrestaShopException
     */
    public function setMobileMedia()
    {
        $this->addJquery();

        if (!file_exists($this->getThemeDir().'js/autoload/')) {
            $this->addJS(_THEME_MOBILE_JS_DIR_.'jquery.mobile-1.3.0.min.js');
            $this->addJS(_THEME_MOBILE_JS_DIR_.'jqm-docs.js');
            $this->addJS(_PS_JS_DIR_.'tools.js');
            $this->addJS(_THEME_MOBILE_JS_DIR_.'global.js');
            $this->addJqueryPlugin('fancybox');
        }

        if (!file_exists($this->getThemeDir().'css/autoload/')) {
            $this->addCSS(_THEME_MOBILE_CSS_DIR_.'jquery.mobile-1.3.0.min.css', 'all');
            $this->addCSS(_THEME_MOBILE_CSS_DIR_.'jqm-docs.css', 'all');
            $this->addCSS(_THEME_MOBILE_CSS_DIR_.'global.css', 'all');
        }
    }

    /**
     * Add one or several JS files for front, checking if js files are overridden in theme/js/modules/ directory.
     *
     * @see Controller::addJS()
     *
     * @param array|string $jsUri Path to file, or an array of paths
     * @param bool $checkPath If true, checks if files exists
     *
     * @return bool
     */
    public function addJS($jsUri, $checkPath = true)
    {
        return $this->addMedia($jsUri, null, null, false, $checkPath);
    }

    /**
     * Adds a media file(s) (CSS, JS) to page header.
     *
     * @param string|array $mediaUri Path to file, or an array of paths like: array(array(uri => media_type), ...)
     * @param string|null $cssMediaType CSS media type
     * @param int|null $offset
     * @param bool $remove If True, removes media files
     * @param bool $checkPath If true, checks if files exists
     *
     * @return bool
     */
    public function addMedia($mediaUri, $cssMediaType = null, $offset = null, $remove = false, $checkPath = true)
    {
        if (!is_array($mediaUri)) {
            if ($cssMediaType) {
                $mediaUri = [$mediaUri => $cssMediaType];
            } else {
                $mediaUri = [$mediaUri];
            }
        }

        $listUri = [];
        foreach ($mediaUri as $file => $media) {
            if (!Validate::isAbsoluteUrl($media)) {
                $different = 0;
                $differentCss = 0;
                $type = 'css';
                if (!$cssMediaType) {
                    $type = 'js';
                    $file = $media;
                }
                if (strpos($file, __PS_BASE_URI__.'modules/') === 0) {
                    $overridePath = str_replace(__PS_BASE_URI__.'modules/', _PS_ROOT_DIR_.'/themes/'._THEME_NAME_.'/'.$type.'/modules/', $file, $different);
                    if (strrpos($overridePath, $type.'/'.basename($file)) !== false) {
                        $overridePathCss = str_replace($type.'/'.basename($file), basename($file), $overridePath, $differentCss);
                    }

                    if ($different && file_exists($overridePath)) {
                        $file = str_replace(__PS_BASE_URI__.'modules/', __PS_BASE_URI__.'themes/'._THEME_NAME_.'/'.$type.'/modules/', $file, $different);
                    } elseif ($differentCss && isset($overridePathCss) && file_exists($overridePathCss)) {
                        $file = $overridePathCss;
                    }
                    if ($cssMediaType) {
                        $listUri[$file] = $media;
                    } else {
                        $listUri[] = $file;
                    }
                } else {
                    $listUri[$file] = $media;
                }
            } else {
                $listUri[$file] = $media;
            }
        }

        if ($remove) {
            if ($cssMediaType) {
                parent::removeCSS($listUri, $cssMediaType);

                return true;
            }
            parent::removeJS($listUri);

            return true;
        }

        if ($cssMediaType) {
            parent::addCSS($listUri, $cssMediaType, $offset, $checkPath);

            return true;
        }

        parent::addJS($listUri, $checkPath);

        return true;
    }

    /**
     * Add one or several CSS for front, checking if css files are overridden in theme/css/modules/ directory.
     *
     * @see Controller::addCSS()
     *
     * @param array|string $cssUri Path to file, or an array of paths like: array(array(uri => media_type), ...)
     * @param string $cssMediaType CSS media type
     * @param int|null $offset
     * @param bool $checkPath If true, checks if files exists
     *
     * @return bool
     */
    public function addCSS($cssUri, $cssMediaType = 'all', $offset = null, $checkPath = true)
    {
        return $this->addMedia($cssUri, $cssMediaType, null, false, $checkPath);
    }

    /**
     * Initializes page footer variables.
     *
     * @throws PrestaShopException
     */
    public function initFooter()
    {
        $hookFooter = Hook::displayHook('displayFooter');

        $extraJs = Configuration::get(Configuration::CUSTOMCODE_JS);
        $extraJsConf = '';
        if (isset($this->php_self) && $this->php_self == 'order-confirmation') {
            $extraJsConf = Configuration::get(Configuration::CUSTOMCODE_ORDERCONF_JS);
        }

        if ($extraJs) {
            $hookFooter .= '<script type="text/javascript">'.$extraJs.'</script>';
        }
        if ($extraJsConf) {
            $hookFooter .= '<script type="text/javascript">'.$extraJsConf.'</script>';
        }

        $this->context->smarty->assign(
            [
                'HOOK_FOOTER'            => $hookFooter,
                'conditions'             => Configuration::get(Configuration::CONDITIONS),
                'id_cgv'                 => Configuration::get(Configuration::CONDITIONS_CMS_ID),
                'PS_SHOP_NAME'           => Configuration::get(Configuration::SHOP_NAME),
                'PS_ALLOW_MOBILE_DEVICE' => Context::getContext()->theme->supportsMobileVariant(),
            ]
        );

        /*
         * RTL support
         * rtl.css overrides theme css files for RTL
         * iso_code.css overrides default font for every language (optional)
         */
        if ($this->context->language->is_rtl) {
            $this->addCSS(_THEME_CSS_DIR_.'rtl.css');
            $this->addCSS(_THEME_CSS_DIR_.$this->context->language->iso_code.'.css');
        }
    }

    /**
     * Renders Live Edit widget.
     *
     * @return string HTML
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function getLiveEditFooter()
    {
        if ($this->checkLiveEditAccess()) {
            $data = $this->context->smarty->createData();
            $data->assign(
                [
                    'ad'        => Tools::getValue('ad'),
                    'live_edit' => true,
                    'hook_list' => Hook::$executed_hooks,
                    'id_shop'   => $this->context->shop->id,
                ]
            );

            return $this->context->smarty->createTemplate(_PS_ALL_THEMES_DIR_.'live_edit.tpl', $data)->fetch();
        } else {
            return '';
        }
    }

    /**
     * Checks if the user can use Live Edit feature.
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function checkLiveEditAccess()
    {
        if (!Tools::isSubmit('live_edit') || !Tools::getValue('ad') || !Tools::getValue('liveToken')) {
            return false;
        }

        if (Tools::getValue('liveToken') != Tools::getAdminToken('AdminModulesPositions'.(int) Tab::getIdFromClassName('AdminModulesPositions').Tools::getIntValue('id_employee'))) {
            return false;
        }

        return is_dir(_PS_CORE_DIR_.DIRECTORY_SEPARATOR.Tools::getValue('ad'));
    }

    /**
     * Assigns product list page sorting variables.
     *
     * @throws PrestaShopException
     */
    public function productSort()
    {
        // $this->orderBy = Tools::getProductsOrder('by', Tools::getValue('orderby'));
        // $this->orderWay = Tools::getProductsOrder('way', Tools::getValue('orderway'));
        // 'orderbydefault' => Tools::getProductsOrder('by'),
        // 'orderwayposition' => Tools::getProductsOrder('way'), // Deprecated: orderwayposition
        // 'orderwaydefault' => Tools::getProductsOrder('way'),

        $stockManagement = Configuration::get('PS_STOCK_MANAGEMENT') ? true : false; // no display quantity order if stock management disabled
        $orderByValues = [0 => 'name', 1 => 'price', 2 => 'date_add', 3 => 'date_upd', 4 => 'position', 5 => 'manufacturer_name', 6 => 'quantity', 7 => 'reference'];
        $orderWayValues = [0 => 'asc', 1 => 'desc'];

        $this->orderBy = mb_strtolower(Tools::getValue('orderby', $orderByValues[(int) Configuration::get('PS_PRODUCTS_ORDER_BY')]));
        $this->orderWay = mb_strtolower(Tools::getValue('orderway', $orderWayValues[(int) Configuration::get('PS_PRODUCTS_ORDER_WAY')]));

        if (!in_array($this->orderBy, $orderByValues)) {
            $this->orderBy = $orderByValues[0];
        }

        if (!in_array($this->orderWay, $orderWayValues)) {
            $this->orderWay = $orderWayValues[0];
        }

        $this->context->smarty->assign(
            [
                'orderby'          => $this->orderBy,
                'orderway'         => $this->orderWay,
                'orderbydefault'   => $orderByValues[(int) Configuration::get('PS_PRODUCTS_ORDER_BY')],
                'orderwayposition' => $orderWayValues[(int) Configuration::get('PS_PRODUCTS_ORDER_WAY')], // Deprecated: orderwayposition
                'orderwaydefault'  => $orderWayValues[(int) Configuration::get('PS_PRODUCTS_ORDER_WAY')],
                'stock_management' => (int) $stockManagement,
            ]
        );
    }

    /**
     * Assigns product list page pagination variables.
     *
     * @param int|null $totalProducts
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function pagination($totalProducts = null)
    {
        if (!static::$initialized) {
            $this->init();
        }

        // Retrieve the default number of products per page and the other available selections
        $defaultProductsPerPage = max(1, (int) Configuration::get('PS_PRODUCTS_PER_PAGE'));
        $nArray = [$defaultProductsPerPage, $defaultProductsPerPage * 2, $defaultProductsPerPage * 5];

        if (Tools::getIntValue('n') && (int) $totalProducts > 0) {
            $nArray[] = $totalProducts;
        }
        // Retrieve the current number of products per page (either the default, the GET parameter or the one in the cookie)
        $this->n = $defaultProductsPerPage;
        if (isset($this->context->cookie->nb_item_per_page) && in_array($this->context->cookie->nb_item_per_page, $nArray)) {
            $this->n = (int) $this->context->cookie->nb_item_per_page;
        }

        if (Tools::getIntValue('n') && in_array(Tools::getIntValue('n'), $nArray)) {
            $this->n = Tools::getIntValue('n');
        }

        // Retrieve the page number (either the GET parameter or the first page)
        $this->p = Tools::getIntValue('p', 1);
        // If the parameter is not correct then redirect (do not merge with the previous line, the redirect is required in order to avoid duplicate content)
        if (!is_numeric($this->p) || $this->p < 1) {
            Tools::redirect($this->context->link->getPaginationLink(false, false, $this->n, false, 1, false));
        }

        // Remove the page parameter in order to get a clean URL for the pagination template
        $currentUrl = preg_replace('/(?:(\?)|&amp;)p=\d+/', '$1', Tools::htmlentitiesUTF8($_SERVER['REQUEST_URI']));

        if ($this->n != $defaultProductsPerPage || isset($this->context->cookie->nb_item_per_page)) {
            $this->context->cookie->nb_item_per_page = $this->n;
        }

        $pagesNb = ceil($totalProducts / (int) $this->n);
        if ($this->p > $pagesNb && $totalProducts != 0) {
            Tools::redirect($this->context->link->getPaginationLink(false, false, $this->n, false, $pagesNb, false));
        }

        $range = 2; /* how many pages around page selected */
        $start = (int) ($this->p - $range);
        if ($start < 1) {
            $start = 1;
        }

        $stop = (int) ($this->p + $range);
        if ($stop > $pagesNb) {
            $stop = (int) $pagesNb;
        }

        $this->context->smarty->assign(
            [
                'nb_products'       => $totalProducts,
                'products_per_page' => $this->n,
                'pages_nb'          => $pagesNb,
                'p'                 => $this->p,
                'n'                 => $this->n,
                'nArray'            => $nArray,
                'range'             => $range,
                'start'             => $start,
                'stop'              => $stop,
                'current_url'       => $currentUrl,
            ]
        );
    }

    /**
     * Initializes front controller: sets smarty variables,
     * class properties, redirects depending on context, etc.
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     * @global bool $useSSL SSL connection flag
     * @global Cookie $cookie Visitor's cookie
     * @global Smarty $smarty
     * @global Cart $cart Visitor's cart
     * @global string $iso Language ISO
     * @global Country $defaultCountry Visitor's country object
     * @global string $protocol_link
     * @global string $protocol_content
     * @global Link $link
     * @global array $css_files
     * @global array $js_files
     * @global Currency $currency Visitor's selected currency
     */
    public function init()
    {
        /*
         * Globals are DEPRECATED as of version 1.5.0.1
         * Use the Context object to access objects instead.
         * Example: $this->context->cart
         */
        global $useSSL, $cookie, $smarty, $cart, $iso, $defaultCountry, $protocolLink, $protocolContent, $link, $cssFiles, $jsFiles, $currency;

        if (static::$initialized) {
            return;
        }

        static::$initialized = true;

        parent::init();

        // If current URL use SSL, set it true (used a lot for module redirect)
        if (Tools::usingSecureMode()) {
            $useSSL = true;
        }

        // For compatibility with globals, DEPRECATED as of version 1.5.0.1
        $cssFiles = $this->css_files;
        $jsFiles = $this->js_files;

        $this->sslRedirection();

        if ($this->ajax) {
            $this->display_header = false;
            $this->display_footer = false;
        }

        // If account created with the 2 steps register process, remove 'account_created' from cookie
        if (isset($this->context->cookie->account_created)) {
            $this->context->smarty->assign('account_created', 1);
            unset($this->context->cookie->account_created);
        }

        ob_start();

        // Init cookie language
        // @TODO This method must be moved into switchLanguage
        Tools::setCookieLanguage($this->context->cookie);

        $protocolLink = (Configuration::get('PS_SSL_ENABLED') || Tools::usingSecureMode()) ? 'https://' : 'http://';
        $useSSL = ((isset($this->ssl) && $this->ssl && Configuration::get('PS_SSL_ENABLED')) || Tools::usingSecureMode()) ? true : false;
        $protocolContent = ($useSSL) ? 'https://' : 'http://';
        $link = new Link($protocolLink, $protocolContent);
        $this->context->link = $link;

        if ($idCart = (int) $this->recoverCart()) {
            $this->context->cookie->id_cart = (int) $idCart;
        }

        if ($this->auth && !$this->context->customer->isLogged($this->guestAllowed)) {
            Tools::redirect('index.php?controller=authentication'.($this->authRedirection ? '&back='.$this->authRedirection : ''));
        }

        /* Theme is missing */
        if (!is_dir(_PS_THEME_DIR_)) {
            throw new PrestaShopException((sprintf(Tools::displayError('Current theme unavailable "%s". Please check your theme directory name and permissions.'), basename(rtrim(_PS_THEME_DIR_, '/\\')))));
        }

        if (Configuration::get('PS_GEOLOCATION_ENABLED')) {
            if (($newDefault = $this->geolocationManagement($this->context->country)) && Validate::isLoadedObject($newDefault)) {
                $this->context->country = $newDefault;
            }
        } elseif (Configuration::get('PS_DETECT_COUNTRY')) {
            $hasCurrency = isset($this->context->cookie->id_currency) && (int) $this->context->cookie->id_currency;
            $hasCountry = isset($this->context->cookie->iso_code_country) && $this->context->cookie->iso_code_country;
            $hasAddressType = false;

            if ((int) $this->context->cookie->id_cart && ($cart = new Cart($this->context->cookie->id_cart)) && Validate::isLoadedObject($cart)) {
                $hasAddressType = isset($cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')}) && $cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')};
            }

            if ((!$hasCurrency || $hasCountry) && !$hasAddressType) {
                $idCountry = $hasCountry && !Validate::isLanguageIsoCode($this->context->cookie->iso_code_country) ?
                    (int) Country::getByIso(strtoupper($this->context->cookie->iso_code_country)) : (int) Tools::getCountry();

                $country = new Country($idCountry, (int) $this->context->cookie->id_lang);

                if (!$hasCurrency && Validate::isLoadedObject($country) && $this->context->country->id !== $country->id) {
                    $this->context->country = $country;
                    $this->context->cookie->id_currency = (int) Currency::getCurrencyInstance($country->id_currency ? (int) $country->id_currency : (int) Configuration::get('PS_CURRENCY_DEFAULT'))->id;
                    $this->context->cookie->iso_code_country = strtoupper($country->iso_code);
                }
            }
        }

        // save user preference about using mobile theme, if submitted
        if (Tools::isSubmit('no_mobile_theme')) {
            $this->setMobileThemeAllowed($this->context->cookie, false);
        } elseif (Tools::isSubmit('mobile_theme_ok')) {
            $this->setMobileThemeAllowed($this->context->cookie, true);
        }

        $currency = Tools::setCurrency($this->context->cookie);

        if (isset($_GET['logout']) || ($this->context->customer->logged && Customer::isBanned($this->context->customer->id))) {
            $this->context->customer->logout();
            Tools::redirect(Tools::secureReferrer(Tools::getHttpReferer()));
        } elseif (isset($_GET['mylogout'])) {
            $this->context->customer->mylogout();
            Tools::redirect(Tools::secureReferrer(Tools::getHttpReferer()));
        }

        /* Cart already exists */
        if ((int) $this->context->cookie->id_cart) {
            if (!isset($cart)) {
                $cart = new Cart($this->context->cookie->id_cart);
            }

            if (Validate::isLoadedObject($cart) && $cart->OrderExists()) {
                unset($this->context->cookie->id_cart, $cart, $this->context->cookie->checkedTOS);
                $this->context->cookie->check_cgv = false;
            } /* Delete product of cart, if user can't make an order from his country */
            elseif (intval(Configuration::get('PS_GEOLOCATION_ENABLED')) &&
                !in_array(strtoupper($this->context->cookie->iso_code_country), explode(';', (string)Configuration::get('PS_ALLOWED_COUNTRIES'))) &&
                $cart->nbProducts() && intval(Configuration::get('PS_GEOLOCATION_NA_BEHAVIOR')) != -1 &&
                !FrontController::isInWhitelistForGeolocation() &&
                !in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1'])
            ) {
                Logger::addLog('Frontcontroller::init - GEOLOCATION is deleting a cart', 1, null, 'Cart', (int) $this->context->cookie->id_cart, true);
                unset($this->context->cookie->id_cart, $cart);
            } // update cart values
            elseif ($this->context->cookie->id_customer != $cart->id_customer || $this->context->cookie->id_lang != $cart->id_lang || $currency->id != $cart->id_currency) {
                if ($this->context->cookie->id_customer) {
                    $cart->id_customer = (int) $this->context->cookie->id_customer;
                }
                $cart->id_lang = (int) $this->context->cookie->id_lang;
                $cart->id_currency = (int) $currency->id;
                $cart->update();
            }
            /* Select an address if not set */
            if (isset($cart) && $this->context->cookie->id_customer
                && (!$cart->id_address_delivery || !$cart->id_address_invoice)) {
                $idFirstAddress = (int) Address::getFirstCustomerAddressId($cart->id_customer);
                if ($idFirstAddress) {
                    $toUpdate = false;
                    if (!$cart->id_address_delivery) {
                        $toUpdate = true;
                        $cart->id_address_delivery = $idFirstAddress;
                    }
                    if (!$cart->id_address_invoice) {
                        $toUpdate = true;
                        $cart->id_address_invoice = $idFirstAddress;
                    }
                    if ($toUpdate) {
                        $cart->update();
                    }
                }
            }
        }

        if (!isset($cart) || !$cart->id) {
            $cart = new Cart();
            $cart->id_lang = (int) $this->context->cookie->id_lang;
            $cart->id_currency = (int) $this->context->cookie->id_currency;
            $cart->id_guest = (int) $this->context->cookie->id_guest;
            $cart->id_shop_group = (int) $this->context->shop->id_shop_group;
            $cart->id_shop = $this->context->shop->id;
            if ($this->context->cookie->id_customer) {
                $cart->id_customer = (int) $this->context->cookie->id_customer;
                $cart->id_address_delivery = (int) Address::getFirstCustomerAddressId($cart->id_customer);
                $cart->id_address_invoice = (int) $cart->id_address_delivery;
            } else {
                $cart->id_address_delivery = 0;
                $cart->id_address_invoice = 0;
            }

            // Needed if the merchant want to give a free product to every visitors
            $this->context->cart = $cart;
            CartRule::autoAddToCart($this->context);
        } else {
            $this->context->cart = $cart;
        }

        /* get page name to display it in body id */

        // Are we in a payment module
        $moduleName = '';
        if (Validate::isModuleName(Tools::getValue('module'))) {
            $moduleName = Tools::getValue('module');
        }

        if (!empty($this->page_name)) {
            $pageName = $this->page_name;
        } elseif (!empty($this->php_self)) {
            $pageName = $this->php_self;
        } elseif (Tools::getValue('fc') == 'module' && $moduleName != '' && (Module::getInstanceByName($moduleName) instanceof PaymentModule)) {
            $pageName = 'module-payment-submit';
        } elseif (preg_match('#^'.preg_quote($this->context->shop->physical_uri, '#').'modules/([a-zA-Z0-9_-]+?)/(.*)$#', $_SERVER['REQUEST_URI'], $m)) {
            $pageName = 'module-'.$m[1].'-'.str_replace(['.php', '/'], ['', '-'], $m[2]);
        } else {
            $pageName = Dispatcher::getInstance()->getController();
            $pageName = (preg_match('/^[0-9]/', $pageName) ? 'page_'.$pageName : $pageName);
        }

        $this->context->smarty->assign(Meta::getMetaTags($this->context->language->id, $pageName));
        $this->context->smarty->assign('request_uri', Tools::safeOutput(urldecode($_SERVER['REQUEST_URI'])));

        /* Breadcrumb */
        $navigationPipe = (Configuration::get('PS_NAVIGATION_PIPE') ? Configuration::get('PS_NAVIGATION_PIPE') : '>');
        $this->context->smarty->assign('navigationPipe', $navigationPipe);

        // Automatically redirect to the canonical URL if needed
        if (!empty($this->php_self) && !Tools::getValue('ajax')) {
            $this->canonicalRedirection($this->context->link->getPageLink($this->php_self, $this->ssl, $this->context->language->id));
        }

        Product::initPricesComputation();

        $displayTaxLabel = $this->context->country->display_tax_label;
        if (isset($cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')}) && $cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')}) {
            $infos = Address::getCountryAndState((int) $cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')});
            if (isset($infos['id_country']) && $infos['id_country']) {
                $country = new Country((int)$infos['id_country']);
                $this->context->country = $country;
                if (Validate::isLoadedObject($country)) {
                    $displayTaxLabel = $country->display_tax_label;
                }
            }
        }

        $languages = Language::getLanguages(true, $this->context->shop->id);
        $metaLanguage = [];
        foreach ($languages as $lang) {
            $metaLanguage[] = $lang['iso_code'];
        }

        $comparedProducts = [];
        if (Configuration::get('PS_COMPARATOR_MAX_ITEM') && isset($this->context->cookie->id_compare)) {
            $comparedProducts = CompareProduct::getCompareProducts($this->context->cookie->id_compare);
        }

        $this->context->smarty->assign(
            [
                // Useful for layout.tpl
                'mobile_device'       => $this->context->getMobileDevice(),
                'link'                => $link,
                'cart'                => $cart,
                'currency'            => $currency,
                'currencyRate'        => $currency->getConversationRate(),
                'cookie'              => $this->context->cookie,
                'page_name'           => $pageName,
                'hide_left_column'    => !$this->display_column_left,
                'hide_right_column'   => !$this->display_column_right,
                'base_dir'            => _PS_BASE_URL_.__PS_BASE_URI__,
                'base_dir_ssl'        => $protocolLink.Tools::getShopDomainSsl().__PS_BASE_URI__,
                'force_ssl'           => Configuration::get('PS_SSL_ENABLED'),
                'content_dir'         => $protocolContent.Tools::getHttpHost().__PS_BASE_URI__,
                'base_uri'            => $protocolContent.Tools::getHttpHost().__PS_BASE_URI__.(!Configuration::get('PS_REWRITING_SETTINGS') ? 'index.php' : ''),
                'tpl_dir'             => _PS_THEME_DIR_,
                'tpl_uri'             => _THEME_DIR_,
                'root_dir'            => _PS_ROOT_DIR_,
                'modules_dir'         => _MODULE_DIR_,
                'mail_dir'            => _MAIL_DIR_,
                'lang_iso'            => $this->context->language->iso_code,
                'lang_id'             => (int) $this->context->language->id,
                'isRtl'               => $this->context->language->is_rtl,
                'language_code'       => $this->context->language->language_code ? $this->context->language->language_code : $this->context->language->iso_code,
                'come_from'           => Tools::getHttpHost(true, true).Tools::htmlentitiesUTF8(str_replace(['\'', '\\'], '', urldecode($_SERVER['REQUEST_URI']))),
                'cart_qties'          => (int) $cart->nbProducts(),
                'currencies'          => Currency::getCurrencies(),
                'languages'           => $languages,
                'meta_language'       => implode(',', $metaLanguage),
                'priceDisplay'        => Product::getTaxCalculationMethod((int) $this->context->cookie->id_customer),
                'is_logged'           => (bool) $this->context->customer->isLogged(),
                'is_guest'            => (bool) $this->context->customer->isGuest(),
                'add_prod_display'    => (int) Configuration::get('PS_ATTRIBUTE_CATEGORY_DISPLAY'),
                'shop_name'           => Configuration::get('PS_SHOP_NAME'),
                'roundMode'           => (int) Configuration::get('PS_PRICE_ROUND_MODE'),
                'use_taxes'           => (int) Configuration::get('PS_TAX'),
                'show_taxes'          => (int) (Configuration::get('PS_TAX_DISPLAY') == 1 && (int) Configuration::get('PS_TAX')),
                'display_tax_label'   => (bool) $displayTaxLabel,
                'vat_management'      => (int) Configuration::get('VATNUMBER_MANAGEMENT'),
                'opc'                 => (bool) Configuration::get('PS_ORDER_PROCESS_TYPE'),
                'PS_CATALOG_MODE'     => Configuration::get('PS_CATALOG_MODE') || (Group::isFeatureActive() && !Group::getCurrent()->show_prices),
                'b2b_enable'          => (bool) Configuration::get('PS_B2B_ENABLE'),
                'request'             => $link->getPaginationLink(false, false, false, true),
                'PS_STOCK_MANAGEMENT' => Configuration::get('PS_STOCK_MANAGEMENT'),
                'quick_view'          => (bool) Configuration::get('PS_QUICK_VIEW'),
                'shop_phone'          => Configuration::get('PS_SHOP_PHONE'),
                'compared_products'   => $comparedProducts,
                'comparator_max_item' => (int) Configuration::get('PS_COMPARATOR_MAX_ITEM'),
                'currencySign'        => $currency->sign, // backward compat, see global.tpl
                'currencyFormat'      => $currency->format, // backward compat
                'currencyBlank'       => $currency->blank, // backward compat
                'high_dpi'            => (bool) Configuration::get('PS_HIGHT_DPI'),
                'lazy_load'           => (bool) Configuration::get('TB_LAZY_LOAD'),
                'webp'                => ImageManager::webpSupport(),
            ]
        );

        // Add the tpl files directory for mobile
        if ($this->useMobileTheme()) {
            $this->context->smarty->assign(
                [
                    'tpl_mobile_uri' => _PS_THEME_MOBILE_DIR_,
                ]
            );
        }

        // Deprecated
        $this->context->smarty->assign(
            [
                'id_currency_cookie' => (int) $currency->id,
                'logged'             => $this->context->customer->isLogged(),
                'customerName'       => ($this->context->customer->logged ? $this->context->cookie->customer_firstname.' '.$this->context->cookie->customer_lastname : false),
            ]
        );

        $assignArray = [
            'img_ps_dir'    => _PS_IMG_,
            'img_cat_dir'   => _THEME_CAT_DIR_,
            'img_lang_dir'  => _THEME_LANG_DIR_,
            'img_prod_dir'  => _THEME_PROD_DIR_,
            'img_manu_dir'  => _THEME_MANU_DIR_,
            'img_sup_dir'   => _THEME_SUP_DIR_,
            'img_ship_dir'  => _THEME_SHIP_DIR_,
            'img_store_dir' => _THEME_STORE_DIR_,
            'img_col_dir'   => _THEME_COL_DIR_,
            'img_dir'       => _THEME_IMG_DIR_,
            'css_dir'       => _THEME_CSS_DIR_,
            'js_dir'        => _THEME_JS_DIR_,
            'pic_dir'       => _THEME_PROD_PIC_DIR_,
        ];

        // Add the images directory for mobile
        if ($this->useMobileTheme()) {
            $assignArray['img_mobile_dir'] = _THEME_MOBILE_IMG_DIR_;
        }

        // Add the CSS directory for mobile
        if ($this->useMobileTheme()) {
            $assignArray['css_mobile_dir'] = _THEME_MOBILE_CSS_DIR_;
        }

        foreach ($assignArray as $assignKey => $assignValue) {
            if (substr($assignValue, 0, 1) == '/' || $protocolContent == 'https://') {
                $this->context->smarty->assign($assignKey, $protocolContent.Tools::getMediaServer($assignValue).$assignValue);
            } else {
                $this->context->smarty->assign($assignKey, $assignValue);
            }
        }

        /*
         * These shortcuts are DEPRECATED as of version 1.5.0.1
         * Use the Context to access objects instead.
         * Example: $this->context->cart
         */
        static::$cookie = $this->context->cookie;
        static::$cart = $cart;
        static::$smarty = $this->context->smarty;
        static::$link = $link;
        $defaultCountry = $this->context->country;

        $this->displayMaintenancePage();

        if ($this->restrictedCountry) {
            $this->displayRestrictedCountryPage();
        }

        if (Tools::isSubmit('live_edit') && !$this->checkLiveEditAccess()) {
            Tools::redirect('index.php?controller=404');
        }

        $this->iso = $iso;
        $this->context->cart = $cart;
        $this->context->currency = $currency;
    }

    /**
     * Redirects to correct protocol if settings and request methods don't match.
     *
     * @throws PrestaShopException
     */
    protected function sslRedirection()
    {
        // If we call a SSL controller without SSL or a non SSL controller with SSL, we redirect with the right protocol
        if (!Tools::isPHPCLI() && Configuration::get('PS_SSL_ENABLED') && (Tools::getRequestMethod() !== 'POST') && $this->ssl != Tools::usingSecureMode()) {
            $this->context->cookie->disallowWriting();
            header('HTTP/1.1 301 Moved Permanently');
            header('Cache-Control: no-cache');
            if ($this->ssl) {
                header('Location: '.Tools::getShopDomainSsl(true).$_SERVER['REQUEST_URI']);
            } else {
                header('Location: '.Tools::getShopDomain(true).$_SERVER['REQUEST_URI']);
            }
            exit();
        }
    }

    /**
     * Recovers cart information.
     *
     * @return int|false
     *
     * @throws PrestaShopException
     */
    protected function recoverCart()
    {
        if (($idCart = Tools::getIntValue('recover_cart')) && Tools::getValue('token_cart') == md5(_COOKIE_KEY_.'recover_cart_'.$idCart)) {
            $cart = new Cart((int) $idCart);
            if (Validate::isLoadedObject($cart)) {
                $customer = new Customer((int) $cart->id_customer);
                if (Validate::isLoadedObject($customer)) {
                    $customer->logged = 1;
                    $this->context->customer = $customer;
                    $this->context->cookie->id_customer = (int) $customer->id;
                    $this->context->cookie->customer_lastname = $customer->lastname;
                    $this->context->cookie->customer_firstname = $customer->firstname;
                    $this->context->cookie->logged = 1;
                    $this->context->cookie->check_cgv = 1;
                    $this->context->cookie->is_guest = $customer->isGuest();
                    $this->context->cookie->passwd = $customer->passwd;
                    $this->context->cookie->email = $customer->email;

                    return $idCart;
                }
            }
        } else {
            return false;
        }

        return false;
    }

    /**
     * Geolocation management.
     *
     * @param Country $defaultCountry
     *
     * @return Country|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function geolocationManagement($defaultCountry)
    {
        $ip = Tools::getRemoteAddr();
        if ($ip && !in_array($ip, ['127.0.0.1', '::1'])) {
            // determine GeoLocation service module
            $geolocationModule = Configuration::getGlobalValue('PS_GEOLOCATION_SERVICE');
            $geolocationModuleId = Module::getModuleIdByName($geolocationModule);
            if ($geolocationModuleId) {

                $allowedCountries = explode(';', (string)Configuration::get('PS_ALLOWED_COUNTRIES'));
                if (!isset($this->context->cookie->iso_code_country) || (isset($this->context->cookie->iso_code_country) && !in_array(strtoupper($this->context->cookie->iso_code_country), $allowedCountries))) {

                    // Invoke geolocation module service
                    $res = Hook::getResponse('actionGeoLocation', $geolocationModuleId, [ 'ip' => $ip ]);
                    if ($res) {
                        $countryCode = strtoupper((string)$res);

                        if (!in_array($countryCode, $allowedCountries) && !static::isInWhitelistForGeolocation()) {
                            if (Configuration::get('PS_GEOLOCATION_BEHAVIOR') == _PS_GEOLOCATION_NO_CATALOG_) {
                                $this->restrictedCountry = true;
                            } elseif (Configuration::get('PS_GEOLOCATION_BEHAVIOR') == _PS_GEOLOCATION_NO_ORDER_) {
                                $countryName = $countryCode;
                                $country = new Country(Country::getByIso($countryCode), $this->context->language->id);
                                if (Validate::isLoadedObject($country)) {
                                    $countryName = $country->name;
                                }
                                $this->context->smarty->assign(
                                    [
                                        'restricted_country_mode' => true,
                                        'geolocation_country' => $countryName
                                    ]
                                );
                            }
                        } else {
                            $hasBeenSet = !isset($this->context->cookie->iso_code_country);
                            $this->context->cookie->iso_code_country = $countryCode;
                        }
                    }
                }

                if (isset($this->context->cookie->iso_code_country) && $this->context->cookie->iso_code_country && !Validate::isLanguageIsoCode($this->context->cookie->iso_code_country)) {
                    $this->context->cookie->iso_code_country = Country::getIsoById(Configuration::get('PS_COUNTRY_DEFAULT'));
                }

                if (isset($this->context->cookie->iso_code_country) && ($idCountry = (int) Country::getByIso(strtoupper($this->context->cookie->iso_code_country)))) {
                    /* Update defaultCountry */
                    if ($defaultCountry->iso_code != $this->context->cookie->iso_code_country) {
                        $defaultCountry = new Country($idCountry);
                    }
                    if (isset($hasBeenSet) && $hasBeenSet) {
                        $this->context->cookie->id_currency = (int) ($defaultCountry->id_currency ? (int) $defaultCountry->id_currency : (int) Configuration::get('PS_CURRENCY_DEFAULT'));
                    }

                    return $defaultCountry;
                } elseif (Configuration::get('PS_GEOLOCATION_NA_BEHAVIOR') == _PS_GEOLOCATION_NO_CATALOG_ && !FrontController::isInWhitelistForGeolocation()) {
                    $this->restrictedCountry = true;
                } elseif (Configuration::get('PS_GEOLOCATION_NA_BEHAVIOR') == _PS_GEOLOCATION_NO_ORDER_ && !FrontController::isInWhitelistForGeolocation()) {
                    $this->context->smarty->assign(
                        [
                            'restricted_country_mode' => true,
                            'geolocation_country' => ''
                        ]
                    );
                }
            }
        }

        return false;
    }

    /**
     * Checks if user's location is whitelisted.
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    protected static function isInWhitelistForGeolocation()
    {
        static $allowed = null;

        if ($allowed !== null) {
            return $allowed;
        }

        $allowed = false;
        $userIp = Tools::getRemoteAddr();
        $ips = [];

        // retrocompatibility
        $ipsOld = explode(';', (string)Configuration::get('PS_GEOLOCATION_WHITELIST'));
        if (is_array($ipsOld) && count($ipsOld)) {
            foreach ($ipsOld as $ip) {
                $ips = array_merge($ips, explode("\n", $ip));
            }
        }

        foreach ($ips as $ip) {
            $ip = trim((string)$ip);
            if ($ip && preg_match('/^'.$ip.'.*/', $userIp)) {
                $allowed = true;
            }
        }

        return $allowed;
    }

    /**
     * Redirects to canonical URL.
     *
     * @param string $canonicalUrl
     *
     * @throws PrestaShopException
     */
    protected function canonicalRedirection($canonicalUrl = '')
    {
        if (!$canonicalUrl || !Configuration::get('PS_CANONICAL_REDIRECT') || Tools::getRequestMethod() !== 'GET' || Tools::getValue('live_edit')) {
            return;
        }

        $matchUrl = rawurldecode(Tools::getCurrentUrlProtocolPrefix().$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
        if (Tools::usingSecureMode()) {
            // Do not redirect to the same page on HTTP
            if (substr_replace($canonicalUrl, 'https', 0, 4) === $matchUrl) {
                return;
            }
        }
        if (!preg_match('/^'.Tools::pRegexp(rawurldecode($canonicalUrl), '/').'([&?].*)?$/', $matchUrl)) {
            $params = [];
            $urlDetails = parse_url($canonicalUrl);

            if (!empty($urlDetails['query'])) {
                parse_str($urlDetails['query'], $query);
                foreach ($query as $key => $value) {
                    $params[Tools::safeOutput($key)] = Tools::safeOutput($value);
                }
            }
            $excludedKey = ['isolang', 'id_lang', 'controller', 'fc', 'id_product', 'id_category', 'id_manufacturer', 'id_supplier', 'id_cms'];
            foreach ($_GET as $key => $value) {
                if (!in_array($key, $excludedKey) && Validate::isUrl($key)) {
                    if (is_array($value)) {
                        $arrayParams = [];
                        foreach ($value as $paramKey => $arrayParam) {
                            if (Validate::isUrl($arrayParam)) {
                                $arrayParams[$paramKey] = Tools::safeOutput($arrayParam);
                            }
                        }
                        $params[Tools::safeOutput($key)] = $arrayParams;
                    } else {
                        if (Validate::isUrl($value)) {
                            $params[Tools::safeOutput($key)] = Tools::safeOutput($value);
                        }
                    }
                }
            }

            $strParams = http_build_query($params, '', '&');
            if (!empty($strParams)) {
                $finalUrl = preg_replace('/^([^?]*)?.*$/', '$1', $canonicalUrl).'?'.$strParams;
            } else {
                $finalUrl = preg_replace('/^([^?]*)?.*$/', '$1', $canonicalUrl);
            }

            // Don't send any cookie
            $this->context->cookie->disallowWriting();

            $redirectType = Configuration::get('PS_CANONICAL_REDIRECT') == 2 ? '301' : '302';
            header('HTTP/1.0 '.$redirectType.' Moved');
            header('Cache-Control: no-cache');
            Tools::redirectLink($finalUrl);
        }
    }

    /**
     * Displays 'country restricted' page if user's country is not allowed.
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function displayRestrictedCountryPage()
    {
        header('HTTP/1.1 503 temporarily overloaded');
        $this->context->smarty->assign(
            [
                'shop_name'   => $this->context->shop->name,
                'favicon_url' => _PS_IMG_.Configuration::get('PS_FAVICON'),
                'logo_url'    => $this->context->link->getMediaLink(_PS_IMG_.Configuration::get('PS_LOGO')),
            ]
        );
        $this->smartyOutputContent($this->getTemplatePath($this->getThemeDir().'restricted-country.tpl'));
        exit;
    }

    /**
     * Checks if token is valid.
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function isTokenValid()
    {
        if (!Configuration::get('PS_TOKEN_ENABLE')) {
            return true;
        }

        return strcasecmp(Tools::getToken(false), Tools::getValue('token')) == 0;
    }

    /**
     * Removes CSS file(s) from page header.
     *
     * @param array|string $cssUri Path to file, or an array of paths like: array(array(uri => media_type), ...)
     * @param string $cssMediaType CSS media type
     * @param bool $checkPath If true, checks if files exists
     */
    public function removeCSS($cssUri, $cssMediaType = 'all', $checkPath = true)
    {
        return $this->removeMedia($cssUri, $cssMediaType, $checkPath);
    }

    /**
     * Removes media file(s) from page header.
     *
     * @param string|array $mediaUri Path to file, or an array paths of like: array(array(uri => media_type), ...)
     * @param string|null $cssMediaType CSS media type
     * @param bool $checkPath If true, checks if files exists
     */
    public function removeMedia($mediaUri, $cssMediaType = null, $checkPath = true)
    {
        $this->addMedia($mediaUri, $cssMediaType, null, true, $checkPath);
    }

    /**
     * Removes JS file(s) from page header.
     *
     * @param array|string $jsUri Path to file, or an array of paths
     * @param bool $checkPath If true, checks if files exists
     */
    public function removeJS($jsUri, $checkPath = true)
    {
        return $this->removeMedia($jsUri, null, $checkPath);
    }

    /**
     * Sets template file for page content output.
     *
     * @param string $defaultTemplate
     *
     * @throws PrestaShopException
     */
    public function setTemplate($defaultTemplate)
    {
        if ($this->useMobileTheme()) {
            $this->setMobileTemplate($defaultTemplate);
        } else {
            $template = $this->getOverrideTemplate();
            if (! $template) {
                $template = $defaultTemplate;
            }

            $theme = Context::getContext()->theme;
            $theme->ensureTemplate($template);
            parent::setTemplate($template);
        }
    }

    /**
     * Checks if the template set is available for mobile themes,
     * otherwise front template is chosen.
     *
     * @param string $template
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function setMobileTemplate($template)
    {
        // Needed for site map
        $blockmanufacturer = Module::getInstanceByName('blockmanufacturer');
        $blocksupplier = Module::getInstanceByName('blocksupplier');

        $this->context->smarty->assign(
            [
                'categoriesTree'            => Category::getRootCategory()->recurseLiteCategTree(0),
                'categoriescmsTree'         => CMSCategory::getRecurseCategory($this->context->language->id, 1, 1, 1),
                'voucherAllowed'            => (int) CartRule::isFeatureActive(),
                'display_manufacturer_link' => (bool) $blockmanufacturer->active,
                'display_supplier_link'     => (bool) $blocksupplier->active,
                'PS_DISPLAY_SUPPLIERS'      => Configuration::get('PS_DISPLAY_SUPPLIERS'),
                'PS_DISPLAY_BEST_SELLERS'   => Configuration::get('PS_DISPLAY_BEST_SELLERS'),
                'display_store'             => Configuration::get('PS_STORES_DISPLAY_SITEMAP'),
                'conditions'                => Configuration::get('PS_CONDITIONS'),
                'id_cgv'                    => Configuration::get('PS_CONDITIONS_CMS_ID'),
                'PS_SHOP_NAME'              => Configuration::get('PS_SHOP_NAME'),
            ]
        );

        $template = $this->getTemplatePath($template);

        $assign = [];
        $assign['tpl_file'] = basename($template, '.tpl');
        if (isset($this->php_self)) {
            $assign['controller_name'] = $this->php_self;
        }

        $this->context->smarty->assign($assign);
        $this->template = $template;
    }

    /**
     * Returns an overridden template path (if any) for this controller.
     * If not overridden, will return false. This method can be easily overriden in a
     * specific controller.
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public function getOverrideTemplate()
    {
        return (string)Hook::getFirstResponse('DisplayOverrideTemplate', ['controller' => $this]);
    }

    /**
     * Renders and adds color list HTML for each product in a list.
     *
     * @param array $products
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function addColorsToProductList(&$products)
    {
        if (!is_array($products) || !count($products) || !file_exists(_PS_THEME_DIR_.'product-list-colors.tpl')) {
            return;
        }

        $productsNeedCache = [];
        foreach ($products as &$product) {
            if (!$this->isCached(_PS_THEME_DIR_.'product-list-colors.tpl', $this->getColorsListCacheId($product['id_product']))) {
                $productsNeedCache[] = (int)$product['id_product'];
            }
        }
        unset($product);

        $colors = false;
        if ($productsNeedCache) {
            $colors = Product::getAttributesColorList($productsNeedCache);
        }

        Tools::enableCache();
        foreach ($products as &$product) {
            $cacheId = $this->getColorsListCacheId($product['id_product']);
            $tpl = $this->context->smarty->createTemplate(_PS_THEME_DIR_.'product-list-colors.tpl', $cacheId);
            if (isset($colors[$product['id_product']])) {
                $tpl->assign(
                    [
                        'id_product'  => $product['id_product'],
                        'colors_list' => $colors[$product['id_product']],
                        'link'        => Context::getContext()->link,
                        'img_col_dir' => _THEME_COL_DIR_,
                        'col_img_dir' => _PS_COL_IMG_DIR_
                    ]
                );
            }

            if (!in_array($product['id_product'], $productsNeedCache) || isset($colors[$product['id_product']])) {
                $product['color_list'] = $tpl->fetch(_PS_THEME_DIR_.'product-list-colors.tpl', $cacheId);
            } else {
                $product['color_list'] = '';
            }
        }
        Tools::restoreCacheSettings();
    }

    /**
     * Returns cache ID for product color list.
     *
     * @param int $idProduct
     *
     * @return string
     */
    protected function getColorsListCacheId($idProduct)
    {
        return Product::getColorsListCacheId($idProduct);
    }

    /**
     * Redirects to redirect_after link.
     *
     * @throws PrestaShopException
     */
    protected function redirect()
    {
        Tools::redirectLink($this->redirect_after);
    }

    /**
     * Saves user preference about 'Mobile Theme' into cookie. This allows
     * visitors to opt out from using mobile theme variant
     *
     * @param Cookie $cookie
     * @param bool $allowed
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function setMobileThemeAllowed(Cookie $cookie, $allowed)
    {
        $allowed = (bool)$allowed;
        $cookie->no_mobile = !$allowed;
        if ($cookie->id_guest) {
            $guest = new Guest($cookie->id_guest);
            if (Validate::isLoadedObject($guest) && (bool)$guest->mobile_theme !== $allowed) {
                $guest->mobile_theme = $allowed;
                $guest->update();
            }
        }
    }

    /**
     * @param array $languages
     * @param int $defaultLangId
     *
     * @return array
     * @throws PrestaShopException
     */
    protected function getHrefLangMapping(array $languages, int $defaultLangId): array
    {
        $targetShopId = (int)$this->context->shop->id;

        // allow modules to override hreng mappings
        $response = Hook::getFirstResponse('actionHrefLangMapping');
        if (is_array($response)) {
            return $response;
        }

        $mapping = [];
        foreach ($languages as $lang) {
            $code = $lang['language_code'];
            $targetLangId = (int)$lang['id_lang'];
            $isDefault = $targetLangId === $defaultLangId;
            $mapping[$code] = [
                'targetShopId' => $targetShopId,
                'targetLangId' => $targetLangId,
                'isDefault' => $isDefault
            ];
        }
        return $mapping;
    }

    /**
     * Returns canonical url to current page, if known
     *
     * @return string|null
     * @throws PrestaShopException
     */
    protected function getCurrentPageCanonicalUrl()
    {
        return $this->getCurrentPageAlternateUrl(
            (int)$this->context->shop->id,
            (int)$this->context->language->id
        );
    }

    /**
     * Returns current page next/prev link tags, if they exits
     *
     * @return string|null
     */
    protected function getCurrentPagePrevNextRelTags()
    {
        return null;
    }

    /**
     * Returns alternate url for current page
     *
     * @param int $shopId
     * @param int $languageId
     *
     * @return string|null
     * @throws PrestaShopException
     */
    protected function getCurrentPageAlternateUrl(int $shopId, int $languageId)
    {
        $routeId = (string)$this->php_self;
        if ($routeId) {
            $dispatcher = Dispatcher::getInstance();
            if ($info = $dispatcher->isModuleControllerRoute($routeId)) {
                // include only required $_GET parameters and ignore others
                $params = array_intersect_key($_GET, $dispatcher->getRouteRequiredParams($routeId));
                return $this->context->link->getModuleLink($info['module'], $info['controller'], $params, null, $languageId, $shopId);
            } else {
                return $this->context->link->getPageLink($routeId, null, $languageId, null, false, $shopId);
            }
        }
        return null;
    }

    /**
     * Returns alternate hreflang link tags
     *
     * @return array
     * @throws PrestaShopException
     */
    protected function getCurrentPageHrefLangTags()
    {
        $languages = Language::getLanguages(true, $this->context->shop->id);
        $idLangDefault = (int)Configuration::get('PS_LANG_DEFAULT');
        $mapping = $this->getHrefLangMapping($languages, $idLangDefault);
        $default = null;
        $links = [];
        foreach ($mapping as $languageCode => $target) {
            $shopId = (int)$target['targetShopId'];
            $languageId = (int)$target['targetLangId'];
            $isDefault = (bool)$target['isDefault'];
            $lnk = $this->getCurrentPageAlternateUrl($shopId, $languageId);
            if ($lnk) {
                // append page number
                if ($p = Tools::getIntValue('p')) {
                    $lnk .= "?p=$p";
                }

                $links[] = '<link rel="alternate" hreflang="' . $languageCode . '" href="' . $lnk . '">';
                if ($isDefault) {
                    $default = '<link rel="alternate" hreflang="x-default" href="' . $lnk . '">';
                }
            }
        }

        if ($default) {
            $links[] = $default;
        }

        return $links;
    }
}
