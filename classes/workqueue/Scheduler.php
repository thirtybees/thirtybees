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

use Configuration;
use DateTime;
use Db;
use Exception;
use PrestaShopException;
use Tools;

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
     * Configuration key: Last cron event timestamp
     */
    const CRON_EVENT_TS = 'SCHEDULER_LAST_CRON_EVENT_TS';

    /**
     * Configuration key: Synthetic cron secret
     */
    const SYNTHETIC_CRON_SECRET = 'SCHEDULER_SYNTHETIC_CRON_SECRET';

    /**
     * Configuration key: Minimal cron interval in seconds
     */
    const MINIMAL_CRON_INTERVAL = 'SCHEDULER_MINIMAL_CRON_INTERVAL';

    /**
     * Default value of cron interval, used if not found in configuration table
     */
    const MINIMAL_CRON_INTERVAL_DEFAULT_VALUE = 600;

    /**
     * Hard limit for minimal cron interval. Used if configuration table contains lower value
     */
    const MINIMAL_CRON_INTERVAL_HARD_LIMIT = 60;

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
     * Returns true, if synthetic cron event is required. This happens when no other cron mechanism are installed,
     * or didn't generated event recently
     *
     * @throws PrestaShopException
     */
    public function syntheticEventRequired()
    {
        $now = time();
        $lastCronEvent = (int)Configuration::getGlobalValue(static::CRON_EVENT_TS);
        $minInterval = (int)Configuration::getGlobalValue(static::MINIMAL_CRON_INTERVAL);
        if (! $minInterval) {
            $minInterval = static::MINIMAL_CRON_INTERVAL_DEFAULT_VALUE;
        }
        $minInterval = max($minInterval, static::MINIMAL_CRON_INTERVAL_HARD_LIMIT);
        $threshold = $lastCronEvent + $minInterval;
        if ($now > $threshold) {
            Configuration::updateGlobalValue(static::SYNTHETIC_CRON_SECRET, Tools::passwdGen(20));
            return true;
        }
        return false;
    }

    /**
     * @return string
     * @throws PrestaShopException
     */
    public function getSyntheticEventSecret()
    {
        $secret = Configuration::getGlobalValue(static::SYNTHETIC_CRON_SECRET);
        if (! $secret) {
            $secret = Tools::passwdGen(20);
            Configuration::updateGlobalValue(static::SYNTHETIC_CRON_SECRET, $secret);
        }
        return $secret;
    }

    /**
     * @throws PrestaShopException
     */
    public function deleteSyntheticEventSecret()
    {
        Configuration::deleteByName(static::SYNTHETIC_CRON_SECRET);
    }

    /**
     * Executes all scheduled tasks
     *
     * @throws Exception
     */
    public function run()
    {
        // update last cron event timestamp
        Configuration::updateGlobalValue(static::CRON_EVENT_TS, time());

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
            /** @var $task ScheduledTask  */
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
