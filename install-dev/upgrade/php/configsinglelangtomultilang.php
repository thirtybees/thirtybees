<?php
/**
 * Copyright (C) 2018 thirty bees
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
 * @copyright 2018 thirty bees
 * @license   Open Software License (OSL 3.0)
 */

/**
 * Convert a single language configuration entry into a multi-language one. No
 * value conversion, e.g. translation, takes place. Does nothing if the entry
 * is a multi-language type already.
 *
 * @param string $configName  Name of the configuration entry.
 *
 * @since 1.0.8
 */
function configSingleLangToMultiLang($configName)
{
    require_once __DIR__.'/environment.php';

    $configValue = Configuration::get($configName);
    if ( ! $configValue) {
        // Either name doesn't exist, or it's multi-lang already.
        return;
    }

    $values = [];
    foreach (Language::getIDs(false) as $idLang) {
        $values[$idLang] = $configValue;
    }

    Configuration::deleteByName($configName);
    Configuration::updateValue($configName, $values);
}
