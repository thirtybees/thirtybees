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

/**
 * Class ScheduledTaskExecutionCore
 *
 * @since 1.3.0
 */
class ScheduledTaskExecutionCore extends ObjectModel
{
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'scheduled_task_execution',
        'primary' => 'id_scheduled_task_execution',
        'multishop' => false,
        'fields'  => [
            'id_scheduled_task'  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'id_workqueue_task'  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'date_add'           => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false],
        ]
    ];

    /**
     * @var int Scheduled task id
     */
    public $id_scheduled_task;

    /**
     * @var int Work queue task id
     */
    public $id_workqueue_task;

    /**
     * @var string DateTime of execution
     */
    public $date_add;

}
