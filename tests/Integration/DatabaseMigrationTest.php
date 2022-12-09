<?php
/**
 * Copyright (C) 2019 thirty bees
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
 * @copyright 2019 thirty bees
 * @license   Open Software License (OSL 3.0)
 */

namespace Tests\Integration;

require_once _PS_MODULE_DIR_ . '/coreupdater/classes/schema/autoload.php';

use Codeception\Test\Unit;
use CoreUpdater\InformationSchemaBuilder;
use CoreUpdater\DatabaseSchemaComparator;
use Db;
use PrestaShopDatabaseException;
use PrestaShopException;

/**
 * Class DatabaseMigrationTest
 *
 * This tests verifies that database migration operations implemented by SchemaDifference subclasses
 * works as expected
 *
 * Test migration of single database table from one state to another. It uses following process
 *
 * 1) creates test database table for target state
 * 2) look into information schema and collects all information about table
 * 3) drop table, and recreates it for initial state
 * 4) look into information schema and collects all information about table
 * 5) find differences between collected information, and resolve set of database operation needed
 *    to migrate table from initial to target state
 * 6) execute these migration operation
 * 7) compare that actual database schema looks the same as expected target database schema
 */
class DatabaseMigrationTest extends Unit
{
    const TEST_TABLE = 'migration_test_table';

    /**
     * Prepares all test cases
     *
     * @return array list of test cases
     */
    public function getTestCases()
    {
        $table = _DB_PREFIX_ . static::TEST_TABLE;
        $t1 = (
            "CREATE TABLE `$table` (\n" .
            "  `id_migration_test_table` int(11) unsigned NOT NULL AUTO_INCREMENT,\n" .
            "  `id_product` int(11) NOT NULL,\n".
            "  PRIMARY KEY (`id_migration_test_table`)\n".
            ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $t2 = (
            "CREATE TABLE `$table` (\n" .
            "  `id_migration_test_table` int(11) unsigned NOT NULL AUTO_INCREMENT,\n" .
            "  PRIMARY KEY (`id_migration_test_table`)\n".
            ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $t3 = (
            "CREATE TABLE `$table` (\n" .
            "  `id_migration_test_table` int(11) unsigned NOT NULL AUTO_INCREMENT,\n" .
            "  `id_product` int(11) NOT NULL DEFAULT '1',\n".
            "  PRIMARY KEY (`id_migration_test_table`)\n".
            ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $t4 = (
            "CREATE TABLE `$table` (\n" .
            "  `id_migration_test_table` int(11) unsigned NOT NULL AUTO_INCREMENT,\n" .
            "  `id_product` int(11),\n".
            "  PRIMARY KEY (`id_migration_test_table`)\n".
            ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $t5 = (
            "CREATE TABLE `$table` (\n" .
            "  `id_migration_test_table` int(11) unsigned NOT NULL AUTO_INCREMENT,\n" .
            "  `id_product` int(11) DEFAULT NULL,\n".
            "  PRIMARY KEY (`id_migration_test_table`)\n".
            ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $t6 = (
            "CREATE TABLE `$table` (\n" .
            "  `id_migration_test_table` int(11) unsigned NOT NULL AUTO_INCREMENT,\n" .
            "  `ts` timestamp,\n".
            "  PRIMARY KEY (`id_migration_test_table`)\n".
            ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $t7 = (
            "CREATE TABLE `$table` (\n" .
            "  `id_migration_test_table` int(11) unsigned NOT NULL AUTO_INCREMENT,\n" .
            "  `ts` timestamp DEFAULT CURRENT_TIMESTAMP,\n".
            "  PRIMARY KEY (`id_migration_test_table`)\n".
            ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $t8 = (
            "CREATE TABLE `$table` (\n" .
            "  `id_migration_test_table` int(11) unsigned NOT NULL AUTO_INCREMENT,\n" .
            "  `ts` datetime NOT NULL,\n".
            "  PRIMARY KEY (`id_migration_test_table`)\n".
            ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $t9 = (
            "CREATE TABLE `$table` (\n" .
            "  `id_migration_test_table` int(11) unsigned NOT NULL AUTO_INCREMENT,\n" .
            "  `ts` int(11) NOT NULL,\n".
            "  PRIMARY KEY (`id_migration_test_table`)\n".
            ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $t10 = (
            "CREATE TABLE `$table` (\n" .
            "  `id_migration_test_table` int(11) unsigned NOT NULL,\n" .
            "  `ts` int(11) NOT NULL,\n".
            "  PRIMARY KEY (`id_migration_test_table`)\n".
            ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $t11 = (
            "CREATE TABLE `$table` (\n" .
            "  `id_migration_test_table` int(11) unsigned NOT NULL,\n" .
            "  `ts` int(11),\n".
            "  PRIMARY KEY (`id_migration_test_table`)\n".
            ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $t12 = (
            "CREATE TABLE `$table` (\n" .
            "  `id_migration_test_table` int(11) unsigned NOT NULL,\n" .
            "  `ts` int(11)\n".
            ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $t13 = (
            "CREATE TABLE `$table` (\n" .
            "  `id_migration_test_table` int(11) unsigned NOT NULL,\n" .
            "  `ts` int(11),\n".
            "  PRIMARY KEY (`id_migration_test_table`),\n".
            "  UNIQUE KEY (`ts`)\n".
            ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $t14 = (
            "CREATE TABLE `$table` (\n" .
            "  `id_migration_test_table` int(11) unsigned NOT NULL,\n" .
            "  `ts` int(11),\n".
            "  PRIMARY KEY (`id_migration_test_table`),\n".
            "  KEY (`ts`,`id_migration_test_table`)\n".
            ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $t15 = (
            "CREATE TABLE `$table` (\n" .
            "  `id_migration_test_table` int(11) unsigned NOT NULL,\n" .
            "  `ts` int(11),\n".
            "  PRIMARY KEY (`ts`)\n".
            ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $t16 = (
            "CREATE TABLE `$table` (\n" .
            "  `id_migration_test_table` int(11) unsigned NOT NULL,\n" .
            "  `ts` int(11),\n".
            "  PRIMARY KEY (`id_migration_test_table`),\n".
            "  UNIQUE KEY (`ts`,`id_migration_test_table`)\n".
            ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $t17 = (
            "CREATE TABLE `$table` (\n" .
            "  `id_migration_test_table` int(11) unsigned NOT NULL,\n" .
            "  `ts` int(11),\n".
            "  `name` varchar(100),\n".
            "  PRIMARY KEY (`id_migration_test_table`),\n".
            "  UNIQUE KEY (`name`(5))\n".
            ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $t18 = (
            "CREATE TABLE `$table` (\n" .
            "  `id_migration_test_table` int(11) unsigned NOT NULL,\n" .
            "  `ts` int(11),\n".
            "  `name` varchar(100) CHARSET latin1 COLLATE latin1_swedish_ci,\n".
            "  PRIMARY KEY (`id_migration_test_table`),\n".
            "  UNIQUE KEY (`name`(5))\n".
            ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $t19 = (
            "CREATE TABLE `$table` (\n" .
            "  `id_migration_test_table` int(11) unsigned NOT NULL,\n" .
            "  `ts` int(11),\n".
            "  `name` varchar(100),\n".
            "  `lastname` varchar(100) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci,\n".
            "  PRIMARY KEY (`id_migration_test_table`),\n".
            "  UNIQUE KEY (`name`(5))\n".
            ") ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci"
        );

        $t20 = (
            "CREATE TABLE `$table` (\n" .
            "  `id_migration_test_table` int(11) unsigned NOT NULL,\n" .
            "  `ts` int(11),\n".
            "  `name` varchar(100),\n".
            "  `lastname` varchar(100),\n".
            "  PRIMARY KEY (`id_migration_test_table`),\n".
            "  UNIQUE KEY (`name`(5))\n".
            ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $t21 = (
            "CREATE TABLE `$table` (\n" .
            "  `id_migration_test_table` int(11) unsigned NOT NULL,\n" .
            "  `ts` int(11),\n".
            "  `name` varchar(100),\n".
            "  `lastname` varchar(100),\n".
            "  PRIMARY KEY (`id_migration_test_table`),\n".
            "  UNIQUE KEY (`name`(5))\n".
            ") ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $t22 = (
            "CREATE TABLE `$table` (\n" .
            "  `id_migration_test_table` int(11) unsigned NOT NULL,\n" .
            "  `lastname` varchar(100),\n".
            "  `name` varchar(100),\n".
            "  `ts` int(11),\n".
            "  PRIMARY KEY (`id_migration_test_table`),\n".
            "  UNIQUE KEY (`ts`)\n".
            ") ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $t23 = (
            "CREATE TABLE `$table` (\n" .
            "  `id_migration_test_table` int(11) unsigned NOT NULL,\n" .
            "  `lastname` varchar(100),\n".
            "  `name` varchar(100),\n".
            "  `firstname` varchar(100) NOT NULL,\n".
            "  `ts` int(11),\n".
            "  PRIMARY KEY (`id_migration_test_table`),\n".
            "  UNIQUE KEY (`ts`)\n".
            ") ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        return [
            'drop table' => [$t1, null],
            'create table' => [null, $t1],
            'drop column' => [$t1, $t2],
            'add column' => [$t2, $t1],
            'add column to the middle' => [$t22, $t23],
            'add default value' => [$t1, $t3],
            'drop default value' => [$t3, $t1],
            'set default NULL' => [$t4, $t5],
            'change column data type 1' => [$t6, $t8],
            'change column data type 2' => [$t6, $t9],
            'drop AUTO_INCREMENT' => [$t9, $t10],
            'add AUTO_INCREMENT' => [$t10, $t9],
            'set column nullable' => [$t10, $t11],
            'set column not null' => [$t11, $t10],
            'drop primary key' => [$t11, $t12],
            'add primary key' => [$t12, $t11],
            'drop unique key' => [$t13, $t11],
            'add unique key' => [$t11, $t13],
            'drop key' => [$t14, $t11],
            'add key' => [$t11, $t14],
            'modify primary key' => [$t11, $t15],
            'modify unique key' => [$t13, $t16],
            'change column character set' => [$t17, $t18],
            'change table character set' => [$t19, $t20],
            'change database engine' => [$t20, $t21],
            'change column order' => [$t21, $t22],
            'set default CURRENT_TIMESTAMP' => [$t6, $t7],
            'complex 1' => [$t1, $t22],
            'complex 2' => [$t23, $t1],
        ];
    }

    /**
     * Actual test to verify migration process
     *
     * @param string $sourceTable create statement for table in initial state
     * @param string $targetTable create statement for table in end state
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @dataProvider getTestCases
     */
    public function testMigration($sourceTable, $targetTable)
    {
        try {
            $connection = Db::getInstance();
            $table = _DB_PREFIX_ . static::TEST_TABLE;
            $builder = new InformationSchemaBuilder($connection, null, [$table]);

            // figure out what target schema should look like
            $this->createTestTable($targetTable);
            $targetSchema = $builder->getSchema(true);

            // revert to source state
            $this->createTestTable($sourceTable);
            $sourceSchema = $builder->getSchema(true);

            // perform migration
            $comparator = new DatabaseSchemaComparator();
            $differences = $comparator->getDifferences($sourceSchema, $targetSchema);
            foreach ($differences as $difference) {
                if (!$difference->applyFix($connection)) {
                    self::fail("Failed to apply fix: " . $difference->getUniqueId() . ": " . $connection->getMsgError());
                }
            }

            // verify that there are no differences
            $resultSchema = $builder->getSchema(true);
            $differences = $comparator->getDifferences($resultSchema, $targetSchema);
            foreach ($differences as $difference) {
                self::fail($difference->describe());
            }
        } finally {
            $this->cleanUp();
        }
    }

    /**
     * Executed after each test. Cleans database - remove test table
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function cleanUp()
    {
        $this->dropTestTable();
    }

    /**
     * Helper method to drop test table
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function dropTestTable()
    {
        if (! Db::getInstance()->execute("DROP TABLE IF EXISTS `" . _DB_PREFIX_ . static::TEST_TABLE . "`")) {
            throw new PrestaShopDatabaseException("Failed to drop test table");
        }
    }

    /**
     * Helper method to create test table
     *
     * @param string $createStatement
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function createTestTable($createStatement)
    {
        $this->dropTestTable();
        if ($createStatement) {
            $conn = Db::getInstance();
            if (!$conn->execute($createStatement)) {
                throw new PrestaShopDatabaseException("Failed to create database table: \n" . $createStatement . "\n\nReason: " . $conn->getMsgError());
            }
        }
    }

}
