<?php

namespace Tests\Integration;

use Codeception\Test\Unit;
use ObjectModel;
use PrestaShopException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionException;
use Tests\Support\UnitTester;

class ObjectModelTest extends Unit
{
    /**
     * @var ObjectModel[]
     */
    private static $models = null;

    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     * This tests verifies ObjectModel class contains properties for all fields in $definition
     *
     * @dataProvider getObjectModels
     *
     * @param string $className object model class name
     * @param array $definition object model definition
     * @param ReflectionClass $reflection reflection class $className
     *
     * @throws ReflectionException
     * @throws PrestaShopException
     */
    public function testModelProperties($className, $definition, $reflection)
    {
        if (isset($definition['fields'])) {
            foreach ($definition['fields'] as $property => $propDef) {
                self::assertArrayHasKey('type', $propDef, "Definition for $className::\$$property should have 'type'");
                self::assertTrue($reflection->hasProperty($property), "Class $className is missing property \$$property");
                $prop = $reflection->getProperty($property);
                self::assertTrue(!$prop->isStatic(), "Property $className::\$$property is static");
                $visibility = ($prop->isPublic() ? 'public' : ($prop->isPrivate() ? 'private' : 'protected'));
                self::assertEquals('public', $visibility,  "Incorrect property visibility: $visibility $className::\$$property");
                $phpdoc = $prop->getDocComment();
                self::assertTrue($phpdoc !== false, "Property $className::\$$property is missing PHPDoc");
                if (preg_match("#@var\s+([a-zA-Z0-9_\[\]|]+)#", $phpdoc, $matches)) {
                    $types = array_map('trim', explode('|', $matches[1]));
                    $expectedTypes = static::getExpectedTypes($propDef);
                    foreach ($expectedTypes as $expectedType) {
                        if (! in_array($expectedType, $types)) {
                            self::fail("Property $className::\$$property should have type '" . implode('|', $expectedTypes) . "' but has '" . implode('|', $types) . "'");
                        }
                    }
                    foreach ($types as $type) {
                        if (! in_array($type, $expectedTypes) && $type !== 'null') {
                            self::fail("Property $className::\$$property should have type '" . implode('|', $expectedTypes) . "' but has '" . implode('|', $types) . "'");
                        }
                    }
                } else {
                    self::fail("Property $className::\$$property is missing @var declaration");
                }
            }
        }
    }

    /**
     * This tests verifies ObjectModel class contains properties for all fields in $definition
     *
     * @dataProvider getObjectModels
     * @param string $className object model class name
     * @param array $definition object model definition
     */
    public function testFormatFields($className, $definition)
    {
        if (isset($definition['fields'])) {
            $shopFields = [];
            $langFields = [];
            $common = [];
            foreach ($definition['fields'] as $property => $def) {
                if (isset($def['shop']) && $def['shop']) {
                    $shopFields[] = $property;
                    if (! isset($def['shopOnly']) || !$def['shopOnly']) {
                        $common[$property] = $property;
                    }
                }
                if (isset($def['lang']) && $def['lang']) {
                    $langFields[] = $property;
                } else {
                    if (! isset($def['shopOnly']) || !$def['shopOnly']) {
                        $common[$property] = $property;
                    }
                }
            }

            // check shop fields
            static::assertEquals(
                $shopFields,
                array_keys($this->tester->invokeMethod(new $className(), 'formatFields', [ObjectModel::FORMAT_SHOP]))
            );

            // check lang fields
            static::assertEquals(
                $langFields,
                array_keys($this->tester->invokeMethod(new $className(), 'formatFields', [ObjectModel::FORMAT_LANG, 1]))
            );

            // check common fields
            static::assertEquals(
                array_keys($common),
                array_keys($this->tester->invokeMethod(new $className(), 'formatFields', [ObjectModel::FORMAT_COMMON]))
            );
        }
    }

    /**
     * Method returns array of all ObjectModel subclasses in the system
     *
     * @return ObjectModel[]
     * @throws ReflectionException
     * @throws PrestaShopException
     */
    public function getObjectModels()
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

    /**
     * @param array $fieldDef
     *
     * @return string[]
     * @throws PrestaShopException
     */
    private static function getExpectedTypes(array $fieldDef)
    {
        $type = (int)$fieldDef['type'];
        $basicType = static::getExpectedType($type);
        $expectedTypes = [ $basicType ];

        if (isset($fieldDef['lang']) && $fieldDef['lang']) {
            $expectedTypes[] = $basicType.'[]';
        }
        return $expectedTypes;
    }

    /**
     * @param int $type
     *
     * @return string
     * @throws PrestaShopException
     */
    private static function getExpectedType(int $type)
    {
        switch ($type) {
            case ObjectModel::TYPE_INT:
                return 'int';
            case ObjectModel::TYPE_BOOL:
                return 'bool';
            case ObjectModel::TYPE_STRING:
                return 'string';
            case ObjectModel::TYPE_FLOAT:
                return 'float';
            case ObjectModel::TYPE_DATE:
                return 'string';
            case ObjectModel::TYPE_HTML:
                return 'string';
            case ObjectModel::TYPE_NOTHING:
                return 'mixed';
            case ObjectModel::TYPE_SQL:
                return 'string';
            case ObjectModel::TYPE_PRICE:
                return 'float';
            default:
                throw new PrestaShopException("Uknown type $type");
        }
    }
}
