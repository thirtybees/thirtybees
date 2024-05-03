<?php
/**
 * Copyright (C) 2017-2024 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2017-2024 thirty bees
 * @license   Open Software License (OSL 3.0)
 */

namespace Thirtybees\Core\Package;

use Archive_Tar;
use PrestaShopException;
use SplFileInfo;
use Throwable;
use Tools;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

/**
 * Class PackageExtractorCore
 *
 * This class can be used to extract zip and tgz packages. Expected usage is to extract
 * module packages into /modules/ directory, but it can be used elsewhere as well.
 */
class PackageExtractorCore
{
    /**
     * Merge mode - when target directory exists, package content will be merged into it
     */
    const MODE_MERGE = 'MERGE';

    /**
     * Replace mode - when target directory exists, it will be replaced with package content
     */
    const MODE_REPLACE = 'REPLACE';

    /**
     * @var string target directory, into which package will be installed
     */
    protected $targetDirectory;

    /**
     * @var string temp directory, used to temporary store unzipped downloaded content
     */
    protected $tempDirectory;

    /**
     * @var int permissions for newly created directories
     */
    protected $directoryPerms = 0755;

    /**
     * @var int permissions for newly created files
     */
    protected $filePerms = 0644;

    /**
     * @var string extract mode - one of MERGE | REPLACE
     */
    protected $mode = self::MODE_MERGE;

    /**
     * @var callable validator for package files. It receives list of package files as an argument, and
     *      returns list of errors if validation fails, or null/empty list on validation success
     */
    protected $packageValidator;

    /**
     * @var array[] array containing all error messages generated during package extraction
     */
    private $errors = [];

    /**
     * @var string[] array containing all warning messages collected during package extractions
     */
    private $warnings = [];

    /**
     * PackageInstallerCore constructor.
     *
     * @param string $targetDirectory directory where to extract packages
     *
     * @throws PrestaShopException
     */
    public function __construct($targetDirectory)
    {
        // resolve target directory
        $targetDirectory = $this->normalizePath($targetDirectory, true);
        if (! is_dir($targetDirectory)) {
            throw new PrestaShopException("Target directory does not exists: $targetDirectory");
        }
        $this->targetDirectory = $targetDirectory;

        // set default temp directory
        $this->tempDirectory = $this->normalizePath(_PS_CACHE_DIR_, true) . 'tmp/';
    }

    /**
     * Main method that extracts package into target directory.
     *
     * $source parameter can be either file path or url address. Required $name parameter must contain
     * name of top_level folder in zip file that we want to extract from archive
     *
     * @param string $source filepath / url to package
     * @param string $name name of the top-level directory from zip to extract
     *
     * @return bool
     */
    public function extractPackage($source, $name)
    {
        $tempFile = null;
        try {
            $this->errors = [];
            $this->warnings = [];

            // if source is url, fetch it
            if (preg_match('/^https?:\/\//', $source)) {
                $tempFile = $this->fetchRemotePackage($source, $name);
                if (! $tempFile) {
                    return false;
                }
                $source = $tempFile;
            }

            return $this->extractLocalPackage($source, $name);
        } catch (Throwable $e) {
            return $this->error("Fatal error: " . $e->getMessage(), $e);
        } finally {
            if ($tempFile && @is_file($tempFile)) {
                @unlink($tempFile);
            }
        }
    }

    /**
     * This method returns list of top-level directories in package
     *
     * @param string $source filepath / url to package
     *
     * @return string[]
     *
     * @throws PrestaShopException
     */
    public function getPackageTopLevelDirectories($source)
    {
        $tempFile = null;
        try {
            $this->errors = [];
            $this->warnings = [];

            // if source is url, fetch it
            if (preg_match('/^https?:\/\//', $source)) {
                $tempFile = $this->fetchRemotePackage($source, 'unknown');
                if (! $tempFile) {
                    return [];
                }
                $source = $tempFile;
            }

            // check that input file exists
            if (! @is_file($source)) {
                $this->error(sprintf(Tools::displayError("File not found: %s"), $source));
                return null;
            }

            return (strtolower(substr($source, -4)) === '.zip')
                ? $this->zipTopLevelDirectories($source)
                : $this->tarTopLevelDirectories($source);

        } finally {
            if ($tempFile && @is_file($tempFile)) {
                @unlink($tempFile);
            }
        }
    }

    /**
     * This method returns list of errors that occurred during last extract package call
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * This method returns list of warnings that occurred during last extract package call
     *
     * @return array
     */
    public function getWarnings()
    {
        return $this->warnings;
    }

    /**
     * This method can be used to attach external validator. This validator will be called before package content
     * is copied to the destination. It can perform additional checks on files, and prevent installing invalid package.
     *
     * Example usage: check that <module_name>/<module_name>.php file exists
     *
     * @param callable $validator validator for package files.
     *
     * @return $this
     */
    public function setPackageValidator($validator)
    {
        $this->packageValidator = $validator;
        return $this;
    }

    /**
     * Sets merge algorithm to be used in case target directory already exists
     *
     *   MERGE - package content will be merged into existing directory, overwriting same files
     *   REPLACE - existing directory will be replaced by fresh copy from package
     *
     * @param string $mode merge mode
     *
     * @return $this
     */
    public function setMode($mode)
    {
        if ($mode === static::MODE_MERGE || $mode === static::MODE_REPLACE) {
            $this->mode = $mode;
        }
        return $this;
    }

    /**
     * Sets chmod permissions to be used for newly created directories
     *
     * @param int $directoryPerms
     *
     * @return $this
     */
    public function setDirectoryPerms($directoryPerms)
    {
        $this->directoryPerms = $directoryPerms;
        return $this;
    }

    /**
     * Sets chmod permissions to be used for newly created files
     *
     * @param int $filePerms
     *
     * @return $this
     */
    public function setFilePerms($filePerms)
    {
        $this->filePerms = $filePerms;
        return $this;
    }

    /**
     * Extracts package that already exists on filesystem
     *
     * @param string $filepath file path to zip file
     * @param string $name name of the top-level directory from zip to extract
     *
     * @return bool
     *
     * @throws Throwable
     */
    protected function extractLocalPackage($filepath, $name)
    {
        $dir = null;
        try {
            // unpack package
            $dir = $this->unpack($filepath, $name);
            if (! $dir) {
                return false;
            }
            return (
                $this->validatePackageContent($dir, $name) &&
                $this->copyPackageContent($dir, $name)
            );
        } finally {
            if ($dir) {
                Tools::deleteDirectory($dir);
            }
        }
    }

    /**
     * Validates content of extracted package. If packageValidator has been provided,
     * it will be called as well
     *
     * @param string $dir directory
     * @param string $name name of the top-level directory from zip to extract
     *
     * @return bool
     */
    protected function validatePackageContent($dir, $name)
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        $files = [];
        $relativeStart = strlen($dir) + 1;
        foreach ($iterator as $item) {
            if ($this->shouldCopyFile($item)) {
                $path = $this->normalizePath($item->getPathname());
                $relativePath = substr($path, $relativeStart);
                $filename = $item->getFilename();
                $files[$relativePath] = [
                    'path' => $path,
                    'filename' => $filename ,
                ];
            }
        }

        if (! $files) {
            return $this->error(Tools::displayError('Package is empty'));
        }

        if ($this->packageValidator) {
            $errors = call_user_func($this->packageValidator, $files, $name);
            if ($errors && is_array($errors)) {
                foreach ($errors as $error) {
                    $this->error($error);
                }
                return false;
            }
        }

        return true;
    }

    /**
     * Copies package content from staging area to real destination
     *
     * @param string $dir path to source directory
     * @param string $name name of directory to copy
     *
     * @return bool
     */
    protected function copyPackageContent($dir, $name)
    {
        if ($this->mode === static::MODE_REPLACE) {
            $targetDir = $this->targetDirectory . $name;
            if (@is_dir($targetDir)) {
                Tools::deleteDirectory($targetDir);
            }
        }

        // Copy content
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        $relativeStart = strlen($dir) + 1;
        $valid = true;
        foreach ($iterator as $item) {
            if ($this->shouldCopyFile($item)) {
                $sourcePath = $this->normalizePath($item->getPathname());
                $relativePath = substr($sourcePath, $relativeStart);
                $valid = $this->moveFile($sourcePath, $relativePath) && $valid;
            }
        }
        return $valid;
    }

    /**
     * Returns true, if file should be copied from package.
     *
     * @param SplFileInfo $file
     *
     * @return bool
     */
    protected function shouldCopyFile(SplFileInfo $file)
    {
        // check file name
        $name = $file->getFilename();
        if (in_array($name, ['.', '..'])) {
            return false;
        }

        // check parent directories
        $path = explode('/', $this->normalizePath($file->getPath()));
        foreach ($path as $dir) {
            if (in_array($dir, ['.svn', '.git', '__MACOSX'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Moves $source file to destination.
     *
     * @param string $source path to file to copy
     * @param string $relativeTarget relative path from $this->targetDirectory
     *
     * @return bool
     */
    protected function moveFile($source, $relativeTarget)
    {
        // create directory
        $path = array_filter(explode('/', $relativeTarget));
        array_pop($path);
        $dir = $this->targetDirectory . implode('/', $path);
        if (!@is_dir($dir) && !@mkdir($dir, $this->directoryPerms, true)) {
            return $this->error(sprintf(Tools::displayError('Failed to create directory %s'), $dir));
        }

        // move file
        $target = $this->targetDirectory . $relativeTarget;
        if (! @rename($source, $target)) {
            return $this->error(sprintf(Tools::displayError('Failed to create file %s'), $target));
        }

        if (! @chmod($target, $this->filePerms)) {
            $this->warning(sprintf(Tools::displayError('Failed to change file permissions for file %s'), $target));
        }

        return true;
    }

    /**
     * This method unpacks package $filepath to temporary directory.
     *
     * @param string $filepath file path to zip file
     * @param string $name name of the top-level directory from zip to extract
     *
     * @return string file path to temp directory containing $name subdirectory, or null
     *
     * @throws Throwable
     */
    protected function unpack($filepath, $name)
    {
        // check that input file exists
        if (! @is_file($filepath)) {
            $this->error(sprintf(Tools::displayError("File not found: %s"), $filepath));
            return null;
        }

        // check that temp directory exists
        if (! @is_dir($this->tempDirectory) && !@mkdir($this->tempDirectory, 0777, true)) {
            $this->error(sprintf(Tools::displayError("Temp directory not exists and can't be created: %s"), $this->tempDirectory));
            return null;
        }

        // create temporary directory for extraction
        $tempDir = tempnam($this->tempDirectory, $name . '-');
        @unlink($tempDir);
        if (! @mkdir($tempDir)) {
            $this->error(sprintf(Tools::displayError('Failed to create temporary directory: %s'), $tempDir));
            return null;
        }

        try {
            // unpack using correct algorithm
            $res = (strtolower(substr($filepath, -4)) === '.zip')
                ? $this->unzip($filepath, $tempDir)
                : $this->untar($filepath, $tempDir);

            if (! $res) {
                Tools::deleteDirectory($tempDir);
                return null;
            }

            // clean up directory content -- we want to keep only $name directory
            $found = false;
            foreach (@scandir($tempDir) as $subdir) {
                if ($subdir === '.' || $subdir === '..') {
                    continue;
                }
                $path = $tempDir . '/' . $subdir;

                // we don't want any files in top level directory
                if (is_file($path)) {
                    @unlink($path);
                }

                // if the entry is dir, check if it the wanted one
                if (is_dir($path)) {
                    if ($subdir === $name) {
                        $found = true;
                    } else {
                        Tools::deleteDirectory($path);
                    }
                }
            }

            if (! $found) {
                $this->error(sprintf(Tools::displayError("Archive does not contain top-level directory %s"), $name));
                Tools::deleteDirectory($tempDir);
                return null;
            }

            return $tempDir;
        } catch (Throwable $e) {
            // delete temp directory on any exception
            Tools::deleteDirectory($tempDir);
            throw $e;
        }
    }

    /**
     * Unzips file to $tempDir
     *
     * @param string $filepath
     * @param string $tempDir
     *
     * @return bool
     */
    protected function unzip($filepath, $tempDir)
    {
        // extract zip file
        $zip = new ZipArchive();

        // open zip archive
        if ($zip->open($filepath) !== true) {
            return $this->error(sprintf(Tools::displayError("Failed to open zip archive: %s"), $filepath));
        }

        // extract content
        if (! $zip->extractTo($tempDir)) {
            $zip->close();
            return $this->error(sprintf(Tools::displayError("Failed to extract zip archive: %s"), $filepath));
        }

        // close zip archive
        $zip->close();

        return true;
    }

    /**
     * Unpacks file to $tempDir
     *
     * @param string $filepath
     * @param string $tempDir
     *
     * @return bool
     */
    protected function untar($filepath, $tempDir)
    {
        $archive = new Archive_Tar($filepath);

        if (! $archive->extract($tempDir)) {
            return $this->error(sprintf(Tools::displayError("Failed to extract tgz archive: %s"), $filepath));
        }

        return true;
    }

    /**
     * Returns list of top-level directories from zip file
     *
     * @param string $filepath
     * @return string[]
     */
    protected function zipTopLevelDirectories($filepath)
    {
        $zip = new ZipArchive();
        $zip->open($filepath);
        $dirs = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filePath = array_filter(explode('/', $zip->getNameIndex($i)));
            if ($filePath) {
                $dirs[] = $filePath[0];
            }
        }
        return array_values(array_unique($dirs));
    }

    /**
     * Returns list of top-level directories from tgz file
     *
     * @param string $filepath
     * @return string[]
     */
    protected function tarTopLevelDirectories($filepath)
    {
        $archive = new Archive_Tar($filepath);
        $dirs = [];
        foreach ($archive->listContent() as $entry) {
            $filePath = array_filter(explode('/', $entry['filename']));
            if ($filePath) {
                $dirs[] = $filePath[0];
            }
        }
        return array_values(array_unique($dirs));
    }


    /**
     * Method to retrieve package from url
     *
     * @param string $url url address
     * @param string $name name of directory we want to extract from the package.
     *
     * @return string
     * @throws PrestaShopException
     */
    protected function fetchRemotePackage($url, $name)
    {
        // check that temp directory exists
        if (! @is_dir($this->tempDirectory) && !@mkdir($this->tempDirectory, 0777, true)) {
            $this->error(sprintf(Tools::displayError("Temp directory not exists and can't be created: %s"), $this->tempDirectory));
            return null;
        }

        // download file
        $suffix = '.zip';
        if (
            (substr($url, -7) === '.tar.gz') ||
            (substr($url, -3) === '.gz') ||
            (substr($url, -4) === '.tar') ||
            (substr($url, -4) === '.tgz')
        ) {
            $suffix = '.tgz';
        }

        $filename = $this->tempDirectory . $name . '-' . md5(Tools::passwdGen() . time()) . $suffix;
        if (! Tools::copy($url, $filename)) {
            $this->error(sprintf(Tools::displayError("Failed to download file %s"), $url));
            @unlink($filename);
            return null;
        }

        return is_file($filename) ? $filename : null;
    }

    /**
     * Helper method to save error message
     *
     * @param string $message
     * @param Throwable|null $exception
     *
     * @return false
     */
    protected function error($message, $exception = null)
    {
        $entry = [ 'message' => $message, ];
        if ($exception) {
            $entry['exception'] = $exception;
        }
        $this->errors[] = $entry;
        return false;
    }

    /**
     * Helper method to save warning message
     *
     * @param string $message
     */
    protected function warning($message)
    {
        $this->warnings[] = [
            'message' => $message,
        ];
    }

    /**
     * Helper method to covert part to normalized (linux-based) version
     *
     * @param string $path file path
     * @param bool $addTrailingSlash if true, / will be added at the end
     *
     * @return string
     */
    protected function normalizePath($path, $addTrailingSlash = false)
    {
        $path = str_replace('\\', '/', $path);
        if ($addTrailingSlash) {
            return rtrim($path, '/') . '/';
        }
        return $path;
    }

}
