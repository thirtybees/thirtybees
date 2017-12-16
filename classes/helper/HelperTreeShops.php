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
 * Class HelperTreeShopsCore
 *
 * @since 1.0.0
 */
class HelperTreeShopsCore extends TreeCore
{
    const DEFAULT_TEMPLATE             = 'tree_shops.tpl';
    const DEFAULT_NODE_FOLDER_TEMPLATE = 'tree_node_folder_checkbox_shops.tpl';
    const DEFAULT_NODE_ITEM_TEMPLATE   = 'tree_node_item_checkbox_shops.tpl';

    // @codingStandardsIgnoreStart
    protected $_lang;
    protected $_selected_shops;
    // @codingStandardsIgnoreEnd

    /**
     * HelperTreeShopsCore constructor.
     *
     * @param int  $id
     * @param null $title
     * @param null $lang
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function __construct($id, $title = null, $lang = null)
    {
        parent::__construct($id);

        if (isset($title)) {
            $this->setTitle($title);
        }

        $this->setLang($lang);
    }

    /**
     * @return mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getData()
    {
        if (!isset($this->_data)) {
            $this->setData(Shop::getTree());
        }

        return $this->_data;
    }

    /**
     * @param int $value
     *
     * @return $this
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function setLang($value)
    {
        $this->_lang = $value;

        return $this;
    }

    /**
     * @return int
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function getLang()
    {
        if (!isset($this->_lang)) {
            $this->setLang($this->getContext()->employee->id_lang);
        }

        return $this->_lang;
    }

    /**
     * @return mixed
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function getNodeFolderTemplate()
    {
        if (!isset($this->_node_folder_template)) {
            $this->setNodeFolderTemplate(static::DEFAULT_NODE_FOLDER_TEMPLATE);
        }

        return $this->_node_folder_template;
    }

    /**
     * @return mixed
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function getNodeItemTemplate()
    {
        if (!isset($this->_node_item_template)) {
            $this->setNodeItemTemplate(static::DEFAULT_NODE_ITEM_TEMPLATE);
        }

        return $this->_node_item_template;
    }

    /**
     * @param int[] $value
     *
     * @return $this
     * @throws PrestaShopException
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function setSelectedShops($value)
    {
        if (!is_array($value)) {
            throw new PrestaShopException('Selected shops value must be an array');
        }

        $this->_selected_shops = $value;

        return $this;
    }

    /**
     * @return int[]
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function getSelectedShops()
    {
        if (!isset($this->_selected_shops)) {
            $this->_selected_shops = [];
        }

        return $this->_selected_shops;
    }

    /**
     * @return string
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function getTemplate()
    {
        if (!isset($this->_template)) {
            $this->setTemplate(static::DEFAULT_TEMPLATE);
        }

        return $this->_template;
    }

    /**
     * @param null $data
     * @param bool $useDefaultActions
     * @param bool $useSelectedShop
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function render($data = null, $useDefaultActions = true, $useSelectedShop = true)
    {
        if (!isset($data)) {
            $data = $this->getData();
        }

        if ($useDefaultActions) {
            $this->setActions(
                [
                    new TreeToolbarLink(
                        'Collapse All',
                        '#',
                        '$(\'#'.$this->getId().'\').tree(\'collapseAll\'); return false;',
                        'icon-collapse-alt'
                    ),
                    new TreeToolbarLink(
                        'Expand All',
                        '#',
                        '$(\'#'.$this->getId().'\').tree(\'expandAll\'); return false;',
                        'icon-expand-alt'
                    ),
                    new TreeToolbarLink(
                        'Check All',
                        '#',
                        'checkAllAssociatedShops($(\'#'.$this->getId().'\')); return false;',
                        'icon-check-sign'
                    ),
                    new TreeToolbarLink(
                        'Uncheck All',
                        '#',
                        'uncheckAllAssociatedShops($(\'#'.$this->getId().'\')); return false;',
                        'icon-check-empty'
                    ),
                ]
            );
        }

        if ($useSelectedShop) {
            $this->setAttribute('selected_shops', $this->getSelectedShops());
        }

        return parent::render($data);
    }

    /**
     * @param null $data
     *
     * @return string
     * @throws PrestaShopException
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function renderNodes($data = null)
    {
        if (!isset($data)) {
            $data = $this->getData();
        }

        if (!is_array($data) && !$data instanceof Traversable) {
            throw new PrestaShopException('Data value must be an traversable array');
        }

        $html = '';

        foreach ($data as $item) {
            if (array_key_exists('shops', $item)
                && !empty($item['shops'])) {
                $html .= $this->getContext()->smarty->createTemplate(
                    $this->getTemplateFile($this->getNodeFolderTemplate()),
                    $this->getContext()->smarty
                )->assign($this->getAttributes())->assign(
                    [
                        'children' => $this->renderNodes($item['shops']),
                        'node'     => $item,
                    ]
                )->fetch();
            } else {
                $html .= $this->getContext()->smarty->createTemplate(
                    $this->getTemplateFile($this->getNodeItemTemplate()),
                    $this->getContext()->smarty
                )->assign($this->getAttributes())->assign(
                    [
                        'node' => $item,
                    ]
                )->fetch();
            }
        }

        return $html;
    }
}
