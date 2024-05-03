<?php
/**
 * 2007-2016 PrestaShop
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

use Thirtybees\Core\DependencyInjection\ServiceLocator;
use Thirtybees\Core\Error\ErrorHandler;
use Thirtybees\Core\Error\ErrorUtils;

/**
 * Class ControllerCore
 */
abstract class ControllerCore
{
    /** @var array List of CSS files */
    public $css_files = [];

    /** @var array List of JavaScript files */
    public $js_files = [];
    /** @var bool If AJAX parameter is detected in request, set this flag to true */
    public $ajax = false;
    /** @var string Controller type. Possible values: 'front', 'modulefront', 'admin', 'moduleadmin' */
    public $controller_type;
    /** @var string Controller name */
    public $php_self;
    /** @var Context */
    protected $context;
    /** @var bool Set to true to display page header */
    protected $display_header;
    /** @var bool Set to true to display page header javascript */
    protected $display_header_javascript;
    /** @var string Template filename for the page content */
    protected $template;
    /** @var string Set to true to display page footer */
    protected $display_footer;
    /** @var bool Set to true to only render page content (used to get iframe content) */
    protected $content_only = false;
    /** @var bool If set to true, page content and messages will be encoded to JSON before responding to AJAX request */
    protected $json = false;
    /** @var string JSON response status string */
    protected $status = '';
    /**
     * @see Controller::run()
     * @var string|null Redirect link. If not empty, the user will be redirected after initializing and processing input.
     */
    protected $redirect_after = null;

    /**
     * @var array errors array
     */
    public $errors = [];

    /**
     * ControllerCore constructor.
     */
    public function __construct()
    {
        if (is_null($this->display_header)) {
            $this->display_header = true;
        }

        if (is_null($this->display_header_javascript)) {
            $this->display_header_javascript = true;
        }

        if (is_null($this->display_footer)) {
            $this->display_footer = true;
        }

        $this->context = Context::getContext();
        $this->context->controller = $this;

        // Usage of ajax parameter is deprecated
        $this->ajax = Tools::getValue('ajax') || Tools::isSubmit('ajax');

        if (!headers_sent()
            && isset($_SERVER['HTTP_USER_AGENT'])
            && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false
                || strpos($_SERVER['HTTP_USER_AGENT'], 'Trident') !== false)
        ) {
            header('X-UA-Compatible: IE=edge,chrome=1');
        }
    }

    /**
     * returns a new instance of this controller
     *
     * @param string $className
     * @param bool $auth
     * @param bool $ssl
     *
     * @return Controller
     *
     * @throws PrestaShopException
     * @deprecated 1.4.0
     */
    public static function getController($className, $auth = false, $ssl = false)
    {
        Tools::displayAsDeprecated();
        return ServiceLocator::getInstance()->getController($className);
    }

    /**
     * thirty bees' new coding style dictates that camelCase should be used
     * rather than snake_case
     * These magic methods provide backwards compatibility for modules/themes/whatevers
     * that still access properties via their snake_case names
     *
     * @param string $property Property name
     *
     * @return mixed
     */
    public function &__get($property)
    {
        // Property to camelCase for backwards compatibility
        $camelCaseProperty = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $property))));
        if (property_exists($this, $camelCaseProperty)) {
            return $this->$camelCaseProperty;
        }

        return $this->$property;
    }

    /**
     * thirty bees' new coding style dictates that camelCase should be used
     * rather than snake_case
     * These magic methods provide backwards compatibility for modules/themes/whatevers
     * that still access properties via their snake_case names
     *
     * @param string $property
     * @param mixed $value
     *
     * @return void
     */
    public function __set($property, $value)
    {
        $blacklist = [
            '_select',
            '_join',
            '_where',
            '_group',
            '_having',
            '_conf',
            '_lang',
        ];

        // Property to camelCase for backwards compatibility
        $snakeCaseProperty = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $property))));
        if (!in_array($property, $blacklist) && property_exists($this, $snakeCaseProperty)) {
            $this->$snakeCaseProperty = $value;
        } else {
            $this->$property = $value;
        }
    }

    /**
     * Starts the controller process
     *
     * @return void
     *
     * @throws SmartyException
     * @throws PrestaShopException
     */
    public function run()
    {
        $this->init();
        if ($this->checkAccess()) {
            if (!$this->content_only && ($this->display_header || (isset($this->className) && $this->className))) {
                $this->setMedia();
            }

            $this->postProcess();

            if (!empty($this->redirect_after)) {
                $this->redirect();
            }

            if (!$this->content_only && ($this->display_header || (isset($this->className) && $this->className))) {
                $this->initHeader();
            }

            if ($this->viewAccess()) {
                $this->initContent();
            } else {
                $this->errors[] = Tools::displayError('Access denied.');
            }

            if (!$this->content_only && ($this->display_footer || (isset($this->className) && $this->className))) {
                $this->initFooter();
            }

            if ($this->ajax) {
                $action = Tools::toCamelCase(Tools::getValue('action'), true);

                if (!empty($action) && method_exists($this, 'displayAjax'.$action)) {
                    $this->{'displayAjax'.$action}();
                } elseif (method_exists($this, 'displayAjax')) {
                    $this->displayAjax();
                }
            } else {
                $this->display();
            }
        } else {
            $this->initCursedPage();
            if (isset($this->layout)) {
                $this->smartyOutputContent($this->layout);
            }
        }
    }

    /**
     * Initialize the page
     *
     * @throws PrestaShopException
     */
    public function init()
    {
        if (!defined('_PS_BASE_URL_')) {
            define('_PS_BASE_URL_', Tools::getShopDomain(true));
        }

        if (!defined('_PS_BASE_URL_SSL_')) {
            define('_PS_BASE_URL_SSL_', Tools::getShopDomainSsl(true));
        }
    }

    /**
     * Check if the controller is available for the current user/visitor
     */
    abstract public function checkAccess();

    /**
     * Sets default media list for this controller
     *
     * @return void
     *
     * @throws PrestaShopException
     */
    abstract public function setMedia();

    /**
     * Do the page treatment: process input, process AJAX, etc.
     */
    abstract public function postProcess();

    /**
     * Redirects to $this->redirect_after after the process if there is no error
     */
    abstract protected function redirect();

    /**
     * Assigns Smarty variables for the page header
     */
    abstract public function initHeader();

    /**
     * Check if the current user/visitor has valid view permissions
     */
    abstract public function viewAccess();

    /**
     * Assigns Smarty variables for the page main content
     */
    abstract public function initContent();

    /**
     * Assigns Smarty variables for the page footer
     */
    abstract public function initFooter();

    /**
     * Displays page view
     */
    abstract public function display();

    /**
     * Assigns Smarty variables when access is forbidden
     */
    abstract public function initCursedPage();

    /**
     * Renders controller templates and generates page content
     *
     * @param array|string $content Template file(s) to be rendered
     *
     * @throws SmartyException
     * @throws PrestaShopException
     */
    protected function smartyOutputContent($content)
    {
        $this->context->cookie->write();
        echo $this->getSmartyOutputContent($content);
    }

    /**
     * Generates page content for controller templates
     *
     * @param string|array $content
     *
     * @return string
     *
     * @throws SmartyException
     */
    protected function getSmartyOutputContent($content): string
    {
        $html = '';
        if (is_array($content)) {
            foreach ($content as $tpl) {
                $html .= $this->context->smarty->fetch($tpl);
            }
        } else {
            $html = $this->context->smarty->fetch($content);
        }
        $html = trim($html);

        $debugScript = $this->getErrorMessagesScript();
        if ($debugScript) {
            $html = str_replace("</body>", $debugScript . "</body>", $html);
        }

        return $html;
    }

    /**
     * Sets page header display
     *
     * @param bool $display
     */
    public function displayHeader($display = true)
    {
        $this->display_header = $display;
    }

    /**
     * Sets page header javascript display
     *
     * @param bool $display
     */
    public function displayHeaderJavaScript($display = true)
    {
        $this->display_header_javascript = $display;
    }

    /**
     * Sets page header display
     *
     * @param bool $display
     */
    public function displayFooter($display = true)
    {
        $this->display_footer = $display;
    }

    /**
     * Sets template file for page content output
     *
     * @param string $template
     */
    public function setTemplate($template)
    {
        $this->template = $template;
    }

    /**
     * Set $this->redirect_after that will be used by redirect() after the process
     *
     * @return void
     */
    public function setRedirectAfter($url)
    {
        $this->redirect_after = $url;
    }

    /**
     * Removes CSS stylesheet(s) from the queued stylesheet list
     *
     * @param string|array $cssUri Path to CSS file or an array like: array(array(uri => media_type), ...)
     * @param string $cssMediaType
     * @param bool $checkPath
     */
    public function removeCSS($cssUri, $cssMediaType = 'all', $checkPath = true)
    {
        if (!is_array($cssUri)) {
            $cssUri = [$cssUri];
        }

        foreach ($cssUri as $cssFile => $media) {
            if (is_string($cssFile) && strlen($cssFile) > 1) {
                if ($checkPath) {
                    $cssPath = Media::getCSSPath($cssFile, $media);
                } else {
                    $cssPath = [$cssFile => $media];
                }
            } else {
                if ($checkPath) {
                    $cssPath = Media::getCSSPath($media, $cssMediaType);
                } else {
                    $cssPath = [$media => $cssMediaType];
                }
            }

            if ($cssPath && isset($this->css_files[key($cssPath)]) && ($this->css_files[key($cssPath)] == reset($cssPath))) {
                unset($this->css_files[key($cssPath)]);
            }
        }
    }

    /**
     * Removes JS file(s) from the queued JS file list
     *
     * @param string|array $jsUri Path to JS file or an array like: array(uri, ...)
     * @param bool $checkPath
     */
    public function removeJS($jsUri, $checkPath = true)
    {
        if (is_array($jsUri)) {
            foreach ($jsUri as $jsFile) {
                $jsPath = $jsFile;
                if ($checkPath) {
                    $jsPath = Media::getJSPath($jsFile);
                }

                if ($jsPath && in_array($jsPath, $this->js_files)) {
                    unset($this->js_files[array_search($jsPath, $this->js_files)]);
                }
            }
        } else {
            $jsPath = $jsUri;
            if ($checkPath) {
                $jsPath = Media::getJSPath($jsUri);
            }

            if ($jsPath) {
                unset($this->js_files[array_search($jsPath, $this->js_files)]);
            }
        }
    }

    /**
     * Adds jQuery library file to queued JS file list
     *
     * @param string|null $version jQuery library version
     * @param string|null $folder jQuery file folder
     * @param bool $minifier If set tot true, a minified version will be included.
     */
    public function addJquery($version = null, $folder = null, $minifier = true)
    {
        $this->addJS(Media::getJqueryPath($version, $folder, $minifier), false);
    }

    /**
     * Adds a new JavaScript file(s) to the page header.
     *
     * @param string|array $jsUri Path to JS file or an array like: array(uri, ...)
     * @param bool $checkPath
     *
     * @return void
     */
    public function addJS($jsUri, $checkPath = true)
    {
        if (is_array($jsUri)) {
            foreach ($jsUri as $jsFile) {
                $this->addJavascriptUri($jsFile, $checkPath);
            }
        } else {
            $this->addJavascriptUri($jsUri, $checkPath);
        }
    }

    /**
     * Adds javascript URI to list of javascript files included in page header
     *
     * @param string $uri uri to javascript file
     * @param boolean $checkPath if true, system will check if the javascript file exits on filesystem
     */
    public function addJavascriptUri($uri, $checkPath)
    {
        if ($checkPath) {
            // remove query parameters from uri
            $parts = explode('?', $uri);

            // resolve uri path
            $uri = Media::getJSPath($parts[0]);

            // add back query parameters
            if ($uri && isset($parts[1]) && $parts[1]) {
                $uri .= '?' . $parts[1];
            }
        }

        if ($uri && !in_array($uri, $this->js_files)) {
            $this->js_files[] = $uri;
        }
    }

    /**
     * Adds jQuery UI component(s) to queued JS file list
     *
     * @param string|array $component
     * @param string $theme
     * @param bool $checkDependencies
     */
    public function addJqueryUI($component, $theme = 'base', $checkDependencies = true)
    {
        if (!is_array($component)) {
            $component = [$component];
        }

        foreach ($component as $ui) {
            $uiPath = Media::getJqueryUIPath($ui, $theme, $checkDependencies);
            $this->addCSS($uiPath['css'], 'all', false);
            $this->addJS($uiPath['js'], false);
        }
    }

    /**
     * Adds a new stylesheet(s) to the page header.
     *
     * @param string|array $cssUri Path to CSS file, or list of css files like this : array(array(uri => media_type), ...)
     * @param string $cssMediaType
     * @param int|null $offset
     * @param bool $checkPath
     *
     * @return bool
     */
    public function addCSS($cssUri, $cssMediaType = 'all', $offset = null, $checkPath = true)
    {
        if (!is_array($cssUri)) {
            $cssUri = [$cssUri];
        }

        $result = count($cssUri) > 0;
        foreach ($cssUri as $cssFile => $media) {
            if (is_string($cssFile) && strlen($cssFile) > 1) {
                if ($checkPath) {
                    $cssPath = Media::getCSSPath($cssFile, $media);
                } else {
                    $cssPath = [$cssFile => $media];
                }
            } else {
                if ($checkPath) {
                    $cssPath = Media::getCSSPath($media, $cssMediaType);
                } else {
                    $cssPath = [$media => is_string($cssMediaType) ? $cssMediaType : 'all'];
                }
            }

            $key = is_array($cssPath) ? key($cssPath) : $cssPath;
            if ($cssPath && (!isset($this->css_files[$key]) || ($this->css_files[$key] != reset($cssPath)))) {
                $size = count($this->css_files);
                if ($offset > $size || $offset < 0 || !is_numeric($offset)) {
                    $offset = $size;
                }

                $this->css_files = array_merge(array_slice($this->css_files, 0, $offset), $cssPath, array_slice($this->css_files, $offset));
            } else {
                $result = false;
            }
        }
        return $result;
    }

    /**
     * Adds jQuery plugin(s) to queued JS file list
     *
     * @param string|array $name
     * @param string|null $folder
     * @param bool $css
     */
    public function addJqueryPlugin($name, $folder = null, $css = true)
    {
        if (!is_array($name)) {
            $name = [$name];
        }
        if (is_array($name)) {
            foreach ($name as $plugin) {
                $pluginPath = Media::getJqueryPluginPath($plugin, $folder);

                if (!empty($pluginPath['js'])) {
                    $this->addJS($pluginPath['js'], false);
                }
                if ($css && !empty($pluginPath['css'])) {
                    $this->addCSS(key($pluginPath['css']), 'all', null, false);
                }
            }
        }
    }

    /**
     * Checks if the controller has been called from XmlHttpRequest (AJAX)
     *
     * @return bool
     */
    public function isXmlHttpRequest()
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
    }

    /**
     * Checks if a template is cached
     *
     * @param string $template
     * @param string|null $cacheId Cache item ID
     * @param string|null $compileId
     *
     * @return bool
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function isCached($template, $cacheId = null, $compileId = null)
    {
        Tools::enableCache();
        $res = $this->context->smarty->isCached($template, $cacheId, $compileId);
        Tools::restoreCacheSettings();

        return $res;
    }

    /**
     * Dies and echoes output value
     *
     * @param string|null $value
     * @param string|null $controller
     * @param string|null $method
     *
     * @throws PrestaShopException
     */
    protected function ajaxDie($value = null, $controller = null, $method = null)
    {
        if ($controller === null) {
            $controller = get_class($this);
        }

        if ($method === null) {
            $bt = debug_backtrace();
            $method = $bt[1]['function'];
        }

        Hook::triggerEvent('actionBeforeAjaxDie', ['controller' => $controller, 'method' => $method, 'value' => $value]);
        Hook::triggerEvent('actionBeforeAjaxDie'.$controller.$method, ['value' => $value]);

        die($value);
    }

    /**
     * This method returns javascript code that outputs all encountered php errors and warnings
     * to javascript console. This script should be inserted just before </body> tag
     *
     * @return string javascript code, or null if no errors were encountered
     */
    protected function getErrorMessagesScript()
    {
        $messages = static::getErrorMessages();
        if ($messages) {
            $messagesList = [];
            foreach ($messages as $msg) {
                $messagesList[] = [
                    'level' => $msg['level'],
                    'type' => $msg['type'],
                    'message' => $msg['errstr'],
                    'file' => ErrorUtils::getRelativeFile($msg['errfile']),
                    'line' => (int)$msg['errline']
                ];
            }
            $messages = '<script type="text/javascript">' . "\n" . 'window.phpMessages=' . json_encode($messagesList, JSON_PRETTY_PRINT). ";\n</script>\n";
            $debugJs = '<script type="text/javascript" src="' . Media::getJSPath(_PS_JS_DIR_ . 'php-debug.js') . '" async defer></script>' . "\n";
            return $messages . $debugJs;
        }
        return '';
    }


    /**
     * Checks if scheduler synthetic cron even should be triggered. If so, /js/trigger.js
     * script will be added to the page. This script will trigger ajax post request
     * to TriggerController front controller
     *
     * @throws PrestaShopException
     */
    protected function addSyntheticSchedulerJs()
    {
        // check if scheduler event is required
        if (! $this->ajax) {
            $scheduler = ServiceLocator::getInstance()->getScheduler();
            if ($scheduler ->syntheticEventRequired()) {
                $triggerUrl = $this->context->link->getPageLink('trigger', null, null, ['ts' => time()]);
                Media::addJsDef([
                    'triggerUrl' => $triggerUrl,
                    'triggerToken' => $scheduler->getSyntheticEventSecret()
                ]);
                $this->addJS(_PS_JS_DIR_ . 'trigger.js');
            }
        }
    }

    /**
     * @return ErrorHandler
     */
    protected static function getErrorHandler(): ErrorHandler
    {
        return ServiceLocator::getInstance()->getErrorHandler();
    }

    /**
     * Returns error messages collected by ErrorHandler
     * @return array
     */
    protected static function getErrorMessages()
    {
        if (_PS_MODE_DEV_) {
            if (_PS_DISPLAY_COMPATIBILITY_WARNING_) {
                $mask = E_ALL;
            } else {
                $mask = E_ALL & ~(E_DEPRECATED | E_USER_DEPRECATED);
            }
            return static::getErrorHandler()->getErrorMessages(false, $mask);
        }
        return [];
    }
}
