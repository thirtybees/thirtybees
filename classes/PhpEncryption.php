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
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;

/**
 * Class PhpEncryptionCore
 *
 * @since 1.0.0
 */
class PhpEncryptionCore
{
    protected $key;

    /**
     * PhpEncryptionCore constructor.
     *
     * @param string $asciiKey
     *
     * @throws \Defuse\Crypto\Exception\BadFormatException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @since 1.0.0
     */
    public function __construct($asciiKey)
    {
        $this->key = Key::loadFromAsciiSafeString($asciiKey);
    }

    /**
     * @param string $plaintext
     *
     * @return string Ciphertext
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    public function encrypt($plaintext)
    {
        return Crypto::encrypt($plaintext, $this->key);
    }

    /**
     * @param string $ciphertext
     *
     * @return string|null Plaintext
     */
    public function decrypt($ciphertext)
    {
        if (! is_string($ciphertext)) {
            return null;
        }

        try {
            return Crypto::decrypt($ciphertext, $this->key);
        } catch (Exception $exception) {
            return null;
        }
    }
}
