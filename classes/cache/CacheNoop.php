<?php
/**
 * Copyright (C) 2019 thirty bees
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
 * @copyright 2019 thirty bees
 * @license   Open Software License (OSL 3.0)
 */

/**
 * Class CacheNoop
 *
 * Dummy cache implementation
 *
 * @since 1.1.1
 */
class CacheNoopCore extends Cache
{
    /**
     * Cache a data
     *
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     *
     * @return bool
     *
     * @since 1.1.1
     * @version 1.1.1 Initial version
     */
    protected function _set($key, $value, $ttl = 0)
    {
        // no-op implementation
    }

    /**
     * Retrieve a cached data by key
     *
     * @param string $key
     *
     * @return mixed
     *
     * @since 1.1.1
     * @version 1.1.1 Initial version
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
     *
     * @since 1.1.1
     * @version 1.1.1 Initial version
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
     *
     * @since 1.1.1
     * @version 1.1.1 Initial version
     */
    protected function _delete($key)
    {
        return false;
    }

    /**
     * Write keys index
     *
     * @since 1.1.1
     * @version 1.1.1 Initial version
     */
    protected function _writeKeys()
    {
        // no-op implementation
    }

    /**
     * Clean all cached data
     *
     * @return bool
     *
     * @since 1.1.1
     * @version 1.1.1 Initial version
     */
    public function flush()
    {
        return true;
    }
}
