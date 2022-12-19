<?php

namespace Tests\Support\Helper;

use Codeception\Module;
use Context;
use Employee;
use PrestaShopDatabaseException;
use PrestaShopException;
use Tab;
use Tools;
use Validate;

class Functional extends Module
{
    /**
     * @param string $controller
     *
     * @return bool|string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getAdminToken($controller)
    {
        return Tools::getAdminTokenLite($controller, $this->ensureBackOfficeContext());
    }

    /**
     * @return Context
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function ensureBackOfficeContext()
    {
        $context = Context::getContext();
        if (! Validate::isLoadedObject($context->employee)) {
            $context->employee = new Employee(1);
        }
        return $context;
    }

}
