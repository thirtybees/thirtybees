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
 * Class EncryptorCore
 *
 * @since 1.0.1
 */
class EncryptorCore
{
    const ALGO_BLOWFISH = 0;
    const ALGO_PHP_ENCRYPTION = 2;

    /** @var Blowfish|PhpEncryption cipher tool instance */
    protected $cipherTool;

    /** @var Encryptor $instance */
    protected static $instance;

    /** @var Encryptor $standalone */
    protected static $standalone;

    /**
     * Encryptor singleton
     *
     * @return Encryptor instance
     * @throws PrestaShopException
     */
    public static function getInstance()
    {
        if (!static::$instance) {
            $cipherTool = static::getCipherTool();
            if (! $cipherTool) {
                // we need some ciphering capability to encode error message
                static::$instance = new Encryptor(static::getStandaloneCipherTool(__FILE__));
                throw new PrestaShopException('No encryption tool available');
            } else {
                static::$instance = new Encryptor($cipherTool);
            }
        }
        return static::$instance;
    }

    /**
     * Encryptor singleton for standalone
     *
     * This encryptor is used in special situations when encryption settings is not
     * set up yet. For example during installation
     *
     * @return Encryptor instance
     */
    public static function getStandaloneInstance($salt)
    {
        if (!static::$standalone) {
            static::$standalone = new Encryptor(static::getStandaloneCipherTool($salt));
        }

        return static::$standalone;
    }

    /**
     * Creates encryptor instance
     *
     * @param Blowfish|PhpEncryption optional cipher tool to use
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function __construct($cipherTool)
    {
        $this->cipherTool = $cipherTool;
    }

    /**
     * Encrypt plaintext
     *
     * @param string $content
     *
     * @return bool|string ciphertext
     * @throws PrestaShopException
     */
    public function encrypt($content)
    {
        try {
            return $this->cipherTool->encrypt($content);
        } catch (Throwable $e) {
            throw new PrestaShopException("Failed to encrypt content", 0, $e);
        }
    }

    /**
     * Decrypt ciphertext
     *
     * @param string $content
     *
     * @return string plaintext
     *
     * @throws PrestaShopException
     * @version 1.0.0 Initial version
     * @since   1.0.0
     */
    public function decrypt($content)
    {
        try {
            return $this->cipherTool->decrypt($content);
        } catch (Throwable $e) {
            throw new PrestaShopException("Failed to decrypt content", 0, $e);
        }
    }

    /**
     * Returns algorithm selected for encryption
     *
     * @return int
     */
    public static function getAlgorithm()
    {
        try {
            $algo = (int)Configuration::get('PS_CIPHER_ALGORITHM');
            if (in_array($algo, [static::ALGO_BLOWFISH, static::ALGO_PHP_ENCRYPTION])) {
                return $algo;
            }
            Configuration::updateValue('PS_CIPHER_ALGORITHM', static::ALGO_BLOWFISH);
            return static::ALGO_BLOWFISH;
        } catch (Throwable $e) {
            trigger_error("Failed to resolve encryption algorithm: " . $e);
            return static::ALGO_BLOWFISH;
        }
    }

    /**
     * Returns ciphering tool according to settings
     */
    private static function getCipherTool()
    {
        $algo = static::getAlgorithm();

        if ($algo === static::ALGO_PHP_ENCRYPTION && static::supportsPhpEncryption() && defined('_PHP_ENCRYPTION_KEY_')) {
            return new PhpEncryption(_PHP_ENCRYPTION_KEY_);
        }

        // fallback to blowfish
        if (defined('_COOKIE_KEY_') && defined('_COOKIE_IV_')) {
            return new Blowfish(_COOKIE_KEY_, _COOKIE_IV_);
        }

        return null;
    }

    /**
     * Returns blowfish ciphering tool used in standalone environment
     */
    private static function getStandaloneCipherTool($salt)
    {
        return new Blowfish(str_pad('', 56, md5('ps'.$salt)), str_pad('', 56, md5('iv'.$salt)));
    }

    /**
     * Check if PhpEncryption can be used
     *
     * @returns bool
     */
    public static function supportsPhpEncryption()
    {
        return extension_loaded('openssl') && function_exists('openssl_encrypt');
    }
}
