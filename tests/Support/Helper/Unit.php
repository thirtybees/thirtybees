<?php
namespace Tests\Support\Helper;
// here you can define custom actions
// all public methods declared in helper class will be available in $I

use Codeception\Module;
use ReflectionClass;
use ReflectionException;

class Unit extends Module
{
    /**
     * Call protected/private method of a class.
     *
     * @param object $object Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     * @throws ReflectionException
     */
    public function invokeMethod($object, $methodName, array $parameters = [])
    {
        if (is_string($object)) {
            $reflection = new ReflectionClass($object);
        } else {
            $reflection = new ReflectionClass(get_class($object));
        }
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    /**
     * Calls protected/private method of a class
     *
     * @param string $className
     * @param string $methodName
     * @param array $parameters
     * @return mixed
     * @throws ReflectionException
     */
    public function invokeStaticMethod($className, $methodName, array $parameters = [])
    {
        $reflection = new ReflectionClass($className);

        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs(null, $parameters);
    }
}
