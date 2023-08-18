<?php
/**
 * Copyright (C) 2023-2023 thirty bees
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
 * @copyright 2023-2023 thirty bees
 * @license   Open Software License (OSL 3.0)
 */

namespace Thirtybees\Core\Tracking\Extractor;

use Thirtybees\Core\Tracking\DataExtractor;

/**
 * Class SystemExtractorCore
 */
class SystemExtractorCore extends DataExtractor
{

    /**
     * Returns data name
     *
     * @return string
     */
    public function getName()
    {
        return $this->l('Thirtybees system information');
    }

    /**
     * Returns detailed information about this data
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->l('Information about your thirty bees version');
    }

    /**
     * Extracts value
     *
     * @return array
     */
    public function extractValue()
    {
        return [
            'version' => _TB_VERSION_,
            'revision' => _TB_REVISION_
        ];
    }
}
