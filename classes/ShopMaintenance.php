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

/**
 * Class ShopMaintenance
 *
 * This class implements tasks for maintaining the shop installation, to be
 * run on a regular schedule. It gets called by an asynchronous Ajax request
 * in DashboardController.
 */
class ShopMaintenanceCore
{
    /**
     * Run tasks as needed. Should take care of running tasks not more often
     * than needed and that one run takes not longer than a few seconds.
     *
     * This method gets triggered by the 'getNotifications' Ajax request, so
     * every two minutes while somebody has back office open.
     *
     * @throws PrestaShopException
     */
    public static function run()
    {
        $now = time();
        $lastRun = Configuration::getGlobalValue('SHOP_MAINTENANCE_LAST_RUN');
        if ($now - $lastRun > 86400) {
            // Run daily tasks.
            static::adjustThemeHeaders();
            static::optinShop();
            static::cleanAdminControllerMessages();
            static::cleanOldLogFiles();
            static::cleanOldThemeCacheFiles();
            static::deleteOldErrorLogEntries();

            Configuration::updateGlobalValue('SHOP_MAINTENANCE_LAST_RUN', $now);
        }
    }

    /**
     * Correct the "generator" meta tag in templates. Technology detection
     * sites like builtwith.com don't recognize thirty bees technology if the
     * theme template inserts a meta tag "generator" for PrestaShop.
     *
     * @return void
     */
    public static function adjustThemeHeaders()
    {
        foreach (scandir(_PS_ALL_THEMES_DIR_) as $themeDir) {
            if ( ! is_dir(_PS_ALL_THEMES_DIR_.$themeDir)
                || in_array($themeDir, ['.', '..'])) {
                continue;
            }

            $headerPath = _PS_ALL_THEMES_DIR_.$themeDir.'/header.tpl';
            if (is_writable($headerPath)) {
                $header = file_get_contents($headerPath);
                $newHeader = preg_replace('/<\s*meta\s*name\s*=\s*["\']generator["\']\s*content\s*=\s*["\'].*["\']\s*>/i',
                    '<meta name="generator" content="thirty bees">', $header);
                if ($newHeader !== $header) {
                    file_put_contents($headerPath, $newHeader);
                    Tools::clearSmartyCache();
                }
            }
        }
    }

    /**
     * Handle shop optin.
     *
     * @throws PrestaShopException
     */
    public static function optinShop()
    {
        $name = Configuration::STORE_REGISTERED;
        if ( ! Configuration::get($name)) {
            $employees = Employee::getEmployeesByProfile(_PS_ADMIN_PROFILE_);
            // Usually there's only one employee when we run this code.
            foreach ($employees as $employee) {
                $employee = new Employee($employee['id_employee']);
                $employee->optin = true;
                if ($employee->update()) {
                    Configuration::updateValue($name, 1);
                }
            }
        }
    }

    /**
     * Delete lost AdminController messages.
     *
     * @return void
     */
    public static function cleanAdminControllerMessages()
    {
        $name = AdminController::MESSAGE_CACHE_PATH;
        $nameLength = strlen($name);
        foreach (scandir(_PS_CACHE_DIR_) as $candidate) {
            if (substr($candidate, 0, $nameLength) === $name) {
                $path = _PS_CACHE_DIR_.'/'.$candidate;
                if (time() - filemtime($path) > 3600) {
                    unlink($path);
                }
            }
        }
    }

    /**
     * Delete all old .log files in the /log/ directory based on the log retention period set in Performance.
     *
     * @return void
     * @throws PrestaShopException
     */
    public static function cleanOldLogFiles()
    {
        $now = time();
        $days = Configuration::getLogsRetentionPeriod();
        $oldlogdeleteperiod = $days * 86400;
        $logDir = _PS_ROOT_DIR_ . '/log/';

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($logDir));
        foreach ($iterator as $item) {
            $filePath = $item->getPathname();
            if (is_file($filePath) && pathinfo($filePath, PATHINFO_EXTENSION) === 'log' && is_writable($filePath)) {
                if ($now - filemtime($filePath) > $oldlogdeleteperiod) {
                    unlink($filePath);
                }
            }
        }
    }

    /**
     * Delete all old .js and .css files in /themes/../cache/ directories based on the JS and CSS retention period set in Performance.
     *
     * @return void
     * @throws PrestaShopException
     */
    public static function cleanOldThemeCacheFiles()
    {
        $days = Configuration::getCCCAssetsRetentionPeriod();
        $themesDir = _PS_ROOT_DIR_ . '/themes/';
        $now = time();
        $themecachedeleteperiod = $days * 86400;

        foreach (scandir($themesDir) as $themeDir) {
            $cacheDir = $themesDir . $themeDir . '/cache/';
            if (is_dir($cacheDir) && !in_array($themeDir, ['.', '..'])) {
                foreach (scandir($cacheDir) as $file) {
                    $filePath = $cacheDir . $file;
                    $extension = pathinfo($filePath, PATHINFO_EXTENSION);
                    if (is_file($filePath) && ($extension === 'js' || $extension === 'css')) {
                        if ($now - filemtime($filePath) > $themecachedeleteperiod) {
                            unlink($filePath);
                        }
                    }
                }
            }
        }
    }

    /**
     * Delete all old line entries in /error_log and /_PS_ADMIN_DIR_/error_log based on the log retention period set in Performance.
     *
     * @return void
     */
    protected static function deleteOldErrorLogEntries()
    {
        // Construct the log file paths
        $logFilePaths = [
            _PS_ROOT_DIR_ . '/error_log',
            _PS_ADMIN_DIR_ . '/error_log'
        ];

        foreach ($logFilePaths as $logFilePath) {
            if ($logFilePath && is_file($logFilePath) && is_writable($logFilePath)) {
                // Get the current time
                $currentTime = time();

                // Calculate the cutoff time
                $days = Configuration::getLogsRetentionPeriod();
                $cutoffTime = $currentTime - ($days * 86400);

                // Temporary file to store new content
                $tempFilePath = $logFilePath . '.tmp';
                $tempFile = fopen($tempFilePath, 'w');

                // Date pattern to match log entry dates
                $datePattern = '/^\[(\d{2}-[A-Za-z]{3}-\d{4} \d{2}:\d{2}:\d{2} [A-Za-z\/]+)\]/';

                // Open the original file for reading
                $file = fopen($logFilePath, 'r');
                if ($file) {
                    while (($line = fgets($file)) !== false) {
                        if (preg_match($datePattern, $line, $matches)) {
                            $logTime = strtotime($matches[1]);
                            if ($logTime >= $cutoffTime) {
                                // Write the current line and all following lines to the temp file
                                fwrite($tempFile, $line);
                                while (($line = fgets($file)) !== false) {
                                    fwrite($tempFile, $line);
                                }
                                break; // Stop processing as we have written all the necessary lines
                            }
                        }
                    }
                    fclose($file);
                }
                fclose($tempFile);

                // Get the original file's last modified time
                $lastModifiedTime = filemtime($logFilePath);

                // Replace the original file with the temp file
                rename($tempFilePath, $logFilePath);

                // Restore the original last modified time
                touch($logFilePath, $lastModifiedTime);
            }
        }
    }

}