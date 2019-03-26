<?php


class PrestaShopCollectionTest extends \Codeception\Test\Unit
{

    /**
     * This tests verifies that all defined associations can be queried
     *
     * @dataProvider getAssociations
     * @throws PrestaShopException
     */
    public function testAssociations($class, $association, $assocDef)
    {
        $col = new PrestaShopCollection($class);
        $targetClass = (isset($assocDef['object'])) ? $assocDef['object'] : Tools::toCamelCase($association, true);
        $targetDefinition = ObjectModel::getDefinition($targetClass);
        $col->where($association . '.' .$targetDefinition['primary'], '=', '');
        $col->getAll();
    }

    /**
     * @return array
     * @throws ReflectionException
     */
    public function getAssociations()
    {
        $directory = new RecursiveDirectoryIterator(_PS_ROOT_DIR_ . DIRECTORY_SEPARATOR . 'classes');
        $iterator = new RecursiveIteratorIterator($directory);
        $ret = [];
        foreach ($iterator as $path) {
            $file = basename($path);
            if (preg_match("/^.+\.php$/i", $file)) {
                $className = str_replace(".php", "", $file);
                if ($className !== "index") {
                    if (! class_exists($className)) {
                        require_once($path);
                    }
                    if (class_exists($className)) {
                        $reflection = new ReflectionClass($className);
                        if ($reflection->isSubclassOf('ObjectModelCore') && !$reflection->isAbstract()) {
                            $definition = ObjectModel::getDefinition($className);
                            if ($definition && isset($definition['associations'])) {
                                foreach ($definition['associations'] as $key => $assoc) {
                                   if ($key !== PrestaShopCollection::LANG_ALIAS)  {
                                       $ret[$className.':'.$key] = [$className, $key, $assoc];
                                   }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $ret;
    }
}
