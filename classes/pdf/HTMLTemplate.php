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
 * Class HTMLTemplateCore
 *
 * @since 1.0.0
 */
abstract class HTMLTemplateCore
{
    // @codingStandardsIgnoreStart
    /** @var string $title */
    public $title;
    /** @var string $date */
    public $date;
    /** @var bool $available_in_your_account */
    public $available_in_your_account = true;
    /** @var Smarty */
    public $smarty;
    /** @var Shop */
    public $shop;
    // @codingStandardsIgnoreEnd

    /**
     * Returns the template's HTML header
     *
     * @return string HTML header
     * @throws Exception
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function getHeader()
    {
        $this->assignCommonHeaderData();

        return $this->smarty->fetch($this->getTemplate('header'));
    }

    /**
     * Returns the template's HTML footer
     *
     * @return string HTML footer
     *
     * @throws Exception
     * @throws PrestaShopException
     * @throws SmartyException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getFooter()
    {
        $shopAddress = $this->getShopAddress();

        $idShop = (int) $this->shop->id;

        $this->smarty->assign(
            [
                'available_in_your_account' => $this->available_in_your_account,
                'shop_address'              => $shopAddress,
                'shop_fax'                  => Configuration::get('PS_SHOP_FAX', null, null, $idShop),
                'shop_phone'                => Configuration::get('PS_SHOP_PHONE', null, null, $idShop),
                'shop_email'                => Configuration::get('PS_SHOP_EMAIL', null, null, $idShop),
                'free_text'                 => Configuration::get('PS_INVOICE_FREE_TEXT', (int) Context::getContext()->language->id, null, $idShop),
            ]
        );

        return $this->smarty->fetch($this->getTemplate('footer'));
    }

    /**
     * Returns the shop address
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    protected function getShopAddress()
    {
        $shopAddress = '';

        $shopAddressObj = $this->shop->getAddress();
        if (isset($shopAddressObj) && $shopAddressObj instanceof Address) {
            $shopAddress = AddressFormat::generateAddress($shopAddressObj, [], ' - ', ' ');
        }

        return $shopAddress;
    }

    /**
     * Returns the invoice logo
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    protected function getLogo()
    {
        $logo = '';

        $idShop = (int) $this->shop->id;

        if (Configuration::get('PS_LOGO_INVOICE', null, null, $idShop) != false && file_exists(_PS_IMG_DIR_.Configuration::get('PS_LOGO_INVOICE', null, null, $idShop))) {
            $logo = _PS_IMG_DIR_.Configuration::get('PS_LOGO_INVOICE', null, null, $idShop);
        } elseif (Configuration::get('PS_LOGO', null, null, $idShop) != false && file_exists(_PS_IMG_DIR_.Configuration::get('PS_LOGO', null, null, $idShop))) {
            $logo = _PS_IMG_DIR_.Configuration::get('PS_LOGO', null, null, $idShop);
        }

        return $logo;
    }

    /**
     * Assign common header data to smarty variables
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function assignCommonHeaderData()
    {
        $this->setShopId();
        $idShop = (int) $this->shop->id;
        $shopName = Configuration::get('PS_SHOP_NAME', null, null, $idShop);

        $pathLogo = $this->getLogo();

        $width = 0;
        $height = 0;
        if (!empty($pathLogo)) {
            list($width, $height) = getimagesize($pathLogo);
        }

        // Limit the height of the logo for the PDF render
        $maximumHeight = 100;
        if ($height > $maximumHeight) {
            $ratio = $maximumHeight / $height;
            $height *= $ratio;
            $width *= $ratio;
        }

        $this->smarty->assign(
            [
                'logo_path'       => $pathLogo,
                'img_ps_dir'      => 'http://'.Tools::getMediaServer(_PS_IMG_)._PS_IMG_,
                'img_update_time' => Configuration::get('PS_IMG_UPDATE_TIME'),
                'date'            => $this->date,
                'title'           => $this->title,
                'shop_name'       => $shopName,
                'shop_details'    => Configuration::get('PS_SHOP_DETAILS', null, null, (int) $idShop),
                'width_logo'      => $width,
                'height_logo'     => $height,
            ]
        );
    }

    /**
     * Assign hook data
     *
     * @param ObjectModel $object generally the object used in the constructor
     */
    public function assignHookData($object)
    {
        $template = ucfirst(str_replace('HTMLTemplate', '', get_class($this)));
        $hookName = 'displayPDF'.$template;

        $this->smarty->assign(
            [
                'HOOK_DISPLAY_PDF' => Hook::exec($hookName, ['object' => $object]),
            ]
        );
    }

    /**
     * Returns the template's HTML content
     *
     * @return string HTML content
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    abstract public function getContent();

    /**
     * Returns the template filename
     *
     * @return string filename
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    abstract public function getFilename();

    /**
     * Returns the template filename when using bulk rendering
     *
     * @return string filename
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    abstract public function getBulkFilename();

    /**
     * If the template is not present in the theme directory, it will return the default template
     * in _PS_PDF_DIR_ directory
     *
     * @param $templateName
     *
     * @return string
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    protected function getTemplate($templateName)
    {
        $template = false;
        $defaultTemplate = rtrim(_PS_PDF_DIR_, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$templateName.'.tpl';
        $overriddenTemplate = _PS_ALL_THEMES_DIR_.$this->shop->getTheme().DIRECTORY_SEPARATOR.'pdf'.DIRECTORY_SEPARATOR.$templateName.'.tpl';
        if (file_exists($overriddenTemplate)) {
            $template = $overriddenTemplate;
        } elseif (file_exists($defaultTemplate)) {
            $template = $defaultTemplate;
        }

        return $template;
    }

    /**
     * Translation method
     *
     * @param string $string
     *
     * @return string translated text
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    protected static function l($string)
    {
        return Translate::getPdfTranslation($string);
    }

    /**
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    protected function setShopId()
    {
        if (isset($this->order) && Validate::isLoadedObject($this->order)) {
            $idShop = (int) $this->order->id_shop;
        } else {
            $idShop = (int) Context::getContext()->shop->id;
        }

        $this->shop = new Shop($idShop);
        if (Validate::isLoadedObject($this->shop)) {
            Shop::setContext(Shop::CONTEXT_SHOP, (int) $this->shop->id);
        }
    }

    /**
     * Returns the template's HTML pagination block
     *
     * @return string HTML pagination block
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function getPagination()
    {
        return $this->smarty->fetch($this->getTemplate('pagination'));
    }
}
