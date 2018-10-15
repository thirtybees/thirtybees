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
 * Class UploaderCore
 *
 * @since 1.0.0
 */
class UploaderCore
{
    const DEFAULT_MAX_SIZE = 10485760;

    private $_check_file_size;
    private $_accept_types;
    private $_files;
    private $_max_size;
    private $_name;
    private $_save_path;

    /**
     * UploaderCore constructor.
     *
     * @param null $name
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function __construct($name = null)
    {
        $this->setName($name);
        $this->setCheckFileSize(true);
        $this->files = [];
    }

    /**
     * @param $value
     *
     * @return $this
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setCheckFileSize($value)
    {
        $this->_check_file_size = $value;

        return $this;
    }

    /**
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getFiles()
    {
        if (!isset($this->_files)) {
            $this->_files = [];
        }

        return $this->_files;
    }

    /**
     * @param null $dest
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function process($dest = null)
    {
        $upload = isset($_FILES[$this->getName()]) ? $_FILES[$this->getName()] : null;

        if ($upload && is_array($upload['tmp_name'])) {
            $tmp = [];
            foreach ($upload['tmp_name'] as $index => $value) {
                $tmp[$index] = [
                    'tmp_name' => $upload['tmp_name'][$index],
                    'name'     => $upload['name'][$index],
                    'size'     => $upload['size'][$index],
                    'type'     => $upload['type'][$index],
                    'error'    => $upload['error'][$index],
                ];

                $this->files[] = $this->upload($tmp[$index], $dest);
            }
        } elseif ($upload) {
            $this->files[] = $this->upload($upload, $dest);
        }

        return $this->files;
    }

    /**
     * @return mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * @param $value
     *
     * @return $this
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setName($value)
    {
        $this->_name = $value;

        return $this;
    }

    /**
     * @param      $file
     * @param null $dest
     *
     * @return mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function upload($file, $dest = null)
    {
        if ($this->validate($file)) {
            if (isset($dest) && is_dir($dest)) {
                $filePath = $dest;
            } else {
                $filePath = $this->getFilePath(isset($dest) ? $dest : $file['name']);
            }

            if ($file['tmp_name'] && is_uploaded_file($file['tmp_name'])) {
                move_uploaded_file($file['tmp_name'], $filePath);
            } else {
                // Non-multipart uploads (PUT method support)
                file_put_contents($filePath, fopen('php://input', 'r'));
            }

            $fileSize = $this->_getFileSize($filePath, true);

            if ($fileSize === $file['size']) {
                $file['save_path'] = $filePath;
            } else {
                $file['size'] = $fileSize;
                unlink($filePath);
                $file['error'] = Tools::displayError('Server file size is different from local file size');
            }
        }

        return $file;
    }

    /**
     * @param $file
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function validate(&$file)
    {
        $file['error'] = $this->checkUploadError($file['error']);

        if ($file['error']) {
            return false;
        }

        $postMaxSize = $this->getPostMaxSizeBytes();

        if ($postMaxSize && ($this->_getServerVars('CONTENT_LENGTH') > $postMaxSize)) {
            $file['error'] = Tools::displayError('The uploaded file exceeds the post_max_size directive in php.ini');

            return false;
        }

        if (preg_match('/\%00/', $file['name'])) {
            $file['error'] = Tools::displayError('Invalid file name');

            return false;
        }

        $types = $this->getAcceptTypes();

        //TODO check mime type.
        if (isset($types) && !in_array(mb_strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)), $types)) {
            $file['error'] = Tools::displayError('Filetype not allowed');

            return false;
        }

        if ($this->checkFileSize() && $file['size'] > $this->getMaxSize()) {
            $file['error'] = sprintf(Tools::displayError('File (size : %1s) is too big (max : %2s)'), $file['size'], $this->getMaxSize());

            return false;
        }

        return true;
    }

    /**
     * @param $errorCode
     *
     * @return array|int|mixed|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function checkUploadError($errorCode)
    {
        $error = 0;
        switch ($errorCode) {
            case 1:
                $error = sprintf(Tools::displayError('The uploaded file exceeds %s'), ini_get('upload_max_filesize'));
                break;
            case 2:
                $error = sprintf(Tools::displayError('The uploaded file exceeds %s'), ini_get('post_max_size'));
                break;
            case 3:
                $error = Tools::displayError('The uploaded file was only partially uploaded');
                break;
            case 4:
                $error = Tools::displayError('No file was uploaded');
                break;
            case 6:
                $error = Tools::displayError('Missing temporary folder');
                break;
            case 7:
                $error = Tools::displayError('Failed to write file to disk');
                break;
            case 8:
                $error = Tools::displayError('A PHP extension stopped the file upload');
                break;
            default:
                break;
        }

        return $error;
    }

    /**
     * @return int PHP setting 'post_max_size', converted to bytes.
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getPostMaxSizeBytes()
    {
        $postMaxSize = ini_get('post_max_size');
        $bytes = (int) trim($postMaxSize);
        $last = strtolower(substr($postMaxSize, -1));

        switch ($last) {
            case 'g':
                $bytes *= 1024;
            case 'm':
                $bytes *= 1024;
            case 'k':
                $bytes *= 1024;
        }

        return $bytes;
    }

    /**
     * @param $var
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function _getServerVars($var)
    {
        return (isset($_SERVER[$var]) ? $_SERVER[$var] : '');
    }

    /**
     * @return mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getAcceptTypes()
    {
        return $this->_accept_types;
    }

    /**
     * @param $value
     *
     * @return $this
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setAcceptTypes($value)
    {
        if (is_array($value) && count($value)) {
            $value = array_map(['Tools', 'strtolower'], $value);
        }
        $this->_accept_types = $value;

        return $this;
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function checkFileSize()
    {
        return (isset($this->_check_file_size) && $this->_check_file_size);
    }

    /**
     * @return mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getMaxSize()
    {
        if (!isset($this->_max_size) || empty($this->_max_size)) {
            $this->setMaxSize(static::DEFAULT_MAX_SIZE);
        }

        return $this->_max_size;
    }

    /**
     * @param $value
     *
     * @return $this
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setMaxSize($value)
    {
        $this->_max_size = intval($value);

        return $this;
    }

    /**
     * @param null $fileName
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getFilePath($fileName = null)
    {
        if (!isset($fileName)) {
            return tempnam($this->getSavePath(), $this->getUniqueFileName());
        }

        return $this->getSavePath().$fileName;
    }

    /**
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getSavePath()
    {
        if (!isset($this->_save_path)) {
            $this->setSavePath(_PS_UPLOAD_DIR_);
        }

        return $this->_normalizeDirectory($this->_save_path);
    }

    /**
     * @param $value
     *
     * @return $this
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setSavePath($value)
    {
        $this->_save_path = $value;

        return $this;
    }

    /**
     * @param $directory
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function _normalizeDirectory($directory)
    {
        $last = $directory[strlen($directory) - 1];

        if (in_array($last, ['/', '\\'])) {
            $directory[strlen($directory) - 1] = DIRECTORY_SEPARATOR;

            return $directory;
        }

        $directory .= DIRECTORY_SEPARATOR;

        return $directory;
    }

    /**
     * @param string $prefix
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getUniqueFileName($prefix = 'PS')
    {
        return uniqid($prefix, true);
    }

    /**
     * @param      $filePath
     * @param bool $clearStatCache
     *
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function _getFileSize($filePath, $clearStatCache = false)
    {
        if ($clearStatCache) {
            clearstatcache(true, $filePath);
        }

        return filesize($filePath);
    }
}
