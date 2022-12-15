<?php

namespace Tests\Support\Helper;

use Codeception\Module;
use PrestaShopDatabaseException;
use PrestaShopException;
use Tab;
use Tools;

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
        return Tools::getAdminToken($controller . (int)Tab::getIdFromClassName($controller) . '1');
    }

}
