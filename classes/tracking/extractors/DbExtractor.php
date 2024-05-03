<?php
/**
 * Copyright (C) 2017-2024 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2017-2024 thirty bees
 * @license   Open Software License (OSL 3.0)
 */

namespace Thirtybees\Core\Tracking\Extractor;

use PrestaShopDatabaseException;
use PrestaShopException;
use Thirtybees\Core\Tracking\DataExtractor;
use Db;

/**
 * Class DbExtractorCore
 */
class DbExtractorCore extends DataExtractor
{

    /**
     * Returns data name
     *
     * @return string
     */
    public function getName()
    {
        return $this->l('Database version');
    }

    /**
     * Returns detailed information about this data
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->l('Information about your database server version');
    }

    /**
     * Extracts value
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function extractValue()
    {
        $connection = Db::getInstance();
        return [
            'version' => $connection->getVersion(),
            'comment' => $this->getVersionComment($connection)
        ];
    }

    /**
     * @param Db $connection
     * @return string
     */
    protected function getVersionComment($connection)
    {
        try {
            $result = $connection->getArray("SHOW VARIABLES LIKE 'version_comment'");
            if (isset($result[0]['Value'])) {
                return $result[0]['Value'];
            }
        } catch (\Exception $ignored) {}
        return '';
    }
}
