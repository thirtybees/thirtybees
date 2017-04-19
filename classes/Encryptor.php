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
 * @author    Thirty Bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017 Thirty Bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class EncryptorCore
 *
 * @since 1.0.1
 */
class EncryptorCore
{
    /** @var string Contain cookie content in a key => value format */
    protected $content;

    /** @var array cipher tool instance */
    protected $cipherTool;

    /** @var Encryptor $instance */
    protected static $instance;

    /**
     * Encryptor singleton
     *
     * @return Encryptor instance
     */
    public static function getInstance()
    {
        if (!static::$instance) {
            static::$instance = new Encryptor();
        }

        return static::$instance;
    }

    /**
     * Get data if the cookie exists and else initialize an new one
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function __construct()
    {
        $this->content = [];

        // Get cipher tool from cookie first
        if (!Validate::isLoadedObject(Context::getContext()->cookie) || !$this->cipherTool = Context::getContext()->cookie->getCipherTool()) {
            if ((int) Configuration::get('PS_CIPHER_ALGORITHM') === 1 && defined('_RIJNDAEL_KEY_')) {
                $this->cipherTool = new Rijndael(_RIJNDAEL_KEY_, _RIJNDAEL_IV_);
            } elseif ((int) Configuration::get('PS_CIPHER_ALGORITHM') === 2 && defined('_PHP_ENCRYPTION_KEY_')) {
                $this->cipherTool = new PhpEncryption(_PHP_ENCRYPTION_KEY_);
            } else {
                $this->cipherTool = new Blowfish(_COOKIE_KEY_, _COOKIE_IV_);
            }
        }
    }

    /**
     * Encrypt plaintext
     *
     * @param string $content
     *
     * @return bool|string ciphertext
     */
    public function encrypt($content)
    {
        return $this->cipherTool->encrypt($content);
    }

    /**
     * Decrypt ciphertext
     *
     * @param string $content
     *
     * @return string plaintext
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function decrypt($content)
    {
        return $this->cipherTool->decrypt($content);
    }
}
