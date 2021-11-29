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

use Context;
use Customer;
use Employee;
use Language;
use PrestaShopException;
use Shop;

/**
 * Class WorkQueueTaskCore
 *
 * @since 1.3.0
 */
class WorkQueueContextCore
{

    /**
     * @var int | null
     */
    protected $shopId;

    /**
     * @var Shop
     */
    protected $shop;

    /**
     * @var int | null
     */
    protected $employeeId;

    /**
     * @var Employee
     */
    protected $employee;

    /**
     * @var int | null
     */
    protected $customerId;

    /**
     * @var Customer
     */
    protected $customer;

    /**
     * @var int | null
     */
    protected $languageId;

    /**
     * @var Language
     */
    protected $language;


    /**
     * WorkQueueContextCore constructor.
     * @param int $shopId
     * @param int $employeeId
     * @param int $customerId
     * @param int $languageId
     */
    public function __construct($shopId, $employeeId, $customerId, $languageId)
    {
        $this->shopId = static::idOrNull($shopId);
        $this->employeeId = static::idOrNull($employeeId);
        $this->customerId = static::idOrNull($customerId);
        $this->languageId = static::idOrNull($languageId);
    }

    /**
     * Creates workqueue context from shop context
     *
     * @param Context $context
     * @return static
     */
    public static function fromContext(Context $context)
    {
        $shop = $context->shop;
        $employee = $context->employee;
        $customer = $context->customer;
        $language = $context->language;

        $workQueueContext = new static(
            is_null($shop) ? 0 : $shop->id,
            is_null($employee) ? 0 : $employee->id,
            is_null($customer) ? 0 : $customer->id,
            is_null($language) ? 0 : $language->id
        );

        $workQueueContext->shop = $shop;
        $workQueueContext->employee = $employee;
        $workQueueContext->customer = $customer;
        $workQueueContext->language = $language;

        return $workQueueContext;
    }

    /**
     * @return int
     */
    public function getShopId()
    {
        return $this->shopId;
    }

    /**
     * @return int
     */
    public function getEmployeeId()
    {
        return $this->employeeId;
    }

    /**
     * @return int
     */
    public function getCustomerId()
    {
        return $this->customerId;
    }

    /**
     * @return int
     */
    public function getLanguageId()
    {
        return $this->languageId;
    }

    /**
     * @return Shop
     * @throws PrestaShopException
     */
    public function getShop()
    {
        if ($this->shopId && is_null($this->shop)) {
            $this->shop = new Shop($this->shopId, $this->languageId);
        }
        return $this->shop;
    }

    /**
     * @return Employee
     * @throws PrestaShopException
     */
    public function getEmployee()
    {
        if ($this->employeeId && is_null($this->employee)) {
            $this->shop = new Employee($this->employeeId);
        }
        return $this->employee;
    }

    /**
     * @return Customer
     * @throws PrestaShopException
     */
    public function getCustomer()
    {
        if ($this->customerId && is_null($this->customer)) {
            $this->customer = new Customer($this->customerId);
        }
        return $this->customer;
    }

    /**
     * @return Language
     * @throws PrestaShopException
     */
    public function getLanguage()
    {
        if ($this->languageId && is_null($this->language)) {
            $this->language = new Language($this->languageId);
        }
        return $this->language;
    }

    /**
     * If input is positive integer (valid ID), then return it, otherwise returns null
     *
     * @param mixed $input
     * @return int | null
     *
     */
    protected static function idOrNull($input)
    {
        $value = (int)$input;
        if ($value) {
            return $value;
        }
        return null;
    }


}
