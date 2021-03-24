<?php
/**
 * Copyright (C) 2021-2021 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2021-2021 thirty bees
 * @license   Open Software License (OSL 3.0)
 */

namespace Thirtybees\Core\DependencyInjection;

use Core_Foundation_IoC_Container;
use Exception;
use PrestaShopException;

/**
 * Class ServiceLocatorCore
 *
 * @since 1.3.0
 */
class ServiceLocatorCore
{

    // services
    const SERVICE_SERVICE_LOCATOR = 'Thirtybees\Core\DependencyInjection\ServiceLocator';

    // Legacy services
    const SERVICE_ADAPTER_CONFIGURATION = 'Core_Business_ConfigurationInterface';
    const SERVICE_ADAPTER_DATABASE  = 'Core_Foundation_Database_DatabaseInterface';

    /**
     * @var ServiceLocator singleton instance
     */
    protected static $instance;

    /**
     * @var Core_Foundation_IoC_Container container
     */
    protected $container;

    /**
     * ServiceLocatorCore constructor
     * @param Core_Foundation_IoC_Container $container
     * @throws Exception
     */
    protected function __construct(Core_Foundation_IoC_Container $container = null)
    {
        $this->container = is_null($container)
            ? new Core_Foundation_IoC_Container()
            : $container;

        $this->container->bind(static::SERVICE_SERVICE_LOCATOR, $this, true);
        $this->container->bind(static::SERVICE_ADAPTER_CONFIGURATION, 'Adapter_Configuration', true);
        $this->container->bind(static::SERVICE_ADAPTER_DATABASE, 'Adapter_Database', true);
    }

    /**
     * @return ServiceLocatorCore
     */
    public function getServiceLocator()
    {
        return $this;
    }

    /**
     * @param string $serviceName
     * @return mixed
     * @throws PrestaShopException
     */
    public function getByServiceName($serviceName)
    {
        try {
            return $this->container->make($serviceName);
        } catch (Exception $e) {
            throw new PrestaShopException("Failed to construct service '$serviceName': " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @return ServiceLocator singleton instance
     * @throws PrestaShopException
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            throw new PrestaShopException("Service locator has not been initialized yet");
        }
        return static::$instance;
    }

    /**
     * Method to initialize service locator
     * @param Core_Foundation_IoC_Container $container
     * @throws PrestaShopException
     */
    public static function initialize(Core_Foundation_IoC_Container $container = null)
    {
        if (! is_null(static::$instance)) {
            throw new PrestaShopException("Service locator is already initialized");
        }
        try {
            static::$instance = new static($container);
        } catch (Exception $e) {
            throw new PrestaShopException("Failed to initialize service locator", 0, $e);
        }
    }
}
