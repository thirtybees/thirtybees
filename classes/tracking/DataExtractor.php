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

namespace Thirtybees\Core\Tracking;

use PrestaShopException;
use RuntimeException;
use Translate;

/**
 * Class DataExtractor
 *
 * @since 1.3.0
 */
abstract class DataExtractorCore
{
    const GROUP_ENVIRONMENT = 'environment';

    /**
     * Return extractor id
     * @return string
     */
    public function getId()
    {
        $class = get_class($this);
        if (preg_match('#^.*\\\([a-zA-Z]+)Extractor(Core)*$#', get_class($this), $matches)) {
            return lcfirst($matches[1]);
        } else {
            throw new RuntimeException("Invariant: failed to resolve extractor ID for class " . $class);
        }
    }

    /**
     * Return list of extractor groups
     *
     * @return array
     */
    public static function getGroups()
    {
        return [
            static::GROUP_ENVIRONMENT => [
                'name' => Translate::getAdminTranslation('Environment', 'AdminDataCollection'),
                'extractors' => [
                    'phpVersion',
                    'phpExtensions',
                    'serverSettings',
                    'db'
                ]
            ]
        ];
    }

    /**
     * Instantiates extractor
     *
     * @param $id
     * @return DataExtractor
     * @throws PrestaShopException
     */
    public static function getExtractor($id)
    {
        $clazz = '\\Thirtybees\\Core\\Tracking\\Extractor\\' . ucfirst($id) . 'Extractor';
        if (! class_exists($clazz)) {
            throw new PrestaShopException("Extractor class for '$id' not found");
        }
        return new $clazz();
    }

    /**
     * Translates string
     *
     * @param string $str
     * @return string
     */
    protected function l($str)
    {
        return Translate::getAdminTranslation($str, 'AdminDataCollection');
    }

    /**
     * Returns data name
     *
     * @return string
     */
    public abstract function getName();

    /**
     * Returns detailed information about this data
     *
     * @return string
     */
    public abstract function getDescription();

    /**
     * Extracts value
     *
     * @return mixed
     */
    public abstract function extractValue();

}
