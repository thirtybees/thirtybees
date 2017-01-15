<?php
/**
 * 2007-2016 PrestaShop
 *
 * Thirty Bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017 Thirty Bees
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
 *  @author    Thirty Bees <contact@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017 Thirty Bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class PageCacheControllerCore
 *
 * @since 1.0.0
 *
 * @todo: Meant for Varnish support (ESI), to be secured
 */
class PageCacheControllerCore extends AjaxController
{
    // @codingStandardsIgnoreStart
    public $php_self = 'pagecache';
    // @codingStandardsIgnoreEnd

    /**
     * Controller constructor
     *
     * @global bool $useSSL SSL connection flag
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function __construct()
    {
        $this->controller_type = 'ajax';

        global $useSSL;

        parent::__construct();

        if (Configuration::get('PS_SSL_ENABLED') && Configuration::get('PS_SSL_ENABLED_EVERYWHERE')) {
            $this->ssl = true;
        }

        if (isset($useSSL)) {
            $this->ssl = $useSSL;
        } else {
            $useSSL = $this->ssl;
        }
    }

    /**
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function initContent()
    {
        if (Tools::isSubmit('type')) {
            header('Content-Type: text/plain');
            die('To be implemented');
        } elseif (Tools::isSubmit('key')) {
            if ($content = CachePhpRedis::getInstance()->get(Tools::getValue('key'))) {
                header('Content-Type: text/html');
                echo $content;
                die();
            }
            header('Content-Type: text/plain');
            die('0');
        }
    }
}
