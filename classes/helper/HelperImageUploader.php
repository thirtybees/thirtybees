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
 * Class HelperImageUploaderCore
 *
 * @since 1.0.0
 */
class HelperImageUploaderCore extends HelperUploader
{
    /**
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getMaxSize()
    {
        return (int) Tools::getMaxUploadSize();
    }

    /**
     * @return string
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function getSavePath()
    {
        return $this->_normalizeDirectory(_PS_TMP_IMG_DIR_);
    }

    /**
     * @param null $fileName
     *
     * @return string
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function getFilePath($fileName = null)
    {
        //Force file path
        return tempnam($this->getSavePath(), $this->getUniqueFileName());
    }

    /**
     * @param $file
     *
     * @return bool
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    protected function validate(&$file)
    {
        $file['error'] = $this->checkUploadError($file['error']);

        if ($file['error']) {
            return false;
        }

        $postMaxSize = Tools::convertBytes(ini_get('post_max_size'));

        $uploadMaxFilesize = Tools::convertBytes(ini_get('upload_max_filesize'));

        if ($postMaxSize && ($this->_getServerVars('CONTENT_LENGTH') > $postMaxSize)) {
            $file['error'] = Tools::displayError('The uploaded file exceeds the post_max_size directive in php.ini');

            return false;
        }

        if ($uploadMaxFilesize && ($this->_getServerVars('CONTENT_LENGTH') > $uploadMaxFilesize)) {
            $file['error'] = Tools::displayError('The uploaded file exceeds the upload_max_filesize directive in php.ini');

            return false;
        }

        if ($error = ImageManager::validateUpload($file, Tools::getMaxUploadSize($this->getMaxSize()), $this->getAcceptTypes())) {
            $file['error'] = $error;

            return false;
        }

        if ($file['size'] > $this->getMaxSize()) {
            $file['error'] = sprintf(Tools::displayError('File (size : %1s) is too big (max : %2s)'), $file['size'], $this->getMaxSize());

            return false;
        }

        return true;
    }
}
