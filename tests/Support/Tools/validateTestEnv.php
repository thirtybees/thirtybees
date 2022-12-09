<?php
/**
 * Copyright (C) 2022 thirty bees
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
 * @copyright 2022 thirty bees
 * @license   Open Software License (OSL 3.0)
 */
/** @noinspection PhpUnhandledExceptionInspection */

/**
 * Helper script that validates test environment
 */
namespace Tests\Support\Tools;
require_once __DIR__ . '/../TestClassIndex.php';

// this script is intended for CLI mode only

use Tests\Support\TestClassIndex;

if (php_sapi_name() !== 'cli') {
    exit;
}

$classIndex = new TestClassIndex();
$missing = $classIndex->getMissingOverrides();
$extra = $classIndex->getExtraOverrides();

if ($missing || $extra) {
    foreach ($missing as $name) {
        echo "Missing override for $name\n";
    }
    foreach ($extra as $name) {
        echo "Extra override for $name\n";
    }
    exit(1);
}
exit(0);
