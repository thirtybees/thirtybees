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
 * Class ValidateCore
 *
 * @since 1.0.0
 */
class ValidateCore
{
    const ADMIN_PASSWORD_LENGTH = 8;
    const PASSWORD_LENGTH = 5;

    /**
     * @param string $ip
     *
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isIp2Long($ip)
    {
        return (bool) preg_match('#^-?[0-9]+$#', (string) $ip);
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isAnything()
    {
        return true;
    }

    /**
     * Check for e-mail validity
     *
     * @param string $email e-mail address to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     *
     * @since 1.0.2 For validation we now use a new `Swift_Message` instance
     *        to automatically check for RFC 2822 compliance, because
     *        that is the only RFC supported by thirty bees
     */
    public static function isEmail($email)
    {
        // Convert to IDN first if necessary
        if (mb_detect_encoding($email, 'UTF-8', true) && mb_strpos($email, '@') > -1) {
            // Convert to IDN
            list ($local, $domain) = explode('@', $email, 2);
            $domain = Tools::utf8ToIdn($domain);
            $email = "$local@$domain";
        }

        try {
            \Swift_Message::newInstance()->setFrom($email);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check for module URL validity
     *
     * @param string $url    module URL to validate
     * @param array  $errors Reference array for catching errors
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isModuleUrl($url, &$errors)
    {
        if (!$url || $url == 'http://' || $url == 'https://') {
            $errors[] = Tools::displayError('Please specify module URL');
        } elseif (substr($url, -4) != '.tar' && substr($url, -4) != '.zip' && substr($url, -4) != '.tgz' && substr($url, -7) != '.tar.gz') {
            $errors[] = Tools::displayError('Unknown archive type');
        } else {
            if ((strpos($url, 'http')) === false) {
                $url = 'http://'.$url;
            }
            if (!static::isAbsoluteUrl($url)) {
                $errors[] = Tools::displayError('Invalid URL');
            }
        }
        if (!is_array($errors) || !count($errors)) {
            return true;
        }

        return false;
    }

    /**
     * Check for MD5 string validity
     *
     * @param string $md5 MD5 string to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isMd5($md5)
    {
        return (bool) preg_match('/^[a-f0-9A-F]{32}$/', $md5);
    }

    /**
     * Check for SHA1 string validity
     *
     * @param string $sha1 SHA1 string to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isSha1($sha1)
    {
        return (bool) preg_match('/^[a-fA-F0-9]{40}$/', $sha1);
    }

    /**
     * @param $float
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isUnsignedFloat($float)
    {
        return strval((float) $float) == strval($float) && $float >= 0;
    }

    /**
     * Check for a float number validity
     *
     * @param float $float Float number to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isOptFloat($float)
    {
        return empty($float) || static::isFloat($float);
    }

    /**
     * Check for a float number validity
     *
     * @param float $float Float number to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isFloat($float)
    {
        return strval((float) $float) == strval($float);
    }

    /**
     * Check for a carrier name validity
     *
     * @param string $name Carrier name to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isCarrierName($name)
    {
        return empty($name) || preg_match(Tools::cleanNonUnicodeSupport('/^[^<>;=#{}]*$/u'), $name);
    }

    /**
     * Check for an image size validity
     *
     * @param string $size Image size to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isImageSize($size)
    {
        return (bool) preg_match('/^[0-9]{1,4}$/', $size);
    }

    /**
     * Check for name validity
     *
     * @param string $name Name to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isName($name)
    {
        return (bool) preg_match(Tools::cleanNonUnicodeSupport('/^[^0-9!<>,;?()@"°{}_$%:]*$/u'), stripslashes($name));
    }

    /**
     * Check for hook name validity
     *
     * @param string $hook Hook name to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isHookName($hook)
    {
        return (bool) preg_match('/^[a-zA-Z0-9\\\_-]+$/', $hook);
    }

    /**
     * Check for sender name validity
     *
     * @param string $mailName Sender name to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isMailName($mailName)
    {
        return (is_string($mailName) && preg_match(Tools::cleanNonUnicodeSupport('/^[^<>;=#{}]*$/u'), $mailName));
    }

    /**
     * Check for e-mail subject validity
     *
     * @param string $mailSubject e-mail subject to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isMailSubject($mailSubject)
    {
        return (bool) preg_match(Tools::cleanNonUnicodeSupport('/^[^<>]*$/u'), $mailSubject);
    }

    /**
     * Check for module name validity
     *
     * @param string $moduleName Module name to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isModuleName($moduleName)
    {
        return (is_string($moduleName) && preg_match('/^[a-zA-Z0-9_-]+$/', $moduleName));
    }

    /**
     * Check for template name validity
     *
     * @param string $tplName Template name to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isTplName($tplName)
    {
        return (bool) preg_match('/^[a-zA-Z0-9_-]+$/', $tplName);
    }

    /**
     * Check for image type name validity
     *
     * @param string $type Image type name to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isImageTypeName($type)
    {
        return (bool) preg_match('/^[a-zA-Z0-9_ -]+$/', $type);
    }

    /**
     * Check for price validity
     *
     * @param string $price Price to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isPrice($price)
    {
        return (bool) preg_match('/^[0-9]{1,10}(\.[0-9]{1,9})?$/', $price);
    }

    /**
     * Check for price validity (including negative price)
     *
     * @param string $price Price to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isNegativePrice($price)
    {
        return (bool) preg_match('/^[-]?[0-9]{1,10}(\.[0-9]{1,9})?$/', $price);
    }

    /**
     * Check for language code (ISO) validity
     *
     * @param string $isoCode Language code (ISO) to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isLanguageIsoCode($isoCode)
    {
        return (bool) preg_match('/^[a-zA-Z]{2,3}$/', $isoCode);
    }

    /**
     * @param $s
     *
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isLanguageCode($s)
    {
        return (bool) preg_match('/^[a-zA-Z]{2}(-[a-zA-Z]{2})?$/', $s);
    }

    /**
     * @param string $isoCode
     *
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isStateIsoCode($isoCode)
    {
        return (bool) preg_match('/^[a-zA-Z0-9]{1,4}((-)[a-zA-Z0-9]{1,4})?$/', $isoCode);
    }

    /**
     * @param string $isoCode
     *
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isNumericIsoCode($isoCode)
    {
        return (bool) preg_match('/^[0-9]{2,3}$/', $isoCode);
    }

    /**
     * Check for voucher name validity
     *
     * @param string $voucher voucher to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isDiscountName($voucher)
    {
        return (bool) preg_match(Tools::cleanNonUnicodeSupport('/^[^!<>,;?()@"°{}_$%:]{3,32}$/u'), $voucher);
    }

    /**
     * Check for product or category name validity
     *
     * @param string $name Product or category name to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isCatalogName($name)
    {
        return (bool) preg_match(Tools::cleanNonUnicodeSupport('/^[^<>;{}]*$/u'), $name);
    }

    /**
     * Check for a message validity
     *
     * @param string $message Message to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isMessage($message)
    {
        return !preg_match('/[<>{}]/i', $message);
    }

    /**
     * Check for a country name validity
     *
     * @param string $name Country name to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isCountryName($name)
    {
        return (bool) preg_match('/^[a-zA-Z -]+$/', $name);
    }

    /**
     * Check for a link (url-rewriting only) validity
     *
     * @param string $link Link to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function isLinkRewrite($link)
    {
        if (Configuration::get('PS_ALLOW_ACCENTED_CHARS_URL')) {
            return (bool) preg_match(Tools::cleanNonUnicodeSupport('/^[_a-zA-Z0-9\pL\pS-]+$/u'), $link);
        }

        return (bool) preg_match('/^[_a-zA-Z0-9\-]+$/', $link);
    }

    /**
     * Check for a route pattern validity
     *
     * @param string $pattern to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function isRoutePattern($pattern)
    {
        if (Configuration::get('PS_ALLOW_ACCENTED_CHARS_URL')) {
            return (bool) preg_match(Tools::cleanNonUnicodeSupport('/^[_a-zA-Z0-9\(\)\.{}:\/\pL\pS-]+$/u'), $pattern);
        }

        return (bool) preg_match('/^[_a-zA-Z0-9\(\)\.{}:\/\-]+$/', $pattern);
    }

    /**
     * Check for a postal address validity
     *
     * @param string $address Address to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isAddress($address)
    {
        return empty($address) || preg_match(Tools::cleanNonUnicodeSupport('/^[^!<>?=+@{}_$%]*$/u'), $address);
    }

    /**
     * Check for city name validity
     *
     * @param string $city City name to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isCityName($city)
    {
        return (bool) preg_match(Tools::cleanNonUnicodeSupport('/^[^!<>;?=+@#"°{}_$%]*$/u'), $city);
    }

    /**
     * Check for search query validity
     *
     * @param string $search Query to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isValidSearch($search)
    {
        return (bool) preg_match(Tools::cleanNonUnicodeSupport('/^[^<>;=#{}]{0,64}$/u'), $search);
    }

    /**
     * Check for HTML field validity (no XSS please !)
     *
     * @param string $html HTML field to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isCleanHtml($html, $allowIframe = false)
    {
        $events = 'onmousedown|onmousemove|onmmouseup|onmouseover|onmouseout|onload|onunload|onfocus|onblur|onchange';
        $events .= '|onsubmit|ondblclick|onclick|onkeydown|onkeyup|onkeypress|onmouseenter|onmouseleave|onerror|onselect|onreset|onabort|ondragdrop|onresize|onactivate|onafterprint|onmoveend';
        $events .= '|onafterupdate|onbeforeactivate|onbeforecopy|onbeforecut|onbeforedeactivate|onbeforeeditfocus|onbeforepaste|onbeforeprint|onbeforeunload|onbeforeupdate|onmove';
        $events .= '|onbounce|oncellchange|oncontextmenu|oncontrolselect|oncopy|oncut|ondataavailable|ondatasetchanged|ondatasetcomplete|ondeactivate|ondrag|ondragend|ondragenter|onmousewheel';
        $events .= '|ondragleave|ondragover|ondragstart|ondrop|onerrorupdate|onfilterchange|onfinish|onfocusin|onfocusout|onhashchange|onhelp|oninput|onlosecapture|onmessage|onmouseup|onmovestart';
        $events .= '|onoffline|ononline|onpaste|onpropertychange|onreadystatechange|onresizeend|onresizestart|onrowenter|onrowexit|onrowsdelete|onrowsinserted|onscroll|onsearch|onselectionchange';
        $events .= '|onselectstart|onstart|onstop';

        if (preg_match('/<[\s]*script/ims', $html) || preg_match('/('.$events.')[\s]*=/ims', $html) || preg_match('/.*script\:/ims', $html)) {
            return false;
        }

        if (!$allowIframe && preg_match('/<[\s]*(i?frame|form|input|embed|object)/ims', $html)) {
            return false;
        }

        return true;
    }

    /**
     * Check for product reference validity
     *
     * @param string $reference Product reference to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isReference($reference)
    {
        return (bool) preg_match(Tools::cleanNonUnicodeSupport('/^[^<>;={}]*$/u'), $reference);
    }

    /**
     * @param string $plainTextPassword
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isPasswdAdmin($plainTextPassword)
    {
        return static::isPasswd($plainTextPassword, static::ADMIN_PASSWORD_LENGTH);
    }

    /**
     * Check for password validity
     *
     * @param string $plainTextPassword Password to validate
     * @param int    $size
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isPasswd($plainTextPassword, $size = self::PASSWORD_LENGTH)
    {
        return (mb_strlen($plainTextPassword) >= $size && mb_strlen($plainTextPassword) < 255);
    }

    /**
     * Check for configuration key validity
     *
     * @param string $configName Configuration key to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isConfigName($configName)
    {
        return (bool) preg_match('/^[a-zA-Z_0-9-]+$/', $configName);
    }

    /**
     * Check date formats like http://php.net/manual/en/function.date.php
     *
     * @param string $dateFormat date format to check
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isPhpDateFormat($dateFormat)
    {
        // We can't really check if this is valid or not, because this is a string and you can write whatever you want in it.
        // That's why only < et > are forbidden (HTML)
        return (bool) preg_match('/^[^<>]+$/', $dateFormat);
    }

    /**
     * Check for date format
     *
     * @param string $date Date to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isDateFormat($date)
    {
        return (bool) preg_match('/^([0-9]{4})-((0?[0-9])|(1[0-2]))-((0?[0-9])|([1-2][0-9])|(3[01]))( [0-9]{2}:[0-9]{2}:[0-9]{2})?$/', $date);
    }

    /**
     * Check for date validity
     *
     * @param string $date Date to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isDate($date)
    {
        if (!preg_match('/^([0-9]{4})-((?:0?[0-9])|(?:1[0-2]))-((?:0?[0-9])|(?:[1-2][0-9])|(?:3[01]))( [0-9]{2}:[0-9]{2}:[0-9]{2})?$/', $date, $matches)) {
            return false;
        }

        foreach ([1, 2, 3] as $i) {
            $matches[$i] = (int) $matches[$i];
        }

        return ($matches[1] === 0 && $matches[2] === 0 && $matches[3] === 0)
               || checkdate($matches[2], $matches[3], $matches[1]);
    }

    /**
     * Check for birthDate validity
     *
     * @param string $date birthdate to validate
     *
     * @return bool Validity is ok or not
     *
     * @since 1.0.0 Changed date in future check
     */
    public static function isBirthDate($date)
    {
        if (empty($date) || $date == '0000-00-00') {
            return true;
        }
        if (preg_match('/^([0-9]{4})-((?:0?[1-9])|(?:1[0-2]))-((?:0?[1-9])|(?:[1-2][0-9])|(?:3[01]))([0-9]{2}:[0-9]{2}:[0-9]{2})?$/', $date, $birthDate)) {
            if (date('Y-m-d', strtotime($date)) > date('Y-m-d')) {
                // Reject dates in the future
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Check for boolean validity
     *
     * @param bool $bool Boolean to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isBool($bool)
    {
        return $bool === null || is_bool($bool) || preg_match('/^(0|1)$/', $bool);
    }

    /**
     * Check for phone number validity
     *
     * @param string $number Phone number to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isPhoneNumber($number)
    {
        return (bool) preg_match('/^[+0-9. ()-]*$/', $number);
    }

    /**
     * Check for barcode validity (EAN-13)
     *
     * @param string $ean13 Barcode to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isEan13($ean13)
    {
        return !$ean13 || preg_match('/^[0-9]{0,13}$/', $ean13);
    }

    /**
     * Check for barcode validity (UPC)
     *
     * @param string $upc Barcode to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isUpc($upc)
    {
        return !$upc || preg_match('/^[0-9]{0,12}$/', $upc);
    }

    /**
     * Check for postal code validity
     *
     * @param string $postcode Postal code to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isPostCode($postcode)
    {
        return empty($postcode) || preg_match('/^[a-zA-Z 0-9-]+$/', $postcode);
    }

    /**
     * Check for zip code format validity
     *
     * @param string $zipCode zip code format to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isZipCodeFormat($zipCode)
    {
        if (!empty($zipCode)) {
            return (bool) preg_match('/^[NLCnlc 0-9-]+$/', $zipCode);
        }

        return true;
    }

    /**
     * Check for table or identifier validity
     * Mostly used in database for ordering : ASC / DESC
     *
     * @param string $way Keyword to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isOrderWay($way)
    {
        return ($way === 'ASC' | $way === 'DESC' | $way === 'asc' | $way === 'desc');
    }

    /**
     * Check for table or identifier validity
     * Mostly used in database for ordering : ORDER BY field
     *
     * @param string $order Field to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isOrderBy($order)
    {
        return (bool) preg_match('/^[a-zA-Z0-9.!_-]+$/', $order);
    }

    /**
     * Check for table or identifier validity
     * Mostly used in database for table names and id_table
     *
     * @param string $table Table/identifier to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isTableOrIdentifier($table)
    {
        return (bool) preg_match('/^[a-zA-Z0-9_-]+$/', $table);
    }

    /**
     * @deprecated 1.0.0 You should not use list like this, please use an array when you build a SQL query
     */
    public static function isValuesList()
    {
        Tools::displayAsDeprecated();

        return true;
        /* For history reason, we keep this line */
        // return preg_match('/^[0-9,\'(). NULL]+$/', $list);
    }

    /**
     * Check for tags list validity
     *
     * @param string $list List to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isTagsList($list)
    {
        return (bool) preg_match(Tools::cleanNonUnicodeSupport('/^[^!<>;?=+#"°{}_$%]*$/u'), $list);
    }

    /**
     * Check for product visibility
     *
     * @param string $s visibility to check
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isProductVisibility($s)
    {
        return (bool) preg_match('/^both|catalog|search|none$/i', $s);
    }

    /**
     * Check for an integer validity
     *
     * @param int $value Integer to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isInt($value)
    {
        return ((string) (int) $value === (string) $value || $value === false);
    }

    /**
     * Check for an percentage validity (between 0 and 100)
     *
     * @param float $value Float to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isPercentage($value)
    {
        return (static::isFloat($value) && $value >= 0 && $value <= 100);
    }

    public static function isNullOrUnsignedId($id)
    {
        return $id === null || static::isUnsignedId($id);
    }

    /**
     * Check for an integer validity (unsigned)
     * Mostly used in database for auto-increment
     *
     * @param int $id Integer to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isUnsignedId($id)
    {
        return static::isUnsignedInt($id); /* Because an id could be equal to zero when there is no association */
    }

    /**
     * Check for an integer validity (unsigned)
     *
     * @param int $value Integer to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isUnsignedInt($value)
    {
        return ((string) (int) $value === (string) $value && $value < 4294967296 && $value >= 0);
    }

    /**
     * Check object validity
     *
     * @param mixed $object Object to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isLoadedObject($object)
    {
        return is_object($object) && $object->id;
    }

    /**
     * Check object validity
     *
     * @param int $object Object to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isColor($color)
    {
        return (bool) preg_match('/^(#[0-9a-fA-F]{6}|[a-zA-Z0-9-]*)$/', $color);
    }

    /**
     * Check tracking number validity (disallowed empty string)
     *
     * @param string $trackingNumber Tracking number to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isTrackingNumber($trackingNumber)
    {
        return (bool) preg_match('/^[~:#,%&_=\(\)\[\]\.\? \+\-@\/a-zA-Z0-9]+$/', $trackingNumber);
    }

    /**
     * Check url validity (allowed empty string)
     *
     * @param string $url Url to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isUrlOrEmpty($url)
    {
        return empty($url) || static::isUrl($url);
    }

    /**
     * Check url validity (disallowed empty string)
     *
     * @param string $url Url to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isUrl($url)
    {
        if (!static::isAbsoluteUrl($url)) {
            return (bool) preg_match(Tools::cleanNonUnicodeSupport('/^[~:#,$%&_=\(\)\.\? \+\-@\/a-zA-Z0-9\pL\pS-]+$/u'), $url);
        }

        return true;
    }

    /**
     * Check if URL is absolute
     *
     * @param string $url URL to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isAbsoluteUrl($url)
    {
        return (bool) filter_var($url, FILTER_VALIDATE_URL);
    }

    /**
     * @param $engine
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isMySQLEngine($engine)
    {
        return (in_array($engine, ['InnoDB', 'MyISAM']));
    }

    /**
     * @param $data
     *
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isUnixName($data)
    {
        return (bool) preg_match(Tools::cleanNonUnicodeSupport('/^[a-z0-9\._-]+$/ui'), $data);
    }

    /**
     * @param $data
     *
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isTablePrefix($data)
    {
        // Even if "-" is theorically allowed, it will be considered a syntax error if you do not add backquotes (`) around the table name
        return (bool) preg_match(Tools::cleanNonUnicodeSupport('/^[a-z0-9_]+$/ui'), $data);
    }

    /**
     * Check for standard name file validity
     *
     * @param string $name Name to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isFileName($name)
    {
        return (bool) preg_match('/^[a-zA-Z0-9_.-]+$/', $name);
    }

    /**
     * Check for standard name directory validity
     *
     * @param string $dir Directory to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isDirName($dir)
    {
        return (bool) preg_match('/^[a-zA-Z0-9_.-]*$/', $dir);
    }

    /**
     * Check for admin panel tab name validity
     *
     * @param string $name Name to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isTabName($name)
    {
        return (bool) preg_match(Tools::cleanNonUnicodeSupport('/^[^<>]+$/u'), $name);
    }

    /**
     * @param string $unit
     *
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isWeightUnit($unit)
    {
        return (static::isGenericName($unit) & (mb_strlen($unit) < 5));
    }

    /**
     * Check for standard name validity
     *
     * @param string $name Name to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isGenericName($name)
    {
        return empty($name) || preg_match(Tools::cleanNonUnicodeSupport('/^[^<>={}]*$/u'), $name);
    }

    /**
     * @param $unit
     *
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isDistanceUnit($unit)
    {
        return (static::isGenericName($unit) & (mb_strlen($unit) < 5));
    }

    /**
     * @param $domain
     *
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isSubDomainName($domain)
    {
        return (bool) preg_match('/^[a-zA-Z0-9-_]*$/', $domain);
    }

    /**
     * @param $text
     *
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isVoucherDescription($text)
    {
        return (bool) preg_match('/^([^<>{}]|<br \/>)*$/i', $text);
    }

    /**
     * Check if the value is a sort direction value (DESC/ASC)
     *
     * @param string $value
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isSortDirection($value)
    {
        return ($value !== null && ($value === 'ASC' || $value === 'DESC'));
    }

    /**
     * Customization fields' label validity
     *
     * @param string $label
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isLabel($label)
    {
        return (preg_match(Tools::cleanNonUnicodeSupport('/^[^{}<>]*$/u'), $label));
    }

    /**
     * Price display method validity
     *
     * @param int $data Data to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isPriceDisplayMethod($data)
    {
        return ($data == PS_TAX_EXC || $data == PS_TAX_INC);
    }

    /**
     * @param string $dni to validate
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isDniLite($dni)
    {
        return empty($dni) || (bool) preg_match('/^[0-9A-Za-z-.]{1,16}$/U', $dni);
    }

    /**
     * Check if $data is a PrestaShop cookie object
     *
     * @param mixed $data to validate
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isCookie($data)
    {
        return (is_object($data) && get_class($data) == 'Cookie');
    }

    /**
     * Price display method validity
     *
     * @param string $data Data to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isString($data)
    {
        return is_string($data);
    }

    /**
     * Check if the data is a reduction type (amout or percentage)
     *
     * @param string $data Data to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isReductionType($data)
    {
        return ($data === 'amount' || $data === 'percentage');
    }

    /**
     * @deprecated 1.0.0 Use static::isBoolId()
     */
    public static function isBool_Id($ids)
    {
        Tools::displayAsDeprecated();

        return static::isBoolId($ids);
    }

    /**
     * Check for bool_id
     *
     * @param string $ids
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isBoolId($ids)
    {
        return (bool) preg_match('#^[01]_[0-9]+$#', $ids);
    }

    /**
     * Check the localization pack part selected
     *
     * @param string $data Localization pack to check
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isLocalizationPackSelection($data)
    {
        return in_array((string) $data, ['states', 'taxes', 'currencies', 'languages', 'units', 'groups']);
    }

    /**
     * Check for PHP serialized data
     *
     * @param string $data Serialized data to validate
     *
     * @return bool Validity is ok or not
     *
     * @deprecated Use the generally safer JSON format instead of serialize().
     *
     * @since      1.0.0
     * @version    1.0.0 Initial version
     * @deprecated 1.0.5
     */
    public static function isSerializedArray($data)
    {
        return $data === null || (is_string($data) && preg_match('/^a:[0-9]+:{.*;}$/s', $data));
    }

    /**
     * Check for JSON encoded data.
     *
     * @param string $data JSON encoded data to validate.
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.4
     * @version 1.0.4 Initial version
     */
    public static function isJSON($data)
    {
        json_decode($data);

        return (json_last_error() === JSON_ERROR_NONE);
    }

    /**
     * Check for Latitude/Longitude
     *
     * @param string $data Coordinate to validate
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isCoordinate($data)
    {
        return $data === null || preg_match('/^\-?[0-9]{1,8}\.[0-9]{1,8}$/s', $data);
    }

    /**
     * Check for Language Iso Code
     *
     * @param string $isoCode
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isLangIsoCode($isoCode)
    {
        return (bool) preg_match('/^[a-zA-Z]{2,3}$/s', $isoCode);
    }

    /**
     * Check for Language File Name
     *
     * @param string $fileName
     *
     * @return bool Validity is ok or not
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isLanguageFileName($fileName)
    {
        return (bool) preg_match('/^[a-zA-Z]{2,3}\.(?:gzip|tar\.gz)$/s', $fileName);
    }

    /**
     *
     * @param array $ids
     *
     * @return bool return true if the array contain only unsigned int value
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isArrayWithIds($ids)
    {
        if (count($ids)) {
            foreach ($ids as $id) {
                if ($id == 0 || !static::isUnsignedInt($id)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     *
     * @param array $zones
     *
     * @return bool return true if array contain all value required for an image map zone
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isSceneZones($zones)
    {
        foreach ($zones as $zone) {
            if (!isset($zone['x1']) || !static::isUnsignedInt($zone['x1'])) {
                return false;
            }
            if (!isset($zone['y1']) || !static::isUnsignedInt($zone['y1'])) {
                return false;
            }
            if (!isset($zone['width']) || !static::isUnsignedInt($zone['width'])) {
                return false;
            }
            if (!isset($zone['height']) || !static::isUnsignedInt($zone['height'])) {
                return false;
            }
            if (!isset($zone['id_product']) || !static::isUnsignedInt($zone['id_product'])) {
                return false;
            }
        }

        return true;
    }

    /**
     *
     * @param array $stock_management
     *
     * @return bool return true if is a valide stock management
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isStockManagement($stock_management)
    {
        if (!in_array($stock_management, ['WA', 'FIFO', 'LIFO'])) {
            return false;
        }

        return true;
    }

    /**
     * Validate SIRET Code
     *
     * @param string $siret SIRET Code
     *
     * @return bool Return true if is valid
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isSiret($siret)
    {
        if (mb_strlen($siret) != 14) {
            return false;
        }
        $sum = 0;
        for ($i = 0; $i != 14; $i++) {
            $tmp = ((($i + 1) % 2) + 1) * intval($siret[$i]);
            if ($tmp >= 10) {
                $tmp -= 9;
            }
            $sum += $tmp;
        }

        return ($sum % 10 === 0);
    }

    /**
     * Validate APE Code
     *
     * @param string $ape APE Code
     *
     * @return bool Return true if is valid
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isApe($ape)
    {
        return (bool) preg_match('/^[0-9]{3,4}[a-zA-Z]{1}$/s', $ape);
    }

    /**
     * @param $name
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isControllerName($name)
    {
        return (bool) (is_string($name) && preg_match(Tools::cleanNonUnicodeSupport('/^[0-9a-zA-Z-_]*$/u'), $name));
    }

    /**
     * @param $version
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isPrestaShopVersion($version)
    {
        return (preg_match('/^[0-1]\.[0-9]{1,2}(\.[0-9]{1,2}){0,2}$/', $version) && ip2long($version));
    }

    /**
     * @param $id
     *
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function isOrderInvoiceNumber($id)
    {
        return (preg_match('/^(?:'.Configuration::get('PS_INVOICE_PREFIX', Context::getContext()->language->id).')\s*([0-9]+)$/i', $id));
    }
}
