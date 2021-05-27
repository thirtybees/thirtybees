<?php

namespace Tests\Integration;

use Codeception\Test\Unit;
use Exception;
use Tests\Support\UnitTester;
use Thirtybees\Core\Package\PackageExtractor;
use Throwable;
use Tools;

/**
 * Copyright (C) 2023 thirty bees
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
 * @copyright 2023 thirty bees
 * @license   Open Software License (OSL 3.0)
 */

class PackageExtractorTest extends Unit
{

    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     * This test tries to extracts non-existing zip. That should fail, of course
     *
     * @throws Exception
     */
    public function testExtractNonExistingSource()
    {
        $this->execute(function(PackageExtractor $extractor) {
            $res = $extractor->extractPackage(_PS_ROOT_DIR_ . '/non-existing.zip', 'whatever');
            $this->assertFalse($res, "Package should not have been extracted");
        });
    }

    /**
     * This test tries to extracts valid zip module saved on filesystem
     *
     * @throws Exception
     */
    public function testExtractLocalModule()
    {
        $this->execute(function(PackageExtractor $extractor) {
            $this->validateNotInstalled('mod1');
            $res = $extractor->extractPackage($this->getLocalSource('mod1'), 'mod1');
            $this->assertTrue($res, "Failed to extract package:" . $this->getErrors($extractor));
            $this->validateModuleStructure('mod1');
        });
    }

    /**
     * This test tries to extract valid .tar.gz module saved on filesystem
     *
     * @throws Exception
     */
    public function testExtractTarGzModule()
    {
        $this->execute(function(PackageExtractor $extractor) {
            $this->validateNotInstalled('mod6');
            $res = $extractor->extractPackage($this->getLocalSource('mod6', '.tar.gz'), 'mod6');
            $this->assertTrue($res, "Failed to extract package:" . $this->getErrors($extractor));
            $this->validateModuleStructure('mod6');
        });
    }

    /**
     * This test tries to extract invalid module - no top-level directory found
     *
     * @throws Exception
     */
    public function testNoValidDirectoryInPackage()
    {
        $this->execute(function(PackageExtractor $extractor) {
            $this->validateNotInstalled('mod1');
            $res = $extractor->extractPackage($this->getLocalSource('mod1'), 'mod2');
            $this->assertFalse($res, "Package should not have been extracted");
        });
    }

    /**
     * This test tries to extract module from archive with multiple top-directories
     *
     * @throws Exception
     */
    public function testExtractFromMultiPackage()
    {
        $this->execute(function(PackageExtractor $extractor) {
            $this->validateNotInstalled('mod2');
            $res = $extractor->extractPackage($this->getLocalSource('mod2'), 'mod2');
            $this->assertTrue($res, "Failed to extract package:" . $this->getErrors($extractor));
            $this->validateModuleStructure('mod2');
        });
    }

    /**
     * This test tries to extract module from archive with one directory and extra file
     *
     * @throws Exception
     */
    public function testExtractFromPackageWithExtraFile()
    {
        $this->execute(function(PackageExtractor $extractor) {
            $this->validateNotInstalled('mod7');
            $res = $extractor->extractPackage($this->getLocalSource('mod7'), 'mod7');
            $this->assertTrue($res, "Failed to extract package:" . $this->getErrors($extractor));
            $this->validateModuleStructure('mod7');
            $this->assertFalse(file_exists($this->getModulesDir() . '/extra.php'), "Extra file detected");
        });
    }

    /**
     * Test whether ignored files/directories will not be copied
     *
     * @throws Exception
     */
    public function testIgnoreGitDirectory()
    {
        $this->execute(function(PackageExtractor $extractor) {
            $this->validateNotInstalled('mod3');
            $res = $extractor->extractPackage($this->getLocalSource('mod3'), 'mod3');
            $this->assertTrue($res, "Failed to extract package:" . $this->getErrors($extractor));
            $this->validateModuleStructure('mod3', [], [
                'mod3/.git/HEAD',
                'mod3/vendor/vendor_a/.git/HEAD'
            ]);
        });
    }

    /**
     * Test whether ignored files/directories will not be copied
     *
     * @throws Exception
     */
    public function testEmptyPackage()
    {
        $this->execute(function(PackageExtractor $extractor) {
            $this->validateNotInstalled('mod4');
            $res = $extractor->extractPackage($this->getLocalSource('mod4'), 'mod4');
            $this->assertFalse($res, "Package should not have been extracted");
        });
    }

    /**
     * Test that external validation works
     *
     * @throws Exception
     */
    public function testExternalValidator()
    {
        $this->execute(function(PackageExtractor $extractor) {
            $extractor->setPackageValidator(function ($lists) {
                $errors = [];
                if (!isset($lists['mod5/mod5.php'])) {
                    $errors[] = 'Package does not contain module primary file';
                }
                return $errors;
            });
            $this->validateNotInstalled('mod5');
            $res = $extractor->extractPackage($this->getLocalSource('mod5'), 'mod5');
            $this->assertFalse($res, "Package should not have been extracted");
        });
    }

    /**
     * Test whether ignored files/directories will not be copied
     *
     * @throws Exception
     */
    public function testMergeMode()
    {
        $this->execute(function(PackageExtractor $extractor) {
            $this->validateNotInstalled('mod3');
            $this->assertTrue($extractor->extractPackage($this->getLocalSource('mod3'), 'mod3'));
            $this->assertTrue($extractor->extractPackage($this->getLocalSource('mod3-v2'), 'mod3'));
            $this->validateModuleStructure('mod3', [
                'mod3/mod3.php',
                'mod3/vendor/vendor_a/file.php',
                'mod3/vendor/vendor_b/readme.txt'
            ]);
            $content = file_get_contents($this->getModulesDir() . '/mod3/mod3.php');
            $this->assertTrue(strpos($content, 'SEARCH_PLACEHOLDER') !== false, "mod3/mod3.php should contain content of mod3-v2 file");
        });
    }

    /**
     * Test whether ignored files/directories will not be copied
     *
     * @throws Exception
     */
    public function testMergeModeOtherDirection()
    {
        $this->execute(function(PackageExtractor $extractor) {
            $this->validateNotInstalled('mod3');
            $this->assertTrue($extractor->extractPackage($this->getLocalSource('mod3-v2'), 'mod3'));
            $this->assertTrue($extractor->extractPackage($this->getLocalSource('mod3'), 'mod3'));
            $this->validateModuleStructure('mod3', [
                'mod3/mod3.php',
                'mod3/vendor/vendor_a/file.php',
                'mod3/vendor/vendor_b/readme.txt'
            ]);
            $content = file_get_contents($this->getModulesDir() . '/mod3/mod3.php');
            $this->assertTrue(strpos($content, 'SEARCH_PLACEHOLDER') === false, "mod3/mod3.php should NOT contain content of mod3-v2 file");
        });
    }

    /**
     * Test whether ignored files/directories will not be copied
     *
     * @throws Exception
     */
    public function testReplaceMode()
    {
        $this->execute(function(PackageExtractor $extractor) {
            $extractor->setMode(PackageExtractor::MODE_REPLACE);
            $this->validateNotInstalled('mod3');
            $this->assertTrue($extractor->extractPackage($this->getLocalSource('mod3'), 'mod3'));
            $this->assertTrue($extractor->extractPackage($this->getLocalSource('mod3-v2'), 'mod3'));
            $this->validateModuleStructure('mod3',
                [
                    // expected files
                    'mod3/mod3.php',
                    'mod3/vendor/vendor_b/readme.txt',
                    'mod3/vendor/vendor_a/autoload.php'
                ],
                [
                    // not expected files
                    'mod3/vendor/vendor_a/file.php',
                ]
            );
        });
    }

    /**
     * Test whether ignored files/directories will not be copied
     *
     * @throws Exception
     */
    public function testZipGetPackageTopLevelDirectories()
    {
        $this->execute(function(PackageExtractor $extractor) {
            $this->assertSame(['mod1', 'mod2'], $extractor->getPackageTopLevelDirectories($this->getLocalSource('mod2')));
            $this->assertSame(['mod3'], $extractor->getPackageTopLevelDirectories($this->getLocalSource('mod3')));
            $this->assertSame(['a', 'b', 'c', 'd'], $extractor->getPackageTopLevelDirectories($this->getLocalSource('multiple')));
        });
    }

    /**
     * Test whether ignored files/directories will not be copied
     *
     * @throws Exception
     */
    public function testTarGzGetPackageTopLevelDirectories()
    {
        $this->execute(function(PackageExtractor $extractor) {
            $this->assertSame(['mod6'], $extractor->getPackageTopLevelDirectories($this->getLocalSource('mod6', '.tar.gz')));
            $this->assertSame(['a', 'b', 'c', 'd'], $extractor->getPackageTopLevelDirectories($this->getLocalSource('multiple', '.tgz')));
        });
    }

    /**
     * Test whether ignored files/directories will not be copied
     *
     * @throws Exception
     */
    public function testReplaceModeOtherDirection()
    {
        $this->execute(function(PackageExtractor $extractor) {
            $extractor->setMode(PackageExtractor::MODE_REPLACE);
            $this->validateNotInstalled('mod3');
            $this->assertTrue($extractor->extractPackage($this->getLocalSource('mod3-v2'), 'mod3'));
            $this->assertTrue($extractor->extractPackage($this->getLocalSource('mod3'), 'mod3'));
            $this->validateModuleStructure('mod3',
                [
                    // expected files
                    'mod3/mod3.php',
                    'mod3/vendor/vendor_a/autoload.php',
                    'mod3/vendor/vendor_a/file.php',
                ],
                [
                    // not expected files
                    'mod3/vendor/vendor_b/readme.txt'
                ]
            );
        });
    }

    /**
     * Test install remote package
     *
     * @throws Exception
     */
    public function testRemotePackage()
    {
        $this->execute(function(PackageExtractor $extractor) {
            $url = 'https://github.com/thirtybees/blockmyaccount/releases/download/2.1.1/blockmyaccount-v2.1.1.zip';
            $module = 'blockmyaccount';
            $this->validateNotInstalled($module);
            $res = $extractor->extractPackage($url, $module);
            $this->assertTrue($res, "Failed to extract package:" . $this->getErrors($extractor));
        });
    }

    /**
     * @param callable $callable
     * @return void
     * @throws Exception
     */
    private function execute($callable)
    {
        $extractor = new PackageExtractor($this->createModulesDir());
        try {
            $callable($extractor);
        } finally {
            $this->removeModulesDir();
        }
    }

    /**
     * @param $moduleName
     */
    private function validateNotInstalled($moduleName)
    {
        $dir = $this->getModulesDir() . '/' . $moduleName;
        $this->assertDirectoryDoesNotExist($dir);
    }

    /**
     * @param $moduleName
     * @param array $expectedFiles
     * @param array $ignoredFiles
     */
    private function validateModuleStructure($moduleName, $expectedFiles = [], $ignoredFiles = [])
    {
        $file = $moduleName . '/' . $moduleName . '.php';
        if (! in_array($file, $expectedFiles)) {
            $expectedFiles[] = $file;
        }

        // check that directory was created
        $this->assertDirectoryExists($this->getModulesDir() . '/' . $moduleName);

        // check that every expected files were coppied
        foreach ($expectedFiles as $file) {
            $this->assertFileExists($this->getModulesDir() . '/' . ltrim($file, '/'));
        }

        // check that ignored files were not copied
        foreach ($ignoredFiles as $file) {
            $this->assertFileNotExists($this->getModulesDir() . '/' . ltrim($file, '/'));
        }

    }

    /**
     * @return string
     */
    private function getModulesDir()
    {
        return rtrim(_PS_ROOT_DIR_, '/') . '/tests/_output/modules';
    }

    /**
     * @param $name
     * @param string $suffix
     * @return string
     */
    private function getLocalSource($name, $suffix='.zip')
    {
        $filename = rtrim(_PS_ROOT_DIR_, '/') . '/tests/_data/modules/' . $name . $suffix;
        if (! is_file($filename)) {
            $this->fail("Source file not found: $filename");
        }
        return $filename;
    }

    /**
     *
     */
    private function removeModulesDir()
    {
        $dir = $this->getModulesDir();
        if (is_dir($dir)) {
            Tools::deleteDirectory($dir, true);
        }
    }

    /**
     * @return string
     * @throws Exception
     */
    private function createModulesDir()
    {
        $this->removeModulesDir();
        $dir = $this->getModulesDir();
        if (! is_dir($dir)) {
            mkdir($dir);
            if (!is_dir($dir)) {
                throw new Exception("Failed to create directory $dir");
            }
        }
        return $dir;
    }

    /**
     *
     */
    private function getErrors(PackageExtractor $extractor)
    {
        $separator = "\n  - ";
        return $separator . implode($separator, array_map(function($error) {
            $ret = $error['message'];
            if (isset($error['exception']) && $error['exception']) {
                /** @var Throwable $e */
                $e = $error['exception'];
                $ret .= ' Caused by ' . $e->getTraceAsString();
            }
            return $ret;
        }, $extractor->getErrors()));
    }
}
