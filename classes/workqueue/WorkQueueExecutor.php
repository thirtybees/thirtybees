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
 * Class WorkQueueExecutor
 */
interface WorkQueueExecutor
{

    /**
     * Returns unique identifier of executor
     *
     * @return string
     */
    public function getExecutorIdentifier();

    /**
     * Enqueues work queue task
     *
     * @param WorkQueueTask $task
     * @return WorkQueueFuture
     */
    public function enqueue(WorkQueueTask $task);

    /**
     * Immediately runs work queue task, if supported
     *
     * This method must be implemented if supportsImmediateExecution()
     * method returns true
     *
     * @param WorkQueueTask $task
     * @return WorkQueueFuture
     */
    public function run(WorkQueueTask $task);

    /**
     * Returns true, if immediate execution is supported by this work queue
     * implementation.
     *
     * If this method returns true, then method run() must be implemented
     *
     * @return boolean
     */
    public function supportsImmediateExecution();
}
