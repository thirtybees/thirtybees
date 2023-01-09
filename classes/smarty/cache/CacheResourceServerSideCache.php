<?php
/**
 * Copyright (C) 2022-2022 thirty bees
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

namespace Thirtybees\Core\Smarty\Cache;

use Cache;
use Smarty_CacheResource_Custom;

/**
 * Class CacheResourceMysqlCore
 */
class CacheResourceServerSideCacheCore extends Smarty_CacheResource_Custom
{
    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @param Cache $cache
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * fetch cached content and its modification time from server side cache
     *
     * @param string $id unique cache content identifier
     * @param string $name template name
     * @param string $cacheId cache id
     * @param string $compileId compile id
     * @param string $content cached content
     * @param int $mtime cache modification timestamp (epoch)
     *
     * @return void
     *
     */
    protected function fetch($id, $name, $cacheId, $compileId, &$content, &$mtime)
    {
        $cacheKey = $this->getCacheKey($name, $cacheId, $compileId);
        $value = $this->cache->get($cacheKey);
        if (is_object($value)) {
            $value = (array)$value;
        }
        if (is_array($value) && isset($value['mtime'])) {
            $mtime = (int)$value['mtime'];
            $content = $value['content'];
        } else {
            $content = null;
            $mtime = null;
        }
    }

    /**
     * Fetch cached content's modification timestamp from server side cache
     *
     * @param string $id unique cache content identifier
     * @param string $name template name
     * @param string $cacheId cache id
     * @param string $compileId compile id
     *
     * @return int|boolean timestamp (epoch) the template was modified, or false if not found
     */
    protected function fetchTimestamp($id, $name, $cacheId, $compileId)
    {
        $value = $this->cache->get($this->getCacheKey($name, $cacheId, $compileId));
        if (is_object($value)) {
            $value = (array)$value;
        }
        if (is_array($value) && isset($value['mtime'])) {
            return (int)$value['mtime'];
        }
        return false;
    }

    /**
     * Save content to server side cache
     *
     * @param string $id unique cache content identifier
     * @param string $name template name
     * @param string $cacheId cache id
     * @param string $compileId compile id
     * @param int|null $expTime seconds till expiration time in seconds or null
     * @param string $content content to cache
     *
     * @return bool success
     */
    protected function save($id, $name, $cacheId, $compileId, $expTime, $content)
    {
        $value = [
            'mtime' => time(),
            'content' => $content
        ];
        return $this->cache->set($this->getCacheKey($name, $cacheId, $compileId), $value, $expTime);
    }

    /**
     * Delete content from cache
     *
     * @param string $name template name
     * @param string $cacheId cache id
     * @param string $compileId compile id
     * @param int|null $expTime seconds till expiration or null
     *
     * @return int number of deleted caches
     */
    protected function delete($name, $cacheId, $compileId, $expTime)
    {
        if ($name === null && $cacheId === null && $compileId === null) {
            $this->cache->flush();
            return -1;
        } else {
            $key = $this->getCacheKey($name, $cacheId, $compileId);
            if ($name && !$cacheId) {
                $key = $key . '*';
            }
            $deleted = $this->cache->delete($key);
            if (is_array($deleted)) {
                return count($deleted);
            }
            return 1;
        }
    }


    /**
     * @param string $name
     * @param string $cacheId
     * @param string $compileId
     *
     * @return string
     */
    protected function getCacheKey($name, $cacheId, $compileId)
    {
        $parts = ['smarty'];
        if ($name) {
            $name = trim(str_replace(_PS_ROOT_DIR_, '', $name), '/');
            $parts[] = $name;
        }
        if ($cacheId) {
            $cacheId = str_replace('*', '_', $cacheId);
            $parts[] = $cacheId;
        }
        if ($compileId) {
            $compileId = str_replace('*', '_', $compileId);
            $parts[] = $compileId;
        }
        return implode('~', $parts);
    }
}
