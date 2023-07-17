<?php

namespace Tests\Support\Utils;

use ObjectModel;
use PrestaShopException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionException;

class ObjectModelUtils
{
    /**
     * @var ObjectModel[]
     */
    private static $models = null;

    /**
     * Method returns array of all ObjectModel subclasses in the system
     *
     * @return ObjectModel[]
     *
     * @throws ReflectionException
     * @throws PrestaShopException
     */
    public static function getObjectModels()
    {
        if (is_null(static::$models)) {
            $directory = new RecursiveDirectoryIterator(_PS_ROOT_DIR_ . DIRECTORY_SEPARATOR . 'classes');
            $iterator = new RecursiveIteratorIterator($directory);
            foreach ($iterator as $path) {
                $file = basename($path);
                if (preg_match("/^.+\.php$/i", $file)) {
                    $className = str_replace(".php", "", $file);
                    if ($className !== "index") {
                        if (!class_exists($className)) {
                            require_once($path);
                        }
                        if (class_exists($className)) {
                            $reflection = new ReflectionClass($className);
                            if ($reflection->isSubclassOf('ObjectModelCore') && !$reflection->isAbstract()) {
                                $definition = ObjectModel::getDefinition($className);
                                static::$models[$className] = [$className, $definition, $reflection];
                            }
                        }
                    }
                }
            }
            ksort(static::$models);
        }
        return static::$models;
    }
}