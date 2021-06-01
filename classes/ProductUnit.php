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
 * Class ProductUnitCore
 * @thirty bees
 * @since 1.1.0
 */
class ProductUnitCore extends ObjectModel
{
	public $id_product_unit;

	public $id_lang;

	public $content_name;
	
	public $display_name;
	
	public $modifier;

	public static $definition = array(
		'table' => 'product_unit',
		'primary' => 'id_product_unit',
        'multilang' => true,
		'fields' => array(
			'content_name' 		=> 		array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 32),
			'display_name' 		=> 		array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 32),
			'modifier' 			=> 		array('type' => self::TYPE_STRING, 'required' => true, 'size' => 128),
		),
	);

    public function __construct($id = null, $idLang = null, $idShop = null)
    {
        parent::__construct($id, $idLang, $idShop);
    }

	public function add($autodate = true, $null_values = false)
	{
		if (!parent::add($autodate, $null_values))
			return false;

		return true;
	}

}

?>