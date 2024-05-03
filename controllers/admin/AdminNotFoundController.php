<?php
/**
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2024 thirty bees
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
 *  @copyright 2017-2024 thirty bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class AdminNotFoundControllerCore
 */
class AdminNotFoundControllerCore extends AdminController
{
    /**
     * AdminNotFoundControllerCore constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->bootstrap = true;

        parent::__construct();
    }

    /**
     * Check accesss
     *
     * Always returns true to make it always available
     *
     * @return true
     */
    public function checkAccess()
    {
        return true;
    }

    /**
     * Has view access
     *
     * Always returns true to make it always available
     *
     * @param bool $disable
     *
     * @return true
     */
    public function viewAccess($disable = false)
    {
        return true;
    }

    /**
     * Initialize content
     *
     * @return void
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function initContent()
    {
        $this->errors[] = Tools::displayError('Controller not found');
        $tplVars['controller'] = Tools::getvalue('controllerUri', Tools::getvalue('controller'));
        $this->context->smarty->assign($tplVars);

        parent::initContent();
    }
}
