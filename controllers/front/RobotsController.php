<?php

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

            // Optionally log to Collect Logs module
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
        // Check if the robots table exists and create it if it doesn't
        $sql = 'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'robots (
                    id_shop INT UNSIGNED NOT NULL,
                    robots_content TEXT NOT NULL,
                    PRIMARY KEY (id_shop)
                ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4';

        if (Db::getInstance()->execute($sql)) {
            // Log successful table creation (only if it was created for the first time)
            if (class_exists('Logger')) {
                Logger::addLog('Checked and ensured existence of the robots table.', 1, null, 'RobotsController', null, true);
            }
        } else {
            // Log failure to create table
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

        // Check if robots.txt exists
        if (!file_exists($robotsFilePath)) {
            return; // No file to migrate
        }

        $fileContents = file_get_contents($robotsFilePath);
        $sitemapRegex = '/Sitemap:.*/i'; // Regex to find Sitemap entries
        $cleanedContent = preg_replace($sitemapRegex, '', $fileContents); // Clean the file once instead of per shop

        $shops = Shop::getShops(false);

        // Prepare the insert query for multiple rows
        $dataToInsert = [];
        foreach ($shops as $shop) {
            $id_shop = (int) $shop['id_shop'];

            // Check if robots content already exists for this shop
            $exists = Db::getInstance()->getValue(
                'SELECT 1 FROM ' . _DB_PREFIX_ . 'robots WHERE id_shop = ' . $id_shop
            );

            if ($exists) {
                continue; // Skip migration if content already exists
            }

            // Append the correct Sitemap URL for this shop
            $sitemapUrl = ($shop['ssl'] ? 'https://' : 'http://') . $shop['domain'] . $shop['physical_uri'] . $id_shop . '_index_sitemap.xml';
            $finalContent = $cleanedContent . "\nSitemap: " . $sitemapUrl;

            // Add the data to the batch insert array
            $dataToInsert[] = [
                'id_shop' => $id_shop,
                'robots_content' => pSQL($finalContent, true),
            ];
        }

        // Perform batch insert if there are entries to insert
        if (!empty($dataToInsert)) {
            Db::getInstance()->insert('robots', $dataToInsert);
        }

        // Rename the robots.txt file to robots.txt.old after migration
        $newRobotsFilePath = _PS_ROOT_DIR_ . '/robots.txt.old';
        if (!rename($robotsFilePath, $newRobotsFilePath)) {
            // Log the error if renaming fails
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
        // Attempt to insert the 'robots' entry if it doesn't exist
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
            return; // Safety check, should not happen
        }

        $shops = Shop::getShops(false);
        $languages = Language::getLanguages(false);

        foreach ($shops as $shop) {
            foreach ($languages as $language) {
                $id_lang = (int) $language['id_lang'];
                $id_shop = (int) $shop['id_shop'];

                // Use raw SQL with interpolated values
                $insertQuery = '
                    INSERT IGNORE INTO ' . _DB_PREFIX_ . 'meta_lang (id_meta, id_lang, id_shop, url_rewrite, title)
                    VALUES (' . (int) $id_meta . ', ' . $id_lang . ', ' . $id_shop . ', "robots.txt", "robots.txt")
                ';

                Db::getInstance()->execute($insertQuery);
            }
        }
    }
}