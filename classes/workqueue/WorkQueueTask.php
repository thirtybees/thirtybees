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

namespace Thirtybees\Core\WorkQueue;

use ObjectModel;
use ReflectionClass;
use PrestaShopDatabaseException;
use PrestaShopException;
use ReflectionException;
use Thirtybees\Core\DependencyInjection\ServiceLocator;
use Throwable;

/**
 * Class WorkQueueTaskCore
 */
class WorkQueueTaskCore extends ObjectModel
{
    const STATUS_PENDING = 'pending';
    const STATUS_RUNNING = 'running';
    const STATUS_FAILURE = 'failure';
    const STATUS_SUCCESS = 'success';

    /**
     * @var array Object model definition
     */
    public static $definition = [
        'table'   => 'workqueue_task',
        'primary' => 'id_workqueue_task',
        'multishop' => false,
        'fields'  => [
            // task definition
            'task'                 => ['type' => self::TYPE_STRING, 'size' => 200, 'required' => true],
            'payload'              => ['type' => self::TYPE_STRING, 'size' => self::SIZE_MEDIUM_TEXT],

            // information about running
            'status'               => ['type' => self::TYPE_STRING, 'required' => true, 'values' => [ self::STATUS_PENDING, self::STATUS_RUNNING, self::STATUS_SUCCESS, self::STATUS_FAILURE ]],
            'date_start'           => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => false],
            'duration'             => ['type' => self::TYPE_FLOAT, 'validate' => 'isUnsignedFloat', 'required' => false],
            'result'               => ['type' => self::TYPE_STRING, 'size' => self::SIZE_MEDIUM_TEXT],
            'error'                => ['type' => self::TYPE_STRING, 'size' => self::SIZE_MEDIUM_TEXT],

            // context fields
            'id_employee_context'  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => false],
            'id_shop_context'      => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => false],
            'id_customer_context'  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => false],
            'id_language_context'  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => false],

            // record information
            'date_add'             => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false],
            'date_upd'             => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false],
        ]
    ];

    /**
     * @var string Work queue task identifier, matches classname of WorkQueueTaskCallable
     */
    public $task;

    /**
     * @var string json serialized task parameters
     */
    public $payload;

    /**
     * @var string current task status
     */
    public $status;

    /**
     * @var string datetime of task execution start
     */
    public $date_start;

    /**
     * @var float task duration in seconds
     */
    public $duration;

    /**
     * @var string result
     */
    public $result;

    /**
     * @var string error description
     */
    public $error;

    /**
     * @var int Context value: employee id
     */
    public $id_employee_context;

    /**
     * @var int Context value: shop id
     */
    public $id_shop_context;

    /**
     * @var int Context value: customer id
     */
    public $id_customer_context;

    /**
     * @var int Context value: language id
     */
    public $id_language_context;

    /**
     * @var string datetime when record has been created
     */
    public $date_add;

    /**
     * @var string datetime when record has been updated
     */
    public $date_upd;

    /**
     * @var WorkQueueContext transient object containing execution context
     */
    protected $context;

    /**
     * @var array transient object containing deserialized parameters
     */
    protected $parameters;

    /**
     * @var float transient object, containing timestamp of execution start
     */
    protected $start;

    /**
     * @var array WorkQueueTaskCallable cache map
     */
    protected static $callableMap = [];

    /**
     * Creates new task
     *
     * @param string $task
     * @param array $parameters
     * @param WorkQueueContext $context
     *
     * @return static
     */
    public static function createTask($task, array $parameters, WorkQueueContext $context)
    {
        $instance = new static();
        $instance->task = $task;
        $instance->payload = json_encode($parameters);
        $instance->parameters = $parameters;
        $instance->status = self::STATUS_PENDING;
        $instance->context = $context;
        $instance->id_employee_context = $context->getEmployeeId();
        $instance->id_shop_context = $context->getShopId();
        $instance->id_customer_context = $context->getCustomerId();
        $instance->id_language_context = $context->getLanguageId();
        return $instance;
    }

    /**
     * WorkQueueTaskCore constructor.
     *
     * @param int|null $id
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function __construct($id = null)
    {
        parent::__construct($id);
        if ($this->id) {
            $this->context = new WorkQueueContext(
                $this->id_shop_context,
                $this->id_employee_context,
                $this->id_customer_context,
                $this->id_language_context
            );
            $this->parameters = $this->payload
                ? json_decode($this->payload, true)
                : [];
        }
    }

    /**
     * Runs task
     *
     * This method executes task, handles all exceptions and errors
     *
     * @return string
     */
    public function run()
    {
        $errorHandler = ServiceLocator::getInstance()->getErrorHandler();
        $previousFatalErrorHandler = $errorHandler->setFatalErrorHandler([$this, 'fatalErrorHandler']);

        $this->start = microtime(true);
        $this->status = static::STATUS_RUNNING;
        $this->date_start = date('Y-m-d H:i:s');
        $this->saveRecord(false);

        try {
            $this->result = $this->execute();
            $this->status = static::STATUS_SUCCESS;
            $this->error = null;
            $this->duration = microtime(true) - $this->start;
            $this->saveRecord(false);
        } catch (Throwable $e) {
            $this->status = static::STATUS_FAILURE;
            $this->duration = date('Y-m-d H:i:s');
            $this->result = null;
            $this->error = $e->__toString();
            $this->duration = microtime(true) - $this->start;
            $this->saveRecord(true);
        } finally {
            $errorHandler->setFatalErrorHandler($previousFatalErrorHandler);
        }
        return $this->status;
    }

    /**
     * Executes task, does not handle and task persistence
     * @throws Throwable
     */
    public function execute()
    {
        $callable = static::getTaskCallable($this->task);
        return $callable->execute($this->context, $this->parameters);
    }

    /**
     * Called when unrecoverable error during execution has been encountered
     *
     * @param array $error
     */
    public function fatalErrorHandler($error)
    {
        $this->status = static::STATUS_FAILURE;
        $this->result = null;
        $this->error = "Error: ";
        if (isset($error['message'])) {
            $this->error .= $error['message'];
        } else {
            $this->error .= "Unknown error";
        }
        if (isset($error['file'])) {
            $this->error .= " in file " . $error['file'];
        }
        if (isset($error['line'])){
            $this->error .= " at line " . $error['line'];
        }
        $this->duration = microtime(true) - $this->start;
        $this->saveRecord(true);
    }

    /**
     * Saves this record to the database, if
     *  - it already exists ($this->id is set)
     *  - or if $force parameter is true
     *
     * @param bool $force if true, then record will be saved even if not exists yet
     */
    protected function saveRecord($force)
    {
        if ($force || $this->id) {
            try {
                 $this->save();
            } catch (Throwable $e) {
                $this->error = $e->__toString();
            }
        }
    }

    /**
     * Resolves callable to handle the task execution
     *
     * @param string $task
     * @return WorkQueueTaskCallable
     *
     * @throws PrestaShopException
     */
    protected static function getTaskCallable($task)
    {
        if (! isset(static::$callableMap[$task])) {
            if (class_exists($task)) {
                try {
                    $reflection = new ReflectionClass($task);
                    if (!$reflection->isInstantiable()) {
                        throw new PrestaShopException("Can't instantiate class $task");
                    }
                    if (!$reflection->implementsInterface(WorkQueueTaskCallable::class)) {
                        throw new PrestaShopException("Class $task does not implements WorkQueueTaskCallable interface");
                    }
                    $instance = $reflection->newInstance();
                    static::$callableMap[$task] = $instance;
                } catch (ReflectionException $e) {
                    throw new PrestaShopException("Failed to instantiate WorkQueueTaskCallable class " . $task, 0, $e);
                }
            } else {
                throw new PrestaShopException("Failed to resolve WorkQueueTaskCallable class " . $task);
            }
        }
        return static::$callableMap[$task];
    }

}
