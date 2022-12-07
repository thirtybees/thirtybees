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

use Controller;
use Core_Foundation_IoC_Container;
use Db;
use Exception;
use PrestaShopException;
use Thirtybees\Core\Error\ErrorHandler;
use Thirtybees\Core\Error\Response\CliErrorResponse;
use Thirtybees\Core\Error\Response\DebugErrorPage;
use Thirtybees\Core\Error\Response\ErrorResponseInterface;
use Thirtybees\Core\Error\Response\ProductionErrorPage;
use Thirtybees\Core\WorkQueue\Scheduler;
use Thirtybees\Core\WorkQueue\WorkQueueClient;
use Throwable;

/**
 * Class ServiceLocatorCore
 */
class ServiceLocatorCore
{

    // services
    const SERVICE_SERVICE_LOCATOR = 'Thirtybees\Core\DependencyInjection\ServiceLocator';
    const SERVICE_SCHEDULER = 'Thirtybees\Core\WorkQueue\Scheduler';
    const SERVICE_WORK_QUEUE_CLIENT = 'Thirtybees\Core\WorkQueue\WorkQueueClient';
    const SERVICE_READ_WRITE_CONNECTION = 'Db';
    const SERVICE_ERROR_HANDLER = 'Thirtybees\Core\Error\ErrorHandler';
    const SERVICE_ERROR_RESPONSE = 'Thirtybees\Core\Error\Response\ErrorResponseInterface';

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
     * @param Core_Foundation_IoC_Container|null $container
     * @throws PrestaShopException
     */
    protected function __construct(Core_Foundation_IoC_Container $container = null)
    {
        $this->container = is_null($container)
            ? new Core_Foundation_IoC_Container()
            : $container;

        // initialize error page
        $this->container->bind(static::SERVICE_ERROR_RESPONSE, $this->getErrorResponse(), true);

        // initialize error handler
        if (! $this->container->knows(static::SERVICE_ERROR_HANDLER)) {
            $errorHandler = new ErrorHandler($this->getByServiceName(static::SERVICE_ERROR_RESPONSE));
            $this->container->bind(static::SERVICE_ERROR_HANDLER, $errorHandler, true);
        }

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
     * @param string $controllerClass
     * @return Controller
     * @throws PrestaShopException
     */
    public function getController($controllerClass)
    {
        $controller = $this->getByServiceName($controllerClass);
        if (! ($controller instanceof Controller)) {
            throw new PrestaShopException("Failed to construct controller, class '$controllerClass' does not extend Controller");
        }
        return $controller;
    }

    /**
     * @return Scheduler
     * @throws PrestaShopException
     */
    public function getScheduler()
    {
        return $this->getByServiceName(static::SERVICE_SCHEDULER);
    }

    /**
     * @return WorkQueueClient
     * @throws PrestaShopException
     */
    public function getWorkQueueClient()
    {
        return $this->getByServiceName(static::SERVICE_WORK_QUEUE_CLIENT);
    }

    /**
     * Returns read/write connection
     *
     * @return Db
     * @throws PrestaShopException
     */
    public function getConnection()
    {
        return $this->getByServiceName(static::SERVICE_READ_WRITE_CONNECTION);
    }

    /**
     * @return ErrorHandler
     */
    public function getErrorHandler()
    {
        try {
            return $this->getByServiceName(static::SERVICE_ERROR_HANDLER);
        } catch (PrestaShopException $e) {
            die('Invariant: error handler must always be known to service locator');
        }
    }

    /**
     * @param string $serviceName
     * @return mixed|object
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
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            die("Service locator has not been initialized yet");
        }
        return static::$instance;
    }

    /**
     * Method to initialize service locator
     * @param Core_Foundation_IoC_Container|null $container
     */
    public static function initialize(Core_Foundation_IoC_Container $container = null)
    {
        if (! is_null(static::$instance)) {
            die("Service locator is already initialized");
        }
        try {
            static::$instance = new static($container);
        } catch (Throwable $e) {
            die("Failed to initialize service locator: ". $e);
        }
    }

    /**
     * @return ErrorResponseInterface
     */
    protected function getErrorResponse()
    {
        if (php_sapi_name() === 'cli') {
            return new CliErrorResponse();
        }
        if (_PS_MODE_DEV_) {
            return new DebugErrorPage();
        }
        return new ProductionErrorPage();
    }
}
