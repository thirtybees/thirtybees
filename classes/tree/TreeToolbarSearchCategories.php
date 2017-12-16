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
class TreeToolbarSearchCategoriesCore extends TreeToolbarButtonCore implements
    ITreeToolbarButtonCore
{
    // @codingStandardsIgnoreStart
    protected $_template = 'tree_toolbar_search.tpl';
    // @codingStandardsIgnoreEnd

    /**
     * TreeToolbarSearchCategoriesCore constructor.
     *
     * @param      $label
     * @param null $id
     * @param null $name
     * @param null $class
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function __construct($label, $id, $name = null, $class = null)
    {
        parent::__construct($label);

        $this->setId($id);
        $this->setName($name);
        $this->setClass($class);
    }

    /**
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function render()
    {
        if ($this->hasAttribute('data')) {
            $this->setAttribute('typeahead_source', $this->_renderData($this->getAttribute('data')));
        }

        $adminWebpath = str_ireplace(_PS_CORE_DIR_, '', _PS_ADMIN_DIR_);
        $adminWebpath = preg_replace('/^'.preg_quote(DIRECTORY_SEPARATOR, '/').'/', '', $adminWebpath);
        $boTheme = ((Validate::isLoadedObject($this->getContext()->employee)
            && $this->getContext()->employee->bo_theme) ? $this->getContext()->employee->bo_theme : 'default');

        if (!file_exists(_PS_BO_ALL_THEMES_DIR_.$boTheme.DIRECTORY_SEPARATOR.'template')) {
            $boTheme = 'default';
        }

        if ($this->getContext()->controller->ajax) {
            $path = __PS_BASE_URI__.$adminWebpath.'/themes/'.$boTheme.'/js/vendor/typeahead.min.js?v='._TB_VERSION_;
            $html = '<script type="text/javascript">$(function(){ $.ajax({url: "'.$path.'",cache:true,dataType: "script"})});</script>';
        } else {
            $this->getContext()->controller->addJs(__PS_BASE_URI__.$adminWebpath.'/themes/'.$boTheme.'/js/vendor/typeahead.min.js');
        }

        return (isset($html) ? $html : '').parent::render();
    }

    /**
     * @param $data
     *
     * @return string
     * @throws PrestaShopException
     *
     * @deprecated 2.0.0
     */
    protected function _renderData($data)
    {
        if (!is_array($data) && !$data instanceof Traversable) {
            throw new PrestaShopException('Data value must be a traversable array');
        }

        $html = '';

        foreach ($data as $item) {
            $html .= json_encode($item).',';
            if (array_key_exists('children', $item) && !empty($item['children'])) {
                $html .= $this->_renderData($item['children']);
            }
        }

        return $html;
    }
}
