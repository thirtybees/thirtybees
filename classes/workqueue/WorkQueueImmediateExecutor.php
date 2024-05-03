<?php
/**
 * Copyright (C) 2017-2024 thirty bees
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
 * @copyright 2017-2024 thirty bees
 * @license   Open Software License (OSL 3.0)
 */

namespace Thirtybees\Core\WorkQueue;

use PrestaShopException;

/**
 * Class WorkQueueImmediateExecutorCore
 */
class WorkQueueImmediateExecutorCore implements WorkQueueExecutor
{
    /**
     * Executor identifier
     */
    const INSTANT_EXECUTOR = 'instant';

    /**
     * @var static
     */
    static $instance = null;

    /**
     * Immediately runs work queue task
     *
     * @param WorkQueueTask $task
     * @return WorkQueueFuture work queue future descriptor
     * @throws PrestaShopException
     */
    public function enqueue(WorkQueueTask $task)
    {
        return $this->run($task);
    }

    /**
     * Immediately runs work queue task
     *
     * @param WorkQueueTask $task
     * @return WorkQueueFuture work queue future descriptor
     * @throws PrestaShopException
     */
    public function run(WorkQueueTask $task)
    {
        return new WorkQueueFuture(
            $this,
            $this->getId($task),
            $task->run()
        );
    }

    /**
     * @return string
     */
    public function getExecutorIdentifier()
    {
        return static::INSTANT_EXECUTOR;
    }

    /**
     * @return bool
     */
    public function supportsImmediateExecution()
    {
        return true;
    }

    /**
     * Generates id for task
     *
     * @param WorkQueueTask $task
     * @return string
     */
    protected function getId(WorkQueueTask $task)
    {
        if ($task->id) {
            return WorkQueueTask::class . '::' . $task->id;
        }
        return $task->task . '::' . microtime(true);
    }

    /**
     * @return static
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }
}
