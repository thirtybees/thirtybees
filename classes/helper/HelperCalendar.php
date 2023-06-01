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
 *  @author    thirty bees <contact@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017-2018 thirty bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class HelperCalendarCore
 */
class HelperCalendarCore extends Helper
{
    const DEFAULT_DATE_FORMAT    = 'Y-mm-dd';

    const DEFAULT_COMPARE_OPTION = 1;

    /**
     * @var array
     */
    private $_actions = [];

    /**
     * @var array
     */
    private $_compare_actions = [];

    /**
     * @var string | null
     */
    private $_compare_date_from;

    /**
     * @var string | null
     */
    private $_compare_date_to;

    /**
     * @var int | null
     */
    private $_compare_date_option = self::DEFAULT_COMPARE_OPTION;

    /**
     * @var string
     */
    private $_date_format = self::DEFAULT_DATE_FORMAT;

    /**
     * @var string
     */
    private $_date_from;

    /**
     * @var string
     */
    private $_date_to;

    /**
     * @var bool
     */
    private $_rtl;

    /**
     * HelperCalendarCore constructor.
     */
    public function __construct()
    {
        $this->base_folder = 'helpers/calendar/';
        $this->base_tpl = 'calendar.tpl';
        $this->_rtl = (bool)Context::getContext()->language->is_rtl;
        parent::__construct();
    }

    /**
     * @param Traversable[] $value
     *
     * @return static
     * @throws PrestaShopException
     */
    public function setActions($value)
    {
        if (!is_array($value) && !$value instanceof Traversable) {
            throw new PrestaShopException('Actions value must be an traversable array');
        }

        $this->_actions = (array)$value;

        return $this;
    }

    /**
     * @return array
     */
    public function getActions()
    {
        return $this->_actions;
    }

    /**
     * @param Traversable[] $value
     *
     * @return static
     * @throws PrestaShopException
     */
    public function setCompareActions($value)
    {
        if (!is_array($value) && !$value instanceof Traversable) {
            throw new PrestaShopException('Actions value must be an traversable array');
        }

        $this->_compare_actions = (array)$value;

        return $this;
    }

    /**
     * @return array
     */
    public function getCompareActions()
    {
        return $this->_compare_actions;
    }

    /**
     * @param string $value
     *
     * @return static
     */
    public function setCompareDateFrom($value)
    {
        $this->_compare_date_from = $this->convertToDate($value);

        return $this;
    }

    /**
     * @return string
     */
    public function getCompareDateFrom()
    {
        return $this->_compare_date_from;
    }

    /**
     * @param string $value
     *
     * @return static
     */
    public function setCompareDateTo($value)
    {
        $this->_compare_date_to = $this->convertToDate($value);

        return $this;
    }

    /**
     * @return string
     */
    public function getCompareDateTo()
    {
        return $this->_compare_date_to;
    }

    /**
     * @param int $value
     *
     * @return static
     */
    public function setCompareOption($value)
    {
        $this->_compare_date_option = (int) $value;

        return $this;
    }

    /**
     * @return int
     */
    public function getCompareOption()
    {
        return $this->_compare_date_option;
    }

    /**
     * @param string $value
     *
     * @return static
     * @throws PrestaShopException
     */
    public function setDateFormat($value)
    {
        if (!is_string($value)) {
            throw new PrestaShopException('Date format must be a string');
        }

        $this->_date_format = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getDateFormat()
    {
        return $this->_date_format;
    }

    /**
     * @param string $value
     *
     * @return static
     */
    public function setDateFrom($value)
    {

        $this->_date_from = $this->convertToDate($value);

        return $this;
    }

    /**
     * @return string
     */
    public function getDateFrom()
    {
        return $this->_date_from ?? date('Y-m-d', strtotime('-31 days'));
    }

    /**
     * @param string $value
     *
     * @return static
     */
    public function setDateTo($value)
    {
        $this->_date_to = $this->convertToDate($value);

        return $this;
    }

    /**
     * @return false|string
     */
    public function getDateTo()
    {
        return $this->_date_to ?? date('Y-m-d');
    }

    /**
     * @param bool $value
     *
     * @return static
     */
    public function setRTL($value)
    {
        $this->_rtl = (bool) $value;

        return $this;
    }

    /**
     * @param string $action
     *
     * @return static
     */
    public function addAction($action)
    {
        $this->_actions[] = $action;
        return $this;
    }

    /**
     * @param string $action
     *
     * @return static
     */
    public function addCompareAction($action)
    {
        $this->_compare_actions[] = $action;
        return $this;
    }

    /**
     * @return string
     *
     * @throws SmartyException
     * @throws PrestaShopException
     */
    public function generate()
    {
        $context = Context::getContext();
        $controller = $this->getController();
        $adminWebpath = str_ireplace(_PS_CORE_DIR_, '', _PS_ADMIN_DIR_);
        $adminWebpath = preg_replace('/^'.preg_quote(DIRECTORY_SEPARATOR, '/').'/', '', $adminWebpath);
        $boTheme = ((Validate::isLoadedObject($context->employee)
            && $context->employee->bo_theme) ? $context->employee->bo_theme : 'default');

        if (!file_exists(_PS_BO_ALL_THEMES_DIR_.$boTheme.DIRECTORY_SEPARATOR.'template')) {
            $boTheme = 'default';
        }

        if ($controller->ajax) {
            $html = '<script type="text/javascript" src="'.__PS_BASE_URI__.$adminWebpath.'/themes/'.$boTheme.'/js/date-range-picker.js"></script>';
            $html .= '<script type="text/javascript" src="'.__PS_BASE_URI__.$adminWebpath.'/themes/'.$boTheme.'/js/calendar.js"></script>';
        } else {
            $html = '';
            $controller->addJs(__PS_BASE_URI__.$adminWebpath.'/themes/'.$boTheme.'/js/date-range-picker.js');
            $controller->addJs(__PS_BASE_URI__.$adminWebpath.'/themes/'.$boTheme.'/js/calendar.js');
        }

        $this->tpl = $this->createTemplate($this->base_tpl);
        $this->tpl->assign(
            [
                'date_format'       => $this->getDateFormat(),
                'date_from'         => $this->getDateFrom(),
                'date_to'           => $this->getDateTo(),
                'compare_date_from' => $this->getCompareDateFrom(),
                'compare_date_to'   => $this->getCompareDateTo(),
                'actions'           => $this->getActions(),
                'compare_actions'   => $this->getCompareActions(),
                'compare_option'    => $this->getCompareOption(),
                'is_rtl'            => $this->isRTL(),
            ]
        );

        $html .= parent::generate();

        return $html;
    }

    /**
     * @return bool
     */
    public function isRTL()
    {
        return $this->_rtl;
    }

    /**
     * Converts and validates input value to date
     *
     * @param mixed $value
     * @return string | null
     */
    public function convertToDate($value)
    {
        if (is_string($value) && !empty($value)) {
            $timestamp = strtotime($value);
            if ($timestamp !== false) {
                return date('Y-m-d', $timestamp);
            }
        }
        return null;
    }
}
