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
/** @noinspection PhpUnhandledExceptionInspection */

/**
 * Helper script that prints expected database schema to standard output
 */
namespace Tests\Support\Tools;
use CoreUpdater\ObjectModelSchemaBuilder;

// this script is intended for CLI mode only
if (php_sapi_name() !== 'cli') {
    exit;
}

require_once __DIR__ . '/../../../config/settings.inc.php';
require_once __DIR__ . '/../../../config/config.inc.php';
require_once _PS_MODULE_DIR_ . '/coreupdater/classes/schema/autoload.php';


$schemaBuilder = new ObjectModelSchemaBuilder();
$schema = $schemaBuilder->getSchema();
echo str_replace('`' . _DB_PREFIX_, '`PREFIX_', $schema->getDDLStatement());
