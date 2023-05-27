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
 * Class AttachmentControllerCore
 */
class AttachmentControllerCore extends FrontController
{
    /**
     * Post process
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function postProcess()
    {
        $a = new Attachment(Tools::getIntValue('id_attachment'), $this->context->language->id);
        if (!$a->id) {
            Tools::redirect('index.php');
        }

        Hook::triggerEvent('actionDownloadAttachment', ['attachment' => &$a]);

        if (ob_get_level() && ob_get_length() > 0) {
            ob_end_clean();
        }

        header('Content-Transfer-Encoding: binary');
        header('Content-Type: '.$a->mime);
        header('Content-Length: '.filesize(_PS_DOWNLOAD_DIR_.$a->file));
        header('Content-Disposition: attachment; filename="'.mb_convert_encoding($a->file_name, 'ISO-8859-1', 'UTF-8').'"');
        @set_time_limit(0);
        readfile(_PS_DOWNLOAD_DIR_.$a->file);
        exit;
    }
}
