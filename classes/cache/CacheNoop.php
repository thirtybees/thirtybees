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

/**
 * Class CacheNoop
 *
 * Dummy cache implementation
 */
class CacheNoopCore extends Cache
{

    /***
     * This cache is never available
     *
     * @return bool
     */
    public function isAvailable()
    {
        return false;
    }

    /**
     * Cache a data
     *
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     *
     * @return bool
     */
    protected function _set($key, $value, $ttl = 0)
    {
        // no-op implementation
        return false;
    }

    /**
     * Retrieve a cached data by key
     *
     * @param string $key
     *
     * @return mixed
     */
    protected function _get($key)
    {
        return null;
    }

    /**
     * Check if a data is cached by key
     *
     * @param string $key
     *
     * @return bool
     */
    protected function _exists($key)
    {
        return false;
    }

    /**
     * Delete a data from the cache by key
     *
     * @param string $key
     *
     * @return bool
     */
    protected function _delete($key)
    {
        return false;
    }

    /**
     * Write keys index
     *
     * @return void
     */
    protected function _writeKeys()
    {
        // no-op implementation
    }

    /**
     * Clean all cached data
     *
     * @return bool
     */
    public function flush()
    {
        return true;
    }
}
