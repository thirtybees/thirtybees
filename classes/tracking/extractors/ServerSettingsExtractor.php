<?php
/**
 * Copyright (C) 2021-2021 thirty bees
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
 * @copyright 2021-2021 thirty bees
 * @license   Open Software License (OSL 3.0)
 */

namespace Thirtybees\Core\Tracking\Extractor;

use Thirtybees\Core\Tracking\DataExtractor;

/**
 * Class ServerSettingsExtractorCore
 *
 * @since 1.3.0
 */
class ServerSettingsExtractorCore extends DataExtractor
{
    /**
     * Returns data name
     *
     * @return string
     */
    public function getName()
    {
        return $this->l('PHP server configuration');
    }

    /**
     * Returns detailed information about this data
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->l('Information about configuration of your PHP server. Includes information such as memory limit or max execution time');
    }

    /**
     * Extracts value
     *
     * @return mixed
     */
    public function extractValue()
    {
        return [
            'serverSoftware'   => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '',
            'memoryLimit'      => @ini_get('memory_limit'),
            'maxExecutionTime' => @ini_get('max_execution_time'),
        ];
    }
}
