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
 * Class RijndaelCore
 *
 * @since 1.0.0
 */
class RijndaelCore
{
    protected $_key;
    protected $_iv;

    /**
     * RijndaelCore constructor.
     *
     * @param string $key
     * @param string $iv
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function __construct($key, $iv)
    {
        $this->_key = $key;
        $this->_iv = base64_decode($iv);
    }

    /**
     * Base64 is not required, but it is be more compact than urlencode
     *
     * @param string $plaintext
     *
     * @return bool|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws Exception
     */
    public function encrypt($plaintext)
    {
        if (mb_strlen($this->_key, '8bit') !== 32) {
            return false;
        }

        if (function_exists('openssl_encrypt')) {
            $ivsize = openssl_cipher_iv_length('aes-256-cbc');
            $iv = openssl_random_pseudo_bytes($ivsize);
            $ciphertext = openssl_encrypt(
                $plaintext,
                'aes-256-cbc',
                $this->_key,
                OPENSSL_RAW_DATA,
                $iv
            );
        } else {
            $ivsize = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
            try {
                $iv = random_bytes($ivsize);
            } catch (Exception $e) {
                if (function_exists('mcrypt_create_iv')) {
                    $iv = mcrypt_create_iv($ivsize, MCRYPT_RAND);
                } elseif (function_exists('openssl_random_pseudo_bytes')) {
                    $iv = openssl_random_pseudo_bytes($ivsize);
                } else {
                    throw new Exception('No secure random number generator found on your system.');
                }
            }

            // Add PKCS7 Padding
            $block = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
            $pad = $block - (mb_strlen($plaintext, '8bit') % $block);
            $plaintext .= str_repeat(chr($pad), $pad);

            $ciphertext = mcrypt_encrypt(
                MCRYPT_RIJNDAEL_128,
                $this->_key,
                $plaintext,
                MCRYPT_MODE_CBC,
                $iv
            );
        }

        return $iv.$ciphertext;
    }

    /**
     * @param string $ciphertext
     *
     * @return string|false
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function decrypt($ciphertext)
    {
        if (mb_strlen($this->_key, '8bit') !== 32) {
            return false;
        }

        if (function_exists('openssl_decrypt')) {
            $ivsize = openssl_cipher_iv_length('aes-256-cbc');
            $iv = mb_substr($ciphertext, 0, $ivsize, '8bit');
            $ciphertext = mb_substr($ciphertext, $ivsize, null, '8bit');

            return openssl_decrypt(
                $ciphertext,
                'aes-256-cbc',
                $this->_key,
                OPENSSL_RAW_DATA,
                $iv
            );
        } else {
            $ivsize = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
            $iv = mb_substr($ciphertext, 0, $ivsize, '8bit');
            $ciphertext = mb_substr($ciphertext, $ivsize, null, '8bit');

            $plaintext = mcrypt_decrypt(
                MCRYPT_RIJNDAEL_128,
                $this->_key,
                $ciphertext,
                MCRYPT_MODE_CBC,
                $iv
            );
        }

        $len = mb_strlen($plaintext, '8bit');
        $pad = ord($plaintext[$len - 1]);
        $block = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
        if ($pad <= 0 || $pad > $block) {
            // Padding error!
            return false;
        }

        return mb_substr($plaintext, 0, $len - $pad, '8bit');
    }
}
