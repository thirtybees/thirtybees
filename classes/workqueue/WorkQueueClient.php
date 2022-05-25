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

/**
 * Class WorkQueueClientCore
 *
 * @since 1.3.0
 */
class WorkQueueClientCore
{
    /**
     * Fallback work queue executor
     */
    const INSTANT_EXECUTOR = 'instant';

    /**
     * Enqueues new work queue task
     *
     * @param WorkQueueTask $task
     * @return WorkQueueFuture work queue future descriptor
     */
    public function enqueue(WorkQueueTask $task)
    {
        $executor = $this->getExecutor();
        if ($executor) {
            return $executor->enqueue($task);
        } else {
            return $this->runImmediately($task);
        }
    }

    /**
     * Immediately executes work queue task
     *
     * @param WorkQueueTask $task
     * @return WorkQueueFuture
     */
    public function runImmediately(WorkQueueTask $task)
    {
        return new WorkQueueFuture(
            static::INSTANT_EXECUTOR,
            $this->getId($task),
            $task->run()
        );
    }

    /**
     * Returns work queue executor
     */
    public function getExecutor()
    {
        // TODO: implement executors
        return null;
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
        return $task->task . '::' . time();
    }
}
