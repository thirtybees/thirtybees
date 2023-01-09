<?php
/**
 * Copyright (C) 2022-2022 thirty bees
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
 * @copyright 2022-2022thirty bees
 * @license   Open Software License (OSL 3.0)
 */

namespace Tests\Support;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

class TestClassIndex
{
    const SOURCES = [
        'Core' => false,
        'Adapter' => false,
        'classes' => true,
        'controllers' => true,
    ];

    const NOT_OVERRIDABLE = [
        'PrestaShopAutoload',
    ];

    const ALIASES = [
        'Collection' => 'PrestaShopCollection',
        'Autoload' => 'PrestaShopAutoload',
        'Backup' => 'PrestaShopBackup',
        'Logger' => 'PrestaShopLogger',
    ];

    /**
     * @var array
     */
    private array $coreClasses;

    /**
     * @var array
     */
    private array $overrideClasses;

    /**
     * @var string
     */
    private $coreDir;

    /**
     * @var string
     */
    private $overrideDir;

    /**
     * @param string|null $coreDir
     * @param string|null $overrideDir
     */
    public  function __construct($coreDir = null, $overrideDir = null)
    {
        $this->coreDir = $coreDir ?? realpath(__DIR__ . '/../../');
        $this->overrideDir = $overrideDir ?? realpath(__DIR__ . '/override/');

        $this->coreClasses = $this->findClasses($this->coreDir, array_keys(static::SOURCES));
        $this->overrideClasses = $this->findClasses($this->overrideDir, array_keys(array_filter(static::SOURCES)));
    }

    /**
     * @param string $rootDir
     *
     * @return array
     */
    protected function findClasses($rootDir, $dirs)
    {
        $classes = [];
        foreach ($dirs as $dir) {
            $directory = new RecursiveDirectoryIterator($rootDir . '/' . $dir);
            $iterator = new RecursiveIteratorIterator($directory);
            foreach ($iterator as $path) {
                if (preg_match("/\.php$/", $path) && is_file($path)) {
                    $filename = basename($path);
                    if ($filename !== 'index.php') {
                        $relative = str_replace($rootDir, "", $path);
                        $content = file($path);
                        $classLine = $this->getClassLine($content);
                        if (! $classLine) {
                            throw new RuntimeException("Failed to resolve class line for $path");
                        }
                        $namespace = $this->getNameSpace($content);
                        $isInterface = $this->isInterface($classLine);
                        $className = $this->getClassName($classLine);
                        $key = ($namespace ? ($namespace . '\\') : '') . $className;
                        $classes[$key] = [
                            'source' => $dir,
                            'classname' => $className,
                            'namespace' => $namespace,
                            'type' => $isInterface ? 'interface' : 'class',
                            'path' => $path,
                            'file' => $relative,
                        ];
                    }
                }
            }
        }
        return $classes;
    }

    /**
     * @param string $classLine
     *
     * @return bool
     */
    protected function isInterface(string $classLine)
    {
        if (preg_match("/^\s*interface\s+/", $classLine)) {
            return true;
        }
        return false;
    }

    /**
     * @param array $content
     *
     * @return string;
     */
    protected function getNameSpace(array $content)
    {
        foreach ($content as $line) {
            if (preg_match("/^\s*namespace\s+\\\*([a-zA-Z0-9_\\\]+)/", $line, $matches)) {
                return trim($matches[1], '\\');
            }
        }
        return '';
    }

    /**
     * @param array $content
     *
     * @return string|null
     */
    protected function getClassLine(array $content)
    {
        $classLine = null;
        foreach ($content as $line) {
            if ($classLine) {
                $classLine .= ' ' . trim($line);
            } elseif (preg_match("/^\s*(abstract)*\s*(class|interface)\s+\\\*([a-zA-Z0-9_\\\]+)/", $line)) {
                $classLine = trim($line);
            }

            if ($classLine) {
                $pos = strpos($classLine, '{');
                if ($pos !== false) {
                    return substr($classLine, 0, $pos);
                }
            }
        }
        return null;
    }

    /**
     * @param string $classLine
     *
     * @return string
     */
    protected function getClassName(string $classLine)
    {
        if (preg_match("/^\s*(abstract)*\s*class\s+\\\*([a-zA-Z0-9_\\\]+)/", $classLine, $matches)) {
            return trim($matches[2]);
        }
        if (preg_match("/^\s*interface\s+\\\*([a-zA-Z0-9_\\\]+)/", $classLine, $matches)) {
            return trim($matches[1]);
        }
        throw new RuntimeException("Failed to resolve class name from $classLine");
    }


    /**
     * @return string[]
     */
    public function getMissingOverrides()
    {
        $missing = [];
        foreach ($this->coreClasses as $key => $coreClass) {
            $overrideKey = preg_replace("/Core$/", "", $key);
            if (! isset($this->overrideClasses[$overrideKey]) &&
                $coreClass['type'] === 'class'  &&
                static::SOURCES[$coreClass['source']] &&
                ! in_array($key, static::NOT_OVERRIDABLE)
            ) {
                $missing[] = $key;
            }
        }
        return $missing;
    }

    /**
     * @return string[]
     */
    public function getExtraOverrides()
    {
        $extra = [];
        foreach ($this->overrideClasses as $key => $overrideClass) {
            $coreKey = $key . 'Core';
            if (! isset($this->coreClasses[$coreKey]) &&
                ! isset(static::ALIASES[$key])
            ) {
                $extra[] = $key;
            }
        }
        return $extra;
    }

    /**
     * Implements autoload functionality for unit tests
     *
     * @param string $className
     */
    public function autoload($className)
    {
        if (isset($this->overrideClasses[$className])) {
            require_once($this->overrideClasses[$className]['path']);
        }
        if (isset($this->coreClasses[$className])) {
            require_once($this->coreClasses[$className]['path']);
        }
    }

}