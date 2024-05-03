<?php
/**
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2024 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.thirtybees.com for more information.
 *
 *  @author    thirty bees <contact@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017-2024 thirty bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class Core_Foundation_IoC_Container
 */
class Core_Foundation_IoC_Container
{
    /**
     * List of services and instruction about their creation
     *
     * @var array
     */
    protected $bindings = [];

    /**
     * List of service instances
     *
     * @var array
     */
    protected $instances = [];

    /**
     * List of namespace aliases, currently unused by core
     *
     * @var array
     */
    protected $namespaceAliases = [];

    /**
     * @param string $serviceName
     *
     * @return bool
     */
    public function knows($serviceName)
    {
        return array_key_exists($serviceName, $this->bindings);
    }

    /**
     * @param string $alias
     *
     * @return bool
     */
    protected function knowsNamespaceAlias($alias)
    {
        return array_key_exists($alias, $this->namespaceAliases);
    }

    /**
     * @param string $serviceName
     * @param string|callable|object $constructor
     * @param bool $shared
     *
     * @return static
     */
    public function bind($serviceName, $constructor, $shared = false)
    {
        if (! $this->knows($serviceName)) {
            $this->bindings[$serviceName] = [
                'constructor' => $constructor,
                'shared' => $shared
            ];
        }
        return $this;
    }

    /**
     * @param string $alias
     * @param string $namespacePrefix
     *
     * @return static
     * @throws Core_Foundation_IoC_Exception
     */
    public function aliasNamespace($alias, $namespacePrefix)
    {
        if ($this->knowsNamespaceAlias($alias)) {
            throw new Core_Foundation_IoC_Exception(
                sprintf(
                    'Namespace alias `%1$s` already exists and points to `%2$s`',
                    $alias, $this->namespaceAliases[$alias]
                )
            );
        }

        $this->namespaceAliases[$alias] = $namespacePrefix;
        return $this;
    }

    /**
     * @param string $className
     *
     * @return string
     */
    public function resolveClassName($className)
    {
        $colonPos = strpos($className, ':');
        if (0 !== $colonPos) {
            $alias = substr($className, 0, $colonPos);
            if ($this->knowsNamespaceAlias($alias)) {
                $class = ltrim(substr($className, $colonPos + 1), '\\');
                return $this->namespaceAliases[$alias] . '\\' . $class;
            }
        }

        return $className;
    }

    /**
     * @param string $className
     * @param array $alreadySeen
     *
     * @return object
     * @throws Core_Foundation_IoC_Exception
     */
    protected function makeInstanceFromClassName($className, array $alreadySeen)
    {
        $className = $this->resolveClassName($className);

        try {
            $refl = new ReflectionClass($className);
            $args = [];

            if ($refl->isAbstract()) {
                throw new Core_Foundation_IoC_Exception(sprintf('Cannot build abstract class: `%s`.', $className));
            }

            $classConstructor = $refl->getConstructor();

            if ($classConstructor) {
                foreach ($classConstructor->getParameters() as $param) {
                    $paramClass = $this->getParameterClassName($param);
                    if ($paramClass) {
                        $args[] = $this->doMake($paramClass, $alreadySeen);
                    } elseif ($param->isDefaultValueAvailable()) {
                        try {
                            $args[] = $param->getDefaultValue();
                        } catch (Exception $e) {
                            throw new Core_Foundation_IoC_Exception("Failed to resolve default parameter", 0, $e);
                        }
                    } else {
                        throw new Core_Foundation_IoC_Exception(sprintf('Cannot build a `%s`.', $className));
                    }
                }
            }

            if (count($args) > 0) {
                return $refl->newInstanceArgs($args);
            } else {
                // newInstanceArgs with empty array fails in PHP 5.3 when the class
                // doesn't have an explicitly defined constructor
                return $refl->newInstance();
            }
        } catch (ReflectionException $re) {
            throw new Core_Foundation_IoC_Exception(sprintf('This doesn\'t seem to be a class name: `%s`.', $className), 0, $re);
        }
    }


    /**
     * @param string $serviceName
     * @param array $alreadySeen
     *
     * @return mixed|object
     * @throws Core_Foundation_IoC_Exception
     */
    protected function doMake($serviceName, array $alreadySeen = [])
    {
        if (array_key_exists($serviceName, $alreadySeen)) {
            throw new Core_Foundation_IoC_Exception(sprintf(
                'Cyclic dependency detected while building `%s`.',
                $serviceName
            ));
        }

        $alreadySeen[$serviceName] = true;

        if (!$this->knows($serviceName)) {
            $this->bind($serviceName, $serviceName);
        }

        $binding = $this->bindings[$serviceName];

        if ($binding['shared'] && array_key_exists($serviceName, $this->instances)) {
            return $this->instances[$serviceName];
        } else {
            $constructor = $binding['constructor'];

            if (is_callable($constructor)) {
                $service = call_user_func($constructor);
            } elseif (!is_string($constructor)) {
                // user already provided the value, no need to construct it.
                $service = $constructor;
            } else {
                // assume the $constructor is a class name
                $service = $this->makeInstanceFromClassName($constructor, $alreadySeen);
            }

            if ($binding['shared']) {
                $this->instances[$serviceName] = $service;
            }

            return $service;
        }
    }

    /**
     * @param string $serviceName
     *
     * @return mixed|object
     *
     * @throws Core_Foundation_IoC_Exception
     */
    public function make($serviceName)
    {
        return $this->doMake($serviceName, []);
    }

    /**
     * Returns parameter class name, or null
     *
     * @param ReflectionParameter $param
     * @return string|null
     */
    protected function getParameterClassName(ReflectionParameter $param)
    {
        if (PHP_VERSION_ID > 80000) {
            $type = $param->getType();
            if ($type instanceof ReflectionNamedType) {
                return $type->getName();
            }
        } else {
            $paramClass = $param->getClass();
            if ($paramClass) {
                return $paramClass->getName();
            }
        }
        return null;
    }
}
