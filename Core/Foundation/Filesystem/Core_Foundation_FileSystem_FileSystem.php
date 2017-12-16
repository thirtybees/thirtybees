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
 * Class Core_Foundation_FileSystem_FileSystem
 *
 * @since 1.0.0
 */
// @codingStandardsIgnoreStart
class Core_Foundation_FileSystem_FileSystem
{
    // @codingStandardsIgnoreStartingStandardsIgnoreEnd

    /**
     * Replaces directory separators with the system's native one
     * and trims the trailing separator.
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function normalizePath($path)
    {
        return rtrim(
            str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path),
            DIRECTORY_SEPARATOR
        );
    }

    /**
     * @param string $a
     * @param string $b
     *
     * @return string
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    protected function joinTwoPaths($a, $b)
    {
        return $this->normalizePath($a) . DIRECTORY_SEPARATOR . $this->normalizePath($b);
    }

    /**
     * Joins an arbitrary number of paths, normalizing them along the way.
     *
     * @return string|null
     * @throws Core_Foundation_FileSystem_Exception
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function joinPaths()
    {
        if (func_num_args() < 2) {
            throw new Core_Foundation_FileSystem_Exception('joinPaths requires at least 2 arguments.');
        } elseif (func_num_args() === 2) {
            $arg0 = func_get_arg(0);
            $arg1 = func_get_arg(1);

            return $this->joinTwoPaths($arg0, $arg1);
        } elseif (func_num_args() > 2) {
            $funcArgs = func_get_args();
            $arg0 = func_get_arg(0);

            return $this->joinPaths(
                $arg0,
                call_user_func_array([$this, 'joinPaths'], array_slice($funcArgs, 1))
            );
        }

        return null;
    }

    /**
     * Performs a depth first listing of directory entries.
     * Throws exception if $path is not a file.
     * If $path is a file and not a directory, just gets the file info for it
     * and return it in an array.
     *
     * @param string $path
     *
     * @return array of SplFileInfo object indexed by file path
     * @throws Core_Foundation_FileSystem_Exception
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function listEntriesRecursively($path)
    {
        if (!file_exists($path)) {
            throw new Core_Foundation_FileSystem_Exception(
                sprintf(
                    'No such file or directory: %s',
                    $path
                )
            );
        }

        if (!is_dir($path)) {
            throw new Core_Foundation_FileSystem_Exception(
                sprintf(
                    '%s is not a directory',
                    $path
                )
            );
        }

        $entries = [];

        foreach (scandir($path) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $newPath = $this->joinPaths($path, $entry);
            $info = new SplFileInfo($newPath);

            $entries[$newPath] = $info;

            if ($info->isDir()) {
                $entries = array_merge(
                    $entries,
                    $this->listEntriesRecursively($newPath)
                );
            }
        }

        return $entries;
    }

    /**
     * Filter used by listFilesRecursively.
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    protected function matchOnlyFiles(SplFileInfo $info)
    {
        return $info->isFile();
    }

    /**
     * Same as listEntriesRecursively but returns only files.
     *
     * @return array
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function listFilesRecursively($path)
    {
        return array_filter(
            $this->listEntriesRecursively($path),
            [$this, 'matchOnlyFiles']
        );
    }
}
