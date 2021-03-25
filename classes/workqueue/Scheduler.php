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

use DateTime;
use Db;
use Exception;

/**
 * Class SchedulerCore
 *
 * @since 1.3.0
 */
class SchedulerCore
{
    /**
     * Database lock name
     */
    const LOCK_NAME = 'THIRTY_BEES_SCHEDULER';

    /**
     * @var Db Database connection
     */
    protected $connection;

    /**
     * @var WorkQueueClient work queue client
     */
    protected $workQueueClient;

    /**
     * SchedulerCore constructor.
     * @param Db $connection
     * @param WorkQueueClient $workQueueClient
     */
    public function __construct(Db $connection, WorkQueueClient $workQueueClient)
    {
        $this->connection = $connection;
        $this->workQueueClient = $workQueueClient;
    }

    /**
     * Executes all scheduled tasks
     *
     * @throws Exception
     */
    public function run()
    {
        // disable timout limit
        @set_time_limit(0);
        if ($this->lock()) {
            try {
                $this->runTasks();
            } finally {
                $this->releaseLock();
            }
        }
    }

    /**
     * Executes all active tasks that should be executed
     *
     * @throws Exception
     */
    protected function runTasks()
    {
        // figure out what tasks should be run
        $asOf = new DateTime();
        $checkedTaskIds = [];
        $taskToRun = [];

        foreach (ScheduledTask::getActiveTasks() as $task) {
            $checkedTaskIds[] = $task->id;
            if ($task->shouldRun($asOf)) {
                $taskToRun[] = $task;
            }
        }

        // mark all checked tasks as checked, just in case something wrong happens later
        ScheduledTask::markTasksChecked($checkedTaskIds, $asOf);

        // execute all tasks
        foreach ($taskToRun as $task) {
            $task->run($this->workQueueClient);
        }
    }

    /**
     * Tries to acquire lock
     *
     * @return bool
     */
    protected function lock()
    {
        try {
            return (bool)(int)$this->connection->getValue("SELECT GET_LOCK('" . static::LOCK_NAME . "', 3)");
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Releases lock
     */
    protected function releaseLock()
    {
        try {
            $this->connection->execute("SELECT RELEASE_LOCK('" . static::LOCK_NAME . "')");
        } catch (Exception $ignored) {
        }
    }

}
