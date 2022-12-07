<?php


class ObjectModelTest extends \Codeception\Test\Unit
{
    /**
     * @var ObjectModel[]
     */
    private static $models = null;

    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * This tests verifies ObjectModel class contains properties for all fields in $definition
     *
     * @dataProvider getObjectModels
     * @param string $className object model class name
     * @param array $definition object model definition
     * @param ReflectionClass $reflection reflection class $className
     * @throws ReflectionException
     */
    public function testModelProperties($className, $definition, $reflection)
    {
        if (isset($definition['fields'])) {
            foreach ($definition['fields'] as $property => $_) {
                self::assertTrue($reflection->hasProperty($property), "Class $className is missing property \$$property");
                $prop = $reflection->getProperty($property);
                self::assertTrue(!$prop->isStatic(), "Property $className::\${$property} is static");
                $visibility = ($prop->isPublic() ? 'public' : ($prop->isPrivate() ? 'private' : 'protected'));
                self::assertEquals('public', $visibility,  "Incorrect property visibility: $visibility $className::\${$property}");
            }
        }
    }

    /**
     * This tests verifies ObjectModel class contains properties for all fields in $definition
     *
     * @dataProvider getObjectModels
     * @param string $className object model class name
     * @param string $definition object model definition
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
        }
        return static::$models;
    }
}
