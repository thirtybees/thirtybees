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

use Detection\MobileDetect;

/**
 * Class ContextCore
 */
class ContextCore
{
    /** @var int */
    const DEVICE_COMPUTER = 1;
    /** @var int */
    const DEVICE_TABLET = 2;
    /** @var int */
    const DEVICE_MOBILE = 4;
    /** @var int */
    const MODE_STD = 1;
    /** @var int */
    const MODE_STD_CONTRIB = 2;
    /** @var int */
    const MODE_HOST_CONTRIB = 4;
    /** @var int */
    const MODE_HOST = 8;
    /* @var Context */
    protected static $instance;
    /** @var Cart */
    public $cart;
    /** @var Customer */
    public $customer;
    /** @var Cookie */
    public $cookie;
    /** @var Link */
    public $link;
    /** @var Country */
    public $country;
    /** @var Employee */
    public $employee;
    /** @var Controller */
    public $controller;
    /** @var string */
    public $override_controller_name_for_translations;
    /** @var Language */
    public $language;
    /** @var Currency */
    public $currency;
    /** @var AdminTab */
    public $tab;
    /** @var Shop */
    public $shop;
    /** @var Theme */
    public $theme;
    /** @var Smarty */
    public $smarty;
    /** @var MobileDetect */
    public $mobile_detect;
    /** @var int */
    public $mode = self::MODE_STD;
    /**
     * Mobile device of the customer
     *
     * @var bool|null
     */
    protected $mobile_device = null;
    /** @var bool|null */
    protected $is_mobile = null;
    /** @var bool|null */
    protected $is_tablet = null;

    /**
     * @param Context $testInstance Unit testing purpose only
     */
    public static function setInstanceForTesting($testInstance)
    {
        static::$instance = $testInstance;
    }

    /**
     * Unit testing purpose only
     *
     * @return void
     */
    public static function deleteTestingInstance()
    {
        static::$instance = null;
    }

    /**
     * Sets mobile_device context variable
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function getMobileDevice()
    {
        if ($this->mobile_device === null) {
            $this->mobile_device = false;
            if ($this->checkMobileContext()) {
                if (isset(Context::getContext()->cookie->no_mobile) && Context::getContext()->cookie->no_mobile == false && (int) Configuration::get('PS_ALLOW_MOBILE_DEVICE') != 0) {
                    $this->mobile_device = true;
                } else {
                    switch ((int) Configuration::get('PS_ALLOW_MOBILE_DEVICE')) {
                        case 1: // Only for mobile device
                            if ($this->isMobile() && !$this->isTablet()) {
                                $this->mobile_device = true;
                            }
                            break;
                        case 2: // Only for touchpads
                            if ($this->isTablet() && !$this->isMobile()) {
                                $this->mobile_device = true;
                            }
                            break;
                        case 3: // For touchpad or mobile devices
                            if ($this->isMobile() || $this->isTablet()) {
                                $this->mobile_device = true;
                            }
                            break;
                    }
                }
            }
        }

        return $this->mobile_device;
    }

    /**
     * Get a singleton instance of Context object
     *
     * @return Context
     */
    public static function getContext()
    {
        if (!isset(static::$instance)) {
            static::$instance = new Context();
        }

        return static::$instance;
    }

    /**
     * Checks if visitor's device is a mobile device
     *
     * @return bool
     */
    public function isMobile()
    {
        if ($this->is_mobile === null) {
            try {
                $this->is_mobile = $this->getMobileDetect()->isMobile();
            } catch (Throwable $e) {
                $this->is_mobile = false;
            }
        }

        return $this->is_mobile;
    }

    /**
     * Sets Mobile_Detect tool object
     *
     * @return MobileDetect
     */
    public function getMobileDetect()
    {
        if ($this->mobile_detect === null) {
            $this->mobile_detect = new MobileDetect();
        }

        return $this->mobile_detect;
    }

    /**
     * Checks if visitor's device is a tablet device
     *
     * @return bool
     */
    public function isTablet()
    {
        if ($this->is_tablet === null) {
            try {
                $this->is_tablet = $this->getMobileDetect()->isTablet();
            } catch (Throwable $e) {
                $this->is_tablet = false;
            }
        }

        return $this->is_tablet;
    }

    /**
     * Returns mobile device type
     *
     * @return int
     */
    public function getDevice()
    {
        static $device = null;

        if ($device === null) {
            if ($this->isTablet()) {
                $device = Context::DEVICE_TABLET;
            } elseif ($this->isMobile()) {
                $device = Context::DEVICE_MOBILE;
            } else {
                $device = Context::DEVICE_COMPUTER;
            }
        }

        return $device;
    }

    /**
     * Clone current context object
     *
     * @return Context
     */
    public function cloneContext()
    {
        /** @var Context $this */
        return clone($this);
    }

    /**
     * Checks if mobile context is possible
     *
     * Returns true, if current theme supports dedicated mobile variant, and user did not
     * opt out from using it
     *
     * @return bool
     * @throws PrestaShopException
     */
    protected function checkMobileContext()
    {
        return $this->theme->supportsMobileVariant() && !$this->cookie->no_mobile;
    }


}
