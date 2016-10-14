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
 * Class HelperListCore
 *
 * @since 1.0.0
 */
class HelperListCore extends Helper
{
    // @codingStandardsIgnoreStart
    /** @var array $cache_lang use to cache texts in current language */
    public static $cache_lang = [];
    /** @var int Number of results in list */
    public $listTotal = 0;
    /** @var array Number of results in list per page (used in select field) */
    public $_pagination = [20, 50, 100, 300, 1000];
    /** @var int Default number of results in list per page */
    public $_default_pagination = 50;
    /** @var string ORDER BY clause determined by field/arrows in list header */
    public $orderBy;
    /** @var string Default ORDER BY clause when $orderBy is not defined */
    public $_defaultOrderBy = false;
    /** @var array : list of vars for button delete */
    public $tpl_delete_link_vars = [];
    /** @var string Order way (ASC, DESC) determined by arrows in list header */
    public $orderWay;
    public $identifier;
    /** @var bool $is_cms */
    public $is_cms = false;
    public $position_identifier;
    public $table_id;
    /** @var bool Content line is clickable if true */
    public $no_link = false;
    /** @var array list of required actions for each list row */
    public $actions = [];
    /** @var array list of row ids associated with a given action for witch this action have to not be available */
    public $list_skip_actions = [];
    public $bulk_actions = false;
    public $force_show_bulk_actions = false;
    public $specificConfirmDelete = null;
    public $colorOnBackground;
    /** @var bool If true, activates color on hover */
    public $row_hover = true;
    /** @var string|null If not null, a title will be added on that list */
    public $title = null;
    /** @var bool ask for simple header : no filters, no paginations and no sorting */
    public $simple_header = false;
    public $ajax_params = [];
    public $page;
    /** @var array Cache for query results */
    protected $_list = [];
    /** @var array WHERE clause determined by filter fields */
    protected $_filter;
    /** @var int $deleted */
    protected $deleted = 0;
    /**
     * @var array Customize list display
     *
     * align  : determine value alignment
     * prefix : displayed before value
     * suffix : displayed after value
     * image  : object image
     * icon   : icon determined by values
     * active : allow to toggle status
     */
    protected $fields_list;
    /** @var Smarty_Internal_Template|string */
    protected $header_tpl = 'list_header.tpl';
    /** @var Smarty_Internal_Template|string */
    protected $content_tpl = 'list_content.tpl';
    /** @var Smarty_Internal_Template|string */
    protected $footer_tpl = 'list_footer.tpl';
    /** @var string $shopLinkType */
    public $shopLinkType;
    // @codingStandardsIgnoreEnd

    /**
     * HelperListCore constructor.
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function __construct()
    {
        $this->base_folder = 'helpers/list/';
        $this->base_tpl = 'list.tpl';

        parent::__construct();
    }

    /**
     * Return an html list given the data to fill it up
     *
     * @param array $list          entries to display (rows)
     * @param array $fieldsDisplay fields (cols)
     *
     * @return string html
     *
     * @throws Exception
     * @throws PrestaShopException
     * @throws SmartyException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function generateList($list, $fieldsDisplay)
    {
        // Append when we get a syntax error in SQL query
        if ($list === false) {
            $this->context->controller->warnings[] = $this->l('Bad SQL query', 'Helper');

            return false;
        }

        $this->tpl = $this->createTemplate($this->base_tpl);
        $this->header_tpl = $this->createTemplate($this->header_tpl);
        $this->content_tpl = $this->createTemplate($this->content_tpl);
        $this->footer_tpl = $this->createTemplate($this->footer_tpl);

        $this->_list = $list;
        $this->fields_list = $fieldsDisplay;

        $this->orderBy = preg_replace('/^([a-z _]*!)/Ui', '', $this->orderBy);
        $this->orderWay = preg_replace('/^([a-z _]*!)/Ui', '', $this->orderWay);

        $this->tpl->assign(
            [
                'header'  => $this->displayListHeader(), // Display list header (filtering, pagination and column names)
                'content' => $this->displayListContent(), // Show the content of the table
                'footer'  => $this->displayListFooter(), // Close list table and submit button
            ]
        );

        return parent::generate();
    }

    /**
     * Display list header (filtering, pagination and column names)
     *
     * @return string
     *
     * @throws Exception
     * @throws SmartyException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function displayListHeader()
    {
        if (!isset($this->list_id)) {
            $this->list_id = $this->table;
        }

        $idCat = (int) Tools::getValue('id_'.($this->is_cms ? 'cms_' : '').'category');

        if (!isset($token) || empty($token)) {
            $token = $this->token;
        }

        /* Determine total page number */
        $pagination = $this->_default_pagination;
        if (in_array((int) Tools::getValue($this->list_id.'_pagination'), $this->_pagination)) {
            $pagination = (int) Tools::getValue($this->list_id.'_pagination');
        } elseif (isset($this->context->cookie->{$this->list_id.'_pagination'}) && $this->context->cookie->{$this->list_id.'_pagination'}) {
            $pagination = $this->context->cookie->{$this->list_id.'_pagination'};
        }

        $totalPages = max(1, ceil($this->listTotal / $pagination));

        $identifier = Tools::getIsset($this->identifier) ? '&'.$this->identifier.'='.(int) Tools::getValue($this->identifier) : '';
//        $order = '';
//        if (Tools::getIsset($this->table.'Orderby')) {
//            $order = '&'.$this->table.'Orderby='.urlencode($this->orderBy).'&'.$this->table.'Orderway='.urlencode(strtolower($this->orderWay));
//        }

        $action = $this->currentIndex.$identifier.'&token='.$token.'#'.$this->list_id;

        /* Determine current page number */
        $page = (int) Tools::getValue('submitFilter'.$this->list_id);

        if (!$page) {
            $page = 1;
        }

        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $this->page = (int) $page;

        /* Choose number of results per page */
        $selectedPagination = Tools::getValue(
            $this->list_id.'_pagination',
            isset($this->context->cookie->{$this->list_id.'_pagination'}) ? $this->context->cookie->{$this->list_id.'_pagination'} : $this->_default_pagination
        );

        if (!isset($this->table_id) && $this->position_identifier && (int) Tools::getValue($this->position_identifier, 1)) {
            $this->table_id = substr($this->identifier, 3, strlen($this->identifier));
        }

        if ($this->position_identifier && ($this->orderBy == 'position' && $this->orderWay != 'DESC')) {
            $tableDnd = true;
        }

        $prefix = isset($this->controller_name) ? str_replace(['admin', 'controller'], '', mb_strtolower($this->controller_name)) : '';
        $ajax = false;
        foreach ($this->fields_list as $key => $params) {
            if (!isset($params['type'])) {
                $params['type'] = 'text';
            }

            $valueKey = $prefix.$this->list_id.'Filter_'.(array_key_exists('filter_key', $params) ? $params['filter_key'] : $key);
            if ($key == 'active' && strpos($key, '!') !== false) {
                $keys = explode('!', $params['filter_key']);
                $valueKey = $keys[1];
            }
            $value = Context::getContext()->cookie->{$valueKey};
            if (!$value && Tools::getIsset($valueKey)) {
                $value = Tools::getValue($valueKey);
            }

            switch ($params['type']) {
                case 'bool':
                    if (isset($params['ajax']) && $params['ajax']) {
                        $ajax = true;
                    }
                    break;

                case 'date':
                case 'datetime':
                    if (is_string($value)) {
                        $value = json_decode($value, true);
                    }
                    if (!Validate::isCleanHtml($value[0]) || !Validate::isCleanHtml($value[1])) {
                        $value = '';
                    }
                    $name = $this->list_id.'Filter_'.(isset($params['filter_key']) ? $params['filter_key'] : $key);
                    $nameId = str_replace('!', '__', $name);

                    $params['id_date'] = $nameId;
                    $params['name_date'] = $name;

                    $this->context->controller->addJqueryUI('ui.datepicker');
                    break;

                case 'select':
                    foreach ($params['list'] as $optionValue => $optionDisplay) {
                        if (isset(Context::getContext()->cookie->{$prefix.$this->list_id.'Filter_'.$params['filter_key']})
                            && Context::getContext()->cookie->{$prefix.$this->list_id.'Filter_'.$params['filter_key']} == $optionValue
                            && Context::getContext()->cookie->{$prefix.$this->list_id.'Filter_'.$params['filter_key']} != ''
                        ) {
                            $this->fields_list[$key]['select'][$optionValue]['selected'] = 'selected';
                        }
                    }
                    break;

                case 'text':
                    if (!Validate::isCleanHtml($value)) {
                        $value = '';
                    }
            }

            $params['value'] = $value;
            $this->fields_list[$key] = $params;
        }

        $hasValue = false;
        $hasSearchField = false;

        foreach ($this->fields_list as $key => $field) {
            if (isset($field['value']) && $field['value'] !== false && $field['value'] !== '') {
                if (is_array($field['value']) && trim(implode('', $field['value'])) == '') {
                    continue;
                }

                $hasValue = true;
                break;
            }
            if (!(isset($field['search']) && $field['search'] === false)) {
                $hasSearchField = true;
            }
        }

        Context::getContext()->smarty->assign(
            [
                'page'                => $page,
                'simple_header'       => $this->simple_header,
                'total_pages'         => $totalPages,
                'selected_pagination' => $selectedPagination,
                'pagination'          => $this->_pagination,
                'list_total'          => $this->listTotal,
                'sql'                 => isset($this->sql) && $this->sql ? str_replace('\n', ' ', str_replace('\r', '', $this->sql)) : false,
                'table'               => $this->table,
                'bulk_actions'        => $this->bulk_actions,
                'show_toolbar'        => $this->show_toolbar,
                'toolbar_scroll'      => $this->toolbar_scroll,
                'toolbar_btn'         => $this->toolbar_btn,
                'has_bulk_actions'    => $this->hasBulkActions($hasValue),
                'filters_has_value'   => (bool) $hasValue,
            ]
        );

        $this->header_tpl->assign(
            array_merge(
                [
                    'ajax'              => $ajax,
                    'title'             => array_key_exists('title', $this->tpl_vars) ? $this->tpl_vars['title'] : $this->title,
                    'show_filters'      => ((count($this->_list) > 1 && $hasSearchField) || $hasValue),
                    'currentIndex'      => $this->currentIndex,
                    'action'            => $action,
                    'is_order_position' => $this->position_identifier && $this->orderBy == 'position',
                    'order_way'         => $this->orderWay,
                    'order_by'          => $this->orderBy,
                    'fields_display'    => $this->fields_list,
                    'delete'            => in_array('delete', $this->actions),
                    'identifier'        => $this->identifier,
                    'id_cat'            => $idCat,
                    'shop_link_type'    => $this->shopLinkType,
                    'has_actions'       => !empty($this->actions),
                    'table_id'          => isset($this->table_id) ? $this->table_id : null,
                    'table_dnd'         => isset($tableDnd) ? $tableDnd : null,
                    'name'              => isset($name) ? $name : null,
                    'name_id'           => isset($nameId) ? $nameId : null,
                    'row_hover'         => $this->row_hover,
                    'list_id'           => isset($this->list_id) ? $this->list_id : $this->table,
                    'token'             => $this->token,
                ],
                $this->tpl_vars
            )
        );

        return $this->header_tpl->fetch();
    }

    /**
     * @param bool $hasValue
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function hasBulkActions($hasValue = false)
    {
        if ($this->force_show_bulk_actions) {
            return true;
        }

        if (count($this->_list) <= 1 && !$hasValue) {
            return false;
        }

        if (isset($this->list_skip_actions) && count($this->list_skip_actions)
            && isset($this->bulk_actions) && is_array($this->bulk_actions) && count($this->bulk_actions)
        ) {
            foreach ($this->bulk_actions as $action => $data) {
                if (array_key_exists($action, $this->list_skip_actions)) {
                    foreach ($this->_list as $key => $row) {
                        if (!in_array($row[$this->identifier], $this->list_skip_actions[$action])) {
                            return true;
                        }
                    }

                    return false;
                }
            }
        }

        return !empty($this->bulk_actions);
    }

    /**
     * @return mixed
     *
     * @throws Exception
     * @throws PrestaShopException
     * @throws SmartyException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function displayListContent()
    {
        if (isset($this->fields_list['position'])) {
            if ($this->position_identifier) {
                if (isset($this->position_group_identifier)) {
                    $positionGroupIdentifier = Tools::getIsset($this->position_group_identifier) ? Tools::getValue($this->position_group_identifier) : $this->position_group_identifier;
                } else {
                    $positionGroupIdentifier = (int) Tools::getValue('id_'.($this->is_cms ? 'cms_' : '').'category', ($this->is_cms ? '1' : Category::getRootCategory()->id));
                }
            } else {
                $positionGroupIdentifier = Category::getRootCategory()->id;
            }

            $positions = array_map(function ($elem) {
                return (int) $elem['position'];
            }, $this->_list);
            sort($positions);
        }

        // key_to_get is used to display the correct product category or cms category after a position change
        $identifier = in_array($this->identifier, ['id_category', 'id_cms_category']) ? '_parent' : '';
        if ($identifier) {
            $keyToGet = 'id_'.($this->is_cms ? 'cms_' : '').'category'.$identifier;
        }

        foreach ($this->_list as $index => $tr) {
            $id = null;
            if (isset($tr[$this->identifier])) {
                $id = $tr[$this->identifier];
            }
            $name = isset($tr['name']) ? $tr['name'] : null;

            if ($this->shopLinkType) {
                $this->_list[$index]['short_shop_name'] = mb_strlen($tr['shop_name']) > 15 ? mb_substr($tr['shop_name'], 0, 15).'...' : $tr['shop_name'];
            }

            $isFirst = true;
            // Check all available actions to add to the current list row
            foreach ($this->actions as $action) {
                //Check if the action is available for the current row
                if (!array_key_exists($action, $this->list_skip_actions) || !in_array($id, $this->list_skip_actions[$action])) {
                    $methodName = 'display'.ucfirst($action).'Link';

                    if (method_exists($this->context->controller, $methodName)) {
                        $this->_list[$index][$action] = $this->context->controller->$methodName($this->token, $id, $name);
                    } elseif ($this->module instanceof Module && method_exists($this->module, $methodName)) {
                        $this->_list[$index][$action] = $this->module->$methodName($this->token, $id, $name);
                    } elseif (method_exists($this, $methodName)) {
                        $this->_list[$index][$action] = $this->$methodName($this->token, $id, $name);
                    }
                }

                if ($isFirst && isset($this->_list[$index][$action])) {
                    $isFirst = false;

                    if (!preg_match('/a\s*.*class/', $this->_list[$index][$action])) {
                        $this->_list[$index][$action] = preg_replace(
                            '/href\s*=\s*\"([^\"]*)\"/',
                            'href="$1" class="btn btn-default"',
                            $this->_list[$index][$action]
                        );
                    } elseif (!preg_match('/a\s*.*class\s*=\s*\".*btn.*\"/', $this->_list[$index][$action])) {
                        $this->_list[$index][$action] = preg_replace(
                            '/a(\s*.*)class\s*=\s*\"(.*)\"/',
                            'a $1 class="$2 btn btn-default"',
                            $this->_list[$index][$action]
                        );
                    }
                }
            }

            // @todo skip action for bulk actions
            // $this->_list[$index]['has_bulk_actions'] = true;
            foreach ($this->fields_list as $key => $params) {
                $tmp = explode('!', $key);
                $key = isset($tmp[1]) ? $tmp[1] : $tmp[0];

                if (isset($params['active'])) {
                    // If method is defined in calling controller, use it instead of the Helper method
                    if (method_exists($this->context->controller, 'displayEnableLink')) {
                        $callingObj = $this->context->controller;
                    } elseif (isset($this->module) && method_exists($this->module, 'displayEnableLink')) {
                        $callingObj = $this->module;
                    } else {
                        $callingObj = $this;
                    }

                    if (!isset($params['ajax'])) {
                        $params['ajax'] = false;
                    }
                    $this->_list[$index][$key] = $callingObj->displayEnableLink(
                        $this->token,
                        $id,
                        $tr[$key],
                        $params['active'],
                        Tools::getValue('id_category'),
                        Tools::getValue('id_product'),
                        $params['ajax']
                    );
                } elseif (isset($params['activeVisu'])) {
                    $this->_list[$index][$key] = (bool) $tr[$key];
                } elseif (isset($params['position'])) {
                    $this->_list[$index][$key] = [
                        'position'          => $tr[$key],
                        'position_url_down' => $this->currentIndex.
                            (isset($keyToGet) ? '&'.$keyToGet.'='.(int) $positionGroupIdentifier : '').
                            '&'.$this->position_identifier.'='.$id.
                            '&way=1&position='.((int) $tr['position'] + 1).'&token='.$this->token,
                        'position_url_up'   => $this->currentIndex.
                            (isset($keyToGet) ? '&'.$keyToGet.'='.(int) $positionGroupIdentifier : '').
                            '&'.$this->position_identifier.'='.$id.
                            '&way=0&position='.((int) $tr['position'] - 1).'&token='.$this->token,
                    ];
                } elseif (isset($params['image'])) {
                    // item_id is the product id in a product image context, else it is the image id.
                    $itemId = isset($params['image_id']) ? $tr[$params['image_id']] : $id;
                    if ($params['image'] != 'p' || Configuration::get('PS_LEGACY_IMAGES')) {
                        $pathToImage = _PS_IMG_DIR_.$params['image'].'/'.$itemId.(isset($tr['id_image']) ? '-'.(int) $tr['id_image'] : '').'.'.$this->imageType;
                    } else {
                        $pathToImage = _PS_IMG_DIR_.$params['image'].'/'.Image::getImgFolderStatic($tr['id_image']).(int) $tr['id_image'].'.'.$this->imageType;
                    }
                    $this->_list[$index][$key] = ImageManager::thumbnail($pathToImage, $this->table.'_mini_'.$itemId.'_'.$this->context->shop->id.'.'.$this->imageType, 45, $this->imageType);
                } elseif (isset($params['icon']) && isset($tr[$key]) && (isset($params['icon'][$tr[$key]]) || isset($params['icon']['default']))) {
                    if (!$this->bootstrap) {
                        if (isset($params['icon'][$tr[$key]]) && is_array($params['icon'][$tr[$key]])) {
                            $this->_list[$index][$key] = [
                                'src' => $params['icon'][$tr[$key]]['src'],
                                'alt' => $params['icon'][$tr[$key]]['alt'],
                            ];
                        } else {
                            $this->_list[$index][$key] = [
                                'src' => isset($params['icon'][$tr[$key]]) ? $params['icon'][$tr[$key]] : $params['icon']['default'],
                                'alt' => isset($params['icon'][$tr[$key]]) ? $params['icon'][$tr[$key]] : $params['icon']['default'],
                            ];
                        }
                    } elseif (isset($params['icon'][$tr[$key]])) {
                        $this->_list[$index][$key] = $params['icon'][$tr[$key]];
                    }
                } elseif (isset($params['type']) && $params['type'] == 'float') {
                    $this->_list[$index][$key] = rtrim(rtrim($tr[$key], '0'), '.');
                } elseif (isset($tr[$key])) {
                    $echo = $tr[$key];
                    if (isset($params['callback'])) {
                        $callbackObj = (isset($params['callback_object'])) ? $params['callback_object'] : $this->context->controller;
                        $this->_list[$index][$key] = call_user_func_array([$callbackObj, $params['callback']], [$echo, $tr]);
                    } else {
                        $this->_list[$index][$key] = $echo;
                    }
                }
            }
        }

        $this->content_tpl->assign(
            array_merge(
                $this->tpl_vars,
                [
                    'shop_link_type'            => $this->shopLinkType,
                    'name'                      => isset($name) ? $name : null,
                    'position_identifier'       => $this->position_identifier,
                    'identifier'                => $this->identifier,
                    'table'                     => $this->table,
                    'token'                     => $this->token,
                    'color_on_bg'               => $this->colorOnBackground,
                    'position_group_identifier' => isset($positionGroupIdentifier) ? $positionGroupIdentifier : false,
                    'bulk_actions'              => $this->bulk_actions,
                    'positions'                 => isset($positions) ? $positions : null,
                    'order_by'                  => $this->orderBy,
                    'order_way'                 => $this->orderWay,
                    'is_cms'                    => $this->is_cms,
                    'fields_display'            => $this->fields_list,
                    'list'                      => $this->_list,
                    'actions'                   => $this->actions,
                    'no_link'                   => $this->no_link,
                    'current_index'             => $this->currentIndex,
                    'view'                      => in_array('view', $this->actions),
                    'edit'                      => in_array('edit', $this->actions),
                    'has_actions'               => !empty($this->actions),
                    'list_skip_actions'         => $this->list_skip_actions,
                    'row_hover'                 => $this->row_hover,
                    'list_id'                   => isset($this->list_id) ? $this->list_id : $this->table,
                    'checked_boxes'             => Tools::getValue((isset($this->list_id) ? $this->list_id : $this->table).'Box'),
                ]
            )
        );

        return $this->content_tpl->fetch();
    }

    /**
     * Fetch the template for action enable
     *
     * @param string $token
     * @param string $id
     * @param int    $value  state enabled or not
     * @param string $active status
     * @param int    $idCategory
     * @param int    $idProduct
     *
     * @param bool   $ajax
     *
     * @return string
     *
     * @throws Exception
     * @throws SmartyException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function displayEnableLink($token, $id, $value, $active, $idCategory = null, $idProduct = null, $ajax = false)
    {
        $tplEnable = $this->createTemplate('list_action_enable.tpl');
        $tplEnable->assign(
            [
                'ajax'       => $ajax,
                'enabled'    => (bool) $value,
                'url_enable' => $this->currentIndex.'&'.$this->identifier.'='.$id.'&'.$active.$this->table.($ajax ? '&action='.$active.$this->table.'&ajax='.(int) $ajax : '').((int) $idCategory && (int) $idProduct ? '&id_category='.(int) $idCategory : '').($this->page && $this->page > 1 ? '&page='.(int) $this->page : '').'&token='.($token != null ? $token : $this->token),
            ]
        );

        return $tplEnable->fetch();
    }

    /**
     * Close list table and submit button
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function displayListFooter()
    {
        if (!isset($this->list_id)) {
            $this->list_id = $this->table;
        }

        $this->footer_tpl->assign(
            array_merge(
                $this->tpl_vars,
                [
                    'current' => $this->currentIndex,
                    'list_id' => $this->list_id,
                    'token'   => $this->token,
                ]
            )
        );

        return $this->footer_tpl->fetch();
    }

    /**
     * Display duplicate action link
     *
     * @param string|null $token
     * @param int         $id
     * @param string|null $name
     *
     * @return string
     * @throws Exception
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function displayDuplicateLink($token = null, $id, $name = null)
    {
        $tpl = $this->createTemplate('list_action_duplicate.tpl');
        // @codingStandardsIgnoreStart
        if (!array_key_exists('Bad SQL query', static::$cache_lang)) {
            static::$cache_lang['Duplicate'] = $this->l('Duplicate', 'Helper');
        }
        // @codingStandardsIgnoreEnd

        // @codingStandardsIgnoreStart
        if (!array_key_exists('Copy images too?', static::$cache_lang)) {
            static::$cache_lang['Copy images too?'] = $this->l('This will copy the images too. If you wish to proceed, click "Yes". If not, click "No".', 'Helper');
        }
        // @codingStandardsIgnoreEnd

        $duplicate = $this->currentIndex.'&'.$this->identifier.'='.$id.'&duplicate'.$this->table;

        // @codingStandardsIgnoreStart
        $confirm = static::$cache_lang['Copy images too?'];
        // @codingStandardsIgnoreEnd

        if (($this->table == 'product') && !Image::hasImages($this->context->language->id, (int) $id)) {
            $confirm = '';
        }

        // @codingStandardsIgnoreStart
        $tpl->assign(
            [
                'href'        => $this->currentIndex.'&'.$this->identifier.'='.$id.'&view'.$this->table.'&token='.($token != null ? $token : $this->token),
                'action'      => static::$cache_lang['Duplicate'],
                'confirm'     => $confirm,
                'location_ok' => $duplicate.'&token='.($token != null ? $token : $this->token),
                'location_ko' => $duplicate.'&noimage=1&token='.($token ? $token : $this->token),
            ]
        );

        // @codingStandardsIgnoreEnd

        return $tpl->fetch();
    }

    /**
     * Display action show details of a table row
     * This action need an ajax request with a return like this:
     *   {
     *     use_parent_structure: true // If false, data need to be an html
     *     data:
     *       [
     *         {field_name: 'value'}
     *       ],
     *     fields_display: // attribute $fields_list of the admin controller
     *   }
     * or somethins like this:
     *   {
     *     use_parent_structure: false // If false, data need to be an html
     *     data:
     *       '<p>My html content</p>',
     *     fields_display: // attribute $fields_list of the admin controller
     *   }
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     *
     * @param string|null $token
     * @param int         $id
     * @param string|null $name
     *
     * @return string
     * @throws Exception
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function displayDetailsLink($token = null, $id, $name = null)
    {
        $tpl = $this->createTemplate('list_action_details.tpl');
        // @codingStandardsIgnoreStart
        if (!array_key_exists('Details', static::$cache_lang)) {
            static::$cache_lang['Details'] = $this->l('Details', 'Helper');
        }
        // @codingStandardsIgnoreEnd

        $ajaxParams = $this->ajax_params;
        if (!is_array($ajaxParams) || !isset($ajaxParams['action'])) {
            $ajaxParams['action'] = 'details';
        }

        // @codingStandardsIgnoreStart
        $tpl->assign(
            [
                'id'          => $id,
                'href'        => $this->currentIndex.'&'.$this->identifier.'='.$id.'&details'.$this->table.'&token='.($token != null ? $token : $this->token),
                'controller'  => str_replace('Controller', '', get_class($this->context->controller)),
                'token'       => $token != null ? $token : $this->token,
                'action'      => static::$cache_lang['Details'],
                'params'      => $ajaxParams,
                'json_params' => json_encode($ajaxParams),
            ]
        );

        // @codingStandardsIgnoreEnd

        return $tpl->fetch();
    }

    /**
     * Display view action link
     *
     * @param string|null $token
     * @param int         $id
     * @param string|null $name
     *
     * @return string
     * @throws Exception
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function displayViewLink($token = null, $id, $name = null)
    {
        $tpl = $this->createTemplate('list_action_view.tpl');
        // @codingStandardsIgnoreStart
        if (!array_key_exists('View', static::$cache_lang)) {
            static::$cache_lang['View'] = $this->l('View', 'Helper');
        }
        $tpl->assign(
            [
                'href'   => $this->currentIndex.'&'.$this->identifier.'='.$id.'&view'.$this->table.'&token='.($token != null ? $token : $this->token),
                'action' => static::$cache_lang['View'],
            ]
        );

        // @codingStandardsIgnoreEnd

        return $tpl->fetch();
    }

    /**
     * Display edit action link
     *
     * @param string|null $token
     * @param int         $id
     * @param string|null $name
     *
     * @return string
     * @throws Exception
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function displayEditLink($token = null, $id, $name = null)
    {
        $tpl = $this->createTemplate('list_action_edit.tpl');
        // @codingStandardsIgnoreStart
        if (!array_key_exists('Edit', static::$cache_lang)) {
            static::$cache_lang['Edit'] = $this->l('Edit', 'Helper');
        }
        $tpl->assign(
            [
                'href'   => $this->currentIndex.'&'.$this->identifier.'='.$id.'&update'.$this->table.($this->page && $this->page > 1 ? '&page='.(int) $this->page : '').'&token='.($token != null ? $token : $this->token),
                'action' => static::$cache_lang['Edit'],
                'id'     => $id,
            ]
        );
        // @codingStandardsIgnoreEnd

        return $tpl->fetch();
    }

    /**
     * Display delete action link
     *
     * @param string|null $token
     * @param int         $id
     * @param string|null $name
     *
     * @return string
     * @throws Exception
     * @throws PrestaShopException
     * @throws SmartyException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function displayDeleteLink($token = null, $id, $name = null)
    {
        $tpl = $this->createTemplate('list_action_delete.tpl');

        // @codingStandardsIgnoreStart
        if (!array_key_exists('Delete', static::$cache_lang)) {
            static::$cache_lang['Delete'] = $this->l('Delete', 'Helper');
        }
        if (!array_key_exists('DeleteItem', static::$cache_lang)) {
            static::$cache_lang['DeleteItem'] = $this->l('Delete selected item?', 'Helper', true, false);
        }
        if (!array_key_exists('Name', static::$cache_lang)) {
            static::$cache_lang['Name'] = $this->l('Name:', 'Helper', true, false);
        }
        if (!is_null($name)) {
            $name = addcslashes('\n\n'.static::$cache_lang['Name'].' '.$name, '\'');
        }
        $data = [
            $this->identifier => $id,
            'href'            => $this->currentIndex.'&'.$this->identifier.'='.$id.'&delete'.$this->table.'&token='.($token != null ? $token : $this->token),
            'action'          => static::$cache_lang['Delete'],
        ];
        if ($this->specificConfirmDelete !== false) {
            $data['confirm'] = !is_null($this->specificConfirmDelete) ? '\r'.$this->specificConfirmDelete : Tools::safeOutput(static::$cache_lang['DeleteItem'].$name);
        }
        // @codingStandardsIgnoreEnd

        $tpl->assign(array_merge($this->tpl_delete_link_vars, $data));

        return $tpl->fetch();
    }

    /**
     * Display default action link
     *
     * @param string|null $token
     * @param int         $id
     * @param string|null $name
     *
     * @return string
     * @throws Exception
     * @throws PrestaShopException
     * @throws SmartyException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function displayDefaultLink($token = null, $id, $name = null)
    {
        $tpl = $this->createTemplate('list_action_default.tpl');
        // @codingStandardsIgnoreStart
        if (!array_key_exists('Default', static::$cache_lang)) {
            static::$cache_lang['Default'] = $this->l('Default', 'Helper');
        }
        $tpl->assign(
            [
                'href'   => $this->currentIndex.'&'.$this->identifier.'='.(int) $id.'&default'.$this->table.'&token='.($token != null ? $token : $this->token),
                'action' => static::$cache_lang['Default'],
                'name'   => $name,
            ]
        );
        // @codingStandardsIgnoreEnd

        return $tpl->fetch();
    }
}
