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
 * Class Core_Business_Email_EmailLister
 */
// @codingStandardsIgnoreStart
class Core_Business_Email_EmailLister
{
    // @codingStandardsIgnoreEnd

    protected $filesystem;

    /**
     * Core_Business_Email_EmailLister constructor.
     *
     * @param Core_Foundation_FileSystem_FileSystem $fs
     */
    public function __construct(Core_Foundation_FileSystem_FileSystem $fs)
    {
        // Register dependencies
        $this->filesystem = $fs;
    }

    /**
     * Return the list of available mails
     *
     * @param string $dir
     *
     * @return array|null
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function getAvailableMails($dir)
    {
        if (!is_dir($dir)) {
            return null;
        }

        $mailDirectory = $this->filesystem->listEntriesRecursively($dir);
        $mailList = [];

        // Remove unwanted .html / .txt / .tpl / .php / . / ..
        foreach ($mailDirectory as $mail) {
            if (strpos($mail->getFilename(), '.') !== false) {
                $tmp = explode('.', $mail->getFilename());

                // Check for filename existence (left part) and if extension is html (right part)
                if (($tmp === false || !isset($tmp[0])) || (isset($tmp[1]) && $tmp[1] !== 'html')) {
                    continue;
                }

                $mailNameNoExt = $tmp[0];
                if (!in_array($mailNameNoExt, $mailList)) {
                    $mailList[] = $mailNameNoExt;
                }
            }
        }

        return $mailList;
    }

    /**
     * Give in input getAvailableMails(), will output a human readable and proper string name
     *
     * @param string $mailName
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0
     */
    public function getCleanedMailName($mailName)
    {
        if (strpos($mailName, '.') !== false) {
            $tmp = explode('.', $mailName);

            if ($tmp === false || !isset($tmp[0])) {
                return $mailName;
            }

            $mailName = $tmp[0];
        }

        return ucfirst(str_replace(['_', '-'], ' ', $mailName));
    }
}
