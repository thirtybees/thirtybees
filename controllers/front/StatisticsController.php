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
 * @author    thirty bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2024 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class StatisticsControllerCore
 */
class StatisticsControllerCore extends FrontController
{
    /** @var bool $display_header */
    public $display_header = false;
    /** @var bool $display_footer */
    public $display_footer = false;
    /** @var string $param_token */
    protected $param_token;

    /**
     * Post processing
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function postProcess()
    {
        $this->param_token = Tools::getValue('token');
        if (!$this->param_token) {
            die;
        }

        if ($_POST['type'] == 'navinfo') {
            $this->processNavigationStats();
        } elseif ($_POST['type'] == 'pagetime') {
            $this->processPageTime();
        } else {
            exit;
        }
    }

    /**
     * Log statistics on navigation (resolution, plugins, etc.)
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function processNavigationStats()
    {
        $idGuest = Tools::getIntValue('id_guest');
        if (sha1($idGuest._COOKIE_KEY_) != $this->param_token) {
            die;
        }

        $guest = new Guest((int) substr($_POST['id_guest'], 0, 10));
        $guest->javascript = true;
        $guest->screen_resolution_x = (int) substr($_POST['screen_resolution_x'], 0, 5);
        $guest->screen_resolution_y = (int) substr($_POST['screen_resolution_y'], 0, 5);
        $guest->screen_color = (int) substr($_POST['screen_color'], 0, 3);
        $guest->sun_java = (int) substr($_POST['sun_java'], 0, 1);
        $guest->adobe_flash = (int) substr($_POST['adobe_flash'], 0, 1);
        $guest->adobe_director = (int) substr($_POST['adobe_director'], 0, 1);
        $guest->apple_quicktime = (int) substr($_POST['apple_quicktime'], 0, 1);
        $guest->real_player = (int) substr($_POST['real_player'], 0, 1);
        $guest->windows_media = (int) substr($_POST['windows_media'], 0, 1);
        $guest->update();
    }

    /**
     * Log statistics on time spend on pages
     *
     * @return void
     *
     * @throws PrestaShopException
     */
    protected function processPageTime()
    {
        $idConnection = Tools::getIntValue('id_connections');
        $time = Tools::getIntValue('time');
        $timeStart = Tools::getValue('time_start');
        $idPage = Tools::getIntValue('id_page');

        if (sha1($idConnection.$idPage.$timeStart._COOKIE_KEY_) != $this->param_token) {
            die;
        }

        if ($time <= 0) {
            die;
        }

        Connection::setPageTime($idConnection, $idPage, substr($timeStart, 0, 19), $time);
    }
}
