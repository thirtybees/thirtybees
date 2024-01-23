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
 * Class PDFCore
 */
class PDFCore
{

    /** @var string $filename */
    public $filename;

    /** @var PDFGenerator */
    public $pdf_renderer;

    /** @var ObjectModel[] */
    public $objects;

    /** @var string */
    public $template;

    /** @var bool */
    public $send_bulk_flag = false;

    /** @var Smarty */
    public $smarty;

    const TEMPLATE_INVOICE = 'Invoice';
    const TEMPLATE_ORDER_RETURN = 'OrderReturn';
    const TEMPLATE_ORDER_SLIP = 'OrderSlip';
    const TEMPLATE_DELIVERY_SLIP = 'DeliverySlip';
    const TEMPLATE_SUPPLY_ORDER_FORM = 'SupplyOrderForm';

    /**
     * @param ObjectModel[]|Iterator|ObjectModel $objects
     * @param string $template
     * @param Smarty $smarty
     * @param string $orientation
     */
    public function __construct($objects, $template, $smarty, $orientation = 'P')
    {
        $this->pdf_renderer = new PDFGenerator(false, $orientation);
        $this->template = $template;
        $this->smarty = $smarty;

        $this->objects = $objects;
        if (!($objects instanceof Iterator) && !is_array($objects)) {
            $this->objects = [ $objects ];
        }

        if (count($this->objects) > 1) { // when bulk mode only
            $this->send_bulk_flag = true;
        }
    }

    /**
     * Render PDF
     *
     * @param bool $display
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function render($display = true)
    {
        $render = false;
        $this->pdf_renderer->setFontForLang(Context::getContext()->language->iso_code);
        foreach ($this->objects as $object) {
            $this->pdf_renderer->startPageGroup();
            $template = $this->getTemplateObject($object);

            if (empty($this->filename)) {
                $this->filename = $template->getFilename();
                if (count($this->objects) > 1) {
                    $this->filename = $template->getBulkFilename();
                }
            }

            $template->assignHookData($object);

            $this->pdf_renderer->createHeader($template->getHeader());
            $this->pdf_renderer->createFooter($template->getFooter());
            $this->pdf_renderer->createPagination($template->getPagination());
            $this->pdf_renderer->createContent($template->getContent());
            $this->pdf_renderer->writePage();
            $render = true;

            unset($template);
        }

        if ($render) {
            // clean the output buffer
            if (ob_get_level() && ob_get_length() > 0) {
                ob_clean();
            }

            return $this->pdf_renderer->render($this->filename, $display);
        }

        return '';
    }

    /**
     * Get correct PDF template classes
     *
     * @param OrderInvoice|OrderReturn|OrderSlip|SupplyOrder $object
     *
     * @return HTMLTemplate
     * @throws PrestaShopException
     */
    public function getTemplateObject($object)
    {
        switch ($this->template) {
            case static::TEMPLATE_INVOICE:
                return new HTMLTemplateInvoice($object, $this->smarty, $this->send_bulk_flag);
            case static::TEMPLATE_ORDER_RETURN:
                return new HTMLTemplateOrderReturn($object, $this->smarty);
            case static::TEMPLATE_ORDER_SLIP:
                return new HTMLTemplateOrderSlip($object, $this->smarty);
            case static::TEMPLATE_DELIVERY_SLIP:
                return new HTMLTemplateDeliverySlip($object, $this->smarty, $this->send_bulk_flag);
            case static::TEMPLATE_SUPPLY_ORDER_FORM:
                return new HTMLTemplateSupplyOrderForm($object, $this->smarty);
            default:
                $className = 'HTMLTemplate'.$this->template;
                if (class_exists($className)) {
                    $instance = new $className($object, $this->smarty, $this->send_bulk_flag);
                    if ($instance instanceof HTMLTemplate) {
                        return $instance;
                    }
                }
                throw new PrestaShopException('Unknown template: '.$this->template);
        }
    }
}
