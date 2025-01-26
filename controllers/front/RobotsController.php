<?php
/**
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2025 thirty bees
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
 *  @copyright 2017-2025 thirty bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */
class RobotsController extends FrontController
{
    public $php_self = 'robots';

    public function initContent()
    {
        parent::initContent();

        // Run migration and setup only if not already completed
        if (!Configuration::get('ROBOTS_MIGRATION_DONE')) {
            $this->migrateAndSetup();
            Configuration::updateValue('ROBOTS_MIGRATION_DONE', 1);
        }

        // Fetch robots.txt content for the current shop
        $id_shop = (int) Context::getContext()->shop->id;
        $robots_content = Db::getInstance()->getValue(
            'SELECT robots_content
             FROM ' . _DB_PREFIX_ . 'robots
             WHERE id_shop = ' . $id_shop
        );

        // Send robots.txt content
        header('Content-Type: text/plain');
        header('X-Content-Type-Options: nosniff');

        if ($robots_content) {
            echo $robots_content;
        } else {
            // Log the missing robots.txt content for this shop
            $message = sprintf('No robots.txt content found for shop ID: %d. Please fix this in your SEO & URLs section.', $id_shop);
            error_log($message);

            if (class_exists('Logger')) {
                Logger::addLog($message, 3, null, 'RobotsController', null, true); // Severity 3 = Warning
            }

            // Send fallback robots.txt
            echo "User-agent: *\n";
            echo "Allow: /modules/*.css\n";
            echo "Allow: /modules/*.js\n";
            echo "Disallow: */classes/\n";
            echo "Disallow: */config/\n";
            echo "Disallow: */download/\n";
            echo "Disallow: */mails/\n";
            echo "Disallow: */translations/\n";
            echo "Disallow: */tools/\n";
        }

        die();
    }

    /**
     * Perform migration and setup.
     */
    private function migrateAndSetup()
    {
        // Step 1: Ensure robots table exists
        $this->ensureRobotsTableExists();

        // Step 2: Migrate robots.txt file contents into the robots table for all shops
        $this->migrateRobotsTxtToTable();

        // Step 3: Ensure 'robots' entry exists in the meta table
        $this->ensureMetaEntryExists();

        // Step 4: Ensure SEO URLs exist in meta_lang table for all shops and languages
        $this->ensureSeoUrlsExist();
    }

    /**
     * Ensure the robots table exists.
     */
    private function ensureRobotsTableExists()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'robots (
                    id_shop INT UNSIGNED NOT NULL,
                    robots_content TEXT NOT NULL,
                    PRIMARY KEY (id_shop)
                ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4';

        if (Db::getInstance()->execute($sql)) {
            if (class_exists('Logger')) {
                Logger::addLog('Checked and ensured existence of the robots table.', 1, null, 'RobotsController', null, true);
            }
        } else {
            $errorMessage = 'Failed to create or ensure the existence of the robots table.';
            if (class_exists('Logger')) {
                Logger::addLog($errorMessage, 3, null, 'RobotsController', null, true);
            }
            error_log($errorMessage);
        }
    }

    /**
     * Migrate contents of robots.txt into the robots table for all shops.
     */
    private function migrateRobotsTxtToTable()
    {
        $robotsFilePath = _PS_ROOT_DIR_ . '/robots.txt';

        if (!file_exists($robotsFilePath)) {
            return;
        }

        $fileContents = file_get_contents($robotsFilePath);
        $sitemapRegex = '/Sitemap:.*/i';
        $cleanedContent = preg_replace($sitemapRegex, '', $fileContents);

        $shops = Shop::getShops(false);

        $dataToInsert = [];
        foreach ($shops as $shop) {
            $id_shop = (int) $shop['id_shop'];

            $exists = Db::getInstance()->getValue(
                'SELECT 1 FROM ' . _DB_PREFIX_ . 'robots WHERE id_shop = ' . $id_shop
            );

            if ($exists) {
                continue;
            }

            // Append the correct Sitemap URL for this shop
            $sitemapUrl = Context::getContext()->shop->getBaseURL(true) . $id_shop . '_index_sitemap.xml';
            $finalContent = $cleanedContent . "\nSitemap: " . $sitemapUrl;

            $dataToInsert[] = [
                'id_shop' => $id_shop,
                'robots_content' => pSQL($finalContent, true),
            ];
        }

        if (!empty($dataToInsert)) {
            Db::getInstance()->insert('robots', $dataToInsert);
        }

        $newRobotsFilePath = _PS_ROOT_DIR_ . '/robots.txt.old';
        if (!rename($robotsFilePath, $newRobotsFilePath)) {
            if (class_exists('Logger')) {
                Logger::addLog('Failed to rename robots.txt to robots.txt.old', 3, null, 'RobotsController', null, true);
            }
            error_log('Failed to rename robots.txt to robots.txt.old');
        }
    }

    /**
     * Ensure the 'robots' entry exists in the meta table.
     */
    private function ensureMetaEntryExists()
    {
        $insertQuery = '
            INSERT IGNORE INTO ' . _DB_PREFIX_ . 'meta (page, configurable)
            VALUES ("robots", 0)
        ';
        Db::getInstance()->execute($insertQuery);
    }

    /**
     * Ensure SEO URLs exist in the meta_lang table for all shops and languages.
     */
    private function ensureSeoUrlsExist()
    {
        $id_meta = Db::getInstance()->getValue(
            'SELECT id_meta
             FROM ' . _DB_PREFIX_ . 'meta
             WHERE page = "robots"'
        );

        if (!$id_meta) {
            return;
        }

        $shops = Shop::getShops(false);
        $languages = Language::getLanguages(false);

        foreach ($shops as $shop) {
            foreach ($languages as $language) {
                $id_lang = (int) $language['id_lang'];
                $id_shop = (int) $shop['id_shop'];

                $insertQuery = '
                    INSERT IGNORE INTO ' . _DB_PREFIX_ . 'meta_lang (id_meta, id_lang, id_shop, url_rewrite, title)
                    VALUES (' . (int) $id_meta . ', ' . $id_lang . ', ' . $id_shop . ', "robots.txt", "robots.txt")
                ';

                Db::getInstance()->execute($insertQuery);
            }
        }
    }
}