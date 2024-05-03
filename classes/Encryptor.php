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

/**
 * Class EncryptorCore
 */
class EncryptorCore
{
    const ALGO_BLOWFISH = 0;
    const ALGO_PHP_ENCRYPTION = 2;

    /**
     * @var Blowfish|PhpEncryption cipher tool instance
     */
    protected $cipherTool;

    /**
     * @var Encryptor $instance
     */
    protected static $instance;

    /**
     * @var Encryptor[] $standalone
     */
    protected static $standalone;

    /**
     * Encryptor singleton
     *
     * @return Encryptor instance
     */
    public static function getInstance()
    {
        if (!static::$instance) {
            static::$instance = new Encryptor(static::getCipherTool());
        }
        return static::$instance;
    }

    /**
     * Encryptor singleton for standalone
     *
     * This encryptor is used in special situations when encryption settings is not
     * set up yet. For example during installation
     *
     * @param string $salt
     *
     * @return Encryptor instance
     *
     * @throws PrestaShopException
     */
    public static function getStandaloneInstance($salt)
    {
        if (! static::$standalone[$salt]) {
            static::$standalone[$salt] = new Encryptor(static::getStandaloneCipherTool($salt));
        }

        return static::$standalone[$salt];
    }

    /**
     * Creates encryptor instance
     *
     * @param Blowfish|PhpEncryption $cipherTool optional cipher tool to use
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
            Configuration::updateValue('PS_CIPHER_ALGORITHM', static::ALGO_PHP_ENCRYPTION);
            return static::ALGO_PHP_ENCRYPTION;
        } catch (Throwable $e) {
            trigger_error("Failed to resolve encryption algorithm: " . $e);
            return static::ALGO_PHP_ENCRYPTION;
        }
    }

    /**
     * Returns ciphering tool according to settings
     *
     * @return Blowfish|PhpEncryption
     */
    private static function getCipherTool()
    {
        $algo = static::getAlgorithm();

        if ($algo === static::ALGO_PHP_ENCRYPTION && static::supportsPhpEncryption()) {
            if (defined('_PHP_ENCRYPTION_KEY_')) {
                return new PhpEncryption(_PHP_ENCRYPTION_KEY_);
            } else {
                trigger_error('PHP Encryption can\'t be used because _PHP_ENCRYPTION_KEY_ constant is not defined. Using Blowfish encryption instead.', E_USER_WARNING);
            }
        }

        // fallback to blowfish
        if (defined('_COOKIE_KEY_') && defined('_COOKIE_IV_')) {
            return new Blowfish(_COOKIE_KEY_, _COOKIE_IV_);
        }

        die('Failed to construct cipher tool. Please install openssl extension.');
    }

    /**
     * Returns blowfish ciphering tool used in standalone environment
     *
     * @param string $salt
     *
     * @return Blowfish|PhpEncryption
     *
     * @throws PrestaShopException
     */
    private static function getStandaloneCipherTool($salt)
    {
        if (static::supportsPhpEncryption()) {
            try {
                $key = PhpEncryption::createKeyFromSalt($salt);
                return new PhpEncryption($key);
            } catch (Throwable $e) {
                throw new PrestaShopException("Failed to create standalone cipher tool from salt", 0, $e);
            }
        }

        return new Blowfish(str_pad('', 56, md5('ps'.$salt)), str_pad('', 56, md5('iv'.$salt)));
    }

    /**
     * Check if PhpEncryption can be used
     *
     * @return bool
     */
    public static function supportsPhpEncryption()
    {
        return extension_loaded('openssl') && function_exists('openssl_encrypt');
    }
}
