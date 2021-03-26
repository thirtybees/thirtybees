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
 * Class WorkQueueFutureCore
 *
 * @since 1.3.0
 */
class WorkQueueFutureCore
{
    /**
     * @var string internal id
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
     * @param string $implementation
     * @param string $id
     * @param string $status
     */
    public function __construct($implementation, $id, $status)
    {
        $this->implementation = $implementation;
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
