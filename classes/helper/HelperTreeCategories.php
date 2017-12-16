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
 * Class HelperTreeCategoriesCore
 *
 * @since 1.0.0
 */
class HelperTreeCategoriesCore extends TreeCore
{
    const DEFAULT_TEMPLATE = 'tree_categories.tpl';
    const DEFAULT_NODE_FOLDER_TEMPLATE = 'tree_node_folder_radio.tpl';
    const DEFAULT_NODE_ITEM_TEMPLATE = 'tree_node_item_radio.tpl';

    // @codingStandardsIgnoreStart
    protected $_disabled_categories;
    protected $_input_name;
    /** @var int $_lang */
    protected $_lang;
    protected $_root_category;
    protected $_selected_categories;
    protected $_full_tree = false;
    protected $_shop;
    protected $_use_checkbox;
    protected $_use_search;
    protected $_use_shop_restriction;
    protected $_children_only = false;
    // @codingStandardsIgnoreEnd

    /**
     * HelperTreeCategoriesCore constructor.
     *
     * @param int         $id
     * @param string|null $title
     * @param int|null    $rootCategory
     * @param int|null    $lang
     * @param bool        $useShopRestriction
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function __construct(
        $id,
        $title = null,
        $rootCategory = null,
        $lang = null,
        $useShopRestriction = true
    ) {
        parent::__construct($id);

        if (isset($title)) {
            $this->setTitle($title);
        }

        if (isset($rootCategory)) {
            $this->setRootCategory($rootCategory);
        }

        $this->setLang($lang);
        $this->setUseShopRestriction($useShopRestriction);
    }

    /**
     * @param array $categories
     * @param int   $idCategory
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function fillTree(&$categories, $idCategory)
    {
        $tree = [];
        foreach ($categories[$idCategory] as $category) {
            $tree[$category['id_category']] = $category;
            if (!empty($categories[$category['id_category']])) {
                $tree[$category['id_category']]['children'] = $this->fillTree($categories, $category['id_category']);
            } elseif ($result = Category::hasChildren($category['id_category'], $this->getLang(), false, $this->getShop()->id)) {
                $tree[$category['id_category']]['children'] = [$result[0]['id_category'] => $result[0]];
            }
        }

        return $tree;
    }

    /**
     * @return mixed
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getData()
    {
        if (!isset($this->_data)) {
            $shop = $this->getShop();
            $lang = $this->getLang();
            $rootCategory = (int) $this->getRootCategory();
            if ($this->_full_tree) {
                $this->setData(Category::getNestedCategories($rootCategory, $lang, false, null, $this->useShopRestriction()));
                $this->setDataSearch(Category::getAllCategoriesName($rootCategory, $lang, false, null, $this->useShopRestriction()));
            } elseif ($this->_children_only) {
                if (empty($rootCategory)) {
                    $rootCategory = Category::getRootCategory()->id;
                }
                $categories[$rootCategory] = Category::getChildren($rootCategory, $lang, false, $shop->id);
                $children = $this->fillTree($categories, $rootCategory);
                $this->setData($children);
            } else {
                if (empty($rootCategory)) {
                    $rootCategory = Category::getRootCategory()->id;
                }
                $newSelectedCategories = [];
                $selectedCategories = $this->getSelectedCategories();
                $categories[$rootCategory] = Category::getChildren($rootCategory, $lang, false, $shop->id);
                foreach ($selectedCategories as $selectedCategory) {
                    $category = new Category($selectedCategory, $lang, $shop->id);
                    $newSelectedCategories[] = $selectedCategory;
                    $parents = $category->getParentsCategories($lang);
                    foreach ($parents as $value) {
                        $newSelectedCategories[] = $value['id_category'];
                    }
                }
                $newSelectedCategories = array_unique($newSelectedCategories);
                foreach ($newSelectedCategories as $selectedCategory) {
                    $currentCategory = Category::getChildren($selectedCategory, $lang, false, $shop->id);
                    if (!empty($currentCategory)) {
                        $categories[$selectedCategory] = $currentCategory;
                    }
                }

                $tree = Category::getCategoryInformations([$rootCategory], $lang);

                $children = $this->fillTree($categories, $rootCategory);

                if (!empty($children)) {
                    $tree[$rootCategory]['children'] = $children;
                }

                $this->setData($tree);
                $this->setDataSearch(Category::getAllCategoriesName($rootCategory, $lang, false, null, $this->useShopRestriction()));
            }
        }

        return $this->_data;
    }

    /**
     * @param mixed $value
     *
     * @return $this
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function setChildrenOnly($value)
    {
        $this->_children_only = $value;

        return $this;
    }

    /**
     * @param mixed $value
     *
     * @return $this
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function setFullTree($value)
    {
        $this->_full_tree = $value;

        return $this;
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function getFullTree()
    {
        return $this->_full_tree;
    }

    /**
     * @param mixed $value
     *
     * @return $this
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function setDisabledCategories($value)
    {
        $this->_disabled_categories = $value;

        return $this;
    }

    /**
     * @return mixed
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function getDisabledCategories()
    {
        return $this->_disabled_categories;
    }

    /**
     * @param mixed $value
     *
     * @return $this
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function setInputName($value)
    {
        $this->_input_name = $value;

        return $this;
    }

    /**
     * @return mixed
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function getInputName()
    {
        if (!isset($this->_input_name)) {
            $this->setInputName('categoryBox');
        }

        return $this->_input_name;
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
     * @param int $value
     *
     * @return $this
     * @throws PrestaShopException
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function setRootCategory($value)
    {
        if (!Validate::isInt($value)) {
            throw new PrestaShopException('Root category must be an integer value');
        }

        $this->_root_category = $value;

        return $this;
    }

    /**
     * @return mixed
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function getRootCategory()
    {
        return $this->_root_category;
    }

    /**
     * @param array $value
     *
     * @return $this
     * @throws PrestaShopException
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function setSelectedCategories($value)
    {
        if (!is_array($value)) {
            throw new PrestaShopException('Selected categories value must be an array');
        }

        $this->_selected_categories = $value;

        return $this;
    }

    /**
     * @return array
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function getSelectedCategories()
    {
        if (!isset($this->_selected_categories)) {
            $this->_selected_categories = [];
        }

        return $this->_selected_categories;
    }

    /**
     * @param Shop $value
     *
     * @return $this
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function setShop($value)
    {
        $this->_shop = $value;

        return $this;
    }

    /**
     * @return Shop
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function getShop()
    {
        if (!isset($this->_shop)) {
            if (Tools::isSubmit('id_shop')) {
                $this->setShop(new Shop(Tools::getValue('id_shop')));
            } elseif ($this->getContext()->shop->id) {
                $this->setShop(new Shop($this->getContext()->shop->id));
            } elseif (!Shop::isFeatureActive()) {
                $this->setShop(new Shop(Configuration::get('PS_SHOP_DEFAULT')));
            } else {
                $this->setShop(new Shop(0));
            }
        }

        return $this->_shop;
    }

    /**
     * @return mixed
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
     * @param $value
     *
     * @return $this
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function setUseCheckBox($value)
    {
        $this->_use_checkbox = (bool) $value;

        return $this;
    }

    /**
     * @param bool $value
     *
     * @return $this
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function setUseSearch($value)
    {
        $this->_use_search = (bool) $value;

        return $this;
    }

    /**
     * @param bool $value
     *
     * @return $this
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function setUseShopRestriction($value)
    {
        $this->_use_shop_restriction = (bool) $value;

        return $this;
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function useCheckBox()
    {
        return (isset($this->_use_checkbox) && $this->_use_checkbox);
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function useSearch()
    {
        return (isset($this->_use_search) && $this->_use_search);
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function useShopRestriction()
    {
        return (isset($this->_use_shop_restriction) && $this->_use_shop_restriction);
    }

    /**
     * @param null $data
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function render($data = null)
    {
        if (!isset($data)) {
            $data = $this->getData();
        }

        if (isset($this->_disabled_categories)
            && !empty($this->_disabled_categories)
        ) {
            $this->_disableCategories($data, $this->getDisabledCategories());
        }

        if (isset($this->_selected_categories)
            && !empty($this->_selected_categories)
        ) {
            $this->_getSelectedChildNumbers($data, $this->getSelectedCategories());
        }

        //Default bootstrap style of search is push-right, so we add this button first
        // FIXME ^md
//        if ($this->useSearch()) {
//            $this->addAction(
//                new TreeToolbarLink(
//                    $this->getId().'-categories-search',
//                    'Find a category:'
//                )
//            );
//            $this->setAttribute('use_search', $this->useSearch());
//        }

        $collapseAll = new TreeToolbarLink(
            'Collapse All',
            '#',
            '$(\'#'.$this->getId().'\').tree(\'collapseAll\');$(\'#collapse-all-'.$this->getId().'\').hide();$(\'#expand-all-'.$this->getId().'\').show(); return false;',
            'icon-collapse-alt'
        );
        $collapseAll->setAttribute('id', 'collapse-all-'.$this->getId());
        $expandAll = new TreeToolbarLink(
            'Expand All',
            '#',
            '$(\'#'.$this->getId().'\').tree(\'expandAll\');$(\'#collapse-all-'.$this->getId().'\').show();$(\'#expand-all-'.$this->getId().'\').hide(); return false;',
            'icon-expand-alt'
        );
        $expandAll->setAttribute('id', 'expand-all-'.$this->getId());
        $this->addAction($collapseAll);
        $this->addAction($expandAll);

        if ($this->useCheckBox()) {
            $checkAll = new TreeToolbarLink(
                'Check All',
                '#',
                'checkAllAssociatedCategories($(\'#'.$this->getId().'\')); return false;',
                'icon-check-sign'
            );
            $checkAll->setAttribute('id', 'check-all-'.$this->getId());
            $uncheckAll = new TreeToolbarLink(
                'Uncheck All',
                '#',
                'uncheckAllAssociatedCategories($(\'#'.$this->getId().'\')); return false;',
                'icon-check-empty'
            );
            $uncheckAll->setAttribute('id', 'uncheck-all-'.$this->getId());
            $this->addAction($checkAll);
            $this->addAction($uncheckAll);
            $this->setNodeFolderTemplate('tree_node_folder_checkbox.tpl');
            $this->setNodeItemTemplate('tree_node_item_checkbox.tpl');
            $this->setAttribute('use_checkbox', $this->useCheckBox());
        }

        $this->setAttribute('selected_categories', $this->getSelectedCategories());
        $this->getContext()->smarty->assign('root_category', Configuration::get('PS_ROOT_CATEGORY'));
        $this->getContext()->smarty->assign('token', Tools::getAdminTokenLite('AdminProducts'));

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
            if (array_key_exists('children', $item)
                && !empty($item['children'])
            ) {
                $html .= $this->getContext()->smarty->createTemplate(
                    $this->getTemplateFile($this->getNodeFolderTemplate()),
                    $this->getContext()->smarty
                )->assign(
                    [
                        'input_name' => $this->getInputName(),
                        'children' => $this->renderNodes($item['children']),
                        'node' => $item,
                    ]
                )->fetch();
            } else {
                $html .= $this->getContext()->smarty->createTemplate(
                    $this->getTemplateFile($this->getNodeItemTemplate()),
                    $this->getContext()->smarty
                )->assign(
                    [
                        'input_name' => $this->getInputName(),
                        'node' => $item,
                    ]
                )->fetch();
            }
        }

        return $html;
    }

    /**
     * @param      $categories
     * @param null $disabledCategories
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    protected function _disableCategories(&$categories, $disabledCategories = null)
    {
        foreach ($categories as &$category) {
            if (!isset($disabledCategories) || in_array($category['id_category'], $disabledCategories)) {
                $category['disabled'] = true;
                if (array_key_exists('children', $category) && is_array($category['children'])) {
                    static::_disableCategories($category['children']);
                }
            } elseif (array_key_exists('children', $category) && is_array($category['children'])) {
                static::_disableCategories($category['children'], $disabledCategories);
            }
        }
    }

    /**
     * @param      $categories
     * @param      $selected
     * @param null $parent
     *
     * @return int
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    protected function _getSelectedChildNumbers(&$categories, $selected, &$parent = null)
    {
        $selectedChilds = 0;

        foreach ($categories as $key => &$category) {
            if (isset($parent) && in_array($category['id_category'], $selected)) {
                $selectedChilds++;
            }

            if (isset($category['children']) && !empty($category['children'])) {
                $selectedChilds += $this->_getSelectedChildNumbers($category['children'], $selected, $category);
            }
        }

        if (!isset($parent['selected_childs'])) {
            $parent['selected_childs'] = 0;
        }

        $parent['selected_childs'] = $selectedChilds;

        return $selectedChilds;
    }
}
