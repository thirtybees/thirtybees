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

use Adapter_Exception;
use DateInterval;
use DateTime;
use Db;
use Exceptin;
use Exception;
use ObjectModel;
use PrestaShopCollection;
use PrestaShopDatabaseException;
use PrestaShopException;

/**
 * Class ScheduledTaskCore
 *
 * @since 1.3.0
 */
class ScheduledTaskCore extends ObjectModel
{
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'scheduled_task',
        'primary' => 'id_scheduled_task',
        'multishop' => false,
        'fields'  => [
            'frequency'               => ['type' => self::TYPE_STRING, 'size' => 40, 'required' => true],
            'name'                    => ['type' => self::TYPE_STRING, 'size' => 200, 'required' => true],
            'description'             => ['type' => self::TYPE_STRING, 'size' => self::SIZE_TEXT],
            'task'                    => ['type' => self::TYPE_STRING, 'size' => 200, 'required' => true],
            'payload'                 => ['type' => self::TYPE_STRING, 'size' => self::SIZE_MEDIUM_TEXT],
            'active'                  => ['type' => self::TYPE_BOOL, 'required' => true, 'default' => true],
            'last_execution'          => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'last_checked'            => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'id_employee_context'     => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => false],
            'id_shop_context'         => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => false],
            'id_customer_context'     => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => false],
            'id_language_context'     => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => false],
            'date_add'                => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false],
            'date_upd'                => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false],
        ]
    ];

    /**
     * @var string Cron expression
     */
    public $frequency;

    /**
     * @var string Task name
     */
    public $name;

    /**
     * @var string Task description
     */
    public $description;

    /**
     * @var string Task type
     */
    public $task;

    /**
     * @var string Json payload
     */
    public $payload;

    /**
     * @var bool Determine if task is active or not
     */
    public $active;

    /**
     * @var integer last execution unix timestamp. Integer is used instead of date to mitigate timezones issues
     */
    public $last_execution;

    /**
     * @var integer last checked unix timestamp. Integer is used instead of date to motigate timezones issues
     */
    public $last_checked;

    /**
     * @var integer id employee
     */
    public $id_employee_context;

    /**
     * @var integer id shop
     */
    public $id_shop_context;

    /**
     * @var integer id customer
     */
    public $id_customer_context;

    /**
     * @var integer id language
     */
    public $id_language_context;

    /**
    /* @var string Object creation date
     */
    public $date_add;

    /**
    /* @var string Object update date
     */
    public $date_upd;

    /**
     * Returns all active scheduled tasks
     *
     * @return ScheduledTask[]
     * @throws PrestaShopException
     */
    public static function getActiveTasks()
    {
        $list = new PrestaShopCollection(static::class);
        $list->where('active', '=', 1);
        return $list->getResults();
    }

    /**
     * Returns all scheduled tasks for given callable
     *
     * @return ScheduledTask[]
     * @throws PrestaShopException
     */
    public static function getTasksForCallable($callable)
    {
        $list = new PrestaShopCollection(static::class);
        $list->where('task', '=', $callable);
        return $list->getResults();
    }

    /**
     * Mark scheduled tasks $taskIds as checked at timestamp $ts
     *
     * @param int[] $taskIds
     * @param DateTime $ts
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function markTasksChecked(array $taskIds, DateTime $ts)
    {
        $taskIds = array_filter(array_map('intval', $taskIds));
        if ($taskIds) {
            Db::getInstance()->update(
                static::$definition['table'],
                [
                    'last_checked' => $ts->getTimestamp()
                ],
                static::$definition['primary'] . ' IN (' . implode(',', $taskIds). ')'
            );
        }
    }

    /**
     * Runs scheduled task
     *
     * Actual task execution is deferred to work queue. This method only creates new work queue task,
     * and mark scheduled task as executed
     *
     * @param WorkQueueClient $workQueueClient
     * @return WorkQueueFuture
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function run(WorkQueueClient $workQueueClient)
    {
        // load context from scheduled task definition
        $context = new WorkQueueContext(
            $this->id_shop_context,
            $this->id_employee_context,
            $this->id_customer_context,
            $this->id_language_context
        );

        $parameters = $this->payload
            ? json_decode($this->payload, true)
            : [];

        // create and persist work queue task
        $task = WorkQueueTask::createTask($this->task, $parameters, $context);
        $task->add();

        // create execution record
        $execution = new ScheduledTaskExecution();
        $execution->id_scheduled_task = $this->id;
        $execution->id_workqueue_task = $task->id;
        $execution->add();

        // enqueue work queue task
        $future = $workQueueClient->enqueue($task);

        // mark task as executed
        $this->last_execution = time();
        $this->update();

        return $future;
    }

    /**
     * Returns true, if the task should be executed
     * This is determined by checking if cron expressions matched any time in range $last_checked ... $asOf
     *
     * @param DateTime $asOf
     * @return bool
     */
    public function shouldRun($asOf)
    {
        try {
            $from = $this->getStartOfCheckInterval();
            $threshold = static::fromTimestamp($asOf->getTimestamp());
            $threshold = $threshold->sub(new DateInterval('P1M'));
            if ($from < $threshold) {
                $from = $threshold;
            }
            if ($from < $asOf) {
                return static::eventOccurredInRange($this->frequency, $from, $asOf);
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Returns true, if the cron $expression occurred in date range $from ... $to
     *
     * @param string $expression cron expression, such as '5 * * * *'
     * @param DateTime $from
     * @param DateTime $to
     *
     * @return bool
     * @throws PrestaShopException
     */
    public static function eventOccurredInRange($expression, $from, $to)
    {
        $matchers = static::parseCronExpression($expression);
        try {
            $minute = new DateInterval('PT1M');
            while ($from <= $to) {
                if (static::cronExpressionMatches($matchers, $from)) {
                    return true;
                }
                $from->add($minute);
            }
        } catch (Exception $e) {
            throw new PrestaShopException("Error occurred when checking cron range", 0, $e);
        }
        return false;
    }

    /**
     * Returns DateTime from which the cron expression will be check
     *
     * @throws Exception
     */
    protected function getStartOfCheckInterval()
    {
        $ts = max((int)$this->last_checked, (int)$this->last_execution);
        if ($ts > 0) {
            $from = static::fromTimestamp($ts);
            $from->add(new DateInterval('PT1M'));
            return $from;
        }
        return static::fromTimestamp(strtotime($this->date_add));
    }

    /**
     * Returns true, if DateTime $ts matches expression
     *
     * @param array $matchers Cron expression matchers returned by method parseCronExpression
     * @param DateTime $ts timestamp to check
     * @return boolean
     */
    protected static function cronExpressionMatches($matchers, $ts)
    {
        return (
            static::cronExpressionPartMatches($matchers['minute'], static::getMinute($ts)) &&
            static::cronExpressionPartMatches($matchers['hour'], static::getHour($ts)) &&
            static::cronExpressionPartMatches($matchers['day_of_month'], static::getDayOfMonth($ts)) &&
            static::cronExpressionPartMatches($matchers['month'], static::getMonth($ts)) &&
            static::cronExpressionPartMatches($matchers['day_of_week'], static::getDayOfWeek($ts))
        );
    }

    /**
     * Parses cron expression, and return array of matchers.
     *
     * @param string $expression
     * @return callable[]
     * @throws PrestaShopException
     */
    protected static function parseCronExpression($expression)
    {
        $parts = array_map('trim', explode(' ', $expression));
        if (count($parts) != 5) {
            throw new PrestaShopException("Invalid cron expression: '" . $expression . "'");
        }
        return [
            'minute' => static::getMatcher($parts[0]),
            'hour' => static::getMatcher($parts[1]),
            'day_of_month' => static::getMatcher($parts[2]),
            'month' => static::getMatcher($parts[3]),
            'day_of_week' => static::getMatcher($parts[4]),
        ];
    }

    /**
     * Parses sub cron expression, such as '*' or '1-7' and returns matcher for it
     *
     * @param string $expression
     * @return callable
     * @throws PrestaShopException
     */
    protected static function getMatcher($expression)
    {
        // any character
        if ($expression === "*") {
            return function() {
                return true;
            };
        }

        // specific number, ie: '3'
        $intValue = (int)$expression;
        if ($intValue >= 0 && "$intValue" === $expression) {
            return function($input) use ($intValue) {
                return (int)$input === $intValue;
            };
        }

        // numeric interval, ie: '2-5'
        if (preg_match("/^([0-9]+)-([0-9]+)/", $expression, $matches)) {
            $min = (int)$matches[1];
            $max = (int)$matches[2];
            return function($input) use ($min, $max) {
                $input = (int)$input;
                return $input >= $min && $input <= $max;
            };
        }

        // n-th value, ie: '*/5'
        if (preg_match("/^\*\/([0-9]+)$/", $expression, $matches)) {
            $modulo = (int)$matches[1];
            if ($modulo === 0) {
                throw new PrestaShopException("Invalid expression: $expression': division by zero");
            }
            return function ($input) use ($modulo) {
                return ($input % $modulo) === 0;
            };
        }

        throw new PrestaShopException("Invalid expression: $expression'");
    }

    /**
     * Return true, if $matcher matches $value
     *
     * @param callable $matcher callable that expects int, and return boolean
     * @param int $value input value
     *
     * @return boolean
     */
    protected static function cronExpressionPartMatches($matcher, $value)
    {
        return !!$matcher((int)$value);
    }

    /**
     * Helper method to return minute part of the datetime object
     *
     * @param DateTime $ts
     * @return int minute of the hour, in rage 0-59
     */
    protected static function getMinute(DateTime $ts)
    {
        return (int)$ts->format("i");
    }

    /**
     * Helper method to return hour part of the datetime object
     *
     * @param DateTime $ts
     * @return int hour of the day, in range 0-23
     */
    protected static function getHour(DateTime $ts)
    {
        return (int)$ts->format("G");
    }

    /**
     * Helper method to return day of month part of the datetime object
     *
     * @param DateTime $ts
     * @return int day of month, in range 1-31
     */
    protected static function getDayOfMonth(DateTime $ts)
    {
        return (int)$ts->format("j");
    }

    /**
     * Helper method to return month the year part of the datetime object
     *
     * @param DateTime $ts
     * @return int month index, in range 1-12
     */
    protected static function getMonth(DateTime $ts)
    {
        return (int)$ts->format("n");
    }

    /**
     * Helper method to return day of the week part of the datetime object
     *
     * @param DateTime $ts
     * @return int day of the week, in range 0-6. 0 represents Sunday, 6 represents Saturday
     */
    protected static function getDayOfWeek(DateTime $ts)
    {
        return (int)$ts->format("w");
    }

    /**
     * Helper method to create DateTime object from unit timestamp
     *
     * @param int $ts unit timestamp
     * @return DateTime
     * @throws Exception
     */
    protected static function fromTimestamp($ts)
    {
        $date = new DateTime();
        $date->setTimestamp($ts);
        return $date;
    }

}
