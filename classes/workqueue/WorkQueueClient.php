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
 */
class WorkQueueClientCore
{
    /**
     * Enqueues new work queue task
     *
     * @param WorkQueueTask $task
     * @return WorkQueueFuture work queue future descriptor
     */
    public function enqueue(WorkQueueTask $task)
    {
        return $this->getExecutor()->enqueue($task);
    }

    /**
     * Immediately executes work queue task and waits for its completion.
     *
     * If executor implementation does not support immediate execution,
     * WorkQueueImmediateExecutor will be used as a fallback
     *
     * @param WorkQueueTask $task
     * @return WorkQueueFuture
     */
    public function runImmediately(WorkQueueTask $task)
    {
        $executor = $this->getExecutor();
        if ($executor->supportsImmediateExecution()) {
            return $executor->run($task);
        } else {
            return $this->getImmediateExecutor()->run($task);
        }
    }

    /**
     * Returns immediate work queue executor
     *
     * @return WorkQueueExecutor
     */
    public function getImmediateExecutor()
    {
        return WorkQueueImmediateExecutor::getInstance();
    }

    /**
     * Returns work queue executor
     *
     * @return WorkQueueExecutor
     */
    public function getExecutor()
    {
        return $this->getImmediateExecutor();
    }

}
