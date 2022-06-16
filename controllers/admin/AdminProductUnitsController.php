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
 * @author    CustomPresta <developer@custompresta.com>
 * @author    thirty bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2018 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class AdminProductUnitsControllerCore
 * @thirty bees
 * @since 1.1.0
 */
class AdminProductUnitsControllerCore extends AdminController
{
	public $bootstrap = true;

	public function __construct()
	{
		$this->bootstrap = true;
		$this->table = 'product_unit';
		$this->className = 'ProductUnit';
		$this->lang = true;

		$this->fields_list = [
			'id_product_unit' => [
				'title' => $this->l('ID'),
				'align' => 'center',
				'class' => 'fixed-width-xs',
			],
			'content_name' => [
				'title' => $this->l('Content Name'),
				'filter_key' => 'a!content_name',
			],
			'display_name' => [
				'title' => $this->l('Display Name'),
				'filter_key' => 'a!display_name',
			],
			'modifier' => [
				'title' => $this->l('Calculation Formula'),
			],
		];

		$this->bulk_actions = [
			'delete' => [
				'text' => $this->l('Delete selected'),
				'icon' => 'icon-trash',
				'confirm' => $this->l('Delete selected items?'),
			],
		];

		parent::__construct();
	}
	
    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_product_unit'] = [
                'href' => static::$currentIndex.'&addproduct_unit&token='.$this->token,
                'desc' => $this->l('Add new unit', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
    }

	public function renderList()
	{
		$this->addRowAction('edit');
	 	$this->addRowAction('delete');

		return parent::renderList();
	}

	public function postProcess()
	{
		return parent::postProcess();
	}

	public function renderForm()
	{
        if (!($obj = $this->loadObject(true))) {
            return '';
        }

		$this->fields_form = [
			'legend' => [
				'title' => $this->l('Product Unit'),
				'icon' => 'icon-tag',
			],
			'input' => [
				[
					'type' => 'select',
					'label' => $this->l('Language'),
					'name' => 'id_lang',
					'required' => true,
					'options' => [
						'query' => Language::getLanguages(false),
						'id' => 'id_lang',
						'name' => 'name',
						],
				],
				[
					'type' => 'text',
					'label' => $this->l('Content Name'),
					'name' => 'content_name',
					'lang'     => true,
					'required' => true,
				],
				[
					'type' => 'text',
					'label' => $this->l('Display Name'),
					'name' => 'display_name',
					'lang'     => true,
					'required' => true,
				],
				[
					'type' => 'text',
					'label' => $this->l('Modifier'),
					'name' => 'modifier',
					'required' => true,
				],
			],
			'submit' => [
				'title' => $this->l('Save'),
			],
		];

		return parent::renderForm();
	}
}
