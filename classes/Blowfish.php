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

// TODO: remove global defines
define('PS_UNPACK_NATIVE', 1);
define('PS_UNPACK_MODIFIED', 2);

/**
 * Class BlowfishCore
 *
 * @since 1.0.0
 */
class BlowfishCore extends CryptBlowfish
{
    /**
     * @param $plaintext
     *
     * @return bool|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function encrypt($plaintext)
    {
        if (($length = strlen($plaintext)) >= 1048576) {
            return false;
        }

        $ciphertext = '';
        $paddedtext = $this->maxi_pad($plaintext);
        $strlen = strlen($paddedtext);
        for ($x = 0; $x < $strlen; $x += 8) {
            $piece = substr($paddedtext, $x, 8);
            $cipherPiece = parent::encrypt($piece);
            $encoded = base64_encode($cipherPiece);
            $ciphertext = $ciphertext.$encoded;
        }

        return $ciphertext.sprintf('%06d', $length);
    }

    /**
     * @param $plaintext
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function maxi_pad($plaintext)
    {
        $strLen = count($plaintext);
        $padLen = $strLen % 8;
        for ($x = 0; $x < $padLen; $x++) {
            $plaintext = $plaintext.' ';
        }

        return $plaintext;
    }

    /**
     * @param $ciphertext
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function decrypt($ciphertext)
    {
        $plainTextLength = intval(substr($ciphertext, -6));
        $ciphertext = substr($ciphertext, 0, -6);

        $plaintext = '';
        $chunks = explode('=', $ciphertext);
        $endingValue = count($chunks);
        for ($counter = 0; $counter < ($endingValue - 1); $counter++) {
            $chunk = $chunks[$counter].'=';
            $decoded = base64_decode($chunk);
            $piece = parent::decrypt($decoded);
            $plaintext = $plaintext.$piece;
        }

        return substr($plaintext, 0, $plainTextLength);
    }
}
