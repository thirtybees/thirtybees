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
     * Enqueues new work queue task
     *
     * @param string $taskType Work queue task type
     * @param array $payload Work queue task payload
     * @param boolean $persistResult if true, result of task execution will be persisted in database
     *
     * @return string|int work queue id
     */
    public function enqueue($taskType, $payload, $persistResult)
    {
        // TODO: implement
        return 0;
    }
}
