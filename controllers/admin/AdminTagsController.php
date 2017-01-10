<?php
/*
* 2007-2016 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2016 PrestaShop SA
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

/**
 * @property Tag $object
 */
class AdminTagsControllerCore extends AdminController
{
    public $bootstrap = true;

    public function __construct()
    {
        $this->table = 'tag';
        $this->className = 'Tag';

        $this->fields_list = [
            'id_tag' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ],
            'lang' => [
                'title' => $this->l('Language'),
                'filter_key' => 'l!name'
            ],
            'name' => [
                'title' => $this->l('Name'),
                'filter_key' => 'a!name'
            ],
            'products' => [
                'title' => $this->l('Products'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'havingFilter' => true
            ]
        ];

        $this->bulk_actions = [
            'delete' => [
                'text' => $this->l('Delete selected'),
                'icon' => 'icon-trash',
                'confirm' => $this->l('Delete selected items?')
            ]
        ];

        parent::__construct();
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_tag'] = [
                'href' => self::$currentIndex.'&addtag&token='.$this->token,
                'desc' => $this->l('Add new tag', null, null, false),
                'icon' => 'process-icon-new'
            ];
        }

        parent::initPageHeaderToolbar();
    }

    public function renderList()
    {
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $this->_select = 'l.name as lang, COUNT(pt.id_product) as products';
        $this->_join = '
			LEFT JOIN `'._DB_PREFIX_.'product_tag` pt
				ON (a.`id_tag` = pt.`id_tag`)
			LEFT JOIN `'._DB_PREFIX_.'lang` l
				ON (l.`id_lang` = a.`id_lang`)';
        $this->_group = 'GROUP BY a.name, a.id_lang';

        return parent::renderList();
    }

    public function postProcess()
    {
        if ($this->tabAccess['edit'] === '1' && Tools::getValue('submitAdd'.$this->table)) {
            if (($id = (int)Tools::getValue($this->identifier)) && ($obj = new $this->className($id)) && Validate::isLoadedObject($obj)) {
                /** @var Tag $obj */
                $previous_products = $obj->getProducts();
                $removed_products = [];

                foreach ($previous_products as $product) {
                    if (!in_array($product['id_product'], $_POST['products'])) {
                        $removed_products[] = $product['id_product'];
                    }
                }

                if (Configuration::get('PS_SEARCH_INDEXATION')) {
                    Search::removeProductsSearchIndex($removed_products);
                }

                $obj->setProducts($_POST['products']);
            }
        }

        return parent::postProcess();
    }

    public function renderForm()
    {
        /** @var Tag $obj */
        if (!($obj = $this->loadObject(true))) {
            return;
        }

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Tag'),
                'icon' => 'icon-tag'
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Name'),
                    'name' => 'name',
                    'required' => true
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Language'),
                    'name' => 'id_lang',
                    'required' => true,
                    'options' => [
                        'query' => Language::getLanguages(false),
                        'id' => 'id_lang',
                        'name' => 'name'
                    ]
                ],
            ],
            'selects' => [
                'products' => $obj->getProducts(true),
                'products_unselected' => $obj->getProducts(false)
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ]
        ];

        return parent::renderForm();
    }
}
