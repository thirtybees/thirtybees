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
 * Class WorkQueueFutureCore
 */
class WorkQueueFutureCore
{
    /**
     * @var string unique internal id, implementation specific
     */
    protected $id;

    /**
     * @var string work queue implementation
     */
    protected $implementation;

    /**
     * @var string task status
     */
    protected $status;

    /**
     * WorkQueueFutureCore constructor.
     *
     * @param WorkQueueExecutor $executor
     * @param string $id
     * @param string $status
     * @throws PrestaShopException
     */
    public function __construct(WorkQueueExecutor $executor, $id, $status)
    {
        if (! in_array($status, [
            WorkQueueTask::STATUS_PENDING,
            WorkQueueTask::STATUS_RUNNING,
            WorkQueueTask::STATUS_SUCCESS,
            WorkQueueTask::STATUS_FAILURE,
        ])) {
            throw new PrestaShopException('Invalid work queue status: ' . $status);
        }
        $this->implementation = $executor->getExecutorIdentifier();
        $this->id = $id;
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getImplementation()
    {
        return $this->implementation;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

}
