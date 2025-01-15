<?php
/**
 * Copyright (C) 2025-2025 thirty bees
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
 * @copyright 2025-2025 thirty bees
 * @license   Open Software License (OSL 3.0)
 */

namespace Thirtybees\Core\Module;

use Context;
use Db;
use DbQuery;
use Module;
use PrestaShopException;
use Thirtybees\Core\DependencyInjection\ServiceLocator;
use Thirtybees\Core\Error\ErrorUtils;
use Throwable;

class MobileDetectHelperCore
{
    /**
     * @var bool|null
     */
    private static $isTablet = null;

    /**
     * @var bool|null
     */
    private static $isMobile = null;

    /**
     * @var string|null
     */
    private static $userAgent = null;

    /**
     * @return bool
     */
    public function isTablet(): bool
    {
        static::detect();
        return (bool)static::$isTablet;
    }

    /**
     * @return bool
     */
    public function isMobile(): bool
    {
        static::detect();
        return (bool)static::$isMobile;
    }

    /**
     * @return string
     */
    public function getUserAgent(): string
    {
        static::detect();;
        return (string)static::$userAgent;
    }

    /**
     * @return void
     */
    protected static function detect(): void
    {
        if (is_null(static::$isTablet)) {
            static::$isMobile = false;
            static::$isTablet = false;
            static::$userAgent = (string)$_SERVER['HTTP_USER_AGENT'];

            try {
                foreach (static::getModulesResponses() as $response) {
                    if (isset($response['isTablet']) && $response['isTablet']) {
                        static::$isTablet = true;
                    }
                    if (isset($response['isMobile']) && $response['isMobile']) {
                        static::$isMobile = true;
                    }
                    if (isset($response['userAgent'])) {
                        static::$userAgent = (string)$response['userAgent'];
                    }
                }
            } catch (Throwable $e) {
                $errorHandler = ServiceLocator::getInstance()->getErrorHandler();
                $errorHandler->logFatalError(ErrorUtils::describeException($e));
            }
        }
    }

    /**
     * Executes hook 'actionDetectMobile' for all installed modules
     *
     * Normally, we would use Hook::getResponses() for this. Unfortunately, that method
     * depends on device type information, which would cause infinite recursion. So we have to
     * call the hook handlers manually in this specific case
     *
     * @return array
     *
     * @throws PrestaShopException
     */
    protected static function getModulesResponses(): array
    {
        $responses = [];
        $sql = (new DbQuery())
            ->select('DISTINCT m.name')
            ->from('module', 'm')
            ->innerJoin('module_shop', 'ms', 'ms.`id_module` = m.`id_module`')
            ->innerJoin('hook_module', 'hm', 'hm.`id_module` = m.`id_module` AND hm.`id_shop` = ms.`id_shop`')
            ->innerJoin('hook', 'h', 'hm.`id_hook` = h.`id_hook`')
            ->where('ms.id_shop = ' . (int)Context::getContext()->shop->id)
            ->where('m.active')
            ->where('ms.enable_device > 0')
            ->where('h.name = "actionDetectMobile"')
            ->orderBy('hm.position');
        $conn = Db::getInstance();
        foreach ($conn->getArray($sql) as $row) {
            $moduleName = $row['name'];
            $moduleInstance = Module::getInstanceByName($moduleName);
            if ($moduleInstance && is_callable([$moduleInstance, 'hookActionDetectMobile'])) {
                $responses[$moduleName] = $moduleInstance->hookActionDetectMobile();
            }
        }
        return $responses;
    }
}