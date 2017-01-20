<?php
/**
 * 2007-2016 PrestaShop
 *
 * Thirty Bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017 Thirty Bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.thirtybees.com for more information.
 *
 *  @author    Thirty Bees <contact@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017 Thirty Bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

namespace PrestaShop\PrestaShop\Tests\Fake;

use Core_Business_ConfigurationInterface;
use Exception;

/**
 * Class FakeConfiguration
 *
 * @package PrestaShop\PrestaShop\Tests\Fake
 */
class FakeConfiguration implements Core_Business_ConfigurationInterface
{
    private $keys;

    /**
     * FakeConfiguration constructor.
     *
     * @param array $keys
     */
    public function __construct(array $keys)
    {
        $this->keys = $keys;
    }

    /**
     * @param string $key
     *
     * @return mixed
     * @throws Exception
     */
    public function get($key)
    {
        if (!array_key_exists($key, $this->keys)) {
            throw new Exception("Key $key does not exist in the fake configuration.");
        }

        return $this->keys[$key];
    }
}
