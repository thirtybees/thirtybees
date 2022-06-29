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
use Db;
use Exception;
use PrestaShopException;
use function PHPUnit\Framework\throwException;

/**
 * Class ServiceLocatorCore
 *
 * @since 1.3.0
 */
class ServiceLocatorCore
{

    // services
    const SERVICE_SERVICE_LOCATOR = 'Thirtybees\Core\DependencyInjection\ServiceLocator';
    const SERVICE_SCHEDULER = 'Thirtybees\Core\WorkQueue\Scheduler';
    const SERVICE_WORK_QUEUE_CLIENT = 'Thirtybees\Core\WorkQueue\WorkQueueClient';
    const SERVICE_READ_WRITE_CONNECTION = 'Db';

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

        // services
        $this->container->bind(static::SERVICE_SERVICE_LOCATOR, $this, true);
        $this->container->bind(static::SERVICE_WORK_QUEUE_CLIENT, static::SERVICE_WORK_QUEUE_CLIENT, true);
        $this->container->bind(static::SERVICE_SCHEDULER, static::SERVICE_SCHEDULER, true);
        $this->container->bind(static::SERVICE_READ_WRITE_CONNECTION, [Db::class, 'getInstance'],true);

        // legacy services
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
     * Instantiates controller class
     *
     * @param $controllerClass
     * @return \Controller
     * @throws PrestaShopException
     */
    public function getController($controllerClass)
    {
        $controller = $this->getByServiceName($controllerClass);
        if (! ($controller instanceof \Controller)) {
            throw new PrestaShopException("Failed to construct controller, class '$controllerClass' does not extend Controller");
        }
        return $controller;
    }

    /**
     * @return \Thirtybees\Core\WorkQueue\Scheduler
     * @throws PrestaShopException
     */
    public function getScheduler()
    {
        return $this->getByServiceName(static::SERVICE_SCHEDULER);
    }

    /**
     * @return \Thirtybees\Core\WorkQueue\WorkQueueClient
     * @throws PrestaShopException
     */
    public function getWorkQueueClient()
    {
        return $this->getByServiceName(static::SERVICE_WORK_QUEUE_CLIENT);
    }

    /**
     * Returns read/write connection
     *
     * @return \Db
     * @throws PrestaShopException
     */
    public function getConnection()
    {
        return $this->getByServiceName(static::SERVICE_READ_WRITE_CONNECTION);
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
